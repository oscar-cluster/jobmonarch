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

        t = 'bogus'
        c = False
        i = False

        while t != self.lexx.eof:
            #print 'get token'
            t = self.lexx.get_token()

            if len( t ) >= 2:

                if len( t ) >= 4:

                    if t[:2] == '/*' and t[-2:] == '*/':

                        print 'comment line'
                        print 'skipping: %s' %t
                        continue

                if t == '/*' or t[:2] == '/*':
                    c = True
                    print 'comment start'
                    print 'skipping: %s' %t
                    continue

                if t == '*/' or t[-2:] == '*/':
                    c = False
                    print 'skipping: %s' %t
                    print 'comment end'
                    continue

            if c:
                print 'skipping: %s' %t
                continue

            if t == 'include':
                i = True
                print 'include start'
                print 'skipping: %s' %t
                continue

            if i:

                print 'include start: %s' %t

                t2 = self.removeQuotes( t )
                t2 = self.removeBraces( t )

                for in_file in glob( self.removeQuotes(t2) ):

                    print 'including file: %s' %in_file
                    parse_infile = GangliaConfigParser( in_file )

                    self.conf_lijst = self.conf_lijst + parse_infile.getConfLijst()

                    del parse_infile

                i = False
                print 'include end'
                print 'skipping: %s' %t
                continue

            print 'keep: %s' %t
            self.conf_lijst.append( t )

    def getConfLijst( self ):

        return self.conf_lijst

    def confListToDict( self, parent_list=None ):

        new_dict = { }
        count    = 0
        skip     = 0

        if not parent_list:
            parent_list = self.conf_lijst

        print 'entering confListToDict(): (parent) list size %s' %len(parent_list)

        for n, c in enumerate( parent_list ):

            count = count + 1

            print 'CL: n %d c %s' %(n, c)

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

                    #print 'parent_list = %s' %parent_list
                    print 'leaving confListToDict(): new dict = %s' %new_dict
                    return (new_dict, count)

    def getConfDict( self ):

        return self.conf_dict

    def makeConfDict( self ):

        new_dict = { }
        skip     = 0

        print 'entering makeConfDict()'

        for n, c in enumerate( self.conf_lijst ):

            print 'M: n %d c %s' %(n, c)

            if skip > 0:

                print '- skipped'
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
        print 'leaving makeConfDict(): conf dict size %d' %len( self.conf_dict )

GMOND_LOCATION = '/etc/ganglia/gmond.conf'

g = GangliaConfigParser( GMOND_LOCATION )

pprint.pprint( g.getConfLijst(), width=1 )

g.makeConfDict()

pprint.pprint( g.getConfDict(), width=1 )

print 'exiting..'
sys.exit(0)
