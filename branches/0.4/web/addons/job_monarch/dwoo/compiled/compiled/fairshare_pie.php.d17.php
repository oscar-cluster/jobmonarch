<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><?php

function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

#$timerStart = microtime_float();

function colorRed( $color ) {
        return substr( $color, 0, 2 );
}
function colorGreen( $color ) {
        return substr( $color, 2, 2 );
}
function colorBlue( $color ) {
        return substr( $color, 4, 2 );
}

function colorDiffer( $first, $second ) {

        // Make sure these two colors differ atleast 50 R/G/B
        $min_diff = 50;

        $c1r = hexDec( colorRed( $first ) );
        $c1g = hexDec( colorGreen( $first ) );
        $c1b = hexDec( colorBlue( $first ) );

        $c2r = hexDec( colorRed( $second ) );
        $c2g = hexDec( colorGreen( $second ) );
        $c2b = hexDec( colorBlue( $second ) );

        $rdiff = ($c1r >= $c2r) ? $c1r - $c2r : $c2r - $c1r;
        $gdiff = ($c1g >= $c2g) ? $c1g - $c2g : $c2g - $c1g;
        $bdiff = ($c1b >= $c2b) ? $c1b - $c2b : $c2b - $c1b;

        if( $rdiff >= $min_diff or $gdiff >= $min_diff or $bdiff >= $min_diff )
                return TRUE;
        else
                return FALSE;
}

function randomColor( $known_colors ) {

        $start = "004E00";

        $start_red = colorRed( $start );
        $start_green = colorGreen( $start );
        $start_blue = colorBlue( $start );

        $end = "FFFFFF";

        $end_red = colorRed( $end );
        $end_green = colorGreen( $end );
        $end_blue = colorBlue( $end );

        $change_color = TRUE;

        while( $change_color ) {

                $change_color = FALSE;

                $new_red = rand( hexDec( $start_red ), hexDec( $end_red ) );
                $new_green = rand( hexDec( $start_green ), hexDec( $end_green ) );
                $new_blue = rand( hexDec( $start_blue ), hexDec( $end_blue ) );

                $new = sprintf("%02s", decHex( $new_red ) ) . sprintf("%02s", decHex( $new_green ) ) . sprintf("%02s", decHex( $new_blue ) );

                foreach( $known_colors as $old )

                        if( !colorDiffer( $new, $old ) )

                                $change_color = TRUE;
        }

        // Whoa! Actually found a good color ;)
        return $new;
}

if( !isset($_GET) )
{
  $_GET = array();
}

unset( $_GET['m'] );
unset( $_GET['mc'] );
unset( $_GET['hc'] );
unset( $_GET['r'] );
unset( $_GET['s'] );
unset( $_GET['c'] );
unset( $metricname );
unset( $metric );

$_GET['h'] = "batch1.irc.sara.nl";
$_GET['c'] = "LISA Cluster";

$monitor = FALSE;
$size="250x150";

if(isset($_GET['monitor']))
{
	$monitor = TRUE;
	$size="500x500";
}


unset($context);
global $context;
$context = "host";

//global $debug;
//$debug = 0;
unset( $metrics );
global $metrics;


include_once "./eval_conf.php";
include_once "./functions.php";

$base_dir = "/data/ganglia-web-current";
require $base_dir . "/conf_default.php";

# Include user-defined overrides if they exist.
if( file_exists( $base_dir . "/conf.php" ) ) {
  include $base_dir . "/conf.php";
}

include_once "./get_context.php";
include_once "./ganglia.php";

$clustername = "LISA Cluster";
$hostname = "batch1.irc.sara.nl";

#$timerBeforeInclude = microtime_float();
#include_once "./get_ganglia.php";
Gmetad($conf['ganglia_ip'], $conf['ganglia_port']);
#$timerAfterInclude = microtime_float();


$fairshare_reporter_host = "batch1.irc.sara.nl";

$rrd_dir = $conf['rrds'] . "/" . $clustername . "/" . $fairshare_reporter_host;

foreach( $metrics as $m_name => $m_details )
{
	if (preg_match("/maui_fs_([a-z]+)/i", $m_name, $matches))
        {
		if( strpos( $m_name, "_current" ) !== FALSE )
		{
			$name_fields = explode( "maui_fs_", $m_name);
			$name2_fields = explode( "_current", $name_fields[1]);
			$short_name = $name2_fields[0];

                        $real_value = $metrics[$m_name]['VAL'];

			if( $real_value > 1.00 )
			{
				$fs[$short_name] = $real_value;
			}
		}
        }
}

$pie_args = '';

$colors = array();

$pie_args = "title=" . rawurlencode("Cluster Fairshare Percentages");
$pie_args .= "&size=". $size;

foreach( $fs as $fs_name => $fs_val )
{
    // Show pie chart of loads

    $color = randomColor( $colors );
    $colors[] = $color;

    $n = $percent_hosts[$color];
    $name_url = rawurlencode($fs_name);
    $pie_args .= "&$name_url=$fs_val,$color";
}

if( !$monitor )
{
	echo "<img src=\"pie.php?";
	echo "$pie_args";
	echo "\" >";
}
else
{
	$url = 'https://'. $_SERVER['HTTP_HOST'] . '/pie.php?'.$pie_args;
	$image_output = file_get_contents( $url );
	header('Content-Type: image/png');
	echo( $image_output );
}

#$timerStop = microtime_float();
#$timerTotal         = round($timerStop - $timerStart,3);
#$timerInclude       = round($timerAfterInclude - $timerBeforeInclude,5);
#file_put_contents('/tmp/fairshare','Total runtime: '.$timerTotal.' include time: '.$timerInclude);

?>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>