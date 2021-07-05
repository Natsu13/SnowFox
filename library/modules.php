<?php
/* Class for malipuation with modules */
class Modules {
	private $modules = NULL;
	private $Modules_loaded = 0;
	public $root;
	public $router;
	
	private $actual_module_name = "";

	public static $calledHooks = array();
	
	function __construct($root){
		$this->root = $root;
		$this->router = $this->root->router;
		$plugins = $this->getEnabled();

		$adresar = opendir(_ROOT_DIR . "/modules/");
		while ($dir = readdir($adresar)){			
			if( is_dir( _ROOT_DIR . "/modules/" . $dir ) and $dir!="." and $dir!=".." ){
				if(!in_array($dir, $plugins)) {
					$root->message[] = array( 
						"state"		=> $root->_MESSAGE_INFO, 
						"message" 	=> "Module \"". $dir ."\"(" . _ROOT_DIR . "/modules/" . $dir . ".php) is not enabled",
						"execution_time" => round(microtime(true) - $root->time_start, 4)
					);
					continue;
				}
				//if(file_exists(_ROOT_DIR . "/modules/" . $dir . "/" . $dir . ".config.php")){
				//	include_once(_ROOT_DIR . "/modules/" . $dir . "/" . $dir . ".config.php");						
									
					$this->modules[$dir] = array(
													"Dir" => _ROOT_DIR . "/modules/" . $dir . "/",
													"CName" => $dir,
													"Enable" => 1
												);
					
					/*$root->message[] = array( 
								"state"		=> $root->_MESSAGE_INFO, 
								"message" 	=> "Load module(".$dir.") configuration \"". $dir ."\"(" . _ROOT_DIR . "/modules/" . $dir . ".config.php)",
								"execution_time" => round(microtime(true) - $root->time_start, 4)
							);*/
					
					$root->message[] = array( 
								"state"		=> $root->_MESSAGE_OK, 
								"message" 	=> "Loading module file \"". $dir ."\"(" . _ROOT_DIR . "/modules/" . $dir . ".php)",
								"execution_time" => round(microtime(true) - $root->time_start, 4)
							);

					$this->actual_module_name = $dir;
					include_once(_ROOT_DIR . "/modules/" . $dir . "/" . $dir . ".php");
					
					$this->Modules_loaded++;
				/*}else{				
					$root->message[] = array( 
								"state"		=> $root->_MESSAGE_WARNING, 
								"message" 	=> "Module config file is missing! (".$dir.")",
								"execution_time" => round(microtime(true) - $root->time_start, 4)
							);
				}*/
			}
		}
		
		/* Seřazení podle priority */
		foreach($this->modules as $mod){
			ksort($mod);
		}
		
		if($this->Modules_loaded == 0){
			$m = "No modules found in dir: " . _ROOT_DIR . "/modules/";
		}else{
			$m = "Successfully initialize modules: ".$this->Modules_loaded;
		}
		
		$root->message[] = array( 
							"state"		=> $root->_MESSAGE_INFO, 
							"message" 	=> $m,
							"execution_time" => round(microtime(true) - $root->time_start, 4)
						);

		$root->getContainer()->set('module.manager', $this);
	}
	
	/*
	This is simple hook funkcions
	*/
	private $hooks = NULL;
	public  $global_count_hook_called = 0;

	function getAllHooks(){
		return $this->hooks;
	}
	
	function hook_register($hook_name, $function_name, $priotiry = 0){
		if($priotiry < -10){ $priotiry = -10; }else if($priotiry > 10){ $priotiry = 10; }
		$this->hooks[$hook_name][$priotiry][$this->actual_module_name][] = $function_name;
		$this->hooks[$hook_name]["debug_info"][] = Utilities::getCallerInfo(1, true);
		$this->hooks[$hook_name]["debug_info_byfun"][$function_name] = Utilities::getCallerInfo(1, true);
		ksort($this->hooks[$hook_name]);
	}
	
	function hook_debuginfo($hook_name) {
		return $this->hooks[$hook_name]["debug_info"];
	}

	function hook_exists($hook_name){
		return isset($this->hooks[$hook_name]);
	}

	/**
	 * @param bool $strictArraySizeOutput Enabling this the call count array size and if the module change it it will throw error
	 */
	function hook_call($hook_name, $data = NULL, $output = "", $noinfo = false, $log = true, $strictArraySizeOutput = false){
		if(!isset(Modules::$calledHooks[$hook_name])) Modules::$calledHooks[$hook_name] = array();

		$calleds = array(
			"time" => time(),
			"caller" => Utilities::getCallerInfo(1, true),
			"calls" => array()
		);

		$arraySize = 0;

		global $module_called_name;
		$called = 0;
		$data[] = $this;
		$data[] = &$output;
		if(!isset($this->hooks[$hook_name])){ 
			$this->hooks[$hook_name] = NULL; 
			if($log)
				$this->root->log("Create empty hook: ".$hook_name);
		} 
		if(count($this->hooks[$hook_name]) > 0){
			foreach($this->hooks[$hook_name] as $hooks_p){
				foreach($hooks_p as $m_name => $hooks){
					$module_called_name = $hook_name."::".$m_name;
					$this->root->log_called = $hook_name."::".$m_name;
					if($this->modules[$m_name]["Enable"] == 1){						
						foreach($hooks as $hook){
							$f = new ReflectionFunction($hook);
							$calleds["calls"][] = array("time" => time(), "name" => $f->getName(), "file" => $f->getFileName(), "params" => $f->getParameters());
							$return = call_user_func_array($hook, $data);
							if($return != null) {
								$output = $return;
							}
							if($strictArraySizeOutput) {
								if(!is_array($output)) {
									throw new Exception("'".$f->getFileName().":".$f->getStartLine()." - ".$f->getName()."()' not return array! Type is ".gettype($output));
								}
								if(count($output) < $arraySize) {
									throw new Exception("'".$f->getFileName().":".$f->getStartLine()." - ".$f->getName()."()' trying to manipulate the array output result!");
								}
								$arraySize = count($output);
							}
							$called++;
						}
					}
				}	
			}
		}
		
		Modules::$calledHooks[$hook_name][] = $calleds;

		$this->root->log_called = "";
		if($called!=0 and $log){
			$this->root->message[] = array( 
							"state"		=> $this->root->_MESSAGE_INFO, 
							"message" 	=> "Successfully called hooks: " . $hook_name."(".$called.")",
							"execution_time" => round(microtime(true) - $this->root->time_start, 4)
						);
		}
		$this->global_count_hook_called += $called;
		if($noinfo)
			return  $output;
		else
			return  ["hook" => $hook_name, "output" => $output, "called" => $called];
	}
	
	/*
	This is simple page module manager
	There is all page registered
	*/
	
	function setState($module, $state){
		foreach($this->modules as $mods){
			foreach($mods as $mod){
				if($mod["CName"] == $module){
					$mod["Enable"] = $state;
					
					if($state == 1){ $m = "Enabling";$s = $this->root->_MESSAGE_OK;  }else{ $m="Disabling";$s = $this->root->_MESSAGE_ERROR; }
					
					$this->root->message[] = array( 
							"state"		=> $s, 
							"message" 	=> $m . " module \"" . $module . "\"(" . $mod["Name"] . ")",
							"execution_time" => round(microtime(true) - $this->root->time_start, 4)
						);
				}
			}
		}
	}
	
	function enable($code){ 
		//$this->setState($module, 1);
		$config = $this->root->config;
		$plugins = $config->get_variable("Plugins", "main", "");
		if(in_array($code, $plugins)) {
			return false;
		}

		$plugins[] = $code;
		ksort($plugins);

		$config->set_variable("Plugins", $plugins, false, "main");
		$config->save_variables("main");

		return true;
	}
	function disable($code){ 
		//$this->setState($module, 0);
		$config = $this->root->config;
		$plugins = $config->get_variable("Plugins", "main", "");
		if(!in_array($code, $plugins)) {
			return false;
		}

		$key = array_search($code, $plugins);
		unset($plugins[$key]);

		$config->set_variable("Plugins", $plugins, false, "main");
		$config->save_variables("main");

		return true;
	}

	function getEnabled(){
		return $this->root->config->get_variable("Plugins", "main", "");
	}
}