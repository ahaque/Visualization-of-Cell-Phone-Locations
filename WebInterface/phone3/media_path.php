<?php

/**
 * Define the full path to the CCS media directory
 * This should be a folder that is self-contained for security reasons.
 * e.g. if your site is in the root of the domain, and your forums in a subfolder /forums,
 * you might make a folder "images" in the root of the domain and define this path to the
 * images/ folder.  You do NOT want to define this to the forums directory.
 */


/**
 * If you use the default ccs_files folder that is included with CCS, you can
 * leave this setting alone.  If you would like to store media uploads (images, etc.)
 * somewhwere else, change this to the full path to the folder.
 * e.g. /home/account/public_html/assets
 */
$path	= DOC_IPS_ROOT_PATH . "ccs_files";


/**
 * If you use the default ccs_files folder that is included with CCS, you can
 * leave this setting alone. The URL should be a URL to the same directory defined 
 * for $path, and should NOT have a trailing slash.
 * e.g. http://www.mysite.com/assets
 */
$url	= ipsRegistry::$settings['board_url'] . '/ccs_files';






/**
 * ------------------------------------------------------------------------------------------
 * No editing below this line
 * ------------------------------------------------------------------------------------------
 */
define( 'CCS_MEDIA', $path );
define( 'CCS_MEDIA_URL', rtrim( $url, " \n\r\t/" ) );
