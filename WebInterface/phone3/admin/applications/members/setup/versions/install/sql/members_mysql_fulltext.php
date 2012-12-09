<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:49 +0000 GMT
*/
$INDEX[] = "ALTER TABLE message_posts ADD FULLTEXT KEY msg_post (msg_post)";
$INDEX[] = "ALTER TABLE message_topics ADD FULLTEXT KEY mt_title (mt_title)";
?>