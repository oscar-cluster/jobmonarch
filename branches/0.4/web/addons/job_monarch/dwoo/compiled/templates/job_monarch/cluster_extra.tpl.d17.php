<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><HR>
<A HREF="./addons/job_monarch/?c=<?php echo $this->scope["cluster"];?>">
<IMG SRC="./addons/job_monarch/image.php?c=<?php echo $this->scope["cluster"];?>&j_view=small-clusterimage" BORDER=0>
</A>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>