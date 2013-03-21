<?php
/*
 *
 * This file is part of Jobmonarch
 *
 * Copyright (C) 2006  Ramon Bastiaans
 *
 * Jobmonarch is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Jobmonarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * SVN $Id$
 */

include_once "./libtoga.php";

if ( !empty( $_GET ) ) {
        extract( $_GET );
}

function checkSessionData() {

	global $_SESSION;

	session_start();

	if( isset( $_SESSION["data"] ) ) {
		$myxml_data	= &$_SESSION["data"];
	} else {
		$myxml_data	= 0;
	}

	if( !$myxml_data ) {
		$ds             = new DataSource();
		$myxml_data     = $ds->getData();

		//print_f( "%s\n", $myxml_data );
	}
	return $myxml_data;
}


$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );
$view = $httpvars->getHttpVar( "j_view" );
$clustername = $httpvars->getClusterName();

//printf("clustername = %s\n", $clustername );
if( isset($f_id) && ($f_id!='')) $filter[f_id]=$id;
if( isset($f_state) && ($f_state!='')) $filter[f_state]=$state;
if( isset($f_user) && ($f_user!='')) $filter[f_user]=$user;
if( isset($f_queue) && ($f_queue!='')) $filter[f_queue]=$queue;

function drawHostImage() {

	global $clustername, $hostname, $data_gatherer;

	$ds             = new DataSource();
	$myxml_data     = $ds->getData();

	$data_gatherer	= new DataGatherer( $clustername );

	$data_gatherer->parseXML( $myxml_data );

	if( $data_gatherer->isJobmonRunning() )
		$ic = new HostImage( $data_gatherer, $clustername, $hostname );
	else
		$ic = new EmptyImage();

	$ic->draw();
}

function drawSmallClusterImage() {

	global $clustername, $data_gatherer;

	$ds             = new DataSource();
	$myxml_data     = $ds->getData();

	$data_gatherer	= new DataGatherer( $clustername );

	$data_gatherer->parseXML( $myxml_data );

	if( $data_gatherer->isJobmonRunning() ) {
		$ic = new ClusterImage( $myxml_data, $clustername );
		$ic->setSmall();
	} else {
		$ic = new EmptyImage();
	}

	$ic->draw();
}

function drawBigClusterImage() {

	global $filter, $clustername;

	$myxml_data	= checkSessionData();

	$ic = new ClusterImage( $myxml_data, $clustername );
	$ic->setBig();

	if( isset( $filter ) ) {
		foreach( $filter as $filtername=>$filtervalue ) {
			//printf("filter %s,%s\n", $filtername, $filtervalue);
			switch( $filtername ) {

				case "f_id":
					$ic->setFilter( 'jobid', $filtervalue );
					break;
				case "f_user":
					$ic->setFilter( 'owner', $filtervalue);
					break;
				case "f_queue":
					$ic->setFilter( 'queue', $filtervalue);
					break;
				case "f_state":
					$ic->setFilter( 'status', $filtervalue);
					break;
				default:
					break;
			}
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

	case "hostimage":

		drawHostImage();
	
		break;

	default:

		break;
}

?>
