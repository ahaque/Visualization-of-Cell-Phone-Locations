<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Captcha
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_captcha extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Load Cpatcha Class */
		$this->class_captcha = $this->registry->getClass('class_captcha');
		
		/* What to do... */
		switch( $this->request['do'] )
		{
			default:
			case 'showimage':
				$this->showImage();
			break;
			case 'refresh':
				$this->refreshImage();
			break;
		}
	}
	
	/**
	 * Show the captcha image
	 * Shows the captcha image. Good god, that was a waste of time
	 *
	 * @access	public
	 * @return	void
	 */
	public function showImage()
	{
		/* INIT */
		$captcha_unique_id = trim( $this->request['captcha_unique_id'] );

		/*  GD installed? */
		$this->class_captcha->show_error_gd_img = TRUE;
		
		/* Show Image... */
		$this->class_captcha->showImage( $captcha_unique_id );
	}
	
	/**
	 * Show the captcha image
	 * Refreshes the captcha image.
	 *
	 * @access	public
	 * @return	void
	 */
	public function refreshImage()
	{
		/* INIT */
		$captcha_unique_id = trim( $this->request['captcha_unique_id'] );
		
		/*  Throw away */
		$blah	= $this->class_captcha->getTemplate();
		
		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax	= new classAjax();
		
		/* Show Image... */
		$ajax->returnString( $this->class_captcha->captchaKey );
		exit;
	}
}