<?php
/**
 * Formats calendar search results
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @version		$Rev: 4948 $ 
 **/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class calendarSearchDisplay implements iSearchDisplay
{
	/**
	 * The search plugin for this app
	 *
	 * @access	public
	 * @var		object
	 */
	public $search_plugin;

	/**
	 * Formats the calendar search result for display
	 *
	 * @access	public
	 * @param	array	$search_row	Array of data from search_index
	 * @return	string				Formatted content, ready for display
	 */	
	public function formatContent( $search_row )
	{
		/* Format as a topic */
		if( $search_row['misc'] )
		{
			return ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->calEventRangedSearchResult( $search_row, $this->search_plugin->onlyTitles );
		}
		/* Format as a forum */
		else
		{
			return ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->calEventSearchResult( $search_row, $this->search_plugin->onlyTitles );
		}
	}

	/**
	 * Retuns the html for displaying the calendar filter on the advanced search page
	 *
	 * @access	public
	 * @return	string
	 */
	public function getFilterHTML()
	{
		return '';
		//return ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->forumAdvancedSearchFilters();
	}
	
	/**
	 * Builds the SQL filter query
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