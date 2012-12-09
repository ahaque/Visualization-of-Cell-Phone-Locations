<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum management
 * Last Updated: $Date: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		Tuesday 17th August 2004
 * @version		$Revision: 5045 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_forums_forums extends ipsCommand
{
	/**
 	* Skin HTML object
 	*
 	* @access	private
 	* @var		object
 	*/
	private $html;
	
	/**
	 * Forum functions object
	 *
	 * @access	private
	 * @var		object
	 */
	private $forum_functions;
	
	/**
	 * Main entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		Outputs to screen
	 **/	
	public function doExecute( ipsRegistry $registry )
	{
		/* Forum functions set up */

		require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/admin_forum_functions.php' );

		$this->forum_functions = new admin_forum_functions( $registry );
		$this->forum_functions->forumsInit();
		
		$this->request['showall'] = intval( $this->request['showall'] );
		
		//-----------------------------------------
		// Load skin & lang
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_forums' );
		$this->html->form_code    = 'module=forums&amp;section=forums&amp;';
		$this->html->form_code_js = 'module=forums&amp;section=forums&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_forums' ) );
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->forum_functions->html =& $this->html;
		
		//-----------------------------------------
		// To do...
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'forum_add':
			case 'new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_add' );
				$this->forumForm( 'new' );
				break;
			case 'donew':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_add' );
				$this->forumSave( 'new' );
				break;
			//------------------- ----------------------
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_edit' );
				$this->forumForm( 'edit' );
				break;
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_edit' );
				$this->forumSave( 'edit' );
				break;
			//-----------------------------------------
			case 'pedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_permissions' );
				$this->permEditForm();
				break;
			case 'pdoedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_permissions' );
				$this->permDoEdit();
				break;
			//-----------------------------------------
			case 'doreorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_reorder' );
				$this->doReorder();
				break;
			//-----------------------------------------
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_delete' );
				$this->deleteForm();
				break;
			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_delete' );
				$this->doDelete();
				break;
			//-----------------------------------------
			case 'recount':
				$this->recount();
				break;
			//-----------------------------------------
			case 'empty':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_empty' );
				$this->emptyForum();
				break;
			case 'doempty':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_empty' );
				$this->doEmpty();
				break;
			//-----------------------------------------
			case 'frules':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_rules' );
				$this->showRules();
				break;
			case 'dorules':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_rules' );
				$this->doRules();
				break;
			//-----------------------------------------
			case 'skinedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_skins' );
				$this->skinEdit();
				break;
			case 'doskinedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_skins' );
				$this->doSkinEdit();
				break;
			//-----------------------------------------
			case 'forums_overview':
			default:
				$this->request['do'] = 'forums_overview';
				$this->showForums();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Edit skins assigned to forums
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function skinEdit()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		
		if( $this->request['f'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1131 );
		}
		
		/* Forum Data */
		$forum = $this->forum_functions->forum_by_id[ $this->request['f'] ];
		
		/* Check the forum */
		if ( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1132 );
		}
		
		if ( ! $forum['skin_id'] )
		{
			$forum['skin_id'] = -1;
		}
		
		/* Skins */
		$skin_list	= array_merge( array( array( -1, $this->lang->words['for_noneall'] ) ), $this->registry->output->generateSkinDropdown() );

		/* Form Data */
		$forum['fsid']              = $this->registry->output->formDropdown( 'fsid', $skin_list, $forum['skin_id'] );
		$forum['apply_to_children'] = $this->registry->output->formYesNo( 'apply_to_children' );
		
		/* Output */
		$this->registry->output->html .= $this->html->forumSkinOptions( $this->request['f'], $forum );
	}
	
	/**
	 * Save the skin assigned to the forum
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function doSkinEdit()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		
		/* Check the forum */
		if ($this->request['f'] == "")
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1133 );
		}
		
		/* Forum Data */
		$forum = $this->forum_functions->forum_by_id[ $this->request['f'] ];
		
		/* Update the forum */
		$this->DB->update( 'forums', array( 'skin_id' => $this->request['fsid'] ), 'id='.$this->request['f'] );
		
		/* Apply to children */
		if( $this->request['apply_to_children'] )
		{
			$ids = $this->forum_functions->forumsGetChildren( $this->request['f'] );
			
			if ( count( $ids ) )
			{
				$this->DB->update( 'forums', array( 'skin_id' => $this->request['fsid'] ), 'id IN ('.implode(",",$ids).')' );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['for_skinup'];
		
		$this->recacheForums();
		
		$this->forum_functions->forumsInit();
		
		/* Bounce */		
		$this->request['f'] = $this->forum_functions->forum_by_id[ $this->request['f'] ]['parent_id'];
		$this->showForums();
	}
	
	/**
	 * Show the form to edit rules
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function showRules()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		
		if( ! $this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1134 );
		}
		
		$this->DB->build( array( 'select' => 'id, name, show_rules, rules_title, rules_text', 'from' => 'forums', 'where' => "id=".$this->request['f'] ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( ! $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1135 );
		}
		
		$forum = $this->DB->fetch();
		
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 0;
		IPSText::getTextClass( 'bbcode' )->parse_html		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'rules';
        
        /* Form Fields */
        $forum['_show_rules'] = $this->registry->output->formDropdown( "show_rules", array( 
																							array( '0' , $this->lang->words['for_rulesdont'] ),
																							array( '1' , $this->lang->words['for_ruleslink'] ),
																							array( '2' , $this->lang->words['for_rulesfull'] )
																							), $forum['show_rules'] );
																								
		$forum['_title'] = $this->registry->output->formInput( "title", IPSText::stripslashes( str_replace( "'", '&#039;', $forum['rules_title'] ) ) );
									     
		if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
		{
			$forum['rules_text'] = IPSText::getTextClass( 'bbcode' )->convertForRTE( $forum['rules_text'] );
		}
		else
		{
			$forum['rules_text'] = IPSText::getTextClass( 'bbcode' )->preEditParse( $forum['rules_text'] );
		}
		
		$forum['_editor'] = IPSText::getTextClass( 'editor' )->showEditor( $forum['rules_text'], 'body' );

		/* Output */
		$this->registry->output->html .= $this->html->forumRulesForm( $forum['id'], $forum );
	}
	
	/**
	 * Save the forum rules
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function doRules()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
				
		if( $this->request['f'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1136 );
		}
		
    	//-----------------------------------------
        // Load editor/bbcode
        //-----------------------------------------
       
        $_POST[ 'body' ]                   = IPSText::getTextClass( 'editor' )->processRawPost( 'body' );
        
		IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 0;
		IPSText::getTextClass( 'bbcode' )->parse_html		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'rules';

		$_POST[ 'body' ]        			= IPSText::getTextClass( 'bbcode' )->preDbParse( $_POST[ 'body' ] );		
		
		$rules = array( 
						'rules_title'    => IPSText::makeSlashesSafe( IPSText::stripslashes( $_POST['title'] ) ),
						'rules_text'     => IPSText::makeSlashesSafe( $_POST['body'] ),
						'show_rules'     => $this->request['show_rules']
					  );
					  
		$this->DB->update( 'forums', $rules, 'id='.$this->request['f'] );
		
		$this->recacheForums();
		$this->registry->output->global_message = $this->lang->words['for_rulesup'];
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		$this->request['f'] = $this->forum_functions->forum_by_id[ $this->request['f'] ]['parent_id'];
		$this->showForums();
	}
	
	/**
	 * Recount the forum
	 *
	 * @access	public
	 * @param	integer		[optional] Forum id
	 * @return	void		Outputs to screen
	 **/	
	public function recount($f_override="")
	{
		if ($f_override != "")
		{
			// Internal call, remap
			
			ipsRegistry::$request[ 'f'] =  $f_override ;
		}
		
		require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
		$modfunc = new moderatorLibrary( $this->registry );
		
		
		$modfunc->forumRecount($this->request['f']);

		$this->recacheForums();
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_recountedlog'], $this->forum_functions->forum_by_id[$this->request['f']]['name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['for_resynched'];
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		ipsRegistry::$request[ 'f'] =  $this->forum_functions->forum_by_id[ $this->request['f'] ]['parent_id'] ;
		$this->showForums();
	}
	
	/**
	 * Show the form to empty a forum
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function emptyForum()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		$form_array         = array();
		
		if( $this->request['f'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1137 );
		}
		
		$this->DB->build( array( 'select' => 'id, name', 'from' => 'forums', 'where' => "id=".$this->request['f'] ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( !$this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1138 );
		}
		
		$forum = $this->DB->fetch();
				
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->forumEmptyForum( $this->request['f'], $forum );		
	}
	
	/**
	 * Empty a forum
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function doEmpty()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
				
		//-----------------------------------------
		// Get module
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
		$modfunc = new moderatorLibrary( $this->registry );
		
		if( $this->request['f'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['for_noid_source'], 1139 );
		}
		
		//-----------------------------------------
		// Check to make sure its a valid forum.
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'id, posts, topics', 'from' => 'forums', 'where' => "id=" . $this->request['f'] ) );
		$this->DB->execute();
		
		if( ! $forum = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['for_nodetails'], 11310 );
		}
		
		$this->DB->build( array( 'select' => 'tid', 'from' => 'topics', 'where' => "forum_id=" . $this->request['f'] ) );
		$outer = $this->DB->execute();
		
		//-----------------------------------------
		// What to do..
		//-----------------------------------------
		
		while( $t = $this->DB->fetch($outer) )
		{
			$modfunc->topicDelete( $t['tid'] );
		}
		
		//-----------------------------------------
		// Rebuild stats
		//-----------------------------------------
		
		$modfunc->forumRecount( $this->request['f'] );
		$modfunc->statsRecount();
		
		//-----------------------------------------
		// Rebuild forum cache
		//-----------------------------------------
		
		$this->recacheForums();
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_emptiedlog'], $this->request['name'] ) );
		
		$this->request['f'] = $this->forum_functions->forum_by_id[ $this->request['f'] ]['parent_id'];

		$this->registry->output->global_message   = $this->lang->words['for_emptied'];
		$this->showForums();
	}
	
	/**
	 * Show the form to delete a form
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function deleteForm()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );		
		$form_array = array();
				
		if ( ! $this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid_delete'], 11311 );
		}
		
		$this->DB->build( array( 'select' => 'id, name, parent_id', 'from' => 'forums', 'order' => 'position' ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Make sure we have more than 1
		// forum..
		//-----------------------------------------
		
		if( $this->DB->getTotalRows() < 2 )
		{
			$this->registry->output->showError( $this->lang->words['for_lastforum'], 11312 );
		}
		
		while( $r = $this->DB->fetch() )
		{
			if( $r['id'] == $this->request['f'] )
			{
				$name 	= $r['name'];
				$is_cat	= $r['parent_id'] > 0 ? 0 : 1;
				continue;
			}
		}
		
		$form_array = $this->forum_functions->adForumsForumList( 1 );
		
		//-----------------------------------------
		// Count the number of topics
		//-----------------------------------------
		
		$posts = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'topics', 'where' => 'forum_id='.$this->request['f'] ) );

		//-----------------------------------------
		// Start form
		//-----------------------------------------

		/* Move dropds */
		if( $posts['count'] )
		{
			$move = $this->registry->output->formDropdown( "MOVE_ID", $form_array );
		}

		/* Output */
		$this->registry->output->html .= $this->html->forumDeleteForm( $this->request['f'], $name, $move );
	}
	
	/**
	 * Delete a forum
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function doDelete()
	{
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->registry->adminFunctions->checkSecurityKey();
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->request['f']             = intval( $this->request['f'] );
		$this->request['MOVE_ID']       = intval( $this->request['MOVE_ID'] );
		$this->request['new_parent_id'] = intval( $this->request['new_parent_id'] );
		
		$forum	= $this->registry->class_forums->forum_by_id[ $this->request['f'] ];
		
		if( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid_source'], 11313 );
		}
		
		if( ! $this->request['new_parent_id'] )
		{
			$this->request['new_parent_id'] = -1;
		}
		else
		{
			if( $this->request['new_parent_id'] == $this->request['f'] )
			{
				$this->registry->output->global_message = $this->lang->words['for_child_no_parent'];
				$this->deleteForm();
				return;
			}
		}
		
		//-----------------------------------------
		// Would deleting this category orphan the only
		// remaining forums?
		//-----------------------------------------
		
		if( $forum['parent_id'] == -1 )
		{
			$otherParent	= 0;
			
			foreach( $this->registry->class_forums->forum_by_id as $id => $data )
			{
				if( $data['parent_id'] == -1 )
				{
					$otherParent	= $id;
					break;
				}
			}
			
			if( !$otherParent )
			{
				$this->registry->output->showError( $this->lang->words['nodelete_last_cat'], 11364 );
			}
		}
		
		//-----------------------------------------
		// Get library
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
		$modfunc = new moderatorLibrary( $this->registry );
				
		//-----------------------------------------
		// Move stuff
		//-----------------------------------------
		
		if( $this->request['MOVE_ID'] )
		{
			if( $this->request['MOVE_ID'] == $this->request['f'] )
			{
				$this->registry->output->global_message = $this->lang->words['for_wherewhatwhy'];
				$this->deleteForm();
			}
			
			//-----------------------------------------
			// Move topics...
			//-----------------------------------------
			
			$this->DB->update( 'topics', array( 'forum_id' => $this->request['MOVE_ID'] ), 'forum_id='.$this->request['f'] );
			
			//-----------------------------------------
			// Move polls...
			//-----------------------------------------
			
			$this->DB->update( 'polls', array( 'forum_id' => $this->request['MOVE_ID'] ), 'forum_id='.$this->request['f'] );
			
			//-----------------------------------------
			// Move voters...
			//-----------------------------------------
			
			$this->DB->update( 'voters', array( 'forum_id' => $this->request['MOVE_ID'] ), 'forum_id='.$this->request['f'] );
			
			$modfunc->forumRecount( $this->request['MOVE_ID'] );
		}
		
		//-----------------------------------------
		// Delete the forum
		//-----------------------------------------
		
		$this->DB->delete( 'forums', "id=".$this->request['f'] );
		$this->DB->delete( 'permission_index', "app='forums' AND perm_type='forum' AND perm_type_id=".$this->request['f'] );
		
		//-----------------------------------------
		// Remove moderators from this forum
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "forum_id LIKE '%,{$this->request['f']},%'" ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$forums		= explode( ',', IPSText::cleanPermString( $r['forum_id'] ) );
			$newForums	= array();
			
			foreach( $forums as $aForumId )
			{
				if( $aForumId != $this->request['f'] )
				{
					$newForums[] = $aForumId;
				}
			}
			
			if( !count($newForums) )
			{
				$this->DB->delete( 'moderators', "mid=" . $r['mid'] );
			}
			else
			{
				$this->DB->update( 'moderators', array( 'forum_id' => ',' . implode( ',', $newForums ) . ',' ), 'mid=' . $r['mid'] );
			}
		}
		
		//-----------------------------------------
		// Delete forum subscriptions
		//-----------------------------------------
		
		$this->DB->delete( 'forum_tracker', "forum_id=".$this->request['f'] );
		
		//-----------------------------------------
		// Update children
		//-----------------------------------------
		
		$this->DB->update( 'forums', array( 'parent_id' => $this->request['new_parent_id'] ), "parent_id=" . $this->request['f'] );
		
		//-----------------------------------------
		// Rebuild forum cache
		//-----------------------------------------
		
		$this->recacheForums();
		
		//-----------------------------------------
		// Rebuild moderator cache
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/modules_admin/forums/moderator.php' );

		$moderator = new admin_forums_forums_moderator( $this->registry );
		$moderator->makeRegistryShortcuts( $this->registry );
		$moderator->rebuildModeratorCache();
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_removedlog'], $forum['name'] ) );
		
		$this->registry->output->doneScreen($this->lang->words['for_removed'], $this->lang->words['for_control'], $this->form_code, 'redirect' );
	}

	/**
	 * Show the form to edit a forum
	 *
	 * @access	public
	 * @param	string		[new|edit]
	 * @param	boolean		Whether to change forum to category/back
	 * @return	void		Outputs to screen
	 **/	
	public function forumForm( $type='edit', $changetype=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$addnew_type = ( $this->request['type'] AND $this->request['type'] ) ? $this->request['type'] : 'forum';
		
		$form        = array();
		$forum       = array();
		$forum_id    = $this->request['f']           ? intval( $this->request['f'] ) : 0;
		$parentid    = intval( $this->request['p'] ) ? intval( $this->request['p'] ) : -1;
		$cat_id      = $this->request['c']           ? intval( $this->request['c'] ) : 0;
		$f_name      = $this->request['name']        ? $this->request['name']        : '';
		$subcanpost  = $cat_id == 1                  ? 0                             : 1;
		$perm_matrix = "";
		$dd_state    = array( 0 => array( 1, $this->lang->words['for_active'] ), 1 => array( 0, $this->lang->words['for_readonly'] ) );
		$dd_moderate = array(
							 0 => array( 0, $this->lang->words['for_no'] ),
							 1 => array( 1, $this->lang->words['for_modall'] ),
							 2 => array( 2, $this->lang->words['for_modtop'] ),
							 3 => array( 3, $this->lang->words['for_modrep'] ),
							);
		$dd_prune    = array( 
							 0 => array( 1, $this->lang->words['for_today'] ),
							 1 => array( 5, $this->lang->words['for_last5']  ),
							 2 => array( 7, $this->lang->words['for_last7']  ),
							 3 => array( 10, $this->lang->words['for_last10'] ),
							 4 => array( 15, $this->lang->words['for_last15'] ),
							 5 => array( 20, $this->lang->words['for_last20'] ),
							 6 => array( 25, $this->lang->words['for_last25'] ),
							 7 => array( 30, $this->lang->words['for_last30'] ),
							 8 => array( 60, $this->lang->words['for_last60'] ),
							 9 => array( 90, $this->lang->words['for_last90'] ),
							 10=> array( 100, $this->lang->words['for_showall']     ),
							);
		
		$dd_order    = array( 
							 0 => array( 'last_post', $this->lang->words['for_s_last'] ),
							 1 => array( 'title'    , $this->lang->words['for_s_topic'] ),
							 2 => array( 'starter_name', $this->lang->words['for_s_name'] ),
							 3 => array( 'posts'    , $this->lang->words['for_s_post'] ),
							 4 => array( 'views'    , $this->lang->words['for_s_view'] ),
							 5 => array( 'start_date', $this->lang->words['for_s_date'] ),
							 6 => array( 'last_poster_name'   , $this->lang->words['for_s_poster'] )
							);

		$dd_by       = array( 
							 0 => array( 'Z-A', $this->lang->words['for_desc'] ),
							 1 => array( 'A-Z', $this->lang->words['for_asc']  )
							);
							
		$dd_filter	 = array(
							 0 => array( 'all', 	$this->lang->words['for_all'] ),
							 1 => array( 'open', 	$this->lang->words['for_open'] ),
							 2 => array( 'hot',		$this->lang->words['for_hot'] ),
							 3 => array( 'poll',	$this->lang->words['for_poll'] ),
							 4 => array( 'locked',	$this->lang->words['for_locked'] ),
							 5 => array( 'moved',	$this->lang->words['for_moved'] ),
							 6 => array( 'istarted', $this->lang->words['for_istarted'] ),
							 7 => array( 'ireplied', $this->lang->words['for_ireplied'] ),
							);

		//-----------------------------------------
		// EDIT
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ( ! $forum_id )
			{
				$this->registry->output->showError( $this->lang->words['for_noforumselected'], 11314 );
			}
			
			//-----------------------------------------
			// Do not show forum in forum list
			//-----------------------------------------
			
			$this->forum_functions->exclude_from_list = $forum_id;
			
			//-----------------------------------------
			// Get this forum
			//-----------------------------------------
			
			$forum = $this->DB->buildAndFetch( array( 
															'select'   => 'f.*',
															'from'     => array( 'forums' => 'f' ),
															'where'    => 'f.id='.$this->request['f'],
															'add_join' => array(																	
																				array(
																						'select' => 'p.*',
																						'from'   => array( 'permission_index' => 'p' ),
																						'where'  => "p.perm_type_id=f.id AND p.app='forums' AND p.perm_type='forum'",
																						'type'   => 'left',
																					)
																			),
													)	);
			
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ($forum['id'] == "")
			{
				$this->registry->output->showError( $this->lang->words['for_noid'], 11315 );
			}
			
			//-----------------------------------------
			// Set up code buttons
			//-----------------------------------------
			
			$addnew_type	= $forum['parent_id'] == -1 ? 'category' : 'forum';
			
			if( $changetype )
			{
				$addnew_type = $addnew_type == 'category' ? 'forum' : 'category';
			}
			if( $addnew_type == 'category' )
			{
				$title  		= sprintf( $this->lang->words['for_editcat'], $forum['name'] );
				$button 		= $this->lang->words['for_editcat_button'];
				$code   		= "doedit";
			}
			else
			{
				$title  		= sprintf( $this->lang->words['for_editfor'], $forum['name'] );
				$button 		= $this->lang->words['for_editfor_button'];
				$code   		= "doedit";
			}
			if( $addnew_type == 'category' )
			{
				$convert	= "<input type='submit' class='realbutton' onclick='ACPForums.convert()' value='{$this->lang->words['for_changefor']}' />";
			}
			else
			{
				$convert	= "<input type='submit' class='realbutton' onclick='ACPForums.convert()' value='{$this->lang->words['for_changecat']}' />";
			}
		}
		
		//-----------------------------------------
		// NEW
		//-----------------------------------------
		
		else
		{
			# Ensure there is an ID
			$this->request['f'] = 0;
			
			if( $changetype )
			{
				$addnew_type = $addnew_type == 'category' ? 'forum' : 'category';
			}
			if( $addnew_type == 'category' )
			{
				$forum = array(
								'sub_can_post'				=> $subcanpost,
								'name'						=> $f_name ? $f_name : $this->lang->words['for_newcat'],
								'parent_id'					=> $parentid,
								'use_ibc'					=> 1,
								'quick_reply'				=> 1,
								'allow_poll'				=> 1,
								'prune'						=> 100,
								'topicfilter'				=> 'all',
								'sort_key'					=> 'last_post',
								'sort_order'				=> 'Z-A',
								'inc_postcount'				=> 1,
								'description'				=> '',
								'status'					=> 0,
								'redirect_url'				=> '',
								'password'					=> '',
								'password_override'			=> '',
								'redirect_on'				=> 0,
								'redirect_hits'				=> 0,
								'permission_showtopic'		=> '',
								'permission_custom_error'	=> '',
								'use_html'					=> 0,
								'allow_pollbump'			=> 0,
								'forum_allow_rating'		=> 0,
								'preview_posts'				=> 0,
								'notify_modq_emails'		=> 0,
								'can_view_others'			=> 1,
								
							  );
							  
				$title       = $this->lang->words['for_addcat'];
				$button      = $this->lang->words['for_addcat'];
				$code        = "donew";
			}
			else
			{
				$forum = array(
								'sub_can_post'				=> $subcanpost,
								'name'						=> $f_name ? $f_name : $this->lang->words['for_newfor'],
								'parent_id'					=> $parentid,
								'use_ibc'					=> 1,
								'quick_reply'				=> 1,
								'allow_poll'				=> 1,
								'prune'						=> 100,
								'topicfilter'				=> 'all',
								'sort_key'					=> 'last_post',
								'sort_order'				=> 'Z-A',
								'inc_postcount'				=> 1,
								'description'				=> '',
								'status'					=> 1,
								'redirect_url'				=> '',
								'password'					=> '',
								'password_override'			=> '',
								'redirect_on'				=> 0,
								'redirect_hits'				=> 0,
								'permission_showtopic'		=> '',
								'permission_custom_error'	=> '',
								'use_html'					=> 0,
								'allow_pollbump'			=> 0,
								'forum_allow_rating'		=> 0,
								'preview_posts'				=> 0,
								'notify_modq_emails'		=> 0,
								'min_posts'					=> 0,
								'hide_last_info'			=> 0,
								'can_view_others'			=> 1,
							  );
							  
				$title       = $this->lang->words['for_addfor'];
				$button      = $this->lang->words['for_addfor'];
				$code        = "donew";
			}
			if( $addnew_type == 'category' )
			{
				$convert	= "<input type='submit' class='realbutton' onclick='ACPForums.convert()' value='{$this->lang->words['for_changefor']}' />";
			}
			else
			{
				$convert	= "<input type='submit' class='realbutton' onclick='ACPForums.convert()' value='{$this->lang->words['for_changecat']}' />";
			}
		}

		//-----------------------------------------
		// Build forumlist
		//-----------------------------------------
		
		$forumlist = $this->forum_functions->adForumsForumList();
		
		//-----------------------------------------
		// Build group list
		//-----------------------------------------		
		
		$mem_group = array();
		
		foreach( $this->caches['group_cache'] as $g_id => $group )
		{
			$mem_group[] = array( $g_id , $group['g_title'] );
		}		
		
		//-----------------------------------------
		// Page title...
		//-----------------------------------------
		
		//$this->registry->output->html_help_title = $title;
		
		//-----------------------------------------
		// Generate form items
		//-----------------------------------------
		
		# Main settings
		$form['name']         = $this->registry->output->formInput(   'name'        , ( isset( $_POST['name'] ) AND $_POST['name'] ) ? IPSText::parseCleanValue( $_POST['name'] ) : $forum['name'] );
		$form['description']  = $this->registry->output->formTextarea("description" , IPSText::br2nl( ( isset( $_POST['description']) AND $_POST['description'] ) ? $_POST['description'] : $forum['description'] ) );
		$form['parent_id']    = $this->registry->output->formDropdown("parent_id"   , $forumlist, ( isset($_POST['parent_id'] ) AND $_POST['parent_id'] ) 	? $_POST['parent_id']    : $forum['parent_id'] );
		$form['status']       = $this->registry->output->formDropdown("status"      , $dd_state , ( isset($_POST['status'] ) AND $_POST['status'] )    	? $_POST['status']       : $forum['status'] );
		$form['sub_can_post'] = $this->registry->output->formYesNo(  'sub_can_post', ( isset($_POST['sub_can_post']) AND $_POST['sub_can_post'] )         ? $_POST['sub_can_post'] : ( $forum['sub_can_post'] == 1 ? 0 : 1 ) );
		
		# Redirect options
		$form['redirect_url']  = $this->registry->output->formInput( 'redirect_url' , ( isset($_POST['redirect_url']) 	AND $_POST['redirect_url'] )  ? $_POST['redirect_url']  : $forum['redirect_url']  );
		$form['redirect_on']   = $this->registry->output->formYesNo('redirect_on'  , ( isset($_POST['redirect_on']) 	AND $_POST['redirect_on'] )   ? $_POST['redirect_on']   : $forum['redirect_on']   );
		$form['redirect_hits'] = $this->registry->output->formInput( 'redirect_hits', ( isset($_POST['redirect_hits']) AND $_POST['redirect_hits'] ) ? $_POST['redirect_hits'] : $forum['redirect_hits'] );
		
		# Permission settings
		$form['permission_showtopic']    = $this->registry->output->formYesNo(  'permission_showtopic'   , ( isset($_POST['permission_showtopic']) AND $_POST['permission_showtopic'] ) ? $_POST['permission_showtopic'] : $forum['permission_showtopic'] );
		$form['permission_custom_error'] = $this->registry->output->formTextarea("permission_custom_error", IPSText::br2nl( ( isset($_POST['permission_custom_error']) AND $_POST['permission_custom_error'] ) ? $_POST['permission_custom_error'] : $forum['permission_custom_error'] ) );
		
		# Forum settings
		$form['use_html']           = $this->registry->output->formYesNo('use_html'          , ( isset($_POST['use_html']) 			AND $_POST['use_html'] )           	? $_POST['use_html']            : $forum['use_html'] );
		$form['use_ibc']            = $this->registry->output->formYesNo('use_ibc'           , ( isset($_POST['use_ibc']) 			AND $_POST['use_ibc'] )            	? $_POST['use_ibc']             : $forum['use_ibc']  );
		$form['quick_reply']        = $this->registry->output->formYesNo('quick_reply'       , ( isset($_POST['quick_reply']) 		AND $_POST['quick_reply'] )         ? $_POST['quick_reply']         : $forum['quick_reply']  );
		$form['allow_poll']         = $this->registry->output->formYesNo('allow_poll'        , ( isset($_POST['allow_poll']) 			AND $_POST['allow_poll'] )          ? $_POST['allow_poll']          : $forum['allow_poll']  );
		$form['allow_pollbump']     = $this->registry->output->formYesNo('allow_pollbump'    , ( isset($_POST['allow_pollbump']) 		AND $_POST['allow_pollbump'] )      ? $_POST['allow_pollbump']      : $forum['allow_pollbump']  );
		$form['inc_postcount']      = $this->registry->output->formYesNo('inc_postcount'     , ( isset($_POST['inc_postcount']) 		AND $_POST['inc_postcount'] )       ? $_POST['inc_postcount']       : $forum['inc_postcount']  );
		$form['forum_allow_rating'] = $this->registry->output->formYesNo('forum_allow_rating', ( isset($_POST['forum_allow_rating']) 	AND $_POST['forum_allow_rating'] )  ? $_POST['forum_allow_rating']  : $forum['forum_allow_rating']  );
		$form['min_posts_post']		= $this->registry->output->formInput('min_posts_post'     , ( isset($_POST['min_posts_post'])      AND $_POST['min_posts_post'] )      ? $_POST['min_posts_post']      : $forum['min_posts_post']  );
		$form['min_posts_view']		= $this->registry->output->formInput('min_posts_view'     , ( isset($_POST['min_posts_view'])      AND $_POST['min_posts_view'] )      ? $_POST['min_posts_view']      : $forum['min_posts_view']  );
		$form['can_view_others']	= $this->registry->output->formYesNo('can_view_others'   , ( isset($_POST['can_view_others'])     AND $_POST['can_view_others'] )     ? $_POST['can_view_others']     : $forum['can_view_others']  );
		$form['hide_last_info']		= $this->registry->output->formYesNo('hide_last_info'   , ( isset($_POST['hide_last_info'])     AND $_POST['hide_last_info'] )     ? $_POST['hide_last_info']     : $forum['hide_last_info']  );

		# Mod settings
		$form['preview_posts']      = $this->registry->output->formDropdown(		"preview_posts"    		, $dd_moderate, ( isset($_POST['preview_posts']) AND $_POST['preview_posts'] ) ? $_POST['preview_posts'] 	: $forum['preview_posts'] );
		$form['notify_modq_emails'] = $this->registry->output->formInput(  		'notify_modq_emails'	, ( isset($_POST['notify_modq_emails']) AND $_POST['notify_modq_emails'] ) ? $_POST['notify_modq_emails'] 	: $forum['notify_modq_emails'] );
		$form['password']           = $this->registry->output->formInput(  		'password'          	, ( isset($_POST['password']) 			AND $_POST['password'] )           ? $_POST['password']           	: $forum['password'] );
		$form['password_override']  = $this->registry->output->formMultiDropdown(  	'password_override[]'	, $mem_group, ( isset($_POST['password_override']) AND $_POST['password_override'] ) ? $_POST['password_override'] : explode( ",", $forum['password_override'] ) );
		
		# Sorting settings
		$form['prune']      		= $this->registry->output->formDropdown("prune"     , $dd_prune, ( isset($_POST['prune']) 			AND $_POST['prune'] )		? $_POST['prune']		: $forum['prune'] );
		$form['sort_key']   		= $this->registry->output->formDropdown("sort_key"  , $dd_order, ( isset($_POST['sort_key']) 		AND $_POST['sort_key'] )	? $_POST['sort_key']	: $forum['sort_key'] );
		$form['sort_order'] 		= $this->registry->output->formDropdown("sort_order", $dd_by   , ( isset($_POST['sort_order']) 	AND $_POST['sort_order'] )	? $_POST['sort_order'] 	: $forum['sort_order'] );
		$form['topicfilter'] 		= $this->registry->output->formDropdown("topicfilter", $dd_filter, ( isset($_POST['topicfilter']) 	AND $_POST['topicfilter'] ) ? $_POST['topicfilter'] : $forum['topicfilter'] );
		
		# Trim the form for categories...
		$form['addnew_type']			= $addnew_type;
		$this->request['type']          = $addnew_type;
		$form['addnew_type_upper']		= ucwords($addnew_type);
		
		$form['convert_button'] 		=& $convert;
		
		//-----------------------------------------
		// Show permission matrix
		//-----------------------------------------
		
		if ( $type != 'edit' OR $addnew_type == 'category' )
		{
			/* Permission Class */
		   	require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
		   	$permissions = new classPublicPermissions( ipsRegistry::instance() );
		   					
			if( $addnew_type == 'category' )
			{
				$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum, 'forums', 'view' );
			}
			else
			{
		   		$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum );
			}
		}
		
		//-----------------------------------------
		// Show form...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->forumForm( $form, $button, $code, $title, $forum, $perm_matrix );
		
		//-----------------------------------------
		// Nav and print
		//-----------------------------------------
		
		//ipsRegistry::getClass('adminOuput')->nav[] = array( $this->form_code, 'Manage Forums' );
		//ipsRegistry::getClass('adminOuput')->nav[] = array( '', 'Add/Edit '.ucwords($addnew_type) );
	}
	
	/**
	 * Save the forum
	 *
	 * @access	public
	 * @param	string		[new|edit]
	 * @return	void		Outputs to screen
	 **/	
	public function forumSave($type='new')
	{
		//-----------------------------------------
		// Converting the type?
		//-----------------------------------------

		if( $this->request['convert'] )
		{
			$this->forumForm( $type, 1 );
			return;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['name'] = trim( $this->request['name'] );
		$this->request['f'] = intval( $this->request['f'] );
		
		$forum_cat_lang = intval( $this->request['parent_id'] ) == -1 ? $this->lang->words['for_iscat_y'] : $this->lang->words['for_iscat_n'];
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->registry->adminFunctions->checkSecurityKey();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if( $this->request['name'] == "" )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['for_entertitle'], strtolower( $forum_cat_lang ) );
			$this->forumForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Are we trying to do something stupid
		// like running with scissors or moving
		// the parent of a forum into itself
		// spot?
		//-----------------------------------------
		
		if( $this->request['parent_id'] != $this->forum_functions->forum_by_id[ $this->request['f'] ]['parent_id'] )
		{
			$ids   = $this->forum_functions->forumsGetChildren( $this->request['f'] );
			$ids[] = $this->request['f'];
			
			if ( in_array( $this->request['parent_id'], $ids ) )
			{
				$this->registry->output->global_error = $this->lang->words['for_whymovethere'];
				$this->forumForm( $type );
				return;
			}
		}
		
		if( $this->request['parent_id'] < 1 )
		{
			$this->request['sub_can_post'] = 1;
		}
				
		//-----------------------------------------
		// Save array
		//-----------------------------------------

		$save = array (  'name'                    => IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['name'] ) ) ),
						 'name_seo'				   => IPSText::makeSeoTitle( $this->request['name'] ),
						 'description'             => IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['description'] ) ) ),
						 'use_ibc'                 => intval($this->request['use_ibc']),
						 'use_html'                => intval($this->request['use_html']),
						 'status'                  => intval($this->request['status']),
						 'password'                => $this->request['password'],
						 'password_override'	   => is_array($this->request['password_override']) ? implode( ",", $this->request['password_override'] ) : '',
						 'sort_key'                => $this->request['sort_key'],
						 'sort_order'              => $this->request['sort_order'],
						 'prune'                   => intval($this->request['prune']),
						 'topicfilter'             => $this->request['topicfilter'],
						 'preview_posts'           => intval($this->request['preview_posts']),
						 'allow_poll'              => intval($this->request['allow_poll']),
						 'allow_pollbump'          => intval($this->request['allow_pollbump']),
						 'forum_allow_rating'      => intval($this->request['forum_allow_rating']),
						 'inc_postcount'           => intval($this->request['inc_postcount']),
						 'parent_id'               => intval($this->request['parent_id']),
						 'sub_can_post'            => ( intval($this->request['sub_can_post']) == 1 ? 0 : 1 ),
						 'quick_reply'             => intval($this->request['quick_reply']),
						 'redirect_on'             => intval($this->request['redirect_on']),
						 'redirect_hits'           => intval($this->request['redirect_hits']),
						 'redirect_url'            => $this->request['redirect_url'],
						 'redirect_loc'		       => $this->request['redirect_loc'] ? $this->request['redirect_loc'] : '',
						 'notify_modq_emails'      => $this->request['notify_modq_emails'],
						 'permission_showtopic'    => intval($this->request['permission_showtopic']),
						 'min_posts_post'          => intval( $this->request['min_posts_post'] ),
						 'min_posts_view'          => intval( $this->request['min_posts_view'] ),
						 'can_view_others'		   => intval( $this->request['can_view_others'] ),
						 'hide_last_info'		   => intval( $this->request['hide_last_info'] ),
						 'permission_custom_error' => nl2br( IPSText::stripslashes($_POST['permission_custom_error']) ) );
						 
		//-----------------------------------------
		// ADD
		//-----------------------------------------
		
		if ( $type == 'new' )
		{
			 $this->DB->build( array( 'select' => 'MAX(id) as top_forum', 'from' => 'forums' ) );
			 $this->DB->execute();
			 
			 $row = $this->DB->fetch();
			 
			 if ( $row['top_forum'] < 1 )
			 {
			 	$row['top_forum'] = 0;
			 }
			 
			 $row['top_forum']++;

			/* Forum Information */
			//$save['id']               = $row['top_forum'];
			$save['position']         = $row['top_forum'];
			$save['topics']           = 0;
			$save['posts']            = 0;
			$save['last_post']        = 0;
			$save['last_poster_id']   = 0;
			$save['last_poster_name'] = "";
			
			/* Insert the record */
			$this->DB->insert( 'forums', $save );
			$forum_id = $this->DB->getInsertId();
			
			 /* Permissions */
			require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
			$permissions = new classPublicPermissions( ipsRegistry::instance() );
			$permissions->savePermMatrix( $this->request['perms'], $forum_id, 'forum' );
			
			if( !$save['can_view_others'] )
			{
				$this->DB->update( 'permission_index', array( 'owner_only' => 1 ), "app='forums' AND perm_type='forum' AND perm_type_id={$forum_id}" );
			}
			
			/* Done */
			$this->registry->output->global_message = $forum_cat_lang . $this->lang->words['for__created'];			
			$this->registry->adminFunctions->saveAdminLog( $forum_cat_lang . " '" . $this->request['name'] . strtolower ( $this->lang->words['for__created'] ) );
		}
		else
		{
			 if ( $this->request['parent_id'] == -1 )
			 {
				$save['can_view_others'] = 1;
				
				/* Permissions */
				require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
				$permissions = new classPublicPermissions( ipsRegistry::instance() );
				$permissions->savePermMatrix( $this->request['perms'], $this->request['f'], 'forum' );

				if( ! $save['can_view_others'] )
				{
					$this->DB->update( 'permission_index', array( 'owner_only' => 1 ), "app='forums' AND perm_type='forum' AND perm_type_id={$this->request['f']}" );
				}
				else
				{
					$this->DB->update( 'permission_index', array( 'owner_only' => 0 ), "app='forums' AND perm_type='forum' AND perm_type_id={$this->request['f']}" );
				}
			}

			$this->DB->update( 'forums', $save, "id=" . $this->request['f'] );
						
			$this->registry->output->global_message = $forum_cat_lang.$this->lang->words['for__edited'];
			
			$this->registry->adminFunctions->saveAdminLog( $forum_cat_lang." '" . $this->request['name'] . strtolower ( $this->lang->words['for__edited'] ) );
		}
		
		$this->recacheForums();
		
		$this->request['f'] = '';
		if( $save['parent_id'] > 0 )
		{
			$this->request['f'] = $save['parent_id'];
		}
		
		$this->forum_functions->forumsInit();
		
		$this->showForums();
	}
	
	/**
	 * Show the form to edit permissions
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function permEditForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['f'] = intval( $this->request['f'] );
		
		//-----------------------------------------
		// check..
		//-----------------------------------------
		
		if ( ! $this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 11316 );
		}
		
		//-----------------------------------------
		// Get this forum details
		//-----------------------------------------
		
		$forum = $this->forum_functions->forum_by_id[ $this->request['f'] ];

		//-----------------------------------------
		// Next id...
		//-----------------------------------------
		
		$relative = $this->getNextId( $this->request['f'] );
		
		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 11317 );
		}
		
		//-----------------------------------------
		// HTML
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
		$permissions = new classPublicPermissions( ipsRegistry::instance() );

		if( $forum['parent_id'] != 'root' )
		{
			$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum );
		}
		else
		{
			$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum, 'forums', 'view' );			
		}
		
		$this->registry->output->html .= $this->html->forumPermissionForm( $forum, $relative, $perm_matrix, $forum );
	}
	
	/**
	 * Get the id of the next forum
	 *
	 * @access	public
	 * @param	integer		Last forum id
	 * @return	void		Outputs to screen
	 **/	
	public function getNextId($fid)
	{
		$nextid = 0;
		$ids    = array();
		$index  = 0;
		$count  = 0;
		
		foreach( $this->forum_functions->forum_cache['root'] as $forum_data )
		{
			$ids[ $count ] = $forum_data['id'];
			
			if ( $forum_data['id'] == $fid )
			{
				$index = $count;
			}
			
			$count++;
			
			if ( isset($this->forum_functions->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_functions->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->forum_functions->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$children = $this->forum_functions->forumsGetChildren( $forum_data['id'] );
					
					$ids[ $count ] = $forum_data['id'];
			
					if ( $forum_data['id'] == $fid )
					{
						$index = $count;
					}
					
					$count++;
					
					if ( is_array($children) and count($children) )
					{
						foreach( $children as $kid )
						{
							$ids[ $count ] = $kid;
			
							if ( $kid == $fid )
							{
								$index = $count;
							}
							
							$count++;
						}
					}
				}
			}
		}
	
		return array( 'next' => $ids[ $index + 1 ], 'previous' => $ids[ $index - 1 ] );
	}
	
	/**
	 * Recache the forums
	 *
	 * @access	public
	 * @return	void
	 * @deprecated	We're moving away from forum cache
	 **/	
	public function recacheForums()
	{
		//$this->registry->class_forums->updateForumCache();		
	}

	/**
	 * Save the permissions
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function permDoEdit()
	{
		/* INI */
		$perms = array();
		$this->request['f'] = intval( $this->request['f'] );		
		
		/* Security Check */
		$this->registry->adminFunctions->checkSecurityKey();
		
		/* Save the permissions */
		require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
   		$permissions = new classPublicPermissions( ipsRegistry::instance() );
		$permissions->savePermMatrix( $this->request['perms'], $this->request['f'], 'forum' );
		
		/* Log */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_permeditedin'], $this->request['name'] ) );
		
		$this->recacheForums();
		
		/* Previous Forum */
		if ( $this->request['doprevious'] AND $this->request['doprevious'] and $this->request['previd'] > 0 )
		{
			$this->registry->output->global_message = $this->lang->words['for_permedited'];
			
			$this->request['f'] = $this->request['previd'];
			
			$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}do=pedit&f=" . $this->request['f'] );
		}
		/* Next Forum */
		else if ( $this->request['donext'] AND $this->request['donext'] and $this->request['nextid'] > 0 )
		{
			$this->registry->output->global_message = $this->lang->words['for_permedited'];
			
			$this->request['f'] = $this->request['nextid'];
			
			$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}do=pedit&f=" . $this->request['f'] );
		}
		/* Reload */
		else if ( $this->request['reload'] AND $this->request['reload'] )
		{
			$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}do=pedit&f=" . $this->request['f'] );
		}
		/* Done */
		else
		{
			$this->registry->output->doneScreen( $this->lang->words['for_permedited2'], $this->lang->words['for_control'], $this->html->form_code, 'redirect' );
		}
	}
	
	/**
	 * Reorder the child forums
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function doReorder()
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
 		
 		if( is_array($this->request['forums']) AND count($this->request['forums']) )
 		{
 			foreach( $this->request['forums'] as $this_id )
 			{
 				$this->DB->update( 'forums', array( 'position' => $position ), 'id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->recacheForums();

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * List the forums
	 *
	 * @access	public
	 * @return	void		Outputs to screen
	 **/	
	public function showForums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['f'] = intval( $this->request['f'] );
		
		//-----------------------------------------
		// Nav
		//-----------------------------------------
		
		if( $this->request['f'] )
		{
			$nav = $this->forum_functions->forumsBreadcrumbNav( $this->request['f'], '&'.$this->form_code.'&f=' );

			if( is_array( $nav ) and count( $nav ) > 1 )
			{
				//array_shift( $nav );
			}
		}
		
		//-----------------------------------------
		// Grab the moderators
		//-----------------------------------------
		
		$this->forum_functions->moderators = array();
		
		$this->DB->build( array( 
										'select'  => 'm.*', 
										'from' 	  => array( 'moderators' => 'm' ),
										'add_join'=> array(
															array( 
																	'select' => 'mm.members_display_name',
																	'from'	 => array( 'members' => 'mm' ),
																	'where'	 => 'mm.member_id=m.member_id AND m.is_group=0',
																	'type'	 => 'left'
																)
															)
										) 		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->forum_functions->moderators[] = $r;
		}
		
		//-----------------------------------------
		// Print screen
		//-----------------------------------------
		
		$this->forum_functions->type = 'manage';

		$this->registry->output->html .= $this->html->renderForumHeader( $nav );
		
		$this->forum_functions->forumsListForums();
		
		$choose = "<select name='roots' class='realbutton'>";
		
		if( is_array($this->forum_functions->forum_cache['root']) AND count($this->forum_functions->forum_cache['root']) )
		{
			foreach( $this->forum_functions->forum_cache['root'] as $fid => $fdata )
			{
				$choose .= "<option value='{$fid}'>{$fdata['name']}</option>\n";
			}
		}
		
		$choose .= "</select>";
		
		//-----------------------------------------
		// Member groups
		//-----------------------------------------
		
		$mem_group = "<select name='group' class='realbutton'>";
			
		$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => "g_title" ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
		 	$mem_group .= "<option value='{$r['g_id']}'>{$r['g_title']}</option>\n";
		}
		
		$mem_group .= "</select>";
		
		//-----------------------------------------
		// Add footer
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->renderForumFooter( $choose, $mem_group );
	}	
}