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


# ALTER ALL OTHER TABLES

$SQL[] = "ALTER TABLE pfields_data
	ADD pf_group_id mediumint(4) unsigned NOT NULL default '0',
	ADD pf_icon varchar(255) default NULL,
	ADD pf_key varchar(255) default NULL,
	CHANGE pf_input_format pf_input_format TEXT;";

$SQL[] = "ALTER TABLE moderators DROP INDEX forum_id;";
$SQL[] = "ALTER TABLE moderators CHANGE forum_id forum_id TEXT NULL;";
$SQL[] = "ALTER TABLE moderators ADD mod_bitoptions INT UNSIGNED NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE groups ADD g_rep_max_positive MEDIUMINT( 8 ) UNSIGNED NOT NULL default '0',
ADD g_rep_max_negative MEDIUMINT( 8 ) UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE groups ADD g_mod_preview TINYINT( 1 ) UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE groups ADD g_signature_limits VARCHAR( 255 ) NULL ;";
$SQL[] = "ALTER TABLE groups ADD g_can_add_friends TINYINT( 1 ) NOT NULL DEFAULT '1';";
$SQL[] = "ALTER TABLE groups ADD g_hide_online_list TINYINT(1) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE groups ADD g_bitoptions INT UNSIGNED NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE groups ADD g_pm_perday SMALLINT NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE forums CHANGE id id SMALLINT( 5 ) NOT NULL AUTO_INCREMENT ;";
$SQL[] = "ALTER TABLE forums ADD can_view_others TINYINT( 1 ) NOT NULL DEFAULT '1';";
$SQL[] = "ALTER TABLE forums ADD min_posts_post INT( 10 ) UNSIGNED NOT NULL ;";
$SQL[] = "ALTER TABLE forums ADD min_posts_view INT( 10 ) UNSIGNED NOT NULL ;";
$SQL[] = "ALTER TABLE forums ADD hide_last_info TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE forums ADD name_seo VARCHAR( 255 ) NULL ;";
$SQL[] = "ALTER TABLE forums ADD seo_last_title VARCHAR(255) NOT NULL default '',
  				   ADD seo_last_name VARCHAR(255) NOT NULL default '';";
$SQL[] = "ALTER TABLE forums ADD last_x_topic_ids TEXT;";
$SQL[] = "ALTER TABLE forums ADD forums_bitoptions INT UNSIGNED NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE cache_store ADD cs_updated INT(10) NOT NULL default '0';";

$SQL[] = "ALTER TABLE task_manager ADD task_application VARCHAR( 100 ) NOT NULL ;";
$SQL[] = "ALTER TABLE task_manager CHANGE task_week_day task_week_day SMALLINT( 1 ) NOT NULL DEFAULT '-1';";

$SQL[] = "ALTER TABLE admin_permission_rows ADD row_id_type VARCHAR( 13 ) NOT NULL DEFAULT 'member' AFTER row_member_id ;";
$SQL[] = "ALTER TABLE admin_permission_rows CHANGE row_member_id row_id INT( 8 ) NOT NULL  ;";
$SQL[] = "ALTER TABLE admin_permission_rows DROP PRIMARY KEY  ;";
$SQL[] = "ALTER TABLE admin_permission_rows add PRIMARY KEY  (row_id,row_id_type);";

$SQL[] = "ALTER TABLE login_methods
  ADD login_alt_acp_html TEXT NULL AFTER login_alt_login_html,
  ADD login_order SMALLINT( 3 ) NOT NULL DEFAULT '0',
  DROP login_installed,
  DROP login_type,
  DROP login_allow_create;";

?>