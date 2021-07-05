<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];

if($action == "show"){
	$action = "form";
}
$user = User::current();

if($_GET["__type"]!="ajax"){
	echo "<ul class='menu sub'>";
		echo "<li ".($action == "form"?"class=select":"")."><a href='".$t->router->url_."adminv2/content/'>".t("Forms")."</a></li>";
		echo "<li ".($action == "generators"?"class=select":"")."><a href='".$t->router->url_."adminv2/content/generators/'><s>".t("Generators")."</s></a></li>";
		echo "<li ".($action == "pages"?"class=select":"")."><a href='".$t->router->url_."adminv2/content/pages/'><s>".t("Pages")."</s></a></li>";
	echo "</ul>";
}

if($action == "generators"){
	if($who == "delete"){
		dibi::query('DELETE FROM :prefix:generators WHERE `id`=%s', $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/content/generators/");
	}
	elseif($who == "new"){
		$data = array(
					"name" 		=> t("new generator"),
					"date"		=> time(),
					"user"		=> $user["id"],
					"alias" 	=> "new-generator"
				);
		$result = dibi::query('INSERT INTO :prefix:generators', $data);
		header("location:".$t->router->url_."adminv2/content/generators-edit/".dibi::InsertId());
	}
	elseif($who == "null"){
		echo "<div class=bottommenu><a href='".Router::url()."adminv2/content/generators-new/'>".t("new generator")."</a></div>";
		echo "<table class='tablik'>";
		echo "<tr><th width=350>".t("title")."</th><th width=150>".t("author")."</th><th width=120>".t("action")."</th></tr>";
		$result = dibi::query('SELECT * FROM :prefix:generators');
		foreach ($result as $n => $row) {
			echo "<tr><td><b>".$row["name"]."</b> <i>".Strings::str_time($row["date"])."</i></td><td>";
			$user = User::get($row["user"]);
			echo "".$user["nick"]."</td><td>";
				echo "<a href='".$t->router->url_."adminv2/content/generators-edit/".$row["id"]."'>".t("edit")."</a> <a href='".$t->router->url_."adminv2/content/generators-delete/".$row["id"]."'>".t("delete")."</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}elseif($who == "edit"){
		$result = dibi::query("SELECT * FROM :prefix:generators WHERE id=%i", $t->router->_data["id"][0])->fetch();
		if($result == NULL){
			$t->root->page->draw_error("Generator neexistuje", "Generator ".$t->router->_data["id"][0]." neexistuje!");
		}else{
			if($_GET["__type"] == "ajax"){
				if(isset($_GET["editform"])){
					$data = array(
								"name" => $_GET["name"]
							);

					$q = dibi::query('UPDATE :prefix:generators SET ', $data, 'WHERE `id`=%s', $t->router->_data["id"][0]);
					if($q or $q==0) echo t("saved");
					else echo "Error in saving!";
				}
			}else{
				if($t->router->_data["state"][0] == "ok"){
					$t->root->page->error_box(t("the generators has been edited"), "ok");
				}
				echo "<div style='padding:8px;'>";
				echo "<div style='margin-bottom:5px;'>Generátor <form id=formedit action=# method=post style='display:inline;'><input type=text name=name value='".$result["name"]."'> <button class=blue onClick=\"ajaxsend('#formedit', this, '#showrrf', '".$t->router->url_."admin/content/generators-edit/".$t->router->_data["id"][0]."/?__type=ajax&editform');return false;\">".t("save")."</button> <span style='displa:none;color:red;' id='showrrf'></span></form> <a href='#' onClick=\"showhide('#properties');return false;\">Show/Hide Properties</a></div>";
				echo "<div style='float:left;width:700px;background:white;'><div id='gen-data'><textarea id='generator-data'>".$result["data"]."</textarea><br>Pokud zde vidíte tento text zřejmě máte vypnutý JavaScrip!</div><div id='gen-content'></div></div>";
				echo "<div style='float:right;width:300px;background:white;padding:10px;' id='properties'>";
					echo "<b>Properties</b>";
					echo "<div id='generator-properties' class=board></div>";
				echo "</div>";
				echo "</div>";

				echo "<script>generatorPreview('#gen-data', '#gen-content', '');</script>";
			}
		}
	}
}
elseif($action == "form"){
	if($who == "delete"){
		dibi::query('DELETE FROM :prefix:form WHERE `id`=%s', $t->router->_data["id"][0]);
		header("location:".$t->router->url_."adminv2/content/");
	}
	elseif($who == "new"){
		$data = array(
					"name" 		=> t("new form"),
					"date"		=> time(),
					"user"		=> $user["id"]
				);
		$result = dibi::query('INSERT INTO :prefix:form', $data);
		header("location:".$t->router->url_."adminv2/content/form-edit/".dibi::InsertId());
	}
	elseif($who == "settings"){
		echo "<div class=bottommenu><a href='".Router::url()."adminv2/content/form-new/'>".t("New form")."</a>";
		echo " | <a href='".Router::url()."adminv2/content/form-styles/'>".t("Edit styles")."</a>";
		echo " | <b>".t("Settings")."</b></div><br>";

		if(isset($_POST["update"])){
			$pole = array(
						"recaptcha-key" => $_POST["recaptcha-key"],
						"recaptcha-secret-key" => $_POST["recaptcha-secret-key"]
					);

			foreach($pole as $key => $value){
				dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
				$t->root->config->set($key, $value);
			}
			$t->root->page->error_box(t("the changes have been saved."), "ok");
		}
		echo "<form method=post><table class=tabfor style='width:700px;'>";
		echo "<tr><td width=170><label>".t("ReCaptcha site key")."</label></td><td width=430><input type=text name='recaptcha-key' value='".$t->root->config->get("recaptcha-key")."'></td><td width=20></td></tr>";
		echo "<tr><td width=170><label>".t("ReCaptcha secret site key")."</label></td><td width=430><input type=text name='recaptcha-secret-key' value='".$t->root->config->get("recaptcha-secret-key")."'></td><td width=20></td></tr>";
		echo "<tr><td></td><td><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></td></tr>";
		echo "</table>";
		echo "</form>";
	}
	elseif($who == "null"){
		echo "<div class=bottommenu><a href='".Router::url()."adminv2/content/form-new/'>".t("New form")."</a>";
		echo " | <a href='".Router::url()."adminv2/content/form-styles/'>".t("Edit styles")."</a>";
		echo " | <a href='".Router::url()."adminv2/content/form-settings/'>".t("Settings")."</a></div>";
		echo "<table class='table table-sm tablik'>";
		$orderBy = Utilities::orderBy(array("f.date" => t("Created"), "answers" => t("Answers")));
		echo "<tr><th width=350>".t("Title")." ".$orderBy["html"]."</th><th width=120>".t("Action")."</th></tr>";
		$result = dibi::query('SELECT f.id, f.date, f.parent, f.name, count(a.id) answers FROM :prefix:form as f LEFT JOIN :prefix:form_answer as a ON f.id = a.parent', " WHERE f.parent = %s", "","GROUP BY f.id ORDER BY ".$orderBy["orderby"]." DESC");
		foreach ($result as $n => $row) {
			echo "<tr><td><b>".$row["name"]."</b> <i>".Strings::str_time($row["date"])."</i> <span style='font-size:12px;padding-left:10px;'>(".t("answers").": ".($row["answers"]).")</span></td><td>";
				echo "<a href='".$t->router->url_."adminv2/content/form-answer/".$row["id"]."'><i class=\"far fa-eye\"></i> ".t("answers")."</a> <a href='".$t->router->url_."adminv2/content/form-edit/".$row["id"]."'><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a> <a href='".$t->router->url_."adminv2/content/form-delete/".$row["id"]."'><i class=\"far fa-trash-alt\"></i> ".t("delete")."</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}
	else if($who == "view"){
		$result = dibi::query("SELECT * FROM :prefix:form_answer WHERE id=%i", $t->router->_data["id"][0])->fetch();
		if($result == NULL){
			$t->root->page->draw_error(t("Answer not a found"), t("Answer")." ".$t->router->_data["id"][0]." ".t("not exists")."!");
		}else{
			$resulf = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $result["parent"])->fetch();
			echo "<h1>".t("Answer to the form")." \"".$resulf["name"]."\"</h1>";

			echo "<div style='float:right;width: 300px;'>";
				echo "<div class=title_admin>".t("Information")."</div>";
				echo "<div style=''>";
					echo "<a href='".Router::url()."adminv2/content/form-edit/".$result["parent"]."'>".t("Back to the editing form")."</a><hr>";
					echo "<b>Odesílatel</b><br>";
					$userIs = User::find("fanswer", $result["user"]);
					if($userIs != false)
						echo "Registrován uživatel <b><a title='".t("Edit user")."' href='".Router::url()."adminv2/users/edit/".$userIs["id"]."/' title='".t("edit")."'>".$userIs["nick"]." <i class=\"fas fa-pencil-alt\"></i></a></b>";
					else
						echo ($result["user"]==-1?"<i style='color:silver;'>".t("Not logged in")."</i>":"<a href='".Router::url()."adminv2/users/edit/".$result["user"]."/' title='".t("edit")."'>".User::get($result["user"])["nick"])." <i class=\"fas fa-pencil-alt\"></i></a>";
					echo "<br>";
					echo "<b>".t("Ip address")."</b><br>".$result["ip"]."<br>";

					echo "<b>".t("Browser")."</b>";
					echo "<br>";
					$brw = Utilities::get_browser_properties();
					echo $brw["browser"]." (".$brw["version"].") ";
					echo "<a href=# onclick=\"$('#brwdat').toggle();\" title='Zobrazit data'><i class=\"far fa-eye\"></i></a><br>";
					echo "<textarea id=brwdat style='display:none'>".$result["browser"]."</textarea><hr>";
					echo "<b>".t("time of sending")."</b>: ".Strings::str_time($result["time"])."<br>";
					echo "<b>".t("sent through")."</b>: ".$result["submit"]."<br>";
				echo "</div>";
			echo "</div>";

			echo "<table class='tablik' style='width:70%;'>";
				$data = Config::sload($result["data"]);

				for($i=0;$i<count($data);$i++){
					echo "<tr><th width=300 valign=top>".$data[$i][0]."</th>";
					if($data[$i][1] == "select"){
						$c = explode("[;", $data[$i][2]);
						echo "<td>".implode("<br>",$c)."</td>";
					}else if($data[$i][1] == "upload"){
						echo "<td><i class=\"fas fa-link\"></i> <a href='".$data[$i][2]."' target=_blank title='".$data[$i][2]."'>Přejít na soubor</a></td>";
					}else if($data[$i][1] == "picker"){
						$c = explode("[;", $data[$i][2]);
						$d = explode("[,", $c[1]);
						echo "<td>";
							echo "<b>Vybráno</b><br>".$d[0]."<br>".$d[1]."<br><span style='font-size:11px;'>Minimální oprávnění: <span style='color:".User::permission($d[2])["color"].";'>".User::permission($d[2])["name"]."</span></span>";
						echo "</td>";
					}else{
						if(User::find("fanswer", $result["parent"]) != false and $data[$i][1] == "password"){
							echo "<td><i>".str_repeat("*",$data[$i][2])."</i></td>";
						}else{
							echo "<td>".$data[$i][2]."</td>";
						}
					}
					echo "</tr>";
				}
			echo "</table>";

			echo "<div style='clear:both;'></div>";
		}
	}else if($who == "answer"){
		$result = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $t->router->_data["id"][0])->fetch();
		echo "<h1>".t("form answer")." \"".$result["name"]."\"</h1>";
		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=110>".t("Date")."</th><th width=100>".t("Ip")."</th><th width=130>".t("User")."</th><th width=100>".t("Sent")."</th><th width=200>".t("Browser")."</th><th width=120>".t("Action")."</th></tr>";
		$result = dibi::query('SELECT * FROM :prefix:form_answer WHERE parent = %i', $t->router->_data["id"][0], " ORDER BY id DESC");
		foreach ($result as $n => $row) {
			echo "<tr><td><i>".Strings::str_time($row["time"])."</i></td>";
			echo "<td>".$row["ip"]."</td>";
			if(User::find("fanswer", $row["id"]) != false)
				echo "<td>Registrován <b>".User::find("fanswer", $row["id"])["nick"]."</b></td>";
			else
				echo "<td>".($row["user"]==-1?"<i style='color:silver;'>".t("not logged in")."</i>":User::get($row["user"])["nick"])."</td>";
			echo "<td>".$row["submit"]."</td>";
			echo "<td><span style='display:inline-block;width:278px; white-space: nowrap; overflow:hidden !important;text-overflow: ellipsis;'>".($row["browser"] == ""?"<i title='".t("not working")."'>---</i>":$row["browser"])."</span></td>";
			echo "<td>";
				echo "<a href='".$t->router->url_."adminv2/content/form-view/".$row["id"]."'>".t("show")."</a> <a href='".$t->router->url_."adminv2/content/form-answer/".$t->router->_data["id"][0]."/?delete=".$row["id"]."'>".t("delete")."</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}else if($who == "edit"){
		if(isset($_GET["item"])){
			if(isset($_GET["dlt"])){
				dibi::query('DELETE FROM :prefix:form_items WHERE `id`=%s', $_GET["item"]);
			}
			else if(isset($_GET["editform"])){
				$data = array(
							"redirect" => $_GET["redirect"],
							"enable" => (isset($_GET["enable"])?1:0),
							"onetime" => (isset($_GET["onetime"])?1:0),
							"width" => $_GET["width"],
							"succme" => str_replace("_R_N_", "\r\n", str_replace("_HASH_", "#", $_GET["succme"]))
						);
				$arr = array(
						"name" 	=> $_GET["name"],
						"data" 	=> Config::ssave($data)
					);

				$q = dibi::query('UPDATE :prefix:form SET ', $arr, 'WHERE `id`=%s', $t->router->_data["id"][0]);
				if($q or $q==0) echo t("saved");
				else echo "Error in saving!";
			}
			elseif(isset($_GET["savepos"])){
				$data = $_GET["data"];
				$data = explode(";", $data);
				for($i=0;$i<count($data);$i++){
					$arr = array("position" => $i);
					$q = dibi::query('UPDATE :prefix:form_items SET ', $arr, 'WHERE `id`=%s', $data[$i]);
				}
			}
			elseif(isset($_GET["save"])){
				$result = dibi::query("SELECT * FROM :prefix:form_items WHERE id=%i", $_GET["item"])->fetch();
				$sdata = Config::sload($result["data"]);
				$data = array(
							"cssclass" 		=> $_GET["css_class"]
						);
				
				if($_GET["type"] == "upload"){
					$data["position"] 		= $_GET["nameplace"];
					$data["state"] 			= $_GET["reque"];
					$data["folder"]			= $_GET["folder"];
					$data["maxsize"] 		= $_GET["maxsize"];
					$data["allowed"] 		= $_GET["allowed"];
					$data["resize"] 		= (isset($_GET["resize"])?1:0);
					$data["resizew"] 		= $_GET["resizew"];
					$data["resizeh"] 		= $_GET["resizeh"];
					$arr = array(
						"name" 	=> $_GET["name"],
						"data" 	=> Config::ssave($data)
					);
				}else if($_GET["type"] == "textbox" or $_GET["type"] == "password"){
					$data["position"] 		= $_GET["nameplace"];
					$data["placeholder"] 	= str_replace("_ADN_D_", "&", str_replace("_QES_", "=", str_replace("_HASH_", "#", str_replace("_R_N_", "\r\n", $_GET["placeholder"]))));
					$data["state"] 			= $_GET["reque"];
					$data["customvalue"] 	= $_GET["rules"];
					$data["asemail"] 		= (isset($_GET["asemail"])?1:0);
					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => str_replace("_ADN_D_", "&", str_replace("_QES_", "=", str_replace("_HASH_", "#", str_replace("_R_N_", "\r\n", $_GET["value"])))),
						"data" 	=> Config::ssave($data)
					);
				}else if($_GET["type"] == "text"){
					$data["position"] 		= $_GET["nameplace"];
					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => str_replace("_ADN_D_", "&", str_replace("_QES_", "=", str_replace("_HASH_", "#", str_replace("_R_N_", "\r\n", $_GET["text"])))),
						"data" 	=> Config::ssave($data)
					);
				}else if($_GET["type"] == "textarea"){
					$data["position"] 		= $_GET["nameplace"];
					$data["placeholder"] 	= $_GET["placeholder"];
					$data["state"] 			= $_GET["reque"];
					$data["rows"]			= $_GET["rows"];
					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => str_replace("_HASH_", "#", str_replace("_R_N_", "\r\n", $_GET["text"])),
						"data" 	=> Config::ssave($data)
					);
				}else if($_GET["type"] == "picker"){
					$data["position"] 		= $_GET["nameplace"];
					$data["state"] 			= $_GET["reque"];
					$data["displayas"]      = $_GET["displayas"];
					$data["size"]  			= $_GET["size"];

					$data["items"] = "";
					$i=0;$a=0;
					while(isset($_GET["item_".$i."_name"])){
						if(isset($_GET["deleteitem"]) and $_GET["deleteitem"] == $i){ $i++;continue; }
						if($a!=0) $data["items"].="[;";
						$data["items"].=$_GET["item_".$i."_name"]."[,".$_GET["item_".$i."_value"]."[,".($_GET["item_".$i."_minperm"])."[,".$_GET["item_".$i."_max"];
						$i++;
						$a++;
					}

					if(isset($_GET["additem"])){ if($data["items"]!=""){ $data["items"].="[;"; }  $data["items"].="NP[,Nová položka[,0[,0"; }

					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => "",
						"data" 	=> Config::ssave($data)
					);
				}else if($_GET["type"] == "select"){
					$data["position"] 		= $_GET["nameplace"];
					$data["state"] 			= $_GET["reque"];
					$data["types"] 			= $_GET["types"];
					$data["custom"] 		= (isset($_GET["custom"])?1:0);
					if(isset($_GET["place"])){ $data["place"] = $_GET["place"]; }

					$data["items"] = "";
					$i=0;$a=0;
					while(isset($_GET["item_".$i."_name"])){
						if(isset($_GET["deleteitem"]) and $_GET["deleteitem"] == $i){ $i++;continue; }
						if($a!=0) $data["items"].="[;";
						if(($data["types"]=="4" or $data["types"]=="1"))
							$s = (($_GET["item_".$_GET["item"]."_selected"] == $i)?1:0);
						else
							$s = (isset($_GET["item_".$_GET["item"]."_".$i."_selected"])?1:0);
						$data["items"].=$_GET["item_".$i."_name"]."[,".$_GET["item_".$i."_value"]."[,".($s);
						$i++;
						$a++;
					}

					if(isset($_GET["additem"])){ if($data["items"]!=""){ $data["items"].="[;"; }  $data["items"].="Nová položka[,[,0"; }

					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => "",
						"data" 	=> Config::ssave($data)
					);
				}elseif($_GET["type"] == "recaptcha"){
					$data["position"] 		= $_GET["nameplace"];
					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => "",
						"data" 	=> Config::ssave($data)
					);
				}elseif($_GET["type"] == "variable"){
					$data["list"]			= str_replace("_HASH_", "#", $_GET["list"]);
					$data["next"]			= $_GET["next"];
					$data["stop"]			= $_GET["stop"];
					$data["closeatstop"]	= (isset($_GET["closeatstop"])?1:0);
					$data["stopat"]			= $_GET["stopat"];
					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => $_GET["value"],
						"data" 	=> Config::ssave($data)
					);
				}else{
					$arr = array(
						"name" 	=> $_GET["name"],
						"value" => "",
						"data" 	=> Config::ssave($data)
					);
				}
				$q = dibi::query('UPDATE :prefix:form_items SET ', $arr, 'WHERE `id`=%s', $_GET["item"]);
				if($q or $q==0) echo "Uloženo";
				else echo "Error in saving!";
			}else{
				$result = dibi::query("SELECT * FROM :prefix:form_items WHERE id=%i", $_GET["item"])->fetch();
				if($result == NULL){
					echo "<b>".t("item does not exist")."!</b>";
				}else{
					$id = $_GET["item"];
					$data = Config::sload($result["data"]);
					if(!isset($data["position"])) $data["position"] = 1;
					if(!isset($data["placeholder"])) $data["placeholder"] = "";
					if(!isset($data["state"])) $data["state"] = 0;
					if(!isset($data["cssclass"])) $data["cssclass"] = "";
					if(!isset($data["text"])) $data["text"] = "";
					if(!isset($data["customvalue"])) $data["customvalue"] = "[]";
					if(!isset($data["types"])) $data["types"] = 1;
					if(!isset($data["items"])) $data["items"] = "";
					if(!isset($data["custom"])) $data["custom"] = 0;
					if(!isset($data["place"])) $data["place"] = 0;
					if(!isset($data["asemail"])) $data["asemail"] = 0;
					if(!isset($data["list"])) $data["list"] = "";
					if(!isset($data["next"])) $data["next"] = "";
					if(!isset($data["stop"])) $data["stop"] = "";
					if(!isset($data["stopat"])) $data["stopat"] = "";
					if(!isset($data["closeatstop"])) $data["closeatstop"] = "";
					if(!isset($data["displayas"])) $data["displayas"] = "row";
					if(!isset($data["size"])) $data["size"] = 48;
					if(!isset($data["rows"])) $data["rows"] = 5;
					if(!isset($data["folder"])) $data["folder"] = "";
					if(!isset($data["maxsize"])) $data["maxsize"] = "1048576";
					if(!isset($data["allowed"])) $data["allowed"] = "gif,png,jpg";
					if(!isset($data["resize"])) $data["resize"] = 0;
					if(!isset($data["resizew"])) $data["resizew"] = 1024;
					if(!isset($data["resizeh"])) $data["resizeh"] = 1024;

					echo "<form action=# method=post id='form_item_".$id."' onSubmit='return false;'>";
					echo "<table style='margin:0px auto;'>";
					if($result["type"] == "variable"){
						echo "<tr><td width=200>".t("Name")."</td><td><input style='width:400px;' type=text name='name' value='".$result["name"]."'></td>";
						echo "<tr><td width=200>".t("Value")."</td><td><input style='width:400px;' type=text name='value' value='".$result["value"]."'></td>";
						echo "<tr><td width=200></td><td><span class=desc>".t("You can use input value by")." <span style='border: 1px solid silver;padding: 2px 2px;background: white;color: black;'>{#".$result["name"]."}</span></span></td>";
						echo "<tr><td width=200>".t("List")."</td><td><input style='width:400px;' type=text name='list' value='".$data["list"]."'></td>";
						echo "<tr><td width=200></td><td><span class=desc>".t("Here put list exploded by")." ,</span></td>";
						echo "<tr><td colspan=4><hr></td></tr>";
						echo "<tr><td width=200>".t("Next")."</td><td><label><input type=radio name='next' value='1' ".($data["next"]=="1"?"checked":"")."> ".t("Increment by")." 1</label></td></tr>";
						echo "<tr><td></td><td><label><input type=radio name='next' value='2' ".($data["next"]=="2"?"checked":"")."> ".t("Decrement by")." 1</label></td></tr>";
						echo "<tr><td></td><td><label><input type=radio name='next' value='3' ".($data["next"]=="3"?"checked":"")."> ".t("Next in list")." <span class=desc>".t("After end start from first")."</span></label></td></tr>";
						echo "<tr><td></td><td><label><input type=radio name='next' value='4' ".($data["next"]=="4"?"checked":"")."> - ".t("nothing")." -</label></td></tr>";
						echo "<tr><td colspan=4><hr></td></tr>";
						echo "<tr><td width=200>".t("Stop")."</td><td><label><input type=radio name='stop' value='1' ".($data["stop"]=="1"?"checked":"")."> ".t("Stop at end of list")."</label></td></tr>";
						echo "<tr><td></td><td><label><input type=radio name='stop' value='2' ".($data["stop"]=="2"?"checked":"")."> ".t("Stop at number")." <input type=text name=stopat value='".$data["stopat"]."'></label></td></tr>";
						echo "<tr><td></td><td><label><input type=radio name='stop' value='3' ".($data["stop"]=="3"?"checked":"")."> - ".t("never")." -</label></td></tr>";
						echo "<tr><td colspan=4><hr></td></tr>";
						echo "<tr><td></td><td><label style='padding-top: 7px;'><input type=toggle_swipe name='closeatstop' value='1' ".($data["closeatstop"]=="1"?"checked":"")."> ".t("When stoped close the form")."</label></td></tr>";
						echo "<tr><td colspan=4><hr></td></tr>";
					}else{
						echo "<tr><td width=200>".t("Name")."</td><td><input style='width:400px;' type=text name='name' value='".$result["name"]."'></td>";
						if($result["type"] == "upload" or $result["type"] == "textbox" or $result["type"] == "password" or $result["type"] == "text" or $result["type"] == "textarea" or $result["type"] == "select" or $result["type"] == "picker" or $result["type"] == "recaptcha")
							echo "<td>".t("Position")."</td><td><select id=place name='nameplace'><option value=1 ".($data["position"]=="1"?"selected":"").">".t("left")."</option><option value=2 ".($data["position"]=="2"?"selected":"").">".t("top")."</option><option value=5 ".($data["position"]=="5"?"selected":"").">".t("hidden")."</option></select></td>";
						echo "</tr>";
						if($result["type"] == "text"){
							echo "<tr><td valign=top>".t("Formatted text")."</td><td><textarea style='width:400px;' type=text class='tinimce_mini' name='text' rows=5>".$result["value"]."</textarea></td></tr>";							
						}
						if($result["type"] == "textarea"){
							echo "<tr><td valign=top>".t("Text")."</td><td><textarea style='width:400px;' type=text name='text' rows='".$data["rows"]."'>".$result["value"]."</textarea></td></tr>";
							echo "<tr><td valign=top>".t("Rows")."</td><td><input style='width:40px;' type='text' name=rows value='".$data["rows"]."'></td></tr>";
						}
						if($result["type"] == "textbox" or $result["type"] == "password"){
							echo "<tr><td>".t("value")."</td><td><input style='width:400px;' type=text name='value' value='".$result["value"]."'></td></tr>";
							echo "<tr><td></td><td>";
								if($data["customvalue"] == "") $data["customvalue"] = "[]";
								echo "<textarea id='rules_".$result["id"]."' name='rules' style='display:none;'>".$data["customvalue"]."</textarea>";
								echo "<div id='rules_".$result["id"]."_cont'></div>";
								echo "<a href=# class=bluecol onClick=\"ruleAdd('#rules_".$result["id"]."');return false;\">Add rule</a>";
								echo "<script>ruleRedraw('#rules_".$result["id"]."');</script>";
							echo "</td></tr>";
						}
						if($result["type"] == "textbox" or $result["type"] == "password")
							echo "<tr><td>".t("Placeholder")."</td><td><input style='width:400px;' type=text name='placeholder' value='".$data["placeholder"]."'></td></tr>";
						if($result["type"] == "textarea")
							echo "<tr><td valign=top>".t("Placeholder")."</td><td><textarea style='width:400px;' rows='".$data["rows"]."' name='placeholder'>".$data["placeholder"]."</textarea></td></tr>";
						if($result["type"] == "textbox")
							echo "<tr><td></td><td><label><input type=toggle_swipe name='asemail' value='1' ".($data["asemail"]=="1"?"checked":"")."> ".t("this field is for email")."</label></td></tr>";
						if($result["type"] == "select"){
							echo "<tr><td>".t("Type")."</td><td><select id='select_types' name='types' style='width:400px;'><option value=1 ".($data["types"]=="1"?"selected":"").">Select</option><option value=2 ".($data["types"]=="2"?"selected":"").">Multi-select</option><option value=3 ".($data["types"]=="3"?"selected":"").">Checkbox</option><option value=4 ".($data["types"]=="4"?"selected":"").">Radio</option></select></td>";
								if($data["types"]=="3" or $data["types"]=="4"){
									echo "<td>".t("Variety")."</td><td><select name='place' id='place'><option value=1 ".($data["place"]=="1"?"selected":"").">".t("One line")."</option><option value=2 ".($data["place"]=="2"?"selected":"").">".t("multi line")."</option></select></td>";
								}
							echo "</tr>";
							echo "<tr><td></td><td colspan=3>";
								echo "<button class='btn btn-primary btn-sm' onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save&additem','".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."', '#edit_item_".$result["id"]."');return false;\">".t("add item")."</button>";
								if($data["items"] == ""){
									echo "<div class=noitem>".t("No item")."</div>";
								}else{
									echo "<ul style='list-style: none;padding: 0px;margin: 5px 0px;'>";
									echo "<li class=litable><table style='width:100%;'><tr>";
											echo "<td></td>";
											echo "<td style='width: 230px;padding-left: 18px;'>".t("Name")."</td>";
											echo "<td>".t("Value")."</td>";
									echo "</tr></table></li>";
									$items = explode("[;", $data["items"]);
									for($l=0;$l<count($items);$l++){
										$dtvl = explode("[,", $items[$l]);
										echo "<li class=litable><table><tr>";
											echo "<td><span class=delete onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save&deleteitem=".$l."','".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."', '#edit_item_".$result["id"]."');return false;\"></span></td>";
											echo "<td><input style='width: 210px;' type=text name=item_".$l."_name value='".$dtvl[0]."'></td>";
											echo "<td><input style='width: 210px;' type=text name=item_".$l."_value value='".$dtvl[1]."'></td>";
											echo "<td><td><input type='".(($data["types"]=="4" or $data["types"]=="1")?"radio":"toggle_swipe")."' name='".(($data["types"]=="4" or $data["types"]=="1")?"item_".$id."_selected":"item_".$id."_".$l."_selected")."' id=item_".$id."_".$l."_selected value='".(($data["types"]=="4" or $data["types"]=="1")?$l:"1")."' ".($dtvl[2]==1?"checked":"")."><label style='margin: 5px;' for='item_".$id."_".$l."_selected'> ".t("selected")."</label></td>";
										echo "</tr></table></li>";
									}
									echo "</ul>";
								}
							echo "</td></tr>";
							echo "<tr><td></td><td><label><input type=toggle_swipe name='custom' value='1' ".($data["custom"]=="1"?"checked":"")."> ".t("allow custom entry")."</label></td></tr>";
						}
						if($result["type"] == "picker"){
							echo "<tr><td>Zobrazit jako</td><td><input type=radio name='displayas' value='row' ".($data["displayas"]=="row"?"checked":"")."> Řádky</td></tr>";
							echo "<tr><td></td><td><input type=radio name='displayas' value='cells' ".($data["displayas"]=="cells"?"checked":"")."> Sloupce";
								if($data["displayas"]=="cells") echo " <input style='width:50px;' type=text name='size' value='".$data["size"]."'> px";
								else  echo "<input type=hidden name='size' value='".$data["size"]."'>";
							echo "</td></tr>";
							echo "<tr><td></td><td colspan=3>";
								echo "<div><button class='btn btn-primary btn-sm' onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save&additem','".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."', '#edit_item_".$result["id"]."');return false;\">".t("add item")."</button></div>";
								if($data["items"] == ""){
									echo "<div class=noitem>".t("no item")."</div>";
								}else{
									$size = $data["size"];
									if(!isset($_GET["selecteditem"])) $selected = -1; else $selected = $_GET["selecteditem"];
									$items = explode("[;", $data["items"]);
									for($l=0;$l<count($items);$l++){
										$dtvl = explode("[,", $items[$l]);
										if($selected != $l){
											echo "<input type=hidden name=item_".$l."_name value='".$dtvl[0]."'>";
											echo "<input type=hidden name=item_".$l."_value value='".$dtvl[1]."'>";
											echo "<input type=hidden name=item_".$l."_minperm value='".$dtvl[2]."'>";
											echo "<input type=hidden name=item_".$l."_max value='".$dtvl[3]."'>";
										}else{ $selecteddata = $dtvl; }
										if($data["displayas"]=="row"){
											echo "<div class='itempick ".($selected == $l?"sel":"")."' onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save','".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&selecteditem=".$l."', '#edit_item_".$result["id"]."');return false;\">".$dtvl[0]." <span class=desc>".$dtvl[1]."</span></div>";
										}else{
											echo "<div class='itempick box ".($selected == $l?"sel":"")."' style='width:".$size."px;height:".$size.";float:left;' onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save','".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&selecteditem=".$l."', '#edit_item_".$result["id"]."');return false;\" title='".$dtvl[1]."'><span class=text>".$dtvl[0]."</span></div>";
										}
									}
									echo "<div style='clear:both;'></div>";

									if($selected != -1){
										echo "<table>";
										echo "<tr><td width=150>".t("Name")."</td><td><input type=text style='width:100%;' name=item_".$selected."_name value='".$selecteddata[0]."'></td></tr>";
										echo "<tr><td>".t("Description")."</td><td><input type=text style='width:100%;' name=item_".$selected."_value value='".$selecteddata[1]."'></td></tr>";
										echo "<tr><td>Minimum oprávnění</td><td>";
											//<input type=text name=item_".$selected."_minperm value='".$selecteddata[2]."'>";
											echo "<select id=permission name=item_".$selected."_minperm style='width:300px;'>";
											$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
											foreach ($resul_ as $n => $row) {
												echo "<option value='".$row["id"]."' ".($row["id"] == $selecteddata[2]?"selected":"").">".$row["name"]." (Level: ".$row["level"].")</option>";
											}
											echo "<option value=0  ".(0 == $selecteddata[2]?"selected":"").">Neregistrovaný uživatel</option></select>";
											echo "</td></tr>";
										echo "<tr><td>".t("Max usage")."</td><td><input type=text style='width:100%;' name=item_".$selected."_max value='".$selecteddata[3]."'></td></tr>";
										echo "<tr><td></td><td><span class=desc>".t("0 for unlimited usage")."</span></td></tr>";
										echo "</table>";
										echo "<button class='btn btn-primary btn-sm' onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save','".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&selecteditem=".$selected."', '#edit_item_".$result["id"]."');return false;\">".t("save")."</button>";
									}
								}
							echo "</td></tr>";
						}
						if($result["type"] == "slider"){
							echo "<tr><td>Slíder</td></tr>";
						}
						if($result["type"] == "upload"){			
							echo "<tr><td valign=top>".t("Folder")."</td><td><input style='width:400px;' id=\"uploadir-".$row["id"]."\" type=text name='folder' value='".$data["folder"]."'><div class=desc>".t("in upload folder")."</div></td></tr>";							
							echo "<tr><td></td><td>";
								echo "<div class='folders'>";
								$dir = "upload";
								$cdir = scandir($dir); 
								foreach ($cdir as $key => $value) { 
									if (!in_array($value,array(".","..")) && is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
										echo "<div class='folder' onclick=\"$('#uploadir-".$row["id"]."').val('".$value."/');\"><i class=\"fas fa-folder\"></i> ".$value."</div>";
									}
								}
								echo "</div>";
							echo "</td></tr>";
							echo "<tr><td valign=top>".t("Max size")."</td><td><input style='width:400px;' type=text name='maxsize' value='".$data["maxsize"]."'><div class=desc>".t("in bites, 1048576 B is 1 MB").", ".t("actually is it", array("first-u" => false))." ".Utilities::convertBtoMB($data["maxsize"])." MB</div></td></tr>";							
							echo "<tr><td valign=top>".t("Allowed extensions")."</td><td><input style='width:400px;' type=text name='allowed' value='".$data["allowed"]."'><div class=desc>".t("separated by ,")." ".t("for example", array("first-u" => false)).": gif,png,jpg</div></td></tr>";							
							echo "<tr><td colspan=2 style='padding:5px;'></td></tr>";
							echo "<tr><td></td><td><label><input type=toggle_swipe name='resize' value='1' ".($data["resize"]=="1"?"checked":"")."> ".t("allow image resize")."</label><div class=desc>".t("works only for gif, png, jpg")."</div></td></tr>";
							echo "<tr><td valign=top>".t("Max size")."</td><td><input style='width:100px;' type=text name='resizew' value='".$data["resizew"]."'>x<input style='width:100px;' type=text name='resizeh' value='".$data["resizeh"]."'></td></tr>";							
						}
						if($result["type"] == "recaptcha"){
							echo "<tr><td></td><td><div class='box info'><a href='".Router::url()."adminv2/content/form-settings/' style='color: #2A8FBD;padding:5px;display: block;' target=_blank><i class=\"fas fa-cogs\"></i> Zde nastavte recaptchu aby fungovala</a>Jedná se overzi V2</div></td></tr>";
						}
						if($result["type"] == "upload" or $result["type"] == "textbox" or $result["type"] == "password" or $result["type"] == "textarea" or $result["type"] == "select" or $result["type"] == "picker"){
							echo "<tr><td colspan=4><hr></td></tr><tr><td>".t("requirement")."</td><td><input type=radio name='reque' value='0' ".($data["state"]=="0"?"checked":"")."> <input type=text style='width:23px;height:23px;'> <span style='width:120px;display:inline-block'>".t("basic")."</span>";//."</td></tr>";
							//echo "<tr><td></td><td>
							echo "<input type=radio name='reque' value='1' ".($data["state"]=="1"?"checked":"")."> <input type=text style='width:23px;height:23px;border: 1px solid red;'> ".t("required")."</td></tr>";
							echo "<tr><td></td><td><input type=radio name='reque' value='2' ".($data["state"]=="2"?"checked":"")."> <input type=text style='width:23px;height:23px;' disabled> <span style='width:120px;display:inline-block'>".t("disabled")."</span>";//."</td></tr>";
							echo "<input type=radio name='reque' value='3' ".($data["state"]=="3"?"checked":"")."> <input type=text style='width:23px;height:23px;border: 1px dashed silver;' readonly> ".t("hidden")."</td></tr>";
						}						
					}
					echo "<tr><td>".t("custom css class")."</td><td><input style='width:400px;' type=text name='css_class' value='".$data["cssclass"]."'></td></tr>";
					echo "</table>";
					//
					//echo "<button class=blue onClick=\"ajaxsend('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save');return false;\">".t("save")."</button> | <a style='padding:5px 0px;color:red;' href=# onClick=\"ajaxsend_del('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&dlt', '#item_col_".$result["id"]."');return false;\">".t("delete")."</a> <span style='displa:none;color:red;' id='show_error_".$id."'></span>";
					echo "<button class='btn btn-primary' onClick=\"ajaxsend_ext('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&save','".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."', '#edit_item_".$result["id"]."');return false;\">".t("save")."</button> | <a style='padding:5px 0px;color:red;' href=# onClick=\"ajaxsend_del('#form_item_".$id."', this, '#show_error_".$id."', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$result["id"]."&dlt', '#item_col_".$result["id"]."');return false;\">".t("delete")."</a> <span style='displa:none;color:red;' id='show_error_".$id."'></span>";
					echo "<input type=hidden name=type value='".$result["type"]."'></form>";					
				}
			}
		}else{
			echo "<script>_form_id_actual = \"".$t->router->_data["id"][0]."\"</script>";
			$result = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $t->router->_data["id"][0])->fetch();
			if($result == NULL){
				$t->root->page->draw_error("Formulář neexistuje", "Formulář ".$t->router->_data["id"][0]." neexistuje!");
			}else{
				if($t->router->_data["state"][0] == "ok"){
					$t->root->page->error_box(t("the form has been edited"), "ok");
				}

				if(isset($_POST["add"])){
					$data = array(
						"name" 		=> $_POST["whatadd"],
						"parent"	=> $t->router->_data["id"][0],
						"type"		=> $_POST["whatadd"],
						"position"	=> dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $t->router->_data["id"][0], " ORDER BY position")->count()
					);
					$result = dibi::query('INSERT INTO :prefix:form_items', $data);
					header("location:".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]);
				}

				$data = Config::sload($result["data"]);

				if(!isset($data["width"]) or $data["width"] == "") $data["width"]="200";
				if(!isset($data["redirect"])) $data["redirect"] = "";
				if(!isset($data["succme"])) $data["succme"] = "";
				if(!isset($data["enable"])) $data["enable"] = 0;
				if(!isset($data["onetime"])) $data["onetime"] = 0;

				echo "<div style='margin:8px auto;width:800px;'>";
					echo "<span style='font-size:26px;'>".t("Form edit")."</span>";
					echo "<form id=formedit style='margin-bottom:15px;'><table style='width:100%;margin-bottom:5px;'>";
						echo "<tr><td width=200>".t("Name")."</td><td><input type=text name=name value='".$result["name"]."' style='width:100%;'></td></tr>";
						echo "<tr><td>".t("After submitting to redirect")."</td><td><input placeholder='".t("inactive")."' type=text name=redirect value='".$data["redirect"]."' style='width:100%;'></td></tr>";
						echo "<tr><td>".t("Width fields")."</td><td><input type=text name=width value='".$data["width"]."' style='width:100%;'></td></tr>";
						echo "<tr><td valign=top>".t("success message")."</td><td><textarea name=succme style='width:100%;' rows=3>".$data["succme"]."</textarea></td></tr>";
						echo "<tr><td></td><td><label><input type=toggle_swipe name=enable value='1' ".($data["enable"]==1?"checked":"")."> ".t("enable form")."</label></td></tr>";
						echo "<tr><td></td><td><label><input type=toggle_swipe name=onetime value='1' ".($data["onetime"]==1?"checked":"")."> ".t("the form can only be filled in once")." <span class=desc>".t("only logged-in users can fill in")."</span></label></td></tr>";
					echo "</table>";
					echo "<span style='width:0px;float:left;top: -21px;position: relative;'><span style='displa:none;color:red;' id='showrrf'></span></span><button class='btn btn-primary' onClick=\"ajaxsend('#formedit', this, '#showrrf', '".$t->router->url_."admin/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item&editform');return false;\">".t("save")."</button></form>";
					echo "<div style='float:right;height: 0px;position: relative;top: -42px;left:0px;'><form action=# method=post>";
						echo "<a href='".Router::url()."adminv2/content/form-answer/".$t->router->_data["id"][0]."'>".t("show form answer")."</a> <span class=_buttonline><input type=text value='[form id=\"".$result["id"]."\"]' onClick=\"$(this).select();\" readonly style='padding: 4px;padding-bottom: 5px;'> <select id=whoadd name=whatadd style='width:230px;'>";
							echo "<option value='textbox'>Textbox</option>";
							echo "<option value='password'>Password</option>";
							echo "<option value='submit'>Submit</option>";
							echo "<option value='text'>HTML Text</option>";
							echo "<option value='textarea'>Textarea</option>";
							echo "<option value='select'>Select</option>";
							echo "<option value='recaptcha'>ReCaptcha</option>";
							echo "<option value='variable'>Variable</option>";
							echo "<option value='picker'>Picker</option>";
							echo "<option value='slider'>Slider</option>";
							echo "<option value='upload'>Upload</option>";
						echo "</select>";
						echo "  <input type=submit class='btn btn-primary btn-sm ml-1' name=add value='".t("add")."'></span>";
					echo "</form></div><div style='clear:both;'></div>";

					echo "<ul id=sortable style='list-style: none;padding: 0px;margin:0px;'>";
					$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $t->router->_data["id"][0], " ORDER BY position");
					foreach ($result_ as $n => $row) {
						echo "<li id='item_col_".$row["id"]."' data-id='".$row["id"]."'>";
						echo "<div id='item_".$row["id"]."' class=item_div>";
							echo "<span class='movable handle' style='width: 19px;display: inline-block;height: 15px;cursor: -webkit-grab;'></span>";
							echo "<span class=type>".$row["type"]."</span> ";
							echo "<span id='name_show_".$row["id"]."'>".($row["name"]==""?"<span class=desc>".$row["value"]."</span>":$row["name"])."</span>";
							echo " <span style='font-size:11px;color:silver;margin-left:10px;'>#".$row["id"]." (form_input_".$row["id"].")</span>";
							echo "<div onClick=\"ajaxload('".$t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item=".$row["id"]."', '#edit_item_".$row["id"]."', this, 56);\" style='width: 20px;height: 20px;float: right;background-repeat: no-repeat;background-position: 4px 1px;cursor:pointer;' class=arrow_r></div>";
						echo "</div>";
						echo "<div id='edit_item_".$row["id"]."' class=obsahblok>";
							echo t("loading")."...";
						echo "</div>";
						echo "</li>";
					}
					echo "</ul>";
					echo "<div class=bottom_line></div>";
				echo "</div>";
				?>
				<script>
				$(function(){
					$("#sortable").sortable({
						handle: ".handle",
						stop: function() { savePos(); }
					});
				});
				function savePos(){
					var p = $("#sortable").find("li");
					var dat = "";
					for (var i=0;i<p.length;i++) {
						dat+=$(p[i]).attr("data-id")+";";
					}
					dat = dat.substr(0,dat.length-1)
					ajaxcall("<?php echo $t->router->url_."adminv2/content/form-edit/".$t->router->_data["id"][0]."/?__type=ajax&item&savepos&data="; ?>"+dat);
				}
				</script>
				<?php
				echo "<span style='margin: 0px auto;color:silver;width:800px;display: block;font-size: 12px;'>".t("the order is automatically saved")."!</span>";
			}
		}
	}
}
