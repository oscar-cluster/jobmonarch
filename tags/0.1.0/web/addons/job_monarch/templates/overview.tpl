<BR><BR>

<CENTER>
<TABLE cellpadding="15">
<TR>

  <TD>

<TABLE ALIGN=CENTER class="overview" cellpadding="5">

<COL id="kol1">
<COL id="kol2">
<COL id="kol3">
<COL id="kol4">

<TR class="overview_header">
<TD>Batch</TD>
<TD>Jobs</TD>
<TD>Nodes</TD>
<TD>Cpus</TD>
</TR>

<TR class="overview_line">
<TD class="blue">
Capacity
</TD>
<TD class="blue">
</TD>
<TD class="blue">
{avail_nodes}
</TD>
<TD class="blue">
{avail_cpus}
</TD>
</TR>


<TR class="overview">
<TD class="red">
Running
</TD>
<TD class="red">
{running_jobs}
</TD>
<TD class="red">
{running_nodes}
</TD>
<TD class="red">
{running_cpus}
</TD>
</TR>

<TR class="overview_line">
<TD class="gray">
Queued
</TD>
<TD class="gray">
{queued_jobs}
</TD>
<TD class="gray">
{queued_nodes}
</TD>
<TD class="gray">
{queued_cpus}
</TD>
</TR>

<TR class="overview">
<TD class="brown">
Total
</TD>
<TD class="brown">
{total_jobs}
</TD>
<TD class="brown">
{total_nodes}
</TD>
<TD class="brown">
{total_cpus}
</TD>
</TR>

<TR class="overview">
<TD class="green">
Free
</TD>
<TD class="green">
</TD>
<TD class="green">
{free_nodes}
</TD>
<TD class="green">
{free_cpus}
</TD>
</TR>

<TR class="overview" id="selected">
<TD>
View
</TD>
<TD>
{view_jobs}
</TD>
<TD>
{view_nodes}
</TD>
<TD>
{view_cpus}
</TD>
</TR>

</TABLE>

  <TD ALIGN="CENTER"><CENTER>
    <IMG SRC="{clusterimage}"><BR>
<FONT class="footer">Last updated: {report_time}</FONT></CENTER>
  </TD>

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
<TR CLASS="monarch">
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
