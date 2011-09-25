<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();

//includes
include("vars.php");
include("fetch_tags.php");
include("build_links.php"); //needed for $ago

//error codes
//0 success
//1 missing call info
//2 mysql error

//if mode isn't set, die
if(!isset($_POST["mode"]))die(json_encode(array("success"=>1,"error"=>"mode not set")));

//connect to mysql
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(json_encode(array("success"=>2)));
mysql_select_db($db_dbname) or die(json_encode(array("success"=>2)));

//build flagged filter
if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
else $show_flagged=1; //1=don't show flagged content

if($show_flagged!=0) $flagged_filter=" and (flags=0)";
else $flagged_filter=" ";


//called with
//mode =
//	0 add a tag to an animation by name
//	1 add a tag to an animation by id
//	2 remove a tag animation pair (by id)
//	3 delete a tag permanently, by id
//	4 remove a synonym by parent and child id
//	5 add a synonym by parent and child id
//	6 get popular tags
//	7 get an animation's tags
//	8 get all animations with a certain tag
//	9 get synonyms of a particular tag
// 	10 get everything attached to a particular tag
//$_POST["tag_id"],$_POST["tag_name"],$_POST["seq_id"],$_POST["parent_tag_id"],$_POST["child_tag_id"]

switch(intval($_POST["mode"])){
	case 0:
		//add tag by name
		if(!isset($_POST["tag_name"]) || !isset($_POST["seq_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo add_named_tag_animation($_POST["tag_name"],intval($_POST["seq_id"]));
		break;
	case 1:
		//add tag by id
		if(!isset($_POST["tag_id"]) || !isset($_POST["seq_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo add_tag_animation(intval($_POST["tag_id"]),intval($_POST["seq_id"]));
		break;
	case 2:
		//remove tag by id
		if(!isset($_POST["tag_id"]) || !isset($_POST["seq_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo remove_tag_animation(intval($_POST["tag_id"]),intval($_POST["seq_id"]));
		break;
	case 3:
		//delete tag permanently
		if(!isset($_POST["tag_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo delete_tag(intval($_POST["tag_id"]));
		break;
	case 4:
		//remove synonym
		if(!isset($_POST["parent_tag_id"]) || !isset($_POST["child_tag_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo remove_synonym(intval($_POST["parent_tag_id"]),intval($_POST["child_tag_id"]));
		break;
	case 5:
		//add synonym
		if(!isset($_POST["parent_tag_id"]) || !isset($_POST["child_tag_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo add_synonym(intval($_POST["parent_tag_id"]),intval($_POST["child_tag_id"]));
		break;
		
	//-------------the rest handle the json encode themselves.
	case 6:
		//get popular tags (eg for tag cloud)
		echo json_encode(array("success"=>0,
			"tags"=>get_popular_tags(10)));
		break;
	case 7:
		//get all the tags attached to an animation
		if(!isset($_POST["seq_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo json_encode(array("success"=>0,
			"call_seq_id"=>intval($_POST["seq_id"]),
			"tags"=>get_tags_from_animation(intval($_POST["seq_id"]))));
		break;
	case 8:
		//get all the animations attached to a certain tag
		if(!isset($_POST["tag_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo json_encode(array("success"=>0,
			"call_tag_id"=>intval($_POST["tag_id"]),
			"animations"=>get_animations_from_tag(intval($_POST["tag_id"]))));
		break;
	case 9:
		//get all the synonyms of a tag
		if(!isset($_POST["tag_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo json_encode(array("success"=>0,
			"call_tag_id"=>intval($_POST["tag_id"]),
			"synonyms"=>get_synonyms_from_tag(intval($_POST["tag_id"]))));
		break;
	case 10:
		//get all the animations and synonyms attached to a certain tag
		if(!isset($_POST["tag_id"])) die(json_encode(array("success"=>1,"error"=>"params missing")));
		echo json_encode(array("success"=>0,
			"call_tag_id"=>intval($_POST["tag_id"]),
			"animations"=>get_animations_from_tag(intval($_POST["tag_id"])),
			"synonyms"=>get_synonyms_from_tag(intval($_POST["tag_id"]))));
		break;
		
}



?>