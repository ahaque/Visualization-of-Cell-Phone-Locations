<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Moderator actions
 * Last Updated: $Date: 2009-07-13 18:08:39 -0400 (Mon, 13 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Revision: 4876 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class digestLibrary
{
	/**
	 * Digest time
	 *
	 * @access	public
	 * @var		string		daily|weekly
	 */
	public $digest_time		= 'daily';

	/**
	 * Digest type
	 *
	 * @access	public
	 * @var		string		topic|forum
	 */
	public $digest_type		= 'topic';

	/**
	 * Midnight time
	 *
	 * @access	public
	 * @var		integer		Timestamp
	 */
	public $midnight		= 0;

	/**
	 * Last week timestamp
	 *
	 * @access	public
	 * @var		integer		Timestamp
	 */
	public $last_week		= 0;

	/**
	 * Yesterday timestamp
	 *
	 * @access	public
	 * @var		integer		Timestamp
	 */
	public $last_day		= 0;

	/**
	 * Last time timestamp
	 *
	 * @access	public
	 * @var		integer		Timestamp
	 */
	public $last_time		= 0;
	
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
		if( ! $this->registry->isClassLoaded( 'class_forums' ) )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
			$this->registry->setClass( 'class_forums', new class_forums( $registry ) );
			$this->registry->strip_invisible = 0;
			$this->registry->class_forums->forumsInit();
		}
		
		//-----------------------------------------
		// Get midnight (GMT - roughly)
		//-----------------------------------------
		
		$this->midnight = mktime( 0, 0 );
		
		//-----------------------------------------
		// Midnight today minus a weeks worth of secs
		//-----------------------------------------
		
		$this->last_week = $this->midnight - 604800;
		
		//-----------------------------------------
		// Midnight today minus a day worth of secs
		//-----------------------------------------
		
		$this->last_day  = $this->midnight - 86400;

		//-----------------------------------------
		// Get some lang bits
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_emails' ), 'core' );
	}
	
	/**
	 * Run the digest
	 *
	 * @access	public
	 * @return	void
	 */
	public function runDigest()
	{
		$this->last_time = $this->digest_time == 'daily' ? $this->last_day : $this->last_week;
		
		if ( $this->digest_type == 'topic' )
		{
			$this->_sendTopicDigest();
		}
		else
		{
			$this->_sendForumDigest();
		}
	}
	
	/**
	 * Run the digest
	 *
	 * @access	private
	 * @return	void
	 */
	private function _sendTopicDigest()
	{
		//-----------------------------------------
		// Get all posts / topics
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'tr.trid, tr.topic_id, tr.member_id as trmid',
										'from'		=> array( 'tracker' => 'tr' ),
										'where'		=> "tr.topic_track_type='{$this->digest_time}' AND t.approved=1 AND t.last_post > {$this->last_time}",
										'add_join'	=> array(
															array( 'select'	=> 'm.member_group_id, m.mgroup_others, m.org_perm_id, m.members_display_name, m.email, m.posts, m.member_id, m.email_full, m.language, m.last_activity',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=tr.member_id',
																	'type'	=> 'left'
																),
															array( 'select'	=> 't.*',
																	'from'	=> array( 'topics' => 't' ),
																	'where'	=> 't.tid=tr.topic_id',
																	'type'	=> 'left'
																),
															)
							)		);
		$topic_query = $this->DB->execute();
		
		//-----------------------------------------
		// Now, loop print and send to subscribers
		//-----------------------------------------
		
		$main_output	= "";
		$count			= 0;
		$cached			= array();
		$subject		= $this->digest_time == 'daily' ? 'digest_topic_daily' : 'digest_topic_weekly';
		
		while( $t = $this->DB->fetch( $topic_query ) )
		{
			//-----------------------------------------
			// Can access forum/topic?
			//-----------------------------------------
			
			if( !$this->registry->getClass('class_forums')->checkEmailAccess($t) )
			{
				continue;
			}
			
			//-----------------------------------------
			// Topic approved or is a mod?
			//-----------------------------------------
			
			if( !$this->registry->getClass('class_forums')->checkEmailApproved($t) )
			{
				continue;
			}
			
			//-----------------------------------------
			// Build email
			//-----------------------------------------

			$main_output	= "";
			$others_posted	= 0;
			
			if ( ! $cached[ $t['tid'] ] )
			{
				$topic_title = $t['title'];
				$forum_name  = ipsRegistry::getClass('class_forums')->allForums[ $t['forum_id'] ]['name'];
				
				//-----------------------------------------
				// Get posts...
				//-----------------------------------------
				
				$this->DB->build( array( 'select' => '*',
											  'from'   => 'posts',
											  'where'  => "topic_id={$t['tid']} AND queued=0 AND post_date > {$this->last_time}",
											  'order'  => 'post_date' ) );
				$post_query = $this->DB->execute();
				
				$post_output	= "";
				
				while( $p = $this->DB->fetch( $post_query ) )
				{
					//-----------------------------------------
					// Do we have other posters?
					//-----------------------------------------
					
					if ( $t['trmid'] != $p['author_id'] )
					{
						$others_posted = 1;
					}
					
					$post_author	= $p['author_name'];
					$post_date		= ipsRegistry::getClass( 'class_localization')->getDate( $p['post_date'], 'SHORT' );
					$post_content	= $p['post'];
					
					$post_output .= "<br />-------------------------------------------<br />"
								 .  "{$post_author} -- {$post_date}<br />{$post_content}<br /><br />";
				}
				
				//-----------------------------------------
				// Skip if there is no content...
				//-----------------------------------------
				
				if( !$post_output )
				{
					continue;
				}
								
				//-----------------------------------------
				// Process
				//-----------------------------------------
				
				$main_output .= $this->lang->words['topic_langbit'] . ": {$topic_title} (" . $this->lang->words['forum_langbit'] . ": {$forum_name})<br />"
							 .  $this->registry->getClass('output')->buildSEOUrl( $this->settings['board_url'] . '/index.php?showtopic=' . $t['tid'], 'none', $t['title_seo'], 'showtopic' ) . "<br />"
							 .  "=====================================<br />"
							 .  $post_output
							 .  "<br />=====================================<br />";
				
				$cached[ $t['tid'] ] = $main_output;
			}
			else
			{
				$others_posted = 1;
				$main_output = $cached[ $t['tid'] ];
			}
			
			if ( $others_posted )
			{
				$count++;
				
				//-----------------------------------------
				// Send email...
				//-----------------------------------------
				
				IPSText::getTextClass( 'email' )->getTemplate( $subject, $t['language'] );
				
				IPSText::getTextClass( 'email' )->buildMessage( array(
													'TOPIC_ID'        => $t['tid'],
													'FORUM_ID'        => $t['forum_id'],
													'TITLE'           => $topic_title,
													'NAME'            => $t['members_display_name'],
													'CONTENT'         => $main_output,
										   )     );
				
				$this->DB->insert( 'mail_queue', array( 'mail_to'		=> $t['email'],
														'mail_date'		=> time(),
														'mail_from'		=> '',
														'mail_subject'	=> IPSText::getTextClass( 'email' )->subject,
														'mail_content'	=> IPSText::getTextClass( 'email' )->message ) );
			}
			
		}
		
		$cache = ipsRegistry::cache()->getCache('systemvars');
		$cache['mail_queue']	+= $count;
		
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		ipsRegistry::cache()->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
	}
	
	/**
	 * Run the digest
	 *
	 * @access	private
	 * @return	void
	 */
	private function _sendForumDigest()
	{
		//-----------------------------------------
		// Get all posts / topics
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'ft.*',
										'from'		=> array( 'forum_tracker' => 'ft' ),
										'where'		=> "ft.forum_track_type='{$this->digest_time}'",
										'add_join'	=> array(
															array( 'select'	=> 'm.members_display_name, m.member_group_id, m.mgroup_others, m.org_perm_id, m.member_id, m.email, m.language, m.posts',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=ft.member_id',
																	'type'	=> 'left'
																)
															)
							)		);
		$forum_query = $this->DB->execute();
				
		//-----------------------------------------
		// Now, loop print and send to subscribers
		//-----------------------------------------
		
		$main_output	= "";
		$count			= 0;
		$cached			= array();
		$subject		= $this->digest_time == 'daily' ? 'digest_forum_daily' : 'digest_forum_weekly';
		
		while( $t = $this->DB->fetch( $forum_query ) )
		{
			$main_output	= "";
			$others_posted	= 0;
			
			$forum_name		= ipsRegistry::getClass('class_forums')->allForums[ $t['forum_id'] ]['name'];
			
			//-----------------------------------------
			// Get topics...
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'topics',
										'where'	=> "forum_id={$t['forum_id']} AND last_post > {$this->last_time}",
								)		);
			$topic_query = $this->DB->execute();
			
			$post_output = "";
			
			while( $p = $this->DB->fetch( $topic_query ) )
			{
				//-----------------------------------------
				// Check access
				//-----------------------------------------
				
				if( !$this->registry->getClass('class_forums')->checkEmailAccess( array_merge($t,$p) ) )
				{
					continue;
				}

				//-----------------------------------------
				// Topic approved or is a mod?
				//-----------------------------------------
				
				if( !$this->registry->getClass('class_forums')->checkEmailApproved( array_merge($t,$p) ) )
				{
					continue;
				}

				$post = $this->DB->buildAndFetch( array( 'select'	=> '*',
														 'from'		=> 'posts',
												 		 'where'	=> 'topic_id=' . $p['tid'],
												 		 'order'	=> 'post_date DESC',
												 		 'limit'	=> array( 1 )
												)		);
				
				//-----------------------------------------
				// Do we have other posters?
				//-----------------------------------------
				
				if ( $t['member_id'] != $post['author_id'] )
				{
					$others_posted = 1;
				}
				
				$post_author	= $post['author_name'];
				$post_date		= ipsRegistry::getClass('class_localization')->getDate( $post['post_date'], 'SHORT' );
				$post_content	= $post['post'];
				$topic_title	= $p['title'];
				
				$post_output .= "<br />-------------------------------------------<br />"
				             .  $this->lang->words['topic_langbit'] . ": {$topic_title} ({$post_author} -- {$post_date})<br />"
				             .  $this->registry->getClass('output')->buildSEOUrl( $this->settings['board_url'] . '/index.php?showtopic=' . $p['tid'], 'none', $p['title_seo'], 'showtopic' )
							 .  "<br />............................................<br />"
							 .  "{$post_content}<br /><br />";
			}
			
			//-----------------------------------------
			// Skip if there is no content...
			//-----------------------------------------
			
			if( $post_output == '' )
			{
				continue;
			}				
			
			//-----------------------------------------
			// Process
			//-----------------------------------------
			
			$main_output = $this->lang->words['forum_langbit'] . ": {$forum_name}<br />"
						 . "=====================================<br />"
						 . $post_output
						 . "<br />=====================================<br />";
			
			if ( $others_posted )
			{
				$count++;
				
				//-----------------------------------------
				// Send email...
				//-----------------------------------------
				
				IPSText::getTextClass( 'email' )->getTemplate( $subject, $t['language'] );
				
				IPSText::getTextClass( 'email' )->buildMessage( array(
													'FORUM_ID'		=> $t['forum_id'],
													'FORUM_NAME'	=> $forum_name,
													'NAME'			=> $t['members_display_name'],
													'CONTENT'		=> $main_output,
										   )     );
				
				$this->DB->insert( 'mail_queue', array( 'mail_to'		=> $t['email'],
														'mail_date'		=> time(),
														'mail_from'		=> '',
														'mail_subject'	=> IPSText::getTextClass( 'email' )->subject,
														'mail_content'	=> IPSText::getTextClass( 'email' )->message ) );
			}
			
		}
		
		$cache = ipsRegistry::cache()->getCache('systemvars');
		$cache['mail_queue']	+= $count;
		
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		ipsRegistry::cache()->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
	}
	
}