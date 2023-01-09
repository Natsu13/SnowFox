<?php
/**
 * Name: Register
 * Description: Providing register page for your page
 * Version: 1.0
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: register
 */

$this->hook_register("init.setting", "register_init", -10);  //Register the new router
$this->hook_register("page.register.init.setting", "register_init_setting", -10);  //Set title of page
$this->hook_register("page.register", "register_page_draw", 0);  //  Draw a register page

$this->hook_register("templates.types", "register_templates", 0);

function register_init($t){
	$t->router->add("register", "module=register");	
	$t->router->add("login", "module=register&login");
	$t->router->add("logout", "module=register&logout");
	$t->router->add("activate", "module=register&activate");
}

function register_templates($t, &$output){
	$rand = Strings::random(10);
	$output[] = array(
		"code" => "USER_ACCOUNT_ACTIVATE",
		"description" => "User account activate email",
		"dummy" => array(
			"user_id" => 9999,
			"user_name" => "Dummy",
			"user_email" => "test@dummy.cz",
			"url" => Router::url()."activate/?key=".$rand."&user=9999",
			"key" => $rand,			
		)
	);
}

function register_init_setting($t){	
	if(isset($_GET["login"])) {
		$t->root->config->set("show-title", false);
		$t->root->config->set("pre-title", t("Login"));
	}
	elseif(isset($_GET["logout"])) {
		$t->root->config->set("show-title", false);
		$t->root->config->set("pre-title", t("Logout"));
	}
	elseif(isset($_GET["activate"]))
		$t->root->config->set("pre-title", t("Account activation"));
	else
		$t->root->config->set("pre-title", t("Registration"));

	if($t->root->config->getD("registration-leftmenu", 1) == 1)
		$t->root->config->set("style.menu.left", "hide");
}

function register_page_draw($t, &$output){
	if(isset($_GET["activate"])){
		$reactive = $t->root->config->get("registration-activation");
		if($reactive == 0)
			header("location:".Router::url()."register/");

		$show = true;
		$user = User::get($_GET["user"]);
		if($user["blokovan"] == 2){
			if(isset($_POST["activate"]) || isset($_GET["key"])){
				$key = (isset($_POST["code"])?$_POST["code"]:$_GET["key"]);
				if(($user["recovery"] == $_POST["code"] || $user["recovery"] == $_GET["key"]) && ($user["recovery"] != "")){					
					$show = false;
					$arr = array("recovery" => "", "blokovan" => 0);
					dibi::query('UPDATE :prefix:users SET ', $arr, 'WHERE `id`=%s', $_GET["user"]);
					$t->root->page->error_box(t("Your account has been activated, you can log in")." ( <a href='".Router::url()."login/'>".t("Log in")."</a> )", "ok");
					Utilities::addHistory("user", "account", "activation_activated", array(), "Acount activated ".$key, $user["id"]);
				}else{
					$t->root->page->error_box(t("The code entered is incorrect!"), "error");
					Utilities::addHistory("user", "account", "activation_wrong", array(), "Wrong activation code ".$key, $user["id"]);
				}
			}	
			if(isset($_GET["resend"])){
				$all = Utilities::getHistory("user", "account", "activation_resend", $user["id"], strtotime('-24 hours'));				
				if($all->count() <= 5){
					$key = Strings::random(8,Strings::$NUMBERS);
					$arr = array("recovery" => $key);
					dibi::query('UPDATE :prefix:users SET ', $arr, 'WHERE `id`=%s', $user["id"]);
	
					//user_account_activate
					$template = dibi::query("SELECT * FROM :prefix:templates WHERE code=%s", "USER_ACCOUNT_ACTIVATE")->fetch();
					if($template == null) {
						$t->root->utilities->sendemail($user["email"], t("Account activation"), t("Click to activate your account")."<br><a href='".Router::url()."activate/?key=".$key."&user=".$user["id"]."'>".t("Activate account")."</a>");				
					}else{
						$model = array(
							"key" => $key,
							"user_id" => $user["id"],
							"user_name" => $user["nick"],
							"user_email" => $user["email"],
							"url" => Router::url()."activate/?key=".$key."&user=".$user["id"]
						);

						ob_start();
						$t->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$template["hash"].".view", $model);
						$text = ob_get_contents();
						ob_end_clean();

						$t->root->utilities->sendemail($user["email"], $template["name"], $text, null, false, false);   
					}				
					
					Utilities::addHistory("user", "account", "activation_resend", array(), "Resend activation ".$key, $user["id"]);
					//$t->root->page->error_box("Kód pro aktivaci byl odeslán na vaší emailovou adresu", "ok");
					$t->root->page->error_box(t("An activation email has been sent to your email address"), "ok");
				}else{
					$t->root->page->error_box(t("You can only submit 5 reactivation requests per day"), "error");
				}
			}
			if($show){
				echo "<h1>".t("Account activation")." ".$user["email"]."</h1>";
				echo "<p>".t("Enter the code you received in the email")."</p>";
				echo "<form action=# method=post>";
					echo "<input type=text name=code class=form-control style='width:auto;display:inline-block;' value='".$_GET["key"]."'> <input type=submit name=activate class='btn btn-primary' value='".t("Activate account")."'>";
				echo "</form>";
				echo "<a href='".Router::url()."activate/?user=".$user["id"]."&resend'>".t("Resend activation email")."</a>";
			}
		}else{
			$t->root->page->error_box(t("Your account is already activated"), "error");
		}
	}
	else if(isset($_GET["login"]) || isset($_GET["logout"])){
		$plugin = $t->root->module_manager->hook_call("page.login", null, array("redirect" => null));
		if(isset($_GET["login"]) && $plugin["output"]["redirect"] != null) {
			header("location:".$plugin["output"]["redirect"]);
		}
		$t->root->page->login_box(true, true, t("Login"));
	}else{
		$user = User::current(true);
		if($user["id"] != null)
			header("location:".Router::url());

		if($t->root->config->get("registration-enable") == 1){
			$custom_form = $t->root->config->get("registration-form");
			$created = false;

			if(isset($_POST["register"])){
				if($_POST["captcha"] != 1){
					$t->root->page->error_box(t("Either you have javascript turned off or you're bot!"), "error");
				}else if($_POST["password"] != $_POST["password2"]){
					$t->root->page->error_box(t("The passwords entered are not the same!"), "error");
				}else if($t->root->config->get("registration-conditions") != "" and !isset($_POST["regaccept"])){
					$t->root->page->error_box(t("You must agree to the registration conditions!"), "error");
				}else{
					$create = User::create($_POST["jmeno"], $_POST["password"], $_POST["email"], $custom_form, $t, $out_id);
					if(!is_array($create)){
						if($t->root->config->get("registration-activation") == 1)
							$t->root->page->error_box(t("Your user account has been successfully created, your account still needs to be activated and an activation email will be sent to your email."), "ok");
						else
							$t->root->page->error_box(t("Your user account has been successfully created, you can log in."), "ok");
						$created = true;
					}else{
						$mess = t("Error creating account").": <ul>";
						for($i = 0;$i < count($create); $i++){
							$mess.= "<li>".$create[$i]."</li>";
						}
						$mess.= "</li>";
						$t->root->page->error_box($mess, "error");
					}
				}
			}
			
			if(!$created){
				if($custom_form != "" and $custom_form != "-1"){
					$r = "[form id=\"".$custom_form."\"]";
					
					global $errors_input,$customFormHook;
					$errors_input = null;
					$sf = Config::sload($t->root->config->get("registration-form-setting"));
					$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $custom_form, " ORDER BY position");
					$buttons = null;
					foreach ($result_ as $n => $row) {
						if(!isset($sf[$row["id"]])) $sf[$row["id"]]=-1;
						if($sf[$row["id"]] == "register"){ $regButton = "form_input_".$row["id"]; }
						elseif($sf[$row["id"]] == "jmeno"){ $buttons["jmeno"] = array("form_input_".$row["id"], $row["id"]); }
						elseif($sf[$row["id"]] == "email"){ $buttons["email"] = array("form_input_".$row["id"], $row["id"]); }
						elseif($sf[$row["id"]] == "password"){ $buttons["password"] = array("form_input_".$row["id"], $row["id"]); }
						elseif($sf[$row["id"]] == "password2"){ $buttons["password2"] = array("form_input_".$row["id"], $row["id"]); }
						elseif($sf[$row["id"]] == "regaccept"){ $buttons["regaccept"] = array("form_input_".$row["id"]."_0", $row["id"]); }
					}
					$customFormHook = true;
					if(isset($_POST[$regButton])){
						if($_POST[$buttons["password"][0]] != $_POST[$buttons["password2"][0]]){
							$t->root->page->error_box(t("The passwords entered are not the same!"), "error");
							$errors_input[$buttons["password"][1]] = t("Please enter the same passwords!");
						}else if(isset($buttons["regaccept"]) and  !isset($_POST[$buttons["regaccept"][0]])){
							$t->root->page->error_box(t("You must agree to the registration conditions!"), "error");
							$errors_input[$buttons["regaccept"][1]] = t("You must agree to the registration conditions!");
						}else{
							$create = User::create($_POST[$buttons["jmeno"][0]], $_POST[$buttons["password"][0]], $_POST[$buttons["email"][0]], $custom_form, $t, $out_id);
							if(!is_array($create)){
								$_POST[$buttons["jmeno"][0]] = "";
								$_POST[$buttons["email"][0]] = "";

								if($t->root->config->get("registration-activation") == 1)
									$t->root->page->error_box(t("Your user account has been successfully created, your account still needs to be activated and an activation email will be sent to your email."), "ok");
								else
									$t->root->page->error_box(t("Your user account has been successfully created, you can log in."), "ok");
							}else{
								$mess = t("Error creating account").": <ul>";
								for($i = 0;$i < count($create); $i++){
									$mess.= "<li>".$create[$i]."</li>";
								}
								$mess.= "</li>";
								$t->root->page->error_box($mess, "error");
							}
						}
					}
					
					$plugin = $t->root->module_manager->hook_call("page.bbcode", null, $r);				
					echo $plugin["output"];
				}else{
					$output = $t->root->page->template_parse(_ROOT_DIR . "/modules/register/register.templatte", $t);
				}
			}
		}else{
			$t->root->page->draw_error(t("Registration has been disabled"), t("Registration on this site is not allowed!"));
		}
	}
}