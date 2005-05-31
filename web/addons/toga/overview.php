<?php

$my_dir = getcwd();

include_once "./libtoga.php";

global $GANGLIA_PATH;
chdir( $GANGLIA_PATH );
include_once "./class.TemplatePower.inc.php";
chdir( $my_dir );

$httpvars = new HTTPVariables( $HTTP_GET_VARS );
$clustername = $httpvars->getClusterName();
printf( "clustername = %s\n", $clustername );
$queue_select = $httpvars->getHttpVar( "queue" );
printf( "queue = %s\n", $queue );

$data_gatherer = new DataGatherer();

$tpl = new TemplatePower("templates/overview.tpl");
$tpl->prepare();

$tpl->assign( "self", "./index.php" );
$tpl->assign( "clustername", $clustername );

$tpl->assign( "clusterimage", "./image.php?c=".rawurlencode($clustername)."&view=big-clusterimage" );

$data_gatherer->parseXML();
$heartbeat = $data_gatherer->getHeartbeat();
$jobs = $data_gatherer->getJobs();
$nodes = $data_gatherer->getNodes();

$tpl->assign("heartbeat", makeDate( $heartbeat ) );

$pie = drawPie();
$tpl->assign("pie", $pie );

function makeTime( $time ) {

	$days = intval( $time / 86400 );
	$time = $days ? $time % ($days * 86400) : $time;

	if( $days > 0 ) {
		if( $days > 1 )
			$date_str .= $days . ' days - ';
		else
			$date_str .= $days . ' day - ';
	}

	$hours = intval( $time / 3600 );
	$time = $hours ? $time % ($hours * 3600) : $time;

	if( $hours > 0 ) {
		$date_str .= $hours . ':';
		$date_unit = ' hours';
	}
		
	$minutes = intval( $time / 60 );
	$seconds = $minutes ? $time % ($minutes * 60) : $time;

	if( $minutes > 0 ) {

		if( $minutes >= 10 )
			$date_str .= $minutes . ':';
		else
			$date_str .= '0' . $minutes . ':';

		$date_unit = (!isset($date_unit)) ? 'minutes' : $date_unit;
	} else if( $days > 0 or $hours > 0 ) {
		$date_str .= '00:';
		$date_unit = (!isset($date_unit)) ? 'minutes' : $date_unit;
	}

	$date_unit = (!isset($date_unit)) ? 'seconds' : $date_unit;

	if( $seconds > 0 ) {

		if( $seconds >= 10 )
			$date_str .= $seconds . ' ' . $date_unit;
		else
			$date_str .= '0' . $seconds . ' ' . $date_unit;
			
	} else if ( $days > 0 or $hours > 0 or $minutes > 0 )
		$date_str .= '00 ' . $date_unit;

	return $date_str;
}

function makeDate( $time ) {
	return strftime( "%a %d %b %Y %H:%M:%S", $time );
}

function colorRed( $color ) {
	return substr( $color, 0, 2 );
}
function colorGreen( $color ) {
	return substr( $color, 2, 2 );
}
function colorBlue( $color ) {
	return substr( $color, 4, 2 );
}

function colorDiffer( $first, $second ) {

	// Make sure these two colors differ atleast 50 R/G/B
	$min_diff = 50;

	$c1r = decHex( colorRed( $first ) );
	$c1g = decHex( colorGreen( $first ) );
	$c1b = decHex( colorBlue( $first ) );

	$c2r = decHex( colorRed( $second ) );
	$c2g = decHex( colorGreen( $second ) );
	$c2b = decHex( colorBlue( $second ) );

	$rdiff = ($c1r >= $c2r) ? $c1r - $c2r : $c2r - $c1r;
	$gdiff = ($c1g >= $c2g) ? $c1g - $c2g : $c2g - $c1g;
	$bdiff = ($c1b >= $c2b) ? $c1b - $c2b : $c2b - $c1b;

	if( $rdiff >= $min_diff or $gdiff >= $min_diff or $bdiff >= $min_diff )
		return TRUE;
	else
		return FALSE;
}

function randomColor( $known_colors ) {

	$start = hexdec( "004E00" );
	$end = hexdec( "FFFFFF" );

	if( count( $known_colors ) == 0 )
		return dechex(rand( $start, $end ));

	$color_changed = TRUE;

	while( $color_changed ) {

		$color_changed = FALSE;

		foreach( $known_colors as $old ) {

			if( !isset( $new ) )
				$new = rand( $start, $end );

			if( !colorDiffer( dechex( $new ), $old ) )

				while( !colorDiffer( $new, $old ) ) {

					$new = rand( $start, $end );
					$color_changed = TRUE;
				}
		}
	}

	// Whoa! Actually found a good color ;)
	return dechex( $new );
}

function drawPie() {

	global $jobs, $nodes;

	$pie_args = "title=" . rawurlencode("Cluster Jobload");
	$pie_args .= "&size=250x150";

	$queues = array();
	$nr_jobs = count( $jobs );
	$nr_nodes = count( $nodes );

	$emptynodes = 0;

	foreach( $nodes as $node ) {

		$myjobs = $node->getJobs();

		if( count( $myjobs ) == 0 )
			$emptynodes++;
		else
			$nodes_jobs = $nodes_jobs + count( $myjobs );
	}

	$empty_percentage = ($emptynodes / $nr_nodes) * 100;
	$job_percentage = 100 - $empty_percentage; 

	$color = randomColor( $qcolors );
	$qcolors[] = $color;
	$pie_args .= "&free=$empty_percentage,$color";

	foreach( $jobs as $jobid => $jobattrs ) {

		$qname = $jobattrs[queue];

		if( !array_search( $qname, $queues ) ) {

			if( !isset( $queues[$qname] ) )
				$queues[$qname] = array();

			$queues[$qname][] = $jobid;
		}
	}

	$qcolors = array();
	foreach( $queues as $queue => $myjobs ) {

		$qjobs = count ( $myjobs );
		$percentage = ( $qjobs / $nr_jobs ) * $job_percentage;
		$color = randomColor( $qcolors );
		$qcolors[] = $color;
		$pie_args .= "&$queue=$percentage,$color";
	}
	$pie = "../../pie.php?$pie_args";

	return $pie;
}

foreach( $jobs as $jobid => $jobattrs ) {

	$report_time = $jobattrs[reported];

	if( $report_time == $heartbeat ) {

		$tpl->newBlock("node");
		$tpl->assign( "clustername", $clustername );
		$tpl->assign("id", $jobid );
		$tpl->assign("state", $jobattrs[status] );
		$tpl->assign("user", $jobattrs[owner] );
		$tpl->assign("queue", $jobattrs[queue] );
		$tpl->assign("name", $jobattrs[name] );
		$tpl->assign("req_cpu", $jobattrs[requested_time] );
		$tpl->assign("req_memory", $jobattrs[requested_memory] );
		$nodes = count( $jobattrs[nodes] );
		$ppn = (int) $jobattrs[ppn] ? $jobattrs[ppn] : 1;
		$cpus = $nodes * $ppn;
		$tpl->assign("nodes", $nodes );
		$tpl->assign("cpus", $cpus );
		$start_time = (int) $jobattrs[start_timestamp];

		if( $start_time ) {

			$runningtime = makeTime( $report_time - $start_time );
			$tpl->assign("started", makeDate( $start_time ) );
			$tpl->assign("runningtime", $runningtime );
		}
	}
}

$tpl->printToScreen();

?>
