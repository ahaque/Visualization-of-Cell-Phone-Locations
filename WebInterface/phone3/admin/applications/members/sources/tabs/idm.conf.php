<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * IDM plugin
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	IP.Downloads
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
$CONFIG['plugin_name']        = 'Files';

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = 'pp_tab_idm';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'idm';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = IPSLib::appIsInstalled('downloads') ? 1 : 0;

/**
* Order
*/
$CONFIG['plugin_order'] = 6;