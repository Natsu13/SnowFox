<?php
class Router {
	private $root;
	private $router = null;
	public  $_get = array();
	public  $_url = null;
	public  $_data = null;
	private $_hash = null;
	public  $url = "http://localhost/";
	
	public function __construct($root){
		$this->root = $root;

		$root->getContainer()->set('router', $this);
	}
	
	public function add($router_condition, $url_parameters, $redirect = false){
		$buffer = $router_condition;		
		$buffer = preg_replace("/\[(.*?)\]/", "($1)?", $buffer);
		$buffer = preg_replace("/\<(.*?)\>/", "(.*?)", $buffer);
		$buffer = str_replace("/", "\\/", $buffer);
		$this->router[] = array($router_condition, $buffer, $url_parameters, null, null, null, $redirect);
	}

	public function preload(){
		$user = User::current();
		$perm = User::permission($user["permission"])["level"];
	
		$result = dibi::query('SELECT * FROM :prefix:redirecting WHERE active = 1 AND '.$perm.' >= minop ORDER BY id DESC');
		foreach ($result as $n => $row) {			
			$this->add($row["_from"], $row["_to"], $row["redirect"] == 1);
		}
	}
	
	public static function url($full = false, $_request = false){
		if(!isset($_GET["url"])){ $url = ""; }else{ $url = $_GET["url"]; }
		if(substr($url, -1) == "/"){ $url = substr($url, 0, -1); }
		if(substr($_SERVER["REQUEST_URI"], -1) != "/"){ $_SERVER["REQUEST_URI"].="/"; }
		if(isset($_SERVER["REQUEST_SCHEME"])){$http=$_SERVER["REQUEST_SCHEME"];}else{$http="http";}
		$request_ = explode("?", $_SERVER["REQUEST_URI"]);
		$request = $request_[0];
		if($full){
			if($_request and isset($request_[1]))
				$ret = $http . "://" . $_SERVER["SERVER_NAME"] . $request."?".$request_[1];
			else
				$ret = $http . "://" . $_SERVER["SERVER_NAME"] . $request;
		}else{
			$ret = $http . "://" . $_SERVER["SERVER_NAME"] . str_replace("/" . $url , "/", $request);
		}
		if(substr($ret, -2) == "//") return substr($ret, 0, -1);
		return $ret;
	}

	public static function urlAddParam($url, $name, $value = "") {
		$ure = explode("?", $url);
		$param = $name;
		if($value != NULL && $value != "") {
			$param.="=".$value;
		}
		if(!isset($ure[1])) {
			return $url."?".$param;
		}
		return $url."&".$param;
	}
	
	public static function urladd($param){
		$url = Router::url(true);
		$ure = explode("?", $url);
		if(!isset($ure[1])) $ure[1] = "";
		$get = explode("&", $ure[1]);
		$found = false;
		$none = true;
		$urlparams = array();
		if(isset($get[0]) and $get[0] != ""){
			foreach($get as $key => $value){
				$none = false;
				$pata = explode("=", $value);
				$urlparams[$pata[0]] = $pata[1];
			}
		}
		
		foreach($param as $key => $value){
			$urlparams[$key] = $value;
		}
		$first = true;
		$url = $ure[1]."?";
		foreach($urlparams as $key => $value){
			if($first){ $first = false; }else{ $url.="&"; }
			$url.=$key;
			if($value != null){ $url.="=".$value; }
		}
		
		return $url;
	}
	
	public function start(){
		if(!isset($_GET["url"])){ $url = ""; }else{ $url = $_GET["url"]; }
		$url_ = explode("?", $url);$url = $url_[0];
		if(substr($url, -1) == "/"){ $url = substr($url, 0, -1); }
		$d = explode("?",$_SERVER["REQUEST_URI"],2); $_SERVER["REQUEST_URI_NEW"] = $d[0];
		if(substr($_SERVER["REQUEST_URI_NEW"], -1) != "/"){ $_SERVER["REQUEST_URI_NEW"].="/"; }		
		
		$this->_url = $url;
		$this->_get = $url;
		$this->url_ = str_replace("/" . $url . "/" , "/", $_SERVER["REQUEST_URI_NEW"]);
		if(isset($_SERVER["REQUEST_SCHEME"])){$http=$_SERVER["REQUEST_SCHEME"];}else{$http="http";}
		$this->url = $http . "://" . $_SERVER["SERVER_NAME"] . str_replace("/" . $url . "/" , "/", $_SERVER["REQUEST_URI_NEW"]);
		
		//Standart get
		$get = explode("?", $_SERVER["REQUEST_URI"], 2);
		if(count($get)>1){
			$get = explode("&", $get[1]);
			for($i = 0;$i < count($get); $i++){
				$ma = explode("=", $get[$i]);
				if(count($ma) == 1){
					$_GET[$ma[0]] = "";
				}else{
					$_GET[$ma[0]] = urldecode($ma[1]);
				}
			}
		}

		foreach($this->router as $key => $routa){	
			if($routa[1] == $url or (preg_match("/^".$routa[1]."$/U", $url) and $routa[1] != "")){
				$this->router[$key][3] = 1;
				$this->router[$key][4] = NULL;
				$variables = null;
				$unuseable = null;
				preg_match_all("/(\<(.*?)\>|\[(.*?)\])/", $routa[0], $names);
				preg_match_all("/^".$routa[1]."$/", $url, $values);
				for($i=0;$i<count($names[2]);$i++){
					if($names[2][$i] == ""){
						preg_match_all("/\<(.*?)\>/", $names[3][$i], $parser);
						if($parser[1]!=null){
							$variables[] = "";
							$unuseable[] = true;
							foreach($parser[1] as $additional){
								$variables[] = $additional;
								$unuseable[] = false;
							}
						}else{
							$variables[] = $names[3][$i];
							$unuseable[] = true;
						}
					}else{
						$variables[] = $names[2][$i];
						$unuseable[] = false;
					}
				}
				for($i=0;$i<count($variables);$i++){
					if(!$unuseable[$i]){
						$nam = explode("=", $variables[$i]);
						if(count($nam) == 1) {$default = null; }else{ $default = $nam[1]; }
						$variables[$i] = $nam[0];
						if(!isset($values[($i+1)][0])){ $values[($i+1)][0] = $default; }else if($values[($i+1)][0] == ""){ $values[($i+1)][0] = $default; }
						$this->router[$key][4][$variables[$i]] = $values[($i+1)][0];
						$this->router[$key][2] = str_replace("<" . $variables[$i] . ">", $values[($i+1)][0], $this->router[$key][2]);						
						$this->_url = $this->router[$key][2];
					}
				}									
			}else{
				$this->router[$key][3] = 0;
			}
			$data = explode("&", $this->router[$key][2]);
			foreach($data as $aq){
					$mas = explode("=",$aq);
					if($mas[0] == "module"){
						if(!isset($mas[1])){ $mas[1]=$this->router[$key][4][$mas[0]]; }else if($mas[1]=="" or $mas[1]==null){ $mas[1]=$this->router[$key][4][$mas[0]]; }
						$this->router[$key][5] = $mas[1];
					}
			}
		}			

		for($i=count($this->router)-1;$i>=0;$i--){
			$routa = $this->router[$i];
			if($routa[3] == 1){
				if(substr_count($routa[0], "/") >= substr_count($url, "/")){
					$this->router[$i][3]=2;
					if($this->router[$i][6] == 1){
						echo "Redirecting... to <a href='".$this->router[$i][2]."'>".$this->router[$i][2]."</a>";
						header("location:" . $this->router[$i][2]);
						exit();
					}
					$data = explode("&", $routa[2]);
					foreach($data as $aq){
						$from_url = false;
						$mas = explode("=",$aq);
						if(isset($routa[4][$mas[0]])){ $mas[1] = $routa[4][$mas[0]];$from_url=true; }
						if(!isset($mas[1])){$mas[1] = "";}
						$_GET[$mas[0]] = $mas[1];
						$this->_data[$mas[0]] = array($mas[1], $from_url);
					}				
					$i=-1;
				}
			}
		}	
	}
	
	public function get($name){
		return $this->_data[$name][0];
	}
	
	public function getData(){
		return array(
			"match" => $this->_get,
			"routes" => $this->router
		);
	}

	public function draw_table(){
		echo "<div style='padding:7px;'>Matching url: ".$this->_get."</div>";
		echo "<table style='table-layout: fixed; border-collapse: collapse;width: 100%;' border=0 class='snowLeopard'>";
		echo "<tr><th width=60>Match?</th><th>Mask</th><th width=150>Module</th><th>Request</th></tr>";
		foreach($this->router as $key => $routa){
			if($routa[3] == 0){$m = "<font color=white>No</font>";}
			else if($routa[3] == 1){$m = "<font style='color:#406b8c'>Maybe</font>";}
			else {$m = "<font style='color: #33a76c;font-weight: bold;'>Yes</font>";}
			echo "<tr><td valign=top>".$m."</td><td valign=top><span onclick=\"$('#routa_".$key."_2').show();$(this).hide();\" id='routa_".$key."_1'>".($routa[0] == "" ? "<span class=desc>[empty]</span>" : htmlspecialchars($routa[0]))."</span><span style='display:none;' onclick=\"$('#routa_".$key."_1').show();$(this).hide();\" id='routa_".$key."_2'>".htmlspecialchars($routa[1])."</span></td><td valign=top>".$routa[5]."</td><td>";
			if($routa[3] == 2){
				if($routa[4] == NULL){
					echo "<i>Bez parametrů</i>";
				}else{
					foreach($routa[4] as $key => $aq){
						echo "<b>" . $key . "</b> = ";
						if($aq == NULL)
							echo "<font color=black>NULL</font><br>";
						else
							echo "<font color=green>".$aq."</font><br>";
					}
				}
			}
			echo "</td></tr>";
		}
		echo "</table>";
	}
}