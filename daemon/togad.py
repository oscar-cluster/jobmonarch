#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys

DEBUG = 1

class GangliaXMLHandler( ContentHandler ):
	"""
	Parse/Handle XML
	"""

	metrics = [ ]

	#def __init__ ( self ):
		#self.isHostElement, self.isMetricElement, self.isGridElement, self.isClusterElement = 0, 0, 0, 0
		#self.isGangliaXMLElement = 0
   
	def startElement( self, name, attrs ):

		if name == 'ganglia_xml':
			self.XMLSource = attrs.get('source',"")
			self.gangliaVersion = attrs.get('version',"")

		elif name == 'grid':
			self.gridName = attrs.get('name',"")

		elif name == 'cluster':
			self.clusterName = attrs.get('name',"")

		elif name == 'host':     
			self.hostName = attrs.get('name',"")
			self.hostIp = attrs.get('ip',"")
			self.hostReported = attrs.get('reported',"")

		elif name == 'metric':
			myMetric = { }
			myMetric['name'] = attrs.get('name',"")
			myMetric['val'] = attrs.get('val',"")

			self.metrics.append( myMetric ) 
			if DEBUG: print ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] )

		return

	def endElement( self, name ):
		if name == 'ganglia_xml':
			if DEBUG: print 'Found XML data: source %s version %s' %( self.XMLSource, self.gangliaVersion )

		if name == 'grid':
			if DEBUG: print '`-Grid found: %s' %( self.gridName )

		if name == 'cluster':
			if DEBUG: print ' |-Cluster found: %s' %( self.clusterName )

		if name == 'host':     
			if DEBUG: print ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported )

		#if name == 'metric':

class GangliaXMLGatherer:
	"""
	Connect to a gmetad and return fd
	"""

	def __init__(self, host, port):
		self.host = host
		self.port = port

	def getFileDescriptor(self):
		s = None
		for res in socket.getaddrinfo(self.host, self.port, socket.AF_UNSPEC, socket.SOCK_STREAM):
			af, socktype, proto, canonname, sa = res
			try:
				s = socket.socket(af, socktype, proto)
			except socket.error, msg:
				s = None
				continue
		    	try:
				s.connect(sa)
		    	except socket.error, msg:
				s.close()
				s = None
				continue
		    	break

		if s is None:
			print 'could not open socket'
			sys.exit(1)

		return s.makefile()

		#s.send('Hello, world')
		#data = s.recv(1024)
		#s.close()
		#print 'Received', `data`

def main():
	"""
	My Main
	"""

	myXMLGatherer = GangliaXMLGatherer( 'localhost', 8651 ) 

	myParser = make_parser()   
	myHandler = GangliaXMLHandler()
	myParser.setContentHandler( myHandler )
	myParser.parse( myXMLGatherer.getFileDescriptor() )

# Let's go
main()
