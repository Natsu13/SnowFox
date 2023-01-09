<?php
class Infobar {
	private $root;

	public function __construct($root){
        $this->root = $root;
    }

    public function getAllByType($type){
        $ip = Utilities::ip();
        $user = User::current(true);

        $result = dibi::query('SELECT toolbar.*, (SELECT count(*) FROM :prefix:toolbar_interaction WHERE (user = %i', $user["id"],' OR ip = %s', $ip,') AND type = %s', "view",' AND toolbar_id = toolbar.id) as toolbar_view, (SELECT time FROM toolbar_interaction WHERE (user = %i', $user["id"],' OR ip = %s', $ip,') AND type=%s ', "view",' AND toolbar_id = toolbar.id ORDER BY time DESC LIMIT 1) as toolbar_last_view, (SELECT time FROM toolbar_interaction WHERE (user = %i', $user["id"],' OR ip = %s', $ip,') AND type=%s ', "close",' AND toolbar_id = toolbar.id ORDER BY time DESC LIMIT 1) as toolbar_last_close FROM :prefix:toolbar as toolbar WHERE type = %s', $type,' ORDER BY active_from DESC');

        $ret = array();
        foreach ($result as $n => $row) {            
            if($row["active"] != 1) continue;
            if($row["active_from"] > time()) continue;
            if($row["active_until"] != "" && $row["active_until"] < time()) continue;

            $ret[]= array(
                "id" => $row["id"],
                "alias" => $row["alias"],
                "data" => json_decode($row["data"], true),
                "title" => $row["title"],
                "text" => Utilities::GetBBCode($this->root, $row["text"]),
                "view" => $row["toolbar_view"],
                "last_view" => $row["toolbar_last_view"],
                "last_close" => $row["toolbar_last_close"]
            );
        }

        return $ret;
    }

    public function closeToolbar($toolbarId){
        $data = array(
            "toolbar_id" => $toolbarId,
			"type" => "close",
			"time" => time(),
			"user" => User::current(true)["id"],
			"ip" => Utilities::ip(),
			"browser" => $_SERVER['HTTP_USER_AGENT']
		);
		dibi::query('INSERT INTO :prefix:toolbar_interaction', $data);
    }

    public function registerToolbarView($toolbarId){
		$data = array(
            "toolbar_id" => $toolbarId,
			"type" => "view",
			"time" => time(),
			"user" => User::current(true)["id"],
			"ip" => Utilities::ip(),
			"browser" => $_SERVER['HTTP_USER_AGENT']
		);
		dibi::query('INSERT INTO :prefix:toolbar_interaction', $data);
	}

    public static function getTypes(){
        return array(
            array(
                "name" => "Top Toolbar", 
                "id" => "top_bar", 
                "description" => "Toolbar show at top width full width",
                "picto" => "<div class=picto-border><div class=picto-topbar></div><div class=picto-page><div class=picto-placeholder></div><div class=picto-placeholder></div></div></div>"
            ),
            array(
                "name" => "Popup window", 
                "id" => "popup_window", 
                "description" => "Popup window over screen width close button",
                "picto" => "<div class=picto-border><div class=picto-message><div class=picto-text></div><div class=picto-button></div></div></div>"
            ),
            /*array( //module
                "name" => "Cart Toolbar", 
                "id" => "cart_bar", 
                "description" => "Toolbar show at top of basket",
                "picto" => "<div class=picto-border><div class=picto-topbar></div><div class=picto-page><div class=picto-placeholder></div><div class=picto-placeholder></div></div></div>"
            ),*/
            array(
                "name" => "Confirm pages", 
                "id" => "confirm_page", 
                "description" => "A separate page with text that requires interaction",
                "picto" => "<div class=picto-border><div class=picto-message><div class=picto-text></div><div class=picto-button></div></div></div>"
            )
        );
    }

    public static function getById($id){
        foreach(Infobar::getTypes() as $key => $type){
            if($type["id"] == $id)
                return $type;
        }
        return null;
    }
}