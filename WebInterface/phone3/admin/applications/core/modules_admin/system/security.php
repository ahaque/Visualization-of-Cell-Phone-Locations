<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Security center tools
 * Last Updated: $Date: 2009-08-12 18:07:52 -0400 (Wed, 12 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		2.2 IIRC
 * @version		$Revision: 5013 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_system_security extends ipsCommand
{
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
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate('cp_skin_security');

		$this->registry->class_localization->loadLanguageFile( array( 'admin_security', 'admin_system' ) );
		
		/* URLs */
		$this->form_code    = $this->html->form_code    = 'module=system&amp;section=security';
		$this->form_code_js = $this->html->form_code_js = 'module=system&section=security';
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'deep_scan':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'deepscan' );
				$this->deepScan();
			break;
			
			case 'virus_check':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'virus_checker' );
				$this->virusCheck();
			break;
			
			case 'list_admins':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'list_admins' );
				$this->listAdmins();
			break;
			
			case 'htaccess':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'htaccess_protection' );
				$this->doHtaccess();
			break;
			
			case 'confglobal':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'nowrite_conf_global' );
				$this->doConfGlobal();
			break;
			
			case 'acprename':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'acp_rename' );
				$this->doAcpRename();
			break;
				
			case 'acphtaccess':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'acp_htaccess' );
				$this->acpHtaccessForm();
			break;
			
			case 'acphtaccess_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'acp_htaccess' );
				$this->acpHtaccessDo();
			break;
								
			default:
				$this->securityOverview();
			break;				
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Virus checker
	 *
	 * @access	public
	 * @return	void
	 */
	public function listAdmins()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content = "";
		$groups  = array();
		$query   = "";
		$members = array();
		
		//-----------------------------------------
		// Get all admin groups...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
											     'from'   => 'groups',
											  	 'where'  => 'g_access_cp > 0 AND g_access_cp IS NOT NULL' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$_gid = intval( $row['g_id'] );
			
			# I hate looped queries, but this should be OK.
			
			$this->DB->build( array( 'select' => '*',
												     'from'   => 'members',
												  	 'where'  => "member_group_id=" . $_gid ." OR mgroup_others LIKE '%,". $_gid .",%' OR mgroup_others='".$_gid."' OR mgroup_others LIKE '".$_gid.",%' OR mgroup_others LIKE '%,".$_gid."'",
												     'order'  => 'joined DESC' ) );

			$b = $this->DB->execute();
			
			while( $member = $this->DB->fetch( $b ) )
			{
				if ( ! $member['member_group_id'] AND ! $member['mgroup_others'] )
				{
					continue;
				}
				
				$members[ $member['member_id'] ] = $member;
			}
			
			$groups[ $row['g_id'] ] = $row;
		}
		
		//-----------------------------------------
		// Generate list
		//-----------------------------------------
		
		foreach( $members as $id => $member )
		{
			$member['members_display_name'] = $member['members_display_name'] ? $member['members_display_name'] : $member['name'];
			$member['_mgroup']				= $this->caches['group_cache'][ $member['member_group_id'] ]['g_title'];
			$_tmp                           = array();
			$member['_joined']              = $this->registry->class_localization->getDate( $member['joined'], 'JOINED' );
			
			foreach( explode( ",", $member['mgroup_others'] ) as $gid )
			{
				if ( $gid )
				{
					$_tmp[] = $this->caches['group_cache'][ $gid ]['g_title'];
				}
			}
			
			$member['_mgroup_others'] = implode( ", ", $_tmp );
			
			$rows[] = $member;
		}

		$this->registry->output->html .= $this->html->list_admin_overview( $rows );
	}
	
	/**
	 * Virus checker
	 *
	 * @access	public
	 * @return	void
	 */
	public function virusCheck()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content         = "";
		$checked_content = "";
		$colors          = array( 0  => '#84ff00',
								  1  => '#84ff00',
								  2  => '#b5ff00',
								  3  => '#b5ff00',
								  4  => '#ffff00',
								  5  => '#ffff00',
								  6  => '#ffde00',
								  7  => '#ffde00',
								  8  => '#ff8400',
								  9  => '#ff8400',
								  10 => '#ff0000' );
							 
		//-----------------------------------------
		// Get class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/virusChecker/virusChecker.php' );
		$class_virus_checker           = new virusChecker( $this->registry );
		
		//-----------------------------------------
		// Run it...
		//-----------------------------------------
		
		$class_virus_checker->runScan();
		
		//-----------------------------------------
		// Update...
		//-----------------------------------------
		
		$cache_array = $this->caches['systemvars'];
		
		$cache_array['last_virus_check'] = time();
		
		$this->cache->setCache( 'systemvars', $cache_array, array( 'array' => 1, 'donow' => 1 ) );
					
		//-----------------------------------------
		// Got any bad files?
		//-----------------------------------------
		
		if ( is_array( $class_virus_checker->bad_files ) and count( $class_virus_checker->bad_files ) )
		{
			foreach( $class_virus_checker->bad_files as $idx => $data )
			{
				$_data = array();
				$_info = stat( $data['file_path'] );
				
				$_data['size']        = filesize( $data['file_path'] );
				$_data['human']       = ceil( $_data['size'] / 1024 );
				$_data['mtime']       = $this->registry->class_localization->getDate( $_info['mtime'], 'SHORT' );
				$_data['score']       = $data['score'];
				$_data['left_width']  = $data['score'] * 5;
				$_data['right_width'] = 50 - $_data['left_width'];
				$_data['color']       = $colors[ $data['score'] ];
				
				if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
				{
					$data['file_path']	= str_replace( "\\", "/", $data['file_path'] );
					$file_path			= str_replace( DOC_IPS_ROOT_PATH, "",  $data['file_path'] );
					$file_path			= str_replace( "\\", "/", $file_path );
					$data['file_path']	= str_replace( "/", "\\", $data['file_path'] );
				}				
				else
				{
					$file_path = str_replace( DOC_IPS_ROOT_PATH, '', $data['file_path'] );
				}
				
				$content .= $this->html->anti_virus_bad_files_row( $file_path, $data['file_path'], $_data );
			}
			
			$this->registry->output->html .= $this->registry->output->global_template->warning_box( $this->lang->words['s_virus_found'], $this->lang->words['s_virus_located'] ) . "<br />";
			
			$this->registry->output->html .= $this->html->anti_virus_bad_files_wrapper( $content );
		}
		else
		{
			$this->registry->output->html .= $this->registry->output->global_template->information_box( $this->lang->words['s_virus_none'], $this->lang->words['s_nolocated'] ) . "<br />";
		}
		
		//-----------------------------------------
		// Show checked folders...
		//-----------------------------------------
		
		if ( is_array( $class_virus_checker->checked_folders ) and count( $class_virus_checker->checked_folders ) )
		{
			foreach( $class_virus_checker->checked_folders as $name )
			{
				$checked_content .= $this->html->anti_virus_checked_row( str_replace( DOC_IPS_ROOT_PATH, '', $name ) );
			}
			
			$this->registry->output->html .= $this->html->anti_virus_checked_wrapper( $checked_content );
		}
	}
	
	/**
	 * Security Overview Screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function securityOverview()
	{
		/* INIT */
		$content		= array( 'bad' => '', 'good' => '', 'ok' => '' );
		$cache_array	= $this->cache->getCache('systemvars');
		
		/* Virus checker link */
		if ( intval($cache_array['last_virus_check']) < time() - 7 * 86400 )
		{
			$content['bad'] .= $this->html->security_item_bad(  $this->lang->words['s_virus'],
			 													$this->lang->words['s_virus_bad'],
																$this->lang->words['s_runtool'],
																$this->form_code_js.'&code=virus_check',
																'vchecker' );
														
		}
		else
		{
			$last_run 		  = $this->registry->class_localization->getDate( $cache_array['last_virus_check'], 'SHORT' );
			$content['good'] .= $this->html->security_item_good( $this->lang->words['s_virus'],
			 													 $this->lang->words['s_virus_good'].$last_run,
																 $this->lang->words['s_runtool'],
																 $this->form_code_js.'&code=virus_check',
																 'vchecker' );
		}
		
		/* Deep Scan Link */
		if ( intval( $cache_array['last_deepscan_check'] ) < time() - 30 * 86400 )
		{
			$content['bad'] .= $this->html->security_item_bad(  $this->lang->words['s_deep'],
			 													$this->lang->words['s_deep_bad'],
																$this->lang->words['s_runtool'],
																$this->form_code_js.'&do=deep_scan',
																'deepscan' );
														
		}
		else
		{
			$last_run 		  = $this->registry->class_localization->getDate( $cache_array['last_deepscan_check'], 'SHORT' );
			$content['good'] .= $this->html->security_item_good(  $this->lang->words['s_deep'],
			 													  $this->lang->words['s_deep_good'] . $last_run,
																  $this->lang->words['s_runtool'],
																   $this->form_code_js.'&do=deep_scan',
																  'deepscan' );
		}

		/* Get .htaccess settings */
		if( strpos( strtolower( PHP_OS ), 'win' ) !== 0 )
		{
			if ( ! file_exists( IPS_ROOT_PATH . '/.htaccess' ) )
			{
				$content['ok'] .= $this->html->security_item_ok(    $this->lang->words['s_htaccess'],
				 													sprintf( $this->lang->words['s_htaccess_bad'], CP_DIRECTORY ),
																	$this->lang->words['s_learnmore'],
																	$this->form_code_js.'&do=acphtaccess',
																	'acphtaccess' );
			}
			else
			{
				$content['good'] .= $this->html->security_item_good( $this->lang->words['s_htaccess'],
				 											 		 sprintf( $this->lang->words['s_htaccess_good'], CP_DIRECTORY ),
																	 $this->lang->words['s_learnmore'],
																	 $this->form_code_js.'&do=acphtaccess',
																	 'acphtaccess' );
			}
			
			/* Other htaccess protection */
			if ( ! file_exists( IPS_CACHE_PATH . 'cache/.htaccess' ) )
			{
				$content['ok'] .= $this->html->security_item_ok( $this->lang->words['s_phpcgi'],
				 												 $this->lang->words['s_phpcgi_bad'],
																 $this->lang->words['s_runtool'],
																 $this->form_code_js.'&do=htaccess',
																 'htaccess' );
			}
			else
			{
				$content['good'] .= $this->html->security_item_good( $this->lang->words['s_phpcgi'],
				 											 		 $this->lang->words['s_phpcgi_good'],
																	 $this->lang->words['s_runtool'],
																	 $this->form_code_js.'&do=htaccess',
																	 'htaccess' );
			}
			
			/* Conf Global */
			if ( is_writeable( DOC_IPS_ROOT_PATH . 'conf_global.php' ) )
			{
				$content['bad'] .= $this->html->security_item_bad( $this->lang->words['s_conf'],
				 												   $this->lang->words['s_conf_bad'],
																   $this->lang->words['s_runtool'],
																   $this->form_code_js.'&do=confglobal',
																   'confglobal' );
															
			}
			else
			{
				$content['good'] .= $this->html->security_item_good(  $this->lang->words['s_conf'],
				 												 	  $this->lang->words['s_conf_good'],
																	   $this->lang->words['s_learnmore'],
																	   $this->form_code_js.'&do=confglobal',
																	   'confglobal' );
			}
		}

		/* ACP Directory Renamed */
		if ( CP_DIRECTORY == 'admin' )
		{
			$content['ok'] .= $this->html->security_item_ok( $this->lang->words['s_rename'],
			 												 $this->lang->words['s_rename_bad'],
															 $this->lang->words['s_learnmore'],
															 $this->form_code_js.'&do=acprename',
															 'acprename' );
														
		}
		else
		{
			$content['good'] .= $this->html->security_item_good( $this->lang->words['s_rename'],
			 													 $this->lang->words['s_rename_good'],
																 $this->lang->words['s_learnmore'],
																 $this->form_code_js.'&do=acprename',
																 'acprename' );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->securityOverview( $content );
	}	
	
	/**
	 * ACP HTACCESS: Step two
	 *
	 * @access	public
	 * @return	void
	 * @author	Josh
	 */	
	public function acpHtaccessDo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name = trim( $this->request['name'] );
		$pass = trim( $this->request['pass'] );
		
		$htaccess_pw   = "";
		$htaccess_auth = "";
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------

		if ( ! $name or ! $pass )
		{
			$this->registry->output->global_message = $this->lang->words['s_completeform'];
			$this->acpHtaccessForm();
			return;
		}
		
		//-----------------------------------------
		// Format files...
		//-----------------------------------------
		
		$htaccess_auth = "ErrorDocument 401 \"Unauthorized\"\n"
					   . "AuthType Basic\n"
					   . "AuthName \"IP.Board CP\"\n"
					   . "AuthUserFile " . IPS_ROOT_PATH . ".htpasswd\n"
				       . "Require valid-user\n";
				
		$htaccess_pw   = $name . ":" . crypt( $pass, base64_encode( $pass ) );
		
		if ( $FH = @fopen( IPS_ROOT_PATH . '.htpasswd', 'w' ) )
		{
			fwrite( $FH, $htaccess_pw );
			fclose( $FH );
			
			$FF = @fopen( IPS_ROOT_PATH .  '.htaccess', 'w' );
			fwrite( $FF, $htaccess_auth );
			fclose( $FF );
			
			$this->registry->output->global_message = $this->lang->words['s_written'];
			$this->securityOverview();
		}
		else
		{
			$this->registry->output->html .= $this->html->htaccess_data( $htaccess_pw, $htaccess_auth );

			$this->registry->output->extra_nav[] = array( '', $this->lang->words['s_htaccess_nav'] );
		}
	}
	
	/**
	 * ACP HTACCESS: Step One
	 *
	 * @access	public
	 * @return	void
	 */
	public function acpHtaccessForm()
	{
		$this->registry->output->html .= $this->html->htaccess_form();		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['s_htaccess_nav'] );
	}
	
	/**
	 * Rename ACP directory
	 *
	 * @access	public
	 * @return	void
	 */	
	public function doAcpRename()
	{

		$this->registry->output->html .= $this->html->rename_admin_dir();
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['s_rename_nav'] );
	}
	
	/**
	 * Change conf global
	 *
	 * @access	public
	 * @return	void
	 */
	public function doConfGlobal()
	{
		/* INIT */
		$done = 0;
		
		/* Try... */
		if ( @chmod( DOC_IPS_ROOT_PATH . 'conf_global.php', 0444) )
		{
			$done = 1;
		}
		
		/* All done */
		if ( $done )
		{
			$this->registry->output->global_message = $this->lang->words['s_chmod_good'];
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['s_chmod_bad'];
		}
		
		$this->securityOverview();
	}
	
	/**
	 * Add htaccess to non IPB dirs
	 *
	 * @access	public
	 * @return	void
	 */
	public function doHtaccess()
	{
		/* INIT */
		$name = '.htaccess';
		$msg  = array();
		$dirs = array( DOC_IPS_ROOT_PATH . 'cache/',
					   DOC_IPS_ROOT_PATH . 'public/style_avatars',
					   DOC_IPS_ROOT_PATH . 'public/style_captcha',
					   DOC_IPS_ROOT_PATH . 'public/style_css',
					   DOC_IPS_ROOT_PATH . 'public/style_emoticons',
					   DOC_IPS_ROOT_PATH . 'public/style_extra',
					   DOC_IPS_ROOT_PATH . 'public/style_images',
					   DOC_IPS_ROOT_PATH . 'public/resources',
					   DOC_IPS_ROOT_PATH . 'hooks',
					   DOC_IPS_ROOT_PATH . 'uploads' );
					
		$towrite = <<<EOF
#<ipb-protection>
<Files ~ "^.*\.(php|cgi|pl|php3|php4|php5|php6|phtml|shtml)">
    Order allow,deny
    Deny from all
</Files>
#</ipb-protection>
EOF;

		/* Loop through the directories and create the files */
		foreach( $dirs as $directory )
		{
			if ( $FH = @fopen( $directory . '/'. $name, 'w' ) )
			{
				fwrite( $FH, $towrite );
				fclose( $FH );
			
				$msg[] = sprintf( $this->lang->words['s_written_to'], $directory );
			}
			else
			{
				$msg[] = sprintf($this->lang->words['s_skipped'], $directory );
			}
		}
		
		/* All Done */
		$this->registry->output->global_message = implode( "<br />", $msg );
		$this->securityOverview();
	}
	
	/**
	 * Deep Virus Scan
	 *
	 * @access	public
	 * @return	void
	 */
	public function deepScan()
	{
		/* INIT */
		$filter          = trim( $this->request['filter'] );
		$file_count      = 0;
		$bad_count       = 0;
		$content         = "";
		$checked_content = "";
		$colors          = array( 0  => '#84ff00',
								  1  => '#84ff00',
								  2  => '#b5ff00',
								  3  => '#b5ff00',
								  4  => '#ffff00',
								  5  => '#ffff00',
								  6  => '#ffde00',
								  7  => '#ffde00',
								  8  => '#ff8400',
								  9  => '#ff8400',
								  10 => '#ff0000' );
							 
		/* Virus checker */
		require_once( IPS_ROOT_PATH . 'sources/classes/virusChecker/virusChecker.php' );
		$class_virus_checker           = new virusChecker( $this->registry );
		
		/* Run Scan */
		$class_virus_checker->deepScan( rtrim( DOC_IPS_ROOT_PATH, '/' ),
													'(php|cgi|pl|perl|php3|php4|php5|php6|shtml|phtml)',
													array( IPS_CACHE_PATH . 'articles',
													 	   IPS_CACHE_PATH . 'pages') );
		
		/* Update the cache */
		$cache       = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='systemvars'" ) );
		$cache_array = unserialize( stripslashes( $cache['cs_value'] ) );
		
		if( ! is_array( $cache_array ) )
		{
			$cache_array = array();
		}
		
		$cache_array['last_deepscan_check'] = time();
		
		$this->cache->setCache( 'systemvars', $cache_array, array( 'name'  => 'systemvars', 'array' => 1, 'donow' => 1 ) );
											
		/* Found bad files? */
		if ( is_array( $class_virus_checker->bad_files ) and count( $class_virus_checker->bad_files ) )
		{
			foreach( $class_virus_checker->bad_files as $idx => $data )
			{
				$file_count++;
				
				$_data = array();
				$_info = stat( $data['file_path'] );
				
				$_data['size']        = filesize( $data['file_path'] );
				$_data['human']       = ceil( $_data['size'] / 1024 );
				$_data['mtime']       = $this->registry->class_localization->getDate( $_info['mtime'], 'SHORT' );
				$_data['score']       = $data['score'];
				$_data['left_width']  = $data['score'] * 5;
				$_data['right_width'] = 50 - $_data['left_width'];
				$_data['color']       = $colors[ $data['score'] ];
				
				if ( $data['score'] >= 7 )
				{
					$bad_score++;
				}
				
				if ( strstr( $filter, 'score' ) )
				{
					$_filter = intval( str_replace( 'score-', '', $filter ) );
					
					if ( $data['score'] < $_filter )
					{
						continue;
					}
				}
				else if ( $filter == 'large' )
				{
					if ( $_data['human'] < 55 )
					{
						continue;
					}
				}
				else if ( $filter == 'recent' )
				{
					if ( $_info['mtime'] < time() - 86400 * 30 )
					{
						continue;
					} 
				}
				else if ( $filter == 'all' )
				{
					
				}
				else
				{
					$filter = "";
				}
				
				$content .= $this->html->deep_scan_bad_files_row( str_replace( DOC_IPS_ROOT_PATH, '', $data['file_path'] ), $data['file_name'], $_data );
			}
			
			if ( $bad_score )
			{
				$this->registry->output->html .= $this->registry->output->global_template->warning_box( $this->lang->words['s_allex'], sprintf( $this->lang->words['s_allex_bad'], $bad_score, $file_count ) ) . "<br />";
			}
			else
			{
				$this->registry->output->html .= $this->registry->output->global_template->information_box( $this->lang->words['s_allex'], sprintf( $this->lang->words['s_allex_good'], $file_count ) ) . "<br />";
			}
			
			$this->registry->output->html .= $this->html->deep_scan_bad_files_wrapper( $content );
		}
		
		/* Fix Filter */
		if ( $filter )
		{
			$this->registry->output->html = preg_replace( "#(value=[\"']".preg_quote( $filter, '#' )."['\"])#i", "\\1 selected='selected'", $this->registry->output->html );
		}
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['s_deep_nav'] );
	}

	/**
	 * Update Setting
	 *
	 * @access	public
	 * @param	string	$key	Setting key to update
	 * @param	mixed	$value	Value to set
	 * @return	boolean
	 * @deprecated	Not used any more best I can tell
	 */
	public function update_setting( $key, $value )
	{
		/* Check for a key */
		if ( ! $key )
		{
			return FALSE;
		}
		
		/* Update the DB */
		$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => $value ), "conf_key='{$key}'" );
		
		//-----------------------------------------
		// Rebuild settings cache
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/settings.php' );
		$settings           =  new settings();

		$settings->setting_rebuildcache();
		
		/* Done */
		return TRUE;
	}
}