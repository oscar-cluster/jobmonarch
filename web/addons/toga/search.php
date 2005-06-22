<?php

global $clustername, $tpl;

function validateFormInput() {
	global $clustername, $tpl, $id, $user, $name, $start_from_time, $start_to_time, $queue;
	global $end_from_time, $end_to_time;

	$error = 0;

	$none_set = 0;

	if( $id == '' and $user == '' and $name == '' and $start_from_time == '' and $start_to_time == '' and $queue == '' and $end_from_time == '' and $end_to_time == '') {
		$error = 1;
		$error_msg = "<FONT COLOR=\"red\"><B>No search criteria set!</B></FONT>";
	}

	if( !is_numeric($id) and !$error and $id != '') {

		$error = 1;
		$error_msg = "<FONT COLOR=\"red\"><B>Id must be a number</B></FONT>";
	}

	// doe checks en set error en error_msg in case shit

	if( $error) {
		$tpl->assign( "form_error_msg", $error_msg );
		return 0;
	} else {
		return 1;
	}
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

function datetimeToEpoch( $datetime ) {

	$datetime_fields = explode( ' ', $datetime );

	$date = $datetime_fields[0];
	$time = $datetime_fields[1];

	$date_fields = explode( '-', $date );

	$days = $date_fields[0];
	$months = $date_fields[1];
	$years = $date_fields[2];

	$time_fields = explode( ':', $time );

	$hours = $time_fields[0];
	$minutes = $time_fields[1];
	$seconds = $time_fields[2];

	$timestamp = mktime( $hours, $minutes, $seconds, $months, $days, $years );

	return $timestamp;
}

function epochToDatetime( $epoch ) {

	return strftime( "%d-%m-%Y %H:%M:%S", $epoch );
}

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

function makeSearchPage() {
	global $clustername, $tpl, $id, $user, $name, $start_from_time, $start_to_time, $queue;
	global $end_from_time, $end_to_time, $filter, $default_showhosts, $m, $hosts_up;
	global $period_start, $period_stop;

	$metricname = $m;
	//printf("job_start = %s job_stop = %s\n", $job_start, $job_stop );
	//printf("start = %s stop = %s\n", $start, $stop );

	$tpl->assign( "cluster", $clustername );
	$tpl->assign( "id_value", $id );
	$tpl->assign( "user_value", $user );
	$tpl->assign( "queue_value", $queue );
	$tpl->assign( "name_value", $name );
	$tpl->assign( "start_from_value", rawurldecode( $start_from_time ) );
	$tpl->assign( "start_to_value", rawurldecode( $start_to_time ) );
	$tpl->assign( "end_from_value", rawurldecode( $end_from_time ) );
	$tpl->assign( "end_to_value", rawurldecode( $end_to_time ) );

	if( validateFormInput() ) {

		$tpl->newBlock( "search_results" );
		$tdb = new TarchDbase();
		if( $start_from_time ) $start_from_time = datetimeToEpoch( $start_from_time );
		if( $start_to_time ) $start_to_time = datetimeToEpoch( $start_to_time );
		if( $end_from_time ) $end_from_time = datetimeToEpoch( $end_from_time );
		if( $end_to_time ) $end_to_time = datetimeToEpoch( $end_to_time );
		$search_ids = $tdb->searchDbase( $id, $queue, $user, $name, $start_from_time, $start_to_time, $end_from_time, $end_to_time );

		foreach( $search_ids as $foundid ) {

			$job = $tdb->getJobArray( $foundid );
			$nodes = $tdb->getNodesForJob( $foundid );

			$tpl->newBlock( "node" );
			$tpl->assign( "id", $job[id] );
			$tpl->assign( "state", $job[status] );
			$tpl->assign( "user", $job[owner] );
			$tpl->assign( "queue", $job[queue] );
			$tpl->assign( "name", $job[name] );
			$tpl->assign( "req_cpu", makeTime( TimeToEpoch( $job[requested_time] ) ) );
			$tpl->assign( "req_memory", $job[requested_memory] );

			$nodes_nr = count( $nodes );

			// need to replace later with domain stored from dbase
			//
			//$job_domain = $job[domain];

			//$myhost = $_SERVER[HTTP_HOST];
			//$myhf = explode( '.', $myhost );
			//$myhf = array_reverse( $myhf );
			//array_pop( $myhf );
			//$myhf = array_reverse( $myhf );
			//$job_domain = implode( '.', $myhf );
			
			//print_r( $job );
			//printf( "job domain = %s\n", $job_domain);
			$ppn = (int) $job[ppn] ? $job[ppn] : 1;
			$cpus = $nodes_nr * $ppn;

			$tpl->assign( "nodes", $nodes_nr );
			$tpl->assign( "cpus", $cpus );

			$job_start = $job[start_timestamp];
			$job_stop = $job[stop_timestamp];
			$runningtime = intval( $job_stop - $job_start );
			$tpl->assign( "started", makeDate( $job_start ) );
			$tpl->assign( "finished", makeDate( $job_stop ) );
			$tpl->assign( "runningtime", makeTime( $runningtime ) );
			
			//print_r( $job );
			//print_r( $nodes );
		}

		
		if( count( $search_ids ) == 1 ) {

			$tpl->newBlock( "showhosts" );

			$showhosts = isset($sh) ? $sh : $default_showhosts;
			//if( !$showhosts) $showhosts = $default_showhosts;
			$tpl->assign("checked$showhosts", "checked");

			# Present a width list
			$cols_menu = "<SELECT NAME=\"hc\" OnChange=\"archive_search_form.submit();\">\n";

			$hostcols = ($hc) ? $hc : 4;

			foreach(range(1,25) as $cols) {
				$cols_menu .= "<OPTION VALUE=$cols ";
				if ($cols == $hostcols)
					$cols_menu .= "SELECTED";
				$cols_menu .= ">$cols\n";
			}
			$cols_menu .= "</SELECT>\n";

			$tpl->assign("metric","$metricname $units");
			$tpl->assign("id", $id);
			# Host columns menu defined in header.php
			$tpl->assign("cols_menu", $cols_menu);

			if( $showhosts ) {
				//bla

				//printf("job_start = %s job_stop = %s\n", $job_start, $job_stop );
				//printf("start = %s stop = %s\n", $start, $stop );

				if( !$period_start ) // Add an additional 5 minutes before
					$period_start = intval( $job_start - (intval( $runningtime * 0.10 ) ) );
				else
					$period_start = datetimeToEpoch( $period_start );

				if( !$period_stop ) // Add an additional 5 minutes after
					$period_stop = intval( $job_stop + (intval( $runningtime * 0.10 ) ) );
				else
					$period_stop = datetimeToEpoch( $period_stop );

				//printf("start = %s stop = %s\n", $start, $stop );

				$tpl->assign("period_start", epochToDatetime( $period_start ) );
				$tpl->assign("period_stop", epochToDatetime( $period_stop ) );

				$hosts_up = array();

				foreach( $nodes as $mynode )
					$hosts_up[] = $mynode[hostname];

				//print_r( $hosts_up );

				$sorted_hosts = array();
				//$hosts_up = $jobs[$filter[id]][nodes];

				foreach ($hosts_up as $host ) {
					//$host = $host. '.'.$job_domain;
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
					$host_link="\"?c=$cluster_url&h=$host_url&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop\"";

					if ($val[TYPE]=="timestamp" or $always_timestamp[$metricname]) {
						$textval = date("r", $val[VAL]);
					} elseif ($val[TYPE]=="string" or $val[SLOPE]=="zero" or $always_constant[$metricname] or ($max_graphs > 0 and $i > $max_graphs )) {
						$textval = "$val[VAL] $val[UNITS]";
					} else {
						$graphargs = "z=small&c=$cluster_url&m=$metricname&h=$host_url&v=$val[VAL]&x=$max&n=$min&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop&min=$min&max=$max";
					}
					if ($textval) {
						$cell="<td class=$class>".  "<b><a href=$host_link>$host</a></b><br>".  "<i>$metricname:</i> <b>$textval</b></td>";
					} else {
						$cell="<td><a href=$host_link>".  "<img src=\"./graph.php?$graphargs\" ".  "alt=\"$host\" border=0></a></td>";
					}

					$tpl->assign("metric_image", $cell);
					if (! ($i++ % $hostcols) )
						 $tpl->assign ("br", "</tr><tr>");
				}

				//einde bla
			}
		}

	}
}
?>
