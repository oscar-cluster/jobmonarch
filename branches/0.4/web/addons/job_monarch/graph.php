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

global $rrds, $range, $start, $r, $conf, $m;
$range = $r;

include "./libtoga.php";

$my_dir = getcwd();

global $context;

$context = 'cluster';

chdir( $GANGLIA_PATH );

include "./ganglia.php";
include "./get_ganglia.php";

chdir( $my_dir );

if ( !empty( $_GET ) ) 
{
        extract( $_GET );
}

$sourcetime = $st;

# Graph specific variables
$size = escapeshellcmd( rawurldecode( $_GET["z"] ));
$graph = escapeshellcmd( rawurldecode( $_GET["g"] ));
$grid = escapeshellcmd( rawurldecode( $_GET["G"] ));
$self = escapeshellcmd( rawurldecode( $_GET["me"] ));
$max = escapeshellcmd( rawurldecode( $_GET["x"] ));
$min = escapeshellcmd( rawurldecode( $_GET["n"] ));
$value = escapeshellcmd( rawurldecode( $_GET["v"] ));
$load_color = escapeshellcmd( rawurldecode( $_GET["l"] ));
$vlabel = escapeshellcmd( rawurldecode( $_GET["vl"] ));
$j_title = escapeshellcmd( rawurldecode( $_GET["t"] ));

$cluster = $c;
$metricname = ($g) ? $g : $m;
$hostname = $h;

# Assumes we have a $start variable (set in get_context.php).
if ($size == "small") 
{
    $height = 40;
    $width = 130;
} 
else if ($size == "medium") 
{
    $height = 75;
    $width = 300;
} 
else if ($size == "overview-medium") 
{
    $height = 75;
    $width = 300;
} 
else 
{
    $height = 100;
    $width = 400;
}

$jobstart_color = "FF0000";

if($command) 
{
      $command = '';
}

$graph = $metricname;

$rrd_dirs = Array();

if (isset($graph)) 
{
    $series = '';
    if( $size == 'overview-medium' )
    {
        $rrd_dirs[] = $conf['rrds'] . '/' . $cluster .'/'. $hostname;
    }
    else
    {
        $trd = new TarchRrdGraph( $cluster, $hostname );
        $rrd_dirs = $trd->getRrdDirs( $period_start, $period_stop );
    }

    if($graph == "cpu_report") 
    {

        $style = "CPU";

        $upper_limit = "--upper-limit 100 --rigid";
        $lower_limit = "--lower-limit 0";

        $vertical_label = "--vertical-label Percent ";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            if( $def_nr == 0 ) 
            {

                $user_str = ":'User CPU'";
                $nice_str = ":'Nice CPU'";
                $system_str = ":'System CPU'";
                $wio_str = ":'WAIT CPU'";
                $idle_str = ":'Idle CPU'";
            } 
            else 
            {

                $user_str = "";
                $nice_str = "";
                $system_str = "";
                $wio_str = "";
                $idle_str = "";
            }

            $series .= "DEF:'cpu_user${def_nr}'='${rrd_dir}/cpu_user.rrd':'sum':AVERAGE "
                ."DEF:'cpu_nice${def_nr}'='${rrd_dir}/cpu_nice.rrd':'sum':AVERAGE "
                ."DEF:'cpu_system${def_nr}'='${rrd_dir}/cpu_system.rrd':'sum':AVERAGE "
                ."DEF:'cpu_idle${def_nr}'='${rrd_dir}/cpu_idle.rrd':'sum':AVERAGE "
                ."AREA:'cpu_user${def_nr}'#".$conf['cpu_user_color']."${user_str} "
                ."STACK:'cpu_nice${def_nr}'#".$conf['cpu_nice_color']."${nice_str} "
                ."STACK:'cpu_system${def_nr}'#".$conf['cpu_system_color']."${system_str} ";

            if (file_exists("$rrd_dir/cpu_wio.rrd")) 
            {
                $series .= "DEF:'cpu_wio${def_nr}'='${rrd_dir}/cpu_wio.rrd':'sum':AVERAGE "
                    ."STACK:'cpu_wio${def_nr}'#".$conf['cpu_wio_color']."${wio_str} ";
            }

            $series .= "STACK:'cpu_idle${def_nr}'#".$conf['cpu_idle_color']."${idle_str} ";

            $def_nr++;
        }

    } 
    else if ($graph == "job_report") 
    {
        $style = "Jobs";

        $lower_limit = "--lower-limit 0 --rigid";
        $vertical_label = "--vertical-label Jobs";

        $def_nr = 0;

        foreach( $metrics as $bhost => $bmetric )
        {
            foreach( $bmetric as $mname => $mval )
            {
                if( ( $mname == 'zplugin_monarch_rj' ) || ($mname == 'zplugin_monarch_qj') )
                {
                    $rjqj_host      = $bhost;
                }
            }
        }

        $rrd_dir = $conf['rrds'] . "/$clustername/$rjqj_host/";

        $rj_rrd    = $rrd_dir . "zplugin_monarch_rj.rrd";
        $qj_rrd    = $rrd_dir . "zplugin_monarch_qj.rrd";

        $sorted_hosts    = array();
        $sorted_hosts[]  = $rjqj_host;

        $rj_str = ":'Running Jobs'";
        $qj_str = ":'Queued Jobs'";

        $series .= "DEF:'running_jobs'='${rj_rrd}':'sum':AVERAGE "
            ."DEF:'queued_jobs'='${qj_rrd}':'sum':AVERAGE "

        
            ."LINE3:'running_jobs'#ff0000${rj_str} "
            ."LINE3:'queued_jobs'#999999${qj_str} ";

    } 
    else if ($graph == "mem_report") 
    {
        $style = "Memory";

        $lower_limit = "--lower-limit 0 --rigid";
        $extras = "--base 1024";
        $vertical_label = "--vertical-label Bytes";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            if( $def_nr == 0 ) 
            {

                $memuse_str = ":'Memory Used'";
                $memshared_str = ":'Memory Shared'";
                $memcached_str = ":'Memory Cached'";
                $membuff_str = ":'Memory Buffered'";
                $memswap_str = ":'Memory Swapped'";
                $total_str = ":'Total In-Core Memory'";
            } 
            else 
            {

                $memuse_str = "";
                $memshared_str = "";
                $memcached_str = "";
                $membuff_str = "";
                $memswap_str = "";
                $total_str = "";
            }

            $series .= "DEF:'mem_total${def_nr}'='${rrd_dir}/mem_total.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_total${def_nr}'=mem_total${def_nr},1024,* "
                ."DEF:'mem_shared${def_nr}'='${rrd_dir}/mem_shared.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_shared${def_nr}'=mem_shared${def_nr},1024,* "
                ."DEF:'mem_free${def_nr}'='${rrd_dir}/mem_free.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_free${def_nr}'=mem_free${def_nr},1024,* "
                ."DEF:'mem_cached${def_nr}'='${rrd_dir}/mem_cached.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_cached${def_nr}'=mem_cached${def_nr},1024,* "
                ."DEF:'mem_buffers${def_nr}'='${rrd_dir}/mem_buffers.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_buffers${def_nr}'=mem_buffers${def_nr},1024,* "
                ."CDEF:'bmem_used${def_nr}'='bmem_total${def_nr}','bmem_shared${def_nr}',-,'bmem_free${def_nr}',-,'bmem_cached${def_nr}',-,'bmem_buffers${def_nr}',- "
                ."AREA:'bmem_used${def_nr}'#".$conf['mem_used_color']."${memuse_str} "
                ."STACK:'bmem_shared${def_nr}'#".$conf['mem_shared_color']."${memshared_str} "
                ."STACK:'bmem_cached${def_nr}'#".$conf['mem_cached_color']."${memcached_str} "
                ."STACK:'bmem_buffers${def_nr}'#".$conf['mem_buffered_color']."${membuff_str} ";

            if (file_exists("$rrd_dir/swap_total.rrd")) 
            {
                $series .= "DEF:'swap_total${def_nr}'='${rrd_dir}/swap_total.rrd':'sum':AVERAGE "
                    ."DEF:'swap_free${def_nr}'='${rrd_dir}/swap_free.rrd':'sum':AVERAGE "
                    ."CDEF:'bmem_swapped${def_nr}'='swap_total${def_nr}','swap_free${def_nr}',-,1024,* "
                    ."STACK:'bmem_swapped${def_nr}'#".$conf['mem_swapped_color']."${memswap_str} ";
            }

            $series .= "LINE2:'bmem_total${def_nr}'#".$conf['cpu_num_color']."${total_str} ";

            $def_nr++;
        }

    } 
    else if ($graph == "load_report") 
    {
        $style = "Load";

        $lower_limit = "--lower-limit 0 --rigid";
        $vertical_label = "--vertical-label 'Load/Procs'";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            if( $def_nr == 0 ) 
            {

                $load_str = ":'1-min Load'";
                $cpu_str = ":'CPUs'";
                $run_str = ":'Running Processes'";
            } 
            else 
            {
                $load_str = "";
                $cpu_str = "";
                $run_str = "";
            }

            $series .= "DEF:'load_one${def_nr}'='${rrd_dir}/load_one.rrd':'sum':AVERAGE "
                ."DEF:'proc_run${def_nr}'='${rrd_dir}/proc_run.rrd':'sum':AVERAGE "
                ."DEF:'cpu_num${def_nr}'='${rrd_dir}/cpu_num.rrd':'sum':AVERAGE ";
            $series .="AREA:'load_one${def_nr}'#".$conf['load_one_color']."${load_str} ";
            $series .="LINE2:'cpu_num${def_nr}'#".$conf['cpu_num_color']."${cpu_str} ";
            $series .="LINE2:'proc_run${def_nr}'#".$conf['proc_run_color']."${run_str} ";

            $def_nr++;
        }

    } 
    else if ($graph == "network_report") 
    {
        $style = "Network";

        $lower_limit = "--lower-limit 0 --rigid";
        $extras = "--base 1024";
        $vertical_label = "--vertical-label 'Bytes/sec'";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            if( $def_nr == 0 ) 
            {

                $in_str = ":'In'";
                $out_str = ":'Out'";
            } 
            else 
            {

                $in_str = "";
                $out_str = "";
            }

            $series .= "DEF:'bytes_in${def_nr}'='${rrd_dir}/bytes_in.rrd':'sum':AVERAGE "
                ."DEF:'bytes_out${def_nr}'='${rrd_dir}/bytes_out.rrd':'sum':AVERAGE "
                ."LINE2:'bytes_in${def_nr}'#".$conf['mem_cached_color']."${in_str} "
                ."LINE2:'bytes_out${def_nr}'#".$conf['mem_used_color']."${out_str} ";

            $def_nr++;
        }

    } 
    else if ($graph == "packet_report") 
    {
        $style = "Packets";

        $lower_limit = "--lower-limit 0 --rigid";
        $extras = "--base 1024";
        $vertical_label = "--vertical-label 'Packets/sec'";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            if( $def_nr == 0 ) 
            {

                $in_str = ":'In'";
                $out_str = ":'Out'";
            } 
            else 
            {

                $in_str = "";
                $out_str = "";
            }

            $series .= "DEF:'bytes_in${def_nr}'='${rrd_dir}/pkts_in.rrd':'sum':AVERAGE "
                ."DEF:'bytes_out${def_nr}'='${rrd_dir}/pkts_out.rrd':'sum':AVERAGE "
                ."LINE2:'bytes_in${def_nr}'#".$conf['mem_cached_color']."${in_str} "
                ."LINE2:'bytes_out${def_nr}'#".$conf['mem_used_color']."${out_str} ";

            $def_nr++;
        }

    } 
    else 
    {
        /* Custom graph */
        $style = "";

        $subtitle = $metricname;
        if($context == "host")
        {
            if ($size == "small")
                $prefix = $metricname;
            else
                $prefix = $hostname;

            $value = $value>1000 ? number_format($value) : number_format($value, 2);
        }

        if (is_numeric($max))
            $upper_limit = "--upper-limit '$max' ";
        if (is_numeric($min))
            $lower_limit ="--lower-limit '$min' ";

        if ($vlabel)
        {
            $vertical_label = "--vertical-label '$vlabel'";
        }
        else 
        {
            if ($upper_limit or $lower_limit) 
            {
                $max = $max>1000 ? number_format($max) : number_format($max, 2);
                $min = $min>0 ? number_format($min,2) : $min;

                $vertical_label ="--vertical-label '$min - $max' ";
            }
        }

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            if( $def_nr == 0 ) 
            {
                $title_str = ":'${subtitle}'";
            } 
            else 
            {
                $title_str = "";
            }

            $rrd_file = "$rrd_dir/$metricname.rrd";
            $series .= "DEF:'sum${def_nr}'='$rrd_file':'sum':AVERAGE "
                ."AREA:'sum${def_nr}'#".$conf['default_metric_color']."${title_str} ";

            $def_nr++;
        }

    }
    if( $series != '' ) 
    {
        if ($job_start)
            $series .= "VRULE:${job_start}#${jobstart_color} ";
        if ($job_stop)
            $series .= "VRULE:${job_stop}#${jobstart_color} ";
    }
}

if ($graph == "job_report")
{
    $title = "Last: $j_title";
}
else
{
    $title = "$hostname";
}

function determineXGrid( $p_start, $p_stop ) 
{

    $period = intval( $p_stop - $p_start );

    // Syntax: <minor_grid_lines_time_declr>:<major_grid_lines_time_declr>:<labels_time_declr>:<offset>:<format>
    //
    // Where each <*time_declr*> = <time_type>:<time_interval>

    //$my_lines1 = intval( $period / 3.0 );
    //$my_lines2 = intval( $period / 6.0 );

    //$my_grid = "SECOND:$my_lines2:SECOND:$my_lines1:SECOND:$my_lines1:0:%R";

    //return "--x-grid $my_grid";

    // Less than 1 minute
    if( $period < 60 ) 
    {

        $tm_formt = "%X";
        $my_grid = "SECOND:15:SECOND:30:SECOND:30:0:$tm_formt";

    // Less than 10 minutes
    } 
    else if( $period < 600 ) 
    {

        $tm_formt = "%R";
        $my_grid = "MINUTE:1:MINUTE:3:MINUTE:3:0:$tm_formt";

    // Less than 1 hour
    } 
    else if( $period < 3600 ) 
    {

        $tm_formt = "%R";
        $my_grid = "MINUTE:5:MINUTE:15:MINUTE:15:0:$tm_formt";

    // Less than 15 hour
    } 
    else if( $period < 3600 ) 
    {

        $tm_formt = "%R";
        $my_grid = "HOUR:1:HOUR:2:HOUR:2:0:$tm_formt";

    // Less than 1 day
    //
    } 
    else if( $period < 86400 ) 
    {

        $tm_formt = "%R";
        $my_grid = "HOUR:2:HOUR:5:HOUR:5:0:$tm_formt";

    // Less than 15 days
    //
    } 
    else if( $period < 1296000 ) 
    {

        $tm_formt = "%e-%m";
        $my_grid = "HOUR:1:DAY:3:DAY:3:0:'$tm_formt'";
        
    // Less than 30 days (a month)
    //
    } 
    else if( $period < 2592000 ) 
    {

        $tm_formt = "%e-%m";
        $my_grid = "DAY:5:DAY:10:DAY:10:0:'$tm_formt'";
    }

    if( isset( $my_grid ) ) 
    {

        $ret_str = "--x-grid $my_grid";
        return array($ret_str,$tm_formt);

    } 
    else 
    {
        return array( "", "" );
    }
}

$lower_limit = "--lower-limit 0";

if( !isset( $load_color ) or ( $load_color == '') )
{
    $load_color = 'FFFFFF';
}

# Calculate time range.
if ( isset($sourcetime) )
{
    $end = $sourcetime;
    # Get_context makes start negative.
    $start = $sourcetime + $start;

    # Fix from Phil Radden, but step is not always 15 anymore.
    if ($range=="month")
    {
        $end = floor($end / 672) * 672;
    }
        $command = $conf['rrdtool']. " graph - --start $start --end $end ".
                "--width $width --height $height $lower_limit ".
                "--title '$title' $extras $background ".
                $series;
}
else
{
    $command = $conf['rrdtool'] . " graph - --start $period_start --end $period_stop ".
               "--width $width --height $height $lower_limit --color BACK#$load_color ".
               "--title '$title' $extras $background ".
               $series;
}

$debug=0;


# Did we generate a command?   Run it.
if($command) 
{
    /*Make sure the image is not cached*/
    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
    header ("Cache-Control: no-cache, must-revalidate");   // HTTP/1.1
    header ("Pragma: no-cache");                     // HTTP/1.0
    if ($debug) 
    {
        header ("Content-type: text/html");
        print "$command\n\n\n\n\n";
    } 
    else 
    {
        header ("Content-type: image/gif");
        passthru($command);
    }
}
?>
