<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * UserCP Tab
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Revision: 4948 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class usercpForms_forums extends public_core_usercp_manualResolver implements interface_usercp
{
	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @access	public
	 * @var		string	Tab name
	 */
	public $tab_name		= "Forums";
	
	/**
	 * OK Message
	 * This is an optional message to return back to the framework
	 * to replace the standard 'Settings saved' message
	 *
	 * @access	public
	 * @var		string		"Done" message to display
	 */
	public $ok_message		= '';
	
	/**
	 * Hide 'save' button and form elements
	 * Useful if you have custom output that doesn't
	 * require it
	 *
	 * @access	public
	 * @var		bool	Hide the form/save button
	 */
	public $hide_form_and_save_button	= false;
	
	/**
	 * If you wish to allow uploads, set a value for this
	 *
	 * @access	public
	 * @var		integer
	 */
	public $uploadFormMax			= 0;	
	
	/**
	 * Initiate this module
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['st'] = intval( $this->request['st'] );
		
		//-----------------------------------------
		// Make sure we have values
		//-----------------------------------------
		
		if ( $this->settings['postpage_contents'] == "" )
		{
			$this->settings['postpage_contents'] = '5,10,15,20,25,30,35,40';
		}
		
		if ( $this->settings['topicpage_contents'] == "" )
		{
			$this->settings['topicpage_contents'] = '5,10,15,20,25,30,35,40';
		}
		
		//-----------------------------------------
		// Grab forum class
		//-----------------------------------------
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			try
			{
				require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
				$this->registry->setClass( 'class_forums', new class_forums( $this->registry ) );
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
			
			$this->registry->getClass('class_forums')->strip_invisible = 1;
			$this->registry->getClass('class_forums')->forumsInit();
		}
		
		$this->tab_name	= ipsRegistry::getClass('class_localization')->words['tab__forums'];
	}
	
	/**
	 * Return links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the tab.
	 *
	 * The links must have 'area=xxxxx'. The rest of the URL
	 * is added automatically.
	 * 'area' can only be a-z A-Z 0-9 - _
	 *
	 * @author	Matt Mecham
	 * @access	public
	 * @return   array 		array of links
	 */
	public function getLinks()
	{
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_usercp' ), 'core' );

		$array = array();
		
		$array[] = array( 'url'   => 'area=settings',
						  'title' => ipsRegistry::instance()->getClass('class_localization')->words['board_prefs'],
						  'active' => $this->request['tab'] == 'forums' && $this->request['area'] == 'settings' ? 1 : 0,
						  'area'  => 'settings'
						 );
						
		$array[] = array( 'url'   => 'area=topicsubs',
						  'title' => ipsRegistry::instance()->getClass('class_localization')->words['subs_title'],
						  'active' => $this->request['tab'] == 'forums' && $this->request['area'] == 'topicsubs' ? 1 : 0,
						  'area'   => 'topicsubs'						  
						 );
		
		$array[] = array( 'url'   => 'area=forumsubs',
						  'title' => ipsRegistry::instance()->getClass('class_localization')->words['forum_subs_header'],
						  'active' => $this->request['tab'] == 'forums' && $this->request['area'] == 'forumsubs' ? 1 : 0,
						  'area'   => 'forumsubs'						  
						 );
		
		
		if ( $this->memberData['g_is_supmod'] == 1 )
		{
			$array[] = array( 'url'   => 'area=mod_announcements',
							  'title' => ipsRegistry::instance()->getClass('class_localization')->words['menu_announcements'],
						  	  'active' => $this->request['tab'] == 'forums' && $this->request['area'] == 'mod_announcements' ? 1 : 0,
						      'area'   => 'mod_announcements'							  
							  );
		}
								
		return $array;
	}
	
	/**
	 * Reset the area
	 * This is used so that you can reset teh $_AREA, to fix the the left-hand menu highlighting
	 * for custom events (e.g. announcements)
	 *
	 * @author	Brandon Farber
	 * @access	public
	 * @param	string		Area
	 * @return   string		Area
	 */
	public function resetArea( $area )
	{
		if( $area == 'modAddAnnouncement' OR $area == 'modEditAnnouncement' )
		{
			$area = 'mod_announcements';
		}
		
		return $area;
	}
		
	/**
	 * Run custom event
	 *
	 * If you pass a 'do' in the URL / post form that is not either:
	 * save / save_form or show / show_form then this function is loaded
	 * instead. You can return a HTML chunk to be used in the UserCP (the
	 * tabs and footer are auto loaded) or redirect to a link.
	 *
	 * If you are returning HTML, you can use $this->hide_form_and_save_button = 1;
	 * to remove the form and save button that is automatically placed there.
	 *
	 * @author	Matt Mecham
	 * @access	public
	 * @param	string				Current 'area' variable (area=xxxx from the URL)
	 * @return   mixed 				html or void
	 */
	public function runCustomEvent( $currentArea )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$html = '';
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $currentArea )
		{
			# Mod: Announcements
			case 'modDeleteAnnouncement':
				$html = $this->_customEvent_modAnnounceDelete();
			break;
			case 'modAddAnnouncement':
				$html = $this->_customEvent_modAnnounceForm('add');
			break;
			case 'modEditAnnouncement':
				$html = $this->_customEvent_modAnnounceForm('edit');
			break;
			case 'modSaveAnnouncement':
				$html = $this->_customEvent_modAnnounceSave( $this->request['type'] );
			break;
			# Watched Topics / Forums
			case 'updateWatchTopics':
				$this->_customEvent_updateTopics();
			break;
			case 'updateWatchForums':
				$this->_customEvent_updateForums();
			break;
			case 'watch':
				if ( $this->request['do'] == 'saveWatch' )
				{
					$html = $this->_customEvent_watch( TRUE );
				}
				else
				{
					$html = $this->_customEvent_watch();
				}
			break;
		}
		
		//-----------------------------------------
		// Turn off save button
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $html;
	}
	
	/**
	 * Custom Event: Add an announcement
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @return	void
	 */
	private function _customEvent_modAnnounceDelete()
	{
		$announce_id    = intval( $this->request['announce_id'] );
		
		//-----------------------------------------
		// Check to see if we have access
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->getClass('output')->showError( 'announcements_supermods', 2030, true );
 			return;
		}
		
		//-----------------------------------------
		// Delete it
		//-----------------------------------------
		
		if ( $announce_id )
		{
			$this->DB->buildAndFetch( array( 'delete' => 'announcements', 'where' => 'announce_id='.$announce_id ) );
		}
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
		
		$this->registry->cache()->rebuildCache( 'announcements', 'forums' );
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=forums&amp;area=mod_announcements" );
	}
		
	/**
	 * Custom Event: Add an announcement
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @return	void
	 */
	private function _customEvent_modAnnounceSave( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$forums_to_save = "";
		$start_date     = 0;
		$end_date       = 0;
		$announce_id    = intval( $this->request['announce_id'] );
		
		//-----------------------------------------
		// Check to see if we have access
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->getClass('output')->showError( 'announcements_supermods', 2031, true );
 			return;
		}
		
		//-----------------------------------------
		// Turn off global form stuff
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( ! $this->request['announce_title'] or ! $this->request['announce_post'] )
		{
			return $this->_customEvent_modAnnounceForm( $type, $this->lang->words['announce_error_title'] );
		}
		
		//-----------------------------------------
		// Get forums to add announce in
		//-----------------------------------------
		
		if ( is_array( $_POST['announce_forum'] ) and count( $_POST['announce_forum'] ) )
		{
			if ( in_array( '*', $_POST['announce_forum'] ) )
			{
				$forums_to_save = '*';
			}
			else
			{
				$forums_to_save = implode( ",", $_POST['announce_forum'] );
			}
		}
		
		if ( ! $forums_to_save )
		{

			return $this->_customEvent_modAnnounceForm( $type, $this->lang->words['announce_error_forums'] );
		}
		
		//-----------------------------------------
		// Check Dates
		//-----------------------------------------
		
		if ( strstr( $this->request['announce_start'], '-' ) )
		{
			$start_array = explode( '-', $this->request['announce_start'] );
			
			if ( $start_array[0] and $start_array[1] and $start_array[2] )
			{
				if ( ! checkdate( $start_array[0], $start_array[1], $start_array[2] ) )
				{
					return $this->_customEvent_modAnnounceForm( $type, $this->lang->words['announce_error_date'] );
				}
			}
			
			$start_date = IPSTime::date_gmmktime( 0, 0, 1, $start_array[0], $start_array[1], $start_array[2] );
		}
		
		if ( strstr( $this->request['announce_end'], '-' ) )
		{
			$end_array = explode( '-', $this->request['announce_end']  );
			
			if ( $end_array[0] and $end_array[1] and $end_array[2] )
			{
				if ( ! checkdate( $end_array[0], $end_array[1], $end_array[2] ) )
				{
					return $this->_customEvent_modAnnounceForm( $type, $this->lang->words['announce_error_date'] );
				}
			}
			
			$end_date = IPSTime::date_gmmktime( 23, 59, 59, $end_array[0], $end_array[1], $end_array[2] );
		}
		
		//-----------------------------------------
		// Sort out the content
		//-----------------------------------------

		$announceContent = IPSText::getTextClass( 'editor' )->processRawPost( 'announce_post' );

		IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html		= $this->request['announce_html_enabled'] ? $this->request['announce_html_enabled'] : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= $this->request['announce_nlbr_enabled'] ? $this->request['announce_nlbr_enabled'] : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'announcement';
		
		//-----------------------------------------
		// Build save array
		//-----------------------------------------
		
		$save_array = array( 'announce_title'        => $this->request['announce_title'],
							 'announce_post'         => IPSText::getTextClass( 'bbcode' )->preDbParse( $announceContent ),
							 'announce_active'       => $this->request['announce_active'] ? $this->request['announce_active'] : 0,
							 'announce_forum'        => $forums_to_save,
							 'announce_html_enabled' => $this->request['announce_html_enabled'] ? $this->request['announce_html_enabled'] : 0,
							 'announce_nlbr_enabled' => $this->request['announce_nlbr_enabled'] ? $this->request['announce_nlbr_enabled'] : 0,
							 'announce_start'        => $start_date,
							 'announce_end'          => $end_date
						   );
						   
		//-----------------------------------------
		// Save..
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$save_array['announce_member_id'] = $this->memberData['member_id'];
			
			$this->DB->insert( 'announcements', $save_array );
		}
		else
		{
			if ( $announce_id )
			{
				$this->DB->update( 'announcements', $save_array, 'announce_id='.$announce_id );
			}
		}
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
		
		$this->registry->cache()->rebuildCache( 'announcements', 'forums' );
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=forums&amp;area=mod_announcements" );
	}
	
	
	/**
	 * Custom Event: Add an announcement
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @param	string	Add or edit
	 * @param	string	Message to show
	 * @return	string	HTML
	 */
	private function _customEvent_modAnnounceForm( $type='add', $msg='' )
	{
		//-----------------------------------------
		// Check to see if we have access
		//-----------------------------------------

		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->getClass('output')->showError( 'announcements_supermods', 2032, true );
 			return;
		}
		
		//-----------------------------------------
		// Turn off global form stuff
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// INIT the editor/bbcode classes
		//-----------------------------------------
		
		/* Load the Parser */
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'announcement';
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$button   = $this->lang->words['announce_add'];
			$announce = array( 'announce_active' => 1, 'announce_id' => 0 );
		
		}
		else
		{
			$announce_id                = intval( $this->request['announce_id'] );
			$button                     = $this->lang->words['announce_button_edit'];
			$announce                   = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'announcements', 'where' => 'announce_id='.$announce_id ) );
			$announce['announce_forum'] = explode( ",", $announce['announce_forum'] );
			$announce['announce_start'] = $announce['announce_start'] ? gmstrftime( '%m-%d-%Y', $announce['announce_start'] ) : '';
			$announce['announce_end']   = $announce['announce_end']   ? gmstrftime( '%m-%d-%Y', $announce['announce_end'] ) : '';
		}
		
		//-----------------------------------------
		// Do we have _POST?
		//-----------------------------------------
		
		foreach( array( 'announce_html_enabled', 'announce_title', 'announce_post', 'announce_start', 'announce_end', 'announce_forum', 'announce_active' ) as $bit )
		{
			if ( isset($_POST[$bit]) AND $_POST[$bit] )
			{
				$announce[$bit] = $_POST[$bit];
			}
			else if( !isset($announce[$bit]) )
			{
				$announce[$bit] = NULL;
			}
		}
		
		if ( $announce['announce_post'] )
		{
			if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
			{
				$announce['announce_post'] = IPSText::getTextClass( 'bbcode' )->convertForRTE( $announce['announce_post'] );
			}
			else
			{
				IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
				IPSText::getTextClass( 'bbcode' )->parse_html		= $announce['announce_html_enabled'] ? $announce['announce_html_enabled'] : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br		= $announce['announce_nlbr_enabled'] ? $announce['announce_nlbr_enabled'] : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
				
				$announce['announce_post'] = IPSText::getTextClass( 'bbcode' )->preEditParse( $announce['announce_post'] );
			}
		}
		
		//-----------------------------------------
		// Forums
		//-----------------------------------------
		
		$forum_html = "<option value='*'>" . $this->lang->words['announce_form_allforums'] . "</option>" . $this->registry->getClass('class_forums')->forumsForumJump(0,1,1);
		
		//-----------------------------------------
		// Save forums?
		//-----------------------------------------
		
		if ( is_array( $announce['announce_forum'] ) and count( $announce['announce_forum'] ) )
		{
			foreach( $announce['announce_forum'] as $f )
			{
				$forum_html = preg_replace( "#option\s+value=[\"'](".preg_quote($f,'#').")[\"']#i", "option value='\\1' selected='selected'", $forum_html );
			}
		}
		
		$announce['announce_active_checked'] = $announce['announce_active'] 	  ? 'checked="checked"'  : '';
		$announce['html_checkbox'] 			 = $announce['announce_html_enabled'] ? "checked='checked' " : '';
		$announce['nlbr_checkbox'] 			 = $announce['announce_nlbr_enabled'] ? "checked='checked' " : '';
		
		$this->_nav[] = array( ipsRegistry::instance()->getClass('class_localization')->words['menu_announcements'], "app=core&amp;module=usercp&amp;tab=forums&amp;area=mod_announcements" );
		$this->_nav[] = array( ipsRegistry::instance()->getClass('class_localization')->words['announce_add'] );
		
		return $this->registry->getClass('output')->getTemplate('ucp')->modAnnounceForm($announce, $button, $forum_html, $type, IPSText::getTextClass( 'editor' )->showEditor( $announce['announce_post'], 'announce_post' ), $msg);
	}
	
	
	/**
	 * Custom Event: Update watched forums
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @return	void
	 */
	private function _customEvent_updateForums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids     = array();
 		$allowed = array( 'none', 'immediate', 'delayed', 'daily', 'weekly' );

		//-----------------------------------------
		// Get IDs
		//-----------------------------------------
		
		if ( is_array( $_REQUEST['forumIDs'] ) )
		{
			foreach( $_REQUEST['forumIDs'] as $id => $value )
			{
				$ids[] = intval( $id );
			}
		}
		
		//-----------------------------------------
 		// Check...
 		//-----------------------------------------

 		if ( $this->request['authKey'] != $this->member->form_hash )
 		{
 			$this->registry->getClass('output')->showError( 'usercp_forums_bad_key', 3030, true );
 		}

		//-----------------------------------------
 		// what we doing?
 		//-----------------------------------------
 		
 		if ( count($ids) > 0 )
 		{
 			if ( $this->request['trackchoice'] == 'unsubscribe' )
 			{
 				$this->DB->buildAndFetch( array( 'delete' => 'forum_tracker', 'where' => "member_id=" . $this->memberData['member_id'] . " and forum_id IN (".implode( ",", $ids ).")" ) );
 			}
 			else if ( in_array( $this->request['trackchoice'], $allowed ) )
 			{
 				$this->DB->update( 'forum_tracker', array( 'forum_track_type' => $this->request['trackchoice'] ), "member_id=" . $this->memberData['member_id'] . " and forum_id IN (".implode( ",", $ids ).")" );
 			}
 		}

		$this->registry->getClass('class_forums')->recacheWatchedForums( $this->memberData['member_id'] );
		
		if( $this->request['forumReturn'] )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'showforum=' . intval( $this->request['forumReturn'] ) );
		}
		else
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=forums&amp;area=forumsubs" );
		}
	}
	
	/**
	 * Custom Event: Update watched topics
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @return	void
	 */
	private function _customEvent_updateTopics()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids     = array();
 		$allowed = array( 'none', 'immediate', 'delayed', 'daily', 'weekly' );

		//-----------------------------------------
		// Get IDs
		//-----------------------------------------
		
		if ( is_array( $_REQUEST['topicIDs'] ) )
		{
			foreach( $_REQUEST['topicIDs'] as $id => $value )
			{
				$ids[] = intval( $id );
			}
		}
	
		//-----------------------------------------
 		// Check...
 		//-----------------------------------------
 		
 		if ( $this->request['authKey'] != $this->member->form_hash )
 		{
 			$this->registry->getClass('output')->showError( 'usercp_forums_bad_key', 3031, true );
 		}
 		
 		//-----------------------------------------
 		// what we doing?
 		//-----------------------------------------
 		
 		if ( count($ids) > 0 )
 		{
 			if ( $this->request['trackchoice'] == 'unsubscribe' )
 			{
 				$this->DB->delete( 'tracker', "member_id='{$this->memberData['member_id']}' and topic_id IN ( " . implode( ",", $ids ) . ")" );
 			}
 			else if ( in_array( $this->request['trackchoice'], $allowed ) )
 			{
 				$this->DB->update( 'tracker', array( 'topic_track_type' => $this->request['trackchoice'] ), "member_id='{$this->memberData['member_id']}' and topic_id IN (".implode( ",", $ids ).")" );
 			}
 		}
 		
 		if( $this->request['topicReturn'] )
 		{
 			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'showtopic=' . intval( $this->request['topicReturn'] ) );
 		}
 		else
 		{
 	    	$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=forums&amp;area=topicsubs" );
 	    }
	}
	
	/**
	 * Custom Event: Watch A Topic
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @param	bool	Whether to save it or not
	 * @return	void
	 */
	private function _customEvent_watch( $saveIt=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$watch      = $this->request['watch'] == 'forum' ? 'forum' : 'topic';
		$topicID    = intval($this->request['tid']);
		$forumID    = intval($this->request['fid']);
		$forum   	= array();
		$topic	    = array();
		
		if ( $watch == 'topic' )
		{
			//-----------------------------------------
			// Get the details from the DB (TOPIC)
			//-----------------------------------------
		
			$topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$topicID ) );
			
			if ( ! $topic['tid'] )
			{ 
				$this->registry->getClass('output')->showError( 'usercp_forums_no_topic', 1030 );
			}
			
			$forum   = $this->registry->getClass('class_forums')->forum_by_id[ $topic['forum_id'] ];
			$forumID = $topic['forum_id'];
		}
		else
		{
			//-----------------------------------------
			// Get the details (FORUM)
			//-----------------------------------------
		
			$forum = $this->registry->getClass('class_forums')->forum_by_id[ $forumID ];
		}
		
		//-----------------------------------------
		// Permy check
		//-----------------------------------------
		
		if ( IPSMember::checkPermissions('read', $forumID ) !== TRUE )
		{
			$this->registry->getClass('output')->showError( 'usercp_forums_no_perms', 1031 );
		}
		
		//-----------------------------------------
		// Passy check
		//-----------------------------------------
		
		if ( ! in_array( $this->memberData['member_group_id'], explode(",", $forum['password_override']) ) AND ( isset($forum['password']) AND $forum['password'] != "" ) )
		{
			if ( $this->registry->getClass('class_forums')->forumsComparePassword( $forum['id'] ) != TRUE )
			{
				$this->registry->getClass('output')->showError( 'usercp_forums_must_login', 1032 );
			}
		}
		
		//-----------------------------------------
		// Have we already subscribed?
		//-----------------------------------------
		
		if ( $watch == 'forum' )
		{
			$tmp = $this->DB->buildAndFetch( array( 
													'select' => 'frid as tmpid',
													'from'   => 'forum_tracker',
													'where'  => "forum_id={$forumID} AND member_id=".$this->memberData['member_id'] 
											)	 );
		}
		else
		{
			$tmp = $this->DB->buildAndFetch( array( 
													'select' => 'trid as tmpid',
													'from'   => 'tracker',
													'where'  => "topic_id={$topicID} AND member_id=".$this->memberData['member_id'] 
											)	 );
		}
		
		if ( $tmp['tmpid'] )
		{
			$this->registry->getClass('output')->showError( 'forum_already_subscribed', 1033 );
		}
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		if ( ! $saveIt )
		{
			//-----------------------------------------
			// Okay, lets do the HTML
			//-----------------------------------------
			
			return $this->registry->getClass('output')->getTemplate('ucp')->watchChoices( $forum, $topic, $watch );
		}
		else
		{
			//-----------------------------------------
			// Auth check
			//-----------------------------------------
			
			if ( $this->request['auth_key'] != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'usercp_forums_bad_key', 2033, true );
			}
			
			//-----------------------------------------
			// Method..
			//-----------------------------------------
			
			switch ( $this->request['emailtype'] )
			{
				case 'immediate':
					$_method = 'immediate';
					break;
				case 'delayed':
					$_method = 'delayed';
					break;
				case 'none':
					$_method = 'none';
					break;
				case 'daily':
					$_method = 'daily';
					break;
				case 'weekly':
					$_method = 'weekly';
					break;
				default:
					$_method = 'delayed';
					break;
			}
        
			//-----------------------------------------
			// Add it to the DB
			//-----------------------------------------
			
			if ( $watch == 'forum' )
			{
				$this->DB->insert( 'forum_tracker', array (
														 'member_id'        => $this->memberData['member_id'],
														 'forum_id'         => $forumID,
														 'start_date'       => time(),
														 'forum_track_type' => $_method,
											  )       );
				
				$this->registry->getClass('class_forums')->recacheWatchedForums( $this->memberData['member_id'] );
				
				$this->registry->getClass('output')->redirectScreen( $this->lang->words['sub_added'], $this->settings['base_url'] . "showforum={$forumID}", $forum['name_seo'] );	
			
			}
			else
			{
				$this->DB->insert( 'tracker',  array (
												   'member_id'        => $this->memberData['member_id'],
												   'topic_id'         => $topicID,
												   'start_date'       => time(),
												   'topic_track_type' => $_method,
										)       );
										
				$this->registry->getClass('output')->redirectScreen( $this->lang->words['sub_added'], $this->settings['base_url'] . "showtopic={$topicID}&st=" . $this->request['st'], $topic['title_seo'] );
	
			}
		}
	}
	
	/**
	 * UserCP Form Show
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string	Current area as defined by 'get_links'
	 * @param	array   Array of member / core_sys_login information (if we're editing)
	 * @return   string  Processed HTML
	 */
	public function showForm( $current_area, $errors=array() )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
	
		switch( $current_area )
		{
			default:
			case 'settings':
				return $this->showForumSettings();
			break;
			case 'topicsubs':
				return $this->showTopicSubs();
			break;
			case 'forumsubs':
				return $this->showForumSubs();
			break;
			case 'mod_announcements':
				return $this->showModAnnouncements();
			break;
		}
	}
	
	/**
	 * Show topic announcement management
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return   string  Processed HTML
	 */
	public function showModAnnouncements()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$announcements = array();
		
		//-----------------------------------------
		// Turn off global form stuff
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// Make sure we have access...
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->getClass('output')->showError( 'announcements_supermods', 2034, true );
 			return;
		}
		
		//-----------------------------------------
		// Get announcements
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'a.*',
										'from'		=> array( 'announcements' => 'a' ),
										'order'		=> 'a.announce_end DESC',
										'add_join'	=> array(
															array( 'select'	=> 'm.member_id, m.name, m.members_display_name',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=a.announce_member_id',
																	'type'	=> 'left'
																)
															)
								)		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			if ( $r['announce_forum'] == '*' )
			{
				$r['announce_forum_show'] = $this->lang->words['announce_page_allforums'];
			}
			else
			{
				$tmp_forums = explode(",",$r['announce_forum']);

				if ( is_array( $tmp_forums ) and count($tmp_forums) )
				{
					if ( count($tmp_forums) > 5 )
					{
						$r['announce_forum_show'] = count($tmp_forums).' '.$this->lang->words['announce_page_numforums'];
					}
					else
					{
						$r['_forums'] = array();
						
						foreach( $tmp_forums as $id )
						{
							$r['_forums'][] = array( $id, $this->registry->getClass('class_forums')->forum_by_id[ $id ]['name'] );
						}
					}
				}	
			}

			$r['announce_inactive'] = !$r['announce_active'] ? "<span class='desc'>" . $this->lang->words['announce_page_disabled'] . "</span>" : '';
			
			$announcements[] = $r;
			
			//$content .= $this->registry->getClass('output')->getTemplate('ucp')->ucp_announce_manage_row( $r );
		}
		
		return $this->registry->getClass('output')->getTemplate('ucp')->modAnnouncements( $announcements );
	}
	
	/**
	 * Show topic subscriptions
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	string  Processed HTML
	 * @todo 	[Future] Explore turning "new posts" into "unread posts"
	 */
	public function showForumSubs()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mainForumArray = array();
		$forums         = array();
		$forumIDs		= array();
		$topParents     = array();
		
		//-----------------------------------------
		// Turn off global form stuff
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
 		// INIT...
 		//-----------------------------------------
 		
 		$remap = array( 'none'      => 'subs_none_title',
						'immediate' => 'subs_immediate',
						'delayed'   => 'subs_delayed',
						'daily'     => 'subs_daily',
						'weekly'    => 'subs_weekly'
					  );
					  
 		$mainForumArray = array();

 		//-----------------------------------------
 		// Query the DB for the subby toppy-ics - at the same time
 		// we get the forum and topic info, 'cos we rule.
 		//-----------------------------------------
 		
		$this->DB->build( array( 'select'	=> '*',
								 'from'	    => 'forum_tracker',
								 'where'	=> 'member_id=' . $this->memberData['member_id'] ) );
		$this->DB->execute();
		
 		while( $forum = $this->DB->fetch() )
 		{
			$topParents[]                 = $this->registry->getClass('class_forums')->fetchTopParentID( $forum['forum_id'] );
			$forum['_type']               = $remap[ $forum['forum_track_type'] ];
			$forums[ $forum['forum_id'] ] = $forum;
		}
		
		//-----------------------------------------
		// Get new count
		//-----------------------------------------
		
		if ( is_array( $forums ) AND count( $forums ) )
		{
			$this->DB->build( array( 'select'   => 'forum_id, COUNT(*) as newTopics',
									 'from'     => 'topics',
									 'where'    => 'forum_id IN (' . implode( ',', array_keys( $forums ) ) . ') AND last_post > ' . $this->memberData['last_visit'],
									 'group'    => 'forum_id' ) );
														
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forums[ $row['forum_id'] ]['_newTopics'] = $row['newTopics'];
			}
		}
		
		//-----------------------------------------
		// Now, loop through all forums...
		//-----------------------------------------
		
		foreach( $this->registry->getClass('class_forums')->forum_cache['root'] as $id => $data )
		{
			if ( in_array( $id, $topParents ) )
			{
				$mainForumArray[ $id ] = array( '_data'   => $data,
												'_forums' => $this->_showForumSubsRecurse( $forums, $id ) );
			}
		}

		return $this->registry->getClass('output')->getTemplate('ucp')->watchedForums( $mainForumArray );
	}
	
	/**
	 * Recursively build up the tracked forums
	 *
	 * @access	private
	 * @param	array 		Tracked forums
	 * @param	integer		Forum id to start at
	 * @param	array 		Forum data thus far
	 * @param	integer		Depth level
	 * @return	array 		Forums
	 */
	private function _showForumSubsRecurse( $forums, $root, $forumArray=array(), $depth=0 )
	{
		if ( is_array( $this->registry->getClass('class_forums')->forum_cache[ $root ] ) AND count( $this->registry->getClass('class_forums')->forum_cache[ $root ] ) )
		{
			foreach( $this->registry->getClass('class_forums')->forum_cache[ $root ] as $id => $forum )
			{
				if ( in_array( $id, array_keys( $forums ) ) )
				{ 
					//-----------------------------------------
					// Got perms to see this forum?
					//-----------------------------------------
			
					if ( ! $this->registry->getClass('class_forums')->forum_by_id[ $forum['id'] ] )
					{
						continue;
					}
					
					$forum['_depth']      = $depth;
					$forum['_newTopics']  = $forums[ $forum['id'] ]['_newTopics'];
					$forum['_type']       = $forums[ $forum['id'] ]['_type'];
					
					$forum['folder_icon'] = $this->registry->getClass('class_forums')->forumsNewPosts($forum);
							
					$forum['last_title'] = str_replace( "&#33;" , "!", $forum['last_title'] );
					$forum['last_title'] = str_replace( "&quot;", '"', $forum['last_title'] );
				
					if ( IPSText::mbstrlen($forum['last_title']) > 30 )
					{
						$forum['last_title'] = IPSText::truncate( $forum['last_title'], 30 );
					}
			
					$forumArray[ $forum['id'] ] = $forum;
				}
				
				$forumArray = $this->_showForumSubsRecurse( $forums, $forum['id'], $forumArray, $depth + 1 );
			}
		}
		
		return $forumArray;
	}
	
	/**
	 * Show topic subscriptions
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return   string  Processed HTML
	 */
	public function showTopicSubs()
	{
		//-----------------------------------------
		// Turn off global form stuff
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
 		//-----------------------------------------
 		// INIT...
 		//-----------------------------------------
 		
		$date_cut       = ( intval($this->request['datecut'] ) ) ? intval( $this->request['datecut'] ) : 30;
 		$date_query     = $date_cut != 1000 ? " AND t.last_post > ".(time() - ($date_cut*86400))." " : "";
 		$topic_array    = array();
		$forum_array    = array();
		$mainForumArray = array();
		
 		$remap = array( 'none'      => 'subs_none_title',
						'immediate' => 'subs_immediate',
						'delayed'   => 'subs_delayed',
						'daily'     => 'subs_daily',
						'weekly'    => 'subs_weekly'
					  );
 		
 		//-----------------------------------------
 		// Get forums module
 		//-----------------------------------------
 		
 		require_once( IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/forums.php' );
 		$forumController = new public_forums_forums_forums( $this->registry );
		$forumController->makeRegistryShortcuts( $this->registry );
 		$forumController->initForums();
 		
 		//-----------------------------------------
 		// Query the DB for the subby toppy-ics - at the same time
 		// we get the forum and topic info, 'cos we rule.
 		//-----------------------------------------
 		
		$this->DB->build( array( 'select'	=> 's.topic_track_type, s.trid, s.member_id, s.topic_id, s.last_sent, s.start_date as track_started',
										'from'	=> array( 'tracker' => 's' ),
										'where'	=> 's.member_id=' . $this->memberData['member_id'] . $date_query,
										'add_join'	=> array(
															array( 'select'	=> 't.*',
																	'from'	=> array( 'topics' => 't' ),
																	'where'	=> 't.tid=s.topic_id',
																	'type'	=> 'left'
																),
															array( 'select'	=> 'f.id as forum_id, f.name as forum_name',
																	'from'	=> array( 'forums' => 'f' ),
																	'where'	=> 'f.id=t.forum_id',
																	'type'	=> 'left'
																),
															)
								)		);
		$this->DB->execute();
		
		while( $topic = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Got perms to see this forum?
			//-----------------------------------------
			
			if ( ! $this->registry->getClass('class_forums')->forum_by_id[ $topic['forum_id'] ] )
			{
				continue;
			}
			
			$topic['_last_post']        = $topic['last_post'];
			$topic['_type']             = $remap[ $topic['topic_track_type'] ];
			$topic_ids[ $topic['tid'] ] = $topic['forum_id'];
			
			if ( ! isset( $mainForumArray[ $topic['forum_id'] ] ) )
			{
				$mainForumArray[ $topic['forum_id'] ] = $topic;
			}
			
			$mainForumArray[ $topic['forum_id'] ]['_topics'][ $topic['tid'] ] = $topic;
		}
		
		//-----------------------------------------
		// Are we dotty?
		//-----------------------------------------
		
		if( ( $this->settings['show_user_posted'] == 1 ) and ( $this->memberData['member_id'] ) and count($mainForumArray) )
		{
			$this->DB->build( array( 
									'select' => 'author_id, topic_id',
									'from'   => 'posts',
									'where'  => 'author_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', array_keys( $topic_ids ) ) . ')',
							)	);
									  
			$this->DB->execute();
			
			while( $p = $this->DB->fetch() )
			{
				if ( is_array( $mainForumArray[ $topic_ids[ $p['topic_id'] ] ]['_topics'][ $p['topic_id'] ] ) )
				{
					$mainForumArray[ $topic_ids[ $p['topic_id'] ] ]['_topics'][ $p['topic_id'] ]['author_id'] = $p['author_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Now parse
		//-----------------------------------------
		
		foreach( $mainForumArray as $forum_id => $data )
		{
			foreach( $mainForumArray[ $forum_id ]['_topics'] as $tid => $_data )
			{
				$mainForumArray[ $forum_id ]['_topics'][ $tid ] = $forumController->parseTopicData( $_data );
			}
		}
	
		return $this->registry->getClass('output')->getTemplate('ucp')->watchedTopics( $mainForumArray, $date_cut );
	}
	
	/**
	 * Show the forums Settings form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return   string  Processed HTML
	 */
	public function showForumSettings()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_emailData   = array();
		$pp_a         = array();
		$tp_a         = array();
		
		//-----------------------------------------
		// Email settings...
		//-----------------------------------------
		
		foreach( array('email_full', 'auto_track') as $k )
		{
			if ( $this->memberData[ $k ] )
			{
				$_emailData[ $k ] = 'checked="checked"';
			}
			else
			{
				$_emailData[ $k ] = '';
			}
		}
		
		foreach( array( 'none', 'immediate', 'delayed', 'daily', 'weekly' ) as $_opt )
		{
			$_emailData['trackOption'][ $_opt ] = ( $this->memberData['auto_track'] == $_opt ) ? 'selected="selected"' : '';
		}

		//-----------------------------------------
		// Viewing settings...
		//-----------------------------------------
		
		list( $post_page, $topic_page ) = explode( "&", $this->memberData['view_prefs'] );

		if ( $post_page == "" )
		{
			$post_page = -1;
		}
		if ( $topic_page == "" )
		{
			$topic_page = -1;
		}

		foreach( explode( ',', $this->settings['postpage_contents'] ) as $n )
		{
			$n      = intval(trim($n));
			$pp_a[] = array( $n, $n );
		}

		foreach( explode( ',', $this->settings['topicpage_contents'] ) as $n )
		{
			$n      = intval(trim($n));
			$tp_a[] = array( $n, $n );
		}

		return $this->registry->getClass('output')->getTemplate('ucp')->forumPrefsForm( $_emailData, array( 
																											'quickReplyForm'    => $html_qr,
																											'viewTopicsForm'    => $tp_a,
																											'viewPostsForm'     => $pp_a,
																											'postsPerPage'		=> $post_page,
																											'topicsPerPage'		=> $topic_page ) );
	}
	
	/**
	 * UserCP Form Check
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string	Current area as defined by 'get_links'
	 * @return   string  Processed HTML
	 */
	public function saveForm( $current_area )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
		
		switch( $current_area )
		{
			default:
			case 'settings':
				return $this->saveForumSettings();
			break;
		}
	}

	/**
	 * UserCP Save Form: Settings
	 *
	 * @access	public
	 * @return   array  Errors
	 */
	public function saveForumSettings()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_trackChoice = '';
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! in_array( $this->request['postpage'], explode( ',', $this->settings['postpage_contents'] ) ) )
		{
			$this->request[ 'postpage'] =  '-1' ;
		}
		
		if ( ! in_array( $this->request['topicpage'], explode( ',', $this->settings['topicpage_contents'] ) ) )
		{
			$this->request[ 'topicpage'] =  '-1' ;
		}
		
		//-----------------------------------------
		// Type of track
		//-----------------------------------------
		
		if ( $this->request['auto_track'] )
		{
 			if ( in_array( $this->request['trackchoice'], array( 'none', 'immediate', 'delayed', 'daily', 'weekly' ) ) )
 			{
 				$_trackChoice = $this->request['trackchoice'];
 			}
 		}
 		
 		IPSCookie::set( 'topicmode', $this->request['topic_display_mode'], 1 );
		
		IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'view_avs'   => intval($this->request['viewAvatars']),
		 																				  'view_sigs'  => intval($this->request['viewSignatures']),
																						  'view_img'   => intval($this->request['viewImages']),
																						  'email_full' => intval($this->request['send_full_msg']),
																						  'auto_track' => $_trackChoice,
																						  'view_prefs' => intval($this->request['postpage'])."&".intval($this->request['topicpage']) ) ) );
																						
		IPSMember::packMemberCache( $this->memberData['member_id'], array( 'qr_open' => intval($this->request['fastReplyOpen']) ), $this->memberData['_cache'] );
		
		return TRUE;
	}
	
	/**
	 * Password check
	 *
	 * @access	private
	 * @param	string	Plain Text Password
	 * @return	bool	Password matches
	 */
	private function _check_password( $password_check )
	{
		//-----------------------------------------
		// Ok, check password first
		//-----------------------------------------
		
    	require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login( $this->registry );
    	$this->han_login->init();
		
		//-----------------------------------------
		// Is this a username or email address?
		//-----------------------------------------
		
		$this->han_login->loginPasswordCheck( $this->memberData['name'], $this->memberData['email'], $password_check );
	
		if ( $this->han_login->return_code == 'SUCCESS' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}