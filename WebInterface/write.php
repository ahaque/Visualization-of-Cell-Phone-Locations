<?php

$count = 0;

function format_time($time){
	
	$time2 = (int) $time;
	
	if($time2 >2400)
		echo "ERROR. Invalid time.";
		
	if($time2 < 999)
	{
		$first = substr($time,0,1);		
		$second = substr($time,1,3);
	}
	
	if($time2 > 999)
	{
		$first = substr($time,0,2);
		$second = substr($time,2,4);
	}
	
	$first2 = (int) $first;
		if($first2 > 23)
			$count = $count . 1;
	
	$second2 = (int) $second;
		if($second2 > 59)
			$count = $count . 1;
	
	$final = $first . ":" . $second . ":00";
	// if time is 0000, keep all zeros in place
	if($time == "0000")
		$final = "00:00:00";
		
	return $final;
}

if (isset($_GET)) {
if($_GET["phone"]!=null) {
    $phone = "p".$_GET["phone"];
  }
if($_GET["monS"]!=null) {
    $monS1 = format_time($_GET["monS"]);
	}
if($_GET["tueS"]!=null) {
    $tueS1 = format_time($_GET["tueS"]);
  }
if($_GET["wedS"]!=null) {
    $wedS1 = format_time($_GET["wedS"]);
  }  
if($_GET["thuS"]!=null) {
    $thuS1 = format_time($_GET["thuS"]);
  }  
if($_GET["friS"]!=null) {
    $friS1 = format_time($_GET["friS"]);
  }  
if($_GET["satS"]!=null) {
    $satS1 = format_time($_GET["satS"]);
  }  
if($_GET["sunS"]!=null) {
    $sunS1 = format_time($_GET["sunS"]);
  }
if($_GET["monE"]!=null) {
    $monE1 = format_time($_GET["monE"]);
	}
if($_GET["tueE"]!=null) {
    $tueE1 = format_time($_GET["tueE"]);
  }
if($_GET["wedE"]!=null) {
    $wedE1 = format_time($_GET["wedE"]);
  }  
if($_GET["thuE"]!=null) {
    $thuE1 = format_time($_GET["thuE"]);
  }  
if($_GET["friE"]!=null) {
    $friE1 = format_time($_GET["friE"]);
  }  
if($_GET["satE"]!=null) {
    $satE1 = format_time($_GET["satE"]);
  }  
if($_GET["sunE"]!=null) {
    $sunE1 = format_time($_GET["sunE"]);
  }  

if($count == 0){
$con = mysql_connect("localhost","root","fin3");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

mysql_select_db("text-restrictions", $con);

// gets current date and time
$date = date("Y-m-d H:i:s");
$date2 = date("d M Y g:i A");

mysql_query("INSERT INTO `$phone` (`id`, `monS`, `monE`, `tueS`, `tueE`, `wedS`, `wedE`, `thuS`, `thuE`, `friS`, `friE`, `satS`, `satE`, `sunS`, `sunE`, `date`) VALUES (NULL, '$monS1', '$monE1', '$tueS1', '$tueE1', '$wedS1', '$wedE1', '$thuS1', '$thuE1', '$friS1', '$friE1', '$satS1', '$satE1', '$sunS1', '$sunE1', '$date')");

mysql_close($con);  

echo "Mon: " . $monS1 . " to " . $monE1;
echo "\nTue: " . $tueS1 . " to " . $tueE1;
echo "\nWed: " . $wedS1 . " to " . $wedE1;
echo "\nThu: " . $thuS1 . " to " . $thuE1;
echo "\nFri: " . $friS1 . " to " . $friE1;
echo "\nSat: " . $satS1 . " to " . $satE1;
echo "\nSun: " . $sunS1 . " to " . $sunE1;
echo "\n\n Data inserted on " . $date2;
}

if($count >0)
	echo "Error! Invalid input";

}

?>