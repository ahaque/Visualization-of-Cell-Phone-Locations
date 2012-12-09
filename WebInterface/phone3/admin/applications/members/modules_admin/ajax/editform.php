<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member property updater (AJAX)
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Revision: 5027 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_members_ajax_editform extends ipsAjaxCommand 
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
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ), 'members' );
		
    	switch( $this->request['do'] )
    	{
			default:
			case 'show':
				$this->show();
			break;
			case 'save_display_name':
				$this->save_member_name( 'members_display_name' );
			break;
			case 'save_name':
				$this->save_member_name( 'name' );
			break;
			case 'save_password':
				$this->save_password();
			break;
			case 'save_email':
				$this->save_email();
			break;
			case 'remove_photo':
				$this->remove_photo();
			break;
    	}
	}

	/**
	 * Remove user's photo
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function remove_photo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
																	
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['m_noid'] );
			exit();
		}
		
		//-----------------------------------------
		// Allowed to upload pics for administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo_admin', 'members', 'members' ) )
		{
			$this->returnJsonError( $this->lang->words['m_editadmin'] );
			exit();
		}

		//-----------------------------------------
		// Sort out upload dir
		//-----------------------------------------

		/* Fix for bug 5075 */
		$this->settings[ 'upload_dir'] =  str_replace( '&#46;', '.', $this->settings['upload_dir']  );		

		$upload_path  = $this->settings['upload_dir'];

		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( file_exists( $upload_path . "/profile" ) )
		{
			# Set path and dir correct
			$upload_path .= "/profile";
			$upload_dir   = "profile/";
		}
		
		IPSMember::getFunction()->removeUploadedPhotos( $member_id, $upload_path );
		
		IPSMember::save( $member_id, array( 'extendedProfile' => array( 'pp_main_photo'   => '',
												  				   	 	'pp_main_width'   => '',
																	   	'pp_main_height'  => '',
																		'pp_thumb_photo'  => '',
																		'pp_thumb_width'  => '',
																		'pp_thumb_height' => '',
																	 ) ) );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf($this->lang->words['m_imgremlog'], $member_id ) );

		$member = IPSMember::load( $member_id );
		$member	= IPSMember::buildDisplayData( $member, 0 );

		//-----------------------------------------
		// Return
		//-----------------------------------------
		

		$_string = <<<EOF
		{
			'success'       	: true,
			'pp_main_photo' 	: "{$member['pp_main_photo']}",
			'pp_main_width'		: "{$member['pp_main_width']}",
			'pp_main_height'	: "{$member['pp_main_height']}"
		}

EOF;
		$this->returnString( $_string );
	}
	
	/**
	 * Change a member's email address
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function save_email()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id	= intval( $this->request['member_id'] );
		$email		= trim( $this->request['email'] );
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
																	
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['m_noid'] );
			exit();
		}
		
		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin', 'members', 'members' ) )
		{
			$this->returnJsonError( $this->lang->words['m_editadmin'] );
			exit();
		}
		
		//-----------------------------------------
		// Is this email addy taken? CONVERGE THIS??
		//-----------------------------------------
		
		$email_check = IPSMember::load( strtolower($email) );
		
		if ( $email_check['member_id'] AND $email_check['member_id'] != $member_id )
		{
			$this->returnJsonError( $this->lang->words['m_emailalready'] );
			exit();
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$han_login		=  new han_login( $this->registry );
    	$han_login->init();
    	$han_login->changeEmail( trim( strtolower( $member['email'] ) ), trim( strtolower( $email ) ) );
    	
    	//-----------------------------------------
    	// We don't want to die just from a Converge error
    	//-----------------------------------------
    	
    	/*if ( $han_login->return_code AND ( $han_login->return_code != 'METHOD_NOT_DEFINED' AND $han_login->return_code != 'SUCCESS' ) )
	    {
			$this->returnJsonError( $this->lang->words['m_emailalready'] );
			exit();
    	}*/
    	
		//-----------------------------------------
		// Update member
		//-----------------------------------------
		
		IPSMember::save( $member_id, array( 'core' => array( 'email' => strtolower( $email ) ) ) );
		
		IPSLib::runMemberSync( 'onEmailChange', $member_id, strtolower( $email ) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_emailchangelog'], $member['email'], $email, $member_id ) );
		
		$_string = <<<EOF
		{
			'success'  : true,
			'email'    : "{$email}"
		}
		
EOF;
		$this->returnString( $_string );
	}
	
	/**
	 * Change a member's password
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function save_password()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		$password		= $this->request['password'];
		$password2		= $this->request['password2'];
		$new_key		= intval( $this->request['new_key'] );
		$new_salt		= intval( $this->request['new_salt'] );
		$salt			= str_replace( '\\', "\\\\", IPSMember::generatePasswordSalt(5) );
		$key			= IPSMember::generateAutoLoginKey();
		$md5_once		= md5( trim($password) );

		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $password OR ! $password2 )
		{
			$this->returnJsonError( $this->lang->words['password_nogood'] );
			exit();
		}
		
		if ( $password != $password2 )
		{
			$this->returnJsonError( $this->lang->words['m_passmatch'] );
			exit();
		}

		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
		
		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin', 'members', 'members' ) )
		{
			$this->returnJsonError( $this->lang->words['m_editadmin'] );
			exit();
		}
		
		//-----------------------------------------
		// Check Converge: Password
		//-----------------------------------------
    	
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$han_login	=  new han_login( $this->registry );
    	$han_login->init();
    	$han_login->changePass( $member['email'], $md5_once );
    	
    	/*if ( $han_login->return_code != 'METHOD_NOT_DEFINED' AND $han_login->return_code != 'SUCCESS' )
    	{
			$this->returnJsonError( $this->lang->words['m_passchange']);
			exit();
    	}*/
		
		//-----------------------------------------
		// Local DB
		//-----------------------------------------
		
		$update = array();
		
		if( $new_salt )
		{
			$update['members_pass_salt']	= $salt;
		}
		
		if( $new_key )
		{
			$update['member_login_key']		= $key;
		}
		
		if( count($update) )
		{
			IPSMember::save( $member_id, array( 'core' => $update ) );
		}
		
		IPSMember::updatePassword( $member_id, $md5_once );
		IPSLib::runMemberSync( 'onPassChange', $member_id, $password );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_passlog'], $member_id ) );
		
		$_string = <<<EOF
		{
			'success'  : true,
			'password' : "*************"
		}
		
EOF;
		$this->returnString( $_string );
	}
	
	/**
	 * Update a user's login or display name
	 *
	 * @access	protected
	 * @param	string		Field to update
	 * @return	void		[Outputs to screen]
	 */
	protected function save_member_name( $field='members_display_name' )
	{
		$member_id	= intval( $this->request['member_id'] );
		
		$member = IPSMember::load( $member_id );
		
		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin', 'members', 'members' ) )
		{
			$this->returnJsonError( $this->lang->words['m_editadmin'] );
			exit();
		}
		
		if ( $field == 'members_display_name' )
		{
			$display_name	= $this->convertAndMakeSafe( $_POST['display_name'], 1 );
    		$display_name	= str_replace("&#43;", "+", $display_name );
    	}
		else
		{
			$display_name	= $this->convertAndMakeSafe( $_POST['name'], 1 );
    		$display_name	= str_replace("&#43;", "+", $display_name );
    		
			$display_name = str_replace( '|', '&#124;' , $display_name );
			$display_name = trim( preg_replace( "/\s{2,}/", " ", $display_name ) );    		
		}
		
		if ( $this->settings['strip_space_chr'] )
    	{
    		// use hexdec to convert between '0xAD' and chr
			$display_name          = IPSText::removeControlCharacters( $display_name );
		}
		
		if ( $field == 'members_display_name' AND preg_match( "#[\[\];,\|]#", str_replace('&#39;', "'", str_replace('&amp;', '&', $members_display_name) ) ) )
		{
			$this->returnJsonError($this->lang->words['m_displaynames']);
		}
		
		try
		{
			if ( IPSMember::getFunction()->updateName( $member_id, $display_name, $field ) === TRUE )
			{
				if ( $field == 'members_display_name' )
				{
					ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_dnamelog'], $member['members_display_name'], $display_name ));
				}
				else
				{
					ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_namelog'], $member['name'], $display_name ) );
					
					//-----------------------------------------
					// If updating a name, and display names 
					//	disabled, update display name too
					//-----------------------------------------
					
					if( !ipsRegistry::$settings['auth_allow_dnames'] )
					{
						IPSMember::getFunction()->updateName( $member_id, $display_name, 'members_display_name' );
					}

					//-----------------------------------------
					// I say, did we choose to email 'dis member?
					//-----------------------------------------

					if ( $this->request['send_email'] == 1 )
					{
						//-----------------------------------------
						// By golly, we did!
						//-----------------------------------------

						$msg = trim( IPSText::stripslashes( nl2br( $_POST['email_contents'] ) ) );

						$msg = str_replace( "{old_name}", $member['name'], $msg );
						$msg = str_replace( "{new_name}", $display_name  , $msg );
						$msg = str_replace( "<#BOARD_NAME#>", $this->settings['board_name'], $msg );
						$msg = str_replace( "<#BOARD_ADDRESS#>", $this->settings['board_url'] . '/index.' . $this->settings['php_ext'], $msg );

						IPSText::getTextClass('email')->message	= stripslashes( IPSText::getTextClass('email')->cleanMessage($msg) );
						IPSText::getTextClass('email')->subject	= $this->lang->words['m_changesubj'];
						IPSText::getTextClass('email')->to		= $member['email'];
						IPSText::getTextClass('email')->sendMail();
					}
				}
				
				$this->cache->rebuildCache( 'stats', 'global' );
			}
			else
			{
				# We should absolutely never get here. So this is a fail-safe, really to
				# prevent a "false" positive outcome for the end-user
				$this->returnJsonError($this->lang->words['m_namealready']);
			}
		}
		catch( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
			
			switch( $error->getMessage() )
			{
				case 'NO_USER':
					$this->returnJsonError( $this->lang->words['m_noid'] );
				break;
				case 'NO_PERMISSION':
				case 'NO_NAME':
					$this->returnJsonError( sprintf($this->lang->words['m_morethan3'], $this->settings['max_user_name_length'] ) );
				break;
				case 'ILLEGAL_CHARS':
					$this->returnJsonError( $this->lang->words['m_illegal'] );
				break;
				case 'USER_NAME_EXISTS':
					$this->returnJsonError( $this->lang->words['m_namealready'] );
				break;
				default:
					$this->returnJsonError( $error->getMessage() );
				break;
			}
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	if( $field == 'name' )
    	{
	    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
	    	$han_login		=  new han_login( $this->registry );
	    	$han_login->init();
	    	$han_login->changeName( $member['name'], $display_name, $member['email'] );
    	}
    	else
    	{
    		IPSLib::runMemberSync( 'onNameChange', $member_id, $display_name );
    	}
		
		$__display_name = addslashes( $display_name );
		
		$_string = <<<EOF
		{
			'success'      : true,
			'display_name' : "$__display_name"
		}
		
EOF;
		$this->returnString( $_string );
   
	}
	
	/**
	 * Show the form
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function show()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name		= trim( IPSText::alphanumericalClean( $this->request['name'] ) );
		$member_id	= intval( $this->request['member_id'] );
		$output		= '';
		
		//-----------------------------------------
		// Load language and skin
		//-----------------------------------------
		
		$html = $this->registry->output->loadTemplate('cp_skin_member_form');
		
		$this->lang->loadLanguageFile( array( 'admin_member' ) );
		
		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'extendedProfile,customFields' );
		
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['m_noid'] );
		}
		
		//-----------------------------------------
		// Return the form
		//-----------------------------------------
		
		if ( method_exists( $html, $name ) )
		{
			$output = $html->$name( $member );
		}
		else
		{
			$save_to		= '';
			$div_id			= '';
			$form_field		= '';
			$text			= '';
			$description	= '';
			$method			= '';

			switch( $name )
			{	
				/*case 'inline_warn_level':
					$method			= 'inline_form_generic';
					$save_to		= 'save_generic&amp;field=warn_level';
					$div_id			= 'warn_level';
					$form_field		= ipsRegistry::getClass('output')->formInput( "generic__field", $member['warn_level'] );
					$text			= "Member Warn Level";
					$description	= "Make adjustments to the member's overall warn level.  This does NOT add a warn log record - you should do so manually using the 'Add New Note' link if you wish to store a log of this adjustment";
				break;*/
				
				case 'inline_ban_member':

					if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban', 'members', 'members' ) )
					{
						$this->returnJsonError($this->lang->words['m_noban']);
					}
					
					if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin', 'members', 'members' ) )
					{
						$this->returnJsonError($this->lang->words['m_noban']);
					}

					//-----------------------------------------
					// INIT
					//-----------------------------------------
					
					$ban_filters 	= array( 'email' => array(), 'name' => array(), 'ip' => array() );
					$email_banned	= false;
					$ip_banned		= array();
					$name_banned	= false;
					
					//-----------------------------------------
					// Grab existing ban filters
					//-----------------------------------------
					
					$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$ban_filters[ $r['ban_type'] ][] = $r['ban_content'];
					}
					
					//-----------------------------------------
					// Check name and email address
					//-----------------------------------------
					
					if( in_array( $member['email'], $ban_filters['email'] ) )
					{
						$email_banned	= true;
					}
					
					if( in_array( $member['name'], $ban_filters['name'] ) )
					{
						$name_banned	= true;
					}

					//-----------------------------------------
					// Retrieve IP addresses
					//-----------------------------------------
					
					$ip_addresses	= IPSMember::findIPAddresses( $member['member_id'] );
					
					//-----------------------------------------
					// Start form fields
					//-----------------------------------------
					
					$form['member']			= ipsRegistry::getClass('output')->formCheckbox( "ban__member", $member['member_banned'] );
					$form['email']			= ipsRegistry::getClass('output')->formCheckbox( "ban__email", $email_banned );
					$form['name']			= ipsRegistry::getClass('output')->formCheckbox( "ban__name", $name_banned );
					
					$form['note']			= ipsRegistry::getClass('output')->formCheckbox( "ban__note", 0 );
					$form['note_field']		= ipsRegistry::getClass('output')->formTextarea( "ban__note_field" );
					$form['ips']			= array();
					
					//-----------------------------------------
					// What about IPs?
					//-----------------------------------------
					
					if( is_array($ip_addresses) AND count($ip_addresses) )
					{
						foreach( $ip_addresses as $ip_address => $count )
						{
							if( in_array( $ip_address, $ban_filters['ip'] ) )
							{
								$form['ips'][ $ip_address ] = ipsRegistry::getClass('output')->formCheckbox( "ban__ip_" . str_replace( '.', '_', $ip_address ), true );
							}
							else
							{
								$form['ips'][ $ip_address ] = ipsRegistry::getClass('output')->formCheckbox( "ban__ip_" . str_replace( '.', '_', $ip_address ), false );
							}
						}
					}
					
					$member_groups = array();
					
					foreach( ipsRegistry::cache()->getCache('group_cache') as $group )
					{
						if( $group['g_id'] == $member['member_group_id'] )
						{
							$member['_group_title'] = $group['g_title'];
						}

						$member_groups[] = array( $group['g_id'], $group['g_title'] );
					}
					
					$form['groups_confirm']	= ipsRegistry::getClass('output')->formCheckbox( "ban__group_change", 0 );
					$form['groups'] 		= ipsRegistry::getClass('output')->formDropdown( "ban__group", $member_groups, $member['member_group_id'] );
					
					$output = $html->inline_ban_member_form( $member, $form );
				break;
			}
			
			if( !$output AND $method AND method_exists( $html, $method ) )
			{
				$output = $html->$method( $member, $save_to, $div_id, $form_field, $text, $description );
			}
		}

		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->returnHtml( $output );
	}
}