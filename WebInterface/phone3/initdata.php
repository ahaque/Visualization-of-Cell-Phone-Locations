<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * INIT File - Sets up globals
 * Last Updated: $Date: 2009-09-02 09:39:50 -0400 (Wed, 02 Sep 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2008 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 5078 $
 *
 */

if ( @function_exists( 'memory_get_usage' ) )
{
	define( 'IPS_MEMORY_START', memory_get_usage() );
}

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: FOLDER AND FILE NAMES
//--------------------------------------------------------------------------

/**
* CP_DIRECTORY
*
* The name of the CP directory
* @since 2.0.0.2005-01-01
*/
define( 'CP_DIRECTORY', 'admin' );

/**
 * PUBLIC_DIRECTORY
 *
 * The name of the public directory
 */
define( 'PUBLIC_DIRECTORY', 'public' );

/**
 * Default app name
 * You can set this in your own scripts before 'initdata.php' is required.
 */
if ( ! defined( 'IPS_DEFAULT_PUBLIC_APP' ) )
{
	define( 'IPS_DEFAULT_PUBLIC_APP', 'ccs' );
}

/**
* PUBLIC SCRIPT
*/
if ( ! defined( 'IPS_PUBLIC_SCRIPT' ) )
{
	define( 'IPS_PUBLIC_SCRIPT', basename( $_SERVER['SCRIPT_NAME'] ) );
}

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: MAIN PATHS
//--------------------------------------------------------------------------

/**
* "PUBLIC" ROOT PATH
*/
define( 'DOC_IPS_ROOT_PATH', str_replace( "\\", "/", dirname( __FILE__ ) ) . '/' );

/**
* "ADMIN" ROOT PATH
*/
define( 'IPS_ROOT_PATH', DOC_IPS_ROOT_PATH . CP_DIRECTORY . "/" );

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: OTHER PATHS
//--------------------------------------------------------------------------

/**
 * PUBLIC PATH
 */
define( 'IPS_PUBLIC_PATH', DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/' );

/**
* IPS KERNEL PATH
*/
define( 'IPS_KERNEL_PATH', DOC_IPS_ROOT_PATH . 'ips_kernel/' );

/**
* Custom log in path
*/
define( 'IPS_PATH_CUSTOM_LOGIN' , IPS_ROOT_PATH.'sources/loginauth' );

/**
* HOOKS PATH
*/
define( 'IPS_HOOKS_PATH'       , DOC_IPS_ROOT_PATH . 'hooks/' );

@set_include_path( @get_include_path() . PATH_SEPARATOR . IPS_KERNEL_PATH );

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: USER LOCATION
//--------------------------------------------------------------------------

define( 'IPS_AREA', strstr( $_SERVER['PHP_SELF'], '/' . CP_DIRECTORY ) ? 'admin' : 'public' );
define( 'IN_ACP', IPS_AREA == 'public' ? 0 : 1 );

/**
 * Default Application if one is not specified in the URL / POST, etc
 *
 */
if ( ! defined( 'IPS_DEFAULT_APP' ) )
{
	define( 'IPS_DEFAULT_APP', ( IPS_AREA == 'public' ) ? IPS_DEFAULT_PUBLIC_APP : 'core' );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: DEBUG
//--------------------------------------------------------------------------

/**
* TOPIC MARKING DEBUG MODE
*
* Turns on topic marking debugging mode. This is NOT recommended
* in a production environment due to additional writes
* @since 2.2.0.2006-11-06
*/
define( 'IPS_TOPICMARKERS_DEBUG', FALSE );

/* Enter any groups or member IDs you want to trace like:
 * groups=4,1&ids=345,32,65
 */
define( 'IPS_TOPICMARKERS_TRACE', 'groups=&ids=1,36' );

/**
 * E_NOTICE / E_ALL Debug mode
 * Can capture and / or log php errors to a file (cache/phpNotices.cgi)
 * use 'TRUE' to capture all or enter comma sep. list of classes to capture, like
 * define( 'IPS_ERROR_CAPTURE', 'classItemMarking,publicSessions' );
 * Set to 'FALSE' for off
 */
define( 'IPS_ERROR_CAPTURE', FALSE );//'classItemMarking,publicSessions' );

/**
* SQL DEBUG MODE
*
* Turns on SQL debugging mode. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
define( 'IPS_SQL_DEBUG_MODE', 0 );

/**
* MEMORY DEBUG MODE
*
* Turns on MEMORY debugging mode. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
define( 'IPS_MEMORY_DEBUG_MODE', 0 );

if ( ! defined( 'IPS_LOG_ALL' ) )
{
	/*
	* Write to a general debug file?
	* IP.Board has debug messages that are sent to the log.
	* The log file will fill VERY quickly, so leave this off unless you
	* are debugging, etc
	*/
	define( 'IPS_LOG_ALL', FALSE );
}

if ( ! defined( 'IPS_XML_RPC_DEBUG_ON' ) )
{
	/**
	* Write to debug file?
	* Enter relative / full path into the constant below
	* Remove contents to turn off debugging.
	* WARNING: If you are passing passwords and such via XML_RPC
	* AND wish to debug, ensure that the debug file ends with .php
	* to prevent it loading as plain text via HTTP which would show
	* the entire contents of the file.
	* @since 2.2.0.2006-11-06
	*/
	define( 'IPS_XML_RPC_DEBUG_ON'  , 0 );
	define( 'IPS_XML_RPC_DEBUG_FILE', str_replace( "\\", "/", dirname( __FILE__ ) ) ."/" . 'cache/xmlrpc_debug_ipboard.cgi' );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: ACP
//--------------------------------------------------------------------------

/**
* Allow IP address matching when dealing with ACP sessions
* @since 2.2.0.2006-06-30
*/
define( 'IPB_ACP_IP_MATCH', 1 );

/**
* Number of minutes of inactivity in ACP before you are timed out
* @since 3.0.0
*/
define( 'IPB_ACP_SESSION_TIME_OUT', 60 );

/**
* Use GZIP page compression in the ACP
* @since 2.2.0.2006-06-30
*/
if( !@ini_get('zlib.output_compression') )
{
	define( 'IPB_ACP_USE_GZIP', 1 );
}
else
{
	define( 'IPB_ACP_USE_GZIP', 0 );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: MISC
//--------------------------------------------------------------------------

/**
* USE SHUT DOWN
*
* Enable shut down features?
* Uses PHPs register_shutdown_function to save
* low priority tasks until end of exec
* @since 2.0.0.2005-01-01
*/
define( 'IPS_USE_SHUTDOWN', IPS_AREA == 'public' ? 1 : 0 );

/**
* Allow UNICODE
*/
define( 'IPS_ALLOW_UNICODE', 1 );

/**
* Time now stamp
*/
define( 'IPS_UNIX_TIME_NOW', time() );

/* Min PHP version number */
define( 'MIN_PHP_VERS', '5.1.0' );

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: MAGIC QUOTES
//--------------------------------------------------------------------------

@set_magic_quotes_runtime(0);

define( 'IPS_MAGIC_QUOTES', @get_magic_quotes_gpc() );

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: ERROR REPORTING
//--------------------------------------------------------------------------

if( version_compare( PHP_VERSION, '5.2.0', '>=' ) )
{
	error_reporting( E_STRICT | E_ERROR | E_WARNING | E_PARSE | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING );
}
else
{
	error_reporting( E_STRICT | E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING );
}

//--------------------------------------------------------------------------
// XX NOTHING USER CONFIGURABLE XX NOTHING USER CONFIGURABLE XX
//--------------------------------------------------------------------------

/**
* IN IPB
*/
define( 'IN_IPB', 1 );

/**
* SAFE MODE
*/
if ( IPS_AREA != 'public' )
{
	if ( function_exists('ini_get') )
	{
		$test = @ini_get("safe_mode");

		define( 'SAFE_MODE_ON', ( $test === TRUE OR $test == 1 OR $test == 'on' ) ? 1 : 0 );
	}
	else
	{
		define( 'SAFE_MODE_ON', 1 );
	}
}
else
{
	define( 'SAFE_MODE_ON', 0 );
}

//--------------------------------------------------------------------------
// NON-CONFIGURABLE: Attempt to sort out some defaults
//--------------------------------------------------------------------------

if ( @function_exists("set_time_limit") == 1 and SAFE_MODE_ON == 0 )
{
	if ( defined('IPS_IS_SHELL') AND IPS_IS_SHELL )
	{
		@set_time_limit(0);
	}
	else
	{
		@set_time_limit( IPS_AREA == 'public' ? 30 : 0 );
	}
}

/**
* Fix for PHP 5.1.x warning
*
* Sets default time zone to server time zone
* @since 2.2.0.2006-05-19
*/
if ( function_exists( 'date_default_timezone_set' ) )
{
	date_default_timezone_set( 'UTC' );
}

//--------------------------------------------------------------------------
// NON-CONFIGURABLE: Global Functions
//--------------------------------------------------------------------------

/**
* Get an environment variable value
*
* Abstract layer allows us to user $_SERVER or getenv()
*
* @param	string	Env. Variable key
* @return	string
* @since	2.2
*/
function my_getenv($key)
{
    $return = array();

    if ( is_array( $_SERVER ) AND count( $_SERVER ) )
    {
	    if( isset( $_SERVER[$key] ) )
	    {
		    $return = $_SERVER[$key];
	    }
    }

    if ( ! $return )
    {
	    $return = getenv($key);
    }

    return $return;
}

/**
* json_encode function if not available in PHP
*
* @param	mixed 		Anything, really
* @return	string
* @since	3.0
*/
if (!function_exists('json_encode'))
{
	function json_encode( $a )
	{
		require_once( IPS_KERNEL_PATH . 'PEAR/JSON/JSON.php' );

		$json = new Services_JSON();

		return $json->encode( $a );
	}
}

/**
* json_encode function if not available in PHP
*
* @param	mixed 		Anything, really
* @return	string
* @since	3.0
*/
if (!function_exists('json_decode'))
{
	function json_decode( $a, $assoc=false )
	{
		require_once( IPS_KERNEL_PATH . 'PEAR/JSON/JSON.php' );

		if ( $assoc === TRUE )
		{
			$json = new Services_JSON( SERVICES_JSON_LOOSE_TYPE );
		}
		else
		{
			$json = new Services_JSON();
		}

		return $json->decode( $a );
	}
}

/**
* Exception error handler
*/
function IPS_exception_error( $error )
{
	@header( "Content-type: text/plain" );
	print $error;
	exit();
}

?>