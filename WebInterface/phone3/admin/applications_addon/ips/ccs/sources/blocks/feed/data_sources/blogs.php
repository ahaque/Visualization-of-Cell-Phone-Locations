<?php
/**
 * Blog feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_blogs implements feedBlockInterface
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
		if( !IPSLib::appIsInstalled('blog') )
		{
			return array();
		}
		
		return array(
					'key'			=> 'blogs',
					'app'			=> 'blog',
					'name'			=> $this->lang->words['feed_name__blogs'],
					'description'	=> $this->lang->words['feed_description__blogs'],
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
							array( 'entries', $this->lang->words['ct_blog_entries'] ),
							array( 'comments', $this->lang->words['ct_blog_comments'] ),
							array( 'blogs', $this->lang->words['ct_blog_blogs'] ),
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
		if( !in_array( $data['content_type'], array( 'blogs', 'entries', 'comments' ) ) )
		{
			$data['content_type']	= 'entries';
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
		
		require_once( IPSLib::getAppDir( 'blog' ) .'/app_class_blog.php' );
		$app_class 	= new app_class_blog( $this->registry );

		switch( $session['config_data']['content_type'] )
		{
			case 'entries':
			default:
				$session['config_data']['filters']['filter_blogid']		= $session['config_data']['filters']['filter_blogid'] ? $session['config_data']['filters']['filter_blogid'] : 0;
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_featured']	= $session['config_data']['filters']['filter_featured'] ? $session['config_data']['filters']['filter_featured'] : 0;
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';
				$session['config_data']['filters']['filter_submitter']	= $session['config_data']['filters']['filter_submitter'] ? $session['config_data']['filters']['filter_submitter'] : '';
				
				$visibility	= array( array( 'open', $this->lang->words['bloge_status__open'] ), array( 'closed', $this->lang->words['bloge_status__closed'] ), array( 'either', $this->lang->words['bloge_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bloge__visibility'],
									'description'	=> $this->lang->words['feed_bloge__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bloge__blogid'],
									'description'	=> $this->lang->words['feed_bloge__blogid_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_blogid', $session['config_data']['filters']['filter_blogid'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bloge__featured'],
									'description'	=> $this->lang->words['feed_bloge__featured_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_featured', $session['config_data']['filters']['filter_featured'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bloge__posted'],
									'description'	=> $this->lang->words['feed_bloge__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bloge__submitter'],
									'description'	=> $this->lang->words['feed_bloge__submitter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitter', $session['config_data']['filters']['filter_submitter'] ),
									);
			break;
			
			case 'comments':
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';
				$session['config_data']['filters']['filter_submitter']	= $session['config_data']['filters']['filter_submitter'] ? $session['config_data']['filters']['filter_submitter'] : '';

				$visibility	= array( array( 'open', $this->lang->words['blogc_status__open'] ), array( 'closed', $this->lang->words['blogc_status__closed'] ), array( 'either', $this->lang->words['blogc_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blogc__visibility'],
									'description'	=> $this->lang->words['feed_blogc__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blogc__posted'],
									'description'	=> $this->lang->words['feed_blogc__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blogc__submitter'],
									'description'	=> $this->lang->words['feed_blogc__submitter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitter', $session['config_data']['filters']['filter_submitter'] ),
									);
			break;
			
			case 'blogs':
				$session['config_data']['filters']['filter_owner']		= $session['config_data']['filters']['filter_owner'] ? $session['config_data']['filters']['filter_owner'] : '';
				$session['config_data']['filters']['filter_type']		= $session['config_data']['filters']['filter_type'] ? $session['config_data']['filters']['filter_type'] : 'local';
				$session['config_data']['filters']['filter_private']	= $session['config_data']['filters']['filter_private'] ? $session['config_data']['filters']['filter_private'] : 0;
				$session['config_data']['filters']['filter_guests']		= $session['config_data']['filters']['filter_guests'] ? $session['config_data']['filters']['filter_guests'] : 1;

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blog__owner'],
									'description'	=> $this->lang->words['feed_blog__owner_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_owner', $session['config_data']['filters']['filter_root'] ),
									);

				$type		= array( array( 'local', $this->lang->words['blog_type__local'] ), array( 'external', $this->lang->words['blog_type__external'] ), array( 'either', $this->lang->words['blog_type__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blog__type'],
									'description'	=> $this->lang->words['feed_blog__type_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_type', $type, $session['config_data']['filters']['filter_type'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blog__private'],
									'description'	=> '',
									'field'			=> $this->registry->output->formYesNo( 'filter_private', $session['config_data']['filters']['filter_private'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_blog__guests'],
									'description'	=> $this->lang->words['feed_blog__guests_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_guests', $session['config_data']['filters']['filter_guests'] ),
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

		switch( $session['config_data']['content_type'] )
		{
			case 'entries':
			default:
				$filters['filter_blogid']		= $data['filter_blogid'] ? $data['filter_blogid'] : 0;
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_featured']		= $data['filter_featured'] ? $data['filter_featured'] : 0;
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
				$filters['filter_submitter']	= $data['filter_submitter'] ? $data['filter_submitter'] : '';
			break;
			
			case 'comments':
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
				$filters['filter_submitter']	= $data['filter_submitter'] ? $data['filter_submitter'] : '';
			break;
			
			case 'blogs':
				$filters['filter_owner']		= $data['filter_owner'] ? $data['filter_owner'] : '';
				$filters['filter_type']			= $data['filter_type'] ? $data['filter_type'] : 'local';
				$filters['filter_private']		= $data['filter_private'] ? $data['filter_private'] : 0;
				$filters['filter_guests']		= $data['filter_guests'] ? $data['filter_guests'] : 1;
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
			case 'entries':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'submitted';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_bloge__title'] ), 
								array( 'trackbacks', $this->lang->words['sort_bloge__trackbacks'] ), 
								array( 'submitted', $this->lang->words['sort_bloge__submitted'] ),
								array( 'comments', $this->lang->words['sort_bloge__comments'] ),
								array( 'lastcomment', $this->lang->words['sort_bloge__lastcomment'] ),
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
								array( 'post_date', $this->lang->words['sort_blogc__postdate'] ), 
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'blogs':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_blog__name'] ), 
								array( 'entries', $this->lang->words['sort_blog__entries'] ), 
								array( 'last_entry', $this->lang->words['sort_blog__lastdate'] ),
								array( 'views', $this->lang->words['sort_blog__views'] ),
								array( 'pinned', $this->lang->words['sort_blog__pinned'] ),
								array( 'rate', $this->lang->words['sort_bloge__rate'] ),
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
			case 'entries':
			default:
				$sortby	= array( 'name', 'trackbacks', 'submitted', 'comments', 'lastcomment', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'submitted';
			break;
			
			case 'comments':
				$sortby					= array( 'post_date' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'post_date';
			break;
			
			case 'blogs':
				$sortby	= array( 'name', 'entries', 'last_entry', 'views', 'pinned', 'rate', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'last_entry';
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
			case 'entries':
				if( $config['filters']['filter_blogid'] )
				{
					$where[]	= "e.blog_id=" . $config['filters']['filter_blogid'];
				}
				
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "e.entry_status='" . ( $config['filters']['filter_visibility'] == 'open' ? 'published' : 'draft' ) . "'";
				}

				if( $config['filters']['filter_featured'] )
				{
					$where[]	= "e.entry_featured=1";
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "e.entry_date > " . $timestamp;
					}
				}
				
				if( $config['filters']['filter_submitter'] == 'myself' )
				{
					$where[]	= "e.entry_author_id = " . $this->memberData['member_id'];
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
						$where[]	= "e.entry_author_id IN( " . implode( ',', $friends ) . ")";
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
						$where[]	= "e.entry_author_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;
			
			case 'comments':
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "c.comment_queued=" . ( $config['filters']['filter_visibility'] == 'open' ? 0 : 1 );
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "c.comment_date > " . $timestamp;
					}
				}
				
				if( $config['filters']['filter_submitter'] == 'myself' )
				{
					$where[]	= "c.member_id = " . $this->memberData['member_id'];
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
						$where[]	= "c.member_id IN( " . implode( ',', $friends ) . ")";
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
						$where[]	= "c.member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;

			case 'blogs':
				if( $config['filters']['filter_owner'] == 'myself' )
				{
					$where[]	= "b.member_id = " . $this->memberData['member_id'];
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
						$where[]	= "b.member_id IN( " . implode( ',', $friends ) . ")";
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
						$where[]	= "b.member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}

				if( $config['filters']['filter_type'] != 'either' )
				{
					$where[]	= "b.blog_type='" . $config['filters']['filter_type'] . "'";
				}

				if( !$config['filters']['filter_private'] )
				{
					$where[]	= "b.blog_private=0";
				}

				if( $config['filters']['filter_guests'] )
				{
					$where[]	= "b.blog_allowguests=1";
				}
			break;
		}

		$order	= '';

		switch( $config['content'] )
		{
			case 'entries':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"e.entry_name ";
					break;
		
					case 'trackbacks':
						$order	.=	"e.entry_trackbacks ";
					break;
					
					default:
					case 'submitted':
						$order	.=	"e.entry_date ";
					break;
		
					case 'comments':
						$order	.=	"e.entry_num_comments ";
					break;
		
					case 'lastcomment':
						$order	.=	"e.entry_last_comment_date ";
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
						$order	.=	"c.comment_date ";
					break;
				}
			break;

			case 'blogs':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"b.blog_name ";
					break;
		
					case 'entries':
						$order	.=	"entry_count ";
					break;
					
					default:
					case 'last_entry':
						$order	.=	"e.entry_date ";
					break;
		
					case 'views':
						$order	.=	"b.blog_num_views ";
					break;

					case 'pinned':
						$order	.=	"b.blog_pinned ";
					break;

					case 'rate':
						$order	.=	"(b.blog_rating_total/b.blog_rating_count) ";
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
			case 'entries':
				$this->DB->build( array(
										'select'	=> 'e.*',
										'from'		=> array( 'blog_entries' => 'e' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'b.*',
																'from'		=> array( 'blog_blogs' => 'b' ),
																'where'		=> 'e.blog_id=b.blog_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=e.entry_author_id',
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
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------

					$r['member_id']	= $r['mid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=blog&amp;module=display&amp;section=blog&amp;blogid=' .  $r['blog_id'] . '&amp;showentry=' . $r['entry_id'], 'none' );
					$r['date']		= $r['entry_date'];
					$r['content']	= $r['entry'];
					$r['title']		= $r['entry_name'];
					
					IPSText::getTextClass('bbcode')->parse_html					= $r['entry_html_state'] ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_nl2br				= $r['entry_html_state'] == 2 ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_smilies				= $r['entry_use_emo'] ? 1: 0;
					IPSText::getTextClass('bbcode')->parsing_section			= 'blog_entry';
					IPSText::getTextClass('bbcode')->parsing_mgroup				= $r['member_group_id'];
					IPSText::getTextClass('bbcode')->parsing_mgroup_others		= $r['mgroup_others'];
			
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->preDisplayParse( $r['content'] );
		
					$r				= IPSMember::buildDisplayData( $r );
					
					$content[]		= $r;
				}
			break;
			
			case 'comments':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'blog_comments' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'e.*',
																'from'		=> array( 'blog_entries' => 'e' ),
																'where'		=> 'c.entry_id=e.entry_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'b.*',
																'from'		=> array( 'blog_blogs' => 'b' ),
																'where'		=> 'b.blog_id=e.blog_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=c.member_id',
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
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=blog&amp;module=display&amp;section=blog&amp;blogid=' .  $r['blog_id'] . '&amp;showentry=' . $r['entry_id'], 'none' );
					$r['date']		= $r['comment_date'];
					$r['content']	= $r['comment_text'];
					$r['title']		= $r['entry_name'];
					
					IPSText::getTextClass('bbcode')->parse_html					= $r['comment_html_state'] ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
					IPSText::getTextClass('bbcode')->parse_nl2br				= $r['comment_html_state'] == 2 ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_smilies				= $r['comment_use_emo'] ? 1: 0;
					IPSText::getTextClass('bbcode')->parsing_section			= 'blog_comment';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
					
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->preDisplayParse( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$content[]		= $r;
				}
			break;
			
			case 'blogs':
				$this->DB->build( array(
										'select'	=> 'b.*',
										'from'		=> array( 'blog_blogs' => 'b' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'group'		=> 'b.blog_id',
										'add_join'	=> array(
															array(
																'select'	=> 'COUNT(e.entry_id) as entry_count, MAX(e.entry_id) as max_entry_id',
																'from'		=> array( 'blog_entries' => 'e' ),
																'where'		=> 'e.blog_id=b.blog_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'et.*',
																'from'		=> array( 'blog_entries' => 'et' ),
																'where'		=> 'et.entry_id=max_entry_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=e.member_id',
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
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------

					$r['member_id']	= $r['mid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=blog&amp;module=display&amp;section=blog&amp;blogid=' . $r['blog_id'], 'none' );
					$r['title']		= $r['blog_name'];
					$r['date']		= $r['entry_date'];
					$r['content']	= $r['blog_desc'];
					
					$r				= IPSMember::buildDisplayData( $r );

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