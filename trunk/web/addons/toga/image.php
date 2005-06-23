<?php
include_once "./libtoga.php";

if ( !empty( $_GET ) ) {
        extract( $_GET );
}

$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );
$view = $httpvars->getHttpVar( "view" );
$clustername = $httpvars->getClusterName();

//printf("clustername = %s\n", $clustername );
if( isset($id) && ($id!='')) $filter[id]=$id;
if( isset($state) && ($state!='')) $filter[state]=$state;
if( isset($user) && ($user!='')) $filter[user]=$user;
if( isset($queue) && ($queue!='')) $filter[queue]=$queue;

function drawSmallClusterImage() {

	global $clustername;

	$ic = new ClusterImage( $clustername );
	$ic->draw();
}

function drawBigClusterImage() {

	global $filter, $clustername;

	$ic = new ClusterImage( $clustername );
	foreach( $filter as $filtername=>$filtervalue ) {
		//printf("filter %s,%s\n", $filtername, $filtervalue);
		switch( $filtername ) {

			case "id":
				$ic->setFilter( 'jobid', $filtervalue );
				break;
			case "user":
				$ic->setFilter( 'owner', $filtervalue);
				break;
			case "queue":
				$ic->setFilter( 'queue', $filtervalue);
				break;
			case "state":
				$ic->setFilter( 'status', $filtervalue);
				break;
			default:
				break;
		}
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
