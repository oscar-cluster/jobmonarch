<?php
include_once "./libtoga.php";

$httpvars = new HTTPVariables( $HTTP_GET_VARS );
$view = $httpvars->getHttpVar( "view" );
$clustername = $httpvars->getClusterName();

function drawSmallClusterImage() {

	$ic = new ClusterImage( $clustername, null );
	$ic->draw();
}

function drawBigClusterImage() {

	$ic = new ClusterImage( $clustername, null );
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
