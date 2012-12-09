<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP Login/Logout Routines
 * Last Updated: $Date: 2009-07-21 19:59:38 -0400 (Tue, 21 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Tuesday 17th August 2004
 * @version		$Revision: 4924 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_login_manualResolver extends ipsCommand
{
	/**
	 * HTML Skin object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $html;
	
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	/**#@-*/

	/**
	 * Login handler object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $han_login;
	
	/**
	 * Initiate login handler
	 *
	 * @access	public
	 * @return	void
	 */
	public function initHanLogin()
	{
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$this->han_login 				= new han_login( $this->registry );
    	$this->han_login->is_admin_auth	= 1;
    	$this->han_login->init();
	}
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @param	string		Validation message
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry, $validationMessage='' )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_login' ), 'core' );

		$this->lang = $this->registry->getClass('class_localization');
		
		$this->initHanLogin();
			
		//-----------------------------------------
		// What to do?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'login':
			default:
				$this->loginForm();
			break;
			
			case 'login-complete':
				$this->loginComplete();
			break;
				
			case 'login-out':
				$this->loginOut();
			break;
		}
	}
	
	/**
	 * Check and verify the login was successful
	 *
	 * @access	public
	 * @return	void
	 */
	public function loginComplete()
	{
		//-----------------------------------------
		// Check form details.
		//-----------------------------------------
	
		$this->request['email'] = str_replace( '|', '&#124;', $this->request['email'] );
		
    	$username	= '';
    	$email		= '';
    	
		//-----------------------------------------
		// Is this a username or email address?
		//-----------------------------------------
		
		if( IPSText::checkEmailAddress( $this->request['username'] ) )
		{
			$email		= $this->request['username'];
		}
		else
		{
			$username	= $this->request['username'];
		}

		//-----------------------------------------
		// Check auth
		//-----------------------------------------
		
		$this->han_login->loginAuthenticate( $username, $email, trim($this->request['password']) );
		
		//-----------------------------------------
		// Check return code...
		//-----------------------------------------
		
		$mem = $this->han_login->member_data;

		if ( ( ! $mem['member_id'] ) or ( $this->han_login->return_code == 'NO_USER' ) )
		{
			$this->_writeToLog( $this->request['username'], 'fail' );
			$this->loginForm( $this->lang->words['bad_email_password'] );
		}
		
		if ( $this->han_login->return_code == 'NO_ACCESS' )
		{
			$this->_writeToLog( $this->request['username'], 'fail' );
			$this->loginForm( $this->lang->words['no_acp_access'] );
		}
		else if ( $this->han_login->return_code != 'SUCCESS' )
		{
			$this->_writeToLog( $this->request['username'], 'fail' );
			$this->loginForm( $this->lang->words['bad_email_password'] );
		}
		
		//-----------------------------------------
		// And sort secondary groups...
		//-----------------------------------------
		
		$mem = $this->member->setUpSecondaryGroups( $mem );
		
		//-----------------------------------------
		// Check access...
		//-----------------------------------------
		
		if ( $mem['g_access_cp'] != 1 )
		{
			$this->_writeToLog( $this->request['username'], 'fail' );
			$this->loginForm( $this->lang->words['no_acp_access'] );
		}
		else
		{
			//-----------------------------------------
			// Fix up query string...
			//-----------------------------------------
			
			$extra_query = "";
		
			if ( $_POST['qstring'] )
			{
				$extra_query = stripslashes( $_POST['qstring'] );
				$extra_query = str_replace( $this->settings['_original_base_url']	, "" , $extra_query );
				$extra_query = str_ireplace( "?index." . $this->settings['php_ext']	, "" , $extra_query );
				$extra_query = ltrim( $extra_query, '?' );
				$extra_query = preg_replace( "!adsess=(\w){32}!"								, "" , $extra_query );
				$extra_query = str_replace( "adsess=x"											, "" , $extra_query );
				$extra_query = str_replace( array( 'old_&', 'old_&amp;' )						, "" , $extra_query );
				$extra_query = preg_replace( "!s=(\w){32}!"										, "" , $extra_query );
				$extra_query = str_replace(  "module=login"										, "" , $extra_query );
				$extra_query = str_replace(  "do=login-complete"								, "" , $extra_query );
				$extra_query = str_replace(  "/admin"											, "" , $extra_query );
				$extra_query = str_replace( '&amp;'												, '&', $extra_query );
				$extra_query = preg_replace( "#&{1,}#"											, "&", $extra_query );
			}
			
			//-----------------------------------------
			// Insert session
			//-----------------------------------------
			
			$sess_id = md5( uniqid( microtime() ) );
			
			$this->DB->delete( 'core_sys_cp_sessions', 'session_member_id=' . $mem['member_id'] );
			
			/* Grab user agent */
			$uAgent = array();
			
			$this->DB->insert( 'core_sys_cp_sessions', array (
																	'session_id'                => $sess_id,
																	'session_ip_address'        => $this->member->ip_address,
																	'session_member_name'       => $mem['members_display_name'],
																	'session_member_id'         => $mem['member_id'],
																	'session_member_login_key'  => $mem['member_login_key'],
																	'session_location'          => 'index',
																	'session_log_in_time'       => time(),
																	'session_running_time'      => time(),
																	'session_app_data'			=> serialize( $uAgent ),
																	'session_url'               => '',
														  )        );
					
			$this->request[ 'adsess'] =  $sess_id ;

			//-----------------------------------------
			// Redirect...
			//-----------------------------------------

			$url = $this->settings['_original_base_url'] . '/' . CP_DIRECTORY . '/index.php?adsess=' . $sess_id . '&' . $extra_query;
		
			$this->_writeToLog( $this->request['username'], 'ok' );
			
			ipsRegistry::getClass('output')->redirect( $url, $this->lang->words['login_successful'] );
		}
	}
	
	/**
	 * Log the user out
	 *
	 * @access	public
	 * @return	void
	 */
	public function loginOut()
	{
		//-----------------------------------------
		// Do it..
		//-----------------------------------------
		
		$this->DB->delete( 'core_sys_cp_sessions', "session_id='" . $this->request['adsess'] . "'" );
		
		//-----------------------------------------
		// Bash cookie
		//-----------------------------------------
		
		IPSCookie::set("ipb3AcpSessionId", 'x', -1);
		
		//-----------------------------------------
		// Redirect...
		//-----------------------------------------
		
		ipsRegistry::getClass('output')->redirect( $this->settings['base_url'] . '/' . CP_DIRECTORY . '/index.php', $this->lang->words['logout_complete'] );
	}
	
	/**
	 * Log the user out
	 *
	 * @access	public
	 * @param	string		Message to show on the form
	 * @return	void
	 */
	public function loginForm( $message='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$message = $message ? $message : $this->member->sessionClass()->getMessage();

		//-------------------------------------------------------
		// Remove all out of date sessions, like a good boy. Woof.
		//-------------------------------------------------------
		
		$cut_off_stamp = time() - 60*60*2;
		
		$this->DB->delete( 'core_sys_cp_sessions', "session_running_time < {$cut_off_stamp}" );
		
		//------------------------------------------------------
		// Start form
		//------------------------------------------------------
		
		$qs = str_replace( '&amp;'		, '&'			, IPSText::parseCleanValue( urldecode( my_getenv( 'QUERY_STRING' ) ) ) );
		$qs = str_replace( 'adsess='	, 'old_adsess='	, $qs );
		$qs = str_replace( 'module=menu', ''			, $qs );
		
		$additional_data	= $this->han_login->additionalFormHTML();
		$replace			= false;
		$data				= array();
		
		if( !is_null($additional_data) AND is_array($additional_data) AND count($additional_data) )
		{
			$replace 	= $additional_data[0];
			$data		= $additional_data[1];
		}

		ipsRegistry::getClass('output')->html_title = $this->lang->words['ipb_login'];
		ipsRegistry::getClass('output')->html_main  = str_replace( '<%CONTENT%>', ipsRegistry::getClass('output')->global_template->log_in_form( $qs, $message, $replace == 'replace' ? true : false, $data ), ipsRegistry::getClass('output')->global_template->global_main_wrapper_no_furniture() );
		ipsRegistry::getClass('output')->html_main  = str_replace( '<%TITLE%>'  , ipsRegistry::getClass('output')->html_title, ipsRegistry::getClass('output')->html_main );
			
		@header("Content-type: text/html");
		print ipsRegistry::getClass('output')->html_main;
		exit();
	}

	/**
	 * Write to the admin log in loggy ma log
	 *
	 * @access	private
	 * @param	string		Username
	 * @param	string		ok/fail flag
	 * @return	void
	 */
	private function _writeToLog( $username='', $flag='fail' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$username 			 = $username ? $username : $this->request['username'];
		$flag    			 = ( $flag == 'ok' ) ? 1 : 0;
		$admin_post_details  = array();
		
		//-----------------------------------------
		// Generate POST / GET details
		//-----------------------------------------
		
		foreach( $_GET as $k => $v )
		{
			$admin_post_details['get'][ $k ] = $v;
		}
		
		foreach( $_POST as $k => $v )
		{
			if ( $k == 'password' AND IPSText::mbstrlen( $v ) > 1 )
			{
				$v = $v ? ( (IPSText::mbstrlen( $v ) - 1) > 0 ? str_repeat( '*', IPSText::mbstrlen( $v ) - 1 ) : '' ) . substr( $v, -1, 1 ) : '';
			}
			
			$admin_post_details['post'][ $k ] = $v;
		}
		
		//-----------------------------------------
		// Write to disk...
		//-----------------------------------------
		
		$this->DB->insert( 'admin_login_logs', array( 'admin_ip_address'		=> $this->member->ip_address,
														 'admin_username'		=> $username,
														 'admin_time'			=> time(),
														 'admin_success'		=> $flag,
														 'admin_post_details'	=> serialize( $admin_post_details ) ) );
	}	
}