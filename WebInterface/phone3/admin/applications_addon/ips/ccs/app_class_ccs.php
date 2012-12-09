<?php

/**
 * Invision Power Services
 * IP.CCS class loader
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */

class app_class_ccs
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Could potentially be setup from sessions
		//-----------------------------------------
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			require_once( IPSLib::getAppDir('ccs') . '/sources/functions.php' );
			$registry->setClass( 'ccsFunctions', new ccsFunctions( $registry ) );
		}
	}
}