<?php

/**
 * Show a mini-calendar widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class plugin_mini_calendar implements pluginBlockInterface
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
					'key'			=> 'mini_calendar',
					'name'			=> $this->lang->words['plugin_name__mini_calendar'],
					'description'	=> $this->lang->words['plugin_description__mini_calendar'],
					'hasConfig'		=> true,
					'templateBit'	=> 'block__mini_calendar',
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
		$options	= array();
		
		foreach( $this->cache->getCache('calendars') as $cal_id => $cal )
		{
			$options[]	= array( $cal['cal_id'], $cal['cal_title'] );
		}

		return array(
					array(
						'label'			=> $this->lang->words['plugin__cal_label1'],
						'description'	=> $this->lang->words['plugin__cal_desc1'],
						'field'			=> $this->registry->output->formDropdown( 'plugin__cal_calendar', $options, $session['config_data']['custom_config']['calendar'] ),
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
		$calId		= 0;
		$default	= 0;

		foreach( $this->cache->getCache('calendars') as $cal_id => $cal )
		{
			if( !$default )
			{
				$default	= $cal_id;
			}
			
			if( $cal_id == $data['plugin__cal_calendar'] )
			{
				$calId	= $cal_id;
			}
		}
		
		return array( true, array( 'calendar' => $calId ? $calId : $default ) );
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
		
		//-----------------------------------------
		// Grab calendar class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/calendars.php' );
		$calendar = new public_calendar_calendar_calendars( $this->registry );
		$calendar->makeRegistryShortcuts( $this->registry );

		//-----------------------------------------
        // Load lang and templs
        //-----------------------------------------
        
        $this->lang->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

 		//-----------------------------------------
 		// DO some set up
 		//-----------------------------------------
 		
 		$calendar->calendar_id = $config['custom']['calendar'];
 		
		if( ! count( $this->caches['calendars'] ) )
		{
			$cache	= array();
			
			$this->DB->build( array( 
									'select'	=> 'c.*', 
									'from'		=> array( 'cal_calendars' => 'c' ), 
									'add_join'	=> array(
														array(
																'select'	=> 'p.*',
																'from'		=> array( 'permission_index' => 'p' ),
																'where'		=> "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																'type'		=> 'left',
															)
														)	
						) 	);
			$this->DB->execute();
			
			while( $cal = $this->DB->fetch() )
			{
				$cache[ $cal['cal_id'] ] = $cal;
			}
			
			$this->cache->setCache( 'calendars', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
		}
		
		/* Calendar Cache */			
		if( count( $this->caches['calendars'] ) AND is_array( $this->caches['calendars'] ) )
		{
			foreach($this->caches['calendars'] as $cal_id => $cal )
			{
				$selected = "";
				
				/* Got a perm */
				if( ! $this->registry->permissions->check( 'view', $cal ) )
				{
					continue;
				}
								
				if ( $cal['cal_id'] == $calendar->calendar_id )
				{
					$calendar->calendar	= $cal;
					$selected			= " selected='selected'";
				}
				
				$calendar->calendar_cache[ $cal['cal_id'] ] = $cal;
			}
		}
		
		if( ! $calendar->calendar )
		{
			if( count( $calendar->calendar_cache ) )
			{
				$tmp_resort = $calendar->calendar_cache;
				ksort($tmp_resort);
				reset($tmp_resort);
				$default_calid = key( $tmp_resort );
				$calendar->calendar_id = $default_calid;
				$calendar->calendar = $tmp_resort[ $default_calid ];
				unset( $tmp_resort );
			}
		}
 		
		$calendar->calendarBuildPermissions();
		
		if( !is_array($calendar->calendar) OR !count($calendar->calendar) OR !$calendar->can_read )
		{
			return '';
		}

 		//-----------------------------------------
        // Finally, build up the lang arrays
        //-----------------------------------------
        
        $calendar->month_words = array( $this->lang->words['M_1'] , $this->lang->words['M_2'] , $this->lang->words['M_3'] ,
										$this->lang->words['M_4'] , $this->lang->words['M_5'] , $this->lang->words['M_6'] ,
										$this->lang->words['M_7'] , $this->lang->words['M_8'] , $this->lang->words['M_9'] ,
										$this->lang->words['M_10'], $this->lang->words['M_11'], $this->lang->words['M_12'] );
        							
		if( !$this->settings['ipb_calendar_mon'] )
		{
        	$calendar->day_words   = array( $this->lang->words['D_0'], $this->lang->words['D_1'], $this->lang->words['D_2'],
        								$this->lang->words['D_3'], $this->lang->words['D_4'], $this->lang->words['D_5'],
        								$this->lang->words['D_6'] );
    	}
    	else
    	{
        	$calendar->day_words   = array( $this->lang->words['D_1'], $this->lang->words['D_2'], $this->lang->words['D_3'],
        								$this->lang->words['D_4'], $this->lang->words['D_5'], $this->lang->words['D_6'],
        								$this->lang->words['D_0'] );
		}
 		
 		//-----------------------------------------
 		// What now?
 		//-----------------------------------------
 		
 		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->lang->getTimeOffset() ) );
		
		$now_date = array(
						  'year'    => $a[0],
						  'mon'     => $a[1],
						  'mday'    => $a[2],
						  'hours'   => $a[3],
						  'minutes' => $a[4],
						  'seconds' => $a[5]
						);
							   
 		$content = $calendar->getMiniCalendar( $now_date['mon'], $now_date['year'] );
 		
		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
 		return $this->registry->getClass('output')->getTemplate('ccs')->$templateBit( $content );
	}
}