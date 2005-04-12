#!/usr/bin/env python

# Specify debugging level here;
#
DEBUG_LEVEL = 10

# If set to 1, in addition to multicasting with gmetric,
# also transmit jobinfo data to a Toga server for archival
#
ARCHIVE_MODE = 0

# Where is the toga server at
#
#TOGA_SERVER = 'monitor2.irc.sara.nl:9048'

# Wether or not to run as a daemon in background
#
DAEMONIZE = 0

# Allows to specify alternate config
#
#GMOND_CONF = '/etc/gmondconf'

from PBSQuery import PBSQuery
import sys
import time

class DataProcessor:

	binary = '/usr/bin/gmetric'

	def __init__( self, binary=None ):

		if binary:
			self.binary = binary

	def multicastGmetric( self, metricname, metricval, tmax ):

		cmd = binary

		try:
			cmd = cmd + ' -c' + GMOND_CONF
		except NameError:
			debug_msg( 8, 'Assuming /etc/gmond.conf for gmetric cmd (ommitting)' )

		cmd = cmd + ' -n' + metricname + ' -v' + metricval + ' -t' + tmax

		print cmd
		#os.system( cmd )

	def togaSubmitJob( self, jobid, jobattrs ):

		pass

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

	def getJobData( self ):

		jobs = self.jobs[:]

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
			stop_timestamp = ''

			myAttrs = { }
			myAttrs['name'] = name
			myAttrs['queue'] = queue 
			myAttrs['owner'] = owner 
			myAttrs['requested_time'] = requested_time
			myAttrs['requested_memory'] = requested_memory
			myAttrs['ppn'] = ppn
			myAttrs['status'] = status
			myAttrs['start_timestamp'] = start_timestamp
			myAttrs['stop_timestamp'] = stop_timestamp

			if self.jobDataChanged( jobs, job_id, myAttrs ):
				jobs[ job_id ] = myAttrs

				self.printJob( jobs, job_id )

				debug_msg( 10, printTime() + ' job %s state changed' %(job_id) )

		for id, attrs in jobs.items():

			# This job was there in the last run, and not anymore
			# it must have finished

			if id not in jobs_processed and attrs['stop_timestamp'] == '':

				jobs[ id ]['status'] = 'F'
				jobs[ id ]['stop_timestamp'] = time.time()
				debug_msg( 10, printTime() + ' job %s finished' %(id) )
				self.printJob( jobs, id )

		# Now let's spread the knowledge
		#
		for jobid, jobattrs in jobs.items():

			if ARCHIVE_MODE:

				if self.jobDataChanged( self.jobs, jobid, jobattrs ):

					self.dp.togaSubmitJob( jobid, jobattrs )

			self.dp.multicastGmetric( jobid, jobattrs )
					
		self.jobs = jobs

	def printJobs( self, jobs ):
	
		for name, attrs in self.jobs.items():

			print 'job %s' %(name)

			for name, val in attrs.items():

				print '\t%s = %s' %( name, val )

	def printJob( self, jobs, job_id ):

		print 'job %s' %(job_id)

		for name, val in self.jobs[ job_id ].items():

			print '\t%s = %s' %( name, val )

        def daemon( self ):
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

        def run( self ):
                "Main thread"

                while ( 1 ):
		
			self.getJobData()
			time.sleep( 1 )	

def printTime( ):

	return time.strftime("%a, %d %b %Y %H:%M:%S")

def debug_msg( level, msg ):

        if (DEBUG_LEVEL >= level):
	                sys.stderr.write( msg + '\n' )

def main():

	gather = PBSDataGatherer()
	if DAEMONIZE:
		gather.daemon()
	else:
		gather.run()

if __name__ == '__main__':
	main()
