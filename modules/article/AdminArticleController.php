<?php
class AdminArticleController extends AdminController {
    public function __construct($root) {
        parent::__construct($root);
    }

    public static function GetSubMenu() {
        return [
            array("text" => "Category", "link" => "category"),
            array("text" => "Comments", "link" => "comments"),
        ];
    }

    public function index() {
        $this->Title(t("Articles"));

        $category = dibi::query('SELECT * FROM :prefix:category ORDER BY id');
        $model = array(
            "categoryList" => $category
        );

        return $this->View($model);
    }

    private function getArticle($id, $lang) {
        $result = dibi::query("SELECT * FROM :prefix:article WHERE (mid=%i", $id, ") AND language=%s", $lang)->fetch();
        if ($result == NULL) {
            $result = dibi::query("SELECT * FROM :prefix:article WHERE (id=%i", $id, ")")->fetch();
        }
        return $result;
    }

    function create($name) {
        $data = array(
            "title"    => $name,
            "alias"    => Strings::random(10),
            "date"       => time(),
            "author"   => User::current()["id"],
            "oauthor"  => User::current()["id"],
            "state"    => 5,
            "language" => _LANGUAGE
        );

        dibi::query('INSERT INTO :prefix:article', $data);
        $id = dibi::getInsertId();
        dibi::query('UPDATE :prefix:article SET ', array("mid" => $id, "alias" => Strings::undiacritic($name) . "_" . $id), 'WHERE `id`=%s', $id);

        return $this->Success("Created", ["id" => $id]);
    }

    /**
     * @method POST
     */
    function delete($id) {
        if (Database::getConfig("mainpage") == $id) {
            return $this->Error("You can't delete main page");
        }

        $data = array(
            "user"      => User::current()["id"],
            "ip"        => Utilities::ip(),
            "date"      => time(),
            "parent"    => "article_" . $id,
            "type"      => "article_recycled"
        );
        $result = dibi::query('INSERT INTO :prefix:history', $data);
        dibi::query('UPDATE :prefix:article SET ', array("state" => 4), 'WHERE `id`=%s', $id);
        return $this->Success("Deleted");
    }

    /**
     * @method POST
     */
    function undelete($id) {
        $data = array(
            "user"      => User::current()["id"],
            "ip"        => Utilities::ip(),
            "date"      => time(),
            "parent"    => "article_" . $id,
            "type"      => "article_cancel_recycled"
        );
        $result = dibi::query('INSERT INTO :prefix:history', $data);
        dibi::query('UPDATE :prefix:article SET ', array("state" => 0), 'WHERE `id`=%s', $id);
        return $this->Success("Restored");
    }

    /**
     * @method POST
     */
    function stoppublish($id) {
        if (Database::getConfig("mainpage") == $id) {
            return $this->Error("You can not unpublish main page");
        }

        dibi::query('UPDATE :prefix:article SET ', array("state" => 5), 'WHERE `id`=%s', $id);
        return $this->Success("Unpublished");
    }

    /**
     * @method POST
     */
    function recycle(){        
        if(!User::isPerm("recycle")) {
            return $this->Error("You don't have permission to recycle articles");
        }

        //remove other languages
        dibi::query('DELETE FROM :prefix:article WHERE state = 4');
        return $this->Success("Recycled");
    }

    /**
     * @method POST
     */
    public function editSave($id, $lang) {        
        $result = $this->getArticle($id, $lang);

        $stt = $result["state"];
        if (($stt != 0 && $stt != 5) || Database::getConfig("mainpage") == $result["id"]) {
        } else {
            $stt = (isset($_POST["public"]) ? 0 : 5);
        }

        $vis = $_POST["visiblity"];
        if ($vis == 1) $vis = "";
        elseif ($vis == 2) $vis = "2";
        else {
            $vis = "!" . $_POST["vishes-pass"];
        }

        if ($_POST["alias"] == "") {
            $_POST["alias"] = Strings::undiacritic($_POST["title"]);
        }

        $arr = array(
            "title" => $_POST["title"],
            "alias" => $_POST["alias"],
            "html" => $_POST["html"],
            "text" => $_POST["text"],
            "state" => $stt,
            "custommenu" => $_POST["custommenu"],
            "comments" => $_POST["comments"],
            "author" => ($_POST["author"] == "custom" ? "@" . $_POST["customname"] : $_POST["author"]),
            "visiblity" => $vis,
            "tags" => $_POST["article_tags"],
            "category" => $_POST["category"]
        );

        dibi::query('UPDATE :prefix:article SET ', $arr, 'WHERE `id`=%i', $_POST["oid"]);//, " AND language = %s", $lang

        /*if($result["mid"] == $result["id"]) {						
				$arr = array("alias" => $_POST["alias"]);
				dibi::query('UPDATE :prefix:article SET ', $arr, 'WHERE `mid`=%i', $result["mid"]);
			}*/

        $data = array(
            "user"         => User::current()["id"],
            "ip"           => Utilities::ip(),
            "date"         => time(),
            "data"         => Config::ssave($result),
            "text"         => $result["text"],
            "parent"       => "article_" . ($_POST["oid"]),
            "type"         => "article_history"
        );
        dibi::query('INSERT INTO :prefix:history', $data);

        //return $this->Json(array("state" => "success", "text" => t("Success"), "oid" => $_POST["oid"], $arr, dibi::$sql));
        return $this->Success();
    }

    private function getOrCreateArticle($id, $lang) {
        $result = $this->getArticle($id, $lang);
        if ($result == null) return null;
        if ($result["language"] != $lang) {
            $data = array(
                "mid"           => $result["id"],
                "title"         => $result["title"],
                "alias"         => $result["alias"],
                "date"          => time(),
                "author"        => User::current()["id"],
                "oauthor"       => $result["oauthor"],
                "state"         => 5,
                "text"          => $result["text"],
                "custommenu"    => $result["custommenu"],
                "comments"      => $result["comments"],
                "html"          => $result["html"],
                "tags"          => $result["tags"],
                "category"      => $result["category"],
                "language"      => $lang
            );
            dibi::query('INSERT INTO :prefix:article', $data);
            return $this->getArticle($id, $lang);
        }
        return $result;
    }

    public function edit($id) {
        $backLinks = [["name" => t("Articles"), "url" => "article/"]];
        $this->Title(t("Edit"), $backLinks);

        $lang = "";
        if (isset($_GET["lang"]))
            $lang = $_GET["lang"];
        if ($lang == "") $lang = _LANGUAGE;

        $result = $this->getOrCreateArticle($id, $lang);

        if ($result == NULL) {
            return $this->Error("Not found", "Article not found", 404);
        }

        $this->Title(t("Edit") . " - " . $result["title"], $backLinks);

        $result_menus = dibi::query('SELECT * FROM :prefix:menu');
        $menus = [];
        foreach ($result_menus as $n => $row) {
            if (!isset($menus[$row["box"]])) {
                $menus[$row["box"]] = array(true, 1);
            } else {
                $menus[$row["box"]][1]++;
            }
        }

        $isCustomName = substr($result["oauthor"], 0, 1) == "@";

        $model = array(
            "id" => $result["id"],
            "main_id" => $id,
            "title" => $result["title"],
            "alias" => $result["alias"],
            "text" => $result["text"],
            "isHtml" => $result["html"] == 1,
            "language" => $result["language"],
            "state" => $result["state"],
            "visiblity" => $result["visiblity"],
            "custommenu" => $result["custommenu"],
            "category" => $result["category"],
            "tags" => $result["tags"],
            "isCustomAuthor" => $isCustomName,
            "authorName" => $isCustomName ? substr($result["oauthor"], 1) : User::get($result["oauthor"])["nick"],
            "author" => $result["author"],
            "originalAuthorName" => User::get($result["oauthor"])["nick"],
            "comments" => !isset($result["comments"]) || $result["comments"] == "" ? 1 : $result["comments"],
            "authors" => dibi::query('SELECT * FROM :prefix:users ORDER BY id'),
            "categories" => dibi::query('SELECT * FROM :prefix:category ORDER BY id'),
            "languages" => explode(",", Database::getConfig("languages")),
            "default-language" => Database::getConfig("default-lang"),
            "template-supports-custom-menu" => $this->root->config->get("style.enable.custommenu"),
            "menus" => $menus,
            "history" => dibi::query('SELECT * FROM :prefix:history WHERE `parent`=%s', "article_" . $result["id"], " ORDER BY id DESC")
        );

        return $this->View($model);
    }

    public function comments() {
        $this->Title(t("Comments"), [["name" => t("Articles"), "url" => "article/"]]);

        $selectData = [];
        $result = dibi::query('SELECT * FROM :prefix:users');
        foreach ($result as $n => $row) {
            $selectData[] = $row["nick"];
        }

        return $this->View(array("selectList" => $selectData));
    }

    public function comments_restore($id) {
        dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 0), ' WHERE id = %s ', $id);
        return $this->Success("Restored");
    }

    public function comments_delete($id) {
        dibi::query('UPDATE :prefix:comments SET ', array("isDelete" => 1), ' WHERE id = %s ', $id);
        return $this->Success("Deleted");
    }

    public function data_comments($page, $limit, $filter) {
        $table = new DataTable("comments");
        if (isset($filter["name"]) && $filter["name"] != "") {
            $result = dibi::query("SELECT * FROM :prefix:users WHERE nick=%s", $filter["name"])->fetch();
            if ($result != null) {
                $filter["name"] = $result["id"];
            }

            //$table->like("ip", $filter["name"])->likeOr("autor", $filter["name"]);
            $table->where("ip = %s", $filter["name"])->whereOr("autor = %s", $filter["name"])->whereOr("autor = %s", "@" . $filter["name"]);
        }
        $table->order("time DESC")->limit($limit)->page($page);

        $rows = [];

        foreach ($table->fetch() as $n => $row) {
            $author = null;
            $custom = false;
            if (substr($row["autor"], 0, 1) == "@") {
                $author["nick"] = "<span class=anonym title='" . t("not logged in") . "'>" . substr($row["autor"], 1, strlen($row["autor"]) - 1) . "</span>";
                $author["avatar"] = Database::getConfig("default-avatar");
                $author["ip"] = t("not logged in");
                $custom = true;
            } else {
                $autor = User::get($row["autor"]);
                $perm = User::permission($autor["permission"]);
                $author["nick"] = "<span title='" . $perm["name"] . "' style='color:" . $perm["color"] . "'>" . $autor["nick"] . "</span>";
            }
            $ar = explode("_", $row["parent"], 2);

            $rows[] = array(
                "id" => $row["id"],
                "date" => $row["time"],
                "author" => $author,
                "isDeleted" => $row["isDelete"],
                "isCustom" => $custom,
                "parentType" => $ar[0],
                "parentData" => $ar[1],
                "ip" => $row["ip"],
                "text" => htmlentities($row["text"])
            );
        }

        /*$rows[] = array(
            "text" => $table->getSql()
        );*/

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    public function category_create($name) {
        $data = array(
            "name" => $name,
            "alias" => Strings::undiacritic($name),
            "description" => "",
            "minlevel" => 0
        );
        $result = dibi::query('INSERT INTO :prefix:category', $data);
        $id = dibi::getInsertId();

        return $this->Success("Created", ["id" => $id]);
    }

    public function category() {
        $this->Title(t("Category"), [["name" => t("Articles"), "url" => "article/"]]);

        return $this->View();
    }

    public function data_category($page, $limit, $filter) {
        $table = new DataTable("category");
        $table->where("parent = %i", 0)->order("id DESC")->limit($limit)->page($page);

        $rows = [];
        foreach ($table->fetch() as $n => $row) {
            $perm = User::permission($row["minlevel"]);
            $rows[] = array(
                "id" => $row["id"],
                "perm" => array("color" => $perm["color"], "name" => $perm["name"]),
                "name" => $row["name"],
                "alias" => $row["alias"]
            );
        }

        return $this->View(array("rows" => $rows, "total" => $table->count(), "page" => $page));
    }

    /**
     * @method POST
     */
    public function edit_categorySave($id) {
        $arr = array(
            "name" => $_POST["name"],
            "alias" => $_POST["alias"],
            "description" => $_POST["description"],
            "minlevel" => $_POST["permission"]
        );
        dibi::query('UPDATE :prefix:category SET ', $arr, 'WHERE `id`=%s', $id);

        return $this->Success();
    }

    /**
     * @method POST
     */
    function category_delete($id){
        if($id == 1) {
            return $this->Error("You can't delete main category");
        }
        
        dibi::query('DELETE FROM :prefix:category WHERE id = %i', $id);
        return $this->Success("Deleted");
    }

    public function edit_category($id) {
        $backLinks = [["name" => t("Articles"), "url" => "article/"], ["name" => t("Category"), "url" => "article/category/"]];
        $this->Title(t("Edit"), $backLinks);

        $result = dibi::query("SELECT * FROM :prefix:category WHERE id=%i", $id)->fetch();
        if ($result == NULL) {
            return $this->Error("Not found", "Category not found", 404);
        }

        $this->Title(t("Edit") . " - " . $result["name"], $backLinks);

        $model = array(
            "id" => $id,
            "name" => $result["name"],
            "alias" => $result["alias"],
            "description" => $result["description"],
            "minlevel" => $result["minlevel"],
            "permissions" => dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC')
        );

        return $this->View($model);
    }

    public function data($page, $limit, $filter) {
        $mainId = Database::getConfig("mainpage");
        $sql = array("FROM :prefix:article WHERE mid=id");

        if (isset($filter["category"]) && $filter["category"] != 0) {
            $sql = array_merge($sql, array(" AND category = %s", $filter["category"]));
        }
        if (isset($filter["active"]) && $filter["active"] == 1) {
            $sql = array_merge($sql, array(" AND state != %i", 5));
        }

        $result = dibi::query(array_merge(array("SELECT * "), $sql, array("ORDER BY id DESC LIMIT ", $limit, " OFFSET ", ($page - 1) * $limit)));
        $total = dibi::query(array_merge(array("SELECT count(*) "), $sql))->fetchSingle();
        $rows = [];
        foreach ($result as $n => $row) {
            $custom = false;
            $author = "";
            if (substr($row["author"], 0, 1) == "@") {
                $custom = true;
                $author = substr($row["author"], 1);
            } else {
                $author = User::get($row["author"])["nick"];
            }

            $rows[] = array(
                "id" => $row["id"],
                "title" => $row["title"],
                "author" => $author,
                "custom_author" => $custom,
                "alias" => $row["alias"],
                "date" => $row["date"],
                "isPublic" => $row["state"] != 5,
                "isDeleted" => $row["state"] == 4,
                "isMain" => $mainId == $row["id"]
            );
        }

        return $this->View(array("rows" => $rows, "total" => $total, "page" => $page));
    }
}
