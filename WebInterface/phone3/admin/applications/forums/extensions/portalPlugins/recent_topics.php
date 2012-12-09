<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal plugin: recent topics
 * Last Updated: $Date: 2009-07-24 23:26:45 -0400 (Fri, 24 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		1st march 2002
 * @version		$Revision: 4940 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_recent_topics extends public_portal_portal_portal 
{
	/**
	 * Initialize module
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
	{
		$this->settings['csite_article_date'] = $this->settings['csite_article_date'] ? $this->settings['csite_article_date'] : '%c';
	}

	/**
	 * Show the recently started topic titles
	 *
	 * @access	public
	 * @return	string		HTML content to replace tag with
	 */
	public function recent_topics_discussions_last_x()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		$results	= array();
		$limit		= $this->settings['recent_topics_discuss_number'] ? $this->settings['recent_topics_discuss_number'] : 5;

		$where_clause	= array();
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk  = array();
	
		foreach( $this->registry->class_forums->forum_by_id as $id => $data )
		{
			/* Can we read? */
			if ( ! $this->registry->permissions->check( 'read', $data ) )
			{
				continue;
			}

			/* Can read, but is it password protected, etc? */
			if ( ! $this->registry->class_forums->forumsCheckAccess( $id, 0, 'forum', array(), true ) )
			{
				continue;
			}

			if ( ! $data['can_view_others'] )
			{
				continue;
			}
			
			if ( $data['min_posts_view'] > $this->memberData['posts'] )
			{
				continue;
			}

			$forumIdsOk[] = $id;
		}
		
		if ( count( $forumIdsOk) )
		{
			/* Add allowed forums */
			$where_clause[] = "t.forum_id IN (" . implode( ",", $forumIdsOk ) . ")";

			$this->DB->build( array( 'select'		=> 't.tid, t.title, t.posts, t.start_date as post_date, t.views, t.title_seo',
											'from'		=> array( 'topics' => 't' ),
											'where'		=> "t.approved=1 and t.state != 'closed' and (t.state != 'link') " . ( count($where_clause) ? ' AND ' . implode( ' AND ', $where_clause ) : '' ),
											'order'		=> 't.tid DESC',
											'limit'		=> array( 0, $limit ),
											'add_join'	=> array(
																array(
																		'from'		=> array( 'forums' => 'f' ),
																		'where'		=> "f.id=t.forum_id",
																		'type'		=> 'left',
																	),
																array(
																		'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> "m.member_id=t.starter_id",
																		'type'		=> 'left',
																	),
																)
								) 		);
			$outer = $this->DB->execute();
		
			while ( $row = $this->DB->fetch($outer) )
			{
				$row['title_display']	= IPSText::truncate( $row['title'], 30 );
				$row['date']			= $this->registry->class_localization->getDate( $row['post_date'], "manual{{$this->settings['csite_article_date']}}" );
 
				$results[] = $row;
			}
		}

		return $this->registry->getClass('output')->getTemplate('portal')->latestPosts( $results );
	}

	
	/**
	 * Show the "news" articles
	 *
	 * @access	public
	 * @return	string		HTML content to replace tag with
	 */
	public function recent_topics_last_x()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------

 		$attach_pids	= array();
 		$attach_posts	= array();
 		$forums			= array();
 		$rows			= array();
 		$output			= array();
		$where_clause	= array();
 		$limit			= $this->settings['recent_topics_article_max'] ? $this->settings['recent_topics_article_max'] : 5;
 		$posts			= intval($this->memberData['posts']);

 		//-----------------------------------------
    	// Grab articles new/recent in 1 bad ass query
    	//-----------------------------------------

 		foreach( explode( ',', $this->settings['recent_topics_article_forum'] ) as $forum_id )
 		{
 			if( !$forum_id )
 			{
 				continue;
 			}

 			$forums[] = intval($forum_id);
 		}
 		
 		if( !count($forums) )
 		{
 			return;
 		}
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk  = array();
	
		foreach( $this->registry->class_forums->forum_by_id as $id => $data )
		{
			/* Allowing this forum? */
			if ( ! in_array( $id, $forums, $id ) )
			{
				continue;
			}
			
			/* Can we read? */
			if ( ! $this->registry->permissions->check( 'read', $data ) )
			{
				continue;
			}

			/* Can read, but is it password protected, etc? */
			if ( ! $this->registry->class_forums->forumsCheckAccess( $id, 0, 'forum', array(), true ) )
			{
				continue;
			}

			if ( ! $data['can_view_others'] )
			{
				continue;
			}
			
			if ( $data['min_posts_view'] > $posts )
			{
				continue;
			}

			$forumIdsOk[] = $id;
		}

		if( !count($forumIdsOk) )
		{
			return '';
		}

		/* Add allowed forums */
		$where_clause[] = "t.forum_id IN (" . implode( ",", $forumIdsOk ) . ")";

		//-----------------------------------------
		// Will we need to parse attachments?
		//-----------------------------------------
		
		$parseAttachments	= false;
		
		//-----------------------------------------
		// Run query
		//-----------------------------------------
		
		$pinned   = array();
		$unpinned = array();
		$all	  = array();
		$data     = array();
		$count    = 0;
		
		if( !$this->settings['portal_exclude_pinned'] )
		{
			/* Fetch all pinned topics to avoid filesort */
			$this->DB->build( array( 'select' => 't.tid, t.start_date',
									 'from'   => 'topics t',
									 'where'  => "t.pinned=1 AND t.approved=1 AND t.state != 'link'" . ( count($where_clause) ? ' AND ' . implode( ' AND ', $where_clause ) : '' ),
									 //'order'  => 't.tid DESC',
									 'limit'  => array ( $limit ) ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$pinned[ $row['start_date'] ] = $row['tid'];
				$all[ $row['start_date'] ]    = $row['tid'];
			}
		}
		
		/* Still need more? */
		
		if ( count( $pinned ) < $limit )
		{
			$pinnedWhere	= $this->settings['portal_exclude_pinned'] ? "" : "t.pinned=0 AND ";
			
			$this->DB->build( array( 'select' => 't.tid, t.start_date',
									 'from'   => 'topics t',
									 'where'  => $pinnedWhere . "t.approved=1 AND t.state != 'link'" . ( count($where_clause) ? ' AND ' . implode( ' AND ', $where_clause ) : '' ),
									 'order'  => 'tid DESC',
									 'limit'  => array ( $limit - count( $pinned ) ) ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$unpinned[ $row['start_date'] ] = $row['tid'];
				$all[ $row['start_date'] ]      = $row['tid'];
			}
		}
		
		/* got anything? */
		if ( ! count( $all ) )
		{
			return;
		}
		
		$this->DB->build( array( 
								'select'	=> 't.*',
								'from'		=> array( 'topics' => 't' ),
								'where'		=> "t.tid IN (" . implode( ",",  array_values( $all ) ) . ")",
								'add_join'	=> array(
													array( 
															'select'	=> 'p.*',
															'from'	=> array( 'posts' => 'p' ),
															'where'	=> 'p.pid=t.topic_firstpost',
															'type'	=> 'left'
														),
													array(
															'select'	=> 'f.use_html',
															'from'		=> array( 'forums' => 'f' ),
															'where'		=> "f.id=t.forum_id",
															'type'		=> 'left',
														),
													array( 
															'select'	=> 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name, m.mgroup_others, m.login_anonymous, m.last_visit, m.last_activity',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=p.author_id',
															'type'		=> 'left'
														),
													array( 
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left'
														),
												
													)
					)		);
		
		$outer = $this->DB->execute();
		
 		//-----------------------------------------
 		// Loop through..
 		//-----------------------------------------
 		
 		while( $row = $this->DB->fetch($outer) )
 		{
			$data[ $row['tid'] ] = $row;
		}
		
		krsort( $unpinned );
		krsort( $pinned );
		
		foreach( $unpinned as $date => $tid )
		{
			if ( count( $pinned ) < $limit )
			{
				$pinned[ $date ] = $tid;
			}
			else
			{
				break;
			}
			
			$count++;
		}
		
		/* Now put it altogether */
		foreach( $pinned as $date => $tid )
		{
 			//-----------------------------------------
 			// INIT
 			//-----------------------------------------
 			
			$entry              = $data[ $tid ];
 			$bottom_string		= "";
 			$read_more			= "";
 			$top_string			= "";
 			$got_these_attach	= 0;
 			
			if( $entry['topic_hasattach'] )
			{
				$parseAttachments	= true;
			}

			//-----------------------------------------
			// Parse the post
			//-----------------------------------------
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $entry['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $entry['use_html'] and $entry['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $entry['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $entry['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $entry['mgroup_others'];
			$entry['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $entry['post'] );
 			
 			//-----------------------------------------
 			// BASIC INFO
 			//-----------------------------------------
 			
 			$real_posts			= $entry['posts'];
 			$entry['posts']		= ipsRegistry::getClass('class_localization')->formatNumber(intval($entry['posts']));

 			if( !$entry['author_id'] )
 			{
				$entry['members_display_name']	= $this->settings['guest_name_pre'] . $entry['author_name'] . $this->settings['guest_name_suf'];
				$entry['member_id']				= 0;
			}
			else
			{
				$entry	= IPSMember::buildDisplayData( $entry );
			}

 			//-----------------------------------------
 			// Get Date
 			//-----------------------------------------
 			
 			$entry['date'] = $this->registry->class_localization->getDate( $entry['post_date'], "manual{" . $this->settings['csite_article_date'] . "}" );

 			//-----------------------------------------
			// Attachments?
			//-----------------------------------------
			
			if( $entry['pid'] )
			{
				$attach_pids[ $entry['pid'] ] = $entry['pid'];
			}
 			
 			//-----------------------------------------
 			// Avatar
 			//-----------------------------------------
 			
 			$entry['avatar']	= IPSMember::buildAvatar( $entry );

			if ( IPSMember::checkPermissions('download', $entry['forum_id'] ) === FALSE )
			{
				$this->settings[ 'show_img_upload'] =  0 ;
			} 
			
			//-----------------------------------------
			// View image...
			//-----------------------------------------
		 			
			$entry['post'] = IPSText::getTextClass( 'bbcode' )->memberViewImages( $entry['post'] );	
 			
			$rows[] = $entry;
 		}
 		
 		$output = $this->registry->getClass('output')->getTemplate('portal')->articles( $rows );
 		
 		//-----------------------------------------
 		// Process Attachments
 		//-----------------------------------------
 		
 		if ( $parseAttachments AND count( $attach_pids ) )
 		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
				$this->class_attach                  =  new class_attach( $this->registry );
				
				$this->class_attach->attach_post_key =  '';

				ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_topic' ), 'forums' );
			}
			
			$this->class_attach->attach_post_key	=  '';
			$this->class_attach->type				= 'post';
			$this->class_attach->init();
		
			$output = $this->class_attach->renderAttachments( $output, $attach_pids );
			$output	= $output[0]['html'];
 		}
 		
 		return $output;
 	}
}