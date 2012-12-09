<?php

/*
+--------------------------------------------------------------------------
|   IP.Board v3.0.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.
|   ========================================
|   Web: http://www.
|   Email: matt@
|   Licence Info: http://www./?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.0.2 -> IPB 2.0.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
		
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'sql':
				$this->upgrade_sql(1);
				break;
			case 'sql1':
				$this->upgrade_sql(1);
				break;
			case 'sql2':
				$this->upgrade_sql(2);
				break;
			case 'sql3':
				$this->upgrade_sql(3);
				break;
			case 'sql4':
				$this->upgrade_sql(4);
				break;
			case 'polls':
				$this->convert_polls();
				break;
			case 'calevents':
				$this->convert_calevents();
				break;
			case 'skin':
				$this->add_skin();
				break;				
			
			default:
				$this->upgrade_sql(1);
				break;
		}
		
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// SQL: 0
	/*-------------------------------------------------------------------------*/
	
	function upgrade_sql( $id=1 )
	{
		$man     = 0; // Manual upgrade ? intval( $this->install->ipsclass->input['man'] );
		$cnt     = 0;
		$SQL     = array();
		$file    = '_updates_'.$id.'.php';
		$output  = "";
		$path    = IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_21003/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix  = $this->registry->dbFunctions()->getPrefix();
	
		if ( file_exists( $path ) )
		{
			require( $path );
		
			$this->error   = array();
			$this->sqlcount 		= 0;
			$output					= "";
			
			$this->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';
				
				$query = str_replace( "<%time%>", time(), $query );
							
				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
				
				if ( IPSSetUp::getSavedData('man') )
				{
					$output .= preg_replace( "/\s{1,}/", " ", $query ) . "\n\n";
				}
				else
				{			
					$this->DB->query( $query );
					
					if ( $this->DB->error )
					{
						$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
					}
					else
					{
						$this->sqlcount++;
					}
				}
			}
		
			$this->registry->output->addMessage("$this->sqlcount queries run....");
		}
		
		//--------------------------------
		// Next page...
		//--------------------------------
		
		$this->request['st'] = 0;
		
		if ( $id != 4 )
		{
			$nextid = $id + 1;
			$this->request['workact'] = 'sql'.$nextid;	
		}
		else
		{
			$this->request['workact'] = 'polls';	
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}	
	}	
	
	
	/*-------------------------------------------------------------------------*/
	// POLLS
	/*-------------------------------------------------------------------------*/
	
	function convert_polls()
	{
		$start     = intval($this->request['st']) > 0 ? intval($this->request['st']) : 0;
		$lend      = 50;
		$end       = $start + $lend;
		$max       = intval(IPSSetUp::getSavedData('max'));
		$converted = intval(IPSSetUp::getSavedData('conv'));
		
		//-----------------------------------------
		// First off.. grab number of polls to convert
		//-----------------------------------------
		
		if ( ! $max )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as max',
																	   'from'   => 'topics',
																	   'where'  => "poll_state IN ('open', 'close', 'closed')" ) );
																	   
			$max   = $total['max'];
		}
		
		//-----------------------------------------
		// In steps...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
													  'from'   => 'topics',
													  'where'  => "poll_state IN ('open', 'close', 'closed' )",
													  'limit'  => array( 0, $lend ) ) );
		$o = $this->DB->execute();
	
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		if ( $this->DB->getTotalRows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------
			
			while ( $r = $this->DB->fetch($o) )
			{
				$converted++;
				
				//-----------------------------------------
				// All done?
				//-----------------------------------------
				
				if ( $converted >= $max )
				{
					$done = 1;
				}				
				
				$new_poll  = array( 1 => array() );
				
				$poll_data = $this->DB->buildAndFetch( array( 'select' => '*',
																			   'from'   => 'polls',
																			   'where'  => "tid=".$r['tid']
																	  )      );
				if ( ! $poll_data['pid'] )
				{
					continue;
				}
				
				if ( ! $poll_data['poll_question'] )
				{
					$poll_data['poll_question'] = $r['title'];
				}
				
				//-----------------------------------------
				// Kick start new poll
				//-----------------------------------------
				
				$new_poll[1]['question'] = $poll_data['poll_question'];
        
				//-----------------------------------------
				// Get OLD polls
				//-----------------------------------------
				
				$poll_answers = unserialize( stripslashes( $poll_data['choices'] ) );
        	
				reset($poll_answers);
				
				foreach ( $poll_answers as $entry )
				{
					$id     = $entry[0];
					$choice = $entry[1];
					$votes  = $entry[2];
					
					$total_votes += $votes;
					
					if ( strlen($choice) < 1 )
					{
						continue;
					}
					
					$new_poll[ 1 ]['choice'][ $id ] = $choice;
					$new_poll[ 1 ]['votes'][ $id  ] = $votes;
				}
				
				//-----------------------------------------
				// Got something?
				//-----------------------------------------
				
				if ( count( $new_poll[1]['choice'] ) )
				{
					$this->DB->update( 'polls' , array( 'choices'    => serialize( $new_poll ) ), 'tid='.$r['tid'] );
					$this->DB->update( 'topics', array( 'poll_state' => 1 ), 'tid='.$r['tid'] );
				}
			}
		}
		else
		{
			$done = 1;
		}
		
		
		if ( ! $done )
		{
			$this->registry->output->addMessage("Polls: $start to $end completed....");
			$this->request['workact'] 	= 'polls';	
			$this->request['st'] 		= $end;
			IPSSetUp::setSavedData('max', $max);
			IPSSetUp::setSavedData('conv', $converted);
			return FALSE;			
		}
		else
		{
			$this->registry->output->addMessage("Polls converted, proceeding to calendar events...");
			$this->request['workact'] 	= 'calevents';	
			$this->request['st'] 		= '0';	
			return FALSE;						
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// CALENDAR EVENTS
	/*-------------------------------------------------------------------------*/
	
	function convert_calevents()
	{
		$start     = intval($this->request['st']) > 0 ? intval($this->request['st']) : 0;
		$lend      = 50;
		$end       = $start + $lend;
	
		//-----------------------------------------
		// In steps...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
													  'from'   => 'calendar_events',
													  'limit'  => array( $start, $lend ) ) );
		$o = $this->DB->execute();
	
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		if ( $this->DB->getTotalRows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------
			
			while ( $r = $this->DB->fetch($o) )
			{
				$recur_remap = array( 'w' => 1,
									  'm' => 2,
									  'y' => 3 );
				
				$begin_date        = IPSTime::date_getgmdate( $r['unix_stamp']     );
				$end_date          = IPSTime::date_getgmdate( $r['end_unix_stamp'] );
				
				if ( ! $begin_date OR ! $end_date )
				{
					continue;
				}
				
				$day               = $begin_date['mday'];
				$month             = $begin_date['mon'];
				$year              = $begin_date['year'];
				
				$end_day           = $end_date['mday'];
				$end_month         = $end_date['mon'];
				$end_year          = $end_date['year'];
		
				$_final_unix_from  = gmmktime(0, 0, 0, $month, $day, $year );
				
				//-----------------------------------------
				// Recur or ranged...
				//-----------------------------------------
				
				if ( $r['event_repeat'] OR $r['event_ranged'] )
				{
					$_final_unix_to = gmmktime(11, 59, 59, $end_month, $end_day, $end_year);
				}
				else
				{
					$_final_unix_to = 0;
				}
				
				$new_event = array( 'event_calendar_id' => 1,
									'event_member_id'   => $r['userid'],
									'event_content'     => $r['event_text'],
									'event_title'       => $r['title'],
									'event_smilies'     => $r['show_emoticons'],
									'event_perms'       => $r['read_perms'],
									'event_private'     => $r['priv_event'],
									'event_approved'    => 1,
									'event_unixstamp'   => $r['unix_stamp'],
									'event_recurring'   => ( $r['event_repeat'] && $recur_remap[ $r['repeat_unit'] ] ) ? $recur_remap[ $r['repeat_unit'] ] : 0,
									'event_tz'          => 0,
									'event_unix_from'   => $_final_unix_from,
									'event_unix_to'     => $_final_unix_to );
				
				//-----------------------------------------
				// INSERT
				//-----------------------------------------
				
				$this->DB->insert( 'cal_events', $new_event );
			}
			
			$this->registry->output->addMessage("Calendar events: $start to $end completed....");
			$this->request['workact'] 	= 'calevents';	
			$this->request['st'] 		= $end;
			return FALSE;		
		}
		else
		{
			$this->registry->output->addMessage("Calendar events converted,  Creating new IPB 2.1 skin...");
			$this->request['workact'] 	= 'skin';	
			return FALSE;		
		}
	}
		
		
	
	/*-------------------------------------------------------------------------*/
	// CALENDAR EVENTS
	/*-------------------------------------------------------------------------*/
	
	function add_skin()
	{
		$this->registry->output->addMessage("Skipping 2.1 skin creation (latest skin will be inserted later)...");
		unset($this->request['workact']);
		return TRUE;
	}
	



	
}
	
	
?>