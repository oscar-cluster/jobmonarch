<?php
class HTTPVariables {

	var $clustername, $metricname;
	var $restvars, $httpvars;

	function HTTPVariables( $vars ) {

		$this->restvars = array();

		$this->clustername = $vars["c"] ? $vars["c"] : null;
		$this->metricname = $vars["m"] ? $vars["m"] : null;

		foreach( $vars as $httpvar => $httpval ) {
			
			if( $httpval ) {
				$this->restvars[$httpvar] = $httpval;
			}
		}
	}

	function getClusterName() {
		return $this->clustername;
	}

	function getMetricName() {
		return $this->metricname;
	}

	function getHttpVar( $var ) {
		if( isset( $this->restvars[$var] ) )
			return $this->restvars[$var];
		else
			return null;
	}
}

// Toga's conf
//
include_once "./conf.php";

global $GANGLIA_PATH, $SMALL_CLUSTERIMAGE_MAXWIDTH, $SMALL_CLUSTERIMAGE_NODEWIDTH, $DATA_SOURCE;

include_once "$GANGLIA_PATH/conf.php";
include_once "$GANGLIA_PATH/functions.php";
include_once "$GANGLIA_PATH/ganglia.php";

global $HTTP_GET_VARS;
$httpvars = new HTTPVariables( $HTTP_GET_VARS );

// Set cluster context so that Ganglia will
// provide us with the correct metrics array
//
global $context, $clustername;
$clustername = $httpvars->getClusterName();
$context = 'cluster';

include_once "$GANGLIA_PATH/get_ganglia.php";

// Ganglia's array of host metrics
//
global $metrics;

// If php is compiled without globals
//
if ( !empty( $_GET ) ) {
        extract( $_GET );
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

		global $DATA_SOURCE;
		
		$ds_fields = explode( ':', $DATA_SOURCE );
		$ds_ip = $ds_fields[0];
		$ds_port = $ds_fields[1];

		$this->source = new DataSource( $ds_ip, $ds_port );

		$this->parser = xml_parser_create();
		$this->httpvars = $httpvars;
		$this->xmlhandler = new TorqueXMLHandler();
		xml_set_element_handler( $this->parser, array( &$this->xmlhandler, 'startElement' ), array( &$this->xmlhandler, 'stopElement' ) );
	}

	function parseXML() {

		$src = &$this->source;
		$this->data = $src->getData();

		if ( !xml_parse( &$this->parser, $this->data ) )
			$error = sprintf( 'XML error: %s at %d', xml_error_string( xml_get_error_code( &$this->parser ) ), xml_get_current_line_number( &$this->parser ) );
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

	function getHeartbeat() {
		$handler = $this->xmlhandler;
		return $handler->getHeartbeat();
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

	function startElement( $parser, $name, $attrs ) {

		$jobs = &$this->jobs;
		$nodes = &$this->nodes;

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

			if( !isset( $this->nodes[$hostname] ) )
				$this->nodes[$hostname] = new NodeImage( $hostname );

		} else if( $name == 'METRIC' and strstr( $attrs[NAME], 'TOGA' ) ) {

			if( strstr( $attrs[NAME], 'TOGA-HEARTBEAT' ) ) {

				$this->heartbeat['time'] = $attrs[VAL];
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

						$mynodes = explode( ';', $togavalue );

						foreach( $mynodes as $node )

							$jobs[$jobid][$toganame][] = $node;
					} else {

						$jobs[$jobid][$toganame] = $togavalue;
					}
				}

				if( isset( $jobs[$jobid][domain] ) and isset( $jobs[$jobid][nodes] ) ) {
			
					$nr_nodes = count( $jobs[$jobid][nodes] );
			
					foreach( $jobs[$jobid][nodes] as $node ) {

						$host = $node.'.'.$jobs[$jobid][domain];
				
						if( !isset( $this->nodes[$host] ) )
							$my_node = new NodeImage( $host );
						else
							$my_node = $this->nodes[$host];

						if( !$my_node->hasJob( $jobid ) )

							if( isset( $jobs[$jobid][ppn] ) )
								$my_node->addJob( $jobid, ((int) $jobs[$jobid][ppn]) );
							else
								$my_node->addJob( $jobid, 1 );

						$this->nodes[$host] = $my_node;
					}
				}
			}
		}
		$this->jobs = $jobs;
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

	function getHeartbeat() {
		return $this->heartbeat['time'];
	}
}

class NodeImage {

	var $image, $x, $y, $hostname, $jobs, $tasks;

	function NodeImage( $hostname ) {

		$this->jobs = array();
		//$this->image = $image;
		//$this->x = $x;
		//$this->y = $y;
		$this->tasks = 0;
		$this->hostname = $hostname;
		$this->cpus = $this->determineCpus();
	}

	function addJob( $jobid, $cpus ) {
		$jobs = &$this->jobs;

		$jobs[] = $jobid;
		$this->jobs = $jobs;

		$this->addTask( $cpus );
	}

	function hasJob( $jobid ) {

		$jobfound = 0;

		if( count( $this->jobs ) > 0 )
			foreach( $this->jobs as $job )

				if( $job == $jobid )
					$jobfound = 1;

		return $jobfound;
	}

	function addTask( $cpus ) {

		$this->tasks = $this->tasks + $cpus;
	}

	function setImage( $image ) {

		$this->image = $image;
	}

	function setCoords( $x, $y ) {

		$this->x = $x;
		$this->y = $y;
	}

	function colorHex( $color ) {
	
		$my_color = imageColorAllocate( $this->image, hexdec( substr( $color, 0, 2 )), hexdec( substr( $color, 2, 2 )), hexdec( substr( $color, 4, 2 )) );

		return $my_color;
	}

	function setLoad( $load ) {
		$this->load = $load;
	}

	function setHostname( $hostname ) {
		$this->hostname = $hostname;
	}

	function getJobs() {
		return $this->jobs;
	}

	function draw() {

		global $SMALL_CLUSTERIMAGE_NODEWIDTH, $JOB_NODE_MARKING_ALLCPUS, $JOB_NODE_MARKING_SINGLECPU;

		$this->load = $this->determineLoad();

		if( !isset( $this->image ) or !isset( $this->x ) or !isset( $this->y ) ) {
			printf( "aborting\n" );
			printf( "x %d y %d load %f\n", $this->x, $this->y, $load );
			return;
		}

		$black_color = imageColorAllocate( $this->image, 0, 0, 0 );

		// Convert Ganglias Hexadecimal load color to a Decimal one
		//
		$load = $this->determineLoad();	
		$my_loadcolor = $this->colorHex( load_color($load) );

		$size = $SMALL_CLUSTERIMAGE_NODEWIDTH;

		imageFilledRectangle( $this->image, $this->x, $this->y, $this->x+($size), $this->y+($size), $black_color );
		imageFilledRectangle( $this->image, $this->x+1, $this->y+1, $this->x+($size-1), $this->y+($size-1), $my_loadcolor );

		$nr_jobs = count( $this->jobs );

		$node_mark = null;

		if( count( $this->jobs ) > 0 )

			if( $this->tasks < $this->cpus )
				$node_mark = $JOB_NODE_MARKING_SINGLECPU;

			else if( $this->tasks == $this->cpus )
				$node_mark = $JOB_NODE_MARKING_ALLCPUS;

		if( $node_mark )
			imageString( $this->image, 1, $this->x+(($size/2)-2), $this->y+(($size/2)-3), $node_mark, $black_color );
	}

	function determineCpus() {

		global $metrics;

		$cpus = $metrics[$this->hostname][cpu_num][VAL];
		if (!$cpus) $cpus=1;

		return $cpus;
	}

	function determineLoad() {

		global $metrics;

		$load_one = $metrics[$this->hostname][load_one][VAL];
		$load = ((float) $load_one)/$this->cpus;

		return $load;
	}
}

class ClusterImage {

	var $dataget, $image, $clustername;

	function ClusterImage( $clustername, $data_gather ) {

		if( !isset( $data_gather ) )
			$this->dataget = new DataGatherer();
		else
			$this->dataget = $data_gather;

		$this->clustername = $clustername;
	}

	function draw() {

		global $SMALL_CLUSTERIMAGE_MAXWIDTH, $SMALL_CLUSTERIMAGE_NODEWIDTH;
	
		$mydatag = $this->dataget;
		$mydatag->parseXML();

		//$max_width = 250;
		//$node_width = 11;

		$max_width = $SMALL_CLUSTERIMAGE_MAXWIDTH;
		$node_width = $SMALL_CLUSTERIMAGE_NODEWIDTH;

		//printf( "cmaxw %s nmaxw %s", $SMALL_CLUSTERIMAGE_MAXWIDTH, $SMALL_CLUSTERIMAGE_NODEWIDTH );

		$nodes = $mydatag->getNodes();
		$nodes_hosts = array_keys( $nodes );

		$nodes_nr = count( $nodes );

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
				$host = $nodes_hosts[$cur_node];

				if( isset( $nodes[$host] ) ) {

					$nodes[$host]->setCoords( $x, $y );
					$nodes[$host]->setImage( $image );
					$nodes[$host]->draw();
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

//$ic = new ClusterImage( "LISA Cluster" );
//$ic->draw();
?>
