<?php
use Entrenos\Utils\Cookie;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';

session_start();
if (isset($_SESSION['user_id'])) { 
	// Code for logged members
    $log->info("Destroying session for " . $_SESSION['login'] . " (user id: " . $_SESSION['user_id'] . ")");
	session_unset();
	session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/", $cookie_domain);
    if (!empty($_COOKIE['uc'])) {
        // Remove DB cookie entry
        $current_cookie = Cookie::fetch_from_db($conn, $_COOKIE['uc']);
        $num_removed = $current_cookie->remove($conn);
        $log->debug("Removed (result " . $num_removed . ") cookie entry " . $current_cookie->id . " from DB");
        // Remove from browser
        $cookie_result = Cookie::invalidate_all();
        $log->debug("Set cookie to expire (" . json_encode($cookie_result) . ") on " . $_SERVER['SERVER_NAME']);
    }
} else {
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
} 
header('Location: ' . $base_url);
exit();  
?> 
