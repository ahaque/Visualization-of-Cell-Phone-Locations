<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Log Splash Screen
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		27th January 2004
 * @version		$Rev: 3887 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_tools_logsSplash extends ipsCommand 
{
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
		/* Load Template */
		$this->html	= $this->registry->output->loadTemplate( 'cp_skin_adminlogs' );
		
		/* Load Language */		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );		
		
		/* URL Bits */
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=splash';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=splash';
		
		/* Get the splash screen */
		$this->registry->output->html = $this->html->logSplashScreen();
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
}