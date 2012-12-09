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


# INSERTS AND UPDATES
$SQL[] = <<<EOF
INSERT INTO core_sys_lang VALUES (1, 'en_US', 'English (USA)', 'USD', '$', '.', ',', 1, 0);
EOF;

$SQL[] = <<<EOF
INSERT INTO cache_store ( cs_key , cs_value , cs_extra , cs_array , cs_updated ) VALUES ( 'rss_output_cache', '' , '', '1', '0' );
EOF;

$SQL[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Simple Plugin Example', 'Plugin that does not require any programming, but does need to be configured.', 'Invision Power Services, Inc', 'http://', 'v1.0', 'default', ',1,2,3,4,6,', ',4,6,', 'a:5:{s:14:"required_input";a:1:{s:8:"video_id";s:13:"[^A-Za-z0-9_]";}s:10:"string_url";s:41:"http://www.youtube.com/watch?v={video_id}";s:12:"string_title";s:25:"#PAGE_TITLE# ({video_id})";s:13:"section_title";s:7:"YouTube";s:11:"section_url";s:22:"http://www.youtube.com";}', 1);
EOF;
$SQL[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Forum Plugin', 'This is the plugin used for reporting posts on the forum.', 'Invision Power Services, Inc', 'http://', 'v1.0', 'post', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:15:"report_supermod";i:1;}', 1);
EOF;
$SQL[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Private Messages Plugin', 'This plugin allows private messages to be reported', 'Invision Power Services, Inc', 'http://', 'v1.0', 'messages', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:18:"plugi_messages_add";s:5:"4,6,8";}', 1);
EOF;
$SQL[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Member Profiles', 'Allows you to report member profiles', 'Invision Power Services, Inc', 'http://', 'v1.0', 'profiles', ',1,2,3,4,6,', ',4,6,', 'N;', 1);
EOF;


$SQL[] = <<<EOF
INSERT INTO rc_status VALUES (1, 'New Report', 1, 5, 1, 0, 1, 1);
EOF;
$SQL[] = <<<EOF
INSERT INTO rc_status VALUES (2, 'Under Review', 1, 5, 0, 0, 1, 2);
EOF;
$SQL[] = <<<EOF
INSERT INTO rc_status VALUES (3, 'Complete', 0, 0, 0, 1, 0, 3);
EOF;

$SQL[] = <<<EOF
INSERT INTO rc_status_sev (id, status, points, img, is_png, width, height) VALUES
(1, 1, 1, 'style_extra/report_icons/flag_gray.png', 1, 16, 16),
(2, 1, 2, 'style_extra/report_icons/flag_blue.png', 1, 16, 16),
(3, 1, 4, 'style_extra/report_icons/flag_green.png', 1, 16, 16),
(4, 1, 7, 'style_extra/report_icons/flag_orange.png', 1, 16, 16),
(5, 1, 12, 'style_extra/report_icons/flag_red.png', 1, 16, 16),
(6, 2, 1, 'style_extra/report_icons/flag_gray_review.png', 1, 16, 16),
(7, 3, 0, 'style_extra/report_icons/completed.png', 1, 16, 16),
(8, 2, 2, 'style_extra/report_icons/flag_blue_review.png', 1, 16, 16),
(9, 2, 4, 'style_extra/report_icons/flag_green_review.png', 1, 16, 16),
(10, 2, 7, 'style_extra/report_icons/flag_orange_review.png', 1, 16, 16),
(11, 2, 12, 'style_extra/report_icons/flag_red_review.png', 1, 16, 16);
EOF;

$SQL[] = <<<EOF
INSERT INTO pfields_groups (pf_group_id, pf_group_name, pf_group_key) VALUES
(1, 'Contact Methods', 'contact'),
(2, 'Profile Information', 'profile_info'),
(3, 'Previous Fields', 'previous' );
EOF;

$SQL[] = <<<EOF
INSERT INTO pfields_data (pf_id, pf_title, pf_desc, pf_content, pf_type, pf_not_null, pf_member_hide, pf_max_input, pf_member_edit, pf_position, pf_show_on_reg, pf_input_format, pf_admin_only, pf_topic_format, pf_group_id, pf_icon, pf_key) VALUES
(NULL, 'AIM', '', '', 'input', 0, 0, 0, 0, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_aim.gif', 'aim'),
(NULL, 'MSN', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_msn.gif', 'msn'),
(NULL, 'Website URL', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_website.gif', 'website'),
(NULL, 'ICQ', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_icq.gif', 'icq'),
(NULL, 'Gender', '', 'm=Male|f=Female|u=Not Telling', 'drop', 0, 0, 0, 1, 0, 0, '', 0, '<dt>{title}:</dt><dd>{content}</dd>', 2, '', 'gender'),
(NULL, 'Location', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '<dt>{title}:</dt><dd>{content}</dd>', 2, '', 'location'),
(NULL, 'Interests', '', '', 'textarea', 0, 0, 0, 1, 0, 0, '', 0, '<dt>{title}:</dt><dd>{content}</dd>', 2, '', 'interests'),
(NULL, 'Yahoo', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_yahoo.gif', 'yahoo'),
(NULL, 'Jabber', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_jabber.gif', 'jabber'),
(NULL, 'Skype', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_skype.gif', 'skype');
EOF;


$SQL[] = "UPDATE attachments_type set atype_img=replace(atype_img,'folder_mime_types','style_extra/mime_types');";

$SQL[] = "UPDATE login_methods SET login_alt_acp_html = '<label for=''openid''>Open ID</label> <input type=''text'' size=''20'' id=''openid'' name=''openid_url'' value=''http://''>' WHERE login_folder_name='openid';";

# Enable internal method on upgrade, but put it after any others
$SQL[] = "UPDATE login_methods SET login_enabled=1,login_order=7 WHERE login_folder_name='internal';";

$SQL[] = "TRUNCATE TABLE skin_cache;";
$SQL[] = "TRUNCATE TABLE skin_templates_cache;";
$SQL[] = "TRUNCATE TABLE skin_url_mapping;";

$SQL[] = "DELETE FROM cache_store WHERE cs_key='skin_id_cache';";
$SQL[] = "DELETE FROM cache_store WHERE cs_key='forum_cache';";

$SQL[] = "INSERT INTO reputation_levels VALUES(1, -20, 'Bad', '');";
$SQL[] = "INSERT INTO reputation_levels VALUES(2, -10, 'Poor', '');";
$SQL[] = "INSERT INTO reputation_levels VALUES(3, 0, 'Neutral', '');";
$SQL[] = "INSERT INTO reputation_levels VALUES(4, 10, 'Good', '');";
$SQL[] = "INSERT INTO reputation_levels VALUES(5, 20, 'Excellent', '');";

?>