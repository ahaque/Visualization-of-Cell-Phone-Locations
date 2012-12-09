<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forward a topic to someone
 * Last Updated: $Date: 2009-06-25 19:58:52 -0400 (Thu, 25 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		20th February 2002
 * @version		$Revision: 4822 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_extras_forward extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;

	/**
	 * Forum information
	 *
	 * @access	private
	 * @var		array		Array of forum details
	 */
	private $forum			= array();

	/**
	 * Topic information
	 *
	 * @access	private
	 * @var		array		Array of topic details
	 */
	private $topic			= array();

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Grab skin and lang stuff
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_emails' ), 'core' );
		
		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		$this->request['t'] = intval($this->request['t']);
		$this->request['f'] = intval($this->request['f']);
		
		if ( !$this->request['t'] )
		{
			$this->registry->output->showError( 'forward_no_tid', 10321 );
		}
	
		//-----------------------------------------
		// Get the topic details
		//-----------------------------------------
	
		$this->topic	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=" . $this->request['t'] ) );
		$this->forum	= ipsRegistry::getClass('class_forums')->forum_by_id[ $this->topic['forum_id'] ];
		
		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------
		
		if ( ! $this->forum['id'] )
		{
			$this->registry->output->showError( 'forward_no_fid', 10322 );
		}
		
		//-----------------------------------------
		// Error out if we can not find the topic
		//-----------------------------------------
		
		if ( !$this->topic['tid'] )
		{
			$this->registry->output->showError( 'forward_no_tid', 10323 );
		}

		//-----------------------------------------
		// Check viewing permissions, private forums,
		// password forums, etc
		//-----------------------------------------
		
		if (! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'forward_only_members', 10324 );
		}
		
		ipsRegistry::getClass('class_forums')->forumsCheckAccess( $this->forum['id'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		if ( $this->request['do'] == '01' )
		{
			$this->_sendEmail();
		}
		else
		{
			$this->_showForm();
		}
	}
	
	/**
	 * Forward the page (sends the email)
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _sendEmail()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->request['k'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'no_permission', 2029 );
		}
        
		$lang_to_use = 1;
		
		foreach( ipsRegistry::cache()->getCache('lang_data') as $l )
		{
			if ( $this->request['lang'] == $l['lang_id'] )
			{
				$lang_to_use = $l['lang_id'];
			}
		}
		
		$check_array = array ( 'to_name'   =>  'stf_no_name',
							   'to_email'  =>  'stf_no_email',
							   'message'   =>  'stf_no_msg',
							   'subject'   =>  'stf_no_subject'
							 );
							 
		foreach ($check_array as $input => $msg)
		{
			if ( !$this->request[ $input ] )
			{
				$this->registry->output->showError( $msg, 10325 );
			}
		}

		if ( !IPSText::checkEmailAddress( $this->request['to_email'] ) )
		{
			$this->registry->output->showError( 'email_address_invalid', 10326 );
		}
		
		IPSText::getTextClass('email')->getTemplate( "forward_page", $lang_to_use );
			
		IPSText::getTextClass('email')->buildMessage( array(
															'THE_MESSAGE'	=> $this->request['message'],
															'TO_NAME'		=> $this->request['to_name'],
															'FROM_NAME'		=> $this->memberData['members_display_name'],
										  					)
													);
									
		IPSText::getTextClass('email')->subject	= $this->request['subject'];
		IPSText::getTextClass('email')->to		= $this->request['to_email'];
		IPSText::getTextClass('email')->from	= $this->memberData['email'];
		IPSText::getTextClass('email')->sendMail();
		
		$this->registry->output->redirectScreen( $this->lang->words['redirect'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . $this->request['st'] );
	}
	
	/**
	 * Show the form to forward the page
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _showForm()
	{
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_email_content' ), 'core' );

		$this->lang->words['send_text'] = str_replace( "<#THE LINK#>" , $this->registry->getClass('output')->buildSEOUrl( "showtopic=" . $this->topic['tid'], 'public', $this->topic['title_seo'], 'showtopic') , $this->lang->words['send_text'] );
		$this->lang->words['send_text'] = str_replace( "<#USER NAME#>", $this->memberData['members_display_name'], $this->lang->words['send_text'] );

		$this->output = $this->registry->getClass('output')->getTemplate('emails')->forward_form( $this->topic['title'], $this->lang->words['send_text'] );

		$this->registry->output->addNavigation( $this->forum['name'], $this->settings['_base_url'] . "showforum={$this->forum['id']}" );
		$this->registry->output->addNavigation( $this->topic['title'], $this->settings['_base_url'] . "showtopic={$this->topic['tid']}" );
		$this->registry->output->addNavigation( $this->lang->words['title'], '' );
		$this->registry->output->setTitle( $this->lang->words['title'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}

}