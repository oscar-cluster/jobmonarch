#!/usr/bin/env python

# This is the MIT License
# http://www.opensource.org/licenses/mit-license.php
#
# Copyright (c) 2007 Nick Galbreath
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.
#

#
# Version 1.0 - 21-April2-2007
#

#
# Modified by: Ramon Bastiaans
# For the Job Monarch Project, see: https://subtrac.sara.nl/oss/jobmonarch/
# 
# added: DEFAULT_TYPE for Gmetric's
# added: checkHostProtocol to determine if target is multicast or not
# changed: allow default for Gmetric constructor
# changed: allow defaults for all send() values except dmax
#

import xdrlib, socket

GMETRIC_DEFAULT_TYPE	= 'string'
GMETRIC_DEFAULT_HOST	= '127.0.0.1'
GMETRIC_DEFAULT_PORT	= '8649'

class Gmetric:

	global GMETRIC_DEFAULT_HOST, GMETRIC_DEFAULT_PORT

	slope		= { 'zero' : 0, 'positive' : 1, 'negative' : 2, 'both' : 3, 'unspecified' : 4 }
	type		= ( '', 'string', 'uint16', 'int16', 'uint32', 'int32', 'float', 'double', 'timestamp' )
	protocol	= ( 'udp', 'multicast' )

	def __init__( self, host=GMETRIC_DEFAULT_HOST, port=GMETRIC_DEFAULT_PORT ):

		global GMETRIC_DEFAULT_TYPE

		self.prot	= self.checkHostProtocol( host )
		self.msg	= xdrlib.Packer()
		self.socket	= socket.socket( socket.AF_INET, socket.SOCK_DGRAM )

		if self.prot not in self.protocol:

			raise ValueError( "Protocol must be one of: " + str( self.protocol ) )

		if self.prot == 'multicast':

			self.socket.setsockopt( socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 20 )

		self.hostport	= ( host, int( port ) )
		self.type	= GMETRIC_DEFAULT_TYPE
		self.unitstr	= ''
		self.slopestr	= 'both'
		self.tmax	= 60

	def checkHostProtocol( self, ip ):

		MULTICAST_ADDRESS_MIN	= ( "224", "0", "0", "0" )
		MULTICAST_ADDRESS_MAX	= ( "239", "255", "255", "255" )

		ip_fields		= ip.split( '.' )

		if ip_fields >= MULTICAST_ADDRESS_MIN and ip_fields <= MULTICAST_ADDRESS_MAX:

			return 'multicast'
		else:
			return 'udp'

	def send( self, name, value, dmax ):

		msg		= self.makexdr( name, value, self.type, self.unitstr, self.slopestr, self.tmax, dmax )

		return self.socket.sendto( msg, self.hostport )
                     
	def makexdr( self, name, value, typestr, unitstr, slopestr, tmax, dmax ):

		if slopestr not in self.slope:

			raise ValueError( "Slope must be one of: " + str( self.slope.keys() ) )

		if typestr not in self.type:

			raise ValueError( "Type must be one of: " + str( self.type ) )

		if len( name ) == 0:

			raise ValueError( "Name must be non-empty" )

		self.msg.reset()
		self.msg.pack_int( 0 )
		self.msg.pack_string( typestr )
		self.msg.pack_string( name )
		self.msg.pack_string( str( value ) )
		self.msg.pack_string( unitstr )
		self.msg.pack_int( self.slope[ slopestr ] )
		self.msg.pack_uint( int( tmax ) )
		self.msg.pack_uint( int( dmax ) )

		return self.msg.get_buffer()
