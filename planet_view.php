<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();


//includes
include("vars.php");
include("build_links.php"); //for bottom bar

//connect to mysql
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("Database Error");
mysql_select_db($db_dbname) or die("Database Error");

//build flagged filter
if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
else $show_flagged=1; //1=don't show flagged content

if($show_flagged!=0) $flagged_filter=" and (flags=0)";
else $flagged_filter=" ";

//if we dont get sent anything for id, then go home
if(!isset($_GET["id"])){
	header('Location: menu.php');
}
else{
	$id=$_GET["id"];
	$result = mysql_query("SELECT name,animator FROM animations WHERE (saved=0) and (id='".mysql_real_escape_string($id)."')".$flagged_filter);
	$row = mysql_fetch_array($result);
	$seq_title=$row['name']." by ".$row['animator'];
}

if(!isset($_GET["stat"]))$stat="date_created";
else $stat=$_GET["stat"];

$title="Exploring ".html_safe($seq_title)." in Planet View";

?>
<!DOCTYPE HTML>
<html>
<head>

<link rel="stylesheet" type="text/css" href="default.css" />
<title>
<?php echo $title;?>
</title>
<!-- Facebook open graph -->
<?php build_og($title); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body style='width: 100%;height: 100%;margin: 0px;'>
<?php
//build page title
build_top_bar();
?>
<br /><br />
<div class="containerExpandableRow"><b>Planet View</b> shows which animations are based on each other.  Click planets to explore...
<!-- google +1 -->
<?php build_google_plus_one("small");?>
<!-- facebook controls -->
<?php build_fb_like(); ?>
</div>
<!-- planets here -->

<div class="containerExpandableRow">
<canvas id="planet_canvas" class="player" width="400" height="300" class="player">Your browser does not support canvas.  <a href='http://www.google.com/chrome/intl/en/landing_chrome.html'>Google Chrome</a> will work.</canvas>
</div>

<div id='status_line' class="containerRow"></div>

<script type="text/javascript" src="planets.js"></script>

<script type="text/javascript">
//get seq_id from php
seq_id=<?php echo $_GET["id"];?>;
//get the shelf_stat from php
shelf_stat="<?php echo $stat;?>";
//init drawing functions
init_planets();
</script>

<?php
//build page footer
build_footer();
?>
</body>
</html>