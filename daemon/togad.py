#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys
import rrdtool
import string
import os
import os.path
import time
import re

# Specify debugging level here;
#
# <=11 = metric XML
# <=10 = host,cluster,grid,ganglia XML
# <=9  = RRD activity,gmetad config parsing
# <=8  = host processing
# <=7  = daemon threading - NOTE: Daemon will 'halt on all errors' from this level
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

"""
This is TOrque-GAnglia's data Daemon
"""

class GangliaXMLHandler( ContentHandler ):
	"Parse Ganglia's XML"

	clusters = { }
	config = None

	def __init__( self, config ):
		self.config = config

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

			if not self.clusters.has_key( self.clusterName ):

				self.clusters[ self.clusterName ] = RRDHandler( self.clusterName )

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

				self.clusters[ self.clusterName ].memMetric( self.hostname, myMetric )

			debug_msg( 11, ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] ) )

	def storeMetrics( self, hostname, timeserial ):

		for cluster in self.clusters:

			cluster.storeMetrics()

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

		if not self.s:
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
		sys.stderr.close()

		os.open('/dev/null', 0)
		os.dup(0)
		os.dup(0)

		self.run()

	def printTime( self ):
		"Print current time in human readable format"

		return time.strftime("%a %d %b %Y %H:%M:%S")

	def grabXML( self ):

		debug_msg( 7, self.printTime() + ' - mainthread() - xmlthread() started' )
		pid = os.fork()

		if pid == 0:
			# Child - XML Thread
			#
			# Process XML and exit

			debug_msg( 7, self.printTime() + ' - xmlthread()  - Start XML processing..' )
			self.processXML()
			debug_msg( 7, self.printTime() + ' - xmlthread()  - Done processing; exiting.' )
			sys.exit( 0 )

		elif pid > 0:
			# Parent - Time/sleep Thread
			#
			# Make sure XML is processed on time and at regular intervals

			debug_msg( 7, self.printTime() + ' - mainthread() - Sleep '+ str( self.config.getInterval() ) +'s: zzzzz..' )
			time.sleep( self.config.getInterval() )
			debug_msg( 7, self.printTime() + ' - mainthread() - Awoken: waiting for XML thread..' )

			r = os.wait()
			ret = r[1]
			if ret != 0:
				debug_msg( 7, self.printTime() + ' - mainthread() - Done waiting: ERROR! xmlthread() exited with status %d' %(ret) )
				if DEBUG_LEVEL>=7: sys.exit( 1 )
			else:

				debug_msg( 7, self.printTime() + ' - mainthread() - Done waiting: xmlthread() finished succesfully' )

	def run( self ):
		"Main thread"

		# Daemonized not working yet
		if DAEMONIZE:
			pid = os.fork()

			# Handle XML grabbing in Child
			if pid == 0:

				while( 1 ):
					self.grabXML()

			# Do scheduled RRD storing in Parent
			#elif pid > ):

		else:
			self.grabXML()
			self.storeMetrics()

	def storeMetrics( self ):
		"Store metrics retained in memory to disk"

		self.myHandler.storeMetrics()

	def processXML( self ):
		"Process XML"

		self.myParser.parse( self.myXMLGatherer.getFileObject() )

class GangliaConfigParser:

	sources = { }

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

class RRDHandler:

	myMetrics = { }

	def __init__( self, config, cluster ):
		self.cluster = cluster
		self.config = config

	def getClusterName( self ):
		return self.cluster

	def memMetric( self, host, metric ):

		for m in self.myMetrics[ host ]:

			if m['time'] == metric['time']:

				# Allready have this metric, abort
				return 1

		if not self.myMetrics.has_key( host ):

			self.myMetrics[ host ] = { }

		if not self.myMetrics[ host ].has_key( metric['name'] ):

			self.myMetrics[ host ][ metric['name'] ] = [ ]

		self.myMetrics[ host ][ metric['name'] ].append( metric )

	def makeUpdateString( self, host, metric ):

		update_string = ''

		for m in self.myMetrics[ host ][ metric['name'] ]:

			update_string = update_string + ' %s:%s' %( metric['time'], metric['val'] )

		return update_string

	def storeMetrics( self ):

		for hostname, mymetrics in self.myMetrics.items():	

			for metricname, mymetric in mymetrics.items():

				self.rrd.createCheck( hostname, metricname, timeserial )	
				update_okay = self.rrd.update( hostname, metricname, timeserial )

				if not update_okay:

					del self.myMetrics[ hostname ][ metricname ]
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
		if metric:
			rrd_file = '%s/%s.rrd' %( rrd_dir, metricname )
		else:
			rrd_file = None

		return rrd_dir, rrd_file

	def getLastRrdTimeSerial( self, host ):
		"""
		Find the last timeserial (directory) for this host
		This is determined once every host
		"""

		rrd_dir, rrd_file = self.makeRrdPath( host )

		newest_timeserial = 0

		if os.path.exists( rrd_dir ):

			for root, dirs, files in os.walk( rrd_dir ):

				for dir in dirs:

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

			if (current_timeserial - last_timeserial) >= archive_secs:
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

	def createCheck( self, host, metricname, timeserial ):
		"Check if an .rrd allready exists for this metric, create if not"

		debug_msg( 9, 'rrdcreate: using timeserial %s for %s/%s' %( timeserial, host, metric['name'] ) )

		rrd_dir, rrd_file = self.makeRrdPath( host, metricname, timeserial )

		if not os.path.exists( rrd_dir ):
			os.makedirs( rrd_dir )
			debug_msg( 9, 'created dir %s' %( str(rrd_dir) ) )

		if not os.path.exists( rrd_file ):

			interval = self.config.getInterval( self.cluster )
			heartbeat = 8 * int(interval)

			param_step1 = '--step'
			param_step2 = str( interval )

			param_start1 = '--start'
			param_start2 = str( int( self.getFirstTime( host, metricname ) ) - 1 )

			param_ds = 'DS:sum:GAUGE:%d:U:U' %heartbeat
			param_rra = 'RRA:AVERAGE:0.5:1:%s' %(ARCHIVE_HOURS_PER_RRD * 240)

			rrdtool.create( str(rrd_file), param_step1, param_step2, param_start1, param_start2, param_ds, param_rra )

			debug_msg( 9, 'created rrd %s' %( str(rrd_file) ) )

	def update( self, host, metricname, timeserial ):

		debug_msg( 9, 'rrdupdate: using timeserial %s for %s/%s' %( timeserial, host, metric['name'] ) )

		rrd_dir, rrd_file = self.makeRrdPath( host, metricname, timeserial )

		#timestamp = metric['time']
		#val = metric['val']

		#update_string = '%s:%s' %(timestamp, val)
		update_string = self.makeUpdateString( host, metricname )

		try:

			rrdtool.update( str(rrd_file), str(update_string) )

		except rrdtool.error, detail:

			debug_msg( 0, 'EXCEPTION! While trying to update rrd:' )
			debug_msg( 0, '\trrd %s with %s' %( str(rrd_file), update_string ) )
			debug_msg( 0, str(detail) )

			return 1
		
		debug_msg( 9, 'updated rrd %s with %s' %( str(rrd_file), update_string ) )

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

# Let's go
if __name__ == '__main__':
	main()
