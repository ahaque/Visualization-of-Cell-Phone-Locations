<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member management
 * Last Updated: $Date: 2009-05-11 04:34:21 -0400 (Mon, 11 May 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Revision: 4626 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_members_members_ranks extends ipsCommand
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
	 * Editor object
	 *
	 * @access	private
	 * @var		object			Editor library
	 */
	private $han_editor;

	/**
	 * Trash can forum id
	 *
	 * @access	private
	 * @var		integer			Trash can forum
	 */
	private $trash_forum		= 0;

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
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_ranks');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=ranks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=ranks';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ) );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'title':
				$this->_titlesStart();
			break;

			case 'rank_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_edit' );
				$this->_titlesForm( 'edit' );
			break;

			case 'rank_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_add' );
				$this->_titlesForm( 'add' );
			break;

			case 'do_add_rank':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_add' );
				$this->_titlesSave( 'add' );
			break;

			case 'do_rank_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_edit' );
				$this->_titlesSave( 'edit' );
			break;

			case 'rank_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ranks_delete' );
				$this->_titlesDelete();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
		
	}
	
	/**
	 * Recache ranks
	 *
	 * @access	public
	 * @return	void
	 */
	public function titlesRecache()
	{
		$ranks = array();
        	
		$this->DB->build( array( 'select'	=> 'id, title, pips, posts',
										'from'	=> 'titles',
										'order'	=> 'posts DESC',
							)      );
		$this->DB->execute();
					
		while ( $i = $this->DB->fetch() )
		{
			$ranks[ $i['id'] ] = array(
										'TITLE'	=> $i['title'],
										'PIPS'	=> $i['pips'],
										'POSTS'	=> $i['posts'],
									);
		}

		$this->cache->setCache( 'ranks', $ranks, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	
	/**
	 * Overview page
	 *
	 * @access	protected
	 * @return	void			[Outputs to screen]
	 */
	protected function _titlesStart()
	{
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['member_rank_nav'] );
		
		$titles		= array();

		//-----------------------------------------
		// Parse macro
		//-----------------------------------------

		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_replacements', 'where' => "replacement_set_id=0 AND replacement_key='pip_pip'" ) );

    	$row['A_STAR'] = str_replace( "{style_image_url}", $this->settings['img_url'], $row['replacement_content'] );

		//-----------------------------------------
		// Lets get on with it...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'titles', 'order' => "posts" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$r['A_STAR']	= $row['A_STAR'];
			$titles[]		= $r;
		}
										 
		$this->registry->output->html .= $this->html->titlesOverview( $titles );
	}
	
	/**
	 * Save rank [add/edit]
	 *
	 * @access	protected
	 * @param	string			'add' or 'edit'
	 * @return	void			[Outputs to screen]
	 */
	protected function _titlesSave( $type = 'add' )
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		foreach( array( 'title', 'pips' ) as $field )
		{
			if ( ! isset( $this->request[ $field ] ) )
			{
				$this->registry->output->showError( $this->lang->words['rnk_completeform'], 11239 );
			}
		}
		
		if ( $this->request['pips'] > 100 )
		{
			$this->registry->output->showError( $this->lang->words['rnk_max100'], 11240 );
		}
		
		if( $type == 'add' )
		{
			$this->DB->insert( 'titles', array(
											 'posts'  => intval( trim( $this->request['posts'] ) ),
											 'title'  => trim($this->request['title']),
											 'pips'   => trim($this->request['pips']),
								  )       );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['rnk_added'] );
		}
		else
		{
			if ( !$this->request['id'] )
			{
				$this->registry->output->showError( $this->lang->words['rnk_notfound'], 11241 );
			}

			$this->DB->update( 'titles', array ( 'posts'  => trim($this->request['posts']),
															  'title'  => trim($this->request['title']),
															  'pips'   => trim($this->request['pips']),
													        ) , "id=" . intval( $this->request['id'] )  );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['rnk_edit'] );
		}
		
		$this->titlesRecache();

		if( $type == 'add' )
		{
			$this->registry->output->doneScreen($this->lang->words['rnk_added2'], $this->lang->words['rnk_rankcontrol'], "{$this->form_code}&do=title", 'redirect' );
		}
		else
		{
			$this->registry->output->doneScreen($this->lang->words['rnk_edited'], $this->lang->words['rnk_rankcontrol'], "{$this->form_code}&do=title", 'redirect' );	
		}
	}
	
	/**
	 * Delete a rank
	 *
	 * @access	protected
	 * @return	void			[Outputs to screen]
	 */
	protected function _titlesDelete()
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		if ( !$this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['rnk_notfounddel'], 11242 );
		}
		
		$this->DB->delete( 'titles', "id=" . intval($this->request['id']) );
		
		$this->titlesRecache();
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['rnk_removed'] );
		
		$this->registry->output->doneScreen($this->lang->words['rnk_removed2'], $this->lang->words['rnk_rankcontrol'], "{$this->form_code}&do=title", 'redirect' );
	}
	

	/**
	 * Show the form to add/edit a rank
	 *
	 * @access	protected
	 * @param	string			Type of form (add|edit)
	 * @return	void			[Outputs to screen]
	 */
	protected function _titlesForm( $mode='edit' )
	{
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['rnk_setup'] );
		
		if ( $mode == 'edit' )
		{
			$form_code = 'do_rank_edit';
			
			if ( !$this->request['id'] )
			{
				$this->registry->output->showError( $this->lang->words['rnk_notfound'], 11243 );
			}
			
			$rank = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'titles', 'where' => "id=" . intval($this->request['id']) ) );

			$button = $this->lang->words['rnk_editrank'];
		}
		else
		{
			$form_code = 'do_add_rank';
			
			$rank = array( 'posts' => '', 'title' => "", 'pips' => "");

			$button = $this->lang->words['rnk_addrank'];
		}
		
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->titlesForm( $rank, $form_code, $button );
	}
}