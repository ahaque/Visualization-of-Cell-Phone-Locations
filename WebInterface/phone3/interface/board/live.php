<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Remote API integration gateway file
 * Last Updated: $Date: 2009-07-23 21:54:55 -0400 (Thu, 23 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4933 $
 *
 */

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'api' );
define( 'IPB_LOAD_SQL'   , 'queries' );
define( 'IPS_PUBLIC_SCRIPT', 'index.php' );

require_once( '../../initdata.php' );

//===========================================================================
// MAIN PROGRAM
//===========================================================================

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );

$_GET['app']		= 'core';
$_REQUEST['app']	= 'core';
$_GET['module']		= 'global';
$_GET['section']	= 'login';
$_GET['do']			= 'process';

ipsController::run();

exit();