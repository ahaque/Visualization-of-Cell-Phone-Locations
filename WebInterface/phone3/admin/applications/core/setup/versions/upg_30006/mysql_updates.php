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

# RC 2

/* 2.x update will already have these but 3.0.0 b/rc won't */
if ( ! ipsRegistry::DB()->checkForField( 'template_user_added', 'skin_templates' ) )
{
	$SQL[] = "ALTER TABLE skin_templates ADD template_user_added  INT(0) NOT NULL DEFAULT '0';";
	$SQL[] = "ALTER TABLE skin_templates ADD template_user_edited INT(0) NOT NULL DEFAULT '0';";
}

$SQL[] = "ALTER TABLE rc_reports CHANGE report report MEDIUMTEXT NOT NULL;";
$SQL[] = "ALTER TABLE skin_collections ADD INDEX parent_set_id ( set_parent_id, set_id );";

$SQL[] = "ALTER TABLE forums CHANGE last_poster_name last_poster_name VARCHAR( 255 ) NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE error_logs CHANGE log_error_code log_error_code VARCHAR( 24 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE tracker DROP INDEX tm_id;";
$SQL[] = "ALTER TABLE tracker ADD INDEX tm_id ( member_id , topic_id );";
$SQL[] = "ALTER TABLE forum_tracker DROP INDEX fm_id , ADD INDEX fm_id ( forum_id );";
$SQL[] = "ALTER TABLE core_sys_lang ADD INDEX ( lang_default );";
$SQL[] = "ALTER TABLE topics CHANGE starter_name starter_name VARCHAR( 255 ) NULL DEFAULT NULL,CHANGE last_poster_name last_poster_name VARCHAR( 255 ) NULL DEFAULT NULL;";

