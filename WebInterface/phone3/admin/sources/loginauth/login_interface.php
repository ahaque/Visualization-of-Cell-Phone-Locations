<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 3887 $
 *
 */

interface interface_login
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() );

	/**
	 * Authenticate via local database
	 *
	 * @param	string		Username [Username or Email Address must be supplied]
	 * @param	string		Email Address [Username or Email Address must be supplied]
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authLocal( $username, $email_address, $password );
	
	/**
	 * Create a record of the user locally
	 *
	 * @param	array 		Member information
	 * @return	void
	 */
	public function createLocalMember( $member );
	
	/**
	 * Normal authentication routine for the login method
	 *
	 * @param	string		Username  [Username or Email Address must be supplied]
	 * @param	string		Email Address  [Username or Email Address must be supplied]
	 * @param	string		Password
	 * @return	boolean
	 */
	public function authenticate( $username, $email_address, $password );
}
