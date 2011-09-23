<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//you don't need to be logged in to watch a video, but if you are the links will be different

// Initialize the session.
session_start();

//builds extend link, 
function build_player_buttons($row){
	$text_block="<a href='print_flipbook.php?id=".urlencode($row['id'])."' class='controlButton' title='print out a copy of the flipbook'>print flipbook</a>";
	$text_block.="<a href='planet_view.php?id=".urlencode($row['id'])."' class='controlButton' title='browse in planets view'>planets</a>";
	$text_block.="<span id='embed' class='controlButton' title='share this animation on a webpage' style='font-weight:bold'>embed</a></span>";
	$extend_link="create_new_animation.php?"
		. "ps_id=" . urlencode($row['id'])
		. "&ps_name=" . urlencode($row['name'])
		. "&ps_anim=" . urlencode($row['animator'])
		. "&ps_len=" . urlencode($row['frames_this']);
	
	//if the user is logged in take them straight to the extend page
	//otherwise send them via login
	if (isset($_SESSION['user'])) return $text_block."<a href='".$extend_link."' class='controlButton' title='extend this flipbook'>extend</a>";
	else return $text_block."<a href='login.php?redir="
		. urlencode($extend_link)
		. "' class='controlButton'>extend</a>";
	//give a print option
}

function get_stack($id,$frames,$names){
	//given the id of a sequence, build a list of the frames
	//frames is an array (reverse ordered)
	//assumes already connected to animations
	
	//important:do not apply the flag filter here
	$result = mysql_query("SELECT * FROM animations WHERE id='".mysql_real_escape_string($id)."'");
	$row = mysql_fetch_array($result);
	
	//append the frames to the end
	for($i=($row["frames_this"]-1);$i>=0;$i--){
		$frames[]=array($id,$i);
	}
	if($names!="") $names.=", ";
	$names.="<a href='player.php?id="
		.urlencode($row["id"])
		."'>"
		.html_safe($row["name"])
		."</a> by <a href='browse_by_animator.php?animator="
		.urlencode($row["animator"])
		."'>"
		.html_safe($row["animator"])
		."</a>";
	
	if($row["parent_seq_id"]==-1) return array($frames,$names);
	else return get_stack($row["parent_seq_id"],$frames,$names);
}

//includes
include("vars.php");
include("build_links.php");

//connect to db
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(mysql_error());
mysql_select_db($db_dbname) or die(mysql_error());

//lookup the animation's info
$result = mysql_query("SELECT * FROM animations WHERE id='".mysql_real_escape_string($_GET["id"])."'");
$row = mysql_fetch_array($result);

//update the play count and the last played date
$last_played=date("c");
mysql_query("UPDATE animations SET viewed='".strval(intval($row["viewed"])+1)."' where id ='".mysql_real_escape_string($_GET["id"])."'") or die(mysql_error());
mysql_query("UPDATE animations SET date_last_viewed='".mysql_real_escape_string($last_played)."' where id ='".mysql_real_escape_string($_GET["id"])."'") or die(mysql_error());

//get the frame stack
list($frame_stack,$name_text)=get_stack($_GET["id"],array(),"");

//if the user is logged in, get the flag and thumb status
$is_thumbed_up=false;
$is_thumbed_down=false;
$is_flagged=false;

$seq_id=intval($_GET["id"]);

if (isset($_SESSION['user']) && !is_anonymous($_SESSION['user'])) {

	//get the user data
	$user_result=mysql_query("SELECT flagged,thumbed_up,thumbed_down FROM animators WHERE user ='".mysql_real_escape_string($_SESSION["user"])."'");
	$user_row = mysql_fetch_array($user_result);
	
	//get the users thumbed up,down and flags array
	$already_thumbed_up=unserialize($user_row["thumbed_up"]);
	if(!is_array($already_thumbed_up))$already_thumbed_up=array();
	
	$already_thumbed_down=unserialize($user_row["thumbed_down"]);
	if(!is_array($already_thumbed_down))$already_thumbed_down=array();
	
	$already_flagged=unserialize($user_row["flagged"]);
	if(!is_array($already_flagged))$already_flagged=array();

}


?>

<!DOCTYPE HTML>
<html>
<head>

<link rel="stylesheet" type="text/css" href="default.css" />
<title>Viewing <?php echo html_safe($row["name"]) ?></title>
<?php
//facebook open graph
echo "<meta property='og:title' content='".html_safe($row["name"])." by ".html_safe($row["animator"])."' />"
	."<meta property='og:type' content='website' />"
	."<meta property='og:url' content='".$site_url.$_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]."' />"
	."<meta property='og:image' content='".$site_url.$flipbook_path."/get_frame.php?seq_id=".strval($seq_id)."' />"
	."<meta property='og:site_name' content='Pass The Flipbook' />"
	."<meta property='fb:admins' content='715190612' />"
	."<meta property='fb:app_id' content='252953534742544' />";

//google analytics
 build_google_analytics();	
?>


</head>
<body class="animation">

<h1>Viewing <?php echo html_safe($row["name"]) ?></h1>

<!-- Player controls -->
<div id="controls_container" class="containerButtonRow">
	<span id='rewind' class="controlButton" title="rewind to the start">|&#60;&#60;</span>
	<span id='frame_back' class="controlButton" title="previous frame">|&#60;</span>
	<span id='frame_current' class="controlDisplay" title="current frame">0</span>
	<span id='play' class="controlButton" style="min-width:100px;" title="toggle animation preview">PAUSE ||</span>
	<span id='frame_forward' class="controlButton" title="next frame">&#62;|</span>
	<span class="controlText">&nbsp;&nbsp;zoom</span>
	<input type="range" id="view_zoom"  min="1.0" max="3.0" value="1.0" step="0.01" class="controlSlider" style="width:100px" title="view zoom"/>
</div>

<div class="containerRow"><br /></div>

<div class="containerExpandableRow">
	<div id="viewer_container" class="containerCanvas"> 
		<canvas id="viewer_canvas" class="player" width="400" height="300" class="player" title="animation">Your browser does not support canvas.
		<a href='http://www.google.com/chrome/intl/en/landing_chrome.html'>Google Chrome</a> will work.
		</canvas>
	</div>
</div>

<div class="containerRow"><br /></div>

<div id="menu_container" class="containerRow"> 
	<!-- Like and flag buttons go here -->
	<a href='menu.php' class="controlButton">home</a>
	<?php
	//extend button
	echo build_player_buttons($row);
	?>
	<span class='controlText'>plays: </span>
	<span id='play_count' class='controlText'></span>
	<span class='controlText'>&nbsp;&nbsp;</span>
	<span id='thumbs_up' class="controlButton" title="vote thumbs up"<?php
		if($is_thumbed_up) echo " style='font-weight:bold'";?>>+<span id='thumbs_up_count'></span></span>
	<span id='thumbs_down' class="controlButton" title="vote thumbs down"<?php
		if($is_thumbed_down) echo " style='font-weight:bold'";?>>-<span id='thumbs_down_count'></span></span>
	<span id='flag' class="controlButton" title="flag inappropriate content"<?php
		if($is_flagged) echo " style='font-weight:bold'";?>>flag</span>
</div>

<div class="containerRow"><br /></div>

<div class="containerButtonRow">
	<span id='loading_status'>Loading: 0%</span>
</div>

<div class="containerRow"><br /></div>

<div class="containerRow">
<!-- google +1 -->
<?php build_google_plus_one("small");?>
<!-- facebook controls -->
<?php build_fb_like(); ?>
</div>

<div class="containerRow"><br /></div>

<div id="comments_container" class="containerRow" style="word-wrap:break-word"></div>

<?php
//build the comments form if the user is logged in
if (isset($_SESSION['user'])) {
	//if the user is anonymous, let them know they must sign up
	if(is_anonymous($_SESSION['user'])){
		echo "<div class='containerButtonRow'>\n";
		echo "Anonymous users cannot comment, please <a href='logout.php?redir="
			.urlencode("login.php?hide_anonymous=0&redir=".urlencode("player.php?".$_SERVER["QUERY_STRING"]))."'>Sign In or Sign Up</a>\n";
		echo "</div>\n";
	}
	else {
		echo "<div class='containerRow'><form name='comments_form'>\n"
			."<textarea id='comment_text' name='comment_text' placeholder='Add a comment' maxlength='500' style='float:left;width:545px;height:50px;resize: none'></textarea>\n"
			."<input id='comment_add' type='button' value='&#62;&#62;' name='add_comment' style='margin-left:5px;float:left;width:40px;height:55px;'></td>\n"
			."</form></div>";
	}
}
else {
	echo "<div class='containerButtonRow'>\n";
	echo "Please <a href='login.php?hide_anonymous=0&redir=".urlencode("player.php?".$_SERVER["QUERY_STRING"])."'>Sign In or Sign Up</a> to leave a comment.\n";
	echo "</div>\n";
}

?>
</div>
	
<div class="containerRow"><br /></div>

<div class="containerRow">
	Jump to the <a href='tree_view.php?id=<?php echo $_GET["id"];?>'>ownership tree</a> or <a href='frame_stack.php?id=<?php echo $_GET["id"];?>'>download</a> the full set of frames.
	Content available under a <a href='http://creativecommons.org/licenses/by-nc/3.0/' target="_blank">Creative Commons CC BY-NC License</a>, &#169; 
	<?php echo date("Y",strtotime($row["date_created"]))." multiple authors: ".$name_text;?>
</div>

<script type="text/javascript" src="player.js"></script>
<script type="text/javascript" src="comments.js"></script>

<script type="text/javascript">
//receive the username from php
<?php
//this is used to allow commenting and flagging, and to allow deletion of own comments
if (isset($_SESSION['user'])) {
	if(is_anonymous($_SESSION['user'])){
		echo "is_logged_in=false;\nis_anonymous=true;\n";
	}
	else {
		echo "is_logged_in=true;\nis_anonymous=false;\n";
		echo "username='".$_SESSION['user']."';\n";
	}
}
else echo "is_logged_in=false;\nis_anonymous=false;\n";
?>

//receive the sequence id from php
seq_id=<?php echo $seq_id;?>;
//receive the name from php
seq_name="<?php echo $row["name"];?>";
//receive the frame stack (from php)
frame_stack=<?php echo json_encode($frame_stack);?>;
//receive the embed url from php
embed_url="<?php echo $site_url.$flipbook_path."/player_embedded.php?id=".urlencode($row['id']);?>";
player_url="<?php echo $site_url.$flipbook_path."/player.php?id=".urlencode($row['id']);?>";
//receive initial play count data and score data (from php)
play_count=<?php echo $row["viewed"]?>;
thumbs_up=<?php echo $row["thumbs_up"]?>;
thumbs_down=<?php echo $row["thumbs_down"]?>;

//initialise the player
init_player();
//initialise comments
init_comments();
</script>
<br />
<?php
//build page footer
build_footer();
?>
</body>
</html>
