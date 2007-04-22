<?php
/*
 *
 * This file is part of Jobmonarch
 *
 * Copyright (C) 2006  Ramon Bastiaans
 *
 * Jobmonarch is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Jobmonarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * SVN $Id$
 */

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

if( isset( $filterorder ) && ($filterorder!='') ) {
	$myfilter_fields = explode( ",", $filterorder );
} else {
	if( isset($queue) && ($queue!='')) $filter[queue]=$queue;
	if( isset($state) && ($state!='')) $filter[state]=$state;
	if( isset($user) && ($user!='')) $filter[user]=$user;
	if( isset($id) && ($id!='')) $filter[id]=$id;
}

// Fill filter array in order they were picked by user
if( isset($myfilter_fields) ) {

	foreach( $myfilter_fields as $myfilter ) {

		switch( $myfilter ) {

			case "queue":
				$filter[queue]=$queue;
				break;
			case "state":
				$filter[state]=$state;
				break;
			case "user":
				$filter[user]=$user;
				break;
			case "id":
				$filter[id]=$id;
				break;
		}
	}
}

//if( isset($queue) && ($queue!='')) $filter[queue]=$queue;
//if( isset($state) && ($state!='')) $filter[state]=$state;
//if( isset($user) && ($user!='')) $filter[user]=$user;
//if( isset($id) && ($id!='')) $filter[id]=$id;

function epochToDatetime( $epoch ) {

        return strftime( "%d-%m-%Y %H:%M:%S", $epoch );
}

function makeHeader( $page_call ) {

	global $tpl, $grid, $context, $initgrid;
	global $jobrange, $jobstart, $title, $longtitle;
	global $page, $gridwalk, $clustername;
	global $parentgrid, $physical, $hostname;
	global $self, $filter, $cluster_url, $get_metric_string;
	global $metrics, $reports, $m, $default_metric;
	global $default_refresh, $filterorder, $view;
	global $JOB_ARCHIVE, $period_start, $period_stop, $h, $id;
	global $job_start, $job_stop;
	
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

	if( $view != "search" )
		$tpl->assign( "refresh", $default_refresh );

	$tpl->assign( "date", date("r") );
	$tpl->assign( "longpage_title", $longtitle );
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
	$node_menu .= "<B><A HREF=\"../..\">$mygrid $meta_designator</A></B> ";
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

	if( isset( $hostname ) ) {

		$node_menu .= "<B>&gt;</B>\n";
		$href = "<A HREF=\"./?c=".rawurlencode($clustername)."&h=".$hostname."\">";
		$node_menu .= "<B>$href";
		$node_menu .= "host: $hostname</A></B> ";
	}

	if( count( $filter ) > 0 && $view != "search" ) {

		$my_ct = 1;
		$filter_nr = count( $filter );

		foreach( $filter as $filtername=>$filterval ) {

			$node_menu .= "<B>&gt;</B>\n";

			$href = "<A HREF=\"./?c=".rawurlencode($clustername);
			$temp_ct = 0;
			$n_filter = $filter;
			$my_filterorder = "";
			$my_filters = array_keys( $filter );

			foreach( $n_filter as $n_filtername=>$n_filterval ) {

				if( $temp_ct < $my_ct ) {
					$href .= "&". $n_filtername . "=" . $n_filterval;

					if( $my_filterorder == "" )
						$my_filterorder = $my_filters[$temp_ct];
					else
						$my_filterorder .= "," . $my_filters[$temp_ct];
				}

				$temp_ct++;
			}
			$href .= "&filterorder=$my_filterorder\">";

			if( $my_ct < $filter_nr )
				$node_menu .= "<B>$href$filtername: $filterval</A></B> ";
			else
				$node_menu .= "<B>$filtername: $filterval</B> ";

			$my_ct++;
		}
	}

	$m = $metricname;


	$tpl->gotoBlock( "_ROOT" );
	$tpl->assignGlobal("view", $view);

	if( array_key_exists( "id", $filter ) or isset($hostname) ) {

		//print_r( $context_metrics );

		if( !isset( $hostname ) ) {

			if (is_array($context_metrics) ) {
				$metric_menu = "<B>Metric</B>&nbsp;&nbsp;"
					."<SELECT NAME=\"m\" OnChange=\"".$form_name.".submit();\">\n";

				sort($context_metrics);
				foreach( $context_metrics as $k ) {
					$url = rawurlencode($k);
					$metric_menu .= "<OPTION VALUE=\"$url\" ";
					if ($k == $metricname )
						$metric_menu .= "SELECTED";
					$metric_menu .= ">$k\n";
				}
				$metric_menu .= "</SELECT>\n";

			}
		}

		$tpl->assign("metric_menu", $metric_menu );

		if( $view == "search" or $view == "host" ) {
			$tpl->newBlock("timeperiod");
			if( is_numeric( $period_start ) ) {
				$period_start = epochToDatetime( $period_start );
			}
			if( is_numeric( $period_stop ) ) {
				$period_stop = epochToDatetime( $period_stop );
			}
			$tpl->assign("period_start", $period_start );
			$tpl->assign("period_stop", $period_stop );
			$tpl->assign("hostname", $hostname );

			if( $view == "host" ) {
				$tpl->newBlock("hostview");
				$tpl->assign("job_start", $job_start );
				$tpl->assign("job_stop", $job_stop );
			}
		} 

	}

	//$ex_fn = $tpl->getVarValue( "_ROOT", "form_name" );

	if( $view == "search" or $view == "host" ) {

		$node_menu .= "<B>&gt;</B>\n";
		$node_menu .= "<B>Jobarchive</B> ";
		$form_name = "archive_search_form";
		$tpl->assignGlobal("form_name", $form_name );

	} else {
		$form_name = "toga_form";
		$tpl->assignGlobal("form_name", $form_name );
	}

	if( $JOB_ARCHIVE && $page_call == 'index' ) {
		$tpl->newBlock( "search" );
		$tpl->assignGlobal( "cluster_url", rawurlencode($clustername) );
		$tpl->assignGlobal( "cluster", $clustername );
	}
	$tpl->gotoBlock( "_ROOT" );
	$tpl->assignGlobal( "cluster", $clustername );
	$tpl->assign("node_menu", $node_menu);

	# Make sure that no data is cached..
	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    # Date in the past
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); # always modified
	header ("Cache-Control: no-cache, must-revalidate");  # HTTP/1.1
	header ("Pragma: no-cache");                          # HTTP/1.0
}

function makeFooter() {
	global $tpl, $version, $parsetime, $monarchversion;

	$tpl->gotoBlock( "_ROOT" );
	$tpl->assign("webfrontend-version",$version["webfrontend"]);
	$tpl->assign("monarch-version", $monarchversion);

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

function includeHostPage() {

	global $tpl;

	$tpl->assignInclude( "main", "templates/host_view.tpl" );
}

$tpl = new TemplatePower( "templates/index.tpl" );

$tpl->assignInclude( "header", "templates/header.tpl" );

if( isset( $h ) and $h != '' ) {
	$hostname = $h;
	//$view = "host";
}

switch( $view ) {

	case "overview":

		includeOverview();
		break;

	case "search":

		includeSearchPage();
		break;

	//case "host":

	//	includeHostPage();
	//	break;

	default:

		includeOverview();
		break;
}

$tpl->assignInclude( "footer", "templates/footer.tpl" );
$tpl->prepare();

$longtitle = "Batch Report :: Powered by Job Monarch!";
$title = "Batch Report";
makeHeader( 'index' );
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

	//case "host":

	//	include "./host_view.php";
	//	makeHostView();
	//	break;

	default:

		makeOverview();
		break;
}

makeFooter();
$tpl->printToScreen();
?>
