<?php

class Debuger {

    public function __construct($core, $flash) {
        $this->core = $core;
        $this->flash = $flash;

        $this->debugToken =  Utilities::GUID(true);
        define("_debugToken", $this->debugToken);
    }

    public function getToken() {
        return $this->debugToken;
    }

    public function getFullToken(){
        return date("Ydm")."/".$this->debugToken;
    }

    public function storeDebugPage(){					
		$debug = array(
			"method" => $_SERVER['REQUEST_METHOD'],
			"ip" => Utilities::ip(),
			"time" => time(),
			"httpstatus" => http_response_code(),
			"token" => $this->debugToken,
			"url" => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
			"request" => array(
				"get" => $_GET,
				"post" => $_POST,
				"headers" => function_exists("getallheaders")? getallheaders(): null
			),
			"response" => array(
				"headers" => headers_list()
			),
			"cookies" => $_COOKIE,
			"database" => array("query" => Database::getLogEvents(), "time" => (round(dibi::$totalTime, 4)*100), "count" => dibi::$numOfQueries),
			"hooks" => array("registered" => $this->core->module_manager->getAllHooks(), "called" => Modules::$calledHooks),
			"router" => $this->core->router->getData(),
			"translation" => Strings::$transaltions,
			"security" => User::current()
		);
		if(!file_exists(_ROOT_DIR."/log/debug/".date("Ydm"))){
			mkdir(_ROOT_DIR."/log/debug/".date("Ydm"), 0777, true);
		}

		$soubor = fopen( _ROOT_DIR."/log/debug/".date("Ydm")."/".$this->debugToken.".log", "a");
		fwrite($soubor, json_encode($debug));
		fclose($soubor);
    }
    
    public function draw($token){
		$this->core->page->add_style(Router::url()."include/debug.css");
		$this->core->page->head();

		$debug = json_decode(file_get_contents(_ROOT_DIR."/log/debug/".$token.".log"), true);
		
		echo "<body>";
			//echo "<link rel=\"stylesheet\" href="styles.css">";
			echo "<div class=topbar>";
				echo "<b>SnowFox</b> Profiler";
			echo "</div>";
			echo "<div class='page-info page-info-".$debug["httpstatus"]."'>";
				echo "<a href='".$debug["url"]."'>".$debug["url"]."</a>";
				echo "<div class=page-data>";
					echo "<label>Method</label><span>".$debug["method"]."</span>";
					echo "<label>Http status</label><span>".$debug["httpstatus"]." ".Utilities::getSatusCodeName($debug["httpstatus"])."</span>";
					echo "<label>IP</label><span>".$debug["ip"]."</span>";
					echo "<label>Profiled on</label><span>".Strings::str_time($debug["time"])."</span>";
					echo "<label>Token</label><span>".$debug["token"]."</span>";
				echo "</div>";				
			echo "</div>";
			echo "<div class=main>";
				echo "<div class=main-sidebar>";
					$menu = array(
						array("name" => "Request/Response", "url" => "request"),
						array("name" => "Database", "url" => "database"),
						array("name" => "Events", "url" => "events"),
						array("name" => "Router", "url" => "router"),
						array("name" => "Translation", "url" => "translation"),
						array("name" => "Security", "url" => "security")
					);
					if(!isset($_GET["show"])) { $_GET["show"] = "request"; }
					echo "<ul class=menu>";
						foreach($menu as $k => $item){
							echo "<li class='".($item["url"] == $_GET["show"]?"active":"")."'><a href='".Router::url()."?__type=debug&token=".$token."&show=".$item["url"]."'>".$item["name"]."</a></li>";
						}
					echo "</ul>";
				echo "</div>";
				echo "<div class=content>";
					if($_GET["show"] == "security") {
						echo "<h1>Security</h1>";

						echo "<div class=metric>";
							echo "<div class=value>".$debug["security"]["nick"]."</div>";
							echo "<div class=title>Username</div>";
						echo "</div>";
						if(!$debug["security"]["isGuest"]){
							echo "<div class=metric>";
								echo "<div class=value>".$debug["security"]["email"]."</div>";
								echo "<div class=title>Email</div>";
							echo "</div>";
						}
						echo "<div class=metric>";
							echo "<div class=value>".(!$debug["security"]["isGuest"]?"<i class=\"fas fa-check\"></i>":"<i class=\"fas fa-times\"></i>")."</div>";
							echo "<div class=title>Authenticated</div>";
						echo "</div>";

						echo "<h3>Permissions</h3>";
						if(count($debug["security"]["perm"]) == 0){
							echo "<div class=border-dash>No permissions</div>";
						}else{
							echo "<table>";								
								echo "<tr>";
									echo "<th>Name</th>";
									echo "<th>Granted</th>";									
								echo "</tr>";
								foreach($debug["security"]["perm"] as $key => $value) {									
									echo "<tr>";									
										echo "<td class=key>".$key."</td>";			
										echo "<td>".($value?"<i class=\"fas fa-check\"></i>":"<i class=\"fas fa-times\"></i>")."</td>";
									echo "</tr>";
									if($value[3] == 2) break;
								}
							echo "</table>";
						}
					}
					else if($_GET["show"] == "translation") {
						echo "<h1>Translation</h1>";

						$states = array("full" => 0, "part" => 0, "none" => 0);
						foreach($debug["translation"] as $key => $value) {
							if($value["state"] == 1) $states["full"]++;
							if($value["state"] == 2) $states["part"]++;
							if($value["state"] == 3) $states["none"]++;
						}

						echo "<div class=metric>";
							echo "<div class=value>".$states["full"]."</div>";
							echo "<div class=title>Fully translated</div>";
						echo "</div>";
						echo "<div class=metric>";
							echo "<div class=value>".$states["part"]."</div>";
							echo "<div class=title>Partialy translated</div>";
						echo "</div>";
						echo "<div class=metric>";
							echo "<div class=value>".$states["none"]."</div>";
							echo "<div class=title>Not translated</div>";
						echo "</div>";

						echo "<ul class=menu-tabbed>";
							echo "<li data-for='full-tab-content'>Full translated<span class=count>".$states["full"]."</span></li>";
							echo "<li data-for='part-tab-content'>Partialy translated<span class=count>".$states["part"]."</span></li>";
							echo "<li data-for='no-tab-content'>No translation<span class=count>".$states["none"]."</span></li>";
						echo "</ul>";

						$tran = array(
							array("id" => "full-tab-content", "state" => 1, "k" => "full", "no" => "No full translated texts"),
							array("id" => "part-tab-content", "state" => 2, "k" => "part", "no" => "No partialy translated texts"),
							array("id" => "no-tab-content", "state" => 3, "k" => "none", "no" => "No not translated texts"),
						);

						foreach($tran as $n => $d){
							echo "<div id='".$d["id"]."'>";
								if($states[$d["k"]] == 0){
									echo "<div class=border-dash>".$d["no"]."</div>";
								}else{
									echo "<table>";								
										echo "<tr>";
											echo "<th>Original</th>";
											echo "<th>Translated</th>";
											echo "<th>File</th>";
										echo "</tr>";
										$i = 0;
										foreach($debug["translation"] as $key => $value) {
											if($value["state"] != $d["state"]) continue;

											echo "<tr>";									
												echo "<td>".$value["original"]."</td>";			
												echo "<td>".$value["translated"]."</td>";
												echo "<td>";
													echo "<div class=file>".$value["file"]["file"].":".$value["file"]["line"]." <span class=function>".$value["file"]["function"]."()</span></div>";
												echo "</td>";
											echo "</tr>";
											if($value[3] == 2) break;
										}
									echo "</table>";
								}
							echo "</div>";
						}						
					}
					else if($_GET["show"] == "router") {
						echo "<h1>Router</h1>";

						$routerModule = "";
						$hitAfter = 0;
						foreach($debug["router"]["routes"] as $key => $value) {
							$hitAfter++;
							$routerModule = $value[5];
							if($value[3] == 2) break;
						}

						echo "<div class=metric>";
							echo "<div class=value>".$routerModule."</div>";
							echo "<div class=title>Module</div>";
						echo "</div>";

						echo "<div class=metric>";
							echo "<div class=value>".$hitAfter."</div>";
							echo "<div class=title>Route found after</div>";
						echo "</div>";

						echo "<h3>Route Matching Logs</h3>";

						echo "<div class=dis><b>Path to match</b>: <span>/".$debug["router"]["match"]."</span></div>";

						if(count($debug["router"]["routes"]) == 0){
							echo "<div class=border-dash>No called hooks</div>";
						}else{
							echo "<table>";								
								echo "<tr>";
									echo "<th>#</th>";
									echo "<th>Priority</th>";
									echo "<th>Name</th>";
									echo "<th>Match</th>";
								echo "</tr>";
								$i = 0;
								foreach($debug["router"]["routes"] as $key => $value) {
									echo "<tr class='match-".$value[3]."'>";									
										echo "<td>".(++$i)."</td>";			
										echo "<td>/".htmlentities($value[0])."</td>";
										echo "<td>".htmlentities($value[2])."</td>";
										echo "<td>";
											if($value[3] == 0) { echo "No"; }
											else if($value[3] == 1) { echo "Maybe"; }
											else if($value[3] == 2) { echo "Yes"; }
										echo "</td>";
									echo "</tr>";
									if($value[3] == 2) break;
								}
							echo "</table>";
						}
					}
					else if($_GET["show"] == "events") {
						echo "<h1>Events</h1>";

						echo "<ul class=menu-tabbed>";
							echo "<li data-for='registered-tab-content'>Registered events</li>";
							echo "<li data-for='called-tab-content'>Called events</li>";
						echo "</ul>";

						echo "<div id=registered-tab-content>";
							if(count($debug["hooks"]["registered"]) == 0){
								echo "<div class=border-dash>No registered hooks</div>";
							}else{
								echo "<table>";								
									echo "<tr>";
										echo "<th>Priority</th>";
										echo "<th>Name</th>";
									echo "</tr>";
									$i = 0;
									foreach($debug["hooks"]["registered"] as $key => $value) {
											echo "<tr>";												
												echo "<td colspan=2><b>".$key."</b></td>";
											echo "</tr>";
											foreach($value as $priority => $hook){
												if($priority == "debug_info") continue;	
												if($priority == "debug_info_byfun") continue;
												foreach($hook as $n => $hook_info){											
													foreach($hook_info as $n => $hook_name){
														$fundef = $debug["hooks"]["registered"][$key]["debug_info_byfun"][$hook_name];
														echo "<tr>";												
															echo "<td style='width:100px;text-align:right;'>".$priority."</td>";
															echo "<td><div class=file>".$fundef["file"].":".$fundef["line"]." <span class=function>".$hook_name."()</span></div></td>";
														echo "</tr>";
													}
												}
											}
									}
								echo "</table>";
							}
						echo "</div>";
						echo "<div id=called-tab-content>";
							if(count($debug["hooks"]["called"]) == 0){
								echo "<div class=border-dash>No called hooks</div>";
							}else{
								echo "<table>";								
									echo "<tr>";
										echo "<th>Priority</th>";
										echo "<th>Name</th>";
									echo "</tr>";
									$i = 0;
									foreach($debug["hooks"]["called"] as $key => $value) {
											echo "<tr>";												
												echo "<td colspan=2><b>".$key."</b></td>";
											echo "</tr>";
											foreach($value as $n => $caller){
												echo "<tr>";				
													echo "<td style='width:100px;'></td>";																				
													echo "<td><b class=file>".$caller["caller"]["file"]."</b><span class=counter>".count($caller["calls"])."</span></td>";
												echo "</tr>";
												foreach($caller["calls"] as $n => $c){
													echo "<tr>";				
														echo "<td style='width:100px;'></td>";																				
														echo "<td><b class=file>".$c["file"]."</b> <span class=function>".$c["name"]."()</span></td>";
													echo "</tr>";
												}
											} 
									}
								echo "</table>";
							}
						echo "</div>";
					}
					else if($_GET["show"] == "database") {
						echo "<h1>Database</h1>";

						echo "<div class=metric>";
							echo "<div class=value>".$debug["database"]["count"]."</div>";
							echo "<div class=title>Database Queries</div>";
						echo "</div>";

						echo "<div class=metric>";
							echo "<div class=value>".$debug["database"]["time"]." ms</div>";
							echo "<div class=title>Query time</div>";
						echo "</div>";

						echo "<h3>Queries</h3>";
						if(count($debug["database"]["count"]) == 0){
							echo "<div class=border-dash>No database query</div>";
						}else{
							echo "<table>";								
								echo "<tr>";
									echo "<th>#</th>";
									echo "<th>Time</th>";
									echo "<th>Query</th>";
								echo "</tr>";
								$i = 0;
								foreach($debug["database"]["query"] as $key => $value) {
									$time = round($value["time"]*100, 3);
									echo "<tr><td>".($i++)."</td>";
										echo "<td style='width:100px;'>".$time." ms</td>";
										echo "<td>".$value["sql"]."<div class=file-in>".$value["source"][0].":".$value["source"][1]."</div></td>";
									echo "</tr>";
								}
							echo "</table>";
						}
					}else{
						echo "<h1>Request/Response</h1>";
						echo "<ul class=menu-tabbed>";
							echo "<li data-for='request-tab-content'>Request</li>";
							echo "<li data-for='response-tab-content'>Response</li>";
							echo "<li data-for='cookies-tab-content'>Cookies</li>";
						echo "</ul>";

						echo "<div id=request-tab-content>";
						
							$tables = array(
								array( "name" => "GET Parameters", "data" => $debug["request"]["get"], "no" => "No GET parameters" ),
								array( "name" => "POST Parameters", "data" => $debug["request"]["post"], "no" => "No POST parameters" ),
								array( "name" => "Request Headers", "data" => $debug["request"]["headers"], "no" => "No request Headers" ),
							);

							foreach($tables as $n => $table){
								echo "<h3>".$table["name"]."</h3>";

								if(count($table["data"]) == 0){
									echo "<div class=border-dash>".$table["no"]."</div>";
								}else{
									echo "<table>";								
										echo "<tr>";
											echo "<th>Key</th>";
											echo "<th>Value</th>";
										echo "</tr>";
										foreach($table["data"] as $key => $value) {
											echo "<tr><td class=key>".$key."</td><td>".$value."</td></tr>";
										}
									echo "</table>";
								}
							}							
						
						echo "</div>";
						echo "<div id=response-tab-content>";
							$tables = array(
								array( "name" => "Response Headers", "data" => $debug["response"]["headers"], "no" => "No response Headers", "sep" => ":" ),
							);

							foreach($tables as $n => $table){
								echo "<h3>".$table["name"]."</h3>";

								if(count($table["data"]) == 0){
									echo "<div class=border-dash>".$table["no"]."</div>";
								}else{
									echo "<table>";								
										echo "<tr>";
											echo "<th>Key</th>";
											echo "<th>Value</th>";
										echo "</tr>";
										foreach($table["data"] as $key => $value) {
											if(isset($table["sep"])){
												$dat = explode($table["sep"], $value, 2);
												$key = $dat[0];
												$value = $dat[1];
											}
											echo "<tr><td class=key>".$key."</td><td>".$value."</td></tr>";
										}
									echo "</table>";
								}
							}
						echo "</div>";
						echo "<div id=cookies-tab-content>";
							
							$tables = array(
								array( "name" => "Request Cookies", "data" => $debug["cookies"], "no" => "No request cookies" ),
							);

							foreach($tables as $n => $table){
								echo "<h3>".$table["name"]."</h3>";

								if(count($table["data"]) == 0){
									echo "<div class=border-dash>".$table["no"]."</div>";
								}else{
									echo "<table>";								
										echo "<tr>";
											echo "<th>Key</th>";
											echo "<th>Value</th>";
										echo "</tr>";
										foreach($table["data"] as $key => $value) {
											echo "<tr><td class=key>".$key."</td><td>".$value."</td></tr>";
										}
									echo "</table>";
								}
							}		

						echo "</div>";
					}
				echo "</div>";
			echo "</div>";			
		echo "</body>";
		?>
		<script>
			$(function(){
				$(".menu-tabbed").each(function(){
					var self = $(this);
					var first = $(self.find("li")[0]);
					first.addClass("selected");
					
					self.find("li").each(function(){
						$(this).data("tab", self);						
						var f = $(this).data("for");
						$("#"+f).hide();
						$(this).on("click", function(e){
							var sel = $($(this).data("tab").find("li.selected")[0]);
							sel.removeClass("selected");
							$("#"+sel.data("for")).hide();

							$(this).addClass("selected");
							$("#"+$(this).data("for")).show();

							e.preventDefault();
						});
					});

					$("#"+first.data("for")).show();					
				});
			});
		</script>
		<?php
	}
}