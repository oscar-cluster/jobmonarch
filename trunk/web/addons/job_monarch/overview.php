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

global $GANGLIA_PATH, $clustername, $tpl, $filter, $cluster, $get_metric_string, $cluster_url, $sh;
global $hosts_up, $m, $start, $end, $filterorder, $COLUMN_REQUESTED_MEMORY, $COLUMN_QUEUED, $COLUMN_NODES, $hostname, $piefilter;
global $longtitle, $title, $range;

$tpl->assign( "clustername", $clustername );

if( $JOB_ARCHIVE )
{
	$tpl->assign( "cluster_url", rawurlencode($clustername) );
}

$ds		= new DataSource();
$myxml_data	= $ds->getData();

$data_gatherer	= new DataGatherer( $clustername );
$data_gatherer->parseXML( $myxml_data );

$heartbeat	= $data_gatherer->getHeartbeat();
$jobs		= $data_gatherer->getJobs();
$gnodes		= $data_gatherer->getNodes();
$cpus		= $data_gatherer->getCpus();

function setupFilterSettings() 
{

	global $tpl, $filter, $clustername, $piefilter, $data_gatherer, $myxml_data, $filterorder;

	$filter_image_url = "";

	$tpl->gotoBlock( "_ROOT" );

	foreach( $filter as $filtername => $filtervalue ) 
	{
		$tpl->assign( "f_".$filtername, $filtervalue );

		$filter_image_url	.= "&$filtername=$filtervalue";
	}

	session_start();

	unset( $_SESSION["data"] );
	$_SESSION["data"]	= &$myxml_data;

	$ic			= new ClusterImage( $myxml_data, $clustername );

	$ic->setBig();
	$ic->setNoimage();
	$ic->draw();

	$tpl->assign( "clusterimage", "./image.php?". session_name() . "=" . session_id() ."&c=".rawurlencode($clustername)."&view=big-clusterimage".$filter_image_url );

	//$tpl->assign( "clusterimage_width", $ic->getWidth() );
	//$tpl->assign( "clusterimage_height", $ic->getHeight() );

	$tpl->newBlock( "node_clustermap" );
	$tpl->assign( "node_area_map", $ic->getImagemapArea() );
	$tpl->gotoBlock( "_ROOT" );

	$tpl->assign( "f_order", $filterorder );

	if( array_key_exists( "id", $filter ) ) 
	{
		$piefilter = 'id';
	} 
	else if( array_key_exists( "user", $filter ) ) 
	{
		$piefilter = 'user';
	} 
	else if( array_key_exists( "queue", $filter ) ) 
	{
		$piefilter = 'queue';
	}

	$pie	= drawPie();

	$tpl->assign("pie", $pie );
}

function timeToEpoch( $time ) 
{
	$time_fields	= explode( ':', $time );

	if( count( $time_fields ) == 3 ) 
	{
		$hours		= $time_fields[0];
		$minutes	= $time_fields[1];
		$seconds	= $time_fields[2];

	} 
	else if( count( $time_fields ) == 2 ) 
	{
		$hours 		= 0;
		$minutes 	= $time_fields[0];
		$seconds 	= $time_fields[1];

	} 
	else if( count( $time_fields ) == 1 ) 
	{
		$hours 		= 0;
		$minutes 	= 0;
		$seconds 	= $time_fields[0];
	}

	$myepoch 	= intval( $seconds + (intval( $minutes * 60 )) + (intval( $hours * 3600 )) );

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

	$c1r 	= hexDec( colorRed( $first ) );
	$c1g 	= hexDec( colorGreen( $first ) );
	$c1b 	= hexDec( colorBlue( $first ) );

	$c2r 	= hexDec( colorRed( $second ) );
	$c2g 	= hexDec( colorGreen( $second ) );
	$c2b 	= hexDec( colorBlue( $second ) );

	$rdiff 	= ($c1r >= $c2r) ? $c1r - $c2r : $c2r - $c1r;
	$gdiff 	= ($c1g >= $c2g) ? $c1g - $c2g : $c2g - $c1g;
	$bdiff 	= ($c1b >= $c2b) ? $c1b - $c2b : $c2b - $c1b;

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
	$start		= "004E00";
	
	$start_red	= colorRed( $start );
	$start_green	= colorGreen( $start );
	$start_blue	= colorBlue( $start );
	
	$end		= "FFFFFF";

	$end_red	= colorRed( $end );
	$end_green	= colorGreen( $end );
	$end_blue	= colorBlue( $end );

	$change_color 	= TRUE;

	while( $change_color ) 
	{
		$change_color	= FALSE;

		$new_red 	= rand( hexDec( $start_red ), hexDec( $end_red ) );
		$new_green 	= rand( hexDec( $start_green ), hexDec( $end_green ) );
		$new_blue 	= rand( hexDec( $start_blue ), hexDec( $end_blue ) );

		$new 		= decHex( $new_red ) . decHex( $new_green ) . decHex( $new_blue );

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

	$nodes 		= $gnodes;

	if( isset($piefilter) )	
	{
		$pie_args	= "title=" . rawurlencode("Cluster ".$piefilter." usage");
	} 
	else 
	{
		$pie_args 	= "title=" . rawurlencode("Cluster queue usage");
	}

	$pie_args 	.= "&size=250x150";

	$queues 	= array();
	$nr_jobs 	= count( $jobs );
	$nr_nodes 	= count( $nodes );

	$nr_cpus 	= cluster_sum("cpu_num", $metrics);

	$empty_cpus 	= 0;
	$used_cpus 	= 0;

	$job_weight 	= array();

	foreach( $nodes as $node ) 
	{
		$myjobs		= $node->getJobs();
		$myhost		= $node->getHostname();
		$node_cpus	= $metrics[$myhost]["cpu_num"][VAL];
		$job_cpu	= 0;

		foreach( $myjobs as $myjob ) 
		{
			$job_cpu	+= (int) $jobs[$myjob][ppn] ? $jobs[$myjob][ppn] : 1;
		}

		$node_freecpu	= $node_cpus - $job_cpu;

		$empty_cpus	+= $node_freecpu;
	}

	$empty_cpus		= ( $empty_cpus >= 0 ) ? $empy_cpus : 0;
	$used_cpus		= $nr_cpus - $empty_cpus;

	$empty_percentage 	= ($empty_cpus / $nr_cpus) * 100;

	$qcolors 		= array();
	$color 			= randomColor( $qcolors );
	$qcolors[] 		= $color;
	$pie_args 		.= "&free=$empty_percentage,$color";

	if( isset( $piefilter ) )
	{
		$filterpie = array();
	}

	foreach( $nodes as $node )
	{
		$node_jobs 	= $node->getJobs();
		$nr_node_jobs 	= count( $node_jobs );
		$myhost 	= $node->getHostname();
		$node_cpus	= $metrics[$myhost]["cpu_num"][VAL];

		foreach( $node_jobs as $myjob )
		{
			$job_cpu		= (int) $jobs[$myjob][ppn] ? $jobs[$myjob][ppn] : 1;

			// Determine the weight of this job
			// - what percentage of the cpus is in use by this job
			//
			$job_weight[$myjob]	= ( $job_cpu / $nr_cpus );


			if( isset( $piefilter ) ) {

				$countjob = 1;

				if( $piefilter == 'id' )
				{
					if( $myjob != $filter[$piefilter] )
					{
						$countjob = 0;
					}
				}
				else if( $piefilter == 'user' )
				{
					if( $jobs[$myjob][owner] != $filter[$piefilter] )
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

				$qname		= $jobs[$myjob][queue];

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
		$percentage 	= ( $totalweight * 100 );
		
		$color 		= randomColor( $qcolors );
		$qcolors[] 	= $color;
		$pie_args 	.= "&$name=$percentage,$color";
	}
	$pie = "../../pie.php?$pie_args";

	return $pie;
}


function sortJobs( $jobs, $sortby, $sortorder ) 
{
	$sorted	= array();

	$cmp	= create_function( '$a, $b', 
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
				$state		= $jobattrs[status];
				$user 		= $jobattrs[owner];
				$queue 		= $jobattrs[queue];
				$name 		= $jobattrs[name];
				$req_cpu 	= $jobattrs[requested_time];
				$req_memory 	= $jobattrs[requested_memory];

				if( $state == 'R' )
				{
					$nodes = count( $jobattrs[nodes] );
				}
				else
				{
					$nodes = $jobattrs[nodes];
				}

				$ppn 		= (int) $jobattrs[ppn] ? $jobattrs[ppn] : 1;
				$cpus 		= $nodes * $ppn;
				$queued_time 	= (int) $jobattrs[queued_timestamp];
				$start_time 	= (int) $jobattrs[start_timestamp];
				$runningtime 	= $report_time - $start_time;

				switch( $sortby ) 
				{
					case "id":
						$sorted[$jobid] = $jobid;
						break;

					case "state":
						$sorted[$jobid] = $state;
						break;

					case "user":
						$sorted[$jobid] = $user;
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
	global $jobs, $nodes, $heartbeat, $clustername, $tpl;
	global $sortorder, $sortby, $filter, $sh, $hc, $m, $range;
	global $cluster_url, $get_metric_string, $host_url, $metrics;
	global $start, $end, $reports, $gnodes, $default_showhosts;
	global $COLUMN_QUEUED, $COLUMN_REQUESTED_MEMORY, $COLUMN_NODES, $hostname;
	global $cluster;

	$metricname		= $m;

	$tpl->assign("sortorder", $sortorder );
	$tpl->assign("sortby", $sortby );

	$sorted_jobs 		= sortJobs( $jobs, $sortby, $sortorder );

	$even 			= 1;

	$used_jobs 		= 0;
	$used_cpus 		= 0;
	$used_nodes 		= 0;

	$queued_jobs 		= 0;
	$queued_nodes 		= 0;
	$queued_cpus 		= 0;

	$total_nodes 		= 0;
	$total_cpus 		= 0;
	$total_jobs 		= 0;

	$all_used_nodes 	= array();
	$total_used_nodes 	= array();

	$running_name_nodes 	= array();

	$running_nodes 		= 0;
	$running_jobs 		= 0;
	$running_cpus 		= 0;

	$avail_nodes 		= count( $gnodes );
	$avail_cpus 		= cluster_sum("cpu_num", $metrics);

	$view_cpus 		= 0;
	$view_jobs 		= 0;
	$view_nodes 		= 0;

	$all_nodes 		= 0;
	$all_jobs 		= 0;
	$all_cpus 		= 0;

	$view_name_nodes 	= array();

	if( $COLUMN_REQUESTED_MEMORY ) 
	{
		$tpl->newBlock( "column_header_req_mem" );
	}

	if( $COLUMN_NODES ) 
	{
		$tpl->newBlock( "column_header_nodes" );
	}

	if( $COLUMN_QUEUED ) 
	{
		$tpl->newBlock( "column_header_queued" );
	}

	$last_displayed_job 	= null;

	$rjqj_host		= null;

	foreach( $metrics as $bhost => $bmetric )
	{
		foreach( $bmetric as $mname => $mval )
		{
			if( ( $mname == 'MONARCH-RJ' ) || ($mname == 'MONARCH-QJ') )
			{
				$rjqj_host      = $bhost;
			}
		}
	}

	if( $rjqj_host != null )
	{

		$rjqj_str =  "<IMG SRC=\"./graph.php?z=small&c=$clustername&g=job_report&r=$range&st=$cluster[LOCALTIME]\">";

		$tpl->gotoBlock( "_ROOT" );

		$tpl->assign( "rjqj_graph", $rjqj_str );
	}

	foreach( $sorted_jobs as $jobid => $sortdec ) 
	{
		$report_time 	= $jobs[$jobid][reported];

		if( $jobs[$jobid][status] == 'R' )
		{
			$nodes = count( $jobs[$jobid][nodes] );
		}
		else if( $jobs[$jobid][status] == 'Q' )
		{
			$nodes = $jobs[$jobid][nodes];
		}

		$ppn 		= (int) $jobs[$jobid][ppn] ? $jobs[$jobid][ppn] : 1;
		$cpus 		= $nodes * $ppn;

		if( $report_time == $heartbeat ) 
		{
			$display_job	= 1;

			if( $jobs[$jobid][status] == 'R' ) 
			{
				foreach( $jobs[$jobid][nodes] as $tempnode ) 
				{
					$all_used_nodes[] = $tempnode;
				}
			}

			$used_cpus += $cpus;

			if( $jobs[$jobid][status] == 'R' ) 
			{
				$running_cpus 	+= $cpus;

				$running_jobs++;

				$found_node_job	= 0;

				foreach( $jobs[$jobid][nodes] as $tempnode ) 
				{
					$running_name_nodes[] = $tempnode;

					if( isset( $hostname ) && $hostname != '' ) 
					{
						//$filter[host] = $hostname;

						$domain_len 	= 0 - strlen( $jobs[$jobid][domain] );
						$hostnode 	= $tempnode;

						//if( substr( $hostnode, $domain_len ) != $jobs[$jobid][domain] ) 
						//{
						//	$hostnode = $hostnode. '.'. $jobs[$jobid][domain];
						//}

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

			if( $jobs[$jobid][status] == 'Q' ) 
			{
				if( isset( $hostname ) && $hostname != '' )
				{
					$display_job = 0;
				}

				$queued_cpus 	+= $cpus;
				$queued_nodes 	+= $nodes;

				$queued_jobs++;
			}

			foreach( $filter as $filtername=>$filtervalue ) 
			{
				if( $filtername == 'id' && $jobid != $filtervalue )
				{
					$display_job = 0;
				}
				else if( $filtername == 'state' && $jobs[$jobid][status] != $filtervalue )
				{
					$display_job = 0;
				}
				else if( $filtername == 'queue' && $jobs[$jobid][queue] != $filtervalue )
				{
					$display_job = 0;
				}
				else if( $filtername == 'user' && $jobs[$jobid][owner] != $filtervalue )
				{
					$display_job = 0;
				}
			}

			if( $display_job ) 
			{
				$tpl->newBlock( "node" );
				$tpl->assign( "clustername", $clustername );
				$tpl->assign( "id", $jobid );

				$last_displayed_job 	= $jobid;

				$tpl->assign( "state", $jobs[$jobid][status] );

				$fullstate 		= '';

				if( $jobs[$jobid][status] == 'R' ) 
				{
					$fullstate 	= "Running";
				} 
				else if( $jobs[$jobid][status] == 'Q' ) 
				{
					$fullstate 	= "Queued";
				}

				$tpl->assign( "fullstate", $fullstate );
				
				$tpl->assign( "user", $jobs[$jobid][owner] );
				$tpl->assign( "queue", $jobs[$jobid][queue] );

				$fulljobname 		= $jobs[$jobid][name];
				$shortjobname 		= '';

				$tpl->assign( "fulljobname", $fulljobname );

				$fulljobname_fields	= explode( ' ', $fulljobname );

				$capjobname		= 0;

				if( strlen( $fulljobname_fields[0] ) > 10 )
				{
					$capjobname	= 1;
				}

				if( $capjobname ) 
				{
					$tpl->newBlock( "jobname_hint_start" );
					$tpl->gotoBlock( "node" );

					$shortjobname 	= substr( $fulljobname, 0, 10 ) . '..';
				} 
				else 
				{
					$shortjobname 	= $fulljobname;
				}
				
				$tpl->assign( "name", $shortjobname );

				if( $capjobname ) 
				{
					$tpl->newBlock( "jobname_hint_end" );
					$tpl->gotoBlock( "node" );
				}

				$domain 		= $jobs[$jobid][domain];

				$tpl->assign( "req_cpu", makeTime( timeToEpoch( $jobs[$jobid][requested_time] ) ) );

				if( $COLUMN_REQUESTED_MEMORY ) 
				{
					$tpl->newBlock( "column_req_mem" );
					$tpl->assign( "req_memory", $jobs[$jobid][requested_memory] );
					$tpl->gotoBlock( "node" );
				}


				if( $COLUMN_QUEUED ) 
				{
					$tpl->newBlock( "column_queued" );
					$tpl->assign( "queued", makeDate( $jobs[$jobid][queued_timestamp] ) );
					$tpl->gotoBlock( "node" );
				}
				if( $COLUMN_NODES ) 
				{
					$tpl->newBlock( "column_nodes" );
					$tpl->gotoBlock( "node" );
				}

				$ppn 			= (int) $jobs[$jobid][ppn] ? $jobs[$jobid][ppn] : 1;
				$cpus 			= $nodes * $ppn;

				$tpl->assign( "nodes", $nodes );
				$tpl->assign( "cpus", $cpus );

				$start_time 		= (int) $jobs[$jobid][start_timestamp];
				$job_start 		= $start_time;

				$view_cpus 		+= $cpus;

				$view_jobs++;

				if( $jobs[$jobid][status] == 'R' ) 
				{
					foreach( $jobs[$jobid][nodes] as $tempnode )
					{
						$view_name_nodes[] 	= $tempnode;
					}

					if( $COLUMN_NODES ) 
					{
						$tpl->gotoBlock( "column_nodes" );

						$mynodehosts 		= array();

						foreach( $jobs[$jobid][nodes] as $mynode ) 
						{
							//$myhost_href 	= "./?c=".$clustername."&h=".$mynode.".".$jobs[$jobid][domain];
							$myhost_href 	= "./?c=".$clustername."&h=".$mynode;
							$mynodehosts[] 	= "<A HREF=\"".$myhost_href."\">".$mynode."</A>";
						}

						$nodes_hostnames 	= implode( " ", $mynodehosts );

						$tpl->assign( "nodes_hostnames", $nodes_hostnames );
						$tpl->gotoBlock( "node" );
					}
				} 
				else if( $jobs[$jobid][status] == 'Q' ) 
				{
					$view_nodes 	+= (int) $jobs[$jobid][nodes];
				}

				if( $even ) 
				{
					$tpl->assign( "nodeclass", "even" );

					$even 		= 0;
				} 
				else 
				{
					$tpl->assign( "nodeclass", "odd" );

					$even 		= 1;
				}

				if( $start_time ) 
				{
					$runningtime 		= makeTime( $report_time - $start_time );
					//$job_runningtime	= $report_time - $start_time;
					$job_runningtime	= $heartbeat - $start_time;

					$tpl->assign( "started", makeDate( $start_time ) );
					$tpl->assign( "runningtime", $runningtime );
				}
			}
		}
	}

	$all_used_nodes 	= array_unique( $all_used_nodes );
	$view_name_nodes 	= array_unique( $view_name_nodes );
	$running_name_nodes 	= array_unique( $running_name_nodes );

	$used_nodes 		= count( $all_used_nodes );
	$view_nodes 		+= count( $view_name_nodes );
	$running_nodes 		+= count( $running_name_nodes );

	$total_nodes 		= $queued_nodes + $running_nodes;
	$total_cpus 		= $queued_cpus + $running_cpus;
	$total_jobs 		= $queued_jobs + $running_jobs;

	$free_nodes 		= $avail_nodes - $running_nodes;
	$free_nodes		= ( $free_nodes >= 0 ) ? $free_nodes : 0;
	$free_cpus 		= $avail_cpus - $running_cpus;
	$free_cpus		= ( $free_cpus >= 0 ) ? $free_cpus : 0;

	$tpl->assignGlobal( "avail_nodes", $avail_nodes );
	$tpl->assignGlobal( "avail_cpus", $avail_cpus );

	$tpl->assignGlobal( "queued_nodes", $queued_nodes );
	$tpl->assignGlobal( "queued_jobs", $queued_jobs );
	$tpl->assignGlobal( "queued_cpus", $queued_cpus );

	$tpl->assignGlobal( "total_nodes", $total_nodes );
	$tpl->assignGlobal( "total_jobs", $total_jobs );
	$tpl->assignGlobal( "total_cpus", $total_cpus );

	$tpl->assignGlobal( "running_nodes", $running_nodes );
	$tpl->assignGlobal( "running_jobs", $running_jobs );
	$tpl->assignGlobal( "running_cpus", $running_cpus );

	$tpl->assignGlobal( "used_nodes", $used_nodes );
	$tpl->assignGlobal( "used_jobs", $used_jobs );
	$tpl->assignGlobal( "used_cpus", $used_cpus );

	$tpl->assignGlobal( "free_nodes", $free_nodes );
	$tpl->assignGlobal( "free_cpus", $free_cpus );

	$tpl->assignGlobal( "view_nodes", $view_nodes );
	$tpl->assignGlobal( "view_jobs", $view_jobs );
	$tpl->assignGlobal( "view_cpus", $view_cpus );

	$tpl->assignGlobal( "report_time", makeDate( $heartbeat) );

	if( intval($view_jobs) == 1 and $start_time )
	{
		if( $last_displayed_job != null )
		{
			$filter[id] = $last_displayed_job;
		}
	}

	//print_r( $metrics );

	global $longtitle, $title;

	$longtitle = "Batch Report :: Powered by Job Monarch!";
	$title = "Batch Report";

	makeHeader( 'overview', $title, $longtitle );

	setupFilterSettings();

	if( intval($view_jobs) == 1 and $start_time ) 
	{
		$tpl->newBlock( "showhosts" );

		# Present a width list
		$cols_menu 	= "<SELECT NAME=\"hc\" OnChange=\"toga_form.submit();\">\n";

		$hostcols 	= ($hc) ? $hc : 4;

		foreach( range( 1, 25 ) as $cols ) 
		{
			$cols_menu	.= "<OPTION VALUE=$cols ";

			if ($cols == $hostcols)
			{
				$cols_menu	.= "SELECTED";
			}
			$cols_menu	.= ">$cols\n";
		}
		$cols_menu 	.= "</SELECT>\n";

		$tpl->assign( "metric","$metricname $units" );
		$tpl->assign( "id", $filter[id] );

		# Host columns menu defined in header.php
		$tpl->assign( "cols_menu", $cols_menu );

		$showhosts 	= isset($sh) ? $sh : $default_showhosts;

		$tpl->assign( "checked$showhosts", "checked" );

		if( $showhosts ) 
		{
			if( !isset( $start ) ) 
			{
				$start	="jobstart";
			}
			if( !isset( $stop ) ) 
			{
				$stop	="now";
			}

			$sorted_hosts 	= array();
			$hosts_up 	= $jobs[$filter[id]][nodes];

			//printf( "r %s\n", $job_runningtime );

			$r 		= intval($job_runningtime * 1.2);

			//$jobrange 	= ($job_runningtime < 3600) ? -3600 : -$r ;
			$jobrange 	= -$r ;
			//$jobstart 	= $report_time - $job_runningtime;
			$jobstart 	= $start_time;

			//printf( "jr %s\n", $jobrange );
			//printf( "js %s\n", $jobstart);

			if ( $reports[$metricname] )
			{
				$metricval 	= "g";
			}
			else
			{
				$metricval	= "m";
			}
				
			foreach ( $hosts_up as $host ) 
			{
				//$domain_len 		= 0 - strlen( $domain );

				//if( substr( $host, $domain_len ) != $domain ) 
				//{
				//	$host 		= $host . '.' . $domain;
				//}

				$cpus 			= $metrics[$host]["cpu_num"]["VAL"];

				if ( !$cpus )
				{
					$cpus		= 1;
				}

				$load_one 		= $metrics[$host]["load_one"][VAL];
				$load 			= ((float) $load_one) / $cpus;
				$host_load[$host] 	= $load;

				$percent_hosts[load_color($load)] ++;

				if ($metricname=="load_one")
				{
					$sorted_hosts[$host] 	= $load;
				}
				else
				{
					$sorted_hosts[$host] 	= $metrics[$host][$metricname][VAL];
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

			foreach ( $sorted_hosts as $host=>$value  ) 
			{
				$tpl->newBlock( "sorted_list" );

				$host_url 	= rawurlencode( $host );
				$cluster_url 	= rawurlencode( $clustername );

				$textval 	= "";

				$val 		= $metrics[$host][$metricname];
				$class 		= "metric";
				$host_link	= "\"../../?c=$cluster_url&h=$host_url&r=job&jr=$jobrange&js=$jobstart\"";

				if ( $val["TYPE"] == "timestamp" || $always_timestamp[$metricname] ) 
				{
					$textval 	= date( "r", $val["VAL"] );
				} 
				elseif ( $val["TYPE"] == "string" || $val["SLOPE"] == "zero" || $always_constant[$metricname] || ($max_graphs > 0 and $i > $max_graphs ))
				{
					$textval 	= $val["VAL"] . " " . $val["UNITS"];
				} 
				else 
				{
					$load_color	= load_color($host_load[$host]);
					$graphargs 	= ($reports[$metricname]) ? "g=$metricname&" : "m=$metricname&";
					$graphargs 	.= "z=small&c=$cluster_url&h=$host_url&l=$load_color" . "&v=$val[VAL]&r=job&jr=$jobrange&js=$jobstart";
					if( $max > 0 ) 
					{
						$graphargs	.= "&x=$max&n=$min";
					}
				}
				if ($textval) 
				{
					$cell	= "<td class=$class>".  "<b><a href=$host_link>$host</a></b><br>".  "<i>$metricname:</i> <b>$textval</b></td>";
				} else {
					$cell	= "<td><a href=$host_link>" . "<img src=\"../../graph.php?$graphargs\" " . "alt=\"$host\" border=0></a></td>";
				}

				$tpl->assign( "metric_image", $cell );

				if(! ($i++ % $hostcols) )
				{
					 $tpl->assign( "br", "</tr><tr>" );
				}
			}
		}
	}
}
?>
