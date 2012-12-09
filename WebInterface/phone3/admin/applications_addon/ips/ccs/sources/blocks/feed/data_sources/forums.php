<?php
/**
 * Forum feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_forums implements feedBlockInterface
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
		return array(
					'key'			=> 'forums',
					'app'			=> 'forums',
					'name'			=> $this->lang->words['feed_name__forums'],
					'description'	=> $this->lang->words['feed_description__forums'],
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
							array( 'forums', $this->lang->words['ct_forums'] ),
							array( 'topics', $this->lang->words['ct_topics'] ),
							array( 'replies', $this->lang->words['ct_replies'] ),
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
		if( !in_array( $data['content_type'], array( 'forums', 'topics', 'replies' ) ) )
		{
			$data['content_type']	= 'topics';
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
		
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php' );
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php' );
		
		$aff = new admin_forum_functions( $this->registry );
		$aff->forumsInit();
		$dropdown = $aff->adForumsForumList(1);
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_forums__forums'],
							'description'	=> $this->lang->words['feed_forums__forums_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_forums[]', $dropdown, explode( ',', $session['config_data']['filters']['filter_forums'] ), 10 ),
							);

		switch( $session['config_data']['content_type'] )
		{
			case 'topics':
			default:
				$session['config_data']['filters']['filter_status']		= $session['config_data']['filters']['filter_status'] ? $session['config_data']['filters']['filter_status'] : 'either';
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'approved';
				$session['config_data']['filters']['filter_pinned']		= $session['config_data']['filters']['filter_pinned'] ? $session['config_data']['filters']['filter_pinned'] : 'either';
				$session['config_data']['filters']['filter_posts']		= $session['config_data']['filters']['filter_posts'] ? $session['config_data']['filters']['filter_posts'] : 0;
				$session['config_data']['filters']['filter_starter']	= $session['config_data']['filters']['filter_starter'] ? $session['config_data']['filters']['filter_starter'] : '';
				$session['config_data']['filters']['filter_poll']		= $session['config_data']['filters']['filter_poll'] ? $session['config_data']['filters']['filter_poll'] : 'either';
				$session['config_data']['filters']['filter_moved']		= $session['config_data']['filters']['filter_moved'] ? $session['config_data']['filters']['filter_moved'] : 1;
				$session['config_data']['filters']['filter_attach']		= $session['config_data']['filters']['filter_attach'] ? $session['config_data']['filters']['filter_attach'] : 0;
				$session['config_data']['filters']['filter_rating']		= $session['config_data']['filters']['filter_rating'] ? $session['config_data']['filters']['filter_rating'] : 0;
				

				$status		= array( array( 'open', $this->lang->words['status__open'] ), array( 'closed', $this->lang->words['status__closed'] ), array( 'either', $this->lang->words['status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__status'],
									'description'	=> $this->lang->words['feed_forums__status_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_status', $status, $session['config_data']['filters']['filter_status'] ),
									);

				$visibility	= array( array( 'approved', $this->lang->words['approved__yes'] ), array( 'unapproved', $this->lang->words['approved__no'] ), array( 'either', $this->lang->words['approved__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__visibility'],
									'description'	=> $this->lang->words['feed_forums__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$pinned		= array( array( 'pinned', $this->lang->words['pinned__yes'] ), array( 'unpinned', $this->lang->words['pinned__no'] ), array( 'either', $this->lang->words['pinned__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__pinned'],
									'description'	=> $this->lang->words['feed_forums__pinned_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_pinned', $pinned, $session['config_data']['filters']['filter_pinned'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__posts'],
									'description'	=> $this->lang->words['feed_forums__posts_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_posts', $session['config_data']['filters']['filter_posts'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__starter'],
									'description'	=> $this->lang->words['feed_forums__starter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_starter', $session['config_data']['filters']['filter_starter'] ),
									);

				$poll		= array( array( 'poll', $this->lang->words['poll__yes'] ), array( 'nopoll', $this->lang->words['poll__no'] ), array( 'either', $this->lang->words['poll__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__poll'],
									'description'	=> $this->lang->words['feed_forums__poll_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_poll', $poll, $session['config_data']['filters']['filter_poll'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__move'],
									'description'	=> $this->lang->words['feed_forums__move_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_moved', $session['config_data']['filters']['filter_moved'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__attach'],
									'description'	=> $this->lang->words['feed_forums__attach_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_attach', $session['config_data']['filters']['filter_attach'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__rating'],
									'description'	=> $this->lang->words['feed_forums__rating_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_rating', $session['config_data']['filters']['filter_rating'] ),
									);
			break;
			
			case 'replies':
				$session['config_data']['filters']['filter_status']		= $session['config_data']['filters']['filter_status'] ? $session['config_data']['filters']['filter_status'] : 'either';
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'approved';
				$session['config_data']['filters']['filter_pinned']		= $session['config_data']['filters']['filter_pinned'] ? $session['config_data']['filters']['filter_pinned'] : 'either';
				$session['config_data']['filters']['filter_posts']		= $session['config_data']['filters']['filter_posts'] ? $session['config_data']['filters']['filter_posts'] : 0;
				$session['config_data']['filters']['filter_poster']		= $session['config_data']['filters']['filter_poster'] ? $session['config_data']['filters']['filter_poster'] : '';
				$session['config_data']['filters']['filter_poll']		= $session['config_data']['filters']['filter_poll'] ? $session['config_data']['filters']['filter_poll'] : 'either';
				$session['config_data']['filters']['filter_attach']		= $session['config_data']['filters']['filter_attach'] ? $session['config_data']['filters']['filter_attach'] : 0;
				$session['config_data']['filters']['filter_rating']		= $session['config_data']['filters']['filter_rating'] ? $session['config_data']['filters']['filter_rating'] : 0;
				

				$status		= array( array( 'open', $this->lang->words['status__open'] ), array( 'closed', $this->lang->words['status__closed'] ), array( 'either', $this->lang->words['status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__status'],
									'description'	=> $this->lang->words['feed_forums__status_desc_r'],
									'field'			=> $this->registry->output->formDropdown( 'filter_status', $status, $session['config_data']['filters']['filter_status'] ),
									);

				$visibility	= array( array( 'approved', $this->lang->words['approved__yes'] ), array( 'unapproved', $this->lang->words['approved__no'] ), array( 'either', $this->lang->words['approved__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__visibility'],
									'description'	=> $this->lang->words['feed_forums__visibility_desc_r'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$pinned		= array( array( 'pinned', $this->lang->words['pinned__yes'] ), array( 'unpinned', $this->lang->words['pinned__no'] ), array( 'either', $this->lang->words['pinned__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__pinned'],
									'description'	=> $this->lang->words['feed_forums__pinned_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_pinned', $pinned, $session['config_data']['filters']['filter_pinned'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__posts'],
									'description'	=> $this->lang->words['feed_forums__posts_desc_r'],
									'field'			=> $this->registry->output->formInput( 'filter_posts', $session['config_data']['filters']['filter_posts'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__poster'],
									'description'	=> $this->lang->words['feed_forums__poster_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_poster', $session['config_data']['filters']['filter_poster'] ),
									);

				$poll		= array( array( 'poll', $this->lang->words['poll__yes'] ), array( 'nopoll', $this->lang->words['poll__no'] ), array( 'either', $this->lang->words['poll__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__poll'],
									'description'	=> $this->lang->words['feed_forums__poll_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_poll', $poll, $session['config_data']['filters']['filter_poll'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__attach'],
									'description'	=> $this->lang->words['feed_forums__attach_desc_r'],
									'field'			=> $this->registry->output->formInput( 'filter_attach', $session['config_data']['filters']['filter_attach'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__rating'],
									'description'	=> $this->lang->words['feed_forums__rating_desc_r'],
									'field'			=> $this->registry->output->formInput( 'filter_rating', $session['config_data']['filters']['filter_rating'] ),
									);
			break;
			
			case 'forums':
				$session['config_data']['filters']['filter_root']	= $session['config_data']['filters']['filter_root'] ? $session['config_data']['filters']['filter_root'] : 1;

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__root'],
									'description'	=> $this->lang->words['feed_forums__root_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_root', $session['config_data']['filters']['filter_root'] ),
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
		
		$filters['filter_forums']	= is_array($data['filter_forums']) ? implode( ',', $data['filter_forums'] ) : '';

		switch( $session['config_data']['content_type'] )
		{
			case 'topics':
			default:
				$filters['filter_status']		= $data['filter_status'] ? $data['filter_status'] : 'either';
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'approved';
				$filters['filter_pinned']		= $data['filter_pinned'] ? $data['filter_pinned'] : 'either';
				$filters['filter_posts']		= $data['filter_posts'] ? $data['filter_posts'] : 0;
				$filters['filter_starter']		= $data['filter_starter'] ? $data['filter_starter'] : '';
				$filters['filter_poll']			= $data['filter_poll'] ? $data['filter_poll'] : 'either';
				$filters['filter_moved']		= $data['filter_moved'] ? $data['filter_moved'] : 1;
				$filters['filter_attach']		= $data['filter_attach'] ? $data['filter_attach'] : 0;
				$filters['filter_rating']		= $data['filter_rating'] ? $data['filter_rating'] : 0;
			break;
			
			case 'replies':
				$filters['filter_status']		= $data['filter_status'] ? $data['filter_status'] : 'either';
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'approved';
				$filters['filter_pinned']		= $data['filter_pinned'] ? $data['filter_pinned'] : 'either';
				$filters['filter_posts']		= $data['filter_posts'] ? $data['filter_posts'] : 0;
				$filters['filter_poster']		= $data['filter_poster'] ? $data['filter_poster'] : '';
				$filters['filter_poll']			= $data['filter_poll'] ? $data['filter_poll'] : 'either';
				$filters['filter_attach']		= $data['filter_attach'] ? $data['filter_attach'] : 0;
				$filters['filter_rating']		= $data['filter_rating'] ? $data['filter_rating'] : 0;
			break;
			
			case 'forums':
				$filters['filter_root']			= $data['filter_root'] ? $data['filter_root'] : 1;
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
			case 'topics':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'last_post';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_topic__title'] ), 
								array( 'posts', $this->lang->words['sort_topic__posts'] ), 
								array( 'start_date', $this->lang->words['sort_topic__startdate'] ),
								array( 'last_post', $this->lang->words['sort_topic__lastdate'] ),
								array( 'views', $this->lang->words['sort_topic__views'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'replies':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'post_date';

				$sortby	= array( 
								array( 'post_date', $this->lang->words['sort_topic__postdate'] ), 
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'forums':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_topic__name'] ), 
								array( 'topics', $this->lang->words['sort_topic__topics'] ), 
								array( 'posts', $this->lang->words['sort_topic__posts'] ),
								array( 'last_post', $this->lang->words['sort_topic__lastdate'] ),
								array( 'position', $this->lang->words['sort_topic__position'] ),
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
			case 'topics':
			default:
				$sortby					= array( 'title', 'posts', 'start_date', 'last_post', 'views', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'last_post';
			break;
			
			case 'replies':
				$sortby					= array( 'post_date' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'post_date';
			break;
			
			case 'forums':
				$sortby					= array( 'name', 'topics', 'posts', 'last_post', 'position', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'position';
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

		if( $config['filters']['filter_forums'] )
		{
			if( $config['content'] == 'forums' )
			{
				$where[]	= "f.id IN(" . $config['filters']['filter_forums'] . ")";
			}
			else
			{
				$where[]	= "t.forum_id IN(" . $config['filters']['filter_forums'] . ")";
			}
		}

		switch( $config['content'] )
		{
			case 'topics':
				if( $config['filters']['filter_status'] != 'either' )
				{
					$where[]	= "t.state='" . $config['filters']['filter_status'] . "'";
				}
				
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "t.approved=" . ( $config['filters']['filter_visibility'] == 'approved' ? 1 : 0 );
				}

				if( $config['filters']['filter_pinned'] != 'either' )
				{
					$where[]	= "t.pinned=" . ( $config['filters']['filter_pinned'] == 'pinned' ? 1 : 0 );
				}

				if( $config['filters']['filter_posts'] > 0 )
				{
					$where[]	= "t.posts > " . $config['filters']['filter_posts'];
				}
				
				if( $config['filters']['filter_starter'] == 'myself' )
				{
					$where[]	= "t.starter_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_starter'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
					$pageBuilder	= new pageBuilder( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "t.starter_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_starter'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_starter'], 'basic' );
					
					if( $member['member_id'] )
					{
						$where[]	= "t.starter_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
				
				if( $config['filters']['filter_poll'] != 'either' )
				{
					$where[]	= "t.poll_state=" . ( $config['filters']['filter_poll'] == 'poll' ? 1 : 0 );
				}

				if( $config['filters']['filter_moved'] )
				{
					$where[]	= "(t.moved_to=0 OR t.moved_to='' OR t.moved_to IS NULL)";
				}
				
				if( $config['filters']['filter_attach'] )
				{
					$where[]	= "t.topic_hasattach > 0";
				}
				
				if( $config['filters']['filter_rating'] )
				{
					$where[]	= "(t.topic_rating_total/t.topic_rating_hits) >= " . $config['filters']['filter_rating'];
				}
			break;
			
			case 'replies':
				if( $config['filters']['filter_status'] != 'either' )
				{
					$where[]	= "t.state='" . $config['filters']['filter_status'] . "'";
				}
				
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "t.approved=" . ( $config['filters']['filter_visibility'] == 'approved' ? 1 : 0 );
					$where[]	= "p.queued=" . ( $config['filters']['filter_visibility'] == 'approved' ? 0 : 1 );
				}

				if( $config['filters']['filter_pinned'] != 'either' )
				{
					$where[]	= "t.pinned=" . ( $config['filters']['filter_pinned'] == 'pinned' ? 1 : 0 );
				}

				if( $config['filters']['filter_posts'] > 0 )
				{
					$where[]	= "t.posts > " . $config['filters']['filter_posts'];
				}

				if( $config['filters']['filter_attach'] )
				{
					$where[]	= "t.topic_hasattach > 0";
				}
				
				if( $config['filters']['filter_rating'] )
				{
					$where[]	= "(t.topic_rating_total/t.topic_rating_hits) >= " . $config['filters']['filter_rating'];
				}
				
				if( $config['filters']['filter_poll'] != 'either' )
				{
					$where[]	= "t.poll_state=" . ( $config['filters']['filter_poll'] == 'poll' ? 1 : 0 );
				}

				if( $config['filters']['filter_poster'] == 'myself' )
				{
					$where[]	= "p.author_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_poster'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
					$pageBuilder	= new pageBuilder( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "p.author_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_poster'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_poster'], 'basic' );
					
					if( $member['member_id'] )
					{
						$where[]	= "p.author_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;
			
			case 'forums':
				if( $config['filters']['filter_root'] )
				{
					$where[]	= "f.parent_id < 1";
				}
			break;
		}

		$order	= '';

		switch( $config['content'] )
		{
			case 'topics':
				switch( $config['sortby'] )
				{
					case 'title':
						$order	.=	"t.title ";
					break;
		
					case 'posts':
						$order	.=	"t.posts ";
					break;
					
					case 'start_date':
						$order	.=	"t.start_date ";
					break;

					default:
					case 'last_post':
						$order	.=	"t.last_post ";
					break;
		
					case 'views':
						$order	.=	"t.views ";
					break;

					case 'rand':
						$order	.=	"RAND() ";
					break;
				}
			break;
			
			case 'replies':
				switch( $config['sortby'] )
				{
					default:
					case 'post_date':
						$order	.=	"p.post_date ";
					break;
				}
			break;

			case 'forums':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"f.name ";
					break;
		
					case 'topics':
						$order	.=	"f.topics ";
					break;
					
					case 'posts':
						$order	.=	"f.posts ";
					break;
		
					case 'last_post':
						$order	.=	"f.last_post ";
					break;
		
					default:
					case 'position':
						$order	.=	"f.position ";
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
			case 'topics':
				$this->DB->build( array(
										'select'	=> 't.*, t.title as topic_title',
										'from'		=> array( 'topics' => 't' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'p.*',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=t.topic_firstpost',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'poster.member_group_id as poster_group_id, poster.member_id as poster_id, poster.mgroup_others as poster_group_others',
																'from'		=> array( 'members' => 'poster' ),
																'where'		=> 'poster.member_id=p.author_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'f.*, f.id as fid',
																'from'		=> array( 'forums' => 'f' ),
																'where'		=> 'f.id=t.forum_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=t.last_poster_id',
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
					
					$r['title']		= $r['topic_title'];
					$r['member_id']	= $r['mid'];
					$r['forum_id']	= $r['fid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showtopic=' . $r['tid'], 'none', $r['title_seo'], 'showtopic' );
					$r['date']		= $r['last_post'];
					$r['content']	= $r['post'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= $r['use_emo'];
					IPSText::getTextClass( 'bbcode' )->parse_html				= ( $r['use_html'] and $this->caches['group_cache'][ $r['poster_group_id'] ]['g_dohtml'] and $r['post_htmlstate'] ) ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $r['post_htmlstate'] == 2 ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $r['use_ibc'];
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['poster_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['poster_group_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$content[]		= $r;
				}
			break;
			
			case 'replies':
				$this->DB->build( array(
										'select'	=> 'p.*',
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 't.*, t.title as topic_title',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'f.*, f.id as fid',
																'from'		=> array( 'forums' => 'f' ),
																'where'		=> 'f.id=t.forum_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=p.author_id',
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
					
					$r['title']		= $r['topic_title'];
					$r['member_id']	= $r['mid'];
					$r['forum_id']	= $r['fid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showtopic=' . $r['tid'] . '&amp;view=findpost&amp;p=' . $r['pid'], 'none', $r['title_seo'], 'showtopic' );
					$r['date']		= $r['post_date'];
					$r['content']	= $r['post'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= $r['use_emo'];
					IPSText::getTextClass( 'bbcode' )->parse_html				= ( $r['use_html'] and $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'] and $r['post_htmlstate'] ) ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $r['post_htmlstate'] == 2 ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $r['use_ibc'];
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$content[]		= $r;
				}
			break;
			
			case 'forums':
				$this->DB->build( array(
										'select'	=> 'f.*, f.name as fname, f.id as fid',
										'from'		=> array( 'forums' => 'f' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=f.last_poster_id',
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
					$r['forum_id']	= $r['fid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showforum=' . $r['forum_id'], 'none', $r['name_seo'], 'showforum' );
					$r['title']		= $r['fname'];
					$r['date']		= $r['joined'];
					$r['content']	= $r['pp_about_me'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['forum_id'];
					
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