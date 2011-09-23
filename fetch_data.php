<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//helper functions used by tree_view and planet_helper and process_comments
//build links must be included as well if ago is to be used

function search_up_tree($id,$data,$flagged_filter){
	//given the id of a sequence traverse the tree upwards
	//assumes already connected to animations
	$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (id='".mysql_real_escape_string($id)."')".$flagged_filter);
	$row = mysql_fetch_array($result);
		
	//append the row to the list
	$row["ago"]=ago(strtotime($row['date_created']));
	$data[]=$row;
	
	if($row["parent_seq_id"]==-1) return $data;
	//otherwise look up the tree, but with NO flagged filter
	else return search_up_tree($row["parent_seq_id"],$data,"");
}

function search_down_tree($id,$flagged_filter){
	//given the id of a sequence, find the children of the sequence
	$data=array();
		
	$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (parent_seq_id='".mysql_real_escape_string($id)."')".$flagged_filter);
	while($row = mysql_fetch_array($result)){
		$row["ago"]=ago(strtotime($row['date_created']));
		$data[]=$row;
	}
	
	return $data;
}

function get_parentless($stat_filter,$limit_filter,$flagged_filter){
	//get all parentless sequences
	$data=array();
	
	$result = mysql_query("SELECT * FROM animations WHERE (saved=0) and (parent_seq_id=-1)".$flagged_filter.$stat_filter.$limit_filter);
	while($row = mysql_fetch_array($result)){
		$row["ago"]=ago(strtotime($row['date_created']));
		$data[]=$row;
	}
	
	return $data;
}

//fetch comments as a mysql row dataset
function fetch_comments_animation_data($seq_id,$flagged_filter,$already_flagged){
	$data=array();
	$query = "SELECT * FROM comments WHERE (seq_id='".mysql_real_escape_string($seq_id)."')".$flagged_filter." ORDER BY date_created";
	$result=mysql_query($query) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$data["success"]=0;
	$data["comments"]=array();
	while($row = mysql_fetch_array($result)){
		//sends back ago, already_flagged, flags, id, seq_id, comment
		$row["ago"]=ago(strtotime($row['date_created']));
		$row["already_flagged"]=(in_array(intval($row["id"]),$already_flagged)?0:1);
		$data["comments"][]=$row;
	}
	return $data;
}

//fetch comments as a mysql row dataset
//untested!
function fetch_comments_user_data($user,$flagged_filter,$already_flagged){
	$data=array();
	$query = "SELECT * FROM comments WHERE (user='".mysql_real_escape_string($user)."')".$flagged_filter." ORDER BY date_created";
	$result=mysql_query($query) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$data["success"]=0;
	$data["comments"]=array();
	while($row = mysql_fetch_array($result)){
		//look up the animation's name
		$name=mysql_fetch_array(mysql_query("SELECT name FROM animations WHERE id='".mysql_real_escape_string($row["seq_id"])."' ORDER BY date_created"))
			or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		$row["seq_name"]=$name["name"];
		$row["ago"]=ago(strtotime($row['date_created']));
		$row["already_flagged"]=(in_array(intval($row["id"]),$already_flagged)?0:1);
		$data["comments"][]=$row;
	}
	return $data;
}


?>
