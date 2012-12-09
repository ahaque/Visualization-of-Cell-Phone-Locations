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


$SQL[] = "ALTER TABLE groups ADD g_mod_post_unit		INT(5) UNSIGNED NOT NULL default '0',
ADD g_ppd_limit			INT(5) UNSIGNED NOT NULL default '0',
ADD g_ppd_unit			INT(5) UNSIGNED NOT NULL default '0',
ADD g_displayname_unit	INT(5) UNSIGNED NOT NULL default '0',
ADD g_sig_unit			INT(5) UNSIGNED NOT NULL default '0';";

$SQL[] = "ALTER TABLE members ADD members_day_posts VARCHAR(32) NOT NULL default '0,0';";

$SQL[] = "ALTER TABLE members ADD KEY members_bitoptions (members_bitoptions);";

$SQL[] = "ALTER TABLE banfilters ADD ban_nocache INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE banfilters ADD INDEX ban_content (ban_content(200)), ADD KEY ban_nocache (ban_nocache);";

$SQL[] = "UPDATE groups SET g_promotion='-1&-1' WHERE g_access_cp=1;";

$SQL[] = "ALTER TABLE groups ADD g_pm_flood_mins INT(5) UNSIGNED NOT NULL default '0';";

$SQL[] = "ALTER TABLE message_topics ADD INDEX mt_date (mt_date); ";
$SQL[] = "ALTER TABLE faq ADD app VARCHAR(32) NOT NULL default 'core';";
$SQL[] = "ALTER TABLE core_sys_lang CHANGE lang_short lang_short VARCHAR( 18 ) NOT NULL;";

?>