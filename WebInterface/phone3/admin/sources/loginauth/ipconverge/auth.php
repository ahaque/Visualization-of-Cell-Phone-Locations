<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : IP.Converge Method
 * Last Updated: $Date: 2009-08-10 17:08:53 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 5009 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_ipconverge extends login_core implements interface_login
{
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();

	/**
	 * API Server object
	 *
	 * @access	private
	 * @var		object
	 */
	private $api_server;
	
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() )
	{
		$this->method_config	= $method;
		
		parent::__construct( $registry );
	}
	
	/**
	 * Authenticate the request
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authenticate( $username, $email_address, $password )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$md5_once_pass = md5( $password );
		$external_data = '';
		
		//-----------------------------------------
		// Check admin authentication request
		//-----------------------------------------
		
		if ( $this->is_admin_auth )
		{
			$this->adminAuthLocal( $username, $email_address, $password );
			
  			if ( $this->return_code == 'SUCCESS' )
  			{
  				return true;
  			}
		}
		
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'converge_local',
															'where'  => 'converge_active=1' 
													) 		);
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		//-----------------------------------------
		// If the user submitted a name, grab email
		//-----------------------------------------

		if( $username AND !$email_address )
		{
			$temp = IPSMember::load( $username, 'extendedProfile', 'username' );
			
			if( $temp['email'] )
			{
				$email_address = $temp['email'];
			}
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
			$this->api_server = new classApiServer();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $email_address,
						  'md5_once_password' => $md5_once_pass,
						  'username'		  => $username
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->apiSendRequest( $url, 'convergeAuthenticate', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		else if( $this->api_server->params['response'] != 'SUCCESS' )
		{
			if( $this->api_server->params['response'] == 'FLAGGED_REMOTE' )
			{
				$this->return_code = 'FLAGGED_REMOTE';
				return false;
			}

			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		if( $this->api_server->params['extra_data'] )
		{
			$external_data = unserialize( base64_decode( $this->api_server->params['extra_data'] ) );
		}
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		$this->_loadMember( $email_address );
		
		if ( !$this->memberData['member_id'] )
		{
			//-----------------------------------------
			// Got no member - but auth passed - create?
			//-----------------------------------------
			
			$tmp_display	= $this->api_server->params['username'] ? $this->api_server->params['username'] : '';//'cvg_' . mt_rand();
			$email_address	= $email_address ? $email_address : $this->api_server->params['email'];

			$this->member_data = $this->createLocalMember( 
														array( 
															'members' => array( 
																			'name'					=> $tmp_display,
																			'members_display_name'	=> $tmp_display,
																			'password'				=> $password,
																			'email'					=> $email_address,

																			//-----------------------------------------
																			// @link	http://forums./index.php?autocom=tracker&showissue=11868
																			//-----------------------------------------
																			
																			'joined'				=> $this->api_server->params['joined'],
																			'ip_address' 			=> $this->api_server->params['ipaddress'],
																			) 
															) 
														);
		}
		
		//-----------------------------------------
		// Allow for custom code execution
		//-----------------------------------------
		
		if( file_exists( IPS_ROOT_PATH . 'sources/loginauth/ipconverge/custom.php' ) )
		{
			include_once( IPS_ROOT_PATH . 'sources/loginauth/ipconverge/custom.php' );
		}

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->return_code = $this->api_server->params['response'];
		return true;
	}
	
	/**
	 * Load the member
	 *
	 * @access	private
	 * @param	string		Email Address
	 * @return	void
	 */
	private function _loadMember( $email_address )
	{
		$this->member_data = IPSMember::load( $email_address, 'extendedProfile,groups' );
		
		if( $this->member_data['member_id'] )
		{
			ipsRegistry::instance()->member()->setMember( $this->member_data['member_id'] );
		}
	}
	
	/**
	 * Check if an email already exists
	 *
	 * @access	public
	 * @param	string		Email Address
	 * @return	boolean		Request was successful
	 */
	public function emailExistsCheck( $email )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'converge_local',
															'where'  => 'converge_active=1' 
													) 		);
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
			$this->api_server = new classApiServer();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $email,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->apiSendRequest( $url, 'convergeCheckEmail', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		$this->return_code = $this->api_server->params['response'];
		return true;
	}
	
	/**
	 * Change an email address
	 *
	 * @access	public
	 * @param	string		Old Email Address
	 * @param	string		New Email Address
	 * @return	boolean		Request was successful
	 */
	public function changeEmail( $old_email, $new_email )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->DB->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
			$this->api_server = new classApiServer();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $new_email,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->apiSendRequest( $url, 'convergeCheckEmail', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		if( $this->api_server->params['response'] == 'EMAIL_NOT_IN_USE' )
		{
			//-----------------------------------------
			// Change email
			//-----------------------------------------
			
			$request = array( 'auth_key'          => $converge['converge_api_code'],
							  'product_id'        => $converge['converge_product_id'],
							  'old_email_address' => $old_email,
							  'new_email_address' => $new_email,
							);

			$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

			//-----------------------------------------
			// Send request
			//-----------------------------------------

			$this->api_server->apiSendRequest( $url, 'convergeChangeEmail', $request );

			//-----------------------------------------
			// Handle errors...
			//-----------------------------------------

			if ( count( $this->api_server->errors ) )
			{
				$this->return_code = 'WRONG_AUTH';
				return false;
			}
		}
		
		$this->return_code = $this->api_server->params['response'];
		return true;
	}
	
	
	/**
	 * Change a password
	 *
	 * @access	public
	 * @param	string		Email Address
	 * @param	string		New Password
	 * @return	boolean		Request was successful
	 */
	public function changePass( $email, $new_pass )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'converge_local', 'where' => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
			$this->api_server = new classApiServer();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $email,
						  'md5_once_password' => $new_pass,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->apiSendRequest( $url, 'convergeChangePassword', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		$this->return_code = $this->api_server->params['response'];
		return true;
	}
	
	/**
	 * Change a login name
	 *
	 * @access	public
	 * @param	string		Old Name
	 * @param	string		New Name
	 * @param	string		User's email address
	 * @return	boolean		Request was successful
	 */
	public function changeName( $old_name, $new_name, $email_address )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->DB->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
			$this->api_server = new classApiServer();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $email_address,
						  'old_username'	  => $old_name,
						  'new_username'	  => $new_name,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->apiSendRequest( $url, 'convergeChangeUsername', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}

		$this->return_code = $this->api_server->params['response'];
		return true;
	}
	
	/**
	 * Ping Converge's convergeLogOut method to log this user out of all other converged apps (if SSO is enabled)
	 *
	 * @access	public
	 * @param	void
	 * @return	void
	 */
	public function logoutCallback()
	{		
		/* Fetch converge */
		$converge = $this->DB->buildAndFetch( array(	'select' => '*',
														'from'   => 'converge_local',
														'where'  => 'converge_active = 1' ) );
																	
		if ( is_array( $converge ) && count( $converge ) )
		{
			/* Set API class */
			if ( ! is_object( $this->api_server ) )
			{
				require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
				$this->api_server = new classApiServer();
			}
			
			/* Build request */
			$request = array(	'auth_key'			=>	$converge['converge_api_code'],
								'product_id'		=>	$converge['converge_product_id'],
								'email_address'		=>	$this->memberData['email'] );
			
			/* Send it */
			$this->api_server->auth_user = $converge['converge_http_user'];
			$this->api_server->auth_pass = $converge['converge_http_pass'];		
			$this->api_server->apiSendRequest( $converge['converge_url'] . '/converge_master/converge_server.php', 'convergeLogOut', $request );
		}
	}
	
	/**
	 * Create an account in IP.Converge
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	boolean		Request was successful
	 */
	public function createAccount( $member=array() )
	{
		if( !is_array( $member ) )
		{
			$this->return_code = 'FAIL';
			return false;
		}
		
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->DB->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( IPS_KERNEL_PATH . 'classApiServer.php' );
			$this->api_server = new classApiServer();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $member['email'],
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->apiSendRequest( $url, 'convergeCheckEmail', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		if( $this->api_server->params['response'] == 'EMAIL_NOT_IN_USE' )
		{
			$request = array( 'auth_key'			=> $converge['converge_api_code'],
							  'product_id'			=> $converge['converge_product_id'],
							  'email_address'		=> $member['email'],
							  'md5_once_password'	=> md5( $member['password'] ),
							  'ip_address'			=> $member['ip_address'],
							  'unix_join_date'		=> $member['joined'],
							  'username'			=> $member['username'],
							);

			$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

			//-----------------------------------------
			// Send request
			//-----------------------------------------

			$this->api_server->apiSendRequest( $url, 'convergeAddMember', $request );

			//-----------------------------------------
			// Handle errors...
			//-----------------------------------------

			if ( count( $this->api_server->errors ) )
			{
				$this->return_details 	= implode( '<br />', $this->api_server->errors );
				$this->return_code 		= $this->api_server->params['response'];
				return false;
			}
		}
		
		$this->return_code = $this->api_server->params['response'];
		return true;
	}
}