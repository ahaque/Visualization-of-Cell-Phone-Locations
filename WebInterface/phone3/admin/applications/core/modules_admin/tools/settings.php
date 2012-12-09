<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP : Settings
 * Last Updated: $Date: 2009-07-20 09:13:18 -0400 (Mon, 20 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Wed. 18th August 2004
 * @version		$Rev: 4911 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_tools_settings extends ipsCommand
{
	/**
	 * Key array
	 *
	 * @access	private
	 * @var		array				Array of keys
	 */
	private $key_array 				= array();
	
	/**
	 * Setting group array
	 *
	 * @access	private
	 * @var		array				Array of setting groups
	 */
	private $in_group				= array();
	
	/**
	 * Setting groups array
	 *
	 * @access	private
	 * @var		array				Array of setting groups
	 */
	private $setting_groups			= array();
	
	/**
	 * Setting groups array mapping
	 *
	 * @access	private
	 * @var		array				Array of setting groups
	 */
	private $setting_groups_by_key	= array();

	/**
	 * Skin object
	 * This is public so that the portal can access it
	 *
	 * @access	public
	 * @var		object				Skin templates
	 */
	public $html;
	
	/**
	 * Form code
	 * This is public so that the portal can access it
	 *
	 * @access	public
	 * @var		string				Form code
	 */
	public $form_code;
	
	/**
	 * Form code
	 * This is public so that the portal can access it
	 *
	 * @access	public
	 * @var		string				JS Form code
	 */
	public $form_code_js;
	
	/**
	 * Application to use
	 *
	 * @access	private
	 * @var		string				Application
	 */
	private $_app;
	
	/**
	 * Where to return to after save
	 *
	 * @access	public 
	 * @var		string				URL to go to
	 */
	public $return_after_save;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry ) 
	{		
		//-----------------------------------------
		// Load language
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );

		$this->html = $this->registry->output->loadTemplate('cp_skin_tools');
		
		$this->form_code    = $this->html->form_code    = 'module=tools&amp;section=settings';
		$this->form_code_js = $this->html->form_code_js = 'module=tools&section=settings';
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settings_manage' );
		
		switch( $this->request['do'] )
		{
			case 'settinggroup_resync':
				$this->_resynchSettingGroup();
				break;
				
			case 'settinggroup_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settings_delete' );
				$this->_deleteSettingGroup();
				break;
				
			case 'settinggroup_new':
				$this->_settingGroupForm('add');
				break;
				
			case 'settinggroup_showedit':
				$this->_settingGroupForm('edit');
				break;
				
			case 'settinggroup_add':
				$this->_settingGroupSave('add');
				break;
				
			case 'settinggroup_edit':
				$this->_settingGroupSave('edit');
				break;
				
			case 'settingnew':
				$this->_settingForm('add');
				break;
				
			case 'setting_showedit':
				$this->_settingForm('edit');
				break;
				
			case 'setting_add':
				$this->_settingSave('add');
				break;
				
			case 'setting_edit':
				$this->_settingSave('edit');
				break;
				
			case 'reorder':
				$this->reorder();
				break;
				
			case 'setting_view':
				$this->_viewSettings();
				break;

			case 'setting_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settings_delete' );
				$this->_deleteSettings();
				break;
			
			case 'setting_revert':
				$this->_revertSettings();
				break;
				
			case 'setting_update':
				$this->_updateSettings();
				break;
			
			case 'findsetting':
				$this->_findSettingGroup();
				break;
				
			case 'settings_do_import':
				$this->importAllSettings();
				break;
			case 'settings_do_import_indev':
				$this->importAllSettings(1);
				break;
				
			case 'setting_someexport_start':
				$this->_startExportingSettings();
				break;

			case 'setting_someexport_complete':
				$this->_finishExportingSettings();
				break;
				
			case 'MOD_export_setting':
				$this->_exportSettingsGroup();
				break;
				
			case 'settingsExportApps':
				$messages = $this->exportAllApps();
				
				$this->registry->output->global_message = implode( "<br />", $messages );
			
				$this->_settingsOverview();
			break;
					
			case 'settingsImportApps':
				$messages = $this->importAllApps();
				
				$this->registry->output->global_message = implode( "<br />", $messages );
				$this->_settingsOverview();
			break;
			default:
				$this->request['do'] = 'settingsview';
				$this->_settingsOverview();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Reorder the settings
	 *
	 * @access	public
	 * @return	void
	 */
	public function reorder()
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
 		
 		if( is_array($this->request['settings']) AND count($this->request['settings']) )
 		{
 			foreach( $this->request['settings'] as $this_id )
 			{
 				$this->DB->update( 'core_sys_conf_settings', array( 'conf_position' => $position ), 'conf_id=' . $this_id ); 
 				
 				$position++;
 			}
 		}
 		
 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Show page to export settings
	 *
	 * @access	private
	 * @return	void
	 */
	private function _startExportingSettings()
	{
		//-----------------------------------------
		// Get the settings
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'order' => 'conf_id' ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			$settings[] = $r;
		}

		$this->registry->output->html .= $this->html->settings_exportsingle( $settings );
	}
	
	/**
	 * Export the settings
	 *
	 * @access	private
	 * @return	void
	 */
	private function _finishExportingSettings()
	{
		$ids    = array();
		$groups = array();

		//-----------------------------------------
		// get ids...
		//-----------------------------------------

		foreach ($_POST as $key => $value)
		{
			if ( preg_match( "/^id_(\d+)$/", $key, $match ) )
			{
				if ($this->request[$match[0]])
				{
					$ids[] = $match[1];
				}
			}
		}

		$ids = IPSLib::cleanIntArray( $ids );

		//-----------------------------------------
		// Got any?
		//-----------------------------------------

		if ( ! count( $ids ) )
		{
			$this->registry->output->global_message = $this->lang->words['s_sometoexport'];
			$this->_startExportingSettings();
			return;
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'class_xml.php' );

		$xml = new class_xml();

		$xml->xml_set_root( 'settingexport', array( 'exported' => time() ) );

		//-----------------------------------------
		// Get groups
		//-----------------------------------------

		$xml->xml_add_group( 'settinggroup' );

		$this->_settingsGetGroups();

		$entry = array();

		$this->DB->build( array( 'select' => '*',
													  'from'   => 'core_sys_conf_settings',
													  'where'  => "conf_id IN (".implode(",",$ids).")",
													  'order'  => 'conf_position, conf_title' ) );

		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			$content                    = array();
			$groups[ $r['conf_group'] ] = $r['conf_group'];
			$r['conf_value']            = '';

			//-----------------------------------------
			// Add in setting key
			//-----------------------------------------

			$r['conf_title_keyword'] = $this->setting_groups[ $r['conf_group'] ]['conf_title_keyword'];

			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}

			$entry[] = $xml->xml_build_entry( 'setting', $content );
		}

		//-----------------------------------------
		// Add in groups...
		//-----------------------------------------

		if ( is_array( $groups ) AND count( $groups ) )
		{
			foreach( $groups as $conf_group_id )
			{
				$content  = array();

				$thisconf = array( 'conf_is_title'      => 1,
								   'conf_title_keyword' => $this->setting_groups[ $conf_group_id ]['conf_title_keyword'],
								   'conf_title_title'   => $this->setting_groups[ $conf_group_id ]['conf_title_title'],
								   'conf_title_desc'    => $this->setting_groups[ $conf_group_id ]['conf_title_desc'],
								   'conf_title_noshow'  => $this->setting_groups[ $conf_group_id ]['conf_title_noshow'],
								   'conf_title_module'  => $this->setting_groups[ $conf_group_id ]['conf_title_module'] );

				foreach( $thisconf as $k => $v )
				{
					$content[] = $xml->xml_build_simple_tag( $k, $v );
				}

				$entry[] = $xml->xml_build_entry( 'setting', $content );
			}
		}

		$xml->xml_add_entry_to_group( 'settinggroup', $entry );

		$xml->xml_format_document();

		$doc = $xml->xml_document;

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------

		$this->registry->output->showDownload( $doc, 'ipb_settings_partial.xml', '', 0 );
	}
	
	/**
	 * Setting group export
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportSettingsGroup()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request[ 'conf_group'] =  intval($this->request['conf_group'] );
		
		/* Got anything? */
		if ( ! $this->request['conf_group'] )
		{
			return;
		}
		
		//-----------------------------------------
		// Get setting groups
		//-----------------------------------------
		
		$this->_settingsGetGroups( true );
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'settingexport' );
		$xml->addElement( 'settinggroup', 'settingexport' );

		foreach( $this->setting_groups as $i => $roar )
		{
			//-----------------------------------------
			// App check?
			//-----------------------------------------
			
			if ( $this->request['conf_group'] != $roar['conf_title_id'] )
			{
				continue;
			}
			
			//-----------------------------------------
			// First, add in setting group title
			//-----------------------------------------
			
			$thisconf = array( 'conf_is_title'      => 1,
							   'conf_title_keyword' => $roar['conf_title_keyword'],
							   'conf_title_title'   => $roar['conf_title_title'],
							   'conf_title_desc'    => $roar['conf_title_desc'],
							   'conf_title_tab' 	=> $roar['conf_title_tab'],
							   'conf_title_app'     => $roar['conf_title_app'] ? $roar['conf_title_app'] : $app_dir,
							   'conf_title_noshow'  => $roar['conf_title_noshow'] );
			
			$xml->addElementAsRecord( 'settinggroup', 'setting', $thisconf );
			
			//-----------------------------------------
			// Get settings...
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'core_sys_conf_settings',
								     'where'  => "conf_group='{$roar['conf_title_id']}'",
								     'order'  => 'conf_position, conf_title' ) );
			
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				//-----------------------------------------
				// Clean up...
				//-----------------------------------------
				
				unset( $r['conf_value'], $r['conf_id'] );
				
				$r['conf_title_keyword'] = $roar['conf_title_keyword'];
				$r['conf_is_title']      = 0;
				
				$xml->addElementAsRecord( 'settinggroup', 'setting', $r );
			}
		}
		
		//-----------------------------------------
		// Grab the XML document
		//-----------------------------------------
		
		$xmlData = $xml->fetchDocument();
		
		$this->registry->output->showDownload( $xmlData, 'settingGroup_' . IPSText::makeSeoTitle( $thisconf['conf_title_title'] ) . '.xml', '', 0 );
	}
	
	/**
	 * Find setting group (don't rely on IDs)
	 *
	 * @access	public
	 * @return	void
	 */
	public function _findSettingGroup()
	{
		if ( ! $this->request['key'] )
		{
			$this->_settingsOverview();
		} 
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles' ) );
		$this->DB->execute();
	
		while( $r = $this->DB->fetch() )
		{ 	
			if(  ( $r['conf_title_keyword'] == $this->request['key'] ) OR strtolower( str_replace( " ", "", trim( $r['conf_title_title'] ) ) ) == urldecode( trim( $this->request['key'] ) ) )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&' . $this->form_code . '&do=setting_view&conf_group=' . $r['conf_title_id'] );
				break;
			}
		}
		
		$this->_settingsOverview();
	}
	
	/**
	 * Import all settings
	 *
	 * @access	public
	 * @param	boolean		In development mode?
	 * @param	boolean		Return (ignore the var name)
	 * @param	array 		Array of known setting values
	 * @return	void
	 */
	public function importAllSettings( $in_dev=0, $no_return=0, $known=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$updated     = 0;
		$inserted    = 0;
		$need_update = array();
		
		//-----------------------------------------
		// Make sure we have titles
		//-----------------------------------------
		
		$this->_settingsTitlesCheck();
		
		//-----------------------------------------
		// INDEV?
		//-----------------------------------------
		
		if ( $in_dev )
		{
			$_FILES['FILE_UPLOAD']['name']          = '';
			$this->request['file_location'] =  IPSLib::getAppDir(  $this->request['app_dir'] ) . '/xml/' . $this->request['app_dir'] . '_settings.xml';
		}
		else
		{
			$this->request['file_location'] =  IPS_ROOT_PATH . $this->request['file_location'] ;
		}
	
		//-----------------------------------------
		// Go for it
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->request['file_location'] )
			{
				$this->registry->output->global_message = $this->lang->words['s_nofile'];
				$this->_settingsOverview();
				return;
			}
			
			if ( ! file_exists( $this->request['file_location'] ) )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['s_noopen'], $this->request['file_location'] );
				$this->_settingsOverview();
				return;
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
		
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['s_nofile'];
			$this->_settingsOverview();
			return;
		}
		
		$return = $this->_importXML( $content, $this->request['app_dir'], $known );
		
		if ( $no_return )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['s_upandin'], $return['updatedCount'], $return['insertedCount'] );
			return TRUE;
		}
		else
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['s_upandin'], $return['updatedCount'], $return['insertedCount'] );
			$this->_settingsOverview();
			return;
		}
	}
		
	/**
	 * Delete a setting group
	 *
	 * @access	private
	 * @return	void
	 */
	private function _deleteSettingGroup()
	{
		if ( $this->request['id'] )
		{
			$conf = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group=' . $this->request['id'] ) );
		
			$count = intval($conf['count']);
			
			if ( $count > 0 )
			{
				$this->registry->output->global_message = $this->lang->words['s_cantremove'];
			}
			else
			{
				$this->DB->delete( 'core_sys_settings_titles', 'conf_title_id=' . $this->request['id'] );
				
				$this->registry->output->global_message = $this->lang->words['s_removed'];
			}
				
		}
		
		$this->settingsRebuildCache();
		
		$this->_settingsOverview();
	}
	
	/**
	 * Resynchronize settings in a group
	 *
	 * @access	private
	 * @return	void
	 */
	private function _resynchSettingGroup()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['id'] =  intval( $this->request['id'] );
		
		//-----------------------------------------
		// Doit...
		//-----------------------------------------
		
		$this->_resynchGroup( $this->request['id'] );
		
		$this->registry->output->global_message = $this->lang->words['s_recounted'];
		
		//-----------------------------------------
		// Get conf title details
		//-----------------------------------------
		
		$conf_group = $this->DB->buildAndFetch( array( 'select' => 'conf_title_tab',
																		'from'   => 'core_sys_settings_titles',
																		'where'  => 'conf_title_id='.$this->request['id'] ) );
		
		$this->_settingsOverview( $conf_group['conf_title_tab'] );
	}
	
	/**
	 * Does the resync
	 *
	 * @access	private
	 * @param	integer		Group id
	 * @return	void
	 */
	private function _resynchGroup( $id )
	{
		if ( $id )
		{
			$conf = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group='.$id ) );
		
			$count = intval($conf['count']);
			
			$this->DB->update( 'core_sys_settings_titles', array( 'conf_title_count' => $count ), 'conf_title_id=' . $id );
		}
	}		
	
	/**
	 * Form to add/edit a setting group
	 *
	 * @access	private
	 * @param	string		[add|edit]
	 * @return	void
	 */
	private function _settingGroupForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$conf_title_id = intval( $this->request['id'] );
		$form          = array();
		$apps          = array( 0 => array( 'core', $this->lang->words['s_global_dd'] ) );
		
		//-----------------------------------------
		// Build applications drop down
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$apps[] = array( $app_dir, $app_data['app_title'] );
		}
		
		//-----------------------------------------
		// Type
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'settinggroup_add';
			$title    = $this->lang->words['s_createnew'];
			$button   = $this->lang->words['s_createnew'];
		}
		else
		{
			$conf = $this->DB->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'core_sys_settings_titles',
																	  'where'  => 'conf_title_id='.$conf_title_id ) );
			
			if ( ! $conf['conf_title_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['s_noid'];
				$this->_settingsOverview();	
			}
			
			$formcode = 'settinggroup_edit';
			$title    = sprintf($this->lang->words['s_editsetting'], $conf['conf_title'] );
			$button   = $this->lang->words['s_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['conf_title_title']   = $this->registry->output->formInput( 'conf_title_title', $_POST['conf_title_title'] ? $_POST['conf_title_title'] : $conf['conf_title_title'] );
		$form['conf_title_desc']    = $this->registry->output->formTextarea( 'conf_title_desc', $_POST['conf_title_desc'] ? $_POST['conf_title_desc'] : $conf['conf_title_desc'] );
		$form['conf_title_app']     = $this->registry->output->formDropdown( 'conf_title_app', $apps, $_POST['conf_title_app'] ? $_POST['conf_title_app'] : $conf['conf_title_app'] );
		$form['conf_title_tab']   = $this->registry->output->formInput( 'conf_title_tab', $_POST['conf_title_tab'] ? $_POST['conf_title_tab'] : $conf['conf_title_tab'] );
		
		if ( IN_DEV )
		{
			$form['conf_title_keyword'] = $this->registry->output->formInput( 'conf_title_keyword', $_POST['conf_title_keyword'] ? $_POST['conf_title_keyword'] : $conf['conf_title_keyword'] );
			$form['conf_title_noshow']  = $this->registry->output->formYesNo( 'conf_title_noshow', $_POST['conf_title_noshow'] ? $_POST['conf_title_noshow'] : $conf['conf_title_noshow'] );
		}
		
		$this->registry->output->html .= $this->html->settings_title_form( $form, $title, $formcode, $button );
	}
	
	/**
	 * Save a setting group
	 *
	 * @access	private
	 * @param	string		[add|edit]
	 * @return	void
	 */
	private function _settingGroupSave($type='add')
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $this->request['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['s_noid'];
				$this->_settingForm();
				return;
			}
		}
		
		if( ! $this->request['conf_title_title'] )
		{
			$this->registry->output->global_message = $this->lang->words['s_notitle'];
			$this->_settingGroupForm( $type );
			return;	
		}
		
		//--------------------------------------------
		// Check...
		//--------------------------------------------
		
		$array = array( 'conf_title_title'   => $this->request['conf_title_title'],
						'conf_title_desc'    => IPSText::safeslashes( $_POST['conf_title_desc'] ),
						'conf_title_keyword' => IPSText::safeslashes( $_POST['conf_title_keyword'] ),
						'conf_title_noshow'  => $this->request['conf_title_noshow'],
						'conf_title_app'     => trim( $this->request['conf_title_app'] ),
						'conf_title_tab'     => trim( $this->request['conf_title_tab'] )
					 );
						
						
		if ( $type == 'add' )
		{
			$this->DB->insert( 'core_sys_settings_titles', $array );
			$this->registry->output->global_message = $this->lang->words['s_added'];
		}
		else
		{
			$this->DB->update( 'core_sys_settings_titles', $array, 'conf_title_id=' . $this->request['id'] );
			$this->registry->output->global_message = $this->lang->words['s_edited'];
		}
		
		$this->settingsRebuildCache();
		
		$this->_settingsOverview();
	}
	
	/**
	 * Form to add/edit a setting
	 *
	 * @access	private
	 * @param	string		[add|edit]
	 * @return	void
	 */
	private function _settingForm( $type='add' )
	{
		if ( $type == 'add' )
		{
			$formcode = 'setting_add';
			$title    = $this->lang->words['s_createnewtitle'];
			$button   = $this->lang->words['s_createnewtitle'];
			$conf     = array( 'conf_group' => $this->request['conf_group'], 'conf_add_cache' => 1 );
			
			if ( IN_DEV )
			{
				$conf['conf_protected'] = 1;
			}
			
			if ( $this->request['conf_group'] )
			{
				$max = $this->DB->buildAndFetch( array( 'select' => 'max(conf_position) as max', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group=' . $this->request['conf_group'] ) );
			}
			else
			{
				$max = $this->DB->buildAndFetch( array( 'select' => 'max(conf_position) as max', 'from' => 'core_sys_conf_settings' ) );
			}
			
			$conf['conf_position'] = $max['max'] + 1;
		}
		else
		{
			$conf = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_id=' . $this->request['id'] ) );
			
			if ( ! $conf['conf_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['s_noid'];
				$this->_settingsOverview();	
			}
			
			$formcode = 'setting_edit';
			$title    = sprintf($this->lang->words['s_editsetting'], $conf['conf_title'] );
			$button   = $this->lang->words['s_savechanges'];
		}
		
		//-----------------------------------------
		// Get groups
		//-----------------------------------------
		
		$this->_settingsGetGroups();
		
		$groups = array();
		
		foreach( $this->setting_groups as $i => $r )
		{
			$groups[] = array( $r['conf_title_id'], $r['conf_title_title'] );
		}
		
		//-----------------------------------------
		// Type
		//-----------------------------------------
		
		$types = array( 0 => array( 'input'   , $this->lang->words['s_input'] ),
						1 => array( 'dropdown', $this->lang->words['s_dropdown']  ),
						2 => array( 'yes_no'  , $this->lang->words['s_yes_no']		),
						3 => array( 'textarea', $this->lang->words['s_textarea']   ),
						4 => array( 'editor'  , $this->lang->words['s_editor']   ),
						5 => array( 'multi'   , $this->lang->words['s_multi'] ),
					 );
		
		
		
		$form['conf_title']       = $this->registry->output->formInput(    'conf_title'      , $_POST['conf_title']       ? $_POST['conf_title'] : $conf['conf_title'] );
		$form['conf_position']    = $this->registry->output->formInput(    'conf_position'   , $_POST['conf_position']    ? $_POST['conf_position'] : $conf['conf_position'] );
		$form['conf_description'] = $this->registry->output->formTextarea( 'conf_description', $_POST['conf_description'] ? $_POST['conf_description'] : $conf['conf_description'] );
		$form['conf_group']       = $this->registry->output->formDropdown( 'conf_group'      , $groups, $_POST['conf_group'] ? $_POST['conf_group'] : $conf['conf_group'] );
		$form['conf_type']        = $this->registry->output->formDropdown( 'conf_type'       , $types, $_POST['conf_type'] ? $_POST['conf_type'] : $conf['conf_type'] );
		$form['conf_key']         = $this->registry->output->formInput(    'conf_key'        , $_POST['conf_key']         ? $_POST['conf_key'] : $conf['conf_key'] );
		$form['conf_value']       = $this->registry->output->formTextarea( 'conf_value'      , $_POST['conf_value']       ? $_POST['conf_value'] : $conf['conf_value'] );
		$form['conf_default']     = $this->registry->output->formTextarea( 'conf_default'    , $_POST['conf_default']     ? $_POST['conf_default'] : $conf['conf_default'] );
		$form['conf_extra']       = $this->registry->output->formTextarea( 'conf_extra'      , $_POST['conf_extra']       ? $_POST['conf_extra'] : $conf['conf_extra'] );
		$form['conf_evalphp']     = $this->registry->output->formTextarea( 'conf_evalphp'    , $_POST['conf_evalphp']     ? $_POST['conf_evalphp'] : $conf['conf_evalphp'] );
		$form['conf_keywords']    = $this->registry->output->formTextarea( 'conf_keywords'   , $_POST['conf_keywords']    ? $_POST['conf_keywords'] : $conf['conf_keywords'] );
		$form['conf_start_group'] = $this->registry->output->formInput(    'conf_start_group', $_POST['conf_start_group'] ? $_POST['conf_start_group'] : $conf['conf_start_group'] );
		$form['conf_end_group']   = $this->registry->output->formYesNo(   'conf_end_group'  , $_POST['conf_end_group']   ? $_POST['conf_end_group'] : $conf['conf_end_group'] );
		$form['conf_add_cache']	  = $this->registry->output->formYesNo( 'conf_add_cache', $_POST['conf_add_cache'] ? $_POST['conf_add_cache'] : $conf['conf_add_cache'] );

		if ( IN_DEV )
		{
			$form['conf_protected'] = $this->registry->output->formYesNo( 'conf_protected', $_POST['conf_protected'] ? $_POST['conf_protected'] : $conf['conf_protected'] );
		}
		
		
		
		//-----------------------------------------
		// start form
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->settings_form( $form, $title, $formcode, $button );
	}
	
	/**
	 * View all settings (form) in a group
	 * This is public so that portal can access it
	 *
	 * @access	public
	 * @return	void
	 */
	public function _viewSettings()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$search_key		= trim( $this->request['search'] );
		$conf_group		= trim( $this->request['conf_group'] );
		$conf_titles	= array();
		$in_group		= array();
		$last_conf_id	= -1;
		$start			= intval( $this->request['st'] );
		$end			= 150;
		$get_by_key     = isset( $this->request['conf_title_keyword'] ) ? $this->request['conf_title_keyword'] : '';
		
		//-----------------------------------------
		// Get settings in group
		//-----------------------------------------
		
		$this->_settingsGetGroups( ( $get_by_key ) ? TRUE : FALSE );
		
		//-----------------------------------------
		// Grabbing by key?
		//-----------------------------------------
		
		if( $get_by_key )
		{
			$data = $this->DB->buildAndFetch( array( 
													'select' => 'conf_title_id, conf_title_keyword, conf_title_title', 
													'from'   => 'core_sys_settings_titles', 
													'where'  => "conf_title_keyword='{$get_by_key}'" 
											)	);
		
			$this->request['conf_group']   = $data['conf_title_id'] ;
			$conf_group                    = $data['conf_title_id'];			
			$this->request['groupHelpKey'] = $data['conf_title_keyword'];
		}

		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if( ( ! $conf_group and ! $search_key ) && ! $search_key )
		{
			$this->registry->output->global_message = $this->lang->words['s_nogroup'];
			$this->_settingsOverview();
			return;
		}
		
		//-----------------------------------------
		// Pagination
		//-----------------------------------------

		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $this->setting_groups[$conf_group]['conf_title_count'],
																	'itemsPerPage'		=> $end,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $this->settings['base_url'] . "&amp;{$this->form_code}&amp;&search={$search_key}",
															)	);
		
		
		//-----------------------------------------
		// Did we search?
		//-----------------------------------------
		
		if( $search_key )
		{
			$keywords	= strtolower($search_key);
			$whereExtra	= $conf_group ? " AND c.conf_group={$conf_group}" : '';
			
			$this->DB->build( array( 
										'select'	=> 'c.*',
										'from'		=> array( 'core_sys_conf_settings' => 'c' ),
										'where'		=> "(LOWER(conf_title) LIKE '%{$keywords}%' OR LOWER(conf_description) LIKE '%{$keywords}%' OR LOWER(conf_keywords) LIKE '%{$keywords}%')" . $whereExtra,
										'order'		=> 'c.conf_title',
										'limit'		=> array( $start, $end ),
										'add_join'	=> array(
																array( 
																		'select'	=> 'ct.conf_title_id, ct.conf_title_noshow, ct.conf_title_title, ct.conf_title_tab',
																		'from'		=> array( 'core_sys_settings_titles' => 'ct' ),
																		'where'		=> 'ct.conf_title_id=c.conf_group',
																		'type'		=> 'left'
																	)
																)
									)		);
    		$this->DB->execute();
    	
			while( $r = $this->DB->fetch() )
			{
				$r['conf_start_group']       = "";
				$r['conf_end_group']         = "";
				$r['conf_description']		.= '<br />' . $this->lang->words['conf_desc_search'] . "<a href='{$this->settings['base_url']}&amp;app=core&amp;module=tools&amp;section=settings&amp;do=setting_view&amp;conf_group={$r['conf_title_id']}'>" . $r['conf_title_title'] . "</a>";
				$conf_entry[ $r['conf_id'] ] = $r;
			}
			
			if( ! count( $conf_entry ) )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['s_nomatches'], $keywords );
				$this->_settingsOverview();
				return;
			}
			
			$title = sprintf( $this->lang->words['s_searchedfor'], $keywords );
			$tab   = '';
		}
		
		//-----------------------------------------
		// Or not...
		//-----------------------------------------
		
		else
		{
			$this->DB->build( array(
									'select' => '*',
									'from'   => 'core_sys_conf_settings',
									'where'  => "conf_group='{$conf_group}'",
									'order'  => 'conf_position, conf_title',
									'limit'  => array( $start,$end ) 
							)	);			
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$conf_entry[ $r['conf_id'] ] = $r;
				
				if ( $r['conf_end_group'] )
				{
					$in_g = 0;
				}
				
				if ( $in_g )
				{
					$this->in_group[] = $r['conf_id'];
				}
				
				if ( $r['conf_start_group'] )
				{
					$in_g = 1;
				}
			}
			
			$title = $this->setting_groups[$conf_group]['conf_title_title'];
			$tab   = IPSText::md5Clean( $this->setting_groups[$conf_group]['conf_title_tab'] );
			$this->request['groupHelpKey'] = $this->setting_groups[ $conf_group ]['conf_title_keyword'];
		}

		//-----------------------------------------
		// Start output
		//-----------------------------------------
		
		$content   = "";
		
		if( is_array( $conf_entry ) and count( $conf_entry ) )
		{
			foreach( $conf_entry as $id => $r )
			{
				$content .= $this->_processSettingEntry( $r );
			}
		}
		
		if( ! $search_key AND ! $get_by_key )
		{
			$searchbutton = 1;
		}
		
		/* Navigation */
		if( $tab )
		{
			$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;_dtab={$tab}", ipsRegistry::$applications[ $this->setting_groups[$conf_group]['conf_title_app'] ]['app_title'] );
			$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=setting_view&amp;conf_group={$conf_group}", $title );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->settings_view_wrapper( $title, $content, $searchbutton, $this->return_after_save );		
		$this->registry->output->html = str_replace( '<!--HIDDEN.FIELDS-->', "<input type='hidden' name='settings_save' value='" . implode( ",", $this->key_array ) . "' />", $this->registry->output->html );
	}
	
	/**
	 * Process an individual setting for display
	 *
	 * @access	private
	 * @param	array 	Setting record
	 * @return	void
	 */
	private function _processSettingEntry($r)
	{
		$tempSkinUrl		= $this->settings['skin_app_url'];
		$this->settings[ 'skin_app_url'] =  $this->settings['_original_base_url'] . '/' . CP_DIRECTORY . '/applications/core/skin_cp/' ;

		$form_element  = "";
		$form_type	   = 'normal';
		$dropdown      = array();
		$start         = "";
		$end           = "";
		$revert_button = "";
			
		$key   = $r['conf_key'];
		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
		$value = $value == "{blank}" ? '' : $value;
		
		$show  = 1;
		
		//-----------------------------------------------
		// Default?
		//-----------------------------------------------
		
		$css = "";
		
		if ( $r['conf_value'] != "" and ( $r['conf_value'] != $r['conf_default'] ) )
		{
			$revert_button = "<a class='dropdown-button' href='" . $this->settings['_base_url'] . "&amp;app=core&amp;{$this->form_code}&amp;do=setting_revert&id={$r['conf_id']}&conf_group={$r['conf_group']}&search=" . $this->request['search'] . "' title='{$this->lang->words['s_revertback']}'><img src='".$this->settings['skin_acp_url']."/_newimages/icons/arrow_rotate_anticlockwise.png' alt='X' border='0' /></a></div>";
		}
		
		//-----------------------------------------------
		// Evil eval
		//-----------------------------------------------
		
		if ( $r['conf_evalphp'] )
		{
			$show = 1;
			
			eval( $r['conf_evalphp'] );
		}
		
		if( ! $show && ! IN_DEV )
		{
			return '';
		}
		
		switch( $r['conf_type'] )
		{
			case 'input':
				$form_element = $this->registry->output->formInput( $key, str_replace( "'", "&#39;", $value ) );
				break;
			
			case 'textarea':
				$form_element = $this->registry->output->formTextarea( $key, $value, 45 );
				break;
				
			case 'editor':
				IPSText::getTextClass('bbcode')->parse_html			= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
				IPSText::getTextClass('bbcode')->parse_smilies		= 1;
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parsing_section	= 'global';

				if ( IPSText::getTextClass('editor')->method == 'rte' )
				{
					$value = IPSText::getTextClass('bbcode')->convertForRTE( IPSText::getTextClass('bbcode')->preDbParse( $value ) );
				}
				else
				{
					$value = IPSText::getTextClass('bbcode')->preEditParse( $value );
				}

				$form_element = IPSText::getTextClass('editor')->showEditor( $value, $key );
				$form_type = 'rte';

				break;
				
			case 'yes_no':
				$form_element = $this->registry->output->formYesNo( $key, $value );
				break;
				
			default:
			
				if ( $r['conf_extra'] )
				{
					if ( $r['conf_extra'] == '#show_forums#' )
					{
						//-----------------------------------------
						// Require the library
						// (Not a building with books)
						//-----------------------------------------

						require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php' );
						require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php' );

						$aff = new admin_forum_functions( $this->registry );
						$aff->forumsInit();
						$dropdown = $aff->adForumsForumList(1);
					}
					else if ( $r['conf_extra'] == '#show_groups#' )
					{
						$this->DB->build( array( 'select' => '*', 'from' => 'groups', 'order' => 'g_title ASC' ) );
						$this->DB->execute();
						
						while( $row = $this->DB->fetch() )
						{
							if ( $row['g_access_cp'] )
							{
								$row['g_title'] .= $this->lang->words['setting_staff_tag'];
							}
							
							$dropdown[] = array( $row['g_id'], $row['g_title'] );
						}
					}
					else if ( $r['conf_extra'] == '#show_applications#' )
					{
						foreach( ipsRegistry::$applications as $app )
						{
							$dropdown[] = array( $app['app_directory'], $app['app_title'] );
						}
					}
					else if ( $r['conf_extra'] == '#show_skins#' )
					{
						$dropdown = $this->registry->output->generateSkinDropdown();
					}
					else
					{
						foreach( explode( "\n", $r['conf_extra'] ) as $l )
						{
							list ($k, $v) = explode( "=", $l );
							if ( $k != "" and $v != "" )
							{
								$dropdown[] = array( trim($k), trim($v) );
							}
						}
					}
				}
		
				if ( $r['conf_type'] == 'dropdown' )
				{
					$form_element = $this->registry->output->formDropdown( $key, $dropdown, $value );
				}
				else
				{
					$form_element = $this->registry->output->formMultiDropdown( $key, $dropdown, explode( ",", $value ) );
				}
			
				break;
		}
				
		$delete  = "&nbsp;<a class='dropdown-button' href='#' onclick='return acp.confirmDelete(\"{$this->settings['_base_url']}&amp;app=core&amp;{$this->form_code}&amp;do=setting_delete&id={$r['conf_id']}\");' title='key: {$r['conf_key']}'><img src='" . $this->settings['skin_acp_url'] . "/_newimages/icons/delete.png' alt='{$this->lang->words['s_delete']}' border='0' /></a>";
		$edit    = "<a class='dropdown-button' href='" . $this->settings['_base_url'] . "&amp;app=core&amp;{$this->form_code}&amp;do=setting_showedit&id={$r['conf_id']}' title='id: {$r['conf_id']}'><img src='" . $this->settings['skin_acp_url'] . "/_newimages/icons/pencil.png' alt='{$this->lang->words['s_edit']}' border='0' /></a>";

		if ( $r['conf_protected'] and ! IN_DEV )
		{
			$delete  = "";
			$edit    = "";
		}
		
		if ( $r['conf_start_group'] )
		{
			$start  = $this->html->settings_row_start_group( $r );
		}
		else
		{
			if ( ! in_array( $r['conf_id'], $this->in_group ) and ! $r['conf_end_group'] )
			{
				$start  = $this->html->settings_row_start_normal();
			}
		}
		
		if ( $r['conf_end_group'] )
		{
			$end = $this->html->settings_row_end_group();
		}
		else
		{
			if ( ! in_array( $r['conf_id'], $this->in_group ) and ! $r['conf_start_group'] )
			{
				$end  = $this->html->settings_row_end_normal();
			}
		}
		
		//-----------------------------------------------
		// Search hi-lite
		//-----------------------------------------------
		
		if ( $this->request['search'] )
		{
			$r['conf_title']       = preg_replace( "/(".$this->request['search'].")/i", "<span style='background:#FCFDD7'>\\1</span>", $r['conf_title'] );
			
			/**
			 * Ok this is just annoying....
			 */
			preg_match_all( "/(href=['\"].*?[\"'])/i", $r['conf_description'], $matches );

			if( count($matches[0]) )
			{
				for( $i=0, $cnt=count($matches[0]); $i <= $cnt; $i++ )
				{
					$r['conf_description'] = str_replace( $matches[0][$i], '{{{' . $i . '}}}', $r['conf_description'] );
				}
			}

			$r['conf_description'] = preg_replace( "/(".$this->request['search'].")/i", "<span style='background:#FCFDD7'>\\1</span>", $r['conf_description'] );
			
			if( count($matches[0]) )
			{
				for( $i=0, $cnt=count($matches); $i <= $cnt; $i++ )
				{
					$r['conf_description'] = str_replace( '{{{' . $i . '}}}', $matches[0][$i], $r['conf_description'] );
				}
			}
		}
		
		$html .= $start . $this->html->settings_view_row( $r, $edit, $delete, $form_element, $revert_button, $form_type ) . $end;
		
		$this->key_array[] = preg_replace( "/\[\]$/", "", $key );

		$this->settings[ 'skin_app_url'] =  $tempSkinUrl ;
		
		return $html;
	}
	
	/**
	 * View setting groups
	 *
	 * @access	private
	 * @param	string		Application to default to
	 * @return	void
	 */
	private function _settingsOverview( $start_app='' )
	{
		$content   = "";
		$title     = "";
		$settings  = array();
		$start_app = ( $start_app ) ? $start_app : trim( $this->request['start_app'] );
		
		//-----------------------------------------
		// Get the groups
		//-----------------------------------------
		
		$this->_settingsGetGroups();
		
		//-----------------------------------------
		// Build settings..
		//-----------------------------------------
		
		foreach( $this->setting_groups as $i => $r )
		{
			$r['conf_title_app']   = ( $r['conf_title_app'] )    ? $r['conf_title_app'] : 'core';
			$r['conf_title_tab']   = ( $r['conf_title_tab'] )    ? $r['conf_title_tab'] : 'System';
			$r['conf_title_title'] = ( $r['conf_title_noshow'] ) ? $r['conf_title_title'] . $this->lang->words['s_ishidden'] : $r['conf_title_title'];
			
			$settings[ $r['conf_title_tab'] ][] = $r;
		}

		$this->registry->output->html .= $this->html->settings_titles_wrapper( $this->html->settings_titles_top_searchbox(), $settings, $start_app );
	}
	
	/**
	 * Update setting values/ordering
	 *
	 * @access	private
	 * @param	boolean		Return afterwards?
	 * @return	void
	 */
	private function _updateSettings( $donothing="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$bounceback = str_replace( '&amp;', '&', $this->request['bounceback'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->request['id'] and ! $this->request['search'] AND !$this->request['settings_save'] )
		{
			$this->registry->output->global_message = $this->lang->words['s_noid'];
			$this->_settingsOverview();
			return;
		}
		
		//--------------------------------------------
		// check...
		//--------------------------------------------
		
		$fields = explode( ",", trim($this->request['settings_save']) );
		
		if ( ! count($fields ) )
		{
			$this->registry->output->global_message = $this->lang->words['s_nofields'];
			$this->_viewSettings();
			return;
		}
		
		//--------------------------------------------
		// Get info from DB
		//--------------------------------------------
		
		$db_fields = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key IN ('" . implode( "','", $fields ) . "')" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$db_fields[ $r['conf_key']  ] = $r;
		}
		
		foreach( $db_fields as $key => $data )
		{
			if ( $data['conf_evalphp'] )
			{
//				print htmlspecialchars($data['conf_evalphp']);exit;
				$save = 1;
				eval( $data['conf_evalphp'] );
			}

			if ( $data['conf_type'] == 'editor' )
			{
				IPSText::getTextClass('bbcode')->bypass_badwords	= 1;
				IPSText::getTextClass('bbcode')->parse_smilies		= 1;
				IPSText::getTextClass('bbcode')->parse_html			= 1;
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br        = 1;
				 
		        //$_POST[ $key ]	= IPSText::getTextClass('bbcode')->preDbParse( IPSText::getTextClass('editor')->processRawPost( $key ) );
		        
		        if( trim($_POST[ $key ]) == '<br>' OR trim($_POST[ $key ]) == '<br />' )
		        {
		        	$_POST[ $key ]	= '';
		        }
				else
				{
					$_POST[ $key ] = IPSText::getTextClass('editor')->processRawPost( $key );
				}
			}
			
			$_POST[ $key ]	= str_replace( "\r", "", $_POST[ $key ] );
			
			
			if ( ($_POST[ $key ] != $data['conf_default']) )
			{
				$value = str_replace( "&#39;", "'", IPSText::stripslashes($_POST[ $key ]) );
				$value = $value == '' ? "{blank}" : $value;
				
				$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => $value ), 'conf_id=' . $data['conf_id'] );
			}
			else if( ( isset( $this->request[ $key ] ) && $this->request[ $key ] == '' ) || ( $this->request[ $key ] == $data['conf_default'] ) || $data['conf_value'] != '' )
			{	
				$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), 'conf_id=' . $data['conf_id'] );
			}
		}
		
		$this->request['conf_group'] = $this->request['id'];
		
		$this->registry->output->global_message = $this->lang->words['s_updated'];
		
		$this->settingsRebuildCache();
		
		//-----------------------------------------
		// We're bouncing back (Boing boing)
		//-----------------------------------------
		
		if ( $bounceback )
		{
			$this->registry->output->silentRedirect( $bounceback );
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		if ( ! $donothing )
		{
			$this->_viewSettings();
		}
	}
	
	/**
	 * Save the settings form
	 *
	 * @access	private
	 * @param	string		[add|edit]
	 * @return	void
	 */
	private function _settingSave($type='add')
	{	
		if ( $type == 'edit' )
		{
			if ( ! $this->request['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['s_noid'];
				$this->_settingForm();
				return;
			}
		}
		
		//--------------------------------------------
		// check...
		//--------------------------------------------
		
		$conf_group = $this->request['conf_newgroup'] ? $this->request['conf_newgroup'] : $this->request['conf_group'];
		
		$array = array( 'conf_title'		=> $this->request['conf_title'],
						'conf_description'	=> IPSText::stripslashes( $_POST['conf_description'] ),
						'conf_group'		=> $this->request['conf_group'],
						'conf_type'			=> $this->request['conf_type'],
						'conf_key'			=> $this->request['conf_key'],
						'conf_value'		=> IPSText::stripslashes( $_POST['conf_value'] ),
						'conf_default'		=> IPSText::stripslashes( $_POST['conf_default'] ),
						'conf_extra'		=> IPSText::stripslashes( $_POST['conf_extra'] ),
						'conf_evalphp'		=> IPSText::stripslashes( $_POST['conf_evalphp'] ),
						'conf_protected'	=> intval( $this->request['conf_protected'] ),
						'conf_position'		=> intval( $this->request['conf_position'] ),
						'conf_start_group'	=> $this->request['conf_start_group'],
						'conf_end_group'	=> $this->request['conf_end_group'],
						'conf_add_cache'	=> intval( $this->request['conf_add_cache'] ),
						'conf_keywords'		=> IPSText::stripslashes( $_POST['conf_keywords'] ),
					 );
						
		if ( $type == 'add' )
		{
			$this->DB->insert( 'core_sys_conf_settings', $array );
			$this->registry->output->global_message = $this->lang->words['s_added2'];
		}
		else
		{
			$conf = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_id=' . $this->request['id'] ) );
			
			$this->DB->update( 'core_sys_conf_settings', $array, 'conf_id=' . $this->request['id'] );
			$this->registry->output->global_message = $this->lang->words['s_edited2'];
			
			// Recount old group
			$this->_resynchGroup( $conf['conf_group'] );
		}

		//-----------------------------------------
		// Recount new group
		//-----------------------------------------
		
		if( $this->request['conf_group'] )
		{
			$this->_resynchGroup( $this->request['conf_group'] );
		}
		
		$this->settingsRebuildCache();
		
		$this->_viewSettings();
	}
	
	/**
	 * Revert a setting to the default value
	 *
	 * @access	private
	 * @return	void
	 */
	private function _revertSettings()
	{
		$this->request[ 'id'] =  intval($this->request['id'] );
		
		if ( ! $this->request['id'] )
		{
			$this->registry->output->global_message = $this->lang->words['s_noid'];
			$this->_settingForm();
			return;
		}
		
		$conf = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_id=' . $this->request['id'] ) );
		
		//--------------------------------------------
		// Revert...
		//--------------------------------------------
		
		$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), 'conf_id=' . $this->request['id'] );
		
		$this->registry->output->global_message = $this->lang->words['s_revertedback'];
		
		$this->settingsRebuildCache();
		
		$this->_viewSettings();
	}
	
	/**
	 * Delete a setting
	 *
	 * @access	private
	 * @return	void
	 */
	private function _deleteSettings()
	{	
		if ( ! $this->request['id'] )
		{
			$this->registry->output->global_message = $this->lang->words['s_noid'];
			$this->_settingsOverview();
			return;
		}
		
		$conf = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_id=' . $this->request['id'] ) );
		
		if ( ! $conf['conf_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['s_noid'];
			$this->_settingsOverview();
			return;
		}
		
		//--------------------------------------------
		// Delete...
		//--------------------------------------------
		
		$this->DB->delete( 'core_sys_conf_settings', 'conf_id=' . $this->request['id'] );
		
		$this->DB->update( 'core_sys_settings_titles', 'conf_title_count=conf_title_count-1', 'conf_title_id=' . $conf['conf_group'], false, true );
		
		$this->registry->output->global_message = $this->lang->words['s_deleted'];
		
		$this->settingsRebuildCache();
		
		$this->_settingsOverview();
	}
	
	
	/**
	 * Rebuild settings cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function settingsRebuildCache()
	{
		$settings = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => 'conf_add_cache=1' ) );
		$info = $this->DB->execute();
	
		while ( $r = $this->DB->fetch($info) )
		{	
			$value = $r['conf_value'] != "" ?  $r['conf_value'] : $r['conf_default'];
			
			if ( $value == '{blank}' )
			{
				$value = '';
			}

			$settings[ $r['conf_key'] ] = $value;
		}
		
		$this->cache->setCache( 'settings', $settings, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Grab all setting groups and store in an internal array
	 *
	 * @access	private
	 * @param	boolean		Pull all settings, not just visible ones
	 * @return	void
	 */
	private function _settingsGetGroups( $ignoreInDev=false )
	{
		$this->setting_groups = array();
		
		if ( IN_DEV OR $ignoreInDev )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'order' => 'conf_title_title' ) );
			$this->DB->execute();
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'where' => 'conf_title_noshow=0', 'order' => 'conf_title_title' ) );
			$this->DB->execute();
		}
		
		while( $r = $this->DB->fetch() )
		{
			$this->setting_groups[ $r['conf_title_id'] ] = $r;
			$this->setting_groups_by_key[ $r['conf_title_keyword'] ] = $r;
		}
	}
	
	/**
	 * Make sure all titles have keywords set
	 *
	 * @access	private
	 * @return	void
	 */
	private function _settingsTitlesCheck()
	{
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles' ) );
		$outer = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $outer ) )
		{
			if ( ! $row['conf_title_keyword'] )
			{
				$new_keyword = strtolower( preg_replace( "#[^\d\w]#", "", $row['conf_title_title'] ) );
				$this->DB->update( 'core_sys_settings_titles', array( 'conf_title_keyword' => $new_keyword ), 'conf_title_id='.$row['conf_title_id'] );
			}
		}
	}
	
	/**
	 * Import XML Settings on an app by app basis
	 *
	 * @access	public
	 * @return	array 		Array of messages
	 */
	public function importAllApps()
	{
		$message = array();
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_settings.xml';
			
			if ( file_exists( $file ) )
			{
				$contents = file_get_contents( $file );
				
				$return = $this->_importXML( $contents, $app_dir );
				
				$message[] = $app_data['app_title'] . " " . sprintf( $this->lang->words['s_upandin'], $return['updatedCount'], $return['insertedCount'] );
				
				/* In dev time stamp? */
				if ( IN_DEV )
				{
					$cache = $this->caches['indev'];
					$cache['import']['settings'][ $app_dir ] = time();
					$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
				}
			}
		}
		
		return $message;
	}

	/**
	 * Import XML Settings
	 *
	 * @access	private
	 * @param	string		XML Data
	 * @param	string		Application (should be set by XML file, however)
	 * @param	array 		Array of any known settings and their values
	 * @return	array 		array( 'insertedCount' => x, 'updatedCount' => x, 'updatedKeys' => array(..), 'insertedKeys' => array() )
	 */
	private function _importXML( $content, $app='core', $knownSettings=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cur_settings = array();
		$updated      = 0;
		$inserted     = 0;
		$updatedKeys  = array();
		$insertedKeys = array();
		$known        = array();
		
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
		
		//-----------------------------------------
		// Get current titles
		//-----------------------------------------
		
		$this->_settingsGetGroups( true );
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
			
		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------

		foreach( $xml->fetchElements('setting') as $setting )
		{
			$entry  = $xml->fetchElementsFromRecord( $setting );

			//-----------------------------------------
			// Is setting?
			//-----------------------------------------
			
			if ( ! $entry['conf_is_title'] )
			{
				unset( $entry['conf_is_title'] );
				unset( $entry['conf_help_key'] );
				unset( $entry['conf_id'] );
				$new_settings[] = $entry;
			}
			
			//-----------------------------------------
			// Is title?
			//-----------------------------------------
			
			else
			{
				$new_titles[] = $entry;
			}
		}

		//-----------------------------------------
		// Sort out titles...
		//-----------------------------------------
		
		if ( is_array( $new_titles ) and count( $new_titles ) )
		{
			foreach( $new_titles as $idx => $data )
			{
				if ( $data['conf_title_title'] AND $data['conf_title_keyword'] )
				{
					//-----------------------------------------
					// Get ID based on key
					//-----------------------------------------
					
					$conf_id = $this->setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];
					
					$save = array( 'conf_title_title'   => $data['conf_title_title'],
								   'conf_title_desc'    => $data['conf_title_desc'],
								   'conf_title_keyword' => $data['conf_title_keyword'],
								   'conf_title_tab' 	=> $data['conf_title_tab'],
								   'conf_title_app'     => $data['conf_title_app'] ? $data['conf_title_app'] : $app,
								   'conf_title_noshow'  => $data['conf_title_noshow']  );
					
					//-----------------------------------------
					// Not got a row, insert first!
					//-----------------------------------------
					
					if ( ! $conf_id )
					{
						$this->DB->insert( 'core_sys_settings_titles', $save );
						$conf_id = $this->DB->getInsertId();
						
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
					
					$save['conf_title_id']                                      = $conf_id;
					$this->setting_groups_by_key[ $save['conf_title_keyword'] ] = $save;
					$this->setting_groups[ $save['conf_title_id'] ]             = $save;
						
					//-----------------------------------------
					// Remove need update...
					//-----------------------------------------
					
					$need_update[] = $conf_id;
				}
			}
		}
		
		//-----------------------------------------
		// Sort out settings
		//-----------------------------------------

		if ( is_array( $new_settings ) and count( $new_settings ) )
		{
			foreach( $new_settings as $idx => $data )
			{
				//-----------------------------------------
				// Insert known
				//-----------------------------------------

				$data['conf_value'] = '';
				
				if( is_array($knownSettings) AND count($knownSettings) )
				{
					if ( ! $data['conf_value'] AND ( in_array( $data['conf_key'], array_keys( $knownSettings ) ) ) )
					{
						$data['conf_value'] = $knownSettings[ $data['conf_key'] ];
					}
				}
				
				$data['conf_group'] = $this->setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];
				
				//-----------------------------------------
				// Remove from array
				//-----------------------------------------
				
				unset( $data['conf_title_keyword'] );
				
				if ( $cur_settings[ $data['conf_key'] ] )
				{
					//-----------------------------------------
					// Don't change the setting value
					//-----------------------------------------					
					unset( $data['conf_value'] );
				
					//-----------------------------------------
					// Update
					//-----------------------------------------
					
					$this->DB->update( 'core_sys_conf_settings', $data, 'conf_id='.$cur_settings[ $data['conf_key'] ] );
					$updatedKeys[] = $data['conf_key'];
					$updated++;
				}
				else
				{
					//-----------------------------------------
					// INSERT
					//-----------------------------------------
					
					$this->DB->insert( 'core_sys_conf_settings', $data );
					$insertedKeys[] = $data['conf_key'];
					$inserted++;
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

		//-----------------------------------------
		// Resync
		//-----------------------------------------
		
		$this->settingsRebuildCache();
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return array( 'insertedCount' => $inserted,
					  'updatedCount'  => $updated,
					  'insertedKeys'  => $insertedKeys,
					  'updatedKeys'   => $updatedKeys );
	}
	
	/**
	 * Export all apps: Wrapper function really. Yes. It is.
	 *
	 * @access	public
	 * @return	array	Array of messages or errors
	 */
	public function exportAllApps()
	{
		$messages = array();
		$errors   = array();
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			@unlink( IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_settings.xml' );
			
			if ( ! file_exists( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
			{
				$errors[] = "Error: " . IPSLib::getAppDir(  $app_dir ) . "/xml/ does not exist";
				continue;
			}
			else if ( ! IPSLib::isWritable( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
			{
				if ( ! @chmod( IPSLib::isWritable( IPSLib::getAppDir(  $app_dir ) . '/xml', 0755 ) ) )
				{
					$errors[] = "Error: " . IPSLib::getAppDir(  $app_dir ) . "/xml/ is not writeable";
					continue;
				}
			}
			else if ( file_exists( IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_settings.xml' ) AND ! IPSLib::isWritable( IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_settings.xml' ) )
			{
				$errors[] = "Error: " . IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . "_settings.xml is not writeable";
				continue;
			}
				
			$this->_exportXML( $app_dir );
				
			$messages[] = $app_data['app_title'] . " Settings written into the application's XML directory";
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['settings'][ $app_dir ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
		
		return $errors ? $errors : $messages;
	}
	
	
	/**
	 * Export all settings to XML (IN_DEV mode)
	 *
	 * @access	private
	 * @param	string		Application directory
	 * @return	boolean
	 */
	private function _exportXML( $app_dir='core' )
	{
		//-----------------------------------------
		// Get setting groups
		//-----------------------------------------
		
		$this->_settingsGetGroups( true );
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'settingexport' );
		$xml->addElement( 'settinggroup', 'settingexport' );

		foreach( $this->setting_groups as $i => $roar )
		{
			//-----------------------------------------
			// App check?
			//-----------------------------------------
			
			if ( $app_dir != $roar['conf_title_app'] )
			{
				continue;
			}
			
			//-----------------------------------------
			// First, add in setting group title
			//-----------------------------------------
			
			$thisconf = array( 'conf_is_title'      => 1,
							   'conf_title_keyword' => $roar['conf_title_keyword'],
							   'conf_title_title'   => $roar['conf_title_title'],
							   'conf_title_desc'    => $roar['conf_title_desc'],
							   'conf_title_tab' 	=> $roar['conf_title_tab'],
							   'conf_title_app'     => $roar['conf_title_app'] ? $roar['conf_title_app'] : $app_dir,
							   'conf_title_noshow'  => $roar['conf_title_noshow'] );
			
			$xml->addElementAsRecord( 'settinggroup', 'setting', $thisconf );
			
			//-----------------------------------------
			// Get settings...
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => '*',
														  'from'   => 'core_sys_conf_settings',
														  'where'  => "conf_group='{$roar['conf_title_id']}'",
														  'order'  => 'conf_position, conf_title' ) );
			
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				//-----------------------------------------
				// Clean up...
				//-----------------------------------------
				
				unset( $r['conf_value'], $r['conf_id'] );
				
				$r['conf_title_keyword'] = $roar['conf_title_keyword'];
				$r['conf_is_title']      = 0;
				
				$xml->addElementAsRecord( 'settinggroup', 'setting', $r );
			}
		}
		
		//-----------------------------------------
		// Grab the XML document
		//-----------------------------------------
		
		$xmlData = $xml->fetchDocument();
		
		//-----------------------------------------
		// Attempt to write...
		//-----------------------------------------
		
		$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_settings.xml';
		
		@unlink( $file );
		@file_put_contents( $file, $xmlData );
	}

}