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


# CREATE NEW TABLES


$SQL[] = "DROP TABLE sessions;";

$SQL[] = "CREATE TABLE sessions (
  id varchar(60) NOT NULL default '0',
  member_name varchar(64) default NULL,
  seo_name varchar(255) NOT NULL default '',
  member_id mediumint(8) NOT NULL default '0',
  ip_address varchar(16) default NULL,
  browser varchar(200) NOT NULL default '',
  running_time int(10) default NULL,
  login_type char(3) default '',
  location varchar(40) default NULL,
  member_group smallint(3) default NULL,
  in_error tinyint(1) NOT NULL default '0',
  location_1_type varchar(10) NOT NULL default '',
  location_1_id int(10) NOT NULL default '0',
  location_2_type varchar(10) NOT NULL default '',
  location_2_id int(10) NOT NULL default '0',
  location_3_type varchar(10) NOT NULL default '',
  location_3_id int(10) NOT NULL default '0',
  current_appcomponent varchar(100) NOT NULL default '',
  current_module varchar(100) NOT NULL default '',
  current_section varchar(100) NOT NULL default '',
  uagent_key varchar(200) NOT NULL default '',
  uagent_version varchar(100) NOT NULL default '',
  uagent_type varchar(200) NOT NULL default '',
  uagent_bypass int(1) NOT NULL default '0',
  search_thread_id int(11) NOT NULL default '0',
  search_thread_time varchar(13) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY location1 (location_1_type,location_1_id),
  KEY location2 (location_2_type,location_2_id),
  KEY location3 (location_3_type,location_3_id),
  KEY running_time (running_time)
);";

$SQL[] = "RENAME TABLE custom_bbcode TO custom_bbcode_old;";

$SQL[] = "CREATE TABLE custom_bbcode (
  bbcode_id int(10) NOT NULL auto_increment,
  bbcode_title varchar(255) NOT NULL default '',
  bbcode_desc text,
  bbcode_tag varchar(255) NOT NULL default '',
  bbcode_replace text,
  bbcode_useoption tinyint(1) NOT NULL default '0',
  bbcode_example text,
  bbcode_switch_option int(1) NOT NULL default '0',
  bbcode_menu_option_text varchar(200) NOT NULL default '',
  bbcode_menu_content_text varchar(200) NOT NULL default '',
  bbcode_single_tag tinyint(1) NOT NULL default '0',
  bbcode_groups varchar(255) default NULL,
  bbcode_sections varchar(255) default NULL,
  bbcode_php_plugin varchar(255) default NULL,
  bbcode_parse smallint(2) NOT NULL default '1',
  bbcode_no_parsing tinyint(1) NOT NULL default '0',
  bbcode_protected tinyint(1) NOT NULL default '0',
  bbcode_aliases varchar(255) default NULL,
  bbcode_optional_option tinyint(1) NOT NULL default '0',
  bbcode_image varchar(255) default NULL,
  bbcode_strip_search tinyint(1) NOT NULL default '0',
  bbcode_app varchar(50) NOT NULL default '',
  PRIMARY KEY  (bbcode_id)
);";


$SQL[] = "CREATE TABLE profile_friends_flood (
  friends_id int(10) NOT NULL auto_increment,
  friends_member_id int(10) unsigned NOT NULL default '0',
  friends_friend_id int(10) unsigned NOT NULL default '0',
  friends_removed int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (friends_id),
  KEY my_friends (friends_member_id,friends_friend_id),
  KEY friends_member_id (friends_member_id)
);";

$SQL[] = "CREATE TABLE core_sys_cp_sessions (
  session_id varchar(32) NOT NULL default '',
  session_ip_address varchar(32) NOT NULL default '',
  session_member_name varchar(250) NOT NULL default '',
  session_member_id mediumint(8) NOT NULL default '0',
  session_member_login_key varchar(32) NOT NULL default '',
  session_location varchar(64) NOT NULL default '',
  session_log_in_time int(10) NOT NULL default '0',
  session_running_time int(10) NOT NULL default '0',
  session_url TEXT,
  session_app_data text,
  PRIMARY KEY  (session_id)
);";

$SQL[] = "CREATE TABLE core_sys_settings_titles (
  conf_title_id smallint(3) NOT NULL auto_increment,
  conf_title_title varchar(255) NOT NULL default '',
  conf_title_desc text,
  conf_title_count smallint(3) NOT NULL default '0',
  conf_title_noshow tinyint(1) NOT NULL default '0',
  conf_title_keyword varchar(200) NOT NULL default '',
  conf_title_module varchar(200) NOT NULL default '',
  conf_title_app varchar(200) NOT NULL default '',
  conf_title_tab varchar(32) default NULL,
  PRIMARY KEY  (conf_title_id)
);";

$SQL[] = "CREATE TABLE core_sys_conf_settings (
  conf_id int(10) NOT NULL auto_increment,
  conf_title varchar(255) NOT NULL default '',
  conf_description text,
  conf_group smallint(3) NOT NULL default '0',
  conf_type varchar(255) NOT NULL default '',
  conf_key varchar(255) NOT NULL default '',
  conf_value text,
  conf_default text,
  conf_extra text,
  conf_evalphp text,
  conf_protected tinyint(1) NOT NULL default '0',
  conf_position smallint(3) NOT NULL default '0',
  conf_start_group varchar(255) NOT NULL default '',
  conf_end_group tinyint(1) NOT NULL default '0',
  conf_add_cache tinyint(1) NOT NULL default '1',
  conf_keywords text,
  PRIMARY KEY  (conf_id)
);";

$SQL[] = "CREATE TABLE core_item_markers (
  item_key char(32) NOT NULL,
  item_member_id int(8) NOT NULL default '0',
  item_app varchar(255) NOT NULL default 'core',
  item_last_update int(10) NOT NULL default '0',
  item_last_saved int(10) NOT NULL default '0',
  item_unread_count int(5) NOT NULL default '0',
  item_read_array mediumtext,
  item_global_reset int(10) NOT NULL default '0',
  item_app_key_1 int(10) NOT NULL default '0',
  item_app_key_2 int(10) NOT NULL default '0',
  item_app_key_3 int(10) NOT NULL default '0',
  UNIQUE KEY combo_key (item_key,item_member_id),
  KEY marker_index (item_member_id,item_app),
  KEY item_member_id (item_member_id)
);";

$SQL[] = "CREATE TABLE core_item_markers_storage (
  item_member_id int(8) NOT NULL default '0',
  item_markers mediumtext,
  item_last_updated int(10) NOT NULL default '0',
  item_last_saved int(10) NOT NULL default '0',
  PRIMARY KEY  (item_member_id)
);";

$SQL[] = "CREATE TABLE template_sandr (
  sandr_session_id int(10) NOT NULL auto_increment,
  sandr_set_id int(10) NOT NULL default '0',
  sandr_search_only int(1) NOT NULL default '0',
  sandr_search_all int(1) NOT NULL default '0',
  sandr_search_for text,
  sandr_replace_with text,
  sandr_is_regex int(1) NOT NULL default '0',
  sandr_template_count int(5) NOT NULL default '0',
  sandr_template_processed int(5) NOT NULL default '0',
  sandr_results mediumtext,
  sandr_updated int(10) NOT NULL default '0',
  PRIMARY KEY  (sandr_session_id)
);";

$SQL[] = "CREATE TABLE question_and_answer (
  qa_id int(11) NOT NULL auto_increment,
  qa_question text,
  qa_answers text,
  PRIMARY KEY  (qa_id)
);";

$SQL[] = "CREATE TABLE mod_queued_items (
  id int(11) NOT NULL auto_increment,
  `type` varchar(32) NOT NULL default 'post',
  type_id int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY type_id (type_id)
);";

$SQL[] = "RENAME TABLE message_topics TO message_topics_old;";

$SQL[] = "CREATE TABLE message_topics (
  mt_id int(10) NOT NULL auto_increment,
  mt_date int(10) NOT NULL default '0',
  mt_title varchar(255) NOT NULL default '',
  mt_hasattach smallint(5) NOT NULL default '0',
  mt_starter_id int(10) NOT NULL default '0',
  mt_start_time int(10) NOT NULL default '0',
  mt_last_post_time int(10) NOT NULL default '0',
  mt_invited_members text,
  mt_to_count int(3) NOT NULL default '0',
  mt_to_member_id int(10) NOT NULL default '0',
  mt_replies int(10) NOT NULL default '0',
  mt_last_msg_id int(10) NOT NULL default '0',
  mt_first_msg_id int(10) NOT NULL default '0',
  mt_is_draft int(1) NOT NULL default '0',
  mt_is_deleted int(1) NOT NULL default '0',
  mt_is_system int(1) NOT NULL default '0',
  PRIMARY KEY  (mt_id),
  KEY mt_starter_id (mt_starter_id)
);";

$SQL[] = "CREATE TABLE message_topic_user_map (
  map_id		int(10) NOT NULL  auto_increment,
  map_user_id int(10) NOT NULL default '0',
  map_topic_id int(10) NOT NULL default '0',
  map_folder_id varchar(32) NOT NULL default '',
  map_read_time int(10) NOT NULL default '0',
  map_user_active int(1) NOT NULL default '0',
  map_user_banned int(1) NOT NULL default '0',
  map_has_unread int(1) NOT NULL default '0',
  map_is_system int(1) NOT NULL default '0',
  map_is_starter int(1) NOT NULL default '0',
  map_left_time int(10) NOT NULL default '0',
  map_ignore_notification int(1) NOT NULL default '0',
  PRIMARY KEY map_id (map_id),
  UNIQUE KEY map_main (map_user_id,map_topic_id),
  KEY map_user (map_user_id,map_folder_id)
);";

$SQL[] = "CREATE TABLE message_posts (
  msg_id int(10) NOT NULL auto_increment,
  msg_topic_id int(10) NOT NULL default '0',
  msg_date int(10) default NULL,
  msg_post text,
  msg_post_key varchar(32) NOT NULL default '0',
  msg_author_id mediumint(8) NOT NULL default '0',
  msg_ip_address varchar(16) NOT NULL default '0',
  msg_is_first_post int(1) NOT NULL default '0',
  PRIMARY KEY  (msg_id),
  KEY msg_topic_id (msg_topic_id),
  KEY msg_date (msg_date)
);";

$SQL[] = "CREATE TABLE error_logs (
  log_id int(11) NOT NULL auto_increment,
  log_member int(11) NOT NULL default '0',
  log_date varchar(13) NOT NULL default '0',
  log_error text,
  log_error_code varchar(24) NOT NULL DEFAULT '0',
  log_ip_address varchar(32) default NULL,
  log_request_uri text,
  PRIMARY KEY  (log_id),
  KEY log_date (log_date)
);";

$SQL[] = "CREATE TABLE pfields_groups (
  pf_group_id mediumint(4) unsigned NOT NULL auto_increment,
  pf_group_name varchar(255) NOT NULL,
  pf_group_key varchar(255) NOT NULL,
  PRIMARY KEY  (pf_group_id)
);";

$SQL[] = "DROP TABLE IF EXISTS rc_classes";
$SQL[] = "DROP TABLE IF EXISTS rc_comments";
$SQL[] = "DROP TABLE IF EXISTS rc_modpref";
$SQL[] = "DROP TABLE IF EXISTS rc_reports";
$SQL[] = "DROP TABLE IF EXISTS rc_reports_index";
$SQL[] = "DROP TABLE IF EXISTS rc_status";
$SQL[] = "DROP TABLE IF EXISTS rc_status_sev";

$SQL[] = "CREATE TABLE rc_classes (
  com_id smallint(4) NOT NULL auto_increment,
  onoff tinyint(1) NOT NULL default '0',
  class_title varchar(255) NOT NULL default '',
  class_desc text NOT NULL,
  author varchar(255) NOT NULL default '',
  author_url varchar(255) NOT NULL default '',
  pversion varchar(255) NOT NULL default '',
  my_class varchar(100) NOT NULL default '',
  group_can_report varchar(255) NOT NULL default '',
  mod_group_perm varchar(255) NOT NULL default '',
  extra_data text NOT NULL,
  lockd tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (com_id)
);";

$SQL[] = "CREATE TABLE rc_comments (
  id int(10) NOT NULL auto_increment,
  rid int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  comment_by mediumint(8) NOT NULL default '0',
  comment_date int(10) NOT NULL default '0',
  PRIMARY KEY  (id)
);";

$SQL[] = "CREATE TABLE rc_modpref (
  mem_id mediumint(8) NOT NULL default '0',
  by_pm tinyint(1) NOT NULL default '0',
  by_email tinyint(1) NOT NULL default '0',
  by_alert tinyint(1) NOT NULL default '0',
  rss_key varchar(32) NOT NULL default '',
  max_points smallint(3) NOT NULL default '0',
  reports_pp smallint(3) NOT NULL default '0',
  rss_cache mediumtext NOT NULL,
  PRIMARY KEY  (mem_id)
);";

$SQL[] = "CREATE TABLE rc_reports (
  id int(10) NOT NULL auto_increment,
  rid int(11) NOT NULL default '0',
  report text NOT NULL,
  report_by mediumint(8) NOT NULL default '0',
  date_reported int(10) NOT NULL default '0',
  PRIMARY KEY  (id)
);";

$SQL[] = "CREATE TABLE rc_reports_index (
  id int(11) NOT NULL auto_increment,
  uid varchar(32) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  `status` smallint(2) NOT NULL default '1',
  url varchar(255) NOT NULL default '',
  img_preview varchar(255) NOT NULL default '',
  rc_class smallint(3) NOT NULL default '0',
  updated_by mediumint(8) NOT NULL default '0',
  date_updated int(10) NOT NULL default '0',
  date_created int(10) NOT NULL default '0',
  exdat1 int(10) NOT NULL default '0',
  exdat2 int(10) NOT NULL default '0',
  exdat3 int(10) NOT NULL default '0',
  num_reports smallint(4) NOT NULL default '0',
  num_comments smallint(4) NOT NULL default '0',
  seoname varchar(255) default NULL,
  seotemplate varchar(255) default NULL,
  PRIMARY KEY  (id),
  KEY uid (uid)
);";

$SQL[] = "CREATE TABLE rc_status (
  `status` tinyint(2) NOT NULL auto_increment,
  title varchar(100) NOT NULL default '',
  points_per_report smallint(4) NOT NULL default '1',
  minutes_to_apoint double NOT NULL default '5',
  is_new tinyint(1) NOT NULL default '0',
  is_complete tinyint(1) NOT NULL default '0',
  is_active tinyint(1) NOT NULL default '0',
  rorder smallint(3) NOT NULL default '0',
  PRIMARY KEY  (`status`)
);";

$SQL[] = "CREATE TABLE rc_status_sev (
  id smallint(4) NOT NULL auto_increment,
  `status` tinyint(2) NOT NULL default '0',
  points smallint(4) NOT NULL default '0',
  img varchar(255) NOT NULL default '',
  is_png tinyint(1) NOT NULL default '0',
  width smallint(3) NOT NULL default '16',
  height smallint(3) NOT NULL default '16',
  PRIMARY KEY  (id),
  KEY `status` (`status`,points)
);";

$SQL[] = "CREATE TABLE reputation_levels (
  level_id mediumint(8) unsigned NOT NULL auto_increment,
  level_points int(10) NOT NULL,
  level_title varchar(255) NOT NULL,
  level_image varchar(255) NOT NULL,
  PRIMARY KEY  (level_id)
);";

$SQL[] = "CREATE TABLE reputation_cache (
  id bigint(10) unsigned NOT NULL auto_increment,
  app varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  type_id int(10) unsigned NOT NULL,
  rep_points int(10) NOT NULL,
  PRIMARY KEY  (id),
  KEY app (app,`type`,type_id)
);";

$SQL[] = "CREATE TABLE reputation_index (
  id bigint(10) unsigned NOT NULL auto_increment,
  member_id mediumint(8) unsigned NOT NULL,
  app varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  type_id int(10) unsigned NOT NULL,
  misc text NOT NULL,
  rep_date int(10) unsigned NOT NULL,
  rep_msg text NOT NULL,
  rep_rating tinyint(1) NOT NULL,
  PRIMARY KEY  (id),
  KEY app (app,`type`,type_id,member_id)
);";

$SQL[] = "CREATE TABLE core_hooks (
  hook_id mediumint(4) unsigned NOT NULL auto_increment,
  hook_enabled tinyint(1) NOT NULL default '0',
  hook_name varchar(255) default NULL,
  hook_desc text,
  hook_author varchar(255) default NULL,
  hook_email varchar(255) default NULL,
  hook_website text,
  hook_update_check text,
  hook_requirements text,
  hook_version_human varchar(32) default NULL,
  hook_version_long varchar(32) NOT NULL default '0',
  hook_installed varchar(13) NOT NULL default '0',
  hook_updated varchar(13) NOT NULL default '0',
  hook_position mediumint(9) NOT NULL default '0',
  hook_extra_data text,
  hook_key VARCHAR( 32 ) NULL,
  PRIMARY KEY  (hook_id)
);";

$SQL[] = "CREATE TABLE core_hooks_files (
  hook_file_id int(10) NOT NULL auto_increment,
  hook_hook_id int(10) NOT NULL default '0',
  hook_file_stored varchar(255) default NULL,
  hook_file_real varchar(255) default NULL,
  hook_type varchar(32) default NULL,
  hook_classname varchar(255) default NULL,
  hook_data text,
  hooks_source longtext,
  PRIMARY KEY  (hook_file_id),
  KEY hook_hook_id (hook_hook_id)
);";

$SQL[] = "CREATE TABLE tags_index (
  id bigint(10) unsigned NOT NULL auto_increment,
  app varchar(255) NOT NULL,
  tag varchar(255) NOT NULL,
  `type` varchar(32) NOT NULL,
  type_id bigint(10) unsigned NOT NULL,
  type_2 varchar(32) NOT NULL,
  type_id_2 bigint(10) unsigned NOT NULL,
  updated int(10) unsigned NOT NULL,
  misc text NOT NULL,
  member_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (id),
  KEY app (app)
);";

$SQL[] = "CREATE TABLE core_uagents (
  uagent_id int(10) NOT NULL auto_increment,
  uagent_key varchar(200) NOT NULL default '',
  uagent_name varchar(200) NOT NULL default '',
  uagent_regex text,
  uagent_regex_capture int(1) NOT NULL default '0',
  uagent_type varchar(200) NOT NULL default '',
  uagent_position int(10) NOT NULL default '0',
  PRIMARY KEY  (uagent_id),
  KEY uagent_key (uagent_key)
);";

$SQL[] = "CREATE TABLE core_uagent_groups (
  ugroup_id int(10) NOT NULL auto_increment,
  ugroup_title varchar(255) NOT NULL default '',
  ugroup_array mediumtext,
  PRIMARY KEY  (ugroup_id)
);";

$SQL[] = "CREATE TABLE skin_replacements (
  replacement_id int(10) NOT NULL auto_increment,
  replacement_key varchar(255) NOT NULL default '',
  replacement_content text,
  replacement_set_id int(10) NOT NULL default '0',
  replacement_added_to int(10) NOT NULL default '0',
  PRIMARY KEY  (replacement_id),
  KEY replacement_set_id (replacement_set_id)
);";

$SQL[] = "RENAME TABLE skin_templates TO skin_templates_old";

$SQL[] = "CREATE TABLE skin_templates (
  template_id int(10) NOT NULL auto_increment,
  template_set_id int(10) NOT NULL default '0',
  template_group varchar(255) NOT NULL default '',
  template_content mediumtext,
  template_name varchar(255) default NULL,
  template_data text,
  template_updated int(10) NOT NULL default '0',
  template_removable int(4) NOT NULL default '0',
  template_added_to int(10) NOT NULL default '0',
  template_user_added  INT(0) NOT NULL DEFAULT '0',
  template_user_edited INT(0) NOT NULL DEFAULT '0',
  PRIMARY KEY  (template_id)
);";

$SQL[] = "CREATE TABLE skin_collections (
  set_id int(10) NOT NULL auto_increment,
  set_name varchar(200) NOT NULL default '',
  set_key varchar(100) NOT NULL default '',
  set_parent_id int(5) NOT NULL default '-1',
  set_parent_array mediumtext,
  set_child_array mediumtext,
  set_permissions text NOT NULL,
  set_is_default int(1) NOT NULL default '0',
  set_author_name varchar(255) NOT NULL default '',
  set_author_url varchar(255) NOT NULL default '',
  set_image_dir varchar(255) NOT NULL default 'default',
  set_emo_dir varchar(255) NOT NULL default 'default',
  set_css_inline int(1) NOT NULL default '0',
  set_css_groups text,
  set_added int(10) NOT NULL default '0',
  set_updated int(10) NOT NULL default '0',
  set_output_format varchar(200) NOT NULL default 'html',
  set_locked_uagent mediumtext,
  set_hide_from_list int(1) NOT NULL default '0',
  PRIMARY KEY  (set_id)
);";

$SQL[] = "CREATE TABLE skin_css (
  css_id int(10) NOT NULL auto_increment,
  css_set_id int(10) NOT NULL default '0',
  css_updated int(10) NOT NULL default '0',
  css_group varchar(255) NOT NULL default '0',
  css_content mediumtext,
  css_position int(10) NOT NULL default '0',
  css_added_to int(10) NOT NULL default '0',
  css_app varchar(200) NOT NULL default '0',
  css_app_hide int(1) NOT NULL default '0',
  css_attributes text,
  css_removed int(1) NOT NULL default '0',
  PRIMARY KEY  (css_id)
);";

$SQL[] = "CREATE TABLE skin_cache (
  cache_id int(10) NOT NULL auto_increment,
  cache_updated int(10) NOT NULL default '0',
  cache_type varchar(200) NOT NULL default '',
  cache_set_id int(10) NOT NULL default '0',
  cache_key_1 varchar(200) NOT NULL default '',
  cache_value_1 varchar(200) NOT NULL default '',
  cache_key_2 varchar(200) NOT NULL default '',
  cache_value_2 varchar(200) NOT NULL default '',
  cache_value_3 varchar(200) NOT NULL default '',
  cache_content mediumtext NOT NULL,
  cache_key_3 varchar(200) NOT NULL default '',
  cache_key_4 varchar(200) NOT NULL default '',
  cache_value_4 varchar(200) NOT NULL default '',
  cache_key_5 varchar(200) NOT NULL default '',
  cache_value_5 varchar(200) NOT NULL default '',
  PRIMARY KEY  (cache_id),
  KEY cache_type (cache_type),
  KEY cache_set_id (cache_set_id)
);";

$SQL[] = "CREATE TABLE bbcode_mediatag (
  mediatag_id smallint(10) unsigned NOT NULL auto_increment,
  mediatag_name varchar(255) NOT NULL,
  mediatag_match text,
  mediatag_replace text,
  PRIMARY KEY  (mediatag_id)
);";

$SQL[] = "CREATE TABLE ignored_users (
  ignore_id int(10) NOT NULL auto_increment,
  ignore_owner_id int(8) NOT NULL default '0',
  ignore_ignore_id int(8) NOT NULL default '0',
  ignore_messages int(1) NOT NULL default '0',
  ignore_topics int(1) NOT NULL default '0',
  PRIMARY KEY  (ignore_id),
  KEY ignore_owner_id (ignore_owner_id),
  KEY ignore_ignore_id (ignore_ignore_id)
);";

$SQL[] = "CREATE TABLE search_index (
  app varchar(255) NOT NULL,
  content text NOT NULL,
  content_title varchar(255) NOT NULL,
  `type` varchar(32) NOT NULL,
  type_id bigint(10) unsigned NOT NULL,
  type_2 varchar(32) NOT NULL,
  type_id_2 bigint(10) unsigned NOT NULL,
  updated int(10) unsigned NOT NULL,
  misc text NOT NULL,
  member_id mediumint(8) unsigned NOT NULL,
  KEY app (app)
);";

$SQL[] = "CREATE TABLE captcha (
  captcha_unique_id varchar(32) NOT NULL default '',
  captcha_string varchar(100) NOT NULL default '',
  captcha_ipaddress varchar(16) NOT NULL default '',
  captcha_date int(10) NOT NULL default '0',
  PRIMARY KEY  (captcha_unique_id)
);";

$SQL[] = "CREATE TABLE permission_index (
  perm_id int(10) unsigned NOT NULL auto_increment,
  app varchar(32) NOT NULL,
  perm_type varchar(32) NOT NULL,
  perm_type_id int(10) unsigned NOT NULL,
  perm_view text NOT NULL,
  perm_2 text NOT NULL,
  perm_3 text NOT NULL,
  perm_4 text NOT NULL,
  perm_5 text NOT NULL,
  perm_6 text NOT NULL,
  perm_7 text NOT NULL,
  owner_only tinyint(1) NOT NULL default '0',
  friend_only tinyint(1) NOT NULL default '0',
  authorized_users varchar(255) default NULL,
  PRIMARY KEY  (perm_id),
  KEY perm_index (perm_type,perm_type_id),
  KEY perm_type (app,perm_type,perm_type_id)
);";

$SQL[] = "CREATE TABLE openid_temp (
  id varchar(32) NOT NULL,
  referrer text,
  privacy tinyint(1) NOT NULL default '0',
  cookiedate tinyint(1) NOT NULL default '0',
  fullurl text,
  PRIMARY KEY  (id)
);";

$SQL[] = "CREATE TABLE core_applications (
  app_id int(10) NOT NULL auto_increment,
  app_title varchar(255) NOT NULL default '',
  app_public_title varchar(255) NOT NULL default '',
  app_description varchar(255) NOT NULL default '',
  app_author varchar(255) NOT NULL default '',
  app_version varchar(255) NOT NULL default '',
  app_long_version int(10) NOT NULL default '10000',
  app_directory varchar(255) NOT NULL default '',
  app_added int(10) NOT NULL default '0',
  app_position int(2) NOT NULL default '0',
  app_protected int(1) NOT NULL default '0',
  app_enabled int(1) NOT NULL default '0',
  app_location varchar(32) NOT NULL default '',
  app_hide_tab TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (app_id)
);";

$SQL[] = "CREATE TABLE core_sys_module (
  sys_module_id mediumint(4) unsigned NOT NULL auto_increment,
  sys_module_title varchar(32) NOT NULL default '',
  sys_module_application varchar(32) NOT NULL default '',
  sys_module_key varchar(32) NOT NULL default '',
  sys_module_description varchar(100) NOT NULL default '',
  sys_module_version varchar(10) NOT NULL default '',
  sys_module_parent varchar(32) NOT NULL default '',
  sys_module_protected tinyint(1) unsigned NOT NULL default '0',
  sys_module_visible tinyint(1) unsigned NOT NULL default '1',
  sys_module_tables varchar(255) NOT NULL default '',
  sys_module_hooks varchar(255) NOT NULL default '',
  sys_module_position int(5) NOT NULL default '0',
  sys_module_admin int(1) NOT NULL default '0',
  PRIMARY KEY  (sys_module_id),
  KEY sys_module_application (sys_module_application),
  KEY sys_module_visible (sys_module_visible),
  KEY sys_module_key (sys_module_key),
  KEY sys_module_parent (sys_module_parent)
);";

$SQL[] = "CREATE TABLE core_sys_lang (
  lang_id mediumint(4) unsigned NOT NULL auto_increment,
  lang_short varchar(10) NOT NULL default '',
  lang_title varchar(60) NOT NULL default '',
  lang_currency_name varchar(4) NOT NULL default '',
  lang_currency_symbol char(2) NOT NULL default '',
  lang_decimal char(2) NOT NULL default '',
  lang_comma char(2) NOT NULL default '',
  lang_default tinyint(1) unsigned NOT NULL default '0',
  lang_isrtl tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (lang_id),
  KEY lang_short (lang_short)
);";

$SQL[] = "CREATE TABLE core_sys_lang_words (
  word_id int(10) unsigned NOT NULL auto_increment,
  lang_id mediumint(4) unsigned NOT NULL,
  word_app varchar(255) NOT NULL,
  word_pack varchar(255) NOT NULL,
  word_key varchar(64) NOT NULL,
  word_default text NOT NULL,
  word_custom text,
  word_default_version varchar(10) NOT NULL default '1',
  word_custom_version varchar(10) default NULL,
  word_js tinyint(1) unsigned NOT NULL,
  PRIMARY KEY  (word_id),
  KEY word_js (word_js),
  KEY word_find (lang_id, word_app(32), word_pack(100))
);";

$SQL[] = "CREATE TABLE core_sys_login (
  sys_login_id int(8) NOT NULL default '0',
  sys_login_skin int(5) default NULL,
  sys_login_language varchar(32) default NULL,
  sys_login_last_visit int(10) default '0',
  sys_cookie mediumtext,
  PRIMARY KEY  (sys_login_id)
);";

if ( ipsRegistry::DB()->checkFulltextSupport() )
{
	$SQL[] = "ALTER TABLE search_index ADD FULLTEXT KEY content (content,content_title);";
	$SQL[] = "ALTER TABLE message_posts ADD FULLTEXT KEY msg_post (msg_post);";
	$SQL[] = "ALTER TABLE message_topics ADD FULLTEXT KEY mt_title (mt_title);";
}

?>