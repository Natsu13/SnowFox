<?php
class Cookies {
	public $root;
	
	public function __construct($root){
		$this->root = $root;
	}
	
	/*
		Example :
		Cookies::set( array("name" => "test", "permision" => "admin"), "+24 hour", false );
		Cookies::set( array("name", "permision"), array("test", "admin"), "+24 hour" );
		Cookies::set( array("name", "permision"), "admin", "+24 hour" );
		Cookies::set( "name", "test", "+24 hour" );
	*/
	public static function set($name, $value = 1, $time = "+1 hour"){		
		if($time == false){
			if($value == 1){ $value = "+1 hour"; } 
			foreach($name as $key => $val){
				Cookies::set($key, $val, $value);
			}
		}elseif(gettype($name) == "array" and gettype($value) == "array"){
			for($i = 0; $i < count($name); $i++){
				Cookies::set($name[$i], $value[$i], $time);
			}
		}else if(gettype($name) == "array" and gettype($value) != "array"){
			for($i = 0; $i < count($name); $i++){
				Cookies::set($name[$i], $value, $time);
			}
		}else{
			if(substr($time, 0, 1)!="-"){
				$_COOKIE[$name] = $value;
			}else{
				unset($_COOKIE[$name]);
			}
			$cookie = setcookie($name, $value, strtotime($time), "/");		
			if(substr($time, 0, 1)!="-"){				
				$hash = sha1($name.$value);
				$time = strtotime($time);
				$url  = Router::url();
				setcookie("SECURITY_".$name, $hash.";;".time().";;".$url, $time, "/");
			}else{				
				setcookie("SECURITY_".$name, "", strtotime("-1 hour"), "/");
				return false;
			}
		}
	}
	
	public static function delete($name){
		if(gettype($name) == "array"){
			for($i = 0; $i < count($name); $i++){
				Cookies::set($name[$i], "", "-1 hour");
			}
		}else {
			return Cookies::set($name, "", "-1 hour");
		}
	}
	
	public static function exists($name){
		if(isset($_COOKIE[$name])){
			return true;
		}else return false;
	}
	
	public static function security_check($name){
		$security = Cookies::security_get($name);
		if($security != false){			
			if(sha1($name.$_COOKIE[$name]) == $security["hash"])
				return true;
			else
				return false;
		}else return false;
	}
	
	public static function security_get($name){
		if(Cookies::exists($name)){
			$data = explode(";;",$_COOKIE["SECURITY_".$name],3);
			return array(
				"hash" 		=> $data[0],
				"create" 	=> $data[1],
				"url"		=> $data[2]
			);
		}else return false;
	}
	
	public static function security_delete(){
		foreach($_COOKIE as $key => $value){
			$dt = explode("_",$key,2);
			if(count($dt)>1){ if($dt[0] == "SECURITY"){ if(!Cookies::exists($dt[1])){ setcookie($key, "", strtotime("-1 hour")); } } }
		}
	}

	public static function create_ifnotExists($name, $value = "", $time = "+1 hour"){
		if(Cookies::exists($name)){
			return true;
		}
		Cookies::set($name, $value, $time);
		return true;
	}
	
	public static function dump($onlyName = false){
		echo "<table style='table-layout: fixed; border-collapse: collapse;width:100%;' border=0 class='snowLeopard'>";
		if($onlyName){
			echo "<tr><th>Key</th></tr>";
			foreach($_COOKIE as $key => $value){
				$dt = explode("_",$key,2);
				if(count($dt)>1){ if($dt[0] == "SECURITY"){ if(!Cookies::exists($dt[1])){ $value="<i>DELETED SECURITY COOKIES</i>"; } } }
				echo "<tr><td style='".($dt[0] == "SECURITY"?"color:red;font-weight:bold;":"")."'>".$key."<br><small>".$value."</small></td></tr>";
			}
		}else{
			echo "<tr><th width=300>Key</th><th width=600>Value</th></tr>";
			foreach($_COOKIE as $key => $value){
				$dt = explode("_",$key,2);
				if(count($dt)>1){ if($dt[0] == "SECURITY"){ if(!Cookies::exists($dt[1])){ $value="<i>DELETED SECURITY COOKIES</i>"; } } }
				echo "<tr><td style='".($dt[0] == "SECURITY"?"color:red;font-weight:bold;":"")."'>".$key."</td><td>".$value."</td></tr>";
			}
		}
		if(count($_COOKIE) == 0)
			echo "<tr><td colspan=2>Žádné cookies...</td></tr>";
		echo "</table>";
	}
}