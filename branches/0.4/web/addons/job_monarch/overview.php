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

global $GANGLIA_PATH, $clustername, $tpl_data, $filter, $cluster, $get_metric_string, $cluster_url, $sh;
global $hosts_up, $m, $start, $end, $filterorder, $COLUMN_REQUESTED_MEMORY, $COLUMN_QUEUED, $COLUMN_NODES, $hostname, $piefilter;
global $longtitle, $title, $range;

include_once "./dwoo/dwooAutoload.php";

global $dwoo;

$tpl = new Dwoo_Template_File("templates/overview.tpl");
$tpl_data = new Dwoo_Data();

$tpl_data->assign( "clustername", $clustername );

if( $JOB_ARCHIVE )
{
    $tpl_data->assign( "cluster_url", rawurlencode($clustername) );
}

$rjqj_start = null;
$ds         = new DataSource();
$myxml_data = $ds->getData();

$data_gatherer = new DataGatherer( $clustername );
$data_gatherer->parseXML( $myxml_data );

$heartbeat = $data_gatherer->getHeartbeat();
$jobs      = $data_gatherer->getJobs();
$gnodes    = $data_gatherer->getNodes();
$cpus      = $data_gatherer->getCpus();
$use_fqdn  = $data_gatherer->getUsingFQDN();

//print_r( $gnodes );

function setupFilterSettings() 
{

    global $tpl_data, $filter, $clustername, $piefilter, $data_gatherer, $myxml_data, $filterorder, $_SESSION;

    $filter_image_url = "";

    foreach( $filter as $filtername => $filtervalue ) 
    {
        $tpl_data->assign( $filtername, $filtervalue );

        $filter_image_url    .= "&$filtername=$filtervalue";
    }

    session_start();

    unset( $_SESSION["data"] );
    $_SESSION["data"] = &$myxml_data;

    $ic = new ClusterImage( $myxml_data, $clustername );

    $ic->setJobs( $data_gatherer->getJobs() );
    $ic->setNodes( $data_gatherer->getNodes() );
    $ic->setBig();
    $ic->setNoimage();
    $ic->draw();

    $tpl_data->assign( "clusterimage", "./image.php?". session_name() . "=" . session_id() ."&c=".rawurlencode($clustername)."&j_view=big-clusterimage".$filter_image_url );

    $tpl_data->assign( "node_clustermap", "yes" );
    $tpl_data->assign( "node_area_map", $ic->getImagemapArea() );

    $tpl_data->assign( "order", $filterorder );

    if( array_key_exists( "id", $filter ) ) 
    {
        $piefilter = 'id';
    } 
    else if( array_key_exists( "owner", $filter ) ) 
    {
        $piefilter = 'owner';
    } 
    else if( array_key_exists( "queue", $filter ) ) 
    {
        $piefilter = 'queue';
    }

    $pie    = drawPie();

    $tpl_data->assign("pie", $pie );
}

function timeToEpoch( $time ) 
{
    $time_fields = explode( ':', $time );

    if( count( $time_fields ) == 3 ) 
    {
        $hours   = $time_fields[0];
        $minutes = $time_fields[1];
        $seconds = $time_fields[2];

    } 
    else if( count( $time_fields ) == 2 ) 
    {
        $hours   = 0;
        $minutes = $time_fields[0];
        $seconds = $time_fields[1];

    } 
    else if( count( $time_fields ) == 1 ) 
    {
        $hours   = 0;
        $minutes = 0;
        $seconds = $time_fields[0];
    }

    $myepoch = intval( $seconds + (intval( $minutes * 60 )) + (intval( $hours * 3600 )) );

    return $myepoch;
}

function colorRed( $color ) 
{
    return substr( $color, 0, 2 );
}

function colorGreen( $color ) 
{
    return substr( $color, 2, 2 );
}

function colorBlue( $color ) 
{
    return substr( $color, 4, 2 );
}

function colorDiffer( $first, $second ) 
{
    // Make sure these two colors differ atleast 50 R/G/B
    $min_diff = 50;

    $c1r   = hexDec( colorRed( $first ) );
    $c1g   = hexDec( colorGreen( $first ) );
    $c1b   = hexDec( colorBlue( $first ) );

    $c2r   = hexDec( colorRed( $second ) );
    $c2g   = hexDec( colorGreen( $second ) );
    $c2b   = hexDec( colorBlue( $second ) );

    $rdiff = ($c1r >= $c2r) ? $c1r - $c2r : $c2r - $c1r;
    $gdiff = ($c1g >= $c2g) ? $c1g - $c2g : $c2g - $c1g;
    $bdiff = ($c1b >= $c2b) ? $c1b - $c2b : $c2b - $c1b;

    if( $rdiff >= $min_diff or $gdiff >= $min_diff or $bdiff >= $min_diff ) 
    {
        return TRUE;

    } 
    else 
    {
        return FALSE;
    }
}

function randomColor( $known_colors ) 
{
    // White (000000) would be invisible
    $start       = "004E00";
    
    $start_red   = colorRed( $start );
    $start_green = colorGreen( $start );
    $start_blue  = colorBlue( $start );
    
    $end         = "FFFFFF";

    $end_red     = colorRed( $end );
    $end_green   = colorGreen( $end );
    $end_blue    = colorBlue( $end );

    $change_color= TRUE;

    while( $change_color ) 
    {
        $change_color= FALSE;

        $new_red     = rand( hexDec( $start_red ), hexDec( $end_red ) );
        $new_green   = rand( hexDec( $start_green ), hexDec( $end_green ) );
        $new_blue    = rand( hexDec( $start_blue ), hexDec( $end_blue ) );

        $new         = decHex( $new_red ) . decHex( $new_green ) . decHex( $new_blue );

        foreach( $known_colors as $old )
        {
            if( !colorDiffer( $new, $old ) )
            {
                 $change_color = TRUE;
            }
        }
    }

    // Whoa! Actually found a good color ;)
    return $new;
}

// Code these some day
function drawJobPie() { }

function drawUserPie() { }

function drawQueuePie() { }

function drawPie() 
{
    global $jobs, $gnodes, $piefilter, $filter, $metrics;

    $nodes = $gnodes;

    if( isset($piefilter) )    
    {
        $pie_args = "title=" . rawurlencode("Cluster ".$piefilter." usage");
    } 
    else 
    {
        $pie_args = "title=" . rawurlencode("Cluster queue usage");
    }

    $pie_args .= "&size=250x150";

    $queues    = array();
    $nr_jobs   = count( $jobs );
    $nr_nodes  = count( $nodes );

    $nr_cpus   = cluster_sum("cpu_num", $metrics);

    $empty_cpus= 0;
    $used_cpus = 0;

    $job_weight= array();

    foreach( $nodes as $node ) 
    {
        $myjobs    = $node->getJobs();
        $myhost    = $node->getHostname();
        $node_cpus = $metrics[$myhost]["cpu_num"]['VAL'];
        $job_cpu   = 0;

        foreach( $myjobs as $myjob ) 
        {
            $job_cpu += isset( $jobs[$myjob]['ppn'] ) ? $jobs[$myjob]['ppn'] : 1;
        }

        $node_freecpu= $node_cpus - $job_cpu;

        $empty_cpus += $node_freecpu;
    }

    $empty_cpus = ( $empty_cpus >= 0 ) ? $empty_cpus : 0;
    $used_cpus  = $nr_cpus - $empty_cpus;

    $empty_percentage= $empty_cpus;

    $qcolors   = array();
    $color     = randomColor( $qcolors );
    $qcolors[] = $color;
    $pie_args .= "&free=$empty_percentage,$color";

    if( isset( $piefilter ) )
    {
        $filterpie = array();
    }

    foreach( $nodes as $node )
    {
        $node_jobs    = $node->getJobs();
        $nr_node_jobs = count( $node_jobs );
        $myhost       = $node->getHostname();
        $node_cpus    = $metrics[$myhost]["cpu_num"]['VAL'];

        foreach( $node_jobs as $myjob )
        {
            $job_cpu = isset( $jobs[$myjob]['ppn'] ) ? $jobs[$myjob]['ppn'] : 1;

            // Determine the weight of this job
            // - what percentage of the cpus is in use by this job
            //
            //$job_weight[$myjob]    = ( $job_cpu / $nr_cpus );
            $job_weight[$myjob]    = $job_cpu;

            if( isset( $piefilter ) ) {

                $countjob = 1;

                if( $piefilter == 'id' )
                {
                    if( $myjob != $filter[$piefilter] )
                    {
                        $countjob = 0;
                    }
                }
                else if( $piefilter == 'owner' )
                {
                    if( $jobs[$myjob]['owner'] != $filter[$piefilter] )
                    {
                        $countjob = 0;
                    }
                }
                else
                {
                    if( $jobs[$myjob][$piefilter] != $filter[$piefilter] )
                    {
                        $countjob = 0;
                    }
                }

                if( $countjob )
                {

                    if( !isset( $filterpie[$filter[$piefilter]] ) )
                    {
                        $filterpie[$filter[$piefilter]] = $job_weight[$myjob];
                    }
                    else
                    {

                        $filterpie[$filter[$piefilter]] = $filterpie[$filter[$piefilter]] + $job_weight[$myjob];
                    }
                }
                else
                {
                    if( !isset( $filterpie["other"] ) )
                    {
                        $filterpie["other"] = $job_weight[$myjob];
                    }
                    else
                    {
                        $filterpie["other"] = $filterpie["other"] + $job_weight[$myjob];
                    }

                }
                
            }
            else
            {

                $qname = $jobs[$myjob]['queue'];

                if( !isset( $queues[$qname] ) )
                {
                    $queues[$qname] = $job_weight[$myjob];
                }
                else
                {
                    $queues[$qname] = $queues[$qname] + $job_weight[$myjob];
                }
            }
        }
    }

    if( isset( $piefilter ) )
    {
        $graphvals = $filterpie;
    }
    else
    {
        $graphvals = $queues;
    }

    foreach( $graphvals as $name => $totalweight) 
    {
        $percentage    = $totalweight;
        
        $color         = randomColor( $qcolors );
        $qcolors[]     = $color;
        $pie_args     .= "&$name=$percentage,$color";
    }
    $pie = "../../pie.php?$pie_args";

    return $pie;
}


function sortJobs( $jobs, $sortby, $sortorder ) 
{
    $sorted    = array();

    $cmp    = create_function( '$a, $b', 
        "global \$sortby, \$sortorder;".

        "if( \$a == \$b ) return 0;".

        "if (\$sortorder==\"desc\")".
            "return ( \$a < \$b ) ? 1 : -1;".
        "else if (\$sortorder==\"asc\")".
            "return ( \$a > \$b ) ? 1 : -1;" );

    if( isset( $jobs ) && count( $jobs ) > 0 ) 
    {
        foreach( $jobs as $jobid => $jobattrs ) 
        {
                $state     = $jobattrs['status'];
                $owner     = $jobattrs['owner'];
                $queue     = $jobattrs['queue'];
                $name      = $jobattrs['name'];
                $req_cpu   = $jobattrs['requested_time'];
                $req_memory= $jobattrs['requested_memory'];

                if( $state == 'R' )
                {
                    $nodes = count( $jobattrs['nodes'] );
                }
                else
                {
                    $nodes = $jobattrs['nodes'];
                }

                $ppn         = (int) $jobattrs['ppn'] ? $jobattrs['ppn'] : 1;
                $cpus        = $nodes * $ppn;
                $queued_time = (int) $jobattrs['queued_timestamp'];
                $start_time  = (int) $jobattrs['start_timestamp'];
                $runningtime = $report_time - $start_time;

                switch( $sortby ) 
                {
                    case "id":
                        $sorted[$jobid] = $jobid;
                        break;

                    case "state":
                        $sorted[$jobid] = $state;
                        break;

                    case "owner":
                        $sorted[$jobid] = $owner;
                        break;

                    case "queue":
                        $sorted[$jobid] = $queue;
                        break;

                    case "name":
                        $sorted[$jobid] = $name;
                        break;

                    case "req_cpu":
                        $sorted[$jobid] = timeToEpoch( $req_cpu );
                        break;

                    case "req_mem":
                        $sorted[$jobid] = $req_memory;
                        break;

                    case "nodes":
                        $sorted[$jobid] = $nodes;
                        break;

                    case "cpus":
                        $sorted[$jobid] = $cpus;
                        break;

                    case "queued":
                        $sorted[$jobid] = $queued_time;
                        break;

                    case "start":
                        $sorted[$jobid] = $start_time;
                        break;

                    case "runningtime":
                        $sorted[$jobid] = $runningtime;
                        break;

                    default:
                        break;
                }
        }
    }

    if( $sortorder == "asc" )
    {
        arsort( $sorted );
    }
    else if( $sortorder == "desc" )
    {
        asort( $sorted );
    }

    return $sorted;
}

function makeOverview() 
{
    global $dwoo, $tpl, $tpl_data, $jobs, $nodes, $heartbeat, $clustername, $tpl_data;
    global $sortorder, $sortby, $filter, $sh, $hc, $m, $range;
    global $cluster_url, $get_metric_string, $host_url, $metrics;
    global $start, $end, $reports, $gnodes, $default_showhosts;
    global $COLUMN_QUEUED, $COLUMN_REQUESTED_MEMORY, $COLUMN_NODES, $hostname;
    global $cluster, $use_fqdn;

    $metricname        = $m;
    if( isset($conf['default_metric']) and ($metricname =='') )
        $metricname = $conf['default_metric'];
    else
        if( isset( $m ) )
            $metricname = $m;
        else
            $metricname = "load_one";

    $tpl_data->assign("sortorder", $sortorder );
    $tpl_data->assign("sortby", $sortby );

    $sorted_jobs        = sortJobs( $jobs, $sortby, $sortorder );

    $even               = 1;

    $used_jobs          = 0;
    $used_cpus          = 0;
    $used_nodes         = 0;

    $queued_jobs        = 0;
    $queued_nodes       = 0;
    $queued_cpus        = 0;

    $total_nodes        = 0;
    $total_cpus         = 0;
    $total_jobs         = 0;

    $all_used_nodes     = array();
    $total_used_nodes   = array();

    $running_name_nodes = array();

    $running_nodes      = 0;
    $running_jobs       = 0;
    $running_cpus       = 0;

    $avail_nodes        = count( $gnodes );
    $avail_cpus         = cluster_sum("cpu_num", $metrics);

    $view_cpus          = 0;
    $view_jobs          = 0;
    $view_nodes         = 0;

    $all_nodes          = 0;
    $all_jobs           = 0;
    $all_cpus           = 0;

    $view_name_nodes    = array();

    // Is the "requested memory" column enabled in the config
    //
    if( $COLUMN_REQUESTED_MEMORY ) 
    {
        $tpl_data->assign( "column_header_req_mem", "yes" );
    }

    // Is the "nodes hostnames" column enabled in the config
    //
    if( $COLUMN_NODES ) 
    {
        $tpl_data->assign( "column_header_nodes", "yes" );
    }

    // Is the "queued time" column enabled in the config
    //
    if( $COLUMN_QUEUED ) 
    {
        $tpl_data->assign( "column_header_queued", "yes" );
    }

    $last_displayed_job = null;

    $rjqj_host = null;

    $na_nodes  = 0;
    $na_cpus   = 0;

    foreach( $metrics as $bhost => $bmetric )
    {
        foreach( $bmetric as $mname => $mval )
        {
            if( ( $mname == 'zplugin_monarch_rj' ) || ($mname == 'zplugin_monarch_qj') )
            {
                $rjqj_host = $bhost;
            }
        }
    }

    foreach( $gnodes as $ghost => $gnode )
    {
        if( $gnode->isDown() || $gnode->isOffline() )
        {
            $na_nodes += 1;
            $na_cpus  += $metrics[$ghost]['cpu_num']['VAL'];
        }
    }

    $node_list = array();

    foreach( $sorted_jobs as $jobid => $sortdec ) 
    {
        $report_time     = $jobs[$jobid]['reported'];

        if( $jobs[$jobid]['status'] == 'R' )
        {
            $nodes = count( $jobs[$jobid]['nodes'] );
        }
        else if( $jobs[$jobid]['status'] == 'Q' )
        {
            $nodes = $jobs[$jobid]['nodes'];
        }

        $ppn  = isset( $jobs[$jobid]['ppn'] ) ? $jobs[$jobid]['ppn'] : 1;
        $cpus = $nodes * $ppn;

        if( $report_time == $heartbeat ) 
        {
            $display_job    = 1;

            if( $jobs[$jobid]['status'] == 'R' ) 
            {
                foreach( $jobs[$jobid]['nodes'] as $tempnode ) 
                {
                    $all_used_nodes[] = $tempnode;
                }
            }

            $used_cpus += $cpus;

            if( $jobs[$jobid]['status'] == 'R' ) 
            {
                $running_cpus     += $cpus;

                $running_jobs++;

                $found_node_job    = 0;

                foreach( $jobs[$jobid]['nodes'] as $tempnode ) 
                {
                    $running_name_nodes[] = $tempnode;

                    if( isset( $hostname ) && $hostname != '' ) 
                    {
                        $domain_len     = 0 - strlen( $jobs[$jobid]['domain'] );
                        $hostnode     = $tempnode;

                        if( $use_fqdn == 1)
                        {
                            if( substr( $hostnode, $domain_len ) != $jobs[$jobid]['domain'] ) 
                            {
                                $hostnode = $hostnode. '.'. $jobs[$jobid]['domain'];
                            }
                        }

                        if( $hostname == $hostnode ) 
                        {
                            $found_node_job = 1;
                            $display_job = 1;
                        } 
                        else if( !$found_node_job ) 
                        {
                            $display_job = 0;
                        }
                    }
                }
            }

            if( $jobs[$jobid]['status'] == 'Q' ) 
            {
                if( isset( $hostname ) && $hostname != '' )
                {
                    $display_job = 0;
                }

                $queued_cpus  += $cpus;
                $queued_nodes += $nodes;

                $queued_jobs++;
            }

            foreach( $filter as $filtername=>$filtervalue ) 
            {
                if( $filtername == 'id' && $jobid != $filtervalue )
                {
                    $display_job = 0;
                }
                else if( $filtername == 'state' && $jobs[$jobid]['status'] != $filtervalue )
                {
                    $display_job = 0;
                }
                else if( $filtername == 'queue' && $jobs[$jobid]['queue'] != $filtervalue )
                {
                    $display_job = 0;
                }
                else if( $filtername == 'owner' && $jobs[$jobid]['owner'] != $filtervalue )
                {
                    $display_job = 0;
                }
            }


            if( $display_job ) 
            {
                $job_loop = array();
                $job_loop["clustername"] = $clustername;

                $job_loop["id"] = $jobid;

                $last_displayed_job     = $jobid;

                $job_loop["state"] = $jobs[$jobid]['status'];

                $fullstate         = '';

                if( $jobs[$jobid]['status'] == 'R' ) 
                {
                    $fullstate     = "Running";
                } 
                else if( $jobs[$jobid]['status'] == 'Q' ) 
                {
                    $fullstate     = "Queued";
                }

                $job_loop["fullstate"] = $fullstate;
                
                $job_loop["owner"] = $jobs[$jobid]['owner'];
                $job_loop["queue"] = $jobs[$jobid]['queue'];

                $fulljobname         = $jobs[$jobid]['name'];
                $shortjobname        = '';

                $job_loop["fulljobname"] = $fulljobname;

                $fulljobname_fields    = explode( ' ', $fulljobname );

                $capjobname        = 0;

                if( strlen( $fulljobname_fields[0] ) > 10 )
                {
                    $capjobname    = 1;
                }

                if( $capjobname ) 
                {
                    //$tpl_data->assign( "jobname_hint_start", "yes" );
                    $job_loop[ "jobname_hint_start" ] = "yes";

                    $shortjobname     = substr( $fulljobname, 0, 10 ) . '..';
                } 
                else 
                {
                    $shortjobname     = $fulljobname;
                }
                
                $job_loop["name"] = $shortjobname;

                if( $capjobname ) 
                {
                    //$tpl_data->assign( "jobname_hint_end", "yes" );
                    $job_loop[ "jobname_hint_end" ] = "yes";
                }

                $domain         = $jobs[$jobid]['domain'];

                $job_loop["req_cpu"] = makeTime( timeToEpoch( $jobs[$jobid]['requested_time'] ) );

                if( $COLUMN_REQUESTED_MEMORY ) 
                {
                    //$tpl_data->assign( "column_req_mem", "yes" );
                    $job_loop[ "column_req_mem" ] = "yes";
                    $job_loop["req_memory"] = $jobs[$jobid]['requested_memory'];
                }


                if( $COLUMN_QUEUED ) 
                {
                    //$tpl_data->assign( "column_queued", "yes" );
                    $job_loop[ "column_queued" ] = "yes";
                    $job_loop["queued"] = makeDate( $jobs[$jobid]['queued_timestamp'] );
                }
                if( $COLUMN_NODES ) 
                {
                    //$tpl_data->assign( "column_nodes", "yes" );
                    $job_loop[ "column_nodes" ] = "yes";
                    //echo "colum nodes";
                }

                $ppn       = isset( $jobs[$jobid]['ppn'] ) ? $jobs[$jobid]['ppn'] : 1;
                $cpus      = $nodes * $ppn;

                $job_loop["nodes"] = $nodes;
                $job_loop["cpus"] = $cpus;

                $start_time= (int) $jobs[$jobid]['start_timestamp'];
                $job_start = $start_time;


                $view_cpus += $cpus;

                $view_jobs++;

                if( $jobs[$jobid]['status'] == 'R' ) 
                {
                    if( $rjqj_start == null ) 
                    {
                        $rjqj_start = $start_time;
                    }
                    else if( $start_time < $rjqj_start )
                    {
                        $rjqj_start = $start_time;
                    }

                    foreach( $jobs[$jobid]['nodes'] as $tempnode )
                    {
                        $view_name_nodes[]     = $tempnode;
                    }

                    if( $COLUMN_NODES ) 
                    {
                        //$tpl_data->assign( "column_nodes", "yes" );
                        $job_loop[ "column_nodes" ] = "yes";

                        $mynodehosts         = array();

                        foreach( $jobs[$jobid]['nodes'] as $shortnode ) 
                        {
                            if( $use_fqdn == 1)
                            {
                                $mynode     = $shortnode.".".$jobs[$jobid]['domain'];
                            }
                            $myhost_href    = "./?c=".$clustername."&h=".$mynode;
                            $mynodehosts[]  = "<A HREF=\"".$myhost_href."\">".$shortnode."</A>";
                        }

                        $nodes_hostnames    = implode( " ", $mynodehosts );

                        $job_loop["nodes_hostnames"] = $nodes_hostnames;
                    }
                } 
                else if( $jobs[$jobid]['status'] == 'Q' ) 
                {
                    $view_nodes     += (int) $jobs[$jobid]['nodes'];
                }

                if( $even ) 
                {
                    $job_loop["nodeclass"] = "even";

                    $even         = 0;
                } 
                else 
                {
                    $job_loop["nodeclass"] = "odd";

                    $even         = 1;
                }

                if( $start_time ) 
                {
                    $runningtime        = makeTime( $report_time - $start_time );
                    $job_runningtime    = $heartbeat - $start_time;

                    $job_loop["started"] = makeDate( $start_time );
                    $job_loop["runningtime"] = $runningtime;
                }
                $node_list[] = $job_loop;
            }
            $tpl_data->assign("node_list", $node_list );
        }
    }
    // Running / queued amount jobs graph
    //
    if( $rjqj_host != null )
    {
        $rjqj_graphargs = "?z=medium&c=$clustername&g=job_report&r=$range";
        if( $range == 'job' )
        {
            $rjqj_end = time();
            $rjqj_graphargs .= "&period_start=$rjqj_start&period_stop=$rjqj_end";
        }
        else
        {
            $rjqj_graphargs .= "&st=$cluster[LOCALTIME]";
        }

        $rjqj_str  = "<A HREF=\"./graph.php$rjqj_graphargs\">";
        $rjqj_str .= "<IMG BORDER=0 SRC=\"./graph.php$rjqj_graphargs\" WIDTH=381 HEIGHT=137>";
        $rjqj_str .= "</A>";

        $tpl_data->assign( "rjqj_graph", $rjqj_str );
    }

    $all_used_nodes     = array_unique( $all_used_nodes );
    $view_name_nodes    = array_unique( $view_name_nodes );
    $running_name_nodes = array_unique( $running_name_nodes );

    $used_nodes         = count( $all_used_nodes );
    $view_nodes        += count( $view_name_nodes );
    $running_nodes     += count( $running_name_nodes );

    $total_nodes        = $queued_nodes + $running_nodes;
    $total_cpus         = $queued_cpus + $running_cpus;
    $total_jobs         = $queued_jobs + $running_jobs;

    $free_nodes         = $avail_nodes - $running_nodes - $na_nodes;
    $free_nodes         = ( $free_nodes >= 0 ) ? $free_nodes : 0;
    $free_cpus          = $avail_cpus - $running_cpus - $na_cpus;
    $free_cpus          = ( $free_cpus >= 0 ) ? $free_cpus : 0;

    $tpl_data->assign( "avail_nodes", $avail_nodes );
    $tpl_data->assign( "avail_cpus", $avail_cpus );

    $tpl_data->assign( "queued_nodes", $queued_nodes );
    $tpl_data->assign( "queued_jobs", $queued_jobs );
    $tpl_data->assign( "queued_cpus", $queued_cpus );

    // Only display "Unavailable" in count overview there are any
    //
    if( $na_nodes > 0 )
    {
        $tpl_data->assign( "na_nodes", "yes");

        $tpl_data->assign( "na_nodes", $na_nodes );
        $tpl_data->assign( "na_cpus", $na_cpus );
    }

    $tpl_data->assign( "total_nodes", $total_nodes );
    $tpl_data->assign( "total_jobs", $total_jobs );
    $tpl_data->assign( "total_cpus", $total_cpus );

    $tpl_data->assign( "running_nodes", $running_nodes );
    $tpl_data->assign( "running_jobs", $running_jobs );
    $tpl_data->assign( "running_cpus", $running_cpus );

    $tpl_data->assign( "used_nodes", $used_nodes );
    $tpl_data->assign( "used_jobs", $used_jobs );
    $tpl_data->assign( "used_cpus", $used_cpus );

    $tpl_data->assign( "free_nodes", $free_nodes );
    $tpl_data->assign( "free_cpus", $free_cpus );

    $tpl_data->assign( "view_nodes", $view_nodes );
    $tpl_data->assign( "view_jobs", $view_jobs );
    $tpl_data->assign( "view_cpus", $view_cpus );

    $tpl_data->assign( "report_time", makeDate( $heartbeat) );

    if( intval($view_jobs) == 1 and $start_time )
    {
        if( $last_displayed_job != null )
        {
            $filter['id'] = $last_displayed_job;
        }
    }

    global $longtitle, $title;

    $longtitle = "Batch Report :: Powered by Job Monarch!";
    $title = "Batch Report";

    makeHeader( 'overview', $title, $longtitle );

    setupFilterSettings();

    if( intval($view_jobs) == 1 and $start_time ) 
    {
        $tpl_data->assign( "showhosts", "yes" );

        # Present a width list
        $cols_menu     = "<SELECT NAME=\"hc\" OnChange=\"toga_form.submit();\">\n";

        $hostcols     = ($hc) ? $hc : 4;

        foreach( range( 1, 25 ) as $cols ) 
        {
            $cols_menu    .= "<OPTION VALUE=$cols ";

            if ($cols == $hostcols)
            {
                $cols_menu    .= "SELECTED";
            }
            $cols_menu    .= ">$cols\n";
        }
        $cols_menu     .= "</SELECT>\n";

        $tpl_data->assign( "metric","$metricname $units" );
        $tpl_data->assign( "id", $filter['id'] );

        # Host columns menu defined in header.php
        $tpl_data->assign( "cols_menu", $cols_menu );

        $showhosts     = isset($sh) ? $sh : $default_showhosts;

        $tpl_data->assign( "checked$showhosts", "checked" );

        $sorted_list = array();

        if( $showhosts ) 
        {
            if( !isset( $start ) ) 
            {
                $start    ="jobstart";
            }
            if( !isset( $stop ) ) 
            {
                $stop    ="now";
            }

            $sorted_hosts = array();
            $hosts_up     = $jobs[$filter['id']]['nodes'];

            $r            = intval($job_runningtime * 1.2);

            $jobrange     = -$r ;
            $jobstart     = $start_time;

            if ( $reports[$metricname] )
            {
                $metricval     = "g";
            }
            else
            {
                $metricval    = "m";
            }
                
            foreach ( $hosts_up as $host ) 
            {
                $domain_len         = 0 - strlen( $domain );

                if( $use_fqdn )
                {
                    if( substr( $host, $domain_len ) != $domain ) 
                    {
                        $host         = $host . '.' . $domain;
                    }
                }
                $cpus             = 0;

                $cpus             = $metrics[$host]["cpu_num"]["VAL"];

                if( $cpus == 0 )
                {
                    $cpus        = 1;
                }

                $load_one         = $metrics[$host]["load_one"]['VAL'];
                $load             = ((float) $load_one) / $cpus;
                $host_load[$host] = $load;

                $percent_hosts[load_color($load)] ++;

                if ($metricname=="load_one")
                {
                    $sorted_hosts[$host]     = $load;
                }
                else
                {
                    $sorted_hosts[$host]     = $metrics[$host][$metricname]['VAL'];
                }
            }

            switch ( $sort ) 
            {
                case "descending":
                    arsort( $sorted_hosts );
                    break;

                case "by hostname":
                    ksort( $sorted_hosts );
                    break;

                case "ascending":
                    asort( $sorted_hosts );
                    break;

                default:
                    break;
            }

            // First pass to find the max value in all graphs for this
            // metric. The $start,$end variables comes from get_context.php,
            // included in index.php.
            //
            list($min, $max) = find_limits($sorted_hosts, $metricname);

            // Second pass to output the graphs or metrics.
            $i = 1;

            $metric_loop = array();
            foreach ( $sorted_hosts as $host=>$value  ) 
            {
                $host_url    = rawurlencode( $host );
                $cluster_url = rawurlencode( $clustername );

                $textval     = "";

                $val         = $metrics[$host][$metricname];
                $class       = "metric";

                if ( $val["TYPE"] == "timestamp" || $always_timestamp[$metricname] ) 
                {
                    $textval     = date( "r", $val["VAL"] );
                } 
                elseif ( $val["TYPE"] == "string" || $val["SLOPE"] == "zero" || $always_constant[$metricname] || ($max_graphs > 0 and $i > $max_graphs ))
                {
                    $textval     = $val["VAL"] . " " . $val["UNITS"];
                } 
                else 
                {
                    $job_start     = $jobs[$last_displayed_job]['start_timestamp'];
                    $period_end    = time();
                    $runningtime   = time() - intval( $job_start );
                    $load_color    = load_color($host_load[$host]);
                    $period_start  = intval( $job_start - (intval( $runningtime * 0.10 ) ) );
                    //printf("last job %s job start %s runningtime %s period start %s", $last_displayed_job, $jobstart, $job_runningtime, $period_start);
                    $graphargs     = ($reports[$metricname]) ? "g=$metricname&" : "m=$metricname&";
                    $graphargs    .= "z=overview-medium&c=$cluster_url&r=$range&h=$host_url&l=$load_color&v=".$val['VAL']."&job_start=$job_start";
                    $host_link     = "?j_view=overview-host&c=$cluster_url&r=$range&h=$host_url&job_start=$jobstart";

                    if( $range == 'job' )
                    {
                        $graphargs     .= "&period_start=$period_start&period_stop=$period_end";
                        $host_link     .= "&period_start=$period_start&period_stop=$period_end";
                    }
                    else
                    {
                        $graphargs     .= "&st=$period_end";
                        $host_link     .= "&st=$period_end";
                    }
                    if( $max > 0 ) 
                    {
                        $graphargs    .= "&x=$max&n=$min";
                    }
                }
                if ($textval) 
                {
                    $cell    = "<td class=$class>".  "<b><a href=$host_link>$host</a></b><br>".  "<i>$metricname:</i> <b>$textval</b></td>";
                } else {
                    $cell    = "<A HREF=\"$host_link\">" . "<IMG SRC=\"./graph.php?$graphargs\" " . "ALT=\"$host\" BORDER=0  WIDTH=381 HEIGHT=148></A>";
                }

                $metric_loop["metric_image"] = $cell;

                //if(! ($i++ % $hostcols) )
                //{
                //     $metric_loop["br"] = "</tr><tr>";
                //}
                $sorted_list[] = $metric_loop;
            }
            $tpl_data->assign("sorted_list", $sorted_list );
        }
    }
    $dwoo->output($tpl, $tpl_data);
}

?>
