<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

include("vars.php");

//given a sequence id and a frame number, load a frame

//if $_GET["save"] is set (giving a filename), then we are doing a download

function send_fail(){
	$fail_img_data=base64_decode("R0lGODlhCgAKAIAAAP///wAAACwAAAAACgAKAAACFQSCaRfLjJxSK8LaDLU60u5lHZZkBQA7");
	header("Content-type: image/gif");
	die($fail_img_data);
}

if(isset($_GET["seq_id"])){
	//cast to int for security
	$seq_id=intval($_GET["seq_id"]);
	//if the frame id is set, we are handling a frame, else we are handling a thumb
	if(isset($_GET["fr_id"])){
		//cast to int for security
		$fr_id=intval($_GET["fr_id"]);
		//open /data/"seq_id"_"fr_id".png
		$filename=$system_data_path . $seq_id . "_" . $fr_id . ".png";
	}
	else {
		//open /data/"seq_id".png
		$filename=$system_data_path . $seq_id . ".png";
	}
	//suppress the warning
	$data=@file_get_contents($filename);
	if($data!=FALSE){
		header("Content-type: image/png");
		if(isset($_GET["save"])) header('Content-Disposition: attachment; filename="'.$_GET["save"].'"');
		echo $data;
	}
	else send_fail();
}
else send_fail();
?>