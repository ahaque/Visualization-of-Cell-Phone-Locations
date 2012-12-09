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

$SQL[] = "ALTER TABLE ibf_sessions CHANGE browser browser VARCHAR(200) NOT NULL default '';";
$SQL[] = "ALTER TABLE ibf_rss_import ADD rss_import_allow_html TINYINT(1) NOT NULL default '0';";

?>