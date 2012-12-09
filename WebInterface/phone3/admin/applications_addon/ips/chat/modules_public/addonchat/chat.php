<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Chat services
 * Last Updated: $Date: 2009-05-12 10:54:26 -0400 (Tue, 12 May 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Chat
 * @since		Fir 12th Aug 2005
 * @version		$Revision: 4634 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class public_chat_addonchat_chat extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML and LANG
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_chataddon' ) );

		if ( ! $this->settings['chat_account_no'] )
		{
			$this->registry->output->showError( 'no_chat_account_number', 1090 );
		}
		
		//-----------------------------------------
		// Get extra settings
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'conf_key,conf_value,conf_default', 'from' => 'core_sys_conf_settings', 'where' => "conf_key LIKE 'chat%'" ) );
    	$this->DB->execute();
    	
    	while( $r = $this->DB->fetch() )
    	{
    		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
    		
    		$this->settings[ $r['conf_key']] =  $value ;
    	}
    	
		//-----------------------------------------
		// Can this group access chat?
		//-----------------------------------------
		    	
        if( $this->settings['chat_access_groups'] )
        {
	    	$access_groups = explode( ",", $this->settings['chat_access_groups'] );
	    	
	    	$my_groups = array( $this->memberData['member_group_id'] );
	    	
	    	if( $this->memberData['mgroup_others'] )
	    	{
		    	$my_groups = array_merge( $my_groups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
	    	}
	    	
	    	$access_allowed = 0;
	    	
	    	foreach( $my_groups as $group_id )
	    	{
		    	if( in_array( $group_id, $access_groups ) )
		    	{
			    	$access_allowed = 1;
		    	}
	    	}
	    	
	    	if( !$access_allowed )
	    	{
		    	$this->registry->output->showError( 'no_chat_access', 1091 );
	    	}
        }    	
		
		//-----------------------------------------
		// Got address?
		//-----------------------------------------
		
		if ( ! $this->settings['chat_server_addr'] )
		{
			$this->settings['chat_server_addr'] = 'client11.addonchat.com';
		}
		
		//-----------------------------------------
		// Server
		//-----------------------------------------
		
		$this->settings['chat_server_addr'] = str_replace( 'http://', '', $this->settings['chat_server_addr'] );
		
		//-----------------------------------------
		// Details
		//-----------------------------------------
		
		$width	= $this->settings['chat_width']    ? $this->settings['chat_width']  : 600;
		$height	= $this->settings['chat_height']   ? $this->settings['chat_height'] : 350;
		$lang	= $this->settings['chat_language'] ? $this->settings['chat_language'] : 'en';
		$user	= "";
		$pass	= "";
		
		//-----------------------------------------
		// Got ID?
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] )
		{
			$user		= $this->memberData['members_display_name'];
			
			$member		= IPSMember::load( $this->memberData['email'] );
			$pass		= $member['members_pass_hash'];
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('chatsigma')->chat_inline( $this->settings['chat_server_addr'], preg_replace( "/.+?\-(\d+)$/", "\\1", $this->settings['chat_account_no'] ), $lang, $width, $height, $user, $pass);
		
		$this->registry->output->addNavigation( $this->lang->words['live_chat'], '' );
		$this->registry->output->setTitle( $this->lang->words['live_chat'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
}