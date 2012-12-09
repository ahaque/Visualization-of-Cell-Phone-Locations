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

$SQL[] = "UPDATE ibf_conf_settings SET conf_value='self' WHERE conf_key='chat_display'";
$SQL[] = "UPDATE ibf_conf_settings SET conf_value='self' WHERE conf_key='chat04_display'";
$SQL[] = "UPDATE ibf_conf_settings SET conf_title='Number of times per day moderators can warn a single member' WHERE conf_key='warn_mod_day'";

$SQL[] = "DELETE FROM ibf_components WHERE com_filename='chatsigma'";
$SQL[] = "INSERT INTO ibf_components (com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES ('SigmaChat', 'Invision Power Services', 'http://www.', '2.1', 1113313895, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'chatsigma', 'chatsigma', 'Full real-time chat system for your members', '{ipb.lang[\'live_chat\']}', '{ipb.base_url}autocom=chatsigma', 5);";

?>