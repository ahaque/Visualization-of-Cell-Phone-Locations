<?php

if($_GET["phone"]) {
    $phone = $_GET["phone"];
	
mysql_connect("localhost", "root", "fin3") or die(mysql_error());

mysql_select_db("gps-geobox") or die(mysql_error());
mysql_query("CREATE TABLE IF NOT EXISTS `$phone` (
  `id` int(11) NOT NULL default '0',
  `lat` float NOT NULL default '0',
  `lng` float NOT NULL default '0',
  PRIMARY KEY  (`id`)
)");


mysql_select_db("gps-locations") or die(mysql_error());
mysql_query("CREATE TABLE IF NOT EXISTS `$phone` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `time` datetime default NULL,
  `lat` float(15,11) default NULL,
  `lng` float(15,11) default NULL,
  `speed` float(15,11) NOT NULL default '0.00000000000',
  `day` mediumtext NOT NULL,
  `inbounds` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
);");


mysql_select_db("text-messagelog") or die(mysql_error());
mysql_query("CREATE TABLE IF NOT EXISTS `$phone` (
  `id` int(11) NOT NULL auto_increment,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `msg` text NOT NULL,
  `num` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)");


mysql_select_db("text-restrictions") or die(mysql_error());
mysql_query("CREATE TABLE IF NOT EXISTS `$phone` (
  `id` int(11) NOT NULL auto_increment,
  `monS` time NOT NULL default '00:00:00',
  `monE` time NOT NULL default '00:00:00',
  `tueE` time NOT NULL default '00:00:00',
  `tueS` time NOT NULL default '00:00:00',
  `wedS` time NOT NULL default '00:00:00',
  `wedE` time NOT NULL default '00:00:00',
  `thuS` time NOT NULL default '00:00:00',
  `thuE` time NOT NULL default '00:00:00',
  `friS` time NOT NULL default '00:00:00',
  `friE` time NOT NULL default '00:00:00',
  `satS` time NOT NULL default '00:00:00',
  `satE` time NOT NULL default '00:00:00',
  `sunS` time NOT NULL default '00:00:00',
  `sunE` time NOT NULL default '00:00:00',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
)");


mysql_select_db("web-settings") or die(mysql_error());
mysql_query("CREATE TABLE IF NOT EXISTS `$phone` (
  `id` int(11) NOT NULL auto_increment,
  `numtoshow` int(11) NOT NULL default '0',
  `daytoshow` text NOT NULL,
  `webRF` int(11) NOT NULL default '0',
  `gpsenable` smallint(6) NOT NULL default '0',
  `textenable` smallint(6) NOT NULL default '0',
  `showgeobox` smallint(6) NOT NULL default '0',
  `slimit` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)");

mysql_query("INSERT INTO `$phone` (`id`, `numtoshow`, `daytoshow`, `webRF`, `gpsenable`, `textenable`) VALUES
(1, -1, '0000-00-00', 0, 1, 1)");

mysql_select_db("text-wordfilter") or die(mysql_error());
mysql_query("CREATE TABLE IF NOT EXISTS `$phone` (
  `id` mediumint(9) NOT NULL auto_increment,
  `word` text NOT NULL,
  PRIMARY KEY  (`id`)
)");
 
}

header( 'Location: http://24.27.110.178/phone3/index.php' ) ;

?> 