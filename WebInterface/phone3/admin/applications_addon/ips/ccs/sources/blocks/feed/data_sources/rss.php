<?php
/**
 * RSS feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_rss implements feedBlockInterface
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
	 * RSS kernel lib
	 *
	 * @access	public
	 * @var		object
	 */
	public $class_rss;
	
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
		return array(
					'key'			=> 'rss',
					'app'			=> '',
					'name'			=> $this->lang->words['feed_name__rss'],
					'description'	=> $this->lang->words['feed_description__rss'],
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
							array( 'rss', $this->lang->words['ct_rss_feed'] ),
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
		if( !in_array( $data['content_type'], array( 'rss' ) ) )
		{
			$data['content_type']	= 'rss';
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
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__feed'],
							'description'	=> $this->lang->words['feed_rss__feed_desc'],
							'field'			=> $this->registry->output->formInput( 'rss_feed_url', $session['config_data']['filters']['rss_feed'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__charset'],
							'description'	=> $this->lang->words['feed_rss__charset_desc'],
							'field'			=> $this->registry->output->formInput( 'rss_charset', $session['config_data']['filters']['rss_charset'] ) .
												" <input type='button' onclick='return acp.ccs.fetchEncoding();' value='{$this->lang->words['fetch_feed_encoding']}' />",
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__limit'],
							'description'	=> $this->lang->words['feed_rss__limit_desc'],
							'field'			=> $this->registry->output->formInput( 'rss_limit', $session['config_data']['filters']['rss_limit'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__html'],
							'description'	=> $this->lang->words['feed_rss__html_desc'],
							'field'			=> $this->registry->output->formYesNo( 'rss_html', $session['config_data']['filters']['rss_html'] ),
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
		return array( true, array(
								'rss_feed'		=> $this->request['rss_feed_url'],
								'rss_charset'	=> $this->request['rss_charset'],
								'rss_limit'		=> intval($this->request['rss_limit']),
								'rss_html'		=> intval($this->request['rss_html']),
					) 			);
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
		return array();
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
		return array( true, array() );
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

		//-----------------------------------------
		// Init RSS kernel library
		//-----------------------------------------
		
		if ( ! is_object( $this->class_rss ) )
		{
			require_once( IPS_KERNEL_PATH . 'classRss.php' );
			$this->class_rss               =  new classRss();
			
			$this->class_rss->use_sockets	= ipsRegistry::$settings['enable_sockets'];
			$this->class_rss->doc_type 		= IPS_DOC_CHAR_SET;
		}
		
		$this->class_rss->feed_charset	= $config['filters']['rss_charset'];
		
		if( strtolower( $config['rss_charset'] ) != IPS_DOC_CHAR_SET )
		{
			$this->class_rss->convert_charset		= 1;
			$this->class_rss->destination_charset	= $this->class_rss->doc_type;
		}
		else
		{
			$this->class_rss->convert_charset = 0;
		}
		
		$this->class_rss->errors		= array();
		$this->class_rss->rss_items		= array();
		$this->class_rss->auth_req		= '';
		$this->class_rss->auth_user		= '';
		$this->class_rss->auth_pass		= '';
		$this->class_rss->rss_count		= 0;
		$this->class_rss->rss_max_show	= $config['filters']['rss_limit'];
		
		//-----------------------------------------
		// Get feed
		//-----------------------------------------

		$this->class_rss->parseFeedFromUrl( $config['filters']['rss_feed'] );

		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
		{
			return '';
		}
		
		if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
		{
			return '';
		}
		
		if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
		{
			return '';
		}
		
		//-----------------------------------------
		// Loop over items and put into array
		//-----------------------------------------
		
		$content	= array();
		
		foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
		{
			if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
			{
				foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
				{
					//-----------------------------------------
					// Check basic data
					//-----------------------------------------
					
					$item_data['content']	= $item_data['content']   ? $item_data['content']  : $item_data['description'];
					$item_data['url']		= $item_data['link'];
					$item_data['date']		= intval($item_data['unixdate'])  ? intval($item_data['unixdate']) : time();

					//-----------------------------------------
					// Convert charset
					//-----------------------------------------
					
					if ( $config['rss_charset'] AND ( strtolower(IPS_DOC_CHAR_SET) != strtolower($config['filters']['rss_charset']) ) )
					{
						$item_data['title']   = IPSText::convertCharsets( $item_data['title']  , $config['filters']['rss_charset'], IPS_DOC_CHAR_SET );
						$item_data['content'] = IPSText::convertCharsets( $item_data['content'], $config['filters']['rss_charset'], IPS_DOC_CHAR_SET );
					}

					//-----------------------------------------
					// Dates
					//-----------------------------------------
					
					if ( $item_data['date'] < 1 )
					{
						$item_data['date'] = time();
					}
					else if ( $item_data['date'] > time() )
					{
						$item_data['date'] = time();
					}

					//-----------------------------------------
					// Got stuff?
					//-----------------------------------------
					
					if ( ! $item_data['title'] OR ! $item_data['content'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Strip html if needed
					//-----------------------------------------
					
					if( $config['filters']['rss_html'] )
					{
						$item_data['title']		= strip_tags($item_data['title']);
						$item_data['content']	= strip_tags($item_data['content']);
					}

					$content[]	= $item_data;
				}
			}
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		
		if( $config['hide_empty'] AND !count($content) )
		{
			return '';
		}		
		
		return $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $content );
	}
}