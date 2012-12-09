<?PHP

if(isset($_POST['word']))
	$word = $_POST['word'];

$phone = $_POST['phone'];
	
mysql_connect("localhost","root","fin3");
mysql_query("INSERT INTO `text-wordfilter`.`$phone` (`id`,`word`) VALUES (NULL, '$word')");

header( 'Location: http://24.27.110.178/phone3/index.php?/page//texting.html' ) ;

?>