<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];

if($_GET["__type"] != "ajax"){
    echo "<ul class='menu sub'>";
        echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/info/'>".t("Infromation")."</a></li>";
        echo "<li ".($action == "language"?"class=select":"")."><a href='".$t->router->url_."adminv2/info/language/'>".t("Languages")."</a></li>";
        echo "<li ".($action == "settings"?"class=select":"")."><a href='".$t->router->url_."adminv2/info/settings/'>".t("Settings")."</a></li>";
        //echo "<li ".($action == "update"?"class=select":"")."><a href='".$t->router->url_."adminv2/info/update/'>".t("update")."<span id=topupdatenumber style='color: red;font-size: 10px;position: relative;top: -7px;padding-left: 5px;'></span></a></li>";
        //echo "<li ".($action == "subdomains"?"class=select":"")."><a href='".$t->router->url_."adminv2/info/subdomains/'>".t("subdomains")."</a></li>";
    echo "</ul>";
}
if($_GET["__type"] == "ajax"){
    if(isset($_GET["savepos"]) && $action == "settings"){
        $data = $_GET["data"];
        User::setData("admin-menu-pos", $data);

        exit();
    }    
}

if($action == "settings"){
    echo "<div class='card small'><div class=content><ul class=list id=sortable>";

    if($who == "mobiletogle"){
        $state = ($_GET["newstate"] == "false"? false: true);
        //echo ($state == true?1:0).", ".$_GET["newstate"].", ".$_GET["key"]."<hr>";
        $menuSettings = User::getData("admin-menu-settings", Config::ssave(array(
            "article" => array("mobile" => true),
            "users" => array("mobile" => true),
            "info" => array("mobile" => true),
            "menu" => array("mobile" => true),
        )));
        $prp = Config::sload($menuSettings);
        if(!isset($prp[$_GET["key"]])){
            $prp[$_GET["key"]] = array("mobile" => $state ? 1: 0);
        }else{
            $prp[$_GET["key"]]["mobile"] = ($state ? 1: 0);
        }
        User::setData("admin-menu-settings", Config::ssave($prp));
        header("location:".Router::url()."adminv2/info/settings/");
        exit();
    }

    $result = getIcons($t);
    //$data = User::getData("admin-menu-pos");        
    //if($data == ""){ $data = ""; foreach($result as $key => $item){ $data.=$key.";"; } User::setData("admin-menu-pos", $data); }
    //$lo = explode(";", $data);
    
    foreach($result as $key => $item){
        echo "<li class='swipable' data-swipable='true' data-id='".$key."' data-newstate='".($item["showMobile"]?"false":"true")."'>";
            echo "<div class='back blue left' style='padding-top: 10px;'>".t("show on mobile")."</div>";
            echo "<div class='main'>";
                echo "<div class='option'>";
                    echo "<input type=checkbox class=hide-mobile value=1 onchange=\"window.location.href='".Router::url()."adminv2/info/settings-mobiletogle/?key=".$key."&newstate='+$(this).is(':checked');\" ".Utilities::check($item["showMobile"] == 1).">";
                    if($item["showMobile"]){
                        echo "<i class='show-mobile fas fa-mobile-alt'></i>";
                    }
                echo "</div>";
                echo "<div class=title><span class='movable handle' style='margin-left: 3px;width: 19px;display: inline-block;height: 15px;cursor: -webkit-grab;'></span> <i style='width:30px;color:silver;padding-left:5px;' class=\"".$item["icon"]."\"></i> ".t($item["text"])."</div>";
            echo "</div>";
        echo "</li>";				
    }    
    echo "</ul></div></div>";
    echo "<span class='desc content padding'>Je doporučeno nastavit si jen 4 položky pro zobrazení na mobilu</span>";
    ?>
        <script>
        $(function(){
			$("#sortable").sortable({
                handle: ".handle",
                items: "li:not(.hide-mobile)",
				stop: function() { savePos(); }
            });
            $("#sortable").disableSelection();
            $(".swipable").on("click", function(){
                //window.location.href = "<?php echo $t->router->url_."adminv2/clients/stats-edit/"; ?>" + $(this).data("id");
            });
            $(".swipable").on("swiped", function(e, direction){
                if(direction == "right"){
                    //window.location.href = "<?php echo $t->router->url_."adminv2/clients/stats-nopublic/" ?>" + $(this).data("id");
                }else{
                    window.location.href = "<?php echo $t->router->url_."adminv2/info/settings-mobiletogle/?key="; ?>" + $(this).data("id") + "&newstate=" + $(this).data("newstate");
                    //console.log("<?php echo $t->router->url_."adminv2/info/settings-mobiletogle/?key="; ?>" + $(this).data("id") + "&newstate=" + $(this).data("newstate"));
                }
            });
        });
        function savePos(){
			var p = $("#sortable").find("li");
			var dat = "";
			for (var i=0;i<p.length;i++) {
                if($(p[i]).attr("data-id") != void 0)
				    dat+=$(p[i]).attr("data-id")+";";
			}
			dat = dat.substr(0,dat.length-1)
			ajaxcall("<?php echo $t->router->url_."adminv2/info/settings-savepos/?__type=ajax&savepos&data="; ?>"+dat);
        }
        </script>
    <?php
}
elseif($action == "update"){
	$sysv = Bootstrap::$version[0]."-".Bootstrap::$version[1].(count(Bootstrap::$version)>1?("-".Bootstrap::$version[2]):"");
	
	echo "<div id=boxo style='block;background: silver;width: 700px;padding: 10px;height: 150px;'>";
		echo "<div class=mess style='display:block;'>";
			echo "<div class=title style='font-size: 21px;margin-bottom:5px;'>Čekejte prosím načítám seznam souborů</div>";
			echo "<div class=text></div>";
		echo "</div>";
		echo "<div class=state style='display:none;'>";
			echo "<div style='color:red;font-size: 12px;font-weight: bold;' class=statetext>Stahuji efsdfds/dfsf/fdsf.php</div>";
			echo "<div style='padding:3px;background: white;margin: 10px 0px;' class=progres><div style='width:25%;height: 30px;background: #104780;' class=progresbar></div></div>";
			echo "<div><div style='float:left;' class=textleft>10 z 12</div><div style='float:right;' class=textright>25%</div><div style='clear:both;'></div></div>";
			echo "<div class=textinfo></div>";
		echo "</div>";
	echo "</div>";
	echo "<div id=transerfi style='display:none;'></div>";
	?>
	<script>
	var listfiles = "";
	var pol = 0, polmax = 5;
	var statedown = -1;
	function doter(d){
		for(var o=0;o<polmax;o++){
			if(pol == o){d.append("<b>·</b>");}
			else{d.append("<b>.</b>");}
		}
		pol++;if(pol >= polmax) pol=0;
	}
	function update(jak){
		if(jak == 1){
			//addScript("http://localhost/www/SnowLeopard/remote/update.php?check&version=<?php echo $sysv ?>&php=<?php echo phpversion(); ?>");
			setTimeout(function(){ update(2); }, 1000);
		}else if(jak == 2){
			if($("#transerfi").html() == ""){
				d.html("Pracuji ");
				doter($("#boxo .mess .text"));
				setTimeout(function(){ update(2); }, 200);
			}else{
				$("#boxo .mess .text").html("");
				listfiles = JSON.parse($("#transerfi").html());
				if(listfiles.state == 0){
					$("#boxo .mess .title").html("Hotovo");
					$("#boxo .mess .text").html("Není třeba aktualizace...");
					if(listfiles.plane!="")
						$("#boxo .mess .text").append("<br>Další verze je plánována dne <b>"+listfiles.plane+"</b>");
					if(listfiles.info!="")
						$("#boxo .mess .text").append(""+listfiles.info+"");
				}else{
					$("#boxo .mess .text").html("Počet souborů ke stažení: "+listfiles.files.count);
				}
				$("#boxo .state .textleft").html("0 z "+listfiles.files.count);
				$("#boxo .state .textright").html("0%");
				$("#boxo .mess").hide();
				$("#boxo .state").show();
				setTimeout(function(){ update(3); }, 100);
			}
		}else if(jak == 3){
			if(statedown+1 < listfiles.files.count){
				statedown+=1;
				
				$("#boxo .state .textright").html((Math.round(((statedown)/listfiles.files.count*100) * 100) / 100)+"%");
				$("#boxo .state .textleft").html((statedown)+" z "+listfiles.files.count);
				$("#boxo .state .statetext").html("Stahuji .../"+listfiles.ver+"_"+listfiles.rev+"/"+listfiles.files[statedown].name);
				doter($("#boxo .state .statetext"));
				$("#boxo .state .progresbar").css("width", (statedown)/listfiles.files.count*100+"%");
			
				//addScript("http://localhost/www/SnowLeopard/include/get.php?version="+listfiles.ver+"&rev="+listfiles.rev+"&pos="+statedown);
			
				setTimeout(function(){ update(4); }, 100);
			}else{
				statedown+=1;
				$("#boxo .state .statetext").html("Hotovo!");	
				$("#boxo .state .textright").html((statedown)/listfiles.files.count*100+"%");
				$("#boxo .state .textleft").html((statedown)+" z "+listfiles.files.count);
				$("#boxo .state .progresbar").css("width", ((statedown)/listfiles.files.count*100)+"%");
				window.location.href="<?php echo Router::url(false); ?>admin/info/";
			}
		}else if(jak == 4){
			if($("#transerfi").html() == ""){
				$("#boxo .state .statetext").html("Stahuji .../"+listfiles.ver+"_"+listfiles.rev+"/"+listfiles.files[statedown].name);
				doter($("#boxo .state .statetext"));
				setTimeout(function(){ update(4); }, 100);
			}else{
				itsit = JSON.parse($("#transerfi").html());
				if(itsit.state == 0){
					$("#boxo .mess .title").html("Chyba");
					$("#boxo .mess .text").html(itsit.error);
					$("#boxo .mess").show();
					$("#boxo .state").hide();
				}else{
					setTimeout(function(){ update(3); }, 100);
				}
			}
		}
	}
	update(1);
	</script>
	<?php
}
else if($action == "subdomains" and $_GET["who"] == "edit"){		
		echo "<h1>Editace subdomény</h1>";
		
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
		
		$result = dibi::query("SELECT * FROM :prefix:subdomains WHERE id=%i", $t->router->_data["id"][0])->fetch();
		
		if(!(User::current()["id"] == 1 or User::current()["id"] == $result["owner"])){
			echo "<b style='color:red;'>You don't have permission to edit this subdomain!</b>";
		}else{
			echo "<form action=# method=post>";
				echo "<table class=tabfor style='width:700px;margin:20px 0px;'>";
					echo "<tr><td width=180><label>".t("name")."</label></td><td width=430><input type=text name=name value='".$result["name"]."'></td></tr>";
					echo "<tr><td><label>".t("prefix")."</label></td><td width=430><input type=text name=prefix value='".$result["prefix"]."'></td></tr>";				
					echo "<tr><td valign=top><label>".t("owner")."</label></td><td>";
						echo "<select id=owner name=owner style='width:300px;'>";
							$resul_ = dibi::query('SELECT * FROM :prefix:users ORDER BY id');
							foreach ($resul_ as $n => $row) {
								echo "<option value='".$row["id"]."' ".($row["id"] == $result["owner"]?"selected":"").">".$row["nick"]."</option>";
							}
					echo "</select></td></tr>";
					echo "<tr><td><label></label></td><td width=430><input type=checkbox name=visible value='1' ".($result["visibility"] == 1?"checked":"")."> ".t("visible")."</td></tr>";				
					echo "<tr><td><label></label></td><td width=430><input type=checkbox name=locked value='1' ".($result["locked"] == 1?"checked":"")."> ".t("locked")."</td></tr>";								
				echo "</table>";
			echo "<input type=submit name=edit value='".t("edit")."'>";
			echo "</form>";
		}
}
else if($action == "subdomains"){
	echo "<div class=bottommenu><a href='".$t->router->url_."admin/info/subdomains-new/'>".t("new subdomain")."</a></div>";
	
		echo "<table class='table table-bordered table-sm tablik'>";
		echo "<tr><th width=150>".t("name")."</th><th width=150>".t("prefix")."</th><th width=80>".t("locked")."</th><th width=100>".t("owner")."</th><th width=160>".t("action")."</th></tr>";
		echo "<tr><td>root<br><span class=desc>Main domain</span></td><td>".$t->root->database->prefix."</td><td>NO</td><td>".User::current()["nick"]."</td><td><a href='".$t->router->url_."admin/info/subdomains-switch/0' class=xbutton>".t("switch")."</a></td></tr>";
		$result = dibi::query('SELECT * FROM subdomains ORDER BY id DESC');
		foreach ($result as $n => $row) {
			echo "<tr>";
				$perm = User::get($row["owner"]);
				
				echo "<td>".$row["name"]."<br><span class=desc>Datum: ".Strings::str_time($row["date"])."</span></td>";
				echo "<td>".$row["prefix"]."</td>";
				if($row["locked"] == 1)
					echo "<td>YES</td>";
				else
					echo "<td>NO</td>";
				echo "<td>".$perm["nick"]."</td>";
				echo "<td>";
					
						echo "<a href='".$t->router->url_."admin/info/subdomains-switch/".$row["id"]."' class=xbutton>".t("switch")."</a> ";
						echo "<a href='".$t->router->url_."admin/info/subdomains-edit/".$row["id"]."' class=xbutton><img src='".Router::url()."/modules/admin/images/edit_.png' class=des> ".t("edit")."</a> ";
						echo "<a href='".$t->router->url_."admin/info/subdomains-delete/".$row["id"]."' class=xbutton><img src='".Router::url()."/modules/admin/images/delete.png' class=des> ".t("delete")."</a>";
				
				echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
}
else if($action == "language"){	
	if(isset($_POST["save"])){
		dibi::query('UPDATE :prefix:settings SET ', array("value" => $_POST["lang"]), "WHERE `name`=%s", "default-lang");
		$t->root->config->update("default-lang", $_POST["lang"]);
		header("location:".Router::url()."adminv2/info/language/");
	}
	
	$languages = explode(",", Database::getConfig("languages"));
	$default   = Database::getConfig("default-lang");
    
    /*echo "<div class=hide-mobile>";
        echo "<form action=# method=post>";
        echo "<table class='table table-bordered table-sm tablik'>";
        echo "<tr><th width=350>".t("name")."</th><th width=60>".t("default")."</th><th width=60>".t("action")."</th></tr>";
        for($i=0;$i<count($languages);$i++){
            echo "<tr><td>".t($languages[$i])." <span style='font-size:12px;color:silver;'>".$languages[$i]."</span></td><td><input type=radio name=lang value='".$languages[$i]."' ".($languages[$i]==$default?"checked":"")."></td><td><a href='#'>".t("edit")."</a></td></tr>";
        }
        echo "</table>";
        echo "<input type=submit class='blue btn btn-primary hide-mobile' name=save value='".t("edit")."'></form>";
    echo "</div>";*/
    echo "<div>";
        echo "<form action=# method=post>";
        echo "<div class='card small'><div class=content><ul class=list>";
        for($i=0;$i<count($languages);$i++){
            echo "<li onclick=\"$('#lgn-radio-".$i."').prop('checked', true);\">";
                echo "<div class=option style='margin-top: 14px;margin-right: 14px;'>";
                    echo "<input type=radio id='lgn-radio-".$i."' name=lang value='".$languages[$i]."' ".($languages[$i]==$default?"checked":"").">";
                echo "</div>";
                echo t($languages[$i])."<br><span style='font-size:12px;color:silver;'>".$languages[$i]."</span>";
            echo "</li>";
        }
        echo "</ul></div></div>";
        echo "<input type=submit class='blue btn btn-primary hide-mobile' style='margin-left: 10px;' name=save id='btn-save' value='".t("edit")."'></form>";
    echo "</div>";
    ?>
    <script>
    $(function(){
        setTimeout(function(){
            actionButton.setText("Upravit")
            actionButton.changeIcon("fas fa-save");
            actionButton.show();
            actionButton.onclick(function(){
                $("#btn-save").click();
            });
        }, 100);
    });
    </script>
    <?php
}
else if($action == "show"){
	if(isset($_POST["title"])){
		$pole = array(
					"title" => $_POST["title"],
					"description" => $_POST["description"],
					"autor" => $_POST["autor"],
					"keywords" => $_POST["keywords"],
                    "utc" => $_POST["utc"],
                    "titleSeparator" => $_POST["titleSeparator"],
                    "titleFirst" => $_POST["titleFirst"],
                    "timeformat" => $_POST["timeformat"]
                );
                
        $logo = $_FILES["logo"];

        $uploadLogo = Utilities::processUploadFile($logo, "", "logo", array("png","jpg","gif"));
        if($uploadLogo["error"] == null && trim($uploadLogo["filename"]) != "")
            $pole["logo"] = 'upload/'.$uploadLogo["filename"];
				
		foreach($pole as $key => $value){
            $t->root->config->update($key, $value);
        }
        
        /*$t->root->page->error_box(t("the changes have been saved."), "ok", true);
        if($uploadLogo["error"] != NULL) {
            $t->root->page->error_box($uploadLogo["error"] , "error", true);
        }*/

        header('Content-Type: application/json');		
		echo json_encode(array(
            "logo" => $uploadLogo            
        )); 

        exit;
	}
	$user = User::current();
    $perm = User::permission($user["permission"]);
    
    include _ROOT_DIR . "/config/db.php";
    echo "<div class='content padding'>";

    echo "<div class='right-side'>";
        echo "<div class='card expandable' data-expandable='true'>";
            echo "<div class=title>".t("Information")."</div>";
            echo "<div class=content>";
                echo "<ul class=list>";
                    echo "<li>";
                        echo "<div class='option'><span style='background:".$perm["color"].";display:inline-block;width: 12px;height: 12px;margin-right: 5px;'></span>".$perm["name"]."</span></div>";
                        echo t("Permission");                 
                    echo "</li>";
                    echo "<li>";
                        echo "<div class='option'>".Utilities::ip()."</div>";
                        echo t("IP address");                 
                    echo "</li>";
                    echo "<li>";
                        echo "<div class='option'>".phpversion()."</div>";
                        echo t("Version")." PHP";                 
                    echo "</li>";
                    /*echo "<li>";
                        if(function_exists("mysql_get_server_info")){
                            $mysql = explode("-", mysql_get_server_info());
                            echo "<div class='option'>".$mysql[0]." <span style='font-size:10px;color:#6B6B6B;'>(".end($mysql).")</span></div>";
                        }else{
                            echo "<div class='option'>chyba</div>";
                        }
                        echo t("version")." MySQL";                 
                    echo "</li>";*/
                    echo "<li>";
                        echo "<div class='option'>".($t->root->database->prefix==""?t("without prefix"):$t->root->database->prefix)."</div>";
                        echo "Prefix";                 
                    echo "</li>";
                    echo "<li>";
                        echo "<div class='option'>".($database["database"])."</div>";
                        echo "Database";                 
                    echo "</li>";                    
                    echo "<li>";
                        echo "<div class='option'>".Bootstrap::$version."</div>";
                        echo t("System version");                 
                    echo "</li>";
                    echo "<li>";
                        echo "<div class='option'><span id='actualver'><i class='loading small'></i></span></div>";
                        echo t("Latest version");   
                        echo "<div style='margin-top: 4px;font-size:13px;display: none;' id='infotext'></div>";              
                    echo "</li>";
                    echo "<li>";
                        echo "<div class='option'>".$t->root->config->get("version")."</div>";
                        echo t("Dabatase version");                 
                    echo "</li>";
                    $t->root->config->load_variables("file", "root");
                    echo "<li>";
                        echo "<div class='option'>".t($t->root->config->get_variable("Compress", "file", "FALSE"))." (".t("Size").": ".$t->root->config->get_variable("CompressChunk", "file", "16000").")</div>";
                        echo t("Compression");                
                    echo "</li>";
                    echo "<li>";
                        echo "<div class='option'>".t($t->root->config->get_variable("LogMysql", "file", "FALSE"))."</div>";
                        echo t("MySQL Log");                
                    echo "</li>";
                echo "</ul>";
            echo "</div>";    
        echo "</div>";
    echo "</div>";

	//echo "<script type='text/javascript' src='http://localhost/www/SnowLeopard/remote/version.php?version=".$sysv."&php=".phpversion()."&mysql=".$mysql[0]."'></script>";
	echo "<div class='left-side'>";
        echo "<div class=boxi id=updatebox style='display:none;'>";
            echo "<h1>".t("There is new version avalible")."</h1>";
            echo "<a href='".Router::url()."adminv2/plugins/install-system/' class=button>".t("Go to update")."</a>";
        echo "</div>";
        echo "<form method=post id=infosetting>";
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Title")."</label><div class=\"col-sm-10\">";
                echo "<input type=text class='form-control' name=title value='".$t->root->config->get("title")."'>";
            echo "</div></div>";
            $sep = $t->root->config->get("titleSeparator");
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Title separator")."</label><div class=\"col-sm-10\">";
                echo "<input type=text class='form-control' name=titleSeparator value='".$sep."'>";
            echo "</div></div>";
            $titleFirst = $t->root->config->get("titleFirst");
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Title position")."</label><div class=\"col-sm-10\">";
                echo "<select name=titleFirst style='width:100%;'>";
                    echo "<option value=2 ".Utilities::selected($titleFirst == 2).">Pre-Title".$sep.$t->root->config->get("title")."</option>";
                    echo "<option value=1 ".Utilities::selected($titleFirst == 1).">".$t->root->config->get("title").$sep."Pre-Title</option>";
                echo "</select>";
            echo "</div></div>";
            $logo = $t->root->config->get("logo");
            if($logo != "" && substr($logo, 0, 4) != "http") { $logo = Router::url().$logo; }
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Logo")."</label><div class=\"col-sm-10\">";
                echo "<input type=\"file\" name=\"logo\" value=\"".$logo."\"/>";                
            echo "</div></div>";
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Keywords")."</label><div class=\"col-sm-10\">";
                echo "<input type=text class='form-control' name=keywords value='".$t->root->config->get("Keywords")."'>";
                echo "<small class=\"form-text text-muted\">".t("A few words describing the page, separated by commas")."</small>";
            echo "</div></div>";
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Description")."</label><div class=\"col-sm-10\">";
                echo "<input type=text class='form-control' name=description value='".$t->root->config->get("description")."'>";
            echo "</div></div>";
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Author")."</label><div class=\"col-sm-10\">";
                echo "<input type=text class='form-control' name=autor value='".$t->root->config->get("autor")."'>";
            echo "</div></div>";
            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Time zone")."</label><div class=\"col-sm-10\">";
                echo "".Utilities::select(
                    $timezones=array(
                        "America/Adak",
                        "America/Argentina/Buenos_Aires",
                        "America/Argentina/La_Rioja",
                        "America/Argentina/San_Luis",
                        "America/Atikokan",
                        "America/Belem",
                        "America/Boise",
                        "America/Caracas",
                        "America/Chihuahua",
                        "America/Cuiaba",
                        "America/Denver",
                        "America/El_Salvador",
                        "America/Godthab",
                        "America/Guatemala",
                        "America/Hermosillo",
                        "America/Indiana/Tell_City",
                        "America/Inuvik",
                        "America/Kentucky/Louisville",
                        "America/Lima",
                        "America/Managua",
                        "America/Mazatlan",
                        "America/Mexico_City",
                        "America/Montreal",
                        "America/Nome",
                        "America/Ojinaga",
                        "America/Port-au-Prince",
                        "America/Rainy_River",
                        "America/Rio_Branco",
                        "America/Santo_Domingo",
                        "America/St_Barthelemy",
                        "America/St_Vincent",
                        "America/Tijuana",
                        "America/Whitehorse",
                        "America/Anchorage",
                        "America/Argentina/Catamarca",
                        "America/Argentina/Mendoza",
                        "America/Argentina/Tucuman",
                        "America/Atka",
                        "America/Belize",
                        "America/Buenos_Aires",
                        "America/Catamarca",
                        "America/Coral_Harbour",
                        "America/Curacao",
                        "America/Detroit",
                        "America/Ensenada",
                        "America/Goose_Bay",
                        "America/Guayaquil",
                        "America/Indiana/Indianapolis",
                        "America/Indiana/Vevay",
                        "America/Iqaluit",
                        "America/Kentucky/Monticello",
                        "America/Los_Angeles",
                        "America/Manaus",
                        "America/Mendoza",
                        "America/Miquelon",
                        "America/Montserrat",
                        "America/Noronha",
                        "America/Panama",
                        "America/Port_of_Spain",
                        "America/Rankin_Inlet",
                        "America/Rosario",
                        "America/Sao_Paulo",
                        "America/St_Johns",
                        "America/Swift_Current",
                        "America/Toronto",
                        "America/Winnipeg",
                        "America/Anguilla",
                        "America/Argentina/ComodRivadavia",
                        "America/Argentina/Rio_Gallegos",
                        "America/Argentina/Ushuaia",
                        "America/Bahia",
                        "America/Blanc-Sablon",
                        "America/Cambridge_Bay",
                        "America/Cayenne",
                        "America/Cordoba",
                        "America/Danmarkshavn",
                        "America/Dominica",
                        "America/Fort_Wayne",
                        "America/Grand_Turk",
                        "America/Guyana",
                        "America/Indiana/Knox",
                        "America/Indiana/Vincennes",
                        "America/Jamaica",
                        "America/Knox_IN",
                        "America/Louisville",
                        "America/Marigot",
                        "America/Menominee",
                        "America/Moncton",
                        "America/Nassau",
                        "America/North_Dakota/Beulah",
                        "America/Pangnirtung",
                        "America/Porto_Acre",
                        "America/Recife",
                        "America/Santa_Isabel",
                        "America/Scoresbysund",
                        "America/St_Kitts",
                        "America/Tegucigalpa",
                        "America/Tortola",
                        "America/Yakutat",
                        "America/Antigua",
                        "America/Argentina/Cordoba",
                        "America/Argentina/Salta",
                        "America/Aruba",
                        "America/Bahia_Banderas",
                        "America/Boa_Vista",
                        "America/Campo_Grande",
                        "America/Cayman",
                        "America/Costa_Rica",
                        "America/Dawson",
                        "America/Edmonton",
                        "America/Fortaleza",
                        "America/Grenada",
                        "America/Halifax",
                        "America/Indiana/Marengo",
                        "America/Indiana/Winamac",
                        "America/Jujuy",
                        "America/Kralendijk",
                        "America/Lower_Princes",
                        "America/Martinique",
                        "America/Merida",
                        "America/Monterrey",
                        "America/New_York",
                        "America/North_Dakota/Center",
                        "America/Paramaribo",
                        "America/Porto_Velho",
                        "America/Regina",
                        "America/Santarem",
                        "America/Shiprock",
                        "America/St_Lucia",
                        "America/Thule",
                        "America/Vancouver",
                        "America/Yellowknife",
                        "America/Araguaina",
                        "America/Argentina/Jujuy",
                        "America/Argentina/San_Juan",
                        "America/Asuncion",
                        "America/Barbados",
                        "America/Bogota",
                        "America/Cancun",
                        "America/Chicago",
                        "America/Creston",
                        "America/Dawson_Creek",
                        "America/Eirunepe",
                        "America/Glace_Bay",
                        "America/Guadeloupe",
                        "America/Havana",
                        "America/Indiana/Petersburg",
                        "America/Indianapolis",
                        "America/Juneau",
                        "America/La_Paz",
                        "America/Maceio",
                        "America/Matamoros",
                        "America/Metlakatla",
                        "America/Montevideo",
                        "America/Nipigon",
                        "America/North_Dakota/New_Salem",
                        "America/Phoenix",
                        "America/Puerto_Rico",
                        "America/Resolute",
                        "America/Santiago",
                        "America/Sitka",
                        "America/St_Thomas",
                        "America/Thunder_Bay",
                        "America/Virgin",
                        "Indian/Antananarivo",
                        "Indian/Kerguelen",
                        "Indian/Reunion",
                        "Australia/ACT",
                        "Australia/Currie",
                        "Australia/Lindeman",
                        "Australia/Perth",
                        "Australia/Victoria",
                        "Europe/Amsterdam",
                        "Europe/Berlin",
                        "Europe/Chisinau",
                        "Europe/Helsinki",
                        "Europe/Kiev",
                        "Europe/Madrid",
                        "Europe/Moscow",
                        "Europe/Prague",
                        "Europe/Sarajevo",
                        "Europe/Tallinn",
                        "Europe/Vatican",
                        "Europe/Zagreb",
                        "Pacific/Apia",
                        "Pacific/Efate",
                        "Pacific/Galapagos",
                        "Pacific/Johnston",
                        "Pacific/Marquesas",
                        "Pacific/Noumea",
                        "Pacific/Ponape",
                        "Pacific/Tahiti",
                        "Pacific/Wallis",
                        "Indian/Chagos",
                        "Indian/Mahe",
                        "Australia/Adelaide",
                        "Australia/Darwin",
                        "Australia/Lord_Howe",
                        "Australia/Queensland",
                        "Australia/West",
                        "Europe/Andorra",
                        "Europe/Bratislava",
                        "Europe/Copenhagen",
                        "Europe/Isle_of_Man",
                        "Europe/Lisbon",
                        "Europe/Malta",
                        "Europe/Nicosia",
                        "Europe/Riga",
                        "Europe/Simferopol",
                        "Europe/Tirane",
                        "Europe/Vienna",
                        "Europe/Zaporozhye",
                        "Pacific/Auckland",
                        "Pacific/Enderbury",
                        "Pacific/Gambier",
                        "Pacific/Kiritimati",
                        "Pacific/Midway",
                        "Pacific/Pago_Pago",
                        "Pacific/Port_Moresby",
                        "Pacific/Tarawa",
                        "Pacific/Yap",
                        "Africa/Abidjan",
                        "Africa/Asmera",
                        "Africa/Blantyre",
                        "Africa/Ceuta",
                        "Africa/Douala",
                        "Africa/Johannesburg",
                        "Africa/Kinshasa",
                        "Africa/Lubumbashi",
                        "Africa/Mbabane",
                        "Africa/Niamey",
                        "Africa/Timbuktu",
                        "Africa/Accra",
                        "Africa/Bamako",
                        "Africa/Brazzaville",
                        "Africa/Conakry",
                        "Africa/El_Aaiun",
                        "Africa/Juba",
                        "Africa/Lagos",
                        "Africa/Lusaka",
                        "Africa/Mogadishu",
                        "Africa/Nouakchott",
                        "Africa/Tripoli",
                        "Africa/Addis_Ababa",
                        "Africa/Bangui",
                        "Africa/Bujumbura",
                        "Africa/Dakar",
                        "Africa/Freetown",
                        "Africa/Kampala",
                        "Africa/Libreville",
                        "Africa/Malabo",
                        "Africa/Monrovia",
                        "Africa/Ouagadougou",
                        "Africa/Tunis",
                        "Africa/Algiers",
                        "Africa/Banjul",
                        "Africa/Cairo",
                        "Africa/Dar_es_Salaam",
                        "Africa/Gaborone",
                        "Africa/Khartoum",
                        "Africa/Lome",
                        "Africa/Maputo",
                        "Africa/Nairobi",
                        "Africa/Porto-Novo",
                        "Africa/Windhoek",
                        "Africa/Asmara",
                        "Africa/Bissau",
                        "Africa/Casablanca",
                        "Africa/Djibouti",
                        "Africa/Harare",
                        "Africa/Kigali",
                        "Africa/Luanda",
                        "Africa/Maseru",
                        "Africa/Ndjamena",
                        "Africa/Sao_Tome",
                        "Atlantic/Azores",
                        "Atlantic/Faroe",
                        "Atlantic/St_Helena",
                        "Atlantic/Bermuda",
                        "Atlantic/Jan_Mayen",
                        "Atlantic/Stanley",
                        "Atlantic/Canary",
                        "Atlantic/Madeira",
                        "Atlantic/Cape_Verde",
                        "Atlantic/Reykjavik",
                        "Atlantic/Faeroe",
                        "Atlantic/South_Georgia",
                        "Asia/Aden",
                        "Asia/Aqtobe",
                        "Asia/Baku",
                        "Asia/Calcutta",
                        "Asia/Dacca",
                        "Asia/Dushanbe",
                        "Asia/Hong_Kong",
                        "Asia/Jayapura",
                        "Asia/Kashgar",
                        "Asia/Kuala_Lumpur",
                        "Asia/Magadan",
                        "Asia/Novokuznetsk",
                        "Asia/Pontianak",
                        "Asia/Riyadh",
                        "Asia/Shanghai",
                        "Asia/Tehran",
                        "Asia/Ujung_Pandang",
                        "Asia/Vladivostok",
                        "Asia/Almaty",
                        "Asia/Ashgabat",
                        "Asia/Bangkok",
                        "Asia/Choibalsan",
                        "Asia/Damascus",
                        "Asia/Gaza",
                        "Asia/Hovd",
                        "Asia/Jerusalem",
                        "Asia/Kathmandu",
                        "Asia/Kuching",
                        "Asia/Makassar",
                        "Asia/Novosibirsk",
                        "Asia/Pyongyang",
                        "Asia/Saigon",
                        "Asia/Singapore",
                        "Asia/Tel_Aviv",
                        "Asia/Ulaanbaatar",
                        "Asia/Yakutsk",
                        "Asia/Amman",
                        "Asia/Ashkhabad",
                        "Asia/Beirut",
                        "Asia/Chongqing",
                        "Asia/Dhaka",
                        "Asia/Harbin",
                        "Asia/Irkutsk",
                        "Asia/Kabul",
                        "Asia/Katmandu",
                        "Asia/Kuwait",
                        "Asia/Manila",
                        "Asia/Omsk",
                        "Asia/Qatar",
                        "Asia/Sakhalin",
                        "Asia/Taipei",
                        "Asia/Thimbu",
                        "Asia/Ulan_Bator",
                        "Asia/Yekaterinburg",
                        "Asia/Anadyr",
                        "Asia/Baghdad",
                        "Asia/Bishkek",
                        "Asia/Chungking",
                        "Asia/Dili",
                        "Asia/Hebron",
                        "Asia/Istanbul",
                        "Asia/Kamchatka",
                        "Asia/Kolkata",
                        "Asia/Macao",
                        "Asia/Muscat",
                        "Asia/Oral",
                        "Asia/Qyzylorda",
                        "Asia/Samarkand",
                        "Asia/Tashkent",
                        "Asia/Thimphu",
                        "Asia/Urumqi",
                        "Asia/Yerevan",
                        "Asia/Aqtau",
                        "Asia/Bahrain",
                        "Asia/Brunei",
                        "Asia/Colombo",
                        "Asia/Dubai",
                        "Asia/Ho_Chi_Minh",
                        "Asia/Jakarta",
                        "Asia/Karachi",
                        "Asia/Krasnoyarsk",
                        "Asia/Macau",
                        "Asia/Nicosia",
                        "Asia/Phnom_Penh",
                        "Asia/Rangoon",
                        "Asia/Seoul",
                        "Asia/Tbilisi",
                        "Asia/Tokyo",
                        "Asia/Vientiane",
                        "Australia/Canberra",
                        "Australia/LHI",
                        "Australia/NSW",
                        "Australia/Tasmania",
                        "Australia/Broken_Hill",
                        "Australia/Hobart",
                        "Australia/North",
                        "Australia/Sydney",
                        "Pacific/Chuuk",
                        "Pacific/Fiji",
                        "Pacific/Guam",
                        "Pacific/Kwajalein",
                        "Pacific/Niue",
                        "Pacific/Pitcairn",
                        "Pacific/Saipan",
                        "Pacific/Truk",
                        "Pacific/Chatham",
                        "Pacific/Fakaofo",
                        "Pacific/Guadalcanal",
                        "Pacific/Kosrae",
                        "Pacific/Nauru",
                        "Pacific/Palau",
                        "Pacific/Rarotonga",
                        "Pacific/Tongatapu",
                        "Pacific/Easter",
                        "Pacific/Funafuti",
                        "Pacific/Honolulu",
                        "Pacific/Majuro",
                        "Pacific/Norfolk",
                        "Pacific/Pohnpei",
                        "Pacific/Samoa",
                        "Pacific/Wake",
                        "Antarctica/Casey",
                        "Antarctica/McMurdo",
                        "Antarctica/Vostok",
                        "Antarctica/Davis",
                        "Antarctica/Palmer",
                        "Antarctica/DumontDUrville",
                        "Antarctica/Rothera",
                        "Antarctica/Macquarie",
                        "Antarctica/South_Pole",
                        "Antarctica/Mawson",
                        "Antarctica/Syowa",
                        "Arctic/Longyearbyen",
                        "Europe/Athens",
                        "Europe/Brussels",
                        "Europe/Dublin",
                        "Europe/Istanbul",
                        "Europe/Ljubljana",
                        "Europe/Mariehamn",
                        "Europe/Oslo",
                        "Europe/Rome",
                        "Europe/Skopje",
                        "Europe/Tiraspol",
                        "Europe/Vilnius",
                        "Europe/Zurich",
                        "Europe/Belfast",
                        "Europe/Bucharest",
                        "Europe/Gibraltar",
                        "Europe/Jersey",
                        "Europe/London",
                        "Europe/Minsk",
                        "Europe/Paris",
                        "Europe/Samara",
                        "Europe/Sofia",
                        "Europe/Uzhgorod",
                        "Europe/Volgograd",
                        "Europe/Belgrade",
                        "Europe/Budapest",
                        "Europe/Guernsey",
                        "Europe/Kaliningrad",
                        "Europe/Luxembourg",
                        "Europe/Monaco",
                        "Europe/Podgorica",
                        "Europe/San_Marino",
                        "Europe/Stockholm",
                        "Europe/Vaduz",
                        "Europe/Warsaw",
                        "Indian/Cocos",
                        "Indian/Mauritius",
                        "Indian/Christmas",
                        "Indian/Maldives",
                        "Indian/Comoro",
                        "Indian/Mayotte",
                        "Australia/Brisbane",
                        "Australia/Eucla",
                        "Australia/Melbourne",
                        "Australia/South",
                        "Australia/Yancowinna",
                        ),
                $t->root->config->get("utc"),"utc","width:100%;","slctutc", "input", true)."";
            $timeFormat = Utilities::getTimeFormat();
                echo "<small class=\"form-text text-muted\">".t("Time with timezone")." <b>".$t->root->config->get("utc")."</b> ".t("is")." <b>".date($timeFormat, time()+3600*$t->root->config->get("utc"))."</b></i></small>";
            echo "</div></div>";

            echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">".t("Time format")."</label><div class=\"col-sm-10\">";
                echo "<input type=text class='form-control' name=timeformat value='".$timeFormat."'>";
                echo "<small class=\"form-text text-muted\">".t("For more information go")." <a href='https://www.php.net/manual/en/function.date.php#format' target=_blank>".t("there")."</a></small>";
            echo "</div></div>";
        
            echo "<div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-2'></label><div class='col-12 col-sm-10'><input type=submit class='blue btn btn-primary hide-mobile' name=update id='btn-update' onclick=\"upload.Upload(); return false;\" value='".t("edit")."'></div></div>";
        echo "</form></div>";	
    echo "</div>";
    echo "<div class=clear></div>";

    ?>
    <script>
        var server = "<?php echo getUpdateServer(); ?>";
        var upload = null;
        var version = "<?php echo Bootstrap::$version; ?>";
        $(function(){
            upload = new Uploader(
                "<?php echo Router::url() ?>adminv2/info/?__type=ajax", 
                $("#infosetting"), 
                $("[name='logo']"), {
                    autosubmit: false,
                    maxfiles: 1
                }
            );
            upload.AfterUpload(function(data){
                window.location.reload();                
            });

            setTimeout(function(){
                actionButton.setText("<?php echo t("Save"); ?>")
                actionButton.changeIcon("fas fa-save");
                actionButton.show();
                actionButton.onclick(function(){
                    $("#btn-update").click();
                });
            }, 100);

            $.getJSON(server+"apigetplugin/?code=core", function(data) {
                $("#actualver").html(data.version);
                var v = cmpVersions(data.version, version);
                if(v < 0) {
                    $("#infotext").show().html("<?php echo t("Your curent version is ahead of latest version"); ?>");
                }else if(v > 0) {
                    $("#infotext").show().html("<?php echo t("There is new version avalible"); ?>");
                    $("#updatebox").show();
                }
            });
        });
    </script>
    <?php
}
?>