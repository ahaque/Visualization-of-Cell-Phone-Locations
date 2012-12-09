<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Contact Member Functions
 * Last Updated: $Date: 2009-04-14 03:01:20 -0400 (Tue, 14 Apr 2009) $
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		20th February 2002
 * @version		$Rev: 4453 $
 *
 */
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_messaging_contact extends ipsCommand
{
	/**
	 * Temporary HTML output
	 *
	 * @access	public
	 * @var		string
	 */
	public $output			= "";
	
	/**
	 * Temporary navigation items
	 *
	 * @access	public
	 * @var		array
	 */
	public $nav				= array();

	/**
	 * Temporary page title
	 *
	 * @access	public
	 * @var		string
	 */
	public $page_title		= "";

	/**
	 * Error
	 *
	 * @access	private
	 * @var		string
	 */
	private $int_error		= "";

	/**
	 * Extra info
	 *
	 * @access	private
	 * @var		string
	 */
	private $int_extra		= "";
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* What to do? */
        switch( $this->request['do'] )
        {
        	case '01':
        	case '00':
			case 'Mail':
				$this->mailMember();
			break;

			case 'report':
				if( $this->request['send'] != 1 )
				{
					$this->reportPostForm();
				}
				else
				{
					$this->reportPostSend();
				}
			break;
			
			default:
				$this->registry->output->showError( 'contact_what_action', 1034 );
			break;
		}
		
		/* Navigation */
		foreach( $this->nav as $nav )
		{
			$this->registry->output->addNavigation( $nav[0], $nav[1] );	
		}
		
		/* Output */
		$this->registry->output->setTitle( $this->page_title );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Displays the report post form
	 *
	 * @access		public
	 * @return		void
	 * @deprecated	Just redirects to report center now
	 */
	public function reportPostForm()
	{
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&module=reports&rcom=post&tid={$this->request['t']}&pid={$this->request['p']}&st={$this->request['st']}" );
	}
	
	/**
	 * Sends the reported post
	 *
	 * @access		public
	 * @return		void
	 * @deprecated	Just redirects to report center now
	 */
	public function reportPostSend()
	{
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&module=reports&rcom=post&tid={$this->request['t']}&pid={$this->request['p']}&st={$this->request['st']}" );
	}

	/**
	 * Handles the routines called by clicking on the "email" button
	 *
	 * @access	public
	 * @return	void
	 */
	public function mailMember()
	{
		/* Load Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_emails' ), 'core' );

		if( empty( $this->memberData['member_id'] ) )
		{
			ipsRegistry::getClass('output')->showError( 'members_only_mail', 10313 );
		}
		
		/* Check email permission */
		if( ! $this->memberData['g_email_friend'] )
		{
			ipsRegistry::getClass('output')->showError( 'no_member_mail', 10314 );
		}
		
		if( $this->request['do'] == '01' )
		{
			$this->mailMemberSend();	
		}
		else
		{			
			$this->mailMemberForm();
		}
		
	}
	
	/**
	 * Displays the form for mailing a member
	 *
	 * @access	public
	 * @param	string 		Errors
	 * @param	string		Extra data
	 * @return	void
	 */
	public function mailMemberForm( $errors="", $extra="" )
	{
		/* Check ID */
		$id = intval( $this->request['MID'] );
				
		if( ! $id )
		{
			$this->registry->output->showError( 'mail_member_no_mid', 10315 );
		}
		
		/* Query member information */		
		$member = IPSMember::load( $id );
		
		/* Make sure we have a valid user */
		if( ! $member['member_id'] )
		{
			$this->registry->output->showError( 'mail_member_no_member', 10316 );
		}
		
		/* Check email privacy */
		if( $member['hide_email'] == 1 )
		{
			$this->registry->output->showError( 'mail_member_private', 10317 );
		}
		
		/* Show errors */
		if ( $errors != "" )
		{
			$msg = $this->lang->words[$errors];
			
			if ( $extra != "" )
			{
				$msg = sprintf( $msg, $extra );
			}
			
			$this->output .= $this->registry->output->getTemplate('emails')->errors( $msg );
		}
		
		/* Output */
		$this->output .= $this->settings['use_mail_form']
					  ? $this->registry->output->getTemplate('emails')->sendMailForm(
												  array(
														  'NAME'   => $member['members_display_name'],
														  'TO'     => $member['member_id'],
														  'subject'=> $this->request['subject'],
														  'content'=> stripslashes( htmlspecialchars( $_POST['message'] ) ),
													   )
											   )
					  : $this->registry->output->getTemplate('emails')->show_address(
												  array(
														  'NAME'    => $member['members_display_name'],
														  'ADDRESS' => $member['email'],
													   )
												 );
												 
		$this->page_title = $this->lang->words['member_address_title'];
		$this->nav[]      = array( $this->lang->words['member_address_title'], '' );
	}
	
	/**
	 * Sends the email
	 *
	 * @access	public
	 * @return	void
	 */
	public function mailMemberSend()
	{
		//-----------------------------------------
		// Check form key first
		//-----------------------------------------
		
		if ( $this->request['k'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'no_permission', 20313 );
		}
        
		$this->request['to'] = intval( $this->request['to'] );
	
		if( $this->request['to'] == 0 )
		{
			$this->registry->output->showError( 'mail_member_no_mid', 10318 );
		}
		
		/* Query Member */		
		$member = IPSMember::load( $this->request['to'] );
		

		/* Check for schtuff */
		if( ! $member['member_id'] )
		{
			$this->registry->output->showError( 'mail_member_no_member', 10319 );
		}
		
		/* Check email privacy */
		if( $member['hide_email'] == 1 )
		{
			$this->registry->output->showError( 'mail_member_private', 10320, true );
		}
		
		/* Check for blanks */
		$check_array = array ( 
							   'message'   =>  'no_message',
							   'subject'   =>  'no_subject'
							 );
						 
		foreach( $check_array as $input => $msg )
		{
			if( empty( $this->request[$input] ) )
			{
				$this->request['MID'] = $this->request['to'];
				$this->mailMemberForm( $msg );
				return;
			}
		}

		/* Check for spam / delays */
		$email_check = $this->_allowToMail( $this->memberData['member_id'], $this->memberData['g_email_limit'] );
		
		if( $email_check != TRUE )
		{
			$this->request['MID'] = $this->request['to'];
			$this->mailMemberForm( $this->int_error, $this->int_extra );
			return;
		}
		
		/**
		 * No check for injected headers in the message
		 * @link	http://forums./index.php?app=tracker&showissue=13098
		 */
		if( preg_match("/(content-type:|content-transfer-encoding:|content-disposition:)/i", $this->request['message'] ) )
		{
			$this->registry->output->showError( 'bad_email_message', 5021, true );
		}

		/* Send the email */
		IPSText::getTextClass( 'email' )->getTemplate( 'email_member' );
			
		IPSText::getTextClass( 'email' )->buildMessage( array(
															'MESSAGE'     => str_replace( "<br>", "\n", str_replace( "\r", "", $this->request['message'] ) ),
															'MEMBER_NAME' => $member['members_display_name'],
															'FROM_NAME'   => $this->memberData['members_display_name']
													)	);
									
		IPSText::getTextClass( 'email' )->subject = $this->request['subject'];
		IPSText::getTextClass( 'email' )->to      = $member['email'];
		IPSText::getTextClass( 'email' )->from    = $this->memberData['email'];
		IPSText::getTextClass( 'email' )->sendMail();
		
		/* Store email in the database */
		$this->DB->insert( 'email_logs', array( 
											'email_subject'      => $this->request['subject'],
											'email_content'      => $this->request['message'],
											'email_date'         => time(),
											'from_member_id'     => $this->memberData['member_id'],
											'from_email_address' => $this->memberData['email'],
											'from_ip_address'	 => $this->member->ip_address,
											'to_member_id'		 => $member['member_id'],
											'to_email_address'	 => $member['email'],
					  )                   );

		$this->output  = $this->registry->output->getTemplate('emails')->sentScreen( $member['members_display_name'] );		

		$this->page_title = $this->lang->words['email_sent'];
		$this->nav[]      = array( $this->lang->words['email_sent'], '' );
	}
	
	/**
	 * Check Flood Limit
	 *
	 * @access	private
	 * @param	integer	$member_id
	 * @param	string	$email_limit
	 * @return	bool
	 **/
	private function _allowToMail( $member_id, $email_limit )
	{
		$member_id = intval( $member_id );
		
		if( ! $member_id )
		{
			$this->int_error = 'gen_error';
			return FALSE;
		}
		
		list( $limit, $flood ) = explode( ':', $email_limit );
		
		if ( ! $limit and ! $flood )
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// Get some stuff from the DB!
		// 1) FLOOD?
		//-----------------------------------------
		
		if( $flood )
		{
			$this->DB->build( array( 
											'select' => '*',
											'from'   => 'email_logs',
											'where'  => "from_member_id=$member_id",
											'order'  => 'email_date DESC',
											'limit'  => array( 0, 1 ) ) );
			$this->DB->execute();
		
			$last_email = $this->DB->fetch();

			if( $last_email['email_date'] + ( $flood * 60 ) > time() )
			{
				$this->int_error = 'exceeded_flood';
				$this->int_extra = $flood;
				return FALSE;
			}
		}
		
		if( $limit )
		{
			$time_range = time() - 86400;
			
			$this->DB->build( array( 
											'select' => 'count(email_id) as cnt',
											'from'   => 'email_logs',
											'where'  => "from_member_id=$member_id AND email_date > $time_range",
								 )      );
			$this->DB->execute();
			
			$quota_sent = $this->DB->fetch();
			
			if( $quota_sent['cnt'] + 1 > $limit )
			{
				$this->int_error = 'exceeded_quota';
				$this->int_extra = limit;
				return FALSE;
			}
		}
		
		return TRUE; //<{%dyn.down.var.md5p2%}> If we get here...
        		
	}
}