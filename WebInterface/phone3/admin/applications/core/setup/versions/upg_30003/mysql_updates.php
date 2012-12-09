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


# CREATE NEW TABLES
$SQL[] = "CREATE TABLE content_cache_sigs (
	cache_content_id		INT(10) UNSIGNED NOT NULL default '0',
	cache_content			MEDIUMTEXT,
	cache_updated			INT(10) NOT NULL default '0',
	UNIQUE KEY cache_content_id( cache_content_id ),
	KEY date_index (cache_updated )
);";

$SQL[] = "CREATE TABLE content_cache_posts (
	cache_content_id		INT(10) UNSIGNED NOT NULL default '0',
	cache_content			MEDIUMTEXT,
	cache_updated			INT(10) NOT NULL default '0',
	UNIQUE KEY cache_content_id( cache_content_id ),
	KEY date_index (cache_updated )
);";


?>