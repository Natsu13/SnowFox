<?php
class AdminContentController extends AdminController {
    public function __construct($root){
        parent::__construct($root);
    }

    public static function GetSubMenu() { 
        return [
            array("text" => "Generators", "link" => "generators")
        ]; 
    }

    private $formVersion = "2";

    /**
     * @method POST
     */
    function create($name){
        $data = array(
            "name" 		=> $name,
            "user"		=> User::current()["id"],
            "date"		=> time(),
            "settings"  => Config::ssave(["version" => $this->formVersion])
        );
        $result = dibi::query('INSERT INTO :prefix:form', $data);

        return $this->Success("Created", array("id" =>  dibi::getInsertId()));
    }

    public function index(){
        $this->Title(t("Forms"));
        return $this->View();
    }

    /**
     * @method POST
     */
    public function data($page, $limit, $filter){
        $result = dibi::query('SELECT f.id, f.date, f.parent, f.name, f.settings, f.data, count(a.id) answers FROM :prefix:form as f LEFT JOIN :prefix:form_answer as a ON f.id = a.parent', " WHERE f.parent = %s", "","GROUP BY f.id ORDER BY f.id DESC");
        $count = dibi::query('SELECT count(*) FROM :prefix:form')->fetchSingle();

        $rows = [];
        foreach ($result as $n => $row) {
            $data = Config::sload($row["data"]);
            $settings = Config::sload($row["settings"]);            
            $version = $settings["version"];
            if(isset($data["version"])) {
                $version = $data["version"];
            }
            if($version == "") $version = "1";

            $rows[] = array(
                "id" => $row["id"],
                "name" => $row["name"],
                "created" => $row["date"],
                "answers" => $row["answers"],
                "version" => $version,
                "isDeprecated" => floatval($version) < $this->formVersion
            );
        }

        return $this->View(array("rows" => $rows, "total" => $count, "page" => $page));
    }

    /**
     * @method POST
     */
    public function update($id){
        $form = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $id)->fetch();
        if($form == null)
            return $this->Error("Form not found");

        $settings = array(
            "redirect" => $_POST["redirect"],
            "enable" => (isset($_POST["enable"])?1:0),
            "onetime" => (isset($_POST["onetime"])?1:0),
            "succme" => $_POST["succes_message"],
            "version" => $this->formVersion
        );
        $arr = array(
            "name" 	=> $_POST["name"],
            "resend" => $_POST["resend"],
            "settings" 	=> Config::ssave($settings)
        );

        dibi::query('UPDATE :prefix:form SET ', $arr, 'WHERE `id`=%s', $id);
        return $this->Success();
    }

    private function getValue($value, $default) {
        if(!isset($value) || $value == "") return $default;
        return $value;
    }

    /**
     * @method POST
     */
    public function order_items($id, $ids) {
		$data = explode(";", $ids);
		for($i=0;$i<count($data);$i++){
			dibi::query('UPDATE :prefix:form_items SET ', array("position" => $i), 'WHERE `id`=%s', $data[$i]);
		}
        return $this->Success("Reordered");
    }

    /**
     * @method POST
     */
    public function delete_item($id) {
        $item = dibi::query('SELECT * FROM :prefix:form_items WHERE `id`=%i', $id)->fetch();
        if($item == NULL)
            return $this->Error("Item not found");

        dibi::query('DELETE FROM :prefix:form_items WHERE `id`=%i', $id);
        return $this->Success("Deleted");
    }

    /**
     * @method POST
     */
    public function add_item($id, $type){
        $form = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $id)->fetch();
        if($form == null)
            return $this->Error("Form not found");

        $data = array(
            "name" 		=> $type,
            "parent"	=> $id,
            "type"		=> $type,
            "position"	=> dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $id, " ORDER BY position")->count(),
            "data"      => Config::ssave(["position" => 1, "state" => 0])
        );
        dibi::query('INSERT INTO :prefix:form_items', $data);

        return $this->Success("Created", array("id" => dibi::getInsertId(), "type" => $type));
    }

    /**
     * @method POST
     */
    public function update_item($id){
        $item = dibi::query('SELECT * FROM :prefix:form_items WHERE `id`=%i', $id)->fetch();
        if($item == NULL)
            return $this->Error("Item not found");

        $data = Config::sload($item["data"]);

        $arr = array(
            "name" 	=> $_POST["name"],
            "value" => "",            
        );

        $data["position"] = $_POST["position"];
        if(isset($_POST["state"])) $data["state"] = $_POST["state"];

        if($item["type"] == "textbox" || $item["type"] == "password") {
            $data["placeholder"] 	= $_POST["placeholder"];
            //$data["customvalue"] 	= $_GET["rules"];
            $data["asemail"] 		= (isset($_POST["isEmail"]) && $_POST["isEmail"] == 1?1:0);
            $arr["value"]           = $_POST["value"];
        }
        else if($item["type"] == "text") {
			$arr["value"]           = $_POST["text"];
        }
        else if($item["type"] == "upload") {
            $data["folder"]			= $_POST["folder"];
            $data["maxsize"] 		= $_POST["maxsize"];
            $data["allowed"] 		= $_POST["allowed"];
            $data["resize"] 		= (isset($_POST["resize"]) && $_POST["resize"] == 1?1:0);
            $data["resizew"] 		= $_POST["resizew"];
            $data["resizeh"] 		= $_POST["resizeh"];
        }
        else if($item["type"] == "textarea") {
			$data["placeholder"] 	= $_POST["placeholder"];
			$data["rows"]			= $_POST["rows"];
			$arr["value"]           = $_POST["value"];
        }
        else if($item["type"] == "select") {                        
            $data["types"] 			= $_POST["select_type"];
            $data["custom"] 		= (isset($_POST["enableCustom"])?1:0);
            if(isset($_POST["variety"])){ $data["place"] = $_POST["variety"]; }

            $data["items"] = "";
			$i=0;$a=0;
			while(isset($_POST["select_item_name"][$i])){
                if($_POST["select_item_name"][$i] != "") {
                    if($a!=0) $data["items"].="[;";

                    if(($data["types"]=="4" or $data["types"]=="1"))
                        $s = (($_POST["select_item_selected"] == $i)?1:0);
                    else
                        $s = (isset($_POST["select_item_selected"][$i]) && $_POST["select_item_selected"][$i] == 1?1:0);

                    $data["items"].= $_POST["select_item_name"][$i]."[,".$_POST["select_item_value"][$i]."[,".($s);
                    $a++;
                }
				$i++;
			}
        }
        else if($item["type"] == "variable") {
            $data["list"]			= $_POST["list"];
            $data["next"]			= $_POST["next"];
            $data["stop"]			= $_POST["stop"];
            $data["closeatstop"]	= (isset($_POST["closeatstop"]) && $_POST["closeatstop"] == 1? 1: 0);
            $data["stopat"]			= $_POST["stopat"];
        }
        else if($item["type"] == "picker"){
			$data["displayas"]      = $_POST["displayas"];
			$data["size"]  			= $_POST["size"];
            $data["fontsize"]  		= $_POST["fontsize"];
            $data["online"]         = $_POST["online"];
            
            $data["items"] = "";
            $i=0;$a=0;
            while(isset($_POST["item_name"][$i])){
                if($_POST["item_name"][$i] != "") {
                    if($a!=0) $data["items"].="[;";

                    $data["items"].= $_POST["item_name"][$i]."[,".$_POST["item_description"][$i]."[,".$_POST["item_minperm"][$i]."[,".$_POST["item_maxusage"][$i];
                    $a++;
                }
                $i++;
            }
        }
        else if($item["type"] == "slider") {
            $data["value_min"] = $_POST["value_min"];
            $data["value_max"] = $_POST["value_max"];
            $data["step"] = $_POST["step"];
            $arr["value"] = $_POST["value"];
            $data["title"] = $_POST["title"];

            if(intval($data["step"]) < 1) $data["step"] = 1;
            if(intval($data["value_min"]) > intval($data["value_max"])) {
                $v = $data["value_min"];
                $data["value_min"] = $data["value_max"];
                $data["value_max"] = $v;
            }
        }

        $arr["data"] = Config::ssave($data);
        dibi::query('UPDATE :prefix:form_items SET ', $arr, 'WHERE `id`=%s', $id);

        return $this->Success();
    }

    /**
     * @method POST
     */
    public function load_item($id, $editing = false) {
        $item = dibi::query('SELECT * FROM :prefix:form_items WHERE `id`=%i', $id)->fetch();
        $data = Config::sload($item["data"]);

        $model = array(
            "id" => $item["id"],
            "name" => $item["name"],
            "type" => $item["type"],
            "value" => $item["value"],
            "state" => $data["state"],
            "position" => $this->getValue($data["position"], 1),
            "isEditing" => $editing
        );

        if($model["type"] == "textbox") {
            $model["isEmail"] = $data["asemail"] == 1;
        }
        if($model["type"] == "slider") {
            $model["value_min"] = $this->getValue($data["value_min"], "0");
            $model["value_max"] = $this->getValue($data["value_max"], "100");
            $model["step"] = $this->getValue($data["step"], "1");
            $model["title"] = $data["title"];
        }
        if($model["type"] == "upload") {
            $model["folder"] = $data["folder"];
            $model["maxsize"] = $this->getValue($data["maxsize"], "1048576");
            $model["allowed"] = $this->getValue($data["allowed"], "gif,png,jpg");
            $model["resize"] = $this->getValue($data["resize"], 0) == 1;
            $model["resizew"] = $this->getValue($data["resizew"], 1024);
			$model["resizeh"] = $this->getValue($data["resizeh"], 1024);
        }
        if($model["type"] == "textbox" || $model["type"] == "password" || $model["type"] == "textarea"){
            $model["placeholder"] = $data["placeholder"];
        }
        if($model["type"] == "textarea") {
            $model["rows"] = $data["rows"] == ""? 3: $data["rows"];
        }
        if($model["type"] == "select") {
            $model["select_type"] = $data["types"] == ""? 1: $data["types"];
            $model["variety"] = $data["place"] == ""? 1: $data["place"];
            $model["enableCustom"] = $data["custom"] == 1;
            $model["items"] = [];
            $items = explode("[;", $data["items"]);
            foreach($items as $key => $value) {
                $dtvl = explode("[,", $value);
                $model["items"][] = array("name" => $dtvl[0], "value" => $dtvl[1], "checked" => $dtvl[2] == 1);
            }
        }
        if($model["type"] == "variable") {
            $model["list"] = $data["list"];
            $model["next"] = $this->getValue($data["next"], 1);
            $model["stop"] = $this->getValue($data["stop"], 1);
            $model["stopat"] = $data["stopat"];
            $model["closeatstop"] = $data["closeatstop"] == 1;
        }
        if($model["type"] == "picker") {
            $model["displayas"] = $this->getValue($data["displayas"], "row");
            $model["size"] = $data["size"];            
            $model["online"] = intval($this->getValue($data["online"], 0));
            $model["fontsize"] = $this->getValue($data["fontsize"], 12);
            $model["permList"] = [];
            $perms = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
            foreach($perms as $perm) {
                $model["permList"][$perm["id"]] = array("name" => $perm["name"], "color" => $perm["color"]);
            }
            $model["items"] = [];
            $items = explode("[;", $data["items"]);
            foreach($items as $key => $value) {
                $dtvl = explode("[,", $value);
                $model["items"][] = array(
                    "name" => $dtvl[0], 
                    "description" => $dtvl[1], 
                    "minperm" => $dtvl[2],
                    "maxusage" => $dtvl[3]
                );
            }
        }

        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function get_elements_definition($id) {
        $form = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $id)->fetch();
        if($form == null)
            return $this->Error("Form not found");

        $result = [];
        $items = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $form["id"], " ORDER BY position");
        foreach ($items as $n => $row) {
            $result[] = array("id" => $row["id"], "name" => $row["name"], "type" => $row["type"]);
        }

        return $this->Json($result);
    }

    public function edit($id) {
        $form = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $id)->fetch();
        if($form == null)
            return $this->Error("Form not found");

        $this->Title($form["name"], [["name" => t("Forms") , "url" => "content/"]]);

        $settings = Config::sload($form["settings"]);
        $data = [];
        $str_data = "";
        if(!isset($settings["version"]) || floatVal($settings["version"]) < 2) {
            $data = Config::sload($form["data"]);
            $data["version"] = $this->formVersion;
            dibi::query('UPDATE :prefix:form SET ', array("settings" => Config::ssave($data), "data" => ""), 'WHERE `id`=%s', $id);
            $settings = $data;
        }else{
            $data = json_decode($form["data"]);
            $str_data = $form["data"];
        }
        if($str_data == "") $str_data = "null";

        if(!isset($settings["width"]) || $settings["width"] == "") $settings["width"]="200";
		if(!isset($settings["redirect"])) $settings["redirect"] = "";
		if(!isset($settings["succme"])) $settings["succme"] = "";
		if(!isset($settings["enable"])) $settings["enable"] = 0;
		if(!isset($settings["onetime"])) $settings["onetime"] = 0;  

        $model = array(
            "id" => $form["id"],
            "name" => $form["name"],
            "redirect" => $settings["redirect"],
            "resend" => $form["resend"],
            "succes_message" => $settings["succme"],
            "data" => $data,
            "strData" => str_replace("\"", "\\\"", $str_data),
            "version" => $settings["version"],
            "isEnabled" => $settings["enable"] == 1,
            "isOneTime" => $settings["onetime"] == 1,            
            "itemList" => [],            
        );

        $items = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $form["id"], " ORDER BY position");
        foreach ($items as $n => $row) {
            $model["itemList"][] = array("id" => $row["id"], "name" => $row["name"], "type" => $row["type"]);
        }

        return $this->View($model);
    }

    /**
     * @method POST
     */
    public function data_answers($id, $page, $limit, $filter){
        $table = new DataTable("form_answer");
        $table->where("parent = %i", $id)->order("time DESC")->limit($limit)->page($page);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {
            $userReg = User::find("fanswer", $row["id"]);
            $browser = Utilities::get_browser_properties($row["browser"]);
            $browserDefault = Strings::take($row["browser"], 50);

            $rows[] = array(
                "id" => $row["id"],
                "ip" => $row["ip"],
                "user" => $userReg == false? User::get($row["user"]): $userReg,
                "submit" => $row["submit"],
                "created" => $row["time"],
                "browser" => $browser === false? $browserDefault: $browser["browser"]." <i>".$browser["version"]."</i>",
                "isFromRegistration" => $userReg != null,
                "isKnowUser" => $row["user"] != -1          
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    public function edit_answers($id) {
        $answer = dibi::query("SELECT * FROM :prefix:form_answer WHERE id=%i", $id)->fetch();
        if($answer == null)
            return $this->Error("Answer not found");

        $form = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $answer["parent"])->fetch();
        if($form == null)
            return $this->Error("Form not found");

        $this->Title(t("Views"), [["name" => t("Forms") , "url" => "content/"], ["name" => $form["name"] , "url" => "content/edit/".$form["id"]], ["name" => t("Answers") , "url" => "content/answers/".$form["id"]]]);

        $userReg = User::find("fanswer", $form["id"]);
        $browser = Utilities::get_browser_properties($answer["browser"]);
        $browserDefault = Strings::take($answer["browser"], 50);

        $model = array(
            "id" => $answer["id"],
            "user" => User::get($answer["user"]),
            "ip" => $answer["ip"],
            "submit" => $answer["submit"],
            "created" => $answer["time"],
            "browser" => $browser === false? $browserDefault: $browser["browser"]." <i>".$browser["version"]."</i>",
            "isFromRegistration" => $userReg != null,
            "formData" => []
        );

        $data = Config::sload($answer["data"]);
        for($i=0;$i<count($data);$i++){
            $m = array(
                "id" => $i,
                "title" => $data[$i][0],
                "type" => $data[$i][1]
            );

            if($m["type"] == "select") {
                $m["value"] = explode("[;", $data[$i][2]);
            }
            else if($m["type"] == "upload") {
                $m["value"] = $data[$i][2];
            }
            else if($m["type"] == "picker") {
                $c = explode("[;", $data[$i][2]);
                $d = explode("[,", $c[1]);
                $m["value"] = array(
                    "title" => $d[0],
                    "description" => $d[1],
                    "minimalPerm" => User::permission($d[2])
                );
            }
            else { 
                if($model["isFromRegistration"] && $m["type"] == "password") {
                    $m["value"] = str_repeat("*", $data[$i][2]);
                }else{
                    $m["value"] = $data[$i][2];
                }
            }

            $model["formData"][] = $m;
        }

        return $this->View($model);
    }

    public function answers($id) {
        $this->Title(t("Answers"), [["name" => t("Forms") , "url" => "content/"]]);
        $form = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $id)->fetch();
        if($form == null)
            return $this->Error("Form not found");

        $this->Title(t("Answers"), [["name" => t("Forms"), "url" => "content/"], ["name" => $form["name"] , "url" => "content/edit/".$form["id"]]]);

        $model = array(
            "id" => $id
        );
        return $this->View($model);
    }
}