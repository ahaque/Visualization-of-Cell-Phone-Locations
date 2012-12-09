<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.0.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.
|   ========================================
|   Web: http://www.
|   Email: matt@
|   Licence Info: http://www./?license
+---------------------------------------------------------------------------
*/


# Nothing of interest!

// $SQL[] = "";

$SQL[] = "ALTER TABLE ibf_forums CHANGE last_title last_title varchar(128) NOT NULL default '';";
$SQL[] = "ALTER TABLE ibf_forums CHANGE last_id last_id int(10) NOT NULL default '0';";
$SQL[] = "UPDATE ibf_components SET com_title='AddOnChat' WHERE com_section='chatsigma';";

?>