<?php
// Show hosts in a jobview by default?
//
$default_showhosts = 1;

// Stop displaying archive search results after SEARCH_RESULT_LIMIT
//
$SEARCH_RESULT_LIMIT = 20;

// Show the column job attribute 'requested memory'?
//
$COLUMN_REQUESTED_MEMORY = 0;

// Show the column job attribute 'queued' (since)?
//
$COLUMN_QUEUED = 1;

// Path to Ganglia's web frontend root
//
$GANGLIA_PATH = "/var/www/ganglia";

// Format of how to display a date and time in human readable format
//
$DATETIME_FORMAT = "%a %d %b %Y %H:%M:%S";

// Max size of small clusterimage
// (250 pixels is same width as Ganglia's pie chart)
//
$SMALL_CLUSTERIMAGE_MAXWIDTH = 250;

// The size of a single node in the small clusterimage
//
$SMALL_CLUSTERIMAGE_NODEWIDTH = 11;

// How to mark nodes with a job in clusterimage
//
$JOB_NODE_MARKING = "J";

// XML Datasource for Job Monarch
// by default localhost's gmetad
// [syntax: <ip>:<port>]
//
$DATA_SOURCE = '127.0.0.1:8651';

// Is there a jobarchive?
//
$JOB_ARCHIVE = 1;

// Path to the job archive rrd files
//
$JOB_ARCHIVE_DIR = "/data/gfs/jobarch/rrds";

// Location of the job archive database
// [syntax: <ip>/<dbase>]
//
$JOB_ARCHIVE_DBASE = "127.0.0.1/jobarch";

// Path to rrdtool binary
//
$RRDTOOL = "/usr/bin/rrdtool";
?>
