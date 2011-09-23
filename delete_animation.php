<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//to use this page the user _must_ be signed in

// Initialize the session.
session_start();

//if the user isn't logged in, we shouldn't be here
if(!isset($_SESSION["user"])) {
	//if there's a query string, then we're probably meant to be on this page
	if($_SERVER["QUERY_STRING"]) {
		$redir_url="delete_animation.php?" . $_SERVER["QUERY_STRING"];
		header('Location: login.php?redir='.urlencode($redir_url));
		die();
	}
	else {
		//otherwise let the user login as by defualt
		header('Location: login.php');
		die();
	}
}

include("vars.php");

function send_to_menu($error){
	header('Location: menu.php');
	die($error);
}

//check the action is valid

mysql_connect($db_host, $db_anon_user, $db_anon_pass) or send_to_menu(mysql_error());
mysql_select_db($db_dbname) or send_to_menu(mysql_error());

// find the record, if its invalid, redirect
$result = mysql_query("SELECT * FROM animations WHERE ID=".$_GET["id"]) or send_to_menu(mysql_error());
// check the pass key matches and that saved is 1
$row = mysql_fetch_array($result) or send_to_menu(mysql_error());
if($row['id']==$_GET["id"] && $row['pass']==$_GET["pass"] && $row['saved']=="1"){
	//remove record from db
	mysql_query("DELETE FROM animations WHERE ID=".$_GET["id"]) or send_to_menu(mysql_error());
	send_to_menu("Done.");
}
else send_to_menu("Animation cannot be deleted.");

?>