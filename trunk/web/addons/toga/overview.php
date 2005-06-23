<?php
global $GANGLIA_PATH, $clustername, $tpl, $filter, $cluster, $get_metric_string, $cluster_url, $sh;
global $hosts_up, $m, $start, $end, $filterorder;

//$tpl->assign("_ROOT.summary", "" );

$data_gatherer = new DataGatherer( $clustername );

//$tpl->assign( "self", "./index.php" );
$tpl->assign( "clustername", $clustername );

if( $TARCHD )
	$tpl->assign( "cluster_url", rawurlencode($clustername) );

$data_gatherer->parseXML();

$heartbeat = $data_gatherer->getHeartbeat();
$jobs = $data_gatherer->getJobs();
$gnodes = $data_gatherer->getNodes();
$cpus = $data_gatherer->getCpus();

$filter_image_url = "";

foreach( $filter as $filtername => $filtervalue ) {
	$tpl->assign( "f_".$filtername, $filtervalue );
	$filter_image_url .= "&$filtername=$filtervalue";
}

$tpl->assign( "clusterimage", "./image.php?c=".rawurlencode($clustername)."&view=big-clusterimage".$filter_image_url );
$tpl->assign( "f_order", $filterorder );

if( array_key_exists( "id", $filter ) )
	$piefilter = 'id';
else if( array_key_exists( "user", $filter ) )
	$piefilter = 'user';
else if( array_key_exists( "queue", $filter ) )
	$piefilter = 'queue';

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

	} else if( count($time_fields) == 2 ) {

		$hours = 0;
		$minutes = $time_fields[0];
		$seconds = $time_fields[1];

	} else if( count($time_fields) == 1 ) {

		$hours = 0;
		$minutes = 0;
		$seconds = $time_fields[0];
	}

	$myepoch = intval( $seconds + (intval( $minutes * 60 )) + (intval( $hours * 3600 )) );

	return $myepoch;
}

function makeTime( $time ) {

	$days = intval( $time / 86400 );
	$time = ($days>0) ? $time % ($days * 86400) : $time;

	//printf( "time = %s, days = %s\n", $time, $days );

	$date_str = '';
	$day_str = '';

	if( $days > 0 ) {
		if( $days > 1 )
			$day_str .= $days . ' days';
		else
			$day_str .= $days . ' day';
	}

	$hours = intval( $time / 3600 );
	$time = $hours ? $time % ($hours * 3600) : $time;

	//printf( "time = %s, days = %s, hours = %s\n", $time, $days, $hours );

	if( $hours > 0 ) {
		$date_str .= $hours . ':';
		$date_unit = 'hours';
	}
		
	$minutes = intval( $time / 60 );
	$seconds = $minutes ? $time % ($minutes * 60) : $time;

	if( $minutes > 0 ) {

		if( $minutes >= 10 )
			$date_str .= $minutes . ':';
		else
			$date_str .= '0' . $minutes . ':';

		$date_unit = (!isset($date_unit)) ? 'minutes' : $date_unit;
	} else {
		if($hours > 0 ) {
			$date_str .= '00:';
			$date_unit = (!isset($date_unit)) ? 'minutes' : $date_unit;
		}
	}


	$date_unit = (!isset($date_unit)) ? 'seconds' : $date_unit;

	if( $seconds > 0 ) {

		if( $seconds >= 10 )
			$date_str .= $seconds . ' ' . $date_unit;
		else
			$date_str .= '0' . $seconds . ' ' . $date_unit;
			
	} else if ( $hours > 0 or $minutes > 0 )

		$date_str .= '00 ' . $date_unit;

	if( $days > 0) {

		if( $hours > 0 or $minutes > 0 or $seconds > 0 )
			$date_str = $day_str . ' - ' . $date_str;
		else
			$date_str = $day_str;
	}

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

	$c1r = hexDec( colorRed( $first ) );
	$c1g = hexDec( colorGreen( $first ) );
	$c1b = hexDec( colorBlue( $first ) );

	$c2r = hexDec( colorRed( $second ) );
	$c2g = hexDec( colorGreen( $second ) );
	$c2b = hexDec( colorBlue( $second ) );

	$rdiff = ($c1r >= $c2r) ? $c1r - $c2r : $c2r - $c1r;
	$gdiff = ($c1g >= $c2g) ? $c1g - $c2g : $c2g - $c1g;
	$bdiff = ($c1b >= $c2b) ? $c1b - $c2b : $c2b - $c1b;

	if( $rdiff >= $min_diff or $gdiff >= $min_diff or $bdiff >= $min_diff )
		return TRUE;
	else
		return FALSE;
}

function randomColor( $known_colors ) {

	$start = "004E00";
	
	$start_red = colorRed( $start );
	$start_green = colorGreen( $start );
	$start_blue = colorBlue( $start );
	
	$end = "FFFFFF";

	$end_red = colorRed( $end );
	$end_green = colorGreen( $end );
	$end_blue = colorBlue( $end );

	$change_color = TRUE;

	while( $change_color ) {

		$change_color = FALSE;

		$new_red = rand( hexDec( $start_red ), hexDec( $end_red ) );
		$new_green = rand( hexDec( $start_green ), hexDec( $end_green ) );
		$new_blue = rand( hexDec( $start_blue ), hexDec( $end_blue ) );

		$new = decHex( $new_red ) . decHex( $new_green ) . decHex( $new_blue );

		foreach( $known_colors as $old )

			if( !colorDiffer( $new, $old ) )

	 			$change_color = TRUE;
	}

	// Whoa! Actually found a good color ;)
	return $new;
}

function drawJobPie() {
}

function drawUserPie() {

}

function drawQueuePie() {

}


function drawPie() {

	global $jobs, $gnodes, $piefilter, $filter;

	$nodes = $gnodes;

	if( isset($piefilter) )	
		$pie_args = "title=" . rawurlencode("Cluster ".$piefilter." usage");
	else
		$pie_args = "title=" . rawurlencode("Cluster queue usage");
		
	$pie_args .= "&size=250x150";

	$queues = array();
	$nr_jobs = count( $jobs );
	$nr_nodes = count( $nodes );

	$emptynodes = 0;

	$job_weight = array();

	foreach( $nodes as $node ) {

		$myjobs = $node->getJobs();

		if( count( $myjobs ) == 0 )
			$emptynodes++;
	}
	$used_nodes = $nr_nodes - $emptynodes;

	$empty_percentage = ($emptynodes / $nr_nodes) * 100;
	$job_percentage = 100 - $empty_percentage; 

	$qcolors = array();
	$color = randomColor( $qcolors );
	$qcolors[] = $color;
	$pie_args .= "&free=$empty_percentage,$color";

	if( isset( $piefilter ) )
		$filterpie = array();

	foreach( $nodes as $node ) {

		$node_jobs = $node->getJobs();
		$nr_node_jobs = count( $node_jobs );
		$myhost = $node->getHostname();

		foreach( $node_jobs as $myjob ) {

			// Determine the weight of this job on the node it is running
			// - what percentage of the node is in use by this job
			//
			$job_weight[$myjob] = ( 100 / count( $node_jobs ) ) / 100;
			$qname = $jobs[$myjob][queue];

			if( isset($piefilter) ) {
				$countjob = 1;
				if( $piefilter == 'id' ) {
					if( $myjob != $filter[$piefilter] )
						$countjob = 0;
				} else if( $piefilter == 'user' ) {
					if( $jobs[$myjob][owner] != $filter[$piefilter] )
						$countjob = 0;
				} else {
					if( $jobs[$myjob][$piefilter] != $filter[$piefilter] )
						$countjob = 0;
				}

				if( $countjob ) {

					if( !isset( $filterpie[$filter[$piefilter]] ) )
						$filterpie[$filter[$piefilter]] = $job_weight[$myjob];
					else
						$filterpie[$filter[$piefilter]] = $filterpie[$filter[$piefilter]] + $job_weight[$myjob];
				} else {
					if( !isset( $filterpie["other"] ) )
						$filterpie["other"] = $job_weight[$myjob];
					else
						$filterpie["other"] = $filterpie["other"] + $job_weight[$myjob];

				}
				
			} else {

				if( !isset( $queues[$qname] ) )
					$queues[$qname] = $job_weight[$myjob];
				else
					$queues[$qname] = $queues[$qname] + $job_weight[$myjob];
			}
		}
	}

	//$qcolors = array();
	if( isset( $piefilter ) )
		$graphvals = $filterpie;
	else
		$graphvals = $queues;

	foreach( $graphvals as $name => $totalweight) {

		$percentage = ( $totalweight / $used_nodes ) * $job_percentage;
		
		$color = randomColor( $qcolors );
		$qcolors[] = $color;
		$pie_args .= "&$name=$percentage,$color";
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

			if( $state == 'R' )
				$nodes = count( $jobattrs[nodes] );
			else
				$nodes = $jobattrs[nodes];

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

	//uasort( $sorted, $cmp );
	if( $sortorder == "asc" )
		arsort( $sorted );
	else if( $sortorder == "desc" )
		asort( $sorted );

	return $sorted;
}

function makeOverview() {

	global $jobs, $nodes, $heartbeat, $clustername, $tpl;
	global $sortorder, $sortby, $filter, $sh, $hc, $m;
	global $cluster_url, $get_metric_string, $host_url, $metrics;
	global $start, $end, $reports, $gnodes, $default_showhosts;

	$metricname = $m;

	$tpl->assign("sortorder", $sortorder );
	$tpl->assign("sortby", $sortby );

	$sorted_jobs = sortJobs( $jobs, $sortby, $sortorder );

	$even = 1;

	$used_jobs = 0;
	$used_cpus = 0;
	$used_nodes = 0;

	$all_used_nodes = array();

	$avail_nodes = count( $gnodes );
	$avail_cpus = cluster_sum("cpu_num", $metrics);

	$view_cpus = 0;
	$view_jobs = 0;
	$view_nodes = 0;

	$view_used_nodes = array();

	foreach( $sorted_jobs as $jobid => $sortdec ) {

		$report_time = $jobs[$jobid][reported];

		$nodes = count( $jobs[$jobid][nodes] );
		$ppn = (int) $jobs[$jobid][ppn] ? $jobs[$jobid][ppn] : 1;
		$cpus = $nodes * $ppn;

		foreach( $jobs[$jobid][nodes] as $tempnode )
			$all_used_nodes[] = $tempnode;

		if( $jobs[$jobid][status] == 'R' ) {
			$used_cpus += $cpus;
			$used_jobs++;
		}

		if( $report_time == $heartbeat ) {

			$display_job = 1;

			foreach( $filter as $filtername=>$filtervalue ) {

				if( $filtername == 'id' && $jobid != $filtervalue )
					$display_job = 0;
				else if( $filtername == 'state' && $jobs[$jobid][status] != $filtervalue )
					$display_job = 0;
				else if( $filtername == 'queue' && $jobs[$jobid][queue] != $filtervalue )
					$display_job = 0;
				else if( $filtername == 'user' && $jobs[$jobid][owner] != $filtervalue )
					$display_job = 0;
			}

			if( $display_job ) {

				$tpl->newBlock("node");
				$tpl->assign( "clustername", $clustername );
				$tpl->assign("id", $jobid );
				$tpl->assign("state", $jobs[$jobid][status] );
				$tpl->assign("user", $jobs[$jobid][owner] );
				$tpl->assign("queue", $jobs[$jobid][queue] );
				$tpl->assign("name", $jobs[$jobid][name] );
				$domain = $jobs[$jobid][domain];
				$tpl->assign("req_cpu", makeTime( timeToEpoch( $jobs[$jobid][requested_time] ) ) );
				$tpl->assign("req_memory", $jobs[$jobid][requested_memory] );

				if( $jobs[$jobid][status] == 'R' )
					$nodes = count( $jobs[$jobid][nodes] );
				else if( $jobs[$jobid][status] == 'Q' )
					$nodes = $jobs[$jobid][nodes];

				$ppn = (int) $jobs[$jobid][ppn] ? $jobs[$jobid][ppn] : 1;
				$cpus = $nodes * $ppn;
				$tpl->assign("nodes", $nodes );
				$tpl->assign("cpus", $cpus );
				$start_time = (int) $jobs[$jobid][start_timestamp];
				$job_start = $start_time;

				$view_cpus += $cpus;
				$view_jobs++;

				if( $jobs[$jobid][status] == 'R' )
					foreach( $jobs[$jobid][nodes] as $tempnode )
						$view_used_nodes[] = $tempnode;
				else if( $jobs[$jobid][status] == 'Q' )
					$view_nodes += $jobs[$jobid][nodes];

				if( $even ) {

					$tpl->assign("nodeclass", "even");
					$even = 0;
				} else {

					$tpl->assign("nodeclass", "odd");
					$even = 1;
				}

				if( $start_time ) {

					$runningtime = makeTime( $report_time - $start_time );
					$job_runningtime = $report_time - $start_time;
					$tpl->assign("started", makeDate( $start_time ) );
					$tpl->assign("runningtime", $runningtime );
				}
			}
		}
	}
	array_unique( $all_used_nodes );
	array_unique( $view_used_nodes );
	$used_nodes = count( $all_used_nodes );
	$view_nodes += count( $view_used_nodes );

	//$tpl->assignGlobal("cpus_nr", $overview_cpus );
	//$tpl->assignGlobal("jobs_nr", $overview_jobs );

	$tpl->assignGlobal("avail_nodes", $avail_nodes );
	$tpl->assignGlobal("avail_cpus", $avail_cpus );

	$tpl->assignGlobal("used_nodes", $used_nodes );
	$tpl->assignGlobal("used_jobs", $used_jobs );
	$tpl->assignGlobal("used_cpus", $used_cpus );

	$tpl->assignGlobal("view_nodes", $view_nodes );
	$tpl->assignGlobal("view_jobs", $view_jobs );
	$tpl->assignGlobal("view_cpus", $view_cpus );

	$tpl->assignGlobal("report_time", makeDate( $heartbeat));
	
	//$tpl->assignGlobal("f_cpus_nr", $f_cpus );
	//$tpl->assignGlobal("f_jobs_nr", $f_jobs );

	if( array_key_exists( "id", $filter ) and $start_time ) {
		$tpl->newBlock( "showhosts" );

		# Present a width list
		$cols_menu = "<SELECT NAME=\"hc\" OnChange=\"toga_form.submit();\">\n";

		$hostcols = ($hc) ? $hc : 4;

		foreach(range(1,25) as $cols) {
			$cols_menu .= "<OPTION VALUE=$cols ";
			if ($cols == $hostcols)
				$cols_menu .= "SELECTED";
			$cols_menu .= ">$cols\n";
		}
		$cols_menu .= "</SELECT>\n";

		//$tpl->assign("cluster", $clustername);
		$tpl->assign("metric","$metricname $units");
		$tpl->assign("id", $filter[id]);
		# Host columns menu defined in header.php
		$tpl->assign("cols_menu", $cols_menu);

		$showhosts = isset($sh) ? $sh : $default_showhosts;
		//if( !$showhosts) $showhosts = $default_showhosts;
		$tpl->assign("checked$showhosts", "checked");

		if( $showhosts ) {
			//-----

			if( !isset($start) ) $start="jobstart";
			if( !isset($stop) ) $stop="now";
			//$tpl->assign("start", $start);
			//$tpl->assign("stop", $stop);

			$sorted_hosts = array();
			$hosts_up = $jobs[$filter[id]][nodes];

			$r = intval($job_runningtime * 1.25);

			$jobrange = ($job_runningtime < 3600) ? -3600 : -$r ;
			$jobstart = $report_time - $job_runningtime;

			if ($reports[$metricname])
				$metricval = "g";
			else
				$metricval = "m";
						
			foreach ($hosts_up as $host ) {
				$host = $host. '.'.$domain;
				$cpus = $metrics[$host]["cpu_num"][VAL];
				if (!$cpus) $cpus=1;
				$load_one  = $metrics[$host]["load_one"][VAL];
				$load = ((float) $load_one)/$cpus;
				$host_load[$host] = $load;
				$percent_hosts[load_color($load)] += 1;
				if ($metricname=="load_one")
					$sorted_hosts[$host] = $load;
				else
					$sorted_hosts[$host] = $metrics[$host][$metricname][VAL];
			}
			switch ($sort) {
				case "descending":
					arsort($sorted_hosts);
					break;
				case "by hostname":
					ksort($sorted_hosts);
					break;
				default:
				case "ascending":
					asort($sorted_hosts);
					break;
			}

			//$sorted_hosts = array_merge($down_hosts, $sorted_hosts);

			# First pass to find the max value in all graphs for this
			# metric. The $start,$end variables comes from get_context.php,
			# included in index.php.
			list($min, $max) = find_limits($sorted_hosts, $metricname);

			# Second pass to output the graphs or metrics.
			$i = 1;
			foreach ( $sorted_hosts as $host=>$value  ) {
				$tpl->newBlock ("sorted_list");
				//$host = $host. '.'.$domain;
				$host_url = rawurlencode($host);
				$cluster_url = rawurlencode($clustername);

				$textval = "";
				//printf("host = %s, value = %s", $host, $value);
				//echo "$host: $value, ";
				$val = $metrics[$host][$metricname];
				$class = "metric";
				$host_link="\"../../?c=$cluster_url&h=$host_url&r=job&jr=$jobrange&js=$jobstart\"";

				if ($val[TYPE]=="timestamp" or $always_timestamp[$metricname]) {
					$textval = date("r", $val[VAL]);
				} elseif ($val[TYPE]=="string" or $val[SLOPE]=="zero" or $always_constant[$metricname] or ($max_graphs > 0 and $i > $max_graphs )) {
					$textval = "$val[VAL] $val[UNITS]";
				} else {
					$load_color = load_color($host_load[$host]);
					$graphargs = ($reports[$metricname]) ? "g=$metricname&" : "m=$metricname&";
					$graphargs .= "z=small&c=$cluster_url&h=$host_url&l=$load_color" ."&v=$val[VAL]&x=$max&n=$min&r=job&jr=$jobrange&js=$jobstart";
				}
				if ($textval) {
					$cell="<td class=$class>".  "<b><a href=$host_link>$host</a></b><br>".  "<i>$metricname:</i> <b>$textval</b></td>";
				} else {
					$cell="<td><a href=$host_link>".  "<img src=\"../../graph.php?$graphargs\" ".  "alt=\"$host\" height=112 width=225 border=0></a></td>";
				}

				$tpl->assign("metric_image", $cell);
				if (! ($i++ % $hostcols) )
					 $tpl->assign ("br", "</tr><tr>");
			}
		}
//---
	}
}
?>
