<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Group editing form interface
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		June 10 2008
 * @version		$Revision: 3887 $
 */

interface admin_group_form
{
	/**
	 * Returns HTML tab content for the page.
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param    array 				Group data
	 * @param	integer				Number of tabs used so far (your ids should be this + 1)
	 * @return   array 				Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent();
	
	/**
	 * Process the entries for saving and return
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return   array 				Array of keys => values for saving
	 */
	public function getForSave();
}