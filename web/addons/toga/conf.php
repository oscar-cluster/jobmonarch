<?php
// Show hosts in a jobview by default?
//
$default_showhosts = 1;

// Path to Ganglia's web frontend root
//
$GANGLIA_PATH = "/var/www/ganglia";

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

// XML Datasource for Toga
// by default localhost's gmetad
//
$DATA_SOURCE = '127.0.0.1:8649';

// Is there a jobarchive?
//
$TARCHD = 1;

?>
