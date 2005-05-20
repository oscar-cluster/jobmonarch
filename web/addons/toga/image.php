<?php

include_once "./libtoga.php";

$httpvars = new HTTPVariables();

$view = $httpvars->getHttpVar( "view" );

function drawSmallClusterImage() {

	$ic = new ClusterImage();
	$ic->draw();
}

function drawBigClusterImage() {

	// iets
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
