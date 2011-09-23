<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//called by ajax

// Inialize session
session_start();

//call with POST
//user
//pass

//db has
//user
//pass
//date_joined
//last_login

//returns
//0 > success
//1 > mysql error
//2 > username taken (registration)
//3 > login incorrect
//4 > captcha incorrect

include("vars.php");
require_once('recaptchalib.php');

// Make a MySQL Connection
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("1".mysql_error());
mysql_select_db($db_dbname) or die("1".mysql_error());

//for last login date
$date_now=date("c");

//are we logging in or registering
if(isset($_POST["register"]) && $_POST["register"]=="1"){
	//first time registering, check the captcha first
	$resp = recaptcha_check_answer ($privatekey,
		$_SERVER["REMOTE_ADDR"],
		$_POST["r_chal"],
		$_POST["r_resp"]);

	//if its invalid, bail out
	if (!$resp->is_valid) die ("4");
	else {
		if(mysql_num_rows(@mysql_query("SELECT * FROM animators WHERE user ='".$_POST["user"]."'"))){
			//username already in table
			die("2");
		}
		else {
			//create the username
			$blank_array=serialize(array());
			$query="INSERT INTO animators(user,human_user,pass,date_joined,last_login,flagged,thumbed_up,thumbed_down,flagged_comments) VALUES('".
				mysql_real_escape_string($_POST['user'])."','".
				mysql_real_escape_string($_POST['user'])."','".
				mysql_real_escape_string(md5($_POST['pass']))."','".
				mysql_real_escape_string($date_now)."','".
				mysql_real_escape_string($date_now)."','".
				mysql_real_escape_string($blank_array)."','".
				mysql_real_escape_string($blank_array)."','".
				mysql_real_escape_string($blank_array)."','".
				mysql_real_escape_string($blank_array)."')";
			mysql_query($query) or die("1");
			// Set username session variable
			$_SESSION['user'] = $_POST['user'];
			if(isset($_POST['show_flagged']))$_SESSION['show_flagged'] = intval($_POST['show_flagged']); //0 is true
			else $_SESSION['show_flagged']=1;
			//successful, so return success
			die("0");
		}
	}
}	
else {
	//if the user is logging in anonymously, then don't check the db - they don't have the same privelidges
	if ($_POST['user']=="#anonymous" && $_POST['pass']=="#anonymous"){
		$_SESSION['user'] = "#anonymous";
		$_SESSION['show_flagged'] = 1; //don't show flagged
		//success
		die("0");
	}
	else {
		//just logging in, confirm user and pass
		
		$result=mysql_query("SELECT * FROM animators WHERE (user = '" . mysql_real_escape_string($_POST['user']) . "') and (pass = '" . mysql_real_escape_string(md5($_POST['pass'])) . "')");
		if (mysql_num_rows($result) == 1) {
			// update their last login date
			mysql_query("UPDATE animators SET last_login='".mysql_real_escape_string($date_now)."' WHERE user='".mysql_real_escape_string($_POST['user'])."'") or die("1");
			// Set username session variable
			$_SESSION['user'] = $_POST['user'];
			if(isset($_POST['show_flagged']))$_SESSION['show_flagged'] = intval($_POST['show_flagged']); //0 is true
			else $_SESSION['show_flagged']=1;
			//success
			die("0");
		}
		else {
			//pass was incorrect, or user was not in the db
			die("3");
		}
	}
}

?>