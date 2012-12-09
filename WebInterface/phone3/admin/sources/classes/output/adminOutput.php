<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Admin Output Library
 * Last Updated: $LastChangedDate: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 5045 $
 *
 */

class adminOutput extends output
{
	/**
	 * Output started
	 *
	 * @access	private
	 * @var		boolean
	 */
	private $_IS_PRINTED;

	/**
	 * Global ACP template
	 *
	 * @access	public
	 * @var		object
	 */
	public $global_template = '';

	/**#@+
	 * HTML variables
	 *
	 * @access	public
	 * @var		string
	 */
	public $html = '';
	public $html_help_title = '';
	public $html_help_msg   = '';
	public $html_main       = '';
	public $body_extra      = '';
	public $cm_output		= '';
	/**#@-*/

	/**#@+
	 * Navigation array entries
	 *
	 * @access	public
	 * @var		array
	 */
	public $extra_nav = array();
	public $nav       = array();
	public $core_nav  = array();
	/**#@-*/

	/**
	 * Do not build nav, we will do manually
	 *
	 * @access	public
	 * @var		bool
	 */
	public $ignoreCoreNav	= false;

	/**
	 * Page titles
	 *
	 * @access	public
	 * @var		array
	 */
	public $extra_title = array();

	/**#@+
	 * Global messages
	 *
	 * @access	public
	 * @access	string
	 */
	public $global_message;
	public $global_error;
	/**#@-*/

	/**
	 * Tab buttons
	 *
	 * @access	public
	 * @var		array
	 */
	public $tab_buttons     = array();

	/**
	 * Tabs
	 *
	 * @access	public
	 * @var		array
	 */
	public $tab_tabs        = array();

	/**
	 * Javascript action to execute on tab click
	 *
	 * @access	public
	 * @var		string
	 */
	public $tab_js_action   = '';

	/**
	 * Default tab
	 *
	 * @access	public
	 * @var		string
	 */
	public $default_tab     = '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry, TRUE );
		
		$_app = ( $this->request['app'] ) ? $this->request['app'] : IPS_APP_COMPONENT;
		
		/* Update paths and such */
		$this->settings['base_url']		= $this->settings['_original_base_url'];
		$this->settings['public_url']   = $this->settings['_original_base_url'] . '/index.php?';
		
		$this->settings['base_acp_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY;
		$this->settings['skin_acp_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . "/skin_cp";
		$this->settings['skin_app_url']	= $this->settings['skin_acp_url'] ;
		$this->settings['js_main_url' ]	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/js/';

		$this->settings['js_app_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/' . IPSLib::getAppFolder( $_app ) . '/' . $_app . '/js/';

		if ( ipsRegistry::$request['app'] )
		{
			$this->settings['skin_app_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/' . IPSLib::getAppFolder( $_app ) . '/' . $_app . "/skin_cp/";
		}

		/* Update base URL */
		if ( $this->member->session_type == 'cookie' )
		{
			$this->settings['base_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/index.php?';

		}
		else
		{
			$this->settings['base_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/index.php?adsess=' . $this->request['adsess'] . '&amp;';
		}

		$this->settings['_base_url']	= $this->settings['base_url'];

		$this->settings['base_url'] =  $this->settings['base_url'] . 'app=' . IPS_APP_COMPONENT . '&amp;';

		$this->settings['extraJsModules']	= '';
	}

	/**
	 * Load a root (non-application) template
	 *
	 * @access	public
	 * @param	string		Template name
	 * @return	object
	 */
	public function loadRootTemplate( $template )
	{
		require_once( IPS_ROOT_PATH . "skin_cp/" . $template . ".php" );
		return new $template( ipsRegistry::instance() );
	}

	/**
	 * Load a template file
	 *
	 * @access	public
	 * @param	string		Template name
	 * @param	string		Application [defaults to current application]
	 * @return	object
	 */
	public function loadTemplate( $template, $app='' )
	{
		$app = $app ? $app : IPS_APP_COMPONENT;

		/* Skin file exists? */
		if ( file_exists( IPSLib::getAppDir(  $app ) . "/skin_cp/".$template.".php" ) )
		{
			$_pre_load = IPSDebug::getMemoryDebugFlag();

			require_once( IPSLib::getAppDir(  $app ) . "/skin_cp/".$template.".php" );

			IPSDebug::setMemoryDebugFlag( "CORE: Template Loaded ($template)", $_pre_load );

			return new $template( $this->registry );
		}
		else
		{
			$this->showError( "Could not locate template: $template", 4100, true );
		}
	}

	/**
	 * Show a download dialog box
	 *
	 * @access	public
	 * @param	string		Data for the download
	 * @param	string		Filename
	 * @param	string		Mime-type to send to browser
	 * @param	boolean		Compress the download
	 * @return	void
	 */
	public function showDownload( $data, $name, $type="unknown/unknown", $compress=true )
	{
		if ( $compress and @function_exists('gzencode') )
		{
			$name .= '.gz';
			//$type = 'application/x-gzip';
		}
		else
		{
			$compress = false;
		}

		header('Content-Type: '.$type);
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $name . '"');

		if ( ! $compress )
		{
			@header('Content-Length: ' . strlen($data) );
		}

		@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		@header('Pragma: public');

		if ( $compress )
		{
			print gzencode($data);
		}
		else
		{
			print $data;
		}

		exit();
	}

	/**
	 * Print a popup window - wraps HTML page in minimalized output
	 *
	 * @access	public
	 * @return	void
	 */
	public function printPopupWindow()
	{
		$this->_sendOutputSetUp( 'popup' );

		//-----------------------------------------
		// Figure out title...
		//-----------------------------------------
		$this->html_title = "IP.Board: ";

		if ( ipsRegistry::$current_application )
		{
			$this->html_title .= " &gt; " . ipsRegistry::$applications[ ipsRegistry::$current_application ]['app_title'];

			if ( ipsRegistry::$current_module )
			{
				$this->html_title .= " &gt; " . ipsRegistry::$modules_by_section [ ipsRegistry::$current_application ][ ipsRegistry::$current_module ]['sys_module_title'];
			}
		}

		$html = str_replace( '<%CONTENT%>'     , $this->html               , $this->global_template->global_main_popup_wrapper() );
		$html = str_replace( "<%TITLE%>"       , $this->html_title         , $html );
		$html = str_replace( "<%BODYEXTRA%>"   , ' '.$this->body_extra               , $html );
		$html = preg_replace( "#{txt\.(.+?)}#e", "IPSText::\$words['\\1']", $html );
		print $html;

		exit();
	}

	/**
	 * Redirect user to another page
	 *
	 * @access	public
	 * @param	string		Page to redirect to
	 * @param	string		Text to show during redirect
	 * @param	integer		Number of seconds between page loads
	 * @param	boolean		Allow a populated $this->registry to stop the redirect with the option to continue
	 * @return	void
	 */
	public function redirect($url, $text, $time=2, $allowErrorToHalt=FALSE)
	{
		/* Check for an error message */
		if ( $allowErrorToHalt !== FALSE AND $this->registry->output->global_error )
		{
			$this->html_title = $this->lang->words['redirect_halt_title'];
	
			$this->html      = $this->global_template->global_redirect_halt( $url );
			$this->html_main = $this->registry->getClass('output')->global_template->global_frame_wrapper();
	
			$this->sendOutput();
			exit();
		}
		
		$this->_sendOutputSetUp( 'redirect' );

		//------------------------------------------
		// Got board URL in url?
		//------------------------------------------

		if( !$url )
		{
			$url	= $this->settings['_base_url'];
		}

		if ( strpos( $url, $this->settings['_original_base_url'] ) === false )
		{
			$url = $this->settings['_original_base_url'].'&'.$url;
		}

		if ( $this->global_message )
		{
			$url .= '&messageinabottleacp='.urlencode( $this->global_message );
		}

		$this->global_message   = "";
		$this->html_title = $this->lang->words['redirect_page_text'];

		$html = $this->global_template->global_redirect( $url, $time, $text );

		$this->html = str_replace( '<%CONTENT%>'   , $html                      , $this->global_template->global_main_wrapper_no_furniture(IPS_DOC_CHAR_SET, $this->_css ) );
		$this->html = str_replace( '<%TITLE%>'     , $this->html_title, $this->html );
		$this->html = str_replace( '<%PAGE_NAV%>'  , $this->html_title, $this->html );

		@header("Content-type: text/html");
		print $this->html;
		exit();
	}

	/**
	 * Redirect user to another page with no intermediary screen
	 *
	 * @access	public
	 * @param	string		Url to send the user to
	 * @param	boolean		Allow a populated $this->registry to stop the redirect with the option to continue
	 * @return	void
	 */
	public function silentRedirectWithMessage($url, $allowErrorToHalt=false)
	{
		/* Check for an error message */
		if ( $allowErrorToHalt !== FALSE AND $this->registry->output->global_error )
		{
			$this->html_title = $this->lang->words['redirect_halt_title'];

			$this->html_main = $this->global_template->global_redirect_halt( $url );
	
			$this->sendOutput();
			exit();
		}
		
		/* Check for a redirect message */
		$extra = "";

		if ( $this->global_message )
		{
			$extra = '&messageinabottleacp='.urlencode( $this->global_message );
		}

		$url = str_replace( "&amp;", "&", $url ) . $extra;

		/* Do the redirect */
		$this->silentRedirect( $url );
	}

	/**
	 * Initialize a multi-redirect.  Creates an iframe that continuously adds the last status to the content of the iframe.
	 *
	 * @access	public
	 * @param	string		Url to initialize
	 * @param	string		Text to initialize with
	 * @param	boolean		Add to the text
	 * @return	void
	 */
	public function multipleRedirectInit( $url, $text='', $addtotext=true )
	{
		$this->_sendOutputSetUp( 'redirect' );

		if ( $this->member->can_use_fancy_js )
		{
			$this->html .= $this->global_template->global_ajax_redirect_init( $url, $text, $addtotext );
		}
		else
		{
			$this->html .= "<iframe src='$url' scrolling='auto' border='0' frameborder='0' width='100%' height='400'></iframe>";
		}

		$this->html_main .= $this->global_template->global_frame_wrapper();
		$this->sendOutput();
	}

	/**
	 * Hit a multi-redirect.  Uses AJAX or redirect page appropriately
	 *
	 * @access	public
	 * @param	string		Url to initialize
	 * @param	string		Text to initialize with
	 * @param	boolean		Add to the text
	 * @return	void
	 */
	public function multipleRedirectHit( $url, $text='', $addtotext=true )
	{
		if ( $this->member->can_use_fancy_js )
		{
			print "acp.ajaxRefresh( '$url', '$text', $addtotext );";
			exit();
		}
		else
		{
			print $this->global_template->global_redirect_hit( $url, $text );
			exit();
		}
	}

	/**
	 * Finish a multi-redirect session
	 *
	 * @access	public
	 * @param	string		Text to display
	 * @return	void
	 */
	public function multipleRedirectFinish($text='Completed!')
	{
		if ( $this->member->can_use_fancy_js )
		{
			$text = str_replace( "'", "\\'", $text );

			print "\$('refreshbox').innerHTML = '<span style=\"color:red\">$text</span>'  + '<br />' + \$('refreshbox').innerHTML;";
			exit();
		}
		else
		{
			print $this->global_template->global_redirect_done( $text );
			exit();
		}
	}

	/**
	 * Display an error page
	 *
	 * @access	public
	 * @param	string		Text to display
	 * @param	integer		Error code
	 * @param	boolean		Log error message
	 * @param   string		Extra log data
	 * @return	void
	 */
	public function showError( $message, $code=0, $logError=FALSE, $logExtra='' )
	{
		$this->_sendOutputSetUp( 'error' );

		$this->lang->loadLanguageFile( array( 'admin_global' ), 'core' );
		
		$message	= $message ? $message : 'no_permission';
		$message	= ( isset($this->lang->words[ $message ]) ) ? $this->lang->words[ $message ] : $message;
		
		//-----------------------------------------
    	// Log all errors above set level?
    	//-----------------------------------------

    	if( $code )
    	{
    		if( $this->settings['error_log_level'] )
    		{
    			$level = substr( $code, 0, 1 );

				if( $this->settings['error_log_level'] == 1 )
				{
					$logError = true;
				}
				else if( $level > 1 )
				{
					if( $level >= $this->settings['error_log_level'] - 1 )
					{
						$logError = true;
					}
				}
			}
    	}

		//-----------------------------------------
    	// Log the error, if needed
    	//-----------------------------------------

		if( $logError )
		{
			$this->logErrorMessage( $message, $code );
		}

		//-----------------------------------------
    	// Send notification if needed
    	//-----------------------------------------

    	$this->sendErrorNotification( $message, $code );

		//-----------------------------------------
    	// Finally, output
    	//-----------------------------------------

		$this->html_main	= $this->global_template->global_frame_wrapper();
		$this->html			= $this->global_template->system_error( $message, $code );
		$this->sendOutput();
	}

	/**
	 * Output the HTML to the browser
	 *
	 * @access	public
	 * @return	void
	 */
	public function sendOutput()
	{
		$this->_sendOutputSetUp( 'normal' );

		//---------------------------------------
		// INIT
		//-----------------------------------------

		$clean_module  = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		$navigation    = array();
		$_seen_nav     = array();
		$_last_nav     = '';
		$no_wrapper    = FALSE;

		//-----------------------------------------
		// Inline pop-up?
		//-----------------------------------------

		if ( ipsRegistry::$request['_popup'] )
		{
			$this->printPopupWindow();
			exit();
		}

		//-----------------------------------------
		// Debug?
		//-----------------------------------------

		if ( $this->DB->obj['debug'] )
        {
        	flush();
        	print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print "<h1 align='center'>SQL Total Time: {$this->DB->sql_time} for {$this->DB->query_cnt} queries</h1><br />".$this->DB->debug_html;
        	print "<br /><div align='center'><strong>Total SQL Time: {$this->DB->sql_time}</div></body></html>";
        	exit();
        }

		//-----------------------------------------
		// Sticky tabs
		//-----------------------------------------

		$this->member->setProperty( 'global_sticky_tabs', is_array( $this->memberData['global_sticky_tabs'] ) ? $this->memberData['global_sticky_tabs'] : array() );

		//-----------------------------------------
		// Start function proper
		//-----------------------------------------

		if ( $no_wrapper === FALSE )
		{
			//-----------------------------------------
			// Context sensitive stuff
			//-----------------------------------------

			if( !$this->cm_output )
			{
				$_file  = IPS_APPLICATION_PATH . 'skin_cp/cp_skin_' . $clean_module . '_context_menu.php';
				$_class = 'cp_skin_' . $clean_module . '_context_menu';

				if ( file_exists( $_file ) )
				{
					require $_file;

					$context_menu     = new $_class( $this->registry );

					$cm_function_full = ipsRegistry::$request['do'] ? 'context_menu__' . $clean_module.'__'.ipsRegistry::$request['section'].'__'.ipsRegistry::$request['do'] : 'context_menu__' . $clean_module.'__'.ipsRegistry::$request['section'];
					$cm_function      = 'context_menu__' . $clean_module.'__'.ipsRegistry::$request['section'];
					$cm_module		  = 'context_menu__' . $clean_module;

					if ( method_exists( $_class, $cm_function_full ) )
					{
						$this->cm_output = $context_menu->__wrap( $context_menu->$cm_function_full() );
					}
					else if ( method_exists( $_class, $cm_function ) )
					{
						$this->cm_output = $context_menu->__wrap( $context_menu->$cm_function() );
					}
					else if ( method_exists( $_class, $cm_module ) )
					{
						$this->cm_output = $context_menu->__wrap( $context_menu->$cm_module() );
					}
					else
					{

						if( ipsRegistry::$request['section'] != 'dashboard' && ipsRegistry::$request['do'] != 'index' )
						{
							$this->cm_output = $this->global_template->no_context_menu();
						}
					}
				}
				else
				{
					$this->cm_output = $this->global_template->no_context_menu();
				}
			}

			//-----------------------------------------
			// Global Context Menu Stuff
			//-----------------------------------------

			$_file  = IPS_ROOT_PATH . "skin_cp/cp_skin_global_context_menu.php";

			if ( file_exists( $_file ) )
			{
				require_once $_file;

				$global_context = new cp_skin_global_context_menu( $this->registry );
				$global_context_output = $global_context->get();
			}

			$html = str_replace( '<%CONTENT%>', $this->html_main, $this->global_template->global_main_wrapper(IPS_DOC_CHAR_SET, $this->_css ) );
		}
		else
		{
			$html = str_replace( '<%CONTENT%>', $this->html_main, $this->global_template->global_main_wrapper_no_furniture(IPS_DOC_CHAR_SET, $this->_css ) );
		}

		//------------------------------------------------
		// Message in a bottle?
		//------------------------------------------------
	
		$message = '';
		
		if ( $this->global_error )
		{
			$message = $this->global_template->global_error_message();
		}
		
		if ( $this->global_message )
		{
			$message .= ( $message ) ? '<br />' . $this->global_template->global_message() : $this->global_template->global_message();
		}

		//------------------------------------------------
		// Help?
		//------------------------------------------------

		/*if ( isset( $this->html_help_title ) AND isset( $this->html_help_msg ) AND $this->html_help_title != '' )
		{
			$help = $this->global_template->information_box( $this->html_help_title, $this->html_help_msg ) . "<br >";
		}*/

		//-----------------------------------------
		// Keith's help mode (tm)
		//-----------------------------------------

		if( $this->settings['acp_tutorial_mode'] )
		{
			//--------------------------------------
			// More Help? - *sigh* Keith
			//--------------------------------------

			// Decided to use request instead of (e.g.) ipsRegistry::$current_application to make it easier for us to map.
			// We can change in the future if it's preferred
			$check_key = $this->request['app'] . '_' . $this->request['module'] . '_' . $this->request['section'] . '_' . $this->request['do'] . '_';

			if( $this->request['groupHelpKey'] )
			{
				$check_key .= $this->request['groupHelpKey'];
			}

			$help .= $this->global_template->help_box( $check_key );
		}

		//-----------------------------------------
		// Figure out title...
		//-----------------------------------------

		$this->html_title = "IP.Board:";

		if ( ipsRegistry::$current_application )
		{
			$this->html_title .= " &gt; " . ipsRegistry::$applications[ ipsRegistry::$current_application ]['app_title'];

			if ( ipsRegistry::$current_module )
			{
				$this->html_title .= " &gt; " . ipsRegistry::$modules_by_section [ ipsRegistry::$current_application ][ ipsRegistry::$current_module ]['sys_module_title'];
			}
		}

		if( count($this->extra_title) )
		{
			$this->html_title .= " &gt; " . implode( ' &gt; ', $this->extra_title );
		}

		//-----------------------------------------
		// Got app menu cache?
		//-----------------------------------------

		if ( ! is_array( ipsRegistry::cache()->getCache('app_menu_cache') ) OR ! count( ipsRegistry::cache()->getCache('app_menu_cache') ) )
		{
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}

		//-----------------------------------------
		// Other tags...
		//-----------------------------------------

		// Can set the second one to none to hide left menu when no context nav is available
		$html = str_replace( "<%DISPLAY_SUB_MENU%>"   , $this->cm_output ? '' : 'none'   , $html );

		$html = str_replace( "<%TITLE%>"              , $this->html_title, $html );
		$html = str_replace( "<%SUBMENU%>"            , $this->_buildSubMenu()    , $html ); # Must be called first
		$html = str_replace( "<%MENU%>"               , $this->_buildMenu()        , $html );
		$html = str_replace( "<%CONTEXT_MENU%>"       , $this->cm_output                 , $html );
		$html = str_replace( "<%GLOBAL_CONTEXT_MENU%>", $global_context_output     , $html );
		$html = str_replace( "<%SECTIONCONTENT%>"     , $this->html      , $html );
		# This has to be called after the menu has been set so that query_string is set correctly

		$html = str_replace( "<%MSG%>"                , $message                   , $html );
		$html = str_replace( "<%HELP%>"               , isset( $help ) ? $help : '', $html );

		//-----------------------------------------
		// Fix up navigation
		//-----------------------------------------

		if ( count( $this->core_nav ) )
		{
			foreach( $this->core_nav as $data )
			{
				if ( isset( $_seen_nav[ $data[1] ] ) )
				{
					continue;
				}
				else
				{
					$_seen_nav[ $data[1] ] = 1;
				}

				$_nav = ( isset( $_last_nav['nav'] ) ) ? $_last_nav['nav'] . ' &gt; ' . $data[1] : $data[1];

				# Append last nav...
				$_last_nav = array( 'url'   => $page_location,
								 	'title' => $data[1],
								    'nav'   => $_nav );
				if ( $data[0] )
				{
					$navigation[] = "<a href='" . $data[0] . "'>" . $data[1] . "</a>";
				}
				else
				{
					$navigation[] = $data[1];
				}
			}
		}

		if ( count( $this->extra_nav ) )
		{
			foreach( $this->extra_nav as $data )
			{
				if ( isset( $_seen_nav[ $data[1] ] ) )
				{
					continue;
				}
				else
				{
					$_seen_nav[ $data[1] ] = 1;
				}

				$_nav      = ( $_last_nav['nav'] ) ? $_last_nav['nav'] . ' &gt; ' . $data[1] : $data[1];

				# Append last nav...
				$_last_nav = array( 'url'   => $page_location,
								 	'title' => $data[1],
								    'nav'   => $_nav );

				if ( $data[0] )
				{
					$navigation[] = "<a href='" . $data[0] . "'>" . $data[1] . "</a>";
				}
				else
				{
					$navigation[] = $data[1];
				}
			}
		}

		//------------------------------------------------
		// Navigation?
		//------------------------------------------------

		if ( count($navigation) > 0 )
		{
			$html = str_replace( "<%NAV%>", $this->global_template->wrap_nav( "<li>" . implode( "&nbsp; &gt; &nbsp;</li><li>", $navigation ) . "</li>" ), $html );
		}
		else
		{
			$html = str_replace( "<%NAV%>", '', $html );
		}

		//-----------------------------------------
		// Last thing, the nav element...
		//-----------------------------------------

		$html = str_replace( "<%PAGE_NAV%>", $_last_nav['title'], $html );

		$query_html = "";

		//-----------------------------------------
		// Show SQL queries
		//-----------------------------------------

		if ( IN_DEV and count( $this->DB->obj['cached_queries']) )
		{
			$queries = "";

			foreach( $this->DB->obj['cached_queries'] as $q )
			{
				$queries .= "<div style='padding:6px; border-bottom:1px solid #000'>" . htmlspecialchars($q) . '</div>';
			}

			$query_html .= $this->global_template->global_query_output($queries);

			/* Included Files */
			if ( function_exists( 'get_included_files' ) )
			{
				$__files = get_included_files();

				$query_html .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>(".count($__files).") Included Files</div><div class='row1' style='padding:6px'>\n";

				foreach( $__files as $__f )
				{
					$query_html .= "<strong>{$__f}</strong><br />";
				}

				$query_html .= '</div></div>';
			}
		}


		$html = str_replace( "<%QUERIES%>"            , $query_html                , $html );

		//-----------------------------------------
		// Got BODY EXTRA?
		//-----------------------------------------

		if ( $this->body_extra )
		{
			$html = str_replace( "<body", "<body ".$this->body_extra, $html );
		}

		//-----------------------------------------
		// Lang Replace
		//-----------------------------------------

		$html = preg_replace( "#{txt\.(.+?)}#e", "\$this->lang->words['\\1']", $html );

		//-----------------------------------------
		// Emoticons fix
		//-----------------------------------------
		
		$html = str_replace( "<#EMO_DIR#>"			, 'default'  , $html );
		
		//-----------------------------------------
		// Gzip?
		//-----------------------------------------

		if ( IPB_ACP_USE_GZIP )
		{
        	$buffer = "";

	        if( count( ob_list_handlers() ) )
	        {
        		$buffer = ob_get_contents();
        		ob_end_clean();
    		}

        	ob_start('ob_gzhandler');
        	print $buffer;
    	}

    	@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		@header("Cache-Control: no-cache, must-revalidate");
		@header("Pragma: no-cache");
		@header("Content-type: text/html; charset=" . IPS_DOC_CHAR_SET );

		//-----------------------------------------
		// OUTPUT
		//-----------------------------------------

    	print $html;

		//-----------------------------------------
		// Memory usage
		//-----------------------------------------

		if ( IPS_MEMORY_DEBUG_MODE AND defined( 'IPS_MEMORY_START' ) AND IN_DEV )
		{
			if ( is_array( IPSDebug::$memory_debug ) )
			{
				$memory .= "<br />\n<div align='center' style='margin-left:auto;margin-right:auto'><div class='tableborder' style='width:75%'>\n<div class='tableheaderalt'>MEMORY USAGE</div><div class='tablerow1' style='padding:6px'>\n";
				$memory .= "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
				$_c      = 0;

				foreach( IPSDebug::$memory_debug as $usage )
				{
					$_col = ( $_c % 2 ) ? '#eee' : '#ddd';
					$_c++;

					if ( $usage[1] > 500 * 1024 )
					{
						$_col .= ";color:#D00000";
					}
					else if ( $usage[1] < 10 * 1024 )
					{
						$_col .= ";color:darkgreen";
					}
					else if ( $usage[1] < 100 * 1024 )
					{
						$_col .= ";color:darkorange";
					}

					$memory .= "<tr><td width='60%' style='background-color:{$_col}' align='left'>{$usage[0]}</td><td style='background-color:{$_col}' align='left'><strong>".IPSLib::sizeFormat( $usage[1] )."</strong></td></tr>";
				}

				$memory .= "</table></div></div></div>";
			}

			$end       = memory_get_usage();
			$peak_end  = memory_get_peak_usage();
			$_used     = $end - IPS_MEMORY_START;
			$peak_used = $peak_end - IPS_MEMORY_START;

			print $memory;
			print "Total Memory Used: " . IPSLib::sizeFormat( $_used ) . " (Peak:".IPSText::sizeFormat( $peak_used ).")";
		}

		$this->_IS_PRINTED = 1;

    	exit();
	}

	/**
	 * Global set up stuff
	 * Sorts the JS module array, calls initiate on the output engine, etc
	 *
	 * @access	private
	 * @param	string		Type of output (normal/popup/redirect/error)
	 * @return	void
	 */
	private function _sendOutputSetUp( $type )
	{
		//----------------------------------------
		// Sort JS Modules
		//----------------------------------------

		arsort( $this->_jsLoader, SORT_NUMERIC );

		foreach( $this->_jsLoader as $k => $v )
		{
			$this->settings['extraJsModules'] .= ',' . $k;
		}

		$this->settings['extraJsModules']	= trim( $this->settings['extraJsModules'], ',' );
	}

	/**
	 * Build the primary menu
	 *
	 * @access	private
	 * @return	string		Menu HTML
	 */
	private function _buildMenu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$html          = '';
		$tabs          = array();
		$children      = array();
		$clean_module  = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		$app           = ipsRegistry::$current_application;
		$link_array    = array();

		/* Fetch fke apps */
		$fakeApps  = $this->registry->output->fetchFakeApps();
		$inFakeApp = FALSE;
		$fakeApp   = '';

		//-----------------------------------------
		// In a fake app?
		//-----------------------------------------

		foreach( $fakeApps as $_app => $_fdata )
		{
			foreach( $_fdata as $__fdata )
			{
				if ( ipsRegistry::$current_application == $__fdata['app'] AND $__fdata['module'] == ipsRegistry::$current_module )
				{
					$inFakeApp = TRUE;
					$fakeApp   = $_app;
					break 2;
				}
			}
		}

		//-----------------------------------------
		// Loop through all menus...
		//-----------------------------------------

		if ( is_array( ipsRegistry::$modules [ ipsRegistry::$current_application ] ) )
		{
			foreach( ipsRegistry::$modules [ ipsRegistry::$current_application ] as $data )
			{
				# Skip non-ACP module
				if ( ! $data['sys_module_admin'] )
				{
					continue;
				}

				$skip = TRUE;

				/* Fake app content? If so.. remove.. */
				foreach( $fakeApps as $_app => $_fdata )
				{
					foreach( $_fdata as $__fdata )
					{
						/* If the fake app matches the menu we're gonna show... */
						if ( $__fdata['app'] == $data['sys_module_application'] AND $__fdata['module'] == $data['sys_module_key'] )
						{
							$skip = ( $inFakeApp === TRUE AND $_app == $fakeApp ) ? FALSE : TRUE;
							break 2;
						}
						else
						{
							/* If we're in a fake app, skip non fake apps */
							$skip = ( $inFakeApp !== TRUE ) ? FALSE : TRUE;
						}
					}
				}

				if ( $skip === TRUE )
				{
					continue;
				}

				if ( ! $data['sys_module_parent'] )
				{
					$_tab_title = $data['sys_module_title'];
					$_tab_key   = $data['sys_module_key'];

					$tabs[ $app ]['items'][ $_tab_key ] = array( 'tab_title' => $_tab_title,
																 'tab_key'   => $_tab_key );
					$tabs[ $app ]['data'] = $data;
				}
				else
				{
					$_tab_title = $data['sys_module_title'];
					$_tab_key   = $data['sys_module_key'];

					$children[ $app ][ $data['sys_module_parent'] ][ $_tab_key ] = array( 'tab_title' => $_tab_title,
														    							  'tab_key'   => $_tab_key );
				}
			}
		}

		//-----------------------------------------
		// Build main menu
		//-----------------------------------------

		foreach( $tabs as $dir_name => $data )
		{
			$_main_key   = isset( ipsRegistry::$applications[ $tabs[ $dir_name ]['data']['sys_module_application'] ]['app_directory'] ) ? ipsRegistry::$applications[ $tabs[ $dir_name ]['data']['sys_module_application'] ]['app_directory'] : '';

			//-----------------------------------------
			// Got access for this application?
			//-----------------------------------------

			if ( ipsRegistry::getClass('class_permissions')->checkForAppAccess( $_main_key ) !== TRUE )
			{
				continue;
			}

			//-----------------------------------------
			// Only show this menu block, now.
			//-----------------------------------------

			if ( $_main_key != ipsRegistry::$current_application )
			{
				continue;
			}

			//-----------------------------------------
			// Loop through...
			//-----------------------------------------

			foreach( $tabs[ $dir_name ]['items'] as $key => $data )
			{
				$title = $data['tab_title'];
				$url   = $this->settings['_base_url'] . 'app=' . $_main_key . '&amp;module=' . $data['tab_key'];

				//-----------------------------------------
				// Got access for this module?
				//-----------------------------------------

				ipsRegistry::getClass('class_permissions')->return = 1;

				if ( ipsRegistry::getClass('class_permissions')->checkForModuleAccess( $_main_key, $data['tab_key'] ) !== TRUE )
				{
					continue;
				}

				//-----------------------------------------
				// Set navigation
				//-----------------------------------------

				if ( $_main_key == ipsRegistry::$current_application AND $clean_module == $data['tab_key'] )
				{
					// Changed this to add to the beginning of the array instead of the end, seems
					// to work better in most cases...but will have to check more.

					if( !$this->ignoreCoreNav )
					{
						array_unshift( $this->core_nav, array( $this->settings['base_url'] . 'module=' . ipsRegistry::$current_module, $title ) );
					}
				}

				//-----------------------------------------
				// Continue
				//-----------------------------------------

				$link_array[ $_main_key ][ $data['tab_key'] ] = array( 'url'    => $url,
																	   'title'  => $title,
																	   'module' => $data['tab_key'] );

				//-----------------------------------------
				// Haf ve got ze kiddivinkies?
				//-----------------------------------------

				if ( isset( $children[ $dir_name ][ $key ] ) && is_array( $children[ $dir_name ][ $key ] ) )
				{
					foreach( $children[ $dir_name ][ $key ] as $__data )
					{
						//-----------------------------------------
						// Set up
						//-----------------------------------------

						$_title = $__data['tab_title'];
						$_url   = $this->settings['_base_url'] . 'app=' . $_main_key . '&amp;module=' . $__data['tab_key'];

						//-----------------------------------------
						// Got access for this module?
						//-----------------------------------------

						ipsRegistry::getClass('class_permissions')->return = 1;

						if ( ipsRegistry::getClass('class_permissions')->checkForModuleAccess( $_main_key, $__data['tab_key'] ) !== TRUE )
						{
							continue;
						}

						//-----------------------------------------
						// Set navigation
						//-----------------------------------------

						if ( !$this->ignoreCoreNav AND $_main_key == ipsRegistry::$current_application AND $clean_module == $__data['tab_key'] )
						{
							$this->core_nav[] = array( $this->settings['base_url'] . 'module=' . ipsRegistry::$current_module, $_title );

						}

						//-----------------------------------------
						// Add it!
						//-----------------------------------------

						$link_array[ $_main_key ][ $__data['tab_key'] ] = array( 'url'    => $_url,
																			     'title'  => $_title,
																			     'module' => $__data['tab_key'] );
					}
				}

			}

			$html .= $this->global_template->menu_cat_wrap( $link_array, $clean_module, $this->menu );
		}

		//-----------------------------------------
		// OK... return
		//-----------------------------------------

		return $html;
	}

	/**
	 * Build the secondary menu
	 *
	 * @access	private
	 * @return	string		Menu HTML
	 */
	private function _buildSubMenu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$clean_module  = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		$this->menu    = array();
		$_nav_main_done = 0;

		/* Fetch fke apps */
		$fakeApps  = $this->registry->output->fetchFakeApps();
		$inFakeApp = FALSE;
		$fakeApp   = '';

		//-----------------------------------------
		// In a fake app?
		//-----------------------------------------

		foreach( $fakeApps as $_app => $_fdata )
		{
			foreach( $_fdata as $__fdata )
			{
				if ( ipsRegistry::$current_application == $__fdata['app'] AND $__fdata['module'] == ipsRegistry::$current_module )
				{
					$fakeApp   = $_app;
					$inFakeApp = TRUE;
					break 2;
				}
			}
		}
		//-----------------------------------------
		// Got a cache?
		//-----------------------------------------

		if ( IN_DEV )
		{
			ipsRegistry::cache()->updateCacheWithoutSaving( 'app_menu_cache', array() );
		}

		if ( ! is_array( ipsRegistry::cache()->getCache('app_menu_cache') ) OR ! count( ipsRegistry::cache()->getCache('app_menu_cache') ) )
		{
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}

		//-----------------------------------------
		// Get child XML tabs
		//-----------------------------------------

		if ( ipsRegistry::$current_application AND $clean_module )
		{
			//-----------------------------------------
			// Do stuff
			//-----------------------------------------

			foreach( ipsRegistry::cache()->getCache('app_menu_cache') as $app_dir => $data )
			{
				if ( ! ipsRegistry::$applications[ $app_dir ]['app_enabled'] )
				{
					continue;
				}

				/* Not in this app? */
				if ( $app_dir != ipsRegistry::$current_application )
				{
					continue;
				}

				foreach( $data as $_current_module => $module_data )
				{
					$skip = TRUE;
					$__current_module = $_current_module;

					$_current_module  = preg_replace( '/^\d+?_(.*)$/', "\\1", $_current_module );

					/* Fake app content? If so.. remove.. */
					foreach( $fakeApps as $_app => $_fdata )
					{
						foreach( $_fdata as $__fdata )
						{
							/* If the fake app matches the menu we're gonna show... */
							if ( $__fdata['app'] == $app_dir AND $__fdata['module'] == $_current_module )
							{
								if ( $inFakeApp === TRUE && $_app == $fakeApp )
								{
									$skip = FALSE;
								}
							}
							else
							{
								/* If we're in a fake app, skip non fake apps */
								if ( $inFakeApp !== TRUE )
								{
									$skip = FALSE;
								}
							}
						}
					}

					if ( $skip === TRUE )
					{
						continue;
					}

					if ( ( $app_dir == ipsRegistry::$request['app'] ) AND ! stristr( $this->settings['query_string_safe'], 'module=' ) )
					{
						$this->settings['query_string_safe'] =  $this->settings[ 'query_string_safe' ] . '&amp;module=' . $clean_module ;
					}

					foreach( $module_data['items'] as $id => $item )
					{
						//-----------------------------------------
						// Permission mask?
						//-----------------------------------------

						if ( $item['rolekey'] )
						{
							ipsRegistry::getClass('class_permissions')->return = 1;

							if ( ipsRegistry::getClass('class_permissions')->checkPermission( $item['rolekey'], $app_dir, $_current_module ) !== TRUE )
							{//print '<pre>';print $app_dir . ' '. $_current_module.'<br>';print_r($module_data);print_r($item);
								continue;
							}
						}

						//-----------------------------------------
						// Force a module/section parameter into the input array
						//-----------------------------------------

						if ( ( $app_dir == ipsRegistry::$current_application ) AND ( ipsRegistry::$current_module == $item['module'] ) AND ! ipsRegistry::$request['section'] AND $item['section'] )
						{
							ipsRegistry::$request['section'] =  $item['section'] ;
						}

						//-----------------------------------------
						// Add to nav?
						//-----------------------------------------

						if ( $app_dir == ipsRegistry::$current_application AND ipsRegistry::$request['section'] AND ( ipsRegistry::$request['section'] == $item['section'] ) AND ( ipsRegistry::$current_module == $item['module'] ) )
						{
							//-----------------------------------------
							// Sure?
							//-----------------------------------------

							$_ok            = 1;
							$__sub_item_url = ( $item['url'] ) ? '&amp;' . $item['url'] : '';

							if ( ! $_nav_main_done )
							{
								if( !$this->ignoreCoreNav )
								{
									$this->core_nav[] = array( $this->settings['base_url'] . 'module=' . $_current_module . '&amp;section=' . $item['section'], $module_data['title'] );
								}

								$_nav_main_done   = 1;

								//-----------------------------------------
								// Sort out do param?
								//-----------------------------------------

								if ( $item['url'] AND ! isset( $_GET['do'] ) )
								{
									$_do = str_replace( "do=", "", $item['url'] );

									ipsRegistry::$request['do'] = $_do;

									if ( ! stristr( $this->settings['query_string_safe'], 'section=' ) )
									{
										$this->settings['query_string_safe'] =  $this->settings[ 'query_string_safe' ] . '&amp;section=' . ipsRegistry::$request['section'];
									}

									$this->settings['query_string_safe'] = '&amp;do=' . $_do;
								}
							}

							if ( $item['url'] )
							{
								/* Reset */
								$_ok = 0;

								/* Trying something a little different with the nav */
								$_url = explode( '=', $item['url'] );

								/* Now we're first going to check for an exact do match */
								$_ok = ( $_url[1] == ipsRegistry::$request['do'] );

								/* No?  Check the Query string then */
								if( ! $_ok )
								{
									$_n = str_replace( '&amp;', '&', strtolower( $item['url'] ) );
									$_h = str_replace( '&amp;', '&', strtolower( my_getenv('QUERY_STRING') ) );

									if ( strstr( $_h, $_n ) )
									{
										$_ok = 1;
									}
								}
							}

							if ( !$this->ignoreCoreNav AND $_ok )
							{
								$this->core_nav[] = array( $this->settings['base_url'] . 'module=' . $_current_module . '&amp;section=' . $item['section'] . $__sub_item_url, $item['title'] );
							}
						}

						//-----------------------------------------
						// Continue...
						//-----------------------------------------

						if ( $item['title'] AND $item['section'] )
						{
							$this->menu[ $app_dir ][ $__current_module ]['items'][]		= array( 'title'        => $item['title'],
																							      'module'       => $_current_module,
																	   						  	  'section'      => $item['section'],
																							   	  'url'          => $item['url'],
																       							  'redirect'     => $item['redirect'] );

							$this->menu[ $app_dir ][ $__current_module ]['title']		= ( count($this->menu[ $app_dir ][ $__current_module ]['items']) > 1 ) ? $module_data['title'] : $item['title'];
						}
					}
				}
			}
		}
		//print_r($this->menu);
		if ( isset( $this->menu ) && count( $this->menu ) )
		{
			return $this->global_template->menu_sub_navigation( $this->menu );
		}
	}

	/**
	 * Action complete screen.  Shows a message with links to continue
	 *
	 * @access	public
	 * @param	string		Title
	 * @param	string		Link text
	 * @param	string		Link URL
	 * @param	integer		Seconds to redirect after [no redirect if ommitted/set to 0; default 0]
	 * @return	void
	 */
	public function doneScreen( $title, $link_text="", $link_url="", $redirect=0 )
	{
		$redirect	= intval($redirect);
		
		if ( $redirect )
		{
			$this->redirect( $this->settings[ 'base_url' ].'&'.$link_url, "<strong>{$title}</strong><br />{$this->lang->words['redirecting_to']} ".$link_text, $redirect );
		}

		$this->html_main.= $this->global_template->doneScreenView( $title, $link_text, $link_url );

		$this->sendOutput();
	}

	/**
	 * Show a page inside an iframe
	 *
	 * @access	public
	 * @param	string		URL
	 * @param	string		Optional HTML to show inside the iframe
	 * @return	void
	 */
	public function showInsideIframe($url="", $html="")
	{
		if ( $url )
		{
			$this->html .= "<iframe src='{$url}' scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='500'></iframe>";
		}
		else
		{
			$this->html .= "<iframe scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='500'>{$html}</iframe>";
		}

		$this->html_main .= $this->global_template->global_frame_wrapper();
		$this->sendOutput();
	}

	/**
	 * Generate a drop down list of groups
	 *
	 * @access 	public
	 * @param	string		Form field name
	 * @param	mixed 		Selected ID(s)
	 * @param	boolean 	Multiselect (TRUE is yes)
	 * @param	string		HTML id attribute value
	 * @return	string		HTML dropdown menu
	 */
	public function generateGroupDropdown( $formFieldName, $selected, $multiselect=FALSE, $formFieldID='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$groups = array();

		//-----------------------------------------
		// Get 'em
		//-----------------------------------------

		$cache = $this->caches['group_cache'];

		foreach( $cache as $id => $data )
		{
			$groups[] = array( $data['g_id'], $data['g_title'] );
		}

		if ( $multiselect === TRUE )
		{
			return $this->formMultiDropdown( $formFieldName, $groups, $selected, 5, $formFieldID );
		}
		else
		{
			return $this->formDropdown( $formFieldName, $groups, $selected, "", $formFieldID );
		}
	}

	/**
	 * Generate a drop down list of skins
	 *
	 * @access 	public
	 * @param	array 		Skin array
	 * @param	int			Parent id
	 * @param	int			Iteration
	 * @return	array 		Array of skins to add to dropdown
	 */
	public function generateSkinDropdown( $skin_array=array(), $parent=0, $iteration=0 )
	{
		//$skin_array		= array();
		$depthMarkers	= "";
		
		if( $iteration )
		{
			for( $i=0; $i<$iteration; $i++ )
			{
				$depthMarkers .= '--';
			}
		}

		foreach( $this->allSkins as $id => $data )
		{
			/* Root skins? */
			if ( count( $data['_parentTree'] ) AND $iteration == 0 )
			{
				continue;
			}
			else if( $iteration > 0 AND (!count( $data['_parentTree'] ) OR $data['_parentTree'][0] != $parent) )
			{
				continue;
			}

			$skin_array[] = array( $data['set_id'], $depthMarkers . $data['set_name'] );

			if ( is_array( $data['_childTree'] ) AND count( $data['_childTree'] ) )
			{
				$skin_array 	= $this->generateSkinDropdown( $skin_array, $id, $iteration + 1 );
			}
		}

		return $skin_array;
	}

	/**
	 * Create a form text input field
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	string		Field ID
	 * @param	string		Javascript code to add to field
	 * @param	string		CSS class(es) to add to field
	 * @return	string		HTML
	 */
	public function formUpload( $name="FILE_UPLOAD", $id='', $js="", $css="" )
	{
		if ($js != "")
		{
			$js = ' ' . $js . ' ';
		}

		if( $css )
		{
			$css = ' ' . $css;
		}

		return "<input class='textinput{$css}' type='file' {$js} size='30' name='{$name}'>";
	}

	/**
	 * Create a form text input field
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	string		Field current value
	 * @param	string		Field ID [defaults to value for $name]
	 * @param	integer		Field size [defaults to 30]
	 * @param	string		Field type [defaults to 'text']
	 * @param	string		Javascript code to add to field
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form input field
	 */
	public function formInput( $name, $value="", $id="", $size="30", $type='text', $js="", $css="" )
	{
		if ($js != "")
		{
			$js = ' ' . $js . ' ';
		}

		if( $css )
		{
			$css = ' ' . $css;
		}

		$id = $id ? $id : $name;

		return "<input type='{$type}' name='{$name}' id='{$id}' value='{$value}' size='{$size}' {$js} class='textinput{$css}' />";
	}

	/**
	 * Create a simpl(er) form text input field
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	string		Field current value
	 * @param	integer		Field size [defaults to 5]
	 * @return	string		Form input field
	 * @see 	formInput()
	 */
	public function formSimpleInput( $name, $value="", $size='5' )
    {
		return $this->formInput( $name, $value, $name, $size );
	}

	/**
	 * Create a form textarea field
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	string		Field current value
	 * @param	integer		Number of columns [defaults to 40]
	 * @param	integer		Number of rows [defaults to 5]
	 * @param	string		HTML id to assign to field [defaults to $name]
	 * @param	string		Javascript code to add to field
	 * @param	string		CSS class(es) to add to field
	 * @param	string		Wrap type [defaults to soft]
	 * @return	string		Form textarea field
	 */
	public function formTextarea( $name, $value="", $cols='40', $rows='5', $id="", $js="", $css="", $wrap='soft' )
	{
		$id = $id ? $id : $name;

		if ( $css )
		{
			$css = ' ' . $css;
		}

		if ($js != "")
		{
			$js = ' ' . $js . ' ';
		}

		return "<textarea name='{$name}' cols='{$cols}' rows='{$rows}' wrap='{$wrap}' id='{$id}' {$js} class='multitext{$css}'>{$value}</textarea>";
	}

	/**
	 * Create a form dropdown/select list
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	array		Options.  Multidimensional array in format of array( array( 'value', 'display' ), array( 'value', 'display' ) )
	 * @param	string		Default value
	 * @param	string		HTML id attribute [defaults to $name]
	 * @param	string		Javascript to add to list
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form dropdown list
	 */
	public function formDropdown( $name, $list=array(), $default_val="", $id="", $js="", $css="" )
	{
		if ($js != "")
		{
			$js = ' ' . $js . ' ';
		}

		if ($css != "")
		{
			$css = ' ' . $css;
		}

		$id = $id ? $id : $name;

		$html = "<select name='{$name}'" . $js . " id='{$id}' class='dropdown{$css}'>\n";

		foreach ( $list as $v )
		{
			$selected = "";

			if ( ($default_val !== "") and ($v[0] == $default_val) )
			{
				$selected = ' selected="selected"';
			}

			$html .= "<option value='" . $v[0] . "'" . $selected . ">" . $v[1] . "</option>\n";
		}

		$html .= "</select>\n\n";

		return $html;
	}

	/**
	 * Create a multiselect form field
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	array		Options.  Multidimensional array in format of array( array( 'value', 'display' ), array( 'value', 'display' ) )
	 * @param	array		Default values
	 * @param	integer		Number of items to show [defaults to 5]
	 * @param	string		HTML id attribute [defaults to $name]
	 * @param	string		Javascript to apply to field
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form multiselect field
	 */
	public function formMultiDropdown( $name, $list=array(), $default=array(), $size=5, $id="", $js="", $css='' )
	{
		if ( $js != "" )
		{
			$js = ' ' . $js . ' ';
		}

		$id = $id ? $id : $name;

		if ( $css != "" )
		{
			$css = ' ' . $css;
		}

		$html = "<select name='{$name}" . "'" . $js . " id='{$id}' class='dropdown{$css}' multiple='multiple' size='{$size}'>\n";

		foreach ($list as $v)
		{
			$selected = "";

			if ( count($default) > 0 )
			{
				if ( in_array( $v[0], $default ) )
				{
					$selected = ' selected="selected"';
				}
			}

			$html .= "<option value='" . $v[0] . "'" . $selected . ">" . $v[1] . "</option>\n";
		}

		$html .= "</select>\n\n";

		return $html;
	}

	/**
	 * Create yes/no radio buttons
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	string		Default values
	 * @param	string		HTML id attribute (appended with "_yes" and "_no" on the respective fields) [defaults to $name]
	 * @param	array 		Javascript to add to the fields.  Array keys should be 'yes' and 'no', values being the javascript to add.
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form yes/no radio buttons
	 */
	public function formYesNo( $name, $default_val="", $id='', $js=array(), $css='' )
	{
		$y_js = "";
		$n_js = "";

		if ( $js['yes'] != "" )
		{
			$y_js = $js['yes'];
		}

		if ( $js['no'] != "" )
		{
			$n_js = $js['no'];
		}

		$id = $id ? $id : $name;

		if ( $id )
		{
			$id_yes = ' id="' . $id . '_yes" ';
			$id_no  = ' id="' . $id . '_no" ';
		}

		$yes = "<span class='yesno_yes {$css}'><input type='radio' name='{$name}' value='1' {$y_js} {$id_yes} /><label for='{$id}_yes'>{$this->lang->words['yesno_yes']}</label></span>";
		$no  = "<span class='yesno_no {$css}'><input type='radio' name='{$name}' value='0' {$n_js} {$id_no} /><label for='{$id}_no'>{$this->lang->words['yesno_no']}</label></span>";

		if ( $default_val == 1 )
		{
			$yes = "<span class='yesno_yes {$css}'><input type='radio' {$id_yes} name='{$name}' value='1' {$y_js} checked='checked' /><label for='{$id}_yes'>{$this->lang->words['yesno_yes']}</label></span>";
		}
		else
		{
			$no  = "<span class='yesno_no {$css}'><input type='radio' {$id_no} name='{$name}' value='0' checked='checked' {$n_js} /><label for='{$id}_no'>{$this->lang->words['yesno_no']}</label></span>";
		}


		return $yes . $no;
	}

	/**
	 * Create a checkbox form field
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	boolean		Field checked or not
	 * @param	string 		Field value
	 * @param	string		HTML id attribute [defaults to $name]
	 * @param	string		Javascript to add to the checkbox
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form checkbox field
	 */
	public function formCheckbox( $name, $checked=false, $val=1, $id='', $js="", $css='' )
	{
		$id = $id ? $id : $name;

		if( $css )
		{
			$css = "class='{$css}' ";
		}

		if ( $js != "" )
		{
			$js = ' ' . $js . ' ';
		}

		if ( $checked == 1 )
		{
			return "<input type='checkbox' name='{$name}' value='{$val}' {$css} {$js} id='{$id}' checked='checked' />";
		}
		else
		{
			return "<input type='checkbox' name='{$name}' value='{$val}' {$css} {$js} id='{$id}' />";
		}
	}

	/**
	 * Add a table row
	 *
	 * @access	public
	 * @param	array		Cell data
	 * @param	string		CSS clasname
	 * @param	string 		align value
	 * @return	string		HTML
	 * @deprecated
	 */
	public function add_td_row( $array, $css="", $align='middle' ) {

		if (is_array($array))
		{
			$html = "<tr>\n";

			$count = count($array);

			$this->td_colspan = $count;

			for ($i = 0; $i < $count ; $i++ )
			{
				$td_col = $i % 2 ? 'tablerow2' : 'tablerow1';

				if ($css != "")
				{
					$td_col = $css;
				}

				if (is_array($array[$i]))
				{
					$text    = $array[$i][0];
					$colspan = $array[$i][1];
					$td_col  = $array[$i][2] != "" ? $array[$i][2] : $td_col;

					$html .= "<td class='$td_col' colspan='$colspan' valign='$align'>".$text."</td>\n";
				}
				else
				{
					if (isset($this->td_header[$i][1]) AND $this->td_header[$i][1] != "")
					{
						$width = " width='{$this->td_header[$i][1]}' ";
					}
					else
					{
						$width = "";
					}

					$html .= "<td class='$td_col' $width valign='$align'>".$array[$i]."</td>\n";
				}
			}

			$html .= "</tr>\n";

			return $html;
		}

	}

	/**
	 * Add a basic table row
	 *
	 * @access	public
	 * @param	string		Text
	 * @param	string		Align value
	 * @param	string		HTML id attribute
	 * @param	integer 	Colspan
	 * @return	string		HTML
	 * @deprecated
	 */
	public function add_td_basic($text="",$align="left",$id="tablerow1", $colspanint=0) {

		$html    = "";
		$colspan = "";

		if ( $colspanint )
		{
			$this->td_colspan = $colspanint;
		}

		if ($text != "")
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='".$this->td_colspan."' ";
			}


			$html .= "<tr><td align='$align' class='$id'".$colspan.">$text</td></tr>\n";
		}

		return $html;

	}

	/**
	 * End a table
	 *
	 * @access	public
	 * @return	string		HTML
	 * @deprecated
	 */
	public function end_table() {

		$this->td_header = array();  // Reset TD headers

		if ($this->has_title == 1)
		{
			$this->has_title = 0;

			return "</table></div><br />\n\n";
		}
		else
		{
			return "</table>\n\n";
		}

	}

	/**
	 * Start a table
	 *
	 * @access	public
	 * @param	string		Title
	 * @param	string		Description
	 * @return	string		HTML
	 * @deprecated
	 */
	public function start_table( $title="", $desc="") {

		$html = "";

		if ($title != "")
		{
			$this->has_title = 1;
			$html .= "<div class='tableborder'>
						<div class='tableheaderalt'>LEGACY -- FIX ME -- $title</div>\n";

			if ( $desc != "" )
			{
				$html .= "<div class='tablesubheader'>$desc</div>\n";
			}
		}



		$html .= "\n<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>";


		if (isset($this->td_header[0]))
		{
			// Auto remove two &nbsp; only headers..

			$this->td_header[1][0] = ( isset($this->td_header[1][0]) AND $this->td_header[1][0] ) ? $this->td_header[1][0] : '';
			$this->td_header[1][1] = ( isset($this->td_header[1][1]) AND $this->td_header[1][1] ) ? $this->td_header[1][1] : '';

			if ( $this->td_header[0][0] == '&nbsp;' && $this->td_header[1][0] == '&nbsp;' && ( ! isset( $this->td_header[2][0] ) ) )
			{
				$this->td_header[0][0] = '{none}';
				$this->td_header[1][0] = '{none}';
			}

			$tds = "";

			foreach ($this->td_header as $td)
			{
				if ($td[1] != "")
				{
					$width = " width='{$td[1]}' ";
				}
				else
				{
					$width = "";
				}

				if ($td[0] != '{none}')
				{
					$tds .= "<td class='tablesubheader'".$width."align='center'>{$td[0]}</td>\n";
				}

				$this->td_colspan++;
			}

			if( $tds )
			{
				$html .= "<tr>\n{$tds}</tr>\n";
			}
		}

		return $html;

	}

	/**
	 * Start a form tag
	 *
	 * @access	public
	 * @param	array		Hidden input field names => values
	 * @param	string		Form name
	 * @param	string 		Javascript to add to form tag
	 * @param	string		HTML id attribute
	 * @return	string		HTML
	 * @deprecated
	 */
	public function start_form($hiddens="", $name='theAdminForm', $js="", $id="")
	{
		if ( ! $id )
		{
			$id = $name;
		}

		$form = "LEGACY -- FIX ME --<form action='" . $this->settings['base_url'] . "' method='post' name='$name' $js id='$id'>";

		if (is_array($hiddens))
		{
			foreach ($hiddens as $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}

		//-----------------------------------------
		// Add in auth key
		//-----------------------------------------

		$form .= "\n<input type='hidden' name='_admin_auth_key' value='".ipsRegistry::getClass('adminFunctions')->_admin_auth_key."' />";

		return $form;
	}

	/**
	 * Add hidden input fields to the form
	 *
	 * @access	public
	 * @param	array		Hidden input fields
	 * @return	string		HTML
	 * @deprecated
	 */
	public function form_hidden($hiddens="")
	{
		if (is_array($hiddens))
		{
			foreach ($hiddens as $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}'>";
			}
		}

		return $form;
	}

	/**
	 * End form (expects to be inside a table)
	 *
	 * @access	public
	 * @param	string		Text
	 * @param	string		Javascript
	 * @param	string 		Extra data to put in table cell
	 * @return	string		HTML
	 * @deprecated
	 */
	public function end_form($text = "", $js = "", $extra = "")
	{
		$html    = "";
		$colspan = "";

		if ($text != "")
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='".$this->td_colspan."' ";
			}

			$html .= "<tr><td align='center' class='tablesubheader'".$colspan."><input type='submit' value='$text'".$js." class='realbutton' accesskey='s'>{$extra}</td></tr>\n";
		}

		$html .= "</form>";

		return $html;
	}

	/**
 	 * Creates a new menu.  Array should be in the format: ( link, title, [image], [delete] )
 	 * The image param is optional.
	 *
	 * @access	public
	 * @param	array 		Array of menu data
	 * @return	string		Javascript menu contents
	 */
	public function buildJavascriptMenu( $menu_array )
	{
		/* Increase the menu count */
		$this->menu_count++;

		/* Open Image */
		$open_image = "<img class='ipbmenu' id='menu{$this->menu_count}' src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt=''>\n<ul style='position: absolute; display: none; z-index: 9999;' class='acp-menu' id='menu{$this->menu_count}_menucontent'>\n";

		/* Build the entries */
		foreach( $menu_array as $menu_item )
		{
			/* Image and Link */

			$img = $menu_item[2] ? $menu_item[2] : 'manage';
			$menu_item[1] = str_replace( '"', '\"', $menu_item[1] );

			/* Delete Link */
			if( isset( $menu_item[3] ) && $menu_item[3] )
			{
				$links[] = "<li style='z-index: 10000;' class='icon {$img}'><a style='z-index: 10000;' href='#' onclick='return acp.confirmDelete(\"{$menu_item[0]}\");'>{$menu_item[1]}</a></li>";
			}
			/* Normal Link */
			else
			{
				$links[] = "<li style='z-index: 10000;' class='icon {$img}'><a style='z-index: 10000;' href='{$menu_item[0]}'>{$menu_item[1]}</a></li>";
			}
		}

		/* Create the JS Code */

		$links = implode( "\n", $links );
		$this->menu_content[$this->menu_count] = $open_image . $links . "</ul>\n";

		return $this->menu_content[$this->menu_count];
	}

	/**
 	 * Adds the button and content to the tab list for this object
	 *
	 * @access	public
	 * @param	string	$button
	 * @param	string	$content
	 * @param	string	[$js_action]
	 * @return	void
	 */
	public function addTab( $button, $content, $js_action='', $default_tab=0 )
	{
		$this->tab_buttons[]   = $button;
		$this->tab_tabs[]      = $content;
		$this->tab_js_action[] = $js_action;

		if( $default_tab )
		{
			$this->default_tab = count( $this->tab_tabs ) - 1;
		}
	}

	/**
 	 * Builds the html for the tabbed area
	 *
	 * @access	public
	 * @return	string		Tab HTML
	 * @see		admin_core_system_manage_languages
	 */
	public function buildTabs()
	{
		/* Buttons */
		$i = 0;
		$tabs = array();
		foreach( $this->tab_buttons as $i => $button )
		{
			/* Tab Button */
			$js    = ( $this->tab_js_action[$i] ) ? 'onmousedown="'.$this->tab_js_action[$i].'"' : '';
			$class = ( $i == 0 ) ? 'mini_tab_on' : 'mini_tab_off';
			$tabs[] = array( 'id' => $i, 'class' => $class, 'text' => $button, 'js' => $js );
		}

		/* Tab Contents */
		$i = 0;
		$content = array();
		foreach( $this->tab_tabs as $tab )
		{
			/* Create the pane */
			$content[] = array( 'id' => $i, 'content' => $tab );
			$i++;
		}

		$default_tab = ( $this->default_tab ) ? $this->default_tab : '';

		/* End of tab pane */
		$tabs = $this->global_template->ui_content_tabs( $tabs, $content, $default_tab );

		return $tabs;
	}

	/**
 	 * Creates an option list for select tags
	 *
	 * @access	public
	 * @param	array	$options		Array of values/text for dropdown
	 * @param	mixed	[$selected]		Selected value or array of selected values
	 * @return	string
	 */
	public function compileSelectOptions( $options, $selected='' )
	{
		if( ! is_array( $selected ) )
		{
			$selected = array( $selected );
		}

		$html = '';
		if( is_array( $options ) )
		{
			foreach( $options as $option )
			{
				$sel = ( in_array( $option[0], $selected ) && ( $option[0] ) ) ? ' selected="selected"' : '';
				$val = ( $option[0] == 'disabled'  ) ? 'disabled="disabled"' : "value='{$option[0]}'";

				$html .= "<option {$val}{$sel}>{$option[1]}</option>";
			}
		}

		return $html;
	}

    /**
	 * Build up page span links
	 *
	 * @access	public
	 * @param	array	Page data
	 * @return	string	Parsed page links HTML
	 * @since	2.0
	 */
	public function generatePagination($data)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		$work = array();

		$data['dotsSkip']			= isset($data['dotsSkip'])       ? $data['dotsSkip'] : '';
		$data['noDropdown']			= isset($data['noDropdown'])     ? intval( $data['noDropdown'] ) : 0;
		$data['startValueKey']		= isset($data['startValueKey'])	 ? $data['startValueKey']	 : '';
		$data['currentStartValue']	= isset( $data['currentStartValue'] ) ? $data['currentStartValue'] : $this->request['st'];
		$data['dotsSkip']			= ! $data['dotsSkip']            ? 2    : $data['dotsSkip'];
		$data['startValueKey']		= ! $data['startValueKey']       ? 'st' : $data['startValueKey'];
		$data['seoTitle']			= isset( $data['seoTitle'] )     ? $data['seoTitle'] : '';
		$data['uniqid']				= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );

		//-----------------------------------------
		// Get the number of pages
		//-----------------------------------------

		if ( $data['totalItems'] > 0 )
		{
			$work['pages'] = ceil( $data['totalItems'] / $data['itemsPerPage'] );
		}

		$work['pages'] = isset( $work['pages'] ) ? $work['pages'] : 1;

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$work['total_page']   = $work['pages'];
		$work['current_page'] = $data['currentStartValue'] > 0 ? ($data['currentStartValue'] / $data['itemsPerPage']) + 1 : 1;

		//-----------------------------------------
		// Loppy loo
		//-----------------------------------------

		if ($work['pages'] > 1)
		{
			for( $i = 0, $j = $work['pages'] - 1; $i <= $j; ++$i )
			{
				$RealNo = $i * $data['itemsPerPage'];
				$PageNo = $i+1;

				if ( $PageNo < ($work['current_page'] - $data['dotsSkip']) )
				{
					# Instead of just looping as many times as necessary doing nothing to get to the next appropriate number, let's just skip there now
					$i = $work['current_page'] - $data['dotsSkip'] - 2;
					continue;
				}

				if ( $PageNo > ($work['current_page'] + $data['dotsSkip']) )
				{
					$work['_showEndDots'] = 1;
					# Page is out of range...
					break;
				}

				$work['_pageNumbers'][ $RealNo ] = ceil( $PageNo );
			}
		}

		return $this->global_template->paginationTemplate( $work, $data );
	}

    /**
	 * Build a javascript help link
	 *
	 * @access	public
	 * @param	string		Help ID
	 * @param	string		Link text
	 * @return	string		Help Link
	 * @since	2.0
	 * @see		admin_core_system_quickhelp
	 */
	public function javascriptHelpLink( $help="", $text='' )
	{
		$text	= $text ? $text : $this->lang->words['acp_quick_help'];
		
		return "( <a href='#' onclick=\"window.open('" . str_replace( '&amp;', '&', $this->settings['_base_url'] ) . "&app=core&module=help&section=quickhelp&id={$help}','Help','width=250,height=400,resizable=yes,scrollbars=yes'); return false;\">{$text}</a> )";
	}

    /**
	 * Retrieve list of "fake apps" to generate tabs for them in ACP
	 *
	 * @access	public
	 * @return	array
	 * @since	3.0
	 */
	public function fetchFakeApps()
	{
		return array(
						'lookfeel' => array( array( 'app'    => 'core',
													'module' => 'templates' ),
									  		 array( 'app'    => 'core',
									 				'module' => 'posts' ),
									         array( 'app'    => 'core',
									 			    'module' => 'languages' ) ),
						'support'  => array( array( 'app'    => 'core',
												    'module' => 'diagnostics' ),
											 array( 'app'    => 'core',
												    'module' => 'sql' ),
											 array( 'app'    => 'core',
											        'module' => 'help' ),
											 
										)
			);
	}
}