<?php
ob_start();

error_reporting(E_ERROR | E_PARSE);

define("_ROOT_DIR", str_replace("\\", "/", getcwd()));
define("_ENVIROMENT", "dev"); //prod

global $boot;

if(!file_exists("./config/db.php"))
	header("location:install.php");

include _ROOT_DIR . "/include/bootstrap.php";
$boot = new Bootstrap("");
$boot->load();

ob_end_flush();
?>