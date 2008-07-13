<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Ganglia :: {longpage_title}</TITLE>
<META http-equiv="Content-type" content="text/html; charset=utf-8">
<META http-equiv="refresh" content="{refresh}{redirect}" >

<link rel="stylesheet" type="text/css" href="./lib/extjs/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="./css/styles.css" />
<script type="text/javascript" src="./lib/extjs/adapter/ext/ext-base.js"></script>
<!-- <script type="text/javascript" src="./lib/extjs/adapter/ext/ext-base-debug.js"></script> -->
<script type="text/javascript" src="./lib/extjs/ext-all.js"></script>
<!-- <script type="text/javascript" src="./lib/extjs/ext-all-debug.js"></script> -->
<script type="text/javascript" src="./lib/extjs/searchfield.js"></script>
<script type="text/javascript" src="./js/jobgrid.js"></script>
<script type="text/javascript">
Ext.onReady( function(){
  initJobGrid();
  JobProxy.on('beforeload', function(p, params) {
        params.c = "{cluster}";
	newparams = joinMyArray( params, myfilters );
	myparams = newparams;
	params = newparams;
    });
  ClusterImageWindow.html = '<IMG SRC="{clusterimage}" USEMAP="#MONARCH_CLUSTER_BIG" BORDER="0">';
  ClusterImageWindow.height = '{clusterimage_height}';
  ClusterImageWindow.width = '{clusterimage_width}';
  ClusterImageWindow.show();
  JobsDataStore.load( {params: {start: 0, limit: 30}} );
  JobListingWindow.setTitle( "{cluster} Jobs Overview" );
  JobListingWindow.show();
  });
</script>

</HEAD>
<BODY BGCOLOR="#FFFFFF">

    <MAP NAME="MONARCH_CLUSTER_BIG">
    <!-- START BLOCK : node_clustermap -->
    {node_area_map}
    <!-- END BLOCK : node_clustermap -->
    </MAP> 

  <A HREF="https://subtrac.sara.nl/oss/jobmonarch/">
  <IMG SRC="./jobmonarch.gif" ALT="Job Monarch" BORDER="0"></IMG>
  </A>
