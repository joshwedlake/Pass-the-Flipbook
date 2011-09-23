<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//start the session
session_start();

//includes
include("vars.php");
include("build_links.php");

?>
<!DOCTYPE HTML>
<html>
<head>

<meta name="description" content="Pass the Flipbook, Drink and Draw, The Lightbox Swinger's Club, Collaborative Animation Tools, Online Pencil Tests">
<meta name="keywords" content="animation,video,pencil test,line test,flipbook,animation tools,animation software,animator's club">
<link href='http://fonts.googleapis.com/css?family=Nothing+You+Could+Do' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="default.css" />
<title>
Intro
</title>
<!-- Facebook open graph -->
<?php build_og("Intro"); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>
<?php
//build page title
build_top_bar();
?>
<h1>Intro: Passing the Flipbook...</h1>

<div class="infoMain" style="font-family: 'Nothing You Could Do', cursive;text-align:justify;">

<p>If you did maths prep in the corner of maths exercise books, then sadly this site isn&#39;t for you&#59; otherwise though, you've come to the right place&#33;  The basic idea is this&#58; you draw a picture, then you turn the page (or add a frame in our case), then you draw another picture, turn the page again, and add another and so on, until eventually when you have enough of them in a row you've got a short animation.  Then you pass the maths book to the guy (or girl) next to you... and he (or she) keeps drawing... and its probably a picture of something completely different, because at age 10 your attention spans were n&#39;t great.</p>
<div style="float:right;line-height:100%;text-align:center;margin-left:35px;"><img src='flipbook.gif' /><br /><span style="font-family:'Arial', Arial, sans-serif;font-size:x-small;">based on <a href='player.php?id=33'>&#39;scratch&#39;</a> by <a href='browse_by_animator.php?animator=topaz'>&#39;topaz&#39;</a></span></div>
<p>Several years down the line and they still aren&#39;t much better.  Such is the game of <b>pass the flipbook</b>, or whatever you want to call it.  The fun part is the passing bit, where you take over someone else&#39;s drawing - the collaboration.  The purpose of this site is to allow you to take over a stranger&#39;s flipbook, and start drawing, and if you're lucky, when your back's turned, someone might just sneak up on your opus magnus and write a new ending.</p>
<p>When you see a flipbook you want to grab, just click the <b>extend</b> link to go to the lightbox.</p>
</div>

<h2 style="margin-left:300px;margin-top:30px;"><a href='menu.php' style="outline:1px dashed;padding:4px;">Get Scribbling&#62;&#62;</a></h2>
<br /><br />
<?php
//build page footer
build_footer();
?>
</body>
</html>