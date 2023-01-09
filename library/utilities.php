<?php
class Utilities {
	public $root;

	public function __construct($root){
		$this->root = $root;
		include(_ROOT_DIR . '/include/phpqrcode/qrlib.php'); 

		$root->getContainer()->set('utilities', $this);
	}

	public static function moveFilesToDirectory($sourceDir, $destinationDir, $doBackupRestore = true, $recursive = true) {
		$ignore = array(".", "..");
		$originalFiles = scandir($sourceDir);
	
		if(!is_dir($destinationDir)){
			mkdir($destinationDir, 0777, true);
		}
	
		$backup = [];
		foreach($originalFiles as $originalFile){
			if(!in_array($originalFile,$ignore)){								
				$backup[] = array($destinationDir.DIRECTORY_SEPARATOR.$originalFile, $sourceDir.DIRECTORY_SEPARATOR.$originalFile);
				if($recursive && is_dir($destinationDir.DIRECTORY_SEPARATOR.$originalFile)) {
					if(!Utilities::moveFilesToDirectory($sourceDir.DIRECTORY_SEPARATOR.$originalFile, $destinationDir.DIRECTORY_SEPARATOR.$originalFile)) {
						$doBackup = true;
					}
				}else{
					if(!rename($sourceDir.DIRECTORY_SEPARATOR.$originalFile, $destinationDir.DIRECTORY_SEPARATOR.$originalFile)){
						$doBackup = true;
					}
				}

				if($doBackup) {
					if($doBackupRestore) {
						foreach($backup as $back) {
							rename($back[0], $back[1]);
						}
					}
					unlink($destinationDir);
					return false;
				}
			}
		}

		return true;
	}

	public static function SendForm($formId, $customFormHook = false, $dontAddErrorInFields = false) {
		$output = array(
			"submited" => false, 
			"success" => "",
			"disableForm" => false,
			"isMultimedia" => false,
			"thisFormWasAleradyFilled" => 0,
			"inputErrors" => [],
			"errors" => [], 
			"warnings" => []
		);
		$result = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $formId)->fetch();

		if($result != null) {
			$datf = Config::sload($result["data"]);
			$settings = Config::sload($result["settings"]);
			if(!isset($settings["version"]) || floatVal($settings["version"]) < 2) {
				$settings = $datf;
			}

			$errorshowed = false;
			if(!isset($settings["onetime"])){ 
				$settings["onetime"] = 0; 
			}
			if($settings["onetime"] == 1){
				if(User::current() == false){
					$output["disableForm"] = true;
					$output["thisFormWasAleradyFilled"] = 2;
					$errorshowed = true;
					$submit = "";
				}else{
					$re = dibi::query('SELECT * FROM :prefix:form_answer WHERE `parent`=%s', $result['id'], " AND user=%i", User::current()["id"]);
					if($re->count() > 0){
						$output["disableForm"] = true;
						$output["thisFormWasAleradyFilled"] = 1;
						$errorshowed = true;
						$submit = "";
					}
				}
			}

			if(isset($_POST["form_id"]) && $_POST["form_id"] == $result['id']){
				$output["submited"] = true;
				if($settings["enable"] != 1 && !$customFormHook){
					$output["warnings"][] = "<b>".t("The form has been disabled!")."</b>";
				}else{
					$input = null;
					$submit = "";
					$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $result["id"], " ORDER BY position");
					$i=0;
					
					$fvars = Array();
					foreach ($result_ as $n => $row) {
						$data = Config::sload($row["data"]);
						if($row["type"] == "variable"){
							$v = $row["value"];
							if($data["list"] == ""){
								$v = $row["value"];
							}else{
								$index = $row["value"];
								$arra  = explode(",", $data["list"]);
								foreach($arra as $key => $value){
									if($index == trim($value)) $v = trim($value);
									if($index == $key) $v = trim($value);
								}
							}
							$fvars[$row["name"]] = $v;
						}else if($row["type"] == "submit"){
							if(isset($_POST["form_input_".$row["id"]])){
								$submit = $row["name"];
							}
						}else{
							$fvars[$row["name"]] = $row["value"];
						}
					}
					
					foreach ($result_ as $n => $row) {
						if($row["type"] == "text"){
							// text
						}else{
							$data = Config::sload($row["data"]);
							if(!isset($data["state"])) $data["state"] = 0;
							if(!isset($data["custom"])) $data["custom"] = 0;
							if(!isset($data["asemail"])) $data["asemail"] = 0;
							
							$input[$i] = array(
								array(
									"form_input_".$row["id"],
									$row["id"], 
									$row["name"]
								), 
								$row["type"], 
								$data["state"]
							);

							if($row["type"] == "textbox" || $row["type"] == "password" || $row["type"] == "textarea"){
								if($data["state"] == "2"){
									$_POST["form_input_".$row["id"]] = preg_replace_callback('/\{#(.*?)\}/U',function ($matches) use($fvars) {
										return (isset($fvars[$matches[1]])?$fvars[$matches[1]]:"null");
									}, $row["value"]);
								}
								if($data["asemail"] == 1 && isset($_POST["form_input_".$row["id"]])){
									if(!Utilities::isEmail($_POST["form_input_".$row["id"]]))
										$errors_input[$row["id"]] = 2;
								}
								if(isset($_POST["form_input_".$row["id"]])){
									if($row["type"] == "password" && $customFormHook)
										$input[$i][3] = strlen($_POST["form_input_".$row["id"]]);
									else
										$input[$i][3] = $_POST["form_input_".$row["id"]];
								}
							}else if($row["type"] == "slider"){
								//$errors_input[$row["id"]] = 0;
								$input[$i][3] = $_POST["form_input_".$row["id"]];
							}else if($row["type"] == "upload"){
								if(!isset($_FILES["form_input_".$row["id"]])){
									//$errors_input[$row["id"]] = t("you must upload file");
								}
								else if(number_format($_FILES["form_input_".$row["id"]]["size"] / $data["maxsize"], 2) > 1){
									$errors_input[$row["id"]] = t("File can have only")." ".Utilities::convertBtoMB($data["maxsize"])." MB";
								}
								else if($_FILES["form_input_".$row["id"]]["error"] == 0){						
									$allowed = (preg_match_all('/[^, ]+/', $data["allowed"], $out)? $out[0]: array('gif','png','jpg'));
									$filename = $_FILES["form_input_".$row["id"]]['name'];
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									if(!in_array($ext,$allowed)) {
										$errors_input[$row["id"]] = t("File type can be only one of this").": ".implode(", ", $allowed);
									}else{
										$newnam = sha1_file($_FILES["form_input_".$row["id"]]['tmp_name']).time();
										$filene = _ROOT_DIR.'/upload/'.$data["folder"].$newnam.'.'.$ext;
										
										if(!move_uploaded_file($_FILES["form_input_".$row["id"]]['tmp_name'], $filene)) {
											$errors_input[$row["id"]] = t("Upload ERROR");
										}else{
											/* TODO: udělat nastavovatelné */
											$images =  array('gif','png' ,'jpg');
											if(in_array($ext,$images) && $data["resize"] == 1)
												Utilities::resizeImage($filene, $data["resizew"], $data["resizeh"]);
												
											$input[$i][3] = Router::url()."upload/".$data["folder"].$newnam.'.'.$ext;
											$_POST["form_input_".$row["id"]] = Router::url()."upload/".$data["folder"].$newnam.'.'.$ext;
										}
									}
								}
								else{
									switch ($_FILES["form_input_".$row["id"]]['error']) {
										case UPLOAD_ERR_OK:
											break;
										case UPLOAD_ERR_NO_FILE:
											//$errors_input[$row["id"]] = t('No file sent');
											if($data["state"] == "1")
												$errors_input[$row["id"]] = t("You must upload file");
											break;
										case UPLOAD_ERR_INI_SIZE:
										case UPLOAD_ERR_FORM_SIZE:
											$errors_input[$row["id"]] = t('Exceeded filesize limit on server');
											break;
										default:
											$errors_input[$row["id"]] = t('Unknown errors')." - ".$_FILES["form_input_".$row["id"]]['error'];
											break;
									}
								}
								if(isset($errors_input[$row["id"]])){
									$_POST["form_input_".$row["id"]] = "!!no";
								}
							}else if($row["type"] == "picker"){
								$input[$i][3] = "";
								if(isset($_POST["form_input_".$row["id"]])){
									if($data["state"] == "1" && $_POST["form_input_".$row["id"]] == ""){ $errors_input[$row["id"]] = 1; }
									else if($data["state"] == "2"){ $input[$i][3] = ""; }
									else{
										$input[$i][3] = "";
										$mydat = explode("[;", $_POST["form_input_".$row["id"]]);							
										$items = explode("[;", $data["items"]);
										for($l=0;$l<count($items);$l++){
											$dtvl = explode("[,", $items[$l]);
											if($l == $mydat[0]){
												
												$selected__ = array();
												$selected__[$l] = 0;
												$result__ = dibi::query('SELECT * FROM :prefix:form_answer WHERE parent = %i', $result["id"], " ORDER BY id DESC");
												foreach ($result__ as $_n => $_row) {
													$_data = Config::sload($_row["data"]);
													for($gh=0;$gh<count($_data);$gh++){
														$dtq = $_data[$gh];
														if($dtq[3] == $row["id"]){
															$mdq = explode("[;", $dtq[2]);
															if(!isset($selected__[$mdq[0]])){ $selected__[$mdq[0]] = 1; }
															else{ $selected__[$mdq[0]]+=1; }
														}
													}
												}									
												$perm = User::permission($dtvl[2]);
												if(User::permission(User::current()["permission"])["level"] >= $perm["level"]){
													if($dtvl[3] == 0 or ($dtvl[3] != 0 and $dtvl[3] - $selected__[$l] > 0)){
														$input[$i][3] = $l."[;".$items[$l];
													}else{
														$errors_input[$row["id"]] = t("Capacity has already been reached");
													}
												}else
													$errors_input[$row["id"]] = t("You can not select this, because you need permision same or above")." ".$perm["name"];
											}	
										}
									}
								}else{
									if($data["state"] == "2"){ $errors_input[$row["id"]] = 1; }
								}
							}else if($row["type"] == "variable" && $submit != ""){
								$act = 0;
								$old = $row["value"];
								$stp = false;
								$arr = explode(",", $data["list"]);
								
								if($data["next"] == 1){
									$row["value"]+=1;
								}else if($data["next"] == 2){
									$row["value"]-=1;
								}else if($data["next"] == 3){
									if(isset($arr[$row["value"]+1])) 
										$row["value"]+=1;
									else{ 
										$row["value"] = 0; 
										$act = 1; 
									}
								}
								
								if($data["stop"] == 1){
									if($act == 1){ 
										$row["value"] = $old;
										$stp = true; 
									}
								}else if($data["stop"] == 2){
									if($row["value"] >= $data["stopat"]){
										$row["value"] = $old;
										$stp = true;
									}
								}
								
								if($stp && $data["closeatstop"] == 1){
									$q = dibi::query('UPDATE :prefix:form SET ', array("enable" => 0), 'WHERE `id`=%s', $result["id"]);
								}
								$q = dibi::query('UPDATE :prefix:form_items SET ', array("value" => $row["value"]), 'WHERE `id`=%s', $row["id"]);		
								$input[$i][3] = $row["value"];
							}else if($row["type"] == "recaptcha"){
								if(isset($_POST["g-recaptcha-response"])){
									$data = array(
										'secret' => Config::getS("recaptcha-secret-key"),
										'response' => $_POST["g-recaptcha-response"]
									);
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
									curl_setopt($ch, CURLOPT_POST, true);
									curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									$server_output = curl_exec ($ch);
									curl_close ($ch);
									$check = json_decode($server_output);
									if($check->success) {
										//okay
									}else{
										$errors_input[$row["id"]] = t("Recaptcha validation failed!");
									}
								}elseif($submit != ""){
									$errors_input[$row["id"]] = 1;
								}
							}elseif($row["type"] == "select"){
								if($data["types"] == "1" or $data["types"] == "4"){
									$input[$i][3] = "";
									if(isset($_POST["form_input_".$row["id"]]))
										$input[$i][3] = $_POST["form_input_".$row["id"]];
									if($input[$i][3] == "custom" and $_POST["form_input_".$row["id"]."_custom_val"]!="" && $data["custom"] == 1){
										$input[$i][3] = $_POST["form_input_".$row["id"]."_custom_val"];
									}
								}elseif($data["types"] == "2"){
									$values = "";
									if(isset($_POST["form_input_".$row["id"]])){
										foreach ($_POST["form_input_".$row["id"]] as $selectedOption){
											if($values != "") $values.="[;";
											$values.=$selectedOption;
										}
									}
									$input[$i][3] = $values;
								}else{
									$values = "";
									$items = explode("[;", $data["items"]);
									for($l=0;$l<count($items);$l++){
										if(isset($_POST["form_input_".$row["id"]."_".$l])){
											if($values != "") $values.="[;";
											$values.=$_POST["form_input_".$row["id"]."_".$l];
										}
									}
									if(isset($_POST["form_input_".$row["id"]."_custom"]) and $_POST["form_input_".$row["id"]."_custom_val"]!="" and $data["custom"] == 1){
										if($values != "") $values.="[;";
										$values.=$_POST["form_input_".$row["id"]."_custom_val"];
									}
									$input[$i][3] = $values;
								}
							}
							$i++;
						}
					}
					
					if($submit != ""){			
						for($a=0;$a<$i;$a++){
							if($input[$a][2] == 1){
								if($input[$a][3] != ""){ 
									//idk
								}
								else { 
									if(!isset($errors_input[$input[$a][0][1]])){ 
										$errors_input[$input[$a][0][1]] = 1; 
									} 
								}  
							}
						}
					}
					
					$show = true;

					if(!$errorshowed){
						if($errors_input != null){
							if(!$customFormHook)
								$output["errors"][] = t("Please fill all required fields!");
						}elseif($submit != ""){
							$pole = null;
							for($a=0;$a<$i;$a++){
								if(!isset($input[$a][3])) 
									$input[$a][3] = 0;
								$pole[$a] = array($input[$a][0][2], $input[$a][1], $input[$a][3], $input[$a][0][1]);
							}
							
							$data = array(
									"parent" 	=> $result["id"],
									"user"		=> (User::current() != false?(User::current()["id"]):"-1"),
									"time"		=> time(),
									"ip"		=> Utilities::ip(),
									"data"		=> Config::ssave($pole),
									"browser"	=> $_SERVER['HTTP_USER_AGENT'],
									"submit"	=> $submit
								);
							$result = dibi::query('INSERT INTO :prefix:form_answer', $data);

							if(!$customFormHook){
								$show = false;
								if($settings["redirect"] != "")
									header("location:".$settings["redirect"]);
								else{
									if($settings["succme"] != ""){
										$settings["succme"] = preg_replace_callback('/\{#(.*?)\}/U',function ($matches) use($fvars) {
											return (isset($fvars[$matches[1]])?$fvars[$matches[1]]:"null");
										}, $settings["succme"]);
										$output["success"] = str_replace("\r\n", "<br>", $settings["succme"]);
									}else{
										$output["success"] = t("The form was sent");
									}
								}
							}
						}
					}

					if($show){
						// Variables load
						$fvars = Array();
						$ftype = Array();
						foreach ($result_ as $n => $row) {
							$data = Config::sload($row["data"]);
							if($row["type"] == "variable"){
								$v = $row["value"];
								if($data["list"] == ""){
									$v = $row["value"];
								}else{
									$index = $row["value"];
									$arra  = explode(",", $data["list"]);
									foreach($arra as $key => $value){
										if($index == trim($value)) $v = trim($value);
										if($index == $key) $v = trim($value);
									}
								}
								$fvars[$row["name"]] = $v;
								$fvars["id_".$row["name"]] = $v;
								$ftype[$row["id"]] = "variable";
							}else{
								$fvars[$row["name"]] = $row["value"];
								$fvars["id_".$row["name"]] = $row["value"];
								$ftype[$row["id"]] = $row["type"];
								if($row["type"] == "upload"){
									$output["isMultimedia"] = true;
								}
							}
						}

						$disabledall = false;			
						if($settings["onetime"] == 1){
							if(User::current() == false){
								$disabledall = true;
								$output["errors"][] = t("Please log in to fill out the form");
							}else{
								$re = dibi::query('SELECT * FROM :prefix:form_answer WHERE `parent`=%s', $result['id'], " AND user=%s", User::current()["id"]);
								if($re->count() > 0){
									$disabledall = true;
									$output["errors"][] = t("This form can only be filled in once!");
								}
							}
						}

						foreach ($result_ as $n => $row) {
							if(isset($errors_input[$row["id"]])){
								if($errors_input[$row["id"]] == 2) {
									$_GET["error_form_" + $result["id"]+"_"+$row["id"]] = t("Please enter a valid email!");
									if(!$dontAddErrorInFields) {
										$output["errors"][] = "<b>".$row["name"]."</b>: ".t("Please enter a valid email!");	
									}
								}
								elseif($errors_input[$row["id"]] == 1 or $errors_input[$row["id"]] === true) {
									$_GET["error_form_" + $result["id"]+"_"+$row["id"]] = t("This field is required!");

									if(!$dontAddErrorInFields) {
										$output["errors"][] = "<b>".$row["name"]."</b>: ".t("This field is required!");
									}							
								}
								else {
									$_GET["error_form_" + $result["id"]+"_"+$row["id"]] = $errors_input[$row["id"]];
									if(!$dontAddErrorInFields) {
										$output["errors"][] = "<b>".$row["name"]."</b>: ".$errors_input[$row["id"]];
									}
								}							
							}
						}
					}

				}		
				$output["inputErrors"] = $errors_input;
			}
		}
	
		return $output;
	}

	public static function GetBBCode($root, $text) {
		$r = $text;
		$plugin = $root->module_manager->hook_call("page.bbcode", null, $r);
		return $plugin["output"];
	}

	public static function StoreFile($filename, &$parent){
        if(empty($filename)) return;
    
        $matches = array();
    
        if(preg_match('|^([^/]+)/(.*)$|', $filename, $matches)){
    
            $nextdir = $matches[1];
    
            if(!isset($parent['children'][$nextdir])){
                $parent['children'][$nextdir] = array('name' => $nextdir,
                    'children' => array(),
                    'href' => $parent['href'] . '/' . $nextdir);
            }
    
            self::StoreFile($matches[2], $parent['children'][$nextdir]);
        } else {
            $parent['children'][$filename] = array('name' => $filename,
                'size' => '...', 
                'href' => $parent['href'] . '/' . $filename);
        }
    }

	public static function DrawFiles($files = null, $filesSelected = null, $parent = null, $showInputType = 0){    
		if($files == null) return;
	
		echo "<div class=\"".($parent == null?"ctree":"subclass")."\" id='subcat".($parent==null?"":"_".$parent)."'><ul>";
		foreach($files["children"] as $n => $file){
			$selected = isset($filesSelected[$file["href"]]);
			$id = Strings::undiacritic($file["href"]);
			echo "<li id='catli_".$id."' class='".($file["children"] == null || count($file["children"]) == 0?"empty":"")." ".($selected?"open":"")."'>";
				echo "<div onclick=\"toggleOpen('#catli_".$id."');return false;\">";
					echo "<div class=controll>";                        
						echo "<span class=cat_name>";
						if($showInputType == 1) {
							echo "<input type=radio ".Utilities::check($selected)." name=maincat value='".$file["href"]."' onclick=\"event.stopPropagation();\">";
						}
						else if($showInputType == 2) {
							echo "<input type=checkbox ".Utilities::check($selected)." name=maincat value='".$file["href"]."' onclick=\"event.stopPropagation();\">";
						}
						echo " ".$file["name"]."</span>";
					echo "</div>";
				echo "</div>";
				if(count($file["children"]) > 0){
					self::DrawFiles($file, $filesSelected, $id, $showInputType);
				}
			echo "</li>";
		}
		echo "</ul></div>";
	}

	public static function Table($id, $columns, $tabs, $search){
		$html = "";

		$html.= "<div class=tabler id='table-".$id."'>";	
			$html.= "<div class=tabler-head>";		
				$html.= "<div class=left>";
					$html.= "<ul class='tabs'>";
						foreach($tabs as $key => $tab) {
							$html.= "<li class='di ".($tab["activated"]?"selected":"")."' data-filter='".$tab["filter"]."'>".$tab["text"]."</li>";
						}
					$html.= "</ul>";
				$html.= "</div>";
				$html.= "<div class='right'>";
					$html.= "<input type=text name=search class=search placeholder='".t("Search")."' value='".$search."'>";
				$html.= "</div>";
			$html.= "</div>";
		$html.= "<div class='responsive-table'>";
			$html.= "<div class='head'>";
				foreach($columns as $key => $column){
					$html.= "<div class='col' data-order='".$column["order"]."'>";
						$html.= $column["name"];
						if($column["name"] != ""){
							$html.= "<span class=order><i class=\"fa fa-sort\" aria-hidden=\"true\"></i></span>";
						}
					$html.= "</div>";
				}
			$html.= "</div>";
			$html.= "<div class='table-body loading'>";
				$html.= "Loading...";
			$html.= "</div>";
			$html.= "<div class='table-body table-load-next'>";
				$html.= "<a href='#'>".t("Load more")."</a>";
			$html.= "</div>";
		$html.= "</table>";
		$html.= "</div>";

		return $html;
	}

	public static function GUID($first = false) {
		$guid = "";
		if (function_exists('com_create_guid') === true)
		{
			$guid = trim(com_create_guid(), '{}');
		}

		$guid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

		if($first){
			$ar = explode("-", $guid);
			return $ar[0];
		}
		return $guid;
	}

	public static function getCountry(){
		return array(
			"1" => array("name" => "Czechia"), 
			"2" => array("name" => "Slovakia")
		);
	}

	public static function ifAll($enum, $cond){
		foreach($enum as $n => $item) {
			if(!$cond($item))
				return false;
		}
		return true;
	}

	public static function QRCode($text, $size = 2){
		$hash = sha1($text.$size);
		$filename = _ROOT_DIR."/temp/qrcode-".$hash.".png";
		$errorCorrectionLevel = 'L'; //'L','M','Q','H'
		$matrixPointSize = $size;
		QRcode::png($text, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
		return Router::url()."temp/qrcode-".$hash.".png";
	}

	/** @Deprecated */
	/*
	public static function DropZoneUpload($url, $name, $image, $classes = "", $callbackjs = ""){
		$result = "";
		$result.= "<div class=upload-view id='".$name."-img-view' style='display:".($image == ""?"none":"block").";'>";
		$result.= "<div class=image data-url=\"".Router::url()."\" id='".$name."-img-view-url' style='background-image:url(".Utilities::decideUrl($image).")'></div>";
		$result.= "<div class=text><span>".$image."</span><a href=# onclick=\"dropZoneRemoveUpload('".$name."');\" class=delete>".t("delete image")."</a></div>";
		$result.= "</div>";
		$result.= "<input type=hidden name='".$name."' id='".$name."-input' value='".$image."'>";
		$result.= "<div class='dropzone ".$classes."' id='".$name."-upload' style='display:".($image == ""?"block":"none").";'></div>";
		$result.= "<script>";
		$result.= "$(function(){ $(\"#".$name."-upload\").dropzone({ url: \"".$url."\", success: function(e,r){ dropZoneFinishUpload(e, r, '".$name."');";
		if($callbackjs != ""){
			$result.= $callbackjs."(e, r, '".$name."')";
		}
		$result.= "} }); });";
        $result.= "</script>";
		return $result;
	}
	*/

	public static function isErrorPage(){
		if(file_exists(_ROOT_DIR."/templates/".Database::getConfig("style")."/error404.php")){
			include_once(_ROOT_DIR."/templates/".Database::getConfig("style")."/error404.php");
			return true;
		}
		return false;
	}

	/** @Deprecated */
	/*
	public static function DropZoneUploadPost($name = "file", $allowed = null){
		if($allowed == null){
			$allowed =  array('gif','png' ,'jpg');
		}
		
		$status = array("error" => "");
		if($_FILES[$name]["error"] == 0){
			$filename = $_FILES[$name]['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if($filename == ""){
				$status["error"] = t('No file sent');
			}
			elseif(!in_array($ext, $allowed) ) {
				$status["error"] = t("The file can only be"). " " . implode(", ", $allowed);
			}else{
				$newnam = sha1($filename.time());
				$filene = _ROOT_DIR.'/upload/images/'.$newnam.'.'.$ext;
				if(!move_uploaded_file($_FILES[$name]['tmp_name'], $filene)) {
					$status["error"] = t("Upload ERROR");
				}else{
					$status["url"] = '/upload/images/'.$newnam.'.'.$ext;
				}
			}
		}else{
			switch ($_FILES[$name]['error']) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					$status["error"] = t('No file sent');
					break;
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$status["error"] = t('Exceeded filesize limit');
					break;
				default:
					$status["error"] = t('Unknown errors');
			}
		}

		return $status;
	}
	*/

	public static function processUploadFile($file, $dir = "", $newname = null, $allowedext = null){
        $error = [];
        $newfilename = [];       

		for($i = 0; $i < count($file["name"]); $i++) {
			if($file["error"][$i] == 0){
				$filename = $file["name"][$i];
				$ext = pathinfo($filename, PATHINFO_EXTENSION);

				if($filename == ""){
					$error[] = t('No file sent.');
				}elseif($allowedext != null && !in_array($ext, $allowedext)) {
					$error[] = t("The file can only be"). " " . implode(", ", $allowedext).".";
				}else{
					if($newname == null) 
						$newnam = sha1($filename.time());
					else if(count($file["name"]) == 1) 
						$newnam = $newname;
					else
						$newnam = $newname."_".$i;
						
					$filene = _ROOT_DIR.'/upload/'.$dir.$newnam.'.'.$ext;
					$newfilename[] = $newnam.'.'.$ext;

					if(!file_exists(_ROOT_DIR.'/upload/'.$dir)){
						mkdir(_ROOT_DIR.'/upload/'.$dir, 0777, true);
					}
					
					if(!move_uploaded_file($file['tmp_name'][0], $filene)) {
						$error[] = t("Upload error.");
					}
				}
			}else{
				$error[] = Utilities::getFileUploadError($file["error"][0]).".";
			}
		}

		if(count($file["name"]) == 1)
			return array("error" => $error[0], "filename" => $newfilename[0]);
		return array("error" => $error, "filename" => $newfilename);
	}

	public static function getFilesContentFromZip($zip, &$files = NULL){
		$files = [];
        $contents = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $stream = $zip->getStream($name);
            $content = "";
            while (!feof($stream)) {
                $content .= fread($stream, 2);
            }
            fclose($stream);
			$files[] = $name;
            $contents[$name] = $content;
        }
		return $contents;
	}

	public static function getFileDocument($file, $index = 1){
		$docComments = array_filter(
			token_get_all( file_get_contents($file) ), function($entry) {
				return $entry[0] == T_DOC_COMMENT;
			}
		);
		$fileDocComment = array_shift( $docComments );
		return $fileDocComment[$index];
	}

	public static function getFileUploadError($error) {
		switch ($error) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				return t('No file sent');
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return  t('Exceeded filesize limit');
				break;				
		}

		return  t('Unknown errors');
	}

	public static function resizeImage($path, $new_width, $new_height){
		$mime = getimagesize($path);
		if($mime['mime']=='image/png') { 
			$src_img = imagecreatefrompng($path);
		}
		if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
			$src_img = imagecreatefromjpeg($path);
		}  
		$old_x = imageSX($src_img);
		$old_y = imageSY($src_img);
		if(!($old_x > $new_width || $old_y > $new_height)){	
			imagedestroy($src_img);
			return $path;
		}
		if($old_x > $old_y) 
		{
			$thumb_w    =   $new_width;
			$thumb_h    =   $old_y*($new_height/$old_x);
		}
		if($old_x < $old_y) 
		{
			$thumb_w    =   $old_x*($new_width/$old_y);
			$thumb_h    =   $new_height;
		}
		if($old_x == $old_y) 
		{
			$thumb_w    =   $new_width;
			$thumb_h    =   $new_height;
		}
		$dst_img = ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y); 

		if($mime['mime']=='image/png') {
			$result = imagepng($dst_img,$path,8);
		}
		if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
			$result = imagejpeg($dst_img,$path,80);
		}
	
		imagedestroy($dst_img); 
		imagedestroy($src_img);
	
		return $result;
	}

	public static function getTimeFormat(){
		return Config::getS("timeformat", "d.n.Y G:i");
	}

	public static function UrlCombine($url, $add) {
		if(strpos($url, "?") === false){
			return $url."?".$add;
		}
		return $url."&".$add;
	}
	
	public static function addHistory($prefix, $parent, $type, $data = array(), $text = "", $userid = null){
		$data = array(
			"user" 		=> ($userid == null ? User::current()["id"]: $userid),
			"ip" 		=> Utilities::ip(),
			"browser" 	=> $_SERVER['HTTP_USER_AGENT'],
			"date" 		=> time(),
			"parent" 	=> $prefix."_".$parent,
			"type" 		=> $prefix."_".$type,
			"data"		=> Config::ssave($data),
			"text"		=> $text
		);
		if($data["user"] == null) $data["user"] = -1;

		$result = dibi::query('INSERT INTO :prefix:history', $data);
	}

	public static function getHistory($prefix, $parent, $type, $userid = null, $until = null){
		if($userid == null && User::current() == false) $userid = -1;
		if($until == null)
			return dibi::query('SELECT * FROM :prefix:history WHERE parent = %s', $prefix."_".$parent, " AND type LIKE %~like~", $prefix."_".$type, " AND user = %i", ($userid == null ? User::current()["id"]: $userid), "ORDER BY id DESC");
		return dibi::query('SELECT * FROM :prefix:history WHERE parent = %s', $prefix."_".$parent, " AND type LIKE %~like~", $prefix."_".$type, " AND user = %i", ($userid == null ? User::current()["id"]: $userid), " AND date > %i", $until, "ORDER BY id DESC");
	}

	public static function drawHistory($history) {
		$user = User::get($history["user"], true);
		$browser = Utilities::get_browser_properties($history["browser"]);
		$out = "<table class='table-bordered table-norm table-small-text' style='width: 100%;'>";
			$out.= "<tr><td>".t("Id")."</td><td>".$history["id"]."</td></tr>";
			$out.= "<tr><td>".t("Text")."</td><td>".$history["text"]."</td></tr>";
			$out.= "<tr><td>".t("Type")."</td><td>".$history["type"]."</td></tr>";
			$out.= "<tr><td>".t("User")."</td><td>".$user["email"]."</td></tr>";
			$out.= "<tr><td>".t("Ip")."</td><td>".$history["ip"]."</td></tr>";
			$out.= "<tr><td>".t("Browser")."</td><td>".$browser["browser"]." (".$browser["version"].")</td></tr>";
			$out.= "<tr><td>".t("Date")."</td><td>".Strings::str_time($history["date"])."</td></tr>";
			$out.= "<tr><td colspan=2><a href=# onclick=\"$('#history_data_".$history["id"]."').toggle();return false;\">+ ".t("Data")."</a></td></tr>";
			$out.= "<tr><td colspan=2 id='history_data_".$history["id"]."' style='display:none;'>".($history["data"] == ""? "<i>null</i>": Config::sload($history["data"]))."</td></tr>";
		$out.= "</table>";
		return $out;
	}

	/** @Deprecated */
	public static function getDataByUrl($url, &$nurl = NULL){
		$cSession = curl_init();
		curl_setopt($cSession, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($cSession, CURLOPT_VERBOSE, true);
		curl_setopt($cSession, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($cSession, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($cSession, CURLOPT_FOLLOWLOCATION, true);
		//curl_setopt($cSession, CURLOPT_POST, count($data));
		//curl_setopt($cSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($cSession, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($cSession, CURLOPT_URL,$url);
		curl_setopt($cSession, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($cSession, CURLOPT_HEADER, false);
		$result=curl_exec($cSession);
		$nurl = curl_getinfo($cSession, CURLINFO_EFFECTIVE_URL);
		curl_close($cSession);	
		return $result;
	}

	public static function captcha($name){
		$text = Strings::upper(Strings::random(5));
		Cookies::set("captcha_".$name,$text);
		return Images::text($text,array(105,24),21);
	}

	public static function captcha_test($name, $value){
		if(!isset($_COOKIE["captcha_".$name])) return false;
		if($_COOKIE["captcha_".$name] == $value)
			return true;
		return false;
	}

	public static function get_browser_properties($agent = null){
		$browser =array();
		if($agent==null){$agent=$_SERVER['HTTP_USER_AGENT'];}
		if(stripos($agent,"firefox")!==false){
			$browser['browser'] = 'Firefox';
			$domain = stristr($agent, 'Firefox');
			$split =explode('/',$domain);
			$browser['version'] = $split[1];
		}
		if(stripos($agent,"Opera")!==false){
			$browser['browser'] = 'Opera';
			$domain = stristr($agent, 'Version');
			$split =explode('/',$domain);
			$browser['version'] = $split[1];
		}
		if(stripos($agent,"MSIE")!==false){
			$browser['browser'] = 'Internet Explorer';
			$domain = stristr($agent, 'MSIE');
			$split =explode(' ',$domain);
			$browser['version'] = $split[1];
		}
		if(stripos($agent,"Chrome")!==false){
			$browser['browser'] = 'Google Chrome';
			$domain = stristr($agent, 'Chrome');
			$split1 =explode('/',$domain);
			$split =explode(' ',$split1[1]);
			$browser['version'] = $split[0];
		}
		else if(stripos($agent,"Safari")!==false){
			$browser['browser'] = 'Safari';
			$domain = stristr($agent, 'Version');
			$split1 =explode('/',$domain);
			$split =explode(' ',$split1[1]);
			$browser['version'] = $split[0];
		}else {
			return false;	
		}
		return $browser;
	}

	public static function randomweight($weights){
		$rand = ((float)rand()/(float)getrandmax());
		asort($weights);
		end($weights);
		$result = $weights[key($weights)];
		foreach ($weights as $value => $weight) {
			if ($rand < $weight) {
			   $result = $value;
			   break;
			}
			$rand -= $weight;
		}
		reset($weights);
		return $result;
	}

	public static function showActionButton($name = "Save", $icon = "fas fa-save", $action = ""){
		?>
        <script>
        $(function(){
            setTimeout(function(){
                actionButton.setText("<?php echo $name; ?>")
                actionButton.changeIcon("<?php echo $icon; ?>");
                actionButton.show();
                actionButton.onclick(function(){
                    <?php echo $action; ?>
                });
            }, 100);
        });
        </script>
        <?php
	}

	public static function rowsToArray($rows){
		$array = [];
		foreach ($rows as $n => $row) {
			$array[] = $row;
		}
		return $array;
	}

	/** @Deprecated */
	public static function uploadForm($url, $formname, $id, $width = 310){
		echo "<div class=uploaddiv style='width: 83%;max-width:".$width."px;'>";
			echo '<form action="'.$url.'" method="post" enctype="multipart/form-data" id="'.$formname.'">';
			echo '<input type="file" name="myfile">';
			echo '<input type="submit" value="Upload">';
			echo '</form>';

			echo '<div class="progress" id="prg'.$formname.'">';
				echo '<div class="bar" id="bar'.$formname.'"></div>';
				echo '<div class="percent" id="per'.$formname.'">0%</div>';
			echo '</div>';

		echo '<div id="status'.$formname.'"></div>';
		echo "</div>";
		?>
		<script>
		$(function() {
			var bar = $('#bar<?php echo $formname; ?>');
			var percent = $('#per<?php echo $formname; ?>');
			var status = $('#status<?php echo $formname; ?>');
			$('#prg<?php echo $formname; ?>').hide();

			$('#<?php echo $formname; ?>').ajaxForm({
				beforeSend: function() {
					status.empty();
					var percentVal = '0%';
					$('#<?php echo $formname; ?>').hide();
					$('#prg<?php echo $formname; ?>').show();
					bar.width(percentVal)
					status.hide();
					percent.html(percentVal);
				},
				uploadProgress: function(event, position, total, percentComplete) {
					var percentVal = percentComplete + '%';
					bar.width(percentVal)
					percent.html(percentVal);
				},
				success: function() {
					var percentVal = '100%';
					bar.width(percentVal)
					percent.html(percentVal);
				},
				complete: function(xhr) {
					$('#<?php echo $formname; ?>').show();
					$('#prg<?php echo $formname; ?>').hide();
					var rtc = xhr.responseText;
					if(rtc.charAt(0) == "!"){
						$("<?php echo $id; ?>").val(rtc.substr(1, rtc.length - 1));
						$("<?php echo $id; ?>").trigger("change");
					}else{
						status.show();
						status.html(rtc);
					}
				}
			});
		});
		</script>
		<?php
	}

	public static function post_debug($output = FALSE){
		$return = "<table style='table-layout: fixed; border-collapse: collapse;width:100%;' border=0 class='snowLeopard'>";
		$return.= "<tr><th width=80>Type</th><th width=300>Key</th><th width=600>Value</th></tr>";
		foreach($_POST as $key => $value){
			$return.= "<tr><td>POST</td><td>".$key."</td><td>".htmlentities($value)."</td></tr>";
		}
		foreach($_GET as $key => $value){
			$return.= "<tr><td>GET</td><td>".$key."</td><td>".htmlentities($value)."</td></tr>";
		}
		if(count($_POST) == 0 and count($_GET) == 0)
			$return.= "<tr><td colspan=2>Žádné data...</td></tr>";
		$return.= "</table>";

		if(!$output)
			echo $return;
		return $return;
	}

	public static function generateMap($place){
		return file_get_contents("https://open.mapquestapi.com/staticmap/v4/getplacemap?key=".Config::getS("map-key", "")."&location=".urlencode($place)."&size=150,150&zoom=14&showicon=blue_1-1");
	}

	public static function getGeolocation($customIp = NULL){
		$ip = $customIp ?? Utilities::ip();

		$url = "https://www.iplocate.io/api/lookup/".$ip."/json/";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result);
	}

	public static function ip($notFancy = false){
		if(defined("CUSTOM_IP")){
			return CUSTOM_IP;
		}
		
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}else if(isset($_SERVER["HTTP_FORWARDED_FOR"])){
			$ip = $_SERVER["HTTP_FORWARDED_FOR"];
		}else{
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		if($notFancy) return $ip;

		if($ip == "::1"){ $ip = "127.0.0.1"; }
		if(strpos($ip, ",") !== false){
			$ip = explode(",", $ip);
			return trim($ip[0]);
		}
		
		return $ip;
	}

	public static function isEmail($email){
		if(filter_var($email, FILTER_VALIDATE_EMAIL)){
			return true;
		}else{
			return false;
		}
	}

	public static function random_float($min, $max) {
		$mul = 1000000;
		return mt_rand($min*$mul,$max*$mul)/$mul;
		//return random_int($min, $max - 1) + (random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX );
	}

	/**
	 * @param String $t Title
	 * @param String $m Message
	 */
	public static function fatal($t, $m){
		if(isset($_COOKIE["debug"]) and $_COOKIE["debug"]==true){
			echo "<div style='position:fixed;top:0px;left:0px;width:100%;height:100%;background-color:white;z-index: 2000000;overflow: auto;' id=error>";
				echo "<h1 style='background-color: red;padding: 13px;margin: 0px;color: white;' onClick=\"$('#error').hide();\">".$t."</h1>";
				echo "<div style='color:black;'>".$m."</div>";
				echo "<div style='height:95px;'></div>";
				echo "<div style='color:black;position:fixed;bottom:0px;left:0px;border-top: 1px solid silver;width: 100%;background: #E8E8E8;font-size:11px;'><div style='color:black;padding: 7px;'>";
					echo "Report generate at <b>".Date(Utilities::getTimeFormat(), Time())."</b><br>";
					if(!isset($_GET["url"])){ $url = ""; }else{ $url = $_GET["url"]; }
					if(substr($url, -1) == "/"){ $url = substr($url, 0, -1); }
					if(substr($_SERVER["REQUEST_URI"], -1) != "/"){ $_SERVER["REQUEST_URI"].="/"; }
					if(isset($_SERVER["REQUEST_SCHEME"])){$http=$_SERVER["REQUEST_SCHEME"];}else{$http="http";}
					$url = $http . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
					echo "Location <b>".$url."</b><br>";
					echo "PHP Version <b>".phpversion()."</b><br>";
					echo "Number of mysql comands <b>".(dibi::$numOfQueries)."</b> ( Time <b>".(dibi::$totalTime)."s</b> )<br>";
					echo "SnowLeopard RS Debuger ver. <b>1.0.0</b>";
				echo "</div></div>";
			echo "</div>";
		}else{
			echo "<h1>Error 500</h1>";
			echo "Code: ".date("Ydm")."/"._debugToken;
			Bootstrap::$debugger->storeDebugPage();
			exit;
		}
	}

	public static function getFileLines($file, $line){
		$line--;
		$file_ = file_get_contents($file);
		$radk = explode("\n", $file_);
		$start = $line-4;;
		$end = $line+5;
		if($start < 0){
			$start = 0;
			$end = 8;
		}
		$return = "<table style='table-layout: fixed; border-collapse: collapse;'>";
		for($i = $start; $i < $end; $i++){
			$code = htmlspecialchars($radk[$i]);
			if(substr(trim($radk[$i]), 0, 2) == "//"){
				$c="green";
			}else{
				$c="black";
				$code = str_replace("echo", "<b>echo</b>", $code);
			}

			$return.="<tr".($i==$line?" bgcolor=red style='color:white;font-weight:bold;'":"")."><td style='color:black;padding:3px;padding-right: 7px;text-align: right;background: silver;'><b>".$i.".</b> </td><td style='color:".($i==$line?"white":$c).";padding:3px;'>".str_replace("\t","<span style='display:inline-block;width:20px;'></span>",$code)."</td></tr>";
		}
		$return.= "</table>";
		return $return;
	}

	public static function log($text, $data){
		$data = array(
					"text" 	=> $text,
					"date" 	=> time(),
					"ip"	=> Utilities::ip(),
					"user"	=> Config::ssave(User::current()),
					"data" 	=> ($data==""?"":Config::ssave($data))
				);
		dibi::query('INSERT INTO :prefix:log', $data);
		return dibi::getInsertId();
	}

	public static function select($data, $sel = "", $name = "", $style = "", $id = "", $class = "", $justone = false){
		$output = "<select name='".$name."' style='".$style."' id='".$id."' class='".$class."' data-search='true'>";
		foreach($data as $key => $value){
			if($justone) $key = $value;
			$output.="<option value='".$key."'".($sel==$key?" selected":"").">".$value."</option>";
		}
		$output.="</select>";
		return $output;
	}

	public static function check($state){
		if(($state == 1 or $state === true) and $state != "" and $state != null)
			return "checked='checked'";
		return "";
	}

	public static function selected($state){
		if(($state == 1 or $state === true) and $state != "" and $state != null)
			return "selected";
		return "";
	}

	public function sendemailtemplate($email, $template, $model, $from = NULL){
		$template = dibi::query("SELECT * FROM :prefix:templates WHERE code=%s", $template)->fetch();
		if($template == NULL) {
			$this->root->log("Template '".$template."' was not found!", $this->root->_MESSAGE_WARNING);
			return false;
		}

        ob_start();
        $this->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$template["hash"].".view", $model);
        $text = ob_get_contents();
        ob_end_clean();

        $this->sendemail($email, $template["name"], $text, $from, false, false);

		return true;
	}	

	public function sendemail($email, $subject, $message, $from = NULL, $bbcode = true, $sendfrom = true){
		if($from == NULL)
			$from = $this->root->config->getD("email-webmaster", User::get(1)["email"]);

		if($bbcode){
			$plugin = $this->root->module_manager->hook_call("page.bbcode", null, $message);
			$message = $plugin["output"];
		}
		if($sendfrom){
			$message.="<br><span style='font-size:11px;'>".t("This email was sent from the site")." <b>".$this->root->page->config["Title"]."</b> - ".$this->root->router->url."</span>";
		}

		if($this->root->config->getD("email-enable", "1") == 1){
			$mail = mail($email,'=?utf-8?B?'.base64_encode($subject).'?=',$message,
				"From: ".$this->root->page->config["Title"]." <".$from.">\r\n"
				."Reply-To: ".$from."\r\n"
				."Content-type: text/html; charset=UTF-8\r\n"
				."X-Mailer: PHP/" . phpversion());
		}

		$data = array(
					"_from" 	=> $from,
					"_to" 		=> $email,
					"subject" 	=> $subject,
					"message" 	=> $message,
					"ip" 		=> Utilities::ip(),
					"user" 		=> User::currentOrNull(true, "id"),
					"time"		=> time()
				);

		$result = dibi::query('INSERT INTO :prefix:emails', $data);

		return $message;
	}

	public static function CookiesAccepted($isShow = false){
		$t = Bootstrap::$self;
		$cookieAcceptShow = $t->config->getD("cookie-accept-show", 1);
		if($isShow)
			return ($cookieAcceptShow != 1 && $cookieAcceptShow != 2) || (($cookieAcceptShow == 1 || $cookieAcceptShow == 2) && isset($_COOKIE["cookieAccept"]) && $_COOKIE["cookieAccept"] == "yes");
		return $cookieAcceptShow != 1 || ($cookieAcceptShow == 1 && isset($_COOKIE["cookieAccept"]) && $_COOKIE["cookieAccept"] == "yes");
	}

	public static function orderBy($orders, $_key = "order"){
		$output = "";
		$orderby = "";
		$first = true;
		foreach($orders as $key => $value){
			if($output != ""){ $output.=", "; }
			if($first){ $orderby = $key; }
			if((isset($_GET[$_key]) and $_GET[$_key] == $key) or (!isset($_GET[$_key]) and $first)){
				$output.="<b class='order selected'>".$value."</b>";
				$orderby = $key;
			}else{
				$output.="<a href='".Router::urladd(array($_key => $key))."'>".$value."</a>";
			}
			$first = false;
		}
		return array("html" => "<span class=pager>".$output."</span>", "orderby" => $orderby);
	}

	public static function getCategoryArticles($id, $limit = 10){
		$level = User::permission(User::currentOrNull(false, "permission"))["level"];
		$result = dibi::query("SELECT * FROM :prefix:category WHERE alias=%s", $id, "OR id=%i", $id)->fetch();
		$paginator = new Paginator($limit, Router::url().$result["alias"]."/?page=(:page)");
		$paginator->queryCount("SELECT count(*) FROM :prefix:article WHERE category = %i", $result["id"], " AND state = 0 AND tags NOT LIKE %~like~ ", "template"," AND visiblity = %s", "", "AND language = %s", _LANGUAGE);
		
		$model = array(
			"items" => $paginator->query('SELECT * FROM :prefix:article WHERE category = %i', $result["id"], " AND state = 0 AND tags NOT LIKE %~like~ ", "template"," AND visiblity = %s", "", "AND language = %s", _LANGUAGE),
			"itemsCount" => $paginator->getCount(),
			"page" => $paginator->getPage(),
			"limit" => 10
		);

		$model["description"] = $result["description"];
		$model["level"] = $result["minlevel"];
		$model["canshow"] = !($level < $result["minlevel"]);
		return $model;
	}

	public static function getArticle($t, $id, $lang = ""){
		$result = dibi::query("SELECT * FROM :prefix:article WHERE id=%i", $id, " or alias=%s", $id)->fetch();
		$languages = explode(",", Database::getConfig("languages"));
		$default   = Database::getConfig("default-lang");

		foreach($languages as $lng){
			if($lng == $default){
			}else{
				$fln = dibi::query("SELECT * FROM :prefix:article WHERE (mid=%i", $result["id"], " or id=%i", $result["id"],") and language=%s", $lng)->fetch();
				if($fln != null)
				{
					if($lng == $lang){
						$id = $fln["id"];
						$result = dibi::query("SELECT * FROM :prefix:article WHERE id=%i", $id, " or alias=%s", $id)->fetch();
						break;
					}
				}
			}
		}

		if($result == NULL){
			return "";
		}

		$r = $result["text"];
		$plugin = $t->root->module_manager->hook_call("page.bbcode", null, $r);

		return $plugin["output"];
	}		

	public static function convertBtoMB($bytes, $add = null){
		if($bytes == -1) return "unlimited";
		$ret = round(Utilities::divideFloat($bytes, 1048576, 4),4);
		if(!is_null($add)) return $ret." ".$add;
		return $ret;
	}

	public static function getCallerInfo($co = 1, $simply = false){
		$info = debug_backtrace(3)[$co];
		if($simply){
			return array(
				"file" => $info["file"], 
				"line" => $info["line"], 
				"function" => $info["function"], 
				"class" => $info["class"],
				"name" => basename($info["file"])
			);
		}
		return $info;
	}

	public static function getFileName($name){
		if (strpos($name, '\\') !== false) $name = str_replace("\\", "/", $name);

		return str_replace(_ROOT_DIR, ".", $name);
	}

	public static function decideUrl($url){
		if($url == "") return "";
		if(substr($url, 0, 4) == "http")
			return $url;
		return Router::url().$url;
	}

	public static function getSatusCodeName($code = NULL) {
		$text = "";

		if($code == NULL) $code = http_response_code();

		switch ($code) {
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
			case 203: $text = 'Non-Authoritative Information'; break;
			case 204: $text = 'No Content'; break;
			case 205: $text = 'Reset Content'; break;
			case 206: $text = 'Partial Content'; break;
			case 300: $text = 'Multiple Choices'; break;
			case 301: $text = 'Moved Permanently'; break;
			case 302: $text = 'Moved Temporarily'; break;
			case 303: $text = 'See Other'; break;
			case 304: $text = 'Not Modified'; break;
			case 305: $text = 'Use Proxy'; break;
			case 400: $text = 'Bad Request'; break;
			case 401: $text = 'Unauthorized'; break;
			case 402: $text = 'Payment Required'; break;
			case 403: $text = 'Forbidden'; break;
			case 404: $text = 'Not Found'; break;
			case 405: $text = 'Method Not Allowed'; break;
			case 406: $text = 'Not Acceptable'; break;
			case 407: $text = 'Proxy Authentication Required'; break;
			case 408: $text = 'Request Time-out'; break;
			case 409: $text = 'Conflict'; break;
			case 410: $text = 'Gone'; break;
			case 411: $text = 'Length Required'; break;
			case 412: $text = 'Precondition Failed'; break;
			case 413: $text = 'Request Entity Too Large'; break;
			case 414: $text = 'Request-URI Too Large'; break;
			case 415: $text = 'Unsupported Media Type'; break;
			case 500: $text = 'Internal Server Error'; break;
			case 501: $text = 'Not Implemented'; break;
			case 502: $text = 'Bad Gateway'; break;
			case 503: $text = 'Service Unavailable'; break;
			case 504: $text = 'Gateway Time-out'; break;
			case 505: $text = 'HTTP Version not supported'; break;
			default:
				exit('Unknown http status code "' . htmlentities($code) . '"');
			break;
		}

		return $text;
	}

	public static function divideFloat($a, $b, $precision=3) {
		$a*=pow(10, $precision);
		$result=(int)($a / $b);
		if (strlen($result)<$precision) return '0.' . $result;
		else return preg_replace('/(\d{' . $precision . '})$/', '.\1', $result);
	}

	public static function permissionSelect($selected = 1, $name = "permission", $style = "", $class = "form-control", $by = "id", $showNotRegister = false){
		$result = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
		$output = "<select name='".$name."' id='".$name."' style='".$style."' class='".$class."'>";
			foreach ($result as $n => $row) {
				$output.= "<option value='".$row[$by]."' ".Utilities::selected($selected == $row[$by]).">".$row["name"]."</option>";
			}
			if($showNotRegister) {
				$output.= "<option value='0' ".Utilities::selected($selected == 0).">".t("Not registered")."</option>";
			}
		$output.= "</select>";
		return $output;
	}

	public static function getMinMax($array, $selector) {
		$itemsLinq = new Linq($array);
		$itemsOrder = $itemsLinq->OrderBy($selector)->ToArray();    
		return array("min" => $itemsOrder[0], "max" => $itemsOrder[count($itemsOrder) - 1]);
	}

	public static function vardump($object, $level = 0){
		echo "<div style='margin-left: ".($level * 10)."px;' class='var-dump level-".$level."'>";
		$move = 0;
		if(is_array($object)){
			echo "<div class=type>array(".count($object).")</div>";
			$move = 10;
		
			foreach($object as $n => $o) {
				echo "<div class=prop style='padding-left: ".$move."px;'><span class=name>";
				if(is_numeric($n)){
					echo $n;
				}else{
					echo "'".$n."'";
				}
				echo "</span> => ";
				if(is_null($o)) {
					echo "<span class=typev>NULL</span> ";
				} elseif(is_array($o)){
					Utilities::vardump($o, $level + 1);
				}elseif(is_object($o)){
					Utilities::vardump($o, $level + 1);
				}else{
					$type = gettype($o);
					echo "<span class=typev>".$type."</span> ";
					echo "<span class='value type-".$type."'>";
					if($type == "string"){
						echo "'".htmlentities($o)."'";					
					}
					elseif($type == "boolean"){
						if($o == true){
							echo "true";
						}else{
							echo "false";
						}
					}
					else{
						echo $o;
					}
					echo "</span>";
					if($type == "string"){
						echo " <span class=string-len>(length=".strlen($o).")</span>";
					}
				}
				echo "</div>";
			}
		}else if($object != null){
			echo "<div class=prop style='padding-left: ".$move."px;'><span class=name>";
			$type = gettype($object);
			echo "<span class=typev>".$type."</span> ";
			echo "<span class='value type-".$type."'>";
			if($type == "string"){
				echo "'".htmlentities($object)."'";					
			}else{
				Utilities::vardump(get_object_vars($object));
			}
			echo "</span>";
			if($type == "string"){
				echo " <span class=string-len>(length=".strlen($object).")</span>";
			}
			echo "</div>";
		}else{
			echo "<div class=prop style='padding-left: ".$move."px;'><span class=name>";
			echo "<span class=typev>NULL</span>";
			echo "</div>";
		}
		echo "</div>";
	}

	public static function milliseconds() {
		$mt = explode(' ', microtime());
		return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
	}

	public static function GetPaginatorArray($page, $limit, $total) {		
		$max = ceil($total / $limit);
		$total = ceil($total / $limit);		

		$data = array("pages" => array());

		if($total > 1 && $page > 1){
			$prev = $page - 1; if($prev < 1) $prev = 1;			
			$data["pages"][] = array("text" => "<", "page" => $prev);
		}

		$offset = 2;

		if($total > $offset * 2 && $page > $offset + 1){	
			$data["pages"][] = array("text" => 1, "page" => 1);		
			$data["pages"][] = array("text" => "...", "static" => true);
		}

		if($max > ($offset * 2) + 1){			
			$start = $page - $offset;
			$size = $page + $offset;
			
			if($start < 1){				
				$start = 1;
				$size = ($offset * 2) + 1;
			}		
			if($start + ($offset * 2) + 1 >= $max){
				$start = $max - ($offset * 2);
				$size = $start + ($offset * 2) + 1;
			}			
			if($size > $max){
				$size = $max;
			}	

			for($i = $start; $i <= $size; $i++){
				if($page == $i){
					$data["pages"][] = array("text" => $i, "page" => $i, "current" => true);
				}else{
					$data["pages"][] = array("text" => $i, "page" => $i);
				}
			}
		}else{			
			for($i = 1; $i <= $max; $i++){
				$class = "";
				if($page == $i){
					$data["pages"][] = array("text" => $i, "page" => $i, "current" => true);
				}else{
					$data["pages"][] = array("text" => $i, "page" => $i);
				}
			}
		}

		if($total > ($total - ($offset * 2)) && $page < ($total - $offset - 1)){
			$data["pages"][] = array("text" => "...", "static" => true);
			$data["pages"][] = array("text" => $total, "page" => $total);			
		}	

		if($total > 0 && $page + 1 <= $max){
			$next = $page + 1; if($next > $max) $next = $max;
			$data["pages"][] = array("text" => ">", "page" => $next);			
		}

		return $data;
	}

	public static function errorForm($model, $name) {
		if(isset($model[$name])){
			echo "<div class='inpoerror'>".$model[$name]."</div>";
		}
	}

	public static function closetags($html) {
		$result = "";
		$openTags = array();
		$closeTags = array();
		$temp = "";
		$tagOpen = false;
		$tagClose = false;
		$tagOneClosed = false;
		$oneTag = false;
		$tagName = "";
		$readToClose = null;
		$tagPrevMinus = false;
		$oo = 0;
		$qq = 0;
		$html = str_replace("\r", "", $html);
		$oneTags = array("img", "hr", "input", "br", "hr", "meta", "!doctype");
		for($i = 0; $i <= strlen($html); $i++){
			$char = substr($html, $i, 1);
			$char2 = substr($html, $i+1, 1);
			
			if(++$qq > 1){
			    $tagPrevMinus = false;
			}
			if($readToClose != null && $readToClose != $char){
				$temp.= $char;
				continue;
			}
			if($readToClose == $char){
				$readToClose = null;
				$temp.= $char;
				continue;
			}
			if($char == "\"" || $char == "'" && $tagOpen){
				$temp.= $char;
				$readToClose = $char;
				continue;
			}
			if($char == "<" && $char2 != " "){
				$tagOpen = true;
				$result.= $temp."<";
				$temp = "";
				$tagOneClosed = false;
				$tagPrevMinus = true;
				$qq = 0;
				continue;
			}
			if(($char == " " || $char == "\n") && $tagOpen && $tagName == ""){
				$oneTag = in_array($temp, $oneTags);
				$result.= $temp." ";
				$tagName = $temp;
				$temp = "";
				continue;
			}
			if($char == "/" && $tagOpen){
				if($tagPrevMinus){
					$tagPrevMinus = false;
					//if($oo>0){$oo--;}
					$tagOpen = false;
					$tagClose = true;
					$result.="/";
					$temp = "";
					continue;
				}else{
					$closeTags[] = $tagName;
					$tagOneClosed = true;  
				}
				$temp.="/";
				continue;
			}
			if($char == ">" && $tagOpen){
				if($tagName == ""){
					$tagName = $temp;
				}
				if(substr($tagName, strlen($tagName) - 1, 1) == "/"){
				    $tagName = substr($tagName, 0, strlen($tagName) - 1);
				    $closeTags[] = $tagName;
				}
				$result.= $temp;
				$oneTag = in_array($tagName, $oneTags);
				if($oneTag && !$tagOneClosed){
					$closeTags[] = $tagName;
					if($tagName != "!doctype"){
						$result.= " /";
					}
				}
				
				$oneTag = false;
				$tagOpen = false;
				$result.=">";
				$openTags[] = $tagName;
				$tagName = "";
				$temp = "";
				continue;
			}
			if($char == ">" && $tagClose){
			    $oo--;
				$result.= $temp.">";
				$closeTags[] = $temp;
				$temp = "";
				$tagClose = false;
				continue;
			}
			
			$temp.= $char;
		}
		$result.= $temp;
		
		$openTags = array_reverse($openTags);
		
		for($i = 0; $i < count($openTags); $i++){
			if(!in_array($openTags[$i], $closeTags)){
				$result.= "</".$openTags[$i].">";
			}else {
			  unset($closeTags[array_search($openTags[$i], $closeTags)]);
			}
		}
		return $result;
	} 

	public static $price = array (
		'AUD' => 'Australia Dollar',
		'BGN' => 'Bulgaria Lev',
		'BRL' => 'Brazil Real',
		'CAD' => 'Canada Dollar',
		'CNY' => 'China Yuan Renminbi',
		'HRK' => 'Croatia Kuna',
		'CZK' => 'Czech Republic Koruna',
		'DKK' => 'Denmark Krone',
		'EUR' => 'Euro Member Countries',
		'HKD' => 'Hong Kong Dollar',
		'HUF' => 'Hungary Forint',
		'INR' => 'India Rupee',
		'IDR' => 'Indonesia Rupiah',
		'ILS' => 'Israel Shekel',
		'JPY' => 'Japan Yen',
		'MYR' => 'Malaysia Ringgit',
		'MXN' => 'Mexico Peso',
		'NZD' => 'New Zealand Dollar',
		'PHP' => 'Philippines Peso',
		'PLN' => 'Poland Zloty',
		'RON' => 'Romania New Leu',
		'RUB' => 'Russia Ruble',
		'SGD' => 'Singapore Dollar',
		'ZAR' => 'South Africa Rand',
		'SEK' => 'Sweden Krona',
		'CHF' => 'Switzerland Franc',
		'THB' => 'Thailand Baht',
		'TRY' => 'Turkey Lira',
		'GBP' => 'United Kingdom Pound',
		'USD' => 'United States Dollar',
		'UYU' => 'Uruguay Peso'
	);

	public static $timezones = array(
		"America/Adak",
		"America/Argentina/Buenos_Aires",
		"America/Argentina/La_Rioja",
		"America/Argentina/San_Luis",
		"America/Atikokan",
		"America/Belem",
		"America/Boise",
		"America/Caracas",
		"America/Chihuahua",
		"America/Cuiaba",
		"America/Denver",
		"America/El_Salvador",
		"America/Godthab",
		"America/Guatemala",
		"America/Hermosillo",
		"America/Indiana/Tell_City",
		"America/Inuvik",
		"America/Kentucky/Louisville",
		"America/Lima",
		"America/Managua",
		"America/Mazatlan",
		"America/Mexico_City",
		"America/Montreal",
		"America/Nome",
		"America/Ojinaga",
		"America/Port-au-Prince",
		"America/Rainy_River",
		"America/Rio_Branco",
		"America/Santo_Domingo",
		"America/St_Barthelemy",
		"America/St_Vincent",
		"America/Tijuana",
		"America/Whitehorse",
		"America/Anchorage",
		"America/Argentina/Catamarca",
		"America/Argentina/Mendoza",
		"America/Argentina/Tucuman",
		"America/Atka",
		"America/Belize",
		"America/Buenos_Aires",
		"America/Catamarca",
		"America/Coral_Harbour",
		"America/Curacao",
		"America/Detroit",
		"America/Ensenada",
		"America/Goose_Bay",
		"America/Guayaquil",
		"America/Indiana/Indianapolis",
		"America/Indiana/Vevay",
		"America/Iqaluit",
		"America/Kentucky/Monticello",
		"America/Los_Angeles",
		"America/Manaus",
		"America/Mendoza",
		"America/Miquelon",
		"America/Montserrat",
		"America/Noronha",
		"America/Panama",
		"America/Port_of_Spain",
		"America/Rankin_Inlet",
		"America/Rosario",
		"America/Sao_Paulo",
		"America/St_Johns",
		"America/Swift_Current",
		"America/Toronto",
		"America/Winnipeg",
		"America/Anguilla",
		"America/Argentina/ComodRivadavia",
		"America/Argentina/Rio_Gallegos",
		"America/Argentina/Ushuaia",
		"America/Bahia",
		"America/Blanc-Sablon",
		"America/Cambridge_Bay",
		"America/Cayenne",
		"America/Cordoba",
		"America/Danmarkshavn",
		"America/Dominica",
		"America/Fort_Wayne",
		"America/Grand_Turk",
		"America/Guyana",
		"America/Indiana/Knox",
		"America/Indiana/Vincennes",
		"America/Jamaica",
		"America/Knox_IN",
		"America/Louisville",
		"America/Marigot",
		"America/Menominee",
		"America/Moncton",
		"America/Nassau",
		"America/North_Dakota/Beulah",
		"America/Pangnirtung",
		"America/Porto_Acre",
		"America/Recife",
		"America/Santa_Isabel",
		"America/Scoresbysund",
		"America/St_Kitts",
		"America/Tegucigalpa",
		"America/Tortola",
		"America/Yakutat",
		"America/Antigua",
		"America/Argentina/Cordoba",
		"America/Argentina/Salta",
		"America/Aruba",
		"America/Bahia_Banderas",
		"America/Boa_Vista",
		"America/Campo_Grande",
		"America/Cayman",
		"America/Costa_Rica",
		"America/Dawson",
		"America/Edmonton",
		"America/Fortaleza",
		"America/Grenada",
		"America/Halifax",
		"America/Indiana/Marengo",
		"America/Indiana/Winamac",
		"America/Jujuy",
		"America/Kralendijk",
		"America/Lower_Princes",
		"America/Martinique",
		"America/Merida",
		"America/Monterrey",
		"America/New_York",
		"America/North_Dakota/Center",
		"America/Paramaribo",
		"America/Porto_Velho",
		"America/Regina",
		"America/Santarem",
		"America/Shiprock",
		"America/St_Lucia",
		"America/Thule",
		"America/Vancouver",
		"America/Yellowknife",
		"America/Araguaina",
		"America/Argentina/Jujuy",
		"America/Argentina/San_Juan",
		"America/Asuncion",
		"America/Barbados",
		"America/Bogota",
		"America/Cancun",
		"America/Chicago",
		"America/Creston",
		"America/Dawson_Creek",
		"America/Eirunepe",
		"America/Glace_Bay",
		"America/Guadeloupe",
		"America/Havana",
		"America/Indiana/Petersburg",
		"America/Indianapolis",
		"America/Juneau",
		"America/La_Paz",
		"America/Maceio",
		"America/Matamoros",
		"America/Metlakatla",
		"America/Montevideo",
		"America/Nipigon",
		"America/North_Dakota/New_Salem",
		"America/Phoenix",
		"America/Puerto_Rico",
		"America/Resolute",
		"America/Santiago",
		"America/Sitka",
		"America/St_Thomas",
		"America/Thunder_Bay",
		"America/Virgin",
		"Indian/Antananarivo",
		"Indian/Kerguelen",
		"Indian/Reunion",
		"Australia/ACT",
		"Australia/Currie",
		"Australia/Lindeman",
		"Australia/Perth",
		"Australia/Victoria",
		"Europe/Amsterdam",
		"Europe/Berlin",
		"Europe/Chisinau",
		"Europe/Helsinki",
		"Europe/Kiev",
		"Europe/Madrid",
		"Europe/Moscow",
		"Europe/Prague",
		"Europe/Sarajevo",
		"Europe/Tallinn",
		"Europe/Vatican",
		"Europe/Zagreb",
		"Pacific/Apia",
		"Pacific/Efate",
		"Pacific/Galapagos",
		"Pacific/Johnston",
		"Pacific/Marquesas",
		"Pacific/Noumea",
		"Pacific/Ponape",
		"Pacific/Tahiti",
		"Pacific/Wallis",
		"Indian/Chagos",
		"Indian/Mahe",
		"Australia/Adelaide",
		"Australia/Darwin",
		"Australia/Lord_Howe",
		"Australia/Queensland",
		"Australia/West",
		"Europe/Andorra",
		"Europe/Bratislava",
		"Europe/Copenhagen",
		"Europe/Isle_of_Man",
		"Europe/Lisbon",
		"Europe/Malta",
		"Europe/Nicosia",
		"Europe/Riga",
		"Europe/Simferopol",
		"Europe/Tirane",
		"Europe/Vienna",
		"Europe/Zaporozhye",
		"Pacific/Auckland",
		"Pacific/Enderbury",
		"Pacific/Gambier",
		"Pacific/Kiritimati",
		"Pacific/Midway",
		"Pacific/Pago_Pago",
		"Pacific/Port_Moresby",
		"Pacific/Tarawa",
		"Pacific/Yap",
		"Africa/Abidjan",
		"Africa/Asmera",
		"Africa/Blantyre",
		"Africa/Ceuta",
		"Africa/Douala",
		"Africa/Johannesburg",
		"Africa/Kinshasa",
		"Africa/Lubumbashi",
		"Africa/Mbabane",
		"Africa/Niamey",
		"Africa/Timbuktu",
		"Africa/Accra",
		"Africa/Bamako",
		"Africa/Brazzaville",
		"Africa/Conakry",
		"Africa/El_Aaiun",
		"Africa/Juba",
		"Africa/Lagos",
		"Africa/Lusaka",
		"Africa/Mogadishu",
		"Africa/Nouakchott",
		"Africa/Tripoli",
		"Africa/Addis_Ababa",
		"Africa/Bangui",
		"Africa/Bujumbura",
		"Africa/Dakar",
		"Africa/Freetown",
		"Africa/Kampala",
		"Africa/Libreville",
		"Africa/Malabo",
		"Africa/Monrovia",
		"Africa/Ouagadougou",
		"Africa/Tunis",
		"Africa/Algiers",
		"Africa/Banjul",
		"Africa/Cairo",
		"Africa/Dar_es_Salaam",
		"Africa/Gaborone",
		"Africa/Khartoum",
		"Africa/Lome",
		"Africa/Maputo",
		"Africa/Nairobi",
		"Africa/Porto-Novo",
		"Africa/Windhoek",
		"Africa/Asmara",
		"Africa/Bissau",
		"Africa/Casablanca",
		"Africa/Djibouti",
		"Africa/Harare",
		"Africa/Kigali",
		"Africa/Luanda",
		"Africa/Maseru",
		"Africa/Ndjamena",
		"Africa/Sao_Tome",
		"Atlantic/Azores",
		"Atlantic/Faroe",
		"Atlantic/St_Helena",
		"Atlantic/Bermuda",
		"Atlantic/Jan_Mayen",
		"Atlantic/Stanley",
		"Atlantic/Canary",
		"Atlantic/Madeira",
		"Atlantic/Cape_Verde",
		"Atlantic/Reykjavik",
		"Atlantic/Faeroe",
		"Atlantic/South_Georgia",
		"Asia/Aden",
		"Asia/Aqtobe",
		"Asia/Baku",
		"Asia/Calcutta",
		"Asia/Dacca",
		"Asia/Dushanbe",
		"Asia/Hong_Kong",
		"Asia/Jayapura",
		"Asia/Kashgar",
		"Asia/Kuala_Lumpur",
		"Asia/Magadan",
		"Asia/Novokuznetsk",
		"Asia/Pontianak",
		"Asia/Riyadh",
		"Asia/Shanghai",
		"Asia/Tehran",
		"Asia/Ujung_Pandang",
		"Asia/Vladivostok",
		"Asia/Almaty",
		"Asia/Ashgabat",
		"Asia/Bangkok",
		"Asia/Choibalsan",
		"Asia/Damascus",
		"Asia/Gaza",
		"Asia/Hovd",
		"Asia/Jerusalem",
		"Asia/Kathmandu",
		"Asia/Kuching",
		"Asia/Makassar",
		"Asia/Novosibirsk",
		"Asia/Pyongyang",
		"Asia/Saigon",
		"Asia/Singapore",
		"Asia/Tel_Aviv",
		"Asia/Ulaanbaatar",
		"Asia/Yakutsk",
		"Asia/Amman",
		"Asia/Ashkhabad",
		"Asia/Beirut",
		"Asia/Chongqing",
		"Asia/Dhaka",
		"Asia/Harbin",
		"Asia/Irkutsk",
		"Asia/Kabul",
		"Asia/Katmandu",
		"Asia/Kuwait",
		"Asia/Manila",
		"Asia/Omsk",
		"Asia/Qatar",
		"Asia/Sakhalin",
		"Asia/Taipei",
		"Asia/Thimbu",
		"Asia/Ulan_Bator",
		"Asia/Yekaterinburg",
		"Asia/Anadyr",
		"Asia/Baghdad",
		"Asia/Bishkek",
		"Asia/Chungking",
		"Asia/Dili",
		"Asia/Hebron",
		"Asia/Istanbul",
		"Asia/Kamchatka",
		"Asia/Kolkata",
		"Asia/Macao",
		"Asia/Muscat",
		"Asia/Oral",
		"Asia/Qyzylorda",
		"Asia/Samarkand",
		"Asia/Tashkent",
		"Asia/Thimphu",
		"Asia/Urumqi",
		"Asia/Yerevan",
		"Asia/Aqtau",
		"Asia/Bahrain",
		"Asia/Brunei",
		"Asia/Colombo",
		"Asia/Dubai",
		"Asia/Ho_Chi_Minh",
		"Asia/Jakarta",
		"Asia/Karachi",
		"Asia/Krasnoyarsk",
		"Asia/Macau",
		"Asia/Nicosia",
		"Asia/Phnom_Penh",
		"Asia/Rangoon",
		"Asia/Seoul",
		"Asia/Tbilisi",
		"Asia/Tokyo",
		"Asia/Vientiane",
		"Australia/Canberra",
		"Australia/LHI",
		"Australia/NSW",
		"Australia/Tasmania",
		"Australia/Broken_Hill",
		"Australia/Hobart",
		"Australia/North",
		"Australia/Sydney",
		"Pacific/Chuuk",
		"Pacific/Fiji",
		"Pacific/Guam",
		"Pacific/Kwajalein",
		"Pacific/Niue",
		"Pacific/Pitcairn",
		"Pacific/Saipan",
		"Pacific/Truk",
		"Pacific/Chatham",
		"Pacific/Fakaofo",
		"Pacific/Guadalcanal",
		"Pacific/Kosrae",
		"Pacific/Nauru",
		"Pacific/Palau",
		"Pacific/Rarotonga",
		"Pacific/Tongatapu",
		"Pacific/Easter",
		"Pacific/Funafuti",
		"Pacific/Honolulu",
		"Pacific/Majuro",
		"Pacific/Norfolk",
		"Pacific/Pohnpei",
		"Pacific/Samoa",
		"Pacific/Wake",
		"Antarctica/Casey",
		"Antarctica/McMurdo",
		"Antarctica/Vostok",
		"Antarctica/Davis",
		"Antarctica/Palmer",
		"Antarctica/DumontDUrville",
		"Antarctica/Rothera",
		"Antarctica/Macquarie",
		"Antarctica/South_Pole",
		"Antarctica/Mawson",
		"Antarctica/Syowa",
		"Arctic/Longyearbyen",
		"Europe/Athens",
		"Europe/Brussels",
		"Europe/Dublin",
		"Europe/Istanbul",
		"Europe/Ljubljana",
		"Europe/Mariehamn",
		"Europe/Oslo",
		"Europe/Rome",
		"Europe/Skopje",
		"Europe/Tiraspol",
		"Europe/Vilnius",
		"Europe/Zurich",
		"Europe/Belfast",
		"Europe/Bucharest",
		"Europe/Gibraltar",
		"Europe/Jersey",
		"Europe/London",
		"Europe/Minsk",
		"Europe/Paris",
		"Europe/Samara",
		"Europe/Sofia",
		"Europe/Uzhgorod",
		"Europe/Volgograd",
		"Europe/Belgrade",
		"Europe/Budapest",
		"Europe/Guernsey",
		"Europe/Kaliningrad",
		"Europe/Luxembourg",
		"Europe/Monaco",
		"Europe/Podgorica",
		"Europe/San_Marino",
		"Europe/Stockholm",
		"Europe/Vaduz",
		"Europe/Warsaw",
		"Indian/Cocos",
		"Indian/Mauritius",
		"Indian/Christmas",
		"Indian/Maldives",
		"Indian/Comoro",
		"Indian/Mayotte",
		"Australia/Brisbane",
		"Australia/Eucla",
		"Australia/Melbourne",
		"Australia/South",
		"Australia/Yancowinna",
	);
}

class Paginator {
	public function __construct($maxOnPage, $url_pattern, $id = "page", $right = false){
		$this->id = $id;
		$this->max = $maxOnPage;
		$this->url = $url_pattern; //(:page)
		$this->right = $right;
		$this->count = null;
	}

	private $symbol_left = "<";
	private $symbol_right =">"; 

	function _id(){
		return $this->id;
	}

	public function getPage(){
		return (isset($_GET[$this->_id()])?$_GET[$this->_id()]:1);
	}

	public function check(){
		if($this->getCount() > 0 && $this->getPage() > $this->getCount()){
			$_GET[$this->_id()] = $this->getCount();
			header("location:".str_replace("(:page)", $this->getCount(), $this->url));
		}		
		if($this->getPage() < 1){ 
			$_GET[$this->_id()] = 1;
		}
	}

	public function getCount(){
		return $this->count;
	}

	public function getCountPages(){
		return $this->getCount() / $this->max;
	}

	public function count(){
		return $this->count;
	}

	public function getMax(){
		return ceil($this->count / $this->max);
	}

	public function queryCount(){
		$array = func_get_args();
		$query = dibi::query($array)->fetchSingle();
		$this->count = $query;	
	}

	public function query(){		
		$array = func_get_args();	
		$from = (($this->getPage()-1)*$this->max);
		if($from < 0) $from = 0;
		$array[] = "LIMIT ".$from.", ".$this->max;			
		$this->_query_result = dibi::query($array);		
		
		if($this->count === null){
			$array = func_get_args();
			$array[0] = preg_replace("/^SELECT(.*)FROM/U", "SELECT count(*) FROM", $array[0]);	
			$this->count = dibi::query($array)->fetchSingle();
		}
		return $this->_query_result;
	}

	public function getPaginator(){
		$this->check();

		$output = "<ul class=\"paginator".($this->right?" side-right":"")."\">";

		$count = round($this->getCountPages());

		if($this->right)
			$output.= "<li class=text>".t("Total")." ".$this->getCount()."</li>";
		
		if($count > 0){
			$prev = $this->getPage() - 1; if($prev < 1) $prev = 1;
			$output.= "<li><a href=\"".str_replace("(:page)", $prev, $this->url)."\">".$this->symbol_left."</a></li>";
		}

		$offset = 3;

		if($count > $offset * 2 && $this->getPage() > $offset){
			$output.= "<li><a href=\"#\" onclick=\"page = prompt('".t("enter page number")."');window.location.href=('".$this->url."').replace('(:page)', page);\">...</a></li>";
		}

		if($count > $offset * 2){
			$start = $this->getPage() - $offset;
			$size = $this->getPage() + $offset;
			
			if($start < $offset - 2){				
				$start = 1;
				$size = $offset * 2 + 2;
			}		
			if($start + $offset * 2 - 2 > $count){
				$start = $count - ($offset * 2) - 1;
				$size = $start + $offset * 2 + 2;
			}	

			for($i = $start; $i <= $size; $i++){
				$class = "";
				if($this->getPage() == $i)
					$class = "class=\"current\"";
				$output.= "<li ".$class."><a href=\"".str_replace("(:page)", $i, $this->url)."\">".$i."</a></li>";
			}
		}else{			
			for($i = 1; $i <= $this->getMax(); $i++){
				$class = "";
				if($this->getPage() == $i)
					$class = "class=\"current\"";
				$output.= "<li ".$class."><a href=\"".str_replace("(:page)", $i, $this->url)."\">".$i."</a></li>";
			}
		}

		if($count > ($count - ($offset * 2)) && $this->getPage() < $count - $offset){
			$output.= "<li><a href=\"#\" onclick=\"page = prompt('".t("enter page number")."');window.location.href=('".$this->url."').replace('(:page)', page);\">...</a></li>";
		}	

		if($count > 0){
			$next = $this->getPage() + 1; if($next > $this->getMax()) $next = $this->getMax();
			$output.= "<li><a href=\"".str_replace("(:page)", $next, $this->url)."\">".$this->symbol_right."</a></li>";
		}

		if(!$this->right)
			$output.= "<li class=text>".t("Total")." ".$this->getCount()."</li>";			

		$output.= "</ul>";
		return $output;
	}		
}

class LinQ {
	public function __construct($array){
		$this->array = $array;
		$this->operations = array();
	}

	public function Where($condition){
		$this->operations[] = new LinQWhereOperation($condition);
		return $this;
	}

	public function OrderBy($condition){
		$this->operations[] = new LinQOrderBy($condition);
		return $this;
	}

	public function Select($condition) {
		$this->operations[] = new LinQSelectOperation($condition);
		return $this;
	}

	public function GroupBy($condition, $selector = null) {
		$this->operations[] = new LinQGroupByOperation($condition, $selector);
		return $this;
	}

	public function Take($how) {
		$array = [];
		for($i = 0; $i < $how; $i++) {
			if($i >= count($this->array)) break;
			$array[] = $this->array[$i];
		}
		$this->array = $array;
		return $this;
	}

	public function Count($condition = null){
		if($condition == null)
			return count($this->array);
			
		$i = 0;
		foreach($this->array as $i => $arr){
			$condition->bindTo($this);
			if($condition($arr, $i)){
				$i++;
			}
		}
		return $i;
	}

	public function ToArray(){
		$return_array = $this->array;
		foreach($this->operations as $op){
			$return_array = $op->compile($return_array);
		}
		
		if(gettype($return_array) == "object"){
			$return_array = json_decode(json_encode($return_array), true);
		}
		return $return_array;
	}

	public function First($condition = null){
		if($condition == null){
			return $this->ToArray()[0];
		}

		foreach($this->array as $i => $a){
			if($condition($a, $i)){
				return $a;
			}
		}
		throw new Exception("Condition not return anything");
	}

	public function FirstIndex($condition = null){	
		if($condition == null){
			return 0;
		}

		foreach($this->array as $i => $a){
			if($condition($a, $i)){
				return $i;
			}
		}
		throw new Exception("Condition not return anything");
	}

	public function FirstCombined($condition = null){
		return array("index" => $this->FirstIndex($condition), "value" => $this->First($condition));
	}

	public function FirstOrNull($condition = null){
		if($condition == null){
			if(count($this->array) == 0) return NULL;
			return $this->array[0];
		}

		foreach($this->array as $i => $a){
			if($condition($a, $i)){
				return $a;
			}
		}
		return NULL;
	}
}

interface LinqOperation {
    public function compile($array);
}

class LinQWhereOperation implements LinqOperation {
	public function __construct($condition){
		$this->condition = $condition;
	}

	public function compile($array){
		$return_array = array();
		foreach($array as $i => $a){
			$c = $this->condition->bindTo($this);
			if($c($a, $i)){
				$return_array[$i] = $a;
			}
		}
		return $return_array;
	}
}

class LinQOrderBy implements LinqOperation {
	private $condition = NULL;

	public function __construct($condition){
		$this->condition = $condition;
	}

	public function compile($array){
		$return_array = $array;
		usort($return_array, function($a, $b){
			$c = $this->condition->bindTo($this);
			return $c($a, $b);
		});
		return $return_array;
	}
}

class LinQSelectOperation implements LinqOperation {
	public function __construct($condition){
		$this->condition = $condition;
	}

	public function compile($array){
		$return_array = array();
		foreach($array as $i => $a){
			$c = $this->condition->bindTo($this);			
			$return_array[$i] = $c($a, $i);
		}
		return $return_array;
	}
}

class LinQGroupByOperation implements LinqOperation {
	public function __construct($condition, $selector){
		$this->condition = $condition;
		$this->selector = $selector;
	}

	public function compile($array){
		$return_array = array();
		foreach($array as $i => $a){
			$c = $this->condition->bindTo($this);	
			if($this->selector == null)		
				$return_array[$c($a, $i)] = $a;
			else{
				$s = $this->selector->bindTo($this);
				$return_array[$c($a, $i)] = $s($a, $i);
			}
		}
		return $return_array;
	}
}

class Http {
	public function __construct(){
		$this->curl = curl_init();
		$this->returnTransfer = true;
		$this->executed = false;
		$this->response = NULL;
		$this->expectedStatus = 200;
		$this->userpwd = NULL;
		$this->url = "";
		$this->headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/x-www-form-urlencoded"
		);
	}

	public function setReturnTransfer($state = true) {
		$this->returnTransfer = $state;
	}

	public function setAccept($accept = "application/json") {
		$this->headers["Accept"] = $accept;
	}

	/**
	 * application/json
	 * application/x-www-form-urlencoded
	 */
	public function setContentType($contentType = "application/x-www-form-urlencoded") {
		$this->headers["Content-Type"] = $contentType;		
	}

	public function setExpectedStatus($expectedStatus = 200) {
		$this->expectedStatus = $expectedStatus;
	}

	public function setAuthorization($token){
		$this->headers["Authorization"] = $token;
	}

	public function setAuthorizationBearer($token){
		$this->setAuthorization("Bearer ".$token);
	}

	public function setUserPwd($user, $pwd) {
		curl_setopt($this->curl, CURLOPT_USERPWD, $user.":".$pwd);
	}

	private function sendRequest($url, $data, $contentLength, $requestType) {
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, $this->returnTransfer);
		curl_setopt($this->curl, CURLOPT_URL, $url);
		if($data != NULL)
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $requestType);		
		if($contentLength > 0)
			$this->headers["Content-Length"] = $contentLength;
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());

		$this->url = $url;
	}

	public function post_json($url, $json = null){
		$this->headers["Content-Type"] = "application/json";
		$this->sendRequest($url, $json, strlen($json), "POST");
	}

	private function addQuery($url, $name, $value = "") {
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

	public function post_query($url, $query, $data = "") {
		$json = json_encode($data);
		foreach($query as $key => $value) {
			$url = $this->addQuery($url, $key, $value);
		}
		$this->sendRequest($url, $json, strlen($json), "POST");
	}

	public function post($url, $data = []){
		$data_query = [];
		foreach($data as $key => $value) {
			$data_query[] = $key."=".urlencode($value);
		}
		$result_query = implode("&", $data_query);
		$this->sendRequest($url, $result_query, strlen($result_query), "POST");
	}

	public function get_json($url){
		$this->headers["Content-Type"] = "application/json";
		$this->sendRequest($url, null, 0, "GET");
	}

	public function get($url){
		$this->sendRequest($url, null, 0, "GET");
	}

	public function getHeaders(){
		$headers = [];
		foreach($this->headers as $key => $value) {
			$headers[] = $key.": ".$value;
		}
		return $headers;
	}

	public function exec() {
		$this->response = curl_exec($this->curl);
		$this->executed = true;
	}

	public function getUrl(){
		return $this->url;
	}

	public function getResponse($forseJsonEncode = false) {
		if(!$this->executed) return false;

		if($forseJsonEncode || $this->headers["Accept"] == "application/json")
			return json_decode($this->response, true);

		return $this->response;
	}

	public function getResponseStatusCode() {
		if(!$this->executed) return false;

		return $this->response->code;
	}

	public function isError(){
		if(!$this->executed) return false;

		if (!$this->response || !isset($this->response->code) || $this->response->code !== $this->expectedStatus) {
			return true;
		}

		return false;
	}
}

/** @deprecated */
function getData_($url, $data, &$nurl){
	$cSession = curl_init();
	curl_setopt($cSession, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($cSession, CURLOPT_VERBOSE, true);
	curl_setopt($cSession, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cSession, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($cSession, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt($cSession, CURLOPT_POST, count($data));
    //curl_setopt($cSession, CURLOPT_POSTFIELDS, $data);
	curl_setopt($cSession, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
	curl_setopt($cSession, CURLOPT_URL,$url);
	curl_setopt($cSession, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($cSession, CURLOPT_HEADER, false);
	$result=curl_exec($cSession);
	$nurl = curl_getinfo($cSession, CURLINFO_EFFECTIVE_URL);
	curl_close($cSession);	
	return $result;
}

class DataTableLike {
	public static $BOTH = 0;
	public static $LEFT = 1;
	public static $RIGHT = 2;
}
class DataTable {
	private $table = "";
	private $where = [];
	private $items = [];
	private $orderBy = "id";
	private $pageLimit = 15;
	private $currentPage = 1;
	private $sql = [];
	private $dibiSql = [];

	public function __construct($table = ""){
		$this->table = $table;
	}

	public function table($name) {
		$this->table = $name;
		return $this;
	}

	public function where(){
		$this->where[] = array("type" => "AND", "value" => func_get_args());
		return $this;
	}

	public function whereOr(){
		$this->where[] = array("type" => "OR", "value" => func_get_args());
		return $this;
	}

	/**
	 * DataTableLike::$BOTH = 0
	 * DataTableLike::$LEFT = 1
	 * DataTableLike::$RIGHT = 2
	 */
	public function like($column, $value, $type = 0, $isOr = false) {
		//"_to LIKE %~like~", $filter["receiver"]
		$like = "%~like~";
		if($type == DataTableLike::$LEFT) $like = "%~like";
		else if($type == DataTableLike::$RIGHT) $like = "%like~";

		if($isOr)
			$this->whereOr($column." LIKE ".$like, $value);
		else
			$this->where($column." LIKE ".$like, $value);

		return $this;
	}

	public function likeOr($column, $value, $type = 0) {
		return $this->like($column, $value, $type, true);
	}

	public function count(){
		$result = dibi::query($this->buildSql(true))->fetchSingle();
		$this->dibiSql["count"] = dibi::$sql;
		return $result;
	}

	public function fetch(){
		$result = dibi::query($this->buildSql(false));
		$this->dibiSql["query"] = dibi::$sql;
		return $result;
	}

	public function order($value) {
		$this->orderBy = $value;
		return $this;
	}

	public function limit($value) {
		$this->pageLimit = $value;
		return $this;
	}

	public function page($value) {
		$this->currentPage = $value;
		return $this;
	}

	public function getSql($isCount = false, $isDibi = true) {
		if($isCount) {
			if($isDibi) {
				return $this->dibiSql["count"];
			}else{
				return $this->sql["count"];
			}
		}

		if($isDibi) {
			return $this->dibiSql["query"];
		}else{
			return $this->sql["query"];
		}
	}

	private function buildSql($isCount = false){
		$sql = [];
		if($isCount) {
			$sql = array("SELECT count(*)");
		}else{
			if(count($this->items) == 0) 
				$sql = array("SELECT * ");
			else
				throw "Not implemented";
		}

		$sql = array_merge($sql, array("FROM :prefix:".$this->table));

		$first = true;
		foreach($this->where as $key => $where) {
			$append = "";
			if($first) {
				$first = false;	
				$sql = array_merge($sql, array("WHERE"));			
			}else{
				$append = $where["type"];
			}

			$sql = array_merge($sql, array($append), $where["value"]);
		}

		if(!$isCount){
			$sql = array_merge($sql, array("ORDER BY ", $this->orderBy ," LIMIT ", $this->pageLimit, " OFFSET ", ($this->currentPage - 1) * $this->pageLimit));
		}

		if($isCount) {
			$this->sql["count"] = $sql;
		}else{
			$this->sql["query"] = $sql;
		}

		return $sql;
	}
}