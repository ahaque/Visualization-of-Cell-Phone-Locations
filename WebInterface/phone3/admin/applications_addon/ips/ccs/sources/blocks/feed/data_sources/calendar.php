<?php
/**
 * Calendar feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_calendar implements feedBlockInterface
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
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		if( !IPSLib::appIsInstalled('calendar') )
		{
			return array();
		}
		
		return array(
					'key'			=> 'calendar',
					'app'			=> 'calendar',
					'name'			=> $this->lang->words['feed_name__calendar'],
					'description'	=> $this->lang->words['feed_description__calendar'],
					'hasFilters'	=> true,
					'templateBit'	=> 'feed__generic'
					);
	}
	
	/**
	 * Get the feed's available content types.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnContentTypes( $session )
	{
		$options	= array(
							array( 'events', $this->lang->words['ct_events'] ),
							);
		return array(
					array(
						'label'			=> $this->lang->words['generic__select_contenttype'],
						'description'	=> $this->lang->words['generic__desc_contenttype'],
						'field'			=> $this->registry->output->formDropdown( 'content_type', $options, $session['config_data']['content_type'] ),
						)
					);
	}
	
	/**
	 * Check the feed content type selection
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		if( !in_array( $data['content_type'], array( 'events' ) ) )
		{
			$data['content_type']	= 'events';
		}

		return array( true, $data['content_type'] );
	}
	
	/**
	 * Get the feed's available filter options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnFilters( $session )
	{
		$filters	= array();

		$calendars	= array();
		
		foreach( $this->cache->getCache('calendars') as $calendar )
		{
			$calendars[]	= array( $calendar['cal_id'], $calendar['cal_title'] );
		}

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_calendar__calendars'],
							'description'	=> $this->lang->words['feed_calendar__calendars_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_calendars[]', $calendars, explode( ',', $session['config_data']['filters']['filter_calendars'] ), 8 ),
							);

		$session['config_data']['filters']['filter_start']			= $session['config_data']['filters']['filter_start'] ? $session['config_data']['filters']['filter_start'] : 0;
		$session['config_data']['filters']['filter_end']			= $session['config_data']['filters']['filter_end'] ? $session['config_data']['filters']['filter_end'] : 0;
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_calendar__start'],
							'description'	=> $this->lang->words['feed_calendar__start_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_start', $session['config_data']['filters']['filter_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_calendar__end'],
							'description'	=> $this->lang->words['feed_calendar__end_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_end', $session['config_data']['filters']['filter_end'] ),
							);

		return $filters;
	}
	
	/**
	 * Check the feed filters selection
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedFilters( $data )
	{
		$filters	= array();
		$return		= true;
		
		$filters['filter_calendars']	= is_array($data['filter_calendars']) ? implode( ',', $data['filter_calendars'] ) : '';
		$filters['filter_start']		= $data['filter_start'];
		$filters['filter_end']			= $data['filter_end'];

		//-----------------------------------------
		// Verify we can create a timestamp out of the dates
		//-----------------------------------------
		
		if( ( $filters['filter_start'] AND !@strtotime($filters['filter_start']) ) OR ($filters['filter_end'] AND !@strtotime($filters['filter_end']) ) )
		{
			$return	= false;
		}

		return array( $return, $filters );
	}
	
	/**
	 * Get the feed's available ordering options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnOrdering( $session )
	{
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;
		$session['config_data']['sortby']		= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'start';

		$filters	= array();

		$sortby	= array( 
						array( 'title', $this->lang->words['sort_calendar__title'] ), 
						array( 'start', $this->lang->words['sort_calendar__start'] ), 
						array( 'end', $this->lang->words['sort_calendar__end'] ),
						array( 'rand', $this->lang->words['sort_generic__rand'] )
						);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_sort_by'],
							'description'	=> $this->lang->words['feed_sort_by_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
							);
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_order_direction'],
							'description'	=> $this->lang->words['feed_order_direction_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortorder', array( array( 'desc', 'DESC' ), array( 'asc', 'ASC' ) ), $session['config_data']['sortorder'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_start'],
							'description'	=> $this->lang->words['feed_limit_offset_start_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_start', $session['config_data']['offset_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_end'],
							'description'	=> $this->lang->words['feed_limit_offset_end_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_end', $session['config_data']['offset_end'] ),
							);
		
		return $filters;
	}
	
	/**
	 * Check the feed ordering options
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Ordering data to use )
	 */
	public function checkFeedOrdering( $data )
	{
		$limits						= array();
		$sortby						= array( 'title', 'start', 'end', 'rand' );
		
		$limits['sortby']			= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'title';
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		return array( true, $limits );
	}
	
	/**
	 * Execute the feed and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executeFeed( $block )
	{
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );
		
		$config	= unserialize( $block['block_config'] );
		$where	= array();
		
		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------
		
		$where		= array( 'e.event_approved=1' );
		$where[]	= 'e.event_private=0';
		
		if( $config['filters']['filter_calendars'] )
		{
			$where[]	= "e.event_calendar_id IN(" . $config['filters']['filter_calendars'] . ")";
		}
		
		if( $config['filters']['filter_start'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_start'] );
			
			if( $timestamp )
			{
				$where[]	= "e.event_unix_from > " . $timestamp;
			}
		}
		
		if( $config['filters']['filter_end'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_end'] );
			
			if( $timestamp )
			{
				$where[]	= "e.event_unix_from < " . $timestamp;
			}
		}
		
		$order	= '';
		
		switch( $config['sortby'] )
		{
			case 'title':
				$order	.=	"e.event_title ";
			break;

			default:
			case 'start':
				$order	.=	"e.event_unix_from ";
			break;
			
			case 'end':
				$order	.=	"e.event_unix_to ";
			break;
			
			case 'rand':
				$order	.=	"RAND() ";
			break;
		}
		
		$order	.= $config['sortorder'];
		
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$events	= array();
		
		$this->DB->build( array(
								'select'	=> 'e.*',
								'from'		=> array( 'cal_events' => 'e' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'c.*',
														'from'		=> array( 'cal_calendars' => 'c' ),
														'where'		=> 'c.cal_id=e.event_calendar_id',
														'type'		=> 'left',
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Normalization
			//-----------------------------------------
			
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=calendar&amp;module=calendar&amp;cal_id=' . $r['cal_id'] . '&amp;do=showevent&amp;event_id=' . $r['event_id'], 'none' );
			$r['title']		= $r['event_title'];
			$r['date']		= $r['event_unix_from'];
			$r['content']	= $r['event_content'];
			
			IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= intval( $r['event_smilies'] );
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
			
			$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
			
			$events[]		= $r;
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];

		if( $config['hide_empty'] AND !count($events) )
		{
			return '';
		}
		
		return $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $events );
	}
}