<?PHP

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("text-restrictions") or die(mysql_error());

$phone = "{$this->memberData['members_display_name']}";

$result2 = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row2 = mysql_fetch_array($result2);
$enabled = $row2['textenable'];

$value = false;

if($enabled==1)
	$value = true;
if($enabled==0)
	$value=false;


if($value){

$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);
echo "Restrictions last set on: ";
$date = strtotime($row['date']);
echo date('M j, Y, l - h:i A', $date);

echo "<BR><BR><table border='1' width='240'  cellspacing='0' cellpadding='0'>";
echo "<tr height='20'> <td width='80'></td><td width='80'>Start</td> <td width='80'>End</td></tr>";echo "<tr height='20'><td>";
echo "Monday";echo "</td><td>";
echo $row['monS'];
echo "</td><td>";
echo $row['monE'];
echo "</td></tr><tr height='20'><td>";
echo "Tuesday";
echo "</td><td>";
echo $row['tueS'];
echo "</td><td>";
echo $row['tueE'];
echo "</td></tr><tr height='20'><td>";
echo "Wednesday";
echo "</td><td>";
echo $row['wedS'];
echo "</td><td>";
echo $row['wedE'];
echo "</td></tr><tr height='20'><td>";
echo "Thursday";echo "</td><td>";
echo $row['thuS'];
echo "</td><td>";
echo $row['thuE'];
echo "</td></tr><tr height='20'><td>";
echo "Friday";
echo "</td><td>";
echo $row['friS'];
echo "</td><td>";
echo $row['friE'];
echo "</td></tr><tr height='20'><td>";
echo "Saturday";
echo "</td><td>";
echo $row['satS'];
echo "</td><td>";
echo $row['satE'];
echo "</td></tr><tr height='20'><td>";
echo "Sunday";
echo "</td><td>";
echo $row['sunS'];
echo "</td><td>";
echo $row['sunE'];
echo "</td></tr>";
echo "</table>";

}

if(!($enabled)){

echo "<font face='Arial' size='2'>
The Text Messaging web application is not enabled.</font>";
}

?>