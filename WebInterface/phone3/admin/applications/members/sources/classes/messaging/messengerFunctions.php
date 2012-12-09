<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Comments library
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 5066 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class messengerFunctions
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
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	
	/**
	 * Directory Data
	 *
	 * @access	public
	 * @var		array
	 */
	public $_dirData = array();
	
	/**
	 * Folder Jump HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $_jumpMenu;
	
	/**
	 * Current folder ID
	 *
	 * @access	public;
	 * @var		string
	 */
	public $_currentFolderID;
	
	/**
	 * Boolean flag
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $forceMessageToSend = FALSE;
	
	/**
	 * More exception data
	 * Should write a custom exception handler really
	 *
	 * @access	public
	 * @var		array
	 */
	public $exceptionData = array();
	
	/**
	 * No. messages per page to view
	 *
	 * @access	public
	 * @var		int
	 */
	public $messagesPerPage = 20;
	
	/**
	 * Folder filter.
	 * Mostly used for 'myconvo' folder currently, but added
	 * here so it's done proper-like
	 *
	 * @access	private
	 * @var		string
	 */
	private $_folderFilter = '';
	
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
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// Need to reset?
		//-----------------------------------------
		
		if ( $this->memberData['msg_count_reset'] )
		{
			$this->memberData['pconversation_filters'] = $this->resetMembersFolderCounts( $this->memberData['member_id'] );
			$this->resetMembersTotalTopicCount( $this->memberData['member_id'] );
			$this->resetMembersNewTopicCount( $this->memberData['member_id'] );
		}
		
		//-----------------------------------------
		// INIT Folder contents
		//-----------------------------------------
		
		$folderContents = array();
		
		//-----------------------------------------
		// Do a little set up, do a litle dance, get
		// down tonight! *boogie*
		//-----------------------------------------
		
		$this->_dirData = $this->explodeFolderData( $this->memberData['pconversation_filters'] );
	
		//-----------------------------------------
		// Do we have VID?
		// No, it's just the way we walk! Haha, etc.
		//-----------------------------------------
		
		if ( $this->request['folderID'] AND $this->request['folderID'] )
		{
			$this->_currentFolderID = $this->request['folderID'];
		}
		else
		{
			/* Got any new messages? If so, show that. If not, show myconvo
			   I'm sure you could have figured that out without this silly comment...*/
			$this->_currentFolderID = ( $this->_dirData['new']['count'] ) ? 'new' : 'myconvo';
		}
		
    	//-----------------------------------------
		// Print folder links
		//-----------------------------------------
		
		foreach( $this->_dirData as $id => $data )
		{
			if ( $data['protected'] AND $id != 'myconvo' )
			{
				continue;
			}
			
			$folderContents[] = "<option value='move_$id'>{$data['real']}</option>";
		}
    	
		if ( count( $folderContents ) > 1 )
		{
			$this->_jumpMenu = implode( "\n", $folderContents );
		}
		else
		{
			$this->_jumpMenu = '';
		}
	}
	
	/**
	 * Flood control check
	 *
	 * @access	public
	 * @return	bool		(return TRUE for OK to continue, FALSE for flood stopped) Also populates $this->exceptionData[0] with time that next PM can be made
	 */
	public function floodControlCheck()
	{
		/* Disabled PM flood? */
		if ( ! $this->memberData['g_pm_flood_mins'] )
		{
			return TRUE;
		}
		
		/* Forcing a PM, bypass the check */
		if ( $this->forceMessageToSend === TRUE )
		{
			return TRUE;
		}
		
		/* Ensure we have a member */
		if ( ! $this->memberData['member_id'] )
		{
			return FALSE;
		}
		
		/* Still here? Grab their last sent PM */
		$pm = $this->DB->buildAndFetch( array( 'select' => 'MAX(mt_date) as max',
											   'from'   => 'message_topics',
											   'where'  => 'mt_starter_id=' . $this->memberData['member_id'] ) );
															
		
		if ( $pm['max'] )
		{
			$_check = time() - ( intval( $this->memberData['g_pm_flood_mins'] ) * 60 );
			
			if ( $pm['max'] >= $_check )
			{
				/* Last PM is more recent */
				$this->exceptionData = array( 0 => $this->registry->class_localization->getDate( $pm['max'] + ( intval( $this->memberData['g_pm_flood_mins'] ) * 60 ), 'LONG', 1 ) );
				return FALSE;
			}
			else
			{
				/* Last PM is older */
				return TRUE;
			}
		}
		
		return TRUE;
	}
	
 	/**
 	 * Check number of messages sent today vs your max per day setting
 	 *
 	 * @access	public
 	 * @return	bool	Can send more
 	 */
 	public function checkHasHitMax()
 	{
 		if( $this->memberData['g_pm_perday'] )
 		{
 			$_time	= time() - ( 60 * 60 * 24 );
 			$_total	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'message_topics', 'where' => 'mt_start_time > ' . $_time . ' AND mt_starter_id=' . $this->memberData['member_id'] ) );

 			if( $_total['total'] >= $this->memberData['g_pm_perday'] )
 			{
 				return true;
 			}
 		}
 		
 		return false;
 	}
	
	/**
	 * Set a folder filter
	 * Mostly used for myconvo but added here for extensibility in the future
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function addFolderFilter( $filter )
	{
		$this->_folderFilter = $filter;
	}
	
	/**
	 * Fetch new PM notification
	 *
	 * @access	public
	 * @param	int			Member ID
	 * @return 	array 		Array of data
	 */
	public function fetchUnreadNotifications( $memberID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return   = array();
		$memberID = intval( $memberID );
		$members  = array();
		$_members = array();
		
		//-----------------------------------------
		// Fetch messages
		//-----------------------------------------
		
		$this->DB->build( array( 'select'   => 'map.*',
								 'from'     => array( 'message_topic_user_map' => 'map' ),
								 'where'    => 'map.map_user_active=1 AND map.map_user_banned=0 AND map.map_has_unread=1 AND map.map_user_id=' . $memberID,
								 'add_join' => array( array( 'select' => 't.*',
															 'from'   => array( 'message_topics' => 't' ),
															 'where'  => 't.mt_id=map_topic_id',
															 'type'   => 'inner' ),
													  array( 'select' => 'p.*',
															 'from'   => array( 'message_posts' => 'p' ),
															 'where'  => 'p.msg_id=t.mt_last_msg_id',
															 'type'   => 'inner' ) ) ) );
															
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$return[ $row['msg_date'] . '.' . $row['msg_id'] ] = $row;
			
			/* Members to load */
			$_members[ $row['mt_starter_id'] ] = $row['mt_starter_id'];
			$_members[ $row['msg_author_id'] ] = $row['msg_author_id'];
		}
		
		/* Got anything? */
		if ( ! count( $return ) )
		{
			return array();
		}
		
		/* Load 'em */
		if ( count( $_members ) )
		{
			$members = IPSMember::load( $_members, 'all' );
		}
		
		/* Parse 'em */
		if ( count( $members ) )
		{
			foreach( $members as $id => $data )
			{
				$members[ $id ] = IPSMember::buildDisplayData( $data );
			}
		}
		
		/* Add 'em */
		foreach( $return as $sortID => $data )
		{
			/* membahs */
			$data['starterData'] = $members[ $data['mt_starter_id'] ];
			$data['authorData']  = $members[ $data['msg_author_id'] ];
			
			/* Format message */
			$data['msg_post'] = IPSText::getTextClass('bbcode')->stripAllTags( $data['msg_post'] ); //$this->_formatMessageForDisplay( $data['msg_post'] );
			
			/* Type of PM */
			if ( $data['msg_id'] == $data['mt_first_msg_id'] )
			{
				$data['_type'] = 'new';
			}
			else
			{
				$data['_type'] = 'reply';
			}
			
			$return[ $sortID ] = $data;
		}
		
		/* Reverse sort it (latest first) */
		krsort( $return );
		
		/* Return 'em */
		return $return;
	}
	
	/**
	 * Search messages
	 * Searches messages. It really does.
	 *
	 * @access	public
	 * @param	int			Member ID who be searching!
	 * @param	string		Words to search (probably tainted at this point, so be careful!)
	 * @param	int			Offset start
	 * @param	int			Number of results to return
	 * @param	array 		Array of folders to search (send nothing to search all)
	 * @return	array 		array( 'totalMatches' => int, 'results' => Array of topics that match the search words )
	 */
	public function searchMessages( $memberID, $words, $start=0, $end=50, $folders=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$results    = array( 'totalMatches' => 0, 'results' => array() );
		$_memberIDs = array();
		$_members   = array();
		$_results   = array();
		
		//-----------------------------------------
		// Load up library
		//-----------------------------------------
		
		$file = IPSLib::getAppDir( 'members' ) . '/sql/messengerSearch_' . strtolower( $this->settings['sql_driver'] ) . '.php';
		
		if ( ! file_exists( $file ) )
		{
			return $results;
		}
		
		//-----------------------------------------
		// Fetch 'em
		//-----------------------------------------
		
		require_once( $file );
		
		$search = new messengerSearch( $this->registry );
		
		$search->execute( $memberID, $words, $start, $end, $folders );
		
		$results['totalMatches'] = $search->fetchTotalRows();
		$_results                = $search->fetchResults();
		
		//-----------------------------------------
		// Add in member data...
		//-----------------------------------------
		
		if ( ! count( $_results ) )
		{
			return $results;
		}
		
		foreach( $_results as $mtID => $mtData )
		{
			$_memberIDs[ $mtData['mt_starter_id'] ]   = $mtData['mt_starter_id'];
			$_memberIDs[ $mtData['mt_to_member_id'] ] = $mtData['mt_to_member_id'];
		}
		
		/* Load members */
		if ( count( $_memberIDs ) )
		{
			$_members = IPSMember::load( array_keys( $_memberIDs ), 'all' );
		}
		
		/* Final result parse */
		foreach( $_results as $mtID => $mtData )
		{
			$mtData['_starterMemberData'] = $_members[ $mtData['mt_starter_id'] ];
			$mtData['_toMemberData']      = $_members[ $mtData['mt_to_member_id'] ];
			
			$mtData['_folderName']       = $this->_dirData[ $mtData['map_folder_id'] ]['real'];
			
			$results['results'][ $mtID ] = $mtData;
		}
		
		return $results;
	}
	
	/**
	 * Block / unblock a member
	 *
	 * @access	public
	 * @param	int			Member ID to block
	 * @param	int			Member ID who is attempting to block
	 * @param	int			Topic ID to apply block to
	 * @param	boolean		TRUE is block, FALSE is unblock
	 * @return	void
	 *
	 * <code>
	 * Exception Codes:
	 * NO_PERMISSION						User does not have permission to access this topic
	 * NO_BLOCK_PERMISSION					User does not have the ability to block anyone
	 * CANNOT_BLOCK_STARTER					Cannot (un)block the topic starter, that's just plain mean!
	 * CANNOT_BLOCK_USER					Cannot (un)block the member because they either are not involved with that topic or they cannot be blocked
	 * </code>
	 */
	public function toggleTopicBlock( $blockID, $blockerID, $topicID, $block )
	{
		$topicData           = $this->fetchTopicData( $topicID );
		$_members            = IPSMember::load( array( $blockID, $blockerID ), 'extendedProfile,groups' );
		$blockMemberData     = $_members[ $blockID ];
		$blockerMemberData   = $_members[ $blockerID ];			 
		$currentParticipants = $this->fetchTopicParticipants( $topicID );
		
		/* Can access this topic? */
		if ( $this->canAccessTopic( $blockerMemberData['member_id'], $topicData, $currentParticipants, TRUE ) !== TRUE )
		{
			throw new Exception( 'NO_PERMISSION' );
		}
		
		/* Topic starter? */
		if ( $topicData['mt_starter_id'] == $blockID )
		{
			throw new Exception( 'CANNOT_BLOCK_STARTER' );
		}
		
		/* Does the person we are attempting to block exist? */
		if ( ! isset($currentParticipants[ $blockID ]) )
		{
			throw new Exception( 'CANNOT_BLOCK_USER' );
		}
		
		/* Can they be blocked? */
		if ( IPSMember::isIgnorable( $blockMemberData['member_group_id'], $blockMemberData['mgroup_others'] ) !== TRUE )
		{
			throw new Exception( 'CANNOT_BLOCK_USER' );
		}
		
		/* Can we actually block anyone? */
		if ( ( $topicData['mt_starter_id'] != $blockerID ) AND ( ! $blockerMemberData['g_is_supmod'] ) )
		{
			throw new Exception( 'NO_BLOCK_PERMISSION' );
		}
		
		/* Ok... */
		if ( $block === TRUE )
		{
			$this->DB->update( 'message_topic_user_map', array( 'map_user_active' => 0, 'map_user_banned' => 1 ), 'map_user_id=' . $blockID . ' AND map_topic_id=' . $topicID . ' AND map_user_active=1' );
		}
		else
		{
			$this->DB->update( 'message_topic_user_map', array( 'map_user_active' => 1, 'map_user_banned' => 0 ), 'map_user_id=' . $blockID . ' AND map_topic_id=' . $topicID . ' AND map_user_banned=1' );
			$this->DB->update( 'message_topics', array( 'mt_to_count' => count( $currentParticipants ) - 1 ), 'mt_id=' . $topicID );
		}
		
		$this->resetMembersNewTopicCount( $blockID );
		$this->resetMembersTotalTopicCount( $blockID );
		$this->resetMembersFolderCounts( $blockID );
	}
	
	/**
	 * Toggles notifications
	 *
	 * @access	public
	 * @param	int				Owner Member ID
	 * @param	array 			Array of IDs to toggle read / unread
	 * @param	boolean			TRUE notify on / FALSE notify off
	 * @return	bool
	 *
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_MEMBER:			The member ID does not exist
	 * NO_IDS_SELECTED			No IDs to move (empty id array)
	 * </code>
	 */
	public function toggleNotificationStatus( $memberID, $ids, $notifyStatus='toggle' )
	{
		//-----------------------------------------
		// Grab data
		//-----------------------------------------
		
		$memberData = IPSMember::load( $memberID, 'groups,extendedProfile' );
		
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( "NO_SUCH_MEMBER");
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			throw new Exception("NO_IDS_SELECTED");
		}
		
		$idString = implode( ",", $ids );

		//-----------------------------------------
		// Toggle...
		//-----------------------------------------

		if ( $notifyStatus === TRUE )
		{
			$this->DB->update( 'message_topic_user_map', array( 'map_ignore_notification' => 0 ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN ($idString)" );
		}
		else if ( $notifyStatus == 'toggle' )
		{
			$_on  = array();
			$_off = array();
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'message_topic_user_map',
									 'where'  => "map_user_id=".$memberData['member_id']." AND map_topic_id IN ($idString)" ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				if ( $row['map_ignore_notification'] )
				{
					$_on[] = $row['map_topic_id'];
				}
				else
				{
					$_off[] = $row['map_topic_id'];
				}
			}
			
			if ( count( $_on ) )
			{
				$this->DB->update( 'message_topic_user_map', array( 'map_ignore_notification' => 0 ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN (" . implode(",",$_on). ")" );
			}
			
			if ( count( $_off ) )
			{
				$this->DB->update( 'message_topic_user_map', array( 'map_ignore_notification' => 1 ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN (" . implode(",",$_off). ")" );
			}
		}
		else
		{
			$this->DB->update( 'message_topic_user_map', array( 'map_ignore_notification' => 1 ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN ($idString)" );
			
		}
			
		/* Recount */
		$this->resetMembersFolderCounts( $memberData['member_id'] );
		
		return TRUE;
	}
	
	/**
	 * Toggles read/unread status
	 *
	 * @access	public
	 * @param	int				Owner Member ID
	 * @param	array 			Array of IDs to toggle read / unread
	 * @param	boolean			TRUE mark as read / FALSE mark as unread
	 * @return	bool
	 *
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_MEMBER:			The member ID does not exist
	 * NO_IDS_SELECTED			No IDs to move (empty id array)
	 * </code>
	 */
	public function toggleReadStatus( $memberID, $ids, $markAsRead )
	{
		//-----------------------------------------
		// Grab data
		//-----------------------------------------
		
		$memberData = IPSMember::load( $memberID, 'groups,extendedProfile' );
		
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( "NO_SUCH_MEMBER");
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			throw new Exception("NO_IDS_SELECTED");
		}
		
		$idString = implode( ",", $ids );
	
		//-----------------------------------------
		// Mark as read...
		//-----------------------------------------
		
		if ( $markAsRead === TRUE )
		{
			$this->DB->update( 'message_topic_user_map', array( 'map_has_unread' => 0 ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN ($idString)" );
		}
		else
		{
			$this->DB->update( 'message_topic_user_map', array( 'map_has_unread' => 1 ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN ($idString)" );
		}
			
		/* Recount */
		$this->resetMembersFolderCounts( $memberData['member_id'] );
		$this->resetMembersNewTopicCount( $memberData['member_id'] );
		
		return TRUE;
	}
	
	/**
	 * Add participants to a topic
	 *
	 * @access	public
	 * @param	int				Topic ID
	 * @param	array 			Array of IDs to invite
	 * @param	int				Member adding them (ie you)
	 * @return	mixed			bool or exception
	 *
	 * <code>
	 * EXCEPTION CODES:
	 * NO_PERMISSION				User does not have permission to access this topic
	 * NOT_ALL_INVITE_USERS_EXIST 	Not all the users you've invited exist
	 * INVITE_USERS_BLOCKED			Some of the users you've invited are blocked
	 * NO_ONE_TO_INVITE				No one left to invite
	 * CANT_INVITE_SELF:			The sender is in the invite list
	 * CANT_INVITE_RECIPIENT:		The main recipient is in the invite list
	 * CANT_INVITE_RECIPIENT_EXIST:	One or more recipients are already participating in this conversation
	 * </code>
	 */
	public function addTopicParticipants( $topicID, $invitedUsers, $readingMemberID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topicData           = $this->fetchTopicData( $topicID );
		$memberData          = IPSMember::load( intval( $readingMemberID ), 'extendedProfile,groups' );
		$currentParticipants = $this->fetchTopicParticipants( $topicID );
		
		//-----------------------------------------
		// Can access this topic?
		//-----------------------------------------
		
		if ( $this->canAccessTopic( $memberData['member_id'], $topicData, $currentParticipants ) !== TRUE )
		{
			throw new Exception( 'NO_PERMISSION' );
		}
		
		//-----------------------------------------
		// Build invited users
		//-----------------------------------------
		
		$invitedUsersData = $this->checkAndReturnInvitedUsers( $invitedUsers, $memberData );

		if ( isset($invitedUsersData[ $topicData['mt_starter_id'] ]) )
		{
			if ( $memberData['member_id'] == $topicData['mt_starter_id'] )
			{
				throw new Exception( 'CANT_INVITE_SELF' );
			}
		}
		
		if ( isset($invitedUsersData[ $topicData['mt_to_member_id'] ]) OR isset($invitedUsersData[ $topicData['mt_starter_id'] ]) )
		{
			throw new Exception( 'CANT_INVITE_RECIPIENT_EXIST' );
		}

		//-----------------------------------------
		// Knock out ones that are already participated
		// At this point $currentParticipants contains non-active
		// participants. This is OK as we don't want the ability
		// to keep re-inviting those who choose to leave.
		//-----------------------------------------
		
		$existInBoth = array_intersect( array_keys( $currentParticipants ), array_keys( $invitedUsersData ) );

		if ( count( $existInBoth ) )
		{
			foreach( $existInBoth as $id )
			{
				unset( $invitedUsersData[ $id ] );
			}
		}
		
		//-----------------------------------------
		// Anything left?
		//-----------------------------------------
		
		if ( ! count( $invitedUsersData ) )
		{
			throw new Exception( 'NO_ONE_TO_INVITE' );
		}
		
		//-----------------------------------------
		// Update the topic
		// Now we can strip non-active participants
		//-----------------------------------------
	
		$__topicParticipants = array_merge( array_keys( $this->_stripNonActiveParticipants( $currentParticipants ) ), array_keys( $invitedUsersData ) );
		
		/* Fix up so they're unique and indexed by member ID */
		if ( is_array( $__topicParticipants ) AND count ( $__topicParticipants ) )
		{
			foreach( $__topicParticipants as $_mid )
			{
				$_topicParticipants[ $_mid ] = $_mid;
			}
		}
		
		/* Remove topic starter */
		unset( $_topicParticipants[ $topicData['mt_starter_id'] ] );
		
		/* Remove recipient */
		unset( $_topicParticipants[ $topicData['mt_to_member_id'] ] );
	
		$this->DB->update( 'message_topics', array( 'mt_invited_members' => serialize( $_topicParticipants ),
												    'mt_to_count'        => count( $_topicParticipants ) + 1 ), 'mt_id=' . $topicID );
		
		//-----------------------------------------
		// Add the users to the invite tree
		//-----------------------------------------
		
		foreach( $invitedUsersData as $id => $toMember )
		{
			$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $id,
																'map_topic_id'    => $topicID,
																'map_folder_id'   => 'myconvo',
																'map_user_active' => 1,
																'map_has_unread'  => 1,
																'map_read_time'   => 0 ) );
			
			
			$new_vdir = $this->rebuildFolderCount( $toMember['member_id'], array( 'myconvo' => 'plus:1', 'new' => 'plus:1' ), TRUE, array( 'core' => array( 'msg_count_total'       => 'plus:1',
																																					   		'msg_show_notification' => $toMember['view_pop'],
																															 						   		'msg_count_new'         => 'plus:1' ) ) );
			
			//-----------------------------------------
			// Has this member requested a PM email nofity?
			//-----------------------------------------
			
			if ( $toMember['email_pm'] == 1 )
			{
				$toMember['language'] = $toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $toMember['language'];
				
				IPSText::getTextClass('email')->getTemplate("personal_convo_invite", $toMember['language']);
			
				IPSText::getTextClass('email')->buildMessage( array(
																	'NAME'   => $toMember['members_display_name'],
																	'POSTER' => $memberData['members_display_name'],
																	'TITLE'  => $topicData['mt_title'],
																	'LINK'   => "?app=members&module=messaging&section=view&do=showConversation&topicID={$topicID}" ) );
											
				$this->DB->insert( 'mail_queue', array( 'mail_to' => $toMember['email'], 'mail_from' => '', 'mail_date' => time(), 'mail_subject' => IPSText::getTextClass('email')->subject, 'mail_content' => IPSText::getTextClass('email')->message ) );

				$cache					= $this->cache->getCache('systemvars');
				$cache['mail_queue']	+= 1;
				$this->cache->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Delete personal topics
	 *
	 * @access	public
	 * @param	int				Owner Member ID
	 * @param	array 			Array of IDs to delete
	 * @param	string			[ Raw SQL to query on when selecting messages to delete, optional ]
	 * @param	bool			Force hard delete
	 * @return	mixed			bool or exception
	 *
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_MEMBER:			The member ID does not exist
	 * NO_IDS_TO_DELETE:		No IDs to delete (empty id array)
	 * </code>
	 */
	public function deleteTopics( $memberID, $ids, $rawSQL=NULL, $hardDelete=false )
	{
		//-----------------------------------------
		// Grab data
		//-----------------------------------------
		
		$memberData   = IPSMember::load( $memberID, 'groups,extendedProfile' );
		$final_ids    = array();
 		$final_mts    = array();
 		$starter      = array();
		$wanttoleave  = array();
		$participater = array();
		$system       = array();
		$deleted_ids  = array();
 		$attach_ids   = array();
		$toHardDelete = array();
		$membahs      = array( $memberID => $memberID );
		
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( "NO_SUCH_MEMBER");
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			throw new Exception("NO_IDS_TO_DELETE");
		}
		
		//-----------------------------------------
		// Finish raw SQL
		//-----------------------------------------
		
		if ( $rawSQL !== NULL )
 		{
 			$rawSQL = ' AND ' . $rawSQL;
 		}

		$idString = implode( ",", IPSLib::cleanIntArray( $ids ) );
		
		//-----------------------------------------
		// Grab all affected members...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'message_topic_user_map',
								 'where'  => 'map_topic_id IN(' . $idString . ')' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$membahs[ $row['map_user_id'] ] = $row['map_user_id'];
		}
		
		/* Flag for recount */
		$this->_flagForCountReset( $membahs );
		
		//-----------------------------------------
 		// Get messages?
 		//-----------------------------------------
 		
 		$this->DB->build( array( 'select' => 'mt.*',
								 'from'   => array( 'message_topics' => 'mt' ),
								 'where'  => "mt.mt_id IN(" . $idString . ")" . $rawSQL,
								 'add_join' => array( array( 'select' => 'map.*',
								 							 'from'   => array( 'message_topic_user_map' => 'map' ),
															 'where'  => 'map.map_topic_id=mt.mt_id AND map.map_user_id=' . $memberData['member_id'],
															 'type'   => 'left' ) ) ) );
 		$this->DB->execute();
 		
 		/* Build up topics to remove */
 		while ( $i = $this->DB->fetch() )
 		{
			/* Starter? */
			if ( $i['mt_starter_id'] == $memberData['member_id'] )
			{
				$starter[ $i['mt_id'] ]   = $i;
				$allTopics[ $i['mt_id'] ] = $i;
			}
			else if ( $i['map_user_id'] AND $i['mt_is_system'] )
			{
				$system[ $i['mt_id'] ]       = $i;
				$allTopics[ $i['mt_id'] ]    = $i;
			}
			else if ( $i['map_user_id'] )
			{
				$wanttoleave[ $i['mt_id'] ]  = $i;
				$allTopics[ $i['mt_id'] ]    = $i;
			}
 		}
		
 		//-----------------------------------------
 		// Are we forcing a hard delete
 		//-----------------------------------------
 		
 		if( $hardDelete )
 		{
 			$idsForAttachments	= array();
 			
	 		$this->DB->build( array( 'select' => 'msg_id',
									 'from'   => 'message_posts',
									 'where'  => 'msg_topic_id IN ('. $idString . ')'
							)		);
	 		$this->DB->execute();
	 		
	 		while( $r = $this->DB->fetch() )
	 		{
	 			$idsForAttachments[]	= $r['msg_id'];
	 		}
 		
			/* Delete Topics */
			$this->DB->delete( 'message_topics', 'mt_id IN ('. $idString . ')' . $rawSQL );
			
			/* Delete Posts */
			$this->DB->delete( 'message_posts', 'msg_topic_id IN ('. $idString . ')' . $rawSQL );
		
			/* Delete mappings */
			$this->DB->delete( 'message_topic_user_map', 'map_topic_id IN ('. $idString . ')' . $rawSQL );
			
	 		//-----------------------------------------
	 		// Delete attachments
	 		//-----------------------------------------

			require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
			$class_attach                  =  new class_attach( $this->registry );
			$class_attach->type            =  'msg';
			$class_attach->init();
			$class_attach->bulkRemoveAttachment( $idsForAttachments );
 		}
 		else
 		{
			//-----------------------------------------
			// System PMs, delete everything to do with them
			//-----------------------------------------
	
			if ( count( $system ) )
			{
				/* Delete Topics */
				$this->DB->delete( 'message_topics', 'mt_id IN ('. implode( ',', array_keys( $system ) ) . ')' );
				
				/* Delete Posts */
				$this->DB->delete( 'message_posts', 'msg_topic_id IN ('. implode( ',', array_keys( $system ) ) . ')' );
			
				/* Delete mappings */
				$this->DB->delete( 'message_topic_user_map', 'map_topic_id IN ('. implode( ',', array_keys( $system ) ) . ')' );
			}
			
			//-----------------------------------------
			// Ones I started, soft delete
			//-----------------------------------------
	
			if ( count( $starter ) )
			{
				/* First step: Soft delete them */
				$this->DB->update( 'message_topics', 'mt_to_count=mt_to_count-1,mt_is_deleted=1', 'mt_id IN (' . implode( ',', array_keys( $starter ) ) . ')', FALSE, TRUE );
			
				/* Update mappings for member */
				$this->DB->update( 'message_topic_user_map', array( 'map_user_active' => 0 ), 'map_user_id=' . $memberData['member_id'] . ' AND map_topic_id IN ('. implode( ',', array_keys( $starter ) ) . ')' );
			}
			
			//-----------------------------------------
			// Ones that I just want to leave..
			//-----------------------------------------
			
			if ( count( $wanttoleave ) )
			{
				/* De-activate participant row */
				$this->DB->update( 'message_topic_user_map', array( 'map_user_active' => 0 ), 'map_user_id=' . $memberData['member_id'] . ' AND map_topic_id IN ('. implode( ',', array_keys( $wanttoleave ) ) . ')' );
	
				/* Update counts */
				$this->DB->update( 'message_topics', 'mt_to_count=mt_to_count-1', 'mt_id IN (' . implode( ',', array_keys( $wanttoleave ) ) . ')', FALSE, TRUE );
			}
			
			//-----------------------------------------
			// Right. Now lets find all (partici)pantless topics
			//-----------------------------------------
	 		
			if ( count( $allTopics ) )
			{
	 			$this->DB->build( array( 'select'   => 'mt.*',
										 'from'     => array( 'message_topics' => 'mt' ),
										 'where'    => "mt.mt_id IN(" . implode( ',', array_keys( $allTopics ) ) . ")",
										 'add_join' => array( array( 'select' => 'map.*',
										 							 'from'   => array( 'message_topic_user_map' => 'map' ),
																	 'where'  => 'map.map_topic_id=mt.mt_id AND map.map_user_active=1',
																	 'type'   => 'left' ) ) ) );
		 		$this->DB->execute();
			
				while( $row = $this->DB->fetch() )
				{
					/* Not got -any- mapping? */
					if ( ! $row['map_user_id'] )
					{
						$toHardDelete[ $row['mt_id'] ] = $row;
					}
				}
				
	 			$idsForAttachments	= array();
	 			
		 		$this->DB->build( array( 'select' => 'msg_id',
										 'from'   => 'message_posts',
										 'where'  => 'msg_topic_id IN ('. implode( ',', array_keys( $allTopics ) ) . ')'
								)		);
		 		$this->DB->execute();
		 		
		 		while( $r = $this->DB->fetch() )
		 		{
		 			$idsForAttachments[]	= $r['msg_id'];
		 		}
			}
			
	 		//-----------------------------------------
	 		// Hard delete MT topics
	 		//-----------------------------------------
	 		
	 		if ( count($toHardDelete) )
	 		{
				$this->DB->delete( 'message_topics', "mt_id IN (".implode( ',', array_keys( $toHardDelete ) ).")" );
				
		 		//-----------------------------------------
		 		// Delete attachments
		 		//-----------------------------------------
	
				require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
				$class_attach                  =  new class_attach( $this->registry );
				$class_attach->type            =  'msg';
				$class_attach->init();
				$class_attach->bulkRemoveAttachment( array_keys( $idsForAttachments ) );
	
				//-----------------------------------------
				// Delete posts and mapping
				//-----------------------------------------
	
				$this->DB->delete( 'message_posts', 'msg_topic_id IN (' . implode( ',', array_keys( $toHardDelete ) ) . ')' );
				$this->DB->delete( 'message_topic_user_map', 'map_topic_id IN (' . implode( ',', array_keys( $toHardDelete ) ) . ')' );
	 		}
 		}
		
		return TRUE;
	}
	
	/**
	 * Moves messages into another folder
	 *
	 * @access	public
	 * @param	int				Owner Member ID
	 * @param	array 			Array of IDs to move
	 * @param	string			'id' of folder to move to
	 * @return	mixed			bool or exception
	 *
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_MEMBER:			The member ID does not exist
	 * NO_SUCH_FOLDER:	 	    Folder you are attemting to move to does not exist
	 * NO_IDS_TO_MOVE			No IDs to move (empty id array)
	 * </code>
	 */
	public function moveTopics( $memberID, $ids, $toFolderID )
	{
		//-----------------------------------------
		// Grab data
		//-----------------------------------------
		
		$memberData = IPSMember::load( $memberID, 'groups,extendedProfile' );
		
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( "NO_SUCH_MEMBER");
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			throw new Exception("NO_IDS_TO_MOVE");
		}
		
		$idString = implode( ",", $ids );
	
		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// Got a folder with that ID?
		//-----------------------------------------
		
		if ( ! $folders[ $toFolderID ] )
		{
			throw new Exception("NO_SUCH_FOLDER");
		}
		
		//-----------------------------------------
		// Move the messages...
		//-----------------------------------------
		
		$this->DB->update( 'message_topic_user_map', array( 'map_folder_id' => $toFolderID ), "map_user_id=".$memberData['member_id']." AND map_topic_id IN ($idString)" );
		
		/* Recount */
		$this->resetMembersFolderCounts( $memberData['member_id'] );
		
		return TRUE;
	}
	
	/**
	 * Edits a message
	 *
	 * @access	public
	 * @param	int				FROM Member ID
	 * @param	int				Topic ID
	 * @param	int				Msg ID
	 * @param	string			Message Content
	 * @return	mixed			TRUE or FALSE or Exception
	 *
	 * <code>
	 * Exception Codes:
	 * MSG_CONTENT_EMPTY:			The 'msgContent' varable is empty
	 * NO_SUCH_TOPIC:				No such topic was found
	 * NO_PERMISSION:				No permission to read/reply
	 * NO_EDIT_PERMISSION:			No permission to edit
	 * TOPIC_HAS_BEEN_DELETED:		Topic has been "deleted" by the topic starter
	 * </code>
	 */
	public function sendEdit( $fromMemberID, $topicID, $msgID, $msgContent, $options=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topicData      = array();
		$messageData    = array();
		$fromMemberData = array();
		$remapData      = array();
		
		//-----------------------------------------
		// Check content
		//-----------------------------------------
		
		if ( ! $msgContent )
		{
			throw new Exception( 'MSG_CONTENT_EMPTY' );
		}
		
		//-----------------------------------------
		// Format content
		//-----------------------------------------
		
		try
		{
			$msgContent = $this->_formatMessageForSaving( $msgContent );
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
		
		/* Fetch topic data */
		$topicData = $this->DB->buildAndFetch( array( 'select' => '*',
										 			  'from'   => 'message_topics',
										 			  'where'  => 'mt_id=' . $topicID ) );
		
		if ( ! $topicData['mt_id'] )
		{
			throw new Exception( 'NO_SUCH_TOPIC' );
		}
		
		if ( $topicData['mt_is_deleted'] )
		{
			throw new Exception( 'TOPIC_HAS_BEEN_DELETED' );
		}
		
		/* Fetch remap Data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'message_topic_user_map',
								 'where'  => 'map_topic_id=' . $topicID ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$remapData[ $row['map_user_id'] ] = $row;
		}
		
		/* Got us? */
		if ( $this->canAccessTopic( $fromMemberID, $topicData, $topicParticipants ) !== TRUE )
		{
			throw new Exception( "NO_PERMISSION" );
		}
		
		/* Fetch message data */
		$messageData = $this->fetchMessageData( $topicID, $msgID );
		
		/* Reset Post Key */
		$options['postKey'] = ( $messageData['msg_post_key'] ) ? $messageData['msg_post_key'] : md5(microtime());
		
		//-----------------------------------------
		// Load member data
		//-----------------------------------------
		
		$memberData     = IPSMember::load( array_keys( $remapData ), 'groups,extendedProfile' );
		$fromMemberData = $memberData[ $fromMemberID ];
		
		/* Can edit? */
		if ( $topicData['mt_is_deleted'] OR $this->_conversationCanEdit( $messageData, $topicData, $fromMemberData ) !== TRUE )
		{
			throw new Exception( "NO_EDIT_PERMISSION" );
		}
		
		//-----------------------------------------
		// Update the post...
		//-----------------------------------------
		
		$this->DB->update( 'message_posts', array( 'msg_post' => IPSText::removeMacrosFromInput( $msgContent ) ), 'msg_id=' . $msgID );
		
		/* Fetch attachment count */
		$count = intval( $this->_makeAttachmentsPermanent( $options['postKey'], $msgID, $topicID ) );
		
		if ( $count )
		{
			$this->DB->update( 'message_topics', array( 'mt_hasattach' => $count ), 'mt_id=' . $topicID );
		}
		
		return TRUE;
	}
	
	/**
	 * Sends a reply to a PM
	 *
	 * @access	public
	 * @param	int				FROM Member ID
	 * @param	int				Topic ID
	 * @param	string			Message Content
	 * @return	mixed			Msg ID or exception
	 *
	 * <code>
	 * Exception Codes:
	 * MSG_CONTENT_EMPTY:			The 'msgContent' varable is empty
	 * NO_SUCH_TOPIC:				No such topic was found
	 * NO_PERMISSION:				No permission to read/reply
	 * TOPIC_HAS_BEEN_DELETED:		Topic has been "deleted" by the topic starter
	 * TOPIC_IS_SYSTEM:				This is a system topic and cannot be replied to
	 * FROM_USER_BLOCKED:			Cannot send as starter has blocked fromMemberID
	 * </code>
	 */
	public function sendReply( $fromMemberID, $topicID, $msgContent, $options=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topicData      = array();
		$fromMemberData = array();
		$remapData      = array();
		
		$options['postKey'] = ( $options['postKey'] ) ? $options['postKey'] : md5(microtime());

		//-----------------------------------------
		// Check content
		//-----------------------------------------
		
		if ( ! $msgContent )
		{
			throw new Exception( 'MSG_CONTENT_EMPTY' );
		}
		
		//-----------------------------------------
		// Format content
		//-----------------------------------------
		
		try
		{
			$msgContent = $this->_formatMessageForSaving( $msgContent );
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
		
		/* Fetch topic data */
		$topicData = $this->DB->buildAndFetch( array( 'select' => '*',
										 			  'from'   => 'message_topics',
										 			  'where'  => 'mt_id=' . $topicID ) );
		
		if ( ! $topicData['mt_id'] )
		{
			throw new Exception( 'NO_SUCH_TOPIC' );
		}
		
		if ( $topicData['mt_is_deleted'] )
		{
			throw new Exception( 'TOPIC_HAS_BEEN_DELETED' );
		}
		
		if ( $topicData['mt_is_system'] )
		{
			throw new Exception( 'TOPIC_IS_SYSTEM' );
		}
		
		/* Fetch remap Data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'message_topic_user_map',
								 'where'  => 'map_user_active=1 AND map_topic_id=' . $topicID ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$remapData[ $row['map_user_id'] ] = $row;
		}
		
		/* Got us? */
		if ( ! isset($remapData[ $fromMemberID ]) )
		{
			throw new Exception( "NO_PERMISSION" );
		} 
		
		//-----------------------------------------
		// Load member data
		//-----------------------------------------
		
		$memberData     = IPSMember::load( array_keys( $remapData ), 'groups,extendedProfile' );
		$fromMemberData = $memberData[ $fromMemberID ];
		
		//-----------------------------------------
		// Has the 'to' use blocked us?
		//-----------------------------------------

		if ( count( $this->blockedByUser( $fromMemberData, $topicData['mt_to_member_id'] ) ) )
		{
			if ( $this->forceMessageToSend !== TRUE )
			{
				throw new Exception( 'FROM_USER_BLOCKED' );
			}
		}

		//-----------------------------------------
		// Add the post...
		//-----------------------------------------
		
		$this->DB->insert( 'message_posts', array(    'msg_date'	      => time(),
													  'msg_topic_id'      => $topicID,
												   	  'msg_post'          => IPSText::removeMacrosFromInput( $msgContent ),
												      'msg_post_key'      => md5( microtime() ),
												      'msg_author_id'     => $fromMemberData['member_id'],
												      'msg_ip_address'    => $this->member->ip_address ) );
			
			
		$msg_id = $this->DB->getInsertId();
		
		//-----------------------------------------
		// Update topic
		//-----------------------------------------
		
		$replyCount = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
		 											   'from'   => 'message_posts',
													   'where'  => 'msg_topic_id=' . $topicID ) );
		
		$replyCount['count'] = ( $replyCount['count'] > 0 ) ? $replyCount['count'] - 1 : 0;
		
		$this->DB->update( 'message_topics', array( 'mt_last_post_time' => time(),
													'mt_hasattach'      => ($topicData['mt_hasattach'] + intval( $this->_makeAttachmentsPermanent( $options['postKey'], $msg_id, $topicID ) )),
													'mt_replies'        => intval( $replyCount['count'] ),
													'mt_last_msg_id'	=> $msg_id ), 'mt_id=' . $topicID );
												
 		/* Update mapping */
		$this->DB->update( 'message_topic_user_map', array( 'map_has_unread' => 1 ), 'map_topic_id=' . $topicID . ' AND map_user_id != ' . $fromMemberID );

		//-----------------------------------------
 		// Update receipients....
 		//-----------------------------------------
 		
 		foreach ($remapData as $memberID => $remapData )
 		{
			$toMember = $memberData[ $memberID ];
			
			//-----------------------------------------
			// Let them know there's a new PM reply
			//-----------------------------------------
		
			if ( $memberID != $fromMemberID )
			{
				if ( $remapData['map_ignore_notification'] )
				{
					IPSMember::save( $memberID, array( 'core' => array( 'msg_count_reset' => 1 ) ) );
				}
				else
				{
					IPSMember::save( $memberID, array( 'core' => array( 'msg_count_new'         => intval( $this->getPersonalTopicsCount( $memberID, 'new') ),
																		'msg_count_reset'       => 1,
					 												    'msg_show_notification' => $memberData[ $memberID ]['view_pop'] ) ) );
				}
			}
			
			//-----------------------------------------
			// Has this member requested a PM email nofity?
			//-----------------------------------------
			
			if ( $toMember['email_pm'] == 1 AND $memberID != $fromMemberID AND ! $remapData['map_ignore_notification'] )
			{
				$toMember['language'] = $toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $toMember['language'];
				
				IPSText::getTextClass('email')->getTemplate("personal_convo_new_reply", $toMember['language']);
			
				IPSText::getTextClass('email')->buildMessage( array(
																	'NAME'   => $toMember['members_display_name'],
																	'POSTER' => $fromMemberData['members_display_name'],
																	'TITLE'  => $topicData['mt_title'],
																	'TEXT'	 => IPSText::removeMacrosFromInput( $msgContent ),
																	'LINK'   => "?app=members&module=messaging&section=view&do=findMessage&topicID=$topicID&msgID=__firstUnread__" ) );
											
				$this->DB->insert( 'mail_queue', array( 'mail_to' => $toMember['email'], 'mail_from' => '', 'mail_date' => time(), 'mail_subject' => IPSText::getTextClass('email')->subject, 'mail_content' => IPSText::getTextClass('email')->message ) );

				$cache					= $this->cache->getCache('systemvars');
				$cache['mail_queue']	+= 1;
				$this->cache->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
			}
		}
		
		return $msg_id;
	}
	
	/**
	 * Sends a new personal message. Very simple.
	 *
	 * @access	public
	 * @param	int				TO Member ID
	 * @param	int				FROM Member ID
	 * @param	array 			Array of InviteUser Names (display name)
	 * @param	string			Message Title
	 * @param	string			Message Content
	 * @param	array 			Options array[ 'isSystem' (if true, then user will have no record of sending this PM) postKey, 'isDraft', 'sendMode' (invite/copy), 'topicID' ] If a topicID is passed, it's presumed that it was a draft....
	 * @return	mixed			TRUE or FALSE or Exception
	 *
	 * <code>
	 * Exception Codes:
	 * TOPIC_ID_NOT_EXISTS:				Topic ID does not exist (re-sending a draft)
	 * NOT_ALL_INVITE_USERS_EXIST: 		Not all invite users exist (check $this->exceptionData for a list of names)
	 * NOT_ALL_INVITE_USERS_CAN_PM:		Not all invite users can PM (check $this->exceptionData for a list of names)
	 * INVITE_USERS_BLOCKED:			Some invite users have been blocked (check $this->exceptionData for a list of names)
	 * TO_USER_DOES_NOT_EXIST:		    The 'to' user ID does not exist
	 * FROM_USER_DOES_NOT_EXIST:		The 'from' user ID does not exist
	 * TO_USER_CANNOT_USE_PM:		    The 'to' user does not have access to PM system
	 * TO_USER_FULL:					The 'to' user cannot accept any more PMs (inbox full)
	 * FROM_USER_BLOCKED:			    The 'from' user has been blocked by the 'to' user
	 * CANNOT_SAVE_TO_SENT_FOLDER:	    The 'from' user does not have space to store a copy of the message in their sent folder
	 * MSG_TITLE_EMPTY:				    The 'msgTitle' variable is empty
	 * MSG_CONTENT_EMPTY:			    The 'msgContent' varable is empty
	 * CANT_SEND_TO_SELF:				The main recipient and sender are the same
	 * CANT_INVITE_SELF:				The sender is in the invite list
	 * CANT_INVITE_RECIPIENT:			The main recipient is in the invite list
	 * FLOOD_STOP						Flood control will not allow this message to send
	 * </code>
	 */
	public function sendNewPersonalTopic( $toMemberID, $fromMemberID, $inviteUsers, $msgTitle, $msgContent, $options=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$toMemberData       = array();
		$fromMemberData     = array();
		$inviteUsersData    = array();
		$isDraft		    = ( $options['isDraft'] ) ? TRUE : FALSE;
		$isCopyTo			= ( $options['sendMode'] == 'copy' ) ? TRUE : FALSE;
		$isSystem			= ( $options['isSystem'] === TRUE || $options['isSystem'] == 1 ) ? 1 : 0;
		$options['postKey'] = ( $options['postKey'] ) ? $options['postKey'] : md5(microtime());
		
		/* Set up force message*/
		$this->forceMessageToSend = ( $this->forceMessageToSend ) ? $this->forceMessageToSend : ( ( $options['forcePm'] ) ? TRUE : FALSE );
		
		//-----------------------------------------
		// Check content
		//-----------------------------------------
		
		if ( $toMemberID == $fromMemberID )
		{
			throw new Exception( 'CANT_SEND_TO_SELF' );
		}
		
		if ( ! $msgTitle )
		{
			throw new Exception( 'MSG_TITLE_EMPTY' );
		}
		
		if ( ! $msgContent )
		{
			throw new Exception( 'MSG_CONTENT_EMPTY' );
		}
		
		//-----------------------------------------
		// Format content
		//-----------------------------------------
		
		try
		{
			$_originalMessageContent = $msgContent;
			$msgContent = $this->_formatMessageForSaving( $msgContent );
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
		
		//-----------------------------------------
		// First off, load the to and from members
		//-----------------------------------------
		
		$_members = IPSMember::load( array( $toMemberID, $fromMemberID ), 'groups,extendedProfile' );
		
		$toMemberData   = $this->_setMaxMessages( $_members[ $toMemberID ] );
		$fromMemberData = $this->_setMaxMessages( $_members[ $fromMemberID ] );
		
		if ( ! $toMemberData['member_id'] AND $this->forceMessageToSend !== TRUE )
		{
			throw new Exception( 'TO_USER_DOES_NOT_EXIST' );
		}
		
		if ( ! $fromMemberData['member_id'] AND $this->forceMessageToSend !== TRUE )
		{
			throw new Exception( 'FROM_USER_DOES_NOT_EXIST' );
		}
		
		if ( $this->floodControlCheck() !== TRUE AND $this->forceMessageToSend !== TRUE )
		{
			throw new Exception( 'FLOOD_STOP' );
		}
		
		//-----------------------------------------
		// Sort out invite users
		//-----------------------------------------
		
		if ( is_array( $inviteUsers ) AND count( $inviteUsers ) )
		{
			try
			{
				if ( $fromMemberData['g_max_mass_pm'] > 0 OR $this->forceMessageToSend === TRUE )
		 		{
					$inviteUsersData = $this->checkAndReturnInvitedUsers( $inviteUsers );
				}
			}
			catch( Exception $error )
			{
				if ( $this->forceMessageToSend !== TRUE )
				{
					throw new Exception( $error->getMessage() );
				}
			}
			
			if ( isset($inviteUsersData[ $fromMemberID ]) )
			{
				throw new Exception( 'CANT_INVITE_SELF' );
			}
			
			if ( isset($inviteUsersData[ $toMemberID ]) )
			{
				throw new Exception( 'CANT_INVITE_RECIPIENT' );
			}
		}
	
		//-----------------------------------------
		// Can the 'to' user accept a PM?
		//-----------------------------------------
		
		if ( $this->canUsePMSystem( $toMemberData ) !== TRUE )
		{
			if ( $this->forceMessageToSend !== TRUE )
			{
				throw new Exception( 'TO_USER_CANNOT_USE_PM' );
			}
 		}

		//-----------------------------------------
		// Does the 'to' user have enough space?
		//-----------------------------------------
		
 		if ( $this->withinPMQuota( $toMemberData ) !== TRUE )
 		{
			if ( $this->forceMessageToSend !== TRUE )
			{
				throw new Exception( 'TO_USER_FULL' );
			}
		}
		
		//-----------------------------------------
		// Has the 'to' use blocked us?
		//-----------------------------------------
			
		if ( count( $this->blockedByUser( $fromMemberData, $toMemberData ) ) )
		{
			if ( $this->forceMessageToSend !== TRUE )
			{
				throw new Exception( 'FROM_USER_BLOCKED' );
			}
		}
		
		//-----------------------------------------
		// Is this simply a copy-to?
		//-----------------------------------------
		
		if ( $isCopyTo === TRUE )
		{
			/* Send out the main one */
			$this->sendNewPersonalTopic( $toMemberID, $fromMemberID, array(), $msgTitle, $_originalMessageContent, array() );
			
			/* Send out copy-tos */
			foreach ($inviteUsersData as $id => $toMember)
	 		{
				$this->sendNewPersonalTopic( $toMember['member_id'], $fromMemberID, array(), $msgTitle, $_originalMessageContent, array() );
			}
			
			/* Done */
			return TRUE;
		}
		
		//-----------------------------------------
		// Insert the user data
		//-----------------------------------------
		
		$_count = count( $inviteUsersData );

		//-----------------------------------------
		// Got a topic ID?
		//-----------------------------------------
		
		if ( $options['topicID'] )
		{
			/* Fetch topic data */
			$_topicData = $this->fetchTopicData( $options['topicID'] );
			
			if ( ! $_topicData['mt_id'] AND $this->forceMessageToSend !== TRUE )
			{
				throw new Exception( 'TOPIC_ID_NOT_EXISTS' );
			}
			
			$this->DB->force_data_type = array( 'mt_title' => 'string' );
			
			/* First off, update message_topics and message_posts... */
			$this->DB->update( 'message_topics', array( 'mt_date' 		     => time(),
									  				    'mt_title'           => $msgTitle,
												        'mt_starter_id'      => $fromMemberData['member_id'],
												        'mt_start_time'      => time(),
												        'mt_last_post_time'  => time(),
												        'mt_invited_members' => serialize( array_keys( $inviteUsersData ) ),
												        'mt_to_count'        => count( array_keys( $inviteUsersData ) ) + 1,
												        'mt_to_member_id'    => $toMemberData['member_id'],
												        'mt_is_draft'        => $isDraft ), 'mt_id=' . $_topicData['mt_id'] );
								
			/* Now the posts ... */
			$this->DB->update( 'message_posts', array(    'msg_date'	      => time(),
														  'msg_topic_id'      => $_topicData['mt_id'],
													   	  'msg_post'          => IPSText::removeMacrosFromInput( $msgContent ),
													      'msg_author_id'     => $fromMemberData['member_id'],
													      'msg_is_first_post' => 1,
													      'msg_ip_address'    => $this->member->ip_address ), 'msg_id=' . $_topicData['mt_first_msg_id'] );
													
			/* Delete any current user mapping as this will be sorted out below */
			$this->DB->delete( 'message_topic_user_map', 'map_topic_id=' . $_topicData['mt_id'] );
			
			/* Reset variable IDs */
			$msg_topic_id = $_topicData['mt_id'];
			$msg_id		  = $_topicData['mt_first_msg_id'];
			
			IPSMember::save( $toMemberData['member_id'], array( 'core' => array( 'msg_count_new' => 'plus:1' ) ) );
		}
		//-----------------------------------------
		// Insert new...
		//-----------------------------------------
		else
		{
			/* Create topic entry */
			$this->DB->force_data_type = array( 'mt_title' => 'string' );
		
			$this->DB->insert( 'message_topics', array( 'mt_date'            => time(),
													    'mt_title'           => $msgTitle,
													    'mt_starter_id'      => $fromMemberData['member_id'],
													    'mt_start_time'      => time(),
													    'mt_last_post_time'  => time(),
													    'mt_invited_members' => serialize( array_keys( $inviteUsersData ) ),
													    'mt_to_count'        => count( array_keys( $inviteUsersData ) ) + 1,
													    'mt_to_member_id'    => $toMemberData['member_id'],
													    'mt_is_draft'        => ( $isDraft ) ? 1 : 0,
													    'mt_is_system'       => $isSystem,
													    'mt_replies'         => 0 ) );
		
			$msg_topic_id = $this->DB->getInsertId();
		
			$this->DB->insert( 'message_posts', array(    'msg_date'	      => time(),
														  'msg_topic_id'      => $msg_topic_id,
													   	  'msg_post'          => IPSText::removeMacrosFromInput( $msgContent ),
													      'msg_post_key'      => $options['postKey'],
													      'msg_author_id'     => $fromMemberData['member_id'],
													      'msg_is_first_post' => 1,
													      'msg_ip_address'    => $this->member->ip_address ) );
			
			
			$msg_id = $this->DB->getInsertId();

			IPSMember::save( $toMemberData['member_id'], array( 'core' => array( 'msg_count_new' => 'plus:1'  ) ) );	
		}
		
		//-----------------------------------------
		// Update with last / first msg ID
		//-----------------------------------------
		
		$this->DB->update( 'message_topics', array( 'mt_last_msg_id'  => $msg_id,
													'mt_first_msg_id' => $msg_id,
		 											'mt_hasattach'    => intval( $this->_makeAttachmentsPermanent( $options['postKey'], $msg_id, $msg_topic_id ) ) ), 'mt_id=' . $msg_topic_id );
		
		//-----------------------------------------
		// Not a draft?
		//-----------------------------------------
		
		if ( $isDraft !== TRUE )
		{
			//-----------------------------------------
			// Add in 'to user' and 'from user' to the cc array
			//-----------------------------------------
		
			$inviteUsersData[ $toMemberData['member_id'] ]   = $toMemberData;
			$inviteUsersData[ $fromMemberData['member_id'] ] = $fromMemberData;
		
	 		//-----------------------------------------
	 		// Loop....
	 		//-----------------------------------------
 		
	 		foreach ($inviteUsersData as $id => $toMember)
	 		{
				//-----------------------------------------
				// Enter the info into the DB
				// Target user side.
				//-----------------------------------------
			
				$_isStarter = ( $fromMemberData['member_id'] == $toMember['member_id'] ) ? 1 : 0;
				$_isSystem  = ( $fromMemberData['member_id'] == $toMember['member_id'] AND $isSystem ) ? 1 : 0;
				$_isActive  = ( $_isSystem ) ? 0 : 1;
				
				/* Create user map entry */
				$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $toMember['member_id'],
																	'map_topic_id'    => $msg_topic_id,
																	'map_folder_id'   => 'myconvo',
																	'map_user_active' => $_isActive,
																	'map_is_starter'  => ( $fromMemberData['member_id'] == $toMember['member_id'] ) ? 1 : 0,
																	'map_has_unread'  => ( $fromMemberData['member_id'] == $toMember['member_id'] ) ? 0 : 1,
																	'map_read_time'   => ( $fromMemberData['member_id'] == $toMember['member_id'] ) ? time() : 0,
																	'map_is_system'   => $_isSystem ) );

				//-----------------------------------------
				// Update profile
				//-----------------------------------------
			
				if ( $fromMemberData['member_id'] != $toMember['member_id'] )
				{
					IPSMember::save(  $toMember['member_id'], array( 'core' => array( 'msg_count_total'       => 'plus:1',
																					  'msg_show_notification' => $toMember['view_pop'],
																					  'msg_count_reset'       => 1 ) ) );
				}
				else
				{
					IPSMember::save(  $fromMemberData['member_id'], array( 'core' => array( 'msg_count_total'       => 'plus:1',
																					        'msg_count_reset'       => 1 ) ) );
				}
												
				//-----------------------------------------
				// Has this member requested a PM email nofity?
				//-----------------------------------------
			
				if ( $toMember['email_pm'] == 1 AND $fromMemberData['member_id'] != $toMember['member_id'])
				{
					$toMember['language'] = $toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $toMember['language'];
				
					IPSText::getTextClass('email')->getTemplate("personal_convo_new_convo", $toMember['language']);
			
					IPSText::getTextClass('email')->buildMessage( array(
																		'NAME'   => $toMember['members_display_name'],
																		'POSTER' => $fromMemberData['members_display_name'],
																		'TITLE'  => $msgTitle,
																		'TEXT'	 => IPSText::removeMacrosFromInput( $msgContent ),
																		'LINK'   => "?app=members&module=messaging&section=view&do=showConversation&topicID={$msg_topic_id}#msg{$msg_id}" ) );
											
					$this->DB->insert( 'mail_queue', array( 'mail_to' => $toMember['email'], 'mail_from' => '', 'mail_date' => time(), 'mail_subject' => IPSText::getTextClass('email')->subject, 'mail_content' => IPSText::getTextClass('email')->message ) );

					$cache					= $this->cache->getCache('systemvars');
					$cache['mail_queue']	+= 1;
					$this->cache->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Is a draft
			//-----------------------------------------
			
			/* Create user map entry */
			$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $fromMemberData['member_id'],
																'map_topic_id'    => $msg_topic_id,
																'map_folder_id'   => 'drafts',
																'map_user_active' => 1,
																'map_has_unread'  => 0,
																'map_read_time'   => 0 ) );

			if( ! $options['topicID'] )
			{
				//-----------------------------------------
				// Update profile
				//-----------------------------------------
		    	
				$this->rebuildFolderCount( $fromMemberData['member_id'], array( 'drafts' => 'plus:1' ), TRUE, array( 'core' => array( 'msg_count_total'  => 'plus:1' ) ) );
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Delete messages from a topic
	 *
	 * @access	public
	 * @param	array 		Array of message IDs to remove
	 * @param	int			Deleted by member ID
	 * @return	boolean		Deleted
	 */
	public function deleteMessages( $msgIDs=array(), $deletedByMemberID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$idsToDelete     = array();
		$topics		     = array();
		$unread			 = array();
		$deletedByMember = IPSMember::load( intval( $deletedByMemberID ), 'all' );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $msgIDs ) or ! count( $msgIDs ) )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Fetch all posts...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'msg.msg_id, msg.msg_topic_id, msg.msg_author_id',
								 'from'   => array( 'message_posts' => 'msg' ),
								 'where'  => 'msg.msg_id IN (' . implode( ',', IPSLib::cleanIntArray( $msgIDs ) ) . ') AND msg.msg_is_first_post != 1',
								 'add_join' => array( array( 'select' => 'mt.*',
								 						     'from'   => array( 'message_topics' => 'mt' ),
															 'where'  => 'mt.mt_id=msg.msg_topic_id',
															 'type'   => 'left' ) ) ) );
								
		$this->DB->execute();
		
		while( $msg = $this->DB->fetch() )
		{
			if ( $this->_conversationCanDelete( $msg, $msg, $deletedByMember ) === TRUE )
			{
				$idsToDelete[ $msg['msg_id'] ]  = $msg['msg_id'];
				$topics[ $msg['msg_topic_id'] ] = $msg['msg_topic_id'];
			}
		}
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! count( $idsToDelete ) )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Is there an attachment to these messages??
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach                  =  new class_attach( $this->registry );
		$class_attach->type            =  'msg';
		$class_attach->init();
		$class_attach->bulkRemoveAttachment( $idsToDelete );
		
		//-----------------------------------------
		// Delete the messages
		//-----------------------------------------
		
		$this->DB->delete( 'message_posts', 'msg_id IN (' . implode( ',', IPSLib::cleanIntArray( $msgIDs ) ) . ')' );
		
		//-----------------------------------------
		// Rebuild member's new message count
		// This MUST go before we rebuild the topic
		// so we get all those who haven't yet read
		// the last replies...
		//-----------------------------------------
		
		$this->DB->build( array( 'select'   => '*',
								 'from'     => 'message_topic_user_map',
								 'where'    => 'map_user_active=1 AND map_topic_id IN (' . implode( ",", array_keys( $topics ) ) . ') AND map_has_unread=1' ) );
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$unread[ $row['map_user_id'] ] = $row['map_user_id'];
		}
		
		//-----------------------------------------
		// Update all relevant topics
		//-----------------------------------------
		
		foreach( array_keys( $topics ) as $topicID )
		{
			$this->rebuildTopic( $topicID );
		}		
		
		/* Update member counts */
		if ( count( $unread ) )
		{
			$this->resetMembersNewTopicCount( $unread );
		}
		
		return TRUE;
	}
	
	/**
	 * Rebuild a PM topic
	 *
	 * @access	public
	 * @param	int			Topic ID
	 * @return	boolean	
	 */
	public function rebuildTopic( $topicID )
	{
		//-----------------------------------------
		// Get message count
		//-----------------------------------------
		
		$replyCount = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
		 											   'from'   => 'message_posts',
													   'where'  => 'msg_topic_id=' . $topicID ) );
		
		$replyCount['count'] = ( $replyCount['count'] > 0 ) ? $replyCount['count'] - 1 : 0;
		
		//-----------------------------------------
		// Fetch latest msg ID
		//-----------------------------------------
		
		$latestID = $this->DB->buildAndFetch( array(  'select' => 'msg_id, msg_date',
		 											  'from'   => 'message_posts',
													  'where'  => 'msg_topic_id=' . $topicID,
													  'order'  => 'msg_date DESC',
													  'limit'  => array( 0, 1 ) ) );
													
		//-----------------------------------------
		// Fetch first msg ID
		//-----------------------------------------
		
		$firstID  = $this->DB->buildAndFetch( array(  'select' => 'msg_id, msg_date',
		 											  'from'   => 'message_posts',
													  'where'  => 'msg_topic_id=' . $topicID,
													  'order'  => 'msg_date ASC',
													  'limit'  => array( 0, 1 ) ) );


		//-----------------------------------------
		// Recount attachments
		//-----------------------------------------
		
		$attach	= $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) as attachments',
												'from'		=> array( 'attachments' => 'a' ),
												'where'		=> 'm.msg_topic_id=' . $topicID . " AND a.attach_rel_module='msg'",
												'add_join'	=> array(
																	array( 'from'	=> array( 'message_posts' => 'm' ),
																			'where'	=> 'm.msg_id=a.attach_rel_id'
																		)
																	)
										)		);

		//-----------------------------------------
		// Update...
		//-----------------------------------------
		
		$this->DB->update( 'message_posts', array( 'msg_is_first_post' => 1 ), 'msg_id=' . $firstID['msg_id'] );
		
		$this->DB->update( 'message_topics', array( 'mt_last_post_time' => $latestID['msg_date'],
													'mt_replies'        => intval( $replyCount['count'] ),
													'mt_last_msg_id'	=> $latestID['msg_id'],
													'mt_hasattach'		=> intval($attach['attachments']),
													'mt_first_msg_id'   => $firstID['msg_id'] ), 'mt_id=' . $topicID );
													
		return TRUE;
	}
	
	/**
	 * Generate a 'definitive" list of invite users from an array of names
	 *
	 * @access	public
	 * @param	array 		Array of display names [ 'bob', 'joe', 'dave' ]
	 * @param	array 		Optional[ Array of member data (group + core only required) ]
	 * @return	array 		Array of members, indexed by member_id
	 *
	 * <code>
	 * Exception Codes:
	 * NOT_ALL_INVITE_USERS_EXIST: 		Not all invite users exist (check $this->exceptionData for a list of names)
	 * NOT_ALL_INVITE_USERS_CAN_PM:		Not all invite users can PM (check $this->exceptionData for a list of names)
	 * INVITE_USERS_BLOCKED:			Some invite users have been blocked (check $this->exceptionData for a list of names)
	 * </code>
	 */
	public function checkAndReturnInvitedUsers( $names, $member=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$finalArray        = array();
		$inviteArrayByID   = array();
		$inviteArrayByName = array();
		$member            = ( $member['member_id'] ) ? $member : $this->member->fetchMemberData();
		$doesNotExist      = array();
		$cannotUsePM       = array();
		
		if ( ! is_array( $names ) OR ! count( $names ) )
		{
			return $finalArray;
		}
		
		//-----------------------------------------
		// Get the members
		//-----------------------------------------
		
		$_members = IPSMember::load( $names, 'groups,extendedProfile', 'displayname' );
		
		if ( ! is_array( $_members ) OR ! count( $_members ) )
		{
			return $finalArray;
		}
		
		//-----------------------------------------
		// Go Loopy
		//-----------------------------------------
		
		foreach( $_members as $member_id => $_member )
		{
			$inviteArrayByID[ $_member['member_id'] ]              = $_member;
			$inviteArrayByName[ $_member['members_l_display_name'] ] = $_member['member_id'];
			
			# Only allow the first X based on g_max_mass_pm
			if ( count( $inviteArrayByID ) > $member['g_max_mass_pm'] )
			{
				break;
			}
			
			/* Hard limit of 500 */
			if( count( $inviteArrayByID ) > 500 )
			{
				break;
			}
		}
		
		//-----------------------------------------
		// Go loopy again...
		//-----------------------------------------
		
		foreach( $names as $name )
		{
			//-----------------------------------------
			// Check to make sure all invite users exist
			//-----------------------------------------
			
			if ( ! in_array( strtolower( $name ), array_keys( $inviteArrayByName ) ) )
			{
				$doesNotExist[] = $name;
			}
		}
		
		if ( count( $doesNotExist ) )
		{
			$this->exceptionData = $doesNotExist;
			throw new Exception( "NOT_ALL_INVITE_USERS_EXIST" );
		}
		
		//-----------------------------------------
		// Check the block list..
		//-----------------------------------------
		
		$blockedInvitedUsers = $this->blockedByUser( $member, array_keys( $inviteArrayByID ) );
		
 		if ( count( $blockedInvitedUsers ) )
		{
			$this->exceptionData = $blockedInvitedUsers;
			throw new Exception( "INVITE_USERS_BLOCKED" );
		}
		
		//-----------------------------------------
		// Check to ensure all invite users can use the PM system
		//-----------------------------------------
		
		foreach( $inviteArrayByID as $id => $_member )
		{
			$_member = $this->_setMaxMessages( $_member );
			
			if ( $_member['g_use_pm'] != 1 OR $_member['members_disable_pm'] )
			{
				$cannotUsePM[ $id ] = $_member['members_display_name'];
			}
			
			if ( $this->withinPMQuota( $_member ) !== TRUE )
			{
				$cannotUsePM[ $id ] = $_member['members_display_name'];
			}
			
			if ( count( $cannotUsePM ) )
			{
				if ( $this->forceMessageToSend !== TRUE )
				{
					$this->exceptionData = $cannotUsePM;
					throw new Exception( "NOT_ALL_INVITE_USERS_CAN_PM" );
				}
				else
				{
					continue;
				}
			}
					
			$finalArray[ $id ] = $_member;
		}
	
	
		return $finalArray;
	}
	
	/**
	 * Returns number of messages based on folders not a SQL search
	 *
	 * @access	public
	 * @param	string		ID of folder to get count from
	 * @param	string	    Folder data line
	 * @return	int			Count
	 */
	public function getFolderTopicCount( $folderID, $folderData )
	{
		$folderArray = $this->explodeFolderData( $folderData );
		
		return intval( $folderArray[ $folderID ]['count'] );
	}
	
	/**
	 * Rebuild directory count
	 *
	 * @access	public
	 * @param	int			Member ID
	 * @param	string		Folder ID to rebuild
	 * @param	int			Count of messages
	 * @param	boolean  	Flag; do not save to DB [True is default]
	 * @param	array 		Array of extra data for passing to IPSMember::save()
	 * @return	string		Rebuilt 'vdir' string
	 */
	public function rebuildFolderCount( $memberID, $folderAndCount=array(), $save=TRUE, $extraData=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rebuild    = array();
		$dirFolders = '';
		
		//-----------------------------------------
		// Got any current directories?
		//-----------------------------------------
		
		if ( $memberID == $this->memberData['member_id'] )
		{
			if ( $this->memberData['pconversation_filters'] )
			{
				$dirFolders = $this->memberData['pconversation_filters'];
			}
		}
		else
		{
			$_member    = IPSMember::load( $memberID, 'extendedProfile' );
			$dirFolders = $_member['pconversation_filters'];
		}
		
		//-----------------------------------------
		// Parse folder data
		//-----------------------------------------
		
		$rebuild = $this->explodeFolderData( $dirFolders );
		
		//-----------------------------------------
		// Sort out new count
		//-----------------------------------------
		
		foreach( $rebuild as $id => $data )
    	{
    		if ( isset($folderAndCount[ $id ]) )
    		{
				$_count = $folderAndCount[ $id ];
				
				if ( strstr( $_count, 'plus:' ) )
				{
					$newCount = $rebuild[ $data['id'] ]['count'] + intval( str_replace( 'plus:', '', $_count ) );
				}
				else if ( strstr( $_count, 'minus:' ) )
				{
					$newCount = $rebuild[ $data['id'] ]['count'] - intval( str_replace( 'minus:', '', $_count ) );
				}
				else
				{
					$newCount = $rebuild[ $data['id'] ]['count'] = intval( $_count );
				}
				
    			$rebuild[ $data['id'] ]['count'] = ( $newCount < 1 ) ? 0 : $newCount;
    		}
    	}
    	
    	$finalString = $this->implodeFolderData( $rebuild );
    	
    	if ( $save === TRUE )
    	{
			$extraData['extendedProfile']['pconversation_filters'] = $finalString;
			
			IPSMember::save( $memberID, $extraData );
    	}

		/* Update 'us' too.. */
		if ( $memberID == $this->memberData['member_id'] )
		{
			$this->memberData['pconversation_filters'] = $finalString;
    	}

    	return $finalString;
	}
	
	/**
	 * Reset all folder counts
	 *
	 * @access	public
	 * @param	int			Member ID to reset
	 * @return	boolean
	 */
	public function resetMembersFolderCounts( $memberID )
	{
		$memberData = IPSMember::load( $memberID, 'extendedProfile' );
		
		/* Grab folders */
		$folders = $this->explodeFolderData( $memberData['pconversation_filters'] );
		
		/* Grab counts */
		$counts = $this->getPersonalTopicsCount( $memberData['member_id'] );
		
		/* Zero 'em out */
		foreach( $folders as $folderID => $data )
		{
			$folders[ $folderID ]['count'] = 0;
		}
		
		if ( is_array( $counts ) )
		{
			foreach( $counts as $folderID => $count )
			{
				$folders[ $folderID ]['count'] = intval( $count );
			}
		}
		
		/* Generate */
		$dirs = $this->implodeFolderData( $folders );
		
		/* Save */
		IPSMember::save( $memberData['member_id'], array( 'core'         	=> array( 'msg_count_reset' => 0 ),
														  'extendedProfile' => array( 'pconversation_filters' => $dirs ) ) );
														
		return $dirs;
	}
	
	/**
	 * Resets a user(s) new message count
	 *
	 * @access	public
	 * @param	mixed		Either INT member ID or ARRAY of member IDs
	 * @return 	boolean
	 */
	public function resetMembersNewTopicCount( $member )
	{
		//-----------------------------------------
		// Load members...
		//-----------------------------------------
		
		if ( is_array( $member ) )
		{
			$members = IPSMember::load( $member, 'extendedProfile' );
		}
		else
		{
			$_member = IPSMember::load( $member, 'extendedProfile' );
			$members = array( $_member['member_id'] => $_member );
		}	
		
		//-----------------------------------------
		// Loop 'em
		//-----------------------------------------
		
		foreach( $members as $id => $data )
		{
			$count  = intval( $this->getPersonalTopicsCount( $id, 'new') );
			
			//-----------------------------------------
			// Parse folder data
			//-----------------------------------------

			$rebuild = $this->explodeFolderData( $data['pconversation_filters'] );
			$rebuild['new']['count'] = $count;
			
			$msg_show_notification = ( $count ) ? $data['msg_show_notification'] : 0;
			
			IPSMember::save( $id, array( 'extendedProfile'	=> array( 'pconversation_filters'	=> $this->implodeFolderData( $rebuild ) ),
			 						     'core'				=> array( 'msg_count_new'			=> $count,
			 														  'msg_show_notification'	=> $msg_show_notification ) ) );
			
			/* Reset our counter */
			if ( $id == $this->memberData['member_id'] )
			{
				$this->memberData['msg_count_new'] = $count;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Resets a members total message count
	 *
	 * @access	public
	 * @param	int			Member ID
	 * @return	int			Message count
	 */
	public function resetMembersTotalTopicCount( $memberID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$memberID = intval( $memberID );
		
		//-----------------------------------------
		// Grab count
		//-----------------------------------------
		
		$total = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as msgTotal',
												  'from'   => 'message_topic_user_map',
												  'where'  => "map_user_id=".$memberID." AND map_user_active=1 AND map_user_banned=0 AND map_is_system=0" ) );
 		
 		$total['msgTotal'] = intval($total['msgTotal']);
 		
		IPSMember::save( $memberID, array( 'core' => array( 'msg_count_total' => $total['msgTotal'] ) ) );

 		return $total['msgTotal'];
	}
		
	/**
	 * Returns PM count on spec
	 *
	 * @access	public
	 * @param	int			Member ID
	 * @param	string		Folder ID (eg: inbox, sent, etc) [ Optional, if omitted, all msg box counts are returned ]
	 * @return	mixed 		If folder ID: Number of messages, otherwise an array of folderID => count
	 */
	public function getPersonalTopicsCount( $memberID, $folderID='' )
	{
		$return = array();
		
		if ( $memberID and $folderID )
		{
			/* New folder */
			if ( $folderID == 'new' )
			{
				$t = $this->DB->buildAndFetch( array ( 'select' => 'COUNT(*) as msg_total',
													   'from'   => 'message_topic_user_map',
													   'where'  => "map_user_active=1 AND map_user_id=" . intval( $memberID ) . " AND map_has_unread=1 AND map_ignore_notification=0" ) );
													
				return intval( $t['msg_total'] );
			}
			/* Drafts */
			else if ( $folderID == 'drafts' )
			{
				$t = $this->DB->buildAndFetch( array ( 'select' => 'COUNT(*) as msg_total',
													   'from'   => 'message_topics',
													   'where'  => "mt_starter_id=" . intval( $memberID ) . " AND mt_is_draft=1 AND mt_is_deleted=0" ) );
													
				return intval( $t['msg_total'] );
			}
			/* All other folders */
			else
			{
				$_folder = $this->DB->addSlashes( $folderID );
				
				if ( $this->_folderFilter )
				{
					if ( $this->_folderFilter == 'in' )
					{
						$t = $this->DB->buildAndFetch( array ( 'select' => 'COUNT(*) as msg_total',
															   'from'   => 'message_topic_user_map',
															   'where'  => "map_user_id=" . intval( $memberID ) . " AND map_user_active=1 AND map_folder_id='" . $_folder . "' AND map_is_starter=0" ) );

						return intval( $t['msg_total'] );
					}
					else if ( $this->_folderFilter == 'sent' )
					{
						$t = $this->DB->buildAndFetch( array ( 'select' => 'COUNT(*) as msg_total',
															   'from'   => 'message_topic_user_map',
															   'where'  => "map_user_id=" . intval( $memberID ) . " AND map_user_active=1 AND map_folder_id='" . $_folder . "' AND map_is_starter=1" ) );

						return intval( $t['msg_total'] );
					}
				}
				else
				{
					$t = $this->DB->buildAndFetch( array ( 'select' => 'COUNT(*) as msg_total',
														   'from'   => 'message_topic_user_map',
														   'where'  => "map_user_id=" . intval( $memberID ) . " AND map_user_active=1 AND map_folder_id='" . $_folder . "'" ) );

					return intval( $t['msg_total'] );
				}
			}
		}
		else if ( $memberID )
		{
			$this->DB->build( array( 'select' => 'COUNT(*) as msg_total, map_folder_id',
									 'from'   => 'message_topic_user_map',
									 'where'  => "map_is_system=0 AND map_user_active=1 AND map_user_id=" . intval( $memberID ),
									 'group'  => 'map_folder_id' ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$return[ $row['map_folder_id'] ] = $row['msg_total'];
			}
			
			$return['drafts']   = $this->getPersonalTopicsCount( $memberID, 'drafts' );
			$return['new']      = $this->getPersonalTopicsCount( $memberID, 'new' );
		
			return $return;
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 * Returns PM data based on spec
	 *
	 * @access	public
	 * @param	int 		Member ID
	 * @param	string		Folder ID (eg: inbox, sent, etc)
	 * @param	array 		Array of data ( array[ 'sort' => '', offsetStart' => '', 'offsetEnd' => '' )
	 * @return	array 		Array of PMs indexed by PM ID
	 */
	public function getPersonalTopicsList( $memberID, $folderID, $searchAndSort )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sortKey     = '';
		$where       = '';
		$memberID    = intval( $memberID );
		$_memberIDs  = array();
		$folderID    = IPSText::alphanumericalClean( $folderID, '-_' );
		$oStart	     = intval( $searchAndSort['offsetStart'] );
		$oEnd 	     = intval( $searchAndSort['offsetEnd'] );
		$messages    = array();
 		$unblockable = explode( ",", $this->settings['unblockable_pm_groups'] );
		
		//-----------------------------------------
		// Figure out sort key
		//-----------------------------------------
		
		switch ( $searchAndSort['sort'] )
 		{
 			case 'rdate':
 				$sortKey = 'mt.mt_last_post_time ASC';
 			break;
 			case 'title':
 				$sortKey = 'mt.mt_title ASC';
 			break;
 			case 'name':
 				$sortKey = 'mem.members_display_name ASC';
 			break;
 			default:
 				$sortKey = 'mt.mt_last_post_time DESC';
 			break;
 		}

		//-----------------------------------------
		// Figure out where clause (no, not santa)
		//-----------------------------------------
		
		switch( $folderID )
		{
			default:
			if ( $this->_folderFilter )
			{
				if ( $this->_folderFilter == 'in' )
				{
					$where = " AND mm.map_user_active=1 AND ( mm.map_folder_id='{$folderID}' AND mm.map_is_starter=0 )";
				}
				else if ( $this->_folderFilter == 'sent' )
				{
					$where = " AND mm.map_user_active=1 AND ( mm.map_folder_id='{$folderID}' AND mm.map_is_starter=1)";
				}
			}
			else
			{
				$where = " AND mm.map_user_active=1 AND mm.map_folder_id='{$folderID}'";
			}
			break;
			case 'drafts':
				$where = " AND mm.map_user_active=1 AND mt.mt_is_draft=1";
			break;
			case 'new':
				$where = ' AND mm.map_user_active=1 AND mm.map_has_unread=1 AND map_ignore_notification=0';
			break;	
		}
		
		if ( ! $memberID or ! $folderID )
		{
			return $messages;
		}
		else
		{
	 		$this->DB->build( array(  'select'	    => 'mm.*',
 									  'from'	    => array( 'message_topic_user_map' => 'mm' ),
									  'where'	    => "mm.map_user_id=" . $memberID . $where,
									  'order'	    => $sortKey,
									  'limit'	    => array( $oStart, $oEnd ),
									  'add_join'	=> array(
															array( 'select' => 'mt.*',
																   'from'   => array( 'message_topics' => 'mt' ),
																   'where'  => 'mm.map_topic_id=mt.mt_id',
																   'type'   => 'left'
																),
															array( 'select' => 'msg.*',
																   'from'   => array( 'message_posts' => 'msg' ),
																   'where'  => 'msg.msg_id=mt.mt_last_msg_id',
																   'type'   => 'left'
																) ) ) );
 			$this->DB->execute();
			
	 		//-----------------------------------------
	 		// Get the messages
	 		//-----------------------------------------

	 		while( $row = $this->DB->fetch() )
 			{
				$_toID    = intval( $row['mt_to_member_id'] );
				$_startID = intval( $row['mt_starter_id'] );
				$_lastID  = intval( $row['msg_author_id'] );

				/* Add member IDs */
				$_memberIDs[ $_toID ]      = $_toID;
				$_memberIDs[ $_startID ]   = $_startID;
				$_memberIDs[ $_lastID ]    = $_lastID;
				
				/* Invited users */
				if ( $row['mt_invited_members'] )
				{
					$row['_invitedMembers'] = unserialize( $row['mt_invited_members'] );
					
					if ( is_array( $row['_invitedMembers'] ) AND count( $row['_invitedMembers'] ) )
					{
						foreach ( $row['_invitedMembers'] as $_mid )
						{
							$_memberIDs[ $_mid ] = $_mid;
						}
					}
				}

				/* Pagination */
				if ( ( ($row['mt_replies'] + 1 ) % $this->messagesPerPage ) == 0 )
				{
					$pages = ($row['mt_replies'] + 1) / $this->messagesPerPage;
				}
				else
				{
					$number = ( ($row['mt_replies'] + 1) / $this->messagesPerPage );
					$pages = ceil( $number);
				}

				if ( $pages > 1 )
				{
					for ( $i = 0 ; $i < $pages ; ++$i )
					{
						$real_no = $i * $this->messagesPerPage;
						$page_no = $i + 1;

						if ( $page_no == 4 and $pages > 4 )
						{
							$row['pages'][] = array( 'last'   => 1,
							 					     'st'     => ($pages - 1) * $this->settings['display_max_posts'],
							  						 'page'   => $pages );
							break;
						}
						else
						{
							$row['pages'][] = array( 'last' => 0,
													 'st'   => $real_no,
													 'page' => $page_no );
						}
					}
				}

				/* The rest */
				$row['_folderName']        = $this->_dirData[ $row['map_folder_id'] ]['real'];
				$row['_otherInviteeCount'] = intval( $row['mt_to_count'] - 1 );
				$messages[ $row['mt_id'] ] = $row;
			}
			
			/* Got any members? */
			if ( count( $_memberIDs ) )
			{
				$members = IPSMember::load( $_memberIDs, 'core' );
			}
			
			/* Now merge (in turn!) */
			foreach( $messages as $id => $row )
			{
				/* From */
				$_member = $members[ $row['mt_starter_id'] ];
				$messages[ $id ]['_starterMemberData']                  = $_member;
				$messages[ $id ]['_starterMemberData']['_canBeBlocked'] = IPSMember::isIgnorable( $_member['member_group_id'], $_member['mgroup_others'] );
				
				/* To */
				$_member = $members[ $row['mt_to_member_id'] ];
				$messages[ $id ]['_toMemberData']                  = $_member;
				$messages[ $id ]['_toMemberData']['_canBeBlocked'] = IPSMember::isIgnorable( $_member['member_group_id'], $_member['mgroup_others'] );
				
				
				/* Last msg author */
				$messages[ $id ]['_lastMsgAuthor'] = $members[ $row['msg_author_id'] ];
				
				/* Invitees */
				if ( is_array( $row['_invitedMembers'] ) AND count( $row['_invitedMembers'] ) )
				{
					foreach ( $row['_invitedMembers'] as $_mid )
					{
						$messages[ $id ]['_invitedMemberData'][ $_mid ] = $members[ $_mid ];
						$messages[ $id ]['_invitedMemberNames'][]       = $members[ $_mid ]['members_display_name'];
					}
				}
			}
		}

		return $messages;
	}
	
	/**
	 * Returns a complete topic
	 *
	 * @access	public
	 * @param	int 		Member ID
	 * @param	string		Folder ID (eg: inbox, sent, etc)
	 * @param	array 		Array of data ( array[ sort => '', offsetStart' => '', 'offsetEnd' => '' )
	 * @return	array 		Array of PMs indexed by PM ID
	 *
	 * <code>
	 * Exception Codes
	 * NO_READ_PERMISSION		You do not have permission to read the topic
	 * YOU_ARE_BANNED			You have been banned
	 * </code>
	 */
	public function fetchConversation( $topicID, $readingMemberID, $filters=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$readingMemberID   = intval( $readingMemberID );
		$topicID     	   = intval( $topicID );
		$oStart	           = intval( $filters['offsetStart'] );
		$oEnd 	           = intval( $filters['offsetEnd'] );
		$replyData         = array();
 		$topicData         = array();
		$remapData         = array();
		$memberData		   = array();
		$missingMembers    = array();
		$whereExtra		   = '';
		
		//-----------------------------------------
		// Figure out sort key
		//-----------------------------------------
		
		switch ( $filters['sort'] )
 		{
 			case 'rdate':
 				$sortKey = 'msg.msg_date DESC';
 			break;
 			default:
 				$sortKey = 'msg.msg_date ASC';
 			break;
 		}

		if ( ! $topicID )
		{
			return array( 'topicData' => $topicData, 'replyData' => $replyData );
		}
		else
		{
			/* Get member data */
			$memberData = $this->fetchTopicParticipants( $topicID, TRUE );
			
			/* Get reading member's data */
			$readingMemberData = $memberData[ $readingMemberID ];
			
			/* Fetch topic data */
			$topicData = $this->fetchTopicData( $topicID, FALSE );
			
			/* Topic deleted? Grab topic starter details, as they won't be in the participant array */
			if ( $topicData['mt_is_deleted'] AND ( $topicData['mt_starter_id'] > 0 ) )
			{
				$memberData[ $topicData['mt_starter_id'] ] = IPSMember::load( $topicData['mt_starter_id'], 'all' );
				$memberData[ $topicData['mt_starter_id'] ]['_canBeBlocked']   = IPSMember::isIgnorable( $memberData[ $topicData['mt_starter_id'] ]['member_group_id'], $memberData[ $topicData['mt_starter_id'] ]['mgroup_others'] );
				$memberData[ $topicData['mt_starter_id'] ]                    = IPSMember::buildDisplayData( $memberData[ $topicData['mt_starter_id'] ], array( '__all__' => 1 ) );
				$memberData[ $topicData['mt_starter_id'] ]['map_user_active'] = 1;
				
				/* Set flag for topic participant starter */
				$memberData[ $topicData['mt_starter_id'] ]['map_is_starter'] = 1;
				
				foreach( $memberData as $id => $data )
				{
					$memberData[ $id ]['_topicDeleted'] = 1;
				}
			}
		
			/* Can access this topic? */
			if ( $this->canAccessTopic( $readingMemberID, $topicData, $memberData ) !== TRUE )
			{
				/* Banned? */
				if ( $readingMemberData['map_user_banned'] )
				{
					throw new Exception( "YOU_ARE_BANNED" );
				}
				else
				{
					throw new Exception( "NO_READ_PERMISSION" );
				}
			}
		
			/* Reply Data */
	 		$this->DB->build( array(  'select'	    => 'msg.*',
 									  'from'	    => array( 'message_posts' => 'msg' ),
									  'where'	    => "msg.msg_topic_id=" . $topicID . $whereExtra,
									  'order'	    => $sortKey,
									  'limit'	    => array( $oStart, $oEnd ),
									  'add_join'	=> array(
															array( 'select' => 'iu.*',
															   	   'from'   => array( 'ignored_users' => 'iu' ),
																   'where'  => 'iu.ignore_owner_id=' . $readingMemberID . ' AND iu.ignore_ignore_id=msg.msg_author_id',
																   'type'   => 'left' ),
															array( 'select' => 'm.member_group_id, m.mgroup_others',
															   	   'from'   => array( 'members' => 'm' ),
																   'where'  => 'm.member_id=msg.msg_author_id',
																   'type'   => 'left' ),
															) 
							)		);
 			$o = $this->DB->execute();

	 		//-----------------------------------------
	 		// Get the messages
	 		//-----------------------------------------

	 		while( $msg = $this->DB->fetch( $o ) )
 			{
				$msg['_ip_address'] = "";
				
				/* IP Address */
				if ( $msg['msg_ip_address'] AND $readingMemberData['g_is_supmod'] == 1 )
				{
					$msg['_ip_address'] = $msg['msg_ip_address'];
				}
				
				/* Edit */
				$msg['_canEdit']   = $this->_conversationCanEdit( $msg, $topicData, $readingMemberData );
				
				/* Delete */
				$msg['_canDelete'] = $this->_conversationCanDelete( $msg, $topicData, $readingMemberData );
				
				/* Format Message */
				$msg['msg_post'] = $this->_formatMessageForDisplay( $msg['msg_post'], $msg );
			
				/* Member missing? */
				if ( ! isset($memberData[ $msg['msg_author_id'] ]) )
				{
					$missingMembers[ $msg['msg_author_id'] ] = $msg['msg_author_id'];
				}
				
				$replyData[ $msg['msg_id'] ] = $msg;
			}
		}
		
		/* Members who've deleted a closed conversation? */
		if ( count( $missingMembers ) )
		{
			$_members = IPSMember::load( array_keys( $missingMembers ), 'all' );
			
			foreach( $_members as $id => $data )
			{
				$data['_canBeBlocked']   = IPSMember::isIgnorable( $memberData[ $topicData['mt_starter_id'] ]['member_group_id'], $memberData[ $topicData['mt_starter_id'] ]['mgroup_others'] );
				$data['map_user_active'] = 0;
				$memberData[ $data['member_id'] ] = IPSMember::buildDisplayData( $data, array( '__all__' => 1 ) );
			}
		}
		
		/* Update reading member's read time */
		$this->DB->update( 'message_topic_user_map', array( 'map_read_time' => time(), 'map_has_unread' => 0 ), 'map_user_id=' . intval($readingMemberData['member_id']) . ' AND map_topic_id=' . $topicID );
		
		/* Reduce the number of 'new' messages */
		$_newMsgs = intval( $this->getPersonalTopicsCount( $readingMemberID, 'new') );
		
		if ( $memberData[ $readingMemberID ]['map_has_unread'] )
		{
			$_pc = $this->rebuildFolderCount( $readingMemberID, array( 'new' => $_newMsgs ), TRUE );
			IPSMember::save( $readingMemberID, array( 'core' => array( 'msg_count_new' => $_newMsgs ) ) );
			
			/* is this us? */
			if ( $readingMemberID == $this->memberData['member_id'] )
			{
				/* Reset folder data */
				$this->_dirData = $this->explodeFolderData( $_pc );
				
				/* Reset global new count */
				$this->memberData['msg_count_new'] = $_newMsgs;
			}
 		}

		/* Clean up topic title */
		$topicData['mt_title'] = str_replace( '[attachmentid=', '&#91;attachmentid=', $topicData['mt_title'] );
		
		/* Ensure our read time is updated */
		$memberData[ $readingMemberID ]['map_read_time'] = time();
		
		/* Do we  have a deleted user? */
		if ( isset( $memberData[0] ) AND $memberData[0]['member_id'] == 0 )
		{
			$memberData[0] = IPSMember::buildDisplayData(IPSMember::setUpGuest( "Deleted Member" ), array( '__all__' => 1 ) );
		}
		
		//-----------------------------------------
		// Attachments?
		//-----------------------------------------

		if ( $topicData['mt_hasattach'] )
		{
			//-----------------------------------------
			// INIT. Yes it is
			//-----------------------------------------

			$postHTML = array();

			//-----------------------------------------
			// Separate out post content
			//-----------------------------------------

			foreach( $replyData as $id => $post )
			{
				$postHTML[ $id ] = $post['msg_post'];
			}
			
			if ( ! is_object( $this->class_attach ) )
			{
				require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
				$this->class_attach = new class_attach( $this->registry );
			}
		
			$this->class_attach->type  = 'msg';
			$this->class_attach->init();
		
			$attachHTML = $this->class_attach->renderAttachments( $postHTML );
			
			/* Now parse back in the rendered posts */
			foreach( $attachHTML as $id => $data )
			{
				/* Get rid of any lingering attachment tags */
				if ( stristr( $data['html'], "[attachment=" ) )
				{
					$data['html'] = IPSText::stripAttachTag( $data['html'] );
				}

				$replyData[ $id ]['msg_post']       = $data['html'];
				$replyData[ $id ]['attachmentHtml'] = $data['attachmentHtml'];
			}
		}
		
		/* Return */
		return array( 'topicData' => $topicData, 'replyData' => $replyData, 'memberData' => $memberData );
	}
	
	/**
	 * "Implode" folder string
	 *
	 * @access	public
	 * @param	array 		Array indexed by 'id' => [ 'id' => string, 'real' => string, 'count' => int ]
	 * @return	string		Formed folder string
	 */
	public function implodeFolderData( $folderArray )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = array();
		
		if ( ! is_array( $folderArray ) )
		{
			return serialize( $this->_fetchDefaultFolders() );
		}
		else
		{
			return serialize( $folderArray );
		}
	}
	
	/**
	 * "Explodes" folder string
	 *
	 * @access	public
	 * @param	string		Raw folder data
	 * @return	array 		Array indexed by 'id' => [ 'id' => string, 'real' => string, 'count' => int ]
	 */
	public function explodeFolderData( $folderData )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$folderArray = array();
		
		//-----------------------------------------
		// Set  up default pconversation_filters
		//-----------------------------------------
		
		if ( ! $folderData )
		{
			$folderData = $this->_fetchDefaultFolders();
		}
		else
		{
			$folderData = unserialize( $folderData );
		}
		
		//-----------------------------------------
		// Process...
		//-----------------------------------------
		
		foreach( $folderData as $id => $data )
		{
			/* Language key exists? */
			if ( isset( $this->lang->words[ 'msgFolder_' . $data['id'] ] ) )
			{
				$folderData[ $id ]['real'] = $this->lang->words[ 'msgFolder_' . $data['id'] ];
			}
		}
	
		return $folderData;
	}
	
	/**
	 * Have we been blocked?
	 *
	 * @access	public
	 * @param	array 			Person A: Array of member data (core + groups required) OR member ID
	 * @param	array 			[Array of] member IDs to check 'person A' has been blocked by them. Array of member data (core + groups required) OR member ID OR array of IDs
	 * @return	array
	 */
	public function blockedByUser( $fromMember, $toMember )
	{
		$fromMember = ( is_array( $fromMember ) ) ? $fromMember : IPSMember::load( intval( $fromMember ), 'groups,extendedProfile' );
		$toMembers  = array();
		$blockedBy  = array();
		
		if ( is_array( $toMember ) AND ( $toMember['member_id'] != '' ) )
		{
			$toMembers = array( $toMember['member_id'] => $toMember );
		}
		else if ( is_array( $toMember ) AND count( $toMember ) )
		{
			$toMembers = IPSMember::load( $toMember, 'groups,extendedProfile' );
		}
		else
		{
		   $toMembers = array( $toMember => IPSMember::load( intval( $toMember ), 'groups,extendedProfile' ) );
		}
		
		$justToIDs = array_keys( $toMembers );

		//-----------------------------------------
		// Can anyone block us?
		//-----------------------------------------
		
		if ( $fromMember['_canBeIgnored'] !== TRUE )
		{
			return $blockedBy;
	 	}
	
		//-----------------------------------------
		// Grab the data from the ignored table
		//-----------------------------------------
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'ignored_users',
							     'where'  => 'ignore_messages=1 AND ignore_owner_id IN ( ' . implode( ',', $justToIDs ) . ' ) AND ignore_ignore_id=' . $fromMember['member_id'] ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( in_array( $row['ignore_owner_id'], $justToIDs ) )
			{
				$blockedBy[ $row['ignore_owner_id'] ] = $row['ignore_owner_id'];
			}
		}

		return $blockedBy;
	}
	
	/**
	 * Can we use the PM system?
	 *
	 * @access	public
	 * @param	array 			Array of member data (core + groups required) OR member ID
	 * @return	boolean			TRUE is OK
	 */
	public function canUsePMSystem( $toMember )
	{
		$toMember = ( is_array( $toMember ) ) ? $toMember : IPSMember::load( intval( $toMember ), 'groups,extendedProfile' );
		
		/* Not using PM system? */
		if ( $toMember['members_disable_pm'] )
		{
			return FALSE;
		}
		
		if ( $toMember['mgroup_others'] && ! $toMember['g_use_pm'] )
		{
			$groups_id = explode( ',', $toMember['mgroup_others'] );
            $cache     = $this->caches['group_cache'];

			if ( count( $groups_id ) )
			{
				foreach( $groups_id as $pid )
				{
					if ( ! $cache[ $pid ]['g_id'] )
					{
						continue;
					}
                    
					if ( $cache[ $pid ]['g_use_pm'] )
					{
						$toMember['g_use_pm'] = 1;
						break;
					}
				}
			}
		}			
	
		return ( $toMember['g_use_pm'] != 1 ) ? FALSE : TRUE;
	}
	
	/**
	 * Get invite users
	 * Does this PM have any invite users attached? Mainly used to detect IPB 3.0.0 methods
	 * over previous methods
	 *
	 * @access	public
	 * @param	string		Raw data from DB (msg_cc_users)
	 * @return	array 		Array of IDs
	 */
	public function getInvitedUsers( $msg_invite_users )
	{
		if ( ! $msg_invite_users )
		{
			return array();
		}
		
		$_users = ( is_array( $msg_invite_users ) ) ? $msg_invite_users : unserialize( $msg_invite_users );
		
		if ( ! count( $_users ) )
		{
			return array();
		}
		
		if ( intval( $_users[0] ) == $_users[0] )
		{
			return $_users;
		}
		else
		{
			# Assume they're names, then...
			$members = IPSMember::load( $_users, 'core', 'displayname' );
			
			# Just return the IDs
			return array_keys( $members );
		}
	}
	
	/**
	 * Have we exceeded our PM quota?
	 *
	 * @access	public
	 * @param	array 			Array of member data (core + groups required) OR member ID
	 * @return	boolean			TRUE is OK (within quota)
	 */
	public function withinPMQuota( $member )
	{
		$member = ( is_array( $member ) ) ? $member : IPSMember::load( intval( $member ), 'groups,extendedProfile' );
		$member = $this->_setMaxMessages( $member );
		
		if ( $member['g_max_messages'] > 0 AND ( $member['msg_count_total'] + 1 > $member['g_max_messages'] ) )
		{
			if ( $this->settings['override_inbox_full'] )
	 		{
		 		$override    = array();
		 		$override    = explode( ",", $this->settings['override_inbox_full'] );
		 		$do_override = 0;

		 		$my_groups = array( $this->memberData['member_group_id'] );
	 		
		 		if ( $this->memberData['mgroup_others'] )
		 		{
			 		$my_groups = array_merge( $my_groups, explode( ",", $this->memberData['mgroup_others'] ) );
		 		}

		 		foreach( $my_groups as $member_group )
		 		{
			 		if ( in_array( $member_group, $override ) )
			 		{
				 		$do_override = 1;
			 		}
			 	}

			 	if ( $do_override == 0 )
			 	{
					return FALSE;
			 	}
				else
				{
					return TRUE;
				}
	 		}
	 		else
	 		{
				return FALSE;
			}
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Returns the max PMs allowed based on primary and seconday groups
	 *
	 * @access	private
	 * @param	array 	Array of member data
	 * @return	array 	Member data
	 */
	private function _setMaxMessages( $member )
 	{
		$groups_id  = explode( ',', $member['mgroup_others'] );
 		$groupCache = $this->caches['group_cache'];

 		if ( count( $groups_id ) )
		{
			foreach( $groups_id as $pid )
			{
				if ( ! isset($groupCache[ $pid ]['g_id']) OR ! $groupCache[ $pid ]['g_id'] )
				{
					continue;
				}
				
				if ( $member['g_max_messages'] > 0 AND $groupCache[ $pid ]['g_max_messages'] > $member['g_max_messages'] )
				{
					$member['g_max_messages'] = $groupCache[ $pid ]['g_max_messages'];
				}
				else if ( $groupCache[ $pid ]['g_max_messages'] == 0 )
				{
					$member['g_max_messages'] = 0;
				}
			}
		}
		
		return $member;
 	}
	
	/**
	 * Builds the data for the messenger storage block
	 *
	 * @access	public
	 * @return	array
	 */
	public function buildMessageTotals()
	{
		/* Get the number of messages we have in total. */
 		$total = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as msg_total', 
 												  'from'   => 'message_topic_user_map', 
 												  'where'  => "map_user_active=1 AND map_is_system=0 AND map_user_banned=0 AND map_user_id=" . $this->memberData['member_id'] ) );
 		
 		$totals['msg_total'] = intval( $total['msg_total'] );
 		
		/* if we're not in myconvo with a filter */
		if ( ! $this->_folderFilter )
		{
 			/* Update the message count if needed */
	 		if ( $totals['msg_total'] != $this->memberData['msg_count_total'] )
	 		{
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'msg_count_total' => $totals['msg_total'] ) ) );
	 		}		
		}
	
		/* Make sure we've not exceeded our alloted allowance. */
 		$info['full_messenger'] = "<br />";
 		$info['img_width']      = 1;
 		$info['vid']            = $this->_currentFolderID;
 		$info['full_percent']   = '';
 		$info['amount_info']    = sprintf( $this->lang->words['pmpc_info_string'], $total['msg_total'] ,$this->lang->words['pmpc_unlimited'] );
 		
 		if ( $this->memberData['g_max_messages'] > 0 )
 		{
 			$totals['amount_info']  = sprintf( $this->lang->words['pmpc_info_string'], $total['msg_total'] ,$this->memberData['g_max_messages'] );
 			
 			$totals['full_percent'] = $total['msg_total'] ? sprintf( "%.0f", ( ($total['msg_total'] / $this->memberData['g_max_messages']) * 100) ) : 0;
			$totals['full_percent'] = ( $totals['full_percent'] > 100 ) ? 100 : $totals['full_percent'];
 			$totals['img_width']    = isset( $info['full_percent'] ) && $info['full_percent'] > 0 ? intval($info['full_percent']) * 2.4 : 1;
 			
 			if ($totals['img_width'] > 300)
 			{
 				$totals['img_width'] = 300;
 			}
 			
 			if ($totals['msg_total'] >= $this->memberData['g_max_messages'])
 			{
 				$totals['full_messenger'] = "<span class='highlight'>".$this->lang->words['c_msg_full']."</span>";
 			}
 			else
 			{
 				$totals['full_messenger'] = str_replace( "<#PERCENT#>", $info['full_percent'], $this->lang->words['pmpc_full_string'] );
 			}
 		}

		return $totals;
	}
	
	/**
	 * Test to see whether or not we are allowed in this PM topic
	 *
	 * @access	public
	 * @param	int			Member ID to test against
	 * @param	mixed		Topic ID or array of topic data
	 * @param	array 		[ topic participants. If omitted, participants are loaded ]
	 * @param	boolean		Do not strip non-active users
	 * @return	boolean
	 */
	public function canAccessTopic( $memberID, $topicData, $topicParticipants=NULL, $noStrip=FALSE )
	{
		$topicData         = ( is_array( $topicData ) ) ? $topicData : $this->fetchTopicData( $topicData );
		$topicParticipants = ( is_array( $topicParticipants ) ) ? $topicParticipants : $this->fetchTopicParticipants( $topicData['mt_id'] );
		
		/* Is it deleted? Check this before stripped map_is_active=0 users */
		if ( $topicData['mt_is_deleted'] AND isset($topicParticipants[ $memberID ]) )
		{
			return TRUE;
		}
		
		/* Is it a system PM? */
		if ( $topicData['mt_is_system'] AND $topicData['mt_starter_id'] == $memberID )
		{
			return FALSE;
		}
	
		if ( defined('FROM_REPORT_CENTER') AND FROM_REPORT_CENTER )
 		{
 			return TRUE;
 		}
		
		/* Now remove all 'non-active' participants */
		$topicParticipants = ( $noStrip === FALSE ) ? $this->_stripNonActiveParticipants( $topicParticipants ) : $topicParticipants;
		
		/* Are we in the participants list? */
		if ( isset($topicParticipants[ $memberID ]) )
		{
			if ( $topicParticipants[ $memberID ]['map_user_banned'] )
			{
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Determines whether the conversation can be replied to
	 *
	 * @access	public
	 * @param	int			Member ID to test against
	 * @param	mixed		Topic ID or array of topic data
	 * @param	array 		[ topic participants. If omitted, participants are loaded ]
	 * @return	boolean
	 */
	public function canReplyTopic( $memberID, $topicData, $topicParticipants=NULL )
	{
		$topicData         = ( is_array( $topicData ) ) ? $topicData : $this->fetchTopicData( $topicData );
		$topicParticipants = ( is_array( $topicParticipants ) ) ? $topicParticipants : $this->fetchTopicParticipants( $topicData['mt_id'] );
		
		if ( $this->canAccessTopic( $memberID, $topicData, $topicParticipants ) !== TRUE )
		{
			return FALSE;
		}
		
		/* Is it a system PM? */
		if ( $topicData['mt_is_system'] )
		{
			return FALSE;
		}
		
		/* Is it a system PM? */
		if ( $topicData['mt_is_deleted'] )
		{
			return FALSE;
		}
		
		/* Ok, then */
		return TRUE;
	}
	
	/**
	 * Find the previous PID
	 *
	 * @access	public
	 * @param	int		Msg ID
	 * @return	int		Previous Msg ID
	 */
	public function fetchPreviousMsgID( $msgID )
	{
		$prevID = $this->DB->buildAndFetch( array( 'select' => 'MAX(msg_id) as max',
										 		   'from'   => 'message_posts',
										 		   'where'  => 'msg_id < ' . $msgID ) );
		return intval( $prevID['max'] );
	}
	
	/**
	 * Fetch Message Data
	 *
	 * @access	public
	 * @param	int			Topic ID
	 * @param	int			Msg ID
	 * @param	boolean		Load starter member data with it
	 * @access	array 		Message data
	 */
	public function fetchMessageData( $topicID, $msgID, $loadMember=FALSE )
	{ 
		$msgData   = $this->DB->buildAndFetch( array( 'select' => '*',
										 			  'from'   => 'message_posts',
										 			  'where'  => 'msg_topic_id=' . intval( $topicID ) . ' AND msg_id=' . intval( $msgID ) ) );
		
		if ( $loadMember AND $msgData['msg_author_id'] )
		{
			$memberData                  = IPSMember::load( $msgData['msg_author_id'], 'all' );
			$memberData['_canBeBlocked'] = IPSMember::isIgnorable( $memberData['member_group_id'], $memberData['mgroup_others'] );
			$memberData                  = IPSMember::buildDisplayData( $memberData, array( '__all__' => 1 ) );
			$msgData                     = array_merge( $msgData, $memberData );
		}
		
		return $msgData;
	}
	
	/**
	 * Fetch topic Data with first message information
	 *
	 * @access	public
	 * @param	int			Topic ID
	 * @param	boolean		Load starter member data with it
	 * @return	array 		Topic + message data
	 */
	public function fetchTopicDataWithMessage( $topicID, $loadMember=FALSE )
	{
		$topicData = $this->DB->buildAndFetch( array( 'select' => 'mt.*',
										 			  'from'   => array( 'message_topics' => 'mt' ),
										 			  'where'  => 'mt.mt_id=' . intval( $topicID ),
													  'add_join' => array( array( 'select' => 'm.*',
																				  'from'   => array( 'message_posts' => 'm' ),
																				  'where'  => 'm.msg_id=mt.mt_first_msg_id',
																				  'type'   => 'inner' ) ) ) );
		
		if ( $loadMember )
		{
			$memberData                  = IPSMember::load( $topicData['mt_starter_id'], 'all' );
			$memberData['_canBeBlocked'] = IPSMember::isIgnorable( $memberData['member_group_id'], $memberData['mgroup_others'] );
			$memberData                  = IPSMember::buildDisplayData( $memberData, array( '__all__' => 1 ) );
			$topicData = array_merge( $topicData, $memberData );
		}
		
		return $topicData;
	}
	

	/**
	 * Fetch topic Data
	 *
	 * @access	public
	 * @param	int			Topic ID
	 * @param	boolean		Load starter member data with it
	 * @return	array 		Topic data
	 */
	public function fetchTopicData( $topicID, $loadMember=FALSE )
	{
		$topicData = $this->DB->buildAndFetch( array( 'select' => '*',
										 			  'from'   => 'message_topics',
										 			  'where'  => 'mt_id=' . intval( $topicID ) ) );
		
		if ( $loadMember )
		{
			$memberData                  = IPSMember::load( $topicData['mt_starter_id'], 'all' );
			$memberData['_canBeBlocked'] = IPSMember::isIgnorable( $memberData['member_group_id'], $memberData['mgroup_others'] );
			$memberData                  = IPSMember::buildDisplayData( $memberData, array( '__all__' => 1 ) );
			$topicData = array_merge( $topicData, $memberData );
		}
		
		return $topicData;
	}
	
	/**
	 * Fetch the topic participants
	 *
	 * @access	public
	 * @param	int			Topic ID
	 * @param	boolean		Load and parse member data (TRUE for yes, FALSE for no)
	 * @return	array 		Array of member data indexed by member ID
	 */
	public function fetchTopicParticipants( $topicID, $parseThem=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$memberData = array();
		$remapData	= array();
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'message_topic_user_map',
								 'where'  => 'map_topic_id=' . intval( $topicID ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$remapData[ $row['map_user_id'] ] = $row;
		}
		
		if( !count($remapData) )
		{
			return array();
		}
		
		/* Parse 'em? */
		if ( $parseThem === TRUE )
		{
			/* Grab member data */
			$memberData = IPSMember::load( array_keys( $remapData ), 'all' );

			foreach( $memberData as $id => $data )
			{
				$data['_canBeBlocked'] = IPSMember::isIgnorable( $data['member_group_id'], $data['mgroup_others'] );
				$memberData[ $id ]     = IPSMember::buildDisplayData( $data, array( '__all__' => 1 ) );
				$memberData[ $id ]     = array_merge( $memberData[ $id ], $remapData[ $id ] );
			}
			
			$remapData = $memberData;
		}
		
		return $remapData;
	}
	
	/**
	 * Strip away non-active participants
	 *
	 * @access	private
	 * @param	array 		Array of current topic participants (as returned by fetchTopicParticipants)
	 * @return	array
	 */
	private function _stripNonActiveParticipants( $topicParticipants )
	{
		$_participants = array();
		
		if ( ! is_array( $topicParticipants ) )
		{
			return array();
		}
		
		foreach( $topicParticipants as $id => $data )
		{
			if ( $data['map_user_active'] )
			{
				$_participants[ $id ] = $data;
			}
		}
		
		return $_participants;
	}

	/**
	 * Function to format the actual message (applies BBcode, etc)
	 *
	 * @access	private
	 * @param	string		Raw text
	 * @param	array 		PM data
	 * @return	string		Processed text
	 */
	private function _formatMessageForDisplay( $msgContent, $data=array() )
	{
		//-----------------------------------------
		// Reset Classes
		//-----------------------------------------
		
		IPSText::resetTextClass('bbcode');
		
		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
		
		$this->settings[ 'max_emos'] =  0 ;

 		IPSText::getTextClass('bbcode')->parse_smilies				= 1;
 		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
 		IPSText::getTextClass('bbcode')->parse_html					= $this->settings['msg_allow_html'];
 		IPSText::getTextClass('bbcode')->parse_bbcode				= $this->settings['msg_allow_code'];
 		IPSText::getTextClass('bbcode')->parsing_section			= 'pms';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $data['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $data['mgroup_others'];
 		
 		$msgContent = IPSText::getTextClass('bbcode')->preDisplayParse( $msgContent );
 	
 		if ( IPSText::getTextClass('bbcode')->error != "" )
 		{
	 		//throw new Exception( "BBCODE_" . IPSText::getTextClass('bbcode')->error );
 		}

		return $msgContent;
	}
	
	/**
	 * Format Post: Converts BBCode, smilies, etc
	 *
	 * @access	public
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	public function _formatMessageForSaving( $msgContent )
	{
		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
	
		if ( isset($_POST['_fastReplyUsed']) AND $_POST['_fastReplyUsed'] )
		{
			if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
			{
				//-----------------------------------------
				// Fast reply used.. and we've chosen the RTE
				// Convert STD to RTE first...
				//-----------------------------------------
			
				$msgContent = IPSText::getTextClass( 'bbcode' )->convertStdToRte( $msgContent );
			}
		}

		$msgContent = IPSText::getTextClass( 'editor' )->processRawPost( $msgContent );
		
		//-----------------------------------------
		// Parse post
		//-----------------------------------------

		IPSText::getTextClass('bbcode')->parse_smilies	 = 1;
 		IPSText::getTextClass('bbcode')->parse_nl2br   	 = 1;
 		IPSText::getTextClass('bbcode')->parse_html    	 = $this->settings['msg_allow_html'];
 		IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['msg_allow_code'];
 		IPSText::getTextClass('bbcode')->parsing_section = 'pms';
		
		$msgContent = IPSText::getTextClass( 'bbcode' )->preDbParse( $msgContent );
		
		if ( IPSText::getTextClass('bbcode')->error != "" )
 		{
	 		throw new Exception( "BBCODE_" . IPSText::getTextClass('bbcode')->error );
 		}

		return $msgContent;
	}
	
	/**
	 * Determines whether the message can be edited
	 *
	 * @access	private
	 * @param	array 		Message data
	 * @param	array 		Topic data
	 * @param	array 		Reading member data
	 * @return	bool
	 */
	private function _conversationCanEdit( $msg, $topicData, $readingMemberData )
	{
		if ( $topicData['mt_is_deleted'] )
		{
			return FALSE;
		}
		
		/* Is it a system PM? */
		if ( $topicData['mt_is_system'] )
		{
			return FALSE;
		}
		
		if ( ( $msg['msg_author_id'] == $readingMemberData['member_id'] ) OR ( $readingMemberData['g_is_supmod'] == 1 ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Determines whether the message can be deleted
	 *
	 * @access	private
	 * @param	array 		Message data
	 * @param	array 		Topic data
	 * @param	array 		Reading member data
	 * @return	bool
	 */
	private function _conversationCanDelete( $msg, $topicData, $readingMemberData )
	{
		if ( $topicData['mt_is_deleted'] )
		{
			return FALSE;
		}
		
		if ( ( $msg['msg_author_id'] == $readingMemberData['member_id'] ) OR ( $readingMemberData['g_is_supmod'] == 1 ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Flag members for a PC count reset
	 *
	 * @access	private
	 * @param	array 		Array of MEMBER ids
	 * @return	boolean
	 */
	private function _flagForCountReset( $members )
	{
		if ( count( $members ) )
		{
			/* OK, so this is a bit naughty and should really go via IPSMember::save()
			   however, it would take a fair bit of rewriting to 'fix it' so that it could
			   save out with multiple IDs. So.... */
			$this->DB->update( 'members', array( 'msg_count_reset' => 1 ), 'member_id IN (' . implode( ',', array_keys( $members ) ) . ')' );
		}
		
		return TRUE;
	}
	
	/**
	 * Makes attachments permananent
	 *
	 * @access	private
	 * @param	string		Post Key
	 * @param	int			Msg ID
	 * @param	int			Topic ID
	 * @return	int
	 */
	private function _makeAttachmentsPermanent( $postKey, $msgID, $topicID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Attachments: Re-affirm...
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach                  =  new class_attach( $this->registry );
		$class_attach->type            =  'msg';
		$class_attach->attach_post_key =  $postKey;
		$class_attach->attach_rel_id   =  $msgID;
		$class_attach->init();
		
		$return = $class_attach->postProcessUpload( array( 'mt_id' => $topicID ) );

		return intval( $return['count'] );
	}
	
	/**
	 * Default folder for people won't don't have any
	 *
	 * @access	private
	 * @return	array
	 */
	private function _fetchDefaultFolders()
	{
		return array( 'new'   => array(   'id'        => 'new',
									      'real'      => $this->lang->words['msgFolder_new'],
									   	  'count'     => 0,
									   	  'protected' => 1 ),
					  'myconvo' => array( 'id'        => 'myconvo',
									      'real'      => $this->lang->words['msgFolder_myconvo'],
									      'count'     => 0,
									      'protected' => 1 ),
					  'drafts'  => array( 'id'        => 'drafts',
										  'real'      => $this->lang->words['msgFolder_drafts'],
										  'count'     => 0,
										  'protected' => 1 ) );
	}
}