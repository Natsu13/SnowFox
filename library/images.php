<?php
class Images {
	public $root;
	
	public function __construct($root){
		$this->root = $root;
	}

	public static function text($text, $isize = array(100,20), $font_size = 15, $rgb = array(255,255,255)){
		$size=strlen($text);
		$im = imagecreatetruecolor($isize[0], $isize[1]);
		$white = imagecolorallocate($im, 255, 255, 255);
		$grey = imagecolorallocate($im, 128, 128, 128);
		$black = imagecolorallocate($im, 0, 0, 0);
		$pozadi_ = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
		imagefilledrectangle($im, 0, 0, $isize[0], $isize[1], $pozadi_);
		imagettftext($im, $font_size, 0, 1, $font_size, $black, _ROOT_DIR."/include/arial.ttf", $text);
		$random = Utilities::GUID(true);
		imagepng($im, _ROOT_DIR."/temp/".$random.".png");
		imagedestroy($im);
		$text = base64_encode(file_get_contents(_ROOT_DIR."/temp/".$random.".png"));
		unlink(_ROOT_DIR."/temp/".$random.".png");
		return "image/png;base64,".$text;
	}

}