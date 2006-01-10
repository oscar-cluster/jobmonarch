#!/bin/bash
#
# This shellscript will setup jobarchived's SQL database
#

# Path of postgres's "createdb"
#
CREATEDB='/usr/bin/createdb'

# Path of postgres's "psql"
#
PSQL='/usr/bin/psql'

# dbname for toga
#
JOBDBNAME='jobarch'

# dbfile for toga
#
JOBDBCMD='./job_dbase.sql'

$CREATEDB $JOBDBNAME
$PSQL -f $JOBDBCMD $JOBDBNAME
