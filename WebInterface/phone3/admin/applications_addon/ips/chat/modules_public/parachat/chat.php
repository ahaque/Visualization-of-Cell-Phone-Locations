<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Chat services
 * Last Updated: $Date: 2009-03-04 07:36:56 -0500 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Chat
 * @since		Fir 12th Aug 2005
 * @version		$Revision: 4135 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class public_chat_parachat_chat extends ipsCommand
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
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_chatpara' ) );

		if ( ! $this->settings['chat04_account_no'] )
		{
			$this->registry->output->showError( 'no_chat_account_number', 1092 );
		}
		
		//-----------------------------------------
		// Get extra settings
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'conf_key,conf_value,conf_default', 'from' => 'core_sys_conf_settings', 'where' => "conf_key LIKE 'chat04%'" ) );
    	$this->DB->execute();
    	
    	while( $r = $this->DB->fetch() )
    	{
    		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
    		
    		$this->settings[ $r['conf_key']] =  $value ;
    	}
    	
    	if( $this->settings['chat04_access_groups'] )
    	{
	    	$access_groups = explode( ",", $this->settings['chat04_access_groups'] );
	    	
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
		    	$this->registry->output->showError( 'no_chat_access', 1093 );
	    	}
    	}
		
		//-----------------------------------------
		// Width and Height
		//-----------------------------------------
		
		$width	= $this->settings['chat04_width']  ? $this->settings['chat04_width']  : 600;
		$height	= $this->settings['chat04_height'] ? $this->settings['chat04_height'] : 350;
		
		//-----------------------------------------
		// v6 < specifics
		//-----------------------------------------
		
		if ( intval($this->settings['parachat_version']) < 7 )
		{
			//-----------------------------------------
			// Got room?
			//-----------------------------------------
		
			if ( ! $this->settings['chat04_default_room'] )
			{
				$this->registry->output->showError( 'no_chat_default_room', 1094 );
			}
		
			//-----------------------------------------
			// Got service type?
			//-----------------------------------------
		
			if ( ! $this->settings['chat04_servicetype'] )
			{
				$this->registry->output->showError( 'no_chat_service_type', 1095 );
			}
		
			//-----------------------------------------
			// Make sure it has #
			//-----------------------------------------
		
			$this->settings[ 'chat04_default_room'] =  '#' . str_replace( '#', '', $this->settings['chat04_default_room']  );
		
			//-----------------------------------------
			// Get service library
			//-----------------------------------------
		
			$CHAT_SERVER = array(
								'advanced' => 'chat3.',
								'premium'  => 'chat4.',
			);
			
			$CHAT_FOLDER = array(
								'advanced' => 'pca',
								'premium'  => 'pcp',
			);
			
			$CHAT_ONLINELIST = array(
								'advanced' => 'cgi-bin/userlist/advanced/group.cgi?group=',
								'premium'  => 'cgi-bin/userlist/premium/group.cgi?group=',
			);
		
			$server = $this->settings['parachat_codebase_url'] ? $this->settings['parachat_codebase_url'] : 'http://'. $CHAT_SERVER[ $this->settings['chat04_servicetype'] ].'/'. $CHAT_FOLDER[ $this->settings['chat04_servicetype'] ];
		
			//-----------------------------------------
			// Lang?
			//-----------------------------------------
		
			$this->settings[ 'chat04_default_lang'] =  ( $this->settings['chat04_default_lang'] == ""  ? 'english.conf' : $this->settings['chat04_default_lang'] );
		
			//-----------------------------------------
			// Text mode
			//-----------------------------------------
		
			$this->settings[ 'chat04_plainmode'] =  ( $this->settings['chat04_plainmode']  ? 'PlainText' : 'MegaText' );
		
			//-----------------------------------------
			// Style options..
			//-----------------------------------------
		
			$style = array(
							'applet_bg' => $this->settings['chat04_style_applet_bg'] ? str_replace( '#', '', $this->settings['chat04_style_applet_bg'] ) : 'BCD0ED',
							'applet_fg' => $this->settings['chat04_style_applet_fg'] ? str_replace( '#', '', $this->settings['chat04_style_applet_fg'] ) : '345487',
							'window_bg' => $this->settings['chat04_style_window_bg'] ? str_replace( '#', '', $this->settings['chat04_style_window_bg'] ) : 'F5F9FD',
							'window_fg' => $this->settings['chat04_style_window_fg'] ? str_replace( '#', '', $this->settings['chat04_style_window_fg'] ) : '345487',
							'font_size' => $this->settings['chat04_style_font_size'] ? str_replace( '#', '', $this->settings['chat04_style_font_size'] ) : '11',
						  );
						
			//-----------------------------------------
			// Show chat..
			//-----------------------------------------
			
			$template		= 'legacy';
			
			$options		= array(
									'server'	=> $server,
									'account'	=> $this->settings['chat04_account_no'],
									'room'		=> $this->settings['chat04_default_room'],
									'width'		=> $width,
									'height'	=> $height,
									'language'	=> $this->settings['chat04_default_lang'],
									'plainmode'	=> $this->settings['chat04_plainmode'],
									'style'		=> $style,
									);

			$this->output .= $this->registry->getClass('output')->getTemplate('chatpara')->chat_inline( $server, $this->settings['chat04_account_no'], $this->settings['chat04_default_room'], $width, $height, $this->settings['chat04_default_lang'], $this->settings['chat04_plainmode'], $style );
		}
		else
		{
			//-----------------------------------------
			// Show chat..
			//-----------------------------------------
			
			$server = $this->settings['parachat_codebase_url'] ? $this->settings['parachat_codebase_url'] : "http://server26./pchat/applet";
			$room   = $this->settings['chat04_default_room']   ? $this->settings['chat04_default_room']   : "Lobby";
			
			if ( strstr( strtolower( $this->settings['chat04_default_room'] ), 'lobby_' ) )
			{
				$room = 'Lobby';
			}

			$template		= 'current';
			
			$options		= array(
									'server'	=> $server,
									'account'	=> $this->settings['chat04_account_no'],
									'room'		=> $room,
									'width'		=> $width,
									'height'	=> $height,
									);
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('chatpara')->chat_inline( $template, $options );
		
		//-----------------------------------------
		// Show chat..
		//-----------------------------------------
		
		$this->output = str_replace( '<!--AUTOLOGIN-->'  , $this->_autoLogin(), $this->output );
		$this->output = str_replace( '<!--CUSTOMPARAM-->', $this->settings['chat04_customparams'], $this->output );

		$this->registry->output->addNavigation( $this->lang->words['live_chat'], '' );
		$this->registry->output->setTitle( $this->lang->words['live_chat'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Autologin
	 *
	 * @access	private
	 * @return	string		HTML autologin content
	 */
	private function _autoLogin()
	{
		if ( $this->memberData['member_id'] )
		{
			$member		= IPSMember::load( $this->memberData['email'] );
			$pass		= $member['members_pass_hash'];
			
			$tmpname	= $this->memberData['members_display_name'];
			$namearray	= array();
			$name		= "";
			
			//-----------------------------------------
			// Okay, we need to safe format this name
			//-----------------------------------------
			
			$tmpname = preg_replace( "#\s#", "_", $tmpname );
			$tmpname = preg_replace( "#(?:[^\w\d\_])#is", "-", $tmpname );
			
			if ( intval( $this->settings['parachat_version'] ) > 6 )
			{
				$return ="<param name=\"Ctrl.AutoLogin\" value=\"true\">
						  <param name=\"Net.User\" value=\"".$tmpname."\">
						  <param name=\"Net.UserPass\" value=\"".urlencode("md5pass({$pass})" . $this->memberData['member_id'] . "")."\">\n";
			
			}
			else
			{
				$return = "<param name='ctrl.LoginOnLoad' value='true'>\n".
	      				  "<param name='ctrl.Nickname' value='".$tmpname."'>\n".
	      				  "<param name='ctrl.RealName' value='".$this->memberData['members_display_name']."'>\n".
	      				  "<param name='ctrl.Password' value='".urlencode("md5pass({$pass})" . $this->memberData['member_id'] . "")."'>\n";
			}
      				   
      		return $return;
		}
		else
		{
			return '';
		}
	}
}