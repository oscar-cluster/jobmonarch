#!/usr/bin/env python
#
# This file is part of Jobmonarch
#
# Copyright (C) 2006-2007  Ramon Bastiaans
# Copyright (C) 2007  Dave Love  (SGE code)
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

import sys, getopt, ConfigParser, time, os, socket, string, re
import xdrlib, socket, syslog, xml, xml.sax
from xml.sax.handler import feature_namespaces

VERSION='0.3.1'

def usage( ver ):

	print 'jobmond %s' %VERSION

	if ver:
		return 0

	print
	print 'Purpose:'
	print '  The Job Monitoring Daemon (jobmond) reports batch jobs information and statistics'
	print '  to Ganglia, which can be viewed with Job Monarch web frontend'
	print
	print 'Usage:	jobmond [OPTIONS]'
	print
	print '  -c, --config=FILE	The configuration file to use (default: /etc/jobmond.conf)'
	print '  -p, --pidfile=FILE	Use pid file to store the process id'
	print '  -h, --help		Print help and exit'
	print '  -v, --version          Print version and exit'
	print

def processArgs( args ):

	SHORT_L		= 'p:hvc:'
	LONG_L		= [ 'help', 'config=', 'pidfile=', 'version' ]

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
 
			usage( False )
			sys.exit( 0 )

		if opt in [ '--version', '-v' ]:

			usage( True )
			sys.exit( 0 )

	return loadConfig( config_filename )

class GangliaConfigParser:

	def __init__( self, config_file ):

		self.config_file	= config_file

		if not os.path.exists( self.config_file ):

			debug_msg( 0, "FATAL ERROR: gmond config '" + self.config_file + "' not found!" )
			sys.exit( 1 )

	def removeQuotes( self, value ):

		clean_value	= value
		clean_value	= clean_value.replace( "'", "" )
		clean_value	= clean_value.replace( '"', '' )
		clean_value	= clean_value.strip()

		return clean_value

	def getVal( self, section, valname ):

		cfg_fp		= open( self.config_file )
		section_start	= False
		section_found	= False
		value		= None

		for line in cfg_fp.readlines():

			if line.find( section ) != -1:

				section_found	= True

			if line.find( '{' ) != -1 and section_found:

				section_start	= True

			if line.find( '}' ) != -1 and section_found:

				section_start	= False
				section_found	= False

			if line.find( valname ) != -1 and section_start:

				value 		= string.join( line.split( '=' )[1:], '' ).strip()

		cfg_fp.close()

		return value

	def getInt( self, section, valname ):

		value	= self.getVal( section, valname )

		if not value:
			return False

		value	= self.removeQuotes( value )

		return int( value )

	def getStr( self, section, valname ):

		value	= self.getVal( section, valname )

		if not value:
			return False

		value	= self.removeQuotes( value )

		return str( value )

def findGmetric():

	for dir in os.path.expandvars( '$PATH' ).split( ':' ):

		guess	= '%s/%s' %( dir, 'gmetric' )

		if os.path.exists( guess ):

			return guess

	return False

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
	global BATCH_API, QUEUE, GMETRIC_TARGET, USE_SYSLOG
	global SYSLOG_LEVEL, SYSLOG_FACILITY, GMETRIC_BINARY

	DEBUG_LEVEL	= cfg.getint( 'DEFAULT', 'DEBUG_LEVEL' )

	DAEMONIZE	= cfg.getboolean( 'DEFAULT', 'DAEMONIZE' )

	SYSLOG_LEVEL	= -1
	SYSLOG_FACILITY	= None

	try:
		USE_SYSLOG	= cfg.getboolean( 'DEFAULT', 'USE_SYSLOG' )

	except ConfigParser.NoOptionError:

		USE_SYSLOG	= True

		debug_msg( 0, 'ERROR: no option USE_SYSLOG found: assuming yes' )

	if USE_SYSLOG:

		try:
			SYSLOG_LEVEL	= cfg.getint( 'DEFAULT', 'SYSLOG_LEVEL' )

		except ConfigParser.NoOptionError:

			debug_msg( 0, 'ERROR: no option SYSLOG_LEVEL found: assuming level 0' )
			SYSLOG_LEVEL	= 0

		try:

			SYSLOG_FACILITY = eval( 'syslog.LOG_' + cfg.get( 'DEFAULT', 'SYSLOG_FACILITY' ) )

		except ConfigParser.NoOptionError:

			SYSLOG_FACILITY = syslog.LOG_DAEMON

			debug_msg( 0, 'ERROR: no option SYSLOG_FACILITY found: assuming facility DAEMON' )

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

		# Not specified: assume /etc/gmond.conf
		#
		GMOND_CONF		= '/etc/gmond.conf'

	ganglia_cfg		= GangliaConfigParser( GMOND_CONF )

	# Let's try to find the GMETRIC_TARGET ourselves first from GMOND_CONF
	#
	gmetric_dest_ip		= ganglia_cfg.getStr( 'udp_send_channel', 'mcast_join' )

	if not gmetric_dest_ip:

		# Maybe unicast target then
		#
		gmetric_dest_ip		= ganglia_cfg.getStr( 'udp_send_channel', 'host' )

	gmetric_dest_port	= ganglia_cfg.getStr( 'udp_send_channel', 'port' )

	if gmetric_dest_ip and gmetric_dest_port:

		GMETRIC_TARGET	= '%s:%s' %( gmetric_dest_ip, gmetric_dest_port )
	else:

		debug_msg( 0, "WARNING: Can't parse udp_send_channel from: '%s'" %GMOND_CONF )

		# Couldn't figure it out: let's see if it's in our jobmond.conf
		#
		try:

			GMETRIC_TARGET	= cfg.get( 'DEFAULT', 'GMETRIC_TARGET' )

		# Guess not: now just give up
		#
		except ConfigParser.NoOptionError:

			GMETRIC_TARGET	= None

			debug_msg( 0, "ERROR: GMETRIC_TARGET not set: internal Gmetric handling aborted. Failing back to DEPRECATED use of gmond.conf/gmetric binary. This will slow down jobmond significantly!" )

	gmetric_bin	= findGmetric()

	if gmetric_bin:

		GMETRIC_BINARY		= gmetric_bin
	else:
		debug_msg( 0, "WARNING: Can't find gmetric binary anywhere in $PATH" )

		try:

			GMETRIC_BINARY		= cfg.get( 'DEFAULT', 'GMETRIC_BINARY' )

		except ConfigParser.NoOptionError:

			debug_msg( 0, "FATAL ERROR: GMETRIC_BINARY not set and not in $PATH" )
			sys.exit( 1 )

	DETECT_TIME_DIFFS	= cfg.getboolean( 'DEFAULT', 'DETECT_TIME_DIFFS' )

	BATCH_HOST_TRANSLATE	= getlist( cfg.get( 'DEFAULT', 'BATCH_HOST_TRANSLATE' ) )

	try:

		BATCH_API	= cfg.get( 'DEFAULT', 'BATCH_API' )

	except ConfigParser.NoOptionError, detail:

		if BATCH_SERVER and api_guess:

			BATCH_API	= api_guess
		else:
			debug_msg( 0, "FATAL ERROR: BATCH_API not set and can't make guess" )
			sys.exit( 1 )

	try:

		QUEUE		= getlist( cfg.get( 'DEFAULT', 'QUEUE' ) )

	except ConfigParser.NoOptionError, detail:

		QUEUE		= None

	return True

def fqdn_parts (fqdn):

	"""Return pair of host and domain for fully-qualified domain name arg."""

	parts = fqdn.split (".")

	return (parts[0], string.join(parts[1:], "."))

METRIC_MAX_VAL_LEN = 900

class DataProcessor:

	"""Class for processing of data"""

	binary = None

	def __init__( self, binary=None ):

		"""Remember alternate binary location if supplied"""

		global GMETRIC_BINARY

		if binary:
			self.binary = binary

		if not self.binary:
			self.binary = GMETRIC_BINARY

		# Timeout for XML
		#
		# From ganglia's documentation:
		#
		# 'A metric will be deleted DMAX seconds after it is received, and
	        # DMAX=0 means eternal life.'

		self.dmax = str( int( int( BATCH_POLL_INTERVAL ) * 2 ) )

		if GMOND_CONF:

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

						# Gmetric 3.0.1 >< 3.0.3 had a bug in the max metric length
						#
						if version_patch < 3:

							METRIC_MAX_VAL_LEN = 900

						elif version_patch >= 3:

							METRIC_MAX_VAL_LEN = 1400

		return incompatible

	def multicastGmetric( self, metricname, metricval, valtype='string', units='' ):

		"""Call gmetric binary and multicast"""

		cmd = self.binary

		if GMETRIC_TARGET:

			GMETRIC_TARGET_HOST	= GMETRIC_TARGET.split( ':' )[0]
			GMETRIC_TARGET_PORT	= GMETRIC_TARGET.split( ':' )[1]

			metric_debug		= "[gmetric] name: %s - val: %s - dmax: %s" %( str( metricname ), str( metricval ), str( self.dmax ) )

			debug_msg( 10, printTime() + ' ' + metric_debug)

			gm = Gmetric( GMETRIC_TARGET_HOST, GMETRIC_TARGET_PORT )

			gm.send( str( metricname ), str( metricval ), str( self.dmax ), valtype, units )

		else:
			try:
				cmd = cmd + ' -c' + GMOND_CONF

			except NameError:

				debug_msg( 10, 'Assuming /etc/gmond.conf for gmetric cmd (omitting)' )

			cmd = cmd + ' -n' + str( metricname )+ ' -v"' + str( metricval )+ '" -t' + str( valtype ) + ' -d' + str( self.dmax )

			if len( units ) > 0:

				cmd = cmd + ' -u"' + units + '"'

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

	def submitJobData( self ):

		"""Submit job info list"""

		global BATCH_API

		self.dp.multicastGmetric( 'MONARCH-HEARTBEAT', str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )

		running_jobs	= 0
		queued_jobs	= 0

		# Count how many running/queued jobs we found
                #
		for jobid, jobattrs in self.jobs.items():

			if jobattrs[ 'status' ] == 'Q':

				queued_jobs += 1

			elif jobattrs[ 'status' ] == 'R':

				running_jobs += 1

		# Report running/queued jobs as seperate metric for a nice RRD graph
                #
		self.dp.multicastGmetric( 'MONARCH-RJ', str( running_jobs ), 'uint32', 'jobs' )
		self.dp.multicastGmetric( 'MONARCH-QJ', str( queued_jobs ), 'uint32', 'jobs' )

		# Report down/offline nodes in batch (PBS only ATM)
		#
		if BATCH_API == 'pbs':

			domain		= fqdn_parts( socket.getfqdn() )[1]

			downed_nodes	= list()
			offline_nodes	= list()
		
			l		= ['state']
		
			for name, node in self.pq.getnodes().items():

				if ( node[ 'state' ].find( "down" ) != -1 ):

					downed_nodes.append( name )

				if ( node[ 'state' ].find( "offline" ) != -1 ):

					offline_nodes.append( name )

			downnodeslist		= do_nodelist( downed_nodes )
			offlinenodeslist	= do_nodelist( offline_nodes )

			down_str	= 'nodes=%s domain=%s reported=%s' %( string.join( downnodeslist, ';' ), domain, str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )
			offl_str	= 'nodes=%s domain=%s reported=%s' %( string.join( offlinenodeslist, ';' ), domain, str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )
			self.dp.multicastGmetric( 'MONARCH-DOWN'   , down_str )
			self.dp.multicastGmetric( 'MONARCH-OFFLINE', offl_str )

		# Now let's spread the knowledge
		#
		for jobid, jobattrs in self.jobs.items():

			# Make gmetric values for each job: respect max gmetric value length
                        #
			gmetric_val		= self.compileGmetricVal( jobid, jobattrs )
			metric_increment	= 0

			# If we have more job info than max gmetric value length allows, split it up
                        # amongst multiple metrics
			#
			for val in gmetric_val:

				self.dp.multicastGmetric( 'MONARCH-JOB-' + jobid + '-' + str(metric_increment), val )

				# Increase follow number if this jobinfo is split up amongst more than 1 gmetric
                                #
				metric_increment	= metric_increment + 1

	def compileGmetricVal( self, jobid, jobattrs ):

		"""Create a val string for gmetric of jobinfo"""

		gval_lists	= [ ]
		val_list	= { }

		for val_name, val_value in jobattrs.items():

			# These are our own metric names, i.e.: status, start_timestamp, etc
                        #
			val_list_names_len	= len( string.join( val_list.keys() ) ) + len(val_list.keys())

			# These are their corresponding values
                        #
			val_list_vals_len	= len( string.join( val_list.values() ) ) + len(val_list.values())

			if val_name == 'nodes' and jobattrs['status'] == 'R':

				node_str = None

				for node in val_value:

					if node_str:

						node_str = node_str + ';' + node
					else:
						node_str = node

					# Make sure if we add this new info, that the total metric's value length does not exceed METRIC_MAX_VAL_LEN
                                        #
					if (val_list_names_len + len(val_name) ) + (val_list_vals_len + len(node_str) ) > METRIC_MAX_VAL_LEN:

						# It's too big, we need to make a new gmetric for the additional info
                                                #
						val_list[ val_name ]	= node_str

						gval_lists.append( val_list )

						val_list		= { }
						node_str		= None

				val_list[ val_name ]	= node_str

				gval_lists.append( val_list )

				val_list		= { }

			elif val_value != '':

				# Make sure if we add this new info, that the total metric's value length does not exceed METRIC_MAX_VAL_LEN
                                #
				if (val_list_names_len + len(val_name) ) + (val_list_vals_len + len(str(val_value)) ) > METRIC_MAX_VAL_LEN:

					# It's too big, we need to make a new gmetric for the additional info
                                        #
					gval_lists.append( val_list )

					val_list		= { }

				val_list[ val_name ]	= val_value

		if len( val_list ) > 0:

			gval_lists.append( val_list )

		str_list	= [ ]

		# Now append the value names and values together, i.e.: stop_timestamp=value, etc
                #
		for val_list in gval_lists:

			my_val_str	= None

			for val_name, val_value in val_list.items():

				if my_val_str:

					my_val_str = my_val_str + ' ' + val_name + '=' + val_value
				else:
					my_val_str = val_name + '=' + val_value

			str_list.append( my_val_str )

		return str_list

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

# SGE code by Dave Love <fx@gnu.org>.  Tested with SGE 6.0u8 and 6.0u11.
# Probably needs modification for SGE 6.1.  See also the fixmes.

class NoJobs (Exception):
	"""Exception raised by empty job list in qstat output."""
	pass

class SgeQstatXMLParser(xml.sax.handler.ContentHandler):
	"""SAX handler for XML output from Sun Grid Engine's `qstat'."""

	def __init__(self):
		self.value = ""
		self.joblist = []
		self.job = {}
		self.queue = ""
		self.in_joblist = False
		self.lrequest = False
		xml.sax.handler.ContentHandler.__init__(self)

	# The structure of the output is as follows.  Unfortunately
	# it's voluminous, and probably doesn't scale to large
	# clusters/queues.

	# <detailed_job_info  xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	#   <djob_info>
	#     <qmaster_response>  <!-- job -->
	#       ...
	#       <JB_ja_template>  
	#         <ulong_sublist>
	#         ...             <!-- start_time, state ... -->
	#         </ulong_sublist>
	#       </JB_ja_template>  
	#       <JB_ja_tasks>
	#         <ulong_sublist>
	#           ...           <!-- task info
	#         </ulong_sublist>
	#         ...
	#       </JB_ja_tasks>
	#       ...
	#     </qmaster_response>
	#   </djob_info>
	#   <messages>
	#   ...

	# NB.  We might treat each task as a separate job, like
	# straight qstat output, but the web interface expects jobs to
	# be identified by integers, not, say, <job number>.<task>.

	# So, I lied.  If the job list is empty, we get invalid XML
	# like this, which we need to defend against:

	# <unknown_jobs  xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	#   <>
	#     <ST_name>*</ST_name>
	#   </>
	# </unknown_jobs>

	def startElement(self, name, attrs):
		self.value = ""
		if name == "djob_info":	# job list
			self.in_joblist = True
		elif name == "qmaster_response" and self.in_joblist: # job
			self.job = {"job_state": "U", "slots": 0,
				    "nodes": [], "queued_timestamp": "",
				    "queued_timestamp": "", "queue": "",
				    "ppn": "0", "RN_max": 0,
				    # fixme in endElement
				    "requested_memory": 0, "requested_time": 0
				    }
			self.joblist.append(self.job)
		elif name == "qstat_l_requests": # resource request
			self.lrequest = True
		elif name == "unknown_jobs":
			raise NoJobs

	def characters(self, ch):
		self.value += ch

	def endElement(self, name): 
		"""Snarf job elements contents into job dictionary.
		   Translate keys if appropriate."""

		name_trans = {
		  "JB_job_number": "number",
		  "JB_job_name": "name", "JB_owner": "owner",
		  "queue_name": "queue", "JAT_start_time": "start_timestamp",
		  "JB_submission_time": "queued_timestamp"
		  }
		value = self.value

		if name == "djob_info":
			self.in_joblist = False
			self.job = {}
		elif name == "JAT_master_queue":
			self.job["queue"] = value.split("@")[0]
		elif name == "JG_qhostname":
			if not (value in self.job["nodes"]):
				self.job["nodes"].append(value)
		elif name == "JG_slots": # slots in use
			self.job["slots"] += int(value)
		elif name == "RN_max": # requested slots (tasks or parallel)
			self.job["RN_max"] = max (self.job["RN_max"],
						  int(value))
		elif name == "JAT_state": # job state (bitwise or)
			value = int (value)
			# Status values from sge_jobL.h
			#define JIDLE                   0x00000000
			#define JHELD                   0x00000010
			#define JMIGRATING              0x00000020
			#define JQUEUED                 0x00000040
			#define JRUNNING                0x00000080
			#define JSUSPENDED              0x00000100
			#define JTRANSFERING            0x00000200
			#define JDELETED                0x00000400
			#define JWAITING                0x00000800
			#define JEXITING                0x00001000
			#define JWRITTEN                0x00002000
			#define JSUSPENDED_ON_THRESHOLD 0x00010000
			#define JFINISHED               0x00010000
			if value & 0x80:
				self.job["status"] = "R"
			elif value & 0x40:
				self.job["status"] = "Q"
			else:
				self.job["status"] = "O" # `other'
		elif name == "CE_name" and self.lrequest and self.value in \
			    ("h_cpu", "s_cpu", "cpu", "h_core", "s_core"):
			# We're in a container for an interesting resource
			# request; record which type.
			self.lrequest = self.value
		elif name == "CE_doubleval" and self.lrequest:
			# if we're in a container for an interesting
			# resource request, use the maxmimum of the hard
			# and soft requests to record the requested CPU
			# or core.  Fixme:  I'm not sure if this logic is
			# right.
			if self.lrequest in ("h_core", "s_core"):
				self.job["requested_memory"] = \
				    max (float (value),
					 self.job["requested_memory"])
			# Fixme:  Check what cpu means, c.f [hs]_cpu.
			elif self.lrequest in ("h_cpu", "s_cpu", "cpu"):
				self.job["requested_time"] = \
				    max (float (value),
					 self.job["requested_time"])
		elif name == "qstat_l_requests":
			self.lrequest = False
		elif self.job and self.in_joblist:
			if name in name_trans:
				name = name_trans[name]
				self.job[name] = value

# Abstracted from PBS original.
# Fixme:  Is it worth (or appropriate for PBS) sorting the result?
#
def do_nodelist( nodes ):

	"""Translate node list as appropriate."""

	nodeslist		= [ ]
	my_domain		= fqdn_parts( socket.getfqdn() )[1]

	for node in nodes:

		host		= node.split( '/' )[0] # not relevant for SGE
		h, host_domain	= fqdn_parts(host)

		if host_domain == my_domain:

			host	= h

		if nodeslist.count( host ) == 0:

			for translate_pattern in BATCH_HOST_TRANSLATE:

				if translate_pattern.find( '/' ) != -1:

					translate_orig	= \
					    translate_pattern.split( '/' )[1]
					translate_new	= \
					    translate_pattern.split( '/' )[2]
					host = re.sub( translate_orig,
						       translate_new, host )
			if not host in nodeslist:
				nodeslist.append( host )
	return nodeslist

class SgeDataGatherer(DataGatherer):

	jobs = {}

	def __init__( self ):
		self.jobs = {}
		self.timeoffset = 0
		self.dp = DataProcessor()

	def getJobData( self ):
		"""Gather all data on current jobs in SGE"""

		import popen2

		self.cur_time = 0
		queues = ""
		if QUEUE:	# only for specific queues
			# Fixme:  assumes queue names don't contain single
			# quote or comma.  Don't know what the SGE rules are.
			queues = " -q '" + string.join (QUEUE, ",") + "'"
		# Note the comment in SgeQstatXMLParser about scaling with
		# this method of getting data.  I haven't found better one.
		# Output with args `-xml -ext -f -r' is easier to parse
		# in some ways, harder in others, but it doesn't provide
		# the submission time, at least.
		piping = popen2.Popen3("qstat -u '*' -j '*' -xml" + queues,
				       True)
		qstatparser = SgeQstatXMLParser()
		parse_err = 0
		try:
			xml.sax.parse(piping.fromchild, qstatparser)
		except NoJobs:
			pass
		except:
			parse_err = 1
	       	if piping.wait():
			debug_msg(10,
				  "qstat error, skipping until next polling interval: "
				  + piping.childerr.readline())
			return None
		elif parse_err:
			debug_msg(10, "Bad XML output from qstat"())
			exit (1)
		for f in piping.fromchild, piping.tochild, piping.childerr:
			f.close()
		self.cur_time = time.time()
		jobs_processed = []
		for job in qstatparser.joblist:
			job_id = job["number"]
			if job["status"] in [ 'Q', 'R' ]:
				jobs_processed.append(job_id)
			if job["status"] == "R":
				job["nodes"] = do_nodelist (job["nodes"])
				# Fixme: Is this right?
				job["ppn"] = float(job["slots"]) / \
				    len(job["nodes"])
				if DETECT_TIME_DIFFS:
					# If a job start is later than our
					# current date, that must mean
					# the SGE server's time is later
					# than our local time.
					start_timestamp = \
					    int (job["start_timestamp"])
					if start_timestamp > \
						    int(self.cur_time) + \
						    int(self.timeoffset):

						self.timeoffset	= \
						    start_timestamp - \
						    int(self.cur_time)
			else:
				# fixme: Note sure what this should be:
				job["ppn"] = job["RN_max"]
				job["nodes"] = "1"

			myAttrs = {}
			for attr in ["name", "queue", "owner",
				     "requested_time", "status",
				     "requested_memory", "ppn",
				     "start_timestamp", "queued_timestamp"]:
				myAttrs[attr] = str(job[attr])
			myAttrs["nodes"] = job["nodes"]
			myAttrs["reported"] = str(int(self.cur_time) + \
						  int(self.timeoffset))
			myAttrs["domain"] = fqdn_parts(socket.getfqdn())[1]
			myAttrs["poll_interval"] = str(BATCH_POLL_INTERVAL)

			if self.jobDataChanged(self.jobs, job_id, myAttrs) \
				    and myAttrs["status"] in ["R", "Q"]:
				self.jobs[job_id] = myAttrs
		for id, attrs in self.jobs.items():
			if id not in jobs_processed:
				del self.jobs[id]

# LSF code by Mahmoud Hanafi <hanafim@users.sourceforge.nt>
# Requres LSFObject http://sourceforge.net/projects/lsfobject
#
class LsfDataGatherer(DataGatherer):
        """This is the DataGatherer for LSf"""

        global lsfObject

        def __init__( self ):
                self.jobs = { }
                self.timeoffset = 0
                self.dp = DataProcessor()
                self.initLsfQuery()

########################
## THIS IS TAKEN FROM
## http://bigbadcode.com/2007/04/04/count-the-duplicates-in-a-python-list/
        from sets import Set
#
        def _countDuplicatesInList(self,dupedList):
                uniqueSet = self.Set(item for item in dupedList)
                return [(item, dupedList.count(item)) for item in uniqueSet]
#
#lst = ['I1','I2','I1','I3','I4','I4','I7','I7','I7','I7','I7']
#print _countDuplicatesInList(lst)
#[('I1', 2), ('I3', 1), ('I2', 1), ('I4', 2), ('I7', 5)]
########################

        def initLsfQuery( self ):
                self.pq = None
                self.pq = lsfObject.jobInfoEntObject()

        def getAttr( self, attrs, name ):
                """Return certain attribute from dictionary, if exists"""
                if attrs.has_key( name ):
                        return attrs[name]
                else:
                        return ''

        def getJobData( self, known_jobs="" ):
                """Gather all data on current jobs in LSF"""
                if len( known_jobs ) > 0:
                        jobs = known_jobs
                else:
                        jobs = { }
                joblist = {}
                joblist = self.pq.getJobInfo()
                nodelist = ''

                self.cur_time = time.time()

                jobs_processed = [ ]

                for name, attrs in joblist.items():
                        job_id = str(name)
                        jobs_processed.append( job_id )
                        name = self.getAttr( attrs, 'jobName' )
                        queue = self.getAttr( self.getAttr( attrs, 'submit') , 'queue' )
                        owner = self.getAttr( attrs, 'user' )

### THIS IS THE rLimit List index values
#define LSF_RLIMIT_CPU      0            /* cpu time in milliseconds */
#define LSF_RLIMIT_FSIZE    1            /* maximum file size */
#define LSF_RLIMIT_DATA     2            /* data size */
#define LSF_RLIMIT_STACK    3            /* stack size */
#define LSF_RLIMIT_CORE     4            /* core file size */
#define LSF_RLIMIT_RSS      5            /* resident set size */
#define LSF_RLIMIT_NOFILE   6            /* open files */
#define LSF_RLIMIT_OPEN_MAX 7            /* (from HP-UX) */
#define LSF_RLIMIT_VMEM     8            /* maximum swap mem */
#define LSF_RLIMIT_SWAP     8
#define LSF_RLIMIT_RUN      9            /* max wall-clock time limit */
#define LSF_RLIMIT_PROCESS  10           /* process number limit */
#define LSF_RLIMIT_THREAD   11           /* thread number limit (introduced in LSF6.0) */
#define LSF_RLIM_NLIMITS    12           /* number of resource limits */

                        requested_time = self.getAttr( self.getAttr( attrs, 'submit') , 'rLimits' )[9]
                        if requested_time == -1: 
                                requested_time = ""
                        requested_memory = self.getAttr( self.getAttr( attrs, 'submit') , 'rLimits' )[8]
                        if requested_memory == -1: 
                                requested_memory = ""
# This tries to get proc per node. We don't support this right now
                        ppn = 0 #self.getAttr( self.getAttr( attrs, 'SubmitList') , 'numProessors' )
                        requested_cpus = self.getAttr( self.getAttr( attrs, 'submit') , 'numProcessors' )
                        if requested_cpus == None or requested_cpus == "":
                                requested_cpus = 1

			if QUEUE:
				for q in QUEUE:
					if q == queue:
						display_queue = 1
						break
					else:
						display_queue = 0
						continue
			if display_queue == 0:
				continue

                        runState = self.getAttr( attrs, 'status' )
                        if runState == 4:
                                status = 'R'
                        else:
                                status = 'Q'
                        queued_timestamp = self.getAttr( attrs, 'submitTime' )

                        if status == 'R':
                                start_timestamp = self.getAttr( attrs, 'startTime' )
                                nodesCpu =  dict(self._countDuplicatesInList(self.getAttr( attrs, 'exHosts' )))
                                nodelist = nodesCpu.keys()

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
                                nodelist = ''

                        myAttrs = { }
                        if name == "":
                                myAttrs['name'] = "none"
                        else:
                                myAttrs['name'] = name

                        myAttrs[ 'owner' ]		= owner
                        myAttrs[ 'requested_time' ]	= str(requested_time)
                        myAttrs[ 'requested_memory' ]	= str(requested_memory)
                        myAttrs[ 'requested_cpus' ]	= str(requested_cpus)
                        myAttrs[ 'ppn' ]		= str( ppn )
                        myAttrs[ 'status' ]		= status
                        myAttrs[ 'start_timestamp' ]	= str(start_timestamp)
                        myAttrs[ 'queue' ]		= str(queue)
                        myAttrs[ 'queued_timestamp' ]	= str(queued_timestamp)
                        myAttrs[ 'reported' ]		= str( int( int( self.cur_time ) + int( self.timeoffset ) ) )
                        myAttrs[ 'nodes' ]		= do_nodelist( nodelist )
			myAttrs[ 'domain' ]		= fqdn_parts( socket.getfqdn() )[1]
                        myAttrs[ 'poll_interval' ]	= str(BATCH_POLL_INTERVAL)

                        if self.jobDataChanged( jobs, job_id, myAttrs ) and myAttrs['status'] in [ 'R', 'Q' ]:
                                jobs[ job_id ] = myAttrs

                                #debug_msg( 10, printTime() + ' job %s state changed' %(job_id) )

                for id, attrs in jobs.items():
                        if id not in jobs_processed:
                                # This one isn't there anymore; toedeledoki!
                                #
                                del jobs[ id ]
                self.jobs=jobs


class PbsDataGatherer( DataGatherer ):

	"""This is the DataGatherer for PBS and Torque"""

	global PBSQuery, PBSError

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
			display_queue		= 1
			job_id			= name.split( '.' )[0]

			name			= self.getAttr( attrs, 'Job_Name' )
			queue			= self.getAttr( attrs, 'queue' )

			if QUEUE:
				for q in QUEUE:
					if q == queue:
						display_queue = 1
						break
					else:
						display_queue = 0
						continue
			if display_queue == 0:
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

			if status in [ 'Q', 'R' ]:

				jobs_processed.append( job_id )

			queued_timestamp	= self.getAttr( attrs, 'ctime' )

			if status == 'R':

				start_timestamp		= self.getAttr( attrs, 'mtime' )
				nodes			= self.getAttr( attrs, 'exec_host' ).split( '+' )

				nodeslist		= do_nodelist( nodes )

				if DETECT_TIME_DIFFS:

					# If a job start if later than our current date,
					# that must mean the Torque server's time is later
					# than our local time.
				
					if int( start_timestamp ) > int( int( self.cur_time ) + int( self.timeoffset ) ):

						self.timeoffset	= int( int(start_timestamp) - int(self.cur_time) )

			elif status == 'Q':

				# 'mynodequest' can be a string in the following syntax according to the
				# Torque Administator's manual:
				# 
				# {<node_count> | <hostname>}[:ppn=<ppn>][:<property>[:<property>]...][+ ...]
				# {<node_count> | <hostname>}[:ppn=<ppn>][:<property>[:<property>]...][+ ...]
				# etc
				#

				#
				# For now we only count the amount of nodes request and ignore properties
				#

				start_timestamp		= ''
				count_mynodes		= 0

				for node in mynoderequest.split( '+' ):

					# Just grab the {node_count|hostname} part and ignore properties
					#
					nodepart	= node.split( ':' )[0]

					# Let's assume a node_count value
					#
					numeric_node	= 1

					# Chop the value up into characters
					#
					for letter in nodepart:

						# If this char is not a digit (0-9), this must be a hostname
						#
						if letter not in string.digits:

							numeric_node	= 0

					# If this is a hostname, just count this as one (1) node
					#
					if not numeric_node:

						count_mynodes	= count_mynodes + 1
					else:

						# If this a number, it must be the node_count
						# and increase our count with it's value
						#
						try:
							count_mynodes	= count_mynodes + int( nodepart )

						except ValueError, detail:

							# When we arrive here I must be bugged or very confused
							# THIS SHOULD NOT HAPPEN!
							#
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

			myAttrs[ 'name' ]		= str( name )
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
			myAttrs[ 'domain' ]		= fqdn_parts( socket.getfqdn() )[1]
			myAttrs[ 'poll_interval' ]	= str( BATCH_POLL_INTERVAL )

			if self.jobDataChanged( self.jobs, job_id, myAttrs ) and myAttrs['status'] in [ 'R', 'Q' ]:

				self.jobs[ job_id ]	= myAttrs

		for id, attrs in self.jobs.items():

			if id not in jobs_processed:

				# This one isn't there anymore; toedeledoki!
				#
				del self.jobs[ id ]

#
# Gmetric by Nick Galbreath - nickg(a.t)modp(d.o.t)com
# Version 1.0 - 21-April2-2007
# http://code.google.com/p/embeddedgmetric/
#
# Modified by: Ramon Bastiaans
# For the Job Monarch Project, see: https://subtrac.sara.nl/oss/jobmonarch/
#
# added: DEFAULT_TYPE for Gmetric's
# added: checkHostProtocol to determine if target is multicast or not
# changed: allow default for Gmetric constructor
# changed: allow defaults for all send() values except dmax
#

GMETRIC_DEFAULT_TYPE    = 'string'
GMETRIC_DEFAULT_HOST    = '127.0.0.1'
GMETRIC_DEFAULT_PORT    = '8649'
GMETRIC_DEFAULT_UNITS	= ''

class Gmetric:

	global GMETRIC_DEFAULT_HOST, GMETRIC_DEFAULT_PORT

	slope           = { 'zero' : 0, 'positive' : 1, 'negative' : 2, 'both' : 3, 'unspecified' : 4 }
	type            = ( '', 'string', 'uint16', 'int16', 'uint32', 'int32', 'float', 'double', 'timestamp' )
	protocol        = ( 'udp', 'multicast' )

	def __init__( self, host=GMETRIC_DEFAULT_HOST, port=GMETRIC_DEFAULT_PORT ):
                
		global GMETRIC_DEFAULT_TYPE

		self.prot       = self.checkHostProtocol( host )
		self.msg        = xdrlib.Packer()
		self.socket     = socket.socket( socket.AF_INET, socket.SOCK_DGRAM )

		if self.prot not in self.protocol:

			raise ValueError( "Protocol must be one of: " + str( self.protocol ) )

		if self.prot == 'multicast':

			# Set multicast options
			#
			self.socket.setsockopt( socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 20 )

		self.hostport   = ( host, int( port ) )
		self.slopestr   = 'both'
		self.tmax       = 60

	def checkHostProtocol( self, ip ):

		"""Detect if a ip adress is a multicast address"""

		MULTICAST_ADDRESS_MIN   = ( "224", "0", "0", "0" )
		MULTICAST_ADDRESS_MAX   = ( "239", "255", "255", "255" )

		ip_fields               = ip.split( '.' )

		if ip_fields >= MULTICAST_ADDRESS_MIN and ip_fields <= MULTICAST_ADDRESS_MAX:

			return 'multicast'
		else:
			return 'udp'

	def send( self, name, value, dmax, typestr = '', units = '' ):

		if len( units ) == 0:
			units		= GMETRIC_DEFAULT_UNITS

		if len( typestr ) == 0:
			typestr		= GMETRIC_DEFAULT_TYPE

		msg             = self.makexdr( name, value, typestr, units, self.slopestr, self.tmax, dmax )

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

def printTime( ):

	"""Print current time/date in human readable format for log/debug"""

	return time.strftime("%a, %d %b %Y %H:%M:%S")

def debug_msg( level, msg ):

	"""Print msg if at or above current debug level"""

	global DAEMONIZE, DEBUG_LEVEL, SYSLOG_LEVEL

        if (not DAEMONIZE and DEBUG_LEVEL >= level):
		sys.stderr.write( msg + '\n' )

	if (DAEMONIZE and USE_SYSLOG and SYSLOG_LEVEL >= level):
		syslog.syslog( msg )

def write_pidfile():

	# Write pidfile if PIDFILE is set
	#
	if PIDFILE:

		pid	= os.getpid()

		pidfile	= open( PIDFILE, 'w' )

		pidfile.write( str( pid ) )
		pidfile.close()

def main():

	"""Application start"""

	global PBSQuery, PBSError, lsfObject
	global SYSLOG_FACILITY, USE_SYSLOG, BATCH_API, DAEMONIZE

	if not processArgs( sys.argv[1:] ):

		sys.exit( 1 )

	# Load appropriate DataGatherer depending on which BATCH_API is set
	# and any required modules for the Gatherer
	#
	if BATCH_API == 'pbs':

		try:
			from PBSQuery import PBSQuery, PBSError

		except ImportError:

			debug_msg( 0, "FATAL ERROR: BATCH_API set to 'pbs' but python module 'pbs_python' is not installed" )
			sys.exit( 1 )

		gather = PbsDataGatherer()

	elif BATCH_API == 'sge':

		# Tested with SGE 6.0u11.
		#
		gather = SgeDataGatherer()

	elif BATCH_API == 'lsf':

		try:
			from lsfObject import lsfObject
		except:
			debug_msg(0, "fatal error: BATCH_API set to 'lsf' but python module is not found or installed")
			sys.exit( 1)

		gather = LsfDataGatherer()

	else:
		debug_msg( 0, "FATAL ERROR: unknown BATCH_API '" + BATCH_API + "' is not supported" )

		sys.exit( 1 )

	if( DAEMONIZE and USE_SYSLOG ):

		syslog.openlog( 'jobmond', syslog.LOG_NOWAIT, SYSLOG_FACILITY )

	if DAEMONIZE:

		gather.daemon()
	else:
		gather.run()

# wh00t? someone started me! :)
#
if __name__ == '__main__':
	main()
