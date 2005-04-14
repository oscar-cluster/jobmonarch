#!/usr/bin/env python

# Specify debugging level here;
#
DEBUG_LEVEL = 10

# Wether or not to run as a daemon in background
#
DAEMONIZE = 0

# How many seconds interval for polling of jobs
#
# this will effect directly how accurate the
# end time of a job can be determined
#
TORQUE_POLL_INTERVAL = 10

from PBSQuery import PBSQuery
import sys
import time
import os
import string

class DataProcessor:

	binary = '/usr/bin/gmetric'

	def __init__( self, binary=None ):

		if binary:
			self.binary = binary

		self.dmax = TORQUE_POLL_INTERVAL

		#incompatible = self.checkGmetricVersion
		incompatible = 0

		if incompatible:
			debug_msg( 0, 'Gmetric version not compatible, pls upgrade to at least 3.0.1' )
			sys.exit( 1 )

	def checkGmetricVersion( self ):

		for line in os.popen( self.binary + ' --version' ).readlines():

			line = line.split( ' ' )

			if len( line ) == 2 and line.find( 'gmetric' ) != -1:
			
				gmetric_version = line[1]

				version_major = int( gemtric_version.split( '.' )[0] )
				version_minor = int( gemtric_version.split( '.' )[1] )
				version_patch = int( gemtric_version.split( '.' )[2] )

				incompatible = 0

				if version_major < 3:

					incompatible = 1
				
				elif version_major == 3:

					if version_minor == 0:

						if version_patch < 1:
						
							incompatbiel = 1

		return incompatible

	def multicastGmetric( self, metricname, metricval, valtype='string', tmax='15' ):

		cmd = self.binary

		try:
			cmd = cmd + ' -c' + GMOND_CONF
		except NameError:
			debug_msg( 10, 'Assuming /etc/gmond.conf for gmetric cmd (ommitting)' )

		cmd = cmd + ' -n' + metricname + ' -v"' + metricval + '" -t' + valtype + ' -x' + tmax + ' -d' + str( self.dmax )

		print cmd
		#os.system( cmd )

class PBSDataGatherer:

	jobs = { }

	def __init__( self ):

		self.pq = PBSQuery()
		self.jobs = { }
		self.dp = DataProcessor()

	def getAttr( self, attrs, name ):

		if attrs.has_key( name ):
			return attrs[name]
		else:
			return ''

	def jobDataChanged( self, jobs, job_id, attrs ):

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

		if len( known_jobs ) > 0:
			jobs = known_jobs
		else:
			jobs = { }

		joblist = self.pq.getjobs()

		jobs_processed = [ ]

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
			start_timestamp = self.getAttr( attrs, 'mtime' )
			#stop_timestamp = ''

			myAttrs = { }
			myAttrs['name'] = name
			myAttrs['queue'] = queue 
			myAttrs['owner'] = owner 
			myAttrs['requested_time'] = requested_time
			myAttrs['requested_memory'] = requested_memory
			myAttrs['ppn'] = ppn
			myAttrs['status'] = status
			myAttrs['start_timestamp'] = start_timestamp
			#myAttrs['stop_timestamp'] = stop_timestamp

			if self.jobDataChanged( jobs, job_id, myAttrs ):
				jobs[ job_id ] = myAttrs

				self.printJob( jobs, job_id )

				debug_msg( 10, printTime() + ' job %s state changed' %(job_id) )

		#for id, attrs in jobs.items():

		#	# This job was there in the last run, and not anymore
		#	# it must have finished

		#	if id not in jobs_processed and attrs['stop_timestamp'] == '':

		#		jobs[ id ]['status'] = 'F'
		#		jobs[ id ]['stop_timestamp'] = time.time()
		#		debug_msg( 10, printTime() + ' job %s finished' %(id) )
		#		self.printJob( jobs, id )

		return jobs

	def submitJobData( self, jobs ):
		"""Submit job info list"""

		time_now = time.time()

		self.dp.multicastGmetric( 'TOGA-HEARTBEAT', str( time_now ), 'float' )

		# Now let's spread the knowledge
		#
		for jobid, jobattrs in jobs.items():

			gmetric_val = self.compileGmetricVal( jobid, jobattrs )

			for val in gmetric_val:
				self.dp.multicastGmetric( 'TOGA-JOB-' + jobid, val )

	def compileGmetricVal( self, jobid, jobattrs ):
		"""Create a val string for gmetric of jobinfo"""

		name_str = 'name=' + jobattrs['name']
		queue_str = 'queue=' + jobattrs['queue']
		owner_str = 'owner=' + jobattrs['owner']
		rtime_str = 'rtime=' + jobattrs['requested_time']
		rmem_str = 'rmem=' + jobattrs['requested_memory']
		ppn_str = 'ppn=' + jobattrs['ppn']
		status_str = 'status=' + jobattrs['status']
		stime_str = 'stime=' + jobattrs['start_timestamp']

		appendList = [ name_str, queue_str, owner_str, rtime_str, rmem_str, ppn_str, status_str, stime_str ]

		return self.makeAppendLists( appendList )

	def makeAppendLists( self, append_list ):

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
