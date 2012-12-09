<?php
/**
 * Gallery feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_gallery implements feedBlockInterface
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
		$this->memberData	=& $this->registry->member()->fetchMemberData();
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
		if( !IPSLib::appIsInstalled('gallery') )
		{
			return array();
		}
		
		return array(
					'key'			=> 'gallery',
					'app'			=> 'gallery',
					'name'			=> $this->lang->words['feed_name__gallery'],
					'description'	=> $this->lang->words['feed_description__gallery'],
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
							array( 'images', $this->lang->words['ct_gal_images'] ),
							array( 'comments', $this->lang->words['ct_gal_comments'] ),
							array( 'cats', $this->lang->words['ct_gal_cats'] ),
							array( 'albums', $this->lang->words['ct_gal_albums'] ),
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
		if( !in_array( $data['content_type'], array( 'cats', 'images', 'comments', 'albums' ) ) )
		{
			$data['content_type']	= 'images';
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
		
		//-----------------------------------------
		// For all the content types, we allow to filter by forums
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'gallery' ) .'/app_class_gallery.php' );
		$app_class 	= new app_class_gallery( $this->registry );
		
		/* Load the category object */
		require_once( GALLERY_LIBS . 'lib_categories.php' );
		$this->registry->setClass( 'category', new lib_categories( $this->registry ) );
		$this->registry->category->normalInit();
		
		$this->registry->glib->category = $this->registry->category;
		
		$album_cache	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums' ) );
		$this->DB->execute();
			
		while( $data = $this->DB->fetch() )
		{
			$album_cache[] = array( $data['id'], $data['name'] );
		}
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_gal__cats'],
							'description'	=> $this->lang->words['feed_gal__cats_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_cats[]', $this->registry->category->catJumpList( true ), explode( ',', $session['config_data']['filters']['filter_cats'] ), 10 ),
							);
							
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_gal__albums'],
							'description'	=> $this->lang->words['feed_gal__albums_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_albums[]', $album_cache, explode( ',', $session['config_data']['filters']['filter_albums'] ), 10 ),
							);

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_media']		= $session['config_data']['filters']['filter_media'] ? $session['config_data']['filters']['filter_media'] : 0;
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';
				$session['config_data']['filters']['filter_submitter']	= $session['config_data']['filters']['filter_submitter'] ? $session['config_data']['filters']['filter_submitter'] : '';
				
				$visibility	= array( array( 'open', $this->lang->words['gal_status__open'] ), array( 'closed', $this->lang->words['gal_status__closed'] ), array( 'either', $this->lang->words['gal_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__visibility'],
									'description'	=> $this->lang->words['feed_gal__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__media'],
									'description'	=> $this->lang->words['feed_gal__media_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_media', $session['config_data']['filters']['filter_media'] ),
									);
									
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__posted'],
									'description'	=> $this->lang->words['feed_gal__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__submitter'],
									'description'	=> $this->lang->words['feed_gal__submitter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitter', $session['config_data']['filters']['filter_submitter'] ),
									);
			break;
			
			case 'comments':
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';

				$visibility	= array( array( 'open', $this->lang->words['galc_status__open'] ), array( 'closed', $this->lang->words['galc_status__closed'] ), array( 'either', $this->lang->words['galc_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_galc__visibility'],
									'description'	=> $this->lang->words['feed_galc__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_galc__posted'],
									'description'	=> $this->lang->words['feed_galc__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);
			break;
			
			case 'cats':
				$session['config_data']['filters']['filter_root']	= $session['config_data']['filters']['filter_root'] ? $session['config_data']['filters']['filter_root'] : 1;

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__root'],
									'description'	=> $this->lang->words['feed_gal__root_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_root', $session['config_data']['filters']['filter_root'] ),
									);
			break;
			
			case 'albums':
				$session['config_data']['filters']['filter_public']	= $session['config_data']['filters']['filter_public'] ? $session['config_data']['filters']['filter_public'] : 1;
				$session['config_data']['filters']['filter_owner']	= $session['config_data']['filters']['filter_owner'] ? $session['config_data']['filters']['filter_owner'] : '';

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__publica'],
									'description'	=> $this->lang->words['feed_gal__publica_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_public', $session['config_data']['filters']['filter_public'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__owner'],
									'description'	=> $this->lang->words['feed_gal__owner_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_owner', $session['config_data']['filters']['filter_owner'] ),
									);
			break;
		}
		
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
		
		$filters['filter_cats']		= is_array($data['filter_cats']) ? implode( ',', $data['filter_cats'] ) : '';
		$filters['filter_albums']	= is_array($data['filter_albums']) ? implode( ',', $data['filter_albums'] ) : '';

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_media']		= $data['filter_media'] ? $data['filter_media'] : 0;
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
				$filters['filter_submitter']	= $data['filter_submitter'] ? $data['filter_submitter'] : '';
			break;
			
			case 'comments':
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
			break;
			
			case 'cats':
				$filters['filter_root']			= $data['filter_root'] ? $data['filter_root'] : 1;
			break;

			case 'albums':
				$filters['filter_public']		= $data['filter_public'] ? $data['filter_public'] : 1;
				$filters['filter_owner']		= $data['filter_owner'] ? $data['filter_owner'] : '';
			break;
		}
		
		return array( true, $filters );
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

		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'submitted';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_gal__title'] ), 
								array( 'filename', $this->lang->words['sort_gal__filename'] ), 
								array( 'views', $this->lang->words['sort_gal__views'] ), 
								array( 'comments', $this->lang->words['sort_gal__comments'] ), 
								array( 'submitted', $this->lang->words['sort_gal__submitted'] ),
								array( 'lastcomment', $this->lang->words['sort_gal__lastcomment'] ),
								array( 'size', $this->lang->words['sort_gal__size'] ),
								array( 'rate', $this->lang->words['sort_gal__rate'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'comments':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'post_date';

				$sortby	= array( 
								array( 'post_date', $this->lang->words['sort_galc__postdate'] ), 
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'cats':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_galcat__name'] ), 
								array( 'files', $this->lang->words['sort_galcat__files'] ), 
								array( 'last_file', $this->lang->words['sort_galcat__lastdate'] ),
								array( 'position', $this->lang->words['sort_galcat__position'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'albums':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_gala__name'] ), 
								array( 'files', $this->lang->words['sort_gala__files'] ), 
								array( 'comments', $this->lang->words['sort_gala__comments'] ), 
								array( 'last_file', $this->lang->words['sort_gala__lastdate'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
		}
		
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
		$limits		= array();
		
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$sortby	= array( 'title', 'filename', 'views', 'comments', 'submitted', 'lastcomment', 'size', 'rate', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'submitted';
			break;
			
			case 'comments':
				$sortby					= array( 'post_date' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'post_date';
			break;
			
			case 'cats':
				$sortby					= array( 'name', 'last_file', 'files', 'position', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'position';
			break;

			case 'albums':
				$sortby					= array( 'name', 'last_file', 'files', 'comments', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'name';
			break;
		}
		
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

		switch( $config['content'] )
		{
			case 'images':
				if( $config['filters']['filter_cats'] )
				{
					$where[]	= "i.category_id IN(" . $config['filters']['filter_cats'] . ")";
				}

				if( $config['filters']['filter_albums'] )
				{
					$where[]	= "i.album_id IN(" . $config['filters']['filter_albums'] . ")";
				}

				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "i.approved=" . ( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "i.idate > " . $timestamp;
					}
				}
				
				if( $config['filters']['filter_submitter'] == 'myself' )
				{
					$where[]	= "i.member_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_submitter'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
					$pageBuilder	= new pageBuilder( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "i.member_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_submitter'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_submitter'], 'basic' );
					
					if( $member['member_id'] )
					{
						$where[]	= "i.member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;
			
			case 'comments':
				if( $config['filters']['filter_cats'] )
				{
					$where[]	= "i.category_id IN(" . $config['filters']['filter_cats'] . ")";
				}

				if( $config['filters']['filter_albums'] )
				{
					$where[]	= "i.album_id IN(" . $config['filters']['filter_albums'] . ")";
				}

				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "c.approved=" .( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}
				
				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "c.post_date > " . $timestamp;
					}
				}
			break;
			
			case 'cats':
				if( $config['filters']['filter_cats'] )
				{
					$where[]	= "c.id IN(" . $config['filters']['filter_cats'] . ")";
				}

				if( $config['filters']['filter_root'] )
				{
					$where[]	= "c.parent < 1";
				}
			break;

			case 'albums':
				if( $config['filters']['filter_albums'] )
				{
					$where[]	= "a.id IN(" . $config['filters']['filter_albums'] . ")";
				}

				if( $config['filters']['filter_public'] )
				{
					$where[]	= "a.public_album=1";
				}

				if( $config['filters']['filter_owner'] == 'myself' )
				{
					$where[]	= "a.member_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_owner'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
					$pageBuilder	= new pageBuilder( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "a.member_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_owner'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_owner'], 'basic' );
					
					if( $member['member_id'] )
					{
						$where[]	= "a.member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;
		}

		$order	= '';

		switch( $config['content'] )
		{
			case 'images':
				switch( $config['sortby'] )
				{
					case 'title':
						$order	.=	"i.caption ";
					break;
		
					case 'filename':
						$order	.=	"i.file_name ";
					break;
					
					case 'views':
						$order	.=	"i.views ";
					break;
		
					case 'comments':
						$order	.=	"i.comments ";
					break;

					default:
					case 'submitted':
						$order	.=	"i.idate ";
					break;

					case 'lastcomment':
						$order	.=	"i.lastcomment ";
					break;

					case 'size':
						$order	.=	"i.file_size ";
					break;

					case 'rate':
						$order	.=	"i.rating ";
					break;

					case 'rand':
						$order	.=	"RAND() ";
					break;
				}
			break;
			
			case 'comments':
				switch( $config['sortby'] )
				{
					default:
					case 'post_date':
						$order	.=	"p.post_date ";
					break;
				}
			break;

			case 'cats':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"c.name ";
					break;

					default:		
					case 'last_file':
						$order	.=	"c.last_pic_date ";
					break;
					
					case 'files':
						$order	.=	"c.images ";
					break;

					case 'position':
						$order	.=	"c.c_order ";
					break;

					case 'rand':
						$order	.=	"RAND() ";
					break;
				}
			break;

			case 'albums':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"a.name ";
					break;
		
					default:
					case 'last_file':
						$order	.=	"a.last_pic_date ";
					break;
					
					case 'files':
						$order	.=	"a.images ";
					break;
		
					case 'comments':
						$order	.=	"a.comments ";
					break;

					case 'rand':
						$order	.=	"RAND() ";
					break;
				}
			break;
		}
		
		$order	.= $config['sortorder'];
		
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$content	= array();

		switch( $config['content'] )
		{
			case 'images':
				$this->DB->build( array(
										'select'	=> 'i.id as imgid, i.*',
										'from'		=> array( 'gallery_images' => 'i' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'c.id as cid, c.*',
																'from'		=> array( 'gallery_categories' => 'c' ),
																'where'		=> 'c.id=i.category_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'a.id as aid, a.*',
																'from'		=> array( 'gallery_albums' => 'a' ),
																'where'		=> 'a.id=i.album_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=i.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id',
																'type'		=> 'left',
																),
															)
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					$r['album_id']	= $r['aid'];
					$r['cat_id']	= $r['cid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;module=images&amp;section=viewimage&amp;img=' . $r['imgid'], 'none' );
					$r['date']		= $r['idate'];
					$r['content']	= $r['description'];
					$r['title']		= $r['caption'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'gallery_image';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					
					$content[]		= $r;
				}
			break;
			
			case 'comments':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'gallery_comments' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'i.id as imgid, i.*',
																'from'		=> array( 'gallery_images' => 'i' ),
																'where'		=> 'i.id=c.img_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'c.id as cid, c.*',
																'from'		=> array( 'gallery_categories' => 'c' ),
																'where'		=> 'c.id=i.category_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'a.id as aid, a.*',
																'from'		=> array( 'gallery_albums' => 'a' ),
																'where'		=> 'a.id=i.album_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=c.author_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id',
																'type'		=> 'left',
																),
															)
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					$r['album_id']	= $r['aid'];
					$r['cat_id']	= $r['cid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;module=images&amp;section=viewimage&amp;img=' . $r['imgid'], 'none' );
					$r['date']		= $r['post_date'];
					$r['content']	= $r['comment'];
					$r['title']		= $r['caption'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html				= ( $r['allow_html'] AND $this->caches['group_cache'][ $poster['member_group_id'] ]['g_dohtml'] ) ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'gallery_comment';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					
					$content[]		= $r;
				}
			break;
			
			case 'cats':
				$this->DB->build( array(
										'select'	=> 'c.id as cid, c.description as cdescription, c.*',
										'from'		=> array( 'gallery_categories' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'i.id as imgid, i.*',
																'from'		=> array( 'gallery_images' => 'i' ),
																'where'		=> 'i.id=c.last_pic_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=c.last_poster_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id',
																'type'		=> 'left',
																),
															)
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					$r['cat_id']	= $r['cid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;module=cats&amp;do=sc&amp;cat=' . $r['cat_id'], 'none' );
					$r['date']		= $r['last_pic_date'];
					$r['content']	= $r['cdescription'];
					$r['title']		= $r['name'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					
					$content[]		= $r;
				}
			break;

			case 'albums':
				$this->DB->build( array(
										'select'	=> 'a.id as aid, a.description as adescription, a.*',
										'from'		=> array( 'gallery_albums' => 'a' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'i.id as imgid, i.*',
																'from'		=> array( 'gallery_images' => 'i' ),
																'where'		=> 'i.id=a.last_pic_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'c.id as cid, c.*',
																'from'		=> array( 'gallery_categories' => 'c' ),
																'where'		=> 'c.id=a.category_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=a.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id',
																'type'		=> 'left',
																),
															)
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					$r['cat_id']	= $r['cid'];
					$r['album_id']	= $r['aid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;module=user&amp;user=' . $r['member_id'] . '&amp;do=view_album&amp;album=' . $r['album_id'], 'none' );
					$r['date']		= $r['last_pic_date'];
					$r['content']	= $r['adescription'];
					$r['title']		= $r['name'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					
					$content[]		= $r;
				}
			break;
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