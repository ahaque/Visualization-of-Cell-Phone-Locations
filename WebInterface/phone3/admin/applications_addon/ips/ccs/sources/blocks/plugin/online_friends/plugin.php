<?php

/**
 * Online friends plugin
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class plugin_online_friends implements pluginBlockInterface
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
	protected $memberData;
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
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
		$this->memberData	=& $this->registry->member()->fetchMemberData();
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
					'key'			=> 'online_friends',
					'name'			=> $this->lang->words['plugin_name__online_friends'],
					'description'	=> $this->lang->words['plugin_description__online_friends'],
					'hasConfig'		=> false,
					'templateBit'	=> 'block__online_friends',
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
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );
		
		$friends		= array();
		$onlineFriends	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $this->memberData['member_id'] ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$friends[ $r['friends_friend_id'] ]	= $r['friends_friend_id'];
		}

		if( count($friends) )
		{
			$_time		= $this->settings['au_cutoff'] * 60;
			$_cutoff	= time() - $_time;

			$this->DB->build( array(
									'select'	=> 's.*',
									'from'		=> array( 'sessions' => 's' ),
									'where'		=> 's.member_id IN(' . implode( ',', $friends ) . ') AND s.running_time > ' . $_cutoff,
									'order'		=> 's.running_time DESC',
									'add_join'	=> array(
														array(
															'select'	=> 'm.*, m.member_id as my_member_id',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=s.member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pf.*',
															'from'		=> array( 'pfields_content' => 'pf' ),
															'where'		=> 'pf.member_id=m.member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left',
															),
														)
							)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$r['member_id']	= $r['my_member_id'];
				
				$r	= IPSMember::buildDisplayData( $r );

				$onlineFriends[ $r['member_id'] ]	= $r;
			}
		}

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		return $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $onlineFriends );
	}
}