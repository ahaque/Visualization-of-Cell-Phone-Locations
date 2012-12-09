<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile AJAX Ignored User methods
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		Thursday 26th June 2008
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_messenger extends ipsAjaxCommand 
{
	/**
	 * Messenger library
	 *
	 * @access	private
	 * @var		object
	 */
	private $messengerFunctions;

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
		// Grab class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
		$this->messengerFunctions = new messengerFunctions( $registry );
		
		switch( $this->request[ 'do' ] )
		{
			case 'addFolder':
				$this->_addFolder();
			break;
			case 'removeFolder':
				$this->_removeFolder();
			break;
			case 'renameFolder':
				$this->_renameFolder();
			break;
			case 'emptyFolder':
				$this->_emptyFolder();
			break;
			case 'getPMNotification':
				$this->_getPMNotification();
			break;
			case 'showQuickForm':
				$this->_showQuickForm();
			break;
			case 'PMSend':
				$this->_PMSend();
			break;
			default:
			break;
		}
	}
	
	/**
	 * Sends the PM
	 *
	 * @access	private
	 * @return	string		HTML to be returned via ajax
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _PMSend()
 	{
 		if( $this->messengerFunctions->checkHasHitMax() )
 		{
 			$this->returnJsonError( 'cannotUsePMSystem' );
 		}
 		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$toMemberID            = intval( $this->request['toMemberID'] );
		$msgContent            = $this->convertHtmlEntities( $this->convertUnicode( $_POST['Post'] ) );
		$msgTitle			   = IPSText::parseCleanValue( $this->convertHtmlEntities( $this->convertUnicode( $_POST['subject'] ) ) );
    	$this->request['Post'] = IPSText::parseCleanValue( $_POST['Post'] );
		$_trackMsg             = intval( $this->request['trackMsg'] );
		$_addToSent			   = intval( $this->request['addToSent'] );
		
		//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		//-----------------------------------------
		// Reset Classes
		//-----------------------------------------
		
		IPSText::resetTextClass('editor');
		
		//-----------------------------------------
		// Load lang file
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_error" ), 'core' );
		
		//-----------------------------------------
    	// Language
    	//-----------------------------------------
    	
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );
    	
 		//-----------------------------------------
 		// Send .. or.. save...
 		//-----------------------------------------
 		
		try
		{
 			$this->messengerFunctions->sendNewPersonalTopic( $toMemberID, $this->memberData['member_id'], array(), $msgTitle, $msgContent, array( 'origMsgID'       => 0,
																																	 'fromMsgID'       => 0,
																																     'postKey'         => md5(microtime()),
																												   		   		     'trackMsg'        => $_trackMsg,
																										   		  		   		     'addToSentFolder' => $_addToSent,
																												   		   		     'hideCCUser'      => 0 ) );
																												
			return $this->returnJsonArray( array( 'status' => 'sent' ) );
 		}
		catch( Exception $error )
		{
			$msg      = $error->getMessage();
			$toMember = IPSMember::load( $toMemberID, 'core', 'displayname' );
			
			if ( strstr( $msg, 'BBCODE_' ) )
			{
				$msg = str_replace( 'BBCODE_', '', $msg );
				
				return $this->returnJsonArray( array( 'inlineError' => $this->lang->words[ $msg ] ) );
			}
			else if ( isset($this->lang->words[ 'err_' . $msg ]) )
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
			
			return $this->returnJsonArray( array( 'inlineError' => $_msgString ) );
		}
	}
	
	/**
	 * Shows the quick PM form
	 *
	 * @access	private
	 * @return	string		HTML to be returned via ajax
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _showQuickForm()
 	{
 		if( $this->messengerFunctions->checkHasHitMax() )
 		{
 			$this->returnJsonError( 'cannotUsePMSystem' );
 		}
 		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$toMemberID = intval( $this->request['toMemberID'] );
		
		//-----------------------------------------
		// Load...
		//-----------------------------------------
		
		$toMemberData = IPSMember::load( $toMemberID, 'all' );
		
		if ( ! $toMemberData['member_id'] )
		{
			$this->returnJsonError( 'noSuchToMember' );
		}
		
		//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		//-----------------------------------------
		// Stil here?
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		return $this->returnJsonArray( array( 'success' => $this->registry->getClass('output')->getTemplate('messaging')->PMQuickForm( $toMemberData ) ) );
	}
	
	/**
	 * Returns PM notification
	 *
	 * @access	private
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _getPMNotification()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$limit = intval( $this->request['limit'] );
		
		//-----------------------------------------
		// INIT the parser
		//-----------------------------------------
		
		IPSText::resetTextClass('bbcode');
        IPSText::getTextClass('bbcode')->allow_update_caches = 0;
        IPSText::getTextClass('bbcode')->parse_bbcode		 = 1;
        IPSText::getTextClass('bbcode')->parse_smilies		 = 1;
        IPSText::getTextClass('bbcode')->parse_html		 	 = 0;
        IPSText::getTextClass('bbcode')->parsing_section	 = 'pms';
		
		//-----------------------------------------
		// Get last PM details
		//-----------------------------------------
		
		$msg = $this->DB->buildAndFetch( array( 'select'	=> 'mt.*',
														'from'	=> array( 'message_topics' => 'mt' ),
														'where'	=> "mt.mt_owner_id=" . $this->memberData['member_id'] . " AND mt.mt_vid_folder='in'",
														'order'	=> 'mt.mt_date DESC',
														'limit'	=> array( intval($limit), 1 ),
														'add_join'	=> array(
																			array( 'select'	=> 'msg.*',
																					'from'	=> array( 'message_text' => 'msg' ),
																					'where'	=> 'msg.msg_id=mt.mt_msg_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'm.member_id,m.name,m.member_group_id,m.mgroup_others,m.email,m.joined,m.posts, m.last_visit, m.last_activity, m.warn_level, m.warn_lastwarn, m.members_display_name',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=mt.mt_from_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'pp.*',
																					'from'	=> array( 'profile_portal' => 'pp' ),
																					'where'	=> 'pp.pp_member_id=mt.mt_from_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'g.g_id, g.g_title, g.g_icon, g.g_dohtml',
																					'from'	=> array( 'groups' => 'g' ),
																					'where'	=> 'g.g_id=m.member_group_id',
																					'type'	=> 'left'
																				) ) ) );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $msg['msg_id'] and ! $msg['mt_id'] and ! $msg['id'] )
		{
			$this->returnJsonError( 'noMsg' );
		}
		
		//-----------------------------------------
		// Strip and wrap
		//-----------------------------------------
		
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $msg['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $msg['mgroup_others'];
		
		$msg['msg_post'] = IPSText::getTextClass('bbcode')->stripAllTags( strip_tags( str_replace( '<br />', "\n", $msg['msg_post'] ) ) );
		$msg['msg_post'] = IPSText::getTextClass('bbcode')->wordWrap( $msg['msg_post'], 50, "\n" );
		
		if ( IPSText::mbstrlen( $msg['msg_post'] ) > 300 )
		{
			$msg['msg_post'] = IPSText::truncate( $msg['msg_post'], 350 ) ;
		}
		
		$msg['msg_post'] = nl2br($msg['msg_post']);
		
		//-----------------------------------------
		// Add attach icon
		//-----------------------------------------
		
		if ( $msg['mt_hasattach'] )
		{
			$msg['attach_img'] = '<{ATTACH_ICON}>&nbsp;';
		}
		
		//-----------------------------------------
		// Date
		//-----------------------------------------
		
		$msg['msg_date'] = $this->registry->getClass('class_localization')->getDate( $msg['msg_date'], 'TINY' );
		
		//-----------------------------------------
		// Next / Total links
		//-----------------------------------------
		
		$msg['_cur_num']   = intval($limit) + 1;
		$msg['_msg_count_total'] = intval($this->memberData['msg_count_new']) ? intval($this->memberData['msg_count_new']) : 1;
		
		//-----------------------------------------
		// Return loverly HTML
		//-----------------------------------------
		
		return $this->returnHtml( $this->registry->getClass('output')->getTemplate('messaging')->PMNotificationBox( $msg ) );
	}
	
	/**
	 * Empties a folder
	 *
	 * @access	private
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _emptyFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$folderID     = IPSText::alphanumericalClean( $this->request['folderID'] );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		$status	      = 'ok';
 		$mtids        = array();
 		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $folderID )
		{
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// "New" folder?
		//-----------------------------------------
		
		if ( $folderID == 'new' )
		{
			/* Just mark them as read */
			$this->DB->update( 'message_topic_user_map', array( 'map_has_unread' => 0 ), 'map_user_id=' . $memberID . " AND map_user_banned=0 AND map_user_active=1" );
		}
		else
		{
			/* Delete all PMs -you- sent regardless of which folder they're in */
			$messages = $this->messengerFunctions->getPersonalTopicsList( $memberID, $folderID, array( 'offsetStart' => 0, 'offsetEnd' => 100000 ) );
		
 			/* Just grab IDs */
			$mtids = array_keys( $messages );
 		
			//-----------------------------------------
			// Got anything?
			//-----------------------------------------
		
			if ( ! count( $mtids ) )
			{
				$this->returnJsonError( 'nothingToRemove' );
			}
		
			//-----------------------------------------
			// Delete the messages
			//-----------------------------------------
		
			try
			{
				$this->messengerFunctions->deleteTopics( $memberData['member_id'], $mtids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}
		}
		
		//-----------------------------------------
		// Reset total message count
		//-----------------------------------------
		
		$totalMsgs = $this->messengerFunctions->resetMembersTotalTopicCount( $memberData['member_id'] );
 		
		//-----------------------------------------
 		// Update directory counts
 		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->resetMembersFolderCounts( $memberData['member_id'] );
		$folders = $this->messengerFunctions->explodeFolderData( $newDirs );
		
		$this->returnJsonArray( array( 'status' =>  $status, 'totalMsgs' => $totalMsgs, 'newDirs' => $newDirs, 'affectedDirCount' => $folders[ $folderID ]['count'] ) );
	}
	
	/**
	 * Renames a folder
	 *
	 * @access	private
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _renameFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$folderID     = IPSText::alphanumericalClean( $this->request['folderID'] );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		
		// If we run through alpha clean, chars in other langs don't work properly of course
		$name         = IPSText::truncate( $this->convertAndMakeSafe($_POST['name']), 50 );	//IPSText::alphanumericalClean( $this->request['name'], ' ' );
		$status	      = 'ok';
		
		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $folderID )
		{
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// Now ensure we actually have that folder
		//-----------------------------------------
		
		if ( ! $folders[ $folderID ] )
		{
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// ..and it is not a 'set' folder..
		// 8.25.2008 - We should be able to rename these
		// 10.10.2008 - No, we shouldn't now they are "magic"
		//-----------------------------------------
		
		/* Protected? */
		if ( $folders[ $folderID ]['protected'] )
		{
			$this->returnJsonError( 'cannotDeleteUndeletable' );
		}

		//-----------------------------------------
		// OK, rename it.
		//-----------------------------------------
		
		$folders[ $folderID ]['real'] = $name;
		
		//-----------------------------------------
		// Collapse
		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->implodeFolderData( $folders );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		IPSMember::save( $memberID, array( 'extendedProfile' => array( 'pconversation_filters' => $newDirs ) ) );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'status' =>  $status, 'newDirs' => $newDirs, 'name' => $name ) );
	}
	
	/**
	 * Removes a folder
	 *
	 * @access	private
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _removeFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$folderID     = IPSText::alphanumericalClean( $this->request['folderID'] );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		$status	      = 'ok';
		
		IPSDebug::fireBug( 'info', array( 'Received folder id:' . $folderID ) );
		IPSDebug::fireBug( 'info', array( 'Received member id:' . $memberID ) );

		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $folderID )
		{
			IPSDebug::fireBug( 'error', array( 'Missing member id or folder id' ) );
			
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// Now ensure we actually have that folder
		//-----------------------------------------
		
		if ( ! $folders[ $folderID ] )
		{
			IPSDebug::fireBug( 'error', array( 'Specified folder does not exist' ) );
			
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// Protected folder?
		//-----------------------------------------
		
		/* Protected? */
		if ( $folders[ $folderID ]['protected'] )
		{
			$this->returnJsonError( 'cannotDeleteUndeletable' );
		}
		
		//-----------------------------------------
		// .. and it has no messages
		//-----------------------------------------
		
		if ( $folders[ $folderID ]['count'] > 0 )
		{
			$this->returnJsonError( 'cannotDeleteHasMessages' );
		}
		
		//-----------------------------------------
		// OK, remove it.
		//-----------------------------------------
		
		unset( $folders[ $folderID ] );
		
		///-----------------------------------------
		// Collapse
		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->implodeFolderData( $folders );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		IPSMember::save( $memberID, array( 'extendedProfile' => array( 'pconversation_filters' => $newDirs ) ) );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'status' =>  $status, 'newDirs' => $newDirs ) );
	}
	
 	/**
	 * Adds a folder
	 *
	 * @access	private
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	private function _addFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$name         = IPSText::truncate( $this->convertAndMakeSafe($_POST['name']), 50 );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		$status	      = 'ok';
		$maxID        = 0;

		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $name )
		{
			$this->returnJsonError( 'invalidName' );
		}
		
		//-----------------------------------------
		// Get vdir information
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		foreach( $folders as $id => $folder )
		{
			if ( stristr( $folder['id'], 'dir_' ) )
 			{
 				$maxID = intval( str_replace( 'dir_', "", $folder['id'] ) ) + 1;
 			}
		}
		
		//-----------------------------------------
		// Add a folder
		//-----------------------------------------
		
		$folders[ 'dir_' . $maxID ] = array( 'id'        => 'dir_' . $maxID,
											 'real'      => $name,
											 'protected' => 0,
											 'count'     => 0 );
		
		//-----------------------------------------
		// If we have more than 50 folders, error
		//-----------------------------------------

		if ( count( $folders ) > 50 )
		{
			$this->returnJsonError( 'tooManyFolders' );
		}
										
		//-----------------------------------------
		// Collapse
		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->implodeFolderData( $folders );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		IPSMember::save( $memberID, array( 'extendedProfile' => array( 'pconversation_filters' => $newDirs ) ) );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'status' =>  $status, 'newDirs' => $newDirs, 'newID' => 'dir_' . $maxID ) );
	}

}