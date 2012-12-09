<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Friends library
 * Last Updated: $Date: 2009-05-18 22:05:12 -0400 (Mon, 18 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4668 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profileFriendsLib
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	
	/**
	 * Is approval pending?
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $pendingApproval	= false;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * Adds a friend to the account that is logged in or specified
	 *
	 * @access	public
	 * @param	integer	$friend_id		The friend being added to the account
	 * @param	integer	$from_id		The requesting member, defaults to current member
	 * @param	boolean	$forceApproval	Automatically approve, regardless of setting
	 * @return	string					Error Key or blank for success
	 */
	public function addFriend( $friend_id, $from_id=0, $forceApproval=false )
	{
		/* INIT */
		$friend_id			= intval( $friend_id );
		$from_id			= $from_id ? intval($from_id) : $this->memberData['member_id'];
		$friend				= array();
		$member				= array();
		$friends_approved	= 1;
		$message			= '';
		$subject			= '';
		$to					= array();
		$from				= array();
		$return_msg			= '';

		/* Can't add yourself */
		if( $from_id == $friend_id )
    	{
			return 'error';
    	}
		
		/* Load our friends account */
		$friend = IPSMember::load( $friend_id );
		
		/* Load our account */
		$member = IPSMember::load( $from_id );
		
    	/* This group not allowed to add friends */
    	if( !$member['g_can_add_friends'] )
    	{
    		return 'error';
    	}
		
		/* Make sure we found ourselves and our friend */
		if( ! $friend['member_id'] OR ! $member['member_id'] )
		{
			return 'error';
		}
		
		/* Are we already friends? */
		$friend_check = $this->DB->buildAndFetch( array( 
														'select'	=> 'friends_id',
														'from'		=> 'profile_friends',
														'where'		=> "friends_member_id={$from_id} AND friends_friend_id={$friend['member_id']}" 
												)	 );
																		
		if( $friend_check['friends_id'] )
		{
			return 'pp_friend_already';
		}
		
		/* Check flood table */
		if ( $this->_canAddFriend( $from_id, $friend['member_id'] ) !== TRUE )
		{
			return 'pp_friend_timeflood';
		}
		
		/* Do we approve our friends first? */
		if( !$forceApproval AND $friend['pp_setting_moderate_friends'] )
		{
			$friends_approved		= 0;
			$this->pendingApproval	= true;
		}
		
		/* Insert the friend */
		$this->DB->insert( 'profile_friends', array( 
														'friends_member_id'	=> $member['member_id'],
														'friends_friend_id'	=> $friend['member_id'],
														'friends_approved'	=> $friends_approved,
														'friends_added'		=> time() 
							)						);
																
		/* Do we need to send notifications? */
		if( ! $friends_approved AND $friend['pp_setting_notify_friend'] )
		{
			IPSText::getTextClass( 'email' )->getTemplate( "new_friend_request", $friend['language'] );
				
			IPSText::getTextClass( 'email' )->buildMessage( array( 
																	'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
																	'FRIEND_NAME'          => $member['members_display_name'],
																	'LINK'                 => "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=members&amp;section=friends&amp;module=profile&amp;do=list&amp;tab=pending"
															)		);
			 
			$message	= IPSText::getTextClass( 'email' )->message;
			$subject	= IPSText::getTextClass( 'email' )->subject;
			$to			= $friend;
			$from		= $member;
			$return_msg = 'pp_friend_added_mod';
		}
		else if( $friend['pp_setting_notify_friend'] != 'none' )
		{
			IPSText::getTextClass( 'email' )->getTemplate( "new_friend_added", $friend['language'] );

			IPSText::getTextClass( 'email' )->buildMessage( array( 
																	'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
																	'FRIEND_NAME'          => $member['members_display_name'],
																	'LINK'                 => "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=members&amp;section=friends&amp;module=profile&amp;do=list"
															)		);

			$message	= IPSText::getTextClass( 'email' )->message;
			$subject	= IPSText::getTextClass( 'email' )->subject;
			$to			= $friend;
			$from		= $member;
			$return_msg	= 'pp_friend_added';
		}
		
		/* Do we have something to send? */
		if( $message AND $subject )
		{
			/* Send an Email */
			if ( $friend['pp_setting_notify_friend'] == 'email' OR ( $friend['pp_setting_notify_friend'] AND $friend['members_disable_pm'] ) )
			{
				IPSText::getTextClass( 'email' )->subject	= $subject;
				IPSText::getTextClass( 'email' )->message	= $message;
				IPSText::getTextClass( 'email' )->to		= $to['email'];
				
				IPSText::getTextClass( 'email' )->sendMail();
			}
			/* Send a PM */
			else if ( $friend['pp_setting_notify_friend'] )
			{
				require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
				$this->messengerFunctions = new messengerFunctions( $this->registry );

				try
				{
				 	$this->messengerFunctions->sendNewPersonalTopic( $to['member_id'], 
															$from['member_id'], 
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

						$this->registry->output->showError( $msg, 10261 );
					}
					else if ( isset($this->lang->words[ 'err_' . $msg ]) )
					{
						$this->lang->words[ 'err_' . $msg ] = $this->lang->words[ 'err_' . $msg ];
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $this->lang->words[ 'err_' . $msg ] );
						
						$this->registry->output->showError( 'err_' . $msg, 10262 );
					}
					else
					{
						$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
						
						$this->registry->output->showError( 'err_UNKNOWN', 10263 );
					}
				}
			}
		}
		
		/* Reache */
		$this->recacheFriends( $member );
		$this->recacheFriends( $friend );
		
		return '';
	}
	
	/**
	 * Removes a friend from the logged in account
	 *
	 * @access	public
	 * @param	integer	$friend_id	The friend being removed
	 * @param	integer	$from_id	The requesting member, defaults to current member
	 * @return	string				Error Key or blank for success
	 */	
	public function removeFriend( $friend_id, $from_id=0 )
	{
		/* INIT */		
		$friend_id	= intval( $friend_id );
		$from_id	= $from_id ? intval($from_id) : $this->memberData['member_id'];
		$friend		= array();
		$member		= array();
		
		/* Get our friend */
		$friend = IPSMember::load( $friend_id );
		
		/* Get our member */
		$member = IPSMember::load( $from_id );
		
		/* Make sure we have both ids */
		if( ! $friend['member_id'] OR ! $member['member_id'] )
		{
			return 'error';
		}
		
		/* Check for the friend */
		$friend_check = $this->DB->buildAndFetch( array( 
																'select' => 'friends_id', 
																'from'   => 'profile_friends', 
																'where'  => "friends_member_id={$from_id} AND friends_friend_id={$friend['member_id']}"
														)	 );
																		
		if( ! $friend_check['friends_id'] )
		{
			return 'error';
		}
		
		/* Remove from the db */
		$this->DB->delete( 'profile_friends', 'friends_id=' . $friend_check['friends_id'] );
		
		/* Remove from flood */
		$this->_addFloodEntry( $from_id, $friend['member_id'] );
		
		/* Recache */
		$this->recacheFriends( $member );
		$this->recacheFriends( $friend );		
	}
	
 	/**
 	 * Recaches member's friends
 	 *
 	 * @access	public
 	 * @param	array	$member	Member array to recache
 	 * @return	boolean
 	 */
 	public function recacheFriends( $member )
 	{
		/* INIT */
		$friends = array();
		
		/* Check the member id */
		if( ! $member['member_id'] )
		{
			return FALSE;
		}
		
		/* Get our friends */
		$this->DB->build( array( 'select' => '*', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $member['member_id'] ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$friends[ $row['friends_friend_id'] ] = $row['friends_approved'];
		}
		
		/* Update the cache */
		IPSMember::packMemberCache( $member['member_id'], array( 'friends' => $friends ) );
		
		return TRUE;
	}
	
	/**
	 * Check to see if we can add this member
	 * Just checks flood table right now, but this can be expanded upon
	 *
	 * @access	private
	 * @param	int			Member ID
	 * @param	int			Friend ID
	 * @return	boolean
	 */
	private function _canAddFriend( $member_id, $friend_id )
	{
		/* Clean flood table */
		$this->_cleanFloodTable();
		
		$test = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'profile_friends_flood',
												 'where'  => 'friends_member_id=' . intval( $member_id ) . ' AND friends_friend_id=' . intval( $friend_id ) ) );
												
		if ( $test['friends_member_id'] )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Add entry to the flood table
	 *
	 * @access	private
	 * @param	int			Member ID
	 * @param	int			Friend ID
	 * @return	boolean
	 */
	private function _addFloodEntry( $member_id, $friend_id )
	{
		/* Clean flood table */
		$this->_cleanFloodTable();
		
		/* Add into flood table */
		$this->DB->replace( 'profile_friends_flood', array( 'friends_member_id' => intval( $member_id ),
															'friends_friend_id' => intval( $friend_id ),
															'friends_removed'   => time() ), array( 'friends_member_id', 'friends_friend_id' ) );
	}
	
	/**
	 * Clean flood table
	 *
	 * @access	private
	 */
	private function _cleanFloodTable()
	{
		$time = time() - ( 60 * 5 );
		
		$this->DB->delete( 'profile_friends_flood', 'friends_removed < ' . $time );
	}
}