<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//once the user logs in with facebook, they get redirected to this page, which converts a facebook cookie into a regular login cookie

// Inialize session
session_start();

//load facebook libs
require_once('facebook/facebook.php');
include("vars.php");

// Make a MySQL Connection
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("1".mysql_error());
mysql_select_db($db_dbname) or die("1".mysql_error());

//show flagged
if(isset($_GET['show_flagged']))$show_flagged=intval($_GET['show_flagged']);
else $show_flagged=1;

//for last login date
$date_now=date("c");

$login_error="Error logging you in, Please <a href='trouble.php'>report trouble.</a>: ";

$facebook = new Facebook($facebook_config);
$user_id = $facebook->getUser();

// if the userid isnt 0 then either add or update the db
if($user_id!=0){
	//get some human readable info for the database
	$user_profile = $facebook->api('/me','GET');
	
	$user="#fb".strval($user_id);
	
	//does user already exist in the db?  if it does then update the name
	if(mysql_num_rows(@mysql_query("SELECT * FROM animators WHERE user ='".mysql_real_escape_string($user)."'"))){
		mysql_query("UPDATE animators SET human_user='".mysql_real_escape_string($user_profile['name'])."' WHERE user='".mysql_real_escape_string($user)."'")
			or die($login_error.mysql_error());
		mysql_query("UPDATE animators SET last_login='".mysql_real_escape_string($date_now)."' WHERE user='".mysql_real_escape_string($user)."'")
			or die($login_error.mysql_error());
	}
	else{
		//not been here before, so create record
		$blank_array=serialize(array());
		$query="INSERT INTO animators(user,human_user,pass,date_joined,last_login,flagged,thumbed_up,thumbed_down,flagged_comments) VALUES('".
			mysql_real_escape_string($user)."','".
			mysql_real_escape_string($user_profile['name'])."','".
			mysql_real_escape_string("")."','".
			mysql_real_escape_string($date_now)."','".
			mysql_real_escape_string($date_now)."','".
			mysql_real_escape_string($blank_array)."','".
			mysql_real_escape_string($blank_array)."','".
			mysql_real_escape_string($blank_array)."','".
			mysql_real_escape_string($blank_array)."')";
		mysql_query($query) or die($login_error.mysql_error());
		
	}
	// Set username session variable
	$_SESSION['user'] = $_POST['user'];
	$_SESSION['show_flagged'] = $show_flagged; //0 is true
	
	//build the fb specific details
	$_SESSION['fb_name'] = $user_profile['name'];
	$params = array('next'=> $site_url.$flipbook_path."/menu.php");
	$_SESSION['fb_logout_url'] = $facebook->getLogoutUrl($params);
	
	//reached this part in the code without dieing, now redirect
	if(isset($_GET["redir"]))header('Location:'.$_GET["redir"]);
	else header('Location:menu.php');
}
//not logged in on facebook, so bail out
else header('Location:login.php');

?>