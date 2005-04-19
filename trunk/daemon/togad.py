#!/usr/bin/env python

import xml.sax
import xml.sax.handler
import socket
import sys
import string
import os
import os.path
import time
import threading
import random
from types import *
import DBClass

# Specify debugging level here;
#
# 11 = XML: metrics
# 10 = XML: host, cluster, grid, ganglia
# 9  = RRD activity, gmetad config parsing
# 8  = RRD file activity
# 6  = SQL
# 1  = daemon threading
#
DEBUG_LEVEL = 7

# Where is the gmetad.conf located
#
GMETAD_CONF = '/etc/gmetad.conf'

# Where to grab XML data from
# Normally: local gmetad (port 8651)
#
# Syntax: <hostname>:<port>
#
ARCHIVE_XMLSOURCE = "localhost:8651"

# List of data_source names to archive for
#
# Syntax: [ "<clustername>", "<clustername>" ]
#
ARCHIVE_DATASOURCES = [ "LISA Cluster" ]

# Where to store the archived rrd's
#
ARCHIVE_PATH = '/data/toga/rrds'

# Amount of hours to store in one single archived .rrd
#
ARCHIVE_HOURS_PER_RRD = 12

# Toga's SQL dbase to use
#
# Syntax: <hostname>/<database>
#
TOGA_SQL_DBASE = "localhost/toga"

# Wether or not to run as a daemon in background
#
DAEMONIZE = 0

######################
#                    #
# Configuration ends #
#                    #
######################

###
# You'll only want to change anything below here unless you 
# know what you are doing (i.e. your name is Ramon Bastiaans :D )
###

# What XML data types not to store
#
UNSUPPORTED_ARCHIVE_TYPES = [ 'string' ]

# Maximum time (in seconds) a parsethread may run
#
PARSE_TIMEOUT = 60

# Maximum time (in seconds) a storethread may run
#
STORE_TIMEOUT = 360

"""
This is TOrque-GAnglia's data Daemon
"""

class DataSQLStore:

	db_vars = None
	dbc = None

	def __init__( self, hostname, database ):

		self.db_vars = DBClass.InitVars(DataBaseName=database,
				User='root',
				Host=hostname,
				Password='',
				Dictionary='true')

		try:
			self.dbc     = DBClass.DB(self.db_vars)
		except DBClass.DBError, details:
			print 'Error in connection to db: %s' %details
			sys.exit(1)

	def setDatabase(self, statement):
		ret = self.doDatabase('set', statement)
		return ret
		
	def getDatabase(self, statement):
		ret = self.doDatabase('get', statement)
		return ret

	def doDatabase(self, type, statement):

		debug_msg( 6, 'doDatabase(): %s: %s' %(type, statement) )
		try:
			if type == 'set':
				result = self.dbc.Set( statement )
				self.dbc.Commit()
			elif type == 'get':
				result = self.dbc.Get( statement )
				
		except DBClass.DBError, detail:
			operation = statement.split(' ')[0]
			print "%s operation on database failed while performing\n'%s'\n%s"\
				%(operation, statement, detail)
			sys.exit(1)

		debug_msg( 6, 'doDatabase(): result: %s' %(result) )
		return result

	def getNodeId( self, hostname ):

		id = self.getDatabase( "SELECT node_id FROM nodes WHERE node_hostname = '%s'" %hostname )

		if len( id ) > 0:

			id = id[0][0]

			return id
		else:
			return None

	def getNodeIds( self, hostnames ):

		ids = [ ]

		for node in hostnames:

			id = self.getNodeId( node )

			if id:
				ids.append( id )

		return ids

	def getJobId( self, jobid ):

		id = self.getDatabase( "SELECT job_id FROM jobs WHERE job_id = '%s'" %jobid )

		if id:
			id = id[0][0]

			return id
		else:
			return None

	def addJob( self, job_id, jobattrs ):

		if not self.getJobId( job_id ):

			self.mutateJob( 'insert', job_id, jobattrs ) 
		else:
			self.mutateJob( 'update', job_id, jobattrs )

	def mutateJob( self, action, job_id, jobattrs ):

		job_values = [ 'name', 'queue', 'owner', 'requested_time', 'requested_memory', 'ppn', 'status', 'start_timestamp', 'stop_timestamp' ]

		insert_col_str = 'job_id'
		insert_val_str = "'%s'" %job_id
		update_str = None

		debug_msg( 6, 'mutateJob(): %s %s' %(action,job_id))

		for valname, value in jobattrs.items():

			if valname in job_values and value:

				column_name = 'job_' + valname

				if action == 'insert':

					if not insert_col_str:
						insert_col_str = column_name
					else:
						insert_col_str = insert_col_str + ',' + column_name

					if not insert_val_str:
						insert_val_str = value
					else:
						insert_val_str = insert_val_str + ",'%s'" %value

				elif action == 'update':
					
					if not update_str:
						update_str = "%s='%s'" %(column_name, value)
					else:
						update_str = update_str + ",%s='%s'" %(column_name, value)

			elif valname == 'nodes' and value:

				self.addNodes( value )
				node_list = value

		if action == 'insert':

			self.setDatabase( "INSERT INTO jobs ( %s ) VALUES ( %s )" %( insert_col_str, insert_val_str ) )
			#ids = self.getNodeIds( node_list )

			#self.addJobNodes( job_id, ids )
		elif action == 'update':

			self.setDatabase( "UPDATE jobs SET %s WHERE job_id=%s" %(update_str, job_id) )

	def addNodes( self, hostnames ):

		for node in hostnames:

			id = self.getNodeId( node )
	
			if not id:
				self.setDatabase( "INSERT INTO nodes ( node_hostname ) VALUES ( '%s' )" %node )

	def addJobNodes( self, jobid, nodes ):

		for node in nodes:
			self.addJobNode( jobid, node )

	def addJobNode( self, jobid, nodeid ):

		self.setDatabase( "INSERT INTO job_nodes (job_id,node_id) VALUES ( %s,%s )" %(jobid, nodeid) )

	def storeJobInfo( self, jobid, jobattrs ):

		self.addJob( jobid, jobattrs )

class RRDMutator:
	"""A class for performing RRD mutations"""

	binary = '/usr/bin/rrdtool'

	def __init__( self, binary=None ):
		"""Set alternate binary if supplied"""

		if binary:
			self.binary = binary

	def create( self, filename, args ):
		"""Create a new rrd with args"""

		return self.perform( 'create', '"' + filename + '"', args )

	def update( self, filename, args ):
		"""Update a rrd with args"""

		return self.perform( 'update', '"' + filename + '"', args )

	def grabLastUpdate( self, filename ):
		"""Determine the last update time of filename rrd"""

		last_update = 0

		debug_msg( 8, self.binary + ' info "' + filename + '"' )

		for line in os.popen( self.binary + ' info "' + filename + '"' ).readlines():

			if line.find( 'last_update') != -1:

				last_update = line.split( ' = ' )[1]

		if last_update:
			return last_update
		else:
			return 0

	def perform( self, action, filename, args ):
		"""Perform action on rrd filename with args"""

		arg_string = None

		if type( args ) is not ListType:
			debug_msg( 8, 'Arguments needs to be of type List' )
			return 1

		for arg in args:

			if not arg_string:

				arg_string = arg
			else:
				arg_string = arg_string + ' ' + arg

		debug_msg( 8, self.binary + ' ' + action + ' ' + filename + ' ' + arg_string  )

		for line in os.popen( self.binary + ' ' + action + ' ' + filename + ' ' + arg_string ).readlines():

			if line.find( 'ERROR' ) != -1:

				error_msg = string.join( line.split( ' ' )[1:] )
				debug_msg( 8, error_msg )
				return 1

		return 0

class XMLProcessor:
	"""Skeleton class for XML processor's"""

	def daemon( self ):
		"""Run as daemon forever"""

		# Fork the first child
		#
		pid = os.fork()

		if pid > 0:

			sys.exit(0)  # end parent

		# creates a session and sets the process group ID 
		#
		os.setsid()

		# Fork the second child
		#
		pid = os.fork()

		if pid > 0:

			sys.exit(0)  # end parent

		# Go to the root directory and set the umask
		#
		os.chdir('/')
		os.umask(0)

		sys.stdin.close()
		sys.stdout.close()
		sys.stderr.close()

		os.open('/dev/null', 0)
		os.dup(0)
		os.dup(0)

		self.run()

	def run( self ):
		"""Do main processing of XML here"""

		pass

class TorqueXMLProcessor( XMLProcessor ):
	"""Main class for processing XML and acting with it"""

	def __init__( self ):
		"""Setup initial XML connection and handlers"""

		self.myXMLGatherer = XMLGatherer( ARCHIVE_XMLSOURCE.split( ':' )[0], ARCHIVE_XMLSOURCE.split( ':' )[1] ) 
		self.myXMLSource = self.myXMLGatherer.getFileObject()
		self.myXMLHandler = TorqueXMLHandler()
		self.myXMLError = XMLErrorHandler()
		self.config = GangliaConfigParser( GMETAD_CONF )

	def run( self ):
		"""Main XML processing"""

		debug_msg( 5, printTime() + ' - torquexmlthread(): started.' )

		while( 1 ):

			self.myXMLSource = self.myXMLGatherer.getFileObject()
			debug_msg( 1, printTime() + ' - torquexmlthread(): Parsing..' )
			xml.sax.parse( self.myXMLSource, self.myXMLHandler, self.myXMLError )
			debug_msg( 1, printTime() + ' - torquexmlthread(): Done parsing.' )
			debug_msg( 1, printTime() + ' - torquexmlthread(): Sleeping.. (%ss)' %(str( self.config.getLowestInterval() ) ) )
			time.sleep( self.config.getLowestInterval() )

class TorqueXMLHandler( xml.sax.handler.ContentHandler ):
	"""Parse Torque's jobinfo XML from our plugin"""

	jobAttrs = { }
	jobs_to_store = [ ]

	def __init__( self ):

		self.ds = DataSQLStore( TOGA_SQL_DBASE.split( '/' )[0], TOGA_SQL_DBASE.split( '/' )[1] )

	def startElement( self, name, attrs ):
		"""
		This XML will be all gmetric XML
		so there will be no specific start/end element
		just one XML statement with all info
		"""
		
		heartbeat = 0
		
		jobinfo = { }

		if name == 'METRIC':

			metricname = attrs.get( 'NAME', "" )

			if metricname == 'TOGA-HEARTBEAT':
				self.heartbeat = attrs.get( 'VAL', "" )

			elif metricname.find( 'TOGA-JOB' ) != -1:

				job_id = metricname.split( 'TOGA-JOB-' )[1]
				val = attrs.get( 'VAL', "" )

				check_change = 0

				if self.jobAttrs.has_key( job_id ):
					check_change = 1

				valinfo = val.split( ' ' )

				for myval in valinfo:

					if len( myval.split( '=' ) ) > 1:

						valname = myval.split( '=' )[0]
						value = myval.split( '=' )[1]

						if valname == 'nodes':
							value = value.split( ';' )

						jobinfo[ valname ] = value

				if check_change:
					if self.jobinfoChanged( self.jobAttrs, job_id, jobinfo ):
						self.jobAttrs[ job_id ] = self.setJobAttrs( self.jobAttrs[ job_id ], jobinfo )
						if not job_id in self.jobs_to_store:
							self.jobs_to_store.append( job_id )

						debug_msg( 0, 'jobinfo for job %s has changed' %job_id )
				else:
					self.jobAttrs[ job_id ] = jobinfo

					if not job_id in self.jobs_to_store:
						self.jobs_to_store.append( job_id )

					debug_msg( 0, 'jobinfo for job %s has changed' %job_id )
					
	def endDocument( self ):
		"""When all metrics have gone, check if any jobs have finished"""

		for jobid, jobinfo in self.jobAttrs.items():

			# This is an old job, not in current jobinfo list anymore
			# it must have finished, since we _did_ get a new heartbeat
			#
			if jobinfo['reported'] < self.heartbeat and jobinfo['status'] == 'R' and jobid not in self.jobs_to_store:

				self.jobAttrs[ jobid ]['status'] = 'F'
				self.jobAttrs[ jobid ]['stop_timestamp'] = str( int( jobinfo['reported'] ) + int( jobinfo['poll_interval'] ) )
				if not jobid in self.jobs_to_store:
					self.jobs_to_store.append( jobid )

		debug_msg( 1, printTime() + ' - torquexmlthread(): Storing..' )

		for jobid in self.jobs_to_store:
			self.ds.storeJobInfo( jobid, self.jobAttrs[ jobid ] )	

		debug_msg( 1, printTime() + ' - torquexmlthread(): Done storing.' )

		self.jobs_to_store = [ ]

	def setJobAttrs( self, old, new ):
		"""
		Set new job attributes in old, but not lose existing fields
		if old attributes doesn't have those
		"""

		for valname, value in new.items():
			old[ valname ] = value

		return old
		

	def jobinfoChanged( self, jobattrs, jobid, jobinfo ):
		"""
		Check if jobinfo has changed from jobattrs[jobid]
		if it's report time is bigger than previous one
		and it is report time is recent (equal to heartbeat)
		"""

		ignore_changes = [ 'reported' ]

		if jobattrs.has_key( jobid ):

			for valname, value in jobinfo.items():

				if valname not in ignore_changes:

					if jobattrs[ jobid ].has_key( valname ):

						if value != jobattrs[ jobid ][ valname ]:

							if jobinfo['reported'] > jobattrs[ jobid ][ 'reported' ] and jobinfo['reported'] == self.heartbeat:
								return 1

					else:
						return 1

		return 0

class GangliaXMLHandler( xml.sax.handler.ContentHandler ):
	"""Parse Ganglia's XML"""

	def __init__( self, config ):
		"""Setup initial variables and gather info on existing rrd archive"""

		self.config = config
		self.clusters = { }
		debug_msg( 0, printTime() + ' - Checking existing toga rrd archive..' )
		self.gatherClusters()
		debug_msg( 0, printTime() + ' - Check done.' )

	def gatherClusters( self ):
		"""Find all existing clusters in archive dir"""

		archive_dir = check_dir(ARCHIVE_PATH)

		hosts = [ ]

		if os.path.exists( archive_dir ):

			dirlist = os.listdir( archive_dir )

			for item in dirlist:

				clustername = item

				if not self.clusters.has_key( clustername ) and clustername in ARCHIVE_DATASOURCES:

					self.clusters[ clustername ] = RRDHandler( self.config, clustername )

	def startElement( self, name, attrs ):
		"""Memorize appropriate data from xml start tags"""

		if name == 'GANGLIA_XML':

			self.XMLSource = attrs.get( 'SOURCE', "" )
			self.gangliaVersion = attrs.get( 'VERSION', "" )

			debug_msg( 10, 'Found XML data: source %s version %s' %( self.XMLSource, self.gangliaVersion ) )

		elif name == 'GRID':

			self.gridName = attrs.get( 'NAME', "" )
			self.time = attrs.get( 'LOCALTIME', "" )

			debug_msg( 10, '`-Grid found: %s' %( self.gridName ) )

		elif name == 'CLUSTER':

			self.clusterName = attrs.get( 'NAME', "" )
			self.time = attrs.get( 'LOCALTIME', "" )

			if not self.clusters.has_key( self.clusterName ) and self.clusterName in ARCHIVE_DATASOURCES:

				self.clusters[ self.clusterName ] = RRDHandler( self.config, self.clusterName )

				debug_msg( 10, ' |-Cluster found: %s' %( self.clusterName ) )

		elif name == 'HOST' and self.clusterName in ARCHIVE_DATASOURCES:     

			self.hostName = attrs.get( 'NAME', "" )
			self.hostIp = attrs.get( 'IP', "" )
			self.hostReported = attrs.get( 'REPORTED', "" )

			debug_msg( 10, ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported ) )

		elif name == 'METRIC' and self.clusterName in ARCHIVE_DATASOURCES:

			type = attrs.get( 'TYPE', "" )

			if type not in UNSUPPORTED_ARCHIVE_TYPES:

				myMetric = { }
				myMetric['name'] = attrs.get( 'NAME', "" )
				myMetric['val'] = attrs.get( 'VAL', "" )
				myMetric['time'] = self.hostReported

				self.clusters[ self.clusterName ].memMetric( self.hostName, myMetric )

				debug_msg( 11, ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] ) )

	def storeMetrics( self ):
		"""Store metrics of each cluster rrd handler"""

		for clustername, rrdh in self.clusters.items():

			ret = rrdh.storeMetrics()

			if ret:
				debug_msg( 9, 'An error occured while storing metrics for cluster %s' %clustername )
				return 1

		return 0

class XMLErrorHandler( xml.sax.handler.ErrorHandler ):

	def error( self, exception ):
		"""Recoverable error"""

		debug_msg( 0, 'Recoverable error ' + str( exception ) )

	def fatalError( self, exception ):
		"""Non-recoverable error"""

		exception_str = str( exception )

		# Ignore 'no element found' errors
		if exception_str.find( 'no element found' ) != -1:
			debug_msg( 1, 'No XML data found: probably socket not (re)connected.' )
			return 0

		debug_msg( 0, 'Non-recoverable error ' + str( exception ) )
		sys.exit( 1 )

	def warning( self, exception ):
		"""Warning"""

		debug_msg( 0, 'Warning ' + str( exception ) )

class XMLGatherer:
	"""Setup a connection and file object to Ganglia's XML"""

	s = None
	fd = None

	def __init__( self, host, port ):
		"""Store host and port for connection"""

		self.host = host
		self.port = port
		self.connect()
		self.makeFileDescriptor()

	def connect( self ):
		"""Setup connection to XML source"""

		for res in socket.getaddrinfo( self.host, self.port, socket.AF_UNSPEC, socket.SOCK_STREAM ):

			af, socktype, proto, canonname, sa = res

			try:

				self.s = socket.socket( af, socktype, proto )

			except socket.error, msg:

				self.s = None
				continue

		    	try:

				self.s.connect( sa )

		    	except socket.error, msg:

				self.disconnect()
				continue

		    	break

		if self.s is None:

			debug_msg( 0, 'Could not open socket' )
			sys.exit( 1 )

	def disconnect( self ):
		"""Close socket"""

		if self.s:
			self.s.shutdown( 2 )
			self.s.close()
			self.s = None

	def __del__( self ):
		"""Kill the socket before we leave"""

		self.disconnect()

	def reconnect( self ):
		"""Reconnect"""

		if self.s:
			self.disconnect()

		self.connect()

	def makeFileDescriptor( self ):
		"""Make file descriptor that points to our socket connection"""

		self.reconnect()

		if self.s:
			self.fd = self.s.makefile( 'r' )

	def getFileObject( self ):
		"""Connect, and return a file object"""

		self.makeFileDescriptor()

		if self.fd:
			return self.fd

class GangliaXMLProcessor( XMLProcessor ):
	"""Main class for processing XML and acting with it"""

	def __init__( self ):
		"""Setup initial XML connection and handlers"""

		self.config = GangliaConfigParser( GMETAD_CONF )

		self.myXMLGatherer = XMLGatherer( ARCHIVE_XMLSOURCE.split( ':' )[0], ARCHIVE_XMLSOURCE.split( ':' )[1] ) 
		self.myXMLSource = self.myXMLGatherer.getFileObject()
		self.myXMLHandler = GangliaXMLHandler( self.config )
		self.myXMLError = XMLErrorHandler()

	def run( self ):
		"""Main XML processing; start a xml and storethread"""

		xmlthread = threading.Thread( None, self.processXML, 'xmlthread' )
		storethread = threading.Thread( None, self.storeMetrics, 'storethread' )

		while( 1 ):

			if not xmlthread.isAlive():
				# Gather XML at the same interval as gmetad

				# threaded call to: self.processXML()
				#
				xmlthread = threading.Thread( None, self.processXML, 'xmlthread' )
				xmlthread.start()

			if not storethread.isAlive():
				# Store metrics every .. sec

				# threaded call to: self.storeMetrics()
				#
				storethread = threading.Thread( None, self.storeMetrics, 'storethread' )
				storethread.start()
		
			# Just sleep a sec here, to prevent daemon from going mad. We're all threads here anyway
			time.sleep( 1 )	

	def storeMetrics( self ):
		"""Store metrics retained in memory to disk"""

		debug_msg( 7, printTime() + ' - storethread(): started.' )

		# Store metrics somewhere between every 360 and 640 seconds
		#
		STORE_INTERVAL = random.randint( 360, 640 )

		storethread = threading.Thread( None, self.storeThread, 'storemetricthread' )
		storethread.start()

		debug_msg( 7, printTime() + ' - storethread(): Sleeping.. (%ss)' %STORE_INTERVAL )
		time.sleep( STORE_INTERVAL )
		debug_msg( 7, printTime() + ' - storethread(): Done sleeping.' )

		if storethread.isAlive():

			debug_msg( 7, printTime() + ' - storethread(): storemetricthread() still running, waiting to finish..' )
			storethread.join( STORE_TIMEOUT ) # Maximum time is for storing thread to finish
			debug_msg( 7, printTime() + ' - storethread(): Done waiting.' )

		debug_msg( 7, printTime() + ' - storethread(): finished.' )

		return 0

	def storeThread( self ):
		"""Actual metric storing thread"""

		debug_msg( 1, printTime() + ' - storemetricthread(): started.' )
		debug_msg( 1, printTime() + ' - storemetricthread(): Storing data..' )
		ret = self.myXMLHandler.storeMetrics()
		debug_msg( 1, printTime() + ' - storemetricthread(): Done storing.' )
		debug_msg( 1, printTime() + ' - storemetricthread(): finished.' )
		
		return ret

	def processXML( self ):
		"""Process XML"""

		debug_msg( 1, printTime() + ' - xmlthread(): started.' )

		parsethread = threading.Thread( None, self.parseThread, 'parsethread' )
		parsethread.start()

		debug_msg( 1, printTime() + ' - xmlthread(): Sleeping.. (%ss)' %self.config.getLowestInterval() )
		time.sleep( float( self.config.getLowestInterval() ) )	
		debug_msg( 1, printTime() + ' - xmlthread(): Done sleeping.' )

		if parsethread.isAlive():

			debug_msg( 1, printTime() + ' - xmlthread(): parsethread() still running, waiting to finish..' )
			parsethread.join( PARSE_TIMEOUT ) # Maximum time for XML thread to finish
			debug_msg( 7, printTime() + ' - xmlthread(): Done waiting.' )

		debug_msg( 1, printTime() + ' - xmlthread(): finished.' )

		return 0

	def parseThread( self ):
		"""Actual parsing thread"""

		debug_msg( 1, printTime() + ' - parsethread(): started.' )
		debug_msg( 1, printTime() + ' - parsethread(): Parsing XML..' )
		self.myXMLSource = self.myXMLGatherer.getFileObject()
		ret = xml.sax.parse( self.myXMLSource, self.myXMLHandler, self.myXMLError )
		debug_msg( 1, printTime() + ' - parsethread(): Done parsing.' )
		debug_msg( 1, printTime() + ' - parsethread(): finished.' )

		return ret

class GangliaConfigParser:

	sources = [ ]

	def __init__( self, config ):
		"""Parse some stuff from our gmetad's config, such as polling interval"""

		self.config = config
		self.parseValues()

	def parseValues( self ):
		"""Parse certain values from gmetad.conf"""

		readcfg = open( self.config, 'r' )

		for line in readcfg.readlines():

			if line.count( '"' ) > 1:

				if line.find( 'data_source' ) != -1 and line[0] != '#':

					source = { }
					source['name'] = line.split( '"' )[1]
					source_words = line.split( '"' )[2].split( ' ' )

					for word in source_words:

						valid_interval = 1

						for letter in word:

							if letter not in string.digits:

								valid_interval = 0

						if valid_interval and len(word) > 0:

							source['interval'] = word
							debug_msg( 9, 'polling interval for %s = %s' %(source['name'], source['interval'] ) )
	
					# No interval found, use Ganglia's default	
					if not source.has_key( 'interval' ):
						source['interval'] = 15
						debug_msg( 9, 'polling interval for %s defaulted to 15' %(source['name']) )

					self.sources.append( source )

	def getInterval( self, source_name ):
		"""Return interval for source_name"""

		for source in self.sources:

			if source['name'] == source_name:

				return source['interval']

		return None

	def getLowestInterval( self ):
		"""Return the lowest interval of all clusters"""

		lowest_interval = 0

		for source in self.sources:

			if not lowest_interval or source['interval'] <= lowest_interval:

				lowest_interval = source['interval']

		# Return 15 when nothing is found, so that the daemon won't go insane with 0 sec delays
		if lowest_interval:
			return lowest_interval
		else:
			return 15

class RRDHandler:
	"""Class for handling RRD activity"""

	myMetrics = { }
	lastStored = { }
	timeserials = { }
	slot = None

	def __init__( self, config, cluster ):
		"""Setup initial variables"""

		self.block = 0
		self.cluster = cluster
		self.config = config
		self.slot = threading.Lock()
		self.rrdm = RRDMutator()
		self.gatherLastUpdates()

	def gatherLastUpdates( self ):
		"""Populate the lastStored list, containing timestamps of all last updates"""

		cluster_dir = '%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster )

		hosts = [ ]

		if os.path.exists( cluster_dir ):

			dirlist = os.listdir( cluster_dir )

			for dir in dirlist:

				hosts.append( dir )

		for host in hosts:

			host_dir = cluster_dir + '/' + host
			dirlist = os.listdir( host_dir )

			for dir in dirlist:

				if not self.timeserials.has_key( host ):

					self.timeserials[ host ] = [ ]

				self.timeserials[ host ].append( dir )

			last_serial = self.getLastRrdTimeSerial( host )
			if last_serial:

				metric_dir = cluster_dir + '/' + host + '/' + last_serial
				if os.path.exists( metric_dir ):

					dirlist = os.listdir( metric_dir )

					for file in dirlist:

						metricname = file.split( '.rrd' )[0]

						if not self.lastStored.has_key( host ):

							self.lastStored[ host ] = { }

						self.lastStored[ host ][ metricname ] = self.rrdm.grabLastUpdate( metric_dir + '/' + file )

	def getClusterName( self ):
		"""Return clustername"""

		return self.cluster

	def memMetric( self, host, metric ):
		"""Store metric from host in memory"""

		if self.myMetrics.has_key( host ):

			if self.myMetrics[ host ].has_key( metric['name'] ):

				for mymetric in self.myMetrics[ host ][ metric['name'] ]:

					if mymetric['time'] == metric['time']:

						# Allready have this metric, abort
						return 1
			else:
				self.myMetrics[ host ][ metric['name'] ] = [ ]
		else:
			self.myMetrics[ host ] = { }
			self.myMetrics[ host ][ metric['name'] ] = [ ]

		# Push new metric onto stack
		# atomic code; only 1 thread at a time may access the stack

		# <ATOMIC>
		#
		self.slot.acquire()

		self.myMetrics[ host ][ metric['name'] ].append( metric )

		self.slot.release()
		#
		# </ATOMIC>

	def makeUpdateList( self, host, metriclist ):
		"""
		Make a list of update values for rrdupdate
		but only those that we didn't store before
		"""

		update_list = [ ]
		metric = None

		while len( metriclist ) > 0:

			metric = metriclist.pop( 0 )

			if self.checkStoreMetric( host, metric ):
				update_list.append( '%s:%s' %( metric['time'], metric['val'] ) )

		return update_list

	def checkStoreMetric( self, host, metric ):
		"""Check if supplied metric if newer than last one stored"""

		if self.lastStored.has_key( host ):

			if self.lastStored[ host ].has_key( metric['name'] ):

				if metric['time'] <= self.lastStored[ host ][ metric['name'] ]:

					# This is old
					return 0

		return 1

	def memLastUpdate( self, host, metricname, metriclist ):
		"""
		Memorize the time of the latest metric from metriclist
		but only if it wasn't allready memorized
		"""

		if not self.lastStored.has_key( host ):
			self.lastStored[ host ] = { }

		last_update_time = 0

		for metric in metriclist:

			if metric['name'] == metricname:

				if metric['time'] > last_update_time:

					last_update_time = metric['time']

		if self.lastStored[ host ].has_key( metricname ):
			
			if last_update_time <= self.lastStored[ host ][ metricname ]:
				return 1

		self.lastStored[ host ][ metricname ] = last_update_time

	def storeMetrics( self ):
		"""
		Store all metrics from memory to disk
		and do it to the RRD's in appropriate timeperiod directory
		"""

		for hostname, mymetrics in self.myMetrics.items():	

			for metricname, mymetric in mymetrics.items():

				metrics_to_store = [ ]

				# Pop metrics from stack for storing until none is left
				# atomic code: only 1 thread at a time may access myMetrics

				# <ATOMIC>
				#
				self.slot.acquire() 

				while len( self.myMetrics[ hostname ][ metricname ] ) > 0:

					if len( self.myMetrics[ hostname ][ metricname ] ) > 0:
						metrics_to_store.append( self.myMetrics[ hostname ][ metricname ].pop( 0 ) )

				self.slot.release()
				#
				# </ATOMIC>

				# Create a mapping table, each metric to the period where it should be stored
				#
				metric_serial_table = self.determineSerials( hostname, metricname, metrics_to_store )

				update_rets = [ ]

				for period, pmetric in metric_serial_table.items():

					self.createCheck( hostname, metricname, period )	

					update_ret = self.update( hostname, metricname, period, pmetric )

					if update_ret == 0:

						debug_msg( 9, 'stored metric %s for %s' %( hostname, metricname ) )
					else:
						debug_msg( 9, 'metric update failed' )

					update_rets.append( update_ret )

				if not (1) in update_rets:

					self.memLastUpdate( hostname, metricname, metrics_to_store )

	def makeTimeSerial( self ):
		"""Generate a time serial. Seconds since epoch"""

		# Seconds since epoch
		mytime = int( time.time() )

		return mytime

	def makeRrdPath( self, host, metricname, timeserial ):
		"""Make a RRD location/path and filename"""

		rrd_dir = '%s/%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host, timeserial )
		rrd_file = '%s/%s.rrd' %( rrd_dir, metricname )

		return rrd_dir, rrd_file

	def getLastRrdTimeSerial( self, host ):
		"""Find the last timeserial (directory) for this host"""

		newest_timeserial = 0

		for dir in self.timeserials[ host ]:

			valid_dir = 1

			for letter in dir:
				if letter not in string.digits:
					valid_dir = 0

			if valid_dir:
				timeserial = dir
				if timeserial > newest_timeserial:
					newest_timeserial = timeserial

		if newest_timeserial:
			return newest_timeserial
		else:
			return 0

	def determinePeriod( self, host, check_serial ):
		"""Determine to which period (directory) this time(serial) belongs"""

		period_serial = 0

		if self.timeserials.has_key( host ):

			for serial in self.timeserials[ host ]:

				if check_serial >= serial and period_serial < serial:

					period_serial = serial

		return period_serial

	def determineSerials( self, host, metricname, metriclist ):
		"""
		Determine the correct serial and corresponding rrd to store
		for a list of metrics
		"""

		metric_serial_table = { }

		for metric in metriclist:

			if metric['name'] == metricname:

				period = self.determinePeriod( host, metric['time'] )	

				archive_secs = ARCHIVE_HOURS_PER_RRD * (60 * 60)

				if (int( metric['time'] ) - int( period ) ) > archive_secs:

					# This one should get it's own new period
					period = metric['time']

					if not self.timeserials.has_key( host ):
						self.timeserials[ host ] = [ ]

					self.timeserials[ host ].append( period )

				if not metric_serial_table.has_key( period ):

					metric_serial_table[ period ] = [ ]

				metric_serial_table[ period ].append( metric )

		return metric_serial_table

	def createCheck( self, host, metricname, timeserial ):
		"""Check if an rrd allready exists for this metric, create if not"""

		debug_msg( 9, 'rrdcreate: using timeserial %s for %s/%s' %( timeserial, host, metricname ) )
		
		rrd_dir, rrd_file = self.makeRrdPath( host, metricname, timeserial )

		if not os.path.exists( rrd_dir ):

			try:
				os.makedirs( rrd_dir )

			except OSError, msg:

				if msg.find( 'File exists' ) != -1:

					# Ignore exists errors
					pass

				else:

					print msg
					return

			debug_msg( 9, 'created dir %s' %( str(rrd_dir) ) )

		if not os.path.exists( rrd_file ):

			interval = self.config.getInterval( self.cluster )
			heartbeat = 8 * int( interval )

			params = [ ]

			params.append( '--step' )
			params.append( str( interval ) )

			params.append( '--start' )
			params.append( str( int( timeserial ) - 1 ) )

			params.append( 'DS:sum:GAUGE:%d:U:U' %heartbeat )
			params.append( 'RRA:AVERAGE:0.5:1:%s' %(ARCHIVE_HOURS_PER_RRD * 240) )

			self.rrdm.create( str(rrd_file), params )

			debug_msg( 9, 'created rrd %s' %( str(rrd_file) ) )

	def update( self, host, metricname, timeserial, metriclist ):
		"""
		Update rrd file for host with metricname
		in directory timeserial with metriclist
		"""

		debug_msg( 9, 'rrdupdate: using timeserial %s for %s/%s' %( timeserial, host, metricname ) )

		rrd_dir, rrd_file = self.makeRrdPath( host, metricname, timeserial )

		update_list = self.makeUpdateList( host, metriclist )

		if len( update_list ) > 0:
			ret = self.rrdm.update( str(rrd_file), update_list )

			if ret:
				return 1
		
			debug_msg( 9, 'updated rrd %s with %s' %( str(rrd_file), string.join( update_list ) ) )

		return 0

def main():
	"""Program startup"""

	myTProcessor = TorqueXMLProcessor()
	myGProcessor = GangliaXMLProcessor()

	if DAEMONIZE:
		torquexmlthread = threading.Thread( None, myTProcessor.daemon, 'tprocxmlthread' )
		gangliaxmlthread = threading.Thread( None, myGProcessor.daemon, 'gprocxmlthread' )
	else:
		torquexmlthread = threading.Thread( None, myTProcessor.run, 'tprocxmlthread' )
		gangliaxmlthread = threading.Thread( None, myGProcessor.run, 'gprocxmlthread' )

	torquexmlthread.start()
	gangliaxmlthread.start()

# Global functions

def check_dir( directory ):
	"""Check if directory is a proper directory. I.e.: Does _not_ end with a '/'"""

	if directory[-1] == '/':
		directory = directory[:-1]

	return directory

def debug_msg( level, msg ):
	"""Only print msg if it is not below our debug level"""

	if (DEBUG_LEVEL >= level):
		sys.stderr.write( msg + '\n' )

def printTime( ):
	"""Print current time in human readable format"""

	return time.strftime("%a %d %b %Y %H:%M:%S")

# Ooohh, someone started me! Let's go..
if __name__ == '__main__':
	main()
