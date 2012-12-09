<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Moderator actions
 * Last Updated: $Date: 2009-08-06 15:59:52 -0400 (Thu, 06 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Revision: 4990 $
 *
 * @todo 		[Future] Get rid of init method, determine data dynamically (necessary for any centralized moderation routine, e.g. from search results)
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class moderatorLibrary
{
	/**
	 * Moderator information
	 *
	 * @access	public
	 * @var		array		Array of moderator details
	 */
	public $moderator		= array();

	/**
	 * Forum information
	 *
	 * @access	public
	 * @var		array		Array of forum details
	 */
	public $forum			= array();

	/**
	 * Topic information
	 *
	 * @access	public
	 * @var		array		Array of topic details
	 */
	public $topic			= array();
	
	/**
	 * Error code encountered
	 *
	 * @access	public
	 * @var		string		Error code
	 */
	public $error 			= "";
	
	/**
	 * Automatically update forum info
	 *
	 * @access	public
	 * @var		boolean		Whether or not to automatically update forum info
	 */
	public $auto_update		= false;
	
	/**
	 * Stored statement
	 *
	 * @access	public
	 * @var		string		Stored multi-mod statement
	 */
	public $stm				= "";

	/**
	 * Upload directory
	 *
	 * @access	public
	 * @var		string		Upload directory
	 */
	public $upload_dir 		= "./uploads";
	
	/**#@+
	 * Registry Object Shortcuts
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Check for class_forums */
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
			$this->registry->setClass( 'class_forums', new class_forums( $registry ) );
			$this->registry->class_forums->forumsInit();
		}
		
		$this->upload_dir = $this->settings['upload_dir'];
	}
	
	/**
	 * Initialization
	 *
	 * @access	public
	 * @param	array 		Forum array
	 * @param 	array 		[Optional] Topic array
	 * @param	array 		[Optional] Moderator array
	 * @return	boolean		True
	 */
	public function init($forum="", $topic="", $moderator="")
	{
		$this->forum = $forum;
		
		if ( is_array($topic) )
		{
			$this->topic = $topic;
		}
		
		if ( is_array($moderator) )
		{
			$this->moderator = $moderator;
		}
		
		return true;
	}
	
	/**
	 * Toggle approve status of content by a member
	 * WARNING: This is a utility function. No permission checks are performed. 
	 *
	 * @access	public
	 * @param	int			Member ID (topic starter / post author)
	 * @param	boolean		TRUE = approve, FALSE = unapprove
	 * @param	string		Option [ topics / replies / all ]
	 * @param	int			[ Optional: last X hours worth of data ]
	 * @return	boolean
	 */
	public function toggleApproveMemberContent( $memberID, $approve=FALSE, $option, $date=0 )
	{
		$memberID  = intval( $memberID );
		$date	   = intval( $date );
		$timeCut   = ( $date ) ? ( time() - ( $date * 3600 ) ) : 0;
		$topicFind = '';
		$postFind  = '';
		$forumIDs  = array();
		$topicIDs  = array();
		
		if ( ! $memberID )
		{
			return FALSE;
		}
		
		switch ( $option )
		{
			default:
			case 'all':
			case 'both':
			case 'topics':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
				$topicFind  = 'starter_id=' . $memberID;
				$topicFind .= ( $timeCut ) ? ' AND start_date > ' . $timeCut : '';
			break;
			case 'replies':
			case 'posts':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
			break;
		}
		
		//-----------------------------------------
		// Find forums..
		//-----------------------------------------
		
		if ( $topicFind )
		{
			$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'forum_id' ),
							  		 'from'   => 'topics',
									 'where'  => $topicFind ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forumIDs[ $row['forum_id'] ] = $row['forum_id'];
			}
		}
		
		if ( $postFind )
		{
			$this->DB->build( array( 'select'   => $this->DB->buildDistinct( 't.forum_id' ) . ',t.tid',
							  		 'from'     => array( 'posts' => 'p' ),
									 'where'    => $postFind,
									 'add_join' => array( array( 'select' => '',
																 'from'   => array( 'topics' => 't' ),
																 'where'  => 'p.topic_id=t.tid' ) ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forumIDs[ $row['forum_id'] ]	= $row['forum_id'];
				$topicIDs[ $row['tid'] ]		= $row['tid'];
			}
		}
		
		//-----------------------------------------
		// Run...
		//-----------------------------------------
		
		if ( $topicFind )
		{
			$this->DB->update( 'topics', array( 'approved' => ( $approve === TRUE ) ? 1 : 0 ), $topicFind );
		}
		
		if ( $postFind )
		{
			$this->DB->update( 'posts', array( 'queued' => ( $approve === TRUE ) ? 0 : 1 ), $postFind );
		}

		if ( count( $topicIDs ) )
		{
			foreach( $topicIDs as $id )
			{
				$this->rebuildTopic( $id );
			}
		}

		if ( count( $forumIDs ) )
		{
			foreach( $forumIDs as $id )
			{
				$this->forumRecount( $id );
			}
		}
		
		$this->statsRecount();
		
		return TRUE;
	}
	
	/**
	 * Delete content by a member
	 * WARNING: This is a utility function. No permission checks are performed.
	 * NOTE: This function is unfinished and unused. Might be useful to finish it for 3.1
	 * The problem is mostly deleting posts when the trash can is in-use. You'd need to split them
	 * and create a new topic for each which would be incredibly expensive without breaking it down into
	 * batches.
	 *
	 * @todo [Future] Deleting topics and posts is bloody expensive. This limits to 150 ids or it could bust the IN() SQL statement
	 *		 Probably need to find a neater way to do this.. Task? 
	 *
	 * @access	public
	 * @param	int			Member ID (topic starter / post author)
	 * @param	string		Option [ topics / replies / all ]
	 * @param	int			[ Optional: last X hours worth of data ]
	 * @return	boolean
	 */
	public function deleteMemberContent( $memberID, $option, $date=0 )
	{
		$memberID  = intval( $memberID );
		$date	   = intval( $date );
		$timeCut   = ( $date ) ? ( time() - ( $date * 3600 ) ) : 0;
		$topicFind = '';
		$postFind  = '';
		$topicIDs  = array();
		$postIDs   = array();
		$forumIDs  = array();
		
		if ( ! $memberID )
		{
			return FALSE;
		}
		
		switch ( $option )
		{
			default:
			case 'all':
			case 'both':
			case 'topics':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
				$topicFind  = 'starter_id=' . $memberID;
				$topicFind .= ( $timeCut ) ? ' AND start_date > ' . $timeCut : '';
			break;
			case 'replies':
			case 'posts':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
			break;
		}
		
		//-----------------------------------------
		// Run...
		//-----------------------------------------
		
		if ( $topicFind )
		{
			$this->DB->build( array( 'select' => 'tid, forum_id',
							  		 'from'   => 'topics',
									 'where'  => $topicFind,
									 'order'  => 'start_date DESC',
									 'limit'  => array( 0, 150 ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$topicIDs[] = $row['tid'];
				$forumIDs[ $row['forum_id'] ] = $row['forum_id'];
			}
		}
		else if ( $postFind )
		{
			$this->DB->build( array( 'select' => 'pid, topic_id',
							  		 'from'   => 'posts',
									 'where'  => $postFind,
									 'order'  => 'post_date DESC',
									 'limit'  => array( 0, 150 ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$postIDs[] = $row['pid'];
				$_topicids[ $row['topic_id'] ] = $row['topic_id'];
			}
			
			if ( is_array( $_topicids ) AND count( $topicids ) )
			{
				$this->DB->build( array( 'select' => 'tid, forum_id',
								  		 'from'   => 'topics',
										 'where'  => 'tid IN (' . implode( ',', $_topicids ) . ')',
										 'order'  => 'start_date DESC',
										 'limit'  => array( 0, 150 ) ) );
				$this->DB->execute();

				while( $row = $this->DB->fetch() )
				{
					$forumIDs[ $row['forum_id'] ] = $row['forum_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Set up trash can
		//-----------------------------------------
		
		$this->_takeOutTrash();
		
		//-----------------------------------------
		// Delete topics...
		//-----------------------------------------
		
		if ( is_array( $topicIDs ) AND count( $topicIDs ) )
		{
			if ( $this->trash_forum )
			{
				//-----------------------------------------
				// Move, don't delete
				//-----------------------------------------

				$this->topicMove( $topicsIDs, 0, $this->trash_forum );
				
				$this->forumRecount( $this->trash_forum );
			}
			else
			{
				$this->topicDelete( $topicIDs, 1 );
			}
		}
		
		//-----------------------------------------
		// Delete posts...
		//-----------------------------------------
		
		if ( is_array( $postIDs ) AND count( $postIDs ) )
		{
			if ( $this->trash_forum )
			{
				//-----------------------------------------
				// Move, don't delete
				//-----------------------------------------

				//$this->topicMove( $topicsIDs, 0, $this->trash_forum );
				
				$this->forumRecount( $this->trash_forum );
			}
			else
			{
				$this->postDelete( $topicIDs, 1 );
			}
		}
		
		if ( count( $forumIDs ) )
		{
			foreach( $forumIDs as $id )
			{
				$this->registry->class_forums->allForums[ $id ]['_update_deletion'] = 1;
				$this->forumRecount( $id );
			}
		}
		
		$this->statsRecount();
		
		return TRUE;
	}
	
	/**
	 * Approve / unapprove posts
	 *
	 * @access	public
	 * @param	array 		Array of Post IDs
	 * @param	boolean		Approve (TRUE) / Unapprove (FALSE)
	 * @param	int			Fix so posts can only come from a specific topic ID
	 * @return	boolean
	 */
	public function postToggleApprove( $postIDs, $approve=FALSE, $topicIDFix=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_approveTopic	= 1;
		$_queuedPost	= 0;
		
		if ( $approve === FALSE )
		{
			$_approveTopic = 0;
			$_queuedPost   = 1;
		}
		
		$_topics	= array();
		$_forumIDs	= array();
		$_pids		= IPSLib::cleanIntArray( $postIDs );
		$_tids		= array();
		
		//-----------------------------------------
		// Fetch distinct topic IDs
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'p.topic_id' ),
								 'from'   => array( 'posts' => 'p' ),
								 'where'  => 'p.pid IN (' . implode( ',', $postIDs ) . ')',
								 'add_join' => array( array( 'select' => 't.*',
															 'from'   => array( 'topics' => 't' ),
															 'where'  => 'p.topic_id=t.tid',
															 'type'   => 'inner' ) ) ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$_topics[ $row['topic_id'] ] = $row;
		}

		//-----------------------------------------
		// Did we get the first post too?
		//-----------------------------------------
		
		foreach( $_topics as $tid => $topicData )
		{
			/* Fix to a topic? */
			if ( $topicIDFix AND ( $topicIDFix != $tid ) )
			{
				continue;
			}
			
			$_forumIDs[] = $topicData['forum_id'];
			
			if ( in_array( $topicData['topic_firstpost'], $_pids ) )
			{
				$this->DB->update( 'topics', array( 'approved' => $_approveTopic ), 'tid=' . $tid );
			
				/* Unapprove the topic, but not the first post? */
				//if ( $_queuedPost )
				//{
					$tmp    = $_pids;
					$_pids	= array();
				
					foreach( $tmp as $t )
					{
						if ( $t != $topicData['topic_firstpost'] )
						{
							$_pids[] = $t;
						}
						else
						{
							$_tids[ $topicData['tid'] ]	= $topicData['tid'];
						}
					}
				//}
			}
		}
	
		if ( count( $_pids ) )
		{
			$this->DB->update( 'posts', array( 'queued' => $_queuedPost ), 'pid IN (' . implode( ",", $_pids ) . ')' );
		}
		
		if ( $approve )
		{
			foreach( $_topics as $tid => $topicData )
			{
				$this->addModerateLog( $topicData['forum_id'], $tid, 0, sprintf( $this->lang->words['acp_approved_posts'], count( $_pids ), $topicData['title'] ) );
			}
			
			if( count($_pids) )
			{
				$this->clearModQueueTable( 'post', $_pids, true );
			}
			
			if( count($_tids) )
			{
				$this->clearModQueueTable( 'topic', $_tids, true );
			}
		}
		else
		{
			foreach( $_topics as $tid => $topicData )
			{
				$this->addModerateLog( $topicData['forum_id'], $tid, 0, sprintf( $this->lang->words['acp_unapproved_posts'], count( $_pids ), $topicData['title'] ) );
			}
		}
		
		foreach( $_topics as $tid => $topicData )
		{
			$this->rebuildTopic( $tid );
		}
		
		if ( count( $_forumIDs ) )
		{
			foreach( $_forumIDs as $_fid )
			{
				$this->forumRecount( $_fid );
			}
		}
		
		$this->statsRecount();
		
		return TRUE;
	}
	
	/**
	 * Clear out the mod-queue table appropriately
	 *
	 * @access	public
	 * @param	string		[topic|post] Type of item moved
	 * @param	mixed		ID of topic or post, or array of ids
	 * @param	boolean		Was content approved?
	 * @return	void
	 */
	public function clearModQueueTable( $type, $typeId, $approved=false )
	{
		//-----------------------------------------
		// Get post class..
		//-----------------------------------------

		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php' );
		
		$_postClass = new classPostForms( $this->registry );
		
		//-----------------------------------------
		// Are we operating on one id, or an array
		//-----------------------------------------
		
		if( is_array($typeId) )
		{
			$where	= "type_id IN(" . implode( ',', IPSLib::cleanIntArray($typeId) ) . ")";
		}
		else
		{
			$where	= "type_id=" . intval($typeId);
		}

		//-----------------------------------------
		// Was content deleted or moved to trash forum
		//-----------------------------------------
		
		if( ! $approved )
		{
			$this->DB->delete( 'mod_queued_items', "type='{$type}' AND {$where}" );
		}

		//-----------------------------------------
		// No, then we are approving content
		//-----------------------------------------
		
		else
		{
			//-----------------------------------------
			// Working with posts?
			//-----------------------------------------
			
			if( $type == 'post' )
			{
				IPSDebug::fireBug( 'info', array( 'type is post' ) );
				
				$this->DB->build( array(
										'select'	=> 'm.id',
										'from'		=> array( 'mod_queued_items' => 'm' ),
										'where'		=> "m.type='{$type}' AND m.{$where}",
										'add_join'	=> array(
															array(
																'select'	=> 'p.pid, p.post, p.author_id, p.post_date',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=m.type_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 't.title, t.forum_id',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
																),
															)
								)		);
				$outer = $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					$member	= IPSMember::load( $r['author_id'], 'extendedProfile,groups' );
					$_postClass->setPublished( true );
					$_postClass->setAuthor( $member );
					$_postClass->setForumData( $this->registry->class_forums->allForums[ $r['forum_id'] ] );
					
					$_postClass->incrementUsersPostCount();
					$_postClass->sendOutTrackedTopicEmails( $r['topic_id'], $r['post'], $member['members_display_name'], time() - $this->settings['session_expiration'], $member['member_id'] );
										
					$this->DB->delete( 'mod_queued_items', 'id=' . $r['id'] );
				}
			}
			else
			{
				IPSDebug::fireBug( 'info', array( 'type is topic' ) );
				
				$this->DB->build( array(
										'select'	=> 'm.id',
										'from'		=> array( 'mod_queued_items' => 'm' ),
										'where'		=> "m.type='{$type}' AND m.{$where}",
										'add_join'	=> array(
															array(
																'select'	=> 't.tid, t.title, t.starter_id, t.forum_id',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=m.type_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'p.pid, p.post, p.post_date',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=t.topic_firstpost',
																'type'		=> 'left',
																),
															)
								)		);
				$outer = $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					$member	= IPSMember::load( $r['starter_id'], 'extendedProfile,groups' );
					$_postClass->setPublished( true );
					$_postClass->setAuthor( $member );
					$_postClass->setForumData( $this->registry->class_forums->allForums[ $r['forum_id'] ] );
					
					$_postClass->incrementUsersPostCount();
					$_postClass->sendOutTrackedForumEmails( $r['forum_id'], $r['tid'], $r['title'], $this->registry->class_forums->allForums[ $r['forum_id'] ]['name'], $r['post'], $member['member_id'], $member['members_display_name'] );
										
					$this->DB->delete( 'mod_queued_items', 'id=' . $r['id'] );
				}
			}
		}
		
		$this->addModerateLog( $this->request['f'], $this->request['t'], $this->request['p'], $this->topic['title'], sprintf( $this->lang->words['modqueue_table_clear'], $type, is_array($typeId) ? implode( ', ', $typeId ) : $typeId ) );
	}

	/**
	 * Delete a post
	 *
	 * @access	public
	 * @param	mixed 		Post id | Array of post ids
	 * @return	boolean		Post deleted
	 */
	public function postDelete($id)
	{
		$posts			= array();
		$attach_tid		= array();
		$attach_ids		= array();
		$topics			= array();
		
		$this->error	= "";

		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$pid = " IN(" . implode( ",", $id ) . ")";
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$pid   = "={$id}";
			}
			else
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Get Stuff
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'pid, topic_id, new_topic', 'from' => 'posts', 'where' => 'pid' . $pid ) );
		$q = $this->DB->execute();
		
		while ( $r = $this->DB->fetch( $q ) )
		{
			$posts[ $r['pid'] ]			= $r['topic_id'];
			$topics[ $r['topic_id'] ]	= 1;
			
			/* Delete from the index */
			$this->registry->class_forums->removePostFromSearchIndex( $r['topic_id'], $r['pid'], $r['new_topic'] );
			
			/* Delete from rep cache */
			$this->DB->delete( 'reputation_cache', "app='forums' AND type='pid' AND type_id={$r['pid']}" );
			$this->DB->delete( 'reputation_index', "app='forums' AND type='pid' AND type_id={$r['pid']}" );
		}
		
		//-----------------------------------------
		// Is there an attachment to this post?
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach = new class_attach( $this->registry );
		$class_attach->type = 'post';
		$class_attach->init();
		
		$class_attach->bulkRemoveAttachment( array_keys( $posts ) );
		
		//-----------------------------------------
		// delete the post
		//-----------------------------------------
		
		$this->DB->delete( 'posts', "pid" . $pid );
		
		/* Remove cache content */
		IPSContentCache::drop( 'post', array_keys( $posts ) );
		
		//-----------------------------------------
		// Update the stats
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'stats', 'global' );

		//-----------------------------------------
		// Update all relevant topics
		//-----------------------------------------
		
		foreach( array_keys($topics) as $tid )
		{
			$this->rebuildTopic( $tid );
		}
		
		//-----------------------------------------
		// Log and return
		//-----------------------------------------
		
		$pid = str_replace( array( 'IN', '(', ')', '=' ), '', $pid );
		
		$this->addModerateLog( "", "", "", $pid, sprintf( $this->lang->words['multi_post_delete'], $pids ) );
		return true;
	}
	
	/**
	 * Rebuild a topic
	 *
	 * @access	public
	 * @param	integer 	Topic id
	 * @param 	boolean		Rebuild forum afterwards
	 * @return	boolean		Rebuild complete
	 */
	public function rebuildTopic( $tid, $doforum=1, $search_index=1 )
	{
		/* Topic ID */
		$tid = intval( $tid );

		if( $this->settings['post_order_column'] != 'post_date' )
		{
			$this->settings[ 'post_order_column'] =  'pid' ;
		}
		
		if( $this->settings['post_order_sort'] != 'desc' )
		{
			$this->settings[ 'post_order_sort'] =  'asc' ;
		}
				
		//-----------------------------------------
		// Get the correct number of replies
		//-----------------------------------------
		
		$posts		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$tid} and queued != 1" ) );
		$pcount		= intval( $posts['posts'] - 1 );
		
		//-----------------------------------------
		// Get the correct number of queued replies
		//-----------------------------------------
		
		$qposts		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$tid} and queued=1" ) );
		$qpcount	= intval( $qposts['posts'] );
		
		//-----------------------------------------
		// Get last post info
		//-----------------------------------------
		
		$last_post	= $this->DB->buildAndFetch( array(
													'select'	=> 'p.post_date, p.topic_id, p.author_id, p.author_name, p.pid',
													'from'		=> array( 'posts' => 'p' ),
													'where'		=> 'p.topic_id=' . $tid . ' AND p.queued=0',
													'order'		=>  $this->settings['post_order_column'] . ' DESC',
													'limit'		=> array( 1 ),
													'add_join'	=> array(
																		array( 'select'	=> 't.forum_id',
																				'from'	=> array( 'topics' => 't' ),
																				'where'	=> 't.tid=p.topic_id',
																				'type'	=> 'left'
																			),
																		array( 'select'	=> 'm.member_id, m.members_display_name',
																				'from'	=> array( 'members' => 'm' ),
																				'where'	=> 'm.member_id=p.author_id',
																				'type'	=> 'left'
																			),
																		)
											)		);

		$last_poster_name = $last_post['members_display_name'] ? $last_post['members_display_name'] : $last_post['author_name'];
		
		//-----------------------------------------
		// Get first post info
		//-----------------------------------------
		
		$first_post = $this->DB->buildAndFetch( array(
													'select'	=> 'p.post_date, p.author_id, p.author_name, p.pid',
													'from'		=> array( 'posts' => 'p' ),
													'where'		=> "p.topic_id={$tid}",
													'order'		=> "p." . $this->settings['post_order_column'] . " ASC",
													'limit'		=> array(0,1),
													'add_join'	=> array(
																		array(
																			'select'	=> 'm.member_id, m.members_display_name',
																			'from'		=> array( 'members' => 'm' ),
																			'where'		=> "p.author_id=m.member_id",
																			'type'		=> 'left'
																			)
																		)
											)		);

		$first_poster_name = $first_post['members_display_name'] ? $first_post['members_display_name'] : $first_post['author_name'];
		$_last_poster_name = $last_poster_name ? $last_poster_name : ( $pcount > 0 ? $this->lang->words['global_guestname'] : $first_poster_name );
		
		//-----------------------------------------
		// Get number of attachments
		//-----------------------------------------
		
		$attach = $this->DB->buildAndFetch( array( 
													'select'    => 'COUNT(*) as count',
													'from'	    => array( 'attachments' => 'a' ),
													'where'	    => "p.topic_id={$tid} AND a.attach_rel_module='post'",
													'add_join'	=> array(
																			array( 
																					'from'	=> array( 'posts' => 'p' ),
																					'where'	=> 'p.pid=a.attach_rel_id',
																					'type'	=> 'left'
																				) ) ) );

		//-----------------------------------------
		// Update topic
		//-----------------------------------------
		
		$this->DB->force_data_type = array( 'starter_name'		=> 'string',
											'last_poster_name'	=> 'string' );		

		$this->DB->update( 'topics', array( 
											'last_post'			=> intval($last_post['post_date'] ? $last_post['post_date'] : $first_post['post_date']),
											'last_poster_id'	=> intval($last_post['author_id'] ? $last_post['author_id'] : ( $pcount > 0 ? 0 : $first_post['author_id'] )),
											'last_poster_name'	=> $_last_poster_name,
											'topic_queuedposts'	=> intval($qpcount),
											'posts'				=> intval($pcount),
											'starter_id'		=> intval($first_post['author_id']),
											'starter_name'		=> $first_poster_name,
											'seo_first_name'    => IPSText::makeSeoTitle( $first_poster_name ),
											'seo_last_name'     => IPSText::makeSeoTitle( $_last_poster_name ),
											'start_date'		=> intval($first_post['post_date']),
											'topic_firstpost'	=> intval($first_post['pid']),
											'topic_hasattach'	=> intval($attach['count'])
							), 'tid=' . $tid );

		//-----------------------------------------
		// Update first post
		//-----------------------------------------

		if( ( ! isset( $first_post['new_topic'] ) OR $first_post['new_topic'] != 1 ) and $first_post['pid'] )
		{
			$this->DB->update( 'posts', array( 'new_topic' => 0 ), 'topic_id=' . $tid, true );
			$this->DB->update( 'posts', array( 'new_topic' => 1 ), 'pid=' . $first_post['pid'], true );
		}
		
		//-----------------------------------------
		// If we deleted the last post in a topic that was
		// the last post in a forum, best update that :D
		//-----------------------------------------
		
		if( ( $this->registry->class_forums->allForums[ $last_post['forum_id'] ]['last_id'] == $tid ) AND ( $doforum == 1 ) )
		{
			$tt = $this->DB->buildAndFetch( array(	
													'select'	=> 'title, tid, last_post, last_poster_id, last_poster_name',
													'from'		=> 'topics',
													'where'		=> 'forum_id=' . $last_post['forum_id'] . ' and approved=1',
													'order'		=> 'last_post desc',
													'limit'		=> array( 1 )
										)	);
			
			$dbs = array(
						 'last_title'		=> $tt['title']				? $tt['title']				: "",
						 'last_id'			=> $tt['tid']				? $tt['tid']				: 0,
						 'last_post'		=> $tt['last_post']			? $tt['last_post']			: 0,
						 'last_poster_name'	=> $tt['last_poster_name']	? $tt['last_poster_name']	: "",
						 'last_poster_id'	=> $tt['last_poster_id']	? $tt['last_poster_id']		: 0,
						 'seo_last_title'   => IPSText::makeSeoTitle( $tt['title'] ),
						 'seo_last_name'    => IPSText::makeSeoTitle( $tt['last_poster_name'] ),
						);
			
			if( $this->registry->class_forums->allForums[ $this->forum['id'] ]['newest_id'] == $tid )
			{
				$sort_key = $this->registry->class_forums->allForums[ $this->forum['id'] ]['sort_key'];
				
				$tt = $this->DB->buildAndFetch( array( 
														'select' => 'title, tid',
														'from'	 => 'topics',
														'where'	 => 'forum_id=' . $this->forum['id'] . ' and approved=1',
														'order'	 => 'start_date desc',
														'limit'	 => array( 1 )
											)	);
															
				$dbs['newest_id']		= $tt['tid']	? $tt['tid']	: "";
				$dbs['newest_title']	= $tt['title']	? $tt['title']	: "";
				$dbs['seo_last_title']  = IPSText::makeSeoTitle( $tt['title'] );
			}
			
			$this->DB->force_data_type = array( 'last_poster_name'	=> 'string',
												'seo_last_title'	=> 'string',
												'seo_last_name'     => 'string',
												'last_title'		=> 'string',
												'newest_title'		=> 'string' );
			
			$this->DB->update( 'forums', $dbs, "id=" . intval($this->forum['id']) );
		}
		
		/* Rebuild the search index for this topic */
		if( $search_index )
		{
			/* Remove the old entries */
			$this->registry->class_forums->removePostFromSearchIndex( $tid, 0, 1 );
			
			/* Rebuild the posts */
			$this->DB->build( array( 
									'select'   => 'p.pid, p.post, p.author_id, p.topic_id, p.post_date', 
									'from'     => array( 'posts' => 'p' ),
									'order'    => 'pid ASC',
									'where'    => 'p.queued=0 AND p.topic_id=' . $tid,
									'add_join' => array( 
														array(
																'select' => 't.title, t.forum_id, t.topic_firstpost',
																'from'   => array( 'topics' => 't' ),
																'where'  => 'p.topic_id=t.tid',
																'type'   => 'left'											
															)
													 )
						)	);
			$q = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $q ) )
			{
				/* The first post needs some different data */
				if( $r['topic_firstpost'] == $r['pid'] )
				{
					$pid   = 0;
					$title = $r['title'];
				}
				else
				{
					$pid   = serialize( array( 'pid' => $r['pid'], 'title' => $r['title'] ) );
					$title = '';
				}

			}
		}

		return true;
	}
		
	/**
	 * Add a reply to a topic
	 *
	 * @access	public
	 * @param	string 		Post contnet
	 * @param 	array		Array of topic ids to apply this reply to
	 * @param	boolean		Increment post count?
	 * @return	boolean		Reply added
	 * @todo 	[Future] Would better using the new posting libs so that topic subs, etc are triggered maybe.
	 *			Though, do we really want topic subs triggered on a multi-mod?
	 */
	public function topicAddReply($post="", $tids=array(), $incpost=0)
	{
		if ( $post == "" )
		{
			return false;
		}
		
		if ( count( $tids ) < 1 )
		{
			return false;
		}
		
		$post = array(
						'author_id'			=> $this->memberData['member_id'],
						'use_sig'			=> 1,
						'use_emo'			=> 1,
						'ip_address'		=> $this->member->ip_address,
						'post_date'			=> time(),
						'icon_id'			=> 0,
						'post'				=> $post,
						'author_name'		=> $this->memberData['members_display_name'],
						'topic_id'			=> "",
						'queued'			=> 0,
						'post_htmlstate'	=> 2,
					 );

		//-----------------------------------------
		// Add posts...
		//-----------------------------------------
		 
		$seen_fids		= array();
		$add_posts		= 0;
		
		foreach( $tids as $row )
		{
			$tid	= intval($row[0]);
			$fid	= intval($row[1]);
			$pa		= array();
			$ta		= array();
			
			if ( ! in_array( $fid, $seen_fids ) )
			{
				$seen_fids[] = $fid;
			}
			
			if ( $tid and $fid )
			{
				$pa				= $post;
				$pa['topic_id']	= $tid;
				
				$this->DB->insert( 'posts', $pa );
				
				$_pid = $this->DB->getInsertId();
				
				/* Add to cache */
				IPSContentCache::update( $_pid, 'post', $post['post'], FALSE );

				$ta = array (
							  'last_poster_id'		=> $this->memberData['member_id'],
							  'last_poster_name'	=> $this->memberData['members_display_name'],
							  'seo_last_name'       => IPSText::makeSeoTitle( $this->memberData['members_display_name'] ),
							  'last_post'			=> $pa['post_date'],
							);
				
				$this->DB->force_data_type = array( 'last_poster_name' => 'string', 'seo_last_name' => 'string' );		

				$this->DB->buildAndFetch( array( 'update' => 'topics', 'set' => $this->DB->compileUpdateString( $ta ) . ", posts=posts+1", 'where' => 'tid='.$tid ) );

				$add_posts++;
				
				//-----------------------------------------
				// Mark as read for current viewer
				//-----------------------------------------
				
				$this->registry->classItemMarking->markRead( array( 'forumID' => $fid, 'itemID' => $tid ) );
			}
		}
				
		if ( $this->auto_update != false )
		{
			if ( count($seen_fids) > 0 )
			{
				foreach( $seen_fids as $id )
				{
					$this->forumRecount( $id );
				}
			}
		}
		
		if ( $add_posts > 0 )
		{
			$this->cache->rebuildCache( 'stats', 'global' );

			//-----------------------------------------
			// Update current members stuff
			//-----------------------------------------
		
			$pcount				= "";
			$member_group_id	= "";
			
			
			if ( ($this->forum['inc_postcount']) and ($incpost != 0) )
			{
				//-----------------------------------------
				// Increment the users post count
				//-----------------------------------------
				
				$pcount = "posts=posts+" . $add_posts . ", ";
				
				//-----------------------------------------
				// Are we checking for auto promotion?
				//-----------------------------------------
				
				if ($this->memberData['g_promotion'] != '-1&-1')
				{
					list($gid, $gposts)	= explode( '&', $this->memberData['g_promotion'] );
					
					if ( $gid > 0 and $gposts > 0 )
					{
						if ( $this->memberData['posts'] + $add_posts >= $gposts )
						{
							$member_group_id = "member_group_id='{$gid}', ";
						}
					}
				}
			}

			$this->DB->buildAndFetch( array( 'update' => 'members', 'set' => $pcount . $member_group_id . "last_post=" . time(), 'where' => "member_id=" . $this->memberData['member_id'] ) );
		}		
		
		return true;
	}
	
	/**
	 * Close a topic
	 *
	 * @access	public
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicClose( $id )
	{
		$this->stmInit();
		$this->stmAddClose();
		$this->stmExec($id);
		return TRUE;
	}
	
	
	/**
	 * Open a topic
	 *
	 * @access	public
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicOpen($id)
	{
		$this->stmInit();
		$this->stmAddOpen();
		$this->stmExec($id);
		return TRUE;
	}
	
	/**
	 * Pin a topic
	 *
	 * @access	public
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicPin($id)
	{
		$this->stmInit();
		$this->stmAddPin();
		$this->stmExec($id);
		return TRUE;
	}
	
	/**
	 * Unpin a topic
	 *
	 * @access	public
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicUnpin($id)
	{
		$this->stmInit();
		$this->stmAddUnpin();
		$this->stmExec($id);
		return TRUE;
	}
	
	/**
	 * Delete a topic
	 *
	 * @access	public
	 * @param	mixed 		Topic id | Array of topic ids
	 * @param	boolean		Skip updating the stats
	 * @return	boolean
	 */
	public function topicDelete( $id, $nostats=0 )
	{
		$posts			= array();
		$attach			= array();
		$this->error	= "";

		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$tid = " IN(" . implode( ",", $id ) . ")";
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$tid   = "={$id}";
			}
			else
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Remove polls assigned to this topic
		//-----------------------------------------
		
		$this->DB->delete( 'polls', "tid" . $tid );
		$this->DB->delete( 'voters', "tid" . $tid );
		$this->DB->delete( 'tracker', "topic_id" . $tid );
		$this->DB->delete( 'topic_ratings', "rating_tid" . $tid );	
		$this->DB->delete( 'topic_views', "views_tid" . $tid );
		$this->DB->delete( 'topics', "tid" . $tid );
		
		//-----------------------------------------
		// Get PIDS for attachment deletion
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'pid', 'from' => 'posts', 'where' => "topic_id" . $tid ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$posts[] = $r['pid'];
		}
		
		/* Remove cache content */
		IPSContentCache::drop( 'post', $posts );
		
		//-----------------------------------------
		// Remove the attachments
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach = new class_attach( $this->registry );
		$class_attach->type = 'post';
		$class_attach->init();
		
		$class_attach->bulkRemoveAttachment( $posts );		
		
		//-----------------------------------------
		// Remove the posts
		//-----------------------------------------
		
		$this->DB->delete( 'posts', "topic_id" . $tid );
		$this->DB->delete( 'search_index', "type='forum' AND type_2='topic' AND type_id_2 " . $tid );
		$this->DB->delete( 'reputation_cache', "app='forums' AND type='pid' AND type_id " . $tid );
		$this->DB->delete( 'reputation_index', "app='forums' AND type='pid' AND type_id " . $tid );
		
		//-----------------------------------------
		// Recount forum...
		//-----------------------------------------
		
		if ( !$nostats )
		{
			if ( $this->forum['id'] )
			{
				$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
				$this->forumRecount( $this->forum['id'] );
			}
			
			$this->statsRecount();
		}

		return TRUE;
	}

	/**
	 * Move a topic
	 *
	 * @access	public
	 * @param	mixed 		Topic id | Array of topic ids
	 * @param	integer		Source forum
	 * @param	integer		Move to forum
	 * @param	boolean		Leave the 'link'
	 * @return	boolean
	 */
	public function topicMove($topics, $source=0, $moveto=0, $leavelink=0)
	{
		$this->error	= "";
		$source			= intval($source);
		$moveto			= intval($moveto);
		$forumIDSQL     = ( $source ) ? " forum_id={$source} AND " : '';
		
		if ( is_array( $topics ) )
		{
			$topics = IPSLib::cleanIntArray( $topics );
			
			if ( count($topics) > 0 )
			{
				$tid = " IN(" . implode( ",", $topics ) . ")";
			}
			else
			{
				return false;
			}
			
			//-----------------------------------------
			// Mark as read in new forum
			//-----------------------------------------
			
			foreach( $topics as $topicId )
			{
				$this->registry->classItemMarking->markRead( array( 'forumID' => $moveto, 'itemID' => $topicId ), 'forums' );
			}
		}
		else
		{
			if ( intval($topics) )
			{
				$tid   = "={$topics}";
			}
			else
			{
				return false;
			}
			
			//-----------------------------------------
			// Mark as read in new forum
			//-----------------------------------------
			
			$this->registry->classItemMarking->markRead( array( 'forumID' => $moveto, 'itemID' => $topics ), 'forums' );
		}
		
		//-----------------------------------------
		// Update the topic
		//-----------------------------------------
		
		$this->DB->update( 'topics', array( 'forum_id' => $moveto ), $forumIDSQL . "tid" . $tid );
		
		/* Update the search index */
		//$this->DB->update( 'search_index', array( 'type_id' => $moveto ), "type='forum' AND type_2='topic' AND type_id_2 {$tid}" );
		
		//-----------------------------------------
		// Update the polls
		//-----------------------------------------
		
		$this->DB->update( 'polls', array( 'forum_id' => $moveto ), $forumIDSQL . "tid" . $tid );
		
		//-----------------------------------------
		// Are we leaving a stink er link?
		//-----------------------------------------
		
		if ( $leavelink AND $source )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'topics', 'where' => "tid" . $tid ) );
			$oq = $this->DB->execute();

			while ( $row = $this->DB->fetch($oq) )
			{
				$this->DB->force_data_type = array( 'title'				=> 'string',
													'starter_name'		=> 'string',
													'last_poster_name'	=> 'string',
													'seo_last_name'	    => 'string'  );

				$this->DB->insert( 'topics', array (
														'title'				=> $row['title'],
														'description'		=> $row['description'],
														'state'				=> 'link',
														'posts'				=> 0,
														'views'				=> 0,
														'starter_id'		=> $row['starter_id'],
														'start_date'		=> $row['start_date'],
														'starter_name'		=> $row['starter_name'],
														'seo_first_name'    => IPSText::makeSeoTitle( $row['starter_name'] ),
														'last_post'			=> $row['last_post'],
														'forum_id'			=> $source,
														'approved'			=> 1,
														'pinned'			=> 0,
														'moved_to'			=> $row['tid'] . '&' . $moveto,
														'last_poster_id'	=> $row['last_poster_id'],
														'last_poster_name'	=> $row['last_poster_name'],
														'seo_last_name'     => IPSText::makeSeoTitle( $row['starter_name'] ),
									)				);
			}
		
		}
		
		//-----------------------------------------
		// Sort out subscriptions
		//-----------------------------------------
		
		$trid_to_delete = array();
		
		$this->DB->build( array( 'select'		=> 'tr.*',
										'from'		=> array( 'tracker' => 'tr' ),
										'where'		=> 'tr.topic_id' . $tid,
										'add_join'	=> array(
															array( 'select'	=> 't.tid, t.forum_id',
																	'from'	=> array( 'topics' => 't' ),
																	'where'	=> 't.tid=tr.topic_id',
																	'type'	=> 'left',
																),
															array( 'select'	=> 'm.member_id, m.member_group_id, m.org_perm_id',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=tr.member_id',
																	'type'	=> 'left',
																),
															array( 'select'	=> 'g.g_id, g.g_perm_id',
																	'from'	=> array( 'groups' => 'g' ),
																	'where'	=> 'g.g_id=m.member_group_id',
																	'type'	=> 'left',
																),
															)
								)		);
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Match the perm group against forum_mask
			//-----------------------------------------
			
			$perm_id			= $r['org_perm_id'] ? $r['org_perm_id'] : $r['g_perm_id'];
			$pass				= 0;
			$forum_perm_array 	= explode( ",", $this->registry->class_forums->allForums[ $r['forum_id'] ]['perm_read'] );
			
			foreach( explode( ',', $perm_id ) as $u_id )
			{
				if ( $u_id AND in_array( $u_id, $forum_perm_array ) )
				{
					$pass = 1;
				}
			}
			
			if ( $pass != 1 )
			{
				$trid_to_delete[] = $r['trid'];
			}		
		}
		
		if ( count($trid_to_delete) > 0 )
		{
			$this->DB->delete( 'tracker', "trid IN(" . implode( ',', $trid_to_delete ) . ")" );
		}
		
		return true;
	}
	
	/**
	 * Recount total topics and replies for site statistics
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function statsRecount()
	{
		$this->cache->rebuildCache( 'stats', 'global' );
		
		return true;
	}
	
	
	/**
	 * Recount a forum
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function forumRecount( $fid="" )
	{
		$fid = intval($fid);
		
		if ( ! $fid )
		{
			if ( $this->forum['id'] )
			{
				$fid = $this->forum['id'];
			}
			else
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Get the topics..
		//-----------------------------------------
		
		$topics			= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														   'from'	=> 'topics',
														   'where'	=> "approved=1 and forum_id={$fid}" ) );
		
		//-----------------------------------------
		// Get the QUEUED topics..
		//-----------------------------------------
		
		$queued_topics	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
																	'from'	=> 'topics',
																	'where'	=> "approved=0 and forum_id={$fid}" ) );
		
		//-----------------------------------------
		// Get the posts..
		//-----------------------------------------
		
		$posts			= $this->DB->buildAndFetch( array( 'select'	=> 'SUM(posts) as replies',
																	'from'	=> 'topics',
																	'where'	=> "approved=1 and forum_id={$fid}" ) );
		
		//-----------------------------------------
		// Get the QUEUED posts..
		//-----------------------------------------
		
		$queued_posts	= $this->DB->buildAndFetch( array( 'select'	=> 'SUM(topic_queuedposts) as replies',
																	'from'	=> 'topics',
																	'where'	=> "forum_id={$fid}" ) );
		
		//-----------------------------------------
		// Get the forum last poster..
		//-----------------------------------------
		
		$last_post		= $this->DB->buildAndFetch( array( 'select'	=> 'tid, title, last_poster_id, last_poster_name, seo_last_name, last_post',
																	'from'	=> 'topics',
																	'where'	=> "approved=1 and forum_id={$fid}",
																	'order'	=> 'last_post DESC',
																	'limit'	=> array( 1 ) 
														)		);
		
		$newest_topic	= $this->DB->buildAndFetch( array( 'select'	=> 'title, tid, seo_first_name',
																	'from'	=> 'topics',
																	'where'	=> 'forum_id=' . $fid . ' and approved=1',
																	'order'	=> 'start_date desc',
																	'limit'	=> array( 1 )
															)	  );
		
		$lastXTopics    = $this->registry->class_forums->lastXFreeze( $this->registry->class_forums->buildLastXTopicIds( $fid, FALSE ) );
		
		//-----------------------------------------
		// Reset this forums stats
		//-----------------------------------------
		
		$dbs = array(
					  'name_seo'			=> IPSText::makeSeoTitle( $this->registry->class_forums->allForums[ $fid ]['name'] ),
					  'last_poster_id'		=> intval($last_post['last_poster_id']),
					  'last_poster_name'	=> $last_post['last_poster_name'],
					  'seo_last_name'       => IPSText::makeSeoTitle( $last_post['last_poster_name'] ),
					  'last_post'			=> intval($last_post['last_post']),
					  'last_title'			=> $last_post['title'],
					  'seo_last_title'      => IPSText::makeSeoTitle( $last_post['title'] ),
					  'last_id'				=> intval($last_post['tid']),
					  'topics'				=> intval($topics['count']),
					  'posts'				=> intval($posts['replies']),
					  'queued_topics'		=> intval($queued_topics['count']),
					  'queued_posts'		=> intval($queued_posts['replies']),
					  'newest_id'			=> intval($newest_topic['tid']),
					  'newest_title'		=> $newest_topic['title'],
					  'last_x_topic_ids'    => $lastXTopics
					);
					
		if ( $this->registry->class_forums->allForums[ $fid ]['_update_deletion'] )
		{
			$dbs['forum_last_deletion'] = time();
		}
		
		$this->DB->force_data_type = array( 'last_poster_name'	=> 'string',
											'last_title'		=> 'string',
											'newest_title'		=> 'string',
											'seo_last_title'    => 'string',
											'seo_last_name'     => 'string'  );		
												 
		$this->DB->update( 'forums', $dbs, "id=" . $fid );

		return true;
	}
	
	
	/**
	 * Multi-statement init
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmInit()
	{
		$this->stm	= array();
		
		return true;
	}
	
	/**
	 * Multi-statement execute
	 *
	 * @access	public
	 * @param 	mixed		Id | Array of ids
	 * @return	boolean
	 */
	public function stmExec($id)
	{
		if ( count($this->stm) < 1 )
		{
			return false;
		}
		
		$final_array = array();
		
		foreach( $this->stm as $real_array )
		{
			foreach( $real_array as $k => $v )
			{
				$final_array[ $k ] = $v;
			}
		}

		if ( is_array($id) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$this->DB->update( 'topics', $final_array, "tid IN(" . implode( ",", $id ) . ")" );
			}
			else
			{
				return false;
			}
		}
		else if ( intval($id) != "" )
		{
			$this->DB->update( 'topics', $final_array, "tid=" . intval($id) );
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Multi-statement pin topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddPin()
	{
		$this->stm[] = array( 'pinned' => 1 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement unpin topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddUnpin()
	{
		$this->stm[] = array( 'pinned' => 0 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement close topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddClose()
	{
		$this->stm[] = array( 'state' => 'closed' );
		$this->stm[] = array( 'topic_open_time' => 0 );
		$this->stm[] = array( 'topic_close_time' => 0 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement open topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddOpen()
	{
		$this->stm[] = array( 'state' => 'open' );
		$this->stm[] = array( 'topic_open_time' => 0 );
		$this->stm[] = array( 'topic_close_time' => 0 );
				
		return TRUE;
	}
	
	/**
	 * Multi-statement update topic title
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddTitle($new_title='')
	{
		if ( $new_title == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'title' => $new_title );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement update topic description topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddDesc($new_desc='')
	{
		if ( $new_desc == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'description' => $new_desc );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement approve topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddApprove()
	{
		$this->stm[] = array( 'approved' => 1 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement unapprove topic
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function stmAddUnapprove()
	{
		$this->stm[] = array( 'approved' => 0 );
		
		return TRUE;
	}
	
	/**
	 * Create 'where' clause for SQL forum pruning
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function sqlPruneCreate( $forum_id, $starter_id="", $topic_state="", $post_min="", $date_exp="", $ignore_pin="" )
	{
		$sql = 'forum_id=' . intval($forum_id);

		if ( intval($date_exp) )
		{
			$sql .= " AND last_post < {$date_exp}";
		}
		
		if ( intval($starter_id) )
		{
			$sql .= " AND starter_id={$starter_id}";
			
		}
		
		if ( intval($post_min) )
		{
			$sql .= " AND posts < {$post_min}";
		}
		
		if ($topic_state != 'all')
		{
			if ($topic_state)
			{
				$sql .= " AND state='{$topic_state}'";
			}
		}
		
		if ( $ignore_pin != "" )
		{
			$sql .= " AND pinned=0";
		}

		return $sql;
	}
	
	/**
	 * Determines if current member is authorized to use multi-mod
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function mmAuthorize()
	{
		$pass_go = FALSE;
		
		if ( $this->memberData['member_id'] )
		{
			if ( $this->memberData['g_is_supmod'] )
			{ 
				$pass_go = TRUE;
			}
			else if ( $this->moderator['can_mm'] == 1 )
			{
				$pass_go = TRUE;
			}
		}
		
		return $pass_go;
	}
	
	/**
	 * Checks if multi-mod is allowed in a specified forum
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function mmCheckIdInForum( $fid, $mm_data)
	{
		$retval = FALSE;
		
		if (  $mm_data['mm_forums'] == '*' OR strstr( "," . $mm_data['mm_forums'] . ",", "," . $fid . "," ) )
		{
			$retval = TRUE;
		}
		
		return $retval;	
	}
	
	/**
	 * Add an entry to the moderator log
	 *
	 * @access	public
	 * @param	integer		Forum id
	 * @param	integer		Topic id
	 * @param	string		Topic title
	 * @param	string		Title to add to moderator log
	 * @return	boolean
	 */
	public function addModerateLog($fid, $tid, $pid, $t_title, $mod_title='Unknown')
	{
		$this->DB->force_data_type = array( 'member_name' => 'string' );	
		
		$this->DB->insert( 'moderator_logs', array (
												  'forum_id'		=> intval($fid),
												  'topic_id'		=> intval($tid),
												  'post_id'			=> intval($pid),
												  'member_id'		=> $this->memberData['member_id'],
												  'member_name'		=> $this->memberData['members_display_name'],
												  'ip_address'		=> $this->member->ip_address,
												  'http_referer'	=> htmlspecialchars(my_getenv('HTTP_REFERER')),
												  'ctime'			=> time(),
												  'topic_title'		=> substr( $t_title, 0, 128 ),
												  'action'			=> substr( $mod_title, 0, 128 ),
												  'query_string'	=> substr( htmlspecialchars(my_getenv('QUERY_STRING')), 0, 128 ),
											  )  );
		return TRUE;
	}
	
	/**
	 * Setup the trash can forum
	 *
	 * @access	private
	 * @author	Brandon Farber
	 * @return	void
	 */
	private function _takeOutTrash()
	{
		if ( $this->settings['forum_trash_can_enable'] and $this->settings['forum_trash_can_id'] )
		{
			if ( $this->registry->class_forums->allForums[ $this->settings['forum_trash_can_id'] ]['sub_can_post'] )
			{
				if ( $this->memberData['g_access_cp'] )
				{
					$this->trash_forum = $this->settings['forum_trash_can_use_admin'] ? $this->settings['forum_trash_can_id'] : 0;
				}
				else if ( $this->memberData['g_is_supmod'] )
				{
					$this->trash_forum = $this->settings['forum_trash_can_use_smod'] ? $this->settings['forum_trash_can_id'] : 0;
				}
				else if ( $this->memberData['is_mod'] )
				{
					$this->trash_forum = $this->settings['forum_trash_can_use_mod'] ? $this->settings['forum_trash_can_id'] : 0;
				}
				else
				{
					$this->trash_forum = $this->settings['forum_trash_can_id'];
				}
			}
		}
	}

}