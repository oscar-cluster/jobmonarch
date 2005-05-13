<?php
$GANGLIA_PATH = "/var/www/ganglia";

include_once "$GANGLIA_PATH/conf.php";
include_once "$GANGLIA_PATH/functions.php";

if ( !empty( $_GET ) ) {
        extract( $_GET );
}

class HTTPVariables {

	var $clustername, $metricname;

	function HTTPVariables() {

		global $HTTP_GET_VARS;

		$this->clustername = $HTTP_GET_VARS["c"] ? $HTTP_GET_VARS["c"] : null;
		$this->metricname = $HTTP_GET_VARS["m"] ? $HTTP_GET_VARS["m"] : null;
	}

	function getClusterName() {
		return $this->clustername;
	}

	function getMetricName() {
		return $this->metricname;
	}
}

class DataSource {

	var $data, $ip, $port;

	function DataSource( $ip = '127.0.0.1', $port = 8649 ) {
		$this->ip = $ip;
		$this->port = $port;
	}

	function getData() {

		$errstr;
		$errno = 0;
		$timeout = 3;

		$fp = fsockopen( $this->ip, $this->port, &$errno, &$errstr, $timeout );

		if( ! $fp ) {
			echo 'Unable to connect to '.$this->ip.':'.$this->port; // printf( 'Unable to connect to [%s:%.0f]', $this->ip, $this->port );
			return;
		}

		while ( !feof( $fp ) ) {
			
			$data .= fread( $fp, 16384 );
		}

		fclose( $fp );

		return $data;
	}
}

class DataGatherer {

	var $xmlhandler, $data;

	function DataGatherer() {

		$this->parser = xml_parser_create();
		$this->source = new DataSource();
		$this->xmlhandler = new TorqueXMLHandler();
		xml_set_element_handler( $this->parser, array( &$this->xmlhandler, 'startElement' ), array( &$this->xmlhandler, 'stopElement' ) );
	}

	function parseXML() {

		$src = &$this->source;
		$this->data = $src->getData();

		if ( !xml_parse( &$this->parser, $this->data ) ) {
			$error = sprintf( 'XML error: %s at %d', xml_error_string( xml_get_error_code( &$this->parser ) ), xml_get_current_line_number( &$this->parser ) );
			// die( $error );
		}
	}

}

class TorqueXMLHandler {

	function TorqueXMLHandler() {
	}

	function startElement( $parser, $name, $attrs ) {

		if ( $attrs[TN] ) {

			// Ignore dead metrics. Detect and mask failures.
			if ( $attrs[TN] > $attrs[TMAX] * 4 )
				return;
		}

		$jobs = array();
		$jobid = null;

		printf( '%s=%s', $attrs[NAME], $attrs[VAL] );

		sscanf( $attrs[NAME], 'TOGA-JOB-%d', $jobid );

		if( $jobid ) {

			if( !isset( $jobs[$jobid] ) )
				$jobs[$jobid] = array();

			$fields = explode( ' ', $attrs[VAL] );

			foreach( $fields as $f ) {
				$togavalues = explode( '=', $f );

				foreach( $togavalues as $toganame => $togavalue ) {

					if( $toganame == 'nodes' ) {

						$nodes = explode( ';', $togavalue );

						foreach( $nodes as $node ) {

							// Doe iets koels met $node
						}

					}

					$jobs[$toganame] = $togavalue;

				}
			}

		}
	}

	function stopElement( $parser, $name ) {
	}
}

class Node {

	var $img;

	function Node() {

		$this->img = new NodeImg();
	}
}

class NodeImg {

	var $image;

	function NodeImg( $image = null ) {

		$imageWidth = 100;
		$imageHeight = 100;

		if( !$image ) {
			$this->image = imageCreate( $imageWidth, $imageHeight );
				// or die( 'Cannot initialize new image stream: is GD installed?' );
		} else {
			$this->image = $image;
		}

		$background_color = imageColorAllocate( $this->image, 255, 255, 255 );
		$black_color = imageColorAllocate( $this->image, 0, 0, 0 );
		imageRectangle( $this->image, 0, 0, $imageWidth-1, $imageHeight-1, $black_color );
	}

	function colorHex( $color ) {
	
		$my_color = imageColorAllocate( $this->image, hexdec( substr( $color, 0, 2 )), hexdec( substr( $color, 2, 4 )), hexdec( substr( $color, 4, 6 )) );

		return $my_color;
	}

	function drawNode( $x, $y, &$queuecolor, $load, &$jobcolor ) {

		// Convert Ganglias Hexadecimal load color to a Decimal one
		$my_loadcolor = $this->colorHex( load_color($load) );

		imageFilledRectangle( $this->image, $x, $y, $x+12, $y+12, $queuecolor );
		imageFilledRectangle( $this->image, $x+2, $y+2, $x+10, $y+10, $my_loadcolor );
		//imageFilledEllipse( $this->image, ($x+9)/2, ($y+9)/2, 6, 6, $jobcolor );
	}

	function drawImage() {

		$queue_color = imageColorAllocate( $this->image, 0, 102, 304 );
		$job_color = imageColorAllocate( $this->image, 204, 204, 0 );

		$this->drawNode( 1, 1, $queue_color, 0.1, $job_color );
		header( 'Content-type: image/png' );
		imagePNG( $this->image );
		imageDestroy( $this->image );
	}
}

//$my_node = new NodeImg();
//$my_node->drawImage();

$my_data = new DataGatherer();
$my_data->parseXML();
?>
