#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys

# Specify debugging level here;
#
# >10 = metric XML
# >9  = host,cluster,grid,ganglia XML
#
DEBUG_LEVEL = 9

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
			if (DEBUG_LEVEL>9): print ' |-Cluster found: %s' %( self.clusterName )

		elif name == 'HOST':     
			self.hostName = attrs.get('NAME',"")
			self.hostIp = attrs.get('IP',"")
			self.hostReported = attrs.get('REPORTED',"")
			if (DEBUG_LEVEL>9): print ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported )

		elif name == 'METRIC':
			myMetric = { }
			myMetric['name'] = attrs.get('NAME',"")
			myMetric['val'] = attrs.get('VAL',"")

			self.metrics.append( myMetric ) 
			if (DEBUG_LEVEL>10): print ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] )

		return

	#def endElement( self, name ):
		#if name == 'ganglia_xml':

		#if name == 'grid':

		#if name == 'cluster':

		#if name == 'host':     

		#if name == 'metric':

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

	def daemon(self):
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

	def run(self):
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

def main():
	"Program startup"

	myProcessor = GangliaXMLProcessor()
	myProcessor.processXML()

# Let's go
main()
