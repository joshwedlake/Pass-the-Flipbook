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
About
</title>
<!-- Facebook open graph -->
<?php build_og("About"); ?>
<!-- Google Tracking Code -->
<?php build_google_analytics(); ?>

</head>
<body>
<?php
//build page title
build_top_bar();
?>
<h1>About</h1>

<div class="infoMain">

<h2>Concept</h2>
<p>The purpose of this site is to encourage and facilitate animation remixes by providing 
<?php
//just start drawing
if(isset($_SESSION['user']))echo "<a href='create_new_animation.php?&ps_id=-1'>";
else echo "<a href='login.php?redir=".urlencode("create_new_animation.php?&ps_id=-1")."'>";
echo "free online flipbook animation software</a>";?>
.  By drawing on this site you make your work available under a Creative Commons licence.  This means that you are still legally the owner of the work, and if anyone builds on it, or makes a derivative artwork, they have to credit you.  If they build upon your drawing using this site, we'll take care of the credits.</p>

<p>If you've come here because you're looking for animations to remix, you'll find a download link below the comments on the animation's player page.  This will give you the raw png frames and the info on who to credit.  I'd also really appreciate it if you mention this site as well if you source frames from here.</p>

<h2>You Might Also Like</h2>
<p><a href='http://mrdoob.com/projects/harmony/'>MrDoob's Harmony</a>, a tool which made me aware of the possibilities of drawing in a browser window.  Like this site, Harmony is also open source and free to use.</p>
<p><a href='http://www.pencil-animation.org/'>Pencil</a>, no nonsense traditional animation software.  If you want to get started in animation and your looking for a free open source animation program, and you feel like you've outgrown the limits of this site's flipbook, then Pencil is for you.  Its bitmap based and handles pressure sensitivity, so if you're coming across from traditional 2D animation, you'll probably prefer Pencil to Adobe Flash.</p>

<h2>Help</h2>
<p>There isn't any help for now, unless you manage to break something, in which case you can <a href='trouble.php'>report trouble</a> here.  If you're suffering from animation addiction, you'll find everything you need to get your kicks at the
<?php if(isset($_SESSION['user']))echo "<a href='create_new_animation.php?&ps_id=-1'>";
else echo "<a href='login.php?redir=".urlencode("create_new_animation.php?&ps_id=-1")."'>";?>
drawing board</a>, and you can discuss your problems openly in the comments!  If you want to know more about animation techniques, give some of these books a try:</p>

<p>
<a href="http://www.amazon.co.uk/gp/product/0571238343/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=0571238343"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=0571238343&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=0571238343" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
<a href="http://www.amazon.co.uk/gp/product/0789322099/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=0789322099"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=0789322099&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=0789322099" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
<a href="http://www.amazon.co.uk/gp/product/0240521609/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=0240521609"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=0240521609&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=0240521609" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
<a href="http://www.amazon.co.uk/gp/product/0789316846/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=0789316846"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=0789316846&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=0789316846" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
<a href="http://www.amazon.co.uk/gp/product/0786860707/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=0786860707"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=0786860707&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=0786860707" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
<a href="http://www.amazon.co.uk/gp/product/1879505975/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=1879505975"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=1879505975&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=1879505975" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
<a href="http://www.amazon.co.uk/gp/product/0415580242/ref=as_li_tf_il?ie=UTF8&tag=thelintesjou-21&linkCode=as2&camp=1634&creative=6738&creativeASIN=0415580242"><img border="0" src="http://ws.assoc-amazon.co.uk/widgets/q?_encoding=UTF8&Format=_SL110_&ASIN=0415580242&MarketPlace=GB&ID=AsinImage&WS=1&tag=thelintesjou-21&ServiceVersion=20070822" ></a><img src="http://www.assoc-amazon.co.uk/e/ir?t=thelintesjou-21&l=as2&o=2&a=0415580242" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />

</p>

<h2>Tech-m-ology</h2>
<p>This site is coded in php, mySQL and Javascript, using HTML5's Canvas property to display the animations, and for the drawing board.  Help yourself to the Javascript code - its licensed under the GPL.  Please credit me and link to this site if you make anything exciting.  If you're interested in the php code, contact me directly.  My email is on the <a href='trouble.php'>trouble page</a>.</p>

</div>

<br /><br />
<?php
//build page footer
build_footer();
?>
</body>
</html>