<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Moderator actions
 * Last Updated: $Date: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 * File Created By: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Revision: 5045 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classPost
{
	/**
	 * Attachments class
	 *
	 * @access	public
	 * @var		object
	 */
	public $class_attach;
	
	/**
	 * HTML output
	 *
	 * @access	public
	 * @var		string
	 */
    public $output	= "";
    
	/**
	 * Moderators
	 *
	 * @access	protected
	 * @var		array
	 */
    protected $moderator	= array();
   
	/**
	 * Topic data
	 *
	 * @access	public
	 * @var		array
	 */
    public $topic	= array();
    
	/**
	 * Open/close times
	 *
	 * @access	public
	 * @var		array
	 */
    public $times	= array( 'open' => NULL, 'close' => NULL );
    
	/**
	 * This request triggers two posts to merge
	 *
	 * @access	protected
	 * @var		bool
	 */
    protected $_isMergingPosts	= false;
    
	/**#@+
	 * Various user permissions
	 *
	 * @access	public
	 * @var		mixed		integer|boolean
	 */
	public $can_add_poll					= 0;
	public $max_poll_questions				= 0;
	public $max_poll_choices_per_question	= 0;
	public $can_upload						= 0;
    public $can_edit_poll					= 0;
 	public $poll_total_votes				= 0;
 	public $can_set_close_time				= 0;
 	public $can_set_open_time				= 0;
 	/**#@-*/
 	
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
	/**#@-*/
	
	/**
	 * Internal post array when editing
	 *
	 * @var		array
	 * @access	protected
	 */
	protected $_originalPost = array();
	
	/**
	 * Internal __call array
	 *
	 * @access	private
	 * @var		array
	 */
	private $_internalData = array();
	
	/**
	 * Internal post error string
	 *
	 * @var		string
	 * @access	protected
	 */
	public $_postErrors = '';
	
	/**
	 * Topic Title
	 *
	 * @var		string
	 * @access	protected
	 */
	protected $_topicTitle = '';
	
	/**
	 * Topic Description
	 *
	 * @var		string
	 * @access	protected
	 */
	protected $_topicDescription = '';

	/**
	 * Allowed items to be saved in the get/set array
	 *
	 * @access	private
	 * @var		array
	 */
	private $_allowedInternalData = array( 'Author',
										   'ForumID',
										   'TopicID',
										   'PostID',
										   'PostContent',
										   'PostContentPreFormatted',
										   'TopicState',
										   'TopicPinned',
										   'Published',
										   'Settings',
										   'IsPreview',
										   'ModOptions',
										   'IsAjax' );
										
	/**
	 * Internal get class array
	 *
	 * @access	private
	 * @var		array
	 */
	private $_internalClasses = array();
	
	/**
	 * Forum Data
	 *
	 * @access	private
	 * @var 	array
	 */
	private $_forumData = array();
	
	/**
	 * Topic Data
	 *
	 * @access	private
	 * @var 	array
	 */
	private $_topicData = array();
	
	/**
	 * Post Data
	 *
	 * @access	private
	 * @var 	array
	 */
	private $_postData = array();
	
	/**
	 * Construct
	 *
	 * @access	public
	 * @param	object	ipsRegistry Object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_post' ) );

		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
	}
	
	/**
	 * Magic Call method
	 *
	 * @access	public
	 * @param	string	Method Name
	 * @param	mixed	Method arguments
	 * @return	mixed
	 * Exception codes:
	 */
	public function __call( $method, $arguments )
	{
		$firstBit = substr( $method, 0, 3 );
		$theRest  = substr( $method, 3 );
	
		if ( in_array( $theRest, $this->_allowedInternalData ) )
		{
			if ( $firstBit == 'set' )
			{
				if ( $theRest == 'Author' )
				{
					if ( is_array( $arguments[0] ) )
					{
						$this->_internalData[ $theRest ] = $arguments[0];
					}
					else
					{
						if( $arguments[0] )
						{
							/* Set up moderator stuff, too */
							$this->_internalData[ $theRest ] = $this->registry->getClass('class_forums')->setUpModerator( IPSMember::load( intval( $arguments[0] ), 'all' ) );

							/* And ignored users */
							$this->_internalData[ $theRest ]['ignored_users'] = array();
							
							$this->registry->DB()->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => "ignore_owner_id=" . intval( $arguments[0] ) ) );
							$this->registry->DB()->execute();
				
							while( $r = $this->registry->DB()->fetch() )
							{
								$this->_internalData[ $theRest ]['ignored_users'][] = $r['ignore_ignore_id'];
							}
						}
						else
						{
							$this->_internalData[ $theRest ] = IPSMember::setUpGuest();
						}
					}
				}
				else
				{
					$this->_internalData[ $theRest ] = $arguments[0];
					return TRUE;
				}
			}
			else
			{
				if ( ( $theRest == 'Author' OR $theRest == 'Settings' OR $theRest == 'ModOptions' ) AND isset( $arguments[0] ) )
				{
					return isset( $this->_internalData[ $theRest ][ $arguments[0] ] ) ? $this->_internalData[ $theRest ][ $arguments[0] ] : '';
				}
				else
				{
					return isset( $this->_internalData[ $theRest ] ) ? $this->_internalData[ $theRest ] : '';
				}
			}
		}
		else
		{
			switch( $method )
			{
				case 'setForumData':
					$this->_forumData = $arguments[0];
				break;
				case 'setPostData':
					$this->_postData = $arguments[0];
				break;
				case 'setTopicData':
					$this->_topicData = $arguments[0];
				break;
				case 'getForumData':
					if ( $arguments[0] )
					{
						return $this->_forumData[ $arguments[0] ];
					}
					else
					{
						return $this->_forumData;
					}
				break;
				case 'getPostData':
					if ( $arguments[0] )
					{
						return $this->_postData[ $arguments[0] ];
					}
					else
					{
						return $this->_postData;
					}
				break;
				case 'getTopicData':
					if ( $arguments[0] )
					{
						return $this->_topicData[ $arguments[0] ];
					}
					else
					{
						return $this->_topicData;
					}
				break;
				case 'getPostError':
					return $this->lang->words[ $this->_postErrors ];
				break;
			}
		}
	}
	
	/**
	 * Set a post error remotely
	 *
	 * @access	public
	 * @param	string		Error
	 * @return	void
	 */
	public function setPostError( $error )
	{
		$this->_postErrors	= $error;
	}

	/**
	 * Sets the topic title.
	 * You *must* pass a raw GET or POST value. ie, a value that has not been cleaned by parseCleanValue
	 * as there are unicode checks to perform. This function will test those and clean the topic title for you
	 *
	 * @access	public
	 * @param	string		Topic Title
	 */
	public function setTopicTitle( $topicTitle )
	{ 
		if ( $topicTitle )
		{
			$this->_topicTitle = $topicTitle;

			/* Clean */
			if( $this->settings['etfilter_shout'] )
			{
				if( function_exists('mb_convert_case') )
				{
					if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
					{
						$this->_topicTitle = mb_convert_case( $this->_topicTitle, MB_CASE_TITLE, $this->settings['gb_char_set'] );
					}
					else
					{
						$this->_topicTitle = ucwords( strtolower($this->_topicTitle) );
					}
				}
				else
				{
					$this->_topicTitle = ucwords( strtolower($this->_topicTitle) );
				}
			}
			
			$this->_topicTitle = IPSText::parseCleanValue( $this->_topicTitle );
			$this->_topicTitle = $this->cleanTopicTitle( $this->_topicTitle );
			$this->_topicTitle = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->_topicTitle );
			
			/* Unicode test */
			if ( IPSText::mbstrlen( $topicTitle ) > $this->settings['topic_title_max_len'] )
			{
				$this->_postErrors = 'topic_title_long';
			}
		
			if ( (IPSText::mbstrlen( IPSText::stripslashes( $topicTitle ) ) < 2) or ( ! $this->_topicTitle )  )
			{
				$this->_postErrors = 'no_topic_title';
			}		
		}
	}
	
	/**
	 * Sets the topic descripton.
	 * You *must* pass a raw GET or POST value. ie, a value that has not been cleaned by parseCleanValue
	 * as there are unicode checks to perform. This function will test those and clean the topic description for you
	 *
	 * @access	public
	 * @param	string		Topic Title
	 */
	public function setTopicDescription( $topicDescription )
	{
		$this->_topicDescription = IPSText::parseCleanValue( $topicDescription );
		$this->_topicDescription = trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::stripAttachTag( $this->_topicDescription ) ) );
		$this->_topicDescription = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $this->_topicDescription, 0, 70 ) );
	}
	
	
	/**
	 * Global checks and set up
	 * Functions pertaining to ALL posting methods
	 *
	 * @access	public
	 * @return	void
	 * Exception Codes:
	 * NO_USER_SET			No user has been set
	 * NO_POSTING_PPD		No posting perms 'cos of PPD
	 */
	public function globalSetUp()
	{
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $this->getForumID() )
		{
			throw new Exception( 'NO_FORUM_ID' );
		}
		
		if ( ! is_array( $this->getAuthor() ) )
		{
			throw new Exception( 'NO_AUTHOR_SET' );
		}
		
		/* Check PPD */
		if ( $this->registry->getClass('class_forums')->checkGroupPostPerDay( $this->getAuthor() ) !== TRUE )
		{
			throw new Exception( 'NO_POSTING_PPD' );
		}
		
		//-----------------------------------------
		// Forum checks
		//-----------------------------------------
		
		# Forum switched off?
        if ( ! $this->getForumData('status') )
        {
        	throw new Exception( 'NO_POST_FORUM' );
        }

        # No forum id?
        if ( ! $this->getForumData('id') )
        {
        	throw new Exception( 'NO_FORUM_ID' );
        }
        
		# Non postable sub forum
        if ( ! $this->getForumData('sub_can_post') )
        {
        	throw new Exception( 'NO_SUCH_FORUM' );
        }   
		
		/* Make sure we have someone set */
		if ( ( ! $this->getAuthor('member_group_id') ) OR ( ! $this->getAuthor('members_display_name') ) )
		{
			throw new Exception( "NO_USER_SET" );
		}
		
		//-----------------------------------------
		// Do we have the member group info for this member?
		//-----------------------------------------
	
		if ( ! $this->getAuthor('g_id') )
		{
			$group_cache = $this->registry->cache()->getCache('group_cache');
			
			$this->setAuthor( array_merge( $this->getAuthor(), $group_cache[ $this->getAuthor('member_group_id') ] ) );
		}
		
		//-----------------------------------------
		// Allowed to upload?
		//-----------------------------------------
		
		$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
		$perm_array = explode( ",", $perm_id );

		if ( $this->registry->permissions->check( 'upload', $this->getForumData(), $perm_array ) === TRUE )
        {
        	if ( $this->getAuthor('g_attach_max') != -1 )
        	{
        		$this->can_upload = 1;
			}
		}
		
		//-----------------------------------------
		// Allowed poll?
		//-----------------------------------------
	
		$_moderator = $this->getAuthor('forumsModeratorData');
		
		$this->can_add_poll                  = intval($this->getAuthor('g_post_polls'));
		$this->max_poll_choices_per_question = intval($this->settings['max_poll_choices']);
		$this->max_poll_questions            = intval($this->settings['max_poll_questions']);
		$this->can_edit_poll                 = ( $this->getAuthor('g_is_supmod') ) ? $this->getAuthor('g_is_supmod') : ( isset($_moderator[ $this->getForumData('id') ]['edit_post']) ? intval( $_moderator[ $this->getForumData('id') ]['edit_post'] ) : 0 );
		
		if ( ! $this->max_poll_questions )
		{
			$this->can_add_poll = 0;
		}
		
		if ( ! $this->getForumData('allow_poll') )
		{
			$this->can_add_poll = 0;
		}

		$this->settings[ 'max_post_length'] =  $this->settings['max_post_length'] ? $this->settings['max_post_length'] : 2140000 ;
	
		//-----------------------------------------
        // Are we a moderator?
        //-----------------------------------------
        
        if ( $this->getAuthor('member_id') != 0 and $this->getAuthor('g_is_supmod') == 0 )
        {
			/* Load Moderator Options */
			$this->moderator = $_moderator[ $this->getForumID() ];
        }
	
		//-----------------------------------------
		// Set open and close time
		//-----------------------------------------
		
		$this->can_set_open_time  = ( $this->getAuthor('g_is_supmod') ) ? $this->getAuthor('g_is_supmod') : ( isset($_moderator[ $this->getForumData('id') ]['mod_can_set_open_time']) ? intval( $_moderator[ $this->getForumData('id') ]['mod_can_set_open_time'] ) : 0 );
		$this->can_set_close_time = ( $this->getAuthor('g_is_supmod') ) ? $this->getAuthor('g_is_supmod') : ( isset($_moderator[ $this->getForumData('id') ]['mod_can_set_close_time']) ? intval( $_moderator[ $this->getForumData('id') ]['mod_can_set_close_time'] ) : 0 );
	
		//-----------------------------------------
		// OPEN...
		//-----------------------------------------
		
		$_POST['open_time_date']  = isset($_POST['open_time_date']) ? $_POST['open_time_date'] : NULL;
		$_POST['open_time_time']  = isset($_POST['open_time_time']) ? $_POST['open_time_time'] : NULL;
		$_POST['close_time_date'] = isset($_POST['close_time_date']) ? $_POST['close_time_date'] : NULL;
		$_POST['close_time_time'] = isset($_POST['close_time_time']) ? $_POST['close_time_time'] : NULL;
		
		if ( $this->can_set_open_time AND $_POST['open_time_date'] AND $_POST['open_time_time'] )
		{
			$date						= strtotime( $_POST['open_time_date'] );
			//list( $month, $day, $year ) = explode( "/", $_POST['open_time_date'] );
			list( $hour , $minute     ) = explode( ":", $_POST['open_time_time'] );
			
			if ( $date )
			{
				$this->times['open'] = ( $date + ( $minute * 60 ) + ( $hour * 3600 ) ) - $this->registry->class_localization->getTimeOffset();
				/*$this->registry->getClass( 'class_localization')->convertDateToTimestamp( array( 'month'  => intval($month),
																						   'day'    => intval($day),
																						   'year'   => intval($year),
																						   'hour'   => intval($hour),
																						   'minute' => intval($minute) ) );*/
			}
		}
		
		//-----------------------------------------
		// CLOSE...
		//-----------------------------------------
		
		if ( $this->can_set_close_time AND $_POST['close_time_date'] AND $_POST['close_time_time'] )
		{
			$date						= strtotime( $_POST['close_time_date'] );
			//list( $month, $day, $year ) = explode( "/", $_POST['close_time_date'] );
			list( $hour , $minute     ) = explode( ":", $_POST['close_time_time'] );
			
			if ( $date )
			{
				$this->times['close'] = ( $date + ( $minute * 60 ) + ( $hour * 3600 ) ) - $this->registry->class_localization->getTimeOffset();
				/*$this->registry->getClass( 'class_localization')->convertDateToTimestamp( array( 'month'  => intval($month),
																							'day'    => intval($day),
																							'year'   => intval($year),
																							'hour'   => intval($hour),
																							'minute' => intval($minute) ) );*/
			}
		}
	}
	
	/**
	 * Alter the topic based on moderation options, etc
	 *
	 * @param	array 	Topic data from the DB
	 * @return	array 	Altered topic data
	 * @access	private
	 */
	private function _modTopicOptions( $topic )
	{
		/* INIT */
		$topic['state'] = ( $topic['state'] == 'closed' ) ? 'closed' : 'open';
		
		if ( ( $this->request['mod_options'] != "") or ( $this->request['mod_options'] != 'nowt' ) )
		{			
			if ($this->request['mod_options'] == 'pin')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['pin_topic'] == 1)
				{
					$topic['pinned'] = 1;
					
					$this->addToModLog( $this->lang->words['modlogs_pinned'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'unpin')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['unpin_topic'] == 1)
				{
					$topic['pinned'] = 0;
					
					$this->addToModLog( $this->lang->words['modlogs_unpinned'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'close')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['close_topic'] == 1)
				{
					$topic['state'] = 'closed';
					
					$this->addToModLog( $this->lang->words['modlogs_closed'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'open')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['open_topic'] == 1)
				{
					$topic['state'] = 'open';
					
					$this->addToModLog( $this->lang->words['modlogs_opened'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'move')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['move_topic'] == 1)
				{
					$topic['_returnToMove'] = 1;
				}
			}
			else if ($this->request['mod_options'] == 'pinclose')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['pin_topic'] == 1 AND $this->moderator['close_topic'] == 1 ) )
				{
					$topic['pinned'] = 1;
					$topic['state']  = 'closed';
					
					$this->addToModLog( $this->lang->words['modlogs_pinclose'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'pinopen')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['pin_topic'] == 1 AND $this->moderator['open_topic'] == 1 ) )
				{
					$topic['pinned'] = 1;
					$topic['state']  = 'open';
					
					$this->addToModLog( $this->lang->words['modlogs_pinopen'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'unpinclose')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['unpin_topic'] == 1 AND $this->moderator['close_topic'] == 1 ) )
				{
					$topic['pinned'] = 0;
					$topic['state']  = 'closed';
					
					$this->addToModLog( $this->lang->words['modlogs_unpinclose'], $topic['title']);
				}
			}
			else if ($this->request['mod_options'] == 'unpinopen')
			{
				if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['unpin_topic'] == 1 AND $this->moderator['open_topic'] == 1 ) )
				{
					$topic['pinned'] = 0;
					$topic['state']  = 'open';
					
					$this->addToModLog( $this->lang->words['modlogs_unpinopen'], $topic['title']);
				}
			}
		}
		
		//-----------------------------------------
		// Check close times...
		//-----------------------------------------
		
		if ( $topic['state'] == 'open' AND ( $this->times['close'] AND $this->times['close'] <= time() ) )
		{
			$topic['state'] = 'closed';
		}
		else if ( $topic['state'] == 'closed' AND ( $this->times['open'] AND $this->times['open'] >= time() ) )
		{
			$topic['state'] = 'open';
		}
		
		if ( $topic['state'] == 'open' AND ( $this->times['open'] OR $this->times['close'] )
				AND ( $this->times['close'] <= time() OR ( $this->times['open'] > time() AND ! $this->times['close'] ) ) )
		{
			$topic['state'] = 'closed';
		}
		
		if ( $topic['state'] == 'open' AND ( $this->times['open'] AND $this->times['close'] )
				AND ( $this->times['close'] >= $this->times['open'] ) )
		{
			$topic['state'] = 'closed';
		}
		
		$topic['state'] = ( $topic['state'] == 'closed' ) ? 'closed' : 'open';
		
		return $topic;
	}
	
	/**
	 * Post a reply
	 * Very simply posts a reply. Simple.
	 *
	 * Usage:
	 * $post->setTopicID(100);
	 * $post->setForumID(5);
	 * $post->setAuthor( $member );
	 * 
	 * $post->setPostContent( "Hello [b]there![/b]" );
	 * # Optional: No bbcode, etc parsing will take place
	 * # $post->setPostContentPreFormatted( "Hello [b]there![/b]" );
	 * $post->addReply();
	 *
	 * Exception Error Codes:
	 * NO_TOPIC_ID       : No topic ID set
	 * NO_FORUM_ID		: No forum ID set
	 * NO_AUTHOR_SET	    : No Author set
	 * NO_CONTENT        : No post content set
	 * NO_SUCH_TOPIC     : No such topic
	 * NO_SUCH_FORUM		: No such forum
	 * NO_REPLY_PERM     : Author cannot reply to this topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_REPLY_POLL     : Cannot reply to this poll only topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_POST_FORUM		: Unable to post in that forum
	 * FORUM_LOCKED		: Forum read only
	 *
	 * @access	public
	 * @return	mixed	Exception, boolean, or void
	 */
	public function addReply()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$topic_id = intval( $this->getTopicID() );
		$forum_id = intval( $this->getForumID() );
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
		
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		if ( ! $this->getPostContent() AND ! $this->getPostContentPreFormatted() AND !$this->getIsPreview() )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		try
		{
			$topic = $this->replySetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$post = $this->compilePostData();

		//-----------------------------------------
		// Do we have a valid post?
		// alt+255 = chr(160) = blank space
		//-----------------------------------------

		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $post['post'] ) ) ) ) < 1 AND !$this->getIsPreview() )
		{
			$this->_postErrors	= 'post_too_short';
		}
		
		if ( IPSText::mbstrlen( $postContent ) > ( $this->settings['max_post_length'] * 1024 ) AND !$this->getIsPreview() )
		{
			$this->_postErrors	= 'post_too_long';
		}
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		if ( ($this->_postErrors != "") or ( $this->getIsPreview() === TRUE ) )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		//-----------------------------------------
		// Insert the post into the database to get the
		// last inserted value of the auto_increment field
		//-----------------------------------------
		
		$post['topic_id'] = $topic['tid'];
		
		//-----------------------------------------
		// Merge concurrent posts?
		//-----------------------------------------
		
		if ( $this->getAuthor('member_id') AND $this->settings['post_merge_conc'] )
		{
			//-----------------------------------------
			// Get check time
			//-----------------------------------------
			
			$time_check = time() - ( $this->settings['post_merge_conc'] * 60 );
			
			//-----------------------------------------
			// Last to post?
			//-----------------------------------------
			
			if ( ( $topic['last_post'] > $time_check ) AND ( $topic['last_poster_id'] == $this->getAuthor('member_id') ) )
			{
				//-----------------------------------------
				// Get the last post. 2 queries more efficient
				// than one... trust me
				//-----------------------------------------
				
				$last_pid = $this->DB->buildAndFetch( array( 'select' => 'MAX(pid) as maxpid',
																			  'from'   => 'posts',
																			  'where'  => 'topic_id='.$topic['tid'],
																			  'limit'  => array( 0, 1 ) ) );
				
				$last_post = $this->DB->buildAndFetch( array( 'select' => '*',
																			   'from'   => 'posts',
																			   'where'  => 'pid='.$last_pid['maxpid'] ) );
				
				//-----------------------------------------
				// Sure we're the last poster?
				//-----------------------------------------
				
				if ( $last_post['author_id'] == $this->getAuthor('member_id') )
				{
					$new_post  = $last_post['post'].'<br /><br />'.$post['post'];
					
					//-----------------------------------------
					// Make sure we don't have too many images
					//-----------------------------------------
					
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->getAuthor('member_group_id');
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->getAuthor('mgroup_others');
										
					$test_post = IPSText::getTextClass( 'bbcode' )->preEditParse( $new_post );
					$test_post = IPSText::getTextClass( 'bbcode' )->preDbParse( $test_post );
										
					if ( IPSText::getTextClass( 'bbcode' )->error )
					{
						$this->_postErrors = 'merge_'.IPSText::getTextClass( 'bbcode' )->error;
						$this->showReplyForm();
						return;
					}
					
					//-----------------------------------------
					// Update post row
					//-----------------------------------------
					
					$this->DB->force_data_type = array( 'pid'  => 'int',
													    'post' => 'string' );
				
					$this->DB->update( 'posts', array( 'post' => $new_post, 'post_date' => time() ), 'pid='.$last_post['pid'] );
										
					/* Add to cache */
					IPSContentCache::drop( 'post', $last_post['pid'] );
					// Commented out for bug #14252, replace is unreliable
					//IPSContentCache::update( $last_post['pid'], 'post', $this->formatPostForCache( $new_post ) );
					
					$post['pid']			= $last_post['pid'];
					$post['post_key']		= $last_post['post_key'];
					$post['post']			= $new_post;
					$post_saved				= 1;
					$this->_isMergingPosts	= 1;
					
					/* Make sure we reset the post key for attachments */
					$this->DB->update( 'attachments', array( 'attach_post_key' => $post['post_key'] ), "attach_rel_module='post' AND attach_post_key='" . $this->post_key . "'" );
				}
			}
		}
		
		//-----------------------------------------
		// No?
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts )
		{
			//-----------------------------------------
			// Add post to DB
			//-----------------------------------------
			
			$post['post_key']    = $this->post_key;
			$post['post_parent'] = ipsRegistry::$request['parent_id'] ? intval(ipsRegistry::$request['parent_id']) : 0;
			
			//-----------------------------------------
			// Typecast
			//-----------------------------------------
			
			$this->DB->force_data_type = array( 'pid'  => 'int',
												'post' => 'string' );
			
			$this->DB->insert( 'posts', $post );
						
			$post['pid'] = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Require pre-approval of posts?
			//-----------------------------------------
			
			if ( $post['queued'] )
			{
				$this->DB->insert( 'mod_queued_items', array( 'type' => 'post', 'type_id' => $post['pid'] ) );
			}
			
			/* Add to cache */
			IPSContentCache::update( $post['pid'], 'post', $this->formatPostForCache( $post['post'] ) );
		}
		
		//-----------------------------------------
		// If we are still here, lets update the
		// board/forum/topic stats
		//-----------------------------------------
		
		$this->updateForumAndStats( $topic, 'reply');
		
		//-----------------------------------------
		// Get the correct number of replies
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topic['tid']} and queued != 1" ) );
		$this->DB->execute();
		
		$posts = $this->DB->fetch();
		
		$pcount = intval( $posts['posts'] - 1 );
		
		//-----------------------------------------
		// Get the correct number of queued replies
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topic['tid']} and queued=1" ) );
		$this->DB->execute();
		
		$qposts  = $this->DB->fetch();
		
		$qpcount = intval( $qposts['posts'] );
		
		//-----------------------------------------
		// UPDATE TOPIC
		//-----------------------------------------
		
		$poster_name = $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : ipsRegistry::$request['UserName'];
		
		$update_array = array(
							  'posts'			 => $pcount,
							  'topic_queuedposts'=> $qpcount
							 );
							 
		if ( $this->getPublished() )
		{					 
			$update_array['last_poster_id']   = $this->getAuthor('member_id');
			$update_array['last_poster_name'] = $poster_name;
			$update_array['seo_last_name']    = IPSText::makeSeoTitle( $poster_name );
			$update_array['last_post']        = time();
			$update_array['pinned']           = $topic['pinned'];
			$update_array['state']            = $topic['state'];
			
			if ( count( $this->poll_questions ) AND $this->can_add_poll )
			{
				$update_array['poll_state'] = 1;
			}
		}
		
		$this->DB->force_data_type = array( 'title'            => 'string',
											'description'      => 'string',
										    'starter_name'     => 'string',
										    'seo_last_name'    => 'string',
										    'last_poster_name' => 'string' );
													  
		$this->DB->update( 'topics', $update_array, "tid={$topic['tid']}"  );
		
		//-----------------------------------------
		// Add the poll to the polls table
		//-----------------------------------------
		
		if ( count( $this->poll_questions ) AND $this->can_add_poll )
		{
			$poll_only = 0;
			
			if( $this->settings['ipb_poll_only'] AND ipsRegistry::$request['poll_only'] == 1 )
			{
				$poll_only = 1;
			}
								
			$this->DB->insert( 'polls', 
											array (
													  'tid'           => $topic['tid'],
													  'forum_id'      => $this->getForumData('id'),
													  'start_date'    => time(),
													  'choices'       => serialize( $this->poll_questions ),
													  'starter_id'    => $this->getAuthor('member_id'),
													  'votes'         => 0,
													  'poll_question' => ipsRegistry::$request['poll_question'],
													  'poll_only'	  => $poll_only,
											)     );
		}
		
		//-----------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts )
		{
			$this->incrementUsersPostCount();
		}
		
		/* Upload Attachments */
		$this->uploadAttachments( $post['post_key'], $post['pid'] );
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		 
		$this->makeAttachmentsPermanent( $post['post_key'], $post['pid'], 'post', array( 'topic_id' => $topic['tid'] ) );
	
		//-----------------------------------------
		// Moderating?
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts AND ( $this->getPublished() === FALSE ) )
		{
			//-----------------------------------------
			// Boing!!!
			//-----------------------------------------
			
			$this->sendNewTopicForApprovalEmails( $topic['tid'], $topic['title'], $topic['starter_name'], $post['pid'], 'reply' );
			
			$page = floor( $topic['posts'] / $this->settings['display_max_posts'] );
			$page = $page * $this->settings['display_max_posts'];
			
			ipsRegistry::getClass('output')->redirectScreen( $this->lang->words['moderate_post'], $this->settings['base_url'] . "showtopic={$topic['tid']}&st=$page" );
		}
		
		//-----------------------------------------
		// Are we tracking topics we reply in 'auto_track'?
		//-----------------------------------------
		
		$this->addTopicToTracker($topic['tid'], 1);
		
		//-----------------------------------------
		// Check for subscribed topics
		// XXPass on the previous last post time of the topic
		// 12.26.2007 - we want to send email if the new post was
		// made after the member's last visit...which should be
		// last_activity minus session expiration
		// to see if we need to send emails out
		//-----------------------------------------
		
		$this->sendOutTrackedTopicEmails( $topic['tid'], $post['post'], $poster_name, time() - $this->settings['session_expiration'], $this->getAuthor('member_id') );
		
		//-----------------------------------------
		// Leave data for other apps
		//-----------------------------------------
		
		$this->setTopicData( $topic );
		$this->setPostData( $post );
		
		return TRUE;
	}

	/**
	 * Performs set up for adding a reply
	 *
	 * @access	public
	 * @return	array    Topic data
	 *
	 * Exception Error Codes
	 * NO_SUCH_TOPIC		No topic could be found matching the topic ID and forum ID
	 * NO_REPLY_PERM		Viewer does not have permission to reply
	 * TOPIC_LOCKED		The topic is locked
	 * NO_REPLY_POLL		This is a poll only topic
	 * NO_TOPIC_ID		No topic ID (durrrrrrrrrrr)
	 */
	public function replySetUp()
	{
		//-----------------------------------------
		// Check for a topic ID
		//-----------------------------------------
	
		if( ! $this->getTopicID() )
		{
			throw new Exception( 'NO_TOPIC_ID' );
		}
		
        /* Minimum Posts Check */        
		if( $this->getForumData('min_posts_post') && $this->getForumData('min_posts_post') > $this->getAuthor('posts') )
		{
			$this->registry->output->showError( 'posting_not_enough_posts', 103140 );
		}		
		
		//-----------------------------------------
		// Set up post key
		//-----------------------------------------
		
		$this->post_key = ( $this->request['attach_post_key'] AND $this->request['attach_post_key'] != "" ) ? $this->request['attach_post_key'] : md5( microtime() );
		
		//-----------------------------------------
		// Load and set topic
		//-----------------------------------------

		$topic = $this->getTopicData();
		
		if( ! $topic['tid'] )
		{
			throw new Exception("NO_SUCH_TOPIC");
		}
		
		//-----------------------------------------
		// Checks
		//-----------------------------------------
		
		if( $topic['poll_state'] == 'closed' and $this->getAuthor('g_is_supadmin') != 1 )
		{
			throw new Exception( 'NO_REPLY_PERM' );
		}
		
		if( $topic['starter_id'] == $this->getAuthor('member_id') )
		{
			if( ! $this->getAuthor('g_reply_own_topics'))
			{
				throw new Exception( 'NO_REPLY_PERM' );
			}
		}

		if( $topic['starter_id'] != $this->getAuthor('member_id') )
		{
			if( ! $this->getAuthor('g_reply_other_topics') )
			{
				throw new Exception( 'NO_REPLY_PERM' );
			}
		}

		//if( IPSMember::checkPermissions( 'reply', $this->getForumID() ) === FALSE )
		$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
		$perm_array = explode( ",", $perm_id );

		if ( $this->registry->permissions->check( 'reply', $this->getForumData(), $perm_array ) === FALSE )
		{
			throw new Exception( 'NO_REPLY_PERM' );
		}
		
		if( $topic['state'] != 'open')
		{
			if( $this->getAuthor('g_post_closed') != 1 )
			{
				throw new Exception( 'TOPIC_LOCKED' );
			}
		}
		
		if( isset($topic['poll_only']) AND $topic['poll_only'] )
		{
			if( $this->getAuthor('g_post_closed') != 1 )
			{
				throw new Exception( 'NO_REPLY_POLL' );
			}
		}
		
		//-----------------------------------------
		// POLL BOX ( Either topic starter or admin)
		// and without a current poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			$this->can_add_poll = 0;
			
			if ( ! $topic['poll_state'] )
			{
				if ( $this->getAuthor('member_id') AND $this->getPublished() )
				{
					if ( $this->getAuthor('g_is_supmod') == 1 )
					{
						$this->can_add_poll = 1;
					}
					else if ( $topic['starter_id'] == $this->getAuthor('member_id') )
					{
						if ( ($this->settings['startpoll_cutoff'] > 0) AND ( $topic['start_date'] + ($this->settings['startpoll_cutoff'] * 3600) > time() ) )
						{
							$this->can_add_poll = 1;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Mod options...
		//-----------------------------------------
		
		$topic = $this->_modTopicOptions( $topic );
		
		return $topic;
	}
	
	/**
	 * Post a new topic
	 * Very simply posts a new topic. Simple.
	 *
	 * Usage:
	 * $post->setTopicID(100);
	 * $post->setForumID(5);
	 * $post->setAuthor( $member );
	 * 
	 * $post->setPostContent( "Hello [b]there![/b]" );
	 * # Optional: No bbcode, etc parsing will take place
	 * # $post->setPostContentPreFormatted( "Hello [b]there![/b]" );
	 * $post->setTopicTitle('Hi!');
	 * $post->addTopic();
	 *
	 * Exception Error Codes:
	 * NO_FORUM_ID		: No forum ID set
	 * NO_AUTHOR_SET	    : No Author set
	 * NO_CONTENT        : No post content set
	 * NO_SUCH_FORUM		: No such forum
	 * NO_REPLY_PERM     : Author cannot reply to this topic
	 * NO_POST_FORUM		: Unable to post in that forum
	 * FORUM_LOCKED		: Forum read only
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function addTopic()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$forum_id = intval( $this->getForumID() );
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
		
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors = $error->getMessage();
		}
		
		if ( ! $this->getPostContent() AND ! $this->getPostContentPreFormatted() )
		{
			$this->_postErrors = 'NO_CONTENT';
		}
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		try
		{
			$topic = $this->topicSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors = $error->getMessage();
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$post = $this->compilePostData();
		
		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $post['post'] ) ) ) ) < 1 )
		{
			$this->_postErrors = 'post_too_short';
			//$this->registry->getClass('output')->showError( 'post_too_short', 103143 );
		}
		
		if ( IPSText::mbstrlen( $post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->_postErrors = 'post_too_long';
			//$this->registry->getClass('output')->showError( 'post_too_long', 103144 );
		}
		
		/* Got a topic title? */
		if ( ! $this->_topicTitle )
		{
			$this->_postErrors = 'no_topic_title';
		}
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		if ( ($this->_postErrors != "") or ( $this->getIsPreview() === TRUE ) )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		//-----------------------------------------
		// Build the master array
		//-----------------------------------------

		$topic = array(
					  'title'            => $this->_topicTitle,
					  'title_seo'		 => IPSText::makeSeoTitle( $this->_topicTitle ),
					  'description'      => $this->_topicDescription,
					  'state'            => $topic['state'],
					  'posts'            => 0,
					  'starter_id'       => $this->getAuthor('member_id'),
					  'starter_name'     => $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'],
					  'seo_first_name'   => IPSText::makeSeoTitle( $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'] ),
					  'start_date'       => time(),
					  'last_poster_id'   => $this->getAuthor('member_id'),
					  'last_poster_name' => $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'],
					  'seo_last_name'    => IPSText::makeSeoTitle( $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'] ),
					  'last_post'        => time(),
					  'icon_id'          => intval($this->request['iconid']),
					  'author_mode'      => $this->getAuthor('member_id') ? 1 : 0,
					  'poll_state'       => ( count( $this->poll_questions ) AND $this->can_add_poll ) ? 1 : 0,
					  'last_vote'        => 0,
					  'views'            => 0,
					  'forum_id'         => $this->getForumData('id'),
					  'approved'         => ( $this->getPublished() === TRUE ) ? 1 : 0,
					  'pinned'           => intval( $topic['pinned'] ),
					  'topic_open_time'  => intval( $this->times['open'] ),
					  'topic_close_time' => intval( $this->times['close'] ),
					 );

		//-----------------------------------------
		// Insert the topic into the database to get the
		// last inserted value of the auto_increment field
		// follow suit with the post
		//-----------------------------------------
		
		$this->DB->force_data_type = array( 'title'            => 'string',
											'description'      => 'string',
										    'starter_name'     => 'string',
										    'seo_first_name'   => 'string',
											'seo_last_name'    => 'string',
										    'last_poster_name' => 'string' );
		
		$this->DB->insert( 'topics', $topic );
		
		$post['topic_id']  = $this->DB->getInsertId();
		$topic['tid']      = $post['topic_id'];
		
		//-----------------------------------------
		// Update the post info with the upload array info
		//-----------------------------------------
		
		$post['post_key']  = $this->post_key;
		$post['new_topic'] = 1;
		
		//-----------------------------------------
		// Unqueue the post if we're starting a new topic
		//-----------------------------------------
		
		$post['queued'] = 0;
		
		//-----------------------------------------
		// Add post to DB
		//-----------------------------------------
		
		$this->DB->insert( 'posts', $post );
	
		$post['pid'] = $this->DB->getInsertId();
		
		//-----------------------------------------
		// Require pre-approval of topics?
		//-----------------------------------------
		
		if( ! $topic['approved'] )
		{
			$this->DB->insert( 'mod_queued_items', array( 'type' => 'topic', 'type_id' => $topic['tid'] ) );
		}
		
		/* Add to cache */
		IPSContentCache::update( $post['pid'], 'post', $this->formatPostForCache( $post['post'] ) );
		
		//-----------------------------------------
		// Update topic with firstpost ID
		//-----------------------------------------
		
		$this->DB->update( 'topics', array( 'topic_firstpost' => $post['pid'] ), 'tid=' . $topic['tid'] );
		
		//-----------------------------------------
		// Add the poll to the polls table
		//-----------------------------------------
		
		if ( count( $this->poll_questions ) AND $this->can_add_poll )
		{
			$poll_only = 0;
			
			if ( $this->settings['ipb_poll_only'] AND $this->request['poll_only'] == 1 )
			{
				$poll_only = 1;
			}

			$this->DB->insert( 'polls',  array ( 'tid'              => $topic['tid'],
												 'forum_id'         => $this->getForumData('id'),
											     'start_date'       => time(),
											     'choices'          => addslashes(serialize( $this->poll_questions )),
											     'starter_id'       => $this->getAuthor('member_id'),
											     'votes'            => 0,
											     'poll_question'    => IPSText::stripAttachTag( $this->request['poll_question'] ),
											     'poll_only'	     => $poll_only,
											     'poll_view_voters' => intval( $this->request['poll_view_voters'] ) ) );
		}
						 
		//-----------------------------------------
		// If we are still here, lets update the
		// board/forum stats
		//----------------------------------------- 
		
		$this->updateForumAndStats( $topic, 'new');
		
		/* Upload Attachments */
		$this->uploadAttachments( $this->post_key, $post['pid'] );		
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		
		$this->makeAttachmentsPermanent( $this->post_key, $post['pid'], 'post', array( 'topic_id' => $topic['tid'] ) );
		
		//-----------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-----------------------------------------
		
		$this->incrementUsersPostCount();
		
		//-----------------------------------------
		// Are we tracking new topics we start 'auto_track'?
		//-----------------------------------------
		
		$this->addTopicToTracker($topic['tid']);		
		
		//-----------------------------------------
		// Moderating?
		//-----------------------------------------
		
		if ( $this->getPublished() === FALSE )
		{
			//-----------------------------------------
			// Redirect them with a message telling them the
			// post has to be previewed first
			//-----------------------------------------
			
			$this->sendNewTopicForApprovalEmails( $topic['tid'], $topic['title'], $topic['starter_name'], $post['pid'] );
			
			ipsRegistry::getClass('output')->redirectScreen( $this->lang->words['moderate_topic'], $this->settings['base_url'] . "showforum=" . $this->getForumData('id') );
		}
		
		//-----------------------------------------
		// Are we tracking this forum? If so generate some mailies - yay!
		//-----------------------------------------
		
		$this->sendOutTrackedForumEmails($this->getForumData('id'), $topic['tid'], $topic['title'], $this->getForumData('name'), $post['post'] );
		
		//-----------------------------------------
		// Leave data for other apps
		//-----------------------------------------
		
		$this->setTopicData( $topic );
		$this->setPostData( $post );
		
		return TRUE;
	}

	/**
	 * Performs set up for adding a new topic
	 *
	 * @access	public
	 * @return	array 	Topic data (state, pinned, etc)
	 *
	 * Exception Error Codes
	 * NO_START_PERM		User does not have permission to start a topic
	 * NOT_ENOUGH_POSTS		User does not have enough posts to start a topic
	 */
	public function topicSetUp()
	{
		//-----------------------------------------
		// Set up post key
		//-----------------------------------------
		
		$this->post_key = ( $this->request['attach_post_key'] AND $this->request['attach_post_key'] != "" ) ? $this->request['attach_post_key'] : md5( microtime() );

		if ( ! $this->getAuthor('g_post_new_topics') )
		{
			throw new Exception( 'NO_START_PERM' );
		}
		
		//if ( IPSMember::checkPermissions( 'start', $this->getForumID() ) == FALSE )
		$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
		$perm_array = explode( ",", $perm_id );

		if ( $this->registry->permissions->check( 'start', $this->getForumData(), $perm_array ) === FALSE )
		{
			throw new Exception( 'NO_START_PERM' );
		}

        /* Minimum Posts Check */
		if ( $this->getForumData('min_posts_post') && $this->getForumData('min_posts_post') > $this->getAuthor('posts') )
		{
			throw new Exception( 'NOT_ENOUGH_POSTS' );
		}
	
		//-----------------------------------------
		// Mod options...
		//-----------------------------------------
		
		$topic = $this->_modTopicOptions( array( 'title' => $this->_topicTitle )  );
		
		return $topic;
	}
	
	/**
	 * Post a reply
	 * Very simply posts a reply. Simple.
	 *
	 * Usage:
	 * $post->setFopicID(1);
	 * $post->setTopicID(5);
	 * $post->setPostID(100);
	 * $post->setAuthor( $member );
	 * 
	 * $post->setPostContent( "Hello [b]there![/b]" );
	 * # Optional: No bbcode, etc parsing will take place
	 * # $post->setPostContentPreFormatted( "Hello <b>there!</b>" );
	 * $post->editPost();
	 *
	 * Exception Error Codes:
	 * NO_TOPIC_ID       : No topic ID set
	 * NO_FORUM_ID		: No forum ID set
	 * NO_AUTHOR_SET	    : No Author set
	 * NO_CONTENT        : No post content set
	 * CONTENT_TOO_LONG  : Post is too long
	 * NO_SUCH_TOPIC     : No such topic
	 * NO_SUCH_FORUM		: No such forum
	 * NO_REPLY_PERM     : Author cannot reply to this topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_REPLY_POLL     : Cannot reply to this poll only topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_REPLY_POLL		: This is a poll only topic
	 * NO_POST_FORUM		: Unable to post in that forum
	 * FORUM_LOCKED		: Forum read only
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function editPost()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$topic_id = intval( $this->getTopicID() );
		$forum_id = intval( $this->getForumID() );
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
		
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		if ( ! $this->getPostContent() AND ! $this->getPostContentPreFormatted() )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		try
		{
			$topic = $this->editSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------

		$post = $this->compilePostData();

		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $post['post'] ) ) ) ) < 1 )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}
		
		if ( IPSText::mbstrlen( $postContent ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->_postErrors	= 'CONTENT_TOO_LONG';
		}
		
		//-----------------------------------------
		// Ajax specifics
		//-----------------------------------------
		
		if ( $this->getIsAjax() === TRUE )
		{
			# Prevent polls from being edited
			$this->can_add_poll = 0;

			# Prevent titles from being edited
			$this->edit_title   = 0;
		
			# Set Settings
			$this->setSettings( array( 'enableSignature' => ( $this->_originalPost['use_sig'] ) ? 1 : 0,
									   'enableEmoticons' => ( $this->_originalPost['use_emo'] ) ? 1 : 0,
									   'post_htmlstatus' => intval( $this->_originalPost['post_htmlstate'] ) ) );
											
			$this->request['iconid'] =  $this->_originalPost['icon_id'] ;
			
			if ( ! $this->getAuthor('g_append_edit') )
			{
				$this->request['add_edit'] =  ( $this->_originalPost['append_edit'] OR ! $this->getAuthor('g_append_edit') ? 1 : 0 );
			}
		}

		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Load the poll from the DB
			//-----------------------------------------
			
			$this->poll_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid=".$topic['tid'] ) );
			$this->DB->execute();
	
    		$this->poll_answers = $this->poll_data['choices'] ? unserialize(stripslashes($this->poll_data['choices'])) : array();
		}
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		if ( ($this->_postErrors != "") or ( $this->getIsPreview() === TRUE ) )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab the edit time
		//-----------------------------------------
		
		$time = ipsRegistry::getClass( 'class_localization')->getDate( time(), 'LONG' );
		
		//-----------------------------------------
		// Reset some data
		//-----------------------------------------
		
		$post['ip_address']  = $this->_originalPost['ip_address'];
		$post['topic_id']    = $this->_originalPost['topic_id'];
		$post['author_id']   = $this->_originalPost['author_id'];
		$post['post_date']   = $this->_originalPost['post_date'];
		$post['author_name'] = $this->_originalPost['author_name'];
		$post['queued']      = $this->_originalPost['queued'];
		$post['edit_time']   = time();
		$post['edit_name']   = $this->getAuthor('members_display_name');
		
		//-----------------------------------------
		// If the post icon has changed, update the topic post icon
		//-----------------------------------------
		
		if ( $this->_originalPost['new_topic'] == 1 )
		{
			if ( $post['icon_id'] != $this->_originalPost['icon_id'] )
			{
				$this->DB->update( 'topics', array( 'icon_id' => $post['icon_id'] ), 'tid='.$topic['tid'] );
			}
		}
		
		//-----------------------------------------
		// Update open and close times
		//-----------------------------------------
		
		if ( $this->_originalPost['new_topic'] == 1 )
		{
			$times = array();
			
			if ( $this->can_set_open_time AND $this->times['open'] )
			{
				$times['topic_open_time'] = intval( $this->times['open'] );
				
				if( $topic['topic_open_time'] AND $this->times['open'] )
				{
					$times['state'] = "closed";
					
					if( time() > $topic['topic_open_time'] )
					{
						if( time() < $topic['topic_close_time'] )
						{
							$times['state'] = "open";
						}
					}
				}
				if ( ! $this->times['open'] AND $topic['topic_open_time'] )
				{
					if ( $topic['state'] == 'closed' )
					{
						$times['state'] = 'open';
					}
				}				
			}
						
			if ( $this->can_set_close_time AND $this->times['close'] )
			{
				$times['topic_close_time'] = intval( $this->times['close'] );
				
				//-----------------------------------------
				// Was a close time, but not now?
				//-----------------------------------------
				
				if ( ! $this->times['close'] AND $topic['topic_close_time'] )
				{
					if ( $topic['state'] == 'closed' )
					{
						$times['state'] = 'open';
					}
				}
			}
			
			if ( count( $times ) )
			{
				$this->DB->update( 'topics', $times, "tid=".$topic['tid'] );
			}
		}
		
		//-----------------------------------------
		// Update poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			if ( is_array( $this->poll_questions ) AND count( $this->poll_questions ) )
			{
				$poll_only = 0;
				
				if ( $this->settings['ipb_poll_only'] AND $this->request['poll_only'] == 1 )
				{
					$poll_only = 1;
				}
				
				$poll_view_voters = ( ! $this->poll_data['votes'] ) ? $this->request['poll_view_voters'] : $this->poll_data['poll_view_voters'];
				
				if( $topic['poll_state'] )
				{
					$this->DB->update( 'polls', array( 
														'votes'				=> intval( $this->poll_total_votes ),
														'choices'			=> addslashes(serialize( $this->poll_questions )),
														'poll_question'		=> IPSText::stripAttachTag( $this->request['poll_question'] ),
														'poll_only'			=> $poll_only,
														'poll_view_voters'	=> intval( $poll_view_voters ) 
													), 'tid='.$topic['tid'] );
							
					if ( $this->poll_data['choices'] != serialize( $this->poll_questions ) OR $this->poll_data['votes'] != intval($this->poll_total_votes) )
					{
						$this->DB->insert( 'moderator_logs', array( 'forum_id'    => $this->getForumData('id'),
																	'topic_id'    => $topic['tid'],
																	'post_id'     => $this->_originalPost['pid'],
																	'member_id'   => $this->getAuthor('member_id'),
																	'member_name' => $this->getAuthor('members_display_name'),
																	'ip_address'  => $this->ip_address,
																	'http_referer'=> my_getenv('HTTP_REFERER'),
																	'ctime'       => time(),
																	'topic_title' => $topic['title'],
																	'action'      => "Edited poll",
																	'query_string'=> my_getenv('QUERY_STRING') ) );
					}
				}
				else
				{
					$this->DB->insert( 'polls', array( 'tid'              => $topic['tid'],
													   'forum_id'         => $this->getForumData('id'),
													   'start_date'       => time(),
													   'choices'          => addslashes(serialize( $this->poll_questions )),
													   'starter_id'       => $this->getAuthor('member_id'),
													   'votes'            => 0,
													   'poll_question'    => IPSText::stripAttachTag( $this->request['poll_question'] ),
													   'poll_only'	      => $poll_only,
													   'poll_view_voters' => intval( $poll_view_voters ) ) );
													
					$this->DB->insert( 'moderator_logs', array ( 'forum_id'    => $this->getForumData('id'),
																 'topic_id'    => $topic['tid'],
																 'post_id'     => $this->_originalPost['pid'],
																 'member_id'   => $this->getAuthor('member_id'),
																 'member_name' => $this->getAuthor('members_display_name'),
																 'ip_address'  => $this->ip_address,
																 'http_referer'=> my_getenv('HTTP_REFERER'),
																 'ctime'       => time(),
																 'topic_title' => $topic['title'],
																 'action'      => "Added a poll to the topic titled '" . $this->request['poll_question'] . "'",
																 'query_string'=> my_getenv('QUERY_STRING') )    );
													
					$this->DB->update( 'topics', array( 'poll_state' => 1, 'last_vote' => 0, 'total_votes' => 0 ), 'tid='.$topic['tid'] );								
				}
			}
			else
			{
				//-----------------------------------------
				// Remove the poll
				//-----------------------------------------
				
				$this->DB->buildAndFetch( array( 'delete' => 'polls' , 'where' => "tid=".$topic['tid'] ) );
				$this->DB->buildAndFetch( array( 'delete' => 'voters', 'where' => "tid=".$topic['tid'] ) );
				$this->DB->update( 'topics', array( 'poll_state' => 0, 'last_vote' => 0, 'total_votes' => 0 ), 'tid='.$topic['tid'] );
			}
		}
		
		//-----------------------------------------
		// Update topic title?
		//-----------------------------------------
		
		if ( $this->edit_title == 1 )
		{
			//-----------------------------------------
			// Update topic title
			//-----------------------------------------
				
			if ( $this->_topicTitle != "" )
			{
				if ( ($this->_topicTitle != $topic['title']) or ( $this->_topicDescription != $topic['description'] ) OR ! ( $topic['title_seo'] ) )
				{
					$this->DB->update( 'topics', array( 'title'       => $this->_topicTitle,
														'title_seo'   => IPSText::makeSeoTitle( $this->_topicTitle ),
														'description' => $this->_topicDescription ) , "tid=".$topic['tid'] );
					
					if ( $topic['tid'] == $this->getForumData('last_id') )
					{
						$this->DB->update( 'forums', array( 'last_title' => $this->_topicTitle ), 'id='.$this->getForumData('id') );
						//ipsRegistry::getClass('class_forums')->updateForumCache();
					}
					
					if ( ($this->moderator['edit_topic'] == 1) OR ( $this->getAuthor('g_is_supmod') == 1 ) )
					{
						$this->DB->insert( 'moderator_logs', array(
																	'forum_id'    => $this->getForumData('id'),
																	'topic_id'    => $topic['tid'],
																	'post_id'     => $this->_originalPost['pid'],
																	'member_id'   => $this->getAuthor('member_id'),
																	'member_name' => $this->getAuthor('members_display_name'),
																	'ip_address'  => $this->ip_address,
																	'http_referer'=> my_getenv('HTTP_REFERER'),
																	'ctime'       => time(),
																	'topic_title' => $topic['title'],
																	'action'      => "Edited topic title or description '{$topic['title']}' to '" . $this->_topicTitle . "' via post form",
																	'query_string'=> my_getenv('QUERY_STRING'),
															)    );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Reason for edit?
		//-----------------------------------------
		
		if ( $this->moderator['edit_post'] OR $this->getAuthor('g_is_supmod') )
		{
			$post['post_edit_reason'] = trim( $this->request['post_edit_reason'] );
		}
		
		//-----------------------------------------
		// Update the database (ib_forum_post)
		//-----------------------------------------
		
		$post['append_edit'] = 1;
		
		if ( $this->getAuthor('g_append_edit') )
		{
			if ( $this->request['add_edit'] != 1 )
			{
				$post['append_edit'] = 0;
			}
		}
		
		$this->DB->force_data_type = array( 'post_edit_reason' => 'string' );
	
		$this->DB->update( 'posts', $post, 'pid='.$this->_originalPost['pid'] );
		
		if( $this->_originalPost['topic_firstpost'] )
		{
			$pid   = 0;
			$title = $r['title'];
		}
		else
		{
			$pid   = serialize( array( 'pid' => $r['pid'], 'title' => $r['title'] ) );
			$title = '';
		}
		
		/* Remove from the search index */
		$this->registry->class_forums->removePostFromSearchIndex( $post['topic_id'], $this->_originalPost['pid'], $topic['posts'] ? 0 : 1 );

		/* Update the search index */
		$topic_title = $this->_topicTitle ? $this->_topicTitle : $topic['title'];
		
		/* Add to cache */
		IPSContentCache::update( $this->_originalPost['pid'], 'post', $this->formatPostForCache( $post['post'] ) );
		
		/* Upload Attachments */
		$this->uploadAttachments( $this->post_key, $this->_originalPost['pid'] );
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		
		$this->makeAttachmentsPermanent( $this->post_key, $this->_originalPost['pid'], 'post', array( 'topic_id' => $topic['tid'] ) );
		
		//-----------------------------------------
		// Make sure paperclip symbol is OK
		//-----------------------------------------
		
		$this->recountTopicAttachments($topic['tid']);
		
		//-----------------------------------------
		// Leave data for other apps
		//-----------------------------------------
		
		$this->setTopicData( $topic );
		$this->setPostData( array_merge( $this->_originalPost, $post ) );
		
		return TRUE;
	}

	/**
	 * Performs set up for editing a post
	 *
	 * @access	public
	 * @return	array    Topic data
	 *
	 * Exception Error Codes
	 * NO_SUCH_TOPIC		No topic could be found matching the topic ID and forum ID
	 * NO_SUCH_POST		Post could not be loaded
	 * NO_EDIT_PERM		Viewer does not have permission to edit
	 * TOPIC_LOCKED		The topic is locked
	 * NO_REPLY_POLL		This is a poll only topic
	 * NO_TOPIC_ID		No topic ID (durrrrrrrrrrr)
	 */
	public function editSetUp()
	{
		//-----------------------------------------
		// Check for a topic ID
		//-----------------------------------------
		
		if ( ! $this->getTopicID() )
		{
			throw new Exception( 'NO_TOPIC_ID' );
		}
		
		//-----------------------------------------
		// Load and set topic
		//-----------------------------------------
		
		$forum_id = intval( $this->getForumID() );
		
		$topic = $this->getTopicData();

		if ( ! $topic['tid'] )
		{
			throw new Exception("NO_SUCH_TOPIC");
		}
		
		if ( $forum_id != $topic['forum_id'] )
		{
			throw new Exception("NO_SUCH_TOPIC");
		}

		//-----------------------------------------
		// Load the old post
		//-----------------------------------------
		
		$this->_originalPost = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'posts', 'where' => "pid=" . $this->getPostID() ) );

		if ( ! $this->_originalPost['pid'] )
		{
			throw new Exception( "NO_SUCH_POST" );
		}

		if ( $this->getIsAjax() === TRUE )
		{
			$this->setSettings( array( 'enableSignature' => intval($this->_originalPost['use_sig']),
									   'enableEmoticons' => intval($this->_originalPost['use_emo']),
									   'post_htmlstatus' => intval( $this->_originalPost['post_htmlstate'] ),
							) 		);
		}

		//-----------------------------------------
		// Same topic?
		//-----------------------------------------
		
		if ( $this->_originalPost['topic_id'] != $topic['tid'] )
		{
            ipsRegistry::getClass('output')->showError( 'posting_mismatch_topic', 20311 );
        }
		
		//-----------------------------------------
		// Generate post key (do we have one?)
		//-----------------------------------------
		
		if ( ! $this->_originalPost['post_key'] )
		{
			//-----------------------------------------
			// Generate one and save back to post and attachment
			// to ensure 1.3 < compatibility
			//-----------------------------------------
			
			$this->post_key = md5(microtime());
			
			$this->DB->update( 'posts'      , array( 'post_key' => $this->post_key ), 'pid='.$this->_originalPost['pid'] );
			$this->DB->update( 'attachments', array( 'attach_post_key' => $this->post_key ), "attach_rel_module='post' AND attach_rel_id=".$this->_originalPost['pid'] );
		}
		else
		{
			$this->post_key = $this->_originalPost['post_key'];
		}
		
		//-----------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to edit this topic
		//-----------------------------------------
		
		$_canEdit = 0;
		
		if ( $this->getAuthor('g_is_supmod') )
		{
			$_canEdit = 1;
		}
		
		if ( isset( $this->moderator['edit_post'] ) && $this->moderator['edit_post'] )
		{
			$_canEdit = 1;
		}
		
		if ( ($this->_originalPost['author_id'] == $this->getAuthor('member_id')) and ($this->getAuthor('g_edit_posts')) )
		{ 
			//-----------------------------------------
			// Have we set a time limit?
			//-----------------------------------------
			
			if ( $this->getAuthor('g_edit_cutoff') > 0 )
			{
				if ( $this->_originalPost['post_date'] > ( time() - ( intval($this->getAuthor('g_edit_cutoff')) * 60 ) ) )
				{
					$_canEdit = 1;
				}
			}
			else
			{
				$_canEdit = 1;
			}
		}
		
		//-----------------------------------------
		// Is the topic locked?
		//-----------------------------------------
		
		if ( ( $topic['state'] != 'open' ) and ( ! $this->memberData['g_is_supmod'] AND ! $this->moderator['edit_post'] ) )
		{
			if ( $this->memberData['g_post_closed'] != 1 )
			{
				$_canEdit = 0;
			}
		}
		
		if ( $_canEdit != 1 )
		{
			throw new Exception( "NO_EDIT_PERMS" );
		}
		
		//-----------------------------------------
		// If we're not a mod or admin
		//-----------------------------------------

		if ( ! $this->getAuthor('g_is_supmod') AND ! $this->moderator['edit_post'] )
		{
			//if ( IPSMember::checkPermissions( 'reply', $this->getForumID() ) !== TRUE )
			$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
			$perm_array = explode( ",", $perm_id );
	
			if ( $this->registry->permissions->check( 'reply', $this->getForumData(), $perm_array ) !== TRUE )
			{
				$_ok = 0;
			
				//-----------------------------------------
				// Are we a member who started this topic
				// and are editing the topic's first post?
				//-----------------------------------------
			
				if ( $this->getAuthor('member_id') )
				{
					if ( $topic['topic_firstpost'] )
					{
						$_post = $this->DB->buildAndFetch( array( 'select' => 'pid, author_id, topic_id',
																		 'from'   => 'posts',
																	     'where'  => 'pid=' . intval( $topic['topic_firstpost'] ) ) );
																			
						if ( $_post['pid'] AND $_post['topic_id'] == $topic['tid'] AND $_post['author_id'] == $this->getAuthor('member_id') )
						{
							$_ok = 1;
						}
					}
				}
			
				if ( ! $_ok )
				{
					throw new Exception( "NO_EDIT_PERMS" );
				}
			}
		}
		
		//-----------------------------------------
		// Do we have edit topic abilities?
		//-----------------------------------------
		
		# For edit, this means there is a poll and we have perm to edit
		$this->can_add_poll_mod = 0;
		
		if ( $this->_originalPost['new_topic'] == 1 )
		{
			if ( $this->getAuthor('g_is_supmod') == 1 )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			else if ( $this->moderator['edit_topic'] == 1 )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			else if ( $this->getAuthor('g_edit_topic') == 1 AND ($this->_originalPost['author_id'] == $this->getAuthor('member_id')) )
			{
				$this->edit_title = 1;
			}
		}
		else
		{
			$this->can_add_poll = 0;
		}
		
		return $topic;
	}
	
	/**
	 * Guest Captcha Check
	 *
	 * Not called automatically! You must check this in your own scripts!
	 *
	 * Exception Error Codes
	 * REG_CODE_ENTER	No reg code was entered
	 * CODE_ERROR		The code entered did not match the one stored in the DB
	 *
	 * @access	public
	 * @return	void
	 */
	public function checkGuestCaptcha()
	{
		//-----------------------------------------
		// Guest w/ CAPTCHA?
		//-----------------------------------------
		
		if ( $this->getAuthor('member_id') == 0 AND $this->settings['guest_captcha'] )
		{
			//-----------------------------------------
			// Security code stuff
			//-----------------------------------------
			
			if ( $this->request['fast_reply_used'] AND $this->request['fast_reply_used'] == 1 )
			{
				throw new Exception( "REG_CODE_ENTER" );
			}					

			if ( !$this->registry->getClass('class_captcha')->validate() )
			{
				throw new Exception( "CODE_ERROR" );
			}			
		}
	}
    
	/**
	 * Check to see if we have any group restrictions on whether we can post or not
	 *
	 * @access	public
	 * @param	array 		[Member data (assumes $this->memberData if nothing passed )]
	 * @return	boolean		TRUE = post is moderated, FALSE is not
	 */
	public function checkGroupIsPostModerated( $memberData = array() )
	{
		$memberData = ( is_array( $memberData ) and count( $memberData ) ) ? $memberData : $this->getAuthor();
		$group      = $this->caches['group_cache'][ $memberData['member_group_id'] ];
	
		/* Ok? */
		if ( is_array( $group ) AND is_array( $memberData ) )
		{
			/* Check posts per day */
			if ( $group['g_mod_preview'] )
			{
				/* Do we only limit for x posts/days? */
				if ( $group['g_mod_post_unit'] )
				{
					if ( $group['gbw_mod_post_unit_type'] )
					{
						/* Days.. .*/
						if ( $memberData['joined'] > ( time() - ( 86400 * $group['g_mod_post_unit'] ) ) )
						{
							return TRUE;
						}
					}
					else
					{
						/* Posts */
						if ( $memberData['posts'] < $group['g_mod_post_unit'] )
						{
							return TRUE;
						}
					}
				}
				else
				{
					/* No limit, but still checking moderating */
					return TRUE;
				}
			}
			
			/* Still here? */
			return FALSE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Sends new topic waiting for approval email
	 *
	 * @access	public
	 * @param	integer	$tid
	 * @param	string	$title
	 * @param	integer	$author
	 * @param	string	[$type]	Set to 'new' for new topic or 'reply' for a reply to topic, 'new' is the default option
	 * @return	void
	 **/
	public function sendNewTopicForApprovalEmails( $tid, $title, $author, $pid=0, $type='new' )
	{
		$tmp = $this->DB->buildAndFetch( array( 'select' => 'notify_modq_emails', 'from' => 'forums', 'where' => "id=".$this->getForumData('id')) );
		
		if ( $tmp['notify_modq_emails'] == "" )
		{ 
			return;
		}
		
		if ( $type == 'new' )
		{
			IPSText::getTextClass( 'email' )->getTemplate("new_topic_queue_notify");
		}
		else
		{
			IPSText::getTextClass( 'email' )->getTemplate("new_post_queue_notify");
		}
		
		IPSText::getTextClass( 'email' )->buildMessage( array(
											'TOPIC'  => $title,
											'FORUM'  => $this->getForumData('name'),
											'POSTER' => $author,
											'DATE'   => $this->registry->getClass( 'class_localization')->getDate( time(), 'SHORT', 1 ),
											'LINK'   => $this->settings['base_url'] .'app=forums&module=forums&section=findpost&pid='.$pid,
										  )
									);
		
		$email_message = IPSText::getTextClass( 'email' )->message;
		
		foreach( explode( ",", $tmp['notify_modq_emails'] ) as $email )
		{
			if ( $email )
			{
				IPSText::getTextClass( 'email' )->message = $email_message;
				IPSText::getTextClass( 'email' )->to      = trim($email);
				IPSText::getTextClass( 'email' )->sendMail();
			}
		}
	}
	
	/**
	 * Sends out topic subscription emails
	 *
	 * @access	public
	 * @param	integer	$tid
	 * @param	string	$post
	 * @param	integer	$poster
	 * @param	integer	$last_post
	 * @param	integer	$member_id
	 * @return	bool
	 **/
	public function sendOutTrackedTopicEmails( $tid=0, $post="", $poster=0, $last_post=0, $member_id=0 )
	{
		if ($tid == "")
		{
			return TRUE;
		}
		
		if( $this->getPublished() === false )
		{
			return true;
		}
		
		$count = 0;

		//-----------------------------------------
		// Get the email addy's, topic ids and email_full stuff - oh yeah.
		// We only return rows that have a member last_activity of greater than the post itself
		// Ergo:
		//  Last topic post: 8:50am
		//  Last topic visit: 9:00am
		//  Next topic reply: 9:10am
		// if ( last.activity > last.topic.post ) { send.... }
		//  Next topic reply: 9:20am
		// if ( last.activity > last.topic.post ) { will fail as 9:10 > 8:50 }
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select'   => 'tr.trid, tr.topic_id, tr.last_sent, tr.topic_track_type',
								'from'     => array( 'tracker' => 'tr' ),
								'where'    => 'tr.topic_id=' . $tid . ' AND m.member_id != ' . intval($member_id)
										  	. ' AND ( ( tr.topic_track_type=\'delayed\' AND m.last_activity < ' . $last_post . ' ) OR tr.topic_track_type=\'immediate\' )',
								'add_join' => array(
													array( 
															'select' => 'm.members_display_name, m.email, m.member_id, m.email_full, m.language, m.org_perm_id, m.member_group_id, m.mgroup_others, m.last_activity, m.posts',
															'from'   => array( 'members' => 'm' ),
															'where'  => 'm.member_id=tr.member_id',
															'type'   => 'left' ),
													array( 
															'select' => 't.title, t.forum_id, t.approved',
															'from'   => array( 'topics' => 't' ),
															'where'  => 't.tid=tr.topic_id',
															'type'   => 'left' 
														) 
													) 
						)	);
		$outer = $this->DB->execute();
		
		if( $this->DB->getTotalRows($outer) )
		{
			while ( $r = $this->DB->fetch($outer) )
			{
				//-----------------------------------------
				// Can access forum/topic?
				//-----------------------------------------
				
				if ( ! $this->registry->getClass('class_forums')->checkEmailAccess($r) )
				{
					continue;
				}
				
				//-----------------------------------------
				// Topic approved or is a mod?
				//-----------------------------------------
				
				if ( ! $this->registry->getClass('class_forums')->checkEmailApproved($r) )
				{
					continue;
				}

				// Only send one email until user logs in again...
				// That is, unless they want the full post
				if ( ( $r['topic_track_type'] != 'immediate' ) AND $r['last_sent'] > $r['last_activity'] AND ! $r['email_full'] )
				{
					continue;
				}
				else
				{
					$this->DB->update( "tracker", array( 'last_sent' => time() ), "trid=".$r['trid'] );
				}
				
				$count++;
				
				$r['language'] = $r['language'] ? $r['language'] : '';
				
				if ($r['email_full'] == 1)
				{
					IPSText::getTextClass( 'email' )->getTemplate( "subs_with_post", $r['language'] );
			
					IPSText::getTextClass( 'email' )->buildMessage( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['members_display_name'],
														'POSTER'          => $poster,
														'POST'            => $post,
													  )
												);
					
				}
				else
				{
				
					IPSText::getTextClass( 'email' )->getTemplate("subs_no_post", $r['language']);
			
					IPSText::getTextClass( 'email' )->buildMessage( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['members_display_name'],
														'POSTER'          => $poster,
													  )
												);
					
				}
								
				//-----------------------------------------
				// Add to mail queue
				//-----------------------------------------
				
				$this->DB->insert( 'mail_queue', array( 'mail_to' => $r['email'], 'mail_from' => '', 'mail_date' => time(), 'mail_subject' => IPSText::getTextClass( 'email' )->subject, 'mail_content' => IPSText::getTextClass( 'email' )->message ) );
			}

			$cache = $this->registry->cache()->getCache('systemvars');
			$cache['mail_queue'] += $count;
			
			//-----------------------------------------
			// Update cache with remaning email count
			//-----------------------------------------
			
			$this->registry->cache()->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
		}

		return TRUE;
	}
	
	/**
	 * Sends out forum subscription emails
	 *
	 * @access	public
	 * @param	integer	$fid
	 * @param	integer	$this_tid
	 * @param	string	$title
	 * @param	string	$forum_name
	 * @param	string	$post
	 * @param	integer	$mid
	 * @param	string	$mname
	 * @return	bool
	 **/
	public function sendOutTrackedForumEmails( $fid=0, $this_tid=0, $title="", $forum_name="", $post="", $mid=0, $mname="" )
	{
		if ($this_tid == "")
		{
			return TRUE;
		}
		
		if ($fid == "")
		{
			return TRUE;
		}
		
		if( $this->getPublished() === false )
		{
			return true;
		}
		
		$mid 	= $mid > 0 ? $mid : $this->getAuthor('member_id');
		$mname	= $mname != '' ? $mname : $this->getAuthor('members_display_name');
		
		//-----------------------------------------
		// Work out the time stamp needed to "guess"
		// if the user is still active on the board
		// We will base this guess on a period of
		// non activity of time_now - 30 minutes.
		//-----------------------------------------
		
		$time_limit = time() - (30*60);
		$count      = 0;
		$gotem      = array();

		$this->DB->build( array( 
								'select'   => 'tr.frid, tr.last_sent',
								'from'     => array( 'forum_tracker' => 'tr' ),
								'where'    => 'tr.forum_id='.$fid." AND ( ( tr.forum_track_type='delayed' AND m.last_activity < {$time_limit} ) OR tr.forum_track_type='immediate' )",
								'add_join' => array( 
													array( 
															'select' => 'm.members_display_name, m.member_group_id, m.email, m.member_id, m.language, m.last_activity, m.org_perm_id, m.mgroup_others, m.posts',
															'from'   => array( 'members' => 'm' ),
															'where'  => "tr.member_id=m.member_id",//" AND m.member_id <> {$mid}",
															'type'   => 'inner' 
														),
													array( 
															'select' => 'g.g_perm_id',
															'from'   => array( 'groups' => 'g' ),
															'where'  => "m.member_group_id=g.g_id",
															'type'   => 'left' 
														)  
													)
						)	);
		
		$outer = $this->DB->execute();
		
		while ( $r = $this->DB->fetch($outer) )
		{
			$this->DB->update( "forum_tracker", array( 'last_sent' => time() ), "frid=".$r['frid'] );
			
			$r['forum_id'] = $fid;
			$gotem[ $r['member_id'] ] = $r;
		}

		//-----------------------------------------
		// Get "all" groups?
		//-----------------------------------------
		
		if ( $this->settings['autoforum_sub_groups'] )
		{
			$this->DB->build( array( 
										'select'   => 'm.members_display_name, m.member_group_id, m.email, m.member_id, m.language, m.last_activity, m.org_perm_id, m.mgroup_others, m.posts',
										'from'     => array( 'members' => 'm' ),
										'where'    => "m.member_group_id IN (" . $this->settings['autoforum_sub_groups'] . ")
													   AND m.member_id <> {$mid}
													   AND m.allow_admin_mails=1
													   AND m.last_activity < {$time_limit}",
										'add_join' => array(
															array( 
																	'select' => 'g.g_perm_id',
																	'from'   => array( 'groups' => 'g' ),
																	'where'  => "m.member_group_id=g.g_id",
																	'type'   => 'left' 
																)  
															)
																		
							)	);
		
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$r['forum_id'] = $fid;
				$gotem[ $r['member_id'] ] = $r;
			}
		}
		
		//-----------------------------------------
		// Row, row and parse, parse
		//-----------------------------------------
		
		if ( count( $gotem ) )
		{			
			foreach( $gotem as $mid => $r )
			{
				$count++;

				//-----------------------------------------
				// Can access forum/topic?
				//-----------------------------------------
				
				if( !$this->registry->getClass('class_forums')->checkEmailAccess( $r, false ) )
				{
					continue;
				}
				
				if( $mid == $this->memberData['member_id'] )
				{
					continue;
				}

				$r['language'] = $r['language'] ? $r['language'] : '';
				
				IPSText::getTextClass( 'email' )->getTemplate("subs_new_topic", $r['language']);
		
				IPSText::getTextClass( 'email' )->buildMessage( array(
													'TOPIC_ID'        => $this_tid,
													'FORUM_ID'        => $fid,
													'TITLE'           => $title,
													'NAME'            => $r['members_display_name'],
													'POSTER'          => $mname,
													'FORUM'           => $forum_name,
													'POST'            => $post,
												  )
											);
				
				$this->DB->insert( 'mail_queue', array( 'mail_to' => $r['email'], 'mail_from' => '', 'mail_date' => time(), 'mail_subject' => IPSText::getTextClass( 'email' )->subject, 'mail_content' => IPSText::getTextClass( 'email' )->message ) );
			}
		}
		
		$cache = $this->registry->cache()->getCache('systemvars');
		$cache['mail_queue'] += $count;
			
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		$this->registry->cache()->setCache( 'systemvars', $cache,  array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
		
		return TRUE;
	}
    
	/**
	 * Compiles an array of poll questions
	 *
	 * @access	protected
	 * @return	array
	 **/
    protected function compilePollData()
    {
    	//-----------------------------------------
		// Check poll
		//-----------------------------------------

		$questions		= array();
		$choices_count	= 0;
		$is_mod			= $this->getAuthor('g_is_supmod') ? $this->getAuthor('g_is_supmod') : ( isset($this->moderator['edit_topic']) ? intval($this->moderator['edit_topic']) : 0);
				
		if ( $this->can_add_poll )
		{
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				$has_poll = 1;
				
				foreach( $_POST['question'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['question'] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $q ) ) ), 255 );
				}
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['multi'] = intval($q);
				}
			}			
			
			//-----------------------------------------
			// Choices...
			//-----------------------------------------
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $mainid => $choice )
				{
					if( !$choice )
					{
						continue;
					}

					list( $question_id, $choice_id ) = explode( "_", $mainid );
					
					$question_id = intval( $question_id );
					$choice_id   = intval( $choice_id );
					
					if ( ! $question_id OR ! isset($choice_id) )
					{
						continue;
					}
					
					if ( ! $questions[ $question_id ]['question'] )
					{
						continue;
					}
					
					$questions[ $question_id ]['choice'][ $choice_id ] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $choice ) ) ), 255 );
					
					if( ! $is_mod OR $this->request['poll_view_voters'] OR $this->poll_data['poll_view_voters'] )
					{
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval($this->poll_answers[ $question_id ]['votes'][ $choice_id ]);
					}
					else
					{
						$_POST['votes'] = isset($_POST['votes']) ? $_POST['votes'] : 0;
						
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval( $_POST['votes'][ $question_id.'_'.$choice_id ] );
					}
					
					$this->poll_total_votes += $questions[ $question_id ]['votes'][ $choice_id ];
				}
			}
			
			//-----------------------------------------
			// Make sure we have choices for each
			//-----------------------------------------
			
			foreach( $questions as $id => $data )
			{
				if ( ! is_array( $data['choice'] ) OR ! count( $data['choice'] ) )
				{
					unset( $questions[ $id ] );
				}
				else
				{
					$choices_count += intval( count( $data['choice'] ) );
				}
			}
			
			//-----------------------------------------
			// Error check...
			//-----------------------------------------
			
			if ( count( $questions ) > $this->max_poll_questions )
			{
				$this->_postErrors = 'poll_to_many';
			}
			
			if ( count( $choices_count ) > ( $this->max_poll_questions * $this->max_poll_choices_per_question ) )
			{
				$this->_postErrors = 'poll_to_many';
			}
		}

		return $questions;
    }

	/**
	 * Compiles all the incoming information into an array which is returned to hte accessor
	 *
	 * @access	protected
	 * @return	array
	 **/
	protected function compilePostData()
	{
		//-----------------------------------------
		// Sort out post content
		//-----------------------------------------
		
		if ( $this->getPostContentPreFormatted() )
		{
			$postContent = $this->getPostContentPreFormatted();
		}
		else
		{
			$postContent = $this->formatPost( $this->getPostContent() );
		}
		
		//-----------------------------------------
		// Remove board tags
		//-----------------------------------------
		
		$postContent = IPSText::removeMacrosFromInput( $postContent );
		
		//-----------------------------------------
		// Need to format the post?
		//-----------------------------------------
		
		$post = array(
						'author_id'   => $this->getAuthor('member_id') ? $this->getAuthor('member_id') : 0,
						'use_sig'     => $this->getSettings('enableSignature'),
						'use_emo'     => $this->getSettings('enableEmoticons'),
						'ip_address'  => $this->member->ip_address,
						'post_date'   => time(),
						'icon_id'     => $this->request['iconid'] ? $this->request['iconid'] : 0,
						'post'        => $postContent,
						'author_name' => $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : $this->request['UserName'],
						'topic_id'    => "",
						'queued'      => ( $this->getPublished() ) ? 0 : 1,
						'post_htmlstate' => $this->getSettings('post_htmlstatus'),
					 );

		//-----------------------------------------
		// If we had any errors, parse them back to this class
		// so we can track them later.
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parse_smilies			= $post['use_emo'];
		IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->getForumData('use_html') and $this->getAuthor('g_dohtml') and $post['post_htmlstate'] ) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $post['post_htmlstate'] == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->getForumData('use_ibc') ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->getAuthor('member_group_id');
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->getAuthor('mgroup_others');
		
		$testParse	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $postContent );
		
		if ( IPSText::getTextClass( 'bbcode' )->error )
		{
			$this->_postErrors = IPSText::getTextClass( 'bbcode' )->error;
		}
		
		return $post;
	}
	
	/**
	* Format Post: Converts BBCode, smilies, etc
	*
	* @access	public
	* @param	string	Raw Post
	* @return	string	Formatted Post
	* @author	MattMecham
	*/
	public function formatPost( $postContent )
	{
		$postContent = IPSText::getTextClass( 'editor' )->processRawPost( $postContent );
		
		//-----------------------------------------
		// Parse post
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parse_smilies    = $this->getSettings('enableEmoticons');
		IPSText::getTextClass( 'bbcode' )->parse_html    	= (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= intval($this->request['post_htmlstatus']) == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= $this->getForumData('use_ibc');
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';
		
		$postContent = IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent );
		
		# Make this available elsewhere without reparsing, etc
		$this->setPostContentPreFormatted( $postContent );
		
		return $postContent;
	}
	
	/**
	* Format Post for cache: Converts BBCode, smilies, etc
	*
	* @access	public
	* @param	string	Raw Post
	* @return	string	Formatted Post
	* @author	MattMecham
	*/
	public function formatPostForCache( $postContent )
	{
		/* Set up parser */
		IPSText::getTextClass( 'bbcode' )->parse_smilies         = $this->getSettings('enableEmoticons');
		IPSText::getTextClass( 'bbcode' )->parse_html    	     = (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		     = intval($this->request['post_htmlstatus']) == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    	     = $this->getForumData('use_ibc');
		IPSText::getTextClass( 'bbcode' )->parsing_section	     = 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup		 = $this->getAuthor('member_group_id');
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = $this->getAuthor('mgroup_others');
		
		/* Did we already format this? */
		$tmp = $this->getPostContentPreFormatted();
		
		if ( $tmp )
		{
			$postContent = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $tmp );
		}
		else
		{
			$postContent = IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent ) );
		}
		
		return $postContent;
	}
	
	/**
	 * Adds the action to the moderator logs
	 *
	 * @access	protected
	 * @param	string	$title
	 * @param	string	$topic_title
	 * @return	void
	 **/
	protected function addToModLog( $title='unknown', $topic_title )
	{
		$this->DB->insert( 'moderator_logs', array (
												'forum_id'    => $this->request['f'],
												'topic_id'    => $this->request['t'],
												'post_id'     => $this->request['p'],
												'member_id'   => $this->getAuthor('member_id'),
												'member_name' => $this->getAuthor('members_display_name'),
												'ip_address'  => $this->member->ip_address,
												'http_referer'=> htmlspecialchars(my_getenv('HTTP_REFERER')),
												'ctime'       => time(),
												'topic_title' => $topic_title,
												'action'      => substr( $title, 0, 255 ),
												'query_string'=> htmlspecialchars(my_getenv('QUERY_STRING')),
										     ) );
	}
	
	/**
	 * Increments the users post count
	 *
	 * @access	public
	 * @param	int		Number of posts to increment by (default 1)
	 * @return	void
	 **/
	public function incrementUsersPostCount( $inc=1 )
	{
		/* INIT */
		$update_sql = array();
		$today      = time() - 86400;
		
		/* Recount today's posts BOTH approved and unapproved */
		$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(post_date) as min',
										  		  'from'   => 'posts',
										  		  'where'  => 'author_id=' . $this->getAuthor('member_id') . ' AND post_date > ' . $today ) );
										
		$update_sql['members_day_posts'] = intval( $count['count'] ) . ',' . intval( $count['min'] );
		
		/* Just save the members day posts for now and return, the rest is handled elsewhere */
		if ( $this->getPublished() === false )
		{
			IPSMember::save( $this->getAuthor('member_id'), array( 'core' => $update_sql ) );
			return;
		}

		if ( $this->getAuthor('member_id') )
		{
			if ( $this->getForumData('inc_postcount') )
			{
				/* Increment the users post count */
				$update_sql['posts'] = $this->getAuthor('posts') + intval( $inc );
				
				/* Are we checking for auto promotion? */
				if ($this->getAuthor('g_promotion') != '-1&-1')
				{
					/* Are we checking for post based auto incrementation? 0 is post based, 1 is date based, so...  */
					if ( ! $this->getAuthor('gbw_promote_unit_type') )
					{
						list($gid, $gposts) = explode( '&', $this->getAuthor('g_promotion') );
					
						if ( $gid > 0 and $gposts > 0 )
						{
							if ( $this->getAuthor('posts') + intval( $inc ) >= $gposts )
							{
								$update_sql['member_group_id'] = $gid;
							}
						}
					}
				}
			}
			
			$update_sql['last_post'] = time();
			
			$this->member->setProperty( 'last_post', time() );
			
			IPSMember::save( $this->getAuthor('member_id'), array( 'core' => $update_sql ) );
		}	
	}
	
	/**
	 * Update forum's last post information
	 *
	 * @access	protected
	 * @param	array 	$topic
	 * @param	string	$type
	 * @return	void
	 **/
	protected function updateForumAndStats( $topic, $type='new')
	{
		$moderated  = 0;
		$stat_cache = $this->registry->cache()->getCache('stats');
		$forum_data = $this->getForumData();
		
		//-----------------------------------------
		// Moderated?
		//-----------------------------------------
		
		$moderate = 0;
		
		if ( $this->getPublished() === false )
		{
			$moderate = 1;
		}

		//-----------------------------------------
		// Add to forum's last post?
		//-----------------------------------------
		
		if ( ! $moderate )
		{
			if( $topic['approved'] )
			{
				$dbs = array( 'last_title'       => $topic['title'],
							  'seo_last_title'   => IPSText::makeSeoTitle( $topic['title'] ),
							  'last_id'          => $topic['tid'],
							  'last_post'        => time(),
							  'last_poster_name' => $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'],
							  'seo_last_name'    => IPSText::makeSeoTitle( $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'] ),
							  'last_poster_id'   => $this->getAuthor('member_id'),
							  'last_x_topic_ids' => $this->registry->class_forums->lastXFreeze( $this->registry->class_forums->buildLastXTopicIds( $forum_data['id'], FALSE ) )
						   );
			
				if ( $type == 'new' )
				{
					$stat_cache['total_topics']++;
					
					$forum_data['topics'] = intval($forum_data['topics']);
					$dbs['topics']         = ++$forum_data['topics'];
					
					$dbs['newest_id']	   = $topic['tid'];
					$dbs['newest_title']   = $topic['title'];
				}
				else
				{
					$stat_cache['total_replies']++;
					
					$forum_data['posts'] = intval($forum_data['posts']);
					$dbs['posts']         = ++$forum_data['posts'];
				}
			}
		}
		else
		{
			if ( $type == 'new' )
			{
				$forum_data['queued_topics'] = intval($forum_data['queued_topics']);
				$dbs['queued_topics']         = ++$forum_data['queued_topics'];
			}
			else
			{
				$forum_data['queued_posts'] = intval($forum_data['queued_posts']);
				$dbs['queued_posts']         = ++$forum_data['queued_posts'];
			}
		}
		
		//-----------------------------------------
		// Merging posts?
		// Don't update counter
		//-----------------------------------------
		
		if ( $this->_isMergingPosts )
		{
			unset($dbs['posts']);
			unset($dbs['queued_posts']);
			
			$stat_cache['total_replies'] -= 1;
		}
		
		//-----------------------------------------
		// Update
		//-----------------------------------------
		
		if( is_array($dbs) AND count($dbs) )
		{
			$this->DB->force_data_type = array( 'last_poster_name' => 'string',
											    'seo_last_name'    => 'string',
											    'seo_last_title'   => 'string',
												'last_title'	   => 'string' );
	
			$this->DB->update( 'forums', $dbs, "id=".intval($forum_data['id']) );
		}
		
		//-----------------------------------------
		// Update forum cache
		//-----------------------------------------
		
		//$this->registry->getClass('class_forums')->updateForumCache();
		
		$this->registry->cache()->setCache( 'stats', $stat_cache, array( 'array' => 1, 'deletefirst' => 0, 'donow' => 0 ) );
	}
	
	/**
	 * Convert temp uploads into permanent ones! YAY
	 *
	 * @access	protected
	 * @param	string	$post_key
	 * @param	integer	$rel_id
	 * @param	string	$rel_module
	 * @param	arary 	$args
	 * @return	void
	 **/
	protected function makeAttachmentsPermanent( $post_key="", $rel_id=0, $rel_module="", $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Attachments: Re-affirm...
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach                  =  new class_attach( $this->registry );
		
		$class_attach->type            =  $rel_module;
		$class_attach->attach_post_key =  $post_key;
		$class_attach->attach_rel_id   =  $rel_id;
		$class_attach->init();
		
		$return = $class_attach->postProcessUpload( $args );
		
		return intval( $return['count'] );
	}
	
	/**
	 * Upload any attachments that were not handled by the flash or JS uploaders
	 *
	 * @access	public
	 * @param	string	$post_key
	 * @param	integer	$rel_id
	 * @return	array
	 **/
	public function uploadAttachments( $post_key, $rel_id )
	{
		/* Setup Attachment Handler */
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach                  = new class_attach( $this->registry );
		
		$class_attach->type            = 'post';
		$class_attach->attach_post_key = $post_key;
		$class_attach->attach_rel_id   = $rel_id;
		$class_attach->init();

		return $class_attach->processMultipleUploads();
	}	
	
	/**
	 * Recount how many attachments a topic has
	 *
	 * @access	public
	 * @param	integer	$tid
	 * @return	void
	 **/
	public function recountTopicAttachments( $tid=0 )
	{
		if ( $tid == "" )
		{
			return;
		}
		
		//-----------------------------------------
		// GET PIDS
		//-----------------------------------------
		
		$pids  = array();
		$count = 0;
		
		$this->DB->build( array( 'select' 	=> 'count(*) as cnt',
												 'from'		=> array( 'attachments' => 'a' ),
												 'where'	=> "a.attach_rel_module='post' AND p.topic_id={$tid}",
												 'add_join'	=> array(
																		0 => array(
																					'from'	=> array( 'posts' => 'p' ),
																					'where' => "p.pid=a.attach_rel_id",
																					'type'	=> 'left'
																				)
																		)
										)		);
		$this->DB->execute();
		
		$cnt = $this->DB->fetch();
		
		$count = intval( $cnt['cnt'] );
		
		$this->DB->build( array( 'update' => 'topics', 'set' => "topic_hasattach=$count", 'where' => "tid={$tid}" ) );
		$this->DB->execute();
	}
	
	/**
	 * Check out the tracker whacker
	 *
	 * @access	protected
	 * @param	integer	$tid
	 * @param	bool	$check_first
	 * @return	void
	 **/
	protected function addTopicToTracker( $tid=0, $check_first=0 )
	{
		if ( ! $tid )
		{
			return;
		}
		
		if ( $this->getAuthor('member_id') AND $this->getSettings('enableTracker') == 1 )
		{
			if ( $check_first )
			{
				$this->DB->build( array( 'select' => 'trid', 'from' => 'tracker', 'where' => "topic_id=".intval($tid)." AND member_id=".$this->getAuthor('member_id') ) );
				$this->DB->execute();
				
				$test = $this->DB->fetch();
				
				if ( $test['trid'] )
				{
					//-----------------------------------------
					// Already tracking...
					//-----------------------------------------
					
					return;
				}
			}
				
			$this->DB->insert( 'tracker', array(
											  'member_id'        => $this->getAuthor('member_id'),
											  'topic_id'         => $tid,
											  'start_date'       => time(),
											  'topic_track_type' => $this->getAuthor('auto_track') ? $this->getAuthor('auto_track') : 'delayed' ,
									)       );
		}
	}
	
	/**
	 * Clean the topic title
	 *
	 * @access	public
	 * @param	string	Raw title
	 * @return	string	Cleaned title
	 */
	public function cleanTopicTitle( $title="" )
	{
		if( $this->settings['etfilter_punct'] )
		{
			$title	= preg_replace( "/\?{1,}/"      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}

		//-----------------------------------------
		// The DB column is 250 chars, so we need to do true mb_strcut, then fix broken HTML entities
		// This should be fine, as DB would do it regardless (cept we can fix the entities)
		//-----------------------------------------

		$title = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $title, 0, 250 ) );
		
		$title = IPSText::stripAttachTag( $title );
		$title = str_replace( "<br />", "", $title  );
		$title = trim( $title );

		return $title;
	}
	
	/**
	 * Check Multi Quote
	 * Checks for quoted information
	 *
	 * @access	public
	 * @param	string	Any raw post
	 * @return	string	Formatted post
	 */
	protected function _checkMultiQuote( $postContent )
	{
		$raw_post = '';
		
		if ( ! $this->request['qpid'] )
		{
			$this->request['qpid'] = IPSCookie::get('mqtids');
			
			if ( $this->request['qpid'] == "," )
			{
				$this->request['qpid'] = "";
			}
		}
		else
		{
			//-----------------------------------------
			// Came from reply button
			//-----------------------------------------
			
			$this->request['parent_id'] = $this->request['qpid'];
		}

		$this->request['qpid'] = preg_replace( "/[^,\d]/", "", trim($this->request['qpid']) );

		if( $this->request['qpid'] )
		{
			$this->quoted_pids = preg_split( '/,/', $this->request['qpid'], -1, PREG_SPLIT_NO_EMPTY );
			
			//-----------------------------------------
			// Get the posts from the DB and ensure we have
			// suitable read permissions to quote them
			//-----------------------------------------
			
			if( count( $this->quoted_pids ) )
			{
				$this->DB->build( array( 
										'select' 	=> 'p.*' ,
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> "p.pid IN(" . implode( ',', $this->quoted_pids ) . ")",
										'add_join'	=> array( array( 'select'	=> 't.forum_id',
																	 'from'		=> array( 'topics' => 't' ),
																	 'where'	=> 't.tid=p.topic_id',
																	 'type'		=> 'left' ),
															  array( 'select'   => 'member_id, members_display_name',
																	 'from'     => array( 'members' => 'm' ),
																	 'where'    => 'p.author_id=m.member_id',
																	 'type'     => 'left' ) ) ) );
															
				$this->DB->execute();
				
				while( $tp = $this->DB->fetch() )
				{
					//if( IPSMember::checkPermissions('read', $this->getForumID() ) == TRUE )
					$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
					$perm_array = explode( ",", $perm_id );
			
					if ( $this->registry->permissions->check( 'read', $this->getForumData(), $perm_array ) === TRUE )
					{
						$tmp_post          = $this->_afterPostCompile( $tp['post'], 'reply' );
						$tp['author_name'] = ( $tp['members_display_name'] ) ? $tp['members_display_name'] : $tp['author_name'];
						
						/*if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
						{
							$tmp_post = IPSText::getTextClass( 'bbcode' )->convertForRTE(  $tp['post'] );
						}
						else
						{
							$tmp_post = trim( IPSText::getTextClass( 'bbcode' )->preEditParse( $tp['post'] ) );
						}*/

						if ( $this->settings['strip_quotes'] )
						{
							$tmp_post = trim( $this->_recursiveKillQuotes( $tmp_post ) );
						}

						$extra = "";
						
						if( $tmp_post )
						{
							if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
							{
								$raw_post .= "[quote name='" . IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($tp['author_name']) . "' date='" . IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($this->registry->getClass( 'class_localization')->getDate( $tp['post_date'], 'LONG', 1 )) . "' timestamp='" . $tp['post_date'] . "' post='" . $tp['pid'] . "']<br />{$tmp_post}<br />" . $extra . '[/quote]<br /><br /><br />';
							}
							else
							{
								/* Knocks out <br />  */
								$tmp_post = trim( IPSText::getTextClass( 'bbcode' )->preEditParse( $tmp_post ) );
								$raw_post .= "[quote name='" . IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($tp['author_name']) . "' date='" . IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($this->registry->getClass( 'class_localization')->getDate( $tp['post_date'], 'LONG', 1 )) . "' timestamp='" . $tp['post_date'] . "' post='" . $tp['pid'] . "']\n{$tmp_post}\n" . $extra . "[/quote]\n\n\n";
							}
						}
					}
				}
				
				$raw_post = trim($raw_post) . "\n";
			}
		}
		
		//-----------------------------------------
		// Need to put into RTE format..
		//-----------------------------------------
		
		if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
		{
			$raw_post = IPSText::getTextClass( 'bbcode' )->convertForRTE( $raw_post );
		}

		
		//-----------------------------------------
		// Make raw POST safe for the text area
		//-----------------------------------------

		$raw_post .= IPSText::raw2form( $postContent );
		
		return $raw_post;
	}
	
	
	/**
	 * Cheap and probably nasty way of killing quotes
	 *
	 * @access	private
	 * @return  string
	 */
	private function _recursiveKillQuotes( $t )
	{
		return IPSText::getTextClass( 'bbcode' )->stripQuotes( $t );
	}
}