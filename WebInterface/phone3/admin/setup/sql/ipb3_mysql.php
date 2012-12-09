<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * SQL for upgrader
 * Last Updated: $Date: 2009-01-16 21:16:58 +0000 (Fri, 16 Jan 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 3721 $
 *
 */

$UPGRADE_HISTORY_TABLE = "CREATE TABLE upgrade_history (
  upgrade_id int(10) NOT NULL auto_increment,
  upgrade_version_id int(10) NOT NULL default '0',
  upgrade_version_human varchar(200) NOT NULL default '',
  upgrade_date int(10) NOT NULL default '0',
  upgrade_mid int(10) NOT NULL default '0',
  upgrade_notes text NOT NULL,
  upgrade_app varchar(32) NOT NULL default 'core',
  PRIMARY KEY  (upgrade_id)
);";

$UPGRADE_TABLE_FIELD   = "ALTER TABLE upgrade_history ADD upgrade_app varchar(32) NOT NULL default 'core'";

$UPGRADE_SESSION_TABLE = "CREATE TABLE upgrade_sessions (
	session_id				varchar(32)	NOT NULL default '',
	session_member_id		int(10) NOT NULL default 0,
	session_member_key		varchar(32) NOT NULL default '',
	session_start_time		int(10) NOT NULL default 0,
	session_current_time	int(10) NOT NULL default 0,
	session_ip_address		varchar(16) NOT NULL default '',
	session_section			varchar(32) NOT NULL default '',
	session_post			TEXT,
	session_get				TEXT,
	session_data			TEXT,
	session_extra			TEXT,
	PRIMARY KEY (session_id)
);";

