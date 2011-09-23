<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();

//called with $_GET["id"] for the sequence id currently expanded
//can be called with -1
//bail out right away if not called correctly
if(!isset($_GET["id"])){
	header('Location: menu.php');
	die();
}

//includes
include("vars.php");
include("build_links.php");
include("fetch_data.php");

//connect to mysql
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("Database Error");
mysql_select_db($db_dbname) or die("Database Error");

//works up and down the tree

function draw_vertical_line($start_x,$start_y,$end_y){
	//add a 'tree' line
	echo "<div style='position:absolute;left:"
		.strval($start_x)."px;top:"
		.strval($start_y)."px;width:3px;height:"
		.strval($end_y-$start_y)."px;border-left:1px solid;'></div>";
}

function draw_horizontal_line($start_x,$start_y,$end_x){
	//add a 'tree' line
	echo "<div style='position:absolute;left:"
		.strval($start_x)."px;top:"
		.strval($start_y)."px;height:3px;width:"
		.strval($end_x-$start_x)."px;border-top:1px solid;'></div>";
}

//show flagged content or not
if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
else $show_flagged=1; //1=don't show flagged content

if($show_flagged!=0) $flagged_filter=" and (flags=0)";
else $flagged_filter=" ";

if(intval($_GET["id"])!=-1){
	$up_data=search_up_tree($_GET["id"],array(),$flagged_filter);
	$seq_title=$up_data[0]["name"];
}
else {
	$up_data=array();
	$seq_title="Everything";
}
$down_data=search_down_tree($_GET["id"],$flagged_filter);

$title="Exploring ".html_safe($seq_title)." in Tree View";
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

<?php
//lay the page out in terms of absolute coordinates
$loc_x=50;
$loc_y=150;
$interval_x=100;
$interval_y=190;
//use the css class listBlock

//the root item
echo "<div class='listBlock' style='position:absolute;left:".strval($loc_x)."px;top:".strval($loc_y)."px;'>"
	."<div class='listMore'><a href='tree_view.php?id=-1' class='moreLink'>[+]</a></div></div>";

$loc_x+=$interval_x;
$loc_y+=$interval_y;

//looping over the updata
for($i=(count($up_data)-1);$i>=0;$i--){
	//draw the connection from the last cell
	draw_vertical_line(($loc_x-$interval_x)+20,($loc_y-$interval_y)+192,$loc_y+185);
	draw_horizontal_line(($loc_x-$interval_x)+20,$loc_y+185,$loc_x);
	//draw the div cell
	echo "<div class='listBlock' style='position:absolute;left:".strval($loc_x)."px;top:".strval($loc_y)."px;'>";
	build_links($up_data[$i]);
	echo tree_link($up_data[$i]['id'],$up_data[$i]['name']) . " by " . animator_link($up_data[$i]['animator']) . " - " . $up_data[$i]['ago'] . " ago";
	echo "</div>";
	$loc_x+=$interval_x;
	$loc_y+=$interval_y;
}

$child_count=count($down_data);

if($child_count>0){
	//draw the connection from the last cell
	draw_vertical_line(($loc_x-$interval_x)+20,($loc_y-$interval_y)+192,$loc_y+185);
	draw_horizontal_line(($loc_x-$interval_x)+20,$loc_y+185,$loc_x);
}

//loop over the downdata
for($i=0;$i<$child_count;$i++){
	//draw the div cell
	echo "<div class='listBlock' style='position:absolute;left:".strval($loc_x)."px;top:".strval($loc_y)."px;'>";
	build_links($down_data[$i]);
	echo tree_link($down_data[$i]['id'],$down_data[$i]['name']) . " by " . animator_link($down_data[$i]['animator']) . " - " . $down_data[$i]['ago'] . " ago";
	echo "</div>";
	$loc_y+=$interval_y;
	
	//draw the connection to the next cell, if there is one
	if($i<($child_count-1)){
		draw_vertical_line(($loc_x-$interval_x)+20,($loc_y-$interval_y)+40,$loc_y+185);
		draw_horizontal_line(($loc_x-$interval_x)+20,$loc_y+185,$loc_x);
	}
}

//add a blank div just to make the page scrollable
$loc_x+=$interval_x/2;
$loc_y+=$interval_y/2;
echo "<div style='position:absolute;left:".strval($loc_x)."px;top:".strval($loc_y)."px;'>&nbsp;</div>";

//build page footer
build_footer();
?>
</body>
</html>