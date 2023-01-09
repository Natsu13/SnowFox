<?php
class Notification {
	private $root;

	public function __construct(Bootstrap $root){
        $this->root = $root;
    }

    public function getAll($limit = 20){        
        return dibi::query("SELECT * FROM :prefix:notifications WHERE (to_user=%i", User::currentOrNull(false, "id"), " OR to_group = %i", User::currentOrNull(false, "permission"),") or (to_user is NULL and to_group is NULL) ORDER BY id LIMIT ".$limit);        
    }

    public function getCount() {
        return dibi::query("SELECT count(*) FROM :prefix:notifications WHERE viewed is NULL AND (to_user=%i", User::currentOrNull(false, "id"), " OR to_group = %i", User::currentOrNull(false, "permission"),") or (to_user is NULL and to_group is NULL)")->fetchSingle();
    }

    public function create($title, $text, $icon = null, $image = null, $tag = null, $link = null, $toUser = null, $toGroup = null){
        if($toUser == null && $toGroup == null) $toUser = User::currentOrNull(true, "id");
        if($tag == null) $tag = $this->root->log_called;
        if($tag == null) $tag = "System";

        $creator = explode("::", $this->root->log_called)[1];
        if($creator == null) $creator = "System";

        $data = array(
            "title" => $title,
            "text" => $text,
            "icon" => $icon,
            "creator" => $creator,
            "id_creator" => User::currentOrNull(true, "id"),
            "created" => time(),
            "to_user" => $toUser,
            "to_group" => $toGroup,
            "image" => $image,
            "tag" => $tag,
            "link" => $link
        ); 
        dibi::query('INSERT INTO :prefix:notifications', $data);

        return dibi::getInsertId();
    }

    public function markAsRead($id){
        dibi::query("UPDATE :prefix:notifications SET viewed = %i WHERE id = %i", time(), $id);
    }
}