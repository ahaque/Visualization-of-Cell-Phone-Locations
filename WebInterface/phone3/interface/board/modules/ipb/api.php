<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Remote API Server
 * Last Updated: $Date: 2009-08-03 16:11:56 -0400 (Mon, 03 Aug 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 4965 $
 *
 */

class API_Server
{
	/**
	 * Defines the service for WSDL
	 *
	 * @access	public
	 * @var		array
	 */			
	public $__dispatch_map = array();
	
	/**
	 * IPS Global Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $registry;
	
	/**
	 * IPS API SERVER Class
	 *
	 * @access	public
	 * @var		object
	 */
	public $classApiServer;
	
	/**
	 * Constructor
	 * 
	 * @access	public
	 * @return	void
	 **/		
	public function __construct( $registry ) 
    {
		//-----------------------------------------
		// Set IPS CLASS
		//-----------------------------------------
		
		$this->registry = $registry;
		
    	//-----------------------------------------
    	// Load allowed methods and build dispatch
		// list
    	//-----------------------------------------
    	
		require_once( DOC_IPS_ROOT_PATH . 'interface/board/modules/ipb/methods.php' );
		
		if ( is_array( $ALLOWED_METHODS ) and count( $ALLOWED_METHODS ) )
		{
			foreach( $ALLOWED_METHODS as $_method => $_data )
			{
				$this->__dispatch_map[ $_method ] = $_data;
			}
		}
	}
	
	/**
	 * Returns the list of online users
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param 	string  $api_module		Module
	 * @param	string	$sep_character	Separator character
	 * @return	string	xml
	 **/	
	public function fetchOnlineUsers( $api_key, $api_module, $sep_character=',' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchOnlineUsers' ) !== FALSE )
		{
			$cut_off = ipsRegistry::$settings['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			$rows    = array();
			$ar_time = time();
			
			$this->registry->DB()->build( array( 'select'	=> 'id, member_id, member_name, seo_name, login_type, running_time, member_group, uagent_type',
									 'from'		=> 'sessions',
									 'where'	=> "running_time > {$time}" 
							)		);
			
			
			$this->registry->DB()->execute();
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ( $r = $this->registry->DB()->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );

			//-----------------------------------------
			// cache all printed members so we
			// don't double print them
			//-----------------------------------------
			
			$cached = array();
			
			foreach ( $rows as $result )
			{
				$last_date = ipsRegistry::getClass( 'class_localization')->getTime( $result['running_time'] );
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( isset( $result['uagent_type'] ) && $result['uagent_type'] == 'search' )
				{
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------
					
					if ( ! $cached[ $result['member_name'] ] )
					{
						if ( ipsRegistry::$settings['spider_anon'] )
						{
							if ( $this->registry->member()->getProperty('g_access_cp') )
							{
								$active['NAMES'][] = $result['member_name'];
							}
						}
						else
						{
							$active['NAMES'][] = $result['member_name'];
						}
						
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------
						
						$active['GUESTS']++;
					}
				}
				
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				
				else if ( ! $result['member_id'] OR ! $result['member_name'] )
				{
					$active['GUESTS']++;
				}
				
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
						
						if ( ! ipsRegistry::$settings['disable_anonymous'] AND $result['login_type'] )
						{
							if ( $this->registry->member()->getProperty('g_access_cp') and (ipsRegistry::$settings['disable_admin_anon'] != 1) )
							{
								$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) . "' title='$last_date'>{$result['member_name']}</a>*";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) ."' title='$last_date'>{$result['member_name']}</a>";
						}
					}
				}
			}
			
			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->classApiServer->apiSendReply( $active );
			exit();
		}
	}
	
	/**
	 * Returns details about the board
	 * 
	 * @access	public
	 * @param	string  $api_key  	Authentication Key
	 * @param	string  $api_module  Module
	 * @return	string	xml
	 */	
	public function fetchStats( $api_key, $api_module )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchStats' ) !== FALSE )
		{
			$stats = $this->registry->cache()->getCache('stats');

			$most_time     = ipsRegistry::getClass('class_localization')->getDate( $stats['most_date'], 'LONG' );
			$most_count    = ipsRegistry::getClass('class_localization')->formatNumber( $stats['most_count'] );
			
			$total_posts   = $stats['total_topics'] + $stats['total_replies'];
			
			$total_posts   = ipsRegistry::getClass('class_localization')->formatNumber($total_posts);
			$mem_count     = ipsRegistry::getClass('class_localization')->formatNumber($stats['mem_count']);
			$mem_last_id   = $stats['last_mem_id'];
			$mem_last_name = $stats['last_mem_name'];
			
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->classApiServer->apiSendReply( array( 'users_most_online'         => $most_count,
			 												'users_most_date_formatted' => $most_time,
															'users_most_data_unix'		=> $stats['most_date'],
															'total_posts'				=> $total_posts,
															'total_members'				=> $mem_count,
															'last_member_id'			=> $mem_last_id,
															'last_member_name'			=> $mem_last_name ) );
			exit();
		}
	}
	
	/**
	 * Returns hello board test
	 * 
	 * @access	public
	 * @param	string  $api_key	Authentication Key
	 * @param	string  $api_module	Module
	 * @return	string	xml
	 */	
	public function helloBoard( $api_key, $api_module )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'helloBoard' ) !== FALSE )
		{
			//-----------------------------------------
	   		// Upgrade history?
	   		//-----------------------------------------

	   		$latest_version = array( 'upgrade_version_id' => NULL );

	   		$this->registry->DB()->build( array( 'select' => '*', 'from' => 'upgrade_history', 'order' => 'upgrade_version_id DESC', 'limit' => array(1) ) );
	   		$this->registry->DB()->execute();

	   		while( $r = $this->registry->DB()->fetch() )
	   		{
				$latest_version = $r;
	   		}
	
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->classApiServer->apiSendReply( array( 'board_name'  		  => ipsRegistry::$settings['board_name'],
			 												'upload_url'  		  => ipsRegistry::$settings['upload_url'],
			 												'ipb_img_url' 		  => ipsRegistry::$settings['ipb_img_url'],
			 												'board_human_version' => $latest_version['upgrade_version_human'],
															'board_long_version'  => ( isset($latest_version['upgrade_notes']) AND $latest_version['upgrade_notes'] ) ? $latest_version['upgrade_notes'] : ipsRegistry::$vn_full ) );
			
			exit();
		}
	}
	
	/**
	 * Posts a topic to the board remotely
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$member_field	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$member_key		Member key to check for
	 * @param	integer	$forum_id		Forum id to post in
	 * @param	string	$topic_title	Topic title
	 * @param	string	$topic_description	Topic description
	 * @param	string	$post_content	Posted content
	 * @return	string	xml
	 */	
	public function postTopic( $api_key, $api_module, $member_field, $member_key, $forum_id, $topic_title, $topic_description, $post_content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$member_field           = IPSText::parseCleanValue( $member_field );
		$member_key             = IPSText::parseCleanValue( $member_key );
		$topic_title            = IPSText::parseCleanValue( $topic_title );
		$topic_description      = IPSText::parseCleanValue( $topic_description );
		$forum_id			    = intval( $forum_id );
		$UNCLEANED_post_content = $post_content;
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'postTopic' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			//-----------------------------------------
			// Member field...
			//-----------------------------------------
			
			$member	= IPSMember::load( $member_key, 'all', $member_field );
			
			//-----------------------------------------
			// Got a member?
			//-----------------------------------------
			
			if ( ! $member['member_id'] )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board could not locate a member using $member_key / $member_field" );
			}
			
			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
			$appClass	= new app_class_forums( $this->registry );

			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );
			$_postClass = new classPost( $this->registry );

			//-----------------------------------------
			// Set the data
			//-----------------------------------------

			$_postClass->setIsPreview( false );
			$_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $forum_id ] );
			$_postClass->setForumID( $forum_id );
			$_postClass->setPostContent( $UNCLEANED_post_content );
			$_postClass->setAuthor( $member['member_id'] );
			$_postClass->setPublished( true );
			$_postClass->setSettings( array( 'enableSignature' => 1,
												   'enableEmoticons' => 1,
												   'post_htmlstatus' => 0,
												   'enableTracker'   => 0 ) );
			$_postClass->setTopicTitle( $topic_title );
			$_postClass->setTopicDescription( $topic_description );
			
			/**
			 * And post it...
			 */
			try
			{
				if ( $_postClass->addTopic() === FALSE )
				{
					$this->classApiServer->apiSendError( '10', "IP.Board could not add the topic" );
				}
			}
			catch( Exception $error )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board post class exception: " . $error->getMessage() );
			}

			$this->classApiServer->apiSendReply( array( 
														'result'   => 'success',
														'topic_id' => $_postClass->getTopicData('tid')
												)	);
			exit();
		}
	}
	
	/**
	 * Posts a topic reply
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$member_field	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$member_key		Member key to check for
	 * @param	integer	$topic_id		Topic id to post in
	 * @param	string	$post_content	Posted content
	 * @return	string	xml
	 */	
	public function postReply( $api_key, $api_module, $member_field, $member_key, $topic_id, $post_content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$member_field           = IPSText::parseCleanValue( $member_field );
		$member_key             = IPSText::parseCleanValue( $member_key );
		$topic_id			    = intval( $topic_id );
		$UNCLEANED_post_content = $post_content;
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'postReply' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			//-----------------------------------------
			// Member field...
			//-----------------------------------------
			
			$member	= IPSMember::load( $member_key, 'all', $member_field );
			
			//-----------------------------------------
			// Got a member?
			//-----------------------------------------
			
			if ( ! $member['member_id'] )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board could not locate a member using $member_key / $member_field" );
			}

			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
			$appClass	= new app_class_forums( $this->registry );

			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );
			$_postClass = new classPost( $this->registry );

			//-----------------------------------------
			// Need the topic...
			//-----------------------------------------
			
			$topic	= $this->registry->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_id ) );
			
			//-----------------------------------------
			// Set the data
			//-----------------------------------------

			$_postClass->setIsPreview( false );
			$_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $topic['forum_id'] ] );
			$_postClass->setForumID( $topic['forum_id'] );
			$_postClass->setTopicID( $topic_id );
			$_postClass->setTopicData( $topic );
			$_postClass->setPostContent( $UNCLEANED_post_content );
			$_postClass->setAuthor( $member['member_id'] );
			$_postClass->setPublished( true );
			$_postClass->setSettings( array( 'enableSignature' => 1,
												   'enableEmoticons' => 1,
												   'post_htmlstatus' => 0,
												   'enableTracker'   => 0 ) );
			
			/**
			 * And post it...
			 */
			try
			{
				if ( $_postClass->addReply() === FALSE )
				{
					//print $_postClass->_postErrors;
					$this->classApiServer->apiSendError( '10', "IP.Board could not add the reply " . $_postClass->_postErrors );
				}
			}
			catch( Exception $error )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board post class exception: " . $error->getMessage() );
			}

			$this->classApiServer->apiSendReply( array( 'result'   => 'success' ) );
															

			exit();
		}
	}
	
	/**
	 * Returns a member
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$search_type	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$search_string	String to search for
	 * @return	string	xml
	 */	
	public function fetchMember( $api_key, $api_module, $search_type, $search_string )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$search_type            = IPSText::parseCleanValue( $search_type );
		$search_string          = IPSText::parseCleanValue( $search_string );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchMember' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$member = IPSMember::load( $search_string, 'all', $search_type );
			
			if ( ! $member['member_id'] )
			{
				$member = array( 'member_id' => 0 );
			}

			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( $member );
			exit();
		}
	}
	
	/**
	 * Check if a member exists
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$search_type	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$search_string	String to search for
	 * @return	string	xml
	 */	
	public function checkMemberExists( $api_key, $api_module, $search_type, $search_string )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$search_type            = IPSText::parseCleanValue( $search_type );
		$search_string          = IPSText::parseCleanValue( $search_string );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'checkMemberExists' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			$member = IPSMember::load( $search_string, 'all', $search_type );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( array( 'memberExists' => $member['member_id'] ? true : false ) );
			exit();
		}
	}
	
	/**
	 * Fetch the forum options list.
	 * WARNING: Last two options are deprecated and no longer supported. All viewable forums returned. User is automatically treated like a guest.
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$selected_forum_ids	Comma separated list of forum ids
	 * @param	bool	$view_as_guest	Treat user as a guest
	 * @return	string	xml
	 */	
	public function fetchForumsOptionList( $api_key, $api_module, $selected_forum_ids, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchForumsOptionList' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
			$appClass	= new app_class_forums( $this->registry );

			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$list = $this->registry->getClass('class_forums')->forumsForumJump();
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( array( 'forumList' => $list ) );
			exit();
		}
	}
	
	/**
	 * Returns the board's forums.
	 * WARNING: Last option is deprecated and no longer supported.  User is automatically treated like a guest.
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$forum_ids		Comma separated list of forum ids
	 * @param	bool	$view_as_guest	Treat user as a guest
	 * @return	string	xml
	 */	
	public function fetchForums( $api_key, $api_module, $forum_ids, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key       = IPSText::md5Clean( $api_key );
		$api_module    = IPSText::parseCleanValue( $api_module );
		$forum_ids     = ( $forum_ids ) ? explode( ',', IPSText::parseCleanValue( $forum_ids ) ) : null;
		$view_as_guest = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchForums' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
			$appClass	= new app_class_forums( $this->registry );

			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$return	= array();
			
			foreach( $forum_ids as $id )
			{
				$return[]	= $this->registry->getClass('class_forums')->forumsFetchData( $id );
			}
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( $return );
			exit();
		}
	}
	
	/**
	 * Returns topics based on request params
	 * 
	 * @access	public
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$forum_ids		Comma separated list of forum ids
	 * @param	string	$order_field	DB field to order by
	 * @param	string	$order_by		One of "asc" or "desc"
	 * @param	integer	$offset			Start point offset for results
	 * @param	integer	$limit			Number of results to pull
	 * @param	bool	$view_as_guest	Treat user as a guest
	 * @return	string	xml
	 */	
	public function fetchTopics( $api_key, $api_module, $forum_ids, $order_field, $order_by, $offset, $limit, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key       = IPSText::md5Clean( $api_key );
		$api_module    = IPSText::parseCleanValue( $api_module );
		$forum_ids 	   = IPSText::parseCleanValue( $forum_ids );
		$order_field   = IPSText::parseCleanValue( $order_field );
		$order_by      = ( strtolower( $order_by ) == 'asc' ) ? 'asc' : 'desc';
		$offset		   = intval( $offset );
		$limit		   = intval( $limit );
		$view_as_guest = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchTopics' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . '/api/forums/api_topic_view.php' );
			$topic_view	= new apiTopicView();
			
			//-----------------------------------------
			// Fetch topic list
			//-----------------------------------------
			
			$topic_view->topic_list_config['order_field']	= $order_field;
			$topic_view->topic_list_config['order_by']		= $order_by;
			$topic_view->topic_list_config['forums']		= $forum_ids;
			$topic_view->topic_list_config['offset']		= $offset;
			$topic_view->topic_list_config['limit']			= $limit;
			
			$topics = $topic_view->return_topic_list_data( $view_as_guest );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( $topics );
			exit();
		}
	}
	

	
	/**
	 * Has to return at least the member ID, member log in key and session ID
	 *
	 * @access	private
	 * @param	array  $member		Array of member information
	 * @return	array  $session		Session information
	 * @deprecated	Session is automatically created via instantiating ipsRegistry
	 */
	private function __create_user_session( $member )
	{
		return $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * Routine to create a local user account
	 *
	 * @access	protected
	 * @param	string  $email_address		Email address of user logged in
	 * @param	string  $md5_once_password	The plain text password, hashed once
	 * @param	string  $ip_address			IP Address of registree
	 * @param	string  $unix_join_date		The member's join date in unix format
	 * @param	string  $timezone			The member's timezone
	 * @param	string  $dst_autocorrect	The member's DST autocorrect settings
	 * @return	array   $member				Newly created member array
	 */
	protected function __create_user_account( $email_address='', $md5_once_password, $ip_address, $unix_join_date, $timezone=0, $dst_autocorrect=0 )
	{
		//-----------------------------------------
		// Check to make sure there's not already
		// a member registered.
		//-----------------------------------------
		
		$member = $this->registry->DB()->buildAndFetch( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "email='" . $this->registry->DB()->addSlashes( $email_address ) . "'" ) );
		
		if ( $member['id'] )
		{
			return $member;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$unix_join_date = $unix_join_date ? $unix_join_date : time();
		$ip_address     = $ip_address     ? $ip_address     : $this->registry->member()->ip_address;

		//-----------------------------------------
		// Create member
		//-----------------------------------------
 		
		$member = IPSMember::create( array( 'members' => array(
																'email'			=> $email_address,
																'password'		=> $md5_once_password,
																'joined'		=> $unix_join_date,
																'ip_address'	=> $ip_address
															)
									)		);
		
		return $member;
	}
	
	/**
	 * Checks to see if the request is allowed
	 * 
	 * @access	protected
	 * @param	string	$api_key		Authenticate Key
	 * @param	string	$api_module		Module
	 * @param	string	$api_function	Function 
	 * @return	string	Error message, if any
	 */
	protected function __authenticate( $api_key, $api_module, $api_function )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->api_user['api_user_id'] )
		{
			$this->api_user['_permissions'] = unserialize( stripslashes( $this->api_user['api_user_perms'] ) );
			
			if ( $this->api_user['_permissions'][ $api_module ][ $api_function ] == 1 )
			{
				return TRUE;
			}
			else
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 0 ) );
				
				$this->classApiServer->apiSendError( '200', "API Key {$api_key} does not have permission for {$api_module}/{$api_function}" );

				return FALSE;
			}
		}
		else
		{
			$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																'api_log_date'    => time(),
																'api_log_query'   => $this->classApiServer->raw_request,
																'api_log_allowed' => 0 ) );
			
			$this->classApiServer->apiSendError( '100', "API Key {$api_key} does not have permission for {$api_module}/{$api_function}" );
																																						
			return FALSE;
		}
	}

}