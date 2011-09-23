<?php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//call this page with redir= new url

// Initialize the session.
session_start();

// Unset all of the session variables.
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params["path"], $params["domain"],
		$params["secure"], $params["httponly"]
	);
}

// destroy the session.
session_destroy();

//redirect the user to the requested page
header('Location: '.$_GET["redir"]);


?>