<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member form plugin interface
 * Last Updated: $Date: 2009-02-04 18:12:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		IP.Board 3.0
 * @version		$Revision: 3890 $
 */

interface admin_member_form
{
	/**
	 * Returns sidebar links for a tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the sidebar for this block.
	 *
	 * The links must have 'section=xxxxx&module=xxxxx[&do=xxxxxx]'. The rest of the URL
	 * is added automatically. member_id will contain the Member ID
	 *
	 * The image must be a full URL or blank to use a default image.
	 *
	 * Use the format:
	 * $array[] = array( 'img' => '', 'url' => '', 'title' => '' );
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	array 			Member data
	 * @return	array 			Array of links
	 */
	public function getSidebarLinks();
	
	/**
	 * Returns HTML tab content for the page.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	array 				Member data
	 * @return	array 				Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content)
	 */
	public function getDisplayContent();
	
	/**
	 * Process the entries for saving and return
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	array 				Multi-dimensional array (core, extendedProfile) for saving
	 */
	public function getForSave();
}