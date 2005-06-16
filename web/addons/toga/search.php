<?php

global $clustername, $tpl;

function validateFormInput() {
	global $clustername, $tpl, $id, $user, $name, $start_from_time, $start_to_time, $queue;
	global $end_from_time, $end_to_time;

	$error = 0;

	$none_set = 0;

	//if( $id == '' or $user == '' or $name == '' or $start_from_time == '' or $start_to_time == '' or $queue == '' or $end_from_time == '' or $end_to_time == '') $none_set = 1;

	//if (!isset($id) and !isset($user) and !isset($start_from_time) and !isset($start_to_time) and !isset($end_from_time) and !isset($end_to_time) and !isset($queue) ) $none_set = 0;

	if( $none_set ) {
		$error = 1;
		$error_msg = "<FONT COLOR=\"red\"><B>No search criteria set!</B></FONT>";
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
	global $end_from_time, $end_to_time, $filter;

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
			$domain = $job[domain];
			$ppn = (int) $job[ppn] ? $job[ppn] : 1;
			$cpus = $nodes_nr * $ppn;

			$tpl->assign( "nodes", $nodes_nr );
			$tpl->assign( "cpus", $cpus );

			$runningtime = intval( $job[stop_timestamp] - $job[start_timestamp] );
			$tpl->assign( "started", makeDate( $job[start_timestamp] ) );
			$tpl->assign( "finished", makeDate( $job[stop_timestamp] ) );
			$tpl->assign( "runningtime", makeTime( $runningtime ) );
			
			print_r( $job );
			print_r( $nodes );
			//output jobzooi

		}
		
		if( count( $search_ids ) == 1 ) {

			$tpl->newBlock( "showhosts" );
		}

		// show search results

	}
}
?>
