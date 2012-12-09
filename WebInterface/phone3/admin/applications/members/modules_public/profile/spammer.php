<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Allow user to change their status
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4239 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_status extends ipsCommand 
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
		// Security check
		//-----------------------------------------
		
		if ( $this->request['k'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'no_permission', 20314 );
		}
    	
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
		
		if ( !$this->memberData['g_mem_info'] )
 		{
			$this->registry->output->showError( 'status_off', 10268 );
		}

		if( !$id )
		{
			$this->registry->output->showError( 'status_off', 10269 );
		}

		$newStatus	= trim( IPSText::getTextClass('bbcode')->stripBadWords( $this->request['new_status'] ) );
		
		IPSMember::save( $id, array( 'extendedProfile' => array( 'pp_status' => $newStatus, 'pp_status_update' => time() ) ) );

		$this->registry->output->redirectScreen( $this->lang->words['status_was_changed'], $this->settings['base_url'] . 'showuser=' . $id, $this->memberData['members_seo_name'] );
	}
}