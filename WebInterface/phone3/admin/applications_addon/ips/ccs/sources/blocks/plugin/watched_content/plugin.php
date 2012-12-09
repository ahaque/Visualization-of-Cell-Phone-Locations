<?php

/**
 * Show watched content widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class plugin_watched_content implements pluginBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $cache;
	protected $registry;
	protected $caches;
	protected $request;
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
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo()
	{
		return array(
					'key'			=> 'watched_content',
					'name'			=> $this->lang->words['plugin_name__watched_content'],
					'description'	=> $this->lang->words['plugin_description__watched_content'],
					'hasConfig'		=> false,
					'templateBit'	=> 'block__watched_content',
					);
	}
	
	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig( $session )
	{
		return array();
	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig( $data )
	{
		return array( true, $data );
	}
	
	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin( $block )
	{
		$this->lang->loadLanguageFile( array( 'public_boards' ), 'forums' );

		if( !$this->memberData['member_id'] )
		{
			return '';
		}

		/* INIT */
		$updatedTopics	= array();
		$updatedForums	= array();
		$nUpdatedTopics	= array();
		$nUpdatedForums	= array();
		
		/* Get watched topics */
		$this->DB->build( array(
								'select'	=> 'tr.*',
								'from'		=> array( 'tracker' => 'tr' ),
								'where'		=> 'tr.member_id=' . $this->memberData['member_id'],
								'add_join'	=> array(
													array(
														'select'	=> 't.*',
														'from'		=> array( 'topics' => 't' ),
														'where'		=> 't.tid=tr.topic_id',
														'type'		=> 'left'
														),
													array(
														'select'	=> 'm.members_seo_name',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=t.starter_id',
														'type'		=> 'left'
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$is_read	= $this->registry->classItemMarking->isRead( array( 'forumID' => $r['forum_id'], 'itemID' => $r['tid'], 'itemLastUpdate' => $r['last_post'] ), 'forums' );
			
			if( !$is_read )
			{
				$updatedTopics[ $r['topic_id'] ]	= $r;
			}
			else
			{
				$nUpdatedTopics[ $r['topic_id'] ]	= $r;
			}
		}
		
		/* Get watched forums */
		$this->DB->build( array(
								'select'	=> 'tr.*',
								'from'		=> array( 'forum_tracker' => 'tr' ),
								'where'		=> 'tr.member_id=' . $this->memberData['member_id'] . ' AND f.hide_last_info=0',
								'add_join'	=> array(
													array(
														'select'	=> 'f.*',
														'from'		=> array( 'forums' => 'f' ),
														'where'		=> 'f.id=tr.forum_id',
														'type'		=> 'left'
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$last_time	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $r['forum_id'] ), 'forums' );
			
			if( $r['last_post'] > $last_time )
			{
				$updatedForums[ $r['forum_id'] ]	= $r;
			}
			else
			{
				$nUpdatedForums[ $r['forum_id'] ]	= $r;
			}
		}

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		return $this->registry->output->getTemplate('ccs')->$templateBit( $updatedTopics, $nUpdatedTopics, $updatedForums, $nUpdatedForums );
	}
}