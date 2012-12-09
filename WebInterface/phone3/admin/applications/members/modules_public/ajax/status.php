<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Allow user to change their status
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_status extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$info = array();
 		$id   = intval( $this->memberData['member_id'] );
 				
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] OR $this->memberData['gbw_no_status_update'] )
 		{
			$this->returnJsonError( $this->lang->words['status_off'] );
		}

		if( !$id )
		{
			$this->returnJsonError( $this->lang->words['status_off'] );
		}

		$newStatus	= trim( IPSText::getTextClass('bbcode')->stripBadWords( IPSText::parseCleanValue( $_POST['new_status'] ) ) );
		
		IPSMember::save( $id, array( 'extendedProfile' => array( 'pp_status' => $newStatus, 'pp_status_update' => time() ) ) );

		$this->returnJsonArray( array( 'status' => 'success', 'new_status' => $newStatus ) );

		exit;
	}
}