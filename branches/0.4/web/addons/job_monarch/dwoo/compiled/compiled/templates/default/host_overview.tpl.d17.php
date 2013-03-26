<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
/* end template head */ ob_start(); /* template body */ ?><br>
<table border="0" width="100%">

<tr>
 <td align="left" valign="TOP">

<img src="<?php echo $this->scope["node_image"];?>" class="noborder" height="60" width="30" title="<?php echo $this->scope["host"];?>"/>
<?php echo $this->scope["node_msg"];?>


<table border="0" width="100%">
<tr>
  <td colspan="2" class="title">Time and String Metrics</td>
</tr>

<?php 
$_fh0_data = (isset($this->scope["s_metrics_data"]) ? $this->scope["s_metrics_data"] : null);
if ($this->isTraversable($_fh0_data) == true)
{
	foreach ($_fh0_data as $this->scope['s_metric'])
	{
/* -- foreach start output */
?>
<tr>
 <td class="footer" width="30%"><?php echo $this->scope["s_metric"]["name"];?></td><td><?php echo $this->scope["s_metric"]["value"];?></td>
</tr>
<?php 
/* -- foreach end output */
	}
}?>


<tr><td>&nbsp;</td></tr>

<tr>
  <td colspan="2" class="title">Constant Metrics</td>
</tr>

<?php 
$_fh1_data = (isset($this->scope["c_metrics_data"]) ? $this->scope["c_metrics_data"] : null);
if ($this->isTraversable($_fh1_data) == true)
{
	foreach ($_fh1_data as $this->scope['c_metric'])
	{
/* -- foreach start output */
?>
<tr>
 <td class="footer" width="30%"><?php echo $this->scope["c_metric"]["name"];?></td><td><?php echo $this->scope["c_metric"]["value"];?></td>
</tr>
<?php 
/* -- foreach end output */
	}
}?>

</table>

 <hr />
<?php if (((isset($this->scope["extra"]) ? $this->scope["extra"] : null) !== null)) {
?>
<?php echo Dwoo_Plugin_include($this, "".(isset($this->scope["extra"]) ? $this->scope["extra"] : null)."", null, null, null, '_root', null);?>

<?php 
}?>

</td> 
</table>

<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>