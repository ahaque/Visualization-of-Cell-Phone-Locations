<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Group property updater (Forums)
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_group_form__forums implements admin_group_form
{	
	/**
	* Tab name
	* This can be left blank and the application title will
	* be used
	*
	* @access	public
	* @var		string	Tab name
	*/
	public $tab_name = "";

	
	/**
	* Returns content for the page.
	*
	* @access	public
	* @author	Brandon Farber
	* @param    array 				Group data
	* @param	integer				Number of tabs used so far
	* @return   array 				Array of tabs, content
	*/
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_group_form', 'forums');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ), 'forums' );
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	* Process the entries for saving and return
	*
	* @access	public
	* @author	Brandon Farber
	* @return   array 				Array of keys => values for saving
	*/
	public function getForSave()
	{
		$return = array(
						'g_other_topics'		=>ipsRegistry::$request['g_other_topics'],
						'g_post_new_topics'		=>ipsRegistry::$request['g_post_new_topics'],
						'g_reply_own_topics'	=>ipsRegistry::$request['g_reply_own_topics'],
						'g_reply_other_topics'	=>ipsRegistry::$request['g_reply_other_topics'],
						'g_edit_posts'			=>ipsRegistry::$request['g_edit_posts'],
						'g_edit_cutoff'			=>ipsRegistry::$request['g_edit_cutoff'],
						'g_delete_own_posts'	=>ipsRegistry::$request['g_delete_own_posts'],
						'g_open_close_posts'	=>ipsRegistry::$request['g_open_close_posts'],
						'g_delete_own_topics'	=>ipsRegistry::$request['g_delete_own_topics'],
						'g_post_polls'			=>ipsRegistry::$request['g_post_polls'],
						'g_vote_polls'			=>ipsRegistry::$request['g_vote_polls'],
						'g_can_remove'			=>ipsRegistry::$request['g_can_remove'] ? intval(ipsRegistry::$request['g_can_remove'] ) : 0,
						'g_append_edit'			=>ipsRegistry::$request['g_append_edit'],
						'g_avoid_q'				=>ipsRegistry::$request['g_avoid_q'],
						'g_avoid_flood'			=>ipsRegistry::$request['g_avoid_flood'],
						'g_post_closed'			=>ipsRegistry::$request['g_post_closed'],
						'g_edit_topic'			=>ipsRegistry::$request['g_edit_topic'],
						'g_topic_rate_setting'	=> intval(ipsRegistry::$request['g_topic_rate_setting']),
						'g_mod_preview'         => ipsRegistry::$request['g_mod_preview'],
						'g_mod_post_unit'		=> intval( ipsRegistry::$request['g_mod_post_unit'] ),
						'g_ppd_unit'			=> intval( ipsRegistry::$request['g_ppd_unit'] ),
						'g_ppd_limit'			=> intval( ipsRegistry::$request['g_ppd_limit'] ),
						);

		return $return;
	}
	

}