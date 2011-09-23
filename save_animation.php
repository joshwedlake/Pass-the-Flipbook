<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

include("vars.php");

//ajax, returns "FAIL:"+error for fail, "SUCCESS" for success

mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die("FAIL: ".mysql_error());
mysql_select_db($db_dbname) or die("FAIL: ".mysql_error());

// find the record
$result = mysql_query("SELECT * FROM animations WHERE id='".$_POST["id"]."'") or die("FAIL: ".mysql_error());
// check the pass key matches and that saved is 1 (ie currently unsaved)
$row = mysql_fetch_array($result) or die("FAIL: ".mysql_error());  
if($row['id']==$_POST["id"] && $row['pass']==$_POST["pass"] && $row['saved']=="1"){
	//update the table
	mysql_query("UPDATE animations SET saved = '0' where id ='".$_POST["id"]."'") or die("FAIL: ".mysql_error());
	mysql_query("UPDATE animations SET frames_this = '".$_POST["frames"]."' where id ='".$_POST["id"]."'") or die("FAIL: ".mysql_error());
	mysql_query("UPDATE animations SET frames_total = '".strval(intval($row["frames_total"])+intval($_POST["frames"]))."' where id ='".$_POST["id"]."'") or die("FAIL: ".mysql_error());
	echo "SUCCESS";
}
else echo "FAIL: credentials invalid!";
?>