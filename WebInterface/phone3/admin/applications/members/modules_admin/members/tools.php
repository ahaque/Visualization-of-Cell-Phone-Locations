<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member validation, locked and banned queues
 * Last Updated: $Date: 2009-09-01 05:04:14 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 5067 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_members_members_tools extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_tools');
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ) );
		
		$this->form_code	= $this->html->form_code	= '&amp;module=members&amp;section=tools';
		$this->form_code_js	= $this->html->form_code_js	= '&module=members&section=tools';
		
		switch( $this->request['do'] )
		{
			case 'show_all_ips':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_ip' );
				$this->_showIPs();
			break;
				
			case 'learn_ip':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_ip' );
				$this->_learnIP();
			break;

			case 'validating':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_validating' );
				$this->_viewQueue( 'validating' );
			break;
			case 'do_validating':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_validating' );
				$this->_manageValidating();
			break;
			case 'unappemail':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_validating' );
				$this->_emailUnapprove();
			break;
				
			case 'locked':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_locked' );
				$this->_viewQueue( 'locked' );
			break;
			case 'do_locked':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_locked' );
				$this->_unlock();
			break;
			
			case 'banned':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_banned' );
				$this->_viewQueue( 'banned' );
			break;
			
			case 'spam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_banned' );
				$this->_viewQueue( 'spam' );
			break;
			
			case 'do_spam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_banned' );
				$this->_unSpam();
			break;
			
			case 'do_banned':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_banned' );
				$this->_unban();
			break;
			
			case 'deleteMessages':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_delete_pms' );
				$this->_deleteMessages();
			break;
			
			case 'merge':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'members_merge' );
				$this->_mergeForm();
			break;
			
			case 'doMerge':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'members_merge' );
				$this->_completeMerge();
			break;

			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_ip' );
				$this->_toolsIndex();
			break;
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
		
	}
	
	/**
	 * Rebuild the stats
	 *
	 * @access	public
	 * @return	bool
	 * @author	Brandon Farber
	 */
	public function rebuildStats()
	{
		$stats	= $this->cache->getCache('stats');
		
		$topics = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as tcount',
															 	 'from'   => 'topics',
											 				 	 'where'  => 'approved=1' ) );

		$posts  = $this->DB->buildAndFetch( array( 'select' => 'SUM(posts) as replies',
																 'from'   => 'topics',
																 'where'  => 'approved=1' ) );

		$stats['total_topics']  = $topics['tcount'];
		$stats['total_replies'] = $posts['replies'];

		$r = $this->DB->buildAndFetch( array( 'select' => 'count(member_id) as members', 'from' => 'members', 'where' => "member_group_id <> '{$this->settings['auth_group']}'" ) );

		$stats['mem_count'] = intval($r['members']);

		$r = $this->DB->buildAndFetch( array( 'select' => 'member_id, name, members_display_name',
										  'from'   => 'members',
										  'where'  => "member_group_id <> '{$this->settings['auth_group']}' AND members_display_name != '' AND members_display_name " . $this->DB->buildIsNull( false ),
										  'order'  => "member_id DESC",
										  'limit'  => array(0,1) ) );

		$stats['last_mem_name'] = $r['members_display_name'] ? $r['members_display_name'] : $r['name'];
		$stats['last_mem_id']   = $r['member_id'];

		$this->cache->setCache( 'stats', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
	
		return true;
	}
	
	/**
	 * Show the form to confirm merging a member
	 *
	 * @access	privte
	 * @return	void	[Outputs to screen]
	 * @author	Brandon Farber
	 */
	private function _mergeForm()
	{
		$id	= intval($this->request['member_id']);
		
		if( !$id )
		{
			$this->_toolsIndex( $this->lang->words['no_merge_id'] );
			return false;
		}
		
		$member	= IPSMember::load( $id );
		
		$this->registry->output->html .= $this->html->mergeStart( $member );
	}
	
	/**
	 * Merge two members
	 *
	 * @access	privte
	 * @return	void	[Redirects to member account]
	 * @author	Brandon Farber
	 */
	private function _completeMerge()
	{
		if( !$this->request['confirm'] )
		{
			$member			= IPSMember::load( $this->request['member_id'] );
			$member2		= IPSMember::load( $this->request['members_display_name'], '', 'displayname' );
			
			if( !$member['member_id'] OR !$member2['member_id'] )
			{
				$this->_toolsIndex( $this->lang->words['no_merge_id'] );
				return false;
			}
					
			//-----------------------------------------
			// Output
			//-----------------------------------------
			
			$this->registry->output->html .= $this->html->mergeConfirm( $member, $member2 );
		}
		else
		{
			$member			= IPSMember::load( $this->request['member_id'] );
			$member2		= IPSMember::load( $this->request['member_id2'] );
			
			if( !$member['member_id'] OR !$member2['member_id'] )
			{
				$this->_toolsIndex( $this->lang->words['no_merge_id'] );
				return false;
			}

			//-----------------------------------------
			// Take care of forum stuff
			//-----------------------------------------

			$this->DB->update( 'posts'					, array( 'author_name'  => $member['members_display_name'] ), "author_id=" . $member2['member_id'] );
			$this->DB->update( 'posts'					, array( 'author_id'  => $member['member_id'] ), "author_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'starter_name' => $member['members_display_name'] ), "starter_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'seo_first_name' => $member['members_seo_name'] ), "starter_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'starter_id' => $member['member_id'] ), "starter_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'last_poster_name' => $member['members_display_name'] ), "last_poster_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'seo_last_name' => $member['members_seo_name'] ), "last_poster_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'last_poster_id' => $member['member_id'] ), "last_poster_id=" . $member2['member_id'] );
			$this->DB->update( 'announcements'			, array( 'announce_member_id' => $member['member_id'] ), "announce_member_id=" . $member2['member_id'] );
			$this->DB->update( 'attachments'			, array( 'attach_member_id' => $member['member_id'] ), "attach_member_id=" . $member2['member_id'] );
			$this->DB->update( 'polls'					, array( 'starter_id' => $member['member_id'] ), "starter_id=" . $member2['member_id'] );
			$this->DB->update( 'topic_ratings'			, array( 'rating_member_id' => $member['member_id'] ), "rating_member_id=" . $member2['member_id'] );
			$this->DB->update( 'moderators'				, array( 'member_id' => $member['member_id'] ) , "member_id=" . $member2['member_id'] );
			$this->DB->update( 'forums'					, array( 'last_poster_name' => $member['members_display_name'] ), "last_poster_id=" . $member2['member_id'] );
			$this->DB->update( 'forums'					, array( 'seo_last_name' => $member['members_seo_name'] ), "last_poster_id=" . $member2['member_id'] );
			$this->DB->update( 'forums'					, array( 'last_poster_id' => $member['member_id'] ), "last_poster_id=" . $member2['member_id'] );

			//-----------------------------------------
			// Clean up profile stuff
			//-----------------------------------------
	
			$this->DB->update( 'profile_comments'		, array( 'comment_by_member_id' => $member['member_id'] ), "comment_by_member_id=" . $member2['member_id'] );
			$this->DB->update( 'profile_comments'		, array( 'comment_for_member_id' => $member['member_id'] ), "comment_for_member_id=" . $member2['member_id'] );
			$this->DB->update( 'profile_portal_views'	, array( 'views_member_id' => $member['member_id'] ), "views_member_id=" . $member2['member_id'] );
			$this->DB->update( 'warn_logs'				, array( 'wlog_mid' => $member['member_id'] ), "wlog_mid=" . $member2['member_id'] );
			$this->DB->update( 'warn_logs'				, array( 'wlog_addedby' => $member['member_id'] ), "wlog_addedby=" . $member2['member_id'] );

			//-----------------------------------------
			// Update admin stuff
			//-----------------------------------------

			$this->DB->update( 'upgrade_history'		, array( 'upgrade_mid' => $member['member_id'] ), "upgrade_mid=" . $member2['member_id'] );
	
			//-----------------------------------------
			// Fix up member messages...
			//-----------------------------------------
			
			$this->DB->update( 'message_posts'			, array( 'msg_author_id' => $member['member_id'] ), 'msg_author_id=' . $member2['member_id'] );
			$this->DB->update( 'message_topics'			, array( 'mt_starter_id' => $member['member_id'] ), 'mt_starter_id=' . $member2['member_id'] );

			//-----------------------------------------
			// Stuff that can't have duplicates
			//-----------------------------------------

			//-----------------------------------------
			// Tracker
			//-----------------------------------------
			
			$tracker	= array();
			
			$this->DB->build( array( 'select' => 'topic_id', 'from' => 'tracker', 'where' => 'member_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$tracker[]	= $r['topic_id'];
			}

			if( count($tracker) )
			{
				$this->DB->update( 'tracker'				, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] . " AND topic_id NOT IN(" . implode( ',', $tracker ) . ")" );
			}
			else
			{
				$this->DB->update( 'tracker'				, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			}
			
			//-----------------------------------------
			// Forum tracker
			//-----------------------------------------
			
			$ftracker	= array();
			
			$this->DB->build( array( 'select' => 'forum_id', 'from' => 'forum_tracker', 'where' => 'member_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$ftracker[]	= $r['forum_id'];
			}

			if( count($ftracker) )
			{
				$this->DB->update( 'forum_tracker'			, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] . " AND forum_id NOT IN(" . implode( ',', $ftracker ) . ")" );
			}
			else
			{
				$this->DB->update( 'forum_tracker'			, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Poll votes
			//-----------------------------------------
			
			$voters		= array();
			
			$this->DB->build( array( 'select' => 'tid', 'from' => 'voters', 'where' => 'member_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$voters[]	= $r['tid'];
			}

			if( count($voters) )
			{
				$this->DB->update( 'voters'					, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] . " AND tid NOT IN(" . implode( ',', $voters ) . ")" );
			}
			else
			{
				$this->DB->update( 'voters'					, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Profile ratings
			//-----------------------------------------
			
			$ratingsFor		= array();
			$ratingsGot		= array();
			
			$this->DB->build( array( 'select' => 'rating_by_member_id,rating_for_member_id', 'from' => 'profile_ratings', 'where' => 'rating_by_member_id=' . $member['member_id'] . ' OR rating_for_member_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( $r['rating_by_member_id'] == $member['member_id'] )
				{
					$ratingsFor[]	= $r['rating_for_member_id'];
				}

				if( $r['rating_for_member_id'] == $member['member_id'] )
				{
					$ratingsGot[]	= $r['rating_by_member_id'];
				}
			}

			if( count($ratingsFor) )
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_by_member_id' => $member['member_id'] ), "rating_by_member_id=" . $member2['member_id'] . " AND rating_for_member_id NOT IN(" . implode( ',', $ratingsFor ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_by_member_id' => $member['member_id'] ), "rating_by_member_id=" . $member2['member_id'] );
			}
			
			if( count($ratingsGot) )
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_for_member_id' => $member['member_id'] ), "rating_for_member_id=" . $member2['member_id'] . " AND rating_by_member_id NOT IN(" . implode( ',', $ratingsGot ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_for_member_id' => $member['member_id'] ), "rating_for_member_id=" . $member2['member_id'] );
			}
			
			//-----------------------------------------
			// Profile friends
			//-----------------------------------------
			
			$myFriends		= array();
			$friendsMy		= array();
			
			$this->DB->build( array( 'select' => 'friends_member_id,friends_friend_id', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $member['member_id'] . ' OR friends_friend_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( $r['friends_member_id'] == $member['member_id'] )
				{
					$myFriends[]	= $r['friends_friend_id'];
				}

				if( $r['friends_friend_id'] == $member['member_id'] )
				{
					$friendsMy[]	= $r['friends_member_id'];
				}
			}

			if( count($myFriends) )
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_member_id' => $member['member_id'] ), "friends_member_id=" . $member2['member_id'] . " AND friends_friend_id NOT IN(" . implode( ',', $myFriends ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_member_id' => $member['member_id'] ), "friends_member_id=" . $member2['member_id'] );
			}
			
			if( count($friendsMy) )
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_friend_id' => $member['member_id'] ), "friends_friend_id=" . $member2['member_id'] . " AND friends_member_id NOT IN(" . implode( ',', $friendsMy ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_friend_id' => $member['member_id'] ), "friends_friend_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Ignored users
			//-----------------------------------------
			
			$myIgnored		= array();
			$ignoredMe		= array();
			
			$this->DB->build( array( 'select' => 'ignore_owner_id,ignore_ignore_id', 'from' => 'ignored_users', 'where' => 'ignore_owner_id=' . $member['member_id'] . ' OR ignore_ignore_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( $r['ignore_owner_id'] == $member['member_id'] )
				{
					$myIgnored[]	= $r['ignore_ignore_id'];
				}

				if( $r['ignore_ignore_id'] == $member['member_id'] )
				{
					$ignoredMe[]	= $r['ignore_owner_id'];
				}
			}

			if( count($myIgnored) )
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_owner_id' => $member['member_id'] ), "ignore_owner_id=" . $member2['member_id'] . " AND ignore_ignore_id NOT IN(" . implode( ',', $myIgnored ) . ")" );
			}
			else
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_owner_id' => $member['member_id'] ), "ignore_owner_id=" . $member2['member_id'] );
			}
			
			if( count($ignoredMe) )
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_ignore_id' => $member['member_id'] ), "ignore_ignore_id=" . $member2['member_id'] . " AND ignore_owner_id NOT IN(" . implode( ',', $ignoredMe ) . ")" );
			}
			else
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_ignore_id' => $member['member_id'] ), "ignore_ignore_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Message topic mapping
			//-----------------------------------------
			
			$pms		= array();
			
			$this->DB->build( array( 'select' => 'map_topic_id', 'from' => 'message_topic_user_map', 'where' => 'map_user_id=' . $member['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$pms[]		= $r['map_topic_id'];
			}

			if( count($pms) )
			{
				$this->DB->update( 'message_topic_user_map'	, array( 'map_user_id' => $member['member_id'] ), "map_user_id=" . $member2['member_id'] . " AND map_topic_id NOT IN(" . implode( ',', $pms ) . ")" );
			}
			else
			{
				$this->DB->update( 'message_topic_user_map'	, array( 'map_user_id' => $member['member_id'] ), 'map_user_id=' . $member2['member_id'] );
			}

			//-----------------------------------------
			// Admin permissions
			//-----------------------------------------
			
			$count	= $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $member['member_id'] ) );
			
			if( !$count['row_id'] )
			{
				$this->DB->update( 'admin_permission_rows'	, array( 'row_id' => $member['member_id'] ), "row_id_type='member' AND row_id=" . $member2['member_id'] );
			}
			
			//-----------------------------------------
			// Member Sync
			//-----------------------------------------
			
			try
			{
				IPSMember::save( $member['member_id'], array( 'core' => array( 
																			'posts'			=> ($member['posts'] + $member2['posts']), 
																			'warn_level'	=> ($member['warn_level'] + $member2['warn_level']),
																			'warn_lastwarn'	=> ($member2['warn_lastwarn'] > $member['warn_lastwarn']) ? $member2['warn_lastwarn'] : $member['warn_lastwarn'] ,
																			'last_post'		=> ($member2['last_post'] > $member['last_post']) ? $member2['last_post'] : $member['last_post'],
																			'last_visit'	=> ($member2['last_visit'] > $member['last_visit']) ? $member2['last_visit'] : $member['last_visit'],
																			) 
								)							);
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			IPSLib::runMemberSync( 'onMerge', $member, $member2 );
			
			//-----------------------------------------
			// Delete member 2
			//-----------------------------------------
			
			IPSMember::remove( $member2['member_id'], false );
	
			//-----------------------------------------
			// Get current stats...
			//-----------------------------------------
	
			$this->cache->rebuildCache( 'stats', 'global' );
			$this->cache->rebuildCache( 'moderators', 'forums' );
			$this->cache->rebuildCache( 'announcements', 'forums' );
			
			//-----------------------------------------
			// Admin logs
			//-----------------------------------------
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['merged_accounts_log'], $member2['members_display_name'], $member['members_display_name'] ) );
			
			//-----------------------------------------
			// Redirect
			//-----------------------------------------
			
			$this->registry->output->redirect( $this->settings['base_url'] . "module=members&amp;section=members&amp;do=viewmember&amp;member_id=" . $member['member_id'], $this->lang->words['merged_members'] );
		}
	}

	/**
	 * Delete all private messages from a member
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _deleteMessages()
	{
		if( !$this->request['confirm'] )
		{
			$countTopics	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'message_topics', 'where' => 'mt_is_deleted=0 AND mt_starter_id=' . intval($this->request['member_id']) ) );
			$countReplies	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'message_posts', 'where' => 'msg_is_first_post=0 AND msg_author_id=' . intval($this->request['member_id']) ) );

			$member			= IPSMember::load( $this->request['member_id'] );
			
			//-----------------------------------------
			// Output
			//-----------------------------------------
			
			$this->registry->output->html .= $this->html->deleteMessagesWrapper( $member, $countTopics, $countReplies );
		}
		else
		{
			//-----------------------------------------
			// Get messenger lib
			//-----------------------------------------
			
			require_once( IPSLib::getAppDir('members') . '/sources/classes/messaging/messengerFunctions.php' );
			$messengerLibrary	= new messengerFunctions( $this->registry );
			
			if( $this->request['topics'] )
			{
				//-----------------------------------------
				// Get topic ids
				//-----------------------------------------
				
				$messages	= array();
				
				$this->DB->build( array( 
										'select'	=> 'mt_id',
										'from'		=> 'message_topics',
										'where'		=> 'mt_is_deleted=0 AND mt_starter_id=' . intval($this->request['member_id']),
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$messages[] = $r['mt_id'];
				}

				//-----------------------------------------
				// Delete topics
				//-----------------------------------------
				
				if( count($messages) )
				{
					$messengerLibrary->deleteTopics( $this->request['member_id'], $messages, null, true );
				}
			}
			
			if( $this->request['replies'] )
			{
				//-----------------------------------------
				// Get reply ids
				//-----------------------------------------
				
				$messages	= array();
				
				$this->DB->build( array( 
										'select'	=> 'msg_id',
										'from'		=> 'message_posts',
										'where'		=> 'msg_is_first_post=0 AND msg_author_id=' . intval($this->request['member_id']),
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$messages[] = $r['msg_id'];
				}
				
				//-----------------------------------------
				// Delete topics
				//-----------------------------------------
				
				if( count($messages) )
				{
					$messengerLibrary->deleteMessages( $messages, $this->request['member_id'] );
				}
			}
			
			$this->registry->output->redirect( $this->settings['base_url'] . "module=members&amp;section=members&amp;do=viewmember&amp;member_id=" . $this->request['member_id'], $this->lang->words['deleted_pms'] );
		}
	}
	
	/**
	 * View queues (validating, locked, banned)
	 *
	 * @access	private
	 * @param 	string		Queue to view [validating, locked, banned]
	 * @return	void		[Outputs to screen]
	 */
	private function _viewQueue( $type='validating' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request[ 'ord'] =  $this->request['ord'] ? $this->request['ord'] : '' ;
		$st			= intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;
		$ord		= $this->request['ord'] == 'asc' ? 'asc' : 'desc';
		$new_ord	= $ord  == 'asc' ? 'desc' : 'asc';
		$filter		= $this->request['filter'] ? $this->request['filter'] : '';
		$q_extra	= "";
		$content	= "";

		//-----------------------------------------
		// Run teh query
		//-----------------------------------------
		
		switch( $type )
		{
			case 'validating':
				switch( $filter )
				{
					case 'reg_user_validate':
						if( $this->settings['reg_auth_type'] != 'admin' )
						{
							$q_extra = " AND v.new_reg=1 AND v.user_verified=0";
						}
					break;

					case 'reg_admin_validate':
						if( $this->settings['reg_auth_type'] == 'admin' )
						{
							$q_extra = " AND v.new_reg=1";
						}
						else
						{
							$q_extra = " AND v.new_reg=1 AND v.user_verified=1";
						}
					break;

					case 'email_chg':
						$q_extra = " AND v.email_chg=1";
					break;
						
					case 'coppa':
						$q_extra = " AND v.coppa_user=1";
					break;
				}
				
				$row = $this->DB->buildAndFetch( array( 'select'		=> 'COUNT(*) as queue', 
																'from'		=> array( 'validating' => 'v' ), 
																'where'		=> "v.lost_pass=0 AND m.member_group_id=" . $this->settings['auth_group'] . $q_extra,
																'add_join'	=> array(
																					array(
																							'from'		=> array( 'members' => 'm' ),
																							'where'		=> 'm.member_id=v.member_id',
																							'type'		=> 'left',
																						),
																					),
														)		);
			break;
			
			case 'locked':
				if( $this->settings['ipb_bruteforce_attempts'] )
				{
					$row = $this->DB->buildAndFetch( array( 'select'		=> 'COUNT(*) as queue', 
																	'from'		=> 'members', 
																	'where'		=> "failed_login_count >= " . intval($this->settings['ipb_bruteforce_attempts']),
															)		);
				}
				else
				{
					$row['queue']	= 0;
				}
			break;
			
			case 'banned':
				$row = $this->DB->buildAndFetch( array( 'select'		=> 'COUNT(*) as queue', 
																'from'		=> 'members', 
																'where'		=> "member_banned=1",
														)		);
			break;
			case 'spam':
				$row = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as queue', 
														'from'		=> 'members', 
														'where'		=> IPSBWOptions::sql( 'bw_is_spammer', 'members_bitoptions', 'members', 'global', 'has' ) ) );
			break;
		}

		$cnt = intval($row['queue']);
		
		//-----------------------------------------
		// Grab default sorting
		//-----------------------------------------
		
		switch( $type )
		{
			case 'validating':
				$col	= 'v.entry_date';
			break;
			
			case 'locked':
				$col	= 'm.members_display_name';
			break;
			
			case 'banned':
				$col	= 'm.members_display_name';
			break;
			case 'spam':
				$col	= 'm.joined';
			break;
		}

		//-----------------------------------------
		// And actual sorting..
		//-----------------------------------------
		
		switch ($this->request['sort'])
		{
			case 'mem':
				$col = 'm.members_display_name';
			break;
			
			case 'email':
				$col = 'm.email';
			break;
			
			case 'sent':
				if( $type == 'validating' )
				{
					$col = 'v.entry_date';
				}
			break;
			
			case 'failed':
				if( $type == 'locked' )
				{
					$col = 'm.failed_login_count';
				}
			break;
			
			case 'group':
				if( $type == 'banned' )
				{
					$col = 'g.g_title';
				}
			break;
			
			case 'posts':
				$col = 'm.posts';
			break;
			
			case 'joined':
				$col = 'm.joined';
			break;
		}					     
		
		//-----------------------------------------
		// Pages...
		//-----------------------------------------
		
		$links = $this->registry->output->generatePagination( array(	'totalItems'		=> $cnt,
																		'itemsPerPage'		=> 75,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> $this->settings['base_url'] . "&amp;{$this->form_code}&amp;do={$type}&amp;ord={$ord}&amp;filter={$filter}",
															)		);
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		if ( $cnt > 0 )
		{
			switch( $type )
			{
				case 'validating':
					$this->DB->build( array( 'select' 	=> 'v.*', 
													 'from' 	=> array( 'validating' => 'v' ), 
													 'where' 	=> "v.lost_pass=0 AND m.member_group_id=" . $this->settings['auth_group'].$q_extra,
													 'order'	=> $col . ' ' . $ord,
													 'limit'	=> array( $st, 75 ),
													 'add_join'	=> array(
													 					array(
													 							'select' 	=> 'm.name, m.member_group_id, m.members_display_name, m.ip_address, m.member_id, m.email, m.posts, m.joined',
													 							'from'		=> array( 'members' => 'm' ),
													 							'where'		=> 'm.member_id=v.member_id',
													 							'type'		=> 'left',
													 						),
													 					),
											) 		);
				break;
				
				case 'locked':
					$this->DB->build( array( 'select' 	=> 'm.member_group_id, m.members_display_name, m.ip_address, m.member_id, m.email, m.posts, m.joined, m.failed_logins, m.failed_login_count', 
													 'from' 	=> 'members m', 
													 'where' 	=> "m.failed_login_count >= " . intval($this->settings['ipb_bruteforce_attempts']),
													 'order'	=> $col . ' ' . $ord,
													 'limit'	=> array( $st, 75 ),
											) 		);
				break;
				
				case 'banned':
					$this->DB->build( array( 'select' 	=> 'm.member_group_id, m.members_display_name, m.ip_address, m.member_id, m.email, m.posts, m.joined, m.failed_logins, m.failed_login_count', 
													 'from' 	=> array( 'members' => 'm' ), 
													 'where' 	=> "m.member_banned=1",
													 'order'	=> $col . ' ' . $ord,
													 'limit'	=> array( $st, 75 ),
													 'add_join'	=> array(
													 					array(
													 							'select' 	=> 'g.g_title',
													 							'from'		=> array( 'groups' => 'g' ),
													 							'where'		=> 'g.g_id=m.member_group_id',
													 							'type'		=> 'left',
													 						),
													 					),
											) 		);
				break;
				case 'spam':
					$this->DB->build( array( 'select' 	=> 'm.*', 
											 'from' 	=> array( 'members' => 'm' ), 
											 'where' 	=> IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' ),
											 'order'	=> $col . ' ' . $ord,
											 'limit'	=> array( $st, 75 ),
											 'add_join'	=> array(
											 					array(  'select' 	=> 'g.g_title',
											 							'from'		=> array( 'groups' => 'g' ),
											 							'where'		=> 'g.g_id=m.member_group_id',
											 							'type'		=> 'left' ) ) )  );
				break;
			}
			
			
			$this->DB->execute();

			while ( $r = $this->DB->fetch() )
			{
				$r['_joined'] 				= ipsRegistry::getClass( 'class_localization')->getDate( $r['joined']	, 'TINY' );
				$r['members_display_name']	= $r['members_display_name'] ? $r['members_display_name'] : $this->lang->words['t_deletedmem'];
				$r['group_title'] 			= IPSLib::makeNameFormatted( $this->caches['group_cache'][ $r['member_group_id'] ]['g_title'], $r['member_group_id'] );

				switch( $type )
				{
					case 'validating':
						$r['_coppa'] = $r['coppa_user'] ? $this->lang->words['t_coppa'] : '';
						
						//-----------------------------------------
						// Sort out 'where'
						//-----------------------------------------

						$r['_where'] = ( $r['lost_pass'] ? $this->lang->words['t_lostpass'] : ( $r['new_reg'] ? $this->lang->words['t_userval'] : ( $r['email_chg'] ? $this->lang->words['t_emailchange'] : $this->lang->words['t_na'] ) ) );
						
						if( isset($r['email_chg']) AND $r['email_chg'] )
						{
							$r['_where'] .= " (<a href='" . $this->settings['base_url'] . "{$this->form_code}&amp;do=unappemail&amp;mid={$r['member_id']}'>{$this->lang->words['t_unapprove']}</a>)";
						}

						if ( $r['new_reg'] AND ( $r['user_verified'] == 1 OR $this->settings['reg_auth_type'] == 'admin' ) )
						{
							$r['_where'] = $this->lang->words['t_adminval'];
						}
						
						//-----------------------------------------
						// How long ago did this start?
						//-----------------------------------------
						
						$r['_hours']  = floor( ( time() - $r['entry_date'] ) / 3600 );
						$r['_days']   = intval( $r['_hours'] / 24 );
						$r['_rhours'] = intval( $r['_hours'] - ($r['_days'] * 24) );
						
						//-----------------------------------------
						// Format time
						//-----------------------------------------
						
						$r['_entry']  = ipsRegistry::getClass( 'class_localization')->getDate( $r['entry_date'], 'TINY' );
					break;
					
					case 'locked':
						//-----------------------------------------
						// Sort out lovely locked info
						//-----------------------------------------
						
						$used_ips 		= array();
						$this_attempt 	= array();
						$oldest			= 0;
						$newest			= 0;
						
						if( $r['failed_logins'] )
						{
							$failed_logins = explode( ",", IPSText::cleanPermString( $r['failed_logins'] ) );
							
							if( is_array($failed_logins) AND count($failed_logins) )
							{
								sort($failed_logins);
								
								foreach( $failed_logins as $attempt )
								{
									$this_attempt = explode( "-", $attempt );
									$used_ips[] = $this_attempt[1];
								}
								
								$oldest = array_shift($failed_logins);
								$newest = array_pop($failed_logins);
							}
						}
						
						$newest = explode( "-", $newest );
						$oldest = explode( "-", $oldest );

						$r['oldest_fail'] = ipsRegistry::getClass( 'class_localization')->getDate( $oldest[0], 'SHORT' );
						$r['newest_fail'] = ipsRegistry::getClass( 'class_localization')->getDate( $newest[0], 'SHORT' );
						
						//-----------------------------------------
						// Some nice IP address info
						//-----------------------------------------
						
						$r['ip_addresses'] = "";
						
						$used_ips = array_unique($used_ips);
						
						foreach( $used_ips as $ip_address )
						{
							$r['ip_addresses'] .= "{$this->lang->words['t_ipcolon']} <a href='" . $this->settings['base_url'] . "&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$ip_address}'>{$ip_address}</a><br />";
						}
					break;
				}
				
				//-----------------------------------------
				// Print row
				//-----------------------------------------
				
				$function	= $type . 'Row';
				$content	.= $this->html->$function( $r );
			}
		}
		else
		{
			$content = $this->html->queueNoRows( sprintf( $this->lang->words['t_notype'], $this->lang->words['t_rowtype_' . $type ] ) );
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->queueWrapper( $type, $content, $st, $new_ord, $links );
		
		//-----------------------------------------
		// Extra navigation
		//-----------------------------------------
		
		switch( $type )
		{
			case 'validating':
				$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['t_validating'] );
			break;
			
			case 'locked':
				$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['t_locked'] );
			break;
			
			case 'banned':
				$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['t_banned'] );
			break;
		}
	}
	
	/**
	 * Learn about an IP address
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _learnIP()
	{
		if ( $this->request['ip'] == "" )
		{
			$this->_toolsIndex( $this->lang->words['t_noip'] );
		}
		
		$ip				= trim($this->request['ip']);
		
		$resolved		= $this->lang->words['t_partip'];
		$exact			= 0;
		
		if ( substr_count( $ip, '.' ) == 3 )
		{
			$exact		= 1;
		}
		
		if ( strstr( $ip, '*' ) )
		{
			$exact		= 0;
			$ip			= str_replace( "*", "", $ip );
		}
			
		if ( $exact == 1 )
		{
			$resolved	= @gethostbyaddr($ip);
			$query		= "='" . $ip . "'";
		}
		else
		{
			$query		= " LIKE '" . $ip . "%'";
		}
		
		$results	= IPSLib::findIPAddresses( $query );

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$registered		= array();
		$posted			= array();
		$voted			= array();
		$emailed		= array();
		$validating		= array();
		
		//-----------------------------------------
		// Find registered members
		//-----------------------------------------

		if( count($results['members']) )
		{
			foreach( $results['members'] as $m )
			{
				$m['_joined']	= ipsRegistry::getClass( 'class_localization')->getDate( $m['joined'], 'SHORT' );
				
				$registered[]	= $m;
			}
			
			unset($results['members']);
		}
		
		//$this->DB->build( array( 'select'	=> 'member_id, members_display_name, email, posts, ip_address, joined',
		//								'from'	=> 'members',
		//								'where'	=> 'ip_address' . $query,
		//								'order'	=> 'joined DESC',
		//								'limit'	=> array( 250 ) 
		//					) 		);
		//$this->DB->execute();
		
		//while ( $m = $this->DB->fetch() )
		//{
		//	$m['_joined']	= ipsRegistry::getClass( 'class_localization')->getDate( $m['joined'], 'SHORT' );
		//	
		//	$registered[]	= $m;
		//}

		//-----------------------------------------
		// Find Names POSTED under
		//-----------------------------------------
		
		if( count($results['posts']) )
		{
			foreach( $results['posts'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_post_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['post_date'], 'SHORT' );
				
				$posted[]	= $m;
			}
			
			unset($results['posts']);
		}
		
		//$this->DB->build( array( 'select'	=> 'p.pid, p.author_id, p.post_date, p.ip_address, p.topic_id',
		//								'from'	=> array( 'posts' => 'p' ),
		//								'where'	=> 'p.ip_address' . $query,
		//								'group'	=> 'p.author_id',
		//								'order'	=> 'p.post_date DESC',
		//								'limit'	=> array( 250 ),
		//								'add_join'	=> array(
		//													array( 'select'	=> 'm.member_id, m.members_display_name, m.email, m.posts, m.joined',
		//															'from'	=> array( 'members' => 'm' ),
		//															'where'	=> 'm.member_id=p.author_id',
		//															'type'	=> 'left'
		//														)
		//													)
		//					)		);
		//$this->DB->execute();
		
		//while ( $m = $this->DB->fetch() )
		//{
		//	$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
		//	$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
		//	$m['_post_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['post_date'], 'SHORT' );
		//	
		//	$posted[]	= $m;
		//}

		//-----------------------------------------
		// Find Names VOTED under
		//-----------------------------------------

		if( count($results['voters']) )
		{
			foreach( $results['voters'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_vote_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['vote_date'], 'SHORT' );
				
				$voted[]	= $m;
			}
			
			unset($results['voters']);
		}
		
		//$this->DB->build( array( 'select'	=> 'p.vote_date, p.ip_address, p.tid',
		//								'from'	=> array( 'voters' => 'p' ),
		//								'where'	=> 'p.ip_address' . $query,
		//								'group'	=> 'p.member_id',
		//								'order'	=> 'p.vote_date DESC',
		//								'limit'	=> array( 250 ),
		//								'add_join'	=> array(
		//													array( 'select'	=> 'm.member_id, m.members_display_name, m.email, m.posts, m.joined',
		//															'from'	=> array( 'members' => 'm' ),
		//															'where'	=> 'm.member_id=p.member_id',
		//															'type'	=> 'left'
		//														)
		//													)
		//					)		);
		//$this->DB->execute();
		
		//while ( $m = $this->DB->fetch() )
		//{
		//	$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
		//	$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
		//	$m['_vote_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['vote_date'], 'SHORT' );
		//	
		//	$voted[]	= $m;
		//}

		//-----------------------------------------
		// Find Names EMAILING under
		//-----------------------------------------

		if( count($results['email_logs']) )
		{
			foreach( $results['email_logs'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_email_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['email_date'], 'SHORT' );
				
				$emailed[]	= $m;
			}
			
			unset($results['email_logs']);
		}
		
		//$this->DB->build( array( 'select'	=> 'p.email_date, p.from_ip_address',
		//								'from'	=> array( 'email_logs' => 'p' ),
		//								'where'	=> 'p.from_ip_address' . $query,
		//								'group'	=> 'p.from_member_id',
		//								'order'	=> 'p.email_date DESC',
		//								'limit'	=> array( 250 ),
		//								'add_join'	=> array(
		//													array( 'select'	=> 'm.member_id, m.members_display_name, m.email, m.posts, m.joined',
		//															'from'	=> array( 'members' => 'm' ),
		//															'where'	=> 'm.member_id=p.from_member_id',
		//															'type'	=> 'left'
		//														)
		//													)
		//					)		);
		//$this->DB->execute();
		
		//while ( $m = $this->DB->fetch() )
		//{
		//	$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
		//	$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
		//	$m['_email_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['email_date'], 'SHORT' );
		//	
		//	$emailed[]	= $m;
		//}

		//-----------------------------------------
		// Find Names VALIDATING under
		//-----------------------------------------

		if( count($results['validating']) )
		{
			foreach( $results['validating'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_entry_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['entry_date'], 'SHORT' );
				
				$validating[]	= $m;
			}
			
			unset($results['validating']);
		}
		
		//$this->DB->build( array( 'select'	=> 'p.entry_date, p.ip_address',
		//								'from'	=> array( 'validating' => 'p' ),
		//								'where'	=> 'p.ip_address' . $query,
		//								'group'	=> 'p.member_id',
		//								'order'	=> 'p.entry_date DESC',
		//								'limit'	=> array( 250 ),
		//								'add_join'	=> array(
		//													array( 'select'	=> 'm.member_id, m.members_display_name, m.email, m.posts, m.joined',
		//															'from'	=> array( 'members' => 'm' ),
		//															'where'	=> 'm.member_id=p.member_id',
		//															'type'	=> 'left'
		//														)
		//													)
		//					)		);
		//$this->DB->execute();
		
		//while ( $m = $this->DB->fetch() )
		//{
		//	$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
		//	$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
		//	$m['_entry_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['entry_date'], 'SHORT' );
		//	
		//	$emailed[]	= $m;
		//}

		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->learnIPResults( $resolved, $registered, $posted, $voted, $emailed, $validating, $results );
	}
	
	/**
	 * Show all IP addresses a user has used
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _showIPs()
	{
		if ( $this->request['name'] == "" and $this->request['member_id'] == "" )
		{
			$this->_toolsIndex( $this->lang->words['t_noname'] );
			return false;
		}
		
		if ( $this->request['member_id'] )
		{
			$id = intval($this->request['member_id']);
			
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name, email, ip_address', 'from' => 'members', 'where' => "member_id={$id}" ) );

			if ( ! $member['member_id'] )
			{
				$this->_toolsIndex( sprintf( $this->lang->words['t_nonameloc'], $id ) );
				return;
			}
		}
		else
		{
			$name = strtolower($this->request['name']);
			
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name, email, ip_address', 'from' => 'members', 'where' => "members_l_username='" . $this->DB->addSlashes( $name ) . "' OR members_l_display_name='" . $this->DB->addSlashes( $name ) . "'" ) );
			
			if ( ! $member['member_id'] )
			{
				$this->_toolsIndex( $this->lang->words['t_noexact'], $name );
				return;
			}
		}

		$master	= array();
		$ips	= array();
		$reg	= array();
		$allips	= IPSMember::findIPAddresses( $member['member_id'] );
		
		$st		= intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;
		$end	= 50;
		
		$links = $this->registry->output->generatePagination( array( 'totalItems'		=> count($allips),
														  			'itemsPerPage'		=> $end,
														  			'currentStartValue'	=> $st,
														  			'baseUrl'			=> $this->settings['base_url'] . "&amp;" . $this->form_code . "&amp;do=show_all_ips&amp;member_id={$member['member_id']}",
												 			)      );
		
		if ( count($allips) > 0 )
		{
			foreach( $allips as $ip_address => $count )
			{
				$ips[]	= "'" . $ip_address . "'";
			}

			$this->DB->build( array( 'select' => 'ip_address', 'from' => 'members', 'where' => "ip_address IN (" . implode( ",", $ips ) . ") AND member_id != {$member['member_id']}" ) );
			$this->DB->execute();
		
			while ( $i = $this->DB->fetch() )
			{
				$reg[ $i['ip_address'] ][] = 1;
			}
		}
					     
		$this->registry->output->html .= $this->html->showAllIPs( $member, $allips, $links, $reg );
	}
	
	/**
	 * IP Address Tools index page
	 *
	 * @access	private
	 * @param 	string		Message to display
	 * @param	string		Membername to default in the dropdown
	 * @return	void		[Outputs to screen]
	 */
	private function _toolsIndex( $msg="", $membername="" )
	{
		if ( !$membername )
		{
			$form = array(
							'text'		=> $this->lang->words['t_entername'],
							'form'		=> $this->registry->output->formInput( "name", isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' )
							);
		}
		else
		{
			$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "members_l_username LIKE '{$membername}%' OR members_l_display_name LIKE '{$membername}%'" ) );
			$this->DB->execute();
		
			if ( ! $this->DB->getTotalRows() )
			{
				$msg	= sprintf( $this->lang->words['t_nomemberloc'], $membername );
				
				$form = array(
								'text'		=> $this->lang->words['t_entername'],
								'form'		=> $this->registry->output->formSimpleInput( "name", isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' )
								);
			}
			else
			{
				$mem_array = array();
				
				while ( $m = $this->DB->fetch() )
				{
					$mem_array[] = array( $m['member_id'], $m['members_display_name'] );
				}
				
				$form = array(
								'text'		=> $this->lang->words['t_choosemem'],
								'form'		=> $this->registry->output->formDropdown( "member_id", $mem_array )
								);
			}
		}

		$this->registry->output->html .= $this->html->toolsIndex( $msg, $form );
	}
	
	/**
	 * Manage validating members
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _manageValidating()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids = array();
		
		//-----------------------------------------
		// GET checkboxes
		//-----------------------------------------
		
		foreach ( $this->request as $k => $v )
		{
			if ( preg_match( "/^mid_(\d+)$/", $k, $match ) )
			{
				if ( $v )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = IPSLib::cleanIntArray( $ids );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( count($ids) < 1 )
		{	
			$this->registry->output->showError( $this->lang->words['t_nomemsel'], 11247 );
		}

		//-----------------------------------------
		// APPROVE
		//-----------------------------------------
		
		if ( $this->request['type'] == 'approve' )
		{
			IPSText::getTextClass('email')->getTemplate( "complete_reg" );
			
			$approved = array();
			
			//-----------------------------------------
			// Get members
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> 'v.*',
											'from'	=> array( 'validating' => 'v' ),
											'where'	=> "m.member_id IN(" . implode( ",", $ids ) . ")",
											'add_join'	=> array(
																array( 'select'	=> 'm.member_id, m.members_display_name, m.name, m.email, m.member_group_id',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=v.member_id',
																		'type'	=> 'left'
																	)
																)
								)		);
			$main = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $main ) )
			{
				$approved[] = $row['name'];

				//-----------------------------------------
				// Only approve if the user is validating
				//-----------------------------------------
				
				if ( $row['member_group_id'] != $this->settings['auth_group'] )
				{
					continue;
				}
				
				//-----------------------------------------
				// Don't approve if no real_group set
				//-----------------------------------------
				
				if ( !$row['real_group'] )
				{
					//$row['real_group'] = $this->settings['member_group'];
					continue;
				}
				
				//-----------------------------------------
				// We don't approve lost pass requests
				//-----------------------------------------
				
				if( $row['lost_pass'] == 1 )
				{
					continue;
				}
				
				try
				{
					IPSMember::save( $row['member_id'], array( 'core' => array( 'member_group_id' => $row['real_group'] ) ) );
				}
				catch( Exception $error )
				{
					$this->registry->output->showError( $error->getMessage(), 11247 );
				}
				
				IPSText::getTextClass('email')->buildMessage( array() );
				
				//-----------------------------------------
				// Using 'name' on purpose
				// @see http://forums./index.php?autocom=tracker&showissue=11564&view=findpost&p=45269
				//-----------------------------------------
				
				IPSText::getTextClass('email')->subject	= sprintf( $this->lang->words['subject__complete_reg'], $row['name'], $this->settings['board_name'] );
				IPSText::getTextClass('email')->to		= $row['email'];
				IPSText::getTextClass('email')->sendMail();
				
				IPSLib::runMemberSync( 'onGroupChange', $row['member_id'], $row['real_group'] );
			}
			
			$this->DB->delete( 'validating', "member_id IN(" . implode( ",", $ids ) . ")" );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memregapp2'] . implode( ", ", $approved ) );
			
			//-----------------------------------------
			// Stats to Update?
			//-----------------------------------------
			
			$this->cache->rebuildCache( 'stats', 'global' );
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memregapp'];
			
			if( $this->request['_return'] )
			{
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members&module=members&section=members&do=viewmember&member_id=' . $this->request['_return'] );
			}

			$this->_viewQueue( 'validating' );
			return;
		}
		
		//-----------------------------------------
		// Resend validation email
		//-----------------------------------------
		
		else if ( $this->request['type'] == 'resend' )
		{
			$reset		= array();
			$cant		= array();
			$main_msgs	= array();
			
			//-----------------------------------------
			// Get members
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> 'v.*',
											'from'	=> array( 'validating' => 'v' ),
											'where'	=> "m.member_id IN(" . implode( ",", $ids ) . ")",
											'add_join'	=> array(
																array( 'select'	=> 'm.member_id, m.members_display_name, m.email, m.member_group_id',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=v.member_id',
																		'type'	=> 'left'
																	)
																)
								)		);
			$main = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $main ) )
			{
				if ( $row['member_group_id'] != $this->settings['auth_group'] )
				{
					continue;
				}
				
				if ( $row['lost_pass'] )
				{
					IPSText::getTextClass('email')->getTemplate("lost_pass");
						
					IPSText::getTextClass('email')->buildMessage( array(
														'NAME'         => $row['members_display_name'],
														'THE_LINK'     => $this->settings['board_url']."/index.php?app=core&module=global&section=lostpass&do=sendform&uid=".$row['member_id']."&aid=".$val['vid'],
														'MAN_LINK'     => $this->settings['board_url']."/index.php?app=core&module=global&section=lostpass",
														'EMAIL'        => $row['email'],
														'ID'           => $row['member_id'],
														'CODE'         => $row['vid'],
														'IP_ADDRESS'   => $row['ip_address'],
													  )
												);
												
					IPSText::getTextClass('email')->subject	= $this->lang->words['t_passwordrec'] . $this->settings['board_name'];
					IPSText::getTextClass('email')->to		= $row['email'];
					IPSText::getTextClass('email')->sendMail();
				}
				else if ( $row['new_reg'] )
				{
					if( $row['user_verified'] )
					{
						$cant[] = $row['members_display_name'];
						continue;
					}
					
					IPSText::getTextClass('email')->getTemplate( "reg_validate" );
							
					IPSText::getTextClass('email')->buildMessage( array(
														'THE_LINK'     => $this->settings['board_url']."/index.php?app=core&module=global&section=register&do=auto_validate&uid=".$row['member_id']."&aid=".$row['vid'],
														'NAME'         => $row['members_display_name'],
														'MAN_LINK'     => $this->settings['board_url']."/index.php?app=core&module=global&section=register&do=05",
														'EMAIL'        => $row['email'],
														'ID'           => $row['member_id'],
														'CODE'         => $row['vid'],
													  )
												);
												
					IPSText::getTextClass('email')->subject	= $this->lang->words['t_regat'] . $this->settings['board_name'];
					IPSText::getTextClass('email')->to		= $row['email'];
					IPSText::getTextClass('email')->sendMail();
				}
				else if ( $row['email_chg'] )
				{
					IPSText::getTextClass('email')->getTemplate("newemail");
						
					IPSText::getTextClass('email')->buildMessage( array(
														'NAME'         => $row['members_display_name'],
														'THE_LINK'     => $this->settings['board_url']."/index.php?app=core&module=global&section=register&do=auto_validate&type=newemail&uid=".$row['member_id']."&aid=".$row['vid'],
														'ID'           => $row['member_id'],
														'MAN_LINK'     => $this->settings['board_url']."/index.php?app=core&module=global&section=register&do=user_validate",
														'CODE'         => $row['vid'],
													  )
												);
												
					IPSText::getTextClass('email')->subject	= $this->lang->words['t_emailchange'] . $this->settings['board_name'];
					IPSText::getTextClass('email')->to		= $row['email'];
					IPSText::getTextClass('email')->sendMail();
				}
				
				$resent[] = $row['members_display_name'];
			}
			
			if( count($resent) )
			{
				ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($resent) . $this->lang->words['tools_val_resent_log'] . implode( ", ", $resent ) );
				$main_msgs[] = count($resent) . $this->lang->words['t_vallog'] . implode( ", ", $resent );
			}
			
			if( count($cant) )
			{
				$main_msgs[] = $this->lang->words['t_valcannot'] . implode( ", ", $cant );
			}
			
			$this->registry->output->global_message = count($main_msgs) ? implode( "<br />", $main_msgs ) : '';
			
			$this->_viewQueue( 'validating' );
			return;
		}
		
		//-----------------------------------------
		// Ban
		//-----------------------------------------
		
		else if( $this->request['type'] == 'ban' )
		{
			$this->DB->update( 'members', array( 'member_banned' => 1 ), "member_id IN(" . implode( ",", $ids ) . ")" );
			
			$this->DB->delete( 'validating', "member_id IN(" . implode( ",", $ids ) . ")" );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_membanned'] );
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_membanned'];
			
			$this->_viewQueue( 'validating' );
			return;
		}
		
		//-----------------------------------------
		// SPAMMER
		//-----------------------------------------
		
		else if ( $this->request['type'] == 'spam' )
		{
			/* Grab members */
			$members = IPSMember::load( $ids );
			
			/* Load moderator's library */
			require( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php');
			$modLibrary = new moderatorLibrary( $this->registry );
			
			/* Load custom fields class */
			require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
			$fields = new customProfileFields();
			
			/* Load language file */
			$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ), 'forums' );
			
			foreach( $members as $member_id => $member )
			{
				$toSave = array( 'core' => array( 'bw_is_spammer' => 1, 'member_group_id' => $this->settings['member_group'] ) );
				
				/* Protected group? */
				if ( strstr( ','.$this->settings['warn_protected'].',', ','.$member['member_group_id'].',' ) )
				{
					continue;
				}
				
				/* What do to.. */
				if ( $this->settings['spm_option'] )
				{
					switch( $this->settings['spm_option'] )
					{
						case 'disable':
							$toSave['core']['restrict_post']      = 1;
							$toSave['core']['members_disable_pm'] = 2;
						break;
						case 'unapprove':
							$toSave['core']['restrict_post']      = 1;
							$toSave['core']['members_disable_pm'] = 2;
							/* Unapprove posts and topics */
							$modLibrary->toggleApproveMemberContent( $member_id, FALSE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
						break;
						case 'ban':
							/* Unapprove posts and topics */
							$modLibrary->toggleApproveMemberContent( $member_id, FALSE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
							
							$toSave	= array(
											'core'				=> array(
																		'member_banned'		=> 1,
																		'title'				=> '',
																		'bw_is_spammer'		=> 1,
																		),
											'extendedProfile'	=> array(
																		'signature'			=> '',
																		'pp_bio_content'	=> '',
																		'pp_about_me'		=> '',
																		'pp_status'			=> '',
																		)
											);
		
							//-----------------------------------------
							// Avatar
							//-----------------------------------------
							
							$toSave['extendedProfile']['avatar_location']	= "";
							$toSave['extendedProfile']['avatar_size']		= "";
				
							try
							{
								IPSMember::getFunction()->removeAvatar( $member['member_id'] );
							}
							catch( Exception $e )
							{
								// Maybe should show an error or something
							}
							
							//-----------------------------------------
							// Photo
							//-----------------------------------------
							
							IPSMember::getFunction()->removeUploadedPhotos( $member['member_id'] );
				
							$toSave['extendedProfile'] = array_merge( $toSave['extendedProfile'], array(
															'pp_main_photo'		=> '',
															'pp_main_width'		=> '',
															'pp_main_height'	=> '',
															'pp_thumb_photo'	=> '',
															'pp_thumb_width'	=> '',
															'pp_thumb_height'	=> ''
															)	);
		
							//-----------------------------------------
							// Profile fields
							//-----------------------------------------
							
							$fields->member_data = $member;
							$fields->initData( 'edit' );
							$fields->parseToSave( array() );
							
							if ( count( $fields->out_fields ) )
							{
								$toSave['customFields']	= $fields->out_fields;
							}
		
							//-----------------------------------------
							// Update signature content cache
							//-----------------------------------------
							
							IPSContentCache::update( $member['member_id'], 'sig', '' );
						break;
					}
				}
				
				/* Send an email */
				if ( $this->settings['spm_notify'] AND ( $this->settings['email_out'] != $this->memberData['email'] ) )
				{
					IPSText::getTextClass('email')->getTemplate( 'possibleSpammer' );
		
					IPSText::getTextClass('email')->buildMessage( array( 'DATE'         => $this->registry->class_localization->getDate( $member['joined'], 'LONG', 1 ),
																		 'MEMBER_NAME'  => $member['members_display_name'],
																		 'IP'			=> $member['ip_address'],
																		 'EMAIL'		=> $member['email'],
																		 'LINK'         => $this->registry->getClass('output')->buildSEOUrl("showuser=" . $member['member_id'], 'public', $member['members_seo_name'], 'showuser') ) );
		
					IPSText::getTextClass('email')->subject = $this->lang->words['new_registration_email_spammer'] . ' ' . $this->settings['board_name'];
					IPSText::getTextClass('email')->to      = $this->settings['email_out'];
					IPSText::getTextClass('email')->sendMail();
				}
				
				/* Flag them as a spammer */
				IPSMember::save( $member['member_id'], $toSave );
				
				/* Send Spammer to Spam Service */
				if( $this->settings['spam_service_send_to_ips'] && $this->settings['spam_service_api_key'] )
				{
					IPSMember::querySpamService( $member['email'], $member['ip_address'], 'markspam' );
				}
				
				/* Remove validating rows */
				$this->DB->delete( 'validating', "member_id IN(" . implode( ",", $ids ) . ")" );
				
				$this->registry->output->global_message = count($ids) . ' ' . $this->lang->words['t_setasspammers'];
				$this->_viewQueue( 'validating' );
				return;
			}
		}
		
		//-----------------------------------------
		// DELETE
		//-----------------------------------------
		
		else
		{
			$denied	= array();
			
			$this->DB->build( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => "member_id IN(" . implode( ",", $ids ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$denied[] = $r['members_display_name'];
			}
			
			try
			{
				IPSMember::remove( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_regdenied'] . implode( ", ", $denied ) );
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_removedmem'];
			$this->_viewQueue( 'validating' );
			return;
		}
	}
	
	/**
	 * Manage spam requests
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _unSpam()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids = array();
		
		//-----------------------------------------
		// GET checkboxes
		//-----------------------------------------
		
		foreach ( $this->request as $k => $v )
		{
			if ( preg_match( "/^mid_(\d+)$/", $k, $match ) )
			{
				if ( $v )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = IPSLib::cleanIntArray( $ids );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( count($ids) < 1 )
		{	
			$this->registry->output->showError( $this->lang->words['t_nomemunspammed'], 11248 );
		}
		
		//-----------------------------------------
		// Unspam
		//-----------------------------------------
		
		if ( $this->request['type'] == 'unspam' OR $this->request['type'] == 'unspam_posts' )
		{
			try
			{
				foreach( $ids as $i )
				{
					IPSMember::save( $i, array( 'core' => array( 'bw_is_spammer' => 0, 'restrict_post' => 0, 'members_disable_pm' => 0 ) ) );
				}
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}
			
			if ( $this->request['type'] == 'unspam_posts' )
			{
				/* Toggle their content */
				require( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php');
				$modLibrary = new moderatorLibrary( $this->registry );
			
				foreach( $ids as $id )
				{
					$modLibrary->toggleApproveMemberContent( $id, TRUE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
				}
			}
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memunspammed']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memunspammed'];
			$this->_viewQueue( 'spam' );
			return;
		}
		
		//-----------------------------------------
		// Ban
		//-----------------------------------------
		
		else if ( $this->request['type'] == 'ban' OR $this->request['type'] == 'ban_blacklist' )
		{	
			try
			{
				foreach( $ids as $i )
				{
					IPSMember::save( $i, array( 'core' => array( 'bw_is_spammer' => 0, 'member_banned' => 1 ) ) );
				}
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}
			
			if ( $this->request['type'] == 'ban_blacklist' )
			{
				/* Load Members */
				$members = IPSMember::load( $ids );
				$ips     = array();
				$email	 = array();
				$ban     = array( 'ip' => array(), 'email' => array() );
				
				if ( is_array( $members ) AND count( $members ) )
				{
					foreach( $members as $id => $data )
					{
						$ips[]   = $data['ip_address'];
						$email[] = $data['email'];
					}
					
					if ( count( $ips ) )
					{
						/* IPS: Check for duplicate */
						$this->DB->build( array(  'select' => '*', 
												  'from'   => 'banfilters', 
												  'where'  => "ban_content IN ('" . implode( "','", $ips ) . "') and ban_type='ip'" ) );
						$this->DB->execute();
						
						while( $row = $this->DB->fetch() )
						{
							$ban['ip'][] = $row['ban_content'];
						}
						
						/* Now insert.. */
						foreach( $ips as $i )
						{
							if ( ! in_array( $i, $ban['ip'] ) )
							{
								/* Insert the new ban filter */
								$this->DB->insert( 'banfilters', array( 'ban_type' => 'ip', 'ban_content' => $i, 'ban_date' => time(), 'ban_nocache' => 1 ) );
							}
						}
					}
					
					if ( count( $email ) )
					{
						/* IPS: Check for duplicate */
						$this->DB->build( array(  'select' => '*', 
												  'from'   => 'banfilters', 
												  'where'  => "ban_content IN ('" . implode( "','", $email ) . "') and ban_type='email'" ) );
						$this->DB->execute();
						
						while( $row = $this->DB->fetch() )
						{
							$ban['email'][] = $row['ban_content'];
						}
						
						/* Now insert.. */
						foreach( $email as $e )
						{
							if ( ! in_array( $e, $ban['email'] ) )
							{
								/* Insert the new ban filter */
								$this->DB->insert( 'banfilters', array( 'ban_type' => 'email', 'ban_content' => $e, 'ban_date' => time(), 'ban_nocache' => 1 ) );
							}
						}
					}
				}
			}
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_membanned']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_membanned'];
			$this->_viewQueue( 'spam' );
			return;
		}
	}
	
	/**
	 * Manage banned requests
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _unban()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids = array();
		
		//-----------------------------------------
		// GET checkboxes
		//-----------------------------------------
		
		foreach ( $this->request as $k => $v )
		{
			if ( preg_match( "/^mid_(\d+)$/", $k, $match ) )
			{
				if ( $v )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = IPSLib::cleanIntArray( $ids );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( count($ids) < 1 )
		{	
			$this->registry->output->showError( $this->lang->words['t_nomemunban'], 11248 );
		}
		
		//-----------------------------------------
		// Unlock
		//-----------------------------------------
		
		if ( $this->request['type'] == 'unban' )
		{
			try
			{
				foreach( $ids as $i )
				{
					IPSMember::save( $i, array( 'core' => array( 'member_banned' => 0 ) ) );
				}
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memunbanned']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memunbanned'];
			$this->_viewQueue( 'banned' );
			return;
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		else if ( $this->request['type'] == 'delete' )
		{	
			IPSMember::remove( $ids );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memdeleted']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memdeleted'];
			$this->_viewQueue( 'banned' );
			return;
		}
	}
	
	/**
	 * Unapprove email change request
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _emailUnapprove()
	{
		//-----------------------------------------
		// GET member
		//-----------------------------------------
		
		if( !$this->request['mid'] )
		{
			$this->registry->output->showError( $this->lang->words['t_noemailloc'], 11249 );
		}

		$member = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'validating', 'where' => 'email_chg=1 AND member_id=' . intval($this->request['mid']) ) );
		
		if( !$member['vid'] )
		{
			$this->registry->output->showError( $this->lang->words['t_noemailloc'], 11250 );
		}

		$this->DB->delete( "validating", "vid='{$member['vid']}'" );
		
		try
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'email' => $member['prev_email'], 'member_group_id' => $member['real_group'] ) ) );
		}
		catch( Exception $error )
		{
			$this->registry->output->showError( $error->getMessage(), 11247 );
		}
		IPSLib::runMemberSync( 'onGroupChange', $member['member_id'], $member['real_group'] );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['t_emailchangeun'], $member['member_id'] ) );

		$this->registry->output->global_message = sprintf( $this->lang->words['t_emailchangeun'], $member['member_id'] );
		$this->_viewQueue( 'validating' );
	}
	
	
	/**
	 * Unlock selected accounts
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _unlock()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids = array();
		
		//-----------------------------------------
		// GET checkboxes
		//-----------------------------------------
		
		foreach ( $this->request as $k => $v )
		{
			if ( preg_match( "/^mid_(\d+)$/", $k, $match ) )
			{
				if ( $v )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = IPSLib::cleanIntArray( $ids );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( count($ids) < 1 )
		{	
			$this->registry->output->showError( $this->lang->words['t_nolockloc'], 11251 );
		}

		//-----------------------------------------
		// Unlock
		//-----------------------------------------
		
		if ( $this->request['type'] == 'unlock' )
		{
			foreach( $ids as $_id )
			{
				try
				{
					IPSMember::save( $_id, array( 'core' => array( 'failed_logins' => '', 'failed_login_count' => 0 ) ) );
				}
				catch( Exception $error )
				{
					$this->registry->output->showError( $error->getMessage(), 11247 );
				}
			}
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memunlocked']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memunlocked'];
			$this->_viewQueue( 'locked' );
			return;
		}
		
		//-----------------------------------------
		// Ban
		//-----------------------------------------
		
		else if ( $this->request['type'] == 'ban' )
		{	
			try
			{	
				IPSMember::save( $ids, array( 'core' => array( 'failed_logins' => '', 'failed_login_count' => 0, 'member_banned' => 1 ) ) );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_membanned']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_membanned'];
			$this->_viewQueue( 'locked' );
			return;
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		else if ( $this->request['type'] == 'delete' )
		{	
			IPSMember::remove( $ids );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memdeleted']);
			
			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memdeleted'];
			$this->_viewQueue( 'locked' );
			return;
		}
	}			
}