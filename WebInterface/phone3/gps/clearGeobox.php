<?PHP
if(isset($_GET['phone']))
	$phone = $_GET['phone'];

mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('gps-geobox');

 mysql_query(" TRUNCATE TABLE `$phone` ") or die(mysql_error());  

$str = "Location: http://24.27.110.178/phone3/gps/redirect.php";
header( $str ) ;

?>
