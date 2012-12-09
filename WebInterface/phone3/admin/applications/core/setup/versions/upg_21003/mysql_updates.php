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

$SQL[] = "CREATE TABLE ibf_components (
	com_id             INT(10) NOT NULL auto_increment,
	com_title		   VARCHAR(255) NOT NULL default '',
	com_author		   VARCHAR(255) NOT NULL default '',
	com_url			   VARCHAR(255) NOT NULL default '',
	com_version		   VARCHAR(255) NOT NULL default '',
	com_date_added	   INT(10) NOT NULL default '0',
	com_menu_data	   MEDIUMTEXT NULL,
	com_enabled		   TINYINT(1) NOT NULL default '1',
	com_safemode	   TINYINT(1) NOT NULL default '1',
	com_section		   VARCHAR(255) NOT NULL default '',
	com_filename	   VARCHAR(255) NOT NULL default '',
	com_description	   VARCHAR(255) NOT NULL default '',
	com_url_title      VARCHAR(255) NOT NULL default '',
	com_url_uri        VARCHAR(255) NOT NULL default '',
	com_position	   INT(3) NOT NULL default '10',
	PRIMARY KEY(com_id)
);";


$SQL[] = "CREATE TABLE ibf_topic_views (
	views_tid int(10) NOT NULL default '0'
);";


$SQL[] = "CREATE TABLE ibf_topic_ratings (
	rating_id INT(10) NOT NULL auto_increment,
	rating_tid INT(10) NOT NULL default '0',
	rating_member_id mediumint(8) NOT NULL default '0',
	rating_value SMALLINT NOT NULL default '0',
	rating_ip_address VARCHAR(16) NOT NULL default '',
	PRIMARY KEY(rating_id),
	KEY rating_tid (rating_tid, rating_member_id)
);";

$SQL[] = "CREATE TABLE ibf_topic_markers (
	marker_member_id INT(8) NOT NULL default '0',
	marker_forum_id  INT(10) NOT NULL default '0',
	marker_last_update INT(10) NOT NULL default '0',
	marker_unread SMALLINT(5) NOT NULL default '0',
	marker_topics_read TEXT NULL,
	marker_last_cleared INT(10) NOT NULL default '0',
	UNIQUE KEY marker_forum_id( marker_forum_id, marker_member_id ),
	KEY marker_member_id (marker_member_id)
);";

$SQL[] = "CREATE TABLE ibf_rss_import (
	rss_import_id          INT(10) NOT NULL auto_increment,
	rss_import_enabled     TINYINT(1) NOT NULL default '0',
	rss_import_title       VARCHAR(255) NOT NULL default '',
	rss_import_url         VARCHAR(255) NOT NULL default '',
	rss_import_forum_id    INT(10) NOT NULL default '0',
	rss_import_mid         MEDIUMINT(8) NOT NULL default '0',
	rss_import_pergo	   SMALLINT(3) NOT NULL default '0',
	rss_import_time		   SMALLINT(3) NOT NULL default '0',
	rss_import_last_import INT(10) NOT NULL default '0',
	rss_import_showlink    VARCHAR(255) NOT NULL default '0',
	rss_import_topic_open  TINYINT(1) NOT NULL default '0',
	rss_import_topic_hide  TINYINT(1) NOT NULL default '0',
	rss_import_inc_pcount  TINYINT(1) NOT NULL default '0',
	rss_import_topic_pre   VARCHAR(50) NOT NULL default '',
	rss_import_charset     VARCHAR(200) NOT NULL default '',
	PRIMARY KEY ( rss_import_id )
);";

$SQL[] = "CREATE TABLE ibf_rss_imported (
	rss_imported_guid     CHAR(32) NOT NULL default '0',
	rss_imported_tid      INT(10) NOT NULL default '0',
	rss_imported_impid    INT(10) NOT NULL default '0',
	PRIMARY KEY ( rss_imported_guid ),
	KEY (rss_imported_impid)
);";

$SQL[] = "CREATE TABLE ibf_rss_export (
  rss_export_id int(10) NOT NULL auto_increment,
  rss_export_enabled tinyint(1) NOT NULL default '0',
  rss_export_title varchar(255) NOT NULL default '',
  rss_export_desc varchar(255) NOT NULL default '',
  rss_export_image varchar(255) NOT NULL default '',
  rss_export_forums text NULL,
  rss_export_include_post tinyint(1) NOT NULL default '0',
  rss_export_count smallint(3) NOT NULL default '0',
  rss_export_cache_time smallint(3) NOT NULL default '30',
  rss_export_cache_last int(10) NOT NULL default '0',
  rss_export_cache_content mediumtext NULL,
  rss_export_sort varchar(4) NOT NULL default 'DESC',
  rss_export_order varchar(20) NOT NULL default 'start_date',
  PRIMARY KEY  (rss_export_id)
);";

$SQL[] = "CREATE TABLE ibf_cal_events (
	event_id			INT(10) UNSIGNED NOT NULL auto_increment,
	event_calendar_id	INT(10) UNSIGNED NOT NULL DEFAULT '0',
	event_member_id		MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
	event_content		MEDIUMTEXT NULL,
	event_title			VARCHAR(255) NOT NULL DEFAULT '',
	event_smilies		TINYINT(1) NOT NULL DEFAULT '0',
	event_perms			TEXT NULL,
	event_private		TINYINT(1) NOT NULL DEFAULT '0',
	event_approved		TINYINT(1) NOT NULL DEFAULT '0',
	event_unixstamp		INT(10) UNSIGNED NOT NULL DEFAULT '0',
	event_recurring		INT(2) UNSIGNED NOT NULL DEFAULT '0',
	event_tz			INT(4) NOT NULL DEFAULT '0',
	event_unix_from		INT(10) UNSIGNED NOT NULL DEFAULT '0',
	event_unix_to		INT(10) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (event_id),
	KEY daterange (event_calendar_id,event_approved,event_unix_from,event_unix_to),
	KEY approved ( event_calendar_id, event_approved )
);";

$SQL[] = "CREATE TABLE ibf_members_partial (
	partial_id	      INT(10) NOT NULL auto_increment,
	partial_member_id INT(8) NOT NULL default '0',
	partial_date	  INT(10) NOT NULL default '0',
	PRIMARY KEY( partial_id ),
	KEY partial_member_id ( partial_member_id )
);";

$SQL[] = "CREATE TABLE ibf_dnames_change (
	dname_id			INT(10) NOT NULL auto_increment,
	dname_member_id		INT(8) NOT NULL default '0',
	dname_date			INT(10) NOT NULL default '0',
	dname_ip_address	VARCHAR(16) NOT NULL default '',
	dname_previous      VARCHAR(255) NOT NULL default '',
	dname_current       VARCHAR(255) NOT NULL default '',
	PRIMARY KEY( dname_id ),
	KEY dname_member_id(dname_member_id),
	KEY date_id ( dname_member_id, dname_date )
);";

$SQL[] = "CREATE TABLE ibf_cal_calendars (
	cal_id				INT(10) UNSIGNED NOT NULL auto_increment,
	cal_title			VARCHAR(255) NOT NULL DEFAULT '0',
	cal_moderate		TINYINT(1) NOT NULL DEFAULT '0',
	cal_position		INT(3) UNSIGNED NOT NULL DEFAULT '0',
	cal_event_limit		INT(2) UNSIGNED NOT NULL DEFAULT '0',
	cal_bday_limit		INT(2) UNSIGNED NOT NULL DEFAULT '0',
	cal_rss_export		TINYINT(1) NOT NULL DEFAULT '0',
	cal_rss_export_days TINYINT(1) NOT NULL DEFAULT '0',
	cal_rss_export_max  INT(3) UNSIGNED NOT NULL DEFAULT '0',
	cal_rss_update		INT(3) UNSIGNED NOT NULL DEFAULT '0',
	cal_rss_update_last INT(10) UNSIGNED NOT NULL DEFAULT '0',
	cal_rss_cache		MEDIUMTEXT NULL,
	cal_permissions		MEDIUMTEXT NULL,
	PRIMARY KEY ( cal_id )
);";

$SQL[] = "CREATE TABLE ibf_login_methods (
	login_id			 INT(10) NOT NULL auto_increment,
	login_title			 VARCHAR(255) NOT NULL default '',
	login_description    VARCHAR(255) NOT NULL default '',
	login_folder_name    VARCHAR(255) NOT NULL default '',
	login_maintain_url	 VARCHAR(255) NOT NULL default '',
	login_register_url	 VARCHAR(255) NOT NULL default '',
	login_type			 VARCHAR(30) NOT NULL default '',
	login_alt_login_html TEXT NULL,
	login_date			 INT(10) NOT NULL default '0',
	login_settings		 INT(1) NOT NULL default '0',
	login_enabled		 INT(1) NOT NULL default '0',
	login_safemode		 INT(1) NOT NULL default '0',
	login_installed		 INT(1) NOT NULL default '0',
	login_replace_form   INT(1) NOT NULL default '0',
	login_allow_create   INT(1) NOT NULL default '0',
	PRIMARY KEY (login_id)
);";

$SQL[] = "CREATE TABLE ibf_admin_permission_rows (
	row_member_id	INT(8) NOT NULL,
	row_perm_cache	MEDIUMTEXT NULL,
	row_updated		INT(10) NOT NULL DEFAULT '0',
	PRIMARY KEY (row_member_id)
);";

$SQL[] = "CREATE TABLE ibf_admin_permission_keys (
	perm_key	VARCHAR(255) NOT NULL,
	perm_main	VARCHAR(255) NOT NULL,
	perm_child	VARCHAR(255) NOT NULL,
	perm_bit	VARCHAR(255) NOT NULL,
	PRIMARY KEY    (perm_key),
	KEY	perm_main  (perm_main),
	KEY perm_child (perm_child)
);";

$SQL[] = "CREATE TABLE ibf_templates_diff_import (
	diff_key			VARCHAR(255) NOT NULL,
	diff_func_group		VARCHAR(150) NOT NULL,
	diff_func_name		VARCHAR(250) NOT NULL,
	diff_func_data		TEXT NULL,
	diff_func_content	MEDIUMTEXT NULL,
	diff_session_id		INT(10) NOT NULL default '0',
	PRIMARY KEY (diff_key),
	KEY diff_func_group (diff_func_group),
	KEY diff_func_name (diff_func_name)
);";

$SQL[] = "CREATE TABLE ibf_template_diff_session (
	diff_session_id				INT(10) NOT NULL auto_increment,
	diff_session_togo			INT(10) NOT NULL default '0',
	diff_session_done			INT(10) NOT NULL default '0',
	diff_session_updated		INT(10) NOT NULL default '0',
	diff_session_title			VARCHAR(255) NOT NULL default '',
	diff_session_ignore_missing INT(1) NOT NULL default '0',
	PRIMARY KEY (diff_session_id)
);";

$SQL[] = "CREATE TABLE ibf_template_diff_changes (
	diff_change_key			VARCHAR(255) NOT NULL,
	diff_change_func_group	VARCHAR(150) NOT NULL,
	diff_change_func_name	VARCHAR(250) NOT NULL,
	diff_change_content		MEDIUMTEXT NULL,
	diff_change_type		INT(1) NOT NULL default '0',
	diff_session_id		    INT(10) NOT NULL default '0',
	PRIMARY KEY (diff_change_key),
	KEY diff_change_func_group (diff_change_func_group),
	KEY diff_change_type (diff_change_type)
);";

?>