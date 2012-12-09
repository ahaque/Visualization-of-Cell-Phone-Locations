<?php

class boardIndexCalendar
{
	public $registry;
	
	public function __construct()
	{
        /* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->lang			=  $this->registry->getClass('class_localization');
	}
	
	public function getOutput()
	{
		/* Load language  */
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ) );
		
		/* Load calendar library */		
		require_once( IPSLib::getAppDir( 'calendar' ) .'/modules_public/calendar/calendars.php' );
		$cal = new public_calendar_calendar_calendars( $this->registry );
		$cal->makeRegistryShortcuts( $this->registry );
		
		/* Get current month and year */
		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->class_localization->getTimeOffset() ) );
		
		$this->now_date = array(
								 'year'    => $a[0],
								 'mon'     => $a[1],
							   );
		
		/* Setup language arrays */
		$cal->month_words = array( $this->lang->words['M_1'] , $this->lang->words['M_2'] , $this->lang->words['M_3'] ,
									$this->lang->words['M_4'] , $this->lang->words['M_5'] , $this->lang->words['M_6'] ,
									$this->lang->words['M_7'] , $this->lang->words['M_8'] , $this->lang->words['M_9'] ,
									$this->lang->words['M_10'], $this->lang->words['M_11'], $this->lang->words['M_12'] );
        
		if( ! $this->settings['ipb_calendar_mon'] )
		{
			$cal->day_words   = array( $this->lang->words['D_0'], $this->lang->words['D_1'], $this->lang->words['D_2'],
										$this->lang->words['D_3'], $this->lang->words['D_4'], $this->lang->words['D_5'],
										$this->lang->words['D_6'] );
		}
		else
		{
			$cal->day_words   = array( $this->lang->words['D_1'], $this->lang->words['D_2'], $this->lang->words['D_3'],
										$this->lang->words['D_4'], $this->lang->words['D_5'], $this->lang->words['D_6'],
										$this->lang->words['D_0'] );
		}		
		
		/* Return calendar */
		return "<div id='mini_calendars' class='calendar_wrap'>". $cal->getMiniCalendar( $this->now_date['mon'], $this->now_date['year'] ) . '</div><br />';
	}
}