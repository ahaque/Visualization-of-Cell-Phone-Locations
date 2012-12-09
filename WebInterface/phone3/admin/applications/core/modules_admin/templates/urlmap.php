<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin URL mapping
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		2.3.x
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_urlmap extends ipsCommand
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
	
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=urlmap';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=urlmap';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		
		$this->skinFunctions = new skinCaching( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'show':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'url_map_manage' );
				$this->_showURLMappingList();
			break;
			case 'remapAdd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'url_map_manage' );
				$this->_remapForm('add');
			break;
			case 'remapEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'url_map_manage' );
				$this->_remapForm('edit');
			break;
			case 'remapAddDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'url_map_manage' );
				$this->_remapSave('add');
			break;
			case 'remapEditDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'url_map_manage' );
				$this->_remapSave('edit');
			break;
			case 'remapRemove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'url_map_delete' );
				$this->_remapRemove();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Remove a remap
	 *
	 * @access	private
	 * @return	void
	 */
	private function _remapRemove()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$setID  = intval( $this->request['setID'] );
		$map_id = intval($this->request['map_id']);
		
		//-----------------------------------------
		// Remove it
		//-----------------------------------------
		
		$this->DB->delete( 'skin_url_mapping', 'map_id=' . $map_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->skinFunctions->rebuildURLMapCache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->getClass('output')->global_message = $this->lang->words['um_removed'];
		$this->_showURLMappingList();
	}
	
	/**
	 * Save the form
	 *
	 * @access	private
	 * @param	string		Type of form
	 * @return	string		HTML
	 */
	private function _remapSave( $type='add' )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$setID          = intval( $this->request['setID'] );
		$map_id         = intval($this->request['map_id']);
		$map_title      = trim( IPSText::stripslashes( IPSText::htmlspecialchars($_POST['map_title'])) );
		$map_url        = trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['map_url'])) );
		$map_match_type = trim( $this->request['map_match_type'] );
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $map_id OR ! $map_title OR ! $map_url )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['um_entireform'];
				$this->_remapForm( $type );
				return;
			}
		}
		else
		{
			if ( ! $map_title OR ! $map_url )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['um_entireform'];
				$this->_remapForm( $type );
				return;
			}
		}
	
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 'map_title'       => $map_title,
						'map_url'         => $map_url,
						'map_match_type'  => $map_match_type,
						'map_skin_set_id' => $setID,
					 );
					 
		if ( $type == 'add' )
		{
			$array['map_date_added'] = time();
			
			$this->DB->insert( 'skin_url_mapping', $array );
			
			$this->registry->getClass('output')->global_message = $this->lang->words['um_added'];
		}
		else
		{
			
			$this->DB->update( 'skin_url_mapping', $array, 'map_id='.$map_id );
			
			$this->registry->getClass('output')->global_message = $this->lang->words['um_edited'];
		}
		
		//-----------------------------------------
		// Rebuild skin cache...
		//-----------------------------------------
		
		$this->skinFunctions->rebuildURLMapCache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		return $this->_showURLMappingList();
	}
	
	/**
	 * Remap form
	 *
	 * @access	private
	 * @param	string	Type of form
	 * @return	string	HTML
	 */
	private function _remapForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID          = intval( $this->request['setID'] );
		$map_id         = intval( $this->request['map_id'] );
		$map_match_type = array( 0 => array( 'contains', $this->lang->words['um_contains']   ),
								 1 => array( 'exactly' , $this->lang->words['um_isexactly'] ) );
		$form           = array();
		$remap          = array();
		$setData = array();
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'remapAddDo';
			$title    = $this->lang->words['um_addnew'];
			$button   = $this->lang->words['um_addnew'];
		}
		else
		{
			$remap = $this->DB->buildAndFetch( array( 'select' => '*',
															 'from'   => 'skin_url_mapping',
															 'where'  => 'map_id='.$map_id ) );
			
			
			if ( ! $remap['map_id'] )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['um_noid'];
				$this->_showURLMappingList();
				return;
			}
			
			$formcode = 'remapEditDo';
			$title    = $this->lang->words['um_editremap'].$remap['map_title'];
			$button   = $this->lang->words['um_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['map_title']       = $this->registry->getClass('output')->formInput(    'map_title'           , IPSText::htmlspecialchars( ( isset($_POST['map_title']) AND $_POST['map_title'] ) ? $_POST['map_title'] : $remap['map_title'] ) );
		$form['map_match_type']  = $this->registry->getClass('output')->formDropdown( 'map_match_type'      , $map_match_type, ( isset($_POST['map_match_type']) AND $_POST['map_match_type'] ) ? $_POST['map_match_type'] : $remap['map_match_type'] );
		$form['map_url']         = $this->registry->getClass('output')->formInput(    'map_url'             , IPSText::htmlspecialchars( ( isset($_POST['map_url']) AND $_POST['map_url'] ) ? $_POST['map_url'] : $remap['map_url'] ) );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=overview', $this->lang->words['um_nav1'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=urlmap&amp;do=show&amp;setID=' . $setID, $this->lang->words['um_nav2'] . $setData['set_name'] );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->urlmap_showForm( $form, $title, $formcode, $button, $remap, $setData );
	}
	
	/**
	 * Show URL maps for this skin set
	 *
	 * @access	private
	 * @return	void
	 */
	private function _showURLMappingList()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID   = intval( $this->request['setID'] );
		$remaps  = array();
		$setData = array();
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );

		//-----------------------------------------
		// Get sessions
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*',
									   'from'   => 'skin_url_mapping',
									   'where'  => 'map_skin_set_id=' . $setID,
									   'order'  => 'map_date_added DESC' ) );


		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Gen data
			//-----------------------------------------

			$row['_date'] = $this->registry->getClass('class_localization')->getDate( $row['map_date_added'], 'TINY' );

			//-----------------------------------------
			// Culmulate
			//-----------------------------------------

			$remaps[] = $row;
		}

		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=overview', $this->lang->words['um_nav1'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=urlmap&amp;do=show&amp;setID=' . $setID, $this->lang->words['um_nav2'] . $setData['set_name'] );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->urlmap_showURLMaps( $remaps, $setData );
	}
}