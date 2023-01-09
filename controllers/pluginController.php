<?php

class PluginController extends Controller {

    public function __construct($root){
        parent::__construct($root, true);

        $this->user = User::current();
        $this->root = $root;
        $this->flash = $this->getContainer()->get('flash');                
    }   

    private $types = ["Module", "Template", "Library"];
    private $plugin = NULL;

    private function getPlugin(){
        if($this->plugin != null) return $this->plugin;

        $this->plugin = dibi::query("SELECT * FROM plugin_list WHERE id = %i", $_GET["id"], "AND user = %i", $this->user["id"])->fetch();
        return $this->plugin;
    }


    public function before($action, $arguments, $params){
        //Utilities::vardump($arguments);
        /*if(strtolower($action) != "messageerror" && strtolower($action) == "edit") {
            //return $this->RedirectToAction("MessageError", $arguments);
        }*/

        if(isset($arguments["id"])){            
            $result = dibi::query("SELECT count(*) FROM plugin_list WHERE id = %i", $arguments["id"], "AND user = %i", $this->user["id"])->fetchSingle();
            if($result == NULL || $result == 0) {               
                if(isset($params["type"]) && (strtolower($params["type"]) == "json" || strtolower($params["type"]) == "api")) {
                    return $this->Json(array("error" => "The plugin was not found!"));
                }                
                return $this->Error("Not found", "The plugin was not found!", 404);
            }
        }
    }

    /**
     * @method GET
     * @type api
     */
    public function ApiList($pass = "", $page = 1){
        $this->Cors();

        $list = [];

        $page -= 1;
        $pSize = 30;
        $pStart = $page * $pSize;

        $results = dibi::query("SELECT pl.id, plv.name, pl.descs as plugin_desc, plv.descs, plv.type, plv.code, pl.created, plv.created as updated, pl.user, plv.state, plv.version, plv.data FROM `plugin_list` as pl LEFT JOIN plugin_list as plv ON plv.id = (SELECT pi.id FROM plugin_list as pi WHERE pi.pid = pl.id ORDER BY pi.id DESC LIMIT 1) WHERE pl.pid IS NULL AND plv.pass = %s", $pass, " AND plv.state != 0 ORDER BY plv.created DESC LIMIT ", $pStart, ",", $pSize);
        
        foreach($results as $res) {
            $list[] = $res;
            $list[count($list)-1]["data"] = Config::sload($list[count($list)-1]["data"]);
        }

        return $this->Json(array("result" => $list, "page" => $page + 1, "url" => Router::url()."upload/plugins/"));
    }

    /**
     * @method GET
     * @type api
     */
    public function ApiGetPlugin($code, $pass = ""){
        $this->Cors();

        $result = dibi::query("SELECT * FROM plugin_list WHERE code = %s", $code, " AND state = %i", 1, " AND pass = %s", $pass," ORDER BY id DESC LIMIT 1")->fetch();

        if($result == null) {
            return $this->Json(array("error" => "Plugin not found"));
        }

        return $this->Json(array(
            "name" => $result["name"],
            "code" => $result["code"],
            "descs" => $result["descs"],
            "version" => $result["version"],
            "published" => $result["published"],
            "data" => Config::sload($result["data"]),
            "url" => Router::url()."upload/plugins/".$result["pid"]."/",
            "download" => Router::url()."plugin/apidownloadplugin/?code=".$result["code"]
        ));
    }

    /**
     * @method GET
     * @type api
     */
    public function ApiDownloadPlugin($code, $pass = ""){
        $this->Cors();
        
        $result = dibi::query("SELECT * FROM plugin_list WHERE code = %s", $code, " AND state = %i", 1, " AND pass = %s ", $pass," ORDER BY id DESC LIMIT 1")->fetch();

        if($result == null) {
            return $this->Json(array("error" => "Plugin not found"));
        }

        $filename = _ROOT_DIR."/temp/".$result["code"]."_".$result["version"].".zip";
        if(!file_exists($filename)){
            copy(_ROOT_DIR.'/upload/plugins/'.$result["pid"].'/'.$result["hash"].'.zip', $filename);
            $zip = new ZipArchive();
            $zip->open($filename);
            $zip->addFile(_ROOT_DIR.'/upload/plugins/'.$result["pid"].'/.manifest', ".manifest");
            $zip->close();
        }

        return $this->File($filename, "application/zip", $result["code"]."_".$result["version"].".zip");
    }

    /**
    * @method GET
    */
    public function Show(){
        $model = array();

        $model["items"] = dibi::query("SELECT * FROM plugin_list WHERE user = %i", $this->user["id"], "AND pid is NULL");

        return $this->View($model);
    }

    /**
     * @method GET
     * @post EditPost
     */
    public function Edit($id){
        $result = $this->getPlugin();
        if($result["type"] == 7) {
            $this->types = array();
            $this->types[7] = "System";
        }
        return $this->View(array("model" => $result, "types" => $this->types));
    }

    /**
     * @method POST
     */
    public function EditPost($id, $name, $type){
        $result = $this->getPlugin();
        $data = array(
            "name" => $name,
            "type" => $type
        );

        dibi::query('UPDATE :prefix:plugin_list SET ', $data, 'WHERE `user`=%i', $this->user["id"], "AND id = %i", $id);
        $this->flash->push("Plugin has been edited", Flash::$OK);

        return $this->Redirect("plugin/edit/?id=".$id);
    }

    /**
     * @method POST
     * @type JSON
     */
    public function FileGetRemap($id, $path) {
        $result = $this->getPlugin();

        $mapping = Config::sload($result["mapping"]);

        if(isset($mapping[$path])) {
            return $this->Json($mapping[$path]);
        }

        return $this->Json(array("path" => ""));
    }

    /**
     * @method POST
     * @type JSON
     */
    public function FileSetRemap($id, $path, $remap) {
        $result = $this->getPlugin();

        $mapping = Config::sload($result["mapping"]);

        if(!isset($mapping[$path])) {
            $mapping[$path] = array("path" => "");
        }
        if($remap != "") {
            $remap = str_replace("\\", "/", $remap);
            if(!Strings::endsWith($remap, "/")){ $remap.="/"; }
            $mapping[$path]["path"] = $remap;
        }else{
            $mapping[$path]["path"] = "";
        }

        $data = array("mapping" => Config::ssave($mapping));
        dibi::query('UPDATE :prefix:plugin_list SET ', $data, 'WHERE `user`=%i', $this->user["id"], "AND id = %i", $id);

        return $this->Json(array("ok" => true));
    }

    /**
    * @method GET
    */
    public function Files($id){
        $result = $this->getPlugin();

        $model = array(
            "model" => $result
        );

        $file = null;
        if(file_exists(_ROOT_DIR.'/upload/plugins/'.$result["id"].'/'.$result["code"].'.zip')){
            $file = $result["code"].'.zip';
        }
        $model["file"] = $file;

        $errors = [];
        if($file != null) {
            $za = new ZipArchive();
            $za->open(_ROOT_DIR.'/upload/plugins/'.$result["id"].'/'.$file);

            if($za->numFiles == 0){
                $errors[] = "There is no files in the zip archive!";
            }

            $errorFiles = [];            
            $files = [];
            for ($i = 0; $i < $za->numFiles; $i++) {
                $name = $za->getNameIndex($i);
                //Utilities::vardump($za->statIndex($i));
                $files[] = $name;
                if(Strings::endsWith($name, ".exe")) {
                    $errors[] = "File ".$name." can't be exe!";
                }
            }
            
            if($result["type"] == 7) {

            }else{
                if(!in_array($result["code"].".php", $files)){
                    $errors[] = "Missing main file in root of the archive ".$result["code"].".php";
                }
            }

            $model["files"] = $files;
            $model["errorFiles"] = $errorFiles;
            $model["size"] = Utilities::convertBtoMB(filesize(_ROOT_DIR.'/upload/plugins/'.$result["id"].'/'.$file));

            if($model["size"] > 32) {
                $errors[] = "Size of the plugin can't be more then 32 MB";
            }

            $root = array('name'=>'/', 'children' => array(), 'href'=>'');
            foreach($files as $file){
                $this->store_file($file, $root);
            }
            $model["tree"] = $root;
        }
        
        $model["errors"] = $errors;

        return $this->View($model);
    }

    private function store_file($filename, &$parent){
        if(empty($filename)) return;
    
        $matches = array();
    
        if(preg_match('|^([^/]+)/(.*)$|', $filename, $matches)){
    
            $nextdir = $matches[1];
    
            if(!isset($parent['children'][$nextdir])){
                $parent['children'][$nextdir] = array('name' => $nextdir,
                    'children' => array(),
                    'href' => $parent['href'] . '/' . $nextdir);
            }
    
            $this->store_file($matches[2], $parent['children'][$nextdir]);
        } else {
            $parent['children'][$filename] = array('name' => $filename,
                'size' => '...', 
                'href' => $parent['href'] . '/' . $filename);
        }
    }

    /**
    * @type JSON
    */
    public function FilesUpload($id, $file) {
        $result = $this->getPlugin();

        $allowed =  array('zip');
        $error = "";
        $newfilename = "";        

        $filene = "";
        if($file["error"][0] == 0){
            $filename = $file["name"][0];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if($filename == ""){
				$error = t('No file sent.');
			}elseif(!in_array($ext, $allowed) ) {
				$error = t("The file can only be"). " " . implode(", ", $allowed).".";
			}else{
                $newnam = $result["code"];//sha1($filename.time());
                $filene = _ROOT_DIR.'/upload/plugins/'.$id.'/'.$newnam.'.'.$ext;
                $newfilename = $newnam.'.'.$ext;

                if(!file_exists(_ROOT_DIR.'/upload/plugins/'.$id.'/')){
                    mkdir(_ROOT_DIR.'/upload/plugins/'.$id.'/', 0777, true);
                }
                
                if(!move_uploaded_file($file['tmp_name'][0], $filene)) {
					$error = t("Upload error.");
				}
            }
        }else{
            $error = Utilities::getFileUploadError($file["error"][0]).".";
        }
        
        if($error != "")
            return $this->Json(array("file" => array("error" => $error)));

        $za = new ZipArchive();
        $za->open($filene);
        if($za->numFiles != 0){
            $fileCrc = [];
            for ($i = 0; $i < $za->numFiles; $i++) {
                $name = $za->getNameIndex($i);
                $fileCrc[$name] = $za->statIndex($i)["crc"];
            }
        }
        $data = Config::sload($result["data"]);
        $data["filecrc"] = $fileCrc;
        dibi::query('UPDATE :prefix:plugin_list SET ', array("data" => Config::ssave($data)), 'WHERE `id`=%i', $result["id"]);

        return $this->Json(array("file" => $newfilename));
    }

    /**
     * @method GET
     */
    public function Design($id) {
        $result = $this->getPlugin();

        $model = array(
            "model" => $result,
            "data" => Config::sload($result["data"])
        );

        return $this->View($model);
    }

    /**
     * @type JSON
     */
    public function DesignSave($id, $description, $file_icon = null, $file = null, $smalltitle = 0) {
        $result = $this->getPlugin();

        $data = Config::sload($result["data"]);

        $uploadIcon = null; $uploadBackground = null;

        $data["smalltitle"] = $smalltitle;
        $data["description"] = $description;

        if($file_icon != null) {
            $uploadIcon = Utilities::processUploadFile($file_icon, "plugins/".$id."/graphics/", "icon", array("png","jpg","gif"));
            if($uploadIcon["error"] == null)
                $data["icon"] = $uploadIcon["filename"];
        }
        if($file != null) {
            $uploadBackground = Utilities::processUploadFile($file, "plugins/".$id."/graphics/", "background", array("png","jpg","gif"));
            if($uploadBackground["error"] == null)
                $data["background"] = $uploadBackground["filename"];
        }

        $_data = array(
            "data" => Config::ssave($data)
        );

        dibi::query('UPDATE :prefix:plugin_list SET ', $_data, 'WHERE `user`=%i', $this->user["id"], "AND id = %i", $id);        

        return $this->Json(
            array(
                "file_icon" => $uploadIcon,
                "file" => $uploadBackground,
            )
        );
    }

    /**
     * @method GET
     * @post ScriptsPost
     */
    public function Scripts($id){
        $result = $this->getPlugin();
        return $this->View(array("model" => $result));
    }

    /**
     * @method POST
     */
    public function ScriptsPost($id){
        $result = $this->getPlugin();

        $data = array(
            "script" => Config::ssave(
                array("preinstall" => $_POST["preinstall"])
            )
        );

        dibi::query('UPDATE :prefix:plugin_list SET ', $data, 'WHERE `user`=%i', $this->user["id"], "AND id = %i", $id);

        return $this->Redirect("plugin/scripts/?id=".$id);
    }

    /**
     * @method GET
     * @post NewPost
     */
    public function New() {
        return $this->View();
    }

    /**
     * @method POST
     */
    public function NewPost($name, $code) {
        $results = dibi::query("SELECT * FROM plugin_list WHERE code = %s", $code);
        if($results->count() > 0) {
            $this->flash->push(t("This code is alerady taken"), Flash::$ERROR);
            return $this->Redirect("/plugin/new");
        }

        $data = array(
            "name" => $name,
            "type" => 0,
            "code" => $code,
            "created" => time(),
            "user" => User::current()["id"]            
        );
        dibi::query('INSERT INTO :prefix:plugin_list', $data);
        return $this->Redirect("/plugin/edit/?id=".dibi::getInsertId());
    }

    /**
     * @method GET
     * @type JSON
     */
    public function Versions($id){
        $result = $this->getPlugin();

        $versions = [];
        $results = dibi::query("SELECT * FROM plugin_list WHERE pid = %i", $id, "AND user = %i", $this->user["id"], "ORDER BY id DESC");
        $rollback = 0;
        foreach($results as $res) {
            if($res["state"] == 1 && $rollback == 0) {
                $rollback = 1;
            } else if($rollback == 1) {
                $rollback = 2;
            } else if($rollback == 2) {
                $rollback = 3;
            }

            $versions[] = array(
                "id" => $res["id"],
                "ver" => $res["version"],
                "created" => $res["created"],
                "state" => $res["state"],
                "rollback" => ($rollback==2)
            );            
        }

        return $this->View(array("model" => $result, "versions" => $versions));
    }

    public function Publish($id) {
        $result = $this->getPlugin();
        $plugins = dibi::query("SELECT * FROM plugin_list WHERE pid = %i", $id, "AND user = %i", $this->user["id"])->count();

        return $this->View(array("model" => $result, "plugins" => $plugins, "lates" => $this->getLatest($id)));
    }

    private function getLatest($id) {
        return dibi::query("SELECT * FROM plugin_list WHERE pid = %i", $_GET["id"], "AND user = %i", $this->user["id"], "ORDER BY created DESC LIMIT 1")->fetch();
    }

    public function SavePublish($id) {
        $result = $this->getPlugin();
        $latest = $this->getLatest($id);

        if($result["locked"] == 1) {
            $this->flash->push("This plugin is locked", Flash::$ERROR);
            return $this->Redirect("plugin/publish/?id=".$id);
        }
        $version = explode(".", $_POST["version"]);
        if(count($version) > 3) {
            $this->flash->push("Version can have only 3 numbers separated by dot (1.0.0)", Flash::$ERROR);
            return $this->Redirect("plugin/publish/?id=".$id);
        }

        if(version_compare($_POST["version"], $latest["version"]) != 1) {
            $this->flash->push("Your version can't be lower, or same than latest(".$latest["version"].")", Flash::$ERROR);
            return $this->Redirect("plugin/publish/?id=".$id);
        }

        if(trim($_POST["desc"]) == "") {
            $this->flash->push("You need to write description", Flash::$ERROR);
            return $this->Redirect("plugin/publish/?id=".$id);
        }

        $data = array(
            "pid" => $id,
            "name" => $result["name"],
            "descs" => $_POST["desc"],
            "type" => $result["type"],
            "code" => $result["code"],
            "version" => $_POST["version"],
            "created" => time(),
            "user" => $result["user"],
            "locked" => 0,
            "state" => $result["state"],
            "pass" => $_POST["pass"],
            "data" => $result["data"],
            "mapping" => $result["mapping"],
            "script" => $result["script"],
            "hash" => Strings::random(15)
        );
        dibi::query('INSERT INTO :prefix:plugin_list', $data);
        rename(_ROOT_DIR.'/upload/plugins/'.$result["id"].'/'.$result["code"].'.zip', _ROOT_DIR.'/upload/plugins/'.$result["id"].'/'.$data["hash"].'.zip');

        $data = array(
            "descs" => "",
            "version" => "",
            "locked" => 1,
            "pass" => $_POST["pass"]
        );
        dibi::query('UPDATE :prefix:plugin_list SET ', $data, 'WHERE `user`=%i', $this->user["id"], "AND id = %i", $id);

        return $this->Redirect("plugin/edit/?id=".$id);
    }
}