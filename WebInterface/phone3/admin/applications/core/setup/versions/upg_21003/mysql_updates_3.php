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

$SQL[] = "ALTER TABLE ibf_sessions ADD location_1_type char(10) NOT NULL default '',
  ADD location_1_id int(10) NOT NULL default '0', 
  ADD location_2_type char(10) NOT NULL default '',
  ADD location_2_id int(10) NOT NULL default '0', 
  ADD location_3_type char(10) NOT NULL default '',
  ADD location_3_id int(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_sessions ADD INDEX location1(location_1_type, location_1_id);";
$SQL[] = "ALTER TABLE ibf_sessions ADD INDEX location2(location_2_type, location_2_id);";
$SQL[] = "ALTER TABLE ibf_sessions ADD INDEX location3(location_3_type, location_3_id);";
$SQL[] = "ALTER TABLE ibf_sessions DROP in_forum, DROP in_topic;";



$SQL[] = "ALTER TABLE ibf_forums ADD forum_last_deletion INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_forums ADD forum_allow_rating TINYINT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_groups ADD g_topic_rate_setting SMALLINT(2) NOT NULL default '0';";
$SQL[] = "alter table ibf_validating ADD user_verified tinyint(1) NOT NULL default '0';";
$SQL[] = "alter table ibf_moderators ADD mod_can_set_open_time tinyint(1) NOT NULL default '0';";
$SQL[] = "alter table ibf_moderators ADD mod_can_set_close_time tinyint(1) NOT NULL default '0';";
$SQL[] = "alter table ibf_task_manager ADD task_locked INT(10) NOT NULL default '0';";
$SQL[] = "alter table ibf_groups ADD g_dname_changes INT(3) NOT NULL default '0',
  					   ADD g_dname_date    INT(5) NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_search_results ADD PRIMARY KEY(id);";
$SQL[] = "ALTER TABLE ibf_search_results ADD INDEX search_date(search_date);";

?>