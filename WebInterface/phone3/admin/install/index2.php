<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installation Gateway
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		14th May 2003
 * @version		$Rev: 3887 $
 */

define( 'IPB_THIS_SCRIPT', 'admin' );
define( 'IPS_IS_UPGRADER', FALSE );
define( 'IPS_IS_INSTALLER', TRUE );

require_once( '../../initdata.php' );

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

/**
* Are we overwriting an existing IP.Board 2 installation?
*/
if ( file_exists( DOC_IPS_ROOT_PATH . 'sources/ipsclass.php' ) )
{
	@header( "Location: http://" . $_SERVER["SERVER_NAME"] . str_replace( "/install/", "/upgrade/", $_SERVER["PHP_SELF"] ) );
	exit();
}

require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsRegistry_setup.php' );
require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsController_setup.php' );

ipsController::run();

exit();

?>