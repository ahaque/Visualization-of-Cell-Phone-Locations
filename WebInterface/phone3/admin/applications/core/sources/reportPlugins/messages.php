<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report Center :: Private Messages plugin
 * Last Updated: $LastChangedDate: 2009-06-11 17:00:57 -0400 (Thu, 11 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4764 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class messages_plugin
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
		$req_input	= '';
		$return		= '';
		
		if( is_array($plugin_data['required_input']) && count($plugin_data['required_input']) > 0 )
		{
			foreach( $plugin_data['required_input'] as $key => $value )
			{
				if( $req_input != '' )
				{
					$req_input .= "\r\n";
				}

				$req_input .= $key . ' ' . $value;
			}
		}
		
		foreach( $this->cache->getCache('group_cache') as $g )
		{
			$groups[] = array( $g['g_id'], $g['g_title'] );
		}
		
		$return		.= $html->addRow(	$this->lang->words['r_messages_enter'],
										$this->lang->words['r_menter_desc'],
										$this->registry->output->formMultiDropdown('plugi_messages_add[]', $groups, explode( ',', $plugin_data['plugi_messages_add'] ) )
									);

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
		$save_data_array['plugi_messages_add']		= ( is_array($this->request['plugi_messages_add']) AND count($this->request['plugi_messages_add']) ) ? implode( ',', $this->request['plugi_messages_add'] ) : '';
		
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
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );

		$msg = intval($this->request['topicID']);
		
		if( ! $msg )
		{
			$this->registry->output->showError( 'reports_no_msg', 10166 );
		}

		$message = $this->DB->buildAndFetch( array( 
													'select'	=> 'mt_title', 
													'from'		=> 'message_topics', 
													'where'		=> 'mt_id=' . intval($msg) 
											)		);
		
		$ex_form_data = array(
								'topic'		=> $msg,
								'msg'		=> intval($this->request['msg']),
								'st'		=> intval($this->request['st']),
								'ctyp'		=> 'message',
								'title'		=> $message['mt_title']
							);
		
		$this->registry->output->setTitle( $this->lang->words['report_msg_page'] );
		$this->registry->output->addNavigation( $this->lang->words['t_welcome'], "app=members&amp;module=messaging" );
		$this->registry->output->addNavigation( $this->lang->words['report_msg_page'], '' );
		
		$this->lang->words['report_basic_title']		= $this->lang->words['report_msg_title'];
		$this->lang->words['report_basic_url_title']	= $this->lang->words['report_msg_title'];
		$this->lang->words['report_basic_enter']		= $this->lang->words['report_msg_msg'];
		
		$url = $this->settings['base_url'] . "app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=" . $ex_form_data['topic'] . "&amp;st=" . $ex_form_data['st'] . "#msg" . $ex_form_data['msg'];
		
		return $this->registry->getClass('reportLibrary')->showReportForm( $message['mt_title'], $url, $ex_form_data );
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
					'title'	=> $this->lang->words['report_section_title_msg'],
					'url'	=> "/index.php?app=core&amp;module=reports&amp;section=reports&amp;do=showMessage&amp;topicID={$report_row['exdat1']}&amp;st={$report_row['exdat3']}&amp;msg={$report_row['exdat2']}",
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
		$topic		= $this->request['topic'];
		$msg		= $this->request['msg'];
		$st			= $this->request['st'];
		$url		= 'app=members&module=messaging&section=view&do=showConversation&topicID=' . $topic;

		if( $msg < 1 )
		{
			$this->registry->output->showError( 'reports_no_msg', 10167 );
		}

		$return_data	= array();
		$a_url			= str_replace("&", "&amp;", $url);
		$uid			= md5(  'msg_' . $msg . '_' . $com_dat['com_id'] );
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
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();

		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->request['title'],
										'status'		=> $status['new'],
										'url'			=> '/index.php?' . $a_url,
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
										'exdat1'		=> $topic,
										'exdat2'		=> $msg,
										'exdat3'		=> $st,
									);

			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report	= $this->DB->fetch();
			$rid		= $the_report['id'];
			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'] ), "id='{$rid}'" );
		}
		
		$data = $this->DB->buildAndFetch( array(
												'select'	=> 't.mt_title',
												'from'		=> array( 'message_topics' => 't' ),
												'where'		=> 't.mt_id=' . $topic . ' AND m.msg_id=' . $msg,
												'add_join'	=> array(
																	array(
																		'select'	=> 'm.msg_post',
																		'from'		=> array( 'message_posts' => 'm' ),
																		'where'		=> 't.mt_id=m.msg_topic_id',
																		'type'		=> 'left',
																		),
																	array(
																		'select'	=> 'mem.member_id, mem.members_display_name',
																		'from'		=> array( 'members' => 'mem' ),
																		'where'		=> 'mem.member_id=m.msg_author_id',
																		'type'		=> 'left',
																		),
																	)
										)		);

		$message	= "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $data['members_display_name'] ) . "']";
		$message	.= $data['msg_post'];
		$message	.= "[/quote]\n\n";
		$message	.= $this->request['message'];
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';
		
		$build_report = array(
							'rid'			=> $rid,
							'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $message ),
							'report_by'		=> $this->memberData['member_id'],
							'date_reported'	=> time(),
							);
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$return_data = array( 
							'REDIRECT_URL'	=> "app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=" . $topic . "&amp;st=" . $st . "#msg" . $msg,
							'REPORT_INDEX'	=> $rid,
							'SAVED_URL'		=> '/index.php?' . $url,
							'REPORT'		=> $build_report['report']
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
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'], $this->settings['base_url'] . $report_data['REDIRECT_URL'] );
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