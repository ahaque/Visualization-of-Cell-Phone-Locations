<?php

/*
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          http://recaptcha.net/plugins/php/
 *    - Get a reCAPTCHA API Key
 *          http://recaptcha.net/api/getkey
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


/**
 * The reCAPTCHA server URL's
 */
define("RECAPTCHA_API_SERVER", "http://api.recaptcha.net");
define("RECAPTCHA_API_SECURE_SERVER", "https://api-secure.recaptcha.net");
define("RECAPTCHA_VERIFY_SERVER", "api-verify.recaptcha.net");

class captchaPlugin
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $member;
	
	/**
	 * Member data object
	 *
	 * @access	protected
	 * @var		object
	 */
	public $memberData;	
	
	/**
	 * Error code from reCAPTCHA
	 *
	 * @access	protected
	 * @var		string
	 */
	public $error;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry Object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		
		/* Settings */
		$this->public_key	= trim( $this->settings['recaptcha_public_key'] );
		$this->private_key	= trim( $this->settings['recaptcha_private_key'] );
		$this->useSSL		= $this->settings['logins_over_https'];
	}
	
	/**
	 * Gets the challenge HTML (javascript and non-javascript version).
	 * This is called from the browser, and the resulting reCAPTCHA HTML widget
	 * is embedded within the HTML form it was called from.
	 *
	 * @access	public
	 * @return 	string		Form HTML to display
	 */
	public function getTemplate()
	{
		if ( ! $this->public_key )
		{
			return '';
		}
	
		if ($this->useSSL) 
		{
			$server = RECAPTCHA_API_SECURE_SERVER;
		} 
		else 
		{
			$server = RECAPTCHA_API_SERVER;
		}
		
		$html	= '';
		$html	.= "<script type='text/javascript'>
						var RecaptchaOptions = { 
												lang : '{$this->settings['recaptcha_language']}',
												theme : '{$this->settings['recaptcha_theme']}'
												};
					</script>";

		$html .=  '<script type="text/javascript" src="'. $server . '/challenge?k=' . $this->public_key . '"></script>
					<noscript>
					<iframe src="'. $server . '/noscript?k=' . $this->public_key . '" height="300" width="500" frameborder="0"></iframe><br/>
					<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
					<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
					</noscript>';
														
		//-----------------------------------------
		// Return Template Bit
		//-----------------------------------------
		
		return $this->registry->output->getTemplate('global_other')->captchaRecaptcha( $html );
	}

	/**
	 * Validate the input code
	 *
	 * @access	public
	 * @return	boolean		Validation successful
	 * @since	1.0
	 */
	public function validate()
	{
		if ( !$this->private_key )
		{
			return '';
		}
		
		$captcha_unique_id	= $_REQUEST['recaptcha_challenge_field'];
		$captcha_input		= $_REQUEST['recaptcha_response_field'];

		if ( $captcha_input == null || strlen($captcha_input) == 0 || $captcha_unique_id == null || strlen($captcha_unique_id) == 0) 
		{
			return false;
		}
		
		$response = $this->_recaptchaPost( RECAPTCHA_VERIFY_SERVER, "/verify",
																			array (
																				'privatekey'	=> $this->private_key,
																				'remoteip'		=> $this->member->ip_address,
																				'challenge'		=> $captcha_unique_id,
																				'response'		=> $captcha_input
																			)
												);
		
		$answers	= explode( "\n", $response [1] );

		if ( trim($answers[0]) == 'true' ) 
		{
			return TRUE;
		}
		else
		{
			/**
			 * It's an error
			 */
			$this->error	= $answers[1];
			return FALSE;
		}
	}
	
	/**
	 * Gets a URL where the user can sign up for reCAPTCHA. If your application
	 * has a configuration page where you enter a key, you should provide a link
	 * using this function.
	 *
	 * @access	public
	 * @param	string	$domain		The domain where the page is hosted
	 * @param	string	$appname	The name of your application
	 */
	public function getSignupUrl( $domain = null, $appname = null ) 
	{
		return "http://recaptcha.net/api/getkey?" .  $this->_encodeQueryString( array( 'domain' => $domain, 'app' => $appname ) );
	}
	
	/**
	 * Submits an HTTP POST to a reCAPTCHA server
	 *
	 * @access	private
	 * @param	string 		$host
	 * @param	string		$path
	 * @param	array 		$data
	 * @param	int 		port
	 * @return	array 		response
	 */
	private function _recaptchaPost( $host, $path, $data, $port = 80 )
	{
		$req = $this->_encodeQueryString( $data );
		
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
		$http_request .= "Content-Length: " . strlen($req) . "\r\n";
		$http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
		$http_request .= "\r\n";
		$http_request .= $req;
		
		$response = '';
		
		if( false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) 
		{
			return false;
		}
		
		fwrite($fs, $http_request);
		
		while ( !feof($fs) )
		{
			$response .= fgets($fs, 1160); // One TCP-IP packet
		}

		fclose($fs);

		$response = explode( "\r\n\r\n", $response, 2 );

		return $response;
	}
	
	/**
	 * Encode array of data into a query string
	 *
	 * @access	private
	 * @param	array 		$data
	 * @return	string 		query string
	 */
	private function _encodeQueryString( $data )
	{
		$req = "";
		
		foreach ( $data as $key => $value )
		{
			$req .= $key . '=' . urlencode( stripslashes($value) ) . '&';
		}

		// Cut the last '&'
		$req = substr( $req, 0, strlen($req)-1 );

		return $req;
    }
	
}
