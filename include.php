<?
define("INT_DAILY", 60*60*24*2);
define("INT_WEEKLY", 60*60*24*8);
define("INT_MONTHLY", 60*60*24*35);
define("INT_YEARLY", 60*60*24*400);

define("XOFFSET", 90);
define("YOFFSET", 45);

require("config.conf");

/**
 * trims all $_GET inputs
 */
function trim_get () {
	//trim all inputs
	foreach ($_GET as $key => $value) {
		$_GET[$key] = trim($_GET[$key]);
	}
	
}

/**
 * 
 * @param string (IP) $given_ip
 * @return string (sanitized IP)
 */
function sanitize_ip ($given_ip) {
	if (preg_match("/(1?[1-9]?[0-9]|2?(?:[0-4]?[0-9]|5[0-5]))\.(1?[1-9]?[0-9]|2?(?:[0-4]?[0-9]|5[0-5]))\.(1?[1-9]?[0-9]|2?(?:[0-4]?[0-9]|5[0-5]))\.(1?[1-9]?[0-9]|2?(?:[0-4]?[0-9]|5[0-5]))(\/[0-9]{1,2})?\b/", $given_ip,$ip))
	{
		return $ip[0];
	}
	else
	{
		return "0.0.0.0/0";
	}
}

function ConnectDb()
    {
	global $db_connect_string;

    $db = pg_pconnect($db_connect_string);
    if (!$db)
        {
        printf("DB Error, could not connect to database");
        exit(1);
        }
    return($db);
    }
                                                                                                                             
function fmtb($kbytes)
	{
	$Max = 1024;
	$Output = $kbytes;
	$Suffix = 'K';

	if ($Output > $Max)
		{
		$Output /= 1024;
		$Suffix = 'M';
		}

	if ($Output > $Max)
		{
		$Output /= 1024;
		$Suffix = 'G';
		}

	if ($Output > $Max)
		{
		$Output /= 1024;
		$Suffix = 'T';
		}

	return(sprintf("<td align=right><tt>%.1f%s</td>", $Output, $Suffix));
	}

$starttime = time();
set_time_limit(300);
?>