<?php

$GANGLIA_PATH = "/var/www/ganglia";

include_once "$GANGLIA_PATH/conf.php";
include_once "$GANGLIA_PATH/functions.php";

class NodeImg {

	var $image;

	function NodeImg( $image = null ) {

		$imageWidth = 100;
		$imageHeight = 100;

		if( ! $image ) {
			$this->$image = @imageCreate( $imageWidth, $imageHeight ) 
				or die( "Cannot initialize new image stream: is GD installed?" );
		} else {
			$this->$image = $image;
		}

		$background_color = imageColorAllocate( $this->$image, 255, 255, 255 );
		$black_color = imageColorAllocate( $this->$image, 0, 0, 0);
		imageRectangle( $this->$image, 0, 0, $imageWidth-1, $imageHeight-1, $black_color );
	}

	function colorHex( $color ) {
	
		$my_color = imageColorAllocate( $this->$image, hexdec( substr( $color, 0, 2 )), hexdec( substr( $color, 2, 4 )), hexdec( substr( $color, 4, 6 ) ) );

		return $my_color;
	}

	function drawNode( $x, $y, &$queuecolor, $load, &$jobcolor ) {

		// Convert Ganglia's Hexadecimal load color to a Decimal one
		$my_loadcolor = $this->colorHex( load_color($load) );

		imageFilledRectangle( $this->$image, $x, $y, $x+12, $y+12, $queuecolor );
		imageFilledRectangle( $this->$image, $x+2, $y+2, $x+10, $y+10, $my_loadcolor );
		//imageFilledEllipse( $this->$image, ($x+9)/2, ($y+9)/2, 6, 6, $jobcolor );
	}

	function drawImage() {

		$queue_color = imageColorAllocate( $this->$image, 0, 102, 304 );
		$job_color = imageColorAllocate( $this->$image, 204, 204, 0 );

		$this->drawNode( 1, 1, $queue_color, 0.1, $job_color );
		header( "Content-type: image/png" );
		imagePNG( $this->$image );
		imageDestroy( $this->$image );
	}
}

$my_node = new NodeImg();
$my_node->drawImage();

?>
