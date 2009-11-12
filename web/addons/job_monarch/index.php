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
 * SVN $Id: index.php 527 2008-06-27 13:37:31Z ramonb $
 */

//ini_set("memory_limit","200M");
ini_set("memory_limit","1G");
set_time_limit(0);


$my_dir = getcwd();

global $r, $range;

include_once "./libtoga.php";

if ( !empty( $_GET ) )
{
	extract( $_GET );
}

global $GANGLIA_PATH;
chdir( $GANGLIA_PATH );

include_once "./class.TemplatePower.inc.php";
chdir( $my_dir );

$httpvars	= new HTTPVariables( $HTTP_GET_VARS, $_GET );
$clustername	= $httpvars->getClusterName();
$view		= $httpvars->getHttpVar( "view" );

global $mySession, $myData, $myXML;

//printf( "c %s\n", $clustername );

$mySession      = new SessionHandler( $clustername );
$mySession->checkSession();

$session        = &$mySession->getSession();
$myXML          = $session['data'];

$myData         = new DataGatherer( $clustername );
$myData->parseXML( $myXML );

$mySession->updatePollInterval( $myData->getPollInterval() );
$mySession->endSession();

$filter = array();

if( !isset($view) ) $view = "overview";
if( !isset($sortorder) ) $sortorder = "asc";
if( !isset($sortby) ) $sortby = "id";

if( isset( $filterorder ) && ($filterorder!='') )
{
	$myfilter_fields = explode( ",", $filterorder );
}
else
{
	if( isset($queue) && ($queue!='')) $filter['queue']=$queue;
	if( isset($state) && ($state!='')) $filter['state']=$state;
	if( isset($user) && ($user!='')) $filter['user']=$user;
	if( isset($id) && ($id!='')) $filter['id']=$id;
}

// Fill filter array in order they were picked by user
if( isset($myfilter_fields) )
{
	foreach( $myfilter_fields as $myfilter )
	{
		switch( $myfilter )
		{
			case "queue":
				$filter['queue']=$queue;
				break;
			case "state":
				$filter['state']=$state;
				break;
			case "user":
				$filter['user']=$user;
				break;
			case "id":
				$filter['id']=$id;
				break;
		}
	}
}

function epochToDatetime( $epoch )
{
        return strftime( "%d-%m-%Y %H:%M:%S", $epoch );
}


function makeFooter() 
{
	global $tpl, $version, $parsetime, $monarchversion;

	$tpl->gotoBlock( "_ROOT" );
	$tpl->assign("webfrontend-version",$version["webfrontend"]);
	$tpl->assign("monarch-version", $monarchversion);

	if ($version["gmetad"]) 
	{
		$tpl->assign("webbackend-component", "gmetad");
		$tpl->assign("webbackend-version",$version["gmetad"]);
	} 
	else if ($version["gmond"]) 
	{
		$tpl->assign("webbackend-component", "gmond");
		$tpl->assign("webbackend-version", $version["gmond"]);
	}

	$tpl->assign("parsetime", sprintf("%.4f", $parsetime) . "s");
}

function includeSearchpage() 
{
	global $tpl;

	$tpl->assignInclude( "main", "templates/search.tpl" );
}

function includeOverview() 
{
	global $tpl;

	$tpl->assignInclude( "main", "templates/overview.tpl" );
}

function includeHostPage() 
{
	global $tpl;

	$tpl->assignInclude( "main", "templates/host_view.tpl" );
}

$tpl = new TemplatePower( "templates/index.tpl" );

$tpl->assignInclude( "header", "templates/header.tpl" );

if( isset( $h ) and $h != '' ) 
{
	$hostname = $h;
}
$ic                     = new ClusterImage( $myData, $clustername );
$ic->setBig();
$ic->setNoimage();
$ic->draw();

//printf("%s\n", $ic->getImagemapArea() );


switch( $view ) 
{
	case "overview":

		//includeOverview();
		break;

	case "search":

		//includeSearchPage();
		break;

	case "host":

		//includeHostPage();
		break;

	default:

		//includeOverview();
		break;
}

$tpl->assignInclude( "footer", "templates/footer.tpl" );
$tpl->prepare();

$longtitle = "Batch Report :: Powered by Job Monarch!";
$title = "Batch Report";
$tpl->assign("cluster_url", rawurlencode($clustername) );
$tpl->assign("cluster", $clustername );

session_start();
$tpl->assign( "session_name", session_name() );
$tpl->assign( "session_id", session_id() );

$rjqj_str .= "./graph.php?z=small&c=$clustername&g=job_report&r=$range&st=$cluster[LOCALTIME]";

$tpl->assign( "rjqj_graph", $rjqj_str );
$tpl->assign( "uue_clustername", rawurlencode($clustername) );

$tpl->assign( "node_area_map", $ic->getImagemapArea() );

switch( $view ) 
{
	case "overview":

		//include "./overview.php";
		//makeOverview();
		break;

	case "search":

		//include "./search.php";
		//makeSearchPage();
		break;

	case "host":

		//include "./host_view.php";
		//makeHostView();
		break;

	default:

		//makeOverview();
		break;
}

makeFooter();
$tpl->printToScreen();
?>
