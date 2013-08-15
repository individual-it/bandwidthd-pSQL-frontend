<?
if ($config_conf_key == 1) {

	define("DFLT_WIDTH", 900);
	define("DFLT_HEIGHT", 256);
	define("DFLT_INTERVAL", INT_DAILY);
	
	$db_connect_string = "user = bandwidthd password = band dbname = bandwidthd host = localhost";
}
else {
	die();
}

?>
