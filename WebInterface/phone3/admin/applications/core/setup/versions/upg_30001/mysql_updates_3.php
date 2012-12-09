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


# ALTER LOG TABLES
$SQL[] = "ALTER TABLE admin_logs DROP act, DROP code,
ADD appcomponent VARCHAR(255) NOT NULL default '',
ADD module VARCHAR(255) NOT NULL default '',
ADD section VARCHAR(255) NOT NULL default '',
ADD do VARCHAR(255) NOT NULL default '';";

$SQL[] = "ALTER TABLE spider_logs ADD INDEX ( entry_date );";
$SQL[] = "ALTER TABLE task_logs ADD INDEX ( log_date );";
$SQL[] = "ALTER TABLE admin_logs ADD INDEX ( ctime );";
$SQL[] = "ALTER TABLE moderator_logs ADD INDEX ( ctime );";

# ALTER SUBSCRIPTIONS MANAGER
/*$SQL[] = "ALTER TABLE subscription_currency ADD subcurrencyIsProtected TINYINT(1) NOT NULL DEFAULT 0;";
$SQL[] = "UPDATE subscription_currency SET subcurrencyIsProtected=1 WHERE subcurrency_code IN('USD','GBP','CAD','EUR');";
$SQL[] = "DROP TABLE subscription_extra;";
$SQL[] = "CREATE TABLE subscription_payment_gateways (
  gateway_id smallint(5) unsigned NOT NULL auto_increment,
  gateway_title varchar(250) NOT NULL,
  gateway_name varchar(20) NOT NULL,
  gateway_description text,
  gateway_active tinyint(1) unsigned NOT NULL default '0',
  gateway_settings text,
  PRIMARY KEY  (gateway_id),
  UNIQUE KEY gateway_name (gateway_name),
  KEY gateway_active (gateway_active)
);";
$SQL[] = "DROP TABLE subscription_methods;";
$SQL[] = "RENAME TABLE subscription_trans TO subscription_transactions;";
$SQL[] = "ALTER TABLE subscription_transactions DROP subtrans_old_group;";
$SQL[] = "ALTER TABLE subscription_transactions DROP subtrans_cumulative;";
$SQL[] = "ALTER TABLE subscription_transactions CHANGE subtrans_start_date subtrans_date VARCHAR(13) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscription_transactions DROP subtrans_end_date;";
$SQL[] = "ALTER TABLE subscription_transactions DROP subtrans_state;";
$SQL[] = "ALTER TABLE subscription_transactions DROP subtrans_trxid;";
$SQL[] = "ALTER TABLE subscription_transactions DROP subtrans_subscrid;";
$SQL[] = "ALTER TABLE subscription_transactions ADD subtrans_payment_verified TINYINT(1) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscription_transactions ADD subtrans_notes TEXT NULL;";
$SQL[] = "ALTER TABLE subscriptions ADD package_id INT NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscriptions ADD sub_member_id INT NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscriptions ADD sub_primary_member_group INT NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscriptions ADD sub_expiration_date INT NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscriptions ADD sub_is_archived TINYINT(1) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_title;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_desc;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_new_group;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_length;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_unit;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_cost;";
$SQL[] = "ALTER TABLE subscriptions DROP sub_run_module;";
$SQL[] = "CREATE TABLE subscriptions_packages ( 
   package_id int(11) NOT NULL AUTO_INCREMENT, 
   package_title varchar(250) NOT NULL, 
   package_description text, 
   package_upgrade_group int(8) NOT NULL, 
   package_duration_days int(5) DEFAULT NULL, 
   package_cost decimal(10,2) NOT NULL, 
   package_run_module varchar(250) NOT NULL, 
   package_allows_upgrade smallint(1) NOT NULL DEFAULT '0', 
   PRIMARY KEY (package_id) 
 );";*/
?>