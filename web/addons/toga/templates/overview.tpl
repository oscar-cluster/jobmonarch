<BR><BR>

<CENTER>
<TABLE>
<TR>
  <TD ALIGN="CENTER">
    <IMG SRC="{clusterimage}"><BR>
    {nodes_nr} nodes total: {jobs_nr} jobs with {cpus_nr} CPU's (at {report_time}).<BR>
    current view: {f_jobs_nr} jobs with {f_cpus_nr} CPU's{lag_msg}.
  </TD>
  <TD ALIGN="CENTER">
    <IMG SRC="{pie}">
  </TD>
</TR>
</TABLE>

<BR><BR>

<SCRIPT TYPE="text/javascript" SRC="ts_picker.js"></SCRIPT>
<SCRIPT TYPE="text/javascript">
	function setSort( sortbyval ) {

		if( sortbyval != document.toga_form.sortby.value ) {

			document.toga_form.sortby.value = sortbyval;
			document.toga_form.sortorder.value = "asc";

		} else {

			if( document.toga_form.sortorder.value == "desc" )
				document.toga_form.sortorder.value = "asc";
			else if( document.toga_form.sortorder.value == "asc" )
				document.toga_form.sortorder.value = "desc";
		}

		document.forms['toga_form'].submit();
	}

	function setFilter( filtername, filterval ) {

		//document.toga_form.id.value = '';
		//document.toga_form.queue.value = '';
		//document.toga_form.state.value = '';
		//document.toga_form.user.value = '';

		if( document.toga_form.elements[filtername] ) {
			document.toga_form.elements[filtername].value = filterval;
		}

		document.forms['toga_form'].submit();
	}
</SCRIPT>

<INPUT TYPE="HIDDEN" NAME="sortby" VALUE="{sortby}">
<INPUT TYPE="HIDDEN" NAME="sortorder" VALUE="{sortorder}">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="{clustername}">
<INPUT TYPE="HIDDEN" NAME="queue" VALUE="{f_queue}">
<INPUT TYPE="HIDDEN" NAME="state" VALUE="{f_state}">
<INPUT TYPE="HIDDEN" NAME="user" VALUE="{f_user}">
<INPUT TYPE="HIDDEN" NAME="id" VALUE="{f_id}">

<TABLE WIDTH="90%" CELLPADDING="8" CELLSPACING="3" BORDER=0>
<TR CLASS="toga">
<TH><B><A HREF="#" onClick="setSort( 'id' )">Id</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'state' )">State</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'user' )">User</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'queue' )">Queue</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'name' )">Name</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'req_cpu' )">Req. CPU time</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'req_mem' )">Req. Memory</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'nodes' )">Nodes</A>/<A HREF="#" onClick="setSort( 'cpus' )">Cpus</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'start' )">Started</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'runningtime' )">Runningtime</A></B></TH>
</TR>

<!-- START BLOCK : node -->
  <TR CLASS="{nodeclass}">
    <TD><A HREF="#" onClick="setFilter( 'id', '{id}' )">{id}</A></TD>
    <TD><A HREF="#" onClick="setFilter( 'state', '{state}' )">{state}</A></TD>
    <TD><A HREF="#" onClick="setFilter( 'user', '{user}' )">{user}</A></TD>
    <TD><A HREF="#" onClick="setFilter( 'queue', '{queue}' )">{queue}</A></TD>
    <TD>{name}</TD>
    <TD>{req_cpu}</TD>
    <TD>{req_memory}</TD>
    <TD>{nodes}/{cpus}</TD>
    <TD>{started}</TD>
    <TD>{runningtime}</TD>
  </TR>
<!-- END BLOCK : node -->
</TABLE>
</CENTER>

<!-- START BLOCK : showhosts -->
<TABLE BORDER="0" WIDTH="100%">
<TR>
  <TD CLASS=title COLSPAN="2">
  <FONT SIZE="-1">
  Show Hosts:
  yes<INPUT type=radio name="sh" value="1" OnClick="toga_form.submit();" {checked1}>
  no<INPUT type=radio name="sh" value="0" OnClick="toga_form.submit();" {checked0}>
  </FONT>
  |
  job <strong>{id}</strong> metric <strong>{metric}</strong>
  |
   <FONT SIZE="-1">
   Columns&nbsp;&nbsp;{cols_menu}
   </FONT><!-- <BR>
   <FONT SIZE="-1">
    Set graph timeperiod from <INPUT TYPE="text" NAME="start" VALUE="{start}" SIZE=12 ALT="Start time"><a href="javascript:show_calendar('document.toga_form.start', document.toga_form.start.value);"><img src="cal.gif" width="16" height="16" border="0"></a> to <INPUT TYPE="text" NAME="stop" VALUE="{stop}" SIZE=12 ALT="Stop time"><a href="javascript:show_calendar('document.toga_form.stop', document.toga_form.stop.value);"><img src="cal.gif" width="16" height="16" border="0"></a><INPUT TYPE="submit" VALUE="Refresh graphs">
   </FONT> -->
  </TD>
</TR>
   
</TABLE>

<CENTER>
<TABLE>
<TR>
<!-- START BLOCK : sorted_list -->
{metric_image}{br}
<!-- END BLOCK : sorted_list -->
</TR>
</TABLE>

<p>
(Nodes colored by 1-minute load) | <A HREF="../../node_legend.html" ALT="Node Image egend">Legend</A>

</CENTER>

<!-- END BLOCK : showhosts -->
