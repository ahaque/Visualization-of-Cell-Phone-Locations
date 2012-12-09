<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : OpenID Method
 * Last Updated: $Date: 2009-09-01 07:56:41 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 5068 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_openid extends login_core implements interface_login
{
	/**
	 * OpenID Consumer object
	 *
	 * @access	private
	 * @var		object
	 */
	private $consumer;
	
	/**
	 * OpenID Store object
	 *
	 * @access	private
	 * @var		object
	 */
	private $store;
	
	/**
	 * Temporary data store
	 *
	 * @access	private
	 * @var		array
	 */
	private $data_store	= array();
	
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
	/**
	 * OpenID configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $openid_config	= array();
	
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
		$this->openid_config	= $conf;
		
		parent::__construct( $registry );
		
		//-----------------------------------------
		// Fix include path for OpenID libs
		//-----------------------------------------
		
		$path_extra	= dirname( __FILE__ );
		$path		= ini_get( 'include_path' );
		$path		= $path_extra . PATH_SEPARATOR . $path;
		ini_set( 'include_path', $path );
		
		define( 'Auth_OpenID_RAND_SOURCE', null );
		
		//-----------------------------------------
		// OpenID libraries are not STRICT compliant
		//-----------------------------------------
		
		ob_start();
		
		/**
		 * Turn off strict error reporting for openid
		 */
		if( version_compare( PHP_VERSION, '5.2.0', '>=' ) )
		{
			error_reporting( E_ERROR | E_WARNING | E_PARSE | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING );
		}
		else
		{
			error_reporting( E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING );
		}

		//-----------------------------------------
		// And grab libs
		//-----------------------------------------
		 
		require_once "Auth/OpenID/Consumer.php";
		require_once "Auth/OpenID/FileStore.php";
		require_once "Auth/OpenID/SReg.php";
		require_once "Auth/OpenID/PAPE.php";
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
		// Set some OpenID stuff
		//-----------------------------------------
		
		$this->auth_errors = array();

		$pape_policy_uris = array(
					  PAPE_AUTH_MULTI_FACTOR_PHYSICAL,
					  PAPE_AUTH_MULTI_FACTOR,
					  PAPE_AUTH_PHISHING_RESISTANT
					  );
		
		session_start();

		//-----------------------------------------
		// OK?
		//-----------------------------------------
		
		if( !$this->request['firstpass'] )
		{
			$this->_doFirstPass();
		}
		else
		{
			$this->_checkFirstPass();
		}

		if ( count($this->auth_errors) )
		{
			$this->return_code = $this->return_code ? $this->return_code : 'NO_USER';
			return false;
		}

		if( !$this->data_store['email'] )
		{
			$this->return_code = 'NO_USER';
			return false;
		}

		$this->_loadMember( $this->data_store['fullurl'], $this->data_store['email'] );
			
		if ( $this->member_data['member_id'] )
		{
			$this->return_code = 'SUCCESS';
			
			if( strtolower(trim($this->data_store['email'])) != strtolower($this->member_data['email']) )
			{
				$check	= $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "email='" . strtolower(trim($this->data_store['email'])) . "'" ) );
				
				if( $check['member_id'] )
				{
					$this->data_store['email'] = $this->member_data['email'];
				}
			}

			//-----------------------------------------
			// Set Birthday fields if available
			//-----------------------------------------
			
			$dob		= trim($this->data_store['dob']);
			$bday_day	= 0;
			$bday_mon	= 0;
			$bday_year	= 0;
			
			if( $dob )
			{
				list( $bday_year, $bday_mon, $bday_day ) = explode( '-', $dob );
			}

			$core	= array(
							'email'						=> trim($this->data_store['email']),
							'bday_year'					=> $bday_year,
							'bday_month'				=> $bday_mon,
							'bday_day'					=> $bday_day,
							'identity_url'				=> $this->data_store['fullurl']
							);

			//-----------------------------------------
			// Update the display name and name, if not taken
			//-----------------------------------------
		
			try
			{
				if( IPSMember::getFunction()->checkNameExists( $this->data_store['nickname'], $this->member_data ) === false )
				{
					$core['members_display_name']	= trim($this->data_store['nickname']);
					$core['members_l_display_name']	= strtolower(trim($this->data_store['nickname']));
					
					//-----------------------------------------
					// If our display name is changing, store record
					//-----------------------------------------
					
					if( $core['members_display_name'] != $this->member_data['members_display_name'] )
					{
						$this->DB->insert( 'dnames_change', array(
																'dname_member_id'	=> $this->member_data['member_id'],
																'dname_date'		=> time(),
																'dname_ip_address'	=> $this->member->ip_address,
																'dname_previous'	=> $this->member_data['members_display_name'],
																'dname_current'		=> $core['members_display_name'],
										)						);
					}

					$this->member_data['members_display_name']		= trim($this->data_store['nickname']);
					$this->member_data['members_l_display_name']	= strtolower( trim($this->data_store['nickname']) );
				}
			}
			catch( Exception $e )
			{}
			
			try
			{
				if( IPSMember::getFunction()->checkNameExists( $this->data_store['nickname'], $this->member_data, 'name' ) === false )
				{
					$core['name']				= trim($this->data_store['nickname']);
					$core['members_l_username']	= strtolower(trim($this->data_store['nickname']));
					
					$this->member_data['name']						= trim($this->data_store['nickname']);
					$this->member_data['members_l_username']		= strtolower( trim($this->data_store['nickname']) );

				}
			}
			catch( Exception $e )
			{}
			
			IPSMember::save( $this->member_data['email'], array( 'core'	=> $core ) );
			
			$this->member_data['identity_url']				= $this->data_store['fullurl'];
			$this->member_data['email']						= trim($this->data_store['email']);
			$this->member_data['bday_year']					= $bday_year;
			$this->member_data['bday_month']				= $bday_month;
			$this->member_data['bday_day']					= $bday_day;
		}
		else
		{
			//-----------------------------------------
			// Set main fields
			//-----------------------------------------

			$email		= trim($this->data_store['email']);
			$name		= trim($this->data_store['nickname']);
			$dob		= trim($this->data_store['dob']);
			$timenow	= time();
			
			//-----------------------------------------
			// Set Birthday fields if available
			//-----------------------------------------
			
			$bday_day	= 0;
			$bday_mon	= 0;
			$bday_year	= 0;
			
			if( $dob )
			{
				list( $bday_year, $bday_mon, $bday_day ) = explode( '-', $dob );
			}
			
			if( strtolower(trim($this->data_store['email'])) != strtolower($this->member_data['email']) )
			{
				$check	= $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "email='" . strtolower(trim($this->data_store['email'])) . "'" ) );
				
				if( $check['member_id'] )
				{
					$email	= '';
				}
			}
			
			if( strtolower(trim($this->data_store['nickname'])) != $this->member_data['members_l_username'] )
			{
				$check	= $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_username='" . strtolower(trim($this->data_store['nickname'])) . "'" ) );
				
				if( $check['member_id'] )
				{
					$name	= '';
				}
			}
		
			$this->member_data = $this->createLocalMember( array(
															'members'			=> array(
																						 'email'					=> $email,
																						 'name'						=> $name,
																						 'members_l_username'		=> strtolower($name),
																						 'members_display_name'		=> $name,
																						 'members_l_display_name'	=> strtolower($name),
																						 'joined'					=> time(),
																						 'bday_day'					=> $bday_day,
																						 'bday_month'				=> $bday_mon,
																						 'bday_year'				=> $bday_year,
																						 'members_created_remote'	=> 1,
																						 'identity_url'				=> $this->data_store['fullurl'],
																						),
															'profile_portal'	=> array(
																						),
													)		);

			$this->return_code = 'SUCCESS';
		}

		if( $this->data_store['referrer'] )
		{
			$this->request['referer'] =  $this->data_store['referrer'] ;
		}
		
		if( $this->data_store['cookiedate'] )
		{
			$this->request['rememberMe'] =  $this->data_store['cookiedate'] ;
		}

		if( $this->data_store['privacy'] )
		{
			$this->request['anonymous'] =  $this->data_store['privacy'] ;
		}

		return $this->return_code;
	}
	
	/**
	 * Load a member from an identity url and then try by email
	 *
	 * @access	private
	 * @param	string 		Identity URL
	 * @param	string 		Email Address
	 * @return	void
	 */
	private function _loadMember( $url, $email )
	{
		$check = $this->DB->buildAndFetch( array( 'select'	=> 'email',
														  'from'	=> 'members',
														  'where'	=> "identity_url='" . $this->DB->addSlashes( $this->data_store['fullurl'] ) . "'"
												)		);

		if( $check['email'] )
		{
			$this->member_data = IPSMember::load( $check['email'], 'extendedProfile,groups' );
		}
		else
		{
			$this->member_data = array( 'member_id' => 0 ); //IPSMember::load( $email, 'extendedProfile,groups' );
		}
	}
	
	/**
	 * Perform first pass through login handler routine
	 *
	 * @access	private
	 * @return	mixed		Boolean on failure else output/redirect
	 */
	private function _doFirstPass()
	{
		//-----------------------------------------
		// Do the same cleaning we do when storing url
		//-----------------------------------------
		
		$url	= trim($this->request['openid_url']);
		$url	= rtrim( $url, "/" );
		
		if( !strpos( $url, 'http://' ) === 0 AND !strpos( $url, 'https://' ) === 0 )
		{
			$url = 'http://' . $url;
		}
		
		if( !IPSText::xssCheckUrl( $url ) )
		{
			$this->auth_errors[]	= 'bad_url';
			$this->return_code 		= 'WRONG_AUTH';
			return false;
		}

		$consumer = $this->_getConsumer();
		
    	if( !is_object($consumer) )
		{
			return false;
		}

		//-----------------------------------------
		// Store some of the input data..
		//-----------------------------------------
		
		$id = md5( uniqid( mt_rand(), true ) );
		
		$this->DB->delete( 'openid_temp', "fullurl='" . $url . "'" );
		
		$this->DB->insert( 'openid_temp', array( 'id'			=> $id,
													'referrer'		=> $this->request['referer'],
													'cookiedate'	=> intval($this->request['rememberMe']),
													'privacy'		=> intval($this->request['anonymous']),
													'fullurl'		=> $url,
							)					);
															 
		
		//-----------------------------------------
		// Set the URLs
		//-----------------------------------------
		
		$openid 		= $url;
		
		if( $this->is_admin_auth )
		{
			$process_url 	= $this->settings['base_url'] . 'app=core&module=login&do=login-complete&firstpass=1&myopenid=' . $id;
		}
		else
		{
			$process_url 	= $this->settings['base_url'] . 'app=core&module=global&section=login&do=process&firstpass=1&myopenid=' . $id;
		}
		
		$trust_root 	= strpos( $this->settings['base_url'], '.php' ) !== false ? substr( $this->settings['base_url'], 0, strpos( $this->settings['base_url'], '.php' ) + 4 ) : $this->settings['base_url'];
		$policy_url		= $this->openid_config['openid_policy'];

		//-----------------------------------------
		// Begin OpenID Auth
		//-----------------------------------------

		$auth_request = $consumer->begin($openid);

		if( !$auth_request ) 
		{
    		$this->return_code 		= 'WRONG_OPENID';
    		$this->auth_errors[]	= 'bad_request';
			return false;
		}

		//-----------------------------------------
		// Set required, optional, policy attribs
		//-----------------------------------------
		
	    $sreg_request = Auth_OpenID_SRegRequest::build(
					                                     // Required
					                                     explode(',', $this->openid_config['args_req']),
					                                     // Optional
					                                     explode(',', $this->openid_config['args_opt']),
					                                     // Policy URI
					                                     $policy_url
	                                     			);
	
	    if( $sreg_request ) 
	    {
	        $auth_request->addExtension($sreg_request);
	    }
	    
		//-----------------------------------------
		// Redirect user
		//-----------------------------------------

		$redirect_url = $auth_request->redirectURL( $trust_root, $process_url );
		
		if( $this->request['module'] == 'ajax' )
		{
			require_once( IPS_KERNEL_PATH . 'classAjax.php' );
			$ajax = new classAjax();
			$ajax->returnJsonArray( array( 'url' => $redirect_url ) );
		}
	
		// If the redirect URL can't be built, try HTML inline

		if( !Auth_OpenID::isFailure( $redirect_url ) ) 
		{
			 header( "Location: " . $redirect_url );
			 exit;
		}
		else
		{
			$form_id = 'openid_message';
			
			$form_html = $auth_request->formMarkup( $trust_root, $process_url, false, array( 'id' => $form_id ) );
	
			// Display an error if the form markup couldn't be generated;
			
			if( Auth_OpenID::isFailure($form_html) ) 
			{
				$this->return_code 		= 'WRONG_AUTH';
				$this->auth_errors[]	= 'bad_request';
				return false;
	        } 
	        else 
	        {
				$page_contents = array(
	               "<html><head><title>",
	               "OpenID transaction in progress",
	               "</title></head>",
	               "<body onload='document.getElementById(\"".$form_id."\").submit()'>",
	               $form_html,
	               "</body></html>");
	
	            print implode("\n", $page_contents);
	            exit;
	        }
	    }
	}
	
	/**
	 * Verify login and extract member data information
	 *
	 * @access	private
	 * @return	boolean
	 */
	private function _checkFirstPass()
	{
		//-----------------------------------------
		// Retrieve stored data
		//-----------------------------------------
		
		$id = IPSText::md5Clean( $this->request['myopenid'] );

		if( !$id )
		{
			$this->auth_errors[] 	= 'no_myopenid';
			$this->return_code 		= 'NO_USER';
			return false;
		}

		$this->data_store = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'openid_temp', 'where' => "id='{$id}'" ) );
		
		if( !$this->DB->getTotalRows() )
		{
			$this->auth_errors[] 	= 'no_myopenid';
			$this->return_code 		= 'NO_USER';
			return false;
		}
		
		$this->DB->delete( 'openid_temp', "id='{$id}'" );
		
		$consumer	= $this->_getConsumer();
		
		if( $this->is_admin_auth )
		{
			$return_to 	= $this->settings['base_url'] . 'app=core&module=login&do=login-complete&myopenid=' . $id;
		}
		else
		{
			$return_to 	= $this->settings['base_url'] . 'app=core&module=global&section=login&do=process&myopenid=' . $id;
		}
		
		$response	= $consumer->complete( $return_to );

    	if( $response->status == Auth_OpenID_CANCEL ) 
    	{
        	// This means the authentication was cancelled.

			$this->auth_errors[] 	= 'no_openid';
			$this->return_code 		= 'WRONG_OPENID';
			return false;        
		} 
		else if( $response->status == Auth_OpenID_FAILURE ) 
		{
        	// Authentication failed; display the error message.

			$this->auth_errors[] 	= 'no_openid';
			$this->return_code 		= 'WRONG_OPENID';
			return false;         
		} 
		else if( $response->status == Auth_OpenID_SUCCESS ) 
		{
	        // This means the authentication succeeded; extract the
	        // identity URL and Simple Registration data (if it was
	        // returned).
        
	        $openid		= $response->getDisplayIdentifier();
			$sreg_resp	= Auth_OpenID_SRegResponse::fromSuccessResponse( $response );
        	$sreg 		= $sreg_resp->contents();

        	if( is_array($sreg) and count($sreg) )
			{
				$this->data_store = array_merge( $this->data_store, $sreg );
			}
		}

   		session_unset();
   		
   		return true;
	}
	
	/**
	 * Grab the OpenID Store
	 *
	 * @access	private
	 * @return	mixed		False on failure, else an OpenID FileStore object
	 */
	private function _getStore()
	{
		if ( !is_dir($this->openid_config['store_path']) AND !mkdir($this->openid_config['store_path']) ) 
		{
			$this->auth_errors[] = 'bad_path';
			return false;
		}

		return new Auth_OpenID_FileStore( $this->openid_config['store_path'] );
	}
	
	/**
	 * Grab the OpenID Store
	 *
	 * @access	private
	 * @return	mixed		False on failure, else an OpenID Consumer object
	 */
	private function _getConsumer()
	{
		$store = $this->_getStore();
			
		if ( !is_object($store) ) 
		{
			$this->auth_errors[] = 'bad_path';
			return false;
		}

		return new Auth_OpenID_Consumer( $store );
	}
}