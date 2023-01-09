<?php
class AdminInfoController extends AdminController {
    public function __construct($root){
        parent::__construct($root);
    }

    public static function GetSubMenu() { 
        return [
            array("text" => "Settings", "link" => "settings")
        ]; 
    }

    /**
     * @method POST
     */
    public function update_language($lang){
        dibi::query('UPDATE :prefix:settings SET ', array("value" => $lang), "WHERE `name`=%s", "default-lang");

		return $this->Success("The language has been successfully changed");
    }

    /**
     * @method POST
     */
    public function update(){
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
            $this->root->config->update($key, $value);
        }

        return $this->Success();
    }

    public function index(){
        $this->Title("Informace");

        $config = $this->root->config;

        $logo = $config->get("logo");
        if($logo != "" && substr($logo, 0, 4) != "http") { $logo = Router::url().$logo; }

        $user = User::current();
        $perm = User::permission($user["permission"]);

        $model = array(
            "title" => $config->get("title"),
            "separator" => $config->get("titleSeparator"),
            "titleFirst" => $config->get("titleFirst"),
            "logo" => $logo,
            "keywords" => $config->get("Keywords"),
            "description" => $config->get("description"),
            "autor" => $config->get("autor"),
            "utc" => $config->get("utc"),
            "user" => $user,
            "permission" => $perm,
            "dbprefix" => $this->root->database->prefix,
            "database" => $this->root->database->database_name,
            "dbversion" => $config->get("version"),
            "languages" => explode(",", Database::getConfig("languages")),
            "default-languge" => Database::getConfig("default-lang")
        );

        $config->load_variables("file", "main");
        $model["compress"] = $config->get_variable("Compress", "file", "FALSE");
        $model["compress_size"] = $config->get_variable("CompressChunk", "file", "16000");
        $model["dblog"] = $config->get_variable("LogMysql", "file", "FALSE");

        return $this->View($model);
    }

    public function settings(){
        return $this->Text("Coming soon");
    }
}