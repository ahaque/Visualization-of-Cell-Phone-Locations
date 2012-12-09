<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report Center :: Profiles plugin
 * Last Updated: $LastChangedDate: 2009-03-04 11:15:29 -0500 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: josh $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4138 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profiles_plugin
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
		$return = '';

		return $return;
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
	 * Get report permissions (only supermods can moderate profiles)
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
		if( $this->_extra['report_bypass'] == 0 || $this->memberData['g_is_supmod'] == 1 )
		{
			return true;
		}
		else
		{
			return false;
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
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		$mem = intval($this->request['member_id']);
		
		if( ! $mem )
		{
			$this->registry->output->showError( 'reports_no_member', 10175 );
		}

		$member = IPSMember::load( $mem );
		
		$ex_form_data = array(
								'member_id'	=> $mem,
								'ctyp'		=> 'profile',
								'title'		=> $member['members_display_name']
							);
		
		$this->registry->output->setTitle( $this->lang->words['report_mem_page'] );
		$this->registry->output->addNavigation( $member['members_display_name'], "showuser=" . $member['member_id'] );
		$this->registry->output->addNavigation( $this->lang->words['report_mem_page'], '' );
		
		$this->lang->words['report_basic_title']		= $this->lang->words['report_mem_title'];
		$this->lang->words['report_basic_url_title']	= $this->lang->words['report_mem_title'];
		$this->lang->words['report_basic_enter']		= $this->lang->words['report_mem_msg'];
		
		$url = $this->registry->getClass('output')->buildSEOUrl( "showuser=" . $ex_form_data['member_id'], 'public', $member['members_seo_name'], 'showuser' );
		
		return $this->registry->getClass('reportLibrary')->showReportForm( $member['members_display_name'], $url, $ex_form_data );
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
		return array(
					'title'			=> $this->lang->words['report_section_title_mem'],
					'url'			=> "/index.php?showuser={$report_row['exdat1']}",
					'seo_template'	=> "showuser",
					'seo_title'		=> $report_row['seoname'],
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
		$mem		= $this->request['member_id'];
		$url		= 'showuser=' . $mem;

		if( $mem < 1 )
		{
			$this->registry->output->showError( 'reports_no_member', 10176 );
		}

		$return_data	= array();
		$a_url			= str_replace("&", "&amp;", $url);
		$uid			= md5(  'mem_' . $mem . '_' . $com_dat['com_id'] );
		$status			= array();
		
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
		
		$data = IPSMember::load( $this->request['member_id'] );
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->request['title'],
										'status'		=> $status['new'],
										'url'			=> '/index.php?' . $a_url,
										'seoname'		=> $data['members_seo_name'],
										'seotemplate'	=> 'showuser',
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
										'exdat1'		=> $mem,
										'exdat2'		=> 0,
										'exdat3'		=> 0
									);

			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report	= $this->DB->fetch();
			$rid		= $the_report['id'];
			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'], 'seoname' => $data['members_seo_name'], 'seotemplate' => 'showuser' ), "id='{$rid}'" );
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
		
		$return_data = array( 
							'REDIRECT_URL'	=> $a_url,
							'REPORT_INDEX'	=> $rid,
							'SAVED_URL'		=> '/index.php?' . $url,
							'REPORT'		=> $build_report['report'],
							'TEMPLATE'		=> 'showuser',
							'SEOTITLE'		=> $data['members_seo_name'],
							);
		
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
		if( $report_data['SEOTITLE'] )
		{
			$name['members_seo_name']	= $report_data['SEOTITLE'];
		}
		else
		{
			$name	= $this->DB->buildAndFetch( array( 'select' => 'members_seo_name', 'from' => 'members', 'where' => 'member_id=' . intval($this->request['member_id']) ) );
		}
		
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'], $this->settings['base_url'] . $report_data['REDIRECT_URL'], $name['members_seo_name'], 'showuser' );
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
}