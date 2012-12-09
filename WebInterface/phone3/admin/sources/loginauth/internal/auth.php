<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : Internal Method
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_internal extends login_core implements interface_login
{
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
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
		if ( ( !$username AND !$email_address ) OR !$password )
		{
			$this->return_code	= 'MISSING_DATA';
			return false;
		}
		
		return $this->authLocal( $username, $email_address, $password );
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
		$email_check = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "email='".$email."'" ) );
		
		$this->return_code = $email_check['member_id'] ? 'EMAIL_IN_USE' : 'EMAIL_NOT_IN_USE';
		return true;
	}
}