<?php
class AdminMenuController extends AdminController {
    public function __construct($root){
        parent::__construct($root);
    }

    public function index(){
        $model = array(
            "menus" => $this->getMenus()[0],
            "languages" => explode(",", Database::getConfig("languages"))
        );

        return $this->View($model);
    }

    public function getMenus() {
        $menus = null;
        $names = [];
        $first = "";
        $result = dibi::query('SELECT * FROM :prefix:menu');
        foreach ($result as $n => $row) {
            if($row["box"] == "deleted" || $row["box"] == "") continue;
            if(!isset($menus[$row["box"]])){ $menus[$row["box"]] = array( 0 => true, 1 => 0); $names[] = $row["box"]; }
            if($menus[$row["box"]][1]<$row["position"]) $menus[$row["box"]][1] = $row["position"];
            if($first == "") $first = $row["box"];
        }
        return array($menus, $names);
    }

    /**
     * @method POST
     */
    public function menu($id, $language = null) {
        if($language == "") $language = null;
        if($language == null)
			$result = dibi::query('SELECT * FROM :prefix:menu WHERE box=%s', $id, " AND language IS %s", $language," ORDER BY position");
		else
			$result = dibi::query('SELECT * FROM :prefix:menu WHERE box=%s', $id, " AND language = %s", $language," ORDER BY position");

        $items = [];
        foreach($result as $key => $item) {
            $items[] = array(
                "id" => $item["id"],
                "title" => $item["title"],
                "type" => $item["typ"],
                "isVisible" => ($item["visible"] == 1)
            );
        }

        $model = array(
            "box" => $id,
            "items" => $items
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function menu_update_positions($sort, $box = null, $language = null){
        $index = 0;
        foreach($sort as $itemId){
            $update = array("position" => $index++);
            if(isset($box) && $box != null){
                $update["box"] = $box;
            }
            if(isset($language) && $language != null){					
                $update["language"] = ($language == ""? null: $language);
            }
            dibi::query('UPDATE :prefix:menu SET ', $update, "WHERE `id`=%i", $itemId);
        }

        return $this->Json(array("ok" => true));
    }

    /**
     * @method POST
     */
    public function visibility_change($id){
        $item = dibi::query('SELECT * FROM :prefix:menu WHERE id = %i', $id)->fetch();
        if($item == null) 
            return $this->Json(array("ok" => false));

        dibi::query('UPDATE :prefix:menu SET ', array("visible" => !($item["visible"] == 1)), 'WHERE `id`=%i', $id);
        return $this->Json(array("ok" => true));
    }

    /**
     * @method POST
     */
    public function item_copy($id, $sort){
        $item = dibi::query('SELECT * FROM :prefix:menu WHERE id = %i', $id)->fetch();
        if($item == null) 
            return $this->Json(array("ok" => false));
        
        $data = array(
            "title"     => $item["title"]." - ".t("Duplicated"),
            "typ"       => $item["typ"],
            "visible"   => 0,
            "position"  => $item["position"] + 1,
            "box"       => $item["box"],
            "milevel"   => $item["milevel"],
            "malevel"   => $item["malevel"],
            "language"  => $item["language"],
            "data"      => $item["data"]
        );
        dibi::query('INSERT INTO :prefix:menu', $data);
        $newId = dibi::getInsertId();

        $index = 0;
        foreach($sort as $itemId){
            $update = array("position" => $index++);            
            dibi::query('UPDATE :prefix:menu SET ', $update, "WHERE `id`=%i", $itemId);

            if($itemId == $id) {
                $update = array("position" => $index++);            
                dibi::query('UPDATE :prefix:menu SET ', $update, "WHERE `id`=%i", $newId);
            }
        }

        return $this->Json(array("ok" => true));
    }

    /**
     * @method POST
     */
    public function item_remove($id){
        dibi::query('DELETE FROM :prefix:menu WHERE `id`=%i', $id);
        return $this->Json(array("ok" => true));
    }

    /**
     * @method POST
     */
    public function update($id) {
        $data = "";
			
		if($_POST["type"] == "article"){
			$idc = explode(", ", $_POST["article_id"]);
			$data = Config::ssave(array( "id" => $idc[0], "alias" => $idc[1] ));
		}
        if($_POST["type"] == "category"){
			$idc = explode(", ", $_POST["category_id"]);
			$data = Config::ssave(array( "id" => $idc[0], "alias" => $idc[1] ));
		}
		elseif($_POST["type"] == "login"){
			$data = Config::ssave(array( "register" => (isset($_POST["register"])?1:0) ));
		}
		elseif($_POST["type"] == "url"){
			$data = Config::ssave(array( "url" => $_POST["link"] ));
		}
			
		$mi = 0;
        $ma = 10000;
		if(isset($_POST["visible"])) $v=true; else $v=false;
		$perm = explode(",", $_POST["permission"]);
		$mi = $perm[0]; $ma = $perm[1];
		if($mi == -1 && $ma == -1){
			$mi = $_POST["permMin"];
			$ma = $_POST["permMax"];
		}
			
		if($mi > $ma){ 
			$s=$ma;
			$ma=$mi;
			$mi=$s; 
		}

		$arr = array(
					"title" => $_POST["title"],
					"box" => trim($_POST["box"]),
					"typ" => $_POST["type"],
					"data" => $data,
					"visible" => $v,
					"milevel" => $mi,
					"malevel" => $ma
				); 
		dibi::query('UPDATE :prefix:menu SET ', $arr, 'WHERE id=%i', $id);
        return $this->Json(array("ok" => true));
    }

    public function edit($id) {
        $item = dibi::query('SELECT * FROM :prefix:menu WHERE id = %i', $id)->fetch();
        if($item == null) 
            return $this->NotFound("Menu item was not found");

        $this->Title(t("Edit")." - ".$item["title"]);

        $data = $item["data"];
        if($data != "") $data = Config::sload($data);
        else $data = array("id" => -1, "alias" => "", "register" => 0, "url" => "");
        
        if(!isset($data["id"])) $data["id"] = "";
        if(!isset($data["alias"])) $data["alias"] = "";
        if(!isset($data["register"])) $data["register"] = 0;
        if(!isset($data["url"])) $data["url"] = "";

        $model = array(
            "id" => $item["id"],
            "menus" => $this->getMenus()[1],
            "title" => $item["title"],
            "box" => $item["box"],
            "type" => $item["typ"],
            "data" => $data,
            "isVisible" => $item["visible"] == 1,
            "levelRange" => [$item["milevel"], $item["malevel"]],
            "customLevel" => !($item["visible"]==1 || ($item["milevel"]==0 && $item["malevel"]==0) || ($item["milevel"]==0 && $item["malevel"]==10000) || ($item["milevel"]==1 && $item["malevel"]>1) || ($item["milevel"]==5000 && $item["malevel"]>5000)),
            "types" => array(
                ["index"        , "Link to main page"], 
                ["category"     , "Link to category"], 
                ["article"      , "Link to article"], 
                ["url"          , "Link to site"], 
                ["login"        , "Login"],
                ["separator"    , "Separator"]
            ),
            "articleList" => dibi::query('SELECT * FROM :prefix:article'),
            "categoryList" => dibi::query('SELECT * FROM :prefix:category ORDER BY id DESC')
        );
        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function add($box, $language = null){
        if($language == "") $language = null;

        $data = array(
            "title" => t("New item"),
            "typ" => "index",
            "visible" => 0,
            "position" => 999,
            "box" => $box,
            "milevel" => 0,
            "malevel" => 10000,
            "language" => $language
        );

        $result = dibi::query('INSERT INTO :prefix:menu', $data);
        
        return $this->Json(array("id" => dibi::getInsertId()));
    }
}