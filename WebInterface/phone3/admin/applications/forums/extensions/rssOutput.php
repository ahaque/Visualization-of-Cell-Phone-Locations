<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * RSS output plugin :: posts
 * Last Updated: $Date: 2009-07-09 03:49:55 -0400 (Thu, 09 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		6/24/2008
 * @version		$Revision: 4857 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_forums
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
	* @return	array		RSS links
	*/
	public function getRssLinks()
	{
		$return			= array();

		ipsRegistry::DB()->build( array( 'select' => 'rss_export_title, rss_export_id', 'from' => 'rss_export', 'where' => 'rss_export_enabled=1' ) );
		ipsRegistry::DB()->execute();

		while( $r = ipsRegistry::DB()->fetch() )
		{
	        $return[] = array( 'title' => $r['rss_export_title'], 'url' => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=forums&amp;id=" . $r['rss_export_id'], '%%' . $r['rss_export_title'] . '%%', 'section=rss2' ) );
	    }

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
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rss_export_id	= intval( ipsRegistry::$request['id'] );
		$rss_data		= array();
		$to_print		= '';
		$this->expires	= time();
		
		//-----------------------------------------
		// Get RSS export
		//-----------------------------------------
		
		$rss_data = ipsRegistry::DB()->buildAndFetch( array( 'select'	=> '*',
															'from'		=> 'rss_export',
															'where'		=> 'rss_export_id=' . $rss_export_id ) );
		
		//-----------------------------------------
		// Got one?
		//-----------------------------------------
		
		if ( $rss_data['rss_export_id'] AND $rss_data['rss_export_enabled'] )
		{
			//-----------------------------------------
			// Correct expires time
			//-----------------------------------------
			
			$this->expires += $rss_data['rss_export_cache_time'] * 60;
			
			//-----------------------------------------
			// Need to recache?
			//-----------------------------------------
			
			$time_check = time() - ( $rss_data['rss_export_cache_time'] * 60 );
			
			if ( ( ! $rss_data['rss_export_cache_content'] ) OR $time_check > $rss_data['rss_export_cache_last'] )
			{
				//-----------------------------------------
				// Yes
				//-----------------------------------------
				
				define( 'IN_ACP', 1 );
				
				require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
				$app_class_forums		= new app_class_forums( ipsRegistry::instance() );
				
				require_once( IPSLib::getAppDir( 'forums' ) . '/modules_admin/rss/export.php' );
				$rss_export		   =  new admin_forums_rss_export();
				$rss_export->makeRegistryShortcuts( ipsRegistry::instance() );
				
				
				return $rss_export->rssExportRebuildCache( $rss_data['rss_export_id'], 0 );
			}
			else
			{
				//-----------------------------------------
				// No
				//-----------------------------------------
				
				return $rss_data['rss_export_cache_content'];
			}
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
		return $this->expires ? $this->expires : time();
	}
}