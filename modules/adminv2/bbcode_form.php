<?php
global $t;
$result = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $formID)->fetch();
if($result == NULL){
	$output = "Formulář ".$formID." neexistuje!";
}else{
	global $errors_input, $customFormHook;
	if(!isset($errors_input))
		$errors_input = null;
	if(!isset($customFormHook))
		$customFormHook=false;
		
	$output = "";
	$datf = Config::sload($result["data"]);
	if($datf["enable"] != 1 and !$customFormHook){
		$output.="<b>Formulář byl zakázán!</b>";
	}else{
		$input = null;
		$submit = "";
		$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $formID, " ORDER BY position");
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
			if($row["type"] == "submit"){
				if(isset($_POST["form_input_".$row["id"]])){
					$submit = $row["name"];
				}
			}else if($row["type"] == "text"){
				// text
			}else{
				$data = Config::sload($row["data"]);
				if(!isset($data["state"])) $data["state"] = 0;
				if(!isset($data["custom"])) $data["custom"] = 0;
				if(!isset($data["asemail"])) $data["asemail"] = 0;
				
				$input[$i] = array(array("form_input_".$row["id"],$row["id"], $row["name"]), $row["type"], $data["state"]);
				if($row["type"] == "textbox" or $row["type"] == "password" or $row["type"] == "textarea"){
					if($data["state"] == "2"){
						$_POST["form_input_".$row["id"]] = preg_replace_callback('/\{#(.*?)\}/U',function ($matches) use($fvars) {
							return (isset($fvars[$matches[1]])?$fvars[$matches[1]]:"null");
						}, $row["value"]);
					}
					if($data["asemail"] == 1 and isset($_POST["form_input_".$row["id"]])){
						if(!Utilities::isEmail($_POST["form_input_".$row["id"]]))
							$errors_input[$row["id"]] = 2;
					}
					if(isset($_POST["form_input_".$row["id"]])){
						if($row["type"] == "password" && $customFormHook)
							$input[$i][3] = strlen($_POST["form_input_".$row["id"]]);
						else
							$input[$i][3] = $_POST["form_input_".$row["id"]];
					}
				}else if($row["type"] == "upload"){
					if(!isset($_FILES["form_input_".$row["id"]])){
						//$errors_input[$row["id"]] = t("you must upload file");
					}
					else if(number_format($_FILES["form_input_".$row["id"]]["size"] / $data["maxsize"], 2) > 1){
						$errors_input[$row["id"]] = t("file can have only")." ".Utilities::convertBtoMB($data["maxsize"])." MB";
					}
					else if($_FILES["form_input_".$row["id"]]["error"] == 0){						
						$allowed = (preg_match_all('/[^, ]+/', $data["allowed"], $out)? $out[0]: array('gif','png','jpg')); //array('gif','png' ,'jpg');
						$filename = $_FILES["form_input_".$row["id"]]['name'];
						$ext = pathinfo($filename, PATHINFO_EXTENSION);
						if(!in_array($ext,$allowed)) {
							$errors_input[$row["id"]] = t("file type can be only one of this").": ".implode(", ", $allowed);
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
									$errors_input[$row["id"]] = t("you must upload file");
								break;
							case UPLOAD_ERR_INI_SIZE:
							case UPLOAD_ERR_FORM_SIZE:
								$errors_input[$row["id"]] = t('exceeded filesize limit on server');
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
						if($data["state"] == "1" and $_POST["form_input_".$row["id"]] == ""){ $errors_input[$row["id"]] = 1; }
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
									$result__ = dibi::query('SELECT * FROM :prefix:form_answer WHERE parent = %i', $formID, " ORDER BY id DESC");
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
											$errors_input[$row["id"]] = t("capacity has already been reached");
										}
									}else
										$errors_input[$row["id"]] = t("you can not select this, because you need permision same or above")." ".$perm["name"];
								}	
							}
						}
					}else{
						if($data["state"] == "2"){ $errors_input[$row["id"]] = 1; }
					}
				}else if($row["type"] == "variable" && $submit != ""){
					$act = 0;$old = $row["value"];$stp = false;
					$arr = explode(",", $data["list"]);
					
					if($data["next"] == 1){
						$row["value"]+=1;
					}else if($data["next"] == 2){
						$row["value"]-=1;
					}else if($data["next"] == 3){
						if(isset($arr[$row["value"]+1])) $row["value"]+=1;
						else{ $row["value"] = 0; $act = 1; }
					}
					
					if($data["stop"] == 1){
						if($act == 1){ $row["value"] = $old;$stp = true; }
					}else if($data["stop"] == 2){
						if($row["value"] >= $data["stopat"]){
							$row["value"] = $old;
							$stp = true;
						}
					}
					
					if($stp && $data["closeatstop"] == 1){
						$q = dibi::query('UPDATE :prefix:form SET ', array("enable" => 0), 'WHERE `id`=%s', $formID);
					}
					$q = dibi::query('UPDATE :prefix:form_items SET ', array("value" => $row["value"]), 'WHERE `id`=%s', $row["id"]);		
					$input[$i][3] = $row["value"];
				}else if($row["type"] == "recaptcha"){
					if(isset($_POST["g-recaptcha-response"])){
						$data = array(
							'secret' => $th->root->config->get("recaptcha-secret-key"),
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
							$errors_input[$row["id"]] = "Recaptcha validation failed!";
						}
					}elseif($submit != ""){
						$errors_input[$row["id"]] = 1;
					}
				}elseif($row["type"] == "select"){
					if($data["types"] == "1" or $data["types"] == "4"){
						$input[$i][3] = "";
						if(isset($_POST["form_input_".$row["id"]]))
							$input[$i][3] = $_POST["form_input_".$row["id"]];
						if($input[$i][3] == "custom" and $_POST["form_input_".$row["id"]."_custom_val"]!="" and $data["custom"] == 1){
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
					if($input[$a][3] != ""){  }
					else { if(!isset($errors_input[$input[$a][0][1]])){ $errors_input[$input[$a][0][1]] = 1; } }  
				}
			}
		}
		
		$show = true;
		
		$errorshowed = false;
		if(!isset($datf["onetime"])){ $datf["onetime"] = 0; }
		if($datf["onetime"] == 1){
			if(User::current() == false){
				$disabledall = true;
				//$output.="<div class='box error'>".t("please log in to fill out the form")."</div>";
				$errorshowed = true;
				$submit = "";
			}else{
				$re = dibi::query('SELECT * FROM :prefix:form_answer WHERE `parent`=%s', $formID, " AND user=%s", User::current()["id"]);
				if($re->count() > 0){
					$disabledall = true;
					//$output.="<div class='box error'>".t("this form can only be filled in once!")."</div>";
					$errorshowed = true;
					$submit = "";
				}
			}
		}
		if(!$errorshowed){
			if($errors_input != null){
				if(!$customFormHook)
					$output.="<div class='box error'>".t("Please fill all required fields!")."</div>";
			}elseif($submit != ""){
				$pole = null;
				for($a=0;$a<$i;$a++){
					if(!isset($input[$a][3])) $input[$a][3] = 0;
					$pole[$a] = array($input[$a][0][2], $input[$a][1], $input[$a][3], $input[$a][0][1]);
				}
				//$b = get_browser($_SERVER['HTTP_USER_AGENT'], true);
				$b = null;
				$data = array(
						"parent" 	=> $formID,
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
					if($datf["redirect"] != "")
						header("location:".$datf["redirect"]);
					else{
						$output.="<a name='_form_id_".$formID."'></a>";
						if($datf["succme"] != ""){
							$datf["succme"] = preg_replace_callback('/\{#(.*?)\}/U',function ($matches) use($fvars) {
								return (isset($fvars[$matches[1]])?$fvars[$matches[1]]:"null");
							}, $datf["succme"]);
							$output.="<div class='box ok'>".str_replace("\r\n", "<br>", $datf["succme"]."</div>");
						}else{
							$output.="<div class='box ok'>".t("the form was sent")."</div>";
						}
					}
				}
			}
		}
		
		$ismultimedia = false;
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
						$ismultimedia = true;
					}
				}
			}
			
			$disabledall = false;			
			if($datf["onetime"] == 1){
				if(User::current() == false){
					$disabledall = true;
					$output.="<div class='box error'>".t("please log in to fill out the form")."</div>";
				}else{
					$re = dibi::query('SELECT * FROM :prefix:form_answer WHERE `parent`=%s', $formID, " AND user=%s", User::current()["id"]);
					if($re->count() > 0){
						$disabledall = true;
						$output.="<div class='box error'>".t("this form can only be filled in once!")."</div>";
					}
				}
			}			
			
			$outputjs = "";
			$widthform = $datf["width"];
			$output.="<a name='_form_id_".$formID."'></a>";
			$inputstype = array();
			$output.="<form action='#_form_id_".$formID."' class='web-form' method=post ".($ismultimedia?"enctype=\"multipart/form-data\"":"")." style='".($widthform!=""?"width:".$widthform."px;":"").";'>";
				//$output.="<div class=\"form-group\">";
				$result_ = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $formID, " ORDER BY position");
				foreach ($result_ as $n => $row) {
					$data = Config::sload($row["data"]);
					if($row["type"] == "variable") continue;
					$inputstype[$row["id"]] = $row["type"];

					$output.="<div class='form-group row mb-2' id='form_input_".$row["id"]."_html'>";
					$requie = false;
					$hide = false;
					if($row["type"] == "textbox" or $row["type"] == "password" or $row["type"] == "textarea" or $row["type"] == "select" or $row["type"] == "picker" or $row["type"] == "upload"){
						if($data["state"] == "1"){ 
							$row["name"].="<span class=requier id='form_input_".$row["id"]."_requie'>*</span>"; 
							$requie = true;
						}elseif($data["state"]=="3"){
							$hide = true;
						}
					}else if($row["type"] == "recaptcha"){
						$row["name"].="<span class=requier id='form_input_".$row["id"]."_requie'>*</span>"; 
						$requie = true;
					}			

					if(!$requie && $row["type"] != "submit"){
						$row["name"].="<span class=requier id='form_input_".$row["id"]."_requie' style='display:none;'>*</span>"; 
					}
					
					if($row["type"] == "textbox" or $row["type"] == "password"){	
						$jsmesempai = false;
						$jsout = "";
						$evalmesemptai = false;
						$jsmesempai = true;
						$data["customvalue"] = trim($data["customvalue"]);
						if(isset($data["customvalue"]) and $data["customvalue"] != "" and $data["customvalue"] != null){							
							foreach(json_decode($data["customvalue"], true) as $q => $d){
								$name = "";
								if($inputstype)
								if($d["type"] == "set value"){
									if(!isset($d["eval"])) $d["eval"] = 0;
									if($d["eval"] == "1") $evalmesemptai = true;
									$row["value"] = get_js($d["data"], $fvars,$jsout,$row,$jsmesempai,$ftype,$d);
									if($evalmesemptai)
										$outputjs.="if(!isNotAccesableField('#form_input_".$row["id"]."') && firts_evals){\$('#form_input_".$row["id"]."').val(eval(".$row["value"]."));}";
									else
										$outputjs.="if(!isNotAccesableField('#form_input_".$row["id"]."') && firts_evals){\$('#form_input_".$row["id"]."').val(\"".$row["value"]."\");}";
									$row["value"] = "";
								}else if($d["type"] == "when"){
									$mif = "if((!isNotAccesableField('#form_input_".$row["id"]."') || firts_evals) && ".js_to_php($d["val1"], $fvars,$jsout,$row,$jsmesempai,$ftype,$d)." ".$d["matchhow"]." ".js_to_php($d["val2"], $fvars,$jsout,$row,$jsmesempai,$ftype,$d)."){";
									if(isset($d["targetid"])){
										$qid = explode(":", $d["targetid"]);
										$qid = substr($qid[1], 0, strlen($qid[1])-1);
									}
									if($d["matchakce"] == "set value"){									
										$outputjs.= $mif."$('#form_input_".$row["id"]."').val('".$d["valuexx"]."');};";
									}
									else if($d["matchakce"] == "increase"){									
										$outputjs.= $mif."$('#form_input_".$row["id"]."').val(parseInt($('#form_input_".$row["id"]."').val())+parseInt('".js_to_php($d["valuexx"], $fvars,$jsout,$row,$jsmesempai,$ftype,$d)."'));};";
									}
									else if($d["matchakce"] == "decrease"){									
										$outputjs.= $mif."$('#form_input_".$row["id"]."').val(parseInt($('#form_input_".$row["id"]."').val())-parseInt('".js_to_php($d["valuexx"], $fvars,$jsout,$row,$jsmesempai,$ftype,$d)."'));};";
									}
									else if($d["matchakce"] == "show"){
										$outputjs.= "$('#form_input_".$qid."_html').hide();".$mif."$('#form_input_".$qid."_html').show();};";						
									}	
									else if($d["matchakce"] == "hide"){
										$outputjs.= "$('#form_input_".$qid."_html').show();".$mif."$('#form_input_".$qid."_html').hide();};";						
									}
									else if($d["matchakce"] == "disable"){
										$outputjs.= "$('#form_input_".$qid."').prop('disabled', false);".$mif."$('#form_input_".$qid."').prop('disabled', true);};";						
									}
									else if($d["matchakce"] == "require"){
										$outputjs.= "$('#form_input_".$qid."_requie').hide();".$mif."$('#form_input_".$qid."_requie').show();};";						
									}														
								}
							}	
						}				
					}
					
					$row["value"] = preg_replace_callback('/\{#(.*?)\}/U',function ($matches) use($fvars) {
						return (isset($fvars[$matches[1]])?$fvars[$matches[1]]:"null");
					}, $row["value"]);
						
					if($hide){
						$output.="<div class=\"col-sm-12\">";
					}else if($row["type"] == "submit"){
						$output.="<div class=\"col-sm-3\"></div><div class=\"col-sm-10\">";
					}else if($data["position"] == 1){
						$output.="<label for=\"input_".$row["id"]."\" class='col-sm-3 col-form-label'>".$row["name"]."</label><div class=\"col-sm-9\">";
					}elseif($data["position"] == 2){
						$output.="<label for=\"input_".$row["id"]."\" class='col-sm-12 col-form-label'>".$row["name"]."</label><div class=\"col-sm-12\">";
					}else{
						$output.="<div class=\"col-sm-12\">";
					}
					
					if($row["type"] == "text"){
						$output.="<span class='".$data["cssclass"]."'>".$row["value"]."</span>";
					}elseif($row["type"] == "upload"){
						$output.="<input type='file' name='form_input_".$row["id"]."'>";
					}elseif($row["type"] == "textbox"){
						$output.="<input type=text name='form_input_".$row["id"]."' onKeyUp=\"evaluate_form_".$formID."();\" id='form_input_".$row["id"]."' style='".($data["state"]=="3"?"display:none;":"")."' value='".(isset($_POST["form_input_".$row["id"]])? $_POST["form_input_".$row["id"]]: $row["value"])."' placeholder='".$data["placeholder"]."' ".(($data["state"]=="2" or $disabledall)?"disabled":"")." class='form-control ".$data["cssclass"]." ".(isset($errors_input[$row["id"]]) ? "is-invalid": "")."'>";
					}elseif($row["type"] == "recaptcha"){
						$output.='<div class="recaptcha'.(isset($errors_input[$row["id"]])?" is-invalid":"").($data["cssclass"]!=""?" ".$data["cssclass"]."":"").'"><div class="g-recaptcha" data-sitekey="'.$th->root->config->get("recaptcha-key").'"></div></div>';
					}elseif($row["type"] == "password"){
						$output.="<input type=password name='form_input_".$row["id"]."' onKeyUp=\"evaluate_form_".$formID."();\" style='".($data["state"]=="3"?"display:none;":"")."' value='".$row["value"]."' placeholder='".$data["placeholder"]."' ".(($data["state"]=="2" or $disabledall)?"disabled":"")." class='form-control ".$data["cssclass"]." ".(isset($errors_input[$row["id"]]) ? "is-invalid": "")."'>";
					}elseif($row["type"] == "textarea"){
						$output.="<textarea name='form_input_".$row["id"]."' onKeyUp=\"evaluate_form_".$formID."();\" style='".($data["state"]=="3"?"display:none;":"")."' placeholder='".$data["placeholder"]."' ".(($data["state"]=="2" or $disabledall)?"disabled":"")." class='form-control ".$data["cssclass"]." ".(isset($errors_input[$row["id"]]) ? "is-invalid": "")."' rows='".$data["rows"]."'>".$row["value"]."</textarea>";
					}elseif($row["type"] == "submit"){
						$output.="<input type=submit name='form_input_".$row["id"]."' id='form_input_".$row["id"]."' style='' value='".$row["name"]."' ".($disabledall?"disabled":"")." class='btn btn-primary ".$data["cssclass"]."'>";
					}elseif($row["type"] == "picker"){
						$selected__ = array();
						$result__ = dibi::query('SELECT * FROM :prefix:form_answer WHERE parent = %i', $formID, " ORDER BY id DESC");
						foreach ($result__ as $_n => $_row) {
							$_data = Config::sload($_row["data"]);
							for($i=0;$i<count($_data);$i++){
								$dtq = $_data[$i];
								if($dtq[3] == $row["id"]){
									$mdq = explode("[;", $dtq[2]);
									if(!isset($selected__[$mdq[0]])){ $selected__[$mdq[0]] = 1; }
									else{ $selected__[$mdq[0]]+=1; }
								}
							}
						}
						$output.="<div class='picker'><input type=hidden id='form_input_".$row["id"]."' name='form_input_".$row["id"]."'>";
						$items = explode("[;", $data["items"]);
						for($l=0;$l<count($items);$l++){	
							if(!isset($selected__[$l])){ $selected__[$l] = 0; } 
							$dtvl = explode("[,", $items[$l]);
							$perm = User::permission($dtvl[2]);
							$leftc = -1;
							$left = "";
							if($dtvl[3] != 0){
								$left = t("lefts").": ".($dtvl[3] - $selected__[$l]);
								$leftc = $dtvl[3] - $selected__[$l];
							}
							if($data["displayas"]=="row"){
								if($disabledall)
									$output.="<div class='itempick disabled'><span style='color:".$perm["color"]."'>".$dtvl[0]."</span> <span class=desc>".$dtvl[1]."</span></div>";
								else if(User::permission(User::current()["permission"])["level"] >= $perm["level"])
									$output.="<div onClick=\"$('#form_input_".$row["id"]."').val('".$l."[;".$items[$l]."');showInfo(this, '".$row["id"]."', '#picker_".$row["id"]."_info', '<div class=rightmin>".$left."</div><b>".t("selected")."</b><br>".$dtvl[0]."<br>".$dtvl[1]."');evaluate_form_".$formID."();\" class='itempick".($leftc == 0?" disabled":"")."' style=''><span style='color:".$perm["color"]."'>".$dtvl[0]."</span> <span class=desc>".$dtvl[1]."</span></div>";
								else
									$output.="<div onClick=\"$('#form_input_".$row["id"]."').val('');showInfo(this, '".$row["id"]."', '#picker_".$row["id"]."_info', '<b class=error>".t("you can not select this, because you need permision same or above")." ".$perm["name"]."</b>');evaluate_form_".$formID."();\" class='itempick".($leftc == 0?" disabled":"")."' style='width:ˇ100%;'><span style='color:".$perm["color"]."'>".$dtvl[0]."</span> <span class=desc>".$dtvl[1]."</span></div>";
							}else{
								if($disabledall)
									$output.="<div class='itempick box disabled' style='height:".$data["size"].";width:".$data["size"].";float:left;' title='".$dtvl[1]."'><span class=text style='color:".$perm["color"]."'>".$dtvl[0]."</span></div>";
								else if(User::permission(User::current()["permission"])["level"] >= $perm["level"])
									$output.="<div onClick=\"$('#form_input_".$row["id"]."').val('".$l."[;".$items[$l]."');showInfo(this, '".$row["id"]."', '#picker_".$row["id"]."_info', '<div class=rightmin>".$left."</div><b>".t("selected")."</b><br>".$dtvl[0]."<br>".$dtvl[1]."');evaluate_form_".$formID."();\" class='itempick box".($leftc == 0?" disabled":"")."' style='height:".$data["size"].";width:".$data["size"].";float:left;' title='".$dtvl[1]."'><span class=text style='color:".$perm["color"]."'>".$dtvl[0]."</span></div>";
								else
									$output.="<div onClick=\"$('#form_input_".$row["id"]."').val('');showInfo(this, '".$row["id"]."', '#picker_".$row["id"]."_info', '<b class=error>".t("you can not select this, because you need permision same or above")." ".$perm["name"]."</b>');evaluate_form_".$formID."();\" class='itempick box".($leftc == 0?" disabled":"")."' style='height:".$data["size"].";width:".$data["size"].";float:left;' title='".$dtvl[1]."'><span class=text style='color:".$perm["color"]."'>".$dtvl[0]."</span></div>";
							}
						}
						$output.="<div style='clear:both;'></div><div style='display:none;' class='infobox' id='picker_".$row["id"]."_info'></div>";
						$output.="</div>";
					}elseif($row["type"] == "select"){
						if($data["types"] == 1 or $data["types"] == 2){
							$output.="<select id='form_input_".$row["id"]."' onChange=\"if(this.value=='custom'){\$('#form_input_".$row["id"]."_custom_val').show();\$('#form_input_".$row["id"]."_custom_val').focus();}else{\$('#form_input_".$row["id"]."_custom_val').hide();};evaluate_form_".$formID."();\" ".($data["types"] == 2?"multiple":"")." name='form_input_".$row["id"]."".($data["types"] == 2?"[]":"")."' style='".($data["state"]=="3"?"display:none;":"")."' ".($data["state"]=="2" or $disabledall?"disabled":"")." class='form-control ".$data["cssclass"]."'>";
								$items = explode("[;", $data["items"]);
								for($l=0;$l<count($items);$l++){
									$dtvl = explode("[,", $items[$l]);
									$output.="<option value='".($dtvl[1] == ""?$dtvl[0]:$dtvl[1])."' ".($dtvl[2] == 1?"selected":"").">".$dtvl[0]."</option>";
								}
								if($data["custom"] == 1){
									$output.="<option value='custom'>Vlastní...</option>";
								}
							$output.="</select>";
						}elseif($data["types"] == 3){
							$items = explode("[;", $data["items"]);
							for($l=0;$l<count($items);$l++){
								$dtvl = explode("[,", $items[$l]);
								$output.="<span class='itemsel form-check'><input onChange=\"evaluate_form_".$formID."();\" type=checkbox name='form_input_".$row["id"]."_".$l."' ".(($data["state"]=="2" or $disabledall)?"disabled":"")." id='form_input_".$row["id"]."_".$l."' value='".($dtvl[1] == ""?$dtvl[0]:$dtvl[1])."' ".($dtvl[2] == 1?"checked":"")." class='form-check-input'><label for='form_input_".$row["id"]."_".$l."'>".$dtvl[0]."</label> ";
								if($data["place"] == "2") $output.="<br>";
								$output.="</span>";
							}
							if($data["custom"] == 1){
								$output.="<span class='itemsel form-check'><input class='form-check-input' onChange=\"if(\$(this).is(':checked')){\$('#form_input_".$row["id"]."_custom_val').show();\$('#form_input_".$row["id"]."_custom_val').focus();}else{\$('#form_input_".$row["id"]."_custom_val').hide();};evaluate_form_".$formID."();\" type=checkbox name='form_input_".$row["id"]."_custom' id='form_input_".$row["id"]."_custom' value='custom' ".($data["state"]=="2" or $disabledall?"disabled":"")."><label for='form_input_".$row["id"]."_custom'>Vlastní...</label></span>";
							}
						}elseif($data["types"] == 4){
							$items = explode("[;", $data["items"]);
							for($l=0;$l<count($items);$l++){
								$dtvl = explode("[,", $items[$l]);
								$output.="<span class='itemsel form-check'><input class='form-check-input' type=radio onChange=\"\$('#form_input_".$row["id"]."_custom_val').hide();evaluate_form_".$formID."();\" name='form_input_".$row["id"]."' id='form_input_".$row["id"]."_".$l."' value='".($dtvl[1] == ""?$dtvl[0]:$dtvl[1])."' ".($dtvl[2] == 1?"checked":"")." ".(($data["state"]=="2" or $disabledall)?"disabled":"")."><label for='form_input_".$row["id"]."_".$l."'>".$dtvl[0]."</label> ";
								if($data["place"] == "2") $output.="<br>";
								$output.="</span>";
							}
							if($data["custom"] == 1){
								$output.="<span class='itemsel form-check'><input class='form-check-input' onChange=\"\$('#form_input_".$row["id"]."_custom_val').show();\$('#form_input_".$row["id"]."_custom_val').focus();evaluate_form_".$formID."();\" type=radio name='form_input_".$row["id"]."' id='form_input_".$row["id"]."_custom' value='custom' ".(($data["state"]=="2" or $disabledall)?"disabled":"")."><label for='form_input_".$row["id"]."_custom'>Vlastní...</label></span>";
							}
						}
						$output.="<div class=custom_value_div><input type=text id='form_input_".$row["id"]."_custom_val' name='form_input_".$row["id"]."_custom_val' style='display:none;' value='' placeholder='".t("custom value")."...' onKeyUp=\"evaluate_form_".$formID."();\" class='custom_val form-control'></div>";
					}
					if(isset($errors_input[$row["id"]])){
						if($errors_input[$row["id"]] == 2)
							$output.="<div class='inpoerror'>".t("Please enter a valid email!")."</div>";
						elseif($errors_input[$row["id"]] == 1 or $errors_input[$row["id"]] === true)
							$output.="<div class='inpoerror'>".t("This field is required!")."</div>";
						else
							$output.="<div class='inpoerror' style='".($row["type"] == "recaptcha"?"width: 302px;":"")."'>".$errors_input[$row["id"]]."</div>";
					}
					
					$output.="</div>";
					$output.="</div>";
				}			
				//$output.="</div>";
			$output.="</form>";	
			$output.="<script>var firts_evals = true;function evaluate_form_".$formID."(){ ".$outputjs." } $(function(){evaluate_form_".$formID."(); firts_evals = false;});</script>";
		}
	}
}

				function get_js($text,$fvars,&$jsout,$row,&$jsmesempai,$ftype,$d){
					$text = preg_replace_callback('/\{(.*?):(.*?)\}/U',function ($matches) use($fvars,&$jsout,$row,&$jsmesempai,$ftype,$d) {
						if($matches[1] == "default"){
							if($matches[2] == "userid"){ return User::current()["id"]; }
							elseif($matches[2] == "actual_date"){ return Date(Utilities::getTimeFormat(), Time()); }
							elseif($matches[2] == "ip"){ return Utilities::ip(); }
							elseif($matches[2] == "nick"){ return User::current()["nick"]; }
							elseif($matches[2] == "email"){ return User::current()["email"]; }
							elseif($matches[2] == "perm"){ return User::permission(User::current()["permission"])["name"]; }
						}else{									
							if(isset($ftype[$matches[2]])){
								if($ftype[$matches[2]] == "picker"){
									if($d["eval"] == "1")
										return "($('#form_input_".$matches[2]."').val()==''?'':$('#form_input_".$matches[2]."').val().split(\"[,\")[0].split(\"[;\")[0])";
									else
										return "\"+($('#form_input_".$matches[2]."').val()==''?'':$('#form_input_".$matches[2]."').val().split(\"[,\")[0].split(\"[;\")[0])+\"";
								}else{
									if($d["eval"] == "1")
										return "$('#form_input_".$matches[2]."').val()";
									else
										return "\"+$('#form_input_".$matches[2]."').val()+\"";
								}
							}else{
								return $matches[0];
							}
						}
						return $matches[0];
					}, $text);
					return $text;
				}
				function js_to_php($text,$fvars,&$jsout,$row,&$jsmesempai,$ftype,$d){
					$nosp = false;
					$text = preg_replace_callback('/\{(.*?):(.*?)\}/U',function ($matches) use($fvars,&$jsout,$row,&$jsmesempai,$ftype,$d,&$nosp) {
						if($matches[1] == "default"){
							if($matches[2] == "userid"){ return User::current()["id"]; }
							elseif($matches[2] == "actual_date"){ return Date(Utilities::getTimeFormat(), Time()); }
							elseif($matches[2] == "ip"){ return Utilities::ip(); }
							elseif($matches[2] == "nick"){ return User::current()["nick"]; }
							elseif($matches[2] == "email"){ return User::current()["email"]; }
							elseif($matches[2] == "perm"){ return User::permission(User::current()["permission"])["name"]; }
						}else{									
							if(isset($ftype[$matches[2]])){
								$nosp = true;
								if($ftype[$matches[2]] == "picker"){
									return "($('#form_input_".$matches[2]."').val()==''?'':$('#form_input_".$matches[2]."').val().split(\"[,\")[0].split(\"[;\")[0])";
								}else if($ftype[$matches[2]] == "select"){
									return "$('input[name=\"form_input_".$matches[2]."\"]:checked').val()";
								}else{
									return "$('#form_input_".$matches[2]."').val()";
								}															
							}else{								
								if(is_numeric($matches[0]))
									return $matches[0];
								else
									return $matches[0];
							}
						}
						return $matches[0];
					}, $text);
					
					if(is_numeric($text) or $nosp)
						return $text;
					else
						return "\"".$text."\"";
				}
?>