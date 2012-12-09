<?php
/*
 * Returns known settings
 *
 * No, really it does!
 *
 * Remember: ipsRegistry::$settings probably won't be available.
 *
 *
 * IPSSetUp::getSavedData('admin_email')
 * IPSSetUp::getSavedData('install_dir')   [Example: /home/user/public_html/forums] - No trailing slash supplied
 * IPSSetUp::getSavedData('install_url')   [Example: http://www.domain.tld/forums]  - No trailing slash supplied
 */
 

$knownSettings = array( 
	 'email_in'          => IPSSetUp::getSavedData('admin_email'),
	 'email_out'         => IPSSetUp::getSavedData('admin_email'),
	 'base_dir'          => IPSSetUp::getSavedData('install_dir'),
	 'css_cache_url'     => IPSSetUp::getSavedData('install_url') . '/public/style_images',
	 'upload_url'        => IPSSetUp::getSavedData('install_url') . '/uploads',
	 'upload_dir'        => IPSSetUp::getSavedData('install_dir') . '/uploads',
	 'search_sql_method' => ipsRegistry::DB()->checkFulltextSupport() ? 'ftext' : 'man',
	 'safe_mode_skins'   => ( @ini_get("safe_mode") ) ? 1 : 0
);