<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * User control panel resolver
 * Last Updated: $Date: 2009-07-29 21:58:31 -0400 (Wed, 29 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 4951 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Main loader class
*/
class public_core_usercp_manualResolver extends ipsCommand
{
	/**
	 * Page Title set by sub classes
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_pageTitle;
	
	/**
	* Navigation array set by sub classes
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_nav = array();
	
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
		// INIT
		//-----------------------------------------
		
		$_thisNav = array();
		
		//-----------------------------------------
		// Load language
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_usercp' ) );
		
		//-----------------------------------------
		// Logged in?
		//-----------------------------------------
			
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'].'&app=core&module=global&section=login&do=form' );
			
			exit();
		}
		
		//-----------------------------------------
		// Make sure they're clean
		//-----------------------------------------
		
		$this->request['tab'] = IPSText::alphanumericalClean( $this->request['tab'] );
		$this->request['area'] = IPSText::alphanumericalClean( $this->request['area'] );
		
		//-----------------------------------------
		// Set up some basics...
		//-----------------------------------------
		
		$_TAB  = ( $this->request['tab'] )  ? $this->request['tab']  : 'core';
		$_AREA = ( $this->request['area'] ) ? $this->request['area'] : 'settings';
		$_DO   = ( $this->request['do'] )   ? $this->request['do']   : 'show';
		$_FUNC = ( $_DO == 'show' ) ? 'showForm' : ( $_DO == 'save' ? 'saveForm' : $_DO );
		$tabs  = array();
		$errors = array();
		
		//-----------------------------------------
		// Got a plug in?
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/interfaces/interface_usercp.php' );
		
		$_FILE  = IPSLib::getAppDir(  $_TAB ) . '/extensions/usercpForms.php';
		$_CLASS = 'usercpForms_' . $_TAB;
		
		if ( ! file_exists( $_FILE ) )
		{
			$this->registry->getClass('output')->showError( 'usercp_bad_tab', 10147 );
			exit();
		}
		
		//-----------------------------------------
		// Grab tabs
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			if ( IPSLib::appIsInstalled( $app_dir ) )
			{
				$__file  = IPSLib::getAppDir( $app_dir ) . '/extensions/usercpForms.php';
				$__class = 'usercpForms_' . $app_dir;
				
				if ( file_exists( $__file ) )
				{
					require_once( $__file );
					$_usercp_module = new $__class();
					$_usercp_module->makeRegistryShortcuts( $this->registry );
					
					if ( is_callable( array( $_usercp_module, 'init' ) ) )
					{
						$_usercp_module->init();
						
						/* Set default area? */
						if (  ( $_TAB == $app_dir ) AND ! isset( $_REQUEST['area'] ) )
						{
							if ( isset( $_usercp_module->defaultAreaCode ) )
							{
								$this->request['area'] = $_AREA = $_usercp_module->defaultAreaCode;
							}
						}
					}

					if ( is_callable( array( $_usercp_module, 'getLinks' ) ) )
					{
						$tabs[ $app_dir ]['_name'] = $_usercp_module->tab_name ? $_usercp_module->tab_name : IPSLib::getAppTitle( $app_dir );
						$tabs[ $app_dir ]['_menu'] = $_usercp_module->getLinks();
						
						if ( ! $tabs[ $app_dir ]['_menu'] )
						{
							unset( $tabs[ $app_dir ] );
						}
						
						/* Add in 'last' element */
						$tabs[ $app_dir ]['_menu'][ count( $tabs[ $app_dir ]['_menu'] ) - 1 ]['last'] = 1;
						
						/* This nav? */
						if ( ! count( $_thisNav ) AND $app_dir == $_TAB )
						{
							foreach( $tabs[ $app_dir ]['_menu'] as $_navData )
							{
								if ( $_navData['url'] == 'area=' . $_AREA )
								{
									$_thisNav = array( 'app=core&amp;module=usercp&amp;tab=' . $_TAB . '&amp;area=' . $_AREA, $_navData['title'] );
								}
							}
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Set up basic navigation
		//-----------------------------------------
		
		$this->_nav[] = array( $this->lang->words['t_title'], '&amp;app=core&amp;module=usercp' );
		
		if ( isset( $_thisNav[0] ) )
		{
			$this->_nav[] = array( $_thisNav[1], $_thisNav[0] );
		}
		
		//-----------------------------------------
		// Load it...
		//-----------------------------------------
		
		require_once( $_FILE );
		$usercp_module =  new $_CLASS();
		$usercp_module->makeRegistryShortcuts( $this->registry );
		$usercp_module->init();
		
		if ( ( $_DO == 'saveForm' || $_DO == 'showForm' ) AND ! is_callable( array( $usercp_module, $_FUNC ) ) )
		{
			$this->registry->getClass('output')->showError( 'usercp_bad_tab', 10148, true );
			exit();
		}
		
		//-----------------------------------------
		// Run it...
		//-----------------------------------------

		if ( $_FUNC == 'showForm' )
		{
			//-----------------------------------------
			// Facebook email
			//-----------------------------------------
			
			//@facebook concession
			if ( IPSLib::fbc_enabled() === TRUE )
			{
				if ( ! $this->memberData['fb_emailallow'] AND strstr( $this->memberData['email'], '@proxymail.facebook.com' ) )
				{
					require_once( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php' );
					$fb = new facebook_connect( $this->registry );
					
					try
					{
						$fb->testConnectSession();
						$result = $fb->users_hasAppPermission( 'email' );
			
						IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'fb_emailallow' => intval( $result ) ) ) );
					}
					catch( Exception $error )
					{
					}
				}
			}
			
			$html = $usercp_module->showForm( $_AREA );
		}
		else if ( $_FUNC == 'saveForm' )
		{
			//-----------------------------------------
			// Check secure key...
			//-----------------------------------------

			if ( $this->request['secure_hash'] != $this->member->form_hash )
			{
				$html = $usercp_module->showForm( $_AREA );
				$errors[] = $this->lang->words['securehash_not_secure'];
			}
			else
			{
				$errors = $usercp_module->saveForm( $_AREA );

				$do = ( $usercp_module->do_url ) ? $usercp_module->do_url : 'show';

				if ( is_array( $errors ) AND count( $errors ) )
				{
					$html = $usercp_module->showForm( $_AREA, $errors );
				}
				else if ( $usercp_module->ok_message )
				{
					$this->registry->getClass('output')->redirectScreen( $usercp_module->ok_message, $this->settings['base_url'] . 'app=' . IPS_APP_COMPONENT . '&module=usercp&tab=' . $_TAB . '&area=' . $_AREA . '&do='.$do.'&saved=1', 1 );
				}
				else
				{
					$this->registry->getClass('output')->silentRedirect( $this->settings['base_url_with_app'] . 'module=usercp&tab=' . $_TAB . '&area=' . $_AREA . '&do='.$do.'&saved=1'.'&_r='.time() );
				}
			}
		}
		else
		{
			if ( ! is_callable( array( $usercp_module, 'runCustomEvent' ) ) )
			{
				$html = $usercp_module->showForm( $_AREA );
				$errors[] = $this->lang->words['called_invalid_function'];
			}
			else
			{
				$html = $usercp_module->runCustomEvent( $_AREA );
			}
		}

		//-----------------------------------------
		// If you've run a custom event, may need to
		// reset the "area" to highlight the menu correctly
		//-----------------------------------------
		
		if ( is_callable( array( $usercp_module, 'resetArea' ) ) )
		{
			$_AREA = $usercp_module->resetArea( $_AREA );
		}
			
		//-----------------------------------------
		// Wrap form and show
		//-----------------------------------------
		
		$template = $this->registry->getClass('output')->getTemplate('ucp')->userCPTemplate( $_TAB, $html, $tabs, $_AREA, $errors, $usercp_module->hide_form_and_save_button, $usercp_module->uploadFormMax );
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( ( $this->_pageTitle ) ? $this->settings['board_name'] . " {$this->lang->words['pagetitle_bit']} : " . $this->_pageTitle : $this->settings['board_name'] . " {$this->lang->words['pagetitle_bit']}" );
		$this->registry->getClass('output')->addContent( $template );

		if ( is_array( $this->_nav ) AND count( $this->_nav ) )
		{
			foreach( $this->_nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
			}
		}

		if ( is_array( $usercp_module->_nav ) AND count( $usercp_module->_nav ) )
		{
			foreach( $usercp_module->_nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
			}
		}		

        $this->registry->getClass('output')->sendOutput();
	}
}