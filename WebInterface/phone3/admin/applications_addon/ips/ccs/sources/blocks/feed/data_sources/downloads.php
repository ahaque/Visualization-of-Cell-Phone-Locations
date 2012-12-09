<?php
/**
 * Downloads feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

class feed_downloads implements feedBlockInterface
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
		if( !IPSLib::appIsInstalled('downloads') )
		{
			return array();
		}

		return array(
					'key'			=> 'downloads',
					'app'			=> 'downloads',
					'name'			=> $this->lang->words['feed_name__downloads'],
					'description'	=> $this->lang->words['feed_description__downloads'],
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
							array( 'files', $this->lang->words['ct_idm_files'] ),
							array( 'comments', $this->lang->words['ct_idm_comments'] ),
							array( 'cats', $this->lang->words['ct_idm_cats'] ),
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
		if( !in_array( $data['content_type'], array( 'cats', 'files', 'comments' ) ) )
		{
			$data['content_type']	= 'files';
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
		
		require_once( IPSLib::getAppDir( 'downloads' ) .'/app_class_downloads.php' );
		$app_class 	= new app_class_downloads( $this->registry );
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_idm__cats'],
							'description'	=> $this->lang->words['feed_idm__cats_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_cats[]', $this->registry->categories->catJumpList( true ), explode( ',', $session['config_data']['filters']['filter_cats'] ), 10 ),
							);

		switch( $session['config_data']['content_type'] )
		{
			case 'files':
			default:
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_broken']		= $session['config_data']['filters']['filter_broken'] ? $session['config_data']['filters']['filter_broken'] : 'either';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';
				$session['config_data']['filters']['filter_submitter']	= $session['config_data']['filters']['filter_submitter'] ? $session['config_data']['filters']['filter_submitter'] : '';
				
				$visibility	= array( array( 'open', $this->lang->words['idm_status__open'] ), array( 'closed', $this->lang->words['idm_status__closed'] ), array( 'either', $this->lang->words['idm_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idm__visibility'],
									'description'	=> $this->lang->words['feed_idm__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$broken		= array( array( 'broken', $this->lang->words['broken__yes'] ), array( 'unbroken', $this->lang->words['broken__no'] ), array( 'either', $this->lang->words['broken__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idm__broken'],
									'description'	=> $this->lang->words['feed_idm__broken_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_broken', $broken, $session['config_data']['filters']['filter_broken'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idm__posted'],
									'description'	=> $this->lang->words['feed_idm__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idm__submitter'],
									'description'	=> $this->lang->words['feed_idm__submitter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitter', $session['config_data']['filters']['filter_submitter'] ),
									);
			break;
			
			case 'comments':
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';

				$visibility	= array( array( 'open', $this->lang->words['idmc_status__open'] ), array( 'closed', $this->lang->words['idmc_status__closed'] ), array( 'either', $this->lang->words['idmc_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idmc__visibility'],
									'description'	=> $this->lang->words['feed_idmc__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idmc__posted'],
									'description'	=> $this->lang->words['feed_idmc__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);
			break;
			
			case 'cats':
				$session['config_data']['filters']['filter_root']	= $session['config_data']['filters']['filter_root'] ? $session['config_data']['filters']['filter_root'] : 1;

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_idm__root'],
									'description'	=> $this->lang->words['feed_idm__root_desc'],
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
		
		$filters['filter_cats']	= is_array($data['filter_cats']) ? implode( ',', $data['filter_cats'] ) : '';

		switch( $session['config_data']['content_type'] )
		{
			case 'files':
			default:
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_broken']		= $data['filter_broken'] ? $data['filter_broken'] : 'either';
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
			case 'files':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'submitted';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_idm__title'] ), 
								array( 'views', $this->lang->words['sort_idm__views'] ), 
								array( 'submitted', $this->lang->words['sort_idm__submitted'] ),
								array( 'updated', $this->lang->words['sort_idm__updated'] ),
								array( 'downloads', $this->lang->words['sort_idm__downloads'] ),
								array( 'size', $this->lang->words['sort_idm__size'] ),
								array( 'rate', $this->lang->words['sort_idm__rate'] ),
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
								array( 'post_date', $this->lang->words['sort_idmc__postdate'] ), 
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
								array( 'name', $this->lang->words['sort_idmcat__name'] ), 
								array( 'files', $this->lang->words['sort_idmcat__files'] ), 
								array( 'last_file', $this->lang->words['sort_idmcat__lastdate'] ),
								array( 'position', $this->lang->words['sort_idmcat__position'] ),
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
			case 'files':
			default:
				$sortby	= array( 'title', 'views', 'submitted', 'updated', 'downloads', 'size', 'rate', 'rand' );
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

		if( $config['filters']['filter_cats'] )
		{
			if( $config['content'] != 'cats' )
			{
				$where[]	= "f.file_cat IN(" . $config['filters']['filter_cats'] . ")";
			}
		}

		switch( $config['content'] )
		{
			case 'files':
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "f.file_open=" . ( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}

				if( $config['filters']['filter_broken'] != 'either' )
				{
					$where[]	= "f.file_broken=" . ( $config['filters']['filter_broken'] == 'broken' ? 1 : 0 );
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "f.file_submitted > " . $timestamp;
					}
				}
				
				if( $config['filters']['filter_submitter'] == 'myself' )
				{
					$where[]	= "f.file_submitter = " . $this->memberData['member_id'];
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
						$where[]	= "f.file_submitter IN( " . implode( ',', $friends ) . ")";
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
						$where[]	= "f.file_submitter = " . $member['member_id'];
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
					$where[]	= "c.comment_open=" . ( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "c.comment_date > " . $timestamp;
					}
				}
			break;
		}

		$order	= '';

		switch( $config['content'] )
		{
			case 'files':
				switch( $config['sortby'] )
				{
					case 'title':
						$order	.=	"f.file_name ";
					break;
		
					case 'views':
						$order	.=	"f.file_views ";
					break;
					
					default:
					case 'submitted':
						$order	.=	"f.file_submitted ";
					break;
		
					case 'updated':
						$where[]	= "f.file_updated > 0 ";
						$order		.=	"f.file_updated ";
					break;

					case 'downloads':
						$order	.=	"f.file_downloads ";
					break;

					case 'size':
						$order	.=	"f.file_size ";
					break;

					case 'rate':
						$order	.=	"f.file_rating ";
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
		}
		
		$order	.= $config['sortorder'];
		
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$content	= array();

		switch( $config['content'] )
		{
			case 'files':
				$this->DB->build( array(
										'select'	=> 'f.*',
										'from'		=> array( 'downloads_files' => 'f' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'c.*',
																'from'		=> array( 'downloads_categories' => 'c' ),
																'where'		=> 'c.cid=f.file_cat',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=f.file_submitter',
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
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=downloads&amp;showfile=' . $r['file_id'], 'none' );
					$r['date']		= $r['file_submitted'];
					$r['content']	= $r['file_desc'];
					$r['title']		= $r['file_name'];
					
					$coptions	= unserialize($r['coptions']);
					IPSText::getTextClass( 'bbcode' )->parse_html				= $coptions['opt_html'];
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $coptions['opt_bbcode'];
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'idm_submit';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
			
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$content[]		= $r;
				}
			break;
			
			case 'comments':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'downloads_comments' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'f.*',
																'from'		=> array( 'downloads_files' => 'f' ),
																'where'		=> 'f.file_id=c.comment_fid',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'cc.*',
																'from'		=> array( 'downloads_cats' => 'cc' ),
																'where'		=> 'cc.cid=f.file_cat',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=c.comment_mid',
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
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=downloads&amp;module=display&amp;section=findpost&amp;id=' . $r['comment_id'], 'none' );
					$r['date']		= $r['comment_date'];
					$r['content']	= $r['comment_text'];
					$r['title']		= $r['file_name'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					IPSText::getTextClass('bbcode')->parse_html 				= 0;
					IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
					IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
					IPSText::getTextClass('bbcode')->parse_smilies				= $r['use_emo'];
					IPSText::getTextClass('bbcode')->parsing_section			= 'idm_comment';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
					
					$r['content']	= IPSText::getTextClass('bbcode')->preDisplayParse( $r['content'] );
					$r['content']	= IPSText::getTextClass('bbcode')->memberViewImages( $r['content'] );
					
					$content[]		= $r;
				}
			break;
			
			case 'cats':
				require_once( IPSLib::getAppDir('downloads') . '/app_class_downloads.php' );
				$app = new app_class_downloads( $this->registry );
				
				$cats	= array();
				$filter	= array();
				
				if( $config['filter_cats'] )
				{
					$filter	= explode( ',', $config['filter_cats'] );
				}
				
				foreach( $this->registry->categories->cat_lookup as $cid => $category )
				{
					if( count($filter) AND !in_array( $cid, $filter ) )
					{
						continue;
					}
					
					if( $config['filter_root'] AND $category['cparent'] > 0 )
					{
						continue;
					}
					
					switch( $config['sortby'] )
					{
						case 'name':
							$cats[ $category['cname'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
			
						case 'last_file':
							$cats[ $category['cfileinfo']['date'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
						
						case 'files':
							$cats[ $category['cfileinfo']['total_files'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
	
						case 'position':
							$cats[ $category['cposition'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
	
						case 'rand':
							$cats[ rand( 10000, 99999 ) ]	= $category;
						break;
					}
				}

				if( $config['sortorder'] == 'desc' )
				{
					krsort($cats);
				}
				else
				{
					ksort($cats);
				}

				$cats		= array_slice( $cats, $config['offset_a'], $config['offset_b'] );
				$finalCats	= array();

				foreach( $cats as $r )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------

					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=downloads&amp;showcat=' . $r['cid'], 'none' );
					$r['title']		= $r['cname'];
					$r['date']		= $r['cfileinfo']['date'];
					$r['content']	= $r['cdesc'];

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