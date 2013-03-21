<P>
All tasks of parallel and array jobs appear as a single &lsquo;job&rsquo;.
<BR></P>

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

<!-- START BLOCK : na_nodes -->
<TR class="overview">
<TD class="gray">
Unavailable
</TD>
<TD class="gray">
{na_jobs}
</TD>
<TD class="gray">
{na_nodes}
</TD>
<TD class="gray">
{na_cpus}
</TD>
</TR>
<!-- END BLOCK : na_nodes -->

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

<BR>
{rjqj_graph}

  <TD ALIGN="CENTER"><CENTER>
<!-- INCLUDESCRIPT BLOCK : ci_script -->
    <IMG SRC="{clusterimage}" USEMAP="#MONARCH_CLUSTER_BIG" BORDER="0">
    <MAP NAME="MONARCH_CLUSTER_BIG">
<!-- START BLOCK : node_clustermap -->
{node_area_map}
<!-- END BLOCK : node_clustermap -->
    </MAP>
    <BR>
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
<NOSCRIPT><P>[Sorting by column header requires JavaScript]<BR><BR></P></NOSCRIPT>

<INPUT TYPE="HIDDEN" NAME="sortby" VALUE="{sortby}">
<INPUT TYPE="HIDDEN" NAME="sortorder" VALUE="{sortorder}">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="{clustername}">
<INPUT TYPE="HIDDEN" NAME="queue" VALUE="{queue}">
<INPUT TYPE="HIDDEN" NAME="state" VALUE="{state}">
<INPUT TYPE="HIDDEN" NAME="owner" VALUE="{owner}">
<INPUT TYPE="HIDDEN" NAME="id" VALUE="{id}">
<INPUT TYPE="HIDDEN" NAME="filterorder" VALUE="{order}">

<TABLE WIDTH="100%" CELLPADDING="2" CELLSPACING="2" BORDER=0>
<TR CLASS="monarch">
<TH><B><A HREF="#" onClick="setSort( 'id' )" ALT="Jobid" TITLE="Jobid">Id</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'state' )" ALT="State" TITLE="State">S</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'owner' )">Owner</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'queue' )">Queue</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'name' )" ALT="Jobname" TITLE="Jobname">Name</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'req_cpu' )" ALT="Requested CPU Time (walltime)" TITLE="Requested CPU Time (walltime)">Req. CPU time</A></B></TH>
<!-- START BLOCK : column_header_req_mem -->
<TH><B><A HREF="#" onClick="setSort( 'req_mem' )" ALT="Requested Memory" TITLE="Requested Memory">Req. Memory</A></B></TH>
<!-- END BLOCK : column_header_req_mem -->
<TH><B><A HREF="#" onClick="setSort( 'nodes' )" ALT="Nodes" TITLE="Nodes">N</A>/<A HREF="#" onClick="setSort( 'cpus' )" ALT="Processors" TITLE="Processors">P</A></B></TH>
<!-- START BLOCK : column_header_queued -->
<TH><B><A HREF="#" onClick="setSort( 'queued' )">Queued</A></B></TH>
<!-- END BLOCK : column_header_queued -->
<!-- START BLOCK : column_header_nodes -->
<TH><B><A HREF="#" onClick="setSort( 'nodes' )" ALT="Nodes" TITLE="Nodes">Nodes</A></B></TH>
<!-- END BLOCK : column_header_nodes -->
<TH><B><A HREF="#" onClick="setSort( 'start' )">Started</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'runningtime' )">Runningtime</A></B></TH>
</TR>

<!-- START BLOCK : node -->
  <TR CLASS="{nodeclass}">
    <TD><A HREF="#" onClick="setFilter( 'id', '{id}' )">{id}</A></TD>
    <TD><A HREF="#" onClick="setFilter( 'state', '{state}' )" ALT="{fullstate}" TITLE="{fullstate}">{state}</A></TD>
    <TD><A HREF="#" onClick="setFilter( 'owner', '{owner}' )">{owner}</A></TD>
    <TD><A HREF="#" onClick="setFilter( 'queue', '{queue}' )">{queue}</A></TD>
    <TD ALT="{fulljobname}" TITLE="{fulljobname}">
<!-- START BLOCK : jobname_hint_start -->
    <FONT CLASS="jobname_hint">
<!-- END BLOCK : jobname_hint_start -->
    {name}
<!-- START BLOCK : jobname_hint_end -->
    </FONT>
<!-- END BLOCK : jobname_hint_end -->
    </TD>
    <TD>{req_cpu}</TD>
<!-- START BLOCK : column_req_mem -->
    <TD>{req_memory}</TD>
<!-- END BLOCK : column_req_mem -->
    <TD>{nodes}/{cpus}</TD>
<!-- START BLOCK : column_queued -->
    <TD>{queued}</TD>
<!-- END BLOCK : column_queued -->
<!-- START BLOCK : column_nodes -->
    <TD>{nodes_hostnames}</TD>
<!-- END BLOCK : column_nodes -->
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
