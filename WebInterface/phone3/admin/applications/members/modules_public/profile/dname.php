<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile View
 * Last Updated: $Date: 2009-03-04 07:36:56 -0500 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 4135 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_dname extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$info = array();
 		$id   = intval( $this->request['id'] );
 				
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
 			$this->registry->output->showError( 'dname_profiles_off', 10233 );
		}
		
 		if ( ! $this->settings['auth_allow_dnames'] )
 		{
 			$this->registry->output->showError( 'dnames_off', 10234 );
 		}
		
		if( !$id )
		{
			$this->registry->output->showError( 'dnames_no_id', 10235 );
		}

    	$member	= IPSMember::load( $id );
    	
    	//-----------------------------------------
    	// Get Dname history
    	//-----------------------------------------
 		
 		$this->DB->build( array( 'select'		=> 'd.*',
										'from'		=> array( 'dnames_change' => 'd' ),
										'where'		=> 'dname_member_id='.$id,
										'add_join'	=> array( 0 => array(	'select'	=> 'm.members_display_name',
																	  		'from'		=> array( 'members' => 'm' ),
																	  		'where'		=> 'm.member_id=d.dname_member_id',
																	  		'type'		=> 'inner' ) ),
										'order'		=> 'dname_date DESC'
								) 		);
 		$this->DB->execute();
    	
    	while( $row = $this->DB->fetch() )
    	{
    		$records[] = $row;
    	}

    	//-----------------------------------------
    	// Print the pop-up window
    	//-----------------------------------------
    	
    	$html = $this->registry->getClass('output')->getTemplate('profile')->dnameWrapper( $member['members_display_name'], $records );

		//-----------------------------------------
		// Push to print handler
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $this->lang->words['dname_title'] );
		$this->registry->getClass('output')->addNavigation( $this->lang->words['dname_title'], '' );
		$this->registry->getClass('output')->addContent( $html );
		$this->registry->output->sendOutput();
 	}
 	
}