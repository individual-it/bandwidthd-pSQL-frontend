<?include("include.php");?>
<html>
<head>
	<script src="js/jquery.min.js"></script>
	<script src="js/jquery.tinysort.min.js"></script>
	<script src="js/bandwidthd.js"></script>
	<link rel="stylesheet" type="text/css" href="bandwidthd.css"/>
</head>

<center>
<img src=logo.gif>
<?
trim_get ();

// Get variables from url and set defaults
if (isset($_GET['sensor_id']) && $_GET['sensor_id'] != "none")
    $sensor_id = filter_var($_GET['sensor_id'], FILTER_SANITIZE_NUMBER_INT);


if (isset($_GET['interval']) && $_GET['interval'] != "none")
    $interval = filter_var($_GET['interval'], FILTER_SANITIZE_NUMBER_INT);
else 
	$interval = DFLT_INTERVAL;


if (isset($_GET['timestamp']) && $_GET['timestamp'] != "none")
    $timestamp = filter_var($_GET['timestamp'], FILTER_SANITIZE_NUMBER_INT);
else
	$timestamp = time() - $interval + (0.05*$interval);


if (isset($_GET['subnet']) && $_GET['subnet'] != "none")
{
	$subnet = sanitize_ip ($_GET['subnet']);
}

if (isset($_GET['limit']) && $_GET['limit'] == "all")
	$limit = "all";
elseif (isset($_GET['limit']) && $_GET['limit'] != "none")
	$limit = filter_var($_GET['limit'], FILTER_SANITIZE_NUMBER_INT);
else
	$limit = 20;

$db = ConnectDb();


?>
<FORM name="navigation" method=get action=<?=$_SERVER['PHP_SELF']?>>
<table width=100% cellspacing=0 cellpadding=5 border=1>
<tr>
<td><SELECT name="sensor_id">

<OPTION value="none">--Select A Sensor--
<?
$sql = "SELECT sensor_id,sensor_name from sensors order by sensor_name;";
$result = pg_query($sql);
while ($r = pg_fetch_array($result))
{
	echo "<option value=\"".$r['sensor_id']."\" ".(isset($sensor_id) && $sensor_id==$r['sensor_id']?"SELECTED":"").">".$r['sensor_name']."\n";
}
?>
</SELECT>

<td><SELECT name="interval">
<OPTION value="none">--Select An Interval--
<OPTION value=<?=INT_DAILY?> <?=isset($interval) && $interval==INT_DAILY?"SELECTED":""?>>Daily
<OPTION value=<?=INT_WEEKLY?> <?=isset($interval) && $interval==INT_WEEKLY?"SELECTED":""?>>Weekly
<OPTION value=<?=INT_MONTHLY?> <?=isset($interval) && $interval==INT_MONTHLY?"SELECTED":""?>>Monthly
<OPTION value=<?=INT_YEARLY?> <?=isset($interval) && $interval==INT_YEARLY?"SELECTED":""?>>Yearly
<OPTION value=<?=24*60*60?> <?=isset($interval) && $interval==24*60*60?"SELECTED":""?>>24hrs
<OPTION value=<?=30*24*60*60?> <?=isset($interval) && $interval==30*24*60*60?"SELECTED":""?>>30days
</select>

<td><SELECT name="limit">
<OPTION value="none">--How Many Results--
<OPTION value=20 <?=isset($limit) && $limit==20?"SELECTED":""?>>20
<OPTION value=50 <?=isset($limit) && $limit==50?"SELECTED":""?>>50
<OPTION value=100 <?=isset($limit) && $limit==100?"SELECTED":""?>>100
<OPTION value=all <?=isset($limit) && $limit=="all"?"SELECTED":""?>>All
</select>

<td>Subnet Filter:<input name=subnet value="<?=isset($subnet)?$subnet:"0.0.0.0/0"?>"> 
<input type=submit value="Go">
</table>
</FORM>
<?
if (!isset($sensor_id))
	exit(0);

$sql = "SELECT sensor_name from sensors WHERE sensor_id = $sensor_id ;";
$result = pg_query($sql);
$sensor_name = pg_fetch_row($result);
$sensor_name = $sensor_name[0];

// Validation
if (!isset($sensor_name))
	exit(0);

// Print Title




if (isset($limit))
	echo "<h2>Top $limit - $sensor_name</h2>";
else
	echo "<h2>All Records - $sensor_name</h2>";

// Sqlize the incomming variables
if (isset($subnet))
	$sql_subnet = "and ip <<= '$subnet'";

// Sql Statement
$sql = "select tx.ip, rx.scale as rxscale, tx.scale as txscale, tx.total+rx.total as total, tx.total as sent, 
rx.total as received, tx.tcp+rx.tcp as tcp, tx.udp+rx.udp as udp,
tx.icmp+rx.icmp as icmp, tx.http+rx.http as http,
tx.p2p+rx.p2p as p2p, tx.ftp+rx.ftp as ftp
from

(SELECT ip, max(total/sample_duration)*8 as scale, sum(total) as total, sum(tcp) as tcp, sum(udp) as udp, sum(icmp) as icmp,
sum(http) as http, sum(p2p) as p2p, sum(ftp) as ftp
from sensors, bd_tx_log
where sensor_name = '$sensor_name'
and sensors.sensor_id = bd_tx_log.sensor_id
$sql_subnet
and timestamp > $timestamp::abstime and timestamp < ".($timestamp+$interval)."::abstime
group by ip) as tx,

(SELECT ip, max(total/sample_duration)*8 as scale, sum(total) as total, sum(tcp) as tcp, sum(udp) as udp, sum(icmp) as icmp,
sum(http) as http, sum(p2p) as p2p, sum(ftp) as ftp
from sensors, bd_rx_log
where sensor_name = '$sensor_name'
and sensors.sensor_id = bd_rx_log.sensor_id
$sql_subnet
and timestamp > $timestamp::abstime and timestamp < ".($timestamp+$interval)."::abstime
group by ip) as rx

where tx.ip = rx.ip
order by total desc;";

//echo "</center><pre>$sql</pre><center>"; exit(0);
pg_query("SET sort_mem TO 30000;");
$result = pg_query($sql);
pg_query("set sort_mem to default;");

if ($limit == "all")
	$limit = pg_num_rows($result);

echo "<table id=xtable width=100% border=1 cellspacing=0>
		<tr><th title='click for sorting' onclick='sortTable(0);'>IP</th>
			<th title='click for sorting' onclick='sortTable(1);'>Name</th>
			<th title='click for sorting' onclick='sortTable(2);'>Total</th>
			<th title='click for sorting' onclick='sortTable(3);'>Sent</th>
			<th title='click for sorting' onclick='sortTable(4);'>Received</th>
			<th title='click for sorting' onclick='sortTable(5);'>tcp</th>
			<th title='click for sorting' onclick='sortTable(6);'>udp</th>
			<th title='click for sorting' onclick='sortTable(7);'>icmp</th>
			<th title='click for sorting' onclick='sortTable(8);'>http</th>
			<th title='click for sorting' onclick='sortTable(9);'>p2p</th>
			<th title='click for sorting' onclick='sortTable(10);'>ftp</th>";


if (!isset($subnet)) // Set this now for total graphs
	$subnet = "0.0.0.0/0";

// Output Total Line
echo "<TBODY><TR><TD><a href=#Total>Total</a><TD>$subnet";
foreach (array("total", "sent", "received", "tcp", "udp", "icmp", "http", "p2p", "ftp") as $key)
	{
	for($Counter=0, $Total = 0; $Counter < pg_num_rows($result); $Counter++)
		{
		$r = pg_fetch_array($result, $Counter);
		$Total += $r[$key];
		}
	echo fmtb($Total);
	}
echo "\n";

// Output Other Lines
for($Counter=0; $Counter < pg_num_rows($result) && $Counter < $limit; $Counter++)
	{
	$r = pg_fetch_array($result, $Counter);
	$hostname=gethostbyaddr($r['ip']);
	echo "<tr><td sorting='".str_replace('.','_',$r['ip'])."'><a href=#".$r['ip'].">";
	echo $r['ip']."<td sorting='".$hostname."'>".$hostname;
	echo "</a>";
	echo fmtb($r['total']).fmtb($r['sent']).fmtb($r['received']).
		fmtb($r['tcp']).fmtb($r['udp']).fmtb($r['icmp']).fmtb($r['http']).
		fmtb($r['p2p']).fmtb($r['ftp'])."\n";
	}
echo "</TBODY></table></center>";

// Output Total Graph
$scale = 0;
for($Counter=0, $Total = 0; $Counter < pg_num_rows($result); $Counter++)
	{
	$r = pg_fetch_array($result, $Counter);
	$scale = max($r['txscale'], $scale);
	$scale = max($r['rxscale'], $scale);
	}

if ($subnet == "0.0.0.0/0")
	$total_table = "bd_tx_total_log";
else
	$total_table = "bd_tx_log";
echo "<a name=Total><h3><a href=details.php?sensor_id=$sensor_id&ip=$subnet>";
echo "Total - Total of $subnet</h3>";
echo "</a>";
echo "Send:<br><img src=graph.php?ip=$subnet&interval=$interval&sensor_id=".$sensor_id."&table=$total_table><br>";
echo "<img src=legend.gif><br>\n";
if ($subnet == "0.0.0.0/0")
	$total_table = "bd_rx_total_log";
else
	$total_table = "bd_rx_log";
echo "Receive:<br><img src=graph.php?ip=$subnet&interval=$interval&sensor_id=".$sensor_id."&table=$total_table><br>";
echo "<img src=legend.gif><br>\n";


// Output Other Graphs
for($Counter=0; $Counter < pg_num_rows($result) && $Counter < $limit; $Counter++) 
	{
	$r = pg_fetch_array($result, $Counter);
	echo "<a name=".$r['ip']."><h3><a href=details.php?sensor_id=$sensor_id&ip=".$r['ip'].">";
	if ($r['ip'] == "0.0.0.0")
		echo "Total - Total of all subnets</h3>";
	else
		echo $r['ip']." - ".gethostbyaddr($r['ip'])."</h3>";
	echo "</a>";
	echo "Send:<br><img src=graph.php?ip=".$r['ip']."&interval=$interval&sensor_id=".$sensor_id."&table=bd_tx_log&yscale=".(max($r['txscale'], $r['rxscale']))."><br>";
	echo "<img src=legend.gif><br>\n";
	echo "Receive:<br><img src=graph.php?ip=".$r['ip']."&interval=$interval&sensor_id=".$sensor_id."&table=bd_rx_log&yscale=".(max($r['txscale'], $r['rxscale']))."><br>";
	echo "<img src=legend.gif><br>\n";
	}

include('footer.php');