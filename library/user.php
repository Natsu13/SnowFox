<?php
class User {
	private $root;

	public function __construct($root){
		$this->root = $root;
	}

	public static $perms = array();
	private static $perms_default = array("admin","public","recycle","info","menu","users","content","system","style");

	public static function initPerms(){
		User::$perms = array_merge(User::$perms_default, User::$perms);
	}

	public static function isExists($name){
		$name_low = strtolower($name);
		$result = dibi::query('SELECT * FROM :prefix:users WHERE jmeno = %s', $name_low, 'or id = %i', $name_low, 'or email = %s', $name_low, "or jmeno = %s", $name);
		if(count($result) == 0)
			return false;
		else
			return true;
	}

	public static function logout(){
		User::session_remove();	
		header("location:".Router::url());
		exit;
	}

	public static function create($username, $pass, $email, $customForm = -1, $t = null, &$id, $perm = null){
		$error = array();
		$pass = sha1($pass); //hash
		$ip = Utilities::ip();

		$u=User::current();
		if($u!=false)
			$error[] = t("You are logged in as")." ".$u["nick"];
		if($ip == "")
			$error[] = t("Failed to get ip address");
		if(User::isExists($username))
			$error[] = t("A user account with this name already exists");
		if(User::isExists($email))
			$error[] = t("A user account with this email already exists");
		if(!Utilities::isEmail($email))
			$error[] = t("The email address entered is not valid");

		$reactive = $t->root->config->get("registration-activation");
		$key = Strings::random(8,Strings::$NUMBERS);

		$perm = ($perm == null ? Database::getConfig("default-permision"): $perm);

		$permission = User::permission($perm);

		if(count($error) == 0){
			$data = array(
						"jmeno" 	=> $username,
						"nick" 		=> mb_strtolower($username),
						"heslo" 	=> $pass,
						"email" 	=> strtolower($email),
						"ip" 		=> $ip,
						"kdy" 		=> time(),
						"prava" 	=> $perm,
						"avatar"	=> Database::getConfig("default-avatar"),
						"fanswer" 	=> $customForm,
						"blokovan"	=> ($reactive == 1 ? 2 : 0),
						"recovery"	=> ($reactive == 1 ? $key : ""),						
					);
			if($permission["expired"] == 1 && $permission["expired_register"] != ""){
				$data["expired"] = strtotime($permission["expired_register"]);
			}
			$result = dibi::query('INSERT INTO :prefix:users', $data);
			$id = dibi::getInsertId();
			if(!$result)
				$error[] = t("An error occurred while creating the user account");
			else if($reactive){
				Utilities::addHistory("user", "account", "created", array(), "Acount created", $id);

				$model = array(
					"key" => $key,
					"user_id" => $id,
					"user_name" => $username,
					"user_email" => $email,
					"url" => Router::url()."activate/?key=".$key."&user=".$id
				);
				if(!$t->root->utilities->sendemailtemplate($email, "USER_ACCOUNT_ACTIVATE", $model)) {
					$t->root->utilities->sendemail(
						$email, 
						t("Account activation"), 
						t("Click to activate your account")."<br><a href='".Router::url()."activate/?key=".$key."&user=".$id."'>".t("Activate account")."</a>"
					);
				}

			}else{
				Utilities::addHistory("user", "account", "created", array(), "Acount created", $id);
			}

			if($result) {
				Bootstrap::$self->getContainer()->get("notification")->create("New user registered", "User ".$username."(".$email.") created new account", "fas fa-user-plus", null, "register", Router::url()."adminv2/users/edit/".$id);
			}
		}

		if(count($error) == 0){			
			return true;		
		}else
			return $error;
	}

	public static function getData($name, $value = "", $user = null){
		if($user == null) $user = User::current(); else $user = User::get($user);
		if(!$user && Cookies::exists($name)) return $_COOKIE[$name];
		if(!$user) return $value;

		if(!isset($user["data"][$name])){ User::setData($name, $value); return $value; }
		else return $user["data"][$name];
	}

	public static function setData($name, $value, $user = null){	
		if($user == null) $user = User::current(); else $user = User::get($user);		

		if(!$user){
			$_COOKIE[$name] = $value;
			Cookies::set($name, $value, "+1 year");			
			return false;
		}		
		
		$user["data"][$name] = $value;				

		$arr = array("data" => Config::ssave($user["data"]));
		dibi::query('UPDATE :prefix:users SET ', $arr, 'WHERE `id`=%s', $user["id"]);

		$dbUser = User::getDbUserById($user["id"]);
		$dbUser["data"] = Config::ssave($user["data"]);
		Cache::Store("dbUser".$user["id"], $dbUser);

		return true;
	}

	public static function session_check(){
		if(Cookies::exists("session")){
			$result = dibi::query('SELECT * FROM :prefix:sessions WHERE hash = %s', $_COOKIE["session"]);
			if(count($result) != 0){
				$result = $result->fetch();
				if($result["closed"] == 1)
					return false;
				else {
					if(time() > $result["timeto"])
						return false;
					else
						return true;
				}
			}
		}
		return false;
	}

	public static function session_new($id, $time = "+1 hour", $force = false){
		if(User::session_check() && !$force){
			return true;
		}

		$hash = sha1(Strings::random(20).time().$_SERVER['HTTP_USER_AGENT']);
		Cookies::set("session", $hash, $time);

		$_data = Utilities::get_browser_properties();
		$_data = Config::ssave($_data);		

		$data = array(
						"user" 	=> $id,
						"ip" 	=> Utilities::ip(),
						"date"	=> time(),
						"timeto"=> strtotime($time),
						"hash" 	=> $hash,
						"data" 	=> $_data,
						"closed"=> 0
					);

		$result = dibi::query('INSERT INTO :prefix:sessions', $data);
		if(!$result){
			Cookies::delete("session");
			return false;
		}

		return true;
	}

	public static function session_new_as($id, $time = "+1 hour") {
		$superuser = Config::getS("superuser", 1);

		if(User::session_check() && User::isPerm("system") && $superuser != $id && User::current()["id"] != $id){
			$user = User::get($id);
			if($user == null) return false;

			Cookies::set("session_old", $_COOKIE["session"], $time);
			User::session_new($id, $time, true);

			return true;
		}

		return false;
	}

	public static function session_remove(){
		if(!User::session_check()){
			return false;
		}				

		dibi::query('UPDATE :prefix:sessions SET ', array("timeto" => time(), "closed" => 1), 'WHERE `hash`=%s', $_COOKIE["session"]);
		Cookies::delete("session");

		if(isset($_COOKIE["session_old"])){
			$old = User::session_old();
			if($old != false){				
				Cookies::set("session", $_COOKIE["session_old"], "+1 hour");
				Cookies::delete("session_old");
			}
		}

		return true;
	}

	public static function session_old(){
		if(isset($_COOKIE["session_old"])){
			$result = dibi::query('SELECT * FROM :prefix:sessions WHERE hash = %s', $_COOKIE["session_old"]);
			if(count($result) != 0){
				$result = $result->fetch();
				if($result["closed"] == 1)
					return false;
				else {
					if(time() > $result["timeto"])
						return false;
					else
						return $result;
				}				
			}
		}
		return false;
	}

	public static function getLanguage() {
		if(!isset($_COOKIE["language"])) {
			return Config::getS("default-lang", "cs");
		}
		return $_COOKIE["language"];
	}

	public static function login($username, $pass, $save = false, $onlycheck = false){		
		$error = array();
		$pass = sha1($pass); //hash
		$ip = Utilities::ip();

		if($ip == "")
			$error[] = t("Failed to get IP address");
		if(!User::isExists($username))
			$error[] = t("A user account with this name does not exist");

		if(count($error) == 0){
			if($onlycheck === 5)
				$result = dibi::query('SELECT * FROM :prefix:users WHERE (jmeno = %s', $username, ' OR email = %s ', $username,')');
			else
				$result = dibi::query('SELECT * FROM :prefix:users WHERE (jmeno = %s', $username, ' OR email = %s ', $username,') AND heslo = %s', $pass);

			if($result->count() != 0){
				$result = $result->fetch();

				if($result["language"] != ""){
					Cookies::set("language", $result["language"], "+1 year");
				}

				$permission = User::permission($result["prava"]);

				if(Config::getS("onlyttl", 0) == 1){
					$time = Config::getS("ttl", "+24 hour");
				}else{
					if($save) 
						$time = Config::getS("ttl", "+24 hour"); 
					else 
						$time = Config::getS("tts", "+8 hour");
				}

				if($permission["expired"]==1 && ($result["expired"] == "0" || $result["expired"] == "" || $result["expired"] < time())){
					if($result["expired"] != "" && $result["expired"] != "0")
						$error[] = t("Your account has expired")." <b>".date(Utilities::getTimeFormat(), $result["expired"])."</b>";
					else
						$error[] = t("Your account is blocked");
				}elseif($result["blokovan"] == 0){
					if($onlycheck !== true)
						User::session_new($result["id"], $time);
				}elseif($result["blokovan"] == 2){
					$error[] = t("Your account has not been activated").". <a href='".Router::url()."activate/?user=".$result["id"]."'>".t("Activate")."</a>";
				}elseif($result["blokovan"] == 3){
					$error[] = t("Your account can not be activated");
				}else{
					$error[] = t("Your account is blocked");
				}
			}
			else {
				$user = User::get($username);
				Utilities::addHistory("user", "account", "wrongPass", array(), "Trying login with wrong pass", $user["id"]);
				$error[] = t("An incorrect password was entered");
			}
		}

		if(count($error) == 0)
			return true;
		else
			return $error;
	}

	public static function getDataBySession(){
		return Cache::GetOrStoreByDb("currentUserSession", function(){ 
			return dibi::query('SELECT * FROM :prefix:sessions WHERE hash = %s', $_COOKIE["session"], " AND closed = %i", 0); 
		});
	}

	private static function getDbUserById($id) {
		return Cache::GetOrStoreByDb("dbUser".$id, function() use($id){ 
			return dibi::query('SELECT * FROM :prefix:users WHERE id = %s', $id); 
		});
	}

	private static function getDbUserByValue($value) {
		return Cache::GetOrStoreByDb("dbUser".$value, function() use($value){ 
			return dibi::query('SELECT * FROM :prefix:users WHERE id = %s', $value, ' OR jmeno = %s', $value, ' OR email = %s', $value); 
		});
	}

	public static function removeOtherSessions($userId = NULL){
		if($userId == NULL) $userId = User::current()["id"];
		dibi::query('UPDATE :prefix:sessions SET ', array("closed" => 1), 'WHERE `user`=%i', $userId, "AND hash != %s", $_COOKIE["session"]);
	}

	public static function changePassword($userId, $pass, $hashed = false) {
		if(!$hashed){ $pass = sha1($pass); }
		dibi::query('UPDATE :prefix:users SET ', array("heslo" => $pass), 'WHERE `id`=%i', $userId);
	}

	public static function isLoggedIn(){
		return User::current() !== false;
	}

	public static function currentOrNull($guest = false, $name) {
		$user = User::current($guest);
		if($user === false) {
			return null;
		}
		return $user[$name];
	}

	public static function current($guest = false){
		if(Cookies::exists("session")){
			$session = User::getDataBySession();			
			$id =  $session == null? -1: $session["user"];
			$result = User::getDbUserById($id);
			if($session != null && $result != null){
				return array(
						"id" 			=> $id,
						"nick" 			=> $result["nick"],
						"login" 		=> $result["jmeno"],
						"password"		=> $result["heslo"],
						"permission" 	=> $result["prava"],
						"time" 			=> $session["timeto"],
						"avatar"		=> $result["avatar"] == "" ? Database::getConfig("default-avatar") : $result["avatar"],
						"background"	=> $result["background"],
						"email"			=> $result["email"],
						"ip"			=> $result["ip"],
						"data"			=> Config::sload($result["data"]),
						"recovery"		=> $result["recovery"],
						"passlastchange"=> $result["passlastchange"],
						"jmeno"			=> $result["jmeno"],
						"perm"			=> User::permission($result["prava"])["permission"],
						"blokovan"		=> $result["blokovan"],
						"isGuest"		=> false
					);
			}else{
				if(!$guest)
					return false;
			}
		}else{
			if(!$guest)
				return false;
		}
		
		/*
		//Todo: toto cookies se mělo používat pro identifikaci uživatele ale ve výsledku to asi není dobré pokud by jen procházel web
		//		protože se mělo použít jen pro modul eshopu pro anonymní ID košíku což se nakonec nestalo a používá se stále ANID
		//		což je anonymní id, možná v budoucnu když budu potřebovat toto cookies ho vrátím ale teď kvuli GDPR odebráno [27.5.2020]
		$secret = User::getData("secret", null);
		if($secret == null){
			$secret = Utilities::GUID();
			User::setData("secret", $secret);
		}
		*/

		return array(
				"id" 			=> NULL,
				"nick" 			=> "GUEST",
				"login" 		=> "GUEST",
				"password"		=> "",
				"permission" 	=> 0,
				"time" 			=> time(),
				"avatar"		=> "",
				"background"	=> "",
				"email"			=> "",
				"ip"			=> Utilities::ip(),
				"data"			=> array(),
				"recovery"		=> "",
				"passlastchange"=> 0,
				"jmeno"			=> "GUEST",
				"perm"			=> User::permission(0)["permission"],
				"blokovan" 		=> 1,
				//"secret"		=> $secret,
				"isGuest"		=> true
			);
	}

	public static function get($id, $guest = false){
		//$result = dibi::query('SELECT * FROM :prefix:users WHERE id = %s', $id, ' OR jmeno = %s', $id, ' OR email = %s', $id);
		$result = User::getDbUserByValue($id);
		if($result != null){
			if($result["avatar"] == "") $result["avatar"] = "default.jpg";
			return array(
					"id" 			=> $result["id"],
					"nick" 			=> $result["nick"],
					"login" 		=> $result["jmeno"],
					"permission" 	=> $result["prava"],
					"time" 			=> time(),
					"avatar"		=> $result["avatar"] == "" ? Database::getConfig("default-avatar") : $result["avatar"],
					"background"	=> $result["background"],
					"email"			=> $result["email"],
					"ip"			=> $result["ip"],
					"data"			=> Config::sload($result["data"]),
					"recovery"		=> $result["recovery"],
					"passlastchange"=> $result["passlastchange"],
					"perm"			=> User::permission($result["prava"])["permission"],
					"blokovan"		=> $result["blokovan"]
				);
		}else{
			if(!$guest)
				return false;
		}

		return array(
				"id" 			=> -1,
				"nick" 			=> "GUEST",
				"login" 		=> "GUEST",
				"password"		=> "",
				"permission" 	=> 0,
				"time" 			=> time(),
				"avatar"		=> "",
				"background"	=> "",
				"email"			=> "",
				"ip"			=> Utilities::ip(),
				"data"			=> array(),
				"recovery"		=> "",
				"passlastchange"=> 0,
				"jmeno"			=> "GUEST",
				"perm"			=> User::permission(0)["permission"],
				"blokovan"		=> 1
			);
	}

	public static function find($column, $value){
		$result = dibi::query('SELECT * FROM :prefix:users WHERE ', array($column => $value));
		if(count($result) != 0){
			$result = $result->fetch();
			if($result["avatar"] == "") $result["avatar"] = "default.jpg";
			return array(
					"id" 			=> $result["id"],
					"nick" 			=> $result["nick"],
					"login" 		=> $result["jmeno"],
					"permission" 	=> $result["prava"],
					"time" 			=> time(),
					"avatar"		=> $result["avatar"],
					"background"	=> $result["background"],
					"email"			=> $result["email"],
					"ip"			=> $result["ip"],
					"data"			=> $result["data"],
					"recovery"		=> $result["recovery"],
					"passlastchange"=> $result["passlastchange"]
				);
		}else
			return false;
	}

	public static function setRecovery($id){
		$user = User::get($id);
		if(!$user)
			return false;
		else{
			if($user["recovery"] == ""){
				$key = Strings::random(8, Strings::$NUMBERS);
				$arr = array("recovery" => $key);
				dibi::query('UPDATE :prefix:users SET ', $arr, 'WHERE `id`=%s', $id, " OR `jmeno`=%s", $id, " OR email = %s", $id);
				return $key;
			}else{
				return -5;
			}
		}
	}

	public static function isPerm($name){
		if(User::current()["permission"] == -1) return true;
		if(!isset(User::permission(User::current()["permission"])["permission"][$name])) return false;
		return User::permission(User::current()["permission"])["permission"][$name];
	}

	private static function getDbPermissionById($id) {
		return Cache::GetOrStoreByDb("dbPermissionId".$id, function() use($id){ 
			return dibi::query('SELECT * FROM :prefix:permission WHERE id = %s', $id); 
		});
	}

	private static function getDbPermissionByLevel($level) {
		return Cache::GetOrStoreByDb("dbPermissionLevel".$level, function() use($level){ 
			return dibi::query('SELECT * FROM :prefix:permission WHERE level = %s', $level); 
		});
	}

	public static function permission($id, $type = "id"){
		if($id == null or $id == "") $id = 0;
		if($type == "id")
			$result = User::getDbPermissionById($id);
		else
			$result = User::getDbPermissionByLevel($id);

		if($result != null){
			return array(
					"id" 			=> $result["id"],
					"level" 		=> $result["level"],
					"name" 			=> $result["name"],
					"color" 		=> $result["color"],
					"image"			=> $result["image"],
					"expired"		=> $result["expired"],
					"expired_register" => $result["expired_register"],
					"permission"	=> Config::sload($result["data"])					
				);
		}else if($id == 0){
			return array(
					"id" 			=> 0,
					"level" 		=> 0,
					"name" 			=> "Neregistrovaný uživatel",
					"color" 		=> "black",
					"image"			=> "",
					"expired"		=> false,
					"expired_register" => "",
					"permission"	=> array("admin" => 0, "info" => 0, "article" => 0, "menu" => 0, "users" => 0, "content" => 0, "system" => 0)
				);
		}else
			return false;
	}

	public static function permission_set(){

	}

	public static $blockType = array("comments-add", "forum-add-post");
	public static function block($ip, $action, $seconds, $comment, $nick = "", $interinfo = ""){
		if(User::find("ip", $ip)){
			if(User::permission(User::find("ip", $ip)["permission"])["level"] == 10000)
				return;
		}
		$user = 0;
		if(User::current()) $user = User::current()["id"];
		$data = array(
						"nick" 			=> $nick,
						"ip" 			=> $ip,
						"time_long" 	=> strtotime("+".$seconds." seconds"),
						"add_ip" 		=> Utilities::ip(),
						"add_user" 		=> $user,
						"information" 	=> $comment,
						"action" 		=> $action,
						"okay"			=> 0,
						"interinfo" 	=> $interinfo
				);
		$result = dibi::query('INSERT INTO :prefix:block', $data);
	}

	public static function getBlock($ip, $nick, $action = null){
		if($action == null){
			$action = $nick;
			$nick = $ip;
		}
		return count(dibi::query('SELECT * FROM :prefix:block WHERE action = %s', $action, ' AND (ip = %s', $ip, ' OR nick = %s', $nick,') AND okay = 0 AND time_long > %i', strtotime("-1 month")));
	}
	
	public static function getLastBlock($ip, $nick, $action = null){
		if($action == null){
			$action = $nick;
			$nick = $ip;
		}
		return dibi::query('SELECT * FROM :prefix:block WHERE action = %s', $action, ' AND (ip = %s', $ip, ' OR nick = %s', $nick,') AND okay = 0 ORDER BY id DESC LIMIT 1')->fetch();
	}
	public static function isBlock($ip, $action){
		$result = dibi::query('SELECT * FROM :prefix:block WHERE action = %s', $action, ' AND okay = 0 AND (ip = %s', $ip, ' OR nick = %s', $ip,')');
		foreach ($result as $n => $row) {
			if(time() < $row["time_long"])
				return true;
		}
		return false;
	}
	public static function blockOkay($ip, $action){
		dibi::query('UPDATE :prefix:block SET ', array("okay" => 1), "WHERE `action`=%s", $action, ' AND okay = 0 AND (ip = %s', $ip, ' OR nick = %s', $ip,')');
	}
	public static function checkLogin(){
		if(Cookies::exists("user") && Cookies::exists("id")){
			if(isset($_GET["logout"])){
				//Cookies::delete(array("id","user","permission"));				
				User::session_remove();
				if($_GET["__type"] == "ajax")
					header("location:".Router::url(true, true));
				else
					header("location:./");
			}

			if(Cookies::exists("session"))
				$session_login = User::session_check($_COOKIE["session"]);
			else
				$session_login = false;

			if(!$session_login){
				echo "Your session has been canceled or has expired!";
				header("location:".Router::url()."login/?logout&session");
			}else if(!(Cookies::security_check("permission") and Cookies::security_check("id") and Cookies::security_check("session"))){
				echo "COOKIES SECURITY ERROR!";
				header("location:".Router::url()."login/?logout");
			}
		}
	}
	public static function debug($output = FALSE){
		$return = "<table style='table-layout: fixed; border-collapse: collapse;width:100%;' border=0 class='snowLeopard'>";
		$return.= "<tr><th width=300>Key</th><th width=600>Value</th></tr>";
		foreach(User::current() as $key => $value){
			$return.= "<tr><td>".$key."</td><td>".htmlentities($value)."</td></tr>";
		}
		$return.= "</table>";

		if(!$output)
			echo $return;
		return $return;
	}
}
