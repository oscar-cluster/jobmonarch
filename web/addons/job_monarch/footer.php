<?php
$tpl = new TemplatePower( "templates/footer.tpl" );
$tpl->prepare();
$tpl->assign("webfrontend-version",$version["webfrontend"]);
$tpl->assign("togaweb-version", "0.1");
$tpl->assign("togaarch-version", "0.1");
$tpl->assign("togaplug-version", "0.1");

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
