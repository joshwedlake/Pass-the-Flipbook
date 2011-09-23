<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//you need to be logged in to be here drawing

// Initialize the session.
session_start();

//if the user isn't logged in, get them to login
if(!isset($_SESSION["user"])) {
	//if there's a query string, then we're probably meant to be on this page
	if($_SERVER["QUERY_STRING"]) {
		$redir_url="draw.php?" . $_SERVER["QUERY_STRING"];
		header('Location: login.php?redir='.urlencode($redir_url));
		die();
	}
	else {
		//otherwise let the user login as by defualt so they get sent to the main menu
		header('Location: login.php');
		die();
	}
}
	
include("vars.php");
include("build_links.php");

//function to check the drawing has already been saved
//returns true if the drawing has already been saved
function check_if_already_saved($save_id){
	
	$result=mysql_query("SELECT * FROM animations WHERE (id = '" . mysql_real_escape_string($save_id) . "') and (saved = '1')");
	if (mysql_num_rows($result) == 1) return false;
	else return true;
	
}

//function to check the drawing belongs to the logged in user
//returns true if the drawing is owned by the right person
function check_drawing_owner($save_id,$save_user){
	
	$result=mysql_query("SELECT * FROM animations WHERE (id = '" . mysql_real_escape_string($save_id) . "') and (animator = '" . mysql_real_escape_string($save_user) . "')");
	if (mysql_num_rows($result) == 1) return true;
	else return false;
	
}

//connect to mysql
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("Database Error");
mysql_select_db($db_dbname) or die("Database Error");
	
//if this drawing is already saved, or if it doesn't belong to the logged in user,
//then somebody followed a dud link and we need to take them home
if (check_if_already_saved($_GET["id"]) || !check_drawing_owner($_GET["id"],$_SESSION["user"])){
	header('Location: menu.php');
	die();

}

$result=mysql_query("SELECT * FROM animations WHERE id = '" . mysql_real_escape_string($_GET["id"]) . "'") or die(mysql_error());
$row=mysql_fetch_array($result) or die(mysql_error());

$title="Editing ".$row["name"];

?>
<!DOCTYPE HTML>
<html>
<head>

<link rel="stylesheet" type="text/css" href="default.css" />
<title><?php echo $title;?></title>
<!-- Facebook open graph -->
<?php build_og($title); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body class="animation">

<h1><?php echo $title;?></h1>

<div class="containerButtonRow">
	<div style="float:left;width:330px;">
		<span class="controlTextTitle">drawing:</span>
		<span id='eraser' class="controlButton" title="toggle the eraser tool">eraser</span>
		<span id='pencil' class="controlButton" title="toggle the pencil tool">pencil</span>
		<input type="range" id="tool_opacity"  min="0" max="1.0" value="1.0" step="0.01" class="controlSlider" title="tool opacity"/>
		<span id='tool_opacity_display' class="controlDisplay" title="tool opacity">100</span>
		<span class="controlText">&#37;&nbsp;</span>
	</div>
	<div style="float:left;width:270px;">
		<span class="controlTextTitle">skins:</span>
		<span id='os_previous_toggle' class="controlButton" style="font-weight:bold;" title="toggle onion skinning for the previous frame">&#60;</span>&nbsp;
		<span id='os_next_toggle' class="controlButton" title="toggle onion skinning for the next frame">&#62;</span>&nbsp;
		<input type="range" id="os_opacity"  min="0" max="1.0" value="1.0" step="0.01" class="controlSlider" title="onion skinning opacity"/>
		<span id='os_opacity_display' class="controlDisplay" title="onion skinning opacity">100</span>
		<span class="controlText">&#37;&nbsp;</span>
	</div>
</div>
<div class="containerButtonRow">
	<div style="float:left;width:330px;">
		<span class="controlTextTitle">timeline:</span>
		<span id='frame_back' class="controlButton" title="previous frame">|&#60;</span>
		<span id='frame_current_display' class="controlDisplay" title="current frame">0</span>
		<span id='play' class="controlButton"  title="toggle animation preview">&#62;</span>&nbsp;
		<span id='frame_forward' class="controlButton" title="next frame">&#62;|</span>
	</div>
	<div style="float:left;width:270px;">
		<span class="controlTextTitle">frames:</span>
		<span id='frame_add_after' class="controlButton" title="add a new frame after the current one">_+</span>
		<span id='frame_add_before' class="controlButton" title="add a new frame before the current one">+_</span>
		<span id='frame_remove' class="controlButton" title="remove the current frame">-</span>
		<span id='frame_shift_backward' class="controlButton" title="shift the current frame backward one frame">&#60;1</span>
		<span id='frame_shift_forward' class="controlButton" title="shift the current frame forward one frame">1&#62;</span>
	</div>
</div>

<div class="containerRow"><br /></div>

<div class="containerRow">
	<div id="draw_container" class="containerCanvas"> 
		<canvas id="draw_canvas" width="400" height="300" class="drawing" title="draw here">Your browser does not support canvas.
		<a href='http://www.google.com/chrome/intl/en/landing_chrome.html'>Google Chrome</a> will work.
		</canvas>
	</div>
	<div id="dope_container" class="containerDope"> 
		<input id='dope_current' class="dopeCurrent" type="text" name="dope_current" width="150" title="current cell name"/>
		<br />
		<select id="dope_sheet" class="dopeSheet" size="16" width="150" title="dope sheet">
			<option value="0">0</option>
		</select>
	</div>
</div>

<div class="containerRow"><br /></div>

<div class="containerRow">
	<span id='finish_discard' class="controlButtonLarge" style="float:right;" title="discard changes and go home">discard</span>&nbsp;
	<span id='finish_reload' class="controlButtonLarge" style="float:right;" title="start over on the same animation">start over</span>&nbsp;
	<span id='finish_save' class="controlButtonLarge" style="float:right;" title="save changes and go home">save and finish</span>&nbsp;&nbsp;&nbsp;
</div>

<div class="containerRow">
	<span id='status_line'></span>
</div>

<script type="text/javascript" src="io.js"></script>
<script type="text/javascript" src="draw.js"></script>
<script type="text/javascript" src="dope.js"></script>

<script type="text/javascript">
//init drawing functions
init_draw();
//initialise io
init_io();
//then the dope sheet
init_dope();
</script>
<?php
//build page footer
build_footer();
?>
</body>
</html>
