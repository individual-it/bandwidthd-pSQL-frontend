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
<img alt="logo" src="logo.gif">
<?php trim_get ();

if (isset($_GET['sensor_id'])) {
	$sensor_id = filter_var($_GET['sensor_id'], FILTER_SANITIZE_NUMBER_INT);
}
else
{
	echo "<br>Please provide a sensor_id";
    exit(1);
}

if (isset($_GET['ip'])) {
	$ip =  sanitize_ip ($_GET['ip']);
}
else
{
    echo "<br>Please provide an ip address";
    exit(1);
}
                                                                                                                             
echo "<h3>";
if (strpos($ip, "/") === FALSE) echo "$ip - ".gethostbyaddr($ip)."</h3>";
else echo "Total - $ip</h3>";

$db = ConnectDb();

if ($ip == "0.0.0.0/0")
{
    $rxtable = "bd_rx_total_log";
	$txtable = "bd_tx_total_log";
}
else
{
    $rxtable = "bd_rx_log";
	$txtable = "bd_tx_log";
}

$sql = "select rx.scale as rxscale, tx.scale as txscale, tx.total+rx.total as total, tx.total as sent,
rx.total as received, tx.tcp+rx.tcp as tcp, tx.udp+rx.udp as udp,
tx.icmp+rx.icmp as icmp, tx.http+rx.http as http,
tx.p2p+rx.p2p as p2p, tx.ftp+rx.ftp as ftp
from
                                                                                                                             
(SELECT ip, max(total/sample_duration)*8 as scale, sum(total) as total, sum(tcp) as tcp, sum(udp) as udp, sum(icmp) as icmp,
sum(http) as http, sum(p2p) as p2p, sum(ftp) as ftp
from sensors, $txtable
where sensors.sensor_id = '$sensor_id'
and sensors.sensor_id = ".$txtable.".sensor_id
and ip <<= '$ip'
group by ip) as tx,
                                                                                                                             
(SELECT ip, max(total/sample_duration)*8 as scale, sum(total) as total, sum(tcp) as tcp, sum(udp) as udp, sum(icmp) as icmp,
sum(http) as http, sum(p2p) as p2p, sum(ftp) as ftp
from sensors, $rxtable
where sensors.sensor_id = '$sensor_id'
and sensors.sensor_id = ".$rxtable.".sensor_id
and ip <<= '$ip'
group by ip) as rx
                                                                                                                             
where tx.ip = rx.ip;";
//echo "</center><pre>$sql</pre><center>";exit(0);
$result = pg_query($sql);
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
		</thead><tbody><tr><td>";
$r = pg_fetch_array($result);

if (strpos($ip, "/") === FALSE) echo "$ip</td><td>".gethostbyaddr($ip) . '</td>';
else echo "Total<td>$ip</td>";

echo fmtb($r['total']).fmtb($r['sent']).fmtb($r['received']).
	fmtb($r['tcp']).fmtb($r['udp']).fmtb($r['icmp']).fmtb($r['http']).
    fmtb($r['p2p']).fmtb($r['ftp']);
echo "</tbody></table>";


	echo "<div class='panel panel-default'><div class='panel-heading'><h3 class='panel-title'>Daily</h3></div>
	    <div class='panel-body'>
	    	<div class='well'>
		    	Send:<br><img alt='graph' src='graph.php?ip=$ip&amp;sensor_id=".$sensor_id."&amp;table=$txtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'><br>
				<img alt='' src=legend.gif><br>
			</div>
			<div class='well'>
				Receive:<br><img alt='graph' src='graph.php?ip=$ip&amp;sensor_id=".$sensor_id."&amp;table=$rxtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'>
				<img alt='' src=legend.gif><br>
			</div>
		</div>
	</div>";

	echo "<div class='panel panel-default'><div class='panel-heading'><h3 class='panel-title'>Weekly</h3></div>
	    <div class='panel-body'>
	    	<div class='well'>
		    	Send:<br><img alt='graph' src='graph.php?interval=".INT_WEEKLY."&amp;ip=$ip&amp;sensor_id=$sensor_id&amp;table=$txtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'><br>
				<img alt='' src=legend.gif><br>
			</div>
			<div class='well'>
				Receive:<br><img alt='graph' src='graph.php?interval=".INT_WEEKLY."&amp;ip=$ip&amp;sensor_id=$sensor_id&amp;table=$rxtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'>
				<img alt='' src=legend.gif><br>
			</div>
		</div>
	</div>";

	echo "<div class='panel panel-default'><div class='panel-heading'><h3 class='panel-title'>Monthly</h3></div>
	    <div class='panel-body'>
	    	<div class='well'>
		    	Send:<br><img alt='graph' src='graph.php?interval=".INT_MONTHLY."&amp;ip=$ip&amp;sensor_id=$sensor_id&amp;table=$txtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'><br>
				<img alt='' src=legend.gif><br>
			</div>
			<div class='well'>
				Receive:<br><img alt='graph' src='graph.php?interval=".INT_MONTHLY."&amp;ip=$ip&amp;sensor_id=$sensor_id&amp;table=$rxtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'>
				<img alt='' src=legend.gif><br>
			</div>
		</div>
	</div>";

	echo "<div class='panel panel-default'><div class='panel-heading'><h3 class='panel-title'>Yearly</h3></div>
	    <div class='panel-body'>
	    	<div class='well'>
		    	Send:<br><img alt='graph' src='graph.php?interval=".INT_YEARLY."&amp;ip=$ip&amp;sensor_id=$sensor_id&amp;table=$txtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'><br>
				<img alt='' src=legend.gif><br>
			</div>
			<div class='well'>
				Receive:<br><img alt='graph' src='graph.php?interval=".INT_YEARLY."&amp;ip=$ip&amp;sensor_id=$sensor_id&amp;table=$rxtable&amp;yscale=".(max($r['txscale'], $r['rxscale']))."'>
				<img alt='' src=legend.gif><br>
			</div>
		</div>
	</div>

</div>";

include('footer.php');
?>