<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member form manipulation
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_member_form__members implements admin_member_form
{

	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @access	public
	 * @var		string			Tab name
	 */
	public $tab_name = "";
	
	/**
	 * Returns sidebar links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the sidebar for this block.
	 *
	 * The links must have 'section=xxxxx&module=xxxxx[&do=xxxxxx]'. The rest of the URL
	 * is added automatically.
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
	public function getSidebarLinks( $member=array() )
	{
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_members' ), 'members' );

		$array = array();

		if( ipsRegistry::getClass('class_permissions')->checkPermission( 'membertools_delete_pms', 'members', 'members' ) )
		{
			$array[] = array( 'img'   => '', 
							  'url'   => 'section=tools&amp;module=members&amp;do=deleteMessages',
							  'title' => ipsRegistry::getClass('class_localization')->words['form_deletepms'] );
		}
		
		if( ipsRegistry::getClass('class_permissions')->checkPermission( 'membertools_ip', 'members', 'members' ) )
		{
			$array[] = array( 'img'   => '', 
							  'url'   => 'section=tools&amp;module=members&amp;do=show_all_ips',
							  'title' => ipsRegistry::getClass('class_localization')->words['form_showallips'] );
		}

		if( ipsRegistry::getClass('class_permissions')->checkPermission( 'member_suspend', 'members', 'members' ) )
		{
			if( $member['temp_ban'] )
			{
				$array[] = array( 'img'   => '', 
								  'url'   => 'section=members&amp;module=members&amp;do=unsuspend',
								  'title' => ipsRegistry::getClass('class_localization')->words['form_unsuspendmem'] );
			}
			else
			{
				$array[] = array( 'img'   => '', 
								  'url'   => 'section=members&amp;module=members&amp;do=banmember',
								  'title' => ipsRegistry::getClass('class_localization')->words['form_suspendmem'] );	
			}
		}
		
		if( ipsRegistry::getClass('class_permissions')->checkPermission( 'members_merge', 'members', 'members' ) )
		{
			$array[] = array( 'img'   => '', 
							  'url'   => 'section=tools&amp;module=members&amp;do=merge',
							  'title' => ipsRegistry::getClass('class_localization')->words['form_mergemember'] );
		}

		return $array;
	}
	
	/**
	 * Returns content for the page.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	array 				Member data
	 * @return	array 				Array of tabs, content
	 */
	public function getDisplayContent( $member=array() )
	{
		return array();
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	array 				Multi-dimensional array (core, extendedProfile) for saving
	 */
	public function getForSave()
	{
		return array( 'core' => array(), 'extendedProfile' => array() );
	}

}