#!/usr/bin/env python

from PBSQuery import PBSQuery

class PBSDataGatherer:

	def __init__( self ):

		self.pq = PBSQuery()
		
	def getJobList( self ):

		joblist = self.pq.getjobs().items
		#for name, job in joblist:
			

def main():

	gather = PBSDataGatherer()

	print 'blaat'

if __name__ == '__main__':
	main()
