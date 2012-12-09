<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * RSS output plugin :: report center
 * Last Updated: $Date: 2009-03-12 13:47:14 -0400 (Thu, 12 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		6/24/2008
 * @version		$Revision: 4206 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_core
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
	* @return	array
	*/
	public function getRssLinks()
	{
		//-----------------------------------------
		// As this is member specific, hardcoded
		// into output library
		//-----------------------------------------
		
		return array();
	}
	
	/**
	* Grab the RSS document content and return it
	*
	* @access	public
	* @return	string		RSS document
	*/
	public function returnRSSDocument()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( ipsRegistry::$request['member_id'] );
		$secure_key		= IPSText::md5Clean( ipsRegistry::$request['rss_key'] );
		$rss_data		= array();
		$to_print		= '';
		
		if( $secure_key and $member_id )
		{
			if( $member_id == ipsRegistry::member()->getProperty('member_id') )
			{
				//-----------------------------------------
				// Get RSS export
				//-----------------------------------------
				
				$rss_data = ipsRegistry::DB()->buildAndFetch( array( 'select'	=> 'rss_cache',
																	'from'		=> 'rc_modpref',
																	'where'		=> "mem_id=" . $member_id . " AND rss_key='" . $secure_key . "'" ) );
				
				//-----------------------------------------
				// Got one?
				//-----------------------------------------
				
				if ( $rss_data['rss_cache'] )
				{
					return $rss_data['rss_cache'];
				}
			}

			//-----------------------------------------
			// Create a dummy one
			//-----------------------------------------
			
			ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_reports' ), 'core' );
			
			require_once( IPS_KERNEL_PATH . 'classRss.php' );
			$rss			=  new classRss();
			$channel_id = $rss->createNewChannel( array( 'title'			=> ipsRegistry::getClass('class_localization')->words['rss_feed_title'],
															'link'			=> ipsRegistry::$settings['board_url'],
															'description'	=> ipsRegistry::getClass('class_localization')->words['reports_rss_desc'],
															'pubDate'		=> $rss->formatDate( time() )
												)		);
			$rss->createRssDocument();
			
			return $rss->rss_document;
		}
	}
	
	/**
	* Grab the RSS document expiration timestamp
	*
	* @access	public
	* @return	integer		Expiration timestamp
	*/
	public function grabExpiryDate()
	{
		return time() + 3600;
	}
}