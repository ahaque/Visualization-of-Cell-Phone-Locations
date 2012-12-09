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

$SQL[] ="ALTER TABLE ibf_groups DROP g_calendar_post;";

$SQL[] ="ALTER TABLE ibf_validating ADD prev_email VARCHAR(150) NOT NULL DEFAULT '0';";

$SQL[] ="ALTER TABLE ibf_forums CHANGE position position INT(5)  UNSIGNED default '0';";
$SQL[] ="ALTER TABLE ibf_forums ADD newest_title VARCHAR( 128 ) NULL , ADD newest_id INT( 10 ) NOT NULL default '0' ;";
$SQL[] ="ALTER TABLE ibf_forums ADD password_override VARCHAR( 255 ) AFTER password;";


$SQL[] ="ALTER TABLE ibf_search_results CHANGE topic_id topic_id text NULL;";
$SQL[] ="ALTER TABLE ibf_search_results CHANGE post_id post_id text NULL;";
$SQL[] ="ALTER TABLE ibf_search_results CHANGE query_cache query_cache text NULL;";

$SQL[] ="ALTER TABLE ibf_cache_store change cs_value cs_value mediumtext NULL;";

$SQL[] ="ALTER TABLE ibf_login_methods ADD login_user_id VARCHAR(255) NOT NULL default 'username';";
$SQL[] ="ALTER TABLE ibf_login_methods ADD login_logout_url  VARCHAR(255) NOT NULL default '';";
$SQL[] ="ALTER TABLE ibf_login_methods ADD login_login_url VARCHAR(255) NOT NULL default '';";

$SQL[] ="ALTER TABLE ibf_sessions CHANGE id id VARCHAR(60) DEFAULT '0' NOT NULL;";

$SQL[] ="ALTER TABLE ibf_voters ADD INDEX ( tid );";

$SQL[] ="ALTER TABLE ibf_faq ADD position SMALLINT( 3 ) DEFAULT '0' NOT NULL;";

$SQL[] ="ALTER TABLE ibf_polls ADD poll_only TINYINT( 1 ) DEFAULT '0' NOT NULL;";

# UPDATES
$SQL[] ="UPDATE ibf_subscription_methods SET submethod_desc='All major credit cards accepted. See <a href=\"https://www.paypal.com\" target=\"_blank\">PayPal</a> for more information.' WHERE LOWER(submethod_name)='paypal';";

$SQL[] ="UPDATE ibf_groups SET g_photo_max_vars='100:150:150';";

$SQL[] ="UPDATE ibf_attachments SET attach_rel_id=attach_pid, attach_rel_module='post' where attach_pid > 0;";
$SQL[] ="UPDATE ibf_attachments SET attach_rel_id=attach_msg, attach_rel_module='msg' where attach_msg > 0;";

$SQL[] ="UPDATE ibf_login_methods SET login_settings = '1', login_installed = '1' WHERE login_folder_name='ldap';";
$SQL[] ="UPDATE ibf_login_methods SET login_settings = '1', login_installed = '1' WHERE login_folder_name='external';";

$SQL[] ="INSERT INTO ibf_login_methods (login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES ('IP.Converge', 'Internal Use Only', 'ipconverge', '', '', 'passthrough', '', 0, 0, 0, 1, 1, 0, 1);";
$SQL[] ="UPDATE ibf_forums SET permission_showtopic=1 WHERE parent_id='-1';";

# MEMBERS
$SQL[] = "UPDATE ibf_members SET members_l_display_name=LOWER(members_display_name);";
$SQL[] = "UPDATE ibf_members SET members_l_username=LOWER(name);";

?>