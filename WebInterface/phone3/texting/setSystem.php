<?PHP

mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('web-settings');

$phone = $_GET["phone"];

$value = $_GET["set"];

mysql_query("UPDATE $phone SET textenable=$value WHERE id='1'") or die(mysql_error());  


echo $value . "<BR>";
echo $phone;

header( 'Location: http://24.27.110.178/phone3/index.php?/page//settings.html' ) ;

?>