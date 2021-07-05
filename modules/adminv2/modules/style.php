<?php
$action = $t->router->_data["action"][0];

$user = User::current();

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
		echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/style/'>Seznam</a></li>";
		echo "<li ".($action == "settings"?"class=select":"")."><a href='".$t->router->url_."adminv2/style/settings/'>".t("settings")."</a></li>";
	echo "</ul>";
}

if($action == "settings"){
	$file = _ROOT_DIR."/templates/".Database::getConfig("style")."/settings.php";
	if(file_exists($file)){
		include_once($file);
	}else{
		echo "<h1>".t("No settings")."</h1>";
	}
}
elseif($action == "show"){
	if(isset($_GET["set"])){
		dibi::query('UPDATE :prefix:settings SET ', array("value" => $_GET["set"]), "WHERE `name`=%s", "style");
		$t->root->config->set("style", $_GET["set"]);
		//header("location:".Router::url()."admin/style/");
		$t->root->page->error_box(t("the changes have been saved."), "ok", true);
	}
	/*
	echo "<br><b>Vyber složku se stylem</b><br><br>";
	$base_dir = _ROOT_DIR."/templates/";
	foreach(scandir($base_dir) as $file) {
        if($file == '.' || $file == '..') continue;
        $dir = $base_dir.DIRECTORY_SEPARATOR.$file;
        if(is_dir($dir)) {
			echo "<a class=polozky href='".$t->router->url_."admin/style/?set=".$file."'>";
			if(Database::getConfig("style") == $file){
				echo "<img src='".Router::url()."/modules/admin/images/radiobutton_yes.png' valign=bottom title='Aktualne zvoleny styl'> ";
			}else{
				echo "<img src='".Router::url()."/modules/admin/images/radiobutton_no.png' valign=bottom> ";
			}
			echo "".$file;
			if(Database::getConfig("style") == $file){ echo " <i>(Zvoleny styl)</i>"; }
			echo "</a>";
        }
	}
	*/
	echo "<div class='card small'><div class=content><ul class=list>";
	$base_dir = _ROOT_DIR."/templates/";
	foreach(scandir($base_dir) as $file) {
        if($file == '.' || $file == '..') continue;
        $dir = $base_dir.DIRECTORY_SEPARATOR.$file;
        if(is_dir($dir)) {
			echo "<li>";
				echo "<a href='".$t->router->url_."adminv2/style/?set=".$file."'>";
					echo "<div class=option style='margin-right: 14px;'>";
					if(Database::getConfig("style") == $file){
						//echo "<img src='".Router::url()."/modules/admin/images/radiobutton_yes.png' valign=bottom title='Aktualne zvoleny styl'> ";
						echo "<div class='radio checked'></div>";
					}else{
						//echo "<img src='".Router::url()."/modules/admin/images/radiobutton_no.png' valign=bottom> ";
						echo "<div class='radio'></div>";
					}
					echo "</div>";
				echo "".$file;
				echo "</a>";
			echo "</li>";
        }
	}
}