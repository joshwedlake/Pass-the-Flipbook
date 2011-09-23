<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

// Initialize the session.
session_start();

function get_stack($id,$data){
	//given the id of a sequence, build a stack of the rows for each sequence
	
	$result = mysql_query("SELECT * FROM animations WHERE id='".mysql_real_escape_string($id)."'") or die(mysql_error());
	$row = mysql_fetch_array($result) or die(mysql_error());
	
	$data[]=$row;
	
	if(intval($row["parent_seq_id"])==-1) return $data;
	else return get_stack($row["parent_seq_id"],$data);
}

function build_download_links($data){
	$text="";
	$seq_count=0;
	$data_length=count($data);
	//iterate over each sequence
	for($i=$data_length-1;$i>=0;$i--){
		$copyright_date=date("Y",strtotime($data[$i]["date_created"]));
		//iterate over each frame
		for($j=0;$j<$data[$i]["frames_this"];$j++){
			$filename=sprintf("%04d.png",$seq_count);
			$text.="<a href='get_frame.php?seq_id="
				.$data[$i]["id"]."&fr_id=".strval($j)."&save=".$filename."'>".$filename
				."</a> From frame ".strval($j)." in ".html_safe($data[$i]["name"]);
			if(is_anonymous($data[$i]["animator"]))$text.=". This frame is in the public domain.<br />";
			else $text.=" &#169;".$copyright_date." ".html_safe($data[$i]["animator"])."<br />";
			$seq_count++;
		}
	}
	return $text;
}

//includes
include("vars.php");
include("build_links.php");

//connect to the database
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(mysql_error());
mysql_select_db($db_dbname) or die(mysql_error());

if(!isset($_GET["id"])) {
	//send the user home, must be an error
	header('Location: menu.php');
	die();
}

$reverse_stack=get_stack($_GET["id"],array());
$title="Download Frames: ".$reverse_stack[0]["name"]." by ".$reverse_stack[0]["animator"];

?>
<!DOCTYPE HTML>
<html>
<head>

<link rel="stylesheet" type="text/css" href="default.css" />
<title>
<?php echo $title;?>
</title>
<!-- Facebook open graph -->
<?php build_og($title); ?>
<!-- Google Tracking Code -->
<script type="text/javascript">
var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-25432188-2']);
	_gaq.push(['_trackPageview']);
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>

</head>
<body>
<a href="menu.php">&#60;&#60;back home</a>
<h1><?php echo $title;?></h1>
<h2>COPYRIGHT NOTICE:</h2>
<p>Copyright owners are listed below on a per-frame basis.<br />
Files are distributed under the terms of the <a href='http://creativecommons.org/licenses/by-nc/3.0/' target="_blank">Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0) License</a> except where otherwise stated.<br />
You must read the license fully before downloading any frames.</p>
<p><?php
echo build_download_links($reverse_stack);
?>
</p>
<?php
//build page footer
build_footer();
?>
</body>
</html>