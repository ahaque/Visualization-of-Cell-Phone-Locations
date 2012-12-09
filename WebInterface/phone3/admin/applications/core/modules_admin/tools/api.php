<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * API Users
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 5027 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_tools_api extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin...
		//-----------------------------------------
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate('cp_skin_api');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=api';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=api';
		
		//-----------------------------------------
		// What are we to do, today?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'api_list':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiList();
			break;
			case 'api_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiForm( 'add' );
			break;
			case 'api_add_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiSave( 'add' );
			break;
			case 'api_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiForm( 'edit' );
			break;
			case 'api_edit_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiSave( 'edit' );
			break;
			case 'api_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_remove' );
				$this->apiRemove();
			break;
			
			case 'log_list':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_logs' );
				$this->logList();
			break;
			case 'log_view_detail':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_logs' );
				$this->logViewDetail();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * API Logs View
	 * View API Log
	 *
	 * @access	private
	 * @return	void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	private function logViewDetail()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=api&do=log_list', $this->lang->words['api_error_logs'] );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_log_id = intval( $this->request['api_log_id'] );
		
		//-----------------------------------------
		// Get data from the deebee
		//-----------------------------------------
		
		$log = $this->DB->buildAndFetch( array( 'select' => '*',
															 	 'from'   => 'api_log',
															 	 'where'  => 'api_log_id='.$api_log_id ) );
															
		if ( ! $log['api_log_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_lognoid'];
			$this->logList();
			return;
		}
		
		//-----------------------------------------
		// Display...
		//-----------------------------------------
		
		$log['_api_log_date'] 		= ipsRegistry::getClass( 'class_localization')->getDate( $log['api_log_date'], 'LONG' );
		$log['_api_log_allowed']    = $log['api_log_allowed'] ? 'aff_tick.png' : 'aff_cross.png';
		$log['_api_log_query']      = htmlspecialchars( $log['api_log_query'] );
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->api_log_detail( $log );
		
		$this->registry->output->printPopupWindow();
	}
	

	/**
	 * API Logs List
	 * List API Logs
	 *
	 * @access	private
	 * @return	void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	private function logList()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=api&do=log_list', $this->lang->words['api_error_logs'] );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start   = intval( $this->request['st'] );
		$perpage = 50;
		$logs    = array();
		
		//-----------------------------------------
		// Get log count
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
																   'from'   => 'api_log' ) );
																
		$links = $this->registry->output->generatePagination( array( 'totalItems'			=> intval( $count['count'] ),
																	 'itemsPerPage'			=> $perpage,
																	 'currentStartValue'	=> $start,
																	 'baseUrl'				=> $this->settings['base_url'].'&'.$this->form_code ) );
									  
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'api_log', 'order' => 'api_log_date DESC', 'limit' => array( $start, $perpage ) ) );
												
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_api_log_date']     = ipsRegistry::getClass('class_localization')->getDate( $row['api_log_date'], 'LONG' );
			$row['_api_log_allowed']  = $row['api_log_allowed'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$logs[] = $row;
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
				
		$this->registry->output->html .= $this->html->api_login_view( $logs, $links );
	}

	/**
	 * API User Remove
	 * Removes an API User
	 *
	 * @access	private
	 * @return	void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	private function apiRemove()
	{
		$api_user_id   = $this->request['api_user_id'] ? intval($this->request['api_user_id']) : 0;
		
		if( !$api_user_id )
		{
			$this->registry->output->global_message = $this->lang->words['a_whatuser'];
			$this->apiList();
			return;
		}
		
		$api_user = $this->DB->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'api_users',
																	  'where'  => 'api_user_id='.$api_user_id ) );
		
		if ( ! $api_user['api_user_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_user404'];
			$this->apiList();
			return;
		}
		
		$this->DB->delete( 'api_users', 'api_user_id='.$api_user_id );
		
		$this->registry->output->global_message = $this->lang->words['a_removed'];
		$this->apiList();
	}

	/**
	 * API Save
	 * Save API user
	 *
	 * @access	private
	 * @param	string		Type
	 * @return	void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	private function apiSave( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_user_id   = $this->request['api_user_id'] ? intval($this->request['api_user_id']) : 0;
		$api_user_key  = $this->request['api_user_key'];
		$api_user_name = $this->request['api_user_name'];
		$api_user_ip   = $this->request['api_user_ip'];
		$permissions = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( ! $api_user_name )
		{
			$this->registry->output->global_message = $this->lang->words['a_entertitle'];
			$this->apiForm( $type );
			return;
		}
		
		//-----------------------------------------
		// More checking...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			if ( ! $api_user_key )
			{
				$this->registry->output->global_message = $this->lang->words['a_noapikey'];
				$this->apiForm( $type );
				return;
			}
		}
		else
		{
			$api_user = $this->DB->buildAndFetch( array( 'select' => '*',
																		  'from'   => 'api_users',
																		  'where'  => 'api_user_id='.$api_user_id ) );
			
			if ( ! $api_user['api_user_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_user404'];
				$this->apiList();
				return;
			}
		}
		
		//-----------------------------------------
		// Save basics
		//-----------------------------------------
		
		$save = array( 'api_user_name' => $api_user_name,
					   'api_user_ip'   => $api_user_ip );
		
		//-----------------------------------------
		// Sort out permissions...
		//-----------------------------------------
		
		foreach( $this->request as $key => $value )
		{
			if ( preg_match( "#^_perm_([^_]+?)_(.*)$#", $key, $matches ) )
			{
				$module   = $matches[1];
				$function = $matches[2];
				
				if ( $value )
				{
					$permissions[ $module ][ $function ] = 1;
				}
			}
		}
	
		//-----------------------------------------
		// Add in perms
		//-----------------------------------------
		
		$save['api_user_perms'] = serialize( $permissions );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Add in key..
			//-----------------------------------------
			
			$save['api_user_key'] = $api_user_key;
			
			//-----------------------------------------
			// Save it...
			//-----------------------------------------
			
			$this->registry->output->global_message = $this->lang->words['a_added'];
			
			$this->DB->insert( 'api_users', $save );
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['a_edited'];
			
			$this->DB->update( 'api_users', $save, 'api_user_id=' . $api_user_id );
		}
		
		$this->apiList();
	}
	
	/**
	 * API Form
	 * Shows the add/edit form
	 *
	 * @access	private
	 * @param	string		Type
	 * @return	void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	private function apiForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_user_id = $this->request['api_user_id'] ? intval($this->request['api_user_id']) : 0;
		$form        = array();
		$permissions = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode  = 'api_add_save';
			$title     = $this->lang->words['a_createnew'];
			$button    = $this->lang->words['a_createnew'];
			$api_user  = array();
			$api_perms = array();
		}
		else
		{
			$api_user = $this->DB->buildAndFetch( array( 'select' => '*',
																		  'from'   => 'api_users',
																		  'where'  => 'api_user_id='.$api_user_id ) );
			
			if ( ! $api_user['api_user_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_user404'];
				$this->apiList();
				return;
			}
			
			$formcode = 'api_edit_save';
			$title    = $this->lang->words['a_edituser'].$api_user['api_user_name'];
			$button   = $this->lang->words['a_savechanges'];
			
			$api_perms = unserialize( $api_user['api_user_perms'] );
		}
		
		//-----------------------------------------
		// Form
		//-----------------------------------------
		
		$form['api_user_name'] = $this->registry->output->formInput( 'api_user_name', ( isset($_POST['api_user_name']) AND $_POST['api_user_name'] ) ? stripslashes($_POST['api_user_name']) : $api_user['api_user_name'] );
		$form['api_user_ip']   = $this->registry->output->formInput( 'api_user_ip', ( isset($_POST['api_user_ip']) AND $_POST['api_user_ip'] ) ? stripslashes($_POST['api_user_ip']) : $api_user['api_user_ip'] );
		
		//-----------------------------------------
		// Get all modules and stuff and other things
		//-----------------------------------------
		
		$path   = DOC_IPS_ROOT_PATH . 'interface/board/modules';
		
		if ( is_dir( $path ) )
		{
			$handle = opendir( $path );

			while ( ( $file = readdir($handle) ) !== FALSE )
			{
				if ( is_dir( $path . '/' . $file ) )
				{
					if ( file_exists( $path . '/' . $file . '/config.php' ) )
					{
						$_name = $file;
				
						require_once( $path . "/" . $file . '/config.php' );
									
						if ( $CONFIG['api_module_title'] )
						{
							$permissions[ $_name ] = array(  'key'    => $api_module_title,
															 'title'  => $CONFIG['api_module_title'],
															 'desc'   => $CONFIG['api_module_desc'],
															 'path'   => $path . "/" . $file,
															 'perms'  => array() );
															
							//-----------------------------------------
							// Get all available methods
							//-----------------------------------------
							
							if ( file_exists( $path . '/' . $file . '/methods.php' ) )
							{
								require_once( $path . '/' . $file . '/methods.php' );
								
								$permissions[ $_name ]['perms'] = array_keys( $ALLOWED_METHODS );
							}
							
							//-----------------------------------------
							// Sort out form field
							//-----------------------------------------
							
							if ( is_array( $permissions[ $_name ]['perms'] ) )
							{
								foreach( $permissions[ $_name ]['perms'] as $perm )
								{
									$_checked = intval( $api_perms[ $_name ][ $perm ] );
									$permissions[ $_name ]['form_perms'][ $perm ] = array( 'title' => $perm,
																						   'form'  => $this->registry->output->formCheckbox( '_perm_' . $_name . '_' . $perm, $_checked ) );
								}
							}
						}
						
						$CONFIG          = array();
						$ALLOWED_METHODS = array();
					}
				}
			}

			closedir( $handle );
		}
		
		//-----------------------------------------
		// Auto-generate API key
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$form['_api_user_key'] = md5( mt_rand() . $this->memberData['member_login_key'] . uniqid( mt_rand(), true ) );
		}
		
		$this->registry->output->html .= $this->registry->output->global_template->information_box( $this->lang->words['a_title'], $this->lang->words['a_msg2'] ) . "<br />";
		$this->registry->output->html .= $this->html->api_form( $form, $title, $formcode, $button, $api_user, $type, $permissions );
		
	}

	/**
	 * API LIST
	 * List all currently stored API users
	 *
	 * @access	private
	 * @return	void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	private function apiList()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$users = array();
		
		//-----------------------------------------
		// Get users from the DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'api_users', 'order' => 'api_user_id' ) );			
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$users[] = $row;
		}
		
		//-----------------------------------------
		// XML RPC Enabled?
		//-----------------------------------------
		
		if ( ! $this->settings['xmlrpc_enable'] )
		{
			$this->registry->output->html .= $this->registry->output->global_template->warning_box( $this->lang->words['a_disabled'], sprintf( $this->lang->words['a_msg3'], $this->settings['base_url'] ) ) .  "<br >";
		}
		
		//-----------------------------------------
		// Dun...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->api_list( $users );
	}
}
