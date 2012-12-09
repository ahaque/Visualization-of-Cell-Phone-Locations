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
|
|   > IPB UPGRADE MODULE:: IPB 2.0.0 PDR1 -> PDR 2
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}


$SQL = array();

$SQL[] = "INSERT INTO ibf_conf_settings_titles (conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES ('IPB Portal', 'These settings enable you to enable or disable IPB Portal and control the options IPB Portal offers.', 20, 0, 'ipbportal');";

$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('ENABLE IPB Portal?', 'If \'yes\', IPB Portal can be accessed via \'index.php?act=home\' or via the special \'index.php\' script (see documentation for more info).', '22', 'yes_no', 'csite_on', '', '1', '', '', 1, 1, '', 0, '', 1);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('IPB Portal Page Title?', 'This will appear inbetween the &lt;title&gt; elements on the page', '22', 'input', 'csite_title', '', 'IPB Portal', '', '', 1, 2, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Forums to export articles from', 'Separate <b>forum ids</b> with a comma for more than one', '22', 'input', 'csite_article_forum', '1,2,3,4,5,6', '', '', '', 1, 3, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Number of Articles to display in the main section', '', '22', 'input', 'csite_article_max', '', '15', '', '', 1, 4, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Enable Recent Articles?', 'This will show a list of recent topic titles on the IPB Portal page', '22', 'yes_no', 'csite_article_recent_on', '', '1', '', '', 1, 5, 'IPB Portal Recent Articles', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Max. no recent articles to show', '', '22', 'input', 'csite_article_recent_max', '', '5', '', '', 1, 6, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Max. length of topic titles', '', '22', 'input', 'csite_article_len', '', '30', '', '', 1, 7, '', 1, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Date format for articles', '<a href=\'http://www.php.net/date\'>Same as PHP\'s date function', '22', 'input', 'csite_article_date', '', 'm-j-y H:i', '', '', 1, 3, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Enable Recent Discussions', '', '22', 'yes_no', 'csite_discuss_on', '', '1', '', '', 1, 9, 'IPB Portal Recent Discussions', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Max. no recent discussions to show', '', '22', 'input', 'csite_discuss_max', '', '10', '', '', 1, 10, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Max. length of topic titles', '', '22', 'input', 'csite_discuss_len', '', '30', '', '', 1, 11, '', 1, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Show User / Guest Info box?', '', '22', 'yes_no', 'csite_pm_show', '', '1', '', '', 1, 12, 'IPB Portal Components', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Show online users?', '', '22', 'yes_no', 'csite_online_show', '', '1', '', '', 1, 13, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Show search box?', '', '22', 'yes_no', 'csite_search_show', '', '1', '', '', 1, 14, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Enable skin selection choice dropdown?', '', '22', 'yes_no', 'csite_skinchange_show', '', '1', '', '', 1, 15, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Enter URL to poll topic for inclusion', 'Leave blank to not show a poll or the poll box', '22', 'input', 'csite_poll_url', '', '', '', '', 1, 17, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Show Site Navigation Menu?', '', '22', 'yes_no', 'csite_nav_show', '', '1', '', '', 1, 18, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Site Navigation Menu Links', 'One per line in this format<br>http://www.apple.com [Apple\'s Website]<br><br>{board_url} will convert into your board', '22', 'textarea', 'csite_nav_contents', '', '{board_url} [Forums]\r\n{board_url}act=Search&do=getactive [Today\'s Active Topics]\r\n{board_url}act=Stats [Today\'s Top 10 Posters]\r\n{board_url}act=Stats&do=leaders [Contact Staff]', '', 'if ( $show == 1)\r\n{\r\n    $value = preg_replace( \"/&(middot|quot|copy|amp)/\", \"&\\\\1\", $value );\r\n}', 1, 19, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Show Affiliates / Favoured Sites box?', '', '22', 'yes_no', 'csite_fav_show', '', '0', '', '', 1, 20, '', 0, '', 0);";
$SQL[] = "INSERT INTO ibf_conf_settings (conf_title, conf_description, conf_group, conf_type, conf_key, conf_value, conf_default, conf_extra, conf_evalphp, conf_protected, conf_position, conf_start_group, conf_end_group, conf_help_key, conf_add_cache) VALUES ('Show Affiliates / Favoured Sites box content', 'Raw HTML enabled', '22', 'textarea', 'csite_fav_contents', '', '', '', 'if ( $show == 1)\r\n{\r\n $value = preg_replace( \"/&(middot|quot|copy|amp)/\", \"&\\\\1\", $value );\r\n}', 1, 21, '', 1, '', 0);";

	
	
?>