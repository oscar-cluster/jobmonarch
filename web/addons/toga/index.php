<?php

$my_dir = getcwd();

include_once "./libtoga.php";

global $GANGLIA_PATH, $context;
chdir( $GANGLIA_PATH );
$context = 'cluster';

$title = "Torque Report";
include_once "./get_context.php";
include_once "./class.TemplatePower.inc.php";
chdir( $my_dir );
include_once "header.php";

include_once "overview.php";

include_once "footer.php";
?>
