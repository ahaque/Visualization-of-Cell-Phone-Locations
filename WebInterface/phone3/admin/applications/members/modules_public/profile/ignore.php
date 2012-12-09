<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Ignore a user
 * Last Updated: $Date: 2009-04-07 08:23:29 -0400 (Tue, 07 Apr 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 4414 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_ignore extends ipsCommand
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
		switch( $this->request['do'] )
		{
			default:
			case 'add':
				$result	= $this->ignoreMember( $this->request['memberID'], 'topics' );
			break;
			
			case 'remove':
				$result	= $this->stopIgnoringMember( $this->request['memberID'], 'topics' );
			break;
			
			case 'addPM':
				$result	= $this->ignoreMember( $this->request['memberID'], 'messages' );
			break;
			
			case 'removePM':
				$result	= $this->stopIgnoringMember( $this->request['memberID'], 'messages' );
			break;
		}
		
		if( $result['error'] )
		{
			$this->registry->output->showError( $result['error'], 10266 );
		}
		else
		{
			$this->registry->output->redirectScreen( $result['message'], $this->settings['base_url'] . 'showuser=' . $this->request['memberID'] );
		}
	}

 	/**
	 * Ignore a member's topics
	 *
	 * @access	public
	 * @param	integer		Member ID to ignore
	 * @param	string		Column to update
	 * @return	array 		Array of info
	 */
 	public function ignoreMember( $ignoreId, $type='topics' )
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
 		
 		$this->lang->loadLanguageFile( array( 'public_usercp' ), 'core' );
		
		$ignoreId			= intval( $ignoreId );
		$md5check			= IPSText::md5Clean( $this->request['md5check'] );
		$antiType			= $type == 'topics' ? 'messages' : 'topics';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if( !$ignoreId )
		{
			return array( 'error' => $this->lang->words['ignoreuser_noid'] );
		}
		
		if ( $md5check != $this->member->form_hash )
    	{
    		return array( 'error' => $this->lang->words['securehash_not_secure'] );
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $ignoreId, 'core' );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			return array( 'error' => $this->lang->words['ignoreuser_noid'] );
    	}
    	
		//-----------------------------------------
		// Can we ignore them?
		//-----------------------------------------
		
		if ( $member['_canBeIgnored'] !== TRUE )
		{
			return array( 0 => $this->lang->words['ignoreuser_cannot'] );
	 	}

		//-----------------------------------------
		// Already ignoring?
		//-----------------------------------------
		
		$ignoreMe = $this->DB->buildAndFetch( array( 
													'select' => '*',
													'from'   => 'ignored_users',
													'where'  => 'ignore_owner_id=' . $this->memberData['member_id'] . ' AND ignore_ignore_id=' . $member['member_id'] 
											)	 );

		if ( $ignoreMe['ignore_id'] )
		{
			if( $ignoreMe['ignore_' . $type ] )
			{
				return array( 'error' => $this->lang->words['ignoreuser_already'] );
			}
			else
			{
				$this->DB->update( 'ignored_users', array( 'ignore_' . $type  => 1 ), 'ignore_id=' . $ignoreMe['ignore_id'] );
				
				return array( 'message' => $this->lang->words['ignoreuser_success'] );
			}
		}

		//-----------------------------------------
		// Add it
		//-----------------------------------------

		$this->DB->insert( 'ignored_users', array( 
													'ignore_owner_id'		=> $this->memberData['member_id'],
													'ignore_ignore_id'		=> $member['member_id'],
													'ignore_' . $antiType	=> 0,
													'ignore_' . $type		=> 1,
												) 
						);
		
		/* Rebuild cache */
		IPSMember::rebuildIgnoredUsersCache( $this->memberData );
		
		return array( 'message' => $this->lang->words['ignoreuser_success'] );
	}

 	/**
	 * Stop ignoring the user's topics
	 *
	 * @access	public
	 * @param	integer		Member ID to stop ignoring
	 * @param	string		Column to update
	 * @return	array 		Array of info
	 */
 	public function stopIgnoringMember( $ignoreId, $type='topics' )
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
 		
 		$this->lang->loadLanguageFile( array( 'public_usercp' ), 'core' );
		
		$ignoreId			= intval( $ignoreId );
		$md5check			= IPSText::md5Clean( $this->request['md5check'] );
		$antiType			= $type == 'topics' ? 'messages' : 'topics';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if( !$ignoreId )
		{
			return array( 'error' => $this->lang->words['noignoreuser_noid'] );
		}
		
		if ( $md5check != $this->member->form_hash )
    	{
    		return array( 'error' => $this->lang->words['securehash_not_secure'] );
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $ignoreId, 'core' );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			return array( 'error' => $this->lang->words['noignoreuser_noid'] );
    	}

		//-----------------------------------------
		// Already ignoring?
		//-----------------------------------------
		
		$ignoreMe = $this->DB->buildAndFetch( array( 
													'select' => '*',
													'from'   => 'ignored_users',
													'where'  => 'ignore_owner_id=' . $this->memberData['member_id'] . ' AND ignore_ignore_id=' . $member['member_id'] 
											)	 );

		if ( $ignoreMe['ignore_id'] )
		{
			if( !$ignoreMe['ignore_' . $antiType ] )
			{
				$this->DB->delete( 'ignored_users', 'ignore_id=' . $ignoreMe['ignore_id'] );
			}
			else
			{
				$this->DB->update( 'ignored_users', array( 'ignore_' . $type => 0 ), 'ignore_id=' . $ignoreMe['ignore_id'] );
			}
			
			/* Rebuild cache */
			IPSMember::rebuildIgnoredUsersCache( $this->memberData );
			
			return array( 'message' => $this->lang->words['noignoreuser_success'] );
		}
		else
		{
			return array( 'error' => $this->lang->words['noignoreuser_noid'] );
		}
	}
}