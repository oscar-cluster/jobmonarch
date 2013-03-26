<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><CENTER>
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

<?php if ("".(isset($this->scope["na_nodes"]) ? $this->scope["na_nodes"] : null)."" == "yes") {
?>
<TR class="overview">
<TD class="gray">
Unavailable
</TD>
<TD class="gray">
<?php echo $this->scope["na_jobs"];?>

</TD>
<TD class="gray">
<?php echo $this->scope["na_nodes"];?>

</TD>
<TD class="gray">
<?php echo $this->scope["na_cpus"];?>

</TD>
</TR>
<?php 
}?>


<TR class="overview_line">
<TD class="blue">
Capacity
</TD>
<TD class="blue">
</TD>
<TD class="blue">
<?php echo $this->scope["avail_nodes"];?>

</TD>
<TD class="blue">
<?php echo $this->scope["avail_cpus"];?>

</TD>
</TR>



<TR class="overview">
<TD class="red">
Allocated
</TD>
<TD class="red">
<?php echo $this->scope["running_jobs"];?>

</TD>
<TD class="red">
<?php echo $this->scope["running_nodes"];?>

</TD>
<TD class="red">
<?php echo $this->scope["running_cpus"];?>

</TD>
</TR>

<TR class="overview_line">
<TD class="gray">
Queued
</TD>
<TD class="gray">
<?php echo $this->scope["queued_jobs"];?>

</TD>
<TD class="gray">
<?php echo $this->scope["queued_nodes"];?>

</TD>
<TD class="gray">
<?php echo $this->scope["queued_cpus"];?>

</TD>
</TR>

<TR class="overview">
<TD class="brown">
Total
</TD>
<TD class="brown">
<?php echo $this->scope["total_jobs"];?>

</TD>
<TD class="brown">
<?php echo $this->scope["total_nodes"];?>

</TD>
<TD class="brown">
<?php echo $this->scope["total_cpus"];?>

</TD>
</TR>


<TR class="overview">
<TD class="green">
Unallocated
</TD>
<TD class="green">
</TD>
<TD class="green">
<?php echo $this->scope["free_nodes"];?>

</TD>
<TD class="green">
<?php echo $this->scope["free_cpus"];?>

</TD>
</TR>

<TR class="overview" id="selected">
<TD>
View
</TD>
<TD>
<?php echo $this->scope["view_jobs"];?>

</TD>
<TD>
<?php echo $this->scope["view_nodes"];?>

</TD>
<TD>
<?php echo $this->scope["view_cpus"];?>

</TD>
</TR>

</TABLE>

<BR>
<div id="monarchimage">
<?php echo $this->scope["rjqj_graph"];?>

</div>

  <TD ALIGN="CENTER"><CENTER>
<!-- INCLUDESCRIPT BLOCK : ci_script -->
    <div id="monarchimage">
    <IMG SRC="<?php echo $this->scope["clusterimage"];?>" USEMAP="#MONARCH_CLUSTER_BIG" BORDER="0">
    </div>
    <MAP NAME="MONARCH_CLUSTER_BIG">
<?php if ("".(isset($this->scope["nodes_clustermap"]) ? $this->scope["nodes_clustermap"] : null)."" == "yes") {
?>
<?php echo $this->scope["node_area_map"];?>

<?php 
}?>

    </MAP>
    <BR>
<FONT class="footer">Last updated: <?php echo $this->scope["report_time"];?></FONT></CENTER>
  </TD>

  </TD>
  <TD ALIGN="CENTER">
    <IMG SRC="<?php echo $this->scope["pie"];?>">
  </TD>
</TR>
</TABLE>

<BR>

<SCRIPT TYPE="text/javascript" SRC="libtoga.js"></SCRIPT>
<NOSCRIPT><P>[Sorting by column header requires JavaScript]<BR><BR></P></NOSCRIPT>

<INPUT TYPE="HIDDEN" NAME="sortby" VALUE="<?php echo $this->scope["sortby"];?>">
<INPUT TYPE="HIDDEN" NAME="sortorder" VALUE="<?php echo $this->scope["sortorder"];?>">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="<?php echo $this->scope["clustername"];?>">
<INPUT TYPE="HIDDEN" NAME="queue" VALUE="<?php echo $this->scope["queue"];?>">
<INPUT TYPE="HIDDEN" NAME="state" VALUE="<?php echo $this->scope["state"];?>">
<INPUT TYPE="HIDDEN" NAME="owner" VALUE="<?php echo $this->scope["owner"];?>">
<INPUT TYPE="HIDDEN" NAME="id" VALUE="<?php echo $this->scope["id"];?>">
<INPUT TYPE="HIDDEN" NAME="filterorder" VALUE="<?php echo $this->scope["order"];?>">

<TABLE WIDTH="100%" CELLPADDING="2" CELLSPACING="2" BORDER=0>
<TR CLASS="monarch">
<TH><B><A HREF="#" onClick="setSort( 'id' )" ALT="Jobid" TITLE="Jobid">Id</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'state' )" ALT="State" TITLE="State">S</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'owner' )">Owner</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'queue' )">Queue</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'name' )" ALT="Jobname" TITLE="Jobname">Name</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'req_cpu' )" ALT="Requested CPU Time (walltime)" TITLE="Requested CPU Time (walltime)">Req. CPU time</A></B></TH>
<?php if ("".(isset($this->scope["column_header_req_mem"]) ? $this->scope["column_header_req_mem"] : null)."" == "yes") {
?>
<TH><B><A HREF="#" onClick="setSort( 'req_mem' )" ALT="Requested Memory" TITLE="Requested Memory">Req. Memory</A></B></TH>
<?php 
}?>

<TH><B><A HREF="#" onClick="setSort( 'nodes' )" ALT="Nodes" TITLE="Nodes">N</A>/<A HREF="#" onClick="setSort( 'cpus' )" ALT="Processors" TITLE="Processors">P</A></B></TH>
<?php if ("".(isset($this->scope["column_header_queued"]) ? $this->scope["column_header_queued"] : null)."" == "yes") {
?>
<TH><B><A HREF="#" onClick="setSort( 'queued' )">Queued</A></B></TH>
<?php 
}?>

<?php if ("".(isset($this->scope["column_header_nodes"]) ? $this->scope["column_header_nodes"] : null)."" == "yes") {
?>
<TH WIDTH="11%"><B><A HREF="#" onClick="setSort( 'nodes' )" ALT="Nodes" TITLE="Nodes">Nodes</A></B></TH>
<?php 
}?>

<TH><B><A HREF="#" onClick="setSort( 'start' )">Started</A></B></TH>
<TH><B><A HREF="#" onClick="setSort( 'runningtime' )">Runningtime</A></B></TH>
</TR>

<?php 
$_loop0_data = (isset($this->scope["node_list"]) ? $this->scope["node_list"] : null);
if ($this->isTraversable($_loop0_data) == true)
{
	foreach ($_loop0_data as $tmp_key => $this->scope["-loop-"])
	{
		$_loop0_scope = $this->setScope(array("-loop-"));
/* -- loop start output */
?>
  <TR CLASS="<?php echo $this->scope["nodeclass"];?>">
    <TD><A HREF="#" onClick="setFilter( 'id', '<?php echo $this->scope["id"];?>' )"><?php echo $this->scope["id"];?></A></TD>
    <TD><A HREF="#" onClick="setFilter( 'state', '<?php echo $this->scope["state"];?>' )" ALT="<?php echo $this->scope["fullstate"];?>" TITLE="<?php echo $this->scope["fullstate"];?>"><?php echo $this->scope["state"];?></A></TD>
    <TD><A HREF="#" onClick="setFilter( 'owner', '<?php echo $this->scope["owner"];?>' )"><?php echo $this->scope["owner"];?></A></TD>
    <TD><A HREF="#" onClick="setFilter( 'queue', '<?php echo $this->scope["queue"];?>' )"><?php echo $this->scope["queue"];?></A></TD>
    <TD ALT="<?php echo $this->scope["fulljobname"];?>" TITLE="<?php echo $this->scope["fulljobname"];?>">
<?php if ("".(isset($this->scope["jobname_hint_start"]) ? $this->scope["jobname_hint_start"] : null)."" == "yes") {
?>
    <FONT CLASS="jobname_hint">
<?php 
}?>

    <?php echo $this->scope["name"];?>

<?php if ("".(isset($this->scope["jobname_hint_end"]) ? $this->scope["jobname_hint_end"] : null)."" == "yes") {
?>
    </FONT>
<?php 
}?>

    </TD>
    <TD><?php echo $this->scope["req_cpu"];?></TD>
<?php if ("".(isset($this->scope["column_req_mem"]) ? $this->scope["column_req_mem"] : null)."" == "yes") {
?>
    <TD><?php echo $this->scope["req_memory"];?></TD>
<?php 
}?>

    <TD><?php echo $this->scope["nodes"];?>/<?php echo $this->scope["cpus"];?></TD>
<?php if ("".(isset($this->scope["column_queued"]) ? $this->scope["column_queued"] : null)."" == "yes") {
?>
    <TD><?php echo $this->scope["queued"];?></TD>
<?php 
}?>

<?php if ("".(isset($this->scope["column_nodes"]) ? $this->scope["column_nodes"] : null)."" == "yes") {
?>
    <TD><?php echo $this->scope["nodes_hostnames"];?></TD>
<?php 
}?>

    <TD><?php echo $this->scope["started"];?></TD>
    <TD><?php echo $this->scope["runningtime"];?></TD>
  </TR>
<?php 
/* -- loop end output */
		$this->setScope($_loop0_scope, true);
	}
}
?>

</TABLE>
</CENTER>

<?php if ("".(isset($this->scope["showhosts"]) ? $this->scope["showhosts"] : null)."" == "yes") {
?>
<TABLE BORDER="0" WIDTH="100%">
<TR>
  <TD CLASS=title COLSPAN="2">
  <FONT SIZE="-1">
  Show Hosts:
  yes<INPUT type=radio name="sh" value="1" OnClick="toga_form.submit();" <?php echo $this->scope["checked1"];?>>
  no<INPUT type=radio name="sh" value="0" OnClick="toga_form.submit();" <?php echo $this->scope["checked0"];?>>
  </FONT>
  |
  job <strong><?php echo $this->scope["id"];?></strong> metric <strong><?php echo $this->scope["metric"];?></strong>
  |
   <FONT SIZE="-1">
   Columns&nbsp;&nbsp;<?php echo $this->scope["cols_menu"];?>

   </FONT>
  </TD>
</TR>
   
</TABLE>

<CENTER>
<TABLE>
<TR>
<?php 
$_loop1_data = (isset($this->scope["sorted_list"]) ? $this->scope["sorted_list"] : null);
if ($this->isTraversable($_loop1_data) == true)
{
	foreach ($_loop1_data as $tmp_key => $this->scope["-loop-"])
	{
		$_loop1_scope = $this->setScope(array("-loop-"));
/* -- loop start output */
?>
<?php echo $this->scope["metric_image"];
echo $this->scope["br"];?>

<?php 
/* -- loop end output */
		$this->setScope($_loop1_scope, true);
	}
}
?>

</TR>
</TABLE>

<p>
(Nodes colored by 1-minute load) | <A HREF="../../node_legend.html" ALT="Node Image egend">Legend</A>

</CENTER>

<?php 
}?>

<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>