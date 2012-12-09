<?PHP

$phone = $_POST['phone'];

mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('web-settings');

if($_POST['numtoshow']!=79124613740741){
$num = $_POST['numtoshow'];
mysql_query("UPDATE `$phone` SET numtoshow = '$num' WHERE id = 1");
}

if($_POST['daytoshow']!=79124613740741){
$day = $_POST['daytoshow'];
mysql_query("UPDATE `$phone` SET daytoshow = '$day' WHERE id = 1");
}

if($_POST['showgeobox']!=79124613740741){
$showgeobox = $_POST['showgeobox'];
mysql_query("UPDATE `$phone` SET `showgeobox` = '$showgeobox' WHERE id = 1");
echo $showgeobox;
}

if($_POST['webRF']!=79124613740741){
$webRF = $_POST['webRF'];
mysql_query("UPDATE `$phone` SET `webRF` = '$webRF' WHERE `id` =1");
}


header( 'Location: http://24.27.110.178/phone3/index.php?/page//gps.html' ) ;

?>