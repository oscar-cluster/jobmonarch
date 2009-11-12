<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Ganglia :: {longpage_title}</TITLE>
<META http-equiv="Content-type" content="text/html; charset=utf-8">

<link rel="stylesheet" href="./lib/lightbox2/css/lightbox.css" type="text/css" media="screen" />

<script type="text/javascript" src="./lib/lightbox2/js/prototype.js"></script>
<script type="text/javascript" src="./lib/lightbox2/js/scriptaculous.js?load=effects,builder"></script>
<script type="text/javascript" src="./lib/lightbox2/js/lightbox.js"></script>

<link rel="stylesheet" type="text/css" href="./lib/extjs/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="./css/styles.css" />
<link rel="stylesheet" type="text/css" href="./lib/extjs/tab-scroller-menu.css" />

<script type="text/javascript" src="./lib/extjs/adapter/ext/ext-base.js"></script>
<!-- <script type="text/javascript" src="./lib/extjs/adapter/ext/ext-base-debug.js"></script> -->
<script type="text/javascript" src="./lib/extjs/adapter/ext/ext-base-debug.js"></script>
<script type="text/javascript" src="./lib/extjs/ext-all.js"></script>
<!-- <script type="text/javascript" src="./lib/extjs/ext-all-debug.js"></script> -->
<script type="text/javascript" src="./lib/extjs/ext-all-debug.js"></script>
<script type="text/javascript" src="./lib/extjs/searchfield.js"></script>
<script type="text/javascript" src="./lib/extjs/StatusBar.js"></script>
<script type="text/javascript" src="./lib/extjs/ProgressBarPager.js"></script>
<script type="text/javascript" src="./lib/extjs/TabScrollerMenu.js"></script>
<script type="text/javascript" src="./lib/extjs/BufferView.js"></script>
<script type="text/javascript" src="./js/monarch.js"></script>
<script type="text/javascript">

Ext.onReady( function()
{
	Ext.QuickTips.init();

	JobProxy.on('beforeload', function(p, params) 
	{
		params['c']			= '{cluster}';
		params['{session_name}']	= '{session_id}';
		newparams			= joinMyArray( params, myfilters );
		myparams			= newparams;
		params				= newparams;
	});

	ClusterImageArgs['{session_name}']	= '{session_id}';
	ClusterImageArgs['c']			= '{cluster}';

	GraphSummaryWindow.show();

	ClusterImageWindow.html			= '<IMG ID="clusterimage" SRC="./image.php?{session_name}={session_id}" USEMAP="#MONARCH_CLUSTER_BIG" BORDER="0">';
	ClusterImageWindow.show();
	setClusterImagePosition();
	reloadClusterImage();

	JobListingWindow.setTitle( "{cluster} Jobs Overview" );
	JobListingWindow.show();
	//achorJobListing();
	setJobListingPosition();
	reloadJobStore();

	Ext.get( 'rjqjgraph' ).update( '<IMG ID="rjqj_graph" SRC="{rjqj_graph}" BORDER=0>' );
	//Ext.get( 'pie' ).update( '<IMG ID="pie" SRC="./image.php?{session_name}={session_id}&c={uue_clustername}&view=big-clusterimage" BORDER=0>' );
});
</script>


</HEAD>
<BODY CLASS="background">

<font size="1" color="blue">
Job Monarch version {monarch-version}<BR />
</font>
<A HREF="https://subtrac.sara.nl/oss/jobmonarch/" TARGET="_blank"><IMG SRC="./jobmonarch.gif" ALT="Job Monarch" BORDER="0"></A>

    <MAP NAME="MONARCH_CLUSTER_BIG">
    {node_area_map}
    </MAP> 
