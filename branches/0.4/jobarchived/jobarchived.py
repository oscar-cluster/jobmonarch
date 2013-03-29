#!/usr/bin/env python
#
# This file is part of Jobmonarch
#
# Copyright (C) 2006-2013  Ramon Bastiaans
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

import getopt, syslog, ConfigParser, sys

VERSION='0.4+SVN'

def usage( ver ):

    print 'jobarchived %s' %VERSION

    if ver:
        return 0

    print
    print 'Purpose:'
    print '  The Job Archive Daemon (jobarchived) stores batch job information in a SQL database'
    print '  and node statistics in a RRD archive'
    print
    print 'Usage:    jobarchived [OPTIONS]'
    print
    print '  -c, --config=FILE    The configuration file to use (default: /etc/jobarchived.conf)'
    print '  -p, --pidfile=FILE    Use pid file to store the process id'
    print '  -h, --help        Print help and exit'
    print '  -v, --version        Print version and exit'
    print

def processArgs( args ):

    SHORT_L    = 'p:hvc:'
    LONG_L    = [ 'help', 'config=', 'pidfile=', 'version' ]

    config_filename = '/etc/jobarchived.conf'

    global PIDFILE

    PIDFILE    = None

    try:

        opts, args = getopt.getopt( args, SHORT_L, LONG_L )

    except getopt.error, detail:

        print detail
        sys.exit(1)

    for opt, value in opts:

        if opt in [ '--config', '-c' ]:

            config_filename = value

        if opt in [ '--pidfile', '-p' ]:

            PIDFILE         = value

        if opt in [ '--help', '-h' ]:

            usage( False )
            sys.exit( 0 )

        if opt in [ '--version', '-v' ]:

            usage( True )
            sys.exit( 0 )

    try:
        return loadConfig( config_filename )

    except ConfigParser.NoOptionError, detail:

        print detail
        sys.exit( 1 )

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

    global DEBUG_LEVEL, USE_SYSLOG, SYSLOG_LEVEL, SYSLOG_FACILITY, GMETAD_CONF, ARCHIVE_XMLSOURCE
    global ARCHIVE_DATASOURCES, ARCHIVE_PATH, ARCHIVE_HOURS_PER_RRD, ARCHIVE_EXCLUDE_METRICS
    global JOB_SQL_DBASE, DAEMONIZE, RRDTOOL, JOB_TIMEOUT, MODRRDTOOL, JOB_SQL_PASSWORD, JOB_SQL_USER

    ARCHIVE_PATH        = cfg.get( 'DEFAULT', 'ARCHIVE_PATH' )

    ARCHIVE_HOURS_PER_RRD    = cfg.getint( 'DEFAULT', 'ARCHIVE_HOURS_PER_RRD' )

    DEBUG_LEVEL        = cfg.getint( 'DEFAULT', 'DEBUG_LEVEL' )

    USE_SYSLOG        = cfg.getboolean( 'DEFAULT', 'USE_SYSLOG' )

    SYSLOG_LEVEL        = cfg.getint( 'DEFAULT', 'SYSLOG_LEVEL' )

    MODRRDTOOL        = False

    try:
        global rrdtool
        import rrdtool

        MODRRDTOOL        = True

    except ImportError:

        MODRRDTOOL        = False

        print "ERROR: py-rrdtool import FAILED: failing back to DEPRECATED use of rrdtool binary. This will slow down jobarchived significantly!"

        RRDTOOL            = cfg.get( 'DEFAULT', 'RRDTOOL' )

    try:

        SYSLOG_FACILITY    = eval( 'syslog.LOG_' + cfg.get( 'DEFAULT', 'SYSLOG_FACILITY' ) )

    except AttributeError, detail:

        print 'Unknown syslog facility'
        sys.exit( 1 )

    GMETAD_CONF        = cfg.get( 'DEFAULT', 'GMETAD_CONF' )

    ARCHIVE_XMLSOURCE    = cfg.get( 'DEFAULT', 'ARCHIVE_XMLSOURCE' )

    ARCHIVE_DATASOURCES    = getlist( cfg.get( 'DEFAULT', 'ARCHIVE_DATASOURCES' ) )

    ARCHIVE_EXCLUDE_METRICS    = getlist( cfg.get( 'DEFAULT', 'ARCHIVE_EXCLUDE_METRICS' ) )

    JOB_SQL_DBASE        = cfg.get( 'DEFAULT', 'JOB_SQL_DBASE' )
    JOB_SQL_USER        = cfg.get( 'DEFAULT', 'JOB_SQL_USER' )
    JOB_SQL_PASSWORD        = cfg.get( 'DEFAULT', 'JOB_SQL_PASSWORD' )

    JOB_TIMEOUT        = cfg.getint( 'DEFAULT', 'JOB_TIMEOUT' )

    DAEMONIZE        = cfg.getboolean( 'DEFAULT', 'DAEMONIZE' )


    return True

# What XML data types not to store
#
UNSUPPORTED_ARCHIVE_TYPES = [ 'string' ]

# Maximum time (in seconds) a parsethread may run
#
PARSE_TIMEOUT = 60

# Maximum time (in seconds) a storethread may run
#
STORE_TIMEOUT = 360

"""
The Job Archiving Daemon
"""

from types import *

import xml.sax, xml.sax.handler, socket, string, os, os.path, time, thread, threading, random, re

try:
    import psycopg2

except ImportError, details:

    print "FATAL ERROR: psycopg2 python module not found"
    sys.exit( 1 )

class InitVars:
        Vars = {}
        
        def __init__(self, **key_arg):
                for (key, value) in key_arg.items():
                        if value:
                                self.Vars[key] = value
                        else:   
                                self.Vars[key] = None
                                
        def __call__(self, *key):
                key = "%s" % key
                return self.Vars[key]
                
        def __getitem__(self, key):
                return self.Vars[key]
                
        def __repr__(self):
                return repr(self.Vars)
                
        def keys(self):
                barf =  map(None, self.Vars.keys())
                return barf
                
        def values(self):
                barf =  map(None, self.Vars.values())
                return barf
                
        def has_key(self, key):
                if self.Vars.has_key(key):
                        return 1
                else:   
                        return 0
                        
class DBError(Exception):
        def __init__(self, msg=''):
                self.msg = msg
                Exception.__init__(self, msg)
        def __repr__(self):
                return self.msg
        __str__ = __repr__

#
# Class to connect to a database
# and return the queury in a list or dictionairy.
#
class DB:
    def __init__(self, db_vars):

        self.dict = db_vars

        if self.dict.has_key('User'):
            self.user = self.dict['User']
        else:
            self.user = 'postgres'

        if self.dict.has_key('Host'):
            self.host = self.dict['Host']
        else:
            self.host = 'localhost'

        if self.dict.has_key('Password'):
            self.passwd = self.dict['Password']
        else:
            self.passwd = ''

        if self.dict.has_key('DataBaseName'):
            self.db = self.dict['DataBaseName']
        else:
            self.db = 'jobarchive'

        # connect_string = 'host:port:database:user:password:
        dsn = "host='%s' dbname='%s' user='%s' password='%s'" %(self.host, self.db, self.user, self.passwd)

        try:
            self.SQL = psycopg2.connect(dsn)
        except psycopg2.Error, details:
            str = "%s" %details
            raise DBError(str)

    def __repr__(self):
        return repr(self.result)

    def __nonzero__(self):
        return not(self.result == None)

    def __len__(self):
        return len(self.result)

    def __getitem__(self,i):
        return self.result[i]

    def __getslice__(self,i,j):
        return self.result[i:j]

    def Get(self, q_str):
        c = self.SQL.cursor()
        try:
            c.execute(q_str)
            result = c.fetchall()
        except psycopg2.Error, details:
            c.close()
            str = "%s" %details
            raise DBError(str)

        c.close()
        return result

    def Set(self, q_str):
        c = self.SQL.cursor()
        try:
            c.execute(q_str)

        except psycopg2.Error, details:
            c.close()
            str = "%s" %details
            raise DBError(str)

        c.close()
        return True

    def Commit(self):
        self.SQL.commit()

class DataSQLStore:

    db_vars = None
    dbc = None

    def __init__( self, hostname, database ):

        global JOB_SQL_USER, JOB_SQL_PASSWORD

        self.db_vars = InitVars(DataBaseName=database,
                User=JOB_SQL_USER,
                Host=hostname,
                Password=JOB_SQL_PASSWORD,
                Dictionary='true')

        try:
            self.dbc     = DB(self.db_vars)
        except DBError, details:
            debug_msg( 0, 'FATAL ERROR: Unable to connect to database!: ' +str(details) )
            sys.exit(1)

    def setDatabase(self, statement):
        ret = self.doDatabase('set', statement)
        return ret
        
    def getDatabase(self, statement):
        ret = self.doDatabase('get', statement)
        return ret

    def doDatabase(self, type, statement):

        debug_msg( 10, 'doDatabase(): %s: %s' %(type, statement) )
        try:
            if type == 'set':
                result = self.dbc.Set( statement )
                self.dbc.Commit()
            elif type == 'get':
                result = self.dbc.Get( statement )
                
        except DBError, detail:
            operation = statement.split(' ')[0]
            debug_msg( 0, 'FATAL ERROR: ' +operation+ ' on database failed while doing ['+statement+'] full msg: '+str(detail) )
            sys.exit(1)

        debug_msg( 10, 'doDatabase(): result: %s' %(result) )
        return result

    def getJobNodeId( self, job_id, node_id ):

        id = self.getDatabase( "SELECT job_id,node_id FROM job_nodes WHERE job_id = '%s' AND node_id = '%s'" %(job_id, node_id) )
        if len( id ) > 0:

            if len( id[0] ) > 0 and id[0] != '':
            
                return 1

        return 0

    def getNodeId( self, hostname ):

        id = self.getDatabase( "SELECT node_id FROM nodes WHERE node_hostname = '%s'" %hostname )

        if len( id ) > 0:

            id = id[0][0]

            return id
        else:
            return None

    def getNodeIds( self, hostnames ):

        ids = [ ]

        for node in hostnames:

            id = self.getNodeId( node )

            if id:
                ids.append( id )

        return ids

    def getJobId( self, jobid ):

        id = self.getDatabase( "SELECT job_id FROM jobs WHERE job_id = '%s'" %jobid )

        if id:
            id = id[0][0]

            return id
        else:
            return None

    def addJob( self, job_id, jobattrs ):

        if not self.getJobId( job_id ):

            self.mutateJob( 'insert', job_id, jobattrs ) 
        else:
            self.mutateJob( 'update', job_id, jobattrs )

    def mutateJob( self, action, job_id, jobattrs ):

        job_values    = [ 'name', 'queue', 'owner', 'requested_time', 'requested_memory', 'ppn', 'status', 'start_timestamp', 'stop_timestamp' ]

        insert_col_str    = 'job_id'
        insert_val_str    = "'%s'" %job_id
        update_str    = None

        debug_msg( 10, 'mutateJob(): %s %s' %(action,job_id))

        ids = [ ]

        for valname, value in jobattrs.items():

            if valname in job_values and value != '':

                column_name = 'job_' + valname

                if action == 'insert':

                    if not insert_col_str:
                        insert_col_str = column_name
                    else:
                        insert_col_str = insert_col_str + ',' + column_name

                    if not insert_val_str:
                        insert_val_str = value
                    else:
                        insert_val_str = insert_val_str + ",'%s'" %value

                elif action == 'update':
                    
                    if not update_str:
                        update_str = "%s='%s'" %(column_name, value)
                    else:
                        update_str = update_str + ",%s='%s'" %(column_name, value)

            elif valname == 'nodes' and value:

                node_valid = 1

                if len(value) == 1:
                
                    if jobattrs['status'] == 'Q':

                        node_valid = 0

                    else:

                        node_valid = 0

                        for node_char in str(value[0]):

                            if string.find( string.digits, node_char ) != -1 and not node_valid:

                                node_valid = 1

                if node_valid:

                    ids = self.addNodes( value, jobattrs['domain'] )

        if action == 'insert':

            self.setDatabase( "INSERT INTO jobs ( %s ) VALUES ( %s )" %( insert_col_str, insert_val_str ) )

        elif action == 'update':

            self.setDatabase( "UPDATE jobs SET %s WHERE job_id='%s'" %(update_str, job_id) )

        if len( ids ) > 0:
            self.addJobNodes( job_id, ids )

    def addNodes( self, hostnames, domain ):

        ids = [ ]

        for node in hostnames:

            node    = '%s.%s' %( node, domain )
            id    = self.getNodeId( node )
    
            if not id:
                self.setDatabase( "INSERT INTO nodes ( node_hostname ) VALUES ( '%s' )" %node )
                id = self.getNodeId( node )

            ids.append( id )

        return ids

    def addJobNodes( self, jobid, nodes ):

        for node in nodes:

            if not self.getJobNodeId( jobid, node ):

                self.addJobNode( jobid, node )

    def addJobNode( self, jobid, nodeid ):

        self.setDatabase( "INSERT INTO job_nodes (job_id,node_id) VALUES ( '%s',%s )" %(jobid, nodeid) )

    def storeJobInfo( self, jobid, jobattrs ):

        self.addJob( jobid, jobattrs )

    def checkStaleJobs( self ):

        # Locate all jobs in the database that are not set to finished
        #
        q = "SELECT * from jobs WHERE job_status != 'F'"

        r = self.getDatabase( q )

        if len( r ) == 0:

            return None

        cleanjobs    = [ ]
        timeoutjobs    = [ ]

        jobtimeout_sec    = JOB_TIMEOUT * (60 * 60)
        cur_time    = time.time()

        for row in r:

            job_id            = row[0]
            job_requested_time    = row[4]
            job_status        = row[7]
            job_start_timestamp    = row[8]

            # If it was set to queued and we didn't see it started
            # there's not point in keeping it around
            #
            if job_status == 'Q' or not job_start_timestamp:

                cleanjobs.append( job_id )

            else:

                start_timestamp = int( job_start_timestamp )

                # If it was set to running longer than JOB_TIMEOUT 
                # close the job: it probably finished while we were not running
                #
                if ( cur_time - start_timestamp ) > jobtimeout_sec:

                    if job_requested_time:

                        rtime_epoch    = reqtime2epoch( job_requested_time )
                    else:
                        rtime_epoch    = None
                    
                    timeoutjobs.append( (job_id, job_start_timestamp, rtime_epoch) )

        debug_msg( 1, 'Found ' + str( len( cleanjobs ) ) + ' stale jobs in database: deleting entries' )

        # Purge these from database
        #
        for j in cleanjobs:

            q = "DELETE FROM jobs WHERE job_id = '" + str( j ) + "'"
            self.setDatabase( q )

        debug_msg( 1, 'Found ' + str( len( timeoutjobs ) ) + ' timed out jobs in database: closing entries' )

        # Close these jobs in the database
        # update the stop_timestamp to: start_timestamp + requested wallclock
        # and set state: finished
        #
        for j in timeoutjobs:

            ( i, s, r )        = j

            if r:
                new_end_timestamp    = int( s ) + r

            q = "UPDATE jobs SET job_status='F',job_stop_timestamp = '" + str( new_end_timestamp ) + "' WHERE job_id = '" + str(i) + "'"
            self.setDatabase( q )

class RRDMutator:
    """A class for performing RRD mutations"""

    binary = None

    def __init__( self, binary=None ):
        """Set alternate binary if supplied"""

        if binary:
            self.binary = binary

    def create( self, filename, args ):
        """Create a new rrd with args"""

        global MODRRDTOOL

        if MODRRDTOOL:
            return self.perform( 'create', filename, args )
        else:
            return self.perform( 'create', '"' + filename + '"', args )

    def update( self, filename, args ):
        """Update a rrd with args"""

        global MODRRDTOOL

        if MODRRDTOOL:
            return self.perform( 'update', filename, args )
        else:
            return self.perform( 'update', '"' + filename + '"', args )

    def grabLastUpdate( self, filename ):
        """Determine the last update time of filename rrd"""

        global MODRRDTOOL

        last_update = 0

        # Use the py-rrdtool module if it's available on this system
        #
        if MODRRDTOOL:

            debug_msg( 8, 'rrdtool.info( ' + filename + ' )' )

            rrd_header     = { }

            try:
                rrd_header    = rrdtool.info( filename )
            except rrdtool.error, msg:
                debug_msg( 8, str( msg ) )

            if rrd_header.has_key( 'last_update' ):
                return last_update
            else:
                return 0

        # For backwards compatiblity: use the rrdtool binary if py-rrdtool is unavailable
        # DEPRECATED (slow!)
        #
        else:
            debug_msg( 8, self.binary + ' info ' + filename )

            my_pipe        = os.popen( self.binary + ' info "' + filename + '"' )

            for line in my_pipe.readlines():

                if line.find( 'last_update') != -1:

                    last_update = line.split( ' = ' )[1]

            if my_pipe:

                my_pipe.close()

            if last_update:
                return last_update
            else:
                return 0


    def perform( self, action, filename, args ):
        """Perform action on rrd filename with args"""

        global MODRRDTOOL

        arg_string = None

        if type( args ) is not ListType:
            debug_msg( 8, 'Arguments needs to be of type List' )
            return 1

        for arg in args:

            if not arg_string:

                arg_string = arg
            else:
                arg_string = arg_string + ' ' + arg

        if MODRRDTOOL:

            debug_msg( 8, 'rrdtool.' + action + "( " + filename + ' ' + arg_string + ")" )

            try:
                debug_msg( 8, "filename '" + str(filename) + "' type "+ str(type(filename)) + " args " + str( args ) )

                if action == 'create':

                    rrdtool.create( str( filename ), *args )

                elif action == 'update':

                    rrdtool.update( str( filename ), *args )

            except rrdtool.error, msg:

                error_msg = str( msg )
                debug_msg( 8, error_msg )
                return 1

        else:

            debug_msg( 8, self.binary + ' ' + action + ' ' + filename + ' ' + arg_string  )

            cmd     = os.popen( self.binary + ' ' + action + ' ' + filename + ' ' + arg_string )
            lines   = cmd.readlines()

            cmd.close()

            for line in lines:

                if line.find( 'ERROR' ) != -1:

                    error_msg = string.join( line.split( ' ' )[1:] )
                    debug_msg( 8, error_msg )
                    return 1

        return 0

class XMLProcessor:
    """Skeleton class for XML processor's"""

    def run( self ):
        """Do main processing of XML here"""

        pass

class TorqueXMLProcessor( XMLProcessor ):
    """Main class for processing XML and acting with it"""

    def __init__( self, XMLSource, DataStore ):
        """Setup initial XML connection and handlers"""

        self.myXMLSource    = XMLSource
        self.myXMLHandler    = TorqueXMLHandler( DataStore )
        self.myXMLError        = XMLErrorHandler()

        self.config        = GangliaConfigParser( GMETAD_CONF )

    def run( self ):
        """Main XML processing"""

        debug_msg( 1, 'torque_xml_thread(): started.' )

        while( 1 ):

            #self.myXMLSource = self.mXMLGatherer.getFileObject()
            debug_msg( 1, 'torque_xml_thread(): Retrieving XML data..' )

            my_data    = self.myXMLSource.getData()
            #print my_data
            #print "size my data: %d" %len( my_data )

            debug_msg( 1, 'torque_xml_thread(): Done retrieving.' )

            if my_data:
                debug_msg( 1, 'ganglia_parse_thread(): Parsing XML..' )

                xml.sax.parseString( my_data, self.myXMLHandler, self.myXMLError )

                debug_msg( 1, 'ganglia_parse_thread(): Done parsing.' )
            else:
                debug_msg( 1, 'torque_xml_thread(): Got no data.' )

                
            debug_msg( 1, 'torque_xml_thread(): Sleeping.. (%ss)' %(str( self.config.getLowestInterval() ) ) )
            time.sleep( self.config.getLowestInterval() )

class TorqueXMLHandler( xml.sax.handler.ContentHandler ):
    """Parse Torque's jobinfo XML from our plugin"""

    jobAttrs = { }

    def __init__( self, datastore ):

        #self.ds            = DataSQLStore( JOB_SQL_DBASE.split( '/' )[0], JOB_SQL_DBASE.split( '/' )[1] )
        self.ds            = datastore
        self.jobs_processed    = [ ]
        self.jobs_to_store    = [ ]
        debug_msg( 1, "XML: Handler created" )

    def startDocument( self ):

        self.heartbeat    = 0
        self.elementct    = 0
        debug_msg( 1, "XML: Start document" )

    def startElement( self, name, attrs ):
        """
        This XML will be all gmetric XML
        so there will be no specific start/end element
        just one XML statement with all info
        """
        
        jobinfo = { }

        self.elementct    += 1

        if name == 'CLUSTER':

            self.clustername = str( attrs.get( 'NAME', "" ) )

        elif name == 'METRIC' and self.clustername in ARCHIVE_DATASOURCES:

            metricname = str( attrs.get( 'NAME', "" ) )

            if metricname == 'zplugin_monarch_heartbeat':
                self.heartbeat = str( attrs.get( 'VAL', "" ) )

            elif metricname.find( 'zplugin_monarch_job' ) != -1:

                job_id    = metricname.split( 'zplugin_monarch_job_' )[1].split( '_' )[1]
                val    = str( attrs.get( 'VAL', "" ) )

                if not job_id in self.jobs_processed:

                    self.jobs_processed.append( job_id )

                check_change = 0

                if self.jobAttrs.has_key( job_id ):

                    check_change = 1

                valinfo = val.split( ' ' )

                for myval in valinfo:

                    if len( myval.split( '=' ) ) > 1:

                        valname    = myval.split( '=' )[0]
                        value    = myval.split( '=' )[1]

                        if valname == 'nodes':
                            value = value.split( ';' )

                        jobinfo[ valname ] = value

                if check_change:
                    if self.jobinfoChanged( self.jobAttrs, job_id, jobinfo ) and self.jobAttrs[ job_id ]['status'] in [ 'R', 'Q' ]:
                        self.jobAttrs[ job_id ]['stop_timestamp']    = ''
                        self.jobAttrs[ job_id ]                = self.setJobAttrs( self.jobAttrs[ job_id ], jobinfo )
                        if not job_id in self.jobs_to_store:
                            self.jobs_to_store.append( job_id )

                        debug_msg( 10, 'jobinfo for job %s has changed' %job_id )
                else:
                    self.jobAttrs[ job_id ] = jobinfo

                    if not job_id in self.jobs_to_store:
                        self.jobs_to_store.append( job_id )

                    debug_msg( 10, 'jobinfo for job %s has changed' %job_id )
                    
    def endDocument( self ):
        """When all metrics have gone, check if any jobs have finished"""

        debug_msg( 1, "XML: Processed "+str(self.elementct)+ " elements - found "+str(len(self.jobs_to_store))+" (updated) jobs" )

        if self.heartbeat:
            for jobid, jobinfo in self.jobAttrs.items():

                # This is an old job, not in current jobinfo list anymore
                # it must have finished, since we _did_ get a new heartbeat
                #
                mytime = int( jobinfo['reported'] ) + int( jobinfo['poll_interval'] )

                if (mytime < self.heartbeat) and (jobid not in self.jobs_processed) and (jobinfo['status'] == 'R'):

                    if not jobid in self.jobs_processed:
                        self.jobs_processed.append( jobid )

                    self.jobAttrs[ jobid ]['status'] = 'F'
                    self.jobAttrs[ jobid ]['stop_timestamp'] = str( self.heartbeat )

                    if not jobid in self.jobs_to_store:
                        self.jobs_to_store.append( jobid )

            debug_msg( 1, 'torque_xml_thread(): Storing..' )

            for jobid in self.jobs_to_store:
                if self.jobAttrs[ jobid ]['status'] in [ 'R', 'F' ]:

                    self.ds.storeJobInfo( jobid, self.jobAttrs[ jobid ] )

                    if self.jobAttrs[ jobid ]['status'] == 'F':
                        del self.jobAttrs[ jobid ]

            debug_msg( 1, 'torque_xml_thread(): Done storing.' )

            self.jobs_processed    = [ ]
            self.jobs_to_store    = [ ]

    def setJobAttrs( self, old, new ):
        """
        Set new job attributes in old, but not lose existing fields
        if old attributes doesn't have those
        """

        for valname, value in new.items():
            old[ valname ] = value

        return old
        

    def jobinfoChanged( self, jobattrs, jobid, jobinfo ):
        """
        Check if jobinfo has changed from jobattrs[jobid]
        if it's report time is bigger than previous one
        and it is report time is recent (equal to heartbeat)
        """

        ignore_changes = [ 'reported' ]

        if jobattrs.has_key( jobid ):

            for valname, value in jobinfo.items():

                if valname not in ignore_changes:

                    if jobattrs[ jobid ].has_key( valname ):

                        if value != jobattrs[ jobid ][ valname ]:

                            if jobinfo['reported'] > jobattrs[ jobid ][ 'reported' ] and jobinfo['reported'] == self.heartbeat:
                                return True

                    else:
                        return True

        return False

class GangliaXMLHandler( xml.sax.handler.ContentHandler ):
    """Parse Ganglia's XML"""

    def __init__( self, config, datastore ):
        """Setup initial variables and gather info on existing rrd archive"""

        self.config    = config
        self.clusters    = { }
        self.ds        = datastore

        debug_msg( 1, 'Checking database..' )

        global DEBUG_LEVEL

        if DEBUG_LEVEL <= 2:
            self.ds.checkStaleJobs()

        debug_msg( 1, 'Check done.' )
        debug_msg( 1, 'Checking rrd archive..' )
        self.gatherClusters()
        debug_msg( 1, 'Check done.' )

    def gatherClusters( self ):
        """Find all existing clusters in archive dir"""

        archive_dir    = check_dir(ARCHIVE_PATH)

        hosts        = [ ]

        if os.path.exists( archive_dir ):

            dirlist    = os.listdir( archive_dir )

            for cfgcluster in ARCHIVE_DATASOURCES:

                if cfgcluster not in dirlist:

                    # Autocreate a directory for this cluster
                    # assume it is new
                    #
                    cluster_dir = '%s/%s' %( check_dir(ARCHIVE_PATH), cfgcluster )

                    os.mkdir( cluster_dir )

                    dirlist.append( cfgcluster )

            for item in dirlist:

                clustername = item

                if not self.clusters.has_key( clustername ) and clustername in ARCHIVE_DATASOURCES:

                    self.clusters[ clustername ] = RRDHandler( self.config, clustername )

        debug_msg( 9, "Found "+str(len(self.clusters.keys()))+" clusters" )

    def startElement( self, name, attrs ):
        """Memorize appropriate data from xml start tags"""

        if name == 'GANGLIA_XML':

            self.XMLSource        = str( attrs.get( 'SOURCE', "" ) )
            self.gangliaVersion    = str( attrs.get( 'VERSION', "" ) )

            debug_msg( 10, 'Found XML data: source %s version %s' %( self.XMLSource, self.gangliaVersion ) )

        elif name == 'GRID':

            self.gridName    = str( attrs.get( 'NAME', "" ) )
            self.time    = str( attrs.get( 'LOCALTIME', "" ) )

            debug_msg( 10, '`-Grid found: %s' %( self.gridName ) )

        elif name == 'CLUSTER':

            self.clusterName    = str( attrs.get( 'NAME', "" ) )
            self.time        = str( attrs.get( 'LOCALTIME', "" ) )

            if not self.clusters.has_key( self.clusterName ) and self.clusterName in ARCHIVE_DATASOURCES:

                self.clusters[ self.clusterName ] = RRDHandler( self.config, self.clusterName )

                debug_msg( 10, ' |-Cluster found: %s' %( self.clusterName ) )

        elif name == 'HOST' and self.clusterName in ARCHIVE_DATASOURCES:     

            self.hostName        = str( attrs.get( 'NAME', "" ) )
            self.hostIp        = str( attrs.get( 'IP', "" ) )
            self.hostReported    = str( attrs.get( 'REPORTED', "" ) )

            debug_msg( 10, ' | |-Host found: %s - ip %s reported %s' %( self.hostName, self.hostIp, self.hostReported ) )

        elif name == 'METRIC' and self.clusterName in ARCHIVE_DATASOURCES:

            type = str( attrs.get( 'TYPE', "" ) )
            
            exclude_metric = False
            
            for ex_metricstr in ARCHIVE_EXCLUDE_METRICS:

                orig_name = str( attrs.get( 'NAME', "" ) )

                if string.lower( orig_name ) == string.lower( ex_metricstr ):
                
                    exclude_metric = True

                elif re.match( ex_metricstr, orig_name ):

                    exclude_metric = True

            if type not in UNSUPPORTED_ARCHIVE_TYPES and not exclude_metric:

                myMetric        = { }
                myMetric['name']    = str( attrs.get( 'NAME', "" ) )
                myMetric['val']        = str( attrs.get( 'VAL', "" ) )
                myMetric['time']    = self.hostReported

                self.clusters[ self.clusterName ].memMetric( self.hostName, myMetric )

                debug_msg( 11, ' | | |-metric: %s:%s' %( myMetric['name'], myMetric['val'] ) )

    def storeMetrics( self ):
        """Store metrics of each cluster rrd handler"""

        for clustername, rrdh in self.clusters.items():

            ret = rrdh.storeMetrics()

            if ret:
                debug_msg( 9, 'An error occured while storing metrics for cluster %s' %clustername )
                return 1

        return 0

class XMLErrorHandler( xml.sax.handler.ErrorHandler ):

    def error( self, exception ):
        """Recoverable error"""

        debug_msg( 0, 'Recoverable XML error ' + str( exception ) + ' ignored.' )

    def fatalError( self, exception ):
        """Non-recoverable error"""

        exception_str = str( exception )

        # Ignore 'no element found' errors
        if exception_str.find( 'no element found' ) != -1:
            debug_msg( 0, 'No XML data found: Socket not (re)connected or datasource not available.' )
            return 0

        debug_msg( 0, 'FATAL ERROR: Non-recoverable XML error ' + str( exception ) )
        sys.exit( 1 )

    def warning( self, exception ):
        """Warning"""

        debug_msg( 0, 'Warning ' + str( exception ) )

class XMLGatherer:
    """Setup a connection and file object to Ganglia's XML"""

    s        = None
    fd        = None
    data        = None
    slot        = None

    # Time since the last update
    #
    LAST_UPDATE    = 0

    # Minimum interval between updates
    #
    MIN_UPDATE_INT    = 10

    # Is a update occuring now
    #
    update_now    = False

    def __init__( self, host, port ):
        """Store host and port for connection"""

        self.host    = host
        self.port    = port
        self.slot       = threading.Lock()

        self.retrieveData()

    def retrieveData( self ):
        """Setup connection to XML source"""

        self.update_now    = True

        self.slot.acquire()

        self.data    = None

        for res in socket.getaddrinfo( self.host, self.port, socket.AF_UNSPEC, socket.SOCK_STREAM ):

            af, socktype, proto, canonname, sa = res

            try:

                self.s = socket.socket( af, socktype, proto )

            except ( socket.error, socket.gaierror, socket.herror, socket.timeout ), msg:

                self.s = None
                continue

            try:

                self.s.connect( sa )

            except ( socket.error, socket.gaierror, socket.herror, socket.timeout ), msg:

                self.disconnect()
                continue

            break

        if self.s is None:

            debug_msg( 0, 'FATAL ERROR: Could not open socket or unable to connect to datasource!' )
            self.update_now    = False
            #sys.exit( 1 )

        else:
            #self.s.send( '\n' )

            my_fp            = self.s.makefile( 'r' )
            my_data          = my_fp.readlines()
            my_data          = string.join( my_data, '' )

            self.data        = my_data

            self.LAST_UPDATE = time.time()

        self.slot.release()

        self.update_now    = False

    def disconnect( self ):
        """Close socket"""

        if self.s:
            #self.s.shutdown( 2 )
            self.s.close()
            self.s = None

    def __del__( self ):
        """Kill the socket before we leave"""

        self.disconnect()

    def reGetData( self ):
        """Reconnect"""

        while self.update_now:

            # Must be another update in progress:
            # Wait until the update is complete
            #
            time.sleep( 1 )

        if self.s:
            self.disconnect()

        self.retrieveData()

    def getData( self ):

        """Return the XML data"""

        # If more than MIN_UPDATE_INT seconds passed since last data update
        # update the XML first before returning it
        #

        cur_time    = time.time()

        if ( cur_time - self.LAST_UPDATE ) > self.MIN_UPDATE_INT:

            self.reGetData()

        while self.update_now:

            # Must be another update in progress:
            # Wait until the update is complete
            #
            time.sleep( 1 )
            
        return self.data

    def makeFileDescriptor( self ):
        """Make file descriptor that points to our socket connection"""

        self.reconnect()

        if self.s:
            self.fd = self.s.makefile( 'r' )

    def getFileObject( self ):
        """Connect, and return a file object"""

        self.makeFileDescriptor()

        if self.fd:
            return self.fd

class GangliaXMLProcessor( XMLProcessor ):
    """Main class for processing XML and acting with it"""

    def __init__( self, XMLSource, DataStore ):
        """Setup initial XML connection and handlers"""

        self.config        = GangliaConfigParser( GMETAD_CONF )

        #self.myXMLGatherer    = XMLGatherer( ARCHIVE_XMLSOURCE.split( ':' )[0], ARCHIVE_XMLSOURCE.split( ':' )[1] ) 
        #self.myXMLSource    = self.myXMLGatherer.getFileObject()
        self.myXMLSource    = XMLSource
        self.ds            = DataStore
        self.myXMLHandler    = GangliaXMLHandler( self.config, self.ds )
        self.myXMLError        = XMLErrorHandler()

    def run( self ):
        """Main XML processing; start a xml and storethread"""

        xml_thread = threading.Thread( None, self.processXML, 'xmlthread' )
        store_thread = threading.Thread( None, self.storeMetrics, 'storethread' )

        while( 1 ):

            if not xml_thread.isAlive():
                # Gather XML at the same interval as gmetad

                # threaded call to: self.processXML()
                #
                try:
                    xml_thread = threading.Thread( None, self.processXML, 'xml_thread' )
                    xml_thread.start()
                except thread.error, msg:
                    debug_msg( 0, 'ERROR: Unable to start xml_thread!: '+str(msg))
                    #return 1

            if not store_thread.isAlive():
                # Store metrics every .. sec

                # threaded call to: self.storeMetrics()
                #
                try:
                    store_thread = threading.Thread( None, self.storeMetrics, 'store_thread' )
                    store_thread.start()
                except thread.error, msg:
                    debug_msg( 0, 'ERROR: Unable to start store_thread!: '+str(msg))
                    #return 1
        
            # Just sleep a sec here, to prevent daemon from going mad. We're all threads here anyway
            time.sleep( 1 )    

    def storeMetrics( self ):
        """Store metrics retained in memory to disk"""

        global DEBUG_LEVEL

        # Store metrics somewhere between every 360 and 640 seconds
        #
        if DEBUG_LEVEL >= 1:
            STORE_INTERVAL = 60
            #STORE_INTERVAL = random.randint( 360, 640 )
        else:
            STORE_INTERVAL = random.randint( 360, 640 )

        try:
            store_metric_thread = threading.Thread( None, self.storeThread, 'store_metric_thread' )
            store_metric_thread.start()
        except thread.error, msg:
            debug_msg( 0, 'ERROR: Unable to start ganglia_store_thread()!: '+str(msg) )
            return 1

        debug_msg( 1, 'ganglia_store_thread(): started.' )

        debug_msg( 1, 'ganglia_store_thread(): Sleeping.. (%ss)' %STORE_INTERVAL )
        time.sleep( STORE_INTERVAL )
        debug_msg( 1, 'ganglia_store_thread(): Done sleeping.' )

        if store_metric_thread.isAlive():

            debug_msg( 1, 'ganglia_store_thread(): storemetricthread() still running, waiting to terminate..' )
            store_metric_thread.join( STORE_TIMEOUT ) # Maximum time is for storing thread to finish
            debug_msg( 1, 'ganglia_store_thread(): Done waiting: terminated storemetricthread()' )

        debug_msg( 1, 'ganglia_store_thread(): finished.' )

        return 0

    def storeThread( self ):
        """Actual metric storing thread"""

        debug_msg( 1, 'ganglia_store_metric_thread(): started.' )
        debug_msg( 1, 'ganglia_store_metric_thread(): Storing data..' )
        ret = self.myXMLHandler.storeMetrics()
        if ret > 0:
            debug_msg( 0, 'ganglia_store_metric_thread(): UNKNOWN ERROR %s while storing Metrics!' %str(ret) )
        debug_msg( 1, 'ganglia_store_metric_thread(): Done storing.' )
        debug_msg( 1, 'ganglia_store_metric_thread(): finished.' )
        
        return 0

    def processXML( self ):
        """Process XML"""

        debug_msg( 5, "Entering processXML()")

        try:
            parsethread = threading.Thread( None, self.parseThread, 'parsethread' )
            parsethread.start()
        except thread.error, msg:
            debug_msg( 0, 'ERROR: Unable to start ganglia_xml_thread()!: ' + str(msg) )
            return 1

        debug_msg( 1, 'ganglia_xml_thread(): started.' )

        debug_msg( 1, 'ganglia_xml_thread(): Sleeping.. (%ss)' %self.config.getLowestInterval() )
        time.sleep( float( self.config.getLowestInterval() ) )    
        debug_msg( 1, 'ganglia_xml_thread(): Done sleeping.' )

        if parsethread.isAlive():

            debug_msg( 1, 'ganglia_xml_thread(): parsethread() still running, waiting (%ss) to terminate..' %PARSE_TIMEOUT )
            parsethread.join( PARSE_TIMEOUT ) # Maximum time for XML thread to finish
            debug_msg( 1, 'ganglia_xml_thread(): Done waiting. parsethread() terminated' )

        debug_msg( 1, 'ganglia_xml_thread(): finished.' )

        debug_msg( 5, "Leaving processXML()")

        return 0

    def parseThread( self ):
        """Actual parsing thread"""

        debug_msg( 1, 'ganglia_parse_thread(): started.' )
        debug_msg( 1, 'ganglia_parse_thread(): Retrieving XML data..' )
        
        my_data    = self.myXMLSource.getData()
        debug_msg( 1, 'ganglia_parse_thread(): data size %d.' %len(my_data) )

        debug_msg( 1, 'ganglia_parse_thread(): Done retrieving.' )

        if my_data:
            debug_msg( 1, 'ganglia_parse_thread(): Parsing XML..' )
            xml.sax.parseString( my_data, self.myXMLHandler, self.myXMLError )
            debug_msg( 1, 'ganglia_parse_thread(): Done parsing.' )

        debug_msg( 1, 'ganglia_parse_thread(): finished.' )

        return 0

class GangliaConfigParser:

    sources = [ ]

    def __init__( self, config ):
        """Parse some stuff from our gmetad's config, such as polling interval"""

        self.config = config
        self.parseValues()

    def parseValues( self ):
        """Parse certain values from gmetad.conf"""

        readcfg = open( self.config, 'r' )

        for line in readcfg.readlines():

            if line.count( '"' ) > 1:

                if line.find( 'data_source' ) != -1 and line[0] != '#':

                    source        = { }
                    source['name']    = line.split( '"' )[1]
                    source_words    = line.split( '"' )[2].split( ' ' )

                    for word in source_words:

                        valid_interval = 1

                        for letter in word:

                            if letter not in string.digits:

                                valid_interval = 0

                        if valid_interval and len(word) > 0:

                            source['interval'] = word
                            debug_msg( 9, 'polling interval for %s = %s' %(source['name'], source['interval'] ) )
    
                    # No interval found, use Ganglia's default    
                    if not source.has_key( 'interval' ):
                        source['interval'] = 15
                        debug_msg( 9, 'polling interval for %s defaulted to 15' %(source['name']) )

                    self.sources.append( source )

    def getInterval( self, source_name ):
        """Return interval for source_name"""

        for source in self.sources:

            if source['name'] == source_name:

                return source['interval']

        return None

    def getLowestInterval( self ):
        """Return the lowest interval of all clusters"""

        lowest_interval = 0

        for source in self.sources:

            if not lowest_interval or source['interval'] <= lowest_interval:

                lowest_interval = source['interval']

        # Return 15 when nothing is found, so that the daemon won't go insane with 0 sec delays
        if lowest_interval:
            return lowest_interval
        else:
            return 15

class RRDHandler:
    """Class for handling RRD activity"""

    myMetrics = { }
    lastStored = { }
    timeserials = { }
    slot = None

    def __init__( self, config, cluster ):
        """Setup initial variables"""

        global MODRRDTOOL

        self.block    = 0
        self.cluster    = cluster
        self.config    = config
        self.slot    = threading.Lock()

        if MODRRDTOOL:

            self.rrdm    = RRDMutator()
        else:
            self.rrdm    = RRDMutator( RRDTOOL )

        global DEBUG_LEVEL

        if DEBUG_LEVEL <= 2:
            self.gatherLastUpdates()

    def gatherLastUpdates( self ):
        """Populate the lastStored list, containing timestamps of all last updates"""

        cluster_dir = '%s/%s' %( check_dir(ARCHIVE_PATH), self.cluster )

        hosts = [ ]

        if os.path.exists( cluster_dir ):

            dirlist = os.listdir( cluster_dir )

            for dir in dirlist:

                hosts.append( dir )

        for host in hosts:

            host_dir    = cluster_dir + '/' + host
            dirlist        = os.listdir( host_dir )

            for dir in dirlist:

                if not self.timeserials.has_key( host ):

                    self.timeserials[ host ] = [ ]

                self.timeserials[ host ].append( dir )

            last_serial = self.getLastRrdTimeSerial( host )

            if last_serial:

                metric_dir = cluster_dir + '/' + host + '/' + last_serial

                if os.path.exists( metric_dir ):

                    dirlist = os.listdir( metric_dir )

                    for file in dirlist:

                        metricname = file.split( '.rrd' )[0]

                        if not self.lastStored.has_key( host ):

                            self.lastStored[ host ] = { }

                        self.lastStored[ host ][ metricname ] = self.rrdm.grabLastUpdate( metric_dir + '/' + file )

    def getClusterName( self ):
        """Return clustername"""

        return self.cluster

    def memMetric( self, host, metric ):
        """Store metric from host in memory"""

        # <ATOMIC>
        #
        self.slot.acquire()
        
        if self.myMetrics.has_key( host ):

            if self.myMetrics[ host ].has_key( metric['name'] ):

                for mymetric in self.myMetrics[ host ][ metric['name'] ]:

                    if mymetric['time'] == metric['time']:

                        # Allready have this metric, abort
                        self.slot.release()
                        return 1
            else:
                self.myMetrics[ host ][ metric['name'] ] = [ ]
        else:
            self.myMetrics[ host ]                = { }
            self.myMetrics[ host ][ metric['name'] ]    = [ ]

        # Push new metric onto stack
        # atomic code; only 1 thread at a time may access the stack

        self.myMetrics[ host ][ metric['name'] ].append( metric )

        self.slot.release()
        #
        # </ATOMIC>

    def makeUpdateList( self, host, metriclist ):
        """
        Make a list of update values for rrdupdate
        but only those that we didn't store before
        """

        update_list    = [ ]
        metric        = None

        while len( metriclist ) > 0:

            metric = metriclist.pop( 0 )

            if self.checkStoreMetric( host, metric ):

                u_val    = str( metric['time'] ) + ':' + str( metric['val'] )
                #update_list.append( str('%s:%s') %( metric['time'], metric['val'] ) )
                update_list.append( u_val )

        return update_list

    def checkStoreMetric( self, host, metric ):
        """Check if supplied metric if newer than last one stored"""

        if self.lastStored.has_key( host ):

            if self.lastStored[ host ].has_key( metric['name'] ):

                if metric['time'] <= self.lastStored[ host ][ metric['name'] ]:

                    # This is old
                    return 0

        return 1

    def memLastUpdate( self, host, metricname, metriclist ):
        """
        Memorize the time of the latest metric from metriclist
        but only if it wasn't allready memorized
        """

        if not self.lastStored.has_key( host ):
            self.lastStored[ host ] = { }

        last_update_time = 0

        for metric in metriclist:

            if metric['name'] == metricname:

                if metric['time'] > last_update_time:

                    last_update_time = metric['time']

        if self.lastStored[ host ].has_key( metricname ):
            
            if last_update_time <= self.lastStored[ host ][ metricname ]:
                return 1

        self.lastStored[ host ][ metricname ] = last_update_time

    def storeMetrics( self ):
        """
        Store all metrics from memory to disk
        and do it to the RRD's in appropriate timeperiod directory
        """

        debug_msg( 5, "Entering storeMetrics()")

        count_values    = 0
        count_metrics    = 0
        count_bits    = 0

        for hostname, mymetrics in self.myMetrics.items():    

            for metricname, mymetric in mymetrics.items():

                count_metrics += 1

                for dmetric in mymetric:

                    count_values += 1

                    count_bits    += len( dmetric['time'] )
                    count_bits    += len( dmetric['val'] )

        count_bytes    = count_bits / 8

        debug_msg( 5, "size of cluster '" + self.cluster + "': " + 
            str( len( self.myMetrics.keys() ) ) + " hosts " + 
            str( count_metrics ) + " metrics " + str( count_values ) + " values " +
            str( count_bits ) + " bits " + str( count_bytes ) + " bytes " )

        for hostname, mymetrics in self.myMetrics.items():    

            for metricname, mymetric in mymetrics.items():

                metrics_to_store = [ ]

                # Pop metrics from stack for storing until none is left
                # atomic code: only 1 thread at a time may access myMetrics

                # <ATOMIC>
                #
                self.slot.acquire() 

                while len( self.myMetrics[ hostname ][ metricname ] ) > 0:

                    if len( self.myMetrics[ hostname ][ metricname ] ) > 0:

                        try:
                            metrics_to_store.append( self.myMetrics[ hostname ][ metricname ].pop( 0 ) )
                        except IndexError, msg:

                            # Somehow sometimes myMetrics[ hostname ][ metricname ]
                            # is still len 0 when the statement is executed.
                            # Just ignore indexerror's..
                            pass

                self.slot.release()
                #
                # </ATOMIC>

                # Create a mapping table, each metric to the period where it should be stored
                #
                metric_serial_table = self.determineSerials( hostname, metricname, metrics_to_store )

                update_rets = [ ]

                for period, pmetric in metric_serial_table.items():

                    create_ret = self.createCheck( hostname, metricname, period )    

                    update_ret = self.update( hostname, metricname, period, pmetric )

                    if update_ret == 0:

                        debug_msg( 9, 'stored metric %s for %s' %( hostname, metricname ) )
                    else:
                        debug_msg( 9, 'metric update failed' )

                    update_rets.append( create_ret )
                    update_rets.append( update_ret )

                # Lets ignore errors here for now, we need to make sure last update time
                # is correct!
                #
                #if not (1) in update_rets:

                self.memLastUpdate( hostname, metricname, metrics_to_store )

        debug_msg( 5, "Leaving storeMetrics()")

    def makeTimeSerial( self ):
        """Generate a time serial. Seconds since epoch"""

        # Seconds since epoch
        mytime = int( time.time() )

        return mytime

    def makeRrdPath( self, host, metricname, timeserial ):
        """Make a RRD location/path and filename"""

        rrd_dir        = '%s/%s/%s/%s'    %( check_dir(ARCHIVE_PATH), self.cluster, host, timeserial )
        rrd_file    = '%s/%s.rrd'    %( rrd_dir, metricname )

        return rrd_dir, rrd_file

    def getLastRrdTimeSerial( self, host ):
        """Find the last timeserial (directory) for this host"""

        newest_timeserial = 0

        for dir in self.timeserials[ host ]:

            valid_dir = 1

            for letter in dir:
                if letter not in string.digits:
                    valid_dir = 0

            if valid_dir:
                timeserial = dir
                if timeserial > newest_timeserial:
                    newest_timeserial = timeserial

        if newest_timeserial:
            return newest_timeserial
        else:
            return 0

    def determinePeriod( self, host, check_serial ):
        """Determine to which period (directory) this time(serial) belongs"""

        period_serial = 0

        if self.timeserials.has_key( host ):

            for serial in self.timeserials[ host ]:

                if check_serial >= serial and period_serial < serial:

                    period_serial = serial

        return period_serial

    def determineSerials( self, host, metricname, metriclist ):
        """
        Determine the correct serial and corresponding rrd to store
        for a list of metrics
        """

        metric_serial_table = { }

        for metric in metriclist:

            if metric['name'] == metricname:

                period        = self.determinePeriod( host, metric['time'] )    

                archive_secs    = ARCHIVE_HOURS_PER_RRD * (60 * 60)

                if (int( metric['time'] ) - int( period ) ) > archive_secs:

                    # This one should get it's own new period
                    period = metric['time']

                    if not self.timeserials.has_key( host ):
                        self.timeserials[ host ] = [ ]

                    self.timeserials[ host ].append( period )

                if not metric_serial_table.has_key( period ):

                    metric_serial_table[ period ] = [ ]

                metric_serial_table[ period ].append( metric )

        return metric_serial_table

    def createCheck( self, host, metricname, timeserial ):
        """Check if an rrd allready exists for this metric, create if not"""

        debug_msg( 9, 'rrdcreate: using timeserial %s for %s/%s' %( timeserial, host, metricname ) )
        
        rrd_dir, rrd_file = self.makeRrdPath( host, metricname, timeserial )

        if not os.path.exists( rrd_dir ):

            try:
                os.makedirs( rrd_dir )

            except os.OSError, msg:

                if msg.find( 'File exists' ) != -1:

                    # Ignore exists errors
                    pass

                else:

                    print msg
                    return

            debug_msg( 9, 'created dir %s' %( str(rrd_dir) ) )

        if not os.path.exists( rrd_file ):

            interval    = self.config.getInterval( self.cluster )
            heartbeat    = 8 * int( interval )

            params        = [ ]

            params.append( '--step' )
            params.append( str( interval ) )

            params.append( '--start' )
            params.append( str( int( timeserial ) - 1 ) )

            params.append( 'DS:sum:GAUGE:%d:U:U' %heartbeat )
            params.append( 'RRA:AVERAGE:0.5:1:%s' %(ARCHIVE_HOURS_PER_RRD * 240) )

            self.rrdm.create( str(rrd_file), params )

            debug_msg( 9, 'created rrd %s' %( str(rrd_file) ) )

    def update( self, host, metricname, timeserial, metriclist ):
        """
        Update rrd file for host with metricname
        in directory timeserial with metriclist
        """

        debug_msg( 9, 'rrdupdate: using timeserial %s for %s/%s' %( timeserial, host, metricname ) )

        rrd_dir, rrd_file    = self.makeRrdPath( host, metricname, timeserial )

        update_list        = self.makeUpdateList( host, metriclist )

        if len( update_list ) > 0:
            ret = self.rrdm.update( str(rrd_file), update_list )

            if ret:
                return 1
        
            debug_msg( 9, 'updated rrd %s with %s' %( str(rrd_file), string.join( update_list ) ) )

        return 0

def daemon():
    """daemonized threading"""

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

    run()

def run():
    """Threading start"""

    config        = GangliaConfigParser( GMETAD_CONF )
    s_timeout    = int( config.getLowestInterval() - 1 )

    socket.setdefaulttimeout( s_timeout )

    myXMLSource        = XMLGatherer( ARCHIVE_XMLSOURCE.split( ':' )[0], ARCHIVE_XMLSOURCE.split( ':' )[1] )
    myDataStore        = DataSQLStore( JOB_SQL_DBASE.split( '/' )[0], JOB_SQL_DBASE.split( '/' )[1] )

    myTorqueProcessor    = TorqueXMLProcessor( myXMLSource, myDataStore )
    myGangliaProcessor    = GangliaXMLProcessor( myXMLSource, myDataStore )

    try:
        torque_xml_thread    = threading.Thread( None, myTorqueProcessor.run, 'torque_proc_thread' )
        ganglia_xml_thread    = threading.Thread( None, myGangliaProcessor.run, 'ganglia_proc_thread' )

        torque_xml_thread.start()
        ganglia_xml_thread.start()
        
    except thread.error, msg:
        debug_msg( 0, 'FATAL ERROR: Unable to start main threads!: '+ str(msg) )
        syslog.closelog()
        sys.exit(1)
        
    debug_msg( 0, 'main threading started.' )

def main():
    """Program startup"""

    global DAEMONIZE, USE_SYSLOG

    if not processArgs( sys.argv[1:] ):
        sys.exit( 1 )

    if( DAEMONIZE and USE_SYSLOG ):
        syslog.openlog( 'jobarchived', syslog.LOG_NOWAIT, SYSLOG_FACILITY )

    if DAEMONIZE:
        daemon()
    else:
        run()

#
# Global functions
#

def check_dir( directory ):
    """Check if directory is a proper directory. I.e.: Does _not_ end with a '/'"""

    if directory[-1] == '/':
        directory = directory[:-1]

    return directory

def reqtime2epoch( rtime ):

    (hours, minutes, seconds )    = rtime.split( ':' )

    etime    = int(seconds)
    etime    = etime + ( int(minutes) * 60 )
    etime    = etime + ( int(hours) * 60 * 60 )

    return etime

def debug_msg( level, msg ):
    """Only print msg if correct levels"""

    if (not DAEMONIZE and DEBUG_LEVEL >= level):
        sys.stderr.write( printTime() + ' - ' + msg + '\n' )
    
    if (DAEMONIZE and USE_SYSLOG and SYSLOG_LEVEL >= level):
        syslog.syslog( msg )

def printTime( ):
    """Print current time in human readable format"""

    return time.strftime("%a %d %b %Y %H:%M:%S")

def write_pidfile():

    # Write pidfile if PIDFILE exists
    if PIDFILE:

        pid     = os.getpid()

        pidfile = open(PIDFILE, 'w')

        pidfile.write( str( pid ) )
        pidfile.close()

# Ooohh, someone started me! Let's go..
#
if __name__ == '__main__':
    main()
