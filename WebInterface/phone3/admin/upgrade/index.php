<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Upgrade gateway
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		14th May 2003
 * @version		$Rev: 3887 $
 */

define( 'IPB_THIS_SCRIPT', 'admin' );
define( 'IPS_IS_UPGRADER', TRUE );
define( 'IPS_IS_INSTALLER', FALSE );

require_once( '../../initdata.php' );

require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsRegistry_setup.php' );
require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsController_setup.php' );

ipsController::run();

exit();
