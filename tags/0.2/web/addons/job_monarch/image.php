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

$httpvars = new HTTPVariables( $HTTP_GET_VARS, $_GET );
$view = $httpvars->getHttpVar( "view" );
$clustername = $httpvars->getClusterName();

//printf("clustername = %s\n", $clustername );
if( isset($id) && ($id!='')) $filter[id]=$id;
if( isset($state) && ($state!='')) $filter[state]=$state;
if( isset($user) && ($user!='')) $filter[user]=$user;
if( isset($queue) && ($queue!='')) $filter[queue]=$queue;

function drawHostImage() {

	global $clustername, $hostname;

	$data_gatherer = new DataGatherer( $clustername );
	$data_gatherer->parseXML();

	if( $data_gatherer->isJobmonRunning() )
		$ic = new HostImage( $data_gatherer, $clustername, $hostname );
	else
		$ic = new EmptyImage();

	$ic->draw();
}

function drawSmallClusterImage() {

	global $clustername;

	$data_gatherer = new DataGatherer( $clustername );
	$data_gatherer->parseXML();

	if( $data_gatherer->isJobmonRunning() ) {
		$ic = new ClusterImage( $clustername );
		$ic->setSmall();
	} else {
		$ic = new EmptyImage();
	}

	$ic->draw();
}

function drawBigClusterImage() {

	global $filter, $clustername;

	$ic = new ClusterImage( $clustername );
	$ic->setBig();

	if( isset( $filter ) ) {
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
