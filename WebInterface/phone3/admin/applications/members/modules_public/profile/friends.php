<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile View
 * Last Updated: $Date: 2009-08-10 17:08:53 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 5009 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_friends extends ipsCommand
{
	/**
	 * Friend's library
	 *
	 * @access	private
	 * @var		object
	 */
	private $friend_lib;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Friends enabled? */
		if( ! $this->settings['friends_enabled'] )
		{
			$this->registry->getClass('output')->showError( 'friends_not_enabled', 10236 );
		}		
		
		/* Friend Library */
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/friends.php' );
		$this->friend_lib = new profileFriendsLib( $this->registry );
				
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_viewList();
			break;

			case 'add':
				$this->_addFriend();
			break;
			
			case 'remove':
				$this->_removeFriend();
			break;
			
			case 'moderate':
				$this->_moderation();
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
    		$this->registry->output->showError( $this->lang->words['nofriendid'], 10270 );
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
		$outer = $this->DB->execute();
		
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
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->friendsIframe( $member, $friends, true );
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $content );
		$this->registry->getClass('output')->sendOutput();
	}


 	/**
	 * Remove a friend
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-09
	 */
 	private function _removeFriend()
 	{
		/* INIT */
		$friend_id = intval( $this->request['member_id'] );

		/* Check the secure key */
		$this->request['secure_key'] = $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'nopermission', 10274 );
		}
		
		/* Remove the friend */
		$result		= $this->friend_lib->removeFriend( $friend_id );
		
		/* Remove from other user as well */
		$result2	= $this->friend_lib->removeFriend( $this->memberData['member_id'], $member_id );

		if( $result )
		{
			$this->registry->output->showError( $result, 10237 );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'showuser=' . $friend_id );
		}
	}
	
 	/**
	 * Moderate pending friends
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-09
	 */
 	private function _moderation()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$md5check			= IPSText::md5Clean( $this->request['md5check'] );
		$friends			= array();
		$friend_ids			= array();
		$friend_member_ids	= array();
		$_friend_ids		= array();
		$friends_already	= array();
		$friends_update		= array();
		$member				= array();
		$pp_option			= $this->request['pp_option'] == 'delete' ? 'delete' : 'add_reciprocal';//trim( $this->request['pp_option'] );
		$message			= '';
		$subject			= '';
		$msg				= 'pp_friend_approved';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
    		$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
			exit();
    	}

		//-----------------------------------------
		// Get friends...
		//-----------------------------------------
		
		if ( ! is_array( $this->request['pp_friend_id'] ) OR ! count( $this->request['pp_friend_id'] ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// Figure IDs
		//-----------------------------------------
		
		foreach( $this->request['pp_friend_id'] as $key => $value )
		{
			$_key = intval( $key );
			
			if ( $_key )
			{
				$_friend_ids[ $_key ] = $_key;
			}
		}
		
		if ( ! is_array( $_friend_ids ) OR ! count( $_friend_ids ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// Check our friends are OK
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'profile_friends',
										'where'	=> 'friends_friend_id=' . $this->memberData['member_id'] . ' AND friends_approved=0 AND friends_member_id IN (' . implode( ',', $_friend_ids ) . ')' ) );
												
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$friend_ids[ $row['friends_id'] ]				= $row['friends_id'];
			$friend_member_ids[ $row['friends_member_id'] ]	= $row['friends_member_id'];
		}
		
		if ( ! is_array( $friend_ids ) OR ! count( $friend_ids ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// Load friends...
		//-----------------------------------------
		
		$friends = IPSMember::load( $friend_member_ids );
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		$member = IPSMember::load( $this->memberData['member_id'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $friends ) OR ! count( $friends ) OR ! $member['member_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		if ( $pp_option == 'delete' )
		{
			//-----------------------------------------
			// Ok.. delete them in the DB.
			//-----------------------------------------
		
			$this->DB->delete( 'profile_friends', 'friends_id IN(' . implode( ',', $friend_ids ) . ')' );
			
			//-----------------------------------------
			// And make sure you are no longer their friend
			//-----------------------------------------
			
			foreach( $friend_member_ids as $friend_id )
			{
				$this->friend_lib->removeFriend( $this->memberData['member_id'], $friend_id );
			}
			
			$msg = 'pp_friend_removed';
		}
		else
		{
			//-----------------------------------------
			// Ok.. approve them in the DB.
			//-----------------------------------------
		
			$this->DB->update( 'profile_friends', array( 'friends_approved' => 1 ), 'friends_id IN(' . implode( ',', $friend_ids ) . ')' );
			
			//-----------------------------------------
			// And make sure they're added in reverse
			//-----------------------------------------
			
			foreach( $friend_member_ids as $friend_id )
			{
				$this->friend_lib->addFriend( $this->memberData['member_id'], $friend_id, true );
			}

			//-----------------------------------------
			// Reciprocal mode?
			//-----------------------------------------
			
			if ( $pp_option == 'add_reciprocal' )
			{
				//-----------------------------------------
				// Find out who isn't already on your list...
				//-----------------------------------------
				
				$this->DB->build( array( 'select'	=> '*',
												'from'	=> 'profile_friends',
												'where'	=> 'friends_member_id=' . $this->memberData['member_id'] . ' AND friends_approved=1 AND friends_friend_id IN (' . implode( ',', $_friend_ids ) . ')' ) );

				$this->DB->execute();

				while( $row = $this->DB->fetch() )
				{
					$friends_already[ $row['friends_friend_id'] ] = $row['friends_friend_id'];
				}
				
				//-----------------------------------------
				// Check which aren't already members...	
				//-----------------------------------------
				
				foreach( $friend_member_ids as $id => $_id )
				{
					if ( in_array( $id, $friends_already ) )
					{
						continue;
					}
					
					$friends_update[ $id ] = $id;
				}
				
				//-----------------------------------------
				// Gonna do it?
				//-----------------------------------------
				
				if ( is_array( $friends_update ) AND count( $friends_update ) )
				{
					foreach( $friends_update as $id => $_id )
					{
						$this->DB->insert( 'profile_friends', array( 'friends_member_id'	=> $member['member_id'],
																		'friends_friend_id'	=> $id,
																		'friends_approved'	=> 1,
																		'friends_added'		=> time() ) );
					}
				}
			}
			
			//-----------------------------------------
			// Send out message...
			//-----------------------------------------
			
			foreach( $friends as $friend )
			{
				//-----------------------------------------
				// INIT
				//-----------------------------------------
				
				$message = '';
				$subject = '';
				
				if ( $friend['pp_setting_notify_friend'] )
				{
					IPSText::getTextClass( 'email' )->getTemplate( "new_friend_approved" );
				
					IPSText::getTextClass( 'email' )->buildMessage( array( 'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
												  'FRIEND_NAME'          => $member['members_display_name'],
												  'LINK'				 => $this->settings['board_url'] . '/index.' . $this->settings['php_ext'] . '?app=members&amp;module=profile&amp;section=friends&amp;do=list' ) );
			 
					$message    = IPSText::getTextClass( 'email' )->message;
					$subject    = IPSText::getTextClass( 'email' )->subject;
					$return_msg = '';
				}
		
				//-----------------------------------------
				// Got anything to send?
				//-----------------------------------------
		
				if ( $message AND $subject )
				{
					//-----------------------------------------
					// Email?
					//-----------------------------------------
			
					if ( $friend['pp_setting_notify_friend'] == 'email' OR $friend['members_disable_pm'] )
					{
						IPSText::getTextClass( 'email' )->subject	= $subject;
						IPSText::getTextClass( 'email' )->message	= $message;
						IPSText::getTextClass( 'email' )->to		= $friend['email'];
				
						IPSText::getTextClass( 'email' )->sendMail();
					}
			
					//-----------------------------------------
					// PM?
					//-----------------------------------------
			
					else
					{
						require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
						$this->messengerFunctions = new messengerFunctions( $this->registry );
			
						try
						{
						 	$this->messengerFunctions->sendNewPersonalTopic( $friend['member_id'], 
																	$member['member_id'], 
																	array(), 
																	$subject, 
																	IPSText::getTextClass( 'editor' )->method == 'rte' ? nl2br($message) : $message, 
																	array( 'origMsgID'			=> 0,
																			'fromMsgID'			=> 0,
																			'postKey'			=> md5(microtime()),
																			'trackMsg'			=> 0,
																			'addToSentFolder'	=> 0,
																			'hideCCUser'		=> 0,
																			'forcePm'			=> 1,
																			'isSystem'          => 1,
																		)
																	);
						}
						catch( Exception $error )
						{
							$msg		= $error->getMessage();
							$toMember	= IPSMember::load( $toMemberID, 'core', 'displayname' );
						   
							if ( strstr( $msg, 'BBCODE_' ) )
						    {
								$msg = str_replace( 'BBCODE_', '', $msg );
			
								$this->registry->output->showError( $msg, 10238 );
							}
							else if ( isset($this->lang->words[ 'err_' . $msg ]) )
							{
								$this->lang->words[ 'err_' . $msg ] = $this->lang->words[ 'err_' . $msg ];
								$this->lang->words[ 'err_' . $msg ] = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $this->lang->words[ 'err_' . $msg ] );
								$this->lang->words[ 'err_' . $msg ] = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $this->lang->words[ 'err_' . $msg ] );
								$this->lang->words[ 'err_' . $msg ] = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $this->lang->words[ 'err_' . $msg ] );
								
								$this->registry->output->showError( 'err_' . $msg, 10239 );
							}
							else
							{
								$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
								
								$this->registry->output->showError( 'err_UNKNOWN', 10240 );
							}
						}
					}
				}
			}
			
			$this->friend_lib->recacheFriends( $friend );
		}
		
		//-----------------------------------------
		// Recache..
		//-----------------------------------------
		
		$this->friend_lib->recacheFriends( $member );

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg='.$msg.'&tab=pending' );
	}
	
 	/**
	 * Add a friend
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-09
	 */
 	private function _addFriend()
 	{
		/* INIT */
		$friend_id = intval( $this->request['member_id'] );
		
		/* Check the secure key */
		$this->request['secure_key'] = $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'nopermission', 10273 );
		}
		
		/* Add the friend */
		$result		= $this->friend_lib->addFriend( $friend_id );
		
		/* Add to other user as well, but only if not pending */
		if( !$this->friends->pendingApproval )
		{
			$result2	= $this->friend_lib->addFriend( $this->memberData['member_id'], $friend_id, true );
		}

		if( $result )
		{
			$this->registry->output->showError( $result, 10241 );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'showuser=' . $friend_id );
		}
	}
	
 	/**
	 * List all current friends.
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-08
	 */
 	private function _viewList()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content		= '';
		$member_id		= intval( $this->memberData['member_id'] );
		$friends		= array();
		$tab			= substr( IPSText::alphanumericalClean( $this->request['tab'] ), 0, 20 );
		$friends_filter	= substr( IPSText::alphanumericalClean( $this->request['friends_filter'] ), 0, 20 );
		$_mutual_ids	= array( 0 => 0 );
		$query			= '';
		$join_field		= '';
		$time_limit		= time() - $this->settings['au_cutoff'] * 60;
		$per_page		= 25;
		$start			= intval( $this->request['st'] );
		
		//-----------------------------------------
		// Check we're a member
		//-----------------------------------------
		
		if ( ! $member_id )
		{
			$this->lang->loadLanguageFile( array( 'public_error' ), 'core' );
			$this->registry->output->showError( $this->lang->words['no_friend_mid'], 10267 );
		}
		
		//-----------------------------------------
		// To what are we doing to whom?
		//-----------------------------------------
		
		if ( $tab == 'pending' )
		{
			$query		= 'f.friends_friend_id=' . $member_id . ' AND f.friends_approved=0';
			$join_field	= 'f.friends_member_id';
		}
		else if ( $tab == 'mutual' AND $friends_filter == 'added' )
		{
			$query		= 'f.friends_friend_id=' . $member_id . ' AND f.friends_approved=1';
			$join_field	= 'f.friends_member_id';
		}
		else if ( $tab == 'mutual' )
		{
			# My friends...
			$this->DB->build( array( 'select'	=> '*',
											'from'	=> 'profile_friends',
											'where'	=> 'friends_member_id=' . $member_id . ' AND friends_approved=1' ) );
													
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$_mutual_ids[] = $row['friends_friend_id'];
			}
			
			$query		= 'f.friends_member_id IN (' . implode( ',', $_mutual_ids ) . ') AND f.friends_friend_id=' . $member_id . ' AND f.friends_approved=1';
			$join_field	= 'f.friends_member_id';
		}
		else
		{
			$query		= 'f.friends_member_id=' . $member_id;
			$join_field	= 'f.friends_friend_id';
		}
		
		//-----------------------------------------
		// Filtered?
		//-----------------------------------------
		
		if ( $friends_filter == 'online' )
		{
			$query .= " AND ( ( m.last_visit > {$time_limit} OR m.last_activity > {$time_limit} ) AND m.login_anonymous='0&1' )";
		}
		else if ( $friends_filter == 'offline' )
		{
			$query .= " AND ( m.last_activity < {$time_limit} OR ( m.login_anonymous='0&0' OR m.login_anonymous='1&0' ) )";
		}
		
		//-----------------------------------------
		// Get count...
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 
													'select'	=> 'COUNT(*) as count',
													'from'		=> array( 'profile_friends' => 'f' ),
													'where'		=> $query,
													'add_join'	=> array( array( 
																					'select'	=> '',
																					'from'		=> array( 'members' => 'm' ),
																					'where'		=> 'm.member_id=' . $join_field,
																					'type'		=> 'inner' 
																		)	) 
										)	);
		
		//-----------------------------------------
		// Pages...
		//----------------------------------------- 
		
		$pages = $this->registry->output->generatePagination( array(	
																	'totalItems'		=> intval( $count['count'] ),
																	'noDropdown'		=> 1,
												   	 				'itemsPerPage'		=> $per_page,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> 'app=members&amp;module=profile&amp;section=friends&amp;do=list&amp;tab={$tab}&amp;friends_filter=' . $friends_filter,
														 	)	);
		//-----------------------------------------
		// Get current friends...	
		//-----------------------------------------
		
		$this->DB->build( array( 
									'select'	=> 'f.*',
									'from'		=> array( 'profile_friends' => 'f' ),
									'where'		=> $query,
									'order'		=> 'm.members_l_display_name ASC',
									'limit'		=> array( $start, $per_page ),
									'add_join'	=> array(
														  array( 
																'select' => 'pp.*',
																'from'   => array( 'profile_portal' => 'pp' ),
																'where'  => 'pp.pp_member_id=' . $join_field,
																'type'   => 'left' 
																),
													 	  array( 
																'select' => 'm.*',
																'from'   => array( 'members' => 'm' ),
																'where'  => 'm.member_id=' . $join_field,
																'type'   => 'left' 
																) 
															) 
						)	);
		$q = $this->DB->execute();
		
		//-----------------------------------------
		// Get and store...
		//-----------------------------------------
		
		while( $row = $this->DB->fetch( $q ) )
		{
			$row = IPSMember::buildDisplayData( $row );

			$friends[] = $row;
		}
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->friendsList( $friends, $pages );
		
		$this->registry->output->setTitle( $this->lang->words['m_title_friends'] );
		$this->registry->output->addNavigation( $this->lang->words['m_title_friends'], '' );
		$this->registry->getClass('output')->addContent( $content );
		$this->registry->getClass('output')->sendOutput( $content );
	}
}