<CENTER>
</FORM>
<SCRIPT LANGUAGE="javascript" SRC="ts_picker.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" SRC="libtoga.js"></SCRIPT>
<SCRIPT LANGUAGE="javascript">

	function setSearchTimestamps() {

		document.archive_search_form.start_from_time.value = document.archive_search_form.start_from_pick.value; 
		document.archive_search_form.start_to_time.value = document.archive_search_form.start_to_pick.value;
		document.archive_search_form.end_from_time.value = document.archive_search_form.end_from_pick.value; 
		document.archive_search_form.end_to_time.value = document.archive_search_form.end_to_pick.value;
	}

	function setPeriodTimestamps() {

		document.archive_search_form.start.value = document.archive_search_form.period_start_pick.value; 
		document.archive_search_form.stop.value = document.archive_search_form.period_stop_pick.value;
	}

</SCRIPT>

<FORM NAME="archive_search_form" ACTION="./">

<INPUT TYPE="hidden" NAME="c" VALUE="{cluster}">
<INPUT TYPE="hidden" NAME="view" VALUE="search">

<BR><BR>

<TABLE WIDTH="90%">

<TR>
  <TD CLASS=title COLSPAN="2">
  <B>Search job archive</B>
  </TD>
</TR>

</TABLE>

<BR><BR>

<TABLE WIDTH="90%">

<TR>
  <TD></TD>
  <TD></TD>
  <TD></TD>
  <TD></TD>
  <TD></TD>
  <TD></TD>
</TR>

<TR>

  <TD>
  Id:
  </TD>
  <TD>
  <INPUT TYPE="TEXT" NAME="id" VALUE="{id_value}">
  </TD>

</TR>

<TR>

  <TD>
  User:
  </TD>
  <TD>
  <INPUT TYPE="TEXT" NAME="user" VALUE="{user_value}">
  </TD>

  <TD>
  Queue:
  </TD>
  <TD>
  <INPUT TYPE="TEXT" NAME="queue" VALUE="{queue_value}">
  </TD>

  <TD>
  Name:
  </TD>
  <TD>
  <INPUT TYPE="TEXT" NAME="name" VALUE="{name_value}">
  </TD>

</TR>

<TR>

  <TD></TD>
  <TD>
  Job <B>start</B> between
  </TD>

  <TD>
  From:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="start_from_time" VALUE="{start_from_value}">
  <INPUT TYPE="TEXT" NAME="start_from_pick" VALUE="{start_from_value}" DISABLED="TRUE"> <A HREF="javascript:show_calendar('document.archive_search_form.start_from_pick', document.archive_search_form.start_from_pick.value );"><IMG SRC="cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the timestamp"></a>
  </TD>
  
  <TD>
  To:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="start_to_time" VALUE="{start_to_value}">
  <INPUT TYPE="TEXT" NAME="start_to_pick" VALUE="{start_to_value}" DISABLED="TRUE"><a href="javascript:show_calendar('document.archive_search_form.start_to_pick', document.archive_search_form.start_to_pick.value );"> <img src="cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the timestamp"></a>
  </TD>
  
</TR>

<TR>

  <TD></TD>
  <TD>
  Job <B>end</B> between
  </TD>

  <TD>
  From:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="end_from_time" VALUE="{end_from_value}">
  <INPUT TYPE="TEXT" NAME="end_from_pick" VALUE="{end_from_value}" DISABLED="TRUE"> <A HREF="javascript:show_calendar('document.archive_search_form.end_from_pick', document.archive_search_form.end_from_pick.value );"><IMG SRC="cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the timestamp"></a>
  </TD>
  
  <TD>
  To:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="end_to_time" VALUE="{end_to_value}">
  <INPUT TYPE="TEXT" NAME="end_to_pick" VALUE="{end_to_value}" DISABLED="TRUE"><a href="javascript:show_calendar('document.archive_search_form.end_to_pick', document.archive_search_form.end_to_pick.value );"> <img src="cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the timestamp"></a>
  </TD>
  
</TR>

<TR>

  <TD></TD>
  <TD></TD>
  <TD></TD>
  <TD></TD>
  <TD>{form_error_msg}</TD>
  <TD>
  <INPUT TYPE="submit" VALUE="Search archive" onClick="setSearchTimestamps();">
  </TD>

</TR>

</TABLE>
</FORM>
<BR><BR>
<!-- START BLOCK : search_results -->
<SCRIPT TYPE="text/javascript" SRC="libtoga.js"></SCRIPT>

<INPUT TYPE="HIDDEN" NAME="sortby" VALUE="{sortby}">
<INPUT TYPE="HIDDEN" NAME="sortorder" VALUE="{sortorder}">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="{clustername}">
<INPUT TYPE="HIDDEN" NAME="queue" VALUE="{f_queue}">
<INPUT TYPE="HIDDEN" NAME="state" VALUE="{f_state}">
<INPUT TYPE="HIDDEN" NAME="user" VALUE="{f_user}">
<INPUT TYPE="HIDDEN" NAME="id" VALUE="{f_id}">
<INPUT TYPE="HIDDEN" NAME="filterorder" VALUE="{f_order}">

<TABLE WIDTH="90%" CELLPADDING="8" CELLSPACING="3" BORDER=0>
<TR CLASS="toga">
<TH><B><A HREF="#" onClick="setSort( 'id' )">Id</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'state' )">State</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'user' )">User</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'queue' )">Queue</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'name' )">Name</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'req_cpu' )">Req. CPU time</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'req_mem' )">Req. Memory</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'nodes' )">Nodes</A>/<A HREF="#" onClick="setSort( 'cpus' )">Cpus</A></
B></TH>
<TH><B><A HREF="#" onClick="setSort( 'start' )">Started</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'finished' )">Finished</A></B></TH>
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
    <TD>{finished}</TD>
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
   </FONT><BR>
   <FONT SIZE="-1">
    <INPUT TYPE="HIDDEN" NAME="start" VALUE="{start}">
    <INPUT TYPE="HIDDEN" NAME="stop" VALUE="{stop}">
    Set graph timeperiod from <INPUT TYPE="text" NAME="period_start_pick" VALUE="{start}" SIZE=12 ALT="Start time"><a href="javascript:show_calendar('document.search_form.period_start_pick', document.search_form.period_start_pick.value);"><img src="cal.gif" width="16" height="16" border="0"></a> to <INPUT TYPE="text" NAME="period_stop_pick" VALUE="{stop}" SIZE=12 ALT="Stop time"><a href="javascript:show_calendar('document.toga_form.period_stop_pick', document.search_form.period_stop_pick.value);"><img src="cal.gif" width="16" height="16" border="0"></a><INPUT TYPE="submit" onClick="setPeriodTimestamps();" VALUE="Refresh graphs">
   </FONT>
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
<!-- END BLOCK : search_results -->
</CENTER>
