<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><!-- A place to put custom HTML for the host view. Re-implement in a skin. -->
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>