<?php
$action = $t->router->_data["action"][0];

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
		echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/article/'>".t("list")."</a></li>";
		echo "<li ".($action == "new"?"class=select":"")."><a href='".$t->router->url_."adminv2/article/new/'>".t("write new")."</a></li>";
		echo "<li ".($action == "recycle"?"class=select":"")."><a href='".$t->router->url_."adminv2/article/recycle/'>".t("recycle trash")."</a></li>";
		echo "<li ".($action == "category"?"class=select":"")."><a href='".$t->router->url_."adminv2/article/category/'>".t("category")."</a></li>";
		echo "<li ".($action == "comments"?"class=select":"")."><a href='".$t->router->url_."adminv2/article/comments/'>".t("comments")."</a></li>";
	echo "</ul>";
}

if($action == "comments"){
		echo "<div class=bottommenu><a href='".$t->router->url_."adminv2/system/#comments'>".t("settings")."</a></div>";

		$iporname = "%";

		if(isset($_POST["cuval"]) or isset($_GET["cuval"])){
			if(isset($_GET["cuval"]))
				$iporname = $_GET["cuval"];
			else
				$iporname = $_POST["cuval"];
		}
		if(isset($_POST["deleteall"])){
			dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 1), ' WHERE ip LIKE %s ', $iporname ,' OR autor LIKE %s ', $iporname);
			$t->root->page->error_box(t("deleted"), "ok");
		}
		if(isset($_GET["deletecom"])){
			dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 1), ' WHERE id = %s ', $_GET["deletecom"]);
			$t->root->page->error_box(t("deleted"), "ok");
		}
		if(isset($_GET["undelete"])){
			dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 0), ' WHERE id = %s ', $_GET["undelete"]);
			$t->root->page->error_box(t("undeleted"), "ok");
		}

		echo "<br><form action=# method=post><input type=hidden name=cuval id=cuval value='".$iporname."'> ".t("User or ip address").": ";
			echo "<select id=user name=user class=selinp style='width:220px;' onChange=\"\$('#cuval').val(\$('#'+\$(this).attr('parent')).attr('value_'));\">";
			if($iporname == "%")
				echo "<option id='0' selected> - Vyber uživatele nebo napište ip - </option>";
			else
				echo "<option id='0' selected>".$iporname."</option>";
			$result = dibi::query('SELECT * FROM :prefix:users');
			foreach ($result as $n => $row) {
				$permission = User::permission(User::get($row["id"])["permission"]);
				echo "<option value='".$row["id"]."' ".($iporname == $row["id"]?"selected":"").">".$row["nick"]."</option>";
			}
			echo "</select>";
			echo " <input type='submit' class='btn btn-primary' name=search value='".t("search")."'>";
			if($iporname != "%"){
				echo " <input class='btn btn-danger' type='submit' name=deleteall value='Delete all his comments'>";
			}
		echo "</form>";

		$blocksip = array();
		$result = dibi::query('SELECT * FROM :prefix:block');
		foreach ($result as $n => $row) {
			$blocksip[$row["ip"]] = true;
		}

		$paginator = new Paginator(15, Router::url()."adminv2/system/emails/?page=(:page)");
		$result = $paginator->query('SELECT * FROM :prefix:comments WHERE ip LIKE %s ', $iporname ,' OR autor LIKE %s ', $iporname ,'ORDER BY time DESC');

		echo $paginator->getPaginator();

		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=30>#ID</th><th width=100>".t("time")."</th><th width=400>".t("text")."</th><th width=60>".t("deleted")."</th><th width=120>".t("action")."</th></tr>";
		//$result = dibi::query('SELECT * FROM :prefix:comments WHERE ip LIKE %s ', $iporname ,' OR autor LIKE %s ', $iporname ,'ORDER BY time DESC');
		foreach ($result as $n => $row) {
			echo "<tr>";
				if(substr($row["autor"],0,1)=="@"){
					$autor["nick"] = "<span class=anonym title='nepřihlášen'>".substr($row["autor"],1,strlen($row["autor"])-1)."</span>";
					$autor["avatar"] = Database::getConfig("default-avatar");
					$autor["ip"] = "nepřihlášen";
				}else{
					$autor = User::get($row["autor"]);
					$perm = User::permission($autor["permission"]);
					$autor["nick"] = "<span title='".$perm["name"]."' style='color:".$perm["color"]."'>".$autor["nick"]."</span>";
				}

				echo "<td valign=top style='padding-top: 13px;'><span style='color:silver'>".$row["id"]."</span></td>";
				echo "<td valign=top style='padding-top: 13px;'>".Strings::str_time($row["time"])."</td>";
				$block = false;
				if(isset($blocksip[$row["ip"]])){ $block = true; }
				echo "<td>".htmlentities($row["text"])."<div style='font-size:10px;'>".$autor["nick"]." ... ".$autor["ip"]." ... <span title='IP adresa autora příspěvku".($block?"[ this ip is blocked! ]":"")."' style='".($block?"color:red;font-weight:bold;":"")."'>".$row["ip"]."</span></div></td>";
				echo "<td align=center style='padding-top: 13px;".($row["isDelete"] == 1?"background:silver":"")."'>".($row["isDelete"] == 1?t("Yes"):t("No"))."</td>";
				echo "<td valign=top style='padding-top: 13px;'>";
					echo '<span class=xbuttonline>';
						$ar = explode("_", $row["parent"]);						
						if($row["isDelete"] == 1)
							echo "<a href='".$t->router->url_."adminv2/article/comments/?undelete=".$row["id"]."".($iporname != "%"?"&cuval=".$iporname:"")."' class=xbutton><i class=\"fas fa-trash-alt\"></i> ".t("Undelete")."</a>";
						else
							echo "<a href='".$t->router->url_."adminv2/article/comments/?deletecom=".$row["id"]."".($iporname != "%"?"&cuval=".$iporname:"")."' class=xbutton><i class=\"fas fa-trash-alt\"></i> ".t("Delete")."</a>";
						if($ar[0] == "article")
							echo " <a target=_new href='".$t->router->url_."".$ar[1]."' class=xbutton><i class=\"far fa-eye\"></i> ".t("View")."</a>";
					echo "</span>";
				echo "</td>";
			echo "</tr>";
		}
		echo "</table>";

		echo $paginator->getPaginator();
		
}else if($action == "nopublic"){
	if(Database::getConfig("mainpage") == $t->router->_data["id"][0]){
		$t->root->page->error_box(t("you can not unpublish main page"), "error");
	}else{
		dibi::query('UPDATE :prefix:article SET ', array("state" => 5), 'WHERE `id`=%s', $t->router->_data["id"][0], " OR `alias`=%s", $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/article/");
	}
}elseif($action == "delete"){
	if(Database::getConfig("mainpage") == $t->router->_data["id"][0]){
		$t->root->page->error_box(t("you can't delete main page"), "error");
	}else{
		$data = array(
				"user" 		=> User::current()["id"],
				"ip" 		=> Utilities::ip(),
				"date" 		=> time(),
				"parent" 	=> "article_".($t->router->_data["id"][0]),
				"type" 		=> "article_recycled"
			);
		$result = dibi::query('INSERT INTO :prefix:history', $data);
		dibi::query('UPDATE :prefix:article SET ', array("state" => 4), 'WHERE `id`=%s', $t->router->_data["id"][0], " OR `alias`=%s", $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/article/");
	}
}elseif($action == "undelete"){
	$data = array(
			"user" 		=> User::current()["id"],
			"ip" 		=> Utilities::ip(),
			"date" 		=> time(),
			"parent" 	=> "article_".($t->router->_data["id"][0]),
			"type" 		=> "article_cancel_recycled"
		);
	$result = dibi::query('INSERT INTO :prefix:history', $data);
	dibi::query('UPDATE :prefix:article SET ', array("state" => 0), 'WHERE `id`=%s', $t->router->_data["id"][0], " OR `alias`=%s", $t->router->_data["id"][0]);
	header("location:".$t->router->url_."adminv2/article/");
}elseif($action == "new"){
	$data = array(
				"title"    => t("New article"),
				"alias"    => Strings::random(10),
				"date"	   => time(),
				"author"   => User::current()["id"],
				"oauthor"  => User::current()["id"],
				"state"    => 5,
				"language" => _LANGUAGE
			);
	$result = dibi::query('INSERT INTO :prefix:article', $data);
	$id = dibi::getInsertId();
	dibi::query('UPDATE :prefix:article SET ', array("mid" => $id, "alias" => Strings::undiacritic(t("New article"))."_".$id), 'WHERE `id`=%s', $id);
	header("location:".$t->router->url_."admin/article/edit/".$id);
}elseif($action == "recycle"){
	if(User::isPerm("recycle") == 1){
		dibi::query('DELETE FROM :prefix:article WHERE `state`=%i', 4);
		header("location:".$t->router->url_."adminv2/article/");
	}else{
		$t->root->page->error_box(t("you dont have permission"), "error");
	}
}elseif($action == "category"){
	if($_GET["who"] == "new"){
		$data = array(
				"name" => t("new category"),
				"alias" => "new-category",
				"description" => "",
				"minlevel" => 0
			);
		$result = dibi::query('INSERT INTO :prefix:category', $data);		
		header("location:".$t->router->url_."adminv2/article/category-edit/".dibi::InsertId());
	}
	elseif($_GET["who"] == "delete"){
		$id = $t->router->_data["id"][0];
		if($id < 2){
			$t->root->page->error_box(t("this is system category"), "error");
		}else{
			dibi::query('UPDATE :prefix:article SET ', array("category" => 0), 'WHERE `category`=%s', $t->router->_data["id"][0]);
			dibi::query('DELETE FROM :prefix:category WHERE `id`=%s', $t->router->_data["id"][0]);
			header("location:".$t->router->url_."adminv2/article/category/");
		}
	}
	elseif($_GET["who"] == "edit"){
		echo "<h1 class=hide-mobile>".t("Category edit")."</h1>";

		if(isset($_POST["edit"])){
			$arr = array(
						"name" => $_POST["name"],
						"alias" => $_POST["alias"],
						"description" => $_POST["description"],
						"minlevel" => $_POST["permission"]
					);
			dibi::query('UPDATE :prefix:category SET ', $arr, 'WHERE `id`=%s', $t->router->_data["id"][0]);
			header("location:".$t->router->url_."admin/article/category/");
		}

		$result = dibi::query("SELECT * FROM :prefix:category WHERE id=%i", $t->router->_data["id"][0])->fetch();

		echo "<div class=content>";
			echo "<form action=# method=post>";

				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Name")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class=form-control name=name value='".$result["name"]."'></div>";
				echo "</div>";

				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Alias")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class=form-control name=alias value='".$result["alias"]."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><a href='".Router::url().$result["alias"]."' target=_blank>Zobrazit <i class='fas fa-external-link-alt'></i></a></div>";
				echo "</div>";

				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Description")."</label>";
					echo "<div class=\"col-sm-9\"><textarea name=description rows=3>".$result["description"]."</textarea></div>";
				echo "</div>";

				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Minimal permission")."</label>";
					echo "<div class=\"col-sm-9\">";
						echo "<select id=permission name=permission style='width:100%;'>";
							$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
							foreach ($resul_ as $n => $row) {
								echo "<option value='".$row["id"]."' ".($row["id"] == $result["minlevel"]?"selected":"").">".$row["name"]." (Level: ".$row["level"].")</option>";
							}
						echo "<option value=0  ".(0 == $result["minlevel"]?"selected":"").">Neregistrovaný uživatel</option></select>";
					echo "</div>";
				echo "</div>";

				echo "<div class=\"form-group row mb-2 hide-mobile\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit name=edit id=btn-update class='btn btn-primary' value='".t("edit")."'></div>";
				echo "</div>";

			echo "</form>";	
		echo "</div>";

		?>
		<script>
			$(function(){
				setTimeout(function(){
					actionButton.setText("<?php echo t("Save"); ?>")
					actionButton.changeIcon("fas fa-save");
					actionButton.show();
					actionButton.onclick(function(){
						$("#btn-update").click();
					});
				}, 100);
			});
		</script>
		<?php
	}else{
		echo "<span class=warning>Články v smazané kategorii budou přesunuty na defaultní!</span>";
		echo "<div class='bottommenu hide-mobile'><a href='".$t->router->url_."admin/article/category-new/'>".t("new category")."</a></div>";

		echo "<div class='content padding'>";
			echo "<div class=hide-mobile>";
				echo "<table class='table table-sm tablik'>";
				echo "<tr><th width=350>".t("title")."</th><th width=120>".t("alias")."</th><th width=120>".t("access")."</th><th width=100>".t("action")."</th></tr>";
				$result = dibi::query('SELECT * FROM :prefix:category WHERE parent = 0 ORDER BY id DESC');
				foreach ($result as $n => $row) {
					echo "<tr>";
						$perm = User::permission($row["minlevel"]);
						echo "<td>".$row["name"]."</td>";
						echo "<td>".$row["alias"]."</td>";
						echo "<td style='color:".$perm["color"].";'>".$perm["name"]."</td>";
						echo "<td>";
							echo '<span>';
								echo "<a href='".$t->router->url_."adminv2/article/category-edit/".$row["id"]."'><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a>";
								if($row["id"] != 1)
									echo " <a href='".$t->router->url_."adminv2/article/category-delete/".$row["id"]."'><i class=\"fas fa-trash-alt\"></i> ".t("delete")."</a>";
							echo "</span>";
						echo "</td>";
					echo "</tr>";
				}
				echo "</table>";
			echo "</div>";

			echo "<div class=show-mobile>";
				echo "<div class='card small margin'><div class=content><ul class=list>";
				$result = dibi::query('SELECT * FROM :prefix:category WHERE parent = 0 ORDER BY id DESC');
				foreach ($result as $n => $row) {
					$perm = User::permission($row["minlevel"]);
					echo "<li class='swipable' data-swipable='true' data-id='".$row["id"]."''>";
						if($row["id"] != 1)
							echo "<div class='back red left' style='padding-top: 21px;'>".($row["state"] == 4 ? t("undelete article"): t("delete"))."</div>";
						echo "<div class='main'>";
							echo "<div class=title>".$row["name"]."</div>";
							echo "<div><span style='color:silver;'>".$row["alias"]."</span> <span style='color:".$perm["color"].";'>".$perm["name"]."</div>";
						echo "</div>";
					echo "</li>";				
				}
				echo "</ul></div></div>";
			echo "</div>";

		echo "</div>";
		?>
		<script>
		$(function(){
			$(".swipable").on("click", function(){
				window.location.href = "<?php echo $t->router->url_."adminv2/article/category-edit/"; ?>" + $(this).data("id");
			});
			$(".swipable").on("swiped", function(e, direction){
				if(direction == "left"){
					window.location.href = "<?php echo $t->router->url_."adminv2/article/category-delete/"; ?>" + $(this).data("id");
				}
			});
			setTimeout(function(){
				actionButton.setText("<?php echo t("New category"); ?>")
				actionButton.changeIcon("fas fa-plus");
				actionButton.show();
				actionButton.onclick(function(){
					window.location.href = "<?php echo $t->router->url_."adminv2/article/category-new/"; ?>";
				});
			}, 100);
		});
		</script>
		<?php	
	}
}elseif($action == "show"){
	echo "<div class='bottommenu hide-mobile'><a href=# onClick='setHomePage();return false;'>".t("select the main article")."</a></div>";

	$category = NULL;
	if(isset($_GET["category"])) $category = $_GET["category"];

	$result_ = dibi::query('SELECT * FROM :prefix:category ORDER BY id');
	echo "<label style='padding:5px;'>".t("Filter by category")." <select id=category name=category style='width:200px;' onchange=\"window.location.href='?category='+$(this).find('option:selected').val();\">";
	echo "<option value=''>Všechny kategorie</option>";
	foreach ($result_ as $n => $row) {
		echo "<option value='" . $row["id"] . "' " . (($category == $row["id"]) ? "selected" : "") . ">" . $row["name"] . "</option>";
	}
	echo "</select></label>";

	if($category == NULL) {
		$paginator = new Paginator(10, Router::url()."adminv2/article/?page=(:page)");
		$result = $paginator->query('SELECT * FROM :prefix:article WHERE mid=id ORDER BY id DESC');
	}else{
		$paginator = new Paginator(10, Router::url()."adminv2/article/?page=(:page)&category=".$category);
		$result = $paginator->query('SELECT * FROM :prefix:article WHERE mid=id AND category = %s', $category,' ORDER BY id DESC');
	}
	

	echo "<div class='content padding'>";

	echo $paginator->getPaginator();

	echo "<div class=hide-mobile>";
		echo "<table class='table table-sm tablik tb-middle'>";
		echo "<tr><th width=300>".t("Title")."</th><th width=120>".t("Author")."</th><th width=120>".t("Alias")."</th><th width=150>".t("Date of publication")."</th><th width=100>".t("Action")."</th></tr>";
		//$result = dibi::query('SELECT * FROM :prefix:article WHERE language=%s', '',' ORDER BY id DESC');
		foreach ($result as $n => $row) {
			$custom = false;$cname = "";
			if(substr($row["author"],0,1)=="@"){
				$custom = true;
				$cname = substr($row["author"],1);
			}else{
				$cname = User::get($row["author"])["nick"];
			}

			if($row["state"] == 4){
				echo "<tr><td><b><i class=\"fas fa-trash-alt\"></i> ".$row["title"]."<span style='color: #A9A8A8;font-size: 9px;padding-left: 9px;text-transform: uppercase;'>".t("recycled")."</span></b></td>";
			}else
				echo "<tr><td valign=middle><b>".$row["title"]." ".($row["html"] == 1?"<span class='tlam'>html</span>":"")."".(Database::getConfig("mainpage") == $row["id"]?"<span class='tlam'>".t("main article")."</span>":"")."".($row["state"] == 5?"<span style='color: #A9A8A8;font-size: 9px;padding-left: 9px;text-transform: uppercase;'>".t("not public")."</span>":"")."</b></td>";

			if($custom)
				echo "<td><i style='color:#8e8d8d;'>".$cname."</i></td>";
			else
				echo "<td>".$cname."</td>";

			echo "<td>".$row["alias"]."</td><td>".Strings::str_time($row["date"])."</td><td>";
				echo "<a href='".$t->router->url_."adminv2/article/edit/".$row["id"]."'><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a> ";
				if($row["state"] == 4) {
					echo "<a href='".$t->router->url_."adminv2/article/undelete/".$row["id"]."' data-title='".t("Undelete article")."'><i class=\"fas fa-ban\"></i></a> ";
				}
				else {
					echo "<a href='".$t->router->url_."adminv2/article/delete/".$row["id"]."' data-title='".t("Delete article")."'><i class=\"fas fa-trash-alt\"></i></a> ";
				}
				if($row["state"] != 5) {
					echo "<a href='".$t->router->url_."adminv2/article/nopublic/".$row["id"]."' data-title='".t("Cancel publishing")."'><i class=\"fas fa-stop-circle\"></i></a> ";					
				}
			echo "</td></tr>";
		}
		echo "</table>";
	echo "</div>";

	echo "<div class=show-mobile>";
		echo "<div class='card small margin'><div class=content><ul class=list>";
			foreach ($result as $n => $row) {
				$custom = false;$cname = "";
				if(substr($row["author"],0,1)=="@"){
					$custom = true;
					$cname = substr($row["author"],1);
				}else{
					$cname = User::get($row["author"])["nick"];
				}

				echo "<li class='swipable' data-swipable='true' data-id='".$row["id"]."' data-delete='".($row["state"] == 4? "undelete": "delete")."'>";
					echo "<div class='back red left'>".($row["state"] == 4 ? t("undelete article"): t("delete"))."</div>";
					echo "<div class='back blue right'>".t("cancel publishing")."</div>";
					echo "<div class='main'>";
						echo "<div class=title>".($row["state"] == 4?"<i class=\"far fa-trash-alt\"></i> ":"").$row["title"]."</div>";
						echo "<div><span style='color:silver;'>".$row["alias"]."</span>".($row["state"] == 5?"<span style='color: #A9A8A8;font-size: 9px;padding-left: 9px;text-transform: uppercase;'>".t("not public")."</span>":"")."</div>";
						echo "<div>".($row["html"] == 1?"<span class='tlam'>html</span>":"")."".(Database::getConfig("mainpage") == $row["id"]?"<span class='tlam'>".t("main article")."</span>":"")."</div>";
						echo "<div class='lover inline'>";
						if($custom)
							echo "<div><i class=\"far fa-user\"></i> ".$cname."</div>";
						else
							echo "<div><i class=\"fas fa-user\"></i> ".$cname."</div>";
						echo "<div><i class=\"fas fa-calendar-day\"></i> ".Strings::str_time($row["date"])."</div>";
						echo "</div>";
					echo "</div>";
				echo "</li>";
			}
		echo "</div>";
	echo "</div>";

	echo "</div>";
	?>
	<script>
	function setHomePage(){
		var d = new Dialog();
		d.setTitle('<?php echo t("select the main article"); ?>');
		d.setButtons([Dialog.CLOSE,Dialog.SAVE]);
		d.Load('<?php echo $t->root->router->url."ajax/dialog/setHomePage"; ?>');
		butt = d.getButtons();
		$(butt[0]).click(function(){ d.Close(); });
		$(butt[1]).click(function(){
			$(butt[1]).remove();
			d.Load('<?php echo $t->root->router->url."ajax/dialog/setHomePage"; ?>', "set="+$("#shcl").val());
		});
	}
	$(function(){
		$(".swipable").on("click", function(){
			window.location.href = "<?php echo $t->router->url_."adminv2/article/edit/"; ?>" + $(this).data("id");
		});
		$(".swipable").on("swiped", function(e, direction){
			if(direction == "right"){
				window.location.href = "<?php echo $t->router->url_."adminv2/article/nopublic/" ?>" + $(this).data("id");
			}else{
				window.location.href = "<?php echo $t->router->url_."adminv2/article/"; ?>"+ $(this).data("delete") + "/" + $(this).data("id");
			}
		});
        setTimeout(function(){
            actionButton.setText("<?php echo t("select the main article"); ?>")
            actionButton.changeIcon("fas fa-cog");
            actionButton.show();
            actionButton.onclick(function(){
                setHomePage();
            });
        }, 100);
    });
	</script>
	<?php
}
else if($action == "edit"){
	$lng = "";
	if(isset($_GET["lang"]))
		$lng = $_GET["lang"];
	if($lng == "") $lng = _LANGUAGE;

	$result = dibi::query("SELECT * FROM :prefix:article WHERE (mid=%i", $t->router->_data["id"][0],") AND language=%s", $lng)->fetch();
	if($result == NULL){
		if($lng != ""){
			$result = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $t->router->_data["id"][0], ")")->fetch();
			$data = array(
						"mid"			=> $result["id"],
						"title"   		=> $result["title"],						
						"alias"   		=> $result["alias"],
						"date"	  		=> time(),
						"author"  		=> User::current()["id"],
						"oauthor" 		=> $result["oauthor"],
						"state"   		=> 5,
						"text"			=> $result["text"],
						"custommenu" 	=> $result["custommenu"],
						"comments"		=> $result["comments"],
						"html" 			=> $result["html"],
						"tags" 			=> $result["tags"],
						"category" 		=> $result["category"],
						"language" 		=> $lng
					);
			dibi::query('INSERT INTO :prefix:article', $data);
			header("location:".$t->router->url_."adminv2/article/edit/".$result["id"]."/?lang=".$lng);
		}
		$t->root->page->draw_error(t("article does not exist"), t("article")." ".$t->router->_data["id"][0]." ".t("does not exist")."!");
	}else{
		if(isset($_GET["saveConcept"])){
			$time = Strings::str_time(time());
			//$koncept = dibi::query("SELECT * FROM :prefix:history WHERE parent=%s", "article_".$t->router->_data["id"][0], "ORDER BY id DESC")->fetch();
			$koncept = null;
			$koncept_ = null;
			$resultDraft = null;
			$konceptDraft = null;
			$koncept = dibi::query("SELECT * FROM :prefix:history WHERE parent=%s", "article_".$t->router->_data["id"][0], " ORDER BY id DESC LIMIT 10");
			foreach ($koncept as $n2 => $ro2) {
				if($ro2["type"] != "article_concept") break;
				if($resultDraft == null && $ro2["user"] == User::current()["id"]){
					$resultDraft = Config::sload($ro2["data"]);
					$resultDraft["text"] = $ro2["text"];
					$konceptDraft = $ro2;
				}
			}
			if($resultDraft != null){
				$koncept_ = $konceptDraft;
			}

			if($result["text"] == $_GET["text"]){
				$time = Strings::str_time($result["date"]);
			}elseif($koncept_ != null){
				if($koncept_["text"] == $_GET["text"]){
					$time = Strings::str_time($koncept_["date"]);
				}else{
					$data = array(
						"date" 		=> time(),
						"data" 		=> Config::ssave($result),
						"text" 		=> $_GET["text"]
					);
					dibi::query('UPDATE :prefix:history SET ', $data, 'WHERE `id`=%s', $koncept_["id"]);
				}
			}else{
				$data = array(
						"user" 		=> User::current()["id"],
						"ip" 		=> Utilities::ip(),
						"date" 		=> time(),
						"data" 		=> Config::ssave($result),
						"text" 		=> $_GET["text"],
						"parent" 	=> "article_".($t->router->_data["id"][0]),
						"type" 		=> "article_concept"
					);
				$result__ = dibi::query('INSERT INTO :prefix:history', $data);
			}
			echo "<span class='sbox ok'>".t("Draft saved")." ".$time."</span>";
			exit;
		}elseif(isset($_GET["showArticle"])){
			$resul2 = dibi::query("SELECT * FROM :prefix:history WHERE id=%i", $_GET["showArticle"])->fetch();
			$resul3 = Config::sload($resul2["data"]);
			echo "<span class='sbox ok'>".t("Contribution")." ".$resul3["title"]." (#".$_GET["showArticle"].")</span>";
			echo "<textarea rows=15 style='width:100%;'>".$resul2["text"]."</textarea>";
			exit;
		}
		if(isset($_POST["edit"])){
			$stt = $result["state"];
			if(($stt != 0 and $stt != 5) or Database::getConfig("mainpage") == $result["id"]){  }else{ $stt = (isset($_POST["public"])?0:5); }

			$vis = $_POST["visiblity"];
			if($vis == 1) $vis = "";
			elseif($vis == 2) $vis = "2";
			else{
				$vis = "!".$_POST["vishes-pass"];
			}

			if($_POST["alias"] == ""){
				$_POST["alias"] = Strings::undiacritic($_POST["title"]);
			}

			$arr = array(
						"title" => $_POST["title"],
						"alias" => $_POST["alias"],
						"html" => $_POST["html"],
						"text" => $_POST["text"],
						"state" => $stt,
						"custommenu" => $_POST["custommenu"],
						"comments" => $_POST["comments"],
						"author" => ($_POST["author"] == "custom"?"@".$_POST["customname"]:$_POST["author"]),
						"visiblity" => $vis,
						"tags" => $_POST["article_tags"],
						"category" => $_POST["category"]
						);			
			dibi::query('UPDATE :prefix:article SET ', $arr, 'WHERE `id`=%s', $_POST["oid"]);

			/*if($result["mid"] == $result["id"]) {						
				$arr = array("alias" => $_POST["alias"]);
				dibi::query('UPDATE :prefix:article SET ', $arr, 'WHERE `mid`=%i', $result["mid"]);
			}*/

			$data = array(
						"user" 		=> User::current()["id"],
						"ip" 		=> Utilities::ip(),
						"date" 		=> time(),
						"data" 		=> Config::ssave($result),
						"text" 		=> $result["text"],
						"parent" 	=> "article_".($_POST["oid"]),
						"type" 		=> "article_history"
					);
			$result__ = dibi::query('INSERT INTO :prefix:history', $data);
			header("location:".$t->router->url_."adminv2/article/edit/".$t->router->_data["id"][0]."/ok?lang=".$lng);
		}

		if(isset($_GET["history"])){
			$his = dibi::query("SELECT * FROM :prefix:history WHERE type='article_history' AND id=%i", $_GET["history"])->fetch();
			if($his != NULL){
				$result = Config::sload($his["data"]);
				$t->root->page->error_box(t("loaded history of article")." | ".t("created")." ".Strings::str_time($his["date"])." <span style='color:#807f7f;'>".strlen(utf8_decode($his["text"]))."b</span> <a style='float:right;' href='".$t->router->url_."admin/article/edit/".$result["id"]."'>X</a>", "info");
			}
		}
		$resultDraft = null;$konceptDraft = null;$yourself = false;
		$koncept = dibi::query("SELECT * FROM :prefix:history WHERE parent=%s", "article_".$result["id"], " ORDER BY id DESC LIMIT 10");
		foreach ($koncept as $n2 => $ro2) {
			if($ro2["type"] != "article_concept") break;
			if(!isset($_GET["nodraft"]) and !isset($_GET["history"])){
				if($resultDraft == null || ($ro2["user"] == User::current()["id"] and !$yourself)){
					$resultDraft = Config::sload($ro2["data"]);
					$resultDraft["text"] = $ro2["text"];
					$konceptDraft = $ro2;
					if($ro2["user"] == User::current()["id"]) $yourself= true;
				}
			}
		}
		if($resultDraft != null){
			$result = $resultDraft;
			$t->root->page->error_box(t("You modifies automatically saved draft")." | ".t("Created")." ".Strings::str_time($konceptDraft["date"])." ".t("by", array("first-u" => false))." <b>".User::get($konceptDraft["user"])["nick"]."</b> <span style='color:#807f7f;'>".strlen(utf8_decode($konceptDraft["text"]))."b</span> <a style='float:right;' href='".$t->router->url_."admin/article/edit/".$result["id"]."?nodraft'>X</a>", "info");
		}

		if($t->router->_data["state"][0] == "ok"){
			$t->root->page->error_box(t("Article was updated"), "ok", true);
		}
		//echo "<h1>".t("editing article")." \"".$result["title"]."\"</h1>";
		echo "<h1 class='fix mobile-small'><span class=id><a href='".Router::url()."".$result["id"]."' target=_blank>#".$result["id"]."</a></span> ".t("Editing article")." <span class=highlight>".$result["title"]."</span></h1>";
		echo "<form method=post><input type='hidden' name=oid value='".$result["id"]."'>";
			//		echo "<input type=submit name=edit value='".t("save article")."'>";// <input type=submit name=copy value='".t("save the article as a copy")."'>

		echo "<div class=content>";
			
			echo "<div class=left-side>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-2 col-form-label\">".t("Title")."</label>";
					echo "<div class=\"col-sm-10\"><input type=\"text\" class=\"form-control\" value=\"".$result["title"]."\" id='title-name' name=\"title\"></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-2 col-form-label hide-mobile\"></label>";
					echo "<label class=\"col-sm-2 col-form-label col-form-bold\">".t("Alias")."</label>";
					echo "<div class=\"col-sm-6 col-8\">";
						echo "<input type=\"text\" class=\"form-control\" onfocus=\"if($(this).val()==''){ $(this).val(toUrl($('#title-name').val())); }\" value=\"".$result["alias"]."\" name=\"alias\">";						
					echo "</div>";
					echo "<div class=\"col-sm-2 col-4\">";
					if ($result["language"] != _LANGUAGE) {
						echo "<a href='".Router::url()."".$result["alias"]."/?showlang=".$result["language"]."' class='btn btn-primary btn-full-width' title='".t("Open on a new page")."' data-title='".t("Open on a new page")."' target=_blank><i class=\"fas fa-external-link-alt\"></i> ".t("View")."</a>";
					}else{
						echo "<a href='".Router::url()."".$result["alias"]."/' class='btn btn-primary btn-full-width' title='".t("Open on a new page")."' data-title='".t("Open on a new page")."' target=_blank><i class=\"fas fa-external-link-alt\"></i> ".t("View")."</a>";
					}
					echo "</div>";
				echo "</div>";
				echo "<div style='margin-top:25px;'></div>";
				
				echo "<textarea name=text style='display:none;' id=oldtextsaved>" . $result["text"] . "</textarea><input type=hidden name=html id=htmlonlyval value='" . (isset($_GET["html"])? $_GET["html"]: $result["html"]) . "'>";
				echo "<a name=html></a>";
				echo "<div style='margin-bottom: -3px;'>";
					echo "<a href=# class='stb' onClick=\"switchToEditor(" . ($result["html"] == 1 ? "true" : "false") . ");return false;\">Editor</a>";
					echo "<a href=# class='stb' onClick=\"switchToHtml();return false;\">HTML</a>";
				echo "</div>";
				//echo "<a href=# onClick=\"$('#htmlonly').val($('#oldtextsaved').val());return false;\">Load text from save to html</a><br>";
				echo "<textarea name=text style='width:100%;' class='tinimcenocheck' id=intereditor rows=20>" . htmlentities($result["text"]) . "</textarea>";
				//echo "<textarea id=htmlonly name=htmltext style='width: 100%; height: 250px; z-index: 5000; position: relative; border:1px solid silver; border-width: 5px 1px 1px; border-style: solid; border-color: #222;outline:0px;".($result["html"] == 1?"":"display:none;")."'>".$result["text"]."</textarea>";

				/*$output = preg_replace_callback('/\[form id=\"(.*?)\"\]/U',function ($matches) use($t) {
						return _LoadForm($matches, $t);
					}, $output);*/
				$result_ = dibi::query('SELECT * FROM :prefix:form');
				$forms = array();
				foreach ($result_ as $n => $row) {
					$forms[$row["id"]] = array("(#" . $row["id"] . ") " . $row["name"], "<a class='btn btn-primary btn-sm' style='font-size: 11px;margin-top: -3px;' href='" . $t->router->url_ . "admin/content/form-answer/" . $row["id"] . "'>" . t("answers") . "</a>", "<a class='btn btn-primary btn-sm' style='font-size: 11px;margin-top: -3px;' href='" . $t->router->url_ . "admin/content/form-edit/" . $row["id"] . "'>" . t("edit") . "</a>");
				}
				preg_match_all('/\[form id=\"(.*?)\"\]/U', $result["text"], $matches, PREG_OFFSET_CAPTURE);
				for ($i = 0; $i < count($matches[1]); $i++) {
					$id = $matches[1][$i][0];
					echo "<div style='padding: 8px;background: #3c3c3c;color: white;border-bottom: 1px solid #505050;'><div style='float:right;'>" . $forms[$id][1] . " " . $forms[$id][2] . "</div><span class=formim><i class=\"fas fa-clipboard-list\"></i> " . $forms[$id][0] . "</span></div>";
				}				

				echo "<div style='border: 1px solid #757575;background: #060606;;'><div style='padding: 4px 10px;background: #1b1b1b;color:white;'>" . t("history") . "</div><div style='overflow-y: auto;height: 200px;'>";
				echo "<table class='table table-bordered table-sm tablik' style='margin:0px;' border=0>";
				echo "<tr><th width=120>" . t("edited") . "</th><th width=120>" . t("when") . "</th><th width=90>" . t("IP") . "</th><th width=70>" . t("size") . "</th><th width=160>" . t("type") . "</th><th width=80>" . t("action") . "</th></tr>";
				$result_ = dibi::query('SELECT * FROM :prefix:history WHERE `parent`=%s', "article_" . $result["id"], " ORDER BY id DESC");
				foreach ($result_ as $n => $row) {
					echo "<tr><td>" . User::get($row["user"])["nick"] . "</td><td style='font-size: 14px;'>" . Strings::str_time($row["date"]) . "</td>";
					echo "<td><span style='text-overflow: ellipsis;overflow: hidden;width: 95px;display: block;font-size: 14px;' title='" . $row["ip"] . "'>" . $row["ip"] . "</span></td>";
					echo "<td style='color:#807f7f;'>" . (strlen(utf8_decode($row["text"])) == 0 ? "---" : strlen(utf8_decode($row["text"])) . "b") . "</td>";
					if ($row["type"] == "article_history") {
						echo "<td><span style='background: #37ea76;padding: 3px;font-size: 12px;text-transform: uppercase;'>" . t("Editing article") . "</span></td>";
						echo "<td><a href='" . $t->router->url_ . "adminv2/article/edit/" . $result["id"] . "?history=" . $row["id"] . "'>" . t("load") . "</a> | <a href='#' onClick=\"loadArticle(" . $row["id"] . ");return false;\">O</a></td>";
					}
					if ($row["type"] == "article_concept") { //showArticle
						echo "<td><span style='background: #25aee2;padding: 3px;font-size: 12px;text-transform: uppercase;'>" . t("Saved draft") . "</span></td>";
						echo "<td><a href='#' onClick=\"loadArticle(" . $row["id"] . ");return false;\">" . t("show") . "</a></td>";
					}
					if ($row["type"] == "article_recycled") {
						echo "<td><span style='background: red;padding: 3px;font-size: 12px;text-transform: uppercase;'>" . t("Article recycled") . "</span></td>";
						echo "<td></td>";
					}
					if ($row["type"] == "article_cancel_recycled") {
						echo "<td><span style='background: orange;padding: 3px;font-size: 12px;text-transform: uppercase;'>" . t("Recycling is canceled") . "</span></td>";
						echo "<td></td>";
					}
					echo "</tr>";
				}
				echo "</table></div></div>";
			echo "</div>";
			
			echo "<div class='right-side'>";
				echo "<div class='expandable' id='cat_1'>";
				echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_1', 'closed');return false;\">" . t("Default setting") . "</a></div>";
				echo "<div>";

				if ($result["state"] == 5 || $result["state"] == 0) {
					if (User::isPerm("public") == 0 and $result["state"] == 5)
						echo "<s>" . t("publish article") . "</s> <font color=#888888 title='" . t("you do not have permission to publish articles") . "'>( " . t("without permission") . "! )</font>";
					elseif (Database::getConfig("mainpage") == $result["id"])
					echo "<b>" . t("article published") . "</b><br><font color=#888888>" . t("main article") . "</font>";
					else {
						//echo "<label><input type=checkbox name=public value=1 ".($result["state"] != 5?"checked":"")."> ".t("publish article")."</label>";
						echo "<label><input class=red type='toggle_swipe' name='public' value=1  " . ($result["state"] != 5 ? "checked" : "") . "> " . t("publish article") . "</label>";
					}
					if ($result["state"] == 5) {
						echo "<br><b>" . t("Article not public") . "</b>";
					}
				} else {
					echo "<s>" . t("Publish article") . "</s><br>";
					if ($result["state"] == 4)
						echo "<b><img src='" . Router::url() . "/modules/admin/images/smaz.gif' class=des>" . t("article recycled") . "</b>";
					else
						echo "<b>" . t("State") . ": " . $result["state"] . "</b>";
				}
				echo "<hr><div style='padding: 5px;'><b>".t("Language").": </b>";

				$languages = explode(",", Database::getConfig("languages"));
				$default   = Database::getConfig("default-lang");
				echo "<select id=lang name=lang style='width:150px;' onChange=\"var l = $('#lang option:selected').val(); if(l != '" . $result["language"] . "'){window.location.href='?lang='+l;}\">";
				foreach ($languages as $lng) {
					echo "<option value='" . ($lng == $default ? "" : $lng) . "' " . ($lng == $result["language"] || ($result["language"] == "" && $lng == $default) ? "selected" : "") . ">" . t($lng) . "</option>";
				}
				echo "</select>";
				if ($result["mid"] != $result["id"]) {
					echo "<br><a href='" . $t->router->url_ . "adminv2/article/edit/" . $result["mid"] . "/'>".t("Parent article")."</a>";
				}
				echo "</div>";

				echo "<hr><div style='padding: 5px;'><b>Viditelnost:</b></div>";
				echo "<div class='visibility-admin'>";
				echo "<input type=radio onChange=\"$('#span-vishes').hide();\" name=visiblity value=1 id=vispub " . ($result["visiblity"] == "" ? "checked" : "") . "> <label for=vispub>" . t("public") . "</label>";
				echo "<br><input type=radio onChange=\"$('#span-vishes').hide();\" name=visiblity value=2 id=vishid " . ($result["visiblity"] == "2" ? "checked" : "") . "> <label for=vishid>" . t("private") . "</label>";
				echo "<br><input type=radio onChange=\"if($(this).is(':checked') && $(this).val() == '3'){ $('#span-vishes').css('display','block');$('#vishes-pass').select(); }else{ $('#span-vishes').hide(); }\" name=visiblity value=3 id=vishes " . ($result["visiblity"] != "" && $result["visiblity"] != "2" ? "checked" : "") . "> <label for=vishes>" . t("protected by password") . "</label>";
				echo "<span id='span-vishes' style='display:" . ($result["visiblity"] != "" && $result["visiblity"] != "2" ? "block" : "none") . ";border: 1px solid rgb(222, 219, 219);padding: 4px 4px;font-size: 14px;margin-left: 27px;background: #e4e4e4;margin-bottom: 10px;'>" . t("enter password") . ":<br><input type=text name=vishes-pass id=vishes-pass value='" . ($result["visiblity"] != "" && $result["visiblity"] != "2" ? substr($result["visiblity"], 1) : "") . "' style='width:100%;'></span>";
				echo "</div>";
				echo "<hr style='margin-bottom: 10px;'>";
				echo "<input type=submit onclick='beforeSubmit();' class='btn btn-primary' name=edit value='" . t("save article") . "'> <button style='padding: 6px;' name=edit class='btn btn-warning' onClick=\"saveConcept();return false;\">" . t("save draft") . "</button> <div id='state-info' style='margin-top: 7px;display:none;'></div>";
				echo "</div>";
				echo "</div>";			
				echo "<textarea id='oldtextfromarticle' style='display:none;'>" . $result["text"] . "</textarea>";

				$menus = null;
				$result_ = dibi::query('SELECT * FROM :prefix:menu');
				foreach ($result_ as $n => $row) {
					if (!isset($menus[$row["box"]])) {
						$menus[$row["box"]] = array(0 => true, 1 => 0);
					}
				}

				echo "<div class='expandable closed' id='cat_2'>";
				echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_2', 'closed');return false;\">" . t("Display other menu") . "</a></div>"; //style.enable.custommenu				
				echo "<div>";
				if ($t->root->config->get("style.enable.custommenu") == true) {
					echo "<select id=box name=custommenu style='width:100%;'><option value=''> - " . t("not to use") . " - </option>";
					foreach ($menus as $n => $box) {
						echo "<option value='" . $n . "' " . ($result["custommenu"] == $n ? "selected" : "") . ">" . $n . "</option>";
					}
					echo "</select>";
				} else {
					echo "<span class=error>" . t("template not support custom left menu") . "</span>";
				}
				echo "</div>";
				echo "</div>";

				echo "<div class='expandable closed' id='cat_3'>";
				echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_3', 'closed');return false;\">" . t("Category") . "</a></div>";
				echo "<div>";
				$result_ = dibi::query('SELECT * FROM :prefix:category ORDER BY id');
				echo "<select id=category name=category style='width:100%;'>";
				foreach ($result_ as $n => $row) {
					echo "<option value='" . $row["id"] . "' " . (($result["category"] == $row["id"]) ? "selected" : "") . ">" . $row["name"] . "</option>";
				}
				echo "</select>";
				echo "</div>";
				echo "</div>";

				echo "<div class='expandable closed' id='cat_4'>";
				echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_4', 'closed');return false;\">" . t("Comments") . "</a></div>";
				echo "<div class='visibility-admin'>";
				if (!isset($result["comments"]) or $result["comments"] == "") $result["comments"] = 1;
				echo "<div style='padding:0px 4px;'><input type=radio name=comments value=1 " . ($result["comments"] == 1 ? "checked" : "") . " id=cm1> <label for=cm1>" . t("enable comments") . "</label></div>";
				echo "<div style='padding:0px 4px;'><input type=radio name=comments value=2 " . ($result["comments"] == 2 ? "checked" : "") . " id=cm2> <label for=cm2>" . t("only logged") . "</label></div>";
				echo "<div style='padding:0px 4px;'><input type=radio name=comments value=3 " . ($result["comments"] == 3 ? "checked" : "") . " id=cm3> <label for=cm3>" . t("disable comments") . "</label></div>";
				echo "</div>";
				echo "</div>";

				echo "<div class='expandable closed' id='cat_5'>";
				echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_5', 'closed');return false;\">" . t("Author") . "</a></div>";
				echo "<div>";
				echo "<select id=author name=author class='' style='width:100%;' onChange=\"var parent = $('#'+$(this).attr('parent'));if(parent.attr('value_') == 'custom'){ $('#customname').show();$('#customname').focus(); }else{ $('#customname').hide(); };\">";

				$custom = false;
				$cname = "";
				if (substr($result["author"], 0, 1) == "@") {
					$custom = true;
					$cname = substr($result["author"], 1);
				}
				echo "<option value='custom' " . ($custom ? "selected" : "") . ">" . t("Custom") . "</option>";

				$result_ = dibi::query('SELECT * FROM :prefix:users ORDER BY id');
				foreach ($result_ as $n => $row) {
					echo "<option value='" . $row["id"] . "' " . (($result["author"] == $row["id"] and !$custom) ? "selected" : "") . ">" . $row["nick"] . "</option>";
				}
				echo "</select><input type=text value='" . $cname . "' style='display:" . ($custom ? "block" : "none") . ";width:100%;padding: 6px 12px;border-top:0px;' placeholder='" . t("custom author") . "' name=customname id=customname>";
				echo "<span style='padding:4px;display: inline-block;'><b>" . t("original author") . "</b>: " . User::get($result["oauthor"])["nick"] . "</span>";
				echo "</div>";
				echo "</div>";

				echo "<div class='expandable closed' id='cat_6'>";
				echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_6', 'closed');return false;\">" . t("Tags") . "</a></div>";
				echo "<div>";
				echo "<input type=text autocomplete=off onKeyDown=\"if(event.keyCode == 13){ addTags($('#tags').val());$('#tags').val('');return false; }\" name=tag id=tags style='width: 78%;'> <button onClick=\"addTags($('#tags').val());$('#tags').val('');return false;\" class='btn btn-primary' style='padding: 6px;width: 55px;'>" . t("add") . "</button>";
				echo "<div id=tagsp style='padding: 6px 0px;border-bottom: 1px solid silver;'>";

				echo "</div><span class='info'>" . t("more tags separated by commas") . "<br>Some special tags: <br>";
				echo "<a href=# onClick=\"addTags('form');return false;\">form</a> - hide top and bottom information, autor, tags<br>";
				echo "<a href=# onClick=\"addTags('test');return false;\">test</a> - at top add information this article is used for testing<br>";
				echo "<a href=# onClick=\"addTags('no-header');return false;\">no-header</a> - hidde header name<br>";
				echo "<a href=# onClick=\"addTags('template');return false;\">template</a> - show page as template<br>";
				echo "<a href=# onClick=\"addTags('no-comments');return false;\">no-comments</a> - hide comments";
				echo "</span>";
				echo "<textarea id=tagst name=article_tags style='display:none;'>" . $result["tags"] . "</textarea>";
				echo "</div>";
				echo "</div>";
				//echo "</div>";
				//echo "</div>";

			if (isset($_GET["html"])) {
				$result["html"] = 0;
			}
			echo "</div>";
		
		echo "</div>";
		echo "</form>";
		if($result["html"] == 1){
			echo "<script>$(function(){ setTimeout(function(){switchToHtml(true);}, 100); });</script>";
		}
		?>
		<script>
			$(function(){
				setTimeout(function(){
					actionButton.setText("<?php echo t("Save article"); ?>")
					actionButton.changeIcon("fas fa-save");
					actionButton.show();
					actionButton.onclick(function(){
						$("[name=edit].btn-primary").click();
					});
				}, 100);
			});
		function beforeSubmit(){
			
		}
		function switchToHtml(html){			
			//$('#htmlonly').show();						
			if(html === true){
				$('#htmlonly').val($("#oldtextsaved").val());
			}else{
				$('#htmlonly').val(tinymce.activeEditor.getContent());
			}
			tinymce.remove('#intereditor');
			$('#htmlonlyval').val(1);
			try{
				tinymce.activeEditor.hide();
				tinymce.activeEditor.getDoc().designMode = 'Off';									
			}catch{}
		}
		function switchToEditor(html){			
			if($("#htmlonlyval").val() == 1) {
				window.location.href = "?lang=<?php echo $_GET["lang"]; ?>&html=0";
			}else{
				tinymce.activeEditor.show();
				try{tinymce.activeEditor.setContent($('#htmlonly').val());}catch(e){}
				tinymce.activeEditor.getDoc().designMode = 'On';
				$('#htmlonlyval').val(0);
			}
		}
		$(function(){
			$("#intereditor").on("keyup", function(){
				if($("#htmlonlyval").val() != 1) {
					var text = $(this).val();
					tinymce.activeEditor.setContent(text);
					tinymce.activeEditor.getDoc().designMode = 'Off';				
				}
			});
		});
		function loadArticle(id){
			var d = new Dialog();
			d.setTitle('<?php echo t("article preview from history"); ?>');
			d.setButtons([Dialog.CLOSE]);
			d.Load('<?php echo $t->router->url."adminv2/article/edit/".$t->router->_data["id"][0]; ?>', "__type=ajax&showArticle="+id);
			butt = d.getButtons();
			$(butt[0]).click(function(){ d.Close(); });
		}
		function removeTag(a){
			var t = $("#tagst").val();
			var p = t.split(",");
			var q = "";
			for(var i = 0;i < p.length; i++){
				if(i != a){
					if(q != "") q+=",";
					q+=p[i];
				}
			}
			$("#tagst").val(q);
			drawTags();
		}
		function drawTags(){
			var t = $("#tagst").val();
			if(t == "")
				$("#tagsp").html("<span style='color:silver;'><?php echo t("no tags"); ?></span>");
			else
				$("#tagsp").html("");
			var p = t.split(",");
			for(var i = 0;i < p.length; i++){
				if(p[i].trim() != ""){
					$("#tagsp").append("<span class='adm_tag animate'><a href=# onClick='removeTag("+i+");return false;' class='delete' style='top: 3px;position: relative;background-size: 14px;width: 13px1;'></a> "+p[i]+"</span>");
				}
			}
		}
		function addTags(tags){
			var t = $("#tagst").val();
			var p = tags.split(",");
			for(var i = 0;i < p.length; i++){
				if(p[i].trim() != ""){
					if(t != "") t+=",";
					t+=p[i].trim();
				}
			}
			$("#tagst").val(t);
			drawTags();
		}
		drawTags();

		$(function(){
			var textDraft = Cookies.get("article_save_text_<?php echo $result["id"]; ?>");
			if(textDraft != null && textDraft != "") {
				$("#state-info").show();
				$("#state-info").html("<span class='sbox ok'><a href=# onclick='loadConceptFromCookies();return false;'><?php echo t("Load draft from cache") ?></a></span>");
			}
		});

		function loadConceptFromCookies(){
			var textDraft = Cookies.get("article_save_text_<?php echo $result["id"]; ?>");

			if($("#htmlonlyval").val() == 1)
				$("#intereditor").val(textDraft);
			else
				tinymce.activeEditor.setContent(textDraft);
		}

		//state-info
		var _text = "";
		$(function(){
			setTimeout(function(){
				if($("#htmlonlyval").val() == 1)
					_text = $("#intereditor").val();
				else
					_text = tinymce.activeEditor.getContent();
			}, 1000);
		});

		function callTimer(){
			setTimeout(function(){ saveConcept(); }, 60000);
		}
		function saveConcept(){
			if($("#htmlonlyval").val() == 1)
				_text = $("#intereditor").val();
			else
				_text = tinymce.activeEditor.getContent();

			if($("#oldtextfromarticle").val() != _text){
				Cookies.set("article_save_text_<?php echo $result["id"]; ?>", _text);

				$("#state-info").show();
				$("#state-info").html("<span class='loading small'></span> <?php echo t("saving")."..."; ?>");
				ajaxcall_draw("<?php echo $t->router->url_."adminv2/article/edit/".$result["id"]."?__type=ajax&saveConcept"; ?>", {text: _text}, "#state-info", 
					function(text){
						Cookies.remove("article_save_text_<?php echo $result["id"]; ?>");
						callTimer();
					});
				$("#oldtextfromarticle").val(_text);
			}else{
				callTimer();
			}
		}
		callTimer();
		</script>
		<?php
	}
}
