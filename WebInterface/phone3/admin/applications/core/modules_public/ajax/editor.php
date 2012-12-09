<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * AJAX Switch Editor
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4948 $
 * @deprecated	We no longer support AJAX switching of editor type.  This file is not likely up to date as a result.
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_editor extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

		$to_rte = intval( $this->request['to_rte'] );
		$post   = $this->convertUnicode( $_POST['Post'] );
		$post   = IPSText::stripslashes($post);
		//$post   = $this->convertHtmlEntities( $post );

		//-----------------------------------------
		// Load BBCode
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/bbcode/core.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/bbcode/normal.php' );

		$bbcode						=  new class_bbcode_normal( $this->registry );
		$bbcode->parse_bbcode		= 1;
		$bbcode->parse_html			= 0;
		$bbcode->parse_nl2br		= 1;
		$bbcode->parse_smilies		= 1;
		$bbcode->parsing_section	= 'topics';
		
		//-----------------------------------------
		// Converting from STD to RTE?
		//-----------------------------------------
		
		if ( $to_rte )
		{
			//-----------------------------------------
			// Ensure no slashy slashy
			//-----------------------------------------

			$post = str_replace( '"','&quot;', $post );
			$post = str_replace( "'",'&apos;', $post );

			//-----------------------------------------
			// Convert <>
			//-----------------------------------------

			$post = str_replace( '<', '&lt;', $post );
		    $post = str_replace( '>', '&gt;', $post );
			
			$post = $bbcode->convertForRTE( $bbcode->preDbParse( $post ) );
			
			//-----------------------------------------
			// Convert <>
			//-----------------------------------------
			
			$post = str_replace( '&#60;', '&lt;', $post );
		    $post = str_replace( '&#62;', '&gt;', $post );

			//-----------------------------------------
			// Fix up...
			//-----------------------------------------
			
			$post	= $this->registry->output->replaceMacros( $post );

			//-----------------------------------------
			// Make sure no nasty HTML entities show
			//-----------------------------------------
			
			$post = IPSText::htmlspecialchars( $post );
		}
		//-----------------------------------------
		// Converting from RTE to STD
		//-----------------------------------------
		
		else
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/editor/class_editor.php' );
			require_once( IPS_ROOT_PATH . 'sources/classes/editor/class_editor_rte.php' );
			
			//-----------------------------------------
			// Ok, now, apparently, for SOME reason, IE
			// strips SOME comments but not others...
			// this causes font/span stuff to break.
			// Let's just strip all the comments
			//-----------------------------------------
			
			if ( $this->member->browser['browser'] == "ie" OR $this->member->browser['browser'] == "opera" )
			{
				$post = preg_replace( "#<\!--(.*?)-->#is", ""  , $post );
			}
			
			//-----------------------------------------
			// IE has this thing where <b>[center]text[/center]</b>
			// Becomes [b][center][b]text[/b][/center][/b]
			//-----------------------------------------
			
			$rte           = new class_editor_module( $this->registry );
			$post		   = $bbcode->preEditParse( $post );
			$post          = $rte->processAfterForm( $post );
			
			//-----------------------------------------
			// Fix up...
			//-----------------------------------------
			
			$post = preg_replace( "/<br>|<br \/>/", "\n", $post );
		}

		//-----------------------------------------
		// Member? Store choice...
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] )
		{
			$_choice = ( $to_rte ) ? 'rte' : 'std';
			
			IPSMember::save( $this->memberData['member_id'], array( 'members' => array( 'members_editor_choice' => $_choice ) ) );
		}
		
		$post   = trim( html_entity_decode($post) );
		
		$this->returnHtml( $post );
	}
}