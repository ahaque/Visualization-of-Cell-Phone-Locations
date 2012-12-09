<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Download Manager Application
 * Last Updated: $Date: 2009-02-12 13:32:01 -0500 (Thu, 12 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		17 February 2003
 * @version		$Revision: 3944 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_applications_copyright extends ipsCommand
{
	/**
	 * HTML skin
	 *
	 * @access	public
	 * @var		object		Skin file
	 */
	public $html;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'purchase_app' );
				
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_applications' );
		
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'admin_applications' ) );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=applications&amp;section=copyright';
		$this->form_code_js	= $this->html->form_code_js	= 'module=applications&section=copyright';
		
		//-----------------------------------------
		// Just redirect to the setting
		//-----------------------------------------
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=tools&section=settings&do=setting_view&conf_title_keyword=ipbcopyright' );
	}
}