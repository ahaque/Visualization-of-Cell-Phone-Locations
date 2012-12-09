<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task Manager
 * Last Updated: $LastChangedDate: 2009-08-17 20:55:28 -0400 (Mon, 17 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 5022 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_system_taskmanager extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */	
	private $html;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Load Class */
		require_once( IPS_ROOT_PATH . '/sources/classes/class_taskmanager.php' );
		$this->func_taskmanager = new class_taskmanager( $registry );
		
		/* Load Skin and Language */
		$this->html = $this->registry->output->loadTemplate('cp_skin_system');
				
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		
		/* URLs */
		$this->form_code    = $this->html->form_code = 'module=system&amp;section=taskmanager';
		$this->form_code_js = $this->html->form_code_js = 'module=system&section=taskmanager';
		
		switch( $this->request['do'] )
		{		
			case 'task_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerForm( 'add' );
			break;
				
			case 'task_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerForm( 'edit' );
			break;
				
			case 'task_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerSave( 'add' );
			break;
				
			case 'task_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerSave( 'edit' );
			break;
				
			case 'task_run_now':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_run_lock' );
				$this->taskManagerRunTask();
			break;
				
			case 'task_unlock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_run_lock' );
				$this->taskManagerUnlockTask();
			break;
				
			case 'task_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_remove' );
				$this->taskDelete();
			break;
				
			case 'task_logs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tasklogs_view' );
				$this->taskLogsOverview();
			break;
			
			case 'task_log_show':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tasklogs_view' );
				$this->taskLogsShow();
			break;
				
			case 'task_log_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tasklogs_delete' );
				$this->taskLogsDelete();
			break;
				
			case 'task_export_xml':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->tasksExportToXML();
			break;
			
			case 'task_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskExport();
			break;
			
			case 'task_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskImport();
			break;

			case 'task_rebuild_xml':
			case 'tasksImportAllApps':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->tasksImportAllApps();
			break;
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->request['do'] = 'overview';
				$this->taskManagerOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Perform the task import
	 *
	 * @access	public
	 * @param	string		Raw XML code
	 * @return	void
	 */
	public function taskImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml();
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['tupload_failed'];
			$this->_bbcodeStart();
			return;
		}

		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$tasks = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tasks[ $r['task_key'] ] = 1;
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->fetchElements('row') as $task )
		{
			$entry  = $xml->fetchElementsFromRecord( $task );

			unset($entry['task_id']);
			unset($entry['task_next_run']);
			
			/* Update */
			$entry['task_cronkey']     = ( $entry['task_cronkey'] )     ? $entry['task_cronkey']     : md5( uniqid( microtime() ) );
			$entry['task_next_run']    = ( $entry['task_next_run'] )    ? $entry['task_next_run']    : time();
			$entry['task_description'] = ( $entry['task_description'] ) ? $entry['task_description'] : '';	
				
			if ( $tasks[ $entry['task_key'] ] )
			{
				$this->DB->update( 'task_manager', $entry, "task_key='" . $entry['task_key'] . "'" );
			}
			else
			{
				$this->DB->insert( 'task_manager', $entry );
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['t_simport_success'];
		$this->taskManagerOverview();
	}
	
	/**
	 * Remove a task log
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskLogsDelete()
	{
		/* INIT */
		$prune = intval( $this->request['task_prune'] ) ? intval( $this->request['task_prune'] ) : 30;
		$prune = time() - ( $prune * 86400 );
		
		if( $this->request['task_title'] != -1 )
		{
			$where = "log_title='{$this->request['task_title']}' AND log_date > {$prune}";
		}
		else
		{
			$where = "log_date > {$prune}";
		}
		
		/* Delete */
		$this->DB->delete( 'task_logs', $where );
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['t_removed'];
		$this->taskLogsOverview();
	}	
	
	/**
	 * Show task logs
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskLogsShow()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=system&section=taskmanager&do=task_logs', $this->lang->words['sched_error_logs'] );
		
		/* INIT */
		$limit = intval( $this->request['task_count'] ) ? intval( $this->request['task_count'] ) : 30;
		$limit = $limit > 150 ? 150 : $limit;
		
		/* Query the tasks */
		if ( $this->request['task_title'] != -1 )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'task_logs', 'where' => "log_title='".$this->request['task_title']."'", 'order' => 'log_date DESC', 'limit' => array(0,$limit) ) );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'task_logs', 'order' => 'log_date DESC', 'limit' => array(0,$limit) ) );
		}
		
		$this->DB->execute();
		
		/* Loop through the tasks */
		$rows = array();
		
		while( $row = $this->DB->fetch() )
		{
			$row['log_date'] = ipsRegistry::getClass( 'class_localization')->getDate( $row['log_date'], 'TINY' );
			$rows[] = $row;
		}
		
		$this->registry->output->html .= $this->html->taskManagerLogsShowWrapper( $rows );
	}	
	
	/**
	 * Builds the task log overview screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskLogsOverview()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=system&section=taskmanager&do=task_logs', $this->lang->words['sched_error_logs'] );
		
		/* INIT */
		$tasks = array( 0 => array( -1, 'All tasks' ) );
		$last5 = "";
		$form  = array();
		
		/* Get thet ask titles */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'order' => 'task_title' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tasks[] = array( $r['task_title'], $r['task_title'] );
		}
		
		/* Get the last 5 logs */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_logs', 'order' => 'log_date DESC', 'limit' => array(0,5) ) );
		$this->DB->execute();
		
		$last5 = array();
		while ( $row = $this->DB->fetch() )
		{
			//$row['log_desc'] = IPSText::truncate( $row['log_desc'] );
			$row['log_date'] = $this->registry->class_localization->getDate( $row['log_date'], 'TINY' );
			$last5[] = $row;
		}
		
		/* Build the form elements */
		$form['task_title']         = $this->registry->output->formDropdown( 'task_title', $tasks, $this->request['task_title'] );
		$form['task_title_delete']  = $this->registry->output->formDropdown( 'task_title', $tasks, $this->request['task_title_delete'] );
		$form['task_count']         = $this->registry->output->formInput(    'task_count', $this->request['task_count'] ? $this->request['task_count'] : 30 );
		$form['task_prune']         = $this->registry->output->formInput(    'task_prune', $this->request['task_prune'] ? $this->request['task_prune'] : 30 );
		
		/* Output */
		$this->registry->output->html .= $this->html->taskManagerLogsOverview( $last5, $form );
	}	
	
	/**
	 * Delete a task
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 */
	public function taskDelete( $type='add' )
	{
		/* INIT */
		$task_id = intval( $this->request['task_id'] );
		
		/* Check to see if this is a valid task */
		$task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id=$task_id" ) );
			
		if ( $task['task_safemode'] and ! IN_DEV )
		{
			$this->registry->output->global_message = $this->lang->words['t_nodelete'];
			$this->taskManagerOverview();
			return;
		}
		
		/* Delete this task */
		$this->DB->delete( 'task_manager', 'task_id='.$task_id );
		
		/* Save next date and bounce */
		$this->func_taskmanager->saveNextRunStamp();
		
		$this->registry->output->global_message = $this->lang->words['t_deleted'];		
		$this->taskManagerOverview();
	}	
	
	/**
	 * Unlock a task
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskManagerUnlockTask()
	{
		/* INIT */
		$task_id = intval( $this->request['task_id'] );
		
		$this->func_taskmanager->unlockTask( $task_id );
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['t_locknomore'];
		$this->taskManagerOverview();
		return;
	}	
	
	/**
	 * Runs the selected task
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskManagerRunTask()
	{
		/* INIT */
		$task_id = intval( $this->request['task_id'] );
		
		/* Check ID */
		if ( ! $task_id )
		{
			$this->registry->output->global_message = $this->lang->words['t_noid'];
			$this->taskManagerOverview();
			return;
		}
		
		/* Query the task */
		$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => 'task_id=' . $task_id ) );
		
		/* NO task found */
		if ( ! $this_task['task_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_notask'];
			$this->taskManagerOverview();
			return;
		}
		
		/* Disabled? */
		if ( ! $this_task['task_enabled'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_disabled'];
			$this->taskManagerOverview();
			return;
		}

		/* Locked */
		if ( $this_task['task_locked'] > 0 && ! IN_DEV )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_locked'], gmstrftime( '%c', $this_task['task_locked'] ) );
			$this->taskManagerOverview();
			return;
		}
		
		/* Get the next rund ate and then update the task */
		$newdate = $this->func_taskmanager->generateNextRun( $this_task );
				
		$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_locked' => time() ), "task_id=".$task_id );
		
		$this->func_taskmanager->saveNextRunStamp();

		/* Run the task file */
		if( file_exists( IPSLib::getAppDir( $this_task['task_application'] ) . '/tasks/' . $this_task['task_file'] ) )
		{
			require_once( IPSLib::getAppDir( $this_task['task_application'] ) . '/tasks/' . $this_task['task_file'] );
			$myobj = new task_item( $this->registry, $this->func_taskmanager, $this_task );
			$myobj->runTask();

			
			/* Bounce */
			$this->registry->output->global_message = $this->lang->words['t_ran'];
			$this->taskManagerOverview();
			return;
		}
		/* Error locating task file */
		else
		{
			/* Bounce */
			$this->registry->output->global_message = sprintf( $this->lang->words['t_nolocate'], IPSLib::getAppDir( $this_task['task_application'] ),  $this_task['task_file'] );
			$this->taskManagerOverview();
			return;
		}
		
	}	
	
	/**
	 * Save the add/edit task form
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit	 
	 * @return	void
	 */
	public function taskManagerSave( $type='add' )
	{
		/* INIT */
		$task_id      = intval( $this->request['task_id'] );
		$task_cronkey = $this->request['task_cronkey'];
		
		/* Check for ID */
		if ( $type == 'edit' )
		{
			if ( ! $task_id )
			{
				$this->registry->output->global_message = $this->lang->words['t_noid'];
				$this->taskManagerForm();
				return;
			}
		}
		
		/* Check for title */
		if ( ! $this->request['task_title'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_entertitle'];
			$this->taskManagerForm();
			return;
		}
		
		/* Check for file */
		if ( ! $this->request['task_file'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_entername'];
			$this->taskManagerForm();
			return;
		}
		
		/* Create the database array */
		$save = array( 'task_title'       => $this->request['task_title'],
					   'task_description' => $this->request['task_description'],
					   'task_file'        => $this->request['task_file'],
					   'task_week_day'    => $this->request['task_week_day'],
					   'task_month_day'   => $this->request['task_month_day'],
					   'task_hour'        => $this->request['task_hour'],
					   'task_minute'      => $this->request['task_minute'],
					   'task_log'		  => $this->request['task_log'],
					   'task_cronkey'     => $this->request['task_cronkey'] ? $task_cronkey : md5(microtime()),
					   'task_enabled'     => $this->request['task_enabled'],
					   'task_application' => $this->request['task_application'],
					 );
		
		if ( IN_DEV )
		{
			$save['task_key']      = $this->request['task_key'];
			$save['task_safemode'] = $this->request['task_safemode'];
		}

		/* Find out the next weekday */		
		if( $this->request['task_week_day'] != -1 )
		{
			$week_days = array(
								0 => 'Sunday',
								1 => 'Monday',
								2 => 'Tuesday',
								3 => 'Wednesday',
								4 => 'Thursday',
								5 => 'Friday',
								6 => 'Saturday',
							);
							
			$_ts = strtotime( "Next {$week_days[$this->request['task_week_day']]}" );
			
			$this->func_taskmanager->date_now['minute']      = intval( gmdate( 'i', $_ts ) );
			$this->func_taskmanager->date_now['hour']        = intval( gmdate( 'H', $_ts ) );
			$this->func_taskmanager->date_now['wday']        = intval( gmdate( 'w', $_ts ) );
			$this->func_taskmanager->date_now['mday']        = intval( gmdate( 'd', $_ts ) );
			$this->func_taskmanager->date_now['month']       = intval( gmdate( 'm', $_ts ) );
			$this->func_taskmanager->date_now['year']        = intval( gmdate( 'Y', $_ts ) );			
		}
		
		/* Get next run date */
		$save['task_next_run'] = $this->func_taskmanager->generateNextRun( $save );
		
		/* Edit */
		if ( $type == 'edit' )
		{
			$this->DB->update( 'task_manager', $save, 'task_id='.$task_id );
			$this->registry->output->global_message = $this->lang->words['t_edited'];
		}
		/* Add */
		else
		{
			$this->DB->insert( 'task_manager', $save );
			$this->registry->output->global_message = $this->lang->words['t_saved'];
		}
		
		/* Save next run and bounce */
		$this->func_taskmanager->saveNextRunStamp();		
		$this->taskManagerOverview();
	}	
	
	/**
	 * Builds the add/edit task form
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 */
	public function taskManagerForm( $type='add' )
	{
		/* INIt */		
		$form     = array();
		$task_id  = intval( $this->request['task_id'] );
		$dropdows = array();
		$apps     = array();
		
		/* Application drop down options */
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$apps[] = array( $app_dir, $app_data['app_title'] );
		}
		
		/* Edit Task Form */
		if ( $type == 'edit' )
		{
			/* Form bits */
			$title   = "Editing Task: ".$group['cb_group_name'];
			$button  = "Edit Task";
			$formbit = "task_edit_do";
			
			
			/* Form Data */
			$task  = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id=$task_id" ) );
			
			if ( $task['task_safemode'] and ! IN_DEV )
			{
				$this->registry->output->global_message = $this->lang->words['t_noedit'];
				$this->taskManagerOverview();
				return;
			}			
		}
		/* Add Task Form */
		else
		{
			/* Form Bits */
			$button  = $this->lang->words['t_create'];
			$formbit = "task_add_do";			
			$title   = $this->lang->words['t_creating'];
			
			/* Form Data */
			$task    = array();
		}
		
		/* Create dropdown data */
		$dropdown['_minute'] = array( 0 => array( '-1', $this->lang->words['t_minute']   ) );
		$dropdown['_hour']   = array( 0 => array( '-1', $this->lang->words['t_hour']    ), 1 => array( '0', $this->lang->words['t_midnight'] ) ); 
		$dropdown['_wday']   = array( 0 => array( '-1', $this->lang->words['t_dayweek'] ) );
		$dropdown['_mday']   = array( 0 => array( '-1', $this->lang->words['t_daymonth'] ) );
		
		for( $i = 0 ; $i < 60; $i++ )
		{
			$dropdown['_minute'][] = array( $i, $i );
		}
		
		for( $i = 1 ; $i < 24; $i++ )
		{
			if ( $i < 12 )
			{
				$ampm = $i. $this->lang->words['t_am'];
			}
			else if ( $i == 12 )
			{
				$ampm = $this->lang->words['t_midday'];
			}
			else
			{
				$ampm = $i - 12 . $this->lang->words['t_pm'];
			}
			
			$dropdown['_hour'][] = array( $i, $i. ' - ('.$ampm.')' );
		}
		
		for( $i = 1 ; $i < 32; $i++ )
		{
			$dropdown['_mday'][] = array( $i, $i );
		}
		
		$dropdown['_wday'][]  = array( '0', $this->lang->words['t_sunday']     );
		$dropdown['_wday'][]  = array( '1', $this->lang->words['t_monday']     );
		$dropdown['_wday'][]  = array( '2', $this->lang->words['t_tuesday']    );
		$dropdown['_wday'][]  = array( '3', $this->lang->words['t_wednesday']  );
		$dropdown['_wday'][]  = array( '4', $this->lang->words['t_thursday']   );
		$dropdown['_wday'][]  = array( '5', $this->lang->words['t_friday']     );
		$dropdown['_wday'][]  = array( '6', $this->lang->words['t_saturday']   );
		
		/* Form Elements */
		$form['task_title']       = $this->registry->output->formInput(        'task_title'      , $this->request['task_title']       ? $this->request['task_title']       : $task['task_title'] );
		$form['task_description'] = $this->registry->output->formInput(        'task_description', $this->request['task_description'] ? $this->request['task_description'] : $task['task_description'] );
		$form['task_file']        = $this->registry->output->formSimpleInput( 'task_file'       , $this->request['task_file']        ? $this->request['task_file']        : $task['task_file']       , '20' );
		$form['task_minute']      = $this->registry->output->formDropdown(     'task_minute'     , $dropdown['_minute']               , $this->request['task_minute']      ? $this->request['task_minute']    : $task['task_minute']  ,  '', 'onchange="updatepreview()"' );
		$form['task_hour']        = $this->registry->output->formDropdown(     'task_hour'       , $dropdown['_hour']                 , $this->request['task_hour']        ? $this->request['task_hour']      : $task['task_hour']     , '', 'onchange="updatepreview()"' );
	    $form['task_week_day']    = $this->registry->output->formDropdown(     'task_week_day'   , $dropdown['_wday']                 , $this->request['task_week_day']    ? $this->request['task_week_day']  : $task['task_week_day'] , '', 'onchange="updatepreview()"' );
		$form['task_month_day']   = $this->registry->output->formDropdown(     'task_month_day'  , $dropdown['_mday']                 , $this->request['task_month_day']   ? $this->request['task_month_day'] : $task['task_month_day'], '', 'onchange="updatepreview()"' );
		$form['task_log']         = $this->registry->output->formYesNo(       'task_log'        , $this->request['task_log']         ? $this->request['task_log']         : $task['task_log'] );
		$form['task_enabled']     = $this->registry->output->formYesNo(       'task_enabled'    , $this->request['task_enabled']     ? $this->request['task_enabled']     : $task['task_enabled'] );
		$form['task_application'] = $this->registry->output->formDropdown(     'task_application', $apps, $this->request['task_application'] ? $this->request['task_application'] : $task['task_application'] );
		
		if ( IN_DEV )
		{
			$form['task_key']      = $this->registry->output->formInput(  'task_key'     , $this->request['task_key']      ? $this->request['task_key']      : $task['task_key'] );
			$form['task_safemode'] = $this->registry->output->formYesNo( 'task_safemode', $this->request['task_safemode'] ? $this->request['task_safemode'] : $task['task_safemode'] );
		}
		
		$this->registry->output->html .= $this->html->taskManagerForm( $form, $button, $formbit, $type, $title, $task );
		
		if ( $type == 'add' )
		{
			$this->registry->output->extra_nav[] = array( "", $this->lang->words['t_adding'] );
		}
		else
		{
			$this->registry->output->extra_nav[] = array( "", sprintf( $this->lang->words['t_editing'], $task['task_title'] ) );
		}
	}	
	
	/**
	 * Builds the task mananger overview screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskManagerOverview()
	{
		/* INIT */
		$tasks   = array();
		$row     = array();
		$content = "";
		
		/* Query the tasks */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'order' => 'task_safemode, task_next_run' ) );
		$this->DB->execute();
		
		/* Loop through and build the output array */
		while ( $row = $this->DB->fetch() )
		{
			$row['task_minute']    = $row['task_minute']    != '-1' ? $row['task_minute']    : '-';
			$row['task_hour']      = $row['task_hour']      != '-1' ? $row['task_hour']      : '-';
			$row['task_month_day'] = $row['task_month_day'] != '-1' ? $row['task_month_day'] : '-';
			$row['task_week_day']  = $row['task_week_day']  != '-1' ? $row['task_week_day']  : '-';
			
			if ( time() > $row['task_next_run'] )
			{
				$row['_image'] = 'task_run_now.gif';
			}
			else
			{
				$row['_image'] = 'task_run.gif';
			}
			
			$row['_next_run'] = gmstrftime( '%c', $row['task_next_run'] );
			
			if ( $row['task_enabled'] != 1 )
			{
				$row['_class']    = " style='color:gray'";
				$row['_title']    = $this->lang->words['t_disabledcaps'];
				$row['_next_run'] = "<span style='color:gray'><s>{$row['_next_run']}</s></span>";
			}
			
			if ( $row['task_locked'] )
			{
				$row['_title'] .= $this->lang->words['t_lockedcaps'];
			}
			
			$tasks[ $row['task_application'] ][] = $row;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->taskManagerOverview( $tasks, gmstrftime( '%c' ) );
	}	
	
	/**
	 * Imports tasks from XML
	 *
	 * @access	public
	 * @param	string	$file		Filename to import tasks from
	 * @param	bool	$no_return	Set to return true/false, instead of displaying results
	 * @return	void
	 */	
	public function tasksImportFromXML( $file='', $no_return=0 )
	{
		/* INIT */
		$file     = ( $file ) ? $file : IPS_PUBLIC_PATH . 'resources/tasks.xml';
		$inserted = 0;
		$updated  = 0;
		$tasks    = array();
		
		/* Check to see if the file exists */
		if ( ! file_exists( $file ) )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_import404'], $file );
			$this->taskManagerOverview();
			return;
		}
		
		/* Grab current tasks */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager' ) );												
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$tasks[ $row['task_key'] ] = $row;
		}
		
		/* XML Class */
		require_once( IPS_KERNEL_PATH.'class_xml.php' );		
		$xml = new class_xml();		
				
		/* Read the xml file */
		$skin_content = implode( '', file( $file ) );
		
		/* Parse the xml file contents */
		$xml->xml_parse_document( $skin_content );
		
		/* Fix up */
		if ( ! is_array( $xml->xml_array['export']['group']['row'][0]  ) )
		{
			/* Ensure [0] is populated */
			$tmp = $xml->xml_array['export']['group']['row'];
			unset($xml->xml_array['export']['group']['row']);
			$xml->xml_array['export']['group']['row'][0] = $tmp;
		}
		
		/* Make sure we have some task data */
		if ( ! is_array( $xml->xml_array['export']['group']['row'][0] ) OR ! count( $xml->xml_array['export']['group']['row'][0] ) )
		{
			if ( $no_return )
			{
				return FALSE;
			}
			else
			{
				$this->registry->output->global_message = $this->lang->words['t_noupdate'];
				$this->taskManagerOverview();
			}
		}
		
		/* Loop through the tasks */
		if ( is_array( $xml->xml_array['export']['group']['row'] ) AND count( $xml->xml_array['export']['group']['row'] ) )
		{
			foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
			{
				$newrow = array();
			
				$_key = $entry['task_key']['VALUE'];
		
				foreach( $entry as $f => $data )
				{
					if ( $f == 'VALUE' or $f == 'task_id' )
					{
						continue;
					}
				
					if ( $f == 'task_cronkey' )
					{
						$entry[ $f ]['VALUE'] = $tasks[ $_key ]['task_cronkey'] ? $tasks[ $_key ]['task_cronkey'] : md5( uniqid( microtime() ) );
					}
				
					if ( $f == 'task_next_run' )
					{
						$entry[ $f ]['VALUE'] = $tasks[ $_key ]['task_next_run'] ? $tasks[ $_key ]['task_next_run'] : time();
					}
				
					$newrow[$f] = $entry[ $f ]['VALUE'];
				}
				
				$newrow['task_description'] = ( $newrow['task_description'] ) ? $newrow['task_description'] : '';
			
				if ( $tasks[ $_key ]['task_key'] )
				{
					$updated++;
					$this->DB->update( 'task_manager', $newrow, "task_key='" . $tasks[ $_key ]['task_key'] . "'" );
				}
				else
				{
					$inserted++;
					$this->DB->insert( 'task_manager', $newrow );
				}
			}
		}
		
		/* Return or Bounce */
		if ( $no_return )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_inserted'], $inserted, $updated );
			return TRUE;
		}
		else
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_inserted'], $inserted, $updated );
			$this->taskManagerOverview();
		}
	}
	
	/**
	 * Import all tasks from XML
	 *
	 * @access	public
	 * @param	bool	$no_return
	 * @return	void
	 */
	public function tasksImportAllApps()
	{
		/* INIT */
		$tasks = array();
		
		/* Grab current tasks */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager' ) );												
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$tasks[ $row['task_key'] ] = $row;
		}
		
		/* Grab XML class */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		
		/* Loop through all the applications */
		foreach( $this->registry->getApplications() as $app => $__data )
		{
			$stats = array( 'inserted' => 0, 'updated' => 0 );
			$xml   = new classXML( IPS_DOC_CHAR_SET );
			$file  = IPSLib::getAppDir(  $app ) . '/xml/' . $app . '_tasks.xml';
			
			if( file_exists( $file ) )
			{
				$xml->load( $file );
				
				foreach( $xml->fetchElements('row') as $task )
				{
					$entry = $xml->fetchElementsFromRecord( $task );
					
					/* Remove unneeded */
					unset( $entry['task_id'] );
					unset( $entry['task_next_run'] );
					
					/* Update */
					$entry['task_cronkey']     = ( $entry['task_cronkey'] )     ? $entry['task_cronkey']     : md5( uniqid( microtime() ) );
					$entry['task_next_run']    = ( $entry['task_next_run'] )    ? $entry['task_next_run']    : time();
					$entry['task_description'] = ( $entry['task_description'] ) ? $entry['task_description'] : '';	
						
					if ( $tasks[ $entry['task_key'] ]['task_key'] )
					{
						$stats['updated']++;
						$this->DB->update( 'task_manager', $entry, "task_key='" . $entry['task_key'] . "'" );
					}
					else
					{
						$stats['inserted']++;
						$this->DB->insert( 'task_manager', $entry );
					}
				}
			}
			
			$this->registry->output->global_message .= $app . ': ' . sprintf( $this->lang->words['t_inserted'], $stats['inserted'], $stats['updated'] ) . '<br />';
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['tasks'][ $app ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
	
		/* Return or Bounce */
		if ( $no_return )
		{
			return TRUE;
		}
		else
		{
			$this->taskManagerOverview();
		}
	}

	/**
	 * Export a single task
	 *
	 * @access	public
	 * @return	void
	 */
	public function taskExport()
	{
		/* INIT */
		$entry	= array();
		$id		= intval($this->request['task_id']);
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'export' );
		$xml->addElement( 'group', 'export' );

		/* Query tasks */
		$r	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id='{$id}'" ) );		

		$xml->addElementAsRecord( 'group', 'row', $r );
			
		/* Finish XML */	
		$doc = $xml->fetchDocument();

		$this->registry->output->showDownload( $doc, 'task.xml', '', 0 );
	}

	/**
	 * Export tasks to XML
	 *
	 * @access	public
	 * @return	void
	 */
	public function tasksExportToXML()
	{
		/* INIT */
		$entry = array();
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		
		/* Loop through all the applications */
		foreach( $this->registry->getApplications() as $app => $__data )
		{
			$_c  = 0;
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->newXMLDocument();
			$xml->addElement( 'export' );
			$xml->addElement( 'group', 'export' );

			/* Query tasks */
			$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_application='{$app}'" ) );		
			$this->DB->execute();
			
			/* Loop through and add tasks to XML */
			while ( $r = $this->DB->fetch() )
			{
				$_c++;
				$xml->addElementAsRecord( 'group', 'row', $r );
			}
			
			/* Finish XML */	
			$doc = $xml->fetchDocument();
			
			/* Write */
			if ( $doc  AND $_c)
			{
				@unlink( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_tasks.xml' );
				$fh = fopen( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_tasks.xml', 'w' );
				fwrite( $fh, $doc );
				fclose( $fh );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['t_exported'];
		$this->taskManagerOverview();		
	}
	
}