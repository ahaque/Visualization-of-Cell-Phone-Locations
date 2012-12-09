<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/
$TABLE[] = "CREATE TABLE cal_calendars (
  cal_id int(10) unsigned NOT NULL auto_increment,
  cal_title varchar(255) NOT NULL default '0',
  cal_moderate tinyint(1) NOT NULL default '0',
  cal_position int(3) NOT NULL default '0',
  cal_event_limit int(2) unsigned NOT NULL default '0',
  cal_bday_limit int(2) unsigned NOT NULL default '0',
  cal_rss_export tinyint(1) NOT NULL default '0',
  cal_rss_export_days int(3) unsigned NOT NULL default '0',
  cal_rss_export_max tinyint(1) NOT NULL default '0',
  cal_rss_update int(3) unsigned NOT NULL default '0',
  cal_rss_update_last int(10) unsigned NOT NULL default '0',
  cal_rss_cache mediumtext,
  cal_permissions mediumtext,
  PRIMARY KEY  (cal_id)
);";
$TABLE[] = "CREATE TABLE cal_events (
  event_id int(10) unsigned NOT NULL auto_increment,
  event_calendar_id int(10) unsigned NOT NULL default '0',
  event_member_id mediumint(8) unsigned NOT NULL default '0',
  event_content mediumtext,
  event_title varchar(255) NOT NULL default '',
  event_smilies tinyint(1) NOT NULL default '0',
  event_perms text,
  event_private tinyint(1) NOT NULL default '0',
  event_approved tinyint(1) NOT NULL default '0',
  event_unixstamp int(10) unsigned NOT NULL default '0',
  event_recurring int(2) unsigned NOT NULL default '0',
  event_tz int(4) NOT NULL default '0',
  event_timeset varchar(6) NOT NULL default '0',
  event_unix_from int(10) unsigned NOT NULL default '0',
  event_unix_to int(10) unsigned NOT NULL default '0',
  event_all_day tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (event_id),
  KEY daterange (event_calendar_id,event_approved,event_unix_from,event_unix_to),
  KEY approved (event_calendar_id,event_approved)
);";
?>