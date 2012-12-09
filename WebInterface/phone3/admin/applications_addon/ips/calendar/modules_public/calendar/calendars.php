<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Calendar View
 * Last Updated: $Date: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @version		$Rev: 5045 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_calendar_calendar_calendars extends ipsCommand
{
	/**
	 * Temporary HTML output
	 *
	 * @access	private
	 * @var		string
	 */
	private $output				= "";

	/**
	 * Page title
	 *
	 * @access	private
	 * @var		string
	 */
	private $page_title			= "";

	/**
	 * Chosen month
	 *
	 * @access	private
	 * @var		int
	 */
	private $chosen_month		= 0;

	/**
	 * Chosen year
	 *
	 * @access	private
	 * @var		int
	 */
	private $chosen_year		= 0;

	/**
	 * Current date
	 *
	 * @access	private
	 * @var		array
	 */
	private $now_date			= array();

	/**
	 * Now date parts
	 *
	 * @access	private
	 * @var		array
	 */
	private $now				= array( 'mday' => '', 'mon' => '', 'year' => '' );

	/**
	 * Timestamp for our date
	 *
	 * @access	private
	 * @var		int
	 */
	private $our_datestamp		= 0;

	/**
	 * Array of date parts for first date
	 *
	 * @access	private
	 * @var		array
	 */
	private $first_day_array	= array();

	/**
	 * Words for the month (for translation)
	 *
	 * @access	private
	 * @var		array
	 * @todo 	[Future] Deprecate this, and use the locale date functionality instead
	 */
	public $month_words			= array();

	/**
	 * Words for the days.  Gets setup special for calendars starting on Monday
	 *
	 * @access	private
	 * @var		array
	 */
	public $day_words			= array();

	/**
	 * Cache of results from a monthly query
	 *
	 * @access	private
	 * @var		array
	 */
	private $query_month_cache	= array();

	/**
	 * Cache of results from a birthday query
	 *
	 * @access	private
	 * @var		array
	 */
	private $query_bday_cache	= array();

	/**
	 * Cache of event results
	 *
	 * @access	private
	 * @var		array
	 */
	private $event_cache		= array();

	/**
	 * Cache of events we've already displayed, to prevent duplication for recurring/ranged events.
	 *
	 * @access	private
	 * @var		array
	 */
	private $shown_events		= array();

	/**
	 * Calendar ID
	 *
	 * @access	private
	 * @var		int
	 */
	public $calendar_id			= 1;

	/**
	 * Calendar data
	 *
	 * @access	private
	 * @var		array
	 */
	public $calendar			= array();

	/**
	 * Calendar jump select list
	 *
	 * @access	private
	 * @var		string
	 */
	private $calendar_jump		= "";

	/**
	 * Parsed member data
	 *
	 * @access	private
	 * @var		array
	 */
	private $parsed_members		= array();

	/**
	 * Can we read the events
	 *
	 * @access	private
	 * @var		bool
	 */
	public $can_read			= false;

	/**
	 * Can we post the events
	 *
	 * @access	private
	 * @var		bool
	 */
	private $can_post			= false;

	/**
	 * Can we avoid a mod queue
	 *
	 * @access	private
	 * @var		bool
	 */
	private $can_avoid_queue	= false;

	/**
	 * Can we moderate events
	 *
	 * @access	private
	 * @var		bool
	 */
	private $can_moderate		= false;

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Make sure the calednar is installed and enabled */
		if( ! IPSLib::appIsInstalled( 'calendar' ) )
		{
			$this->registry->output->showError( 'no_permission', 1076 );
		}
		
		/* Load language  */
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ) );

		/* Max Birthdays */
		$this->settings['bday_show_cal_max'] = 5;

		/* Get calendar details */
		if( $this->request['cal_id'] OR $this->request['calendar_id'] )
		{
			$this->calendar_id = intval( $this->request['cal_id'] ) ? intval( $this->request['cal_id'] ) : intval( $this->request['calendar_id'] );
		}
		else
		{
			$this->calendar_id = 1;
		}

		/* Sneaky cheaty */
		$this->request['_cal_id'] = $this->calendar_id;

		/* Get all calendar details */
		if( ! count( $this->caches['calendars'] ) )
		{
			$this->DB->build( array(
									'select'   => 'c.*',
									'from'     => array( 'cal_calendars' => 'c' ),
									'add_join' => array(
														array(
																'select' => 'p.*',
																'from'   => array( 'permission_index' => 'p' ),
																'where'  => "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																'type'   => 'left',
															)
														)
						) 	);
			$this->DB->execute();

			$_cal_cache = array();
			while( $cal = $this->DB->fetch() )
			{
				$_cal_cache[ $cal['cal_id'] ] = $cal;
			}

			$this->cache->setCache( 'calendars', $_cal_cache, array( 'array' => 1, 'deletefirst' => 1 ) );
		}

		/* Loop through the cache and build calendar jump */
		if ( count( $this->caches['calendars'] ) AND is_array( $this->caches['calendars'] ) )
		{
			foreach( $this->caches['calendars'] as $cal_id => $cal )
			{
				$selected = "";

				/* Got a perm */
				if( ! $this->registry->permissions->check( 'view', $cal ) )
				{
					continue;
				}

				if ( $cal['cal_id'] == $this->calendar_id )
				{
					$this->calendar = $cal;
					//$selected       = " selected='selected'";
				}

				$this->calendar_cache[ $cal['cal_id'] ] = $cal;

				$this->calendar_jump .= "<option value='{$cal['cal_id']}'{$selected}>{$cal['cal_title']}</option>\n";
			}
		}

		if( ! $this->calendar )
		{
			if( count( $this->calendar_cache ) )
			{
				$tmp_resort = $this->calendar_cache;
				ksort($tmp_resort);
				reset($tmp_resort);
				$default_calid = key( $tmp_resort );
				$this->calendar_id = $default_calid;
				$this->calendar = $tmp_resort[ $default_calid ];
				unset( $tmp_resort );
			}
		}

		/* Setup calendar permissions */
		$this->calendarBuildPermissions();

		if ( ! $this->can_read )
		{
			$this->registry->output->showError( 'cal_no_perm', 1040 );
		}

		/* There is something whacky with getdate and GMT
		   This handrolled method seems to take into account
		   DST where getdate refuses. */
		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->class_localization->getTimeOffset() ) );

		$this->now_date = array(
								 'year'    => $a[0],
								 'mon'     => $a[1],
								 'mday'    => $a[2],
								 'hours'   => $a[3],
								 'minutes' => $a[4],
								 'seconds' => $a[5]
							   );

		if( $this->request['year'] )
		{
			$this->request['y'] = $this->request['year'];
		}

		/* Set the chosen month and year */
		$this->chosen_month = ( ! $this->request['m'] OR ! intval( $this->request['m'] ) ) ? $this->now_date['mon']  : intval( $this->request['m'] );
		$this->chosen_year  = ( ! $this->request['y'] OR ! intval( $this->request['y'] ) ) ? $this->now_date['year'] : intval( $this->request['y'] );

		/* Make sure the date is in range. */
		if( ! checkdate( $this->chosen_month, 1 , $this->chosen_year ) )
		{
			$this->chosen_month = $this->now_date['mon'];
			$this->chosen_year  = $this->now_date['year'];
		}

		/* Get the timestamp for our chosen date */
		$this->our_datestamp   = mktime( 0,0,1, $this->chosen_month, 1, $this->chosen_year);
		$this->first_day_array = IPSTime::date_getgmdate($this->our_datestamp);

		/* Finally, build up the lang arrays */
		$this->month_words = array( $this->lang->words['M_1'] , $this->lang->words['M_2'] , $this->lang->words['M_3'] ,
									$this->lang->words['M_4'] , $this->lang->words['M_5'] , $this->lang->words['M_6'] ,
									$this->lang->words['M_7'] , $this->lang->words['M_8'] , $this->lang->words['M_9'] ,
									$this->lang->words['M_10'], $this->lang->words['M_11'], $this->lang->words['M_12'] );

		if( ! $this->settings['ipb_calendar_mon'] )
		{
			$this->day_words   = array( $this->lang->words['D_0'], $this->lang->words['D_1'], $this->lang->words['D_2'],
										$this->lang->words['D_3'], $this->lang->words['D_4'], $this->lang->words['D_5'],
										$this->lang->words['D_6'] );
		}
		else
		{
			$this->day_words   = array( $this->lang->words['D_1'], $this->lang->words['D_2'], $this->lang->words['D_3'],
										$this->lang->words['D_4'], $this->lang->words['D_5'], $this->lang->words['D_6'],
										$this->lang->words['D_0'] );
		}

		switch( $this->request['do'] )
		{
			case 'newevent':
				$this->calendarEventForm( 'add' );
			break;

			case 'addnewevent':
				$this->calendarEventSave( 'add' );
			break;

			case 'edit':
				$this->calendarEventForm( 'edit' );
			break;

			case 'doedit':
				$this->calendarEventSave( 'edit' );
			break;

			case 'calendarEventApprove':
				$this->calendarEventApprove();
			break;

			case 'showday':
				$this->calendarShowDay();
			break;

			case 'showevent':
				$this->calendarShowEvent();
			break;

			case 'birthdays':
				$this->calendarShowBirthdays();
			break;

			case 'showweek':
				$this->calendarShowWeek();
			break;

			case 'delete':
				$this->calendarEventDelete();
			break;

			case 'find':
				$this->calendarFindDate();
			break;

			default:
				$this->calendarShowMonth();
			break;
		}

		/* Page Title */
		if( $this->page_title == "" )
		{
			$this->page_title = $this->settings['board_name'] . " " . $this->lang->words['page_title'];
		}

		$this->registry->output->setTitle( $this->page_title );

		/* Navigation */
		if( ! is_array( $this->registry->output->_navigation ) )
		{
			$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
			$this->registry->output->addNavigation( $this->calendar['cal_title']    , "app=calendar&amp;module=calendar&amp;cal_id={$this->calendar_id}" );
		}

		/* Output */
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}

	/**
	 * Deletes an event from the calendar
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarEventDelete()
	{
		/* INIT */
		$cal_id    = intval( $this->request['cal_id'] );
		$event_id  = intval( $this->request['event_id'] );
		$md5check  = trim( $this->request['md5check'] );

		/* Get Permissions */
		$this->calendarBuildPermissions( $cal_id );

		/* Check */
		$this->DB->build( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=$event_id AND event_calendar_id=$cal_id" ) );
		$this->DB->execute();
		$memcheck = $this->DB->fetch();

		if ( ! $cal_id OR ! $event_id )
		{
			$this->registry->output->showError( 'calendar_bad_delete', 1041 );
		}

		if ( ! $this->can_moderate && ( $this->memberData['member_id'] > 0 && $this->memberData['member_id'] <> $memcheck['event_member_id'] ) )
		{
			$this->registry->output->showError( 'calendar_delete_no_perm', 1042 );
		}

		/* Check MD5 */
		if ( $md5check != $this->member->form_hash )
		{
			$this->registry->output->showError( 'calendar_bad_key', 2040 );
		}

		/* Delete */
		$this->DB->delete( 'cal_events', "event_id=$event_id AND event_calendar_id=$cal_id" );

		/* Recache */
		$this->calendarCallRecache();

		/* Boing... */
		$this->registry->output->redirectScreen( $this->lang->words['cal_event_delete'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;cal_id={$cal_id}" );
	}

	/**
	 * Approves an event
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarEventApprove()
	{
		/* INIT */
		$cal_id    = intval( $this->request['cal_id'] );
		$event_id  = intval( $this->request['event_id'] );
		$approve   = intval( $this->request['approve'] );
		$modfilter = trim( $this->request['modfilter'] );
		$quicktime = trim( $this->request['qt'] );
		$md5check  = trim( $this->request['md5check'] );

		list( $month, $day, $year ) = explode( "-", $quicktime );

		/* Get Permissions */
		$this->calendarBuildPermissions( $cal_id );

		/* Check */
		if ( ! $this->can_moderate )
		{
			$this->registry->output->showError( 'calendar_bad_approve', 1043 );
		}

		/* Check MD5 */
		if ( $md5check != $this->member->form_hash )
		{
			$this->registry->output->showError( 'calendar_bad_key', 2041 );
		}

		/* Check Dates */
		if ( ! $day OR ! $month OR ! $year )
		{
			$this->registry->output->showError( 'calendar_dmy_missing', 1044 );
		}

		/* Get Event */
		$event = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_calendar_id={$cal_id} and event_id={$event_id}" ) );

		if( ! $event['event_id'] )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 1045 );
		}

		/* Update Event... */
		$this->DB->update( 'cal_events', array( 'event_approved' => $event['event_approved'] ? 0 : 1 ), 'event_id='.$event_id );

		/* Recache... */
		$this->calendarCallRecache();

		/* Boink */
		$this->registry->output->silentRedirect( $this->settings['base_url']."app=calendar&module=calendar&cal_id={$cal_id}&modfilter={$modfilter}&do=showday&y={$year}&m={$month}&d={$day}");
	}

	/**
	 * Build calendar permissions
	 *
	 * @access	public
	 * @param	int		Calendar ID
	 * @return	void
	 */
	public function calendarBuildPermissions( $cal_id=0 )
	{
		$this->can_read        = 0;
		$this->can_post        = 0;
		$this->can_avoid_queue = 0;
		$this->can_moderate    = 0;

		//-----------------------------------------
		// Got an idea?
		//-----------------------------------------

		if ( ! $cal_id )
		{
			$cal_id = $this->calendar_id;
		}

		$calendar = $this->calendar_cache[ $cal_id ];

		//-----------------------------------------
		// Read
		//-----------------------------------------

		if( $this->registry->permissions->check( 'view', $calendar ) )
		{
			$this->can_read = 1;
		}

		//-----------------------------------------
		// Post
		//-----------------------------------------

		if( $this->registry->permissions->check( 'start', $calendar ) )
		{
			$this->can_post = 1;
		}

		//-----------------------------------------
		// Mod Queue
		//-----------------------------------------

		if( $this->registry->permissions->check( 'nomod', $calendar ) )
		{
			$this->can_avoid_queue = 1;
		}

		//-----------------------------------------
		// Moderate
		//-----------------------------------------

		if ( $this->memberData['g_is_supmod'] )
		{
			$this->can_moderate = 1;
		}

	}

	/**
	 * Find a date
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarFindDate()
	{
		if( $this->request['what'] )
		{
			if( $this->request['what'] == 'thismonth' )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url']."app=calendar&amp;module=calendar&amp;cal_id={$this->calendar_id}&amp;m={$this->now_date['mon']}&amp;y={$this->now_date['year']}" );
			}
			else
			{
				$time = time() + $this->registry->class_localization->getTimeOffset();

				$this->registry->output->silentRedirect( $this->settings['base_url']."app=calendar&amp;module=calendar&amp;cal_id={$this->calendar_id}&amp;do=showweek&amp;week={$time}" );
			}
		}
		else
		{
			$this->calendarShowMonth();
		}
	}

	/**
	 * Shows the events for a week
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarShowWeek()
	{
		/* INIt */
		$in_week = intval( $this->request['week'] );

		/* Get start of the week */
		$startweek = IPSTime::date_getgmdate( $in_week );

		if( ! $this->settings['ipb_calendar_mon'] )
		{
			/* Not Sunday? Go back */
			$startweek['wday'] = intval( $startweek['wday'] );

			if( $startweek['wday'] > 0 )
			{
				while( $startweek['wday'] != 0 AND $startweek['wday'] != 7 )
				{
					$startweek['wday']--;
					$in_week -= 86400;
				}

				$startweek = IPSTime::date_getgmdate( $in_week );
			}
		}
		else
		{
			//-----------------------------------------
			// Not Monday, rewind...
			// date_getgmdate will set weekday start to 1
			// PHP 5.1 allows for 'N' which will fix
			// this, but we support earlier versions..
			//-----------------------------------------
			if( $startweek['wday'] != 1 )
			{
				$startweek['wday'] = $startweek['wday'] == 0 ? 7 : $startweek['wday'];

				while( $startweek['wday'] != 1 )
				{
					$startweek['wday']--;
					$in_week -= 86400;
				}

				$startweek = IPSTime::date_getgmdate( $in_week );
			}
		}

		/* Get end of week */
		$endweek       = IPSTime::date_getgmdate( $in_week + 604800 );
		$our_datestamp = gmmktime( 0,0,0, $startweek['mon'], $startweek['mday'], $startweek['year']);
		$our_timestamp = $in_week;
		$seen_days     = array(); // Holds yday
		$seen_ids      = array();

		/* Figure out the next/previous links */
		$prev_month = $this->calendarGetPreviousMonth( $this->chosen_month, $this->chosen_year );
		$next_month = $this->calendarGetNextMonth( $this->chosen_month, $this->chosen_year );

		$prev_week = IPSTime::date_getgmdate( $in_week - 604800 );
		$next_week = IPSTime::date_getgmdate( $in_week + 604800 );

		$this->output .= $this->registry->output->getTemplate('calendar')->cal_week_content( $this->calendar_cache, $startweek['mday'], $this->month_words[$startweek['mon'] - 1], $startweek['year'], $prev_week, $next_week, $this->calendar_id );

		$last_month_id = -1;

		/* Get the events */
		$this->calendarGetEventsSQL( $startweek['mon'], $startweek['year'] );

		/* Print each effing day :D */
		$cal_output = "";

		for( $i = 0 ; $i <= 6 ; $i++ )
		{
			$year   = gmdate( 'Y', $our_datestamp );
			$month  = gmdate( 'n', $our_datestamp );
			$day    = gmdate( 'j', $our_datestamp );
			$today  = IPSTime::date_getgmdate( $our_datestamp );
		
			$this_day_events = "";

			if ( $last_month_id != $today['mon'] )
			{
				$last_month_id = $today['mon'];

				$cal_output .= $this->registry->output->getTemplate('calendar')->cal_week_monthbar( $this->month_words[$today['mon'] - 1], $today['year'] );

				/* Get the birthdays from the database */
				if ( $this->settings['show_bday_calendar'] )
				{
					$birthdays = array();

					$this->calendarGetBirthdaySQL( $today['mon'] );

					$birthdays = $this->query_bday_cache[ intval( $today['mon'] ) ];
				}

				/* Get the events */
				$this->calendarGetEventsSQL( $month, $year );
			}

			$events       = $this->calendarGetDayEvents( $month, $day, $year );
			$queued_event = 0;

			if ( is_array( $events ) AND count( $events ) )
			{
				foreach( $events as $event )
				{
					if ( !isset($this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ]) OR !$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] )
					{
						/* Recurring */
						if ( $event['recurring'] )
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_recurring( $event );
						}
						else if ( $event['single'] )
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap( $event );
						}
						else
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_range( $event );
						}

						$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] = 1;
					}

					/* Queued Events */
					if ( ! $event['event_approved'] AND $this->can_moderate )
					{
						$queued_event = 1;
					}
				}
			}

			/* Birthdays */
			if( $this->calendar['cal_bday_limit'] )
			{
				if( isset( $birthdays[ $today['mday'] ] ) and count( $birthdays[ $today['mday'] ] ) > 0 )
				{
					$no_bdays = count( $birthdays[ $today['mday'] ] );

					if ( $this->calendar['cal_bday_limit'] and $no_bdays <= $this->calendar['cal_bday_limit'] )
					{
						foreach( $birthdays[ $today['mday'] ] as $user )
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_week_events_wrap(
																															"code=birthdays&amp;y=".$today['year']."&amp;m=".$today['mon']."&amp;d=".$today['mday'],
																															$user['members_display_name'].$this->lang->words['bd_birthday']
																														  );
						}

					}
					else
					{
						$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_week_events_wrap(
																													   "code=birthdays&amp;y=".$today['year']."&amp;m=".$today['mon']."&amp;d=".$today['mday'],
																													   sprintf( $this->lang->words['entry_birthdays'], count($birthdays[ $today['mday'] ]) )
																													 );
					}
				}
			}

			if( $this_day_events == "" )
			{
				$this_day_events = '&nbsp;';
			}

			if( $this->settings['ipb_calendar_mon'] )
			{
				// Reset if Monday is first day
				$today['wday'] = ( $today['wday'] == 0 ) ? 6 : $today['wday'] - 1;
			}
			else
			{
				$today['wday'] = ( $today['wday'] == 7 ) ? 0 : $today['wday'];
			}

			$cal_output .= $this->registry->output->getTemplate('calendar')->cal_week_dayentry( $this->day_words[ $today['wday'] ], $today['mday'], $this->month_words[$today['mon'] - 1], $today['mon'], $today['year'], $this_day_events, $queued_event );

			$our_datestamp += 86400;

			unset( $this_day_events );
		}

		/* Switch the HTML tags */
		$this->output = str_replace( "<!--IBF.DAYS_CONTENT-->"  , $cal_output, $this->output );
		$this->output = str_replace( "<!--IBF.MONTH_BOX-->"     , $this->calendarGetMonthDropDown(), $this->output );
		$this->output = str_replace( "<!--IBF.YEAR_BOX-->"      , $this->calendarGetYearDropDown() , $this->output );

		/* Get prev / this / next calendars */
		$this->output = str_replace( "<!--PREV.MONTH-->", $this->getMiniCalendar( $prev_month['month_id'], $prev_month['year_id'] ), $this->output );
		$this->output = str_replace( "<!--THIS.MONTH-->", $this->getMiniCalendar( $this->chosen_month    , $this->chosen_year     ), $this->output );
		$this->output = str_replace( "<!--NEXT.MONTH-->", $this->getMiniCalendar( $next_month['month_id'], $next_month['year_id'] ), $this->output );

		/* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title']    , 'app=calendar&amp;module=calendar&amp;cal_id=' . $this->calendar_id );
		$this->registry->output->addNavigation( $this->month_words[$this->chosen_month - 1]." ".$this->chosen_year, '' );

	}

	/**
	 * Displays the specified month
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarShowMonth()
	{
		/* Figure out the next / previous links */
		$prev_month = $this->calendarGetPreviousMonth( $this->chosen_month, $this->chosen_year );
		$next_month = $this->calendarGetNextMonth( $this->chosen_month, $this->chosen_year );

		$this->output .= $this->registry->output->getTemplate('calendar')->calendarMainContent(
																								$this->calendar_cache,
																								$this->month_words[$this->chosen_month - 1],
																								$this->chosen_year,
																								$prev_month,
																								$next_month,
																								$this->calendar_jump,
																								$this->calendar_id,
																								$this->day_words
																								);

		/* Print the days table top row */
		$day_output = "";
		$cal_output = "";

		foreach( $this->day_words as $day )
		{
			$day_output .= $this->registry->output->getTemplate('calendar')->cal_day_bit( $day );
		}

		$cal_output = $this->getMonthEvents($this->chosen_month, $this->chosen_year);

		/* Switch the HTML Tags */
		$this->output = str_replace( "<!--IBF.DAYS_TITLE_ROW-->", $day_output, $this->output );
		$this->output = str_replace( "<!--IBF.DAYS_CONTENT-->"  , $cal_output, $this->output );

		$this->output = str_replace( "<!--IBF.MONTH_BOX-->"     , $this->calendarGetMonthDropDown(), $this->output );
		$this->output = str_replace( "<!--IBF.YEAR_BOX-->"      , $this->calendarGetYearDropDown() , $this->output );

		/* Get prev / this / next calendars */
		$this->output = str_replace( "<!--PREV.MONTH-->", $this->getMiniCalendar( $prev_month['month_id'], $prev_month['year_id'] ), $this->output );
		$this->output = str_replace( "<!--THIS.MONTH-->", $this->getMiniCalendar( $this->chosen_month    , $this->chosen_year     ), $this->output );
		$this->output = str_replace( "<!--NEXT.MONTH-->", $this->getMiniCalendar( $next_month['month_id'], $next_month['year_id'] ), $this->output );

		/* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title']    , 'app=calendar&amp;module=calendar&amp;cal_id=' . $this->calendar_id );
		$this->registry->output->addNavigation( $this->month_words[$this->chosen_month - 1] ." " . $this->chosen_year, '' );
	}

	/**
	 * Build the ID for the current day
	 *
	 * @access	private
	 * @param	int		$year	The year
	 * @param	int		$month	The month
	 * @param	int		$day	The day
	 * @return 	string
	 */
	private function _buildDayID( $year, $month, $day )
	{
		return 'day-' . $year . '_' . $month . '_' . $day;
	}

	/**
	 * Form for creating/modifying calendar events
	 *
	 * @access	public
	 * @param	string  $type  Either add or edit
	 * @return	void
	 */
	public function calendarEventForm( $type='add' )
	{
		/* Extra languages */
		$this->registry->class_localization->loadLanguageFile( array( 'public_usercp' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' )  , 'forums' );

		/* INIT */
		$event_id      = $this->request['event_id'] ? intval( $this->request['event_id'] ) : 0;
		$calendar_id   = $this->request['calendar_id'] ? intval( $this->request['calendar_id'] ) : 0;
		$form_type     = $this->request['formtype'];
		$recur_menu    = "";
		$calendar_jump = "";
		$divhide	   = "none";

		/* Check */
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'calendar_no_guests', 1046 );
		}

		/* Got permission to post to this calendar? */
		$this->calendarBuildPermissions( $calendar_id );

		if ( ! $this->can_post )
		{
			$this->registry->output->showError( 'calendar_no_post_perm', 1047 );
		}

		/* Edit calendar option */
		foreach( $this->calendar_cache as $data )
		{
			if( $this->registry->permissions->check( 'start', $data ) && ( $this->registry->permissions->check( 'view', $data ) ) )
			{
				$selected       = $calendar_id == $data['cal_id'] ? ' selected="selected" ' : '';
				$calendar_jump .= "<option value='{$data['cal_id']}'{$selected}>{$data['cal_title']}</option>\n";
			}
		}

		/* WHICHISIT */
		if ( $type == 'add' )
		{
			$tmp = $this->request['nd'] ? explode( "-", $this->request['nd'] ) : array();

			$nd = isset($tmp[2]) ? intval( $tmp[2] ) : 0;
			$nm = isset($tmp[1]) ? intval( $tmp[1] ) : 0;
			$ny = isset($tmp[0]) ? intval( $tmp[0] ) : 0;

			$public  = "";
			$private = "";
			$event   = array( 'event_smilies' => 1, 'event_content' => '' );

			$fd = $nd = $nd ? $nd : $this->now['mday'];
			$fm = $nm = $nm ? $nm : $this->now['mon'];
			$fy = $ny = $ny ? $ny : $this->now['year'];

			$tz_offset  = ( ($this->memberData['time_offset'] AND $this->memberData['time_offset'] != "" ) OR $this->memberData['time_offset'] === '0' OR $this->memberData['time_offset'] === 0 ) ? $this->memberData['time_offset'] : $this->settings['time_offset'];

			$recur_menu = "<option value='1'>{$this->lang->words['fv_days']}</option><option value='2'>{$this->lang->words['fv_months']}</option><option value='3'>{$this->lang->words['fv_years']}</option>";
			$code       = 'addnewevent';
			$button     = $this->lang->words['calendar_submit'];

			$event['event_timeset'] = "";
		}
		else
		{
			if ( ! $event_id )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 1048 );
			}

			/* Get the evnet */
			$this->DB->build( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id={$event_id}" ) );
			$this->DB->execute();

			if ( ! $event = $this->DB->fetch() )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 1049 );
			}

			//-----------------------------------------
			// Do we have permission to see the event?
			//-----------------------------------------

			if ( $event['event_perms'] != '*' )
			{
				$this_member_mgroups[] = $this->memberData['member_group_id'];

				if( $this->memberData['mgroup_others'] )
				{
					$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
				}

				$check = 0;

				foreach( $this_member_mgroups as $this_member_mgroup )
				{
					if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $event['event_perms'] ) )
					{
						$check = 1;
					}
				}

				if( $check == 0 )
				{
					$this->registry->output->showError( 'calendar_event_not_found', 10411 );
				}
			}

			/* Do we have permission to edit this event? */
			if ( $this->memberData['member_id'] == $event['event_member_id'] )
			{
				$can_edit = 1;
			}
			else if ( $this->memberData['g_is_supmod'] == 1 )
			{
				$can_edit = 1;
			}
			else
			{
				$can_edit = 0;
			}

			if ( $can_edit != 1 )
			{
				$this->registry->output->showError( 'calendar_no_edit_perm', 10410 );
			}

			$tz_offset = $event['event_tz'];

			//-----------------------------------------
			// Date stuff
			//-----------------------------------------

			$convert_hours = 0;

			if( $event['event_timeset'] )
			{
				$divhide       = "";
				$hour_min      = explode( ":", $event['event_timeset'] );
				$convert_hours = ( $hour_min[0] * 3600 ) + ( $hour_min[1] * 60 );

				/* This code adds a 0 to the end if it only has 1 (e.g. changes 13:0 to 13:00) - purely cosmetic */

				if( strlen( substr( $event['event_timeset'], strpos( $event['event_timeset'], ':' ) + 1 ) ) == 1 )
				{
					$event['event_timeset'] = (string) $event['event_timeset'] . '0';
				}
			}

			if( $tz_offset && $event['event_timeset'] AND !$event['event_all_day'] )
			{
				$convert_hours = $tz_offset * 3600 + $convert_hours;
			}

			$_unix_from = explode( '-', gmdate( 'n-j-Y', $event['event_unix_from'] - $convert_hours ) );

			if( $convert_hours && $event['event_unix_to'] )
			{
				$event['event_unix_to'] -= $convert_hours;
			}
			$_unix_to   = explode('-', gmdate('n-j-Y', $event['event_unix_to']  ));

			$nd = $_unix_from[1];
			$nm = $_unix_from[0];
			$ny = $_unix_from[2];

			$fd = $_unix_to[1];
			$fm = $_unix_to[0];
			$fy = $_unix_to[2];

			/* Form Stuff */
			if ( $event['event_recurring'] )
			{
				$form_type = 'recur';
			}
			else if ( ! $event['event_recurring'] AND $event['event_unix_to'] )
			{
				$form_type = 'range';
			}
			else
			{
				$form_type = 'single';
			}

			/* Private */
			if ( $event['event_private'] == 1 )
			{
				$private = ' selected';
			}
			else
			{
				$public = ' selected';
			}

			/* Recur Stuff */
			$recur_menu .= $event['event_recurring'] == '1' ? "<option value='1' selected='selected'>{$this->lang->words['fv_days']}</option>"
															: "<option value='1'>{$this->lang->words['fv_days']}</option>";

			$recur_menu .= $event['event_recurring'] == '2' ? "<option value='2' selected='selected'>{$this->lang->words['fv_months']}</option>"
															: "<option value='2'>{$this->lang->words['fv_months']}</option>";

			$recur_menu .= $event['event_recurring'] == '3' ? "<option value='3' selected='selected'>" . $this->lang->words['fv_years'] . "</option>"
															: "<option value='3'>{$this->lang->words['fv_years']}</option>";
			$code   = 'doedit';
			$button = $this->lang->words['calendar_edit_submit'];
		}

		/* Do TZ form */
 		$time_select = "<select name='event_tz' class='forminput'>";

 		/* Loop through the langauge time offsets and names to build our HTML jump box. */
 		foreach( $this->lang->words as $off => $words )
 		{
 			if ( preg_match("/^time_(-?[\d\.]+)$/", $off, $match))
 			{
				$time_select .= $match[1] == $tz_offset ? "<option value='{$match[1]}' selected='selected'>{$words}</option>\n"
												        : "<option value='{$match[1]}'>{$words}</option>\n";
 			}
 		}

 		$time_select .= "</select>";

		/* Start off nav */
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title']    , 'app=calendar&amp;module=calendar&amp;cal_id=' . $this->calendar_id );
		$this->registry->output->addNavigation( $this->lang->words['post_new_event'], '' );

		/* Setup Form Type */
		$event_dates = array();

		if ( $form_type == 'single' )
		{
			$event_dates = array(
									'nd' => $this->calendarGetDayDropDown( $nd ),
									'nm' => $this->calendarGetMonthDropDown( $nm ),
									'ny' => $this->calendarGetYearDropDown( $ny ),
								);
		}
		else if ( $form_type == 'range' )
		{
			$event_dates = array(
									'nd' => $this->calendarGetDayDropDown( $nd ),
									'nm' => $this->calendarGetMonthDropDown( $nm ),
									'ny' => $this->calendarGetYearDropDown( $ny ),
									'fd' => $this->calendarGetDayDropDown( $fd ),
									'fm' => $this->calendarGetMonthDropDown( $fm ),
									'fy' => $this->calendarGetYearDropDown( $fy ),
								);
		}
		else
		{
			$event_dates = array(
									'nd' => $this->calendarGetDayDropDown( $nd ),
									'nm' => $this->calendarGetMonthDropDown( $nm ),
									'ny' => $this->calendarGetYearDropDown( $ny ),
									'fd' => $this->calendarGetDayDropDown( $fd ),
									'fm' => $this->calendarGetMonthDropDown( $fm ),
									'fy' => $this->calendarGetYearDropDown( $fy ),
									'recur_unit' => $recur_menu,
								);
		}

		/* Event Type */
		$event_type = array(
								'pub_select'  => $public,
								'priv_select' => $private,
								'timezone'    => $time_select,
								'dropdown'    => $this->calendar_jump,
								'timestuff'   => array(
														'formtype'  => $form_type,
														'timestart' => $event['event_timeset'],
														'divhide'   => $divhide,
														'checked'   => $divhide == '' ? "checked='checked'" : '' ),
								'smilies'	  => $event['event_smilies']
							);

		/* Build the admin group box dataq */
		if( $this->memberData['g_access_cp'] )
		{
			/* INI */
			$group_choices = "";

			foreach( $this->caches['group_cache'] as $r )
			{
				$selected = "";

				if ( isset($event['event_perms']) AND preg_match( "/(^|,)".$r['g_id']."(,|$)/", $event['event_perms'] ) )
				{
					$selected = ' selected';
				}

				$group_choices .= "<option value='".$r['g_id']."'".$selected.">".$r['g_title']."</option>\n";
			}
		}

		/* Using RTE? Convert BBCode to HTML */
		if ( $event['event_content'] )
		{
			IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
			IPSText::getTextClass( 'bbcode' )->parse_smilies	= intval( $event['event_smilies'] );
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';

			if( IPSText::getTextClass( 'editor' )->method == 'rte' )
			{
				$event['event_content'] = IPSText::getTextClass( 'bbcode' )->convertForRTE( $event['event_content'] );
			}
			else
			{
				$event['event_content'] = IPSText::getTextClass( 'bbcode' )->preEditParse( $event['event_content'] );
			}
		}

		/* Output */
		$this->output .= $this->registry->output->getTemplate( 'calendar' )->calendarEventForm(
																								$code,
																								$this->calendar_id,
																								$form_type,
																								$event_id,
																								$event['event_title'],
																								$this->lang->words['post_new_event'],
																								$event_dates,
																								$event_type,
																								$group_choices,
																								$button,
																								IPSText::getTextClass( 'editor' )->showEditor( $event['event_content'], 'Post' )
																							);
	}

	/**
	 * Saves the add/edit calendar event form
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 */
	public function calendarEventSave( $type='add' )
	{
		/* INIT */
    	$read_perms   = '*';
		$end_day      = "";
		$end_month    = "";
		$end_year     = "";
		$end_date     = "";
		$event_ranged = 0;
		$event_repeat = 0;
		$can_edit     = 0;

		$form_type         = $this->request['formtype'];
		$event_id          = intval( $this->request['event_id'] );
    	$calendar_id       = intval( $this->request['calendar_id'] );
    	$allow_emoticons   = $this->request['enableemo'] == 'yes'     ? 1 : 0;
		$private_event     = $this->request['e_type']    == 'private' ? 1 : 0;
		$event_title       = trim( $this->request['event_title'] );
		$day               = intval( $this->request['e_day'] );
		$month             = intval( $this->request['e_month'] );
		$year              = intval( $this->request['e_year'] );
		$end_day           = intval( $this->request['end_day'] );
		$end_month         = intval( $this->request['end_month'] );
		$end_year          = intval( $this->request['end_year'] );
		$recur_unit        = intval( $this->request['recur_unit'] );
		$event_tz          = intval( $this->request['event_tz'] );
		$offset            = 0;
		$event_all_day	   = 0;
		$event_calendar_id = intval( $this->request['event_calendar_id'] );
		$set_time		   = intval( $this->request['set_times'] );
		$hour_min		   = array();

		if( $set_time )
		{
			$hour_min	   = strstr( $this->request['event_timestart'], ":" ) ? explode( ":", $this->request['event_timestart'] ) : 0;

			if( intval( $hour_min[0] ) < 0 || intval( $hour_min[0] ) > 23 )
			{
				$hour_min[0] = 0;
			}

			if( intval( $hour_min[1] ) < 0 || intval( $hour_min[1] ) > 59 )
			{
				$hour_min[1] = 0;
			}

			if( $hour_min[0] || $hour_min[1] )
			{
				$offset	= $event_tz * 3600;
			}
			else
			{
				$hour_min 	= array();
				$offset		= 0;
			}
		}
		else
		{
			$event_all_day	= 1;
		}

		$this->settings['max_post_length'] = $this->settings['max_post_length'] ? $this->settings['max_post_length'] : 2140000;

		/* Check Permissions */
		if ( ! $this->memberData['member_id'])
		{
			$this->registry->output->showError( 'calendar_no_guests', 10412 );
		}

		$this->calendarBuildPermissions( $event_calendar_id );

		if ( ! $this->can_post )
		{
			$this->registry->output->showError( 'calendar_no_post_perm', 10413 );
		}

		/* WHATDOWEDO? */
		if ( $type == 'add' )
		{

		}
		else
		{
			/* Check ID */
			if ( ! $event_id )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 10414 );
			}

			/* Get the event */
			$this->DB->build( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id={$event_id}" ) );
			$this->DB->execute();

			if ( ! $event = $this->DB->fetch() )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 10415 );
			}

			/* Do we have permission to edit this event */
			if ( $this->memberData['member_id'] == $event['event_member_id'] )
			{
				$can_edit = 1;
			}
			else if ( $this->memberData['g_is_supmod'] == 1 )
			{
				$can_edit = 1;
			}

			if ( $can_edit != 1 )
			{
				$this->registry->output->showError( 'calendar_no_edit_perm', 10416 );
			}
		}

		/* Do we have a valid post? */
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $_POST['Post'] ) ) ) ) < 1 )
		{
			$this->registry->output->showError( 'calendar_post_too_short', 10417 );
		}

		/* Check the post length */
		if( IPSText::mbstrlen( $_POST['Post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->registry->output->showError( 'calendar_post_too_long', 10418 );
		}

		/* Fix up the event title */
		if( ( IPSText::mbstrlen( $event_title ) < 2 ) or ( ! $event_title ) )
		{
			$this->registry->output->showError( 'calendar_no_title', 10419 );
		}

		if( IPSText::mbstrlen( $event_title ) > 64 )
		{
			$this->registry->output->showError( 'calendar_title_too_long', 10420 );
		}

		/* Are we an admin, and have we set w/groups can see */
		if( $this->memberData['g_access_cp'] )
		{
			if( is_array( $_POST['e_groups'] ) )
			{
				foreach( $this->cache->getCache('group_cache') as $gid => $groupCache )
				{
					if( $groupCache['g_access_cp'] )
					{
						$_POST['e_groups'][] = $gid;
					}
				}

				$read_perms = implode( ",", $_POST['e_groups'] );
			}

			if( $read_perms == "" )
			{
				$read_perms = '*';
			}
		}

		/* Check dates: Range */
		if( $form_type == 'range' )
		{
			if( $end_year < $year )
			{
				$this->registry->output->showError( 'calendar_range_wrong', 10421 );
			}

			if( $end_year == $year )
			{
				if( $end_month < $month )
				{
					$this->registry->output->showError( 'calendar_range_wrong', 10422 );
				}

				if( $end_month == $month AND $end_day <= $day )
				{
					$this->registry->output->showError( 'calendar_range_wrong', 10423 );
				}
			}

			$_final_unix_from = gmmktime( 0 , 0, 0  , $month    , $day    , $year     ) + $offset;// # Midday
			$_final_unix_to   = gmmktime( 23, 59, 59, $end_month, $end_day, $end_year ) + $offset;// # End of the day

			$event_ranged = 1;
			$set_time 	  = 0;
			$hour_min 	  = array();
		}

		/* Check dates: Recu */
		elseif( $form_type == 'recur' )
		{
			if( $this->request['recur_unit'] )
			{
				$event_repeat = 1;
			}

			if( $end_year < $year )
			{
				$this->registry->output->showError( 'calendar_range_wrong', 10424 );
			}

			if( $end_year == $year )
			{
				if( $end_month < $month )
				{
					$this->registry->output->showError( 'calendar_range_wrong', 10425 );
				}

				if( $end_month == $month AND $end_day <= $day )
				{
					$this->registry->output->showError( 'calendar_range_wrong', 10426 );
				}
			}

			$hour = 0;
			$min  = 0;
			if( $set_time )
			{
				if( is_array( $hour_min ) )
				{
					$hour = $hour_min[0];
					$min  = $hour_min[1];
				}
			}

			$_final_unix_from = gmmktime( $hour, $min, 0, $month    , $day    , $year     ) + $offset;
			$_final_unix_to   = gmmktime( $hour, $min, 0, $end_month, $end_day, $end_year ) + $offset;# End of the day
			$event_recur      = 1;
		}

		/* Check dates: Single */
		else
		{
			$hour = 0;
			$min  = 0;
			if( $set_time )
			{
				if( is_array( $hour_min ) )
				{
					$hour = $hour_min[0];
					$min  = $hour_min[1];
				}
			}

			$_final_unix_from = gmmktime( $hour, $min, 0, $month, $day, $year  ) - $offset;
			$_final_unix_to   = 0;
		}

		/* Do we have a sensible date? */
		if( ! checkdate( $month, $day , $year ) )
        {
			$this->registry->output->showError( 'calendar_invalid_date', 10427 );
		}

		/* Post process the editor, now we have safe HTML and bbcode */
		IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= intval($allow_emoticons);
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';

 		$this->request['Post'] = IPSText::getTextClass( 'editor' )->processRawPost( 'Post' );
 		$this->request['Post'] = IPSText::getTextClass( 'bbcode' )->preDbParse( $this->request['Post'] );

 		/* Event approved? */
 		$event_approved = $this->can_avoid_queue ? 1 : ( $this->calendar_cache[ $event_calendar_id ]['cal_moderate'] ? 0 : 1 );

 		if( $private_event == 1 )
 		{
	 		$event_approved = 1;
 		}

 		/* Create new event */
 		if ( $type == 'add' )
 		{
			/* Add it to the DB */
			$this->DB->insert( 'cal_events', array(
														'event_calendar_id' => $event_calendar_id,
														'event_member_id'   => $this->memberData['member_id'],
														'event_content'     => $this->request['Post'],
														'event_title'       => $event_title,
														'event_smilies'     => $allow_emoticons,
														'event_perms'       => $read_perms,
														'event_private'     => $private_event,
														'event_approved'    => $event_approved,
														'event_unixstamp'   => time(),
														'event_recurring'   => $recur_unit,
														'event_tz'          => $event_tz,
														'event_timeset'	 => count( $hour_min ) > 0 ? intval( $hour_min[0] ).":".intval( $hour_min[1] ) : 0,
														'event_unix_from'   => $_final_unix_from,
														'event_unix_to'     => $_final_unix_to,
														'event_all_day'	 => $event_all_day ) );

			/* Recache */
			$this->calendarCallRecache();

			/* Bounce */
			if ( $event_approved )
			{
				$this->registry->output->redirectScreen( $this->lang->words['new_event_redirect'], $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;cal_id={$event_calendar_id}" );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['new_event_mod'], $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;cal_id{$event_calendar_id}" );
			}
		}
		/* Edit Event */
		else
		{
			/* Update the database recored */
			$this->DB->update( 'cal_events', array(
														'event_calendar_id' => $event_calendar_id,
														'event_content'     => $this->request['Post'],
														'event_title'       => $event_title,
														'event_smilies'     => $allow_emoticons,
														'event_perms'       => $read_perms,
														'event_private'     => $private_event,
														'event_approved'    => $event_approved,
														'event_unixstamp'   => time(),
														'event_recurring'   => $recur_unit,
														'event_tz'          => $event_tz,
														'event_timeset'	    => count( $hour_min ) > 0 ? intval( $hour_min[0] ).":".intval( $hour_min[1] ) : 0,
														'event_unix_from'   => $_final_unix_from,
														'event_unix_to'     => $_final_unix_to,
														'event_all_day'	    => $event_all_day ), 'event_id='.$event_id );

			/* Recache */
			$this->calendarCallRecache();

			/* Bounce */
			if ( $event_approved )
			{
				$this->registry->output->redirectScreen( $this->lang->words['edit_event_redirect'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;cal_id={$event_calendar_id}&amp;do=showevent&amp;event_id=$event_id" );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['new_event_mod'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;cal_id={$event_calendar_id}" );
			}
		}
	}

	/**
	 * Display the events for a calendar date
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarShowDay()
    {
        $day         = intval( $this->request['d'] );
		$month       = intval( $this->request['m'] );
		$year        = intval( $this->request['y'] );
		$seen_ids    = array();
		$timenow     = gmmktime( 0,0,0, $month, $day, $year);
		$day_array   = IPSTime::date_getgmdate( $timenow );
		$printed     = 0;
		$events_html = '';

		/* Do we have a sensible date */
		if ( ! checkdate( $month, $day , $year ) )
		{
			$this->registry->output->showError( 'calendar_invalid_date_view', 10428 );
		}

		/* Get the events */
		$this->calendarGetEventsSQL($month, $year);

		$events = $this->calendarGetDayEvents( $month, $day, $year );

		/* Loop through the events */
		if ( is_array( $events ) AND count( $events ) )
		{
			foreach( $events as $event )
			{
				if ( ! isset( $this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] ) OR ! $this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] )
				{
					/* Is it a private event? */
					if ( $event['event_private'] == 1 and $this->memberData['member_id'] != $event['event_member_id'] )
					{
						continue;
					}

					/* Do we have permission to see the event */
					if( $event['event_perms'] != '*' )
					{
						$this_member_mgroups[] = $this->memberData['member_group_id'];

						if( $this->memberData['mgroup_others'] )
						{
							$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
						}

						$check = 0;

						foreach( $this_member_mgroups as $this_member_mgroup )
						{
							if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $event['event_perms'] ) )
							{
								$check = 1;
							}
						}

						if( $check == 0 )
						{
							continue;
						}
					}
					
					$set_offset = 0;
			
					if( $event['event_timeset'] )
					{
						$set_offset = ( $this->memberData['member_id'] ? $this->memberData['time_offset'] : $this->settings['time_offset'] ) * 3600;
					}
			
					$event['event_unix_from'] = $event['event_unix_from'] + $set_offset;

					if( !isset( $seen_ids[ $event['eventid'] ] ) OR ! $seen_ids[ $event['eventid'] ] )
					{
						$events_html.= $this->calendarMakeEventHTML( $event );

						$printed++;
						$seen_ids[ $event['event_id'] ] = 1;
					}
				}
			}
		}

		/* Do we have any printed events? */
		if( $printed > 0 )
		{
			$switch = 1;
		}
		else
		{
			// Error if no birthdays
			$switch = 0;
		}

		$this->output .= $this->calendarMakeBirthdayHTML($month, $day, $switch);

		/* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title']    , 'app=calendar&amp;module=calendar&amp;cal_id=' . $this->calendar_id );
		$this->registry->output->addNavigation( $this->month_words[$this->chosen_month - 1] ." " . $this->chosen_year, '' );

		/* Output */
		$this->output .= $this->registry->output->getTemplate( 'calendar' )->calendarEventsList( $events_html );
    }

	/**
	 * Show a single event based on eventid
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarShowEvent()
	{
		/* INIT */
        $event_id = intval($this->request['event_id']);

		/* Check */
		if( ! $event_id )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10429 );
		}

		/* Get it from the DB */
		$this->DB->build( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id={$event_id}" ) );
		$this->DB->execute();

		if ( ! $event = $this->DB->fetch() )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10430 );
		}

		$set_offset = 0;

		if( $event['event_timeset'] )
		{
			$set_offset = ( $this->memberData['member_id'] ? $this->memberData['time_offset'] : $this->settings['time_offset'] ) * 3600;
		}

		$event['event_unix_from'] = $event['event_unix_from'] + $set_offset;

		/* Is it a private event */
		if ( $event['event_private'] == 1 and $this->memberData['member_id'] != $event['event_member_id'] )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10431 );
		}

		/* Do we have permission to see the event? */
		if( $event['event_perms'] != '*' )
		{
			$this_member_mgroups[] = $this->memberData['member_group_id'];

			if( $this->memberData['mgroup_others'] )
			{
				$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
			}

			$check = 0;

			foreach( $this_member_mgroups as $this_member_mgroup )
			{
				if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $event['event_perms'] ) )
				{
					$check = 1;
				}
			}

			if( $check == 0 )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 10432 );
			}
		}

		//-----------------------------------------
		// Highlight...
		//-----------------------------------------

		if ( $this->request['hl'] )
		{
			$event['event_content'] = IPSText::searchHighlight( $event['event_content'], $this->request['hl'] );
			$event['event_title'] = IPSText::searchHighlight( $event['event_title'], $this->request['hl'] );
		}

		/* Output */
		$this->output .= $this->registry->output->getTemplate( 'calendar' )->calendarEventsList( $this->calendarMakeEventHTML( $event ) );

		/* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title']    , 'app=calendar&amp;module=calendar&amp;cal_id=' . $this->calendar_id );
		$this->registry->output->addNavigation( $event['event_title'], '' );

    }

    /**
     * Builds the html for an event
     *
     * @access	public
     * @param	array	Array of event data
     * @return	string	Parsed event HTML
     **/
    public function calendarMakeEventHTML( $event )
	{
		/* INIT */
		$approve_button = "";

		/* What kind of event is it */
		$event_type = $this->lang->words['public_event'];

		if( $event['event_private'] == 1 )
		{
			$event_type = $this->lang->words['private_event'];
		}
		else if( $event['event_perms'] != '*' )
		{
			$event_type = $this->lang->words['restricted_event'];
		}

		/* Do we have an edit button? */
		$edit_button = "";

		/* Are we a super dooper moderator? */
		if( $this->memberData['g_is_supmod'] == 1 )
		{
			$edit_button = $this->registry->output->getTemplate('calendar')->cal_edit_del_button( $event['event_id'], $event['event_calendar_id'] );
		}

		/* Are we the OP of this event? */
        else if( $this->memberData['member_id'] == $event['event_member_id'] )
        {
        	$edit_button = $this->registry->output->getTemplate('calendar')->cal_edit_del_button( $event['event_id'], $event['event_calendar_id'] );
        }

		/* Get the member details and stuff */
        if( $this->parsed_members[ $event['event_member_id'] ] )
        {
	        $member = $this->parsed_members[ $event['event_member_id'] ];
        }
        else
        {
			$member = IPSMember::load( $event['event_member_id'], 'all' );

			$member = IPSMember::buildDisplayData( $member, 0 );

			$this->parsed_members[ $member['member_id'] ] = $member;
		}

		/* Date */
		$set_offset = 0;
		if( $event['event_timeset'] AND !$event['event_all_day'] )
		{
			$set_offset = ( $this->memberData['member_id'] ? $this->memberData['time_offset'] : $this->settings['time_offset'] ) * 3600;
		}

		/**
		 * Since set_offset takes into account the member timezone, we need to reverse the submitted time zone
		 */
		//$event['event_unix_from']	= $event['event_unix_from'] - ($event['event_tz'] * 3600);
		//$event['event_unix_to']		= $event['event_unix_to'] ? $event['event_unix_to'] - ($event['event_tz'] * 3600) : 0;

		$tmp  = explode( ',', gmdate( 'n,j,Y,G,i', $event['event_unix_from'] ) );

		$event['mday']       = $tmp[1];
		$event['month']      = $tmp[0];
		$event['year']       = $tmp[2];
		$event['month_text'] = $this->month_words[ $tmp[0] - 1 ];

		$event['_start_date'] = gmstrftime( $this->settings['clock_joined'], $event['event_unix_from'] + $set_offset );


		$this->request['d'] = $event['mday'];
		$this->request['m'] = $event['month'];
		$this->request['y'] = $event['year'];

		$type = $this->lang->words['se_normal'];
		$de   = "";

		if( $event['event_recurring'] == 0 AND $event['event_unix_to'] )
		{
			$type = $this->lang->words['se_range'];
			$de   = $this->lang->words['se_ends'] . ' ' . gmstrftime( $this->settings['clock_joined'], $event['event_unix_to'] - $set_offset );
		}
		else if ( $event['event_recurring'] == 1 )
		{
			$type = $this->lang->words['se_recur'];
			$de   = $this->lang->words['se_ends'] . ' ' . gmstrftime( $this->settings['clock_joined'], $event['event_unix_to'] - $set_offset );
		}

		$event['time'] = '';
		if( $type == $this->lang->words['se_normal'] )
		{
			//if( $tmp[3] > 0 )
			if( !$event['event_all_day'] )
			{
				$event['time'] = " {$tmp[3]}:{$tmp[4]}";
			}
		}

		/* Moderate */
		$event['_quicktime'] = intval( $event['month'] ).'-'.intval( $event['mday'] ).'-'.intval( $event['year']);

		if( $this->can_moderate )
		{
			if( ! $event['event_approved'] )
			{
				$event['_event_css_1'] = 'row2shaded';
				$event['_event_css_2'] = 'row4shaded';
				$approve_button = $this->registry->output->getTemplate('calendar')->cal_approve_button($event['event_id'], $event['event_calendar_id'], $event);
			}
			else
			{
				$event['_event_css_1'] = 'row1';
				$event['_event_css_2'] = 'row2';
				$approve_button = $this->registry->output->getTemplate('calendar')->cal_unapprove_button($event['event_id'], $event['event_calendar_id'], $event);
			}
		}
		else
		{
			$event['_event_css_1'] = 'row1';
			$event['_event_css_2'] = 'row2';
		}

		/* parse bbcode */
		IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= intval( $event['event_smilies'] );
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $event['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $event['mgroup_others'];

		$event['event_content'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $event['event_content'] );

		/* Return Event HTML */
		return $this->registry->output->getTemplate('calendar')->cal_show_event($event, $member, $event_type, $edit_button, $approve_button, $type, $de );
	}

	/**
	 * Builds the html for a mini calendar
	 *
	 * @access	public
	 * @var		int		$month	Numeric value of month to build
	 * @var		int		$year	Year to build
	 * @return	string	Mini-cal HTML
	 **/
    public function getMiniCalendar( $month, $year )
    {
        //-----------------------------------------
        // Print the main calendar body
        //-----------------------------------------

        $cal_output = $this->getMonthEvents( $month, $year, 1 );

        return $this->registry->output->getTemplate('calendar')->mini_cal_mini_wrap( $this->month_words[ $month - 1 ], $month, $year, $cal_output, $this->day_words );
    }

	/**
	 * Gets birthdays and stores them in the query_bday_cache
	 *
	 * @access	public
	 * @param	integer	$month	Month
	 * @return	void
	 */
	public function calendarGetBirthdaySQL($month)
	{
		if( ! isset( $this->query_bday_cache[ $month ] ) OR !is_array( $this->query_bday_cache[ $month ] ) )
		{
			// We are just going to query next and previous month
			// so let's do it in one query and cache it...

			$prev_month = $this->calendarGetPreviousMonth( $month, date("Y") );
			$next_month = $this->calendarGetNextMonth( $month, date("Y") );

			$this->query_bday_cache[ $month ] 					= array();
			$this->query_bday_cache[ $next_month['month_id'] ] 	= array();
			$this->query_bday_cache[ $prev_month['month_id'] ] 	= array();

			$this->DB->build( array( 'select' => 'bday_day, bday_month, member_id, members_display_name', 'from' => 'members', 'where' => 'bday_month IN('.$prev_month['month_id'].','.$month.','.$next_month['month_id'].')' ) );
			$this->DB->execute();

			while ($r = $this->DB->fetch())
			{
				$this->query_bday_cache[ $r['bday_month'] ][ $r['bday_day'] ][] = $r;
			}
		}		
    }

	/**
	 * Generates the sql to query events
	 *
	 * @access	public
	 * @param	integer	$month		Month to get events from
	 * @param	integer	$year		Year to get events from
	 * @param	array	$get_cached	Get from cache
	 * @return	void
	 */
	public function calendarGetEventsSQL( $month=0, $year=0, $get_cached=array() )
	{
		/* INIT */
		if( ! count( $get_cached ) )
		{
			/*
				Mini-cal is going to call next month and
				previous month anyways....let's just pull
				it all in one query and cache it
			*/
			$next_month = $this->calendarGetNextMonth( $month, $year );
			$prev_month = $this->calendarGetPreviousMonth( $month, $year );
			$numberdays = date( 't', mktime( 0, 0, 0, $next_month['month_id'], 1, $next_month['year_id'] ) );
			$timenow    = gmmktime( 0, 0, 1   , $prev_month['month_id'], 1, $prev_month['year_id'] ) - ( 12 * 3600 );
			$timethen   = gmmktime( 23, 59, 59, $next_month['month_id'], $numberdays, $next_month['year_id'] ) + ( 12 * 3600 );
			$getcached  = 0;
		}
		else
		{
			$next_month = array( 'month_id' => 0 );
			$prev_month = array( 'month_id' => 0 );
			$timenow    = $get_cached['timenow'];
			$timethen   = $get_cached['timethen'];
			list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', $get_cached['timenow']) );
			$getcached  = 1;
		}

		/* Get the events */
		if( !isset($this->event_cache[ $month ]) OR ! is_array( $this->event_cache[ $month ] ) )
		{
			/* Get for cache */
			if ( $getcached )
			{
				$extra = ( isset( $get_cached['cal_id'] ) AND $get_cached['cal_id'] ) ? "event_calendar_id=" . intval( $get_cached['cal_id'] ) . " AND " : '';

				$this->DB->build( array(
											//'queryKey'      => 'fetchEventsAll',
											//'queryVars'     => array( 'extra' => $extra, 'timenow' => $timenow, 'timethen' => $timethen, 'month' => $month ),
											//'queryLocation' => $this->registry->dbFunctions()->fetchQueryFileName( 'public', 'calendar' ),
											//'queryClass'    => $this->registry->dbFunctions()->fetchQueryFileClassName( 'public', 'calendar' ),

											'select'        => '*',
											'from'          => 'cal_events',
											'where'         => "{$extra} event_approved=1
															AND ( (event_unix_to >= {$timenow} AND event_unix_from <= {$timethen} )
															OR ( event_unix_to=0 AND event_unix_from >= {$timenow} AND event_unix_from <= {$timethen} )
															OR ( event_recurring=3 AND " . $this->DB->buildFromUnixtime( 'event_unix_from', '%c' ) . "={$month} AND event_unix_to <= {$timethen} ) )"
								) 		);
				$this->DB->execute();
			}
			else
			{
				/* Get for display */
				$extra = $this->can_moderate ? "event_approved IN (0,1)" : "event_approved=1";

				$this->DB->build( array(
											//'queryKey'      => 'fetchEventsCal',
											//'queryVars'     => array( 'event_calendar_id' => $this->calendar_id, 'extra' => $extra, 'timenow' => $timenow, 'timethen' => $timethen, 'month' => $month ),
											//'queryLocation' => $this->registry->dbFunctions()->fetchQueryFileName( 'public', 'calendar' ),
											//'queryClass'    => $this->registry->dbFunctions()->fetchQueryFileClassName( 'public', 'calendar' ),

											'select'        => '*',
											'from'          => 'cal_events',
											'where'         => "event_calendar_id = {$this->calendar_id} AND {$extra}
															AND ( (event_unix_to >= {$timenow} AND event_unix_from <= {$timethen} )
															OR ( event_unix_to=0 AND event_unix_from >= {$timenow} AND event_unix_from <= {$timethen} )
															OR ( event_recurring=3 AND " . $this->DB->buildFromUnixtime( 'event_unix_from', '%c' ) . "={$month} AND event_unix_to <= {$timethen} ) )"
								) 		);
				$this->DB->execute();
			}

			while( $r = $this->DB->fetch() )
			{
				/* Private Event */
				if( $r['event_private'] == 1 AND ! $getcached )
				{
					if( ! $this->memberData['member_id'] )
					{
						continue;
					}

					if( $this->memberData['member_id'] != $r['event_member_id'] )
					{
						continue;
					}
				}

				/* Got Permission? */
				if( $r['event_perms'] != '*' AND ! $getcached )
				{
					$this_member_mgroups[] = $this->memberData['member_group_id'];

					if( $this->memberData['mgroup_others'] )
					{
						$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
					}

					$check = 0;

					foreach( $this_member_mgroups as $this_member_mgroup )
					{
						if( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $r['event_perms'] ) )
						{
							$check = 1;
						}
					}

					if( $check == 0 )
					{
						continue;
					}
				}

				/* Times */
				$set_offset = 0;
				if( $r['event_timeset'] AND ! $r['event_all_day'] )
				{
					$set_offset = ( $this->memberData['member_id'] ? $this->memberData['time_offset'] : $this->settings['time_offset'] ) * 3600;
				}

				$r['_unix_from'] = $r['event_unix_from']    ? $r['event_unix_from'] + $set_offset  : 0;
				$r['_unix_to']   = $r['event_unix_to'] > 0  ? $r['event_unix_to']   + $set_offset  : 0;

				/* Recurring Events */
				if( $r['event_recurring'] > 0 )
				{
					$r['recurring'] = 1;

					/* Get recurring months */
					$r_month = 0;
					$r_stamp = $r['event_unix_from'];
					$r_month = gmdate('n', $r['_unix_from']);
					$r_year  = gmdate('Y', $r['_unix_from']);

					if( $r_month == $month && ( $r_year == $year OR $r['event_recurring'] == 3 ) && $getcached == 0 )
					{
						$this->event_cache[ $r_month ]['recurring'][] = $r;
					}

					while( $r_stamp < $r['_unix_to'] )
					{
						/* Stop Duplicates! */
						$shouldpass = 1;
						if( isset( $this->event_cache[ $r_month ]['recurring'] ) AND count( $this->event_cache[ $r_month ]['recurring'] ) )
						{
							foreach( $this->event_cache[ $r_month ]['recurring'] as $eventarray )
							{
								if( $eventarray['event_id'] == $r['event_id'] )
								{
									$shouldpass = 0;
								}
							}
						}

						if( ( ( $r_month != $month AND $r_month != $next_month['month_id'] AND $r_month != $prev_month['month_id'] ) OR $r_year != $year ) AND $getcached == 0 )
						{
							$shouldpass = 0;
						}

						if( $shouldpass == 1 )
						{
							$this->event_cache[ $r_month ]['recurring'][] = $r;
						}

						if( $r['event_recurring'] == 1 )
						{
							$r_stamp += 604800;
						}
						elseif( $r['event_recurring'] == 2 )
						{
							$r_stamp += 86400 * 30;
						}
						else
						{
							// No need to check year, as month would then match anyways
							$r_stamp += 31536000;
						}

						if( $r_month != gmdate('n', $r_stamp) )
						{
							$r_month = gmdate('n', $r_stamp);
						}

						if( $r_year != gmdate('Y', $r_stamp) )
						{
							$r_year  = gmdate('Y', $r_stamp);
						}
					}
				}

				//-----------------------------------------
				// Ranged event?
				// OK, this is getting silly.....
				// _checkdate -> gmmtime( 0,0,0 $_checkdate..
				// was showing a day earlier than allowed
				//-----------------------------------------

				else if( $r['event_recurring'] == 0 AND $r['_unix_to'] )
				{
					$_gotit         = array();
					$_begin         = gmdate( "z", $r['_unix_from'] );
					$_checkdate		= gmdate( "z", $r['_unix_to'] );
					$_checkts		= $r['_unix_from'];
					$_cur_mo		= $month; // Store to retrieve later if necessary
					$_tmp           = '';
					$r['ranged']    = 1;

					/* Lapse over a year? */
					if( $_checkdate < $_begin )
					{
						$tmp_difference = 365 + $_checkdate - $_begin;

						if( $tmp_difference > 0 )
						{
							$_checkdate = $_begin + $tmp_difference;
						}

						unset( $tmp_difference );
					}

					while( $_begin <= $_checkdate  )
					{
						// Did we lapse over the month?
						// How about the year?  If so,
						// let's go ahead and reset ourselves

						$realday = gmdate( "j", $_checkts );
						$month   = gmdate( "n", $_checkts );

						if( ! isset($_gotit[ $month ]) )
						{
							$_gotit[ $month ] = array();
						}
						if( ! in_array( $realday, $_gotit[ $month ] ) )
						{
							$this->event_cache[ $month ]['ranged'][ $realday ][] = $r;

							$_count =  count( $this->event_cache[ $month ]['ranged'][ $realday ] ) - 1;
							$_tmp   =  $this->event_cache[ $month ]['ranged'][ $realday ][ $_count ];
							$_gotit[ $month ][] = $realday;
						}
						else
						{
							if( $r['_unix_to'] != $r['event_unix_from'] )
							{
								$this->event_cache[ $month ]['ranged'][ $realday ][] = $_tmp;
							}
						}
						$_begin   += 1;
						$_checkts += 86400;
					}
					$month = $_cur_mo;
				}
				/* Single Event */
				else
				{
					$r['single'] = 1;

					/* Make sure correct month is used for cached queries */
					list( $_month, $_day, $_year ) = explode( ',', gmdate('n,j,Y', $r['_unix_from']  ) );

					$this->event_cache[ $_month ]['single'][ $_day ][] = $r;
				}
			}
		}
    }

	/**
	 * Gets the days events
	 *
	 * @access	public
	 * @param	string	$month	Month
	 * @param	string	$day	Day
	 * @param	string	$year	Year
	 * @return	array 	Get events for selected day
	 **/
	public function calendarGetDayEvents( $month="", $day="", $year="" )
	{
		/* INIT */
		$return = array();

		/* Ranged */
		if( isset( $this->event_cache[ $month ]['ranged'][ $day ] ) AND is_array( $this->event_cache[ $month ]['ranged'][ $day ] ) and count( $this->event_cache[ $month ]['ranged'][ $day ] ) )
		{
			foreach( $this->event_cache[ $month ]['ranged'][ $day ] as $idx => $data )
			{
				$return[] = $this->event_cache[ $month ]['ranged'][ $day ][ $idx ];
			}
		}

		/* Recurring */
		if( isset( $this->event_cache[ $month ]['recurring'] ) AND is_array( $this->event_cache[ $month ]['recurring'] ) and count( $this->event_cache[ $month ]['recurring'] ) )
		{
			foreach( $this->event_cache[ $month ]['recurring'] as $idx => $data )
			{
				if ( $this->getInfoEvents( $data, $month, $day, $year ) )
				{
					$return[] = $this->event_cache[ $month ]['recurring'][ $idx ];
				}
			}
		}

		/* Single Day */
		if( isset( $this->event_cache[ $month ]['single'][ $day ] ) AND is_array( $this->event_cache[ $month ]['single'][ $day ] ) and count( $this->event_cache[ $month ]['single'][ $day ] ) )
		{
			foreach( $this->event_cache[ $month ]['single'][ $day ] as $idx => $data )
			{
				$return[] = $this->event_cache[ $month ]['single'][ $day ][ $idx ];
			}
		}

		return $return;
	}

	/**
	 * Get event info
	 *
	 * @access	public
	 * @param	array	$event	Event data
	 * @param	int		$month	Month
	 * @param	int		$day	Day
	 * @param	int		$year	Year
	 * @param	bool	$adj	Adjust or not
	 * @return	bool
	 */
	public function getInfoEvents( $event, $month, $day, $year, $adj=1 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_start  = gmmktime(0 , 0 , 0 , $month, $day, $year);
		$_lunch  = gmmktime(12, 0 , 0 , $month, $day, $year);
		$_end    = gmmktime(23, 59, 59, $month, $day, $year) + 1;
		$_month  = gmmktime(0 , 0 , 0 , $month, 1   , $year);
		$_offset = 0; // - Set at time of save: $event['event_tz'] * 3600;

		//-----------------------------------------
		// Already seen it?
		//-----------------------------------------

		if ( $event['event_id'] AND isset($this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ]) AND $this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Check we're in range
		//-----------------------------------------

		if ( isset($event['single']) AND $event['single'] )
		{
			if ( $month.','.$day.','.$year == gmdate('n,j,Y', $event['_unix_from']) )
			{
				$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] = 1;
				return TRUE;
			}
		}

		if ( ($event['_unix_to']) < $_start OR ($event['_unix_from'] ) > $_end )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Check recurring
		//-----------------------------------------

		if ( $event['event_recurring'] )
		{
			if ( $adj AND gmdate('w', $event['_unix_from']) != gmdate('w', $event['event_unix_from']) )
			{
				if ( $event['_unix_from'] > $event['event_unix_from'])
				{
					$_lunch -= 86400;
				}
				else
				{
					$_lunch += 86400;
				}
			}

			//-----------------------------------------
			// Weekly
			//-----------------------------------------

			if ( $event['event_recurring'] == 1 )
			{
				if ( gmdate('w', $event['event_unix_from']) != gmdate('w', $_lunch ) )
				{
					return FALSE;
				}
				else
				{
					return TRUE;
				}
			}

			//-----------------------------------------
			// Monthly
			//-----------------------------------------

			else if ( $event['event_recurring'] == 2 )
			{
				if ( gmdate('j', $event['event_unix_from']) == gmdate('j', $_lunch ) )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}

			//-----------------------------------------
			// Yearly
			//-----------------------------------------

			else if ( $event['event_recurring'] == 3 )
			{
				if ( (gmdate('j', $event['event_unix_from']) == gmdate('j', $_lunch )) AND (gmdate('n', $event['event_unix_from']) == gmdate('n', $_lunch )) )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Builds the html for the monthly events
	 *
	 * @access	public
	 * @param	int		$month		Numeric value of the month to get events from
	 * @param	int		$year		Year to get events from
	 * @param	bool	$minical	Set to 1 if this is for the minicalendar
	 * @return	string	Calendar HTML output
	 */
    public function getMonthEvents( $month, $year, $minical=0 )
	{
		//-----------------------------------------
		// Reset shown events
		//-----------------------------------------

		$this->shown_events = array();

		//-----------------------------------------
		// Work out timestamps
		//-----------------------------------------

		$our_datestamp   = gmmktime( 0,0,0, $month, 1, $year);
		$first_day_array = IPSTime::date_getgmdate( $our_datestamp );

		if( $this->settings['ipb_calendar_mon'] )
		{
		    $first_day_array['wday'] = $first_day_array['wday'] == 0 ? 7 : $first_day_array['wday'];
		}

		//-----------------------------------------
		// Get the birthdays from the database
		//-----------------------------------------

		if ( $this->settings['show_bday_calendar'] )
		{
			$birthdays = array();

			$this->calendarGetBirthdaySQL( $month );

			$birthdays = $this->query_bday_cache[ $month ];
		}

		//-----------------------------------------
		// Get the events
		//-----------------------------------------

		$this->calendarGetEventsSQL( $month, $year );

		//-----------------------------------------
		// Get events
		//-----------------------------------------

		$seen_days     = array();
		$seen_ids      = array();
		$cal_output    = "";
		$calendar_data = array();

		for( $c = 0 ; $c < 42; $c++ )
		{
			//-----------------------------------------
			// Work out timestamps
			//-----------------------------------------

			$_year      = gmdate('Y', $our_datestamp);
			$_month     = gmdate('n', $our_datestamp);
			$_day       = gmdate('j', $our_datestamp);
			$day_array  = IPSTime::date_getgmdate( $our_datestamp );

			$check_against = $c;

			if( $this->settings['ipb_calendar_mon'] )
			{
		    	$check_against = $c+1;
			}

			if( ( ( $c ) % 7 ) == 0 )
			{
				//-----------------------------------------
				// Kill the loop if we are no longer on our month
				//-----------------------------------------

				if( $day_array['mon'] != $month )
				{
					break;
				}

				if( $minical )
				{
					$cal_output .= $this->registry->output->getTemplate('calendar')->mini_cal_new_row( $our_datestamp );
				}
				else
				{
					$cal_output .= $this->registry->output->getTemplate('calendar')->cal_new_row( $our_datestamp );
				}
			}

			//-----------------------------------------
			// Run out of legal days for this month?
			// Or have we yet to get to the first day?
			//-----------------------------------------

			if ( ($check_against < $first_day_array['wday']) or ($day_array['mon'] != $month) )
			{
				$cal_output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_blank_cell()
										: $this->registry->output->getTemplate('calendar')->cal_blank_cell();
			}
			else
			{
				if ( isset($seen_days[ $day_array['yday'] ]) AND $seen_days[ $day_array['yday'] ] == 1 )
				{
					continue;
				}

				$seen_days[ $day_array['yday'] ] = 1;
				$tmp_cevents     = array();
				$this_day_events = "";
				$cal_date        = $day_array['mday'];
				$queued_event    = 0;
				$cal_date_queued = "";

				//-----------------------------------------
				// Get events
				//-----------------------------------------

				$events = $this->calendarGetDayEvents( $_month, $_day, $_year );

				if ( is_array( $events ) AND count( $events ) )
				{
					foreach( $events as $event )
					{
						if ( !isset($this->shown_events[ $_month.'-'.$_day.'-'.$_year ][ $event['event_id'] ]) OR !$this->shown_events[ $_month.'-'.$_day.'-'.$_year ][ $event['event_id'] ] )
						{
							//-----------------------------------------
							// Recurring
							//-----------------------------------------

							if ( isset($event['recurring']) )
							{
								$tmp_cevents[ $event['event_id'] ] = $this->registry->output->getTemplate('calendar')->cal_events_wrap_recurring( $event );
							}
							else if ( isset($event['single']) )
							{
								$tmp_cevents[ $event['event_id'] ] = $this->registry->output->getTemplate('calendar')->cal_events_wrap( $event );
							}
							else
							{
								$tmp_cevents[ $event['event_id'] ] = $this->registry->output->getTemplate('calendar')->cal_events_wrap_range( $event );
							}

							$this->shown_events[ $_month.'-'.$_day.'-'.$_year ][ $event['event_id'] ] = 1;

							//-----------------------------------------
							// Queued events?
							//-----------------------------------------

							if ( ! $event['event_approved'] AND $this->can_moderate )
							{
								$queued_event = 1;
							}
						}
					}

					//-----------------------------------------
					// How many events?
					//-----------------------------------------

					if ( count($tmp_cevents) >= $this->calendar['cal_event_limit'] )
					{
						$this_day_events = $this->registry->output->getTemplate('calendar')->cal_events_wrap_manual(
																												  		"cal_id={$this->calendar_id}&amp;do=showday&amp;y=".$day_array['year']."&amp;m=".$day_array['mon']."&amp;d=".$day_array['mday'],
																												  		sprintf( $this->lang->words['show_n_events'], intval(count($tmp_cevents)) ) );
					}
					else if ( count( $tmp_cevents ) )
					{
						$this_day_events = implode( "\n", $tmp_cevents );
					}

					$tmp_cevents[] = array();
        		}

				//-----------------------------------------
				// Birthdays
				//-----------------------------------------

				if ( $this->calendar['cal_bday_limit'] )
				{
					if ( isset($birthdays[ $day_array['mday'] ]) and count( $birthdays[ $day_array['mday'] ] ) > 0 )
					{
						$no_bdays = count($birthdays[ $day_array['mday'] ]);

						if ( $no_bdays )
						{
							if ( $this->calendar['cal_bday_limit'] and $no_bdays <= $this->calendar['cal_bday_limit'] )
							{
								foreach( $birthdays[ $day_array['mday'] ] as $user )
								{
									$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_manual(
																												"cal_id={$this->calendar_id}&amp;do=birthdays&amp;y=".$day_array['year']."&amp;m=".$day_array['mon']."&amp;d=".$day_array['mday'],
																												$user['members_display_name'].$this->lang->words['bd_birthday'] );
								}

							}
							else
							{
								$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_manual(
																															 "cal_id={$this->calendar_id}&amp;do=birthdays&amp;y=".$day_array['year']."&amp;m=".$day_array['mon']."&amp;d=".$day_array['mday'],
																															 sprintf( $this->lang->words['entry_birthdays'], $no_bdays ) );
							}
						}
					}
        		}

        		//-----------------------------------------
        		// Show it
        		//-----------------------------------------

        		if ($this_day_events != "")
        		{
        			$cal_date        = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "app=calendar&amp;module=calendar&amp;cal_id={$this->calendar_id}&amp;do=showday&amp;y=".$year."&amp;m=".$month."&amp;d=".$day_array['mday'], 'public', 'false', 'cal_day' ) ."'>{$day_array['mday']}</a>";

					$cal_date_queued = "" . $this->settings['base_url'] . "app=calendar&amp;cal_id={$this->calendar_id}&amp;modfilter=queued&amp;do=showday&amp;y=".$year."&amp;m=".$month."&amp;d=".$day_array['mday'];

        			$this_day_events = $this->registry->output->getTemplate('calendar')->cal_events_start() . $this_day_events . $this->registry->output->getTemplate('calendar')->cal_events_end();
        		}

        		if ( ($day_array['mday'] == $this->now_date['mday']) and ($this->now_date['mon'] == $day_array['mon']) and ($this->now_date['year'] == $day_array['year']))
        		{
        			$cal_output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_date_cell_today($cal_date, $this_day_events) : $this->registry->output->getTemplate('calendar')->cal_date_cell_today($cal_date, $this_day_events, $cal_date_queued, $queued_event, $this->_buildDayID( $this->chosen_year, $this->chosen_month, $_day ) );
        		}
        		else
        		{
        			$cal_output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_date_cell($cal_date, $this_day_events) : $this->registry->output->getTemplate('calendar')->cal_date_cell($cal_date, $this_day_events, $cal_date_queued, $queued_event, $this->_buildDayID( $this->chosen_year, $this->chosen_month, $_day ) );
        		}

        		unset($this_day_events);

        		$our_datestamp += 86400;
        	}
        }

    	return $cal_output;
    }

	/**
	 * Displays birthdays for the specified date
	 *
	 * @access	public
	 * @return	void
	 **/
	public function calendarShowBirthdays()
	{
		/* INIT */
		$day   = intval( $this->request['d'] );
		$month = intval( $this->request['m'] );
		$year  = intval( $this->request['y'] );

		/* Do we have a sensible date */
		if( ! checkdate( $month, $day , $year ) )
        {
			$this->registry->output->showError( 'calendar_invalid_date_view', 10433 );
		}

		/* Output */
        $this->output .= $this->registry->output->getTemplate('calendar')->calendarEventsList( $this->calendarMakeBirthdayHTML( $month, $day ) );

        /* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=calendar&amp;module=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title']    , 'app=calendar&amp;module=calendar&amp;cal_id=' . $this->calendar_id );
		$this->registry->output->addNavigation( $this->lang->words['cal_birthdays'] ." " . $day ." " . $this->month_words[$this->chosen_month - 1] . " " . $this->chosen_year, '' );
    }

	/**
	 * Make Birthday HTML (return HTML for bdays)
	 *
	 * @access	public
	 * @param	integer	$month	Month
	 * @param	integer	$day	Day
	 * @param	bool	$switch Don't show an error page if no birthdays, just return
	 * @return	string	HTML output
	 **/
	public function calendarMakeBirthdayHTML($month, $day, $switch=0)
	{
		/* Check birthday setting */
		if( ! $this->settings['show_bday_calendar'] )
		{
			return;
		}

		/* Is it a leap yar? */
		if( ! date( "L" ) )
		{
			if( $month == "2" AND $day == "28" )
			{
				$where_string = "bday_month=".$month." AND (bday_day={$day} OR bday_day=29)";
			}
			else
			{
				$where_string = 'bday_month='.$month . " AND bday_day={$day}";
			}
		}
		else
		{
			$where_string = 'bday_month='.$month . " AND bday_day={$day}";
		}

		/* Get the birthdays from the database */
		$birthdays = array();
		$output    = "";

		$this->DB->build( array( 'select' => 'bday_day, bday_month, bday_year, member_id, members_display_name, members_seo_name', 'from' => 'members', 'where' => $where_string ) );
		$this->DB->execute();

		if( ! $this->DB->getTotalRows() )
		{
			if( $switch == 1 )
			{
				return;
			}
			else
			{
				$this->registry->output->showError( 'calendar_no_birthdays', 10434 );
			}
		}
		else
		{
			/* Loop through the birthdays */
			$rows = array();

			while( $r = $this->DB->fetch() )
			{
				$age = $r['bday_year'] ? $this->chosen_year - $r['bday_year'] : 0;
				$rows[] = array( 'uid' => $r['member_id'], 'seoname' => $r['members_seo_name'], 'uname' => $r['members_display_name'], 'age' => $age );
			}
		}

		return $this->registry->output->getTemplate( 'calendar' )->calendarBirthdayList( $rows );
	}

	/**
	 * Builds a month dropdown
	 *
	 * @access	private
	 * @param	integer	$month	Month to select by default
	 * @return	string	Dropdown HTML
	 */
	private function calendarGetMonthDropDown( $month=0 )
	{
		$return = "";

		if( $month == "" )
		{
			$month = $this->chosen_month;
		}

		for( $x = 1 ; $x <= 12 ; $x++ )
		{
			$return .= "\t<option value='$x'";
			$return .= ($x == $month) ? " selected='selected'" : "";
			$return .= ">".$this->month_words[$x-1]."</option>\n";
		}

		return $return;
	}


	/**
	 * Builds a year dropdown
	 *
	 * @access	private
	 * @var		integer	$year	Year to select by default
	 * @return	string	HTML dropdown
	 */
	private function calendarGetYearDropDown( $year=0 )
	{
		$return = "";

		$this->settings['start_year'] = ( $this->settings['start_year'] ) ? $this->settings['start_year'] : 2001;
		$this->settings['year_limit'] = ( $this->settings['year_limit'] ) ? $this->settings['year_limit'] : 5;

		if( $year == "" )
		{
			$year = $this->chosen_year;
		}

		if( ($this->now_date['year'] + $this->settings['year_limit']) < $year )
		{
			$difference = $year - ($this->now_date['year'] + $this->settings['year_limit']);

			if( $difference < 50 )
			{
				$this->settings[ 'year_limit'] =  $this->settings['year_limit'] + $difference ;
			}
		}

		for( $x = $this->settings['start_year'], $xy = $this->now_date['year'] + $this->settings['year_limit'] ; $x <= $xy ; $x++ )
		{
			$return .= "\t<option value='$x'";
			$return .= ($x == $year) ? " selected='selected'" : "";
			$return .= ">".$x."</option>\n";
		}

		return $return;
	}

	/**
	 * Builds a day dropdown
	 *
	 * @access	private
	 * @param	integer	$day	day to select by default
	 * @return	string	HTML dropdown
	 */
	private function calendarGetDayDropDown( $day=0 )
	{
		if( $day == "" )
		{
			$day = $this->now_date['mday'];
		}

		$return = "";

		for( $x = 1 ; $x <= 31 ; $x++ )
		{
			$return .= "\t<option value='$x'";
			$return .= ($x == $day) ? " selected='selected'" : "";
			$return .= ">".$x."</option>\n";
		}

		return $return;
	}

	/**
	 * Figures out what the next month on the calendar is
	 *
	 * @access	private
	 * @param	integer	$month	Current Month
	 * @param	integer	$year	Current Year
	 * @return	array 	Month data
	 */
	private function calendarGetNextMonth( $month, $year )
	{
		$next_month = array();

		$next_month['year_id']    = $year;

		$next_month['month_name'] = $this->month_words[$month];
		$next_month['month_id']   = $month + 1;

		if ($next_month['month_id'] > 12 )
		{
			$next_month['month_name'] = $this->month_words[0];
			$next_month['month_id']   = 1;
			$next_month['year_id']    = $year + 1;
		}

		return $next_month;
	}

    /**
     * Recaches calendars
     *
     * @access	private
     * @return	void
     */
	private function calendarCallRecache()
    {
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/calendar/modules_admin/calendar/calendars.php' );
		$calendars = new admin_calendar_calendar_calendars( $this->registry );
		$calendars->makeRegistryShortcuts( $this->registry );
		$calendars->calendarRebuildCache( 0 );
    }

	/**
	 * Figures out what the previous month on the calendar is
	 *
	 * @access	private
	 * @param	integer	$month	Current Month
	 * @param	integer	$year	Current Year
	 * @return	array 	Month data
	 **/
	private function calendarGetPreviousMonth($month, $year)
	{
		$prev_month               = array();
		$prev_month['year_id']    = $year;
		$prev_month['month_id']   = $month - 1;
		$prev_month['month_name'] = $this->month_words[$month - 2];

		if( $this->chosen_month == 1 )
		{
			$prev_month['month_name'] = $this->month_words[11];
			$prev_month['month_id']   = 12;
			$prev_month['year_id']    = $year - 1;

		}

		return $prev_month;
    }
}