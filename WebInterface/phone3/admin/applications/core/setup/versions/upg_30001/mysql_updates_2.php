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


# ALTER TOPICS TABLES
$SQL[] = "ALTER TABLE topics ADD title_seo VARCHAR(250) NOT NULL default '';";
$SQL[] = "ALTER TABLE topics ADD seo_last_name VARCHAR(255) NOT NULL default '';";
$SQL[] = "ALTER TABLE topics ADD seo_first_name VARCHAR(255) NOT NULL default '';";
$SQL[] = "ALTER TABLE topics CHANGE description description varchar(250) default NULL;";

$SQL[] = "ALTER TABLE voters CHANGE member_id member_id INT(11) NOT NULL DEFAULT '0' ;";
$SQL[] = "ALTER TABLE voters ADD member_choices TEXT;";

$SQL[] = "ALTER TABLE polls ADD poll_view_voters	INT(1) NOT NULL default '0';";

$SQL[] = "ALTER TABLE tracker ADD KEY tm_id (topic_id, member_id);";
$SQL[] = "ALTER TABLE forum_tracker ADD KEY member_id (member_id), ADD KEY fm_id (member_id, forum_id );";

?>