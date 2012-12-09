<?PHP

if($_GET['phone1']!=null)
	$phone1 = $_GET['phone1'];
if($_GET['phone1']==null)
	$phone1= "None Entered";

if($_GET['phone2']!=null)
	$phone2 = $_GET['phone2'];
if($_GET['phone2']==null)
	$phone2= "None Entered";
	
if($_GET['phone3']!=null)
	$phone3 = $_GET['phone3'];
if($_GET['phone3']==null)
	$phone3= "None Entered";
	
if(isset($_GET['phoneOriginal']))
	$phoneOriginal = $_GET['phoneOriginal'];
	
// CHECK TO MAKE SURE PHONES ENTERED ARE VALID AND HAVE POINTS
// OTHERWISE RETURN WITH ERROR

function table_exists($table) {
mysql_connect('localhost', 'root', 'fin3') or die(mysql_error());
mysql_select_db('gps-locations') or die(mysql_error());
if (mysql_query("SELECT 1 FROM `".$table."` LIMIT 0")) {
return true;
}
else {
return false;
}
}


// END ERROR HANDLING FOR PHONE CHECK
	
if (table_exists($phone1))
	if (table_exists($phone2))
		if (table_exists($phone3))
		{
			$url = "Location: http://24.27.110.178/phone3/globaltracking/index.php?phoneOriginal=".$phoneOriginal . "&phone1=".$phone1."&phone2=".$phone2."&phone3=".$phone3;
			header( $url ) ;
		}

$errorStr = "";

if (!(table_exists($phone1))){
$errorStr = "Error with " . $phone1;
}
if (!(table_exists($phone2)))
$errorStr = $errorStr . " " . $phone2;
if  (!(table_exists($phone3)))
$errorStr = $errorStr . " " .  $phone3;


echo "<script language='javascript' type='text/javascript'>";
echo "alert('One phone entered does not exist in database or is incorrectly entered. " . $errorStr . "')";
echo "</script>";

?>


