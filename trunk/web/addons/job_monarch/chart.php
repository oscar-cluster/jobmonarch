<?php

$values	= array();
$legend = array();

foreach($_GET as $key=>$val)
{
	if ($key == "title")

		$title = $val;

	elseif ($key == "size")

		list($size_x, $size_y) = explode("x", $val);

	else
	{
		list( $value, $color ) = explode( ",", $val );
		$values[] = $value;
		$legend[] = $key;
	}
}

// Include pChart libs
//
include( "./lib/pchart/pChart/pData.class" );
include( "./lib/pchart/pChart/pChart.class" );

// Dataset definition
//
$DataSet = new pData;
$DataSet->AddPoint( $values, "Pie slices" );
$DataSet->AddPoint( $legend, "Legend" );
$DataSet->AddAllSeries();
$DataSet->SetAbsciseLabelSerie( "Legend" );

// Initialise the graph  
$myChart = new pChart(380,200);
$myChart->drawFilledRoundedRectangle(7,7,373,193,5,240,240,240);
$myChart->drawRoundedRectangle(5,5,375,195,5,230,230,230);

// Font for slice percentages & legend text
//
$myChart->setFontProperties( "./lib/pchart/tahoma.ttf", 8 );

// Draw the pie slices and percentages text
//
$myChart->drawPieGraph( $DataSet->GetData(), $DataSet->GetDataDescription(), 160, 100, 110, TRUE, TRUE, 50, 20, 20 );

// Draw legend text
//
$myChart->drawPieLegend( 280, 15, $DataSet->GetData(), $DataSet->GetDataDescription(), 250, 250, 250 );  

// Font for title: a little bigger
//
$myChart->setFontProperties( "./lib/pchart/tahoma.ttf", 12 );

// Draw title text
//
$myChart->drawTitle( 0, 5, $title, 50, 50, 50, 380, 20 );

// Draw complete graph embedded inline: output the png
//
$myChart->Stroke();
?>
