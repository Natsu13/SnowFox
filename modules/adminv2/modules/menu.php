<?php
$action = $t->router->_data["action"][0];

$menus = null;
$first = "";
$result = dibi::query('SELECT * FROM :prefix:menu');
foreach ($result as $n => $row) {
	if($row["box"] == "deleted") continue;
	if(!isset($menus[$row["box"]])){ $menus[$row["box"]] = array( 0 => true, 1 => 0); }
	if($menus[$row["box"]][1]<$row["position"]) $menus[$row["box"]][1] = $row["position"];
	if($first == "") $first = $row["box"];
}
if($t->router->_data["id"][0] == 1) $sel = $first; else $sel = $t->router->_data["id"][0];

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
		foreach($menus as $n => $box){
			if($n == "deleted") continue;
			echo "<li ".($sel == $n?"class=select":"")."><a href='".Router::url()."adminv2/menu/show/".$n."/?language=".$_GET["language"]."'>".t($n)."</a></li>";
		}
		//echo "<li><a href='".Router::url()."adminv2/menu/new/".$sel."/".($menus[$sel][1]+1)."/' title='".t("new item")."'>+</a></li>";
	echo "</ul>";
}

if($action == "delete"){
	if($t->router->_data["id"][0] == "trash") {
		dibi::query('DELETE FROM :prefix:menu WHERE `box`=%s', "deleted");
	}else{
		dibi::query('DELETE FROM :prefix:menu WHERE `id`=%s', $t->router->_data["id"][0]);
	}
	header("location:".$t->router->url_."adminv2/menu/show/".$t->router->_data["state"][0]."?language=".$_GET["language"]);
}
elseif($action == "new"){
	$data = array(
				"title" => t("new item"),
				"typ" => "index",
				"visible" => 0,
				"position" => $t->router->_data["state"][0],
				"box" => $t->router->_data["id"][0],
				"milevel" => 0,
				"malevel" => 10000,
				"language" => isset($_GET["language"]) && $_GET["language"] != ""? $_GET["language"]: null
			);
	$result = dibi::query('INSERT INTO :prefix:menu', $data);
	header("location:".$t->router->url_."adminv2/menu/edit/".dibi::InsertId()."/?language=".$_GET["language"]);
}
elseif($action == "duplicate"){
	$result = dibi::query("SELECT * FROM :prefix:menu WHERE id=%i", $t->router->_data["id"][0])->fetch();
	if($result == NULL){
		$t->root->page->draw_error(t("called item does not exist!"));
	}else{
		$data = array(
					"title" => $result["title"]." - Duplicated",
					"typ" => $result["typ"],
					"visible" => 0,
					"position" => $result["position"] + 1,
					"box" => $result["box"],
					"milevel" => $result["milevel"],
					"malevel" => $result["malevel"],
					"language" => $result["language"],
					"data" => $result["data"]
				);
		dibi::query('INSERT INTO :prefix:menu', $data);
		header("location:".$t->router->url_."adminv2/menu/edit/".dibi::InsertId()."/?language=".$result["language"]);
	}
}
elseif($action == "edit"){
	$result = dibi::query("SELECT * FROM :prefix:menu WHERE id=%i", $t->router->_data["id"][0])->fetch();
	if($result == NULL){
		$t->root->page->draw_error(t("called item does not exist!"));
	}else{
		if(isset($_POST["edit"])){
			$data = "";
			
			if($_POST["typ"] == "article"){
				$idc = explode(", ", $_POST["clanekid"]);
				$data = Config::ssave(array( "id" => $idc[0], "alias" => $idc[1] ));
			}
			elseif($_POST["typ"] == "login"){
				$data = Config::ssave(array( "register" => (isset($_POST["register"])?1:0) ));
			}
			elseif($_POST["typ"] == "url"){
				$data = Config::ssave(array( "url" => $_POST["odkaz"] ));
			}
			
			$mi = 0;$ma = 10000;
			if(isset($_POST["visible"])) $v=true; else $v=false;
			/*
			if($_POST["howvis"] == -1){ $mi=0;$ma=10000; }
			else if($_POST["howvis"] == 0){ $mi=0;$ma=0; }
			else if($_POST["howvis"] == 2){ $mi=1;$ma=10000; }
			else if($_POST["howvis"] == 3){ $mi=5000;$ma=10000; }
			else if($_POST["howvis"] == 4){ $mi=$_POST["customilevel"];$ma=$_POST["customalevel"]; }
			*/
			$perm = explode(",", $_POST["permission"]);
			$mi = $perm[0]; $ma = $perm[1];
			if($mi == -1 && $ma == -1){
				$mi = $_POST["permMin"];
				$ma = $_POST["permMax"];
			}
			
			if($mi > $ma){ 
				$s=$ma;
				$ma=$mi;
				$mi=$s; 
			}

			$box = $_POST["box"];
			if($_POST["cutomit"] == 1) {
				$box = $_POST["customname"];
			}

			$arr = array(
						"title" => $_POST["title"],
						"box" => $box,
						"typ" => $_POST["typ"],
						"data" => $data,
						"visible" => $v,
						"milevel" => $mi,
						"malevel" => $ma
					); 
			dibi::query('UPDATE :prefix:menu SET ', $arr, 'WHERE `id`=%s', $t->router->_data["id"][0]);
			header("location:".$t->router->url_."adminv2/menu/edit/".$t->router->_data["id"][0]."/ok?language=".$_POST["language"]);
		}
		if($t->router->_data["state"][0] == "ok"){
			$t->root->page->error_box(t("item has been updated"), "ok", true);
		}
		
		if(isset($_GET["type"])){
			if($_GET["type"] != $result["typ"]){
				$result["typ"] = $_GET["type"];
				$result["data"] = "";
			}
		}
		
		echo "<h1 class=hide-mobile>".t("edit items")." \"".$result["title"]."\"</h1>";

		echo "<div class=content>";
			echo "<div class=left-side>";
				echo "<form action=# method=post>";
					echo "<input type=hidden name=language value=\"".$_GET["language"]."\">";

						echo "<div class=\"form-group row mb-2\">";
							echo "<label class=\"col-sm-3 col-form-label\">".t("Title")."</label>";
							echo "<div class=\"col-sm-9\"><input type=text class=form-control name=title value='".$result["title"]."'></div>";
						echo "</div>";

						echo "<div class=\"form-group row mb-2\">";
							echo "<label class=\"col-sm-3 col-form-label\">".t("Box")."</label>";
							echo "<div class=\"col-sm-9\">";
								echo "<select id=box name=box class=selinp style='width:100%;' onChange=\"var selected = $($(this).find('option:selected')).val();if(selected==-1){\$('#newpol').show();$('#cutomit').val(1);}else{\$('#newpol').hide(100);$('#cutomit').val(0);};\">";
									$menus = null;
									$first = "";
									$result_ = dibi::query('SELECT * FROM :prefix:menu');
									foreach ($result_ as $n => $row) {
										if(!isset($menus[$row["box"]])){ $menus[$row["box"]] = array( 0 => true, 1 => 0); }
										if($menus[$row["box"]][1]<$row["position"]) $menus[$row["box"]][1] = $row["position"];
										if($first == "") $first = $row["box"];
									}
									echo "<option value='-1'>".t("Custom menu")."</option>";
									foreach($menus as $n => $box){
										echo "<option value='".$n."' ".($result["box"] == $n?"selected":"").">".$n."</option>";
									}
								echo "</select>";
								echo "<div id=newpol style='display:none;'>";
									echo "<input type=hidden value='0' name=cutomit id=cutomit>";
									echo "<input type=text value='' class=form-control name=customname id=customname>";
								echo "</div>";
							echo "</div>";
						echo "</div>";

						echo "<script>var lastSettMen = \"".$result["typ"]."\";</script>";
						echo "<div class=\"form-group row mb-2\">";
							echo "<label class=\"col-sm-3 col-form-label\">".t("Item type")."</label>";
							echo "<div class=\"col-sm-9\">";
								$types = array(
									["index", "Link to main page"], 
									["category", "Link to category"], 
									["article", "Link to article"], 
									["url", "Link to site"], 
									["login", "Login"],
									["separator", "Separator"]
								);

								echo "<select name=typ id=typ style='width:100%;' onChange=\"$('#'+lastSettMen+'-set').hide();lastSettMen = $($(this).find('option:selected')).val(); $('#'+lastSettMen+'-set').show();\">";
								foreach($types as $type) {
									echo "<option value='".$type[0]."' ".Utilities::selected($type[0] == $result["typ"]).">".t($type[1])."</option>";
								}
								echo "</select>";
							echo "</div>";
						echo "</div>";

						if($result["visible"]==1 || ($result["milevel"]==0 && $result["malevel"]==0) || ($result["milevel"]==0 && $result["malevel"]==10000) || ($result["milevel"]==1 && $result["malevel"]>1) || ($result["milevel"]==5000 && $result["malevel"]>5000)) 
							$custom = false; 
						else 
							$custom = true;

						echo "<div class=\"form-group row mb-2\">";
							echo "<label class=\"col-sm-3 col-form-label\"></label>";
							echo "<div class=\"col-sm-9\">";
								echo "<label><input id=visb type=toggle_swipe ".($result["visible"]==1?"checked":"")." name=visible value='1'> ".t("Visible")."</label>";
							echo "</div>";
						echo "</div>";

						echo "<div class=\"form-group row mb-2\">";
							echo "<label class=\"col-sm-3 col-form-label\">".t("Accessibility")."</label>";
							echo "<div class=\"col-sm-9\">";
								echo "<select name=permission style='width:100%;' onchange=\"if($($(this).find('option:selected')).val()=='-1,-1'){ $('#customlevel').css('display','block'); }else{ $('#customlevel').hide(); };\">";
									$perms = array(
										[0, 10000, "Anyone"], 
										[0, 0, "Only not loged"], 
										[1, 10000, "Only loged"], 
										[5000, 10000, "Only admin"], 
										[-1, -1, "Custom"]
									);
									$hasSelected = false; $hasCustom = false;
									foreach($perms as $perm) {
										$level = "Level: ".$perm[0]." >< ".$perm[1];
										$selected = $result["milevel"] == $perm[0] && $result["malevel"] == $perm[1];
										if(!$hasSelected) { 
											$hasSelected = $selected;
											if($perm[0] == -1 && $perm[1] == -1) { $selected = true; $hasCustom = true; }
										}		
										if($perm[0] == -1 && $perm[1] == -1) { $level = $result["milevel"]." >< " . $result["malevel"]; }
										echo "<option value='".$perm[0].",".$perm[1]."' ".Utilities::selected($selected).">".t($perm[2])." (".$level.")</option>";
									}
								echo "</select>";
								echo "<div id=customlevel style='display:".($hasCustom?"block":"none").";'>";
									echo "<div>".t("Custom")."</div>";
									echo Utilities::permissionSelect($result["milevel"], "permMin", "width:50%", "form-control", "level", true);
									echo Utilities::permissionSelect($result["malevel"], "permMax", "width:50%", "form-control", "level", true);
								echo "</div>";
							echo "</div>";
						echo "</div>";
						
						if($result["data"] != "")
							$data = $t->root->config->load($result["data"]);
						else
							$data = array("id" => -1, "alias" => "", "register" => 0, "url" => "");
						
						echo "<div class=\"form-group row mb-2\">";
							echo "<label class=\"col-sm-3 col-form-label\"></label>";
							echo "<div class=\"col-sm-9\"><b>".t("Menu type setting")."</b><hr/></div>";
						echo "</div>";

						echo "<div id=article-set style='display:".($result["typ"] == "article"?"block":"none")."'>";
							if(!isset($data["alias"])) $data["alias"] = "";

							echo "<div class=\"form-group row mb-2\">";
								echo "<label class=\"col-sm-3 col-form-label\">".t("Article")."</label>";
								echo "<div class=\"col-sm-9\">";
									echo "<select id='shcl' name=clanekid style='width:100%;'>";
										$result_ = dibi::query('SELECT * FROM :prefix:article');
										foreach ($result_ as $n => $row) {
											echo "<option value='".$row["id"].", ".$row["alias"]."' ".($data["alias"]==$row["alias"]?"selected":"").">".$row["title"]."</option>";
										}
									echo "</select>";
								echo "</div>";
							echo "</div>";				
						echo "</div>";

						echo "<div id=index-set style='display:".($result["typ"] == "index"?"block":"none")."'>";
							echo "<div class=\"form-group row mb-2\">";
								echo "<label class=\"col-sm-3 col-form-label\">".t("Link to main page")."</label>";
								echo "<div class=\"col-sm-9 col-form-label\">";
									echo "<i>".Router::url()."</i>";
								echo "</div>";
							echo "</div>";
						echo "</div>";

						echo "<div id=separator-set style='display:".($result["typ"] == "separator"?"block":"none")."'>";
							echo "<div class=\"form-group row mb-2\">";
								echo "<label class=\"col-sm-3 col-form-label\"></label>";
								echo "<div class=\"col-sm-9 col-form-label\">";
									echo t("Separated from each item");
								echo "</div>";
							echo "</div>";
						echo "</div>";

						echo "<div id=category-set style='display:".($result["typ"] == "category"?"block":"none")."'>";
							echo "<div class=\"form-group row mb-2\">";
								echo "<label class=\"col-sm-3 col-form-label\"></label>";
								echo "<div class=\"col-sm-9 col-form-label\">";
									echo t("In development");
								echo "</div>";
							echo "</div>";
						echo "</div>";

						echo "<div id=login-set style='display:".($result["typ"] == "login"?"block":"none")."'>";
							if(!isset($data["register"])) $data["register"] = 0;

							echo "<div class=\"form-group row mb-2\">";
								echo "<label class=\"col-sm-3 col-form-label\"></label>";
								echo "<div class=\"col-sm-9\">";
									echo "<label><input type=checkbox ".($data["register"]==1?"checked":"")." name=register value='1'> ".t("Show register button")."</label>";
								echo "</div>";
							echo "</div>";					
						echo "</div>";

						echo "<div id=url-set style='display:".($result["typ"] == "url"?"block":"none")."'>";
							if(!isset($data["url"])) $data["url"] = "";

							echo "<div class=\"form-group row mb-2\">";
								echo "<label class=\"col-sm-3 col-form-label\">".t("Link")."</label>";
								echo "<div class=\"col-sm-9\">";
									echo "<input type=text class=form-control name=odkaz value='".$data["url"]."'>";
								echo "</div>";
							echo "</div>";
						echo "</div>";

					echo "<div class=\"form-group row mb-2\">";
						echo "<label class=\"col-sm-3 col-form-label\"></label>";
						echo "<div class=\"col-sm-9\">";
							echo "<input type=submit name=edit class='btn btn-primary' value=".t("edit")."> <a class='btn btn-secondary' href='".$t->router->url_."adminv2/menu/show/".$result["box"]."/?language=".$_GET["language"]."'>".t("back")."</a>";
						echo "</div>";
					echo "</div>";					
				echo "</form>";
			echo "</div>";
		echo "</div>";
	}
}
elseif($action == "show"){
	if($_GET["__type"] == "ajax"){
		if(isset($_GET["sort"])){
			$sort = explode(",", $_GET["sort"]);
			$i = 0;
			foreach($sort as $n => $so){
				$update = array("position" => $i);
				if(isset($_GET["setbox"])){
					$update["box"] = $_GET["setbox"];
				}
				if(isset($_GET["language"])){					
					$update["language"] = ($_GET["language"] == ""? null: $_GET["language"]);
				}
				dibi::query('UPDATE :prefix:menu SET ', $update, "WHERE `id`=%i", $so);
				$i++;
			}
		}
		exit;
	}

	echo "<div style='padding:10px;'>";						
		//echo " <b>".t("usable menu")."</b>: ".implode(", ",explode(";",$t->root->config->get("usable_menu")));
		//if($t->root->config->get("usable_menu") == "") echo "<i>".t("nothing")."</i>";
		if(!isset($_GET["language"]) || $_GET["language"] == ""){$lang=null;}else{$lang=$_GET["language"];}
		if($lang == null)
			$result = dibi::query('SELECT * FROM :prefix:menu WHERE box=%s', $sel, " AND language IS %s", $lang," ORDER BY position");
		else
			$result = dibi::query('SELECT * FROM :prefix:menu WHERE box=%s', $sel, " AND language = %s", $lang," ORDER BY position");

		echo "<div>";
			$languages = explode(",", Database::getConfig("languages"));
			echo "<div class=oneline>";
				echo "<a href='".Router::url()."adminv2/menu/show/".$sel."/".($menus[$sel][1]+1)."/' class='button ".($lang==null?"toggle":"")."'>".t("Default")."</a>";
				for($i=0;$i<count($languages);$i++){
					if($_GET["language"] == $languages[$i]) {
						echo "<a href=# class='button toggle'>".t($languages[$i])."</a>";
					}else{
						echo "<a class='button' href='".Router::url()."adminv2/menu/show/".$sel."/".($menus[$sel][1]+1)."/?language=".$languages[$i]."'>".t($languages[$i])."</a>";
					}			
				}
			echo "</div>";
		echo "</div>";

		echo "<div class=row>";
			echo "<div class=col-md-6>";
				echo "<div class=sortable_menu_box>";
					echo "<div class=title>".t($sel)."</div>";
					echo "<div class=empty ".($result->count()==0?"":"style='display:none;'").">".t("Nothing in box")." '".t($sel)."'</div>";
					echo "<div class=sortable_menu id='menusortable' data-box='".$sel."'>";				
					foreach ($result as $n => $row) {
						echo "<a class='sortable' href='".Router::url()."adminv2/menu/edit/".$row["id"]."?language=".$_GET["language"]."' data-id='".$row["id"]."'>";
							echo "<span onclick=\"window.location.href = '".Router::url()."adminv2/menu/duplicate/".$row["id"]."'; event.stopPropagation(); return false;\" class='duplicate-icon'><i class=\"far fa-copy\"></i></span>";
							echo "<span class=mover></span>";
							if($row["visible"] == 0){
								echo "<span class='title desc'><s>".$row["title"]."</s></span>";
							}else{
								echo "<span class=title>".$row["title"]."</span>";
							}							
							echo "<span class=type>".t($row["typ"])."</span>";
						echo "</a>";
					}
					echo "</div>";
					/*echo "<a class='sortable' href='".Router::url()."adminv2/menu/new/".$sel."/".($menus[$sel][1]+1)."/?language=".$_GET["language"]."'>";
						echo "<i class=\"fas fa-plus\"></i> <span class=title>".t("new item")."</span>";
					echo "</a>";*/
					echo "<a href='".Router::url()."adminv2/menu/new/".$sel."/".($menus[$sel][1]+1)."/?language=".$_GET["language"]."' class='btn btn-primary mt-2 hide-mobile'><i class=\"fas fa-plus\"></i> ".t("New item")."</a>";
				echo "</div>";
			echo "</div>";

			echo "<div class=col-md-6>";
				echo "<div class=sortable_menu_box>";					
					$result = dibi::query('SELECT * FROM :prefix:menu WHERE box=%s', "deleted", "ORDER BY position");
					echo "<div class=title><i class=\"far fa-trash-alt\"></i> ".t("deleted")." (".t("Unactive menu").")</div>";
					echo "<div class=empty ".($result->count()==0?"":"style='display:none;'").">".t("Nothing in box")." '".t("deleted")."'</div>";
					echo "<div class=sortable_menu id='menudeletesortable' data-box='deleted'>";				
					foreach ($result as $n => $row) {
						echo "<a class='sortable' href='".Router::url()."adminv2/menu/edit/".$row["id"]."' data-id='".$row["id"]."'>";
							echo "<span class=mover></span>";
							echo "<span class=title>".$row["title"]."</span>";
							echo "<span class=type>".t($row["typ"])."</span>";
						echo "</a>";
					}
					echo "</div>";
					echo "<a href='".Router::url()."adminv2/menu/delete/trash/".$sel."/?language=".$_GET["language"]."' class='btn btn-danger mt-3'><i class=\"far fa-trash-alt\"></i> ".t("Delete all items in trash")."</a>";					
				echo "</div>";
			echo "</div>";
		echo "</div>";
		
	echo "</div>";

	?>
        <script>
		var notupdate = false;
        $( function() {
			setTimeout(function(){
				actionButton.setText("<?php echo t("New item"); ?>")
				actionButton.changeIcon("fas fa-plus");
				actionButton.show();
				actionButton.onclick(function(){
					window.location.href = "<?php echo Router::url()."adminv2/menu/new/".$sel."/".($menus[$sel][1]+1)."/?language=".$_GET["language"]; ?>";
				});
			}, 100);
		
            $(".sortable_menu").sortable({
                items: "a.sortable",
                handle: ".mover",
                //axis: "y",
                //animation: 150,
				connectWith: ".sortable_menu",
                stop: function(event, ui){
					if(notupdate){
						notupdate = false;
						return;
					}
                    var id = id;
                    var parent = parent;
                    var callback = callback;
                    var sort = $.map($(this).find("a.sortable"), function(e,i){ return $(e).data("id"); }).join(",");
                    $.get('<?php echo Router::url()."adminv2/menu/?__type=ajax"; ?>', { sort: sort }, function(data){ });                                      
                },
				receive: function(event, ui){
					notupdate = true;
                    var id = id;
                    var parent = parent;
                    var callback = callback;
                    var sort = $.map($(this).find("a.sortable"), function(e,i){ return $(e).data("id"); }).join(",");
					if(sort == ""){
						$(this).parent().find(".empty").show();
					}else { $(this).parent().find(".empty").hide(); }
                    $.get('<?php echo Router::url()."adminv2/menu/?__type=ajax"; ?>', { sort: sort, setbox: $(this).data("box"), language: "<?php echo $_GET["language"]; ?>" }, function(data){ });                                      
                }
            });
        } );
        </script>
    <?php
}