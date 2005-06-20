<CENTER>

<SCRIPT LANGUAGE="javascript" SRC="ts_picker.js"></SCRIPT>
<SCRIPT LANGUAGE="javascript">
function setSort( sortbyval ) {

        if( sortbyval != document.archive_search_form.sortby.value ) {

                document.archive_search_form.sortby.value = sortbyval;
                document.archive_search_form.sortorder.value = "asc";

        } else {

                if( document.archive_search_form.sortorder.value == "desc" )
                        document.archive_search_form.sortorder.value = "asc";
                else if( document.archive_search_form.sortorder.value == "asc" )
                        document.archive_search_form.sortorder.value = "desc";
        }

        document.forms['archive_search_form'].submit();
}

function setFilter( filtername, filterval ) {

        var myfilterorder = document.archive_search_form.elements['filterorder'].value;

        if( document.archive_search_form.elements[filtername] ) {
                document.archive_search_form.elements[filtername].value = filterval;
                if( myfilterorder != '')
                        myfilterorder = myfilterorder + "," + filtername;
                else
                        myfilterorder = filtername;

        }
        document.archive_search_form.elements['filterorder'].value = myfilterorder;

        //setTimeout( "document.forms['archive_search_form'].submit();", 1000 );

        document.forms['archive_search_form'].submit();
}
</SCRIPT>
<SCRIPT LANGUAGE="javascript">

	function setSearchTimestamps() {

		document.archive_search_form.start_from_time.value = document.archive_search_form.start_from_pick.value; 
		document.archive_search_form.start_to_time.value = document.archive_search_form.start_to_pick.value;
		document.archive_search_form.end_from_time.value = document.archive_search_form.end_from_pick.value; 
		document.archive_search_form.end_to_time.value = document.archive_search_form.end_to_pick.value;
	}

	function setPeriodTimestamps() {

		document.archive_search_form.period_start.value = document.archive_search_form.period_start_pick.value; 
		document.archive_search_form.period_stop.value = document.archive_search_form.period_stop_pick.value;
	}

</SCRIPT>

<FORM NAME="archive_search_form" ACTION="./">

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
  Job <B>start</B>
  </TD>

  <TD>
  From:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="start_from_time" VALUE="{start_from_value}">
  <INPUT TYPE="TEXT" NAME="start_from_pick" VALUE="{start_from_value}" DISABLED="TRUE"> 
  <A HREF="javascript:show_calendar('document.archive_search_form.start_from_pick', document.archive_search_form.start_from_pick.value );">
  <IMG SRC="cal.gif" width="16" height="16" border="0" title="Click to select a date/time" alt="Click to select a date/time"></a> 
  <a href="#" onClick="document.archive_search_form.start_from_pick.value=''" alt="Click here to clear field" title="Click here to clear field"><IMG SRC="redcross.jpg" BORDER=0></A>
  </TD>
  
  <TD>
  To:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="start_to_time" VALUE="{start_to_value}">
  <INPUT TYPE="TEXT" NAME="start_to_pick" VALUE="{start_to_value}" DISABLED="TRUE">
  <a href="javascript:show_calendar('document.archive_search_form.start_to_pick', document.archive_search_form.start_to_pick.value );"> 
  <img src="cal.gif" width="16" height="16" border="0" title="Click to select a date/time" alt="Click to select a date/time"></a> 
  <a href="#" onClick="document.archive_search_form.start_to_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
  <IMG SRC="redcross.jpg" BORDER=0></A>
  </TD>
  
</TR>

<TR>

  <TD></TD>
  <TD>
  Job <B>finish</B>
  </TD>

  <TD>
  From:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="end_from_time" VALUE="{end_from_value}">
  <INPUT TYPE="TEXT" NAME="end_from_pick" VALUE="{end_from_value}" DISABLED="TRUE"> 
  <A HREF="javascript:show_calendar('document.archive_search_form.end_from_pick', document.archive_search_form.end_from_pick.value );">
  <IMG SRC="cal.gif" width="16" height="16" border="0" title="Click to select a date/time" alt="Click to select a date/time"></a> 
  <a href="#" onClick="document.archive_search_form.end_from_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
  <IMG SRC="redcross.jpg" BORDER=0></A>
  </TD>
  
  <TD>
  To:
  </TD>
  <TD>
  <INPUT TYPE="HIDDEN" NAME="end_to_time" VALUE="{end_to_value}">
  <INPUT TYPE="TEXT" NAME="end_to_pick" VALUE="{end_to_value}" DISABLED="TRUE">
  <a href="javascript:show_calendar('document.archive_search_form.end_to_pick', document.archive_search_form.end_to_pick.value );"> 
  <img src="cal.gif" width="16" height="16" border="0" title="Click to select a date/time" alt="Click to select a date/time"></a> 
  <a href="#" onClick="document.archive_search_form.end_to_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
  <IMG SRC="redcross.jpg" BORDER=0></A>
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
<BR><BR>
<!-- START BLOCK : search_results -->

<INPUT TYPE="HIDDEN" NAME="sortby" VALUE="{sortby}">
<INPUT TYPE="HIDDEN" NAME="sortorder" VALUE="{sortorder}">
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
  yes<INPUT type=radio name="sh" value="1" OnClick="archive_search_form.submit();" {checked1}>
  no<INPUT type=radio name="sh" value="0" OnClick="archive_search_form.submit();" {checked0}>
  </FONT>
  |
  job <strong>{id}</strong> metric <strong>{metric}</strong>
  |
   <FONT SIZE="-1">
   Columns&nbsp;&nbsp;{cols_menu}
   </FONT><BR>
<FONT SIZE="-1">
    <INPUT TYPE="HIDDEN" NAME="period_start" VALUE="{period_start}">
    <INPUT TYPE="HIDDEN" NAME="period_stop" VALUE="{period_stop}">
    Graph timeperiod from 
    <INPUT TYPE="text" NAME="period_start_pick" VALUE="{period_start}" ALT="Start time" DISABLED="TRUE">
    <a href="javascript:show_calendar('document.archive_search_form.period_start_pick', document.archive_search_form.period_start_pick.value);" alt="Click to select a date/time" title="Click to select a date/time">
    <img src="cal.gif" width="16" height="16" border="0"></a> 
    <a href="#" onClick="javascript: document.archive_search_form.period_start_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
    <IMG SRC="redcross.jpg" BORDER=0></A>
    to <INPUT TYPE="text" NAME="period_stop_pick" VALUE="{period_stop}" ALT="Stop time" DISABLED="TRUE">
    <a href="javascript:show_calendar('document.archive_search_form.period_stop_pick', document.archive_search_form.period_stop_pick.value);" alt="Click to select a date/time" title="Click to select a date/time">
    <img src="cal.gif" width="16" height="16" border="0"></a> 
    <a href="#" onClick="javascript: document.archive_search_form.period_stop_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
    <IMG SRC="redcross.jpg" BORDER=0></A>
    <INPUT TYPE="submit" onClick="setPeriodTimestamps();" VALUE="Refresh graphs">
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

</CENTER>

<!-- END BLOCK : showhosts -->
<!-- END BLOCK : search_results -->
</CENTER>
