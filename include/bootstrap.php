<?php
function t($string, $format = ""){	
	if($format == "") return Strings::t($string);
	return Strings::t($string, $format);
}

class Bootstrap {
	public static $timestart;
	public $message = NULL;

	private $library = array("container", "flash", "modules", "database", "config", "router", "page", "images", "string", "utilities", "cookies", "user", "controls", "infobar", "cache");

	/* Message state variable */
	public	$_MESSAGE_LOG = -1,
			$_MESSAGE_INFO = 0,
			$_MESSAGE_OK = 1,
			$_MESSAGE_WARNING = 2,
			$_MESSAGE_ERROR = 3,
			$_MESSAGE_FATAL = 4;
	/* This variable provides the no info message write to base log file */
	private $_MINIMUM_LOGED_LEVEL = 1;

	public $base_log = "";
	/* Stop load system after fatal error in loading system library */
	public $exitOnFault = false;
	/* Number of loaded modules */
	public $Library_loaded = 0;
	private $container = null;

	/* Execution counter */
	public $time_start;
	public $time_end;
	public $time_initialized_end;

	public static $self = null;
	public static function GetTime($withountInitialize = false){
		if($withountInitialize)
			return Bootstrap::GetTime() - Bootstrap::GetTimeInitialize();
		return round(microtime(true) - Bootstrap::$self->time_start, 3)*1000;
	}
	public static function GetTimeInitialize(){
		return round(microtime(true) - Bootstrap::$self->time_initialized_end, 3)*1000;
	}

	public static $version = "1.4";

	public $log_called = "";

	public $GLOBAL_LANGUAGE = "cs";

	public $template = "defaultold";

	public static $debugger = null;

	public static function getContainer(){
		return Bootstrap::$self->container;
	}

	public function __construct( $bl ){
		Bootstrap::$self = $this;

		if(isset($_COOKIE["debug"]) && $_COOKIE["debug"]==true)
			define("_CACHE", time());
		else
			define("_CACHE", 16);
		if($bl == "" || $bl == NULL){ $bl = _ROOT_DIR . "/log/base.log"; }
		$this->base_log = $bl;
	}

	public function load(){				
		if(isset($_COOKIE["debug"]) && $_COOKIE["debug"]==true && _ENVIROMENT == "dev")
			define("_DEBUG", true);
		else
			define("_DEBUG", false);

		if(!isset($_COOKIE["language"])){ $lang = $this->GLOBAL_LANGUAGE; }else{ $lang = $_COOKIE["language"]; }	
		//$this->bootstrap = $this;

		$this->time_start = microtime(true);
		Bootstrap::$timestart = $this->time_start;
		$this->load_library( $this->library );

		$this->container = new Container();

		$this->container->set('core', $this, 'Core');
		$this->register_modules();

		Cookies::security_delete();//Check deleted security cookies

		$this->message[] = array(
							"state"		=> $this->_MESSAGE_INFO,
							"message" 	=> "Complete loaded libraries: " . $this->Library_loaded,
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
		
		if(!isset($_GET["__type"])){ $_GET["__type"] = "page"; }

		if(defined("_SYSTEM_LIBRARY_flash")){
			$flash = new Flash();
		}
		$debuger = $this->container->get('debuger');
		Bootstrap::$debugger = $debuger;
		$this->debugToken = $debuger->getToken();

		if(defined("_SYSTEM_LIBRARY_utilities")){
			$this->utilities = new Utilities($this);
		}

		if(defined("_SYSTEM_LIBRARY_infobar")){
			$this->infobar = new Infobar($this);
		}

		$this->router = new Router($this);
		$this->router->add("<module>[/<id>][/<page=1>]", "module=<module>&id=<id>&page=<page>");//Routa with lowest weight! Only to be used if no other route over it! Respectively below it.
		$this->router->add("<id>", "module=handler&id=<id>");//Default handling module if not then "article"

		if(defined("_SYSTEM_LIBRARY_config")){
			$this->config = new Config($this);
			$this->config->load_variables("main");			
			$this->container->set('config', $this->config, 'Config');
		}else{
			$this->message[] = array(
							"state"		=> $this->_MESSAGE_FATAL,
							"message" 	=> "Fatal error library \"config\" is not defined!",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
			$this->draw_log();
			die();
		}

		if(defined("_SYSTEM_LIBRARY_modules")){
			$this->module_manager = new Modules($this);
			//$this->module_manager->call_module("database");
		}else{
			$this->message[] = array(
							"state"		=> $this->_MESSAGE_FATAL,
							"message" 	=> "Fatal error library \"modules\" is not defined!",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
			$this->draw_log();
			die();
		}

		//Calling hook init
		$this->module_manager->hook_call("init");		

		if($this->config->get_variable("Compress", "main", "FALSE") == "TRUE"){
			$this->log("Runnin compression(ob_start)");
			if(!ob_start("ob_gzhandler", $this->config->get_variable("CompressChunk", "main", 16000))){
				ob_start();
				$this->log("Can't turn on gzip(ob_gzhandler)");
			} 
		}

		if(defined("_SYSTEM_LIBRARY_database")){
			$this->database = new Database($this);
			$dbc = $this->database->connect(true, ($this->config->get("LogMysql", "main")=="TRUE"?true:false));
		}else{
			$this->message[] = array(
							"state"		=> $this->_MESSAGE_FATAL,
							"message" 	=> "Fatal error library \"database\" is not defined!",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
			$this->draw_log();
			die();
		}
		
		//Calling hook with database
		$this->module_manager->hook_call("init.database");

		//Init permissions
		$this->module_manager->hook_call("init.permissions", array("perms" => &User::$perms));
		User::initPerms();

		if(defined("_SYSTEM_LIBRARY_config")){
			//$this->config = new Config($this);
			$this->config->set_variable("session_used", 0, false); //But we dont want it
			$this->database->load(); //Loading all configuration from database
			$this->log("The configuration was loaded from the database");
			date_default_timezone_set($this->config->get("utc"));
			$this->log("The time zone has been set to ".$this->config->get("utc"));
		}				

		if(!isset($_COOKIE["language"]))
			$lang = Database::getConfig("default-lang");
		else
			$lang = $_COOKIE["language"];				

		$this->template = (_DEBUG && isset($_GET["style"]) ? $_GET["style"] : Database::getConfig("style"));		

		//Calling hook with loaded setting
		$this->module_manager->hook_call("init.setting");

		//If some module want it on
		if($this->config->get("session_used", "main") == 1){
			session_start();
		}
		
		//Preload router config from admin settings		
		$this->router->preload();
		//Ajax route last				
		$this->router->add("ajax/<module>[/<id>][/<page=1>]", "__type=ajax&module=<module>&id=<id>&page=<page>");		
		$this->router->add("cron/<hash>", "__type=cron&hash=<hash>");
		
		$this->router->start();

		$pluginLang = $this->module_manager->hook_call("init.language.".$this->router->_data["module"][0], [$lang]);
		if($pluginLang["called"] > 0){
			$lang = $pluginLang["output"];
		}

		if(isset($_GET["showlang"])) {
			$lang = $_GET["showlang"];
		}

		define("_LANGUAGE", $lang);
		global $lng;
		if(file_exists(_ROOT_DIR . "/languages/" . $lang . ".php"))
			include _ROOT_DIR . "/languages/" . $lang . ".php";
		else {
			$lng = array();
			$this->log("Language file '/languages/".$lang.".php' was not found!", $this->_MESSAGE_ERROR);
		}

		/* Language handling */
		if(isset($_GET["setlang"])){
			if(file_exists(_ROOT_DIR . "/languages/" . $_GET["setlang"] . ".php")) {
				Cookies::set("language", $_GET["setlang"], "+1 year");
				$lang = $_GET["setlang"];
				$user = User::current();
				if($user != false) {
					dibi::query('UPDATE :prefix:users SET ', array("language" => $_GET["setlang"]), 'WHERE `id`=%i', $user["id"]);
				}
				header("location:".$_SERVER['HTTP_REFERER']);
			}
		}

		if(defined("_SYSTEM_LIBRARY_page")){
			$this->page = new Page($this);
		}else{
			$this->message[] = array(
							"state"		=> $this->_MESSAGE_FATAL,
							"message" 	=> "Fatal error library \"page\" is not defined!",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
			$this->draw_log();
			die();
		}		

		$handler = $this->router->_data["module"][0];

		if($handler == "handler"){
			$plugin = $this->module_manager->hook_call("page.default.handler", null, "article");
			$handler = $plugin["output"];
			$this->router->_data["module"][0] = $handler;
			$_GET["module"] = $handler;			
		}

		$this->config->set("system.page.use", "module");
		$this->config->set("system.page.name", $this->router->_data["module"][0]);		

		$this->module_manager->hook_call("page.".$this->router->_data["module"][0].".init.setting");				
		$this->module_manager->hook_call("page.global.init");		
		$this->module_manager->hook_call("page.".$this->router->_data["module"][0].".init.template");		

		$this->page->prepare();
		if($this->page->isAjax()){
			$_GET["__type"] = "ajax";
		}		

		$flash->flush();
		$flash->store();

		$this->log("Page was prepared");
		$this->time_initialized_end = microtime(true);

		if(isset($_GET["__type"]) and $_GET["__type"] == "ajax"){
			if($this->database->_use != "tibi")
				unset($dbc->onEvent[count($dbc->onEvent)-1]);
		}

		if($this->config->get("style") != "" && !(_DEBUG && isset($_GET["style"])))
			$this->template = $this->config->get("style");
		if($_GET["__type"] == "debug"){ $this->template = "default"; }

		$this->message[] = array(
							"state"		=> $this->_MESSAGE_OK,
							"message" 	=> "Initializate template \"". $this->template ."\"(" . _ROOT_DIR . "/templates/" . $this->template . "/init.php)",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
		include_once(_ROOT_DIR . "/templates/" . $this->template . "/init.php");		

		if(User::getBlock(Utilities::ip(), "all") > 0 && User::getLastBlock(Utilities::ip(), "all")["time_long"] > time()){
			$this->page->head();
			echo "<div style='width:600px;margin:20px auto;'>";
				$this->page->error_box(t("you has been baned until")." ".Strings::str_time(User::getLastBlock(Utilities::ip(), "all")["time_long"])."<hr>".User::getLastBlock(Utilities::ip(), "all")["information"], "error");
			echo "</div>";
			exit();
		}

		$cron = NULL; $cronCheck = sha1(time());
		if($_GET["__type"] == "cron") {
			$cron = $this->container->get('cron');
			$cronCheck = $cron->getHash();

			if($_GET["hash"] != $cronCheck) {
				$_GET["__type"] = "page";
			}
		}

		if($_GET["__type"] == "debug"){
			$debuger->draw($_GET["token"]);
		}
		else if($_GET["__type"] == "cron" && $_GET["hash"] == $cronCheck){
			$cron->execute();
		}
		else if($_GET["__type"] == "ajax"){
			$this->page->page_draw();
		}else{
			echo "<!DOCTYPE html>";
			echo "<html class=\"".$this->config->get("style.html.class")."\">";

			$this->page->head();// Show head of page

			echo "<body class=\"".$this->config->get("style.body.class")."\" ".$this->config->get("style.body.attributes").">";						

			$this->message[] = array(
								"state"		=> $this->_MESSAGE_OK,
								"message" 	=> "Loading template \"". $this->template ."\"(" . _ROOT_DIR . "/templates/" . $this->template . "/index.php)",
								"execution_time" => round(microtime(true) - $this->time_start, 4)
							);
			include_once(_ROOT_DIR . "/templates/" . $this->template . "/index.php");

			$this->page->footer();

			if(_DEBUG || _ENVIROMENT == "dev"){				
				$this->page->admin_panel();
			}

			echo "</body>";
			echo "</html>";
		}		

		if($_GET["__type"] != "debug")
			$this->log_write( $this->message );
		$this->time_end = microtime(true);

		$this->log("(DIBI Info) Celkový počet mysql příkazů: ".(dibi::$numOfQueries).", celkový čas v sekundách: ".(dibi::$totalTime));

		if((_DEBUG || _ENVIROMENT == "dev") && $_GET["__type"] != "debug"){
			$debuger->storeDebugPage();
		}

		return round($this->time_end - $this->time_start, 4);
	}

	private function load_library($library = array("log")){
		//First load init system library
		foreach($library as $name){
			if(file_exists(_ROOT_DIR . "/library/" . $name . ".init.php")){
				$file =  _ROOT_DIR . "/library/" . $name . ".php";
				$this->message[] = array(
							"state"		=> $this->_MESSAGE_OK,
							"message" 	=> "Initializate library \"". $name ."\"(" . $file . ")",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);

				include_once(_ROOT_DIR . "/library/" . $name . ".init.php");
			}
		}
		//Now load system library
		foreach($library as $name){
			$file =  _ROOT_DIR . "/library/" . $name . ".php";
			if(file_exists($file)){
				include_once($file);
				define("_SYSTEM_LIBRARY_" . $name, true);

				$this->message[] = array(
							"state"		=> $this->_MESSAGE_OK,
							"message" 	=> "Library \"". $name ."\"(" . $file . ") has successfully loaded",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);

				$this->Library_loaded++;
			}else{
				$this->message[] = array(
							"state"		=> $this->_MESSAGE_WARNING,
							"message" 	=> "Failed to load library \"". $name ."\"(" . $file . ")",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
				if($this->exitOnFault){
					$this->message[] = array(
							"state"		=> $this->_MESSAGE_ERROR,
							"message" 	=> "Loading halted after fatal error!",
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
					break;
				}
			}
		}
	}

	public function log($message, $state = NULL){
		if($state == NULL) { 
			$state = $this->_MESSAGE_LOG; 
		}
		
		if($this->log_called != ""){ 
			$m = "(" . $this->log_called . ") ".$message; 
		}else{ 
			$m=$message; 		
		}

		$this->message[] = array(
							"state"		=> $state,
							"message" 	=> $m,
							"execution_time" => round(microtime(true) - $this->time_start, 4)
						);
	}

	public function set_minimum_log_level( $level = 0 ){
		$this->_MINIMUM_LOGED_LEVEL = $level;
	}

	public function draw_log(){
		foreach( $this->message as $m ){
			if( $m["state"] == 0 ){ $c="blue"; }elseif( $m["state"] == 1 ){ $c="green"; }elseif( $m["state"] == 2 ){ $c="orange"; }elseif( $m["state"] == 3 ){ $c="red"; }elseif( $m["state"] == -1 ){ $c="silver"; }else{ $c="purple"; }
			echo "<span style='background-color:" . $c . ";padding: 0px;width: 6px;height: 14px;display: inline-block;margin-right: 3px;'> </span>";
			echo "<i style='color:silver;display: inline-block;width: 50px;'>".$m["execution_time"]."</i> ";
			echo $m["message"] . "</font><br>";
		}
	}	

	private function log_write( $log = NULL ){
		if($log != NULL){
			$soubor = fopen( $this->base_log, "a");
			foreach( $log as $data ){
				if( $data["state"] >= $this->_MINIMUM_LOGED_LEVEL )
				fwrite($soubor, Date("j/m/Y H:i:s", time()) . "||" . Utilities::ip() . "||" . $data["state"] . "||" . $data["message"] . "\r\n");
			}
			fclose($soubor);
		}
	}

	public function draw_debug_box(){
		if($_GET["__type"] == "page"){
			echo "<div id='debug_box' style='position:fixed;bottom: 0px;left:0px;right:0px;z-index: 5000;width: 60px;'>";
			echo "<div id='debug_button' style='background-color:black;color:white;padding: 10px 20px;float: right;right: 15px;position: relative;cursor:pointer;' onClick=\"togle_debug_box();\" title='Debug panel'> > </div>";
			echo "<div id=log style='background-color: black;color: white;padding: 20px;height: 350px;overflow: auto;width: 98%;'>";
			echo "<div style='border-bottom: 1px solid silver;margin-bottom: 7px;padding-bottom: 5px;'>LOG | <a href=# onClick=\"$(last).hide();last='#routing';$('#routing').show();\">ROUTING</a> | <a href=# onClick=\"$(last).hide();last='#cookies';$('#cookies').show();\">COOKIES</a> | <a href=# onClick=\"$(last).hide();last='#post';$('#post').show();\">POST/GET</a> | <b>DEBUG PANEL</b></div>";
			$this->draw_log();
			echo "</div>";
			echo "<div id=routing style='background-color: black;color: white;padding: 20px;height: 350px;display:none;overflow: auto;width: 98%;'>";
			echo "<div style='border-bottom: 1px solid silver;margin-bottom: 7px;padding-bottom: 5px;'><a href=# onClick=\"$(last).hide();last='#log';$('#log').show();\">LOG</a> | ROUTING | <a href=# onClick=\"$(last).hide();last='#cookies';$('#cookies').show();\">COOKIES</a> | <a href=# onClick=\"$(last).hide();last='#post';$('#post').show();\">POST/GET</a> | <b>DEBUG PANEL</b></div>";
			$this->router->draw_table();
			echo "</div>";
			echo "<div id=cookies style='background-color: black;color: white;padding: 20px;height: 350px;display:none;overflow: auto;width: 98%;'>";
			echo "<div style='border-bottom: 1px solid silver;margin-bottom: 7px;padding-bottom: 5px;'><a href=# onClick=\"$(last).hide();last='#log';$('#log').show();\">LOG</a> | <a href=# onClick=\"$(last).hide();last='#routing';$('#routing').show();\">ROUTING</a> | COOKIES | <a href=# onClick=\"$(last).hide();last='#post';$('#post').show();\">POST/GET</a> | <b>DEBUG PANEL</b></div>";
			Cookies::dump();
			echo "</div>";
			echo "<div id=post style='background-color: black;color: white;padding: 20px;height: 350px;display:none;overflow: auto;width: 98%;'>";
			echo "<div style='border-bottom: 1px solid silver;margin-bottom: 7px;padding-bottom: 5px;'><a href=# onClick=\"$(last).hide();last='#log';$('#log').show();\">LOG</a> | <a href=# onClick=\"$(last).hide();last='#routing';$('#routing').show();\">ROUTING</a> | <a href=# onClick=\"$(last).hide();last='#cookies';$('#cookies').show();\">COOKIES</a> | POST/GET | <b>DEBUG PANEL</b></div>";
			Utilities::post_debug();
			echo "</div>";
			echo "</div>";
		}
	}

	private function register_modules(){
		//Cron module definition because we don't need it every time
		$this->container->register('cron', array(
			"file" => "/library/cron.php",
			"class" => "Cron",
			"arguments" => array(
				"core",
				'debuger',
				'module.manager'
			)
		));

		$this->container->register('debuger', array(
			"file" => "/library/debuger.php",
			"class" => "Debuger",
			"arguments" => array(
				"core",
				Flash::class
			)
		));

		$this->container->register('notification', array(
			"file" => "/library/notification.php",
			"class" => "Notification",
			"arguments" => array(
				"core"
			)
		));
	}
}
