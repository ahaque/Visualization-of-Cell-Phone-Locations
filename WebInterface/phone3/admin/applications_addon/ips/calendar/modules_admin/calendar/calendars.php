<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Calendar Management
 * Last Updated: $LastChangedDate: 2009-06-30 12:06:12 -0400 (Tue, 30 Jun 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 4829 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_calendar_calendar_calendars extends ipsCommand 
{
	/**
	 * Skin file
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;
	
	/**
	 * Main execution method
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html               = $this->registry->output->loadTemplate( 'cp_skin_calendar' );
		$this->html->form_code    = 'module=calendar&amp;section=calendars';
		$this->html->form_code_js = 'module=calendar&section=calendars';
		
		$this->lang->loadLanguageFile( array( 'admin_calendar' ) );
		
		switch( $this->request['do'] )
		{
			case 'calendar_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_delete' );
				$this->calendarDelete();
			break;
			
			case 'calendar_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarForm( 'new' );
			break;
			
			case 'calendar_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarSave( 'new' );
			break;
			
			case 'calendar_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarForm( 'edit' );
			break;
			
			case 'calendar_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarSave( 'edit' );
			break;
			
			case 'calendar_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarMove();
			break;
			
			case 'calendar_rebuildcache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarRebuildCache( 1 );
			break;
			
			case 'calendar_rss_cache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarRSSCache( intval( $this->request['cal_id'] ), 1 );
			break;
			
			case 'calendars_list':				
			default:
				$this->calendarsList();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Update the calendar position
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarMove()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax			= new classAjax();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['calendars']) AND count($this->request['calendars']) )
 		{
 			foreach( $this->request['calendars'] as $this_id )
 			{
 				$this->DB->update( 'cal_calendars', array( 'cal_position' => $position ), 'cal_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->calendarsRebuildCache();

 		$ajax->returnString( 'OK' );
 		exit();
	}	
	
	/**
	 * Delete a calendar
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarDelete()
	{
		/* INIT */
		$cal_id = intval( $this->request['cal_id'] );
		
		if ( ! $cal_id )
		{
			$this->registry->output->global_message = $this->lang->words['c_noid'];
			$this->components_list();
			return;
		}
		
		/* Delete Calendar Events */
		$this->DB->delete( 'cal_events', 'event_calendar_id= ' .$cal_id );
		
		/* Delete Calendar */
		$this->DB->delete( 'cal_calendars', 'cal_id=' . $cal_id );
		$this->DB->delete( 'permission_index', "app='calendar' AND perm_type='calendar' AND perm_type_id=" . $cal_id );
		
		/* Recache and bounce */
		/* Rebuild Caches and Bounce */
		$this->calendarsRebuildCache();
		$this->calendarRebuildCache( 0 );
		$this->calendarRSSCache( 0, 0 );
		
		$this->registry->output->global_message = $this->lang->words['c_removed'];
		$this->calendarsList();
	}		
	
	/**
	 * Handles the calednar new/edit form
	 *
	 * @access	public
	 * @param	string	$type	Either new or edit	 
	 * @return	void
	 */
	public function calendarSave( $type='new' )
	{
		/* INIT */
		$cal_id              = intval( $this->request['cal_id'] );
		$cal_title           = trim( IPSText::stripslashes( IPSText::htmlspecialchars( $_POST['cal_title'] ) ) );
		$cal_moderate        = intval( $this->request['cal_moderate'] );
		$cal_event_limit     = intval( $this->request['cal_event_limit'] );
		$cal_bday_limit      = intval( $this->request['cal_bday_limit'] );
		$cal_rss_export      = intval( $this->request['cal_rss_export'] );
		$cal_rss_export_days = intval( $this->request['cal_rss_export_days'] );
		$cal_rss_export_max  = intval( $this->request['cal_rss_export_max'] );
		$cal_rss_update      = intval( $this->request['cal_rss_update'] );
		$cal_perms			 = array( 'perm_read' => '', 'perm_post' => '', 'perm_nomod' => '' );
		
		/* Error Checks */
		if ( $type == 'edit' )
		{
			if ( ! $cal_id OR ! $cal_title )
			{
				$this->registry->output->global_message = $this->lang->words['c_noid'];
				$this->calendarsList();
				return;
			}
		}
		else
		{
			if ( ! $cal_title )
			{
				$this->registry->output->global_message = $this->lang->words['c_completeform'];
				$this->calendarForm( $type );
				return;
			}
		}

		/* DB Array */
		$array = array( 'cal_title'           => $cal_title,
						'cal_moderate'        => $cal_moderate,
						'cal_event_limit'     => $cal_event_limit,
						'cal_bday_limit'      => $cal_bday_limit,
						'cal_rss_export'      => $cal_rss_export,
						'cal_rss_export_days' => $cal_rss_export_days,
						'cal_rss_export_max'  => $cal_rss_export_max,
						'cal_rss_update'      => $cal_rss_update,
					 );
		 
		/* Create Calendar */
		if ( $type == 'new' )
		{
			$this->DB->insert( 'cal_calendars', $array );
			$cal_id = $this->DB->getInsertId();			
			$this->registry->output->global_message = $this->lang->words['c_added'];
		}
		/* Modify Calendar */
		else
		{
			$this->DB->update( 'cal_calendars', $array, 'cal_id='.$cal_id );
			$this->registry->output->global_message = $this->lang->words['c_edited'];
		}

		/* Permissions */
		require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
		$permissions = new classPublicPermissions( ipsRegistry::instance() );
		$permissions->savePermMatrix( $this->request['perms'], $cal_id, 'calendar' );		
		
		/* Rebuild Caches and Bounce */
		$this->calendarsRebuildCache();
		$this->calendarRebuildCache( 0 );
		$this->calendarRSSCache( $cal_id, 0 );
		$this->cache->rebuildCache( 'rss_output_cache' );
		
		$this->calendarsList();
	}	
	
	/**
	 * Add/Edit Calendar Form
	 *
	 * @access	public
	 * @param	string	$type	Either new or edit
	 * @return	void
	 */
	public function calendarForm( $type='new' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$cal_id             	= $this->request['cal_id'] ? intval( $this->request['cal_id'] ) : 0;
		$form               	= array();
		$form['perm_read']  	= "";
		$form['perm_post']  	= "";
		$form['perm_nomod'] 	= "";
		$form['perm_read_all'] 	= "";
		$form['perm_post_all']	= "";
		$form['perm_nomod_all']	= "";
		
		/* New Calendar */
		if ( $type == 'new' )
		{
			/* Form Bits */
			$formcode = 'calendar_add_do';
			$title    = $this->lang->words['c_addcal'];
			$button   = $this->lang->words['c_addcal'];
			
			/* Data */
			$calendar = array( 'perm_read'			=> '',
								'perm_post'			=> '',
								'perm_nomod'		=> '',
								'cal_title'			=> '',
								'cal_moderate'		=> '',
								'cal_event_limit'	=> '',
								'cal_bday_limit'	=> '',
								'cal_rss_export'	=> '',
								'cal_rss_update'	=> '',
								'cal_rss_export_days' => '',
								'cal_rss_export_max' => '',
								'cal_id'			=> 0 );
		}
		/* Edit Calendar */
		else
		{
			/* Data */
			$calendar = $this->DB->buildAndFetch( array( 
														'select'   => 'c.*', 
														'from'     => array( 'cal_calendars' => 'c' ), 
														'where'    => 'c.cal_id=' . $cal_id,
														'add_join' => array(
																			array(
																					'select' => 'p.*',
																					'from'   => array( 'permission_index' => 'p' ),
																					'where'  => "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																					'type'   => 'left',
																				)
															)
												)	 );

			if ( ! $calendar['cal_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_noid'];
				$this->calendarsList();
				return;
			}
			
			/* Form Bits */
			$formcode = 'calendar_edit_do';
			$title    = $this->lang->words['c_editbutton'].$calendar['cal_title'];
			$button   = $this->lang->words['c_savebutton'];
		}
		
		/* Permissions */
	   	require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
	   	$permissions         = new classPublicPermissions( ipsRegistry::instance() );
   		$form['perm_matrix'] = $permissions->adminPermMatrix( 'calendar', $calendar );		
		
		/* Form Elements */
		$form['cal_title']           = $this->registry->output->formInput(        'cal_title'           , IPSText::htmlspecialchars( ( isset( $_POST['cal_title'] ) AND $_POST['cal_title'] ) ? $_POST['cal_title'] : $calendar['cal_title'] ) );
		$form['cal_moderate']        = $this->registry->output->formYesNo(       'cal_moderate'        , ( isset( $this->request['cal_moderate'] ) 		AND $this->request['cal_moderate'] )         ? $this->request['cal_moderate']         : $calendar['cal_moderate'] );
		$form['cal_event_limit']     = $this->registry->output->formSimpleInput( 'cal_event_limit'     , ( isset( $this->request['cal_event_limit'] ) 	AND $this->request['cal_event_limit'] )      ? $this->request['cal_event_limit']      : $calendar['cal_event_limit'], 5 );
		$form['cal_bday_limit']      = $this->registry->output->formSimpleInput( 'cal_bday_limit'      , ( isset( $this->request['cal_bday_limit'] ) 		AND $this->request['cal_bday_limit'] )       ? $this->request['cal_bday_limit']       : $calendar['cal_bday_limit'], 5 );
		$form['cal_rss_export']      = $this->registry->output->formYesNo(       'cal_rss_export'      , ( isset( $this->request['cal_rss_export'] ) 		AND $this->request['cal_rss_export'] )       ? $this->request['cal_rss_export']       : $calendar['cal_rss_export'] );
		$form['cal_rss_update']      = $this->registry->output->formSimpleInput( 'cal_rss_update'      , ( isset( $this->request['cal_rss_update'] ) 		AND $this->request['cal_rss_update'] )       ? $this->request['cal_rss_update']       : $calendar['cal_rss_update'], 5 );
		$form['cal_rss_export_days'] = $this->registry->output->formSimpleInput( 'cal_rss_export_days' , ( isset( $this->request['cal_rss_export_days'] )	AND $this->request['cal_rss_export_days'] )  ? $this->request['cal_rss_export_days']  : $calendar['cal_rss_export_days'], 5 );
		$form['cal_rss_export_max']  = $this->registry->output->formSimpleInput( 'cal_rss_export_max'  , ( isset( $this->request['cal_rss_export_max'] ) 	AND $this->request['cal_rss_export_max'] )   ? $this->request['cal_rss_export_max']   : $calendar['cal_rss_export_max'], 5 );
		
		/* Output */
		$this->registry->output->html .= $this->html->calendarForm( $form, $title, $formcode, $button, $calendar );	
	}	
	
	/**
	 * List Calendars
	 *
	 * @access	public
	 * @return	void
	 */
	public function calendarsList()
	{
		/* INIT */
		$content     = "";
		$seen_count  = 0;
		$total_items = 0;
		$rows        = array();
		
		/* Query calendars */		
		$this->DB->build( array( 'select' => '*', 'from' => 'cal_calendars', 'order' => 'cal_position ASC' ) );
		$this->DB->execute();
		
		/* Get number of rows */
		$total_items = $this->DB->getTotalRows();
		
		/* Loop through the calendars */
		while( $r = $this->DB->fetch() )
		{
			$seen_count++;
			
			$rows[] = $r;				
		}
		
		/* Output */
		$this->registry->output->html           .= $this->html->calendarOverviewScreen( $rows );		
	}	
	
	/**
	 * Rebuild the RSS Cache
	 *
	 * @access	public
	 * @param	mixed	$calendar_id	Specify which calendar to rebuild, all is default
	 * @param	bool	$return			If set to 0, the cache will be output to the browser
	 * @return	mixed	void, or the RSS document
	 */
	public function calendarRSSCache( $calendar_id='all', $return=0 )
	{		
		/* INIT */
		$seenids   = array();
		$calevents = "";
		
		/* Calendar Class */
		require_once( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/calendars.php' );
		$calendar           =  new public_calendar_calendar_calendars();
		$calendar->makeRegistryShortcuts( $this->registry );

		/* RSS Class */
		require_once( IPS_KERNEL_PATH . 'classRss.php' );
		$class_rss              =  new classRss();
		
		$class_rss->use_sockets =  $this->use_sockets;
		$class_rss->doc_type    =  IPS_DOC_CHAR_SET;

		/* Reset the cache */
		$cache = array();

		/* Get the calendars */
		$this->DB->build( array( 
										'select'   => 'c.*', 
										'from'     => array( 'cal_calendars' => 'c' ), 
										'where'    => 'c.cal_rss_export_days > 0 AND c.cal_rss_export_max > 0 AND c.cal_rss_export=1',
										'add_join' => array(
															array(
																	'select' => 'p.*',
																	'from'   => array( 'permission_index' => 'p' ),
																	'where'  => "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																	'type'   => 'left',
																)
															)	
							) 	);
		$outer = $this->DB->execute();
		
		/* Loop through the calendars */
		while( $row = $this->DB->fetch( $outer ) )
		{
			//print_r($row);exit;
			/* Add Calendar to cache */
			if ( $row['cal_rss_export'] )
			{
				$cache[] = array( 'url' => $this->settings['board_url'].'/index.php?app=core&amp;module=global&amp;section=rss&amp;type=calendar&amp;id='.$row['cal_id'], 'title' => $row['cal_title'] );
			}
			
			/* Are we including events from this calendar? */
			if( $calendar_id == $row['cal_id'] OR $calendar_id == 'all' )
			{
				/* Create the RSS Channel */
				$channel_id = $class_rss->createNewChannel( array( 'title'       => $row['cal_title'],
																	 'link'        => $this->settings['board_url'].'/index.php?app=calendar&amp;module=calendar&amp;cal_id='.$row['cal_id'],
																	 'pubDate'     => $class_rss->formatDate( time() ),
																	 'ttl'         => $row['cal_rss_update'] * 60,
																	 'description' => $row['cal_title']
															)      );
				
				/* Check guest permissions */
				if( $row['perm_view'] == '*' OR preg_match( "/(^|,)".$this->settings['guest_group']."(,|$)/", $row['perm_view'] ) )
				{
					$pass = 1;
				}

				if ( ! $pass )
				{
					continue;
				}
				
				/* Sort out the dates */
				$row['cal_rss_export_days'] = intval ($row['cal_rss_export_days'] ) + 1;
				
				list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', time()) );
				
				$timenow   = gmmktime( 0,0,0, $month, 1, $year );
				$timethen  = time() + ( $row['cal_rss_export_days'] * 86400 ) + 86400;
				$nowtime   = time() - 86400;
				$items     = 0;
				
				/* Get Events */
				$calendar->calendarGetEventsSQL( 0, 0, array( 'timenow' => $timenow, 'timethen' => $timethen, 'cal_id' => $row['cal_id'] ), 0 );
				
				/* Check the events */
				for( $i = 0 ; $i <= $row['cal_rss_export_days'] ; $i++ )
				{
					/* Get the month, day, and year */
					list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', $nowtime) );
					
					/* Get the events for this day */
					$eventcache = $calendar->calendarGetDayEvents( $month, $day, $year );
					
					/* Loop through the events */
					foreach( $eventcache as $event )
					{ 
						if ( ! in_array( $event['event_id'], $seenids ) )
						{
							/* Reached the max? */
							if ( $row['cal_rss_export_max'] <= $items )
							{
								break;
							}
							
							/* Get the event info */
							if ( $calendar->getInfoEvents( $event, $month, $day, $year, 0 ) )
							{
								/* Approved */
								if ( ! $event['event_approved'] )
								{
									continue;
								}
								
								/* Check Private */
								if ( $event['event_private'] )
								{
									continue;
								}
								
								/* Check Permis */
								if ( $event['event_perms'] != '*' AND ! preg_match( "/(^|,)".$this->settings['guest_group']."(,|$)/", $event['event_perms'] ) )
								{
									continue;
								}
								
								/* Get Dates */
								list( $m , $d , $y  ) = explode( ",", gmdate('n,j,Y', $event['event_unix_from']  ) );
								list( $m1, $d1, $y1 ) = explode( ",", gmdate('n,j,Y', $event['event_unix_to']   ) );
								
								$event['_from_month'] = $m;
								$event['_from_day']   = $d;
								$event['_from_year']  = $y;
								$event['_to_month']   = $m1;
								$event['_to_day']     = $d1;
								$event['_to_year']    = $y1;

								//-----------------------------------------
								// Coming from RSS rebuild?
								//-----------------------------------------
								
								if( !$this->html )
								{
									/* Load Skin and Lang */
									$this->html               = $this->registry->output->getTemplate( 'calendar' );
									
									$this->lang->loadLanguageFile( array( 'admin_calendar' ) );
								}
								
								//-----------------------------------------
								// Parse bbcode
								//-----------------------------------------
								
								IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
								IPSText::getTextClass( 'bbcode' )->parse_smilies			= intval( $event['event_smilies'] );
								IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
								IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';
								IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $event['member_group_id'];
								IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $event['mgroup_others'];
						
								$event['event_content'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $event['event_content'] );

								if ( $event['recurring'] )
								{
									$event['event_content'] = $this->html->calendar_rss_recurring( $event );
								}
								else if ( $event['single'] )
								{
									$event['event_content'] = $this->html->calendar_rss_single( $event );
								}
								else
								{
									$event['event_content'] = $this->html->calendar_rss_range( $event );
								}
								
								$event['event_unix_from'] = $event['event_tz'] ? $event['event_unix_from'] : $event['event_unix_from'] + ( $this->settings['time_offset'] * 3600 );
								
								/* Add the item to our channel */
								$class_rss->addItemToChannel( $channel_id, array( 'title'           => $event['event_title'],
																				 'link'            => $this->settings['board_url'].'/index.php?app=calendar&amp;module=calendar&amp;do=showevent&amp;cal_id='.$row['cal_id'].'&amp;event_id='.$event['event_id'],
																				 'description'     => $event['event_content'],
																				 'pubDate'	       => $class_rss->formatDate( $event['event_unix_from'] ),
																				 'guid'            => $event['event_id']
														  )                    );
											
										}
							
							/* Increment */
							$seenids[ $event['event_id'] ] = $event['event_id'];
							$items++;
						}
					}
					
					$nowtime += 86400;
				}

				/* Create the RSS Document */
				$class_rss->createRssDocument();
			
				/* Update the cache */
				$this->DB->update( 'cal_calendars', array( 'cal_rss_update_last' => time(), 'cal_rss_cache' => $class_rss->rss_document ), 'cal_id='.$row['cal_id'] );
			}
		}
		
		/* Update Cache */
		$this->cache->setCache( 'rss_calendar', $cache, array( 'name' => 'rss_calendar', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		/* Return */
		if ( $return )
		{
			$this->registry->output->global_message = $this->lang->words['c_rssrecached'];
			$this->calendarsList();
			return;
		}
		/* Print */
		else
		{
			return $class_rss->rss_document;
		}
	}
	
	/**
	 * Rebuild Calendar Cache
	 *
	 * @access	public
	 * @param	bool		Whether to return or not
	 * @return	void
	 */
	public function calendarRebuildCache( $return=0 )
	{
		/* INIT */
		$this->settings['calendar_limit'] = intval( $this->settings['calendar_limit'] ) < 2 ? 1 : intval( $this->settings['calendar_limit'] );

		//--------------------------------------------
		// Grab an extra day for the TZ diff
		//--------------------------------------------
		
		$this->settings['calendar_limit']++;
		
		list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', time() ) );
				
		$timenow   = gmmktime( 0,0,0, $month, 1, $year );
		$timethen  = time() + (intval($this->settings['calendar_limit']) * 86400);
		$seenids   = array();
		$nowtime   = time() - 86400;
		$birthdays = array();
		$calevents = array();
		$calendars = array();
		
		$a           = explode( ',', gmdate( 'Y,n,j,G,i,s', time() ) );
		$day         = $a[2];
		$month       = $a[1];
		$year        = $a[0];
		$daysinmonth = date( 't', time() );
		
		//-----------------------------------------
		// Get 24hr before and 24hr after to make
		// sure we don't break any timezones
		//-----------------------------------------
		
		$last_day   = $day - 1;
		$last_month = $month;
		$last_year  = $year;
		$next_day   = $day + 1;
		$next_month = $month;
		$next_year  = $year;
		
		//-----------------------------------------
		// Calculate dates..
		//-----------------------------------------
		
		if ( $last_day == 0 )
		{
			$last_month -= 1;
			$last_day   = gmdate( 't', time() );
		}
		
		if ( $last_month == 0 )
		{
			$last_month = 12;
			$last_year  -= 1;
		}
		
		if ( $next_day > gmdate( 't', time() ) )
		{
			$next_month += 1;
			$next_day   = 1;
		}
		
		if ( $next_month == 13 )
		{
			$next_month = 1;
			$next_year += 1;
		}
		
		//--------------------------------------------
		// Get classes
		//--------------------------------------------
		
		require_once( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/calendars.php' );
		$calendar = new public_calendar_calendar_calendars( $this->registry );
		$calendar->makeRegistryShortcuts( $this->registry );
				
		//--------------------------------------------
		// Get stuff
		//--------------------------------------------
		
		$this->DB->build( array( 
										'select'   => 'c.*', 
										'from'     => array( 'cal_calendars' => 'c' ), 
										'order'    => 'c.cal_position ASC',
										'add_join' => array(
															array(
																	'select' => 'p.*',
																	'from'   => array( 'permission_index' => 'p' ),
																	'where'  => "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																	'type'   => 'left',
																)
															)
								)	 );		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_perm_read'] = $perms['perm_view'];
			
			$calendars[ $row['cal_id'] ] = $row;
		}
		
		$calendar->calendarGetEventsSQL( 0, 0, array('timenow' => $timenow, 'timethen' => $timethen ) );

		//--------------------------------------------
		// OK.. Go through days and check events
		//--------------------------------------------
		
		for( $i = 0 ; $i <= $this->settings['calendar_limit'] ; $i++ )
		{
			list( $_month, $tday, $year ) = explode( ',', gmdate('n,j,Y', $nowtime) );
			
			$eventcache = $calendar->calendarGetDayEvents( $_month, $tday, $year );
			
			foreach( $eventcache as $event )
			{
				if ( ! in_array( $event['event_id'], $seenids ) )
				{ 
					if ( $calendar->getInfoEvents( $event, $_month, $tday, $year, 0 ) )
					{
						if ( ! $event['event_approved'] )
						{
							continue;
						}
						
						unset( $event['event_content'], $event['event_smilies'] );
						
						$event['_perm_read']             = $calendars[ $event['event_calendar_id'] ]['perm_view'];
						$calevents[ $event['event_id'] ] = $event;
					}
					
					$seenids[ $event['event_id'] ] = $event['event_id'];
				}
			}
			
			$nowtime += 86400;
		}
 
		//-----------------------------------------
		// Grab birthdays
		//-----------------------------------------
		
		$append_string = "";
		
        if( ! date("L") )
        {
	        if( $month == "2" AND ( $day == "28" OR $day == "27" ) )
	        {
		        $append_string = " or( bday_month=2 AND bday_day=29 )";
	        }
		}

		$this->DB->build( array( 
								'select' => 'member_id, members_seo_name, members_display_name, member_group_id, bday_day, bday_month, bday_year',
								'from'   => 'members',
								'where'  => "( bday_day=$last_day AND bday_month=$last_month )
											 or ( bday_day=$day AND bday_month=$month )
											 or ( bday_day=$next_day AND bday_month=$next_month ) {$append_string}"
							)	);
							 
		$this->DB->execute();
		
		$birthdays = array();
		
		while( $r = $this->DB->fetch() )
		{
			$birthdays[ $r['member_id'] ] = $r;
		}
		
		//--------------------------------------------
		// Update calendar array
		//--------------------------------------------

		$this->cache->setCache( 'calendar' , $calevents, array( 'array' => 1, 'deletefirst' => 1 ) );
		$this->cache->setCache( 'birthdays', $birthdays, array( 'array' => 1, 'deletefirst' => 1 ) );

		if ( $return )
		{
			$this->registry->output->global_message = $this->lang->words['c_recached'];
			$this->calendarsList();
		}
	}
	
	/**
	 * Builds a cache of the current calendars
	 *
	 * @access	public
	 * @return	bool	Returns true
	 */
	public function calendarsRebuildCache()
	{
		/* INI */
		$cache = array();
			
		/* Query Calenar with permissions */
		$this->DB->build( array( 
										'select'   => 'c.*', 
										'from'     => array( 'cal_calendars' => 'c' ), 
										'order'    => 'c.cal_position ASC',
										'add_join' => array(
															array(
																	'select' => 'p.*',
																	'from'   => array( 'permission_index' => 'p' ),
																	'where'  => "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																	'type'   => 'left',
																)
															)
								)	 );		
		$this->DB->execute();
		
		/* Add to cache */
		while( $r = $this->DB->fetch() )
		{
			$cache[ $r['cal_id'] ] = $r;
		}
		
		/* Save */
		$this->cache->setCache( 'calendars', $cache, array( 'name' => 'calendars', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		return TRUE;
	}
}