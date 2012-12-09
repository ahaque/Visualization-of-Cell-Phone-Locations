<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum Permission Masks
 * Last Updated: $LastChangedDate: 2009-02-23 16:27:28 -0500 (Mon, 23 Feb 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		17th March 2002
 * @version		$Rev: 4081 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_forums_permissions extends ipsCommand
{
	/**
	 * Forum functions object
	 *
	 * @access	private
	 * @var		object
	 */
	private $forumfunc;
	
	/**
	 * Main execution point
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Setup Forum Classes */
		$this->registry->class_forums->forumsInit();
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/admin_forum_functions.php' );

		$this->forumfunc = new admin_forum_functions( $registry );
		$this->forumfunc->forumsInit();
		
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_permissions' );
		$this->html->form_code    = 'module=forums&amp;section=permissions&amp;';
		$this->html->form_code_js = 'module=forums&amp;section=permissions&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_forums' ) );
		
		/* Determine what todo */
		switch( $this->request['do'] )
		{
			case 'fedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_edit' );
				$this->forumPermissions();
			break;
				
			case 'pdelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_delete' );
				$this->deleteMask();
			break;
				
			case 'dofedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_edit' );
				$this->saveForumPermissions();
			break;
				
			case 'permsplash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_view' );
				$this->permsplash();
			break;		
					
			case 'preview_forums':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_view' );
				$this->previewForumPermissions();
			break;
				
			case 'dopermadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_add' );
				$this->addNewPerm();
			break;
			
			case 'donameedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_edit' );
				$this->editPermName();
			break;			
			
			case 'splash':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permmasks_view' );
				$this->permissionsSplash();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Shows the forum permission preview popup
	 *
	 * @access	public
	 * @return	void
	 **/
	public function previewForumPermissions()
	{
		/* Check ID */
		$perm_id = intval( $this->request['id'] );
		if( ! $perm_id )
		{
			$this->registry->output->showError( $this->lang->words['per_setid'], 11342 );
		}
		
		/* Query the set name */
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=" . $perm_id ) );
		$this->DB->execute();
		
		if ( ! $perms = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['per_setid'], 11343 );
		}
		
		/* Type of permission to preview */
		switch( $this->request['t'] )
		{
			case 'start':
				$human_type = $this->lang->words['per_start'];
				$code_word  = 'perm_4';
				break;
				
			case 'reply':
				$human_type = $this->lang->words['per_reply'];
				$code_word  = 'perm_3';
				break;
			
			case 'show':
				$human_type = $this->lang->words['per_show'];
				$code_word  = 'perm_view';
				break;
				
			case 'upload':
				$human_type = $this->lang->words['per_upload'];
				$code_word  = 'perm_5';
				break;
				
			case 'download':
				$human_type = $this->lang->words['per_download'];
				$code_word  = 'perm_6';
				break;				
				
			default:
				$human_type = $this->lang->words['per_read'];
				$code_word  = 'perm_2';
				break;
		}
		
		/* Get forums */		
		$theforums  = $this->forumfunc->adForumsForumList(1);
		
		/* Loop through forums */
		$rows = array();
		
		foreach( $theforums as $v )
		{
			$id   = $v[0];
			$name = $v[1];
			
			$this->registry->class_forums->forum_by_id[$id][ $code_word ] = isset( $this->registry->class_forums->forum_by_id[$id][ $code_word ] ) ? $this->registry->class_forums->forum_by_id[$id][ $code_word ] : '';
			
			if( $this->registry->class_forums->forum_by_id[$id][ $code_word ] == '*' )
			{
				$rows[] = array( 'css' => 'color:green', 'name' => $name );
			}
			else if (preg_match( "/(^|,)".$perm_id."(,|$)/", $this->registry->class_forums->forum_by_id[$id][ $code_word ]) )
			{
				$rows[] = array( 'css' => 'color:green;font-weight:bold', 'name' => $name );
			}
			else
			{
				if( $code_word != 'perm_view' AND $this->registry->class_forums->forum_by_id[$id]['parent_id'] == 'root' )
				{
					/* Categories */
					$rows[] = array( 'css' => 'color:grey', 'name' => $name );
				}
				else
				{
					/* No Access */
					$rows[] = array( 'css' => 'color:red;font-weight:bold', 'name' => $name );
				}
			}
		}
										 
		$type_drop = $this->registry->output->formDropdown( 't', array( 
																		array( 'start'   , $this->lang->words['per_start_m']          ),
																		array( 'reply'   , $this->lang->words['per_reply_m']    		  ),
																		array( 'read'    , $this->lang->words['per_read_m']        	  ),
																		array( 'show'    , $this->lang->words['per_show_m']           ),
																		array( 'upload'  , $this->lang->words['per_upload_m']     		),
																		array( 'download', $this->lang->words['per_download_m']			  ),
																	), $this->request['t'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->previewForumPermissions( $id, $perms['perm_name'], $type_drop, $rows, $human_type );
		$this->registry->output->printPopupWindow();
	}	
	
	/**
	 * Save Forum Permission Matrix
	 *
	 * @access	public
	 * @return	void
	 **/
	public function saveForumPermissions()
	{
		/* Check ID */
		$gid = intval( $this->request['id'] );
		
		if( ! $gid )
		{
			$this->registry->output->showError( $this->lang->words['per_groupid'], 11344 );
		}
		
		/* Check the mask */
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=" . $gid ) );
		$this->DB->execute();
		
		if ( ! $gr = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['per_groupid'], 11345 );
		}
				
		/* Permission Fields */
		$permission_fields = array( 'view' => 'perm_view', 'read' => 'perm_2', 'reply' => 'perm_3', 'start' => 'perm_4', 'upload' => 'perm_5', 'download' => 'perm_6' );
		
		/* Query all the forum permissions */
		$forum_data = $this->forumfunc->adForumsForumData();
		
		//echo '<pre>'; print_r( $this->request );exit();
		foreach( $forum_data as $r )
		{
			/* Initialize the array */
			$new_permissions = array();
			
			/* Loop through each permission type */
			foreach( $permission_fields as $k => $v )
			{
				/* Don't change global permissions */				
				if( $r[$v] != '*' )
				{					
					if( isset( $this->request[$k] ) && is_array( $this->request[$k] ) )
					{
						/* Check for forum */
						$forum_checked = isset($this->request[$k][ $r['id'] ]);
						
						/* Check to see if the forum is currently added to this mask */
						$_perm_array = explode( ',', IPSText::cleanPermString( $r[$v] ) );
						
						if( in_array( $gid, $_perm_array ) )
						{
							/* The forum was unchecked, so we need to remove it */
							if( ! $forum_checked )
							{
								unset( $_perm_array[ array_search( $gid, $_perm_array ) ] );
							}
						}
						else
						{
							/* The forum was checked, so we need to add it */
							if( $forum_checked )
							{
								$_perm_array[] = $gid;
							}
						}
						
						/* Set the new string */
						$new_permissions[$v] = ',' . implode( ',', $_perm_array ) . ',';
					}
					else
					{
						$new_permissions[$v] = '';
					}
				}
				else
				{
					$new_permissions[$v] = '*';
				}				
			}
			
			/* Root Forum */
			if( $r['root_forum'] )
			{
				$new_permissions['perm_2'] = $new_permissions['perm_3'] = $new_permissions['perm_4'] = $new_permissions['perm_5'] = $new_permissions['perm_6'] = '';
			}			
			
			/* Update the database */
			$this->DB->update( 'permission_index', $new_permissions, "perm_type='forum' AND app='forums' AND perm_type_id={$r['id']}" );
		}
		
		/* All Done */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['per_editedlog'], $gr['perm_name'] ) );		
		$this->registry->output->global_message = $this->lang->words['per_updated'];
		$this->permissionsSplash();
	}	
	
	/**
	 * Removes a permission set
	 *
	 * @access	public
	 * @return	void
	 **/
	public function deleteMask()
	{
		/* Check ID */
		$old_id = intval( $this->request['id'] );
		
		if( ! $old_id )
		{
			$this->registry->output->showError( $this->lang->words['per_setid'], 11346 );
		}
		
		/* Permission Fields */
		$permission_fields = array( 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' );
		
		/* Query all the forum permissions */
		$this->DB->build( array( 'select' => '*', 'from' => 'permission_index', 'where' => "perm_type='forum' AND app='forums'" ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			/* Perms */
			foreach( $permission_fields as $perm )
			{
				if( isset( $r[$perm] ) && $r[$perm] != '*' && $r[$perm] )
				{
					/* Arrayize */
					$_perm_array = explode( ',', IPSText::cleanPermString( $r[$perm] ) );
					
					/* Check for the mask */										
					if( in_array( $old_id, $_perm_array ) )
					{
						unset( $_perm_array[ array_search( $old_id, $_perm_array ) ] );
					}
					
					/* Back to string */
					$r[$perm] = ',' . implode( ',', $_perm_array ) . ',';
				}
			}
						
			/* Update */
			$this->DB->update( 'permission_index', $r, "perm_id={$r['perm_id']}" );
		}
		
		/* Delete the mask */
		$this->DB->delete( 'forum_perms', "perm_id={$old_id}" );
		
		/* Done */
		$this->registry->output->global_message = $this->lang->words['per_removed'];
		$this->permissionsSplash();
	}	
	
	/**
	 * Adds a new permission set
	 *
	 * @access	public
	 * @return	void
	 **/
	public function addNewPerm()
	{
		/* Check Perm Name */
		$this->request['new_perm_name'] = trim( $this->request['new_perm_name'] );
		
		if( $this->request['new_perm_name'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['per_entername'], 11347 );
		}
		
		/* Perm set to copy */
		$copy_id = intval( $this->request['new_perm_copy'] );
		
		/* Insert new permission set */
		$this->DB->insert( 'forum_perms', array( 'perm_name' => $this->request['new_perm_name'] ) );		
		$new_id = $this->DB->getInsertId();
		
		/* Copy permission */
		if( $new_id && $copy_id )
		{
			/* Permission Fields */
			$permission_fields = array( 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' );
			
			/* Query all the forum permissions */
			$this->DB->build( array( 'select' => '*', 'from' => 'permission_index', 'where' => "perm_type='forum' AND app='forums'" ) );
			$outer = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $outer ) )
			{
				/* Perms */
				foreach( $permission_fields as $perm )
				{
					if( isset( $r[$perm] ) )
					{
						if( in_array( $copy_id, explode( ',', IPSText::cleanPermString( $r[$perm] ) ) ) )
						{
							$r[$perm] .= "{$new_id},";
						}
					}	
				}
				
				/* Update */
				$this->DB->update( 'permission_index', $r, "perm_id={$r['perm_id']}" );
			}
		}
		
		/* Done */
		$this->registry->output->global_message = sprintf( $this->lang->words['per_added'], $this->request['new_perm_name'] );
		$this->permissionsSplash();
	}	
	
	/**
	 * Changes the name of a permission set
	 *
	 * @access	public
	 * @return	void
	 **/
	public function editPermName()
	{
		/* ID */
		$gid = intval( $this->request['id'] );		
		
		/* Check ID */
		if( ! $gid )
		{
			$this->registry->output->showError( $this->lang->words['per_groupid'], 11348 );
		}
		
		/* Check Name */
		if( $this->request['perm_name'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['per_entername'], 11349 );
		}
		
		/* Query group */		
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id={$gid}" ) );
		$this->DB->execute();
		
		if ( ! $gr = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['per_groupid'], 11350 );
		}
		
		/* Update the database */
		$this->DB->update( 'forum_perms', array( 'perm_name' => $this->request['perm_name'] ), 'perm_id=' . $gid );
		
		/* Done */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['per_editedlog'], $gr['perm_name'] ) );		
		$this->registry->output->global_message = $this->lang->words['per_updated'];
		$this->forumPermissions();
	}	

	/**
	 * Forum Permission Matrix
	 *
	 * @access	public
	 * @return	void
	 **/
	public function forumPermissions()
	{
		/* Check for ID */
		if( $this->request['id'] == "")
		{
			$this->registry->output->showError( $this->lang->words['per_groupid'], 11351 );
		}
		
		/* Permission Class */
		require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
		$permissions = new classPublicPermissions( ipsRegistry::instance() );		
		
		/* Page Setup */
		$this->registry->output->nav[]           = array( $this->form_code.'&do=permsplash', $this->lang->words['per_manage'] );
		$this->registry->output->nav[]           = array( '', $this->lang->words['per_addedit'] );		
		
		/* Query Mask */
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=" . intval( $this->request['id'] ) ) );
		$this->DB->execute();		
		$group = $this->DB->fetch();
		
		/* Data */
		$gid        = $group['perm_id'];
		$gname      = $group['perm_name'];		
		$forum_data = $this->forumfunc->adForumsForumData();
				
		/* Permission Fields */
		$permission_fields = array( 'view' => 'perm_view', 'read' => 'perm_2', 'reply' => 'perm_3', 'start' => 'perm_4', 'upload' => 'perm_5', 'download' => 'perm_6' );		
		
		/* Loop through masks and build output array */
		$permission_rows = array();
		
		foreach( $forum_data as $r )
		{
			/* INI Perms */
			$_perms = array();
			
			foreach( $permission_fields as $perm => $key )
			{
				if( $r[$key] == '*' )
				{
					$_perms[$perm] = $this->lang->words['per_global'];
				}
				else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r[$key] ) )
				{
					$_perms[$perm] = 'checked';
				}
				else
				{
					$_perms[$perm] = '';
				}
			}

			/* Root Forum */
			if( $r['root_forum'] )
			{
				$r['css'] = 'tablerow4';
				$_perms['download'] = $_perms['upload'] = $_perms['reply'] = $_perms['start'] = $_perms['read'] = $this->lang->words['per_notused'];
			}
			else
			{
				$r['css'] = 'tablerow1';
			}
			
			/* Perms */
			$r['_perms'] = $_perms;
			
			/* Add to array */
			$permission_rows[] = $r;
		}
	
		/* Output */
		$this->registry->output->html .= $this->html->permissionsForum( $gid, $gname, $permission_rows, $this->registry->class_forums->forumById( $this->request['id'] ) );		
	}	
	
	/**
	 * Displays the permission splash screen
	 *
	 * @access	public
	 * @return	void
	 **/
	public function permissionsSplash()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$perms   = array();
		$mems    = array();
		$groups  = array();
		$dlist   = "";
		$content = "";
		
		//-----------------------------------------
		// Page title & desc
		//-----------------------------------------

		$this->registry->output->nav[]           = array( $this->form_code.'&do=permsplash', $this->lang->words['per_manage'] );
								
		//-----------------------------------------
		// Get the names for the perm masks w/id
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$perms[ $r['perm_id'] ] = $r['perm_name'];
		}
		
		//-----------------------------------------
		// Get the number of members using this mask
		// as an over ride
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> 'COUNT(member_id) as count, org_perm_id',
										'from'	=> 'members',
										'where'	=> "(org_perm_id " . $this->DB->buildIsNull( false ) . " AND org_perm_id != '')",
										'group'	=> 'org_perm_id',
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if ( strstr( $r['org_perm_id'] , "," ) )
			{
				foreach( explode( ",", $r['org_perm_id'] ) as $pid )
				{
					$mems[ $pid ]  = ! isset( $mems[ $pid ] ) ? 0 : $mems[ $pid ];
					$mems[ $pid ] += $r['count'];
				}
			}
			else
			{
				$mems[ $r['org_perm_id'] ] += $r['count'];
			}
			
		}
	
		//-----------------------------------------
		// Get the member group names and the mask
		// they use
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'g_id, g_title, g_perm_id', 'from' => 'groups' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if ( strstr( $r['g_perm_id'] , "," ) )
			{
				foreach( explode( ",", $r['g_perm_id'] ) as $pid )
				{
					$groups[ $pid ][] = $r['g_title'];
				}
			}
			else
			{
				$groups[ $r['g_perm_id'] ][] = $r['g_title'];
			}
		}
		
		//-----------------------------------------
		// Print the splash screen
		//-----------------------------------------
		
		$display_groups = array();
		foreach( $perms as $id => $name )
		{
			$groups_used = "";
			$mems_used   = 0;
			$is_active   = 0;
			$dlist      .= "<option value='$id'>$name</option>\n";
			
			if ( isset( $groups[ $id ] ) AND is_array( $groups[ $id ] ) )
			{
				foreach( $groups[ $id ] as $g_title )
				{
					$groups_used .= '&middot; ' . $g_title . "<br />";
				}
				
				$is_active = 1;
			}
			else
			{
				$groups_used = "{$this->lang->words['per_none']}";
			}			
			
			if ( isset($mems[ $id ]) AND $mems[ $id ] > 0 )
			{
				$is_active = 1;
			}
			
			$r['id']       = $id;
			$r['name']     = $name;
			$r['isactive'] = $is_active;
			$r['groups']   = $groups_used;
			$r['mems']     = isset( $mems[ $id ] ) ? intval( $mems[ $id ] ) : 0;
			
			$display_groups[] = $r;
		}
		
		$this->registry->output->html .= $this->html->permissionsSplash( $display_groups, $dlist );
	}	
}


?>