<BR><BR>

<CENTER>
<TABLE>
<TR>
  <TD ALIGN="CENTER">
    <IMG SRC="{clusterimage}"><BR>

<TABLE ALIGN=CENTER>
<TR>
<TD><FONT SIZE="-1" class=footer>Last updated:</FONT></TD><TD><FONT SIZE="-1" class=footer>{report_time}</TD>
</TR><TR>
<TD><FONT SIZE="-1" class=footer>Available:</FONT></TD><TD><FONT SIZE="-1" class=footer>{avail_nodes} nodes / {avail_cpus} cpu's</FONT></TD>
</TR><TR>
<TD><FONT SIZE="-1" class=footer>Usage:</FONT></TD><TD><FONT SIZE="-1" class=footer>{used_jobs} jobs - {used_nodes} nodes / {used_cpus} cpu's</FONT></TD>
</TR><TR>
<TD><FONT SIZE="-1" class=footer>View:</FONT></TD><TD><FONT SIZE="-1" class=footer>{view_jobs} jobs - {view_nodes} nodes / {view_cpus} cpu's</FONT></TD>
</TR>
</TABLE>
</FONT>

  </TD>
  <TD ALIGN="CENTER">
    <IMG SRC="{pie}">
  </TD>
</TR>
</TABLE>

<BR>

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
