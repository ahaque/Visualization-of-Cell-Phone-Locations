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

$SQL[] = "DELETE FROM ibf_admin_permission_keys WHERE perm_main='tools' and perm_child='login';";
$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key='max_poll_questions' AND conf_group='';";
$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key IN( 'poll_disable_noreply', 'chat04_who_save', 'chat04_whodat_server_addr' );";
$SQL[] = "UPDATE ibf_conf_settings SET conf_group=12 WHERE conf_key='smtp_pass';";
$SQL[] = "UPDATE ibf_conf_settings SET conf_group=21 WHERE conf_key='chat04_default_lang';";
$SQL[] = "UPDATE ibf_conf_settings SET conf_group=5, conf_position=43 WHERE conf_key='max_h_flash';";
$SQL[] = "DELETE FROM ibf_components WHERE com_filename='registration';";
$SQL[] = "UPDATE ibf_components SET com_section='chatpara', com_filename='chatpara' where com_filename='chat';";
$SQL[] = "ALTER TABLE ibf_member_extra CHANGE icq_number icq_number varchar(40) NOT NULL default '';";

?>