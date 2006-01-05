<?php
# Check if this context is private.
include_once "$GANGLIA_PATH/auth.php";
checkcontrol();
checkprivate();

if ( $context == "control" && $controlroom < 0 )
      $header = "header-nobanner";
else
      $header = "header";

$tpl = new TemplatePower( "templates/header.tpl" );
$tpl->prepare();

# Maintain our path through the grid tree.
$me = $self . "@" . $grid[$self][AUTHORITY];
if ($initgrid)
   {
      $gridstack = array();
      $gridstack[] = $me;
   }
else if ($gridwalk=="fwd")
   {
      # push our info on gridstack, format is "name@url>name2@url".
      if (end($gridstack) != $me)
         {
            $gridstack[] = $me;
         }
   }
else if ($gridwalk=="back")
   {
      # pop a single grid off stack.
      if (end($gridstack) != $me)
         {
            array_pop($gridstack);
         }
   }
$gridstack_str = join(">", $gridstack);
$gridstack_url = rawurlencode($gridstack_str);

if ($initgrid or $gridwalk)
   {
      # Use cookie so we dont have to pass gridstack around within this site.
      # Cookie values are automatically urlencoded. Expires in a day.
      setcookie("gs", $gridstack_str, time() + 86400);
   }

# Invariant: back pointer is second-to-last element of gridstack. Grid stack never
# has duplicate entries.
list($parentgrid, $parentlink) = explode("@", $gridstack[count($gridstack)-2]);

# Setup a redirect to a remote server if you choose a grid from pulldown menu. Tell
# destination server that we're walking foward in the grid tree.
if (strstr($clustername, "http://"))
   {
      $tpl->assign("refresh", "0");
      $tpl->assign("redirect", ";URL=$clustername?gw=fwd&gs=$gridstack_url");
      echo "<h2>Redirecting, please wait...</h2>";
      $tpl->printToScreen();
      exit;
   }
$tpl->assign("refresh", $default_refresh);

$tpl->assign( "date", date("r"));
$tpl->assign( "page_title", $title );

# The page to go to when "Get Fresh Data" is pressed.
if (isset($page))
      $tpl->assign("page",$page);
else
      $tpl->assign("page","./");

# Templated Logo image
$tpl->assign("images","./templates/$template_name/images");

#
# Used when making graphs via graph.php. Included in most URLs
#
$sort_url=rawurlencode($sort);
$get_metric_string = "m=$metric&r=$range&s=$sort_url&hc=$hostcols";
if ($jobrange and $jobstart)
        $get_metric_string .= "&jr=$jobrange&js=$jobstart";

# Set the Alternate view link.
$cluster_url=rawurlencode($clustername);
$node_url=rawurlencode($hostname);

# Make some information available to templates.
$tpl->assign("cluster_url", $cluster_url);

# Build the node_menu
$node_menu = "";

if ($parentgrid)
   {
      $node_menu .= "<B><A HREF=\"$parentlink?gw=back&gs=$gridstack_url\">".
         "$parentgrid $meta_designator</A></B> ";
      $node_menu .= "<B>&gt;</B>\n";
   }

# Show grid.
$mygrid =  ($self == "unspecified") ? "" : $self;
$node_menu .= "<B><A HREF=\"./?$get_metric_string\">$mygrid $meta_designator</A> ";
$node_menu .= "<B>&gt;</B>\n";

if ($physical)
   $node_menu .= hiddenvar("p", $physical);

if ( $clustername )
   {
      $url = rawurlencode($clustername);
      $node_menu .= "<B><A HREF=\"./?c=$url&$get_metric_string\">$clustername</A></B> ";
      //$node_menu .= "<B>&gt;</B>\n";
      $node_menu .= hiddenvar("c", $clustername);
   }
else
   {
      # No cluster has been specified, so drop in a list
      $node_menu .= "<SELECT NAME=\"c\" OnChange=\"ganglia_form.submit();\">\n";
      $node_menu .= "<OPTION VALUE=\"\">--Choose a Source\n";
      ksort($grid);
      foreach( $grid as $k => $v )
         {
            if ($k==$self) continue;
            if ($v[GRID])
               {
                  $url = $v[AUTHORITY];
                  $node_menu .="<OPTION VALUE=\"$url\">$k $meta_designator\n";
               }
            else
               {
                  $url = rawurlencode($k);
                  $node_menu .="<OPTION VALUE=\"$url\">$k\n";
               }
         }
      $node_menu .= "</SELECT>\n";
   }

$tpl->assign("node_menu", $node_menu);

# Make sure that no data is cached..
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    # Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); # always modified
header ("Cache-Control: no-cache, must-revalidate");  # HTTP/1.1
header ("Pragma: no-cache");                          # HTTP/1.0

$tpl->printToScreen();



?>
