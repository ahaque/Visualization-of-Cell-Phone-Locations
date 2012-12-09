<?php
/**
* Installation Schematic File
* Generated on Thu, 30 Apr 2009 19:03:38 +0000 GMT
*/
$INDEX[] = "ALTER TABLE posts ADD FULLTEXT KEY post (post)";
$INDEX[] = "ALTER TABLE topics ADD FULLTEXT KEY title (title)";
?>