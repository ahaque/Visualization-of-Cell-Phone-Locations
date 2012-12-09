<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum Statistics
 * Last Updated: $Date: 2009-08-18 08:08:46 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Rev: 5026 $
 */


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_statistics_stats extends ipsCommand
{
	/**
	* Array of Month Names
	*
	* @access	private
	* @var		array
	*/		
	private $month_names;
	
	/**
	* Skin object
	*
	* @access	private
	* @var		object			Skin templates
	*/	
	private $html;	
	
	/**
	* Main class entry point
	*
	* @access	public
	* @param	object		ipsRegistry reference
	* @return	void		[Outputs to screen]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		/* Load HTML and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_stats' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_stats' ) );

		/* URLs */
		$this->form_code	= $this->html->form_code	= 'module=statistics&amp;section=stats';
		$this->form_code_js	= $this->html->form_code_js	= 'module=statistics&section=stats';		
		
		/* Setup the month name array */
		$this->month_names = array( 1	=> $this->lang->words['stats_jan'], 
									2	=> $this->lang->words['stats_feb'], 
									3	=> $this->lang->words['stats_mar'], 
									4	=> $this->lang->words['stats_apr'],
									5	=> $this->lang->words['stats_may'], 
									6	=> $this->lang->words['stats_jun'], 
									7	=> $this->lang->words['stats_jul'], 
									8	=> $this->lang->words['stats_aug'], 
									9	=> $this->lang->words['stats_sep'], 
									10	=> $this->lang->words['stats_oct'], 
									11	=> $this->lang->words['stats_nov'], 
									12	=> $this->lang->words['stats_dec']
								  );
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'show_reg':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_registration' );
				$this->result_screen( 'reg' );
			break;
				
			case 'show_topic':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_topics' );
				$this->result_screen( 'topic' );
			break;
			
			case 'topic':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_topics' );
				$this->statsMainScreen( 'topic' );
			break;
			
			//-----------------------------------------
			
			case 'show_post':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_posts' );
				$this->result_screen( 'post' );
			break;
					
			case 'post':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_posts' );
				$this->statsMainScreen( 'post' );
			break;
			
			//-----------------------------------------
			
			case 'show_msg':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_msg' );
				$this->result_screen( 'msg' );
			break;
					
			case 'msg':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_msg' );
				$this->statsMainScreen( 'msg' );
			break;
				
			//-----------------------------------------
			
			case 'statsShowTopicViews':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_views' );
				$this->statsShowTopicViews();
			break;
					
			case 'views':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_views' );
				$this->statsMainScreen( 'views' );
			break;
			
			//-----------------------------------------
			
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_registration' );
				$this->statsMainScreen( 'reg' );
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Display statistics for the selected mode
	 *
	 * @access	public
	 * @param	string	Type of stat screen reg, topic, post, msg	 
	 * @return	void
	 **/
	public function result_screen($mode='reg')
	{
		/* Check the to fields */
		if ( ! checkdate( $this->request['to_month'], $this->request['to_day'], $this->request['to_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_toincorrect'], 11352 );
		}
		
		/* Check the from fields */
		if ( ! checkdate( $this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_fromincorrect'], 11353 );
		}
		
		/* Create time stamps */
		$to_time   = mktime(12 ,0 ,0 ,$this->request['to_month']   ,$this->request['to_day']   ,$this->request['to_year']  );
		$from_time = mktime(12 ,0 ,0 ,$this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year']);
		
		/* Get Human Dates */
		$human_to_date   = getdate( $to_time );
		$human_from_date = getdate( $from_time );
		
		/* Setup based on mode */
		switch( $mode )
		{
			case 'reg':
				$table     = $this->lang->words['stats_reg'];
				
				$sql_table = 'members';
				$sql_field = 'joined';

				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_reg_nav'] );			
			break;
			
			case 'topic':
				$table     = $this->lang->words['stats_topic'];
				
				$sql_table = 'topics';
				$sql_field = 'start_date';

				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_topic_nav'] );			
			break;
			
			case 'post':
				$table     = $this->lang->words['stats_post'];
				
				$sql_table = 'posts';
				$sql_field = 'post_date';

				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_post_msg'] );			
			break;
			
			case 'msg':
				$table     = $this->lang->words['stats_msg'];
				
				$sql_table = 'message_topics';
				$sql_field = 'mt_date';

				$this->registry->output->extra_nav[] = array( '', $this->lang->words['stats_msg_nav'] );			
			break;
		}

		/* Setup Timescale */
	  	switch( $this->request['timescale'] )
	  	{
	  		case 'daily':
	  			$sql_date = "%j";
		  		$php_date = "F jS - Y";
		  		break;
		  		
		  	case 'monthly':
		  		$sql_date = "%m %Y";
		  	    $php_date = "F Y";
		  	    break;
		  	    
		  	default:
		  		// weekly
		  		$sql_date = "%U %Y";
		  		$php_date = " [F Y]";
		  		break;
		}
		
		/* Table Title */
		$title       = $this->lang->words[ 'timescale_' . $this->request['timescale'] ] ." {$table} ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} {$this->lang->words['stats_to']} {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})";

		/* Query the stats */
		$this->DB->build( array(
										'select' => "MIN( {$sql_field} ) as result_maxdate, COUNT(*) as result_count, {$this->DB->buildDateFormat( $this->DB->buildFromUnixtime( $sql_field ), $sql_date )} as result_time",
										'from'	 => $sql_table,
										'where'	 => $sql_field . ' > ' . $from_time . ' AND ' . $sql_field . ' < ' . $to_time,
										'group'	 => $this->DB->buildDateFormat( $this->DB->buildFromUnixtime( $sql_field ), $sql_date ) . ", $sql_field",
										'order'	 => $sql_field . ' ' . $this->request['sortby'],
							)		);
							
						
		$this->DB->execute();
		
		/* Loop through the results */
		$running_total = 0;
		$max_result    = 0;
		$results       = array();
	
		while( $row = $this->DB->fetch() )
		{	
			if( $row['result_count'] >  $max_result )
			{
				$max_result = $row['result_count'];
			}
				
			$running_total += $row['result_count'];
			
			$results[ date( $php_date, $row['result_maxdate'] ) ] = array(
								 											 'result_maxdate'  => $row['result_maxdate'],
																			 'result_count'    => $row['result_count'],
																			 'result_time'     => $row['result_time'],
																		  );
								  
		}
		
		/* Build the output rows */
		$rows = array();
			
		foreach( $results as $data )
		{
			/* Width of the bar */				
    		$data['_width'] = intval( ( $data['result_count'] / $max_result ) * 100 - 8 );
    			
    		if( $img_width < 1 )
    		{
    			$img_width = 1;
    		}
    			
    		$img_width .= '%';
    		
			/* Format Date */
    		if( $this->request['timescale'] == 'weekly' )
    		{
    			$data['_name'] = $this->lang->words['stats_weekno'] . strftime( "%W", $data['result_maxdate'] ) . date( $php_date, $data['result_maxdate'] );
    		}
    		else
    		{
    			$data['_name'] = date( $php_date, $data['result_maxdate'] );
    		}
    		
    		$rows[] = $data;
		}
		
		/* Output */
		$this->registry->output->html	.= $this->html->statResultsScreen( $title, $rows, $running_total );
	}	
	
	/**
	 * Date Selection Screen
	 *
	 * @access	public
	 * @param	string	Type of stat screen reg, topic, post, msg, views
	 * @return	void
	 **/
	public function statsMainScreen( $mode='reg' )
	{
		/* Setup this mode */
		switch( $mode )
		{
			case 'reg':
				$form_code = 'show_reg';
				$table     = $this->lang->words['stats_reg'];
			break;
			
			case 'topic':
				$form_code = 'show_topic';
				$table     = $this->lang->words['stats_topic'];
			break;
			
			case 'post':
				$form_code = 'show_post';
				$table     = $this->lang->words['stats_post'];
			break;
			
			case 'msg':
				$form_code = 'show_msg';
				$table     = $this->lang->words['stats_msg'];
			break;
			
			case 'views':
			default:
				$form_code = 'statsShowTopicViews';
				$table     = $this->lang->words['stats_views'];
			break;			
		}

		/* Setup Dates */
		$old_date = getdate( time() - ( 3600 * 24 * 90 ) );
		$new_date = getdate( time() + ( 3600 * 24 ) );

		/* Form Elements */
		$form = array();

		$form['from_month'] = $this->registry->output->formDropdown( "from_month", $this->statsMakeMonth(), $old_date['mon']  );
		$form['from_day']   = $this->registry->output->formDropdown( "from_day"  , $this->statsMakeDay()  , $old_date['mday'] );
		$form['from_year']  = $this->registry->output->formDropdown( "from_year" , $this->statsMakeYear() , $old_date['year'] );
		$form['to_month']   = $this->registry->output->formDropdown( "to_month"  , $this->statsMakeMonth(), $new_date['mon']  );
		$form['to_day']     = $this->registry->output->formDropdown( "to_day"    , $this->statsMakeDay()  , $new_date['mday'] );
		$form['to_year']    = $this->registry->output->formDropdown( "to_year"   , $this->statsMakeYear() , $new_date['year'] );
		$form['timescale']  = $this->registry->output->formDropdown( "timescale" , array( 0 => array( 'daily', $this->lang->words['stats_daily']), 1 => array( 'weekly', $this->lang->words['stats_weekly'] ), 2 => array( 'monthly', $this->lang->words['stats_monthly'] ) ) );
		$form['sortby']     = $this->registry->output->formDropdown( "sortby"    , array( 0 => array( 'asc', $this->lang->words['stats_asc']), 1 => array( 'desc', $this->lang->words['stats_desc'] ) ), 'desc' );
									     									     
		/* Output */
		$this->registry->output->html           .= $this->html->statMainScreeen( $form_code, $table, $form );
	}	

	/**
	 * Show topic view stats
	 *
	 * @access	public
	 * @return	void
	 **/
	public function statsShowTopicViews()
	{
		/* Check the to fields */
		if ( ! checkdate( $this->request['to_month'], $this->request['to_day'], $this->request['to_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_toincorrect'], 11354 );
		}
		
		/* Check the from fields */
		if ( ! checkdate( $this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year'] ) )
		{
			$this->registry->output->showError( $this->lang->words['stats_fromincorrect'], 11355 );
		}
		
		/* Create time stamps */
		$to_time   = mktime(0 ,0 ,0 ,$this->request['to_month']   ,$this->request['to_day']   ,$this->request['to_year']  );
		$from_time = mktime(0 ,0 ,0 ,$this->request['from_month'] ,$this->request['from_day'] ,$this->request['from_year']);
		
		/* Get Human Dates */
		$human_to_date   = getdate( $to_time );
		$human_from_date = getdate( $from_time );
		
		/* Title */
		$title = "{$this->lang->words['stats_views_nav']} ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} {$this->lang->words['stats_to']} {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})";
		
		/* Query the topic stats */
		$this->DB->build( array( 
										'select'    => 'SUM(t.views) as result_count, t.forum_id',
										'from'	    => array( 'topics' => 't' ),
										'where'	    => "t.start_date > {$from_time} AND t.start_date < {$to_time}",
										'group'	    => 't.forum_id',
										'order'	    => 'result_count ' . $this->request['sortby'],
										'add_join'	=> array(
															array( 
																	'select' => 'f.name as result_name',
																	'from'	 => array( 'forums' => 'f' ),
																	'where'	 => 'f.id=t.forum_id',
																	'type'	 => 'left'
																)
															)
							)		);
		$this->DB->execute();
		
		/* Loop through the results */
		$running_total = 0;
		$max_result    = 0;
		$results       = array();
	
		while( $row = $this->DB->fetch() )
		{	
			if( $row['result_count'] >  $max_result )
			{
				$max_result = $row['result_count'];
			}
				
			$running_total += $row['result_count'];
			
			$results[] = array(
								 'result_maxdate'  => $row['result_maxdate'],
								 'result_count'    => $row['result_count'],
								 'result_time'     => $row['result_time'],
								 'result_name'     => $row['result_name'],								 
							  );
								  
		}
		
		/* Build the output rows */
		$rows = array();
			
		foreach( $results as $data )
		{
			/* Width of the bar */				
    		$data['_width'] = intval( ( $data['result_count'] / $max_result ) * 100 - 8 );
    			
    		if( $img_width < 1 )
    		{
    			$img_width = 1;
    		}
    			
    		$img_width .= '%';
    		
    		/* Title */
    		$data['_name'] = $data['result_name'];
    		
    		$rows[] = $data;
		}
		
		/* Output */
		$this->registry->output->extra_nav[]     = array( '', 'Topic Views' );
		$this->registry->output->html           .= $this->html->statResultsScreen( $title, $rows, $running_total );
	}
	
	/**
	 * Create the drop down options for the year select
	 *
	 * @access	public
	 * @return	array
	 **/
	public function statsMakeYear()
	{
		$time_now = getdate();
		
		$return = array();
		
		$start_year = 2002;
		
		$latest_year = intval($time_now['year']);
		
		if ($latest_year == $start_year)
		{
			$start_year -= 1;
		}
		
		for ( $y = $start_year; $y <= $latest_year; $y++ )
		{
			$return[] = array( $y, $y);
		}
		
		return $return;
	}
	
	/**
	 * Create the drop down options for the month select
	 *
	 * @access	public
	 * @return	array
	 **/
	public function statsMakeMonth()
	{
		$return = array();
		
		for ( $m = 1 ; $m <= 12; $m++ )
		{
			$return[] = array( $m, $this->month_names[$m] );
		}
		
		return $return;
	}
	
	/**
	 * Create the drop down options for the day select
	 *
	 * @access	public
	 * @return	array
	 **/
	public function statsMakeDay()
	{
		$return = array();
		
		for ( $d = 1 ; $d <= 31; $d++ )
		{
			$return[] = array( $d, $d );
		}
		
		return $return;
	}
	
	
		
}