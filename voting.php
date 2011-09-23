<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//ajax, called with POST

//non-optional param:
//seq_id

//these params optional, all indicate change of status:
//thumbs_up=1
//thumbs_down=1
//flag=1

//fail codes
//FAIL1 - need to login first
//FAIL2 - give the sequence id
//FAIL3 - mysql error
//SUCCESS1 - added thumbs up
//SUCCESS2 - removed thumbs up
//SUCCESS3 - added thumbs down
//SUCCESS4 - removed thumbs down
//SUCCESS5 - switched thumb direction up>down
//SUCCESS6 - switched thumb direction down>up
//SUCCESS7 - added flag
//SUCCESS8 - removed flag


//start the session
session_start();

//includes
include("vars.php");

//functions
function update_count($seq_id,$property,$count){
	mysql_query("UPDATE animations SET ".$property."='"
		.mysql_real_escape_string(strval($count))
		."' where id ='".mysql_real_escape_string(strval($seq_id))."'") or die("FAIL3".mysql_error());
}

function update_array($user,$property,$data_array){
	mysql_query("UPDATE animators SET ".$property."='"
		.mysql_real_escape_string(serialize($data_array))
		."' where user ='".mysql_real_escape_string($user)."'") or die("FAIL3".mysql_error());
}

if (isset($_POST["seq_id"])){

	$seq_id=intval($_POST["seq_id"]);
	
	//voting actions are only allowed from logged in users
	if (isset($_SESSION['user']) && !is_anonymous($_SESSION['user'])) {
		//connect to mysql
		mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("1");
		mysql_select_db($db_dbname) or die("1");
		
		//get the animation's data
		$animation_result=mysql_query("SELECT flags,thumbs_up,thumbs_down FROM animations WHERE id ='".mysql_real_escape_string(strval($seq_id))."'");
		$animation_row = mysql_fetch_array($animation_result);
		$thumbs_up = intval($animation_row["thumbs_up"]);
		$thumbs_down = intval($animation_row["thumbs_down"]);
		$flags = intval($animation_row["flags"]);
		
		//get the user data
		$user_result=mysql_query("SELECT flagged,thumbed_up,thumbed_down FROM animators WHERE user ='".mysql_real_escape_string($_SESSION["user"])."'");
		$user_row = mysql_fetch_array($user_result);
		
		//get the users thumbed up,down and flags array
		$already_thumbed_up=unserialize($user_row["thumbed_up"]);
		if(!is_array($already_thumbed_up))$already_thumbed_up=array();
		
		$already_thumbed_down=unserialize($user_row["thumbed_down"]);
		if(!is_array($already_thumbed_down))$already_thumbed_down=array();
		
		$already_flagged=unserialize($user_row["flagged"]);
		if(!is_array($already_flagged))$already_flagged=array();

		//thumbs up
		if (isset($_POST["thumbs_up"]) && intval($_POST["thumbs_up"])==1){
			
			//if its already been thumbed up then remove thumbs up
			if(in_array($seq_id,$already_thumbed_up)){
				
				//update the animation's count
				$thumbs_up--;
				update_count($seq_id,"thumbs_up",$thumbs_up);
				update_count($seq_id,"thumbs_score",$thumbs_up-$thumbs_down);
				
				//remove the thumbs up from the animators list
				$already_thumbed_up = array_diff($already_thumbed_up, array($seq_id));
				
				//update the animators list
				update_array($_SESSION['user'],"thumbed_up",$already_thumbed_up);
					
				//finish
				die("SUCCESS2");
			}
			//if its already been thumbed down then remove it from thumbs down and add it to thumbs up
			else if(in_array($seq_id,$already_thumbed_down)){
			
				//update the animation's count
				$thumbs_down--;
				$thumbs_up++;
				update_count($seq_id,"thumbs_up",$thumbs_up);
				update_count($seq_id,"thumbs_down",$thumbs_down);
				update_count($seq_id,"thumbs_score",$thumbs_up-$thumbs_down);
				
				//remove the thumbs down from the animators list, and add a thumbs up
				$already_thumbed_down = array_diff($already_thumbed_down, array($seq_id));
				$already_thumbed_up[] = $seq_id;
				
				//update the animators lists
				update_array($_SESSION['user'],"thumbed_up",$already_thumbed_up);
				update_array($_SESSION['user'],"thumbed_down",$already_thumbed_down);

				die("SUCCESS6");
			}
			//otherwise its a straightforward case of adding it to thumbs up
			else {
				
				//update the animation's count
				$thumbs_up++;
				update_count($seq_id,"thumbs_up",$thumbs_up);
				update_count($seq_id,"thumbs_score",$thumbs_up-$thumbs_down);
				
				//and add thumbs up
				$already_thumbed_up[] = $seq_id;
				
				//update the animators lists
				update_array($_SESSION['user'],"thumbed_up",$already_thumbed_up);

				die("SUCCESS1");
			}

		}
		//thumbs down
		else if (isset($_POST["thumbs_down"]) && intval($_POST["thumbs_down"])==1){
			
			//if its already been thumbed down then remove thumbs down
			if(in_array($seq_id,$already_thumbed_down)){
				
				//update the animation's count
				$thumbs_down--;
				update_count($seq_id,"thumbs_down",$thumbs_down);
				update_count($seq_id,"thumbs_score",$thumbs_up-$thumbs_down);
				
				//remove the thumbs down from the animators list
				$already_thumbed_down = array_diff($already_thumbed_down, array($seq_id));
				
				//update the animators list
				update_array($_SESSION['user'],"thumbed_down",$already_thumbed_down);
					
				//finish
				die("SUCCESS4");
			}
			//if its already been thumbed up then remove it from thumbs up and add it to thumbs down
			else if(in_array($seq_id,$already_thumbed_up)){
			
				//update the animation's count
				$thumbs_up--;
				$thumbs_down++;
				update_count($seq_id,"thumbs_up",$thumbs_up);
				update_count($seq_id,"thumbs_down",$thumbs_down);
				update_count($seq_id,"thumbs_score",$thumbs_up-$thumbs_down);
				
				//remove the thumbs up from the animators list, and add a thumbs down
				$already_thumbed_up = array_diff($already_thumbed_up, array($seq_id));
				$already_thumbed_down[] = $seq_id;
				
				//update the animators lists
				update_array($_SESSION['user'],"thumbed_up",$already_thumbed_up);
				update_array($_SESSION['user'],"thumbed_down",$already_thumbed_down);

				die("SUCCESS5");
			}
			//otherwise its a straightforward case of adding it to thumbs down
			else {
				
				//update the animation's count
				$thumbs_down++;
				update_count($seq_id,"thumbs_down",$thumbs_down);
				update_count($seq_id,"thumbs_score",$thumbs_up-$thumbs_down);
				
				//and add thumbs down
				$already_thumbed_down[] = $seq_id;
				
				//update the animators lists
				update_array($_SESSION['user'],"thumbed_down",$already_thumbed_down);

				die("SUCCESS3");
			}
		}
		//flag
		else if  (isset($_POST["flag"]) && intval($_POST["flag"])==1){
			//flag is a bit simpler
			//if there is already flag, remove it
			if(in_array($seq_id,$already_flagged)){
			
				//update the animation's count
				$flags--;
				update_count($seq_id,"flags",$flags);
				
				//remove the flag from the animators list
				$already_flagged = array_diff($already_flagged, array($seq_id));
				
				//update the animators list
				update_array($_SESSION['user'],"flagged",$already_flagged);
				
				//finish
				die("SUCCESS8");
			
			}
			// there is no flag, so add one
			else {
			
				//update the animation's count
				$flags++;
				update_count($seq_id,"flags",$flags);
				
				//add the flag to the animators list
				$already_flagged [] = $seq_id;
				
				//update the animators list
				update_array($_SESSION['user'],"flagged",$already_flagged);
				
				//finish
				die("SUCCESS7");
			
			}
		}
	}
	else die("FAIL1");
}
else die("FAIL2");

?>