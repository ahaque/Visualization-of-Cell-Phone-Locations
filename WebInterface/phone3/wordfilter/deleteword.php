<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />
<body style="background-color:transparent;">

<?PHP
$phone = $_POST['phone'];

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("text-wordfilter") or die(mysql_error());

$result = mysql_query("SELECT * FROM `$phone` ORDER BY word ASC");

if(isset($_POST['cd'])){
$cd=$_POST['cd'];

foreach ($cd as $key => $value )
{
$result = mysql_query("SELECT * FROM `$phone` WHERE id=$value");
$row = mysql_fetch_array($result);

$wordtodelete = $row['word'];

mysql_query("DELETE FROM `$phone` WHERE id=$value");

}

$str = "Location: http://24.27.110.178/phone3/wordfilter/showWordDB.php?set=true&phone=" . $phone;
header($str) ;
}

if(!(isset($_POST['cd']))){
$str = "Location: http://24.27.110.178/phone3/wordfilter/showWordDB.php?set=blank&phone=" . $phone;
header($str) ;
}

?>