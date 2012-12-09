<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Library: Handle public session data
 * Last Updated: $Date: 2009-08-10 17:08:53 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Portal
 * @link		http://www.
 * @since		12th March 2002
 * @version		$Revision: 5009 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class publicSessions__portal
{
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	array
	 */
	public function getSessionVariables()
	{
		return array();
	}
	
	
	/**
	 * Parse/format the online list data for the records
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	array 			Online list rows to check against
	 * @return	array 			Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}

		$final = array();
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'portal' )
			{
				$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_portal'];
			}
			
			$final[ $row['id'] ] = $row;
		}

		return $final;
	}
}