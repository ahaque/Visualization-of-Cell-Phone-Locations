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

# RC 1

$SQL[] = "ALTER TABLE skin_css ADD css_modules varchar(250) NOT NULL default '';";
$SQL[] = "ALTER TABLE skin_cache ADD cache_key_6 varchar(200) NOT NULL default '', ADD cache_value_6 varchar(200) NOT NULL default '';";

if( !ipsRegistry::DB()->checkForField( 'bbcode_app', 'custom_bbcode' ) )
{
	$SQL[] = "ALTER TABLE custom_bbcode ADD bbcode_app varchar(50) NOT NULL default '';";
}

$SQL[] = "ALTER TABLE skin_collections ADD set_minify	INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE core_item_markers_storage ADD KEY item_last_saved (item_last_saved);";
$SQL[] = "ALTER TABLE core_item_markers ADD KEY item_last_saved (item_last_saved);";

$SQL[] = "ALTER TABLE core_item_markers DROP INDEX combo_key;";
$SQL[] = "ALTER TABLE core_item_markers ADD UNIQUE KEY combo_key (item_key,item_member_id,item_app);";

$SQL[] = "UPDATE core_hooks SET hook_key='recent_topics' where hook_name='Recent Topics';";

?>