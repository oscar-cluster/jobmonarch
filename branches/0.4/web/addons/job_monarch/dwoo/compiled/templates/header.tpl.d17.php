<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Ganglia :: <?php echo $this->scope["longpage_title"];?></TITLE>
<META http-equiv="Content-type" content="text/html; charset=utf-8">
<META http-equiv="refresh" content="<?php echo $this->scope["refresh"];
echo $this->scope["redirect"];?>" >
<LINK rel="stylesheet" href="./styles.css" type="text/css">
<SCRIPT LANGUAGE="javascript" SRC="ts_picker.js"></SCRIPT>
<SCRIPT LANGUAGE="javascript">

        function setPeriodTimestamps() {

                document.<?php echo $this->scope["form_name"];?>.period_start.value = document.<?php echo $this->scope["form_name"];?>.period_start_pick.value;
                document.<?php echo $this->scope["form_name"];?>.period_stop.value = document.<?php echo $this->scope["form_name"];?>.period_stop_pick.value;
        }

</SCRIPT>
</HEAD>
<BODY BGCOLOR="#FFFFFF">

<FORM ACTION="./" METHOD="GET" NAME="<?php echo $this->scope["form_name"];?>">
<INPUT TYPE="HIDDEN" NAME="c" VALUE="<?php echo $this->scope["cluster"];?>">
<INPUT TYPE="HIDDEN" NAME="view" VALUE="<?php echo $this->scope["view"];?>">
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
     <B><?php echo $this->scope["page_title"];?> for <?php echo $this->scope["date"];?></B>
     </FONT>
     </TD>
     <TD BGCOLOR="#DDDDDD" ALIGN="RIGHT">
     <INPUT TYPE="SUBMIT" VALUE="Get Fresh Data">
     </TD>
     <TD></TD>
  </TR>
  <TR>
     <TD COLSPAN=1>
     <?php echo $this->scope["metric_menu"];?> &nbsp;&nbsp;
     <?php echo $this->scope["range_menu"];?>&nbsp;&nbsp;
     <?php echo $this->scope["sort_menu"];?>&nbsp;&nbsp;
<?php if ("".(isset($this->scope["timeperiod"]) ? $this->scope["timeperiod"] : null)."" == "yes") {
?>
    <INPUT TYPE="HIDDEN" NAME="period_start" VALUE="<?php echo $this->scope["period_start"];?>">
    <INPUT TYPE="HIDDEN" NAME="period_stop" VALUE="<?php echo $this->scope["period_stop"];?>">
    <INPUT TYPE="HIDDEN" NAME="h" VALUE="<?php echo $this->scope["hostname"];?>">
    <BR><BR><B>Graph/ from
    <INPUT TYPE="text" NAME="period_start_pick" VALUE="<?php echo $this->scope["period_start"];?>" ALT="Start time" DISABLED="TRUE">
    <a href="javascript:show_calendar('document.<?php echo $this->scope["form_name"];?>.period_start_pick', document.<?php echo $this->scope["form_name"];?>.period_start_pick.value);" alt="Click to select a date/time" title="Click to select a date/time">
    <img src="cal.gif" width="16" height="16" border="0"></a>
    <a href="#" onClick="javascript: document.<?php echo $this->scope["form_name"];?>.period_start_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
    <IMG SRC="redcross.jpg" BORDER=0></A>
    to <INPUT TYPE="text" NAME="period_stop_pick" VALUE="<?php echo $this->scope["period_stop"];?>" ALT="Stop time" DISABLED="TRUE">
    <a href="javascript:show_calendar('document.<?php echo $this->scope["form_name"];?>.period_stop_pick', document.<?php echo $this->scope["form_name"];?>.period_stop_pick.value);" alt="Click to select a date/time" title="Click to select a date/time">
    <img src="cal.gif" width="16" height="16" border="0"></a>
    <a href="#" onClick="javascript: document.<?php echo $this->scope["form_name"];?>.period_stop_pick.value=''" alt="Click here to clear field" title="Click here to clear field">
    </B>
    <IMG SRC="redcross.jpg" BORDER=0></A>

<?php if ("".(isset($this->scope["hostview"]) ? $this->scope["hostview"] : null)."" == "yes") {
?>
    <INPUT TYPE="HIDDEN" NAME="job_start" VALUE="<?php echo $this->scope["job_start"];?>">
    <INPUT TYPE="HIDDEN" NAME="job_stop" VALUE="<?php echo $this->scope["job_stop"];?>">
<?php 
}?>

    <INPUT TYPE="submit" onClick="setPeriodTimestamps();" VALUE="Refresh graphs">
<?php 
}?>

     </TD>
     <TD>
      <B><?php echo $this->scope["alt_view"];?></B>
     </TD>

  </TR>
  </TABLE>

<?php if ("".(isset($this->scope["search"]) ? $this->scope["search"] : null)."" == "yes") {
?>
     <TD align="right"><CENTER>
       <A HREF="./?c=<?php echo $this->scope["cluster_url"];?>&view=search">
       <B><I>Jobarchive</I></B><BR>
       <IMG SRC="./document_archive.jpg" HEIGHT=100 ALT="Search the archive for <?php echo $this->scope["cluster"];?>" TITLE="Search the archive for <?php echo $this->scope["cluster"];?>" BORDER=0></A></CENTER>
     </TD>
<?php 
}?>


  </TD>
</TR>
</TABLE> 

<FONT SIZE="+1">
<?php echo $this->scope["node_menu"];?>

</FONT>

<HR SIZE="1" NOSHADE>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>