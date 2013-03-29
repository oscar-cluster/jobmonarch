<?php
/*
 *
 * This file is part of Jobmonarch
 *
 * Copyright (C) 2006-2013  Ramon Bastiaans
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

ini_set("memory_limit","500M");
set_time_limit(0);

$my_dir = getcwd();

include_once "./libtoga.php";
include_once "./dwoo/dwooAutoload.php";

$r = escapeshellcmd( rawurldecode( $_GET["r"] ));
$range = $r;
if( !isset($range) or $range == '') $range= "job";

global $context;

#$context = 'cluster';

#chdir( $GANGLIA_PATH );

#include "./ganglia.php";
#include "./get_ganglia.php";

#chdir( $my_dir );

if ( !empty( $_GET ) ) 
{
    extract( $_GET );
}

global $GANGLIA_PATH;

//include_once "./class.TemplatePower.inc.php";

$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );
$clustername = $httpvars->getClusterName();
$view = $httpvars->getHttpVar( "j_view" );

$filter = array();

if( !isset($view) ) $view = "overview";
if( !isset($sortorder) ) $sortorder = "desc";
if( !isset($sortby) ) $sortby = "id";

if( isset( $filterorder ) && ($filterorder!='') ) 
{
    $myfilter_fields = explode( ",", $filterorder );
} 
else 
{
    if( isset($queue) && ($queue!='')) $filter['queue']=$queue;
    if( isset($state) && ($state!='')) $filter['state']=$state;
    if( isset($owner) && ($owner!='')) $filter['owner']=$owner;
    if( isset($id) && ($id!='')) $filter['id']=$id;
}

// Fill filter array in order they were picked by user
if( isset($myfilter_fields) ) 
{

    foreach( $myfilter_fields as $myfilter ) 
    {

        switch( $myfilter ) 
        {

            case "queue":
                $filter['queue']=$queue;
                break;
            case "state":
                $filter['state']=$state;
                break;
            case "owner":
                $filter['owner']=$owner;
                break;
            case "id":
                $filter['id']=$id;
                break;
        }
    }
}

function epochToDatetime( $epoch ) 
{

        return strftime( "%d-%m-%Y %H:%M:%S", $epoch );
}

function makeHeader( $page_call, $title, $longtitle ) 
{
    global $dwoo, $grid, $context, $initgrid;
    global $jobrange, $jobstart;
    global $page, $gridwalk, $clustername;
    global $parentgrid, $physical, $hostname;
    global $self, $filter, $cluster_url, $get_metric_string;
    global $metrics, $reports, $m, $conf;
    global $default_refresh, $filterorder, $view;
    global $JOB_ARCHIVE, $period_start, $period_stop, $h, $id;
    global $job_start, $job_stop, $range, $r, $metricname;
    global $conf, $show_hosts;
    try
       {
          //$dwoo = new Dwoo($conf['dwoo_compiled_dir'], $conf['dwoo_cache_dir']);
          $dwoo = new Dwoo( 'dwoo/compiled', 'dwoo/cache' );
       }
    catch (Exception $e)
       {
       print "<H4>There was an error initializing the Dwoo PHP Templating Engine: ".
          $e->getMessage() . "<br><br>The compile directory should be owned and writable by the apache user.</H4>";
          exit;
       }

    
    if( isset($conf['default_metric']) and !isset($m) )
        $metricname = $conf['default_metric'];
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

    if ($gridwalk=="fwd") 
    {
        # push our info on gridstack, format is "name@url>name2@url".
        if (end($gridstack) != $me) 
        {
            $gridstack[] = $me;
        }
    } 
    else if ($gridwalk=="back") 
    {
        # pop a single grid off stack.
        if (end($gridstack) != $me) 
        {
            array_pop($gridstack);
        }
    }

    $gridstack_str = join(">", $gridstack);
    $gridstack_url = rawurlencode($gridstack_str);

    if ($initgrid or $gridwalk) 
    {
        # Use cookie so we dont have to pass gridstack around within this site.
        # Cookie values are automatically urlencoded. Expires in a day.
        setcookie("gs", $gridstack_str, time() + 86400);
    }

    # Invariant: back pointer is second-to-last element of gridstack. Grid stack never
    # has duplicate entries.
    list($parentgrid, $parentlink) = explode("@", $gridstack[count($gridstack)-2]);

    $tpl = new Dwoo_Template_File("templates/header.tpl");
    $tpl_data = new Dwoo_Data();

    $tpl_data->assign( "date", date("r") );
    $tpl_data->assign( "longpage_title", $longtitle );
    $tpl_data->assign( "page_title", $title );

    # The page to go to when "Get Fresh Data" is pressed.
    $tpl_data->assign("page","./");

    # Templated Logo image
    $tpl_data->assign("images","./templates/$template_name/images");

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
    $tpl_data->assign("cluster_url", $cluster_url);
    # Build the node_menu
    $node_menu = "";

    if ($parentgrid) 
    {
        $node_menu .= "<B>$parentgrid $meta_designator</B> ";
        $node_menu .= "<B>&gt;</B>\n";
    }

    # Show grid.
    $mygrid =  ($self == "unspecified") ? "" : $self;
    $node_menu .= "<B><A HREF=\"../..\">$mygrid $meta_designator</A></B> ";
    $node_menu .= "<B>&gt;</B>\n";

    if ($physical)
    {
        $node_menu .= hiddenvar("p", $physical);
    }

    if ( $clustername ) 
    {
        $url = rawurlencode($clustername);
        $node_menu .= "<B><A HREF=\"../../?c=".rawurlencode($clustername)."\">$clustername</A></B> ";
        $node_menu .= "<B>&gt;</B>\n";
        $node_menu .= hiddenvar("c", $clustername);
    }

    if (!count($metrics) && $view!='search') 
    {
        echo "<h4>Cannot find any metrics for selected cluster \"$clustername\", exiting.</h4>\n";
        echo "Check ganglia XML tree (telnet $ganglia_ip $ganglia_port)\n";
        exit;
    }
    reset($metrics);
    $firsthost = key($metrics);

    $mmfh    = array();

    $mmfh    = $metrics[$firsthost];

    $context_metrics    = array();

    foreach( $mmfh as $mm => $bla )
    {
        $context_metrics[] = $mm;
    }

    foreach ($reports as $mr => $mfoo)
        $context_metrics[] = $mr;

    $node_menu .= "<B><A HREF=\"./?c=".rawurlencode($clustername)."\">Joblist</A></B> ";

    if( isset( $hostname ) && ( $view != 'host' ) ) 
    {

        $node_menu .= "<B>&gt;</B>\n";
        $href = "<A HREF=\"./?c=".rawurlencode($clustername)."&h=".$hostname."\">";
        $node_menu .= "<B>$href";
        $node_menu .= "host: $hostname</A></B> ";
    }

    if( count( $filter ) > 0 && $view != "search" ) 
    {

        $my_ct = 1;
        $filter_nr = count( $filter );

        foreach( $filter as $filtername=>$filterval ) 
        {

            $node_menu .= "<B>&gt;</B>\n";

            $href = "<A HREF=\"./?c=".rawurlencode($clustername);
            $temp_ct = 0;
            $n_filter = $filter;
            $my_filterorder = "";
            $my_filters = array_keys( $filter );

            foreach( $n_filter as $n_filtername=>$n_filterval ) 
            {

                if( $temp_ct < $my_ct ) 
                {
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

    $tpl_data->assign("view", $view);

    if( array_key_exists( "id", $filter ) or isset($hostname) ) 
    {

        #$range = "job";

        if( $page_call != "host_view" )
        {

            if (is_array($context_metrics) ) 
            {
                $metric_menu = "<B>Metric</B>&nbsp;&nbsp;"
                    ."<SELECT NAME=\"m\" OnChange=\"toga_form.submit();\">\n";

                sort($context_metrics);
                foreach( $context_metrics as $k ) 
                {
                    $url = rawurlencode($k);
                    $metric_menu .= "<OPTION VALUE=\"$url\" ";
                    if ($k == $metricname )
                        $metric_menu .= "SELECTED";
                    $metric_menu .= ">$k\n";
                }
                $metric_menu .= "</SELECT>\n";
            }

        }

        $tpl_data->assign("metric_menu", $metric_menu );

        if( $view == "search" or $view == "host" or $view == "overview-host" ) 
        {
            $tpl_data->assign("timeperiod", "yes" );
            if( is_numeric( $period_start ) ) 
            {
                $period_start = epochToDatetime( $period_start );
            }
            if( is_numeric( $period_stop ) ) 
            {
                $period_stop = epochToDatetime( $period_stop );
            }
            $tpl_data->assign("period_start", $period_start );
            $tpl_data->assign("period_stop", $period_stop );
            $tpl_data->assign("hostname", $hostname );

            if( $view == "host" or $view == "overview-host" ) 
            {
                $tpl_data->assign("hostview", "yes");
                $tpl_data->assign("job_start", $job_start );
                $tpl_data->assign("job_stop", $job_stop );
            }
        } 

    }

    if( array_key_exists( "id", $filter ) or isset($hostname) ) 
    {

        #$range = "job";

        if( ( $page_call != "host_view" ) && ( $view != "search" ) )
        {
            $context_ranges[]="hour";
            $context_ranges[]="day";
            $context_ranges[]="week";
            $context_ranges[]="month";
            $context_ranges[]="year";
            $context_ranges[]="job";

            $range_menu = "<B>Last</B>&nbsp;&nbsp;" ."<SELECT NAME=\"r\" OnChange=\"toga_form.submit();\">\n";
            foreach ($context_ranges as $v) 
            {
                $url=rawurlencode($v);
                $range_menu .= "<OPTION VALUE=\"$url\" ";
                if ($v == $range)
                {
                    $range_menu .= "SELECTED";
                }
                $range_menu .= ">$v\n";
            }
            $range_menu .= "</SELECT>\n";

            $tpl_data->assign("range_menu", $range_menu);
        }

    }

    if( $view == "search" or $view == "host" ) 
    {

        $node_menu .= "<B>&gt;</B>\n";
        $node_menu .= "<B>Jobarchive</B> ";
        $form_name = "archive_search_form";
        $tpl_data->assign("form_name", $form_name );

    } 
    else 
    {
        $form_name = "toga_form";
        $tpl_data->assign("form_name", $form_name );
    }

    if( $JOB_ARCHIVE && $page_call == 'overview' ) 
    {
        $tpl_data->assign( "search", "yes" );
        $tpl_data->assign( "cluster_url", rawurlencode($clustername) );
        $tpl_data->assign( "cluster", $clustername );
    }
    $tpl_data->assign( "cluster", $clustername );
    $tpl_data->assign("node_menu", $node_menu);

    # Make sure that no data is cached..
    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    # Date in the past
    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); # always modified
    header ("Cache-Control: no-cache, must-revalidate");  # HTTP/1.1
    header ("Pragma: no-cache");                          # HTTP/1.0

    $dwoo->output($tpl, $tpl_data);
}

function makeFooter() 
{
    global $dwoo, $version, $parsetime, $monarchversion;

    $tpl = new Dwoo_Template_File("templates/footer.tpl");
    $tpl_data = new Dwoo_Data();

    $tpl_data->assign("webfrontendversion",$version["webfrontend"]);
    $tpl_data->assign("monarchversion", $monarchversion);

    if ($version["gmetad"]) 
    {
        $tpl_data->assign("webbackendcomponent", "gmetad");
        $tpl_data->assign("webbackendversion",$version["gmetad"]);
    } 
    else if ($version["gmond"]) 
    {
        $tpl_data->assign("webbackendcomponent", "gmond");
        $tpl_data->assign("webbackendversion", $version["gmond"]);
    }

    $tpl_data->assign("parsetime", sprintf("%.4f", $parsetime) . "s");

    $dwoo->output($tpl, $tpl_data);
}

if( isset( $h ) and $h != '' ) 
{
    $hostname = $h;
}

//$tpl_data->assign( "footer", template( "templates/footer.tpl" ) );

$longtitle = "Batch Report :: Powered by Job Monarch!";
$title = "Batch Report";
//$tpl_data->assign("cluster_url", rawurlencode($clustername) );
//$tpl_data->assign("cluster", $clustername );

#makeHeader();

switch( $view ) 
{

    case "overview":

        include "./overview.php";
        makeOverview();
        break;

    case "search":

        include "./search.php";
        makeSearchPage();
        break;

    case "host":

        include "./host_view.php";
        makeHostView();
        break;

    case "overview-host":

        include "./host_view.php";
        makeHostView();
        break;

    default:

        include "./overview.php";
        makeOverview();
        break;
}

makeFooter();
?>
