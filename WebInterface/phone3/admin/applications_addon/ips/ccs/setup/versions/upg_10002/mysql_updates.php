<?php

$SQL[]	= "ALTER TABLE ccs_block_wizard ADD wizard_started VARCHAR( 13 ) NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE ccs_page_wizard ADD wizard_started VARCHAR( 13 ) NOT NULL DEFAULT '0';";