#!/usr/bin/env python

# Specify debugging level here;
#
# 10 = gemtric cmd's
DEBUG_LEVEL = 0

# Wether or not to run as a daemon in background
#
DAEMONIZE = 1

# Which multicast port (and gmond) to transmit to
#
GANGLIA_MULTICAST_PORT = 8649

# Which Torque server to monitor
#
TORQUE_SERVER = 'localhost'

# How many seconds interval for polling of jobs
#
# this will effect directly how accurate the
# end time of a job can be determined
#
TORQUE_POLL_INTERVAL = 10

# Alternate location of gmond.conf
#
# Default: /etc/gmond.conf
#
#GMOND_CONF = '/etc/gmond.conf'

from PBSQuery import PBSQuery
import sys
import time
import os
import socket
import string

class DataProcessor:
	"""Class for processing of data"""

	binary = '/usr/bin/gmetric'

	def __init__( self, binary=None ):
		"""Remember alternate binary location if supplied"""

		if binary:
			self.binary = binary

		# Timeout for XML
		#
		# From ganglia's documentation:
		#
		# 'A metric will be deleted DMAX seconds after it is received, and
	        # DMAX=0 means eternal life.'

		self.dmax = str( int( TORQUE_POLL_INTERVAL ) )

		try:
			gmond_file = GMOND_CONF

		except NameError:
			gmond_file = '/etc/gmond.conf'

		if not os.path.exists( gmond_file ):
			debug_msg( 0, gmond_file + ' does not exist' )
			sys.exit( 1 )

		incompatible = self.checkGmetricVersion()

		if incompatible:
			debug_msg( 0, 'Gmetric version not compatible, pls upgrade to at least 3.0.1' )
			sys.exit( 1 )

	def checkGmetricVersion( self ):
		"""
		Check version of gmetric is at least 3.0.1
		for the syntax we use
		"""

		for line in os.popen( self.binary + ' --version' ).readlines():

			line = line.split( ' ' )

			if len( line ) == 2 and str(line).find( 'gmetric' ) != -1:
			
				gmetric_version = line[1].split( '\n' )[0]

				version_major = int( gmetric_version.split( '.' )[0] )
				version_minor = int( gmetric_version.split( '.' )[1] )
				version_patch = int( gmetric_version.split( '.' )[2] )

				incompatible = 0

				if version_major < 3:

					incompatible = 1
				
				elif version_major == 3:

					if version_minor == 0:

						if version_patch < 1:
						
							incompatible = 1

		return incompatible

	def multicastGmetric( self, metricname, metricval, valtype='string' ):
		"""Call gmetric binary and multicast"""

		cmd = self.binary

		try:
			cmd = cmd + ' -c' + GMOND_CONF
		except NameError:
			debug_msg( 10, 'Assuming /etc/gmond.conf for gmetric cmd (ommitting)' )

		cmd = cmd + ' -n' + str( metricname )+ ' -v"' + str( metricval )+ '" -t' + str( valtype ) + ' -d' + str( self.dmax )

		debug_msg( 10, printTime() + ' ' + cmd )
		os.system( cmd )

class PBSDataGatherer:

	jobs = { }

	def __init__( self ):
		"""Setup appropriate variables"""

		self.jobs = { }
		self.dp = DataProcessor()
		self.initPbsQuery()

	def initPbsQuery( self ):

		self.pq = None
		if( TORQUE_SERVER ):
			self.pq = PBSQuery( TORQUE_SERVER )
		else
			self.pq = PBSQuery()

	def getAttr( self, attrs, name ):
		"""Return certain attribute from dictionary, if exists"""

		if attrs.has_key( name ):
			return attrs[name]
		else:
			return ''

	def jobDataChanged( self, jobs, job_id, attrs ):
		"""Check if job with attrs and job_id in jobs has changed"""

		if jobs.has_key( job_id ):
			oldData = jobs[ job_id ]	
		else:
			return 1

		for name, val in attrs.items():

			if oldData.has_key( name ):

				if oldData[ name ] != attrs[ name ]:

					return 1

			else:
				return 1

		return 0

	def getJobData( self, known_jobs ):
		"""Gather all data on current jobs in Torque"""

		if len( known_jobs ) > 0:
			jobs = known_jobs
		else:
			jobs = { }

		#self.initPbsQuery()
	
		#print self.pq.getnodes()
	
		joblist = self.pq.getjobs()

		self.cur_time = time.time()

		jobs_processed = [ ]

		#self.printJobs( joblist )

		for name, attrs in joblist.items():

			job_id = name.split( '.' )[0]

			jobs_processed.append( job_id )

			name = self.getAttr( attrs, 'Job_Name' )
			queue = self.getAttr( attrs, 'queue' )
			owner = self.getAttr( attrs, 'Job_Owner' ).split( '@' )[0]
			requested_time = self.getAttr( attrs, 'Resource_List.walltime' )
			requested_memory = self.getAttr( attrs, 'Resource_List.mem' )

			mynoderequest = self.getAttr( attrs, 'Resource_List.nodes' )

			if mynoderequest.find( ':' ) != -1 and mynoderequest.find( 'ppn' ) != -1:
				ppn = mynoderequest.split( ':' )[1].split( 'ppn=' )[1]
			else:
				ppn = ''

			status = self.getAttr( attrs, 'job_state' )

			if status == 'R':
				start_timestamp = self.getAttr( attrs, 'mtime' )
				nodes = self.getAttr( attrs, 'exec_host' ).split( '+' )

				nodeslist = [ ]

				for node in nodes:
					host = node.split( '/' )[0]

					if nodeslist.count( host ) == 0:
						nodeslist.append( host )

			elif status == 'Q':
				start_timestamp = ''
				count_mynodes = 0
				numeric_node = 1

				for node in mynoderequest.split( '+' ):

					nodepart = node.split( ':' )[0]

					for letter in nodepart:

						if letter not in string.digits:

							numeric_node = 0

					if not numeric_node:
						count_mynodes = count_mynodes + 1
					else:
						count_mynodes = count_mynodes + int( nodepart )
						
				nodeslist = count_mynodes

			myAttrs = { }
			myAttrs['name'] = name
			myAttrs['queue'] = queue 
			myAttrs['owner'] = owner 
			myAttrs['requested_time'] = requested_time
			myAttrs['requested_memory'] = requested_memory
			myAttrs['ppn'] = ppn
			myAttrs['status'] = status
			myAttrs['start_timestamp'] = start_timestamp
			myAttrs['reported'] = str( int( self.cur_time ) )
			myAttrs['nodes'] = nodeslist
			myAttrs['domain'] = string.join( socket.getfqdn().split( '.' )[1:], '.' )
			myAttrs['poll_interval'] = TORQUE_POLL_INTERVAL

			if self.jobDataChanged( jobs, job_id, myAttrs ):
				jobs[ job_id ] = myAttrs

				#debug_msg( 10, printTime() + ' job %s state changed' %(job_id) )

		for id, attrs in jobs.items():

			if id not in jobs_processed:

				# This one isn't there anymore; toedeledoki!
				#
				del jobs[ id ]

		return jobs

	def submitJobData( self, jobs ):
		"""Submit job info list"""

		self.dp.multicastGmetric( 'TOGA-HEARTBEAT', str( int( self.cur_time ) ) )

		# Now let's spread the knowledge
		#
		for jobid, jobattrs in jobs.items():

			gmetric_val = self.compileGmetricVal( jobid, jobattrs )

			for val in gmetric_val:
				self.dp.multicastGmetric( 'TOGA-JOB-' + jobid, val )

	def makeNodeString( self, nodelist ):
		"""Make one big string of all hosts"""

		node_str = None

		for node in nodelist:
			if not node_str:
				node_str = node
			else:
				node_str = node_str + ';' + node

		return node_str

	def compileGmetricVal( self, jobid, jobattrs ):
		"""Create a val string for gmetric of jobinfo"""

		appendList = [ ]
		appendList.append( 'name=' + jobattrs['name'] )
		appendList.append( 'queue=' + jobattrs['queue'] )
		appendList.append( 'owner=' + jobattrs['owner'] )
		appendList.append( 'requested_time=' + jobattrs['requested_time'] )

		if jobattrs['requested_memory'] != '':
			appendList.append( 'requested_memory=' + jobattrs['requested_memory'] )

		if jobattrs['ppn'] != '':
			appendList.append( 'ppn=' + jobattrs['ppn'] )

		appendList.append( 'status=' + jobattrs['status'] )

		if jobattrs['start_timestamp'] != '':
			appendList.append( 'start_timestamp=' + jobattrs['start_timestamp'] )

		appendList.append( 'reported=' + jobattrs['reported'] )
		appendList.append( 'poll_interval=' + str( jobattrs['poll_interval'] ) )
		appendList.append( 'domain=' + jobattrs['domain'] )

		if jobattrs['status'] == 'R':
			if len( jobattrs['nodes'] ) > 0:
				appendList.append( 'nodes=' + self.makeNodeString( jobattrs['nodes'] ) )
		elif jobattrs['status'] == 'Q':
			appendList.append( 'nodes=' + str(jobattrs['nodes']) )

		return self.makeAppendLists( appendList )

	def makeAppendLists( self, append_list ):
		"""
		Divide all values from append_list over strings with a maximum
		size of 1400
		"""

		app_lists = [ ]

		mystr = None

		for val in append_list:

			if not mystr:
				mystr = val
			else:
				if not self.checkValAppendMaxSize( mystr, val ):
					mystr = mystr + ' ' + val
				else:
					# Too big, new appenlist
					app_lists.append( mystr )
					mystr = val

		app_lists.append( mystr )

		return app_lists

	def checkValAppendMaxSize( self, val, text ):
		"""Check if val + text size is not above 1400 (max msg size)"""

		# Max frame size of a udp datagram is 1500 bytes
		# removing misc header and gmetric stuff leaves about 1400 bytes
		#
		if len( val + text ) > 1400:
			return 1
		else:
			return 0

	def printJobs( self, jobs ):
		"""Print a jobinfo overview"""

		for name, attrs in self.jobs.items():

			print 'job %s' %(name)

			for name, val in attrs.items():

				print '\t%s = %s' %( name, val )

	def printJob( self, jobs, job_id ):
		"""Print job with job_id from jobs"""

		print 'job %s' %(job_id)

		for name, val in jobs[ job_id ].items():

			print '\t%s = %s' %( name, val )

        def daemon( self ):
                """Run as daemon forever"""

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
                """Main thread"""

                while ( 1 ):
		
			self.jobs = self.getJobData( self.jobs )
			self.submitJobData( self.jobs )
			time.sleep( TORQUE_POLL_INTERVAL )	

def printTime( ):
	"""Print current time/date in human readable format for log/debug"""

	return time.strftime("%a, %d %b %Y %H:%M:%S")

def debug_msg( level, msg ):
	"""Print msg if at or above current debug level"""

        if (DEBUG_LEVEL >= level):
	                sys.stderr.write( msg + '\n' )

def main():
	"""Application start"""

	gather = PBSDataGatherer()
	if DAEMONIZE:
		gather.daemon()
	else:
		gather.run()

# w00t someone started me
#
if __name__ == '__main__':
	main()
