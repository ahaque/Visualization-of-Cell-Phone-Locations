<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member property updater (AJAX)
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_member_form__forums implements admin_member_form
{	
	/**
	* Tab name
	* This can be left blank and the application title will
	* be used
	*
	* @access	public
	* @var		string		Tab name
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
	* @param    array 			Member data
	* @return   array 			Array of links
	*/
	public function getSidebarLinks( $member=array() )
	{
	
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ), 'forums' );
		
		
		$array = array();
				
		$array[] = array( 'img'   => '', 
						  'url'   => 'section=tools&amp;module=tools&amp;do=deleteposts',
						  'title' => ipsRegistry::getClass('class_localization')->words['m_deltitle'] );
						  
		$array[] = array( 'img'   => '', 
						  'url'   => 'section=tools&amp;module=tools&amp;do=deletesubscriptions',
						  'title' => ipsRegistry::getClass('class_localization')->words['m_delsubs'] );
	
		return $array;
	}
	
	/**
	* Returns content for the page.
	*
	* @access	public
	* @author	Matt Mecham
	* @param    array 				Member data
	* @return   array 				Array of tabs, content
	*/
	public function getDisplayContent( $member=array() )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_member_form', 'forums');

		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ), 'forums' );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member_form' ), 'forums' );
		
		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member 			= IPSMember::load( $member['member_id'], 'extendedProfile' );
		$member['_avatar']	= str_replace( '<img ', '<img id="MF__avatar" ', IPSMember::buildAvatar( $member ) );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		return array( 'tabs' => $this->html->acp_member_form_tabs( $member ), 'content' => $this->html->acp_member_form_main( $member ) );
	}
	
	/**
	* Process the entries for saving and return
	*
	* @access	public
	* @author	Brandon Farber
	* @return   array 				Multi-dimensional array (core, extendedProfile) for saving
	*/
	public function getForSave()
	{
		$return = array( 'core' => array(), 'extendedProfile' => array() );
		
		$return['core']['posts']				= intval(ipsRegistry::$request['posts']);
		$return['core']['view_avs']				= intval(ipsRegistry::$request['view_avs']);
		$return['core']['view_img']				= intval(ipsRegistry::$request['view_img']);
		$return['core']['restrict_post']		= ipsRegistry::$request['post_indef'] ? 1 : ( ipsRegistry::$request['post_timespan'] > 0 ? IPSMember::processBanEntry( array( 'timespan' => intval(ipsRegistry::$request['post_timespan']), 'unit' => ipsRegistry::$request['post_units']  ) ) : '' );
		$return['core']['mod_posts']			= ipsRegistry::$request['mod_indef'] ? 1 : ( ipsRegistry::$request['mod_timespan'] > 0 ? IPSMember::processBanEntry( array( 'timespan' => intval(ipsRegistry::$request['mod_timespan']), 'unit' => ipsRegistry::$request['mod_units']  ) ) : '' );
		$return['core']['org_perm_id']			= ipsRegistry::$request['org_perm_id'] ? ',' . implode( ",", ipsRegistry::$request['org_perm_id'] ) . ',' : '';
		
		return $return;
	}
	

}