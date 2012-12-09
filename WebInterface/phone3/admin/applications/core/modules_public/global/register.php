<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Register
 * Last Updated: $Date: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 5045 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_register extends ipsCommand
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
			case 'process_form':
				$this->registerProcessForm();
			break;
			
			case 'auto_validate':
				$this->_autoValidate();
			break;
			
			case 'coppa_two':
				$this->registerCoppaTwo();
			break;
			
			case '12':
				$this->registerCoppaPermsForm();
			break;
			
    		case '05':
    			$this->_showManualForm();
    			break;
    			
    		case '07':
    			$this->_showManualForm('newemail');
    			break;
    			
    		case 'reval':
    			$this->_revalidateForm();
    			break;
    			
    		case 'reval2':
    			$this->_revalidateComplete();
    			break;
			
			case 'complete_login':
				$this->_completeRegistration();
				break;
			case 'complete_login_do':
				$this->_completeRegistrationSave();
				break;
			
			default:
			case 'form':
				if( $this->settings['no_reg'] == 1 )
				{
					$this->registry->output->showError( 'registration_disabled', 10123 );
				}
		
    			if( $this->settings['use_coppa'] == 1 and $this->request['coppa_pass'] != 1 )
    			{
    				$this->registerCoppaStart();
    			}
    			else
    			{
    				$this->registerForm();
    			}			
			break;
		}
		
		/* Output */
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();		
	}
	
	
	/**
	 * Save the data to complete the partial member record login/registration
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _completeRegistrationSave()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mid					= intval( $this->request['mid'] );
		$key					= intval( $this->request['key'] );
		$in_email				= strtolower( trim($this->request['EmailAddress']) );
		$banfilters				= array();
		$form_errors			= array( 'dname' => array(), 'email' => array(), 'general' => array() );
		$members_display_name	= trim( $this->request['members_display_name'] );
		$poss_session_id		= "";
		
		//-----------------------------------------
		// Get DB row
		//-----------------------------------------
		
		$reg		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id={$mid} AND partial_date={$key}" ) );
		$tmp_member	= IPSMember::load( $mid );
		
		//-----------------------------------------
		// Got it?
		//-----------------------------------------
		
		if ( ! $reg['partial_id'] OR ! $tmp_member['member_id'] )
		{
			$this->registry->output->showError( 'partial_reg_noid', 10117 );
		}
		
		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
		}

		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$custom_fields = new customProfileFields();
		
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToSave( $this->request, 'register' );
		
		/* Check */
		if( $custom_fields->error_messages )
		{
			$form_errors['general']	= $custom_fields->error_messages;
		}
		
		//-----------------------------------------
		// Remove 'sneaky' spaces
		//-----------------------------------------
		
		if ( $this->settings['strip_space_chr'] )
    	{
			$members_display_name = IPSText::removeControlCharacters( $members_display_name );
		}
		
		//-----------------------------------------
		// Testing email addresses?
		//-----------------------------------------
		
		if ( ! $reg['partial_email_ok'] )
		{
			//-----------------------------------------
			// Check the email address
			//-----------------------------------------

			if( !IPSText::checkEmailAddress( $in_email ) )
			{
				$form_errors['email'][] = $this->lang->words['reg_error_email_nm'];
			}
		
			//-----------------------------------------
			// Test email address
			//-----------------------------------------
		
			$this->request['EmailAddress_two']	= strtolower( trim($this->request['EmailAddress_two']) );
		
			if ( $this->request['EmailAddress_two'] != $in_email )
			{
				$form_errors['email'][] = $this->lang->words['reg_error_email_nm'];
			}
			
			//-----------------------------------------
			// Are they banned [EMAIL]?
			//-----------------------------------------

			if ( is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
			{
				foreach ( $banfilters['email'] as $email )
				{
					$email = str_replace( '\*', '.*' ,  preg_quote($email, "/") );

					if ( preg_match( "/^{$email}$/i", $in_email ) )
					{
						$form_errors['email'][] = $this->lang->words['reg_error_email_taken'];
						break;
					}
				}
			}
			
			/* Is this email addy taken? */
			if( IPSMember::checkByEmail( $in_email ) == TRUE )
			{
				$form_errors['email'][] = $this->lang->words['reg_error_email_taken'];
			}
		
	        //-----------------------------------------
	    	// Load handler...
	    	//-----------------------------------------
	    	
	    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
	    	$han_login		=  new han_login( $this->registry );
	    	$han_login->init();
	    	
	    	if( $han_login->emailExistsCheck( trim( strtolower( $member['email'] ) ), trim( strtolower( $in_email ) ) ) )
		    {
				$form_errors['email'][] = $this->lang->words['reg_error_email_taken'];
	    	}
		}

		if ( $this->settings['auth_allow_dnames'] )
		{
			/* Check the username */
			$user_check = IPSMember::getFunction()->cleanAndCheckName( $members_display_name, $tmp_member, 'members_display_name' );
	
			if( is_array( $user_check['errors'] ) && count( $user_check['errors'] ) )
			{
				$form_errors['dname'] = array_merge( $form_errors['dname'], $user_check['errors'] );
			}
		}

		//-----------------------------------------
		// CHECK 1: Any errors (duplicate names, etc)?
		//-----------------------------------------
		
		if ( count( $form_errors ) )
		{
			$errorMessages	= array();
			
			foreach( $form_errors as $errorCat => $errorMessage )
			{
				foreach( $errorMessage as $error )
				{
					$errorMessages['general'][]	= $error;
				}
			}

			if( count($errorMessages) )
			{
				$this->_completeRegistration( $errorMessages );
				return;
			}
		}
		
		//-----------------------------------------
		// Update: Members
		//-----------------------------------------
		
		$members_display_name = $this->settings['auth_allow_dnames'] ? $members_display_name : $tmp_member['name'];
		
		if ( ! $reg['partial_email_ok'] )
		{
			IPSMember::save( $mid, array( 'members' => array( 'email'						=> $in_email,
																'members_display_name'		=> $members_display_name,
																'name'						=> $tmp_member['name'] ? $tmp_member['name'] : $members_display_name,
																'members_l_username'		=> $tmp_member['members_l_username'] ? $tmp_member['members_l_username'] : $members_display_name,
																'members_l_display_name'	=> strtolower( $members_display_name ) ) ) );
		}
		else
		{
			IPSMember::save( $mid, array( 'members' => array( 'members_display_name'		=> $members_display_name,
																'name'						=> $tmp_member['name'] ? $tmp_member['name'] : $members_display_name,
																'members_l_username'		=> $tmp_member['members_l_username'] ? $tmp_member['members_l_username'] : $members_display_name,
																'members_l_display_name'	=> strtolower( $members_display_name ) ) ) );
		}
		
		//-----------------------------------------
		// Delete: Partials row
		//-----------------------------------------
		
		$this->DB->delete( 'members_partial', 'partial_member_id=' . $mid );
		
		//-----------------------------------------
		//  Update: Profile fields
		//-----------------------------------------

		$this->DB->force_data_type = array();
		
		foreach( $custom_fields->out_fields as $_field => $_data )
		{
			$this->DB->force_data_type[ $_field ] = 'string';
		}

		if ( is_array($custom_fields->out_fields) and count($custom_fields->out_fields) )
		{
			$this->DB->update( 'pfields_content', $custom_fields->out_fields, 'member_id=' . $mid );
		}	
		
		//-----------------------------------------
		// Send out admin email
		//-----------------------------------------
		
		if ( $this->settings['new_reg_notify'] )
		{
			$date = $this->registry->getClass('class_localization')->getDate( time(), 'LONG', 1 );
			
			IPSText::getTextClass('email')->getTemplate("admin_newuser");
		
			IPSText::getTextClass('email')->buildMessage( array( 'DATE'			=> $date,
												'MEMBER_NAME'	=> $members_display_name ) );
										
			IPSText::getTextClass('email')->subject = $this->lang->words['new_registration_email'] . $this->settings['board_name'];
			IPSText::getTextClass('email')->to      = $this->settings['email_in'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		//-----------------------------------------
		// Set cookies
		//-----------------------------------------
														   
		IPSCookie::set("member_id"   , $mid								, 1 );
		IPSCookie::set("pass_hash"   , $tmp_member['member_login_key']	, 1 );
		
		//-----------------------------------------
		// Fix up session
		//-----------------------------------------

		$privacy    = $this->request['Privacy'] ? 1 : 0;
		
		if( $this->caches['group_cache'][ $tmp_member['member_group_id'] ]['g_hide_online_list'] )
		{
			$privacy	= 1;
		}
		
		$this->member->sessionClass()->convertGuestToMember( array( 'member_name'	  => $members_display_name,
														  			'member_id'	  	  => $mid,
																	'member_group'    => $tmp_member['member_group_id'],
																	'login_type'	  => $privacy ) );

		//-----------------------------------------
		// Update Stats
		//-----------------------------------------

		$cache	= $this->cache->getCache('stats');
		
		if( $members_display_name AND $mid )
		{
			$cache['last_mem_name']	= $members_display_name;
			$cache['last_mem_id']	= $mid;
		}
		
		$cache['mem_count']		+= 1;
		
		$this->cache->setCache( 'stats', $cache, array( 'array' => 1, 'deletefirst' => 0 ) );
		
		/* Complete account */
		IPSLib::runMemberSync( 'onCompleteAccount', IPSMember::load( $mid ) );
		
		//-----------------------------------------
		// Go to the board index
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['clogin_done'], $this->settings['base_url'] );
	}
 	
	/**
	 * When a member logs in via an external login method and we do not have all of the data
	 * to create the member's account, we create a partial record.  This function shows the
	 * form upon first visit by member (usually immediately after login) to complete the
	 * login/registration.
	 *
	 * @access	private
	 * @param 	array 		Errors
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _completeRegistration( $form_errors=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mid          = intval( $this->request['mid'] );
		$key          = intval( $this->request['key'] );
		$final_errors = '';
		
		//-----------------------------------------
		// Get DB row
		//-----------------------------------------
		
		$reg = $this->DB->buildAndFetch( array( 'select'	=> '*',
														'from'	=> 'members_partial',
														'where'	=> "partial_member_id={$mid} AND partial_date={$key}" ) );
		
		//-----------------------------------------
		// Got it?
		//-----------------------------------------
		
		if ( ! $reg['partial_id'] )
		{
			$this->registry->output->showError( 'partial_reg_noid', 10118 );
		}
		
		//-----------------------------------------
		// Custom profile fields stuff
		//-----------------------------------------
		
		/* Custom Profile Fields */
		$custom_fields_out = array( 'required', 'optional' );
				
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$custom_fields = new customProfileFields();
		
		$custom_fields->member_data = array();
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToEdit( 'register' );
		
		if ( count( $custom_fields->out_fields ) )
		{
			foreach( $custom_fields->out_fields as $id => $form_element )
	    	{
				if ( $custom_fields->cache_data[ $id ]['pf_not_null'] == 1 )
				{
					$ftype = 'required';
				}
				else
				{
					$ftype = 'optional';
				}
				
				$custom_fields_out[$ftype][] = array( 
														'name'  => $custom_fields->field_names[ $id ], 
														'desc'  => $custom_fields->field_desc[ $id ], 
														'field' => $form_element, 
														'id'    => $id,
														'error' => $error,
														'type'	=> $custom_fields->cache_data[ $id ]['pf_type']
														);
	    	}
		}

    	//-----------------------------------------
    	// Other errors
    	//-----------------------------------------

    	foreach( array( 'username', 'dname', 'password', 'email', 'general' ) as $thing )
    	{
			if ( is_array( $form_errors[ $thing ] ) AND count( $form_errors[ $thing ] ) )
			{
				$final_errors .= implode( "<br />", $form_errors[ $thing ] );
			}
		}

		//-----------------------------------------
		// No display name?
		//-----------------------------------------
		
		if ( ! $this->memberData['members_display_name'] )
		{
			$this->member->setProperty( 'members_display_name', $this->memberData['email'] );
		}
		
		//-----------------------------------------
		// Show the form (email and display name)
		//-----------------------------------------
		
		$this->output     .= $this->registry->getClass('output')->getTemplate('register')->completePartialLogin( $mid, $key, $custom_fields_out, $final_errors, $reg );
		
		$this->registry->output->setTitle( $this->lang->words['clogin_title'] );
		$this->registry->output->addNavigation( $this->lang->words['clogin_title'], '' );
	}
	
	/**
	 * Show the form to request validation email to be resent
	 *
	 * @access	private
	 * @param 	string 		Errors
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _revalidateForm( $errors="" )
	{
    	$name = $this->memberData['member_id'] == "" ? '' : $this->memberData['email'];
		
		$this->output     .= $this->registry->getClass('output')->getTemplate('register')->showRevalidateForm( $name, $errors );
		$this->registry->output->setTitle( $this->lang->words['rv_title'] );
		$this->registry->output->addNavigation( $this->lang->words['rv_title'], '' );
	}
	
	/**
	 * Resend email validation request for lost password, email change and new registration
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _revalidateComplete()
	{
		//-----------------------------------------
		// Check in the DB for entered member name
		//-----------------------------------------
		
		if ( $this->request['username'] == "" )
		{
			$this->_revalidateForm('err_no_username');
			return;
		}
		
		$username	= $this->request['username'];
		$keyType	= IPSText::checkEmailAddress( $this->request['username'] ) ? 'email' : 'username';
		
		$member = IPSMember::load( $username, 'extendedProfile', $keyType );
		
		if ( ! $member['member_id'] )
		{
			/**
			 * Try the other key type just to be safe...
			 */
			$member = IPSMember::load( $username, 'extendedProfile', $keyType == 'email' ? 'username' : 'email' );
			
			if ( ! $member['member_id'] )
			{
				$this->_revalidateForm('err_no_username');
				return;
			}
		}

		//-----------------------------------------
		// Check in the DB for any validations
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'validating', 'where' => "member_id=" . intval($member['member_id']) ) );
		$this->DB->execute();
		
		if ( ! $val = $this->DB->fetch() )
		{
			$this->_revalidateForm('err_no_validations');
			return;
		}
		
		//-----------------------------------------
		// Which type is it then?
		//-----------------------------------------
		
		if ( $val['lost_pass'] == 1 )
		{
			IPSText::getTextClass('email')->getTemplate("lost_pass");
				
			IPSText::getTextClass('email')->buildMessage( array(
												'NAME'         => $member['members_display_name'],
												'THE_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=lostpass&do=sendform&uid=" . $member['member_id'] . "&aid=" . $val['vid'],
												'MAN_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=lostpass&do=sendform",
												'EMAIL'        => $member['email'],
												'ID'           => $member['member_id'],
												'CODE'         => $val['vid'],
												'IP_ADDRESS'   => $this->request['IP_ADDRESS'],
											  )
										);
										
			IPSText::getTextClass('email')->subject = $this->lang->words['lp_subject'].' '.$this->settings['board_name'];
			IPSText::getTextClass('email')->to      = $member['email'];
			
			IPSText::getTextClass('email')->sendMail();
		}
		else if ( $val['new_reg'] == 1 )
		{
			IPSText::getTextClass('email')->getTemplate("reg_validate");
					
			IPSText::getTextClass('email')->buildMessage( array(
												'THE_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=register&do=auto_validate&uid=" . $member['member_id'] . "&aid=" . $val['vid'],
												'NAME'         => $member['members_display_name'],
												'MAN_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=register&do=05",
												'EMAIL'        => $member['email'],
												'ID'           => $member['member_id'],
												'CODE'         => $val['vid'],
											  )
										);
										
			IPSText::getTextClass('email')->subject = $this->lang->words['email_reg_subj'] . " " . $this->settings['board_name'];
			IPSText::getTextClass('email')->to      = $member['email'];
			
			IPSText::getTextClass('email')->sendMail();
		}
		else if ( $val['email_chg'] == 1 )
		{
			IPSText::getTextClass('email')->getTemplate("newemail");
				
			IPSText::getTextClass('email')->buildMessage( array(
												'NAME'         => $member['members_display_name'],
												'THE_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=register&do=auto_validate&type=newemail&uid=" . $member['member_id'] . "&aid=" . $val['vid'],
												'ID'           => $member['member_id'],
												'MAN_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=register&do=07",
												'CODE'         => $val['vid'],
											  )
										);
										
			IPSText::getTextClass('email')->subject = $this->lang->words['ne_subject'] . ' ' . $this->settings['board_name'];
			IPSText::getTextClass('email')->to      = $member['email'];
			
			IPSText::getTextClass('email')->sendMail();
		}
		else
		{
			$this->_revalidateForm('err_no_validations');
			return;
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('register')->showRevalidated();
		
		$this->registry->output->setTitle( $this->lang->words['rv_title'] );
		$this->registry->output->addNavigation( $this->lang->words['rv_title'], '' );
	}
	
	/**
	 * Show the form to manually enter in validation info if auto-validation failed.
	 *
	 * @access	private
	 * @param 	string		Type of validation
	 * @param 	string 		Error messages
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _showManualForm($type='reg', $errors="")
	{
		//-----------------------------------------
		// In IPB3 this is handled by another section
		// If somehow a request comes in, send to correct location
		//-----------------------------------------
		
		if ( $type == 'lostpass' )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=global&section=lostpass&do=sendform&uid=' . $this->request['uid'] . '&aid=' . $this->request['aid'] );
		}
		else
		{
			$this->output     = $this->registry->getClass('output')->getTemplate('register')->showManualForm( $type );
		}
		
		$this->registry->output->setTitle( $this->lang->words['activation_form'] );
		$this->registry->output->addNavigation( $this->lang->words['activation_form'], '' );
	}
	
	
	/**
	 * Show coppa form.  This is the downloadable/printable form, and as such is just printed to the browser directly
	 *
	 * @access	private
	 * @return	void
	 */
	private function registerCoppaPermsForm()
	{
		/* Keith said to strip these out - it's all his fault :( */
		$this->settings[ 'coppa_address'] =  str_replace( array( '<br />', '<br>' ), '', $this->settings['coppa_address'] );
		$this->settings[ 'coppa_address'] =  nl2br( $this->settings['coppa_address']  );

		echo( $this->registry->output->getTemplate('register')->registerCoppaForm() );
		exit();
	}	
	
	/**
	 * This is the second page a COPPA submission hits.  Really, user hits this page and if birthday is older than 13
	 * we just continue with registration form.  Otherwise we show the coppa validation required message with link
	 * to download/print coppa compliance sheet for parents to fill in.
	 *
	 * @access	private
	 * @return	void
	 */
 	private function registerCoppaTwo()
	{
		if( ! $this->request['m'] OR ! $this->request['d'] OR ! $this->request['y'] )
		{
			$this->registry->output->showError( 'coppa_form_fill', 10119 );
		}
		
		$birthday	= mktime( 0, 0, 0, intval( $this->request['m'] ), intval( $this->request['d'] ), intval( $this->request['y'] ) );
		$coppa		= mktime( 0, 0, 0, date( "m" ), date ("d" ), date( "Y" ) - 13 );
		
		if( $birthday <= $coppa )
		{
			$this->registerForm();
			return;
		}
		
		IPSCookie::set( 'coppa', 'yes', 0, 1 );
		IPSCookie::set( 'coppabday', $this->request['m'] . '-' . $this->request['d'] . '-' . $this->request['y'], 0, 1 );

		$this->lang->words['coppa_form_text'] = str_replace( "<#FORM_LINK#>", "<a href='{$this->settings['base_url']}app=core&amp;module=global&amp;section=register&amp;do=12'>" . $this->lang->words['coppa_link_form'] . "</a>", $this->lang->words['coppa_form_text']);
		
		$this->output .= $this->registry->output->getTemplate('register')->registerCoppaTwo();
		
		$this->registry->output->setTitle( $this->lang->words['coppa_title'] );
		$this->registry->output->addNavigation( $this->lang->words['coppa_title'], '' );
 	}	
	
	/**
	 * First COPPA screen.  Shows form for visitor to enter birthday in and then submits to registerCoppaTwo
	 *
	 * @access	private
	 * @return	void
	 */
	private function registerCoppaStart()
	{
		$this->lang->words['coppa_form_text'] = str_replace( "<#FORM_LINK#>", "<a href='{$this->settings['base_url']}app=core&amp;module=global&amp;section=register&amp;do=12'>{$this->lang->words['coppa_link_form']}</a>", $this->lang->words['coppa_form_text']);
		
		$this->output .= $this->registry->output->getTemplate('register')->registerCoppaStart();
		
		$this->registry->output->setTitle( $this->lang->words['coppa_title'] );
		$this->registry->output->addNavigation( $this->lang->words['coppa_title'], '' );
 	}
	
	/**
	 * Validation completion.  This is the action hit when a user clicks a validation link from their email for
	 * lost password, email change and new registration.
	 *
	 * @access	private
	 * @return	void
	 */
	private function _autoValidate()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$in_user_id			= intval(trim(urldecode($this->request['uid'])));
		$in_validate_key	= substr( IPSText::alphanumericalClean( urldecode( $this->request['aid'] ) ), 0, 32 );
		$in_type			= trim($this->request['type']);
		$in_type			= $in_type ? $in_type : 'reg';

		//-----------------------------------------
		// Attempt to get the profile of the requesting user
		//-----------------------------------------
		
		$member = IPSMember::load( $in_user_id, 'members' );
			
		if ( ! $member['member_id'] )
		{
			$this->_showManualForm( $in_type, 'reg_error_validate' );
			return;
		}
		
		//-----------------------------------------
		// Get validating info..
		//-----------------------------------------
		
		if ( $in_type == 'lostpass' )
		{
			$validate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id=' . $in_user_id . " AND lost_pass=1" ) );
		}
		else if ( $in_type == 'newemail' )
		{
			$validate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id=' . $in_user_id . " AND email_chg=1" ) );
		}
		else
		{
			$validate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id=' . $in_user_id ) );
		}
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $validate['member_id'] )
		{
			$this->registry->output->showError( 'no_validate_key', 10120 );
		}
		
		if ( ( $validate['new_reg'] == 1 ) && ($this->settings['reg_auth_type'] == "admin" ) ) 
		{ 
			$this->registry->output->showError( 'validate_admin_turn', 10121 );
		} 

		if ( $validate['vid'] != $in_validate_key )
		{
			$this->registry->output->showError( 'validation_key_invalid', 10122 );
		}
		
		//-----------------------------------------
		// Captcha (from posted form, not GET)
		//-----------------------------------------
		
		if ( $this->settings['use_captcha'] AND $this->request['uid'] )
		{
			if ( $this->registry->getClass('class_captcha')->validate( $this->request['captcha_unique_id'], $this->request['captcha_input'] ) !== TRUE )
			{
				$this->_showManualForm( $in_type, 'reg_error_anti_spam' );
				return;
			}
		}
		//-----------------------------------------
		// REGISTER VALIDATE
		//-----------------------------------------
		
		if ( $validate['new_reg'] == 1 )
		{
			if ( ! $validate['real_group'] )
			{
				$validate['real_group'] = $this->settings['member_group'];
			}
			
			//-----------------------------------------
			// SELF-VERIFICATION...
			//-----------------------------------------
			
			if ( $this->settings['reg_auth_type'] != 'admin_user' )
			{
				IPSMember::save( $member['member_id'], array( 'members' => array( 'member_group_id' => $validate['real_group'] ) ) );
				
				/* Reset newest member */
				$stat_cache	 = $this->caches['stats'];
				
				if( $member['members_display_name'] AND $member['member_id'] )
				{
					$stat_cache['last_mem_name']	= $member['members_display_name'];
					$stat_cache['last_mem_id']		= $member['member_id'];
				}

				$stat_cache['mem_count'] += 1;
				
				$this->cache->setCache( 'stats', $stat_cache, array( 'array' => 1, 'deletefirst' => 0 ) );
				
				//-----------------------------------------
				// Remove "dead" validation
				//-----------------------------------------

				$this->DB->delete( 'validating', "vid='" . $validate['vid'] . "'" );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=core&module=global&section=login&do=autologin&fromreg=1' );
			}
			
			//-----------------------------------------
			// ADMIN-VERIFICATION...
			//-----------------------------------------
			
			else
			{
				//-----------------------------------------
				// Update DB row...
				//-----------------------------------------
				
				$this->DB->update( 'validating', array( 'user_verified' => 1 ), 'vid="' . $validate['vid'] . '"' );
				
				//-----------------------------------------
				// Print message
				//-----------------------------------------
				
				$this->registry->output->setTitle( $this->lang->words['validation_complete'] );
				
				$this->output = $this->registry->getClass('output')->getTemplate('register')->showPreview( $member );
			}
		}
		
		//-----------------------------------------
		// LOST PASS VALIDATE
		//-----------------------------------------
		
		else if ( $validate['lost_pass'] == 1 )
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
			
	        //-----------------------------------------
	    	// Load handler...
	    	//-----------------------------------------
	    	
	    	require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
	    	$this->han_login           =  new han_login( $this->registry );
	    	$this->han_login->init();
	    	$this->han_login->changePass( $member['email_address'], md5( $new_pass ) );
	    	
	    	if ( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
	    	{
				$this->registry->output->showError( 'lostpass_external_fail', 2015, true );
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

			$this->registry->output->setTitle( $this->lang->words['validation_complete'] );
			
			//-----------------------------------------
			// Remove "dead" validation
			//-----------------------------------------
			
			$this->DB->delete( 'validating', "vid='" . $validate['vid'] . "' OR (member_id={$member['member_id']} AND lost_pass=1)" );

			$this->output = $this->registry->getClass('output')->getTemplate('register')->showLostPassWaitRandom( $member );
		}
		
		//-----------------------------------------
		// EMAIL ADDY CHANGE
		//-----------------------------------------
		
		else if ( $validate['email_chg'] == 1 )
		{
			if ( !$validate['real_group'] )
			{
				$validate['real_group'] = $this->settings['member_group'];
			}
			
			IPSMember::save( $member['member_id'], array( 'members' => array( 'member_group_id' => intval($validate['real_group']) ) ) );

			IPSCookie::set( "member_id", $member['member_id']		, 1 );
			IPSCookie::set( "pass_hash", $member['member_login_key'], 1 );
			
			//-----------------------------------------
			// Remove "dead" validation
			//-----------------------------------------
			
			$this->DB->delete( 'validating', "vid='" . $validate['vid'] . "' OR (member_id={$member['member_id']} AND email_chg=1)" );
			
			$this->registry->output->silentRedirect( $this->settings['base_url'].'&app=core&module=global&section=login&do=autologin&fromemail=1' );
		}
	}
	
	/**
	 * Displays the registration form
	 *
	 * @access	public
	 * @param	array 	$form_errors
	 * @return	void
	 */
	public function registerForm( $form_errors=array() )
	{
		/* INIT */
		$final_errors = array();

		if( $this->settings['no_reg'] == 1 )
		{
			$this->registry->output->showError( 'registration_disabled', 10123 );
		}
		
		$coppa = IPSCookie::get( 'coppa' );
		
		if( $coppa == 'yes' )
		{
			$this->registry->output->showError( 'awaiting_coppa', 10124 );
		}
		
		$this->settings[ 'username_errormsg'] =  str_replace( '{chars}', $this->settings['username_characters'], $this->settings['username_errormsg']  );
		
		/* Read T&Cs yet? */
		if( ! $this->request['termsread'] )
		{	
			if( $this->memberData['member_id'] )
			{
		    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
		    	$this->han_login =  new han_login( $this->registry );
		    	$this->han_login->init();
		    	
				//-----------------------------------------
				// Set some cookies
				//-----------------------------------------
				
				IPSCookie::set( "member_id" , "0"  );
				IPSCookie::set( "pass_hash" , "0"  );
				IPSCookie::set( "anonlogin" , "-1" );
				
				if ( is_array($_COOKIE) )
		 		{
		 			foreach( $_COOKIE as $cookie => $value)
		 			{
		 				if ( stripos( $cookie, $this->settings['cookie_id']."ipbforum" ) !== false )
		 				{
		 					IPSCookie::set( str_replace( $this->settings['cookie_id'], "", $match[0] ) , '-', -1 );
		 				}
		 			}
		 		}
		
				//-----------------------------------------
				// Logout callbacks...
				//-----------------------------------------
				
				$this->han_login->logoutCallback();
				
				//-----------------------------------------
				// Do it..
				//-----------------------------------------
				
				$this->member->sessionClass()->convertMemberToGuest();

				list( $privacy, $loggedin ) = explode( '&', $this->memberData['login_anonymous'] );
		
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'login_anonymous' => "{$privacy}&0",
																								  'last_activity'   => time() ) ) );
			}
			
			/* Continue */
			$cache = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='reg_rules'" ) );
			
			$text  = $cache['conf_value'] ? $cache['conf_value'] : $cache['conf_default'];
			
			/* Load the Parser */
			IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'global';
			
			$text	= IPSText::getTextClass('bbcode')->preDbParse( $text );
			$text	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $text );
			
			$this->registry->output->setTitle( $this->lang->words['registration_form'] );
			$this->registry->output->addNavigation( $this->lang->words['registration_form'], '' );

			$this->output .= $this->registry->output->getTemplate('register')->registerShowTerms( $text, $coppa );
			return;
		}
		else
		{
			/* Did we agree to the t&c? */
			if( ! $this->request['agree_to_terms'] )
			{
				$this->registry->output->showError( 'must_agree_to_terms', 10125 );
			}
    	}

		/* Do we have another URL that one needs to visit to register? */
		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( $r['login_register_url'] )
			{
				$this->registry->output->silentRedirect( $r['login_register_url'] );
				exit();
			}
		}

		/* Continue... */
		if( $this->settings['reg_auth_type'] )
		{
			if( $this->settings['reg_auth_type'] == 'admin_user' OR $this->settings['reg_auth_type'] == 'user' )
			{
				$this->lang->words['std_text'] .= "<br />" . $this->lang->words['email_validate_text'];
			}
			
			/* User then admin? */
			if( $this->settings['reg_auth_type'] == 'admin_user' )
			{
				$this->lang->words['std_text'] .= "<br />" . $this->lang->words['user_admin_validation'];
			}
			
			if( $this->settings['reg_auth_type'] == 'admin' )
			{
				$this->lang->words['std_text'] .= "<br />" . $this->lang->words['just_admin_validation'];
			}
		}
    	
		$captchaHTML	= '';
		$qandaHTML		= '';
		$this->cache->updateCacheWithoutSaving('_hasStep3', 0);
		
		/* Q and A Challenge */
		if( $this->settings['registration_qanda'] )
		{
			// Grab a random question...
			$question	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'question_and_answer', 'order' => 'rand()', 'limit' => array(1) ) );
			
			if( count($question) )
			{
				$qandaHTML	= $this->registry->output->getTemplate('global_other')->questionAndAnswer( $question );
			}
		}
    	
		/* Custom Profile Fields */
		$custom_fields_out = array( 'required', 'optional' );
				
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$custom_fields = new customProfileFields();
		
		$custom_fields->member_data = $member;
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToEdit( 'register' );
		
		if ( count( $custom_fields->out_fields ) )
		{
			$this->cache->updateCacheWithoutSaving('_hasStep3', 1);
			
			foreach( $custom_fields->out_fields as $id => $form_element )
	    	{
				if ( $custom_fields->cache_data[ $id ]['pf_not_null'] == 1 )
				{
					$ftype = 'required';
				}
				else
				{
					$ftype = 'optional';
				}
				
				$custom_fields_out[$ftype][] = array( 
														'name'  => $custom_fields->field_names[ $id ], 
														'desc'  => $custom_fields->field_desc[ $id ], 
														'field' => $form_element, 
														'id'    => $id,
														'error' => $error,
														'type'	=> $custom_fields->cache_data[ $id ]['pf_type']
														);
	    	}
		}
		
		/* CAPTCHA */
		if( $this->settings['bot_antispam'] )
		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();
		}
		
		$this->registry->output->setTitle( $this->lang->words['registration_form'] );
		$this->registry->output->addNavigation( $this->lang->words['registration_form'], '' );
		
		/* Other errors */
		$final_errors = array( 'username' => NULL, 'dname' => NULL, 'password' => NULL, 'email' => NULL );

		foreach( array( 'username', 'dname', 'password', 'email' ) as $thing )
		{
			if ( isset($form_errors[ $thing ]) AND is_array( $form_errors[ $thing ] ) AND count( $form_errors[ $thing ] ) )
			{
				$final_errors[ $thing ] = implode( "<br />", $form_errors[ $thing ] );
			}
		}
		
		$this->request['UserName'] = 				$this->request['UserName']				? $this->request['UserName'] 				: '' ;
		$this->request['PassWord'] = 				$this->request['PassWord'] 				? $this->request['PassWord'] 				: '' ;
		$this->request['EmailAddress'] = 			$this->request['EmailAddress'] 			? $this->request['EmailAddress'] 			: '' ;
		$this->request['EmailAddress_two'] = 		$this->request['EmailAddress_two']		? $this->request['EmailAddress_two']		: '' ;
		$this->request['PassWord_Check'] = 			$this->request['PassWord_Check'] 		? $this->request['PassWord_Check'] 			: '' ;
		$this->request['members_display_name'] = 	$this->request['members_display_name']	? $this->request['members_display_name']	: '' ;
		$this->request['time_offset'] = 			$this->request['time_offset'] 			? $this->request['time_offset'] 			: '' ;
		$this->request['allow_member_mail'] = 		$this->request['allow_member_mail']		? $this->request['allow_member_mail']		: '' ;
		$this->request['dst'] = 					$this->request['dst']					? $this->request['dst']						: '' ;
		
		/* Time zone... */
		$this->registry->class_localization->loadLanguageFile( array( 'public_usercp' ), 'core' );
		
		$time_select		= array();
		
		foreach( $this->lang->words as $k => $v )
		{
			if( strpos( $k, "time_" ) === 0 )
			{
				$k				= str_replace( "time_", '', $k );

				if( preg_match( "/^[\-\d\.]+$/", $k ) )
				{
					$time_select[ $k ]	= $v;
				}
			}
		}
		
		ksort( $time_select );
		
		/* set default.. */
		$this->request['time_offset'] = ( $this->request['time_offset'] ) ? $this->request['time_offset'] : $this->settings['time_offset'];
		
		/* Need username? */
		$uses_name	= false;
		
		foreach( $this->cache->getCache('login_methods') as $method )
		{
			if( $method['login_user_id'] == 'username' )
			{
				$uses_name	= true;
			}
		}

 		/* Get form HTML */
		$this->output .= $this->registry->output->getTemplate('register')->registerForm(
																					$form_errors['general'],
																					array( 
																							'TEXT'			=> $this->lang->words['std_text'], 
																							'coppa_user'	=> $coppa,
																							'captchaHTML'   => $captchaHTML,
																							'qandaHTML'		=> $qandaHTML,
																							'requireName'	=> $uses_name,
																						), 
																					$final_errors, 
																					$time_select,
																					$custom_fields_out );
		
		/* Run the member sync module */
		IPSLib::runMemberSync( 'onRegisterForm' );
	}
	
	/**
	 * Processes the registration form
	 *
	 * @access	public
	 * @return	void
	 */
 	public function registerProcessForm()
 	{
		$form_errors			= array();
		$coppa					= ( $this->request['coppa_user'] == 1 ) ? 1 : 0;
		$in_password			= trim( $this->request['PassWord'] );
		$in_email				= strtolower( trim( $this->request['EmailAddress'] ) );
		$_SFS_FOUND             = FALSE;

		/* Check */
		if( $this->settings['no_reg'] == 1 )
    	{
    		$this->registry->output->showError( 'registration_disabled', 2016, true );
    	}
    	
		/* Custom profile field stuff */
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$custom_fields = new customProfileFields();
		
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToSave( $this->request, 'register' );		

		/* Check */
		if( $custom_fields->error_messages )
		{
			$form_errors['general']	= $custom_fields->error_messages;
		}
		
		/* Check the email address */		
		if ( ! $in_email OR strlen( $in_email ) < 6 OR !IPSText::checkEmailAddress( $in_email ) )
		{
			$form_errors['email'][$this->lang->words['err_invalid_email']] = $this->lang->words['err_invalid_email'];
		}
		
		if( trim($this->request['PassWord_Check']) != $in_password )
		{
			$form_errors['password'][$this->lang->words['passwords_not_match']] = $this->lang->words['passwords_not_match'];
		}
		
		/* Test email address */
		$this->request['EmailAddress_two'] = strtolower( trim( $this->request['EmailAddress_two'] ) );
		$this->request['EmailAddress']     = strtolower( trim( $this->request['EmailAddress'] ) );
		
		if( !IPSText::checkEmailAddress( $this->request['EmailAddress_two'] ) )
		{
			$form_errors['email'][$this->lang->words['reg_error_email_invalid']] = $this->lang->words['reg_error_email_invalid'];
		}
		else
		{		
			if ( $in_email AND $this->request['EmailAddress_two'] != $in_email )
			{
				$form_errors['email'][$this->lang->words['reg_error_email_nm']] = $this->lang->words['reg_error_email_nm'];
			}
		}
		
		/* Need username? */
		$uses_name	= false;
		
		foreach( $this->cache->getCache('login_methods') as $method )
		{
			if( $method['login_user_id'] == 'username' )
			{
				$uses_name	= true;
			}
		}
		
		if( !$uses_name )
		{
			$_REQUEST['UserName']		= $_REQUEST['members_display_name'];
			$this->request['UserName']	= $this->request['members_display_name'];
		}
		
		/* Check the username */
		$user_check = IPSMember::getFunction()->cleanAndCheckName( $this->request['UserName'], array(), 'name' );
		
		if( $this->settings['auth_allow_dnames'] )
		{
			$disp_check = IPSMember::getFunction()->cleanAndCheckName( $this->request['members_display_name'], array(), 'members_display_name' );
		}

		if( is_array( $user_check['errors'] ) && count( $user_check['errors'] ) )
		{
			foreach( $user_check['errors'] as $key => $error )
			{
				$form_errors[ $key ][]	= $error;
			}
		}
		
		if( $this->settings['auth_allow_dnames'] AND is_array( $disp_check['errors'] ) && count( $disp_check['errors'] ) )
		{
			foreach( $disp_check['errors'] as $key => $error )
			{
				$form_errors[ $key ][]	= $error;
			}
		}

		/* CHECK 1: Any errors (missing fields, etc)? */
		if( count( $form_errors ) )
		{
			$this->registerForm( $form_errors );
			return;
		}
		
		/* Is this email addy taken? */
		if( IPSMember::checkByEmail( $in_email ) == TRUE )
		{
			$form_errors['email'][$this->lang->words['reg_error_email_taken']] = $this->lang->words['reg_error_email_taken'];
		}
		
		/* Load handler... */
		require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
		$this->han_login           =  new han_login( $this->registry );
		$this->han_login->init();
		$this->han_login->emailExistsCheck( $in_email );

		if( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'EMAIL_NOT_IN_USE' )
		{
			$form_errors['email'][$this->lang->words['reg_error_email_taken']] = $this->lang->words['reg_error_email_taken'];
		}
		
		/* Are they banned [EMAIL]? */
		if ( IPSMember::isBanned( 'email', $in_email ) === TRUE )
		{
			$form_errors['email'][$this->lang->words['reg_error_email_ban']] = $this->lang->words['reg_error_email_ban'];
		}
		
		/* Check the CAPTCHA */
		if ( $this->settings['bot_antispam'] )
		{
			if ( $this->registry->getClass('class_captcha')->validate() !== TRUE )
			{
				$form_errors['general'][$this->lang->words['err_reg_code']] = $this->lang->words['err_reg_code'];
			}
		}
		
		/* Check the Q and A */
		if( $this->settings['registration_qanda'] )
		{
			$qanda	= intval($this->request['qanda_id']);
			$pass	= false;
			
			if( $qanda )
			{
				$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'question_and_answer', 'where' => 'qa_id=' . $qanda ) );
				
				if( $data['qa_id'] )
				{
					$answers	 = explode( "\n", str_replace( "\r", "", $data['qa_answers'] ) );
					
					if( count($answers) )
					{
						foreach( $answers as $answer )
						{
							if( strtolower($answer) == strtolower($this->request['qa_answer']) )
							{
								$pass	= true;
								break;
							}
						}
					}
				}
			}
			else
			{
				//-----------------------------------------
				// Do we have any questions?
				//-----------------------------------------
				
				$data	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as questions', 'from' => 'question_and_answer' ) );
				
				if( !$data['questions'] )
				{
					$pass	= true;
				}
			}
			
			if( !$pass )
			{
				$form_errors['general'][$this->lang->words['err_q_and_a']] = $this->lang->words['err_q_and_a'];
			}
		}

		/* CHECK 2: Any errors ? */		
		if ( count( $form_errors ) )
		{
			$this->registerForm( $form_errors );
			return;
		}
		
		/* Build up the hashes */
		$mem_group = $this->settings['member_group'];
		
		/* Are we asking the member or admin to preview? */
		if( $this->settings['reg_auth_type'] )
		{
			$mem_group = $this->settings['auth_group'];
		}
		else if ($coppa == 1)
		{
			$mem_group = $this->settings['auth_group'];
		}
				
		/* Create member */
		$member = array(
						 'name'						=> $this->request['UserName'],
						 'password'					=> $in_password,
						 'members_display_name'		=> $this->settings['auth_allow_dnames'] ? $this->request['members_display_name'] : $this->request['UserName'],
						 'email'					=> $in_email,
						 'member_group_id'			=> $mem_group,
						 'joined'					=> time(),
						 'ip_address'				=> $this->member->ip_address,
						 'time_offset'				=> $this->request['time_offset'],
						 'coppa_user'				=> $coppa,
						 'members_auto_dst'			=> intval($this->request['dst']),
						 'allow_admin_mails'		=> intval( $this->request['allow_admin_mail'] ),
						 'hide_email'				=> $this->request['allow_member_mail'] ? 0 : 1,
					   );
	
		/* Spam Service */
		$spamCode = 0;
		
		if( $this->settings['spam_service_enabled'] && $this->settings['spam_service_api_key'] )
		{
			/* Query the service */
			$spamCode = IPSMember::querySpamService( $in_email );
        
			/* Action to perform */
			$action = $this->settings[ 'spam_service_action_' . $spamCode ];
        
			/* Perform Action */
			switch( $action )
			{
				/* Proceed with registraction */
				case 1:
				break;
        
				/* Flag for admin approval */
				case 2:
        			$member['member_group_id'] = $this->settings['auth_group'];
					$this->settings['reg_auth_type'] = 'admin';
				break;
        
				/* Approve the account, but ban it */
				case 3:
        			$member['member_banned'] = 1;
					$member['member_group_id'] = $this->settings['banned_group'];
					$this->settings['reg_auth_type'] = '';
				break;
			}
		}
				
		//-----------------------------------------
		// Create the account
		//-----------------------------------------
		
		$member	= IPSMember::create( array( 'members' => $member, 'pfields_content' => $this->request ) );
				
		//-----------------------------------------
		// Login handler create account callback
		//-----------------------------------------
		
   		$this->han_login->createAccount( array(	'email'			=> $member['email'],
												'joined'		=> $member['joined'],
												'password'		=> $in_password,
												'ip_address'	=> $this->member->ip_address,
												'username'		=> $member['members_display_name'],
   										)		);

		//-----------------------------------------
		// We'll just ignore if this fails - it shouldn't hold up IPB anyways
		//-----------------------------------------
		
		/*if ( $han_login->return_code AND ( $han_login->return_code != 'METHOD_NOT_DEFINED' AND $han_login->return_code != 'SUCCESS' ) )
		{
			$this->registry->output->showError( 'han_login_create_failed', 2017, true );
		}*/
   		
		//-----------------------------------------
		// Validation
		//-----------------------------------------
		
		$validate_key = md5( IPSLib::makePassword() . time() );
		$time         = time();
		
		if( $coppa != 1 )
		{
			if( ( $this->settings['reg_auth_type'] == 'user' ) or ( $this->settings['reg_auth_type'] == 'admin' ) or ( $this->settings['reg_auth_type'] == 'admin_user' ) )
			{
				//-----------------------------------------
				// We want to validate all reg's via email,
				// after email verificiation has taken place,
				// we restore their previous group and remove the validate_key
				//-----------------------------------------
				
				$this->DB->insert( 'validating', array(
													  'vid'         => $validate_key,
													  'member_id'   => $member['member_id'],
													  'real_group'  => /*$this->settings['subsm_enforce'] ? $this->settings['subsm_nopkg_group'] :*/ $this->settings['member_group'],
													  'temp_group'  => $this->settings['auth_group'],
													  'entry_date'  => $time,
													  'coppa_user'  => $coppa,
													  'new_reg'     => 1,
													  'ip_address'  => $member['ip_address']
											)       );
				
				if( $this->settings['reg_auth_type'] == 'user' OR $this->settings['reg_auth_type'] == 'admin_user' )
				{
					IPSText::getTextClass('email')->getTemplate("reg_validate");

					IPSText::getTextClass('email')->buildMessage( array(
														'THE_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=register&do=auto_validate&uid=" . urlencode( $member['member_id'] ) . "&aid=" . urlencode( $validate_key ),
														'NAME'         => $member['members_display_name'],
														'MAN_LINK'     => $this->settings['base_url'] . "app=core&module=global&section=register&do=05",
														'EMAIL'        => $member['email'],
														'ID'           => $member['member_id'],
														'CODE'         => $validate_key,
													  )
												);
												
					IPSText::getTextClass('email')->subject = $this->lang->words['new_registration_email'] . $this->settings['board_name'];
					IPSText::getTextClass('email')->to      = $member['email'];
					
					IPSText::getTextClass('email')->sendMail();
					
					$this->output     = $this->registry->output->getTemplate('register')->showAuthorize( $member );
					
				}
				else if( $this->settings['reg_auth_type'] == 'admin' )
				{
					$this->output     = $this->registry->output->getTemplate('register')->showPreview( $member );
				}
				
				if( $this->settings['new_reg_notify'] )
				{
					$date = $this->registry->class_localization->getDate( time(), 'LONG', 1 );
					
					IPSText::getTextClass('email')->getTemplate( 'admin_newuser' );
				
					IPSText::getTextClass('email')->buildMessage( array(
														'DATE'         => $date,
														'MEMBER_NAME'  => $member['members_display_name'],
													  )
												);
												
					IPSText::getTextClass('email')->subject = $this->lang->words['new_registration_email1'] . $this->settings['board_name'];
					IPSText::getTextClass('email')->to      = $this->settings['email_in'];
					IPSText::getTextClass('email')->sendMail();
				}
				
				$this->registry->output->setTitle( $this->lang->words['reg_success'] );
				$this->registry->output->addNavigation( $this->lang->words['nav_reg'], '' );
			}
			else
			{
				/* We don't want to preview, or get them to validate via email. */
				$stat_cache						= $this->caches['stats'];
				
				if( $member['members_display_name'] AND $member['member_id'] )
				{
					$stat_cache['last_mem_name']	= $member['members_display_name'];
					$stat_cache['last_mem_id']		= $member['member_id'];
				}

				$stat_cache['mem_count']		+= 1;
				
				$this->cache->setCache( 'stats', $stat_cache, array( 'array' => 1, 'deletefirst' => 0 ) );
				
				if( $this->settings['new_reg_notify'] )
				{
					$date = $this->registry->class_localization->getDate( time(), 'LONG', 1 );
					
					IPSText::getTextClass('email')->getTemplate( 'admin_newuser' );
				
					IPSText::getTextClass('email')->buildMessage( array(
														'DATE'         => $date,
														'MEMBER_NAME'  => $member['members_display_name'],
													  )
												);
												
					IPSText::getTextClass('email')->subject = $this->lang->words['new_registration_email1'] . $this->settings['board_name'];
					IPSText::getTextClass('email')->to      = $this->settings['email_in'];
					IPSText::getTextClass('email')->sendMail();
				}

				IPSCookie::set( 'pass_hash'   , $member['member_login_key'], 1);
				IPSCookie::set( 'member_id'   , $member['member_id']       , 1);
				
				//-----------------------------------------
				// Fix up session
				//-----------------------------------------

				$privacy    = $this->request['Privacy'] ? 1 : 0;
				
				if( $member['g_hide_online_list'] )
				{
					$privacy	= 1;
				}
		
				$this->member->sessionClass()->convertGuestToMember( array( 'member_name'	  => $member['members_display_name'],
																  			'member_id'	  	  => $member['member_id'],
																			'member_group'  => $member['member_group_id'],
																			'login_type'	  => $privacy ) );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=core&module=global&section=login&do=autologin&fromreg=1');
			}
		}
		else
		{
			/* This is a COPPA user, so lets tell them they registered OK and redirect to the form. */
			$this->DB->insert( 'validating', array (
												  'vid'         => $validate_key,
												  'member_id'   => $member['member_id'],
												  'real_group'  => $this->settings['member_group'],
												  'temp_group'  => $this->settings['auth_group'],
												  'entry_date'  => $time,
												  'coppa_user'  => $coppa,
												  'new_reg'     => 1,
												  'ip_address'  => $member['ip_address']
										)       );
			
			$this->registry->output->redirectScreen( $this->lang->words['cp_success'], $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=12' );
		}
	}
}