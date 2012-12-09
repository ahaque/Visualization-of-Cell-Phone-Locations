<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : AJAX login
 * Last Updated: $Date: 2009-01-05 22:21:54 +0000 (Mon, 05 Jan 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 3572 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_facebook extends ipsAjaxCommand 
{
	/**
	 * Login handler object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $han_login;
	
	/**
	 * Flag : Logged in
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $logged_in		= false;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	/* What to do */
		switch( $this->request['do'] )
		{
			case 'getUserByFbId':
				$return = $this->_getUserByFbId();
			break;
			case 'setAllowEmailAccess':
				$return = $this->_setAllowEmailAccess();
			break;
		}
		
		/* Output */
		$this->returnHtml( $return );
	}
	
	/**
	 * Allow facebook email access set
	 *
	 * @access	private
	 * @return	void		[Outputs JSON to browser AJAX call]
	 */
	private function _setAllowEmailAccess()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$memberID = intval( $this->memberData['member_id'] );
		
		IPSMember::update( $memberID, array( 'core' => array( 'fb_emailallow' => 1 ) ) );

		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
		
	/**
	 * Main AJAX log in routine
	 *
	 * @access	private
	 * @return	void		[Outputs JSON to browser AJAX call]
	 */
	private function _getUserByFbId()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$fbUid = intval( $this->request['fbid'] );
		$member = array( 'member_id' => 0 );
		
		if ( $fbUid )
		{
			$_mid = $this->DB->buildAndFetch( array( 'select' => 'member_id',
													 'from'   => 'members',
													 'where'  => 'fb_uid=' . $fbUid ) );
													
			if ( $_mid['member_id'] )
			{
				$member = IPSMember::load( $_mid['member_id'], 'all' );
			}
		}
		
		$this->returnJsonArray( $member );
	}
}