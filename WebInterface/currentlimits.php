<?php 

$database = "text-restrictions";
$link = mysql_connect("localhost", "root", "fin3",false);

if (isset($_GET["phone"]))
    $phone = $_GET["phone"];

	$phone = "p" . $phone;
  
  function table_exists($table, $database, $link) {
	
	mysql_select_db($database, $link);
	$exists = mysql_query("SELECT 1 FROM `$table` LIMIT 0", $link);
	if ($exists)
		return true;
	else
		return false;
}

if (!$link) {	die('Could not connect to database: ' . mysql_error()); }

if (table_exists($phone, $database,$link)) {

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("text-restrictions") or die(mysql_error());

//get entire last row
$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");

//put into an array
$row = mysql_fetch_array($result);

		$mon = $row['monS'] . " to " . $row['monE'];
		$tue = $row['tueS'] . " to " . $row['tueE'];
		$wed = $row['wedS'] . " to " . $row['wedE'];
		$thu = $row['thuS'] . " to " . $row['thuE'];
		$fri = $row['friS'] . " to " . $row['friE'];
		$sat = $row['satS'] . " to " . $row['satE'];
		$sun = $row['sunS'] . " to " . $row['sunE']; 
		
		$date = strtotime($row['date']);
		
echo "Mon: " . $mon;
echo "\nTue: " . $tue;
echo "\nWed ". $wed;
echo "\nThu: " . $thu;
echo "\nFri: " . $fri;
echo "\nSat: " . $sat;
echo "\nSun: " . $sun; 
echo "\n\nSet on: " . date('h:i A M j, Y', $date);

  }
  
else {
echo "Phone Number ".$phone." does not exist in our records.";
}
 
 ?>