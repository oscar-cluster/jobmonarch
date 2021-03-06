<?php

ini_set("memory_limit","1G");
set_time_limit(0);

//ini_set("xdebug.profiler_output_dir","/tmp");
//ini_set("xdebug.profiler_output_name","timestamp");
//ini_set("xdebug.profiler_enable","1");

$c			= $_POST['c'];
$clustername		= $c;
$cluster		= $c;

// Supplied by ExtJS when DataStore has remoteSort: true
//
$sortfield		= isset($_POST['sort'] ) ? $_POST['sort'] : "jid";
$sortorder		= isset($_POST['dir'] ) ? $_POST['dir'] : "ASC"; // ASC or DESC

// Search query from ext.SearchField
//
$query			= isset($_POST['query']) ? $_POST['query'] : null;

// Filter values
//
$jid			= isset($_POST['jid']) ? $_POST['jid'] : null;
$jids			= isset($_POST['jids']) ? $_POST['jids'] : null;
$owner			= isset($_POST['owner']) ? $_POST['owner'] : null;
$status			= isset($_POST['status']) ? $_POST['status'] : null;
$queue			= isset($_POST['queue']) ? $_POST['queue'] : null;
$host			= isset($_POST['host']) ? $_POST['host'] : null;
$p_metricname		= isset($_POST['metricname']) ? $_POST['metricname'] : 'load_one';

//print_r( $_POST );

if( $jids != null )
{
	$jobids	= explode( ",", $jids );
}
else
{
	$jobids	= null;
}

global $c, $clustername, $cluster, $metrics;

// Grid Paging stuff
//
//$pstart	= (int) (isset($_POST['start']) ? $_POST['start'] : $_GET['pstart']);
$pstart	= (int) $_POST['start'];
//$pend	= (int) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['plimit']);
$pend	= (int) $_POST['limit'];

//echo $pend.'p ';
// Need to fool Ganglia here: or it won't parse XML for our cluster
//
$HTTP_POST_VARS['c']	= $c;
$_GET['c']		= $c;

global $c, $clustername, $cluster, $mySession;

include_once "./libtoga.php";


global $jobs, $metrics, $session;

//printf( "c %s\n", $clustername );

$mySession	= new SessionHandler( $clustername );
$mySession->checkSession();

$session	= &$mySession->getSession();
$myXML		= $session['data'];

//printf( "gt %s\n", $session['gather_time'] );
//printf( "pi %s\n", $session['poll_interval'] );

$myData		= new DataGatherer( $clustername );
$myData->parseXML( $myXML );

$mySession->updatePollInterval( $myData->getPollInterval() );
//printf( "pi %s\n", $myData->getPollInterval() );
//printf( "pi %s\n", $session['poll_interval'] );
$mySession->endSession();


$heartbeat      = &$myData->getHeartbeat();
$jobs           = &$myData->getJobs();
$cpus           = &$myData->getCpus();
$use_fqdn       = &$myData->getUsingFQDN();

//print_r( $jobs );

//print_r( $session );

// The ext grid script will send  a task field which will specify what it wants to do
//$task = '';

if( isset($_POST['task']) )
{
	$task = $_POST['task'];
}
if( isset( $HTTP_POST_VARS['task' ] ) )
{
	$task = $HTTP_POST_VARS['task'];
}

switch($task)
{
    case "GETJOBS":
        getJobs();
        break;		
    case "GETNODES":
        getNodes();
        break;		
    case "GETMETRICS":
        getMetrics();
        break;		
    default:
        echo "{failure:true}";
        break;
}

function printCacheHeaders()
{
	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
}

function getMetrics( $host=null )
{
	global $metrics, $reports;

	reset($metrics);

        $context_metrics        = array();

	if( !$host)
	{
		$firsthost = key($metrics);
	}
	else
	{
		$firsthost = $host;
	}

	$first_metrics = $metrics[$firsthost];

	foreach( $first_metrics as $metricname => $metricval )
        {
                $context_metrics[] = $metricname;
        }

        foreach ($reports as $mr => $mfoo)
	{
                $context_metrics[] = $mr;
	}

	sort( $context_metrics );

	$metric_list	= array();
	$metric_count	= 0;

	foreach( $context_metrics as $metricname )
	{
		$metric		= array();
		$metric['id']	= $metricname;
		$metric['name']	= $metricname;

		$metric_list[]	= $metric;
		$metric_count	= $metric_count + 1;
	}
	
	$results		= array();
	$results['names']	= $metric_list;
	$results['total']	= $metric_count;

	printCacheHeaders();

	$jsonresults    = JEncode( $results );

	echo $jsonresults;

	return 0;
}

function quickSearchJobs( $jobs, $query )
{
	$searchresults	= array();

	foreach( $jobs as $jobid => $jobattrs )
	{
		if( $query != null )
		{
			if( strpos( $jobattrs['jid'], $query ) !== false )
			{
				$searchresults[$jobid]	= $jobattrs;
			}
			if( strpos( $jobattrs['owner'], $query ) !== false )
			{
				$searchresults[$jobid]	= $jobattrs;
			}
			if( strpos( $jobattrs['queue'], $query ) !== false )
			{
				$searchresults[$jobid]	= $jobattrs;
			}
			if( strpos( $jobattrs['name'], $query ) !== false )
			{
				$searchresults[$jobid]	= $jobattrs;
			}
			if( is_array( $jobattrs['nodes'] ) )
			{
				foreach( $jobattrs['nodes'] as $jattr )
				{
					if( strpos( $jattr, $query ) !== false )
					{
						$searchresults[$jobid]  = $jobattrs;
					}
				}
			}
			if( strpos( $jobid, $query ) !== false )
			{
				$searchresults[$jobid]  = $jobattrs;
			}
		}
	}

	return $searchresults;
}

function sortJobs( $jobs, $sortby, $sortorder )
{
        $sorted = array();

        $cmp    = create_function( '$a, $b',
                "global \$sortby, \$sortorder;".

                "if( \$a == \$b ) return 0;".

                "if (\$sortorder==\"DESC\")".
                        "return ( \$a < \$b ) ? 1 : -1;".
                "else if (\$sortorder==\"ASC\")".
                        "return ( \$a > \$b ) ? 1 : -1;" );

        if( isset( $jobs ) && count( $jobs ) > 0 )
        {
                foreach( $jobs as $jobid => $jobattrs )
                {
                                $state          = $jobattrs['status'];
                                $user           = $jobattrs['owner'];
                                $queue          = $jobattrs['queue'];
                                $name           = $jobattrs['name'];
                                $req_cpu        = $jobattrs['requested_time'];
                                $req_memory     = $jobattrs['requested_memory'];

                                $nodes		= $jobattrs['nodes'];

                                $ppn            = (int) $jobattrs['ppn'] ? $jobattrs['ppn'] : 1;

				if( $state == 'R' )
				{
					$cpus           = count( $nodes ) * $ppn;
				}
				else
				{
					$cpus		= ((int) $nodes ) * $ppn;
				}
                                $queued_time    = (int) $jobattrs['queued_timestamp'];
                                $start_time     = (int) $jobattrs['start_timestamp'];
                                $runningtime    = $report_time - $start_time;

                                switch( $sortby )
                                {
                                        case "jid":
                                                $sorted[$jobid] = $jobid;
                                                break;

                                        case "status":
                                                $sorted[$jobid] = $state;
                                                break;

                                        case "owner":
                                                $sorted[$jobid] = $user;
                                                break;

                                        case "queue":
                                                $sorted[$jobid] = $queue;
                                                break;

                                        case "name":
                                                $sorted[$jobid] = $name;
                                                break;

                                        case "requested_time":
                                                $sorted[$jobid] = timeToEpoch( $req_cpu );
                                                break;

                                        case "requested_memory":
                                                $sorted[$jobid] = $req_memory;
                                                break;

                                        case "ppn":
                                                $sorted[$jobid] = $ppn;
                                                break;
                                        case "nodect":
						if( $state == 'Q' )
						{
							$sorted[$jobid] = $nodes;
						}
						else
						{
							$sorted[$jobid] = count( $nodes );
						}
                                                break;
                                        case "cpus":
                                                $sorted[$jobid] = $cpus;
                                                break;

                                        case "queued_timestamp":
                                                $sorted[$jobid] = $queued_time;
                                                break;

                                        case "start_timestamp":
                                                $sorted[$jobid] = $start_time;
                                                break;

                                        case "runningtime":
                                                $sorted[$jobid] = $runningtime;
                                                break;
					case "nodes":
						if( $state == 'R' )
						{
							$sorted[$jobid]	= $nodes[0];
						}
						else
						{
							$sorted[$jobid] = $nodes;
						}

                                        default:
                                                break;
                                }
                }
        }

        if( $sortorder == "ASC" )
        {
                asort( $sorted );
        }
        else if( $sortorder == "DESC" )
        {
                arsort( $sorted );
        }

        return $sorted;
}

function filterJobs( $jobs )
{
	global $jid, $owner, $queue,  $status, $host, $use_fqdn;

	$filtered_jobs	= array();

        if( isset( $jobs ) && count( $jobs ) > 0 )
        {
                foreach( $jobs as $jobid => $jobattrs )
                {
                                $state          = $jobattrs['status'];
                                $user           = $jobattrs['owner'];
                                $jqueue          = $jobattrs['queue'];
                                $name           = $jobattrs['name'];
                                $req_cpu        = $jobattrs['requested_time'];
                                $req_memory     = $jobattrs['requested_memory'];

                                if( $state == 'R' )
                                {
                                        $nodes = count( $jobattrs['nodes'] );

					$mynodehosts = array();
				        foreach( $jobattrs['nodes'] as $mynode )
					{
						//if( $use_fqdn == 1)
						//{
						//	$mynode = $mynode.".".$jobattrs['domain'];
						//}
						$mynodehosts[]  = $mynode;
					}
					$jobattrs['nodes'] = $mynodehosts;
                                }
                                else
                                {
                                        $nodes = $jobattrs['nodes'];
                                }

                                $ppn            = (int) $jobattrs['ppn'] ? $jobattrs['ppn'] : 1;
                                $cpus           = $nodes * $ppn;
                                $queued_time    = (int) $jobattrs['queued_timestamp'];
                                $start_time     = (int) $jobattrs['start_timestamp'];
                                $runningtime    = $report_time - $start_time;

				$domain		= $jobattrs['domain'];
				$domain_len 	= 0 - strlen( $domain );

				$keepjob	= true;

				if( $jid )
				{
					if( $jobid != $jid )
					{
						$keepjob	= false;
					}
				}
				else if( $host )
				{
					if( $state == 'R' )
					{
						$jnodes		= $jobattrs['nodes'];

						$keepjob	= false;

						foreach( $jnodes as $jnode)
						{
							if( $jnode == $host )
							{
								$keepjob = true;
							}
						}
					}
					else
					{
						$keepjob	= false;
					}
				}
				if( $owner )
				{
					if( $user != $owner )
					{
						$keepjob	= false;
					}
				}
				if( $queue )
				{
					if( $jqueue != $queue )
					{
						$keepjob	= false;
					}
				}
				if( $status )
				{
					if( $state != $status )
					{
						$keepjob	= false;
					}
				}
				if( $keepjob )
				{
					$filtered_jobs[$jobid]	= $jobattrs;
				}
		}
	}

	return $filtered_jobs;
}

function getNodes()
{
	global $jobs, $jobids, $clustername, $metrics, $jid, $p_metricname;
	global $always_timestamp, $always_constant, $mySession;

	$display_nodes	= array();

	$metricname	= $p_metricname;

	printCacheHeaders();

	if( !$jobids && !$jid )
	{
		// RB: todo replace with 0 result rows
		//
		printf("no jobid(s)\n");
		return 1;
	}

	foreach( $jobs[$jid]['nodes'] as $jobnode )
	{
		if( !in_array( $jobnode, $display_nodes) )
		{
			$display_nodes[]	= $jobnode;
		}
	}

	$node_results	= array();
	$result_count	= count( $display_nodes );

	foreach( $display_nodes as $host )
	{
		$nr		= array();

		$cpus		= $metrics[$host]['cpu_num']['VAL'];

		//print_r( $jobs[$jid] );

		if ( !$cpus )
		{
			$cpus		= 1;
		}

		$load_one	= $metrics[$host]['load_one']['VAL'];
		$load		= ((float) $load_one) / $cpus;
		$load_color	= load_color($load);

		$reported	= (int) $jobs[$jid]['reported'];

		$poll_interval	= (int) $jobs[$jid]['poll_interval'];

		//$mySession->updatePollInterval( $poll_interval );

		$time		= time();

		// RB: something broken here with JR / JS
		//
		$job_runtime	= $time - intval( $jobs[$jid]['start_timestamp'] );
		//$job_runtime	= date( 'u' ) - intval( $jobs[$jid]['start_timestamp'] );
		$job_window	= intval( $job_runtime ) * 1.2;

		$jobrange	= -$job_window;
		$jobstart	= (int) $jobs[$jid]['start_timestamp'];
		$period_start	= (int) ($time - (($time - $jobstart) * 1.1 ));

		$nr['jid']	= $jid;
		$nr['nodename']	= $host;

		$hostar		= array( $host );

		list($min,$max)	= find_limits( $hostar, $metricname );

		$host_url	= rawurlencode( $host );
		$cluster_url	= rawurlencode( $clustername );

		$textval	= "";

		$val		= $metrics[$host][$metricname];

		// RB: haven't used this yet: link to Ganglia's host overview
		// maybe later to popup?
		//
		$host_link      = "../../?c=$cluster_url&h=$host_url&r=job&jr=$jobrange&job_start=$jobstart";

		$nr['hostlink']	= $host_link;

		if ( $val["TYPE"] == "timestamp" || $always_timestamp[$metricname] )
		{
			$textval	= date( "r", $val["VAL"] );
		}
		elseif ( $val["TYPE"] == "string" || $val["SLOPE"] == "zero" || $always_constant[$metricname] )
		{
			$textval	= $val["VAL"] . " " . $val["UNITS"];
		}
		else
		{
			$end		= time();
			$graphargs	= ($reports[$metricname]) ? "g=$metricname&" : "m=$metricname&";
			$graphargs	.= "c=$cluster_url&h=$host_url&l=$load_color&v=".$val['VAL']."&r=job&period_start=$period_start&period_stop=$end&job_start=$jobstart";

			if( $max > 0 )
			{
				$graphargs	.= "&x=$max&n=$min";
			}
		}

		$nr['ga']	= $graphargs;

		$node_results[]	= $nr;
	}


	$jsonresults	= JEncode( $node_results );

	echo '{"total":"'. $result_count .'","results":'. $jsonresults .'}';
}

function getJobs() 
{
	global $jobs, $hearbeat, $pstart, $pend;
	global $sortfield, $sortorder, $query, $host;
	global $jid, $owner, $queue,  $status, $mySession;

	$job_count		= count( $jobs );

	printCacheHeaders();

	if( $job_count == 0 )
	{
		echo '({"total":"0", "results":""})';
		return 0;
	}

	$jobresults		= array();

	$cur_job		= 0;

	$sorted_jobs            = sortJobs( $jobs, $sortfield, $sortorder );

	if( $query )
	{
		$jobs			= quickSearchJobs( $jobs, $query );
	}
	if( $jid || $owner || $queue || $status || $host )
	{
		$jobs			= filterJobs( $jobs );
	}
	$result_count		= count( $jobs );

	foreach( $sorted_jobs as $jobid => $jobattrs )
	{
		//if( $jobattrs['reported'] != $heartbeat )
		//{
		//	continue;
		//}

		if( ! array_key_exists( $jobid, $jobs ) )
		{
			continue;
		}

		$jr			= array();
		$jr['jid']		= strval( $jobid );
		$jr['status']		= $jobs[$jobid]['status'];
		$jr['owner']		= $jobs[$jobid]['owner'];
		$jr['queue']		= $jobs[$jobid]['queue'];
		$jr['name']		= $jobs[$jobid]['name'];
		$jr['requested_time']	= makeTime( timeToEpoch( $jobs[$jobid]['requested_time'] ) );

		$poll_interval		= (int) $jobs[$jobid]['poll_interval'];

		//$mySession->updatePollInterval( $poll_interval );

		if( $jr['status'] == 'R' )
		{
			$nodes 		= count( $jobs[$jobid]['nodes'] );
		}
		else
		{
			$nodes 		= (int) $jobs[$jobid]['nodes'];
		}

		$jr['ppn']		= strval( $jobs[$jobid]['ppn'] ? $jobs[$jobid]['ppn'] : 1 );
		$jr['nodect']		= strval( $nodes );

		if( $jr['status'] == 'R' )
		{
			$jr['nodes']	= implode( ",", $jobs[$jobid]['nodes'] );
		}
		else
		{
			$jr['nodes']	= "";
		}

		$jr['queued_timestamp']	= makeDate( $jobs[$jobid]['queued_timestamp'] );
		$jr['start_timestamp']	= ($jobs[$jobid]['start_timestamp'] ? makeDate( $jobs[$jobid]['start_timestamp'] ) : "");

		if( $jr['status'] == 'R' )
		{
			$runningtime		= (int) $jobs[$jobid]['reported'] - (int) $jobs[$jobid]['start_timestamp'];
			$jr['runningtime']	= makeTime( $runningtime );
		}
		else
		{
			$jr['runningtime']	= "";
		}

		if( ( $cur_job < $pstart ) || ( ($cur_job - $pstart) >= $pend ) )
		{
			$cur_job	= $cur_job + 1;
			continue;
		}
		else
		{
			$cur_job	= $cur_job + 1;
		}

		$jobresults[]		= $jr;
	}

	$jsonresults	= JEncode( $jobresults );


	echo '{"total":"'. $result_count .'","results":'. $jsonresults .'}';

	return 0;
}

// Encodes a SQL array into a JSON formated string: so that Javascript may understand it
//
function JEncode( $arr )
{
	if( version_compare( PHP_VERSION, "5.2", "<" ) )
	{    
		require_once( "./JSON.php" );		//if php<5.2 need JSON class

		$json	= new Services_JSON();		//instantiate new json object
		$data	= $json->encode( $arr );	//encode the data in json format
	} 
	else
	{
		$data	= json_encode( $arr );		//encode the data in json format
	}

	return $data;
}

?> 
