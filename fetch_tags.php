<?php

//helper functions only

//add tag
function add_named_tag_animation($tag_name,$seq_id){
	//check the tag isn't already in tags
	$tag_result=@mysql_query("SELECT id FROM tags WHERE name='".$tag_name."'") or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	if(mysql_num_rows($tag_result)==0){
		//if it is, return add_tag_animation with the tag id
		//allocates an id for the tag
		$blank_array=serialize(array());
		$query="INSERT INTO tags(name,animations,synonyms,count) VALUES('".
			mysql_real_escape_string($tag_name)."','".
			mysql_real_escape_string($blank_array)."','".
			mysql_real_escape_string($blank_array)."','0')";
		mysql_query($query) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		$tag_id=mysql_insert_id();
		//then calls add_tag_animation
		return add_tag_animation($tag_id,$seq_id);
	}
	else{
		$tag_row = mysql_fetch_array($tag_result);
		return add_tag_animation($tag_row["id"],$seq_id);
	}
}


//adds a tag to an animation
//must update the animations field of the tag, and the tags field of the animation, and the use count of the tag
function add_tag_animation($tag_id,$seq_id){
	//get the animations field of the tag
	$tag_result=mysql_query("SELECT animations,count FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$animations=unserialize($tag_row["animations"]);
	if(!is_array($animations))$animations=array();
	//if the seq_id isn't already in the animations, add it
	if(!in_array($seq_id,$animations))$animations[]=$seq_id;
	//up the tag count
	$count=intval($tag_row["count"])+1;
	//save the tag's row
	mysql_query("UPDATE tags SET animations='".mysql_real_escape_string(serialize($animations))."' where id='".mysql_real_escape_string($tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	mysql_query("UPDATE tags SET count='".mysql_real_escape_string($count)."' where id='".mysql_real_escape_string($tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	//get the tags field of the animation
	$seq_result=mysql_query("SELECT tags FROM animations WHERE id=".$seq_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$seq_row = mysql_fetch_array($seq_result);
	$tags=unserialize($seq_row["tags"]);
	if(!is_array($tags))$tags=array();
	//if the tag_id isn't already in the tags, add it
	if(!in_array($tag_id,$tags))$tags[]=$tag_id;
	//save the animation's row
	mysql_query("UPDATE animations SET tags='".mysql_real_escape_string(serialize($tags))."' where id='".mysql_real_escape_string($seq_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	return json_encode(array("success"=>0));
}

//removes the tag from the animation's tags column, and the tag's animations column, and drops the tag's count by one
//must be the animation's owner to perform this
function remove_tag_animation($tag_id,$seq_id){
	//get the animations field of the tag
	$tag_result=mysql_query("SELECT animations FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$animations=unserialize($tag_row["animations"]);
	if(!is_array($animations))$animations=array();
	//if the seq_id isn't already in the animations, add it
	if(in_array($seq_id,$animations))$animations=array_diff($animations, array($seq_id));
	//down the tag count
	$count=intval($tag_row["count"])-1;
	//save the tag's row
	mysql_query("UPDATE tags SET animations='".mysql_real_escape_string(serialize($animations))."' where id='".mysql_real_escape_string($tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	mysql_query("UPDATE tags SET count='".mysql_real_escape_string($count)."' where id='".mysql_real_escape_string($tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	//get the tags field of the animation
	$seq_result=mysql_query("SELECT tags FROM animations WHERE id=".$seq_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$seq_row = mysql_fetch_array($seq_result);
	$tags=unserialize($seq_row["tags"]);
	if(!is_array($tags))$tags=array();
	//if the tag_id isn't already in the tags, add it
	if(in_array($tag_id,$tags))$tags=array_diff($tags, array($tag_id));
	//save the animation's row
	mysql_query("UPDATE animations SET tags='".mysql_real_escape_string(serialize($tags))."' where id='".mysql_real_escape_string($seq_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	return json_encode(array("success"=>0));
}

//delete a tag permanently
function delete_tag($tag_id){
	//get the tag row
	$tag_result=mysql_query("SELECT animations FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$animations=unserialize($tag_row["animations"]);
	
	//searches through each animation in the serialised animations, removing the tag
	for($i=sizeof($animations)-1;$i>=0;$i--){
		//remove the tag from the $animations[$i]
		$seq_id=$animations[$i];
		$seq_result=mysql_query("SELECT tags FROM animations WHERE id=".$seq_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		$seq_row = mysql_fetch_array($seq_result);
		$tags=unserialize($seq_row["tags"]);
		if(!is_array($tags))$tags=array();
		//if the tag_id isn't already in the tags, add it
		if(in_array($tag_id,$tags))$tags=array_diff($tags, array($tag_id));
		//save the animation's row
		mysql_query("UPDATE animations SET tags='".mysql_real_escape_string(serialize($tags))."' where id='".mysql_real_escape_string($seq_id)."'")
			or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	}
	
	//deletes the tag row
	mysql_query("DELETE FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	return json_encode(array("success"=>0));
}

//for admins only?
//remove the synonym's id from the serialised synonyms data on the parent tag
//AND vica versa
function remove_synonym($parent_tag_id,$child_tag_id){
	//add the child to the parent
	$tag_result=mysql_query("SELECT synonyms FROM tags WHERE id=".$parent_tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$synonyms=unserialize($tag_row["synonyms"]);
	if(!is_array($synonyms))$synonyms=array();
	if(in_array($child_tag_id,$synonyms))$synonyms=array_diff($synonyms, array($child_tag_id));
	mysql_query("UPDATE tags SET synonyms='".mysql_real_escape_string(serialize($synonyms))."' where id='".mysql_real_escape_string($parent_tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	//add the parent to the child
	$tag_result=mysql_query("SELECT synonyms FROM tags WHERE id=".$child_tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$synonyms=unserialize($tag_row["synonyms"]);
	if(!is_array($synonyms))$synonyms=array();
	if(in_array($parent_tag_id,$synonyms))$synonyms=array_diff($synonyms, array($parent_tag_id));
	mysql_query("UPDATE tags SET synonyms='".mysql_real_escape_string(serialize($synonyms))."' where id='".mysql_real_escape_string($child_tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		
	return json_encode(array("success"=>0));
}

//adds the synonym's id to the serialised synonyms data on the parent tag
//AND vica versa
function add_synonym($parent_tag_id,$child_tag_id){
	//add the child to the parent
	$tag_result=mysql_query("SELECT synonyms FROM tags WHERE id=".$parent_tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$synonyms=unserialize($tag_row["synonyms"]);
	if(!is_array($synonyms))$synonyms=array();
	if(!in_array($child_tag_id,$synonyms))$synonyms[]=$child_tag_id;
	mysql_query("UPDATE tags SET synonyms='".mysql_real_escape_string(serialize($synonyms))."' where id='".mysql_real_escape_string($parent_tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	
	//add the parent to the child
	$tag_result=mysql_query("SELECT synonyms FROM tags WHERE id=".$child_tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$synonyms=unserialize($tag_row["synonyms"]);
	if(!is_array($synonyms))$synonyms=array();
	if(!in_array($parent_tag_id,$synonyms))$synonyms[]=$parent_tag_id;
	mysql_query("UPDATE tags SET synonyms='".mysql_real_escape_string(serialize($synonyms))."' where id='".mysql_real_escape_string($child_tag_id)."'")
		or die(json_encode(array("success"=>2,"error"=>mysql_error())));
		
	return json_encode(array("success"=>0));
}

//return $count tags ordered by count
function get_popular_tags($max){
	if(!isset($max) || (isset($max) && $max==0))$max=10;
	$tag_result=mysql_query("SELECT * FROM tags ORDER BY -count LIMIT ".$max) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$data=array();
	while($tag_row = mysql_fetch_array($tag_result))$data[]=$tag_row;
	return $data;
}

//get tags data given a seq_id
function get_tags_from_animation($seq_id){
	//lookup the serialised tags column from the animation
	$animation_result=mysql_query("SELECT tags FROM animations WHERE id=".$seq_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$animation_row = mysql_fetch_array($animation_result);
	$tags=unserialize($animation_row["tags"]);
	if(is_array($tags)){
		//resolve each tag_id
		$data=array();
		foreach($tags as $tag_id){
			//lookup the tag
			$tag_result=mysql_query("SELECT * FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
			$tag_row = mysql_fetch_array($tag_result);
			$data[]=$tag_row;
		}
		return $data;
	}
	else return array();
}

//get animations data given a tag_id
function get_animations_from_tag($tag_id){
	//lookup the serialised animations column from the tag
	$tag_result=mysql_query("SELECT animations FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$animations=unserialize($tag_row["animations"]);
	if(is_array($animations)){
		//resolve each seq_id
		$data=array();
		foreach($animations as $seq_id){
			//lookup the seq id
			$animation_result=mysql_query("SELECT * FROM animations WHERE id=".$seq_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
			$animation_row = mysql_fetch_array($animation_result);
			$animation_row['ago']=ago(strtotime($animation_row['date_created']));
			$data[]=$animation_row;
		}
		return $data; //just an array, not jsoned
	}
	else return array(); //just an array, not jsoned
}

function get_synonyms_from_tag($tag_id){
	//lookup the serialised synonyms column from the tag
	$tag_result=mysql_query("SELECT synonyms FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
	$tag_row = mysql_fetch_array($tag_result);
	$synonyms=unserialize($tag_row["synonyms"]);
	if(is_array($synonyms)){
		//resolve each tag_id
		$data=array();
		foreach($synonyms as $tag_id){
			//lookup the seq id
			$tag_result=mysql_query("SELECT * FROM tags WHERE id=".$tag_id) or die(json_encode(array("success"=>2,"error"=>mysql_error())));
			$tag_row = mysql_fetch_array($tag_result);
			$data[]=$tag_row;
		}
		return $data; //just an array, not jsoned
	}
	else return array(); //just an array, not jsoned
}




?>