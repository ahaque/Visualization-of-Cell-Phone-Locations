<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Wrapper for interfacing with stopforumspam.com
 * Class written by Matt Mecham
 * Last Updated: $Date: 2009-02-04 20:05:25 +0000 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Tuesday 22nd February 2005 (16:55)
 * @version		$Revision: 222 $
 */
 
if ( ! defined( 'IPS_KERNEL_PATH' ) )
{
	/**
	 * Define classes path
	 */
	define( 'IPS_KERNEL_PATH', dirname(__FILE__) );
}

class classStopForumSpam
{
	/**
	 * Minimum frequency to check for
	 *
	 * @access	private
	 * @var		int
	 */
	private $_minF = 3;
	
	/**
	 * XML object
	 *
	 * @access	private
	 * @var		object
	 */
	private $_xml = null;
	
	/**
	 * ClassFileManagement object
	 *
	 * @access	private
	 * @var		object
	 */
	private $_cfm = null;
	
	/**
	 * Load XML and classFileManagement libraries
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct()
	{
		/* CFM */
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );
		$this->_cfm = new classFileManagement();
		
		/* XML */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$this->_xml = new classXML('utf-8');
	}
	
	/**
	 * Set a frequency
	 *
	 * @access	public
	 * @param	int
	 * @return 	void
	 */
	public function setFrequency( $f )
	{
		$this->_minF = $f;
	}
	
	/**
	 * Check to see if IP address is blacklisted
	 *
	 * @access	public
	 * @param	string		IP Address
	 * @return	boolean		TRUE = blacklisted, FALSE = clean
	 */
	public function checkForIpAddress( $ip )
	{
		$result = $this->_cfm->getFileContents( "http://www.stopforumspam.com/api?ip=" . $ip );

		/* We're only interested in this, currently */
		if ( $result )
		{
			$this->_xml->loadXML( $result );
			
			foreach( $this->_xml->fetchElements( 'response' ) as $_el )
			{
				$data = $this->_xml->fetchElementsFromRecord( $_el );
			
				if ( $data['appears'] AND intval( $data['frequency'] ) > $this->_minF )
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Check to see if email address is blacklisted
	 *
	 * @access	public
	 * @param	string		Email address
	 * @return	boolean		TRUE = blacklisted, FALSE = clean
	 */
	public function checkForEmailAddress( $email )
	{
		$result = $this->_cfm->getFileContents( "http://www.stopforumspam.com/api?email=" . $email );

		/* We're only interested in this, currently */
		if ( $result )
		{
			$this->_xml->loadXML( $result );
			
			foreach( $this->_xml->fetchElements( 'response' ) as $_el )
			{
				$data = $this->_xml->fetchElementsFromRecord( $_el );
			
				if ( $data['appears'] AND intval( $data['frequency'] ) > $this->_minF )
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
}