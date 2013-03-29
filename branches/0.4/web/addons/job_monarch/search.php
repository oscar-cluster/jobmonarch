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

include_once "./dwoo/dwooAutoload.php";

global $dwoo;

global $clustername, $m, $metric;

function validateFormInput() {
    global $clustername, $dwoo, $id, $owner, $name, $start_from_time, $start_to_time, $queue;
    global $end_from_time, $end_to_time, $period_start, $period_stop, $tpl_data;

    $error = false;
    $error_msg = "<FONT COLOR=\"red\"><B>";
    $show_msg = 0;

    $none_set = 0;

    if( $id == '' and $owner== '' and $name == '' and $start_from_time == '' and $start_to_time == '' and $queue == '' and $end_from_time == '' and $end_to_time == '') {
        $error = true;
        $show_msg = 1;
        $error_msg .= "No search criteria set!";
    }

    if( !is_numeric($id) and !$error and $id != '') 
    {

        $error = true;
        $show_msg = 1;
        $error_msg .= "Id must be a number";
    }

    if( !$error and $period_start != '' ) 
    {
        $pstart_epoch = datetimeToEpoch( $period_start );
        if( $period_stop != '' ) 
        {

            $pstop_epoch = datetimeToEpoch( $period_stop );

            if( $pstart_epoch > $pstop_epoch ) {

                $show_msg = 1;
                $error = true;
                $error_msg .= "Graph timeperiod reset: start date/time can't be later than end";
                $period_stop = '';
                $period_start = '';
            } else if( $pstop_epoch == $pstart_epoch ) {

                $show_msg = 1;
                $error = true;
                $error_msg .= "Graph timeperiod reset: start and end date/time can't be the same";
                $period_stop = '';
                $period_start = '';
            }
        }
    }

    $error_msg .= "</B></FONT>";

    return array( $error_msg, $error);
}

function datetimeToEpoch( $datetime ) {

    $datetime_fields = explode( ' ', $datetime );

    $date = $datetime_fields[0];
    $time = $datetime_fields[1];

    $date_fields = explode( '-', $date );

    $days = $date_fields[0];
    $months = $date_fields[1];
    $years = $date_fields[2];

    $time_fields = explode( ':', $time );

    $hours = $time_fields[0];
    $minutes = $time_fields[1];
    $seconds = $time_fields[2];

    $timestamp = mktime( $hours, $minutes, $seconds, $months, $days, $years );

    return $timestamp;
}

function timeToEpoch( $time ) {

        $time_fields = explode( ':', $time );

        if( count($time_fields) == 3 ) {

                $hours = $time_fields[0];
                $minutes = $time_fields[1];
                $seconds = $time_fields[2];

        } else if( count($time_fields) == 2 ) {

                $hours = 0;
                $minutes = $time_fields[0];
                $seconds = $time_fields[1];

        } else if( count($time_fields) == 1 ) {

                $hours = 0;
                $minutes = 0;
                $seconds = $time_fields[0];
        }

        $myepoch = intval( $seconds + (intval( $minutes * 60 )) + (intval( $hours * 3600 )) );

        return $myepoch;
}

function sortJobs( $jobs, $nodes, $sortby, $sortorder ) {

        $sorted = array();

        $cmp = create_function( '$a, $b',
                "global \$sortby, \$sortorder;".

                "if( \$a == \$b ) return 0;".

                "if (\$sortorder==\"desc\")".
                        "return ( \$a < \$b ) ? 1 : -1;".
                "else if (\$sortorder==\"asc\")".
                        "return ( \$a > \$b ) ? 1 : -1;" );

        foreach( $jobs as $jobid => $jobattrs ) {

                        $state = $jobattrs['status'];
                        $owner = $jobattrs['owner'];
                        $queue = $jobattrs['queue'];
                        $name = $jobattrs['name'];
                        $req_cpu = $jobattrs['requested_time'];
                        $req_memory = $jobattrs['requested_memory'];

                        $mynodes = count( $nodes[$jobid] );

                        $ppn = (int) $jobattrs['ppn'] ? $jobattrs['ppn'] : 1;
                        $cpus = $mynodes * $ppn;
                        $start_time = (int) $jobattrs['start_timestamp'];
                        $stop_time = (int) $jobattrs['stop_timestamp'];
                        $runningtime = $stop_time - $start_time;

                        switch( $sortby ) {
                                case "id":
                                        $sorted[$jobid] = $jobid;
                                        break;

                                case "state":
                                        $sorted[$jobid] = $state;
                                        break;

                                case "owner":
                                        $sorted[$jobid] = $owner;
                                        break;

                                case "queue":
                                        $sorted[$jobid] = $queue;
                                        break;

                                case "name":
                                        $sorted[$jobid] = $name;
                                        break;

                                case "req_cpu":
                                        $sorted[$jobid] = timeToEpoch( $req_cpu );
                                        break;

                                case "req_mem":
                                        $sorted[$jobid] = $req_memory;
                                        break;

                                case "nodes":
                                        $sorted[$jobid] = $mynodes;
                                        break;

                                case "cpus":
                                        $sorted[$jobid] = $cpus;
                                        break;

                                case "start":
                                        $sorted[$jobid] = $start_time;
                                        break;

                case "finished":
                    $sorted[$jobid] = $stop_time;
                    break;

                                case "runningtime":
                                        $sorted[$jobid] = $runningtime;
                                        break;

                                default:
                                        break;

                        }
        }

        if( $sortorder == "asc" )
                arsort( $sorted );
        else if( $sortorder == "desc" )
                asort( $sorted );

        return array_keys( $sorted );
}

function makeSearchPage() {
    global $clustername, $dwoo, $id, $owner, $name, $start_from_time, $start_to_time, $queue;
    global $end_from_time, $end_to_time, $filter, $default_showhosts, $m, $hosts_up, $hc;
    global $period_start, $period_stop, $sortby, $sortorder, $COLUMN_REQUESTED_MEMORY;
    global $SEARCH_RESULT_LIMIT, $COLUMN_NODES, $metricname;

    $longtitle = "Batch Archive Search :: Powered by Job Monarch!";
    $title = "Batch Archive Search";

    makeHeader( 'search', $title, $longtitle );

    $tpl = new Dwoo_Template_File("templates/search.tpl");
    $tpl_data = new Dwoo_Data();

    $tpl_data->assign( "cluster", $clustername );
    $tpl_data->assign( "id_value", $id );
    $tpl_data->assign( "owner_value", $owner);
    $tpl_data->assign( "queue_value", $queue );
    $tpl_data->assign( "name_value", $name );
    $tpl_data->assign( "start_from_value", rawurldecode( $start_from_time ) );
    $tpl_data->assign( "start_to_value", rawurldecode( $start_to_time ) );
    $tpl_data->assign( "end_from_value", rawurldecode( $end_from_time ) );
    $tpl_data->assign( "end_to_value", rawurldecode( $end_to_time ) );

    list( $form_error_msg, $form_error ) = validateFormInput();

    if( $form_error == true )
    {
        $tpl_data->assign( "form_error_msg", $form_error_msg );
    } 
    else if( $form_error == false ) 
    {
        $tpl_data->assign( "search_results", "yes" );
        $tpl_data->assign( "sortby", $sortby);
        $tpl_data->assign( "sortorder", $sortorder);
        $tdb = new TarchDbase( "127.0.0.1" );
        if( $start_from_time ) $start_from_time = datetimeToEpoch( $start_from_time );
        if( $start_to_time ) $start_to_time = datetimeToEpoch( $start_to_time );
        if( $end_from_time ) $end_from_time = datetimeToEpoch( $end_from_time );
        if( $end_to_time ) $end_to_time = datetimeToEpoch( $end_to_time );
        $search_ids = $tdb->searchDbase( $id, $queue, $owner, $name, $start_from_time, $start_to_time, $end_from_time, $end_to_time );

        //print_r( $search_ids );
        if( ($tdb->resultcount) > (int) $SEARCH_RESULT_LIMIT ) {
       
            $tpl_data->assign( "form_error_msg", "Got " . $tdb->resultcount . " search results, output limited to last " . $SEARCH_RESULT_LIMIT . " jobs." );
        }

        $jobs = array();
        $nodes = array();

        $even = 1;

        foreach( $search_ids as $myid ) {

            $jobs[$myid] = $tdb->getJobArray( $myid );
            $nodes[$myid] = $tdb->getNodesForJob( $myid );
        }

        if( $COLUMN_REQUESTED_MEMORY ) {
            $tpl_data->assign( "column_header_req_mem", "yes" );
        }
        if( $COLUMN_NODES ) {
            $tpl_data->assign( "column_header_nodes", "yes" );
        }

        $sorted_search = sortJobs( $jobs, $nodes, $sortby, $sortorder );

        $node_loop = array();
        foreach( $sorted_search as $sortid ) {

            $job = $jobs[$sortid];
            $foundid = $job['id'];

            $node_list = array();
            $node_list["id"]= $job['id'];
            $node_list["state"]= $job['status'];
            $node_list["owner"]= $job['owner'];
            $node_list["queue"]= $job['queue'];
            $node_list["name"]= $job['name'];
            $node_list["req_cpu"]= makeTime( TimeToEpoch( $job['requested_time'] ) );

            if( $COLUMN_REQUESTED_MEMORY ) {
                $node_list["column_req_mem"] = "yes";
                $node_list["req_memory"]= $job['requested_memory'];
            }
            if( $COLUMN_NODES) {

                $job_nodes    = array();

                foreach( $nodes[$foundid] as $mynode )
                    $job_nodes[] = $mynode['hostname'];

                $node_list["column_nodes"] = "yes";
                $nodes_hostnames = implode( " ", $job_nodes );
                $node_list["nodes_hostnames"]= $nodes_hostnames;
            }

            $nodes_nr = count( $nodes[$foundid] );

            if( $even ) {

                $node_list["nodeclass"]= "even";
                $even = 0;
            } else {

                $node_list["nodeclass"]= "odd";
                $even = 1;
            }

            $ppn = (int) $job['ppn'] ? $job['ppn'] : 1;
            $cpus = $nodes_nr * $ppn;

            $node_list["nodes"]= $nodes_nr;
            $node_list["cpus"]= $cpus;

            $job_start = $job['start_timestamp'];
            $job_stop = $job['stop_timestamp'];
            $runningtime = intval( $job_stop - $job_start );
            $node_list["started"]= makeDate( $job_start );
            $node_list["finished"]= makeDate( $job_stop );
            $node_list["runningtime"]= makeTime( $runningtime );
            
            $node_loop[]=$node_list;
        }
        //print_r( $node_loop );
        $tpl_data->assign("node_list", $node_loop );

        if( count( $search_ids ) == 1 ) {

            $tpl_data->assign( "showhosts", "yes" );

            $showhosts = isset($sh) ? $sh : $default_showhosts;
            $tpl_data->assign("checked$showhosts", "checked");

            # Present a width list
            $cols_menu = "<SELECT NAME=\"hc\" OnChange=\"archive_search_form.submit();\">\n";

            $hostcols = ($hc) ? $hc : 4;

            foreach(range(1,25) as $cols) {
                $cols_menu .= "<OPTION VALUE=$cols ";
                if ($cols == $hostcols)
                    $cols_menu .= "SELECTED";
                $cols_menu .= ">$cols\n";
            }
            $cols_menu .= "</SELECT>\n";

            $tpl_data->assign("metric","$metricname $units");
            $tpl_data->assign("id", $id);
            # Host columns menu defined in header.php
            $tpl_data->assign("cols_menu", $cols_menu);

            if( $showhosts ) {

                if( !$period_start ) // Add an extra 10% to graphstart
                    $period_start = intval( $job_start - (intval( $runningtime * 0.10 ) ) );
                else
                    $period_start = datetimeToEpoch( $period_start );

                if( !$period_stop ) // Add an extra 10% to graphend
                    $period_stop = intval( $job_stop + (intval( $runningtime * 0.10 ) ) );
                else
                    $period_stop = datetimeToEpoch( $period_stop );

                #        $tpl_data->gotoBlock( "timeperiod" );

                #$tpl_data->assign("period_start", epochToDatetime( $period_start ) );
                #$tpl_data->assign("period_stop", epochToDatetime( $period_stop ) );

                $hosts_up = array();

                foreach( $nodes[$id] as $mynode )
                    $hosts_up[] = $mynode['hostname'];

                $sorted_hosts = array();

                foreach ($hosts_up as $host ) {
                    $cpus = $metrics[$host]["cpu_num"]['VAL'];
                    if (!$cpus) $cpus=1;
                    $load_one  = $metrics[$host]["load_one"]['VAL'];
                    $load = ((float) $load_one)/$cpus;
                    $host_load[$host] = $load;
                    $percent_hosts['load_color'.($load)] += 1;
                    if ($metricname=="load_one")
                        $sorted_hosts[$host] = $load;
                    else
                        $sorted_hosts[$host] = $metrics[$host][$metricname]['VAL'];
                }
                switch ($sort) {
                    case "descending":
                        arsort($sorted_hosts);
                        break;
                    case "by hostname":
                        ksort($sorted_hosts);
                        break;
                    default:
                    case "ascending":
                        asort($sorted_hosts);
                        break;
                }

                # First pass to find the max value in all graphs for this
                # metric. The $start,$end variables comes from get_context.php,
                # included in index.php.
                list($min, $max) = find_limits($sorted_hosts, $metricname);

                $sorted_loop = array();
                # Second pass to output the graphs or metrics.
                $i = 1;
                foreach ( $sorted_hosts as $host=>$value  ) {
                    $sorted_list = array();
                    $host_url = rawurlencode($host);
                    $cluster_url = rawurlencode($clustername);

                    $textval = "";
                    $val = $metrics[$host][$metricname];
                    $class = "metric";
                    $host_link="\"?j_view=host&c=$cluster_url&h=$host_url&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop\"";

                    if ($val['TYPE']=="timestamp" or $always_timestamp[$metricname]) {
                        $textval = date("r", $val['VAL']);
                    } elseif ($val['TYPE']=="string" or $val['SLOPE']=="zero" or $always_constant[$metricname] or ($max_graphs > 0 and $i > $max_graphs )) {
                        $textval = $val['VAL']." ".$val['UNITS'];
                    } else {
                        $graphargs = "z=small&c=$cluster_url&m=$metricname&h=$host_url&v=".$val['VAL']."&x=$max&n=$min&job_start=$job_start&job_stop=$job_stop&period_start=$period_start&period_stop=$period_stop&min=$min&max=$max";
                    }
                    if ($textval) {
                        $cell="<td class=$class>".  "<b><a href=$host_link>$host</a></b><br>".  "<i>$metricname:</i> <b>$textval</b></td>";
                    } else {
                        $cell="<td><a href=$host_link>".  "<img src=\"./graph.php?$graphargs\" ".  "alt=\"$host\" border=0></a></td>";
                    }

                    $sorted_list["metric_image"]= $cell;
                    if (! ($i++ % $hostcols) )
                         $sorted_list["br"]= "</tr><tr>";

                    $sorted_loop[]=$sorted_list;
                }
                $tpl_data->assign("sorted_list", $sorted_loop );
            }
        }

    }
    $dwoo->output($tpl, $tpl_data);
}
?>
