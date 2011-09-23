<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//call with $_GET["stat"]

//and with $_GET["show_start"]

//start the session
session_start();

//bail out right away if not called correctly
if(!isset($_GET["stat"])){
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

//options for stat
//date_created,frames_total,viewed,thumbs_score,random,date_last_viewed
if($_GET["stat"]=="random"){
	//random has its own query, everything else follows a pattern
	$query_request=
		"	SELECT  *										"
		."	FROM											"
		."		(											"
		."		SELECT  @cnt := COUNT(*) + 1,				"
		."				@lim := ".strval($display_thumb_limit)
		."		FROM    animations							"
		."	) set_vars										"
		."	STRAIGHT_JOIN									"
		."		(											"
		."		SELECT  *,									"
		."				@lim := @lim - 1					"
		."		FROM    animations							"
		."		WHERE   (@cnt := @cnt - 1)					"
		."				AND RAND(".strval(rand()).") < @lim / @cnt	"
		."				AND (saved=0)						"
		.$flagged_filter
		."	) choose_data									";
}
else{
	$query_request="SELECT * FROM animations WHERE (saved=0)"
		.$flagged_filter
		." ORDER BY -".mysql_real_escape_string($_GET["stat"])
		." LIMIT ".strval($show_start).","
		.strval($display_thumb_limit);
}

$stat_human_names=array(
	"date_created"=>"Recently Created",
	"frames_total"=>"Length",
	"viewed"=>"Play Count",
	"thumbs_score"=>"Rating",
	"random"=>"Random",
	"date_last_viewed"=>"Last Played",
);

$title="Browsing Animations by ".$stat_human_names[$_GET["stat"]];

?>
<!DOCTYPE HTML>
<html>
<head>

<link rel="stylesheet" type="text/css" href="default.css" />
<title>
<?php echo $title; ?>
</title>
<!-- Facebook open graph -->
<?php build_og($title); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>
<?php

//build page title
build_top_bar()

?>
<br /><br />
<a href="menu.php">&#60;&#60;back home</a>
<h2><?php echo $title;?></h2>
<p>
	<?php
	//more link - prev
	if($show_start>0 && $_GET["stat"]!="random"){
		$new_show_start=$show_start-$display_thumb_limit;
		if($new_show_start<0) $new_show_start=0;
		
		echo "<div class='listBlock'><div class='listMore'><a href='browse_by_stat.php?stat="
		.urlencode($_GET["stat"])."&show_start="
		.strval($new_show_start)."' class='moreLink'>&#60;&#60;previous</a></div></div>";
	}
	
	$result = mysql_query($query_request);
	
	$count=0;
	while($row = mysql_fetch_array($result))
		{
		echo "<div class='listBlock'>";
		build_links($row);
		echo planet_link($row['id'],$row['name'],$_GET["stat"]) . " by " . animator_link($row['animator']);
		
		//description after title
		if($_GET["stat"]=="date_created") echo " - " . ago(strtotime($row['date_created'])) . " ago";
		else if($_GET["stat"]=="frames_total") echo " - " . $row['frames_total'] . " frames";
		else if($_GET["stat"]=="viewed") echo " - " . $row['viewed'] . " plays";
		else if($_GET["stat"]=="thumbs_score") echo " - " . $row['thumbs_up'] . "++, " . $row['thumbs_down'] . "--";
		else if($_GET["stat"]=="random") echo " - " . $row['viewed'] . " plays";
		else if($_GET["stat"]=="date_last_viewed") echo " - " . ago(strtotime($row['date_last_viewed'])) . " ago";
		
		echo "</div>";
		$count++;
	}
	
	//more link - next
	if($count==$display_thumb_limit){
		echo "<div class='listBlock'><div class='listMore'><a href='browse_by_stat.php?stat="
		.urlencode($_GET["stat"])."&show_start="
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