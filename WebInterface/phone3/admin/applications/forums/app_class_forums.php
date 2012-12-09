<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forums application initialization
 * Last Updated: $LastChangedDate: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		14th May 2003
 * @version		$Rev: 4948 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_forums
{
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
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		
		if ( IN_ACP )
		{
			try
			{
				require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
				require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/admin_forum_functions.php" );
				
				$this->registry->setClass( 'class_forums', new admin_forum_functions( $registry ) );
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
		else
		{
			try
			{
				require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
				$this->registry->setClass( 'class_forums', new class_forums( $registry ) );
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
		
		//---------------------------------------------------
		// Grab and cache the topic now as we need the 'f' attr for
		// the skins...
		//---------------------------------------------------
		
		if ( isset( $_GET['showtopic'] ) AND $_GET['showtopic'] != '' )
		{
			$this->request['t'] = intval( $_GET['showtopic']  );
			
			if ( $this->settings['cpu_watch_update'] AND $this->memberData['member_id'] )
			{
				$this->DB->build( array( 'select' => 't.*',
										 'from'   => array( 'topics' => 't' ),
										 'where'  => 't.tid=' . $this->request['t'],
										 'add_join' => array( array( 'select' => 'w.trid as trackingTopic',
																	 'from'   => array( 'tracker' => 'w' ),
																	 'where'  => 'w.topic_id=t.tid AND w.member_id=' . $this->memberData['member_id'],
																	 'type'   => 'left' ) ) ) );
				$this->DB->execute();
				
				$topic = $this->DB->fetch();
			}
			else
			{
				$topic = $this->DB->buildAndFetch( array( 'select' => '*',
														  'from'   => 'topics',
														  'where'  => "tid=" . $this->request['t'],
												)      );
			}
											
			$this->registry->getClass('class_forums')->topic_cache = $topic;
	   
		    $this->request['f'] =  $topic['forum_id'];
			
			/* Update query location */
			$this->member->sessionClass()->addQueryKey( 'location_2_id', ipsRegistry::$request['f'] );
		}
		
		$this->registry->getClass('class_forums')->strip_invisible = 1;
		$this->registry->getClass('class_forums')->forumsInit();
		
		//-----------------------------------------
		// Set up moderators
		//-----------------------------------------
		
		$this->memberData = $this->registry->getClass('class_forums')->setUpModerator( $this->memberData );
	}
	
	/**
	* Do some set up after ipsRegistry::init()
	*
	* @access	public
	*/
	public function afterOutputInit()
	{
		if ( isset( $_GET['showtopic'] ) AND $_GET['showtopic'] != '' AND is_array( $this->registry->getClass('class_forums')->topic_cache ) )
		{
			$topic = $this->registry->getClass('class_forums')->topic_cache;
			$topic['title_seo'] = ( $topic['title_seo'] ) ? $topic['title_seo'] : IPSText::makeSeoTitle( $topic['title'] );
			
			/* Check TOPIC permalink... */
			$this->registry->getClass('output')->checkPermalink( $topic['title_seo'] );
			
			/* Add canonical tag */
			$this->registry->getClass('output')->addCanonicalTag( ( $this->request['st'] ) ? 'showtopic=' . $topic['tid'] . '&st=' . $this->request['st'] : 'showtopic=' . $topic['tid'], $topic['title_seo'], 'showtopic' );
		}
		else if ( isset( $_GET['showforum'] ) AND $_GET['showforum'] != '' )
		{
			$data             = $this->registry->getClass('class_forums')->forumsFetchData( $_GET['showforum'] );
			$data['name_seo'] = ( $data['name_seo'] ) ? $data['name_seo'] : IPSText::makeSeoTitle( $data['name'] );
			
			/* Check FORUM permalink... */
			$this->registry->getClass('output')->checkPermalink( $data['name_seo'] );
			
			/* Add canonical tag */
			$this->registry->getClass('output')->addCanonicalTag( ( $this->request['st'] ) ? 'showforum=' . $data['id'] . '&st=' . $this->request['st'] : 'showforum=' . $data['id'], $data['name_seo'], 'showforum' );
			
		}
	}
}