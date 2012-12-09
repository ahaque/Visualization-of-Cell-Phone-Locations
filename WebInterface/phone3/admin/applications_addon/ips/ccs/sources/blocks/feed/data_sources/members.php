<?php
/**
 * Member feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_members implements feedBlockInterface
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
		return array(
					'key'			=> 'members',
					'app'			=> 'members',
					'name'			=> $this->lang->words['feed_name__members'],
					'description'	=> $this->lang->words['feed_description__members'],
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
							array( 'members', $this->lang->words['ct_members'] ),
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
		if( !in_array( $data['content_type'], array( 'members' ) ) )
		{
			$data['content_type']	= 'members';
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

		$groups		= array();
		
		foreach( $this->caches['group_cache'] as $group )
		{
			$groups[]	= array( $group['g_id'], $group['g_title'] );
		}

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_members__groups'],
							'description'	=> $this->lang->words['feed_members__groups_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_groups[]', $groups, explode( ',', $session['config_data']['filters']['filter_groups'] ), 8 ),
							);

		$session['config_data']['filters']['filter_posts']			= $session['config_data']['filters']['filter_posts'] ? $session['config_data']['filters']['filter_posts'] : 0;
		$session['config_data']['filters']['filter_bday_day']		= $session['config_data']['filters']['filter_bday_day'] ? $session['config_data']['filters']['filter_bday_day'] : 0;
		$session['config_data']['filters']['filter_bday_mon']		= $session['config_data']['filters']['filter_bday_mon'] ? $session['config_data']['filters']['filter_bday_mon'] : 0;
		$session['config_data']['filters']['filter_has_blog']		= $session['config_data']['filters']['filter_has_blog'] ? $session['config_data']['filters']['filter_has_blog'] : 0;
		$session['config_data']['filters']['filter_has_gallery']	= $session['config_data']['filters']['filter_has_gallery'] ? $session['config_data']['filters']['filter_has_gallery'] : 0;
		$session['config_data']['filters']['filter_min_rating']		= $session['config_data']['filters']['filter_min_rating'] ? $session['config_data']['filters']['filter_min_rating'] : 0;
		$session['config_data']['filters']['filter_min_rep']		= $session['config_data']['filters']['filter_min_rep'] ? $session['config_data']['filters']['filter_min_rep'] : 0;
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_members__posts'],
							'description'	=> $this->lang->words['feed_members__posts_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_posts', $session['config_data']['filters']['filter_posts'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_members__bdayday'],
							'description'	=> $this->lang->words['feed_members__bdayday_desc'],
							'field'			=> $this->registry->output->formYesNo( 'filter_bday_day', $session['config_data']['filters']['filter_bday_day'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_members__bdaymon'],
							'description'	=> $this->lang->words['feed_members__bdaymon_desc'],
							'field'			=> $this->registry->output->formYesNo( 'filter_bday_mon', $session['config_data']['filters']['filter_bday_mon'] ),
							);

		if( IPSLib::appIsInstalled('blog') )
		{
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_members__blog'],
								'description'	=> $this->lang->words['feed_members__blog_desc'],
								'field'			=> $this->registry->output->formYesNo( 'filter_has_blog', $session['config_data']['filters']['filter_has_blog'] ),
								);
		}

		if( IPSLib::appIsInstalled('gallery') )
		{
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_members__gallery'],
								'description'	=> $this->lang->words['feed_members__gallery_desc'],
								'field'			=> $this->registry->output->formYesNo( 'filter_has_gallery', $session['config_data']['filters']['filter_has_gallery'] ),
								);
		}

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_members__rating'],
							'description'	=> $this->lang->words['feed_members__rating_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_min_rating', $session['config_data']['filters']['filter_min_rating'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_members__rep'],
							'description'	=> $this->lang->words['feed_members__rep_desc'],
							'field'			=> $this->registry->output->formInput( 'filter_min_rep', $session['config_data']['filters']['filter_min_rep'] ),
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
		
		$filters['filter_groups']		= is_array($data['filter_groups']) ? implode( ',', $data['filter_groups'] ) : '';
		$filters['filter_posts']		= intval($data['filter_posts']);
		$filters['filter_bday_day']		= intval($data['filter_bday_day']);
		$filters['filter_bday_mon']		= intval($data['filter_bday_mon']);
		$filters['filter_has_blog']		= intval($data['filter_has_blog']);
		$filters['filter_has_gallery']	= intval($data['filter_has_gallery']);
		$filters['filter_min_rating']	= intval($data['filter_min_rating']);
		$filters['filter_min_rep']		= intval($data['filter_min_rep']);

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
		$session['config_data']['sortby']		= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'posts';

		$filters	= array();

		$sortby	= array( 
						array( 'name', $this->lang->words['sort_members__name'] ), 
						array( 'posts', $this->lang->words['sort_members__posts'] ), 
						array( 'joined', $this->lang->words['sort_members__joined'] ),
						array( 'last_active', $this->lang->words['sort_members__lastactive'] ),
						array( 'last_post', $this->lang->words['sort_members__lastpost'] ),
						array( 'age', $this->lang->words['sort_members__age'] ),
						array( 'profile_views', $this->lang->words['sort_members__views'] ),
						array( 'status_update', $this->lang->words['sort_members__status'] ),
						array( 'rating', $this->lang->words['sort_members__rating'] ),
						array( 'rep', $this->lang->words['sort_members__rep'] ),
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
		$sortby						= array( 'name', 'posts', 'joined', 'last_active', 'last_post', 'age', 'profile_views', 'status_update', 'rating', 'rep', 'rand' );
		
		$limits['sortby']			= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'posts';
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

		if( $config['filters']['filter_groups'] )
		{
			$where[]	= "m.member_group_id IN(" . $config['filters']['filter_groups'] . ")";
		}
		
		if( $config['filters']['filter_posts'] )
		{
			$where[]	= "m.posts > " . $config['filters']['filter_posts'];
		}
		
		if( $config['filters']['filter_bday_day'] )
		{
			$where[]	= "m.bday_day=" . date('j') . " AND m.bday_month=" . date('n');
		}

		if( $config['filters']['filter_bday_mon'] )
		{
			$where[]	= "m.bday_month=" . date('n');
		}

		if( $config['filters']['filter_has_blog'] )
		{
			$where[]	= "m.has_blog=1";
		}

		if( $config['filters']['filter_has_gallery'] )
		{
			$where[]	= "m.has_gallery=1";
		}

		if( $config['filters']['filter_min_rating'] )
		{
			$where[]	= "p.pp_rating_value >= " . $config['filters']['filter_min_rating'];
		}

		if( $config['filters']['filter_min_rep'] )
		{
			$where[]	= "p.pp_reputation_points >= " . $config['filters']['filter_min_rep'];
		}
		
		$order	= '';

		switch( $config['sortby'] )
		{
			case 'name':
				$order	.=	"m.members_display_name ";
			break;

			default:
			case 'posts':
				$order	.=	"m.posts ";
			break;
			
			case 'joined':
				$order	.=	"m.joined ";
			break;

			case 'last_active':
				$order	.=	"m.last_active ";
			break;

			case 'last_post':
				$order	.=	"m.last_post ";
			break;

			case 'age':
				$where[]	= "bday_year IS NOT NULL AND bday_year > 0";
				$order	.=	"m.bday_year " . $config['sortorder'] . ",m.bday_mon " . $config['sortorder'] . ",m.bday_day ";
			break;

			case 'profile_views':
				$order	.=	"m.members_profile_views ";
			break;

			case 'status_update':
				$order	.=	"p.pp_status_update ";
			break;

			case 'rating':
				$order	.=	"p.pp_rating_value ";
			break;

			case 'rep':
				$order	.=	"p.pp_reputation_points ";
			break;

			case 'rand':
				$order	.=	"RAND() ";
			break;
		}
		
		$order	.= $config['sortorder'];
		
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$members	= array();
		
		$this->DB->build( array(
								'select'	=> 'm.*, m.member_id as mid',
								'from'		=> array( 'members' => 'm' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'p.*',
														'from'		=> array( 'profile_portal' => 'p' ),
														'where'		=> 'p.pp_member_id=m.member_id',
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
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $r['member_id'], 'none', $r['members_seo_name'], 'showuser' );
			$r['title']		= $r['members_display_name'];
			$r['date']		= $r['joined'];
			$r['content']	= $r['pp_about_me'];
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $this->settings['aboutme_emoticons'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= intval($this->settings['aboutme_html']);
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->settings['aboutme_bbcode'];
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'aboutme';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];

			$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
			$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
			
			$r				= IPSMember::buildDisplayData( $r );
			
			$members[]		= $r;
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];

		if( $config['hide_empty'] AND !count($members) )
		{
			return '';
		}
		
		return $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $members );
	}
}