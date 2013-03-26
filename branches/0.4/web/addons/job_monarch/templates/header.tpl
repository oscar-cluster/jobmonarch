<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Ganglia :: {$longpage_title}</TITLE>
<META http-equiv="Content-type" content="text/html; charset=utf-8">
<META http-equiv="refresh" content="{$refresh}{$redirect}" >
<LINK rel="stylesheet" href="./styles.css" type="text/css">
<SCRIPT LANGUAGE="javascript" SRC="ts_picker.js"></SCRIPT>
<SCRIPT LANGUAGE="javascript">

        function setPeriodTimestamps() {

                document.{$form_name}.period_start.value = document.{$form_name}.period_start_pick.value;
                document.{$form_name}.period_stop.value = document.{$form_name}.period_stop_pick.value;
        }

</SCRIPT>
</HEAD>
<BODY BGCOLOR="#FFFFFF">

<FORM ACTION="./" METHOD="GET" NAME="{$form_name}">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="{$cluster}">
<INPUT TYPE="HIDDEN" NAME="view" VALUE="{$view}">
<TABLE WIDTH="100%">
<TR>
  <TD ROWSPAN="2" WIDTH="150">
  <A HREF="https://subtrac.sara.nl/oss/jobmonarch/">
  <IMG SRC="./jobmonarch.gif" 
      ALT="Ganglia" BORDER="0"></A>
  </TD>
  <TD VALIGN="TOP">

  <TABLE WIDTH="100%" CELLPADDING="8" CELLSPACING="0" BORDER=0>
  <TR BGCOLOR="#DDDDDD">
     <TD BGCOLOR="#DDDDDD">
     <FONT SIZE="+1">
     <B>{$page_title} for {$date}</B>
     </FONT>
     </TD>
     <TD BGCOLOR="#DDDDDD" ALIGN="RIGHT">
     <INPUT TYPE="SUBMIT" VALUE="Get Fresh Data">
     </TD>
     <TD></TD>
  </TR>
  <TR>
     <TD COLSPAN=1>
     {$metric_menu} &nbsp;&nbsp;
     {$range_menu}&nbsp;&nbsp;
     {$sort_menu}&nbsp;&nbsp;
{if "$timeperiod" == "yes"}
    <INPUT TYPE="HIDDEN" NAME="period_start" VALUE="{$period_start}">
    <INPUT TYPE="HIDDEN" NAME="period_stop" VALUE="{$period_stop}">
    <INPUT TYPE="HIDDEN" NAME="h" VALUE="{$hostname}">
    <BR><BR><B>Graph/ from
    <INPUT TYPE="text" NAME="period_start_pick" VALUE="{$period_start}" ALT="Start time" DISABLED="TRUE">
    <a href="javascript:show_calendar('document.{$form_name}.period_start_pick', document.{$form_name}.period_start_pick.value);" alt="Click to select a date/time" title="Click to select a date/time">
    <img src="cal.gif" width="16" height="16" border="0"></a>
    <a href="#" onClick="javascript: document.{$form_name}.period_start_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
    <IMG SRC="redcross.jpg" BORDER=0></A>
    to <INPUT TYPE="text" NAME="period_stop_pick" VALUE="{$period_stop}" ALT="Stop time" DISABLED="TRUE">
    <a href="javascript:show_calendar('document.{$form_name}.period_stop_pick', document.{$form_name}.period_stop_pick.value);" alt="Click to select a date/time" title="Click to select a date/time">
    <img src="cal.gif" width="16" height="16" border="0"></a>
    <a href="#" onClick="javascript: document.{$form_name}.period_stop_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
    </B>
    <IMG SRC="redcross.jpg" BORDER=0></A>

{if "$hostview" == "yes"}
    <INPUT TYPE="HIDDEN" NAME="job_start" VALUE="{$job_start}">
    <INPUT TYPE="HIDDEN" NAME="job_stop" VALUE="{$job_stop}">
{/if}
    <INPUT TYPE="submit" onClick="setPeriodTimestamps();" VALUE="Refresh graphs">
{/if}
     </TD>
     <TD>
      <B>{$alt_view}</B>
     </TD>

  </TR>
  </TABLE>

{if "$search" == "yes"}
     <TD align="right"><CENTER>
       <A HREF="./?c={$cluster_url}&view=search">
       <B><I>Jobarchive</I></B><BR>
       <IMG SRC="./document_archive.jpg" HEIGHT=100 ALT="Search the archive for {$cluster}" TITLE="Search the archive for {$cluster}" BORDER=0></A></CENTER>
     </TD>
{/if}

  </TD>
</TR>
</TABLE> 

<FONT SIZE="+1">
{$node_menu}
</FONT>

<HR SIZE="1" NOSHADE>
