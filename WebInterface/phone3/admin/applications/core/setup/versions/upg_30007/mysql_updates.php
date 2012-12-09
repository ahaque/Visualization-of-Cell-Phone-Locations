<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.0.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.
|   ========================================
|   Web: http://www.
|   Email: matt@
|   Licence Info: http://www./?license
+---------------------------------------------------------------------------
*/

# Final

/* Bug #15747 */
//$SQL[] = "ALTER TABLE topics CHANGE description description varchar(250) default NULL;";

$SQL[] = "delete from core_sys_conf_settings where conf_key='number_format';";
$SQL[] = "delete from core_sys_conf_settings where conf_key='decimal_seperator';";

?>