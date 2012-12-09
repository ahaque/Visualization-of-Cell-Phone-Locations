<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Core variables for portal
 * Last Updated: $LastChangedDate: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Portal
 * @since		27th January 2004
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET = array();

###### Redirect requests... ######

if( ! $_REQUEST['module'] AND $_REQUEST['app'] == 'portal' )
{
	$_RESET['module'] = 'portal';
}


$_LOAD = array();


$_LOAD['portal']  = 1;

$CACHE['portal'] = array( 
								'array'            => 1,
								'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'portal' ) . '/modules_admin/portal/portal.php',
								'recache_class'    => 'admin_portal_portal_portal',
							    'recache_function' => 'portalRebuildCache' 
							);
