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

if($_POST["monS"]!=null) {
    $monS1 = format_time($_POST["monS"]);
	}
if($_POST["tueS"]!=null) {
    $tueS1 = format_time($_POST["tueS"]);
  }
if($_POST["wedS"]!=null) {
    $wedS1 = format_time($_POST["wedS"]);
  }  
if($_POST["thuS"]!=null) {
    $thuS1 = format_time($_POST["thuS"]);
  }  
if($_POST["friS"]!=null) {
    $friS1 = format_time($_POST["friS"]);
  }  
if($_POST["satS"]!=null) {
    $satS1 = format_time($_POST["satS"]);
  }  
if($_POST["sunS"]!=null) {
    $sunS1 = format_time($_POST["sunS"]);
  }
if($_POST["monE"]!=null) {
    $monE1 = format_time($_POST["monE"]);
	}
if($_POST["tueE"]!=null) {
    $tueE1 = format_time($_POST["tueE"]);
  }
if($_POST["wedE"]!=null) {
    $wedE1 = format_time($_POST["wedE"]);
  }  
if($_POST["thuE"]!=null) {
    $thuE1 = format_time($_POST["thuE"]);
  }  
if($_POST["friE"]!=null) {
    $friE1 = format_time($_POST["friE"]);
  }  
if($_POST["satE"]!=null) {
    $satE1 = format_time($_POST["satE"]);
  }  
if($_POST["sunE"]!=null) {
    $sunE1 = format_time($_POST["sunE"]);
  }  
  
  
  
  
  if($_POST["monS"]==null) {
    $monS1 = "0000";
	}
if($_POST["tueS"]==null) {
    $tueS1 = "0000";
  }
if($_POST["wedS"]==null) {
    $wedS1 = "0000";
  }  
if($_POST["thuS"]==null) {
    $thuS1 = "0000";
  }  
if($_POST["friS"]==null) {
    $friS1 = "0000";
  }  
if($_POST["satS"]==null) {
    $satS1 = "0000";
  }  
if($_POST["sunS"]==null) {
    $sunS1 = "0000";
  }
if($_POST["monE"]==null) {
    $monE1 = "0000";
	}
if($_POST["tueE"]==null) {
    $tueE1 = "0000";
  }
if($_POST["wedE"]==null) {
    $wedE1 = "0000";
  }  
if($_POST["thuE"]==null) {
    $thuE1 = "0000";
  }  
if($_POST["friE"]==null) {
    $friE1 = "0000";
  }  
if($_POST["satE"]==null) {
    $satE1 = "0000";
  }  
if($_POST["sunE"]==null) {
    $sunE1 = "0000";
  }  

if($count == 0){
$con = mysql_connect("localhost","root","fin3");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }


$phone = $_POST["phone"];

// gets current date and time
$date = date("Y-m-d H:i:s");
$date2 = date("d M Y g:i A");

mysql_select_db("text-restrictions", $con);
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

$str = "Location: http://24.27.110.178/phone3/texting/redirect.php";

header( $str) ;
}

if($count >0)
	echo "Error! Invalid input";

?>