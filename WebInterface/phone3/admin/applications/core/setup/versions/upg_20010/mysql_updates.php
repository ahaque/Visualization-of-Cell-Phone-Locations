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

# Fix bug where ICQ alt text missing last single quote

$SQL[] = "DELETE FROM ibf_skin_macro WHERE macro_value='PRO_ICQ' and macro_set=1";
$SQL[] = "INSERT INTO ibf_skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('PRO_ICQ', '<img src=\'style_images/<#IMG_DIR#>/profile_icq.gif\' border=\'0\'  alt=\'ICQ\' />', 1, 1);";

# Fix bug where "select * from members where temp_ban..." prevents NULL IS NOT NULL confusion

$SQL[] = "ALTER TABLE ibf_members change temp_ban temp_ban varchar(100) default '0'";
?>