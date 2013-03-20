<?php
// Show hosts in a jobview by default?
//
$default_showhosts = true;

// Stop displaying archive search results after SEARCH_RESULT_LIMIT
//
$SEARCH_RESULT_LIMIT = 20;

// Show the column job attribute 'requested memory'?
//
$COLUMN_REQUESTED_MEMORY = false;

// Show the column job attribute 'queued' (since)?
//
$COLUMN_QUEUED = true;

// Show the column job attribute 'nodes' hostnames?
//
$COLUMN_NODES = true;

// Path to Ganglia's web frontend root
//
$GANGLIA_PATH = "/data/ganglia-web-current/";
//$GANGLIA_PATH = "../..";

// Format of how to display a date and time in human readable format
//
$DATETIME_FORMAT = "%a %d %b %Y %H:%M:%S";

// Max size of small clusterimage
// (250 pixels is same width as Ganglia's pie chart)
//
$SMALL_CLUSTERIMAGE_MAXWIDTH = 300;

// The size of a single node in the small clusterimage
//
$SMALL_CLUSTERIMAGE_NODEWIDTH = 7;

// Max size of small clusterimage
// (250 pixels is same width as Ganglia's pie chart)
//
$BIG_CLUSTERIMAGE_MAXWIDTH = 500;

// The size of a single node in the small clusterimage
//
$BIG_CLUSTERIMAGE_NODEWIDTH = 11;

// Max size of small host image
//
$SMALL_HOSTIMAGE_MAXWIDTH = 450;

// How to mark nodes with a job in clusterimage
//
$JOB_NODE_MARKING = "J";

// How to mark nodes that are 'down' in the batch
//
$NODE_DOWN_MARKING = "X";

// How to mark nodes that are 'offline' in the batch
//
$NODE_OFFLINE_MARKING = ".";

// Show empty columns in the ClusterImage?
//
$SHOW_EMPTY_COLUMN = false;

// Show empty rows in the ClusterImage?
//
$SHOW_EMPTY_ROW = true;

// XML Datasource for Job Monarch
// by default localhost's gmetad
// [syntax: <ip>:<port>]
//
$DATA_SOURCE = '127.0.0.1:8651';

// Is there a jobarchive?
//
$JOB_ARCHIVE = false;

// Path to the job archive rrd files
//
$JOB_ARCHIVE_DIR = "/path/to/my/archive";

// Location of the job archive database
// [syntax: <ip>/<dbase>]
//
$JOB_ARCHIVE_DBASE = "127.0.0.1/jobarch";

// Path to rrdtool binary
//
$RRDTOOL = "/usr/bin/rrdtool";

// Include cluster specific settings here, 
// they will override any (global) settings above
// on a per-cluster basis, where available.
//
//$CLUSTER_CONFS["Example Cluster"]	= "./clusterconf/example.php";
//
//$CLUSTER_CONFS["LISA Cluster"]          = "./clusterconf/lisa-example.php";
//$CLUSTER_CONFS["GINA Cluster"]          = "./clusterconf/gina-example.php";
?>
