#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys
import rrdtool
import string
import os
import os.path

# Specify debugging level here;
#
# >10 = metric XML
# >9  = host,cluster,grid,ganglia XML
# >8  = RRD activity,gmetad config parsing
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
ARCHIVE_HOURS_PER_RRD = 24


#
# Configuration ends
#

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

		elif name == 'HOST':     
			self.hostName = attrs.get('NAME',"")
			self.hostIp = attrs.get('IP',"")
			self.hostReported = attrs.get('REPORTED',"")
			# Reset the metrics list for each host
			self.metrics = [ ]
			debug_msg( 10, ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported ) )

		elif name == 'METRIC':
			myMetric = { }
			myMetric['name'] = attrs.get('NAME',"")
			myMetric['val'] = attrs.get('VAL',"")
			myMetric['time'] = self.time

			self.metrics.append( myMetric ) 
			debug_msg( 11, ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] ) )

		return

	def endElement( self, name ):
		#if name == 'GANGLIA_XML':

		#if name == 'GRID':

		#if name == 'CLUSTER':

		if name == 'HOST':     
			self.storeMetrics( self.hostName )

		#if name == 'METRIC':

	def storeMetrics( self, hostname ):

		for metric in self.metrics:
			self.rrd.createCheck( hostname, metric )	
			self.rrd.update( hostname, metric['name'], metric['val'] )
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

	def createCheck( self, host, metric ):
		"Check if an .rrd allready exists for this metric, create if not"

		rrd_parameters = [ ]
		rrd_dir = '%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host )

		if not os.path.exists( rrd_dir ):
			os.makedirs( rrd_dir )

		rrd_file = '%s/%s.rrd' %( rrd_dir, metric['name'] )

		interval = self.gmetad_conf.getInterval( self.cluster )
		heartbeat = 8 * int(interval)

		param_step1 = '--step'
		param_step2 = str( interval )

		param_start1 = '--start'
		param_start2 = str( int( metric['time'] ) - 1 )

		param_ds = 'DS:sum:GAUGE:%d:U:U' %heartbeat
		param_rra = 'RRA:AVERAGE:0.5:1:%s' %(ARCHIVE_HOURS_PER_RRD * 240)

		rrdtool.create( str(rrd_file), param_step1, param_step2, param_start1, param_start2, param_ds, param_rra )

	def update( self, metric, timestamp, val ):

		pass

		#rrd.update( bla )
		

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
