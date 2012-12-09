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

$SQL[] = "TRUNCATE TABLE ibf_search_results;";

$SQL[] ="ALTER TABLE ibf_conf_settings_titles ADD conf_title_module	 varchar(200) NOT NULL default '';";
$SQL[] ="ALTER TABLE ibf_conf_settings_titles change conf_title_desc conf_title_desc TEXT NULL;";

$SQL[] ="ALTER TABLE ibf_conf_settings change conf_value conf_value text NULL;";
$SQL[] ="ALTER TABLE ibf_conf_settings change conf_default conf_default text NULL;";
$SQL[] ="ALTER TABLE ibf_conf_settings change conf_extra conf_extra text NULL;";
$SQL[] ="ALTER TABLE ibf_conf_settings change conf_evalphp conf_evalphp text NULL;";

$SQL[] = "CREATE TABLE ibf_converge_local (
    converge_api_code VARCHAR(32) NOT NULL default '',
    converge_product_id INT(10) NOT NULL default '0',
    converge_added      INT(10) NOT NULL default '0',
    converge_ip_address VARCHAR(16) NOT NULL default '',
    converge_url        VARCHAR(255) NOT NULL default '',
    converge_active     INT(1) NOT NULL default '0',
    converge_http_user  VARCHAR(255) NOT NULL default '',
    converge_http_pass  VARCHAR(255) NOT NULL default '',
    PRIMARY KEY (converge_api_code ),
    KEY converge_active (converge_active)
);";

$SQL[] = "CREATE TABLE ibf_admin_login_logs (
    admin_id            INT(10) NOT NULL auto_increment,
    admin_ip_address    VARCHAR(16) NOT NULL default '0.0.0.0',
    admin_username      VARCHAR(40) NOT NULL default '',
    admin_time          INT(10) UNSIGNED NOT NULL default '0',
    admin_success       INT(1) UNSIGNED NOT NULL default '0',
    admin_post_details  TEXT NULL,
    PRIMARY KEY (admin_id),
    KEY admin_ip_address (admin_ip_address),
    KEY admin_time (admin_time)
);";

$SQL[] = "CREATE TABLE ibf_profile_friends (
    friends_id          INT(10) NOT NULL auto_increment,
    friends_member_id   INT(10) UNSIGNED NOT NULL default '0',
    friends_friend_id   INT(10) UNSIGNED NOT NULL default '0',
    friends_approved    TINYINT(1) NOT NULL default '0',
    friends_added       INT(10) UNSIGNED NOT NULL default '0',
    PRIMARY KEY( friends_id ),
    KEY my_friends ( friends_member_id, friends_friend_id ),
    KEY friends_member_id ( friends_member_id )
);";

$SQL[] = "CREATE TABLE ibf_profile_comments (
    comment_id              INT(10) NOT NULL auto_increment,
    comment_for_member_id   INT(10) UNSIGNED NOT NULL default '0',
    comment_by_member_id    INT(10) UNSIGNED NOT NULL default '0',
    comment_date            INT(10) UNSIGNED NOT NULL default '0',
    comment_ip_address      VARCHAR(16) NOT NULL default '0',
    comment_content         TEXT NULL,
    comment_approved        TINYINT(1) NOT NULL default '0',
    PRIMARY KEY( comment_id ),
    KEY my_comments( comment_for_member_id )
);";

$SQL[] = "CREATE TABLE ibf_profile_ratings (
    rating_id               INT(10) NOT NULL auto_increment,
    rating_for_member_id    INT(10) NOT NULL default '0',
    rating_by_member_id     INT(10) NOT NULL default '0',
    rating_added            INT(10) NOT NULL default '0',
    rating_ip_address       VARCHAR(16) NOT NULL default '',
    rating_value            INT(2) NOT NULL default '0',
    PRIMARY KEY ( rating_id ),
    KEY rating_for_member_id ( rating_for_member_id ) 
);";


$SQL[] = "CREATE TABLE ibf_profile_portal (
    pp_member_id                    INT(10) NOT NULL default '0',
    pp_profile_update               INT(10) UNSIGNED NOT NULL default '0',
    pp_bio_content                  TEXT NULL,
    pp_last_visitors                TEXT NULL,
    pp_comment_count                INT(10) UNSIGNED NOT NULL default '0',
    pp_rating_hits                  INT(10) UNSIGNED NOT NULL default '0',
    pp_rating_value                 INT(10) UNSIGNED NOT NULL default '0',
    pp_rating_real                  INT(10) UNSIGNED NOT NULL default '0',
    pp_friend_count                 INT(5) UNSIGNED NOT NULL default '0',
    pp_main_photo                   VARCHAR(255) NOT NULL default '',
    pp_main_width                   INT(5) UNSIGNED NOT NULL default '0',
    pp_main_height                  INT(5) UNSIGNED NOT NULL default '0',
    pp_thumb_photo                  VARCHAR(255) NOT NULL default '',
    pp_thumb_width                  INT(5) UNSIGNED NOT NULL default '0',
    pp_thumb_height                 INT(5) UNSIGNED NOT NULL default '0',
    pp_gender                       VARCHAR(10) NOT NULL default '',
    pp_setting_notify_comments      VARCHAR(10) NOT NULL default 'email',
    pp_setting_notify_friend        VARCHAR(10) NOT NULL default 'email',
    pp_setting_moderate_comments    TINYINT(1) NOT NULL default '0',
    pp_setting_moderate_friends     TINYINT(1) NOT NULL default '0',
    pp_setting_count_friends        INT(2) NOT NULL default '0',
    pp_setting_count_comments       INT(2) NOT NULL default '0',
    pp_setting_count_visitors       INT(2) NOT NULL default '0',
    pp_profile_views                INT(10) NOT NULL default '0',
    PRIMARY KEY ( pp_member_id )
);";

$SQL[] = "CREATE TABLE ibf_profile_portal_views (
  views_member_id int(10) NOT NULL default '0'
);";

$SQL[] = "DROP TABLE ibf_calendar_events;";

?>