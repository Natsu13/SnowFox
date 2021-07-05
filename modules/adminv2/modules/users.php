<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
		echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/users/'>".t("List")."</a></li>";
		echo "<li ".($action == "new"?"class=select":"")."><a href='".$t->router->url_."adminv2/users/new/'>".t("New user")."</a></li>";
		echo "<li ".($action == "block"?"class=select":"")."><a href='".$t->router->url_."adminv2/users/block/'>".t("Blocking")."</a></li>";
		//echo "<li ".($action == "pole"?"class=select":"")."><a href='".$t->router->url_."adminv2/users/pole/'>".t("custom fields")."</a></li>";
		echo "<li ".($action == "authorization"?"class=select":"")."><a href='".$t->router->url_."adminv2/users/authorization/'>".t("Permission")."</a></li>";
		echo "<li ".($action == "register"?"class=select":"")."><a href='".$t->router->url_."adminv2/users/register/'>".t("Register")."</a></li>";
	echo "</ul>";
}

if($action == "block"){
	echo "<div class='content padding'>";
		if(isset($_POST["add"])){
			$prem = false;
			if(User::find("ip", $_POST["ip"])){
				if(User::permission(User::find("ip", $_POST["ip"])["permission"])["level"] == 10000)
					$prem = true;
			}
			if($prem){
				$t->root->page->error_box(t("you can not ban a user IP address with rank")." ".User::permission(User::find("ip", $_POST["ip"])["permission"])["name"], "error", true);
			}else{
				$data = array(
								"nick" 			=> $_POST["user"],
								"ip" 			=> $_POST["ip"],
								"time_long" 	=> strtotime("+".$_POST["hours"]." hour"),
								"add_ip" 		=> Utilities::ip(),
								"add_user" 		=> User::current()["id"],
								"information" 	=> $_POST["whyban"],
								"interinfo" 	=> $_POST["infoadmin"],
								"action"		=> $_POST["action"]
						);
				$result = dibi::query('INSERT INTO :prefix:block', $data);
				header("location:".$t->router->url_."adminv2/users/block/");
			}
		}
		if(isset($_GET["unban"])){
			$id = $_GET["unban"];
			dibi::query('UPDATE :prefix:block SET ', array("time_long" => time()), 'WHERE `id`=%s', $id);
			header("location:".$t->router->url_."adminv2/users/block/");
		}

		echo "<div class='right-side'>";
			echo "<div class='card expandable' data-expandable='true'>";
				echo "<div class=title>".t("block")."</div>";
				echo "<div class='content' style='overflow-x: hidden;'>";
					echo "<div class=padding>";
						echo "<form action=# method=post>";
							echo "<b>".t("user")."</b><br>";
								//<input type=text name=user id=user style='width:100%;'>
								echo "<select id=user name=user class=selinp style='width:95%;'>";
								echo "<option id='0' selected> - Vyber uživatele - </option>";
								$result = dibi::query('SELECT * FROM :prefix:users');
								foreach ($result as $n => $row) {
									$permission = User::permission(User::get($row["id"])["permission"]);
									echo "<option value='".$row["id"]."'>".$row["nick"]."</option>";
								}
								echo "</select>";
							echo "<br><input type=text name=ip id=ip data-title='".t("IP address")."' style='width:100%;'>";
							echo "<br><b>".t("akce")."</b><br>";
								echo "<select id=action name=action style='width:100%;'>";
								echo "<option id='0' value='all' selected>".t("all")."</option>";
								foreach (User::$blockType as $n => $row) {
									echo "<option value='".$row."'>".$row."</option>";
								}
								echo "</select>";
							echo "<br><b>".t("until")."</b><br><input type=text name=hours style='width:70%;' placeholder=0 class=price data-postfix='".t("hours")."'>";
							echo "<br><input type=text name=whyban data-title='".t("information for blocked users")."' style='width:100%;'>";
							echo "<br><input type=text name=infoadmin data-title='".t("information for administrators")."' style='width:100%;'>";
							echo "<br><input type=submit name=add style=\"margin-top: 8px;\" class='btn btn-primary' value='".t("block")."'>";
						echo "</form>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
		echo "</div>";
		echo "<div class='left-side'>";
			echo "<table class='table table-sm tablik' style='table-layout: auto;margin:0px;'>";
			echo "<tr><th width=100>".t("blocked")."</th><th width=100>".t("added")."</th><th width=100>".t("expires")."</th><th width=100>".t("info")."</th><th width=80>".t("action")."</th></tr>";
			$result = dibi::query('SELECT * FROM :prefix:block ORDER BY id DESC');
			foreach ($result as $n => $row) {
				$permission = User::permission(User::get($row["add_user"])["permission"]);
				echo "<tr>";
					echo "<td>".($row["nick"]==""?"<span style='color:silver'>".t("is not")."</span>":User::get($row["nick"])["nick"])."<br> / <span style=\"font-size:10px;\">".($row["ip"]==""?"<span style='color:silver'>".t("is not")."</span>":$row["ip"])."</span></td>";
					echo "<td><span style='color:".$permission["color"]."'>".($row["add_user"]==""?"<span style='color:silver'>".t("is not")."</span>":User::get($row["add_user"])["nick"])."</span><br> / <span style=\"font-size:10px;\">".$row["add_ip"]."</span></td>";
					echo "<td>".Strings::str_time($row["time_long"])."</td>";
					echo "<td>(<span style='color:#888888;'>".$row["action"]."</span>) ".$row["information"]."</td>";
					echo "<td>";
						if($row["time_long"]>time())
							echo "<a href='".Router::url()."adminv2/users/block/?unban=".$row["id"]."'><img src='".Router::url()."/modules/admin/images/delete.png' class=des> ".t("unban")."</a>";
						else
							echo "<i>Vypršelo</i>";
					echo "</td>";
					if($row["interinfo"] != ""){
						echo "<tr><td colspan=5><b>".t("interinfo")."</b>:<br>".$row["interinfo"]."</td></tr>";
					}
				echo "</tr>";
			}
			echo "</table>";
		echo "</div>";
	echo "</div>";
}
elseif($action == "new"){
	if(isset($_POST["create"])){
		$data = array(
						"jmeno" 	=> $_POST["username"],
						"nick" 		=> $_POST["username"],
						"heslo" 	=> sha1($_POST["pass"]),
						"email" 	=> $_POST["email"],
						"ip" 		=> Utilities::ip(),
						"kdy" 		=> time(),
						"prava" 	=> Database::getConfig("default-permision"),
						"avatar"	=> Database::getConfig("default-avatar"),
						"blokovan" 	=> 0
				);
		$result = dibi::query('INSERT INTO :prefix:users', $data);
		header("location:".$t->router->url_."adminv2/users/edit/".dibi::InsertId());
	}
	echo "<form action=# method=post>";
		echo "<table class=tabfor style='width:70%;margin:20px 0px;'>";
		echo "<tr><td width=100><label>".t("username")."</label></td><td width=430><input type=text name=username></td></tr>";
		echo "<tr><td width=100><label>".t("password")."</label></td><td width=430><input type=password name=pass style='width:100%;'></td></tr>";
		echo "<tr><td width=100><label>".t("email")."</label></td><td width=430><input type=text name=email></td></tr>";
		echo "</table>";
	echo "<input type=submit name=create value='".t("create")."'>";
	echo "</form>";
}
else if($action == "delete"){
	if($t->router->_data["id"][0] < 2){
		$t->root->page->error_box(t("this user has permission <b>superuser</b> and therefore it can not be deleted"), "error");
	}else{
		dibi::query('DELETE FROM :prefix:users WHERE `id`=%s', $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/users/");
	}
}
else if($action == "edit"){
	$superuser = $t->root->config->getD("superuser", 1);

	if(isset($_POST["edit"])){
		$result = dibi::query("SELECT * FROM :prefix:users WHERE id=%i", $t->router->_data["id"][0])->fetch();

		if($result["prava"] == -20){
			$arr = array(
						"jmeno" => $_POST["jmeno"],
						"nick" => $_POST["jmeno"],
						"prava" => $_POST["permission"],
						"data" => Config::ssave(array("desc" => $_POST["desc"]))
					);
		}elseif($t->router->_data["id"][0] == $superuser){
			$arr = array(
						"nick" => $_POST["nick"],
						"email" => $_POST["email"],
						"ip" => $_POST["ip"]
					);
		}
		else{
			$blok = 0;
			if(isset($_POST["block"])){
				$block = 1;
			}
			if(isset($_POST["noactive"])){
				$block = ($block == 1? 3: 2);
			}
			$arr = array(
						"jmeno" => $_POST["jmeno"],
						"nick" => $_POST["nick"],
						"prava" => $_POST["permission"],
						"email" => $_POST["email"],
						"ip" => $_POST["ip"],
						"blokovan" => $block,
						"underuser" => $_POST["underuser"],
						"recovery" => ($block == 2 && $result["recovery"] == ""? Strings::random(8,Strings::$NUMBERS): $result["recovery"])
					);
			if($_POST["password"] != "") $arr["heslo"] = sha1($_POST["password"]);
			if(isset($_POST["expired"]))
				$arr["expired"] = strtotime($_POST["expired"], ($result["expired"] == ""? time(): $result["expired"]));
		}

		$plugin = $t->root->module_manager->hook_call("admin.user.edit.post", array("user" => $result), $arr);

		$old = array(
			"jmeno" => $result["jmeno"],
			"nick" => $result["nick"],
			"prava" => $result["prava"],
			"email" => $result["email"],
			"ip" => $result["ip"],
			"blokovan" => $result["blokovan"],
			"underuser" => $result["underuser"],
			"recovery" => $result["recovery"]
		);
		Utilities::addHistory("user", "account", "edited", array("old" => $old, "new" => $arr), "Acount edited by ".User::current(true)["nick"], $t->router->_data["id"][0]);

		dibi::query('UPDATE :prefix:users SET ', $arr, 'WHERE `id`=%s', $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/users/edit/".$t->router->_data["id"][0]."/ok");
	}

	if($t->router->_data["state"][0] == "ok"){
		$t->root->page->error_box(t("user has been updated"), "ok", true);
	}
	if($t->router->_data["state"][0] == "removed"){
		$t->root->page->error_box(t("session removed"), "ok", true);
	}

	if(isset($_GET["remove"])){
		dibi::query('UPDATE :prefix:sessions SET ', array("timeto" => time()), 'WHERE `id`=%s', $_GET["remove"]);
		header("location:".$t->router->url_."adminv2/users/edit/".$t->router->_data["id"][0]."/removed");
	}

	if($_GET["state"] == "loginas"){
		if(User::session_new_as($_GET["id"])){
			Utilities::addHistory("user", "account", "loginas", array("user" => User::current()["id"]), "User ".User::current(true)["nick"]." login as this user", $t->router->_data["id"][0]);
			header("location:".Router::url());
		}else{
			$t->root->page->error_box(t("This function cannot be used"), "error", true);
		}
	}

	$result = dibi::query("SELECT * FROM :prefix:users WHERE id=%i", $t->router->_data["id"][0])->fetch();
	$permission = User::permission($result["prava"]);

	if($t->router->_data["id"][0] == $superuser)
		$t->root->page->error_box(t("this user has permission <b>superuser</b> and therefore it can not be fully updated"), "info");

	echo "<h1>".t("Editing user")." \"".$result["nick"]."\"</h1>";

	if($t->router->_data["id"][0] != $superuser && User::current()["id"] != $_GET["id"]) {
		echo "<div class=tool-bar>";
			echo "<a href='".Router::url()."adminv2/users/edit/".$_GET["id"]."/loginas' class=\"button\"><i class=\"fas fa-key\"></i> ".t("Login as")."</a>";
		echo "</div>";
	}

	echo "<div style='float:right;width: 330px;'>";	
	echo "<ul class='menu sub' id='toglmen'>";
		echo "<li class=select data-parent='act'><a href=#>Aktivita</a></li>";
		echo "<li data-parent='rel'><a href=#>Relace</a></li>";
	echo "</ul>";
	if($result["id"] > 0){
		echo "<div id='rel' style='height: 370px;overflow: auto;background: #f9f9f9;border: 1px solid silver;overflow-x: hidden;'><table class='table table-sm' style='width: 100%;'><tr><th style='background: silver;'><span style='font-size:22px;'>".t("Relation")."</span></th></tr>";
				$resul_ = dibi::query('SELECT * FROM :prefix:sessions WHERE user = %s', $result["id"] ,'ORDER BY date DESC LIMIT 4');
				foreach ($resul_ as $n => $row) {
					if(time()>$row["timeto"]) $how = false; else $how = true;
					echo "<tr><td style='font-size:14px;padding: 10px;margin-bottom: 5px;".($how?"background: #d0d0d0;":"background: #2f2f2f;border: 1px solid #484848;color: #6b6b6b;")."'>";
						if($how){ echo "<div style='float:right;'><a href='?remove=".$row["id"]."'><img src='".Router::url()."/modules/admin/images/delete.png' title='".t("Cancel")."'></a></div>"; }
						else{ echo "<div style='float:right;'>".t("not applicable")."</div>"; }
						echo "<b>".t("date of create").": </b>".Strings::str_time($row["date"])."<br>";
						echo "<b>".t("valid until").": </b>".Strings::str_time($row["timeto"])."<br>";
						echo "<b>".t("ip").": </b>".$row["ip"]."<br>";
						$dt = Config::sload($row["data"]);
						echo "<b>".t("browser").": </b>".$dt["browser"]." (".$dt["version"].")<br>";
						echo "<span style='font-size:10px;'><b>".t("hash").": </b>".$row["hash"]."</span>";
					echo "</td></tr>";
				}
		echo "</table></div>";
	}
	echo "<div id='act' style='height: 370px;overflow: auto;background: #f9f9f9;border: 1px solid silver;'><table class='table table-sm' style='width: 100%;'><tr><th colspan=2 style='background: silver;'><span style='font-size:22px;'>".t("Actions")."</span></th></tr>";
				$resul_ = Utilities::getHistory("user", "account", "", $result["id"]);
				foreach ($resul_ as $n => $row) {
					echo "<tr><td colspan=2 style='font-size:13px;'>";
						if($row["type"] == "user_activation_resend") 
							echo "Požadavek o znovu zaslání aktivačního kodu";	
						elseif($row["type"] == "user_activation_activated")
							echo "Uživatelský účet byl aktivován";	
						elseif($row["type"] == "user_wrongPass")
							echo "Pokus o přihlášení s špatným heslem";	
						elseif($row["type"] == "user_created")
							echo "Účet byl vytvořen";	
						elseif($row["type"] == "user_activated")
							echo "Účet byl aktivován";	
						elseif($row["type"] == "user_edited")
							echo "Účet byl upraven";	
						elseif($row["type"] == "user_loginas")
							echo "Přihlášení za uživatele";
						else
							echo t($row["type"]);
					echo "</td></tr><tr style='font-size:13px;'>";
					echo "<td width=160><i class=\"far fa-clock\"></i> <b>".Strings::str_time($row["date"])."</b></td>";
					echo "<td>.:. <b>".$row["ip"]."</b></td>";
					echo "</tr><tr><td colspan=2 style='font-size:13px;border-bottom: 2px solid silver;'><a href=# onclick=\"$('#data_".$row["id"]."').toggle();\"><i class=\"far fa-eye\"></i> Zobrazit data</a>";
						echo "<div id='data_".$row["id"]."' style='display:none;'>";
						echo "Type: ".$row["type"]."<br>";
						echo "<textarea style='width:100%;' rows=2>".$row["text"]."</textarea>";	
						echo "<textarea style='width:100%;' rows=5>".$row["data"]."</textarea>";		
						echo "</div>";			
					echo "</td></tr>";
				}
		echo "</table></div>";
	echo "</div>";
	?>
	<script>
		$(function(){
			var lastshow = null;
			$("#toglmen li").each(function(){
				$("#"+$(this).data("parent")).hide();
				if($(this).hasClass("select")){
					lastshow = $("#"+$(this).data("parent"));
					lastshow.show();
				}
				$(this).click(function(){
					if(!$(this).hasClass("select")){
						lastshow.hide();
						lastshow = $("#"+$(this).data("parent"));
						lastshow.show();
						$("#toglmen li").each(function(){$(this).removeClass("select");});
						$(this).addClass("select");
						return false;
					}				
				});
			});
		});
	</script>
	<?php

	echo "<form action=# method=post>";
		if($result["prava"] == -20){
			$data = Config::sload($result["data"]);
			if(!isset($data["desc"])) $data["desc"] = "";
			echo "<table class=tabfor style='width:70%;margin:20px 0px;table-layout: fixed;'>";
				echo "<tr><td width=100><label>".t("username")."</label></td><td width=430><input type=text name=jmeno value='".$result["jmeno"]."'></td></tr>";
				echo "<tr><td><label>".t("permission")."</label></td><td>";
					echo "<select id=permission name=permission style='width:300px;'><option value='-20'>".t("system account")."</option><option disabled>---------------</option>";
						$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
						foreach ($resul_ as $n => $row) {
							echo "<option value='".$row["id"]."' ".($row["id"] == $result["prava"]?"selected":"").">".$row["name"]." (Level: ".$row["level"].")</option>";
						}
					echo "</select>";
				echo "</td></tr>";
				echo "<tr><td width=100><label>".t("description")."</label></td><td width=430><input type=text name=desc value='".$data["desc"]."'></td></tr>";
			echo "</table>";
			echo "<input type=submit name=edit value='".t("edit")."'>";
		}else{
			echo "<table class=tabfor style='width:70%;margin:20px 0px;table-layout: fixed;'>";
			if($result["id"] == $superuser)
				echo "<tr><td width=100><label>".t("username")."</label></td><td width=430>".$result["jmeno"]."</td></tr>";
			else
				echo "<tr><td width=100><label>".t("username")."</label></td><td width=430><input type=text name=jmeno value='".$result["jmeno"]."'></td></tr>";
			echo "<tr><td><label>".t("nick")."</label></td><td><input type=text name=nick value='".$result["nick"]."'></td></tr>";
			echo "<tr><td></td><td><span class=desc>".t("display name")."</span></td></tr>";
			if($result["id"] == $superuser)
				echo "<tr><td><label>".t("password")."</label></td><td>".t("can not change")."...</td></tr>";
			else
				echo "<tr><td><label>".t("password")."</label></td><td><input type=password name=password value='' style='width:100%;'></td></tr>";
			echo "<tr><td></td><td><span class=desc>".t("complete only when you change your password")."</span></td></tr>";
			if($result["id"] == $superuser)
				echo "<tr><td><label>".t("permission")."</label></td><td><select id=superuser style='width: 100%;'><option disabled>".t("superuser")."</option></select></td></tr>";
			else{
				echo "<tr><td><label>".t("permission")."</label></td><td>";
					echo "<select id=permission name=permission style='width:100%;'><option value='-20'>".t("system account")."</option><option disabled>---------------</option>";
						$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
						foreach ($resul_ as $n => $row) {
							echo "<option value='".$row["id"]."' ".($row["id"] == $result["prava"]?"selected":"").">".$row["name"]." (Level: ".$row["level"].")</option>";
						}
					echo "</select>";
				echo "</td></tr>";
			}
			echo "<tr><td><label>".t("email")."</label></td><td><input type=text name=email value='".$result["email"]."'></td></tr>";
			echo "<tr><td><label>".t("IP address")."</label></td><td><input type=text name=ip value='".$result["ip"]."'></td></tr>";
			if($result["id"] != $superuser){
				echo "<tr><td><label></label></td><td><label><input type=toggle_swipe name=block value='1' ".($result["blokovan"] == 1 || $result["blokovan"] == 3?"checked":"")."> ".t("block account")."</label></td></tr>";
				echo "<tr><td><label></label></td><td><label><input type=toggle_swipe name=noactive value='1' ".($result["blokovan"] == 2 || $result["blokovan"] == 3?"checked":"")."> ".t("account not active")."".(($result["blokovan"] == 2) ? " (Aktivační kod: <b>".$result["recovery"]."</b>)": "")."</label></td></tr>";
			}

			if($permission["expired"]){
				echo "<tr><td colspan=2><hr style='margin: 16px 0px;'/></td></tr>";
				echo "<tr><td><label>".t("account expired")."</label></td><td>";
					$date = $result["expired"];
					if($date == 0) $date = "";
					if($date != "") $date = date(Utilities::getTimeFormat(), $date);
					echo "<input type=text name=expired class=\"".($result["expired"] == ""?"is-invalid":"")."\" value='".$date."'>";
					if($result["expired"] == "")
						echo "<div class=inpoerror style='display: block;'>".t("expiration has not been set, account is blocked")."</div>";
					echo "<div class=desc>".t("for example")." ".date(Utilities::getTimeFormat(), time())." nebo \"<i>+1 month</i>\" pro přidání jednoho měsíce k aktuální expiraci</div>";
				echo "</td></tr>";
			}

			/*
			if($result["id"] == $superuser)
				echo "<tr><td><label>".t("under user")."</label></td><td>".t("superuser")."</td></tr>";
			else{
				echo "<tr><td><label>".t("under user")."</label></td><td>";
					$resul_ = dibi::query('SELECT * FROM :prefix:permission WHERE level = 10000')->fetch();
					$idperm = $resul_["id"];
					echo "<select id=underuser name=underuser style='width:100%;'><option value=''> - Nikdo - </option>";
						$resul_ = dibi::query('SELECT * FROM :prefix:users WHERE prava = %i', $idperm ,' ORDER BY id');
						foreach ($resul_ as $n => $row) {
							echo "<option value='".$row["id"]."' ".($row["id"] == $result["underuser"]?"selected":"").">".$row["jmeno"]."</option>";
						}
					echo "</select>";
				echo "</td></tr>";
			}
			echo "<tr><td></td><td><span class=desc>".t("can be set only for administration")."</span></td></tr>";
			*/
			echo "<tr><td colspan=2><hr style='margin: 16px 0px;'/></td></tr>";
			$r = "";
			$plugin = $t->root->module_manager->hook_call("admin.user.edit", array("user" => $result), $r);

			echo "</table>";

			echo "<input type=submit name=edit value='".t("edit")."'>";
		}
	echo "</form>";

}
elseif($action == "register" and $who == "form"){
	echo "<div class=bottommenu><a href='".Router::url()."adminv2/users/register/'>".t("back")."</a></div>";
	if(isset($_POST["setform"])){
		$pole = array(
					"registration-form" => $_POST["regform"]
				);

		foreach($pole as $key => $value){
			dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
			$t->root->config->set($key, $value);
		}
		$t->root->page->error_box(t("the changes have been saved."), "ok", true);
	}
	$custom_form = $t->root->config->get("registration-form");
	if($custom_form != -1 and isset($_POST["editform"])){
		$settingForm = null;
		$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $custom_form, " ORDER BY position");
		foreach ($result_ as $n => $row) {
			$settingForm[$row["id"]] = $_POST["funct_".$row["id"]];
		}
		$settingForm["parent"] = $custom_form;
		dibi::query('UPDATE :prefix:settings SET ', array("value" => Config::ssave($settingForm)), "WHERE `name`=%s", "registration-form-setting");
		$t->root->config->set("registration-form-setting", Config::ssave($settingForm));
		$t->root->page->error_box(t("the changes have been saved."), "ok", true);
	}
	echo "<div style='padding:5px;'><form action=# method=post>Pro registraci použít vlastní formulář ";
		echo "<select id=regfrom name=regform style='width:300px;'>";
			echo "<option value='-1' ".($custom_form == -1?"selected":"").">- Nepoužívat -</option>";
			$result = dibi::query('SELECT * FROM :prefix:form');
			foreach ($result as $n => $row) {
				echo "<option value='".$row["id"]."' ".($custom_form == $row["id"]?"selected":"").">".$row["name"]."</option>";
			}
		echo "</select> <input type=submit name=setform class='btn btn-primary btn-sm' value='Změnit'>".(($custom_form!="" && $custom_form!= "-1")?" <a href=\"".Router::url()."adminv2/content/form-edit/".$custom_form."\">Upravit</a>":"")."</form>";

		if($custom_form != -1){
			$sf = Config::sload($t->root->config->get("registration-form-setting"));
			if($sf["parent"] != $custom_form) $sf = null;

			echo "<h3>Nastavení formuláře</h3>";
			echo "<div style='float:right;width:400px;'>Pokud chcete vytvořit možnost souhlasit s registračnímy podmínkami musíte vytvořit selectbox jako checkbox nejlépe a první položka musí být <i>Souhlasím s registračnímy podmínkamy</i><br><br>Každý formulář musí obsahovat <b>jméno, heslo, heslo pro kontrolu, email a registrační tlačítko!</b></div>";
			echo "<form action=# method=post><table>";
			$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $custom_form, " ORDER BY position");
			foreach ($result_ as $n => $row) {
				if(!isset($sf[$row["id"]])) $sf[$row["id"]]=-1;
				echo "<tr><td width=300>".$row["name"]."</td><td>Použít jako <select style='width:250px;' name='funct_".$row["id"]."' id='funct_".$row["id"]."'>";
					echo "<option value=''>- nic -</option>";
					if($row["type"] == "password"){
						echo "<option value='password' ".($sf[$row["id"]] == "password"?"selected":"").">Heslo</option>";
						echo "<option value='password2' ".($sf[$row["id"]] == "password2"?"selected":"").">Heslo pro kontrolu</option>";
					}elseif($row["type"] == "select"){
						echo "<option value='regaccept' ".($sf[$row["id"]] == "regaccept"?"selected":"").">Potvrzení podmínek</option>";
					}else{
						echo "<option value='jmeno' ".($sf[$row["id"]] == "jmeno"?"selected":"").">Jméno</option>";
						echo "<option value='email' ".($sf[$row["id"]] == "email"?"selected":"").">Email</option>";
						echo "<option value='register' ".($sf[$row["id"]] == "register"?"selected":"").">Tlačítko registrace</option>";
					}
				echo "</select></td></tr>";
			}
			echo "<tr><td></td><td><span class=info>Formulář se také uloží do dat v formuláři (Heslo bude vymazáno!)</span></td></tr>";
			echo "<tr><td></td><td><input type=submit name=editform class='btn btn-primary' value='Upravit'></td></tr>";
			echo "</table></form>";
		}
	echo "</div>";
}
elseif($action == "register"){
	echo "<div class=bottommenu><a href='".Router::url()."adminv2/users/register-form/'>".t("edit register form")."</a></div>";
	if(isset($_POST["edit"])){
		$pole = array(
					"registration-enable" => (isset($_POST["enablereg"])?1:0),
					"registration-activation" => (isset($_POST["requireemail"])?1:0),
					"registration-conditions" => $_POST["regtext"],
					"registration-leftmenu" => (isset($_POST["leftmenu"])?1:0)
				);

		foreach($pole as $key => $value){
			//dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
			$t->root->config->update($key, $value);
		}
		$t->root->page->error_box(t("the changes have been saved."), "ok", true);
	}
	$dprm = User::permission($t->root->config->get("default-permision"));
	if($dprm["level"] >= 5000){
		echo "<div class='box error'>".t("it's a big risk giving the user after registering role of level higher than 5000")."</div>";
	}
	echo "<div style='padding:10px;'>".t("after registration, assign permissions")." <span style='color:".$dprm["color"].";' class='dottedhighlight pcol".$dprm["color"]."'>".$dprm["name"]."</span> <a href='".$t->router->url_."adminv2/users/'>".t("change")."</a> | <a href='".Router::url()."adminv2/users/permedit/".$dprm["id"]."/'>".t("edit")."</a>";

	echo "<br><br><table style='padding-top:10px;'><form action=# method=post>";
		echo "<tr><td width=250 valign=top><h3 style='padding:0px;padding-top:0px;margin:0px;'>".t("settings")."</h3></td><td><label><input type=toggle_swipe name=enablereg value=1 ".($t->root->config->get("registration-enable")==1?"checked":"")."> ".t("enable registraion")."</label>";
		echo "<br><label><input type=toggle_swipe name=requireemail value=1 ".($t->root->config->get("registration-activation")==1?"checked":"")."> ".t("require e-mail activation")."</label>";
		echo "<br><label><input type=toggle_swipe name=leftmenu value=1 ".($t->root->config->get("registration-leftmenu")==1?"checked":"")."> Vypnout boční menu</label></td></tr>";
		echo "<tr><td valign=top>".t("registration conditions")."</td><td width=400><textarea rows=4 name='regtext' style='width:100%;'>".$t->root->config->get("registration-conditions")."</textarea><br><span class=info>".t("leave blank for shutdown")."</span></td></tr>";
		echo "<tr><td></td><td><input type=submit class='btn btn-primary' name=edit value='".t("edit")."'></td></tr>";
	echo "</form></table>";

	echo "</div>";
}
else if($action == "permdelete"){
	if($t->router->_data["id"][0] < 2){
		$t->root->page->error_box(t("this is system permission"), "error");
	}else{
		dibi::query('DELETE FROM :prefix:permission WHERE `id`=%s', $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/users/authorization/");
	}
}
elseif($action == "newperm"){
	$data = array(
					"name" 		=> "Nové oprávnění",
					"level"		=> 5,
					"color"		=> "black"
				);
	$result = dibi::query('INSERT INTO :prefix:permission', $data);
	header("location:".$t->router->url_."adminv2/users/permedit/".dibi::InsertId());
}
else if($action == "permedit"){
	$result = dibi::query("SELECT * FROM :prefix:permission WHERE id=%i", $t->router->_data["id"][0])->fetch();

	if(isset($_POST["edit"])){
		if($result["level"] == 10000 or $result["level"] == 1 or $result["level"] == 0){
			$arr = array(
						"name"  => $_POST["name"],
						"color" => $_POST["color"]
					);
		}
		else{
			$arr = array(
						"name"  => $_POST["name"],
						"color" => $_POST["color"],
						"level" => $_POST["level"],
						"expired" => (isset($_POST["expired"])?1:0),
						"expired_register" => $_POST["expired_register"]
					);
		}

		if(($_POST["level"] > 9999 or $_POST["level"] < 2) and isset($_POST["level"])){
			header("location:".$t->router->url_."adminv2/users/permedit/".$t->router->_data["id"][0]."/error");
			exit;
		}elseif(isset($_POST["level"]) and dibi::query("SELECT * FROM :prefix:permission WHERE level=%i", $_POST["level"], "AND id!=%i", $result["id"])->count() > 0){
			header("location:".$t->router->url_."adminv2/users/permedit/".$t->router->_data["id"][0]."/error2");
			exit;
		}

		$prm = null;
		if($result["level"] == 10000){ $_POST["admin"] = true;$_POST["users"] = true;$_POST["system"] = true; }
		foreach(User::$perms as $p) {
			if(isset($_POST[$p])) $prm[$p] = 1; else $prm[$p] = 0;
		}
		$arr["data"] = Config::ssave($prm);

		dibi::query('UPDATE :prefix:permission SET ', $arr, 'WHERE `id`=%s', $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/users/permedit/".$t->router->_data["id"][0]."/ok");
	}

	if($t->router->_data["state"][0] == "ok"){
		$t->root->page->error_box(t("permission has been updated"), "ok", true);
	}elseif($t->router->_data["state"][0] == "error"){
		$t->root->page->error_box(t("level can't be more then 9999 and less than 2"), "error", true);
	}elseif($t->router->_data["state"][0] == "error2"){
		$t->root->page->error_box(t("level can't be same like other"), "error", true);
	}else if($result["level"] == 10000 or $result["level"] == 1)
		$t->root->page->error_box(t("this is system permission"), "info");

	echo "<h1>".t("editing permission")." \"".$result["name"]."\"</h1>";

	echo "<form action=# method=post>";
		echo "<table class=tabfor style='width:70%;margin:20px 0px;'>";
			echo "<tr><td width=100><label>".t("name")."</label></td><td width=430><input type=text name=name value='".$result["name"]."'></td></tr>";
			echo "<tr><td width=100><label>".t("color")."</label></td><td width=430><input type=text name=color value='".$result["color"]."'></td></tr>";
			if($result["level"] == 10000 or $result["level"] == 1 or $result["level"] == 0)
				echo "<tr><td width=100><label>".t("level")."</label></td><td width=430>".$result["level"]."</td></tr>";
			else
				echo "<tr><td width=100><label>".t("level")."</label></td><td width=430><input type=text name=level value='".$result["level"]."'></td></tr>";

			if($result["level"] != 10000 and $result["level"] != 1 and $result["level"] != 0) {
				echo "<tr><td width=100><label></label></td><td width=430><label><input type=toggle_swipe name=expired ".($result["expired"] == 1?"checked":"")."> ".t("expired permission?")."</label></td></tr>";			
				echo "<tr><td width=100><label>".t("expired time")."</label></td><td width=430><input type=text name=expired_register value='".$result["expired_register"]."'><div class=desc>".t("expiration time after registration").", použíjte pro příklad <i>+1 month</i></div></td></tr>";
			}

			echo "<tr><td colspan=2><hr style='margin:8px 0px;' /></td></tr>";
			echo "<tr><td valign=top><label>".t("permission")."</label></td><td>";
			$prm = Config::sload($result["data"]);
				foreach(User::$perms as $p) {
					if(!isset($prm[$p])) $prm[$p]=0;
					$disabled = false;
					if($result["level"] == 10000 && ($p=="admin" || $p=="users" || $p=="system"))
						$disabled = true;
					echo "<input type=toggle_swipe id='ch_".$p."' ".($disabled?"disabled='disabled'":"")." name='".$p."' ".($prm[$p] == 1?"checked":"")."> <label for='ch_".$p."' style='width:120px;display:inline-block;'>".$p."</label><span style='padding-left: 23px;padding-top: 4px;padding-bottom: 5px;font-size: 12px;'>".t("role_".$p)."</span><br>";
				}
			echo "</td></tr>";

		echo "</table>";
	echo "<input type=submit name=edit value='".t("edit")."'>";
	echo "</form>";

}else if($action == "authorization"){
	echo "<div class=bottommenu><a href='".Router::url()."adminv2/users/newperm/'>".t("new permission")."</a></div>";
	echo "<table class='table table-sm tablik'>";
	echo "<tr><th width=20>".t("id")."</th><th width=300>".t("name")."</th><th width=30>".t("count")."</th><th width=150>".t("level")."</th><th width=120>".t("color")."</th><th width=120>".t("action")."</th></tr>";
	$result = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
	foreach ($result as $n => $row) {
		echo "<tr>";
			echo "<td>".$row["id"]."</td>";
			echo "<td>".$row["name"]."</td>";
			echo "<td align=center><i><b>".(dibi::query('SELECT * FROM :prefix:users WHERE prava = %i', $row["id"])->count())."</b></i></td>";
			echo "<td>".$row["level"]."</td>";
			echo "<td style='background:".$row["color"].";color:".($row["color"]=="black"?"white":"black").";'>".$row["color"]."</td>";
			echo "<td>";
				echo "<a href='".Router::url()."adminv2/users/permedit/".$row["id"]."/'><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a> ";
				if($row["level"] == 10000 or $row["level"] == 1)
					echo "<span title='Nelze smazat'><i class=\"fas fa-lock\"></i> ".t("delete")."</span>";
				else
					echo "<a href='".Router::url()."adminv2/users/permdelete/".$row["id"]."/'><i class=\"far fa-trash-alt\"></i> ".t("delete")."</a>";
			echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
else if($action == "show"){
	$user = User::current();
	$superuser = $t->root->config->getD("superuser", 1);

	if(isset($_POST["setpermdef"])){
		$pole = array("default-permision" => $_POST["prm"]);
		foreach($pole as $key => $value){
			dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
			$t->root->config->set($key, $value);
		}
	}	
	if(isset($_POST["send"])){
		$t->root->config->update("superuser", ($_POST["user"]));
		header("location:".$t->router->url_."adminv2/users/");
	}
	echo "<div class=bottommenu>";
		echo "<form action=# method=post class=xbuttonline>".t("after registration, assign permissions")." <span class='black'><select id=prm name=prm style='width:200px;'>";
		$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
		foreach ($resul_ as $n => $row) {
			echo "<option value='".$row["id"]."' ".($row["id"] == $t->root->config->get("default-permision")?"selected":"").">".$row["name"]."</option>";
		}
		echo "</select></span> <input type=submit class='btn btn-primary' name=setpermdef value='".t("set")."'></form>";
	echo "</div>";

	$paginator = new Paginator(10, Router::url()."adminv2/users/?page=(:page)");
	$resultPaginator = $paginator->query('SELECT * FROM :prefix:users');

	echo $paginator->getPaginator();

	if($user["id"] == $superuser){
		echo "<div class='boxred' style='margin-top: 17px;'>";
			echo "<h2>Superuser</h2>";
			echo "Váš účet má nejvyší oprávnění vždy budete mít práva <b>".User::permission(User::find("id", $user["id"])["permission"])["name"]."</b>, nelze vás plně editovat, smazat a blokovat!";
			echo "<form action=# method=post class=fixsel>Předat práva superuser uživately (Pouze s právy <b>".User::permission(User::find("id", $user["id"])["permission"])["name"]."</b>) ";
				echo "<select id=user name=user style='width: 200px;'>";
				$result = dibi::query('SELECT * FROM :prefix:users');
				foreach ($result as $n => $row) {
					$permission = User::permission(User::get($row["id"])["permission"]);
					if($permission["level"] == 10000)
						echo "<option value='".$row["id"]."' ".($user["id"] == $row["id"]?"selected":"").">".$row["nick"]."</option>";
				}
				echo "</select>";
				echo " <input type=submit class='btn btn-danger btn-sm' name=send value='Předat'> <span class=danger><i class=\"fas fa-exclamation-triangle\"></i> Tuto akci již nelze vrátit!</span>";
			echo "</form>";
		echo "</div>";
	}	

	echo "<table class='table table-sm tablik'>";
	echo "<tr><th width=35></th><th width=250>".t("username")."</th><th width=200>".t("email")."</th><th width=150>".t("permission")."</th><th width=120>".t("IP address")."</th><th width=150>".t("action")."</th></tr>";
	$result = $resultPaginator;
	foreach ($result as $n => $row) {
		$permission = User::permission(User::get($row["id"])["permission"]);
		echo "<tr>";
			echo "<td align=center>";
				if($row["blokovan"] == 2)
					echo "<i class=\"fas fa-exclamation-triangle\" style='color:orange;margin-left: -5px;' title='Účet nebyl aktivován!'></i> ";
				else if($row["blokovan"] == 1)
					echo "<i class=\"fas fa-ban\" style='color:red;margin-left: -5px;' title='Účet byl zablokován!'></i> ";		
				else if($row["blokovan"] == 3)
				echo "<i class=\"fas fa-exclamation-circle\" style='color:red;margin-left: -5px;' title='Účet je neaktivní a blokován!'></i> ";
				else if($superuser == $row["id"])			
					echo "<i class=\"fas fa-user-tie\" style='color:#32A2D6;margin-left: -4px;' title='Účet s nejvyššímy právy'></i> ";				
			echo "</td>";
			echo "<td>".$row["nick"]."</td>";
			echo "<td><a href='mailto:".$row["email"]."'>".$row["email"]."</a></td>";
			if($row["prava"] == -20){ $permission["color"]="silver";$permission["name"]=t("system account"); }
			echo "<td><span style='color:".$permission["color"]."'>".$permission["name"]."</span></td>";
			echo "<td>".$row["ip"]."</td>";
			echo "<td>";
				echo "<a href='".Router::url()."adminv2/users/edit/".$row["id"]."/'><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a> ";
				if($row["id"] == $superuser)
					echo "<span title='Nelze smazat'><i class=\"fas fa-lock\"></i> ".t("delete")."</span>";
				else
					echo "<a href='".Router::url()."adminv2/users/delete/".$row["id"]."/'><i class=\"far fa-trash-alt\"></i> ".t("delete")."</a>";
			echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
