<?php

$c			= $_POST["c"];
$clustername		= $c;
$cluster		= $c;

global $c, $clustername, $cluster;

$HTTP_POST_VARS["c"]	= $c;
$_GET["c"]		= $c;

global $c, $clustername, $cluster;

include_once "./libtoga.php";

$ds             = new DataSource();
$myxml_data     = &$ds->getData();

//printf( "d %s\n", strlen( $myxml_data ) );
//return 0;

global $jobs;

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

//getList();

switch($task)
{
    case "LISTING":
        getList();
        break;		
    default:
        echo "{failure:true}";
        break;
}

function getList() 
{
	global $jobs, $hearbeat;

	$job_count	= count( $jobs );

	if( $job_count == 0 )
	{
		echo 'crap({"total":"0", "results":""})';
		return 0;
	}

	$jobresults	= array();

	foreach( $jobs as $jobid => $jobattrs )
	{
		//if( $jobattrs['reported'] != $heartbeat )
		//{
		//	continue;
		//}

		$jr['jid']		= strval( $jobid );
		$jr['status']		= $jobattrs['status'];
		$jr['owner']		= $jobattrs['owner'];
		$jr['queue']		= $jobattrs['queue'];
		$jr['name']		= $jobattrs['name'];
		$jr['requested_time']	= makeTime( timeToEpoch( $jobattrs['requested_time'] ) );

		if( $jr['status'] == 'R' )
		{
			$nodes 		= count( $jobattrs[nodes] );
		}
		else
		{
			$nodes 		= (int) $jobattrs[nodes];
		}

		$jr['ppn']		= strval( $jobattrs[ppn] ? $jobattrs[ppn] : 1 );
		$jr['nodect']		= strval( $nodes );

		if( $jr['status'] == 'R' )
		{
			$jr['nodes']	= implode( ",", $jobattrs['nodes'] );
		}
		else
		{
			$jr['nodes']	= "";
		}

		$jr['queued_timestamp']	= makeDate( $jobattrs['queued_timestamp'] );
		$jr['start_timestamp']	= ($jobattrs['start_timestamp'] ? makeDate( $jobattrs['start_timestamp'] ) : "");

		if( $jr['status'] == 'R' )
		{
			$runningtime		= (int) $jobattrs['reported'] - (int) $jobattrs['start_timestamp'];
			$jr['runningtime']	= makeTime( $runningtime );
		}
		else
		{
			$jr['runningtime']	= "";
		}

		$jobresults[]		= $jr;
	}


	//$results	= array();

	//foreach( $jobresults as $resid => $jr )
	//{
	//	$jr_count	= 0;
	//	$job_record	= array();

	//	foreach( $jr as $atrname => $atrval )
	//	{
	//		$job_record[$jr_count]	= $atrval;
	//		$job_record[$atrname]	= $atrval;

	//		$jr_count		= $jr_count + 1;
	//	}

	//	$results[]	= $job_record;
	//}

	$jsonresults	= JEncode( $jobresults );

	echo '{"total":"'. count( $jobresults) .'","results":'. $jsonresults .'}';

	return 0;
}

// Encodes a SQL array into a JSON formated string
function JEncode( $arr )
{
	if (version_compare(PHP_VERSION,"5.2","<"))
	{    
		require_once("./JSON.php"); //if php<5.2 need JSON class

		$json	= new Services_JSON();//instantiate new json object
		$data	= $json->encode($arr);  //encode the data in json format
	} 
	else
	{
		$data	= json_encode($arr);  //encode the data in json format
	}

	return $data;
}

?> 
