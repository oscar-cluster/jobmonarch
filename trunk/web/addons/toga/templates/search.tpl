<CENTER>
</FORM>
<SCRIPT LANGUAGE="javascript" SRC="ts_picker.js"></SCRIPT>
<SCRIPT LANGUAGE="javascript">

	function setHiddenTimestamps() {

		document.archive_search_form.start_from_time.value = document.archive_search_form.start_from_pick.value; 
		document.archive_search_form.start_to_time.value = document.archive_search_form.start_to_pick.value;
		document.archive_search_form.end_from_time.value = document.archive_search_form.end_from_pick.value; 
		document.archive_search_form.end_to_time.value = document.archive_search_form.end_to_pick.value;
		alert( "poep gezet" );
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
  <INPUT TYPE="submit" VALUE="Search archive" onClick="setHiddenTimestamps();">
  </TD>

</TR>

</TABLE>
</FORM>
<BR><BR>
<!-- INCLUDE BLOCK : search_results -->
<BR><BR>
<!-- INCLUDE BLOCK : job_details -->
</CENTER>
