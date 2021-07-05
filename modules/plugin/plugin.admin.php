<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];

if($_GET["__type"] != "ajax"){
    echo "<ul class='menu sub'>";
        echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/plugins/'>".t("Manage")."</a></li>";
        echo "<li ".($action == "install"?"class=select":"")."><a href='".$t->router->url_."adminv2/plugins/install/'>".t("Install")."</a></li>";
        //#not:release
        if(User::isPerm("plugin_manager")) {
            echo "<li ".($action == "admin"?"class=select":"")."><a href='".$t->router->url_."adminv2/plugins/admin/'>".t("Administrate")."</a></li>";        
        }
        //#endnot
        echo "<li ".($action == "settings"?"class=select":"")."><a href='".$t->router->url_."adminv2/plugins/settings/'>".t("Settings")."</a></li>";
    echo "</ul>";
}

$config = $t->root->config;
$server = getUpdateServer();

if($action == "install" && $who == "system") {
    if(isset($_GET["maintace_on"])) {
        $t->root->page->maintenanceMode(true, "system.update");
        echo json_encode(array("ok" => true));
        exit;
    }
    if(isset($_GET["maintace_off"])) {
        $t->root->page->maintenanceMode(false, "system.update.end");

        echo json_encode(array("ok" => true));
        exit;
    }
    if(isset($_GET["download"])) {
        $result = json_decode(file_get_contents($server."apigetplugin/?code=".$data["code"]."&pass=".$_GET["pass"]), true);
        file_put_contents(_ROOT_DIR."/upload/plugins/install/systemupdate_".$result["version"].".zip", fopen($server."apidownloadplugin/?code=core", 'r'));
        
        echo json_encode(array("ok" => true));
        exit;
    }
    if(isset($_GET["clean"])) {
        $result = json_decode(file_get_contents($server."apigetplugin/?code=".$data["code"]."&pass=".$_GET["pass"]), true);
        
        unlink(_ROOT_DIR."/.manifest");
        unlink(_ROOT_DIR."/update.php");
        unlink(_ROOT_DIR."/upload/plugins/install/systemupdate_".$result["version"].".zip");
        
        echo json_encode(array("ok" => true));
        exit;
    }
    if(isset($_GET["unzip"])) {
        $result = json_decode(file_get_contents($server."apigetplugin/?code=".$data["code"]."&pass=".$_GET["pass"]), true);
        $zip = new ZipArchive();
        $zip->open(_ROOT_DIR."/upload/plugins/install/systemupdate_".$result["version"].".zip");
        $zip->extractTo(_ROOT_DIR);
        $zip->close();

        echo json_encode(array("ok" => true));
        exit;
    }
    if(isset($_GET["install"])) {
        if(file_exists(_ROOT_DIR."/update.php")) {
            include_once _ROOT_DIR."/update.php";
            if(function_exists("_system_update")) {
                _system_update();
            }
        }

        echo json_encode(array("ok" => true));
        exit;
    }

    echo "<h1>".t("System update")."</h1>";

    echo "<div class='state-section'>";
        echo "<div class='status disabled' id=step1>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Checking latest version of system")." <span id=latestver></span></span>";
        echo "</div>";
        echo "<div class='status disabled' id=step2>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Turning on maintenance mode")."</span>";
        echo "</div>";
        echo "<div class='status disabled' id=step3>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Downloading latest version")."</span>";
        echo "</div>";
        echo "<div class='status disabled' id=step4>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Unpacking latest version")."</span>";
        echo "</div>";
        echo "<div class='status disabled' id=step5>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Installing")."</span>";
        echo "</div>";
        echo "<div class='status disabled' id=step6>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Clearing")."</span>";
        echo "</div>";
        echo "<div class='status disabled' id=step7>";
            echo "<span class='icon'><i class='loading small'></i></span> <span class=text>".t("Turning off maintenance mode")."</span>";
        echo "</div>";
    echo "<div>";

    echo "<div style='margin: 20px 25px;'><a href=# class=button id=installbtn style='width: 130px;'>".t("Update")."</a></div>";
    echo "<input type=progress value=0 id=prgs />";

    ?>
    <script>
    var one = 100/7;
    var version = "<?php echo Bootstrap::$version; ?>";
    function doUpdate(step){
        if(step === undefined) step = 1;

        if(step == 1) {
            $.getJSON("<?php echo getUpdateServer(); ?>apigetplugin/?code=core&phpver=<?php echo phpversion(); ?>", function(data){
                var v = cmpVersions(data.version, version);
                if(v > 0) {
                    $("#step1 .icon > i").attr("class", "fa fa-check green");
                    $("#latestver").html("("+data.version+")");
                    $("#step2").removeClass("disabled");
                    $("#prgs").val(one).trigger("change");
                    doUpdate(2);                    
                }else if(data.error !== undefined){
                    $("#step1 .icon > i").attr("class", "fa fa-times red");
                    messageBox(data.error);
                    cancel();
                }else{
                    $("#step1 .icon > i").attr("class", "fa fa-times red");
                    $("#latestver").html("("+data.version+")");
                    messageBox("<?php echo t("You running on latest version"); ?>");
                    cancel();
                }
            });
        }
        else if(step == 2) {
            $.getJSON("<?php echo Router::url(); ?>adminv2/plugins/install-system/?maintace_on&__type=ajax", function(data){
                if(data.ok) {
                    $("#step2 .icon > i").attr("class", "fa fa-check green");
                    $("#step3").removeClass("disabled");
                    doUpdate(3);
                }
            });
        }
        else if(step == 3) {
            $.getJSON("<?php echo Router::url(); ?>adminv2/plugins/install-system/?download&__type=ajax", function(data){
                if(data.ok) {
                    $("#step3 .icon > i").attr("class", "fa fa-check green");
                    $("#step4").removeClass("disabled");
                    doUpdate(4);
                }
            });
        }
        else if(step == 4) {
            $.getJSON("<?php echo Router::url(); ?>adminv2/plugins/install-system/?unzip&__type=ajax", function(data){
                if(data.ok) {
                    $("#step4 .icon > i").attr("class", "fa fa-check green");
                    $("#step5").removeClass("disabled");
                    doUpdate(5);
                }
            });
        }
        else if(step == 5) {
            $.getJSON("<?php echo Router::url(); ?>adminv2/plugins/install-system/?install&__type=ajax", function(data){
                if(data.ok) {
                    $("#step5 .icon > i").attr("class", "fa fa-check green");
                    $("#step6").removeClass("disabled");
                    doUpdate(6);
                }
            });
        }
        else if(step == 6) {
            $.getJSON("<?php echo Router::url(); ?>adminv2/plugins/install-system/?clean&__type=ajax", function(data){
                if(data.ok) {
                    $("#step6 .icon > i").attr("class", "fa fa-check green");   
                    $("#step7").removeClass("disabled");
                    doUpdate(7);
                }
            });
        }
        else if(step == 7) {
            $.getJSON("<?php echo Router::url(); ?>adminv2/plugins/install-system/?maintace_off&__type=ajax", function(data){
                if(data.ok) {
                    $("#step7 .icon > i").attr("class", "fa fa-check green");                    
                    cancel();
                    window.location.href = "<?php echo Router::url(); ?>adminv2/";
                }
            });
        }
        else {
            $("#step"+step+" .icon > i").attr("class", "fa fa-check green");
            $("#step"+(step + 1)+"").removeClass("disabled");
            doUpdate(step + 1);
        }
    }        
    function cancel() {
        $("#prgs").data("api").Hide();
        $("#installbtn").removeClass("disabled");
    }
    function install(s){
        if($(s).hasClass("disabled")) return;

        $("#step1").removeClass("disabled");
        $("#prgs").val(0).trigger("change");
        $("#prgs").data("api").Show();
        $($(s)).addClass("disabled");
        doUpdate(1);
    }
    $(function(){    
        $("#prgs").data("api").AppendToButton($("#installbtn"));
        $("#prgs").data("api").Hide();         
        $("#installbtn").on("click", function(e){ e.preventDefault(); install(this); });
    });
    </script>
    <?php
}
else if($action == "show" && $who == "activate") {    
    $t->root->module_manager->enable($_GET["id"]);
    header("location:".Router::url()."adminv2/plugins/");
}
else if($action == "show" && $who == "deactivate") {
    $t->root->module_manager->disable($_GET["id"]);
    header("location:".Router::url()."adminv2/plugins/");
}
else if($action == "show") {
    $plugins = $t->root->module_manager->getEnabled();    

	echo "<table class='table table-sm tablik tb-middle'>";
	echo "<tr><th width=10></th><th width=220>Plugin</th><th>".t("Description")."</th></tr>";
    $adresar = opendir(_ROOT_DIR . "/modules/");
	while ($dir = readdir($adresar)){			
		if( is_dir( _ROOT_DIR . "/modules/" . $dir ) and $dir!="." and $dir!=".." ){
            $doc = Utilities::getFileDocument(_ROOT_DIR . "/modules/" . $dir."/".$dir.".php");

            $params = [];
            if (preg_match_all('%^\s*\*\s*([A-Za-z\-]+):\s*(\N+)\s*$%im', $doc, $result, PREG_SET_ORDER, 0)) {
                $sParams = $result;
                foreach ($result as $sProp) {
                    $params[strtolower($sProp[1])] = $sProp[2];
                }
            }
            echo "<tr>";
                echo "<td style='vertical-align: top;'><input type=checkbox name='plugin_".$dir."'/></td>";
                echo "<td style='vertical-align: top;'>";
                    if(isset($params["name"])){
                        echo $params["name"];
                    }else{
                        echo $dir;
                    }    
                    echo "<div class=small>";
                        if(in_array($dir, $plugins)) {
                            echo "<a href='".Router::url()."adminv2/plugins/show-deactivate/".$dir."'>".t("Deactivate")."</a>";
                        }else{
                            echo "<a href='".Router::url()."adminv2/plugins/show-activate/".$dir."'>".t("Activate")."</a>";
                        }
                        echo " | <a href=# class=red>".t("Uninstall")."</a>";
                    echo "</div>";
                echo "</td>";
                echo "<td style='vertical-align: top;'>";
                    if(isset($params["description"])){
                        echo $params["description"];
                    }else{
                        echo "-";
                    }
                    echo "<div class=small>";
                        if(!isset($params["version"])){ $params["version"] = "0"; }
                        echo t("Version").": ".$params["version"]." | ";
                        if(isset($params["author"])){
                            echo t("Author").": ".$params["author"];
                        }
                    echo "</div>";
                echo "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
}
else if($action == "install") {        
    if(isset($_FILES["plugin"])) {
        $data = array();

        $upload = Utilities::processUploadFile($_FILES["plugin"], "plugins/install/", "plugin_".time(), array("zip"));
        if($upload["error"] == null)
            $data["fileid"] = $upload["filename"];
        else
            $data["error"] = $upload["error"];

        echo json_encode($data);
        exit();
    }    
    if(isset($_GET["unpack"])) {
        $zip = new ZipArchive();
        $zip->open(_ROOT_DIR."/upload/plugins/install/".$_GET["fileid"]);
        $contents = Utilities::getFilesContentFromZip($zip);
        $data = Config::sload($contents[".manifest"]);
        $isSystem = $data["type"] == "SYSTEM";

        //move actual files to _backup folder in module

        if($isSystem) {
            $destinationFolder = _ROOT_DIR;
        }else{
            $destinationFolder = _ROOT_DIR."/modules/".$data["code"]."/";
        }

        if(!$isSystem && !is_dir(_ROOT_DIR."/modules/".$data["code"]."/")) {
            if (!mkdir(_ROOT_DIR."/modules/".$data["code"]."/", 0777, true)) {
                echo json_encode(array("state" => "error", "error" => t("Can't create module folder")));
                //do restore backup
                exit();
            }
        }

        $zip->extractTo($destinationFolder);
        $zip->close();
        unlink($destinationFolder."/.manifest");

        if(!$isSystem) {
            if(is_dir(_ROOT_DIR."/modules/".$data["code"]."/views/")) {
                if(!is_dir(_ROOT_DIR."/views/".$data["code"]."/")) {
                    if (!mkdir(_ROOT_DIR."/views/".$data["code"]."/", 0777, true)) {
                        echo json_encode(array("state" => "error", "error" => t("Can't create module views folder")));
                        //do restore backup
                        exit();
                    }
                }

                //if(!rename(_ROOT_DIR."/modules/".$data["code"]."/views/", _ROOT_DIR."/views/".$data["code"]."/")){
                if(!Utilities::moveFilesToDirectory(_ROOT_DIR."/modules/".$data["code"]."/views/", _ROOT_DIR."/views/".$data["code"]."/")){
                    echo json_encode(array("state" => "error", "error" => t("Can't move module views folder")));
                    //do restore backup
                    exit();
                }
            }

            if(file_exists(_ROOT_DIR."/modules/".$data["code"]."/".$data["code"]."Controller.php")) {
                if(!rename(_ROOT_DIR."/modules/".$data["code"]."/".$data["code"]."Controller.php", _ROOT_DIR."/controllers/".$data["code"]."Controller.php")){
                    echo json_encode(array("state" => "error", "error" => t("Can't move module controller")));
                    //do restore backup
                    exit();
                }
            }
        }

        echo json_encode(array("state" => "ok", "module" => $data["code"]));
        exit();
    }
    if(isset($_GET["installing"])) {
        $zip = new ZipArchive();
        $zip->open(_ROOT_DIR."/upload/plugins/install/".$_GET["fileid"]);
        $contents = Utilities::getFilesContentFromZip($zip);
        $data = Config::sload($contents[".manifest"]);
        $isSystem = $data["type"] == "SYSTEM";

        if($isSystem) {
            if(file_exists(_ROOT_DIR."/update.php")) {
                include_once _ROOT_DIR."/update.php";
                if(function_exists("_system_update")) {
                    _system_update();
                }
            }
        }else{
            $out = $t->root->module_manager->hook_call("module.".$_GET["installing"].".install", array());
            if($out["called"] == 0)
                echo json_encode(array("state" => "ok"));
            else {
                $state = $out["output"]["state"] == true;
                echo json_encode(array("state" => $state?"ok":"error", "error" => $out["output"]["error"]));
            }
        }
        exit();
    }
    if(isset($_GET["cleanup"])) {
        if(file_exists(_ROOT_DIR."/update.php")) {
            unlink(_ROOT_DIR."/update.php");
        }
        unlink(_ROOT_DIR."/upload/plugins/install/".$_GET["fileid"]);
        echo json_encode(array("state" => "ok"));
        exit();
    }
    if(isset($_GET["ok"])) {
        $t->root->page->error_box(t("Plugin has been installed"), "ok", true);
    }
    if(isset($_GET["cancel"])) {
        unlink(_ROOT_DIR."/upload/plugins/install/".$_GET["cancel"]);
        header("location:?");
    }
    if(isset($_GET["download"])) {
        $zip = new ZipArchive();
        $zip->open(_ROOT_DIR."/upload/plugins/install/".$_GET["fileid"]);
        $contents = Utilities::getFilesContentFromZip($zip);
        $data = Config::sload($contents[".manifest"]);
        $zip->close();

        unlink(_ROOT_DIR."/upload/plugins/install/".$_GET["fileid"]);
        $filename = "plugin_".time().".zip";
        file_put_contents(_ROOT_DIR."/upload/plugins/install/".$filename, fopen($server."apidownloadplugin/?code=".$_GET["download"]."&pass=".$data["pass"], 'r'));
        header("location:?fileid=".$filename."&pass=".$data["pass"]);
    }
    if(isset($_GET["download_plugin"])) {
        $filename = "plugin_".time().".zip";
        file_put_contents(_ROOT_DIR."/upload/plugins/install/".$filename, fopen($server."apidownloadplugin/?code=".$_GET["download_plugin"]."&pass=".$_GET["pass"], 'r'));
        header("location:?fileid=".$filename."&pass=".$_GET["pass"]);
    }

    if(isset($_GET["fileid"])) {
        echo "<h3>".t("Plugin info")."</h3>";
        $zip = new ZipArchive();
        $zip->open(_ROOT_DIR."/upload/plugins/install/".$_GET["fileid"]);
        $files = [];
        $contents = Utilities::getFilesContentFromZip($zip, $files);
        $zip->close();

        if(!in_array(".manifest", $files)){
            $errors[] = "Missing .manifest file in root of the archive.";
        }

        $code = "";
        $data = Config::sload($contents[".manifest"]);
        
        $result = json_decode(file_get_contents($server."apigetplugin/?code=".$data["code"]."&pass=".$_GET["pass"]), true);

        foreach($errors as $error){
            $t->root->page->error_box($error, "error");
        }

        echo "<div class='content padding'>";
            echo "<div class='left-side'>";         
                if(!isset($result["error"])) {
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Name")."</label><div class=\"col-sm-9\">";
                        echo "<input type=text readonly class=\"form-control\" value='".$result["name"]."' name=name>";
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Latest version")."</label><div class=\"col-sm-9\">";
                        echo $result["version"];
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Changeset")."</label><div class=\"col-sm-9\">";
                        echo $result["descs"];
                    echo "</div></div>";     
                } else {
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\"></label><div class=\"col-sm-9\">";
                        echo t("This module has no online version");
                    echo "</div></div>";
                }
                echo "<hr/><br/>";
                echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Version")."</label><div class=\"col-sm-9\">";
                    echo $data["version"];
                echo "</div></div>";
                echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Code")."</label><div class=\"col-sm-9\">";
                    echo $data["code"];
                echo "</div></div>";       
                echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\"></label><div class=\"col-sm-9\">";
                    if(version_compare($result["version"], $data["version"]) == 1) {
                        echo "<a href='?download=".$data["code"]."&fileid=".$_GET["fileid"]."' onclick=\"btnLoading($(this), true);\" class=button>Download ".$result["version"]."</a> ";
                    }
                    echo "<a href=# onclick='install();return false;' id=installbtn class=button>Install</a> <a href='?cancel=".$_GET["fileid"]."' class=button>Cancel</a>";
                    echo "<div style='margin:5px 0px;max-width:200px;'><input type=progress id=pb value=0 /></div>";
                echo "</div></div>";                
            echo "</div>";
        echo "</div>";
    }else{
        echo "<h3>".t("Drop update file to install manualy")."</h3>";
        echo "<form action=# method=post id=uploadplugin>";
            echo "<input type=\"file\" name=\"plugin\"/>";
        echo "</form>";

        if(isset($_GET["pass"])) $pass = $_GET["pass"]; else $pass = "";

        echo "<form action='".Router::url()."adminv2/plugins/install/' method=get class='m-2'>";
            echo "<label>".t("Password")."</label> <input type=text name=pass value='".$pass."'> <input type=submit value='".t("Show")."' class='btn btn-primary'>";
        echo "</form>";
        $result = json_decode(file_get_contents($server."apilist/?pass=".$pass), true);

        //Utilities::vardump($result);

        echo "<div class=plugins-flex-grid>";
            foreach($result["result"] as $i => $plugin) {
                if($plugin["code"] == "core") continue;

                echo '<div class="plugin-preview">';
                    echo '<div class="top-side" style="background-image: url('.$result["url"].$plugin["id"].'/graphics/'.$plugin["data"]["background"].');">';
                        echo '<div class="logo" style="background-image: url('.$result["url"].$plugin["id"].'/graphics/'.$plugin["data"]["icon"].');"></div>';
                        echo '<div class="content">';
                            echo '<div class="title '.($plugin["data"]["smalltitle"]==1?"small":"").'" id="plugin-title" title="">'.$plugin['name'].'</div>';
                            echo '<a href="#" class="btn btn-primary plugin-get" onclick="btnLoading($(this), true);downloadPlugin(\''.$plugin["code"].'\');return false;">'.t("Get").'</a>';
                        echo '</div>';
                    echo '</div>';
                    echo '<div class="bottom-side">';
                        echo '<div class="description">'.str_replace("\n", "<br>", $plugin['data']['description']).'</div>';
                    echo '</div>';
                echo '</div>';
            }
        echo '</div>';
    }

    ?>
    <script>
        var upload = null;
        var percent = 100/3;

        function downloadPlugin(code){
            window.location.href = "<?php echo Router::url(); ?>adminv2/plugins/install/?download_plugin="+code+"&pass=<?php echo $_GET["pass"]; ?>";
        }

        $(function(){
            upload = new Uploader(
                "<?php echo Router::url() ?>adminv2/plugins/install/?__type=ajax&upload", 
                $("#uploadplugin"), 
                $("[name='plugin']"), {
                    autosubmit: true,
                    maxfiles: 1,
                    response: "json"
                }
            );
            upload.AfterUpload(function(data){
                if(data.error != undefined && data.error != "") {
                    messageBox(data.error);
                }else{
                    window.location.href = "?fileid=" + data.fileid;
                }
            });
                
            if($("#pb").length != 0) {
                $("#pb").data("api").Hide();
                $("#pb").data("api").AppendToButton($("#installbtn"));
            }
        });
        function install(){
            if($("#installbtn").hasClass("disabled")) return;

            $("#pb").val(0).trigger("change");
            $("#pb").data("api").Show();
            $("#installbtn").addClass("disabled");
            /*
            setTimeout(function(){$("#pb").val(27).trigger("change");}, 1000);
            setTimeout(function(){$("#pb").val(50).trigger("change");}, 5000);
            setTimeout(function(){$("#pb").val(100).trigger("change");}, 8000);
            */           
            $.getJSON("<?php echo Router::url() ?>adminv2/plugins/install/?__type=ajax&unpack&fileid=<?php echo $_GET["fileid"]; ?>", function(data){
                if(data.state == "ok") {
                    $("#pb").val(percent).trigger("change");                    
                    var modul = data.module;
                    $.getJSON("<?php echo Router::url() ?>adminv2/plugins/install/?__type=ajax&installing="+modul+"&fileid=<?php echo $_GET["fileid"]; ?>", function(data){
                        if(data.state == "ok") {
                            $("#pb").val(percent*2).trigger("change");
                            $.getJSON("<?php echo Router::url() ?>adminv2/plugins/install/?__type=ajax&fileid=<?php echo $_GET["fileid"]; ?>&cleanup="+modul, function(data){
                                if(data.state == "ok") {
                                    $("#pb").val(percent*3).trigger("change");
                                    window.location.href = "?ok";
                                }else{
                                    messageBox(data.error);                                    
                                }
                                $("#pb").data("api").Hide();
                                $("#installbtn").removeClass("disabled");
                            });
                        }else{
                            messageBox(data.error);
                            $("#pb").data("api").Hide();
                            $("#installbtn").removeClass("disabled");
                        }
                    });
                }else{
                    messageBox(data.error);
                    $("#pb").data("api").Hide();
                    $("#installbtn").removeClass("disabled");
                }
            });
        }
    </script>
    <?php
}
//#not:release
else if($who == "approve" && $action == "admin") {
    $result = dibi::query("SELECT * FROM plugin_list WHERE id = %i", $_GET["id"])->fetch();
    if($result==null) {
        header("location:".Router::url()."admin/plugins/admin/");
    }

    dibi::query('UPDATE :prefix:plugin_list SET ', array("state" => 1, "published" => time()), "WHERE id = %i", $result["id"]);
    dibi::query('UPDATE :prefix:plugin_list SET ', array("locked" => 0), "WHERE id = %i", $result["pid"]);

    header("location:".Router::url()."admin/plugins/admin-manifest/".$result["id"]);
}
else if($who == "manifest" && $action == "admin") {
    $result = dibi::query("SELECT * FROM plugin_list WHERE id = %i", $_GET["id"])->fetch();
    if($result==null) {
        header("location:".Router::url()."admin/plugins/admin/");
    }

    $data = array(
        "id" => $result["pid"],
        "version" => $result["version"],
        "code" => $result["code"],
        "file_mapping" => Config::sload($result["mapping"]),
        "pass" => $result["pass"],
        "type" => ($result["type"] == 7)?"SYSTEM":$result["type"]
    );

    $soubor = fopen(_ROOT_DIR.'/upload/plugins/'.$result["pid"].'/.manifest', "w+");
	fwrite($soubor, Config::ssave($data));
	fclose($soubor);

    header("location:".Router::url()."admin/plugins/admin-edit/".$result["id"]);
}
else if($who == "download" && $action == "admin") {
    $result = dibi::query("SELECT * FROM plugin_list WHERE id = %i", $_GET["id"])->fetch();
    if($result==null) {
        header("location:".Router::url()."admin/plugins/admin/");
    }

    $filename = _ROOT_DIR."/temp/".$result["code"].".zip";
    copy(_ROOT_DIR.'/upload/plugins/'.$result["pid"].'/'.$result["hash"].'.zip', $filename);
    $zip = new ZipArchive();
    $zip->open($filename);
    $zip->addFile(_ROOT_DIR.'/upload/plugins/'.$result["pid"].'/.manifest', ".manifest");
    $zip->close();

    header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
    header("Cache-Control: public"); // needed for internet explorer
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: Binary");
    header("Content-Length:".filesize($filename));
    header("Content-Disposition: attachment; filename=".$result["code"].".zip");
    echo file_get_contents($filename);

    die();
}
else if($who == "edit" && $action == "admin") {
    $result = dibi::query("SELECT * FROM plugin_list WHERE id = %i", $_GET["id"])->fetch();    
    if($result==null) {
        echo "<h1>Plugin not found</h1>";
        exit;
    }
    $result_main = dibi::query("SELECT * FROM plugin_list WHERE pid = %i", $result["pid"], "ORDER BY id DESC")->fetch();
    if($result_main["id"] != $result["id"]) {
        header("location:".Router::url()."admin/plugins/admin-edit/".$result_main["id"]);
    }

    echo "<h1>".$result["name"]."</h1>";

    $pid = $result["pid"];
    $data = Config::sload($result["data"]);

    echo "<div class='content padding'>";
            echo "<div class='left-side'>";
                echo "<form method=post>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Name")."</label><div class=\"col-sm-9\">";
                        echo "<input type=text class=\"form-control\" value='".$result["name"]."' name=name>";
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Code")."</label><div class=\"col-sm-9\">";
                        echo "<input type=text class=\"form-control\" value='".$result["code"]."' name=code>";
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Type")."</label><div class=\"col-sm-9\">";
                        $select = new Select("type", "type", "100%");
                        $types = ["Module", "Template", "Library"];
                        if($result["type"] == 7) { $types[7] = "System"; }
                        foreach($types as $n => $t){
                            $select->addOption($t, $n);
                        }
                        $select->select($result["type"]);
                        echo $select->render();
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Changeset")."</label><div class=\"col-sm-9\">";
                        echo "<textarea class=\"form-control tinimce_mini\" rows=5 name=prescript>".$result["descs"]."</textarea>";
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Icon")."</label><div class=\"col-sm-9\">";
                        if($data["icon"] != "") {
                            echo "<img src='".Router::url()."upload/plugins/".$pid."/graphics/".$data["icon"]."' style='max-width:120px;'/>";
                        }else { echo t("Nothing"); }
                    echo "</div></div>";
                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Background")."</label><div class=\"col-sm-9\">";
                        if($data["background"] != "") {
                            echo "<img src='".Router::url()."upload/plugins/".$pid."/graphics/".$data["background"]."' style='max-width:120px;'/>";
                        }else { echo t("Nothing"); }
                    echo "</div></div>";

                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Description")."</label><div class=\"col-sm-9\">";
                        echo "<textarea class=\"form-control tinimce_mini\" name=description rows=5>".$data["description"]."</textarea>";
                    echo "</div></div>";

                    echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Pre install script")."</label><div class=\"col-sm-9\">";
                        echo "<textarea class=\"form-control\" name=prescript>".$data["prescript"]."</textarea>";
                    echo "</div></div>";

                    echo "<div>";
                        $isSystem = $result["type"] == 7;
                        $file = null;
                        if(file_exists(_ROOT_DIR.'/upload/plugins/'.$pid.'/'.$result["hash"].'.zip')){
                            $file = $result["hash"].'.zip';
                        }
                        
                        if($file != null) {
                            $za = new ZipArchive();
                            $za->open(_ROOT_DIR.'/upload/plugins/'.$pid.'/'.$file);

                            if($za->numFiles == 0){
                                $errors[] = "There is no files in the zip archive!";
                            }

                            $errorFiles = [];
                            $files = [];
                            for ($i = 0; $i < $za->numFiles; $i++) {
                                $name = $za->getNameIndex($i);
                                $files[] = $name;
                                if(Strings::endsWith($name, ".exe")) {
                                    $errors[] = "File ".$name." can't be exe!";
                                }
                            }
                            
                            if(!$isSystem && !in_array($result["code"].".php", $files)){
                                $errors[] = "Missing main file in root of the archive ".$result["code"].".php";
                            }

                            $size = Utilities::convertBtoMB(filesize(_ROOT_DIR.'/upload/plugins/'.$pid.'/'.$file));
                            if($size > 32) {
                                $errors[] = "Size of the plugin can't be more then 32 MB";
                            }

                            $root = array('name'=>'/', 'children' => array(), 'href'=>'');
                            foreach($files as $file){
                                Utilities::StoreFile($file, $root);
                            }

                            if(count($errors) == 0) {
                                echo "<div><b>".t("Files in plugin")."</b><hr/></div>";
                                Utilities::DrawFiles($root, null, null, 2);
                            }else{
                                foreach($errors as $n=>$error) {
                                    echo "<div class=error>".$error."</div>";
                                }
                            }
                        }    
                    echo "</div>";
                    
                    /*echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\"></label><div class=\"col-sm-9\">";                        
                        echo "<label><input type=checkbox value='1' ".Utilities::check($result["internal"] == 1)." name=internal> ".t("Internal")."</label>";
                    echo "</div></div>";*/
                    echo "<div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-3'></label><div class='col-12 col-sm-9'><input type=submit class='blue btn btn-primary hide-mobile' name=update id='btn-update' value='".t("edit")."'></div></div>";	
                echo "</form>";
            echo "</div>";
            echo "<div class=right-side>";
                echo "<div class='expandable' id='cat_1'>";
                echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_1', 'closed');return false;\">".t("Information")."</a></div>";
                    echo "<div>";
                        echo "<b class=up>".t("State")."</b>";
                        echo "<div>";
                            $author = User::get($result["user"]);
                            if($result["state"] == 0 && $result["locked"]==1) {
                                echo "<b class=red>".t("Locked")."</b>";
                            }else if($result["state"] == 0){
                                echo "<b class=red>".t("Not published")."</b>";
                            }else{
                                echo "<b class=green>".t("Published")."</b>";
                            }                            
                            if($result["state"] == 0) {
                                echo "<hr class=spacer />";
                                echo "<div>";
                                    echo "<a href='".Router::url()."admin/plugins/admin-approve/".$result["id"]."/' class=button>".t("Approve plugin")."</a>";
                                echo "</div>";
                            }
                            echo "<hr class=spacer />";
                            echo "<b class=up>".t("Author")."</b>";
                            echo "<div><a href='".Router::url()."admin/users/edit/".$author["id"]."'>".$author["nick"]."</a></div>";
                            echo "<b class=up>".t("Version")."</b>";
                            echo "<div>".$result["version"]."</div>";
                            if($result["pass"] != ""){
                                echo "<b class=up>".t("Password")."</b>";
                                echo "<div>".$result["pass"]."</div>";
                            }
                            if($result["published"] != NULL) {
                                echo "<b class=up>".t("Published")."</b>";
                                echo "<div>".Strings::str_time($result["published"])."</div>";
                            }
                            echo "<hr class=spacer />";
                            echo "<a href='".Router::url()."admin/plugins/admin-manifest/".$result["id"]."/' class=button>".t("Regenerate manifest")."</a>";
                            echo "<hr class=spacer />";
                            echo "<a href='".Router::url()."admin/plugins/admin-download/".$result["id"]."/?__type=ajax' class=button>".t("Download plugin")."</a>";
                        echo "</div>";
                    echo "</div>";
                echo "</div>"; 
            echo "</div>";
        echo "</div>";
}
else if($action == "admin") {

    $paginator = new Paginator(10, Router::url()."adminv2/plugins/admin/?page=(:page)");
    $result = $paginator->query("SELECT pl.id, plv.id as pid, plv.name, plv.descs, plv.type, plv.code, pl.created, plv.created as updated, pl.user, plv.state, plv.version FROM `plugin_list` as pl LEFT JOIN plugin_list as plv ON plv.id = (SELECT pi.id FROM plugin_list as pi WHERE pi.pid = pl.id ORDER BY pi.id DESC LIMIT 1) WHERE pl.pid IS NULL ORDER BY plv.created DESC");    
    
    echo "<div class='content padding'>";

    echo $paginator->getPaginator();

    echo "<div class=row>";
        foreach ($result as $n => $row) {
            echo "<div class='box_rounded col-md-6'>";
                echo "<div>";
                    echo "<div class='title title-icon' style='background-image:url(".Router::url()."/upload/plugins/".$row["id"]."/graphics/icon.jpg);'>".$row["name"]."</div>";
                    echo "<div class=desc>".$row["descs"]."</div>";
                    echo "<div class=action>";
                        echo "<div class=right><a href='".Router::url()."admin/plugins/admin-edit/".$row["pid"]."'>Manage</a></div>";
                        echo "Last update: ".Strings::str_time($row["updated"]).", ";
                        echo "Version: ".$row["version"];
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        }
    echo "</div>";

    echo $paginator->getPaginator();
    
    echo "</div>";
}
//#endnot