#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys
import rrdtool
import string

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
			if (DEBUG_LEVEL>9): print 'Found XML data: source %s version %s' %( self.XMLSource, self.gangliaVersion )

		elif name == 'GRID':
			self.gridName = attrs.get('NAME',"")
			if (DEBUG_LEVEL>9): print '`-Grid found: %s' %( self.gridName )

		elif name == 'CLUSTER':
			self.clusterName = attrs.get('NAME',"")
			self.rrd = RRDHandler( self.clusterName )
			if (DEBUG_LEVEL>9): print ' |-Cluster found: %s' %( self.clusterName )

		elif name == 'HOST':     
			self.hostName = attrs.get('NAME',"")
			self.hostIp = attrs.get('IP',"")
			self.hostReported = attrs.get('REPORTED',"")
			# Reset the metrics list for each host
			self.metrics = [ ]
			if (DEBUG_LEVEL>9): print ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported )

		elif name == 'METRIC':
			myMetric = { }
			myMetric['name'] = attrs.get('NAME',"")
			myMetric['val'] = attrs.get('VAL',"")

			self.metrics.append( myMetric ) 
			if (DEBUG_LEVEL>10): print ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] )

		return

	def endElement( self, name ):
		#if name == 'ganglia_xml':

		#if name == 'grid':

		#if name == 'cluster':

		if name == 'host':     
			self.storeMetrics( self.hostName )

		#if name == 'metric':

	def storeMetrics( self, hostname ):

		for metric in self.metrics:
			self.rrd.checkCreate( hostname, metric['name'] )	
			self.rrd.update( hostname, metric['name'], metric['val'] )
			if (DEBUG_LEVEL>8): print 'stored metric %s for %s: %s' %( hostname, metric['name'], metric['val'] )
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
							if (DEBUG_LEVEL>8): print 'polling interval for %s = %s' %(source['name'], source['interval'] )
		
		# No interval found, use Ganglia's default	
		if not source.has_key( 'interval' ):
			source['interval'] = 15
			if (DEBUG_LEVEL>8): print 'polling interval for %s defaulted to 15' %(source['name'])

		self.sources.append( source )

	def getInterval( self, source_name ):
		for source in self.sources:
			if source['name'] == name:
				return source['interval']
		return None

class RRDHandler:

	def __init__( self, cluster ):
		self.cluster = cluster

		self.gmetad_conf = GangliaConfigParser( GMETAD_CONF )

	def createCheck( self, host, metric ):
		"Check if an .rrd allready exists for this metric, create if not"

		rrd_dir = '%s/%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster, host )

		if not os.path.exists( rrd_dir ):
			os.makedirs( rrd_dir )

		rrd_file = '%s/%s.rrd' %( rrd_dir, metric )

		interval = self.gmetad_conf.getInterval( self.cluster )

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

# Let's go
if __name__ == '__main__':
	main()
