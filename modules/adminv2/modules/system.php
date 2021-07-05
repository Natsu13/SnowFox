<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];
$user = User::current();
$superuser = $t->root->config->getD("superuser", 1);

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
		echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/'>".t("Articles")."</a></li>";
		echo "<li ".($action == "emails"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/emails/'>".t("Emails")."</a></li>";
		echo "<li ".($action == "redirecting"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/redirecting/'>".t("Redirecting")."</a></li>";		
		echo "<li ".($action == "login"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/login/'>".t("Login")."</a></li>";	
		echo "<li ".($action == "cookies"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/cookies/'>".t("Cookies")."</a></li>";
		if($user["id"] == $superuser){
			echo "<li ".($action == "lock"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/lock/'>".t("Lock")."</a></li>";
			echo "<li ".($action == "cron"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/cron/'>".t("Cron")."</a></li>";
			echo "<li ".($action == "variables"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/variables/'>".t("Variables")."</a></li>";
		}
	echo "</ul>";
}

if($action == "cookies"){
	$cookieText = $t->root->config->getD("cookie-text", t("This website uses cookies. By continuing to browse this site, you agree to their use."));
	$cookieAccept = $t->root->config->getD("cookie-text-accept", t("I accept"));
	$cookieAcceptShow = $t->root->config->getD("cookie-accept-show", 1);
	$cookieMore = $t->root->config->getD("cookie-more", "https://policies.google.com/technologies/cookies");
	$cookieNoJs = $t->root->config->getD("cookie-no-js", "window['ga-disable-GA_MEASUREMENT_ID'] = true;");
	
	if(isset($_POST["update"])){
		$cookieText = $t->root->config->update("cookie-text", $_POST["cookie-text"]);
		$cookieAccept = $t->root->config->update("cookie-text-accept", $_POST["accept-text"]);
		$cookieAcceptShow = $t->root->config->update("cookie-accept-show", $_POST["cookie-accept-show"]);
		$cookieMore = $t->root->config->update("cookie-more", $_POST["cookie-more"]);
		$cookieNoJs = $t->root->config->update("cookie-no-js", $_POST["cookie-no-js"]);
		$t->root->page->error_box(t("updated"), "ok", true);
	}	

	echo "<div class=content>";
		echo "<div class=left-side>";
			echo "<form method=post>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\">";
						echo "<label><input type=radio name=cookie-accept-show value='1' ".Utilities::check($cookieAcceptShow == 1)."> ".t("Show the bar with the consent of cookies, and must be accepted")."</label><br/>";
						echo "<label><input type=radio name=cookie-accept-show value='2' ".Utilities::check($cookieAcceptShow == 2)."> ".t("Display a panel with information about cookies")."</label><br/>";
						echo "<label><input type=radio name=cookie-accept-show value='0' ".Utilities::check($cookieAcceptShow == 0)."> ".t("Don't show the cookie bar")."</label>";
					echo "</div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Text for cookie")."</label>";
					echo "<div class=\"col-sm-9\"><textarea name=cookie-text>".$cookieText."</textarea></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Accept button text")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=accept-text value='".$cookieAccept."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("More button link")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=cookie-more value='".$cookieMore."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Javascript code that will be executed without consent")."</label>";
					echo "<div class=\"col-sm-9\"><textarea name=cookie-no-js rows=3>".$cookieNoJs."</textarea></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("The name of cookies what will be created after accept")."</label>";
					echo "<div class=\"col-sm-9 form-static\"><span class=code>cookieAccept</span></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></div>";
				echo "</div>";				
			echo "</form>";
		echo "</div>";
	echo "</div>";	
}
elseif($action == "lock" and $user["id"] == $superuser){
	if(isset($_POST["update"])){
		$t->root->config->update("lock-enable", (isset($_POST["enable-lock"])?1:0));
		$t->root->config->update("lock-password", $_POST["lock-password"]);
		$t->root->page->error_box(t("updated"), "ok", true);
	}
	$enableLock = $t->root->config->getD("lock-enable", "0");
	$lockPassword = $t->root->config->getD("lock-password", "");

	$maintenance = false;
	if(file_exists(_ROOT_DIR."/maintenance.html")) {$maintenance = true;}	

	if(isset($_GET["maintenance"])) {
		$t->root->page->maintenanceMode(!$maintenance, "manualy by user");		
		header("location:?");
	}		

	echo "<h1>Lock</h1>";
	echo "<div class=content>";
		echo "<div class=left-side>";
			echo "<a class='button red' href='".Router::url()."adminv2/system/lock/?maintenance'>".t($maintenance?"Turn off maintenance mode":"Turn on maintenance mode")."</a>";
		
			echo "<form method=post>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\">";
						echo "<label><input type=checkbox name=enable-lock value='1' ".Utilities::check($enableLock == 1)."> ".t("Enable page lock")."</label>";
					echo "</div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Password")."</label>";
					echo "<div class=\"col-sm-9\">";
						echo "<input type=text class=form-controll name=lock-password value='".$lockPassword."'>";
					echo "</div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></div>";
				echo "</div>";
			echo "</form>";
		echo "</div>";
	echo "</div>";	
}
elseif($action == "cron" and $user["id"] == $superuser){

	if($_GET["__type"] == "ajax"){
        if($who == "show"){
			$id = $_GET["id"];
			$data = [];
			$result = dibi::query("SELECT * FROM :prefix:history WHERE parent=%s", "cron_log_".$_GET["id"], " ORDER BY date DESC LIMIT 20");
			foreach($result as $n => $cron) {
				$cronData = Config::sload($cron["data"]);
				$data[] = array(
					"id" => $cron["id"],
					"date" => Strings::str_time($cron["date"]),
					"data" => $cronData,
					"text" => $cron["text"],
					"type" => $cron["type"]
				);
			}

			echo json_encode($data);
        }
        exit();
    }

	if(isset($_POST["update"])){
		$t->root->config->update("cron-enable", (isset($_POST["enable-cron"])?1:0));
		$t->root->page->error_box(t("updated"), "ok", true);
	}
	$enableCron = $t->root->config->getD("cron-enable", "0");

	$lastCron = Utilities::getHistory("cron", "log", "start");

	$plugin = $t->root->module_manager->hook_call("cron.register", null, array(), false, true, true);            
	$jobs = $plugin["output"];

	if(count($lastCron) > 0) {
		$crons = $lastCron->fetchAll();
		$lastCron = $crons[0];
		$prevCron = $crons[1];
						
		$diff = Strings::time_difference($lastCron["date"], $prevCron["date"]);
		$minutes = $diff["minute"];
		$hours = $diff["hour"];

		if($hours > 0) {
			$timeTo = $hours." ".t("hours");
		}else{
			$timeTo = $minutes." ".t("minutes");
		}
	}

	echo "<div class=row>";
	echo "<div class='col-md-8 col-12'>";	
		echo "<div class=row>";
			echo "<div class='col-12 col-md-6 margin-card'>";		
				echo "<div class=card data-expandable='false'>";
					echo "<div class=title>".t("List of cron jobs")."</div>";
					echo "<div class=content id=cont1 style='max-height: 500px !important;overflow: auto;'>";
						echo "<ul class=list>";
							foreach($jobs as $job) {
								echo "<li onclick=\"loadLog('".$job["call"]."');return false;\">";
									echo "<div class=badge style='float: right;'>".t($job["every"])."</div>";
									echo "<div class=title>".$job["name"]."</div>";
									echo "<div class=desc>".$job["description"]."</div>";
								echo "</li>";
							}
						echo "</ul>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
			echo "<div class='col-12 col-md-6 margin-card'>";		
				echo "<div class=card data-expandable='false'>";
					echo "<div class=title>".t("Log")."</div>";
					echo "<div class=content id=cont2 style='max-height: 500px !important;overflow: auto;'>";
						echo "<ul class=list id='log'>";
							echo "<li><div class=desc>".t("Clic on cron job to see the logs")."</div></li>";
						echo "</ul>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
		echo "</div>";
		?>
		<script>
		function checkSize(){			
			setTimeout(function(){
				var c1 = $("#cont1");
				var c2 = $("#cont2");

				if(!isMobile()){			
					if(c2.outerHeight() > c1.outerHeight()) {
						c1.animate({"height": c2.outerHeight()}, 300);
					}
				}else{
					c1.css("height", "initial");
				}
				checkSize();
			}, 1000);
		}
		$(function(){
			checkSize();
		});
		function loadLog(parent) {
			$.getJSON('<?php echo Router::url()."adminv2/system/cron-show/"; ?>'+parent+'/?__type=ajax', function(data){
				console.log(data);

				$("#log").html("");
				if(data.length == 0) {
					$('#log').html("<li><div class=desc><?php echo t("No logs found"); ?></div></li>"); 
				}else{
					for(var key in data) {
						var l = data[key];

						var li = $("<li></li>");
						li.on("click", function(){ showDialogLog($(this).data("logs")); });
						li.data("logs", l.data);
						var time = $("<div class='badge' style='float: right;'></div>");
						time.html(l.date);
						li.append(time);
						var name = $("<div class=title></div>");
						name.html(l.text);
						li.append(name);
						$("#log").append(li);
					}
				}
			});
		}
		var dialogLog = null;
		$(function(){   
			dialogLog = new Dialog(800);
			dialogLog.setTitle("Logs");
			dialogLog.setButtons(Dialog.CLOSE);     
			var btn = dialogLog.getButtons();
			$(btn[0]).click(function(){ dialogLog.Close(); }); 
		});
		function showDialogLog(logs){
			dialogLog.html.dialogHtml.html("");

			if(typeof logs.output != "undefined") {
				var div = $("<ul class=list></div>");

				for(var key in logs.output) {
					var log = logs.output[key];
					var li = $("<li></li>");
					var logli = $("<div class=lilog></div>");
					
					var time = $("<div class=time></div>");
					var date = new Date(log.time * 1000);
					time.html(date.toLocaleTimeString());
					logli.append(time);

					var text = $("<div class=text></div>");
					text.html(log.text);
					logli.append(text);

					li.append(logli);
					div.append(li);
				}
				
				dialogLog.html.dialogHtml.append(div);
			}else{
				dialogLog.html.dialogHtml.append(logs);
			}

			dialogLog.Show();
		}
		</script>
		<?php
		echo "</div>";
		echo "<div class='col-md-4 col-12'>";		
			echo "<h2>".t("cron settings")."</h2>";
			echo "<form method=post><table class=tabfor style='width:700px;'>";
				echo "<tr><td></td><td><label><input type=checkbox name=enable-cron value='1' ".Utilities::check($enableCron)."> ".t("enable cron")."</label></td></tr>";
				echo "<tr><td></td><td><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></td></tr>";
			echo "</table>";
			echo "</form>";

			if(count($lastCron) > 0) {						
				echo "<br>";
				$t->root->page->error_box(t("Last cron run").": ".Strings::str_time($lastCron["date"]).". Rozmezí: ".$timeTo, "ok", false);
			}

			$cron = Bootstrap::getContainer()->get('cron');
			$hash = $cron->getHash();
			echo "<div style='margin-top:10px;'>".t("Cron address for run")."</div>";
			echo "<div><a href='".Router::url()."cron/".$hash."' target=_blank>/cron/".$hash."</a></div>";
			echo "<div class=desc>".t("if you change superuser the url will be changed too")."</div>";
		echo "</div>";
	echo "</div>";
}
elseif($action == "login"){
	if(isset($_POST["update"])){
		$t->root->config->update("ttl", $_POST["ttl"]);
		$t->root->config->update("tts", $_POST["tts"]);
		$t->root->config->update("onlyttl", (isset($_POST["onlyttl"])?1:0));
		$t->root->page->error_box(t("updated"), "ok", true);
	}

	$ttl = $t->root->config->getD("ttl", "+24 hour");
	$tts = $t->root->config->getD("tts", "+8 hour");
	$onlyttl = $t->root->config->getD("onlyttl", 0);
	echo "<div class=content>";
		echo "<div class=left-side>";
			echo "<form method=post>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Time of long login")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=ttl value='".$ttl."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Time of short login")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=tts value='".$tts."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><label><input type=checkbox name=onlyttl value='1' ".Utilities::check($onlyttl)."> ".t("only ttl")."</label></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></div>";
				echo "</div>";
			echo "</form>";
		echo "</div>";
	echo "</div>";
}
elseif($action == "variables" and $user["id"] == $superuser){	
	/*echo "<div class=hide-mobile>";
		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=100>".t("name")."</th><th width=200>".t("value")."</th></tr>";
		$result = dibi::query('SELECT * FROM :prefix:settings');
		foreach ($result as $n => $row) {		
			echo "<tr>";
				echo "<td valign=top><b>".$row["name"]."</b></td>";
				echo "<td>".str_replace("\n", "<br>", $row["value"])."</td>";
			echo "</tr>";
		}
		echo "</table>";
	echo "</div>";*/
	echo "<div>";
		echo "<div class='card small'><div class=content><ul class=list>";
		$result = dibi::query('SELECT * FROM :prefix:settings');
		foreach ($result as $n => $row) {
			echo "<li>";
				echo "<div class=option>".str_replace("\n", "<br>", $row["value"])."</div>";				
				echo $row["name"];
				echo "<div class=clear></div>";
			echo "</li>";
		}
		echo "</ul></div></div>";
	echo "</div>";
}
elseif($action == "redirecting"){
	if($who == "edit"){ 
		echo "<h1>".t("Editing redirect")."</h1>";		

		$result = dibi::query("SELECT * FROM :prefix:redirecting WHERE id=%i", $t->router->_data["id"][0])->fetch();
		if($result == NULL){
			$t->root->page->draw_error(t("called item does not exist!"));
		}else{
			if(isset($_POST["update"])){
				$pole = array(
							"name" => $_POST["name"],
							"_from" => $_POST["from"],
							"_to" => $_POST["to"],
							"minop" => $_POST["minop"],
							"active" => isset($_POST["active"]),
							"redirect" => isset($_POST["redirect"]),
						);
		
				dibi::query('UPDATE :prefix:redirecting SET ', $pole, "WHERE `id`=%s", $t->router->_data["id"][0]);
				header("location: ".$t->router->url_."adminv2/system/redirecting-edit/".$t->router->_data["id"][0]."/ok");
			}

			if($t->router->_data["state"][0] == "ok"){
				$t->root->page->error_box(t("redirecting was updated"), "ok", true);
			}
		
			echo "<form method=post><table class=tabfor style='width:700px;'>";
				echo "<tr><td width=120><label>".t("Name")."</label></td><td width=330><input type=text name=name value='".$result["name"]."'></td></tr>";
				echo "<tr><td><label>".t("From")."</label></td><td><input type=text name=from value='".$result["_from"]."'></td></tr>";
				echo "<tr><td></td><td><pre>".t("For example").": ".htmlentities("<module>[/<id>][/<page=1>]")."</pre></td></tr>";
				echo "<tr><td><label>".t("To")."</label></td><td><input type=text name=to value='".$result["_to"]."'></td></tr>";
				echo "<tr><td></td><td><pre>".t("For example").": ".htmlentities("module=<module>&id=<id>&page=<page>")."</pre></td></tr>";
				echo "<tr><td><label>".t("Minimal permission").": </label></td><td>";
					echo "<select id=permission name=minop style='width:300px;'>";
					$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
					foreach ($resul_ as $n => $row) {
						echo "<option value='".$row["id"]."' ".($row["id"] == $result["minop"]?"selected":"").">".$row["name"]." (Level: ".$row["level"].")</option>";
					}
					echo "<option value=0  ".(0 == $result["minop"]?"selected":"").">Neregistrovaný uživatel</option></select>";
				echo "</td></tr>";
				echo "<tr><td></td><td><label><input type=checkbox name=redirect value='1' ".Utilities::check($result["redirect"])."> ".t("Redirect")."</label></td></tr>";
				echo "<tr><td></td><td><label><input type=checkbox name=active value='1' ".Utilities::check($result["active"])."> ".t("Active")."</label></td></tr>";
				echo "<tr><td></td><td><input type=submit class='blue button' name=update value='".t("edit")."'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}elseif($who == "new"){
		$data = array(
			"name" 		=> t("New redirecting"),
			"active"	=> 0
		);
		$result = dibi::query('INSERT INTO :prefix:redirecting', $data);

		header("location: ".$t->router->url_."adminv2/system/redirecting-edit/".dibi::InsertId());
	}elseif($who == "delete"){
		$result = dibi::query('DELETE FROM :prefix:redirecting WHERE id=%i', $_GET["id"]);
		header("location: ".$t->router->url_."adminv2/system/redirecting/");
	}else{
		echo "<div class=bottommenu><a href='".$t->router->url_."adminv2/system/redirecting-new/'>".t("new redirecting")."</a></div>";
		echo "<h1>".t("Redirecting settings")."</h1>";
		echo "<span class=desc>".t("These redirects are preceded by other modular and system redirects, in addition to ajax")."</span>";

		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=10>ID</th><th width=250>".t("name")."</th><th width=200>".t("from")."</th><th width=200>".t("to")."</th><th width=120>".t("action")."</th></tr>";
		$result = dibi::query('SELECT * FROM :prefix:redirecting ORDER BY id DESC');
		foreach ($result as $n => $row) {
			echo "<tr>";
				echo "<td>".$row["id"]."</td>";
				echo "<td>".$row["name"]."".($row["active"] == 0?" <span class=desc>".t("not active")."</span>":"")."</td>";
				echo "<td>".$row["_from"]."</td>";
				echo "<td>".$row["_to"]."</td>";				
				echo "<td valign=top>";
					echo "<a href='".$t->router->url_."adminv2/system/redirecting-edit/".$row["id"]."/' class=xbutton><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a>";
					echo " <a href='".Router::url()."adminv2/system/redirecting-delete/".$row["id"]."/'><i class=\"far fa-trash-alt\"></i> ".t("delete")."</a>";
				echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
elseif($action == "show"){
	if(isset($_POST["update"])){
		$pole = array(
					"comment-max-url" => $_POST["maxurl"],
					"comment-timeout" => $_POST["timeout"]
				);

		foreach($pole as $key => $value){
			dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
			$t->root->config->update($key, $value);
		}
		$t->root->page->error_box(t("the changes have been saved."), "ok", true);
	}

	if($t->root->config->get("comment-max-url") == null) $t->root->config->set("comment-max-url", "2");
	if($t->root->config->get("comment-timeout") == null) $t->root->config->set("comment-timeout", "20");

	echo "<h1>".t("Article settings")."</h1>";
	echo "<form method=post><table class=tabfor style='width:700px;'>";
	echo "<tr><td width=280><label>".t("Maximum number of links")."</label></td><td width=330><input type=text name=maxurl value='".$t->root->config->get("comment-max-url")."'></td><td width=20></td></tr>";
	echo "<tr><td></td><td><span class=desc>".t("in one comment post")."</span></td></tr>";
	echo "<tr><td><label>".t("Timeout in posting comments")."</label></td><td width=430><input type=text data-postfix='".t("second")."' class=price placeholder=0 name=timeout value='".$t->root->config->get("comment-timeout")."'></td></tr>";
	echo "<tr><td></td><td><span class=desc>".t("only for not loget users")."</span></td></tr>";
	echo "<tr><td></td><td><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></td></tr>";
	echo "</table>";
	echo "</form>";
}else if($action == "emails"){
	if($who == "text"){
		$result = dibi::query("SELECT * FROM :prefix:emails WHERE id=%i", $t->router->_data["id"][0])->fetch();
		echo $result["message"];
	}
	else if($who == "show"){
		$result = dibi::query("SELECT * FROM :prefix:emails WHERE id=%i", $t->router->_data["id"][0])->fetch();
		echo "<h1>".$result["subject"]."</h1>";
		$user = User::get($result["user"], true);
		echo "<b>Odesláno</b> ".Strings::str_time($result["time"])."<br>";
		echo "<b>Přihlášený uživatel</b> ".$user["login"];
		echo "<div style='padding:4px;'></div>";
		echo "<a href=# onclick=\"$('#ifp').animate({width: '100%'}, 500, function(){});return false;\">Web</a> | <a href=# onclick=\"$('#ifp').animate({width: '450px'}, 500, function(){});return false;\">Mobile</a>";
		echo "<div style='background: white;padding: 13px;border: 1px solid silver;'>";		
			echo "<iframe style='width:100%;height:550px;border: 0px;margin:0px auto;display:block;' id=ifp src='".Router::url()."/adminv2/system/emails-text/".$result["id"]."/?__type=ajax'></iframe>";
		echo "</div>";
	}else{
		echo "<h1>".t("Emails settings")."</h1>";

		if(isset($_POST["change"])){
			$pole = array(
						"email-enable" => (isset($_POST["enable_email"]) and $_POST["enable_email"] == 1?1:0),
						"email-webmaster" => $_POST["email"]
					);

			foreach($pole as $key => $value){
				dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
				$t->root->config->update($key, $value);
			}
			$t->root->page->error_box(t("the changes have been saved."), "ok", true);
		}

		if($t->root->config->get("email-webmaster") == null) $t->root->config->set("email-webmaster", User::get(1)["email"]);
		$em = $t->root->config->get("email-webmaster");

		echo "<form action=# method=post><input type=checkbox value=1 name=enable_email ".Utilities::check($t->root->config->getD("email-enable", "1"))."> Povolit odesílání emailu (Jinak se bude ukládat pouze kopie zde)";
		echo "<br>Hlavní email: <input type=text name=email value='".$em."'> <input type=submit class='btn btn-primary' name=change value='Upravit'></form>";

		$paginator = new Paginator(15, Router::url()."adminv2/system/emails/?page=(:page)");
		$result = $paginator->query('SELECT * FROM :prefix:emails ORDER BY time DESC');

		echo $paginator->getPaginator();

		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=30>ID</th><th width=150>".t("from")."</th><th width=150>".t("to")."</th><th width=400>".t("subject")."</th><th width=120>".t("action")."</th></tr>";
		//$result = dibi::query('SELECT * FROM :prefix:emails ORDER BY time DESC');
		foreach ($result as $n => $row) {
			echo "<tr>";
				echo "<td>".$row["id"]."</td>";
				echo "<td>".$row["_from"]."</td>";
				echo "<td>".$row["_to"]."</td>";
				echo "<td>".$row["subject"];
					$user = User::get($row["user"], true);
					echo "<div style='font-size:12px;'>".$user["nick"]." ( ".Strings::str_time($row["time"])." )</div>";
				echo "</td>";
				echo "<td valign=top>";
					echo "<a href='".$t->router->url_."adminv2/system/emails-show/".$row["id"]."/'>".t("show")."</a>";
				echo "</td>";
			echo "</tr>";
		}
		echo "</table>";

		echo $paginator->getPaginator();
	}
}
