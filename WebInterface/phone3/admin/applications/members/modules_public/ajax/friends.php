<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile AJAX Comment Handler
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
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_friends extends ipsAjaxCommand 
{
	/**
	 * Friends library
	 *
	 * @access	private
	 * @var		object
	 */
	private $friends;

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Friends Enabled */
		if( ! $this->settings['friends_enabled'] )
		{
			$this->registry->getClass('output')->showError( 'friends_not_enabled', 10220 );
		}
				
		/* Friends Library */
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/friends.php' );
		$this->friends = new profileFriendsLib( $registry );

		switch( $this->request['do'] )
		{
			case 'add':
				$this->_addFriend();
			break;
			
			case 'remove':
				$this->_removeFriend();
			break;	
				
			case 'view':
				$this->_iframeList();
			break;
		}
	}
	
 	/**
	 * Loads the content for the friends tab
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-15
	 */
 	private function _iframeList()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id			= intval( $this->request['member_id'] );
		$content			= '';
		$friends			= array();

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			$this->returnString( $this->lang->words['nofriendid'] );
    	}

		//-----------------------------------------
		// Grab the friends
		//-----------------------------------------

		$this->DB->build( array( 'select'		=> 'f.*',
								 'from'			=> array( 'profile_friends' => 'f' ),
								 'where'		=> 'f.friends_member_id=' . $member_id . ' AND f.friends_approved=1',
								 'order'		=> 'm.members_display_name ASC',
								 'add_join'		=> array(
													  1 => array( 'select' => 'pp.*',
																  'from'   => array( 'profile_portal' => 'pp' ),
																  'where'  => 'pp.pp_member_id=f.friends_friend_id',
																  'type'   => 'left' ),
												 	  2 => array( 'select' => 'm.*',
																  'from'   => array( 'members' => 'm' ),
																  'where'  => 'm.member_id=f.friends_friend_id',
																  'type'   => 'left' ) 
													) 
								) 		);
		$outer	= $this->DB->execute();
		
		//-----------------------------------------
		// Get and store...
		//-----------------------------------------
		
		while( $row = $this->DB->fetch($outer) )
		{
			$row['members_display_name_short'] = IPSText::truncate( $row['members_display_name'], 13 );
			
			$friends[] = IPSMember::buildDisplayData( $row, 0 );
		}

		//-----------------------------------------
		// Ok.. show the friends
		//-----------------------------------------
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->friendsIframe( $member, $friends );
		
		$this->returnHtml( $content );
	}

	/**
	 * Add a friend
	 *
	 * @access	private
	 * @return	void
	 */
 	private function _addFriend()
 	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );

		/* Add friend */		
		$result		= $this->friends->addFriend( $member_id );
		
		/* Add to other user as well, but only if not pending */
		if( !$this->friends->pendingApproval )
		{
			$result2	= $this->friends->addFriend( $this->memberData['member_id'], $member_id, true );
		}
		
		/* Check for error */
		if( $result )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnString( 'success' );
		}
	}
	
	/**
	 * Removes a friend
	 *
	 * @access	private
	 * @return	void
	 */
	private function _removeFriend()
	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );

		/* Remove friend */		
		$result		= $this->friends->removeFriend( $member_id );
		
		/* Remove from other user as well */
		$result2	= $this->friends->removeFriend( $this->memberData['member_id'], $member_id );
		
		/* Check for error */
		if( $result )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnString( 'success' );
		}		
	}
}