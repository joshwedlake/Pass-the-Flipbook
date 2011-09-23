<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//session start

// Inialize session
session_start();

//includes
include("vars.php");
include("build_links.php");

// Check, if user is already login, then jump to secured page
if (isset($_SESSION['user'])) {
header('Location: menu.php');
}

//called with a $_GET["redir"]=
//and a $_GET["hide_anonymous"]=0 true or 1 false to decide whether to offer up an anonymous option

?>
<!DOCTYPE HTML>
<html>
<head>

<!!-- load recaptcha -->
<script type="text/javascript" src="http://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>
<script type="text/javascript">

var register_enabled=false;

var recaptcha_public_key="<?php echo $publickey;?>";

function register_mode(){
	if(!register_enabled){
		//set the pass_repeat_row and the advisory to be shown
		document.getElementById('pass_repeat_row').style.display='block';
		document.getElementById('copyright_advisory_row').style.display='block';
		//set the recaptcha to be shown
		document.getElementById('recaptcha_row').style.display='block';
		//initialise the recaptcha
		Recaptcha.create(recaptcha_public_key, 'recaptcha_container', {theme: "red",callback: Recaptcha.focus_response_field});
		
		//change the text of the register toggle
		document.getElementById('register_toggle_text').innerHTML="I'm a regular, Login";
		//change the text of the login button
		document.getElementById('submit_form').value='Sign Up';
		//set register_enabled to true
		register_enabled=true;
	}
	else {
		document.getElementById('register_toggle_text').innerHTML="I'm new, Sign Up";
		document.getElementById('pass_repeat_row').style.display='none';
		document.getElementById('copyright_advisory_row').style.display='none';
		document.getElementById('recaptcha_row').style.display='none';
		Recaptcha.destroy();
		document.getElementById('submit_form').value='Sign In';
		register_enabled=false;
	}
}

function go_anonymous(){
	//send to login
	send_to_php(false,encodeURIComponent("#anonymous"),encodeURIComponent("#anonymous"),1);
}

function do_login(){
	//work out the status of the flagged choice
	if(document.getElementById('show_flagged').checked)show_flagged=0;
	else show_flagged=1;
	
	//register or login
	if (register_enabled){
	
		//registering
		register_user=document.getElementById('user').value.substr(0,30);
		register_pass=document.getElementById('pass').value.substr(0,30);
		register_pass_repeat=document.getElementById('pass_repeat').value.substr(0,30);
		
		if(register_user.replace(/[^a-zA-Z0-9_]+/g,'')!=register_user){
			//remove non alphanum and alert user
			document.getElementById('user').value=register_user.replace(/[^a-zA-Z0-9_]+/g,'');
			alert("Bad characters have been removed from your username.\nIf you are happy with the result, please click 'Sign Up' again.");
			}
		else {
			if(register_user.length==0) alert("Please type a username");
			else {
				if(register_pass.length==0 || register_pass_repeat.length==0) alert("Please type your password twice");
				else {
					if(register_pass!=register_pass_repeat) alert("Please check your passwords match");
					else {
						//encode components, and register
						register_user=encodeURIComponent(register_user);
						register_pass=encodeURIComponent(register_pass);
						//send to register
						send_to_php(true,register_user,register_pass,show_flagged);
					}
				}
			}
		}
	}
	else {
		//logging in
		register_user=document.getElementById('user').value.substr(0,30).replace(/[^a-zA-Z0-9_]+/g,'');
		register_pass=document.getElementById('pass').value.substr(0,30);
		if(register_user.length==0) alert("Please type your username");
		else {
			if(register_pass.length==0) alert("Please type your password");
			else {
				//OK, send to php
				//encode components, and login
				register_user=encodeURIComponent(register_user);
				register_pass=encodeURIComponent(register_pass);
				//send to login
				send_to_php(false,register_user,register_pass,show_flagged);
			}
		}
	}
}


function send_to_php(send_register,send_user,send_pass,show_flagged){
	// create xmlhttp oblect
	var xmlhttp;
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange=function() {
		//handle the response
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			//get message text
			if(xmlhttp.responseText.indexOf("0")==0){
				//success, have recieved success flag and a session, redirect
				<?php
				//on this page if arrive with $_GET["redir"]=blah.php?a=1 ... then send to there
				if(isset($_GET["redir"])) echo 'location.href="'.$_GET["redir"].'";';
				else echo 'location.href="menu.php";';
				?>
			}
			else {
				//recaptcha must be refreshed
				Recaptcha.destroy();
				Recaptcha.create(recaptcha_public_key, 'recaptcha_container', {theme: "red",callback: Recaptcha.focus_response_field});
				if(xmlhttp.responseText.indexOf("1")!=-1){
					//sql error
					document.getElementById("status").innerHTML="Server error, sorry.";
				}
				else if(xmlhttp.responseText.indexOf("2")!=-1){
					//username taken
					document.getElementById("status").innerHTML="Username already taken, please try another.";
				}
				else if(xmlhttp.responseText.indexOf("3")!=-1){
					//login incorrect
					document.getElementById("status").innerHTML="Username or password incorrect, please try again.";
				}
				else if(xmlhttp.responseText.indexOf("4")!=-1){
					//captcha incorrect
					document.getElementById("status").innerHTML="Captcha incorrect, please try again.";
				}
			}
		}
	}
	
	xmlhttp.open("POST","process_login.php",true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	request="user="+send_user+"&pass="+send_pass+"&show_flagged="+show_flagged;
	if(send_register) request+="&register=1&r_chal="+encodeURIComponent(Recaptcha.get_challenge())+"&r_resp="+encodeURIComponent(Recaptcha.get_response());
	xmlhttp.send(request);
	document.getElementById("status").innerHTML="please wait, contacting server...";
}

</script>

<link rel="stylesheet" type="text/css" href="default.css" />
<title>
Login
</title>
<!-- Facebook open graph -->
<?php build_og("Sign In or Sign Up"); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>

<a href='menu.php'>&#60;&#60;cancel sign in</a>

<h1>Sign in...</h1>
<div style="font-size:large;margin-left:40px;">

<!-- facebook login -->
<span id="fb-root"></span>
<span id="facebook_login_button" style="position:relative;bottom:3px;display:hidden;"><fb:login-button>Login with Facebook</fb:login-button></span>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
FB.init({ 
	appId:'252953534742544', cookie:true, 
	status:true, xfbml:true, oauth:true
});
FB.getLoginStatus(function(response) {
	if (response.authResponse) {
		// logged in and connected user, redirect
		<?php
		if(isset($_GET["redir"])) echo 'window.location="process_login_facebook.php?redir='.urlencode($_GET["redir"]).'";';
		else echo 'window.location="process_login_facebook.php";';
		?>
	}
	else {
		//not yet logged in
		facebook_login_button = document.getElementById('facebook_login_button');
		facebook_login_button.style.display="inline";
		//redirect on login
		FB.Event.subscribe('auth.login', function () {
			window.location = "process_login_facebook.php";
		});
	}
});

</script>

or <a href="#" onClick="register_mode();return false;" style="border:1px solid;padding:2px;"><span id='register_toggle_text'>I'm new, Sign Up</span></a>

<?php
//offer anonymous mode, only if its not set to hidden
if(!isset($_GET["hide_anonymous"]) || (isset($_GET["hide_anonymous"]) && intval($_GET["hide_anonymous"])!=0))
	echo " or <a href='#' onClick='go_anonymous();return false;' style='border:1px solid;padding:2px;'>Draw Anonymously!</a>";

echo "</div><p style='margin-left:80px;'>";

if(!isset($_GET["hide_anonymous"]) || (isset($_GET["hide_anonymous"]) && intval($_GET["hide_anonymous"])!=0))
	echo "If you prefer, you can draw anonymously without signing up.<br />"
		."Please be aware that ninjas who hide under the cover of darkness can't be credited.<br />";

?>

Signing up is free, all you need is a username and password!</p>
<br />
<!-- div defines an id which can be changed from js -->
<form name="login_form" action="" onSubmit="do_login();return false;">

<div class="loginRow">
	<div class="loginLeftColTitle">Username:</div>
	<div class="loginInputCol"><input id="user" type="text" name="user" maxlength="30"  style="width:150px"/></div>
</div>

<div class="loginRow">
	<div class="loginLeftColTitle">Password:</div>
	<div class="loginInputCol"><input id="pass" type="password" name="pass" maxlength="30"  style="width:150px"/></div>
</div>

<div class="loginRow" id="pass_repeat_row" style="display:none;">
	<div class="loginLeftColTitle">Repeat Password:</div>
	<div class="loginInputCol"><input id="pass_repeat" type="password" name="pass_repeat" maxlength="30"  style="width:150px"/></div>
</div>

<div class="loginRow">
	<div class="loginLeftCol">Show Flagged Content:</div>
	<div class="loginInputCol" style="margin-top:5px;"><input type="checkbox" id="show_flagged" name="show_flagged" value="show_flagged" title="tick this box to allow content which has been marked as inappropriate by other users"/></div>
</div>

<div class="loginRow" id="recaptcha_row" style="display:none;">
	<div id="recaptcha_container" style="margin-left:105px;"></div>
</div>
	
<div class="loginRow" id="copyright_advisory_row" style="display:none;">
	<br /><p style="font-size:x-small;">By clicking &#39;Sign Up&#39; you agree to grant this website non-exclusive permission to display and distribute any artwork you produce using the tools on this site, under the terms of the Creative Commons <a href='http://creativecommons.org/licenses/by-nc/3.0/' target="_blank">CC BY-NC 3.0</a> License.  Don't worry, you retain ownership of anything you create on this site, and you can download the frames when you're done animating.  Your artwork will be credited to your username so pick a good one in case you hit the big time.  The purpose of this website is to encourage collaboration, and as such other users of this website may base their artworks on content which you submit.  Please play nice, show respect for each other, and responsibly enjoy the freedom of expression given to you by the internet.  You can draw whatever you like, but if another user flags your work it will be hidden from public view.  If censorship isn't your bag you can always check the &#39;Show Flagged Content&#39; box on the login screen.</p>
</div>
<div class="loginRow" style="padding-top:20px;padding-left:260px;">
	<input id="submit_form" type="submit" value="Sign In" style="width:150px"></div>
</div>

</form>

<br />
<h2><div id='status'></div></h2>
<br />
<?php
//build page footer
build_footer();
?>
</body>
</html>


