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
# Fix bug where unread PM count not incremented

$SQL[] = "ALTER TABLE ibf_members change msg_total msg_total smallint(5) default '0'";
$SQL[] = "ALTER TABLE ibf_members change new_msg new_msg smallint(5) default '0'";
$SQL[] = "UPDATE ibf_members SET new_msg=0";


# Add WIZZY Blog hook

//$SQL[] = "ALTER TABLE ibf_members add has_blog TINYINT(1) NOT NULL default '0'";

# Efficiency

$SQL[] = "ALTER TABLE ibf_members_converge ADD INDEX converge_email(converge_email)";
$SQL[] = "ALTER TABLE ibf_polls ADD INDEX tid(tid)";

?>