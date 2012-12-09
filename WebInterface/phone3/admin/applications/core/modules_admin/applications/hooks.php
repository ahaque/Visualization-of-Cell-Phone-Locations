<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Hooks Management
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		3.0.0
 * @version		$Revision: 5066 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_applications_hooks extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Hooks library
	 *
	 * @access	protected
	 * @var		object			Hooks library
	 */
	protected $hooksFunctions;
	
	/**
	 * Existing hooks
	 *
	 * @access	protected
	 * @var		array 			Existing hooks
	 */
	protected $hooks;	
	
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
		// Load skin
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_applications');
		
		//-----------------------------------------
		// Load hooks library
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('core') . '/sources/classes/hooksFunctions.php' );
		$this->hooksFunctions	= new hooksFunctions( $registry );
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_applications' ) );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=applications&amp;section=hooks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=applications&section=hooks';
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'disable_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_disableHook();
			break;
			case 'enable_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_enableHook();
			break;
			
			case 'uninstall_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_delete' );
				$this->_uninstallHook();
			break;
			case 'install_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_installHook();
			break;
			
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_reorder();
			break;
			
			case 'view_details':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_viewDetails();
			break;
			
			case 'check_requirements':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_checkRequirements();
			break;
			
			case 'export_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_exportHook();
			break;
			
			case 'do_export_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_doExportHook();
			break;
			
			case 'create_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookForm( 'add' );
			break;
			
			case 'edit_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookForm( 'edit' );
			break;
			
			case 'do_create_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookSave( 'add' );
			break;
			
			case 'do_edit_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookSave( 'edit' );
			break;
			
			case 'skinsRebuild':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_rebuildSkins();
			break;
			
			case 'removeDeadCaches':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->removeDeadCaches();
			break;

			case 'reimport_apps':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->reimportAppHooks();
			break;

			case 'hooks_overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->request['do'] = 'hooks_overview';
				$this->_hooksOverview();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Reimport all hooks for all installed applications
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function reimportAppHooks()
	{
		$stats	= array( 'inserted' => 0, 'updated' => 0, 'skipped' => 0 );
		
		foreach( ipsRegistry::$applications as $app )
		{
			$_stats	= $this->installAppHooks( $app['app_directory'] );
			
			$stats['inserted']	= $stats['inserted'] + $_stats['inserted'];
			$stats['updated']	= $stats['updated'] + $_stats['updated'];
			$stats['skipped']	= $stats['skipped'] + $_stats['skipped'];
		}
		
		//-----------------------------------------
		// Message
		//-----------------------------------------
		
		$this->registry->output->global_message = sprintf( $this->lang->words['app_hooks_rebuilt'], $stats['inserted'], $stats['updated'], $stats['skipped'] );
		
		//-----------------------------------------
		// Recache hooks so templates know that there
		// are comments to preserve
		//-----------------------------------------
		
		$this->rebuildHooksCache();
		
		//-----------------------------------------
		// Get the libs
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		$skinCaching	= new skinCaching( $this->registry );
		
		//-----------------------------------------
		// Find first skin id
		//-----------------------------------------
		
		ksort( $this->registry->output->allSkins );
		$_skins = $this->registry->output->allSkins;
		$_set   = array_shift( $_skins );
		$setID  = $_set['set_id'];
		
		$skinCaching->rebuildPHPTemplates( $setID );
		
		/* Fetch next id */
		$nextID = $setID;
		
		ksort( $this->registry->output->allSkins );
	
		foreach( $this->registry->output->allSkins as $id => $data )
		{
			if ( $id > $nextID )
			{
				$nextID = $id;
				break;
			}
		}
		
		//-----------------------------------------
		// Have more than one skin
		//-----------------------------------------
		
		if ( $nextID != $setID )
		{
			//-----------------------------------------
			// Save hook import messages
			//-----------------------------------------
			
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'hookResult', $this->registry->output->global_message );
			
			//-----------------------------------------
			// Wipe out the messages
			//-----------------------------------------
			
			$this->registry->output->global_message	= '';
			
			//-----------------------------------------
			// Redirect to rebuild skins
			//-----------------------------------------
			
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
			
			$this->registry->output->redirect( $this->settings['base_url'] . 'app=core&module=applications&section=hooks&do=skinsRebuild&setID=' . $nextID, $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'] );
		}
		
		/* Print message */
		
		$this->_hooksOverview();
	}
	
	/**
	 * Rebuild skins following hook import that included a template
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function removeDeadCaches()
	{
		$messages = array();
		$keep	  = array();
		$unlink	  = array();
		
		/* Grab all current hooks caches */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_hooks_files' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$keep[] = $row['hook_file_stored'];
		}
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'hooks' ) as $file )
			{
				if ( ! $file->isDot() )
				{
					$_name = $file->getFileName();
					
					if ( preg_match( "#_[a-z0-9]{32}\.php$#", $_name ) )
					{
						if ( ! in_array( $_name, $keep ) )
						{
							$unlink[] = $_name;
						}
					}
				}
			}
		} catch ( Exception $e ) { print $e->getMessage(); }
		
		/* Anything to unlink? */
		if ( count( $unlink ) )
		{
			foreach( $unlink as $file )
			{
				@unlink( DOC_IPS_ROOT_PATH . 'hooks/' . $file );
			}
		}
		
		/* Print message */
		$this->registry->output->global_message = count( $unlink ) . " Cached Hook Files Removed<br />" . implode( "<br />", $unlink );
		$this->_hooksOverview();
	}
	
	/**
	 * Rebuild skins following hook import that included a template
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _rebuildSkins()
	{
		$setID = intval( $this->request['setID'] );
		
		if( $setID )
		{
			//-----------------------------------------
			// Get the libs
			//-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
			$skinCaching	= new skinCaching( $this->registry );
							
			$skinCaching->rebuildPHPTemplates( $setID );
			
			/* Fetch next id */
			$nextID = $setID;
			
			ksort( $this->registry->output->allSkins );
		
			foreach( $this->registry->output->allSkins as $id => $data )
			{
				if ( $id > $nextID )
				{
					$nextID = $id;
					break;
				}
			}
			
			if ( $nextID != $setID )
			{
				$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
				
				/* More to go.. */
				$this->registry->output->redirect( $this->settings['base_url'] . 'app=core&module=applications&section=hooks&do=skinsRebuild&setID=' . $nextID, $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'] );
			}
		}
		
		//-----------------------------------------
		// Must be all done!
		//-----------------------------------------
		
		$message = ipsRegistry::getClass('adminFunctions')->staffGetCookie( 'hookResult' );
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'hookResult', '' );
		
		$this->registry->output->global_message	= $message;
		
		$this->rebuildHooksCache();
		
		$this->_hooksOverview();
	}

	/**
	 * Reorder hooks
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _reorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax			= new classAjax();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['hooks']) AND count($this->request['hooks']) )
 		{
 			foreach( $this->request['hooks'] as $this_id )
 			{
 				$this->DB->update( 'core_hooks', array( 'hook_position' => $position ), 'hook_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
		$this->rebuildHooksCache();

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Install a new hook
	 *
	 * @access	private
	 * @return   void
	 */
	private function _installHook()
	{
		//-----------------------------------------
		// Get uploaded schtuff
		//-----------------------------------------

		$tmp_name = $_FILES['FILE_UPLOAD']['name'];
		$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
		
		$content  = ipsRegistry::getClass('adminFunctions')->importXml( $tmp_name );
		
		if( ! $content )
		{
			$this->registry->output->showError( $this->lang->words['h_noxml'], 1110 );
		}

		$this->installHook( $content, TRUE );
		
		$this->rebuildHooksCache();
		
		$this->_hooksOverview();
	}
	
	/**
	 * Function mostly used in installer/upgrader
	 * Inserts new hooks
	 *
	 * @access	public
	 * @param	string		Application to install hooks for
	 * @todo 	Figure out how to upgrade hooks properly (db updates / template updates / file updates, etc)
	 */
	public function installAppHooks( $app )
	{
		static $hooks	= array();

		$msgs    = array( 'inserted' => 0, 'updated' => 0, 'skipped' => 0 );
		$hooks   = array();
		$xmlData = array();
		$path    = IPSLib::getAppDir( $app ) . '/xml';
		
		if ( file_exists( $path . '/hooks.xml' ) AND is_dir( $path . '/hooks' ) )
		{
			if( !count($hooks) )
			{
				/* Fetch current hooks */
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'core_hooks' ) );
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					if ( $row['hook_key'] )
					{
						$hooks[ $row['hook_key'] ] = $row;
					}
				}
			}
			
			/* Alright. We're in. Read the XML file */
			require_once( IPS_KERNEL_PATH . 'classXML.php' );
			$xml    = new classXML( IPS_DOC_CHAR_SET );

			/* Grab wrapper file */
			$xml->load(  $path . '/hooks.xml' );

			foreach( $xml->fetchElements('hook') as $data )
			{
				$xmlData[] = $xml->fetchElementsFromRecord( $data );
			}

			/* Examine XML */
			if ( is_array( $xmlData ) AND count( $xmlData ) )
			{
				foreach( $xmlData as $x )
				{
					if ( file_exists( $path . '/hooks/' . $x['file'] ) )
					{
						$xml->load(  $path . '/hooks/' . $x['file'] );
						
						foreach( $xml->fetchElements('config') as $data )
						{
							$config	= $xml->fetchElementsFromRecord( $data );
						}
						
						if ( ! isset( $hooks[ $config['hook_key'] ] ) )
						{
							/* Add it */
							$msgs['inserted']++;
							$this->installHook( file_get_contents( $path . '/hooks/' . $x['file'] ), FALSE, FALSE, $x['enabled'] );
						} 
						else
						{
							$this->installHook( file_get_contents( $path . '/hooks/' . $x['file'] ), FALSE, FALSE, $x['enabled'] );
							$msgs['updated']++;
						}
					}
				}
			}
		}
		
		return $msgs;
	}
	
	/**
	 * Public install hook so we can use it in the installer and elsewhere
	 *
	 * @access	public
	 * @param	string		XML data
	 * @param	boolean		Add message to output->global_message
	 * @param	boolean		Allow skins to recache
	 * @param	int			Install enabled
	 * @return	void
	 */
	public function installHook( $content, $addMessage=FALSE, $allowSkinRecache=TRUE, $enabled=1 )
	{
		//-----------------------------------------
		// Hooks directory writable?
		//-----------------------------------------
		
		if( !is_writable( IPS_HOOKS_PATH ) )
		{
			if( !$addMessage )
			{
				return false;
			}

			$this->registry->output->showError( $this->lang->words['h_dir_notwritable'], 111159 );
		}
		
		//-----------------------------------------
		// Got our hooks?
		//-----------------------------------------
		
		if( !is_array($this->hooks) OR !count($this->hooks) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$this->hooks[ $r['hook_key'] ]	= $r;
			}
		}

		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->loadXML( $content );

		foreach( $xml->fetchElements('config') as $data )
		{
			$config	= $xml->fetchElementsFromRecord( $data );

			if( !count($config) )
			{
				$this->registry->output->showError( $this->lang->words['h_xmlwrong'], 1111 );
			}
		}
		
		//-----------------------------------------
		// Temp
		//-----------------------------------------
		
		$tempExtraData	= unserialize( $config['hook_extra_data'] );

		//-----------------------------------------
		// Set config
		//-----------------------------------------
		
		$config			= array(
								'hook_name'				=> $config['hook_name'],
								'hook_desc'				=> $config['hook_desc'],
								'hook_author'			=> $config['hook_author'],
								'hook_email'			=> $config['hook_email'],
								'hook_website'			=> $config['hook_website'],
								'hook_update_check'		=> $config['hook_update_check'],
								'hook_requirements'		=> $config['hook_requirements'],
								'hook_version_human'	=> $config['hook_version_human'],
								'hook_version_long'		=> $config['hook_version_long'],
								'hook_key'				=> $config['hook_key'],
								'hook_enabled'			=> $enabled,
								'hook_updated'			=> time(),
								);

		$extra_data		= array();

		//-----------------------------------------
		// Set files
		//-----------------------------------------
		
		$files			= array();

		foreach( $xml->fetchElements('hookfiles') as $node )
		{
			foreach( $xml->fetchElements('file', $node) as $_file )
			{
				$file	= $xml->fetchElementsFromRecord( $_file );
	
				if( $file['hook_type'] )
				{
					$files[]	= array(
										'hook_file_real'	=> $file['hook_file_real'],
										'hook_type'			=> $file['hook_type'],
										'hook_classname'	=> $file['hook_classname'],
										'hook_data'			=> $file['hook_data'],
										'hooks_source'		=> $file['hooks_source'],
										);
				}
			}
		}

		//-----------------------------------------
		// Set the custom script
		//-----------------------------------------

		$custom			= array();

		foreach( $xml->fetchElements('hookextras_custom') as $node )
		{
			foreach( $xml->fetchElements('file', $node) as $_file )
			{
				$file	= $xml->fetchElementsFromRecord( $_file );
				
				if( count($file) )
				{
					$custom	= array(
									'filename'	=> $file['filename'],
									'source'	=> $file['source'],
									);
				}
			}
		}

		//-----------------------------------------
		// Set the settings
		//-----------------------------------------
		
		$settings		= array();
		$settingGroups	= array();

		foreach( $xml->fetchElements('hookextras_settings') as $node )
		{
			foreach( $xml->fetchElements('setting', $node) as $_setting )
			{
				$setting	= $xml->fetchElementsFromRecord( $_setting );
	
				if( $setting['conf_is_title'] == 1)
				{
					$settingGroups[]	= array(
												'conf_title_title'		=> $setting['conf_title_title'],
												'conf_title_desc'		=> $setting['conf_title_desc'],
												'conf_title_noshow'		=> $setting['conf_title_noshow'],
												'conf_title_keyword'	=> $setting['conf_title_keyword'],
												'conf_title_app'		=> $setting['conf_title_app'],
												'conf_title_tab'		=> $setting['conf_title_tab'],
												);
				}
				else
				{
					$settings[]			= array(
												'conf_title'			=> $setting['conf_title'],
												'conf_description'		=> $setting['conf_description'],
												'conf_group'			=> $setting['conf_group'],
												'conf_type'				=> $setting['conf_type'],
												'conf_key'				=> $setting['conf_key'],
												'conf_default'			=> $setting['conf_default'],
												'conf_extra'			=> $setting['conf_extra'],
												'conf_evalphp'			=> $setting['conf_evalphp'],
												'conf_protected'		=> $setting['conf_protected'],
												'conf_position'			=> $setting['conf_position'],
												'conf_start_group'		=> $setting['conf_start_group'],
												'conf_end_group'		=> $setting['conf_end_group'],
												'conf_add_cache'		=> $setting['conf_add_cache'],
												'conf_title_keyword'	=> $setting['conf_title_keyword'],
												);
				}
			}
		}

		//-----------------------------------------
		// Set the lang bits
		//-----------------------------------------
		
		$language		= array();

		foreach( $xml->fetchElements('hookextras_language') as $node )
		{
			foreach( $xml->fetchElements('language', $node) as $_langbit )
			{
				$langbit	= $xml->fetchElementsFromRecord( $_langbit );
				
				$language[]	= array(
									'word_app'			=> $langbit['word_app'],
									'word_pack'			=> $langbit['word_pack'],
									'word_key'			=> $langbit['word_key'],
									'word_default'		=> $langbit['word_default'],
									'word_custom'		=> $langbit['word_custom'],
									'word_js'			=> $langbit['word_js'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the modules
		//-----------------------------------------
		
		$modules		= array();

		foreach( $xml->fetchElements('hookextras_modules') as $node )
		{
			foreach( $xml->fetchElements('module', $node) as $_module )
			{
				$module		= $xml->fetchElementsFromRecord( $_module );
				$modules[]	= array(
									'sys_module_title'			=> $module['sys_module_title'],
									'sys_module_application'	=> $module['sys_module_application'],
									'sys_module_key'			=> $module['sys_module_key'],
									'sys_module_description'	=> $module['sys_module_description'],
									'sys_module_version'		=> $module['sys_module_version'],
									'sys_module_parent'			=> $module['sys_module_parent'],
									'sys_module_protected'		=> $module['sys_module_protected'],
									'sys_module_visible'		=> $module['sys_module_visible'],
									'sys_module_tables'			=> $module['sys_module_tables'],
									'sys_module_hooks'			=> $module['sys_module_hooks'],
									'sys_module_position'		=> $module['sys_module_position'],
									'sys_module_admin'			=> $module['sys_module_admin'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the help files
		//-----------------------------------------
		
		$help			= array();

		foreach( $xml->fetchElements('hookextras_help') as $node )
		{
			foreach( $xml->fetchElements('help', $node) as $_helpfile )
			{
				$helpfile	= $xml->fetchElementsFromRecord( $_helpfile );
				$help[]		= array(
									'title'			=> $helpfile['title'],
									'text'			=> $helpfile['text'],
									'description'	=> $helpfile['description'],
									'position'		=> $helpfile['position'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the templates
		//-----------------------------------------
		
		$templates		= array();

		foreach( $xml->fetchElements('hookextras_templates') as $node )
		{
			foreach( $xml->fetchElements('templates', $node) as $_template )
			{
				$template		= $xml->fetchElementsFromRecord( $_template );
				$templates[]	= array(
										'template_set_id'		=> 0,
										'template_group'		=> $template['template_group'],
										'template_content'		=> $template['template_content'],
										'template_name'			=> $template['template_name'],
										'template_data'			=> $template['template_data'],
										'template_updated'		=> $template['template_updated'],
										'template_removable'	=> $template['template_removable'],
										'template_added_to'		=> $template['template_added_to'],
										'template_user_added'	=> 1,
										'template_user_edited'  => 0,
										);
			}
		}
		
		//-----------------------------------------
		// Set the tasks
		//-----------------------------------------
		
		$tasks			= array();

		foreach( $xml->fetchElements('hookextras_tasks') as $node )
		{
			foreach( $xml->fetchElements('tasks', $node) as $_task )
			{
				$task		= $xml->fetchElementsFromRecord( $_task );
				$tasks[]	= array(
									'task_title'			=> $task['task_title'],
									'task_file'				=> $task['task_file'],
									'task_week_day'			=> $task['task_week_day'],
									'task_month_day'		=> $task['task_month_day'],
									'task_hour'				=> $task['task_hour'],
									'task_minute'			=> $task['task_minute'],
									'task_cronkey'			=> $task['task_cronkey'],
									'task_log'				=> $task['task_log'],
									'task_description'		=> $task['task_description'],
									'task_enabled'			=> $task['task_enabled'],
									'task_key'				=> $task['task_key'],
									'task_safemode'			=> $task['task_safemode'],
									'task_locked'			=> $task['task_locked'],
									'task_application'		=> $task['task_application'],
									);
			}
		}

		//-----------------------------------------
		// Set the database changes
		//-----------------------------------------
		
		$database		= array(
								'create'	=> array(),
								'alter'		=> array(),
								'update'	=> array(),
								'insert'	=> array(),
								);

		foreach( $xml->fetchElements('hookextras_database_create') as $node )
		{
			foreach( $xml->fetchElements('create', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['create'][]	= array(
											'name'			=> $table['name'],
											'fields'		=> $table['fields'],
											'tabletype'		=> $table['tabletype'],
											);
			}
		}

		foreach( $xml->fetchElements('hookextras_database_alter') as $node )
		{
			foreach( $xml->fetchElements('alter', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['alter'][]	= array(
											'altertype'		=> $table['altertype'],
											'table'			=> $table['table'],
											'field'			=> $table['field'],
											'newfield'		=> $table['newfield'],
											'fieldtype'		=> $table['fieldtype'],
											'default'		=> $table['default'],
											);
			}
		}

		foreach( $xml->fetchElements('hookextras_database_update') as $node )
		{
			foreach( $xml->fetchElements('update', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['update'][]	= array(
											'table'		=> $table['table'],
											'field'		=> $table['field'],
											'newvalue'	=> $table['newvalue'],
											'oldvalue'	=> $table['oldvalue'],
											'where'		=> $table['where'],
											);
			}
		}

		foreach( $xml->fetchElements('hookextras_database_insert') as $node )
		{
			foreach( $xml->fetchElements('insert', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['insert'][]	= array(
											'table'			=> $table['table'],
											'updates'		=> $table['updates'],
											'fordelete'		=> $table['fordelete'],
											);
			}
		}
		
		//-----------------------------------------
		// Set some vars for display tallies
		//-----------------------------------------

		$filesInserted			= 0;
		$settingGroupsInserted	= 0;
		$settingsInserted		= 0;
		$settingsUpdated		= 0;
		$languageInserted		= 0;
		$languageUpdated		= 0;
		$modulesInserted		= 0;
		$modulesUpdated			= 0;
		$helpInserted			= 0;
		$helpUpdated			= 0;
		$templatesInserted		= 0;
		$templatesUpdated		= 0;
		$templateHooks		    = 0;
		$tasksInserted			= 0;
		$tasksUpdated			= 0;
		$createQueries			= 0;
		$alterQueries			= 0;
		$updateQueries			= 0;
		$insertQueries			= 0;
		
		//-----------------------------------------
		// Need to recache skins?
		//-----------------------------------------
		
		foreach( $files as $_f )
		{
			if ( $_f['hook_type'] AND $_f['hook_type'] == 'templateHooks' )
			{
				$templateHooks++;
			}
		}
		
		//-----------------------------------------
		// Insert/update DB records
		//-----------------------------------------
		
		if( $this->hooks[ $config['hook_key'] ]['hook_id'] )
		{
			//-----------------------------------------
			// Don't change enabled/disabled status
			//-----------------------------------------
			
			unset( $config['hook_enabled'] );
			
			$this->DB->update( 'core_hooks', $config, 'hook_id=' . $this->hooks[ $config['hook_key'] ]['hook_id'] );
			
			$hook_id	= $this->hooks[ $config['hook_key'] ]['hook_id'];
			
			$extra_data	= unserialize( $this->hooks[ $config['hook_key'] ]['hook_extra_data'] );
		}
		else
		{
			$config['hook_installed']			= time();
			$this->hook[ $config['hook_key'] ]	= $config;
			
			$this->DB->insert( 'core_hooks', $config );
			
			$hook_id	= $this->DB->getInsertId();
			
			$this->hook[ $config['hook_key'] ]['hook_id']	= $hook_id;
			
			$extra_data['display']	= $tempExtraData['display'];
		}

		if( count($files) )
		{
			//-----------------------------------------
			// If we are updating, remove old files
			//-----------------------------------------
			
			if( $this->hooks[ $config['hook_key'] ]['hook_id'] )
			{
				$this->DB->build( array( 'select' => 'hook_file_id, hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $this->hooks[ $config['hook_key'] ]['hook_id'] ) );
				$outer = $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
					$this->DB->delete( 'core_hooks_files', 'hook_file_id=' . $r['hook_file_id'] );
				}
			}
				
			foreach( $files as $file )
			{
				//-----------------------------------------
				// Store new files
				//-----------------------------------------
				
				$filename	= $file['hook_classname'] . '_' . md5( uniqid( microtime(), true ) ) . '.php';
				
				file_put_contents( IPS_HOOKS_PATH . $filename, $file['hooks_source'] );
				chmod( IPS_HOOKS_PATH . $filename, 0777 );
				
				$file['hook_file_stored']	= $filename;
				$file['hook_hook_id']		= $hook_id;
				
				$this->DB->insert( 'core_hooks_files', $file );
				$filesInserted++;
			}
		}

		//-----------------------------------------
		// Put custom install/uninstall file
		//-----------------------------------------
		
		if( $custom['source'] AND $custom['filename'] )
		{
			file_put_contents( IPS_HOOKS_PATH . "install_" . $custom['filename'], $custom['source'] );
		}
		
		//-----------------------------------------
		// (1) Settings
		//-----------------------------------------
		
		if( count($settingGroups) OR count($settings) )
		{
			$setting_groups = array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'order' => 'conf_title_title' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$setting_groups[ $r['conf_title_id'] ] = $r;
				$setting_groups_by_key[ $r['conf_title_keyword'] ] = $r;
			}
			
			//-----------------------------------------
			// Get current settings.
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => 'conf_id, conf_key',
														  'from'   => 'core_sys_conf_settings',
														  'order'  => 'conf_id' ) );
			
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$cur_settings[ $r['conf_key'] ] = $r['conf_id'];
			}
		}
			
		if( count($settingGroups) )
		{
			$need_to_update = array();
			
			foreach( $settingGroups as $data )
			{
				if ( $data['conf_title_title'] AND $data['conf_title_keyword'] )
				{
					//-----------------------------------------
					// Get ID based on key
					//-----------------------------------------
					
					$conf_id = $setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];
					
					$save = array( 'conf_title_title'   => $data['conf_title_title'],
								   'conf_title_desc'    => $data['conf_title_desc'],
								   'conf_title_keyword' => $data['conf_title_keyword'],
								   'conf_title_app'     => $data['conf_title_app'],
								   'conf_title_tab'		=> $data['conf_title_tab'],
								   'conf_title_noshow'  => $data['conf_title_noshow']  );
					
					//-----------------------------------------
					// Not got a row, insert first!
					//-----------------------------------------
					
					if ( ! $conf_id )
					{
						$this->DB->insert( 'core_sys_settings_titles', $save );
						$conf_id = $this->DB->getInsertId();
						$settingGroupsInserted++;
						$extra_data['settingGroups'][] = $conf_id;
					}
					else
					{
						//-----------------------------------------
						// Update...
						//-----------------------------------------
						
						$this->DB->update( 'core_sys_settings_titles', $save, 'conf_title_id='.$conf_id );
					}
					
					//-----------------------------------------
					// Update settings cache
					//-----------------------------------------
					
					$save['conf_title_id']									= $conf_id;
					$setting_groups_by_key[ $save['conf_title_keyword'] ]	= $save;
					$setting_groups[ $save['conf_title_id'] ]				= $save;
						
					//-----------------------------------------
					// Set need update...
					//-----------------------------------------
					
					$need_update[] = $conf_id;
				}
			}
		}
		
		if( count($settings) )
		{
			foreach( $settings as $idx => $data )
			{
				$data['conf_group'] = $setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];
				
				//-----------------------------------------
				// Remove from array
				//-----------------------------------------
				
				unset( $data['conf_title_keyword'] );
				
				if ( $cur_settings[ $data['conf_key'] ] )
				{
					$this->DB->update( 'core_sys_conf_settings', $data, 'conf_id='.$cur_settings[ $data['conf_key'] ] );
					$settingsUpdated++;
				}
				else
				{
					$this->DB->insert( 'core_sys_conf_settings', $data );
					$settingsInserted++;
					$conf_id = $this->DB->getInsertId();
					$extra_data['settings'][] = $conf_id;
				}
			}
		}
		
		//-----------------------------------------
		// Update group counts...
		//-----------------------------------------
		
		if ( count( $need_update ) )
		{
			foreach( $need_update as $i => $idx )
			{
				$conf = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group='.$idx ) );
			
				$count = intval($conf['count']);
				
				$this->DB->update( 'core_sys_settings_titles', array( 'conf_title_count' => $count ), 'conf_title_id='.$idx );
			}
		}
		
		if( count($settingGroups) OR count($settings) )
		{
			$this->cache->rebuildCache( 'settings', 'global' );
		}

		//-----------------------------------------
		// (2) Languages
		//-----------------------------------------

		if( count($language) )
		{
			$langPacks	= array();
			
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$langPacks[] = $r['lang_id'];
			}

			foreach( $language as $langbit )
			{
				foreach( $langPacks as $lang_id )
				{
					$langbit['lang_id'] = $lang_id;
					
					// See if it exists
					$cnt = $this->DB->buildAndFetch( array( 'select' => 'word_id', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$lang_id} AND word_app='{$langbit['word_app']}' AND word_key='{$langbit['word_key']}' AND word_pack='{$langbit['word_pack']}'" ) );
					
					if( $cnt['word_id'] )
					{
						$this->DB->update( 'core_sys_lang_words', $langbit, 'word_id=' . $cnt['word_id'] );
						$languageUpdated++;
					}
					else
					{
						$this->DB->insert( 'core_sys_lang_words', $langbit );
						$languageInserted++;
						$word_id = $this->DB->getInsertId();
						$extra_data['language'][ $langbit['word_pack'] ][]	= $langbit['word_key'];
					}
				}
			}
			
			require_once( IPSLib::getAppDir('core') . '/modules_admin/languages/manage_languages.php' );
			$langLib = new admin_core_languages_manage_languages( $this->registry );
			$langLib->makeRegistryShortcuts( $this->registry );
			
			foreach( $langPacks as $langId )
			{
				$langLib->cacheToDisk( $langId );
			}
		}
		
		//-----------------------------------------
		// (3) Modules
		//-----------------------------------------
		
		if( count($modules) )
		{
			//-----------------------------------------
			// Get current modules
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'core_sys_module',
										'order'	=> 'sys_module_id' ) );
			
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$cur_modules[ $r['sys_module_application'] ][ $r['sys_module_key'] ] = $r['sys_module_id'];
			}
			
			foreach( $modules as $module )
			{
				//-----------------------------------------
				// Insert or update?
				//-----------------------------------------
			
				if ( $cur_modules[ $module['sys_module_application'] ][ $module['sys_module_key'] ] )
				{
					$this->DB->update( 'core_sys_module', $module, "sys_module_id=" . $cur_modules[ $module['sys_module_application'] ][ $module['sys_module_key'] ] );
					$modulesUpdated++;
				}
				else
				{
					$this->DB->insert( 'core_sys_module', $module );
					$modulesInserted++;
					$module_id = $this->DB->getInsertId();
					$extra_data['modules'][] = $module_id;
				}
			}
			
			require_once( IPSLib::getAppDir('core') . '/modules_admin/applications/applications.php' );
			$moduleLib = new admin_core_applications_applications( $this->registry );
			$moduleLib->makeRegistryShortcuts( $this->registry );
			$moduleLib->moduleRecache();
			$moduleLib->applicationsMenuDataRecache();
		}
		
		//-----------------------------------------
		// (4) Help Files
		//-----------------------------------------
		
		if( count($help) )
		{
			$keys		= array();
			
			$this->DB->build( array( 'select' => 'title', 'from' => 'faq' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$keys[] = $r['title'];
			}

			foreach( $help as $entry )
			{
				if( in_array( $entry['title'], $keys ) )
				{
					$this->DB->update( 'faq', $entry, "title='{$entry['title']}'" );
					$helpUpdated++;
				}
				else
				{	
					$this->DB->insert( 'faq', $entry );
					$helpInserted++;
					$help_id = $this->DB->getInsertId();
					$extra_data['help'][] = $help_id;
				}
			}
		}

		//-----------------------------------------
		// (6) Templates
		//-----------------------------------------
		
		if( count($templates) )
		{
			$bits		= array();
			
			$this->DB->build( array( 'select' => 'template_name,template_group', 'from' => 'skin_templates', 'where' => 'template_set_id=0' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$bits[ $r['template_group'] ][] = $r['template_name'];
			}

			foreach( $templates as $template )
			{
				if( is_array($bits[ $template['template_group'] ]) AND in_array( $template['template_name'], $bits[ $template['template_group'] ] ) )
				{
					$this->DB->update( 'skin_templates', $template, "template_group='{$template['template_group']}' AND template_name='{$template['template_name']}'" );
					$templatesUpdated++;
				}
				else
				{	
					$this->DB->insert( 'skin_templates', $template );
					$templatesInserted++;
					$template_id = $this->DB->getInsertId();
					$extra_data['templates'][ $template['template_group'] ][]	= $template['template_name'];
				}
			}
		}
		
		//-----------------------------------------
		// (7) Tasks
		//-----------------------------------------
		
		if( count($tasks) )
		{
			$keys		= array();
			
			$this->DB->build( array( 'select' => 'task_key', 'from' => 'task_manager' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$keys[] = $r['task_key'];
			}

			foreach( $tasks as $entry )
			{
				if( in_array( $entry['task_key'], $keys ) )
				{
					$this->DB->update( 'task_manager', $entry, "task_key='{$entry['task_key']}'" );
					$tasksUpdated++;
				}
				else
				{	
					$this->DB->insert( 'task_manager', $entry );
					$tasksInserted++;
					$task_id = $this->DB->getInsertId();
					$extra_data['tasks'][] = $task_id;
				}
			}
		}
		
		//-----------------------------------------
		// (8) Create new tables
		//-----------------------------------------
		
		if( count($database['create']) )
		{
			foreach($database['create'] as $create )
			{
				$query	= "CREATE TABLE {$create['name']} (
							{$create['fields']}
							)";
				
				if( $tabletype )
				{
					$query .= " TYPE=" . $create['tabletype'];
				}
				
				//-----------------------------------------
				// Fix prefix
				//-----------------------------------------
				
				$query = preg_replace( "#^CREATE TABLE(?:\s+?)?(\S+?)#s", "CREATE TABLE " . $this->settings['sql_tbl_prefix']."\\1", $query );
				
				$this->DB->return_die = true;
				$this->DB->query( $query );
				$this->DB->return_die = false;
				$createQueries++;
				
				$extra_data['database']['create'][] = $create;
			}
		}

		//-----------------------------------------
		// (9) Alter tables
		//-----------------------------------------
		
		if( count($database['alter']) )
		{
			foreach( $database['alter'] as $alter )
			{
				$this->DB->return_die = true;
				
				switch( $alter['altertype'] )
				{
					case 'remove':
						$this->DB->dropField( $alter['table'], $alter['field'] );
					break;
					
					case 'add':
						$this->DB->addField( $alter['table'], $alter['field'], $alter['fieldtype'], $alter['default'] );
					break;
					
					case 'change':
						$this->DB->changeField( $alter['table'], $alter['field'], $alter['newfield'], $alter['fieldtype'], $alter['default'] );
					break;
				}
				
				$this->DB->return_die = false;
				$alterQueries++;
				$extra_data['database']['alter'][] = $alter;
			}
		}
		
		//-----------------------------------------
		// (10) Run update queries
		//-----------------------------------------
		
		if( count($database['update']) )
		{
			foreach( $database['update'] as $update )
			{
				$this->DB->return_die = true;
				$this->DB->update( $update['table'], array( $update['field'] => $update['newvalue'] ), html_entity_decode( $update['where'], ENT_QUOTES ) );
				$this->DB->return_die = false;
				$updateQueries++;
				$extra_data['database']['update'][] = $update;
			}
		}
		
		//-----------------------------------------
		// (11) Run insert queries
		//-----------------------------------------
		
		if( !$this->hooks[ $config['hook_key'] ]['hook_id'] )
		{
			if( count($database['insert']) )
			{
				foreach( $database['insert'] as $insert )
				{
					$fields		= array();
					$content	= explode( ',', $insert['updates'] );
					
					foreach( $content as $value )
					{
						list( $field, $toInsert ) = explode( '=', $value );
						
						$fields[ $field ] = $toInsert;
					}
					
					$this->DB->return_die = true;
					$this->DB->insert( $insert['table'], $fields );
					$this->DB->return_die = false;
					$insertQueries++;
					$extra_data['database']['insert'][] = $insert;
				}
			}
		}
	
		if( $custom['filename'] AND file_exists( IPS_HOOKS_PATH . 'install_' . $custom['filename'] ) )
		{
			require_once( IPS_HOOKS_PATH . 'install_' . $custom['filename'] );
			
			$classname = str_replace( '.php', '', $custom['filename'] );
			
			if( class_exists( $classname ) )
			{
				$install = new $classname( $this->registry );
				
				if( method_exists( $install, 'install' ) )
				{
					$install->install();
				}
			}
		}
		
		if( count($extra_data) )
		{
			$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize( $extra_data ) ), 'hook_id=' . $hook_id );
		}
					
		//print_r($config);
		//print_r($files);
		//print_r($custom);
		//print_r($settingGroups);
		//print_r($settings);
		//print_r($language);
		//print_r($modules);
		//print_r($templates);
		//print_r($tasks);
		//print_r($help);
		//print_r($database);

		if ( $addMessage )
		{
			$this->registry->output->global_message = <<<EOF
		{$this->lang->words['h_followacts']}
		<ul>
			<li>{$this->lang->words['h_newhookin']}</li>
			<li>{$filesInserted} {$this->lang->words['h_filesin']}</li>
			<li>{$settingGroupsInserted} {$this->lang->words['h_settinggin']}</li>
			<li>{$settingsInserted} {$this->lang->words['h_settingin']}</li>
			<li>{$settingsUpdated} {$this->lang->words['h_settingup']}</li>
			<li>{$languageInserted} {$this->lang->words['h_langbitin']}</li>
			<li>{$languageUpdated} {$this->lang->words['h_langbitup']}</li>
			<li>{$modulesInserted} {$this->lang->words['h_modin']}</li>
			<li>{$modulesUpdated} {$this->lang->words['h_modup']}</li>
			<li>{$helpInserted} {$this->lang->words['h_helpin']}</li>
			<li>{$helpUpdated} {$this->lang->words['h_helpup']}</li>
			<li>{$templatesInserted} {$this->lang->words['h_tempin']}</li>
			<li>{$templatesUpdated} {$this->lang->words['h_tempup']}</li>
			<li>{$tasksInserted} {$this->lang->words['h_taskin']}</li>
			<li>{$tasksUpdated} {$this->lang->words['h_taskup']}</li>
			<li>{$createQueries} {$this->lang->words['h_dbcreated']}</li>
			<li>{$alterQueries} {$this->lang->words['h_dbaltered']}</li>
			<li>{$updateQueries} {$this->lang->words['h_updateran']}</li>
			<li>{$insertQueries} {$this->lang->words['h_insertran']}</li>
		</ul>
EOF;

			//-----------------------------------------
			// Got some skin recaching to do...
			//-----------------------------------------
			
			if( $allowSkinRecache === TRUE AND ( $templatesInserted OR $templatesUpdated OR $templateHooks ) )
			{
				//-----------------------------------------
				// Recache hooks so templates know that there
				// are comments to preserve
				//-----------------------------------------
				
				$this->rebuildHooksCache();
				
				//-----------------------------------------
				// Get the libs
				//-----------------------------------------
				
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
				$skinCaching	= new skinCaching( $this->registry );
				
				//-----------------------------------------
				// Find first skin id
				//-----------------------------------------
				
				ksort( $this->registry->output->allSkins );
				$_skins = $this->registry->output->allSkins;
				$_set   = array_shift( $_skins );
				$setID  = $_set['set_id'];
				
				$skinCaching->rebuildPHPTemplates( $setID );
				
				/* Fetch next id */
				$nextID = $setID;
				
				ksort( $this->registry->output->allSkins );
			
				foreach( $this->registry->output->allSkins as $id => $data )
				{
					if ( $id > $nextID )
					{
						$nextID = $id;
						break;
					}
				}
				
				//-----------------------------------------
				// Have more than one skin
				//-----------------------------------------
				
				if ( $nextID != $setID )
				{
					//-----------------------------------------
					// Save hook import messages
					//-----------------------------------------
					
					ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'hookResult', $this->registry->output->global_message );
					
					//-----------------------------------------
					// Wipe out the messages
					//-----------------------------------------
					
					$this->registry->output->global_message	= '';
					
					//-----------------------------------------
					// Redirect to rebuild skins
					//-----------------------------------------
					
					$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
					
					$this->registry->output->redirect( $this->settings['base_url'] . 'app=core&module=applications&section=hooks&do=skinsRebuild&setID=' . $nextID, $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'] );
				}
			}
		}
	}

	/**
	 * Show the form to export a hook
	 *
	 * @access	private
	 * @return   void
	 */
	private function _exportHook()
	{
		/* Get the hook */
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1112 );
		}
		
		$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1113 );
		}
		
		/* Set defaults */
		$hookData['hook_extra_data']	= unserialize( $hookData['hook_extra_data'] );

		/* Output */
		$this->registry->output->html .= $this->html->hooksExport( $hookData );
	}
	
	/**
	 * Actually export the damn hook already
	 * Sorry, it has been a long day...
	 *
	 * @access	private
	 * @return	void
	 */
	private function _doExportHook()
	{
		//-----------------------------------------
		// Get hook
		//-----------------------------------------
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1114 );
		}
		
		$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1115 );
		}
		
		$extra_data	= unserialize( $hookData['hook_extra_data'] );
		
		//-----------------------------------------
		// Get hook files
		//-----------------------------------------
		
		$files		= array();
		$index		= 1;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$files[ $index ]	= $r;
			$index++;
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'hookexport' );

		//-----------------------------------------
		// Put hook data in export
		//-----------------------------------------
		
		$xml->addElement( 'hookdata', 'hookexport' );
		$content	= array();
		
		foreach( $hookData as $k => $v )
		{
			if( in_array( $k, array( 'hook_id', 'hook_enabled', 'hook_installed', 'hook_updated', 'hook_position' ) ) )
			{
				continue;
			}
			
			$content[ $k ] = $v;
		}
		
		$xml->addElementAsRecord( 'hookdata', 'config', $content );
		
		//-----------------------------------------
		// Put hook files in export
		//-----------------------------------------
		
		$xml->addElement( 'hookfiles', 'hookexport' );

		foreach( $files as $index => $r )
		{
			$content	= array();
			
			foreach( $r as $k => $v )
			{
				if( in_array( $k, array( 'hook_file_id', 'hook_hook_id', 'hook_file_stored', 'hooks_source' ) ) )
				{
					continue;
				}
				
				$content[ $k ] = $v;
			}
			
			$source	= file_exists( IPS_HOOKS_PATH . $r['hook_file_stored'] ) ? file_get_contents( IPS_HOOKS_PATH . $r['hook_file_stored'] ) : '';
			
			if( $r['hook_type'] == 'commandHooks' )
			{
				$source	= $this->_cleanSource( $source );
			}

			$content['hooks_source'] = $source;

			$xml->addElementAsRecord( 'hookfiles', 'file', $content );
		}

		//-----------------------------------------
		// Custom install/uninstall script?
		//-----------------------------------------
		
		if( $extra_data['custom'] )
		{
			$content	= array();
			$xml->addElement( 'hookextras_custom', 'hookexport' );
		
			$content['filename']	= $extra_data['custom'];
			$content['source']		= file_exists( IPS_HOOKS_PATH . 'install_' . $extra_data['custom'] ) ? file_get_contents( IPS_HOOKS_PATH . 'install_' . $extra_data['custom'] ) : '';
			
			$xml->addElementAsRecord( 'hookextras_custom', 'file', $content );
		}
		
		//-----------------------------------------
		// Settings or setting groups?
		//-----------------------------------------
		
		$entry		= array();
		$_groups	= array();
		$_settings	= array();
		$titles		= array();
		$content	= array();
		
		$xml->addElement( 'hookextras_settings', 'hookexport' );
		
		# Store group ids and setting ids for entire setting groups
		if( is_array($extra_data['settingGroups']) AND count($extra_data['settingGroups']) )
		{
			$_groups	= $extra_data['settingGroups'];
			
			$this->DB->build( array( 'select' => 'conf_id', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group IN(' . implode( ',', $_groups ) . ')' ) );
			$this->DB->execute();
			
			while( $setting = $this->DB->fetch() )
			{
				$_settings[] = $setting['conf_id'];
			}
		}
		
		# Store group ids and setting ids for indvidual settings
		if( is_array($extra_data['settings']) AND count($extra_data['settings']) )
		{
			foreach( $extra_data['settings'] as $settingId )
			{
				$_settings[] = $settingId;
			}
			
			$this->DB->build( array( 'select' => 'conf_group', 'from' => 'core_sys_conf_settings', 'where' => 'conf_id IN(' . implode( ',', $extra_data['settings'] ) . ')' ) );
			$this->DB->execute();
			
			while( $group = $this->DB->fetch() )
			{
				$_groups[] = $group['conf_group'];
			}
		}
		
		if( count($_groups) )
		{
			# Now get the group data for the XML file
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'where' => 'conf_title_id IN(' . implode( ',', $_groups ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$content	= array();
				
				$titles[ $r['conf_title_id'] ] = $r['conf_title_keyword'];
				
				$content['conf_is_title']	= 1;
				
				foreach( $r as $k => $v )
				{
					if( in_array( $k, array( 'conf_title_tab', 'conf_title_keyword', 'conf_title_title', 'conf_title_desc', 'conf_title_app', 'conf_title_noshow' ) ) )
					{
						$content[ $k ] = $v;
					}
				}

				$xml->addElementAsRecord( 'hookextras_settings', 'setting', $content );
			}
		}
		
		if( count($_settings) )
		{
			# Now get the group data for the XML file
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_id IN(' . implode( ',', $_settings ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$r['conf_value']			= '';
				$r['conf_title_keyword']	= $titles[ $r['conf_group'] ];
				$r['conf_is_title']			= 0;

				$xml->addElementAsRecord( 'hookextras_settings', 'setting', $r );
			}
		}

		//-----------------------------------------
		// Language strings/files
		//-----------------------------------------
		
		$entry		= array();
		
		$xml->addElement( 'hookextras_language', 'hookexport' );
		
		if( is_array($extra_data['language']) AND count($extra_data['language']) )
		{
			foreach( $extra_data['language'] as $file => $strings )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "word_pack='{$file}' AND word_key IN('" . implode( "','", $strings ) . "')" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$content	= array();
					
					foreach( $r as $k => $v )
					{
						if( in_array( $k, array( 'word_id', 'lang_id', 'word_default_version', 'word_custom_version' ) ) )
						{
							continue;
						}
						
						$content[ $k ] = $v;
					}
	
					$xml->addElementAsRecord( 'hookextras_language', 'language', $content );
				}
			}
		}

		//-----------------------------------------
		// Modules
		//-----------------------------------------

		$xml->addElement( 'hookextras_modules', 'hookexport' );
		
		if( is_array($extra_data['modules']) AND count($extra_data['modules']) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => "sys_module_key IN('" . implode( "','", $extra_data['modules'] ) . "')" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['sys_module_id']);

				$xml->addElementAsRecord( 'hookextras_modules', 'module', $r );
			}
		}
		
		//-----------------------------------------
		// Help files
		//-----------------------------------------

		$xml->addElement( 'hookextras_help', 'hookexport' );
		
		if( is_array($extra_data['help']) AND count($extra_data['help']) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'faq', 'where' => "id IN(" . implode( ",", $extra_data['help'] ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['id']);

				$xml->addElementAsRecord( 'hookextras_help', 'help', $r );
			}
		}
		
		//-----------------------------------------
		// Skin templates
		//-----------------------------------------

		$xml->addElement( 'hookextras_templates', 'hookexport' );
				
		if( is_array($extra_data['templates']) AND count($extra_data['templates']) )
		{
			foreach( $extra_data['templates'] as $file => $templates )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "template_set_id=0 AND template_group='{$file}' AND template_name IN('" . implode( "','", $templates ) . "')" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					unset($r['template_id']);
	
					$xml->addElementAsRecord( 'hookextras_templates', 'templates', $r );
				}
			}
		}
		
		//-----------------------------------------
		// Tasks
		//-----------------------------------------

		$xml->addElement( 'hookextras_tasks', 'hookexport' );
				
		if( is_array($extra_data['tasks']) AND count($extra_data['tasks']) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id IN(" . implode( ",", $extra_data['tasks'] ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['task_id']);
				unset($r['task_next_run']);

				$xml->addElementAsRecord( 'hookextras_tasks', 'tasks', $r );
			}
		}

		//-----------------------------------------
		// Database changes
		//-----------------------------------------

		$xml->addElement( 'hookextras_database_create', 'hookexport' );
		
		if( is_array($extra_data['database']['create']) AND count($extra_data['database']['create']) )
		{
			foreach( $extra_data['database']['create'] as $create_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_create', 'create', $create_query );
			}
		}

		$xml->addElement( 'hookextras_database_alter', 'hookexport' );
		
		if( is_array($extra_data['database']['alter']) AND count($extra_data['database']['alter']) )
		{
			foreach( $extra_data['database']['alter'] as $alter_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_alter', 'alter', $alter_query );
			}
		}

		$xml->addElement( 'hookextras_database_update', 'hookexport' );
		
		if( is_array($extra_data['database']['update']) AND count($extra_data['database']['update']) )
		{
			foreach( $extra_data['database']['update'] as $update_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_update', 'update', $update_query );
			}
		}

		$xml->addElement( 'hookextras_database_insert', 'hookexport' );
		
		if( is_array($extra_data['database']['insert']) AND count($extra_data['database']['insert']) )
		{
			foreach( $extra_data['database']['insert'] as $insert_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_update', 'insert', $insert_query );
			}
		}

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------

		$this->registry->output->showDownload( $xml->fetchDocument(), 'hook.xml', '', 0 );
	}
	
	/**
	 * Clean source code for export..
	 *
	 * @access	private
	 * @param	string			Hook source code
	 * @return	string			"Cleaned" source code
	 */
	private function _cleanSource( $source )
	{
		$source = preg_replace( "/class\s+(\S+)\s+extends\s+(\S+)/i", "class \\1 extends (~extends~)", $source );
		
		return $source;
	}
	
	/**
	 * Fix hook positions
	 *
	 * @access	private
	 * @return   void
	 */
	private function _fixPositions()
	{
		$new_order		= 0;
		$usedActions	= array();
		
		$this->DB->build( array( 'select' => 'hook_id, hook_position', 'from' => 'core_hooks', 'where' => 'hook_enabled=1', 'order' => 'hook_position ASC' ) );
		$qid = $this->DB->execute();

		while ( $hook = $this->DB->fetch( $qid ) )
		{
			$new_order++;
			$this->DB->update( 'core_hooks', array ( 'hook_position' => $new_order ), "hook_id={$hook['hook_id']}" );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => "(hook_type='commandHooks' OR hook_type='skinHooks') AND hook_hook_id=" . $hook['hook_id'] ) );
			$this->DB->execute();
			
			while( $file = $this->DB->fetch() )
			{
				if( $file['hooks_source'] )
				{
					$source = $file['hooks_source'];
				}
				else
				{
					$source = file_exists( IPS_HOOKS_PATH . $file['hook_file_stored'] ) ? file_get_contents( IPS_HOOKS_PATH . $file['hook_file_stored'] ) : '';
					
					if( $source )
					{
						 $source = $this->_cleanSource( $source );
					}
				}

				if( $source )
				{
					$hook_data	= unserialize( $file['hook_data'] );
					$overload	= $hook_data['classToOverload'];
					$newClass	= $overload;
					
					if( isset( $usedActions[ $overload ] ) )
					{
						$newClass = $usedActions[ $overload ];
					}
					else if( $file['hook_type'] == 'skinHooks' )
					{
						$newClass .= "(~id~)";
					}
					
					$source = str_replace( "(~extends~)", $newClass, $source );
			
					file_put_contents( IPS_HOOKS_PATH . $file['hook_file_stored'], $source );
					
					$usedActions[ $hook_data['classToOverload'] ] = $file['hook_classname'];
				}
			}
		}
	}
	
	/**
	 * Check if there is an update for a hook
	 *
	 * @access	private
	 * @param	string 		URL to check
	 * @param	string		Long version number
	 * @return	bool
	 */
	private function _updateAvailable( $url, $version )
	{
		if( !$version )
		{
			return false;
		}
		
		require_once( IPS_KERNEL_PATH . '/classFileManagement.php' );
		$checker				= new classFileManagement();
		$checker->use_sockets	= $this->settings['enable_sockets'];
		
		//-----------------------------------------
		// This is a low timeout to prevent page from taking too long
		//-----------------------------------------
		
		$checker->timeout		= 5;
		
		$return	= $checker->getFileContents( str_replace( 'php&', 'php?', $url . '&version=' . $version ) );

		return $return == 1 ? true : false;
	}
	
	/**
	 * Hooks overview
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 * @todo 	[Future] Explore showing details/version requirements in a DHTML popup rather than a new page
	 */
	private function _hooksOverview()
	{
		/* INI */
		$installedHooks		= array();
		$uninstalledHooks	= array();
		$min_order			= 999999;
		$max_order			= 0;
		
		/* Get current hooks */
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks', 'order' => 'hook_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_updated']		= $this->registry->getClass('class_localization')->formatTime( $r['hook_updated'] );
			$r['_installed']	= $this->registry->getClass('class_localization')->formatTime( $r['hook_installed'] );
			
			$r['hook_update_available']	= '';
			
			if( $r['hook_update_check'] )
			{
				if( $this->_updateAvailable( $r['hook_update_check'], $r['hook_version_long'] ) )
				{
					$r['hook_update_available']	= "<span class='hookupdate'>Update Available</span>";
					
					if( $r['hook_website'] )
					{
						$r['hook_update_available']	= "<a href='{$r['hook_website']}' target='_blank'>" . $r['hook_update_available'] . '</a>';
					}
				}
			}
			
			$min_order			= $r['hook_position'] < $min_order ? $r['hook_position'] : $min_order;
			$max_order			= $r['hook_position'] > $max_order ? $r['hook_position'] : $max_order;

			if( $r['hook_enabled'] )
			{
				$installedHooks[ $r['hook_id'] ]	= $r;
			}
			else
			{
				$uninstalledHooks[ $r['hook_id'] ]	= $r;
			}
		}

		if( count($installedHooks) )
		{
			foreach( $installedHooks as $id => $r )
			{
				$upButton	= $r['hook_position'] > $min_order ? 1 : 0;
				$downButton	= $r['hook_position'] < $max_order ? 1 : 0;
				
				$installedHooks[ $id ]['upButton']		= $upButton;
				$installedHooks[ $id ]['downButton']	= $downButton;
			}
		}

		/* Output */
		$this->registry->output->html .= $this->html->hooksOverview( $installedHooks, $uninstalledHooks );
	}
	
	/**
	 * Disables a hook
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _disableHook()
	{
		/* INI */
		$hook_id = intval( $this->request['id'] );

		/* Do update */
		$this->DB->update( 'core_hooks', array( 'hook_enabled' => 0 ), "hook_id={$hook_id}" );
		
		$this->rebuildHooksCache();
		
		/* Done */
		$this->registry->output->global_message = "The hook has been disabled";
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}	
	
	/**
	 * Enables a hook
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _enableHook()
	{
		/* INI */
		$hook_id = intval( $this->request['id'] );

		/* Do update */
		$this->DB->update( 'core_hooks', array( 'hook_enabled' => 1 ), "hook_id={$hook_id}" );
		
		$this->rebuildHooksCache();
		
		/* Done */
		$this->registry->output->global_message = $this->lang->words['h_hasbeenenabled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * Uninstall a hook
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _uninstallHook()
	{
		/* INI */
		$hook_id	= intval( $this->request['id'] );

		$hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $hook_id ) );
		$extra_data	= unserialize( $hook['hook_extra_data'] );

		/* Delete main hook entry */
		$this->DB->delete( 'core_hooks', "hook_id={$hook_id}" );
		
		/* Get associated files */
		$this->DB->build( array( 'select' => 'hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $hook_id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
		}
		
		/* Delete hook file entries */
		$this->DB->delete( 'core_hooks_files', "hook_hook_id={$hook_id}" );
		
		//-----------------------------------------
		// Settings or setting groups?
		//-----------------------------------------
		
		if( count($extra_data['settingGroups']) )
		{
			$this->DB->delete( 'core_sys_settings_titles', 'conf_title_id IN(' . implode( ',', $extra_data['settingGroups'] ) . ')' );
		}
		
		if( count($extra_data['settings']) )
		{
			$this->DB->delete( 'core_sys_conf_settings', 'conf_id IN(' . implode( ',', $extra_data['settings'] ) . ')' );
		}
		
		$this->cache->rebuildCache( 'settings', 'global' );

		//-----------------------------------------
		// Language strings/files
		//-----------------------------------------
		
		if( count($extra_data['language']) )
		{
			foreach( $extra_data['language'] as $file => $bit )
			{
				$this->DB->delete( 'core_sys_lang_words', "word_pack='{$file}' AND word_key IN('" . implode( "','", $bit ) . "')" );
			}
			
			$langPacks	= array();
			
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$langPacks[] = $r['lang_id'];
			}

			require_once( IPSLib::getAppDir('core') . '/modules_admin/languages/manage_languages.php' );
			$langLib = new admin_core_languages_manage_languages( $this->registry );
			$langLib->makeRegistryShortcuts( $this->registry );
			
			foreach( $langPacks as $langId )
			{
				$langLib->cacheToDisk( $langId );
			}
		}
		
		//-----------------------------------------
		// Modules
		//-----------------------------------------
		
		if( count($extra_data['modules']) )
		{
			$this->DB->delete( 'core_sys_module', 'sys_module_id IN(' . implode( ',', $extra_data['modules'] ) . ')' );
			
			$this->cache->rebuildCache( 'module_cache', 'global' );
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}
		
		//-----------------------------------------
		// Help files
		//-----------------------------------------
		
		if( count($extra_data['help']) )
		{
			$this->DB->delete( 'faq', 'id IN(' . implode( ',', $extra_data['help'] ) . ')' );
		}

		//-----------------------------------------
		// Skin templates
		//-----------------------------------------
		
		if( count($extra_data['templates']) )
		{
			foreach( $extra_data['templates'] as $file => $bit )
			{
				$this->DB->delete( 'skin_templates', "template_group='{$file}' AND template_name IN('" . implode( "','", $bit ) . "')" );
			}
			
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
			$skinCaching	= new skinCaching( $this->registry );
			$skinCaching->rebuildPHPTemplates( 0 );
		}
		
		//-----------------------------------------
		// Tasks
		//-----------------------------------------
		
		if( count($extra_data['tasks']) )
		{
			$this->DB->delete( 'task_manager', 'task_id IN(' . implode( ',', $extra_data['tasks'] ) . ')' );
		}
		
		//-----------------------------------------
		// Database changes
		//-----------------------------------------
		
		if( is_array($extra_data['database']['create']) AND count($extra_data['database']['create']) )
		{
			foreach( $extra_data['database']['create'] as $create_query )
			{
				$this->DB->return_die	= true;
				$this->DB->dropTable( $create_query['name'] );
				$this->DB->return_die	= false;
			}
		}

		if( is_array($extra_data['database']['alter']) AND count($extra_data['database']['alter']) )
		{
			foreach( $extra_data['database']['alter'] as $alter_query )
			{
				$this->DB->return_die	= true;
				
				if( $alter_query['altertype'] == 'add' )
				{
					if( $this->DB->checkForField( $alter_query['field'], $alter_query['table'] ) )
					{
						$this->DB->dropField( $alter_query['table'], $alter_query['field'] );
					}
				}
				else if( $alter_query['altertype'] == 'change' )
				{
					if( $this->DB->checkForField( $alter_query['newfield'], $alter_query['table'] ) )
					{
						$this->DB->changeField( $alter_query['table'] , $alter_query['newfield'], $alter_query['field'], $alter_query['fieldtype'], $alter_query['default'] );
					}
				}
				
				$this->DB->return_die	= false;
			}
		}

		if( is_array($extra_data['database']['update']) AND count($extra_data['database']['update']) )
		{
			foreach( $extra_data['database']['update'] as $update_query )
			{
				$this->DB->return_die	= true;
				$this->DB->update( $update_query['table'], array( $update_query['field'] => $update_query['oldvalue'] ), $update_query['where'] );
				$this->DB->return_die	= false;
			}
		}

		if( is_array($extra_data['database']['insert']) AND count($extra_data['database']['insert']) )
		{
			foreach( $extra_data['database']['insert'] as $insert_query )
			{
				if( $insert_query['fordelete'] )
				{
					$this->DB->return_die	= true;
					$this->DB->delete( $insert_query['table'], $insert_query['fordelete'] );
					$this->DB->return_die	= false;
				}
			}
		}

		//-----------------------------------------
		// Custom install/uninstall script?
		//-----------------------------------------

		if( $extra_data['custom'] )
		{
			if( file_exists( IPS_HOOKS_PATH . $extra_data['custom'] ) )
			{
				require_once( IPS_HOOKS_PATH . $extra_data['custom'] );
				
				$classname = str_replace( '.php', '', $extra_data['custom'] );
				
				if( class_exists( $classname ) )
				{
					$uninstall = new $classname( $this->registry );
					
					if( method_exists( $uninstall, 'uninstall' ) )
					{
						$uninstall->uninstall();
					}
				}
				
				@unlink( IPS_HOOKS_PATH . $extra_data['custom'] );
			}
		}

		$this->rebuildHooksCache();
		
		/* Done */
		$this->registry->output->global_message = $this->lang->words['h_hasbeenun'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}	
	
	/**
	 * Hook add/edit form
	 * This dynamic form allows users to associate multiple files with a single hook.
	 *
	 * @access	private
	 * @param	string		[add|edit]
	 * @return	void		[Outputs to screen]
	 */
	private function _hookForm( $type='add' )
	{
		if( $type == 'edit' )
		{
			$id	= intval($this->request['id']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1116 );
			}
			
			$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
			
			if( !$hookData['hook_id'] )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1117 );
			}
			
			$hookData['hook_extra_data']	= unserialize( $hookData['hook_extra_data'] );
			$hookData['hook_requirements']	= unserialize( $hookData['hook_requirements'] );
			
			$files		= array();
			$index		= 1;
			$skinGroups = $this->hooksFunctions->getSkinGroups();
			$entryPoint	= array();
			
			$entryPoint['foreach']	= array(
											array( 'outer.pre', $this->lang->words['h_outerpre'] ),
											array( 'inner.pre', $this->lang->words['h_innerpre'] ),
											array( 'inner.post', $this->lang->words['h_innerpost'] ),
											array( 'outer.post', $this->lang->words['h_outerpost'] ),
											);

			$entryPoint['if']		= array(
											array( 'pre.startif', $this->lang->words['h_prestartif'] ),
											array( 'post.startif', $this->lang->words['h_poststartif'] ),
											array( 'pre.else', $this->lang->words['h_preelse']),
											array( 'post.else', $this->lang->words['h_postelse'] ),
											array( 'pre.endif', $this->lang->words['h_preendif'] ),
											array( 'post.endif', $this->lang->words['h_postendif'] ),
											);
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id ) );
			$outer = $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$r['hook_data']		= unserialize( $r['hook_data'] );

				if( $r['hook_type'] == 'templateHooks' )
				{
					$templates	= $this->hooksFunctions->getSkinMethods( $r['hook_data']['skinGroup'] );
					$hookIds	= $this->hooksFunctions->getHookIds( $r['hook_data']['skinFunction'], $r['hook_data']['type'] );
					
					$r['_skinDropdown']		= $this->registry->output->formDropdown( "skinGroup[{$index}]", $skinGroups, $r['hook_data']['skinGroup'], "skinGroup[{$index}]", "onchange='getTemplatesForAdd({$index});'" );
					$r['_templateDropdown']	= $this->registry->output->formDropdown( "skinFunction[{$index}]", $templates, $r['hook_data']['skinFunction'], "skinFunction[{$index}]", "onchange='getTypeOfHook({$index});'" );
					$r['_hookTypeDropdown']	= $this->registry->output->formDropdown( "type[{$index}]", array( array( 'foreach', 'foreach loop' ), array( 'if', 'if statement' ) ), $r['hook_data']['type'], "type[{$index}]", "onchange='getHookIds({$index});'" );
					$r['_hookIdsDropdown']	= $this->registry->output->formDropdown( "id[{$index}]", $hookIds, $r['hook_data']['id'], "id[{$index}]", "onchange='getHookEntryPoints({$index});'" );
					$r['_hookEPDropdown']	= $this->registry->output->formDropdown( "position[{$index}]", $r['hook_data']['type'] == 'foreach' ? $entryPoint['foreach'] : $entryPoint['if'], $r['hook_data']['position'] );
				}
				
				$files[ $index ]	= $r;
				$index++;
			}
			
			$action		= 'do_edit_hook';
		}
		else
		{
			$hookData	= array();
			
			foreach( array( 'hook_enabled', 'hook_name', 'hook_desc', 'hook_author', 'hook_email', 'hook_website', 'hook_update_check', 'hook_requirements', 'hook_version_human', 'hook_version_long', 'hook_installed', 	'hook_updated', 'hook_position', 'hook_extra_data' ) as $_hook )
			{
				$hookData[ $_hook ] = $this->request[ $_hook ];
			}
			
			foreach( array( 'hook_ipb_version_max', 'hook_ipb_version_min', 'hook_php_version_min', 'hook_php_version_max' ) as $_version )
			{
				$hookData['hook_requirements'][ $_version ] = $this->request[ $_version ];
			}
			
			$files		= array();
			$action		= 'do_create_hook';
		}

		/* Output */
		$this->registry->output->html .= $this->html->hookForm( $action, $hookData, $files );
	}

	/**
	 * Hook add/edit save
	 * Save the new (or updated) hook record
	 *
	 * @access	private
	 * @param	string		[add|edit]
	 * @return	void		[Outputs to screen]
	 */
	private function _hookSave( $type='add' )
	{
		/* Error Checking */
		if( ! $this->request['hook_name'] )
		{
			$errors[] = $this->lang->words['hook_form_no_title'];
		}
		
		if( ! $this->request['hook_key'] )
		{
			$errors[] = $this->lang->words['hook_form_no_key'];
		}

		$newFiles	= array();
		
		if( !is_array( $this->request['file'] ) OR !count( $this->request['file'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['h_onefile'];
			$this->_hookForm();
			return;
		}
		
		foreach( $this->request['file'] as $index => $file )
		{
			if( $file )
			{
				$newFiles[ $index ]	= array(
											'hook_file_real'		=> $file,
											'hook_file_stored'		=> $file,	// During import this is a random name, but for devs it's actual file
											'hook_type'				=> $this->request['hook_type'][ $index ],
											'hook_classname'		=> $this->request['hook_classname'][ $index ],
											'hook_data'				=> serialize(
																				array(
																					'classToOverload'	=> trim($this->request['classToOverload'][ $index ]),
																					'skinGroup'			=> $this->request['skinGroup'][ $index ],
																					'skinFunction'		=> $this->request['skinFunction'][ $index ],
																					'type'				=> $this->request['type'][ $index ],
																					'id'				=> $this->request['id'][ $index ],
																					'position'			=> $this->request['position'][ $index ],
																					)
																				)
											);
			}
		}

		if( !is_array( $newFiles ) OR !count( $newFiles ) )
		{
			$errors[] = $this->lang->words['h_onefile'];
		}
		
		if( is_array( $errors ) )
		{
			$this->registry->output->global_message = implode( '<br>', $errors );
			$this->_hookForm();
			return;
		}

		/* Get data if we are editing */
		if( $type == 'edit' )
		{
			$id	= intval($this->request['hook_id']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1118 );
			}
			
			$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
			
			if( !$hookData['hook_id'] )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1119 );
			}
		}
		
		/* Get new position */
		$position	= $this->DB->buildAndFetch( array( 'select' => 'MAX(hook_position) as newPos', 'from' => 'core_hooks' ) );

		$mainHookRecord		= array(
									'hook_name'				=> trim($this->request['hook_name']),
									'hook_key'				=> substr( trim($this->request['hook_key']), 0, 32 ),
									'hook_desc'				=> trim($this->request['hook_desc']),
									'hook_version_human'	=> trim($this->request['hook_version_human']),
									'hook_version_long'		=> trim($this->request['hook_version_long']),
									'hook_author'			=> trim($this->request['hook_author']),
									'hook_email'			=> trim($this->request['hook_email']),
									'hook_website'			=> trim($this->request['hook_website']),
									'hook_update_check'		=> trim($this->request['hook_update_check']),
									'hook_enabled'			=> $type == 'add' ? 1 : $hookData['hook_enabled'],
									'hook_installed'		=> $type == 'add' ? time() : $hookData['hook_installed'],
									'hook_updated'			=> $type == 'add' ? 0 : time(),
									'hook_position'			=> $type == 'add' ? $position['newPos'] + 1 : $hookData['hook_position'],
									'hook_requirements'		=> serialize(
																		array(
																			'hook_ipb_version_min'	=> intval($this->request['hook_ipb_version_min']),
																			'hook_ipb_version_max'	=> intval($this->request['hook_ipb_version_max']),
																			'hook_php_version_min'	=> $this->request['hook_php_version_min'],
																			'hook_php_version_max'	=> $this->request['hook_php_version_max'],
																			)
																		)
									);

		if( $type == 'edit' )
		{
			$this->DB->update( 'core_hooks', $mainHookRecord, 'hook_id=' . $hookData['hook_id'] );
			
			$this->DB->delete( 'core_hooks_files', 'hook_hook_id=' . $hookData['hook_id'] );
		}
		else
		{
			$this->DB->insert( 'core_hooks', $mainHookRecord );
			
			$hookData['hook_id']	= $this->DB->getInsertId();
		}
		
		foreach( $newFiles as $index => $toInsert )
		{
			$toInsert['hook_hook_id']	= $hookData['hook_id'];
			
			$this->DB->insert( 'core_hooks_files', $toInsert );
		}
		
		$this->rebuildHooksCache();
		
		$this->registry->output->global_message = $this->lang->words['h_saved'];
		$this->_hooksOverview();
	}
	
	/**
	 * View details about a hook
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _viewDetails()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_nodetails'], 11110 );
		}
		
		$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_nodetails'], 11111 );
		}
		
		$hookData['hook_extra_data']	= unserialize( $hookData['hook_extra_data'] );
		$hookData['hook_requirements']	= unserialize( $hookData['hook_requirements'] );
		
		if( $hookData['hook_requirements']['hook_ipb_version_min'] OR $hookData['hook_requirements']['hook_ipb_version_max'] )
		{
			/* Get the setup class */
			require IPS_ROOT_PATH . "setup/sources/base/setup.php";
			
			/* Fetch numbers */
			$versions  = IPSSetUp::fetchXmlAppVersions( 'core' );
			
			/* Ensure we match at least 3.0.0 */
			$hookData['hook_requirements']['hook_ipb_version_min'] = ( $hookData['hook_requirements']['hook_ipb_version_min'] < 30000 ) ? 30000 : $hookData['hook_requirements']['hook_ipb_version_min'];
			
			foreach( $versions as $long => $human )
			{
				if( $hookData['hook_requirements']['hook_ipb_version_min'] <= $long )
				{
					$hookData['hook_requirements']['hook_ipb_version_min'] = $human;
					break;
				}
			}
			
			krsort( $versions );
			
			foreach( $versions as $long => $human )
			{
				if( $hookData['hook_requirements']['hook_ipb_version_max'] >= $long )
				{
					$hookData['hook_requirements']['hook_ipb_version_max'] = $human;
					break;
				}
			}
		}
		
		$files		= array();
		$index		= 1;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['hook_data']		= unserialize( $r['hook_data'] );
			$files[ $index ]	= $r;
			$index++;
		}

		/* Output */
		$this->registry->output->html .= $this->html->hookDetails( $hookData, $files );
	}
	
	/**
	 * Check if you meet hook requirements
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _checkRequirements()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_norequirements'], 11112 );
		}
		
		$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_norequirements'], 11113 );
		}

		$hookData['hook_requirements']	= unserialize( $hookData['hook_requirements'] );
		$hookIpbBad						= false;
		$hookPhpBad						= false;
		
		/* Ensure we match at least 3.0.0 */
		$hookData['hook_requirements']['hook_ipb_version_min'] = ( $hookData['hook_requirements']['hook_ipb_version_min'] < 30000 ) ? 30000 : $hookData['hook_requirements']['hook_ipb_version_min'];
		
		if( $hookData['hook_requirements']['hook_ipb_version_min'] OR $hookData['hook_requirements']['hook_ipb_version_max'] )
		{
			/* Get the setup class */
			require IPS_ROOT_PATH . "setup/sources/base/setup.php";
			
			/* Fetch numbers */
			$versions  = IPSSetUp::fetchXmlAppVersions( 'core' );
			
			$currentIPB		= (int) IPB_LONG_VERSION;

			if( $hookData['hook_requirements']['hook_ipb_version_min'] AND $currentIPB < $hookData['hook_requirements']['hook_ipb_version_min'] )
			{
				$hookIpbBad		= $this->lang->words['h_tooold'];
			}

			if( $hookData['hook_requirements']['hook_ipb_version_max'] AND $currentIPB > $hookData['hook_requirements']['hook_ipb_version_max'] )
			{
				$hookIpbBad		= $this->lang->words['h_toonew'];
			}

			foreach( $versions as $long => $human )
			{
				if( $hookData['hook_requirements']['hook_ipb_version_min'] <= $long )
				{
					$hookData['hook_requirements']['hook_ipb_version_min'] = $human;
					break;
				}
			}
			
			krsort( $versions );
		
			foreach( $versions as $long => $human )
			{
				if( $hookData['hook_requirements']['hook_ipb_version_max'] >= $long )
				{
					$hookData['hook_requirements']['hook_ipb_version_max'] = $human;
					break;
				}
			}
		}
		
		if( $hookData['hook_requirements']['hook_php_version_min'] OR $hookData['hook_requirements']['hook_php_version_max'] )
		{
			if( $hookData['hook_requirements']['hook_php_version_min'] AND version_compare( PHP_VERSION, $hookData['hook_requirements']['hook_php_version_min'], '<' ) == true )
			{
				$hookPhpBad		= $this->lang->words['h_phpold'];
			}
			
			if( $hookData['hook_requirements']['hook_php_version_max'] AND version_compare( PHP_VERSION, $hookData['hook_requirements']['hook_php_version_max'], '>' ) == true )
			{
				$hookPhpBad		= $this->lang->words['h_phpnew'];
			}
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->hookRequirements( $hookData, $hookIpbBad, $hookPhpBad );
	}
	
	/**
	 * Rebuild hooks cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildHooksCache()
	{
		/* INI */
		$cache		= array();

		/* First fix positions */
		$this->_fixPositions();

		/* Get current hooks */
		$this->DB->build( array( 'select'		=> 'f.*', 
									'from'		=> array( 'core_hooks_files' => 'f' ), 
									'where'		=> 'c.hook_enabled=1',
									'order'		=> 'c.hook_position ASC',
									'add_join'	=> array(
														array(
															'select' => 'c.hook_id, c.hook_key',
															'from'	 => array( 'core_hooks' => 'c' ),
															'where'	 => 'c.hook_id=f.hook_hook_id',
															'type'	 => 'left',
															)
														)
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$data	= unserialize( $r['hook_data'] );
			
			$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]	= array(
																						'filename'	=> $r['hook_file_stored'],
																						'className'	=> $r['hook_classname'],
																						);

			if( $r['hook_type'] == 'templateHooks' )
			{
				$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]['type']				= $data['type'];
				$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]['skinGroup']			= $data['skinGroup'];
				$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]['skinFunction']		= $data['skinFunction'];
				$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]['id']					= $data['id'];
				$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]['position']			= $data['position'];
			}
			else
			{
				$cache[ $r['hook_type'] ][ $r['hook_key'] ][ $r['hook_file_id'] ]['classToOverload']	= $data['classToOverload'];
			}
		}
		
		/* Update the cache */
		$this->cache->setCache( 'hooks', $cache, array( 'array' => 1 ) );
	}	
}