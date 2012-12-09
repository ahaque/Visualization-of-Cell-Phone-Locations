<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Recover Lost Password
 * Last Updated: $Date: 2009-08-18 03:26:21 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 5023 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_lostpass extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_register' ), 'core' );

		/* What to do */
		switch( $this->request['do'] )
		{
			case 'sendform':
				$this->lostPasswordValidateForm();
			break;
			
			case '11':
				$this->lostPasswordEnd();
			break;
			
			case '03':
				$this->lostPasswordValidate();
			break;

			default:
			case '10':
				$this->lostPasswordForm();
			break;
		}
		
		/* Output */
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();				
	}
	
	/**
	 * Validates a lost password request
	 *
	 * @access	public
	 * @return	void
	 */
	public function lostPasswordValidate()
	{
		/* Check for input and it's in a valid format. */
		$in_user_id      = intval( trim( urldecode( $this->request['uid'] ) ) );
		$in_validate_key = IPSText::md5Clean( trim( urldecode( $this->request['aid'] ) ) );
		
		/* Check Input */
		if( ! $in_validate_key )
		{
			$this->registry->output->showError( 'validation_key_incorrect', 1015 );
		}
		
		if( ! preg_match( "/^(?:\d){1,}$/", $in_user_id ) )
		{
			$this->registry->output->showError( 'uid_key_incorrect', 1016 );
		}
		
		/* Attempt to get the profile of the requesting user */
		$member = IPSMember::load( $in_user_id );
			
		if( ! $member['member_id'] )
		{
			$this->registry->output->showError( 'lostpass_no_member', 1017 );
		}
		
		/* Get validating info.. */
		$validate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id=' . $in_user_id . ' and lost_pass=1' ) );

		if( ! $validate['member_id'] )
		{
			$this->registry->output->showError( 'lostpass_not_validating', 1018 );
		}
		
		if( ( $validate['new_reg'] == 1 ) && ( $this->settings['reg_auth_type'] == "admin" ) ) 
		{ 
			$this->registry->output->showError( 'lostpass_new_reg', 4010, true ); 
		} 
		
		if( $validate['vid'] != $in_validate_key )
		{
			$this->registry->output->showError( 'lostpass_key_wrong', 1019 );
		}
		else
		{
			/* On the same page? */
			if( $validate['lost_pass'] != 1 )
			{
				$this->registry->output->showError( 'lostpass_not_lostpass', 4011, true );
			}
			
			/* Test GD image */
			if( $this->settings['bot_antispam'] )
			{
				if ( $this->registry->getClass('class_captcha')->validate() !== TRUE )
				{
					$this->lostPasswordValidateForm( 'err_reg_code' );
					return;
				}
			}

			/* Send a new random password? */
			if( $this->settings['lp_method'] == 'random' )
			{
				//-----------------------------------------
				// INIT
				//-----------------------------------------
				
				$save_array = array();
				
				//-----------------------------------------
				// Generate a new random password
				//-----------------------------------------
				
				$new_pass = IPSLib::makePassword();
				
				//-----------------------------------------
				// Generate a new salt
				//-----------------------------------------
				
				$salt = IPSMember::generatePasswordSalt(5);
				$salt = str_replace( '\\', "\\\\", $salt );
				
				//-----------------------------------------
				// New log in key
				//-----------------------------------------
				
				$key  = IPSMember::generateAutoLoginKey();
				
				//-----------------------------------------
				// Update...
				//-----------------------------------------
				
				$save_array['members_pass_salt']		= $salt;
				$save_array['members_pass_hash']		= md5( md5($salt) . md5( $new_pass ) );
				$save_array['member_login_key']			= $key;
				$save_array['member_login_key_expire']	= $this->settings['login_key_expire'] * 60 * 60 * 24;
				$save_array['failed_logins']			= null;
				$save_array['failed_login_count']		= 0;
				
		        //-----------------------------------------
		    	// Load handler...
		    	//-----------------------------------------
		    	
		    	require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
		    	$this->han_login           =  new han_login( $this->registry );
		    	$this->han_login->init();
		    	$this->han_login->changePass( $member['email'], md5( $new_pass ) );
		    	
		    	if ( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		    	{
					$this->registry->output->showError( $this->lang->words['lostpass_external_fail'], 2013 );
		    	}
				
		    	IPSMember::save( $member['member_id'], array( 'members' => $save_array ) );
				
				//-----------------------------------------
				// Send out the email...
				//-----------------------------------------
				
				IPSText::getTextClass('email')->getTemplate("lost_pass_email_pass");
					
				IPSText::getTextClass('email')->buildMessage( array(
																'NAME'		=> $member['members_display_name'],
																'THE_LINK'	=> $this->settings['base_url'] . 'app=core&module=usercp&tab=core&area=password',
																'PASSWORD'	=> $new_pass,
																'LOGIN'		=> $this->settings['base_url'] . 'app=core&module=global&section=login',
																'USERNAME'	=> $member['name'],
																'EMAIL'		=> $member['email'],
																'ID'		=> $member['member_id'],
															)
														);
											
				IPSText::getTextClass('email')->subject = $this->lang->words['lp_random_pass_subject'] . ' ' . $this->settings['board_name'];
				IPSText::getTextClass('email')->to      = $member['email'];
				
				IPSText::getTextClass('email')->sendMail();

				$this->registry->output->setTitle( $this->lang->words['activation_form'] );
				$this->output = $this->registry->getClass('output')->getTemplate('register')->showLostPassWaitRandom( $member );	
			}
			else
			{
				if( $_POST['pass1'] == "" )
				{
					$this->registry->output->showError( 'pass_blank', 10184 );
				}
			
				if( $_POST['pass2'] == "" )
				{
					$this->registry->output->showError( 'pass_blank', 10185 );
				}
			
				$pass_a = trim( $this->request['pass1'] );
				$pass_b = trim( $this->request['pass2'] );
			
				if( strlen( $pass_a ) < 3 )
				{
					$this->registry->output->showError( 'pass_too_short', 10186 );						
				}
			
				if( $pass_a != $pass_b )
				{
					$this->registry->output->showError( 'pass_no_match', 10187 );								
				}
			
				$new_pass = md5( $pass_a );
				
				/* Update Member Array */
				$save_array = array();
				
				/* Generate a new salt */
				$salt = IPSMember::generatePasswordSalt(5);
				$salt = str_replace( '\\', "\\\\", $salt );
				
				/* New log in key */
				$key = IPSMember::generateAutoLoginKey();
				
				/* Update Array */
				$save_array['members_pass_salt']		= $salt;
				$save_array['members_pass_hash']		= md5( md5($salt) . $new_pass );
				$save_array['member_login_key']			= $key;
				$save_array['member_login_key_expire']	= $this->settings['login_key_expire'] * 60 * 60 * 24;
				$save_array['failed_logins']			= null;
				$save_array['failed_login_count']		= 0;					
				
				/* Change the password */
				require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
				$this->han_login           =  new han_login( $this->registry );
				$this->han_login->init();
				$this->han_login->changePass( $member['email'], md5( $new_pass ) );
		    	
				//-----------------------------------------
				// We'll ignore any remote errors
				//-----------------------------------------
				
		    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		    	{
					// Pass not changed remotely
		    	}
		    	
		    	/* Update the member */
		    	IPSMember::save( $member['member_id'], array( 'members' => $save_array ) );
			
				/* Remove "dead" validation */
				$this->DB->delete( 'validating', "vid='{$validate['vid']}' OR (member_id={$member['member_id']} AND lost_pass=1)" );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=core&module=global&section=login&do=autologin&frompass=1' );
			}
		}
	} 	
	
	/**
	 * Completes the lost password request form
	 *
	 * @access	public
	 * @return	void
	 */
	public function lostPasswordEnd()
	{
		if( $this->settings['bot_antispam'] )
		{
			if( !$this->registry->getClass('class_captcha')->validate( $this->request['regid'], $this->request['reg_code'] ) )
			{
				$this->lostPasswordForm( 'err_reg_code' );
				return;
			}
		}
		
		/* Back to the usual programming! :o */
		if( $this->request['member_name'] == "" AND $this->request['email_addy'] == "" )
		{
			$this->registry->output->showError( 'lostpass_name_email', 10110 );
		}
		
		/* Check for input and it's in a valid format. */
		$member_name = trim( strtolower( $this->request['member_name'] ) );
		$email_addy  = trim( strtolower( $this->request['email_addy'] ) );
		
		if( $member_name == "" AND $email_addy == "" )
		{
			$this->registry->output->showError( 'lostpass_name_email', 10111 );
		}
		
		/* Attempt to get the user details from the DB */
		if( $member_name )
		{
			$this->DB->build( array( 'select' => 'members_display_name, name, member_id, email, member_group_id', 'from' => 'members', 'where' => "members_l_username='{$member_name}'" ) );
			$this->DB->execute();
		}
		else if( $email_addy )
		{
			$this->DB->build( array( 'select' => 'members_display_name, name, member_id, email, member_group_id', 'from' => 'members', 'where' => "email='{$email_addy}'" ) );
			$this->DB->execute();
		}

		if ( ! $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( 'lostpass_no_user', 10112 );
		}
		else
		{
			$member = $this->DB->fetch();
			
			/* Is there a validation key? If so, we'd better not touch it */
			if( $member['member_id'] == "" )
			{
				$this->registry->output->showError( 'lostpass_no_mid', 2014 );
			}
			
			$validate_key = md5( IPSLib::makePassword() . uniqid( mt_rand(), TRUE ) );
			
			/* Get rid of old entries for this member */
			$this->DB->delete( 'validating', "member_id={$member['member_id']} AND lost_pass=1" );
			
			/* Update the DB for this member. */
			$db_str = array(
							'vid'         => $validate_key,
							'member_id'   => $member['member_id'],
							'temp_group'  => $member['member_group_id'],
							'entry_date'  => time(),
							'coppa_user'  => 0,
							'lost_pass'   => 1,
							'ip_address'  => $this->request['IP_ADDRESS'],
						   );
					
			/* Are they already in the validating group? */
			if( $member['member_group_id'] != $this->settings['auth_group'] )
			{
				$db_str['real_group'] = $member['member_group_id'];
			}
						   
			$this->DB->insert( 'validating', $db_str );
			
			/* Send out the email. */
    		IPSText::getTextClass('email')->getTemplate( 'lost_pass' );
				
			IPSText::getTextClass('email')->buildMessage( array(
											'NAME'         => $member['members_display_name'],
											'THE_LINK'     => $this->settings['base_url']."app=core&module=global&section=lostpass&do=sendform&uid=".$member['member_id']."&aid=".$validate_key,
											'MAN_LINK'     => $this->settings['base_url']."app=core&module=global&section=lostpass&do=sendform",
											'EMAIL'        => $member['email'],
											'ID'           => $member['member_id'],
											'CODE'         => $validate_key,
											'IP_ADDRESS'   => $this->member->ip_address,
										)
									);
										
			IPSText::getTextClass('email')->subject = $this->lang->words['lp_subject'] . ' ' . $this->settings['board_name'];
			IPSText::getTextClass('email')->to      = $member['email'];			
			IPSText::getTextClass('email')->sendMail();
			
			$this->output = $this->registry->getClass('output')->getTemplate('register')->lostPasswordWait( $member );
		}
    	
    	$this->registry->output->setTitle( $this->lang->words['lost_pass_form'] );
    }	
	
	/**
	 * Displays the lost password form
	 *
	 * @access	public
	 * @param	string	$errors
	 * @return	void
	 */
	public function lostPasswordForm( $errors="" )
	{
		//-----------------------------------------
    	// Do we have another URL for password resets?
    	//-----------------------------------------
    	
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$han_login =  new han_login( $this->registry );
    	$han_login->init();
    	$han_login->checkMaintenanceRedirect();
				
		/* CAPTCHA */
		if( $this->settings['bot_antispam'] )
		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();
		}
		
		$this->registry->output->setTitle( $this->lang->words['lost_pass_form'] );
		$this->registry->output->addNavigation( $this->lang->words['lost_pass_form'], '' );

    	$this->output .= $this->registry->output->getTemplate('register')->lostPasswordForm( $this->lang->words[ $errors ] );
    	
    	if ( $this->settings['bot_antispam'] )
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $captchaHTML, $this->output );
		}
    }	
	
	
	/**
	 * Shows the form for validating a lost password request
	 *
	 * @access	public
	 * @param	string	$msg
	 * @return	void
	 */
	public function lostPasswordValidateForm( $msg='' )
	{
		$this->output .= $this->registry->getClass('output')->getTemplate('register')->showLostpassForm( $this->lang->words[$msg] );
		
		/* Check for input and it's in a valid format. */
		if( $this->request['uid'] AND $this->request['aid'] )
		{ 
			$in_user_id      = intval( trim( urldecode( $this->request['uid'] ) ) );
			$in_validate_key = IPSText::md5Clean( trim( urldecode( $this->request['aid'] ) ) );
			$in_type         = trim( $this->request['type'] );
			
			if ($in_type == "")
			{
				$in_type = 'reg';
			}
			
			/* Check and test input */
			if ( ! $in_validate_key )
			{
				$this->registry->output->showError( 'validation_key_incorrect', 10113 );
			}
			
			if (! preg_match( "/^(?:\d){1,}$/", $in_user_id ) )
			{
				$this->registry->output->showError( 'uid_key_incorrect', 10114 );
			}
			
			/* Attempt to get the profile of the requesting user */
			$member = IPSMember::load( $in_user_id );

			if( ! $member['member_id'] )
			{
				$this->registry->output->showError( 'lostpass_no_member', 10115 );
			}
			
			/* Get validating info.. */
			$validate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'validating', 'where' => "member_id={$in_user_id} and vid='{$in_validate_key}' and lost_pass=1" ) );
			
			if( ! $validate['member_id'] )
			{
				$this->registry->output->showError( 'validation_key_incorrect', 10116 );
			}
			
			$this->output = str_replace( "<!--IBF.INPUT_TYPE-->", $this->registry->output->getTemplate('register')->show_lostpass_form_auto( $in_validate_key, $in_user_id ), $this->output );
		}
		else
		{
			$this->output = str_replace( "<!--IBF.INPUT_TYPE-->", $this->registry->output->getTemplate('register')->show_lostpass_form_manual(), $this->output );
		}
		
		/* CAPTCHA */
		if( $this->settings['bot_antispam'] )
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->registry->getClass('class_captcha')->getTemplate(), $this->output );
		}
		
		$this->registry->output->setTitle( $this->lang->words['activation_form'] );
		$this->registry->output->addNavigation( $this->lang->words['activation_form'], '' );
	}
}