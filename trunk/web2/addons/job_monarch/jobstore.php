<?php

ini_set("memory_limit","100M");
set_time_limit(0);

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

global $c, $clustername, $cluster;

include_once "./libtoga.php";

$ds             = new DataSource();
$myxml_data     = &$ds->getData();

session_start();
unset( $_SESSION['data'] );
$_SESSION['data']       = &$myxml_data;

global $jobs, $metrics;

$data_gatherer  = new DataGatherer( $clustername );
$data_gatherer->parseXML( &$myxml_data );

$heartbeat      = &$data_gatherer->getHeartbeat();
$jobs           = &$data_gatherer->getJobs();
//$gnodes         = $data_gatherer->getNodes();
$cpus           = &$data_gatherer->getCpus();
$use_fqdn       = &$data_gatherer->getUsingFQDN();

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

function getMetrics( $host=null )
{
	global $metrics;

	reset($metrics);
	if( !$host)
	{
          $firsthost = key($metrics);
	}
	else
	{
          $firsthost = $host;
	}

	$first_metrics = $metrics[$firsthost];

	$metric_list	= array();

	$metric_count	= 0;

	foreach( $first_metrics as $metricname => $metricval )
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
                                        case "nodesct":
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

				if( $host )
				{
					if( $state == 'R' )
					{
						$jnodes = $jobattrs['nodes'];

						$keepjob = false;

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
						$keepjob = false;
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
	global $jobs, $jobids, $clustername, $metrics;

	$display_nodes	= array();

	if( !$jobids )
	{
		return 1;
	}
	foreach( $jobs as $jobid => $jobattrs )
	{
		if( in_array( $jobid, $jobids ) )
		{
			foreach( $jobattrs['nodes'] as $jobnode )
			{
				if( !in_array( $jobnode, $display_nodes) )
				{
					$display_nodes[$jobid]	= $jobnode;
				}
			}
		}
	}

	$node_results	= array();
	$result_count	= count( $display_nodes );
	foreach( $display_nodes as $jobid => $host )
	{
		$nr		= array();
		$nr['c']	= $clustername;
		$nr['h']	= $host ;
		$nr['x']	= '5';
		$nr['v']	= '0';

		$cpus		= $metrics[$host]['cpu_num']['VAL'];

		if ( !$cpus )
		{
			$cpus		= 1;
		}

		$load_one	= $metrics[$host]['load_one']['VAL'];
		$load		= ((float) $load_one) / $cpus;
		$load_color	= load_color($load);

		$nr['l']	= $load_color;

		$job_runtime	= (int) $jobs[$jobid]['reported'] - (int) $jobs[$jobid]['start_timestamp'];
		$job_window	= intval($job_runtime * 1.2);

		$nr['jr']	= -$job_window;
		$nr['js']	= (int) $jobs[$jobid]['start_timestamp'];

		$node_results[]	= $nr;
	}
	$jsonresults	= JEncode( $node_results );

	echo '{"total":"'. $result_count .'","results":'. $jsonresults .'}';
}

function getJobs() 
{
	global $jobs, $hearbeat, $pstart, $pend;
	global $sortfield, $sortorder, $query, $host;
	global $jid, $owner, $queue,  $status;

	$job_count		= count( $jobs );

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
