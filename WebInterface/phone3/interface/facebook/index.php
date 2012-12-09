<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Main public executable wrapper.
 * Set-up and load module to run
 * Last Updated: $Date: 2009-07-08 21:23:44 -0400 (Wed, 08 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2008 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 4856 $
 *
 */

define( 'IPB_THIS_SCRIPT', 'public' );
require_once( '../../initdata.php' );

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );

$registry = ipsRegistry::instance();
$registry->init();

IPSDebug::addLogMessage( 'fbc', 'fbc', $_POST );

//require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );

//ipsController::run();

exit();