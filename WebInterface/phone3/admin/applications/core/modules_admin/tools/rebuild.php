<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Rebuild & Other Tools
 * Last Updated: $LastChangedDate: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 5066 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_tools_rebuild  extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;

	/**#@+
	 * URL bits
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		require_once( IPS_ROOT_PATH . "applications/forums/sources/classes/forums/class_forums.php" );
		$this->registry->setClass( 'class_forums', new class_forums( $registry ) );
		$this->registry->class_forums->forumsInit();

		/* Load lang and skin */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_rebuild' );

		/* URLs */
		$this->form_code    = $this->html->form_code    = 'module=tools&amp;section=rebuild';
		$this->form_code_js = $this->html->form_code_js = 'module=tools&section=rebuild';

		/* What to do */
		switch( $this->request['do'] )
		{
			case 'docount':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_recount' );
				$this->doCount();
			break;

			case 'doresyncforums':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_resynch' );
				$this->resyncForums();
			break;

			case 'doresynctopics':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_resyncht' );
				$this->resyncTopics();
			break;

			case 'doposts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_rebuild' );
				$this->rebuildPosts();
			break;

			case 'dopostnames':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_postcounts' );
				$this->rebuildPostNames();
			break;

			case 'dopostcounts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_postcounts' );
				$this->rebuildPostCount();
			break;

			case 'dothumbnails':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_thumbs' );
				$this->rebuildThumbnails();
			break;

			case 'dophotos':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_thumbs' );
				$this->rebuildPhotos();
			break;

			case 'doattachdata':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_attach' );
				$this->rebuildAttachdata();
			break;
			
			case 'doseousernames':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_rebuild' );
				$this->rebuildSeoUserNames();
			break;
			case 'cleanattachments':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_orphaned' );
				$this->cleanAttachments();
			break;

			case 'cleanavatars':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_orphana' );
				$this->cleanAvatars();
			break;

			case 'cleanphotos':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_orphanp' );
				$this->cleanPhotos();
			break;
			
			case 'domsgcounts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_postcounts' );
				$this->rebuildMsgCounts();
			break;

			//-----------------------------------------
			// Tools
			//-----------------------------------------
			
			case '300pms':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->tools300Pms();
			break;
			
			case '220tool_contacts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->tools220Contacts();
			break;

			case '210polls':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->tools210Polls();
			break;

			case '210calevents':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->tools210Calevents();
			break;

			case '210tool_settings':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->tools210DupeSettings();
			break;

			case 'tool_settings':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->toolsDupeSettings();
			break;

			case 'tool_converge':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->toolsConverge();
			break;

			case 'tool_bansettings':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->toolBanSettings();
			break;

			case 'tools':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tools_220' );
				$this->toolsSplashScreen();
			break;

			case 'rebuild_overview':
			default:
				$this->rebuildSplashScreen();
			break;
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * 3.0.x Tools: PM Conversion
	 *
	 * @access	public
	 * @return	void
	 */
	public function tools300pms()
	{
		/* INIT */
		$pergo     = 100;
		$start     = intval( $this->request['st'] );
		$converted = 0;
		$seen      = 0;
		
		//-----------------------------------------
		// Check to make sure table exists
		//-----------------------------------------

		if ( ! $this->DB->checkForTable( 'message_text' ) )
		{
			$this->registry->output->global_message = $this->lang->words['re_msgsconverted'];
			$this->toolsSplashScreen();
			return;
		}
		
		
		/* Select max topic ID thus far */
		$_tmp = $this->DB->buildAndFetch( array( 'select' => 'MAX(mt_id) as max',
												 'from'   => 'message_topics' ) );
												
		$topicID = intval( $_tmp['max'] );
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'message_text',
								 'order'  => 'msg_id ASC',
								 'limit'  => array( $start, $pergo ) ) );
								
		$o = $this->DB->execute();
		
		while( $post = $this->DB->fetch( $o ) )
		{
			$seen++;
			
			/* Make sure all data is valid */
			if ( intval( $post['msg_sent_to_count'] ) < 1 )
			{
				continue;
			}
			
			/* a little set up */
			$oldTopics = array();
			
			/* Now fetch all topics */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'message_topics_old',
									 'where'  => 'mt_msg_id=' . intval( $post['msg_id'] ) ) );
									
			$t = $this->DB->execute();
			
			while( $topic = $this->DB->fetch( $t ) )
			{
				/* Got any data? */
				if ( ! $topic['mt_from_id'] OR ! $topic['mt_to_id'] )
				{
					continue;
				}
				
				$oldTopics[ $topic['mt_id'] ] = $topic;
			}
			
			/* Fail safe */
			if ( ! count( $oldTopics ) )
			{
				continue;
			}
			
			/* Increment number */
			$topicID++;
			
			/* Add in the post */
			$this->DB->insert( 'message_posts', array( 'msg_topic_id'      => $topicID,
													   'msg_date'          => $post['msg_date'],
													   'msg_post'          => $post['msg_post'],
													   'msg_post_key'      => $post['msg_post_key'],
													   'msg_author_id'     => $post['msg_author_id'],
													   'msg_ip_address'    => $post['msg_ip_address'],
													   'msg_is_first_post' => 1 ) );
			$postID = $this->DB->getInsertId();
			
			/* Define some stuff. "To" member is added last in IPB 2 */
			$_tmp       = $oldTopics;
			ksort( $_tmp );
			$topicData  = array_pop( $_tmp ); 
			$_invited   = array();
			$_seenOwner = array();
			$_isDeleted = 0;
			
			/* Add the member rows */
			foreach( $oldTopics as $mt_id => $data )
			{
				/* Prevent SQL error with unique index: Seen the owner ID already? */
				if ( $_seenOwner[ $data['mt_owner_id'] ] )
				{
					continue;
				}
				
				$_seenOwner[ $data['mt_owner_id'] ] = $data['mt_owner_id'];
				
				/* Build invited - does not include 'to' person */
				if ( $data['mt_owner_id'] AND ( $post['msg_author_id'] != $data['mt_owner_id'] ) AND ( $topicData['mt_to_id'] != $data['mt_owner_id'] ) )
				{
					$_invited[ $data['mt_owner_id'] ] = $data['mt_owner_id'];
				}
				
				$_isSent  = ( $data['mt_vid_folder'] == 'sent' )   ? 1 : 0;
				$_isDraft = ( $data['mt_vid_folder'] == 'unsent' ) ? 1 : 0;
				
				$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $data['mt_owner_id'],
																	'map_topic_id'    => $topicID,
																	'map_folder_id'   => ( $_isDraft ) ? 'drafts' : 'myconvo',
																	'map_read_time'   => ( $data['mt_user_read'] ) ? $data['mt_user_read'] : ( $data['mt_read'] ? time() : 0 ),
																	'map_user_active' => 1,
																	'map_user_banned' => 0,
																	'map_has_unread'  => 0, //( $data['mt_read'] ) ? 0 : 1,
																	'map_is_system'   => 0,
																	'map_is_starter'  => ( $data['mt_owner_id'] == $post['msg_author_id'] ) ? 1 : 0 ) );
				
			}
			
			/* Now, did we see the author? If not, add them too but as inactive */
			if ( ! $_seenOwner[ $post['msg_author_id'] ] )
			{
				$_isDeleted = 1;
				
				/*$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $post['msg_author_id'],
																	'map_topic_id'    => $topicID,
																	'map_folder_id'   => 'myconvo',
																	'map_read_time'   => 0,
																	'map_user_active' => 0,
																	'map_user_banned' => 0,
																	'map_has_unread'  => 0,
																	'map_is_system'   => 0,
																	'map_is_starter'  => 1 ) );*/
			}
			
			$_isSent  = ( $topicData['mt_vid_folder'] == 'sent' )   ? 1 : 0;
			$_isDraft = ( $topicData['mt_vid_folder'] == 'unsent' ) ? 1 : 0;
			
			/* Add the topic */
			$this->DB->insert( 'message_topics', array( 'mt_id'			     => $topicID,
														'mt_date'		     => $topicData['mt_date'],
														'mt_title'		     => $topicData['mt_title'],
														'mt_starter_id'	     => $post['msg_author_id'],
														'mt_start_time'      => $post['msg_date'],
														'mt_last_post_time'  => $post['msg_date'],
														'mt_invited_members' => serialize( array_keys( $_invited ) ),
														'mt_to_count'		 => count(  array_keys( $_invited ) ) + 1,
														'mt_to_member_id'	 => $topicData['mt_to_id'],
														'mt_replies'		 => 0,
														'mt_last_msg_id'	 => $postID,
														'mt_first_msg_id'    => $postID,
														'mt_is_draft'		 => $_isDraft,
														'mt_is_deleted'		 => $_isDeleted,
														'mt_is_system'		 => 0 ) );
														
			$converted++;
		}
		
		/* What to do? */
		if ( $seen )
		{
			$this->request['st'] = $start + $pergo;
			
			/* We did some, go check again.. */
			$this->registry->output->global_message = "Checked " . $this->request['st'] . " PMs so far. This cycle: $converted Private Messages converted into conversations..";
			
			/* Re-do Page */
			$url  = "{$this->settings['base_url']}{$this->form_code}&do=".$this->request['do'].'&st=' . $this->request['st'];
		}
		else
		{
			/* Update all members */
			$this->DB->update( 'members', array( 'msg_count_reset' => 1 ) );
			
			$this->registry->output->global_message = $this->lang->words['re_pmsconverted'];

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=tools";
		}
		
		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $this->registry->output->global_message, $time );
	}

	/**
	 * Tools: Ban Settings
	 *
	 * @access	public
	 * @return	void
	 */
	public function toolBanSettings()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$bomb        = array();
		$ban         = array();
		$ip_count    = 0;
		$email_count = 0;
		$name_count  = 0;

		//-----------------------------------------
		// Get current entries
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters', 'order' => 'ban_date desc' ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			$ban[ $r['ban_type'] ][ $r['ban_content'] ] = $r;
		}

		//-----------------------------------------
		// Get $INFO (again) ip email name
		//-----------------------------------------

		require( DOC_IPS_ROOT_PATH."conf_global.php" );

		//-----------------------------------------
		// IP
		//-----------------------------------------

		if ( $INFO['ban_ip'] )
		{
			$bomb = explode( '|', $INFO['ban_ip'] );

			if ( is_array( $bomb ) and count( $bomb ) )
			{
				foreach( $bomb as $bang )
				{
					if ( ! is_array($ban['ip'][ $bang ]) )
					{
						$this->DB->insert( 'banfilters', array( 'ban_type' => 'ip', 'ban_content' => $bang, 'ban_date' => time() ) );

						$ip_count++;
					}
				}
			}
		}

		//-----------------------------------------
		// EMAIL
		//-----------------------------------------

		if ( $INFO['ban_email'] )
		{
			$bomb = explode( '|', $INFO['ban_email'] );

			if ( is_array( $bomb ) and count( $bomb ) )
			{
				foreach( $bomb as $bang )
				{
					if ( ! is_array($ban['email'][ $bang ]) )
					{
						$this->DB->insert( 'banfilters', array( 'ban_type' => 'email', 'ban_content' => $bang, 'ban_date' => time() ) );

						$email_count++;
					}
				}
			}
		}

		//-----------------------------------------
		// EMAIL
		//-----------------------------------------

		if ( $INFO['ban_names'] )
		{
			$bomb = explode( '|', $INFO['ban_names'] );

			if ( is_array( $bomb ) and count( $bomb ) )
			{
				foreach( $bomb as $bang )
				{
					if ( ! is_array($ban['name'][ $bang ]) )
					{
						$this->DB->insert( 'banfilters', array( 'ban_type' => 'name', 'ban_content' => $bang, 'ban_date' => time() ) );

						$name_count++;
					}
				}
			}
		}

		$this->registry->output->global_message = sprintf( $this->lang->words['re_bansimport'], $ip_count, $email_count, $name_count );

		$this->cache->rebuildCache( 'banfilters', 'global' );

		$this->toolsSplashScreen();
	}

	/**
	 * 2.0.0 Tools: Converge
	 *
	 * @access	public
	 * @return	void
	 */
	public function toolsConverge()
	{
		//-----------------------------------------
		// Get all validating members...
		//-----------------------------------------

		$to_unconverge    = array();
		$unconverge_count = 0;

		$this->DB->build( array( 'select' => 'member_id, email, member_group_id', 'from' => 'members', 'where' => 'member_group_id='.$this->settings['auth_group'] ) );
		$this->DB->execute();

		while( $m = $this->DB->fetch() )
		{
			if ( preg_match( "#^{$m['member_id']}\-#", $m['email'] ) )
			{
				$to_unconverge[] = $m['member_id'];
			}
		}

		$unconverge_count = intval( count( $to_unconverge ) );

		if ( $unconverge_count )
		{
			foreach( $to_unconverge as $mid )
			{
				$this->DB->update( 'members'     , array( 'member_group_id' => $this->settings['member_group'] ), 'member_id='.$mid );
				$this->DB->update( 'profile_portal', array( 'bio'   => 'dupemail'                       ), 'pp_member_id='.$mid );
			}
		}

		//-----------------------------------------
		// Time to move on dude
		//-----------------------------------------

		$this->registry->output->global_message = (sprintf( $this->lang->words['re_convergerest'], $unconverge_count ) );
		$this->toolsSplashScreen();
	}

	/**
	 * 2.0.0 Tools: Dupe Settings
	 *
	 * @access	public
	 * @return	void
	 */
	public function toolsDupeSettings()
	{
		//-----------------------------------------
		// Remove dupe categories
		//-----------------------------------------

		$title_id_to_keep    = array();
		$title_id_to_delete  = array();
		$title_deleted_count = 0;

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'order' => 'conf_title_id' ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			if ( $title_id_to_keep[ $r['conf_title_title'] ] )
			{
				$title_id_to_delete[ $r['conf_title_id'] ] = $r['conf_title_id'];
			}
			else
			{
				$title_id_to_keep[ $r['conf_title_title'] ] = $r['conf_title_id'];
			}
		}

		if ( count( $title_id_to_delete ) )
		{
			$this->DB->buildAndFetch( array( 'delete' => 'conf_settings_titles', 'where' => 'conf_title_id IN ('.implode( ',', $title_id_to_delete ).')' ) );
		}

		$title_deleted_count = intval( count($title_id_to_delete) );

		//-----------------------------------------
		// Remove dupe settings
		//-----------------------------------------

		$setting_id_to_keep       = array();
		$setting_id_to_delete     = array();
		$setting_id_deleted_count = 0;

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'order' => 'conf_id' ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			if ( $setting_id_to_keep[ $r['conf_title'].','.$r['conf_key'] ] )
			{
				$setting_id_to_delete[ $r['conf_id'] ] = $r['conf_id'];
			}
			else
			{
				$setting_id_to_keep[ $r['conf_title'].','.$r['conf_key'] ] = $r['conf_id'];
			}
		}

		if ( count( $setting_id_to_delete ) )
		{
			$this->DB->buildAndFetch( array( 'delete' => 'conf_settings', 'where' => 'conf_id IN ('.implode( ',', $setting_id_to_delete ).')' ) );
		}

		$setting_deleted_count = intval( count($setting_id_to_delete) );

		//-----------------------------------------
		// Time to move on dude
		//-----------------------------------------

		$this->registry->output->global_message = sprintf( $this->lang->words['re_deletetitle'], $title_deleted_count, $setting_deleted_count ) ;
		$this->toolsSplashScreen();
	}

	/**
	 * 2.1.0 Tools: Dupe Settings
	 *
	 * @access	public
	 * @return	void
	 */
	public function tools210DupeSettings()
	{
		//-----------------------------------------
		// Remove dupe categories
		//-----------------------------------------

		$title_id_to_keep    = array();
		$title_id_to_delete  = array();
		$title_deleted_count = 0;
		$msg                 = '';

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'order' => 'conf_title_id DESC' ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			if ( $title_id_to_keep[ $r['conf_title_title'] ] )
			{
				$title_id_to_delete[ $r['conf_title_id'] ] = $r['conf_title_id'];

				$msg .= sprintf( $this->lang->words['re_deletingid'], $r['conf_title_title'], $r['conf_title_id'] ) . "<br />";
			}
			else
			{
				$title_id_to_keep[ $r['conf_title_title'] ] = $r['conf_title_id'];
				$msg .= sprintf( $this->lang->words['re_keepingid'],  $r['conf_title_title'], $r['conf_title_id'] ) . "<br />";
			}
		}

		if ( count( $title_id_to_delete ) )
		{
			$this->DB->buildAndFetch( array( 'delete' => 'core_sys_conf_settings', 'where' => 'conf_title_id IN ('.implode( ',', $title_id_to_delete ).')' ) );
		}

		$title_deleted_count = intval( count($title_id_to_delete) );

		//-----------------------------------------
		// Time to move on dude
		//-----------------------------------------

		$this->registry->output->global_message = sprintf( $this->lang->words['re_duplicatetitle'], $title_deleted_count, $msg );
		$this->toolsSplashScreen();
	}
		
	/**
	 * 2.1.0 Tools: Calendar Events
	 *
	 * @access	public
	 * @return	void
	 */
	public function tools210Calevents()
	{
		$start = intval($_GET['st']);
		$lend  = 50;
		$end   = $start + $lend;
		$max   = intval($_GET['max']);

		//-----------------------------------------
		// Check to make sure table exists
		//-----------------------------------------

		if ( ! $this->DB->checkForTable( 'calendar_events' ) )
		{
			$this->registry->output->global_message = $this->lang->words['re_calremoved'];
			$this->toolsSplashScreen();
			return;
		}

		//-----------------------------------------
		// Do we need to run this tool?
		//-----------------------------------------

		if ( ! $max )
		{
			$original = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as max', 'from' => 'calendar_events' ) );
			$new      = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as max', 'from' => 'cal_events' ) );

			if ( $new['max'] >= $original['max'] OR ! $original['max'] )
			{
				$this->registry->output->global_message = $this->lang->words['re_calalready'];
				$this->toolsSplashScreen();
			}
		}

		$max = intval( $original['max'] );

		//-----------------------------------------
		// In steps...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from'   => 'calendar_events', 'limit'  => array( $start, $lend ) ) );
		$o = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->DB->getTotalRows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------

			while ( $r = $this->DB->fetch($o) )
			{
				$recur_remap = array( 'w' => 1,
									  'm' => 2,
									  'y' => 3 );

				$begin_date        = IPSTime::date_getgmdate( $r['unix_stamp']     );
				$end_date          = IPSTime::date_getgmdate( $r['end_unix_stamp'] );

				if ( ! $begin_date OR ! $end_date )
				{
					continue;
				}

				$day               = $begin_date['mday'];
				$month             = $begin_date['mon'];
				$year              = $begin_date['year'];

				$end_day           = $end_date['mday'];
				$end_month         = $end_date['mon'];
				$end_year          = $end_date['year'];

				$_final_unix_from  = gmmktime(0, 0, 0, $month, $day, $year );

				//-----------------------------------------
				// Recur or ranged...
				//-----------------------------------------

				if ( $r['event_repeat'] OR $r['event_ranged'] )
				{
					$_final_unix_to = gmmktime(23, 59, 59, $end_month, $end_day, $end_year);
				}
				else
				{
					$_final_unix_to = 0;
				}

				$new_event = array( 'event_calendar_id' => 1,
									'event_member_id'   => $r['userid'],
									'event_content'     => $r['event_text'],
									'event_title'       => $r['title'],
									'event_smilies'     => $r['show_emoticons'],
									'event_perms'       => $r['read_perms'],
									'event_private'     => $r['priv_event'],
									'event_approved'    => 1,
									'event_unixstamp'   => $r['unix_stamp'],
									'event_recurring'   => ( $r['event_repeat'] && $recur_remap[ $r['repeat_unit'] ] ) ? $recur_remap[ $r['repeat_unit'] ] : 0,
									'event_tz'          => 0,
									'event_unix_from'   => $_final_unix_from,
									'event_unix_to'     => $_final_unix_to );

				//-----------------------------------------
				// INSERT
				//-----------------------------------------

				$this->DB->insert( 'cal_events', $new_event );
			}

			$this->registry->output->global_message = sprintf( $this->lang->words['re_calstartto'], $start, $end );

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=".$this->request['do'].'&max='.$max.'&st='.$end;
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['re_calsconverted'];

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=tools";
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $this->registry->output->global_message, $time );
	}

	/**
	 * 2.1.0 Tools: Polls
	 *
	 * @access	public
	 * @return	void
	 */
	public function tools210Polls()
	{
		$start     = intval( $this->request['st'] );
		$lend      = 50;
		$end       = $start + $lend;
		$max       = intval( $this->request['max'] );
		$done      = 0;
		$converted = intval( $this->request['conv'] );

		//-----------------------------------------
		// First off.. grab number of polls to convert
		//-----------------------------------------

		if ( ! $max )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as max',
																	   'from'   => 'topics',
																	   'where'  => "poll_state IN ('open', 'close', 'closed')" ) );

			$max   = $total['max'];
		}

		if ( $max < 1 )
		{
			$done = 1;
		}

		//-----------------------------------------
		// In steps...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*',
													  'from'   => 'topics',
													  'where'  => "poll_state IN ('open', 'close', 'closed' )",
													  'limit'  => array( $start, $lend ) ) );
		$o = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->DB->getTotalRows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------

			while ( $r = $this->DB->fetch($o) )
			{
				$converted++;

				$new_poll  = array( 1 => array() );

				$poll_data = $this->DB->buildAndFetch( array( 'select' => '*',
																			   'from'   => 'polls',
																			   'where'  => "tid=".$r['tid']
																	  )      );
				if ( ! $poll_data['pid'] )
				{
					continue;
				}

				if ( ! $poll_data['poll_question'] )
				{
					$poll_data['poll_question'] = $r['title'];
				}

				//-----------------------------------------
				// Kick start new poll
				//-----------------------------------------

				$new_poll[1]['question'] = $poll_data['poll_question'];

				//-----------------------------------------
				// Get OLD polls
				//-----------------------------------------

				$poll_answers = unserialize( stripslashes( $poll_data['choices'] ) );

				reset($poll_answers);

				foreach ( $poll_answers as $entry )
				{
					$id     = $entry[0];
					$choice = $entry[1];
					$votes  = $entry[2];

					$total_votes += $votes;

					if ( strlen($choice) < 1 )
					{
						continue;
					}

					$new_poll[ 1 ]['choice'][ $id ] = $choice;
					$new_poll[ 1 ]['votes'][ $id  ] = $votes;
				}

				//-----------------------------------------
				// Got something?
				//-----------------------------------------

				if ( count( $new_poll[1]['choice'] ) )
				{
					$this->DB->update( 'polls' , array( 'choices'    => serialize( $new_poll ) ), 'tid='.$r['tid'] );
					$this->DB->update( 'topics', array( 'poll_state' => 1 ), 'tid='.$r['tid'] );
				}

				//-----------------------------------------
				// All done?
				//-----------------------------------------

				if ( $converted >= $max )
				{
					$done = 1;
					continue;
				}
			}
		}
		else
		{
			$done = 1;
		}


		if ( ! $done )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['re_pollstartto'], $start, $end, $max );

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=".$this->request['do'].'&max='.$max.'&st='.$end.'&conv='.$converted;
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['re_pollconverted'];

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=tools";
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $this->registry->output->global_message, 1 );
	}

	/**
	 * 2.2.0 Tools: Contacts
	 * DEPRECATED
	 *
	 * @access	public
	 * @return	void
	 */
	public function tools220Contacts()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$start           = intval($_GET['st']);
		$lend            = 50;
		$end             = $start + $lend;
		$done            = 0;
		$updated         = 0;

		//-----------------------------------------
		// Get lib
		//-----------------------------------------

		require_once( IPSLib::getAppDir('members') . '/sources/friends.php' );
		$profile           =  new profileFriendsLib( $this->registry );


		//-----------------------------------------
		// OK..
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*',
											 	 'from'   => 'contacts',
											 	 'where'  => 'allow_msg=1',
											     'limit'  => array( $start, $lend ) ) );

		$o = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->DB->getTotalRows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------

			while( $row = $this->DB->fetch( $o ) )
			{
				//-----------------------------------------
				// Already a friend
				//-----------------------------------------

				$friend = $this->DB->buildAndFetch( array( 'select' => '*',
																			'from'   => 'profile_friends',
																			'where'  => 'friends_member_id=' . intval( $row['member_id'] ) . ' AND friends_friend_id=' . intval( $row['contact_id'] ) ) );

				if ( ! $friend['friends_id'] )
				{
					//-----------------------------------------
					// Add to DB
					//-----------------------------------------

					$this->DB->insert( 'profile_friends', array( 'friends_member_id' => $row['member_id'],
																			  'friends_friend_id' => $row['contact_id'],
																			  'friends_approved'  => 1,
																			  'friends_added'     => time() ) );

					//-----------------------------------------
					// Rebuild...
					//-----------------------------------------

					$profile->recacheFriends( array( 'member_id' => $row['member_id'] ) );

					$updated++;
				}

				$this->DB->delete( "contacts", "id={$row['id']}" );
			}
		}
		else
		{
			$done = 1;
		}

		//-----------------------------------------
		// Done?
		//-----------------------------------------

		if ( ! $done )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['re_contactsstartto'],$start, $end, $updated );

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=".$this->request['do'].'&st='.$end;
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['re_contactsup'];

			$url  = "{$this->settings['base_url']}{$this->form_code}&do=tools";
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $this->registry->output->global_message, 1 );
	}

	/**
	 * Cleanup Tools Splash Screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function toolsSplashScreen()
	{
		/* Output */
		$this->registry->output->html .= $this->html->toolsSplashScreen();
	}

	/**
	 * Count stats
	 *
	 * @access	public
	 * @return	void
	 */
	public function doCount()
	{
		if ( (! $this->request['posts']) and (! $this->request['online']) and (! $this->request['members'] ) and (! $this->request['lastreg'] ) )
		{
			$this->registry->output->showError( $this->lang->words['re_nothing'], 11154 );
		}

		$stats = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='stats'" ) );

		$stats = unserialize( IPSText::stripslashes( $stats['cs_value'] ) );

		if ($this->request['posts'])
		{
			$topics = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as tcount',
																 	 'from'   => 'topics',
												 				 	 'where'  => 'approved=1' ) );

			$posts  = $this->DB->buildAndFetch( array( 'select' => 'SUM(posts) as replies',
																	 'from'   => 'topics',
																	 'where'  => 'approved=1' ) );

			$stats['total_topics']  = $topics['tcount'];
			$stats['total_replies'] = $posts['replies'];
		}

		if ($this->request['members'])
		{
			$this->DB->build( array( 'select' => 'count(member_id) as members', 'from' => 'members', 'where' => "member_group_id <> '{$this->settings['auth_group']}'" ) );
			$this->DB->execute();

			$r = $this->DB->fetch();
			$stats['mem_count'] = intval($r['members']);
		}

		if ($this->request['lastreg'])
		{
			$this->DB->build( array( 'select' => 'member_id, name, members_display_name',
										  'from'   => 'members',
										  'where'  => "member_group_id <> '{$this->settings['auth_group']}' AND members_display_name != '' AND members_display_name " . $this->DB->buildIsNull( false ),
										  'order'  => "member_id DESC",
										  'limit'  => array(0,1) ) );
			$this->DB->execute();

			$r = $this->DB->fetch();
			$stats['last_mem_name'] = $r['members_display_name'] ? $r['members_display_name'] : $r['name'];
			$stats['last_mem_id']   = $r['member_id'];
		}

		if ($this->request['online'])
		{
			$stats['most_date'] = time();
			$stats['most_count'] = 1;
		}

		if ( count($stats) > 0 )
		{
			$this->cache->setCache( 'stats', $stats, array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['re_nothing'], 11155 );
		}

		$this->registry->output->global_message = $this->lang->words['re_statsrecount'];

		$this->registry->output->doneScreen($this->lang->words['re_statsrecount'], $this->lang->words['re_statscenter'], "{$this->settings['base_url']}{$this->form_code}", 'redirect' );
	}

	/**
	 * Clean out photos
	 *
	 * @access	public
	 * @return	void
	 */
	public function cleanPhotos()
	{
		/* Upload Class */
		require_once( IPS_KERNEL_PATH.'classUpload.php' );
		$upload = new classUpload();

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$display = $end + $start;
		$output = array();

		//-----------------------------------------
		// Pop open the directory and
		// peek inside...
		//-----------------------------------------

		$i = 0;
		
		try
		{
			foreach( new DirectoryIterator( $this->settings['upload_dir']  ) as $file )
 			{
 				if ( strstr( $file->getFilename(), 'photo-' ) )
 				{
 					$fullfile = $this->settings['upload_dir'].'/'.$file->getFilename();
        	
 					$i++;
        	
 					//-----------------------------------------
 					// Already started?
 					//-----------------------------------------
        	
 					if ( $start > $i )
 					{
 						continue;
 					}
        	
 					//-----------------------------------------
 					// Done for this iteration?
 					//-----------------------------------------
        	
 					if ( $i > $display )
 					{
 						break;
 					}
        	
 					//-----------------------------------------
 					// Try and get attach row
 					//-----------------------------------------
        	
 					$found = $this->DB->buildAndFetch( array( 'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => "pp_main_photo='{$file->getFilename()}' OR pp_thumb_photo='{$file->getFilename()}'" ) );
        	
 					if ( ! $found['pp_member_id'] )
 					{
 						@unlink( $fullfile );
 						$output[] = "<span style='color:red'>{$this->lang->words['re_removedorph']} {$file->getFilename()}</span>";
 					}
 					else
 					{
 						$output[] = "<span style='color:gray'>{$this->lang->words['re_attachedok']} {$file->getFilename()}</span>";
 					}
				}
 			}
		} catch ( Exception $e ) {}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( $i < $display)
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'] . implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $display );
			$text = $thisgoeshere . implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$display}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Clean out avatars
	 *
	 * @access	public
	 * @return	void
	 */
	public function cleanAvatars()
	{
		/* Upload Class */
		require_once( IPS_KERNEL_PATH.'classUpload.php' );
		$upload = new classUpload();

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Pop open the directory and
		// peek inside...
		//-----------------------------------------

		$i = 0;
		
		try
		{
 			foreach( new DirectoryIterator( $this->settings['upload_dir'] ) as $file )
 			{
 				if ( strstr( $file->getFilename(), 'av-' ) )
 				{
 					$fullfile = $this->settings['upload_dir'].'/'.$file->getFilename();
        	
 					$i++;
        	
 					//-----------------------------------------
 					// Already started?
 					//-----------------------------------------
        	
 					if ( $start > $i )
 					{
 						continue;
 					}
        	
 					//-----------------------------------------
 					// Done for this iteration?
 					//-----------------------------------------
        	
 					if ( $i > $dis )
 					{
 						break;
 					}
        	
 					//-----------------------------------------
 					// Try and get attach row
 					//-----------------------------------------
        	
 					$found = $this->DB->buildAndFetch( array( 'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => "avatar_location='{$file->getFilename()}' or avatar_location='upload:{$file->getFilename()}'" ) );
        	
 					if ( ! $found['pp_member_id'] )
 					{
 						@unlink( $fullfile );
 						$output[] = "<span style='color:red'>{$this->lang->words['re_removedorph']} $file</span>";
 					}
 					else
 					{
 						$output[] = "<span style='color:gray'>{$this->lang->words['re_attachedok']} $file</span>";
 					}
				}
 			}
		} catch ( Exception $e ) {}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( $i < $dis)
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomplete'] . implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere . implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Clean out attachments
	 *
	 * @access	public
	 * @return	void
	 */
	public function cleanAttachments()
	{
		/* Upload Class */
		require_once( IPS_KERNEL_PATH.'classUpload.php' );
		$upload = new classUpload();

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Pop open the directory and
		// peek inside...
		//-----------------------------------------

		$i = 0;
		try
		{
 			foreach( new DirectoryIterator( $this->settings['upload_dir'] ) as $file )
 			{
				if ( $file->getFilename() == '.' OR $file->getFilename() == '..' )
				{
					continue;
				}
        	
	 			$fullfile = $this->settings['upload_dir'] . '/' . $file->getFilename();
        	
	 			if( is_dir( $fullfile ) )
	 			{
			 		$ndh = opendir( $fullfile );
        			
					try
					{
			 			foreach( new DirectoryIterator( $fullfile ) as $nfile )
			 			{
							if ( $nfile->getFilename() == '.' OR $nfile->getFilename() == '..' )
							{
								continue;
							}
        	        	
			 				if ( strstr( $nfile->getFilename(), 'post-' ) )
			 				{
			 					$i++;
        	        	
			 					//-----------------------------------------
			 					// Already started?
			 					//-----------------------------------------
        	        	
			 					if ( $start > $i )
			 					{
			 						continue;
			 					}
        	        	
			 					//-----------------------------------------
			 					// Done for this iteration?
			 					//-----------------------------------------
        	        	
			 					if ( $i > $dis )
			 					{
			 						break;
			 					}
        	        	
			 					//-----------------------------------------
			 					// Try and get attach row
			 					//-----------------------------------------
        	        	
			 					$found = $this->DB->buildAndFetch( array( 'select' => 'attach_id', 'from' => 'attachments', 'where' => "attach_location='{$file->getFilename()}/{$nfile->getFilename()}' OR attach_thumb_location='{$file->getFilename()}/{$nfile->getFilename()}'" ) );
        	        	
			 					if ( ! $found['attach_id'] )
			 					{
			 						@unlink( $fullfile . '/' . $nfile );
			 						$output[] = "<span style='color:red'>{$this->lang->words['re_removedorph']} {$nfile->getFilename()}</span>";
			 					}
			 					else
			 					{
			 						$output[] = "<span style='color:gray'>{$this->lang->words['re_attachedok']} {$nfile->getFilename()}</span>";
			 					}
							}
						}
    				} catch ( Exception $e ) {}
					closedir( $ndh );
				}
 				else if ( strstr( $file, 'post-' ) )
 				{
 					$i++;
        	
 					//-----------------------------------------
 					// Already started?
 					//-----------------------------------------
        	
 					if ( $start > $i )
 					{
 						continue;
 					}
        	
 					//-----------------------------------------
 					// Done for this iteration?
 					//-----------------------------------------
        	
 					if ( $i > $dis )
 					{
 						break;
 					}
        	
 					//-----------------------------------------
 					// Try and get attach row
 					//-----------------------------------------
        	
 					$found = $this->DB->buildAndFetch( array( 'select' => 'attach_id', 'from' => 'attachments', 'where' => "attach_location='{$file->getFilename()}' OR attach_thumb_location='{$file->getFilename()}'" ) );
        	
 					if ( ! $found['attach_id'] )
 					{
 						@unlink( $fullfile );
 						$output[] = "<span style='color:red'>{$this->lang->words['re_removedorph']} {$file->getFilename()}</span>";
 					}
 					else
 					{
 						$output[] = "<span style='color:gray'>{$this->lang->words['re_attachedok']} {$file->getFilename()}</span>";
 					}
				}
 			}
		} catch ( Exception $e ) {}
		
		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( $i < $dis)
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuidcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere.implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Rebuild Attachment Data
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildAttachdata()
	{
		/* Upload Class */
		require_once( IPS_KERNEL_PATH.'classUpload.php' );
		$upload = new classUpload();

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'attach_id', 'from' => 'attachments', 'limit' => array($dis,1) ) );
		$max = intval( $tmp['attach_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'order' => 'attach_id ASC', 'limit' => array($start,$end) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Get ext
			//-----------------------------------------

			$update = array();

			$update['attach_ext'] = $upload->_getFileExtension( $r['attach_file'] );

			if ( $r['attach_location'] )
			{
				if ( file_exists( $this->settings['upload_dir'].'/'.$r['attach_location'] ) )
				{
					$update['attach_filesize'] = @filesize( $this->settings['upload_dir'].'/'.$r['attach_location'] );

					if( $r['attach_is_image'] )
					{
						$dims = @getimagesize( $this->settings['upload_dir'].'/'.$r['attach_location'] );

						if( $dims[0] AND $dims[1] )
						{
							$update['attach_img_width'] = $dims[0];
							$update['attach_img_height'] = $dims[1];
						}
					}
				}
			}

			if ( count( $update ) )
			{
				$this->DB->update( 'attachments', $update, 'attach_id='.$r['attach_id'] );
			}

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Rebuild Photos
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildPhotos()
	{
		/* Image Class */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );
		require_once( IPS_KERNEL_PATH . 'classImageGd.php' );

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array(  'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => "pp_main_photo != ''", 'order' => 'pp_member_id ASC', 'limit' => array($dis,1)  ) );
		$max = intval( $tmp['pp_member_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'profile_portal', 'order' => 'pp_member_id ASC', 'where' => "pp_main_photo != ''", 'limit' => array($start,$end) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			if ( $r['pp_thumb_photo'] and ( $r['pp_thumb_photo'] != $r['pp_main_photo'] ) )
			{
				if ( file_exists( $this->settings['upload_dir'].'/'.$r['pp_thumb_photo'] ) )
				{
					if ( ! @unlink( $this->settings['upload_dir'].'/'.$r['pp_thumb_photo'] ) )
					{
						$output[] = $this->lang->words['re_noremove'].$r['pp_thumb_photo'];
						continue;
					}
				}
			}
			
			if( !file_exists( $this->settings['upload_dir'] . '/' . $r['pp_main_photo'] ) )
			{
				$output[] = $this->lang->words['re_noresizefound'] . $r['pp_main_photo'];
				continue;
			}

			$photo_data = array();
			$thumb_data = array();

			$image = new classImageGd();

			$image->init( array( 'image_path' => $this->settings['upload_dir'], 'image_file' => $r['pp_main_photo'] ) );

			$thumb_data = $image->resizeImage( 50, 50 );
			$image->writeImage( $this->settings['upload_dir'] . '/' . $r['pp_thumb_photo'] );

			$photo_data['pp_thumb_width']   = intval($thumb_data['newWidth']);
			$photo_data['pp_thumb_height']  = intval($thumb_data['newHeight']);
			$photo_data['pp_thumb_photo'] 	= $r['pp_thumb_photo'];

			if ( count( $photo_data ) )
			{
				$this->DB->update( 'profile_portal', $photo_data, 'pp_member_id='.$r['pp_member_id'] );

				$output[] = $this->lang->words['re_resized'].$r['pp_main_photo'];
			}

			unset($image);

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Rebuild Post Thumbnails
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildThumbnails()
	{
		require_once( IPS_KERNEL_PATH . 'classImage.php' );
		require_once( IPS_KERNEL_PATH . 'classImageGd.php' );

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'attach_id', 'from' => 'attachments', 'where' => "attach_rel_module IN('post','msg')", 'limit' => array($dis,1)  ) );
		$max = intval( $tmp['attach_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module IN('post','msg')", 'order' => 'attach_id ASC', 'limit' => array($start,$end) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			if ( $r['attach_is_image'] )
			{
				if ( $r['attach_thumb_location'] and ( $r['attach_thumb_location'] != $r['attach_location'] ) )
				{
					if ( file_exists( $this->settings['upload_dir'].'/'.$r['attach_thumb_location'] ) )
					{
						if ( ! @unlink( $this->settings['upload_dir'].'/'.$r['attach_thumb_location'] ) )
						{
							$output[] = $this->lang->words['re_noremove'].$r['attach_thumb_location'];
							continue;
						}
					}
				}

				$attach_data	= array();
				$thumbnail		= $r['attach_thumb_location'] ? $r['attach_thumb_location']
									: preg_replace( "/^(.*)\.(.+?)$/", "\\1_thumb.\\2", $r['attach_location'] );

				$image = new classImageGd();

				$image->init( array(
				                         'image_path'     => $this->settings['upload_dir'],
				                         'image_file'     => $r['attach_location'],
				               )          );

				$return = $image->resizeImage( $this->settings['siu_width'], $this->settings['siu_height'] );
				$image->writeImage( $this->settings['upload_dir'] . '/' . $thumbnail );

				$attach_data['attach_thumb_width']    = intval($return['newWidth'] ? $return['newWidth'] : $image->cur_dimensions['width']);
				$attach_data['attach_thumb_height']   = intval($return['newHeight'] ? $return['newHeight'] : $image->cur_dimensions['height']);
				$attach_data['attach_thumb_location'] = $thumbnail;

				if ( count( $attach_data ) )
				{
					$this->DB->update( 'attachments', $attach_data, 'attach_id='.$r['attach_id'] );

					$output[] = $this->lang->words['re_resized'].$r['attach_location'];
				}

				unset($image);
			}

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}
	
	/**
	 * Rebuild Messenger Totals
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildMsgCounts()
	{
		//-----------------------------------------
		// Grab messenger lib
		//-----------------------------------------

		require_once( IPSLib::getAppDir( "members" ) . '/sources/classes/messaging/messengerFunctions.php' );
		$messengerFunctions = new messengerFunctions( $this->registry );

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis   = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'limit' => array($dis,1)  ) );
		$max = intval( $tmp['member_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'order' => 'member_id ASC', 'limit' => array($start,$end) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$messengerFunctions->resetMembersFolderCounts( $r['member_id'] );
			$messengerFunctions->resetMembersTotalTopicCount( $r['member_id'] );
			$messengerFunctions->resetMembersNewTopicCount( $r['member_id'] );

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Rebuild Post Count
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildPostCount()
	{
		//-----------------------------------------
		// Forums not to count?
		//-----------------------------------------

		$forums = array();

		foreach( $this->registry->class_forums->forum_by_id as $data )
		{
			if ( ! $data['inc_postcount'] )
			{
				$forums[] = $data['id'];
			}
		}

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis   = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'limit' => array($dis,1)  ) );
		$max = intval( $tmp['member_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'member_id, name', 'from' => 'members', 'order' => 'member_id ASC', 'limit' => array($start,$end) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			if ( ! count( $forums ) )
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'posts', 'where' => 'queued != 1 AND author_id='.$r['member_id'] ) );
			}
			else
			{
				$this->DB->build( array( 'select' 	=> 'count(p.pid) as count',
														 'from'		=> array( 'posts' => 'p' ),
														 'where'	=> 'p.queued <> 1 AND p.author_id='.$r['member_id'].' AND t.forum_id NOT IN ('.implode(",",$forums).')',
														 'add_join'	=> array( 1 => array( 'type'	=> 'left',
														 								  'from'	=> array( 'topics' => 't' ),
														 								  'where'	=> 't.tid=p.topic_id'
														 					)			)
												)		);
				$this->DB->execute();

				$count = $this->DB->fetch();
			}

			$new_post_count = intval( $count['count'] );

			$this->DB->update( 'members', array( 'posts' => $new_post_count ), 'member_id='.$r['member_id'] );

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}
	
	/**
	 * Rebuild Seo User Names
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildSeoUserNames()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'limit' => array( $dis, 1 )  ) );
		$max = intval( $tmp['member_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'order' => 'member_id ASC', 'limit' => array( $start, $end ) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$this->DB->update( 'members', array( 'members_seo_name' => IPSText::makeSeoTitle( $r['members_display_name'] ) ), "member_id=" . $r['member_id'] );
			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}
	
	
	/**
	 * Rebuild Post Names
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildPostNames()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'limit' => array( $dis, 1 )  ) );
		$max = intval( $tmp['member_id'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'order' => 'member_id ASC', 'limit' => array( $start, $end ) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$seoName = IPSText::makeSeoTitle( $r['members_display_name'] );
			$this->DB->update( 'topics', array( 'starter_name' => $r['members_display_name'], 'seo_first_name' => $seoName ), "starter_id=" . $r['member_id'] );
			$this->DB->update( 'topics', array( 'last_poster_name' => $r['members_display_name'], 'seo_last_name' => $seoName ), "last_poster_id=" . $r['member_id'] );

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Rebuild posts
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildPosts()
	{
		//-----------------------------------------
		// If it's "none", go back
		//-----------------------------------------
		
		if( !$this->request['type'] OR $this->request['type'] == 'none' )
		{
			$this->rebuildSplashScreen();
			return;
		}

		//-----------------------------------------
		// Only need to rebuild once
		//-----------------------------------------

		$sections		= array();
		$alreadyRebuilt	= $this->DB->buildAndFetch( array( 'select' => 'cs_value', 'from' => 'cache_store', 'where' => "cs_key='isRebuilt'" ) );

		if( $alreadyRebuilt['cs_value'] )
		{
			$sections	= explode( ',', $alreadyRebuilt['cs_value'] );
		}

		//-----------------------------------------
		// Get new parser and old parser
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . "sources/handlers/han_parse_bbcode.php" );
		$parser		= new parseBbcode( $this->registry );
		$oldparser	= new parseBbcode( $this->registry, 'legacy' );

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$last	= 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = intval($this->request['dis']) >=0 ? intval($this->request['dis']) : 0;
		$output = array();
		$plugin	= null;

		$types	= array( 'posts', 'pms', 'cal', 'announce', 'sigs', 'aboutme', 'rules' );
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			//-----------------------------------------
			// Retrieve the RSS links for the header
			//-----------------------------------------
		
			if( file_exists( IPSLib::getAppDir( $app_dir ) . '/extensions/upgradePostRebuild.php' ) )
			{
				require_once( IPSLib::getAppDir( $app_dir ) . '/extensions/upgradePostRebuild.php' );
				$className = "postRebuild_{$app_dir}";
				
				if( class_exists( $className ) )
				{
					$rebuild = new $className( $this->registry );
					
					if( method_exists( $rebuild, "getDropdown" ) )
					{
						$options	= $rebuild->getDropdown();
						
						if( count($options) )
						{
							foreach( $options as $appOption )
							{
								$types[]	= $appOption[0];
								
								if( $appOption[0] == $this->request['type'] )
								{
									$plugin	=& $rebuild;
									break 2;
								}
							}
						}
					}
				}
			}
		}

		$type	= in_array( $this->request['type'], $types ) ? $this->request['type'] : 'posts';

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		switch( $type )
		{
			case 'cal':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'event_id', 'from' => 'cal_events', 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['event_id'] );
			break;

			case 'announce':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'announce_id', 'from' => 'announcements', 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['announce_id'] );
			break;

			case 'pms':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'msg_id', 'from' => 'message_posts', 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['msg_id'] );
			break;

			case 'sigs':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => "signature IS NOT NULL AND signature != ''", 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['pp_member_id'] );
			break;

			case 'aboutme':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => "pp_about_me IS NOT NULL AND pp_about_me != ''", 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['pp_member_id'] );
			break;

			case 'posts':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'pid', 'from' => 'posts', 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['pid'] );
			break;
			
			case 'rules':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'forums', 'where' => "rules_text IS NOT NULL AND rules_text != ''", 'limit' => array($dis,1)  ) );
				$max = intval( $tmp['id'] );
			break;
			
			default:
				$max = $rebuild->getMax( $type, $dis );
			break;
				
		}

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		switch( $type )
		{
			case 'cal':
				$this->DB->build( array( 'select' 	=> 'e.*',
														 'from' 	=> array( 'cal_events' => 'e' ),
														 'order' 	=> 'e.event_id ASC',
														 'where'	=> 'e.event_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=e.event_member_id"
														  						)	)
												) 		);
			break;

			case 'announce':
				$this->DB->build( array( 'select' 	=> 'a.*',
														 'from' 	=> array( 'announcements' => 'a' ),
														 'order' 	=> 'a.announce_id ASC',
														 'where'	=> 'a.announce_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=a.announce_member_id"
														  						)	)
												) 		);
			break;

			case 'pms':
				$this->DB->build( array( 'select' 	=> 'p.*',
														 'from' 	=> array( 'message_posts' => 'p' ),
														 'order' 	=> 'p.msg_id ASC',
														 'where'	=> 'p.msg_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=p.msg_author_id"
														  						)	)
												) 		);
			break;

			case 'sigs':
				$this->DB->build( array( 'select' 	=> 'me.signature, me.pp_member_id',
														 'from' 	=> array( 'profile_portal' => 'me' ),
														 'order' 	=> 'me.pp_member_id ASC',
														 'where'	=> "me.signature IS NOT NULL AND me.signature != '' AND me.pp_member_id > " . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=me.pp_member_id"
														  						)	)
												) 		);
			break;

			case 'aboutme':
				$this->DB->build( array( 'select' 	=> 'pp.pp_about_me, pp.pp_member_id',
														 'from' 	=> array( 'profile_portal' => 'pp' ),
														 'order' 	=> 'pp.pp_member_id ASC',
														 'where'	=> "pp.pp_about_me != '' AND pp.pp_about_me IS NOT NULL AND pp.pp_member_id > " . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=pp.pp_member_id"
														  						)	)
												) 		);
			break;

			case 'posts':
				$this->DB->build( array( 'select' 	=> 'p.*',
														 'from' 	=> array( 'posts' => 'p' ),
														 'order' 	=> 'p.pid ASC',
														 'where'	=> 'p.pid > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														 									'select'	=> 't.forum_id',
														  								  	'from'		=> array( 'topics' => 't' ),
														  								  	'where' 	=> "t.tid=p.topic_id"
														  						),
														  						2 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=p.author_id"
														  						)	)
												) 		);
			break;

			case 'rules':
				$this->DB->build( array( 'select' => '*', 'from' => 'forums', 'order' => 'id ASC', 'where' => "rules_text IS NOT NULL AND rules_text != '' AND id > " . $start, 'limit' => array($end) ) );
			break;

			default:
				$plugin->executeQuery( $type, $start, $end );
			break;
		}

		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Reset
			//-----------------------------------------
			
			$parser->quote_open				= $oldparser->quote_open			= 0;
			$parser->quote_closed			= $oldparser->quote_closed			= 0;
			$parser->quote_error			= $oldparser->quote_error			= 0;
			$parser->error					= $oldparser->error					= '';
			$parser->image_count			= $oldparser->image_count			= 0;
			$parser->parsing_mgroup			= $oldparser->parsing_mgroup		= $r['member_group_id'];
			$parser->parsing_mgroup_others	= $oldparser->parsing_mgroup_others	= $r['mgroup_others'];

			$this->memberData['g_bypass_badwords'] = $this->caches['group_cache'][ $r['member_group_id'] ]['g_bypass_badwords'];

			switch( $type )
			{
				case 'cal':
					$parser->parse_smilies	= $oldparser->parse_smilies	= $r['event_smilies'];
					$parser->parse_html		= $oldparser->parse_html	= 0;
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= 1;
					$parser->parsing_section		= 'calendar';

					$rawpost = $oldparser->preEditParse( $r['event_content'] );
				break;

				case 'announce':
					$parser->parse_smilies	= $oldparser->parse_smilies	= 1;
					$parser->parse_html		= $oldparser->parse_html	= $r['announce_html_enabled'];
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= 1;
					$parser->parse_nl2br	= $oldparser->parse_nl2br	= ( $r['announce_html_enabled'] ? $r['announce_nlbr_enabled'] : 1 );
					$parser->parsing_section		= 'announcement';

					$rawpost = $oldparser->preEditParse( $r['announce_post'] );
				break;

				case 'pms':
					$parser->parse_smilies	= $oldparser->parse_smilies	= 1;
					$parser->parse_html		= $oldparser->parse_html	= $this->settings['msg_allow_html'];
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= $this->settings['msg_allow_code'];
					$parser->parse_nl2br	= $oldparser->parse_nl2br	= 1;
					$parser->parsing_section		= 'pms';

					$rawpost = $oldparser->preEditParse( $r['msg_post'] );
				break;

				case 'sigs':
					$parser->parse_smilies	= $oldparser->parse_smilies	= 0;
					$parser->parse_html		= $oldparser->parse_html	= $this->settings['sig_allow_html'];
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= $this->settings['sig_allow_ibc'];
					$parser->parse_nl2br	= $oldparser->parse_nl2br	= 1;
					$parser->parsing_section		= 'signatures';

					$rawpost = $oldparser->preEditParse( $r['signature'] );
				break;

				case 'aboutme':
					$parser->parse_smilies	= $oldparser->parse_smilies	= $this->settings['aboutme_emoticons'];
					$parser->parse_html		= $oldparser->parse_html	= intval($this->settings['aboutme_html']);
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= $this->settings['aboutme_bbcode'];
					$parser->parse_nl2br	= $oldparser->parse_nl2br	= 1;
					$parser->parsing_section		= 'aboutme';

					$rawpost = $oldparser->preEditParse( $r['pp_about_me'] );
				break;

				case 'posts':
					$parser->parse_smilies	= $oldparser->parse_smilies	= $r['use_emo'];
					$parser->parse_html		= $oldparser->parse_html	= ( $this->registry->class_forums->forum_by_id[ $r['forum_id'] ]['use_html'] AND
																		   $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'] AND
																		   $r['post_htmlstate'] > 0 ) ? 1 : 0;
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= $this->registry->class_forums->forum_by_id[ $r['forum_id'] ]['use_ibc'];
					$parser->parse_nl2br	= $oldparser->parse_nl2br	= ( $r['post_htmlstate'] != 1 ) ? 1 : 0;
					$parser->parsing_section		= 'topics';

					$rawpost = $oldparser->preEditParse( $r['post'] );
				break;
				
				case 'rules':
					$parser->parse_smilies	= $oldparser->parse_smilies	= 1;
					$parser->parse_html		= $oldparser->parse_html	= 1;
					$parser->parse_bbcode	= $oldparser->parse_bbcode	= 1;
					$parser->parse_nl2br	= $oldparser->parse_nl2br	= 1;
					$parser->parsing_section		= 'rules';

					$rawpost = $oldparser->preEditParse( $r['rules_text'] );
				break;
				
				default:
					$plugin->parser		=& $parser;
					$plugin->oldparser	=& $oldparser;

					$rawpost = $plugin->getRawPost( $type, $r );
				break;
			}

			$newpost = $parser->preDbParse( $rawpost );

			//-----------------------------------------
			// Remove old \' escaping
			//-----------------------------------------

			$newpost = str_replace( "\\'", "'", $newpost );

			//-----------------------------------------
			// Convert old dohtml?
			//-----------------------------------------

			$htmlstate = 0;

			if ( strstr( strtolower($newpost), '[dohtml]' ) )
			{
				//-----------------------------------------
				// Can we use HTML?
				//-----------------------------------------

				if ( $type == 'posts' AND $this->registry->class_forums->forum_by_id[ $r['forum_id'] ]['use_html'] )
				{
					$htmlstate = 2;
				}

				$newpost = preg_replace( "#\[dohtml\]#i" , "", $newpost );
				$newpost = preg_replace( "#\[/dohtml\]#i", "", $newpost );
			}
			else
			{
				$htmlstate = intval( $r['post_htmlstate'] );
			}

			//-----------------------------------------
			// Convert old attachment tags
			//-----------------------------------------

			$newpost = preg_replace( "#\[attachmentid=(\d+?)\]#is", "[attachment=\\1:attachment]", $newpost );

			if ( $newpost OR $type == 'sigs' OR $type == 'aboutme' )
			{
				switch( $type )
				{
					case 'posts':
						$this->DB->update( 'posts', array( 'post' => $newpost, 'post_htmlstate' => $htmlstate ), 'pid='.$r['pid'] );
						$last = $r['pid'];
					break;

					case 'pms':
						$this->DB->update( 'message_posts', array( 'msg_post' => $newpost ), 'msg_id='.$r['msg_id'] );
						$last = $r['msg_id'];
					break;

					case 'sigs':
						$this->DB->update( 'profile_portal', array( 'signature' => $newpost ), 'pp_member_id='.$r['pp_member_id'] );
						$last = $r['pp_member_id'];
					break;

					case 'aboutme':
						$this->DB->update( 'profile_portal', array( 'pp_about_me' => $newpost ), 'pp_member_id='.$r['pp_member_id'] );
						$last = $r['pp_member_id'];
					break;

					case 'cal':
						$this->DB->update( 'cal_events', array( 'event_content' => $newpost ), 'event_id='.$r['event_id'] );
						$last = $r['event_id'];
					break;

					case 'announce':
						$this->DB->update( 'announcements', array( 'announce_post' => $newpost ), 'announce_id='.$r['announce_id'] );
						$last = $r['announce_id'];
					break;
					
					case 'rules':
						$this->DB->update( 'forums', array( 'rules_text' => $newpost ), 'id='.$r['id'] );
						$last = $r['id'];
					break;
					
					default:
						$last = $plugin->storeNewPost( $type, $r, $newpost );
					break;
				}
			}

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $last and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;

			$sections[]	= $type;
			$this->DB->replace( 'cache_store', array( 'cs_key' => 'isRebuilt', 'cs_value' => implode( ',', $sections ) ), "cs_key='isRebuilt'" );
			
			if( $type == 'posts' )
			{
				$this->DB->delete( 'content_cache_posts' );
			}
			else if( $type == 'sigs' )
			{
				$this->DB->delete( 'content_cache_sigs' );
			}
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------

			$dis  = $dis + $done;
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&type={$type}&pergo={$this->request['pergo']}&st={$last}&dis={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Resyncronize Topics
	 *
	 * @access	public
	 * @return	void
	 */
	public function resyncTopics()
	{
		require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
		$modfunc = new moderatorLibrary( $this->registry );

		$this->registry->class_localization->loadLanguageFile( array( 'public_global' ) );

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval( $this->request['st'] ) >= 0 ? intval( $this->request['st']    ) : 0;
		$end    = intval( $this->request['pergo'] )   ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'topics', 'limit' => array( $dis, 1 )  ) );
		$max = intval( $tmp['count'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'topics', 'order' => 'tid ASC', 'limit' => array( $start, $end ) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$modfunc->rebuildTopic($r['tid'], 0);

			if ( $this->request['pergo'] <= 200 )
			{
				$output[] = $this->lang->words['re_processed'].$r['title'];
			}

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Resyncronize forum data
	 *
	 * @access	public
	 * @return	void
	 */
	public function resyncForums()
	{
		require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
		$modfunc = new moderatorLibrary( $this->registry );

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$start  = intval( $this->request['st'] ) >= 0 ? intval( $this->request['st'] )    : 0;
		$end    = intval( $this->request['pergo'] )   ? intval( $this->request['pergo'] ) : 100;
		$dis    = $end + $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'forums', 'limit' => array( $dis, 1 )  ) );
		$max = intval( $tmp['count'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'forums', 'order' => 'id ASC', 'limit' => array( $start, $end ) ) );
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$modfunc->forumRecount( $r['id'] );
			$output[] = "Processed forum ".$r['name'];
			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
		 	//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = $this->lang->words['re_rebuildcomp'].implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$thisgoeshere = sprintf( $this->lang->words['re_thisgoeshere'], $dis );
			$text = $thisgoeshere .implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->form_code}&do={$this->request['do']}&pergo={$this->request['pergo']}&st={$dis}";
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/**
	 * Splash screen with rebuild options
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildSplashScreen()
	{
		/* Build Form Elements */
		$form = array();

		$form['posts']   = $this->registry->output->formDropdown( 'posts'  , array( 0 => array( 0, $this->lang->words['yesno_no'] ), 1 => array( 1, $this->lang->words['yesno_yes'] ) ) );
		$form['members'] = $this->registry->output->formDropdown( 'members', array( 0 => array( 0, $this->lang->words['yesno_no'] ), 1 => array( 1, $this->lang->words['yesno_yes'] ) ) );
		$form['lastreg'] = $this->registry->output->formDropdown( 'lastreg', array( 0 => array( 0, $this->lang->words['yesno_no'] ), 1 => array( 1, $this->lang->words['yesno_yes'] ) ) );
		$form['online']  = $this->registry->output->formDropdown( 'online' , array( 0 => array( 0, $this->lang->words['yesno_no'] ), 1 => array( 1, $this->lang->words['yesno_yes'] ) ) );

		//-----------------------------------------
		// Only need to rebuild once
		//-----------------------------------------

		$todo 		= array(
							array( 'posts'	 , $this->lang->words['remenu_posts'] ),
							array( 'pms'	 , $this->lang->words['remenu_pms'] ),
							array( 'cal'	 , $this->lang->words['remenu_events'] ),
							array( 'announce', $this->lang->words['remenu_announce'] ),
							array( 'sigs'	 , $this->lang->words['remenu_sigs'] ),
							array( 'aboutme' , $this->lang->words['remenu_aboutme'] ),
							array( 'rules'	 , $this->lang->words['remenu_rules'] ),
						);

		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			//-----------------------------------------
			// Retrieve the RSS links for the header
			//-----------------------------------------
		
			if( file_exists( IPSLib::getAppDir( $app_dir ) . '/extensions/upgradePostRebuild.php' ) )
			{
				require_once( IPSLib::getAppDir( $app_dir ) . '/extensions/upgradePostRebuild.php' );
				$className = "postRebuild_{$app_dir}";
				
				if( class_exists( $className ) )
				{
					$rebuild = new $className( $this->registry );
					
					if( method_exists( $rebuild, "getDropdown" ) )
					{
						$todo = array_merge( $todo, $rebuild->getDropdown() );
					}
				}
			}
		}

		$final		= array();
		$sections	= array();

		$alreadyRebuilt	= $this->DB->buildAndFetch( array( 'select' => 'cs_value', 'from' => 'cache_store', 'where' => "cs_key='isRebuilt'" ) );

		if( $alreadyRebuilt['cs_value'] )
		{
			$sections	= explode( ',', $alreadyRebuilt['cs_value'] );
		}

		$form['pergo']  = $this->registry->output->formSimpleInput( 'pergo', '50', 5 );

		/* Output */
		$this->registry->output->html           .= $this->html->rebuildSplashScreen( $form, $todo, $sections );
	}
}