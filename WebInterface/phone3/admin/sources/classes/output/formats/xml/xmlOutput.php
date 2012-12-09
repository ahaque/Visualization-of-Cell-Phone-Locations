<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Ouput format: HTML
 * (Matt Mecham)
 * Last Updated: $Date: 2009-02-16 13:44:12 -0500 (Mon, 16 Feb 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 3998 $
 *
 */

class xmlOutput extends coreOutput implements interface_output 
{
	/**
	 * Main output class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $output;
	
	/**
	 * CSS array
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $_css = array( 'import' => array(), 'inline' => array() );
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Output object
	 * @return	void
	 */
	public function __construct( output $output )
	{
		/* Make object */
		parent::__construct( $output );
	}
	
	/**
	 * Prints any header information for this output module
	 *
	 * @access	public
	 * @return	null		Prints header() information
	 */
	public function printHeader()
	{
		//-----------------------------------------
		// Start GZIP compression
        //-----------------------------------------
        
        if ( $this->settings['disable_gzip'] != 1 )
        {
	        $buffer = "";
	        
	        if ( count( ob_list_handlers() ) )
	        {
        		$buffer = ob_get_contents();
        		ob_end_clean();
    		}
    		
        	@ob_start('ob_gzhandler');
        	print $buffer;
        }

		header("HTTP/1.0 200 OK");
		header("HTTP/1.1 200 OK");
		header( "Content-type: text/xml;charset=" . IPS_DOC_CHAR_SET );
		
		if ( $this->settings['nocache'] )
		{
			header("Cache-Control: no-cache, must-revalidate, max-age=0");
			header("Expires: 0");
			header("Pragma: no-cache");
		}
	}
	
	/**
	 * Display error
	 *
	 * @access	public
	 * @param	string		Error message
	 * @param	integer		Error code
	 * @return	mixed		You can print a custom message here, or return formatted data to be sent do registry->output->sendOutput
	 */
	public function displayError( $message, $code=0 )
	{

	}
	
	/**
	 * Display board offline
	 *
	 * @access	public
	 * @param	string		Message
	 * @return	mixed		You can print a custom message here, or return formatted data to be sent do registry->output->sendOutput
	 */
	public function displayBoardOffline( $message )
	{
		
	}
	
	/**
	 * Fetches the output
	 *
	 * @access	public
	 * @param	string		Output gathered
	 * @param	string		Title of the document
	 * @param	array 		Navigation gathered
	 * @param	array 		Array of document head items
	 * @param	array 		Array of JS loader items
	 * @param	array 		Array of extra data
	 * @return	string		Output to be printed to the client
	 */
	public function fetchOutput( $output, $title, $navigation, $documentHeadItems, $jsLoaderItems, $extraData=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$system_vars_cache = $this->caches['systemvars'];
		$showPMBox	  	   = '';

		//-----------------------------------------
		// NORMAL
		//-----------------------------------------
		
		if ( $this->_outputType == 'normal' )
		{
			//-----------------------------------------
			// Grab output
			//-----------------------------------------

			$finalOutput = $this->output->getTemplate('global')->globalTemplate( $output, $documentHeadItems, $this->_css, $jsLoaderItems,
																								   array( 'title'           => $title,
																										  'applications'    => $this->core_fetchApplicationData(),
																										  'page'			=> $this->_current_page_title  ),
																								   array( 'navigation'      => $navigation,
																								          'pmData'	        => $pmData,
																								          'showReportBox'	=> ($memberCache['report_last_updated'] < $reportsCache['last_updated'] && $memberCache['report_num'] > 0) ? 1 : 0 ),
																								   array( 'time'         => $this->registry->getClass( 'class_localization')->getDate( time(), 'SHORT', 1 ),
																										  'lang_chooser' => $this->html_buildLanguageDropDown(),
																										  'skin_chooser' => $this->html_fetchSetsDropDown(),
																										  'stats'        => $this->html_showDebugInfo(),
																										  'copyright'    => $this->html_fetchCopyright() ),
																								   array( 'ex_time'      => sprintf( "%.4f", IPSDebug::endTimer() ),
																								          'gzip_status'  => ( $this->settings['disable_gzip'] == 1 ) ? $this->lang->words['gzip_off'] : $this->lang->words['gzip_on'],
																								          'server_load'  => ipsRegistry::$server_load,
																								          'queries'      => $this->DB->getQueryCount() )
																								);
		}
		
		//-----------------------------------------
		// Grab output
		// REDIRECT
		//-----------------------------------------
		
		else if ( $this->_outputType == 'redirect' )
		{
			# SEO?
			if ( $extraData['seoTitle'] )
			{
				$extraData['url']  = $this->output->buildSEOUrl( $extraData['url'], 'none', $extraData['seoTitle'] );
				$extraData['full'] = 1;
			}
			
			$finalOutput = $this->output->getTemplate('global')->redirectTemplate( $documentHeadItems, $this->_css, $jsLoaderItems, $extraData['text'], $extraData['url'], $extraData['full'] );
		}

		//-----------------------------------------
		// POP UP
		//-----------------------------------------
		
		else if ( $this->_outputType == 'popup' )
		{
			$finalOutput = $this->output->getTemplate('global')->displayPopUpWindow( $documentHeadItems, $this->_css, $jsLoaderItems, $title, $output );
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $this->parseIPSTags( $finalOutput );
	}
	
	/**
	 * Finish / clean up after sending output
	 *
	 * @access	public
	 * @return	null
	 */
	public function finishUp()
	{
	}
	
	/**
	 * Adds more items into the document header like CSS / RSS, etc
	 *
	 * @access	public
	 * @return   null
	 */
	public function addHeadItems()
	{
	}
	
	/**
	 * Silent redirect (Redirects without a screen or other notification)
	 *
	 * @access	public
	 * @param	string		URL
	 * @param	string		[SEO Title]
	 * @return	mixed
	 */
	public function silentRedirect( $url, $seoTitle='' )
	{
		# Ensure &amp;s are taken care of
		$url = str_replace( "&amp;", "&", $url );
		
		# SEO?
		if ( $seoTitle )
		{
			$url = $this->registry->getClass('output')->buildSEOUrl( $url, 'none', $seoTitle );
		}
		
		if ( $this->settings['header_redirect'] == 'refresh' )
		{
			@header("Refresh: 0;url=".$url);
		}
		else if ( $this->settings['header_redirect'] == 'html' )
		{
			$url = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $url ) );
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			@header( "Location: ".$url );
		}
		
		exit();
	}
	
	/**
	 * Replace IPS tags
	 * Converts over <#IMG_DIR#>, etc
	 *
	 * @access	public
	 * @param	string	Unparsed text
	 * @return	string	Parsed text
	 */
	public function parseIPSTags( $text )
	{
		$text = str_replace( "<#IMG_DIR#>"      , $this->skin['set_image_dir'], $text );
		$text = str_replace( "<#EMO_DIR#>"      , $this->skin['set_emo_dir']  , $text );
		$text = str_replace( "<% CHARSET %>"    , IPS_DOC_CHAR_SET            , $text );
		$text = str_replace( "{style_image_url}", $this->settings['img_url']  , $text );
		
		if ( $this->settings['ipb_img_url'] )
		{
			$text = preg_replace( "#img\s+?src=[\"']style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\"".$this->settings['ipb_img_url']."style_\\1\\2\"\\3>", $text );
		}
		
		return $text;
	}
	
	/**
	 * Fetch copyright notice
	 *
	 * @access	private
	 * @return	string
	 */
	private function html_fetchCopyright()
	{
		//-----------------------------------------
		// REMOVAL OF THIS WITHOUT PURCHASING COPYRIGHT REMOVAL WILL VIOLATE THE LICENCE YOU AGREED
		// TO WHEN DOWNLOADING THIS PRODUCT. THIS COULD MEAN REMOVAL OF YOUR BOARD AND EVEN
		// CRIMINAL CHARGES
		//-----------------------------------------
        
		$version = ( $this->settings['ipb_display_version'] AND $this->settings['ipb_display_version'] != 0 ) ? ipsRegistry::$version : '';
		
        if ($this->settings['ipb_copy_number'] && $this->settings['ips_cp_purchase'])
        {
        	$copyright = "";
        }
        else
        {
        	$copyright = "<!-- Copyright Information -->
        				  <p id='copyright'>
        				  	Powered By <a href='http://www.invisionboard.com/' target'_blank'>IP.Board</a>
        				  	{$version} &copy; ".date("Y")." &nbsp;<a href='http://www.invisionpower.com/' title='IPS Homepage'>IPS, <abbr title='Incorporated'>Inc</abbr></a>.
        				  ";
        				  
        	if ( $this->settings['ipb_reg_show'] and $this->settings['ipb_reg_name'] )
        	{
        		$copyright .= "<div>Licensed to: ". $this->settings['ipb_reg_name']."</div>";
        	}
        	
        	
        	$copyright .= "</p>\n\t\t<!-- / Copyright -->";
        }

		return $copyright;
	}
	
	/**
	 * Returns debug data
	 *
	 * @access	private
	 * @return	string
	 */
	public function html_showDebugInfo()
    {
    }

	/**
	 * Fetch language drop down box
	 *
	 * @access	private
	 * @return   string
	 */
	private function html_buildLanguageDropDown()
    {

    }

	/**
	 * Fetch skin list
	 *
	 * Does what is says up there a bit
	 *
	 * @access	private
	 * @return	HTML		Drop down list. All nicely formatted.
	 */
	private function html_fetchSetsDropDown()
	{
	}
}