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
# >10 = metric XML
# >9  = host,cluster,grid,ganglia XML
# >8  = RRD activity,gmetad config parsing
#
DEBUG_LEVEL = 8

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
ARCHIVE_HOURS_PER_RRD = 24

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

	metrics = [ ]

	def startElement( self, name, attrs ):
		"Store appropriate data from xml start tags"

		if name == 'GANGLIA_XML':
			self.XMLSource = attrs.get('SOURCE',"")
			self.gangliaVersion = attrs.get('VERSION',"")
			debug_msg( 10, 'Found XML data: source %s version %s' %( self.XMLSource, self.gangliaVersion ) )

		elif name == 'GRID':
			self.gridName = attrs.get('NAME',"")
			self.time = attrs.get('LOCALTIME',"")
			debug_msg( 10, '`-Grid found: %s' %( self.gridName ) )

		elif name == 'CLUSTER':
			self.clusterName = attrs.get('NAME',"")
			self.time = attrs.get('LOCALTIME',"")
			self.rrd = RRDHandler( self.clusterName )
			debug_msg( 10, ' |-Cluster found: %s' %( self.clusterName ) )

		elif name == 'HOST' and self.clusterName in ARCHIVE_SOURCES:     
			self.hostName = attrs.get('NAME',"")
			self.hostIp = attrs.get('IP',"")
			self.hostReported = attrs.get('REPORTED',"")
			# Reset the metrics list for each host
			self.metrics = [ ]
			debug_msg( 10, ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported ) )

		elif name == 'METRIC' and self.clusterName in ARCHIVE_SOURCES:
			myMetric = { }
			myMetric['name'] = attrs.get('NAME',"")
			myMetric['val'] = attrs.get('VAL',"")
			myMetric['time'] = self.time
			myMetric['type'] = attrs.get('TYPE',"")

			self.metrics.append( myMetric ) 
			debug_msg( 11, ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] ) )

		return

	def endElement( self, name ):
		#if name == 'GANGLIA_XML':

		#if name == 'GRID':

		#if name == 'CLUSTER':

		if name == 'HOST' and self.clusterName in ARCHIVE_SOURCES:     
			self.storeMetrics( self.hostName )

		#if name == 'METRIC':

	def storeMetrics( self, hostname ):

		for metric in self.metrics:
			if metric['type'] not in UNSUPPORTED_ARCHIVE_TYPES:

				self.rrd.createCheck( hostname, metric )	
				self.rrd.update( hostname, metric )
				debug_msg( 9, 'stored metric %s for %s: %s' %( hostname, metric['name'], metric['val'] ) )
				sys.exit(1)
	

class GangliaXMLGatherer:
	"Setup a connection and file object to Ganglia's XML"

	s = None

	def __init__( self, host, port ):
		"Store host and port for connection"

		self.host = host
		self.port = port

	def __del__( self ):
		"Kill the socket before we leave"

		self.s.close()

	def getFileObject( self ):
		"Connect, and return a file object"

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
			print 'Could not open socket'
			sys.exit(1)

		return self.s.makefile( 'r' )

class GangliaXMLProcessor:

	def daemon( self ):
		"Run as daemon forever"

		self.DAEMON = 1

		# Fork the first child
		#
		pid = os.fork()
		if pid > 0:
			sys.exit(0)  # end parrent

		# creates a session and sets the process group ID 
		#
		os.setsid()

		# Fork the second child
		#
		pid = os.fork()
		if pid > 0:
			sys.exit(0)  # end parrent

		# Go to the root directory and set the umask
		#
		os.chdir('/')
		os.umask(0)

		sys.stdin.close()
		sys.stdout.close()
		if (DEBUGLEVEL == 0):
			sys.stderr.close()

		os.open('/dev/null', 0)
		os.dup(0)
		os.dup(0)

		self.run()

	def run( self ):
		"Main thread"

		while ( 1 ):
			self.processXML()
			time.sleep( 5 )

	def processXML( self ):
		"Process XML"

		myXMLGatherer = GangliaXMLGatherer( 'localhost', 8651 ) 

		myParser = make_parser()   
		myHandler = GangliaXMLHandler()
		myParser.setContentHandler( myHandler )

		myParser.parse( myXMLGatherer.getFileObject() )

class GangliaConfigParser:

	sources = [ ]

	def __init__( self, config ):
		self.config = config
		self.parseValues()

	def parseValues(self):
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

	def __init__( self, cluster ):
		self.cluster = cluster

		self.gmetad_conf = GangliaConfigParser( GMETAD_CONF )

	def makeTimeSerial( self ):

		# YYYYMMDDhhmmss: 20050321143411
		#mytime = time.strftime( "%Y%m%d%H%M%S" )

		# Seconds since epoch
		mytime = int( time.time() )

		return mytime

	def makeRrdPath( self, host, metric, timeserial='notime' ):

		rrd_dir = '%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host )
		rrd_file = '%s/%s$%s.rrd' %( rrd_dir, metric['name'], timeserial )

		return rrd_dir, rrd_file

	def getLastRrdTimeSerial( self, host, metric ):

		rrd_dir, rrd_file = self.makeRrdPath( host, metric )

		if os.path.exists( rrd_dir ):
			for root, dirs, files in os.walk( rrd_dir ):

				newest_timeserial = 0

				for file in files:

					myre = re.match( '(\S+)\$(\d+).rrd', file )

					if not myre:
						continue

					mymetric = myre.group(1)

					if mymetric == metric['name']:

						timeserial = myre.group(2)
						if timeserial > newest_timeserial:
							newest_timeserial = timeserial

		if newest_timeserial:
			return timeserial
		else:
			return 0

	def checkNewRrdPeriod( self, host, metric ):

		cur_timeserial = int( self.makeTimeSerial() )
		last_timeserial = int( self.getLastRrdTimeSerial( host, metric ) )

		if not last_timeserial:
			return 0, 0

		archive_secs = ARCHIVE_HOURS_PER_RRD * (60 * 60)

		if (cur_timeserial - last_timeserial) >= archive_secs:
			return 1, last_timeserial
		else:
			return 0, last_timeserial

	def createCheck( self, host, metric ):
		"Check if an .rrd allready exists for this metric, create if not"

		need_new, last_timeserial = self.checkNewRrdPeriod( host, metric )

		if need_new or not last_timeserial:
			timeserial = self.makeTimeSerial()
		else:
			timeserial = last_timeserial

		rrd_dir, rrd_file = self.makeRrdPath( host, metric, timeserial )

		if not os.path.exists( rrd_dir ):
			os.makedirs( rrd_dir )
			debug_msg( 9, 'created dir %s' %( str(rrd_dir) ) )

		if not os.path.exists( rrd_file ):

			interval = self.gmetad_conf.getInterval( self.cluster )
			heartbeat = 8 * int(interval)

			param_step1 = '--step'
			param_step2 = str( interval )

			param_start1 = '--start'
			param_start2 = str( int( metric['time'] ) - 1 )

			param_ds = 'DS:sum:GAUGE:%d:U:U' %heartbeat
			param_rra = 'RRA:AVERAGE:0.5:1:%s' %(ARCHIVE_HOURS_PER_RRD * 240)

			rrdtool.create( str(rrd_file), param_step1, param_step2, param_start1, param_start2, param_ds, param_rra )

			debug_msg( 9, 'created rrd %s' %( str(rrd_file) ) )

	def update( self, host, metric ):

		rrd_dir = '%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host )
		rrd_file = '%s/%s.rrd' %( rrd_dir, metric['name'] )

		timestamp = metric['time']
		val = metric['val']

		update_string = '%s:%s' %(timestamp, val)

		rrdtool.update( str(rrd_file), str(update_string) )
		debug_msg( 9, 'updated rrd %s with %s' %( str(rrd_file), update_string ) )

def main():
	"Program startup"

	myProcessor = GangliaXMLProcessor()
	myProcessor.processXML()

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
