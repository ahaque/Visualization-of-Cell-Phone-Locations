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

$SQL[]="ALTER TABLE ibf_members ADD members_profile_views INT(10) UNSIGNED NOT NULL default '0';";
$SQL[]="ALTER TABLE ibf_members ADD member_login_key_expire INT(10) NOT NULL default '0' AFTER member_login_key;";

$SQL[]="alter table ibf_members ADD members_l_display_name VARCHAR(255) NOT NULL default '0';";
$SQL[]="alter table ibf_members ADD members_l_username   VARCHAR(255) NOT NULL default '0';";
$SQL[]="alter table ibf_members DROP INDEX name;";
$SQL[]="alter table ibf_members DROP INDEX members_display_name;";
$SQL[]="alter table ibf_member_extra change interests interests text NULL;";
$SQL[]="alter table ibf_members change ignored_users ignored_users text NULL;";
$SQL[]="alter table ibf_members change members_markers members_markers text NULL;";

$SQL[]="ALTER TABLE ibf_members ADD INDEX members_l_display_name (members_l_display_name), ADD INDEX members_l_username (members_l_username);";

$SQL[]="ALTER TABLE ibf_members ADD failed_logins TEXT NULL;";
$SQL[]="ALTER TABLE ibf_members ADD failed_login_count SMALLINT( 3 ) DEFAULT '0' NOT NULL;";

$SQL[]="ALTER TABLE ibf_members_partial ADD partial_email_ok INT(1) NOT NULL default '0';";

$SQL[] ="ALTER TABLE ibf_attachments ADD attach_rel_id           INT(10) NOT NULL default '0',
                            ADD attach_rel_module       VARCHAR(100) NOT NULL default '0';";

$SQL[] ="ALTER TABLE ibf_attachments add attach_img_width        INT(5) NOT NULL default '0',
                            add attach_img_height       INT(5) NOT NULL default '0';";
                                                        
$SQL[] ="ALTER TABLE ibf_attachments DROP INDEX attach_mid_size,
                            DROP INDEX attach_pid,
                            DROP INDEX attach_msg,
                            ADD INDEX attach_pid (attach_rel_id),
                            ADD INDEX attach_mid_size (attach_member_id,attach_rel_module, attach_filesize),
                            ADD INDEX attach_where (attach_rel_module, attach_rel_id);";
?>