<?php
class Page {
	public $title = "Title of your page";
	public $styles = null;
	public $script = null;
	public $config = null;

	private $root;

	public function __construct($root){
		$this->root = $root;
		$this->config = array(
						"Autor" 		=> $root->config->get("autor"),
						"Description" 	=> $root->config->get("description"),
						"Charset" 		=> "UTF-8",
						"Title" 		=> $root->config->get("title"),
						"Keywords" 		=> $root->config->get("keywords")
					);
		$this->title = $root->config->get("title_head");
		$this->add_script($root->router->url . "include/jquery-1.11.2.min.js");
		$this->add_script($root->router->url . "include/jquery-ui.min.js", false);
		$this->add_script($root->router->url . "include/jquery.form.js", false);
		$this->add_script($root->router->url . "include/js.cookie.js");
		$this->add_script($root->router->url . "include/jquery.ui.touch-punch.min.js", false);
		$this->add_script($root->router->url . "include/snowfox.js", false);
		$this->add_script($root->router->url . "include/dropzone.js", false); // toto se bude rušit!
		$this->add_script($root->router->url . "include/jquery.ui.sortable-animation.js", false);
		//$this->add_script($root->router->url . "include/fontawesome/fontawesome-all.min.js", false);
		$this->add_style($root->router->url  . "include/fontawesome/fontawesome-all.min.css");		
		$this->add_style($root->router->url  . "include/style.css");	

		$root->getContainer()->set('page', $this);

		$this->root->module_manager->hook_register("page.format", "default_page_formater", 0);
	}

	function default_page_formater($t, &$output){
		$output = preg_replace ('/\[b\](.*?)\[\/b\]/U', '<b>$1</b>', $output);
		$output = preg_replace ('/\[i\](.*?)\[\/i\]/U', '<i>$1</i>', $output);
		$output = preg_replace ('/\[u\](.*?)\[\/u\]/U', '<u>$1</u>', $output);
		$output = preg_replace ('/\>\r\n\</U', '><', $output);
		$output = preg_replace ('/\n/U', '<br>', $output);
		//$output = nl2br($output);
		//$output = preg_replace_callback('/\[form id=\"(.*?)\"\]/U','_LoadForm', $output);
		$output = preg_replace_callback('/\[form id=\"(.*?)\"\]/U', function ($matches) use($t) {
				return Page::LoadForm($t->root, $matches[1]);
			}, $output);
		$output = preg_replace_callback('/(\s|^)(www\.|https?:\/\/)?[a-z0-9]+\.[a-z0-9]{2,4}\S*/m',function ($matches) {
				return "<a href='".trim($matches[0])."' target=_blank>".trim($matches[0])."</a>";
			}, $output);
	}

	public static function FormGetVariables($formId) {
		$formItems = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $formId, " ORDER BY position");
		$fvars = [];

		foreach ($formItems as $n => $row) {
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
				//submit button
			}else{
				$fvars[$row["name"]] = $row["value"]; //all others default values
			}
		}

		return $fvars;
	}

	public static function LoadForm($root, $formId, $customFormHook = false) {		
		$output = "";
		$result = dibi::query("SELECT * FROM :prefix:form WHERE id=%i", $formId)->fetch();
		if($result == NULL) {
			return str_replace("%0", $formId, t("Form %0 was not found"));
		}

		$data = Config::sload($result["data"]);
		$settings = Config::sload($result["settings"]);
		if(!isset($settings["version"]) || floatVal($settings["version"]) < 2) {
			$settings = $data;
		}

		if($settings["enable"] != 1 && !$customFormHook) {
			return t("The form has been disabled!");		
		}
		if(!isset($settings["onetime"])) { 
			$settings["onetime"] = 0; 
		}

		$user = User::current();

		$formItems = dibi::query('SELECT * FROM :prefix:form_items WHERE `parent`=%s', $formId, " ORDER BY position");
		$formVariables = Page::FormGetVariables($formId);
		$formWidth = $settings["width"];

		$formState = Utilities::SendForm($formId, $customFormHook, true);
		//Utilities::vardump($formState);

		$disableAllFormField = $formState["disableForm"];
		$formIsMultimedia = $formState["isMultimedia"];
		$thisFormWasAleradyFilled = $formState["thisFormWasAleradyFilled"]; // 0 - False, 1 = True, 2 = You need to login

		if($thisFormWasAleradyFilled == 1) {
			$output.="<div class='box error'>".t("This form can only be filled in once!")."</div>";
		}else if($thisFormWasAleradyFilled == 2) {
			$output.="<div class='box error'>".t("Please log in to fill out the form")."</div>";
		}

		if($thisFormWasAleradyFilled == 0) {
			foreach($formState["warnings"] as $key => $warning) {
				$output.="<div class='box warning'>".$warning."</div>";
			}
			if($formState["submited"]) {
				foreach($formState["errors"] as $key => $error) {
					$output.="<div class='box error'>".$error."</div>";
				}			
			}

			if($formState["success"] != "") {
				$output.="<div class='box ok'>".$formState["success"]."</div>";
				return $output;
			}
		}
		
		$output.="<a name='_form_id_".$formId."'></a>";
		$output.="<form action='#_form_id_".$formId."' class='web-form' method=post ".($formIsMultimedia?"enctype=\"multipart/form-data\"":"").">";
		$output.="<input type=hidden name=form_id value='".$formId."'/>";

		$standartInputFieldType = ["textbox", "password", "textarea", "select", "picker", "upload", "slider"];
	
		foreach ($formItems as $id => $item) {
			if($item["type"] == "variable") continue;
			$itemData = Config::sload($item["data"]);			

			$name = $item["name"];
			$isRequired = false;
			$isHidden = false;

			if(in_array($item["type"], $standartInputFieldType)) {
				if($itemData["state"] == "1") { 
					$name.="<span class=requier id='form_input_".$item["id"]."_requie'>*</span>"; 
					$isRequired = true;
				}elseif($itemData["state"] == "3") {
					$isHidden = true;
				}
			}else if($item["type"] == "recaptcha"){
				$name.="<span class=requier id='form_input_".$item["id"]."_requie'>*</span>"; 
				$isRequired = true;
			}			

			if(!$isRequired && $item["type"] != "submit"){
				$name.="<span class=requier id='form_input_".$item["id"]."_requie' style='display:none;'>*</span>"; 
			}

			$item["value"] = preg_replace_callback('/\{#(.*?)\}/U', function ($matches) use($formVariables) {
				return (isset($formVariables[$matches[1]])? $formVariables[$matches[1]]: "null");
			}, $item["value"]);

			$leftClass = $itemData["position"] == 1?"col-md-3":"col-md-12";
			$rightClass = $itemData["position"] == 1?"col-md-9":"col-md-12";
			$value = (isset($_POST["form_input_".$item["id"]])? $_POST["form_input_".$item["id"]]: $item["value"]);

			$output.="<div class='form-group row mb-2' id='form_input_".$item["id"]."_html'>";
				$output.= "<div class='static-text ".$leftClass." ".($itemData['position'] == 5?'hidden':'')."'>".($item["type"] != "submit"? $name: "")."</div>";

				$element = "";
				if($item["type"] == "text") {
					$element = $item["value"];
				}elseif($item["type"] == "upload") {
					$element = "<input type='file' name='form_input_".$item["id"]."'>";				
				}elseif($item["type"] == "textbox"){
					$element = "<input type=text name='form_input_".$item["id"]."' onKeyUp=\"evaluate_form_".$formId."();\" id='form_input_".$item["id"]."' style='".($itemData["state"]=="3"?"display:none;":"")."' value='".$value."' placeholder='".$itemData["placeholder"]."' ".(($itemData["state"]=="2" || $disableAllFormField)?"disabled":"")." class='form-control ".(isset($formState["inputErrors"][$item["id"]]) ? "is-invalid": "")."'>";					
				}elseif($item["type"] == "password") {
					$element = "<input type=password name='form_input_".$item["id"]."' onKeyUp=\"evaluate_form_".$formId."();\" id='form_input_".$item["id"]."' style='".($itemData["state"]=="3"?"display:none;":"")."' placeholder='".$itemData["placeholder"]."' ".(($itemData["state"]=="2" || $disableAllFormField)?"disabled":"")." class='form-control ".(isset($formState["inputErrors"][$item["id"]]) ? "is-invalid": "")."'>";					
				}elseif($item["type"] == "recaptcha") {
					$element = '<div class="recaptcha'.(isset($formState["inputErrors"][$item["id"]])?" is-invalid":"").'"><div class="g-recaptcha" data-sitekey="'.$root->config->get("recaptcha-key").'"></div></div>';
				}elseif($item["type"] == "textarea") {
					$element = "<textarea name='form_input_".$item["id"]."' onKeyUp=\"evaluate_form_".$formId."();\" style='".($itemData["state"]=="3"?"display:none;":"")."' placeholder='".$itemData["placeholder"]."' ".(($itemData["state"]=="2" || $disableAllFormField)? "disabled": "")." class='form-control".(isset($formState["inputErrors"][$item["id"]]) ? " is-invalid": "")."' rows='".$itemData["rows"]."'>".$item["value"]."</textarea>";
				}elseif($item["type"] == "submit") {
					$element = "<input type=submit name='form_input_".$item["id"]."' id='form_input_".$item["id"]."' value='".$name."' ".($disableAllFormField? "disabled": "")." class='btn btn-primary'>";
				}elseif($item["type"] == "slider") {
					$element = "<input type=slider name='form_input_".$item["id"]."' id='form_input_".$item["id"]."' value='".$value."' ".(($itemData["state"]=="2" || $disableAllFormField)? "disabled": "")." data-formater='".$itemData['title']."' data-min='".$itemData['value_min']."' data-max='".$itemData['value_max']."' data-step='".$itemData['step']."'/>";
				}elseif($item["type"] == "picker") { 
					$pickerSelected = [];
					$resultAnswers = dibi::query('SELECT * FROM :prefix:form_answer WHERE parent = %i', $formId, " ORDER BY id DESC");
					foreach ($resultAnswers as $answerKey => $answer) {
						$answerData = Config::sload($answer["data"]);
						for($i = 0; $i < count($answerData); $i++){
							$dtq = $answerData[$i];
							if($dtq[3] == $item["id"]){
								$mdq = explode("[;", $dtq[2]);
								if(!isset($pickerSelected[$mdq[0]])){ 
									$pickerSelected[$mdq[0]] = 1; 
								} else { 
									$pickerSelected[$mdq[0]]+= 1; 
								}
							}
						}
					}
					$element.="<div class='picker'><input type=hidden id='form_input_".$item["id"]."' name='form_input_".$item["id"]."'>";
						$items = explode("[;", $itemData["items"]);
						for($index = 0; $index < count($items); $index++){	
							if(!isset($pickerSelected[$index])){ $pickerSelected[$index] = 0; } 

							$itemValue = explode("[,", $items[$index]);
							$perm = User::permission($itemValue[2]);
							$color = $perm['color'];
							if($color == "") $color = "black";

							$leftCount = -1;
							$leftText = "";
							if($itemValue[3] != 0){
								$leftText = t("Lefts").": ".($itemValue[3] - $pickerSelected[$index]);
								$leftCount = $itemValue[3] - $pickerSelected[$index];
							}

							$isDisabled = $disableAllFormField;
							if(User::permission(User::current()["permission"])["level"] < $perm["level"] || $leftCount == 0) {
								$isDisabled = true;
							}
							$onClick = "";
							if(!$isDisabled) {
								$onClick = "$('#form_input_".$item["id"]."').val('".$index."[;".$items[$index]."');";
								if($itemData["displayas"] == "cells") {
									$onClick.="showInfo(this, '".$item["id"]."', '#picker_".$item["id"]."_info', '<div class=rightmin>".$leftText."</div><b>".t("Selected")."</b><br>".$itemValue[0]."<br>".$itemValue[1]."');evaluate_form_".$formId."();";
								} else {
									$onClick.="var c = '".$item["id"]."'; if(typeof lastSel[c] == 'undefined'){ lastSel[c] = this; }else{ $(lastSel[c]).removeClass('sel'); lastSel[c] = this; } $(this).addClass('sel');";
								}
							}

							if($itemData["displayas"] == "cells"){
								$element.="<div onClick=\"".$onClick."\" data-index='".$index."' class='picker-cells".($disableAllFormField || $isDisabled?" disabled":"")."' title='".$itemValue[1]."' style='font-size: ".$itemData['fontsize']."px; width: ".$itemData['size']."px; height: ".$itemData['size']."px; color: ".$color.";'>".$itemValue[0]."</div>";
							}else{
								$element.="<div onClick=\"".$onClick."\" class='picker-".$itemData["displayas"]."".($disableAllFormField || $isDisabled?" disabled":"")."' style='color:".$color.";'>";								
								$element.=$itemValue[0]."<div class='desc'>".$itemValue[1]."</div>";
								if($leftText != "") { $element.="<div class='info-text'>".$leftText."</div>"; }
								$element.="</div>";
							}
						}
						$element.="<div style='clear:both;'></div><div style='display:none;' class='info-box' id='picker_".$item["id"]."_info'></div>";
					$element.="</div>";
				}elseif($item["type"] == "select") {
					$items = explode("[;", $itemData["items"]);
					
					if($itemData["types"] == 1 || $itemData["types"] == 2){
						$element = "<select id='form_input_".$item["id"]."' onChange=\"if(this.value=='custom'){ \$('#form_input_".$item["id"]."_custom_val').show(); \$('#form_input_".$item["id"]."_custom_val').focus(); }else{ \$('#form_input_".$item["id"]."_custom_val').hide(); }; evaluate_form_".$formId."();\" ".($itemData["types"] == 2?"multiple":"")." name='form_input_".$item["id"]."".($itemData["types"] == 2?"[]":"")."' style='width:100%;".($itemData["state"]=="3"?"display:none;":"")."' ".($data["state"]=="2" || $disableAllFormField?"disabled":"")." class='''>";
						for($l=0; $l < count($items); $l++){
							$itemValue = explode("[,", $items[$l]);
							$optionValue = ($itemValue[1] == ""? $itemValue[0]: $itemValue[1]);
							$isSelected = $itemValue[2] == 1;
							if(isset($_POST["form_input_".$item["id"]])) {
								$isSelected = $_POST["form_input_".$item["id"]] == $optionValue;
							}
							$element.="<option value='".$optionValue."' ".($isSelected ?"selected":"").">".$itemValue[0]."</option>";
						}
						if($itemData["custom"] == 1){
							$element.="<option value='custom'>".t("Custom value")."...</option>";
						}
						$element.="</select>";
					}elseif($itemData["types"] == 3){
						for($l=0; $l < count($items); $l++){
							$itemValue = explode("[,", $items[$l]);
							$optionValue = $itemValue[1] == ""? $itemValue[0]: $itemValue[1];
							$isSelected = $itemValue[2] == 1;
							if(isset($_POST["form_input_".$item["id"]])) {
								$isSelected = false;
								foreach($_POST["form_input_".$item["id"]] as $value) {
									if($value == $optionValue) { $isSelected = true; }
								}
							}
							$element.="<span class='itemsel form-input-check'><input class='form-input-check-input' onChange=\"evaluate_form_".$formId."();\" type=checkbox name='form_input_".$item["id"]."_".$l."' ".(($itemData["state"] == "2" || $disableAllFormField)? "disabled": "")." id='form_input_".$item["id"]."_".$l."' value='".$optionValue."' ".($isSelected?"checked":"")."><label for='form_input_".$item["id"]."_".$l."'>".$itemValue[0]."</label></span> ";
							if($itemData["place"] == "2") $element.="<br>";
						}
						if($itemData["custom"] == 1){
							$element.="<span class='itemsel form-input-check'><input class='form-input-check-input' onChange=\"if(\$(this).is(':checked')){ \$('#form_input_".$item["id"]."_custom_val').show(); \$('#form_input_".$item["id"]."_custom_val').focus(); }else{ \$('#form_input_".$item["id"]."_custom_val').hide(); }; evaluate_form_".$formId."();\" type=checkbox name='form_input_".$item["id"]."_custom' id='form_input_".$item["id"]."_custom' value='custom' ".($itemData["state"]=="2" || $disableAllFormField? "disabled": "")."><label for='form_input_".$item["id"]."_custom'>".t("Custom value")."...</label></span>";
						}
					}
					elseif($itemData["types"] == 4){
						for($l=0; $l < count($items); $l++){
							$itemValue = explode("[,", $items[$l]);
							$optionValue = $itemValue[1] == ""? $itemValue[0]: $itemValue[1];
							$isSelected = $itemValue[2] == 1;
							if(isset($_POST["form_input_".$item["id"]])) {
								$isSelected = $_POST["form_input_".$item["id"]] == $optionValue;
							}
							$element.="<span class='itemsel form-input-check'><input class='form-input-check-input' type=radio onChange=\"\$('#form_input_".$item["id"]."_custom_val').hide(); evaluate_form_".$formId."();\" name='form_input_".$item["id"]."' id='form_input_".$item["id"]."_".$l."' value='".$optionValue."' ".($isSelected?"checked":"")." ".(($itemData["state"] == "2" || $disableAllFormField)?"disabled":"")."><label for='form_input_".$item["id"]."_".$l."'>".$itemValue[0]."</label></span> ";
							if($itemData["place"] == "2") $element.="<br>";
						}
						if($itemData["custom"] == 1){
							$element.="<span class='itemsel form-input-check'><input class='form-input-check-input' onChange=\"\$('#form_input_".$item["id"]."_custom_val').show(); \$('#form_input_".$item["id"]."_custom_val').focus(); evaluate_form_".$formId."();\" type=radio name='form_input_".$item["id"]."' id='form_input_".$item["id"]."_custom' value='custom' ".(($itemData["state"]=="2" || $disableAllFormField)? "disabled": "")."><label for='form_input_".$item["id"]."_custom'>".t("Custom value")."...</label></span>";
						}
					}

					if($itemData["custom"] == 1){
						$element.="<div class=custom_value_div><input type=text id='form_input_".$item["id"]."_custom_val' name='form_input_".$item["id"]."_custom_val' value='".(isset($_POST["form_input_".$item["id"]."_custom_val"])?$_POST["form_input_".$item["id"]."_custom_val"]:"")."' style='display:none;' value='' placeholder='".t("Custom value")."...' onKeyUp=\"evaluate_form_".$formId."();\" class='custom_val form-control'></div>";
					}
				}

				$error = "";
				if(isset($formState["inputErrors"][$item["id"]])){
					if($formState["inputErrors"][$item["id"]] == 2)
						$error="<div class='inpoerror'>".t("Please enter a valid email!")."</div>";
					elseif($formState["inputErrors"][$item["id"]] == 1 || $formState["inputErrors"][$item["id"]] === true)
						$error="<div class='inpoerror'>".t("This field is required!")."</div>";
					else
						$error="<div class='inpoerror' style='".($item["type"] == "recaptcha"?"width: 302px;":"")."'>".$formState["inputErrors"][$item["id"]]."</div>";
				}

				$output.= "<div class='".$rightClass."'>";
					$output.= $element.$error;
				$output.= "</div>";

			$output.="</div>";			
		}
		$output.="</form>";
		$output.="<script>function evaluate_form_".$formId."() {  }</script>";
		return $output;
	}

	public function head(){		
		echo "<head>";
		echo '<title>';
			$separator = $this->root->config->get("titleSeparator");
			if($separator == "") $separator = "::";

			$pretitle = $this->root->config->get("pre-title");
			$title = $this->config["Title"];
			$titleFirst = $this->root->config->get("titleFirst");			
			if($pretitle == ""){
				echo $this->config["Title"];
				if($this->config["Description"] != ""){
					echo " - ".$this->config["Description"];
				}
			}
			else if($titleFirst != 2){
				echo $title.$separator.$pretitle;
			}else{
				echo $pretitle.$separator.$title;
			}
		echo '</title>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->config["Charset"] . '">';
		echo '<meta name="description" content="' . $this->config["Description"] . '">';
		echo '<meta http-equiv="Content-language" content="' . $this->root->GLOBAL_LANGUAGE . '">';
		echo '<meta name="author" content="' . $this->config["Autor"] . '">';
		echo '<meta name="keywords" content="' . $this->config["Keywords"] . '">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		if($this->root->config->get("browser-tab-color") != ""){
			echo '<meta name="theme-color" content="'.$this->root->config->get("browser-tab-color").'">';
		}
		echo '<link href="' . Router::url() . 'favicon.ico?cache='._CACHE.'" rel="icon">';		

		$cookieNoJs = $this->root->config->getD("cookie-no-js", "window['cookies'] = false;");
		$cookieJs = $this->root->config->getD("cookie-js", "cookieEnabled = true;");
		if(!Utilities::CookiesAccepted()) {
			echo "<script>".$cookieNoJs."</script>";
		}else{
			echo "<script>".$cookieJs."</script>";
		}

		if($this->script != null){
			foreach($this->script as $script){
				if($script["inhead"])
					echo '<script type="text/javascript" src="' . Router::urlAddParam($script["url"], "cache", _CACHE) . '"></script>';
			}
		}
		if($this->styles != null){
			foreach($this->styles as $style){
				echo '<link rel="stylesheet" '.($style["type"]!=""?"type='".$style["type"]."'":"").' href="' . Router::urlAddParam($style["url"], "cache", _CACHE) . '" media="screen" />';
			}
		}
		echo "</head>";
	}	

	public function maintenanceMode($turnOn = true, $reason = "none", $customHtaccess = "") {
		if($turnOn) {
			if($customHtaccess == "") {
				$customHtaccess = _ROOT_DIR."/include/maintenance.htaccess";
			}
			$plugin = $this->root->module_manager->hook_call("maintenance.on", array("reason" => $reason), $customHtaccess);

			if($plugin["called"] == 0) {
				$htaccess = file_get_contents(_ROOT_DIR."/.htaccess");

				rename(_ROOT_DIR."/.htaccess", _ROOT_DIR."/original.htaccess");
				//copy($customHtaccess, _ROOT_DIR."/.htaccess");
				
				preg_match("/\#MOD_REWRITE{(.*?)#}/is", $htaccess, $matches, PREG_OFFSET_CAPTURE, 0);
				$htaccessModRewrite = $matches[1][0];
				$htaccessMaintance = file_get_contents($customHtaccess);
				
				$htaccessMaintance = str_replace("{%MOD_REWRITE%}", $htaccessModRewrite, $htaccessMaintance);
				$htaccessMaintance = str_replace("{%URL%}", Router::url(), $htaccessMaintance);
				$htaccessMaintance = str_replace("{%IP%}", Utilities::ip(true), $htaccessMaintance);

				file_put_contents(_ROOT_DIR."/.htaccess", $htaccessMaintance);

				if(!file_exists(_ROOT_DIR."/views/content/maintenance.view")) {
					copy(_ROOT_DIR."/include/maintenance.html", _ROOT_DIR."/maintenance.html");
				}else{
					ob_start();
					$this->template_parse(_ROOT_DIR . "/views/content/maintenance.view", array(
						"reason" => t($reason)
					));
					$text = ob_get_contents();
					ob_end_clean();
					file_put_contents(_ROOT_DIR."/maintenance.html", $text);
				}
			}else{
				rename(_ROOT_DIR."/.htaccess", _ROOT_DIR."/original.htaccess");
				copy($plugin["output"], _ROOT_DIR."/.htaccess");
			}

			return;
		}

		$plugin = $this->root->module_manager->hook_call("maintenance.off", array("reason" => $reason));

        if(file_exists(_ROOT_DIR."/original.htaccess")) {
            rename(_ROOT_DIR."/original.htaccess", _ROOT_DIR."/.htaccess");
        }
        unlink(_ROOT_DIR."/maintenance.html");
	}

	public function toolbarRequests(){
		if(isset($_GET["closeToolbar"])){
			if($_GET["toolbarid"] == "cookie"){
				Cookies::set("cookieAccept", "yes", "+1 year");
				echo json_encode(array("ok" => true));
			}else{
				$this->root->infobar->closeToolbar($_GET["toolbarid"]);
				echo json_encode(array("ok" => true));
			}
			return true;
		}
		return false;
	}

	public function toptoolbar(){
		$user = User::current();
		$bars = $this->root->infobar->getAllByType("top_bar");		
		
		/*Cookie toolbar*/		
		if(!Utilities::CookiesAccepted(true)) {
			$cookieText = $this->root->config->getD("cookie-text", t("This website uses cookies. By continuing to browse this site, you agree to their use."));
			$cookieAccept = $this->root->config->getD("cookie-text-accept", t("I accept"));
			$cookieMore = $this->root->config->getD("cookie-more", "https://policies.google.com/technologies/cookies");

			echo "<div class='topbar toolbar-cookies' id='toolbar_cookie'>";
				echo "<div class=container>";
					echo "<div class=inner>";
						echo "<div class=text>".$cookieText;
						echo "<span class=toolbar-actions>";
							if($cookieMore != ""){
								echo "<a href='".$cookieMore."' target=_blank class='btn'>".t("More")."</a>";
							}
							echo "<a href=# class='btn' onclick=\"closeToolbar('cookie');\">".$cookieAccept."</a></div>";
						echo "</span>";
					echo "</div>";
				echo "</div>";				
			echo "</div>";
		}

		$session = User::session_old();
		if($session != false){
			echo "<div class='topbar toolbar-session' id='toolbar_session'>";
				echo "<div class=container>";
					echo "<div class=inner>";
						echo "<div class=text>".t("You are logged in as")." <b>".User::current()["nick"]."</b>";
						echo "<span class=toolbar-actions>";
							echo "<a href='".Router::url()."logout/' class='btn'>".t("Go back to")." ".User::get($session["user"])["nick"]."</a></div>";
						echo "</span>";
					echo "</div>";
				echo "</div>";				
			echo "</div>";
		}

		$condPass = true;
		foreach($bars as $key => $bar) {
			$closeByUser = false;
			if($bar["last_close"] != null && $bar["last_close"] > 0){
				$closeByUser = true;
			}

			foreach($bar["data"] as $n => $cond) {
				if($cond["type"] == "category") {
					$plugin = $this->root->module_manager->hook_call("page.toolbar.condition.category", 
						array("action" => $_GET["action"], "category" => $_GET["categoryid"], "condition" => $cond)
					);

					if(!$plugin["output"] && $plugin["called"] > 0){
						$condPass = false;
						continue;
					}
				}else if($cond["type"] == "showonly") {
					if(intval($bar["view"]) > intval($cond["howmany"])) {
						$condPass = false;
						continue;
					}
				}else if($cond["type"] == "showonlytype") {
					$perm = $cond["perm"];
					$permUp = $cond["perm_up"];
					$userPerm = User::permission($user["permission"]);
					$wantPerm = User::permission($perm);

					if(!($userPerm["level"] == $wantPerm["level"] || ($permUp && $userPerm["level"] >= $wantPerm["level"]))){
						$condPass = false;
						continue;
					}
				}else if($cond["type"] == "showonlytime") {
					if(strtotime("+".$cond["time"]." ".$cond["how"], intval($bar["last_close"])) < time()){
						$closeByUser = false;
					}
					else if(!(strtotime("+".$cond["time"]." ".$cond["how"], intval($bar["last_view"])) < time())) {
						$condPass = false;
						continue;
					}
				}else if($cond["type"] == "showonlyintime") {
					$fromHour = $cond["from_hour"];
					$fromMinutes = $cond["from_minutes"];
					$toHour = $cond["to_hour"];
					$toMinutes = $cond["to_minutes"];

					$currentHour = date('H');
					$currentMinutes = date('i');

					if(!($currentHour >= $fromHour && $currentMinutes >= $fromMinutes && $currentHour <= $toHour && $currentMinutes <= $toMinutes)){
						$condPass = false;
						continue;						
					}
				}
			}			

			if(!$condPass || $closeByUser){
				continue;
			}

			$this->root->infobar->registerToolbarView($bar["id"]);
			
			echo "<div class='topbar".($bar["alias"]!=""?" ".$bar["alias"]:"")."' id='toolbar_".$bar["id"]."'>";
				echo "<div class=container>";
					echo "<div class=inner>";
						echo "<div class=text>".$bar["text"]."</div>";
						echo "<div class=close onclick='closeToolbar(".$bar["id"].");'><span></span></div>";
					echo "</div>";
				echo "</div>";				
			echo "</div>";			
		}
	}

	public function footer(){
		if($this->script != null){
			foreach($this->script as $script){
				if(!$script["inhead"])
					echo '<script type="text/javascript" src="' . $script["url"] . '?cache='._CACHE.'"></script>';
			}
		}
	}

	public function admin_panel(){
		$user = User::current(true);
		$perm = User::permission($user["permission"])["permission"];

		if($perm["admin"] == 1 || _DEBUG || _ENVIROMENT == "dev"){
			echo "<div class='atoolbar d-none d-sm-block' id=admintoolsdebug style='bottom:-500px;height:350px;overflow: hidden;padding: 0px;'>";
				echo "<div id=log style='position: relative;height: 350px;overflow: hidden;width: 100%;'>";
				echo "<div style='width: 100%;position: absolute;top: 0px;' class='atoolbar fixme'><div class='l b'>LOG</div><a href=# onClick=\"$(last).hide();last='#routing';$('#routing').show();\">ROUTING</a><a href=# onClick=\"$(last).hide();last='#cookies';$('#cookies').show();\">COOKIES</a><a href=# onClick=\"$(last).hide();last='#post';$('#post').show();\">POST/GET</a></div>";
					echo "<div style='height: 312px;overflow: auto;width: 100%;margin-top: 38px;color: white;'>";
					$this->root->draw_log();
					echo "</div>";
				echo "</div>";
				echo "<div id=routing style='position: relative;height: 350px;overflow: hidden;width: 100%;display:none;'>";
				echo "<div style='width: 100%;position: absolute;top: 0px;' class='atoolbar fixme'><a href=# onClick=\"$(last).hide();last='#log';$('#log').show();\">LOG</a><div class='l b'>ROUTING</div><a href=# onClick=\"$(last).hide();last='#cookies';$('#cookies').show();\">COOKIES</a><a href=# onClick=\"$(last).hide();last='#post';$('#post').show();\">POST/GET</a></div>";
					echo "<div style='height: 312px;overflow: auto;width: 100%;margin-top: 38px;color: white;'>";	
					$this->root->router->draw_table();
					echo "</div>";
				echo "</div>";
				echo "<div id=cookies style='position: relative;height: 350px;overflow: hidden;width: 100%;display:none;'>";
				echo "<div style='width: 100%;position: absolute;top: 0px;' class='atoolbar fixme'><a href=# onClick=\"$(last).hide();last='#log';$('#log').show();\">LOG</a><a href=# onClick=\"$(last).hide();last='#routing';$('#routing').show();\">ROUTING</a><div class='l b'>COOKIES</div><a href=# onClick=\"$(last).hide();last='#post';$('#post').show();\">POST/GET</a></div>";
					echo "<div style='height: 312px;overflow: auto;width: 100%;margin-top: 38px;color: white;'>";	
					Cookies::dump();
					echo "</div>";
				echo "</div>";
				echo "<div id=post style='position: relative;height: 350px;overflow: hidden;width: 100%;display:none;'>";
				echo "<div style='width: 100%;position: absolute;top: 0px;' class='atoolbar fixme'><a href=# onClick=\"$(last).hide();last='#log';$('#log').show();\">LOG</a><a href=# onClick=\"$(last).hide();last='#routing';$('#routing').show();\">ROUTING</a><a href=# onClick=\"$(last).hide();last='#cookies';$('#cookies').show();\">COOKIES</a><div class='l b'>POST/GET</div></div>";
					echo "<div style='height: 312px;overflow: auto;width: 100%;margin-top: 38px;color: white;'>";	
					Utilities::post_debug();
					echo "</div>";
				echo "</div>";
				echo "</div>";
			echo "</div>";

			echo "<div class='atoolbar d-xs-none' id=admintools style='display:none;'>";		
				//status code				
				$status = http_response_code();
				$using = $this->root->config->get("system.page.use");
				$usingName = $this->root->config->get("system.page.name");
				$module_debug = $this->root->module_manager->hook_debuginfo("page.".$usingName)[0];
				echo "<div class='a'>";
					echo "<a href='".Router::url()."?__type=debug&token=".date("Ydm")."/".$this->root->debugToken."'><span class='status-code status-code-".$status."'>".$status."</span> ".$usingName."</a>";
					echo "<div class='atool-popup'>";
						echo "<div>";
							echo "<div class=line><b>HTTP Status</b><span>".$status." ".Utilities::getSatusCodeName($status)."</span></div>";
							echo "<div class=line><b>Using</b><span>".$using."</span></div>";
							if($using == "module") {
								echo "<div class=line><b>Module Name</b><span>".$usingName."</span></div>";
								echo "<div class=line><b>File definition</b><span>".Utilities::getFileName($module_debug["file"])."</span></div>";				
							} else if($using == "controller") {
								echo "<div class=line><b>Name</b><span>".$usingName."</span></div>";
								echo "<div class=line><b>File definition</b><span>".Utilities::getFileName($this->root->config->get("system.page.file"))."</span></div>";
								echo "<div class=line><b>Action</b><span>".$this->root->config->get("system.page.controller")."::".$this->root->config->get("system.page.action")."</span></div>";
							}
							echo "<div class=line><b>Using sessions</b><span><span class='upper colored-".($this->root->config->get("session_used") == 1?"red": "green")."'>".($this->root->config->get("session_used") == 1?"Yes":"No")."</span></span></div>";						
							echo "<div class=line><b>Template</b><span>".$this->root->template."</span></div>";
							echo "<div class=line><b>Language</b><span>"._LANGUAGE."</span></div>";							
						echo "</div>";
					echo "</div>";
				echo "</div>";	
				echo "<div class='d'></div>";

				//execution time
				$timeEndAll = Bootstrap::GetTime(false);
				$initializedEnd = Bootstrap::GetTimeInitialize();
				$timeEnd = Bootstrap::GetTime(true);
				echo "<div class='a ".($timeEndAll > 1000?" atoolbar-color-yellow":"")."'>";
					echo "<a href=#><i class=\"fas fa-hourglass-end\"></i><span> <b>".$timeEndAll."</b> ms</span></a>";
					echo "<div class='atool-popup'>";
						echo "<div>";
							echo "<div class=line><b>Initializing</b><span>".$initializedEnd." ms</span></div>";
							echo "<div class=line><b>Rendering</b><span>".$timeEnd." ms</span></div>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
				echo "<div class='d'></div>";

				//memory usage
				$memUsage = Utilities::convertBtoMB(memory_get_usage(true), "MB");
				$memLimit = Utilities::convertBtoMB(ini_get('memory_limit'), "MB");
				echo "<div class='a".(Utilities::convertBtoMB(memory_get_usage(), "MB") > 20?" atoolbar-color-yellow":"")."'>";
					echo "<a href=#><i class=\"fas fa-memory\"></i> <b>".$memUsage."</b></a>";
					echo "<div class='atool-popup'>";
						echo "<div>";
							echo "<div class=line><b>PHP Memory Usage</b><span>".$memUsage."</span></div>";
							echo "<div class=line><b>PHP Memory Limit</b><span>".$memLimit."</span></div>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
				echo "<div class='d'></div>";

				//user
				$userPerm = User::permission($user["permission"]);
				echo "<div class='a'>";
					echo "<a href=#><i class=\"fas fa-user\"></i> <b>".$user["nick"]."</b></a>";
					echo "<div class='atool-popup'>";
						echo "<div>";
							echo "<div class=line><b>Username</b><span>".$user["nick"]."</span></div>";
							echo "<div class=line><b>Authenticated</b><span><span class='upper colored-".($user["isGuest"]?"red": "green")."'>".($user["isGuest"]?"no": "yes")."</span></div>";
							if($user["isGuest"]){
								
							}else{
								echo "<div class=line><b>Permission</b><span>".$userPerm["name"]."</span></div>";
								echo "<div class=line><b>Email</b><span>".$user["email"]."</span></div>";
								echo "<div class=line><b>Ip</b><span>".Utilities::ip()."</span></div>";
								echo "<div class=line><b>Actions</b><span>";
									echo "<a href='".Router::url()."logout/' class='link'>Logout</a>, ";
									echo "<a href='".Router::url()."settings/' class='link'>Settings</a>";
								echo "</span></div>";
							}
						echo "</div>";
					echo "</div>";
				echo "</div>";
				echo "<div class='d'></div>";

				//database
				$dbTime = (round(dibi::$totalTime, 4)*100);
				echo "<div class='a'>";
					echo "<a href=#><i class=\"fas fa-database\"></i> <span><b>".dibi::$numOfQueries."</b> in <b>".$dbTime."</b> ms</span></a>";
					echo "<div class='atool-popup'>";
						echo "<div>";
							echo "<div class=line><b>Engine</b><span>".$this->root->database->_use."</span></div>";
							echo "<div class=line><b>Database queries</b><span>".dibi::$numOfQueries."</span></div>";
							echo "<div class=line><b>Query time</b><span>".$dbTime." ms</span></div>";							
						echo "</div>";
					echo "</div>";
				echo "</div>";
				echo "<div class='d'></div>";

				//$this->styles
				//Styles, Javascript
				$total = count($this->styles)+count($this->script);
				echo "<div class='a'>";
					echo "<a href=#><i class=\"fas fa-file-code\"></i> ".$total."</a>";
					echo "<div class='atool-popup'>";
						echo "<div>";
							echo "<div class=cat><span class='cat-tag tag-yellow'>CSS</span></div>";
							foreach($this->styles as $n => $file) {
								echo "<div class=line>".str_replace(Router::url(), "", $file["url"])."</div>";						
							}
							echo "<div class=cat><span class='cat-tag tag-purple'>JS</span></div>";
							foreach($this->script as $n => $file) {
								echo "<div class=line>".str_replace(Router::url(), "", $file["url"])."</div>";						
							}
						echo "</div>";
					echo "</div>";
				echo "</div>";
				echo "<div class='d'></div>";

				/*
				echo "<a class='b' href='".$this->root->router->url."'>".$this->config["Title"]."</a>";
				echo "<div class='l'>".$this->root->config->get("pre-title")."</div>";			
				*/

				echo "<a href=# onClick='hideTopBar();return false;' title='Schovat' class='right'><i class=\"fas fa-angle-down\"></i></a>";				
				echo "<div class='right d m-close'></div>";				
				echo "<a href=# class='right m-close' onclick='showdebug();'><i class=\"fas fa-wrench\"></i> Debug</a>";

				$plugin = $this->root->module_manager->hook_call("admin.toolbar.".$this->root->router->_data["module"][0]);
				if( $plugin["called"] != 0 ){
					echo $plugin["output"];
				}

			echo "</div>";
			echo "<a href=# onclick='showTopBar();return false;' id=admintoolshow class='admshowTopBar d-block d-sm-block'><i class=\"fas fa-angle-up\"></i></a>";
			echo "<script>$(function(){readyTopBar();});</script>";
		}
	}

	public function error_box($text, $class, $popup = false, $ret = false){
		$error = $this->root->module_manager->hook_call("page.box.error", array("text" => $text, "class" => $class));
		if($error["called"] == 0){
			if(!$popup){
				if($ret)
					return "<div class='box ".$class."'>".$text."</div>";
				echo "<div class='box ".$class."'>".$text."</div>";
			}
			else{
			?>
			<script>
			$(function(){
				NotificationCreate("", "<?php echo $text; ?>", "#", "<?php echo $class; ?>");
			});		
			</script>
			<?php
			}
		}
		else{
			if($ret)
				return $error["output"];
			echo $error["output"];
		}
	}

	public function draw_error($title, $text = ""){
		echo "<h1>".$title."</h1>";
		echo $text;
	}

	public function login_box($register = false, $lostpass = false, $title = "Přihlášení", $text = ""){
		echo "<div class=loginbox>";
			if($title != ""){
				echo "<div class=title><i class=\"fas fa-key\"></i> ".$title."</div>";
			}

			$this->login($register, $lostpass);

			if($text != ""){
				echo "<div class=line></div>";
				echo "<div class='body text'>";
					echo $text;
				echo "</div>";
			}
		echo "</div>";
	}

	public function login($register = false, $lostpass = false){
		echo "<div class=body>";

			if(_DEBUG){
				echo "<div class='expandable closed' id='cat_1'>";
					echo "<div class=title_admin><a href=# onclick=\"showhideclass('#cat_1', 'closed');return false;\">".t("debug information")."</a></div>";
					echo "<div>";					
						echo "<div>session: ".(Cookies::security_check("session")?1:0)."</div>";
						echo "<div>session value: ".$_COOKIE["session"]."</div>";
						$security_get = Cookies::security_get("session");
						echo "<div>hash: ".$security_get["hash"]."</div>";
						echo "<div>sha1: ".sha1("session".$_COOKIE["session"])."</div>";						
						Cookies::dump(true);
					echo "</div>";
				echo "</div>";
			}

				if(Cookies::exists("session") && User::current() != null){
					if(isset($_GET["logout"])){						
						User::logout();
						header("location:".Router::url());
						exit;
					}

					$session_login = User::session_check();			

					if(Cookies::security_check("session") and $session_login){
						$user = User::current();
						$perm = User::permission($user["permission"]);
						if($user["avatar"] == ""){ $user["avatar"] = $this->root->config->get_variable("default-avatar"); }
						echo "<img src='".Router::url()."/upload/avatars/".$user["avatar"]."' alt='avatar' class=avatar>";
						echo "<div id=username class='".$perm["name"]."' title='".$perm["name"]."' style='color:".$perm["color"]."'>".$user["nick"]."</div>";
						echo "<a href='?logout'>".t("Logout")."</a><a href='".Router::url()."settings/' class=left_separator>".t("Settings")."</a>";
						if($perm["permission"]["admin"]==1){ echo "<a href='".Router::url()."admin/' class=left_separator>".t("Admin")."</a>"; }
					}elseif(!$session_login){
						echo "<div style='color:red;font-weight:bold;'>".t("Your session has been canceled or has expired!")."</div>";
						header("location:?logout&session");
					}else{
						echo "<div style='color:red;font-weight:bold;'>".("COOKIES SECURITY ERROR!")."</div>";
						header("location:?logout");
					}
				}else{
					if(isset($_POST["login"])){
						$username = $_POST["username"];
						$password = $_POST["password"];
						if(isset($_POST["remember"])) $remember = true; else $remember = false;

						$login = User::login($username, $password, $remember);
						
						if(!is_array($login)){
							$this->error_box("Přihlášen", "ok");
							header("location:".Router::url(true, true));
						}else{
							$mess="";
							for($i = 0;$i < count($login); $i++){
								$mess.= "<div>".$login[$i]."</div>";
							}
							echo "<div class=pol>";
								$this->error_box($mess, "error");
							echo "</div>";
						}
					}
					if(isset($_GET["session"]))
						$this->error_box(t("Your session has been canceled or has expired!"), "error");
					echo "<form action=# method=post>";
						echo "<div class=pol><input type=text name=username value='' placeholder='".t("username")."' style='width:100%;'></div>";
						echo "<div class=pol><input type=password name=password value='' placeholder='".t("password")."' style='width:100%;'></div>";
						echo "<div class=pol>";
						if(Config::getS("onlyttl", 0) != 1)
							echo "<label><input type=checkbox name=remember value='1'> ".t("Remember")."</label>";
						echo "</div>";
						echo "<div class=foot>";
							echo "<div class=right><input type=submit class='login btn btn-primary btn-sm' name=login value='".t("Login")."'></div>";
							if($register){
								echo "<a href='".$this->root->router->url."register' class='link'>".t("Register")."</a>";
							}
							if($lostpass){
								echo " <a href='#' onClick=\"return recoveryPass(1);\" class='link'>".t("Forgotten password")."</a>"; //".$this->root->router->url."newpass
							}
						echo "</div>";						
					echo "</form></div>";
				}
			echo "<div style='clear:both;'></div>";		
		echo "</div>";	
	}

	public function menu_draw($name, $setting){
		if(isset(User::current()["permission"])){ $l = User::permission(User::current()["permission"])["level"]; }else{ $l = 0; }

		$result = dibi::query("SELECT * FROM :prefix:menu WHERE box=%s", $name, " AND language = %s", _LANGUAGE," AND visible = 1 AND milevel <= %i ", $l," AND malevel >= %i ", $l," ORDER BY position");
		if($result->count() == 0){
			$result = dibi::query("SELECT * FROM :prefix:menu WHERE box=%s", $name, " AND language is NULL AND visible = 1 AND milevel <= %i ", $l," AND malevel >= %i ", $l," ORDER BY position");
		}

		if(!isset($setting["noul"])){ 				$setting["noul"] = false; }
		if(!isset($setting["class"])){ 				$setting["class"] = "menu"; }
		if(!isset($setting["li_class"])){ 			$setting["li_class"] = ""; }
		if(!isset($setting["a_class"])){ 			$setting["a_class"] = ""; }
		if(!isset($setting["li_selected_class"])){ 	$setting["li_selected_class"] = "selected"; }
		if(!isset($setting["custom_tag"])){ 		$setting["custom_tag"] = "li"; }
		if(!isset($setting["class"])){ 				$setting["class"] = "menu"; }		

		if( $setting["noul"]!=true ){ echo "<ul".($setting["class"]!=""?" class='".$setting["class"]."'":"").">"; }
		foreach ($result as $n => $row) {
				$row = $this->root->module_manager->hook_call("page.menu.item", null, $row, true, false);
				$this->menu_item_get($row, $setting);
				if(isset($setting["inside"])){
					echo "<".$setting["custom_tag"].">".$setting["inside"]."</".$setting["custom_tag"].">";
				}			
		}
		if( $setting["noul"]!=true ){ echo "</ul>"; }
	}

	public function menu_item_get($row, $setting){
		if($row["data"]!=""){ $data = $this->root->config->load($row["data"]); }else{ $data = ""; }
		if(!isset($setting["li_class"])){ $setting["li_class"] = ""; }
		if($row["typ"] == "index"){
			echo "<".$setting["custom_tag"]." class='".$setting["li_class"]." ".
				($this->root->router->_get == ""?$setting["li_selected_class"]:"")
				."'><a href='".$this->root->router->url."'".
				($setting["a_class"] != ""?" class='".$setting["a_class"]."'":"")
				.">".$row["title"]."</a></".$setting["custom_tag"].">";
		}
		else if($row["typ"] == "article"){
			//TODO: multilang and changes better is ID
			if( $this->root->router->_data["module"][0]=="article" && ( $this->root->router->_data["id"][0] == $data["alias"] or $this->root->router->_data["id"][0] == $data["id"] ) ){
				$select = true;
			}else{ $select = false; }
			echo "<".$setting["custom_tag"]." class='".$setting["li_class"]." ".
				($select?$setting["li_selected_class"]:"")
				."'><a href='".$this->root->router->url.$data["alias"]."'".
				($setting["a_class"] != ""?" class='".$setting["a_class"]."'":"")
				.">".$row["title"]."</a></".$setting["custom_tag"].">";
		}
		else if($row["typ"] == "category"){
			if( $this->root->router->_data["module"][0]=="category" && ( $this->root->router->_data["id"][0] == $data["alias"] or $this->root->router->_data["id"][0] == $data["id"] ) ){
				$select = true;
			}else{ $select = false; }
			echo "<".$setting["custom_tag"]." class='".$setting["li_class"]." ".
				($select?$setting["li_selected_class"]:"")
				."'><a href='".$this->root->router->url."category/".$data["alias"]."'".
				($setting["a_class"] != ""?" class='".$setting["a_class"]."'":"")
				.">".$row["title"]."</a></".$setting["custom_tag"].">";
		}
		else if($row["typ"] == "url"){
			if(substr($data["url"],0,1) == "/") $data["url"] = $this->root->router->url."".substr($data["url"],1);
			if( $this->root->router->_get==$data["url"] || "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" == $data["url"]){
				$select = true;
			}else{ $select = false; }
			echo "<".$setting["custom_tag"]." class='".$setting["li_class"]." ".
				($select?$setting["li_selected_class"]:"")
				."'><a href='".$data["url"]."'".
				($setting["a_class"] != ""?" class='".$setting["a_class"]."'":"")
				.">".$row["title"]."</a></".$setting["custom_tag"].">";
		}
		else if($row["typ"] == "separator"){
			//class
			echo "</ul><ul class='".$setting["class"]."'>";
			if($row["title"] != "")
				echo "<div class=title>".$row["title"]."</div>";
		}
		else if($row["typ"] == "login"){
			echo "<".$setting["custom_tag"]." class='".$setting["li_class"]." menu-login'><span class=body>";				

				if(Cookies::exists("user") and Cookies::exists("id")){
					if(isset($_GET["logout"])){
						Cookies::delete(array("id","user","permission"));
						if($_GET["__type"] == "ajax")
							header("location:".Router::url(true, true));
						else
							header("location:./");
						//header("refresh:0");
					}

					if(Cookies::exists("id"))
						$session_login = User::session_check($_COOKIE["id"]);
					else
						$session_login = false;

					if(Cookies::security_check("permission") and Cookies::security_check("id") and Cookies::security_check("session") and $session_login){
						$user = User::current();
						$perm = User::permission($user["permission"]);
						if($user["avatar"] == ""){ $user["avatar"] = $this->root->config->get_variable("default-avatar"); }
						echo "<img src='".Router::url()."/upload/avatars/".$user["avatar"]."' alt='avatar' class=avatar>";
						echo "<div id=username class='".$perm["name"]."' title='".$perm["name"]."' style='color:".$perm["color"]."'>".$user["nick"]."</div>";
						echo "<a href='?logout'>Odhlásit se</a><a href='".Router::url()."settings/' class=left_separator>Nastavení</a>";
						if($perm["permission"]["admin"]==1){ echo "<a href='".Router::url()."admin/' class=left_separator>Admin</a>"; }
					}elseif(!$session_login){
						echo "<div style='color:red;font-weight:bold;'>Tvoje relace byla zrušena nebo vypršela!</div>";
						header("location:?logout&session");
					}else{						
						echo "<div style='color:red;font-weight:bold;'>COOKIES SECURITY ERROR!</div>";
						header("location:?logout&cookies");
					}
				}else{
					if(Cookies::exists("session"))
						Cookies::delete("session");

					if($row["title"] == "")
						echo "<b class=ctitle>Přihlášení</b>";
					else	
						echo "<b class=ctitle>".$row["title"]."</b>";
						
					if(isset($_POST["login"])){
						$username = $_POST["username"];
						$password = $_POST["password"];
						if(isset($_POST["remember"])) $remember = true; else $remember = false;

						$login = User::login($username, $password, $remember);
						if(!is_array($login)){
							$this->error_box("Přihlášen", "ok");
							header("location:".Router::url(true, true));
						}else{
							$mess="";
							for($i = 0;$i < count($login); $i++){
								$mess.= "<div>".$login[$i]."</div>";
							}
							$this->error_box($mess, "error");
						}
					}
					if(isset($_GET["session"]))
						$this->error_box("Tvoje relace byla zrušena nebo vypršela!", "error");
					echo "<form action=# method=post class=loginboxmenu>";
						echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">Jméno</label><div class=\"col-sm-10\"><input type=text class='form-control' name=username value=''></div></div>";
						echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-2 col-form-label\">Heslo</label><div class=\"col-sm-10\"><input type=password class='form-control' name=password value=''></div></div>";
						echo "<div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-2'></label><div class='col-12 col-sm-10'><label><input type=checkbox name=remember value='1'> Zapamatovat</label></div></div>";
						echo "<div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-2'></label><div class='col-12 col-sm-10'><input type=submit class='btn btn-primary' name=login value='Přihlásit se'>";
						echo "</div></div><div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-2'></label><div class='col-12 col-sm-10'>";
						if($data["register"]=="1"){
							if( $this->root->router->_get=="register" ){ $select = true; }else{ $select = false; }
							echo " <a href='".$this->root->router->url."register' class='registerlink".($select?" ".$setting["li_selected_class"]:"")."'>Registrovat se</a><br style='clear:right;'>";
						}
						if(!isset($data["nopasslost"])){
							if( $this->root->router->_get=="newpass" ){ $select = true; }else{ $select = false; }
							echo " <a href='".$this->root->router->url."newpass' onClick=\"return recoveryPass(1);\" class='-newpasslink ".($select?" ".$setting["li_selected_class"]:"")."'>Zapomenuté heslo</a>";
						}
						echo "</div></div>";
						echo "";
					echo "</form>";
				}
			echo "</span></".$setting["custom_tag"].">";
		}
		else{
			// Use module menu
		}
	}

	private $view = null;

	public function getContent($module, $action, $loadController = true, $folder = "controllers", $subClass = "Controller"){
		$file = "";
		$className = "";
		$actionName = "";
		$_view = "";
		$lastError = "";
		$module = strtolower($module);

		$fdef = file_exists(_ROOT_DIR . "/" . $folder . "/" . $module . ".php");
		$fcon = file_exists(_ROOT_DIR . "/" . $folder . "/" . $module . "Controller.php");		
		$class = strtolower($module);

		if($fdef || $fcon || class_exists($class)){
			//$this->root->config->set("system.page.use", "controller");
			//$this->root->config->set("system.page.name", $module);

			if($loadController) {
				if($fdef) {					
					//$this->root->config->set("system.page.file", _ROOT_DIR . "/controllers/" . $module . ".php");
					$file = _ROOT_DIR . "/" . $folder . "/" . $module . ".php";
					include_once $file;
				}
				else {
					//$this->root->config->set("system.page.file", _ROOT_DIR . "/controllers/" . $module . "Controller.php");
					$file = _ROOT_DIR . "/" . $folder . "/" . $module . "Controller.php";
					include_once $file;
				}
			}

			//$this->root->log("Loaded controller " . $module);
			
			if(class_exists($class)) {		
				$controller = new $class($this->root);
				//$this->root->config->set("system.page.controller", get_class($controller));
				$className = get_class($controller);
			}
			else if(class_exists($class."Controller")){
				$class = $class."Controller";				
				$controller = new $class($this->root);
				//$this->root->config->set("system.page.controller", get_class($controller));
				$className = get_class($controller);
			}
			else{
				//echo Utilities::fatal("Controller", "Controller \"".$class."\" not exists");
				return array(false, "Controller \"".$class."\" not exists");
			}

			//$this->root->log("Class was created " . $class);
			//$action = $_GET["action"];

			$customArguments = [];
			//$view = $controller->$action();

			//$this->root->log("Controller action '" . $action . "'");

			while(true){
				try{					
					$reflection = new ReflectionMethod($controller, $action);
					//$this->root->config->set("system.page.action", $reflection->getName());
					$actionName = $reflection->getName();
				}catch(ReflectionException $e) {
					//echo Utilities::fatal("Controller", "Method \"".$class.".".$action."\" not found!");
					return array(false, "Method \"".$class.".".$action."\" not found!");
				}
				
				$fireArgs = array();
				foreach($reflection->getParameters() as $arg){
					if(isset($customArguments[$arg->name]))
						$fireArgs[$arg->name] = $customArguments[$arg->name];
					else if(isset($_GET[$arg->name]))
						$fireArgs[$arg->name] = $_GET[$arg->name];
					else if(isset($_POST[$arg->name])) {
						$fireArgs[$arg->name] = $_POST[$arg->name];		
						if($arg->isDefaultValueAvailable()){	
							if(is_bool($arg->getDefaultValue())){
								if($fireArgs[$arg->name] == "true" || $fireArgs[$arg->name] == "false" || $fireArgs[$arg->name] == "1" || $fireArgs[$arg->name] == "0") {
									$fireArgs[$arg->name] = ($fireArgs[$arg->name] == "true" || $fireArgs[$arg->name] == "1"?true:false);	
								}
							}							
						}
					}
					else if(isset($_FILES[$arg->name]))
						$fireArgs[$arg->name] = $_FILES[$arg->name];
					else if(($a = internal_GET_ARRAY($arg->name)) != null)
						$fireArgs[$arg->name] = $a;
					else if(!$arg->isDefaultValueAvailable()){
						$lastError = "Controller method \"".$class.":".$action."\" argument \"".$arg->name."\" must be defined";						
					}else
						$fireArgs[$arg->name] = $arg->getDefaultValue();
				}							
								
				$method = null;		
				$onmethod = array();	

				$sComment = $reflection->getDocComment();
				$sParams = null;
				//Parse standart
				if (preg_match_all('%^\s*\*\s*@([a-zA-Z]+)(\s+([a-zA-Z/ =!_]+))*\s*$%im', $sComment, $result, PREG_SET_ORDER, 0)) {
					$sParams = $result;
					foreach ($result as $sProp) {
						$wh = strtolower($sProp[1]);					
						if($wh == "method"){
							$method = trim(strtoupper($sProp[3]));
						}
						else if($wh == "post"){
							$onmethod["post"] = $sProp[3];
						}
						else if($wh == "get"){
							$onmethod["get"] = $sProp[3];
						}
						else if($wh == "param"){						
							$d = explode(" ", $sProp[3]);
							if($fireArgs[$d[0]] != null){
								$ar = $fireArgs[$d[0]];
								$po = strtolower($d[1]);
								$is = false;							
								if($po == "number"){							
									if(is_numeric($ar))
										$is = true;
								}
								else if($po == "string"){
									if(is_string($ar))
										$is = true;
								}
								if(!$is){
									//echo Utilities::fatal("Controller", "Method \"".$class.".".$action."\" argument \"".$d[0]."\" must be type of \"".$po."\" got \"".$ar."\"");
									return array(false, "Method \"".$class.".".$action."\" argument \"".$d[0]."\" must be type of \"".$po."\" got \"".$ar."\"");
								}
							}
						}
					}
				}
				//Parse attributes functions
				/*if (preg_match_all('%^\s*\*\s*@([a-zA-Z]+)\(([a-zA-Z/ =!_",*+0-9\(\)]*)\)\s*$%im', $sComment, $result, PREG_SET_ORDER, 0)) {
					foreach ($result as $sProp) {
						echo "Calling function: ".$sProp[1]." width arguments ".$sProp[2]."<hr>";


					}
				}*/

				if ($method != null && $_SERVER['REQUEST_METHOD'] !== $method) {
					if(isset($onmethod[strtolower($_SERVER['REQUEST_METHOD'])])){						
						$action = $onmethod[strtolower($_SERVER['REQUEST_METHOD'])];
						continue;
					}
					//echo Utilities::fatal("Controller", "Method \"".$class.".".$action."\" must be show only with \"".$method."\" method. Current method is \"".$_SERVER['REQUEST_METHOD']."\"");
					return array(false, "Method \"".$class.".".$action."\" must be show only with \"".$method."\" method. Current method is \"".$_SERVER['REQUEST_METHOD']."\"");
				}

				if($lastError != ""){
					//echo Utilities::fatal("Controller", $lastError);
					return array(false, $lastError);
				}										

				if(!is_subclass_of($class, $subClass)){
					//echo Utilities::fatal("Controller", "Controller \"".$class."\" not implement class \"".$subClass."\"");
					return array(false, "Controller \"".$class."\" not implement class \"".$subClass."\"");
				}									

				$overideAction = null;
				try{
					//$reflectionBefore = new ReflectionMethod($controller, "before");

					$aParams = [];
					$cParams = [];
					foreach($sParams as $i => $param) {
						if(!isset($cParams[$param[1]])) $cParams[$param[1]] = 1;

						if(!isset($param[3])) $param[3] = true;
						if($cParams[$param[1]] == 1) {
							$aParams[$param[1]] = $param[2];
						}else{
							if(!isset($cParams["_".$param[1]][0])) {
								$aParams["_".$param[1]][0] = $aParams[$param[1]];
							}
							$aParams["_".$param[1]][$cParams[$param[1]] - 1] = $param[2];
						}

						$cParams[$param[1]]++;
					}

					$reflectionBeforeCall = call_user_func_array(array($controller, "before"), array($action, $fireArgs, $aParams, $sComment, $controller, $sParams));

					if($reflectionBeforeCall != null && $reflectionBeforeCall["type"] == "action") {
						$action = $reflectionBeforeCall["action"];
						if($reflectionBeforeCall["arguments"] != null) {
							$customArguments = $reflectionBeforeCall["arguments"];
						}
						continue;
					}else if($reflectionBeforeCall != null) {
						$overideAction = $reflectionBeforeCall;
					}
				}catch(ReflectionException $e){}

				if($overideAction != null) {
					//$this->root->log("View function " . $reflection->getName()." id overidet by before!");
					$_view = $overideAction;
				}else{
					$view = call_user_func_array(array($controller, $action), $fireArgs);
					//$this->root->log("View function was called " . $reflection->getName());
					$_view = $view;
				}

				if($_view["type"] == "html" && $_view["template"] == null){
					//echo Utilities::fatal("Controller", "View \"".$view["view"]."\" not found in views/".$class."/");
					return array(false, "View \"".$_view["view"]."\" not found in ".$_view["folder"]."/".$class."/");
				}
				
				break;
			}
			return array(true, $file, $module, $className, $actionName, $_view);
		}
	}

	public function prepare(){		
		$prep = $this->getContent($_GET["module"], $_GET["action"]);
		if($prep == null) return;

		if($prep[0] === false) {
			echo Utilities::fatal("Controller", $prep[1]);
			return;
		}

		$this->root->config->set("system.page.use", "controller");
		$this->root->config->set("system.page.name", $prep[2]);
		$this->root->config->set("system.page.file", $prep[1]);
		$this->root->config->set("system.page.controller", $prep[3]);
		$this->root->config->set("system.page.action", $prep[4]);
		$this->view = $prep[5];
	}

	public function isAjax(){
		return $this->view["ajax"] == true;
	}

	public function page_draw(){	
		if($this->toolbarRequests())
			return;

		if($this->view != null){
			if($this->view["type"] == "json"){		
				header('Content-Type: application/json');		
				echo json_encode($this->view["data"]); 
			}
			else if($this->view["type"] == "redirect"){	
				if(strpos($this->view["data"], 'http') !== false)	
					header('location: '.$this->view["data"]);
				else {
					if(substr($this->view["data"], 0, 1) == "/"){
						$this->view["data"] = substr($this->view["data"], 1);
					}
					header('location: '.Router::url().$this->view["data"]);	
				}
				exit;
			}
			else if($this->view["type"] == "text"){
				if($this->view["textarea"] == true){
					echo "<textarea>".$this->view["data"]."</textarea>";
				}else{
					echo $this->view["data"];
				}
			}
			else if($this->view["type"] == "error"){
				http_response_code($this->view["status"]);
				$this->draw_error($this->view["title"], $this->view["description"]);
			}
			else if($this->view["type"] == "file") {
				header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
				header("Cache-Control: public"); // needed for internet explorer
				if($this->view["application-type"] != null)
					header("Content-Type: ".$this->view["application-type"]);
				if($this->view["transfer-encoding"] != null)
					header("Content-Transfer-Encoding: ".$this->view["transfer-encoding"]);
				header("Content-Length:".filesize($this->view["file"]));
				if($this->view["newname"] != null) 
					header("Content-Disposition: attachment; filename=".$this->view["newname"]);
				echo file_get_contents($this->view["file"]);

				die();
			}
			else if($this->view["type"] == "content"){		
				header('Content-Type: '.$this->view["content"]);		
				echo $this->view["data"]; 
			}else{
				$this->template_parse($this->view["template"], $this->view["model"]);
			}
		}else{
			$handler = $this->root->router->_data["module"][0];
			$plugin_page = $this->root->module_manager->hook_call("page.".$handler);
			if($_GET["__type"] != "ajax"){
				echo "<script>var _PAGE_URL = \"".Router::url()."\";</script>";
			}
			if( $plugin_page["called"] == 0 ){		
				http_response_code(404);				
				if(!Utilities::isErrorPage()){
					$this->draw_error("Module not a found!","Module with name \"".$handler."\" not a found! Please conact administrator of page thanks.");
				}						
			}else{				
				echo $plugin_page["output"];
			}
		}
	}

	public function template_parse($filename, $model = NULL, $onlycompile = false, &$outputFile = null){		
		//Owerload by template		
		if(substr($filename,0,strlen(_ROOT_DIR)) == _ROOT_DIR) {
			if(file_exists($filename)){
				$template_filename = str_replace(_ROOT_DIR, "", $filename);
				$new_filename = _ROOT_DIR."/templates/".$this->root->config->get("style").$template_filename;

				if(file_exists($new_filename)) {
					$filename = $new_filename;
				}
			}
		}

		$name = explode("/", $filename);
		$fnam = end($name);
		$name = explode(".", $fnam);
		$name = str_replace(".".end($name),"",$fnam);
		$html = file_get_contents($filename);		


		$hash = sha1($html);
		if(_DEBUG)
			$hash = "debug";
		
		$file = _ROOT_DIR."/temp/templates/".$this->root->router->_data["module"][0].".".$name.".".$hash.".template.php";
		$outputFile = $file;

		if(file_exists($file) && !_DEBUG){
			if(!$onlycompile)
				include($file);
			return "";
		}

		//$parser = new Parser($html);
		$output = "";
		if($fnam == "templatte"){
			$compiler = new Compiler($html);
			$compiler->program();
			$output = $out = $compiler->output;
		}else{
			$htmlFixed = Utilities::closetags($html);
			$temp = new Templater($htmlFixed);
			//$temp->disableFunctions(); //Hmm maybe?!
			$output = $out = $temp->template();
		}

		$file = _ROOT_DIR."/temp/templates/".$this->root->router->_data["module"][0].".".$name.".".$hash.".template.php";
		//if(!file_exists($file))
/*
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		//$dom->loadHTML($compiler->output, LIBXML_HTML_NOIMPLIED);
		$dom->loadHTML(mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED);
		$dom->formatOutput = true;
		$out = $dom->saveXML($dom->documentElement);
		//$out = str_replace("&lt;", "<", $out);
		//$out = str_replace("&gt;", ">", $out);		
		//$out = $compiler->output;
*/
		//$format = new Format;
		//$formatted_html = $format->HTML($output);
		//$out = $formatted_html;

		file_put_contents($file, $out);
		if(!$onlycompile)
			include($file);
		return "";
	}

	public function add_style($url, $type = "text/css"){
		$this->styles[] = array("url" => $url, "type" => $type);
	}

	public function add_script($url, $inhead = true){
		$this->script[] = array("url" => $url, "inhead" => $inhead);
	}
}

function default_page_formater($t, &$output){
	return Page::default_page_formater($t, $output);
}

//For better page view :3
class Controller {

	private $root = null;
	public function __construct($root, $nocache = false){
		$this->root = $root;

		if($nocache){
			$this->NoCache();
		}

		$this->template = $root->config->get("style");
	}

	/**
	 * 	@param Bool $show Show the title on page
	 */
	public function SetTitle($title, $show = true){
		$this->root->config->set("pre-title", $title);
		if($show !== true){
			$this->root->config->set("show-title", $show);
		}
	}

	public function NoCache(){
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	//Allow cors origin
	public function Cors() {
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
			header('Access-Control-Allow-Headers: token, Content-Type');
			header('Access-Control-Max-Age: 1728000');
			header('Content-Length: 0');
			header('Content-Type: text/plain');
			die();
		}
	
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/json');
	}

	public function getContainer() {
		return Bootstrap::$self->getContainer();
	}

    public function View($name = null, $model = null, $noTemplate = false, $folder = "views", $debugBack = 1){
        $file = null;
        
		$caller = debug_backtrace()[$debugBack];		
		$class = str_replace("controller", "", strtolower($caller["class"]));

        if(!is_string($name)){
            $model = $name;
            $name = $caller["function"];
		}
		$name = str_replace("controller", "", strtolower($name));
		
		/*if(file_exists(_ROOT_DIR . "/templates/".$this->template."/views/".$class."/" . $name . ".view"))
			$file = _ROOT_DIR . "/templates/".$this->template."/views/".$class."/" . $name . ".view";  
		else*/ if(file_exists(_ROOT_DIR . "/".$folder."/".$class."/" . $name . ".view"))
            $file = _ROOT_DIR . "/".$folder."/".$class."/" . $name . ".view";  
        else if(file_exists(_ROOT_DIR . "/".$folder."/".$class."/" . $name . ".templatte"))
			$file = _ROOT_DIR . "/".$folder."/".$class."/" . $name . ".templatte";   

        return array(
			"type" => "html",
			"template" => $file, 
			"view" => $name, 
			"model" => $model,
			"ajax" => $noTemplate,
			"folder" => $folder
		);
	}

	public function Error($title, $description, $status){
		return array(
			"type" => "error", 
			"ajax" => false,
			"title" => $title,
			"description" => $description,
			"status" => $status
		);
	}
	
	public function Json($data){
		return array(
			"type" => "json", 
			"ajax" => true,
			"data" => $data
		);
	}

	public function Text($content, $textarea = false){
		return array(
			"type" => "text", 
			"ajax" => true,
			"data" => $content,
			"textarea" => $textarea
		);
	}

	public function Content($data, $type){
		return array(
			"type" => "content",
			"ajax" => true,
			"data" => $data,
			"content" => $type
		);
	}

	public function Redirect($url){
		return array(
			"type" => "redirect",
			"ajax" => false,
			"data" => $url
		);
	}

	public function RedirectToAction($action, $arguments) {
		return array(
			"type" => "action",
			"action" => $action,
			"arguments" => $arguments
		);
	}

	public function File($file, $type = null, $newname = null, $encoding = null) {
		return array(
			"type" => "file",
			"ajax" => true,
			"file" => $file,
			"application-type" => $type,
			"transfer-encoding" => $encoding,
			"newname" => $newname
		);
	}

	public function GET_ARRAY($name){
		return internal_GET_ARRAY($name);
	}
}

function internal_GET_ARRAY($name){
	$array = array();
	foreach($_GET as $key => $value) {
		if(substr($key, 0, strlen($name) + 1) == $name."_"){
			$array[substr($key, strlen($name) + 1)] = $value;
		}
	}
	return count($array) == 0? null: $array;
}

/**
 * Table creator for views
 */
function table($id, $data_url, $columns){
	$col_data = [];
	$table = "<table id='table-".$id."' class='table table-ajax'>";
		$table.= "<thead>";
		foreach($columns as $key => $column) {
			$table.= "<th id='table-".$id."-".$key."' ".(isset($column["width"])?"width='".$column["width"]."'":"")." ".(isset($column["title"])?"title='".$column["title"]."'":"").">";
				$table.= "<span>".t($column["name"])."</span>";
			$table.= "</th>";
			$col_data[] = "\"".$key."\"";
		}
		$table.= "</thead>";
		$table.= "<tbody><tr class=loading-line><td colspan=".count($columns).">".t("Loading")."...</td></tr></tbody>";
	$table.= "</table>";
	$table.= "<script>var table_".$id." = new TableManager('".$id."', '".$data_url."', [".implode(", ", $col_data)."]);</script>";
	return $table;
}

function tablePaginator($id){
	$table = "<div id='table-".$id."-paginator' class=table-paginator>";
		$table.= "<div class=left>".t("Totaly")." <span class=total-count>0</span> ".t("records").".</div>";
		$table.= "<div class=right><span>".t("Page")." <span class=page-number>0</span> ".t("@from")." <span class=page-total>0</span></span> <span class=paginator></span></div>";
	$table.= "</div>";
	return $table;
}

class Compiler {
	public function __construct($code){
	  $this->pos = -1;
	  $this->symbols = array(
		"@" => "AT",
		"{" => "BEGIN",
		"}" => "END",
		"<" => "TBEGIN",
		">" => "TEND",
		"=" => "EQUAL",
		"/" => "DIV",
		"(" => "OPEN",
		")" => "CLOSE",
		"," => "COMMA",
		"+" => "PLUS",
		"-" => "MINUS",
		"*" => "MUL",
		"!" => "NOT",
		"&" => "AMP",
		"|" => "VERT",
		"." => "DOT",
		":" => "COLON"
	  );
	  $this->nopairtags = array(
		"br",
		"input",
		"hr"
	  );
	  $this->code = $code."\0";
	  $this->code = utf8_str_split($this->code);
	  $this->tokens = array();
	  $this->debugEnable = false;
	  $this->compile();    
	  //echo "\n\n";
	  $this->pos = 0;
	  $this->mode = "HTML";
	  $this->output = "";
	}
  
	public function reset($tkns){
	  $this->pos = 0;
	  $this->mode = "EVAL";
	  $this->tokens = $tkns;
	}
  
	public function program(){
		$w = $this->getNextToken();
		
		if($w[0] == "EVAL"){
		  $this->mode = "EVAL";
		  $this->output.= "<?php\n";
		  $this->block(false);
		  $this->output.= "\n?>";
		  $this->mode = "HTML";
		}else{
		  $this->pos--;
		  $savemode = $this->mode;
		  $this->mode = "HTML";
		  $this->block(false);
		  $this->mode = $savemode;
		}
	}
  
	public function block($endByEND){
		$w = $this->getNextToken();
	
		while($w[0] != "\0" && ($w[0] != "END" || !$endByEND)){		  
		  if($w[0] == "VAR"){
			if($this->mode == "HTML"){
			  $this->output.= $w[1];
			}else{
			  $this->catchVar($w[1]);
			}
		  }else if($w[0] == "TBEGIN"){
			$this->catchTag();
		  }else if($w[0] == "TBEGINEND" || $w[0] == "TENDBEGIN"){
			break;
		  }else if($w[0] == "AT"){
			if($this->mode == "HTML")
			  $this->output.= "\n<?php \n";			
			$s = $this->mode;
			$this->mode = "EVAL";
			//echo "\n---".$this->_getNextToken(false, 0)[1];
			$qc = $this->_getNextToken(false, 0);
			if(!$this->isRegistered($qc))
			  $this->output.= " echo ";
			$this->catchVar("");
			if(!$this->isRegistered($qc))
			  $this->output.= ";";
			$this->mode = $s;
			if($this->mode == "HTML")
			  $this->output.= " ?>";
		  }else if($w[0] == "NUMBER"){
			  $this->output.= $w[1];
		  }
	
		  $w = $this->getNextToken();
		}
	}
  
	public function isRegistered($t){
	  if(count($t) == 1) 
		return false;

	  $t[1] = trim($t[1]);
  
	  if($t[1] == "if" || $t[1] == "else" || $t[1] == "for")
		return true;
	  return false;
	}
  
	public function catchTag(){
		$w = $this->getNextToken();
		$tag = trim($w[1]);
		$m = $this->mode;
		if($m != "HTML"){
			$this->output.= "?>\n";
		}
		$this->output.= "<".$tag;
		
		$w2 = $this->ce()[1];
		if($w2[0] != "TBEGINEND"){
		$r = $this->catchParams();
		$i = 0;
		foreach($r["props"] as $q => $t){			
			$showit = true;
			$condpo = "";

			if(isset($r["show"][$q])){
				foreach($r["show"][$q] as $ql => $pl){
					if(substr($pl["cond"], 0, 1) == "\""){
						$pl["cond"] = substr($pl["cond"], 1, strlen($pl["cond"]) - 2);
					}

					if($ql == "if"){
						if($pl["cond"] == "false"){
							$showit = false;
						}else{
							$condpo = $pl["cond"];				
						}
					}
					//$this->output.= " x-".$q." = \"".$r["show"][$q]["if"]["cond"]."\"";
				}
			}

			if($showit && $condpo == ""){
				$this->output.= " ";
				$this->output.= $q."=".$t["value"];
			}
			else if($condpo != ""){		
				//echo "-->".$showit.", ".$condpo."<br>";
				$this->output.= " ";
				$t["value"] = str_replace("\"","\\\"", $t["value"]);
				$this->output.= "<?php echo (".$condpo."?\"".$q."=".$t["value"]."\":\"\"); ?>";
			}

			$i++;			
		}
		//$this->getNextToken();    
		$xvc = $this->_getNextToken(false, 0)[0];
		if($xvc == "TENDBEGIN" || in_array($tag, $this->nopairtags) || $this->_getNextToken(false, -2)[0] == "TBEGINEND"){
			//$this->getNextToken();
			$this->output.= "/>";  
		}else{
			$this->output.= ">";      
			$this->mode = "HTML";
			//echo "\n---block start--\n";
			$this->block(false);
			$this->mode = $m;
			$this->output.= "</".$tag.">";
			$this->getNextToken();  
			$this->getNextToken();
		}
		}else{
		$this->getNextToken();
		$this->output.= "/>";      
		}
		if($m != "HTML"){
		$this->output.= "\n<?php ";
		}
	}
  
	public function catchParams(){
		$ret = array(
			"props" => array(),
			"show" => array()
		);

		$w = $this->getNextToken();
		while($w[0] != "TEND"){
		  if($w[0] == "VAR"){
			$what = trim($w[1]);
			$w2 = $this->getNextToken();
			if($w2[0] == "ASIGN"){
			  $w3 = $this->getNextToken();
			  if($w3[0] == "VAR" || $w3[0] == "NUMBER")
				$value = $w3[1];
			  else if($w3[0] == "STRING"){
				$cmp = new Compiler($w3[1]);
				//echo "\"".$w3[1]."\"";
				$r = $this->catchInArg($cmp->tokens);
				if(substr($r, 0, 2) == "~/"){
					$r = Router::url() . substr($r, 2);					
				}
				$value = "\"".$r."\"";
			  }else{           
				$this->output.= "<?php echo "; 
				  $this->output.= $this->catchVar($w3[1]);
				$this->output.= "; ?>";
			  }
			}
			$ret["props"][$what] = array("value" => $value);
		  }else if($w[0] == "COLON"){
			  $what = $this->getNextToken()[1];
			  $this->getNextToken();
			  $where = $this->getNextToken()[1];
			  $this->getNextToken();
			  $w3 = $this->getNextToken();
			  
			  if($w3[0] == "VAR" || $w3[0] == "NUMBER")
				$cond = $w3[1];
			  else if($w3[0] == "STRING"){
				$cmp = new Compiler($w3[1]);
				$r = $this->catchInArg($cmp->tokens);								
				if(substr($r, 0, 2) == "~/"){
					$r = Router::url() . substr($r, 2);					
				}
				$cond = "\"".$r."\"";
			  }else{           				
				$cond = $this->catchVar($w3[1]);				
			  }
			  $ret["show"][$what][$where] = array("cond" => $cond);
		  }
		  $w = $this->getNextToken();		  
		}

		return $ret;
	}
  
	public function catchInArg($tokens){    
	  $out = "";
	  for($o = 0; $o < count($tokens); $o++){
		$to = $tokens[$o];
		if($to[0] == "AT"){
		  $o++;
		  if($tokens[$o][0] == "OPEN"){
			//@(xx + 5)
			$o++;
			$tkns = [];
			while(count($tokens) > $o && $tokens[$o][0] != "CLOSE"){
			  $tkns[]= $tokens[$o];
			  $o++;
			}
			$cp = new Compiler("");			
			$cp->reset($tkns);
  
			//$out.= "<?php echo ";
			$cp->prim();
			$out.= $cp->output;
			//$out.= "; ? >";
			//print_r($out); echo "<br>";
		  }else{
			echo "<?php echo $";
			$res = "";
			do {   
			  if($res != ""){ $res.= "."; }                  
			  $res.= $tokens[$o][1];
			  $o++;
			}while($tokens[$o][0] == "DOT");          
			
			if($tokens[$o][0] == "OPEN"){
				$out.= "<?php echo ".$res."(";
				$tkns = [];
				$o++;
				while($tokens[$o][0] != "CLOSE"){
				  $tkns[] = $tokens[$o];
				  $o++;
				}
				$tkns[] = $tokens[$o];
				
				$cp = new Compiler("");
				$cp->reset($tkns);
				$cp->catchFunParams();
				$out.= $cp->output;
  
				$o++;
				$out.= "); ?>";
			}else{
			  $o--;
			  $out.= "<?php echo $".$res."; ?>";
			}          
		  }
		}else{
		  if($o != 0) $out.= "";
		  $out.= $to[1];
		}
	  }
	  return $out;
	}
  
	public function catchVar($name){	
	  $w = $this->getNextToken(); 	  
		  
	  if($w[0] == "ASIGN"){      
		$this->output.= "$" . trim($name) . " = ";
		$this->prim();
		$this->output.= ";\n";      
	  }else if($w[0] == "OPEN"){  
		$qc = $this->_getNextToken(false, -2);		
		if($this->isRegistered($qc)){			
		  $this->doRegistered($qc);
		}else{
		  $this->output.= $name . "(";
			$this->catchFunParams();
		  $this->output.= ")";
		}
	  }else if($w[0] != "\0"){
		if($w[0] != "VAR"){
		  $this->output.= "$".$name;        
		  $this->pos--;
		}else{
		  if($w[1] == "if"){
			$this->catchIf();
		  }else if($w[1] == "else"){
			$this->catchElse();
		  }else if($w[1] == "for"){
			$this->catchFor();
		  }else{          			
			$this->output.= "$".trim($w[1]);
			$w=$this->getNextToken();
			if($w[0] != "DOT") {
				$this->pos--;
			}
			while($w[0] == "DOT"){
			  $w = $this->getCurrentToken();
			  $this->output.= "[\"".$w[1]."\"]";
			  $w = $this->getNextToken();
			}
		  }
		}
	  }
	}
  
	public function doRegistered($t){
	  $t[1] = trim($t[1]);
	  if($t[1] == "if"){
		$this->output.= "if(";
		  $this->catchFunParams();
		$this->output.= ") {\n";
		$this->block(true);
		$this->output.= "}\n";
	  }else if($t[1] == "for"){
		$this->output.= "foreach(";
		$this->catchVar();
		$this->pos++;
		$this->output.= " as ";
		$v = $this->tokens[$this->pos];		
		if(trim($v[1]) == "["){
			$this->pos++;
			//catch range
			throw Exception("Not implemented");
		}else{
			$this->catchVar();			
			$this->pos--;
			$v = $this->tokens[$this->pos];
			$next = $this->getCurrentToken();
			if($next[0] == "AS"){
				$next = $this->getNextToken();
				$this->output.= " => ";
				$this->catchVar();				
			}
		}
		$this->output.= ") {\n";
		$this->block(true);
		$this->output.= "}\n";
	  }
	}
  
	public function catchIf(){        
	  $this->eat("OPEN");
	  $tkns = [];
	  $wo = $this->tokens[$this->pos];
	  $p = 0;
	  while($wo[0] != "END" && (($wo[0] != "CLOSE" && $p == 0) || ($p != 0))){
		$tkns[]= $wo;
		
		if($wo[0] == "OPEN")
		  $p++;
		if($wo[0] == "CLOSE")
		  $p--;
  
		$this->pos++;
		$wo = $this->tokens[$this->pos];
	  }
	  $cp = new Compiler("");
	  $cp->reset($tkns);
	  $this->output.= "if(";
	  $cp->prim();
	  $this->output.= $cp->output;
	  $this->output.= "){\n";
	  $this->block(true);
	  $this->output.= "}\n";

	  $wo = $this->tokens[$this->pos];
	  
	  if(trim($wo[1]) == "else"){
		$this->pos++;
		$this->output.= " else { ";
		$this->block(true);
		$this->output.= "}\n";
	  }

	  //echo "\n---".$name."-".$wo[0]."=>".(count($wo) > 1?$wo[1]:"")."\n";
	}
  
	public function catchFunParams(){    
	  $w = $this->_getNextToken(false, 0);   
	  $p = 0; 
	  while($w[0] != "CLOSE" && $p == 0){
		$this->output.= $this->prim();   
		$w = $this->getNextToken();   
		
		if($w[0] == "OPEN")
		  $p++;
		if($w[0] == "CLOSE")
		  $p--;
  
		if($w[0] != "CLOSE" && $p == 0)
		  $this->output.= ", ";
	  }
	}
  
	public function factor(){
	  $w = $this->getCurrentToken();    
	  $this->debug("factor ----".$w[0]);
  
	  if($w[0] == "STRING")
		$this->output.= "\"".$w[1]."\"";
	  else if($w[0] == "NUMBER")
		$this->output.= $w[1];
	  else {                    
		if($this->_getNextToken(false, 1)[0] == "OPEN")
		  $this->eat($w[0]);  
  
		//echo "---". $w[0];
		//var_dump(debug_backtrace()[0]);
		$this->catchVar($w[1]);  
  
		$this->debug("---".$this->getCurrentToken()[0]);
		if($this->debugEnable){
		  var_dump($this->tokens);
		}
		//$this->debug($this->tokens[$this->pos][0]);
		//$this->debug($this->tokens[$this->pos + 1][0]);
	  }
  
	  if($w[0] == "STRING" || $w[0] == "NUMBER"){
		$this->eat($w[0]);
	  }
	}
  
	public function term(){
	  $this->factor();
	  $w = $this->getCurrentToken();
	  $this->debug("term ----".$w[0]);
  
	  while($w[0] == "MUL" || $w[0] == "DIV"){
		if($w[0] == "MUL"){
		  $this->output.= " * ";
		}else if($w[0] == "DIV"){
		  $this->output.= " / ";
		}
		$this->eat($w[0]);
		$this->factor();
		$w = $this->getCurrentToken();
	  }
	}
  
	public function math(){
	  $this->term();
	  $w = $this->getCurrentToken();
	  $this->debug("math ----".$w[0]);
  
	  while($w[0] == "PLUS" || $w[0] == "MINUS"){
		if($w[0] == "PLUS"){
		  $this->output.= " + ";
		}else if($w[0] == "MINUS"){
		  $this->output.= " - ";
		}
		$this->eat($w[0]);
		$this->term();
		$w = $this->getCurrentToken();
	  }
	}
  
	public function comp(){
	  $this->math();
	  $w = $this->getCurrentToken();
	  $this->debug("comp ----".$w[0]);
  
	  while($w[0] == "EQUAL" || $w[0] == "NOTEQUAL" || $w[0] == "TBEGIN" || $w[0] == "TEND"){
		if($w[0] == "EQUAL"){
		  $this->output.= " == ";
		}else if($w[0] == "NOTEQUAL"){
		  $this->output.= " != ";
		}else if($w[0] == "TBEGIN"){
		  $this->output.= " < ";
		}else if($w[0] == "TEND"){
		  $this->output.= " > ";
		}
		$this->eat($w[0]);
		$this->math();
		$w = $this->getCurrentToken();
	  }
	}  
  
	public function prim(){
	  //$this->getNextToken();
	  $this->comp();
	  $w = $this->getCurrentToken();
	  $this->debug("prim ----".$w[0]);
  
	  while($w[0] == "AND" || $w[0] == "OR"){
		if($w[0] == "AND"){
		  $this->output.= " && ";
		}else if($w[0] == "OR"){
		  $this->output.= " || ";
		}
		$this->eat($w[0]);
		$this->comp();
		$w = $this->getCurrentToken();
	  }
	}
  
	public function debug($t){
	  if($this->debugEnable){
		echo "\n".$t;
	  }
	}
  
	public function eat($what){
	  $c = $this->getCurrentToken();
	  if($c[0] == $what)
		return $this->getNextToken();
	  throw new Exception("want " . $what . " got " . $c[0]);
	}
	public function getNextToken(){
	  return $this->_getNextToken(true, 0);
	}
	public function getCurrentToken(){
	  return $this->_getNextToken(false, 0);
	}
	public function _getNextToken($move, $po){
	  if($this->pos >= count($this->tokens))
		return array("\0");
	  if($move){
		$curr = array($this->tokens[$this->pos++], @$this->tokens[$this->pos], @$this->tokens[$this->pos + 1]);
	  }else{
		$curr = array(@$this->tokens[$this->pos + $po], @$this->tokens[$this->pos + 1 + $po], @$this->tokens[$this->pos + 2 + $po]);
	  }
	  if($curr[0][0] == "AT" && $curr[1][0] == "BEGIN"){
		if($move) 
		  $this->pos+=1;
		return array("EVAL"); 
	  }else if($curr[0][0] == "VARIABLE"){
		return array("VAR", $curr[0][1]); 
	  }else if($curr[0][0] == "EQUAL" && $curr[1][0] == "EQUAL"){
		if($move) 
		  $this->pos+=1;
		return array("EQUAL", $curr[0][1]);
	  }else if($curr[0][0] == "EQUAL" && $curr[1][0] == "TEND"){
		if($move) 
		  $this->pos+=1;
		return array("AS", $curr[0][1]);
	  }else if($curr[0][0] == "EQUAL"){
		return array("ASIGN");
	  }else if($curr[0][0] == "NOT" && $curr[1][0] == "EQUAL"){
		if($move) 
		  $this->pos+=1;
		return array("NOTEQUAL");
	  }else if($curr[0][0] == "STRING"){
		return array("STRING", $curr[0][1]);
	  }else if($curr[0][0] == "NUMBER"){
		return array("NUMBER", $curr[0][1]);
	  }else if($curr[0][0] == "TBEGIN" && $curr[1][0] == "DIV"){
		return array("TENDBEGIN", $curr[0][1]);
	  }else if($curr[0][0] == "TBEGIN"){
		return array("TBEGIN", $curr[0][1]);
	  }else if($curr[0][0] == "DIV" && $curr[1][0] == "TEND"){
		return array("TBEGINEND", $curr[0][1]);
	  }else if($curr[0][0] == "TEND"){
		return array("TEND", $curr[0][1]);
	  }else if($curr[0][0] == "OPEN"){
		return array("OPEN", $curr[0][1]);
	  }else if($curr[0][0] == "CLOSE"){
		return array("CLOSE", $curr[0][1]);
	  }else if($curr[0][0] == "COMMA"){
		return array("COMMA", $curr[0][1]);
	  }else if($curr[0][0] == "AT"){
		return array("AT", $curr[0][1]);
	  }else if($curr[0][0] == "AMP" && $curr[1][0] == "AMP"){
		if($move) 
		  $this->pos+=1;
		return array("AND", $curr[0][1]);
	  }else if($curr[0][0] == "VERT" && $curr[1][0] == "VERT"){
		if($move) 
		  $this->pos+=1;
		return array("OR", $curr[0][1]);
	  }
	  return array($curr[0][0], $curr[0][1]);
	}
  
	public function compile(){
	  while(true){
		$token = $this->getNext();
		if($token == "EOF")
		  break;
		$this->tokens[]= $token;
	  }
	}
  
	public function current(){ return $this->code[$this->pos]; }
	public function c() { return $this->current(); }
	public function ce() { 
	  return array(
		$this->code[$this->pos], 
		@$this->code[$this->pos + 1], 
		@$this->code[$this->pos + 2]
	  ); 
	}
	public function next() { return $this->code[++$this->pos]; }
	public function n() { return $this->next(); }  
  
	public function getNext(){
	  $char = $this->next();
	  if($char == "\0")
		return "EOF";      

	  $wsp = $this->eatWhitespace();
	  if(strlen($wsp) > 0 && $this->tokens[count($this->tokens) - 1][1] == ">" && $wsp[0] == "\n"){
		$wsp = substr($wsp, 1);
	  }
	  $char = $this->c();
	  $chas = $this->ce();    
  
	  if(($chas[0] == "/" && $chas[1] == "/") || $chas[0] == "/" && $chas[1] == "*")
		$this->comment();       
  
	  if(@$this->symbols[$char] != NULL)
		return array($this->symbols[$char], $char);    
  
	  if(ctype_alpha($char) || $char == "$" || $char == "_" || ord($char) > 190)
		return array("VARIABLE", $wsp . $this->variable());
  
	  if($char == "\"")
		return array("STRING", $this->string("\""));

	  if($char == "'")
		return array("STRING", $this->string("'"));
  
	  if(is_numeric($char))
		return array("NUMBER", $this->number());
  
	  return array("UNKWN", $char);
	}  
  
	public function variable(){
	  $char = $this->c();
	  $ret = "";
	  //283, 353, 269, 345, 382
	  do {      
		$ret.= $char;
		$char = $this->next();
	  }while(ctype_alpha($char) || $char == "_" || is_numeric($char) || ord($char) > 190);
  
	  $this->pos--;
	  return $ret;
	}
  
	public function eatWhitespace(){
		$ret = "";
		while(ctype_space($this->c()) || ctype_cntrl($this->c()) || $this->c() == "\n" || $this->c() == "\r"){
		  $ret.= $this->c();
		  $this->n();
		}
		return $ret;
	}
  
	public function number(){
	  $ret = "";
	  do {
		$ch = $this->c();
		$ret.= $ch;
		$this->next();
	  }while(is_numeric($this->c()) || ($this->c() == "." && strpos($ret, ".") == false));
  
	  if($ret != "")
		$this->pos--;
  
	  return $ret;
	}
  
	public function string($end){
	  $skip = false;
	  $ret = "";
	  while($this->next() != $end || $skip){
		$skip = false;
		$char = $this->c();
		if($char == "\\")
		  $skip = true;
		$ret.= $char;
	  }
	  return $ret;
	}
  }

class Format {
	private $input = '';
	private $output = '';
	private $tabs = 0;
	private $in_tag = FALSE;
	private $in_comment = FALSE;
	private $in_content = FALSE;
	private $inline_tag = FALSE;
	private $input_index = 0;
	
	public function HTML($input)
	{
		$this->input = $input;
		$this->output = '';
		
		$starting_index = 0;
		
		if (preg_match('/<\!doctype/i', $this->input)) {
			$starting_index = strpos($this->input, '>') + 1;
			$this->output .= substr($this->input, 0, $starting_index);
		}
		
		for ($this->input_index = $starting_index; $this->input_index < strlen($this->input); $this->input_index++) {
			if ($this->in_comment) {
				$this->parse_comment();
			} elseif ($this->in_tag) {
				$this->parse_inner_tag();
			} elseif ($this->inline_tag) {
				$this->parse_inner_inline_tag();
			} else {
				if (preg_match('/[\r\n\t]/', $this->input[$this->input_index])) {
					continue;
				} elseif ($this->input[$this->input_index] == '<') {
					if ( ! $this->is_inline_tag()) {
						$this->in_content = FALSE;
					}
					$this->parse_tag();
				} elseif ( ! $this->in_content) {
					if ( ! $this->inline_tag) {
						$this->output .= "\n" . str_repeat("\t", $this->tabs);
					}
					$this->in_content = TRUE;
				}
				$this->output .= $this->input[$this->input_index];
			}
		}
		
		return $this->output;
	}
	
	private function parse_comment()
	{
		if ($this->is_end_comment()) {
			$this->in_comment = FALSE;
			$this->output .= '-->';
			$this->input_index += 3;
		} else {
			$this->output .= $this->input[$this->input_index];
		}
	}
	
	private function parse_inner_tag()
	{
		if ($this->input[$this->input_index] == '>') {
			$this->in_tag = FALSE;
			$this->output .= '>';
		} else {
			$this->output .= $this->input[$this->input_index];
		}
	}
	
	private function parse_inner_inline_tag()
	{
		if ($this->input[$this->input_index] == '>') {
			$this->inline_tag = FALSE;
			$this->decrement_tabs();
			$this->output .= '>';
		} else {
			$this->output .= $this->input[$this->input_index];
		}
	}
	
	private function parse_tag()
	{
		if ($this->is_comment()) {
			$this->output .= "\n" . str_repeat("\t", $this->tabs);
			$this->in_comment = TRUE;
		} elseif ($this->is_end_tag()) {
			$this->in_tag = TRUE;
			$this->inline_tag = FALSE;
			$this->decrement_tabs();
			if ( ! $this->is_inline_tag() AND ! $this->is_tag_empty()) {
				$this->output .= "\n" . str_repeat("\t", $this->tabs);
			}
		} else {
			$this->in_tag = TRUE;
			if ( ! $this->in_content AND ! $this->inline_tag) {
				$this->output .= "\n" . str_repeat("\t", $this->tabs);
			}
			if ( ! $this->is_closed_tag()) {
				$this->tabs++;
			}
			if ($this->is_inline_tag()) {
				$this->inline_tag = TRUE;
			}
		}
	}
	
	private function is_end_tag()
	{
		for ($input_index = $this->input_index; $input_index < strlen($this->input); $input_index++) {
			if ($this->input[$input_index] == '<' AND $this->input[$input_index + 1] == '/') {
				return true;
			} elseif ($this->input[$input_index] == '<' AND $this->input[$input_index + 1] == '!') {
				return true;
			} elseif ($this->input[$input_index] == '>') {
				return false;
			}
		}
		return false;
	}
	
	private function decrement_tabs()
	{
		$this->tabs--;
		if ($this->tabs < 0) {
			$this->tabs = 0;
		}
	}
	
	private function is_comment()
	{
		if ($this->input[$this->input_index] == '<'
		AND $this->input[$this->input_index + 1] == '!'
		AND $this->input[$this->input_index + 2] == '-'
		AND $this->input[$this->input_index + 3] == '-') {
			return true;
		} else {
			return false;
		}
	}
	
	private function is_end_comment()
	{
		if ($this->input[$this->input_index] == '-'
		AND $this->input[$this->input_index + 1] == '-'
		AND $this->input[$this->input_index + 2] == '>') {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	private function is_tag_empty()
	{
		$current_tag = $this->get_current_tag($this->input_index + 2);
		$in_tag = FALSE;
		
		for ($input_index = $this->input_index - 1; $input_index >= 0; $input_index--) {
			if ( ! $in_tag) {
				if ($this->input[$input_index] == '>') {
					$in_tag = TRUE;
				} elseif ( ! preg_match('/\s/', $this->input[$input_index])) {
					return FALSE;
				}
			} else {
				if ($this->input[$input_index] == '<') {
					if ($current_tag == $this->get_current_tag($input_index + 1)) {
						return TRUE;
					} else {
						return FALSE;
					}
				}
			}
		}
		return TRUE;
	}
	
	private function get_current_tag($input_index)
	{
		$current_tag = '';
		
		for ($input_index; $input_index < strlen($this->input); $input_index++) {
			if ($this->input[$input_index] == '<') {
				continue;
			} elseif ($this->input[$input_index] == '>' OR preg_match('/\s/', $this->input[$input_index])) {
				return $current_tag;
			} else {
				$current_tag .= $this->input[$input_index];
			}
		}
		
		return $current_tag;
	}
	
	private function is_closed_tag()
	{
		$closed_tags = array(
			'meta', 'link', 'img', 'hr', 'br', 'input',
		);
		
		$current_tag = '';
		
		for ($input_index = $this->input_index; $input_index < strlen($this->input); $input_index++) {
			if ($this->input[$input_index] == '<') {
				continue;
			} elseif (preg_match('/\s/', $this->input[$input_index])) {
				break;
			} else {
				$current_tag .= $this->input[$input_index];
			}
		}
		
		if (in_array($current_tag, $closed_tags)) {
			return true;
		} else {
			return false;
		}
	}
	
	private function is_inline_tag()
	{
		$inline_tags = array(
			'title', 'a', 'span', 'abbr', 'acronym', 'b', 'basefont', 'bdo', 'big', 'cite', 'code', 'dfn', 'em', 'font', 'i', 'kbd', 'q', 's', 'samp', 'small', 'strike', 'strong', 'sub', 'sup', 'textarea', 'tt', 'u', 'var', 'del', 'pre',
		);
		
		$current_tag = '';
		
		for ($input_index = $this->input_index; $input_index < strlen($this->input); $input_index++) {
			if ($this->input[$input_index] == '<' OR $this->input[$input_index] == '/') {
				continue;
			} elseif (preg_match('/\s/', $this->input[$input_index]) OR $this->input[$input_index] == '>') {
				break;
			} else {
				$current_tag .= $this->input[$input_index];
			}
		}
		
		if (in_array($current_tag, $inline_tags)) {
			return true;
		} else {
			return false;
		}
	}
}

Templater::$components["form"] = array(
	"pair" => true,
	"callback" => function($formName) {
		$result = dibi::query("SELECT * FROM :prefix:form WHERE name=%s", $formName)->fetch();		

		$utilities = Bootstrap::$self->getContainer()->get('utilities');
		$page = Bootstrap::$self->getContainer()->get('page');

		if(isset($_POST["form_id"]) && $_POST["form_id"] == $result['id']){
			$state = $utilities->SendForm($formName);

			if($state["success"] != "") {
				$page->error_box($state["success"], "ok");
			}

			$warnings = "";
			foreach($state["warnings"] as $warning) {
				$warnings.="<div>".$warning."</div>";
			}
			if($warnings != "") { $page->error_box($warnings, "warning"); }

			$errors = "";
			foreach($state["errors"] as $error) {
				$errors.="<div>".$error."</div>";
			}
			if($errors != "") { $page->error_box($errors, "error"); }

		}
		if(isset($_POST["form_name"]) && $_POST["form_name"] == $formName){
			if($formName == "contact") {				
				$name = isset($_POST["name"])?$_POST["name"]:"";
				$email = isset($_POST["email"])?$_POST["email"]:"";
				$phone = isset($_POST["phone"])?$_POST["phone"]:"";
				$subject = isset($_POST["subject"])?$_POST["subject"]:"";
				$message = isset($_POST["message"])?$_POST["message"]:"";

				if($name == "" || $email == "" || $message == ""){
					$_GET["form"][$formName]["errors"][] = t("You must have all required fields");
				}
				if(!Utilities::isEmail($email)){
					$_GET["form"][$formName]["errors"][] = t("Email is not valid");
				}

				if(!isset($_GET["form"][$formName]["errors"])) {
					$utilities->sendemail($email, $subject, t("You have new message from web").": <br/>".$message, null, false, true);
					$_GET["form"]["ok"] = true;
				}
			}
		}

		echo "<a name='component_form_".$result["id"]."'></a>";
		echo "<form action='#component_form_".$result["id"]."' method=post>";
		if($result != null){
			echo "<input type=hidden value='".$result['id']."' name=form_id>";
		}else{
			echo "<input type=hidden value='".$formName."' name=form_name>";
		}
	},
	"callback_end" => function($formName) {
		echo "</form>";
	}
);

 class Templater {
    public function __construct($content, $fu = false, $parent = NULL){
        $this->content = $content;
		
		$this->fu = $fu;
		$this->parent = $parent;
		$this->prev = "";
        $this->pos = 0;
        $this->chars = utf8Split($this->content);
        $this->tokens = [];
		$this->catchPHP = false;
		$this->catchTagAfter = 1;
		$this->tagName = "";
		$this->catchTagName = false;
		$this->functionAlias = [];
		$this->parse();		
		$this->tokens[] = array("EOF", "\e");	

		$this->_isDisabledFunctions = false;
	}

	public static $components = [];
	public static $globalmodel = [];

	public function disableFunctions(){
		$this->_isDisabledFunctions = true;
	}
	public function isDisabledFunctions(){
		if($this->parent != NULL) {
			return $this->parent->isDisabledFunctions();
		}
		return $this->_isDisabledFunctions;
	}
	
	public function getFunctionAlias($alias) {
		if($this->parent != NULL) {
			return $this->parent->getFunctionAlias($alias);
		}
		if(isset($this->functionAlias[$alias]["name"]))
			return $this->functionAlias[$alias]["name"];		
		return null;
	}

	public function setFunctionAlias($alias, $name) {
		if($this->parent != NULL) {
			return $this->parent->setFunctionAlias($alias, $name);
		}
		$this->functionAlias[$alias] = array("name" => $name);
	}

	public function allFunctionAlias(){
		if($this->parent != NULL) {
			return $this->parent->allFunctionAlias();
		}
		return $this->functionAlias;
	}

	private function getPos(){
		if($this->parent == NULL)
			return $this->pos;
		return intVal($this->pos) + $this->parent->getPos();
	}

	private function getContent(){
		if($this->parent == NULL)
			return $this->content;
		return $this->parent->getContent();
	}
    
    public function template(){
        $this->output = "";
        $this->pos = 0;
		$this->skipOpen = false;
		
        $this->output.= $this->block(null);
		return $this->output;
	}
	
	public function templateScript($str){
		if(!(strpos($str, '{') !== false)){	
			return $str;
		}

		$ret = "";
		$chars = utf8Split($str);
		$take = "";
		$open = 0;

		for($i = 0; $i < count($chars); $i++){
			$ch = $chars[$i];
			$ch2 = null;
			if($i + 1 < count($chars)){ $ch2 = $chars[$i+1]; }

			if($ch == "}" && $open == 1){
				$str = $take;
				$temp = new Templater("{".$str."}", true, $this);
				$ret.= $temp->template();
				$open = 0;
				$take = "";
			}
			else if($open > 0){
				$take.= $ch;
				if($ch == "{") {
					$open++;
				}
			}
			else if($ch == "{" && $ch2 != "\n" && $ch2 != "\"" && $ch2 != "'" && $ch2 != " " && ord($ch2) != 13 && $ch2 != "}"){				
				$open++;
			}		
			else{
				$ret.= $ch;
				if($ch == "{" && $ch2 == " "){ $i++; }
			}	
		}

		return $ret;
	}
	
	private $debugInfo = "";

    public function block($end, $endIn = NULL){
        $ret = "";
        
		if($end == null) $end = "EOF";

		//var_dump($this->tagName);
		//echo "<hr/>";
        
        $token = $this->getNextToken();        
        while($token[0] != "EOF" && ($token[0] != $end || $endIn != NULL)){
			if($end == "BEGIN" || $end == "BEGIN_"){
				$nextToken = isset($this->tokens[$this->pos+1])?$this->tokens[$this->pos+1]:array("", "");
				if(($token[0] == $end || $token[0] == $end."_") && $nextToken[0] == "PHP" && $nextToken[1] == $endIn){
					break;
				}
			}

            if($token[0] == "DIV/BEGIN"){
				$ret.= $this->catchDiv();				
			}else if($token[0] == "VAR"){
				if($this->tagName == "script"){
					$ret.= $this->templateScript($token[1]);
				}else{
					$ret.= $token[1]."";					
				}				
				$this->eat($token[0]);
			}else if($token[0] == "STRING"){
				$str = substr($token[1], 1, strlen($token[1]) - 2);
				if(strpos($str, '{') !== false){					
					$temp = new Templater($str, true, $this);
					$ret.= "'".$temp->template()."'";
					//var_dump($str, $ret);
					//exit;
				}else{
					$ret.= $token[1];
				}			
				$this->eat($token[0]);
			}else if($token[0] == "BEGIN" || $token[0] == "BEGIN_"){
				if($this->tokens[$this->pos+1][1][0] == "\r"){
					$ret.= $this->tokens[$this->pos][1] . $this->tokens[$this->pos+1][1] . $this->tokens[$this->pos+2][1];
					$this->pos+=2;
				}else{
					$ret.= $this->catchControl($token[0]);
				}                
			}else if($token[0] == "END"){
				$ret.="}";   
				$this->eat($token[0]);         
			}else{
                $this->eat($token[0]);
            }
            $token = $this->getNextToken();
        }
        
        return $ret;
	}
	
	private function parserParams($params, $len = null){
		$outpar = [];
		$open = null;
		$index = 0;
		$buffer = "";
		for($i = 0; $i < strlen($params); $i++) {
			$c = substr($params, $i, 1);
			if($open != null && $c != $open){
				$buffer.= $c;
				continue;
			}
			if($c=="\"" || $c=="'" || $c=="("){
				if($c == $open){
					$open = null;
					$buffer.= $c;
					continue;
				}
				$buffer.= $c;
				$open = $c;

				if($open == "(") $open = ")";
				continue;
			}

			if($c != ",")
				$buffer.= $c;

			if($c == "," || $i == strlen($params)){
				$outpar[$index++] = trim($buffer);
				$buffer = "";
				if($len != null && $len == $index) {
					$outpar[$index++] = trim(substr($params, $i+1));
					break;
				}
				continue;
			}
		}

		if($buffer != ""){
			$outpar[$index++] = trim($buffer);
		}

		return $outpar;
	}

	private function replaceFunctionAlias($content) {	
		return preg_replace_callback('/([^A-Za-z]|^)([A-Za-z0-9_]+)\(/m', function($match){
			$found = $this->getFunctionAlias($match[2]);
			if($found != null) {
				if($this->isDisabledFunctions()) {
					return $match[1]."Utilities::vardump(";
				} 
				return $match[1].$found."(";
			}
			return $match[0];
		}, $content);
	}

	public function catchControl($c){
		$ret = "";

		$this->eat($c);
		if($c == "BEGIN_"){		
			$ret.=" ";
		}

		$code = $this->getNextToken();

		$wha = explode(" ", $code[1], 2);
		$who = trim($wha[0]);

		if(substr($who[0], 0, 1) == "~" ){
			$url = trim(substr($code[1], 1, strlen($code[1]) - 1));
			if(substr($url, 0, 1) == "/") 
				$url = substr($url, 1);
			$ret.="<?php echo Router::url(); ?>".$url;
		}else if($who == "continueif"){
			$ret.="<?php if(".$this->replaceFunctionAlias($wha[1]).") { continue; } ?>";
		}else if($who == "breakif"){
			$ret.="<?php if(".$this->replaceFunctionAlias($wha[1]).") { break; } ?>";
		}else if($who == "if"){
			$ret.="<?php if(".$this->replaceFunctionAlias($wha[1]).") { ?>";
		}else if($who == "isset"){
			$ret.="<?php if(isset(".$this->replaceFunctionAlias($wha[1]).")) { ?>";
		}else if($who == "elseif"){
			$ret.="<?php } else if(".$this->replaceFunctionAlias($wha[1]).") { ?>";
		}else if($who == "else"){
			$ret.="<?php } else { ?>";
		}else if($who == "/if"){
			$ret.="<?php } ?>";
		}else if($who == "for"){
			if(strpos($wha[1], ' as ') !== false){
				$ret.="<?php foreach(".$this->replaceFunctionAlias($wha[1]).") { ?>";
			}else{
				$ret.="<?php for(".$this->replaceFunctionAlias($wha[1]).") { ?>";
			}
		}else if($who == "/for"){
			$ret.="<?php } ?>";
		}else if($who == "while"){
			$ret.="<?php while(".$this->replaceFunctionAlias($wha[1]).") { ?>";
		}else if($who == "/while"){
			$ret.="<?php } ?>";
		}else if($who == "default"){
			$ro = explode("=", $wha[1], 2);
			$ret.="<?php if(!isset(".$ro[0].")) { ".$this->replaceFunctionAlias($wha[1])."; } ?>";
		}else if($who == "include"){
			$params = $this->parserParams($wha[1], 1);
			$ret.= "<?php Bootstrap::\$self->getContainer()->get('page')->template_parse(_ROOT_DIR . \"/views/\".".$params[0].".\".view\", array(".$this->replaceFunctionAlias($params[1]).")); ?>";
		}else if($who == "var"){
			$ret.="<?php ".$wha[1]."; ?>";
		}else if($who == "comment") {
			$this->eat("PHP");
			$this->eat("END");
			//$ret.= "<!-- ".$this->block("BEGIN", "/comment")." -->";
			$this->block("BEGIN", "/comment");
			$this->eat($this->getNextToken()[0]);
		}else if($who == "%" && trim(substr($code[1], strlen($code[1]) - 1, 1)) == "%") {
			//hidden comment
		}else if($who == "capture"){
			$name = $wha[1];
			$this->eat("PHP");
			$this->eat("END");
			$ret.= "<?php ob_start(); ?>";
			$ret.= $this->block("BEGIN", "/capture");
			if(substr($name, 0, 1) == "'" || substr($name, 0, 1) == '"'){
				$name = substr($name, 1, strlen($name) - 2);
				$ret.= "<?php \$".$name." = ob_get_contents(); ob_end_clean(); ?>";
			}else{
				$ret.= "<?php define('".$name."', ob_get_contents()); ob_end_clean(); ?>";
			}			
			$this->eat($this->getNextToken()[0]);
		}else if($who == "function"){
			$name = $wha[1];
			$this->eat("PHP");
			$this->eat("END");

			$n = explode("(", $name, 2);
			
			$alias = $n[0];
			$name = $alias."_".Strings::random(5);
			$this->setFunctionAlias($alias, $name);

			if($this->isDisabledFunctions()) {
				$ret.= "<b style='color:red'>Function ".$alias." can't be created because functions are disabled! It will be replaced with var_dump.</b>";
				$this->block("BEGIN", "/function");
			}else{
				if(count($n) == 1){
					$ret.= "<?php function ".$name."() {";
				}else{
					$ret.= "<?php function ".$name."(".$n[1]." {";
				}			
				$ret.= $this->replaceFunctionAlias($this->block("BEGIN", "/function"));	
				$ret.= "} ?>";	
			}
			$this->eat($this->getNextToken()[0]);
		}else{
			if(isset(Templater::$components[$who])){
				$f = Templater::$components[$who];

				$this->eat("PHP");
				$this->eat("END");

				if($f["pair"]) {					
					$inside = $this->block("BEGIN", "/".$who);

					$ret.= "<?php Templater::\$components['".$who."']['callback'](".$wha[1]."); ?>";
					$ret.= $inside;
					$ret.= "<?php Templater::\$components['".$who."']['callback_end'](".$wha[1]."); ?>";

					$this->eat($this->getNextToken()[0]);
				}else{
					$ret.= "<?php Templater::\$components[".$who."]['callback'](".$wha[1]."); ?>";
				}
			}else{
				if(substr($code[1], 0, 1) != " " && substr($code[1], 0, 1) != "\n" && substr($code[1], 0, 1) != "{"){
					$ret.="<?php echo ".$this->replaceFunctionAlias($code[1])."; ?>";
				}else{
					if(substr($code[1], 0, 1) == "{")
						$ret.="{".substr($code[1], 1, strlen($code[1]) - 2)."}";
					else
						$ret.="{".$code[1]."}";
				}
			}			
		}
		
		$this->eat($code[0]);

		$this->eat("END");

		return $ret;
	}
    
    public function catchDiv(){		
        $ret = "";
        
        $this->eat("DIV/BEGIN");
        $ret.= "<";
		
		$name = $this->getNextToken()[1];;
		$this->tagName = $name;
		$ret.= $name;
		$this->eat("VAR");		

		$tu = explode("\n", substr($this->getContent(), 0, $this->getPos()));
		$this->debugInfo = "Catching div - ".$name." (Line ".count($tu).", Pos: ".$this->getPos().")";
		
		$next = $this->getNextToken();
		
		if($next[0] == "DIV/CLOSE" && $name == "!doctype"){
			$this->eat("DIV/CLOSE");
			$ret.= ">";
		}else if($next[0] == "DIV/END"){
			$this->eat("DIV/END");
			$ret.= " />";
		}else if($next[0] == "DIV/CLOSE"){
            $this->eat("DIV/CLOSE");
			$ret.= ">";
			
			if(strtolower($name) == "style"){
				$style = "";
				$token = $this->getNextToken();        

				while($token[0] != "EOF" && $token[0] != "DIV/BEGIN/END"){
					$this->eat($token[0]);   

					$style.= $token[1];
					$token = $this->getNextToken();  					
				}

				$ret.= $style;
			}else{				
				$ret.= $this->block("DIV/BEGIN/END");
			}			

			//echo "-> ".$next[0]." / ".$name." <br/>";
			$next = $this->getNextToken();		
			//echo "-> ".$next[0]." / ".$name." <br/>";

			$this->eat("DIV/BEGIN/END");
			$ret.= "</";
			$ret.= $this->getNextToken()[1];
			$this->eat("VAR");
			$ret.=">";
			$this->eat("DIV/CLOSE");
        }else{
			$attributes = [];
			$attributesSource = "";
			$i = 0;

			while($next[0] != "DIV/CLOSE" && $next[0] != "DIV/END" && $next[0] != "EOF"){
				$attributesSource .= " ".$next[1];
				//$ret.= " ".$next[1];
				$attributes[$i] = array($next[1], null);
				$this->eat($next[0]);
				$next = $this->getNextToken();				
				
				if($next[0] == "ASIGN"){
					$this->eat($next[0]);
					$next = $this->getNextToken();
					//$ret.="=".$next[1];			
					$attributes[$i][1] = $next[1];
					$this->eat($next[0]);		
				}
				
				$next = $this->getNextToken();
				$i++;
			}

			foreach($attributes as $n => $attribut){
				if(strpos($attribut[0], ":") !== false)
					continue;
				
				if($attribut[1] == null){
					$ret.= " ".$attribut[0];
				}else{
					$a = "\"";
					$r = $attribut[1];
					$w = substr($r, 0, 1);
					if($w == "\"" || $w == "'"){
						$r = substr($r, 1, strlen($r) - 2);
						$a = $w;
					}

					$cond = false;
					$ps = strpos($attribut[1], "{");
					
					if(strpos($attribut[1], "{") !== false && substr($attribut[1], $ps+1, 1) != " " && substr($attribut[1], $ps+1, 1) != "\""){
						$s = $r;
						$temp = new Templater($r, true, $this);
						$r = $temp->template();
						if(substr($s, 0, 2) == "{\$" || substr($s, 0, 3) == "{!\$" && substr($s, strlen($s) - 1, 1) == "}"){
							$ph = str_replace("<?php", "", $r);
							$ph = str_replace("?>", "", $ph);
							$ph = str_replace("echo", "", $ph);
							$ph = str_replace(";", "", $ph);
							$r = $ph;
							$cond = true;
						}						
					}
					if($cond){
						$ret.= " <?php if((is_bool(".$r.") && (".$r.")) || !is_bool(".$r.")) { echo \"".$attribut[0]."=\\\"\" . (".trim($r).") . \"\\\"\"; } ?>";
					}else{
						$ret.= " ".$attribut[0]."=".$a."".trim(str_replace("\n", " ", preg_replace('/\s+/', ' ', $r)))."".$a."";
					}
				}
			}

			$this->debugInfo .= "{ " . $attributesSource . " }";
			
			$nt = $this->getNextToken();
			if($nt[0] == "DIV/CLOSE" && $name == "!doctype"){				
				$this->eat("DIV/CLOSE");
				$ret.= ">";
			}elseif($nt[0] == "DIV/END"){
				$this->eat("DIV/END");
				$ret.= " />";
			}else{
				$this->eat("DIV/CLOSE");
				$ret.= ">";								

				$ret.= $this->block("DIV/BEGIN/END");

				$next = $this->getNextToken();						

				//echo $next[0]."(1. ".htmlentities($next[1]).") -> ".$name."<hr/>";
				$this->eat("DIV/BEGIN/END");
				$ret.= "</";
				$next = $this->getNextToken();
				$ret.= $next[1];
			//	echo $next[0]."(2. ".htmlentities($next[1]).") -> ".$name."<hr/>";
				$this->eat("VAR");
				$next = $this->getNextToken();
				$ret.=">";for($p=0;$p<$next[3];$p++) { $ret.=" "; }
				$this->eat("DIV/CLOSE");
			}
		}
        
        return $ret;
	}
	
	function mb_trim($string, $charlist = null) 
	{   
		if (is_null($charlist)) {
			return trim ($string);
		} 

		$charlist = str_replace ('/', '\/', preg_quote ($charlist));
		return preg_replace ("/(^[$charlist]+)|([$charlist]+$)/us", '', $string);
	}
    
    public function eat($type){
        if($this->getNextToken()[0] == $type)
            $this->pos++;
        else {
			$pos = $this->getPos();
			//echo str_replace("UO", "<br>", htmlentities(str_replace("\r\n", "UO", substr($this->getContent(), 0, $pos))));			
			$tu = explode("\r\n", substr($this->getContent(), 0, $pos));
			$this->debugInfo.= " -> (Line ".count($tu).", Pos: ".$pos.")";
			throw new Exception("Want ".$type." got ".$this->getNextToken()[0]."(".$this->getNextToken()[1]."). ".$this->debugInfo);
		}
    }
    
    public function getNextToken(){
        return $this->tokens[$this->pos];
    }
		
    public function parse(){
		$x = $this->getToken();
		$this->prev = $x;
        $this->pos += mb_strlen($x[1]);
		
		$i = 0;
        while($x[0] != "EOF"){
            //echo $x[0]." -> ".htmlentities($x[1])."<br>";
            $this->tokens[] = $x;
			$x = $this->getToken();
			//var_dump($x);
			//echo "<br>";
			if(count($x) > 2)
				$this->pos += $x[2];
			else
				$this->pos += mb_strlen($x[1]);
		}		
    }
    
    public function getToken(){
		$skiped = false;
		if($this->catchPHP == false){
			$skiped = $this->skipWhite();
		}
			
		$chars = $this->getChars();

		if($this->catchTagAfter >= 1)
			$this->catchTagAfter++;
        
        if($chars[0] == "")
            return array("EOF", "");
            
        if($this->catchPHP){
			$this->catchPHP = false;
			$x = $this->php();
            return array("PHP", $x);
        }
        
        if($chars[0] == "\"" || $chars[0] == "'")
			return array("STRING", $this->string($chars[0]));
        
        if($chars[0] == "<" and $chars[1] == "/")
            return array("DIV/BEGIN/END", "</");
        if($chars[0] == "<" && $chars[1] != " "){
			$this->catchTagName = true;
			return array("DIV/BEGIN", "<");
		}
        if($chars[0] == "/" and $chars[1] == ">")
            return array("DIV/END", "/>", 1, $this->getWhiteChars());
        if($chars[0] == ">"){
			$this->catchTagAfter = 1;			
			return array("DIV/CLOSE", ">", 1, $this->getWhiteChars());
		}
        if($chars[0] == "=" and $chars[1] == "=")
            return array("EQUAL", "==");
        if($chars[0] == "=")
            return array("ASIGN", "=");
        if($chars[0] == "{"/* && $chars[1] != " " && $chars[1] != "\"" && $chars[1] != "["*/){
			$this->catchPHP = true;
			if($skiped)
				return array("BEGIN_", "{",);
			return array("BEGIN", "{",);
		}
		if($chars[0] == "{"/* && ($chars[1] != " " || $chars[1] != "\"" || $chars[1] != "[")*/){
			$skiped = false;
		}
        if($chars[0] == "}"){
			$this->catchTagAfter = 1;
			return array("END", "}");		
		}
		
		$tospace = $this->toSpace();
		$val = ($skiped?" ":"").$tospace;
		if($this->catchTagName){
			$this->tagName = $val;
		}
        return array("VAR", $val, mb_strlen($tospace));
    }
    
    public function php(){
        $ret = "";
        $i = $this->pos;
        $open = 0;
        while($i < count($this->chars) && ($open > 0 || $this->chars[$i] != "}")){
            $ret.= $this->chars[$i];
            if($this->chars[$i] == "{")
                $open++;
            if($this->chars[$i] == "}")
                $open--;
            $i++;
        }
        return $ret;
    }
    
    public function skipWhite(){
        if($this->pos >= count($this->chars)) return;
		
		$pos = $this->pos;
        $white = array(" ", "\n", "\r", "\t");
        if(in_array($this->chars[$this->pos], $white)){
            while(in_array($this->chars[$this->pos], $white)){
                $this->pos++;
            }
		}
		return ($pos != $this->pos);
    }
    
    public function string($end){
        $ret = "";
        $i = $this->pos + 1;
		$skip = false;
		$cont = 0;
        while($i < count($this->chars) && ($skip || $this->chars[$i] != $end || $cont > 0)){
			$ret.= $this->chars[$i];
			if(!$skip && $this->chars[$i] == "{")
				$cont++;
			if(!$skip && $this->chars[$i] == "}")
				$cont--;

			if($skip) $skip = false;			
            if($this->chars[$i] == "\\")
				$skip = true;			
			$i++;
		}
        return $end.$ret.$end;
    }
    
    public function toSpace(){
        $ret = "";
		$i = $this->pos;
		$nospace = 0;
		if($this->catchTagAfter == 2){
			$open = false;
			$this->catchTagAfter = 0;
			while($i < count($this->chars) && 
					(	($this->chars[$i] != "{" || $this->tagName == "script") && 
						(
							(($this->tagName == "script" && $this->chars[$i+1] != "/") ||
							$this->chars[$i] != "<") || 
							$open
						)
						&& (
							((!$this->fu) || (($this->fu) && $this->chars[$i] != "\"" && $this->chars[$i] != "'")) ||
							$this->tagName == "script"
						)
					)
				){
				$ret.= $this->chars[$i];

				if($this->chars[$i] == " " && $nospace == 0){ $nospace = 1; }
				else if($this->chars[$i] != " " && $nospace == 1) { $nospace = 3; }
				if($this->chars[$i] == "\"") { $open = !$open; }

				$i++;
			}
		}else{
			while($i < count($this->chars) && (((
					preg_match('/^[\p{Latin}]+$/u', $this->chars[$i]) || is_numeric($this->chars[$i]) || $this->chars[$i]==":" || $this->chars[$i]=="(" || $this->chars[$i]==")" || $this->chars[$i]==";" || $this->chars[$i]=="_" || $this->chars[$i]=="&" || $this->chars[$i]=="." || $this->chars[$i]=="!" || $this->chars[$i]=="%" || $this->chars[$i]=="-"
				) || $this->fu ) && ($this->chars[$i] != "\"" && $this->chars[$i] != "'"))){
				$ret.= $this->chars[$i];
				$i++;
			}			
		}	
        return $ret;
    }
    
    public function getChars(){
        $ch = ["","","",""];
        for($i = 0; $i < 4; $i++){
            if($this->pos + $i < count($this->chars)){
				$ch[$i] = $this->chars[$this->pos + $i];
			}
		}  		
        return $ch;
    }

	public function getWhiteChars(){
		if($this->pos >= count($this->chars)) return 0;
		
		$white = 1;
		$pos = $this->pos + 1;
        $whiteChars = array(" ", "\n", "\r", "\t");
        if(in_array($this->chars[$pos], $whiteChars)){
            while(in_array($this->chars[$pos], $whiteChars)){
				if(in_array($this->chars[$pos], array("\n", "\r"))) break;
                $white++;
				$pos++;
            }
		}
		return $white;
	}
}

function utf8Split($str, $len = 1) {
  $arr = array();
  $strLen = mb_strlen($str, 'UTF-8');
  for ($i = 0; $i < $strLen; $i++)
  {
    $arr[] = mb_substr($str, $i, $len, 'UTF-8');
  }
  return $arr;
}

function utf8_str_split($str='',$len=1){
    preg_match_all("/./u", $str, $arr);
    $arr = array_chunk($arr[0], $len);
    $arr = array_map('implode', $arr);
    return $arr;
}