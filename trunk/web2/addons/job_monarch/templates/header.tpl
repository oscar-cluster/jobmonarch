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
  JobProxy.on('beforeload', function(p, params) 
    {
        params.c = "{cluster}";
	newparams = joinMyArray( params, myfilters );
	myparams = newparams;
	params = newparams;
    });

  ClusterImageArgs['{session_name}'] = '{session_id}';
  ClusterImageArgs['c'] = '{cluster}';

  ClusterImageWindow.html = '<IMG ID="clusterimage" SRC="{clusterimage}" USEMAP="#MONARCH_CLUSTER_BIG" BORDER="0">';
  ClusterImageWindow.show();
  reloadClusterImage();

  JobListingWindow.setTitle( "{cluster} Jobs Overview" );
  JobListingWindow.show();
  reloadJobStore();

  GraphSummaryWindow.show();

  Ext.get( 'rjqjgraph' ).update( '<IMG ID="rjqj_graph" SRC="{rjqj_graph}" BORDER=0>' );
  Ext.get( 'pie' ).update( '<IMG ID="pie" SRC="{pie}" BORDER=0>' );
});
</script>

</HEAD>
<BODY BGCOLOR="#FFFFFF">

    <MAP NAME="MONARCH_CLUSTER_BIG">
    <!-- START BLOCK : node_clustermap -->
    {node_area_map}
    <!-- END BLOCK : node_clustermap -->
    </MAP> 
