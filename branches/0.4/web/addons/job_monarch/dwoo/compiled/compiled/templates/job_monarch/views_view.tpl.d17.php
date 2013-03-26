<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><style type="text/css">
.img_view {
  float: left;
  margin: 0 0 10px 10px;
}
</style>

<script type="text/javascript">
  function refreshView() {
    $("#view_graphs img").each(function (index) {
	var src = $(this).attr("src");
	if (src.indexOf("graph.php") == 0) {
	  var d = new Date();
	  $(this).attr("src", jQuery.param.querystring(src, "&_=" + d.getTime()));
	}    
    });
  }

  $(function() {
    $( "#popup-dialog" ).dialog({ autoOpen: false, width:850 });
    $("#create_view_button")
      .button()
      .click(function() {
	$( "#create-new-view-dialog" ).dialog( "open" );
    });
    $("#delete_view_button")
      .button()
      .click(function() {
        if ($("#vn").val() != "") {
	  if (confirm("Are you sure you want to delete the view: " + $("#vn").val() + " ?")) {
	    $.get('views_view.php?view_name=' + 
                  encodeURIComponent($("#vn").val()) +
                  '&delete_view&views_menu',
                  function(data) {
                    $("#views_menu").html(data);
                    $("#view_graphs").html("");  
                    $.cookie('ganglia-selected-view-' + window.name, "");
		    $("#vn").val("");
                  });
          }
        } else
	  alert("Please select the view to delete");
    });
  });
</script>

<div id="popup-dialog" title="Inspect Graph">
  <div id="popup-dialog-content">
  </div>
</div>

<table id="views_table">
<tr><td valign="top">
<div id="views_menu">
    <?php echo $this->scope["existing_views"];?>

</div>
<script type="text/javascript">$(function() { $("#views_menu").buttonsetv(); });</script>
</td>
<td valign="top">
<div>
<div id="views-content">
  <div id=view_graphs>
    <?php if (((isset($this->scope["number_of_view_items"]) ? $this->scope["number_of_view_items"] : null) !== null)) {
?>
    <?php if ((isset($this->scope["number_of_view_items"]) ? $this->scope["number_of_view_items"] : null) == 0) {
?>
    <div class="ui-widget">
      <div class="ui-state-default ui-corner-all" style="padding: 0 .7em;"> 
        <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
          No graphs defined for this view. Please add some
      </div>
    </div>
    <?php 
}
else {
?>
      <?php $this->scope["i"]=0?>

      <?php 
$_fh0_data = (isset($this->scope["view_items"]) ? $this->scope["view_items"] : null);
if ($this->isTraversable($_fh0_data) == true)
{
	foreach ($_fh0_data as $this->scope['view_item'])
	{
/* -- foreach start output */
?>
      <?php $this->scope["graphId"]=((isset($this->scope["GRAPH_BASE_ID"]) ? $this->scope["GRAPH_BASE_ID"] : null)).("view_").((isset($this->scope["i"]) ? $this->scope["i"] : null))?>

      <?php $this->scope["showEventsId"]=((isset($this->scope["SHOW_EVENTS_BASE_ID"]) ? $this->scope["SHOW_EVENTS_BASE_ID"] : null)).("view_").((isset($this->scope["i"]) ? $this->scope["i"] : null))?>

      <div class="img_view">
        <button title="Export to CSV" class="cupid-green" onClick="javascript:location.href='graph.php?<?php echo $this->scope["view_item"]["url_args"];?>&amp;csv=1';return false;">CSV</button>
        <button title="Export to JSON" class="cupid-green" onClick="javascript:location.href='graph.php?<?php echo $this->scope["view_item"]["url_args"];?>&amp;json=1';return false;">JSON</button>
        <?php if ((isset($this->scope["view_item"]["aggregate_graph"]) ? $this->scope["view_item"]["aggregate_graph"]:null) == 1) {
?>
        <button title="Decompose aggregate graph" class="shiny-blue" onClick="javascript:location.href='?<?php echo $this->scope["view_item"]["url_args"];?>&amp;dg=1&amp;tab=v';return false;">Decompose</button>
        <?php 
}?>

        <button title="Inspect Graph" onClick="inspectGraph('<?php echo $this->scope["view_item"]["url_args"];?>'); return false;" class="shiny-blue">Inspect</button>
        <input type="checkbox" id="<?php echo $this->scope["showEventsId"];?>" onclick="showEvents('<?php echo $this->scope["graphId"];?>', this.checked)"/><label title="Hide/Show Events" class="show_event_text" for="<?php echo $this->scope["showEventsId"];?>">Hide/Show Events</label>
        <br />
<?php if ((isset($this->scope["graph_engine"]) ? $this->scope["graph_engine"] : null) == "flot") {
?>
<div id="placeholder_<?php echo $this->scope["view_item"]["url_args"];?>" class="flotgraph2 img_view"></div>
<div id="placeholder_<?php echo $this->scope["view_item"]["url_args"];?>_legend" class="flotlegend"></div>
<?php 
}
else {
?>
<a href="graph_all_periods.php?<?php echo $this->scope["view_item"]["url_args"];?>"><img id="<?php echo $this->scope["graphId"];?>" class="noborder <?php echo $this->scope["additional_host_img_css_classes"];?>" style="margin-top:5px;" src="graph.php?<?php echo $this->scope["view_item"]["url_args"];?>" /></a>
<?php 
}?>

      </div>
      <?php echo ($this->assignInScope((isset($this->scope["i"]) ? $this->scope["i"] : null) + 1, 'i'));?>

      <?php 
/* -- foreach end output */
	}
}?>

    <?php 
}?>

    <?php 
}?>

  </div>
</div>
<div style="clear: left"></div>
</div>
</td>
</tr>
</table>

<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>