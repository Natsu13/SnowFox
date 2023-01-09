<?php

class Adminv3Controller extends Controller {

    public function __construct($root){
        parent::__construct($root, true);

        User::checkLogin();
        $this->user = User::current();

        $this->root = $root;
        $this->flash = $this->getContainer()->get('flash');       
        $this->getContainer()->set("admin", $this);

        $this->controllers = [];
        $this->loadControllers();
    }

    public static $controllerFolder = "modules/adminv3/controllers";

    public static function registerController($file, $className){
        $admin = Bootstrap::$self->getContainer()->get("admin");

        include_once $file;

        $admin->controllers[$className] = array(
            "file" => $file,
            "menu" => call_user_func($className .'::GetSubMenu')
        );        
    }

    private function loadControllers(){
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminInfoController.php", "AdminInfoController");
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminMenuController.php", "AdminMenuController");
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminUsersController.php", "AdminUsersController");
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminSystemController.php", "AdminSystemController");
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminStyleController.php", "AdminStyleController");     
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminTemplateController.php", "AdminTemplateController");
        Adminv3Controller::registerController(Adminv3Controller::$controllerFolder . "/AdminContentController.php", "AdminContentController");        
        $this->root->module_manager->hook_call("admin.register", null);
    }

    /**
     * @method POST
     * @get index
     */
    public function login($login = "", $password = "", $back_url = "") {
        $model = array("message" => "", "url" => $back_url, "login" => $login);

        if($login == "" || $password == "") {
            $model["message"] = "<div>".t("You need to enter login and password")."</div>";
        }else{
            $state = User::login($login, $password, true);
            if($state === true) {
                return $this->Redirect($back_url == ""? "adminv3/": $back_url);
            }

            foreach($state as $message) {
                $model["message"].= "<div>".$message."</div>";
            }
        }

        return $this->View($model);
    }
    
    public function index() {
        if($this->user == false) {
            return $this->View("login", ["url" => Router::url(true), "message" => ""]);
        }

        $menu = $this->getIcons();

        foreach($menu as $key => $value) {
            $module = $value["module"];
            $subMenu = $this->controllers[$module]["menu"];
            $menu[$key]["menu"] = $subMenu;
        }

        $appendAction = false;
        $url = $_GET["adminModule"];
        if(isset($_GET["adminAction"]) && $_GET["adminAction"] != "index") { $url.="/".$_GET["adminAction"]; $appendAction = true; }
        if(isset($_GET["id"]) && $_GET["id"] != "1" && $appendAction) $url.="/".$_GET["id"];

        $i = 0;
        $removeGet = array("url", "__type", "module", "action", "adminModule", "adminAction", "id");
        foreach($_GET as $key => $get) {
            if(in_array($key, $removeGet)) continue;
            $url.=($i++ == 0?"?":"&").$key."=".$get;
        }

        $model = array(
            "icons" => $menu,
            "user" => $this->user,
            "module" => $_GET["adminModule"],
            "url" => $url,
            "separator" => $this->root->config->get("titleSeparator")
        );

        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function load($_pageLoad){
        return $this->Json($this->_load_internal($_pageLoad));
    }

    /**
     * @referent adminv3
     */
    public function loadContent($_pageLoad) {
        $data =$this->_load_internal($_pageLoad);
        if(is_array($data)) {
            return $this->Text($data["content"]);
        }
        return $this->Error("Internal error", "This method is not allowed", 500);
    } 

    private function _load_internal($_pageLoad){
        if($_pageLoad == "") $_pageLoad = "info";

        $data = $this->getIcons();
        $query = explode("/", $_pageLoad, 3);
        $module = null;
        $content = "";
        $title = null;
        $back = [];
        $q = $query[1];
        $id = $query[2];
        
        if(isset($q) && substr($q, strlen($q)-1, 1) == "/"){
            $q = substr($q, 0, strlen($q) - 1);
        }
        if($q == "") $q="index";

        if($id == "" || $id === 0) $id = 1;
        $_GET["id"] = $id;        
 
        if(isset($data[$query[0]])){
            $module = $data[$query[0]];
        }
        if($module != null) {
            $content = $this->getModuleContent($module["module"], $q, $title, $back);

            if($title == null) {
                $title = t($module["text"]);
            }

            if(isset($content["type"]) && $content["type"] == "error") {
                return array("content" => $content["description"], "title" => $content["title"], "back" => $back);
            }
            if(isset($content["type"]) && $content["type"] == "json"){
                return $content["data"];
            }
            if(isset($content["type"]) && $content["type"] == "text"){
                if($content["textarea"]) {
                    return array("content" => "<textarea>".$content["data"]."</textarea>", "title" => $title, "back" => $back);
                }
                return array("content" => $content["data"], "title" => $title, "back" => $back);
            }
        }

        return array("content" => $content, "title" => $title, "back" => $back);
    }

    private function getModuleContent($module, $action, &$title = null, &$back = 1){
        $prep = $this->root->page->getContent($module, $action, false, Adminv3Controller::$controllerFolder, "AdminController");
		if($prep == null) return;

        if($prep[0] === false) {
			return $prep[1];
		}

        $file = $prep[1];
        $name = $prep[2];
        $controller = $prep[3];
        $actionName = $prep[4];
        $view = $prep[5];
        $title = $view["title"];
        $back = $view["back"];

        if($view["type"] == "html" && $view["template"] != null) {
            $outputFile = "";

            ob_start();
			$this->root->page->template_parse($view["template"], $view["model"]);
			$text = ob_get_contents();
			ob_end_clean();
            return $text;
        }

        return $view;
    }

    public function getIcons() {
        $icons = array(
            "info"      => array(
                "module" => "AdminInfoController", 
                "url" => "", 
                "icon" => "fas fa-question-circle", 
                "text" => "Information"
            ),
            "menu"      => array(
                "module" => "AdminMenuController", 
                "url" => "menu/", 
                "icon" => "fab fa-elementor", 
                "text" => "Menu"
            ),
            "users"     => array(
                "module" => "AdminUsersController", 
                "url" => "users/", 
                "icon" => "fas fa-users", 
                "text" => "Users"
            ),
            "content"   => array(
                "module" => "AdminContentController", 
                "url" => "content/", 
                "icon" => "fas fa-archive", 
                "text" => "Content"
            ),
            "system"    => array(
                "module" => "AdminSystemController", 
                "url" => "system/", 
                "icon" => "fas fa-cogs", 
                "text" => "System"
            ),
            "style"     => array(
                "module" => "AdminStyleController", 
                "url" => "style/",
                "icon" => "fas fa-paint-roller", 
                "text" => "Style", 
                "showMobile" => false
            ),		
            "templates" => array(
                "module" => "AdminTemplateController", 
                "url" => "templates/", 
                "icon" => "fas fa-layer-group", 
                "text" => "Templates", 
                "showMobile" => false
            ),
        );
    
        $plugin = $this->root->module_manager->hook_call("admin.icons", null, $icons);
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
}

class AdminController extends Controller {
    public function __construct($root){
        parent::__construct($root, true);
        $this->title = null;
        $this->back = [];
        $this->root = $root;
        $this->reload = false;
    }

    public static function GetSubMenu() { return []; }

    public function Reload() {
        $this->reload = true;
    }

    public function Success($message = "Saved", $data = [], $reload = null) {
        if($reload == null) $reload = $this->reload;
        return $this->Json(array_merge(array("state" => "success", "text" => t($message), "reload" => $reload), $data));
    }

    public function NotFound($message = "Not found"){
        return parent::Error(t("Not found"), t($message), 404);
    }

    public function Error($message = "Error", $data = [], $_unused = "") {
        return $this->Json(array_merge(array("state" => "error", "text" => t($message)), $data));
    }

    public function Title($title, $back = []) {
        $this->title = $title;
        $this->back = $back;
    }

    public function View($name = null, $model = null, $noTemplate = false, $folder = "views/adminv3/views", $debugBack = 2){
        $ret = parent::View($name, $model, $noTemplate, $folder, $debugBack);
        $ret["title"] = $this->title;
        $ret["back"] = $this->back;
        return $ret;
    }
}