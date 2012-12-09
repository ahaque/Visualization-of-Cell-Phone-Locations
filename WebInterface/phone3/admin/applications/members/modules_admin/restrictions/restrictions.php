<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP Restrictions
 * Last Updated: $Date: 2009-06-10 16:15:40 -0400 (Wed, 10 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 4755 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_members_restrictions_restrictions extends ipsCommand
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
	 * Member information
	 *
	 * @access	private
	 * @var		array			Member information
	 */
	private $member_info		= array();
	
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_restrictions');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=restrictions&amp;section=restrictions';
		$this->form_code_js	= $this->html->form_code_js	= 'module=restrictions&section=restrictions';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_restrictions' ) );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			///-----------------------------------------
			// Remove restrictions
			//-----------------------------------------
			case 'accperms-member-remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_delete_member' );
				$this->_removePermissions( 'member' );
			break;
			case 'accperms-group-remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_delete_group' );
				$this->_removePermissions( 'group' );
			break;

			///-----------------------------------------
			// Add restrictions to member
			//-----------------------------------------
			case 'acpperms-member-add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_add_member' );
				$this->_addRole( 'member' );
			break;
			case 'acpperms-member-add-complete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_add_member' );
				$this->_addMemberDo();
			break;

			///-----------------------------------------
			// Add restrictions to group
			//-----------------------------------------
			case 'acpperms-group-add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_add_group' );
				$this->_addRole( 'group' );
			break;
			case 'acpperms-group-add-complete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_add_group' );
				$this->_addGroupDo();
			break;

			///-----------------------------------------
			// Management form..
			//-----------------------------------------
			case 'accperms-member-edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_edit_member' );
				$this->_restrictionsForm( 'member' );
			break;
			case 'accperms-group-edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_edit_group' );
				$this->_restrictionsForm( 'group' );
			break;
				
			///-----------------------------------------
			// And saving the restrictions..
			//-----------------------------------------
			case 'acpperms-save':
				$this->_restrictionsSave();
			break;

			default:
			case 'overview':
				$this->_acppermsList();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Saving the ACP restrictions - here's where it gets really dirty
	 *
	 * @access	private
	 * @return	void
	 * @since	2.1.0.2005-7-11
	 */
	private function _restrictionsSave()
	{
		$role_id		= intval($this->request['id']);
		$role_type		= $this->request['type'];
		
		if( $role_type == 'member' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_edit_member' );
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'restrictions_edit_group' );
		}

		if( !$role_id OR !$role_type )
		{
			$this->registry->output->global_message = $this->lang->words['r_whichmodify'];
			$this->_acppermsList();
			return;
		}
		
		if( !in_array( $role_type, array( 'member', 'group' ) ) )
		{
			$this->registry->output->global_message = $this->lang->words['r_whichmodify'];
			$this->_acppermsList();
			return;
		}
		
		$role_entry		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'admin_permission_rows', 'where' => "row_id_type='{$role_type}' AND row_id=" . $role_id ) );
		
		if( !$role_entry['row_id'] )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_whichrole'], $role_type );
			$this->_acppermsList();
			return;
		}
		
		$updated_permissions	= array(
										'applications'	=> array(),
										'modules'		=> array(),
										'items'			=> array(),
										);

		foreach( $_POST as $key => $value )
		{
			if( preg_match( "/^app_(\d+)$/", $key, $matches ) )
			{
				if( $value )
				{
					$updated_permissions['applications'][]	= $matches[1];
				}
			}
			else if( preg_match( "/^module_(\d+)$/", $key, $matches ) )
			{
				if( $value )
				{
					$updated_permissions['modules'][]	= $matches[1];
				}
			}
			else if( preg_match( "/^item_(\d+)_(\S+)$/", $key, $matches ) )
			{
				if( $value )
				{
					$updated_permissions['items'][ $matches[1] ][]	= $matches[2];
				}
			}
		}
		
		$this->DB->update( 'admin_permission_rows', array( 'row_perm_cache' => serialize($updated_permissions), 'row_updated' => time() ), "row_id_type='{$role_type}' AND row_id=" . $role_id );

		$this->registry->output->global_message = sprintf( $this->lang->words['r_roleupdate'], $role_type );
		$this->_acppermsList();
	}
	
	/**
	 * Manage ACP restrictions - The meat and potatoes, so to speak
	 *
	 * @access	private
	 * @param	string		[member|group]
	 * @return	void
	 * @since	2.1.0.2005-7-11
	 * @todo 	One of the only areas that still uses the old XML parser
	 */
	private function _restrictionsForm( $type='member' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$row_id = $type == 'member' ? intval( $this->request['mid'] ) : intval( $this->request['gid'] );
		$perms	= array();
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $row_id )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_notypeid'], $type );
			$this->_acppermsList();
			return;
		}
		
		//-------------------------------
		// Grab member's row
		//-------------------------------
		
		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'admin_permission_rows', 'where' => "row_id_type='{$type}' AND row_id=" . $row_id ) );
		
		$row['current']		= unserialize( $row['row_perm_cache'] );

		if( !is_array($row['current']) OR !count($row['current']) )
		{
			$row['current']	= array(
									'applications'	=> array(),
									'modules'		=> array(),
									'items'			=> array(),
									);
		}

		//-------------------------------
		// Grab XML library
		//-------------------------------
		
		require_once( IPS_KERNEL_PATH . 'class_xml.php' );

		$xml = new class_xml();
		
		//-------------------------------
		// Start the madness...err parsing
		//-------------------------------
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			if( !is_array(ipsRegistry::$modules[ $app_dir ]) OR !count(ipsRegistry::$modules[ $app_dir ]) )
			{
				continue;
			}

			foreach( ipsRegistry::$modules[ $app_dir ] as $module )
			{
				$_file 	= IPSLib::getAppDir( $app_dir ) . '/modules_admin/' . $module['sys_module_key'] . '/xml/permissions.xml';
				
				if( ! $module['sys_module_admin'] )
				{
					continue;
				}
				
				if( ! file_exists( $_file ) )
				{
					continue;
				}

				$content	= file_get_contents( $_file );
				
				$xml->xml_parse_document( $content );
				
				if ( ! is_array( $xml->xml_array['permissions']['group'][0]  ) )
				{
					//-----------------------------------------
					// Ensure [0] is populated
					//-----------------------------------------
					
					$xml->xml_array['permissions']['group'] = array( 0 => $xml->xml_array['permissions']['group'] );
				}
				
				//-----------------------------------------
				// Loop through and sort out permissions and groups...
				//-----------------------------------------
				
				$group	= 0;

				foreach( $xml->xml_array['permissions']['group'] as $entry )
				{
					//-----------------------------------------
					// Do we have a row matching this already?
					//-----------------------------------------
					
					$_title			= $entry['grouptitle']['VALUE'];
					$items			= array();
					$group++;
					
					if( is_array($entry['items']) AND count($entry['items']) )
					{
						foreach( $entry['items'] as $item )
						{
							if( is_array($item) AND count($item) )
							{
								if( isset($item['key']) )
								{
									$items[ $item['key']['VALUE'] ] = $item['string']['VALUE'];
								}
								else
								{
									foreach( $item as $sub_item )
									{
										$items[ $sub_item['key']['VALUE'] ]	= $sub_item['string']['VALUE'];
									}
								}
							}
						}

						$perms[ $app_data['app_id'] ][ $module['sys_module_id'] ][ $group ]['title']	= $_title;
						$perms[ $app_data['app_id'] ][ $module['sys_module_id'] ][ $group ]['items']	= $items;
					}
				}
			}
		}

		//-------------------------------
		// Print
		//-------------------------------
		
		$this->registry->output->html .= $this->html->restrictionsForm( $row_id, $type, $perms, $row['current'] );
	}
	
	/**
	 * List members and groups with restrictions
	 *
	 * @access	private
	 * @return	void			Outputs to screen
	 * @since	2.1.0.2005-7-7
	 */
	private function _acppermsList()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$members		= '';
		$groups			= '';
		
		//-------------------------------
		// Get current ACP listed members
		//-------------------------------
		
		$this->DB->build( array( 'select'		=> 'p.*',
										'from'		=> array( 'admin_permission_rows' => 'p' ),
										'add_join'	=> array( 
															array(
											 						'select'	=> 'm.members_display_name, m.member_id, m.member_group_id, m.mgroup_others',
											 						'from'		=> array( 'members' => 'm' ),
											 						'where'		=> "p.row_id_type='member' AND m.member_id=p.row_id",
											 						'type'		=> 'left' 
												 				) 
												 			),
										'order'		=> 'm.members_display_name DESC' 
							) 		);
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			//-------------------------------
			// (Alex) Cross
			//-------------------------------
			
			$r['_date']			= ipsRegistry::getClass('class_localization')->getDate( $r['row_updated'], 'SHORT' );
			
			if( $r['row_id_type'] == 'member' )
			{
				$r['_group_name']	= $this->caches['group_cache'][ $r['member_group_id'] ]['g_title'];
				$r['_other_groups']	= '<em>None</em>';
				
				if( $r['mgroup_others'] )
				{
					$other_mgroups	= explode( ',', IPSText::cleanPermString( $r['mgroup_others'] ) );
					$formatted		= array();
					
					if( is_array($other_mgroups) AND count($other_mgroups) )
					{
						foreach( $other_mgroups as $omg )
						{
							$formatted[] = $this->caches['group_cache'][ $omg ]['g_title'];
						}
						
						$r['_other_groups'] = implode( ', ', $formatted );
					}
				}
				
				$members .= $this->html->acpMemberRow($r);
			}
			else
			{
				$count = $this->DB->buildAndFetch(
														array(
																'select'	=> 'count(*) as total',
																'from'		=> 'members',
																'where'		=> "member_group_id = {$r['row_id']} OR mgroup_others LIKE '%,{$r['row_id']},%'",
															)
														);
				$r['_group_name']	= $this->caches['group_cache'][ $r['row_id'] ]['g_title'];
				$r['_total']		= $count['total'];
				
				$groups .= $this->html->acpGroupRow($r);
			}
		}
		
		$this->registry->output->html .= $this->html->acpPermsOverview( $members, $groups );
	}
	

	/**
	 * Remove ACP restrictions - this will make a restricted member or group have full permissions
	 *
	 * @access	private
	 * @param	string		[member|group]
	 * @return	void
	 * @since	2.1.0.2005-7-11
	 */
	private function _removePermissions( $type='member' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$row_id = $type == 'member' ? intval( $this->request['mid'] ) : intval( $this->request['gid'] );
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $row_id )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_notypeid'], $type );
			$this->_acppermsList();
			return;
		}
		
		//-------------------------------
		// Remove member's row
		//-------------------------------
		
		$this->DB->delete( 'admin_permission_rows', 'row_id=' . $row_id );
		
		//-------------------------------
		// Print
		//-------------------------------
		
		$this->registry->output->global_message = $this->lang->words['r_lifted'];
		$this->_acppermsList();
	}
	
	/**
	 * ACP Perms: Add member form
	 *
	 * @access	private
	 * @param	string		[member|group]
	 * @return	void
	 * @since	2.1.0.2005-7-7
	 */
	private function _addRole( $type='member' )
	{
		$this->registry->output->extra_nav[]		= array( '', $this->lang->words['r_nav'] );

		//-------------------------------
		// Show the form
		//-------------------------------
		
		$method	= $type == 'member' ? 'restrictionsMemberForm' : 'restrictionsGroupForm';
		
		$this->registry->output->html .= $this->html->$method();
	}
	
	/**
	 * ACP Perms: Finish adding the member
	 *
	 * Checks input, adds row to DB if OK
	 * This bud's for you
	 *
	 * @access	private
	 * @return	void
	 * @since	2.1.0.2005-7-8
	 */
	private function _addMemberDo()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$name	= trim( $this->request['entered_name'] );
		$isok	= 0;
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $name )
		{
			$this->registry->output->global_message = $this->lang->words['r_entername'];
			$this->_addRole( 'member' );
			return;
		}
		
		//-------------------------------
		// Get member...
		//-------------------------------
		
		$member = IPSMember::load( $name, 'groups' , 'displayname' );

		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $member['member_id'] )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_noname'], $name );
			$this->_addRole( 'member' );
			return;
		}
		
		//-------------------------------
		// Already got 'em
		//-------------------------------
		
		$test = $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $member['member_id'] ) );
		
		if ( $test['row_id'] )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_namealready'], $name );
			$this->_addRole( 'member' );
			return;
		}
		
		//-------------------------------
		// Don't restrict ourselves
		//-------------------------------
		if( $member['member_id'] == $this->memberData['member_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_ownaccount'];
			$this->_addRole( 'member' );
			return;			
		}

		//-------------------------------
		// Primary ACP group?
		//-------------------------------
		
		if ( $member['g_access_cp'] )
		{
			$isok = 1;
		}
		
		//-------------------------------
		// Secondary ACP group?
		//-------------------------------
		
		else if ( $member['mgroup_others'] )
		{
			foreach( explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) ) as $gid )
			{
				if ( $this->caches['group_cache'][ $gid ]['g_access_cp'] )
				{
					$isok = 1;
					break;
				}
			}
		}
		
		//-------------------------------
		// Not oK?
		//-------------------------------
		
		if ( ! $isok )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_noaccess'], $member['members_display_name'] );
			$this->_addRole( 'member' );
			return;
		}
		
		//-------------------------------
		// Does we haz groop perms?
		// Too many LOLCATZ >.<
		//-------------------------------
		
		$groups[]	= $member['member_group_id'];
		$restrict	= array();
		
		if( $member['mgroup_others'] )
		{
			$groups	= array_merge( $groups, explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) ) );
		}
		
		$this->DB->build( array( 'select' => '*', 'from' => 'admin_permission_rows', 'where' => "row_id_type='group' AND row_id IN(" . implode( ',', $groups ) . ")" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$restrict = array_merge( $restrict, unserialize($r['row_perm_cache']) );
		}
		
		//-------------------------------
		// A-OK
		//-------------------------------

		$this->DB->insert( 'admin_permission_rows', array( 'row_id'				=> $member['member_id'],
																'row_id_type'		=> 'member',
																'row_perm_cache'	=> serialize( $restrict ),
																'row_updated'		=> time() ) );

		$message = count($restrict) ? $this->lang->words['r_restrictbase'] : $this->lang->words['r_noacpaccessuntil'];
		
		$this->registry->output->global_message = sprintf( $this->lang->words['r_addedmember'], $member['members_display_name'], $message );

		$this->request[ 'mid'] =  $member['member_id'] ;
		$this->_restrictionsForm( 'member' );
	}

	/**
	 * ACP Perms: Finish adding the group
	 *
	 * @access	private
	 * @return	void
	 * @since	2.1.0.2005-7-8
	 */
	private function _addGroupDo()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$g_id	= intval( $this->request['entered_group'] );
		$isok	= 0;
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( !$g_id )
		{
			$this->registry->output->global_message = $this->lang->words['r_nogroup'];
			$this->_addRole( 'group' );
			return;
		}
		
		//-------------------------------
		// Get member...
		//-------------------------------
		
		$group = $this->caches['group_cache'][ $g_id ];

		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $group['g_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_nofindgroup'];
			$this->_addRole( 'group' );
			return;
		}
		
		//-------------------------------
		// Already got 'em
		//-------------------------------
		
		$test = $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='group' AND row_id=" . $g_id ) );
		
		if ( $test['row_id'] )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_groupalready'], $group['g_title'] );
			$this->_addRole( 'group' );
			return;
		}
		
		//-------------------------------
		// Don't restrict ourselves
		//-------------------------------
		if( $g_id == $this->memberData['member_group_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['r_ownaccount'];
			$this->_addRole( 'group' );
			return;			
		}

		if( in_array( $g_id, explode( ',', IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) ) )
		{
			$this->registry->output->global_message = $this->lang->words['r_ownaccount'];
			$this->_addRole( 'group' );
			return;			
		}

		//-------------------------------
		// Primary ACP group?
		//-------------------------------
		
		if ( !$group['g_access_cp'] )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['r_groupnoacp'], $group['g_title'] );
			$this->_addRole( 'group' );
			return;
		}
		
		//-------------------------------
		// A-OK
		//-------------------------------

		$this->DB->insert( 'admin_permission_rows', array( 'row_id'				=> $g_id,
																'row_id_type'		=> 'group',
																'row_perm_cache'	=> serialize( array() ),
																'row_updated'		=> time() ) );
																		
		$this->registry->output->global_message = sprintf( $this->lang->words['r_addedgroup'], $group['g_title'] );
		$this->_acppermsList();
	}
}