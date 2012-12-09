<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.0.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.
|   ========================================
|   Web: http://www.
|   Email: matt@
|   Licence Info: http://www./?license
+---------------------------------------------------------------------------
*/

# 3.0.2

/* Field was incorrectly added during beta ..er.. something */
if ( ipsRegistry::DB()->checkForField( 'fb_status', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP fb_status;";
}

/* Added during 2.3.6 upgrade */
if ( ! ipsRegistry::DB()->checkForField( 'app_hide_tab', 'core_applications' ) )
{
	$SQL[] = "ALTER TABLE core_applications ADD app_hide_tab TINYINT(1) NOT NULL DEFAULT '0';";
}

# Bug 17345 Removes unrequired task files
$SQL[] = "DELETE FROM task_manager WHERE task_key IN ('doexpiresubs', 'expiresubs') AND task_application != 'subscriptions';";

$SQL[] = "CREATE TABLE spam_service_log (
  id int(10) unsigned NOT NULL auto_increment,
  log_date int(10) unsigned NOT NULL,
  log_code smallint(1) unsigned NOT NULL,
  log_msg varchar(32) NOT NULL,
  email_address varchar(255) NOT NULL,
  ip_address varchar(32) NOT NULL,
  PRIMARY KEY  (id)
);";

?>