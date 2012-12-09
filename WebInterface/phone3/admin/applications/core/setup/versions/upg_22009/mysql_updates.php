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

$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key='converge_login_method';";

$SQL[] = "ALTER TABLE ibf_member_extra CHANGE avatar_location avatar_location varchar(255) NOT NULL default '';";

$SQL[] = "ALTER TABLE ibf_skin_sets CHANGE set_css set_css mediumtext NULL,
	CHANGE set_cache_macro set_cache_macro mediumtext NULL,
	CHANGE set_wrapper set_wrapper mediumtext NULL,
	CHANGE set_cache_css set_cache_css mediumtext NULL,
	CHANGE set_cache_wrapper set_cache_wrapper mediumtext NULL;";
	
	
$SQL[] = "ALTER TABLE ibf_forums CHANGE rules_text rules_text TEXT NULL;";


$SQL[] = "ALTER TABLE ibf_cal_events ADD event_all_day TINYINT( 1 ) NOT NULL DEFAULT '0';";

?>