<BR><BR>

<CENTER>
<TABLE>
<TR>
  <TD>
    <IMG SRC="{clusterimage}"><BR>
    reported: {heartbeat}
  </TD>
  <TD>
    <IMG SRC="{pie}">
  </TD>
</TR>
</TABLE>

<BR><BR>

<SCRIPT TYPE="text/javascript">
	function setSort( sortbyval ) {

		if( sortbyval != document.toga_form.sortby.value ) {

			document.toga_form.sortby.value = sortbyval;
			document.toga_form.sortorder.value = "desc";

		} else {

			if( document.toga_form.sortorder.value == "desc" )
				document.toga_form.sortorder.value = "asc";
			else if( document.toga_form.sortorder.value == "asc" )
				document.toga_form.sortorder.value = "desc";
		}

		document.forms['toga_form'].submit();
	}
</SCRIPT>

<FORM NAME="toga_form" ACTION="./" METHOD="GET">

<INPUT TYPE="HIDDEN" NAME="sortby" VALUE="{sortby}">
<INPUT TYPE="HIDDEN" NAME="sortorder" VALUE="{sortorder}">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="{clustername}">
<INPUT TYPE="HIDDEN" NAME="jobid" VALUE="{jobid}">

</FORM>

<TABLE WIDTH="80%" CELLPADDING="8" CELLSPACING="0" BORDER=1>
<TR>
<TD><B><A HREF="#" onClick="setSort( 'id' )">Id</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'state' )">State</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'user' )">User</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'queue' )">Queue</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'name' )">Name</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'req_cpu' )">Requested CPU time</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'req_mem' )">Requested Memory</A></B></TD>
<TD><B>Current <A HREF="#" onClick="setSort( 'nodes' )">Nodes</A>/<A HREF="#" onClick="setSort( 'cpus' )">Cpus</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'start' )">Started</A></B></TD>
<TD><B><A HREF="#" onClick="setSort( 'runningtime' )">Runningtime</A></B></TD>
</TR>

<!-- START BLOCK : node -->
  <TR>
    <TD><A HREF="">{id}</A></TD>
    <TD><A HREF="">{state}</A></TD>
    <TD><A HREF="{togasorted}&user={user}">{user}</A></TD>
    <TD><A HREF="{togasorted}&queue={queue}">{queue}</A></TD>
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
