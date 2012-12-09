<?php

/**
 * Invision Power Services
 * RSS output plugin :: ccs
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_ccs
{
	/**
	 * Expiration date
	 *
	 * @access	private
	 * @var		integer			Expiration timestamp
	 */
	private $expires			= 0;

	/**
	 * Grab the RSS links
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function getRssLinks()
	{		
		$return	= array();

	    return $return;
	}
	
	/**
	 * Grab the RSS document content and return it
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{
		return '';
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 *
	 * @access	public
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		// Generated on the fly, so just return expiry of one hour
		return time() + 3600;
	}
}