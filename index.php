<?php
ob_start();

error_reporting(E_ERROR | E_PARSE);

define("_ROOT_DIR", str_replace("\\", "/", getcwd()));
define("_ENVIROMENT", "dev"); //prod
//define("CUSTOM_IP", "48.24.245.200");

//Override wedost shit! Only with htaccess
//header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
//header("Cache-Control: post-check=0, pre-check=0", false);
//header("Pragma: no-cache");
/*
# BEGIN Expire headers
<ifModule mod_expires.c>
Header unset ETag
Header set Cache-Control "max-age=0, no-cache, no-store, must-revalidate"
Header set Pragma "no-cache"
Header set Expires "Sat, 1 Jan 2000 01:00:00 GMT"
</ifModule>
# END Expire headers
*/

global $boot;

if(!file_exists("./config/db.php"))
	header("location:install.php");

include _ROOT_DIR . "/include/bootstrap.php";
$boot = new Bootstrap("");
$boot->load();

ob_end_flush();
?>