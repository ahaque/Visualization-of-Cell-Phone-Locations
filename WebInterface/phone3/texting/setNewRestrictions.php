<link rel="stylesheet" type="text/css" media='screen' href="custom_css.css" />

<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/public/style_css/css_1/ipb_styles.css" />
<body style="background-color:transparent;">
<?PHP

$phone = $_GET['phone'];

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("web-settings") or die(mysql_error());

$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);
$enabled = $row['textenable'];
$value = false;

if($enabled==1)
	$value = true;
if($enabled==0)
	$value=false;

if($value){

echo "<form action='http://24.27.110.178/phone3/texting/webwrite.php?' method='POST'>";
echo "<input name='phone' type='hidden' value='";
echo $phone;
echo "' />";

?>


Please enter the time in the following format <B>without</b> the colon and in <b>military</b> time and <b>without</b> preceding zeros.<BR>
Example: 830 or 2130<BR><BR>

<?PHP

if($_GET["set"]=="true")
	echo "<B><font color='green'>
	Restrictions updated!</font></b>
	";

?>

<table width="100" border="1" cellspacing="0" cellpadding="0">
  <tr>

    <td width="50">&nbsp;</td>
    <td width="40"><font size="2">Start</font></td>
    <td width="40"><font size="2">Stop</font></td>
  </tr>
  <tr>

    <td><font size="2">Monday</font></td>
    <td><input name="monS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">

    
    </td>
    <td><input name="monE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
  </tr>
  <tr>
    <td><font size="2">Tuesday</font></td>
    <td><input name="tueS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
   </td>
    <td><input name="tueE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">

    </td>
  </tr>
  <tr>
    <td><font size="2">Wednesday</font></td>
    <td><input type="text" name="wedS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">

    </td>
    <td><input name="wedE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
   </td>

  </tr>
  <tr>
    <td><font size="2">Thursday</font></td>
    <td><input name="thuS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
    <td><input name="thuE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
  </tr>
  <tr>

    <td><font size="2">Friday</font></td>
    <td><input name="friS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
    <td><input name="friE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
  </tr>
  <tr>
    <td><font size="2">Saturday</font></td>
    <td><input name="satS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">

</td>
    <td><input name="satE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
  </tr>
  <tr>
    <td><font size="2">Sunday</font></td>
    <td><input name="sunS" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>
    <td><input name="sunE" size="4" onkeyup="if (this.value.length > 4) { alert('You can only enter 4 digits for the time!'); this.value = this.value.substr(0,4); }">
</td>

  </tr>
</table>

<button type="submit">Submit</button>
</form>


<?php } ?>
<?php if(!($enabled)){ ?>

<font face="Arial" size="2">
The Text Messaging web application is not enabled.</font>

<?php } ?>