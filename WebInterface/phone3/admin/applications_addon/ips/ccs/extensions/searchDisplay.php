<?php
/**
 * Formats pages search results
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Rev: 42 $ 
 **/

class ccsSearchDisplay implements iSearchDisplay
{
	/**
	 * The search plugin for this app
	 *
	 * @access	public
	 * @var		object
	 */
	public $search_plugin;

	/**
	 * Formats the search result for display
	 *
	 * @access	public
	 * @param	array	$search_row	Array of data from search_index
	 * @return	string				Formatted content, ready for display
	 */	
	public function formatContent( $search_row )
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		$registry	= ipsRegistry::instance();
		
		require_once( IPSLib::getAppDir('ccs') . '/sources/functions.php' );
		$registry->setClass( 'ccsFunctions', new ccsFunctions( $registry ) );

		$search_row['formatted_url']	= $registry->ccsFunctions->returnPageUrl( $search_row );
		
		$search_row['content']	= preg_replace( "#{parse block=(.+?)}#", '', $search_row['content'] );
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		return ipsRegistry::getClass( 'output' )->getTemplate( 'ccs' )->pageSearchResult( $search_row, $this->search_plugin->onlyTitles );
	}
	
	/**
	 * Formats the search result for live search
	 *
	 * @access	public
	 * @param	array	$search_row	Array of data from search_index
	 * @return	string				Formatted content, ready for display
	 */	
	public function formatLiveSearchContent( $search_row )
	{

	}	
	
	/**
	 * Retuns the html for displaying the forum category filter on the advanced search page
	 *
	 * @access	public
	 * @return	string				Formatted content, ready for display
	 */	
	public function getFilterHTML()
	{
		return '';
	}
	
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data		Array of categories to view
	 * @return	array 				Array with column, operator, and value keys, for use in the setCondition call
	 **/
	public function buildFilterSQL( $data )
	{
		return '';
	}
}