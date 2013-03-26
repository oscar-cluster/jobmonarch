<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><HR>
<CENTER>
<FONT SIZE="-1" class=footer>
<A HREF="https://oss.trac.surfsara.nl/jobmonarch/">Job Monarch</A> version <?php echo $this->scope["monarchversion"];?>

<A HREF="https://oss.trac.surfsara.nl/jobmonarch/">Check for Updates.</A><BR>

Ganglia web version <?php echo $this->scope["webfrontendversion"];?><BR>

Downloading and parsing ganglia's XML tree took <?php echo $this->scope["parsetime"];?>.<BR>
Images created with <A HREF="http://www.rrdtool.org/">RRDTool</A>.<BR>
</FONT>
</CENTER>

</FORM>
</BODY>
</HTML>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>