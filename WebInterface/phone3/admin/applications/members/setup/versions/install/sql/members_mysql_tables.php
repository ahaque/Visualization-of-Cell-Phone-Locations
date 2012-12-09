<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/
$TABLE[] = "CREATE TABLE message_posts (
  msg_id int(10) NOT NULL auto_increment,
  msg_topic_id int(10) NOT NULL default '0',
  msg_date int(10) default NULL,
  msg_post text,
  msg_post_key varchar(32) NOT NULL default '0',
  msg_author_id mediumint(8) NOT NULL default '0',
  msg_ip_address varchar(16) NOT NULL default '0',
  msg_is_first_post int(1) NOT NULL default '0',
  PRIMARY KEY  (msg_id),
  KEY msg_topic_id (msg_topic_id),
  KEY msg_date (msg_date),
  KEY msg_ip_address (msg_ip_address)
);";
$TABLE[] = "CREATE TABLE message_topic_user_map (
  map_id		int(10) NOT NULL auto_increment,
  map_user_id int(10) NOT NULL default '0',
  map_topic_id int(10) NOT NULL default '0',
  map_folder_id varchar(32) NOT NULL default '',
  map_read_time int(10) NOT NULL default '0',
  map_user_active int(1) NOT NULL default '0',
  map_user_banned int(1) NOT NULL default '0',
  map_has_unread int(1) NOT NULL default '0',
  map_is_system int(1) NOT NULL default '0',
  map_is_starter int(1) NOT NULL default '0',
  map_left_time int(10) NOT NULL default '0',
  map_ignore_notification int(1) NOT NULL default '0',
  PRIMARY KEY map_id (map_id),
  UNIQUE KEY map_main (map_user_id,map_topic_id),
  KEY map_user (map_user_id,map_folder_id)
);";
$TABLE[] = "CREATE TABLE message_topics (
  mt_id int(10) NOT NULL auto_increment,
  mt_date int(10) NOT NULL default '0',
  mt_title varchar(255) NOT NULL default '',
  mt_hasattach smallint(5) NOT NULL default '0',
  mt_starter_id int(10) NOT NULL default '0',
  mt_start_time int(10) NOT NULL default '0',
  mt_last_post_time int(10) NOT NULL default '0',
  mt_invited_members text,
  mt_to_count int(3) NOT NULL default '0',
  mt_to_member_id int(10) NOT NULL default '0',
  mt_replies int(10) NOT NULL default '0',
  mt_last_msg_id int(10) NOT NULL default '0',
  mt_first_msg_id int(10) NOT NULL default '0',
  mt_is_draft int(1) NOT NULL default '0',
  mt_is_deleted int(1) NOT NULL default '0',
  mt_is_system int(1) NOT NULL default '0',
  PRIMARY KEY  (mt_id),
  KEY mt_date (mt_date),
  KEY mt_starter_id (mt_starter_id)
);";
?>