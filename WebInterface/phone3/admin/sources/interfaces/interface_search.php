<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Search Interfaces
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4440 $ 
 **/

interface iSearchRebuild
{
	/**
	 * Clears the search index for the app
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear();
	
	/**
	 * Function to handle rebuilding the search index
	 *
	 * @access	public
	 * @param	integer	$st		Position to start indexing
	 * @param	integer	$per_go	Number of entries to process
	 * @return	integer			Number of entries processed
	 */
	public function doRebuild( $st, $per_go );
}

interface iSearchDisplay
{
	/**
	 * Formats the search result for display
	 *
	 * @access	public
	 * @param	array	$search_row	Array of data from search_index
	 * @return	string				Formatted content, ready for display
	 */
	public function formatContent( $search_row );
	
	/**
	 * Retuns the html for displaying the filter box on the advanced search page
	 *
	 * @access	public
	 * @return	string
	 */
	public function getFilterHTML();
	
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array	$data	Array of data for filtering
	 * @return	array			Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data );
}

/* May end up making this an abstract class */
interface iSearchIndexPlugin
{
	/**
	 * Performs search and returns an array of results
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @param	array	$limit_clause	The param should be array( begin, end )
	 * @param	string	$sort_by		Either relevance or date
	 * @param	string	[$group_by]		Field to group on
	 * @param	bool	$content_title_only	Only search title records
	 * @return	array
	 */
	public function getSearchResults( $search_term, $limit_clause, $sort_by, $group_by='', $content_title_only=false );
	
	/**
	 * Performs live search and returns an array of results
	 *
	 * @access	public
	 * @param	string	$search_term 
	 * @return	array
	 */	
	public function getLiveSearchResults( $search_term );
	
	/**
	 * Returns the total number of results the search will return
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @param	string	$group_by
	 * @param	bool	$content_title_only
	 * @return	integer
	 */
	public function getSearchCount( $search_term, $group_by='', $content_title_only=false );
	
	/**
	 * Restrict the date range that the search is performed on
	 *
	 * @access	public
	 * @param	timestamp	$begin
	 * @param	timestamp	[$end]
	 * @return	void
	 */
	public function setDateRange( $begin, $end=0 );
	
	/**
	 * Generic function for adding special search conditions
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @param	string	$operator	Operation to perform for this condition, ex: =, <>, IN, NOT IN
	 * @param	mixed	$value		Value to check with
	 * @return	void
	 */
	public function setCondition( $column, $operator, $value );
	
	/**
	 * Reassigns fields in a way the index exepcts
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function formatFieldsForIndex( $r );
	
	/**
	 * This function grabs the actual results for display
	 *
	 * @param  array  $ids
	 * @return query result
	 **/
	public function getResultsForSphinx( $ids );	
}