<?php
include_once "./libtoga.php";

if ( !empty( $_GET ) ) {
        extract( $_GET );
}

$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );
$view = $httpvars->getHttpVar( "view" );
$clustername = $httpvars->getClusterName();

if( isset($id) && ($id!='')) $filter = 'id';
else if( isset($state) && ($state!='')) $filter='state';
else if( isset($user) && ($user!='')) $filter='user';
else if( isset($queue) && ($queue!='')) $filter='queue';

function drawSmallClusterImage() {

	$ic = new ClusterImage( $clustername );
	$ic->draw();
}

function drawBigClusterImage() {

	global $filter, $queue, $id, $user;

	$ic = new ClusterImage( $clustername );
	switch( $filter ) {

		case "id":
			$ic->setFilter( 'jobid', $id );
			break;
		case "user":
			$ic->setFilter( 'owner', $user);
			break;
		case "queue":
			$ic->setFilter( 'queue', $queue);
			break;
		default:
			break;
	}
	$ic->draw();
}

switch( $view ) {

	case "small-clusterimage":

		drawSmallClusterImage();
		
		break;

	case "big-clusterimage":

		drawBigClusterImage();
	
		break;

	default:

		break;
}

?>
