<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
/* end template head */ ob_start(); /* template body */ ?><!-- Begin header.tpl -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Ganglia:: <?php echo $this->scope["page_title"];?></title>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<link type="text/css" href="css/smoothness/jquery-ui-1.9.1.custom.min.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.liveSearch.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.multiselect.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.flot.events.css" rel="stylesheet" />
<link type="text/css" href="./styles.css" rel="stylesheet" />
<link type="text/css" href="addons/job_monarch/styles.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.9.1.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.livesearch.min.js"></script>
<script type="text/javascript" src="js/ganglia.js"></script>
<script type="text/javascript" src="js/jquery.gangZoom.js"></script>
<script type="text/javascript" src="js/jquery.cookie.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="js/jquery.ba-bbq.min.js"></script>
<script type="text/javascript" src="js/combobox.js"></script>
<script type="text/javascript" src="js/jquery.scrollTo-1.4.2-min.js"></script>
<script type="text/javascript" src="js/jquery.buttonsetv.js"></script>
<script type="text/javascript">
    var server_utc_offset=<?php echo $this->scope["server_utc_offset"];?>;
    
    var g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);

    function refreshHeader() {
      $.get('header.php?date_only=1', function(data) {
        var title = $("#page_title").text();
        var l = title.lastIndexOf(" for ");
        title = title.substring(0, l);
        title += " for " + data;
        $("#page_title").text(title);
        });
    }

    function refresh() {
      var selected_tab = $("#selected_tab").val();
      if (selected_tab == "agg") {
        refreshAggregateGraph();
        g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);
      } else if (selected_tab == "v") {
        refreshHeader();
        if ($.isFunction(window.refreshView)) {
          refreshView();
          g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);
        } else if ($.isFunction(window.refreshDecomposeGraph)) {
          refreshDecomposeGraph();
          g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);
        } else
          ganglia_form.submit();
      } else if (selected_tab == "ev") {
        refreshOverlayEvent();
        g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);
      } else if (selected_tab == "m") {
        if ($.isFunction(window.refreshClusterView)) {
          refreshHeader();
          refreshClusterView();
          g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);
        } else if ($.isFunction(window.refreshHostView)) {
          refreshHeader();
          refreshHostView();
          g_refresh_timer = setTimeout("refresh()", <?php echo $this->scope["refresh"];?> * 1000);
        } else
          ganglia_form.submit();
      } else
        ganglia_form.submit();
    }

    $(function() {
      g_overlay_events = ($("#overlay_events").val() == "true");

      g_tabIndex = new Object();
      g_tabName = [];
      var tabName = ["m", "s", "v", "agg", "ch", "ev", "rot", "mob"];
      var j = 0;
      for (var i in tabName) {
        if (tabName[i] == "ev" && !g_overlay_events)
          continue;
        g_tabIndex[tabName[i]] = j++;
        g_tabName.push(tabName[i]);
      }

      // Follow tab's URL instead of loading its content via ajax
      var tabs = $("#tabs");
      if (tabs[0]) {
        tabs.tabs();
        // Restore previously selected tab
        var selected_tab = $("#selected_tab").val();
        //alert("selected_tab = " + selected_tab);
        if (typeof g_tabIndex[selected_tab] != 'undefined') {
          try {
            //alert("Selecting tab: " + selected_tab);
            tabs.tabs("select", g_tabIndex[selected_tab]);
            if (selected_tab == "rot")
              autoRotationChooser();
          } catch (err) {
            try {
              alert("Error(ganglia.js): Unable to select tab: " + 
                    selected_tab + ". " + err.getDescription());
            } catch (err) {
              // If we can't even show the error, fail silently.
            }
          }
        }

        tabs.bind("tabsselect", function(event, ui) {
          $("#selected_tab").val(g_tabName[ui.index]);
          if (g_tabName[ui.index] != "mob")
            $.cookie("ganglia-selected-tab-" + window.name, ui.index);
          if (ui.index == g_tabIndex["m"] ||
              ui.index == g_tabIndex["v"] ||
              ui.index == g_tabIndex["ch"])
            ganglia_form.submit();
        });
      }
    });

    $(function() {
      $("#metrics-picker").combobox();

      <?php echo $this->scope["is_metrics_picker_disabled"];?>


      $(".header_btn").button();
    });

  $(function () {

    done = function done(startTime, endTime) {
            setStartAndEnd(startTime, endTime);
            document.forms['ganglia_form'].submit();
    }

    cancel = function (startTime, endTime) {
            setStartAndEnd(startTime, endTime);
    }

    defaults = {
        startTime: <?php echo $this->scope["start_timestamp"];?>,
        endTime: <?php echo $this->scope["end_timestamp"];?>,
        done: done,
        cancel: cancel
    }

    $(".host_small_zoomable").gangZoom($.extend({
        paddingLeft: 67,
        paddingRight: 30,
        paddingTop: 38,
        paddingBottom: 25
    }, defaults));

    $(".host_medium_zoomable").gangZoom($.extend({
        paddingLeft: 67,
        paddingRight: 30,
        paddingTop: 38,
        paddingBottom: 40
    }, defaults));

    $(".host_default_zoomable").gangZoom($.extend({
        paddingLeft: 66,
        paddingRight: 30,
        paddingTop: 37,
        paddingBottom: 50
    }, defaults));

    $(".host_large_zoomable").gangZoom($.extend({
        paddingLeft: 66,
        paddingRight: 29,
        paddingTop: 37,
        paddingBottom: 56
    }, defaults));

    $(".cluster_zoomable").gangZoom($.extend({
        paddingLeft: 67,
        paddingRight: 30,
        paddingTop: 37,
        paddingBottom: 50
    }, defaults));

    function rrdDateTimeString(date) {
      return (date.getMonth() + 1) + "/" + date.getDate() + "/" + date.getFullYear() + " " + date.getHours() + ":" + date.getMinutes();
    }

    function setStartAndEnd(startTime, endTime) {
        // we're getting local start/end times.

        // getTimezoneOffset returns negative values east of UTC,
        // which is the opposite of PHP. we want negative values to the west.
        var local_offset = new Date().getTimezoneOffset() * 60 * -1;
        var delta = local_offset - server_utc_offset;
        var date = new Date((Math.floor(startTime) - delta) * 1000);
        $("#datepicker-cs").val(rrdDateTimeString(date));
        date = new Date((Math.floor(endTime) - delta) * 1000);
        $("#datepicker-ce").val(rrdDateTimeString(date));
    }

    initShowEvent();
    initTimeShift();
  });


</script>
<?php echo $this->scope["custom_time_head"];?>

</head>
<body style="background-color: #ffffff;" onunload="g_refresh_timer=null">
<?php if (((isset($this->scope["user_header"]) ? $this->scope["user_header"] : null) !== null)) {
?>
<?php echo Dwoo_Plugin_include($this, "user_header.tpl", null, null, null, '_root', null);?>

<?php 
}?>


<?php if ((isset($this->scope["auth_system_enabled"]) ? $this->scope["auth_system_enabled"] : null)) {
?>
<div style="float:right">
  <?php if ((isset($this->scope["username"]) ? $this->scope["username"] : null)) {
?>
    Currently logged in as: <?php echo $this->scope["username"];?> | <a href="logout.php">Logout</a>
  <?php 
}
else {
?>
    You are not currently logged in. | <a href="login.php">Login</a>
  <?php 
}?>

</div>
<br style="clear:both"/>
<?php 
}?>


<div id="tabs">
<ul>
  <li><a href="#tabs-main">Main</a></li>
  <li><a href="#tabs-search">Search</a></li>
  <li><a href="#tabs-main">Views</a></li>
  <li><a href="aggregate_graphs.php">Aggregate Graphs</a></li>
  <li><a href="#tabs-main">Compare Hosts</a></li>
<?php if ((isset($this->scope["overlay_events"]) ? $this->scope["overlay_events"] : null)) {
?>
  <li><a href="events.php">Events</a></li>
<?php 
}?>

  <li><a href="#tabs-autorotation" onclick="autoRotationChooser();">Automatic Rotation</a></li>
  <li><a href="#tabs-livedashboard" onclick="liveDashboardChooser();">Live Dashboard</a></li>  
  <li><a href="#tabs-mobile" onclick="window.location.href='mobile.php';">Mobile</a></li>
</ul>

<div id="tabs-main">
<form action="<?php echo $this->scope["page"];?>" method="GET" name="ganglia_form">
  <div style="background-color:#dddddd;padding:5px;">
     <big style="float:left;"><b id="page_title"><?php echo $this->scope["page_title"];?> for <?php echo $this->scope["date"];?></b></big><input style="float:right;" class="header_btn" type="submit" value="Get Fresh Data"/><div style="clear:both"></div>
  </div>
  <div style="padding:5px 5px 0 5px;">
    <div style="float:left;" id="range_menu" class="nobr"><?php echo $this->scope["range_menu"];?></div>
    <div style="float:left;" id="custom_range_menu"><?php echo $this->scope["custom_time"];?></div>
    <div style="float:right;"><?php echo $this->scope["additional_buttons"];?>&nbsp;&nbsp;<?php echo $this->scope["alt_view"];?></div>
    <div style="clear:both;"></div>
  </div>
  <div id="sort_menu" style="padding:5px 5px 0 5px;">
   Metric&nbsp;&nbsp; <select name="m" id="metrics-picker"><?php echo $this->scope["picker_metrics"];?></select>&nbsp;&nbsp;
     <?php echo $this->scope["sort_menu"];?>

  </div>
<?php if ((isset($this->scope["node_menu"]) ? $this->scope["node_menu"] : null) != "") {
?>
  <div id="sort_menu" style="padding:5px 5px 0 5px;">
    <?php echo $this->scope["node_menu"];?>&nbsp;&nbsp;<?php echo $this->scope["additional_filter_options"];?>

  </div>
<?php 
}?>


<input type="hidden" name="tab" id="selected_tab" value="<?php echo $this->scope["selected_tab"];?>">
<input type="hidden" id="vn" name="vn" value="<?php echo $this->scope["view_name"];?>">
<?php if ((isset($this->scope["overlay_events"]) ? $this->scope["overlay_events"] : null)) {
?>
<input type="hidden" id="overlay_events" value="true">
<?php 
}
else {
?>
<input type="hidden" id="overlay_events" value="false">
<?php 
}?>

<hr size="1" noshade>
<!-- End header.tpl -->
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>