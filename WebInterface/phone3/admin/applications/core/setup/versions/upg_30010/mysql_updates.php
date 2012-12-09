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

# 3.0.3

$SQL[] = "ALTER TABLE  admin_logs ADD INDEX (  ip_address );";
$SQL[] = "ALTER TABLE  dnames_change ADD INDEX (  dname_ip_address );";
$SQL[] = "ALTER TABLE  error_logs ADD INDEX (  log_ip_address );";
$SQL[] = "ALTER TABLE  members ADD INDEX (  ip_address );";
$SQL[] = "ALTER TABLE  message_posts ADD INDEX (  msg_ip_address );";
$SQL[] = "ALTER TABLE  moderator_logs ADD INDEX (  ip_address );";
$SQL[] = "ALTER TABLE  profile_comments ADD INDEX (  comment_ip_address );";
$SQL[] = "ALTER TABLE  profile_ratings ADD INDEX (  rating_ip_address );";
$SQL[] = "ALTER TABLE  topic_ratings ADD INDEX (  rating_ip_address );";
$SQL[] = "ALTER TABLE  validating ADD INDEX (  ip_address );";
$SQL[] = "ALTER TABLE  voters ADD INDEX (  ip_address );";

$SQL[] = "ALTER TABLE  sessions ADD INDEX (  member_id );";
$SQL[] = "ALTER TABLE  profile_portal ADD INDEX  pp_status (  pp_status ( 128 ) ,  pp_status_update );";
$SQL[] = "ALTER TABLE members ADD live_id VARCHAR( 32 ) NULL;";


