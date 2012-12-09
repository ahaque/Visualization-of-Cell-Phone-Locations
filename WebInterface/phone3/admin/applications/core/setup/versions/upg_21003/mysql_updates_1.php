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

$SQL[] = "ALTER TABLE ibf_members ADD members_auto_dst TINYINT(1) NOT NULL default '1';";

$SQL[] = "ALTER TABLE ibf_members CHANGE new_msg new_msg tinyint(2) default '0',
  				     	CHANGE msg_total msg_total smallint(5) default '0',
  				     	ADD members_cache MEDIUMTEXT NULL,
  						ADD members_disable_pm INT(1) NOT NULL default '0';";
  						
$SQL[] = "ALTER TABLE ibf_members ADD members_display_name VARCHAR(255) NOT NULL default '', ADD members_created_remote TINYINT(1) NOT NULL default '0';";

$SQL[] = "ALTER TABLE ibf_members ADD INDEX members_display_name( members_display_name );";

$SQL[] = "ALTER TABLE ibf_members ADD members_editor_choice VARCHAR(3) NOT NULL default 'std';";

$SQL[] = "ALTER TABLE ibf_members ADD members_markers TEXT NULL;";

$SQL[] = "ALTER TABLE ibf_message_text ADD msg_ip_address VARCHAR(16) NOT NULL default '0';";

$SQL[] = "UPDATE ibf_members SET members_display_name=name;";

?>