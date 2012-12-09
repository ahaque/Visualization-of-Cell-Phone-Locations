<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * BBCode parsing gateway.
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 5027 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class parseBbcode
{
	/**
	 * Allowed to update the caches if not present
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $allow_update_caches		= true;
	
	/**
	 * Parse emoticons?
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $parse_smilies			= true;

	/**
	 * Parse HTML?
	 * HIGHLY NOT RECOMMENDED IN MOST CASES
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $parse_html				= false;

	/**
	 * Parse bbcode?
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $parse_bbcode			= true;

	/**
	 * Strip quotes?
	 * Strips quotes from the resulting text
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $strip_quotes			= false;

	/**
	 * Auto convert newlines to html line breaks
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $parse_nl2br				= true;
	
	/**
	 * Parse wordwrap
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $parse_wordwrap			= 0;

	/**
	 * Bypass badwords?
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $bypass_badwords			= false;

	/**
	 * Section keyword for parsing area
	 *
	 * @access	public
	 * @var		string
	 */	
	public $parsing_section			= 'post';

	/**
	 * Group id of poster
	 *
	 * @access	public
	 * @var		int
	 */	
	public $parsing_mgroup			= 0;
	
	/**
	 * Value of mgroup_others for poster
	 *
	 * @access	public
	 * @var		string
	 */	
	public $parsing_mgroup_others	= '';
	
	/**
	 * Error code stored
	 *
	 * @access	public
	 * @var		string
	 */	
	public $error					= '';
	
	/**
	 * BBCode library object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $bbclass;
	
	/**
	 * Already loaded the classes?
	 *
	 * @access	protected
	 * @var		boolean
	 */	
	protected $classes_loaded		= false;

	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	string		Parsing method to use
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $method='normal' )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->pre_edit_parse_method	= $method;
		
		/* Initialize our bbcode class */
		$this->_loadClasses();
		
		/* And some default properties */
		$this->bypass_badwords	= $this->memberData ? intval( $this->memberData['g_bypass_badwords'] ) : 0;
		$this->strip_quotes		= $this->settings['strip_quotes'];
		$this->parse_wordwrap	= $this->settings['post_wordwrap'];
	}
	
	/**
	 * Load the required bbcode classes and initialize the object
	 *
	 * @access	private
	 * @return	void
	 */
	private function _loadClasses()
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		if ( ! $this->classes_loaded )
		{
			$_NOW = IPSDebug::getMemoryDebugFlag();
			
			require_once( IPS_ROOT_PATH . 'sources/classes/bbcode/core.php' );
			
			IPSDebug::setMemoryDebugFlag( "Require once call for core.php", $_NOW );
			
			switch( $this->pre_edit_parse_method )
			{
				case 'legacy':
					$file	= 'legacy.php';
					$class 	= 'class_bbcode_legacy';
					break;
				default:
					$file	= '';//'normal.php';
					$class 	= 'class_bbcode_core';
					break;
			}
			
			if( $file )
			{
				require_once ( IPS_ROOT_PATH . 'sources/classes/bbcode/' . $file );
			}
			
			$this->bbclass			= new $class( $this->registry );
			$this->classes_loaded	= true;
			$this->error			=& $this->bbclass->error;
		}
		
		IPSDebug::setMemoryDebugFlag( "BBCode classes loaded", $_NOW );
	}
	
	/**
	 * Pass off our settings to our bbcode handler
	 *
	 * @access	private
	 * @return	void
	 */
	private function _passSettings()
	{
		//-----------------------------------------
		// Pass the settings
		//-----------------------------------------

		$this->cache->updateCacheWithoutSaving( '_tmp_section', $this->parsing_section );

		$this->bbclass->bypass_badwords			= $this->bypass_badwords;
		$this->bbclass->parse_smilies			= $this->parse_smilies;
		$this->bbclass->parse_html				= $this->parse_html;
		$this->bbclass->parse_bbcode			= $this->parse_bbcode;
		$this->bbclass->strip_quotes			= $this->strip_quotes;
		$this->bbclass->parse_nl2br				= $this->parse_nl2br;
		$this->bbclass->parse_wordwrap			= $this->parse_wordwrap;
		$this->bbclass->parsing_section			= $this->parsing_section;
		$this->bbclass->parsing_mgroup			= $this->parsing_mgroup;
		$this->bbclass->parsing_mgroup_others	= $this->parsing_mgroup_others;
		$this->bbclass->initOurBbcodes();
		
		$this->cache->updateCacheWithoutSaving( '_tmp_bbcode_images', 0 );
	}
			
	/**
	 * Parses the bbcode to be stored into the database.
	 * If all bbcodes are parse on display, this method does nothing really
	 *
	 * @access	public
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be stored in database
	 */
	public function preDbParse( $text )
	{
		$this->_passSettings();

		//-----------------------------------------
		// Pass off to the main handler
		//-----------------------------------------
		
		return $this->bbclass->preDbParse( trim($text) );
	}
	
	/**
	 * Parses the bbcode to be shown in the STD editor.
	 * If all bbcodes are parse on display, this method does nothing really
	 *
	 * @access	public
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be stored in database
	 */
	public function preEditParse( $text )
	{
		$this->_passSettings();

		//-----------------------------------------
		// Parse
		//-----------------------------------------

		return $this->bbclass->preEditParse( $text );
	}
	
	/**
	 * Parses the bbcode to be shown in the browser.  Expects preDbParse has already been done before the save.
	 * If all bbcodes are parse on save, this method does nothing really
	 *
	 * @access	public
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be displayed
	 */
	public function preDisplayParse( $text )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		$this->_passSettings();

		//-----------------------------------------
		// Parse
		//-----------------------------------------
		
		$text	= $this->bbclass->preDisplayParse( $text );
		
		IPSDebug::setMemoryDebugFlag( "PreDisplayParse completed", $_NOW );
		
		return $text;
	}
	
	/**
	 * Parses the bbcode to be shown in the polls.
	 * Parses img and url, if enabled
	 *
	 * @access	public
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be displayed
	 */
	public function parsePollTags( $text )
	{
		if( $this->settings['poll_tags'] )
		{
			$text = $this->bbclass->parseBbcode( $text, 'display', array( 'img', 'url' ) );
			$text = $this->bbclass->finishNonParsed( $text, 'display' );
		}
		
		return $text;
	}

	/**
	 * Converts the STD contents to RTE compatible output
	 * Used when switching editors or taking the bbcode post and putting into the RTE
	 *
	 * @access	public
	 * @param 	string			BBCode text
	 * @return	string			RTE-compatible text
	 */
	public function convertStdToRte( $t )
	{
		//-----------------------------------------
		// Ensure no slashy slashy
		//-----------------------------------------
		
		$t	= str_replace( '"','&quot;', $t );
		$t	= str_replace( "'",'&apos;', $t );
		
		//-----------------------------------------
		// Convert <>
		//-----------------------------------------

		if( $this->parse_nl2br )
		{
			$t	= str_replace( "<br />", "\n", $t );
		}
		
		$t	= str_replace( '<', '&lt;', $t );
		$t	= str_replace( '>', '&gt;', $t );
		
		//-----------------------------------------
		// RTE expects <br /> not \n
		//-----------------------------------------
		
		$t = str_replace( "\n", "<br />", str_replace( "\r\n", "\n", $t ) );
		
		//-----------------------------------------
		// Okay, convert ready for RTE
		//-----------------------------------------

		$t	= $this->preDbParse( $t );
		$t	= $this->convertForRTE( $t );
		
		return $t;
	}
	
	/**
	 * Converts "IP.Board HTML" to regular (RTE) HTML
	 *
	 * @access	public
	 * @param 	string			Parsed text
	 * @return	string			RTE-compatible text
	 */
	public function convertForRTE( $t )
	{
		$this->_passSettings();

		return $this->bbclass->convertForRTE( $t );
	}
	
	/**
	 * Strip all HTML and bbcode tags
	 *
	 * @access	public
	 * @param 	string			BBCode + HTML text
	 * @param	boolean			Run through pre_edit_parse
	 * @param 	boolean		Check "strip from search" option
	 * @return	string			Raw text with no tags
	 */
	public function stripAllTags( $t, $pre_edit_parse=true, $only_search=true )
	{
		$this->_passSettings();
		
		return $this->bbclass->stripAllTags( $t, $pre_edit_parse, $only_search );
	}
	
	/**
	 * Strip quotes
	 *
	 * @access	public
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with no quotes
	 */
	public function stripQuotes( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->stripBbcode( 'quote', $t );
	}
	
	/**
	 * Strip emoticons
	 *
	 * @access	public
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with no emoticons
	 */
	public function stripEmoticons( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->stripEmoticons( $t );
	}
	
	/**
	 * Unconvert emoticons
	 *
	 * @access	public
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with text emoticons
	 */
	public function unconvertSmilies( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->unconvertSmilies( $t );
	}
	
	/**
	 * Strip badwords
	 *
	 * @access	public
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with no badwords
	 */
	public function stripBadWords( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->badWords( $t );
	}
	
	/**
	 * Apply word wrapping
	 *
	 * @access	public
	 * @param 	string			BBCode + HTML text
	 * @param	integer			Number of characters to wrap after
	 * @param	string			Break string
	 * @return	string			Raw text with no tags
	 */
	public function wordWrap( $t, $chars=80, $break="\n" )
	{
		$this->parse_wordwrap	= $chars;
		$this->_passSettings();
		
		return $this->bbclass->applyWordwrap( $t, $chars, $break );
	}

	/**
	 * Make data in quotes "safe"
	 *
	 * @access	public
	 * @param 	string			Raw posted text
	 * @return	string			Raw text safe for use in quote tag
	 */
	public function makeQuoteSafe( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->makeQuoteSafe( $t );
	}
	
	/**
	 * Clean content from XSS (best shot at least)
	 *
	 * @access	public
	 * @param 	string			Raw posted text
	 * @param	boolean			Attempt to fix script tag
	 * @return	string			Cleaned text
	 */
	public function xssHtmlClean( $t, $fixScript=true )
	{
		$this->_passSettings();
		
		return $this->bbclass->checkXss( $t, $fixScript );
	}
	
	/**
	 * Determines if member is viewing images
	 * If not, unparses smilies
	 *
	 * @access	public
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be stored in database
	 */
	public function memberViewImages( $text )
	{
		$this->_passSettings();

		//-----------------------------------------
		// Parse
		//-----------------------------------------
		
		if ( ! $this->memberData['view_img'] )
		{
			//-----------------------------------------
			// Second regex needed for content caching
			//-----------------------------------------
			
			$text	= $this->bbclass->unconvertSmilies( $text );
			$text	= preg_replace( "/<img src=['\"](.+?)[\"'].*?class=['\"]bbc_img[\"'].*? \/>/", "\\1", $text );
			
			return $text;
		}
		else
		{
			return $text;
		}
	}
}