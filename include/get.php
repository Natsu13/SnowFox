<?php
$ver = $_GET["version"];
$pos = $_GET["pos"];
$rev = $_GET["rev"];
$file = explode(";", file_get_contents("http://localhost/www/SnowLeopard/remote/download.php?fls&ver=".$ver."&rev=".$rev."&pos=".$pos));

$text = file_get_contents($file[0]);
if($text!=""){
	$soubor = fopen("../".$file[1], "w+");
	fwrite($soubor, $text);
	fclose($soubor);
	?>
		var arra = {state:1};
		$("#transerfi").html(JSON.stringify(arra));
	<?php
}else{
	?>
		var arra = {state:0, error: "Chyba při stahování souboru!"};
		$("#transerfi").html(JSON.stringify(arra));
	<?php
}