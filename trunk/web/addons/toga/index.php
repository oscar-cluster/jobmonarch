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
if( isset($id) && ($id!='')) $filter[id]=$id;
if( isset($state) && ($state!='')) $filter[state]=$state;
if( isset($user) && ($user!='')) $filter[user]=$user;
if( isset($queue) && ($queue!='')) $filter[queue]=$queue;

function makeHeader() {

	global $tpl, $grid, $context, $initgrid;
	global $jobrange, $jobstart, $title;
	global $page, $gridwalk, $clustername;
	global $parentgrid, $physical, $hostname;
	global $self, $filter, $cluster_url, $get_metric_string;

	if ( $context == "control" && $controlroom < 0 )
		$header = "header-nobanner";
	else
		$header = "header";

	# Maintain our path through the grid tree.
	$me = $self . "@" . $grid[$self][AUTHORITY];

	if ($initgrid) {
		$gridstack = array();
		$gridstack[] = $me;
	} else if ($gridwalk=="fwd") {
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
	if (isset($page))
		$tpl->assign("page",$page);
	else
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

	$node_menu .= "<B><A HREF=\"./?c=".rawurlencode($clustername)."\">Joblist</A></B> ";

	if( count( $filter ) > 0 ) {

		foreach( $filter as $filtername => $filterval ) {

			$node_menu .= "<B>&gt;</B>\n";
			$node_menu .= "<B>'$filtername': $filterval</B> ";
		}
	}

	$tpl->assign("node_menu", $node_menu);

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

function includeJobview() {
	global $tpl;

	$tpl->assignInclude( "main", "templates/jobview.tpl" );
}

function includeOverview() {
	global $tpl;

	$tpl->assignInclude( "main", "templates/overview.tpl" );
}

function makeJobview() {

}

$tpl = new TemplatePower( "templates/index.tpl" );

$tpl->assignInclude( "header", "templates/header.tpl" );

switch( $view ) {

	case "overview":

		includeOverview();
		break;

	case "jobview":

		includeJobview();
		break;

	default:

		includeOverview();
		break;
}

$tpl->assignInclude( "footer", "templates/footer.tpl" );
$tpl->prepare();

$title = "Torque Report";
makeHeader();

switch( $view ) {

	case "overview":

		include "./overview.php";
		makeOverview();
		break;

	case "jobview":

		makeJobview();
		break;

	default:

		makeOverview();
		break;
}

makeFooter();

$tpl->printToScreen();
?>
