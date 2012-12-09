<?php
$phone = $_POST['phone'];

$num = count($_POST['latitude']);


if($num>2){
mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('gps-geobox');

mysql_query(" TRUNCATE TABLE $phone  ");

$num2 = 10 - $num;
$i=0;

for($i; $i< count($_POST['latitude']); $i++){
$lat2=$_POST['latitude'][$i];
$lng2=$_POST['longitude'][$i];

mysql_query("INSERT INTO `$phone` (`id` ,`lat` ,`lng`)VALUES ('$i', '$lat2', '$lng2');");
}

// fils table up to 10 points, duplicating the last entered point to fill up table
$lat3=$_POST['latitude'][$i-1];
$lng3=$_POST['longitude'][$i-1];

for($i; $i< 10; $i++)
	mysql_query("INSERT INTO `$phone` (`id` ,`lat` ,`lng`)VALUES ('$i', '$lat3', '$lng3');");

}

$str = "Location: http://24.27.110.178/phone3/gps/redirect.php";
header( $str ) ;

?>
