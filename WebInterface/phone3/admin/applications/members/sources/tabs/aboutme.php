<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile Plugin Library
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		20th February 2002
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profile_aboutme extends profile_plugin_parent
{
	/**
	 * Feturn HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! is_array( $member ) OR ! count( $member ) )
		{
			return $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_aboutme_to_show' );
		}

		//-----------------------------------------
		// Format signature
		//-----------------------------------------
		
		if( $member['signature'] )
		{
			IPSText::getTextClass('bbcode')->parse_bbcode				= $this->settings[ 'msg_allow_code' ];
			IPSText::getTextClass('bbcode')->parse_smilies				= 1;
			IPSText::getTextClass('bbcode')->parse_html					= $this->settings[ 'msg_allow_html' ];
			IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
			IPSText::getTextClass('bbcode')->parsing_section			= 'signatures';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $member['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $member['mgroup_others'];
		
			$member['signature']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $member['signature'] );
		
			$member['signature'] = $this->registry->getClass('output')->getTemplate('global')->signature_separator( $member['signature'] );
		}
		
		//-----------------------------------------
		// Format 'About me'
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parse_html				= intval($this->settings['aboutme_html']);
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= $this->settings['aboutme_emoticons'];
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->settings['aboutme_bbcode'];
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'aboutme';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $member['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $member['mgroup_others'];

		$member['pp_about_me']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $member['pp_about_me'] );
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->tabAboutMe( $member );
		
		//-----------------------------------------
		// Macros...
		//-----------------------------------------
		
		$content = $this->registry->output->replaceMacros( $content );
		
		//-----------------------------------------
		// Return content..
		//-----------------------------------------
		
		return $content ? $content : $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_aboutme_to_show' );
	}
	
}