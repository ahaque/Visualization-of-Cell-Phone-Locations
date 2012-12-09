<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Topic Multi-Moderation
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 3887 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_forums_multimods extends ipsCommand
{
	/**
	 * Forum functions library
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
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_multimods' );
		$this->html->form_code    = 'module=forums&amp;section=multimods&amp;';
		$this->html->form_code_js = 'module=forums&amp;section=multimods&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_forums' ) );
		
		/* Navigation */
		$this->registry->output->nav[]			= array( $this->form_code, $this->lang->words['mm_nav'] );
		$this->registry->output->extra_title[]	= $this->lang->words['mm_nav'];
		
		switch( $this->request['do'] )
		{
			case 'new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mmod_add' );
				$this->multiModerationForm( 'new' );
			break;
				
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mmod_edit' );
				$this->multiModerationForm( 'edit' );
			break;
				
			case 'donew':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mmod_add' );
				$this->multiModerationSaveForm( 'new' );
			break;
				
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mmod_edit' );
				$this->multiModerationSaveForm( 'edit' );
			break;
				
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mmod_delete' );
				$this->multiModerationDelete();
			break;
				
			case 'overview':						
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mmod_view' );
				$this->multiModerationOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Removes a multi moderation
	 *
	 * @access	public
	 * @return 	void
	 **/
	public function multiModerationDelete()
	{
		/* Check ID */
		if ($this->request['id'] == "")
		{
			$this->registry->output->showError( $this->lang->words['mm_noid'], 11332 );
		}
		
		/* Delete the record */
		$this->DB->delete( 'topic_mmod', "mm_id=" . intval( $this->request['id'] ) );
		
		/* Rebuild Cache */
		$this->multiModerationRebuildCache();
		
		/* Log and bounce */
		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['mm_removed'] );		
		$this->registry->output->silentRedirect($this->settings['base_url'] . $this->html->form_code );
		
	}	
	
	/**
	 * Saves the add/edit multi moderation form
	 *
	 * @access	public
	 * @param	string  $type  Either 'new' or 'edit'	 
	 * @return	void
	 **/
	public function multiModerationSaveForm( $type='new' )
	{
		/* INI */
		$forums = array();

		/* Make sure we have a title */
		if( ! $this->request['mm_title'] )
		{
			$this->registry->output->showError( $this->lang->words['mm_valtitle'], 11333 );
		}
		
		/* Check for forums */
		$forums = $this->_getSelectedForums();
		
		/* Check forums */
		if( ! $forums )
		{
			$this->registry->output->showError( $this->lang->words['mm_forums'], 11334 );
		}
		
		/* Check move location */
		if( $this->request['topic_move'] == 'n' )
		{
			$this->registry->output->showError( $this->lang->words['mm_wrong'], 11335 );
		}
		
		/* Build the insert array */
		$save = array(
						'mm_title'              => $this->request['mm_title'],
						'mm_enabled'            => 1,
						'topic_state'           => $this->request['topic_state'],
						'topic_pin'	            => $this->request['topic_pin'],
						'topic_move'            => intval( $this->request['topic_move'] ),
						'topic_move_link'       => intval( $this->request['topic_move_link'] ),
						'topic_title_st'        => IPSText::makeSlashesSafe( $_POST['topic_title_st'] ),
						'topic_title_end'       => IPSText::makeSlashesSafe( $_POST['topic_title_end'] ),
						'topic_reply'           => intval( $this->request['topic_reply'] ),
						'topic_reply_content'   => IPSText::makeSlashesSafe( $_POST['topic_reply_content'] ),
						'topic_reply_postcount' => intval( $this->request['topic_reply_postcount'] ),
						'mm_forums'             => $forums,
						'topic_approve'         => intval( $this->request['topic_approve'] ),
					 );
		 
		/* Edit */
		if ( $type == 'edit' )
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['mm_valid'] );
			}
			
			/* Update the multi mod */			
			$this->DB->update( 'topic_mmod', $save, 'mm_id='.$id );
		}
		/* New */
		else
		{
			/* Insert the new multi mod */
			$this->DB->insert( 'topic_mmod', $save );
		}
		
		/* Log, Cache, and Bounce */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['mm_update'], $type ) );
		$this->multiModerationRebuildCache();		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->html->form_code );
	}
	
	/**
	 * Builds the add/edit multi moderation form
	 *
	 * @access	public
	 * @param	string  $type  Either 'new' or 'edit'
	 * @return	void
	 **/
	public function multiModerationForm( $type='new' )
	{
		if( $type == 'new' )
		{
			/* Setup */
			$form_code   = 'donew';
			$id			 = 0;
			$description = $this->lang->words['mm_addnew'];
			$button      = $this->lang->words['mm_addnew'];
			
			/* Default Values */
			$topic_mm	 = array( 
									'mm_forums'             => '', 
									'mm_title'              => '', 
									'topic_title_st'        => '',
									'topic_title_end'       => '', 
									'topic_state'           => '', 
									'topic_pin'             => '',
									'topic_approve'         => '', 
									'topic_move'            => '', 
									'topic_move_link'       => '',
									'topic_reply'           => '', 
									'topic_reply_content'   => '', 
									'topic_reply_postcount' => '' 
								);
		}
		else
		{
			/* Setup */
			$id = intval( $this->request['id'] );
			$form_code   = 'doedit';
			$description = $this->lang->words['mm_edit'];
			$button      = $this->lang->words['mm_edit'];
			
			/* Default Values */			
			$this->DB->build( array( 'select' => '*', 'from' => 'topic_mmod', 'where' => "mm_id=$id" ) );
			$this->DB->execute();
		
			if ( ! $topic_mm = $this->DB->fetch() )
			{
				$this->registry->output->showError( sprintf( $this->lang->words['mm_noinfo'], $id ), 11337 );
			}
		}
		
		/* State Drop Options */
		$state_dd = array(
						  0 => array( 'leave', $this->lang->words['mm_leave'] ),
						  1 => array( 'close', $this->lang->words['mm_close'] ),
						  2 => array( 'open' , $this->lang->words['mm_open']  ),
					   );
		
		/* Pinned Drop Down Options */
		$pin_dd   = array(
						  0 => array( 'leave', $this->lang->words['mm_leave'] ),
						  1 => array( 'pin'  , $this->lang->words['mm_pin']   ),
						  2 => array( 'unpin', $this->lang->words['mm_unpin'] ),
					    );
		
		/* Approved Drop Down Options */
		$app_dd   = array(
						  0 => array( '0', $this->lang->words['mm_leave']     ),
						  1 => array( '1', $this->lang->words['mm_approve']   ),
						  2 => array( '2', $this->lang->words['mm_unapprove'] ),
					    );
		
		/* Build forum multiselect */
		$topic_mm['forums'] = "<select name='forums[]' class='textinput' size='15' multiple='multiple'>\n";
		
		$topic_mm['forums'] .= $topic_mm['mm_forums'] == '*' ? "<option value='all' selected='selected'>{$this->lang->words['mm_allforums']}</option>\n" : "<option value='all'>{$this->lang->words['mm_allforums']}</option>\n";		    
		
		$forum_jump = $this->forumfunc->adForumsForumData();
			
		foreach( $forum_jump as $i )
		{
			if( strstr( "," . $topic_mm['mm_forums'] . ",", "," . $i['id'] . "," ) and $topic_mm['mm_forums'] != '*' )
			{
				$selected = ' selected="selected"';
			}
			else
			{
				$selected = "";
			}
			
			if( isset( $i['redirect_on'] ) AND $i['redirect_on'] == 1 )
			{
				continue;
			}
			
			$fporum_jump[] = array( $i['id'], $i['depthed_name'] );
			
			$topic_mm['forums']  .= "<option value=\"{$i['id']}\" $selected>{$i['depthed_name']}</option>\n";
		}
		
		$topic_mm['forums'] .= "</select>";
		
		/* Build Form Fields */		
		$topic_mm['mm_title']              = $this->registry->output->formInput("mm_title", $topic_mm['mm_title'] );
		$topic_mm['topic_title_st']        = $this->registry->output->formInput("topic_title_st", $topic_mm['topic_title_st'] );
		$topic_mm['topic_title_end']       = $this->registry->output->formInput("topic_title_end", $topic_mm['topic_title_end'] );
		$topic_mm['topic_state']           = $this->registry->output->formDropdown("topic_state", $state_dd, $topic_mm['topic_state'] );
		$topic_mm['topic_pin']             = $this->registry->output->formDropdown("topic_pin", $pin_dd, $topic_mm['topic_pin'] );
		$topic_mm['topic_approve']         = $this->registry->output->formDropdown("topic_approve", $app_dd, $topic_mm['topic_approve'] );
		$topic_mm['topic_move']            = $this->registry->output->formDropdown("topic_move", array_merge( array( 0 => array('-1', $this->lang->words['mm_nobodymovenobodygethurt'] ) ), $fporum_jump ), $topic_mm['topic_move'] );
		$topic_mm['topic_move_link']       = $this->registry->output->formCheckbox('topic_move_link', $topic_mm['topic_move_link'] );
		$topic_mm['topic_reply']           = $this->registry->output->formYesNo('topic_reply', $topic_mm['topic_reply'] );
		$topic_mm['topic_reply_content']   = $this->registry->output->formTextarea("topic_reply_content", $topic_mm['topic_reply_content'] );
		$topic_mm['topic_reply_postcount'] = $this->registry->output->formCheckbox('topic_reply_postcount', $topic_mm['topic_reply_postcount'] );

		/* Output */
		$this->registry->output->html .= $this->html->multiModerationForm( $id, $form_code, $description, $topic_mm, $button );
	}	
	
	/**
	 * Show all available multi mods
	 *
	 * @access	public
	 * @return	void
	 **/
	public function multiModerationOverview()
	{
		/* Query the multi mods */		
		$this->DB->build( array( 'select' => '*', 'from' => 'topic_mmod', 'order' => "mm_title" ) );
		$this->DB->execute();
		
		/* Loop through and build output arrays */
		$rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->multiModerationOverview( $rows );
	}	
	
	/**
	 * Rebuilds the multi moderation cache
	 *
	 * @access	public
	 * @return	void
	 **/
	public function multiModerationRebuildCache()
	{
		/* INI */
		$cache = array();
        
		/* Get the multi mods */
		$this->DB->build( array(
								 'select' => '*',
								 'from'   => 'topic_mmod',
								 'order'  => 'mm_title'
						 )      );
							
		$this->DB->execute();
					
		while ($i = $this->DB->fetch())
		{
			$cache[ $i['mm_id'] ] = $i;
		}
		
		/* Save the cache */
		$this->cache->setCache( 'multimod', $cache,  array( 'name' => 'multimod', 'array' => 1, 'deletefirst' => 0, 'donow' => 0 ) );		
	}
		
	/**
	 * Get selected forums
	 *
	 * @access	private
	 * @return	string	Comma separated list of forum ids
	 **/ 
    private function _getSelectedForums()
    {
    	/* INI */
		$forumids = array();
    	
		/* Check for the forums array */
    	if( is_array( $_POST['forums'] )  )
    	{
    		/* Add All Forums */
    		if( in_array( 'all', $_POST['forums'] ) )
    		{
    			return '*';
    		}
    		/* Add selected Forums */
    		else
    		{
				/* Loop through the selected forums */
				foreach( $_POST['forums'] as $l )
				{
					if( $this->registry->class_forums->forum_by_id[ $l ] )
					{
						$forumids[] = intval( $l );
					}
				}
				
				/* Do we have cats? Give 'em to Charles! */
				if( count( $forumids  ) )
				{
					foreach( $forumids as $f )
					{
						$children = $this->registry->class_forums->forumsGetChildren( $f );
						
						if( is_array( $children ) and count( $children ) )
						{
							$forumids = array_merge( $forumids, $children );
						}
					}
				}
				/* No forums */
				else
				{
					return;
				}
    		}
		}
		/* Not an array */
		else
		{
			/* All Forums */
			if ( $this->request['forums'] == 'all' )
			{
				return '*';
			}
			else
			{
				/* Anything selected? */
				if( $this->request['forums'] != "" )
				{
					$l = intval( $this->request['forums'] );
					
					/* Single Forum */
					if( $this->registry->class_forums->forum_by_id[ $l ] )
					{
						$forumids[] = intval( $l );
					}
					
					/* Check subs */
					if ( $this->request['searchsubs'] == 1 )
					{
						$children = $this->registry->class_forums->forumsGetChildren( $f );
						
						if( is_array ($children ) and count( $children ) )
						{
							$forumids = array_merge( $forumids, $children );
						}
					}
				}
			}
		}
		
		return implode( ",", $forumids );
    }
}
