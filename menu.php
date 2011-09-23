<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();

//function defines
include("vars.php");
include("build_links.php");

//show flagged content or not
if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
else $show_flagged=1; //1=don't show flagged content

if($show_flagged!=0) $flagged_filter=" and (flags=0)";
else $flagged_filter=" ";

//connect to the database
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(mysql_error());
mysql_select_db($db_dbname) or die(mysql_error());

?>
<!DOCTYPE HTML>
<html>
<head>

<link rel="stylesheet" type="text/css" href="default.css" />
<meta name="description" content="Pass the Flipbook, Drink and Draw, The Lightbox Swinger's Club, Collaborative Animation Tools, Online Pencil Tests">
<meta name="keywords" content="animation,video,pencil test,line test,flipbook,animation tools,animation software,animator's club">
<title>
Pass The Flipbook
</title>

<!-- Facebook open graph -->
<?php build_og("Pass The Flipbook"); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>

<?php

//build page title
build_top_bar();
//just start drawing
echo "<h1>";
if(isset($_SESSION['user']))echo "<a href='create_new_animation.php?&ps_id=-1' class='controlButton'>";
else echo "<a href='login.php?redir=".urlencode("create_new_animation.php?&ps_id=-1")."' class='controlButton'>";
echo "Just Start Drawing!</a>";
//build the "go interplanetary" button
$most_children_result=mysql_query("SELECT parent_seq_id FROM (SELECT parent_seq_id,COUNT(*) as cnt FROM `animations` WHERE saved=0".$flagged_filter." GROUP BY parent_seq_id)t ORDER BY -cnt LIMIT 1,1");
if($most_children_row = mysql_fetch_array($most_children_result)){
	$parent_to_load=$most_children_row["parent_seq_id"];
	//button to jump to planets with $parent_to_load in place
	echo "<div style='float:left;'>&nbsp;&nbsp;or&nbsp;&nbsp;</div><a href='planet_view.php?id=".strval($parent_to_load)."' class='controlButton'>Go Planetary</a>";
}
echo "</h1>";

?>

<div class="menuRow">
	<h2><a href='browse_by_stat.php?stat=date_created'>Recently Created</a></h2>
	<p>
		<?php
		$result = mysql_query("SELECT * FROM animations WHERE (saved=0)"
			.$flagged_filter
			." ORDER BY -date_created LIMIT 5");
		
		while($row = mysql_fetch_array($result))
			{
			echo "<div class='menuBlock'>";
			build_links($row);
			echo planet_link($row['id'],$row['name'],"date_created") . " by " . animator_link($row['animator']) . " - " . ago(strtotime($row['date_created'])) . " ago";
			echo "</div>";
		}

		?>
	</p>
</div>

<div class="menuRow">
	<h2><a href='browse_by_stat.php?stat=frames_total'>Longest Animations</a></h2>
	<p>
		<?php
		$result = mysql_query("SELECT * FROM animations WHERE (saved=0)"
			.$flagged_filter
			." ORDER BY -frames_total LIMIT 5");

		while($row = mysql_fetch_array($result))
			{
			echo "<div class='menuBlock'>";
			build_links($row);
			echo planet_link($row['id'],$row['name'],"frames_total") . " by " . animator_link($row['animator']) . " - " . $row['frames_total'] . " frames";
			echo "</div>";
		}

		?>
	</p>
</div>


<div class="menuRow">
	<h2><a href='browse_by_stat.php?stat=viewed'>Most Viewed</a></h2>
	<p>
		<?php
		$result = mysql_query("SELECT * FROM animations WHERE (saved=0)"
			.$flagged_filter
			." ORDER BY -viewed LIMIT 5");

		while($row = mysql_fetch_array($result))
			{
			echo "<div class='menuBlock'>";
			build_links($row);
			echo planet_link($row['id'],$row['name'],"viewed") . " by " . animator_link($row['animator']) . " - " . $row['viewed'] . " plays";
			echo "</div>";
		}

		?>
	</p>
</div>

<div class="menuRow">
	<h2><a href='browse_by_stat.php?stat=thumbs_score'>Top Rated</a></h2>
	<p>
		<?php
		$result = mysql_query("SELECT * FROM animations WHERE (saved=0)"
			.$flagged_filter
			." ORDER BY -thumbs_score LIMIT 5");

		while($row = mysql_fetch_array($result))
			{
			echo "<div class='menuBlock'>";
			build_links($row);
			echo planet_link($row['id'],$row['name'],"thumbs_score") . " by " . animator_link($row['animator']) . " - " . $row['thumbs_up'] . "++, " . $row['thumbs_down'] . "--";
			echo "</div>";
		}

		?>
	</p>
</div>

<div class="menuRow">
	<h2><a href='browse_by_stat.php?stat=random'>Random</a></h2>
	<p>
		<?php
		$random_query=
		"	SELECT  *										" // Select all data
		."	FROM											" // from...
		."		(											" // the sub query...
		."		SELECT  @cnt := COUNT(*) + 1,				" // which generates the total row count
		."				@lim := 5							" // and sets the number of random items we want
		."		FROM    animations							" // when selecting the table animations.
		."	) set_vars										"
		."	STRAIGHT_JOIN									" // make sure set_vars executes before choose data
		."		(											"
		."		SELECT  *,									" //select everything in the row
		."				@lim := @lim - 1					" //evaluated on select - ie reduce the number left needed to select
		."		FROM    animations							" //using the animations table
		."		WHERE   (@cnt := @cnt - 1)					" //this expression is in a where, evaluated on each row - count is zero indexed
		."				AND RAND(".rand().") < @lim / @cnt	" //random (re-seeded by php each refresh) is less than the number left to select/count of remaining items
		."				AND (saved=0)						" //only saved
		.$flagged_filter
		."	) choose_data									";

		$result = mysql_query($random_query);

		while($row = mysql_fetch_array($result))
			{
			echo "<div class='menuBlock'>";
			build_links($row);
			echo planet_link($row['id'],$row['name'],"date_created") . " by " . animator_link($row['animator']); //just do date created... too much work to sort random on planets
			echo "</div>";
		}

		?>
	</p>
</div>

<div class="menuRow">
	<h2><a href='browse_by_stat.php?stat=date_last_viewed'>Recently Viewed</a></h2>
	<p>
		<?php
		$result = mysql_query("SELECT * FROM animations WHERE (saved=0)"
			.$flagged_filter
			." ORDER BY -date_last_viewed LIMIT 5");

		while($row = mysql_fetch_array($result))
			{
			echo "<div class='menuBlock'>";
			build_links($row);
			echo planet_link($row['id'],$row['name'],"date_viewed") . " by " . animator_link($row['animator']) . " - " . ago(strtotime($row['date_last_viewed'])) . " ago";
			echo "</div>";
		}

		?>
	</p>
</div>

<div class="menuRow"><br /></div>

<?php
//build page footer
build_footer();
?>
</body>
</html>