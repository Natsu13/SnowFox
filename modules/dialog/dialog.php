<?php
/**
 * Name: Dialog ajax
 * Description: Providing basic ajax dialogs
 * Version: 1.0
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: dialog
 */

$this->hook_register("init.setting", "dialog_init", -10);
$this->hook_register("page.dialog", "dialog_page_draw", 0);

function dialog_init($t){
	$t->router->add("recovery/password", "module=dialog&id=1&changepass");
}

function dialog_page_draw($t, &$output){
	if($_GET["id"] == 1){
		$error = 0;
		$jak = 1;
		if(isset($_GET["user"])){
			if(!User::isExists($_GET["user"])){ $error = 1; }
		}else{ $_GET["user"] = "";$_GET["key"] = ""; }

		if($error == 0 and $_GET["user"] != "" and isset($_GET["changepass"]) and $_GET["key"]!=""){
			echo "<div class=spacetop></div><div class=recovery><div style='width: 390px;border: 1px solid silver;border-bottom: 0px;padding: 15px;background-color: white;margin: 20px;margin-bottom: 0px;' class='recovery dialog'><h2>".t("Choose a new password")."</h2>";
			$show = true;
			$user = User::get($_GET["user"]);
			if($user["recovery"] != $_GET["key"]){header("location:".$t->root->router->url_);}
			if(isset($_POST["heslon"])){
				if($_POST["heslon"] != $_POST["heslo2"]){ $t->root->page->error_box(t("The passwords entered are not the same!"),"error"); }
				elseif($user["recovery"] != $_GET["key"]){ $t->root->page->error_box(t("Wrong verification key!"),"error"); }
				elseif(strlen($_POST["heslon"]) < 6 or $_POST["heslon"] == ""){ $t->root->page->error_box(t("Password is too short!"),"error"); }
				else{
					$id = $_GET["user"];
					$arr = array("heslo" => sha1($_POST["heslon"]), "recovery" => "", "passlastchange" => time());
					dibi::query('UPDATE :prefix:users SET ', $arr, 'WHERE `id`=%s', $id, " OR `jmeno`=%s", $id, " OR email = %s", $id);
					$t->root->page->error_box(t("Password changed successfully, you can log in!"),"green");
					$show = false;
				}
				echo "<br>";
			}
			if($show){
				echo "<span class='description black'>".t("Change user password")." <b>".$user["nick"]."</b></span><br>";
				echo "<span class='description black'>".t("Your password was last changed")." <b>".($user["passlastchange"]==""?t("Never"):Strings::str_time($user["passlastchange"]))."</b></span>";
				echo "<form action=# method=post><table style='margin:20px 0px;margin-left:20px;'>";
				echo "<tr><td>".t("New password")."</td><td><input type=password name=heslon id=frm_in_pass1></td><td><button class=buttoninput onClick=\"recoveryPass(2);return false;\">?</button></td></tr>";
				echo "<tr><td>".t("Password again")."</td><td><input type=password name=heslo2 id=frm_in_pass2></td></tr>";
				echo "</table>";
			}
			echo "</div><div class='dialog-footer'><div class=dialog-footer-button>";
			if($show)
				echo "<input type=submit name=change class='btn btn-primary button' value='".t("Change")."'> ";
			echo "<a class='btn btn-secondary button' href='".$t->root->router->url."'>Zrušit</a></div><div class=clear></div></div></form></div>";
		}elseif($error == 0 and $_GET["user"] != ""){
			if(isset($_GET["key"])){
				if($_GET["key"] != ""){
					$user = User::get($_GET["user"]);
					if($user["recovery"] != $_GET["key"])
						$error = 1;
					if(trim($_GET["key"]) == "")
						$error = 2;
				}

				if($_GET["key"]!="" and $error == 0){
					echo "[Show Change Pass]";
				}else{
					echo "<div class='cnt'>";
						echo "<input type=hidden id='frm_in_username' value='".$_GET["user"]."'>";
						echo "<span class=description style=''>".t("An 8-digit key has been sent to your email address, please enter it below.")."</span><br><br>";
						echo "<input type=number style='font-size:25px;background-color:transparent;width:200px;border:0px;".($error!=0?"border-bottom:1px solid red;":"border-bottom:1px solid black;").";' id='frm_in_key' placeholder='XXXXXXXX' value='".$_GET["key"]."'>";
						if($error == 1){ echo "<span class=error style='font-size: 16px;padding: 7px 3px;position: relative;top: -3px;left: -3px;'>".t("The key does not match the sent key!")."</span>"; }
						elseif($error == 2){ echo "<span class=error style='font-size: 16px;padding: 7px 3px;position: relative;top: -3px;left: -3px;'>".t("Key is required!")."</span>"; }
					echo "</div>";
				}
			}
			elseif(isset($_GET["recovery"])){
				if($_GET["recovery"]=="2"){
					$rec = User::setRecovery($_GET["user"]);
					if(!$rec) { $error = 1; }
					elseif($rec == -5){ $error = 2; }
					else{
						$user = User::get($_GET["user"]);
						$t->root->utilities->sendemail($user["email"], t("Password recovery"), "".t("The password recovery code is").": <b>".$rec."</b><br><a href='".Router::url()."recovery/password/?key=".$rec."&user=".$user["login"]."'>".t("Password recovery")."</a>");
					}
				}

				if($_GET["recovery"]=="2" and ($error == 0 or $error == 2)){
					echo "[Show key]";
				}else{
					$user = User::get($_GET["user"]);
					echo "<div class='cnt'>";
					echo "<input type=hidden id='frm_in_username' value='".$_GET["user"]."'>";
					echo "<table class=tabfor>";
						echo "<tr><td width=50 style='width:50px;'><img src='".$t->root->router->url."upload/avatars/".$user["avatar"]."' width=48 height=48></td><td><b>".$user["nick"]."</b><br>Uživatelský účet</td></tr>";
						$m=$user["email"];
						$s=explode("@",$m);
						$d="";for($i=0;$i<strlen($s[0])-3;$i++){$d.="*";}
						$m=substr($s[0],0,1).$d.substr($s[0],strlen($s[0])-2,2)."@".$s[1];
						echo "<tr><td colspan=2 class=description style='margin-top: 9px;'>".t("To your email address")." <b class=description>".$m."</b> ".t("a confirmation code will be sent.")."</td></tr>";
					echo "</table>";
					if($error == 1){ echo "<span class=error>".t("An error occurred while requesting account renewal!")."</span>"; }
					elseif($error == 2){ echo "<span class=error>".t("The recovery code has already been set for this account!")."</span>"; }
					echo "</div>";
				}
			}else echo "[Select Recovery]";
		}else{
			echo "<div class='cnt'>";
			if($jak == 1){
				echo "<table class=tabfor>";
					echo "<tr><td>".t("Enter your username or email")."</td></tr>";
					echo "<tr><td><input type=text name=jmeno id=frm_in_username placeholder='' value='".$_GET["user"]."'></td></tr>";
				echo "</table>";
				if($error == 1){ echo "<span class=error>".t("This user account does not exist!")."</span>"; }
				echo "<input type=hidden id=hownext value='".$jak."'>";
			}
			echo "</div>";
		}
	}
	elseif($_GET["id"] == 2){
		echo "<div class='cnt'>";
		echo "".t("Secure password")." <b>".t("should")."</b> ".t("be longer than 6 characters.")."<br><b>".t("Should not")."</b> ".t("contain a word easily found in the dictionary or your name or address")."<br><b>".t("It should be")."</b> ".t("contain at least one number.");
		echo "<br><br><span style=description>".t("Here you can copy the generated password")."<br><input type=text readonly value='".Strings::random(8)."'></span></div>";
	}
	elseif($_GET["id"] == "setHomePage"){
		if(isset($_GET["set"])){
			$arr = array("value" => $_GET["set"]);
			dibi::query('UPDATE :prefix:settings SET ', $arr, ' WHERE `name`=%s', "mainpage");
			echo "<div class='box ok'>Hlavní stránka byla změněna!</div>";
		}else{
			$isa = Database::getConfig("default-article");
			echo "<div class='cnt'>";
			echo "<p>Vyberte hlavní článek:</p><select id='shcl' style='width:480px;'>";
				echo "<option value=-5>Nejnovější články</option>";
				echo "<option value='' disabled>--- Články ---</option>";
				$result = dibi::query('SELECT * FROM :prefix:article');
				foreach ($result as $n => $row) {
					echo "<option value='".$row["id"]."' ".(($row["alias"]=="" or $row["state"] == 4)?"disabled":"")." ".(Database::getConfig("mainpage")==$row["id"]?"selected":"").">".$row["title"]." (".$row["alias"].")</option>";
				}
			echo "</select>";
			echo "</div>";
		}
	}
	elseif($_GET["id"] == "passkontrolhes"){
		$user = User::current();
		if($user["password"] == sha1($_GET["pass"]))
			echo sha1($_GET["pass"]);
		else
			echo "[PASS FAILED]";
	}
	elseif($_GET["id"] == "datapicker"){
		//echo $_GET["inputid"]."<br>".$_GET["formid"];
		echo "<table class=tablik style='margin:0px;table-layout: inherit;width: 800px;'>";
		if($_GET["jem"] != "false"){
			$classic = array(
							array("actual_date", "Actual date", Date(Utilities::getTimeFormat(), Time())),
							array("ip", "IP address of user", Utilities::ip()),
							array("userid", "ID of current user", User::current()["id"]),
							array("nick", "Nick of user", User::current()["nick"]),
							array("email", "Email of user", User::current()["email"]),
							array("perm", "Permission of user", User::permission(User::current()["permission"])["name"])
						);
			echo "<tr><th colspan=3>Default variables</th></tr>";
			foreach ($classic as $ve) {
				echo "<tr style='cursor:pointer;border-bottom:1px solid #c1c1c1;' onClick=\"$('#".$_GET["inputid"]."').val('{default:".$ve[0]."}');$('#".$_GET["inputid"]."').change();current_showed_data_dialog.Close();\"><td width=210>default:".$ve[0]."</td><td width=200>".$ve[1]."</td><td>".$ve[2]."</td></tr>";
			}
		}
		echo "<tr><th colspan=3>Form variables</th></tr>";
		$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $_GET["formid"], " ORDER BY position");
		foreach ($result_ as $n => $row) {
			echo "<tr style='cursor:pointer;border-bottom:1px solid #c1c1c1;' onClick=\"$('#".$_GET["inputid"]."').val('{field:".$row["id"]."}');$('#".$_GET["inputid"]."').change();current_showed_data_dialog.Close();\"><td width=210>field:".$row["id"]."</td><td width=200>".$row["name"]."</td><td>".$row["type"]."</td></tr>";
		}
		echo "</table>";
	}
}
