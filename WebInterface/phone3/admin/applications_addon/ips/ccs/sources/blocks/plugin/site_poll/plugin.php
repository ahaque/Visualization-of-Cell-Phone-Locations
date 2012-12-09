<?php

/**
 * Show a poll widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class plugin_site_poll implements pluginBlockInterface
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
					'key'			=> 'site_poll',
					'name'			=> $this->lang->words['plugin_name__site_poll'],
					'description'	=> $this->lang->words['plugin_description__site_poll'],
					'hasConfig'		=> true,
					'templateBit'	=> 'block__site_poll',
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
		return array(
					array(
						'label'			=> $this->lang->words['plugin__poll_label1'],
						'description'	=> $this->lang->words['plugin__poll_desc1'],
						'field'			=> $this->registry->output->formInput( 'plugin__poll', $session['config_data']['custom_config']['poll'] ),
						)
					);
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
		return array( $data['plugin__poll'] ? true : false, array( 'poll' => $data['plugin__poll'] ) );
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
		$config	= unserialize($block['block_config']);
		
		if( !$config['custom']['poll'] )
		{
			return '';
		}
		
		/* Friendly URL */
		if( $this->settings['use_friendly_urls'] )
		{
			preg_match( "#/topic/(\d+)(.*?)/#", $config['custom']['poll'], $match );
			$tid = intval( trim( $match[1] ) );
		}
		/* Normal URL */
		else
		{
			preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $config['custom']['poll'], $match );
			$tid = intval( trim( $match[3] ) );
		}

		if( !$tid )
		{
			return '';
		}

		$poll	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => 'tid=' . $tid ) );
		
		if( !$poll['pid'] )
		{
			return '';
		}

		$this->lang->loadLanguageFile( array( 'public_boards', 'public_topic' ), 'forums' );
		$this->lang->loadLanguageFile( array( 'public_editors' ), 'core' );

		require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
		$this->registry->setClass( 'class_forums', new class_forums( $this->registry ) );

		require_once( IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/topics.php' );
		$topic = new public_forums_forums_topics();
		$topic->makeRegistryShortcuts( $this->registry );

		$topic->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from'   => 'topics', 'where'  => "tid=" . $poll['tid'] ) );
		$topic->forum = ipsRegistry::getClass('class_forums')->forum_by_id[ $topic->topic['forum_id'] ];
		
		$this->request['f'] = $topic->forum['id'];
		$this->request['t'] = $poll['tid'];
		
		if ( $topic->topic['poll_state'] )
		{
			$pluginConfig	= $this->returnPluginInfo();
			$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
 			return $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $topic->_generatePollOutput(), $tid );
 		}
 		else
 		{
 			return;
 		}
	}
}