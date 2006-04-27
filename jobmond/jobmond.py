#!/usr/bin/env python
#
# This file is part of Jobmonarch
#
# Copyright (C) 2006  Ramon Bastiaans
# 
# Jobmonarch is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Jobmonarch is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
# 
# SVN $Id$
#

import sys, getopt, ConfigParser

def processArgs( args ):

	SHORT_L = 'c:'
	LONG_L = 'config='

	config_filename = None

	try:

		opts, args = getopt.getopt( args, SHORT_L, LONG_L )

	except getopt.error, detail:

		print detail
		sys.exit(1)

	for opt, value in opts:

		if opt in [ '--config', '-c' ]:
		
			config_filename = value

	if not config_filename:

		config_filename = '/etc/jobmond.conf'

	return loadConfig( config_filename )

def loadConfig( filename ):

        def getlist( cfg_string ):

                my_list = [ ]

                for item_txt in cfg_string.split( ',' ):

                        sep_char = None

                        item_txt = item_txt.strip()

                        for s_char in [ "'", '"' ]:

                                if item_txt.find( s_char ) != -1:

                                        if item_txt.count( s_char ) != 2:

                                                print 'Missing quote: %s' %item_txt
                                                sys.exit( 1 )

                                        else:

                                                sep_char = s_char
                                                break

                        if sep_char:

                                item_txt = item_txt.split( sep_char )[1]

                        my_list.append( item_txt )

                return my_list

	cfg = ConfigParser.ConfigParser()

	cfg.read( filename )

	global DEBUG_LEVEL, DAEMONIZE, TORQUE_SERVER, TORQUE_POLL_INTERVAL, GMOND_CONF, DETECT_TIME_DIFFS, BATCH_HOST_TRANSLATE

	DEBUG_LEVEL = cfg.getint( 'DEFAULT', 'DEBUG_LEVEL' )

	DAEMONIZE = cfg.getboolean( 'DEFAULT', 'DAEMONIZE' )

	TORQUE_SERVER = cfg.get( 'DEFAULT', 'TORQUE_SERVER' )

	TORQUE_POLL_INTERVAL = cfg.getint( 'DEFAULT', 'TORQUE_POLL_INTERVAL' )

	GMOND_CONF = cfg.get( 'DEFAULT', 'GMOND_CONF' )

	DETECT_TIME_DIFFS = cfg.getboolean( 'DEFAULT', 'DETECT_TIME_DIFFS' )

	BATCH_HOST_TRANSLATE = getlist( cfg.get( 'DEFAULT', 'BATCH_HOST_TRANSLATE' ) )

	return True

from PBSQuery import PBSQuery

import time, os, socket, string, re

METRIC_MAX_VAL_LEN = 900

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

		self.dmax = str( int( int( TORQUE_POLL_INTERVAL ) * 2 ) )

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

class DataGatherer:

	jobs = { }

	def __init__( self ):
		"""Setup appropriate variables"""

		self.jobs = { }
		self.timeoffset = 0
		self.dp = DataProcessor()
		self.initPbsQuery()

	def initPbsQuery( self ):

		self.pq = None
		if( TORQUE_SERVER ):
			self.pq = PBSQuery( TORQUE_SERVER )
		else:
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

			queued_timestamp = self.getAttr( attrs, 'ctime' )

			if status == 'R':
				start_timestamp = self.getAttr( attrs, 'mtime' )
				nodes = self.getAttr( attrs, 'exec_host' ).split( '+' )

				nodeslist = [ ]

				for node in nodes:
					host = node.split( '/' )[0]

					if nodeslist.count( host ) == 0:

						for translate_pattern in BATCH_HOST_TRANSLATE:

							if translate_pattern.find( '/' ) != -1:

								translate_orig = translate_pattern.split( '/' )[1]
								translate_new = translate_pattern.split( '/' )[2]

								host = re.sub( translate_orig, translate_new, host )
				
						if not host in nodeslist:
				
							nodeslist.append( host )

				if DETECT_TIME_DIFFS:

					# If a job start if later than our current date,
					# that must mean the Torque server's time is later
					# than our local time.
				
					if int(start_timestamp) > int( int(self.cur_time) + int(self.timeoffset) ):

						self.timeoffset = int( int(start_timestamp) - int(self.cur_time) )

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
						
				nodeslist = str( count_mynodes )
			else:
				start_timestamp = ''
				nodeslist = ''

			myAttrs = { }
			myAttrs['name'] = str( name )
			myAttrs['queue'] = str( queue )
			myAttrs['owner'] = str( owner )
			myAttrs['requested_time'] = str( requested_time )
			myAttrs['requested_memory'] = str( requested_memory )
			myAttrs['ppn'] = str( ppn )
			myAttrs['status'] = str( status )
			myAttrs['start_timestamp'] = str( start_timestamp )
			myAttrs['queued_timestamp'] = str( queued_timestamp )
			myAttrs['reported'] = str( int( int( self.cur_time ) + int( self.timeoffset ) ) )
			myAttrs['nodes'] = nodeslist
			myAttrs['domain'] = string.join( socket.getfqdn().split( '.' )[1:], '.' )
			myAttrs['poll_interval'] = str( TORQUE_POLL_INTERVAL )

			if self.jobDataChanged( jobs, job_id, myAttrs ) and myAttrs['status'] in [ 'R', 'Q' ]:
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

		self.dp.multicastGmetric( 'MONARCH-HEARTBEAT', str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )

		# Now let's spread the knowledge
		#
		for jobid, jobattrs in jobs.items():

			gmetric_val = self.compileGmetricVal( jobid, jobattrs )

			metric_increment = 0

			for val in gmetric_val:
				self.dp.multicastGmetric( 'MONARCH-JOB-' + jobid + '-' + str(metric_increment), val )
				metric_increment = metric_increment + 1

	def compileGmetricVal( self, jobid, jobattrs ):
		"""Create a val string for gmetric of jobinfo"""

		gval_lists = [ ]

		mystr = None

		val_list = { }

		for val_name, val_value in jobattrs.items():

			val_list_names_len	= len( string.join( val_list.keys() ) ) + len(val_list.keys())
			val_list_vals_len	= len( string.join( val_list.values() ) ) + len(val_list.values())

			if val_name == 'nodes' and jobattrs['status'] == 'R':

				node_str = None

				for node in val_value:

					if node_str:
						node_str = node_str + ';' + node
					else:
						node_str = node

					if (val_list_names_len + len(val_name) ) + (val_list_vals_len + len(node_str) ) > METRIC_MAX_VAL_LEN:

						val_list[ val_name ] = node_str
						gval_lists.append( val_list )
						val_list = { }
						node_str = None

				val_list[ val_name ] = node_str
				gval_lists.append( val_list )
				val_list = { }

			elif val_value != '':

				if (val_list_names_len + len(val_name) ) + (val_list_vals_len + len(str(val_value)) ) > METRIC_MAX_VAL_LEN:

					gval_lists.append( val_list )
					val_list = { }

				val_list[ val_name ] = val_value

		if len(val_list) > 0:
			gval_lists.append( val_list )

		str_list = [ ]

		for val_list in gval_lists:

			my_val_str = None

			for val_name, val_value in val_list.items():

				if my_val_str:

					my_val_str = my_val_str + ' ' + val_name + '=' + val_value
				else:
					my_val_str = val_name + '=' + val_value

			str_list.append( my_val_str )

		return str_list

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
                        sys.exit(0)  # end parent

                # creates a session and sets the process group ID
                #
                os.setsid()

                # Fork the second child
                #
                pid = os.fork()
                if pid > 0:
                        sys.exit(0)  # end parent

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

	if not processArgs( sys.argv[1:] ):
		sys.exit( 1 )

	gather = DataGatherer()
	if DAEMONIZE:
		gather.daemon()
	else:
		gather.run()

# w00t someone started me
#
if __name__ == '__main__':
	main()
