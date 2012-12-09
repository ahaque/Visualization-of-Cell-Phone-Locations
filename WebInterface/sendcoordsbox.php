<?php

$count = 0;

$lat = $_GET["lat"];
$lng = $_GET["lng"];

if($count == 0){
$con = mysql_connect("localhost","root","fin3");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

mysql_query("INSERT INTO `phone`.`geobox` (`id`, `lat`, `lng`) VALUES (NULL, '$lat', '$lng')");

mysql_close($con);  

header( 'Location: http://24.27.110.178/phone3/index.php?/page//gps.html' ) ;

}

if($count >0)
	echo "Error! Invalid input";

}

?>