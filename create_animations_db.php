<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

include("vars.php");

function report_error($error){
	echo "error: ".$error."<br />";
}

// Make a MySQL Connection
mysql_connect($db_host, $db_admin_user, $db_admin_pass) or die(mysql_error());
echo "Connected to MySQL<br>";
mysql_select_db($db_dbname) or die(mysql_error());
echo "Connected to test<br>";

echo "Creating logins table...<br>";

//Create the login table
//flagged,thumbed_up and thumbed_down are simple php arrays of sequece ids which have already been handled

mysql_query("CREATE TABLE animators(
	user VARCHAR(30) CHARSET ascii, 
	PRIMARY KEY(user),
	human_user TINYTEXT CHARSET ascii,
	pass CHAR(32) CHARSET ascii,
	date_joined DATETIME,
	last_login DATETIME,
	flagged MEDIUMBLOB,
	thumbed_up MEDIUMBLOB,
	thumbed_down MEDIUMBLOB,
	flagged_comments MEDIUMBLOB)") or  report_error(mysql_error());


echo "Creating animations table<br>";

mysql_query("CREATE TABLE animations(
	id BIGINT NOT NULL AUTO_INCREMENT, 
	PRIMARY KEY(id),
	pass INT,
	name VARCHAR(30) CHARSET ascii, 
	animator VARCHAR(30) CHARSET ascii,
	parent_seq_id BIGINT,
	date_created DATETIME,
	date_last_viewed DATETIME,
	frames_this INT,
	frames_total INT,
	thumbs_up INT,
	thumbs_down INT,
	thumbs_score INT,
	viewed INT,
	flags INT,
	tags MEDIUMBLOB,
	saved TINYINT)") or report_error(mysql_error());


echo "Creating comments table<br>";

mysql_query("CREATE TABLE comments(
	id BIGINT NOT NULL AUTO_INCREMENT, 
	PRIMARY KEY(id),
	user VARCHAR(30) CHARSET ascii,
	seq_id INT,
	flags INT,
	date_created DATETIME,
	comment TEXT)") or report_error(mysql_error());

echo "Creating tags table<br>";

mysql_query("CREATE TABLE tags(
	id BIGINT NOT NULL AUTO_INCREMENT, 
	PRIMARY KEY(id),
	name VARCHAR(30) CHARSET ascii,
	animations MEDIUMBLOB,
	synonyms MEDIUMBLOB,
	count INT)") or report_error(mysql_error());

echo "Done.<br>";

?>