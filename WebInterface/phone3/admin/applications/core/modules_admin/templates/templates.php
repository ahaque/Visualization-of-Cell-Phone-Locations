<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template editing
 * Last Updated: $Date: 2009-05-06 11:58:19 -0400 (Wed, 06 May 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 4610 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_templates extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * Recursive depth guide
	 *
	 * @access	private
	 * @var		array
	 */
	private $_depthGuide = array();
	
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=templates';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=templates';
		
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
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'templates_manage' );
				$this->_listTemplateGroups();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * List template sets
	 *
	 * @access	private
	 * @return	void
	 */
	private function _listTemplateGroups()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID           = intval( $this->request['setID'] );
		$templateGroups  = array();
		$css			 = array();
		$setData         = array();
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Fetch Template Groups
		//-----------------------------------------
		
		$templateGroups = $this->skinFunctions->fetchTemplates( $setID, 'groupNames' );
		
		//-----------------------------------------
		// Get CSS
		//-----------------------------------------
	
		$_css = $this->skinFunctions->fetchCSS( $setID );
		
		//-----------------------------------------
		// Fix up positioning
		//-----------------------------------------
		
		foreach( $_css as $_id => $_data )
		{
			$_data['css_content'] = null;
			$css[ $_data['css_position'] . '.' . $_data['css_id'] ] = $_data;
		}
		
		ksort( $css, SORT_NUMERIC );
		
		//-----------------------------------------
		// Add in group counts
		//-----------------------------------------
		
		foreach( $templateGroups as $name => $data )
		{
			$templateGroups[ $name ]['_modCount'] = $this->skinFunctions->fetchModifiedTemplateCount( $setID, $name );
			unset( $templateGroups[ $name ]['template_name'] );
			unset( $templateGroups[ $name ]['template_data'] );
			unset( $templateGroups[ $name ]['template_content'] );
		}
		
		//-----------------------------------------
		// Now ensure that skin_global is first
		//-----------------------------------------
		
		$tmp = $templateGroups['skin_global'];
		unset( $templateGroups['skin_global'] );
		$templateGroups = array_merge( array( 'skin_global' => $tmp ), $templateGroups );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=overview', $this->lang->words['te_nav1'] );
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'].'module=templates&amp;section=templates&amp;do=list&amp;setID=' . $setID, $this->lang->words['te_nav2'] . $setData['set_name'] );
		$this->registry->output->extra_title[]	= "Manage Templates in " . $setData['set_name'];
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->templates_listTemplateGroups( $templateGroups, $css, $setData );
	}
}