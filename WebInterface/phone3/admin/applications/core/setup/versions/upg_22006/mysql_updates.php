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

$SQL[] = "ALTER TABLE ibf_custom_bbcode ADD bbcode_switch_option     INT(1) NOT NULL default '0',
                              ADD bbcode_add_into_menu     INT(1) NOT NULL default '0',
                              ADD bbcode_menu_option_text  VARCHAR(200) NOT NULL default '',
                              ADD bbcode_menu_content_text VARCHAR(200) NOT NULL default '';";

$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key IN ('rte_width', 'rte_pm_width');";
?>