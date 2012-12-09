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


$SQL[] = "ALTER TABLE ibf_members CHANGE email email varchar( 150 ) NOT NULL default ''";

$SQL[] = "ALTER TABLE ibf_subscription_currency CHANGE `subcurrency_exchange` `subcurrency_exchange` DECIMAL( 16, 8 ) DEFAULT '0.00000000' NOT NULL";

?>