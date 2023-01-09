<?php
class Strings {
	public $root;
	public static $NUMBERS = 2;
	
	public function __construct($root){
		$this->root = $root;
	}

	public static function sklonuj($n, $arr){
		if($n == 1)
			return $arr[0];
		else if($n >= 2 and $n <= 4)
			return $arr[1];
		else
			return $arr[2];
	}

	public static function hideSome($text) {
		if(strpos($text, "@") !== -1) {
			$sub = explode("@", $text, 2);
			$s0 = strlen($sub[0]);
			if($s0 < 3) {
				$sub[0] = substr($sub[0], 0, 1);
				for($i = 0; $i < $s0 - 1; $i++){ $sub[0].="*"; }
			}else{
				$sub[0] = substr($sub[0], 0, round($s0 / 3));
				for($i = 0; $i < $s0 - round($s0 / 3); $i++){ $sub[0].="*"; }
			}
			return $sub[0]."@".$sub[1];
		}else{
			$s = strlen($text);
			if($s < 3){
				$text = substr($text, 0, 1);
				for($i = 0; $i < $s - 1; $i++){ $text.="*"; }
			}else{
				$text = substr($text, 0, round($s / 3));
				for($i = 0; $i < $s - round($s / 3); $i++){ $text.="*"; }
			}
			return $text;
		}
	}
	
	public static function toInt($string){
		$ret = "";
		for($i=0;$i<strlen($string);$i++){
			$ret.= round(ord(substr($string, $i, 1))/10);
		}
		return $ret;
	}
	
	public static function random($length, $special=false) {
		if($special == Strings::$NUMBERS)
			$mozne_znaky = '0123456789';
		elseif($special)
			$mozne_znaky = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ,.-ů§ú)?:_"!/(ˇ%0-+*/';
		else
			$mozne_znaky = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$vystup = '';
		$pocet_moznych_znaku = strlen($mozne_znaky);
		for ($i=0;$i<$length;$i++) {
			$vystup .= $mozne_znaky[mt_rand(0,$pocet_moznych_znaku-1)];
		}
		return $vystup;
	}

	public static function startsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		return substr( $haystack, 0, $length ) === $needle;
   	}

   	public static function endsWith( $haystack, $needle ) {
	   $length = strlen( $needle );
	   if( !$length ) {
		   return true;
	   }
	   return substr( $haystack, -$length ) === $needle;
   	}
	
	public static function getMonth($val){
		switch($val){
			   case 1:$return = t("January");break;
			   case 2:$return = t("February");break;
			   case 3:$return = t("March");break;
			   case 4:$return = t("April");break;
			   case 5:$return = t("May");break;
			   case 6:$return = t("June");break;
			   case 7:$return = t("July");break;
			   case 8:$return = t("August");break;
			   case 9:$return = t("September");break;
			   case 10:$return = t("October");break;
			   case 11:$return = t("November");break;
			   case 12:$return = t("December");break;
			   default:$return = t("Unknown");break;
		}
		return $return;		 
	}
	
	public static function getDay($val){
		switch($val){
			case 1: return t("Monday");
			case 2: return t("Tuesday");
			case 3: return t("Wednesday");
			case 4: return t("Thursday");
			case 5: return t("Friday");
			case 6: return t("Saturday");
			case 7: return t("Sunday");
		}
	}
	
	public static function htmlStr($input){
		return str_replace(array("&", "<", ">", "\"", "'"), array("&amp;", "&lt;", "&gt;", "&quot;", "&#39;"), $input);
	}
	 
	public static function lower($input){
		return str_replace(
			array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"), 
			array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"), 
			$input);
	} 
	
	public static function upper($input){
		return str_replace(
			array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"), 
			array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"), 
			$input);
	} 
	 
	public static function strHtml($input, $double = false) { 
		$output = str_replace(array("&lt;", "&gt;", "&quot;", "&#39;", "&amp;"), array("<", ">", "\"", "'", "&"), $input);
		if($double) {
			$output = Strings::strHtml($output);
		}
		return $output;
	}

	public static function undiacritic($in, $na="-") {
		$in = preg_replace('~[^\\pL0-9_]+~u', $na, $in);
		$in = trim($in, $na);
		$in = iconv("utf-8", "us-ascii//TRANSLIT", $in);
		$in = strtolower($in);
		$out = preg_replace('~[^-a-z0-9_]+~', '', $in);
		return $out;
	}		
		
	public static function str_time($csp, $noconvert=true, $without_time=false, $nospan = false) {
		//Utilities::getTimeFormat();

		$posun = 0;
		$den=Date("j", $csp + ($posun * 3600)); 
		$mes=Date("m", $csp + ($posun * 3600)); 
		$rok=Date("Y", $csp + ($posun * 3600)); 
		$hod=Date("H", $csp + ($posun * 3600)); 
		$min=Date("i", $csp + ($posun * 3600)); 
		$sec=Date("s", $csp + ($posun * 3600));
		
		if($without_time){return $den.". ".Strings::getMonth($mes)." ".$rok;}
		
		if(!$noconvert){return $den.". ".Strings::getMonth($mes)." v ".$hod.":".$min."";}	 
		
		if(Date("Y", time())==$rok and Date("j", time())==$den and Date("m", time())==$mes)
			$vr = t("Today in")." ".$hod.":".$min;
		elseif(Date("Y", time())==$rok and Date("j", time())==($den+1) and Date("m", time())==$mes)
			$vr = t("Yesterday in")." ".$hod.":".$min;
		elseif(Date("Y", time())==$rok and Date("j", time())==($den-1) and Date("m", time())==$mes)
			$vr = t("Tomorrow in")." ".$hod.":".$min; 
		elseif(Date("Y", time())==$rok)
			$vr = $den." ".Strings::getMonth($mes)." ".t("v")." ".$hod.":".$min;		
		else
			$vr = $den.". ".Strings::getMonth($mes)." ".$rok;
		if($nospan)
			return $vr;
		return "<span title='".$den.".".$mes.".".$rok.", ".$hod.":".$min."'>".$vr."</span>";
	}
	
	function json_encode_my($array){
		$return = "";
		$onlyNumber = 1;
		foreach($array as $key => $value){
			if(gettype($key)!="integer"){ $onlyNumber=0; }
		}
		$i=0;$a=0;
		$return.=($onlyNumber==1?"[":"{");
		foreach($array as $key => $value){
			if(is_array($value)){ 
				if($key===$a){ 
					$return.=json_encode_my($value); 
				}else{ 
					$return.='"'.$key.'":'.json_encode_my($value); 
				} 
			}
			else if(($value==$a and gettype($value)=="integer" and $key=="") or ($key==$a and gettype($key)=="integer")){ 
				if(gettype($value)=="integer"){ 
					$return.=$value; 
				}else{ 
					$return.='"'.$value.'"';
				} 
			}
			else{ 
				if(gettype($value)=="integer"){ 
					$return.='"'.$key.'":'.$value; 
				}else{ 
					$return.='"'.$key.'":"'.$value.'"'; 
				} 
			}
			if($a!=count($array)-1){$return.=",";}
			$a++;
		}
		$return.=($onlyNumber==1?"]":"}");
		return $return;
	}
	
	public static function mb_ucfirst($string, $encoding) {
		$strlen = mb_strlen($string, $encoding);
		$firstChar = mb_substr($string, 0, 1, $encoding);
		$then = mb_substr($string, 1, $strlen - 1, $encoding);
		return mb_strtoupper($firstChar, $encoding) . $then;
	}
	
	public static $transaltions = array();

	public static function t($string){
		global $lng;

		$trans = array("original" => $string, "translated" => "", "state" => 0, "module" => "", "file" => "");

		$original = $string;
		$string = strtolower($string);
		$r = $string;
		if(isset($lng[$string])){
			$r = $lng[$string];
			if(substr($original, 0, 1) != strtolower(substr($original, 0, 1)))
				$r = Strings::mb_ucfirst($r, "utf8");

			//Utilities::vardump(array($string, substr($original, 0, 1), strtolower(substr($r, 0, 1))));			
			$trans["translated"] = $r;
			$trans["state"] = 1;
		}
		else{
			$trans["state"] = 3;

			$r = "";
			$word = explode(" ", $original);
			for($i=0;$i<count($word);$i++){
				if(isset($lng[strtolower($word[$i])])) { $x=$lng[strtolower($word[$i])]." "; $trans["state"] = 2; }
				else $x=$word[$i]." ";
				
				/*if($word[$i] == Strings::mb_ucfirst($word[$i], "utf8")){
					$x = Strings::mb_ucfirst($x, "utf8");
				}
				if(mb_strtoupper($word[$i], 'utf8') == $word[$i]){
					$x = mb_strtoupper($x, 'utf8');
				}
				*/
				if($word[$i] == strtoupper($word[$i])){
					$x = strtoupper($x);
				}
				elseif(substr($word[$i], 0, 1) != strtolower(substr($word[$i], 0, 1))){
					$x = Strings::mb_ucfirst($x, "utf8");
				}				
				$r.=$x;
			}
			$r = substr($r,0,strlen($r)-1);

			$trans["translated"] = $r;			
		}		

		global $module_called_name;
		$trans["module"] = $module_called_name;
		$trans["file"] = Utilities::getCallerInfo(2, true);

		if(substr($trans["original"], 0, 1) != strtolower(substr($trans["original"], 0, 1))){
			$r = Strings::mb_ucfirst($r, "utf8");
		}

		Strings::$transaltions[] = $trans;
		return $r;
	}

	public static function time_difference($time_1, $time_2) {
		$val_1 = new DateTime(date("Y-m-d H:i:s", $time_1));
		$val_2 = new DateTime(date("Y-m-d H:i:s", $time_2));

		$interval = $val_1->diff($val_2);

		return array(
			"year" => $interval->y,
			"month" => $interval->m,
			"day" => $interval->d,
			"hour" => $interval->h,
			"minute" => $interval->i,
			"second" => $interval->s
		);
	}
	
	public static function markdown($text) {
		$text = str_replace(array("\r\n", "\r"), "\n", $text);
		
		$pars = array(
					'/(\*\*){1}(.+?)(\*\*){1}/is' => '<b>$2</b>',
					'/(\*){1}(.+?)(\*){1}/is' => '<i>$2</i>',
					'/\[(.+?)\]\((.+?)\)/is' => '<a href="$2">$1</a>',
					'/(\~){1}(.+?)(\~){1}/is' => '<s>$2</s>',
					'/(\^){1}(.+?)(\ |\n|$){1}/is' => '<sup>$2</sup>$3',
					'/(\^){1}\((.+?)\)/is' => '<sup>$2</sup>',
				);
		$output = $text;
		
		foreach($pars as $key => $value){
			$output = preg_replace($key, $value, $output);
		}
		
		$lines = explode("\n", $output);
		$output = "";
		foreach($lines as $key => $line){
			$output.= "<p>".$line."</p>";
		}
		
		return $output;
	}

	public static function containSplit($self, $text, $split){
		$data = explode($split, $self);
		foreach($data as $val) {
			if($val == $text)
				return true;
		}
		return false;
	}

	public static function take($text, $length = 10, $append = "..."){
		$appendLength = strlen($append);
		//if($length < $appendLength) throw "Length is too short";
		if($length < $appendLength || strlen($text) < $length) return $text;
		return substr($text, 0, $length - $appendLength).$append;
	}
}