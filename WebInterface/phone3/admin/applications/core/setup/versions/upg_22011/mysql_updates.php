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


$SQL[] = "ALTER TABLE ibf_attachments_type ADD index(atype_extension);";
$SQL[] = "DELETE FROM ibf_admin_permission_keys WHERE perm_key='content:mem:add';";

$SQL[] = "ALTER TABLE ibf_upgrade_history CHANGE upgrade_notes upgrade_notes TEXT NULL;";

$SQL[] = "ALTER TABLE ibf_attachments_type ADD INDEX atype ( atype_post , atype_photo );";

$SQL[] = "ALTER TABLE ibf_moderator_logs CHANGE query_string query_string TEXT NULL;";

$SQL[] = "ALTER TABLE ibf_rss_import CHANGE rss_import_url rss_import_url TEXT NULL;";

?>