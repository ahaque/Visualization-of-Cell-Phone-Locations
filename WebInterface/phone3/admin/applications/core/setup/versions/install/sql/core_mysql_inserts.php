<?php

$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('bbcode', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('moderators', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('multimod', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('banfilters', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('attachtypes', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('emoticons', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('forum_cache', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('badwords', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('systemvars', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('adminnotes', '', '', 0);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('ranks', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('group_cache', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('stats', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('profilefields', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('settings','', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('languages', '', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('birthdays', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('calendar', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('calendars', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('chatting', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('components', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('rss_export', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('rss_calendar', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('announcements', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('hooks', 'a:0:{}', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('portal', 'a:6:{s:5:\"blogs\";a:5:{s:8:\"pc_title\";s:30:\"Invision Power Community Blogs\";s:7:\"pc_desc\";s:40:\"Shows IPB Blog information on the portal\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:25:\"blogs_show_last_updated_x\";a:2:{i:0;s:25:\"blogs_show_last_updated_x\";i:1;s:30:\"Shows the last X updated blogs\";}}s:6:\"pc_key\";s:5:\"blogs\";}s:8:\"calendar\";a:5:{s:8:\"pc_title\";s:29:\"Invision Power Board Calendar\";s:7:\"pc_desc\";s:46:\"Displays a mini calendar for the current month\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:27:\"calendar_show_current_month\";a:2:{i:0;s:27:\"calendar_show_current_month\";i:1;s:38:\"Shows a calendar for the current month\";}}s:6:\"pc_key\";s:8:\"calendar\";}s:7:\"gallery\";a:5:{s:8:\"pc_title\";s:22:\"Invision Power Gallery\";s:7:\"pc_desc\";s:54:\"Shows Invision Power Gallery information on the portal\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:25:\"gallery_show_random_image\";a:2:{i:0;s:25:\"gallery_show_random_image\";i:1;s:44:\"Shows a random image from a member\'s gallery\";}}s:6:\"pc_key\";s:7:\"gallery\";}s:12:\"online_users\";a:5:{s:8:\"pc_title\";s:33:\"Invision Power Board Online Users\";s:7:\"pc_desc\";s:49:\"Shows the current name and number of online users\";s:19:\"pc_settings_keyword\";s:0:\"\";s:18:\"pc_exportable_tags\";a:1:{s:17:\"online_users_show\";a:2:{i:0;s:17:\"online_users_show\";i:1;s:22:\"Shows the online users\";}}s:6:\"pc_key\";s:12:\"online_users\";}s:4:\"poll\";a:5:{s:8:\"pc_title\";s:25:\"Invision Power Board Poll\";s:7:\"pc_desc\";s:23:\"Shows the selected poll\";s:19:\"pc_settings_keyword\";s:11:\"portal_poll\";s:18:\"pc_exportable_tags\";a:1:{s:14:\"poll_show_poll\";a:2:{i:0;s:14:\"poll_show_poll\";i:1;s:22:\"Shows the request poll\";}}s:6:\"pc_key\";s:4:\"poll\";}s:13:\"recent_topics\";a:5:{s:8:\"pc_title\";s:34:\"Invision Power Board Recent Topics\";s:7:\"pc_desc\";s:47:\"Shows IPB recent topics with topic\'s first post\";s:19:\"pc_settings_keyword\";s:20:\"portal_recent_topics\";s:18:\"pc_exportable_tags\";a:2:{s:20:\"recent_topics_last_x\";a:2:{i:0;s:20:\"recent_topics_last_x\";i:1;s:63:\"Shows the last X topics with full post from the selected forums\";}s:32:\"recent_topics_discussions_last_x\";a:2:{i:0;s:32:\"recent_topics_discussions_last_x\";i:1;s:54:\"Shows the last X topic titles from ALL viewable forums\";}}s:6:\"pc_key\";s:13:\"recent_topics\";}}', '', 1);";


$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (1, ':mellow:', 'mellow.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (2, ':huh:', 'huh.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (3, '^_^', 'happy.gif', 0, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (4, ':o', 'ohmy.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (5, ';)', 'wink.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (6, ':P', 'tongue.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (7, ':D', 'biggrin.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (8, ':lol:', 'laugh.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (9, 'B)', 'cool.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (10, ':rolleyes:', 'rolleyes.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (11, '-_-', 'sleep.gif', 0, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (12, '&lt;_&lt;', 'dry.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (13, ':)', 'smile.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (14, ':wub:', 'wub.gif', 0, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (15, ':angry:', 'angry.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (16, ':(', 'sad.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (17, ':unsure:', 'unsure.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (18, ':wacko:', 'wacko.gif', 0, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (19, ':blink:', 'blink.gif', 1, 'default');";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set) VALUES (20, ':ph34r:', 'ph34r.gif', 0, 'default');";

# Profile fields stuff
$INSERT[] = "INSERT INTO pfields_data (pf_id, pf_title, pf_desc, pf_content, pf_type, pf_not_null, pf_member_hide, pf_max_input, pf_member_edit, pf_position, pf_show_on_reg, pf_input_format, pf_admin_only, pf_topic_format, pf_group_id, pf_icon, pf_key) VALUES
(1, 'AIM', '', '', 'input', 0, 0, 0, 0, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_aim.gif', 'aim'),
(2, 'MSN', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_msn.gif', 'msn'),
(3, 'Website URL', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_website.gif', 'website'),
(4, 'ICQ', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_icq.gif', 'icq'),
(5, 'Gender', '', 'u=Not Telling|m=Male|f=Female', 'drop', 0, 0, 0, 1, 0, 0, '', 0, '', 2, '', 'gender'),
(6, 'Location', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '<span class=\'ft\'>{title}</span><span class=\'fc\'>{content}</span>', 2, '', 'location'),
(7, 'Interests', '', '', 'textarea', 0, 0, 0, 1, 0, 0, '', 0, '', 2, '', 'interests'),
(8, 'Yahoo', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_yahoo.gif', 'yahoo'),
(9, 'Jabber', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_jabber.gif', 'jabber'),
(10, 'Skype', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_skype.gif', 'skype')";

$INSERT[] = "INSERT INTO pfields_groups (pf_group_id, pf_group_name, pf_group_key) VALUES
(1, 'Contact Methods', 'contact'),
(2, 'Profile Information', 'profile_info')";

$INSERT[] = "INSERT INTO core_sys_lang VALUES(1, 'en_US', 'English (USA)', 'USD', '$', '.', ',', 1, 0)";

$INSERT[] = "INSERT INTO titles (id, posts, title, pips) VALUES (1, 0, 'Newbie', '1');";
$INSERT[] = "INSERT INTO titles (id, posts, title, pips) VALUES (2, 10, 'Member', '2');";
$INSERT[] = "INSERT INTO titles (id, posts, title, pips) VALUES (4, 30, 'Advanced Member', '3');";

$INSERT[] ="INSERT INTO permission_index VALUES(1, 'members', 'profile_view', 1, '*', '', '', '', '', '', '', 0, 0, NULL)";
$INSERT[] ="INSERT INTO permission_index VALUES(2, 'core', 'help', 1, '*', '', '', '', '', '', '', 0, 0, NULL)";

/* Report center stuff */

$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Simple Plugin Example', 'Plugin that does not require any programming, but does need to be configured.', 'Invision Power Services, Inc', 'http://', 'v1.0', 'default', ',3,4,6,', ',4,6,', 'a:5:{s:14:"required_input";a:1:{s:8:"video_id";s:13:"[^A-Za-z0-9_]";}s:10:"string_url";s:41:"http://www.youtube.com/watch?v={video_id}";s:12:"string_title";s:25:"#PAGE_TITLE# ({video_id})";s:13:"section_title";s:7:"YouTube";s:11:"section_url";s:22:"http://www.youtube.com";}', 1);
EOF;
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Forum Plugin', 'This is the plugin used for reporting posts on the forum.', 'Invision Power Services, Inc', 'http://', 'v1.0', 'post', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:15:"report_supermod";i:1;}', 1);
EOF;
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Private Messages Plugin', 'This plugin allows private messages to be reported', 'Invision Power Services, Inc', 'http://', 'v1.0', 'messages', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:18:"plugi_messages_add";s:5:"4";}', 1);
EOF;
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Member Profiles', 'Allows you to report member profiles', 'Invision Power Services, Inc', 'http://', 'v1.0', 'profiles', ',1,2,3,4,6,', ',4,6,', 'N;', 1);
EOF;

$INSERT[] = "INSERT INTO rc_status VALUES(1, 'New Report', 1, 5, 1, 0, 1, 1);";
$INSERT[] = "INSERT INTO rc_status VALUES(2, 'Under Review', 1, 5, 0, 0, 1, 2);";
$INSERT[] = "INSERT INTO rc_status VALUES(3, 'Complete', 0, 0, 0, 1, 0, 3);";

$INSERT[] = <<<EOF
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

$INSERT[] = "INSERT INTO reputation_levels VALUES(1, -20, 'Bad', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(2, -10, 'Poor', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(3, 0, 'Neutral', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(4, 10, 'Good', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(5, 20, 'Excellent', '');";

