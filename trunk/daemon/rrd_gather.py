#!/usr/bin/env python

import os
import time

# Location of rrdtool
RRDTOOL_BIN = '/usr/bin/rrdtool'

# Location of Ganglia's rrd's
GANGLIA_RRD_DIR = '/var/lib/ganglia/rrds'

# Location where you want our own rrd's stored
#MYDATA_DIR = '/data/myrrds'
MYDATA_DIR = '/tmp/ramdisk/myrrds'

for root, dirs, files in os.walk( GANGLIA_RRD_DIR, topdown=False ):

	for name in files:
		if name.find('.rrd') != -1 and name.find('__SummaryInfo__') == -1:

			ganglia_rrd = os.path.join( root, name )
			rel_dir = ganglia_rrd[len(GANGLIA_RRD_DIR):]
			mydata_rrd = MYDATA_DIR + rel_dir

			if not os.path.isdir( MYDATA_DIR + os.path.dirname(rel_dir) ):
				#print 'mkdir ' + MYDATA_DIR + os.path.dirname(rel_dir)
				os.makedirs( MYDATA_DIR + os.path.dirname(rel_dir) )

			if os.path.exists( '/tmp/ramdisk/myblatemp.xml' ):
				os.remove( '/tmp/ramdisk/myblatemp.xml' )

			xml = open( '/tmp/ramdisk/myblatemp.xml', 'w' )
	
			#print RRDTOOL_BIN + ' dump "' + ganglia_rrd + '" | head -271'	
			rrd_dump = os.popen( RRDTOOL_BIN + ' dump "' + ganglia_rrd + '" | head -271', 'r')

			for regel in rrd_dump.readlines():
				xml.write( regel )

			rrd_dump.close()

			xml.write( '</rrd>' )

			xml.close()

			#print RRDTOOL_BIN + ' restore /tmp/ramdisk/myblatemp.xml "' + mydata_rrd + '"'
			os.system( RRDTOOL_BIN + ' restore /tmp/ramdisk/myblatemp.xml "' + mydata_rrd + '"' )
