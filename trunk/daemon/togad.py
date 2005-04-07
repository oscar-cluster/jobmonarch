#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys
import string
import os
import os.path
import time
import re
import threading
import mutex
import random
from types import *

# Specify debugging level here;
#
# 11 = XML: metrics
# 10 = XML: host, cluster, grid, ganglia
# 9  = RRD activity, gmetad config parsing
# 8  = RRD file activity
# 7  = daemon threading
#
DEBUG_LEVEL = 9

# Where is the gmetad.conf located
#
GMETAD_CONF = '/etc/gmetad.conf'

# Where to store the archived rrd's
#
ARCHIVE_PATH = '/data/toga/rrds'

# List of data_source names to archive for
#
ARCHIVE_SOURCES = [ "LISA Cluster" ]

# Amount of hours to store in one single archived .rrd
#
ARCHIVE_HOURS_PER_RRD = 12

# Wether or not to run as a daemon in background
#
DAEMONIZE = 0

######################
#                    #
# Configuration ends #
#                    #
######################

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

class RRDMutator:
	"A class for handling .rrd mutations"

	binary = '/usr/bin/rrdtool'

	def __init__( self, binary=None ):

		if binary:
			self.binary = binary

	def create( self, filename, args ):
		return self.perform( 'create', '"' + filename + '"', args )

	def update( self, filename, args ):
		return self.perform( 'update', '"' + filename + '"', args )

	def grabLastUpdate( self, filename ):

		last_update = 0

		for line in os.popen( self.binary + ' info "' + filename + '"' ).readlines():

			if line.find( 'last_update') != -1:

				last_update = line.split( ' = ' )[1]

		if last_update:
			return last_update
		else:
			return 0

	def perform( self, action, filename, args ):

		arg_string = None

		if type( args ) is not ListType:
			debug_msg( 8, 'Arguments needs to be of type List' )
			return 1

		for arg in args:

			if not arg_string:

				arg_string = arg
			else:
				arg_string = arg_string + ' ' + arg

		debug_msg( 8, 'rrdm.perform(): ' + self.binary + ' ' + action + ' ' + filename + ' ' + arg_string  )

		for line in os.popen( self.binary + ' ' + action + ' ' + filename + ' ' + arg_string ).readlines():

			if line.find( 'ERROR' ) != -1:

				error_msg = string.join( line.split( ' ' )[1:] )
				debug_msg( 8, error_msg )
				return 1

		return 0

class GangliaXMLHandler( ContentHandler ):
	"Parse Ganglia's XML"

	def __init__( self, config ):
		self.config = config
		self.clusters = { }
		debug_msg( 0, printTime() + ' Checking existing toga rrd archive..' )
		self.gatherClusters()
		debug_msg( 0, printTime() + ' Check done.' )

	def gatherClusters( self ):

		archive_dir = check_dir(ARCHIVE_PATH)

		hosts = [ ]

		if os.path.exists( archive_dir ):

			dirlist = os.listdir( archive_dir )

			for item in dirlist:

				clustername = item

				if not self.clusters.has_key( clustername ) and clustername in ARCHIVE_SOURCES:

					self.clusters[ clustername ] = RRDHandler( self.config, clustername )

	def startElement( self, name, attrs ):
		"Store appropriate data from xml start tags"

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

			if not self.clusters.has_key( self.clusterName ) and self.clusterName in ARCHIVE_SOURCES:

				self.clusters[ self.clusterName ] = RRDHandler( self.config, self.clusterName )

				debug_msg( 10, ' |-Cluster found: %s' %( self.clusterName ) )

		elif name == 'HOST' and self.clusterName in ARCHIVE_SOURCES:     

			self.hostName = attrs.get( 'NAME', "" )
			self.hostIp = attrs.get( 'IP', "" )
			self.hostReported = attrs.get( 'REPORTED', "" )

			debug_msg( 10, ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported ) )

		elif name == 'METRIC' and self.clusterName in ARCHIVE_SOURCES:

			type = attrs.get( 'TYPE', "" )

			if type not in UNSUPPORTED_ARCHIVE_TYPES:

				myMetric = { }
				myMetric['name'] = attrs.get( 'NAME', "" )
				myMetric['val'] = attrs.get( 'VAL', "" )
				myMetric['time'] = self.hostReported

				self.clusters[ self.clusterName ].memMetric( self.hostName, myMetric )

				debug_msg( 11, ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] ) )

	def storeMetrics( self ):

		for clustername, rrdh in self.clusters.items():

			ret = rrdh.storeMetrics()

			if ret:
				debug_msg( 9, 'An error occured while storing metrics for cluster %s' %clustername )
				return 1

		return 0

class GangliaXMLGatherer:
	"Setup a connection and file object to Ganglia's XML"

	s = None

	def __init__( self, host, port ):
		"Store host and port for connection"

		self.host = host
		self.port = port
		self.connect()

	def connect( self ):
		"Setup connection to XML source"

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

				self.s.close()
				self.s = None
				continue

		    	break

		if self.s is None:

			debug_msg( 0, 'Could not open socket' )
			sys.exit( 1 )

	def disconnect( self ):
		"Close socket"

		if self.s:
			self.s.close()
			self.s = None

	def __del__( self ):
		"Kill the socket before we leave"

		self.disconnect()

	def getFileObject( self ):
		"Connect, and return a file object"

		if self.s:
			# Apearantly, only data is received when a connection is made
			# therefor, disconnect and connect
			#
			self.disconnect()
			self.connect()

		return self.s.makefile( 'r' )

class GangliaXMLProcessor:

	def __init__( self ):
		"Setup initial XML connection and handlers"

		self.config = GangliaConfigParser( GMETAD_CONF )

		self.myXMLGatherer = GangliaXMLGatherer( 'localhost', 8651 ) 
		self.myParser = make_parser()   
		self.myHandler = GangliaXMLHandler( self.config )
		self.myParser.setContentHandler( self.myHandler )

	def daemon( self ):
		"Run as daemon forever"

		self.DAEMON = 1

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
		#sys.stderr.close()

		os.open('/dev/null', 0)
		os.dup(0)
		os.dup(0)

		self.run()

	def printTime( self ):
		"Print current time in human readable format"

		return time.strftime("%a %d %b %Y %H:%M:%S")

	def run( self ):
		"Main thread"

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
		"Store metrics retained in memory to disk"

		debug_msg( 7, self.printTime() + ' - storethread(): started.' )

		# Store metrics somewhere between every 60 and 180 seconds
		#
		#STORE_INTERVAL = random.randint( 360, 640 )
		STORE_INTERVAL = 16

		storethread = threading.Thread( None, self.storeThread, 'storemetricthread' )
		storethread.start()

		debug_msg( 7, self.printTime() + ' - storethread(): Sleeping.. (%ss)' %STORE_INTERVAL )
		time.sleep( STORE_INTERVAL )
		debug_msg( 7, self.printTime() + ' - storethread(): Done sleeping.' )

		if storethread.isAlive():

			debug_msg( 7, self.printTime() + ' - storethread(): storemetricthread() still running, waiting to finish..' )
			storethread.join( STORE_TIMEOUT ) # Maximum time is for storing thread to finish
			debug_msg( 7, self.printTime() + ' - storethread(): Done waiting.' )

		debug_msg( 7, self.printTime() + ' - storethread(): finished.' )

		return 0

	def storeThread( self ):

		debug_msg( 7, self.printTime() + ' - storemetricthread(): started.' )
		debug_msg( 7, self.printTime() + ' - storemetricthread(): Storing data..' )
		ret = self.myHandler.storeMetrics()
		debug_msg( 7, self.printTime() + ' - storemetricthread(): Done storing.' )
		debug_msg( 7, self.printTime() + ' - storemetricthread(): finished.' )
		
		return ret

	def processXML( self ):
		"Process XML"

		debug_msg( 7, self.printTime() + ' - xmlthread(): started.' )

		parsethread = threading.Thread( None, self.parseThread, 'parsethread' )
		parsethread.start()

		debug_msg( 7, self.printTime() + ' - xmlthread(): Sleeping.. (%ss)' %self.config.getLowestInterval() )
		time.sleep( float( self.config.getLowestInterval() ) )	
		debug_msg( 7, self.printTime() + ' - xmlthread(): Done sleeping.' )

		if parsethread.isAlive():

			debug_msg( 7, self.printTime() + ' - xmlthread(): parsethread() still running, waiting to finish..' )
			parsethread.join( PARSE_TIMEOUT ) # Maximum time for XML thread to finish
			debug_msg( 7, self.printTime() + ' - xmlthread(): Done waiting.' )

		debug_msg( 7, self.printTime() + ' - xmlthread(): finished.' )

		return 0

	def parseThread( self ):

		debug_msg( 7, self.printTime() + ' - parsethread(): started.' )
		debug_msg( 7, self.printTime() + ' - parsethread(): Parsing XML..' )
		ret = self.myParser.parse( self.myXMLGatherer.getFileObject() )
		debug_msg( 7, self.printTime() + ' - parsethread(): Done parsing.' )
		debug_msg( 7, self.printTime() + ' - parsethread(): finished.' )

		return ret

class GangliaConfigParser:

	sources = [ ]

	def __init__( self, config ):

		self.config = config
		self.parseValues()

	def parseValues( self ):
		"Parse certain values from gmetad.conf"

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

		for source in self.sources:

			if source['name'] == source_name:

				return source['interval']

		return None

	def getLowestInterval( self ):

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

	myMetrics = { }
	lastStored = { }
	timeserials = { }
	slot = None

	def __init__( self, config, cluster ):
		self.block = 0
		self.cluster = cluster
		self.config = config
		self.slot = threading.Lock()
		self.rrdm = RRDMutator()
		self.gatherLastUpdates()

	def isBlocking( self ):

		return self.block

	def gatherLastUpdates( self ):
		"Populate the lastStored list, containing timestamps of all last updates"

		self.block = 1

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
		return self.cluster

	def memMetric( self, host, metric ):

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

		# Ah, psst, push it
		#
		# <atomic>
		self.slot.acquire()

		self.myMetrics[ host ][ metric['name'] ].append( metric )

		self.slot.release()
		# </atomic>

	def makeUpdateList( self, host, metriclist ):

		update_list = [ ]
		metric = None

		while len( metriclist ) > 0:

			# Kabouter pop
			#
			# <atomic>	
			#self.slot.acquire()

			# len might have changed since loop start
			#
			if len( metriclist ) > 0:
				metric = metriclist.pop( 0 )

			#self.slot.release()
			# </atomic>

			if metric:
				if self.checkStoreMetric( host, metric ):
					update_list.append( '%s:%s' %( metric['time'], metric['val'] ) )

		return update_list

	def checkStoreMetric( self, host, metricname, metric ):

		if self.lastStored.has_key( host ):

			if self.lastStored[ host ].has_key( metric['name'] ):

				if metric['time'] <= self.lastStored[ host ][ metric['name'] ]:

					# Allready wrote a value with this timestamp, skip tnx
					return 0

		else:
			self.lastStored[ host ] = { }

		self.lastStored[ host ][ metric['name'] ] = metric['time']

		return 1

	def storeMetrics( self ):

		for hostname, mymetrics in self.myMetrics.items():	

			for metricname, mymetric in mymetrics.items():

				#mytime = self.makeTimeSerial()
				#serial = mymetric['time']
				#correct_serial = self.checkNewRrdPeriod( hostname, mytime )

				self.slot.acquire() 

				# Create a mapping table, each metric to the period where it should be stored
				#
				metric_serial_table = self.determineSerials( hostname, metricname, mymetric )
				self.myMetrics[ hostname ][ metricname ] = [ ]

				self.slot.release()

				for period, pmetric in metric_serial_table.items():

					self.createCheck( hostname, metricname, period )	

					update_ret = self.update( hostname, metricname, period, pmetric )

					if update_ret == 0:

						debug_msg( 9, 'stored metric %s for %s' %( hostname, metricname ) )
					else:
						debug_msg( 9, 'metric update failed' )

				sys.exit(1)

	def makeTimeSerial( self ):
		"Generate a time serial. Seconds since epoch"

		# Seconds since epoch
		mytime = int( time.time() )

		return mytime

	def makeRrdPath( self, host, metricname=None, timeserial=None ):
		"""
		Make a RRD location/path and filename
		If a metric or timeserial are supplied the complete locations
		will be made, else just the host directory
		"""

		if not timeserial:	
			rrd_dir = '%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host )
		else:
			rrd_dir = '%s/%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host, timeserial )
		if metricname:
			rrd_file = '%s/%s.rrd' %( rrd_dir, metricname )
		else:
			rrd_file = None

		return rrd_dir, rrd_file

	def getLastRrdTimeSerial( self, host ):
		"""
		Find the last timeserial (directory) for this host
		This is determined once every host
		"""

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

		period_serial = 0

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

				if (int( metric['time'] ) - period) > archive_secs:

					# This one should get it's own new period
					period = metric['time']

				if not metric_serial_table.has_key( period ):

					metric_serial_table = [ ]

				metric_serial_table[ period ].append( metric )

		print metric_serial_table

		return metric_serial_table

	def checkNewRrdPeriod( self, host, current_timeserial ):
		"""
		Check if current timeserial belongs to recent time period
		or should become a new period (and file).

		Returns the serial of the correct time period
		"""

		last_timeserial = int( self.getLastRrdTimeSerial( host ) )
		debug_msg( 9, 'last timeserial of %s is %s' %( host, last_timeserial ) )

		if not last_timeserial:
			serial = current_timeserial
		else:

			archive_secs = ARCHIVE_HOURS_PER_RRD * (60 * 60)

			if (current_timeserial - last_timeserial) > archive_secs:
				serial = current_timeserial
			else:
				serial = last_timeserial

		return serial

	def getFirstTime( self, host, metricname ):
		"Get the first time of a metric we know of"

		first_time = 0

		for metric in self.myMetrics[ host ][ metricname ]:

			if not first_time or metric['time'] <= first_time:

				first_time = metric['time']

		return first_time

	def createCheck( self, host, metricname, timeserial ):
		"Check if an .rrd allready exists for this metric, create if not"

		debug_msg( 9, 'rrdcreate: using timeserial %s for %s/%s' %( timeserial, host, metricname ) )
		
		rrd_dir, rrd_file = self.makeRrdPath( host, metricname, timeserial )

		if not os.path.exists( rrd_dir ):
			os.makedirs( rrd_dir )
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
	"Program startup"

	myProcessor = GangliaXMLProcessor()

	if DAEMONIZE:
		myProcessor.daemon()
	else:
		myProcessor.run()

def check_dir( directory ):
	"Check if directory is a proper directory. I.e.: Does _not_ end with a '/'"

	if directory[-1] == '/':
		directory = directory[:-1]

	return directory

def debug_msg( level, msg ):

	if (DEBUG_LEVEL >= level):
		sys.stderr.write( msg + '\n' )

def printTime( ):
	"Print current time in human readable format"

	return time.strftime("%a %d %b %Y %H:%M:%S")

# Let's go
if __name__ == '__main__':
	main()
