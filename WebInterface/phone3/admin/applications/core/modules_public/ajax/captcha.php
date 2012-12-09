<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Update captcha image
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		2.3
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_captcha extends ipsAjaxCommand 
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
    	switch( ipsRegistry::$request['do'] )
    	{
			default:
			case 'refresh':
    			$this->refresh();
    		break;
    		
    	}
	}
	
	/**
	 * Refresh the captcha image
	 *
	 * @access	public
	 * @return	void		[Outputs to screen]
	 */
	public function refresh()
	{
		$captcha_unique_id = trim( IPSText::alphanumericalClean( ipsRegistry::$request['captcha_unique_id'] ) );
		
		$template    = $this->registry->getClass('class_captcha')->getTemplate( $captcha_unique_id );
		$newUniqueID = $this->registry->getClass('class_captcha')->captchaKey;

		$this->returnString( $newUniqueID );
	}

}