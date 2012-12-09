<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer output methods
 * Last Updated: $Date: 2009-08-07 04:26:14 -0400 (Fri, 07 Aug 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 4998 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * class output
 *
 * Class for managing skins, templates and printing output
 *
 * @author	Matt Mecham
 * @package	Invision Power Board
 * @version	3.0.0
 */
class output
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $member;
	public $cache;

	/**
	 * URLs array
	 *
	 * @access	public
	 * @var		array
	 */
	public $urls = array();

	/**
	 * Template
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $template		= '';

	/**
	 * Image url
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $imgUrl       = '';

	/**
	 * HTML to output
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_html        = '';

	/**
	 * Page title
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_title       = '';

	/**
	 * Error messages
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_errors		= array();

	/**
	 * Navigation information
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $__navigation = array();

	/**
	 * Currently in an error
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_isError     = FALSE;

	/**
	 * Navigation information
	 *
	 * @access	pubic
	 * @var		array
	 */
	public    $_navigation  = array();

	/**
	 * Warnings
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_warnings    = array();

	/**
	 * Sequence data
	 *
	 * @access	public
	 * @var		array
	 */
	public $sequenceData = array();

	/**
	 * Current page
	 *
	 * @access	public
	 * @var		string
	 */
	public $currentPage  = '';

	/**
	 * Next page
	 *
	 * @access	public
	 * @var		string
	 */
	public $nextAction   = '';

	/**
	 * Hide continue button
	 *
	 * @access	public
	 * @var		bool
	 */
	public $_hideButton  = FALSE;

	/**
	 * Install steps
	 *
	 * @access	private
	 * @var		array
	 */
	private $_installStep = array();

	/**
	 * Internal array for messages
	 *
	 * @access	private
	 * @var		array
	 */
	private $_messages = array();

	private $_curVersion = 0;
	private $_curApp     = '';

   	/**
	 * Construct
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @param	bool	Whether to init
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $init=FALSE )
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		if ( $init === TRUE )
		{
			/* Load 'template' */
			require_once( IPS_ROOT_PATH . 'setup/templates/skin_setup.php' );
			$this->template = new skin_setup( $registry );

			/* Images URL */
			$this->imageUrl = '../setup/public/images';

			/* Fetch sequence data */
			require_once( IPS_KERNEL_PATH . 'classXML.php' );
			$xml    = new classXML( IPSSetUp::charSet );
			$file   = ( IPS_IS_UPGRADER ) ? IPS_ROOT_PATH . 'setup/xml/upgrade_sequence.xml' : IPS_ROOT_PATH . 'setup/xml/sequence.xml';

			try
			{
				$xml->load( $file );

				foreach( $xml->fetchElements( 'action' ) as $xmlelement )
				{
					$data = $xml->fetchElementsFromRecord( $xmlelement );

					$_tmp[ $data['position'] ] = $data;

					ksort( $_tmp );

					foreach( $_tmp as $pos => $data )
					{
						$this->sequenceData[ $data['file'] ] = $data['menu'];
					}
				}
			}
			catch( Exception $error )
			{
				$this->addError( "Could not locate: " . $file );
			}


			/* Set up URLs */
			$this->settings['base_url']       = ( $this->settings['base_url'] ) ? $this->settings['base_url'] : IPSSetUp::getSavedData('install_url');
			$this->settings['img_url_no_dir'] = $this->settings['base_url'] . '/public/style_images/';

			/* Set Current Page */
			$this->currentPage = ( $this->request['section'] ) ? $this->request['section'] : 'index';

			if ( ! $this->sequenceData[ $this->currentPage ] )
			{
				$this->currentPage = 'index';
			}

			/* Set default next action */
			$_hit = 0;
			foreach( $this->sequenceData as $file => $text )
			{
				if ( $_hit )
				{
					$this->nextAction = $file;
					break;
				}

				if ( $file == $this->currentPage )
				{
					$_hit = 1;
				}
			}
			
			/* Build all skins array */
			if ( IPS_IS_UPGRADER )
			{
				/* For < 3.0.0 upgrades, they won't have this table, so check for it */
				if ( $this->DB->checkForTable( 'skin_collections' ) )
				{
					$this->DB->build( array( 'select' => '*',
											 'from'	  => 'skin_collections',
											 'order'  => 'set_id ASC' ) );
					$this->DB->execute();
					
					while( $_skinSets = $this->DB->fetch() )
					{
						$id = $_skinSets['set_id'];
						
						$this->allSkins[ $id ]					   = $_skinSets;
						$this->allSkins[ $id ]['_parentTree']      = unserialize( $_skinSets['set_parent_array'] );
						$this->allSkins[ $id ]['_childTree']       = unserialize( $_skinSets['set_child_array'] );
						$this->allSkins[ $id ]['_userAgents']      = unserialize( $_skinSets['set_locked_uagent'] );
						$this->allSkins[ $id ]['_cssGroupsArray']  = unserialize( $_skinSets['set_css_groups'] );
						$this->allSkins[ $id ]['_youCanUse']       = TRUE;
						$this->allSkins[ $id ]['_gatewayExclude']  = FALSE;
		
						/* Array groups */
						if ( is_array( $this->allSkins[ $id ]['_cssGroupsArray'] ) )
						{
							ksort( $this->allSkins[ $id ]['_cssGroupsArray'], SORT_NUMERIC );
						}
						else
						{
							$this->allSkins[ $id ]['_cssGroupsArray'] = array();
						}
					}
				}
			}
		}
	}

	/**
	 * Add an message string
	 *
	 * @access	public
	 * @param	string	Message
	 * @return	void
	 */
	public function addMessage( $string )
	{
		$this->_messages[] = $string;
	}

	/**
	 * Add an error string
	 *
	 * @access	public
	 * @param	string	Error
	 * @return	void
	 */
	public function addError( $string )
	{
		$this->_errors[] = $string;
	}

	/**
	 * Add a warning string
	 *
	 * @access	public
	 * @param	string	Warning
	 * @return	void
	 */
	public function addWarning( $string )
	{
		$this->_warnings[] = $string;
	}

	/**
	 * Fetch errors
	 *
	 * @access	public
	 * @return	array 	Errors
	 */
	public function fetchErrors()
	{
		return $this->_errors;
	}

	/**
	 * Fetch warnings
	 *
	 * @access	public
	 * @return	array 	Warnings
	 */
	public function fetchWarnings()
	{
		return $this->_warnings;
	}

	/**
	 * Add content
	 *
	 * @access	public
	 * @param	string		content to add
	 * @param	boolean		Prepend isntead of append
	 * @return	void
	 */
	public function addContent( $content, $prepend=false )
	{
		if( $prepend )
		{
			$this->_html = $content . $this->_html;
		}
		else
		{
			$this->_html .= $content;
		}
	}

	/**
	 * Set the current version and app
	 *
	 * @access	public
	 * @param	string		Current Human version
	 * @param	string		App key
	 * @return	void
	 */
	public function setVersionAndApp( $version, $app )
	{
		$this->_curVersion = $version;
		$this->_curApp     = $app;
	}

	/**
	 * Set the current install step
	 *
	 * @access	public
	 * @param	mixed		Current step
	 * @param	int			Total steps
	 * @return	void
	 */
	public function setInstallStep( $current, $total )
	{
		$this->_installStep = array( $current, $total );
	}

	/**
	 * Set the hide button value
	 *
	 * @access	public
	 * @param	boolean		TRUE = hide button, FALSE = show button
	 * @return	void
	 */
	public function setHideButton( $hide=FALSE )
	{
		$this->_hideButton = $hide;
	}

	/**
	 * Set the next action value
	 *
	 * @access	public
	 * @param	string		action URL
	 * @return	void
	 */
	public function setNextAction( $url )
	{
		$this->nextAction = $url;
	}

	/**
	 * Set the title of the document
	 *
	 * @access	public
	 * @param	string	Page title
	 * @return	void
	 */
	public function setTitle( $title )
	{
		$this->_title = $title;
	}

	/**
	 * Add navigational elements
	 *
	 * @access	public
	 * @param	string	Nav title
	 * @param	string	Nav URL
	 * @param	string	SEO title
	 * @param	string	SEO template
	 * @return	void
	 */
	public function addNavigation( $title, $url, $seoTitle='', $seoTemplate='' )
	{
		$this->_navigation[] = array( $title, $url, $seoTitle, $seoTemplate );
	}

	/**
	 * Set the is error flag
	 *
	 * @access	public
	 * @param	bool	Error yes/no
	 * @return	void
	 */
	public function setError( $boolean )
	{
		$this->_isError = $boolean;
	}

	/**
	 * Wrapper to return template function
	 *
	 * @access	public
	 * @return	object
	 */
	public function template()
	{
		return $this->template;
	}

    /**
	 * Main output function
	 *
	 * @access	public
	 * @access	boolean		TRUE - freeze data, FALSE, do not.
	 * @return	void	Nothin'
	 */
    public function sendOutput( $saveData=TRUE )
    {
        //-----------------------------------------
        // INIT
        //-----------------------------------------

		$_hit = 0;

		/* Options */
		$options['savedData']  = ( $saveData === TRUE ) ? IPSSetUp::freezeSavedData() : '';
		$options['hideButton'] = ( $this->_hideButton === TRUE ) ? TRUE : FALSE;
		$options['progress']   = array();

		/* Sequence progress */
		foreach( $this->sequenceData as $key => $page )
		{
			if ( $key == $this->currentPage )
			{
				$options['progress'][] = array( 'step_doing', $page );
				$_hit = 1;
			}
			else if( $_hit )
			{
				$options['progress'][] = array( 'step_notdone', $page );
			}
			else
			{
				$options['progress'][] = array( 'step_done', $page );
			}
		}

		//-----------------------------------------
		// Header
		//-----------------------------------------

        header( "HTTP/1.0 200 OK" );
		header( "HTTP/1.1 200 OK" );
		header( "Content-type: text/html;charset={$this->settings['gb_char_set']}" );
		header( "Cache-Control: no-cache, must-revalidate, max-age=0" );
		header( "Expires: 0" );
		header( "Pragma: no-cache" );

		$template = $this->template->globalTemplate( $this->_title, $this->_html, $options, $this->_errors, $this->_warnings, $this->_messages, $this->_installStep, $this->_curVersion, $this->_curApp );

		print $template;

        exit;
    }

    /**
	 * Print a redirect screen
	 * Wrapper function, really
	 *
	 * @access	public
	 * @param	string		Text to display on the redirect screen
	 * @param	string		URL to direct to
	 * @param	string		SEO Title
	 * @return	string		HTML to browser and exits
	 */
    public function redirectScreen( $text="", $url="", $seoTitle="" )
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------

		$this->_sendOutputSetUp( 'redirect' );

		//-----------------------------------------
		// Forcing silent redirects?
		//-----------------------------------------

		if ( $this->settings['ipb_remove_redirect_pages'] == 1 )
    	{
    		$this->silentRedirect( $url );
    	}

		//-----------------------------------------
		// Gather output
		//-----------------------------------------

        $output = $this->outputFormatClass->fetchOutput( $this->_html, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader, array( 'url' => $url, 'text' => $text, 'seoTitle' => $seoTitle ) );

        //-----------------------------------------
        // Check for SQL Debug
        //-----------------------------------------

        $this->_checkSQLDebug();

		//-----------------------------------------
		// Print it...
		//-----------------------------------------

		$this->outputFormatClass->printHeader();

		print $output;

		$this->outputFormatClass->finishUp();

        exit;
    }

	/**
	 * Immediate redirect
	 *
	 * @access	public
	 * @param	string		URL to redirect to
	 * @param	string		SEO Title
	 * @return	mixed
	 */
	public function silentRedirect( $url, $seoTitle='' )
	{
		return $this->outputFormatClass->silentRedirect( $url, $seoTitle );
	}

	/**
	 * Add content to the document <head>
	 *
	 * @access	public
	 * @param	string		Type of data to add: css, js, raw, rss, rsd, etc
	 * @param	string		Data to add
	 * @return	void
	 */
	public function addToDocumentHead( $type, $data )
	{
		$this->_documentHeadItems[ $type ][] = $data;
	}

	/**
	 * Process remap data
	 * For use with IN_DEV
	 *
	 * @access	public
	 * @return 	array 		Array of remap data
	 */
	public function buildRemapData()
	{
		$remapData = array();

		if ( IN_DEV and file_exists( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' ) )
		{
			require( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' );

			if ( is_array( $REMAP ) )
			{
				/* Master skins */
				foreach( array( 'templates', 'css' ) as $type )
				{
					foreach( $REMAP[ $type ] as $id => $dir )
					{
						if ( preg_match( "#^[a-zA-Z]#", $id ) )
						{
							/* we're using a key */
							$_skin = $this->_fetchSkinByKey( $id );

							$remapData[ $type ][ $_skin['set_id'] ] = $dir;
						}
						else
						{
							/* ID */
							$remapData[ $type ][ $id ] = $dir;
						}
					}
				}

				/* IN DEV default */
				if ( preg_match( "#^[a-zA-Z]#", $REMAP['inDevDefault'] ) )
				{
					$_skin = $this->_fetchSkinByKey( $REMAP['inDevDefault'] );

					$remapData['inDevDefault'] = $_skin['set_id'];
				}
				else
				{
					$remapData['inDevDefault'] = $REMAP['inDevDefault'];
				}

				/* IN DEV export */
				foreach( $REMAP['export'] as $id => $key )
				{
					if ( preg_match( "#^[a-zA-Z]#", $key ) )
					{
						$_skin = $this->_fetchSkinByKey( $key );

						$remapData['export'][ $id ] = $_skin['set_id'];
					}
					else
					{
						$remapData['export'][ $id ] = $id;
					}
				}
			}
		}
		else
		{
			$remapData = array( 'templates'    => array( 0 => 'master_skin' ),
								'css'	       => array( 0 => 'master_css' ),
								'inDevDefault' => 0 );
		}

		return $remapData;
	}

	/**
	 * Fetch a skin set via a key
	 *
	 * @access	private
	 * @param	string		Skin set key
	 * @return	array 		Array of skin data
	 */
	public function _fetchSkinByKey( $key )
	{
		if ( ! is_array( $this->allSkins ) )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_collections' ) );
			$this->DB->execute();

			while( $skin = $this->DB->fetch() )
			{
				$this->allSkins[ $skin['set_id'] ] = $skin;
			}
		}

		foreach( $this->allSkins as $_id => $_data )
		{
			if ( $_data['set_key'] == $key )
			{
				return $_data;
			}
		}

		return array();
	}


}