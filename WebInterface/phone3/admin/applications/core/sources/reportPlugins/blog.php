<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report Center :: Blog plugin
 * Last Updated: $LastChangedDate: 2009-03-20 10:20:46 -0400 (Fri, 20 Mar 2009) $
 *
 * @author 		$Author: josh $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.Blog
 * @link		http://www.
 * @version		$Rev: 4266 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class blog_plugin
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
	 * Holds extra data for the plugin
	 *
	 * @access	private
	 * @var		array			Data specific to the plugin
	 */
	public $_extra;
	
	/**
	 * Blog dat
	 *
	 * @access	private
	 * @var		array			Data about the blog
	 */
	public $blog;
	
	/**
	 * Entry data
	 *
	 * @access	private
	 * @var		array			Data about the entry
	 */
	public $entry;
	
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
		
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang		= $this->registry->class_localization;
	}
	
	/**
	 * Display the form for extra data in the ACP
	 *
	 * @access	public
	 * @param	array 		Plugin data
	 * @param	object		HTML object
	 * @return	string		HTML to add to the form
	 */
	public function displayAdminForm( $plugin_data, &$html )
	{
		return $html->addRow(	$this->lang->words['r_supermod'],
								sprintf(  $this->lang->words['r_supermod_info'], $this->settings['_base_url'] ),
								$this->registry->output->formYesNo('report_supermod', (!isset( $plugin_data['report_supermod'] )) ? 1 : $plugin_data['report_supermod'] )
							);
	}
	
	/**
	 * Process the plugin's form fields for saving
	 *
	 * @access	public
	 * @param	array 		Plugin data for save
	 * @return	string		Error message
	 */
	public function processAdminForm( &$save_data_array )
	{
		$save_data_array['report_supermod'] = intval($this->request['report_supermod']);
		
		return '';
	}
	
	/**
	 * Update timestamp for report
	 *
	 * @access	public
	 * @param	array 		New reports
	 * @param 	array 		New members cache
	 * @return	boolean
	 */
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		return true;
	}
	
	/**
	 * Get report permissions
	 *
	 * @access	public
	 * @param	string 		Type of perms to check
	 * @param 	array 		Permissions data
	 * @param 	array 		group ids
	 * @param 	string		Special permissions
	 * @return	boolean
	 */
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if ( ! IPSLib::appIsInstalled('blog') )
		{
			return false;
		}
		
		if( $this->memberData['g_is_supmod'] == 1 && ( ! isset($this->_extra['report_supermod']) || $this->_extra['report_supermod'] == 1 ) )
		{
			return true;
		}
		else
		{
			$this->DB->build( array(
										'select'	=> 'md.moderate_type, md.moderate_mg_id',
										'from'		=> array('blog_moderators' => 'md'),
										'add_join'	=> array( 
							            					array(
																'select' => 'm.member_id, m.name, m.email, m.member_group_id',
																'from'   => array( 'members' => 'm' ),
																'where'  => "(md.moderate_type='member' AND md.moderate_mg_id={$this->memberData['member_id']}) OR (md.moderate_type='group' AND md.moderate_mg_id IN(" . implode( ',', $group_ids ) . "))",
																'type'   => 'inner'
																)
															),
									)		);
			$this->DB->execute();

			if ( $this->DB->getTotalRows() > 0 )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	
	/**
	 * Show the report form for this module
	 *
	 * @access	public
	 * @param 	array 		Application data
	 * @return	string		HTML form information
	 */
	public function reportForm( $com_dat )
	{
		$ex_form_data = array(
							'blog_id'		=> intval($this->request['blog_id']),
							'comment_id'	=> intval($this->request['comment_id']),
							'entry_id'		=> intval($this->request['entry_id']),
							'st'			=> intval($this->request['st'])
							);
		
		if( $ex_form_data['entry_id'] < 1 || $ex_form_data['blog_id'] < 1 )
		{
			$this->registry->output->showError( 'reports_blog_entry_id', 10152 );
		}
		
		$this->_loadBlog( $ex_form_data['blog_id'] );
		$this->_checkAccess( $ex_form_data['entry_id'] );
		
		$this->settings['blog_url'] =  $this->registry->getClass('blog_std')->getBlogUrl( $ex_form_data['blog_id'] );
		
		$this->registry->output->setTitle( $this->lang->words['report_title'] );
		$this->registry->output->addNavigation( $this->lang->words['blog_title'], "app=blog" );
		$this->registry->output->addNavigation( $this->blog['blog_name'], $this->settings['blog_url'] );
		$this->registry->output->addNavigation( $this->entry['entry_name'], $this->settings['blog_url'] . "showentry={$this->entry['entry_id']}" );
		$this->registry->output->addNavigation( $this->lang->words['report_title'], '' );

		$url = $this->settings['base_url'] . "automodule=blog&blogid={$this->entry['blog_id']}&showentry={$ex_form_data['entry_id']}&st=0&#comment{$ex_form_data['comment_id']}";
		
		$this->lang->words['report_basic_title']		= $this->lang->words['report_title'];
		$this->lang->words['report_basic_url_title']	= $this->lang->words['report_topic'];
		$this->lang->words['report_basic_enter']		= $this->lang->words['report_message'];
		
		//-----------------------------------------
		// Instead of dull output, lets make it
		// blogerishes!
		//-----------------------------------------

		return $this->registry->getClass('reportLibrary')->showReportForm( $this->entry['entry_name'], $url, $ex_form_data );
	}

	/**
	 * Get section and link
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	array 		Section/link
	 */
	public function giveSectionLinkTitle( $report_row )
	{
		$cache 	= $this->cache->getCache('report_cache');

		return array(
					'title'	=> $cache['blog_titles'][ $report_row['exdat1'] ],
					'url'	=> "/index.php?automodule=blog&blogid=" . $report_row['exdat1'],
					);
	}
	
	/**
	 * Process a report and save the data appropriate
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	array 		Data from saving the report
	 */
	public function processReport( $com_dat )
	{
		$blog_id	= intval( $this->request['blog_id'] );
		$entry_id	= intval( $this->request['entry_id'] );
		$comment_id	= intval( $this->request['comment_id'] );
		
		if( $entry_id < 1 || $blog_id < 1 )
		{
			$this->registry->output->showError( 'reports_blog_entry_id', 10153 );
		}
		
		$this->_loadBlog( $blog_id );
		$this->_checkAccess( $entry_id );
		
		$url = "app=blog&blogid={$blog_id}&showentry={$entry_id}&st=0&#comment{$comment_id}";

		$return_data	= array();
		$a_url			= str_replace("&", "&amp;", $url);
		$uid			= md5( $url . '_' . $com_dat['com_id'] );
		
		$status = array();
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
										 'from'		=> 'rc_status', 
										 'where'	=> "is_new=1 OR is_complete=1",
								) 		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->entry['entry_name'],
										'status'		=> $status['new'],
										'url'			=> '/index.php?' . $a_url,
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
										'exdat1'		=> $blog_id,
										'exdat2'		=> $entry_id,
										'exdat3'		=> $comment_id
									);
			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report = $this->DB->fetch();
			$rid = $the_report['id'];
			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'] ), "id='{$rid}'" );
		}
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';

		$build_report = array(
							'rid'			=> $rid,
							'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $this->request['message'] ),
							'report_by'		=> $this->memberData['member_id'],
							'date_reported'	=> time(),
						);
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$cache 	= $this->cache->getCache('report_cache');
		
		if( $cache['blog_titles'][ $this->blog['blog_id'] ] != $this->blog['blog_name'] )
		{
			$cache['blog_titles'][ $this->blog['blog_id'] ]	= $this->blog['blog_name'];

			$this->cache->setCache( 'report_cache', $cache, array( 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );
		}
		
		$return_data['REDIRECT_URL']	= $a_url;
		$return_data['REPORT_INDEX']	= $rid;
		$return_data['SAVED_URL']		= '/index.php?' . $url;
		$return_data['REPORT']			= $build_report['report'];
		
		return $return_data;
	}
	
	/**
	 * Where to send user after report is submitted
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	void
	 */
	public function reportRedirect( $report_data )
	{
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'],  $this->settings['base_url'] . $report_data['REDIRECT_URL'] );
	}
	
	/**
	 * Retrieve list of users to send notifications to
	 *
	 * @access	public
	 * @param 	string 		Group ids
	 * @param 	array 		Report data
	 * @return	array 		Array of users to PM/Email
	 */
	public function getNotificationList( $group_ids, $report_data )
	{
		$notify = array();
		
		$this->DB->build( array(
									'select'	=> 'noti.*',
									'from'		=> array( 'rc_modpref' => 'noti' ),
									'where'		=> 'mem.member_group_id IN(' . $group_ids . ')',
									'add_join'	=> array(
														array(
															'select'	=> 'mem.member_id, mem.members_display_name as name, mem.language, mem.members_disable_pm, mem.email, mem.member_group_id',
															'from'		=> array( 'members' => 'mem' ),
															'where'		=> 'mem.member_id=noti.mem_id',
															)
														)
							)		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() > 0 )
		{
			while( $row = $this->DB->fetch() )
			{
				if( $row['by_pm'] == 1 )
				{
					$notify['PM'][] = $row;
				}
				if( $row['by_email'] == 1 )
				{
					$notify['EMAIL'][] = $row;
				}
				
				$notify['RSS'][] = $row;
			}	
		}
		
		return $notify;
	}
	
	/**
	 * Load up blog libs
	 *
	 * @access	private
	 * @param 	integer 	Blog id
	 * @return	void
	 */
	private function _loadBlog( $blog_id )
	{	
		require_once( IPSLib::getAppDir('blog') . '/app_class_blog.php' );
		$blog = new app_class_blog( $this->registry );

		$this->blog = $this->registry->getClass('blog_std')->loadBlog( $blog_id );
		
		$this->settings['blog_url'] =  $this->registry->getClass('blog_std')->getBlogUrl( $this->blog['blog_id'] );
	}
	
	/**
	 * Check access
	 *
	 * @access	private
	 * @param 	integer		Entry id
	 * @return	void
	 */
	private function _checkAccess($eid)
    {
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'reports_must_be_member' );
		}
		
		if ( ! $this->blog['blog_name'] )
		{
			$this->registry->output->showError( 'blog_not_enabled', 10154 );
		}
		
		$this->entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id=" . $eid ) );
		
		if ( !$this->entry['entry_id'] )
		{
			$this->registry->output->showError( 'reports_no_entry', 10155 );
		}

		//-----------------------------------------
		// Are we allowed to see draft entries?
		//-----------------------------------------

		if ( $this->blog['allow_entry'] )
		{
			$show_draft	= $this->blog['blog_settings']['hidedraft'] ? false : true;
		}
		elseif ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			$show_draft	= true;
		}
		else
		{
			$show_draft	= false;
		}

		if ( $this->entry['entry_status'] == 'draft' and !$show_draft )
		{
			$this->registry->output->showError( 'reports_cannot_view_entry', 10156, true );
		}

	}
}