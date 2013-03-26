<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
if (function_exists('Dwoo_Plugin_regex_replace')===false)
	$this->getLoader()->loadPlugin('regex_replace');
/* end template head */ ob_start(); /* template body */ ?><!-- Begin host_view.tpl -->
<style type="text/css">
/* don't display legends for these small graphs */
.flotlegend, .flotlegendtoplabel {
  display: none !important;
}
.flotheader {
  margin-top: 2em;
}
.flottitle {
  padding-right: 4em;
  font-weight: bold;
}
.img_view {
  float: left;
  margin: 0 0 10px 10px;
}
</style>
<script type="text/javascript">
var SEPARATOR = "_|_";
var ALL_GROUPS = "ALLGROUPS";
var NO_GROUPS = "NOGROUPS";
// Map metric group id to name
var g_mgMap = new Object();

function clearStoredMetricGroups() {
  var stored_groups = $('input[name="metric_group"]');
  stored_groups.val(NO_GROUPS);
}

function selectAllMetricGroups() {
  var stored_groups = $('input[name="metric_group"]');
  stored_groups.val(ALL_GROUPS);
}

function addMetricGroup(mgName) {
  var stored_groups = $('input[name="metric_group"]');

  var open_groups = stored_groups.val();
  if (open_groups == ALL_GROUPS)
    return; // no exceptions

  var groups = open_groups.split(SEPARATOR);
  switch (groups[0]) {
    case ALL_GROUPS:
      // Remove from except list
      for (var i = 1; i < groups.length; i++) {
        if (groups[i] == mgName) {
          groups.splice(i, 1);
          break;
        }
      }
      open_groups = groups.join(SEPARATOR);
    break;
    case NO_GROUPS:
      // Add to list if not already there
      var inList = false;
      for (var i = 1; i < groups.length; i++) {
         if (groups[i] == mgName) {
           inList = true;
           break;
         }
      }
      if (!inList) {
        open_groups += SEPARATOR;
        open_groups += mgName;
      }
    break;
    default:
      alert("Unrecognized group option - " + groups[0]);
  }
  stored_groups.val(open_groups);
}

function removeMetricGroup(mgName) {
  var stored_groups = $('input[name="metric_group"]');

  var open_groups = stored_groups.val();
  if (open_groups == NO_GROUPS)
    return; // no exceptions

  var groups = open_groups.split(SEPARATOR);
  switch (groups[0]) {
    case ALL_GROUPS:
      var inList = false;
      for (var i = 1; i < groups.length; i++) {
        if (groups[i] == mgName) {
          inList = true;
          break;
        }
      }
      if (!inList) {
        open_groups += SEPARATOR;
        open_groups += mgName;
      }
    break;
    case NO_GROUPS:
      for (var i = 1; i < groups.length; i++) {
        if (groups[i] == mgName) {
          groups.splice(i, 1);
          break;
        }
      }
      open_groups = groups.join(SEPARATOR);
    break;
    default:
      alert("Unrecognized group option - " + groups[0]);
  }
  stored_groups.val(open_groups);
}

function toggleMetricGroup(mgId, mgDiv) {
  var mgName = g_mgMap[mgId];
  if (mgDiv.is(":visible"))
    // metric group is being closed
    removeMetricGroup(mgName);
  else
    addMetricGroup(mgName);
  document.ganglia_form.submit();
}

function jumpToMetricGroup(mgId) {
  //alert("jumping to " + mgId);
  $.scrollTo($('#' + mgId));
}

function refreshHostView() {
  $.get('host_overview.php?h=<?php echo $this->scope["hostname"];?>&c=<?php echo $this->scope["cluster"];?>', function(data) {
    $('#host_overview_div').html(data);
  });

  $("#optional_graphs img").each(function (index) {
    var src = $(this).attr("src");
    if ((src.indexOf("graph.php") == 0) ||
        (src.indexOf("./graph.php") == 0)) {
      var d = new Date();
      $(this).attr("src", jQuery.param.querystring(src, "&_=" + d.getTime()));
    }    
  });

  $("#metrics img").each(function (index) {
    var src = $(this).attr("src");
    if ((src.indexOf("graph.php") == 0)  ||
        (src.indexOf("./graph.php") == 0)) {
      var d = new Date();
      $(this).attr("src", jQuery.param.querystring(src, "&_=" + d.getTime()));
    }    
  });
}

$(function() {
  var stored_groups = $('input[name="metric_group"]');
  stored_groups.val("<?php echo $this->scope["g_open_metric_groups"];?>");
 
  $("#edit_optional_graphs").dialog({ autoOpen: false, minWidth: 550,
    beforeClose: function(event, ui) { location.reload(true); } });

  $("#close_edit_optional_graphs_link").button();
  $("#popup-dialog").dialog({ autoOpen: false, minWidth: 850 });

  $("#edit_optional_graphs_button").button();
  $("#edit_optional_graphs_button").click(function(event) {
    $("#edit_optional_graphs").dialog('open');
    $('#edit_optional_graphs_content').html('<img src="img/spinner.gif" />');
    $.get('edit_optional_graphs.php', "hostname=<?php echo $this->scope["hostname"];?>", function(data) {
      $('#edit_optional_graphs_content').html(data);
    })
    return false;
  });

  $("#save_optional_graphs_button").button();
  $("#save_optional_graphs_button").click(function(event) {
    $.get('edit_optional_graphs.php', $("#edit_optional_reports_form").serialize(), function(data) {
      $('#edit_optional_graphs_content').html(data);
      $("#save_optional_graphs_button").hide();
    });
    return false;
  });

  $("#expand_all_metric_groups").button();
  $("#expand_all_metric_groups").click(function(event) {
    selectAllMetricGroups();
    document.ganglia_form.submit();
    return false;
  });

  $("#collapse_all_metric_groups").button();
  $("#collapse_all_metric_groups").click(function(event) {
    clearStoredMetricGroups();
    document.ganglia_form.submit();
    return false;
  });

  $("#host_overview").button();
  $('#host_overview').click(function() {
    var options = { to: { width: 200, height: 60 } }; 
    $("#host_overview_div").toggle("blind", options, 500);
    return false;
  });

  $('.metric-group').each(function() {
    $(this).button();
    $(this).click(function() {
      var id = $(this).attr('id');
      toggleMetricGroup(id, $("#"+id+"_div"));
    });
  });
});
</script>

<?php if ((isset($this->scope["graph_engine"]) ? $this->scope["graph_engine"] : null) == "flot") {
?>
<script language="javascript" type="text/javascript" src="js/jquery.flot.min.js"></script>
<script type="text/javascript" src="js/create-flot-graphs.js"></script>
<style type="text/css">
.flotgraph2 {
  height: <?php echo $this->scope["graph_height"];?>px;
  width:  <?php echo $this->scope["graph_width"];?>px;
}
</style>
<?php 
}?>


<style type="text/css">
  .toggler { width: 500px; height: 200px; }
  #effect { width: 240px; height: 135px; padding: 0.4em; position: relative; }
  #effect h3 { margin: 0; padding: 0.4em; text-align: center; }
</style>

<div id="metric-actions-dialog" title="Metric Actions">
  <div id="metric-actions-dialog-content">
	Available Metric actions.
  </div>
</div>
<div id="popup-dialog" title="Inspect Graph">
  <div id="popup-dialog-navigation"></div>
  <div id="popup-dialog-content">
  </div>
</div>

<div>
<button id="host_overview" class="button">Host Overview</button>
</div>

<div style="display: none;" id="host_overview_div">
<?php echo Dwoo_Plugin_include($this, 'host_overview.tpl', null, null, null, '_root', null);?>

</div>

<div id="edit_optional_graphs">
  <div style="text-align:center">
    <button id="save_optional_graphs_button">Save</button>
  </div>
  <div id="edit_optional_graphs_content" style="padding: .4em 1em .4em 10px;">Empty</div>
</div>

<div id="optional_graphs" style="padding-top:5px;">
<?php echo $this->scope["optional_reports"];?>

<div style='clear: left'></div>
<?php if ((isset($this->scope["may_edit_cluster"]) ? $this->scope["may_edit_cluster"] : null)) {
?>
<div style="text-align:center"><button id="edit_optional_graphs_button">Edit Optional Graphs</button></div>
<?php 
}?>

</div>

<div id="sort_column_dropdowns" style="padding-top:5px;">
<table border="0" width="100%">
<tr>
  <td style="text-align:center;background-color:rgb(238,238,238);">
  <?php echo $this->scope["host"];?> <strong>graphs</strong> (<?php echo $this->scope["host_metrics_count"];?>)
  last <strong><?php echo $this->scope["range"];?></strong>
  sorted <strong><?php echo $this->scope["sort"];?></strong>
<?php if (((isset($this->scope["columns_dropdown"]) ? $this->scope["columns_dropdown"] : null) !== null)) {
?>
  <font>
    Columns&nbsp;&nbsp;<?php echo $this->scope["metric_cols_menu"];?>

    Size&nbsp;&nbsp;<?php echo $this->scope["size_menu"];?>

  </font>
<?php 
}?>

  </td>
</tr>
</table>

</div>

<div id=metrics style="padding-top:5px">
<center>
<div style="padding-bottom:5px;">
<button id="expand_all_metric_groups">Expand All Metric Groups</button>
<button id="collapse_all_metric_groups">Collapse All Metric Groups</button>
<input title="Time Shift Overlay - overlays previous period on all graphs" type="checkbox" id="timeshift_overlay" onclick="showTimeshiftOverlay(this.checked)"/><label for="timeshift_overlay">Timeshift Overlay</label>
<select id="jump_to_metric_group" class="ui-corner-all" onchange="jumpToMetricGroup(this.options[this.selectedIndex].value);">
<option disabled="disabled" selected="selected">Jump To Metric Group...</option>
<?php 
$_fh0_data = (isset($this->scope["g_metrics_group_data"]) ? $this->scope["g_metrics_group_data"] : null);
if ($this->isTraversable($_fh0_data) == true)
{
	foreach ($_fh0_data as $this->scope['group']=>$this->scope['g_metrics'])
	{
/* -- foreach start output */
?>
<?php $this->scope["mgId"]="mg_";
$this->scope["mgId"].=Dwoo_Plugin_regex_replace($this, (isset($this->scope["group"]) ? $this->scope["group"] : null), '/[^a-zA-Z0-9_]/', '_')?>

<option value="<?php echo $this->scope["mgId"];?>"><?php echo $this->scope["group"];?></a>
<?php 
/* -- foreach end output */
	}
}?>

</select>
</div>
<table>
<tr>
 <td>

<?php 
$_fh2_data = (isset($this->scope["g_metrics_group_data"]) ? $this->scope["g_metrics_group_data"] : null);
if ($this->isTraversable($_fh2_data) == true)
{
	foreach ($_fh2_data as $this->scope['group']=>$this->scope['g_metrics'])
	{
/* -- foreach start output */
?>
<?php $this->scope["mgId"]="mg_";
$this->scope["mgId"].=Dwoo_Plugin_regex_replace($this, (isset($this->scope["group"]) ? $this->scope["group"] : null), '/[^a-zA-Z0-9_]/', '_')?>

<table border="0" width="100%">
<tr>
  <td class="metric">
  <button id="<?php echo $this->scope["mgId"];?>" class="metric-group" title="Toggle <?php echo $this->scope["group"];?> metrics group on/off"><?php echo $this->scope["group"];?> metrics (<?php echo $this->scope["g_metrics"]["group_metric_count"];?>)</button>
<script type="text/javascript">$(function() {
g_mgMap["<?php echo $this->scope["mgId"];?>"] = "<?php echo $this->scope["group"];?>";
})</script>
  </td>
</tr>
</table>

<?php if ((isset($this->scope["g_metrics"]["visible"]) ? $this->scope["g_metrics"]["visible"]:null)) {
?>
<div id="<?php echo $this->scope["mgId"];?>_div">
<?php 
}
else {
?>
<div id="<?php echo $this->scope["mgId"];?>_div" class="ui-helper-hidden">
<?php 
}?>

<?php if ((isset($this->scope["g_metrics"]["visible"]) ? $this->scope["g_metrics"]["visible"]:null)) {
?>
<table><tr>
<?php $this->scope["i"]=0?>

<?php 
$_fh1_data = (isset($this->scope["g_metrics"]["metrics"]) ? $this->scope["g_metrics"]["metrics"]:null);
if ($this->isTraversable($_fh1_data) == true)
{
	foreach ($_fh1_data as $this->scope['g_metric'])
	{
/* -- foreach start output */
?>
<td>
<font style="font-size: 8px"><?php echo $this->scope["g_metric"]["metric_name"];?> <?php if ((isset($this->scope["g_metric"]["title"]) ? $this->scope["g_metric"]["title"]:null) != '' && (isset($this->scope["g_metric"]["title"]) ? $this->scope["g_metric"]["title"]:null) != (isset($this->scope["g_metric"]["metric_name"]) ? $this->scope["g_metric"]["metric_name"]:null)) {
?>- <?php echo $this->scope["g_metric"]["title"];

}?></font><br>
<?php if ((isset($this->scope["may_edit_views"]) ? $this->scope["may_edit_views"] : null)) {
?>
<?php $this->scope["graph_args"]="&amp;";
$this->scope["graph_args"].=(isset($this->scope["g_metric"]["graphargs"]) ? $this->scope["g_metric"]["graphargs"]:null)?>

<button class="cupid-green" title="Metric Actions - Add to View, etc" onclick="metricActions('<?php echo $this->scope["g_metric"]["host_name"];?>','<?php echo $this->scope["g_metric"]["metric_name"];?>', 'metric', '<?php echo $this->scope["graph_args"];?>'); return false;">+</button>
<?php 
}?>

<button title="Export to CSV" class="cupid-green" onClick="javascript:location.href='./graph.php?<?php echo $this->scope["g_metric"]["graphargs"];?>&amp;csv=1';return false;">CSV</button>
<button title="Export to JSON" class="cupid-green" onClick="javascript:location.href='./graph.php?<?php echo $this->scope["g_metric"]["graphargs"];?>&amp;json=1';return false;">JSON</button>
<button title="Inspect Graph" onClick="inspectGraph('<?php echo $this->scope["g_metric"]["graphargs"];?>'); return false;" class="shiny-blue">Inspect</button>
<button title="6 month trend" onClick="drawTrendGraph('./graph.php?<?php echo $this->scope["g_metric"]["graphargs"];?>&amp;trend=1&amp;z=xlarge'); return false;" class="shiny-blue">Trend</button>

<?php if ((isset($this->scope["graph_engine"]) ? $this->scope["graph_engine"] : null) == "flot") {
?>
<br>
<div id="placeholder_<?php echo $this->scope["g_metric"]["graphargs"];?>" class="flotgraph2 img_view"></div>
<div id="placeholder_<?php echo $this->scope["g_metric"]["graphargs"];?>_legend" class="flotlegend"></div>
<?php 
}
else {
?>
<?php $this->scope["graphId"]=((isset($this->scope["GRAPH_BASE_ID"]) ? $this->scope["GRAPH_BASE_ID"] : null)).((isset($this->scope["mgId"]) ? $this->scope["mgId"] : null)).((isset($this->scope["i"]) ? $this->scope["i"] : null))?>

<?php $this->scope["showEventsId"]=((isset($this->scope["SHOW_EVENTS_BASE_ID"]) ? $this->scope["SHOW_EVENTS_BASE_ID"] : null)).((isset($this->scope["mgId"]) ? $this->scope["mgId"] : null)).((isset($this->scope["i"]) ? $this->scope["i"] : null))?>

<input title="Hide/Show Events" type="checkbox" id="<?php echo $this->scope["showEventsId"];?>" onclick="showEvents('<?php echo $this->scope["graphId"];?>', this.checked)"/><label class="show_event_text" for="<?php echo $this->scope["showEventsId"];?>">Hide/Show Events</label>
<?php $this->scope["timeShiftId"]=((isset($this->scope["TIME_SHIFT_BASE_ID"]) ? $this->scope["TIME_SHIFT_BASE_ID"] : null)).((isset($this->scope["mgId"]) ? $this->scope["mgId"] : null)).((isset($this->scope["i"]) ? $this->scope["i"] : null))?>

<input title="Timeshift Overlay" type="checkbox" id="<?php echo $this->scope["timeShiftId"];?>" onclick="showTimeShift('<?php echo $this->scope["graphId"];?>', this.checked)"/><label class="show_timeshift_text" for="<?php echo $this->scope["timeShiftId"];?>">Timeshift</label>
<br>
<a href="./graph_all_periods.php?<?php echo $this->scope["g_metric"]["graphargs"];?>&amp;z=large">
<img id="<?php echo $this->scope["graphId"];?>" class="noborder <?php echo $this->scope["additional_host_img_css_classes"];?>" style="margin:5px;" alt="<?php echo $this->scope["g_metric"]["alt"];?>" src="./graph.php?<?php echo $this->scope["g_metric"]["graphargs"];?>" title="<?php echo $this->scope["g_metric"]["desc"];?>" />
</A>
<?php 
}?>

</td>
<?php echo $this->scope["g_metric"]["new_row"];?>

<?php echo ($this->assignInScope((isset($this->scope["i"]) ? $this->scope["i"] : null) + 1, 'i'));?>

<?php 
/* -- foreach end output */
	}
}?>

</tr>
</table>
<?php 
}?>

</div>
<?php 
/* -- foreach end output */
	}
}?>

 </td>
</tr>
</table>
</center>
</div>
<input type="hidden" name="metric_group" value="">
<!-- End host_view.tpl -->
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>