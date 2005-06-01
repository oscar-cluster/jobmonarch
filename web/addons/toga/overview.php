<?php
global $GANGLIA_PATH, $clustername, $tpl, $filter, $cluster, $get_metric_string, $cluster_url;

$data_gatherer = new DataGatherer();

//$tpl->assign( "self", "./index.php" );
$tpl->assign( "clustername", $clustername );

$data_gatherer->parseXML();

$heartbeat = $data_gatherer->getHeartbeat();
$jobs = $data_gatherer->getJobs();
$nodes = $data_gatherer->getNodes();

$filter_image_url = "";

foreach( $filter as $filtername => $filtervalue ) {
	$tpl->assign( "f_".$filtername, $filtervalue );
	$filter_image_url .= "&$filtername=$filtervalue";
}

$tpl->assign( "clusterimage", "./image.php?c=".rawurlencode($clustername)."&view=big-clusterimage".$filter_image_url );

$tpl->assign("heartbeat", makeDate( $heartbeat ) );

$pie = drawPie();
$tpl->assign("pie", $pie );

//if( !array_key_exists( 'id', $filter ) ) {

//	$graph_args = "c=$cluster_url&$get_metric_string&st=$cluster[LOCALTIME]";
//	$tpl->newBlock( "average_graphs" );
//	$tpl->assign( "graph_args", $graph_args );
//}

function timeToEpoch( $time ) {

	$time_fields = explode( ':', $time );

	if( count($time_fields) == 3 ) {

		$hours = $time_fields[0];
		$minutes = $time_fields[1];
		$seconds = $time_fields[2];

		$myepoch = intval( $seconds + (intval( $minutes * 60 )) + (intval( $hours * 3600 )) );
		return $myepoch;
	}
}

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


function sortJobs( $jobs, $sortby, $sortorder ) {

	$sorted = array();

	$cmp = create_function( '$a, $b', 
		"global \$sortby, \$sortorder;".

		"if( \$a == \$b ) return 0;".

		"if (\$sortorder==\"desc\")".
			"return ( \$a < \$b ) ? 1 : -1;".
		"else if (\$sortorder==\"asc\")".
			"return ( \$a > \$b ) ? 1 : -1;" );

        foreach( $jobs as $jobid => $jobattrs ) {

                        $state = $jobattrs[status];
                        $user = $jobattrs[owner];
                        $queue = $jobattrs[queue];
                        $name = $jobattrs[name];
                        $req_cpu = $jobattrs[requested_time];
                        $req_memory = $jobattrs[requested_memory];
                        $nodes = count( $jobattrs[nodes] );
                        $ppn = (int) $jobattrs[ppn] ? $jobattrs[ppn] : 1;
                        $cpus = $nodes * $ppn;
                        $start_time = (int) $jobattrs[start_timestamp];
			$runningtime = $report_time - $start_time;

			switch( $sortby ) {
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
					$sorted[$jobid] = $req_cpu;
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

	uasort( $sorted, $cmp );

	return $sorted;
}

function makeOverview() {

	global $jobs, $nodes, $heartbeat, $clustername, $tpl;
	global $sortorder, $sortby, $filter;

	$tpl->assign("sortorder", $sortorder );
	$tpl->assign("sortby", $sortby );

	$sorted_jobs = sortJobs( $jobs, $sortby, $sortorder );

	$even = 1;

	foreach( $sorted_jobs as $jobid => $sortdec ) {

		$report_time = $jobs[$jobid][reported];

		if( $report_time == $heartbeat ) {

			if( count( $filter ) == 0 )
				$display_job = 1;
			else
				$display_job = 0;

			foreach( $filter as $filtername=>$filtervalue ) {

				if( $filtername == 'id' && $jobid == $filtervalue )
					$display_job = 1;
				else if( $filtername == 'state' && $jobs[$jobid][status] == $filtervalue )
					$display_job = 1;
				else if( $filtername == 'queue' && $jobs[$jobid][queue] == $filtervalue )
					$display_job = 1;
				else if( $filtername == 'user' && $jobs[$jobid][owner] == $filtervalue )
					$display_job = 1;
			}

			if( $display_job ) {

				$tpl->newBlock("node");
				$tpl->assign( "clustername", $clustername );
				$tpl->assign("id", $jobid );
				$tpl->assign("state", $jobs[$jobid][status] );
				$tpl->assign("user", $jobs[$jobid][owner] );
				$tpl->assign("queue", $jobs[$jobid][queue] );
				$tpl->assign("name", $jobs[$jobid][name] );
				$tpl->assign("req_cpu", makeTime( timeToEpoch( $jobs[$jobid][requested_time] ) ) );
				$tpl->assign("req_memory", $jobs[$jobid][requested_memory] );
				$nodes = count( $jobs[$jobid][nodes] );
				$ppn = (int) $jobs[$jobid][ppn] ? $jobs[$jobid][ppn] : 1;
				$cpus = $nodes * $ppn;
				$tpl->assign("nodes", $nodes );
				$tpl->assign("cpus", $cpus );
				$start_time = (int) $jobs[$jobid][start_timestamp];

				if( $even ) {

					$tpl->assign("nodeclass", "even");
					$even = 0;
				} else {

					$tpl->assign("nodeclass", "odd");
					$even = 1;
				}

				if( $start_time ) {

					$runningtime = makeTime( $report_time - $start_time );
					$tpl->assign("started", makeDate( $start_time ) );
					$tpl->assign("runningtime", $runningtime );
				}
			}
		}
	}
}
?>
