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

			printf("found job %s\n", $foundid );
			//output jobzooi

		}
		
		if( count( $search_ids ) == 1 ) {

			$tpl->newBlock( "showhosts" );
		}

		// show search results

	}
}
?>
