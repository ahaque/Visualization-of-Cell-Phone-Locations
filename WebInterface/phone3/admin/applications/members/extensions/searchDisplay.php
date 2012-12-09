<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Format member search results
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Rev: 4948 $ 
 **/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class membersSearchDisplay implements iSearchDisplay
{
	/**
	 * The search plugin for this app
	 *
	 * @access	public
	 * @var		object
	 */
	public $search_plugin;

	/**
	 * Formats the member search result for display
	 *
	 * @access	public
	 * @param	array	$search_row	Array of data from search_index
	 * @return	string				Formatted content, ready for display
	 */	
	public function formatContent( $search_row )
	{
		$search_row['misc'] = unserialize( $search_row['misc'] );
		
		return ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->memberSearchResult( $search_row, $this->search_plugin->onlyTitles );
	}

	/**
	 * Retuns the html for displaying the member filter on the advanced search page
	 *
	 * @access	public
	 * @return	string
	 */
	public function getFilterHTML()
	{
		return '';
	}
	
	/**
	 * Build filter SQL data
	 *
	 * @access	public
	 * @param	array 
	 * @return	array
	 */
	public function buildFilterSQL( $data )
	{
		return array();
	}
}