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

# 3.0.1

$SQL[] = "ALTER TABLE  core_sys_lang CHANGE  lang_title  lang_title VARCHAR( 255 ) NOT NULL;";

# PM bug
$SQL[] = "DELETE FROM message_topic_user_map WHERE map_user_active=0 AND map_is_system=0 AND map_user_banned=0 AND map_is_starter=1;";

?>