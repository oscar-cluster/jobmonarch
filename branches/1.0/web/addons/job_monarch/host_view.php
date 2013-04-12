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

include_once "./libtoga.php";

if( $view == "overview-host" )
{
    $my_dir = getcwd();

    global $context;

    $context = 'cluster';

    chdir( $GANGLIA_PATH );

    include "./ganglia.php";
    include "./get_ganglia.php";

    chdir( $my_dir );
}

function datetimeToEpoch( $datetime ) 
{

        $datetime_fields = explode( ' ', $datetime );

        $date = $datetime_fields[0];
        $time = $datetime_fields[1];

        $date_fields = explode( '-', $date );

        $days = $date_fields[0];
        $months = $date_fields[1];
        $years = $date_fields[2];

        //printf( "days = %s months = %s years = %s\n", $days, $months, $years );

        $time_fields = explode( ':', $time );

        $hours = $time_fields[0];
        $minutes = $time_fields[1];
        $seconds = $time_fields[2];

        $timestamp = mktime( $hours, $minutes, $seconds, $months, $days, $years );

        return $timestamp;
}

function makeHostView() 
{

    global $dwoo, $metrics, $clustername, $hostname;
    global $cluster_ul, $hosts_up, $get_metric_string;
    global $cluster, $period_start, $period_stop;
    global $job_start, $job_stop, $view, $conf, $range;

    $tpl = new Dwoo_Template_File("templates/host_view.tpl");
    $tpl_data = new Dwoo_Data();

    $rrdirs = array();

    if( $view == "overview-host" )
    {
        $rrdirs[] = $conf['rrds'] . '/' . $clustername .'/'. $hostname;
    }
    else
    {
        $trd    = new TarchRrdGraph( $clustername, $hostname );
        $rrdirs = $trd->getRrdDirs( $period_start, $period_stop );
    }

    $longtitle    = "Batch Archive Node Report :: Powered by Job Monarch!";
    $title        = "Batch Archive Node Report";

    makeHeader( 'host_view', $title, $longtitle );

    #print_r( $rrdirs);

    $metrics = $metrics[$hostname];
    $mymetrics = array();

    foreach( $rrdirs as $rrdir ) 
    {
        #printf("rrd dir %s\n", $rrdir );
        if( $view == "overview-host" )
        {
            $mymetrics = $metrics;
            unset( $mymetrics['last_reported_timestamp'] ); // Ganglia bug?
            #print_r( $mymetrics );
        }
        else
        {
            #printf("archive mode\n");
            $ml    = $trd->dirList( $rrdir );

            foreach( $ml as $lmetr )
            {
                $metrn_fields = explode( '.', $lmetr );

                $metrn        = $metrn_fields[0];

                if( !in_array( $metrn, $mymetrics ) )
                {
                    $mymetrics[$metrn]    = $metrics[$metrn];
                }
            }
        }
    }

    $hosts_up = $hosts_up[$hostname];

    $tpl_data->assign("cluster", $clustername);
    $tpl_data->assign("host", $hostname);
    $tpl_data->assign("node_image", "../../".node_image($metrics));
    $tpl_data->assign("sort",$sort);
    $tpl_data->assign("range",$range);

    if( !is_numeric( $period_start ) ) 
    {
        $period_start = datetimeToEpoch( $period_start );
    }
    if( !is_numeric( $period_stop ) ) 
    {
        $period_stop = datetimeToEpoch( $period_stop );
    }

    if($hosts_up)
          $tpl_data->assign("node_msg", "This host is up and running."); 
    else
          $tpl_data->assign("node_msg", "This host is down."); 

    $cluster_url=rawurlencode($clustername);
    $tpl_data->assign("cluster_url", $cluster_url);

    $graphargs = "h=$hostname&r=$range&job_start=$job_start&job_stop=$job_stop";

    if( $range == 'job' )
    {
        $graphargs .= "&period_start=$period_start&period_stop=$period_stop";
    }
    else
    {
        $tijd = time();
        $graphargs .= "&st=$tijd";
    }

    $tpl_data->assign("graphargs", "$graphargs");

    # For the node view link.
    $tpl_data->assign("node_view","./?p=2&c=$cluster_url&h=$hostname");

    $tpl_data->assign("ip", $hosts_up['IP']);

    #print_r( $mymetrics );

    foreach ($mymetrics as $name => $v)
    {
        if ($v['TYPE'] == "string" or $v['TYPE']=="timestamp" or $always_timestamp[$name] or $v['NAME']=='last_reported_timestamp')
        {
            # Long gmetric name/values will disrupt the display here.
            if ($v['SOURCE'] == "gmond") $s_metrics[$name] = $v;
        }
        else if ($v['SLOPE'] == "zero" or $always_constant[$name])
        {
            $c_metrics[$name] = $v;
        }
        else if ($reports[$metric])
        {
            continue;
        }
        else
        {
            $graphargs = "c=$cluster_url&h=$hostname&m=$name&z=overview-medium&r=$range&job_start=$job_start&job_stop=$job_stop";

            if( $range == 'job' )
            {
                $graphargs .= "&period_start=$period_start&period_stop=$period_stop";
            }
            else
            {
                $tijd = time();
                $graphargs .= "&st=$tijd";
            }
            # Adding units to graph 2003 by Jason Smith <smithj4@bnl.gov>.
            if ($v['UNITS']) 
            {
                $encodeUnits = rawurlencode($v['UNITS']);
                $graphargs .= "&vl=$encodeUnits";
            }
            $g_metrics[$name]['graph'] = $graphargs;
        }
    }
    # Add the uptime metric for this host. Cannot be done in ganglia.php,
    # since it requires a fully-parsed XML tree. The classic contructor problem.
    $s_metrics['uptime']['TYPE'] = "string";
    $s_metrics['uptime']['VAL'] = uptime($cluster['LOCALTIME'] - $metrics['boottime']['VAL']);

    # Add the gmond started timestamps & last reported time (in uptime format) from
    # the HOST tag:
    $s_metrics['gmond_started']['TYPE'] = "timestamp";
    $s_metrics['gmond_started']['VAL'] = $hosts_up['GMOND_STARTED'];
    $s_metrics['last_reported']['TYPE'] = "string";
    $s_metrics['last_reported']['VAL'] = uptime($cluster['LOCALTIME'] - $hosts_up['REPORTED']);

    # Show string metrics
    if (is_array($s_metrics))
    {
        ksort($s_metrics);
        $string_metric_info_loop = array();
        foreach ($s_metrics as $name => $v )
        {
            $metric_info = array();
            $metric_info["name"] = $name;
            if( $v['TYPE']=="timestamp" or $always_timestamp[$name])
            {
                $metric_info["value"] = date("r", $v['VAL']);
            }
            else
            {
                $metric_info["value"] = $v['VAL']." ". $v['UNITS'];
            }
            $string_metric_info_loop[] = $metric_info;
        }
        $tpl_data->assign("string_metric_info", $string_metric_info_loop );
    }

    # Show constant metrics.
    if (is_array($c_metrics))
    {
        ksort($c_metrics);
        $const_metric_info_loop = array();
        foreach ($c_metrics as $name => $v )
        {
            $const_info = array();
            $const_infp["name"] = $name;
            $const_info["value"] = $v[VAL]." ". $v[UNITS];
            $const_metric_info_loop[] = $const_info;
        }
        $tpl_data->assign("const_metric_info", $const_metric_info_loop );
    }

    # Show graphs.
    if (is_array($g_metrics))
    {
        ksort($g_metrics);
        $vol_metric_info_loop = array();

        $i = 0;
        foreach ( $g_metrics as $name => $v )
        {
            $metric_info = array();
            $metric_info["graphargs"] = $v['graph'];
            $metric_info["alt"] = "$hostname $name";
            $vol_metric_info_loop[] = $metric_info;
        } 
        $tpl_data->assign("vol_metric_info", $vol_metric_info_loop );
    }
    $dwoo->output($tpl, $tpl_data);
}

?>
