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


$SQL[] = "alter table ibf_topics add index last_post_sorting(last_post,forum_id);";
$SQL[] = "alter table ibf_profile_comments drop index my_comments;";
$SQL[] = "alter table ibf_profile_comments add index my_comments (comment_for_member_id,comment_date);";
$SQL[] = "ALTER TABLE ibf_sessions ADD INDEX ( running_time );";

$SQL[] = "ALTER TABLE ibf_conf_settings DROP conf_help_key;";
