<?php

if ( !empty( $_GET ) ) {
	extract( $_GET );
}

$my_dir = getcwd();

include_once "./libtoga.php";

global $GANGLIA_PATH;
chdir( $GANGLIA_PATH );

include_once "./class.TemplatePower.inc.php";
chdir( $my_dir );

$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );
$clustername = $httpvars->getClusterName();
$view = $httpvars->getHttpVar( "view" );

$filter = array();

if( !isset($view) ) $view = "overview";
if( !isset($sortorder) ) $sortorder = "asc";
if( !isset($sortby) ) $sortby = "id";
if( isset($queue) && ($queue!='')) $filter[queue]=$queue;
if( isset($state) && ($state!='')) $filter[state]=$state;
if( isset($user) && ($user!='')) $filter[user]=$user;
if( isset($id) && ($id!='')) $filter[id]=$id;

function makeHeader() {

	global $tpl, $grid, $context, $initgrid;
	global $jobrange, $jobstart, $title;
	global $page, $gridwalk, $clustername;
	global $parentgrid, $physical, $hostname;
	global $self, $filter, $cluster_url, $get_metric_string;
	global $metrics, $reports, $m, $default_metric;
	global $default_refresh, $filterorder, $view;
	global $TARCHD;

	if( isset($default_metric) and !isset($m) )
		$metricname = $default_metric;
	else
		if( isset( $m ) )
			$metricname = $m;
		else
			$metricname = "load_one";

	$header = "header";

	# Maintain our path through the grid tree.
	$me = $self . "@" . $grid[$self][AUTHORITY];

	$gridstack = array();
	$gridstack[] = $me;

	if ($gridwalk=="fwd") {
		# push our info on gridstack, format is "name@url>name2@url".
		if (end($gridstack) != $me) {
			$gridstack[] = $me;
		}
	} else if ($gridwalk=="back") {
		# pop a single grid off stack.
		if (end($gridstack) != $me) {
			array_pop($gridstack);
		}
	}

	$gridstack_str = join(">", $gridstack);
	$gridstack_url = rawurlencode($gridstack_str);

	if ($initgrid or $gridwalk) {
		# Use cookie so we dont have to pass gridstack around within this site.
		# Cookie values are automatically urlencoded. Expires in a day.
		setcookie("gs", $gridstack_str, time() + 86400);
	}

	# Invariant: back pointer is second-to-last element of gridstack. Grid stack never
	# has duplicate entries.
	list($parentgrid, $parentlink) = explode("@", $gridstack[count($gridstack)-2]);

	# Setup a redirect to a remote server if you choose a grid from pulldown menu. Tell
	# destination server that we're walking foward in the grid tree.
	if (strstr($clustername, "http://")) {
		$tpl->assign("refresh", "0");
		$tpl->assign("redirect", ";URL=$clustername?gw=fwd&gs=$gridstack_url");
		echo "<h2>Redirecting, please wait...</h2>";
		$tpl->printToScreen();
		exit;
	}

	$tpl->assign( "refresh", $default_refresh );
	$tpl->assign( "date", date("r") );
	$tpl->assign( "page_title", $title );

	# The page to go to when "Get Fresh Data" is pressed.
	$tpl->assign("page","./");

	# Templated Logo image
	$tpl->assign("images","./templates/$template_name/images");

	#
	# Used when making graphs via graph.php. Included in most URLs
	#
	$sort_url=rawurlencode($sort);
	$get_metric_string = "m=$metric&r=$range&s=$sort_url&hc=$hostcols";

	if ($jobrange and $jobstart)
		$get_metric_string .= "&jr=$jobrange&js=$jobstart";

	# Set the Alternate view link.
	$cluster_url=rawurlencode($clustername);
	$node_url=rawurlencode($hostname);

	# Make some information available to templates.
	$tpl->assign("cluster_url", $cluster_url);
	# Build the node_menu
	$node_menu = "";

	if ($parentgrid) {
		$node_menu .= "<B>$parentgrid $meta_designator</B> ";
		$node_menu .= "<B>&gt;</B>\n";
	}

	# Show grid.
	$mygrid =  ($self == "unspecified") ? "" : $self;
	$node_menu .= "<B>$mygrid $meta_designator</B> ";
	$node_menu .= "<B>&gt;</B>\n";

	if ($physical)
		$node_menu .= hiddenvar("p", $physical);

	if ( $clustername ) {
		$url = rawurlencode($clustername);
		$node_menu .= "<B><A HREF=\"../../?c=".rawurlencode($clustername)."\">$clustername</A></B> ";
		$node_menu .= "<B>&gt;</B>\n";
		$node_menu .= hiddenvar("c", $clustername);
	}

	if (!count($metrics)) {
		echo "<h4>Cannot find any metrics for selected cluster \"$clustername\", exiting.</h4>\n";       echo "Check ganglia XML tree (telnet $ganglia_ip $ganglia_port)\n";
		exit;
	}
	$firsthost = key($metrics);
	foreach ($metrics[$firsthost] as $m => $foo)
		$context_metrics[] = $m;
	foreach ($reports as $r => $foo)
		$context_metrics[] = $r;

	$node_menu .= "<B><A HREF=\"./?c=".rawurlencode($clustername)."\">Joblist</A></B> ";

	if( count( $filter ) > 0 && $view != "search" ) {

		$filter_fields = explode( ",", $filterorder );
		$onclick_href = array();

		if( count( $filter_fields ) > 1 ) {
			//for( $i = (count( $filter_fields )-2); $i >= 0; $i-- ) {
				
			//	for( $l = intval($i+1); $l <=(count($filter_fields)-1); $l++ ) {
					//$onclick_href[$filter_fields[$i]] = $onclick_href[$filter_fields[$i]] . "removeFilter( '" . $filter_fields[$l] . "' );";
			//		if( !isset( $onclick_href[$filter_fields[$i]] ) )
			//			$onclick_href[$filter_fields[$i]] = "removeFilters( '".$filter_fields[$l];
			//		else
			//			$onclick_href[$filter_fields[$i]] = $onclick_href[$filter_fields[$i]] . " ". $filter_fields[$l];
						
			//	}
			//	$onclick_href[$filter_fields[$i]] = $onclick_href[$filter_fields[$i]] . "' )";
			//}
			foreach( $filter_fields as $filtername ) {

				$node_menu .= "<B>&gt;</B>\n";
				if( isset( $onclick_href[$filtername] ) )
					$node_menu .= "<B><A HREF =\"#\" onClick=\"".$onclick_href[$filtername]."\">$filtername: $filter[$filtername]</A></B> ";
				else
					$node_menu .= "<B>$filtername: $filter[$filtername]</B> ";
			}
		} else {

			foreach( $filter as $filtername=>$filterval ) {
				$node_menu .= "<B>&gt;</B>\n";
				$node_menu .= "<B>$filtername: $filterval</B> ";
			}
		}

	}

	if( $view == "search" ) {
		$node_menu .= "<B>&gt;</B>\n";
		$node_menu .= "<B>Jobarchive</B> ";
		$tpl->assign("view", "search" );
		$tpl->assign("form_name", "archive_search_form" );
	} else {
		$tpl->assign("form_name", "toga_form" );
		$tpl->assign("view", "overview" );
	}

	$tpl->assign("node_menu", $node_menu);

	if( array_key_exists( "id", $filter ) ) {

		if (is_array($context_metrics) ) {
			$metric_menu = "<B>Metric</B>&nbsp;&nbsp;"
				."<SELECT NAME=\"m\" OnChange=\"toga_form.submit();\">\n";

			sort($context_metrics);
			foreach( $context_metrics as $k ) {
				$url = rawurlencode($k);
				$metric_menu .= "<OPTION VALUE=\"$url\" ";
				if ($k == $metricname )
					$metric_menu .= "SELECTED";
				$metric_menu .= ">$k\n";
			}
			$metric_menu .= "</SELECT>\n";

			$tpl->assign("metric_menu", $metric_menu );
		}
	}
	$m = $metricname;

	if( $TARCHD ) {
		$tpl->newBlock( "search" );
		$tpl->assignGlobal( "cluster_url", rawurlencode($clustername) );
		$tpl->assignGlobal( "cluster", $clustername );
		$tpl->gotoBlock( "_ROOT" );
	}

	# Make sure that no data is cached..
	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    # Date in the past
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); # always modified
	header ("Cache-Control: no-cache, must-revalidate");  # HTTP/1.1
	header ("Pragma: no-cache");                          # HTTP/1.0
}

function makeFooter() {
	global $tpl, $version, $parsetime;

	$tpl->assign("webfrontend-version",$version["webfrontend"]);
	$tpl->assign("togaweb-version", "0.1");
	$tpl->assign("togaarch-version", "0.1");
	$tpl->assign("togaplug-version", "0.1");

	if ($version["gmetad"]) {
		$tpl->assign("webbackend-component", "gmetad");
		$tpl->assign("webbackend-version",$version["gmetad"]);
	} else if ($version["gmond"]) {
		$tpl->assign("webbackend-component", "gmond");
		$tpl->assign("webbackend-version", $version["gmond"]);
	}

	$tpl->assign("parsetime", sprintf("%.4f", $parsetime) . "s");
}

function includeSearchpage() {
	global $tpl;

	$tpl->assignInclude( "main", "templates/search.tpl" );

}

function includeOverview() {
	global $tpl;

	$tpl->assignInclude( "main", "templates/overview.tpl" );
}

$tpl = new TemplatePower( "templates/index.tpl" );

$tpl->assignInclude( "header", "templates/header.tpl" );

switch( $view ) {

	case "overview":

		includeOverview();
		break;

	case "search":

		includeSearchPage();
		break;

	default:

		includeOverview();
		break;
}

$tpl->assignInclude( "footer", "templates/footer.tpl" );
$tpl->prepare();

$title = "Torque Report";
makeHeader();
$tpl->assign("cluster_url", rawurlencode($clustername) );
$tpl->assign("cluster", $clustername );

switch( $view ) {

	case "overview":

		include "./overview.php";
		makeOverview();
		break;

	case "search":

		include "./search.php";
		makeSearchPage();
		break;

	default:

		makeOverview();
		break;
}

makeFooter();

$tpl->printToScreen();
?>
