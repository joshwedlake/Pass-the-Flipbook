<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//call with $_GET["animator"]
//and with $_GET["show_start"]

//start the session
session_start();

//bail out right away if not called correctly
if(!isset($_GET["animator"])){
	header('Location: menu.php');
	die();
}

//function defines
include("vars.php");
include("build_links.php");

//connect to the database
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(mysql_error());
mysql_select_db($db_dbname) or die(mysql_error());

//show flagged content or not
if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
else $show_flagged=1; //1=don't show flagged content

if($show_flagged!=0) $flagged_filter=" and (flags=0)";
else $flagged_filter=" ";

//show more
if (!isset($_GET["show_start"])) $show_start=0;
else $show_start=intval($_GET["show_start"]);

$title="Browsing Animations by ".html_safe($_GET["animator"]);

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
<body>
<?php
//build page title
build_top_bar();
?>
<br /><br />
<a href="menu.php">&#60;&#60;back home</a>
<h2><?php echo $title;?></h2>
<p>
	<?php
	//more link - prev
	if($show_start>0){
		$new_show_start=$show_start-$display_thumb_limit;
		if($new_show_start<0) $new_show_start=0;
		
		echo "<div class='listBlock'><div class='listMore'><a href='browse_by_animator.php?animator="
		.urlencode($_GET["animator"])."&show_start="
		.strval($new_show_start)."' class='moreLink'>&#60;&#60;previous</a></div></div>";
	}
	
	$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (animator='"
		.mysql_real_escape_string($_GET["animator"])
		."') "
		.$flagged_filter
		." ORDER BY -date_created LIMIT ".strval($show_start).",".strval($display_thumb_limit));
	
	$count=0;
	while($row = mysql_fetch_array($result))
		{
		echo "<div class='listBlock'>";
		build_links($row);
		echo planet_link($row['id'],$row['name'],"date_created") . " by " . animator_link($row['animator']) . " - " . ago(strtotime($row['date_created'])) . " ago";
		echo "</div>";
		$count++;
	}
	
	//more link - next
	if($count==$display_thumb_limit){
		echo "<div class='listBlock'><div class='listMore'><a href='browse_by_animator.php?animator="
		.urlencode($_GET["animator"])."&show_start="
		.strval($show_start+$display_thumb_limit)."' class='moreLink'>next&#62;&#62;</a></div></div>";
	}

	?>
</p>
</div>
<?php
//build page footer
build_footer();
?>
</body>
</html>