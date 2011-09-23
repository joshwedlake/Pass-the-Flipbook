<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.
//an ajax called file

//includes
include("vars.php");

//functions
function lookup_parent_data($parent_seq_id){
	//returns an array of (frames parent,flags parent)
	if($parent_seq_id!='-1'){
		//lookup parent_seq_id from animations
		$animations_record = mysql_query("SELECT * FROM animations WHERE id=".mysql_real_escape_string($parent_seq_id)) or die("1,0,0,");  
		//find the value of frames_total and new_parent_seq_id
		$animations_record_row = mysql_fetch_array( $animations_record );
		return array($animations_record_row['frames_total'],$animations_record_row['flags']);
	}
	else return array(0,0);
}

//main

// Make a MySQL Connection
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("1,0,0,");
mysql_select_db($db_dbname) or die("1,0,0,");

// Create a new record
// id (auto),name,animator,parent_seq_id ->all given
// date_created needs to be generated
// date last viewed (set as the creation date
// frames_this = 0
// frames_total is a recursive add
// thumbs_up=0
// thumbs_down=0
// thumbs_score=0 (up-down)
// flags=0
// viewed=0
// Insert a row of information into the table "animations"

$date_created=date("c");
list($frames_total,$flags)=lookup_parent_data($_POST["parent_seq_id"]);
$pass=rand();

$query="INSERT INTO animations(pass,name,animator,parent_seq_id,date_created,date_last_viewed,frames_this,frames_total,thumbs_up,thumbs_down,thumbs_score,viewed,flags,saved) VALUES('".
	mysql_real_escape_string($pass)."','".
	mysql_real_escape_string($_POST["name"])."','".
	mysql_real_escape_string($_POST["animator"])."','".
	mysql_real_escape_string($_POST["parent_seq_id"])."','".
	mysql_real_escape_string($date_created)."','".
	mysql_real_escape_string($date_created)."','0','".
	mysql_real_escape_string($frames_total)."','0','0','0','0','"
	.mysql_real_escape_string($flags)."','1')";
mysql_query($query) or die("1,0,0,");

//let the html know the operation was a success
echo "0," . mysql_insert_id() . "," . $pass . ",";

?>