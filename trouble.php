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
<link rel="stylesheet" type="text/css" href="default.css" />

<title>
Trouble
</title>
<!-- Facebook open graph -->
<?php build_og("Trouble"); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>
<?php
//build page title
build_top_bar();
?>
<h1>Report Trouble</h1>

<div class="infoMain">
<p>Stuff breaks from time to time.  When it does, I'd like to hear from you.</p>
<p>Please email me, putting <b>josh dot wedlake</b> before the at, then <b>googlemail dot com</b> after it.</p>
</div>

<br /><br />
<?php
//build page footer
build_footer();
?>
</body>
</html>