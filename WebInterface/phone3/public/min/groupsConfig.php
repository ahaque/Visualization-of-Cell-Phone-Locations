<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

$min_serveOptions['encodeOutput'] = 0;

return array(
     'js' => array(
     				$min_documentRoot . '/public/js/3rd_party/prototype.js', 
     				$min_documentRoot . '/public/js/3rd_party/scriptaculous/scriptaculous-cache.js',
     			),
    // 'css' => array('//css/file1.css', '//css/file2.css'),
);