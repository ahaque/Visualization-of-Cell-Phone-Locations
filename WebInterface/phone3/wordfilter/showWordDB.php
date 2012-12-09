<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />
<body style="background-color:transparent;">
<script type="text/javascript" src="selectall.js"></script>

<table border="1" width="180">
<tr><td width="30">Delete</td> <td width="130">Word</td></tr>

<FORM ACTION="deleteword.php" method="POST">
<?PHP

// error messages
if(isset($_GET['set'])){
	if($_GET['set']=="true")
		echo "<B><font color='green'>Words removed from database.</font></b>";
	
	if($_GET['set']=="blank")
		echo "<B><font color='red'>Error: You must select a word to delete.</font></b>";
		
		}


$phone = $_GET['phone'];

echo "<INPUT TYPE='hidden' name='phone' VALUE='";
echo $phone;
echo "'>";

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("text-wordfilter") or die(mysql_error());

$result = mysql_query("SELECT * FROM `$phone` ORDER BY word ASC");

$count3=0;
while ($row = mysql_fetch_array($result)) {
$count3++;
echo "<tr><td>";
echo "<center><INPUT TYPE='checkbox' value='";
echo $row['id'];
echo "' NAME='cd[]'></center>";
echo "</td><td>";
echo $row['word'];
echo "</td></tr>";
	
}

?>
</table>

<input type="checkbox" name="checkall" onclick="checkUncheckAll(this);"/> Select/Deselect All<BR>

<INPUT TYPE="SUBMIT" VALUE="Delete"></FORM>