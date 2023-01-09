<?php
class AdminStyleController extends AdminController {
    public function __construct($root){
        parent::__construct($root);
    }

    public function index(){
        $styles = [];
        $base_dir = _ROOT_DIR."/templates/";
        foreach(scandir($base_dir) as $file) {
            if($file == '.' || $file == '..') continue;
            $dir = $base_dir.DIRECTORY_SEPARATOR.$file;
            if(is_dir($dir)) {
                $styles[] = $file;
            }
        }

        $model = array(
            "selected" => Database::getConfig("style"),
            "styleList" => $styles
        );
        return $this->View($model);
    }

    public function select($selected) {
		$this->root->config->update("style", $selected);
		return $this->Success();
    }
}