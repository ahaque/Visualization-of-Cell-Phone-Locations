<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Core variables for calendar
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @version		$Rev: 4948 $ 
 **/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_LOAD = array();


$_LOAD['calendars']  = 1;

$CACHE['calendars'] = array( 
								'array'            => 1,
								'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'calendar' ) . '/modules_admin/calendar/calendars.php',
								'recache_class'    => 'admin_calendar_calendar_calendars',
							    'recache_function' => 'calendarsRebuildCache' 
							);

$CACHE['birthdays'] = array( 
								'array'            => 1,
								'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'calendar' ) . '/modules_admin/calendar/calendars.php',
								'recache_class'    => 'admin_calendar_calendar_calendars',
							    'recache_function' => 'calendarRebuildCache' 
							);

$CACHE['calendar'] = array( 
								'array'            => 1,
								'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'calendar' ) . '/modules_admin/calendar/calendars.php',
								'recache_class'    => 'admin_calendar_calendar_calendars',
							    'recache_function' => 'calendarRebuildCache' 
							);