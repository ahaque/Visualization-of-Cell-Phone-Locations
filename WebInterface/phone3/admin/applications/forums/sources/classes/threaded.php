<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Threaded mode functions
 * Last Updated: $Date: 2009-03-30 11:13:39 -0400 (Mon, 30 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Revision: 4354 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class threadedModeLibrary
{
	/**
	 * Topics library
	 *
	 * @access	public
	 * @var		object
	 */
	public $topics;
	
	/**
	 * Structured post ids
	 *
	 * @access	public
	 * @var		array
	 */
	public $structured_pids		= array();

	/**
	 * Post cache
	 *
	 * @access	public
	 * @var		array
	 */
	public $post_cache			= array();

	/**
	 * Used post ids
	 *
	 * @access	public
	 * @var		array
	 */
	public $used_post_ids		= array();

	/**
	 * Threaded posts
	 *
	 * @access	public
	 * @var		array
	 */
	public $_threaded_posts		= array();

	/**
	 * Last id we've seen
	 *
	 * @access	public
	 * @var		int
	 */
	public $last_id				= 0;
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
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
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @param	object		Reference to topics lib
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $topics )
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
		
		$this->topics	=& $topics;
	}

	/**
	 * Get Threaded Topic Data
	 *
	 * @access	public
	 * @return	array
	 **/	
	public function _getTopicDataThreaded()
	{
		//-----------------------------------------
		// Grab the posts we'll need
		//-----------------------------------------
		
		$pc_join	= array();
		
		$first		= intval( $this->request['start'] );
		$last		= $this->settings['threaded_per_page'] ? $this->settings['threaded_per_page'] : 250;
				
		//-----------------------------------------
		// GET meh pids
		//-----------------------------------------
		
		if ( $first > 0 )
		{
			// we're on a page, make sure init val is there
			
			$this->topics->pids[0]			= $this->topics->topic['topic_firstpost'];
			$this->structured_pids[ 0 ][]	= $this->topics->topic['topic_firstpost'];
		}
		
		$this->DB->build( array(
								'select' => 'pid, post_parent',
								'from'   => 'posts',
								'where'  => 'topic_id=' . $this->topics->topic['tid'] . ' and queued != 1',
								'order'  => 'pid',
								'limit'  => array( $first, $last )
						)	);
							
		$this->DB->execute();
		
		while( $p = $this->DB->fetch() )
		{
			$this->topics->pids[] = $p['pid'];
			
			// Force to be children of 'root' post
			
			if ( ! $p['post_parent'] and $p['pid'] != $this->topics->topic['topic_firstpost'] )
			{
				$p['post_parent'] = $this->topics->topic['topic_firstpost'];
			}
			
			$this->structured_pids[ $p['post_parent'] ][] = $p['pid'];
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
		// Get post bodah
		//-----------------------------------------
		
		if ( count( $this->topics->pids ) )
		{
			$this->DB->build( array(
									'select' => 'pid, post, author_id, author_name, post_date, post_title, post_parent, topic_id, icon_id',
									'from'   => 'posts',
									'where'  => 'pid IN(' . implode( ',', $this->topics->pids ) . ')',
									'order'  => 'pid',
						)	);
								 
			$this->DB->execute();
			
			while( $p = $this->DB->fetch() )
			{
				if ( ! $p['post_parent'] and $p['pid'] != $this->topics->topic['topic_firstpost'] )
				{
					$p['post_parent'] = $this->topics->topic['topic_firstpost'];
				}
				
				$this->post_cache[ $p['pid'] ] = $p;
				
				$this->last_id = $p['pid'];
			}
		}
		
		//-----------------------------------------
		// Force root in cache
		//-----------------------------------------
		
		$this->post_cache[0] = array( 'id' => 1 );
		
		$this->post_cache[ $this->topics->topic['topic_firstpost'] ]['post_title']  = $this->topics->topic['title'];
		
		//-----------------------------------------
		// Are we viewing Posts?
		//-----------------------------------------
	
		$post_id = intval( $this->request['pid'] );
		
		if ( $post_id && ! in_array( $post_id, $this->topics->pids ) )
		{
			$this->registry->output->showError( 'topics_post_not_in_topic', 10358, true );
		}
				
		$postid_array = array( 1 => $post_id );
		
		if ( $post_id and $post_id != $this->topics->topic['topic_firstpost'] )
		{
			$parents = $this->_threadedPostGetParents( $post_id );
			
			if ( count($parents) )
			{
				foreach( $parents as $pid )
				{
					if ( $pid != $this->topics->topic['topic_firstpost'] )
					{
						$postid_array[] = $pid;
					}
				}
			}
		}
		
		/* Join Queries */
		$_post_joins = array(
								array( 
										'select' => 'm.member_id as mid,m.name,m.member_group_id,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title,m.hide_email, m.warn_level, m.warn_lastwarn, m.members_display_name, m.members_seo_name, m.has_gallery, m.has_blog, m.members_bitoptions',
										'from'	 => array( 'members' => 'm' ),
										'where'	 => 'm.member_id=p.author_id',
										'type'	 => 'left'
									),
								array( 
										'select' => 'pp.*',
										'from'	 => array( 'profile_portal' => 'pp' ),
										'where'	 => 'm.member_id=pp.pp_member_id',
										'type'	 => 'left'
									)
							);
		
		
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
		
		if( $this->settings['custom_profile_topic'] == 1 )
		{
			$_post_joins[] = array( 
								'select' => 'pc.*',
								'from'   => array( 'pfields_content' => 'pc' ),
								'where'  => 'pc.member_id=p.author_id',
								'type'   => 'left'
							);
		}				
		
		if( count( $postid_array ) )
		{			
			//-----------------------------------------
			// Get root post and children of clicked
			//-----------------------------------------
			
			$this->used_post_ids = ',' . implode( ",", $postid_array ) . ',';
			
			$postid_array[0] = $this->topics->topic['topic_firstpost'];
			
			$this->DB->build( array( 
									'select'   => 'p.*',
									'from'     => array( 'posts' => 'p' ),
									'where'    => "p.pid IN(" . implode( ',', $postid_array ) . ")",
									'order'    => 'pid asc',
									'add_join' => $_post_joins
						)	);
		}
		else
		{
			//-----------------------------------------
			// Just get root
			//-----------------------------------------
			
			$this->DB->build( array( 
									'select'   => 'p.*',
									'from'     => array( 'posts' => 'p' ),
									'where'    => "p.pid=" . $this->topics->topic['topic_firstpost'],
									'order'    => 'pid asc',
									'add_join' => $_post_joins
							)	);
		}
		
		//-----------------------------------------
		// Attachment PIDS
		//-----------------------------------------
		
		$this->topics->attach_pids = $postid_array;
		
		//-----------------------------------------
		// Render the original post
		//-----------------------------------------
												 					 
		$outer = $this->DB->execute();
		
		//-----------------------------------------
		// Format and print out the topic list
		//-----------------------------------------
		
		$post_data = array();
		$num_rows  = $this->DB->getTotalRows( $outer );
		
		while ( $row = $this->DB->fetch( $outer ) )
		{
			$return = $this->topics->parsePostRow( $row );
			
			$poster = $return['poster'];
			$row	= $return['row'];
			
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
		
			if ( ! $this->printed and $num_rows > 1 )
			{
				$post_data[ $row['pid'] ]['post']['_end_first_post'] = 1;
				$this->printed = 1;
			}
		}
		
		//-----------------------------------------
		// Sort out pagination
		//-----------------------------------------
		
		$total_replies = $this->topics->topic['posts'];
		$show_replies  = count( $this->structured_pids ) - 1;
		
		$this->topics->topic['SHOW_PAGES']		= '';
		$this->topics->topic['threaded_pages']	= $this->registry->output->generatePagination( array( 
																							'totalItems'		=> $total_replies,
																	  						'itemsPerPage'		=> $last,
																							'currentStartValue'	=> $this->request['start'],
																							'baseUrl'			=> "showtopic=" . $this->topics->topic['tid'],
																							'startValueKey'		=> 'start'
																					)  	);
		
		//-----------------------------------------
		// START GETTING THE OUTLINE LIST
		//-----------------------------------------
				
		$this->_threaded_posts[ 0 ]['child'] = $this->_threadedLoopGetChildren();
		$this->topics->topic['_threaded_posts'] = $this->_buildThreadedOutput( $this->_threaded_posts[ 0 ] );
		
		return $post_data;
	}
	
	/**
	 * Actually builds the HTML for threaded view
	 *
	 * @access	public
	 * @param	array 	$root	The root we're iterating
	 * @param	string	$html	The current HTML in this loop
	 * @return	string	HTML
	 */
	public function _buildThreadedOutput( $root, $html='' )
	{
		$child = '';
		
		if( is_array( $root['child'] ) )
		{
			if( count( $root['child'] ) )
			{
				foreach( $root['child'] as $pid => $post )
				{
					$child .= $this->_buildThreadedOutput( $post );
				}
			}
		}
		
		$html .= $this->registry->output->getTemplate('topic')->build_threaded( $root, $child );
		
		return $html;
	}
	
	/**
	 * Render kiddies
	 *
	 * @access	public
	 * @param	integer  $root_id
	 * @param	array    $posts
	 * @param	integer  $dguide
	 * @return	array
	 **/
	public function _threadedLoopGetChildren($root_id=0, $posts=array() ,$dguide=-1)
	{
		$dguide++;
		
		if ( isset( $this->structured_pids[ $root_id ] ) && is_array( $this->structured_pids[ $root_id ] ) )
		{
			if ( count( $this->structured_pids[ $root_id ] ) )
			{
				foreach( $this->structured_pids[ $root_id ] as $pid )
				{
					$posts[ $pid ] = $this->_threadedRenderListRow( $this->post_cache[ $pid ], $dguide );
					$posts[$pid]['child'] = $this->_threadedLoopGetChildren( $pid, isset( $posts[ $root_id ] ) ? $posts[ $root_id ] : array(), $dguide );
				}
			}
		}
		
		return $posts;
	}
	
	/**
	 * Builds an array of output for used in threaded view
	 *
	 * @access	public
	 * @param	array	$post
	 * @param	integer	$depth
	 * @return	array
	 **/
	public function _threadedRenderListRow( $post, $depth )
	{
		$post['depthguide'] = "";
		
		$this->settings[ 'post_showtext_notitle'] =  1 ;
		
		for( $i = 1 ; $i < $depth; $i++ )
		{
			$post['depthguide'] .= $this->depth_guide[ $i ];
		}
		
		// Last child?
		
		if ( $depth > 0 )
		{
			$last_id = count($this->structured_pids[ $post['post_parent'] ]) - 1;
			
			if ( $this->structured_pids[ $post['post_parent'] ][$last_id] == $post['pid'] )
			{
				$this->depth_guide[ $depth ] = '<img src="'.$this->settings['img_url'].'/spacer.gif" alt="" width="20" height="16">';
				$post['depthguide'] .= "<img src='".$this->settings['img_url']."/to_post_no_children.gif' alt='-' />";
			}
			else
			{
				$this->depth_guide[ $depth ] = '<img src="'.$this->settings['img_url'].'/to_down_pipe.gif" alt="|" />';
				$post['depthguide'] .= "<img src='".$this->settings['img_url']."/to_post_with_children.gif' alt='-' />";
			}
		}
		
		if ( ! $post['post_title'] )
		{
			if ( $this->settings['post_showtext_notitle'] )
			{
				$post_text = IPSText::getTextClass( 'bbcode' )->stripAllTags( strip_tags( IPSText::br2nl( $post['post'] ) ) );

				if ( IPSText::mbstrlen($post_text) > 50 )
				{
					$post['post_title'] = IPSText::truncate( $post_text, 50 ).'...';
				}
				else
				{
					$post['post_title'] = $post_text;
				}
				
				if ( ! trim($post['post_title']) )
				{
					$post['post_title'] = $this->lang->words['post_title_re'] . $this->topics->topic['title'];
				}
			}
			else
			{
				$post['post_title'] = $this->lang->words['post_title_re'] . $this->topics->topic['title'];
			}
		}
		
		$post['linked_name'] = IPSLib::makeProfileLink( $post['author_name'], $post['author_id'] );
		
		$post['formatted_date'] = $this->registry->class_localization->getDate( $post['post_date'], 'LONG' );
		
		$post['new_post'] = 't_threaded_read';
		
		if ( $post['post_date'] > $this->topics->last_read_tid )
		{
			$post['new_post'] = 't_threaded_read';
		}
				
		if ( strstr( $this->used_post_ids, ','.$post['pid'].',' ) )
		{
			$post['_show_highlight'] = 1;
		}
		
		return $post;
	}
	
	/**
	 * Get parents
	 *
	 * @access	private
	 * @param	integer	$root_id
	 * @param	array	$ids
	 * @return	array
	 **/
	private function _threadedPostGetParents($root_id, $ids=array())
	{
		if( in_array( $root_id, $ids ) )  
		{  
			 $cnt = 0;  
			   
			foreach( $ids as $id )  
			{  
				if( $id == $root_id )  
				{  
					$cnt++;  

					if( $cnt > 1 )  
					{  
						return $ids;  
					}  
				}  
			} 
		}

		if ( $this->post_cache[ $root_id ]['post_parent'] )
		{
			$ids[] = $this->post_cache[ $root_id ]['post_parent'];
			
			$ids = $this->_threadedPostGetParents( $this->post_cache[ $root_id ]['post_parent'], $ids );
		}
		
		return $ids;
	}
	
	/**
	 * Get children
	 *
	 * @access	private
	 * @param	integer	$root_id
	 * @param	array	$ids
	 * @return	array
	 **/
	private function _threadedPostGetChildren($root_id, $ids=array())
	{
		if ( is_array($this->structured_pids[ $root_id ]) )
		{
			foreach( $this->structured_pids[ $root_id ] as $pid )
			{
				$ids[] = $pid;
				
				$ids = $this->_threadedPostGetChildren( $pdaid, $ids );
			}
		}
		
		return $ids;
	}
}