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

global $rrds, $start, $r, $conf, $m;

include "./libtoga.php";

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
$hostname = escapeshellcmd( rawurldecode( $_GET["h"] ));
$range = escapeshellcmd( rawurldecode( $_GET["r"] ));

if( strpos( $size, 'overview' ) )
{
    $my_dir = getcwd();

    global $context;

    $context = 'host';

    chdir( $GANGLIA_PATH );

    include "./ganglia.php";
    include "./get_ganglia.php";

    chdir( $my_dir );
}

if ( !empty( $_GET ) ) 
{
        extract( $_GET );
}

$sourcetime = $st;


$cluster = $c;
$metricname = ($g) ? $g : $m;

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

// RB: Perform some formatting/spacing magic.. tinkered to fit
//
if ($size == 'small') {
   $eol1 = '\\l';
   $space1 = ' ';
   $space2 = '         ';
} else if ($size == 'medium') {
   $eol1 = '';
   $space1 = '';
   $space2 = '';
   $extras = ' --font LEGEND:7 ';
} else if ($size == 'overview-medium') {
   $eol1   = '';
   $space1 = '';
   $space2 = '';
   $extras = ' --font LEGEND:7 ';
} else if ($size == 'large') {
   $eol1 = '';
   $space1 = '       ';
   $space2 = '       ';
} else if ($size == 'xlarge') {
   $eol1 = '';
   $space1 = '             ';
   $space2 = '             ';
} else if ($size == 'mobile') {
   $eol1 = '';
   $space1 = ' ';
   $space2 = '';
}

$jobstart_color = "3AE302";
$jobstop_color = "F5164A";

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

        $title = "CPU: $hostname";

        $upper_limit = "--upper-limit 100 --rigid";
        $lower_limit = "--lower-limit 0";

        $vertical_label = "--vertical-label Percent ";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            $series .= "DEF:'cpu_user${def_nr}'='${rrd_dir}/cpu_user.rrd':'sum':AVERAGE "
                ."DEF:'cpu_nice${def_nr}'='${rrd_dir}/cpu_nice.rrd':'sum':AVERAGE "
                ."DEF:'cpu_system${def_nr}'='${rrd_dir}/cpu_system.rrd':'sum':AVERAGE "
                ."DEF:'cpu_idle${def_nr}'='${rrd_dir}/cpu_idle.rrd':'sum':AVERAGE "
                ."DEF:'cpu_wio${def_nr}'='${rrd_dir}/cpu_wio.rrd':'sum':AVERAGE ";

            $report_names = array( "user", "nice", "system", "wio", "idle" );

            if( $conf['graphreport_stats'] )
            {
                foreach( $report_names as $r )
                {
                    $series .= "CDEF:cpu_${r}${def_nr}_nonans=cpu_${r}${def_nr},UN,0,cpu_${r}${def_nr},IF ";
                }
            }

            $def_nr++;
        }

        if( $conf['graphreport_stats'] )
        {
            $s_last     = $def_nr - 1;
            $user_sum   = "CDEF:cpu_user=cpu_user0_nonans";
            $nice_sum   = "CDEF:cpu_nice=cpu_nice0_nonans";
            $system_sum = "CDEF:cpu_system=cpu_system0_nonans";
            $wio_sum    = "CDEF:cpu_wio=cpu_wio0_nonans";
            $idle_sum   = "CDEF:cpu_idle=cpu_idle0_nonans";

            if( $s_last > 1 )
            {
                foreach (range(1, ($s_last)) as $print_nr ) 
                {
                    $user_sum   .= ",cpu_user{$print_nr}_nonans,+";
                    $nice_sum   .= ",cpu_nice{$print_nr}_nonans,+";
                    $system_sum .= ",cpu_system{$print_nr}_nonans,+";
                    $wio_sum    .= ",cpu_wio{$print_nr}_nonans,+";
                    $idle_sum   .= ",cpu_idle{$print_nr}_nonans,+";
                }
            }

            $user_sum .= " ";
            $nice_sum .= " ";
            $system_sum .= " ";
            $wio_sum .= " ";
            $idle_sum .= " ";

            $series .= $user_sum . $nice_sum . $system_sum . $wio_sum . $idle_sum;


            $r_count = 0;

            foreach( $report_names as $r )
            {
                $legend_str = ucfirst( $r );

                if( $r_count == 0 )
                {
                    $graph_str = "AREA";
                }
                else
                {
                    $graph_str = "STACK";
                }
                foreach (range(0, ($s_last)) as $print_nr ) 
                {

                    $series .= "${graph_str}:'cpu_${r}${print_nr}'#".$conf['cpu_'.${r}.'_color'].":'${legend_str}\g' ";
                    $legend_str = '';
                }

                $series .= "VDEF:'${r}_last'=cpu_${r},LAST ";
                $series .= "VDEF:'${r}_min'=cpu_${r},MINIMUM ";
                $series .= "VDEF:'${r}_avg'=cpu_${r},AVERAGE ";
                $series .= "VDEF:'${r}_max'=cpu_${r},MAXIMUM ";

                $spacefill = '';

                $spacesize = 6-strlen($r);
                foreach ( range( 0, $spacesize ) as $whatever )
                {
                    $spacefill .= ' ';
                }

                $series .= "GPRINT:'${r}_last':'${spacefill}Last\:%6.1lf%%' "
                        . "GPRINT:'${r}_min':'${space1}Min\:%6.1lf%%${eol1}' "
                        . "GPRINT:'${r}_avg':'${space2}Avg\:%6.1lf%%' "
                        . "GPRINT:'${r}_max':'${space1}Max\:%6.1lf%%\\l' ";
                        
                $r_count = $r_count + 1;
            }
        }
        else
        {
            $series .= "AREA:'cpu_user${def_nr}'#".$conf['cpu_user_color']."${user_str} "
                    ."STACK:'cpu_nice${def_nr}'#".$conf['cpu_nice_color']."${nice_str} "
                    ."STACK:'cpu_system${def_nr}'#".$conf['cpu_system_color']."${system_str} "
                    ."STACK:'cpu_wio${def_nr}'#".$conf['cpu_wio_color']."${wio_str} "
                    ."STACK:'cpu_idle${def_nr}'#".$conf['cpu_idle_color']."${idle_str} ";
        }

    } 
    else if ($graph == "job_report") 
    {
        $title = "Jobs";

        $lower_limit = "--lower-limit 0 --rigid";
        $vertical_label = "--vertical-label Jobs";

        $def_nr = 0;

        $rrd_dir = $conf['rrds'] . "/$clustername/$hostname/";

        $rj_rrd    = $rrd_dir . "zplugin_monarch_rj.rrd";
        $qj_rrd    = $rrd_dir . "zplugin_monarch_qj.rrd";

        $sorted_hosts    = array();
        $sorted_hosts[]  = $rjqj_host;

        $rj_str = ":'Running Jobs'";
        $qj_str = ":'Queued Jobs'";

        $series .= "DEF:'running_jobs'='${rj_rrd}':'sum':AVERAGE "
            ."DEF:'queued_jobs'='${qj_rrd}':'sum':AVERAGE ";

        
        $series .= "LINE3:'running_jobs'#ff0000${rj_str} ";

        if ( $conf['graphreport_stats'] ) 
        {
            $series .= "CDEF:running_pos=running_jobs,0,INF,LIMIT "
                    . "VDEF:running_last=running_pos,LAST "
                    . "VDEF:running_min=running_pos,MINIMUM "
                    . "VDEF:running_avg=running_pos,AVERAGE "
                    . "VDEF:running_max=running_pos,MAXIMUM "
                    . "GPRINT:'running_last':' ${space1}Last\:%5.0lf' "
                    . "GPRINT:'running_min':'${space1}Min\:%5.0lf${eol1}' "
                    . "GPRINT:'running_avg':'${space2}Avg\:%5.0lf' "
                    . "GPRINT:'running_max':'${space1}Max\:%5.0lf\\l' ";
        }

        $series .= "LINE3:'queued_jobs'#999999${qj_str} ";

        if ( $conf['graphreport_stats'] ) 
        {
            $series .= "CDEF:queued_pos=queued_jobs,0,INF,LIMIT "
                    . "VDEF:queued_last=queued_pos,LAST "
                    . "VDEF:queued_min=queued_pos,MINIMUM "
                    . "VDEF:queued_avg=queued_pos,AVERAGE "
                    . "VDEF:queued_max=queued_pos,MAXIMUM "
                    . "GPRINT:'queued_last':'  ${space1}Last\:%5.0lf' "
                    . "GPRINT:'queued_min':'${space1}Min\:%5.0lf${eol1}' "
                    . "GPRINT:'queued_avg':'${space2}Avg\:%5.0lf' "
                    . "GPRINT:'queued_max':'${space1}Max\:%5.0lf\\l' ";
        }
    } 
    else if ($graph == "mem_report") 
    {
        $title = "Memory: $hostname";

        $lower_limit = "--lower-limit 0 --rigid";
        $extras .= "--base 1024";
        $vertical_label = "--vertical-label Bytes";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {
            $series .= "DEF:'mem_total${def_nr}'='${rrd_dir}/mem_total.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_total${def_nr}'=mem_total${def_nr},1024,* "
                ."DEF:'mem_shared${def_nr}'='${rrd_dir}/mem_shared.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_shared${def_nr}'=mem_shared${def_nr},1024,* "
                ."DEF:'mem_free${def_nr}'='${rrd_dir}/mem_free.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_free${def_nr}'=mem_free${def_nr},1024,* "
                ."DEF:'mem_cached${def_nr}'='${rrd_dir}/mem_cached.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_cached${def_nr}'=mem_cached${def_nr},1024,* "
                ."DEF:'mem_buffer${def_nr}'='${rrd_dir}/mem_buffers.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_buffer${def_nr}'=mem_buffer${def_nr},1024,* "
                ."CDEF:'bmem_used${def_nr}'='bmem_total${def_nr}','bmem_shared${def_nr}',-,'bmem_free${def_nr}',-,'bmem_cached${def_nr}',-,'bmem_buffer${def_nr}',- "
                ."DEF:'swap_total${def_nr}'='${rrd_dir}/swap_total.rrd':'sum':AVERAGE "
                ."DEF:'swap_free${def_nr}'='${rrd_dir}/swap_free.rrd':'sum':AVERAGE "
                ."CDEF:'bmem_swap${def_nr}'='swap_total${def_nr}','swap_free${def_nr}',-,1024,* ";

            $report_names = array( "used", "shared", "cached", "buffer", "swap", "total" );

            if( $conf['graphreport_stats'] )
            {
                foreach( $report_names as $r )
                {
                    $series .= "CDEF:bmem_${r}${def_nr}_nonans=bmem_${r}${def_nr},UN,0,bmem_${r}${def_nr},IF ";
                }
            }

            $def_nr++;
        }

        if( $conf['graphreport_stats'] )
        {
            $s_last     = $def_nr - 1;

            foreach( $report_names as $r )
            {
                $cdef_sum   = "CDEF:bmem_${r}=bmem_${r}0_nonans";

                if( $s_last > 1 )
                {
                    foreach (range(1, ($s_last)) as $print_nr ) 
                    {
                        $user_sum   .= ",bmem_${r}{$print_nr}_nonans,+";
                    }
                }
                $cdef_sum .= " ";

                $series   .= $cdef_sum;
            }

            $r_count = 0;

            $conf['mem_buffer_color'] = $conf['mem_buffered_color'];
            $conf['mem_swap_color']   = $conf['mem_swapped_color'];
            $conf['mem_total_color']  = $conf['cpu_num_color'];

            foreach( $report_names as $r )
            {
                $legend_str = ucfirst( $r );

                if( $r == "total" )
                {
                    $graph_str  = "LINE2";
                }
                else if( $r_count == 0 )
                {
                    $graph_str  = "AREA";
                }
                else
                {
                    $graph_str  = "STACK";
                }
                foreach (range(0, ($s_last)) as $print_nr ) 
                {
                    $series .= "${graph_str}:'bmem_${r}${print_nr}'#".$conf['mem_'.${r}.'_color'].":'${legend_str}\g' ";
                    $legend_str = '';
                }

                $series .= "VDEF:'${r}_last'=bmem_${r},LAST ";
                $series .= "VDEF:'${r}_min'=bmem_${r},MINIMUM ";
                $series .= "VDEF:'${r}_avg'=bmem_${r},AVERAGE ";
                $series .= "VDEF:'${r}_max'=bmem_${r},MAXIMUM ";

                $spacefill = '';

                $spacesize = 6-strlen($r); // max length 'swapped' = 7
                foreach ( range( 0, $spacesize ) as $whatever )
                {
                    $spacefill .= ' ';
                }
                $series .= "GPRINT:'${r}_last':'${spacefill}Last\:%6.1lf%s' "
                        . "GPRINT:'${r}_min':'${space1}Min\:%6.1lf%s${eol1}' "
                        . "GPRINT:'${r}_avg':'${space2}Avg\:%6.1lf%s' "
                        . "GPRINT:'${r}_max':'${space1}Max\:%6.1lf%s\\l' ";
                $r_count = $r_count + 1;
            }
        }
        else
        {
            $series .= "AREA:'bmem_used${def_nr}'#".$conf['mem_used_color']."${memuse_str} " 
                    ."STACK:'bmem_shared${def_nr}'#".$conf['mem_shared_color']."${memshared_str} " 
                    ."STACK:'bmem_cached${def_nr}'#".$conf['mem_cached_color']."${memcached_str} " 
                    ."STACK:'bmem_buffer${def_nr}'#".$conf['mem_buffered_color']."${membuff_str} "
                    ."STACK:'bmem_swap${def_nr}'#".$conf['mem_swapped_color']."${memswap_str} "; 
        }

    } 
    else if ($graph == "load_report") 
    {
        $title = "Load: $hostname";

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

            $series .= "DEF:'load_load${def_nr}'='${rrd_dir}/load_one.rrd':'sum':AVERAGE "
                ."DEF:'load_procs${def_nr}'='${rrd_dir}/proc_run.rrd':'sum':AVERAGE "
                ."DEF:'load_cpus${def_nr}'='${rrd_dir}/cpu_num.rrd':'sum':AVERAGE ";

            $report_names = array( "load", "procs", "cpus" );

            if( $conf['graphreport_stats'] )
            {
                foreach( $report_names as $r )
                {
                    $series .= "CDEF:load_${r}${def_nr}_nonans=load_${r}${def_nr},UN,0,load_${r}${def_nr},IF ";
                }
            }

            $def_nr++;
        }

        if( $conf['graphreport_stats'] )
        {
            $s_last     = $def_nr - 1;

            foreach( $report_names as $r )
            {
                $cdef_sum   = "CDEF:load_${r}=load_${r}0_nonans";

                if( $s_last > 1 )
                {
                    foreach (range(1, ($s_last)) as $print_nr ) 
                    {
                        $user_sum   .= ",load_${r}{$print_nr}_nonans,+";
                    }
                }
                $cdef_sum .= " ";

                $series   .= $cdef_sum;
            }

            $conf['load_load_color']  = $conf['load_one_color'];
            $conf['load_procs_color'] = $conf['proc_run_color'];
            $conf['load_cpus_color']  = $conf['cpu_num_color'];

            foreach( $report_names as $r )
            {
                $legend_str = ucfirst( $r );

                if( $r == 'load' )
                {
                    $graph_str  = "AREA";
                }
                else
                {
                    $graph_str  = "LINE2";
                }
                foreach (range(0, ($s_last)) as $print_nr ) 
                {
                    $series .= "${graph_str}:'load_${r}${print_nr}'#".$conf['load_'.${r}.'_color'].":'${legend_str}\g' ";
                    $legend_str = '';
                }

                $series .= "VDEF:'${r}_last'=load_${r},LAST ";
                $series .= "VDEF:'${r}_min'=load_${r},MINIMUM ";
                $series .= "VDEF:'${r}_avg'=load_${r},AVERAGE ";
                $series .= "VDEF:'${r}_max'=load_${r},MAXIMUM ";

                $spacefill = '';

                $spacesize = 6-strlen($r); // max length 'swapped' = 7
                foreach ( range( 0, $spacesize ) as $whatever )
                {
                    $spacefill .= ' ';
                }
                $series .= "GPRINT:'${r}_last':'${spacefill}Last\:%6.1lf%s' "
                        . "GPRINT:'${r}_min':'${space1}Min\:%6.1lf%s${eol1}' "
                        . "GPRINT:'${r}_avg':'${space2}Avg\:%6.1lf%s' "
                        . "GPRINT:'${r}_max':'${space1}Max\:%6.1lf%s\\l' ";
            }
        }
        else
        {
            $series .="AREA:'load_one${def_nr}'#".$conf['load_one_color']."${load_str} ";
            $series .="LINE2:'cpu_num${def_nr}'#".$conf['cpu_num_color']."${cpu_str} ";
            $series .="LINE2:'proc_run${def_nr}'#".$conf['proc_run_color']."${run_str} ";
        }
    } 
    else if ($graph == "network_report") 
    {
        $title = "Network: $hostname";

        $lower_limit = "--lower-limit 0 --rigid";
        $extras .= "--base 1024";
        $vertical_label = "--vertical-label 'Bytes/sec'";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {
            $series .= "DEF:'bytes_in${def_nr}'='${rrd_dir}/bytes_in.rrd':'sum':AVERAGE "
                    ."DEF:'bytes_out${def_nr}'='${rrd_dir}/bytes_out.rrd':'sum':AVERAGE ";

            $report_names = array( "in", "out" );

            if( $conf['graphreport_stats'] )
            {
                foreach( $report_names as $r )
                {
                    $series .= "CDEF:bytes_${r}${def_nr}_nonans=bytes_${r}${def_nr},UN,0,bytes_${r}${def_nr},IF ";
                }
            }

            $def_nr++;
        }

        if( $conf['graphreport_stats'] )
        {
            $s_last     = $def_nr - 1;

            foreach( $report_names as $r )
            {
                $cdef_sum   = "CDEF:bytes_${r}=bytes_${r}0_nonans";

                if( $s_last > 1 )
                {
                    foreach (range(1, ($s_last)) as $print_nr ) 
                    {
                        $user_sum   .= ",bytes_${r}{$print_nr}_nonans,+";
                    }
                }
                $cdef_sum .= " ";

                $series   .= $cdef_sum;
            }

            $r_count = 0;

            $conf['bytes_out_color'] = $conf['mem_used_color'];
            $conf['bytes_in_color']  = $conf['mem_cached_color'];

            foreach( $report_names as $r )
            {
                $legend_str = ucfirst( $r );

                $graph_str  = "LINE2";

                foreach (range(0, ($s_last)) as $print_nr ) 
                {
                    $series .= "${graph_str}:'bytes_${r}${print_nr}'#".$conf['bytes_'.${r}.'_color'].":'${legend_str}\g' ";
                    $legend_str = '';
                }

                $series .= "VDEF:'${r}_last'=bytes_${r},LAST ";
                $series .= "VDEF:'${r}_min'=bytes_${r},MINIMUM ";
                $series .= "VDEF:'${r}_avg'=bytes_${r},AVERAGE ";
                $series .= "VDEF:'${r}_max'=bytes_${r},MAXIMUM ";

                $spacefill = '';

                $spacesize = 6-strlen($r); // max length 'swapped' = 7
                foreach ( range( 0, $spacesize ) as $whatever )
                {
                    $spacefill .= ' ';
                }
                $series .= "GPRINT:'${r}_last':'${spacefill}Last\:%6.1lf%s' "
                        . "GPRINT:'${r}_min':'${space1}Min\:%6.1lf%s${eol1}' "
                        . "GPRINT:'${r}_avg':'${space2}Avg\:%6.1lf%s' "
                        . "GPRINT:'${r}_max':'${space1}Max\:%6.1lf%s\\l' ";

            }
        }
        else
        {
                $series .= "LINE2:'bytes_in${def_nr}'#".$conf['mem_cached_color']."'Bytes In' "
                        ."LINE2:'bytes_out${def_nr}'#".$conf['mem_used_color']."'Bytes Out' ";
        }

    } 
    else if ($graph == "packet_report") 
    {
        $title = "Packets: $hostname";

        $lower_limit = "--lower-limit 0 --rigid";
        $extras .= "--base 1024";
        $vertical_label = "--vertical-label 'Packets/sec'";

        $def_nr = 0;

        foreach( $rrd_dirs as $rrd_dir ) 
        {

            $series .= "DEF:'pkts_in${def_nr}'='${rrd_dir}/pkts_in.rrd':'sum':AVERAGE "
                    ."DEF:'pkts_out${def_nr}'='${rrd_dir}/pkts_out.rrd':'sum':AVERAGE ";

            $report_names = array( "in", "out" );

            if( $conf['graphreport_stats'] )
            {
                foreach( $report_names as $r )
                {
                    $series .= "CDEF:pkts_${r}${def_nr}_nonans=pkts_${r}${def_nr},UN,0,pkts_${r}${def_nr},IF ";
                }
            }

            $def_nr++;
        }

        if( $conf['graphreport_stats'] )
        {
            $s_last     = $def_nr - 1;

            foreach( $report_names as $r )
            {
                $cdef_sum   = "CDEF:pkts_${r}=pkts_${r}0_nonans";

                if( $s_last > 1 )
                {
                    foreach (range(1, ($s_last)) as $print_nr ) 
                    {
                        $user_sum   .= ",pkts_${r}{$print_nr}_nonans,+";
                    }
                }
                $cdef_sum .= " ";

                $series   .= $cdef_sum;
            }

            $r_count = 0;

            $conf['pkts_out_color'] = $conf['mem_used_color'];
            $conf['pkts_in_color']  = $conf['mem_cached_color'];

            foreach( $report_names as $r )
            {
                $legend_str = ucfirst( $r );

                $graph_str  = "LINE2";

                foreach (range(0, ($s_last)) as $print_nr ) 
                {
                    $series .= "${graph_str}:'pkts_${r}${print_nr}'#".$conf['pkts_'.${r}.'_color'].":'${legend_str}\g' ";
                    $legend_str = '';
                }

                $series .= "VDEF:'${r}_last'=pkts_${r},LAST ";
                $series .= "VDEF:'${r}_min'=pkts_${r},MINIMUM ";
                $series .= "VDEF:'${r}_avg'=pkts_${r},AVERAGE ";
                $series .= "VDEF:'${r}_max'=pkts_${r},MAXIMUM ";

                $spacefill = '';

                $spacesize = 6-strlen($r); // max length 'swapped' = 7
                foreach ( range( 0, $spacesize ) as $whatever )
                {
                    $spacefill .= ' ';
                }
                $series .= "GPRINT:'${r}_last':'${spacefill}Last\:%6.1lf%s' "
                        . "GPRINT:'${r}_min':'${space1}Min\:%6.1lf%s${eol1}' "
                        . "GPRINT:'${r}_avg':'${space2}Avg\:%6.1lf%s' "
                        . "GPRINT:'${r}_max':'${space1}Max\:%6.1lf%s\\l' ";

            }
        }
        else
        {
                $series .= "LINE2:'pkts_in${def_nr}'#".$conf['mem_cached_color']."'Packets In' "
                        ."LINE2:'pkts_out${def_nr}'#".$conf['mem_used_color']."'Packets Out' ";
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

            if( $conf['graphreport_stats'] )
            {
                $series .= "CDEF:sum${def_nr}_nonans=sum${def_nr},UN,0,sum${def_nr},IF ";
            }

            $def_nr++;
        }

        if( $conf['graphreport_stats'] )
        {
            $s_last         = $def_nr - 1;
            $series_sum     = "CDEF:sum=sum0_nonans";

            if( $def_nr > 1 )
            {
                foreach (range(1, ($s_last)) as $print_nr ) 
                {
                    $series_sum     .= ",sum{$print_nr}_nonans,+";
                }
            }

            $series_sum .= " ";

            $series_last    = "VDEF:'sum_last'=sum,LAST ";
            $series_minimum = "VDEF:'sum_min'=sum,MINIMUM ";
            $series_average = "VDEF:'sum_avg'=sum,AVERAGE ";
            $series_maximum = "VDEF:'sum_max'=sum,MAXIMUM ";

            $series .= $series_sum . $series_last . $series_minimum . $series_average . $series_maximum;

            $series .= "COMMENT:\"\\n\" ";
            $series .= "GPRINT:'sum_last':'${space1}Last\:%6.1lf%s' "
                    . "GPRINT:'sum_min':'${space1}Min\:%6.1lf%s${eol1}' "
                    . "GPRINT:'sum_avg':'${space2}Avg\:%6.1lf%s' "
                    . "GPRINT:'sum_max':'${space1}Max\:%6.1lf%s\\l' ";
        }
    }
}
if( $series != '' ) 
{
    if ($job_start)
    {
        $series .= "VRULE:${job_start}#${jobstart_color}:'job start':dashes=4,2 ";
    }
    if ($job_stop)
    {
        $series .= "VRULE:${job_stop}#${jobstop_color}:'job stop':dashes=4,2 ";
    }
}

if($graph == "job_report")
{
    if($range == 'job' )
    {
        $title = "Last: $j_title";
    }
    else
    {
        $title = "Last: $range";
    }
}
else if( !isset( $title ) )
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
                "--width $width --height $height $lower_limit $vertical_label ".
                "--title '$title' $extras $background ".
                $series;
}
else
{
    $command = $conf['rrdtool'] . " graph - --start $period_start --end $period_stop ".
               "--width $width --height $height $lower_limit --color BACK#$load_color $vertical_label ".
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
