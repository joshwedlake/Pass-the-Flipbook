<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

function animator_link($animator){
	return "<a href='browse_by_animator.php?animator=".urlencode($animator)."' class='miniLink'>".$animator."</a>";
}

function tree_link($id,$name){
	return "<a href='tree_view.php?id=".urlencode($id)."' class='miniLink'>".html_safe($name)."</a>";
}

function planet_link($id,$name,$stat){
	return "<a href='planet_view.php?id=".urlencode($id)."&stat=".urlencode($stat)."' class='miniLink'>".html_safe($name)."</a>";
}

function build_top_bar(){
	echo "<span style='white-space:nowrap;font-size:small;'>";
	if (isset($_SESSION['user'])) {
		//if the user isn't anonymous
		if($_SESSION['user']!="#anonymous"){
			//are they a facebook user
			if(isset($_SESSION['fb_name'])){
				//get human name
				echo "Welcome ".$_SESSION['fb_name'];
				echo " // <a href='logout.php?redir=".urlencode($_SESSION['fb_logout_url'])."'>Facebook Logout</a> // ";
			}
			else echo "Welcome ".$_SESSION['user']
				." // <a href='logout.php?redir=".urlencode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"])."'>Logout</a> // ";
			echo "<a href='browse_by_animator.php?animator=".urlencode($_SESSION["user"])."'>My Animations</a> // ";
		}
		else echo "Anonymous Mode // <a href='logout.php?redir=".urlencode("login.php?redir="
			.urlencode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]))."'>Sign Up</a> // "
			."<a href='browse_by_animator.php?animator=".urlencode($_SESSION["user"])."'>Anonymous Animations</a> // ";
	}
	else echo "<a href='login.php?redir=".urlencode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"])."'>Login or Sign Up</a> // ";

	echo "<a href='menu.php'>Home</a> // <a href='index.php'>What&#63;</a> // <a href='about.php'>About</a> // <a href='trouble.php'>Trouble&#33;</a> // Click extend for... // Pass the Flipbook // Drink and Draw // The Lightbox Swinger's Club // Collaborative Animation Remixing // Online Pencil Tests</span>";
}

//builds the facebook opengraph
//currently used by every page except player.php
function build_og($page_name){
	global $site_url,$flipbook_path;
	echo "<meta property='og:title' content='".$page_name."' />"
		."<meta property='og:type' content='website' />"
		."<meta property='og:url' content='".$site_url.$_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]."' />"
		."<meta property='og:image' content='".$site_url.$flipbook_path."/flipbook.gif' />"
		."<meta property='og:site_name' content='Pass The Flipbook' />"
		."<meta property='fb:admins' content='715190612' />"
		."<meta property='fb:app_id' content='252953534742544' />";
}

function build_fb_like(){
	global $site_url;
	echo "<span id='fb-root'></span>"
		."<script>(function(d, s, id) {"
		."var js, fjs = d.getElementsByTagName(s)[0];"
		."if (d.getElementById(id)) {return;}"
		."js = d.createElement(s); js.id = id;"
		."js.src = '//connect.facebook.net/en_US/all.js#appId=252953534742544&xfbml=1';"
		."fjs.parentNode.insertBefore(js, fjs);"
		."}(document, 'script', 'facebook-jssdk'));</script>"
		."<span class='fb-like' data-href='"
		.$site_url.$_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]
		."' data-send='true' data-layout='button_count' data-width='450' data-show-faces='false' data-font='arial'></span>";
}

function build_google_plus_one($size){
	global $site_url;
	echo "<div class='g-plusone' data-size='".$size."' data-href='"
		.$site_url.$_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]."'></div>"
		."<script type='text/javascript'>"
		."window.___gcfg = {lang: 'en-GB'};"
		."(function() {"
		."var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;"
		."po.src = 'https://apis.google.com/js/plusone.js';"
		."var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);"
		."})();</script>";
}

function build_google_analytics(){
	echo "<script type='text/javascript'>"
		."var _gaq = _gaq || [];"
		."_gaq.push(['_setAccount', 'UA-25432188-2']);"
		."_gaq.push(['_trackPageview']);"
		."(function() {"
		."var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;"
		."ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';"
		."var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);"
		."})();</script>";
}

function build_footer(){
	echo "<p class='pageFooter'>The Lightbox Swingers Club  - content <a rel='license' href='http://creativecommons.org/licenses/by-nc/3.0/' target='_blank'><img alt='CC NC-BY' style='border-width:0;position:relative;top:3px;' src='http://i.creativecommons.org/l/by-nc/3.0/80x15.png' /></a>&nbsp;&nbsp;- <a href='https://github.com/joshwedlake/Pass-the-Flipbook' target='_blank'>source code</a> &#169; <a href='http://linetestjournals.blogspot.com'>Josh Wedlake</a>, available under the GPL.</p>";
}

function build_links($row){
	if (isset($_SESSION['user'])) {
		//warn if flagged - its only possible to see flagged stuff if logged in
		if(intval($row["flags"])!=0) echo "<span class='menuFlag'>".$row["flags"]."!</span>";
		echo "<a href='create_new_animation.php?"
			. "ps_id=" . urlencode($row['id'])
			. "&ps_name=" . urlencode($row['name'])
			. "&ps_anim=" . urlencode($row['animator'])
			. "&ps_len=" . urlencode($row['frames_this']) . "' class='menuButton'>extend</a><br />";
	}
	else {
		echo "<a href='login.php?redir="
			. urlencode(
				"create_new_animation.php?"
				. "ps_id=" . urlencode($row['id'])
				. "&ps_name=" . urlencode($row['name'])
				. "&ps_anim=" . urlencode($row['animator'])
				. "&ps_len=" . urlencode($row['frames_this']))
			. "' class='menuButton'>extend</a><br />";
	}
	echo "<a href='player.php?"
		. "id=" . $row['id'] . "'>"
		. "<img src='get_frame.php?seq_id=".$row['id']."' width='200px' height='150px' style='border:none;margin:3px;'/></a><br />";
}

function ago($time) {
	$current_time=time();
    $time_diff = $current_time-$time;
    $units = array('second','minute','hour','day','week','month','year','decade');
    $length = array(1,60,3600,86400,604800,2630880,31570560,315705600);
	
	//starting with the smallest unit, see if the difference is more than one of these units
	//if it is, break.  if it isn't, try the next largest unit
    for($i = sizeof($length)-1; ($i >= 0)&&(($unit_count = $time_diff/$length[$i])<=1); $i--);
	
	//if the time is less than a second, then set it equal to a second
	if($i < 0) $i = 0;
    
	//round up the unit count
    $unit_count = round($unit_count);
	
	//if the unit count isn't one, then pluralise the units
	if($unit_count <> 1) $units[$i] .='s';

	//return "n units"
	return sprintf("%d %s",$unit_count,$units[$i]);
}


?>