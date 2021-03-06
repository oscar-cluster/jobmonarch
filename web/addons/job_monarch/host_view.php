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
 * SVN $Id: host_view.php 412 2007-07-06 14:36:09Z bastiaans $
 */

include_once "./libtoga.php";

//$tpl = new TemplatePower( "templates/host_view.tpl" );
//$tpl->assignInclude("extra", "templates/host_extra.tpl");
//$tpl->prepare();

function datetimeToEpoch( $datetime ) {

        //printf("datetime = %s\n", $datetime );
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

        //printf( "hours = %s minutes = %s seconds = %s\n", $hours, $minutes, $seconds );

        $timestamp = mktime( $hours, $minutes, $seconds, $months, $days, $years );

        //printf( "timestamp = %s\n", $timestamp );

        return $timestamp;
}

function makeHostView() {

	global $tpl, $metrics, $clustername, $hostname;
	global $cluster_ul, $hosts_up, $get_metric_string;
	global $cluster, $period_start, $period_stop;
	global $job_start, $job_stop;

	//print_r( $metrics );

	//printf( "c %s\n", $clustername );

	$trd = new TarchRrdGraph( $clustername, $hostname );
	$rrdirs = $trd->getRrdDirs( $period_start, $period_stop );

	$longtitle = "Batch Archive Node Report :: Powered by Job Monarch!";
	$title = "Batch Archive Node Report";

	makeHeader( 'host_view', $title, $longtitle );

	$metrics = $metrics[$hostname];
	$mymetrics = array();

	foreach( $rrdirs as $rrdir ) 
	{
		$ml	= $trd->dirList( $rrdir );

		foreach( $ml as $lmetr )
		{
			$metrn_fields	= explode( '.', $lmetr );

			$metrn		= $metrn_fields[0];

			if( !in_array( $metrn, $mymetrics ) )
			{
				$mymetrics[$metrn]	= $metrics[$metrn];
			}
		}
	}

	$hosts_up = $hosts_up[$hostname];
	//print_r( $hosts_up );

	$tpl->assign("cluster", $clustername);
	$tpl->assign("host", $hostname);
	$tpl->assign("node_image", "../../".node_image($metrics));
	$tpl->assign("sort",$sort);
	$tpl->assign("range",$range);

	if( !is_numeric( $period_start ) ) {
		$period_start = datetimeToEpoch( $period_start );
	}
	if( !is_numeric( $period_stop ) ) {
		$period_stop = datetimeToEpoch( $period_stop );
	}

	if($hosts_up)
	      $tpl->assign("node_msg", "This host is up and running."); 
	else
	      $tpl->assign("node_msg", "This host is down."); 

	$cluster_url=rawurlencode($clustername);
	$tpl->assign("cluster_url", $cluster_url);
	//$tpl->assign("graphargs", "h=$hostname&$get_metric_string&st=$cluster[LOCALTIME]&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop");
	$tpl->assign("graphargs", "h=$hostname&$get_metric_string&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop");

	# For the node view link.
	$tpl->assign("node_view","./?p=2&c=$cluster_url&h=$hostname");

	//# No reason to go on if this node is down.
	//if ($hosts_down)
	//   {
	//      $tpl->printToScreen();
	//      return;
	//   }

	$tpl->assign("ip", $hosts_up[IP]);

	foreach ($mymetrics as $name => $v)
	   {
	       if ($v[TYPE] == "string" or $v[TYPE]=="timestamp" or $always_timestamp[$name])
		  {
		     # Long gmetric name/values will disrupt the display here.
		     if ($v[SOURCE] == "gmond") $s_metrics[$name] = $v;
		  }
	       elseif ($v[SLOPE] == "zero" or $always_constant[$name])
		  {
		     $c_metrics[$name] = $v;
		  }
	       else if ($reports[$metric])
		  continue;
	       else
		  {
		     //$graphargs = "c=$cluster_url&h=$hostname&v=$v[VAL]&m=$name"
		     //  ."&z=medium&st=$cluster[LOCALTIME]&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop";
		     $graphargs = "c=$cluster_url&h=$hostname&m=$name"
		       ."&z=medium&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop";
		     # Adding units to graph 2003 by Jason Smith <smithj4@bnl.gov>.
		     if ($v[UNITS]) {
			$encodeUnits = rawurlencode($v[UNITS]);
			$graphargs .= "&vl=$encodeUnits";
		     }
		     $g_metrics[$name][graph] = $graphargs;
          }
	   }
	# Add the uptime metric for this host. Cannot be done in ganglia.php,
	# since it requires a fully-parsed XML tree. The classic contructor problem.
	$s_metrics[uptime][TYPE] = "string";
	$s_metrics[uptime][VAL] = uptime($cluster[LOCALTIME] - $metrics[boottime][VAL]);

	# Add the gmond started timestamps & last reported time (in uptime format) from
	# the HOST tag:
	$s_metrics[gmond_started][TYPE] = "timestamp";
	$s_metrics[gmond_started][VAL] = $hosts_up[GMOND_STARTED];
	$s_metrics[last_reported][TYPE] = "string";
	$s_metrics[last_reported][VAL] = uptime($cluster[LOCALTIME] - $hosts_up[REPORTED]);

	# Show string metrics
	if (is_array($s_metrics))
	   {
	      ksort($s_metrics);
	      foreach ($s_metrics as $name => $v )
	     {
		$tpl->newBlock("string_metric_info");
		$tpl->assign("name", $name);
		if( $v[TYPE]=="timestamp" or $always_timestamp[$name])
		   {
		      $tpl->assign("value", date("r", $v[VAL]));
		   }
		else
		   {
		      $tpl->assign("value", "$v[VAL] $v[UNITS]");
		   }
	     }
	   }

	# Show constant metrics.
	if (is_array($c_metrics))
	   {
	      ksort($c_metrics);
	      foreach ($c_metrics as $name => $v )
	     {
		$tpl->newBlock("const_metric_info");
		$tpl->assign("name", $name);
		$tpl->assign("value", "$v[VAL] $v[UNITS]");
	     }
	   }

	# Show graphs.
	if (is_array($g_metrics))
	   {
	      ksort($g_metrics);

	      $i = 0;
	      foreach ( $g_metrics as $name => $v )
		 {
		    $tpl->newBlock("vol_metric_info");
		    $tpl->assign("graphargs", $v[graph]);
		    $tpl->assign("alt", "$hostname $name");
		    if($i++ %2)
		       $tpl->assign("br", "<BR>");
		 }
	   }
}

	//$tpl->printToScreen();
?>
