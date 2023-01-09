<?php
/**
 * Name: Administration
 * Description: This module provides you administration panel for your page
 * Version: 2.0
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: adminv2
 */

$this->hook_register("init.setting", "adminv2_init", -10);
$this->hook_register("page.adminv2.init.setting", "adminv2_init_setting", -10);
$this->hook_register("page.adminv2", "adminv2_page_draw", 0);
$this->hook_register("page.adminv2.init.template", "adminv2_init_template", 0);
$this->hook_register("init.permissions", "adminv2_perms", 0);
$this->hook_register("init.language.adminv2", "admin_language", 0);

function adminv2_init($t){
	$t->router->add("admin[/<adminModule=info>][/<action=show>][/<id=1>][/<state=none>]", "module=adminv2&admin_module=<adminModule>&action=<action>&who=null&id=<id>&state=<state>");
	$t->router->add("admin/<adminModule=info>/<action=show>-<who=null>[/<id=1>][/<state=none>]", "module=adminv2&admin_module=<adminModule>&who=<who>&action=<action>&id=<id>&state=<state>");
	$t->router->add("adminv2[/<adminModule=info>][/<action=show>][/<id=1>][/<state=none>]", "module=adminv2&admin_module=<adminModule>&action=<action>&who=null&id=<id>&state=<state>");
	$t->router->add("adminv2/<adminModule=info>/<action=show>-<who=null>[/<id=1>][/<state=none>]", "module=adminv2&admin_module=<adminModule>&who=<who>&action=<action>&id=<id>&state=<state>");
}

function admin_language($lang) {
	if(isset($_GET["setlangforadmin"])) {
		if($_GET["setlangforadmin"] == "delete") {
			Cookies::delete("language_admin");
		}else{
			Cookies::set("language_admin", $_COOKIE["language"], "+1 year");
		}		
		header("location:".$_SERVER['HTTP_REFERER']);
	}
	if(isset($_COOKIE["language_admin"])) 
		return $_COOKIE["language_admin"];

	return $lang;
}

function adminv2_perms(&$perms){
	$perms[] = "templates";
}

function adminv2_init_setting($t){
	$t->root->config->set("pre-title",t("Administration"));
	$t->root->config->set("style.menu.left", "hide");
	$t->root->config->set("style.menu.top", "hide");
	$t->root->config->set("style.header", "hide");
	$t->root->config->set("style", "default");

	$t->root->page->add_style("https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.theme.min.css");
	$t->root->page->add_script("https://api.mapy.cz/loader.js");
	$t->root->page->add_style(Router::url()."modules/adminv2/script/jquery.datetimepicker.css", false);
	$t->root->page->add_script(Router::url()."modules/adminv2/script/jquery.datetimepicker.full.min.js", false);
	$t->root->page->add_script(Router::url()."modules/adminv2/script.js", false);
	//$t->root->page->add_script("https://unpkg.com/gijgo@1.9.13/js/gijgo.min.js");
}

function adminv2_init_template($t){
	$t->root->page->add_style(Router::url()."modules/adminv2/style.css");
	$t->root->config->set("browser-tab-color", "#484848", true);	
}

function getIcons($t){
	$icons = array(
		"info" => array("module" => "info", "url" => "", "icon" => "fas fa-question-circle", "text" => "Information", "showMobile" => true),
		"menu" => array("module" => "menu", "url" => "menu/", "icon" => "fab fa-elementor", "text" => "Menu", "showMobile" => true),
		"users" => array("module" => "users", "url" => "users/", "icon" => "fas fa-users", "text" => "Users", "showMobile" => true),
		"content" => array("module" => "content", "url" => "content/", "icon" => "fas fa-archive", "text" => "Content", "showMobile" => false),
		"system" => array("module" => "system", "url" => "system/", "icon" => "fas fa-cogs", "text" => "System", "showMobile" => false),
		"style" => array("module" => "style", "url" => "style/", "icon" => "fas fa-paint-roller", "text" => "Style", "showMobile" => false),		
		"templates" => array("module" => "templates", "url" => "templates/", "icon" => "fas fa-layer-group", "text" => "Templates", "showMobile" => false),
	);

	$plugin = $t->root->module_manager->hook_call("admin.icons", null, $icons);
	$icons = $plugin["output"];

	$data = User::getData("admin-menu-pos");        
	if($data != ""){ if(count(explode(";", $data)) != count($icons)) { $data = ""; } }	
    if($data == ""){ $data = ""; foreach($icons as $key => $item){ $data.=$key.";"; } $data = substr($data, 0, strlen($data)-1); User::setData("admin-menu-pos", $data); }
	$lo = explode(";", $data);

	$iconSettings = Config::sload(User::getData("admin-menu-settings", Config::ssave(array(
		"article" => array("mobile" => true),
		"users" => array("mobile" => true),
		"info" => array("mobile" => true),
		"menu" => array("mobile" => true),
	))));
	
	$ret = array();
	foreach($lo as $key){
		$ret[$key] = $icons[$key];
		$ret[$key]["showMobile"] = $iconSettings[$key]["mobile"] == "1";
	}
	return $ret;
}

function adminv2_page_ajax($t, &$output){
	$user = User::current();

	$page = $t->router->_data["admin_module"][0];
	if($page == "search"){
		
		$data = [];

		if(!$user){
			echo json_encode(array("data" => []));
			return;
		}


		$text = Strings::undiacritic($_GET["text"]);		
		$arrayItems = getIcons($t);

		/*
		$data[] = array(
			"icon" => $item["icon"],
			"text" => $text,
			"url" => Router::url()."adminv2/".$item["url"]
		);*/

		$result = dibi::query('SELECT id,nick FROM :prefix:users ORDER BY id DESC');
		foreach ($result as $n => $row) {
			$jext = Strings::undiacritic($row["nick"]);

			$f = (strpos(strtolower($jext), $text) >= 0 && strpos(strtolower($jext), $text) !== false);
			if(!$f) continue;

			$data[] = array(
				"icon" => "fas fa-users",
				"text" => t("users") . " > " . $row["nick"],
				"url" => Router::url()."adminv2/users/edit/".$row["id"]
			);
		}

		$result = dibi::query('SELECT id,title FROM :prefix:article ORDER BY id DESC');
		foreach ($result as $n => $row) {
			$jext = Strings::undiacritic($row["title"]);

			$f = (strpos(strtolower($jext), $text) >= 0 && strpos(strtolower($jext), $text) !== false);
			if(!$f) continue;

			$data[] = array(
				"icon" => "fas fa-book",
				"text" => t("articles") ." > ". $row["title"],
				"url" => Router::url()."adminv2/article/edit/".$row["id"]
			);
		}

		$result = dibi::query('SELECT id,name FROM :prefix:form ORDER BY id DESC');
		foreach ($result as $n => $row) {
			$jext = Strings::undiacritic($row["name"]);

			$f = (strpos(strtolower($jext), $text) >= 0 && strpos(strtolower($jext), $text) !== false);
			if(!$f) continue;

			$data[] = array(
				"icon" => "fas fa-archive",
				"text" => t("form") ." > ". $row["name"],
				"url" => Router::url()."adminv2/content/form-edit/".$row["id"]
			);
		}

		foreach($arrayItems as $i => $item){
			$jext = Strings::undiacritic(t($item["text"]));
			$f = (strpos(strtolower($jext), $text) >= 0 && strpos(strtolower($jext), $text) !== false);
			if(!$f) continue;

			$data[] = array(
				"icon" => $item["icon"],
				"text" => t($item["text"]),
				"url" => Router::url()."adminv2/".$item["url"]
			);
		}
		
		echo json_encode(array("data" => $data));
	}
} 

function adminv2_page_draw($t, &$admin_output){	
	User::checkLogin();

	if($_GET["__type"] == "ajax" && $t->router->_data["admin_module"][0] == "search"){
		adminv2_page_ajax($t, $output);
		exit();
	}

	global $haveOp;
	$currentUser = User::current();	
	$perm = User::permission($currentUser["permission"])["permission"];

	if(isset($_GET["switchdebug"]) && User::permission($currentUser["permission"])["level"]==10000){
		Cookies::set("debug", !$_COOKIE["debug"], "+5 days");
		header("location:?");
	}

	$arrayItems = getIcons($t);

	$haveOp = [];
	$currentModule = null;

	foreach($arrayItems as $i => $item){
		if($i == "info") $url = "/"; else $url = "/".$i."/";
		/*if($i == "style") $isPerm = true; else */$isPerm = User::isPerm($i);
		$haveOp[$i] = array($isPerm, $item["url"], $i);
		if($t->router->_data["admin_module"][0]."/" == $item["url"]) {
			$currentModule = $item;
			$currentModule["key"] = $i;
		}
	}
/*
	$haveOp = array(
				"info" => array(User::isPerm("info"), "/", "info"),
				"article" => array(User::isPerm("article"), "/article/", "article"),
				"menu" => array(User::isPerm("menu"), "/menu/", "menu"),
				"users" => array(User::isPerm("users"), "/users/", "users"),
				"content" => array(User::isPerm("content"), "/content/", "content"),
				"system" => array(User::isPerm("system"), "/system/", "system"),
				"style" => array(true, "/style/", "style"),
				"eshop" => array(User::isPerm("eshop"), "/eshop/", "eshop"),
				"clients" => array(User::isPerm("clients"), "/clients/", "clients"),
				"templates" => array(User::isPerm("templates"), "/templates/", "templates"),
			);
*/
	$can = true;

	foreach ($haveOp as $n => $op) {
		if($op[2] == $t->router->_data["admin_module"][0] and $op[0] == 1) $can=false;
	}	

	if(isset($_GET["__type"]) and $_GET["__type"]=="ajax" and $perm["admin"] != 1){
		echo "<span class='sbox error'>".t("you are not authorized to enter into administration!")."</span>";
	}elseif(isset($_GET["__type"]) and $_GET["__type"]=="ajax" and $perm["admin"] == 1){
		if(file_exists(_ROOT_DIR."/modules/adminv2/modules/".$t->router->_data["admin_module"][0].".php"))
			include _ROOT_DIR."/modules/adminv2/modules/".$t->router->_data["admin_module"][0].".php";
		else if(file_exists(_ROOT_DIR."/modules/".$currentModule["key"]."/".$currentModule["file"]))
			include _ROOT_DIR."/modules/".$currentModule["key"]."/".$currentModule["file"];
	}else{		
		if($currentUser != false){
			echo "<div class='adminheader' id=adminheader><div class=content>";			
				echo "<div class='dotmenu right' id='adminMenu' onclick='event.stopPropagation();'><img src='".Router::url()."upload/avatars/".$currentUser["avatar"]."'></div>";
				echo "<div class='dotmenu notifications right' id='notifications'><i class=counter>2</i><i class=\"far fa-bell\"></i></div>";
				echo "<div class='title' ".($currentUser != false ? "onclick=\"openSearch();\"":"")."><div class=backico onclick=\"closeSearch();event.stopPropagation();\"><i class=\"fas fa-arrow-left\"></i></div>";
					echo "<input type=text id='searchglobal' class=input placeholder=\"Hledat v administraci\" onblur=\"closeSearch();\">";
					echo "<span class=text>".t("Administration")."</span>";
				echo "</div>";
				echo "</div>";		
				echo "<div class=searchcon><div class=searchbox id=searchresult></div></div>";
			echo "</div>";
		}

		if($perm["admin"] == 1){
			if($can){
				foreach ($haveOp as $n => $op) {
					if($op[0] == 1 && $op[2] != $t->router->_data["admin_module"][0]){
						//header("location:".$t->router->url_."adminv2".$op[1]);
						break;
					}elseif($op[0] == 1 && $op[2] == $t->router->_data["admin_module"][0]){
						break;
					}
				}
			}

			echo "<div class=admin>";
				echo "<ul class=topmenu style='  z-index: 0;position: relative;'>";
					foreach($arrayItems as $key => $item){
						if($haveOp[$key][0] == 1){
							$class = "hide-mobile";
							if($item["showMobile"]){
								$class = "mobile-width";
							}						
							if($t->router->_data["admin_module"][0] == $key){
								$class .= " select";
							}
							if(file_exists(_ROOT_DIR."/modules/adminv2/modules/".$item["module"].".php") || 
							   file_exists(_ROOT_DIR."/modules/".$key."/".$item["file"])){
								echo "<li class='".$class."'>";
									echo "<a href='".$t->router->url_."adminv2/".$item["url"]."'>";
										echo "<span class=icon><i class=\"".$item["icon"]."\"></i></span>";
										echo "<span class=text>".t($item["text"])."</span>";
									echo "</a>";
								echo "</li>";
							}
						}
					}
					echo "<li id='admin-menu-settings' class='mobile-width show-mobile'>";
						echo "<a href='#'><span class=icon><i class=\"fas fa-ellipsis-v\"></i></span><span class=text>".t("more")."</span></a>";
					echo "</li>";						
				echo "</ul>";				

				if(file_exists(_ROOT_DIR."/modules/adminv2/modules/".$t->router->_data["admin_module"][0].".php"))
					include _ROOT_DIR."/modules/adminv2/modules/".$t->router->_data["admin_module"][0].".php";
				else if(file_exists(_ROOT_DIR."/modules/".$currentModule["key"]."/".$currentModule["file"]))
					include _ROOT_DIR."/modules/".$currentModule["key"]."/".$currentModule["file"];
				else
					$t->root->page->error_box(t("this section not exists!<br>Try reinstall system...<br>Or administrator of this page delete file of this section!<br><span class=infoerro>Name of file: ".$t->router->_data["admin_module"][0].".php</span>"), "error");
			echo "</div>";
			echo "<div id=debug style='position:fixed;bottom:0px;left:0px;z-index:1000;background:white; width:200px;'></div>";
		}else{
			$t->root->page->login_box(true, true, t("Login"), $t->root->page->error_box(t("You are not authorized to enter into administration!"), "error", false, true));
		}
	}
	if($_GET["__type"] != "ajax"){
		?>
		<script>
			$(function(){
				var notificiationCenter = new NotificationCenter();

				<?php 
					$c = Bootstrap::$self->getContainer()->get("notification");
					foreach ($c->getAll() as $id => $notif) {
						$link = $notif["link"];
						if($link == null || $link == "") $link = "#";
						$text = str_replace("\\", "\\\\", $notif["text"]);
						$text = str_replace("\r\n", "<br/>", $text);
						$text = str_replace("'", "\\'", $text);
						echo "notificiationCenter.add('".$notif["icon"]."', '".$notif["creator"]."', '".$notif["title"]."', '".$text."', '".$link."', '".Strings::str_time($notif["created"], true, false, true)."');\n";
					}
					echo "$('#notifications .counter').html('".$c->getCount()."');\n";//$amount
				?>
				//notificiationCenter.add("fas fa-info", "Snowfox", "Hello world", "This is some text<br>second line<br>last line", "#", "23:18");
				//notificiationCenter.add("fas fa-info", "Snowfox", "Hello world 2", "This is some text<br>second line<br>last line", "#", "23:18");
				//notificiationCenter.add("fas fa-exclamation-triangle", "Snowfox", "Servise completed", "All finished", "#", "23:18");
			});		
			var actionButton;
			$(function(){			
				var contentmenu2 = new Menu($("#admin-menu-settings"), [
					<?php 
						foreach($arrayItems as $key => $item){
							if($haveOp[$key][0] == 1){
								if($item["showMobile"]){
									continue;
								}			
								echo "{text: \"".t($item["text"])."\", icon: \"".$item["icon"]."\", href: \"".$t->router->url_."adminv2/".$item["url"]."\"}, ";
							}
						}
					?>	
					{type: "line"},			
					{text: "Upravit položky v menu", icon: "fas fa-pencil-alt", href: "<?php echo Router::url()."adminv2/info/settings/"; ?>"}
				], {side: "left", closeButton: false, classes: ""});

				$("#admin-menu-settings").on("click", function(e){			
					contentmenu2.show();
					e.preventDefault();
				})

				contentmenu2.click(function(self, pos, title){	
					self.close();
				});

				var contentmenu = new Menu($("#adminMenu"), [
					{text: "<?php echo $currentUser["nick"]; ?>", href: "<?php echo Router::url()."adminv2/users/edit/".$currentUser["id"]; ?>/", subtext: "Upravit účet", "type": "image-big", icon: "<?php echo Router::url()."upload/avatars/".$currentUser["avatar"]; ?>"},
					{text: "Odhlásit", href: "?logout", icon: "fas fa-lock"}, 
					<?php if(User::permission($currentUser["permission"])["level"] == 10000) { ?>
						{text: "Debug mode", icon: "fas fa-bug", href: "?switchdebug", type: "check", ischecked: <?php echo ((isset($_COOKIE["debug"]) and $_COOKIE["debug"])==true?"true":"false"); ?>}, 
					<?php } ?>					
					{text: "", type: "line"}, 					
					<?php if(isset($_COOKIE["language_admin"])) { ?>					
						{text: "<?php echo t(_LANGUAGE); ?>", icon: "", type: "text"}, 
						{text: "<?php echo t("Cancel for Admin only"); ?>", icon: "fas fa-screwdriver", href: "?setlangforadmin=delete", type: "text"}, 
					<?php }else{ ?>	
						<?php 
						$languages = explode(",", Database::getConfig("languages"));
						foreach($languages as $lang){
							?>
								{text: "<?php echo t($lang); ?>", icon: "", href: "?setlang=<?php echo $lang; ?>", type: "check", ischecked: <?php echo (_LANGUAGE == $lang?"true":"false"); ?>}, 
							<?php
						}
						?>
						{text: "<?php echo t("Set for Admin only"); ?>", icon: "fas fa-screwdriver", href: "?setlangforadmin", type: "text"}, 
					<?php } ?>	
				], {side: "left", closeButton: false, classes: ""});

				$("#adminMenu").on("click", function(e){			
					contentmenu.show();
					e.preventDefault();
				})

				contentmenu.click(function(self, pos, title){	
					self.close();
				});

				$("#notifications").on("click", function(e){
					e.preventDefault();
					e.stopPropagation();
					NotificationCenter.open(this);
				});

				actionButton = new ActionButton();			
			});

			<?php 
			if($currentUser != false){
			?>
				function openSearch(){
					$("#adminheader").addClass('active');
					$('#searchglobal').focus();
					$("#searchresult").animate({
						height: $(window).outerHeight()-54
					}, 500, function() {
						setTimeout(() => {
							$("#searchresult").css({ height: $(window).outerHeight()-54 });
						}, 100);
					});
					/*$(window).on("resize", function(){
						$("#searchresult").css({ height: $(window).outerHeight()-54 });
					});*/
				}
				function closeSearch(){
					$("#searchresult").animate({
						height: 0
					}, 300, function() {
						$('#adminheader').removeClass('active')
					});			
				}
				var lasttext = "";
				$(function(){			
					$("#searchglobal").on("keyup", function(){
						lasttext = $(this).val();
						setTimeout(() => {
							doAjaxSearch();
						}, 200);
					});			
				});
				
				function doAjaxSearch(){
					if(lasttext != $("#searchglobal").val()) return;
					lasttext = "";
					$.getJSON("<?php echo Router::url(); ?>adminv2/search/?__type=ajax", {text: $("#searchglobal").val()}, function( data ) {
						$("#searchresult").html("");
						var ul = $("<ul></ul>");
						for(var key in data.data){
							var item = data.data[key];
							var li = $("<li></li>");
							li.data("url", item.url);
							li.on("click", function(e){
								e.stopPropagation();
								window.location.href = $(this).data("url");
							})
							var icon = $("<i class='"+item.icon+"'></i>");
							li.append(icon);
							var text = $("<span></span>");
							text.html(item.text);
							li.append(text);
							ul.append(li);
						}
						$("#searchresult").append(ul);
					});
				}
			<?php } ?>
		</script>
		<?php
	}
}
