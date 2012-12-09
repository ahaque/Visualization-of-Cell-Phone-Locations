<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Admin Login Logs
 * Last Updated: $LastChangedDate: 2009-03-20 12:37:44 -0400 (Fri, 20 Mar 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 4267 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_system_loginlog extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Check Permissions */
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'acplogin_log' );
		
		/* Language */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		/* URLs */
		$this->form_code    = 'module=system&amp;section=loginlog';
		$this->form_code_js = 'module=system&section=loginlog';
		
		/* Navigation */
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=system&section=loginlog', $this->lang->words['al_error_logs'] );
		
		/* Load lang and skin */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		$this->html = $this->registry->output->loadTemplate('cp_skin_system');
		
		switch( $this->request['do'] )
		{
			default:
				$this->loginLogsView();
			break;
			
			case 'view_detail':
				$this->loginLogDetails();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * View Details of a Log in Attempt
	 *
	 * @access	public
	 * @return	void
	 */
	public function loginLogDetails()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$admin_id = intval( $this->request['detail'] );
		
		//-----------------------------------------
		// Get data from the deebee
		//-----------------------------------------
		
		$log = $this->DB->buildAndFetch( array( 'select' => '*','from' => 'admin_login_logs', 'where' => 'admin_id='.$admin_id ) );
															
		if ( ! $log['admin_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['ll_noid'];
			$this->login_logs_view();
			return;
		}
		
		//-----------------------------------------
		// Display...
		//-----------------------------------------
		
		$log['_admin_time'] 		= $this->registry->class_localization->getDate( $log['admin_time'], 'LONG' );
		$log['_admin_post_details'] = unserialize( $log['admin_post_details'] );
		$log['_admin_img']          = $log['admin_success'] ? 'aff_tick.png' : 'aff_cross.png';
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->acp_last_logins_detail( $log );		
		$this->registry->output->printPopupWindow();
	}	
	
	/**
	 * View admin login logs
	 *
	 * @access	public
	 * @return	void
	 */
	public function loginLogsView()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start   = intval( $this->request['st'] );
		$perpage = 50;
			
		//-----------------------------------------
		// Get log count
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'admin_login_logs' ) );
																
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'        => intval( $count['count'] ),
																	'itemsPerPage'      => $perpage,
																	'currentStartValue' => $start,
																	'baseUrl'           => $this->settings['base_url'].$this->form_code 
															)	 );
									  
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from'  => 'admin_login_logs', 'order' => 'admin_time DESC', 'limit' => array( $start, $perpage ) ) );												
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_admin_time'] = $this->registry->class_localization->getDate( $row['admin_time'], 'ACP' );
			$row['_admin_img']  = $row['admin_success'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$logins .= $this->html->acp_last_logins_row( $row );
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->registry->output->global_template->information_box( $this->lang->words['ll_title'], $this->lang->words['ll_msg'] );
		$this->registry->output->html .= $this->html->acp_last_logins_wrapper( $logins, $links );
	}
}