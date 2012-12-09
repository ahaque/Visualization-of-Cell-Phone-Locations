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

$SQL[] = "INSERT INTO ibf_cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('portal', 'a:6:{s:5:\"blogs\";a:5:{s:8:\"pc_title\";s:30:\"Invision Power Community Blogs\";s:7:\"pc_desc\";s:40:\"Shows IPB Blog information on the portal\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:25:\"blogs_show_last_updated_x\";a:2:{i:0;s:25:\"blogs_show_last_updated_x\";i:1;s:30:\"Shows the last X updated blogs\";}}s:6:\"pc_key\";s:5:\"blogs\";}s:8:\"calendar\";a:5:{s:8:\"pc_title\";s:29:\"Invision Power Board Calendar\";s:7:\"pc_desc\";s:46:\"Displays a mini calendar for the current month\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:27:\"calendar_show_current_month\";a:2:{i:0;s:27:\"calendar_show_current_month\";i:1;s:38:\"Shows a calendar for the current month\";}}s:6:\"pc_key\";s:8:\"calendar\";}s:7:\"gallery\";a:5:{s:8:\"pc_title\";s:22:\"Invision Power Gallery\";s:7:\"pc_desc\";s:54:\"Shows Invision Power Gallery information on the portal\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:25:\"gallery_show_random_image\";a:2:{i:0;s:25:\"gallery_show_random_image\";i:1;s:44:\"Shows a random image from a member\'s gallery\";}}s:6:\"pc_key\";s:7:\"gallery\";}s:12:\"online_users\";a:5:{s:8:\"pc_title\";s:33:\"Invision Power Board Online Users\";s:7:\"pc_desc\";s:49:\"Shows the current name and number of online users\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:17:\"online_users_show\";a:2:{i:0;s:17:\"online_users_show\";i:1;s:22:\"Shows the online users\";}}s:6:\"pc_key\";s:12:\"online_users\";}s:4:\"poll\";a:5:{s:8:\"pc_title\";s:25:\"Invision Power Board Poll\";s:7:\"pc_desc\";s:23:\"Shows the selected poll\";s:19:\"pc_settings_keyword\";s:11:\"portal_poll\";s:18:\"pc_exportable_tags\";a:1:{s:14:\"poll_show_poll\";a:2:{i:0;s:14:\"poll_show_poll\";i:1;s:22:\"Shows the request poll\";}}s:6:\"pc_key\";s:4:\"poll\";}s:13:\"recent_topics\";a:5:{s:8:\"pc_title\";s:34:\"Invision Power Board Recent Topics\";s:7:\"pc_desc\";s:47:\"Shows IPB recent topics with topic\'s first post\";s:19:\"pc_settings_keyword\";s:20:\"portal_recent_topics\";s:18:\"pc_exportable_tags\";a:2:{s:20:\"recent_topics_last_x\";a:2:{i:0;s:20:\"recent_topics_last_x\";i:1;s:63:\"Shows the last X topics with full post from the selected forums\";}s:32:\"recent_topics_discussions_last_x\";a:2:{i:0;s:32:\"recent_topics_discussions_last_x\";i:1;s:54:\"Shows the last X topic titles from ALL viewable forums\";}}s:6:\"pc_key\";s:13:\"recent_topics\";}}', '', 1);";

$SQL[] ="INSERT INTO ibf_cal_calendars (cal_id, cal_title, cal_moderate, cal_position, cal_event_limit, cal_bday_limit, cal_rss_export, cal_rss_export_days, cal_rss_export_max, cal_rss_update, cal_rss_update_last, cal_rss_cache, cal_permissions) VALUES (1, 'Community Calendar', 1, 0, 2, 1, 1, 14, 20, 1440, <%time%>, '', 'a:3:{s:9:\"perm_read\";s:1:\"*\";s:9:\"perm_post\";s:3:\"4,3\";s:10:\"perm_nomod\";s:0:\"\";}');";

$SQL[] ="INSERT INTO ibf_login_methods (login_id, login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES (1, 'IPB Internal', 'The standard method of authentication', 'internal', '', '', 'passthrough', '', <%time%>, 0, 1, 1, 1, 0, 1);";
$SQL[] ="INSERT INTO ibf_login_methods (login_id, login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES (2, 'LDAP Authentication', 'LDAP / Active Directory Authentication', 'ldap', '', '', 'passthrough', '', <%time%>, 0, 0, 1, 0, 0, 1);";
$SQL[] ="INSERT INTO ibf_login_methods (login_id, login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES (3, 'External Database', 'Authentication via an external database', 'external', '', '', 'passthrough', '', <%time%>, 0, 0, 1, 0, 0, 1);";

$SQL[] ="INSERT INTO ibf_task_manager VALUES ('', 'Update Topic Views Counter', 'updateviews.php', <%time%>, -1, -1, 3, -1, 'ddce954b5ba1c163bc627ca20725b595', 0, 'Used when topic views are not incremented immediately', 1, 'updateviews', 0, 0);";
$SQL[] ="INSERT INTO ibf_task_manager VALUES ('', 'Subscription Expiration Notification', 'expiresubs.php', <%time%>, -1, -1, 1, 0, '21fa5f52cf9122c6fe940e1c6dac0b5a', 1, 'Sends out an email to all members who have a subscription due to expire', 1, 'expiresubs', 0, 0);";
$SQL[] ="INSERT INTO ibf_task_manager VALUES ('', 'RSS Import Update', 'rssimport.php', <%time%>, -1, -1, -1, 30, '8f17dc0ba334e5f18e762f154365a578', 0, 'Imports any new RSS articles', 1, 'rssimport', 1, 0);";

$SQL[] = "INSERT INTO ibf_task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Expire Paid Subscriptions', 'doexpiresubs.php', 1121212800, -1, -1, 0, 0, 'bb57399ea05eb9240b42d5d5f53575fb', 1, 'Unsubscribes members from their subscribed packages', 1, 'doexpiresubs', 1)";


$SQL[] ="INSERT INTO ibf_components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (1, 'Invision Gallery', 'Invision Power Services', 'http://www.', '1.3.0', 1113309894, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'gallery', 'gallery', 'Complete gallery system for your members', '{ipb.lang[\'gallery\']}', '{ipb.base_url}act=module&module=gallery', 1);";
$SQL[] ="INSERT INTO ibf_components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (2, 'Invision Community Blog', 'Invision Power Services', 'http://www.', '1.1.2', 1113310263, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'blog', 'blog', 'Blogging addition for your members', '{ipb.lang[\'blog\']}', '{ipb.base_url}automodule=blog', -1);";
$SQL[] ="INSERT INTO ibf_components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (3, 'Invision Chat (ParaChat)', 'Invision Power Services', 'http://chat.', '2.1', 1113313895, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'chatpara', 'chatpara', 'Full real-time chat system for your members', '{ipb.lang[\'live_chat\']}', '{ipb.base_url}autocom=chatpara', 2);";
$SQL[] ="INSERT INTO ibf_components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (4, 'Invision Copyright Removal', 'Invision Power Services', 'http://www.', '2.1', 1113314009, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 1, 1, 'copyright', 'copyright', 'Allows the copyright notices to be removed from the board\'s output', '', '', 3);";

$SQL[] ="DELETE FROM ibf_conf_settings WHERE conf_key IN ( 'csite_article_forum'     , 'csite_article_max', 'csite_article_recent_on',
														   'csite_article_recent_max', 'csite_article_len', 'csite_discuss_on',
														   'csite_discuss_max'       , 'csite_discuss_len', 'csite_poll_url',
														   'csite_online_show', 'poll_disable_noreply' );";

# UPDATE KNOWN CONF_TITLE KEYS
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='general' WHERE conf_title_id=1";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='boardoffline' WHERE conf_title_id=15";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='calendar' WHERE conf_title_id=9";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='converge' WHERE conf_title_id=18";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='coppa' WHERE conf_title_id=8";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='cpusaving' WHERE conf_title_id=2";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='date' WHERE conf_title_id=3";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='email' WHERE conf_title_id=12";
$SQL[] = "UPDATE ibf_conf_settings_titles SET conf_title_keyword='searchsetup' WHERE conf_title_id=19";

?>