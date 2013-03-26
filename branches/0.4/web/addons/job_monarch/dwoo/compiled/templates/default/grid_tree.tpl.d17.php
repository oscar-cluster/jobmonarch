<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><table border="0" width="100%">
<tr>
  <td class="title"><B><?php echo $this->scope["self"];?> Grid Tree</b></td>
</tr>
</table>

<div align="center">
<table cellspacing="5" cellpadding="5" border="1">
<tr>

<?php if (((isset($this->scope["parentgrid"]) ? $this->scope["parentgrid"] : null) !== null)) {
?>
<td align="center">
<table cellpadding="3" cellspacing="3" border="0">
<?php echo $this->scope["parents"];?>

</table>
</td>
<?php 
}?>

</tr>

<tr>
<td align="center">

<table cellpadding="3" cellspacing="3" border="0">
<tr>
 <td colspan="<?php echo $this->scope["n"];?>" class="self" align="center" style="border: thin solid rgb(47,47,47);">
  <?php echo $this->scope["self"];?>

 </td>
</tr>

<tr>
 <?php echo $this->scope["children"];?>

</tr>
</table>

</td>
</tr>

</table>
</div>

<p>
<hr />
<b>Legend:</b>
<table cellspacing="5" border="0">
<tr>
<td class="self" width="20">&nbsp;</td><td>This Grid.</td>
</tr>
<tr>
<td class="grid">&nbsp;</td><td>A Remote Grid.</td>
</tr>
</table>

<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>