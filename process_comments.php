<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();

//includes
include("vars.php");
include("build_links.php");
//fetch comments is located in fetch_data
include("fetch_data.php");

//called by ajax with POST

//called with
//$_POST["mode"]==0 -fetch comments for anim, return as "SUCCESS \n id= &user= &time_ago= &comment= 
//$_POST["mode"]==1 -fetch comments by user, return as "SUCCESS \n id= &seq_id= &seq_name= &time ago= &comment=
//$_POST["mode"]==2 -add comment return "SUCCESS \n id=
//$_POST["mode"]==3 -delete comment return "SUCCESS
//$_POST["mode"]==4 -toggle flag

//$_POST["user"]= //refers a user name, as given in session
//$_POST["seq_id"]= //refers to an animation
//$_POST["id"]= //refers to a comment [for deletion]
//$_POST["comment"]= //gives comment text

//sends back an array of
//"success" = code
//"error" = if error
//"id" = added comment id
//"comments" = array of comments

//fail codes
//SUCCESS=0 - successful
//SUCCESS=1 - wrong params
//SUCCESS=2 - mysql
//SUCCESS=3 - unauthorised

function add_comment($user,$seq_id,$comment){
	$query="INSERT INTO comments(user,seq_id,flags,date_created,comment) VALUES('"
		.mysql_real_escape_string($user)."','"
		.mysql_real_escape_string($seq_id)."','0','"
		.mysql_real_escape_string(date("c"))."','"
		.mysql_real_escape_string($comment)."')";
	$result=mysql_query($query) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	return json_encode(array("success"=>0,"id"=>mysql_insert_id()));
}

function remove_comment($user,$id){
	$query="DELETE FROM comments WHERE (id='".mysql_real_escape_string($id)."') and (user='".mysql_real_escape_string($user)."')";
	$result=mysql_query($query) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	return json_encode(array("success"=>0));
}

//functions
function update_count($id,$property,$count){
	mysql_query("UPDATE comments SET ".$property."='"
		.mysql_real_escape_string(strval($count))
		."' where id ='".mysql_real_escape_string(strval($id))."'") or die(json_encode(array("success"=>3,"error"=>mysql_error())));
}

function update_array($user,$property,$data_array){
	mysql_query("UPDATE animators SET ".$property."='"
		.mysql_real_escape_string(serialize($data_array))
		."' where user ='".mysql_real_escape_string($user)."'") or die(json_encode(array("success"=>3,"error"=>mysql_error())));
}

function flag_comment($id,$already_flagged){
	//toggles the flag's state
	
	//get the number of flags
	$comment_result=mysql_query("SELECT flags FROM comments WHERE id ='".mysql_real_escape_string(strval($id))."'") or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$comment_row = mysql_fetch_array($comment_result) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$flags = intval($comment_row["flags"]);
	
	//if its not set, then set it, otherwise unset it
	if(in_array($id,$already_flagged)){
	
		//update the comment's count
		$flags--;
		update_count($id,"flags",$flags);
		
		//remove the flag from the animators list
		$already_flagged = array_diff($already_flagged, array($id));
		
		//update the animators list
		update_array($_SESSION['user'],"flagged_comments",$already_flagged);
		
		//finish
		return json_encode(array("success"=>0));
	
	}
	// there is no flag, so add one
	else {
	
		//update the comment's count
		$flags++;
		update_count($id,"flags",$flags);
		
		//add the flag to the animators list
		$already_flagged [] = $id;
		
		//update the animators list
		update_array($_SESSION['user'],"flagged_comments",$already_flagged);
		
		//finish
		return json_encode(array("success"=>0));
	
	}
}

if(!isset($_POST["mode"])) die(json_encode(array("success"=>1)));
else {
	$mode=intval($_POST["mode"]);

	//connect to db
	mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	mysql_select_db($db_dbname) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	//if logged in
	if (isset($_SESSION['user']) && !is_anonymous($_SESSION['user'])){
		//get the list of comments the user has already flagged
		$user_result=mysql_query("SELECT flagged_comments FROM animators WHERE user ='".mysql_real_escape_string($_SESSION["user"])."'")
			or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		$user_row = mysql_fetch_array($user_result) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		$already_flagged=unserialize($user_row["flagged_comments"]);
		if(!is_array($already_flagged))$already_flagged=array();
	}
	else $already_flagged=array();

	//show flagged content or not
	if (isset($_SESSION['show_flagged'])) $show_flagged=$_SESSION['show_flagged'];
	else $show_flagged=1; //1=don't show flagged content

	if($show_flagged!=0) $flagged_filter=" and (flags=0)";
	else $flagged_filter=" ";
	
	if($mode==0){
		echo json_encode(fetch_comments_animation_data($_POST["seq_id"],$flagged_filter,$already_flagged));
	}
	else if($mode==1){
		echo json_encode(fetch_comments_user_data($_POST["user"],$flagged_filter,$already_flagged));
	}
	else if($mode==2){
		//add comment to db
		if (isset($_SESSION['user']) && !is_anonymous($_SESSION['user'])) echo add_comment($_SESSION['user'],$_POST["seq_id"],$_POST["comment"]);
		else die(json_encode(array("success"=>3)));
	}
	else if($mode==3){
		//remove comment
		if (isset($_SESSION['user']) && !is_anonymous($_SESSION['user'])) echo remove_comment($_SESSION['user'],$_POST["id"]);
		else die(json_encode(array("success"=>3)));
	}
	else if($mode==4){
		// toggle flag
		if (isset($_SESSION['user']) && !is_anonymous($_SESSION['user'])) echo flag_comment($_POST["id"],$already_flagged);
		else die(json_encode(array("success"=>3)));
	}
	
}


?>