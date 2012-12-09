<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Send email using php mail() or SMTP
 * Last Updated: $Date: 2009-08-18 16:46:23 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 316 $
 *
 * @todo 		[Future] Support receiving and parsing emails and attachments
 *
 * Example usage:
 * <code>
 * $email = new classEmail(
 * 						array(	'debug'			=> 1,
 * 								'method'		=> 'mail',
 * 								'html'			=> 1,
 * 								'debug_path'	=> '/tmp/_mail',
 * 								'charset'		=> 'utf-8',
 * 							)
 * 						);
 * $email->setFrom( "support@", "This is\r\nan email" );
 * $email->setTo( "me@mydomain.com" );
 * $email->addBCC( "myfriend@mydomain.com" );
 * $email->addBCC( "myotherfriend@mydomain.com" );
 * $email->setSubject( "This is a test!!" );
 * $email->setBody( "<b>We have HTML capability!</b><br /><br /><i>But this is just a test...</i>" );
 * $email->sendMail();
 * </code>
 */
 
/**
 * Email class interface
 *
 */
interface interfaceEmail
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	Initiate class parameters
	 * @return	void
	 */
	public function __construct( $opts=array() );

	/**
	 * Clear stored data to prepare a new email. Useful
	 *	to prevent having to close/reopen SMTP connection
	 *	repeatedly.
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearEmail();
	
	/**
	 * Clear stored errors to prepare a new email.
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearError();
	
	/**
	 * Set the from email address
	 *
	 * @access	public
	 * @param	string		From email address
	 * @param	string		[Optional] From display
	 * @return	boolean
	 */
	public function setFrom( $email, $display='' );
	
	/**
	 * Set the 'to' email address
	 *
	 * @access	public
	 * @param	string		To email address
	 * @return	boolean
	 */
	public function setTo( $email );
	
	/**
	 * Add bcc's
	 *
	 * @access	public
	 * @param	string		To email address
	 * @return	boolean
	 */
	public function addBCC( $email );
	
	/**
	 * Set the email subject
	 *
	 * @access	public
	 * @param	string		Email subject
	 * @return	boolean
	 */
	public function setSubject( $subject );
	
	/**
	 * Set the email body
	 *
	 * @access	public
	 * @param	string		Email body
	 * @return	boolean
	 */
	public function setBody( $body );
	
	/**
	 * Set a header manually
	 *
	 * @access	public
	 * @param	string		Header key
	 * @param	string		Header value
	 * @return	boolean
	 */
	public function setHeader( $key, $value );
	
	/**
	 * Send the mail (All appropriate params must be set by this point)
	 *
	 * @access	public
	 * @return	boolean		Mail sent successfully
	 */
	public function sendMail();
	
	/**
	 * Add an attachment to the current email
	 *
	 * @access	public
	 * @param	string	File data
	 * @param	string	File name
	 * @param	string	File type (MIME)
	 * @return	void
	 */
	public function addAttachment( $data="", $name="", $ctype='application/octet-stream' );

}

/**
 * Email class
 *
 */
class classEmail implements interfaceEmail
{
	/**
	 * From email address 
	 *
	 * @access	private
	 * @var 	string
	 */
	private $from			= "";
	
	/**
	 * From email address (displayed)
	 *
	 * @access	private
	 * @var 	string
	 */
	private $from_display	= "";
	
	/**
	 * To email address
	 *
	 * @access	private
	 * @var 	string
	 */
	private $to				= "";
	
	/**
	 * Email subject
	 *
	 * @access	private
	 * @var 	string
	 */
	private $subject		= "";
	
	/**
	 * Email message contents
	 *
	 * @access	private
	 * @var 	string
	 */
	private $message		= "";
	
	/**
	 * PHP mail() extra params
	 *
	 * @access	private
	 * @var 	string
	 */
	private $extra_opts		= '';
	
	/**
	 * Plain text message contents
	 *
	 * @access	private
	 * @var 	string
	 */
	private $pt_message		= "";
	
	/**
	 * Attachments: Parts
	 *
	 * @access	private
	 * @var 	array
	 */
	private $parts			= array();

	/**
	 * BCC Email addresses 
	 *
	 * @access	private
	 * @var 	array
	 */
	private $bcc			= array();
	
	/**
	 * Email headers
	 *
	 * @access	private
	 * @var 	array
	 */
	private $mail_headers	= array();
	
	/**
	 * Header EOL
	 *  RFC specs state \r\n
	 *  However most servers seem to only support \n
	 *
	 * @access	public
	 * @var 	string
	 */
	const header_eol		= "\n";
	
	/**
	 * Attachments: Multi-part
	 *
	 * @access	private
	 * @var 	string
	 */
	private $multipart		= "";
	
	/**
	 * Attachments: Boundry
	 *
	 * @access	private
	 * @var 	string
	 */
	private $boundry		= "----=_NextPart_000_0022_01C1BD6C.D0C0F9F0";
	
	/**
	 * HTML email flag
	 *
	 * @access	private
	 * @var 	integer
	 */
	private $html_email		= 0;
	
	/**
	 * Email character set
	 *
	 * @access	private
	 * @var 	string
	 */
	private $char_set		= 'utf-8';
	
	/**
	 * SMTP: Resource
	 *
	 * @access	private
	 * @var 	resource
	 */
	private $smtp_fp		= null;
	
	/**
	 * SMTP: Message
	 *
	 * @access	public
	 * @var 	string
	 */
	public $smtp_msg		= "";
	
	/**
	 * SMTP: Port
	 *
	 * @access	private
	 * @var 	integer
	 */
	private $smtp_port		= 25;
	
	/**
	 * SMTP: Host
	 *
	 * @access	private
	 * @var 	string
	 */
	private $smtp_host		= "localhost";
	
	/**
	 * SMTP: Username 
	 *
	 * @access	private
	 * @var 	string
	 */
	private $smtp_user		= "";
	
	/**
	 * SMTP: Password
	 *
	 * @access	private
	 * @var 	string
	 */
	private $smtp_pass		= "";
	
	/**
	 * SMTP: Return code
	 *
	 * @access	public
	 * @var 	string
	 */
	public $smtp_code		= "";
	
	/**
	 * SMTP: Wrap email addresses in brackets flag
	 *
	 * @access	private
	 * @var 	boolean
	 */
	private $wrap_brackets	= false;
	
	/**
	 * Default email method (mail or smtp)
	 *
	 * @access	private
	 * @var 	string
	 */
	private $mail_method	= 'mail';
	
	/**
	 * Dump email to flat file for testing
	 *
	 * @access	private
	 * @var 	integer
	 */
	private $temp_dump		= 0;
	
	/**
	 * Path for email dumps
	 *
	 * @access	private
	 * @var 	string
	 */
	private $temp_dump_path	= '';
	
	/**
	 * Error message
	 *
	 * @access	public
	 * @var 	string
	 */
	public $error_msg		= '';
	
	/**
	 * Error description
	 *
	 * @access	public
	 * @var 	string
	 */
	public $error_help		= '';
	
	/**
	 * Error flag
	 *
	 * @access	public
	 * @var 	boolean
	 */
	public $error			= false;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	Initiate class parameters
	 * @return	void
	 */
	public function __construct( $opts=array() )
	{
		$this->mail_method		= ( $opts['method'] AND in_array( strtolower($opts['method']), array( 'smtp', 'mail' ) ) )  ? strtolower($opts['method']) : 'mail';
		$this->temp_dump		= ( isset($opts['debug']) AND $opts['debug'] ) ? 1 : 0;
		$this->temp_dump_path	= ( isset($opts['debug_path']) AND $opts['debug_path'] ) ? $opts['debug_path'] : '';
		$this->html_email		= ( isset($opts['html']) AND $opts['html'] ) ? 1 : 0;
		$this->char_set			= ( isset($opts['charset']) AND $opts['charset'] ) ? $opts['charset'] : 'utf-8';

		$this->smtp_host		= ( isset($opts['smtp_host']) AND $opts['smtp_host'] ) ? $opts['smtp_host'] : '';
		$this->smtp_port		= ( isset($opts['smtp_port']) AND $opts['smtp_port'] ) ? intval($opts['smtp_port']) : 25;
		$this->smtp_user		= ( isset($opts['smtp_user']) AND $opts['smtp_user'] ) ? $opts['smtp_user'] : '';
		$this->smtp_pass		= ( isset($opts['smtp_pass']) AND $opts['smtp_pass'] ) ? $opts['smtp_pass'] : '';
		$this->wrap_brackets	= ( isset($opts['wrap_brackets']) AND $opts['wrap_brackets'] ) ? true : false;
		$this->extra_opts		= ( isset($opts['extra_opts']) AND $opts['extra_opts'] ) ? $opts['extra_opts'] : '';
		
		if( $this->mail_method == 'smtp' )
		{
			$this->_smtpConnect();
		}
	}
	
	/**
	 * Destructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		if( $this->mail_method == 'smtp' )
		{
			$this->_smtpDisconnect();
		}
	}
	
	/**
	 * Clear stored data to prepare a new email. Useful
	 *	to prevent having to close/reopen SMTP connection
	 *	repeatedly.
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearEmail()
	{
		$this->from			= '';
		$this->from_display	= '';
		$this->to			= '';
		$this->bcc			= array();
		$this->subject		= '';
		$this->message		= '';
		$this->pt_message	= '';
		$this->parts		= array();
		$this->mail_headers	= array();
		$this->multipart	= '';
		$this->smtp_msg		= '';
		$this->smtp_code	= '';
	}
	
	/**
	 * Clear stored errors to prepare a new email.
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearError()
	{
		$this->error_msg	= '';
		$this->error_help	= '';
		$this->error		= false;
	}

	/**
	 * Set the from email address
	 *
	 * @access	public
	 * @param	string		From email address
	 * @param	string		[Optional] From display
	 * @return	boolean
	 */
	public function setFrom( $email, $display='' )
	{
		$this->from			= $this->_cleanEmail( $email );
		$this->from_display	= $display;
		
		return true;
	}
	
	/**
	 * Set a header manually
	 *
	 * @access	public
	 * @param	string		Header key
	 * @param	string		Header value
	 * @return	boolean
	 */
	public function setHeader( $key, $value )
	{
		$this->mail_headers[ $key ]	= $value;
		
		return true;
	}
	
	/**
	 * Set the 'to' email address
	 *
	 * @access	public
	 * @param	string		To email address
	 * @return	boolean
	 */
	public function setTo( $email )
	{
		$this->to			= $this->_cleanEmail( $email );
		
		return true;
	}
	
	/**
	 * Add bcc's
	 *
	 * @access	public
	 * @param	string		To email address
	 * @return	boolean
	 */
	public function addBCC( $email )
	{
		$this->bcc[]		= $this->_cleanEmail( $email );
		
		return true;
	}
	
	/**
	 * Set the email subject
	 *
	 * @access	public
	 * @param	string		Email subject
	 * @return	boolean
	 */
	public function setSubject( $subject )
	{
		/* Fix encoded quotes, etc */
		$subject = str_replace( '&quot;', '"', $subject );
		$subject = str_replace( '&#039;', "'", $subject );
		$subject = str_replace( '&#39;' , "'", $subject );
		$subject = str_replace( '&#33;' , "!", $subject );
		$subject = str_replace( '&#36;' , "$", $subject );
		
		if( $this->mail_method != 'smtp' )
		{
			$sheader	= $this->_encodeHeaders( array( 'Subject' => $subject ) );
			$subject	= $sheader['Subject'];
		}
		
		$this->subject		= $subject;
		
		return true;
	}
	
	/**
	 * Set the email body
	 *
	 * @access	public
	 * @param	string		Email body
	 * @return	boolean
	 */
	public function setBody( $body )
	{
		$this->message		= $body;
		
		return true;
	}
	
	/**
	 * Clean an email address
	 *
	 * @access	private
	 * @param	string		Email address
	 * @return	string		Cleaned email address
	 */
	private function _cleanEmail( $email )
	{
		$email		= str_replace( ' '	, '',	$email );
		$email		= str_replace( "\t"	, '',	$email );
		$email		= str_replace( "\r"	, '',	$email );
		$email		= str_replace( "\n"	, '',	$email );
		$email		= str_replace( ',,'	, ',',	$email );
		$email		= preg_replace( "#\#\[\]'\"\(\):;/\$!£%\^&\*\{\}#" , "", $email  );
		
		return $email;
	}

	/**
	 * Send the mail (All appropriate params must be set by this point)
	 *
	 * @access	public
	 * @return	boolean		Mail sent successfully
	 */
	public function sendMail()
	{
		//-----------------------------------------
		// Build headers
		//-----------------------------------------

		$this->_buildHeaders();
		
		//-----------------------------------------
		// Verify params are all set
		//-----------------------------------------
		
		if( !$this->to OR !$this->from OR !$this->subject )
		{
			$this->_fatalError( "From, to, or subject empty" );
			return false;
		}

		//-----------------------------------------
		// Debugging
		//-----------------------------------------
		
		if( $this->temp_dump == 1 )
		{
			$debug	= $this->subject . "\n------------\n" . $this->rfc_headers . "\n\n" . $this->message;

			if( !is_dir( $this->temp_dump_path ) )
			{
				@mkdir( $this->temp_dump_path );
				@chmod( $this->temp_dump_path, 0777 );
			}
			
			if( !is_dir( $this->temp_dump_path ) )
			{
				$this->_fatalError( "Debugging enabled, but debug path does not exist and cannot be created" );
				return false;
			}
			
			$pathy = $this->temp_dump_path . '/' . date("M-j-Y,hi-A") . str_replace( '@', '+', $this->to ) . '.php';
			
			$fh = @fopen( $pathy, 'w' );
			@fputs( $fh, $debug, strlen($debug) );
			@fclose( $fh );
		}
		else
		{
			//-----------------------------------------
			// PHP mail()
			//-----------------------------------------
			
			if( $this->mail_method != 'smtp' )
			{
				if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers, $this->extra_opts ) )
				{
					if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers ) )
					{
						$this->_fatalError( "Could not send the email", "Failed at 'mail' command" );
					}
				}
			}
			
			//-----------------------------------------
			// SMTP
			//-----------------------------------------
			
			else
			{
				$this->_smtpSendMail();
			}
		}
		
		$this->clearEmail();
		
		return $this->error ? false : true;
	}

	/**
	 * Fatal error handler
	 *
	 * @access	private
	 * @param	string	Error Message
	 * @param	string	Error Help / Description
	 * @return	boolean
	 */
	private function _fatalError( $msg, $help="" )
	{
		$this->error		= true;
		$this->error_msg	= $msg;
		$this->error_help	= $help;
		
		return false;
	}


	/*-------------------------------------------------------------------------*/
	// HEADERS
	/*-------------------------------------------------------------------------*/
	
	/**
	 * Build the multipart headers for the email
	 *
	 * @access	private
	 * @return	string			Multipart headers
	 */
	private function _buildMultipart() 
	{
		$multipart	= '';
		
		for( $i = sizeof( $this->parts ) - 1 ; $i >= 0 ; $i-- )
		{
			$multipart .= self::header_eol . $this->_encodeAttachment( $this->parts[$i] ) . "--" . $this->boundry;
		}
		
		return $multipart . "--\n";
	}

	/**
	 * ENCODE HEADERS - RFC2047
	 *
	 * @access	private
	 * @param	array 			Array of headers
	 * @return	array			Headers encoded per RFCs
	 * @see		http://www.faqs.org/rfcs/rfc822.html
	 * @see		http://www.faqs.org/rfcs/rfc2045
	 * @see		http://www.faqs.org/rfcs/rfc2047
	 * @see		http://us2.php.net/manual/en/function.mail.php#27997
	 */
	private function _encodeHeaders( $headers = array() )
	{	
		$enc_headers = count($headers) ? $headers : $this->mail_headers;
		
        foreach( $enc_headers as $header => $value) 
        {
        	$orig_value	= $value;
        	
        	//-----------------------------------------
        	// MTAs seem to dislike 'From' encoded
        	//  so we just strip board name and continue
        	//-----------------------------------------
        	
			if( $header == 'From' OR $header == 'Content-Type' OR $header == 'Content-Disposition' )
			{
				$this->mail_headers[ $header ]	= $orig_value;
				$enc_headers[ $header ]			= $orig_value;
				
				continue;
			}
			
			//-----------------------------------------
			// Don't bother encoding unless we have chars
			//  that need to be encoded
			//-----------------------------------------
			
			if( !preg_match( '/(\w*[\x80-\xFF]+\w*)/', $value ) )
			{
				$this->mail_headers[ $header ]	= $orig_value;
				$enc_headers[ $header ]			= $orig_value;
				
				continue;
			}

			//-----------------------------------------
			// Base64 encoding from example at php.net
			//-----------------------------------------
			
        	$start		= '=?' . $this->char_set . '?B?';
        	$end		= '?=';
        	$spacer		= $end . self::header_eol . ' ' . $start;
        	$length		= 75 - strlen($start) - strlen($end);
        	$length		= $length - ($length % 4);
        	
        	$value		= base64_encode($value); 
        	$value		= chunk_split( $value, $length, $spacer );
        	
        	$spacer		= preg_quote($spacer);
        	$value		= preg_replace( "/" . $spacer . "$/", "", $value );
        	$value		= $start . $value . $end;

            if( !count($headers) )
            {
            	$this->mail_headers[ $header ]	= $value;
        	}
        	else
        	{
	        	$enc_headers[ $header ]			= $value;
        	}
        }
        
        return $enc_headers;
    }
    
	/**
	 * Build the email headers (MIME, Charset, From, BCC, To, Subject, etc)
	 *
	 * @access	private
	 * @return	void
	 */
	private function _buildHeaders()
	{
		$extra_headers		= array();
		$extra_header_rfc	= "";
		
		//-----------------------------------------
		// HTML (hitmuhl)
		// If we're sending HTML messages, then
		// we'll add the plain text message along with
		// it for non HTML browsers
		//-----------------------------------------
		
		$this->pt_message = $this->message;
		$this->pt_message = str_replace( "<br />", "\n", $this->pt_message );
		$this->pt_message = str_replace( "<br>"  , "\n", $this->pt_message );
		$this->pt_message = strip_tags( $this->pt_message );
		
		$this->pt_message = html_entity_decode( $this->pt_message, ENT_QUOTES );
		$this->pt_message = str_replace( '&#092;', '\\', $this->pt_message );
		$this->pt_message = str_replace( '&#036;', '$', $this->pt_message );

		//-----------------------------------------
		// Start mail headers
		//-----------------------------------------
		
		$this->mail_headers['MIME-Version']			= "1.0";
		$this->mail_headers['Date'] 				= date( "r" );
		$this->mail_headers['Return-Path']			= $this->from;
		$this->mail_headers['X-Priority']			= "3";
		$this->mail_headers['X-MSMail-Priority']	= "Normal";
		$this->mail_headers['X-Mailer']				= "IPS PHP Mailer";
		
		//-----------------------------------------
		// From and to...
		//-----------------------------------------
		
		if( $this->from_display )
		{
			$this->mail_headers['From']		= '"' . $this->from_display . '" <' . $this->from . '>';
		}
		else
		{
			$this->mail_headers['From']		= '<' . $this->from . '>';
		}
		
		if ( $this->mail_method != 'smtp' )
		{
			if( count( $this->bcc ) > 0 )
			{
				$this->mail_headers['Bcc']	= implode( "," , $this->bcc );
			}
		}
		else
		{
			if ( $this->to )
			{
				$this->mail_headers['To']	= $this->to;
			}

			$this->mail_headers['Subject']	= $this->subject;
		}

		//-----------------------------------------
		// Attachments?
		//-----------------------------------------
		
		if ( count($this->parts) > 0 )
		{
			if ( ! $this->html_email )
			{
				$extra_headers[0]['Content-Type']	= "multipart/mixed;\n\tboundary=\"".$this->boundry."\"";
				$extra_headers[0]['notencode']		= "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				$extra_headers[1]['Content-Type']	= "text/plain;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['notencode']		= "\n\n".$this->message."\n\n--".$this->boundry;
			}
			else
			{
				$extra_headers[0]['Content-Type']	= "multipart/mixed;\n\tboundary=\"".$this->boundry."\"";
				$extra_headers[0]['notencode']		= "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				$extra_headers[1]['Content-Type']	= "text/html;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['notencode'] 		= "\n\n".$this->message."\n\n--".$this->boundry;
			}
			
			$extra_headers[2]['notencode'] 			= $this->_buildMultipart();
			
			reset($extra_headers);
			
			foreach( $extra_headers as $subset => $the_header )
			{
				foreach( $the_header as $k => $v )
				{
					if( $k == 'notencode' )
					{
						$extra_headers_rfc .= $v;
					}
					else
					{
						$v = $this->_encodeHeaders( array( 'v' => $v ) );
						
						$extra_headers_rfc .= $k . ': ' . $v['v'] . self::header_eol;
					}
				}
			}
			
			$this->message = "";
		}
		else
		{
			//-----------------------------------------
			// HTML (hitmuhl) ?
			//-----------------------------------------
			
			if ( $this->html_email )
			{
				$extra_headers[0]['Content-Type']	= "multipart/alternative;\n\tboundary=\"".$this->boundry."\"";
				$extra_headers[0]['notencode']		= "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				$extra_headers[1]['Content-Type']	= "text/plain;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['notencode']		= "\n\n".$this->pt_message."\n\n--".$this->boundry."\n";
				$extra_headers[2]['Content-Type']	= "text/html;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[2]['notencode']		= "\n\n".$this->message."\n\n";
				
				reset($extra_headers);
				
				foreach( $extra_headers as $subset => $the_header )
				{
					foreach( $the_header as $k => $v )
					{
						if( $k == 'notencode' )
						{
							$extra_headers_rfc .= $v;
						}
						else
						{
							$v = $this->_encodeHeaders( array( 'v' => $v ) );

							$extra_headers_rfc .= $k . ': ' . $v['v'] . self::header_eol;
						}
					}
				}
				
				$this->message = "";
			}
			else
			{
				$this->mail_headers['Content-type']	= 'text/plain; charset="'.$this->char_set.'"';
			}
		}
	
		$this->_encodeHeaders();
		
		foreach( $this->mail_headers as $k => $v )
		{
			$this->rfc_headers .= $k . ": " . $v . self::header_eol;
		}
		
		//-----------------------------------------
		// Attachments extra?
		//-----------------------------------------
		
		if( $extra_headers_rfc )
		{
			$this->rfc_headers .= $extra_headers_rfc;
		}
	}
    
    
	/*-------------------------------------------------------------------------*/
	// SMTP Methods
	/*-------------------------------------------------------------------------*/
    
	/**
	 * SMTP connect
	 *
	 * @access	private
	 * @return	boolean			Connection successful
	 */
    private function _smtpConnect()
	{
		$this->smtp_fp = @fsockopen( $this->smtp_host, intval($this->smtp_port), $errno, $errstr, 30 );
		
		if ( ! $this->smtp_fp )
		{
			$this->_smtpError( "Could not open a socket to the SMTP server ({$errno}:{$errstr}" );
			return false;
		}
		
		$this->_smtpGetLine();
		
		$this->smtp_code = substr( $this->smtp_msg, 0, 3 );
		
		if ( $this->smtp_code == 220 )
		{
			//-----------------------------------------
			// HELO!, er... HELLO!
			//-----------------------------------------
			
			$this->_smtpSendCmd( "HELO " . $this->smtp_host );
			
			if ( $this->smtp_code != 250 )
			{
				$this->_smtpSendCmd( "EHLO " . $this->smtp_host );
				
				if ( $this->smtp_code != 250 )
				{
					$this->_smtpError( "HELO,EHLO" );
					return false;
				}
			}
			
			//-----------------------------------------
			// Do you like my user!
			//-----------------------------------------
			
			if ( $this->smtp_user AND $this->smtp_pass )
			{
				$this->_smtpSendCmd( "AUTH LOGIN" );
				
				if ( $this->smtp_code == 334 )
				{
					$this->_smtpSendCmd( base64_encode($this->smtp_user) );
					
					if ( $this->smtp_code != 334  )
					{
						$this->_smtpError( "Username not accepted from the server" );
						return false;
					}
					
					$this->_smtpSendCmd( base64_encode($this->smtp_pass) );
					
					if ( $this->smtp_code != 235 )
					{
						$this->_smtpError( "Password not accepted from the server" );
						return;
					}
				}
				else
				{
					$this->_smtpError( "This server does not support authorisation" );
					return;
				}
			}
		}
		else
		{
			$this->_smtpError( "Could not connect to the SMTP server" );
			return false;
		}
		
		return true;
	}
	
	/**
	 * SMTP disconnect
	 *
	 * @access	private
	 * @return	boolean			Disconnect successful
	 */
    private function _smtpDisconnect()
	{
		return @fclose( $this->smtp_fp );
	}
	
	/**
	 * SMTP: Get next line
	 *
	 * @access	private
	 * @return	void
	 */
	private function _smtpGetLine()
	{
		$this->smtp_msg = "";
		
		while ( $line = @fgets( $this->smtp_fp, 515 ) )
		{
			$this->smtp_msg .= $line;
			
			if ( substr($line, 3, 1) == " " )
			{
				break;
			}
		}
	}
	
	/**
	 * SMTP: Send command
	 *
	 * @access	private
	 * @param	string		SMTP command
	 * @return	boolean		Command successful
	 */
	private function _smtpSendCmd( $cmd )
	{
		$this->smtp_msg  = "";
		$this->smtp_code = "";
		
		@fputs( $this->smtp_fp, $cmd . "\r\n" );
		
		$this->_smtpGetLine();
		
		$this->smtp_code = substr( $this->smtp_msg, 0, 3 );
		
		return $this->smtp_code == "" ? false : true;
	}
	
	/**
	 * Encode data and make it safe for SMTP transport
	 *
	 * @access	private
	 * @param	string	Raw Data
	 * @return	string	CRLF Encoded Data
	 */
	private function _smtpCrlfEncode( $data )
	{
		$data .= "\n";
		$data  = str_replace( "\n", "\r\n", str_replace( "\r", "", $data ) );
		$data  = str_replace( "\n.\r\n" , "\n. \r\n", $data );
		
		return $data;
	}
	
	/**
	 * SMTP: Error handler
	 *
	 * @access	private
	 * @param	string		SMTP error
	 * @return	boolean
	 */
	private function _smtpError( $err="" )
	{
		$this->smtp_msg = $err;
		$this->_fatalError( $err );
		return false;
	}
	
	/**
	 * SMTP: Sends the SMTP email
	 *
	 * @access	private
	 * @return	void
	 */
	private function _smtpSendMail()
	{
		$data = $this->_smtpCrlfEncode( $this->rfc_headers . "\n\n" . $this->message );

		//-----------------------------------------
		// Wrap in brackets
		//-----------------------------------------
		
		if ( $this->wrap_brackets )
		{
			if ( ! preg_match( "/^</", $this->from ) )
			{
				$this->from = "<" . $this->from . ">";
			}
		}
		
		//-----------------------------------------
		// From:
		//-----------------------------------------
		
		$this->_smtpSendCmd( "MAIL FROM:" . $this->from );
		
		if ( $this->smtp_code != 250 )
		{
			$this->_smtpError( "Mail from command failed" );
			return false;
		}
		
		$to_arry = array( $this->to );
		
		if( count( $this->bcc ) > 0 )
		{
			foreach( $this->bcc as $bcc )
			{
				$to_arry[] = $bcc;
			}
		}
		
		//-----------------------------------------
		// To:
		//-----------------------------------------
		
		foreach( $to_arry as $to_email )
		{
			if ( $this->wrap_brackets )
			{
				$this->_smtpSendCmd( "RCPT TO:<" . $to_email . ">" );
			}
			else
			{
				$this->_smtpSendCmd( "RCPT TO:" . $to_email );
			}
			
			if ( $this->smtp_code != 250 )
			{
				$this->_smtpError( "Incorrect email address: $to_email" );
			}
		}
		
		//-----------------------------------------
		// SEND MAIL!
		//-----------------------------------------
		
		$this->_smtpSendCmd( "DATA" );
		
		if ( $this->smtp_code == 354 )
		{
			fputs( $this->smtp_fp, $data . "\r\n" );
		}
		else
		{
			$this->_smtpError( "Error writing email body to SMTP server");
			return false;
		}
		
		//-----------------------------------------
		// GO ON, NAFF OFF!
		//-----------------------------------------
		
		$this->_smtpSendCmd( "." );
		
		if ( $this->smtp_code != 250 )
		{
			$this->_smtpError( "Email was not sent successfully" );
			return false;
		}
		
		$this->_smtpSendCmd( "quit" );

		if ( $this->smtp_code != 221 )
		{
			$this->_smtpError( "Unable to exit SMTP server with 'quit' command" );
			return false;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// ATTACHMENTS
	/*-------------------------------------------------------------------------*/
	
	/**
	 * Add an attachment to the current email
	 *
	 * @access	public
	 * @param	string	File data
	 * @param	string	File name
	 * @param	string	File type (MIME)
	 * @return	void
	 */
	public function addAttachment( $data="", $name="", $ctype='application/octet-stream' )
	{
		$this->parts[] = array( 'ctype'  => $ctype,
								'data'   => $data,
								'encode' => 'base64',
								'name'   => $name
							  );
	}
	
	/**
	 * Encode an attachment
	 *
	 * @access	private
	 * @param	array	Raw data [ctype,encode,name,data]
	 * @return	string	Processed data
	 */
	private function _encodeAttachment( $part=array() )
	{
		$msg = chunk_split( base64_encode( $part['data'] ) );
		
		$headers 	= array();
		$header_str	= "";

		$headers['Content-Type'] 				= $part['ctype'] . ( $part['name'] ? "; name =\"".$part['name']."\"" : "" );
		$headers['Content-Transfer-Encoding'] 	= $part['encode'];
		$headers['Content-Disposition']			= "attachment; filename=\"".$part['name']."\"";
		
		$headers = $this->_encodeHeaders( $headers );
		
		foreach( $headers as $k => $v )
		{
			$header_str .= $k . ': ' . $v . self::header_eol;
		}
		
		$header_str .= "\n\n" . $msg . "\n";
		
		return $header_str;
	}
}