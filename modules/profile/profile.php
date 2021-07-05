<?php
/**
 * Name: User profile
 * Description: Providing basic user profile
 * Version: 1.0
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: profile
 */

$this->hook_register("page.profile.init.setting", "profile_init_setting", -10);
$this->hook_register("page.profile", "profile_page_draw", 0);

function profile_init_setting($t){		
	$profil = User::get($t->router->_data["id"][0]);
	if(!$profil){
		$title = "Profil nebyl nalezen";
		$t->root->config->set("pre-title",$title);
	}else{
		$title = $profil["nick"];
		$t->root->config->set("title",$title);
		$t->root->config->set("pre-title","");
	}
}

function profile_page_draw($t, &$output){
	$profil = User::get($t->router->_data["id"][0]);
	
	if($profil == NULL){
		$t->root->page->draw_error("Profil nebyl nalezen", "Profil ".$t->router->_data["id"][0]." neexistuje!");
	}else{
		$data = $profil["data"];
		if($profil["avatar"] == ""){ $profil["avatar"] = $this->root->config->get_variable("default-avatar"); }
		echo "<div class='user-background-tile' style='background-image:url(".Router::url()."/upload/images/1401677869.utunu_nimroderick_-_utunu_background_mono_1_upload.png);background-size: 106%;background-position: -15px -155px;'>";
			echo "<div class=avatar style='background-image:url(".Router::url()."/upload/avatars/".$profil["avatar"].");width: 150px;height:150px;background-size: 100%;border: 3px solid #969696;border-radius: 2px;position: relative;top: 19px;left: 20px;'></div>";
			echo "<div style='width: 400px;font-size: 40px;position:relative;top: -36px;color: white;left: 193px;text-shadow: 0px 0px 5px black;'>".$profil["nick"]."</div>";
		echo "</div>";
		echo "<div class='user-background-panel'>";
			echo "<div style='float:right;padding: 11px;'><span class=buttonline><a class=button>Zpráva</a> <a class=button>Blokovat</a></span></div>";
			echo "<span class=tile><span class=namedti>Druh</span> ".(isset($data["druh"])?$data["druh"]:"Neznáme")."</span>";
			echo "<span class=tile><span class=namedti>Pohlaví</span> ".(isset($data["pohlavi"])?$data["pohlavi"]:"Neznáme")."</span>";
			
			if(!isset($data["level"])) $data["level"] = 0;
			if(!isset($data["xp"])) $data["xp"] = 0;
			
			$level = $data["level"];
			if($level < 10){
				$color = "#2A8FBD";			
				$xp = (($data["xp"]/100)*128);			
				$left = 33;
			}else if($level < 20){
				$color = "#2abd35";			
				$xp = (($data["xp"]/200)*128);
				$left = 15;
			}
			
			echo "<span class=tile><div style='height: 0px;'><div id='xp_bar' style='height: 2px;display:inline-block;position:relative;background: ".$color.";width: 0%;top: 28px;left: -12px;transition: .9s;'></div></div><span class=namedti>Level</span>";				
				echo "<div style='position:relative;display: inline-block;width: 25px;height: 14px;'><div style='position: absolute;display: inline-block;background:".$color.";width: 26px;height: 26px;top: -5px;border-radius: 50%;'><div style='background:white;border-radius: 50%;width: 18px;height: 18px;position: absolute;top: 4px;left: 4px;'><div style='position:absolute;top: 15%;left: ".$left."%;font-size: 11px;color:".$color."'>".$level."</div></div></div></div>";
			echo "</span>";
		echo "</div>";
		
		?>
		<script>
			$("#xp_bar").css("width", "<?php echo $xp; ?>%");
		</script>
		<?php
	}
}