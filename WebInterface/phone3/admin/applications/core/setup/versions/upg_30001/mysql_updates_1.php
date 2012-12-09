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


# ALTER MEMBER TABLES
$SQL[] = "ALTER TABLE members CHANGE id member_id MEDIUMINT(8) NOT NULL auto_increment;";
$SQL[] = "ALTER TABLE members CHANGE mgroup member_group_id smallint(3) NOT NULL default '0';";
$SQL[] = "ALTER TABLE members ADD members_pass_hash varchar(32) NOT NULL default '', ADD members_pass_salt varchar(5) NOT NULL default '';";

$SQL[] = "ALTER TABLE members ADD member_banned TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE members ADD INDEX ( member_banned ) ;";
$SQL[] = "ALTER TABLE members ADD identity_url TEXT NULL;";

# FIX NULL COLUMNS
$SQL[] = "UPDATE members SET new_msg=0 WHERE new_msg IS NULL;";
$SQL[] = "UPDATE members SET msg_total=0 WHERE msg_total IS NULL;";
$SQL[] = "UPDATE members SET show_popup=0 WHERE show_popup IS NULL;";

$SQL[] = "ALTER TABLE members CHANGE new_msg msg_count_new INT(2) NOT NULL default '0';";
$SQL[] = "ALTER TABLE members CHANGE msg_total msg_count_total INT(3) NOT NULL default '0';";
$SQL[] = "ALTER TABLE members ADD msg_count_reset INT(1) NOT NULL default '0' AFTER msg_count_total;";
$SQL[] = "ALTER TABLE members CHANGE show_popup msg_show_notification INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE members ADD member_uploader VARCHAR( 32 ) NOT NULL DEFAULT 'default';";
$SQL[] = "ALTER TABLE members DROP members_markers;";
$SQL[] = "ALTER TABLE members ADD members_seo_name varchar(255) NOT NULL default '' AFTER members_display_name;";
$SQL[] = "ALTER TABLE members ADD members_bitoptions INT UNSIGNED NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE members ADD fb_uid INT(10) UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE members ADD fb_emailhash VARCHAR(60) NOT NULL default '';";
$SQL[] = "ALTER TABLE members ADD fb_emailallow INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE members ADD fb_lastsync INT(10) NOT NULL default '0';";

$SQL[] = "ALTER TABLE profile_portal ADD notes TEXT NULL ,
ADD links TEXT NULL ,
ADD bio TEXT NULL ,
ADD ta_size VARCHAR( 3 ) NULL ,
ADD signature TEXT NULL ,
ADD avatar_location VARCHAR( 255 ) NULL ,
ADD avatar_size VARCHAR( 9 ) NOT NULL DEFAULT '0',
ADD avatar_type VARCHAR( 15 ) NULL ,
ADD pconversation_filters TEXT NULL,
ADD fb_photo	TEXT,
ADD fb_photo_thumb TEXT,
ADD fb_bwoptions INT UNSIGNED NOT NULL DEFAULT '0',
ADD pp_reputation_points INT( 10 ) NOT NULL DEFAULT '0',
ADD pp_status TEXT NULL,
ADD pp_status_update VARCHAR( 13 ) NOT NULL DEFAULT '0';";

$SQL[] = "UPDATE groups SET g_icon=REPLACE(g_icon,'style_images/<#IMG_DIR#>/folder_team_icons/admin.gif','public/style_extra/team_icons/admin.png');";
$SQL[] = "UPDATE groups SET g_icon=REPLACE(g_icon,'style_images/<#IMG_DIR#>/folder_team_icons/','public/style_extra/team_icons/');";

