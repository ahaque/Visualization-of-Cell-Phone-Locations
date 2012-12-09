<?PHP

if(isset($_POST['speedLimit']))
	$speed = $_POST['speedLimit'];

$phone = $_POST['phone'];
	
mysql_connect("localhost","root","fin3");
mysql_select_db('web-settings');

mysql_query("UPDATE $phone SET slimit=$speed WHERE id='1'") or die(mysql_error()); 

header( 'Location: http://24.27.110.178/phone3/index.php?/page//texting.html' ) ;

?>