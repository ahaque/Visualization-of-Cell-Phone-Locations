<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Gallery plugin
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	IP.Gallery
 * @since		20th February 2002
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Plug in name (Default tab name)
*/
$CONFIG['plugin_name']        = "Gallery";

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = 'pp_tab_gallery';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'gallery';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = IPSLib::appIsInstalled('gallery') ? 1 : 0;

/**
* Order: CANNOT USE 1
*/
$CONFIG['plugin_order'] = 5;