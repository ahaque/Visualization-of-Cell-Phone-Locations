<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Group management
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Monday 15th January 2007 (15:01)
 * @version		$Revision: 5041 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_applications_applications extends ipsCommand
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
		// Load skin
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_applications');
		$this->registry->class_localization->loadLanguageFile( array( 'admin_applications' ) );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code    = $this->html->form_code = 'module=applications&amp;section=applications';
		$this->form_code_js = $this->html->form_code_js = 'module=applications&section=applications';
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch($this->request['do'])
		{
			default:
			case 'applications_overview':
				$this->request[ 'do'] =  'applications_overview' ;
				$this->applicationsOverview();
			break;
			case 'application_manage_position':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationManagePosition();
			break;
			case 'application_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationForm( 'edit' );
			break;
			case 'application_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationForm( 'add' );
			break;
			case 'application_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationSave( 'edit' );
			break;
			case 'application_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationSave( 'add' );
			break;
			case 'application_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_delete' );
				$this->applicationRemove();
			break;
			case 'application_remove_splash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_delete' );
				$this->applicationRemoveSplash();
			break;			
			case 'application_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationExport();
			break;
			case 'application_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationImport();
			break;
			case 'toggle_app':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_manage' );
				$this->toggleAppEnabled();
			break;
			case 'inDevRebuildAll':
				$this->inDevRebuildAll();
			break;
			case 'inDevExportAll':
				$this->inDevExportAll();
			break;
			case 'module_recache_all':
				$this->moduleRecacheAll();
			break;
			case 'modules_overview':
				$this->modules_overview();
			break;
			case 'module_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleForm( 'edit' );
			break;
			case 'module_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleForm( 'add' );
			break;
			case 'module_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleSave( 'edit' );
			break;
			case 'module_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleSave( 'add' );
			break;
			case 'module_manage_position':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleManagePosition();
			break;
			case 'module_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_delete' );
				$this->moduleRemove();
			break;
			case 'module_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleExport();
			break;
			case 'module_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleImport();
			break;
			
			case 'build_sphinx':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'build_sphinx' );
				$this->buildSphinx();
			break;
			
			case 'seoRebuild':
				$this->seoRebuild();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Toggle application enabled/disabled
	 *
	 * @access	public
	 * @return	void
	 */
	public function toggleAppEnabled()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$app_id = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select' => '*',
																		 'from'   => 'core_applications',
																		 'where'  => 'app_id=' . $app_id ) );
		
		if( !$application['app_id'] )	
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_tog_app'], 111161 );
		}
		
		$this->DB->update( 'core_applications', array( 'app_enabled' => $application['app_enabled'] ? 0 : 1 ), 'app_id=' . $app_id );
		
		$this->moduleRecacheAll(1);
		
		$this->registry->output->global_message = $this->lang->words['app_toggled_ok'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Build the FURL templates file into cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function seoRebuild()
	{
		try
		{
			IPSLib::cacheFurlTemplates();
			$msg = $this->lang->words['furl_cache_rebuilt'];
		}
		catch( Exception $e )
		{
			$msg = $e->getMessage();
			
			switch( $msg )
			{
				case 'CANNOT_WRITE':
					$msg = $this->lang->words['seo_cannot_write'];
				break;
				case 'NO_DATA_TO_WRITE':
					$msg = $this->lang->words['seo_no_data'];
				break;
			}
		}
		
		$this->registry->output->global_message = $msg;
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	
	/**
	 * Build the sphinx conf file and give for download
	 *
	 * @access	public
	 * @return	void
	 */
	public function buildSphinx()
	{
		$sphinxContent	= IPSLib::rebuildSphinxConfig();
		
		$this->registry->output->showDownload( $sphinxContent, 'sphinx.conf', '', 0 );
	}

	/**
	 * IN DEV Tool to rebuild all data via XML
	 * 
	 * @access	private
	 * @return	void
	 */
	private function inDevRebuildAll()
	{
		$output = array();
		
		/* Do each app */
		$apps       = new IPSApplicationsIterator();
		
		foreach( $apps as $app )
		{
			$app_dir = $apps->fetchAppDir();
			
			$this->request['_app'] = $app_dir;
			$return   = $this->moduleImport('', 1, FALSE);
			$output[] = 'App - ' . $app_dir . " done: " . $return;
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['modules'][ $app_dir ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
		
		/* Recache */
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		$this->moduleRecache();
		
		$this->registry->output->global_message = implode( "<br />", $output );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * IN DEV Tool to rebuild all data via XML
	 * 
	 * @access	private
	 * @return	void
	 */
	private function inDevExportAll()
	{
		$output = array();
		
		/* Do each app */
		$apps       = new IPSApplicationsIterator();
		
		foreach( $apps as $app )
		{
			$app_dir = $apps->fetchAppDir();
			
			if ( ! is_writeable( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_modules.xml' ) )
			{
				$output[] = "Cannot write to " . IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_modules.xml';
				continue;
			}
			
			$this->request['app_dir'] = $app_dir;
			$moduleXML = $this->moduleExport( 1 );
			
			file_put_contents( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_modules.xml', $moduleXML['xml'] );
			
			$output[] = $app_dir . " modules exported";
		}
		
		$this->registry->output->global_message = implode( "<br />", $output );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Remove a module
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function moduleRemove()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$app_id                 = intval( $this->request['app_id'] );
		$sys_module_id          = intval( $this->request['sys_module_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select'	=> '*',
																'from'	=> 'core_applications',
																'where'	=> 'app_id=' . $app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Got a module?
		//-----------------------------------------
		
		$module = $this->DB->buildAndFetch( array( 'select'	=> '*',
															'from'	=> 'core_sys_module',
															'where'	=> 'sys_module_id='.$sys_module_id ) );

		if ( ! $module['sys_module_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Protected?
		//-----------------------------------------
		
		if ( ! IN_DEV and $module['sys_module_protected'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_protectedmod'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Update all children...
		//-----------------------------------------
		
		$this->DB->update( 'core_sys_module', array( 'sys_module_parent' => '' ), "sys_module_parent='". $module['sys_module_key'] . "'" );
		
		//-----------------------------------------
		// Remove...
		//-----------------------------------------
		
		$this->DB->delete( 'core_sys_module', 'sys_module_id='.$sys_module_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->moduleRecache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['a_removed'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id='.$app_id.'&amp;sys_module_admin=' . $module['sys_module_admin'] );
	}
	
	/**
	 * Import a module
	 *
	 * @access	public
	 * @param	string		[Optional] XML content
	 * @param	integer		IN_DEV override
	 * @return	void		[Outputs to screen]
	 */
	public function moduleImport( $content='', $in_dev=0, $return=TRUE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$updated     = 0;
		$inserted    = 0;
		$modules	 = array();
		$apps        = array();
		$app_id      = 0;
		$app_dir     = '';
		
		//-----------------------------------------
		// Got content?
		//-----------------------------------------
		
		if ( ! $content )
		{
			//-----------------------------------------
			// INDEV?
			//-----------------------------------------

			if ( $in_dev )
			{
				$_FILES['FILE_UPLOAD']['name']   = '';
				$this->request[ 'file_location'] =  IPSLib::getAppDir( $this->request['_app'] ) . '/xml/' . $this->request['_app'] . '_modules.xml';
			}
			else
			{
				$this->request[ 'file_location'] =  IPS_ROOT_PATH . $this->request['file_location'] ;
			}
			
			//-----------------------------------------
			// Uploaded file?
			//-----------------------------------------
		
			if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
			{
				//-----------------------------------------
				// check and load from server
				//-----------------------------------------
			
				if ( ! $this->request['file_location'] )
				{
					if ( $return )
					{
						$this->registry->output->global_message = $this->lang->words['a_nofile'];
						$this->applicationsOverview();
						return;
					}
					else
					{
						return 'Nothing to import';
					}
				}
			
				if ( ! file_exists( $this->request['file_location'] ) )
				{
					if ( $return )
					{
						$this->registry->output->global_message = $this->lang->words['a_file404'] . $this->request['file_location'];
						$this->applicationsOverview();
						return;
					}
					else
					{
						return 'Nothing to import';
					}
				}
			
				if ( preg_match( "#\.gz$#", $this->request['file_location'] ) )
				{
					if ( $FH = @gzopen( $this->request['file_location'], 'rb' ) )
					{
						while ( ! @gzeof( $FH ) )
						{
							$content .= @gzread( $FH, 1024 );
						}
					
						@gzclose( $FH );
					}
				}
				else
				{
					if ( $FH = @fopen( $this->request['file_location'], 'rb' ) )
					{
						$content = @fread( $FH, filesize($this->request['file_location']) );
						@fclose( $FH );
					}
				}
			}
			else
			{
				//-----------------------------------------
				// Get uploaded schtuff
				//-----------------------------------------
			
				$tmp_name = $_FILES['FILE_UPLOAD']['name'];
				$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
				$content  = ipsRegistry::getClass('adminFunctions')->importXml( $tmp_name );
			}
		}
	
		//-----------------------------------------
		// Get current applications
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'app_id, app_directory',
										'from'	=> 'core_applications',
										'order'	=> 'app_id' ) );

		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			$apps[ $r['app_directory'] ] = $r['app_id'];
		}
			
		//-----------------------------------------
		// Get current modules
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'core_sys_module',
								 'order'	=> 'sys_module_id' ) );
		
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$modules[ $r['sys_module_application'] ][ intval( $r['sys_module_admin'] ) . '-' . $r['sys_module_key'] ] = $r['sys_module_id'];
		}
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPS_DOC_CHAR_SET );

		$xml->loadXML( $content );
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		$fields = array( 'sys_module_title'    , 'sys_module_application', 'sys_module_key'   , 'sys_module_description', 'sys_module_version', 'sys_module_parent',
						 'sys_module_protected', 'sys_module_visible'    , 'sys_module_tables', 'sys_module_hooks'      , 'sys_module_position', 'sys_module_admin' );

		foreach( $xml->fetchElements( 'module' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			foreach( $data as $k => $v )
			{
				if ( ! in_array( $k, $fields ) )
				{
					unset( $data[ $k ] );
				}
			}
		
			$app_dir = $data['sys_module_application'];
			$_key    = intval( $data['sys_module_admin'] ) . '-' . $data['sys_module_key'];
			
			//-----------------------------------------
			// Insert, or update...
			//-----------------------------------------
			
			if ( $apps[ $app_dir ] )
			{
				//-----------------------------------------
				// Insert or update?
				//-----------------------------------------
			
				if ( $modules[ $app_dir ][ $_key ] )
				{
					//-----------------------------------------
					// Update
					//-----------------------------------------
				
					$updated++;
					$this->DB->update( 'core_sys_module', $data, "sys_module_id=" . $modules[ $data['sys_module_application'] ][ $_key ] );
				
				}
				else
				{
					//-----------------------------------------
					// Insert
					//-----------------------------------------
				
					$inserted++;
					$this->DB->insert( 'core_sys_module', $data );
				}
			}
		}

		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		if ( $return )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['a_insertupdated'], $inserted, $updated );
		
			//-----------------------------------------
			// Recache
			//-----------------------------------------
		
			$this->moduleRecache();
		
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id=' . $apps[ $app_dir ] );
		}
		else
		{
			return sprintf( $this->lang->words['a_insertupdated'], $inserted, $updated );
		}
	}
	
	/**
	 * Export modules
	 *
	 * @access	public
	 * @param	integer		Return the XML [1] or print to browser [0]
	 * @return	mixed		XML content or outputs to screen
	 */
	public function moduleExport( $return_xml=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$app_id  = intval( $this->request['app_id'] );
		$app_dir = trim( IPSText::alphanumericalClean( $this->request['app_dir'] ) );
		$xml     = '';
		
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		
		if ( $app_id )
		{
			$application = $this->DB->buildAndFetch( array( 'select'	=> '*',
																	'from'	=> 'core_applications',
																	'where'	=> 'app_id='.$app_id ) );
		}
		else if ( $app_dir )
		{
			$application = $this->DB->buildAndFetch( array( 'select'	=> '*',
																	'from'	=> 'core_applications',
																	'where'	=> "app_directory='" . $app_dir . "'") );
		}
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------
		
		$xml->addElement( 'moduleexport' );
		
		//-----------------------------------------
		// Get applications
		//-----------------------------------------
		
		$xml->addElement( 'modulegroup', 'moduleexport' );
		
		$this->DB->build( array( 'select' => '*',
													  'from'   => 'core_sys_module',
													  'where'  => "sys_module_application='".$application['app_directory']."'",
													  'order'  => 'sys_module_admin, sys_module_position'  ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			unset( $r['sys_module_id'] );
			
			$xml->addElementAsRecord( 'modulegroup', 'module', $r );
		}

		if( $return_xml )
		{
			return array( 'title' => $application['app_directory'] . '_modules.xml', 'xml' => $xml->fetchDocument() );
		}
		else 
		{
			$this->registry->output->showDownload( $xml->fetchDocument(), $application['app_directory'] . '_modules.xml', '', 0 );
		}
	}
	
	/**
	 * Save a module
	 *
	 * @access	private
	 * @param 	string		Type [add|edit]
	 * @return	void		[Outputs to screen]
	 */
	private function moduleSave( $type='add' )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$app_id                 = intval( $this->request['app_id'] );
		$sys_module_admin       = intval( $this->request['sys_module_admin'] );
		$sys_module_id          = intval( $this->request['sys_module_id'] );
		$sys_module_title		= trim( $this->request['sys_module_title'] );
		$sys_module_key		    = IPSText::alphanumericalClean( trim( $this->request['sys_module_key'] ) );
		$sys_module_description = trim( $this->request['sys_module_description'] );
		$sys_module_version	    = trim( $this->request['sys_module_version'] );
		$sys_module_parent      = IPSText::alphanumericalClean( trim( $this->request['sys_module_parent'] ) );
		$sys_module_protected   = intval( $this->request['sys_module_protected'] );
		$sys_module_visible     = intval( $this->request['sys_module_visible'] );
		$application            = array();
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select'	=> '*',
																'from'	=> 'core_applications',
																'where'	=> 'app_id='.$app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//--------------------------------------------
		// Check
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			$module = $this->DB->buildAndFetch( array( 'select' => '*',
																		'from'   => 'core_sys_module',
																		'where'  => 'sys_module_id='.$sys_module_id ) );

			if ( ! $module['sys_module_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->modules_overview();
				return;
			}
		}
		else
		{
			//-----------------------------------------
			// Make sure that we don't have a key already
			//-----------------------------------------
			
			$test = $this->DB->buildAndFetch( array( 'select' => 'sys_module_id',
																	  'from'   => 'core_sys_module',
																	  'where'  => "sys_module_key='".$sys_module_key."' AND sys_module_application='".$application['app_directory']."' AND sys_module_admin=".$sys_module_admin ) );
																	
			if ( $test['sys_module_id'] )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['a_already'], $system_module_key );
				$this->modules_overview();
				return;
			}
			
		}
		
		//-----------------------------------------
		// Form checks...
		//-----------------------------------------
		
		if ( ! $sys_module_title OR ! $sys_module_key )
		{
			$this->registry->output->global_message = $this->lang->words['a_titlekey'];
			$this->moduleForm( $type );
			return;
		}
		
		//--------------------------------------------
		// Check...
		//--------------------------------------------
		
		$array = array( 'sys_module_title'       => $sys_module_title,
						'sys_module_application' => $application['app_directory'],
						'sys_module_key'	     => $sys_module_key,
						'sys_module_description' => $sys_module_description,
						'sys_module_version'	 => $sys_module_version,
						'sys_module_parent'      => $sys_module_parent,
						'sys_module_visible'	 => $sys_module_visible,
						'sys_module_admin'       => $sys_module_admin );
		
		//-----------------------------------------
		// IN DEV?
		//-----------------------------------------
		
		if ( IN_DEV )
		{
			$array['sys_module_protected'] = $sys_module_protected;
		}
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$max	= $this->DB->buildAndFetch( array( 'select' => 'MAX(sys_module_position) as position', 'from' => 'core_sys_module' ) );
			
			$array['sys_module_position']	= $max['position'] + 1;
			
			$this->DB->insert( 'core_sys_module', $array );
			$this->registry->output->global_message = $this->lang->words['a_added'];
		}
		else
		{
			$this->DB->update( 'core_sys_module', $array, 'sys_module_id='.$sys_module_id );
			$this->registry->output->global_message = $this->lang->words['a_edited'];
		}
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->moduleRecache();
		
		//-----------------------------------------
		// List...
		//-----------------------------------------
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id='.$app_id.'&amp;sys_module_admin='.$sys_module_admin );
	}
	
	/**
	 * Add/Edit module form
	 *
	 * @access	private
	 * @param	string		Type [add|edit]
	 * @return	void		[Outputs to screen]
	 */
	private function moduleForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sys_module_admin = intval( $this->request['sys_module_admin'] );
		$sys_module_id    = intval( $this->request['sys_module_id'] );
		$app_id		      = intval( $this->request['app_id'] );
		$module           = array( 'sys_module_admin' => $sys_module_admin );
		$modules          = array( 'root' => array() );
		$application      = array();
		$form             = array();
		$apps		      = array( 0 => array( 0, $this->lang->words['a_noparent'] ) );
		$module_type      = array( 0 => array( 0, $this->lang->words['a_public'] ), 1 => array( 1, $this->lang->words['a_admin'] ) );
		
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select' => '*',
																		 'from'   => 'core_applications',
																		 'where'  => 'app_id='.$app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Get root modules...
		//-----------------------------------------
		
		ipsRegistry::$modules_by_section [ $application['app_directory'] ] = is_array( ipsRegistry::$modules_by_section [ $application['app_directory'] ] ) ? ipsRegistry::$modules_by_section [ $application['app_directory'] ] : array();
		
		foreach( ipsRegistry::$modules_by_section [ $application['app_directory'] ] as $key => $data )
		{
			if ( ! $data['sys_module_parent'] AND $data['sys_module_admin'] == $sys_module_admin AND ( $sys_module_id AND $sys_module_id != $data['sys_module_id'] ) )
			{
				$apps[] = array( $data['sys_module_key'], $data['sys_module_title'] );
			}
		}
		
		//-----------------------------------------
		// Add or edit?
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'module_add_do';
			$title    = "Add New Module";
			$button   = "Add New Module";
		}
		else
		{
			$module = $this->DB->buildAndFetch( array( 'select' => '*',
																		'from'   => 'core_sys_module',
																		'where'  => 'sys_module_id='.$sys_module_id ) );

			if ( ! $module['sys_module_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->applicationsOverview();
				return;
			}
			
			$sys_module_admin = $module['sys_module_admin'];
			$formcode         = 'module_edit_do';
			$title            = $this->lang->words['a_editmod'] . $module['sys_module_title'];
			$button           = $this->lang->words['a_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['sys_module_title']       = $this->registry->output->formInput(  'sys_module_title'      , $_POST['sys_module_title']         ? $_POST['sys_module_title']       : $module['sys_module_title'] );
		$form['sys_module_description'] = $this->registry->output->formInput(  'sys_module_description', $_POST['sys_module_description']   ? $_POST['sys_module_description'] : $module['sys_module_description'] );
		$form['sys_module_key']         = $this->registry->output->formInput(  'sys_module_key'        , $_POST['sys_module_key']           ? $_POST['sys_module_key']         : $module['sys_module_key'] );
		$form['sys_module_version']     = $this->registry->output->formInput(  'sys_module_version'    , $_POST['sys_module_version']       ? $_POST['sys_module_version']     : $module['sys_module_version'] );
		$form['sys_module_parent']      = $this->registry->output->formDropdown(  'sys_module_parent'  , $apps, $_POST['sys_module_parent'] ? $_POST['sys_module_parent']      : $module['sys_module_parent'] );
		$form['sys_module_protected']   = $this->registry->output->formYesNo( 'sys_module_protected'  , $_POST['sys_module_protected']     ? $_POST['sys_module_protected']   : $module['sys_module_protected'] );
		$form['sys_module_visible']     = $this->registry->output->formYesNo( 'sys_module_visible'    , $_POST['sys_module_visible']       ? $_POST['sys_module_visible']     : $module['sys_module_visible'] );
		$form['sys_module_admin']      = $this->registry->output->formDropdown(  'sys_module_admin'    , $module_type, $_POST['sys_module_admin'] ? $_POST['sys_module_admin']        : $module['sys_module_admin'] );
		
		//-----------------------------------------
		// Nav
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview', $application['app_title'] );
		
		$this->registry->output->html .= $this->html->module_form( $form, $title, $formcode, $button, $module, $application );
	}
	
	/**
	 * Move a module up/down
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function moduleManagePosition()
	{
		$app_id           = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax			= new classAjax();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( !$app_id )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
		
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['modules']) AND count($this->request['modules']) )
 		{
 			foreach( $this->request['modules'] as $this_id )
 			{
 				$this->DB->update( 'core_sys_module', array( 'sys_module_position' => $position ), 'sys_module_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->moduleRecache();

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Recache modules
	 *
	 * @access	public
	 * @return	void
	 */
	public function moduleRecache()
	{
		$modules = array();
		
		//-----------------------------------------
		// Load known modules
		//-----------------------------------------

		$this->DB->build( array( 'select'		=> 'm.*',
										'from'		=> array( 'core_sys_module' => 'm' ),
										'where'		=> 'm.sys_module_visible=1',
										'order'		=> 'a.app_position, m.sys_module_position ASC',
										'add_join'	=> array( 0 => array( 'select'	=> 'a.*',
												 							'from'	=> array( 'core_applications' => 'a' ),
																			'where'	=> 'm.sys_module_application=a.app_directory',
																			'type'	=> 'inner' ) ) ) );

		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$_row = array();
			
			foreach( $row as $k => $v )
			{
				if ( strpos( $k, "sys_" ) === 0 )
				{
					$_row[ $k ] = $v;
				}
			}
			
			$modules[ $row['sys_module_application'] ][] = $_row;
		}

		$this->cache->setCache( 'module_cache', $modules, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Recache apps, modules and menus
	 *
	 * @access	public
	 * @return	void		[Outputs to screen]
	 */
	public function moduleRecacheAll( $return=0 )
	{
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		$this->moduleRecache();
		
		if( ! $return )
		{
			$this->registry->output->global_message = $this->lang->words['a_recachecomplete'];
		
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
		}
	}
	
	/**
	 * View al module
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function modules_overview()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sys_module_admin = intval( $this->request['sys_module_admin'] );
		$app_id		      = intval( $this->request['app_id'] );
		$application      = array();
		$modules          = array();
		$_modules         = array();
		$_parents         = array( '_root' => array() );
		$seen_count       = 0;
		$total_items      = array( '_root' => 0 );
		$_modules_admin   = ( $sys_module_admin ) ? 'modules_admin' : 'modules_public';
		
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select' => '*',
																		 'from'   => 'core_applications',
																		 'where'  => 'app_id='.$app_id ) );
																		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Get modules
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
													  'from'   => 'core_sys_module',
													  'where'  => "sys_module_application='".$application['app_directory']."' AND sys_module_admin=".$sys_module_admin,
													  'order'  => 'sys_module_position ASC' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Enabled?
			//-----------------------------------------
			
			$row['_sys_module_visible'] = ( $row['sys_module_visible'] ) ? 'tick.png' : 'cross.png';

			//-----------------------------------------
			// Exists...
			//-----------------------------------------
			
			if ( ! file_exists( IPSLib::getAppDir(  $application['app_directory'] ) . '/'. $_modules_admin . '/'. $row['sys_module_key'] ) )
			{
				$row['_missing'] = 1;
			}
			
			//-----------------------------------------
			// Add to row
			//-----------------------------------------
			
			$_modules[ $row['sys_module_id'] ] = $row;
			
			if ( $row['sys_module_parent'] )
			{
				$_parents[ $row['sys_module_parent'] ][ $row['sys_module_id'] ] = $row['sys_module_id'];
				$total_items[ $row['sys_module_parent'] ]++;
			}
			else
			{
				$_parents[ '_root' ][ $row['sys_module_id'] ] = $row['sys_module_id'];
				$total_items['_root']++;
			}
		}
		
		//-----------------------------------------
		// Set up..
		//-----------------------------------------
		
		$total_items = count( $_parents[ '_root' ] );
		
		//-----------------------------------------
		// Loop...
		//-----------------------------------------
		
		foreach( $_parents[ '_root' ] as $_sys_module_id => $sys_module_id )
		{
			$row = $_modules[ $_sys_module_id ];
			
			$seen_count++;
			
			//-----------------------------------------
			// Add to row
			//-----------------------------------------
			
			$modules['root'][] = $row;
			
			//-----------------------------------------
			// Got any children?
			//-----------------------------------------
			
			if ( is_array( $_parents[ $row['sys_module_key'] ] ) AND count( $_parents[ $row['sys_module_key'] ] ) )
			{
				$_seen_count  = 0;
				$_total_items = count( $_parents[ $row['sys_module_key'] ] );
				
				//-----------------------------------------
				// Loop
				//-----------------------------------------
				
				foreach( $_parents[ $row['sys_module_key'] ] as $___sys_module_id => $__sys_module_id )
				{
					$child_row = $_modules[ $___sys_module_id ];
					
					$_seen_count++;
					
					$modules[ $row['sys_module_key'] ][] = $child_row;
				}
			}
		}

		//-----------------------------------------
		// List 'em
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->modules_list( $modules, $application, $sys_module_admin );
	}
	
	/**
	 * Remove an application (confirm screen)
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function applicationRemoveSplash()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$app_id = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id='.$app_id ) );
		
		$this->registry->output->html .= $this->html->application_remove_splash( $application );	
	}
	
	/**
	 * Remove an application
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function applicationRemove()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$app_id = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id='.$app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Protected?
		//-----------------------------------------
		
		if ( ! IN_DEV and $application['app_protected'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_protectapp'];
			$this->applicationsOverview();
			return;
		}
				
		//-----------------------------------------
		// Remove Settings
		//-----------------------------------------				
		
		$this->DB->build( array( 'select' => '*', 'from'  => 'core_sys_settings_titles', 'where' => "conf_title_app='{$application['app_directory']}'" ) );
		$this->DB->execute();
		
		$conf_title_id = array();
		while( $r = $this->DB->fetch() )
		{
			$conf_title_id[] = $r['conf_title_id'];
		}
		
		if( count( $conf_title_id ) )
		{
			$this->DB->delete( 'core_sys_conf_settings', 'conf_group IN(' . implode( ',', $conf_title_id ) . ')' );
		}
		
		$this->DB->delete( 'core_sys_settings_titles', "conf_title_app='{$application['app_directory']}'" );

		//-----------------------------------------
		// Remove Application Caches
		//-----------------------------------------		
		
		$_file = IPSLib::getAppDir( $application['app_directory'] ).'/extensions/coreVariables.php';
		
		if ( file_exists( $_file ) )
		{
			require( $_file );
			
			if ( is_array( $CACHE ) AND count( $CACHE ) )
			{
				foreach( $CACHE as $key => $data )
				{
					$this->DB->delete( 'cache_store', "cs_key='{$key}'" );
				}
			}
		}
		
		//-----------------------------------------
		// Remove tables
		//-----------------------------------------

		$_file = IPSLib::getAppDir( $application['app_directory'] ) . '/setup/versions/install/sql/' . $application['app_directory'] . '_' . ipsRegistry::dbFunctions()->getDriverType() . '_tables.php';

		if( file_exists( $_file ) )
		{
			require( $_file );

			foreach( $TABLE as $q )
			{
				//-----------------------------------------
				// Capture create tables first
				//-----------------------------------------
				
				preg_match( "/CREATE TABLE (\S+)(\s)?\(/", $q, $match );
				
				if( $match[1] )
				{
					$this->DB->dropTable( preg_replace( '#^' . ipsRegistry::dbFunctions()->getPrefix() . "(\S+)#", "\\1", $match[1] ) );
				}
				else
				{
					//-----------------------------------------
					// Then capture alter tables
					//-----------------------------------------
					
					preg_match( "/ALTER TABLE (\S+)\sADD\s(\S+)\s/i", $q, $match );
					
					if( $match[1] AND $match[2] )
					{
						$this->DB->dropField( preg_replace( '#^' . ipsRegistry::dbFunctions()->getPrefix() . "(\S+)#", "\\1", $match[1] ), $match[2] );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check for uninstall sql
		//-----------------------------------------

		/* Any "extra" configs required for this driver? */
		if( file_exists( IPS_ROOT_PATH . 'setup/sql/' . $this->settings['sql_driver'] . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . $this->settings['sql_driver'] . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}
		
		$_file = IPSLib::getAppDir(  $application['app_directory'] ) . '/setup/versions/install/sql/' . $application['app_directory'] . '_' . ipsRegistry::dbFunctions()->getDriverType() . '_uninstall.php';
		
		if( file_exists( $_file ) )
		{
			require( $_file );
			
			if ( is_array( $QUERY ) AND count( $QUERY ) )
			{			
				foreach( $QUERY as $q )
				{
					if ( $extra_install AND method_exists( $extra_install, 'process_query_create' ) )
					{
						 $q = $extra_install->process_query_create( $q );
					}
					
					$this->DB->query( $q );
				}
			}
		}				
		
		//-----------------------------------------
		// Remove Misc Stuff
		//-----------------------------------------		
		
		$this->DB->delete( 'core_sys_lang_words'	, "word_app='{$application['app_directory']}'" );
		$this->DB->delete( 'task_manager'			, "task_application='{$application['app_directory']}'" );
		$this->DB->delete( 'permission_index'		, "app='{$application['app_directory']}'" );
		$this->DB->delete( 'reputation_index'		, "app='{$application['app_directory']}'" );
		$this->DB->delete( 'tags_index'				, "app='{$application['app_directory']}'" );
		$this->DB->delete( 'faq'					, "app='{$application['app_directory']}'" );
		$this->DB->delete( 'custom_bbcode'			, "bbcode_app='{$application['app_directory']}'" );
		
		if( $this->DB->checkForTable( 'search_index' ) )
		{
			$this->DB->delete( 'search_index'		, "app='{$application['app_directory']}'" );
		}
		
		//-----------------------------------------
		// Get all hook files
		//-----------------------------------------
		
		if( is_dir( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/hooks' ) )
		{
			$files	= scandir( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/hooks' );
			$hooks	= array();
			
			require_once( IPS_KERNEL_PATH . 'classXML.php' );
			$xml    = new classXML( IPS_DOC_CHAR_SET );
			
			if( count($files) AND is_array($files) )
			{
				foreach( $files as $_hookFile )
				{
					if( $_hookFile != '.' AND $_hookFile != '..' AND preg_match( "/(\.xml)$/", $_hookFile ) )
					{
						$xml->loadXML( file_get_contents( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/hooks/' . $_hookFile ) );
				
						foreach( $xml->fetchElements('config') as $data )
						{
							$config	= $xml->fetchElementsFromRecord( $data );
				
							if( !count($config) )
							{
								continue;
							}
							else
							{
								$hooks[]	= $config['hook_key'];
							}
						}
					}
				}
			}
			
			if( count($hooks) )
			{
				foreach( $hooks as $hook )
				{
					$hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => "hook_key='" . $hook . "'" ) );
					
					if( ! $hook['hook_id'] )
					{
						continue;
					}
					
					$this->DB->delete( 'core_hooks', "hook_id={$hook['hook_id']}" );
					
					/* Get associated files */
					$this->DB->build( array( 'select' => 'hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $hook['hook_id'] ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
					}
					
					/* Delete hook file entries */
					$this->DB->delete( 'core_hooks_files', "hook_hook_id={$hook['hook_id']}" );
				}
				
				$this->cache->rebuildCache( 'hooks', 'global' );
			}
		}

		//-----------------------------------------
		// Remove Modules
		//-----------------------------------------		
		
		$this->DB->delete( 'core_sys_module', "sys_module_application='{$application['app_directory']}'" );
		
		//-----------------------------------------
		// Remove Application
		//-----------------------------------------
		
		$this->DB->delete( 'core_applications', 'app_id='.$app_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->moduleRecacheAll(1);
		
		$this->cache->rebuildCache( 'settings', 'global' );
		
		//-----------------------------------------
		// Remove Files
		//-----------------------------------------
		
		/* Languages */
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/lang_cache/' ) as $dir )
			{
				if( ! $dir->isDot() && intval( $dir->getFileName() ) )
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/lang_cache/' . $dir->getFileName() . '/' ) as $file )
					{
						if( ! $file->isDot() )
						{
							if( preg_match( "/^({$application['app_directory']}_)/", $file->getFileName() ) )
							{
								unlink( $file->getPathName() );
							}
						}
					}
				}
			}
		} catch ( Exception $e ) {}
		
		/* Remove Skins */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml = new classXML( $this->settings['gb_char_set'] );
		$xml->load( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/information.xml' );
		
		if ( is_array( $xml->fetchElements( 'template' ) ) )
		{
			foreach( $xml->fetchElements( 'template' ) as $template )
			{
				$name  = $xml->fetchItem( $template );
				$match = $xml->fetchAttribute( $template, 'match' );
		
				if ( $name )
				{
					$templateGroups[ $name ] = $match;
				}
			}
		}
		
		if( is_array($templateGroups) AND count($templateGroups) )
		{
			/* Loop through skin directories */
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/skin_cache/' ) as $dir )
				{
					if( preg_match( "/^(cacheid_)/", $dir->getFileName() ) )
					{
						foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/skin_cache/' . $dir->getFileName() . '/' ) as $file )
						{
							if( ! $file->isDot() )
							{
								foreach( $templateGroups as $name => $match )
								{
									if( $match == 'contains' )
									{
										if( stristr( $file->getFileName(), $name ) )
										{
											unlink( $file->getPathName() );
										}
									}
									else if( $file->getFileName() == $name . '.php' )
									{
										unlink( $file->getPathName() );
									}
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {}
			
			/* Delete from database */
			foreach( $templateGroups as $name => $match )
			{
				if( $match == 'contains' )
				{
					$this->DB->delete( 'skin_templates', "template_group LIKE '%{$name}%'" );
				}
				else 
				{
					$this->DB->delete( 'skin_templates', "template_group='{$name}'" );
				}
			}
		}
		
		/* CSS files */
		$css_files	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'skin_css', 'where' => "css_app='" . $application['app_directory'] . "'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$css_files[ $r['css_group'] ]	= $r['css_group'];
		}
		
		if( count($css_files) )
		{
			$this->DB->delete( 'skin_css', "css_app='" . $application['app_directory'] . "'" );
			$this->DB->delete( 'skin_cache', "cache_type='css' AND cache_value_1 IN('" . implode( "','", $css_files ) . "')" );
			
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_css/' ) as $dir )
				{
					if( preg_match( "/^(css_)/", $dir->getFileName() ) )
					{
						foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_css/' . $dir->getFileName() . '/' ) as $file )
						{
							if( ! $file->isDot() )
							{
								foreach( $css_files as $css_file )
								{
									if( $file->getFileName() == $css_file . '.css' )
									{
										unlink( $file->getPathName() );
									}
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {}
		}
		
		/* Delete from upgrade */
		$this->DB->delete( 'upgrade_history', "upgrade_app='{$application['app_directory']}'" );
		
		//-----------------------------------------
		// Sphinx involved?
		//-----------------------------------------
		
		if( $this->settings['search_method'] == 'sphinx' )
		{
			$this->registry->output->global_message .= sprintf( $this->lang->words['rebuild_sphinx'], $this->settings['_base_url'] );
		}

		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['a_appremoved'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Import an application
	 *
	 * @access	private
	 * @param	integer		IN_DEV override
	 * @return	void		[Outputs to screen]
	 */
	private function applicationImport( $in_dev=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$updated     = 0;
		$inserted    = 0;
		$apps		 = array();
		
		//-----------------------------------------
		// INDEV?
		//-----------------------------------------
		
		if ( $in_dev )
		{
			$_FILES['FILE_UPLOAD']['name']          = '';
			$this->request['file_location'] =  IPS_ROOT_PATH . 'setup/xml/applications.xml';
		}
		else
		{
			$this->request[ 'file_location'] =  IPS_ROOT_PATH . $this->request['file_location'] ;
		}
		
		//-----------------------------------------
		// Uploaded file?
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->request['file_location'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_nofile'];
				$this->setting_start();
			}
			
			if ( ! file_exists( $this->request['file_location'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['a_file404'] . $this->request['file_location'];
				$this->setting_start();
			}
			
			if ( preg_match( "#\.gz$#", $this->request['file_location'] ) )
			{
				if ( $FH = @gzopen( $this->request['file_location'], 'rb' ) )
				{
					while ( ! @gzeof( $FH ) )
					{
						$content .= @gzread( $FH, 1024 );
					}
					
					@gzclose( $FH );
				}
			}
			else
			{
				if ( $FH = @fopen( $this->request['file_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize($this->request['file_location']) );
					@fclose( $FH );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content  = ipsRegistry::getClass('adminFunctions')->importXml( $tmp_name );
		}
		
		//-----------------------------------------
		// Module import?
		//-----------------------------------------
		
		if ( preg_match( "#type=\"application:([^\"]+?)\"#", $content ) )
		{
			$this->moduleImport( $content, $in_dev );
		}
		
		//-----------------------------------------
		// Get current applications.
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'app_id, app_directory',
											     'from'   => 'core_applications',
												 'order'  => 'app_id' ) );
		
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$apps[ $r['app_directory'] ] = $r['app_id'];
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		$fields = array( 'app_title'    , 'app_public_title', 'app_description', 'app_author', 'app_version', 'app_directory', 'app_added', 'app_position',
						 'app_protected', 'app_enabled', 'app_location', 'app_hide_tab' );

		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------

		foreach( $xml->fetchElements('application') as $record )
		{
			$entry  = $xml->fetchElementsFromRecord( $record );
			
			//-----------------------------------------
			// INIT
			//-----------------------------------------
			
			$newrow = array();
			
			//-----------------------------------------
			// Add in known elements
			//-----------------------------------------
			
			foreach( $fields as $f )
			{
				$newrow[ $f ] = $entry[ $f ];
			}

			//-----------------------------------------
			// Insert or update?
			//-----------------------------------------
			
			if ( $apps[ $entry['app_directory'] ] )
			{
				//-----------------------------------------
				// Update
				//-----------------------------------------
				
				$updated++;
				$this->DB->update( 'core_applications', $newrow, "app_directory='" . $entry['app_directory'] . "'" );
				
			}
			else
			{
				//-----------------------------------------
				// Insert
				//-----------------------------------------
				
				$inserted++;
				$this->DB->insert( 'core_applications', $newrow );
			}
		}

		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->output->global_message = sprintf( $this->lang->words['a_insertedupdated'], $inserted, $updated );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Export an application
	 *
	 * @access	private
	 * @param	integer		Return XML [1] or print to browser [0]
	 * @return	mixed		XML content or void [Outputs to screen]
	 */
	private function applicationExport( $return_xml=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$xml = '';
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'applicationexport' );
		$xml->addElement( 'applicationgroup', 'applicationexport' );

		//-----------------------------------------
		// Get applications
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*',
									'from'	=> 'core_applications'  ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			unset( $r['app_id'] );
			
			$xml->addElementAsRecord( 'applicationgroup', 'application', $r );
		}

		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		if( $return_xml )
		{
			return array( 'title' => 'applications.xml', 'xml' => $xml->fetchDocument() );	
		}
		else 
		{
			$this->registry->output->showDownload( $xml->fetchDocument(), 'applications.xml', '', 0 );
		}
	}
	
	/**
	 * Save an application
	 *
	 * @access	private
	 * @param	string		Type [add|edit]
	 * @return	void		[Outputs to screen]
	 */
	private function applicationSave( $type='add' )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$app_id           = intval( $this->request['app_id'] );
		$app_title	  	  = trim( $this->request['app_title'] );
		$app_public_title = trim( $this->request['app_public_title'] );
		$app_description  = trim( $this->request['app_description'] );
		$app_author		  = trim( $this->request['app_author'] );
		$app_version      = trim( $this->request['app_version'] );
		$app_directory	  = trim( $this->request['app_directory'] );
		$app_protected    = intval( $this->request['app_protected'] );
		$app_enabled      = intval( $this->request['app_enabled'] );
		$app_hide_tab     = intval( $this->request['app_hide_tab'] );
		$application      = array();
		
		//--------------------------------------------
		// Check
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
			
			if ( ! $application['app_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->applicationsOverview();
				return;
			}
		}
		
		if ( ! $app_title OR ! $app_directory )
		{
			$this->registry->output->global_message = $this->lang->words['a_titledirectory'];
			$this->applicationForm( $type );
			return;
		}
		
		//--------------------------------------------
		// Check...
		//--------------------------------------------
		
		$array = array( 'app_title'			=> $app_title,
					    'app_public_title'	=> $app_public_title,
						'app_description'	=> $app_description,
						'app_author'		=> $app_author,
						'app_version'		=> $app_version,
						'app_directory'		=> $app_directory,
						'app_enabled'		=> $app_enabled,
						'app_hide_tab'		=> $app_hide_tab );
		
		//-----------------------------------------
		// IN DEV?
		//-----------------------------------------
		
		if ( IN_DEV )
		{
			$array['app_protected'] = $app_protected;
		}
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$array['app_added']		= time();
			$array['app_location']	= 'other';
			
			$max	= $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as position', 'from' => 'core_applications' ) );
			
			$array['app_position']	= $max['position'] + 1;
			
			$this->DB->insert( 'core_applications', $array );
			$this->registry->output->global_message = $this->lang->words['a_newapp'];
		}
		else
		{
			/* Update the application record */
			$this->DB->update( 'core_applications', $array, 'app_id='.$app_id );
			
			/* Update modules, if the application directory changed */
			if( $application['app_directory'] != $app_directory )
			{
				$this->DB->update( 'core_sys_module', array( 'sys_module_application' => $app_directory ), "sys_module_application='{$application['app_directory']}'" );
			}
			
			/* Set the message */
			$this->registry->output->global_message = $this->lang->words['a_editappdone'];
		}
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		
		//-----------------------------------------
		// Sphinx involved?
		//-----------------------------------------
		
		if( $this->settings['search_method'] == 'sphinx' )
		{
			$this->registry->output->global_message .= sprintf( $this->lang->words['rebuild_sphinx'], $this->settings['_base_url'] );
		}
		
		//-----------------------------------------
		// List...
		//-----------------------------------------
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Add/edit application form
	 *
	 * @access	private
	 * @param	string		Type [add|edit]
	 * @return	void		[Outputs to screen]
	 */
	private function applicationForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$app_id      = intval( $this->request['app_id'] );
		$application = array();
		$form        = array();
		
		//-----------------------------------------
		// Add or edit?
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'application_add_do';
			$title    = $this->lang->words['a_addnewapp'];
			$button   = $this->lang->words['a_addnewapp'];
		}
		else
		{
			$application = $this->DB->buildAndFetch( array( 'select' => '*',
																			 'from'   => 'core_applications',
																			 'where'  => 'app_id='.$app_id ) );
			
			if ( ! $application['app_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->applicationsOverview();
				return;
			}
			
			$formcode = 'application_edit_do';
			$title    = $this->lang->words['a_editapp'] . $application['app_title'];
			$button   = $this->lang->words['a_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['app_title']        = $this->registry->output->formInput(  'app_title'      , $_POST['app_title']       ? $_POST['app_title']       : $application['app_title'] );
		$form['app_public_title'] = $this->registry->output->formInput(  'app_public_title', $_POST['app_public_title'] ? $_POST['app_public_title'] : $application['app_public_title'] );
		$form['app_description']  = $this->registry->output->formInput(  'app_description', $_POST['app_description'] ? $_POST['app_description'] : $application['app_description'] );
		$form['app_author']       = $this->registry->output->formInput(  'app_author'     , $_POST['app_author']      ? $_POST['app_author']      : $application['app_author'] );
		$form['app_version']      = $this->registry->output->formInput(  'app_version'    , $_POST['app_version']     ? $_POST['app_version']     : $application['app_version'] );
		$form['app_directory']    = $this->registry->output->formInput(  'app_directory'  , $_POST['app_directory']   ? $_POST['app_directory']   : $application['app_directory'] );
		$form['app_protected']    = $this->registry->output->formYesNo( 'app_protected'  , $_POST['app_protected']   ? $_POST['app_protected']   : $application['app_protected'] );
		$form['app_enabled']      = $this->registry->output->formYesNo( 'app_enabled'    , $_POST['app_enabled']     ? $_POST['app_enabled']     : $application['app_enabled'] );
		$form['app_hide_tab']     = $this->registry->output->formYesNo( 'app_hide_tab'    , $_POST['app_hide_tab']     ? $_POST['app_hide_tab']     : $application['app_hide_tab'] );
		
		$this->registry->output->html .= $this->html->application_form( $form, $title, $formcode, $button, $application );
	}
	
	/**
	 * Move an application up/down
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function applicationManagePosition()
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
 		
 		if( is_array($this->request['apps']) AND count($this->request['apps']) )
 		{
 			foreach( $this->request['apps'] as $this_id )
 			{
 				$this->DB->update( 'core_applications', array( 'app_position' => $position ), 'app_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * List applications
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function applicationsOverview()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$folders     = array();
		$application = array();
		$_apps       = array();
		$seen_count  = 0;
		$total_items = 0;
		$uninstalled = array();
		
		/* Get the setup class */
		require IPS_ROOT_PATH . "setup/sources/base/setup.php";
		
		//-----------------------------------------
		// Get DB applications
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_applications',
								 'order'  => 'app_position' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$_apps[ IPSLib::getAppFolder( $row['app_directory'] ) . '/' . $row['app_directory'] ] = $row;
			$total_items++;
		}
		
		//-----------------------------------------
		// Get folder applications...
		//-----------------------------------------
		
		foreach( array( 'applications', 'applications_addon/ips', 'applications_addon/other' ) as $folder )
		{
			try
			{
				foreach( new DirectoryIterator( IPS_ROOT_PATH . $folder ) as $file )
				{
					if ( ! $file->isDot() AND $file->isDir() )
					{
						$_name = $file->getFileName();
						
						if ( substr( $_name, 0, 1 ) != '.' )
						{
							$folders[ $folder . '/' . $_name ] = $_name;
						}
					}
				}
			} catch ( Exception $e ) {}
		}
		
		//-----------------------------------------
		// Installed Loop...
		//-----------------------------------------
		
		foreach( $_apps as $_app_path => $row )
		{
			$app_dir = $row['app_directory'];
			
			//-----------------------------------------
			// Enabled?
			//-----------------------------------------
			
			$row['_app_enabled'] = ( $row['app_enabled'] ) ? 'tick.png' : 'cross.png';
			
			/* Version numbers */
			$_a = ( $app_dir == 'forums' OR $app_dir == 'members' ) ? 'core' : $app_dir;
			$numbers  = IPSSetUp::fetchAppVersionNumbers( $_a );
				
			$row['_human_version'] = $numbers['latest'][1];
			$row['_long_version']  = $numbers['latest'][0];
			
			$row['_human_current'] = $numbers['current'][1];
			$row['_long_current']  = $numbers['current'][0];
			
			/* Exists? */
			if ( ! file_exists( IPSLib::getAppDir( $app_dir ) ) )
			{
				$row['_missing'] = 1;
			}
			
			$seen_count++;
			
			$application[$row['app_location']][] = $row;
		}
		
		/* Make sure they are in the proper order, hacky but it works :) */
		$__apps = array();
		
		$__apps['root']  = $application['root']  ? $application['root']  : array();
		$__apps['ips']   = $application['ips']   ? $application['ips']   : array();
		$__apps['other'] = $application['other'] ? $application['other'] : array();
		
		$application = $__apps;
	
		//-----------------------------------------
		// Uninstalled
		//-----------------------------------------

		foreach( $folders as $filepath => $_file )
		{
			if ( ! in_array( $filepath, array_keys( $_apps ) ) )
			{
				$info = IPSSetUp::fetchXmlAppInformation( $_file );
				
				/* OK, we're making no effort to conceal the secret behind the ipskey. It's an honourable setting - do not abuse it.
				   We only mildly obfuscate it to stop copy and paste mistakes in information.xml
				*/
				$okToGo = 0;
				
				if ( strstr( $filepath, 'applications_addon/ips' ) or strstr( $filepath, 'applications/' ) )
				{
					if ( md5( 'ips_' . $_file ) == $info['ipskey'] )
					{
						$okToGo = 1;
					}
				}
				else if ( strstr( $filepath, 'applications_addon/other' ) )
				{
					if ( ! $info['ipskey'] )
					{
						$okToGo = 1;
					}
				}
				
				$uninstalled[ $_file ] = array( 'title'     => $info['name'],
											    'author'    => $info['author'],
											    'path'      => $filepath,
											    'okToGo'    => $okToGo,
											    'directory' => $_file );
			}
		}
	
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->applications_list( $application, $uninstalled );
	}
	
	/**
	 * Recache applications
	 *
	 * @access	public
	 * @return	void
	 */
	public function applicationsRecache()
	{
	 	$apps = array();
		
		$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'core_applications',
										'order'	=> 'app_position ASC' ) );
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$apps[ $row['app_directory'] ] = $row;
		}
		
		$this->cache->setCache( 'app_cache', $apps, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Recache main version number
	 *
	 * @access	public
	 * @return	void
	 */
	public function versionNumbersRecache()
	{
	 	$numbers = IPSLib::fetchVersionNumber();
		
		$this->cache->setCache( 'vnums', $numbers, array( 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );
	}
	
	/**
	 * Recache menu data
	 *
	 * @access	public
	 * @return	void
	 */
	public function applicationsMenuDataRecache()
	{ 
		$app_menu_cache = array();

		$this->DB->build( array( 
										'select' => '*',
										'from'	 => 'core_applications',
										'order'	 => 'app_position ASC' ) );
		$outer = $this->DB->execute();


		while( $row = $this->DB->fetch( $outer ) )
		{
			$app_dir	= $row['app_directory'];
			$main_items	= array();
			
			$this->DB->build( array( 'select'	=> '*',
											'from'	=> 'core_sys_module',
											'where'	=> "sys_module_application='" . $app_dir . "'",
											'order'	=> 'sys_module_position ASC' ) );
			$inner = $this->DB->execute();
			
			while( $module = $this->DB->fetch( $inner ) )
			{
				$main_items[] = $module['sys_module_key'];
			}
			
			//-----------------------------------------
			// Continue...
			//-----------------------------------------
			
			foreach( $main_items as $_current_module )
			{
				$_file = IPSLib::getAppDir( $app_dir ) . "/modules_admin/" . $_current_module . '/xml/menu.xml';

				if ( file_exists( $_file ) )				
				{
					//-----------------------------------------
					// Get xml mah-do-dah
					//-----------------------------------------
					
					require_once( IPS_KERNEL_PATH.'classXML.php' );
					$xml = new classXML( IPS_DOC_CHAR_SET );
			
					$content = @file_get_contents( $_file );					

					if ( $content )
					{
						$xml->loadXML( $content );
						$menu			= $xml->fetchXMLAsArray();
						$item			= array();
						$subItemIndex	= 0;
						$itemIndex		= 0;

						/**
						 * Easiest way I could find to get the data in a proper multi-dimensional array
						 */
						foreach( $menu as $id => $data )
						{
							foreach( $data as $dataKey => $dataValue )
							{
								if( $dataKey == 'tabitems' )
								{
									foreach( $dataValue as $tabitemsKey => $tabItemsValue )
									{
										if( $tabitemsKey == 'item' )
										{
											foreach( $tabItemsValue as $itemKey => $itemValue )
											{
												if( is_int($itemKey) )
												{
													foreach( $itemValue as $_itemKey => $_itemValue )
													{
														$subItemIndex	= 0;
														
														if( $_itemKey == 'title' )
														{
															$item[ $itemIndex ][ $_itemKey ]	= $_itemValue['#alltext'];
														}
														else if( $_itemKey == 'subitems' )
														{
															foreach( $_itemValue as $subitemKey => $subitemValue )
															{
																if( $subitemKey != '#alltext' )
																{
																	foreach( $subitemValue as $subitemRealKey => $subitemRealValue )
																	{
																		if( is_int( $subitemRealKey ) )
																		{
																			foreach( $subitemRealValue as $_subitemRealKey => $_subitemRealValue )
																			{
																				if( $_subitemRealKey != '#alltext' )
																				{
																					$item[ $itemIndex ][ $_itemKey ][ $subitemKey ][ $subItemIndex ][ $_subitemRealKey ]	= $_subitemRealValue['#alltext'];
																				}
																			}
																		}
																		else if( $subitemRealKey != '#alltext' )
																		{
																			$item[ $itemIndex ][ $_itemKey ][ $subitemKey ][ $subItemIndex ][ $subitemRealKey ]	= $subitemRealValue['#alltext'];
																		}
																		
																		if( is_int( $subitemRealKey ) )
																		{
																			$subItemIndex++;
																		}
																	}
																	
																	$subItemIndex++;
																}
															}
														}														
													}
													
													$itemIndex++;
												}
												else if( $itemKey == 'title' )
												{
													$item[ $itemIndex ][ $itemKey ]	= $itemValue['#alltext'];
												}
												else if( $itemKey == 'subitems' )
												{
													foreach( $itemValue as $subitemKey => $subitemValue )
													{
														if( $subitemKey != '#alltext' )
														{
															foreach( $subitemValue as $subitemRealKey => $subitemRealValue )
															{
																if( is_int( $subitemRealKey ) )
																{
																	foreach( $subitemRealValue as $_subitemRealKey => $_subitemRealValue )
																	{
																		if( $_subitemRealKey != '#alltext' )
																		{
																			$item[ $itemIndex ][ $itemKey ][ $subitemKey ][ $subItemIndex ][ $_subitemRealKey ]	= $_subitemRealValue['#alltext'];
																		}
																	}
																}
																else if( $subitemRealKey != '#alltext' )
																{
																	$item[ $itemIndex ][ $itemKey ][ $subitemKey ][ $subItemIndex ][ $subitemRealKey ]	= $subitemRealValue['#alltext'];
																}
																
																if( is_int( $subitemRealKey ) )
																{
																	$subItemIndex++;
																}
															}
															
															$subItemIndex++;
														}
													}
												}
											}
											
											$itemIndex++;
										}
									}
								}
							}
						}

						foreach( $item as $id => $data )
						{
							//-----------------------------------------
							// INIT
							//-----------------------------------------
					
							$_cat_title     = $data['title'];
							$_cat_title     = str_replace( '&', '&amp;', $_cat_title ); // Validation thing
							$_nav_main_done = 0;
					
							if ( is_array( $data['subitems'] ) )
							{
								//-----------------------------------------
								// Loop....
								//-----------------------------------------

								foreach( $data['subitems'] as $__data )
								{
									foreach( $__data as $_id => $_data )
									{
										$_sub_item_title    = $_data['subitemtitle'];
										$_sub_item_url      = $_data['subitemurl'];
										$_sub_is_redirect   = $_data['subisredirect'];
										$_sub_section       = $_data['subsection'];
										$_sub_keywords      = $_data['subitemkeywords'];
										$_sub_item_role_key = isset( $_data['subitemrolekey'] ) ? $_data['subitemrolekey'] : '';
																			
										//-----------------------------------------
										// Continue...
										//-----------------------------------------
								
										if ( $_sub_item_title AND $_sub_section )
										{
											$app_menu_cache[ $app_dir ][ $id . '_' . $_current_module ]['title']         = $_cat_title;
											$app_menu_cache[ $app_dir ][ $id . '_' . $_current_module ]['items'][ $_id ] = array(  'title'    => $_sub_item_title,
																																   'module'   => $_current_module,
																																   'keywords' => $_sub_keywords,
																									   						  	   'section'  => $_sub_section,
																															   	   'url'      => $_sub_item_url,
																															       'rolekey'  => $_sub_item_role_key,
																								       							   'redirect' => $_sub_is_redirect );
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		$this->cache->setCache( 'app_menu_cache', $app_menu_cache, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
	
}

?>