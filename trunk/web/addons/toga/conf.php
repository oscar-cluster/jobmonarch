<?php

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
// allcpus when all cpu's are in use
// singlecpu when less than all cpus are in use
//
$JOB_NODE_MARKING_ALLCPUS = "J";
$JOB_NODE_MARKING_SINGLECPU = "j";

// XML Datasource for Toga
// by default localhost's gmetad
//
$DATA_SOURCE = '127.0.0.1:8649';

?>
