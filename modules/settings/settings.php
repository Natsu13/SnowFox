<?php
/**
 * Name: Settings
 * Description: Settings page for user account
 * Version: 1.0
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: settings
 */

$this->hook_register("init.setting", "settings_init", -10);
$this->hook_register("page.settings.init.setting", "settings_init_setting", -10);
$this->hook_register("page.settings", "settings_page_draw", 0);

function settings_init($t){
	$t->router->add("settings[/<action=edit>]", "module=settings&action=<action>");	
}

function settings_init_setting($t){
	$t->root->config->set("pre-title", t("Account settings"));
}

function settings_page_draw($t, &$output){
	if($_GET["action"] == "edit"){
		if(isset($_POST["nickname"])){
			$data = array(
				"nick" => $_POST["nickname"],
				"email" => $_POST["email"],
			);
			if($_POST["password"] != ""){
				$data["heslo"] = sha1($_POST["password"]);
			}
			User::setData("country", $_POST["country"]);
			dibi::query('UPDATE :prefix:users SET ', $data, "WHERE `id`=%s AND `heslo`=%s", User::current()["id"], $_POST["key"]);
			header("location:?ok");
		}
		$user = User::current();
		if(isset($_GET["ok"])){
			$t->root->page->error_box(t("updated"), "ok");
		}
		$output.= "<form method=post onSubmit=\"return checkPass('#key', this);\"><input type=hidden name=key id=key value=''>";
			$output.="<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Nickname")."</label><div class=\"col-sm-10\"><input type=text class='form-control' name=nickname autocomplete=off value='".$user["nick"]."'>";
				$output.="<small class=\"form-text text-muted\">".t("This is only a nickname, not a login name that cannot be changed.")."</small>";
			$output.="</div></div>";
			$output.="<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Password")."</label><div class=\"col-sm-10\"><input class='form-control' type=password name=password autocomplete=off value=''>";
				$output.="<small class=\"form-text text-muted\">".t("Fill in only when changing the password.")."</small>";
			$output.="</div></div>";
			$output.="<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">Email</label><div class=\"col-sm-10\"><input class='form-control' type=text name=email value='".$user["email"]."'>";
			$output.="</div></div>";
			/*
			$country = $t->root->config->open("./config/country_list.txt");
			$output.="<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">Země</label><div class=\"col-sm-10\"><select class='form-control' id=country name=country class='input' style=''>";
				$select = User::getData("country", 55);
				$i=0;
				foreach($country as $countr){
					if($countr[0] != "English Name")
						$output.="<option value='".$i."' ".($select == $i?"selected":"").">".$countr[0]."</option>";
					$i++;
				}
			$output.="</select></div></div>";	
			*/		
			$output.="<div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-2'></label><div class='col-12 col-sm-10'><input type=submit class='btn btn-primary' name=update value='".t("Edit profile")."'></div></div>";
		$output.="</form>";		
	}else{
		
	}
}