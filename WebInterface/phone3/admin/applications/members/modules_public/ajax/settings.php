<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile AJAX Tab Loader
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4948 $
 * @deprecated	We no longer have AJAX setting changes in profile
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_settings extends ipsAjaxCommand 
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
    	
		$member_id		= intval($this->request['member_id']);
		$member			= array();
		$command		= trim( $this->request['cmd'] );
		$value			= $this->convertAndMakeSafe( $this->request['value'], 0 );
		$md5_check		= IPSText::md5Clean( $this->request['md5check'] );
		$return_string	= '';
		$pp_b_day		= intval( $this->request['pp_b_day'] );
		$pp_b_month		= intval( $this->request['pp_b_month'] );
		$pp_b_year		= intval( $this->request['pp_b_year'] );
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5_check != $this->member->form_hash )
    	{
			$this->returnString( 'error' );
    	}

		//-----------------------------------------
    	// Check
    	//-----------------------------------------
    	
    	if ( ! $member_id OR ! $this->memberData['member_id'] )
    	{
			$this->returnString( 'error' );
    	}
    	
		if ( ! $this->memberData['g_edit_profile'] )
		{
			$this->returnString( 'error' );
		}
		
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'extendedProfile,groups' );

    	if ( ! $member['member_id'] )
    	{
			$this->returnString( 'error' );
    	}
    	
		//-----------------------------------------
		// Not the same member?
		//-----------------------------------------
		
		if ( !$this->memberData['g_is_supmod'] AND $member_id != $this->memberData['member_id'] )
		{
			$this->returnString( 'error' );
		}
    	
		//-----------------------------------------
		// Alright.. what are we doing?
		//-----------------------------------------
		
		switch( $command )
		{
			case 'birthdate':
				$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ) );
			
				if( $pp_b_month OR $pp_b_day OR $pp_b_year )
				{
					if ( ! $pp_b_month or ! $pp_b_day )
					{
						$return_string = 'dateerror';
					}
				}

				if ( ( $pp_b_month AND $pp_b_day AND $pp_b_year ) AND ! @checkdate( $pp_b_month, $pp_b_day, $pp_b_year ) )
				{
					$return_string = 'dateerror';
				}

				if( $return_string != 'dateerror' )
				{
					IPSMember::save( $member_id, array( 'core' => array( 'bday_month' => intval( $pp_b_month ), 'bday_day' => intval( $pp_b_day ), 'bday_year' => intval( $pp_b_year ) ) ) );

					$_pp_b_month = '';

					if( $pp_b_month > 0 AND $pp_b_month < 13 )
					{
						$_pp_b_month = $this->lang->words['M_' . $pp_b_month ];
					}
					
					$date_vars = array();
					
					# Adding this to support birthdays that don't specify all 3 params

					if( $_pp_b_month )
					{
						$date_vars[] = $_pp_b_month;
					}
					
					if( $pp_b_day )
					{
						$date_vars[] = $pp_b_day;
					}
					
					if( $pp_b_year )
					{
						$date_vars[] = $pp_b_year;
					}

					$return_string = ( count($date_vars) ) ? implode( '-', $date_vars ) : $this->lang->words['m_bday_unknown'];
				}
		}
		
		$this->returnString( $return_string );
	}
}