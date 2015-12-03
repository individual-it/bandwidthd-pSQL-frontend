<?php include("include.php");?>
<!DOCTYPE HTML>
<html>
<head>
	<title>BandWidthd</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
	<script type="text/javascript" src="js/jquery.tinysort.min.js"></script>
	<script type="text/javascript" src="js/bandwidthd.js"></script>
	<link media="screen" rel="stylesheet" type="text/css" href="css/style.css">
	<link media="screen" rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
</head>
<body>
	<div class="container content">
		<img id="logo" alt="logo" src="logo.gif">
<?php
trim_get();

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

<form name="navigation" class="form-inline" method=get action=<?=$_SERVER['PHP_SELF']?>>
	<div class="form-group">
		<select class="form-control" name="sensor_id">
			<option value="none">--Select A Sensor--
			<?php  $sql = "SELECT sensor_id,sensor_name from sensors order by sensor_name;";
				$result = pg_query($sql);
				while ($r = pg_fetch_array($result))
				{
					echo "<option value=\"".$r['sensor_id']."\" ".(isset($sensor_id) && $sensor_id==$r['sensor_id']?"SELECTED":"").">".$r['sensor_name']."\n";
				}
			?>
		</select>
	</div>
	<div class="form-group">
		<select class="form-control" name="interval">
			<option value="none">--Select An Interval--
			<option value=<?php echo INT_DAILY?> <?php echo isset($interval) && $interval==INT_DAILY?"SELECTED":""?>>Daily
			<option value=<?php echo INT_WEEKLY?> <?php echo isset($interval) && $interval==INT_WEEKLY?"SELECTED":""?>>Weekly
			<option value=<?php echo INT_MONTHLY?> <?php echo isset($interval) && $interval==INT_MONTHLY?"SELECTED":""?>>Monthly
			<option value=<?php echo INT_YEARLY?> <?php echo isset($interval) && $interval==INT_YEARLY?"SELECTED":""?>>Yearly
			<option value=<?php echo 24*60*60?> <?php echo isset($interval) && $interval==24*60*60?"SELECTED":""?>>24hrs
			<option value=<?php echo 30*24*60*60?> <?php echo isset($interval) && $interval==30*24*60*60?"SELECTED":""?>>30days
		</select>
	</div>
	<div class="form-group">
		<select class="form-control" name="limit">
			<option value="none">--How Many Results--
			<option value="20" <?php echo isset($limit) && $limit==20?"SELECTED":""?>>20
			<option value="50" <?php echo isset($limit) && $limit==50?"SELECTED":""?>>50
			<option value="100" <?php echo isset($limit) && $limit==100?"SELECTED":""?>>100
			<option value="all" <?php echo isset($limit) && $limit=="all"?"SELECTED":""?>>All
		</select>
	</div>
	<div class="form-group">
    	<input id="subnet" class="form-control" name="subnet" value="<?php echo isset($subnet)?$subnet:"0.0.0.0/0"?>">
 	</div>
 	<input class="btn btn-success" type="submit" value="Go">
</form>

<?php if (!isset($sensor_id)) exit(0);

$sql = "SELECT sensor_name from sensors WHERE sensor_id = $sensor_id ;";
$result = pg_query($sql);
$sensor_name = pg_fetch_row($result);
$sensor_name = $sensor_name[0];

// Validation
if (!isset($sensor_name)) exit(0);

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

echo "<div class='table-responsive'><table class='table table-hover table-bordered' id='xtable' style='font-size:13px;'>
		<thead>
			<tr>
				<th title='click for sorting' onclick='sortTable(0);'>IP</th>
				<th title='click for sorting' onclick='sortTable(1);'>Name</th>
				<th title='click for sorting' onclick='sortTable(2);'>Total</th>
				<th title='click for sorting' onclick='sortTable(3);'>Sent</th>
				<th title='click for sorting' onclick='sortTable(4);'>Received</th>
				<th title='click for sorting' onclick='sortTable(5);'>tcp</th>
				<th title='click for sorting' onclick='sortTable(6);'>udp</th>
				<th title='click for sorting' onclick='sortTable(7);'>icmp</th>
				<th title='click for sorting' onclick='sortTable(8);'>http</th>
				<th title='click for sorting' onclick='sortTable(9);'>p2p</th>
				<th title='click for sorting' onclick='sortTable(10);'>ftp</th>
			</tr>
		</thead>";


if (!isset($subnet)) // Set this now for total graphs
	$subnet = "0.0.0.0/0";

// Output Total Line
echo "<tbody><tr><td><a href=#Total>Total</a><td>$subnet";
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
	echo "<tr><td class='".str_replace('.','_',$r['ip'])."'><a href='#".$r['ip']."'>";
	echo $r['ip']."</a></td><td class='".$hostname."'>".$hostname;
	echo "</td>";
	echo fmtb($r['total']).fmtb($r['sent']).fmtb($r['received']).
		fmtb($r['tcp']).fmtb($r['udp']).fmtb($r['icmp']).fmtb($r['http']).
		fmtb($r['p2p']).fmtb($r['ftp'])."</tr>";
}
echo "</tbody></table></div>";

// Output Total Graph
$scale = 0;
for($Counter=0, $Total = 0; $Counter < pg_num_rows($result); $Counter++)
{
	$r = pg_fetch_array($result, $Counter);
	$scale = max($r['txscale'], $scale);
	$scale = max($r['rxscale'], $scale);
}






if ($subnet == "0.0.0.0/0") {
	$total_table = "bd_tx_total_log";
	$total_table2 = "bd_rx_total_log";
}
else {
	$total_table = "bd_tx_log";
	$total_table2 = "bd_rx_log";
}
echo "<div class='panel panel-default'>
   <div class='panel-heading'>
        <h3 class='panel-title'><a href='details.php?sensor_id=$sensor_id&amp;ip=$subnet'>Total - Total of $subnet</a></h3>
    </div>
    <div class='panel-body'>
    	<div class='well'>
	    	Send:<br><img alt='graph' src='graph.php?ip=$subnet&amp;interval=$interval&amp;sensor_id=".$sensor_id."&amp;table=$total_table'><br>
			<img alt='' src=legend.gif><br>
		</div>
		<div class='well'>
			Receive:<br><img alt='graph' src='graph.php?ip=$subnet&amp;interval=$interval&amp;sensor_id=".$sensor_id."&amp;table=$total_table2'>
			<img alt='' src=legend.gif><br>
		</div>
	</div>
</div>";


// Output Other Graphs
for($Counter=0; $Counter < pg_num_rows($result) && $Counter < $limit; $Counter++) 
{
	$r = pg_fetch_array($result, $Counter);
	echo "<div class='panel panel-default'>
   			<div class='panel-heading'>
   			<h3 id='".$r['ip']."' class='panel-title'><a href='details.php?sensor_id=$sensor_id&amp;ip=".$r['ip']."'>";
			if ($r['ip'] == "0.0.0.0") {
				echo "Total - Total of all subnets";
			}
			else {
				echo $r['ip']." - ".gethostbyaddr($r['ip']);
			}
	echo "</a></h3></div> <div class='panel-body'>
	<div class='well'>Send:<br><img alt='graph' src='graph.php?ip=".$r['ip']."&amp;interval=$interval&amp;sensor_id=".$sensor_id."&amp;table=bd_tx_log&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'/><br>
	<img alt='legend' src='legend.gif'/><br></div>
	<div class='well'>Receive:<br><img alt='graph' src='graph.php?ip=".$r['ip']."&amp;interval=$interval&amp;sensor_id=".$sensor_id."&amp;table=bd_rx_log&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'/><br>
	<img alt='legend' src='legend.gif'/><br></div></div></div>";
}
include('footer.php');
?>