<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />
<body style="background-color:transparent;">

<HTML><B>Web Applications</b><BR>
GPS: </HTML>

<?PHP

$phone = $_GET['phone'];

mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('web-settings');

$result = mysql_query("SELECT * FROM $phone ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);
$gpsenabled1 = $row['gpsenable'];
$textenabled1 = $row['textenable'];

if($gpsenabled1==1)
	$gpsenabled = true;
if($gpsenabled1==0)
	$gpsenabled=false;
	
if($textenabled1==1)
	$textenabled = true;
if($textenabled1==0)
	$textenabled=false;

if($gpsenabled){
?>
<font color="#397D02"><b>Enabled</b></font> - <a href="http://24.27.110.178/phone3/gps/setSystem.php?set=0&phone=<?PHP echo $phone; ?>" target="_top">Disable</a>

<?php } 
if(!($gpsenabled)) {
?>

<font color="#CD0000"><b>Disabled</b></font> - <a href="http://24.27.110.178/phone3/gps/setSystem.php?set=1&phone=<?PHP echo $phone; ?>" target="_top">Enable</a>
<?php } ?>
<BR>

Texting:

<?php
if($textenabled){
?>
<font color="#397D02"><b>Enabled</b></font> - <a href="http://24.27.110.178/phone3/texting/setSystem.php?set=0&phone=<?PHP echo $phone; ?>" target="_top">Disable</a>

<?php } 
if(!($textenabled)) {
?>

<font color="#CD0000"><B>Disabled</b></font> - <a href="http://24.27.110.178/phone3/texting/setSystem.php?set=1&phone=<?PHP echo $phone; ?>" target="_top">Enable</a>
<?php } ?>
</body>