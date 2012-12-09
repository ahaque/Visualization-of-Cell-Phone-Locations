<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member property updater (AJAX)
 * Last Updated: $Date: 2009-05-20 09:25:05 -0400 (Wed, 20 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 4674 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_hooks extends ipsAjaxCommand 
{
	/**
	 * Cache object
	 *
	 * @access	public
	 * @var		object
	 */
	public $cache;	
	
	/**
	 * Hook ID
	 *
	 * @access	public
	 * @var		integer
	 */
	public $hookId;	
	
	/**
	 * Hook data
	 *
	 * @access	public
	 * @var		array
	 */
	public $hook;	
	
	/**
	 * Hook's export settings
	 *
	 * @access	public
	 * @var		array
	 */
	public $data;
	
	/**
	 * Hooks library
	 *
	 * @access	private
	 * @var		object			Hooks library
	 */
	private $hooksFunctions;

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
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_hooks_export');
		
		//-----------------------------------------
		// Load hooks library
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('core') . '/sources/classes/hooksFunctions.php' );
		$this->hooksFunctions	= new hooksFunctions( $registry );
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_hooks' ) );
		
    	switch( $this->request['do'] )
    	{
    		case 'getStrings':
    			$this->_getAjaxStrings();
    		break;
    		
    		case 'getLangFiles':
    			$this->_getAjaxFiles();
    		break;
    		
    		case 'getTemplates':
    			$this->_getAjaxTemplates();
    		break;
    		
    		case 'getSkinFiles':
    			$this->_getAjaxSkins();
    		break;
    		
    		// These are for the add file form
    		case 'getGroupsForAdd':
    			$this->_getGroupsForAdd();
    		break;
    		
    		case 'getTemplatesForAdd':
    			$this->_getTemplatesForAdd();
    		break;
    		
    		case 'getHookIds':
    			$this->_getHookIds();
    		break;
    		
    		case 'save':
    			$this->save();
    		break;
    		
			case 'show':
			default:
				$this->show();
			break;
    	}
	}
	
	/**
	 * Get all hook ids for a template/type
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getHookIds()
	{
		$i				= intval( $this->request['i'] );
		$template		= IPSText::alphanumericalClean( $this->request['template'] );
		$type			= IPSText::alphanumericalClean( $this->request['type'] );
		$return			= $this->hooksFunctions->getHookIds( $template, $type );

		$output			= $this->registry->output->formDropdown( "id[{$i}]", $return, null, "id[{$i}]", "onchange='getHookEntryPoints({$i});'" );

		$this->returnHtml( $output );
	}
	
	/**
	 * Get all skin files
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getGroupsForAdd()
	{
		$i				= intval( $this->request['i'] );

		$_skinFiles		= $this->hooksFunctions->getSkinGroups();
		$output			= $this->registry->output->formDropdown( "skinGroup[{$i}]", $_skinFiles, null, "skinGroup[{$i}]", "onchange='getTemplatesForAdd({$i});'" );

		$this->returnHtml( $output );
	}
	
	/**
	 * Get all skin templates
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getTemplatesForAdd()
	{
		$i				= intval( $this->request['i'] );
		$group			= IPSText::alphanumericalClean( $this->request['group'] );

		$_strings		= $this->hooksFunctions->getSkinMethods( $group, true );
		$output			= $this->registry->output->formDropdown( "skinFunction[{$i}]", $_strings, null, "skinFunction[{$i}]", "onchange='getTypeOfHook({$i});'" );

		$this->returnHtml( $output );
	}
	
	/**
	 * Get language strings in a given file
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getAjaxStrings()
	{
		$group			= IPSText::alphanumericalClean( $this->request['group'] );
		$i				= intval( $this->request['i'] );
		$hook			= intval( $this->request['id'] );
		
		$this->hookId	= intval( $this->request['id'] );
		$this->hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $this->hookId ) );
		$this->data		= unserialize($this->hook['hook_extra_data']);
		
		$_strings		= $this->hooksFunctions->getStrings( $group );
		$output			= $this->registry->output->formMultiDropdown( "strings_{$i}[]", $_strings, $this->data['language'][ $group ], 5, "strings_{$i}", "", "' style='width: 100%'" );

		$this->returnHtml( $output );
	}
	
	/**
	 * Get all language files
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getAjaxFiles()
	{
		$i				= intval( $this->request['i'] );
		$hook			= intval( $this->request['id'] );
		
		$this->hookId	= intval( $this->request['id'] );
		$this->hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $this->hookId ) );
		$this->data		= unserialize($this->hook['hook_extra_data']);
		
		$_langFiles		= $this->hooksFunctions->getLanguageFiles();
		$output			= $this->registry->output->formDropdown( "language_{$i}", $_langFiles, null, "language_{$i}", "onchange='acp.hooks.generateStrings({$i});'" );

		$this->returnHtml( $output );
	}
	
	/**
	 * Get skin templates in a given file
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getAjaxTemplates()
	{
		$group			= IPSText::alphanumericalClean( $this->request['group'] );
		$i				= intval( $this->request['i'] );
		$hook			= intval( $this->request['id'] );
		
		$this->hookId	= intval( $this->request['id'] );
		$this->hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $this->hookId ) );
		$this->data		= unserialize($this->hook['hook_extra_data']);
		
		$_strings		= $this->hooksFunctions->getSkinMethods( $group );
		$output			= $this->registry->output->formMultiDropdown( "templates_{$i}[]", $_strings, $this->data['templates'][ $group ], 5, "templates_{$i}", "", "' style='width: 100%'" );

		$this->returnHtml( $output );
	}
	
	/**
	 * Get all skin files
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _getAjaxSkins()
	{
		$i				= intval( $this->request['i'] );
		$hook			= intval( $this->request['id'] );
		
		$this->hookId	= intval( $this->request['id'] );
		$this->hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $this->hookId ) );
		$this->data		= unserialize($this->hook['hook_extra_data']);
		
		$_skinFiles		= $this->hooksFunctions->getSkinGroups();
		$output			= $this->registry->output->formDropdown( "skin_{$i}", $_skinFiles, null, "skin_{$i}", "onchange='acp.hooks.generateTemplates({$i});'" );

		$this->returnHtml( $output );
	}
		
	/**
	 * Save the form
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function save()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$name			= trim( IPSText::alphanumericalClean( $this->request['name'] ) );
		$this->hookId	= intval( $this->request['id'] );
		$this->hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $this->hookId ) );
		$this->data		= unserialize($this->hook['hook_extra_data']);
		
		$output			= '';

		//-----------------------------------------
		// Got a hook?
		//-----------------------------------------
		
		if ( ! $this->hook['hook_id'] )
		{
			$this->returnJsonError( $this->lang->words['hook_cannot_load'] );
		}
		
		//-----------------------------------------
		// Run the proper operation
		//-----------------------------------------

		switch( $name )
		{	
			case 'settings':
				$_settingGroups		= $this->hooksFunctions->getSettingGroups();
				$_settings			= $this->hooksFunctions->getSettings();
				$toSave				= array();
				$toDisplay			= array();
				
				$toSave['settingGroups']		= array();
				$toSave['settings']				= array();
				
				if( is_array($this->request['setting_groups']) AND count($this->request['setting_groups']) )
				{
					 $toSave['settingGroups']		= $this->request['setting_groups'];
					 
					 foreach( $_settingGroups as $data )
					 {
					 	if( in_array( $data[0], $toSave['settingGroups'] ) )
					 	{
					 		$toDisplay['settingGroups'][] = $data[1];
					 	}
					 }
				}
				
				if( is_array($this->request['settings']) AND count($this->request['settings']) )
				{
					 $toSave['settings']			= $this->request['settings'];
					 
					 foreach( $_settings as $data )
					 {
					 	if( in_array( $data[0], $toSave['settings'] ) )
					 	{
					 		$toDisplay['settings'][] = $data[1];
					 	}
					 }
				}
				
				$this->data['settingGroups']		= $toSave['settingGroups'];
				$this->data['settings']				= $toSave['settings'];
				
				if( count($toDisplay['settingGroups']) )
				{
					$output .= "{$this->lang->words['hook_setting_groups']} " . implode( ', ', $toDisplay['settingGroups'] );
				}

				if( count($toDisplay['settings']) )
				{
					if( count($toDisplay['settingGroups']) )
					{
						$output .= '<br />';
					}

					$output .= "{$this->lang->words['hook_settings']} " . implode( ', ', $toDisplay['settings'] );
				}
				
				$this->data['display']['settings']	= $output;
				
				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_settings'];
				}
			break;
			
			case 'language':
				$_langFiles				= $this->hooksFunctions->getLanguageFiles();
				$ids					= array();
				$files					= array();
				$strings				= array();
				$toDisplay				= array();
				$this->data['language']	= array();
				
				foreach( $_POST as $k => $v )
				{
					if( preg_match( "/^language_(\d+)$/", $k, $matches ) )
					{
						$files[ $matches[1] ]	= $v;
						$strings[ $matches[1] ]	= $this->request[ 'strings_' . $matches[1] ];
						$ids[ $matches[1] ]		= $matches[1];
					}
				}

				foreach( $ids as $id )
				{
					if( $files[ $id ] AND $strings[ $id ] )
					{
						$this->data['language'][ $files[ $id ] ] = $strings[ $id ];
						$toDisplay[]	= "{$this->lang->words['hook_from']} {$files[ $id ]}: " . implode( ', ', $strings[ $id ] );
					}
				}

				if( count($toDisplay) )
				{
					$output .= implode( '<br />', $toDisplay );
				}
				
				$this->data['display']['language']	= $output;

				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_language'];
				}
			break;
			
			case 'modules':
				$_modules			= $this->hooksFunctions->getModules();
				$toSave				= array();
				$toDisplay			= array();

				if( is_array($this->request['modules']) AND count($this->request['modules']) )
				{
					 $toSave		= $this->request['modules'];
				}

				foreach( $_modules as $data )
				{
				 	if( in_array( $data[0], $toSave ) )
				 	{
				 		$toDisplay[] = $data[1];
				 	}
				}

				if( count($toDisplay) )
				{
					$output .= "{$this->lang->words['hook_modules']} " . implode( ', ', $toDisplay );
				}

				$this->data['modules']				= $toSave;
				$this->data['display']['modules']	= $output;
				
				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_modules'];
				}
			break;
			
			case 'help':
				$_help				= $this->hooksFunctions->getHelpFiles();
				$toSave				= array();
				$toDisplay			= array();

				if( is_array($this->request['help']) AND count($this->request['help']) )
				{
					 $toSave		= $this->request['help'];
				}
				
				foreach( $_help as $data )
				{
					if( in_array( $data[0], $toSave ) )
					{
						$toDisplay[] = $data[1];
					}
				}

				if( count($toDisplay) )
				{
					$output .= "{$this->lang->words['hook_help']} " . implode( ', ', $toDisplay );
				}

				$this->data['help']				= $toSave;
				$this->data['display']['help']	= $output;
				
				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_help'];
				}
			break;

			case 'skins':
				$_skinFiles					= $this->hooksFunctions->getSkinGroups();
				$ids						= array();
				$files						= array();
				$templates					= array();
				$toDisplay					= array();
				$this->data['templates']	= array();
				
				foreach( $_POST as $k => $v )
				{
					if( preg_match( "/^skin_(\d+)$/", $k, $matches ) )
					{
						$files[ $matches[1] ]		= $v;
						$templates[ $matches[1] ]	= $this->request[ 'templates_' . $matches[1] ];
						$ids[ $matches[1] ]			= $matches[1];
					}
				}

				foreach( $ids as $id )
				{
					if( $files[ $id ] AND $templates[ $id ] )
					{
						$this->data['templates'][ $files[ $id ] ] = $templates[ $id ];
						$toDisplay[]	= "{$this->lang->words['hook_from']} {$files[ $id ]}: " . implode( ', ', $templates[ $id ] );
					}
				}

				if( count($toDisplay) )
				{
					$output .= implode( '<br />', $toDisplay );
				}

				$this->data['display']['templates']	= $output;
				
				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_skin'];
				}
			break;
			
			case 'tasks':
				$_tasks				= $this->hooksFunctions->getTasks();
				$toSave				= array();
				$toDisplay			= array();

				if( is_array($this->request['tasks']) AND count($this->request['tasks']) )
				{
					 $toSave		= $this->request['tasks'];
				}
				
				foreach( $_tasks as $data )
				{
					if( in_array( $data[0], $toSave ) )
					{
						$toDisplay[] = $data[1];
					}
				}

				if( count($toDisplay) )
				{
					$output .= "{$this->lang->words['hook_tasks']} " . implode( ', ', $toDisplay );
				}

				$this->data['tasks']				= $toSave;
				$this->data['display']['tasks']		= $output;
				
				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_tasks'];
				}
			break;
			
			case 'database':
				$types				= array(
											array( '0', $this->lang->words['hook_db_select'] ),
											array( 'create', $this->lang->words['hook_db_create'] ),
											array( 'alter', $this->lang->words['hook_db_alter'] ), 
											array( 'update', $this->lang->words['hook_db_update'] ), 
											array( 'insert', $this->lang->words['hook_db_insert'] ),
											);
				$alters				= array(
											array( 'add', $this->lang->words['hook_db_addnew'] ),
											array( 'change', $this->lang->words['hook_db_change'] ), 
											array( 'remove', $this->lang->words['hook_db_drop'] ), 
											);
				$ids						= array();
				$toDisplay					= array();
				$this->data['database']		= array();
				
				/* Since this is more complicated, just get ids for now... */
				foreach( $_POST as $k => $v )
				{
					if( preg_match( "/^type_(\d+)$/", $k, $matches ) )
					{
						$ids[ $matches[1] ]			= $matches[1];
					}
				}

				/* Now loop through and set stuff properly.. */
				foreach( $ids as $id )
				{
					$type	= $this->request[ 'type_' . $id ];
					
					if( !$type )
					{
						continue;
					}
					
					switch( $type )
					{
						case 'create':
							$name		= $this->request[ 'name_' . $id ];
							$fields		= IPSText::br2nl( $_POST[ 'fields_' . $id ] );
							$tabletype	= $this->request[ 'tabletype_' . $id ];

							if( !$name OR !$fields )
							{
								continue;
							}
							
							$this->data['database']['create'][]	= array(
																		'name'		=> $name,
																		'fields'	=> $fields,
																		'tabletype'	=> $tabletype
																		);

							$text	= "CREATE TABLE {$name} (
										{$fields}
										)";
							
							if( $tabletype )
							{
								$text .= " TYPE=" . $tabletype;
							}
							
							$toDisplay[] = nl2br($text);
						break;
						
						case 'alter':
							$altertype		= $this->request[ 'altertype_' . $id ];
							$table			= $this->request[ 'table_' . $id ];
							$field			= $this->request[ 'field_' . $id ];
							$newfield		= $this->request[ 'newfield_' . $id ];
							$fieldtype		= $this->request[ 'fieldtype_' . $id ];
							$default		= $_POST[ 'default_' . $id ];
							
							if( !$altertype OR !$table OR !$field )
							{
								continue;
							}
							
							$this->data['database']['alter'][]	= array(
																		'altertype'		=> $altertype,
																		'table'			=> $table,
																		'field'			=> $field,
																		'newfield'		=> $newfield,
																		'fieldtype'		=> $fieldtype,
																		'default'		=> $default,
																		);

							$text	= "ALTER TABLE {$table}";
							
							switch( $altertype )
							{
								case 'add':
									$text .= " ADD {$field} {$fieldtype}";
									
									if( $default !== '' )
									{
										$text .= " DEFAULT {$default}";
									}
								break;
								
								case 'change':
									$text .= " CHANGE {$field} {$newfield} {$fieldtype}";
									
									if( $default !== '' )
									{
										$text .= " DEFAULT {$default}";
									}
								break;
								
								case 'remove':
									$text .= " DROP {$field}";
								break;
							}
							
							$toDisplay[] = nl2br($text);
						break;
						
						case 'update':
							$table		= $this->request[ 'table_' . $id ];
							$field		= $this->request[ 'field_' . $id ];
							$newvalue	= $_POST[ 'newvalue_' . $id ];
							$oldvalue	= $_POST[ 'oldvalue_' . $id ];
							$where		= $_POST[ 'where_' . $id ];
							
							if( !$table OR !$field OR !$newvalue )
							{
								continue;
							}
							
							$this->data['database']['update'][]	= array(
																		'table'		=> $table,
																		'field'		=> $field,
																		'newvalue'	=> $newvalue,
																		'oldvalue'	=> $oldvalue,
																		'where'		=> $where
																		);

							$text	= "UPDATE {$table} SET {$field}='{$newvalue}'";
							
							if( $where )
							{
								$text .= " WHERE " . $where;
							}
							
							$toDisplay[] = nl2br($text);
						break;
						
						case 'insert':
							$table		= $this->request[ 'table_' . $id ];
							$updates	= $_POST[ 'updates_' . $id ];
							$fordelete	= $_POST[ 'fordelete_' . $id ];
							
							if( !$table OR !updates )
							{
								continue;
							}
							
							$this->data['database']['insert'][]	= array(
																		'table'		=> $table,
																		'updates'	=> $updates,
																		'fordelete'	=> $fordelete,
																		);

							$cols	= array();
							$vals	= array();
							$index	= 0;
							
							$toins	= explode( ',', $updates );
							
							foreach( $toins as $insertQuery )
							{
								$piece			= explode( '=', $insertQuery );
								$cols[ $index ]	= $piece[0];
								$vals[ $index ]	= $piece[1];
								$index++;
							}
							
							$text	= "INSERT INTO {$table} (";
							$text	.= implode( ', ', $cols );
							$text	.= ") VALUES ('";
							$text	.= implode( "', '", $vals );
							$text	.= "')";
							
							$toDisplay[] = nl2br($text);
						break;
					}
				}

				if( count($toDisplay) )
				{
					$output .= implode( '<br />', $toDisplay );
				}
				
				$this->data['display']['database'] = $output;

				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_db'];
				}

			break;
			
			case 'custom':
				$toSave				= '';
				$toDisplay			= '';

				if( $this->request['custom'] )
				{
					 $toSave		= $this->request['custom'];
					 $toDisplay 	= $this->request['custom'];
				}

				if( $toDisplay )
				{
					$output .= "install_" . $toDisplay;
				}

				$this->data['custom']				= $toSave;
				$this->data['display']['custom']	= $output;
				
				$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize($this->data) ), 'hook_id=' . $this->hookId );
				
				if( !$output )
				{
					$output = $this->lang->words['hook_no_custom'];
				}
			break;
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$return	= array( 'success' => true, 'message' => $output );

		$this->returnJsonArray( $return );
	}

	/**
	 * Show the form
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function show()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$name			= trim( IPSText::alphanumericalClean( $this->request['name'] ) );
		$this->hookId	= intval( $this->request['id'] );
		$this->hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $this->hookId ) );
		$this->data		= unserialize($this->hook['hook_extra_data']);
		
		$output			= '';

		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! $this->hook['hook_id'] )
		{
			$this->returnJsonError( $this->lang->words['hook_cannot_load'] );
		}
		
		//-----------------------------------------
		// Run the proper operation
		//-----------------------------------------

		switch( $name )
		{	
			case 'settings':
				$_settingGroups		= $this->hooksFunctions->getSettingGroups();
				$_settings			= $this->hooksFunctions->getSettings();
				
				$form				= array();
				$form['groups']		= $this->registry->output->formMultiDropdown( "setting_groups[]", $_settingGroups, $this->data['settingGroups'], 5, "setting_groups" );
				$form['settings']	= $this->registry->output->formMultiDropdown( "settings[]", $_settings, $this->data['settings'], 5, "settings" );

				$output = $this->html->inline_settings( $this->hook, $form );
			break;
			
			case 'languages':
				// We'll show the lang files and let them select lang file, then select strings, then they can repeat
				$_langFiles			= $this->hooksFunctions->getLanguageFiles();
				$i					= 1;
				$form				= array();
				
				if( count($this->data['language']) )
				{
					foreach( $this->data['language'] as $file => $strings )
					{
						$form["language_file_{$i}"]		= $this->registry->output->formDropdown( "language_{$i}", $_langFiles, $file, "language_{$i}", "onchange='acp.hooks.generateStrings({$i});'" );
						
						$_strings						= $this->hooksFunctions->getStrings( $file );
						$form["language_strings_{$i}"]	= $this->registry->output->formMultiDropdown( "strings_{$i}[]", $_strings, $strings, 5, "strings_{$i}", "", "' style='width: 100%'" );
						
						$i++;
					}
				}
				
				$form["language_file_{$i}"]		= $this->registry->output->formDropdown( "language_{$i}", $_langFiles, null, "language_{$i}", "onchange='acp.hooks.generateStrings({$i});'" );

				$output = $this->html->inline_languages( $this->hook, $form, $i );
			break;
			
			case 'modules':
				$_modules			= $this->hooksFunctions->getModules();
				
				$form				= array();
				$form['modules']	= $this->registry->output->formMultiDropdown( "modules[]", $_modules, $this->data['modules'], 5, "modules" );

				$output = $this->html->inline_modules( $this->hook, $form );
			break;
			
			case 'help':
				$_help				= $this->hooksFunctions->getHelpFiles();
				
				$form				= array();
				$form['help']		= $this->registry->output->formMultiDropdown( "help[]", $_help, $this->data['help'], 5, "help" );

				$output = $this->html->inline_help( $this->hook, $form );
			break;

			case 'skins':
				// We'll show the skin groups and let them select skin file, then select templates, then they can repeat
				$_skinFiles			= $this->hooksFunctions->getSkinGroups();
				$i					= 1;
				$form				= array();
				
				if( count($this->data['templates']) )
				{
					foreach( $this->data['templates'] as $file => $methods )
					{
						$form["skin_file_{$i}"]		= $this->registry->output->formDropdown( "skin_{$i}", $_skinFiles, $file, "skin_{$i}", "onchange='acp.hooks.generateTemplates({$i});'" );
						
						$_methods					= $this->hooksFunctions->getSkinMethods( $file );
						$form["skin_method_{$i}"]	= $this->registry->output->formMultiDropdown( "templates_{$i}[]", $_methods, $methods, 5, "templates_{$i}", "", "' style='width: 100%'" );
						
						$i++;
					}
				}
				
				$form["skin_file_{$i}"]		= $this->registry->output->formDropdown( "skin_{$i}", $_skinFiles, null, "skin_{$i}", "onchange='acp.hooks.generateTemplates({$i});'" );

				$output = $this->html->inline_skins( $this->hook, $form, $i );
			break;
			
			case 'tasks':
				$_tasks				= $this->hooksFunctions->getTasks();
				
				$form				= array();
				$form['tasks']		= $this->registry->output->formMultiDropdown( "tasks[]", $_tasks, $this->data['tasks'], 5, "tasks" );

				$output = $this->html->inline_tasks( $this->hook, $form );
			break;
			
			case 'database':
				// First we'll show their current DB changes, then give them a dropdown to add another
				$i					= 1;
				$form				= array();
				$types				= array(
											array( '0', $this->lang->words['hook_db_select'] ),
											array( 'create', $this->lang->words['hook_db_create'] ),
											array( 'alter', $this->lang->words['hook_db_alter'] ), 
											array( 'update', $this->lang->words['hook_db_update'] ), 
											array( 'insert', $this->lang->words['hook_db_insert'] ),
											);
				$alters				= array(
											array( 'add', $this->lang->words['hook_db_addnew'] ),
											array( 'change', $this->lang->words['hook_db_change'] ), 
											array( 'remove', $this->lang->words['hook_db_drop'] ), 
											);
				
				if( count($this->data['database']) )
				{
					foreach( $this->data['database'] as $type => $data )
					{
						foreach( $data as $change )
						{
							$form["type_{$i}"]		= $this->registry->output->formDropdown( "type_{$i}", $types, $type, "type_{$i}", "onchange='acp.hooks.generateFields({$i});'" );
							
							switch( $type )
							{
								case 'create':
									$form['field_1_' . $i ]			= $this->registry->output->formInput( "name_{$i}", $change['name'] );
									$form['description_1_' . $i ]	= $this->lang->words['desc_newtable'];
									$form['field_2_' . $i ]			= $this->registry->output->formTextarea( "fields_{$i}", htmlspecialchars($change['fields']) );
									$form['description_2_' . $i ]	= $this->lang->words['desc_fieldnames'];
									$form['field_3_' . $i ]			= $this->registry->output->formInput( "tabletype_{$i}", $change['tabletype'] );
									$form['description_4_' . $i ]	= $this->lang->words['desc_tabletype'];
								break;
								
								case 'alter':
									$form['field_1_' . $i ]			= $this->registry->output->formDropdown( "altertype_{$i}", $alters, $change['altertype'] );
									$form['description_1_' . $i ]	= $this->lang->words['desc_altertype'];
									$form['field_2_' . $i ]			= $this->registry->output->formInput( "table_{$i}", $change['table'] );
									$form['description_2_' . $i ]	= $this->lang->words['desc_newtable'];
									$form['field_3_' . $i ]			= $this->registry->output->formInput( "field_{$i}", $change['field'] );
									$form['description_3_' . $i ]	= $this->lang->words['desc_field'];
									$form['field_4_' . $i ]			= $this->registry->output->formInput( "newfield_{$i}", $change['newfield'] );
									$form['description_4_' . $i ]	= $this->lang->words['desc_changefield'];
									$form['field_5_' . $i ]			= $this->registry->output->formInput( "fieldtype_{$i}", $change['fieldtype'] );
									$form['description_5_' . $i ]	= $this->lang->words['desc_definition'];
									$form['field_6_' . $i ]			= $this->registry->output->formInput( "default_{$i}", htmlspecialchars($change['default']) );
									$form['description_6_' . $i ]	= $this->lang->words['desc_defaultvalue'];
								break;
								
								case 'update':
									$form['field_1_' . $i ]			= $this->registry->output->formInput( "table_{$i}", $change['table'] );
									$form['description_1_' . $i ]	= $this->lang->words['desc_newtable'];
									$form['field_2_' . $i ]			= $this->registry->output->formInput( "field_{$i}", $change['field'] );
									$form['description_2_' . $i ]	= $this->lang->words['desc_field'];
									$form['field_3_' . $i ]			= $this->registry->output->formInput( "newvalue_{$i}", htmlspecialchars($change['newvalue']) );
									$form['description_3_' . $i ]	= $this->lang->words['desc_newvalue'];
									$form['field_4_' . $i ]			= $this->registry->output->formInput( "oldvalue_{$i}", htmlspecialchars($change['oldvalue']) );
									$form['description_4_' . $i ]	= $this->lang->words['desc_oldvalue'];
									$form['field_5_' . $i ]			= $this->registry->output->formInput( "where_{$i}", htmlspecialchars($change['where']) );
									$form['description_5_' . $i ]	= $this->lang->words['desc_where'];
								break;
								
								case 'insert':
									$form['field_1_' . $i ]			= $this->registry->output->formInput( "table_{$i}", $change['table'] );
									$form['description_1_' . $i ]	= $this->lang->words['desc_newtable'];
									$form['field_2_' . $i ]			= $this->registry->output->formTextarea( "updates_{$i}", htmlspecialchars($change['updates']) );
									$form['description_2_' . $i ]	= $this->lang->words['desc_data'];
									$form['field_3_' . $i ]			= $this->registry->output->formInput( "fordelete_{$i}", htmlspecialchars($change['fordelete']) );
									$form['description_3_' . $i ]	= $this->lang->words['desc_revert'];
								break;
							}
						
							$i++;
						}
					}
				}
				
				$form["type_{$i}"]		= $this->registry->output->formDropdown( "type_{$i}", $types, null, "type_{$i}", "onchange='acp.hooks.generateFields({$i});'" );

				$output = $this->html->inline_database( $this->hook, $form, $i );
			break;
			
			case 'custom':
				$form				= array();
				$form['custom']		= $this->registry->output->formInput( "custom", $this->data['custom'] );

				$output = $this->html->inline_custom( $this->hook, $form );
			break;
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->returnHtml( $output );
	}
}