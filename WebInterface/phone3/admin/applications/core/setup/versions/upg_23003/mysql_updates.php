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


$SQL[] = "ALTER TABLE ibf_skin_sets ADD set_key VARCHAR( 32 ) NULL ;";
$SQL[] = "ALTER TABLE ibf_skin_sets ADD INDEX ( set_key ) ;";

?>