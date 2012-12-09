<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member management
 * Last Updated: $Date: 2009-06-30 12:06:12 -0400 (Tue, 30 Jun 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 4829 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_forums_tools_tools extends ipsCommand
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
	 * Editor object
	 *
	 * @access	private
	 * @var		object			Editor library
	 */
	private $han_editor;

	/**
	 * Trash can forum id
	 *
	 * @access	private
	 * @var		integer			Trash can forum
	 */
	private $trash_forum		= 0;

	
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
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_member_form');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=tools';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=tools';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ), 'forums' );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'deleteposts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_deleteposts' );
				$this->_deletePostsStart();
			break;
			
			case 'deletesubscriptions':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_deletesubscriptions' );
				$this->_deleteSubscriptions();
			break;
			
			case 'clearforumsubs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_clearforumsubs' );
				$this->_deleteForumSubscriptions();
			break;
			
			case 'deleteposts_process':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_deleteposts' );
				$this->_deletePostsDo();
			break;
			
			case 'new_avatar':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_photo', 'members', 'members' );
				$this->_processAvatar();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
		
	}
	
	/**
	 * Delete a forum's email subscriptions
	 * 
	 * @access	private
	 * @return	void
	 * @author	Brandon Farber
	 * @since	IPB3 / 22 Oct 2008
	 */
	private function _deleteForumSubscriptions()
	{
		/**
		 * Get members watching forums so we can recache em..
		 */
		$forum_id	= intval( $this->request['f'] );
		$members	= array();
		
		$this->DB->build( array( 'select' => 'member_id', 'from' => 'forum_tracker', 'where' => 'forum_id=' . $forum_id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$members[]	= $r['member_id'];
		}
		
		$this->DB->delete( 'forum_tracker', 'forum_id=' . $forum_id );
		
		foreach( $members as $mid )
		{
			$this->registry->getClass('class_forums')->recacheWatchedForums( $mid );
		}
		
		/**
		 * Topics is teh suck...gotta get em first to delete em
		 */
		$toDelete	= array();
		
		$this->DB->build( array( 'select'		=> 'tr.trid',
									'from'		=> array( 'tracker' => 'tr' ),
									'where'		=> 't.forum_id=' . $forum_id,
									'add_join'	=> array(
														array(
															'from'	=> array( 'topics' => 't' ),
															'where'	=> 't.tid=tr.topic_id',
															'type'	=> 'left'
															)
														)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$toDelete[]	= $r['trid'];
		}
		
		if( count($toDelete) )
		{
			$this->DB->delete( 'tracker', 'trid IN(' . implode( ',', $toDelete ) . ')' );
		}
		
		$this->registry->output->redirect( $this->settings['_base_url'] . "app=forums", $this->lang->words['m_subsf_redirect'] );

	}

	/**
	 * Delete a member's email subscriptions
	 * 
	 * @access	private
	 * @return	void
	 * @author	Brandon Farber
	 * @since	IPB3 / 22 Oct 2008
	 */
	private function _deleteSubscriptions()
	{
		$member_id			= intval( $this->request['member_id'] );
		
		$this->DB->delete( 'tracker', 'member_id=' . $member_id );
		$this->DB->delete( 'forum_tracker', 'member_id=' . $member_id );
		
		$this->registry->output->redirect( $this->settings['_base_url'] . "app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$this->request['member_id']}", $this->lang->words['m_subs_redirect'] );
	}

	/**
	 * Update a member's avatar
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 * @author	Brandon Farber
	 * @since	IPB3 / 9 June 2008
	 */
	private function _processAvatar()
	{
		$member_id = intval( $this->request['member_id'] );
		
		try
		{
			IPSMember::getFunction()->saveNewAvatar( $member_id );
		}
		catch( Exception $error )
		{
			switch ( $error->getMessage() )
			{
				case 'NO_MEMBER_ID':
					$this->registry->output->showError( $this->lang->words['t_noid'], 11356 );
				break;
				case 'NO_PERMISSION':
					$this->registry->output->showError( $this->lang->words['t_permav'], 11357, true );
				break;
				case 'UPLOAD_NO_IMAGE':
					$this->registry->output->showError( $this->lang->words['t_uploadfail1'], 11358 );
				break;
				case 'UPLOAD_INVALID_FILE_EXT':
					$this->registry->output->showError( $this->lang->words['t_uploadfail2'], 11359 );
				break;
				case 'UPLOAD_TOO_LARGE':
					$this->registry->output->showError( $this->lang->words['t_uploadfail3'], 11360 );
				break;
				case 'UPLOAD_CANT_BE_MOVED':
					$this->registry->output->showError( $this->lang->words['t_uploadfail4'], 11361 );
				break;
				case 'UPLOAD_NOT_IMAGE':
					$this->registry->output->showError( $this->lang->words['t_uploadfail5'], 2131, true );
				break;
				case 'NO_AVATAR_TO_SAVE':
					$this->registry->output->showError( $this->lang->words['t_noav'], 11362 );
				break;
				case 'INVALID_FILE_EXT':
					$this->registry->output->showError( $this->lang->words['t_badavext'], 11362 );
				break;
			}
		}

		$this->registry->output->redirect( $this->settings['_base_url'] . "app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$this->request['member_id']}", $this->lang->words['t_avupdated'] );
	}
	
	/**
	 * Delete a member's posts [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _deletePostsDo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id			= intval( $this->request['member_id'] );
		$delete_posts		= intval( $this->request['dposts'] );
		$delete_topics		= intval( $this->request['dtopics'] );
		$end				= intval( $this->request['dpergo'] ) ? intval( $this->request['dpergo'] ) : 50;
		$init				= intval( $this->request['init'] );
		$done				= 0;
		$start				= intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$forums_affected	= array();
		$topics_affected	= array();
		$img				= '<img src="' . $this->settings['skin_acp_url'] . '/images/aff_tick_small.png" border="0" alt="-" /> ';
		$posts_deleted		= 0;
		$topics_deleted		= 0;
		
		//--------------------------------------------
		// NOT INIT YET?
		//--------------------------------------------
		
		if ( ! $init )
		{
			$url = $this->settings['base_url'] . '&' . $this->form_code_js . "&do=deleteposts_process&dpergo=" . $this->request['dpergo']
																			  ."&st=0"
																			  ."&init=1"
																			  ."&dposts={$delete_posts}"
																			  ."&dtopics={$delete_topics}"
																			  ."&use_trash_can=".intval($this->request['use_trash_can'])
																			  ."&member_id={$member_id}";
																			  
			$this->registry->output->multipleRedirectInit( $url );
		}

		//--------------------------------------------
		// Not loaded the func?
		//--------------------------------------------
		
		if ( ! is_object( $mod_func ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php' );
			$this->registry->setClass( 'classItemMarking', new classItemMarking( $this->registry ) );
			
			require_once( IPSLib::getAppDir('forums') . '/sources/classes/moderate.php' );
			$mod_func	=  new moderatorLibrary( $this->registry );
		}
		
        //-----------------------------------------
        // Trash-can set up
        //-----------------------------------------
        
        $trash_append = '';
        
        if ( $this->settings['forum_trash_can_enable'] and $this->settings['forum_trash_can_id'] )
        {
        	if ( $this->registry->class_forums->forum_by_id[ $this->settings['forum_trash_can_id'] ]['sub_can_post'] )
        	{
        		if ( $this->request['use_trash_can'] )
        		{
        			$this->trash_forum	= $this->settings['forum_trash_can_id'];
        			$trash_append		= " AND forum_id<>{$this->trash_forum}";
        		}
        	}
        }
        
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'core' );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "p.author_id={$member_id}{$trash_append}",
									'order'		=> 'p.pid ASC',
									'limit'		=> array( $start, $end ),
									'add_join'	=> array(
														array(
															'select'	=> 't.*',
															'from'		=> array( 'topics' => 't' ),
															'where'		=> 't.tid=p.topic_id',
															'type'		=> 'left',
															)
														)
						)		);
		$outer = $this->DB->execute();
		
		//-----------------------------------------
		// Process...
		//-----------------------------------------
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Copy record to topic array
			//-----------------------------------------
			
			$topic	= $r;
			
			//-----------------------------------------
			// No longer a topic?
			//-----------------------------------------
			
			if ( ! $topic['tid'] )
			{
				continue;
			}
			
			$done++;

			//-----------------------------------------
			// Get number of MID posters
			//-----------------------------------------
			
			$topic_i_posted = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
																		'from'	=> 'posts',
																		'where'	=> 'author_id=' . $member_id . ' AND topic_id=' . $r['topic_id'] ) );
			
			//-----------------------------------------
			// Aready deleted this topic?
			//-----------------------------------------
			
			if ( ! $topic_i_posted['count'])
			{
				continue;
			}

			//-----------------------------------------
			// First check: Our topic and no other replies?
			//-----------------------------------------
			
			if ( $topic['starter_id'] == $member_id AND $topic_i_posted['count'] == ( $topic['posts'] + 1 ) )
			{
				//-----------------------------------------
				// Ok, deleting topics or posts?
				//-----------------------------------------
				
				if ( ( $delete_posts OR $delete_topics ) AND ( $this->trash_forum and $this->trash_forum != $topic['forum_id'] ) )
				{
					//-----------------------------------------
					// Move, don't delete
					//-----------------------------------------
					
					$mod_func->topicMove( $r['topic_id'], $topic['forum_id'], $this->trash_forum );
					
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$forums_affected[ $this->trash_forum ] = $this->trash_forum;
					
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}				
				else if ( $delete_posts OR $delete_topics )
				{
					$this->DB->delete( 'posts', 'author_id=' . $member_id . ' AND topic_id=' . $r['topic_id'] );
					$this->DB->delete('topics', 'tid=' . $r['topic_id'] );
																	  
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}
			}
			
			//-----------------------------------------
			// Is this a topic we started?
			//-----------------------------------------
			
			else if ( $topic['starter_id'] == $member_id AND $delete_topics )
			{
				if ( $this->trash_forum and $this->trash_forum != $topic['forum_id'] )
				{
					//-----------------------------------------
					// Move, don't delete
					//-----------------------------------------
					
					$mod_func->topicMove( $r['topic_id'], $topic['forum_id'], $this->trash_forum );
					
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$forums_affected[ $this->trash_forum ] = $this->trash_forum;
					
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}				
				else
				{				
					$this->DB->delete( 'posts', 'topic_id=' . $r['topic_id'] );
					$this->DB->delete( 'topics', 'tid=' . $r['topic_id'] );
																	  
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_deleted++;
					$posts_deleted += $topic['posts'] + 1;
				}
			}
			
			//-----------------------------------------
			// Just delete the post, then
			//-----------------------------------------
			
			else if ( $delete_posts AND ! $r['new_topic'] )
			{
				if ( $this->trash_forum and $this->trash_forum != $topic['forum_id'] )
				{
					//-----------------------------------------
					// Set up and pass to split topic handler
					//-----------------------------------------
					
					$new_title   = $this->lang->words['acp_posts_deleted_from'] . $topic['title'];
					$new_desc    = $this->lang->words['acp_posts_deleted_from_tid'] . $topic['tid'];
					
					//-----------------------------------------
					// Is first post queued?
					//-----------------------------------------
					
					$topic_approved	= 1;
					
					$first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																			'from'	=> 'posts',
																			'where'	=> "pid=" . $r['pid'],
																	)      );
					
					if( $first_post['queued'] )
					{
						$topic_approved = 0;
						$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $first_post['pid'] );
					}
					
					//-----------------------------------------
					// Complete a new dummy topic
					//-----------------------------------------
					
					$this->DB->insert( 'topics',  array(
											'title'				=> $new_title,
											'description'		=> $new_desc,
											'state'				=> 'open',
											'posts'				=> 0,
											'starter_id'		=> $member_id,
											'starter_name'		=> $member['members_display_name'],
											'start_date'		=> time(),
											'last_poster_id'	=> $member_id,
											'last_poster_name'	=> $member['members_display_name'],
											'last_post'			=> time(),
											'icon_id'			=> 0,
											'author_mode'		=> 1,
											'poll_state'		=> 0,
											'last_vote'			=> 0,
											'views'				=> 0,
											'forum_id'			=> $this->trash_forum,
											'approved'			=> $topic_approved,
											'pinned'			=> 0,
									)               );

					$new_topic_id = $this->DB->getInsertId();
			
					//-----------------------------------------
					// Move the posts
					//-----------------------------------------
					
					$this->DB->update( 'posts', array( 'topic_id' => $new_topic_id, 'new_topic' => 0, 'queued' => 0 ), "pid={$r['pid']}" ); 
					$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$topic['tid']}" );

					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$forums_affected[ $this->trash_forum ] = $this->trash_forum;
					$topics_affected[ $topic['tid']      ] = $topic['tid'];
					$topics_affected[ $new_topic_id      ] = $new_topic_id;

					$posts_deleted++;
				}
				else
				{
					$this->DB->delete( 'posts', 'pid=' . $r['pid'] );

					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_affected[ $topic['tid']      ] = $topic['tid'];

					$posts_deleted++;
				}
			}
		}
		
		//-----------------------------------------
		// Rebuild topics and forums
		//-----------------------------------------
		
		if ( count( $topics_affected ) )
		{
			foreach( $topics_affected as $tid )
			{
				$mod_func->rebuildTopic( $tid, 0 );
			}
		}
		
		if ( count( $forums_affected ) )
		{
			foreach( $forums_affected as $fid )
			{
				$mod_func->forumRecount( $fid );
			}
		}
		
		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done )
		{
		 	//-----------------------------------------
			// Recount stats..
			//-----------------------------------------
			
			$mod_func->statsRecount();
			
			//-----------------------------------------
			// Reset member's posts
			//-----------------------------------------
			
			$forums = array();
			
			foreach( $this->registry->class_forums->forum_by_id as $data )
			{
				if ( ! $data['inc_postcount'] )
				{
					$forums[] = $data['id'];
				}
			}

			if ( ! count( $forums ) )
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'posts', 'where' => 'queued != 1 AND author_id=' . $member_id ) );
			}
			else
			{
				$count = $this->DB->buildAndFetch( array( 'select'	=> 'count(p.pid) as count',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.queued <> 1 AND p.author_id=' . $member_id . ' AND t.forum_id NOT IN (' . implode( ",", $forums ) . ')',
																'add_join'	=> array( 
																					array( 'type'	=> 'left',
																		 					'from'	=> array( 'topics' => 't' ),
																		 					'where'	=> 't.tid=p.topic_id'
																		 				)			
																		 			)
																)		);
			}
			
			$new_post_count = intval( $count['count'] );
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['member_posts_deleted'], $member['members_display_name'] ) );
			
			IPSMember::save( $member_id, array( 'core' => array( 'posts' => $new_post_count ) ) );

			$this->registry->output->multipleRedirectFinish( $this->lang->words['mem_posts_process_done'] );
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			
			$next = $start + $end;
			
			$url = $this->settings['base_url'] . '&' . $this->form_code_js . "&do=deleteposts_process&dpergo={$end}"
																			  ."&st={$next}"
																			  ."&init=1"
																			  ."&dposts={$delete_posts}"
																			  ."&dtopics={$delete_topics}"
																			  ."&use_trash_can=".intval($this->request['use_trash_can'])
																			  ."&member_id={$member_id}";
																			  
			$text = sprintf( $this->lang->words['mem_posts_process_more'], $end, $posts_deleted, $topics_deleted );
			
			$this->registry->output->multipleRedirectHit( $url, $img . ' ' . $text );
		}
	}
	
	/**
	 * Delete a member's posts [form/confirmation]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _deletePostsStart()
	{
		//-----------------------------------------
		// Page set up
		//-----------------------------------------

		$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['mem_delete_title'] );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval($this->request['member_id']);
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'core' );

		//-----------------------------------------
		// Get number of topics member has started
		//-----------------------------------------
		
		$topics = $this->DB->buildAndFetch( array( 'select'	=> 'count(*) as count',
															'from'	=> 'topics',
															'where'	=> 'starter_id=' . $member_id ) );
																	
		$posts  = $this->DB->buildAndFetch( array( 'select'	=> 'count(*) as count',
															'from'	=> 'posts',
															'where'	=> 'author_id=' . $member_id ) );
		
		//-----------------------------------------
		// Got any posts?
		//-----------------------------------------
		
		if ( ! $posts['count'] )
		{
			$this->registry->output->showError( $this->lang->words['t_noposts'], 11363 );
		}
		
		//-----------------------------------------
		// Get number of topics member has started
		//-----------------------------------------
		
		$this->lang->words['mem_delete_delete_posts']  = sprintf( $this->lang->words['mem_delete_delete_posts'] , intval($posts['count']) );
		$this->lang->words['mem_delete_delete_topics'] = sprintf( $this->lang->words['mem_delete_delete_topics'], intval($topics['count']) );
		
		$this->registry->output->html .= $this->html->deletePostsStart( $member, intval($topics['count']), intval($posts['count']) );
		
		
	}
}