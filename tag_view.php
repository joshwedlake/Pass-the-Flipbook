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
if(isset($_GET["seq_id"])){
	$seq_id=intval($_GET["seq_id"]);
	$tag_id=-1;
	$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (id='".mysql_real_escape_string($seq_id)."')".$flagged_filter);
	$row = mysql_fetch_array($result);
	$row['ago']=ago(strtotime($row['date_created']));
	$title="Tagging ".html_safe($row['name']." by ".$row['animator']);
	
}
else if(isset($_GET["tag_id"])){
	$seq_id=-1;
	$tag_id=intval($_GET["tag_id"]);
	$result = mysql_query("SELECT * FROM tags WHERE (id='".mysql_real_escape_string($tag_id)."')");
	$row = mysql_fetch_array($result);
	$title="Animations tagged with ".html_safe($row['name']);
}
else header('Location: menu.php');


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
<div class="containerExpandableRow"><b>Tagger</b> Click items to expand, drag to tag...
<!-- google +1 -->
<?php build_google_plus_one("small");?>
<!-- facebook controls -->
<?php build_fb_like(); ?>
</div>
<!-- planets here -->

<div class="containerExpandableRow">
<canvas id="tags_canvas" class="player" width="400" height="300" class="player">Your browser does not support canvas.  <a href='http://www.google.com/chrome/intl/en/landing_chrome.html'>Google Chrome</a> will work.</canvas>
</div>

<div id='status_line' class="containerRow"></div>

<script type="text/javascript" src="tags.js"></script>

<script type="text/javascript">
//get seq_id from php
seq_id=<?php echo $seq_id;?>;
//get the tag_id from php
tag_id=<?php echo $tag_id;?>;
//get the first item data from php
first_item_data=JSON.parse(<?php echo json_encode(json_encode($row));?>);
//init tag view
init_tags();
</script>

<?php
//build page footer
build_footer();
?>
</body>
</html>