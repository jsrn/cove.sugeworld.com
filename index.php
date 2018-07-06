<HTML>
<head>
	<title>The Baronship of Cove</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="One of Ultima Online's best and most established roleplaying guilds. Join the adventure for free!">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="uo,europa,roleplay,roleplaying,rp,rpg,mmo,moorpg,ultima,ultima online,uorp">

  <SCRIPT>
function tale_window(strURL) {
 window.open(strURL,'scrollframe','width=900,height=600,scrollbars,menubar=no,status=no');
}
function tale_music(strURL) {
	window.open(strURL,'scrollframe','width=100,height=50,scrollbars,menubar=no,status=no');
}
</SCRIPT>

  <LINK REL=STYLESHEET HREF="norm.css" TYPE="text/css">
  <link rel="shortcut icon" href="bocimages/favicon.ico">
</head>
<BODY bgcolor="#0C5003" background="bocimages/grassbg.gif" topmargin="0"
bottommargin="0" LINK="#000000" VLINK="#000000" ALINK="#000000">
<P>
<?php include("eggs.inc"); ?>
<!--- The Roads are no longer safe. Losis Rederic, Endless Rogue --->
<DIV align="center">
  <IMG SRC="bocimages/boctop.jpg" WIDTH=973 HEIGHT=128 BORDER=0 ALT="" USEMAP="#boctop1_Map"><BR>
  <TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" WIDTH="973">
    <TR>
      <TD width="224" background="bocimages/contside.gif" valign="top"><TABLE border="0"
	cellspacing="0" cellpadding="0" width="224">
	  <TR>
	    <TD valign="top" background="bocimages/leftable.gif"><DIV align="center">
		<TABLE width="100%" cellspacing="0" cellpadding="10" width="40">
		  <TR>
		    <TD valign="top" width="15"></TD>
		    <TD valign="top"><?php include("menu.inc"); ?></TD>
		  </TR>
		</TABLE>
	      </DIV>
	    </TD>
	  </TR>
	  <TR>
	    <TD valign="top"><IMG SRC="bocimages/cont.jpg" WIDTH=224 HEIGHT=222 BORDER=0
		  ALT="" USEMAP="#cont_Map"></TD>
	  </TR>
	</TABLE>
      </TD>
      <TD width="563" background="bocimages/centretable.gif" valign="top">
<!-- Individual content starts here -->
		<?php
		if (isset($_GET["page"])) {
			$page = $_GET["page"].".html";
                    // Strip null bytes to protect against poison null byte injection
		    $load = str_replace(chr(0), '', $page);
		    if (file_exists($load)) {
			include ($load);
		    } else {
			include ("latestnews.php");
		    }
		} else {
			include ("latestnews.php");
		    } ?>

<!-- Individual content ends here -->
      </TD>
      <TD width="186" background="bocimages/righttable.gif" valign="top"><!---Wooden
	Table--->
	<TABLE width="100%" cellspacing="0" cellpadding="0" width="186">
	  <TR>
	    <TD valign="top" width="17"></TD>
	    <TD valign="top" class="notes"><?php include("board.inc");
	      ?></TD>
	  </TR>
	</TABLE>
      </TD>
    </TR>
    <TR>
      <TD valign="top"><IMG SRC="bocimages/leftbot.gif" WIDTH="224" HEIGHT="12"></TD>
      <TD valign="top"><IMG SRC="bocimages/centrebot.gif" WIDTH="563" HEIGHT="12"></TD>
      <TD valign="top"><IMG SRC="bocimages/rightbot.gif" WIDTH=186 HEIGHT=12 BORDER=0
	    ALT="" USEMAP="#rightbot_Map"></TD>
    </TR>
  </TABLE>
</DIV>
<P>
<BR>
<DIV align="right">
  <?php include("footer.inc"); ?>
</DIV>
</BODY></HTML>
