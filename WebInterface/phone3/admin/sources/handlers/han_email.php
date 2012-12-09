<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * API: Core
 * Last Updated: $Date: 2009-07-13 11:17:28 -0400 (Mon, 13 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 4872 $
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class hanEmail
{
	/**
	 * Emailer object reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $emailer;
	
	/**
	 * Email header
	 *
	 * @access	public
	 * @var		string
	 */
	public $header;
	
	/**
	 * Email footer
	 *
	 * @access	public
	 * @var		string
	 */
	public $footer;
	
	/**
	 * Email from
	 *
	 * @access	public
	 * @var		string
	 */
	public $from;
	
	/**
	 * Email to
	 *
	 * @access	public
	 * @var		string
	 */
	public $to;
	
	/**
	 * Email bcc's
	 *
	 * @access	public
	 * @var	array
	 */
	public $bcc		= array();
	
	/**
	 * Email subject
	 *
	 * @access	public
	 * @var		string
	 */
	public $subject;
	
	/**
	 * Email body
	 *
	 * @access	public
	 * @var		string
	 */
	public $message;
	
	/**
	 * Temp word swapping array
	 *
	 * @access	public
	 * @var		array
	 */
	private $_words;
	
	/**
	 * Headers to pass to email lib
	 *
	 * @access	private
	 * @var		array
	 */
	private $temp_headers		= array();
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	public
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	/**#@-*/
	
	/**
	 * Construct
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * Init method (setup stuff)
	 *
	 * @access	public
	 * @return	void
	 */
    public function init()
    {
		$this->header   = $this->settings['email_header'] ? $this->settings['email_header'] : '';
		$this->footer   = $this->settings['email_footer'] ? $this->settings['email_footer'] : '';

		require_once( IPS_KERNEL_PATH . 'classEmail.php' );

		$this->emailer = new classEmail(
										 array(
										 		'debug'			=> $this->settings['fake_mail'] ? $this->settings['fake_mail'] : '0',
										 		'debug_path'	=> DOC_IPS_ROOT_PATH . '_mail',
										 		'smtp_host'		=> $this->settings['smtp_host'] ? $this->settings['smtp_host'] : 'localhost',
										 		'smtp_port'		=> intval($this->settings['smtp_port']) ? intval($this->settings['smtp_port']) : 25,
										 		'smtp_user'		=> $this->settings['smtp_user'],
										 		'smtp_pass'		=> $this->settings['smtp_pass'],
										 		'method'		=> $this->settings['mail_method'],
										 		'wrap_brackets'	=> $this->settings['mail_wrap_brackets'],
										 		'extra_opts'	=> $this->settings['php_mail_extra'],
										 		'charset'		=> IPS_DOC_CHAR_SET,
										 		'html'			=> intval($this->settings['html_email']),
												)
										 );
    }
    
    /**
     * Clear out any temporary headers
     *
     * @access	public
     * @return	void
     */
    public function clearHeaders()
    {
    	$this->temp_headers	= array();
    }
    
    /**
     * Manually set an email header
     *
     * @access	public
     * @param	string	Header key
     * @param	string	Header value
     * @return	void
     */
    public function setHeader( $key, $value )
    {
    	$this->temp_headers[ $key ]	= $value;
    }
    
	/**
	 * Send an email
	 *
	 * @access	public
	 * @return	boolean		Email sent successfully
	 */
	public function sendMail()
	{
		//$this->emailer->clearEmail();
		//$this->emailer->clearError();
		$this->init();
		
		$this->settings['board_name'] = $this->cleanMessage($this->settings['board_name']);
		
		$this->emailer->setFrom( $this->from ? $this->from : $this->settings['email_out'], $this->settings['board_name'] );
		$this->emailer->setTo( $this->to );
		
		foreach( $this->bcc as $bcc )
		{
			$this->emailer->addBCC( $bcc );
		}
		
		if( count($this->temp_headers) )
		{
			foreach( $this->temp_headers as $k => $v )
			{
				$this->emailer->setHeader( $k, $v );
			}
		}

		$this->emailer->setSubject( $this->subject );
		$this->emailer->setBody( $this->message );
		$this->emailer->sendMail();
		
		if( $this->emailer->error )
		{
			return $this->fatalError( $this->emailer->error_msg, $this->emailer->error_help );
		}
		
		return true;
	}
		
	/**
	 * Retrieve an email template
	 *
	 * @access	public
	 * @param	string		Template key
	 * @param	string		Language to use
	 * @param	string		Language file to load
	 * @param	string		Application of language file
	 * @return	void
	 */
	public function getTemplate( $name, $language="", $lang_file='public_email_content', $app='core' )
	{
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if( $name == "" )
		{
			$this->error++;
			$this->fatalError( "A valid email template ID was not passed to the email library during template parsing", "" );
		}
		
		//-----------------------------------------
		// Default?
		//-----------------------------------------

		if( ! $language )
		{
			$language = IPSLib::getDefaultLanguage();
		}
		
		//-----------------------------------------
		// Check and get
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( $lang_file ), $app, $language );
		
		//-----------------------------------------
		// Stored KEY?
		//-----------------------------------------
		
		if ( ! isset($this->lang->words[ $name ]) )
		{
			if ( $language == IPSLib::getDefaultLanguage() )
			{
				$this->fatalError( "Could not find an email template with an ID of '{$name}'", "" );
			}
			else
			{
				$this->registry->class_localization->loadLanguageFile( array( $lang_file ), $app, IPSLib::getDefaultLanguage() );
				
				if ( ! isset($this->lang->words[ $name ]) )
				{
					$this->fatalError( "Could not find an email template with an ID of '{$name}'", "" );
				}
			}
		}
		
		//-----------------------------------------
		// Subject?
		//-----------------------------------------
		
		if ( isset( $this->lang->words[ 'subject__'. $name ] ) )
		{
			$this->subject = stripslashes( $this->lang->words[ 'subject__'. $name ] );
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->template = stripslashes($this->lang->words['email_header']) . stripslashes($this->lang->words[ $name ]) . stripslashes($this->lang->words['email_footer']);
	}
		
	/**
	 * Builds an email from a template, replacing variables
	 *
	 * @access	public
	 * @param	array		Replacement keys to values
	 * @param	bool		Do not "clean"
	 * @return	void
	 */
	public function buildMessage( $words, $noClean=false )
	{
		if( $this->template == "" )
		{
			$this->error++;
			$this->fatalError( "Could not build the email message, no template assigned", "Make sure a template is assigned first." );
		}
		
		$this->message = $this->template;

		if( $noClean )
		{
			$this->message	= str_replace( "\n", "<br />", str_replace( "\r", "", $this->message ) );
		}

		//-----------------------------------------
		// Add some default words
		//-----------------------------------------
		
		$words['BOARD_ADDRESS'] = $this->settings['board_url'] . '/index.' . $this->settings['php_ext'];
		$words['WEB_ADDRESS']   = $this->settings['home_url'];
		$words['BOARD_NAME']    = $this->settings['board_name'];
		$words['SIGNATURE']     = $this->settings['signature'] ? $this->settings['signature'] : '';
		
		//-----------------------------------------
		// Swap the words
		// 10.7.08 - Added replacements in subject
		//-----------------------------------------
		
		if( !$noClean )
		{
			foreach( $words as $k => $v )
			{
				$words[ $k ]	= $this->cleanMessage( $v );
			}
		}

		$this->_words = $words;
		
		$this->message = preg_replace_callback( "/<#(.+?)#>/", array( &$this, '_swapWords' ), $this->message );
		$this->subject = preg_replace_callback( "/<#(.+?)#>/", array( &$this, '_swapWords' ), $this->subject );

		$this->_words = array();
	}
	
	/**
	 * Replaces key with value
	 *
	 * @access	private
	 * @param	string		Key
	 * @return	string		Replaced variable
	 */
	private function _swapWords( $matches )
	{
		return $this->_words[ $matches[1] ];
	}
	
	/**
	 * Cleans an email message
	 *
	 * @access	public
	 * @param	string		Email content
	 * @return	string		Cleaned email content
	 */
	public function cleanMessage( $message = "" ) 
	{
		$message = preg_replace( "#\[quote.*\](.+?)\[/quote\]#", "<br /><br />------------ QUOTE ----------<br />\\1<br />-----------------------------<br /><br />" , $message );

		//-----------------------------------------
		// Unconvert smilies 'cos at this point they are img tags
		//-----------------------------------------
		
		$message = IPSText::getTextClass('bbcode')->unconvertSmilies( $message );
		
		//-----------------------------------------
		// We may want to adjust this later, but for
		// now just strip any other html
		//-----------------------------------------

		$message = strip_tags( $message, '<br>' );

		IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_wordwrap	= -1;
		
		$message = IPSText::getTextClass('bbcode')->stripAllTags( $message, true, false );

		//-----------------------------------------
		// Bear with me...
		//-----------------------------------------
		
		$message = str_replace( "\n"			, "\r\n", $message );
		$message = str_replace( "\r"			, ""	, $message );
		$message = str_replace( "<br>"			, "\r\n", $message );
		$message = str_replace( "<br />"		, "\r\n", $message );
		$message = str_replace( "\r\n\r\n"		, "\r\n", $message );
		
		$message = str_replace( "&quot;", '"' , $message );
		$message = str_replace( "&#092;", "\\", $message );
		$message = str_replace( "&#036;", "\$", $message );
		$message = str_replace( "&#33;" , "!" , $message );
		$message = str_replace( "&#34;" , '"' , $message );
		$message = str_replace( "&#39;" , "'" , $message );
		$message = str_replace( "&#40;" , "(" , $message );
		$message = str_replace( "&#41;" , ")" , $message );
		$message = str_replace( "&lt;"  , "<" , $message );
		$message = str_replace( "&gt;"  , ">" , $message );
		$message = str_replace( "&#124;", '|' , $message );
		$message = str_replace( "&amp;" , "&" , $message );
		$message = str_replace( "&#38;" , '&' , $message );
		$message = str_replace( "&#58;" , ":" , $message );
		$message = str_replace( "&#91;" , "[" , $message );
		$message = str_replace( "&#93;" , "]" , $message );
		$message = str_replace( "&#064;", '@' , $message );
		$message = str_replace( "&#60;" , '<' , $message );
		$message = str_replace( "&#62;" , '>' , $message );
		$message = str_replace( "&nbsp;" , ' ', $message );

		return $message;
	}
	
	/**
	 * Log a fatal error
	 *
	 * @access	private
	 * @param	string		Message
	 * @param	string		Help key (deprecated)
	 * @return	bool
	 */
	private function fatalError( $msg, $help="" )
	{
		$this->DB->insert( 'mail_error_logs',
										array(
												'mlog_date'     => time(),
												'mlog_to'       => $this->to,
												'mlog_from'     => $this->from,
												'mlog_subject'  => $this->subject,
												'mlog_content'  => substr( $this->message, 0, 200 ),
												'mlog_msg'      => $msg,
												'mlog_code'     => $this->emailer->smtp_code,
												'mlog_smtp_msg' => $this->emailer->smtp_msg
											 )
									  );
		
		return false;
	}	
}