<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />
<body style="background-color:transparent;">

<form action='http://24.27.110.178/phone3/globaltracking/submitform.php' method='GET'>
Phone 1: <input name="phone1" id="phone1" type="text" value="p9729998888"><BR>
Phone 2: <input name="phone2" id="phone2" type="text" value="p9721110000"><BR>
Phone 3: <input name="phone3" id="phone3" type="text" value="p1234567890"><BR>

<?PHP
echo "<input name='phoneOriginal' id='phoneOriginal' type='hidden' value='";
echo $_GET["phone"];
echo "'>";
?>

<button type="submit">Submit</button>