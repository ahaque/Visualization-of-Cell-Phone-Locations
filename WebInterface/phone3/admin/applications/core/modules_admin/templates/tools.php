<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin tools
 * Last Updated: $Date: 2009-07-22 04:02:13 -0400 (Wed, 22 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 4925 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_tools extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**#@+
	 * URL bits
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/
	
	/**
	 * Main executable function
	 *
	 * @access	public
	 * @param	object		IPS Registry Object
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=tools';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=tools';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );
		
		$this->skinFunctions = new skinImportExport( $registry );
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'template_tools' );
		
		switch( $this->request['do'] )
		{
			case 'rebuildPHPTemplates':
				$this->_rebuildPHPTemplates();
			break;
			case 'rebuildMasterSkin':
				$this->_rebuildMasterSkin();
			break;
			case 'exportAPPCSS':
				$this->_exportAPPCSS();
			break;
			case 'exportAPPTemplates':
				$this->_exportAPPTemplates();
			break;
			case 'exportMasterReplacements':
				$this->_exportReplacements();
			break;
			case 'importAPPTemplates':
				$this->_importAPPTemplates();
			break;
			case 'rebuildMasterCss':
			case 'inDevMasterCSS':
				$this->_inDevMasterCSS();
			break;
			case 'toolsRecache':
				$this->_recache();
			break;
			case 'toolsRebuildMaster':
				$this->_rebuildMaster();
			break;
			case 'toolsResetSkin':
				$this->_resetSkin();
			break;
			case 'inDevMasterReplacements':
				$this->_inDevMasterReplacements();
			break;
			case 'rebuildForRelease':
				$this->_rebuildForRelease();
			break;
			case 'createMasterSkin':
				$this->_createMasterSkin();
			break;
			case 'templateDbClean':
				$this->_templateDbClean();
			break;
			case 'cssDbClean':
				$this->_cssDbClean();
			break;
			case 'toolCacheClean':
				$this->_toolCacheClean();
			break;
			case '_splash':
			default:
				$this->request['do'] = 'splash';
				$this->_splash();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
		
	/**
	 * Cleans out CSS DB
	 *
	 * @access 	public
	 * @return	void
	*/
	public function _toolCacheClean()
	{
		/* INIT */
		$setId       = intval( $this->request['setID'] );
		$doCss       = intval( $this->request['cleanCss'] );
		$doTemplates = intval( $this->request['cleanTemplates'] );
		$affectedCss = 0;
		$affectedTem = 0;
		
		if ( $doCss )
		{
			$affectedCss = $this->skinFunctions->removeDeadCSSCaches( $setId );
		}
		
		if ( $doTemplates )
		{
			$affectedTem = $this->skinFunctions->removeDeadPHPCaches( $setId );
		}

		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['tool_cache_clean_run'], array( sprintf( $this->lang->words['tool_cache_clean_desc'], $affectedCss, $affectedTem ) ) );
	}
		
	/**
	 * Cleans out CSS DB
	 *
	 * @access 	public
	 * @return	void
	*/
	public function _cssDbClean()
	{
		$affected = $this->skinFunctions->cleanDbCss();

		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['tool_css_clean_run'], array( sprintf( $this->lang->words['tool_css_clean_desc'], $affected['cached'], $affected['templates'] ) ) );
	}
		
	/**
	 * Cleans out template DB
	 *
	 * @access 	public
	 * @return	void
	*/
	public function _templateDbClean()
	{
		$affected = $this->skinFunctions->cleanDbTemplates();
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['tool_template_clean_run'], array( sprintf( $this->lang->words['tool_template_clean_desc'], $affected['cached'], $affected['templates'] ) ) );
	}
	
	/**
	 * Creates the master skin
	 *
	 * @access 	public
	 * @return	void
	*/
	public function _createMasterSkin()
	{
		/* No dir? create */
		if ( ! is_dir( IPS_CACHE_PATH . 'cache/skin_cache/master_skin' ) )
		{
			if ( @mkdir( IPS_CACHE_PATH . 'cache/skin_cache/master_skin', 0777 ) )
			{
				@chmod( IPS_CACHE_PATH . 'cache/skin_cache/master_skin', 0777 );
			}
			else
			{
				$this->registry->output->global_error = "Could not create directory: " . IPS_CACHE_PATH . 'cache/skin_cache/master_skin please create it via FTP and run the tool again';
				return $this->_splash();
			}
		}
		
		$messages = $this->skinFunctions->writeMasterSkin( 0, 'master_skin' );
		
		$this->registry->output->html .= $this->html->tools_toolResults( 'master_skin Directory Creation Results', $messages );
	}
	
	/**
	 * Rebuild everything for a release
	 *
	 * @access	private
	 * @return	void
	 */
	private function _rebuildForRelease()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$messages = array();
		$errors   = array();
		
		/* This is which skins we want to export for default installations */
		$skinIDs = array_values( $this->skinFunctions->remapData['export'] );
		
		//-----------------------------------------
		// Can do .. stuff?
		//-----------------------------------------
		
		if ( ! is_writable( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins' ) )
		{
			$this->registry->output->global_error = $this->lang->words['to_writetoprs'];
			return $this->_splash();
		}
		
		//-----------------------------------------
		// Rebuild stuff
		//-----------------------------------------
		
		foreach( $skinIDs as $id )
		{
			$msg        = $this->skinFunctions->rebuildMasterCSS( $id );
			$messages[] = "<strong>CSS Rebuilt for $id</strong>";
			$messages   = array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			$msg        = $this->skinFunctions->rebuildMasterFromPHP( $id );
			$messages[] = "<strong>HTML Rebuilt for $id</strong>";
			$messages   = array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		}
		
		$this->skinFunctions->rebuildMasterReplacements();
		$messages[] = "<strong>Replacements Rebuilt</strong>";
		$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		/* Got any errors? */
		if ( count( $errors ) )
		{
			$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultrebuild'] . ' ABORTED', $messages, $errors );
			return;
		}
		
		$this->skinFunctions->rebuildTreeInformation();
		$this->skinFunctions->rebuildSkinSetsCache();
		
		//-----------------------------------------
		// Rebuild skin caches
		//-----------------------------------------
		
		foreach( $skinIDs as $id )
		{
			$setData = $this->skinFunctions->fetchSkinData( $id );
			
			$key = $setData['set_key'];
			
			if ( $id > 0 )
			{
				$this->skinFunctions->rebuildPHPTemplates( $id );
				$messages[] = "<strong>PHP Templates Recached for $key</strong>";
				$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
				$this->skinFunctions->rebuildCSS( $id );
				$messages[] = "<strong>CSS Recached for $key</strong>";
				$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			}
		
			$this->skinFunctions->rebuildReplacementsCache( $id );
			$messages[] = "{$this->lang->words['to_replacerecached']} $key";
			$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			/* Build and write XML files for replacements and CSS */
			$replacementsXML = $this->skinFunctions->generateReplacementsXML( $id, $_thisOnly );
			
			@unlink( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml', $replacementsXML );
			
			if ( ! @file_put_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml', $replacementsXML ) )
			{
				$errors[] = "{$this->lang->words['to_couldnotwrite']}: " . DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml';
			}
			else
			{
				$messages[] = "{$this->lang->words['to_wrote']} " . DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml';
			}
			
			/* Export all CSS - Function loops through all skin sets */
			$this->skinFunctions->exportAllAppCSS( $id );
			$messages[] = "<strong>App CSS Written For $key</strong>";
			$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );

			/* Export all CSS - Function loops through all skin sets */
			$this->skinFunctions->exportAllAppTemplates( $id );
			$messages[] = "<strong>App XML Written For $key</strong>";
			$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			/* Got any errors? */
			if ( count( $errors ) )
			{
				$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultrebuild'] . ' ABORTED', array(), $errors );
				return;
			}
		}
		
		/* Generate skin sets XML */
		$skinSetXML = $this->skinFunctions->generateMasterSkinSetXML( $skinIDs );
		
		@unlink( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/setsData.xml' );
		
		if ( ! @file_put_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/setsData.xml', $skinSetXML ) )
		{
			$errors[] = "{$this->lang->words['to_couldnotwrite']}: " . DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/setsData.xml';
		}
		else
		{
			$messages[] = "{$this->lang->words['to_wrote']} " . DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/setsData.xml';
		}
		
		$messages[] = $this->lang->words['to_alldone'];
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultrebuild'], $messages, $errors );
	}
	
	/**
	 * Rebuilds master CSS
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _inDevMasterCSS()
	{
		/* This is which skins we want to export for default installations */
		$skinIDs  = array_values( $this->skinFunctions->remapData['export'] );
		$messages = array();
		$errors   = array();
		
		//-----------------------------------------
		// Rebuild stuff
		//-----------------------------------------
		
		foreach( $skinIDs as $id )
		{
			$msg        = $this->skinFunctions->rebuildMasterCSS( $id );
			$messages[] = "<strong>CSS Rebuilt for $id</strong>";
			$messages   = array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		}
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultrebuild'], $messages, $errors );
	}
	
	/**
	 * Rebuilds master replacements
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _inDevMasterReplacements()
	{
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		$this->skinFunctions->rebuildMasterReplacements();
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['to_masterreplace'];
		return $this->_splash();
	}
	
	/**
	 * Imports all APP templates
	 *
	 * @access	private
	 * @return	void
	 */
	private function _importAPPTemplates()
	{
		//-----------------------------------------
		// Easy one this...
		//-----------------------------------------
		
		$this->skinFunctions->importAllAppTemplates();
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultxmlimp'], $this->skinFunctions->fetchMessages(), $this->skinFunctions->fetchErrorMessages() );
	}
	
	/**
	 * Exports all APP templates
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportAPPTemplates()
	{
		//-----------------------------------------
		// Easy one this...
		//-----------------------------------------
		
		$this->skinFunctions->exportAllAppTemplates();
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultxmlimp'], $this->skinFunctions->fetchMessages(), $this->skinFunctions->fetchErrorMessages() );
	}
	
	/**
	 * Exports all APP templates
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportAPPCSS()
	{
		//-----------------------------------------
		// Easy one this...
		//-----------------------------------------
		
		$this->skinFunctions->exportAllAppCSS();
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultxmlimp'], $this->skinFunctions->fetchMessages(), $this->skinFunctions->fetchErrorMessages() );
	}
	
	/**
	 * Exports Replacements to XML
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportReplacements()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$messages = array();
		$errors   = array();
		
		/* This is which skins we want to export for default installations */
		$skinIDs = array_values( $this->skinFunctions->remapData['export'] );
		
		//-----------------------------------------
		// Export to disk
		//-----------------------------------------
		
		foreach( $skinIDs as $id )
		{
			$setData = $this->skinFunctions->fetchSkinData( $id );
			
			$key = $setData['set_key'];
			
			$this->skinFunctions->rebuildReplacementsCache( $id );
			$messages[] = "{$this->lang->words['to_replacerecached']} $key";
			$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			/* Build and write XML files for replacements and CSS */
			$replacementsXML = $this->skinFunctions->generateReplacementsXML( $id, $_thisOnly );
			
			@unlink( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml', $replacementsXML );
			
			if ( ! @file_put_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml', $replacementsXML ) )
			{
				$errors[] = "{$this->lang->words['to_couldnotwrite']}: " . DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml';
			}
			else
			{
				$messages[] = "{$this->lang->words['to_wrote']} " . DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins/replacements_' . $key . '.xml';
			}
			
			/* Got any errors? */
			if ( count( $errors ) )
			{
				$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultrebuild'] . ' ABORTED', array(), $errors );
				return;
			}
		}
		
		$messages[] = $this->lang->words['to_alldone'];
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_resultrebuild'], $messages, $errors );
	}
	
	/**
	 * Rebuild PHP Templates Cache
	 *
	 * @access	private
	 * @return	void
	 */
	private function _rebuildMasterSkin()
	{
		$messages = array();
		$errors   = array();

		if( $this->request['set_id'] )
		{
			if( $this->skinFunctions->remapData['templates'][ $this->request['set_id'] ] )
			{
				$msg		= $this->skinFunctions->rebuildMasterFromPHP( $this->request['set_id'] );
				$messages[]	= "<strong>HTML Rebuilt for {$this->request['set_id']}</strong>";
				$messages	= array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors		= array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			}
		}
		else
		{
			/* This is which skins we want to export for default installations */
			$skinIDs  = array_values( $this->skinFunctions->remapData['export'] );
				
			//-----------------------------------------
			// Do it...
			//-----------------------------------------
			
			foreach( $skinIDs as $id )
			{
				$msg		= $this->skinFunctions->rebuildMasterFromPHP( $id );
				$messages[]	= "<strong>HTML Rebuilt for {$id}</strong>";
				$messages	= array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors		= array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			}
		}
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_masterskinrebuilt'] . $this->request['set_id'], $messages, $errors );
	}
	
	/**
	 * Resets skin usage
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _resetSkin()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$resetMembers = intval( $this->request['resetMembers'] );
		$resetForums  = intval( $this->request['resetForums'] );
		$resetSkinID  = intval( $this->request['resetSkinID'] );
		$usingIDs	  = '';
		$message      = array();
		
		//-----------------------------------------
		// When using array...
		//-----------------------------------------
		
		if ( is_array( $_POST['setID'] ) )
		{
			$usingIDs = implode( ",", array_keys( $_POST['setID'] ) );
		
			if ( $resetMembers )
			{
				$this->DB->update( 'members', array( 'skin' => $resetSkinID ), 'skin IN(' . $usingIDs . ')' );
				$message[] = $this->DB->getAffectedRows() . $this->lang->words['to_membersupdated'];
			}
			
			if ( $resetForums )
			{
				$this->DB->update( 'forums', array( 'skin_id' => $resetSkinID ), 'skin_id IN(' . $usingIDs . ')' );
				$message[] = $this->DB->getAffectedRows() . $this->lang->words['to_forumsupdated'];
			}
		}
		
		$this->registry->output->global_message = implode( "<br />", $message );
		return $this->_splash();
	}
		
	/**
	 * Rebuild Master data
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _rebuildMaster()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$apps                = array();
		$rebuildHTML         = intval( $this->request['rebuildHTML'] );
		$rebuildCSS          = intval( $this->request['rebuildCSS'] );
		$rebuildReplacements = intval( $this->request['rebuildReplacements'] );
		$messages			 = array();
		
		//-----------------------------------------
		// Figure out app data
		//-----------------------------------------
		
		if ( ! is_array( $_POST['apps'] ) )
		{
			$this->registry->output->global_error = $this->lang->words['to_noapps'];
			return $this->_splash();
		}
		
		//-----------------------------------------
		// Do it!
		//-----------------------------------------
		
		foreach( $_POST['apps'] as $app_dir => $data )
		{
			if ( $data )
			{
				$_appTitle = ipsRegistry::$applications[ $app_dir ]['app_title'];
				
				/* HTML */
				if ( $rebuildHTML )
				{
					foreach( array( 'root', 'lofi', 'xmlskin' ) as $setKey )
					{
						$return     = $this->skinFunctions->importTemplateAppXML( $app_dir, $setKey );
						$messages[] = $_appTitle . sprintf( $this->lang->words['to_masterhtml'], $setKey, $return['updateCount'], $return['insertCount'] );
					}
				}
			}
			
			/* Replacements */
			if ( $rebuildReplacements )
			{
				$this->skinFunctions->rebuildMasterReplacements();
				$messages[] = $this->lang->words['to_masterreplace'];
			}
			
			/* CSS */
			if ( $rebuildCSS )
			{
				foreach( array( 'root', 'lofi', 'xmlskin' ) as $setKey )
				{
					$return     = $this->skinFunctions->importCSSAppXML( $app_dir, $setKey );
					$messages[] = $_appTitle . sprintf( $this->lang->words['to_mastercssrebuilt'], $setKey, $return['updateCount'], $return['insertCount'] );
				}
			}
		}
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_rebuildcomplete'], $messages );
	}
	
	/**
	 * Rebuild PHP Templates Cache
	 *
	 * @access	private
	 * @return	void
	 */
	private function _recache()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID    = intval( $this->request['setID'] );
		$type     = $this->request['type'];
		$messages = array();
		$errors   = array();
		
		$this->registry->output->global_error = '';
		
		//-----------------------------------------
		// Multi job?
		//-----------------------------------------
		
		if ( ! $type AND ! $setID )
		{
			ksort( $this->registry->output->allSkins );
			$_skins = $this->registry->output->allSkins;
			$_set   = array_shift( $_skins );
			$setID  = $_set['set_id'];
			$type   = 'all';
		}
		
		//-----------------------------------------
		// Rebuild...
		//-----------------------------------------
		
		/* Single */
		$this->skinFunctions->rebuildPHPTemplates( $setID );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildCSS( $setID );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildReplacementsCache( $setID );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		//-----------------------------------------
		// All chosen...
		//-----------------------------------------
		
		if ( $type == 'all' )
		{
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
				if ( count( $errors ) )
				{
					$this->registry->output->global_error = implode( '<br />', $errors );
				}
				
				/* More to go.. */
				$this->registry->output->redirect( $this->settings['base_url'] . 'app=core&module=templates&section=tools&do=toolsRecache&type=all&setID=' . $nextID, $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'], 2, TRUE );
			}
			else
			{
				/* All done */
				$this->registry->output->global_message = $this->lang->words['to_recache_done'];
				
				if ( count( $errors ) )
				{
					$this->registry->output->global_error = implode( '<br />', $errors );
				}
			
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview', TRUE );
			}
		}
		else
		{
			/* All done */
			$this->registry->output->global_message = $this->lang->words['to_recache_done']  . '<br />' . implode( '<br />', $messages );
			
			if ( count( $errors ) )
			{
				$this->registry->output->global_error = implode( '<br />', $errors );
			}
				
			$this->_splash();
		}
	}
	
	/**
	 * Rebuild PHP Templates Cache
	 *
	 * @access	private
	 * @return	void
	 */
	private function _rebuildPHPTemplates()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID = intval( $this->request['setID'] );

		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		$this->skinFunctions->rebuildPHPTemplates( $setID );
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_toolResults( $this->lang->words['to_phprebuilt'] . $setData['set_name'], $this->skinFunctions->fetchMessages(), $this->skinFunctions->fetchErrorMessages() );
	}

	/**
	 * Shows a list of available tools
	 *
	 * @access	public
	 * @return	void
	 */
	public function _splash()
	{
		//-----------------------------------------
		// Build app data
		//-----------------------------------------
		
		$appsData = '';
		$apps     = new IPSApplicationsIterator();
		
		foreach( $apps as $app )
		{
			$app_dir                   = $apps->fetchAppDir();
			$app['lastmTime']          = @filemtime( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_root_templates.xml' );
			$app['lastmTimeFormatted'] = $this->registry->getClass( 'class_localization')->getDate( $app['lastmTime'], 'JOINED' );
			
			$appsData[ $app_dir ] = $app;
		}
		
		//-----------------------------------------
		// Show tools
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->tools_splash( $this->skinFunctions->getTiersFunction()->fetchAllsItemDropDown(), $appsData, $this->skinFunctions->remapData );

		$this->registry->output->nav[]    = array( "" . $this->settings['base_url'] . "{$this->form_code}", $this->lang->words['to_remplatetools'] );
	}

}
