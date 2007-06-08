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
import time, os, socket, string, re
import xml, xml.sax
from xml.sax import saxutils, make_parser
from xml.sax import make_parser
from xml.sax.handler import feature_namespaces

def usage():

	print
	print 'usage: jobmond [options]'
	print 'options:'
	print '      --config, -c      configuration file'
	print '      --pidfile, -p     pid file'
	print '      --help, -h        help'
	print

def processArgs( args ):

	SHORT_L		= 'hc:'
	LONG_L		= [ 'help', 'config=' ]

	global PIDFILE
	PIDFILE		= None

	config_filename	= '/etc/jobmond.conf'

	try:

		opts, args	= getopt.getopt( args, SHORT_L, LONG_L )

	except getopt.GetoptError, detail:

		print detail
		usage()
		sys.exit( 1 )

	for opt, value in opts:

		if opt in [ '--config', '-c' ]:
		
			config_filename	= value

		if opt in [ '--pidfile', '-p' ]:

			PIDFILE		= value
		
		if opt in [ '--help', '-h' ]:
 
			usage()
			sys.exit( 0 )

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

	cfg		= ConfigParser.ConfigParser()

	cfg.read( filename )

	global DEBUG_LEVEL, DAEMONIZE, BATCH_SERVER, BATCH_POLL_INTERVAL
	global GMOND_CONF, DETECT_TIME_DIFFS, BATCH_HOST_TRANSLATE
	global BATCH_API, QUEUE, GMETRIC_TARGET

	DEBUG_LEVEL	= cfg.getint( 'DEFAULT', 'DEBUG_LEVEL' )

	DAEMONIZE	= cfg.getboolean( 'DEFAULT', 'DAEMONIZE' )

	try:

		BATCH_SERVER		= cfg.get( 'DEFAULT', 'BATCH_SERVER' )

	except ConfigParser.NoOptionError:

		# Backwards compatibility for old configs
		#

		BATCH_SERVER		= cfg.get( 'DEFAULT', 'TORQUE_SERVER' )
		api_guess		= 'pbs'
	
	try:
	
		BATCH_POLL_INTERVAL	= cfg.getint( 'DEFAULT', 'BATCH_POLL_INTERVAL' )

	except ConfigParser.NoOptionError:

		# Backwards compatibility for old configs
		#

		BATCH_POLL_INTERVAL	= cfg.getint( 'DEFAULT', 'TORQUE_POLL_INTERVAL' )
		api_guess		= 'pbs'
	
	try:

		GMOND_CONF		= cfg.get( 'DEFAULT', 'GMOND_CONF' )

	except ConfigParser.NoOptionError:

		GMOND_CONF		= None

	DETECT_TIME_DIFFS	= cfg.getboolean( 'DEFAULT', 'DETECT_TIME_DIFFS' )

	BATCH_HOST_TRANSLATE	= getlist( cfg.get( 'DEFAULT', 'BATCH_HOST_TRANSLATE' ) )

	try:

		BATCH_API	= cfg.get( 'DEFAULT', 'BATCH_API' )

	except ConfigParser.NoOptionError, detail:

		if BATCH_SERVER and api_guess:

			BATCH_API	= api_guess
		else:
			debug_msg( 0, "fatal error: BATCH_API not set and can't make guess" )
			sys.exit( 1 )

	try:

		QUEUE		= getlist( cfg.get( 'DEFAULT', 'QUEUE' ) )

	except ConfigParser.NoOptionError, detail:

		QUEUE		= None

	try:

		GMETRIC_TARGET	= cfg.get( 'DEFAULT', 'GMETRIC_TARGET' )

	except ConfigParser.NoOptionError:

		GMETRIC_TARGET	= None

		if not GMOND_CONF:

			debug_msg( 0, "fatal error: GMETRIC_TARGET or GMOND_CONF both not set!" )
			sys.exit( 1 )
		else:

			debug_msg( 0, "error: GMETRIC_TARGET not set: internel Gmetric handling aborted. Failing back to DEPRECATED use of gmond.conf/gmetric binary. This will slow down jobmond significantly!" )

	return True

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

		self.dmax = str( int( int( BATCH_POLL_INTERVAL ) * 2 ) )

		if GMOND_CONF:

			try:
				gmond_file = GMOND_CONF

			except NameError:
				gmond_file = '/etc/gmond.conf'

			if not os.path.exists( gmond_file ):
				debug_msg( 0, 'fatal error: ' + gmond_file + ' does not exist' )
				sys.exit( 1 )

			incompatible = self.checkGmetricVersion()

			if incompatible:

				debug_msg( 0, 'Gmetric version not compatible, please upgrade to at least 3.0.1' )
				sys.exit( 1 )

	def checkGmetricVersion( self ):

		"""
		Check version of gmetric is at least 3.0.1
		for the syntax we use
		"""

		global METRIC_MAX_VAL_LEN

		incompatible	= 0

		gfp		= os.popen( self.binary + ' --version' )
		lines		= gfp.readlines()

		gfp.close()

		for line in lines:

			line = line.split( ' ' )

			if len( line ) == 2 and str( line ).find( 'gmetric' ) != -1:
			
				gmetric_version	= line[1].split( '\n' )[0]

				version_major	= int( gmetric_version.split( '.' )[0] )
				version_minor	= int( gmetric_version.split( '.' )[1] )
				version_patch	= int( gmetric_version.split( '.' )[2] )

				incompatible	= 0

				if version_major < 3:

					incompatible = 1
				
				elif version_major == 3:

					if version_minor == 0:

						if version_patch < 1:
						
							incompatible = 1

						if version_patch < 3:

							METRIC_MAX_VAL_LEN = 900

						elif version_patch >= 3:

							METRIC_MAX_VAL_LEN = 1400

		return incompatible

	def multicastGmetric( self, metricname, metricval, valtype='string' ):

		"""Call gmetric binary and multicast"""

		cmd = self.binary

		if GMETRIC_TARGET:

			from gmetric import Gmetric

		if GMETRIC_TARGET:

			GMETRIC_TARGET_HOST	= GMETRIC_TARGET.split( ':' )[0]
			GMETRIC_TARGET_PORT	= GMETRIC_TARGET.split( ':' )[1]

			metric_debug		= "[gmetric] name: %s - val: %s - dmax: %s" %( str( metricname ), str( metricval ), str( self.dmax ) )

			debug_msg( 10, printTime() + ' ' + metric_debug)

			gm = Gmetric( GMETRIC_TARGET_HOST, GMETRIC_TARGET_PORT )

			gm.send( str( metricname ), str( metricval ), str( self.dmax ) )

		else:
			try:
				cmd = cmd + ' -c' + GMOND_CONF

			except NameError:

				debug_msg( 10, 'Assuming /etc/gmond.conf for gmetric cmd (ommitting)' )

			cmd = cmd + ' -n' + str( metricname )+ ' -v"' + str( metricval )+ '" -t' + str( valtype ) + ' -d' + str( self.dmax )

			debug_msg( 10, printTime() + ' ' + cmd )

			os.system( cmd )

class DataGatherer:

	"""Skeleton class for batch system DataGatherer"""

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

		write_pidfile()

                # Go to the root directory and set the umask
                #
                os.chdir('/')
                os.umask(0)

                sys.stdin.close()
                sys.stdout.close()
                sys.stderr.close()

                os.open('/dev/null', os.O_RDWR)
                os.dup2(0, 1)
                os.dup2(0, 2)

                self.run()

        def run( self ):

                """Main thread"""

                while ( 1 ):
		
			self.getJobData()
			self.submitJobData()
			time.sleep( BATCH_POLL_INTERVAL )	

class SgeQstatXMLParser(xml.sax.handler.ContentHandler):

	"""Babu Sundaram's experimental SGE qstat XML parser"""

	def __init__(self, qstatinxml):

		self.qstatfile = qstatinxml
		self.attribs = {}
		self.value = ''
		self.jobID = ''
		self.currentJobInfo = ''
		self.job_list = []
		self.EOFFlag = 0
		self.jobinfoCount = 0


	def startElement(self, name, attrs):

		if name == 'job_list':
			self.currentJobInfo = 'Status=' + attrs.get('state', None) + ' '
		elif name == 'job_info':
			self.job_list = []
			self.jobinfoCount += 1

	def characters(self, ch):

		self.value = self.value + ch

	def endElement(self, name):

		if len(self.value.strip()) > 0 :

			self.currentJobInfo += name + '=' + self.value.strip() + ' '         
		elif name != 'job_list':

			self.currentJobInfo += name + '=Unknown '

		if name == 'JB_job_number':

			self.jobID = self.value.strip()
			self.job_list.append(self.jobID)          

		if name == 'job_list':

			if self.attribs.has_key(self.jobID) == False:
				self.attribs[self.jobID] = self.currentJobInfo
			elif self.attribs.has_key(self.jobID) and self.attribs[self.jobID] != self.currentJobInfo:
				self.attribs[self.jobID] = self.currentJobInfo
			self.currentJobInfo = ''
			self.jobID = ''

		elif name == 'job_info' and self.jobinfoCount == 2:

			deljobs = []
			for id in self.attribs:
				try:
					self.job_list.index(str(id))
				except ValueError:
					deljobs.append(id)
			for i in deljobs:
				del self.attribs[i]
			deljobs = []
			self.jobinfoCount = 0

		self.value = ''

class SgeDataGatherer(DataGatherer):

	jobs = { }
	SGE_QSTAT_XML_FILE	= '/tmp/.jobmonarch.sge.qstat'

	def __init__( self ):
		"""Setup appropriate variables"""

		self.jobs = { }
		self.timeoffset = 0
		self.dp = DataProcessor()
		self.initSgeJobInfo()

	def initSgeJobInfo( self ):
		"""This is outside the scope of DRMAA; Get the current jobs in SGE"""
		"""This is a hack because we cant get info about jobs beyond"""
		"""those in the current DRMAA session"""

		self.qstatparser = SgeQstatXMLParser( self.SGE_QSTAT_XML_FILE )

		# Obtain the qstat information from SGE in XML format
		# This would change to DRMAA-specific calls from 6.0u9

	def getJobData(self):
		"""Gather all data on current jobs in SGE"""

		# Get the information about the current jobs in the SGE queue
		info = os.popen("qstat -ext -xml").readlines()
		f = open(self.SGE_QSTAT_XML_FILE,'w')
		for lines in info:
			f.write(lines)
		f.close()

		# Parse the input
		f = open(self.qstatparser.qstatfile, 'r')
		xml.sax.parse(f, self.qstatparser)
		f.close()

		self.cur_time = time.time()

		return self.qstatparser.attribs

	def submitJobData(self):
		"""Submit job info list"""

		self.dp.multicastGmetric( 'MONARCH-HEARTBEAT', str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )
		# Now let's spread the knowledge
		#
		metric_increment = 0
		for jobid, jobattrs in self.qstatparser.attribs.items():

			self.dp.multicastGmetric( 'MONARCH-JOB-' + jobid + '-' + str(metric_increment), jobattrs)

class PbsDataGatherer( DataGatherer ):

	"""This is the DataGatherer for PBS and Torque"""

	global PBSQuery

	def __init__( self ):

		"""Setup appropriate variables"""

		self.jobs	= { }
		self.timeoffset	= 0
		self.dp		= DataProcessor()

		self.initPbsQuery()

	def initPbsQuery( self ):

		self.pq		= None

		if( BATCH_SERVER ):

			self.pq		= PBSQuery( BATCH_SERVER )
		else:
			self.pq		= PBSQuery()

	def getAttr( self, attrs, name ):

		"""Return certain attribute from dictionary, if exists"""

		if attrs.has_key( name ):

			return attrs[ name ]
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

	def getJobData( self ):

		"""Gather all data on current jobs in Torque"""

		joblist		= {}
		self.cur_time	= 0

		try:
			joblist		= self.pq.getjobs()
			self.cur_time	= time.time()

		except PBSError, detail:

			debug_msg( 10, "Caught PBS unavailable, skipping until next polling interval: " + str( detail ) )
			return None

		jobs_processed	= [ ]

		for name, attrs in joblist.items():

			job_id			= name.split( '.' )[0]

			jobs_processed.append( job_id )

			name			= self.getAttr( attrs, 'Job_Name' )
			queue			= self.getAttr( attrs, 'queue' )

			if QUEUE:

				if QUEUE != queue:

					continue

			owner			= self.getAttr( attrs, 'Job_Owner' ).split( '@' )[0]
			requested_time		= self.getAttr( attrs, 'Resource_List.walltime' )
			requested_memory	= self.getAttr( attrs, 'Resource_List.mem' )

			mynoderequest		= self.getAttr( attrs, 'Resource_List.nodes' )

			ppn			= ''

			if mynoderequest.find( ':' ) != -1 and mynoderequest.find( 'ppn' ) != -1:

				mynoderequest_fields	= mynoderequest.split( ':' )

				for mynoderequest_field in mynoderequest_fields:

					if mynoderequest_field.find( 'ppn' ) != -1:

						ppn	= mynoderequest_field.split( 'ppn=' )[1]

			status			= self.getAttr( attrs, 'job_state' )

			queued_timestamp	= self.getAttr( attrs, 'ctime' )

			if status == 'R':

				start_timestamp		= self.getAttr( attrs, 'mtime' )
				nodes			= self.getAttr( attrs, 'exec_host' ).split( '+' )

				nodeslist		= [ ]

				for node in nodes:

					host		= node.split( '/' )[0]

					if nodeslist.count( host ) == 0:

						for translate_pattern in BATCH_HOST_TRANSLATE:

							if translate_pattern.find( '/' ) != -1:

								translate_orig	= translate_pattern.split( '/' )[1]
								translate_new	= translate_pattern.split( '/' )[2]

								host		= re.sub( translate_orig, translate_new, host )
				
						if not host in nodeslist:
				
							nodeslist.append( host )

				if DETECT_TIME_DIFFS:

					# If a job start if later than our current date,
					# that must mean the Torque server's time is later
					# than our local time.
				
					if int( start_timestamp ) > int( int( self.cur_time ) + int( self.timeoffset ) ):

						self.timeoffset	= int( int(start_timestamp) - int(self.cur_time) )

			elif status == 'Q':

				start_timestamp		= ''
				count_mynodes		= 0
				numeric_node		= 1

				for node in mynoderequest.split( '+' ):

					nodepart	= node.split( ':' )[0]

					for letter in nodepart:

						if letter not in string.digits:

							numeric_node	= 0

					if not numeric_node:

						count_mynodes	= count_mynodes + 1
					else:
						try:
							count_mynodes	= count_mynodes + int( nodepart )

						except ValueError, detail:

							debug_msg( 10, str( detail ) )
							debug_msg( 10, "Encountered weird node in Resources_List?!" )
							debug_msg( 10, 'nodepart = ' + str( nodepart ) )
							debug_msg( 10, 'job = ' + str( name ) )
							debug_msg( 10, 'attrs = ' + str( attrs ) )
						
				nodeslist	= str( count_mynodes )
			else:
				start_timestamp	= ''
				nodeslist	= ''

			myAttrs				= { }

			myAttrs[ 'name' ]			= str( name )
			myAttrs[ 'queue' ]		= str( queue )
			myAttrs[ 'owner' ]		= str( owner )
			myAttrs[ 'requested_time' ]	= str( requested_time )
			myAttrs[ 'requested_memory' ]	= str( requested_memory )
			myAttrs[ 'ppn' ]		= str( ppn )
			myAttrs[ 'status' ]		= str( status )
			myAttrs[ 'start_timestamp' ]	= str( start_timestamp )
			myAttrs[ 'queued_timestamp' ]	= str( queued_timestamp )
			myAttrs[ 'reported' ]		= str( int( int( self.cur_time ) + int( self.timeoffset ) ) )
			myAttrs[ 'nodes' ]		= nodeslist
			myAttrs[ 'domain' ]		= string.join( socket.getfqdn().split( '.' )[1:], '.' )
			myAttrs[ 'poll_interval' ]	= str( BATCH_POLL_INTERVAL )

			if self.jobDataChanged( self.jobs, job_id, myAttrs ) and myAttrs['status'] in [ 'R', 'Q' ]:

				self.jobs[ job_id ]	= myAttrs

		for id, attrs in self.jobs.items():

			if id not in jobs_processed:

				# This one isn't there anymore; toedeledoki!
				#
				del self.jobs[ id ]

	def submitJobData( self ):

		"""Submit job info list"""

		self.dp.multicastGmetric( 'MONARCH-HEARTBEAT', str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )

		# Now let's spread the knowledge
		#
		for jobid, jobattrs in self.jobs.items():

			gmetric_val		= self.compileGmetricVal( jobid, jobattrs )
			metric_increment	= 0

			for val in gmetric_val:

				self.dp.multicastGmetric( 'MONARCH-JOB-' + jobid + '-' + str(metric_increment), val )

				metric_increment	= metric_increment + 1

	def compileGmetricVal( self, jobid, jobattrs ):

		"""Create a val string for gmetric of jobinfo"""

		gval_lists	= [ ]
		mystr		= None
		val_list	= { }

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

						val_list[ val_name ]	= node_str

						gval_lists.append( val_list )

						val_list		= { }
						node_str		= None

				val_list[ val_name ]	= node_str

				gval_lists.append( val_list )

				val_list		= { }

			elif val_value != '':

				if (val_list_names_len + len(val_name) ) + (val_list_vals_len + len(str(val_value)) ) > METRIC_MAX_VAL_LEN:

					gval_lists.append( val_list )

					val_list		= { }

				val_list[ val_name ]	= val_value

		if len( val_list ) > 0:

			gval_lists.append( val_list )

		str_list	= [ ]

		for val_list in gval_lists:

			my_val_str	= None

			for val_name, val_value in val_list.items():

				if my_val_str:

					my_val_str = my_val_str + ' ' + val_name + '=' + val_value
				else:
					my_val_str = val_name + '=' + val_value

			str_list.append( my_val_str )

		return str_list

def printTime( ):

	"""Print current time/date in human readable format for log/debug"""

	return time.strftime("%a, %d %b %Y %H:%M:%S")

def debug_msg( level, msg ):

	"""Print msg if at or above current debug level"""

        if (DEBUG_LEVEL >= level):
	                sys.stderr.write( msg + '\n' )

def write_pidfile():

	# Write pidfile if PIDFILE exists
	if PIDFILE:

		pid	= os.getpid()

		pidfile	= open(PIDFILE, 'w')

		pidfile.write( str( pid ) )
		pidfile.close()

def main():

	"""Application start"""

	global PBSQuery, PBSError

	if not processArgs( sys.argv[1:] ):

		sys.exit( 1 )

	if BATCH_API == 'pbs':

		try:
			from PBSQuery import PBSQuery, PBSError

		except ImportError:

			debug_msg( 0, "fatal error: BATCH_API set to 'pbs' but python module 'pbs_python' is not installed" )
			sys.exit( 1 )

		gather = PbsDataGatherer()

	elif BATCH_API == 'sge':

		gather = SgeDataGatherer()

	else:
		debug_msg( 0, "fatal error: unknown BATCH_API '" + BATCH_API + "' is not supported" )

		sys.exit( 1 )

	if DAEMONIZE:

		gather.daemon()
	else:
		gather.run()

# wh00t? someone started me! :)
#
if __name__ == '__main__':
	main()
