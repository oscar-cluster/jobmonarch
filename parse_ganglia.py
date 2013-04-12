#!/usr/bin/env python

import shlex, sys, pprint
from glob import glob

class GangliaConfigParser:

    def __init__( self, filename ):

        self.conf_lijst   = [ ]
        self.conf_dict    = { }
        self.filename     = filename
        self.file_pointer = file( filename, 'r' )
        self.lexx         = shlex.shlex( self.file_pointer )
        self.lexx.whitespace_split = True

        self.parse()

    def __del__( self ):

        """
        Cleanup: close file descriptor
        """

        self.file_pointer.close()
        del self.lexx
        del self.conf_lijst

    def removeQuotes( self, value ):

        clean_value = value
        clean_value = clean_value.replace( "'", "" )
        clean_value = clean_value.replace( '"', '' )
        clean_value = clean_value.strip()
    
        return clean_value

    def removeBraces( self, value ):

        clean_value = value
        clean_value = clean_value.replace( "(", "" )
        clean_value = clean_value.replace( ')', '' )
        clean_value = clean_value.strip()
    
        return clean_value

    def parse( self ):

        """
        Parse self.filename using shlex scanning.
        - Removes /* comments */
        - Traverses (recursively) through all include () statements
        - Stores complete valid config tokens in self.conf_list

        i.e.:
            ['globals',
             '{',
             'daemonize',
             '=',
             'yes',
             'setuid',
             '=',
             'yes',
             'user',
             '=',
             'ganglia',
             'debug_level',
             '=',
             '0', 
             <etc> ]
        """

        t = 'bogus'
        c = False
        i = False

        while t != self.lexx.eof:
            #print 'get token'
            t = self.lexx.get_token()

            if len( t ) >= 2:

                if len( t ) >= 4:

                    if t[:2] == '/*' and t[-2:] == '*/':

                        #print 'comment line'
                        #print 'skipping: %s' %t
                        continue

                if t == '/*' or t[:2] == '/*':
                    c = True
                    #print 'comment start'
                    #print 'skipping: %s' %t
                    continue

                if t == '*/' or t[-2:] == '*/':
                    c = False
                    #print 'skipping: %s' %t
                    #print 'comment end'
                    continue

            if c:
                #print 'skipping: %s' %t
                continue

            if t == 'include':
                i = True
                #print 'include start'
                #print 'skipping: %s' %t
                continue

            if i:

                #print 'include start: %s' %t

                t2 = self.removeQuotes( t )
                t2 = self.removeBraces( t )

                for in_file in glob( self.removeQuotes(t2) ):

                    #print 'including file: %s' %in_file
                    parse_infile = GangliaConfigParser( in_file )

                    self.conf_lijst = self.conf_lijst + parse_infile.getConfLijst()

                    del parse_infile

                i = False
                #print 'include end'
                #print 'skipping: %s' %t
                continue

            #print 'keep: %s' %t
            self.conf_lijst.append( self.removeQuotes(t) )

    def getConfLijst( self ):

        return self.conf_lijst

    def confListToDict( self, parent_list=None ):

        """
        Recursively traverses a conf_list and creates dictionary from it
        """

        new_dict = { }
        count    = 0
        skip     = 0

        if not parent_list:
            parent_list = self.conf_lijst

        #print 'entering confListToDict(): (parent) list size %s' %len(parent_list)

        for n, c in enumerate( parent_list ):

            count = count + 1

            #print 'CL: n %d c %s' %(n, c)

            if skip > 0:

                #print '- skipped'
                skip = skip - 1
                continue

            if (n+1) <= (len( parent_list )-1):

                if parent_list[(n+1)] == '{':

                    if not new_dict.has_key( c ):
                        new_dict[ c ] = [ ]

                    (temp_new_dict, skip) = self.confListToDict( parent_list[(n+2):] )
                    new_dict[ c ].append( temp_new_dict )

                if parent_list[(n+1)] == '=' and (n+2) <= (len( parent_list )-1):

                    if not new_dict.has_key( c ):
                        new_dict[ c ] = [ ]

                    new_dict[ c ].append( parent_list[ (n+2) ] )

                    skip = 2

                if parent_list[n] == '}':

                    #print 'leaving confListToDict(): new dict = %s' %new_dict
                    return (new_dict, count)


    def makeConfDict( self ):

        """
        Walks through self.conf_list and creates a dictionary based upon config values

        i.e.:
            'tcp_accept_channel': [{'acl': [{'access': [{'action': ['"allow"'],
                                                         'ip': ['"127.0.0.1"'],
                                                         'mask': ['32']}]}],
                                    'port': ['8649']}],
            'udp_recv_channel': [{'port': ['8649']}],
            'udp_send_channel': [{'host': ['145.101.32.3'],
                                  'port': ['8649']},
                                 {'host': ['145.101.32.207'],
                                  'port': ['8649']}]}
        """

        new_dict = { }
        skip     = 0

        #print 'entering makeConfDict()'

        for n, c in enumerate( self.conf_lijst ):

            #print 'M: n %d c %s' %(n, c)

            if skip > 0:

                #print '- skipped'
                skip = skip - 1
                continue

            if (n+1) <= (len( self.conf_lijst )-1):

                if self.conf_lijst[(n+1)] == '{':

                    if not new_dict.has_key( c ):
                        new_dict[ c ] = [ ]

                    ( temp_new_dict, skip ) = self.confListToDict( self.conf_lijst[(n+2):] )
                    new_dict[ c ].append( temp_new_dict )

                if self.conf_lijst[(n+1)] == '=' and (n+2) <= (len( self.conf_lijst )-1):

                    if not new_dict.has_key( c ):
                        new_dict[ c ] = [ ]

                    new_dict[ c ].append( self.conf_lijst[ (n+2) ] )

                    skip = 2

        self.conf_dict = new_dict
        #print 'leaving makeConfDict(): conf dict size %d' %len( self.conf_dict )

    def checkConfDict( self ):

        if len( self.conf_lijst ) == 0:

            raise Exception("Something went wrong generating conf list for %s" %self.file_name )

        if len( self.conf_dict ) == 0:

            self.makeConfDict()

    def getConfDict( self ):

        self.checkConfDict()
        return self.conf_dict

    def getUdpSendChannels( self ):

        self.checkConfDict()
        return self.conf_dict[ 'udp_send_channel' ]

    def getSectionLastOption( self, section, option ):

        """
        Get last option set in a config section that could be set multiple times in multiple (include) files.

        i.e.: getSectionLastOption( 'globals', 'send_metadata_interval' )
        """

        self.checkConfDict()
        value = None

        if not self.conf_dict.has_key( section ):

            return None

        # Could be set multiple times in multiple (include) files: get last one set
        for c in self.conf_dict[ section ]:

                if c.has_key( option ):

                    cluster_name = c[ option ][0]

        return cluster_name

    def getClusterName( self ):

        return self.getSectionLastOption( 'cluster', 'name' )

    def getVal( self, section, option ):

        return self.getSectionLastOption( section, option )

    def getInt( self, section, valname ):

        value    = self.getVal( section, valname )

        if not value:
            return None

        return int( value )

    def getStr( self, section, valname ):

        value    = self.getVal( section, valname )

        if not value:
            return None

        return str( value )

GMOND_LOCATION = '/etc/ganglia/gmond.conf'

g = GangliaConfigParser( GMOND_LOCATION )

pprint.pprint( g.getConfLijst(), width=1 )

g.makeConfDict()

pprint.pprint( g.getConfDict(), width=1 )

print g.getClusterName()
print g.getUdpSendChannels()

print 'exiting..'
sys.exit(0)
