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

		if name == 'GANGLIA_XML':
			self.XMLSource = attrs.get('SOURCE',"")
			self.gangliaVersion = attrs.get('VERSION',"")
			if DEBUG: print 'Found XML data: source %s version %s' %( self.XMLSource, self.gangliaVersion )

		elif name == 'GRID':
			self.gridName = attrs.get('NAME',"")
			if DEBUG: print '`-Grid found: %s' %( self.gridName )

		elif name == 'CLUSTER':
			self.clusterName = attrs.get('NAME',"")
			if DEBUG: print ' |-Cluster found: %s' %( self.clusterName )

		elif name == 'HOST':     
			self.hostName = attrs.get('NAME',"")
			self.hostIp = attrs.get('IP',"")
			self.hostReported = attrs.get('REPORTED',"")
			if DEBUG: print ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported )

		elif name == 'METRIC':
			myMetric = { }
			myMetric['name'] = attrs.get('NAME',"")
			myMetric['val'] = attrs.get('VAL',"")

			self.metrics.append( myMetric ) 
			if DEBUG: print ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] )

		return

	#def endElement( self, name ):
		#if name == 'ganglia_xml':

		#if name == 'grid':

		#if name == 'cluster':

		#if name == 'host':     

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
				print socket.error
				print msg
				s = None
				continue
		    	try:
				print 'connected'
				s.connect(sa)
				#s.setblocking(1)
		    	except socket.error, msg:
				s.close()
				print socket.error
				print msg
				s = None
				continue
		    	break

		if s is None:
			print 'could not open socket'
			sys.exit(1)

		return s.makefile( 'r' )
		#return s

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

	#for line in myXMLGatherer.getFileDescriptor().readlines():
	#	print line

	myParser.parse( myXMLGatherer.getFileDescriptor() )

# Let's go
main()
