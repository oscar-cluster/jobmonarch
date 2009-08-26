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

ini_set("memory_limit","200M");
set_time_limit(0);

include_once "./libtoga.php";

if ( !empty( $_GET ) )
{
        extract( $_GET );
}

function makeSession()
{
        $ds             = new DataSource();
        $myxml_data     = &$ds->getData();

        unset( $_SESSION['data'] );

        $_SESSION['data']               = &$myxml_data;
        $_SESSION['gather_time']        = time();
}

global $session_active, $_SESSION, $myxml_data;

function checkSessionPollInterval( $poll_interval )
{
        global $session_active, $_SESSION;

        if( ! session_active )
        {
                return 0;
        }

        if( isset( $_SESSION['poll_interval'] ) )
        {
                if( $poll_interval <> $_SESSION['poll_interval'] )
                {
                        $_SESSION['poll_interval']      = $poll_interval;
                }
        }
        else
        {
                $_SESSION['poll_interval']      = $poll_interval;
        }

        session_write_close();

        $session_active = false;
}

function checkSession()
{
        global $session_active, $_SESSION;

        session_start();

        $session_active         = true;

        // I got nothing; create session
        //
        if( ! isset( $_SESSION['gather_time'] ) || ! isset( $_SESSION['data'] ) )
        {
                makeSession();

                return 0;
        }

        if( isset( $_SESSION['poll_interval'] ) )
        {
                $gather_time    = $_SESSION['gather_time'];
                $poll_interval  = $_SESSION['poll_interval'];

                $cur_time       = time();

                // If poll_interval time elapsed since last update; recreate session
                //
                if( ($cur_time - $gather_time) >= $poll_interval )
                {
                        makeSession();

                        return 0;
                }
        }
}

checkSession();
$myxml_data	= &$_SESSION['data'];
session_write_close();

$httpvars	= new HTTPVariables( $HTTP_GET_VARS, $_GET );
$view		= $httpvars->getHttpVar( "view" );
$host		= $httpvars->getHttpVar( "host" );
$query		= $httpvars->getHttpVar( "query" );
$clustername	= $httpvars->getClusterName();

if( isset($jid) && ($jid!='')) $filter['jid']=$jid;
if( isset($state) && ($state!='')) $filter['state']=$state;
if( isset($owner) && ($owner!='')) $filter['owner']=$owner;
if( isset($queue) && ($queue!='')) $filter['queue']=$queue;
if( isset($host) && ($host!='')) $filter['host']=$host;
if( isset($query) && ($query!='')) $filter['query']=$query;

$data_gatherer  = new DataGatherer( $clustername );
$data_gatherer->parseXML( &$myxml_data );

function drawHostImage()
{
	global $clustername, $hostname, $data_gatherer;

	if( $data_gatherer->isJobmonRunning() )
	{
		$ic = new HostImage( $data_gatherer, $clustername, $hostname );
	}
	else
	{
		$ic = new EmptyImage();
	}

	$ic->draw();
}

function drawSmallClusterImage() 
{
	global $clustername, $data_gatherer, $myxml_data;

	if( $data_gatherer->isJobmonRunning() )
	{
		$ic = new ClusterImage( $myxml_data, $clustername );
		$ic->setSmall();
	}
	else
	{
		$ic = new EmptyImage();
	}

	$ic->draw();
}

function drawBigClusterImage()
{
	global $filter, $clustername, $myxml_data;

	$ic = new ClusterImage( $myxml_data, $clustername );
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
