<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//global variables

//toggle this when testing locally,eg with WAMP/LAMP
$site_live=true;

//domain without a final slash, used for the facebook opengraph
$site_url="http://www.joshwedlake.com";
$flipbook_path="/flipbook";

//captcha
$privatekey = "";
$publickey = "";

if($site_live){
	$db_host="localhost";
	$db_admin_user=""; //a user who can create tables
	$db_admin_pass="";
	$db_anon_user=""; //the user who can edit/create rows
	$db_anon_pass="";
	$db_dbname=""; //the database name
	
	//folder where image files are stored
	//include a final slash
	$system_data_path="/home/joshw/public_html/flipbook/data/";
}
else {
	//as above, but for local testing
	$db_host="";
	$db_admin_user="";
	$db_admin_pass="";
	$db_anon_user="";
	$db_anon_pass="";
	$db_dbname="";

	$system_data_path="C:/wamp/www/data/";
}

//max number of thumbs to show
$display_thumb_limit=30;

$facebook_config = array(
	'appId' => '', //your facebook app data here
	'secret' => '',
	'cookie' => true
);

//---------------don't edit the rest-------------------

function is_anonymous($user) {
	//checks if a username is anonymous
	if($user=="#anonymous") return true;
	else return false;
}

function html_safe($unsafe) {
	$unsafe=str_replace("&", "&amp;",$unsafe);
	$unsafe=str_replace("<", "&lt;",$unsafe);
	$unsafe=str_replace(">", "&gt;",$unsafe);
	$unsafe=str_replace('"', "&quot;",$unsafe);
	$unsafe=str_replace("'", "&#039;",$unsafe);
	return $unsafe;
}

?>