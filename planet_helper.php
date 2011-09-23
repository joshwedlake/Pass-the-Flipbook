<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();


//includes
include("vars.php");
include("build_links.php"); //needed for $ago
include("fetch_data.php");

//connect to mysql
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(json_encode(array("success"=>2)));
mysql_select_db($db_dbname) or die(json_encode(array("success"=>2)));

//build flagged filter
if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
else $show_flagged=1; //1=don't show flagged content

if($show_flagged!=0) $flagged_filter=" and (flags=0)";
else $flagged_filter=" ";

//if called with a parentless limit, build a filter
if(isset($_POST["parentless_limit"]))$limit_filter=" LIMIT ".$_POST["parentless_limit"];
else $limit_filter=" LIMIT 10";

//if called with a stat
if(isset($_POST["parentless_stat"]))$stat_filter=" ORDER BY -".$_POST["parentless_stat"];
else $stat_filter=" ORDER BY -date_created";

//called with id=planet
//returns a JSON encoded array
//"success"=see fail codes, 0 for success
//"this"=[this]
//"parent"=[parent] (array length 1) or [parentless,parentless,...]
//"children"=[child,child,child]
//"comments"=[comment,comment,comment]
//where this,parent,children and comment are all $row
//with the added human readable date in ["ago"]

//fail codes
//1 id not set
//2 mysql fail

if(isset($_POST["id"])){

	$planet_data=array();
	
	$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (id='".mysql_real_escape_string($_POST["id"])."')".$flagged_filter) or die(json_encode(array("success"=>2)));
	
	//this
	$row = mysql_fetch_array($result) or die(json_encode(array("success"=>2)));
	$row['ago']=ago(strtotime($row['date_created']));
	$planet_data["this"]=$row;
	
	//parent
	if(intval($planet_data["this"]["parent_seq_id"])==-1)$planet_data["parent"]=get_parentless($stat_filter,$limit_filter,$flagged_filter);
	else{
		//don't apply the flagged filter to parents
		$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (id='"
			.mysql_real_escape_string($planet_data["this"]["parent_seq_id"])."')") or die(json_encode(array("success"=>2)));
		$single_parent=mysql_fetch_array($result) or die(json_encode(array("success"=>2)));
		$single_parent["ago"]=ago(strtotime($single_parent['date_created']));
		$planet_data["parent"]=array($single_parent);
	}
	
	//children
	$planet_data["children"]=search_down_tree($_POST["id"],$flagged_filter);
	
	//comments
	//IGNORE FOR NOW
	//$planet_data["comments"]=fetch_comments_animation_data($_POST["id"],$flagged_filter,array());
	
	//success
	$planet_data["success"]=0;
	
	//send it back
	die(json_encode($planet_data));

}
else die(json_encode(array("success"=>1)));

?>