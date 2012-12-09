<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * XML gateway file
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 3887 $
 *
 */

/**
* Turn off 'friendly' URLs
*/
define( 'ALLOW_FURLS', FALSE );

require_once( './initdata.php' );

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );

ipsController::run();

exit();