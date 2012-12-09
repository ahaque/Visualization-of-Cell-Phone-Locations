<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Core user control panel plugin
 * Last Updated: $Date: 2009-08-30 23:34:46 -0400 (Sun, 30 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 5064 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class usercpForms_core extends public_core_usercp_manualResolver implements interface_usercp
{
	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @access	public
	 * @var		string
	 */
	public $tab_name = "Settings";
	
	/**
	 * OK Message
	 * This is an optional message to return back to the framework
	 * to replace the standard 'Settings saved' message
	 *
	 * @access	public
	 * @var		string
	 */
	public $ok_message = '';
	
	/**
	 * Hide 'save' button and form elements
	 * Useful if you have custom output that doesn't
	 * require it
	 *
	 * @access	public
	 * @var		bool
	 */
	public $hide_form_and_save_button = false;
	
	/**
	 * If you wish to allow uploads, set a value for this
	 *
	 * @access	public
	 * @var		integer
	 */
	public $uploadFormMax = 0;
	
	/**
	 * Flag to indicate that the user is a facebook logged in user doozer
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $_isFBUser = false;
	
	/**
	 * Initiate this module
	 *
	 * @access	public
	 * @return	void
	 */
	public function init( )
	{
		$this->tab_name	= ipsRegistry::getClass('class_localization')->words['tab__core'];
		
		/* Facebook? */
		if ( IPSLib::fbc_enabled() === TRUE AND $this->memberData['fb_uid'] )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php' );
			$facebook = new facebook_connect( $this->registry );
		
			/* Test connection */
			$facebook->testConnectSession();
		
			try
			{
				$fbuid = $facebook->FB()->get_loggedin_user();
			}
			catch( Exception $e )
			{
			}
		
			if ( $fbuid )
			{
				$this->_isFBUser = true;
			}
		}
	}
	
	/**
	 * Return links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the tab.
	 *
	 * The links must have 'area=xxxxx'. The rest of the URL
	 * is added automatically.
	 * 'area' can only be a-z A-Z 0-9 - _
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	array 		Links
	 */
	public function getLinks()
	{
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_usercp' ), 'core' );
		
		$array = array();
		
		$array[] = array( 'url'    => 'area=settings',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['ucp_general_settings'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'settings' ? 1 : 0,
						  'area'   => 'settings'
						);
		
		$array[] = array( 'url'    => 'area=email',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['ucp_change_email'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'email' ? 1 : 0,
						  'area'   => 'email' 
						);
		
		/* No use for this for FB users */
		if ( ! $this->_isFBUser )
		{
			$array[] = array( 'url'    => 'area=password',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['ucp_change_password'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'password' ? 1 : 0,
							  'area'   => 'password' 
							);
		}
		
		if ( $this->settings['auth_allow_dnames'] == 1 AND $this->memberData['g_dname_changes'] > 0 )
		{
			$array[] = array( 'url'    => 'area=displayname',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['ucp_change_name'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'displayname' ? 1 : 0,
							  'area'   => 'displayname' 
							);
		}
		
		$array[] = array( 'url'    => 'area=notes',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_notes'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'notes' ? 1 : 0,
						  'area'   => 'notes'
						);
		
		if ( $this->memberData['g_attach_max'] != -1 )
		{
			$array[] = array( 
							'url'    => 'area=attachments',
							'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_attach'],
							'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'attachments' ? 1 : 0,
							'area'   => 'attachments'
							);
		}
		
		return $array;
	}
	
	
	/**
	 * Run custom event
	 *
	 * If you pass a 'do' in the URL / post form that is not either:
	 * save / save_form or show / show_form then this function is loaded
	 * instead. You can return a HTML chunk to be used in the UserCP (the
	 * tabs and footer are auto loaded) or redirect to a link.
	 *
	 * If you are returning HTML, you can use $this->hide_form_and_save_button = 1;
	 * to remove the form and save button that is automatically placed there.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string		Current area
	 * @return	mixed		html or void
	 */
	public function runCustomEvent( $currentArea )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$html = '';
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $currentArea )
		{
			case 'updateAttachments':
				return $this->customEvent_updateAttachments();
			break;
		}
		
		//-----------------------------------------
		// Turn off save button
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $html;
	}
	
	/**
	 * Delete attachments
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function customEvent_updateAttachments()
	{
		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------
 		
 		$finalIDs = array();
 		
		//-----------------------------------------
		// Grab post IDs
		//-----------------------------------------
		
		if ( is_array( $_POST['attach'] ) and count( $_POST['attach'] ) )
		{
			foreach( $_POST['attach'] as $id => $value )
			{
				$finalIDs[ $id ] = intval( $id );
			}
		}

 		if ( count($finalIDs) > 0 )
 		{
			$this->DB->build( array(	'select'	=> 'a.*',
											'from'		=> array( 'attachments' => 'a' ),
											'where'		=> "a.attach_id IN (" . implode( ",", $finalIDs ) .") AND a.attach_rel_module IN( 'post', 'msg' ) AND attach_member_id=" . $this->memberData['member_id'],
											'add_join'	=> array(
																array( 'select'	=> 'p.topic_id, p.pid',
																		'from'	=> array( 'posts' => 'p' ),
																		'where'	=> "p.pid=a.attach_rel_id AND a.attach_rel_module='post'",
																		'type'	=> 'left'
																	),
																array( 'select'	=> 'mt.msg_id, mt.msg_topic_id',
																		'from'	=> array( 'message_posts' => 'mt' ),
																		'where'	=> "mt.msg_id=a.attach_rel_id AND a.attach_rel_module='msg'",
																		'type'	=> 'left'
																	),
																)
								)		);

			$o = $this->DB->execute();

			while ( $killmeh = $this->DB->fetch( $o ) )
			{
				if ( $killmeh['attach_location'] )
				{
					@unlink( $this->settings['upload_dir']."/".$killmeh['attach_location'] );
				}
				if ( $killmeh['attach_thumb_location'] )
				{
					@unlink( $this->settings['upload_dir']."/".$killmeh['attach_thumb_location'] );
				}
				
				if ( $killmeh['topic_id'] )
				{
					$this->DB->update( 'topics', 'topic_hasattach=topic_hasattach-1', 'tid='.$killmeh['topic_id'], true, true );
				}
				else if( $killmeh['msg_id'] )
				{
					$this->DB->update( 'message_topics', 'mt_hasattach=mt_hasattach-1', 'mt_id='.$killmeh['msg_topic_id'], true, true );
				}
			}
			
			$this->DB->delete( 'attachments', 'attach_id IN ('.implode(",",$finalIDs).') and attach_member_id='.$this->memberData['member_id'] );
 		}

		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=attachments&amp;do=show" );
	}
	
	/**
	 * UserCP Form Show
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string		Current area as defined by 'get_links'
	 * @param	array		Array of errors
	 * @return	string		Processed HTML
	 */
	public function showForm( $current_area, $errors=array() )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
	
		switch( $current_area )
		{
			default:
			case 'settings':
				return $this->showFormSettings();
			break;
			case 'email':
				return $this->showFormEmail();
			break;
			case 'password':
				return $this->showFormPassword();
			break;
			case 'displayname':
				return $this->showFormDisplayname();
			break;
			case 'attachments':
				return $this->showFormAttachments();
			break;
			case 'notes':
				return $this->showFormNotes();
			break;
		}
	}
	
	/**
	 * Show the attachments form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function showFormAttachments()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$info        = array();
 		$start       = intval( $this->request['st'] );
 		$perpage     = 15;
 		$sort_key    = "";
 		$attachments = array();

		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// Sort it
		//-----------------------------------------
		
 		switch ( $this->request['sort'] )
 		{
 			case 'date':
 				$sort_key = 'a.attach_date ASC';
 				$info['date_order'] = 'rdate';
 				$info['size_order'] = 'size';
 				break;
 			case 'rdate':
 				$sort_key = 'a.attach_date DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 			case 'size':
 				$sort_key = 'a.attach_filesize DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'rsize';
 				break;
 			case 'rsize':
 				$sort_key = 'a.attach_filesize ASC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 			default:
 				$sort_key = 'a.attach_date DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 		}
 		
 		//-----------------------------------------
 		// Get some stats...
 		//-----------------------------------------
 		
 		$maxspace = intval($this->memberData['g_attach_max']);
 		
 		if ( $this->memberData['g_attach_max'] == -1 )
 		{
 			$this->registry->getClass('output')->showError( 'no_permission_to_attach', 1010 );
 		}
 		
 		//-----------------------------------------
 		// Limit by forums
 		//-----------------------------------------
 		
 		$stats = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count, sum(attach_filesize) as sum',
 												  'from'   => 'attachments',
 												  'where'  => 'attach_member_id=' . $this->memberData['member_id'] . " AND attach_rel_module IN( 'post', 'msg' )" ) );
 		
 		if ( $maxspace > 0 )
 		{
			//-----------------------------------------
			// Figure out percentage used
			//-----------------------------------------
			
			$info['has_limit']    = 1;
			$info['full_percent'] = $stats['sum'] ? sprintf( "%.0f", ( ( $stats['sum'] / ($maxspace * 1024) ) * 100) ) : 0;
			
			if ( $info['full_percent'] > 100 )
			{
				$info['full_percent'] = 100;
			}
			else if ( $info['full_percent'] < 1 AND $stats['count'] > 0 )
			{
				$info['full_percent'] = 1;
			}
			
			$info['attach_space_count'] = sprintf( $this->lang->words['attach_space_count'], intval($stats['count']), intval($info['full_percent']) );
			$info['attach_space_used']  = sprintf( $this->lang->words['attach_space_used'] , IPSLib::sizeFormat(intval($stats['sum'])), IPSLib::sizeFormat($maxspace * 1024) );
 		}
 		else
 		{
 			$info['has_limit'] = 0;
 			$info['attach_space_used']  = sprintf( $this->lang->words['attach_space_unl'] , IPSLib::sizeFormat(intval($stats['sum'])) );
 		}
 		
 		//-----------------------------------------
 		// Pages
 		//-----------------------------------------
 		
 		$pages = $this->registry->getClass('output')->generatePagination( array(  'totalItems'         => $stats['count'],
														   					 	  'itemsPerPage'       => $perpage,
																				  'currentStartValue'  => $start,
																				  'baseUrl'            => "app=core&amp;module=usercp&amp;tab=core&amp;area=attachments&amp;sort=" . $this->request['sort'] . "",
																		  )      );
									  
 		//-----------------------------------------
 		// Get attachments...
 		//-----------------------------------------
 		
 		$this->DB->build( array(  'select'	=> 'a.*',
 										'from'	=> array( 'attachments' => 'a' ),
 										'where'	=> "a.attach_member_id=" . $this->memberData['member_id'] . " AND a.attach_rel_module IN( 'post', 'msg' )",
 										'order'	=> $sort_key,
 										'limit'	=> array( $start, $perpage ),
 										'add_join'	=> array(
 															array( 'select'	=> 'p.topic_id',
 																	'from'	=> array( 'posts' => 'p' ),
 																	'where'	=> 'p.pid=a.attach_rel_id',
 																	'type'	=> 'left'
 																),
 															array( 'select'	=> 't.*',
 																	'from'	=> array( 'topics' => 't' ),
 																	'where'	=> 't.tid=p.topic_id',
 																	'type'	=> 'left'
 																) ) ) );
    	$outer = $this->DB->execute();
    	
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topic'), 'forums' );
		
		$cache = $this->cache->getCache('attachtypes');
		
		while ( $row = $this->DB->fetch( $outer ) )
		{
			if ( IPSMember::checkPermissions('read', $row['forum_id'] ) != TRUE )
			{
				$row['title'] = $this->lang->words['attach_topicmoved'];
			}
			
			//-----------------------------------------
			// Full attachment thingy
			//-----------------------------------------
			
			if ( $row['attach_rel_module'] == 'post' )
			{
				$row['_type'] = 'post';
			}
			else if ( $row['attach_rel_module'] == 'msg' )
			{
				$row['_type'] = 'msg';
				$row['title'] = $this->lang->words['attach_inpm'];
			}
			
			/* IPB 2.x conversion */
			$row['image']       = str_replace( 'folder_mime_types', 'mime_types', $cache[ $row['attach_ext'] ]['atype_img'] );
			$row['short_name']  = IPSText::truncate( $row['attach_file'], 30 );
			$row['attach_date'] = $this->registry->getClass( 'class_localization')->getDate( $row['attach_date'], 'SHORT' );
			$row['real_size']   = IPSLib::sizeFormat( $row['attach_filesize'] );
			
			$attachments[]      = $row;
		}
    	
    	return $this->registry->getClass('output')->getTemplate('ucp')->coreAttachments( $info, $pages, $attachments );
	}
	
	/**
	 * Show the Password form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function showFormPassword()
	{
		//-----------------------------------------
    	// Do we have another URL for password resets?
    	//-----------------------------------------
    	
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$han_login =  new han_login( $this->registry );
    	$han_login->init();
    	$han_login->checkMaintenanceRedirect();
    	
		if( $this->memberData['g_access_cp'] )
		{
			$this->hide_form_and_save_button	= true;
		}

		return $this->registry->getClass('output')->getTemplate('ucp')->passwordChangeForm();
	}
	
	/**
	 * Show the Email form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string		Returned error message (if any)
	 * @return	string		Processed HTML
	 */
	public function showFormEmail( $_message='' )
	{
		//-----------------------------------------
    	// Do we have another URL for email resets?
    	//-----------------------------------------
    	
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$han_login =  new han_login( $this->registry );
    	$han_login->init();
    	$han_login->checkMaintenanceRedirect();
    	
		$txt = $this->lang->words['ce_current'] . $this->memberData['email'];
 		
 		if ( $this->settings['reg_auth_type'])
 		{
 			$txt .= $this->lang->words['ce_auth'];
 		}
 		
 		if ( $this->settings['bot_antispam'] )
 		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();
		}
		
		$_message = $_message ? $this->lang->words[$_message] : '';
		
		if( $this->memberData['g_access_cp'] )
		{
			$this->hide_form_and_save_button	= true;
		}
 		
 		return $this->registry->getClass('output')->getTemplate('ucp')->emailChangeForm( $txt, $_message, $captchaHTML, $this->_isFBUser );
	}
	
	/**
	 * Show the display name form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string		Error message (if any)
	 * @return	string		Processed HTML
	 */
	public function showFormDisplayname( $error="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$form = array();
		
		//-----------------------------------------
		// CHECK (please)
		//-----------------------------------------
		
		if ( ! $this->settings['auth_allow_dnames'] OR $this->memberData['g_dname_changes'] < 1 OR $this->memberData['g_dname_date'] < 1 )
		{
			$this->registry->getClass('output')->showError( 'no_permission_for_display_names', 1011 );
		}
		
		$this->request['display_name'] =  $this->request['display_name'] ? $this->request['display_name'] : '';
		
		$this->settings['username_errormsg'] =  str_replace( '{chars}', $this->settings['username_characters'], $this->settings['username_errormsg'] );
		
		//-----------------------------------------
		// Grab # changes > 24 hours
		//-----------------------------------------
		
		$time_check = time() - 86400 * $this->memberData['g_dname_date'];
		
		if( $time_check < $this->memberData['joined'] )
		{
			$time_check = $this->memberData['joined'];
		}
		
		$name_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(dname_date) as min_date', 'from' => 'dnames_change', 'where' => "dname_member_id=" . $this->memberData['member_id'] . " AND dname_date > $time_check" ) );
		
		$name_count['count']    = intval( $name_count['count'] );
		$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $time_check;
		
		//-----------------------------------------
		// Calculate # left
		//-----------------------------------------
		
		/* Check new permissions */
		$_g = $this->caches['group_cache'][ $this->memberData['member_group_id'] ];
	
		if ( $_g['g_displayname_unit'] )
		{
			if ( $_g['gbw_displayname_unit_type'] )
			{
				/* days */
				if ( $this->memberData['joined'] > ( time() - ( 86400 * $_g['g_displayname_unit'] ) ) )
				{
					$this->hide_form_and_save_button = 1;
					$form['_noPerm'] = sprintf( $this->lang->words['dname_group_restrict_date'], $this->lang->getDate( $this->memberData['joined'] + ( 86400 * $_g['g_displayname_unit'] ), 'long' ) );
				}
			}
			else
			{
				/* Posts */
				if ( $this->memberData['posts'] < $_g['g_displayname_unit'] )
				{
					$this->hide_form_and_save_button = 1;
					$form['_noPerm'] = sprintf( $this->lang->words['dname_group_restrict_posts'], $_g['g_displayname_unit'] - $this->memberData['posts'] );
				}
			}
		}
		
		if( !$form['_noPerm'] )
		{
			$form['_changes_left'] = $this->memberData['g_dname_changes'] - $name_count['count'];
			$form['_changes_done'] = $name_count['count'];
		
			# Make sure changes done isn't larger than allowed
			# This happens when changing via ACP
		
			if ( $form['_changes_done'] > $this->memberData['g_dname_changes'] )
			{
				$form['_changes_done'] = $this->memberData['g_dname_changes'];
			}
		
			$form['_first_change'] = $this->registry->getClass( 'class_localization')->getDate( $name_count['min_date'], 'date', 1 );
			$form['_lang_string']  = sprintf( $this->lang->words['dname_string'],
												$form['_changes_done'], $this->memberData['g_dname_changes'],
												$form['_first_change'], $this->memberData['g_dname_changes'],
												$this->memberData['g_dname_date'] );
		}
		
		//-----------------------------------------
		// Print
		//-----------------------------------------
		
		$this->_pageTitle = $this->lang->words['m_dname_change'];
 	
		return $this->registry->getClass('output')->getTemplate('ucp')->displayNameForm( $form, $error, $okmessage, $this->_isFBUser );
	}
	
	/**
	 * Show the Settings form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function showFormSettings()
	{
		/* Sort the times */
		$times		= array();
		
		foreach( $this->lang->words as $k => $v )
		{
			if( strpos( $k, "time_" ) === 0 )
			{
				$k				= str_replace( "time_", '', $k );
				
				if( preg_match( "/^[\-\d\.]+$/", $k ) )
				{
					$times[ $k ]	= $v;
				}
			}
		}
		
		ksort( $times );
		//uksort( $this->lang->words, create_function( '$a, $b', '$a = str_replace( "time_", "", $a ); $b = str_replace( "time_", "", $b ); return $a > $b;' ) );
		
		/* Show the form */
 		return $this->registry->getClass('output')->getTemplate('ucp')->generalSettingsForm( $times );		
	}
	
	/**
	 * Show the Notes form
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function showFormNotes()
	{
		/* Show the form */
		$member  = IPSMember::load( $this->memberData['member_id'], 'extendedProfile' );
		$content = $member['notes'];
		
 		return $this->registry->getClass('output')->getTemplate('ucp')->coreNotesForm( $content );		
	}
	
	/**
	 * UserCP Form Check
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @param	string		Current area as defined by 'get_links'
	 * @return	string		Processed HTML
	 */
	public function saveForm( $current_area )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
		
		switch( $current_area )
		{
			default:
			case 'settings':
				return $this->saveFormSettings();
			break;
			case 'email':
				return $this->saveFormEmail();
			break;
			case 'password':
				return $this->saveFormPassword();
			break;
			case 'displayname':
				return $this->saveFormDisplayname();
			break;
			case 'notes':
				return $this->saveFormNotes();
			break;
		}
	}
	
	/**
	 * UserCP Save Form: Notes
	 *
	 * @access	public
	 * @return	boolean		Successful
	 */
	public function saveFormNotes()
	{
		//-----------------------------------------
		// Remove board tags
		//-----------------------------------------
		
		$_POST['Post'] = IPSText::removeMacrosFromInput( $_POST['Post'] );

		//-----------------------------------------
		// Write it to the DB.
		//-----------------------------------------
		
		IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => array( 'notes' => htmlspecialchars($_POST['Post']) ) ) );
		
		$this->ok_message	= $this->lang->words['notes_saved_msg'];
		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Password
	 *
	 * @access	public
	 * @param	array	Array of member / core_sys_login information (if we're editing)
	 * @return	mixed	Array of errors / boolean true
	 */
	public function saveFormPassword( $member=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cur_pass = trim($this->request['current_pass']);
 		$new_pass = trim($this->request['new_pass_1']);
 		$chk_pass = trim($this->request['new_pass_2']);

		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if( $this->memberData['g_access_cp'] )
		{
			return array( 0 => $this->lang->words['admin_emailpassword'] );
		}
		
		if ( ! $_POST['current_pass'] OR ( empty($new_pass) ) or ( empty($chk_pass) ) )
 		{
			return array( 0 => $this->lang->words['complete_entire_form'] );
 		}
 		
 		//-----------------------------------------
 		// Do the passwords actually match?
 		//-----------------------------------------
 		
 		if ( $new_pass != $chk_pass )
 		{
 			return array( 0 => $this->lang->words['passwords_not_matchy'] );
 		}
 		
 		//-----------------------------------------
 		// Check password...
 		//-----------------------------------------
 		
		if ( $this->_checkPassword( $cur_pass ) !== TRUE )
		{
			return array( 0 => $this->lang->words['current_pw_bad'] );
		}
		
		/*if ( IPSText::mbstrlen( $new_pass ) > 32)
		{
			return array( 0 => $this->lang->words['new_pw_too_long'] );
		}*/

 		//-----------------------------------------
 		// Create new password...
 		//-----------------------------------------
 		
 		$md5_pass = md5($new_pass);
 		
        //-----------------------------------------
    	// han_login was loaded during check_password
    	//-----------------------------------------
    	
    	$this->han_login->changePass( $this->memberData['email'], $md5_pass );

    	if ( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
    	{
			return array( 0 => $this->lang->words['hanlogin_pw_failed'] );
    	}
 		
 		//-----------------------------------------
 		// Update the DB
 		//-----------------------------------------
 		
 		IPSMember::updatePassword( $this->memberData['email'], $md5_pass );
 		
 		IPSLib::runMemberSync( 'onPassChange', $this->memberData['member_id'], $new_pass );
 		
 		//-----------------------------------------
 		// Update members log in key...
 		//-----------------------------------------
 		
 		$key  = IPSMember::generateAutoLoginKey();

		IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_login_key' => $key ) ) );
 		 		
		$this->ok_message = $this->lang->words['pw_change_successful'];
		
 		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Display Name
	 *
	 * @access	public
	 * @return	mixed	Array of errors / boolean true
	 */
	public function saveFormDisplayname()
	{		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$members_display_name  = trim($this->request['displayName']);
		$password_check        = trim( $this->request['displayPassword'] );
		
		//-----------------------------------------
		// Check for blanks...
		//-----------------------------------------
		
		if ( ! $members_display_name OR ( ! $this->_isFBUser AND ! $password_check ) )
		{
			return array( 0 => $this->lang->words['complete_entire_form'] );
		}
	
		//-----------------------------------------
		// Check password
		//-----------------------------------------
		
		if ( ! $this->_isFBUser )
		{
			if ( $this->_checkPassword( $password_check ) === FALSE )
			{
				return array( 0 => $this->lang->words['current_pw_bad'] );
			}
		}
		
		try
		{
			if ( IPSMember::getFunction()->updateName( $this->memberData['member_id'], $members_display_name, 'members_display_name' ) === TRUE )
			{
				$this->cache->rebuildCache( 'stats', 'global' );
				
				return $this->showFormDisplayname( '', $this->lang->words['dname_change_ok'] );
			}
			else
			{
				# We should absolutely never get here. So this is a fail-safe, really to
				# prevent a "false" positive outcome for the end-user
				return array( 0 => $this->lang->words['name_taken_change'] );
			}
		}
		catch( Exception $error )
		{
			switch( $error->getMessage() )
			{
				case 'NO_MORE_CHANGES':
					return array( 0 => $this->lang->words['name_change_no_more'] );
				break;
				case 'NO_USER':
					return array( 0 => $this->lang->words['name_change_noload'] );
				break;
				case 'NO_PERMISSION':
					return array( 0 => $this->lang->words['name_change_noperm'] );
				case 'NO_NAME':
					return array( 0 => sprintf( $this->lang->words['name_change_tooshort'], $this->settings['max_user_name_length'] ) );
				break;
				case 'TOO_LONG':
					return array( 0 => sprintf( $this->lang->words['name_change_tooshort'], $this->settings['max_user_name_length'] ) );
				break;
				case 'ILLEGAL_CHARS':
					return array( 0 => $this->lang->words['name_change_illegal'] );
				break;
				case 'USER_NAME_EXISTS':
					return array( 0 => $this->lang->words['name_change_taken'] );
				break;
				default:
					return array( 0 => $error->getMessage() );
				break;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Email Address
	 *
	 * @access	public
	 * @return	mixed		Array of errors / boolean true
	 */
	public function saveFormEmail()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$_emailOne         = strtolower( trim($this->request['in_email_1']) );
		$_emailTwo         = strtolower( trim($this->request['in_email_2']) );
		$captchaInput      = trim(ipsRegistry::$request['captchaInput']);
		$captchaUniqueID   = trim(ipsRegistry::$request['captchaUniqueID']);
		
		//-----------------------------------------
		// Check input
		//-----------------------------------------

		if( $this->memberData['g_access_cp'] )
		{
			return array( 0 => $this->lang->words['admin_emailpassword'] );
		}

		if ( ! $_POST['in_email_1'] OR ! $_POST['in_email_2'] )
		{
			return array( 0 => $this->lang->words['complete_entire_form'] );
		}

		//-----------------------------------------
		// Check password...
		//-----------------------------------------
		
		if ( ! $this->_isFBUser )
		{
			if ( $this->_checkPassword( $this->request['password'] ) === FALSE )
			{
				return array( 0 => $this->lang->words['current_pw_bad'] );
			}
		}

		//-----------------------------------------
		// Test email addresses
		//-----------------------------------------

		if ( $_emailOne != $_emailTwo)
		{
			return array( 0 => $this->lang->words['emails_no_matchy'] );
		}

		if ( IPSText::checkEmailAddress( $_emailOne ) !== TRUE )
		{
			return array( 0 => $this->lang->words['email_not_valid'] );
		}

		//-----------------------------------------
		// Is this email addy taken?
		//-----------------------------------------

		if ( IPSMember::checkByEmail( $_emailOne ) == TRUE )
		{
			return array( 0 => $this->lang->words['email_is_taken'] );
		}

		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
		}

		//-----------------------------------------
		// Check in banned list
		//-----------------------------------------

		if ( isset($banfilters['email']) AND is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
		{
			foreach ( $banfilters['email'] as $email )
			{
				$email = str_replace( '\*', '.*' ,  preg_quote($email, "/") );

				if ( preg_match( "/^{$email}$/i", $_emailOne ) )
				{
					return array( 0 => $this->lang->words['email_is_taken'] );
				}
			}
		}

		//-----------------------------------------
		// Anti bot flood...
		//-----------------------------------------

		if ( $this->settings['bot_antispam'] )
		{
			if ( $this->registry->getClass('class_captcha')->validate() !== TRUE )
			{
				return array( 0 => $this->lang->words['captcha_email_invalid'] );
			}
		}

		//-----------------------------------------
		// Load handler...
		//-----------------------------------------

		require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
		$this->han_login =  new han_login( $this->registry );
		$this->han_login->init();
		
		if ( $this->han_login->emailExistsCheck( $_emailOne ) !== FALSE )
		{
			return array( 0 => $this->lang->words['email_is_taken'] );
		}
		
		$this->han_login->changeEmail( $this->memberData['email'], $_emailOne );

		if ( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		{
		 	return array( 0 => $this->lang->words['email_is_taken'] );
		}

		//-----------------------------------------
		// Require new validation? NON ADMINS ONLY
		//-----------------------------------------

		if ( $this->settings['reg_auth_type'] AND !$this->memberData['g_access_cp'] )
		{
			$validate_key = md5( IPSLib::makePassword() . time() );

			//-----------------------------------------
			// Update the new email, but enter a validation key
			// and put the member in "awaiting authorisation"
			// and send an email..
			//-----------------------------------------

			$db_str = array(
							'vid'         => $validate_key,
							'member_id'   => $this->memberData['member_id'],
							'temp_group'  => $this->settings['auth_group'],
							'entry_date'  => time(),
							'coppa_user'  => 0,
							'email_chg'   => 1,
							'ip_address'  => $this->request['IP_ADDRESS'],
							'prev_email'  => $this->memberData['email'],
						   );

			if ( $this->memberData['member_group_id'] != $this->settings['auth_group'] )
			{
				$db_str['real_group'] = $this->memberData['member_group_id'];
			}

			$this->DB->insert( 'validating', $db_str );
			
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_group_id' => $this->settings['auth_group'],
																							  'email'           => $_emailOne ) ) );
																							  
			IPSLib::runMemberSync( 'onEmailChange', $this->memberData['member_id'], strtolower( $_emailOne ) );

			//-----------------------------------------
			// Update their session with the new member group
			//-----------------------------------------

			if ( $this->member->session_id  )
			{
				$this->member->sessionClass()->convertMemberToGuest();
			}

			//-----------------------------------------
			// Kill the cookies to stop auto log in
			//-----------------------------------------

			IPSCookie::set( 'pass_hash'  , '-1', 0 );
			IPSCookie::set( 'member_id'  , '-1', 0 );
			IPSCookie::set( 'session_id' , '-1', 0 );

			//-----------------------------------------
			// Dispatch the mail, and return to the activate form.
			//-----------------------------------------

			IPSText::getTextClass( 'email' )->getTemplate("newemail");

			IPSText::getTextClass( 'email' )->buildMessage( array(
												'NAME'         => $this->memberData['members_display_name'],
												'THE_LINK'     => $this->settings['base_url']."app=core&module=global&section=register&do=auto_validate&type=newemail&uid=".$this->memberData['member_id']."&aid=".$validate_key,
												'ID'           => $this->memberData['member_id'],
												'MAN_LINK'     => $this->settings['base_url']."app=core&module=global&section=register&do=07",
												'CODE'         => $validate_key,
											  ) );

			IPSText::getTextClass( 'email' )->subject = $this->lang->words['lp_subject'].' '.$this->settings['board_name'];
			IPSText::getTextClass( 'email' )->to      = $_emailOne;

			IPSText::getTextClass( 'email' )->sendMail();

			$this->registry->getClass('output')->redirectScreen( $this->lang->words['ce_redirect'], $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=07' );
		}
		else
		{
			//-----------------------------------------
			// No authorisation needed, change email addy and return
			//-----------------------------------------
			
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'email' => $_emailOne ) ) );
			
			IPSLib::runMemberSync( 'onEmailChange', $this->memberData['member_id'], strtolower( $_emailOne ) );
		
			//-----------------------------------------
			// Add to OK message
			//-----------------------------------------
		
			$this->ok_message = $this->lang->words['ok_email_changed'];
			
			return TRUE;
		}
	}
	
	/**
	 * UserCP Save Form: Settings
	 *
	 * @access	public
	 * @param	array	Array of member / core_sys_login information (if we're editing)
	 * @return	mixed	Array of errors / boolean true
	 */
	public function saveFormSettings( $member=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$timeZone    = IPSText::alphanumericalClean( $this->request['timeZone'], '+.' );
		$dst_correct = intval( $this->request['dst_correct'] );
	
		//-----------------------------------------
		// RTE
		//-----------------------------------------
		
		if ( ! $this->settings['posting_allow_rte'] )
		{
			$this->request[ 'editorChoice'] =  0 ;
		}

		//-----------------------------------------
		// PM Settings: 2 means admin says no.
		//-----------------------------------------
		
		if ( $this->memberData[ 'members_disable_pm' ] == 2 )
		{
			$this->member->setProperty( 'members_disable_pm', 2 );
		}
		else
		{
			$this->member->setProperty( 'members_disable_pm', intval( $this->request[ 'disableMessenger' ] ) );
		}
		
		//-----------------------------------------
		// Only one account per identity url
		//-----------------------------------------
		
		if( $this->request['identity_url'] )
		{
			$account	= $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "identity_url='" . trim($this->request['identity_url']) . "' AND member_id<>" . $this->memberData['member_id'] ) );
			
			if( $account['member_id'] )
			{
				return array( 0 => $this->lang->words['identity_url_assoc'] );
			}
			
			//-----------------------------------------
			// Need to clean up identity URL a little
			//-----------------------------------------
			
			$identityUrl	= trim($this->request['identity_url']);
			$identityUrl	= rtrim( $identityUrl, "/" );
			
			if( !strpos( $identityUrl, 'http://' ) === 0 AND !strpos( $identityUrl, 'https://' ) === 0 )
			{
				$identityUrl = 'http://' . $identityUrl;
			}
		}
		
		/* Figure out BW options */
		$toSave = IPSBWOptions::thaw( $this->memberData['members_bitoptions'], 'members' );
		
		foreach( array( 'bw_vnc_type', 'bw_forum_result_type' ) as $field )
		{
			$toSave[ $field ] = intval( $this->request[ $field ] );
		}

		IPSMember::save( $this->memberData['member_id'], array( 'core' => array(  'hide_email'            => intval( $this->request['hide_email'] ),
															   					  'email_pm'              => intval( $this->request['pm_reminder'] ),
													   							  'allow_admin_mails'     => intval( $this->request['admin_send'] ),
																				  'time_offset'           => $timeZone,
																				  'dst_in_use'            => ( $this->request['dstOption'] AND intval($this->request['dstCheck']) == 0 ) ? intval($this->request['dstOption']) : 0,
																				  'members_auto_dst'      => intval($this->request['dstCheck']),
																				  'members_disable_pm'    => intval($this->memberData['members_disable_pm']),
																				  'members_editor_choice' => $this->request['editorChoice'] ? 'rte' : 'std',
																				  'member_uploader'		  => $this->request['member_uploader'] ? 'flash' : 'default',
																				  'view_pop'			  => intval($this->request['showPMPopUp']),
																				  'identity_url'		  => $identityUrl,
																				  'members_bitoptions'	  => IPSBWOptions::freeze( $toSave, 'members' ) ) ) );

		return TRUE;
	}
	
	/**
	 * Password check
	 *
	 * @access	private
	 * @param	string		Plain Text Password
	 * @return	boolean		Password matched or not
	 */
	private function _checkPassword( $password_check )
	{
		//-----------------------------------------
		// Ok, check password first
		//-----------------------------------------
		
    	require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login( $this->registry );
    	$this->han_login->init();
		
		//-----------------------------------------
		// Is this a username or email address?
		//-----------------------------------------
		
		$this->han_login->loginPasswordCheck( $this->memberData['name'], $this->memberData['email'], $password_check );
	
		if ( $this->han_login->return_code == 'SUCCESS' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}