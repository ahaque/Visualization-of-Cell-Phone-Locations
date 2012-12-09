<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Topic View
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage  Forums
 * @version		$Rev: 5027 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_topics extends ipsCommand
{
	/**
	 * First post content
	 *
	 * @access	private
	 * @var		string
	 */
	private $_firstPostContent = '';
	
	/**
	 * Moderators in this forum
	 *
	 * @access	public
	 * @var		array
	 */
	public $moderator	= array();
	
	/**
	 * Forum data
	 *
	 * @access	public
	 * @var		array
	 */
	public $forum		= array();
	
	/**
	 * Topic data
	 *
	 * @access	public
	 * @var		array
	 */
	public $topic		= array();

	/**
	 * Number of posts so far (offset)
	 *
	 * @access	public
	 * @var		integer
	 */
	public $post_count	 = 0;
	
	/**
	 * Parsed member data
	 *
	 * @access	public
	 * @var		array
	 */
	public $cached_members	= array();
	
	/**
	 * Is first post printed?
	 *
	 * @access	public
	 * @var		bool
	 */
	public $first_printed	= false;
	
	/**
	 * Printed any posts?
	 *
	 * @access	public
	 * @var		bool
	 */
	public $printed		= false;
	
	/**
	 * First post id
	 *
	 * @access	public
	 * @var		integer
	 */
	public $first		= 0;
	
	/**
	 * Quoted post ids
	 *
	 * @access	public
	 * @var		string
	 */
	public $qpids		= "";
	
	/**
	 * Post ids
	 *
	 * @access	public
	 * @var		array
	 */
	public $pids		= array();

	/**
	 * Attachment post ids
	 *
	 * @access	public
	 * @var		array
	 */
	public $attach_pids	= array();
	
	/**
	 * Last read topic id
	 *
	 * @access	public
	 * @var		integer
	 */
	public $last_read_tid	= 0;

	/**
	 * Can rate a topic permission
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $can_rate	= false;
	
	/**
	 * Is this a poll only (disallow replies)?
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $poll_only	= false;
	
	/**
	 * Attachments library
	 *
	 * @access	public
	 * @var		object
	 */
	public $class_attach;
	
	/**
	 * Test to see if user is restricted by Posts per day
	 *
	 * @access private
	 * @var	   int
	 */
	private $_ppd_ok = TRUE;
	
	/**
	 * Topic view mode
	 *
	 * @access private
	 * @var	   string
	 */
	private $topic_view_mode = '';
	
	/**
	 * Cache Monitor
	 *
	 * @access	private
	 * @var		array
	 */
	private $_cacheMonitor = array( 'post' => array( 'cached' => 0, 'raw' => 0 ),
									'sig'  => array( 'cached' => 0, 'raw' => 0 ) );

	/**
	 * Mod actions
	 *
	 * @access	private
	 * @var		array
	 */
	private $mod_action = array(	'CLOSE_TOPIC'   => '00',
									'OPEN_TOPIC'	=> '01',
									'MOVE_TOPIC'	=> '02',
									'DELETE_TOPIC'  => '03',
									'EDIT_TOPIC'	=> '05',
									'PIN_TOPIC'	    => '15',
									'UNPIN_TOPIC'   => '16',
									'UNSUBBIT'	    => '30',
									'MERGE_TOPIC'   => '60',
									'TOPIC_HISTORY' => '90',
								);
							 							 
							 	
	/**
	 * Loads the topic and forum data for the specified topic id
	 *
	 * @access	public
	 * @param	mixed	$topic	Array of topic data, or single topic id
	 * @return	void
	 **/
	public function loadTopicAndForum( $topic="" )
	{
		if ( ! is_array( $topic ) )
		{
			//-----------------------------------------
			// Check the input
			//-----------------------------------------
			
			$this->request['t'] = intval( $this->request['t'] );
			
			if ( $this->request['t'] < 0  )
			{
				$this->registry->output->showError( 'topics_no_tid', 10341 );
			}
			
			//-----------------------------------------
			// Get the forum info based on the forum ID,
			// get the category name, ID, and get the topic details
			//-----------------------------------------
			
			if ( ! isset( $this->registry->class_forums->topic_cache['tid'] ) OR ! $this->registry->class_forums->topic_cache['tid'] )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$this->request['t'] ) );									
				$this->DB->execute();
				
				$this->topic = $this->DB->fetch();
			}
			else
			{
				$this->topic = $this->registry->class_forums->topic_cache;
			}
		}
		else
		{
			$this->topic = $topic;
		}
		
		$this->topic['forum_id'] = isset( $this->topic['forum_id'] ) ? $this->topic['forum_id'] : 0;
		
		$this->forum = $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ];
		
		$this->request['f'] = $this->forum['id'];
		
		//-----------------------------------------
		// Error out if we can not find the topic
		//-----------------------------------------
		
		if ( ! $this->topic['tid'] )
		{
			$this->registry->output->showError( 'topics_no_tid', 10343 );
		}
		
		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------
		
		if ( ! $this->forum['id'] )
		{
			$this->registry->output->showError( 'topics_no_fid', 10342 );
		}
		
		//-----------------------------------------
		// Error out if the topic is not approved
		//-----------------------------------------
		
		if ( ! $this->registry->class_forums->canQueuePosts($this->forum['id']) )
		{
			if ($this->topic['approved'] != 1)
			{
				$this->registry->output->showError( 'topic_not_approved', 10344 );
			}
		}
		
		$this->registry->class_forums->forumsCheckAccess( $this->forum['id'], 1, 'topic', $this->topic );
	}
	
	/**
	 * Main Execution Function
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$post_data = array();
		$poll_data = '';
		$function  = '';
		
		//-----------------------------------------
		// INIT module
		//-----------------------------------------
		
		$this->loadTopicAndForum();

		//-----------------------------------------
		// Topic rating: Rating
		//-----------------------------------------
		
		$this->can_rate = $this->memberData['member_id'] ? intval( $this->memberData['g_topic_rate_setting'] ) : 0;

		//-----------------------------------------
		// Reputation Cache
		//-----------------------------------------
		
		if( $this->settings['reputation_enabled'] )
		{
			/* Load the class */
			require_once( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php' );
			$this->registry->setClass( 'repCache', new classReputationCache() );
		
			/* Update the filter? */
			if( isset( $this->request['rep_filter'] ) && $this->request['rep_filter'] == 'update' )
			{
				$_mem_cache = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
				
				if( $this->request['rep_filter_set'] == '*' )
				{
					$_mem_cache['rep_filter'] = '*';
				}
				else
				{
					$_mem_cache['rep_filter'] = intval( $this->request['rep_filter_set'] );
				}
				
				IPSMember::packMemberCache( $this->memberData['member_id'], $_mem_cache );
				
				$this->memberData['_members_cache'] = $_mem_cache;
			}
			else
			{
				$this->memberData['_members_cache'] = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
			}
		}

		//-----------------------------------------
		// Process the topic
		//-----------------------------------------
		
		$this->topicSetUp();
		
		//-----------------------------------------
		// Which view are we using?
		// If mode='show' we're viewing poll results, don't change view mode
		//-----------------------------------------
		
		$this->topic_view_mode = $this->_generateTopicViewMode();
		
		//-----------------------------------------
		// VIEWS
		//-----------------------------------------

		$this->_doViewCheck();
		
		//-----------------------------------------
		// UPDATE TOPIC?
		//-----------------------------------------
		
		$this->_doUpdateTopicCheck();
		
		//-----------------------------------------
		// Check PPD
		//-----------------------------------------
		
		$this->_ppd_ok = $this->registry->getClass('class_forums')->checkGroupPostPerDay( $this->memberData, TRUE );
		
		//-----------------------------------------
		// Post ID stuff
		//-----------------------------------------
		
		$find_pid	  = $this->request['pid'] == "" ? (isset( $this->request['p'] ) ? $this->request['p'] : 0 ) : ( isset( $this->request['pid'] ) ? $this->request['pid'] : 0 );
		$threaded_pid = $find_pid ? '&amp;pid=' . $find_pid : '';
		$linear_pid   = $find_pid ? '&amp;view=findpost&amp;p=' . $find_pid : '';
		
		//-----------------------------------------
		// Remove potential [attachmentid= tag in title
		//-----------------------------------------
		
		$this->topic['title'] = IPSText::stripAttachTag( $this->topic['title'] );
		
		//-----------------------------------------
		// Get posts
		//-----------------------------------------
		
		$_NOW = IPSDebug::getMemoryDebugFlag();

		if ( $this->topic_view_mode == 'threaded' )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/threaded.php' );
			$threaded	= new threadedModeLibrary( $this->registry, $this );
			$post_data	= $threaded->_getTopicDataThreaded();
		}
		else
		{
			$post_data = $this->_getTopicDataLinear();
		}

		unset( $this->cached_members );
		
		IPSDebug::setMemoryDebugFlag( "TOPICS: Parsed Posts - Completed", $_NOW );
	
		//-----------------------------------------
		// Generate template
		//-----------------------------------------
		
		$this->topic['id'] = $this->topic['forum_id'];
		
		/* Posting Allowed? */
		$this->forum['_user_can_post'] = 1;//( $this->_ppd_ok === TRUE ) ? 1 : 0;
		
		if( ! $this->registry->permissions->check( 'start', $this->forum ) )
		{
			$this->forum['_user_can_post'] = 0;
		}

		if( ! $this->forum['sub_can_post'] )
		{
			$this->forum['_user_can_post'] = 0;
		}

		if( $this->forum['min_posts_post'] && $this->forum['min_posts_post'] > $this->memberData['posts'] )
		{
			$this->forum['_user_can_post'] = 0;
		}
		
		if( ! $this->forum['status'] )
		{
			$this->forum['_user_can_post'] = 0;
		}
		
		if( ! $this->memberData['g_post_new_topics'] )
		{
			$this->forum['_user_can_post'] = 0;
		}

		//-----------------------------------------
		// This has to be called first to set $this->poll_only
		//-----------------------------------------
		
		$poll_data	= ( $this->topic['poll_state'] ) ? $this->_generatePollOutput() : '';

		$displayData = array( 'threaded_mode_enabled' => ( $this->topic_view_mode == 'threaded' ) ? 1 : 0,
							  'fast_reply'			  => $this->_getFastReplyData(),
							  'multi_mod'			  => $this->_getMultiModerationData(),
							  'reply_button'		  => $this->_getReplyButtonData(),
							  'active_users'		  => $this->_getActiveUserData(),
							  'mod_links'			  => $this->_generateModerationPanel(),
							  'poll_data'			  => $poll_data );
		
		$post_data = $this->_parseAttachments( $post_data );
		
		/* Rules */
		if( $this->forum['show_rules'] == 2 )
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberdata['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];

			$this->forum['rules_text'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $this->forum['rules_text'] );
		}
		
		//-----------------------------------------
		// Fix post order if linear+ and ordering
		// posts DESC (rare, I know): 16221
		//-----------------------------------------
		
		if( $this->topic_view_mode == 'linearplus' AND $this->settings['post_order_sort'] == 'desc' )
		{
			$newPosts	= array();
			
			foreach( $post_data as $pid => $data )
			{
				if( $pid == $this->topic['topic_firstpost'] )
				{
					array_unshift( $newPosts, $data );
				}
				else
				{
					$newPosts[] = $data;
				}
			}
			
			//-----------------------------------------
			// Nothing else relies on the key being pid
			//-----------------------------------------
			
			$post_data	= $newPosts;
		}
		
		$template = $this->registry->output->getTemplate('topic')->topicViewTemplate( $this->forum, $this->topic, $post_data, $displayData );

		//-----------------------------------------
		// Send for output
		//-----------------------------------------
		
		$this->registry->output->setTitle( $this->topic['title'] . ' - ' . $this->settings['board_name']);
		$this->registry->output->addContent( $template );
		
		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->output->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
		/**
		 * Update cache monitor
		 */
		IPSContentCache::updateMonitor( $this->_cacheMonitor );
		
		/**
		 * Add navigational links
		 */
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'showforum=' . $this->topic['forum_id'], 'public', $this->forum['name_seo'], 'showforum' ) . "' />" );
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='author' href='" . $this->registry->output->buildSEOUrl( 'showuser=' . $this->topic['starter_id'], 'public', $this->topic['seo_first_name'], 'showuser' ) . "' />" );
		
		/* Add Meta Content */
		if ( $this->_firstPostContent )
		{
			$this->registry->output->addMetaTag( 'keywords', $this->topic['title'] . ' ' . str_replace( "\n", " ", str_replace( "\r", "", strip_tags( $this->_firstPostContent ) ) ), TRUE );
		}
		
		$this->registry->output->addMetaTag( 'description', $this->topic['title'] . ': ' . $this->topic['description'], TRUE );
		
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Builds an array of post data for output
	 *
	 * @access	public
	 * @param	array	$row	Array of post data
	 * @return	array
	 **/
	public function parsePostRow( $row = array() )
	{
		//-----------------------------------------
		// Memory Debug
		//-----------------------------------------
		
		$_NOW   = IPSDebug::getMemoryDebugFlag();
		$poster = array();
		
		//-----------------------------------------
		// Cache member
		//-----------------------------------------
		
		if ( $row['author_id'] != 0 )
		{
			//-----------------------------------------
			// Is it in the hash?
			//-----------------------------------------
			
			if ( isset( $this->cached_members[ $row['author_id'] ] ) )
			{
				//-----------------------------------------
				// Ok, it's already cached, read from it
				//-----------------------------------------
				
				$poster = $this->cached_members[ $row['author_id'] ];
				$row['name_css'] = 'normalname';
			}
			else
			{
				$row['name_css'] = 'normalname';
				$poster = $row;
				
				if ( isset( $poster['cache_content_sig'] ) )
				{
					$poster['cache_content'] = $poster['cache_content_sig'];
					$poster['cache_updated'] = $poster['cache_updated_sig'];
					
					/* Cache data monitor */
					$this->_cacheMonitor['sig']['cached']++;
				}
				else
				{
					unset( $poster['cache_content'], $poster['cache_updated'] );
					
					/* Cache data monitor */
					$this->_cacheMonitor['sig']['raw']++;
				}
				
				$poster = IPSMember::buildDisplayData( $poster, array( 'signature' => 1, 'customFields' => 1, 'warn' => 1, 'avatar' => 1, 'checkFormat' => 1, 'cfLocation' => 'topic' ) );
				$poster['member_id'] = $row['mid'];
				
				//-----------------------------------------
				// Add it to the cached list
				//-----------------------------------------
				
				$this->cached_members[ $row['author_id'] ] = $poster;
			}
		}
		else
		{
			//-----------------------------------------
			// It's definitely a guest...
			//-----------------------------------------
			
			$row['author_name']	= $this->settings['guest_name_pre'] . $row['author_name'] . $this->settings['guest_name_suf'];
			
			$poster = IPSMember::setUpGuest( $row['author_name'] );
			$poster['members_display_name']		= $row['author_name'];
			$poster['_members_display_name']	= $row['author_name'];
			$poster['custom_fields']			= "";
			$poster['warn_img']					= "";
			$row['name_css']					= 'unreg';
		}
		
		# Memory Debug
		IPSDebug::setMemoryDebugFlag( "PID: ".$row['pid'] . " - Member Parsed", $_NOW );
		
		//-----------------------------------------
		// Queued
		//-----------------------------------------
		
		if ( $this->topic['topic_firstpost'] == $row['pid'] and $this->topic['approved'] != 1 )
		{
			$row['queued'] = 1;
		}
		
		//-----------------------------------------
		// Edit...
		//-----------------------------------------
		
		$row['edit_by'] = "";
		
		if ( ( $row['append_edit'] == 1 ) and ( $row['edit_time'] != "" ) and ( $row['edit_name'] != "" ) )
		{
			$e_time = $this->registry->class_localization->getDate( $row['edit_time'] , 'LONG' );
			
			$row['edit_by'] = sprintf( $this->lang->words['edited_by'], $row['edit_name'], $e_time );
		}
		
		//-----------------------------------------
		// Parse the post
		//-----------------------------------------
		
		if ( ! $row['cache_content'] )
		{
			$_NOW2   = IPSDebug::getMemoryDebugFlag();
		
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->forum['use_html'] and $this->caches['group_cache'][ $row['member_group_id'] ]['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->forum['use_ibc'];
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
			
			/* Work around */
			$_tmp = $this->memberData['view_img'];
			$this->memberData['view_img'] = 1;
			
			$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
			
			$this->memberData['view_img'] = $_tmp;
			
			IPSDebug::setMemoryDebugFlag( "topics::parsePostRow - bbcode parse - Completed", $_NOW2 );
			
			IPSContentCache::update( $row['pid'], 'post', $row['post'] );
			
			/* Cache data monitor */
			$this->_cacheMonitor['post']['raw']++;
		}
		else
		{
			$row['post'] = '<!--cached-' . gmdate( 'r', $row['cache_updated'] ) . '-->' . $row['cache_content'];
			
			/* Cache data monitor */
			$this->_cacheMonitor['post']['cached']++;
		}
		
		//-----------------------------------------
		// Capture content
		//-----------------------------------------
		
		if ( $this->topic['topic_firstpost'] == $row['pid'] )
		{
			$this->_firstPostContent = $row['post'];
		}
		
		//-----------------------------------------
		// View image...
		//-----------------------------------------
		
		$row['post'] = IPSText::getTextClass( 'bbcode' )->memberViewImages( $row['post'] );
		
		//-----------------------------------------
		// Highlight...
		//-----------------------------------------
		
		if ( $this->request['hl'] )
		{
			$row['post'] = IPSText::searchHighlight( $row['post'], $this->request['hl'] );
		}
		
		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------

		if ( $this->qpids )
		{
			if ( strstr( ','.$this->qpids.',', ','.$row['pid'].',' ) )
			{
				$row['_mq_selected'] = 1;
			}
		}
		
		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------

		if ( $this->memberData['is_mod'] )
		{
			if ( $this->request['selectedpids'] )
			{
				if ( strstr( ','.$this->request['selectedpids'].',', ','.$row['pid'].',' ) )
				{
					$row['_pid_selected'] = 1;
				}
				
				$this->request['selectedpidcount'] =  count( explode( ",", $this->request['selectedpids']  ) );
			}
		}
		
		//-----------------------------------------
		// Delete button..
		//-----------------------------------------
		
		$row['_can_delete'] = $row['pid'] != $this->topic['topic_firstpost'] 
							  ? $this->_getDeleteButtonData( $row ) 
							  : FALSE;		
		$row['_can_edit']   = $this->_getEditButtonData( $row );
		$row['_show_ip']	= $this->_getIPAddressData();
		
		//-----------------------------------------
		// Siggie stuff
		//-----------------------------------------
		
		$row['signature'] = "";
		
		if ( isset( $poster['signature'] ) AND $poster['signature'] AND $this->memberData['view_sigs'] )
		{
			if ($row['use_sig'] == 1)
			{
				$row['signature'] = $this->registry->output->getTemplate( 'global' )->signature_separator( $poster['signature'] );
			}
		}
		
		//-----------------------------------------
		// Fix up the membername so it links to the members profile
		//-----------------------------------------
	
		if ( $poster['member_id'] )
		{
			$poster['_members_display_name'] = "<a href='{$this->settings['_base_url']}showuser={$poster['member_id']}'>{$poster['members_display_name_short']}</a>";
		}
		
		//-----------------------------------------
		// Post number
		//-----------------------------------------
		
		if ( $this->topic_view_mode == 'linearplus' and $this->topic['topic_firstpost'] == $row['pid'] )
		{
			$row['post_count'] = 1;
			
			if ( ! $this->first )
			{
				$this->post_count++;
			}
		}
		else
		{
			$this->post_count++;
		
			$row['post_count'] = intval($this->request['st']) + $this->post_count;
		}
		
		$row['forum_id'] = $this->topic['forum_id'];		
		
		//-----------------------------------------
		// Memory Debug
		//-----------------------------------------
	
		IPSDebug::setMemoryDebugFlag( "PID: ".$row['pid']. " - Completed", $_NOW );
		
		return array( 'row' => $row, 'poster' => $poster );
	}

	/**
	 * Return last post
	 *
	 * @access	public
	 * @return	void
	 **/
	public function returnLastPost()
	{
		$st = 0;
		
		$mode 	= $this->topic_view_mode;
		
		$pre	= $mode != 'threaded' ? 'st' : 'start';
		
		if( $mode == 'threaded' )
		{
			$this->settings['display_max_posts'] = $this->settings['threaded_per_page'] ;
			$this->request['st'] =  $this->request['start'] ;
		}		
			
		if ($this->topic['posts'])
		{
			if ( (($this->topic['posts'] + 1) % $this->settings['display_max_posts']) == 0 )
			{
				$pages = ($this->topic['posts'] + 1) / $this->settings['display_max_posts'];
			}
			else
			{
				$number = ( ($this->topic['posts'] + 1) / $this->settings['display_max_posts'] );
				$pages = ceil( $number);
			}
			
			$st = ($pages - 1) * $this->settings['display_max_posts'];
			
			if( $this->settings['post_order_sort'] == 'desc' )
			{
				$st = (ceil(($this->topic['posts']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
			}
		}
		
		$this->DB->build( array( 
										'select' => 'pid',
										'from'   => 'posts',
										'where'  => "queued=0 AND topic_id=".$this->topic['tid'],
										'order'  => $this->settings['post_order_column'].' DESC',
										'limit'  => array( 0,1 )
							)	  );
							 
		$this->DB->execute();
		
		$post = $this->DB->fetch();
		
		$this->registry->output->silentRedirect($this->settings['base_url']."showtopic=".$this->topic['tid']."&pid={$post['pid']}&{$pre}={$st}&"."#entry".$post['pid'], $this->topic['title_seo'] );
	}
	
	/**
	* Parse attachments
	*
	* @access	public
	* @param	array	Array of post data
	* @return	string	HTML parsed by attachment class
	*/
	public function _parseAttachments( $postData )
	{
		//-----------------------------------------
		// INIT. Yes it is
		//-----------------------------------------
		
		$postHTML = array();
		
		//-----------------------------------------
		// Separate out post content
		//-----------------------------------------
		
		foreach( $postData as $id => $post )
		{
			$postHTML[ $id ] = $post['post']['post'];
		}
		
		//-----------------------------------------
		// ATTACHMENTS!!!
		//-----------------------------------------
		
		if ( $this->topic['topic_hasattach'] )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
				$this->class_attach = new class_attach( $this->registry );
			}
			
			//-----------------------------------------
			// Not got permission to view downloads?
			//-----------------------------------------
			
			if ( $this->registry->permissions->check( 'download', $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ] ) === FALSE )
			{
				$this->settings['show_img_upload'] =  0 ;
			}
			
			//-----------------------------------------
			// Continue...
			//-----------------------------------------
			
			$this->class_attach->type  = 'post';
			$this->class_attach->init();
			
			# attach_pids is generated in the func_topic_xxxxx files
			$attachHTML = $this->class_attach->renderAttachments( $postHTML, $this->attach_pids );
		}
		
		/* Now parse back in the rendered posts */
		if( is_array($attachHTML) AND count($attachHTML) )
		{
			foreach( $attachHTML as $id => $data )
			{
				/* Get rid of any lingering attachment tags */
				if ( stristr( $data['html'], "[attachment=" ) )
				{
					$data['html'] = IPSText::stripAttachTag( $data['html'] );
				}
				
				$postData[ $id ]['post']['post']           = $data['html'];
				$postData[ $id ]['post']['attachmentHtml'] = $data['attachmentHtml'];
			}
		}
		
		return $postData;
	}
	
	/**
	 * Generate the Poll output
	 *
	 * @access	public
	 * @return	string
	 **/
	public function _generatePollOutput()
	{
		$showResults = 0;
		$pollData    = array();
		
		//-----------------------------------------
		// Get the poll information...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'polls', 'where' => 'tid=' . $this->topic['tid'] ) );							 
		$this->DB->execute();
		
		$poll = $this->DB->fetch();
		
		//-----------------------------------------
		// check we have a poll
		//-----------------------------------------
		
		if ( ! $poll['pid'] )
		{
			return;
		}
		
		//-----------------------------------------
		// Do we have a poll question?
		//-----------------------------------------
		
		if ( ! $poll['poll_question'] )
		{
			$poll['poll_question'] = $this->topic['title'];
		}
		
		//-----------------------------------------
		// Poll only?
		//-----------------------------------------
		
		if( $poll['poll_only'] == 1 )
		{
			$this->poll_only = true;
		}
		
		//-----------------------------------------
		// Additional Poll Vars
		//-----------------------------------------
		
		$poll['_totalVotes']  = 0;
		$poll['_memberVoted'] = 0;
		$memberChoices        = array();
		
		//-----------------------------------------
		// Have we voted in this poll?
		//-----------------------------------------
		
		$this->DB->build( array( 'select'   => 'v.*',
								 'from'     => array( 'voters' => 'v' ),
								 'where'    => 'v.tid=' . $this->topic['tid'],
								 'add_join' => array( array( 'select' => 'm.*',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=v.member_id',
															 'type'   => 'left' ) ) ) );
		$this->DB->execute();
		
		while( $voter = $this->DB->fetch() )
		{
			$poll['_totalVotes']++;
			
			if ( $voter['member_id'] == $this->memberData['member_id'] )
			{
				$poll['_memberVoted'] = 1;
			}
			
			/* Member choices */
			if ( $poll['poll_view_voters'] AND $voter['member_choices'] AND $this->settings['poll_allow_public'] )
			{
				$_choices = unserialize( $voter['member_choices'] );
				
				if ( is_array( $_choices ) AND count( $_choices ) )
				{
					$memberData = array( 'member_id'            => $voter['member_id'],
										 'members_seo_name'     => $voter['members_seo_name'],
										 'members_display_name' => $voter['members_display_name'],
										 'members_colored_name' => IPSLib::makeNameFormatted( $voter['members_display_name'], $voter['member_group_id'] ),
										 '_last'                => 0 );
					
					foreach( $_choices as $_questionID => $data )
					{
						foreach( $data as $_choice )
						{
							$memberChoices[ $_questionID ][ $_choice ][ $voter['member_id'] ] = $memberData;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Already Voted
		//-----------------------------------------
		
		if ( $poll['_memberVoted'] )
		{
			$showResults = 1;
		}
	
		//-----------------------------------------
		// Created poll and can't vote in it
		//-----------------------------------------
		
		if ( ($poll['starter_id'] == $this->memberData['member_id']) and ($this->settings['allow_creator_vote'] != 1) )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Guest, but can view results without voting
		//-----------------------------------------
		
		if ( ! $this->memberData['member_id'] AND $this->settings['allow_result_view'] )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// is the topic locked?
		//-----------------------------------------
		
		if ( $this->topic['state'] == 'closed' OR ! $this->forum['status'] )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Can we see the poll before voting?
		//-----------------------------------------
		
		if ( $this->settings['allow_result_view'] == 1 AND $this->request['mode'] == 'show' )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Stop the parser killing images
		// 'cos there are too many
		//-----------------------------------------
		
		$tmp_max_images			      = $this->settings['max_images'];
		$this->settings['max_images'] = 0;
		
		//-----------------------------------------
		// Parse it
		//-----------------------------------------
		
		$poll_answers 	 = unserialize(stripslashes($poll['choices']));
		
		if( !is_array($poll_answers) OR !count($poll_answers) )
		{
			return '';
		}
		
		reset($poll_answers);
		
		foreach ( $poll_answers as $id => $data )
		{
			if( !is_array($data['choice']) OR !count($data['choice']) )
			{
				continue;
			}

			//-----------------------------------------
			// Get the question
			//-----------------------------------------
			
			$pollData[ $id ]['question'] = $data['question'];
			
			$tv_poll = 0;
			
			# Get total votes for this question
			if( is_array($poll_answers[ $id ]['votes']) AND count($poll_answers[ $id ]['votes']) )
			{
				foreach( $poll_answers[ $id ]['votes'] as $number)
				{
					$tv_poll += intval( $number );
				}
			}
				
			//-----------------------------------------
			// Get the choices for this question
			//-----------------------------------------
			
			foreach( $data['choice'] as $choice_id => $text )
			{
				$choiceData = array();
				$choice     = $text;
				$voters     = array();
				
				# Get total votes for this question -> choice
				$votes   = intval($data['votes'][ $choice_id ]);
				
				if ( strlen($choice) < 1 )
				{
					continue;
				}
			
				$choice = IPSText::getTextClass( 'bbcode' )->parsePollTags($choice);
				
				if ( $showResults )
				{
					$percent = $votes == 0 ? 0 : $votes / $tv_poll * 100;
					$percent = sprintf( '%.2F' , $percent );
					$width   = $percent > 0 ? intval($percent * 2) : 0;
				
					/* Voters */
					if ( $poll['poll_view_voters'] AND $memberChoices[ $id ][ $choice_id ] )
					{
						$voters = $memberChoices[ $id ][ $choice_id ];
						$_tmp   = $voters;
					
						$lastDude = array_pop( $_tmp );
					
						$voters[ $lastDude['member_id'] ]['_last'] = 1;
					}
					
					$pollData[ $id ]['choices'][ $choice_id ] = array( 'votes'   => $votes,
													  				   'choice'  => $choice,
																	   'percent' => $percent,
																	   'width'   => $width,
																	   'voters'  => $voters );
				}
				else
				{
					$pollData[ $id ]['choices'][ $choice_id ] =  array( 'type'   => ( isset($data['multi']) AND $data['multi'] == 1 ) ? 'multi' : 'single',
													   					'votes'  => $votes,
																		'choice' => $choice );
				}
			}
		}

		$html = $this->registry->output->getTemplate('topic')->pollDisplay( $poll, $this->topic, $this->forum, $pollData, $showResults );
		
		$this->settings['max_images'] = $tmp_max_images;
		
		return $html;
	}
	
	/**
	 * Generate the topic mode.
	 *
	 * @access	private
	 * @return	string
	 **/
	private function _generateTopicViewMode()
	{
		$return = '';
		
		// We don't want indexed links changing the mode
		/*if ( $this->request['mode'] AND $this->request['mode'] AND $this->request['mode'] != 'show' )
		{
			$return = $this->request['mode'];
			IPSCookie::set( 'topicmode', $this->request['mode'], 1 );
		}
		else
		{*/
			$return = IPSCookie::get('topicmode');
		//}
		
		if ( ! $return )
		{
			$return = $this->settings['topicmode_default'] ? $this->settings['topicmode_default'] : 'linear';
		}
	
		if ( $return == 'threaded' )
		{
			$this->settings['display_max_posts'] = $this->settings['threaded_per_page'];
			$this->request['st'] = $this->request['start'];
		}
		
		return $return;
	}

	/**
	 * Updates the topic
	 *
	 * @access	private
	 * @return	void
	 **/
	private function _doUpdateTopicCheck()
	{
		$mode 	= $this->topic_view_mode;
		$pre	= $mode != 'threaded' ? 'st' : 'start';

		if ( empty( $this->request['b'] ) )
		{
			if ( $this->topic['topic_firstpost'] < 1 )
			{
				//--------------------------------------
				// No first topic set - old topic, update
				//--------------------------------------
				
				$this->DB->build( array( 'select' => 'pid', 'from' => 'posts', 'where' => 'topic_id='.$this->topic['tid'].' AND new_topic=1' ) );									 
				$this->DB->execute();
				
				$post = $this->DB->fetch();
				
				if ( ! $post['pid'] )
				{
					//-----------------------------------------
					// Get first post info
					//-----------------------------------------
					
					$this->DB->build( array( 
													'select' => 'pid',
													'from'   => 'posts',
													'where'  => "topic_id={$this->topic['tid']}",
													'order'  => 'pid ASC',
													'limit'  => array( 0, 1 ) 
											) 	);
					$this->DB->execute();
					
					$first_post  = $this->DB->fetch();
					$post['pid'] = $first_post['pid'];
				}
				
				if ( $post['pid'] )
				{
					$this->DB->update('topics', 'topic_firstpost='.$post['pid'], 'tid='.$this->topic['tid'], false, true );
				}
				
				//--------------------------------------
				// Reload "fixed" topic
				//--------------------------------------
				
				$this->registry->output->silentRedirect($this->settings['base_url']."showtopic=".$this->topic['tid']."&b=1&{$pre}=" . $this->request['st'] . "&p=" . $this->request['p'] . ""."&#entry".$this->request['p']);
			}
		}
	}
	
	/**
	 * Tests to see if we're viewing a post, etc
	 *
	 * @access	private
	 * @return	void
	 **/
	private function _doViewCheck()
	{
		$mode 	= $this->topic_view_mode;
		$pre	= $mode != 'threaded' ? 'st' : 'start';
   
		if ( $this->request['view'] )
		{
			if ($this->request['view'] == 'new')
			{
				//-----------------------------------------
				// Newer 
				//-----------------------------------------
				
				$this->DB->build( array( 
												'select' => 'tid, title_seo',
												'from'   => 'topics',
												'where'  => "forum_id={$this->forum['id']} AND approved=1 AND state <> 'link' AND last_post > {$this->topic['last_post']}",
												'order'  => 'last_post',
												'limit'  => array( 0,1 )
									)	);
				$this->DB->execute();
				
				if ( $this->DB->getTotalRows() )
				{
					$this->topic = $this->DB->fetch();
					
					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$this->topic['tid'], $this->topic['title_seo'] );
				}
				else
				{
					$this->registry->output->showError( 'topics_none_newer', 10356 );
				}
			}
			else if ($this->request['view'] == 'old')
			{
				//-----------------------------------------
				// Older
				//-----------------------------------------
				
				$this->DB->build( array( 
												'select' => 'tid, title_seo',
												'from'   => 'topics',
												'where'  => "forum_id={$this->forum['id']} AND approved=1 AND state <> 'link' AND last_post < {$this->topic['last_post']}",
												'order'  => 'last_post DESC',
												'limit'  => array( 0,1 )
									)	);
									
				$this->DB->execute();
					
				if ( $this->DB->getTotalRows() )
				{
					$this->topic = $this->DB->fetch();
					
					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$this->topic['tid'], $this->topic['title_seo'] );
				}
				else
				{
					$this->registry->output->showError( 'topics_none_older', 10357 );
				}
			}
			else if ($this->request['view'] == 'getlastpost')
			{
				//-----------------------------------------
				// Last post
				//-----------------------------------------
				
				$this->returnLastPost();
			}
			else if ($this->request['view'] == 'getnewpost')
			{
				//-----------------------------------------
				// Newest post
				//-----------------------------------------
				
				$st	       = 0;
				$pid	   = "";
				$markers   = $this->memberData['members_markers'];
				$last_time = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $this->forum['id'], 'itemID' => $this->topic['tid'] ) );
				
				$this->DB->build( array( 
												'select' => 'MIN(pid) as pid',
												'from'   => 'posts',
												'where'  => "queued=0 AND topic_id={$this->topic['tid']} AND post_date > " . intval( $last_time ),
												'limit'  => array( 0,1 )
									)	);						
				$this->DB->execute();
				
				$post = $this->DB->fetch();
				
				if ( $post['pid'] )
				{
					$pid = "&#entry".$post['pid'];
					
					$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} AND pid <= {$post['pid']}" ) );										
					$this->DB->execute();
				
					$cposts = $this->DB->fetch();
					
					if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
						$pages = ceil( $number);
					}
					
					$st = ($pages - 1) * $this->settings['display_max_posts'];
					
					if( $this->settings['post_order_sort'] == 'desc' )
					{
						$st = (ceil(($this->topic['posts']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
					}						
					
					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$this->topic['tid']."&pid={$post['pid']}&{$pre}={$st}".$pid, $this->topic['title_seo'] );
				}
				else
				{
					$this->returnLastPost();
				}
			}
			else if ($this->request['view'] == 'findpost')
			{
				//-----------------------------------------
				// Find a post
				//-----------------------------------------
				
				$pid = intval($this->request['p']);
				
				if ( $pid > 0 )
				{
					$sort_value = $pid;
					$sort_field = ($this->settings['post_order_column'] == 'pid') ? 'pid' : 'post_date';
					
					if($sort_field == 'post_date')
					{
						$date = $this->DB->buildAndFetch( array( 'select' => 'post_date', 'from' => 'posts', 'where' => 'pid=' . $pid ) );

						$sort_value = $date['post_date'];
					}

					$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} AND {$sort_field} <=" . intval( $sort_value ) ) );										
					$this->DB->execute();
					
					$cposts = $this->DB->fetch();
					
					if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
						$pages = ceil($number);
					}
					
					$st = ($pages - 1) * $this->settings['display_max_posts'];
					
					if( $this->settings['post_order_sort'] == 'desc' )
					{
						$st = (ceil(($this->topic['posts']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
					}
					
					$search_hl = '';
					if( isset( $this->request['hl'] ) && $this->request['hl'] )
					{
						$search_hl .= "&amp;hl={$this->request['hl']}";
					}
					
					if( isset( $this->request['fromsearch'] ) && $this->request['fromsearch'] )
					{
						$search_hl .= "&amp;fromsearch={$this->request['fromsearch']}";
					}

					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$this->topic['tid']."&{$pre}={$st}&p={$pid}{$search_hl}"."&#entry".$pid, $this->topic['title_seo'] );
				}
				else
				{
					$this->returnLastPost();
				}
			}
		}
	}
	
	/**
	 * Return whether we can see the IP address or not
	 *
	 * @access	public
	 * @return	bool
	 **/
	public function _getIPAddressData()
	{
		if ( $this->memberData['g_is_supmod'] != 1 && ( !isset($this->moderator['view_ip']) OR $this->moderator['view_ip'] != 1 ) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Return whether or not we have permission to delete the post
	 *
	 * @access	public
	 * @return	bool
	 **/
	public function _getDeleteButtonData( $poster )
	{
		if ( ! $this->memberData['member_id']  )
		{
			return FALSE;
		}
		
		if ( $this->memberData['g_is_supmod'] OR $this->moderator['delete_post'] )
		{
			return TRUE;
		}
		
		if ( $poster['member_id'] == $this->memberData['member_id'] and ( $this->memberData['g_delete_own_posts'] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Return whether or not we can edit this post
	 *
	 * @access	public
	 * @param	array 		Array of post data
	 * @return  bool
	 **/
	public function _getEditButtonData( $poster=array() )
	{
		if ( $this->memberData['member_id'] == "" or $this->memberData['member_id'] == 0 )
		{
			return FALSE;
		}
				
		if ( $this->memberData['g_is_supmod'] )
		{
			return TRUE;
		}
		
		if ( $this->moderator['edit_post'] )
		{
			return TRUE;
		}
		
		if ( $poster['member_id'] == $this->memberData['member_id'] and ($this->memberData['g_edit_posts']) )
		{
			// Have we set a time limit?
			if ($this->memberData['g_edit_cutoff'] > 0)
			{
				if ( $poster['post_date'] > ( time() - ( intval($this->memberData['g_edit_cutoff']) * 60 ) ) )
				{
					return TRUE;
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
		
		return FALSE;
	}
	
	/**
	 * Get reply button data
	 *
	 * @access	public
	 * @return	array
	 **/
	public function _getReplyButtonData()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$image = '';
		$url   = '';
		
		if ($this->topic['state'] == 'closed' OR ( $this->topic['poll_state'] AND $this->poll_only ) )
		{
			/* Do we have the ability to post in closed topics or is this a poll only?*/
			if ($this->memberData['g_post_closed'] == 1)
			{
				$url   = $this->settings['base_url_with_app'] . "module=post&amp;section=post&amp;do=reply_post&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid'];
				$image = 'locked';
			}
			else
			{
				$image = "locked";
			}
		}
		else
		{
			if ( $this->topic['state'] == 'moved' )
			{
				$image = "moved";
			}
			else
			{
				//if ( $this->_ppd_ok !== TRUE )
				//{
					/* Posts per day restriction */
					//$image = 'locked';
				//}
				if( $this->forum['min_posts_post'] && $this->forum['min_posts_post'] > $this->memberData['posts'] )
				{
					$image = "locked";
				}
				else if( ! $this->forum['status'] )
				{
					$image = "locked";
				}
				else
				{
					if ( $this->memberData['member_id'] AND ( ( ( $this->memberData['member_id'] == $this->topic['starter_id'] ) AND ! $this->memberData['g_reply_own_topics'] ) OR ( ! $this->memberData['g_reply_other_topics'] ) ) )
					{
						$image = "no_reply";
					}
					else if( $this->registry->permissions->check( 'reply', $this->forum ) == TRUE )
					{
						$url   = $this->settings['base_url_with_app'] . "module=post&amp;section=post&amp;do=reply_post&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid'];
						$image = "reply";
					}
					else
					{
						$image = "no_reply";
					}
				}
			}
		}

		return array( 'image' => $image, 'url' => $url );
	}		
	
	/**
	 * Get multimoderation data
	 *
	 * @access	public
	 * @return	array
	 **/
	public function _getMultiModerationData()
	{
		$return_array = array();
		$mm_array	 = $this->registry->class_forums->getMultimod( $this->forum['id'] );
		
		//-----------------------------------------
		// Print and show
		//-----------------------------------------
		
		if ( is_array( $mm_array ) and count( $mm_array ) )
		{
			foreach( $mm_array as $m )
			{
				$return_array[] = $m;
			}
		}
		
		return $return_array;
	}
	
	/**
	 * Get fast reply status
	 *
	 * @access	public
	 * @return	string
	 * @deprecated	Fast reply is always open now, but this does still check perms before showing it
	 **/
	public function _getFastReplyData()
	{
		$show = 'unavailable';
		
		if (   ( $this->forum['quick_reply'] == 1 )
		   and ( $this->registry->permissions->check( 'reply', $this->forum ) == TRUE )
		   and ( $this->topic['state'] != 'closed' )
		   and ( $this->_ppd_ok === TRUE )
		   and ( ! $this->poll_only ) )
		{
			$show  = "hide";
			$cache = $this->memberData['_cache'];
			
			$sqr = ($cache['qr_open']) ? $cache['qr_open'] : 0;
			
			if ( $sqr == 1 )
			{
				$show = "show";
			}
		}
		
		/* Status? */
		if ( $this->_ppd_ok === TRUE )
		{
			/* status from PPD */
			$this->topic['_fastReplyStatusMessage'][] = $this->registry->getClass('class_forums')->ppdStatusMessage;
			
			/* status from mod posts */
			$this->topic['_fastReplyStatusMessage'][] = $this->registry->getClass('class_forums')->fetchPostModerationStatusMessage( $this->memberData, $this->forum, $this->topic, 'reply' );
		}
		
		return $show;
	}
	
	/**
	 * Return active user data
	 *
	 * @access	public
	 * @return	array
	 **/
	public function _getActiveUserData()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ar_time = time();
		$cached = array();
		$guests = array();
		$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => array() );
		$rows   = array( $ar_time => array( 'login_type'   => substr($this->memberData['login_anonymous'],0, 1),
											'running_time' => $ar_time,
											'id'		   => $this->member->session_id,
											'seo_name'     => $this->memberData['members_seo_name'],
											'member_id'	   => $this->memberData['member_id'],
											'member_name'  => $this->memberData['members_display_name'],
											'member_group' => $this->memberData['member_group_id'] ) );
		
		//-----------------------------------------
		// Process users active in this forum
		//-----------------------------------------
		
		if ($this->settings['no_au_topic'] != 1)
		{	
			//-----------------------------------------
			// Get the users
			//-----------------------------------------
			
			$cut_off = ($this->settings['au_cutoff'] != "") ? $this->settings['au_cutoff'] * 60 : 900;

			$this->DB->build( array( 
									'select' => 's.member_id, s.member_name, s.member_group, s.id, s.login_type, s.location, s.running_time, s.uagent_type, s.current_module, s.seo_name',
									'from'	 => 'sessions s',
									'where'	 => "s.location_1_type='topic' AND s.location_1_id={$this->topic['tid']} AND s.running_time > " . ( time() - $cut_off ) . " AND s.in_error=0",
						)		);	 
			$this->DB->execute();
					   
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ($r = $this->DB->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );

			//-----------------------------------------
			// PRINT...
			//-----------------------------------------
			
			foreach( $rows as $result )
			{
				$result['member_name'] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
				
				$last_date = $this->registry->class_localization->getTime( $result['running_time'] );
				
				if ( $result['member_id'] == 0 OR ! $result['member_name'] )
				{
					if ( in_array( $result['id'], $guests ) )
					{
						continue;
					}
					
					//-----------------------------------------
					// Bot?
					//-----------------------------------------

					if ( $result['uagent_type'] == 'search' )
					{
						/* Skipping bot? */
						if ( ! $this->settings['spider_active'] )
						{
							continue;
						}
						
						//-----------------------------------------
						// Seen bot of this type yet?
						//-----------------------------------------

						if ( ! $cached[ $result['member_name'] ] )
						{
							if ( $this->settings['spider_anon'] )
							{
								if ( $this->memberData['g_access_cp'] )
								{
									$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'seo' => $result['seo_name'] );
								}
							}
							else
							{
								$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'seo' => $result['seo_name'] );
							}

							$cached[ $result['member_name'] ] = 1;
						}
						else
						{
							$active['guests']++;
							$guests[] = $result['id'];
						}
					}
					else
					{
						$active['guests']++;
						$guests[] = $result['id'];
					}
				}
				else
				{
					if (empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;
						
						$p_start = "";
						$p_end   = "";
						$p_title = sprintf( $this->lang->words['au_reading'], $last_date );
						
						if ( strstr( $result['current_module'], 'post' ) and $result['member_id'] != $this->memberData['member_id'] )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
							$p_title = sprintf( $this->lang->words['au_posting'], $last_date );
						}
						
						if ( ! $this->settings['disable_anonymous'] AND $result['login_type'] )
						{
							if ( $this->memberData['g_access_cp'] and ($this->settings['disable_admin_anon'] != 1) )
							{
								$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'] . '*', 'p_start' => $p_start, 'p_title' => $p_title, 'p_end' => $p_end, 'seo' => $result['seo_name'] );
								$active['anon']++;
							}
							else
							{
								$active['anon']++;
							}
						}
						else
						{
							$active['members']++;
							$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'p_start' => $p_start, 'p_title' => $p_title, 'p_end' => $p_end, 'seo' => $result['seo_name'] );
						}
					}
				}
			}
						
			$active['active_users_title']   = sprintf( $this->lang->words['active_users_title']  , ($active['members'] + $active['guests'] + $active['anon'] ) );
			$active['active_users_detail']  = sprintf( $this->lang->words['active_users_detail'] , $active['members'],$active['guests'],$active['anon'] );
			$active['active_users_members'] = sprintf( $this->lang->words['active_users_members'], $active['members'] );
			
			return $active;
		}
	}
	
	/**
	 * Generate the moderation panel
	 * $skcusgej, still. After all this time	 
	 *
	 * @access	public
	 * @return	array
	 **/
	public function _generateModerationPanel()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mod_links = array();
		$_got_data = 0;
		$actions   = array( 'move_topic', 'close_topic', 'open_topic', 'delete_topic', 'edit_topic', 'pin_topic', 'unpin_topic', 'merge_topic', 'unsubbit' );
		
		if ( ! $this->memberData['member_id'] )
		{
			return;
		}
		
		if ( $this->memberData['member_id'] == $this->topic['starter_id'] )
		{
			$_got_data = 1;
		}
		
		if ( $this->memberData['g_is_supmod'] == 1 )
		{
			$_got_data = 1;
		}
		
		if ( isset( $this->moderator['mid'] ) AND $this->moderator['mid'] != "" )
		{
			$_got_data = 1;
		}
		
		if ( $_got_data == 0 )
		{
		   	return;
		}

		//-----------------------------------------
		// Add on approve/unapprove topic fing
		//-----------------------------------------
		
		if ( $this->registry->class_forums->canQueuePosts( $this->forum['id'] ) ) 
		{
			if ( $this->topic['approved'] != 1 )
			{
				$mod_links[] = array( 'option' => 'topic_approve',
									  'value'  => $this->lang->words['cpt_approvet'] );
			}
			else
			{
				$mod_links[] = array( 'option' => 'topic_unapprove',
									  'value'  => $this->lang->words['cpt_unapprovet'] );
			}
		}
		
		foreach( $actions as $key )
		{
			if( is_array($this->_addModLink($key)) )
			{
				if ($this->memberData['g_is_supmod'])
				{
					$mod_links[] = $this->_addModLink($key);
				}
				elseif ( isset($this->moderator['mid']) AND $this->moderator['mid'])
				{
					if ($key == 'merge_topic' or $key == 'split_topic')
					{
						if ($this->moderator['split_merge'] == 1)
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
					else if ( isset($this->moderator[ $key ]) AND $this->moderator[ $key ] )
					{
						$mod_links[] = $this->_addModLink($key);
					}
					
					// What if member is a mod, but doesn't have these perms as a mod?
					
					elseif ($key == 'open_topic' or $key == 'close_topic')
					{
						if ($this->memberData['g_open_close_posts'])
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
					elseif ($key == 'delete_topic')
					{
						if ($this->memberData['g_delete_own_topics'])
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
				}
				elseif ($key == 'open_topic' or $key == 'close_topic')
				{
					if ($this->memberData['g_open_close_posts'])
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
				elseif ($key == 'delete_topic')
				{
					if ($this->memberData['g_delete_own_topics'])
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
			}
		}
		
		if ($this->memberData['g_is_supmod'] == 1)
		{
			$mod_links[] = $this->_addModLink('topic_history');
		}

		return $mod_links;
	}
	
	/**
	 * Append mod links
	 *
	 * @access	public
	 * @param	string	$key
	 * @return	array 	Options
	 **/
	public function _addModLink( $key="" )
	{
		if ($key == "") return "";
		
		if ($this->topic['state'] == 'open'   and $key == 'open_topic') return "";
		if ($this->topic['state'] == 'closed' and $key == 'close_topic') return "";
		if ($this->topic['state'] == 'moved'  and ($key == 'close_topic' or $key == 'move_topic')) return "";
		if ($this->topic['pinned'] == 1 and $key == 'pin_topic')   return "";
		if ($this->topic['pinned'] == 0 and $key == 'unpin_topic') return "";
		
		return array( 'option' => $this->mod_action[ strtoupper($key) ],
					  'value'  => $this->lang->words[ strtoupper($key) ] );
	}
	
	/**
	 * Get Linear Topic Data
	 *
	 * @access	public
	 * @return	array
	 **/
	public function _getTopicDataLinear()
	{
		//-----------------------------------------
		// Grab the posts we'll need
		//-----------------------------------------

		$first = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;

		$pc_join = array();

		if ( $this->settings['post_order_column'] != 'post_date' )
		{
			$this->settings['post_order_column'] = 'pid';
		}

		if ( $this->settings['post_order_sort'] != 'desc' )
		{
			$this->settings['post_order_sort'] = 'asc';
		}

		if ( $this->settings['au_cutoff'] == "" )
		{
			$this->settings['au_cutoff'] = 15;
		}

		//-----------------------------------------
		// Moderator?
		//-----------------------------------------

		$queued_query_bit = ' and queued=0';

		if ( $this->registry->class_forums->canQueuePosts($this->topic['forum_id']) )
		{
			$queued_query_bit = '';

			if ( $this->request['modfilter'] AND  $this->request['modfilter'] == 'invisible_posts' )
			{
				$queued_query_bit = ' and queued=1';
			}
		}

		//-----------------------------------------
		// Using "new" mode?
		//-----------------------------------------

		if ( $this->topic_view_mode == 'linearplus' and $this->topic['topic_firstpost'] )
		{
			$this->topic['new_mode_start'] = $first + 1;

			if ( $first )
			{
				$this->topic['new_mode_start']--;
			}

			if ( $first + $this->settings['display_max_posts'] > ( $this->topic['posts'] + 1 ) )
			{
				$this->topic['new_mode_end'] = $this->topic['posts'];
			}
			else
			{
				$this->topic['new_mode_end'] = $first + ($this->settings['display_max_posts'] - 1);
			}

			if ( $first )
			{
				$this->pids = array( 0 => $this->topic['topic_firstpost'] );
			}

			//-----------------------------------------
			// Get PIDS of this page/topic
			//-----------------------------------------

			$this->DB->build( array(
									'select' => 'pid,topic_id',
									'from'   => 'posts',
									'where'  => 'topic_id='.$this->topic['tid']. $queued_query_bit,
									'order'  => 'pid asc',
									'limit'  => array( $first, $this->settings['display_max_posts'] )
						)	);

			$this->DB->execute();

			while( $p = $this->DB->fetch() )
			{
				$this->pids[ $p['pid'] ] = $p['pid'];
			}
		}
		else
		{
			//-----------------------------------------
			// Run query
			//-----------------------------------------

			$this->topic_view_mode = 'linear';

			# We don't need * but if we don't use it, it won't use the correct index
			$this->DB->build( array(
									'select' => 'pid',
									'from'   => 'posts',
									'where'  => 'topic_id='.$this->topic['tid']. $queued_query_bit,
									'order'  => $this->settings['post_order_column'].' '.$this->settings['post_order_sort'],
									'limit'  => array( $first, $this->settings['display_max_posts'] )
						)	);

			$this->DB->execute();

			while( $p = $this->DB->fetch() )
			{
				$this->pids[ $p['pid'] ] = $p['pid'];
			}
		}

		//-----------------------------------------
		// Do we have any PIDS?
		//-----------------------------------------

		if ( ! count( $this->pids ) )
		{
			if ( $first )
			{
				//-----------------------------------------
				// Add dummy PID, AUTO FIX
				// will catch this below...
				//-----------------------------------------

				$this->pids[] = 0;
			}

			if ( $this->request['modfilter'] == 'invisible_posts' )
			{
				$this->pids[] = 0;
			}
		}

		//-----------------------------------------
		// Attachment PIDS
		//-----------------------------------------

		$this->attach_pids = $this->pids;

		//-----------------------------------------
		// Fail safe
		//-----------------------------------------

		if ( ! is_array( $this->pids ) or ! count( $this->pids ) )
		{
			$this->pids = array( 0 => 0 );
		}
		
		//-----------------------------------------
		// Joins
		//-----------------------------------------		
		
		$_post_joins = array(
								array( 
										'select' => 'm.member_id as mid,m.name,m.member_group_id,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title,m.hide_email, m.warn_level, m.warn_lastwarn, m.members_display_name, m.members_seo_name, m.has_gallery, m.has_blog, m.members_bitoptions',
										'from'   => array( 'members' => 'm' ),
										'where'  => 'm.member_id=p.author_id',
										'type'   => 'left'
									),
								array( 
										'select' => 'pp.*',
										'from'   => array( 'profile_portal' => 'pp' ),
										'where'  => 'm.member_id=pp.pp_member_id',
										'type'   => 'left'
								),
								array( 
										'select' => 'g.g_access_cp',
										'from'   => array( 'groups' => 'g' ),
										'where'  => 'g.g_id=m.member_group_id',
										'type'   => 'left'
								)
							);
							
		/* Add custom fields join? */
		if( $this->settings['custom_profile_topic'] == 1 )
		{
			$_post_joins[] = array( 
									'select' => 'pc.*',
									'from'   => array( 'pfields_content' => 'pc' ),
									'where'  => 'pc.member_id=p.author_id',
									'type'   => 'left'
								);
		}							
		
		/* Reputation system enabled? */
		if( $this->settings['reputation_enabled'] )
		{
			/* Add the join to figure out if the user has already rated the post */
			$_post_joins[] = $this->registry->repCache->getUserHasRatedJoin( 'pid', 'p.pid', 'forums' );
			
			/* Add the join to figure out the total ratings for each post */
			if( $this->settings['reputation_show_content'] )
			{
				$_post_joins[] = $this->registry->repCache->getTotalRatingJoin( 'pid', 'p.pid', 'forums' );
			}
		}
		
		/* Cache? */
		if ( IPSContentCache::isEnabled() )
		{
			if ( IPSContentCache::fetchSettingValue('post') )
			{
				$_post_joins[] = IPSContentCache::join( 'post', 'p.pid' );
			}
			
			if ( IPSContentCache::fetchSettingValue('sig') )
			{
				$_post_joins[] = IPSContentCache::join( 'sig' , 'm.member_id', 'ccb', 'left', 'ccb.cache_content as cache_content_sig, ccb.cache_updated as cache_updated_sig' );
			}
		}
		
		/* Ignored Users */
		$ignored_users = array();
		
		foreach( $this->member->ignored_users as $_i )
		{
			if( $_i['ignore_topics'] )
			{
				$ignored_users[] = $_i['ignore_ignore_id'];
			}
		}
		
		//-----------------------------------------
		// Get posts
		//-----------------------------------------

		$this->DB->build( array( 
								'select'   => 'p.*',
								'from'	   => array( 'posts' => 'p' ),
								'where'	   => "p.pid IN(" . implode( ',', $this->pids ) . ")",
								'order'	   => $this->settings['post_order_column'] . ' ' . $this->settings['post_order_sort'],
								'add_join' => $_post_joins
						)	);

		$oq = $this->DB->execute();

		if ( ! $this->DB->getTotalRows() )
		{
			if ($first >= $this->settings['display_max_posts'])
			{
				//-----------------------------------------
				// AUTO FIX: Get the correct number of replies...
				//-----------------------------------------

				$this->DB->build( array(
										'select' => 'COUNT(*) as pcount',
										'from'   => 'posts',
										'where'  => "topic_id=".$this->topic['tid']." and queued !=1"
							)	);

				$newq   = $this->DB->execute();

				$pcount = $this->DB->fetch($newq);

				$pcount['pcount'] = $pcount['pcount'] > 0 ? $pcount['pcount'] - 1 : 0;

				//-----------------------------------------
				// Update the post table...
				//-----------------------------------------

				if ($pcount['pcount'] > 1)
				{
					$this->DB->update( 'topics', array( 'posts' => $pcount['pcount'] ), "tid=".$this->topic['tid'] );

				}

				$this->registry->output->silentRedirect($this->settings['base_url']."showtopic={$this->topic['tid']}&view=getlastpost");
			}
		}

		//-----------------------------------------
		// Render the page top
		//-----------------------------------------

		$this->topic['go_new'] = isset($this->topic['go_new']) ? $this->topic['go_new'] : '';

		//-----------------------------------------
		// Format and print out the topic list
		//-----------------------------------------

		$post_data = array();

		while ( $row = $this->DB->fetch( $oq ) )
		{
			$row['member_id']	= $row['mid'];

			$return = $this->parsePostRow( $row );

			$poster = $return['poster'];
			$row	= $return['row'];
			$poster['member_id'] = $poster['mid'];
			
			/* Reputation */
			if( $this->settings['reputation_enabled'] )
			{
				$row['pp_reputation_points'] = $row['pp_reputation_points'] ? $row['pp_reputation_points'] : 0;
				$row['has_given_rep']        = $row['has_given_rep'] ? $row['has_given_rep'] : 0;
				$row['rep_points']           = $row['rep_points'] ? $row['rep_points'] : 0;
			}

			$post_data[ $row['pid'] ] = array( 'post' => $row, 'author' => $poster );

			//-----------------------------------------
			// Are we giving this bloke a good ignoring?
			//-----------------------------------------
			if( isset( $ignored_users ) && is_array( $ignored_users ) && count( $ignored_users ) )
			{
				if( in_array( $poster['member_id'], $ignored_users ) )
				{
					if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$poster['member_group_id'].',' ) )
					{
						$post_data[ $row['pid'] ]['post']['_ignored'] = 1;
						continue;
					}
				}
			}
			
			//-----------------------------------------
			// What about rep, are we ignoring?
			//-----------------------------------------
			
			$this->memberData['_members_cache']['rep_filter'] = isset( $this->memberData['_members_cache']['rep_filter'] ) ? $this->memberData['_members_cache']['rep_filter']: false;
			
			if( $this->settings['reputation_enabled'] )
			{
				if( !( $this->settings['reputation_protected_groups'] && 
					in_array( $this->memberData['member_group_id'], explode( ',', $this->settings['reputation_protected_groups'] ) ) ) &&
				 	$this->memberData['_members_cache']['rep_filter'] != '*' 
				)
				{
					if( $this->settings['reputation_show_content'] && $post_data[ $row['pid'] ]['post']['rep_points'] < $this->memberData['_members_cache']['rep_filter'] )
					{
						$post_data[ $row['pid'] ]['post']['_repignored'] = 1;
					}
				}
			}

			//-----------------------------------------
			// Show end first post
			//-----------------------------------------

			if ( $this->topic_view_mode == 'linearplus' and $this->first_printed == 0 and $row['pid'] == $this->topic['topic_firstpost'] and $this->topic['posts'] > 0)
			{
				$post_data[ $row['pid'] ]['post']['_end_first_post'] = 1;
			}
			
			$post_data[ $row['pid'] ]['post']['rep_points'] = $post_data[ $row['pid'] ]['post']['rep_points'] ? $post_data[ $row['pid'] ]['post']['rep_points'] : 0;			
		}

		//-----------------------------------------
		// Print the footer
		//-----------------------------------------

		return $post_data;	
	}

	/**
	 * Topic set up ya'll
	 *
	 * @access	public
	 * @return	void
	 **/
	public function topicSetUp()
	{
		//-----------------------------------------
		// Memory...
		//-----------------------------------------
		
		$_before = IPSDebug::getMemoryDebugFlag();
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['start']	= ! empty( $this->request['start'] )	? intval( $this->request['start'] )	: '';
		$this->request['st']	= ! empty( $this->request['st'] )		? intval( $this->request['st'] )	: '';
		
		//-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_boards', 'public_topic' ) );
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		//-----------------------------------------
 		// Get all the member groups and
 		// member title info
 		//-----------------------------------------
	   
		if ( ! is_array( $this->cache->getCache('ranks') ) )
		{
			$this->cache->rebuildCache( 'ranks', 'global' );
		}
		
		//-----------------------------------------
		// Are we actually a moderator for this forum?
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$moderator = $this->memberData['forumsModeratorData'];
			
			if ( !isset($moderator[ $this->forum['id'] ]) OR !is_array( $moderator[ $this->forum['id'] ] ) )
			{
				$this->memberData['is_mod'] = 0;
			}
		}
		
		$this->settings['_base_url'] = $this->settings['base_url'];
		$this->forum['FORUM_JUMP']   = $this->registry->getClass('class_forums')->buildForumJump();
		$this->first				 = intval( $this->request['st'] ) > 0 ? intval( $this->request['st'] ) : 0;
		$this->request['view']	     = ! empty( $this->request['view'] ) ? $this->request['view'] : NULL ;
		
		//-----------------------------------------
		// Check viewing permissions, private forums,
		// password forums, etc
		//-----------------------------------------

		if ( ( ! $this->memberData['g_other_topics'] ) AND ( $this->topic['starter_id'] != $this->memberData['member_id'] ) )
		{
			$this->registry->output->showError( 'topics_not_yours', 10359 );
		}
		else if( (!$this->forum['can_view_others'] AND !$this->memberData['is_mod'] ) AND ( $this->topic['starter_id'] != $this->memberData['member_id'] ) )
		{
			$this->registry->output->showError( 'topics_not_yours2', 10360 );
		}
		
		//-----------------------------------------
		// Update the topic views counter
		//-----------------------------------------
		
		if ( ! $this->request['view'] AND $this->topic['state'] != 'link' )
		{
			if ( $this->settings['update_topic_views_immediately'] )
			{
				$this->DB->update( 'topics', 'views=views+1', "tid=".$this->topic['tid'], true, true );
			}
			else
			{
				$this->DB->insert( 'topic_views', array( 'views_tid' => $this->topic['tid'] ), true );
			}
		}
		
		//-----------------------------------------
		// Need to update this topic?
		//-----------------------------------------
		
		if ( $this->topic['state'] == 'open' )
		{
			if( !$this->topic['topic_open_time'] OR $this->topic['topic_open_time'] < $this->topic['topic_close_time'] )
			{
				if ( $this->topic['topic_close_time'] AND ( $this->topic['topic_close_time'] <= time() AND ( time() >= $this->topic['topic_open_time'] OR !$this->topic['topic_open_time'] ) ) )
				{
					$this->topic['state'] = 'closed';
					
					$this->DB->update( 'topics', array( 'state' => 'closed' ), 'tid='.$this->topic['tid'], true );
				}
			}
			else if( $this->topic['topic_open_time'] OR $this->topic['topic_open_time'] > $this->topic['topic_close_time'] )
			{
				if ( $this->topic['topic_close_time'] AND ( $this->topic['topic_close_time'] <= time() AND time() <= $this->topic['topic_open_time'] ) )
				{
					$this->topic['state'] = 'closed';
					
					$this->DB->update( 'topics', array( 'state' => 'closed' ), 'tid='.$this->topic['tid'], true );
				}
			}				
		}
		else if ( $this->topic['state'] == 'closed' )
		{
			if( !$this->topic['topic_close_time'] OR $this->topic['topic_close_time'] < $this->topic['topic_open_time'] )
			{
				if ( $this->topic['topic_open_time'] AND ( $this->topic['topic_open_time'] <= time() AND ( time() >= $this->topic['topic_close_time'] OR !$this->topic['topic_close_time'] ) ) )
				{
					$this->topic['state'] = 'open';
					
					$this->DB->update( 'topics', array( 'state' => 'open' ), 'tid='.$this->topic['tid'], true );
				}
			}
			else if( $this->topic['topic_close_time'] OR $this->topic['topic_close_time'] > $this->topic['topic_open_time'] )
			{

				if ( $this->topic['topic_open_time'] AND ( $this->topic['topic_open_time'] <= time() AND time() <= $this->topic['topic_close_time'] ) )
				{
					$this->topic['state'] = 'open';
					
					$this->DB->update( 'topics', array( 'state' => 'open' ), 'tid='.$this->topic['tid'], true );
				}
			}				
		}
		
		//-----------------------------------------
		// Current topic rating value
		//-----------------------------------------
		
		$this->topic['_rate_show']  = 0;
		$this->topic['_rate_int']   = 0;
		$this->topic['_rate_img']   = '';
		
		if ( $this->topic['state'] != 'open' )
		{
			$this->topic['_allow_rate'] = 0;
		}
		else
		{
			$this->topic['_allow_rate'] = $this->can_rate;
		}
		
		if ( $this->forum['forum_allow_rating'] )
		{
			$rating = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$this->topic['tid']} and rating_member_id=".$this->memberData['member_id'] ) );
			
			if ( $rating['rating_value'] AND $this->memberData['g_topic_rate_setting'] != 2 )
			{
				$this->topic['_allow_rate'] = 0;
			}
			
			$this->topic['_rate_id']	   = 0;
			$this->topic['_rating_value']  = $rating['rating_value'] ? $rating['rating_value'] : -1;
			
			if ( $this->topic['topic_rating_total'] )
			{
				$this->topic['_rate_int'] = round( $this->topic['topic_rating_total'] / $this->topic['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $this->topic['topic_rating_hits'] >= $this->settings['topic_rating_needed'] ) AND ( $this->topic['_rate_int'] ) )
			{
				$this->topic['_rate_id']   = $this->topic['_rate_int'];
				$this->topic['_rate_show'] = 1;
			}
		}
		else
		{
			$this->topic['_allow_rate'] = 0;
		}		
		
		//-----------------------------------------
		// Update the item marker
		//-----------------------------------------
		
		if ( ! $this->request['view'] )
		{
			$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $this->forum['id'], 'itemID' => $this->topic['tid'] ) );
		}
		
		//-----------------------------------------
		// If this forum is a link, then 
		// redirect them to the new location
		//-----------------------------------------
		
		if ( $this->topic['state'] == 'link' )
		{
			$f_stuff = explode("&", $this->topic['moved_to']);
			$this->registry->output->redirectScreen( $this->lang->words['topic_moved'], $this->settings['base_url'] . "showtopic={$f_stuff[0]}" );
		}
		
		//-----------------------------------------
		// If this is a sub forum, we need to get
		// the cat details, and parent details
		//-----------------------------------------
		
	   	$this->nav = $this->registry->class_forums->forumsBreadcrumbNav( $this->forum['id'] );
		
		//-----------------------------------------
		// Are we a moderator?
		//-----------------------------------------
		
		if ( ($this->memberData['member_id']) and ($this->memberData['g_is_supmod'] != 1) )
		{
			$other_mgroups = array();
			
			if( $this->memberData['mgroup_others'] )
			{
				$other_mgroups = explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
			}
		
			$other_mgroups[] = $this->memberData['member_group_id'];
			
			$member_group_ids = implode( ",", $other_mgroups );
			
			$this->moderator = $this->DB->buildAndFetch( array( 
																'select' => '*',
																'from'	=> 'moderators',
																'where'	=> "forum_id LIKE '%,{$this->forum['id']},%' AND (member_id={$this->memberData['member_id']} OR (is_group=1 AND group_id IN({$member_group_ids})))"
														)	);
		}
		
		//-----------------------------------------
		// Hi! Light?
		//-----------------------------------------
		
		$hl = (isset( $this->request['hl'] ) AND $this->request['hl']) ? '&amp;hl='.$this->request['hl'] : '';
		
		//-----------------------------------------
		// If we can see queued topics, add count
		//-----------------------------------------
		
		if ( $this->registry->class_forums->canQueuePosts($this->forum['id']) )
		{
			if( isset( $this->request['modfilter'] ) AND $this->request['modfilter'] == 'invisible_posts' )
			{
				$this->topic['posts'] = intval( $this->topic['topic_queuedposts'] );
			}
			else
			{
				$this->topic['posts'] += intval( $this->topic['topic_queuedposts'] );
			}
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------
		
		$this->topic['SHOW_PAGES']
			= $this->registry->output->generatePagination( array( 
																	'totalItems'		=> ($this->topic['posts']+1),
																	'itemsPerPage'		=> $this->settings['display_max_posts'],
																	'currentStartValue'	=> $this->first,
																	'seoTitle'			=> $this->topic['title_seo'],
																	'seoTemplate'		=> 'showtopic',
 																	'baseUrl'			=> "showtopic=".$this->topic['tid'].$hl ) );
								   
		if ( ($this->topic['posts'] + 1) > $this->settings['display_max_posts'])
		{
		//	$this->topic['go_new'] = $this->registry->output->getTemplate('topic')->golastpost_link($this->forum['id'], $this->topic['tid'] );
		}
								   
		
		//-----------------------------------------
		// Fix up some of the words
		//-----------------------------------------
		
		$this->topic['TOPIC_START_DATE'] = $this->registry->class_localization->getDate( $this->topic['start_date'], 'LONG' );
		
		$this->lang->words['topic_stats'] = str_replace( "<#START#>", $this->topic['TOPIC_START_DATE'], $this->lang->words['topic_stats']);
		$this->lang->words['topic_stats'] = str_replace( "<#POSTS#>", $this->topic['posts']		   , $this->lang->words['topic_stats']);
		
		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------
		
		$this->qpids = IPSCookie::get('mqtids');
		
		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------
		
		$this->request['selectedpids'] = ! empty( $this->request['selectedpids'] ) ? $this->request['selectedpids'] : IPSCookie::get('modpids');
		$this->request['selectedpidcount'] = 0 ;
		
		IPSCookie::set('modpids', '', 0);
		
		IPSDebug::setMemoryDebugFlag( "TOPIC: topics.php::topicSetUp", $_before );
	}
	
}