<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><style>
.img_view {
  float: left;
  margin: 0 0 10px 10px;
}
</style>
<script>
  function refreshDecomposeGraph() {
    $("#decompose-graphs img").each(function (index) {
	var src = $(this).attr("src");
	if (src.indexOf("graph.php") == 0) {
          var l = src.indexOf("&_=");
          if (l != -1)
            src = src.substring(0, l);
	  var d = new Date();
	  $(this).attr("src", src + "&_=" + d.getTime());
	}    
    });
  }

  $(function() {
    $( "#popup-dialog" ).dialog({ autoOpen: false, minWidth: 850 });
    $("#create_view_button")
      .button()
      .click(function() {
	$( "#create-new-view-dialog" ).dialog( "open" );
    });;
  });
</script>
<div id="metric-actions-dialog" title="Metric Actions">
<div id="metric-actions-dialog-content">
	Available Metric actions.
</div>
</div>
<div id="popup-dialog" title="Inspect Graph">
  <div id="popup-dialog-content">
  </div>
</div>
<div id="decompose-graph-content">
  <div id=decompose-graphs>
    <?php if ((isset($this->scope["number_of_items"]) ? $this->scope["number_of_items"] : null) == 0) {
?>
    <div class="ui-widget">
      <div class="ui-state-default ui-corner-all" style="padding: 0 .7em;"> 
        <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
          No graphs decomposed
      </div>
    </div>
    <?php 
}
else {
?>
      <?php 
$_fh0_data = (isset($this->scope["items"]) ? $this->scope["items"] : null);
if ($this->isTraversable($_fh0_data) == true)
{
	foreach ($_fh0_data as $this->scope['item'])
	{
/* -- foreach start output */
?>
      <div class="img_view">
        <button title="Export to CSV" class="cupid-green" onClick="javascript:location.href='graph.php?<?php echo $this->scope["item"]["url_args"];?>&amp;csv=1';return false;">CSV</button>
        <button title="Export to JSON" class="cupid-green" onClick="javascript:location.href='graph.php?<?php echo $this->scope["item"]["url_args"];?>&amp;json=1';return false;">JSON</button>
        <button title="Inspect Graph" onClick="inspectGraph('<?php echo $this->scope["item"]["url_args"];?>'); return false;" class="shiny-blue">Inspect</button>
        <br /><a href="graph_all_periods.php?<?php echo $this->scope["item"]["url_args"];?>"><img class="noborder <?php echo $this->scope["additional_host_img_css_classes"];?>" style="margin-top:5px;" src="graph.php?<?php echo $this->scope["item"]["url_args"];?>" /></a>
      </div>
      <?php 
/* -- foreach end output */
	}
}?>

    <?php 
}?>

  </div>
</div>
<div style="clear: left"></div>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>