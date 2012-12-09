<?PHP

$phone = "p" . $_GET["phone"];
	
if(isset($_GET["msg"]))
    $message = $_GET["msg"];
  
if(isset($_GET["num"])) 
    $number = $_GET["num"];

mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('text-messagelog');
mysql_query("INSERT INTO `$phone` (`id` ,`date` ,`msg`,`num`,`flag`)VALUES (NULL ,CURRENT_TIMESTAMP , '$message', '$number',NULL);");

// pass message through filter
$str = $message;

mysql_select_db('text-messagelog');
mysql_query("INSERT INTO `$phone` (`id` ,`date` ,`msg`,`num`,`flag`)VALUES (NULL ,CURRENT_TIMESTAMP , '$message', '$number','0');");
$result = mysql_query("SELECT * FROM `$phone`");
$row = mysql_fetch_array($result);
$num_rows = mysql_num_rows($result);

// converts string into an array separates by spaces
$strA = explode(' ',$str);

$arraylength = count($strA);

$num_rows++;
$count =1;

echo "Message: " . $str . "<BR>";

// cycles through the mySQL database
for($i=0; $i<$num_rows; $i++){

$countYY = $count -1;
mysql_select_db('text-wordfilter');
$result = mysql_query("SELECT * FROM $phone order by id desc LIMIT $countYY,$count");
$row = mysql_fetch_array($result);

$dbstr = $row["word"];

	//cycles through the str array
	for($k=0; $k<$arraylength; $k++){
	
		if($dbstr == $strA[$k]){
			mysql_select_db('text-messagelog');
			mysql_query("UPDATE `$phone` SET flag='1' WHERE msg='$message'");
			echo $strA[$k] . "flagged";
			}
	}
	$count++;
}

?>