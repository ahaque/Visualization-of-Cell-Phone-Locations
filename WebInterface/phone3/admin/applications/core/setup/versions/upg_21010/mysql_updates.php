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


$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key='csite_skinchange_show'";
$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key='csite_pm_show'";
$SQL[] = "DELETE FROM ibf_conf_settings WHERE conf_key='csite_search_show'";
$SQL[] = "UPDATE ibf_conf_settings SET conf_end_group=1 WHERE conf_key='recent_topics_discuss_number'";				

?>