<P>
All tasks of parallel and array jobs appear as a single &lsquo;job&rsquo;.
<BR></P>

</FORM>

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

<div id="grid-example"></div>

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
