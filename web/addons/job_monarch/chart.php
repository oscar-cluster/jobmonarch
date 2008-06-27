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

#print_r( $values );
#print_r( $legend );

// Standard inclusions     
include("./lib/pchart/pData.class");  
include("./lib/pchart/pChart.class");  

// Dataset definition   
$DataSet = new pData;  
$DataSet->AddPoint($values,"Serie1");  
$DataSet->AddPoint($legend,"Serie2");  
$DataSet->AddAllSeries();  
$DataSet->SetAbsciseLabelSerie("Serie2");  

// Initialise the graph  
$Test = new pChart(380,200);  
$Test->drawFilledRoundedRectangle(7,7,373,193,5,240,240,240);  
$Test->drawRoundedRectangle(5,5,375,195,5,230,230,230);  

// Draw the pie chart  
$Test->setFontProperties("./lib/pchart/tahoma.ttf",8);  
$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),150,90,110,TRUE,TRUE,50,20,20);  
$Test->drawPieLegend(310,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);  

$Test->drawTitle(60,22,$title,50,50,50,585);

$Test->Stroke();
?>
