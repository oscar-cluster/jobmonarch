#!/usr/bin/env python

import rrdtool
import os

class Toga:

	def RrdMerge(self, rrd1, rrd2, newrrd):

		rrdtool.resize( rrd1, '0', 'GROW', '240' )
		os.rename( './resize.rrd', newrrd )
		
		newvals = rrdtool.fetch( rrd2, 'AVERAGE', '-r', '15',  '-s', '10:55', '-e', '11:55' )
		print newvals
		times = newvals[0]

		start_time = times[0]
		end_time = times[1]
		time_interval = times[2]

		last = rrdtool.last( rrd1 )

		dss = newvals[1]
		ds = dss[0]

		values = newvals[2]

		loop_count = 0
		for val in values:
			timestamp = start_time + ( loop_count * time_interval )
			update_string = '%s:%s' %(timestamp, val[0])
			if timestamp > last and val[0]:
				print 'rrdtool update %s -t sum %s' %(newrrd, update_string)
				rrdtool.update( newrrd, '-t', 'sum', update_string )
			loop_count = loop_count + 1

def main():

	t = Toga()

	t.RrdMerge( '/tmp/test_cpu_num01.rrd', '/tmp/test_cpu_num02.rrd', './test_cpu_merged.rrd' )

main()
