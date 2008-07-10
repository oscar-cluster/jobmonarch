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
 * SVN $Id: libtoga.php 531 2008-07-08 09:04:51Z ramonb $
 *
 */


class HTTPVariables
{
	var $clustername, $metricname;
	var $restvars, $httpvars;

	function HTTPVariables( $httpvars, $getvars )
	{
		$this->restvars		= array();

		$this->clustername	= $httpvars["c"] ? $httpvars["c"] : $getvars["c"];
		$this->metricname	= $httpvars["m"] ? $httpvars["m"] : $getvars["m"];

		if( count( $httpvars ) > 0 )
		{
			foreach( $httpvars as $httpvar => $httpval )
			{
				if( $httpval )
				{
					$this->restvars[$httpvar] = $httpval;
				}
			}
		}

		if( count( $getvars ) > 0 )
		{
			foreach( $getvars as $getvar => $getval )
			{
				if( $getval )
				{
					$this->restvars[$getvar] = $getval;
				}
			}
		}
	}

	function getClusterName()
	{
		return $this->clustername;
	}

	function getMetricName()
	{
		return $this->metricname;
	}

	function getHttpVar( $var )
	{
		if( isset( $this->restvars[$var] ) )
		{
			return $this->restvars[$var];
		}
		else
		{
			return null;
		}
	}
}

$CLUSTER_CONFS	= array();

// Monarch's conf
//
include_once "./conf.php";
include_once "./version.php";

global $GANGLIA_PATH;
global $RRDTOOL;
global $JOB_ARCHIVE_DIR;
global $JOB_ARCHIVE_DBASE;
global $skan_str;
global $x_first, $y_first;
global $CLUSTER_CONFS;

$my_dir = getcwd();

// Load Ganglia's PHP
chdir( $GANGLIA_PATH );

include_once "./conf.php";
include_once "./functions.php";
include_once "./ganglia.php";
include_once "./get_context.php";

$context = 'cluster';
include_once "./get_ganglia.php";

// Back to our PHP
chdir( $my_dir );

global $SMALL_CLUSTERIMAGE_MAXWIDTH, $SMALL_CLUSTERIMAGE_NODEWIDTH, $DATA_SOURCE, $HTTP_GET_VARS, $_GET;
$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );

// Set cluster context so that Ganglia will
// provide us with the correct metrics array
//
global $context, $clustername, $reports;

global $default_metric;

// Ganglia's array of host metrics
//
global $metrics, $hosts_up;
global $range, $start;

global $DATETIME_FORMAT;

function makeDate( $time )
{
	global $DATETIME_FORMAT;
	return strftime( $DATETIME_FORMAT, $time );
}


class TarchDbase
{
	var $ip, $dbase, $conn;

	function TarchDbase( $ip = null, $dbase = null )
	{
		global $CLUSTER_CONFS, $clustername;
		global $JOB_ARCHIVE_DBASE;

		// Import cluster specific settings
		//
		foreach( $CLUSTER_CONFS as $confcluster => $conffile )
		{
			if( strtolower( trim($this->clustername) ) == strtolower(trim($confcluster)) )
			{
				include_once $conffile;
			}
		}

		$db_fields = explode( '/', $JOB_ARCHIVE_DBASE );

		$this->ip	= $db_fields[0];
		$this->dbase	= $db_fields[1];
		$this->conn	= null;
	}

	function connect()
	{
		if( $this->ip == null )
			$this->conn = pg_connect( "dbname=".$this->dbase );
		else
			$this->conn = pg_connect( "host=".$this->ip." dbname=".$this->dbase );
	}

	function searchDbase( $id = null, $queue = null, $user = null, $name = null, $start_from_time = null, $start_to_time = null, $end_from_time = null, $end_to_time = null )
	{
		global $SEARCH_RESULT_LIMIT;

		if( $id )
		{
			$select_query = "SELECT job_id FROM jobs WHERE job_id = '$id' AND job_status = 'F'";
			$this->resultcount = 1;
		}
		else
		{
			$query_args = array();
			
			if( $queue )
			{
				$query_args[] = "job_queue ='$queue'";
			}
			if( $user )
			{
				$query_args[] = "job_owner ='$user'";
			}
			if( $name )
			{
				$query_args[] = "job_name = '$name'";
			}
			if( $start_from_time )
			{
				$query_args[] = "job_start_timestamp >= $start_from_time";
			}
			if( $start_to_time )
			{
				$query_args[] = "job_start_timestamp <= $start_to_time";
			}
			if( $end_from_time )
			{
				$query_args[] = "job_stop_timestamp >= $end_from_time";
			}
			if( $end_to_time )
			{
				$query_args[] = "job_stop_timestamp <= $end_to_time";
			}

			$query = "FROM jobs WHERE job_status = 'F' AND ";
			$extra_query_args = '';

			foreach( $query_args as $myquery )
			{
				if( $extra_query_args == '' )
				{
					$extra_query_args = $myquery;
				}
				else
				{
					$extra_query_args .= " AND ".$myquery;
				}
			}
			$query .= $extra_query_args;

			$count_result_idname = "COUNT(job_id)";
			$select_result_idname = "job_id";

			$count_query = "SELECT " . $count_result_idname . " " . $query;

			$count_result = $this->queryDbase( $count_query );
			$this->resultcount = (int) $count_result[0][count];

			$select_query = "SELECT " . $select_result_idname . " " . $query . " ORDER BY job_id DESC LIMIT " . $SEARCH_RESULT_LIMIT;
		}

		$ids = $this->queryDbase( $select_query );

		$ret = array();

		foreach( $ids as $crow)
		{
			$ret[] = $crow[job_id];
		}

		return $ret;
	}

	function getNodesForJob( $jobid )
	{
		$result = $this->queryDbase( "SELECT node_id FROM job_nodes WHERE job_id = '$jobid'" );

		$nodes = array();

		foreach( $result as $result_row ) 
		{
			$nodes[] = $this->getNodeArray( $result_row[node_id] );
		}

		return $nodes;
	}

	function getJobsForNode( $nodeid )
	{
		$result = $this->queryDbase( "SELECT job_id FROM job_nodes WHERE node_id = '$nodeid'" );

		$jobs = array();

		foreach( $result as $result_row )
		{
			$jobs[] = $this->getJobArray( $result_row[job_id] );
		}
		return $jobs;
	}

	function getJobArray( $id )
	{
		$result = $this->queryDbase( "SELECT * FROM jobs WHERE job_id = '$id'" );

		return ( $this->makeArray( $result[0] ) );
	}

	function getNodeArray( $id )
	{
		$result = $this->queryDbase( "SELECT * FROM nodes WHERE node_id = '$id'" );

		return ( $this->makeArray( $result[0] ) );
	}

	function makeArray( $result_row )
	{
		$myar = array();

		foreach( $result_row as $mykey => $myval )
		{
			$map_key = explode( '_', $mykey );

			$rmap_key = array_reverse( $map_key );
			array_pop( $rmap_key );
			$map_key = array_reverse( $rmap_key );
			
			$newkey = implode( '_', $map_key );
			
			$myar[$newkey] = $result_row[$mykey];
		}

		return $myar;
	}

	function queryDbase( $query )
	{
		$result_rows = array();
	
		if( !$this->conn )
		{
			$this->connect();
		}

		$result = pg_query( $this->conn, $query );

		while ($row = pg_fetch_assoc($result))
		{
			$result_rows[] = $row;
		}

		return $result_rows;
	}
}

class TarchRrdGraph
{
	var $rrdbin, $rrdvalues, $clustername, $hostname, $tempdir, $tarchdir, $metrics;

	function TarchRrdGraph( $clustername, $hostname )
	{
		global $RRDTOOL;
		global $JOB_ARCHIVE_DIR;

		$this->rrdbin		= $RRDTOOL;
		$this->rrdvalues	= array();
		$this->tarchdir		= $JOB_ARCHIVE_DIR;
		$this->clustername	= $clustername;
		$this->hostname		= $hostname;
	}

	function doCmd( $command )
	{
		$pipe = popen( $command . ' 2>&1', 'r' );

		if (!$pipe)
		{
			print "pipe failed.";
			return "";
		}

		$output = '';

		while(!feof($pipe))
		{
			$output .= fread($pipe, 1024);
		}

		pclose($pipe);

		$output = explode( "\n", $output );

		return $output;
	}

	function dirList( $dir )
	{
		$dirlist = array();

		if ($handle = opendir( $dir ))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					$dirlist[] = $file;
				}
			}
			closedir($handle);
		}

		return $dirlist;
	}

	function getTimePeriods( $start, $end )
	{
		$times = array();
		$dirlist = $this->dirList( $this->tarchdir . '/' . $this->clustername . '/' . $this->hostname );

		$first = 0;
		$last = 9999999999999;

		foreach( $dirlist as $dir )
		{
			if( $dir > $first and $dir <= $start )
			{
				$first = $dir;
			}
			if( $dir < $last and $dir >= $end )
			{
				$last = $dir;
			}
		}

		foreach( $dirlist as $dir )
		{
			if( $dir >= $first and $dir <= $last and !array_key_exists( $dir, $times ) )
			{
				$times[] = $dir;
			}
		}

		sort( $times );

		return $times;
	}

	function getRrdDirs( $start, $stop )
	{
		$timess = $this->getTimePeriods( $start, $stop );

		$rrd_files = array();

		foreach( $timess as $time )
		{
			$rrd_files[] = $this->tarchdir . '/' . $this->clustername . '/' . $this->hostname. '/'.$time;
		}

		return $rrd_files;
	}

	function getRrdFiles( $metric, $start, $stop )
	{
		$times = $this->getTimePeriods( $start, $stop );

		$rrd_files = array();

		foreach( $times as $time )
		{
			$rrd_files[] = $this->tarchdir . '/' . $this->clustername . '/' . $this->hostname . '/' .$time. '/' . $metric. '.rrd';
		}

		return $rrd_files;
	}
}

class DataSource
{
	var $data, $ip, $port;

	function DataSource()
	{
		global $DATA_SOURCE;

		$ds_fields 	= explode( ':', $DATA_SOURCE );

		$ds_ip 		= $ds_fields[0];
		$ds_port 	= $ds_fields[1];

		$this->ip 	= $ds_ip;
		$this->port 	= $ds_port;

	}

	function getData()
	{
		$errstr;
		$errno = 0;
		$timeout = 3;

		$fp = fsockopen( $this->ip, $this->port, $errno, $errstr, $timeout );

		if( !$fp )
		{
			echo 'Unable to connect to '.$this->ip.':'.$this->port; // printf( 'Unable to connect to [%s:%.0f]', $this->ip, $this->port );
			return;
		}

		stream_set_timeout( $fp, 30 );

		while ( !feof( $fp ) )
		{
			$data .= fread( $fp, 16384 );
		}

		fclose( $fp );

		return $data;
	}
}

class DataGatherer
{
	var $xmlhandler, $data, $httpvars;

	function DataGatherer( $cluster )
	{
		$this->cluster	= $cluster;
		$this->httpvars = $httpvars;
	}

	function parseXML( $data )
	{
		$this->parser 		= xml_parser_create();
		$this->xmlhandler 	= new TorqueXMLHandler( $this->cluster );

		xml_set_element_handler( $this->parser, array( &$this->xmlhandler, 'startElement' ), array( &$this->xmlhandler, 'stopElement' ) );

		if ( !xml_parse( $this->parser, $data ) )
		{
			$error = sprintf( 'XML error: %s at %d', xml_error_string( xml_get_error_code( $this->parser ) ), xml_get_current_line_number( $this->parser ) );
		}
	}

	function printInfo()
	{
		$handler = $this->xmlhandler;
		$handler->printInfo();
	}

	function getUsingFQDN()
	{
		$handler = $this->xmlhandler;
		return $handler->getUsingFQDN();
	}

	function getNodes()
	{
		$handler = $this->xmlhandler;
		return $handler->getNodes();
	}

	function getNode( $node )
	{
		$handler = $this->xmlhandler;
		return $handler->getNode( $node );
	}

	function getCpus()
	{
		$handler = $this->xmlhandler;
		return $handler->getCpus();
	}

	function getJobs()
	{
		$handler = $this->xmlhandler;
		return $handler->getJobs();
	}

	function getJob( $job )
	{
		$handler = $this->xmlhandler;
		return $handler->getJob( $job );
	}

	function getHeartbeat()
	{
		$handler = $this->xmlhandler;
		return $handler->getHeartbeat();
	}

	function isJobmonRunning()
	{
		$handler = $this->xmlhandler;
		return $handler->isJobmonRunning();
	}
}

class TorqueXMLHandler
{
	var $clusters, $heartbeat, $nodes, $jobs, $clustername, $proc_cluster;

	function TorqueXMLHandler( $clustername )
	{
		$jobs			= array();
		$clusters 		= array();
		$this->nodes 		= array();
		$heartbeat 		= array();
		$down_nodes		= array();
		$offline_nodes		= array();
		$this->clustername	= $clustername;
		$this->fqdn		= 1;
	}

	function getUsingFQDN()
	{
		return $this->fqdn;
	}

	function getCpus()
	{
		$cpus = 0;

		if( isset( $this->jobs ) && count( $this->jobs ) > 0 )
		{
			foreach( $this->jobs as $jobid=>$jobattrs )
			{
				$nodes	= count( $jobattrs[nodes] );
				$ppn	= (int) $jobattrs[ppn] ? $jobattrs[ppn] : 1;
				$mycpus	= $nodes * $ppn;

				$cpus	= $cpus + $mycpus;
			}
		}
	}

	function isJobmonRunning()
	{
		if (isset( $this->heartbeat['time'] ))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	function makeHostname( $thostname, $tdomain=null )
	{
		// Should hostname be FQDN or short w/o domain
		//
		$nodes = &$this->nodes;

		$fqdn = 1;

		//$tdomain = explode( '.', $thostname );
		//
		// TODO?: extract domain from hostname or something?

		if( $tdomain )
		{
			$domain_len	= 0 - strlen( $tdomain );

			// Let's see if Ganglia use's FQDN or short hostnames
			//
			foreach( $nodes as $hostname => $nimage )
			{
				if( substr( $hostname, $domain_len ) != $tdomain )
				{
					$fqdn	= 0;
				}
			}
		}
		else
		{
			$fqdn	= 0;
		}
	
		if( $tdomain && $fqdn )
		{
			if( substr( $thostname, $domain_len ) != $tdomain )
			{
				$thostname = $thostname . '.'.$tdomain;
			} 
			else
			{
				$thostname = $thostname;
			}
		}

		return $thostname;
	}

	function startElement( $parser, $name, $attrs )
	{
		$jobs = $this->jobs;
		$nodes = $this->nodes;

		if ( $attrs[TN] )
		{
			// Ignore dead metrics. Detect and mask failures.
			if ( $attrs[TN] > $attrs[TMAX] * 4 )
			{
				return;
			}
		}

		$jobid = null;

		if( $name == 'CLUSTER' )
		{
			$this->proc_cluster = $attrs[NAME];
		}
		else if( $name == 'HOST' and $this->proc_cluster == $this->clustername)
		{
			$hostname = $attrs[NAME];

			$location = $attrs[LOCATION];

			if( !isset( $nodes[$hostname] ) )
			{
				$nodes[$hostname] = new NodeImage( $this->proc_cluster, $hostname );
			}
		}
		else if( $name == 'METRIC' and strstr( $attrs[NAME], 'MONARCH' ) and $this->proc_cluster == $this->clustername )
		{
			if( strstr( $attrs[NAME], 'MONARCH-HEARTBEAT' ) )
			{
				$this->heartbeat['time'] = $attrs[VAL];
			}
			else if( strstr( $attrs[NAME], 'MONARCH-DOWN' ) )
			{
				$fields		= explode( ' ', $attrs[VAL] );

				$nodes_down	= array();
				$down_domain	= null;

				foreach( $fields as $f )
				{
					$togavalues	= explode( '=', $f );

					$toganame	= $togavalues[0];
					$togavalue	= $togavalues[1];

					if( $toganame == 'nodes' )
					{
						$mynodes = explode( ';', $togavalue );

						foreach( $mynodes as $node )
						{
							$nodes_down[] = $node;
						}
					}
					else if( $toganame == 'domain' )
					{
						$down_domain = $togavalue;
					}
					else if( $toganame == 'reported' )
					{
						if( !isset( $this->down_nodes['heartbeat'] ) )
						{
							$this->down_nodes[$togavalue]	= array( $nodes_down, $down_domain );
						}
					}
				}
			}
			else if( strstr( $attrs[NAME], 'MONARCH-OFFLINE' ) )
			{
				$fields		= explode( ' ', $attrs[VAL] );

				$nodes_offline	= array();
				$offline_domain	= null;

				foreach( $fields as $f )
				{
					$togavalues	= explode( '=', $f );

					$toganame	= $togavalues[0];
					$togavalue	= $togavalues[1];

					if( $toganame == 'nodes' )
					{
						$mynodes = explode( ';', $togavalue );

						foreach( $mynodes as $node )
						{
							$nodes_offline[] = $node;
						}
					}
					else if( $toganame == 'domain' )
					{
						$offline_domain = $togavalue;
					}
					else if( $toganame == 'reported' )
					{
						if( !isset( $this->offline_nodes['heartbeat'] ) )
						{
							$this->offline_nodes[$togavalue] = array( $nodes_offline, $offline_domain );
						}
					}
				}
			}
			else if( strstr( $attrs[NAME], 'MONARCH-JOB' ) )
			{
				sscanf( $attrs[NAME], 'MONARCH-JOB-%d-%d', $jobid, $monincr );

				if( !isset( $jobs[$jobid] ) )
				{
					$jobs[$jobid] = array();
				}

				$fields = explode( ' ', $attrs[VAL] );

				foreach( $fields as $f )
				{
					$togavalues = explode( '=', $f );

					$toganame = $togavalues[0];
					$togavalue = $togavalues[1];

					if( $toganame == 'nodes' )
					{
						if( $jobs[$jobid][status] == 'R' )
						{
							if( !isset( $jobs[$jobid][$toganame] ) )
							{
								$jobs[$jobid][$toganame] = array();
							}

							$mynodes = explode( ';', $togavalue );

							foreach( $mynodes as $node )
							{
								if( !in_array( $node, $jobs[$jobid][$toganame] ) )
								{
									$jobs[$jobid][$toganame][] = $node;
								}
							}

						}
						else if( $jobs[$jobid][status] == 'Q' )
						{
							$jobs[$jobid][$toganame] = $togavalue;
						}
					}
					else
					{
						$jobs[$jobid][$toganame] = $togavalue;
					}
				}

				if( isset( $jobs[$jobid][nodes] ) )
				{
					$nr_nodes = count( $jobs[$jobid][nodes] );
		
					if( $jobs[$jobid][status] == 'R' )
					{

						if( isset( $jobs[$jobid][domain] ) )
						{
							$domain		= $jobs[$jobid][domain];
							$domain_len	= 0 - strlen( $domain );

							// Let's see if Ganglia use's FQDN or short hostnames
							//
							foreach( $nodes as $hostname => $nimage )
							{
					
								if( substr( $hostname, $domain_len ) != $domain )
								{
									$this->fqdn	= 0;
								}
							}
						}
						else
						{
							$this->fqdn	= 0;
						}

						foreach( $jobs[$jobid][nodes] as $node )
						{

							// Only add domain name to the hostname if Ganglia is doing that too
							//
							if( $this->fqdn && isset( $jobs[$jobid][domain] ) )
							{
								if( substr( $node, $domain_len ) != $domain )
								{
									$host = $node. '.'.$domain;
								} else
								{
									$host = $node;
								}
							}
							else
							{
								$host	= $node;
							}

							if( !isset( $nodes[$host] ) )
							{
								$my_node = new NodeImage( $this->proc_cluster, $host );
							}
							else
							{
								$my_node = $nodes[$host];
							}

							if( !$my_node->hasJob( $jobid ) )
							{
								if( isset( $jobs[$jobid][ppn] ) )
								{
									$my_node->addJob( $jobid, ((int) $jobs[$jobid][ppn]) );
								}
								else
								{
									$my_node->addJob( $jobid, 1 );
								}
							}

							$nodes[$host] = $my_node;
						}
					}
				}
			}
		}
		$this->jobs	= $jobs;
		$this->nodes	= $nodes;
	}

	function stopElement( $parser, $name )
	{
		$nodes	= $this->nodes;

		if( $name == "GANGLIA_XML" )
		{
			if( sizeof( $this->down_nodes ) > 0 )
			{
				foreach( $this->down_nodes as $reported => $dnodes )
				{
					if( $reported == $this->heartbeat['time'] )
					{
						$domain = $dnodes[1];

						foreach( $dnodes[0] as $downhost )
						{
							$downhost = $this->makeHostname( $downhost, $domain );

							if( isset( $nodes[$downhost] ) )
							{
								// OMG PHP4 is fking stupid!
								// $nodes[$downhost]->setDown( 1 ) won't work here..
								//
								$mynode = $nodes[$downhost];
								$mynode->setDown( 1 );
								$nodes[$downhost] = $mynode;
							}
						}
					}
				}
			}

			if( sizeof( $this->offline_nodes ) > 0 )
			{
				foreach( $this->offline_nodes as $reported => $onodes )
				{
					if( $reported == $this->heartbeat['time'] )
					{
						$domain = $onodes[1];

						foreach( $onodes[0] as $offlinehost )
						{
							$offlinehost = $this->makeHostname( $offlinehost, $domain );

							if( isset( $nodes[$offlinehost] ) )
							{
								// OMG PHP4 is fking stupid!
								// $nodes[$offlinehost]->setDown( 1 ) won't work here..
								//
								$mynode = $nodes[$offlinehost];
								$mynode->setOffline( 1 );
								$nodes[$offlinehost] = $mynode;
							}
						}
					}
				}
			}
		}

		$this->nodes = $nodes;
	}

	function printInfo()
	{
		$jobs = &$this->jobs;

		printf( "---jobs---\n" );

		foreach( $jobs as $jobid => $job )
		{
			printf( "job %s\n", $jobid );

			if( isset( $job[nodes] ) )
			{
				foreach( $job[nodes] as $node )
				{
					$mynode = $this->nodes[$node];
					$hostname = $mynode->getHostname();
					$location = $mynode->getLocation();

					printf( "\t- node %s\tlocation %s\n", $hostname, $location );
				}
			}
		}

		printf( "---nodes---\n" );

		$nodes = &$this->nodes;

		foreach( $nodes as $node )
		{
			$hostname = $node->getHostname();
			$location = $node->getLocation();
			$jobs = implode( ' ', $node->getJobs() );
			printf( "* node %s\tlocation %s\tjobs %s\n", $hostname, $location, $jobs );
		}
	}

	function getNodes()
	{
		return $this->nodes;
	}

	function getNode( $node )
	{
		$nodes = &$this->nodes;

		if( isset( $nodes[$node] ) )
		{
			return $nodes[$node];
		}
		else
		{
			return NULL;
		}
	}

	function getJobs()
	{
		return $this->jobs;
	}

	function getJob( $job )
	{
		$jobs = &$this->jobs;

		if( isset( $jobs[$job] ) )
		{
			return $jobs[$job];
		}
		else
		{
			return NULL;
		}
	}

	function getHeartbeat()
	{
		return $this->heartbeat['time'];
	}
}

class NodeImage
{
	var $image, $x, $y, $hostname, $jobs, $tasks, $showinfo;

	function NodeImage( $cluster, $hostname )
	{
		global $SMALL_CLUSTERIMAGE_NODEWIDTH;

		$this->jobs		= array();
		$this->tasks		= 0;
		$this->hostname		= $hostname;
		$this->cpus		= $this->determineCpus();
		$this->clustername	= $cluster;
		$this->showinfo		= 1;
		$this->size		= $SMALL_CLUSTERIMAGE_NODEWIDTH;
		$this->down		= 0;
		$this->offline		= 0;
	}

	function addJob( $jobid, $cpus )
	{
		$jobs		= &$this->jobs;
		$jobs[]		= $jobid;
		$this->jobs	= $jobs;

		$this->addTask( $cpus );
	}

	function hasJob( $jobid )
	{
		$jobfound = 0;

		if( count( $this->jobs ) > 0 )
		{
			foreach( $this->jobs as $job )
			{
				if( $job == $jobid )
				{
					$jobfound = 1;
				}
			}
		}

		return $jobfound;
	}

	function addTask( $cpus )
	{
		$this->tasks = $this->tasks + $cpus;
	}
	function setDown( $down )
	{
		$this->down = $down;
	}
	function isDown()
	{
		return $this->down;
	}
	function setOffline( $offline )
	{
		$this->offline = $offline;
	}
	function isOffline()
	{
		return $this->offline;
	}
	function setImage( $image )
	{
		$this->image = $image;
	}
	function setCoords( $x, $y )
	{
		$this->x = $x;
		$this->y = $y;
	}
	function getX()
	{
		return $this->x;
	}
	function getY()
	{
		return $this->y;
	}

	function getImagemapArea()
	{
		$area_topleft		= $this->x . "," . $this->y;
		$area_bottomright	= ($this->x + $this->size) . "," . ($this->y + $this->size);
		$area_coords		= $area_topleft . "," . $area_bottomright;

		$area_href		= "./?c=" . $this->clustername . "&h=" . $this->hostname;

		$area_tooltip		= $this->hostname;

		if( $this->down)
		{
			$area_tooltip		= $area_tooltip . ": DOWN";
		}
		else if( $this->offline )
		{
			$area_tooltip		= $area_tooltip . ": OFFLINE";
		}

		$area_tooltip		= $area_tooltip . ": " . implode( " ", $this->jobs );

		$tag_href		= "HREF=\"" . $area_href . "\"";
		$tag_coords		= "COORDS=\"" . $area_coords . "\"";
		$tag_tooltip1		= "ALT=\"" . $area_tooltip . "\"";
		$tag_tooltip2		= "TITLE=\"" . $area_tooltip . "\"";

		return ("<AREA SHAPE=\"RECT\" " . $tag_coords . " " . $tag_href . " " . $tag_tooltip1 . " " . $tag_tooltip2 . ">");
	}

	function colorHex( $color )
	{
		$my_color = imageColorAllocate( $this->image, hexdec( substr( $color, 0, 2 )), hexdec( substr( $color, 2, 2 )), hexdec( substr( $color, 4, 2 )) );

		return $my_color;
	}

	function setLoad( $load )
	{
		$this->load = $load;
	}

	function setHostname( $hostname )
	{
		$this->hostname = $hostname;
	}

	function getHostname()
	{
		return $this->hostname;
	}

	function getJobs()
	{
		return $this->jobs;
	}

	function setShowinfo( $showinfo )
	{
		$this->showinfo = $showinfo;
	}

	function drawSmall()
	{
		global $SMALL_CLUSTERIMAGE_NODEWIDTH;

		$this->size	= $SMALL_CLUSTERIMAGE_NODEWIDTH;

		$this->draw();
	}

	function drawBig()
	{
		global $BIG_CLUSTERIMAGE_NODEWIDTH;

		$this->size	= $BIG_CLUSTERIMAGE_NODEWIDTH;

		$this->draw();
	}

	function draw()
	{
		global $JOB_NODE_MARKING, $NODE_DOWN_MARKING, $NODE_OFFLINE_MARKING;

		$black_color = imageColorAllocate( $this->image, 0, 0, 0 );
		$size = $this->size;

		imageFilledRectangle( $this->image, $this->x, $this->y, $this->x+($size), $this->y+($size), $black_color );

		if( $this->showinfo)
		{
			$this->load = $this->determineLoad();

			if( !isset( $this->image ) or !isset( $this->x ) or !isset( $this->y ) )
			{
				printf( "aborting\n" );
				printf( "x %d y %d load %f\n", $this->x, $this->y, $load );
				return;
			}

			// Convert Ganglias Hexadecimal load color to a Decimal one
			//
			$load = $this->determineLoad();	
			$usecolor = $this->colorHex( load_color($load) );
			imageFilledRectangle( $this->image, $this->x+1, $this->y+1, $this->x+($size-1), $this->y+($size-1), $usecolor );
			if( $this->down )
			{
				imageString( $this->image, 1, $this->x+(($size/2)-1), $this->y+(($size/2)-4), $NODE_DOWN_MARKING, $black_color );
			}
			else if( $this->offline )
			{
				imageString( $this->image, 1, $this->x+(($size/2)-1), $this->y+(($size/2)-4), $NODE_OFFLINE_MARKING, $black_color );
			}
			else if( count( $this->jobs ) > 0 )
			{
				imageString( $this->image, 1, $this->x+(($size/2)-1), $this->y+(($size/2)-4), $JOB_NODE_MARKING, $black_color );
			}
		}
		else
		{
			// White
			$usecolor = imageColorAllocate( $this->image, 255, 255, 255 );
			imageFilledRectangle( $this->image, $this->x+1, $this->y+1, $this->x+($size-1), $this->y+($size-1), $usecolor );
		}
	}

	function determineCpus()
	{
		global $metrics;

		$cpus = $metrics[$this->hostname][cpu_num][VAL];

		if (!$cpus)
		{
			$cpus=1;
		}

		return $cpus;
	}

	function determineLoad()
	{
		global $metrics;

		$load_one	= $metrics[$this->hostname][load_one][VAL];
		$load		= ((float) $load_one)/$this->cpus;

		return $load;
	}
}

class ClusterImage
{
	var $dataget, $image, $clustername;
	var $filtername, $filters;

	function ClusterImage( $data, $clustername )
	{
		$this->dataget		= new DataGatherer( $clustername );
		$this->data		= $data;
		$this->clustername	= $clustername;
		$this->filters		= array();
		$this->size		= 's';
		$this->width		= 0;
		$this->height		= 0;
		$this->output		= 1;
	}

	function getWidth()
	{
		return $this->width;
	}
	function getHeight()
	{
		return $this->height;
	}
	function setSmall()
	{
		$this->size	= 's';
	}
	function setBig()
	{
		$this->size	= 'b';
	}
	function setNoimage()
	{
		$this->output	= 0;
	}
	function isSmall()
	{
		return ($this->size == 's');
	}
	function isBig()
	{
		return ($this->size == 'b');
	}
	function setFilter( $filtername, $filtervalue )
	{
		$this->filters[$filtername] = $filtervalue;
	}

	function filterNodes( $jobs, $nodes )
	{
		$filtered_nodes = array();

		foreach( $nodes as $node )
		{
			$hostname = $node->getHostname();

			$addhost = 1;

			if( count( $this->filters ) > 0 )
			{
				$mynjobs = $node->getJobs();

				if( count( $mynjobs ) > 0 )
				{
					foreach( $mynjobs as $myjob )
					{
						foreach( $this->filters as $filtername => $filtervalue )
						{
							if( $filtername!=null && $filtername!='' )
							{
								if( $filtername == 'jobid' && !$node->hasJob( $filtervalue) )
								{
									$addhost = 0;
								}
								else if( $filtername != 'jobid' )
								{
									if( $jobs[$myjob][$filtername] != $filtervalue )
									{
										$addhost = 0;
									}
								}
							}
						}
					}
				}
				else
				{
					$addhost = 0;
				}
			}

			if( $addhost )
			{
				$filtered_nodes[] = $hostname;
			}
		}

		return $filtered_nodes;
	}

	function draw()
	{
		global $SMALL_CLUSTERIMAGE_MAXWIDTH, $SMALL_CLUSTERIMAGE_NODEWIDTH;
		global $BIG_CLUSTERIMAGE_MAXWIDTH, $BIG_CLUSTERIMAGE_NODEWIDTH;
		global $CLUSTER_CONFS, $confcluster, $SHOW_EMPTY_COLUMN, $SHOW_EMPTY_ROW;

		global $SORTBY_HOSTNAME, $SORT_ORDER, $skan_str;
		global $x_first, $y_first;

		foreach( $CLUSTER_CONFS as $confcluster => $conffile )
		{
			if( strtolower( trim($this->clustername) ) == strtolower(trim($confcluster)) )
			{
				include_once $conffile;
			}
		}

		$mydatag = $this->dataget;
		$mydatag->parseXML( $this->data );

		if( $this->isSmall() )
		{
			$max_width	= $SMALL_CLUSTERIMAGE_MAXWIDTH;
			$node_width	= $SMALL_CLUSTERIMAGE_NODEWIDTH;
		}
		else if( $this->isBig() )
		{
			$max_width	= $BIG_CLUSTERIMAGE_MAXWIDTH;
			$node_width	= $BIG_CLUSTERIMAGE_NODEWIDTH;
		}

		$nodes		= $mydatag->getNodes();
		$nodes_hosts	= array_keys( $nodes );

		$nodes_nr	= count( $nodes );

		$nodes_size	= $nodes_nr*$node_width;
		$node_rows	= 0;

		if( $nodes_size > $max_width )
		{
			$nodes_per_row = ( (int) ($max_width/$node_width) );
		}
		else
		{
			$nodes_per_row = $nodes_size;
			$node_rows = 1;
		}

		if( $nodes_per_row < $nodes_nr )
		{
			$node_rows = ( (int) ($nodes_nr/$nodes_per_row) );
			$node_rest = fmod( $nodes_nr, $nodes_per_row );

			if( $node_rest > 0 )
			{
				$node_rows++;
			}
		}

		$y_offset	= 0;
		$font 		= 2;
		$fontwidth	= ImageFontWidth( $font );
		$fontheight	= ImageFontHeight( $font );
		$fontspaceing	= 2;
		$y_offset	= $fontheight + (2 * $fontspaceing);

		$this->width	= $max_width;
		$this->height	= ($y_offset + (($node_rows*$node_width)+1) );

		$jobs = $mydatag->getJobs();
		$filtered_nodes = $this->filterNodes( $jobs, $nodes );

		if( $SORTBY_HOSTNAME != "" )
		{
		        $sorted 	= array();

			$x_first	= 0;
			$y_first	= 0;

			$skan_str	= $SORTBY_HOSTNAME;

			global $x_present, $y_present;
			$x_present	= false;
			$y_present	= false;

			// Should we scan by X, Y or both
			//
			if(strpos( $SORTBY_HOSTNAME, "{x}" ) != false )
			{
				$x_str		= "{x}";
				$x_present	= true;
			}
			else if(strpos( $SORTBY_HOSTNAME, "{X}" ) != false )
			{
				$x_str		= "{X}";
				$x_present	= true;
			}
			if(strpos( $SORTBY_HOSTNAME, "{y}" ) != false )
			{
				$y_str		= "{y}";
				$y_present	= true;
			}
			else if(strpos( $SORTBY_HOSTNAME, "{Y}" ) != false )
			{
				$y_str		= "{Y}";
				$y_present	= true;
			}

			// If we should scan for both X and Y: see which one is first
			//
			if(( strpos( $SORTBY_HOSTNAME, $x_str ) < strpos( $SORTBY_HOSTNAME, $y_str ) ) && ( $x_present && $y_present ))
			{
				$x_first	= 1;
			}
			else if(( strpos( $SORTBY_HOSTNAME, $x_str ) > strpos( $SORTBY_HOSTNAME, $y_str ) ) && ( $x_present && $y_present ))
			{
				$y_first	= 1;
		
			}
			else if( $x_present )
			{
				$x_first	= 1;
			}
			else if( $y_present )
			{
				$y_first	= 1;
			}

			// Now replace our {x} and {y} with %d for sscanf parsing 
			//
			if(( $x_first ) && ( $x_present && $y_present ) )
			{
				$skan_str	= str_replace( $x_str, "%d", $skan_str );
				$skan_str	= str_replace( $y_str, "%d", $skan_str );
			} 
			else if( $x_present)
			{
				$skan_str	= str_replace( $x_str, "%d", $skan_str );
			}
			else if( $y_present)
			{
				$skan_str	= str_replace( $y_str, "%d", $skan_str );
			}

			$x_min		= null;
			$x_max		= null;
			$y_min		= null;
			$y_max		= null;

			$x_columns	= array();
			$y_rows		= array();

			// Now let's walk through all our nodes and see which one are valid for our scan pattern
			//
			foreach( $nodes as $hostname => $node )
			{
				$x	= null;
				$y	= null;

				if( $x_present && $y_present )
				{
					if( $x_first )
					{
						$n = sscanf( $hostname, $skan_str, $x, $y );
					}
					else if( $y_first )
					{
						$n = sscanf( $hostname, $skan_str, $y, $x );
					}

					// Remove nodes that don't match
					//
					if( $n < 2 )
					{
						// This node hostname has no match for: {x} and {y}
						//
						unset( $nodes[$hostname] );
					}
				}
				else if( $x_present && !$y_present )
				{
					$n = sscanf( $hostname, $skan_str, $x );

					// Remove nodes that don't match
					//
					if( $n < 1 )
					{
						// This node hostname has no match for: {x}
						//
						unset( $nodes[$hostname] );
					}
					$y	= 1;
				}
				else if( $y_present && !$x_present )
				{
					$n = sscanf( $hostname, $skan_str, $y );

					// Remove nodes that don't match
					//
					if( $n < 1 )
					{
						// This node hostname has no match for: {y}
						//
						unset( $nodes[$hostname] );
					}
					$x	= 1;
				}

				// Determine the lowest value of {x} that exists in all node hostnames
				//
				if( !$x_min && $x != null )
				{
					$x_min	= $x;
				}
				else if( $x < $x_min && $x != null )
				{
					$x_min	= $x;
				}

				// Determine the highest value of {x} that exists in all node hostnames
				//
				if( !$x_max && $x != null )
				{
					$x_max	= $x;
				}
				else if( $x > $x_max && $x != null )
				{
					$x_max	= $x;
				}

				// Determine the lowest value of {y} that exists in all node hostnames
				//
				if( !$y_min && $y != null )
				{
					$y_min	= $y;
				}
				else if( $y < $y_min && $y != null )
				{
					$y_min	= $y;
				}

				// Determine the highest value of {y} that exists in all node hostnames
				//
				if( !$y_max && $y != null )
				{
					$y_max	= $y;
				}
				else if( $y > $y_max && $y != null )
				{
					$y_max	= $y;
				}

				// Store which non-empty columns and rows we found
				//
				if( !in_array( $x, $x_columns ) )
				{
					$x_columns[] = $x;
				}
				if( !in_array( $y, $y_rows ) )
				{
					$y_rows[] = $y;
				}
			}

			// Sort all the nodes (alpha and numerically)
			// 1: gb-r1n1, 2: gb-r1n2, 3: gb-r2n1, etc
			//
			$sorted_nodes	= usort( $nodes, "cmp" );

			//print_r( $x_columns ) ;

			$cur_node	= 0;

			$x_offset	= 0;
			$y_offset	= 0;
			$font 		= 2;
			$fontwidth	= ImageFontWidth( $font );
			$fontheight	= ImageFontHeight( $font );
			$fontspaceing	= 2;

			if( $this->isSmall() ) 
			{
				$y_offset	= $y_offset + (2 * $fontspaceing) + $fontheight;
			}

			if( $this->isBig() ) 
			{
				$y_offset	= ($fontheight * (1 + strlen( $x_max) ) ) + ((2 + strlen( $x_max)) * $fontspaceing);
				$x_offset	= ($fontwidth * (1 + strlen( $y_max) ) ) + ((2 + strlen( $y_max)) * $fontspaceing);
			}

			$image_width	= $x_offset + ($node_width * ($x_max-$x_min+2));

			if( $this->isSmall() ) 
			{
				$image_width	= $max_width;
			}
			else if( $this->isBig() ) 
			{
				$image_width	= ($image_width < $max_width) ? $image_width : $max_width;
			}
			$image_height	= $y_offset + ($node_width * ($y_max-$y_min+2));

			$this->width	= $image_width;
			$this->heigth	= $image_heigth;

			$image		= imageCreateTrueColor( $image_width, $image_height );
			$colorwhite	= imageColorAllocate( $image, 255, 255, 255 );

			imageFill( $image, 0, 0, $colorwhite );

			if( $this->isSmall() )
			{
				// Draw a fancy little header text to explain what it is
				//
				$colorblue	= imageColorAllocate( $image, 0, 0, 255 );

				imageString( $image, $font, 2, 2, "Monarch Joblist - cluster: ".$this->clustername, $colorblue );
			}

			if( $this->isBig() && ( isset( $SORT_XLABEL ) || isset( $SORT_YLABEL ) ) )
			{
				$colorblue	= imageColorAllocate( $image, 0, 0, 255 );

				if( isset( $SORT_XLABEL ) )
				{
					// Print the {x} label: rack
					//
					imageString( $image, $font, $x_offset, $fontspaceing, $SORT_XLABEL, $colorblue );
				}

				if( isset( $SORT_YLABEL ) )
				{
					// Stupid php without imageStringDown function... we'll make one ourself
					//

					// Print the {y} label: node
					//
					imageStringDown( $image, $font, $fontspaceing, $y_offset, $SORT_YLABEL, $colorblue );
				}
			}

			$previous_n	= 0;
			$previous_m	= 0;
			$x_empty_count	= 0;
			$y_empty_count	= 0;

			// Let's start assigning x,y coordinates now
			//
			for( $n = $x_min; $n <= $x_max; $n++ )
			{
				for( $m = $y_min; $m <= $y_max; $m++ )
				{
					if( $x_min > 0 )
					{
						$x	= $x_offset + ( ($n-$x_min) * $node_width ) - ($x_empty_count * $node_width);
					}
					if( $y_min > 0 )
					{
						$y	= $y_offset + ( ($m-$y_min) * $node_width ) - ($y_empty_count * $node_width);
					}

					// Don't show empty rows/columns if option enabled
					//
					if( !in_array( $n, $x_columns ) && !$SHOW_EMPTY_COLUMN )
					{
						// Skip to next iteration: we don't want a empty column
						//
						if( $n > $previous_n )
						{
							$previous_n = $n;
							$x_empty_count++;
						}
						continue;
					}
					if( !in_array( $m, $y_rows ) && !$SHOW_EMPTY_ROW )

					{
						// Skip to next iteration: we don't want a empty column
						//
						if( $m > $previous_m )
						{
							$previous_m = $m;
							$y_empty_count++;
						}
						continue;
					}

					if( $this->isBig() ) 
					{
						// Draw y(node) column number header
						//
						if(( $n == $x_min ) && ( isset($SORT_YLABEL) ) )
						{
							$mfontspacing	= 1;

							$ylabel_x	= $x - ( $fontwidth * strlen( $y_max ) ) - $mfontspacing;
							$ylabel_y	= $y;

							imageString( $image, $font, $ylabel_x, $ylabel_y, strval( $m ), $colorblue );

							$xmin_hit[$n]	= true;
						}

						// Draw x(rack) column number header
						//
						if(( $m == $y_min ) && ( isset($SORT_XLABEL) ) )
						{
							$mfontspacing	= 2;
							$xlabel_y	= $y - ( $fontheight * strlen( $x_max ) );
							$xlabel_x	= $x + $mfontspacing; 

							imageStringDown( $image, $font, $xlabel_x, $xlabel_y, strval( $n ), $colorblue );
						}
					}

					if( isset( $nodes[$cur_node] ) ) 
					{
						$host	= $nodes[$cur_node]->getHostname();

						if( $x_present && $y_present )
						{
							if( $x_first )
							{
								$nn = sscanf( $host, $skan_str, $rx, $ry );
							}
							else if( $y_first )
							{
								$nn = sscanf( $host, $skan_str, $ry, $rx );
							}
							if ( $nn < 2 )
							{
								//printf( "skipping node %s - y present & x present + <2 x,y matchs\n", $host);
								continue;
							}
							if( intval( $rx ) > $n )
							{
								// If x(rack) is higher than current x, skip to next x(rack)
								//
								$m		= $y_max + 1;

								continue;
							}
							if( intval( $ry ) > $m )
							{
								// If y(node) is higher than current y, skip to next y(node)
								//
								continue;
							}
						}
						else if( $x_present )
						{
							$nn = sscanf( $host, $skan_str, $rx );
						}
						else if( $y_present )
						{
							$nn = sscanf( $host, $skan_str, $ry );
						}

						if( !in_array( $host, $filtered_nodes ) )
						{
							// This node has been filtered out: we only want to see certain nodes
							//
							$nodes[$cur_node]->setShowinfo( 0 );
						}

						$nodes[$cur_node]->setCoords( $x, $y );
						$nodes[$cur_node]->setImage( $image );

						if( $this->isSmall() )
						{
							$nodes[$cur_node]->drawSmall();
						}
						else if( $this->isBig() )
						{
							$nodes[$cur_node]->drawBig();
						}

						$cur_node++;
					}
				}
			}

		}
		else
		{
			if( $this->isSmall() )
			{
				$image		= imageCreateTrueColor( $max_width, ($y_offset + (($node_rows*$node_width)+1) ) );
			}
			else if( $this->isBig() )
			{
				$image_width	= ($node_width * $nodes_nr) + 2;
				$image_width	= ($image_width < $max_width) ? $image_width : $max_width;
				$image		= imageCreateTrueColor( $image_width, ($y_offset + (($node_rows*$node_width)+1) ) );
			}
			$colorwhite	= imageColorAllocate( $image, 255, 255, 255 );

			imageFill( $image, 0, 0, $colorwhite );

			if( $this->isSmall() )
			{
				$colorblue	= imageColorAllocate( $image, 0, 0, 255 );

				imageString( $image, $font, 2, 2, "Monarch Joblist - cluster: ".$this->clustername, $colorblue );
			}

			for( $n = 0; $n < $node_rows; $n++ )
			{
				for( $m = 0; $m < $nodes_per_row; $m++ )
				{
					$x = ($m * $node_width);
					$y = $y_offset + ($n * $node_width);

					$cur_node = ($n * $nodes_per_row) + ($m);
					$host = $nodes_hosts[$cur_node];

					if( isset( $nodes[$host] ) )
					{
						$nodes[$host]->setCoords( $x, $y );
						$nodes[$host]->setImage( $image );

						if( !in_array( $host, $filtered_nodes ) )
						{
							$nodes[$host]->setShowinfo( 0 );
						}

						if( $this->isSmall() )
						{
							$nodes[$host]->drawSmall();
						}
						else if( $this->isBig() )
						{
							$nodes[$host]->drawBig();
						}
					}
				}
			}
		}
	
		$this->nodes	= &$nodes;

		if ($this->output)
		{
			header( 'Content-type: image/png' );
			imagePNG( $image );
			imageDestroy( $image );
		}
	}

	function getImagemapArea()
	{
		$clusterimage_map	= "";

		foreach( $this->nodes as $hostname => $node )
		{
			$node_map		= $node->getImagemapArea();
			$clusterimage_map	.= $node_map;
		}

		return $clusterimage_map;
	}
}

class EmptyImage
{
	function draw()
	{
		$image		= imageCreateTrueColor( 1, 1 );
		$colorwhite	= imageColorAllocate( $image, 255, 255, 255 );
		imageFill( $image, 0, 0, $colorwhite );                         

		header( 'Content-type: image/png' );
		imagePNG( $image );
		imageDestroy( $image );
	}
}

class HostImage
{
	var $data_gather, $cluster, $host, $node, $image;
	var $headerstrlen;

	function HostImage( $data_gather, $cluster, $host )
	{
		$this->data_gather 	= $data_gather;
		$this->cluster		= $cluster;
		$this->host		= $host;
		$this->y_offset		= 0;
		$this->font		= 2;
		$this->fontspaceing	= 2;
		$this->headerstrlen	= array();

		$this->fontheight	= ImageFontHeight( $this->font );
		$this->fontwidth	= ImageFontWidth( $this->font );

		$dg			= &$this->data_gather;
		$this->node		= &$dg->getNode( $this->host );
		$n			= &$this->node;
		$this->njobs		= $n->getJobs();
	}

	function drawJobs()
	{
		$dg                     = &$this->data_gather;
		$colorblack		= imageColorAllocate( $this->image, 0, 0, 0 );

		for( $n = 0; $n < count( $this->njobs ); $n++ )
		{
			$jobid			= $this->njobs[$n];
			$jobinfo		= $dg->getJob( $jobid );

			$xoffset		= 5;
			imageString( $this->image, $this->font, $xoffset, $this->y_offset, strval( $jobid ), $colorblack );

			foreach( $this->headerstrlen as $headername => $headerlen )
			{
				if( $headername == 'nodes' )
				{
					$attrval	= strval( count( $jobinfo[nodes] ) );
				}
				else if( $headername == 'cpus' )
				{
					if( !isset( $jobinfo[ppn] ) )
					{
						$jobinfo[ppn] = 1;
					}

					$attrval	= strval( count( $jobinfo[nodes] ) * intval( $jobinfo[ppn] ) );
				}
				else if( $headername == 'runningtime' )
				{
					$attrval	= makeTime( intval( $jobinfo[reported] ) - intval( $jobinfo[start_timestamp] ) );
				}
				else
				{
					$attrval	= strval( $jobinfo[$headername] );
				}

				imageString( $this->image, $this->font, $xoffset, $this->y_offset, $attrval, $colorblack );
		
				$xoffset	= $xoffset + ($this->fontwidth * ( $headerlen + 1 ) );
			}
			
			$this->newLineOffset();
		}
	}

	function drawHeader()
	{
		$dg                     = &$this->data_gather;

		for( $n = 0; $n < count( $this->njobs ); $n++ )
		{
			$jobid			= $this->njobs[$n];
			$jobinfo		= $dg->getJob( $jobid );

			if( !isset( $this->headerstrlen[id] ) )
			{
				$this->headerstrlen[id]	= strlen( strval( $jobid ) );
			}
			else if( strlen( strval( $jobid ) ) > $this->headerstrlen[id] )
			{
				$this->headerstrlen[id]	= strlen( strval( $jobid ) );
			}

			if( !isset( $this->headerstrlen[owner] ) )
			{
				$this->headerstrlen[owner]	= strlen( strval( $jobinfo[owner] ) );
			}
			else if( strlen( strval( $jobinfo[owner] ) ) > $this->headerstrlen[owner] )
			{
				$this->headerstrlen[owner]	= strlen( strval( $jobinfo[owner] ) );
			}

			if( !isset( $this->headerstrlen[queue] ) )
			{
				$this->headerstrlen[queue]	= strlen( strval( $jobinfo[queue] ) );
			}
			else if( strlen( strval( $jobinfo[queue] ) ) > $this->headerstrlen[queue] )
			{
				$this->headerstrlen[queue]	= strlen( strval( $jobinfo[queue] ) );
			}

			if( !isset( $jobinfo[ppn] ) )
			{
				$jobinfo[ppn] = 1;
			}

			$cpus			= count( $jobinfo[nodes] ) * intval( $jobinfo[ppn] );

			if( !isset( $this->headerstrlen[cpus] ) )
			{
				$this->headerstrlen[cpus]	= strlen( strval( $cpus ) );
			}
			else if( strlen( strval( $cpus ) ) > $this->headerstrlen[cpus] )
			{
				$this->headerstrlen[cpus]	= strlen( strval( $cpus ) );
			}

			$nodes			= count( $jobinfo[nodes] );

			if( !isset( $this->headerstrlen[nodes] ) )
			{
				$this->headerstrlen[nodes]	= strlen( strval( $nodes ) );
			}
			else if( strlen( strval( $nodes) ) > $this->headerstrlen[nodes] )
			{
				$this->headerstrlen[nodes]	= strlen( strval( $nodes ) );
			}

			$runningtime		= makeTime( intval( $jobinfo[reported] ) - intval( $jobinfo[start_timestamp] ) );

			if( !isset( $this->headerstrlen[runningtime] ) )
			{
				$this->headerstrlen[runningtime]	= strlen( strval( $runningtime) );
			}
			else if( strlen( strval( $runningtime) ) > $this->headerstrlen[runningtime] )
			{
				$this->headerstrlen[runningtime]	= strlen( strval( $runningtime) );
			}

			if( !isset( $this->headerstrlen[name] ) )
			{
				$this->headerstrlen[name]	= strlen( strval( $jobinfo[name] ) );
			}
			else if( strlen( strval( $jobinfo[name] ) ) > $this->headerstrlen[name] )
			{
				$this->headerstrlen[name]	= strlen( strval( $jobinfo[name] ) );
			}
		}

		$xoffset	= 5;

		foreach( $this->headerstrlen as $headername => $headerlen )
		{
			$colorgreen	= imageColorAllocate( $this->image, 0, 200, 0 );

			if( $headerlen < strlen( $headername ) )
			{
				$this->headerstrlen[$headername]	= strlen( $headername );
			}

			imageString( $this->image, $this->font, $xoffset, $this->y_offset, ucfirst( $headername ), $colorgreen );

			$xoffset	= $xoffset + ($this->fontwidth * ( $this->headerstrlen[$headername] + 1 ) );
		}
		$this->newLineOffset();
	}

	function newLineOffset()
	{
		$this->y_offset		= $this->y_offset + $this->fontheight + $this->fontspaceing;
	}

	function draw()
	{
		$xlen		= 450;
		$ylen		= ( count( $this->njobs ) * ( $this->fontheight + $this->fontspaceing ) ) + (3 * $this->fontheight);

		$this->image	= imageCreateTrueColor( $xlen, $ylen );
		$colorwhite	= imageColorAllocate( $this->image, 255, 255, 255 );
		imageFill( $this->image, 0, 0, $colorwhite );                         

		$colorblue	= imageColorAllocate( $this->image, 0, 0, 255 );

		imageString( $this->image, $this->font, 1, $this->y_offset, "Monarch Joblist - host: ".$this->host, $colorblue );
		$this->newLineOffset();

		$this->drawHeader();
		$this->drawJobs();

		header( 'Content-type: image/png' );
		imagePNG( $this->image );
		imageDestroy( $this->image );
	}
}

function imageStringDown( &$image, $font, $x, $y, &$s, &$col )
{
	$fw	= imagefontwidth( $font);
	$fh	= imagefontheight( $font);
	
	$fontspacing = 0;

	$fx	= $x;
	$fy	= $y;

	for( $n=0; $n<strlen( $s ); $n++ )
	{
		$myc	= $s{$n};

		imagestring( $image, $font, $fx, $fy, $myc, $col );

		$fy	+= ($fontspacing + $fh );
	}
}

function array_rem( $val, &$arr )
{
	// Delete val from arr
	//
	$i	= array_search( $val, $arr );

	if( $i == false ) return false;

	$arr	= array_merge( array_slice( $arr, 0, $i ), array_slice( $arr, $i+1, count( $arr ) ) );

	return true;
}

function cmp( $a, $b ) 
{
	global $SORT_ORDER;
	global $skan_str;
	global $x_first, $y_first;
	global $x_present, $y_present;

	$a_node		= $a;
	$b_node		= $b;
	$a		= $a_node->getHostname();
	$b		= $b_node->getHostname();

	if( $a == $b ) return 0;

	$a_x		= 0;
	$b_x		= 0;
	$a_y		= 0;
	$b_y		= 0;

	if( $x_present && $y_present )
	{
		if( $x_first )
		{
			$n = sscanf( $a, $skan_str, $a_x, $a_y );
			$n = sscanf( $b, $skan_str, $b_x, $b_y );
		}
		else if( $y_first )
		{
			$n = sscanf( $a, $skan_str, $a_y, $a_x );
			$n = sscanf( $b, $skan_str, $b_y, $b_x );
		}
	} 
	else if( $x_present && !$y_present )
	{
		$n = sscanf( $a, $skan_str, $a_x );
		$n = sscanf( $b, $skan_str, $b_x );
	}
	else if( $y_present && !$x_present )
	{
		$n = sscanf( $a, $skan_str, $a_y );
		$n = sscanf( $b, $skan_str, $b_y );
	}

	if ( $SORT_ORDER=="desc" )
	{

		if( $x_present && $y_present )
		{
			// 1  = a < b
			// -1 = a > b
			//
			if ($a_x == $b_x)
			{
				if ($a_y < $b_y)
				{
					return 1;
				}
				else if ($a_y > $b_y)
				{
					return -1;
				}
			}
			else if ($a_x < $b_x)
			{
				return 1;
			}
			else if ($a_x > $b_x)
			{
				return -1;
			}
		} 
		else if( $x_present && !$y_present )
		{
			if ($a_x < $b_x)
			{
				return 1;
			}
			else if ($a_x > $b_x)
			{
				return -1;
			}
		}
		else if( $y_present && !$x_present )
		{
			if ($a_y < $b_y)
			{
				return 1;
			}
			else if ($a_y > $b_y)
			{
				return -1;
			}
		}
	}
	else if ( $SORT_ORDER == "asc" )
	{

		if( $x_present && $y_present )
		{
			// 1  = a > b
			// -1 = a < b
			//
			if ($a_x == $b_x)
			{
				if ($a_y > $b_y)
				{
					return 1;
				}
				else if ($a_y < $b_y)
				{
					return -1;
				}
			}
			else if ($a_x > $b_x)
			{
				return 1;
			}
			else if ($a_x < $b_x)
			{
				return -1;
			}
		}
		else if( $x_present && !$y_present )
		{
			if ($a_x > $b_x)
			{
				return 1;
			}
			else if ($a_x < $b_x)
			{
				return -1;
			}
		}
		else if( $y_present && !$x_present )
		{
			if ($a_y > $b_y)
			{
				return 1;
			}
			else if ($a_y < $b_y)
			{
				return -1;
			}
		}
	}
}

function timeToEpoch( $time )
{
        $time_fields    = explode( ':', $time );

        if( count( $time_fields ) == 3 )
        {
                $hours          = $time_fields[0];
                $minutes        = $time_fields[1];
                $seconds        = $time_fields[2];

        }
        else if( count( $time_fields ) == 2 )
        {
                $hours          = 0;
                $minutes        = $time_fields[0];
                $seconds        = $time_fields[1];

        }
        else if( count( $time_fields ) == 1 )
        {
                $hours          = 0;
                $minutes        = 0;
                $seconds        = $time_fields[0];
        }

        $myepoch        = intval( $seconds + (intval( $minutes * 60 )) + (intval( $hours * 3600 )) );

        return $myepoch;
}

function makeTime( $time )
{
        $days = intval( $time / 86400 );
        $time = ($days>0) ? $time % ($days * 86400) : $time;

        $date_str = '';
        $day_str = '';

        if( $days > 0 )
	{
		if( $days > 1 )
		{
			$date_str .= $days . ' days';
		}
		else
		{
			$date_str .= $days . ' day';
		}
        }

        $hours = intval( $time / 3600 );
        $time = $hours ? $time % ($hours * 3600) : $time;

        if( $hours > 0 )
	{
		if( $hours > 1 )
		{
			$date_unit	= 'hours';
		}
		else
		{
			$date_unit	= 'hour';
		}

		if( $date_str != '' )
		{
			$date_str .= ' ' . $hours . ' ' . $date_unit;
		}
		else
		{
			$date_str .= $hours . ' ' . $date_unit;
		}
        }

        $minutes = intval( $time / 60 );
        $seconds = $minutes ? $time % ($minutes * 60) : $time;

        if( $minutes > 0 )
	{
		if( $minutes > 1 )
		{
			$date_unit	= 'minutes';
		}
		else
		{
			$date_unit	= 'minute';
		}

		if( $date_str != '' )
		{
			$date_str .= ' ' . $minutes . ' ' . $date_unit;
		}
		else
		{
			$date_str .= $minutes . ' ' . $date_unit;
		}
        }

        if( $seconds > 0 )
	{
		if( $seconds > 1 )
		{
			$date_unit	= 'seconds';
		}
		else
		{
			$date_unit	= 'second';
		}

		if( $date_str != '' )
		{
			$date_str .= ' ' . $seconds . ' ' . $date_unit;
		}
		else
		{
			$date_str .= $seconds . ' ' . $date_unit;
		}
        }

        return $date_str;
}
?>
