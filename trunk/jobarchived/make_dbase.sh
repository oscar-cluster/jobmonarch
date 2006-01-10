#!/bin/bash
#
# This shellscript will setup toga's SQL database
#

# Path of postgres's "createdb"
#
CREATEDB='/usr/bin/createdb'

# Path of postgres's "psql"
#
PSQL='/usr/bin/psql'

# dbname for toga
#
TOGADBNAME='toga'

# dbfile for toga
#
TOGADBCMD='./job_dbase.sql'

$CREATEDB $TOGADBNAME
$PSQL -f $TOGADBCMD $TOGADBNAME
