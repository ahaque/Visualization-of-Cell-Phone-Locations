<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Library for reported content
 * Last Updated: $LastChangedDate: 2009-06-15 04:12:19 -0400 (Mon, 15 Jun 2009) $
 *
 * @author 		$Author: matt $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4773 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class reportLibrary
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Group Ids
	 *
	 * @access	protected
	 * @var		array
	 */	
	protected $member_group_ids;
	
	/**
	 * Array of plugin objects
	 *
	 * @access	public
	 * @var		array
	 */	
	public $plugins;
	
	/**
	 * Status for new reports
	 *
	 * @access	public
	 * @var		integer
	 */	
	public $report_is_new		= 0;
	
	/**
	 * Status for complete reports
	 *
	 * @access	public
	 * @var		integer
	 */	
	public $report_is_complete	= 0;
	
	/**
	 * Cache of status/flag images
	 *
	 * @access	public
	 * @var		array
	 */	
	public $flag_cache			= array();
	
	/**
	 * Cache of HTML status dropdown
	 *
	 * @access	public
	 * @var		string
	 */	
	public $flag_body			= '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->member_group_ids	= array( $this->memberData['member_group_id'] );
		$this->member_group_ids	= array_diff( array_merge( $this->member_group_ids, explode( ',', $this->memberData['mgroup_others'] ) ), array('') );
	}

	/**
	 * Rebuild the member cache array if it is outdated
	 *
	 * @access	public
	 * @return	integer		New 'total reports' count
	 */
	public function rebuildMemberCacheArray()
	{
		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_report_queries.php', 'report_sql_queries' );

		$class_perm = $this->buildQueryPermissions();
		
		$total = $this->DB->buildAndFetch( array(
														'select'	=> 'COUNT(*) as reports',
														'from'		=> array( 'rc_reports_index' => 'rep' ),
														'where'		=> $class_perm . " AND stat.is_active=1",
														'add_join'	=> array(
																			array(
																				'from'	=> array( 'rc_classes' => 'rcl' ),
																				'where'	=> 'rcl.com_id=rep.rc_class'
																				),
																			array(
																				'from'	=> array( 'rc_status' => 'stat' ),
																				'where'	=> 'stat.status=rep.status'
																				),
																			)
												)		);
		
		$reports_by_plugin = array();
		
		$this->DB->build( array(
									'select'	=> 'rep.id, rep.title, rep.num_reports, rep.exdat1, rep.exdat2, rep.exdat3',
									'from'		=> array( 'rc_reports_index' => 'rep' ),
									'where'		=> $class_perm . " AND (stat.is_active=1 OR stat.is_new=1) AND rcl.onoff=1",
									'order'		=> 'stat.is_new ASC',
									'add_join'	=> array(
														array(
															'select'	=> 'stat.is_active, stat.is_new',
															'from'		=> array( 'rc_status' => 'stat' ),
															'where'		=> 'stat.status=rep.status'
															),
														array(
															'select'	=> 'rcl.com_id, rcl.com_id, rcl.my_class, rcl.extra_data',
															'from'		=> array( 'rc_classes' => 'rcl' ),
															'where'		=> 'rcl.com_id=rep.rc_class'
															),

														)
							)		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['my_class'] != '' )
			{
				$reports_by_plugin[ $row['my_class'] ][] = $row;
			}
		}
		
		$build_member_cache_array['report_temp'] = array();
		
		foreach( $reports_by_plugin as $plugin_name => $reports_array )
		{
			$this->loadPlugin( $plugin_name );
			$this->plugins[ $plugin_name ]->updateReportsTimestamp( $reports_array, $build_member_cache_array );
		}
		
		$build_member_cache_array['report_last_updated']	= time();
		$build_member_cache_array['report_num']				= $total['reports'];
		
		if( count($build_member_cache_array) > 0 )
		{
			IPSMember::packMemberCache( $this->memberData['member_id'], $build_member_cache_array );
		}
			
		return $total['reports'];
	}
	
	/**
	 * Builds permissions for several sql queries for various functions
	 *
	 * @access	public
	 * @param	string	based on mods or users
	 * @return	string
	 */
	public function buildQueryPermissions( $check='mod' )
	{
		//-----------------------------------------
		// Are we checking user or mod permissions?
		//-----------------------------------------
		
		if( $check == 'mod' )
		{
			$col = 'mod_group_perm';
		}
		else
		{
			$col = 'group_can_report';
		}

		//-----------------------------------------
		// Get components we have access to...
		//-----------------------------------------
		
		$this->DB->buildFromCache( 'get_class_permissions', array( 'COL' => $col, 'GROUP_IDS' => $this->member_group_ids ), 'report_sql_queries' );
		$res = $this->DB->execute(); 

		while( $row = $this->DB->fetch( $res ) )
		{
			if( $row['my_class'] != '' )
			{
				$spec_perm = '';	
				
				$this->loadPlugin( $row['my_class'] );
				
				if( $row['extra_data'] && $row['extra_data'] != 'N;' )
				{
					$this->plugins[ $row['my_class'] ]->_extra = unserialize( $row['extra_data'] );
				}
				else
				{
					$this->plugins[ $row['my_class'] ]->_extra = array();
				}

				if( $this->plugins[ $row['my_class'] ]->getReportPermissions( $check, $row, $this->member_group_ids, $spec_perm ) )
				{
					$cids[ $row['com_id'] ] = $spec_perm;
				}
			}
		}

		return $this->DB->fetchLoadedClass('report_sql_queries')->join_com_permissions( array( 'NOTCACHE' => 1, 'COMS' => $cids ) );
	}
	
	/**
	 * Loads plugins into $this->plugins object for later use
	 *
	 * @access	public
	 * @param	string	plugin name (>name<.php)
	 * @return	void
	 */
	public function loadPlugin( $plugin_name )
	{
		if( ! $this->plugins[ $plugin_name ] )
		{
			require_once( IPSLib::getAppDir('core') . '/sources/reportPlugins/' . $plugin_name . '.php' );
			$class_name = $plugin_name . '_plugin';
		
			$this->plugins[ $plugin_name ] = new $class_name( $this->registry );
		}
	}
	
	/**
	 * Process a URL
	 *
	 * @access	public
	 * @param	string	URL
	 * @param	string	FURL Title
	 * @param	string	FURL Template
	 * @return	string	Full URL if existing URL was short
	 */
	public function processUrl( $url, $friendlyTitle='', $friendlyTemplate='' )
	{
		if( $url && ! preg_match( "/(http|https)\:\/\//", $url ) )
		{
			$returnUrl	= str_replace( '/index.php?', '', $url );
			$returnUrl	= $this->registry->output->buildSEOUrl( $returnUrl, 'public', $friendlyTitle, $friendlyTemplate );
			
			return $returnUrl;
		}
		else
		{
			return $url;
		}
	}
	
	/**
	 * Fixes the member's RSS Key if none set
	 *
	 * @access	public
	 * @return	void
	 */
	public function checkMemberRSSKey()
	{
		if( ! $this->memberData['_cache']['rc_rss_key'] )
		{
			$new_rss_key = md5( uniqid( microtime(), true ) );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'rc_modpref', 'where' => "mem_id=" . $this->memberData['member_id'] ) );
			$this->DB->execute();
		
			if( $this->DB->getTotalRows() == 1 )
			{
				$this->DB->update( 'rc_modpref', array( 'rss_key' => $new_rss_key ), "mem_id=" . $this->memberData['member_id'] );
			}
			else
			{
				$this->DB->insert( 'rc_modpref', array( 'rss_key' => $new_rss_key, 'mem_id' => $this->memberData['member_id'], 'rss_cache' => '' ) );
			}
			
			$memberCache				= $this->memberData['_cache'];
			$memberCache['rc_rss_key']	= $new_rss_key;
			$this->member->setProperty( '_cache', $memberCache );

			IPSMember::packMemberCache( $this->memberData['member_id'], array( 'rc_rss_key' => $new_rss_key ) );
		}
	}
	
	/**
	 * Generates report form HTML
	 *
	 * @access	public
	 * @param	string	Title - What is being reported
	 * @param	string	URL - what the user can click on (title)
	 * @param	array	Extra data passed on to the form for processing
	 * @return	string
	 */
	public function showReportForm( $name, $url, $ex_data=array() )
	{
		$extra_input = '';

		if( is_array( $ex_data ) && count( $ex_data ) > 0 )
		{
			foreach( $ex_data as $bname => $value )
			{
				$extra_input .= "<input type='hidden' name='{$bname}' value='{$value}' />";
			}
		}
		
		return $this->registry->getClass('output')->getTemplate('reports')->basicReportForm( $name, $url, $extra_input );
	}
	
	/**
	 * Updates global 'cache time' which forces 'mod caches' to re-cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function updateCacheTime()
	{
		$cache					= $this->cache->getCache('report_cache');
		$cache['last_updated']	= time();

		$this->cache->setCache( 'report_cache', $cache, array( 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );
	}
	
	/**
	 * Builds the status information (and maybe drop down html)
	 *
	 * @access	public
	 * @param	boolean		Do we need the drop down?
	 * @return	string 		HTML body
	 */
	public function buildStatuses( $ignore_html = false )
	{
		if( !$this->flag_cache )
		{
			$stat_set	= array();
			$this->body	= '';
	
			$this->DB->build( array(
										'select'	=> 'stat.status, stat.title, stat.is_new, stat.is_complete',
										'from'		=> array( 'rc_status' => 'stat' ),
										'order'		=> 'stat.rorder ASC, star.points ASC',
										'add_join'	=> array(
															array(
																'select'	=> 'star.img, star.width, star.height, star.points, star.is_png',
																'from'		=> array( 'rc_status_sev' => 'star' ),
																'where'		=> 'stat.status=star.status'
																)
															)
									)		);
			$this->DB->execute(); 
	
			while( $row = $this->DB->fetch() )
			{
				if( ! $stat_set[ $row['status'] ] )
				{
					$this->body .= "<option value='{$row['status']}'>{$row['title']}</option>";
	
					if( $row['is_new'] == 1 )
					{
						$this->report_is_new = $row['status'];
					}
					elseif( $row['is_complete'] == 1 )
					{
						$this->report_is_complete = $row['status'];
					}
				}
	
				$stat_set[ $row['status'] ] = true;
				
				$this->flag_cache[ $row['status'] ][ $row['points'] ] = array(
																				'img'		=> $row['img'],
																				'width'		=> $row['width'],
																				'height'	=> $row['height'],
																				'is_png'	=> $row['is_png'],
																				'title'		=> $row['title'],
																			);
				
				if( $row['points'] == 0 )
				{
					$this->flag_cache[ $row['status'] ][ NULL ] = array(
																		'img'		=> $row['img'],
																		'width'		=> $row['width'],
																		'height'	=> $row['height'],
																		'is_png'	=> $row['is_png'],
																		'title'		=> $row['title'],
																	);
				}
			}
		}

		return $ignore_html ? '' : $this->body;
	}
}