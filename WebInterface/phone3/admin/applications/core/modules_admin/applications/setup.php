<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Application Installation
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 5041 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_applications_setup extends ipsCommand
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
	 * Current app path
	 *
	 * @access	private
	 * @var		object
	 */
	private $app_full_path;
	
	/**
	 * Product information
	 *
	 * @access	private
	 * @var		array
	 */
	private $product_information;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_install' );
		
		/* Get Template and Language */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_setup' );
		
		$this->lang->loadLanguageFile( array( 'admin_setup', 'admin_system', 'admin_tools' ), 'core' );
		
		/* URL Bits */
		$this->form_code    = $this->html->form_code = 'module=applications&amp;section=setup';
		$this->form_code_js = $this->html->form_code_js = 'module=applications&section=setup';
		
		/* Get the setup class */
		require IPS_ROOT_PATH . "setup/sources/base/setup.php";
		
		/* Set the path */
		$this->app_full_path = IPSLib::getAppDir( $this->request['app_directory'] ) . '/';
		
		/* Set up product info from XML file */
		$this->product_information = IPSSetUp::fetchXmlAppInformation( $this->request['app_directory'] );
		
		if( ! $this->app_full_path OR ! $this->product_information['title'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__cannot_init'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] );
			return;		
		}
		
		/* Sequence of Events:
			# SQL
			# App Module
			# Check for more modules
			# Templates
			# Languages
			# Tasks
			# Settings
			# Template Cache
			# Caches / Done
		*/		
		switch( $this->request['do'] )
		{
			default:
			case 'start':
				$this->start();
			break;
			case 'sql':
				$this->sqlBasics();
			break;
			case 'sql_steps':
				$this->sqlSteps();
			break;
			case 'next_check':
				$this->nextCheck();
			break;
			
			case 'templates':
				$this->templates();
			break;
			case 'languages':
				$this->languages();
			break;
			case 'tasks':
				$this->tasks();
			break;
			case 'bbcode':
				$this->bbcode();
			break;
			case 'help':
				$this->help();
			break;
			case 'settings':
				$this->settings();
			break;
			case 'hooks':
				$this->hooks();
			break;
			case 'tplcache':
				$this->recacheTemplates();
			break;
			case 'finish':
				$this->finish();
			break;
		}

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Rebuild PHP Templates Cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function recacheTemplates()
	{
		//-----------------------------------------
		// Determine if we need to recache templates
		//-----------------------------------------
		
		$vars		= $this->getVars();
		$hasSkin	= false;
		
		if ( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_replacements.xml' ) )
		{
			$hasSkin	= true;
		}
		
		if( !$hasSkin )
		{
			//-----------------------------------------
			// We'll check for any of the default 3
			//-----------------------------------------
			
			if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_root_templates.xml' ) OR
				file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_lofi_templates.xml' ) OR
				file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_xmlskin_templates.xml' ) )
			{
				$hasSkin	= true;
			}
		}

		if( !$hasSkin )
		{
			//-----------------------------------------
			// We'll check for any of the default 3
			//-----------------------------------------
			
			if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_root_css.xml' ) OR
				file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_lofi_css.xml' ) OR
				file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_xmlskin_css.xml' ) )
			{
				$hasSkin	= true;
			}
		}
		
		//-----------------------------------------
		// See if any hooks were installed..
		//-----------------------------------------
		
		if( !$hasSkin )
		{
			if( is_dir( $this->app_full_path . 'xml/hooks' ) )
			{
				$files	= scandir( $this->app_full_path . 'xml/hooks' );
				
				foreach( $files as $file )
				{
					if( $file != '.' AND $file != '..' )
					{
						$hasSkin	= true;
						break;
					}
				}
			}
		}
		
		if( !$hasSkin )
		{
			$this->showRedirectScreen( $vars['app_directory'], array( $this->lang->words['redir__no_template_re'] ) , '', $this->getNextURL( 'finish', $vars ) );
		}
		
		/* INIT */
		$setID = intval( $this->request['setID'] );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );
		
		$skinFunctions = new skinImportExport( $this->registry );
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );	
		
		/* Get first set id */
		if( ! $setID )
		{
			ksort( $this->registry->output->allSkins );
			$_skins = $this->registry->output->allSkins;
			$_set   = array_shift( $_skins );
			$setID  = $_set['set_id'];
		}

		/* Rebuild */
		$skinFunctions->rebuildPHPTemplates( $setID );
		$skinFunctions->rebuildCSS( $setID );
		$skinFunctions->rebuildReplacementsCache( $setID );
		$skinFunctions->rebuildSkinSetsCache();
				
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
			$this->showRedirectScreen( $vars['app_directory'], array( $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'] ), '', $this->getNextURL( 'tplcache&amp;setID=' . $nextID, $vars ) );
		}
		else
		{
			$this->showRedirectScreen( $vars['app_directory'], array( $this->lang->words['to_recache_done'] ) , '', $this->getNextURL( 'finish', $vars ) );
		}

	}	
	
	/**
	 * Finalizes installation and rebuilds caches
	 *
	 * @access	public
	 * @return	void
	 **/
	public function finish()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $vars['app_directory'] );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $vars['app_directory'] );
		
		/* Grab Data */
		$data['app_directory']   = $vars['app_directory'];
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Rebuild applications and modules cache */
		$this->cache->rebuildCache( 'app_cache', 'global' );
		$this->cache->rebuildCache( 'module_cache', 'global' );
		$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		$this->cache->rebuildCache( 'group_cache', 'global' );

		/* Rebuild application specific caches */
		$_file = $this->app_full_path . 'extensions/coreVariables.php';
			
		if( file_exists( $_file ) )
		{
			require( $_file );
			
			if( is_array( $CACHE ) AND count( $CACHE ) )
			{
				foreach( $CACHE as $key => $cdata )
				{
					$this->cache->rebuildCache( $key, $vars['app_directory'] );
				}
			}
		}		
		
		/* Show completed screen... */
		$this->registry->output->html .= $this->html->setup_completed_screen( $data, $vars['type'] );
	}
	
	/**
	 * Next Check
	 *
	 * @access	public
	 * @return	void
	 **/
	public function nextCheck()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $vars['app_directory'] );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $vars['app_directory'] );
		$modules   = IPSSetUp::fetchXmlAppModules( $vars['app_directory'] );
		
		/* Grab Data */
		$data['app_directory']   = $vars['app_directory'];
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Update the app DB */
		if( $vars['type'] == 'install' )
		{
			/* Get current max position */
			$pos = $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as pos', 'from' => 'core_applications' ) );
			$new_pos = intval( $pos['pos'] ) + 1;
			
			/* Insert details into the DB */
			$this->DB->insert( 'core_applications', array( 
																'app_title'			=> $this->product_information['name'],
																'app_public_title'	=> $this->product_information['public_name'],
																'app_author'		=> $this->product_information['author'],
																'app_description'	=> $this->product_information['description'],
																'app_hide_tab'		=> intval($this->product_information['hide_tab']),
																'app_version'		=> $_numbers['latest'][1],
																'app_long_version'	=> $_numbers['latest'][0],
																'app_directory'		=> $vars['app_directory'],
																'app_location'		=> $vars['app_location'],
																'app_added'			=> time(),
																'app_position'		=> $new_pos,
																'app_protected'		=> 0,
																'app_enabled'		=> $this->product_information['disabledatinstall'] ? 0 : 1
															)
								);
								
			$this->DB->insert( 'upgrade_history', array( 
														'upgrade_version_id'	=> $_numbers['latest'][0],
														'upgrade_version_human'	=> $_numbers['latest'][1],
														'upgrade_date'			=> time(),
														'upgrade_notes'			=> '',
														'upgrade_mid'			=> $this->memberData['member_id'],
														'upgrade_app'			=> $vars['app_directory']
												)	);
			
			/* Insert the modules */
			foreach( $modules as $key => $module )
			{
				$this->DB->insert( 'core_sys_module', $module );
			}
		}
		else
		{
			$this->DB->update( 'core_applications', array( 
															'app_version'      => $_numbers['current'][1],
															'app_long_version' => $_numbers['current'][0] 
							), "app_directory='" . $vars['app_directory'] . "'" );
			
			/* Update the modules */
			foreach( $modules as $key => $module )
			{
				$this->DB->update( 'core_sys_module', $module, "sys_module_application='{$module['sys_module_application']}' AND sys_module_key='{$module['sys_module_key']}'" );
			}
		}
		
		/* Finish? */
		if( $vars['type'] == 'install' OR $vars['version'] == $_numbers['latest'][0] )
		{
			/* Go back and start over with the new version */
			$output[] = $this->lang->words['redir__nomore_modules'];

			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'templates', $vars ) );
		}
		else
		{
			/* Go back and start over with the new version */
			$output[] = sprintf( $this->lang->words['redir__upgraded_to'], $_numbers['current'][1] );
			
			/* Work out the next step */
			$vars['version'] = $_numbers['next'][0];
			
			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'sql', $vars ) );
		}
	}
	
	/**
	 * Import Hooks
	 *
	 * @access	public
	 * @return	void
	 **/
	public function hooks()
	{
		/* INIT */
		$vars          = $this->getVars();
		$output        = array();
		$errors        = array();
		$knownSettings = array();
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		
		if( file_exists( $this->app_full_path . 'xml/hooks.xml' ) )
		{
			/* Get the hooks class */
			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/applications/hooks.php' );
			$hooks = new admin_core_applications_hooks();
			$hooks->makeRegistryShortcuts( $this->registry );

			$return = $hooks->installAppHooks( $vars['app_directory'] );
			$this->cache->rebuildCache( 'hooks', 'global' );
			
			$output[] = sprintf( $this->lang->words['redir__hooks'], $return['inserted'], $return['updated'] );
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['hooks_nofile'];
			
			$output[] = $this->registry->output->global_message;
		}
		
		/* Clear main messaage */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'tplcache', $vars ) );
	}
	
	/**
	 * Import Settings
	 *
	 * @access	public
	 * @return	void
	 **/
	public function settings()
	{
		/* INIT */
		$vars          = $this->getVars();
		$output        = array();
		$errors        = array();
		$knownSettings = array();
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		
		if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_settings.xml' ) )
		{
			/* Get the settings class */			
			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/tools/settings.php' );
			$settings = new admin_core_tools_settings( $this->registry );
			$settings->makeRegistryShortcuts( $this->registry );
			
			$this->request['app_dir'] = $vars['app_directory'];
			
			//-----------------------------------------
			// Known settings
			//-----------------------------------------

			if ( substr( $this->settings['_original_base_url'], -1 ) == '/' )
			{
				IPSSetUp::setSavedData('install_url', substr( $this->settings['_original_base_url'], 0, -1 ) );
			}
			
			if ( substr( $this->settings['base_dir'], -1 ) == '/' )
			{
				IPSSetUp::setSavedData('install_dir', substr( $this->settings['base_dir'], 0, -1 ) );
			}
			
			/* Fetch known settings  */
			if ( file_exists( IPSLib::getAppDir( $vars['app_directory'] ) . '/setup/versions/install/knownSettings.php' ) )
			{
				require( IPSLib::getAppDir( $vars['app_directory'] ) . '/setup/versions/install/knownSettings.php' );
			}
			
			$settings->importAllSettings( 1, 1, $knownSettings );
			$settings->settingsRebuildCache();
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['settings_nofile'];
		}
		
		$output[] = $this->registry->output->global_message;
		
		/* Clear main messaage */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'hooks', $vars ) );
	}
	
	/**
	 * Import tasks
	 *
	 * @access	public
	 * @return	void
	 **/
	public function tasks()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_tasks.xml' ) )
		{
			/* Get the language class */
			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/system/taskmanager.php' );
			$task_obj = new admin_core_system_taskmanager( $this->registry );
			$task_obj->makeRegistryShortcuts( $this->registry );
			
			$task_obj->tasksImportFromXML( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_tasks.xml', true );
		}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['no_tasks_for_import'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'bbcode', $vars ) );
	}
	
	/**
	 * Import bbcode
	 *
	 * @access	public
	 * @return	void
	 **/
	public function bbcode()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_bbcode.xml' ) )
		{
			/* Get the language class */
			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/bbcode.php' );
			$bbcode = new admin_core_posts_bbcode();
			$bbcode->makeRegistryShortcuts( $this->registry );
			
			$bbcode->bbcodeImportDo( file_get_contents( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_bbcode.xml' ) );
			
			$output[] = $this->lang->words['bbcode_and_media'];
		}
		
		if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_mediatag.xml' ) )
		{
			/* Get the language class */
			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/media.php' );
			$bbcode = new admin_core_posts_media();
			$bbcode->makeRegistryShortcuts( $this->registry );
			
			$bbcode->doMediaImport( file_get_contents( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_mediatag.xml' ) );
			
			if( !count($output) )
			{
				$output[] = $this->lang->words['bbcode_and_media'];
			}
		}
		
		if( !count($output) )
		{
			$output[] = $this->lang->words['no_bbcode_media'];
		}

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'help', $vars ) );
	}
	
	/**
	 * Import help
	 *
	 * @access	public
	 * @return	void
	 **/
	public function help()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_help.xml' ) )
		{
			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/tools/help.php' );
			$help = new admin_core_tools_help();
			$help->makeRegistryShortcuts( $this->registry );

			$done = $help->helpFilesXMLImport_app( $vars['app_directory'] );
			
			$output[] = sprintf( $this->lang->words['imported_x_help'], ($done['added'] + $done['updated']) );
		}
		else
		{
			$output[] = $this->lang->words['imported_no_help'];
		}
		

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'settings', $vars ) );
	}
	
	/**
	 * Language Import
	 *
	 * @access	public
	 * @return	void
	 **/
	public function languages()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Load the language file */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		
		/* Get the language stuff */
		require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php' );
		$lang = new admin_core_languages_manage_languages( $this->registry );		
		$lang->makeRegistryShortcuts( $this->registry );	
			
		/* Loop through the xml directory and look for lang packs */
		$_PATH = $this->app_full_path . '/xml/';		
		
		try
		{
			foreach( new DirectoryIterator( $_PATH ) as $f )
			{
				if( preg_match( "#(.+?)_language_pack.xml#", $f->getFileName() ) )
				{
					$this->request['file_location'] = $_PATH . $f->getFileName();
					$lang->imprtFromXML( true, true, true, $vars['app_directory'] );				
				}
			}
		} catch ( Exception $e ) {}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['redir__nolanguages'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'tasks', $vars ) );
	}
	
	/**
	 * Install templates
	 *
	 * @access	public
	 * @return	void
	 **/
	public function templates()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );
		$skinFunctions	= new skinImportExport( $this->registry );
		$skinCaching	= new skinCaching( $this->registry );
		
		/* Grab skin data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Bit of jiggery pokery... */
			if ( $row['set_key'] == 'default' )
			{
				$row['set_key'] = 'root';
				$row['set_id']  = 0;
			}
			
			$skinSets[ $row['set_key'] ] = $row;
		}
			
		foreach( $skinSets as $skinKey => $skinData )
		{
			/* Skin files first */
			if( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_' . $skinKey . '_templates.xml' ) )
			{
				$return = $skinFunctions->importTemplateAppXML( $vars['app_directory'], $skinKey, $skinData['set_id'], TRUE );
				
				$output[] = sprintf( $this->lang->words['redir__templates'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* Then CSS files */
			if ( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_' . $skinKey . '_css.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importCSSAppXML( $vars['app_directory'], $skinKey, $skinData['set_id'] );
				
				$output[] = sprintf( $this->lang->words['redir__cssfiles'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* And we can support replacements for good measure */
			if ( file_exists( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_replacements.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importReplacementsXMLArchive( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_' . $skinKey . '_replacements.xml' );
				
				$output[] = $this->lang->words['redir__replacements'];
			}
		}
		
		/* Recache */
		//$skinCaching->rebuildPHPTemplates( 0 );
		//$skinCaching->rebuildCSS( 0 );
		//$skinCaching->rebuildReplacementsCache( 0 );

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'languages', $vars ) );
	}
	
	/**
	 * Runs any additional sql files
	 *
	 * @access	public
	 * @return	void
	 **/
	public function sqlSteps()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		$id        = intval( $this->request['id'] );
		$id        = ( $id < 1 ) ? 1 : $id;
		$sql_files = array();
		
		/* Any "extra" configs required for this driver? */
		if( file_exists( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}
		
		
		/* Run any sql files we found */
		if( file_exists( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' ) )
		{
			/* INIT */
			$new_id = $id + 1;
			$count  = 0;
			
			/* Get the sql file */
			require_once( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' );

			$this->DB->return_die = 1;
			
			/* Run the queries */
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';

				$query = str_replace( "<%time%>", time(), $query );
				
				if( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
				{
					 $query = $extra_install->process_query_insert( $query );
				}
				
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$errors[] = $query."<br /><br />".$this->DB->error;
				}
				else
				{
					$count++;
				}				
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_run'], $count );
			
			/* Show redirect... */
			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) . '&amp;id=' . $new_id );
		}
		else
		{
			$output[] = $this->lang->words['redir__nomore_sql'];

			/* Show redirect... */
			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'next_check', $vars ) );
		}
	}
	
	/**
	 * Creates Tables, Runs Inserts, and Indexes
	 *
	 * @access	public
	 * @return	void
	 **/
	public function sqlBasics()
	{
		/* INIT */
		$vars		= $this->getVars();
		$output		= array();
		$errors		= array();
		$skipped	= 0;
		$count		= 0;

		/* Any "extra" configs required for this driver? */
		if( file_exists( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}

		//-----------------------------------------
		// Tables
		//-----------------------------------------
		
		$this->DB->return_die = 1;

		if ( file_exists( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' ) )
		{
			include( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' );

			if ( is_array( $TABLE ) and count( $TABLE ) )
			{
				foreach( $TABLE as $q )
				{
					//-----------------------------------------
					// Is this a create?
					//-----------------------------------------
					
					preg_match("/CREATE TABLE (\S+)(\s)?\(/", $q, $match);

					if( $match[1] AND $vars['dupe_tables'] == 'drop' )
					{
						$this->DB->dropTable( str_replace( $this->settings['sql_tbl_prefix'], '', $match[1] ) );
					}
					else if( $match[1] )
					{
						if( $this->DB->getTableSchematic( $match[1] ) )
						{
							$skipped++;
							continue;
						}
					}
					
					//-----------------------------------------
					// Is this an alter?
					//-----------------------------------------
					
					preg_match("/ALTER\s+TABLE\s+(\S+)\s+ADD\s+(\S+)\s+/i", $q, $match);

					if( $match[1] AND $match[2] AND $vars['dupe_tables'] == 'drop' )
					{
						$this->DB->dropField( str_replace( $this->settings['sql_tbl_prefix'], '', $match[1] ), $match[2] );
					}
					else if( $match[1] AND $match[2] )
					{
						if( $this->DB->checkForField( $match[2], $match[1] ) )
						{
							$skipped++;
							continue;
						}
					}
		
					if ( $extra_install AND method_exists( $extra_install, 'process_query_create' ) )
					{
						 $q = $extra_install->process_query_create( $q );
					}
					$this->DB->error = '';
				
					$this->DB->query( $q );
					
					if ( $this->DB->error )
					{
						$errors[] = $q."<br /><br />".$this->DB->error;
					}
					else
					{
						$count++;
					}
				}
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_tables'], $count, $skipped );
		}
		
		//---------------------------------------------
		// Create the fulltext index...
		//---------------------------------------------

		if ( $this->DB->checkFulltextSupport() )
		{
			if ( file_exists( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' ) )
			{
				include( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' );
				
				$count	= 0;
	
				foreach( $INDEX as $q )
				{
					//---------------------------------------------
					// Pass to handler
					//---------------------------------------------
					
					if ( $extra_install AND method_exists( $extra_install, 'process_query_index' ) )
					{
						$q = $extra_install->process_query_index( $q );
					}
					
					//---------------------------------------------
					// Pass query
					//---------------------------------------------
					$this->DB->error = '';
					$this->DB->query( $q );
					
					if ( $this->DB->error )
					{
						$errors[] = $q."<br /><br />".$this->DB->error;
					}
					else
					{
						$count++;
					}
				}
				
				$output[] = sprintf( $this->lang->words['redir__sql_indexes'], $count );
			}
		}
		
		/* INSERTS */
		if ( file_exists( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' ) )
		{
			$count   = 0;
			
			/* Get the SQL File */
			include( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' );
			
			foreach( $INSERT as $q )
			{
				/* Extra Handler */
			 	if( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
			 	{
					$q = $extra_install->process_query_insert( $q );
				}
				
				$q = str_replace( "<%time%>", time(), $q );
				$this->DB->error = '';
				$this->DB->query( $q );
				
				if ( $this->DB->error )
				{
					$errors[] = $q."<br /><br />".$this->DB->error;
				}
				else
				{
					$count++;
				}
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_inserts'], $count );
		}

		/* Show Redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) );
	}
	
	/**
	 * Begin installation
	 *
	 * @access	public
	 * @return	void
	 **/
	public function start()
	{
		/* INIT */
		$app_directory = IPSText::alphanumericalClean( $this->request['app_directory'] );
		$type          = 'upgrade';
		$data          = array();
		$ok            = 1;
		$errors        = array();
		$localfiles    = array( DOC_IPS_ROOT_PATH . 'cache/skin_cache' );
		$info          = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $app_directory );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $app_directory );
		$_files    = IPSSetUp::fetchXmlAppWriteableFiles( $app_directory );
		
		/* Grab Data */
		$data['app_directory']   = $app_directory;
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Install, or upgrade? */
		if ( ! $_numbers['current'][0] )
		{
			$type = 'install';
		}
		
		//-----------------------------------------
		// For upgrade, redirect
		//-----------------------------------------
		
		else
		{
			@header( "Location: {$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/" );
			exit;
		}
		
		/* Version Check */
		if( $data['current_version'] > 0 AND $data['current_version'] == $data['latest_version'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__up_to_date'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] );
			return;
		}
		
		/* Check local files */
		foreach( $localfiles as $_path )
		{
			if ( ! file_exists( $_path ) )
			{
				if ( $data['dir'] )
				{
					if ( ! @mkdir( $_path, 0777, TRUE ) )
					{
						$info['notexist'][] = $_path;
					}
				}
				else
				{
					$info['notexist'][] = $_path;
				}
			}
			else if ( ! is_writeable( $_path ) )
			{
				if ( ! @chmod( $_path, 0777 ) )
				{
					$info['notwrite'][] = $_path;
				}
			}
		}
		
		/* Check files... */
		if( is_array( $_files ) AND count( $_files ) )
		{
			$info = array_merge( $info, $_files );
		}
		
 		if ( count( $info['notexist'] ) )
		{
			foreach( $info['notexist'] as $path )
			{
				$errors[] = sprintf( $this->lang->words['error__file_missing'], $path );
			}
		}
		
		if ( count( $info['notwrite'] ) )
		{
			foreach( $info['notwrite'] as $path )
			{
				$errors[] = sprintf( $this->lang->words['error__file_chmod'], $path );
			}
		}
		
		/**
		 * Custom errors
		 */
		if ( count( $info['other'] ) )
		{
			foreach( $info['other'] as $error )
			{
				$errors[]	= $error;
			}
		}
		
		/* Check for xml files */
		$required_xml = array( 
								"information",
								//"{$app_directory}_modules",
								//"{$app_directory}_settings",
								//"{$app_directory}_tasks",
								//"{$app_directory}_templates", 
							);

		foreach( $required_xml as $r )
		{
			if( ! file_exists( $this->app_full_path . "xml/{$r}.xml" ) )
			{
				$errors[] = sprintf( $this->lang->words['error__file_needed'], $this->app_full_path . "xml/{$r}.xml" );
			}
		}

		/* Show splash */
		$this->registry->output->html .= $this->html->setup_splash_screen( $data, $errors, $type );
	}
	
	/**
	 * Get environment vars
	 *
	 * @access	private
	 * @return	array
	 **/
	private function getVars()
	{
		/* INIT */
		$env = array();
		
		/* Get the infos */
		$env['type']			= strtolower( $this->request['type'] );
		$env['version']			= $this->request['version'];
		$env['dupe_tables']		= $this->request['dupe_tables'];
		$env['app_directory']	= $this->request['app_directory'];
		
		$env['app_location']	= 'other';
		
		if( $this->product_information['ipskey'] )
		{
			if ( strstr( $this->app_full_path, 'applications_addon/ips' ) or strstr( $this->app_full_path, 'applications/' ) )
			{
				if ( md5( 'ips_' . basename($this->app_full_path) ) == $this->product_information['ipskey'] )
				{
					$env['app_location']	= 'ips';
				}
			}
		}
		
		$env['path'] = ( $env['type'] == 'install' ) ? $this->app_full_path . 'setup/versions/install'
											         : $this->app_full_path . 'setup/versions/' . $env['version'];

		return $env;
	}
	
	/**
	 * Get next action URL
	 *
	 * @access	private
	 * @param	string	$next_action
	 * @param	array	$env
	 * @return	string
	 **/
	private function getNextURL( $next_action, $env )
	{
		return $this->settings['base_url'] . $this->form_code . '&amp;do=' . $next_action . '&amp;app_directory=' . $env['app_directory'] . '&amp;type=' . $env['type'] . '&amp;version=' . $env['version'];
	}
	
	/**
	 * Show the redirect screen
	 *
	 * @access	private
	 * @param	string	$app_directory
	 * @param	string	$output
	 * @param	string	$errors
	 * @param	string	$next_url
	 * @return	void
	 **/
	private function showRedirectScreen( $app_directory, $output, $errors, $next_url )
	{
		/* Init Data */
		$data		= IPSSetUp::fetchXmlAppInformation( $app_directory );
		$_numbers	= IPSSetUp::fetchAppVersionNumbers( $app_directory );
		
		/* Grab Data */
		$data['app_directory']   = $app_directory;
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
			
		/* Setup Redirect */
		$this->registry->output->html .= $this->html->setup_redirectScreen( $output, $errors, $next_url );
	}
}