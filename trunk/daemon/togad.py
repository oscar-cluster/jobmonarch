#!/usr/bin/env python

from xml.sax import make_parser
from xml.sax.handler import ContentHandler 
import socket
import sys

class GangliaXMLHandler(ContentHandler):
	"""
	Parse/Handle XML
	"""

	def __init__ (self, searchTerm):
		self.isHostElement, self.isxReboundsElement = 0, 0;
   
	def startElement(self, name, attrs):

		if name == 'player':     
			self.playerName = attrs.get('name',"")
			self.playerAge = attrs.get('age',"")
			self.playerHeight = attrs.get('height',"")
		elif name == 'points':
			self.isPointsElement= 1;
			self.playerPoints = "";
		elif name == 'rebounds':
			self.isReboundsElement = 1;
			self.playerRebounds = "";
		return

	def characters (self, ch):
		if self.isPointsElement== 1:
			self.playerPoints += ch
		if self.isReboundsElement == 1:
			self.playerRebounds += ch

	def endElement(self, name):
		if name == 'points':
			self.isPointsElement= 0
		if name == 'rebounds':
			self.inPlayersContent = 0
		if name == 'player' and self.searchTerm== self.playerName :
			print '<h2>Statistics for player:' , self.playerName, '</h2><br>(age:', self.playerAge , 'height' , self.playerHeight , ")<br>"
			print 'Match average:', self.playerPoints , 'points,' , self.playerRebounds, 'rebounds'

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

		return s.fileno()

		#s.send('Hello, world')
		#data = s.recv(1024)
		#s.close()
		#print 'Received', `data`

def main():
	"""
	My Main
	"""

	myXMLGatherer = GangliaXMLGatherer( localhost, 8651 ) 

	myParser = make_parser()   
	myHandler = GangliaXMLHandler()
	myParser.setContentHandler( myHandler )
	myParser.parse( myXMLGatherer.getFileDescriptor() )

# Let's go
main()
