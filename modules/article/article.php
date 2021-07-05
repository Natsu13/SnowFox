<?php
/**
 * Name: Article
 * Description: Showing article on page
 * Version: 1.2
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: article
 */
$this->hook_register("page.article.init.setting", "databaze_init_setting", -10);
$this->hook_register("page.article", "article_page_draw", 0);
$this->hook_register("admin.toolbar.article", "admin_toolbar_article", 0);
$this->hook_register("page.global.init", "article_init", -10);
$this->hook_register("module.article.install", "article_install", -10);

$this->hook_register("admin.icons", "article_admin_icons", 0);
$this->hook_register("init.permissions", "article_perms", 0);

function article_admin_icons($t, &$output) {
	$output["article"] = array("module" => "article", "url" => "article/", "icon" => "fas fa-book", "text" => "Articles", "showMobile" => true, "file" => "article.admin.php");
}

function article_perms(&$perms){
	$perms[] = "article";
}

function article_install($t){
	return array("state" => true);
}

function article_init($t){
	$articleId = $t->router->_data["id"][0];
	$resultAliasId = $articleId;	
	$resultAlias = dibi::query("SELECT * FROM :prefix:article WHERE (alias=%s", $articleId,") AND (language = '')")->fetch();
	if($resultAlias != null) $resultAliasId = $resultAlias["id"];

	if(($articleId == Database::getConfig("mainpage") || $resultAliasId == Database::getConfig("mainpage")) && $t->router->_data["module"][0] == "article")
		$t->root->config->set("style.body.class", "index");

	if((($t->router->_data["id"][0] == Database::getConfig("mainpage")) || $articleId == "") && $t->router->_data["module"][0] == "article")
		$t->root->config->set("style.body.class", "index");
}

function databaze_init_setting($t){
	$articleId = $t->router->_data["id"][0];
	if($articleId == NULL){
		$articleId = Database::getConfig("mainpage");
	}
	$resultAliasId = $articleId;	

	$resultAlias = dibi::query("SELECT * FROM :prefix:article WHERE (alias=%s", $articleId,") AND (language = '')")->fetch();
	if($resultAlias != null) $resultAliasId = $resultAlias["id"];
	
	$result = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $articleId, " or mid=%i", $resultAliasId," or alias=%s", $articleId,") AND (language = %s", _LANGUAGE, ")")->fetch();
	if($result == NULL) {
		$result = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $articleId, " or mid=%i", $resultAliasId," or alias=%s", $articleId,") AND (language = %s", "", ")")->fetch();
	}

	if($result == NULL){	
		if(file_exists(_ROOT_DIR."/templates/".Database::getConfig("style")."/error404.php")){
			$t->root->config->set("show-title", false);
			$t->root->config->set("custom-render", true);
		}
		$title = t("Error 404");
	}else{
		$title = $result["title"];

		$dtags = explode(",", $result["tags"]);
		if(trim($result["tags"]) == "")
			$dtags = [];

		if(in_array("template", $dtags)){	
			$t->root->config->set("show-title", false);
			$t->root->config->set("custom-render", true);
		}

		if($result["custommenu"] != ""){
			$t->root->config->set("style.menu.left", "hide");
		}
	}
	$t->root->config->set("pre-title",$title);
}

function admin_toolbar_article($t, &$output){	
	if($t->router->_data["id"][0] == "index" or $t->router->_data["id"][0] == null)
		$t->router->_data["id"][0] = Database::getConfig("mainpage");

	$result = dibi::query("SELECT * FROM :prefix:article WHERE id=%i", $t->router->_data["id"][0], " or alias=%s", $t->router->_data["id"][0])->fetch();

	if($result == NULL){
		echo "<div class='d right'></div>";
		echo "<div class='l right'>Článek neexistuje</div>";
		echo "<div class='l b right'>Správa članku</div>";
	}else{
		echo "<div class='d right'></div>";
		echo "<div class='l right'>";
			echo "<select id=action_article><option value=1 selected>Veřejné</option><option>Soukromé</option><option>Nepublikovano</option></select>";
		echo "</div>";
		echo "<a href='".$t->router->url."admin/article/nopublic/".$result["id"]."' class=right>Zrušit publikaci</a>";
		echo "<a href='".$t->router->url."admin/article/edit/".$result["id"]."' class=right>Upravit</a>";
		echo "<div class='l b right'>Správa članku</div>";
	}
}

function article_page_draw($t, &$output){
	$articleId = $t->router->_data["id"][0];

	if($articleId == "index" || $articleId == null)
		$articleId = Database::getConfig("mainpage");

	$resultAliasId = $articleId;	

	$resultAlias = dibi::query("SELECT * FROM :prefix:article WHERE (alias=%s", $articleId,") AND (language = '')")->fetch();
	if($resultAlias != null) $resultAliasId = $resultAlias["id"];
	
	$result = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $articleId, " or mid=%i", $resultAliasId," or alias=%s", $articleId,") AND (language = %s", _LANGUAGE, ")")->fetch();
	if($result == NULL) {
		$result = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $articleId, " or mid=%i", $resultAliasId," or alias=%s", $articleId,") AND (language = %s", "", ")")->fetch();
	}
	if($result == NULL or $result["state"] == 4){
		http_response_code(404);
		if(!Utilities::isErrorPage()){
			$t->root->page->draw_error("", t("Page not found!"));
		}
	}elseif($result["state"] == 5){
		$t->root->page->draw_error("<br>Tento článek nebyl publikován", "Článek ".$result["title"]." není publikován!");
	}else{
		if(isset($_GET["comid"]) and isset($_GET["delete"])){
			//if($result["comments"] != 3){
				$result_ = dibi::query('SELECT * FROM :prefix:comments WHERE `id`=%i', $_GET["comid"])->fetch();
				if((User::current() and $result_["autor"] == User::current()["id"]) or (User::isPerm("admin"))){
					dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 1), 'WHERE `id`=%s', $_GET["comid"]);
					echo "<div class='succ_edit'><b>Smazano!</b></div>";
				}else
					echo "You dont have right permission";
			//}
		}
		else if(isset($_GET["comid"])){
			if($result["comments"] != 3){
				$result_ = dibi::query('SELECT * FROM :prefix:comments WHERE `id`=%i', $_GET["comid"])->fetch();
				if(isset($_GET["edit"])){
					if(str_replace("_R_N_", "\r\n", $_GET["edit"]) == $result_["text"])
						echo $result_["text"]."<div class='succ_edit'><b>Žádná změna!</b></div>";
					else if((User::current() and $result_["autor"] == User::current()["id"]) or (User::isPerm("admin"))){
						$data = array(
								"user" 		=> User::current()["id"],
								"ip" 		=> Utilities::ip(),
								"date" 		=> time(),
								"parent" 	=> "comment_".($_GET["comid"]),
								"text"		=> $result_["text"],
								"type" 		=> "comment_edited"
							);
						$result = dibi::query('INSERT INTO :prefix:history', $data);
						$arr = array("text" => str_replace("_R_N_", "\r\n", $_GET["edit"]));
						dibi::query('UPDATE :prefix:comments SET ', $arr, 'WHERE `id`=%s', $_GET["comid"]);
						echo $arr["text"]."<div class='succ_edit'><b>".t("edited")."!</b></div>";
					}else
						echo t("You dont have right permission");
				}
				else if(isset($_GET["delete"])){
					if((User::current() and $result_["autor"] == User::current()["id"]) or (User::isPerm("admin"))){
						dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 1), ' WHERE id = %s ', $_GET["comid"]);
						echo "<div class='succ_edit'><b>".t("deleted")."!</b></div>";
					}
				}else{
					echo "<textarea class='form-control' rows=3 id='edit_text_".$_GET["comid"]."'>".$result_["text"]."</textarea>";
					echo "<span class=tool><button class='btn btn-primary btn-sm' onClick=\"ajaxsend_nor(this, '".$t->router->url_."".$t->router->_data["id"][0]."/?__type=ajax&comid=".$result_["id"]."&edit='+$('#edit_text_".$_GET["comid"]."').val().replace(new RegExp('\\r\\n', 'g'), '_R_N_'), '#text_com_".$result_["id"]."', '#edit_com_".$result_["id"]."');return false;\">Upravit</button> <button class='btn btn-danger btn-sm' onClick=\"hideSlide('#text_com_".$result_["id"]."', '#edit_com_".$result_["id"]."');return false;\">Zrušit</button></span>";
				}
			}
		}else{
			if($result["custommenu"] != ""){
				echo '<div class=left><ul class="menu">';
				$t->root->page->menu_draw($result["custommenu"], array("noul" => true, "li_selected_class" => "selected", "a_class" => "href", "class" => "menu"));
				echo "</ul></div><div class=right>";
			}

			$perm = User::permission(User::current()["permission"])["permission"];
			if(!User::current()) $perm["admin"] = 0;

			if(isset($_GET["pass"])) $pass = $_GET["pass"]; else $pass = "";
			$vis = $result["visiblity"];
			if(substr($vis,0,1) == "!" and substr($vis,1) == $pass){ header("location:?pass=".sha1($pass)); }
			if(substr($vis,0,1) == "!" and sha1(substr($vis,1)) != $pass){
				echo "<h1>".t("this article is locked")."</h1>";
				echo "<br><form action=# method=get>".t("password").": <input type=password name=pass> <button>".t("send")."</button></form>";
			}else{
				$r = $result["text"];
				$plugin = $t->root->module_manager->hook_call("page.bbcode", null, $r);

				$t->root->config->set("comments.show", true);

				if(file_exists(_ROOT_DIR."/templates/".$t->root->template."/template.article.php")){
					include _ROOT_DIR."/templates/".$t->root->template."/template.article.php";
				}else{
					$dtags = explode(",", $result["tags"]);
					if(trim($result["tags"]) == "")
						$dtags = [];

					$render = true;
					if(in_array("template", $dtags)){						
						$file = _ROOT_DIR."/views/content/".$result["text"].".view";

						if(file_exists($file)){
							$t->root->config->set("comments.show", false);

							$render = false;

							ob_start();
							$model = array("user" => User::current());
							$t->root->page->template_parse($file, $model);
							$text = ob_get_contents();
							ob_end_clean();

							echo $text;							
						}
					}

					if($render){
						echo "<article author=\"".User::get($result["author"])["nick"]."\">";
							
						if(in_array("test", $dtags)){
							$t->root->page->error_box(t("this article is intended for testing"), "warning");
						}
						if(!in_array("no-header", $dtags)){
							echo "<h1>".$result["title"]."</h1>";
						}
						if(!in_array("form", $dtags)){
							$at = "<a href='".Router::url()."profile/".User::get($result["author"])["login"]."'>".User::get($result["author"])["nick"]."</a>";
							if(substr($result["author"],0,1)=="@"){
								$at = substr($result["author"],1);
							}
							echo "<div class='page-info row'><div class='col-sm-12 col-md-6'>".$at."</div><div class='col-sm-12 col-md-6 text-right text-sm-left'>".Strings::str_time($result["date"])."</div>";
	//LANG
	//dibi::query("SELECT * FROM :prefix:article WHERE id=%i", $t->router->_data["id"][0], " or alias=%s", $t->router->_data["id"][0])->fetch()
							$languages = explode(",", Database::getConfig("languages"));
							$default   = Database::getConfig("default-lang");

							//echo "<a href='".$t->router->url_."".($result["alias"]==""?$result["id"]:$result["alias"])."' class='langsel ".("" == $result["language"]?"selected":"")."'>".t($default)."</a> ";
							$onlyone = true;
							foreach($languages as $lng){
								if($lng != $default){
									$fln = dibi::query("SELECT * FROM :prefix:article WHERE (mid=%i", $result["id"], " or id=%i", $result["id"],") and language=%s", $lng)->fetch();
										if($fln != null){
											$onlyone = false;
											continue;
										}
								}
							}
							if(!$onlyone && 1==2){
								echo "<div class=col-md-12>";
								foreach($languages as $lng){
									if($lng == $default){
										$fln = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $result["mid"], " or mid=%i", $result["id"],") and language=%s", "")->fetch();
										echo "<a href='".$t->router->url_."".($fln["alias"]==""?$fln["id"]:$fln["alias"])."' class='langsel ".("" == $result["language"]?"selected":"")."'>".t($lng)."</a> ";
									}else{
										$fln = dibi::query("SELECT * FROM :prefix:article WHERE (mid=%i", $result["id"], " or id=%i", $result["id"],") and language=%s", $lng)->fetch();
										if($fln != null)
											echo "<a href='".$t->router->url_."".($fln["alias"]==""?$fln["id"]:$fln["alias"])."' class='langsel ".($lng == $result["language"]?"selected":"")."'>".t($lng)."</a> ";
									}
								}
								echo "</div>";
							}
							echo "</div>";
						}
						echo "<div class=text>".$plugin["output"]."</div>";
						if(!in_array("form", $dtags) and count($dtags) > 0){
							echo "<div class=page-bott>";
							for($i = 0; $i < count($dtags); $i++)
								echo "<span class=val>".$dtags[$i]."</span>";
							echo "</div>";
						}

						echo "</article>";
					}
				}				

				if($t->root->config->get("comments.show")){
					echo "<div class=comments>";
						$errc = 0;
						if(isset($_POST["addcomment"])){
							if(!User::current() and $_POST["nick"] == ""){
								$errc=1;
							}
							if($_POST["text"] == ""){
								$errc=2+$errc;
							}

							$pattern = '/(\s|^)(www\.|https?:\/\/)?[a-z0-9]+(\.|\(tečka\))[a-z0-9]{2,4}\S*/m';
							preg_match_all($pattern, $_POST["text"], $matches, PREG_PATTERN_ORDER);

							if($t->root->config->get("comment-max-url") == null) $t->root->config->set("comment-max-url", "2");
							if($t->root->config->get("comment-timeout") == null) $t->root->config->set("comment-timeout", "20");

							if(count($matches[1]) > $t->root->config->get("comment-max-url")){
								$errc = 5;
							}

							if(!User::current()) {
								$last = dibi::query('SELECT * FROM :prefix:comments WHERE ip = %s', Utilities::ip(), 'ORDER BY id DESC LIMIT 1');
								if(count($last) > 0){
									$last = $last->fetch();
									if($last["time"] > strtotime("-".$t->root->config->get("comment-timeout")." seconds") and User::getBlock(Utilities::ip(), "comments-add") == 0){
										User::block(Utilities::ip(), "comments-add", $t->root->config->get("comment-timeout"), "Automatical spam bot all block: ".User::getBlock(Utilities::ip(), "comments-add"), "", $_POST["text"]);
										$errc = 7;
									}else if($last["time"] > strtotime("-".$t->root->config->get("comment-timeout")." seconds")){
										$errc = 8;
										$how = User::getBlock(Utilities::ip(), "comments-add");
										if($how < 10)
											User::block(Utilities::ip(), "comments-add", 60*5, "Automatical spam bot all block: ".User::getBlock(Utilities::ip().", still spaming block for 5 minutes", "comments-add"), "", $_POST["text"]);
										else
											User::block(Utilities::ip(), "comments-add", 60*60, "Automatical spam bot all block: ".User::getBlock(Utilities::ip().", still spaming block for 1 hour", "comments-add"), "", $_POST["text"]);
									}
								}
							}

							if($errc==0){
								User::blockOkay(Utilities::ip(), "comments-add");
								$data = array(
										"autor"		=> "-1",
										"time" 		=> time(),
										"ip" 		=> Utilities::ip(),
										"text" 		=> $_POST["text"],
										"parent" 	=> "article_".($result["id"])
										);
								if(User::current()){
									$data["autor"] = User::current()["id"];
								}else{
									$data["autor"] = "@".$_POST["nick"];
								}
								$resultc = dibi::query('INSERT INTO :prefix:comments', $data);
								if($resultc){
									$t->root->page->error_box("Komentář byl přidán", "ok");
									header("location:".Router::url().$articleId);
								}else{
									$t->root->page->error_box("Error #256", "error");
								}
							}
						}

						$showno=false;
						if($result["comments"] != 3){
							echo "<h2>Komentáře</h2>";
							if((User::getBlock(Utilities::ip(), "comments-add") > 1 && User::getLastBlock(Utilities::ip(), "comments-add")["time_long"] > time()) || $errc == 8){
								$t->root->page->error_box(t("Sending new comments for you is baned until")." (".Strings::str_time(User::getLastBlock(Utilities::ip(), "comments-add")["time_long"]).")", "error");
							}
							else if((User::current() || $result["comments"] == 1)){
								echo "<form action=# method=post>";
									echo "<textarea class='form-control' rows=3 name=text style='".(($errc==2 or $errc==3 or $errc==5 or $errc==7)?"border-color:red;":"")."'>".(isset($_POST["text"])?$_POST["text"]:"")."</textarea>";
									echo "<div class='row' style='padding: 5px 14px;'>";
										if(User::current())
											echo "<div class='col-8 nopadding' style='padding-top: 4px !important;'><span class='d-sm-inline d-none'>Přidáváte komentář jako</span><span class='d-inline d-sm-none'><i class='fas fa-user'></i></span> <b>".User::current()["nick"]."</b></div>";
										else
											echo "<div class='".(User::current()?"col-8":"col-sm-12 text-right")." nopadding' style='padding: 4px !important;'>Přezdívka <input type=text class='' name=nick value='".(isset($_POST["nick"])?$_POST["nick"]:"")."' style='max-width: 200px;width: 100%;padding: 4px;".(($errc==1 or $errc==3)?"border-color:red;":"")."'></div>";
										echo "<div class='".(User::current()?"col-4":"col-sm-12")." nopadding text-right' style='padding-left: 5px !important;padding-top: 4px !important;'><input type=submit class='btn btn-primary btn-sm' name=addcomment value='Přidat komentář'></div>";
										echo "<div style='clear:both;'></div>";
										if($errc == 5)
											$t->root->page->error_box(t("The maximum number of possible links in the post has been reached"), "error");
										if($errc == 7)
											$t->root->page->error_box(t("The limit for sending new post is")." ".$t->root->config->get("comment-timeout")." ".t("seconds"), "error");
									echo "</div>";
								echo "</form>";
							}else{ echo "<div class='info_small'>Pro přidání komentáře se musíte přihlásit!</div>"; }
						}else{
							$result_ = dibi::query('SELECT * FROM :prefix:comments WHERE `parent`=%s', "article_".($result["id"]), " AND isDelete = 0 ORDER BY id DESC");
							if(count($result_ )>0)
								$t->root->page->error_box("Komentáře byly zakázány", "error");
						}

							$result_ = dibi::query('SELECT * FROM :prefix:comments WHERE `parent`=%s', "article_".($result["id"]), " AND isDelete = 0 ORDER BY id DESC LIMIT 30");
							foreach ($result_ as $n => $row) {
								if(substr($row["autor"],0,1)=="@"){
									$autor["nick"] = "<span class=anonym title='nepřihlášen'>".substr($row["autor"],1,strlen($row["autor"])-1)."</span>";
									$autor["avatar"] = Database::getConfig("default-avatar");
									$autor["ip"] = "nepřihlášen";
								}else{
									$autor = User::get($row["autor"]);
									$perm = User::permission($autor["permission"]);
									$autor["nick"] = "<span title='".$perm["name"]."' style='color:".$perm["color"]."'>".$autor["nick"]."</span>";
								}
								echo "<div class=comment>";
									echo "<div class=avatar><img src='".Router::url()."upload/avatars/".$autor["avatar"]."'></div>";
									echo "<div class=body>";
										$resh = dibi::query('SELECT * FROM :prefix:history WHERE `parent`=%s', "comment_".$row["id"], " ORDER BY date DESC LIMIT 1");
										echo "<div class=titlebar>".$autor["nick"]." <span class='time'>".Strings::str_time($row["time"])."</span>";
										echo (User::isPerm("admin")?"<span class='admin d-none d-sm-inline'><b>.:.</b> ".$autor["ip"]." / <span title='IP adresa autora příspěvku'>".$row["ip"]."</span></span>":"");
										if(count($resh) > 0){
											echo "<span class='admin edited d-none d-sm-inline'>Upraveno</span>";
											echo "<span class='admin edited d-sm-none d-inline'><i class='fas fa-pencil-alt'></i></span>";
										}
										if((User::current() and $row["autor"] == User::current()["id"]) or (User::isPerm("admin"))){
											echo "<span class=admin style='float:right;'>";
											if(count($resh) > 0){
												$resh = $resh->fetch();
												echo "<span class='admin editedby d-none d-md-inline'>Upravil <b>".User::get($resh["user"])["nick"]."</b> (".$resh["ip"]."), celkem ".(count(dibi::query('SELECT * FROM :prefix:history WHERE `parent`=%s', "comment_".$row["id"])))."x</span>";
											}
											echo "<a href='".Router::url()."comments/".$row["id"]."/edit/' onClick=\"ajaxcall_loadtext('".$t->router->url_."".$articleId."?__type=ajax&comid=".$row["id"]."', '#text_com_".$row["id"]."', '#edit_com_".$row["id"]."');return false;\">Upravit</a> <a href='".Router::url()."comments/".$row["id"]."/delete/' onClick=\"ajaxcall_loadtext('".$t->router->url_."".$t->router->_data["id"][0]."?__type=ajax&comid=".$row["id"]."&delete', '#text_com_".$row["id"]."', '#edit_com_".$row["id"]."');return false;\"><i class='fas fa-times'></i></a></span>";
										}
										echo "</div>";
										echo "<div class=text>";

											$r = htmlentities($row["text"]);
											$plugin = $t->root->module_manager->hook_call("page.bbcode", null, $r);

											echo "<div id='text_com_".$row["id"]."'>".$plugin["output"]."</div>";
											echo "<div style='display:none;' id='edit_com_".$row["id"]."'><span class='loading small'></span> Načítám...</div>";
										echo "</div>";
									echo "</div>";
									echo "<div style='clear:both;'></div>";
								echo "</div>";
							}

					echo "</div>";
				}
			}

			if($result["custommenu"] != ""){echo "</div>";}
		}
	}
}
