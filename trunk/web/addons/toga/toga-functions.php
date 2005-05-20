<?php
$GANGLIA_PATH = "/var/www/ganglia";

include_once "$GANGLIA_PATH/conf.php";
include_once "$GANGLIA_PATH/functions.php";
include_once "$GANGLIA_PATH/ganglia.php";

// Set cluster context so that Ganglia will
// provide us with the correct metrics array
//
global $context;
$context = 'cluster';

include_once "$GANGLIA_PATH/get_ganglia.php";

// If php is compiled without globals
//
if ( !empty( $_GET ) ) {
        extract( $_GET );
}

// Ganglia's array of host metrics
//
global $metrics;

//print_r($metrics);

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

		if( !$fp ) {
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

	var $xmlhandler, $data, $httpvars;

	function DataGatherer() {

		$this->parser = xml_parser_create();
		$this->source = new DataSource();
		$this->httpvars = new HTTPVariables();
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

	function printInfo() {
		$handler = $this->xmlhandler;
		$handler->printInfo();
	}

	function getNodes() {
		$handler = $this->xmlhandler;
		return $handler->getNodes();
	}

	function getJobs() {
		$handler = $this->xmlhandler;
		return $handler->getJobs();
	}

}

class TorqueXMLHandler {

	var $clusters, $heartbeat, $nodes, $jobs;

	function TorqueXMLHandler() {
		$jobs = array();
		$clusters = array();
		$nodes = array();
		$heartbeat = array();
	}

	function gotNode( $hostname, $location = 'unspecified', $jobid = null ) {

		$nodes = &$this->nodes;

		if( !isset( $nodes[$hostname] ) ) {

			$nodes[$hostname] = new Node( $hostname );
		}

		if( $location ) {

			$nodes[$hostname]->setLocation( $location );
		}

		if( $jobid ) {
			$nodes[$hostname]->addJob( $jobid );
		}
	}

	function startElement( $parser, $name, $attrs ) {

		$jobs = &$this->jobs;

		if ( $attrs[TN] ) {

			// Ignore dead metrics. Detect and mask failures.
			if ( $attrs[TN] > $attrs[TMAX] * 4 )
				return;
		}

		$jobid = null;

		// printf( '%s=%s', $attrs[NAME], $attrs[VAL] );

		if( $name == 'CLUSTER' ) {

			$clustername = $attrs[VAL];

			if( !isset( $clusters[$clustername] ) )
				$clusters[$clustername] = array();

		} else if( $name == 'HOST' ) {

			$hostname = $attrs[NAME];
			$location = $attrs[LOCATION];

			$this->gotNode( $hostname, $location, null );

		} else if( $name == 'METRIC' and strstr( $attrs[NAME], 'TOGA' ) ) {

			if( strstr( $attrs[NAME], 'TOGA-HEARTBEAT' ) ) {

				$heartbeat['time'] = $attrs[VAL];
				//printf( "heartbeat %s\n", $heartbeat['time'] );

			} else if( strstr( $attrs[NAME], 'TOGA-JOB' ) ) {

				sscanf( $attrs[NAME], 'TOGA-JOB-%d', $jobid );

				//printf( "jobid %s\n", $jobid );

				if( !isset( $jobs[$jobid] ) )
					$jobs[$jobid] = array();

				$fields = explode( ' ', $attrs[VAL] );

				foreach( $fields as $f ) {
					$togavalues = explode( '=', $f );

					$toganame = $togavalues[0];
					$togavalue = $togavalues[1];

					//printf( "\t%s\t= %s\n", $toganame, $togavalue );

					if( $toganame == 'nodes' ) {

						if( !isset( $jobs[$jobid][$toganame] ) )
							$jobs[$jobid][$toganame] = array();

						$nodes = explode( ';', $togavalue );

						foreach( $nodes as $node ) {

							$hostname = $node.'.'.$jobs[$jobid][domain];
							$jobs[$jobid][$toganame][] = $hostname;
							$this->gotNode( $hostname, null, $jobid );
						}

					} else {

						$jobs[$jobid][$toganame] = $togavalue;
					}
				}
			}
		}
	}

	function stopElement( $parser, $name ) {
	}

	function printInfo() {

		$jobs = &$this->jobs;

		printf( "---jobs---\n" );

		foreach( $jobs as $jobid => $job ) {

			printf( "job %s\n", $jobid );

			if( isset( $job[nodes] ) ) {

				foreach( $job[nodes] as $node ) {

					$mynode = $this->nodes[$node];
					$hostname = $mynode->getHostname();
					$location = $mynode->getLocation();

					printf( "\t- node %s\tlocation %s\n", $hostname, $location );
					//$this->nodes[$hostname]->setLocation( "hier draait job ".$jobid );
				}
			}
		}

		printf( "---nodes---\n" );

		$nodes = &$this->nodes;

		foreach( $nodes as $node ) {

			$hostname = $node->getHostname();
			$location = $node->getLocation();
			$jobs = implode( ' ', $node->getJobs() );
			printf( "* node %s\tlocation %s\tjobs %s\n", $hostname, $location, $jobs );
		}
	}

	function getNodes() {
		return $this->nodes;
	}

	function getJobs() {
		return $this->jobs;
	}
}

class Node {

	var $img, $hostname, $location, $jobs;
	var $x, $y;

	function Node( $hostname ) {

		$this->hostname = $hostname;
		//$this->img = new NodeImg();
		$this->jobs = array();
	}

	function addJob( $jobid ) {
		$jobs = &$this->jobs;

		$jobs[] = $jobid;
	}

	function setLocation( $location ) {
		$this->location = $location;
	}

	function setHostname( $hostname ) {
		$this->hostname = $hostname;
	}

	function setCpus( $cpus ) {
		$this->cpus = $cpus;
	}

	function getHostname() {
		return $this->hostname;
	}

	function getLocation() {
		return $this->location;
	}

	function getCpus() {
		return $this->cpus;
	}

	function getJobs() {
		return $this->jobs;
	}

	function setCoords( $x, $y ) {
		$myimg = $this->img;
		$myimg->setCoords( $x, $y );
	}

	function setImage( $image ) {
		$myimg = $this->img;
		$myimg->setImage( &$image );
	}

	function draw() {
		global $metrics;
	
		$myimg = $this->img;

		$cpus = $metrics[$this->hostname]["cpu_num"][VAL];
		if (!$cpus) $cpus=1;
		$load_one = $metrics[$this->hostname]["load_one"][VAL];
		$load = ((float) $load_one)/$cpus;

		$myimg->setLoad( $load );
		$myimg->draw();
	}
}

class NodeImage {

	var $image, $x, $y, $hostname;

	function NodeImage( $image, $x, $y ) {

		$this->image = $image;
		$this->x = $x;
		$this->y = $y;
	}

	function setCoords( $x, $y ) {

		$this->x = $x;
		$this->y = $y;
	}

	function colorHex( $color ) {
	
		$my_color = imageColorAllocate( $this->image, hexdec( substr( $color, 0, 2 )), hexdec( substr( $color, 2, 2 )), hexdec( substr( $color, 4, 2 )) );

		return $my_color;
	}

	function setImage( $image ) {
		$this->image = $image;
	}

	function setLoad( $load ) {
		$this->load = $load;
	}

	function setHostname( $hostname ) {
		$this->hostname = $hostname;
	}

	function drawNode( $load ) {

		if( !isset( $this->x ) or !isset( $this->y ) or !isset( $load ) ) {
			printf( "aborting\n" );
			printf( "x %d y %d load %f\n", $this->x, $this->y, $load );
			return;
		}

		$queue_color = imageColorAllocate( &$this->image, 0, 102, 304 );
		$job_color = imageColorAllocate( &$this->image, 204, 204, 0 );

		// Convert Ganglias Hexadecimal load color to a Decimal one
		$my_loadcolor = $this->colorHex( load_color($load) );

		imageFilledRectangle( $this->image, $this->x, $this->y, $this->x+11, $this->y+11, $queuecolor );
		imageFilledRectangle( $this->image, $this->x+1, $this->y+1, $this->x+10, $this->y+10, $my_loadcolor );

		// Een job markering?
		//imageFilledEllipse( $this->image, ($this->x+9)/2, ($this->y+9)/2, 6, 6, $jobcolor );
	}

	function draw() {

		global $metrics;

		$cpus = $metrics[$this->hostname][cpu_num][VAL];
		if (!$cpus) $cpus=1;
		$load_one = $metrics[$this->hostname][load_one][VAL];
		$load = ((float) $load_one)/$cpus;
		//printf( "hostname %s cpus %s load_one %s load %f\n", $this->hostname, $cpus, $load_one, $load );

		$this->drawNode( $load );
	}
}

class ClusterImage {

	var $dataget, $image;

	function ClusterImage() {
		$this->dataget = new DataGatherer();
	}

	function draw() {
		$mydatag = $this->dataget;
		$mydatag->parseXML();

		$max_width = 250;
		$node_width = 11;

		$nodes = $mydatag->getNodes();

		$nodes_nr = count( $nodes );
		$node_keys = array_keys( $nodes );

		$nodes_size = $nodes_nr*$node_width;
		$node_rows = 0;

		if( $nodes_size > $max_width ) {
			$nodes_per_row = ( (int) ($max_width/$node_width) );
		} else {
			$nodes_per_row = $nodes_size;
			$node_rows = 1;
		}

		if( $nodes_per_row < $nodes_nr ) {
			$node_rows = ( (int) ($nodes_nr/$nodes_per_row) );
			$node_rest = fmod( $nodes_nr, $nodes_per_row );
			//printf( "nodesnr %d noderest %f\n", $nodes_nr, $node_rest );
			if( $node_rest > 0 ) {
				$node_rows++;
				//printf( "noderows %d\n", $node_rows );
			}
		}

		//printf( "imagecreate: %dx%d", ($nodes_per_row*$node_width), ($node_rows*$node_width) );
		$image = imageCreateTrueColor( ($nodes_per_row*$node_width)+1, ($node_rows*$node_width)+1 );
		$colorwhite = imageColorAllocate( $image, 255, 255, 255 );
		imageFill( $image, 0, 0, $colorwhite );

		for( $n = 0; $n < $node_rows; $n++ ) {
			
			for( $m = 0; $m < $nodes_per_row; $m++ ) {
			
				$x = ($m * $node_width);
				$y = ($n * $node_width);

				$cur_node = ($n * $nodes_per_row) + ($m);
				$host = $node_keys[$cur_node];

				//printf( "host %s curnode %s ", $host, $cur_node );

				if( isset( $nodes[$host] ) and ($cur_node < $nodes_nr) ) {
					//printf( "image %s\n", $host );
					$node = new NodeImage( $image, $x, $y );
					$node->setHostname( $host );
					$node->draw();
					//$nodes[$host]->setCoords( $x, $y );
					//$nodes[$host]->setImage( &$image );
					//$nodes[$host]->draw();
					//$cur_node++;
				}
			}
		}
		
		header( 'Content-type: image/png' );
		imagePNG( $image );
		imageDestroy( $image );
	}
}

//$my_data = new DataGatherer();
//$my_data->parseXML();
//$my_data->printInfo();

$ic = new ClusterImage();
$ic->draw();
?>
