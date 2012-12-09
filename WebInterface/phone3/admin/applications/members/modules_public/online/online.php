<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Online list
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		12th March 2002
 * @version		$Revision: 5041 $
 *
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_online_online extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;

	/**
	 * DB result start point
	 *
	 * @access	public
	 * @var		integer
	 */
	public $first			= 0;
	
	/**
	 * DB number of rows to pull
	 *
	 * @access	public
	 * @var		integer
	 */
	public $perpage			= 25;

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
    	// Are we allowed to see the online list?
    	//-----------------------------------------
    	
    	if ( !$this->settings['allow_online_list'] )
    	{
    		$this->registry->output->showError( 'onlinelist_disabled', 10230 );
    	}
    	
    	//-----------------------------------------
    	// Init, lang, html
    	//-----------------------------------------
    	
    	$this->request['st'] = intval( $this->request['st'] >= 0 ? intval($this->request['st']) : 0 );
		$this->registry->class_localization->loadLanguageFile( array( 'public_online' ), 'members' );

    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
    		case 'listall':
    		default:
    			$this->_listAll();
    		break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->registry->output->addContent( $this->output );
    	$this->registry->output->setTitle( $this->lang->words['page_title'] );
    	$this->registry->output->addNavigation( $this->lang->words['page_title'], '' );
        $this->registry->output->sendOutput();
 	}
 	
 	
	/**
	 * Show the online list
	 *
	 * @access	private
	 * @return	void		[Stores HTML in $this->output]
	 */
	private function _listAll()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->first	= intval($this->request['st']) > 0 ? intval($this->request['st']) : 0;
		$final			= array();
		$modules		= array();
		$our_mgroups	= array();
		$memberIDs		= array();
		
		if ( !$this->settings['au_cutoff'] )
		{
			$this->settings[ 'au_cutoff'] =  15 ;
		}

		$defaults		= array(
								'show_mem'		=> ( $this->request['show_mem'] AND in_array( $this->request['show_mem'], array( 'reg', 'guest', 'all' ) ) ) ? $this->request['show_mem'] : 'all',
								'sort_order'	=> ( $this->request['sort_order'] AND in_array( $this->request['sort_order'], array( 'desc', 'asc' ) ) ) ? $this->request['sort_order'] : 'asc',
								'sort_key'		=> ( $this->request['sort_key'] AND in_array( $this->request['sort_key'], array( 'click', 'name' ) ) ) ? $this->request['sort_key'] : 'click',
								);
		
		//-----------------------------------------
		// Get member groups
		//-----------------------------------------

		if( $this->memberData['mgroup_others'] )
		{
			$our_mgroups = explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
		}
		
		$our_mgroups[] = $this->memberData['member_group_id'];
			
		//-----------------------------------------
		// Sort the db query
		//-----------------------------------------
		
		$cut_off  = $this->settings['au_cutoff'] * 60;
		$t_time   = time() - $cut_off;
		
		$db_order	= $defaults['sort_order'] == 'asc' ? 'asc' : 'desc';
		$db_key		= $defaults['sort_key']   == 'click' ? 'running_time' : 'member_name';
		$wheres		= array( 'running_time > ' . $t_time );

		switch ( $defaults['show_mem'] )
		{
			case 'reg':
				$wheres[]	= "member_id > 0";
				$wheres[]	= "member_group != " . $this->settings['guest_group'];
				break;
			case 'guest':
				$wheres[]	= "member_group = " . $this->settings['guest_group'];
				break;
		}
		
		if ( $this->settings['spider_anon'] OR ! $this->settings['spider_active'] )
		{
			if ( ! $this->settings['spider_active'] OR ! $this->memberData['g_access_cp'] )
			{
				$wheres[]	= $this->DB->buildRight( 'id', 8 ) . " != '_session'";
			}
		}
		
		if ( ! $this->settings['disable_anonymous'] AND ! $this->memberData['g_access_cp'] OR $this->settings['disable_admin_anon'] )
		{
			$wheres[]	= "login_type != 1";
		}
		
		//-----------------------------------------
		// Grab all the current sessions.
		//-----------------------------------------
		
		$this->DB->build( array( 'select' 	=> '*',
							 	 'from'		=> 'sessions',
								 'where'	=> implode( ' AND ', $wheres ),
								 'calcRows' => TRUE,
								 'order'	=> $db_key . ' ' . $db_order,
								 'limit'	=> array( $this->first, $this->perpage ) ) );
								
		$outer = $this->DB->execute();
		
		$max   = $this->DB->fetchCalculatedRows();
		
		if ( ! $this->DB->getTotalRows($outer) && $this->first > 0 )
		{
			// We are request page 2 - but there is no page 2 now...
			$this->registry->output->silentRedirect( $this->settings['base_url']."app=members&amp;section=online&amp;module=online&amp;sortkey={$defaults['sort_key']}&amp;show_mem={$defaults['show_mem']}&amp;sort_order={$defaults['sort_order']}" );
		}
		
		//-----------------------------------------
		// Put results into array
		//-----------------------------------------
		
		while( $r = $this->DB->fetch($outer) )
		{
			if ( strstr( $r['id'], '_session' ) )
			{
				$r['is_bot']	= 1;
			}
			
			$r['where_line']	= '';
			$r['where_link']	= '';
			
			//-----------------------------------------
			// Sessions aren't updated until shutdown
			// so reset our session now
			//-----------------------------------------
			
			if( $this->memberData['member_id'] AND $r['member_id'] == $this->memberData['member_id'] )
			{
				$r['current_appcomponent']	= 'members';
				$r['current_module']		= 'online';
				$r['current_section']		= 'online';
			}
			
			//-----------------------------------------
			// Is this a member?
			//-----------------------------------------
			
			if ( $r['member_id'] )
			{
				$memberIDs[] = $r['member_id'];
			}
			
			$final[ $r['id'] ] = $r;

			//-----------------------------------------
			// Module?
			//-----------------------------------------

			$modules[ $r['current_section'] ]  = array( 'app' => $r['current_appcomponent'] );

		}
		
		$links	= $this->registry->output->generatePagination(  array( 'totalItems'  	    => $max,
																	   'itemsPerPage'		=> $this->perpage,
																	   'currentStartValue'	=> $this->first,
																	   'baseUrl'	  	    => "app=members&amp;section=online&amp;module=online&amp;sort_key={$defaults['sort_key']}&amp;sort_order={$defaults['sort_order']}&amp;show_mem={$defaults['show_mem']}"
															)		);
		
		//-----------------------------------------
		// Pass off entries to modules..
		//-----------------------------------------
		
		if ( count( $modules ) )
		{
			foreach( $modules as $module_array )
			{
				$filename = IPSLib::getAppDir( IPSText::alphanumericalClean($module_array['app']) ) . '/extensions/coreExtensions.php';
				
				if ( file_exists( $filename ) )
				{
					require_once( $filename );
					$toload           = 'publicSessions__' . IPSText::alphanumericalClean($module_array['app']);
					$loader           = new $toload;

					if( method_exists( $loader, 'parseOnlineEntries' ) )
					{
						$final = $loader->parseOnlineEntries( $final );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Finally, members...
		//-----------------------------------------
		
		if ( count( $memberIDs ) )
		{
			$members = IPSMember::load( $memberIDs, 'all' );
		}
		
		$newFinal = array();
		
		foreach( $final as $id => $data )
		{
			if ( $data['member_id'] )
			{
				$newFinal[ 'member-' . $data['member_id'] ] = $data;
				$newFinal[ 'member-' . $data['member_id'] ]['memberData']  = $members[ $data['member_id'] ];
				$newFinal[ 'member-' . $data['member_id'] ]['_memberData'] = IPSMember::buildProfilePhoto( $members[ $data['member_id'] ] );
			}
			else
			{
				$newFinal[ $data['id'] ] = $data;
				$newFinal[ $data['id'] ]['memberData']  = array();
				$newFinal[ $data['id'] ]['_memberData'] = IPSMember::buildProfilePhoto( 0 );
			}
		}
				
		$this->output .= $this->registry->getClass('output')->getTemplate('online')->showOnlineList( $newFinal, $links, $defaults, $our_mgroups );

	}
}