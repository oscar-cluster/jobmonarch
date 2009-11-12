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
 * SVN $Id: image.php 329 2007-04-22 13:36:26Z bastiaans $
 */

ini_set("memory_limit","1G");
set_time_limit(0);

include_once "./libtoga.php";

if ( !empty( $_GET ) )
{
        extract( $_GET );
}

$httpvars	= new HTTPVariables( $HTTP_GET_VARS, $_GET );
$view		= $httpvars->getHttpVar( "view" );
$host		= $httpvars->getHttpVar( "host" );
$query		= $httpvars->getHttpVar( "query" );
$clustername	= $httpvars->getClusterName();

global $mySession, $myData, $myXML;

//printf( "c %s\n", $clustername );

$mySession      = new SessionHandler( $clustername );
$mySession->checkSession();

$session        = &$mySession->getSession();
$myXML		= $session['data'];

$myData         = new DataGatherer( $clustername );
$myData->parseXML( $myXML );

$mySession->updatePollInterval( $myData->getPollInterval() );
$mySession->endSession();

//printf( "%s\n", strlen( $myXML ) );

if( isset($jid) && ($jid!='')) $filter['jid']=$jid;
if( isset($state) && ($state!='')) $filter['state']=$state;
if( isset($owner) && ($owner!='')) $filter['owner']=$owner;
if( isset($queue) && ($queue!='')) $filter['queue']=$queue;
if( isset($host) && ($host!='')) $filter['host']=$host;
if( isset($query) && ($query!='')) $filter['query']=$query;

function drawHostImage()
{
	global $clustername, $hostname, $myData;

	if( $myData->isJobmonRunning() )
	{
		$ic = new HostImage( $myData, $clustername, $hostname );
	}
	else
	{
		$ic = new EmptyImage();
	}

	$ic->draw();
}

function drawSmallClusterImage() 
{
	global $clustername, $myData, $myXML;

	//printf( "%s\n", strlen( $myXML ) );

	if( $myData->isJobmonRunning() )
	{
		//$ic = new ClusterImage( $myXML, $clustername );
		$ic = new ClusterImage( $myData, $clustername );
		$ic->setSmall();
		//printf( "is running\n" );
	}
	else
	{
		$ic = new EmptyImage();
		//printf( "not running\n" );
	}

	$ic->draw();
}

function drawBigClusterImage()
{
	global $filter, $clustername, $myXML, $myData;

	//$ic = new ClusterImage( $myXML, $clustername );
	$ic = new ClusterImage( $myData, $clustername );
	$ic->setBig();

	if( isset( $filter ) )
	{
		foreach( $filter as $filtername=>$filtervalue )
		{
			$ic->setFilter( $filtername, $filtervalue );
		}
	}
	$ic->draw();
}

switch( $view ) 
{
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
