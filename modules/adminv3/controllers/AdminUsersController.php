<?php
class AdminUsersController extends AdminController {
    public function __construct($root){
        parent::__construct($root);

        $this->superuser = $this->root->config->getD("superuser", 1);
    }

    public static function GetSubMenu() { 
        return [
            array("text" => "Blocking", "link" => "blocking"),
            array("text" => "Permission", "link" => "permissions"),
            array("text" => "Register", "link" => "register"),
        ]; 
    }

    public function blocking(){
        $selectData = [];
        $result = dibi::query('SELECT * FROM :prefix:users');
		foreach ($result as $n => $row) {
            $selectData[] = $row["nick"];
		}

        $model = array(
            "selectList" => $selectData
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function delete_blocking($id){
        dibi::query('UPDATE :prefix:block SET ', array("time_long" => time()), 'WHERE `id`=%s', $id);
        return $this->Success();
    }

    /**
     * @method POST
     */
    public function new_blocking($block, $hours){
        $findByIp = User::find("ip", $block);
        $foundUser = null;
        $user = null;
		if($findByIp){
			if(User::permission($findByIp["permission"])["level"] == 10000)
                $foundUser = $findByIp;
		}else{
            $user = User::find("nick", $block);
            if($user !== false) {
                if(User::permission($user["permission"])["level"] == 10000)
                    $foundUser = $user;
            }
        }

		if($foundUser != null){
            return $this->Json(array("error" => t("You can not ban this user with rank")." ".User::permission($foundUser["permission"])["name"]));
		}

		$data = array(
					"nick" 			=> $user == null? null: $user["id"],
					"ip" 			=> $user == null? $block: null,
					"time_long" 	=> strtotime("+".$hours." hour"),
					"add_ip" 		=> Utilities::ip(),
					"add_user" 		=> User::current()["id"],
					"information" 	=> $_POST["information"],
					"interinfo" 	=> $_POST["internalinfo"],
					"action"		=> $_POST["action"]
				);
		$result = dibi::query('INSERT INTO :prefix:block', $data);
		return $this->Json(array("ok" => true));
    }

    public function data_blocking($page, $limit, $filter){
        $table = new DataTable("block");        

        if($filter["active"] == 1) {
            $table->where("okay = %i", 0);
            $table->where("FROM_UNIXTIME(time_long) > NOW()");
        }
        if(isset($filter["name"]) && $filter["name"] != null && $filter["name"] != "") {            
            $table->where("ip = %s", $filter["name"]);

            $user = User::find("nick", $filter["name"]);
            if($user !== false) {
                $table->whereOr("nick = %i", $user["id"]);
            }
        }

        $table->order("id DESC")->limit($limit)->page($page);

        $block = [];
        foreach($table->fetch() as $id => $row) {
            $isBlockByIp = $row["nick"] == null || $row["nick"] == "";

            $block[] = array(
                "id" => $row["id"],
                "block" => $isBlockByIp? $row["ip"]: $row["nick"],                
                "user" => !$isBlockByIp? User::get($row["nick"]): null,
                "expires" => $row["time_long"],
                "information" => $row["information"],
                "internal_information" => $row["interinfo"],
                "action" => $row["action"],
                "blocked_by" => array("user" => User::get($row["add_user"]), "ip" => $row["add_ip"]),
                "isOkay" => $row["okay"] == 1,
                "isBlockByIp" => $isBlockByIp,
                "isExpired" => !($row["time_long"] > time() && !($row["okay"] == 1))
            );
        }

        return $this->View(array("rows" => $block, "total" => $table->count(), "page" => $page));
    }

    public function index() {   
        $user = User::current();
        $model = array(
            "permissionList" => dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC')
        );

        return $this->View($model);
    }

    public function data($page, $limit, $filter) {
        $table = new DataTable("users");
        $table->where("underuser != %i", -1);

        if (isset($filter["permission"]) && $filter["permission"] != 0) {
            $table->where("prava = %i", $filter["permission"]);
        }
        if (isset($filter["active"]) && $filter["active"] == 1) {
            $table->where("blokovan = %i", 0);
        }

        $table->order("id")->limit($limit)->page($page);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {
            $rows[] = array(
                "id" => $row["id"],
                "name" => $row["jmeno"],
                "email" => $row["email"],
                "permission" => $row["prava"] == -20? null: User::permission($row["prava"]),
                "ip" => $row["ip"],
                "isBlocked" => $row["blokovan"] == 1,
                "isSuperUser" => $row["id"] == $this->superuser,
                "isNotActive" => $row["blokovan"] == 2,
                "isNotActiveAndBlocked" => $row["blokovan"] == 3,
                "isSystem" => $row["prava"] == -20
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    /**
     * @method POST
     */
    public function update($id) {
        if($this->superuser == $id && User::current()["id"] != $id) 
            return $this->Error("User not found");

        $result = dibi::query("SELECT * FROM :prefix:users WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->Error("User not found");

        $data = [];
        if($result["prava"] == -20){
            $data = array(
                "jmeno" => $_POST["name"],
                "nick" => $_POST["name"],
                "prava" => $_POST["permission"],
                "data" => Config::ssave(array("desc" => $_POST["desc"]))
            );
        }else if($this->superuser == $id){
            $data = array(
                "nick" => $_POST["nick"],
                "email" => $_POST["email"],
                "ip" => $_POST["ip"]
            );
        }else{
            $block = 0;
			if(isset($_POST["block"]) && $_POST["block"] == 1)
				$block = 1;
			if(isset($_POST["noactive"]) && $_POST["noactive"] == 1)
				$block = ($block == 1? 3: 2);

            $data = array(
                "jmeno" => $_POST["name"],
                "nick" => $_POST["nick"],
                "prava" => $_POST["permission"],
                "email" => $_POST["email"],
                "ip" => $_POST["ip"],
                "blokovan" => $block,
                "underuser" => null,
                "recovery" => ($block == 2 && $result["recovery"] == ""? Strings::random(8,Strings::$NUMBERS): $result["recovery"])
            );

            if(isset($_POST["hidefromlist"]))
				$data["underuser"] = -1; 

			if($_POST["password"] != "") 
				$data["heslo"] = sha1($_POST["password"]);

			if(isset($_POST["expired"]))
				$data["expired"] = strtotime($_POST["expired"], ($result["expired"] == ""? time(): $result["expired"]));
        }

        $plugin = $this->root->module_manager->hook_call("admin.user.edit.post", array("user" => $result), $data);

        $oldData = array(
			"jmeno" => $result["jmeno"],
			"nick" => $result["nick"],
			"prava" => $result["prava"],
			"email" => $result["email"],
			"ip" => $result["ip"],
			"blokovan" => $result["blokovan"],
			"underuser" => $result["underuser"],
			"recovery" => $result["recovery"]
		);

        Utilities::addHistory("user", "account", "edited", array("old" => $oldData, "new" => $data), "Acount edited by ".User::current(true)["nick"], $id);
		dibi::query('UPDATE :prefix:users SET ', $data, 'WHERE `id`=%s', $id);

        return $this->Success("User has been updated");
    }

    public function edit($id) {
        $backLinks = [["name" => t("Users"), "url" => "users/"]];
        $this->Title(t("Edit"), $backLinks);

        if($this->superuser == $id && User::current()["id"] != $id) 
            return $this->NotFound("User not found");

        $result = dibi::query("SELECT * FROM :prefix:users WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->NotFound("User not found");

        $this->Title(t("Edit") ." - ". $result["nick"], $backLinks);

        $permission = User::permission($result["prava"]);
        $currentUserId = User::current()["id"];

        $model = array(
            "id" => $result["id"],
            "name" => $result["jmeno"],
            "nick" => $result["nick"],
            "permission" => $result["prava"],
            "email" => $result["email"],
            "ip" => $result["ip"],
            "data" => Config::sload($result["data"]),
            "expired" => $result["expired"] == 0? "": date(Utilities::getTimeFormat(), $result["expired"]==0?"":$result["expired"]),
            "recoveryCode" => $result["recovery"],
            "permissions" => dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC'),     
            "relations" => dibi::query('SELECT * FROM :prefix:sessions WHERE user = %s', $result["id"] ,'ORDER BY date DESC LIMIT 4'),   
            "actions" => Utilities::getHistory("user", "account", "", $result["id"]),
            "showActivatingCode" => $result["blokovan"] == 2,
            "isSystem" => $result["prava"] == -20,
            "isSuperUser" => $result["id"] == $this->superuser,
            "isBlocked" => $result["blokovan"] == 1 ||$result["blokovan"] == 3,
            "isNotActive" => $result["blokovan"] == 2 ||$result["blokovan"] == 3,            
            "isHidden" => $result["underuser"] == -1,
            "isEditorSuperUser" => $this->superuser == $currentUserId,
            "isMe" => $result["id"] == $currentUserId,
            "isExpiredPermission" => $permission["expired"]
        );
        return $this->View($model);
    }

    public function permissions() {
        $backLinks = [["name" => t("Users"), "url" => "users/"]];
        $this->Title(t("Permission"), $backLinks);

        return $this->View();
    }

    public function data_permission($page, $limit, $filter){
        $table = new DataTable("permission");
        $table->order("level DESC")->limit($limit)->page($page);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {
            $rows[] = array(
                "id" => $row["id"],
                "name" => $row["name"],
                "count" => dibi::query('SELECT * FROM :prefix:users WHERE prava = %i', $row["id"])->count(), //join?
                "level" => $row["level"],
                "color" => $row["color"],
                "isSystemPermission" => $row["level"] == 10000 || $row["level"] == 1
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    /**
     * @method POST
     */
    function create_permission($name){
        $data = array(
            "name" 		=> $name,
            "level"		=> 5,
            "color"		=> "black"
        );
        $result = dibi::query('INSERT INTO :prefix:permission', $data);

        return $this->Success("Created", array("id" =>  dibi::getInsertId()));
    }

    /**
     * @method POST
     */
    public function delete_permissions($id){
        $result = dibi::query("SELECT * FROM :prefix:permission WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->Error("Permission not found");

        if($result["level"] == 1 || $result["level"] == 10000)
            return $this->Error("This is system permission");
        
        $defaultPerm = $this->root->config->get("default-permision");
        dibi::query('UPDATE :prefix:users SET prava = %i', $defaultPerm,' WHERE prava = %i', $id);
        dibi::query('DELETE FROM :prefix:permission WHERE id = %i', $id);
        return $this->Success("Deleted");
    }

    /**
     * @method POST
     */
    public function update_permission($id) {
        $result = dibi::query("SELECT * FROM :prefix:permission WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->Error("Permission not found");

        if($result["level"] == 10000 || $result["level"] == 1 || $result["level"] == 0){
			$data = array(
					"name"  => $_POST["name"],
					"color" => $_POST["color"]
                );
		}
		else{
			$data = array(
					"name"  => $_POST["name"],
					"color" => $_POST["color"],
					"level" => $_POST["level"],
					"expired" => (isset($_POST["expired"])?1:0),
					"expired_register" => $_POST["expired_register"]
				);
		}

		if(($_POST["level"] > 9999 || $_POST["level"] < 2) && ($result["level"] != 10000 && $result["level"] != 1))
			return $this->Error("Level can't be more then 9999 and less than 2");

		if(isset($_POST["level"]) and dibi::query("SELECT * FROM :prefix:permission WHERE level=%i", $_POST["level"], "AND id!=%i", $result["id"])->count() > 0)
            return $this->Error("Level can't be same like other");

		$prm = null;
		if($result["level"] == 10000){ 
            $_POST["admin"] = true;
            $_POST["users"] = true;
            $_POST["system"] = true; 
        }

		foreach(User::$perms as $p) {
            if($result["level"] == 10000 && ($p == "admin" || $p == "users" || $p == "system")){
                $prm[$p] = 1;
            }else{
			    $prm[$p] = (isset($_POST[$p])?1:0);
            }
		}
		$data["data"] = Config::ssave($prm);

		dibi::query('UPDATE :prefix:permission SET ', $data, 'WHERE `id`=%s', $id);
		return $this->Success("Permission has been updated");
    }

    public function edit_permission($id){
        $backLinks = [["name" => t("Users"), "url" => "users/"], ["name" => t("Permission"), "url" => "users/permissions"]];
        $this->Title(t("Edit"), $backLinks);

        $result = dibi::query("SELECT * FROM :prefix:permission WHERE id=%i", $id)->fetch();
        if($result == null)
            return $this->NotFound("Permission not found");

        $this->Title(t("Edit")." - ".$result["name"], $backLinks);

        $model = array(
            "id" => $result["id"],
            "name" => $result["name"],
            "color" => $result["color"],
            "level" => $result["level"],            
            "expired_register" => $result["expired_register"],
            "data" => Config::sload($result["data"]),
            "permissionList" => User::$perms,
            "isSystemPermission" => $result["level"] == 10000 || $result["level"] == 1,
            "isExpired" => $result["expired"] == 1
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function update_register(){
        $perm = User::permission($_POST["default_permission"]);
        if($perm === false) {
            return $this->Error("Default permission not exists");
        }
        if($perm["level"] >= 10000) {
            return $this->Error("Can't set default permission to ".$perm["name"]);
        }

        $pole = array(
            "default-permision" => $perm["id"],
            "registration-enable" => (isset($_POST["enable_registration"])? 1: 0),
            "registration-activation" => (isset($_POST["enable_activation"])? 1: 0),
            "registration-conditions" => $_POST["registration_condition"],
        );

        $config = $this->root->config;
        foreach($pole as $key => $value){            
            $config->update($key, $value);
        }
        return $this->Success("Saved", [], true);
    }

    public function register(){
        $backLinks = [["name" => t("Users"), "url" => "users/"]];
        $this->Title(t("Register"), $backLinks);

        $model = array(
            "isEmailActivationRequired" => $this->root->config->get("registration-activation") == 1,
            "isRegistrationEnabled" => $this->root->config->get("registration-enable") == 1,
            "defaultPermission" => User::permission($this->root->config->get("default-permision")),
            "permissionList" => dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC'),            
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function update_form_register_form($regform){
        $this->root->config->update("registration-form", $regform);
        return $this->Success("Saved", [], true);
    }

    /**
     * @method POST
     */
    public function update_register_form(){
        $customFormId = $this->root->config->get("registration-form");
        if($customFormId == -1) 
            return $this->Error("You need to set custom form to use this feature");

        $settingForm = null;
        $result = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $customFormId, " ORDER BY position");
        foreach ($result as $n => $row) {
            $settingForm[$row["id"]] = $_POST["funct_".$row["id"]];
        }
        $settingForm["parent"] = $customFormId;
        $this->root->config->update("registration-form-setting", Config::ssave($settingForm));

        return $this->Success();
    }

    public function register_form(){
        $backLinks = [["name" => t("Users"), "url" => "users/"], ["name" => t("Register"), "url" => "users/register/"]];
        $this->Title(t("Register form"), $backLinks);

        $customFormId = $this->root->config->get("registration-form");
        $model = array(
            "customForm" => $customFormId,
            "formList" => dibi::query('SELECT * FROM :prefix:form'),
            "isCustomForm" => $customFormId != -1
        );
        if($model["customForm"] != -1) {
            $model["formSettings"] = Config::sload($this->root->config->get("registration-form-setting"));
            $model["formItems"] = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $model["customForm"], " ORDER BY position");
        }

        return $this->View($model);
    }
}