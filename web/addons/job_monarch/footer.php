<?php
/*
 *
 * This file is part of Jobmonarch
 *
 * Copyright (C) 2006-2013  Ramon Bastiaans
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

$tpl = new TemplatePower( "templates/footer.tpl" );
$tpl->prepare();
$tpl->assign("webfrontend-version",$version["webfrontend"]);
//$tpl->assign("togaweb-version", "0.1.1");
//$tpl->assign("togaarch-version", "0.1.1");
//$tpl->assign("togaplug-version", "0.1.1");

if ($version["gmetad"]) {
   $tpl->assign("webbackend-component", "gmetad");
   $tpl->assign("webbackend-version",$version["gmetad"]);
}
elseif ($version["gmond"]) {
   $tpl->assign("webbackend-component", "gmond");
   $tpl->assign("webbackend-version", $version["gmond"]);
}

$tpl->assign("parsetime", sprintf("%.4f", $parsetime) . "s");

$tpl->printToScreen();
?>
