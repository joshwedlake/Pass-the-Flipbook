<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//helper functions for the player, called by ajax

// Initialize the session.
session_start();

//includes
include("vars.php");
include("build_links.php");

//called with
//$_POST['id']= the sequence id to collect
//returns the json_encode d frame stack and updates the play count and last viewed

//fail codes
//1 id not set
//2 mysql error

function get_stack($id,$frames){
	//given the id of a sequence, build a list of the frames
	//frames is an array (reverse ordered)
	//assumes already connected to animations
	
	//important:do not apply the flag filter here
	$result = mysql_query("SELECT * FROM animations WHERE id='".mysql_real_escape_string($id)."'");
	$row = mysql_fetch_array($result);
	
	//append the frames to the end
	for($i=($row["frames_this"]-1);$i>=0;$i--){
		$frames[]=array($id,$i);
	}
	
	if($row["parent_seq_id"]==-1) return $frames;
	else return get_stack($row["parent_seq_id"],$frames);
}

if(isset($_POST["id"])){
	//connect to db
	mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	mysql_select_db($db_dbname) or die(json_encode(array("success"=>2,"error"=>mysql_error())));

	//lookup the animation's info
	$result = mysql_query("SELECT * FROM animations WHERE id='".mysql_real_escape_string($_POST["id"])."'");
	$row = mysql_fetch_array($result);

	//update the play count and the last played date
	$last_played=date("c");
	mysql_query("UPDATE animations SET viewed='".strval(intval($row["viewed"])+1)."' where id ='".mysql_real_escape_string($_POST["id"])."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	mysql_query("UPDATE animations SET date_last_viewed='".mysql_real_escape_string($last_played)."' where id ='".mysql_real_escape_string($_POST["id"])."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));

	//get the frame stack
	$frame_stack=get_stack(intval($_POST["id"]),array());

	//if the user is logged in, get the flag and thumb status
	$is_thumbed_up=false;
	$is_thumbed_down=false;
	$is_flagged=false;
	
	//return the data
	echo json_encode(array("success"=>0,"frame_stack"=>$frame_stack));
}
else die(json_encode(array("success"=>1)));
	


?>