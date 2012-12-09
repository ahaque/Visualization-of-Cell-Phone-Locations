<?PHP

$phone = "p".$_GET["phone"];

// string of 5 numbers containing 0s-pass 1s-problem
// day, time, speed, location, word filter <- in that order

// ##########################	
// check day & time
mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("text-restrictions") or die(mysql_error());

$currentDay = date("D");
$currentDay = strtolower($currentDay);

$currentTime = date("Hi");

$daySstring = $currentDay . "S";
$dayEstring = $currentDay . "E";

$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);

$dayS = $row[$daySstring];
$dayE = $row[$dayEstring];

$daySint = substr($dayS, 0,2) . substr($dayS, 3,2);
$dayEint = substr($dayE, 0,2) . substr($dayE, 3,2);

$daySint2 = 0+$daySint;
$dayEint2 = 0+$dayEint;

if(($currentTime>=$daySint2)&&($currentTime<=$dayEint2))
	echo "11";
else
	echo "00";
	
// ##########################	
// speed
mysql_select_db("gps-locations") or die(mysql_error());
$result2 = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row2 = mysql_fetch_array($result2);
$mps = $row2['speed'];
$mph = $mps/2.23693629;

mysql_select_db("web-settings") or die(mysql_error());
$result3 = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row3 = mysql_fetch_array($result3);
$slimit = $row3['slimit'];

if($mph>$slimit)
	echo "1";
else
	echo "0";
	
	
// ##########################	
// location
mysql_select_db("gps-locations") or die(mysql_error());
$result0 = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row0 = mysql_fetch_array($result0);
$bounds = $row0['inbounds'];

if($bounds == "outside")
	echo "1";
else
	echo "0";
	
?>