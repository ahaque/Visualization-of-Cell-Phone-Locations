<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Localization Class
 *
 * Used for handling language text, money formatting, etc.
 * Last Updated: $Date: 2009-08-30 23:34:46 -0400 (Sun, 30 Aug 2009) $
 * 
 * @author		Joshua Williams <josh@>
 * @version		$Rev: 5064 $
 * @since		1.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 */ 

class class_localization
{
   /**
    * Current offset
    *
    * @access	public
    * @var		integer
    */	
	public $offset;

   /**
    * Current day
    *
    * @access	public
    * @var		integer
    */	
	public $day;
	
	/**#@+
	* Registry Object Shortcuts
	*
	* @access	protected
	* @var		object
	*/
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
   /**
    * Set up time options
    *
    * @access	public
    * @var		array
    */	
	public $time_options = array();
	
   /**
    * Array of words
    *
    * @access	public
    * @var		array
    */	
	public $words = array();
	
   /**
    * Locale
    *
    * @access	public
    * @var		string
    */		
	public $local       = "en_US";
	
   /**
    * Array of loaded language files
    *
    * @access public
    * @var array
    */		
	public $loaded_lang_files = array();

   /**
    * Offset has been set
    *
    * @access	private
    * @var		boolean
    */	
	private $offset_set;
	
   /**
    * Determines if lang entries are loaded from the db or the filesystem
    *
    * @access public
    * @var bool
    */	
	public $load_from_db = 0;
	
	/**
	 * Force UK/English language
	 *
	 * @access private
	 * @var	   boolean
	 */
	private $_forceEnglish = false;
	
	/**
	 * Constructor
	 * 
	 * @access	public
	 * @param	string	[$lang]		Language file to load, english by default
	 * @return	void
	 */		
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->DB         =  $registry->DB();
		$this->settings   =  $registry->fetchSettings();
		$this->member     =  $registry->member();
		$this->cache      =  $registry->cache();
		$this->caches     =& $registry->cache()->fetchCaches();
		$this->request    =  $registry->fetchRequest();
		$this->memberData =& $registry->member()->fetchMemberData();
		
		/* Rebuild the cache if needed */
		if( ! $this->caches['lang_data'] )
		{
			$this->rebuildLanguagesCache();		
		}
		
		/* Find the lang we need */
		if( $this->caches['lang_data'] )
		{ 
			foreach( $this->caches['lang_data'] as $_lang )
			{
				$this->languages[] = $_lang;
	
				if( $_lang['lang_default'] )
				{
					$this->local        = $_lang['lang_short'];
					$this->lang_id      = $_lang['lang_id'];
					$this->language_dir = $_lang['lang_id'];
					
					/* Guests get the default */
					if( ! $this->memberData['member_id'] )
					{
						$this->member->language_id = $this->lang_id;
					}
				}
			}
		}
		
		/* Got a guest cookie? */
		if ( ! $this->memberData['member_id'] )
		{
			$langCookie = IPSCookie::get('language');
			
			if ( $langCookie )
			{
				$this->member->language_id = trim( IPSText::parseCleanValue( $langCookie ) );
			}
		}
		
		/* Forcing Engrish? */
		if ( $forceCookie = IPSCookie::get('forceEnglish') )
		{
			if ( $forceCookie )
			{
				$this->_forceEnglish = true;
			}
		}
		
		//-----------------------------------------
		// Time options
		//-----------------------------------------
		
		/* 	%b is month abbr
			%B is full month
			%d is date 01-31
			%Y is 4 digit year
			%g is 2 digit year
			%I is hour 01-12
			%H - hour as a decimal number using a 24-hour clock (range 00 to 23) 
			%M is min 01-59
			%p is am/pm */
		
		$this->time_options = array( 'JOINED' => $this->settings['clock_joined'] ? $this->settings['clock_joined'] : '%d-%B %y',
								     'SHORT'  => $this->settings['clock_short']  ? $this->settings['clock_short']  : '%b %d %Y %I:%M %p',
									 'LONG'   => $this->settings['clock_long']   ? $this->settings['clock_long']   : '%d %B %Y - %I:%M %p',
									 'TINY'   => $this->settings['clock_tiny']   ? $this->settings['clock_tiny']   : '%d %b %Y - %H:%M',
									 'DATE'   => $this->settings['clock_date']   ? $this->settings['clock_date']   : '%d %b %Y',
									 'TIME'   => 'h:i A',
									 'ACP'    => '%d %B %Y, %H:%M',
									 'ACP2'   => '%d %B %Y, %H:%M',
								   );

		//--------------------------------
		// Did we choose a language?
		//--------------------------------

		if ( isset( $this->request['setlanguage'] ) AND $this->request['setlanguage'] AND $this->request['langid'] )
		{
			/* Forcing english? */
			if ( $this->request['langid'] == '__english__' )
			{
				IPSDebug::addMessage( "forceEnglish cookie written" );
				IPSCookie::set('forceEnglish', 1, 0 );
				$this->_forceEnglish = true;
			}
			else if ( $this->request['k'] == $this->member->form_hash AND is_array( ipsRegistry::cache()->getCache('lang_data') ) and count( ipsRegistry::cache()->getCache('lang_data') ) )
			{
				foreach( ipsRegistry::cache()->getCache('lang_data') as $data )
				{
					if ( $data['lang_id'] == $this->request['langid'] )
					{
						if( $this->memberData['member_id'] )
						{
							IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'language' => $data['lang_id'] ) ) );
						}
						else
						{
							IPSCookie::set('language', $data['lang_id'] );
						}

						$this->member->language_id = $data['lang_id'];
						$this->member->setProperty( 'language', $data['lang_id'] );
						break;
					}
				}
			}
		}

		//--------------------------------
		// Now set it
		//--------------------------------

		if( $this->member->language_id )
		{
			foreach( $this->caches['lang_data'] as $_lang )
			{
				if( $_lang['lang_id'] == $this->member->language_id )
				{
					$this->local        = $_lang['lang_short'];
					$this->lang_id      = $_lang['lang_id'];
					$this->language_dir = $_lang['lang_id'];
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Set locale
		//-----------------------------------------
		
		setlocale( LC_ALL, $this->local );
		$this->local_data = localeconv();

		//-----------------------------------------
		// Using in_dev override
		//-----------------------------------------
		
		if ( IN_DEV AND ! $this->_forceEnglish )
		{
			if ( is_dir( IPS_CACHE_PATH . 'cache/lang_cache/master_lang' ) )
			{
				$this->lang_id = 'master_lang';
			}
		}
	}
	
	/**
	 * Rebuilds the language cache
	 * 
	 * @access	public
	 * @return 	void
	 */	
	public function rebuildLanguagesCache()
	{
		$langs = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang' ) );
		$this->DB->execute();

		while( $lang = $this->DB->fetch() )
		{
			$langs[] = $lang;	
		}
		
		$this->cache->setCache( 'lang_data', $langs, array( 'name' => 'lang_data', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Formats the amount and outputs it in a nice localized format
	 * 
	 * @access	public
	 * @param	float	$amount			Numeric value to format
	 * @param	bool	[$color]		Determines if the output is colored or not      
	 * @param	string	[$force_color]	Force the output to use specified color
	 * @return	string
	 */		 
	public function formatMoney( $amount, $color=true, $force_color='' )
	{
		/* Format the money */
		if( function_exists( 'money_format' ) )
		{
			$formatted = money_format( '%n', doubleval( $amount ) );
		}
		else 
		{
			$formatted = $this->local_data['currency_symbol'] . ' ' . $this->formatNumber( $amount, 2 );
		}

		/* Color the number */
		if( ! $force_color )
		{
			if( $color )
			{
				if( $amount >= 0 )
				{
					$formatted = "<span class='money positive'>{$formatted}</span>";
				}
				else
				{
					$formatted = "<span class='money negative'>{$formatted}</span>";
				}	
			}
		}
		else
		{
			if( $color )
			{
				$formatted = "<span class='money' style='color: {$color}'>{$formatted}</span>";	
			}			
		}

		return $formatted;		
	}
	
	/**
	 * Formats a number based on localized data
	 *
	 * @access	public
	 * @param	float	$number		Number to format
	 * @param	integer	[$places]	Decimal places
	 * @return	float
	 */
	public function formatNumber( $number, $places=0 )
	{
		return str_replace( 'x', $this->local_data['thousands_sep'], number_format( $number, $places, $this->local_data['decimal_point'], 'x' ) );
	}
		
	/**
	 * Formats the current timestamp to make it read nicely
	 *
	 * @access	public
	 * @param	integer	[$ts]			Timestamp to format, $this->timestamp used if none specified
	 * @param	string	[$format]		Type of formatting to use: short or long
	 * @param	bool	[$relative]		Determines if date will be displayed in relative format
	 * @return	string
	 **/	
	public function formatTime( $ts=0, $format='short', $relative=1 )
	{
		return $this->getDate( $ts, strtoupper( $format ), $relative ? 0 : 1 );
	}
	
	/**
	 * Generate Human formatted date string
	 * Return a date or '--' if the date is undef.
	 * We use the rather nice gmdate function in PHP to synchronise our times
	 * with GMT. This gives us the following choices:
	 * If the user has specified a time offset, we use that. If they haven't set
	 * a time zone, we use the default board time offset (which should automagically
	 * be adjusted to match gmdate.         
	 *
	 * @access	public
	 * @param	integer		Unix date
	 * @param	method		LONG, SHORT, JOINED, TINY
	 * @param	integer		Do not use relative dates
	 * @param	integer		Use fully relative dates
	 * @return	string		Parsed time
	 * @since	2.0
	 */
    public function getDate($date, $method, $norelative=0, $full_relative=0)
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$format = '';

		//-----------------------------------------
		// Manual format?
		//-----------------------------------------
	
		if ( empty($method) )
        {
        	$format = $this->time_options['LONG'];
        }
		else if ( ! in_array( strtoupper($method), array_keys( $this->time_options ) ) )
		{
			if ( preg_match( "#^manual\{([^\{]+?)\}#i", $method, $match ) )
			{
				$format = $match[1];
			}
			else
			{
				$format = $this->time_options['LONG'];
			}
		}
		else
		{
			$format = str_replace( "&#092;", "\\", $this->time_options[ strtoupper( $method ) ] );
		}
		
		if( strpos( $date, "custom" ) !== false )
		{
			if( preg_match( "/{custom:(.+?)}/i", $date, $matches ) )
			{
				if( $matches[1] )
				{
					if( ! preg_match( "#^[0-9]{10}$#", $matches[1] ) )
					{
						$_time = strtotime( $matches[1] );
	
						if ( $_time === FALSE OR $_time == -1 )
						{
							$date = 0;
						}
						else
						{
							$date = $_time;
						}
					}
					else
					{
						$date	= $matches[1];
					}
				}
			}
		}

        if ( ! $date )
        {
            return '--';
        }
        
        if ($this->offset_set == 0)
        {
        	// Save redoing this code for each call, only do once per page load
        	
			$this->offset = $this->getTimeOffset();
			
			if ( $this->settings['time_use_relative'] )
			{
				$this->today_time     = gmstrftime('%m,%d,%Y', ( time() + $this->offset ) );
				$this->yesterday_time = gmstrftime('%m,%d,%Y', ( ( time() - 86400 ) + $this->offset ) );
			}	
			
			$this->offset_set = 1;
        }
        
        //-----------------------------------------
        // Full relative?
        //-----------------------------------------
        
        if ( $this->settings['time_use_relative'] == 3 )
        {
        	$full_relative = 1;
        }
        
        //-----------------------------------------
        // FULL Relative
        //-----------------------------------------

        if ( $full_relative and ( $norelative != 1 ) )
		{
			$diff = time() - $date;
			
			if ( $diff < 3600 )
			{
				if ( $diff < 120 )
				{
					return $this->words['time_less_minute'];
				}
				else
				{
					return sprintf( $this->words['time_minutes_ago'], intval($diff / 60) );
				}
			}
			else if ( $diff < 7200 )
			{
				return $this->words['time_less_hour'];
			}
			else if ( $diff < 86400 )
			{
				return sprintf( $this->words['time_hours_ago'], intval($diff / 3600) );
			}
			else if ( $diff < 172800 )
			{
				return $this->words['time_less_day'];
			}
			else if ( $diff < 604800 )
			{
				return sprintf( $this->words['time_days_ago'], intval($diff / 86400) );
			}
			else if ( $diff < 1209600 )
			{
				return $this->words['time_less_week'];
			}
			else if ( $diff < 3024000 )
			{
				return sprintf( $this->words['time_weeks_ago'], intval($diff / 604900) );
			}
			else
			{
				return gmstrftime($format, ($date + $this->offset) );
			}
		}
		
		//-----------------------------------------
		// Yesterday / Today
		//-----------------------------------------
		
		else if ( $this->settings['time_use_relative'] and ( $norelative != 1 ) )
		{
			$this_time = gmstrftime('%m,%d,%Y', ($date + $this->offset) );
			
			//-----------------------------------------
			// Use level 2 relative?
			//-----------------------------------------
			
			if ( $this->settings['time_use_relative'] == 2 AND ($date < time()) )
			{
				$diff = time() - $date;

				if ( $diff < 3600 )
				{
					if ( $diff < 120 )
					{
						return $this->words['time_less_minute'];
					}
					else
					{
						return sprintf( $this->words['time_minutes_ago'], intval($diff / 60) );
					}
				}
			}
			
			//-----------------------------------------
			// Still here? 
			//-----------------------------------------
			
			if ( $this_time == $this->today_time )
			{
				return str_replace( '{--}', $this->words['time_today'], gmstrftime($this->settings['time_use_relative_format'], ($date + $this->offset) ) );
			}
			else if  ( $this_time == $this->yesterday_time )
			{
				return str_replace( '{--}', $this->words['time_yesterday'], gmstrftime($this->settings['time_use_relative_format'], ($date + $this->offset) ) );
			}
			else
			{
				return gmstrftime( $format, ($date + $this->offset) );
			}
		}
		
		//-----------------------------------------
		// Normal
		//-----------------------------------------
		
		else
		{
        	return gmstrftime($format, ($date + $this->offset) );
        }
    }

    /**
	 * Return current TIME (not date)
	 *
	 * @access	public
	 * @param	integer		Unix date
	 * @param	string		PHP strftime() formatting options
	 * @return	string
	 * @since	2.0
	 */
    public function getTime($date, $method='%I:%M %p')
    {
        if ($this->offset_set == 0)
        {
        	// Save redoing this code for each call, only do once per page load

			$this->offset = $this->getTimeOffset();

			$this->offset_set = 1;
        }

        return gmstrftime($method, ($date + $this->offset) );
    }

    /**
	 * Returns the member's time zone offset
	 *
	 * @access	public
	 * @return	string
	 * @since	2.0
	 */
    public function getTimeOffset()
    {
    	$r = 0;

    	$r = ( ( ($this->memberData['time_offset'] AND $this->memberData['time_offset'] != "" ) OR $this->memberData['time_offset'] === '0' OR $this->memberData['time_offset'] === 0 ) ? $this->memberData['time_offset'] : $this->settings['time_offset'] ) * 3600;

		if ( $this->settings['time_adjust'] )
		{
			$r += ($this->settings['time_adjust'] * 60);
		}
		
		if ( isset($this->memberData['dst_in_use']) AND $this->memberData['dst_in_use'] )
		{
			$r += 3600;
		}
    	
    	return $r;
	}
	
    /**
	 * Converts user's date to GMT unix date
	 *
	 * @access	public
	 * @param	array		array( 'year', 'month', 'day', 'hour', 'minute' )
	 * @return	integer
	 * @since	2.0
	 */
    public function convertDateToTimestamp( $time=array() )
    {
		//-----------------------------------------
		// Get the local offset
		//-----------------------------------------

		if( $this->offset_set == 0 )
        {
			$this->offset = $this->getTimeOffset();
			$this->offset_set = 1;
		}
		
		$time = gmmktime( intval($time['hour']), intval($time['minute']), 0, intval($time['month']), intval($time['day']), intval($time['year']) );

 		return $time - $this->offset;
	}

	/**
	 * Loads the language file, also loads the global lang file if not loaded
	 * 
	 * @access	public
	 * @param	array 	[$load]		Array of lang files to load
	 * @param	string	[$app]		Specify application to use
	 * @param	string	[$lang]		Language pack to use
	 * @return	void
	 */	 
	public function loadLanguageFile( $load=array(), $app='', $lang='' )
	{
		$_MASTER2	= IPSDebug::getMemoryDebugFlag();
		
		/* App */
		$app     = ( $app )  ? $app : IPS_APP_COMPONENT;
		$load    = ( $load ) ? $load : array();
		$global  = ( IPS_AREA == 'admin' ) ? 'core_admin_global' : 'core_public_global';
		$_global = str_replace( 'core_', '', $global );
		
		if ( $lang AND ! IN_DEV )
		{
			$tempLangId		= $this->lang_id;
			$this->lang_id	= $lang;
		}
		
		/* Some older calls may still think $load is a string... */
		if( is_string( $load ) )
		{
			$load = array( $load );
		}
		
		/* Has the global language file been loaded? */
		if ( ! in_array( $global, $this->loaded_lang_files ) AND ( $app == 'core' AND ! in_array( $_global, $load ) ) )
		{
			$load[] = $global;
		}
		
		/* Load the language file */
		$errors = '';
		
		if( $this->load_from_db OR $this->_forceEnglish )
		{
			if( is_array( $load ) AND count( $load ) )
			{
				/* Reformat for query and make sure we're not loading something twice */
				$_load = array();
				
				foreach( $load as $l )
				{
					/* Already loaded? */
					if ( ! in_array( $app . $l, $this->loaded_lang_files ) )
					{	
						/* Reformat */
						$_load[] = "'{$l}'";					
					}	
					
					/* Add to the loaded array */
					$this->loaded_lang_files[] = $app . '_' . $l;
				}
				
				/* Query the lang entries */
				$this->DB->build( array( 
										'select' => 'word_key, word_default, word_custom',
										'from'   => 'core_sys_lang_words',
										'where'  => "lang_id={$this->lang_id} AND word_app='{$app}' AND word_pack IN ( " . implode( ',', $_load ) . " )",
								)	);
				$this->DB->execute();
				
				/* Add to the language array */
				while( $r = $this->DB->fetch() )
				{
					$this->words[$r['word_key']] = ( $this->_forceEnglish ) ? $r['word_default'] : ( $r['word_custom'] ? $r['word_custom'] : $r['word_default'] );
				}				
			}
		}
		else
		{
			if( is_array( $load ) AND count( $load ) )
			{
				foreach( $load as $l )
				{
					/* Load global from the core app */
					if ( $l == $global )
					{
						$_file = IPS_CACHE_PATH . 'cache/lang_cache/' . $this->lang_id . '/' . $l . '.php';
						$_test = $l;
					}
					else 			
					{
						$_file = IPS_CACHE_PATH . 'cache/lang_cache/' . $this->lang_id . '/' . $app . '_' . $l . '.php';
						$_test = $app . '_' . $l;
					}
					
					if ( ! in_array( $_test, $this->loaded_lang_files ) )
					{
						if ( file_exists( $_file ) )
						{
							require( $_file );
				
							foreach( $lang as $k => $v )
							{
								$this->words[$k] = $v;
							}
				
							$this->loaded_lang_files[] = $_test;
							
							IPSDebug::setMemoryDebugFlag( "Loaded Language File: " . str_replace( IPS_CACHE_PATH, '', $_file ), $_MASTER2 );
						}
						else 
						{
							$errors .= "<li>Missing Language File: " . $_file;
							
							IPSDebug::setMemoryDebugFlag( "NO SUCH Language File: " . str_replace( IPS_CACHE_PATH, '', $_file ), $_MASTER2 );
						}
					}
					else
					{
						IPSDebug::setMemoryDebugFlag( "ALREADY LOADED Language File: " . str_replace( IPS_CACHE_PATH, '', $_file ), $_MASTER2 );
					}
				}
			}
		}
		
		if( isset( $tempLangId ) AND $tempLangId )
		{
			$this->lang_id	= $tempLangId;
		}
		
		if( $errors && IN_ACP )
		{
			return "<ul>{$errors}</ul>";
		}
	}	
	
}