<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//you don't need to be logged in to watch a video, but if you are the links will be different

// Initialize the session.
session_start();

//includes
include("vars.php");
include("build_links.php");

//connect to db
mysql_connect($db_host, $db_anon_user, $db_anon_pass) or die(mysql_error());
mysql_select_db($db_dbname) or die(mysql_error());

//lookup the animation's info
$result = mysql_query("SELECT * FROM animations WHERE id='".mysql_real_escape_string($_GET["id"])."'");
$row = mysql_fetch_array($result);

$player_link=$site_url.$flipbook_path."/player.php?id=".urlencode($row['id']);
$planets_link=$site_url.$flipbook_path."/planet_view.php?id=".urlencode($row['id']);
$extend_link=$site_url.$flipbook_path."/create_new_animation.php?"
	. "ps_id=" . urlencode($row['id'])
	. "&ps_name=" . urlencode($row['name'])
	. "&ps_anim=" . urlencode($row['animator'])
	. "&ps_len=" . urlencode($row['frames_this']);
//if the user is not logged in, redirect the extend link
if (!isset($_SESSION['user']))$extend_link=$site_url.$flipbook_path."/login.php?redir=".urlencode($extend_link)


?>
<!DOCTYPE HTML>
<html>
<head>

<title>Viewing <?php echo html_safe($row["name"]) ?></title>
<?php
//facebook open graph
echo "<meta property='og:title' content='".html_safe($row["name"])." by ".html_safe($row["animator"])."' />"
	."<meta property='og:type' content='website' />"
	."<meta property='og:url' content='".$site_url.$_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]."' />"
	."<meta property='og:image' content='".$site_url.$flipbook_path."/get_frame.php?seq_id=".strval($row["id"])."' />"
	."<meta property='og:site_name' content='Pass The Flipbook' />"
	."<meta property='fb:admins' content='715190612' />"
	."<meta property='fb:app_id' content='252953534742544' />";

//google analytics
 build_google_analytics();	
?>
</head>
<body style="background-color:#FFFFFF;-webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-o-user-select: none;user-select: none;border:0px;margin:0px;">
<canvas id="viewer_canvas" class="player" width="400" height="320" style="border:0px;margin:0px;padding:0px;" title="animation">Your browser does not support canvas.
<a href='http://www.google.com/chrome/intl/en/landing_chrome.html'>Google Chrome</a> will work.
</canvas>
<script type="text/javascript" src="player_embedded.js"></script>
<script type="text/javascript">
//receive the animation data from php, everything else is retrieved with ajax
animation_data=JSON.parse('<?php echo json_encode($row);?>');
//receive the url of the player from php
planets_url="<?php echo $planets_link;?>";
player_url="<?php echo $player_link;?>";
extend_url="<?php echo $extend_link;?>";
//initialise the player
init_player();
</script>
</body>
</html>
