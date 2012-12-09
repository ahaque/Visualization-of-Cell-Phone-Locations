<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Online list
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		12th March 2002
 * @version		$Revision: 5066 $
 *
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
} 

class public_members_messaging_view extends ipsCommand
{
	/**
	 * Page title
	 *
	 * @access	private
	 * @var		string
	 */
	private $_title;
	
	/**
	 * Navigation
	 *
	 * @access	private
	 * @var		array[ 0 => [ title, url ] ]
	 */
	private $_navigation;
	
	/**
	 * Folder totals
	 *
	 * @access	private
	 * @var		mixed
	 */
	private $_totals;
	
	/**
	 * Contains topic participant data
	 *
	 * @access	public
	 * @var		array
	 */
	public $_topicParticipants;
	
	/**
	 * Messenger library
	 *
	 * @access	public
	 * @var		object
	 */
	public $messengerFunctions;
	
	/**
	 * Error string
	 *
	 * @access	public
	 * @var		string
	 */
	public $_errorString = '';
	
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
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10226 );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10227 );
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_no_guests', 10228 );
		}
		
		if( ! IPSLib::moduleIsEnabled( 'messaging', 'members' ) )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10227 );
		}

    	//-----------------------------------------
    	// Language
    	//-----------------------------------------
    	
    	$this->registry->class_localization->loadLanguageFile( array( "public_editors" ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
    	
		//-----------------------------------------
		// Grab class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
		$this->messengerFunctions = new messengerFunctions( $registry );
		
		/* Messenger Totals */
		$this->_totals = $this->messengerFunctions->buildMessageTotals();

		/* Filtah */
		if ( $this->request['folderFilter'] )
		{
			$this->messengerFunctions->addFolderFilter( $this->request['folderFilter'] );
		}
		
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
			default:
    		case 'inbox':
    		case 'showFolder':
    			$html = $this->_showFolder();
    		break;
			case 'showConversation':
			case 'showMessage':
				$html = $this->showConversation();
			break;
			case 'multiFile':
				$html = $this->_multiFile();
			break;
			case 'findMessage':
				$html = $this->_findMessage();
			break;
			case 'addParticipants':
				$html = $this->_addParticipants();
			break;
			/*case 'leaveConversation':
				$html = $this->_leaveConversation();
			break;
			case 'rejoinConversation':
				$html = $this->_rejoinConversation();
			break;*/
			case 'deleteConversation':
				$html = $this->_deleteConversation();
			break;
			case 'blockParticipant':
				$html = $this->_blockParticipant();
			break;
			case 'unblockParticipant':
				$html = $this->_unblockParticipant();
			break;
			case 'toggleNotifications':
				$html = $this->_toggleNotifications();
			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->registry->output->addContent( $this->registry->getClass('output')->getTemplate('messaging')->messengerTemplate( $html, $this->messengerFunctions->_jumpMenu, $this->messengerFunctions->_dirData, $this->_totals, $this->_topicParticipants, $this->_errorString, $this->_deletedTopic ) );
    	$this->registry->output->setTitle( $this->_title );
		
		$this->registry->output->addNavigation( $this->lang->words['messenger__nav'], 'app=members&amp;module=messaging' );
		
		if ( is_array( $this->_navigation ) AND count( $this->_navigation ) )
		{
			foreach( $this->_navigation as $idx => $data )
			{
				$this->registry->output->addNavigation( $data[0], $data[1] );
			}
    	}

        $this->registry->output->sendOutput();
 	}
	
	
	/**
	 * Block a participant
	 *
	 * @access 	private
	 * @return	mixed	void, or HTML
	 */
	private function _toggleNotifications()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$authKey     = $this->request['authKey'];
		$topicID	 = intval( $this->request['topicID'] );

		//-----------------------------------------
		// Auth check
		//-----------------------------------------

		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024 );
		}

		//-----------------------------------------
		// Do it
		//-----------------------------------------

		try
		{
			$this->messengerFunctions->toggleNotificationStatus( $this->memberData['member_id'], array( $topicID ), 'toggle' );

			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=' . $topicID );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			$this->_errorString = $msg;

			return $this->showConversation();
		}
	}
		
	/**
	 * Block a participant
	 *
	 * @access 	private
	 * @return	mixed	void, or HTML
	 */
	private function _blockParticipant()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey     = $this->request['authKey'];
		$memberID    = intval( $this->request['memberID'] );
		$topicID	 = intval( $this->request['topicID'] );
		
		//-----------------------------------------
		// Auth check
		//-----------------------------------------
		
		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024 );
		}
		
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		try
		{
			$this->messengerFunctions->toggleTopicBlock( $memberID, $this->memberData['member_id'], $topicID, TRUE );
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=' . $topicID );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			$this->_errorString = $msg;
			
			return $this->showConversation();
		}
	}
	
	/**
	 * Leave a conversation (non topic starter)
	 *
	 * @access 	private
	 * @return	mixed	void, or HTML
	 */
	private function _unblockParticipant()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey     = $this->request['authKey'];
		$memberID    = intval( $this->request['memberID'] );
		$topicID	 = intval( $this->request['topicID'] );
		
		//-----------------------------------------
		// Auth check
		//-----------------------------------------
		
		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024 );
		}
		
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		try
		{
			$this->messengerFunctions->toggleTopicBlock( $memberID, $this->memberData['member_id'], $topicID, FALSE );
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=' . $topicID );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			$this->_errorString = $msg;
			
			return $this->showConversation();
		}
	}
	
	/**
	 * Leave a conversation (non topic starter)
	 *
	 * @access 	private
	 * @return	mixed	void, or HTML
	 */
	private function _deleteConversation()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey     = $this->request['authKey'];
		$topicID	 = intval( $this->request['topicID'] );
		
		//-----------------------------------------
		// Auth check
		//-----------------------------------------
		
		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024 );
		}
		
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		try
		{
			$this->messengerFunctions->deleteTopics( $this->memberData['member_id'], array( $topicID )  );
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging' );
			
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			$this->_errorString = $msg;
			
			return $this->showConversation();
		}
	}
	
	/**
	 * Deletes a reply
	 *
	 * @access 	private
	 * @return	mixed	void, or HTML
	 */
	private function _addParticipants()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey     = $this->request['authKey'];
		$topicID	 = intval( $this->request['topicID'] );
 		$inviteUsers = array();
		$start		 = intval( $this->request['st'] );
		
		//-----------------------------------------
		// Auth check
		//-----------------------------------------
		
		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024 );
		}
		
		//-----------------------------------------
		// Invite Users
		//-----------------------------------------
		
		if ( $this->memberData['g_max_mass_pm'] AND $this->request['inviteNames'] )
		{
			foreach( explode( ",", $this->request['inviteNames'] ) as $id => $name )
			{
				$name = trim( $name );
				
				if ( $name )
				{
					$inviteUsers[] = $name;
				}
			}
		}
		
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		try
		{
			$this->messengerFunctions->addTopicParticipants( $topicID, $inviteUsers, $this->memberData['member_id'] );
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=' . $topicID . '&amp;st=' . $start );
			
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			$msg = $error->getMessage();
			
			if ( isset($this->lang->words[ 'err_' . $msg ]) )
			{
				$_msgString = $this->lang->words[ 'err_' . $msg ];
				$_msgString = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $_msgString );
				$_msgString = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $_msgString );
				$_msgString = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $_msgString );
			}
			else
			{
				$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
			}
			
			$this->_errorString = $_msgString;
			
			return $this->showConversation();
		}
	}
	
	/**
	 * Redirects the user to the correct page in a conversation based on the incoming msg ID
	 *
	 * @access	private	
	 * @return	string		returns HTML
	 */
	private function _findMessage()
	{
		$msgID   = ( $this->request['msgID'] == '__firstUnread__' ) ? '__firstUnread__' : intval( $this->request['msgID'] );
		$topicID = intval( $this->request['topicID'] );
		
		/* Fetch topic data */
		$topicData   = $this->messengerFunctions->fetchTopicData( $topicID );
		
		/* Figure out the MSG id */
		if ( $msgID == '__firstUnread__' )
		{
			/* Grab mah 'pants */
			$participants = $this->messengerFunctions->fetchTopicParticipants( $topicID );
			
			if ( $participants[ $this->memberData['member_id'] ] )
			{
				$_msgID = $this->DB->buildAndFetch( array( 'select' => 'msg_id',
														   'from'   => 'message_posts',
														   'where'  => 'msg_topic_id=' . $topicID . ' AND msg_date > ' . intval( $participants[ $this->memberData['member_id'] ]['map_read_time'] ),
														   'order'  => 'msg_date ASC',
														   'limit'  => array( 0, 1 ) ) );
	
				$msgID = $_msgID['msg_id'];
			}
		}
		
		$msgID   = ( $msgID ) ? $msgID : $topicData['mt_last_msg_id'];
		
		/* Figure it out */
		$replies   = $topicData['mt_replies'] + 1;
		$perPage   = $this->messengerFunctions->messagesPerPage;
		$page      = 0;
		
		$_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
												   'from'   => 'message_posts',
												   'where'  => "msg_topic_id=" . $topicID . " AND msg_id <=" . intval( $msgID ) ) );										
		
		
		if ( (($_count['count']) % $perPage) == 0 )
		{
			$pages = ($_count['count']) / $perPage;
		}
		else
		{
			$pages = ceil( ( ( $_count['count'] ) / $perPage ) );
		}
		
		$st = ($pages - 1) * $perPage;
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=' . $topicID . '&amp;st=' . $st . '#msg' . $msgID );
	}
	
	/**
	 * Multi Files Messages
	 *
	 * @access	private
	 * @return	string		returns HTML
	 */
	private function _multiFile()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$method    = $this->request['method'];
		$folderID  = $this->request['folderID'];
		$cfolderID = $this->request['cFolderID'];
		$sort      = $this->request['sort'];
		$start     = intval( $this->request['st'] );
		$ids       = array();
		
		//-----------------------------------------
		// Auth OK?
		//-----------------------------------------
		
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->messengerFunctions->_currentFolderID = $cfolderID;
			return $this->_showFolder( $this->lang->words['err_auth'] );
		}
		
		//-----------------------------------------
		// Grab IDs
		//-----------------------------------------
		
		if ( is_array( $_POST['msgid'] ) )
		{
			foreach( $_POST['msgid'] as $id => $value )
			{
				$id = intval( $id );
				$ids[ $id ] = $id;
			}
		}

		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		try
		{
			if ( $method == 'delete' )
			{
				$this->messengerFunctions->deleteTopics( $this->memberData['member_id'], $ids );
			}
			else if ( $method == 'move' )
			{
				$this->messengerFunctions->moveTopics( $this->memberData['member_id'], $ids, $folderID );
			}
			else if ( $method == 'markread' OR $method == 'markunread' )
			{
				$_method = ( $method == 'markread' ) ? TRUE : FALSE;
				
				$this->messengerFunctions->toggleReadStatus( $this->memberData['member_id'], $ids, $_method );
			}
			else if ( $method == 'notifyon' OR $method == 'notifyoff' )
			{
				$_method = ( $method == 'notifyon' ) ? TRUE : FALSE;

				$this->messengerFunctions->toggleNotificationStatus( $this->memberData['member_id'], $ids, $_method );
			}
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=showFolder&amp;folderID=' . $cfolderID . '&amp;sort=' . $sort . '&amp;st=' . $start );
		}
		catch( Exception $error )
		{
			$msg   = $error->getMessage();
			$error = '';

			switch( $msg )
			{
				default:
					$error =  $this->lang->words['err_unspecifed'];
				break;
				case 'NO_IDS_SELECTED':
					$error = $this->lang->words['err_NO_IDS_SELECTED'];
				break;
				/* Move exceptions */
				case 'NO_SUCH_FOLDER':
					$error = $this->lang->words['err_NO_SUCH_FOLDER'];
				break;
				case 'NO_IDS_TO_MOVE':
					$error = $this->lang->words['err_NO_IDS_TO_MOVE'];
				break;
				/* Delete exceptions */
				case 'NO_IDS_TO_DELETE':
					$error = $this->lang->words['err_NO_IDS_TO_DELETE'];
				break;
			}
				
			$this->messengerFunctions->_currentFolderID = $cfolderID;
			return $this->_showFolder( $error );
		}
	}
	
	/**
	 * Show a message
	 *
	 * @access	public
	 * @return	string		returns HTML
	 */
	public function showConversation()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$topicID            = intval( $this->request['topicID'] );
		$start              = intval( $this->request['st'] );
		$end	            = $this->messengerFunctions->messagesPerPage;
		
		//-----------------------------------------
		// Got a message ID?
		//-----------------------------------------
		
		if ( ! $topicID )
 		{
 			$this->registry->getClass('output')->showError( 'messenger_no_msgid', 10225 );
 		}
 		
		//-----------------------------------------
		// Fetch the conversation
		//-----------------------------------------

 		try
		{
			$conversationData = $this->messengerFunctions->fetchConversation( $topicID, $this->memberData['member_id'], array( 'offsetStart' => $start, 'offsetEnd' => $end ) );
 		}
		catch( Exception $error )
		{
			$_msg = $error->getMessage();
			
			if ( $_msg == 'NO_READ_PERMISSION' )
			{
				$this->registry->getClass('output')->showError( 'messenger_no_msgid', 10229 );
			}
			else if ( $_msg == 'YOU_ARE_BANNED' )
			{
				$this->registry->getClass('output')->showError( 'messenger_you_be_banned_yo', 99910229 );
			}
 		}

		//-----------------------------------------
		// Add to the topic data...
		//-----------------------------------------
		
		$conversationData['topicData']['_canReply'] = $this->messengerFunctions->canReplyTopic( $this->memberData['member_id'], $conversationData['topicData'], $conversationData['memberData'] );
 		
		//-----------------------------------------
		// Pages
		//-----------------------------------------

		$conversationData['topicData']['_pages'] = $this->registry->output->generatePagination( array( 'totalItems'         => $conversationData['topicData']['mt_replies'] + 1,
														  						 					   'itemsPerPage'       => $end,
																			  						   'currentStartValue'  => $start,
																			  						   'baseUrl'            => "app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=".$topicID."&amp;sort=".$this->request['sort'] ) );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$html = $this->registry->getClass('output')->getTemplate('messaging')->showConversation( $conversationData['topicData'], $conversationData['replyData'], $conversationData['memberData'], $this->messengerFunctions->_jumpMenu );
		
		/* Sort out topic participants */
		$this->_topicParticipants = $conversationData['memberData'];
		
		/* Deleted topic flag */
		$this->_deletedTopic = $conversationData['topicData']['mt_is_deleted'];
		
		/* Finish off... */
		$_folder      = $conversationData['memberData'][ $this->memberData['member_id'] ]['map_folder_id'];
		$this->messengerFunctions->_currentFolderID = $_folder;
		$this->_title = $conversationData['topicData']['mt_title'];
		$this->_navigation[] = array( $this->messengerFunctions->_dirData[ $_folder ]['real'], "app=members&amp;module=messaging&amp;section=view&amp;do=showFolder&amp;folderID=".$_folder."&amp;sort=".$this->request['sort'] );
		$this->_navigation[] = array( $conversationData['topicData']['mt_title'], '' );
		
		return $html;
	}
	
	/**
	 * Show the folder list
	 *
	 * @access	private
	 * @param	string		Any error text
	 * @return	string		returns HTML
	 */
	private function _showFolder( $error='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
 		$sort     = $this->request['sort'];
		$start    = intval($this->request['st']);
		$p_end    = $this->settings['show_max_msg_list'] > 0 ? $this->settings['show_max_msg_list'] : 50;
		$sort_key = '';
 		
		/* Got an error? */
		if ( $error )
		{
			$this->_errorString = $error;
		}
		
 		//-----------------------------------------
 		// Get the number of messages in our curr folder.
 		//-----------------------------------------
 		
		$totalMsg = $this->messengerFunctions->getPersonalTopicsCount( $this->memberData['member_id'], $this->messengerFunctions->_currentFolderID );
		
		/* Only update if we're not using a filter */
		if ( ( ! $this->request['folderFilter'] ) AND $totalMsg != $this->messengerFunctions->_dirData[ $this->messengerFunctions->_currentFolderID ]['count'] )
	 	{
	 		$this->messengerFunctions->rebuildFolderCount( $this->memberData['member_id'], array( $this->messengerFunctions->_currentFolderID => $totalMsg ) );
	 	}

 		//-----------------------------------------
 		// Generate Pagination
 		//-----------------------------------------

 		if ( $start >= $totalMsg )
 		{
	 		$start = 0;
 		}
 		
		$_baseURL = "app=members&amp;module=messaging&amp;section=view&amp;do=showFolder&amp;folderID=".$this->messengerFunctions->_currentFolderID;
		
		if ( $this->request['sort'] )
		{
			$_baseURL .= "&amp;sort=".$this->request['sort'];
		}
		
		if ( $this->request['folderFilter'] )
		{
			$_baseURL .= "&amp;folderFilter=".$this->request['folderFilter'];
		}
		
 		$pages = $this->registry->getClass('output')->generatePagination( array( 'totalItems'         => $totalMsg,
														  						 'itemsPerPage'       => $p_end,
														  						 'currentStartValue'  => $start,
														  						 'baseUrl'            => $_baseURL ) );
 		
		//-----------------------------------------
		// Get the PMs
		//-----------------------------------------

		$messages = $this->messengerFunctions->getPersonalTopicsList( $this->memberData['member_id'], $this->messengerFunctions->_currentFolderID, array( 'sort' => $sort, 'offsetStart' => $start, 'offsetEnd' => $p_end ) );
	
		//-----------------------------------------
		// Set title
		//-----------------------------------------
		
		$this->_title = $this->lang->words['t_welcome'];
		
		//-----------------------------------------
		// Set navigation
		//-----------------------------------------
		
		$this->_navigation[] = array( $this->messengerFunctions->_dirData[ $this->messengerFunctions->_currentFolderID ]['real'], "app=members&amp;module=messaging&amp;section=view&amp;do=showFolder&amp;folderID={$this->messengerFunctions->_currentFolderID}" );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		return $this->registry->getClass('output')->getTemplate('messaging')->showFolder( $messages, $this->messengerFunctions->_dirData[ $this->messengerFunctions->_currentFolderID ]['real'], $pages, $this->messengerFunctions->_currentFolderID, $this->messengerFunctions->_jumpMenu, $error );
	}
}