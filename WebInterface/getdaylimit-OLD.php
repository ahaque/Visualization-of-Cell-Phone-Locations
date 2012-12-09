<?php 


$phone = "p".$_GET["phone"];
$day = $_GET["day"];
  
mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("text-restrictions") or die(mysql_error());

$day1 = $day . "S";
$day2 = $day . "E";


//get entire last row
$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);

$start = $row[$day1];
$end = $row[$day2];

//put into an array
$date2 = date("d M Y H:i:s");

echo $day . "|";
echo $start . "|" . $end;

mysql_select_db("web-settings") or die(mysql_error());
$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);

$sLimit = $row['slimit'];

mysql_select_db("gps-locations") or die(mysql_error());
$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);

$cSpeed = $row['speed'];

if($cSpeed<=$sLimit)
  echo "|0";
else
	echo "|1";

 ?>