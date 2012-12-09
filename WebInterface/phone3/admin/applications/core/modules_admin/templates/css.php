<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * CSS editing
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_css extends ipsCommand
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=css';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=css';
		
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
			case 'list':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'css_manage' );
				$this->_listCSS();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * List available CSS for this skin set
	 *
	 * @access	private
	 * @return	void
	 */
	private function _listCSS()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID    = intval( $this->request['setID'] );
		$css      = array();
		$setData  = array();
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Get CSS
		//-----------------------------------------
	
		$_css = $this->skinFunctions->fetchCSS( $setID );
		
		//-----------------------------------------
		// Fix up positioning
		//-----------------------------------------
		
		foreach( $_css as $_id => $_data )
		{
			$css[ $_data['css_position'] . '.' . $_data['css_id'] ] = $_data;
		}
		
		ksort( $css, SORT_NUMERIC );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=overview', $this->lang->words['cs_nav1'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=css&amp;do=list&amp;setID=' . $setID, $this->lang->words['cs_nav2'] . $setData['set_name'] );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->css_listCSS( $css, $setData );
	}
}
