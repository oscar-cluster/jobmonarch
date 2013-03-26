<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><table><tr>
<?php $this->scope["i"]=0?>

<?php 
$_fh2_data = (isset($this->scope["g_metrics"]["metrics"]) ? $this->scope["g_metrics"]["metrics"]:null);
if ($this->isTraversable($_fh2_data) == true)
{
	foreach ($_fh2_data as $this->scope['g_metric'])
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
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>