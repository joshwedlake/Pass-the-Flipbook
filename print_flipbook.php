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

function build_image_table($data,$size){
	$image_block="<div style='font-size:xx-small;'>";
	$copyright_text="Copyright Information: ";
	$count=0;
	$width=strval(200*$size);
	$height=strval(150*$size);
	$data_length=count($data);
	//iterate over each sequence
	for($i=$data_length-1;$i>=0;$i--){
		$copyright_date=date("Y",strtotime($data[$i]["date_created"]));
		$copyright_text.="Frames ".sprintf("%04d",$count)."-".sprintf("%04d",($count+intval($data[$i]['frames_this'])))." from ";
		$copyright_text.="&#39;".html_safe($data[$i]["name"])."&#39;";
		if(is_anonymous($data[$i]["animator"]))$copyright_text.=" are in the public domain // ";
			else $copyright_text.=" by &#39;".html_safe($data[$i]["animator"])."&#39; &#169; CC BY-NC ".$copyright_date." // ";
		//iterate over each frame
		for($j=0;$j<$data[$i]["frames_this"];$j++){
			$image_block.="<div style='float:left;border:1px solid;'>".sprintf("%04d",$count)."<img src='get_frame.php?seq_id="
				.$data[$i]["id"]."&fr_id=".strval($j)."' width='".$width."px' height='".$height."px'></div>";
			$count++;
		}
	}
	return $image_block."</div>"."<div style='clear:both;font-size:x-small;'>".$copyright_text." Created using Pass-the-Flipbook joshwedlake.com/flipbook</div>";
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

if(isset($_GET["size"]))$size=intval($_GET["size"])/100;
else $size=1;

$reverse_stack=get_stack($_GET["id"],array());
$title="Print Animated Flipbook: ".$reverse_stack[0]["name"]." by ".$reverse_stack[0]["animator"];

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
<?php build_google_analytics(); ?>

</head>
<body>
<?php
if(!(isset($_GET["hide_bar"]) && $_GET["hide_bar"]==0)){
	echo "<a href='".$_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]."&hide_bar=0'>hide this bar</a>&nbsp;&nbsp;&nbsp;Resize: "
		."<form name='resize' action='".$_SERVER['PHP_SELF']."' style='display:inline;' method='get'>"
		."<input type='hidden' name='id' value='".$_GET["id"]."'>"
		."<input type='text' size='4' name='size' style='width:30px;' value='".strval($size*100)."'>&#37;<input type='submit' value='Update' />"
		."</form>";

}
echo build_image_table($reverse_stack,$size);
?>
</body>
</html>