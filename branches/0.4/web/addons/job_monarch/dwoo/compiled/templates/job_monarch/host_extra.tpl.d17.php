<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><a href="./addons/job_monarch/?c=<?php echo $this->scope["cluster"];?>&h=<?php echo $this->scope["host"];?>">
<IMG SRC="./addons/job_monarch/image.php?c=<?php echo $this->scope["cluster"];?>&h=<?php echo $this->scope["host"];?>&j_view=hostimage" BORDER=0>
</a>
<hr>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>