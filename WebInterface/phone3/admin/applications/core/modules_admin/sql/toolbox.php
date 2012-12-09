<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * SQL Admin
 * Last Updated: $Date: 2009-03-11 09:44:20 -0400 (Wed, 11 Mar 2009) $
 *
 * @author 		$Author: jason $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 4193 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_sql_toolbox extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Require the right driver file */
		require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/sql/' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '.php' );
		$dbdriver = new admin_core_sql_toolbox_module();
		$dbdriver->makeRegistryShortcuts( $registry );
		$dbdriver->doExecute( $registry );
	}
}