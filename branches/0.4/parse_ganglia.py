#!/usr/bin/env python

import shlex, sys
from glob import glob

class GangliaConfigParser:

    def __init__( self, filename ):

        self.conf_lijst   = [ ]
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
        c= False
        i= False

        while t != self.lexx.eof:
            #print 'get token'
            t = self.lexx.get_token()

            if t == '/*':
                c = True
                print 'comment start'
                print 'skipping: %s' %t
                continue

            if t == '*/':
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

GMOND_LOCATION = '/etc/ganglia/gmond.conf'

g = GangliaConfigParser( GMOND_LOCATION )

print g.getConfLijst()

print 'exiting..'
sys.exit(0)
