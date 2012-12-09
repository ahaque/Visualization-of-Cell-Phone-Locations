<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Converge Handler
 * Last Updated: $Date: 2009-08-10 17:08:53 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	IP.Converge
 * @since		2.1.0
 * @version		$Revision: 5009 $
 *
 */

class Converge_Server
{
   /**
    * Defines the service for WSDL
    *
    * @access	public
    * @var 		array
    */			
	public $__dispatch_map		= array();
	
   /**
    * Global registry
    *
    * @access 	private
    * @var 		object
    */
	private $registry;
	
	/**
	* IPS API SERVER Class
	*
    * @access	public
    * @var 		object
    */
	public $classApiServer;
	
	/**
	 * CONSTRUCTOR
	 * 
	 * @access	public
	 * @return 	void
	 **/		
	public function __construct( $registry )
    {
		//-----------------------------------------
		// Set IPS CLASS
		//-----------------------------------------
		
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->member   =  $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->settings =& $registry->fetchSettings();
		
    	//-----------------------------------------
    	// Load allowed methods and build dispatch
		// list
    	//-----------------------------------------
    	
		require_once( DOC_IPS_ROOT_PATH . 'converge_local/apis/allowed_methods.php' );
		
		if ( is_array( $_CONVERGE_ALLOWED_METHODS ) and count( $_CONVERGE_ALLOWED_METHODS ) )
		{
			foreach( $_CONVERGE_ALLOWED_METHODS as $_method => $_data )
			{
				$this->__dispatch_map[ $_method ] = $_data;
			}
		}
	}
	
	/**
	 * Converge_Server::requestData()
	 * Returns extra data from this application
	 * EACH BATCH MUST BE ORDERED BY ID ASC (low to high)
	 * 
	 * @access	public
	 * @param	string	$auth_key		Authentication Key
	 * @param	int		$product_id		Product ID
	 * @param	int		$limit_a		SQL limit a
	 * @param	int		$limit_b		SQL limit b
	 * @return	mixed	xml / boolean
	 **/	
	public function requestData( $auth_key, $product_id, $email_address, $getdata_key )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key      = IPSText::md5Clean( $auth_key );
		$product_id    = intval( $product_id );
		$email_address = IPSText::parseCleanValue( $email_address );
		$getdata_key   = IPSText::parseCleanValue( $getdata_key );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Grab local extension file
			//-----------------------------------------
			
			require_once( DOC_IPS_ROOT_PATH  . 'converge_local/apis/local_extension.php' );
			$extension = new local_extension( $this->registry );
			
			if ( is_callable( array( $extension, $getdata_key ) ) )
			{
				$data = @call_user_func( array( $extension, $getdata_key), $email_address );
			}
			
			$return = array( 'data' => base64_encode( serialize( $data ) ) );
			
			# return complex data
			$this->classApiServer->apiSendReply( $return );
			exit();
		}
	}
	
	/**
	 * Converge_Server::onMemberDelete()
	 *
	 * Deletes the member.
	 * Keep in mind that the member may not be in the local DB
	 * if they've not yet visited this site.
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * @access	public
	 * @param	int		$product_id					Product ID
	 * @param	string	$auth_key					Authentication Key
	 * @param	string	$multiple_email_addresses	Comma delimited list of email addresses
	 * @return	mixed	xml / boolean
	 **/	
	public function onMemberDelete( $auth_key, $product_id, $multiple_email_addresses='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return     = 'FAILED';
		$emails     = explode( ",", $this->DB->addSlashes( IPSText::parseCleanValue( $multiple_email_addresses ) ) );
		$member_ids = array();
		$auth_key   = IPSText::md5Clean( $auth_key );
		$product_id = intval( $product_id );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get member IDs
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => 'member_id',
													 'from'   => 'members',
													 'where'  => "email IN ('" . implode( "','", $emails ) . "')" ) );
			
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$member_ids[ $row['member_id'] ] = $row['member_id'];
			}
			
			//-----------------------------------------
			// Remove the members
			//-----------------------------------------
			
			if ( count( $member_ids ) )
			{
				//-----------------------------------------
				// Get the member class
				//-----------------------------------------
				
				IPSMember::remove( $member_ids, false );
			}
			
			//-----------------------------------------
			// return
			//-----------------------------------------
			
			$return = 'SUCCESS';
		
			$this->classApiServer->apiSendReply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::onPasswordChange()
	 *
	 * handles new password change
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * @access	public
	 * @param	int		$product_id				Product ID
	 * @param	string	$auth_key				Authentication Key
	 * @param	string	$email_address			Email address
	 * @param	string	$md5_once_password		Plain text password hashed by MD5
	 * @return	mixed	xml / boolean
	 **/	
	public function onPasswordChange( $auth_key, $product_id, $email_address, $md5_once_password )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = IPSText::md5Clean( $auth_key );
		$product_id        = intval( $product_id );
		$email_address	   = IPSText::parseCleanValue( $email_address );
		$md5_once_password = IPSText::md5Clean( $md5_once_password );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			IPSMember::updatePassword( $email_address, $md5_once_password );

			$return = 'SUCCESS';
		
			$this->classApiServer->apiSendReply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::onEmailChange()
	 *
	 * Updates the local app's DB
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * @access	public
	 * @param	int		$product_id			Product ID
	 * @param	string	$auth_key 			Authentication Key
	 * @param	string	$old_email_address	Existing email address
	 * @param	string	$new_email_address	NEW email address to change
	 * @return	mixed	xml / boolean
	 **/	
	public function onEmailChange( $auth_key, $product_id, $old_email_address, $new_email_address )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = IPSText::md5Clean( $auth_key );
		$product_id        = intval( $product_id );
		$old_email_address = IPStext::parseCleanValue( $old_email_address );
		$new_email_address = IPStext::parseCleanValue( $new_email_address );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id',
																	'from'   => 'members',
																	'where'  => "email='" . $this->DB->addSlashes( $old_email_address ) . "'" ) );
																	
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			if ( $old_email_address AND $new_email_address AND $member['member_id'] )
			{
				$check = $this->DB->buildAndFetch( array( 'select'	=> 'member_id',
																				'from'	=> 'members',
																				'where'	=> "email='" . $this->DB->addSlashes( $new_email_address ) . "'" ) );

				if( !$check['member_id'] )
				{
					IPSMember::save( $old_email_address, array( 'core' => array( 'email' => $new_email_address ) ) );
					
					$return = 'SUCCESS';
				}
				else
				{
					$return = 'FAIL';
				}
			}
		
			$this->classApiServer->apiSendReply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}

	/**
	 * Converge_Server::onUsernameChange()
	 *
	 * Updates the local app's DB
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * @access	public
	 * @param	int		$product_id			Product ID
	 * @param	string	$auth_key 			Authentication Key
	 * @param	string	$old_username		Existing username
	 * @param	string	$new_username		NEW username to change
	 * @param	string	$auth				Email address
	 * @return	mixed	xml / boolean
	 **/	
	public function onUsernameChange( $auth_key, $product_id, $old_username, $new_username, $auth )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = IPSText::md5Clean( $auth_key );
		$product_id        = intval( $product_id );
		$email_address     = IPStext::parseCleanValue( $auth );
		$new_username      = IPStext::parseCleanValue( $new_username );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $auth );
														
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			if( $member['member_id'] )
			{
				if ( $old_email_address AND $new_email_address AND $member['member_id'] )
				{
					$check = IPSMember::load( $new_username, 'core', 'username' );
	
					if( !$check['member_id'] )
					{
						$lower = strtolower($new_username);
						
						IPSMember::save( $member['email'], array( 'core' => array(
															'name'						=> $new_username,
															'members_display_name'		=> $new_username,
															'members_seo_name'			=> IPSText::makeSeoTitle( $new_username ),
															'members_l_display_name'	=> $lower,
															'members_l_username'		=> $lower
										)						)				);  
						
						$return = 'SUCCESS';
					}
					else
					{
						$return = 'FAIL';
					}
				}
			}
		}
		
		$this->classApiServer->apiSendReply( array( 'complete'   => 1,
		 												'response'   => $return ) );
		exit();
	}
	
	/**
	 * Converge_Server::importMembers()
	 *
	 * Returns a batch of members to import
	 * Important!
	 * Each member row must return the following:
	 * - email_address
	 * - pass_salt (5 chr salt)
	 * - password  (md5 hash of: md5( md5( $salt ) . md5( $raw_pass ) );
	 * - ip_address (optional)
	 * - join_date (optional)
	 *
	 * EACH BATCH MUST BE ORDERED BY ID ASC (low to high)
	 * 
	 * @access	public
	 * @param	string	$auth_key		Authentication Key
	 * @param	int		$product_id		Product ID
	 * @param	int		$limit_a		SQL limit a
	 * @param	int		$limit_b		SQL limit b
	 * @return	mixed	xml / boolean
	 **/	
	public function importMembers( $auth_key, $product_id, $limit_a, $limit_b )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key   = IPSText::md5Clean( $auth_key );
		$product_id = intval( $product_id );
		$limit_a    = intval( $limit_a );
		$limit_b    = intval( $limit_b );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// INIT
			//-----------------------------------------
			
			$members = array();
			$done    = 0;
			
			//-----------------------------------------
			// Get Data
			//-----------------------------------------

			$this->DB->build( array( 'select' 	=> 'email, name, member_id, ip_address, members_pass_salt, members_pass_hash, joined',
									 'from'   	=> 'members',
									 'order'  	=> 'member_id ASC',
									 'limit'  	=> array( $limit_a, $limit_b ) ) );
													
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$members[ $row['member_id'] ] = array( 'email_address'	=> $row['email'],
													   'pass_salt'		=> $row['members_pass_salt'],
													   'password'		=> $row['members_pass_hash'],
													   'ip_address'		=> $row['ip_address'],
													   'username'		=> $row['name'],
													   'extra'			=> '',
													   'flag'			=> 0,
													   'join_date'		=> $row['joined'] );
			}
			
			if ( ! count( $members ) )
			{
				$done = 1;
			}
			
			$return = array( 'complete' => $done,
							 'members'  => $members );
			
			# return complex data
			$this->classApiServer->apiSendReply( $return, 1 );
			exit();
		}
	}
	
	/**
	 * Converge_Server::getMembersInfo()
	 *
	 * IP.Converge uses this to gather how many users the local application has,
	 * and the last ID entered into the local application’s member table.
	 *
	 * Expected repsonse:
	 * count   => The number of users
	 * last_id => The last ID
	 * 
	 * @access	public
	 * @param	string	$auth_key		Authentication Key
	 * @param	int		$product_id		Product ID
	 * @return	mixed	xml / boolean
	 **/	
	public function getMembersInfo( $auth_key, $product_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key   = IPSText::md5Clean( $auth_key );
		$product_id = intval( $product_id );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get Data
			//-----------------------------------------
			
			$member_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
															'from'   => 'members' ) );
																			  
			$member_last  = $this->DB->buildAndFetch( array( 'select' => 'MAX(member_id) as max',
															 'from'   => 'members' ) );
			

			$this->classApiServer->apiSendReply( array( 'count'   => intval( $member_count['count'] ),
			 											'last_id' => intval( $member_last['max'] ) ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::convergeLogOut()
	 *
	 * Logs in the member out of local application
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * @access	public
	 * @param	string	$auth_key		Authentication Key
	 * @param	int		$product_id		Product ID
	 * @param	string	$email_address	Email address of user logged in
	 * @return	mixed	xml / boolean
	 **/
	public function convergeLogOut( $auth_key, $product_id, $email_address='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key      = IPSText::md5Clean( $auth_key );
		$product_id    = intval( $product_id );
		$email_address = IPSText::parseCleanValue( $email_address );
		$update        = array();
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get member
			//-----------------------------------------
			
			$member = IPSMember::load( $email_address );
			
			//-----------------------------------------
			// If we've got a member, delete their session
			// and change the log in key so that the members
			// auto-log in cookies won't work.
			//-----------------------------------------
			
			if ( $member['member_id'] )
			{
				$update['member_login_key'] = IPSMember::generateAutoLoginKey();
				$update['login_anonymous']  = '0&0';
				$update['last_visit']       = time();
				$update['last_activity']    = time();
				
				IPSMember::save( $member['member_id'], array( 'core' => $update ) );
				
				//-----------------------------------------
				// Delete session
				//-----------------------------------------
				
				$this->DB->delete( 'sessions', 'member_id=' . $member['member_id'] );
			}
			
			//-----------------------------------------
			// Add cookies
			//-----------------------------------------
			
			$this->classApiServer->apiAddCookieData( array( 'name'   => $this->settings['cookie_id'] . 'member_id',
														  	'value'  => 0,
														  	'path'   => $this->settings['cookie_path'],
														  	'domain' => $this->settings['cookie_domain'],
														    'sticky' => 1 ) );
														
			$this->classApiServer->apiAddCookieData( array( 'name'   => $this->settings['cookie_id'] . 'pass_hash',
														  	'value'  => 0,
														  	'path'   => $this->settings['cookie_path'],
														  	'domain' => $this->settings['cookie_domain'],
														  	'sticky' => 1 ) );
														
			$this->classApiServer->apiAddCookieData( array( 'name'   => $this->settings['cookie_id'] . 'session_id',
														  	'value'  => 0,
														  	'path'   => $this->settings['cookie_path'],
														  	'domain' => $this->settings['cookie_domain'],
														  	'sticky' => 0 ) );
														
			$this->classApiServer->apiSendReply( array( 'complete'   => 1,
			 											'response'   => 'SUCCESS' ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::convergeLogIn()
	 *
	 * Logs in the member to the local application
	 *
	 * This must return
	 * - complete   [ All done.. ]
	 * - session_id [ Session ID created ]*
	 * - member_id  [ Member's log in ID / email ]
	 * - log_in_key [ Member's log in key or password ]
	 * -- RESPONSE
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * The session key and password/log in key will be posted to
	 * this apps handshake API so that the app can return cookies.
	 *
	 * @access	public
	 * @param	int		$product_id			Product ID
	 * @param	string	$auth_key			Authentication Key
	 * @param	string	$email_address		Email address of user logged in
	 * @param	string	$md5_once_password	The plain text password, hashed once
	 * @param	string	$ip_address			IP Address of registree
	 * @param	string	$unix_join_date		The member's join date in unix format
	 * @param	string	$timezone			The member's timezone
	 * @param	string	$dst_autocorrect	The member's DST autocorrect settings
	 * @param	mixed	$extra_data			Extra member account data
	 * @param	string	$username			Member's user name
	 * @return	mixed	xml / boolean
	 **/
	public function convergeLogIn( $auth_key, $product_id, $email_address='', $md5_once_password='', $ip_address='', $unix_join_date='', $timezone=0, $dst_autocorrect=0, $extra_data='', $username='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = IPSText::md5Clean( $auth_key );
		$product_id        = intval( $product_id );
		$email_address     = IPSText::parseCleanValue( $email_address );
		$username		   = IPSText::parseCleanValue( $username );
		$md5_once_password = IPSText::md5Clean( $md5_once_password );
		$ip_address        = IPSText::parseCleanValue( $ip_address );
		$unix_join_date    = intval( $unix_join_date );
		$timezone          = intval( $timezone );
		$dst_autocorrect   = intval( $dst_autocorrect );
		$extra_data        = IPSText::parseCleanValue( $extra_data );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Extra data?
			//-----------------------------------------
			
			if ( $extra_data )
			{
				$external_data = unserialize( base64_decode( $extra_data ) );
			}
			
			//-----------------------------------------
			// Get member
			//-----------------------------------------
			
			$member = IPSMember::load( $email_address );
			$this->registry->member()->setMember( $member['member_id'] );
			
			//-----------------------------------------
			// No such user? Create one!
			// FAIL SAFE
			//-----------------------------------------
			
			if ( ! $this->memberData['member_id'] )
			{ 
				$unix_join_date    = $unix_join_date    ? $unix_join_date    : time();
				$md5_once_password = $md5_once_password ? $md5_once_password : md5( $email_address . $unix_join_date . uniqid( microtime() ) );
				$ip_address        = $ip_address        ? $ip_address        : '127.0.0.1';
				
				$this->registry->member()->setMember( $this->__create_user_account( $email_address, $md5_once_password, $ip_address, $unix_join_date, $timezone, $dst_autocorrect, $username ) );
				$return = 'SUCCESS';
			}
			else
			{
				$return = 'SUCCESS';
			}

			//-----------------------------------------
			// Start session
			//-----------------------------------------
			
			$session = $this->__create_user_session( $this->memberData );

			//-----------------------------------------
			// Add cookies
			//-----------------------------------------
			
			$this->classApiServer->apiAddCookieData( array( 'name'   => $this->settings['cookie_id'] . 'member_id',
														  		 'value'  => $session['member_id'],
														  		 'path'   => $this->settings['cookie_path'],
														  		 'domain' => $this->settings['cookie_domain'],
														  		 'sticky' => 1 ) );
														
			$this->classApiServer->apiAddCookieData( array( 'name'   => $this->settings['cookie_id'] . 'pass_hash',
														  		 'value'  => $session['member_login_key'],
														  		 'path'   => $this->settings['cookie_path'],
														  		 'domain' => $this->settings['cookie_domain'],
														  		 'sticky' => 1 ) );
														
			$this->classApiServer->apiAddCookieData( array( 'name'   => $this->settings['cookie_id'] . 'session_id',
														  		 'value'  => $session['publicSessionID'],
														  		 'path'   => $this->settings['cookie_path'],
														  		 'domain' => $this->settings['cookie_domain'],
														  		 'sticky' => 0 ) );
			
			$this->classApiServer->apiSendReply( array( 'complete'   => 1,
															'response'   => $return,
			 												'session_id' => $session['publicSessionID'],
															'member_id'  => $session['member_id'],
			 												'log_in_key' => $session['member_login_key'] ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::__create_user_session()
	 *
	 * Has to return at least the member ID, member log in key and session ID
	 *
	 * @access	private
	 * @param	object	$member		Member object (can access as an array of member information thx to SPL)
	 * @return	array	$session	Session information
	 **/
	private function __create_user_session( $member )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$update = array();
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------

		if ( $this->settings['login_change_key'] OR ! $member['member_login_key'] )
		{
			$update['member_login_key'] = IPSMember::generateAutoLoginKey();
		}
		
		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$update['login_anonymous'] = '0&1';
		
		//-----------------------------------------
		// Update member?
		//-----------------------------------------
		
		if ( is_array( $update ) and count( $update ) )
		{
			IPSMember::save( $member['member_id'], array( 'core' => $update ) );
		}
		
		//-----------------------------------------
		// Still here? Create a new session
		//-----------------------------------------
		
		$this->registry->member()->setMember( $member['member_id'] );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/session/publicSessions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/session/convergeSessions.php' );
		$session           = new convergeSessions( $this->registry );
		$session->time_now = time();

		$update['publicSessionID']	= $session->createMemberSession();

		return array_merge( $this->memberData, $update );
	}
	
	/**
	 * Converge_Server::__create_user_account()
	 *
	 * Routine to create a local user account
	 *
	 * @access	public
	 * @param	string	$email_address		Email address of user logged in
	 * @param	string	$md5_once_password	The plain text password, hashed once
	 * @param	string	$ip_address			IP Address of registree
	 * @param	string	$unix_join_date		The member's join date in unix format
	 * @param	string	$timezone			The member's timezone
	 * @param	string	$dst_autocorrect	The member's DST autocorrect settings
	 * @param	string	$username			The member's username
	 * @return	array	$member				Newly created member array
	 **/
	private function __create_user_account( $email_address='', $md5_once_password, $ip_address, $unix_join_date, $timezone=0, $dst_autocorrect=0, $username='' )
	{
		//-----------------------------------------
		// Check to make sure there's not already
		// a member registered.
		//-----------------------------------------
		
		$member = IPSMember::load( $email_address );
		
		if ( $member['member_id'] )
		{
			return $member;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$unix_join_date = $unix_join_date ? $unix_join_date : time();
		$ip_address     = $ip_address     ? $ip_address     : $this->member->ip_address;

		//-----------------------------------------
		// Create member
		//-----------------------------------------

		$member = IPSMember::create( array( 'members' => array(  'email'					=> $email_address, 
																 'password'					=> $md5_once_password, 
																 'name'						=> $username,
																 'members_display_name'		=> $username,
																 'joined'					=> $unix_join_date, 
																 'ip_address'				=> $ip_address,
																 'members_created_remote'	=> true
									)		), false, true		);
		
		return $member['member_id'];
	}
	
	/**
	 * Converge_Server::__authenticate()
	 *
	 * Checks to see if the request is allowed
	 * 
	 * @access	private
	 * @param	string	$key			Authenticate Key
	 * @param	string	$product_id		Product ID
	 * @return	string         			Error message, if any
	 **/	
	private function __authenticate( $key, $product_id )
	{
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'api_langbits' ), 'core' );
		
		//-----------------------------------------
		// Check converge users API DB
		//-----------------------------------------
		
		$info = $this->DB->buildAndFetch( array( 'select' => '*',
																  'from'   => 'converge_local',
																  'where'  => "converge_product_id=" . intval($product_id) . " AND converge_active=1 AND converge_api_code='{$key}'" ) );
	
		//-----------------------------------------
		// Got a user?
		//-----------------------------------------
		
		if ( ! $info['converge_api_code'] )
		{
			$this->classApiServer->apiSendError( 100, $this->registry->getClass( 'class_localization' )->words['unauthorized_user'] );
			return FALSE;
		}
		else if ( 1 == 0 )// CVG_IP_MATCH AND ( my_getenv('REMOTE_ADDR') != $info['converge_ip_address'] ) )
		{
			$this->classApiServer->apiSendError( 101, $this->registry->getClass( 'class_localization' )->words['bad_ip_address'] );
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Request additional data from Converge
	 *
	 * @access	public
	 * @param	string	$auth_key		Authenticate Key
	 * @param	string	$product_id		Product ID
	 * @param	string	$gateway_key	Gateway key
	 * @param	mixed	$arg			Additional arguments
	 * @return	void
	 **/	
	public function requestAdditionalData( $auth_key, $product_id, $getdata_key, $arg )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key		= IPSText::md5Clean( $auth_key );
		$product_id		= intval( $product_id );
		$data			= IPSText::parseCleanValue( $data );
		$getdata_key	= IPSText::parseCleanValue( $getdata_key );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Grab local extension file
			//-----------------------------------------
			
			require_once( DOC_IPS_ROOT_PATH  . 'converge_local/apis/additional_methods.php' );
			$extension = new additional_methods( $this->registry );
			
			if ( is_callable( array( $extension, $getdata_key ) ) )
			{
				$data = @call_user_func( array( $extension, $getdata_key), $arg );
			}
			
			$return	= array( 'data' => base64_encode( serialize( $data ) ) );
			
			# return complex data
			$this->classApiServer->apiSendReply( $return );
			exit();
		}
	}

}