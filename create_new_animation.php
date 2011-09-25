<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//to use this page the user _must_ be signed in

// Inialize session
session_start();

//includes
include("vars.php");
include("build_links.php");

//if the user isn't logged in, we shouldn't be here
if(!isset($_SESSION["user"])) {
	//if there's a query string, then we're probably meant to be on this page
	if($_SERVER["QUERY_STRING"]) {
		$redir_url="create_new_animation.php?" . $_SERVER["QUERY_STRING"];
		header('Location: login.php?redir='.urlencode($redir_url));
		die();
	}
	else {
		//otherwise let the user login as by defualt
		header('Location: login.php');
		die();
	}
}

if(intval($_GET["ps_id"])==-1)$based_on="...starting from scratch...";
else $based_on="based on ".$_GET["ps_name"]." by ".$_GET["ps_anim"];

?>
<!DOCTYPE HTML>
<html>
<head>
	
<link rel="stylesheet" type="text/css" href="default.css" />
<title>
<?php echo "Create New Animation ".$based_on;?>
</title>
<!-- Facebook open graph -->
<?php build_og("Create New Animation ".$based_on);?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>

<a href="menu.php">&#60;&#60;back home</a>

<h1>Create Animation</h1>
<p><?php echo $based_on; ?>
<br /><br />
<?php
if(intval($_GET['ps_id'])!=-1) echo "<img src='get_frame.php?seq_id=".$_GET['ps_id']."' width='200px' height='150px' style='border:1px solid;' />";
?>
</p>

<p>
	<form name="details_form" action="" onSubmit="create_animation();return false;">
		<input id="name" type="text" placeholder="Choose a Title" name="name" maxlength="30" style="margin-left:40px;width:160px;"/>
		<input type="submit" value="&#62;&#62;" name="create"></td>
	</form>
</p>

<br />

<p><div id='status' style="margin-left:40px;"></div></p>

<script type="text/javascript">

//escapes html chars
function html_safe(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

function create_animation() {
	//check the name is acceptable
	animation_name=encodeURIComponent(document.getElementById('name').value.substr(0,30));
	if (animation_name.length==0){
		//warn the user
		alert("A title is required for your animation");
	}
	else {
		//continue and save
		// create xmlhttp object
		var xmlhttp;
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		//this code executes after the response is received
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				//this is the response
				if(xmlhttp.responseText.indexOf("0,")==0){
					document.getElementById("status").innerHTML="success";
					//sort out the return data, index 1 is id, index 2 is the password
					var return_data=xmlhttp.responseText.split(',');
					request="draw.php?"+
						"id="+encodeURIComponent(return_data[1])+
						"&pass="+encodeURIComponent(return_data[2])+
						"&ps_id="+encodeURIComponent(ps_id);
					//if there is a valid parent sequence, then send the length of it
					if(ps_id!=-1){
						request+="&ps_len="+ps_len;
					}
					location.href=request;
				}
				else {
					document.getElementById("status").innerHTML="failed, please try again";
				}
			}
		}
	
		xmlhttp.open("POST","process_animation.php",true);
		xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		request="name="
			+animation_name
			<?php
			//the user name comes from php
			echo '+"&animator='.urlencode($_SESSION["user"]).'"'
			?>
			+"&parent_seq_id="+encodeURIComponent(ps_id);
		xmlhttp.send(request);
		document.getElementById("status").innerHTML="please wait, creating...";
	}
}

//receive parent seq data from php
<?php
echo "ps_id=".$_GET["ps_id"].";";
if(intval($_GET['ps_id'])!=-1)echo "ps_len=".$_GET["ps_len"].";";
?>


</script>
<?php
//build page footer
build_footer();
?>
</body>
</html>



