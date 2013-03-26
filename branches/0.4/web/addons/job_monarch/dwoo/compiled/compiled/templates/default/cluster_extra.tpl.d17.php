<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
/* end template head */ ob_start(); /* template body */ ?><!-- A place to put custom HTML for the cluster view. -->
<?php if ((isset($this->scope["cluster"]) ? $this->scope["cluster"] : null) == "LISA Cluster") {
?> 
<?php echo Dwoo_Plugin_include($this, 'fairshare_pie.php', null, null, null, '_root', null);?>

<?php 
}?>

<!-- <img src="pie.php?<?php echo $this->scope["fairshare_pie_args"];?>" /> -->
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>