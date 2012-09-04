#!/usr/bin/env python
#
# This file is part of Jobmonarch
#
# Copyright (C) 2006-2012  Ramon Bastiaans
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
from types import *

VERSION='TRUNK+SVN'

def usage( ver ):

    print 'jobmond %s' %VERSION

    if ver:
        return 0

    print
    print 'Purpose:'
    print '  The Job Monitoring Daemon (jobmond) reports batch jobs information and statistics'
    print '  to Ganglia, which can be viewed with Job Monarch web frontend'
    print
    print 'Usage:   jobmond [OPTIONS]'
    print
    print '  -c, --config=FILE  The configuration file to use (default: /etc/jobmond.conf)'
    print '  -p, --pidfile=FILE Use pid file to store the process id'
    print '  -h, --help     Print help and exit'
    print '  -v, --version          Print version and exit'
    print

def processArgs( args ):

    SHORT_L     = 'p:hvc:'
    LONG_L      = [ 'help', 'config=', 'pidfile=', 'version' ]

    global PIDFILE
    PIDFILE     = None

    config_filename = '/etc/jobmond.conf'

    try:

        opts, args  = getopt.getopt( args, SHORT_L, LONG_L )

    except getopt.GetoptError, detail:

        print detail
        usage()
        sys.exit( 1 )

    for opt, value in opts:

        if opt in [ '--config', '-c' ]:
        
            config_filename = value

        if opt in [ '--pidfile', '-p' ]:

            PIDFILE     = value
        
        if opt in [ '--help', '-h' ]:
 
            usage( False )
            sys.exit( 0 )

        if opt in [ '--version', '-v' ]:

            usage( True )
            sys.exit( 0 )

    return loadConfig( config_filename )

# Fixme:  This doesn't DTRT with commented-out bits of the file.  E.g.
# it picked up a commented-out `mcast_join' and tried to use a
# multicast channel when it shouldn't have done.
class GangliaConfigParser:

    def __init__( self, config_file ):

        self.config_file    = config_file

        if not os.path.exists( self.config_file ):

            debug_msg( 0, "FATAL ERROR: gmond config '" + self.config_file + "' not found!" )
            sys.exit( 1 )

    def removeQuotes( self, value ):

        clean_value = value
        clean_value = clean_value.replace( "'", "" )
        clean_value = clean_value.replace( '"', '' )
        clean_value = clean_value.strip()

        return clean_value

    def getVal( self, section, valname ):

        cfg_fp      = open( self.config_file )
        section_start   = False
        section_found   = False
        value       = None

        for line in cfg_fp.readlines():

            if line.find( section ) != -1:

                section_found   = True

            if line.find( '{' ) != -1 and section_found:

                section_start   = True

            if line.find( '}' ) != -1 and section_found:

                section_start   = False
                section_found   = False

            if line.find( valname ) != -1 and section_start:

                value       = string.join( line.split( '=' )[1:], '' ).strip()

        cfg_fp.close()

        return value

    def getInt( self, section, valname ):

        value   = self.getVal( section, valname )

        if not value:
            return False

        value   = self.removeQuotes( value )

        return int( value )

    def getStr( self, section, valname ):

        value   = self.getVal( section, valname )

        if not value:
            return False

        value   = self.removeQuotes( value )

        return str( value )

def findGmetric():

    for dir in os.path.expandvars( '$PATH' ).split( ':' ):

        guess   = '%s/%s' %( dir, 'gmetric' )

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

    cfg     = ConfigParser.ConfigParser()

    cfg.read( filename )

    global DEBUG_LEVEL, DAEMONIZE, BATCH_SERVER, BATCH_POLL_INTERVAL
    global GMOND_CONF, DETECT_TIME_DIFFS, BATCH_HOST_TRANSLATE
    global BATCH_API, QUEUE, GMETRIC_TARGET, USE_SYSLOG
    global SYSLOG_LEVEL, SYSLOG_FACILITY, GMETRIC_BINARY

    DEBUG_LEVEL = cfg.getint( 'DEFAULT', 'DEBUG_LEVEL' )

    DAEMONIZE   = cfg.getboolean( 'DEFAULT', 'DAEMONIZE' )

    SYSLOG_LEVEL    = -1
    SYSLOG_FACILITY = None

    try:
        USE_SYSLOG  = cfg.getboolean( 'DEFAULT', 'USE_SYSLOG' )

    except ConfigParser.NoOptionError:

        USE_SYSLOG  = True

        debug_msg( 0, 'ERROR: no option USE_SYSLOG found: assuming yes' )

    if USE_SYSLOG:

        try:
            SYSLOG_LEVEL    = cfg.getint( 'DEFAULT', 'SYSLOG_LEVEL' )

        except ConfigParser.NoOptionError:

            debug_msg( 0, 'ERROR: no option SYSLOG_LEVEL found: assuming level 0' )
            SYSLOG_LEVEL    = 0

        try:

            SYSLOG_FACILITY = eval( 'syslog.LOG_' + cfg.get( 'DEFAULT', 'SYSLOG_FACILITY' ) )

        except ConfigParser.NoOptionError:

            SYSLOG_FACILITY = syslog.LOG_DAEMON

            debug_msg( 0, 'ERROR: no option SYSLOG_FACILITY found: assuming facility DAEMON' )

    try:

        BATCH_SERVER        = cfg.get( 'DEFAULT', 'BATCH_SERVER' )

    except ConfigParser.NoOptionError:

        # Backwards compatibility for old configs
        #

        BATCH_SERVER        = cfg.get( 'DEFAULT', 'TORQUE_SERVER' )
        api_guess       = 'pbs'
    
    try:
    
        BATCH_POLL_INTERVAL = cfg.getint( 'DEFAULT', 'BATCH_POLL_INTERVAL' )

    except ConfigParser.NoOptionError:

        # Backwards compatibility for old configs
        #

        BATCH_POLL_INTERVAL = cfg.getint( 'DEFAULT', 'TORQUE_POLL_INTERVAL' )
        api_guess       = 'pbs'
    
    try:

        GMOND_CONF      = cfg.get( 'DEFAULT', 'GMOND_CONF' )

    except ConfigParser.NoOptionError:

        # Not specified: assume /etc/ganglia/gmond.conf
        #
        GMOND_CONF      = '/etc/ganglia/gmond.conf'

    ganglia_cfg     = GangliaConfigParser( GMOND_CONF )

    # Let's try to find the GMETRIC_TARGET ourselves first from GMOND_CONF
    #
    gmetric_dest_ip     = ganglia_cfg.getStr( 'udp_send_channel', 'mcast_join' )

    if not gmetric_dest_ip:

        # Maybe unicast target then
        #
        gmetric_dest_ip     = ganglia_cfg.getStr( 'udp_send_channel', 'host' )

        gmetric_dest_port   = ganglia_cfg.getStr( 'udp_send_channel', 'port' )

    if gmetric_dest_ip and gmetric_dest_port:

        GMETRIC_TARGET  = '%s:%s' %( gmetric_dest_ip, gmetric_dest_port )
    else:

        debug_msg( 0, "WARNING: Can't parse udp_send_channel from: '%s'" %GMOND_CONF )

        # Couldn't figure it out: let's see if it's in our jobmond.conf
        #
        try:

            GMETRIC_TARGET  = cfg.get( 'DEFAULT', 'GMETRIC_TARGET' )

        # Guess not: now just give up
        #
        except ConfigParser.NoOptionError:

            GMETRIC_TARGET  = None

            debug_msg( 0, "ERROR: GMETRIC_TARGET not set: internal Gmetric handling aborted. Failing back to DEPRECATED use of gmond.conf/gmetric binary. This will slow down jobmond significantly!" )

    gmetric_bin = findGmetric()

    if gmetric_bin:

        GMETRIC_BINARY      = gmetric_bin
    else:
        debug_msg( 0, "WARNING: Can't find gmetric binary anywhere in $PATH" )

        try:

            GMETRIC_BINARY      = cfg.get( 'DEFAULT', 'GMETRIC_BINARY' )

        except ConfigParser.NoOptionError:

            debug_msg( 0, "FATAL ERROR: GMETRIC_BINARY not set and not in $PATH" )
            sys.exit( 1 )

    DETECT_TIME_DIFFS   = cfg.getboolean( 'DEFAULT', 'DETECT_TIME_DIFFS' )

    BATCH_HOST_TRANSLATE    = getlist( cfg.get( 'DEFAULT', 'BATCH_HOST_TRANSLATE' ) )

    try:

        BATCH_API   = cfg.get( 'DEFAULT', 'BATCH_API' )

    except ConfigParser.NoOptionError, detail:

        if BATCH_SERVER and api_guess:

            BATCH_API   = api_guess
        else:
            debug_msg( 0, "FATAL ERROR: BATCH_API not set and can't make guess" )
            sys.exit( 1 )

    try:

        QUEUE       = getlist( cfg.get( 'DEFAULT', 'QUEUE' ) )

    except ConfigParser.NoOptionError, detail:

        QUEUE       = None

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

        incompatible    = 0

        gfp     = os.popen( self.binary + ' --version' )
        lines       = gfp.readlines()

        gfp.close()

        for line in lines:

            line = line.split( ' ' )

            if len( line ) == 2 and str( line ).find( 'gmetric' ) != -1:
            
                gmetric_version = line[1].split( '\n' )[0]

                version_major   = int( gmetric_version.split( '.' )[0] )
                version_minor   = int( gmetric_version.split( '.' )[1] )
                version_patch   = int( gmetric_version.split( '.' )[2] )

                incompatible    = 0

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

            GMETRIC_TARGET_HOST = GMETRIC_TARGET.split( ':' )[0]
            GMETRIC_TARGET_PORT = GMETRIC_TARGET.split( ':' )[1]

            metric_debug        = "[gmetric] name: %s - val: %s - dmax: %s" %( str( metricname ), str( metricval ), str( self.dmax ) )

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

    def getAttr( self, d, name ):

        """Return certain attribute from dictionary, if exists"""

        if d.has_key( name ):

            if type( d[ name ] ) == ListType:

                return string.join( d[ name ], ' ' )

            return d[ name ]
        
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

        running_jobs    = 0
        queued_jobs = 0

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

            domain      = fqdn_parts( socket.getfqdn() )[1]

            downed_nodes    = list()
            offline_nodes   = list()
        
            l       = ['state']
        
            for name, node in self.pq.getnodes().items():

                if 'down' in node[ 'state' ]:

                    downed_nodes.append( name )

                if 'offline' in node[ 'state' ]:

                    offline_nodes.append( name )

            downnodeslist       = do_nodelist( downed_nodes )
            offlinenodeslist    = do_nodelist( offline_nodes )

            down_str    = 'nodes=%s domain=%s reported=%s' %( string.join( downnodeslist, ';' ), domain, str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )
            offl_str    = 'nodes=%s domain=%s reported=%s' %( string.join( offlinenodeslist, ';' ), domain, str( int( int( self.cur_time ) + int( self.timeoffset ) ) ) )
            self.dp.multicastGmetric( 'MONARCH-DOWN'   , down_str )
            self.dp.multicastGmetric( 'MONARCH-OFFLINE', offl_str )

        # Now let's spread the knowledge
        #
        for jobid, jobattrs in self.jobs.items():

            # Make gmetric values for each job: respect max gmetric value length
            #
            gmetrics     = self.compileGmetricVal( jobid, jobattrs )

            for g_name, g_val in gmetrics.items():

                self.dp.multicastGmetric( g_name, g_val )

    def compileGmetricVal( self, jobid, jobattrs ):

        """Create gmetric name/value pairs of jobinfo"""

        gmetrics = { }

        for val_name, val_value in jobattrs.items():

            gmetric_sequence = 0

            if len( val_value ) > METRIC_MAX_VAL_LEN:

                while len( val_value ) > METRIC_MAX_VAL_LEN:

                    gmetric_value   = val_value[:METRIC_MAX_VAL_LEN]
                    val_value       = val_value[METRIC_MAX_VAL_LEN:]

                    gmetric_name    = 'MONARCHJOB$%s$%s$%s' %( jobid, string.upper(val_name), gmetric_sequence )

                    gmetrics[ gmetric_name ] = gmetric_value

                    gmetric_sequence = gmetric_sequence + 1
            else:
                gmetric_value   = val_value

                gmetric_name    = 'MONARCH$%s$%s$%s' %( jobid, string.upper(val_name), gmetric_sequence )

                gmetrics[ gmetric_name ] = gmetric_value

        return gmetrics

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

# Abstracted from PBS original.
#
def do_nodelist( nodes ):

    """Translate node list as appropriate."""

    nodeslist       = [ ]
    my_domain       = fqdn_parts( socket.getfqdn() )[1]

    for node in nodes:

        host        = node.split( '/' )[0] # not relevant for SGE
        h, host_domain  = fqdn_parts(host)

        if host_domain == my_domain:

            host    = h

        if nodeslist.count( host ) == 0:

            for translate_pattern in BATCH_HOST_TRANSLATE:

                if translate_pattern.find( '/' ) != -1:

                    translate_orig  = \
                        translate_pattern.split( '/' )[1]
                    translate_new   = \
                        translate_pattern.split( '/' )[2]
                    host = re.sub( translate_orig,
                               translate_new, host )
            if not host in nodeslist:
                nodeslist.append( host )
    return nodeslist

class PbsDataGatherer( DataGatherer ):

    """This is the DataGatherer for PBS and Torque"""

    global PBSQuery, PBSError

    def __init__( self ):

        """Setup appropriate variables"""

        self.jobs   = { }
        self.timeoffset = 0
        self.dp     = DataProcessor()

        self.initPbsQuery()

    def initPbsQuery( self ):

        self.pq     = None

        if( BATCH_SERVER ):

            self.pq     = PBSQuery( BATCH_SERVER )
        else:
            self.pq     = PBSQuery()

    def getJobData( self ):

        """Gather all data on current jobs in Torque"""

        joblist     = {}
        self.cur_time   = 0

        try:
            joblist     = self.pq.getjobs()
            self.cur_time   = time.time()

        except PBSError, detail:

            debug_msg( 10, "Caught PBS unavailable, skipping until next polling interval: " + str( detail ) )
            return None

        jobs_processed  = [ ]

        for name, attrs in joblist.items():
            display_queue       = 1
            job_id          = name.split( '.' )[0]

            owner           = self.getAttr( attrs, 'Job_Owner' )
            name            = self.getAttr( attrs, 'Job_Name' )
            queue           = self.getAttr( attrs, 'queue' )
            nodect          = self.getAttr( attrs['Resource_List'], 'nodect' )

            requested_time      = self.getAttr( attrs['Resource_List'], 'walltime' )
            requested_memory    = self.getAttr( attrs['Resource_List'], 'mem' )


            requested_nodes     = ''
            mynoderequest       = self.getAttr( attrs['Resource_List'], 'nodes' )

            ppn         = ''
            attributes  = ''

            if mynoderequest.find( ':' ) != -1:

                mynoderequest_fields    = mynoderequest.split( ':' )

                for mynoderequest_field in mynoderequest_fields:

                    if mynoderequest_field.isdigit():

                        continue #TODO add requested_nodes if is hostname(s)

                    if mynoderequest_field.find( 'ppn' ) != -1:

                        ppn = mynoderequest_field.split( 'ppn=' )[1]

                    else:

                        if attributes == '':

                            attributes = '%s' %mynoderequest_field
                        else:
                            attributes = '%s:%s' %( attributes, mynoderequest_field )

            status          = self.getAttr( attrs, 'job_state' )

            if status in [ 'Q', 'R', 'W' ]:

                jobs_processed.append( job_id )

            create_timestamp    = self.getAttr( attrs, 'ctime' )
            running_nodes       = ''
            exec_nodestr        = '' 

            if status == 'R':

                #start_timestamp     = self.getAttr( attrs, 'etime' )
                start_timestamp     = self.getAttr( attrs, 'start_time' )
                exec_nodestr        = self.getAttr( attrs, 'exec_host' )

                nodes           = exec_nodestr.split( '+' )
                nodeslist       = do_nodelist( nodes )
                running_nodes   = string.join( nodeslist, ' ' )

                if DETECT_TIME_DIFFS:

                    # If a job start if later than our current date,
                    # that must mean the Torque server's time is later
                    # than our local time.
                
                    if int( start_timestamp ) > int( int( self.cur_time ) + int( self.timeoffset ) ):

                        self.timeoffset = int( int(start_timestamp) - int(self.cur_time) )

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

                start_timestamp     = ''
                count_mynodes       = 0

                queued_timestamp    = self.getAttr( attrs, 'qtime' )

                for node in mynoderequest.split( '+' ):

                    # Just grab the {node_count|hostname} part and ignore properties
                    #
                    nodepart    = node.split( ':' )[0]

                    # Let's assume a node_count value
                    #
                    numeric_node    = 1

                    # Chop the value up into characters
                    #
                    for letter in nodepart:

                        # If this char is not a digit (0-9), this must be a hostname
                        #
                        if letter not in string.digits:

                            numeric_node    = 0

                    # If this is a hostname, just count this as one (1) node
                    #
                    if not numeric_node:

                        count_mynodes   = count_mynodes + 1
                    else:

                        # If this a number, it must be the node_count
                        # and increase our count with it's value
                        #
                        try:
                            count_mynodes   = count_mynodes + int( nodepart )

                        except ValueError, detail:

                            # When we arrive here I must be bugged or very confused
                            # THIS SHOULD NOT HAPPEN!
                            #
                            debug_msg( 10, str( detail ) )
                            debug_msg( 10, "Encountered weird node in Resources_List?!" )
                            debug_msg( 10, 'nodepart = ' + str( nodepart ) )
                            debug_msg( 10, 'job = ' + str( name ) )
                            debug_msg( 10, 'attrs = ' + str( attrs ) )
                        
                nodeslist   = str( count_mynodes )
            else:
                start_timestamp = ''
                nodeslist   = ''

            myAttrs             = { }

            myAttrs[ 'name' ]              = str( name )
            myAttrs[ 'status' ]            = str( status )
            myAttrs[ 'queue' ]             = str( queue )
            myAttrs[ 'owner' ]             = str( owner )
            myAttrs[ 'nodect' ]            = str( nodect )
            myAttrs[ 'exec.hostnames' ]    = str( running_nodes )
            myAttrs[ 'exec.nodestr' ]      = str( exec_nodestr )
            myAttrs[ 'req.walltime' ]      = str( requested_time )
            myAttrs[ 'req.memory' ]        = str( requested_memory )
            myAttrs[ 'req.nodes' ]         = str( requested_nodes )
            myAttrs[ 'req.ppn' ]           = str( ppn )
            myAttrs[ 'req.attributes' ]    = str( attributes )
            myAttrs[ 'timestamp.running' ] = str( start_timestamp )
            myAttrs[ 'timestamp.created' ] = str( create_timestamp )
            myAttrs[ 'timestamp.queued' ]  = str( queued_timestamp )

            if self.jobDataChanged( self.jobs, job_id, myAttrs ) and myAttrs['status'] in [ 'R', 'Q', 'W' ]:

                self.jobs[ job_id ] = myAttrs

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
GMETRIC_DEFAULT_UNITS   = ''

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
            units       = GMETRIC_DEFAULT_UNITS

        if len( typestr ) == 0:
            typestr     = GMETRIC_DEFAULT_TYPE

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

        pid = os.getpid()

        pidfile = open( PIDFILE, 'w' )

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
