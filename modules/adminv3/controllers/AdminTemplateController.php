<?php
class AdminTemplateController extends AdminController {
    public function __construct($root){
        parent::__construct($root);

        if(!file_exists(_ROOT_DIR . "/views/_templates/")){
            mkdir(_ROOT_DIR . "/views/_templates/", 0777);
        }
    }

    public static function GetSubMenu() { 
        return [
            array("text" => "Editor", "link" => "editor"),
        ]; 
    }

    public function index(){
	    $templates = $this->getTypes();

        return $this->View();
    }

    private function getTypes(){
        $plugin = $this->root->module_manager->hook_call("templates.types", null, array(), false, true, true);            
	    return $plugin["output"];
    }

    public function data($page, $limit, $filter){
        $table = new DataTable("templates");
        $table->order("id DESC")->limit($limit)->page($page);

        $templates = $this->getTypes();
        $dataLinq = new LinQ($templates);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {            
            $data = $dataLinq->FirstOrNull(function($e) use($row){ return $e["code"] == $row["code"]; });  
        
            $rows[] = array(
                "id" => $row["id"],
                "name" => $row["name"],
                "author" => User::get($row["author"]),
                "code" => $row["code"],
                "created" => $row["created"],
                "templateDescription" => $data != null? $data["description"]: "",
                "hasTemplate" => $data != null
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    /**
     * @method POST
     */
    public function create($name){
        $data = array(
            "name" 		=> $name,
            "created"	=> time(),
            "author"	=> User::current()["id"],
            "hash" 	    => sha1(time() + Strings::random(10))
        );
        $result = dibi::query('INSERT INTO :prefix:templates', $data);
        $id = dibi::getInsertId();

        return $this->Success("Created", ["id" => $id]);
    }

    /**
     * @method POST
     */
    public function delete($id) {
        $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $id)->fetch();
        if($result == null)
            return $this->NotFound("Template not found");

        unlink(_ROOT_DIR . "/views/_templates/".$result["hash"].".view");
        dibi::query('DELETE FROM :prefix:templates WHERE `id`=%s', $id);
        
        return $this->Success("Deleted");
    }

    public function edit($id) {
        $backLinks = [["name" => t("Templates"), "url" => "templates/"]];
        $this->Title(t("Edit"), $backLinks);

        $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $id)->fetch();
        if($result == null)
            return $this->NotFound("Template not found");

        $this->Title(t("Edit") ." - ". $result["name"], $backLinks);

        $templates = $this->getTypes();
        $dataLinq = new LinQ($templates);
        $dataTemplate = $dataLinq->FirstOrNull(function($e) use($result){ return $e["code"] == $result["code"]; });  
        $templateCodes = $dataLinq->Select(function($e) { return $e["code"]; })->ToArray();

        $model = array(
            "id" => $result["id"],
            "name" => $result["name"],
            "code" => $result["code"],
            "content" => file_get_contents(_ROOT_DIR . "/views/_templates/".$result["hash"].".view"),
            "rand" => Strings::random(10),
            "templateDescription" => $dataTemplate["description"],
            "hasTemplate" => $dataTemplate != null,
            "templatesList" => $templateCodes
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function update($id){
        $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $id)->fetch();
        if($result == null)
            return $this->NotFound("Template not found");

        file_put_contents(_ROOT_DIR . "/views/_templates/".$result["hash"].".view", $_POST["content"]);

        $update = array(
            "name" => $_POST["name"],
            "code" => $_POST["code"]
        );
        dibi::query('UPDATE :prefix:templates SET ', $update, "WHERE `id`=%i", $id);

        return $this->Success("Saved", [], true);
    }

    /**
     * @method POST
     */
    public function draft($id, $rand, $content){
        $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $id)->fetch();
        if($result == null)
            return $this->NotFound("Template not found");
            
        file_put_contents(_ROOT_DIR . "/views/_templates/".$result["hash"]."-temp-".$rand.".view", $content);
        
        return $this->Json(array("ok" => true));
    }

    public function preview($id, $rand) {
        $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $id)->fetch();
        if($result == null)
            return $this->NotFound("Template not found");

        $templates = $this->getTypes();
        $dataLinq = new LinQ($templates);
        $dataTemplate = $dataLinq->FirstOrNull(function($e) use($result){ return $e["code"] == $result["code"]; });

        $model = [];
        if($dataTemplate != null) {
            $model = $dataTemplate["dummy"];
        }

        ob_start();
        $filePath = _ROOT_DIR . "/views/_templates/".$result["hash"]."-temp-".$rand.".view";
        if(file_exists($filePath)){
            $this->root->page->template_parse($filePath, $model);
            unlink($filePath);
        }else{
            $this->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$result["hash"].".view", $model);
        }
        $text = ob_get_contents();
        ob_end_clean();

        return $this->Text($text);
    }

    /**
     * @method POST
     */
    public function rebuild($id) {
        $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $id)->fetch();
        if($result == null)
            return $this->NotFound("Template not found");

        $this->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$result["hash"].".view", null, true);

        return $this->Success("Rebuilded");
    }

    /**
     * @method POST
     */
    public function editor_new($file){
        $file = $file.".view";

        if(file_exists(_ROOT_DIR . "/views/".$file)){
            return $this->Json(array("error" => t("File alerady exists!")));
        }

        $f = fopen(_ROOT_DIR . "/views/".$file, "w");
        fclose($f);

        return $this->Json(array("ok" => "Saved", "file" => explode("/", $file)[1], "full" => $file));
    }

    /**
     * @method POST
     */
    public function editor_save($file, $text){
        file_put_contents(_ROOT_DIR . "/views/".$file, $text);
        return $this->Json(array("ok" => "Saved"));
    }

    /**
     * @method POST
     */
    public function editor_open($file) {
        return $this->Json(array("text" => file_get_contents(_ROOT_DIR . "/views/".$file)));
    }

    public function editor(){
        $backLinks = [["name" => t("Templates"), "url" => "templates/"]];
        $this->Title("Editor", $backLinks);

        $base_dir = _ROOT_DIR."/views/";
        $i = 0;

        $dirs = [];

        foreach(scandir($base_dir) as $file) {
            if($file == '.' || $file == '..' || $file == "_templates") continue;
            $dir = $base_dir.DIRECTORY_SEPARATOR.$file;
            if(is_dir($dir)) {
                $i++;
                $sub_dir = $base_dir.$file."/";

                $dirs[$file] = ["index" => $i, "files" => [], "name" => $file];

                foreach(scandir($sub_dir) as $sfile) {
                    if($sfile == '.' || $sfile == '..') continue;
                    $sdir = $sub_dir.DIRECTORY_SEPARATOR.$sfile;
                    if(is_file($sdir)) {
                        $dirs[$file]["files"][] = $sfile;
                    }
                }
            }
        }

        $model = array(
            "dirs" => $dirs
        );

        return $this->View($model);
    }
}