<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin Language Loading File
 *
 * @version $Id: language.lib.php 11335 2008-06-21 14:01:54Z lem9 $
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * lang detection is done here
 */
require_once './libraries/select_lang.lib.php';

// Load the translation
require_once $lang_path . $available_languages[$GLOBALS['lang']][1] . '.inc.php';

?>
