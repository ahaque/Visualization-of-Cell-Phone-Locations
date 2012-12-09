<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal plugin: calendar
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @since		1st march 2002
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_calendar extends public_portal_portal_portal 
{
	/**
	 * Initialize module
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
 	{
 	}
 	
	/**
	 * Show the current calendar month on the portal
	 *
	 * @access	public
	 * @return	string		HTML content to replace tag with
	 */
	public function calendar_show_current_month()
	{
		//-----------------------------------------
		// Grab calendar class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'calendar', 'calendar' ) . '/calendars.php' );
		$calendar = new public_calendar_calendar_calendars();
		$calendar->makeRegistryShortcuts( $this->registry );

		//-----------------------------------------
        // Load lang and templs
        //-----------------------------------------
        
        ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_calendar' ) );

 		//-----------------------------------------
 		// DO some set up
 		//-----------------------------------------
 		
 		$calendar->calendar_id = 1; // CHANGE TO DEFAULT?
 		
		if( ! count( $this->caches['calendars'] ) )
		{
			$cache	= array();
			
			$this->DB->build( array( 
									'select'	=> 'c.*', 
									'from'		=> array( 'cal_calendars' => 'c' ), 
									'add_join'	=> array(
														array(
																'select'	=> 'p.*',
																'from'		=> array( 'permission_index' => 'p' ),
																'where'		=> "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																'type'		=> 'left',
															)
														)	
						) 	);
			$this->DB->execute();
			
			while( $cal = $this->DB->fetch() )
			{
				$cache[ $cal['cal_id'] ] = $cal;
			}
			
			$this->cache->setCache( 'calendars', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
		}
		
		/* Calendar Cache */			
		if( count( $this->caches['calendars'] ) AND is_array( $this->caches['calendars'] ) )
		{
			foreach($this->caches['calendars'] as $cal_id => $cal )
			{
				$selected = "";
				
				/* Got a perm */
				if( ! $this->registry->permissions->check( 'view', $cal ) )
				{
					continue;
				}
								
				if ( $cal['cal_id'] == $calendar->calendar_id )
				{
					$calendar->calendar	= $cal;
					$selected			= " selected='selected'";
				}
				
				$calendar->calendar_cache[ $cal['cal_id'] ] = $cal;
			}
		}
		
		if( ! $calendar->calendar )
		{
			if( count( $calendar->calendar_cache ) )
			{
				$tmp_resort = $calendar->calendar_cache;
				ksort($tmp_resort);
				reset($tmp_resort);
				$default_calid = key( $tmp_resort );
				$calendar->calendar_id = $default_calid;
				$calendar->calendar = $tmp_resort[ $default_calid ];
				unset( $tmp_resort );
			}
		}
 		
		$calendar->calendarBuildPermissions();
		
		if( !is_array($calendar->calendar) OR !count($calendar->calendar) OR !$calendar->can_read )
		{
			return'';
		}

 		//-----------------------------------------
        // Finally, build up the lang arrays
        //-----------------------------------------
        
        $calendar->month_words = array( $this->lang->words['M_1'] , $this->lang->words['M_2'] , $this->lang->words['M_3'] ,
										$this->lang->words['M_4'] , $this->lang->words['M_5'] , $this->lang->words['M_6'] ,
										$this->lang->words['M_7'] , $this->lang->words['M_8'] , $this->lang->words['M_9'] ,
										$this->lang->words['M_10'], $this->lang->words['M_11'], $this->lang->words['M_12'] );
        							
		if( !$this->settings['ipb_calendar_mon'] )
		{
        	$calendar->day_words   = array( $this->lang->words['D_0'], $this->lang->words['D_1'], $this->lang->words['D_2'],
        								$this->lang->words['D_3'], $this->lang->words['D_4'], $this->lang->words['D_5'],
        								$this->lang->words['D_6'] );
    	}
    	else
    	{
        	$calendar->day_words   = array( $this->lang->words['D_1'], $this->lang->words['D_2'], $this->lang->words['D_3'],
        								$this->lang->words['D_4'], $this->lang->words['D_5'], $this->lang->words['D_6'],
        								$this->lang->words['D_0'] );
		}
 		
 		//-----------------------------------------
 		// What now?
 		//-----------------------------------------
 		
 		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + ipsRegistry::getClass( 'class_localization')->getTimeOffset() ) );
		
		$now_date = array(
						  'year'    => $a[0],
						  'mon'     => $a[1],
						  'mday'    => $a[2],
						  'hours'   => $a[3],
						  'minutes' => $a[4],
						  'seconds' => $a[5]
						);
							   
 		$content = $calendar->getMiniCalendar( $now_date['mon'], $now_date['year'] );
 		
 		return $this->registry->getClass('output')->getTemplate('portal')->calendarWrap( $content );
  	}
}