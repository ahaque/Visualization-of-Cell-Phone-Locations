<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Ouput format: HTML
 * (Matt Mecham)
 * Last Updated: $Date: 2009-08-28 05:22:55 -0400 (Fri, 28 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 5053 $
 *
 */

interface interface_output
{
	/**
	 * Prints any header information for this output module
	 *
	 * @access	public
	 * @return	void		Prints header() information
	 */
	public function printHeader();
	
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
	public function fetchOutput( $output, $title, $navigation, $documentHeadItems, $jsLoaderItems, $extraData=array() );
	
	/**
	 * Finish / clean up after sending output
	 *
	 * @access	public
	 * @return	null
	 */
	public function finishUp();
	
	/**
	 * Adds more items into the document header like CSS / RSS, etc
	 *
	 * @access	public
	 * @return   null
	 */
	public function addHeadItems();
	
	/**
	 * Replace IPS tags
	 * Converts over <#IMG_DIR#>, etc
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function parseIPSTags( $text );
	
	/**
	 * Silent redirect (Redirects without a screen or other notification)
	 *
	 * @access	public
	 * @param	URL
	 * @return	mixed
	 */
	public function silentRedirect( $url );
	
}

class coreOutput
{
	/**
	 * Main output class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $output;
	
	/**
	 * Header code and status
	 *
	 * @access	 protected
	 * @var	 	 int
	 */
	protected $_headerCode   = 200;
	protected $_headerStatus = 'OK';
	
	/**
	 * Header expiration
	 *
	 * @access	protected
	 * @var		int				Seconds
	 */
	protected $_headerExpire = 0;
	
	/**
	 * Meta tags
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_metaTags = array();
	
	/**
	 * Type of output (redirect / popup / normal)
	 * Some of which will have no meaning to some output engines of course
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_outputType = 'normal';
	
	/**
	 * Constructor
	 * We could use 'extends' and build the registry object up but
	 * we need to use the output handler attached to the registry to 
	 * save spawning new handlers for 'output' which will have different
	 * variables saved in navigation, addToHead, etc
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( output $output )
	{
		/* Make object */
		$this->output     =  $output;
		$this->registry   =  $output->registry;
		$this->DB	      =  $output->DB;
		$this->settings   =& $output->settings;
		$this->request    =& $output->request;
		$this->lang	      =  $output->lang;
		$this->member     =  $output->member;
		$this->memberData =  $output->memberData;
		$this->cache	  =  $output->cache;
		$this->caches	  =  $output->caches;
		$this->skin		  =  $output->skin;
	}
	
	/**
	 * Set the cache expiration in seconds
	 *
	 * @access	public
	 * @param	int			Seconds to expire (60 == 1 minute, etc)
	 */
	public function setCacheExpirationSeconds( $seconds='' )
	{
		$this->_headerExpire = intval( $seconds );
	}
	
	/**
	 * Set the header code
	 *
	 * @access	public
	 * @param	int			Header code (200/301, etc)
	 * @param	string		[Optional status if omitted, the function will best guess]
	 */
	public function setHeaderCode( $code, $status='' )
	{
		$this->_headerCode   = intval( $code );
		$this->_headerStatus = $status;
		
		if ( ! $this->_headerStatus )
		{
			switch( $this->_headerCode )
			{
				case 200:
					$this->_headerStatus = 'OK';
				break;
				case 301:
					$this->_headerStatus = 'Moved Permanently';
				break;
			}
		}
	}
	
	/**
	 * Add Canonical Tag
	 * @example:  $output->addCanonicalTag( 'showtopic=xx', 'my-test-topic', 'showtopic' );
	 *
	 * @access	public
	 * @param	string		URL bit (showtopic=x)
	 * @param	string		SEO Title (my-test-topic)
	 * @param	string		SEO Template (showtopic)
	 * @return	void
	 */
	public function addCanonicalTag( $urlBit, $seoTitle, $seoTemplate )
	{
		/* Build it */
		if ( $urlBit AND $seoTemplate )
		{
			$url = $this->registry->getClass('output')->buildSEOUrl( $urlBit, 'public', $seoTitle, $seoTemplate );
			
			if ( $url )
			{ 
				$this->registry->getClass('output')->addToDocumentHead( 'raw' , '<link rel="canonical" href="' . $url . '" />' );
			}
		}
	}
	
	/**
	 * Add meta tag
	 * @example:  $output->addMetaTag( 'description', 'This is a short description' );
	 *
	 * @access	public
	 * @param	string		tag name
	 * @param	string		tag content
	 * @param	boolean		Encode content
	 */
	public function addMetaTag( $tag, $content, $encode='' )
	{
		$encode = ( $encode === FALSE ) ? FALSE : TRUE;
		
		switch( $tag )
		{
			case 'description':
				$content = IPSText::truncate( strip_tags($content), 247 );
			break;
			case 'keywords':
				if ( $encode === TRUE )
				{
					//Bug #15323 breaks accented characters, etc
					//$content = strtolower( preg_replace( "/[^0-9a-zA-Z ]/", "", preg_replace( "/&([^;]+?);/", "", $content ) ) );
					$content = str_replace( array( '.', ',', '!', ':', ';', "'", "'", '@', '%', '*', '(', ')' ), '', preg_replace( "/&([^;]+?);/", "", $content ) );
					$_vals   = preg_split( "/\s+?/", $content, -1, PREG_SPLIT_NO_EMPTY );
					$_sw     = explode( ',', $this->lang->words['_stopwords_'] );
					$_fvals  = array();
					$_limit  = 30;
					$_c      = 0;
					
					if ( is_array( $_vals ) )
					{
						foreach( $_vals as $_v )
						{
							if ( strlen( $_v ) >= 3 AND ! in_array( $_v, array_values( $_fvals ) ) AND ! in_array( $_v, $_sw ) )
							{
								$_fvals[] = $_v;
							}
							
							if ( $_c >= $_limit )
							{
								break;
							}
							
							$_c++;
						}
					}
					
					$content = implode( ',', $_fvals );
				}
			break;
		}
		
		$this->_metaTags[ $tag ] = ( $encode === TRUE ) ? htmlspecialchars( $content ) : $content;
	}
	
	/**
	 * initiate
	 * Function to do global stuff
	 *
	 * @access	public
	 */
	public function core_initiate()
	{
		//-----------------------------------------
		// Server load
		//-----------------------------------------
		
		if ( ! ipsRegistry::$server_load  )
        {
        	ipsRegistry::$server_load = '--';
        }
        
        if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
		{
			ipsRegistry::$server_load = ipsRegistry::$server_load . '%';
		}
		
		//-----------------------------------------
		// Set up defaults
		//-----------------------------------------
		
		$this->memberData['msg_count_new']   = intval($this->memberData['msg_count_new']);
        $this->memberData['msg_count_total'] = intval($this->memberData['msg_count_total']);
	}
	
	/**
	 * Set output type
	 *
	 * @access	public
	 * @param	string
	 */
	public function core_setOutputType( $type )
	{
		$this->_outputType = $type;
	}
	
	/**
	 * Fetch applications
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function core_fetchApplicationData()
	{
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$_appShow  = 1;
			$_appActive = 0;
			
			if ( IPSLib::appIsInstalled( $app_dir ) !== TRUE )
			{
				continue;
			}
			
			if( $app_data['app_hide_tab'] )
			{
				continue;
			}
			
			if ( $app_dir == 'core' OR $app_dir == 'forums' OR $app_dir == 'members' )
			{
				$_appShow = 0;
			}
			
			if ( IPS_APP_COMPONENT == $app_dir )
			{
				$_appActive = 1;
			}
			
			$applications[ $app_dir ] = array( 'app_dir'    => $app_dir,
											   'app_title'  => $app_data['app_public_title'],
											   'app_show'   => $_appShow,
											   'app_active' => $_appActive,
											   'app_data'   => $app_data );
											
		}
		
		return $applications;
	}
	
	/**
	 * Add items into the document head
	 * Simple redirect function
	 *
	 * @access	protected
	 * @param	string		Type of head item
	 * @param	mixed 		Data
	 * @return	null
	 */
	protected function addToDocumentHead( $type, $data )
	{
		return $this->output->addToDocumentHead( $type, $data );
	}
	
	/**
	 * Add CSS files
	 *
	 * @access	public
	 * @param	string		inline or import
	 * @param	string		Data to add
	 * @return	void
	 */
	public function addCSS( $type, $data )
	{
		if( $type == 'inline' )
		{
			$this->_css['inline'][]	= array(
											'content'	=> $data,
											);
		}
		else if( $type == 'import' )
		{
			if( ! $this->_css['import'][$data] )
			{
				$this->_css['import'][$data] = array(
													'content'	=> $data,
												);
			}
		}
	}
}