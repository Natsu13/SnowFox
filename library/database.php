<?php
class Database {
	private $config 		= null;
	private $database_name 	= "";
	private $encode 		= "utf8";
	private $connect;
	public  $isConnected 	= false;
	public  $prefix 		= "";
	private $root;
	public  $_use			= "dibi"; //tibi

	public function __construct($root){
		$this->root = $root;

		include _ROOT_DIR . "/config/db.php";
		if($this->_use == "tibi")
			include _ROOT_DIR . "/include/tibi/tibi.php";
		else
			include _ROOT_DIR . "/include/dibi/dibi.min.php";

		$this->config = array(
							"server" 	=> $database["server"],
							"user" 		=> $database["login"],
							"password" 	=> $database["password"]
						);
		if( $database["prefix"] != "" ){ $database["prefix"] = $database["prefix"]."_"; }
		$this->prefix = $database["prefix"];
		$this->database_name = $database["database"];
	}

	private static $logEvents = array();
	public static function logEvent($event) {
		Database::$logEvents[] = array(
			"sql" => $event->sql,
			"result" => $event->result,
			"time" => $event->time,
			"count" => $event->count,
			"source" => $event->source,
		);
	}

	public static function getLogEvents(){
		return Database::$logEvents;
	}

	public function connect($global = true, $log = FALSE){
		$connection = false;

		$options = array(
			'driver'   => 'mysqli',
			'host'     => $this->config["server"],
			'username' => $this->config["user"],
			'password' => $this->config["password"],
			'database' => $this->database_name,
			'profiler' => array(
				'run' => $log,
				'file' => _ROOT_DIR . '/log/mysql.log.sql',
			),
			'charset'  => $this->encode,
			'folder'   => _ROOT_DIR . '/include/tibi/data/',
			'root'	   => $this->root
		);

		if($global){
			try {
				dibi::connect($options);
			}
			catch (DibiException $exception) {
				$this->root->message[] = array(
										"state"		=> $this->root->_MESSAGE_FATAL,
										"message" 	=> "(".$this->_use." Error) " . $exception->getMessage(),
										"execution_time" => round(microtime(true) - $this->root->time_start, 4)
									);
				$this->root->draw_log();
				die;
			}

				$connection = dibi::getConnection();
				if($this->_use != "tibi"){
				$connection->onEvent[] = 	function(DibiEvent $e){
												if ($e->result instanceof Exception) {
													if(isset($_COOKIE["debug"]) and $_COOKIE["debug"]==true){
														echo "<style>* {font-family: Arial;}</style>";
														$mess = "<div style='padding: 20px;padding-bottom: 0px;'>[".$e->result->getCode()."] ".$e->result->getMessage()."</div>";
														$mess.= "<div style='padding: 15px;border: 1px solid silver;margin: 20px;border-radius: 2px;background-color: #EDEDED;'>".$e->sql."</div>";
														$mess.= "<div style='padding: 15px;border: 1px solid silver;margin: 20px;border-radius: 2px;background-color: #EDEDED;'>".$e->source[0]." on line ".$e->source[1]."<br>".Utilities::getFileLines($e->source[0], $e->source[1])."</div>";
														$mess.= "<div style='padding: 15px;border: 1px solid silver;margin: 20px;border-radius: 2px;background-color: #EDEDED;'>".Utilities::post_debug(true)."</div>";
														echo Utilities::getFileLines($e->source[0], $e->source[1]);
														echo Utilities::fatal("Mysql error", $mess);
														exit;
													}else{
														echo "<h1>There was a mysql error</h1>";
														echo "<h2>Please contact the site owner</h2>";
														exit;
													}
												} else {
													if(_ENVIROMENT == "dev"){
														if($e->type == DibiEvent::SELECT){
															Database::logEvent($e);
														}
													}
												}
											};
				}
			if($log)
				$this->root->log("(".$this->_use." Info)[LOG] Logovací soubor je umístěn zde '" . _ROOT_DIR . "/log/mysql.sql'");
			$this->root->log("(".$this->_use." Info)[Global] Successfully connected to database \"" . $this->config["server"]  . ":" . $this->config["user"] . "@" . $this->database_name . "\" [Encode: " . $this->encode . "]");
		}
		else{
			if($this->_use != "tibi"){
				$connection = new DibiConnection($options);
				$this->connect = $connection;
				$this->root->log("(".$this->_use." Info)[Local] Successfully connected to database \"" . $this->config["server"]  . ":" . $this->config["user"] . "@" . $this->database_name . "\" [Encode: " . $this->encode . "]");
			}else{
				$this->root->message[] = array(
										"state"		=> $this->root->_MESSAGE_FATAL,
										"message" 	=> "(".$this->_use." Error) In tibi you can use only global conection!",
										"execution_time" => round(microtime(true) - $this->root->time_start, 4)
									);
				$this->root->draw_log();
				die;
			}
		}

		//Define prefix table
		//dibi::addSubst('prefix', $this->prefix);
		dibi::getSubstitutes()->prefix = $this->prefix;
		//Please use in query this format...
		//dibi::query("SELECT * FROM [:prefix:items]");

		$this->isConnected = true;

		return $connection;
	}

	private static $settings = [];

	public function load(){
		$result = dibi::query('SELECT * FROM :prefix:settings');

		foreach ($result as $n => $row) {
			$this->root->config->set_variable($row["name"], $row["value"], ($row["protected"]==1?true:false));
			Database::$settings[$row["name"]] = $row["value"];
		}

		//initial setting all variable
		$this->root->config->set_variable("title_head", $this->root->config->get("title"));
	}

	public static function getConfig($name){
		if(isset(Database::$settings[$name])) return Database::$settings[$name];

		$result = dibi::query('SELECT * FROM :prefix:settings WHERE name=%s', $name)->fetch();
		Database::$settings[$name] = $result["value"];
		return $result["value"];
	}

	public function setEncode($encode = "utf8"){
		$this->encode = $encode;
	}

	public function getError(){
		return mysql_error();
	}

	public function getConnect(){
		return $this->connect;
	}
}
