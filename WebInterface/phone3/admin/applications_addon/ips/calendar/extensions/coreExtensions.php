<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Calendar core extensions
 * Last Updated: $LastChangedDate: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class calendarPermMappingCalendar
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	private
	 * @var		array
	 */
	private $mapping = array(
								'view'     => 'perm_view',
								'start'    => 'perm_2',
								'nomod'    => 'perm_3',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	private
	 * @var		array
	 */
	private $perm_names = array(
								'view'     => 'Show Calendar',
								'start'    => 'Create Events',
								'nomod'    => 'Bypass Moderation',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	private
	 * @var		array
	 */
	private $perm_colors = array(
								'view'     => '#fff0f2',
								'start'    => '#effff6',
								'nomod'    => '#edfaff',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}
}

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Library: Handle public session data
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @since		12th March 2002
 * @version		$Revision: 4948 $
 *
 */

class publicSessions__calendar
{
	/**
	* Return session variables for this application
	*
	* current_appcomponent, current_module and current_section are automatically
	* stored. This function allows you to add specific variables in.
	*
	* @access	public
	* @author	Matt Mecham
	* @return   array
	* @todo 	[Future] Store which event or calendar a user is viewing?
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
	* @return   array 			Online list rows parsed
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
			if( $row['current_appcomponent'] == 'calendar' )
			{
				$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_calendar'];
				$row['where_link']		= 'app=calendar&amp;module=calendar&amp;section=calendars';
			}
			
			$final[ $row['id'] ] = $row;
		}

		return $final;
	}
}