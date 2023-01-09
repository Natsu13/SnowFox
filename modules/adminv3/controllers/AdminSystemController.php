<?php
class AdminSystemController extends AdminController {
    public function __construct($root){
        parent::__construct($root);

        $this->defBackLinks = [["name" => t("System"), "url" => "system/"]];
        $this->superuser = $this->root->config->getD("superuser", 1);
        $this->user = $user = User::current();
    }

    public static function GetSubMenu() { 
        return [
            array("text" => "Articles", "link" => "article"),
            array("text" => "Emails", "link" => "emails"),
            array("text" => "Redirecting", "link" => "redirecting"),
            array("text" => "Login", "link" => "login"),
            array("text" => "Cookies", "link" => "cookies"),
            array("text" => "FTP", "link" => "ftp"),
            array("text" => "Infobar", "link" => "infobar"),
            array("text" => "Lock", "link" => "lock", "condition" => "superuser"),
            array("text" => "Cron", "link" => "cron", "condition" => "superuser"),
            //array("text" => "Variables", "link" => "variables", "condition" => "superuser"),
        ]; 
    }

    public function index() {
        return $this->Text("There will be the content from info");
    }

    /**
     * @method POST
     */
    public function update_article(){
        $this->root->config->update("comment-max-url", $_POST["maxurl"]);
        $this->root->config->update("comment-timeout", $_POST["timeout"]);
        $this->root->config->update("comment-ban-length", $_POST["banlength"]);
        return $this->Success();
    }

    public function article(){
        $this->Title(t("Articles"), $this->defBackLinks);

        $model = array(
            "commentMaxUrl" => $this->root->config->getD("comment-max-url", 2),
            "commentTimeout" => $this->root->config->getD("comment-timeout", 20),
            "commentBanLength" => $this->root->config->getD("comment-ban-length", 1),
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function update_emails(){
        $this->root->config->update("email-enable", (isset($_POST["enable_email"]) && $_POST["enable_email"] == 1?1:0));
        $this->root->config->update("email-webmaster", $_POST["masterEmail"]);

        return $this->Success();
    }

    public function settings_emails(){
        $back = array_merge($this->defBackLinks, [["name" => t("Emails"), "url" => "system/emails/"]]); 
        $this->Title(t("Settings"), $back);

        $model = array(
            "masterEmail" => $this->root->config->get("email-webmaster"),
            "enableEmail" => $this->root->config->getD("email-enable", "1") == 1
        );
        return $this->View($model);
    }

    public function content_email($id) {
        $result = dibi::query("SELECT * FROM :prefix:emails WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->NotFound("Email not found");

		return $this->Text($result["message"]);
    }

    public function show_email($id){  
        $back = array_merge($this->defBackLinks, [["name" => t("Emails"), "url" => "system/emails/"]]);     
        $this->Title(t("View"), $back);

        $result = dibi::query("SELECT * FROM :prefix:emails WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->NotFound("Email not found");

        $this->Title($result["subject"], $back);

        $model = array(
            "id" => $id,
            "subject" => $result["subject"],
            "user" => User::get($result["user"], true),
            "sended" => $result["time"],
            "ip" => $result["ip"],
            "from" => $result["_from"],
            "to" => $result["_to"],
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function data_emails($page, $limit, $filter){
        $table = new DataTable("emails");
        if (isset($filter["receiver"]) && $filter["receiver"] != "") {
            $table->like("_to", $filter["receiver"]);
        }
        $table->order("time DESC")->limit($limit)->page($page);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {
            $rows[] = array(
                "id" => $row["id"],
                "from" => $row["_from"],
                "to" => $row["_to"],
                "subject" => $row["subject"],
                "ip" => $row["ip"],
                "user" => User::get($row["user"], true),
                "time" => $row["time"]
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    public function emails(){
        $this->Title(t("Emails"), $this->defBackLinks);
        return $this->View();
    }

    /**
     * @method POST
     */
    public function update_login(){
        $config = $this->root->config;
        $config->update("ttl", $_POST["timeLong"]);
		$config->update("tts", $_POST["timeShort"]);
		$config->update("onlyttl", (isset($_POST["onlyLongLogin"])?1:0));
        return $this->Success();
    }

    public function login(){
        $this->Title(t("Login"), $this->defBackLinks);
        $config = $this->root->config;

        $model = array(
            "timeLongLogin" => $config->getD("ttl", "+24 hour"),
            "timeShortLogin" => $config->getD("tts", "+8 hour"),
            "onlyLongLogin" => $config->getD("onlyttl", 0) == 1
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function update_maintenance($state){
        $this->root->page->maintenanceMode($state, "manualy by user");
        return $this->Success($state?t("Maintance mode is enabled!"):t("Maintance mode is disabled!"));
    }

    /**
     * @method POST
     */
    public function update_lock(){
        $this->root->config->update("lock-enable", (isset($_POST["pageLock"])?1:0));
		$this->root->config->update("lock-password", $_POST["password"]);
        return $this->Success();
    }

    public function lock(){
        if($this->user["id"] != $this->superuser) 
            return $this->NotFound("This page was not found");

        $this->Title(t("Lock"), $this->defBackLinks);

        $maintenanceMode = file_exists(_ROOT_DIR."/maintenance.html");

        $model = array(
            "maintenanceModeEnable" => $maintenanceMode,
            "lockEnable" => $this->root->config->getD("lock-enable", "0") == 1,
            "password" => $this->root->config->getD("lock-password", "")
        );

        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function update_redirecting($id) {
        $pole = array(
            "name" => $_POST["name"],
            "_from" => $_POST["from"],
            "_to" => $_POST["to"],
            "minop" => $_POST["minop"],
            "active" => isset($_POST["active"]),
            "redirect" => isset($_POST["redirect"]),
        );

        dibi::query('UPDATE :prefix:redirecting SET ', $pole, "WHERE `id`=%s", $id);
        return $this->Success();
    }

    /**
     * @method POST
     */
    public function create_redirecting($name){
        $data = array(
			"name" 		=> $name,
			"active"	=> 0
		);
		$result = dibi::query('INSERT INTO :prefix:redirecting', $data);
        return $this->Success("Created", ["id" => dibi::getInsertId()]);
    }

    /**
     * @method POST
     */
    public function delete_redirecting($id) {
        dibi::query('DELETE FROM :prefix:redirecting WHERE id=%i', $id);
        return $this->Success("Deleted");
    }

    public function edit_redirecting($id) {
        $redirect = ["name" => t("Redirecting"), "url" => "system/redirecting/"];
        $back = array_merge($this->defBackLinks, [$redirect]); 
        $this->Title(t("Edit"), $back);

        $result = dibi::query("SELECT * FROM :prefix:redirecting WHERE id=%i", $id)->fetch();
		if($result == NULL)
            return $this->NotFound("Redirect not found");

        $back = array_merge($this->defBackLinks, [$redirect]); 
        $this->Title($result["name"], $back);

        $model = array(
            "name" => $result["name"],
            "from" => $result["_from"],
            "to" => $result["_to"],
            "permission" => $result["minop"],
            "isRedirect" => $result["redirect"] == 1,
            "isActive" => $result["active"] == 1,
            "permissionList" => dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC'),

        );
        return $this->View($model);
    }

    public function redirecting(){
        $this->Title(t("Redirecting"), $this->defBackLinks);

        return $this->View();
    }

    public function data_redirecting($page, $limit, $filter){
        $table = new DataTable("redirecting");
        $table->order("id DESC")->limit($limit)->page($page);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {
            $rows[] = array(
                "id" => $row["id"],
                "name" => $row["name"],
                "from" => $row["_from"],
                "to" => $row["_to"],                
                "isActive" => $row["active"]
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    /**
    * @method POST
    */
    public function update_cookies(){
        $config = $this->root->config;
        $state = $config->getD("cookie-accept-show", 1);
        $config->update("cookie-accept-show", $_POST["cookie-accept-show"]);
        if($state != 0) {
            $config->update("cookie-text", $_POST["cookie-text"]);
            $config->update("cookie-more", $_POST["cookie-more"]);
            if($state == 1) {
                $config->update("cookie-text-accept", $_POST["accept-text"]);		
                $config->update("cookie-no-js", $_POST["cookie-no-js"]);
            }            
            $config->update("cookie-js", $_POST["cookie-js"]);
        }
        return $this->Success("Saved", [], true);
    }

    public function cookies(){
        $this->Title(t("Cookies"), $this->defBackLinks);

        $config = $this->root->config;
        $model = array(
            "cookieAcceptShow" => $config->getD("cookie-accept-show", 1),
            "cookieText" => $config->getD("cookie-text", t("This website uses cookies. By continuing to browse this site, you agree to their use.")),
            "cookieAccept" => $config->getD("cookie-text-accept", t("I accept")),
            "cookieMore" => $config->getD("cookie-more", "https://policies.google.com/technologies/cookies"),
            "cookieNoJs" => $config->getD("cookie-no-js", "window['ga-disable-GA_MEASUREMENT_ID'] = true;"),
            "cookieJs" => $config->getD("cookie-js", "cookieEnabled = true;"),
            "cookiesList" => dibi::query('SELECT * FROM :prefix:cookies ORDER BY created DESC')
        );
        return $this->View($model);
    }

    public function api_ftp_save_file($saveFile, $text){
        $file = _ROOT_DIR . $saveFile;
        if(!file_exists($file)) {
			return $this->Json(array("error" => "FILE_NOT_EXISTS", "message" => "File not exists"));
		}

		if(file_put_contents($file, $text) === false){
            return $this->Json(array("error" => "FILE_CANT_SAVE", "message" => "File can't be saved"));
			exit;
        }

		return $this->Json(array("ok" => true));
    }

    public function api_ftp_get_file($file, $name){
        $file = _ROOT_DIR . $file;
		if(!file_exists($file)) {
			return $this->Json(array("error" => "FILE_NOT_EXISTS", "message" => "File not exists"));
		}

		return $this->Json(array(
            "name" => $name,
            "text" => file_get_contents($file)
        ));
    }

    public function api_ftp_get_files($list){
        $dir = _ROOT_DIR . $list;
        if(!is_dir($dir)){
            return $this->Json(array("error" => "FOLDER_NOT_EXISTS", "message" => "Folder not exists"));
        }

        $list = array("files" => [], "dirs" => []);			
        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) { 
            $fpath = $dir . DIRECTORY_SEPARATOR . $value;

            $perms = fileperms($fpath);
            // Owner
            $info  = (($perms & 0x0100) ? 'r' : '-');
            $info .= (($perms & 0x0080) ? 'w' : '-');
            $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
            // Group
            $info .= (($perms & 0x0020) ? 'r' : '-');
            $info .= (($perms & 0x0010) ? 'w' : '-');
            $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
            // World
            $info .= (($perms & 0x0004) ? 'r' : '-');
            $info .= (($perms & 0x0002) ? 'w' : '-');
            $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

            if (!in_array($value,array(".",".."))) {		
                if(is_dir($fpath)){
                    $list["dirs"][] = array(
                        "name" => $value, 
                        "isdir" => true,
                        "size" => "",
                        "lastmodify" => "",
                        "perms" => $info/*$perms*/
                    );
                }else{
                    $list["files"][] = array(
                        "name" => $value, 
                        "isdir" => false,
                        "size" => filesize($fpath),
                        "lastmodify" => date("d.m.y H:i:s", filemtime($fpath)),
                        "perms" => $info/*$perms*/
                    );	
                }					
            }
        }			
        usort($list["files"], function($a, $b){ return strcmp(strtolower($a["name"]), strtolower($b["name"])); });		
        usort($list["dirs"], function($a, $b){ return strcmp(strtolower($a["name"]), strtolower($b["name"])); });
        $result = array_merge($list["dirs"], $list["files"]);
        //usort($fileList, function($a, $b){ if($a["isdir"]){ return -1; } return 1; });
        return $this->Json($result);
    }

    public function ftp(){ /* hide on mobile */
        $this->Title(t("FTP"), $this->defBackLinks);
        return $this->View();
    }
}