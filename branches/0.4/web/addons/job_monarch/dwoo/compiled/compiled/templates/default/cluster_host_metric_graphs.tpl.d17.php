<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><center>
<table id=graph_sorted_list>
<tr>
<?php 
$_fh2_data = (isset($this->scope["sorted_list"]) ? $this->scope["sorted_list"] : null);
if ($this->isTraversable($_fh2_data) == true)
{
	foreach ($_fh2_data as $this->scope['host'])
	{
/* -- foreach start output */
?>
<?php echo $this->scope["host"]["metric_image"];
echo $this->scope["host"]["br"];?>

<?php 
/* -- foreach end output */
	}
}?>

</tr>
</table>

<?php echo $this->scope["overflow_list_header"];?>

<?php 
$_fh3_data = (isset($this->scope["overflow_list"]) ? $this->scope["overflow_list"] : null);
if ($this->isTraversable($_fh3_data) == true)
{
	foreach ($_fh3_data as $this->scope['host'])
	{
/* -- foreach start output */
?>
<?php echo $this->scope["host"]["metric_image"];
echo $this->scope["host"]["br"];?>

<?php 
/* -- foreach end output */
	}
}?>

<?php echo $this->scope["overflow_list_footer"];?>


<?php if (((isset($this->scope["node_legend"]) ? $this->scope["node_legend"] : null) !== null)) {
?>
<p>
(Nodes colored by 1-minute load) | <a href="./node_legend.html">Legend</A>
</p>
<?php 
}?>

</center>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>