<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Easy Logo Changer
 * Last Updated: $Date: 2009-07-06 03:32:52 -0400 (Mon, 06 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Tuesday 17th August 2004
 * @version		$Revision: 4840 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_templates_easylogo extends ipsCommand
{
	/**
	 * HTML Skin object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $html;
	
	/**
	 * Skin Functions Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );
		
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=easylogo';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=easylogo';
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		
		$this->skinFunctions = new skinCaching( $registry );		
	
		//-----------------------------------------
		// What to do?
		//-----------------------------------------

		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'easy_logo' );
		
		switch( $this->request['do'] )
		{
			default:
			case 'splash':
				$this->splash();
				break;
			case 'finish':
				$this->complete();
				break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Finish changing the logo
	 *
	 * @access	public
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function complete()
	{
		//-----------------------------------------
		// Check id
		//-----------------------------------------
		
		if ( ! $this->request['skin'] )
		{
			$this->registry->output->global_message = $this->lang->words['el_noskinid'];
			$this->splash();
			return;
		}

		//-----------------------------------------
		// Upload or new logo?
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			if ( ! $_POST['logo_url'] )
			{
				$this->registry->output->global_message = $this->lang->words['el_nofile'];
				$this->splash();
				return;
			}
			
			$newlogo = $_POST['logo_url'];
		}
		else
		{
			if ( ! is_writable( IPS_CACHE_PATH . 'public/style_images' ) )
			{
				$this->registry->output->global_message = $this->lang->words['el_chmod'];
				$this->splash();
				return;
			}
			
			//-----------------------------------------
			// Upload
			//-----------------------------------------
			
			$FILE_NAME = $_FILES['FILE_UPLOAD']['name'];
			$FILE_SIZE = $_FILES['FILE_UPLOAD']['size'];
			$FILE_TYPE = $_FILES['FILE_UPLOAD']['type'];
			
			//-----------------------------------------
			// Silly spaces
			//-----------------------------------------
			
			$FILE_NAME = preg_replace( "/\s+/", "_", $FILE_NAME );
			
			//-----------------------------------------
			// Naughty Opera adds the filename on the end of the
			// mime type - we don't want this.
			//-----------------------------------------
			
			$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
			
			//-----------------------------------------
			// Correct file type?
			//-----------------------------------------
			
			if ( ! preg_match( "#\.(?:gif|jpg|jpeg|png)$#is", $FILE_NAME ) )
			{
				$this->registry->output->global_message = $this->lang->words['el_wrongformat'];
				$this->splash();
				return;
			}
			
			if ( move_uploaded_file( $_FILES[ 'FILE_UPLOAD' ]['tmp_name'], IPS_CACHE_PATH . "public/style_images/{$this->request['skin']}_" . $FILE_NAME ) )
			{
				@chmod( IPS_CACHE_PATH."public/style_images/{$this->requestt['skin']}_".$FILE_NAME, 0777 );
			}
			else
			{
				$this->registry->output->global_message = $this->lang->words['el_chmod'];
				$this->start();
				return;
			}
			
			$newlogo = "{$this->settings['public_dir']}style_images/{$this->request['skin']}_" . urlencode($FILE_NAME);
		}

		//-----------------------------------------
		// Update the macro..
		//-----------------------------------------
		
		$this->skinFunctions->saveReplacementFromEdit( $this->request['replacementId'], $this->request['skin'], $newlogo, 'logo_img' );
		
		//-----------------------------------------
		// Rebuild cache(s)
		//-----------------------------------------

		$this->skinFunctions->rebuildReplacementsCache( $this->request['skin'] );
		
		$this->registry->output->global_message = sprintf( $this->lang->words['el_log'], $this->request['skin'] );

		if( $this->skinFunctions->fetchErrorMessages() )
		{
			$this->registry->output->global_message .= "<br />" . implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
		}
		
		if( $this->skinFunctions->fetchMessages() )
		{
			$this->registry->output->global_message .= "<br />" . implode( "<br />", $this->skinFunctions->fetchMessages() );
		}
		
		$this->splash();
	}
	
	/**
	 * Show the form to change the logo
	 *
	 * @access	public
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function splash()
	{
		//-----------------------------------------
		// Can we upload into style_images?
		//-----------------------------------------
		
		$warning	= ! is_writable( IPS_CACHE_PATH . 'public/style_images' ) ? true : false;
		
		//-----------------------------------------
		// Get header logo image
		//-----------------------------------------
		
		$replacements	= $this->skinFunctions->fetchReplacements( 0 );
		$currentUrl		= $replacements['logo_img']['replacement_content'];
		$currentId		= $replacements['logo_img']['replacement_id'];

		$this->registry->output->html .= $this->html->easyLogo( $warning, $currentUrl, $currentId );
	}
	
}