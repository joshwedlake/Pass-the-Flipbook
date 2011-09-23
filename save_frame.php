<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

include("vars.php");

//given a sequence id and a frame number, save a frame
$seq_id=intval($_POST["seq_id"]);
$imageData=$_POST["data"];

// split at the first comma, where the data starts, ie.  $data="data:image/png;base64,0000000000000000000..."
$filteredData=substr($imageData, strpos($imageData, ",")+1);

// base64 decode
$unencodedData=base64_decode($filteredData);

if(isset($_POST["seq_id"])){
	if(isset($_POST["fr_id"])){
		//open /data/"seq_id"_"fr_id".png for read
		$fr_id=intval($_POST["fr_id"]);
		$filename=$system_data_path . $seq_id . "_" . $fr_id . ".png";
	}
	else $filename=$system_data_path . $seq_id . ".png"; 


	$handle = fopen($filename, "wb") or die("FAIL");

	if($handle){
		$data = base64_decode($_POST["data"]);
		fwrite($handle, $unencodedData);
		fclose($handle);
		//indicate success
		echo "SUCCESS";
	}
	else die ("FAIL");
}
else die ("FAIL");

?>
