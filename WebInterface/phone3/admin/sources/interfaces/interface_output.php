<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Output formats interface
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		MattMecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		Wednesday 28th May 2008 16:42 GMT
 * @version		$Revision: 3887 $
 *
 */

interface interface_output
{
	/**
	 * Prints any header information for this output module
	 *
	 * @access	public
	 * @return	void		Prints header() information
	 */
	public function printHeader();
	
	/**
	 * Fetches the output
	 *
	 * @access	public
	 * @param	string		Output gathered
	 * @param	string		Title of the document
	 * @param	array 		Navigation gathered
	 * @param	array 		Array of document head items
	 * @param	array 		Array of JS loader items
	 * @param	array 		Array of extra data
	 * @return	string		Output to be printed to the client
	 */
	public function fetchOutput( $output, $title, $navigation, $documentHeadItems, $jsLoaderItems, $extraData=array() );
	
	/**
	 * Finish / clean up after sending output
	 *
	 * @access	public
	 * @return	null
	 */
	public function finishUp();
	
	/**
	 * Adds more items into the document header like CSS / RSS, etc
	 *
	 * @access	public
	 * @return   null
	 */
	public function addHeadItems();
	
	/**
	 * Replace IPS tags
	 * Converts over <#IMG_DIR#>, etc
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function parseIPSTags( $text );
	
	/**
	 * Silent redirect (Redirects without a screen or other notification)
	 *
	 * @access	public
	 * @param	URL
	 * @return	mixed
	 */
	public function silentRedirect( $url );
	
}