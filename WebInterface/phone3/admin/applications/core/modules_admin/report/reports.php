<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Management for reported content
 * Last Updated: $LastChangedDate: 2009-05-21 16:47:25 -0400 (Thu, 21 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4682 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_report_reports extends ipsCommand 
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
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_reports');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=report&amp;section=reports';
		$this->form_code_js	= $this->html->form_code_js	= 'module=report&section=reports';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_posts' ) );

		//-----------------------------------------
		// How would you like your eggs cooked?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'plugin_toggle':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_togglePlugin();
			break;
			case 'create_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_createPlugin();
			break;
			case 'edit_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_editPlugin();
			break;
			case 'change_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_changePlugin();
			break;
			case 'remove_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_remove' );
				$this->_removePlugin();
			break;
			case 'plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_showPluginIndex();
			break;
			
			case 'remove_image':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_remove' );
				$this->_removeImage();
			break;
			case 'add_image':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_addImage();
			break;
			case 'edit_image':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_editImage();
			break;
			case 'status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_showStatusIndex();
			break;
			case 'set_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_setStatus();
			break;
			case 'remove_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_removeStatus();
			break;
			case 'create_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_createStatus();
			break;
			case 'edit_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_editStatus();
			break;
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_moveStatus();
			break;

			case 'chart_report_stats':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_access' );
				$this->_reportsChart();
			break;
			case 'chart_top_moderator':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_access' );
				$this->_moderatorsChart();
			break;
			default:
			case 'main':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_access' );
				$this->_mainScreen();
			break;
			
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Displays the overview page with version info, blog, stats, etc...
	 *
	 * @access	private
	 * @return	void
	 */
	private function _mainScreen()
	{
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports' ) );
		$this->DB->execute();
		$num_reports = $this->DB->getTotalRows();
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_comments' ) );
		$this->DB->execute();
		$num_comments = $this->DB->getTotalRows();
		
		$this->DB->build( array( 'select' => 'onoff', 'from' => 'rc_classes' ) );
		$this->DB->execute();
		
		$plug_on		= 0;
		$plug_total		= 0;
		
		while( $plugs = $this->DB->fetch() )
		{
			if( $plugs['onoff'] == 1 )
			{
				$plug_on++;
			}

			$plug_total++;
		}

		$this->registry->output->html .=  $this->html->overview_main_template( array(
																					'reports_total'		=> $num_reports,
																					'comments_total'	=> $num_comments,
																					'active_plugins'	=> $plug_on,
																					'total_plugins'		=> $plug_total,
																			)		);
	}
	

	/**
	 * This function is used when you disable/enable a report plugin
	 *
	 * @access	private
	 * @return	void
	 */
	private function _togglePlugin()
	{
		$plugin_id = intval($this->request['plugin_id']);
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( ! $plugin_id )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];
			$this->_showPluginIndex();
			return;
		}

		//--------------------------------------------
		// Get from database
		//--------------------------------------------
		
		$component		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_classes', 'where' => 'com_id=' . $plugin_id ) );
		
		$com_enabled	= $component['onoff'] ? 0 : 1;
		
		$this->DB->update( 'rc_classes', array( 'onoff' => $com_enabled ), 'com_id=' . $plugin_id );
		
		$this->registry->output->global_message = $this->lang->words['r_toggle'];
		$this->_showPluginIndex();
	}

	/**
	 * Basics for creating a plugin (with only file name, title, and description)
	 *
	 * @access	private
	 * @return	void
	 */
	private function _createPlugin()
	{
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Make sure stuff is right
			//-----------------------------------------
			
			if( ! $_POST['plugi_title'] || ! $_POST['plugi_desc'] || ! $_POST['plugi_file'] )
			{
				$this->registry->output->global_message = $this->lang->words['r_missingfield'];
			}
			elseif( preg_match("/[^a-z0-9\-_]/i", $this->request['plugi_file'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_incchar'];
			}
			elseif( ! file_exists( IPSLib::getAppDir('core') . '/sources/reportPlugins/' . $this->request['plugi_file'] . '.php' ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_404file'];	
			}
			
			//-----------------------------------------
			// If no errors, create the plugin
			//-----------------------------------------
		
			if( ! $this->registry->output->global_message )
			{	
				$build_plugin = array(
									'onoff'			=> 1,
									'class_title'	=> $this->request['plugi_title'],
									'class_desc'	=> IPSText::stripslashes( $_POST['plugi_desc'] ),
									'author'		=> $this->request['plugi_author'],
									'author_url'	=> $this->request['plugi_author_url'],
									'my_class'		=> $this->request['plugi_file'],
									'pversion'		=> 'v' . strval($this->request['plugi_version']),
									'lockd'			=> 0,
				);
				
				$this->DB->insert( 'rc_classes', $build_plugin );

				$plugin_id = $this->DB->getInsertId();
				
				//-----------------------------------------
				// Redirect to edit the plugin for more
				//-----------------------------------------
				
				$this->registry->output->doneScreen( $this->lang->words['r_plugincreate'], $this->lang->words['r_finishplugin'], "{$this->form_code}&amp;do=edit_plugin&amp;com_id=" . $plugin_id, 'redirect' );
			}
		}

		//-----------------------------------------
		// Show the form
		//-----------------------------------------

		$this->registry->output->html .= $this->html->pluginForm();
	}

	/**
	 * Here we are editing a plugin's settings
	 *
	 * @access	private
	 * @return	void
	 */
	private function _editPlugin()
	{
		$plug_id = intval( $this->request['com_id'] );
		
		//-----------------------------------------
		// Make sure the plug ID is not zero...
		//-----------------------------------------
		
		if( $plug_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Pull up the plugin information...
		//-----------------------------------------
	
		$plug_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_classes', 'where' => "com_id='{$plug_id}'" ) );

		//-----------------------------------------
		// Does this plugin even exist...?
		//-----------------------------------------
		
		if( !$plug_data['com_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_plugnoexist'];	
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Load the plugin and it's information
		//-----------------------------------------

		if( $plug_data['my_class'] == '' )
		{
			$plug_data['my_class'] = 'default';
		}
		
		require_once( IPSLib::getAppDir('core') . '/sources/reportPlugins/' . $plug_data['my_class'] . '.php' );
		$plugin_name = $plug_data['my_class'] . '_plugin';
		$plugin = new $plugin_name( $this->registry );
		
		//-----------------------------------------
		// Load the plugin's extra data settings
		//-----------------------------------------
		
		if( $plug_data['extra_data'] && $plug_data['extra_data'] != 'N;' )
		{
			$plugin->_extra = unserialize( $plug_data['extra_data'] );
		}
		else
		{
			$plugin->_extra = array();
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Form was sent, so let's process stuff
			//-----------------------------------------

			$plug_error = $plugin->processAdminForm( $extra_data );
			
			if( $plugin_error )
			{
				$this->registry->output->global_message = $plug_error;
			}
			
			if( is_array( $_POST['plugi_can_report'] ) )
			{
				$p_can_report = ',' . implode( ',', $_POST['plugi_can_report'] ) . ',';
			}
			
			if( is_array( $_POST['plugi_gperm'] ) )
			{
				$p_can_gperm = ',' . implode( ',', $_POST['plugi_gperm'] ) . ',';
			}
			
			$build_plugin = array(
								'onoff'				=> $this->request['plugi_onoff'],
								'group_can_report'	=> $p_can_report,
								'mod_group_perm'	=> $p_can_gperm,
								'extra_data'		=> serialize($extra_data),
								);
			
			if( $plug_data['lockd'] == 0 || IN_DEV == 1 )
			{
				$build_plugin['lockd'] = $this->request['plugi_lockd'];
			}
			
			if( ! $this->registry->output->global_message )
			{						
				$this->DB->update( 'rc_classes', $build_plugin, "com_id={$plug_id}" );		
				
				$this->registry->output->doneScreen($this->lang->words['r_plugupdated'], $this->lang->words['r_plugmanager'], "{$this->form_code}&amp;do=plugin", 'redirect' );
			}
			else
			{
				$plug_data = $build_plugin;
			}
		}

		//-----------------------------------------
		// Break up group perms into an array
		//-----------------------------------------
						   
		if( $plug_data['group_can_report'] )
		{
			$sel_can_report = explode( ',' , $plug_data['group_can_report'] );
		}
		
		if( $plug_data['mod_group_perm'] )
		{
			$sel_group_perm = explode( ',' , $plug_data['mod_group_perm'] );
		}

		//-----------------------------------------
		// Display special plugin settings here...
		//-----------------------------------------
		
		$extraForm = $plugin->displayAdminForm( $plugin->_extra, $this->html );

		$this->registry->output->html .= $this->html->finishPluginForm( $plug_data, $sel_can_report, $sel_group_perm, $extraForm );
	}
	
	/**
	 * Lock/unlock a plugin
	 *
	 * @access	private
	 * @return	void
	 */
	private function _changePlugin()
	{
		$plug_id = intval( $this->request['com_id'] );
		
		//-----------------------------------------
		// Make sure plugin ID is > than zero...
		//-----------------------------------------

		if( $plug_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Load basic, very basic, information...
		//-----------------------------------------
		
		$plug_data = $this->DB->buildAndFetch( array( 'select' => 'com_id, my_class, class_title, class_desc, author, author_url, pversion', 'from' => 'rc_classes', 'where' => "com_id='{$plug_id}'" ) );
		
		//-----------------------------------------
		// Does our plugin even exist...?
		//-----------------------------------------
		
		if( !$plug_data['com_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_plugnoexist'];	
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Can we even change this plugin?
		//-----------------------------------------

		if( $plug_data['lockd'] > 0 && !IN_DEV )
		{
			$this->registry->output->global_message = $this->lang->words['r_pluglocked'];	
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Let's start loading stuff...!
		//-----------------------------------------

		if( $plug_data['my_class'] == '' )
		{
			$plug_data['my_class'] = 'default';
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// The form got sent, so lets go!
			//-----------------------------------------
			
			if( ! $_POST['plugi_title'] || ! $_POST['plugi_desc'] || ! $_POST['plugi_file'] )
			{
				$this->registry->output->global_message = $this->lang->words['r_missingfield'];
			}
			elseif( preg_match( "/[^a-z0-9_\-]/i", $_POST['plugi_file'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_incchar'];
			}
			
			$build_plugin = array(
								'class_title'	=> $this->request['plugi_title'],
								'class_desc'	=> IPSText::stripslashes( $_POST['plugi_desc'] ),
								'author'		=> $this->request['plugi_author'],
								'author_url'	=> $this->request['plugi_author_url'],
								'my_class'		=> $this->request['plugi_file'],
								'pversion'		=> 'v'  . strval($this->request['plugi_version']),
								'lockd'			=> $this->request['plugi_lockd'] 
								);
			
			//-----------------------------------------
			// If file was changed blank out extra...
			//-----------------------------------------
			
			if( $plug_data['my_class'] != $build_plugin['my_class'] )
			{
				$build_plugin['extra_data'] = '';
				$do_edit = true;
			}

			if( ! $this->registry->output->global_message )
			{						
				$this->DB->update( 'rc_classes', $build_plugin, "com_id={$plug_id}" );		
				
				if( $do_edit == true )
				{
					//-----------------------------------------
					// Plugin was changed, need settings now
					//-----------------------------------------
					
					$this->registry->output->doneScreen($this->lang->words['r_plugupdated'], $this->lang->words['r_finishplugin'], "{$this->form_code}&amp;do=edit_plugin&amp;com_id=" . $plug_id, 'redirect' );
				}
				else
				{
					//-----------------------------------------
					// File was not changed, no need to edit..
					//-----------------------------------------
					
					$this->registry->output->doneScreen($this->lang->words['r_plugupdated'], $this->lang->words['r_plugmanager'], "{$this->form_code}&amp;do=plugin", 'redirect' );
				}
			}
			else
			{
				$plug_data = $build_plugin;
			}
		}
		
		//-----------------------------------------
		// Basic info for when I hit "Save"...
		//-----------------------------------------

		$this->registry->output->html .= $this->html->pluginForm( $plug_data );
	}

	/**
	 * This is when we want to remove a plugin, possibly cuz it's crap
	 *
	 * @access	private
	 * @return	void
	 */
	private function _removePlugin()
	{
		$com_id = intval( $this->request['com_id'] );
		
		//-----------------------------------------
		// Make sure we don't delete nothing...
		//-----------------------------------------
		
		if( $com_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_deleteair'];	
			$this->_showPluginIndex();
			return;
		}
		
		$row = $this->DB->buildAndFetch( array( 'select' => 'com_id, lockd', 'from' => 'rc_classes', 'where' => "com_id={$com_id}" ) );

		//-----------------------------------------
		// Make sure plugin exists first!
		// Again... NOTHING!
		//-----------------------------------------
		
		if( !$row['com_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_plugin404'];	
			$this->_showPluginIndex();
			return;
		}

		//-----------------------------------------
		// Make sure it isn't locked... Snicker..
		//-----------------------------------------
		
		if( $row['lockd'] == 1 && !IN_DEV )
		{
			$this->registry->output->global_message = $this->lang->words['r_dellocked'];	
			$this->_showPluginIndex();
			return;
		}
		
		$this->DB->delete( 'rc_classes', "com_id={$com_id}" );
		
		$this->registry->output->doneScreen( $this->lang->words['r_deleteplug'], $this->lang->words['r_plugmanager'], "{$this->form_code}&amp;do=plugin", 'redirect' );
	}

	/**
	 * This is where you view all the plugins in a list with options
	 *
	 * @access	private
	 * @return	void
	 */
	private function _showPluginIndex()
	{
		$this->registry->output->extra_nav[]		= array( "{$this->settings['base_url']}{$this->form_code}&do=plugin" , $this->lang->words['r_plugmanager'] );
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_classes' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_enabled_img'] = $row['onoff'] == 1 ? 'tick.png' : 'cross.png';

			$plugin_rows .= $this->html->report_plugin_row( $row );
		}
		
		$this->registry->output->html = $this->html->report_plugin_overview( $plugin_rows );
	}
	
	/**
	 * This is when you delete a status/severity image
	 *
	 * @access	private
	 * @return	void
	 */
	private function _removeImage()
	{
		$img_id = intval( $this->request['id'] );
		
		if( $img_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}
		
		$this->DB->delete( 'rc_status_sev', "id={$img_id}" );
		
		$this->registry->output->doneScreen( $this->lang->words['r_imgdel'], $this->lang->words['r_statsever'], "{$this->form_code}&amp;do=status", 'redirect' );
	}

	/**
	 * This is when I want to add a severity/status image
	 *
	 * @access	private
	 * @return	void
	 */
	private function _addImage()
	{
		$stat_id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Make sure that status isn't zero...
		//-----------------------------------------
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Time to add that status img to DB...
			//-----------------------------------------
						
			$image_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status_sev', 'where' => "status='{$stat_id}' AND points={$this->request['img_points']}" ) );
		
			if( $image_data['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['r_changepoints'];	
			}
			else
			{
				$build_image = array(
									'status'	=> $stat_id,
									'img'		=> trim(IPSText::safeslashes($_POST['img_filename'])),
									'width'		=> $this->request['img_width'],
									'height'	=> $this->request['img_height'],
									'points'	=> $this->request['img_points'],
									'is_png'	=> ( strtolower(strrchr( $this->request['img_filename'], '.' )) == '.png' ? 1 : 0 )
				);
				
				$this->DB->insert( 'rc_status_sev', $build_image, "id='{$img_id}'" );	
				
				$this->registry->output->doneScreen($this->lang->words['r_imgsaved'], $this->lang->words['r_statsever'], "{$this->form_code}&amp;do=status", 'redirect' );
			}
		}
		
		//-----------------------------------------
		// Load basic status data...
		//-----------------------------------------

		$status = $this->DB->buildAndFetch( array( 'select' => 'title', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );

		//-----------------------------------------
		// Does the status exist in the db?
		//-----------------------------------------
		
		if( !$status['title'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_404status'];	
			$this->_showStatusIndex();
			return;
		}

		//-----------------------------------------
		// Build the form so we can do something
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->imageForm( 'add_image', $status, $image_data );
	}

	/**
	 * This is for setting, in status index, which is New, Complete, and Active
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setStatus()
	{
		$stat_id = intval( $this->request['id'] );
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
		}
		
		//-----------------------------------------
		// Load the status that needs a change
		//-----------------------------------------
		
		$row = $this->DB->buildAndFetch( array( 'select' => 'is_new, is_complete, is_active', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );
		
		if( $this->request['status'] == 'new' || $this->request['status'] == 'complete' )
		{
			//-----------------------------------------
			// We want to make it new or complete
			//-----------------------------------------
			
			if( $this->request['status'] == 'new' )
			{
				if( $row['is_complete'] == 1 )
				{
					$this->registry->output->global_message = $this->lang->words['r_newcomplete'];
					$this->_showStatusIndex();
					return;
				}

				$build_status			= array( 'is_new' => 1 );
				$build_other_statuses	= array( 'is_new' => 0 );
			}
			else
			{
				if( $row['is_new'] == 1 )
				{
					$this->registry->output->global_message = $this->lang->words['r_completenew'];
					$this->_showStatusIndex();
					return;
				}

				$build_status			= array( 'is_complete' => 1 );
				$build_other_statuses	= array( 'is_complete' => 0 );
			}

			$this->DB->update( 'rc_status', $build_other_statuses, "status<>{$stat_id}" );
			$this->DB->update( 'rc_status', $build_status, "status={$stat_id}" );
		}
		elseif( $this->request['status'] == 'active' )
		{
			//-----------------------------------------
			// We can have as many active as we want
			//-----------------------------------------
			
			$this->DB->update( 'rc_status', array( 'is_active' => ( ! $row['is_active'] ) ), "status={$stat_id}" );
		}
		else
		{
			//-----------------------------------------
			// What the heck can this be?
			//-----------------------------------------
			
			$this->registry->output->global_message = $this->lang->words['r_invpar'];	
		}

		$this->_showStatusIndex();
	}

	/**
	 * This is the magic behind removing a status and the images behind it
	 *
	 * @access	private
	 * @return	void
	 */
	private function _removeStatus()
	{
		$stat_id = intval( $this->request['id'] );
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_deleteair'];	
			$this->_showStatusIndex();
			return;
		}
		
		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );

		if( !$row['status'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}

		//-----------------------------------------
		// Make sure we arn't removing something
		// that we are going to need.
		//-----------------------------------------
		
		if( $row['is_new'] == 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_onenew'];	
			$this->_showStatusIndex();
			return;
		}
		elseif( $row['is_complete'] == 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_onecomplete'];	
			$this->_showStatusIndex();
			return;
		}
		
		//-----------------------------------------
		// Remove the status and its images
		//-----------------------------------------
		
		$this->DB->delete( 'rc_status', "status='{$stat_id}'" );
		$this->DB->delete( 'rc_status_sev', "status='{$stat_id}'" );
		
		$this->registry->output->doneScreen($this->lang->words['r_statdel'], $this->lang->words['r_statsever'], "{$this->form_code}&amp;do=status", 'redirect' );
	}

	/**
	 * Here is where you create a status (before the added images)
	 *
	 * @access	private
	 * @return	void
	 */
	private function _createStatus()
	{
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// The form has been sent, lets go!
			//-----------------------------------------
			
			if( preg_match("/[^0-9]/", $_POST['stat_ppr'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_ppr'];	
			}
			elseif( preg_match("/[^0-9\.]/", $_POST['stat_pph'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_mtp'];	
			}
			else if( !$this->request['stat_title'] )
			{
				$this->registry->output->global_message = $this->lang->words['r_nostattitle'];	
			}
			else
			{
				$stat_check = $this->DB->buildAndFetch( array( 'select' => 'MAX(rorder) as rorder', 'from' => 'rc_status' ) );

				$build_status = array(
									'title'				=> $this->request['stat_title'],
									'points_per_report'	=> $this->request['stat_ppr'],
									'minutes_to_apoint'	=> $this->request['stat_pph'],
									'rorder'			=> $stat_check['rorder'] + 1,
									);
				
				$this->DB->insert( 'rc_status', $build_status );

				$this->registry->output->doneScreen( $this->lang->words['r_statcreated'], $this->lang->words['r_statsever'], "{$this->form_code}&amp;do=status", 'redirect' );
			}
		}
			 
		$this->registry->output->html .= $this->html->statusForm();
	}

	/**
	 * This is when I want to edit a status
	 *
	 * @access	private
	 * @return	void
	 */
	private function _editStatus()
	{
		$stat_id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Make sure the status ID is not zero
		//-----------------------------------------
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// We just sen't the form, check stuff
			//-----------------------------------------
			
			if( preg_match("/[^0-9]/", $_POST['stat_ppr'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_ppr'];	
			}
			elseif( preg_match("/[^0-9\.]/", $_POST['stat_pph'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['r_mtp'];	
			}
			else
			{
				$build_status = array(
									'title'				=> $this->request['stat_title'],
									'points_per_report'	=> $this->request['stat_ppr'],
									'minutes_to_apoint'	=> $this->request['stat_pph'],
				);
					
				$this->DB->update( 'rc_status', $build_status, "status='{$stat_id}'" );

				$this->registry->output->doneScreen( $this->lang->words['r_statsaved'], $this->lang->words['r_statsever'], "{$this->form_code}&amp;do=status", 'redirect' );
			}
		}
		
		$stat_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );

		//-----------------------------------------
		// Make sure the status actually exists
		//-----------------------------------------
		
		if( !$stat_data['status'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}

		$this->registry->output->html .= $this->html->statusForm( 'edit_status', $stat_data );
	}

	/**
	 * This is where you edit an image under a status
	 *
	 * @access	private
	 * @return	void
	 */
	private function _editImage()
	{
		$img_id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Make sure the ID is not zero...
		//-----------------------------------------
		
		if( $img_id < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Now it's time to send the form...
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => '*', 'from' => 'rc_status_sev', 'where' => "status='{$this->request['img_status']}' AND points={$this->request['img_points']} AND id!={$img_id}", 'limit' => 1 ) );
			$this->DB->execute();
		
			if( $this->DB->getTotalRows() != 0 )
			{
				$this->registry->output->global_message = $this->lang->words['r_changepoints'];	
			}
			else
			{
				$build_image = array(
									'img'		=> trim(IPSText::safeslashes($_POST['img_filename'])),
									'width'		=> $this->request['img_width'],
									'height'	=> $this->request['img_height'],
									'points'	=> $this->request['img_points'],
									'is_png'	=> ( strtolower(strrchr( $this->request['img_filename'], '.' )) == '.png' ? 1 : 0 )
				);
				
				$this->DB->update( 'rc_status_sev', $build_image, "id='{$img_id}'" );	
				
				$this->registry->output->doneScreen($this->lang->words['r_imgsaved'], $this->lang->words['r_statsever'], "{$this->form_code}&amp;do=status", 'redirect' );
			}
		}
		
		$image_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status_sev', 'where' => "id='{$img_id}'" ) );
		
		//-----------------------------------------
		// Make sure the image actually exists...
		//-----------------------------------------
		
		if( !$image_data['id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_noid'];	
			$this->_showStatusIndex();
			return;
		}
		
		//-----------------------------------------
		// Load basic data so we can edit it
		//-----------------------------------------

		$status = $this->DB->buildAndFetch( array( 'select' => 'title', 'from' => 'rc_status', 'where' => "status='{$image_data['status']}'" ) );
		
		$this->registry->output->html .= $this->html->imageForm( 'edit_image', $status, $image_data );
	}

	/**
	 * When we want to move the status up or down (not sideways...)
	 *
	 * @access	private
	 * @return	void
	 */
	private function _moveStatus()
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
 		
 		if( is_array($this->request['status']) AND count($this->request['status']) )
 		{
 			foreach( $this->request['status'] as $this_id )
 			{
 				$this->DB->update( 'rc_status', array( 'rorder' => $position ), 'status=' . $this_id );
 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}

	/**
	 * Shows the status/severity index with all the special stuff
	 *
	 * @access	private
	 * @return	void
	 */
	private function _showStatusIndex()
	{
		$this->registry->output->extra_nav[]		= array( "{$this->settings['base_url']}{$this->form_code}&do=status" , $this->lang->words['r_statsever'] );

		//-----------------------------------------
		// Get the default board skin...
		//-----------------------------------------
		
		$skin = $this->DB->buildAndFetch( array( 'select' => 'set_image_dir', 'from' => 'skin_collections', 'where' => "set_is_default=1" ) );
		
		//-----------------------------------------
		// Get the status images...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_status_sev', 'order' => 'status ASC, points ASC' ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Store the status images in an array...
		//-----------------------------------------
		
		$status_image_cache = array();

		while( $srow = $this->DB->fetch() )
		{
			$status_image_cache[ $srow['status'] ] .= str_replace( '<#IMG_DIR#>', $skin['set_image_dir'], $this->html->report_status_image( $srow ) );
		}
		
		//-----------------------------------------
		// Load and process the stateses...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_status', 'order' => 'rorder ASC' ) );
		$this->DB->execute();

		$total_statuses = $this->DB->getTotalRows();
		
		while( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Show the New/Complete/Active boxes...
			//-----------------------------------------
			
			$row['_is_new']			= $row['is_new'] == 1 ? 'on' : 'off';
			$row['_is_complete']	= $row['is_complete'] == 1 ? 'on' : 'off';
			$row['_is_active']		= $row['is_active'] == 1 ?  'on' : 'off';
			
			//-----------------------------------------
			// Finish row with image cache...
			//-----------------------------------------
			
			$row['status_images'] = $status_image_cache[ $row['status'] ];
			$status_rows .= $this->html->report_status_row( $row );
		}

		$this->registry->output->html = $this->html->report_status_overview( $status_rows );
	}

	/**
	 * Shows report stats in the last 24 hours using chart class
	 *
	 * @access	private
	 * @return	void
	 */
	private function _reportsChart()
	{
		//-----------------------------------------
		// Let's build our data for the chart
		//-----------------------------------------
		
		$this->DB->build(
								array(
									'select'	=> 'stat.title, COUNT(*) as num_reports',
									'from'		=> array( 'rc_reports_index' => 'rep' ),
									'where'		=> "( stat.is_active=1 OR ( stat.is_active=0 AND (rep.date_updated-" . time() . ")<86400 ) )",
									'group'		=> 'rep.status',
									'add_join'	=> array(
														array(
															'from'	=> array( 'rc_reports' => 'reps' ),
															'where'	=> 'rep.id=reps.rid'
															),
														array(
															'from'	=> array( 'rc_status' => 'stat' ),
															'where'	=> 'stat.status=rep.status'
															),
														)
									)
								);
		$this->DB->execute();
		
		$chart_data	= array();
		$labels		= array();
		
		while( $row = $this->DB->fetch() )
		{
			$chart_data[]	= $row['num_reports'];
			$labels[]		= $row['title'];
		}

		//-----------------------------------------
		// Let's draw our pie chart now
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . '/classGraph.php' );
		$graph = new classGraph();
		$graph->options['title'] = $this->lang->words['r_last24'];
		$graph->options['width'] = 400;
		$graph->options['height'] = 250;
		$graph->options['style3D'] = 1;

		if( count($chart_data) )
		{
			$graph->addLabels( $labels );
			$graph->addSeries( 'test', $chart_data );
		}
		else
		{
			$graph->addLabels( array( $this->lang->words['r_nodata'] ) );
			$graph->addSeries( 'test', array( 1 ) );
		}

		$graph->options['charttype'] = 'Pie';
		$graph->display();
	}

	/**
	 * Shows bar graph of top five moderators. Show's what's been done
	 *
	 * @access	private
	 * @return	void
	 */
	private function _moderatorsChart()
	{
		//-----------------------------------------
		// Build data for the bar chart
		//-----------------------------------------
		
		$this->DB->build( array(
									'select'	=> 'rep.id',
									'from'		=> array( 'rc_reports' => 'reps' ),
									'where'		=> 'stat.is_complete=1',
									'add_join'	=> array(
														array(
															'from'	=> array( 'rc_reports_index' => 'rep' ),
															'where'	=> 'reps.rid=rep.id',
															),
														array(
															'from'	=> array( 'rc_status' => 'stat' ),
															'where'	=> 'stat.status=rep.status',
															),
														)
							)		);
		$this->DB->execute();
		
		$total_complete_reports = $this->DB->getTotalRows();
		
		$this->DB->build(
								array(
									'select'	=> 'mem.members_display_name as name, COUNT(*) as num_reports',
									'from'		=> array( 'rc_reports_index' => 'rep' ),
									'where'		=> "stat.is_complete=1",
									'group'		=> 'rep.updated_by',
									'limit'		=> array( 5 ),
									'add_join'	=> array(
														array(
															'from'	=> array( 'rc_reports' => 'reps' ),
															'where'	=> 'rep.id=reps.rid'
															),
														array(
															'from'	=> array( 'rc_status' => 'stat' ),
															'where'	=> 'stat.status=rep.status'
															),
														array(
															'from'	=> array( 'members' => 'mem' ),
															'where'	=> 'mem.member_id=rep.updated_by'
															),
														)
									)
								);
		$this->DB->execute();
		
		$chart_data	= array();
		$labels		= array();
		
		while( $row = $this->DB->fetch() )
		{
			$chart_data[]	= $row['num_reports'];
			$labels[]		= substr($row['name'],0,16);
		}

		//-----------------------------------------
		// Let's draw our pie chart now
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . '/classGraph.php' );
		$graph = new classGraph();
		$graph->options['title'] = $this->lang->words['r_top5'];
		$graph->options['width'] = 400;
		$graph->options['height'] = 250;
		$graph->options['style3D'] = 1;
		
		if( count($chart_data) )
		{
			$graph->addLabels( $labels );
			$graph->addSeries( 'test', $chart_data );
		}
		else
		{
			$graph->addLabels( array( $this->lang->words['r_nodata'] ) );
			$graph->addSeries( 'test', array( 1 ) );
		}

		$graph->options['charttype'] = 'Pie';
		$graph->display();
	}
	
}