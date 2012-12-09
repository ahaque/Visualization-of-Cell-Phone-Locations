<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Core extensions
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class publicSessions__members
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
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$array = array( 'location_1_type'   => '',
						'location_1_id'     => 0,
						'location_2_type'   => '',
						'location_2_id'     => 0 );
		
		if( ipsRegistry::$request['id'] AND ipsRegistry::$request['section'] == 'view' AND ipsRegistry::$request['module'] == 'profile' )
		{
			$array['location_1_type']	= 'profile';
			$array['location_1_id']		= ipsRegistry::$request['id'];
		}

		return $array;
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
		
		$final		= array();
		$profiles	= array();
		$names		= array();
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'members' OR !$row['current_module'] )
			{
				continue;
			}

			if( $row['current_module'] == 'profile' )
			{
				$profiles[]				= $row['location_1_id'];
			}
		}

		if( count($profiles) )
		{
			ipsRegistry::DB()->build( array( 'select' => 'member_id, members_display_name, members_seo_name', 'from' => 'members', 'where' => 'member_id IN(' . implode( ',', $profiles ) . ')' ) );
			$pr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($pr) )
			{
				$names[ $r['member_id'] ] = array( 'members_display_name' => $r['members_display_name'], 'members_seo_name' => $r['members_seo_name'] );
			}
		}

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'members' )
			{
				if( $row['current_module'] == 'online' )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_online'];
				}
				
				if( $row['current_module'] == 'list' )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_members'];
				}
				
				if( $row['current_module'] == 'profile' )
				{
					if ( isset( $names[ $row['location_1_id'] ] ) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_profile'];
						$row['where_line_more']	= $names[ $row['location_1_id'] ]['members_display_name'];
						$row['where_link']		= 'showuser=' . $row['location_1_id'];
						$row['_whereLinkSeo']   = ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $names[ $row['location_1_id'] ]['members_seo_name'], 'showuser' );
					}
				}
			}
			
			$final[ $row['id'] ]	= $row;
		}
		
		return $final;
	}
}
