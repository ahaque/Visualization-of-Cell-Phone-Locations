<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Reputation System
 * Last Updated: $Date: 2009-07-06 03:32:52 -0400 (Mon, 06 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Rev: 4840 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_members_members_reputation extends ipsCommand
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
		/* Load Skin & Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_reputation' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ) );
		
		/* URL Bits */
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=reputation';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=reputation';

		/* What to do */
		switch( $this->request['do'] )
		{
			case 'add_level_form':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->levelForm( 'add' );
			break;
			
			case 'do_add_level':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->doLevelForm( 'add' );
			break;
			
			case 'edit_level_form':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->levelForm( 'edit' );
			break;
			
			case 'do_edit_level':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->doLevelForm( 'edit' );
			break;
			
			case 'delete_level':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_delete' );
				$this->deleteLevel();
			break;
			
			default:
			case 'overview':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->reputationOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Rebuilds the reputation level cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildReputationLevelCache()
	{
		/* Cache */
		$cache = array();
		
		/* Query the levels */
		$this->DB->build( array( 'select' => '*', 'from' => 'reputation_levels', 'order' => 'level_points DESC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$cache[] = $r;
		}
		
		/* Update the cache */
		$this->cache->setCache( 'reputation_levels', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Removes the selected reputation level
	 *
	 * @access	public
	 * @return	void
	 */
	public function deleteLevel()
	{
		/* ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['invalid_id'], 11244 );
		}
		
		/* Delete */
		$this->DB->delete( 'reputation_levels', "level_id={$id}" );
		$this->rebuildReputationLevelCache();
		
		/* Redirect */
		$this->registry->output->doneScreen( $this->lang->words['rep_level_removed'], $this->lang->words['rep_management'], $this->form_code, 'redirect' );		
	}
	
	/**
	 * Handles the add/edit reputation level form
	 *
	 * @access	public
	 * @param	string	$mode	Either add or edit
	 * @return	void
	 */	
	public function doLevelForm( $mode='add' )
	{
		/* Error Checking */
		$errors = array();
		
		if( ! $this->request['level_title'] && ! $this->request['level_image'] )
		{
			$errors[] = $this->lang->words['rep_no_title_img'];
		}
		
		if( count( $errors ) )
		{
			$this->levelForm( $mode, $errors );
			return;
		}
		
		/* Build the data array */
		$data = array(
						'level_title'  => $this->request['level_title'],
						'level_image'  => $this->request['level_image'],
						'level_points' => intval( $this->request['level_points'] )
					);
					
		/* Add the level */
		if( $mode == 'add' )
		{
			/* Insert */
			$this->DB->insert( 'reputation_levels', $data );
			$this->rebuildReputationLevelCache();
			
			/* Redirect */
			$this->registry->output->doneScreen( $this->lang->words['rep_level_added'], $this->lang->words['rep_management'], $this->form_code, 'redirect' );
		}
		else
		{
			/* ID Check */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['invalid_id'], 11245 );
			}
			
			/* Update */
			$this->DB->update( 'reputation_levels', $data, "level_id={$id}" );
			$this->rebuildReputationLevelCache();
			
			/* Redirect */
			$this->registry->output->doneScreen( $this->lang->words['rep_level_edited'], $this->lang->words['rep_management'], $this->form_code, 'redirect' );			
		}		
	}
	
	/**
	 * Form for adding/exiting reputation levels
	 *
	 * @access	public
	 * @param	string	$mode	Either add or edit
	 * @param	array	$errors	Array of error messages to display
	 * @return	void
	 */
	public function levelForm( $mode='add', $errors=array() )
	{
		/* Add Level Form */
		if( $mode == 'add' )
		{
			/* ID */
			$id = 0;
			
			/* Data */
			$data = array();
						
			/* Text Bits */
			$title = $this->lang->words['rep_form_add_title'];
			$do    = 'do_add_level';
		}
		/* Edit Level Form */
		else
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['invalid_id'], 11246 );
			}
			
			/* Data */
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'reputation_levels', 'where' => 'level_id=' . $id ) );
			
			/* Text Bits */
			$title = $this->lang->words['rep_form_edit_title'];
			$do    = 'do_edit_level';
		}

		/* Default Values */
	 	$data['level_title']  = isset( $this->request['level_title'] )  ? $this->request['level_title']  : $data['level_title'];
	 	$data['level_image']  = isset( $this->request['level_image'] )  ? $this->request['level_image']  : $data['level_image'];
	 	$data['level_points'] = isset( $this->request['level_points'] ) ? $this->request['level_points'] : $data['level_points'];

		/* Form Elements */
		$form = array();
		
		$form['level_title']  = $this->registry->output->formInput( 'level_title' , $data['level_title'] );
		$form['level_image']  = $this->registry->output->formInput( 'level_image' , $data['level_image'] );
		$form['level_points'] = $this->registry->output->formInput( 'level_points', $data['level_points'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->reputationForm( $id, $do, $title, $form, $errors );
	}
	
	/**
	 * Reputation overview
	 *
	 * @access	public
	 * @return	void
	 */
	public function reputationOverview()
	{
		/* INIT */
		$levels = array();
		
		/* Query Levels */
		$this->DB->build( array( 'select' => '*', 'from' => 'reputation_levels', 'order' => 'level_points ASC' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$r['level_image'] = $r['level_image'] ? "<img src='{$this->settings['public_dir']}style_extra/reputation_icons/{$r['level_image']}'>" : '';
			
			$levels[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->reputationOverview( $levels );
	}
}