<?php

class boardIndexWhosChatting
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	public function __construct()
	{
		/* Make registry objects */
		$this->registry	= ipsRegistry::instance();
		$this->DB		= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang		= $this->registry->getClass('class_localization');
		$this->member	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	public function getOutput()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_ids         = array();
		$to_load            = array();
		
		//-----------------------------------------
		// Check module/app
		//-----------------------------------------
		
		$module				= '';
		
		if( !IPSLib::appIsInstalled('chat') )
		{
			return '';
		}
		
		if( IPSLib::moduleIsEnabled( 'addonchat', 'chat' ) )
		{
			$_hide_whoschatting	= $this->settings['chat_hide_whoschatting'];
			$_who_on			= $this->settings['chat_who_on'];
		}
		else if( IPSLib::moduleIsEnabled( 'parachat', 'chat' ) )
		{
			$_hide_whoschatting	= $this->settings['chat04_hide_whoschatting'];
			$_who_on			= $this->settings['chat04_who_on'];
		}
		else
		{
			return '';
		}

		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $_who_on )
		{
			return;
		}
		
		//-----------------------------------------
		// Sort and show :D
		//-----------------------------------------
		
		if ( is_array( $this->caches['chatting'] ) AND count( $this->caches['chatting'] ) )
		{
			foreach( $this->caches['chatting'] as $id => $data )
			{
				if ( $data['updated'] < ( time() - 120 ) )
				{
					continue;
				}
				
				$to_load[ $id ] = $id;
			}
		}

		//-----------------------------------------
		// Got owt?
		//-----------------------------------------
		
		if ( count($to_load) )
		{
			$this->DB->build( array( 'select' => 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name',
												     'from'   => array( 'members' => 'm' ),
												     'where'  => "m.member_id IN(" . implode( ",", $to_load ) . ")",
	 												 'add_join' => array( 0 => array( 'select' => 's.login_type, s.current_appcomponent',
																					  'from'   => array( 'sessions' => 's' ),
																					  'where'  => 's.member_id=m.member_id',
																					  'type'   => 'left' ) ),
													 'order'  => 'm.members_display_name' ) );
			$this->DB->execute();
			
			while ( $m = $this->DB->fetch() )
			{
				if( $m['member_id'] == $this->memberData['member_id'] )
				{
					continue;
				}

				$m['members_display_name'] = IPSLib::makeNameFormatted( $m['members_display_name'], $m['member_group_id'] );
								
				if( $m['login_type'] )
				{
					if ( $this->memberData['g_access_cp'] and ($this->settings['disable_admin_anon'] != 1) )
					{
						$member_ids[] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$m['member_id']}", 'public', $m['seo_name'], 'showuser' ) . "'>{$m['members_display_name']}</a>";
					}
				}
				else
				{
					$member_ids[] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$m['member_id']}", 'public', $m['seo_name'], 'showuser' ) . "'>{$m['members_display_name']}</a>";
				}
			}
		}		
		
		//-----------------------------------------
		// Got owt?
		//-----------------------------------------
		
		if ( count( $member_ids ) )
		{
			$this->html = $this->registry->getClass('output')->getTemplate('boards')->whoschatting_show( intval(count($member_ids)), $member_ids );
		}
		else
		{
			if ( ! $_hide_whoschatting )
			{
				$this->html = $this->registry->getClass('output')->getTemplate('boards')->whoschatting_empty();
			}
		}
		
		return $this->html;
	}
}