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
			case 'sql1':
				$this->upgradeSql(1);
				break;
			case 'sql2':
				$this->upgradeSql(2);
				break;
			case 'sql3':
				$this->upgradeSql(3);
				break;
			case 'sql4':
				$this->upgradeSql(4);
				break;
			case 'sql5':
				$this->upgradeSql(5);
				break;
			case 'sql6':
				$this->upgradeSql(6);
				break;
			case 'applications':
				$this->addApplications();
				break;
			case 'settings':
				$this->addSettings();
				break;
			case 'profile':
				$this->addProfile();
				break;
			case 'permsAndBbcode':
				$this->permsAndBbcode();
				break;
			case 'skinlang':
				$this->exportSkinLang();
				break;
			case 'pms':
				$this->convertPms();
				break;
			case 'contacts':
				$this->convertBlockLists();
				break;
			case 'pmblock':
				$this->convertPmBlockLists();
				break;
			case 'finish':
				$this->finish();
				break;
			
			default:
				$this->upgradeSql(1);
				break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	* Run SQL files
	* 
	* @access	public
	* @param	int
	*/
	public function upgradeSql( $id=1 )
	{
		$cnt        = 0;
		$SQL        = array();
		$file       = '_updates_'.$id.'.php';
		$output     = "";
		$path       = IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_30001/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix     = $this->registry->dbFunctions()->getPrefix();
		$sourceFile = '';
		
		if ( file_exists( $path ) )
		{
			require( $path );
			
			/* Set DB driver to return any errors */
			$this->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';
				
				$query  = str_replace( "<%time%>", time(), $query );
				
				if ( $this->settings['mysql_tbl_type'] )
				{
					if ( preg_match( "/^create table(.+?)/i", $query ) )
					{
						$query = preg_replace( "/^(.+?)\);$/is", "\\1) TYPE={$this->settings['mysql_tbl_type']};", $query );
					}
				}
				
				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
				
				if( IPSSetUp::getSavedData('man') )
				{
					$query = trim( $query );
					
					/* Ensure the last character is a semi-colon */
					if ( substr( $query, -1 ) != ';' )
					{
						$query .= ';';
					}
					
					$output .= $query . "\n\n";
				}
				else
				{			
					$this->DB->query( $query );
					
					if ( $this->DB->error )
					{
						$this->registry->output->addError( "<br />" . $query."<br />".$this->DB->error );
					}
					else
					{
						$cnt++;
					}
				}
			}
		
			$this->registry->output->addMessage("$cnt queries run....");
		}
		
		/* Next Page */
		$this->request['st'] = 0;
		
		if ( $id < 6 )
		{
			$nextid = $id + 1;
			$this->request['workact'] = 'sql'.$nextid;	
		}
		else
		{
			$this->request['workact'] = 'applications';	
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '30001', $id );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
	}	
	
	
	/**
	* Add Applications
	* 
	* @access	public
	* @param	int
	*/
	public function addApplications()
	{
		/* Add applications.. */
		$apps       = array( 'core' => array(), 'ips' => array(), 'other' => array() );
		$components = array();
		
		foreach( array( 'applications', 'applications_addon/ips', 'applications_addon/other' ) as $_pBit )
		{
			$path   = IPS_ROOT_PATH . $_pBit;
			$handle = opendir( $path );
		
			while ( ( $file = readdir( $handle ) ) !== FALSE )
			{
				if ( ! preg_match( "#^\.#", $file ) )
				{
					if ( is_dir( $path . '/' . $file ) )
					{
						//-----------------------------------------
						// Get it!
						//-----------------------------------------
					
						if ( ! file_exists( IPS_ROOT_PATH . $_pBit . '/' . $file . '/xml/information.xml' ) )
						{
							continue;		
						}
						
						$data = IPSSetUp::fetchXmlAppInformation( $file );
						
						switch( $_pBit )
						{
							case 'applications':
								$apps['core'][ $file ] = $data;
							break;
							case 'applications_addon/ips':
								$apps['ips'][ $file ] = $data;
							break;
							case 'applications_addon/other':
								$apps['other'][ $file ] = $data;
							break;
						}
					}
				}
			}
		
			closedir( $handle );
		}
		
		/* Reorder the array so that core is first */
		$new_array = array();
		$new_array['core'] = $apps['core']['core'];
		
		foreach( $apps['core'] as $app => $data )
		{
			if( $app == 'core' )
			{
				continue;
			}
			
			$new_array[$app] = $data;
		}
		
		$apps['core'] = $new_array;
		
		/* Fetch data for current 'components' */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'components' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$components[ $row['com_section'] ] = $row;
		}
		
		/* Got Gallery? */
		if ( $components['gallery']['com_enabled'] AND $this->DB->checkForTable( 'gallery_upgrade_history' ) )
		{
			/* Fetch current version number */
			$version = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'gallery_upgrade_history',
														'order'  => 'gallery_version_id DESC',
														'limit'  => array( 0, 1 ) ) );
														
			$apps['ips']['gallery']['_currentLong']  = $version['gallery_version_id'];
			$apps['ips']['gallery']['_currentHuman'] = $version['gallery_version_human'];
		}
		
		/* Got Blog? */
		if ( $components['blog']['com_enabled'] AND $this->DB->checkForTable( 'blog_upgrade_history' ) )
		{
			/* Fetch current version number */
			$version = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'blog_upgrade_history',
														'order'  => 'blog_version_id DESC',
														'limit'  => array( 0, 1 ) ) );
														
			$apps['ips']['blog']['_currentLong']  = $version['blog_version_id'];
			$apps['ips']['blog']['_currentHuman'] = $version['blog_version_human'];
		}
		
		/* Got Downloads? */
		if ( $components['downloads']['com_enabled'] AND $this->DB->checkForTable( 'downloads_upgrade_history' ) )
		{
			/* Fetch current version number */
			$version = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'downloads_upgrade_history',
														'order'  => 'idm_version_id DESC',
														'limit'  => array( 0, 1 ) ) );
														
			$apps['ips']['downloads']['_currentLong']  = $version['idm_version_id'];
			$apps['ips']['downloads']['_currentHuman'] = $version['idm_version_human'];
		}
		
		/* Others.. */
		$apps['core']['forums']['_currentLong']  = '30001';
		$apps['core']['forums']['_currentHuman'] = '3.0.0';
		
		$apps['core']['core']['_currentLong']  = '30001';
		$apps['core']['core']['_currentHuman'] = '3.0.0';
		
		$apps['core']['members']['_currentLong']  = '30001';
		$apps['core']['members']['_currentHuman'] = '3.0.0';
		
		$apps['ips']['portal']['_currentLong']  = '30003';
		$apps['ips']['portal']['_currentHuman'] = '3.0.0';
		
		$apps['ips']['chat']['_currentLong']  = '30003';
		$apps['ips']['chat']['_currentHuman'] = '3.0.0';
		
		$apps['ips']['calendar']['_currentLong']  = '30003';
		$apps['ips']['calendar']['_currentHuman'] = '3.0.0';
		
		/* Now install them.. */
		$num = 0;
		
		foreach( $apps as $where => $data )
		{
			foreach( $apps[ $where ] as $dir => $appData )
			{
				//-----------------------------------------
				// Had Gallery (e.g.) but didn't upload updated files
				//-----------------------------------------
				
				if( !$appData['name'] )
				{
					continue;
				}
				
				$num++;
				$_protected = ( in_array( $appData['key'], array( 'core', 'forums', 'members' ) ) ) ? 1 : 0;
				$_enabled   = ( $appData['disabledatinstall'] ) ? 0 : 1;
				
				if ( ! $appData['_currentLong'] )
				{
					continue;
				}
				
				$this->registry->output->addMessage("Adding application: {$appData['name']}....");
				
				$this->DB->insert( 'core_applications', array(   'app_title'        => $appData['name'],
																 'app_public_title' => ( $appData['public_name'] ) ? $appData['public_name'] : '',	// Allow blank in case it's an admin-only app
																 'app_description'  => $appData['description'],
																 'app_author'       => $appData['author'],
																 'app_hide_tab'		=> intval($appData['hide_tab']),
																 'app_version'      => $appData['_currentHuman'],
																 'app_long_version' => $appData['_currentLong'],
																 'app_directory'    => $appData['key'],
																 'app_added'        => time(),
																 'app_position'     => $num,
																 'app_protected'    => $_protected,
																 'app_location'     => IPSLib::extractAppLocationKey( $appData['key'] ),
																 'app_enabled'      => $_enabled ) );
			}
		}
		
		/* Next Page */
		$this->request['workact'] = 'settings';
	}
	
	/**
	* Add Settings
	* 
	* @access	public
	* @param	int
	*/
	public function addSettings()
	{
		/* INIT */
		$known = array();
		$apps  = array();
		
		/* Grab all known settings */
		$this->DB->build( array( 'select' => '*', 'from' => 'conf_settings' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( $row['conf_value'] != '' )
			{
				$known[ $row['conf_key'] ] = $row['conf_value'];
			}
		}
		
		/* Brandon's bug report thingy #12516*/
		if ( ! $known['gb_char_set'] )
		{
			/* No charset, set - so we need to now ensure that iso-8859-1 is set */
			$known['gb_char_set'] = 'iso-8859-1';
		}
		
		/* Wipe out custom time/date settings */
		foreach( array( 'clock_short', 'clock_long', 'clock_tiny', 'clock_date', 'clock_joined', 'time_use_relative_format' ) as $setting )
		{
			unset( $known[ $setting ] );
		}
		
		/* Now we need to fix moderator groups who have permission to RC */
		$groups	= array();
		
		$this->DB->build( array( 'select' => 'g_id', 'from' => 'groups', 'where' => 'g_access_cp=1 OR g_is_supmod=1' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$groups[]	= $r['g_id'];
		}
		
		$known['report_mod_group_access']	= implode( ',', $groups );
		
		/* Load apps */
		$this->DB->build( array( 'select' => '*', 'from' => 'core_applications' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$apps[ $row['app_directory'] ] = $row;
		}
		
		/* Add 'em */
		foreach( $apps as $dir => $data )
		{
			if ( file_exists( IPSLib::getAppDir( $dir ) .  '/xml/' . $dir . '_settings.xml' ) )
			{
				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/tools/settings.php' );
				$settings =  new admin_core_tools_settings();
				$settings->makeRegistryShortcuts( $this->registry );
		
				$this->request['app_dir'] = $dir;
				$settings->importAllSettings( 1, 1, $known );
				
				$this->registry->output->addMessage("Added settings for {$data['app_title']}....");
			}
		}
		
		/* Next Page */
		$this->request['workact'] = 'profile';
	}
	
	/**
	* Sort out profile fields
	* 
	* @access	public
	* @param	int
	*/
	public function addProfile()
	{
		/* INIT */
		$fields     = array();
		$prefix     = $this->registry->dbFunctions()->getPrefix();
		$sourceFile = '';
		
		/* Get DB driver file */
		require_once( IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_30001/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . '_version_upgrade.php' );
		
		/* First off, move all current profile fields to group ID 3 */
		$this->DB->update( 'pfields_data', array( 'pf_group_id' => 3 ), 'pf_group_id=0' );
		
		/* Grab all custom fields */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'pfields_data' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$fields[ $row['pf_id'] ] = $row;
		}
		
		foreach( $fields as $id => $data )
		{
			/* Now add any missing content fields */
			if ( ! $this->DB->checkForField( "field_$id", 'pfields_content' ) )
			{
				$this->DB->addField( 'pfields_content', "field_$id", 'text' );
			}
		}
		
		$this->DB->optimize( 'pfields_content' );
		
		/* Now make add a key */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'pfields_data',
								 'where'  => 'pf_group_id=3' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			/* Attempt basic conversion of data */
			if ( $row['pf_topic_format'] )
			{
				if ( $row['pf_topic_format'] == '{title}: {content}<br />' )
				{
					$row['pf_topic_format'] = '<span class="ft">{title}:</span><span class="fc">{content}</span>';
				}
				else if ( $row['pf_topic_format'] == '{title}: {key}<br />' )
				{
					$row['pf_topic_format'] = '<span class="ft">{title}:</span><span class="fc">{key}</span>';
				}
			}
			
			/* 2.3.x used 'text', 3.0.0 uses 'input'... */
			$row['pf_type'] = ( $row['pf_type'] == 'text' ) ? 'input' : $row['pf_type'];
			
			$this->DB->update( 'pfields_data', array( 'pf_type' => $row['pf_type'], 'pf_topic_format' => $row['pf_topic_format'], 'pf_key' => IPSText::makeSeoTitle( $row['pf_title'] ) ), 'pf_id=' . $row['pf_id'] );
		}
			
		/* Now, move profile data into the correct fields */
		foreach( array( 'aim_name'   => 'aim',
						'icq_number' => 'icq',
						'website'    => 'website',
						'yahoo'      => 'yahoo',
						'interests'  => 'interests',
						'msnname'    => 'msn',
						'location'   => 'location' ) as $old => $new )
		{
			$field = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'pfields_data',
													  'where'  => 'pf_key=\'' . $new . '\'' ) );
			
			
			
			
			$query = SQLVC::UpdateOne( $old, $field );
			
			if ( IPSSetUp::getSavedData('man') )
			{
				$query = trim( $query );
				
				/* Ensure the last character is a semi-colon */
				if ( substr( $query, -1 ) != ';' )
				{
					$query .= ';';
				}
				
				$output .= $query . "\n\n";
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
					$this->registry->output->addMessage("Converted field: {$old}....");
				}
			}
		}
		
		/* Update gender */
		$gender = $this->DB->buildAndFetch( array( 'select' => '*',
												   'from'   => 'pfields_data',
												   'where'  => 'pf_key=\'gender\'' ) );
												
		if ( $gender['pf_id'] )
		{
			$queries = array( SQLVC::UpdateTwo( $gender ),
							  SQLVC::UpdateThree( $gender ) );
		
			foreach( $queries as $query )
			{
				if ( IPSSetUp::getSavedData('man') )
				{
					$query = trim( $query );
				
					/* Ensure the last character is a semi-colon */
					if ( substr( $query, -1 ) != ';' )
					{
						$query .= ';';
					}
				
					$output .= $query . "\n\n";
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
						$this->registry->output->addMessage("Converted Gender Field....");
					}
				}
			}
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '30001', 'pf' );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
		
		/* Next Page */
		$this->request['workact'] = 'permsAndBbcode';
	}
	
	/**
	* Sort out bbcode and permissions
	* 
	* @access	public
	* @param	int
	*/
	public function permsAndBbcode()
	{
		/* INIT */
		$options    = IPSSetUp::getSavedData('custom_options');
		$rootAdmins = $options['core'][30001]['rootAdmins'];
		
		/* First off, import default BBCode */
		$apps      = array();
		$bbcodeOld = array();
		$bbcodeNew = array();
		
		/* Load apps */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_applications' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$apps[ $row['app_directory'] ] = $row;
		}
		
		/* Load old codes */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'custom_bbcode_old' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$bbcodeOld[ $row['bbcode_tag'] ] = $row;
		}
		
		/* Add 'em */
		foreach( $apps as $dir => $data )
		{
			if ( file_exists( IPSLib::getAppDir( $dir ) .  '/xml/' . $dir . '_bbcode.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------
			
				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/bbcode.php' );
				$bbcode = new admin_core_posts_bbcode();
				$bbcode->makeRegistryShortcuts( $this->registry );

				$bbcode->bbcodeImportDo( file_get_contents( IPSLib::getAppDir( $dir ) .  '/xml/' . $dir . '_bbcode.xml' ) );
			}
		
			if ( file_exists( IPSLib::getAppDir( $dir ) .  '/xml/' . $dir . '_mediatag.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------
			
				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/media.php' );
				$bbcode = new admin_core_posts_media();
				$bbcode->makeRegistryShortcuts( $this->registry );

				$bbcode->doMediaImport( file_get_contents( IPSLib::getAppDir( $dir ) .  '/xml/' . $dir . '_mediatag.xml' ) );
			}
		}
		
		/* Load current code */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'custom_bbcode' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$bbcodeCurrent[ $row['bbcode_tag'] ] = $row;
			
			//-----------------------------------------
			// Need to take into account aliases too!
			//-----------------------------------------
			
			if( $row['bbcode_aliases'] )
			{
				$aliases	= explode( ',', $row['bbcode_aliases'] );
				
				if( count($aliases) )
				{
					foreach( $aliases as $alias )
					{
						$bbcodeCurrent[ $alias ] = $row;
					}
				}
			}
		}
		
		if ( count( $bbcodeOld ) )
		{
			foreach( $bbcodeOld as $tag => $row )
			{
				if ( ! $bbcodeCurrent[ $row['bbcode_tag'] ] )
				{
					$bbcodeNew[ $row['bbcode_tag'] ] = $row;
				}
			}
		}
		
		$this->registry->output->addMessage("BBCode tags upgraded....");
		
		/* Now see if there's anything we need to move back over */
		if ( count( $bbcodeNew ) )
		{
			foreach( $bbcodeNew as $tag => $data )
			{
				$bbarray = array(
								 'bbcode_title'             => $data['bbcode_title'],
								 'bbcode_desc'              => $data['bbcode_desc'],
								 'bbcode_tag'               => $data['bbcode_tag'],
								 'bbcode_replace'           => IPSText::safeslashes($data['bbcode_replace']),
								 'bbcode_useoption'         => $data['bbcode_useoption'],
								 'bbcode_example'           => $data['bbcode_example'],
								 'bbcode_switch_option'     => $data['bbcode_switch_option'],
								 'bbcode_menu_option_text'  => $data['bbcode_menu_option_text'],
								 'bbcode_menu_content_text' => $data['bbcode_menu_content_text'],
								 'bbcode_groups'			=> 'all',
								 'bbcode_sections'			=> 'all',
								 'bbcode_php_plugin'		=> '',
 								 'bbcode_parse'				=> 2,
 								 'bbcode_no_parsing'		=> 0,
 								 'bbcode_optional_option'	=> 0,
 								 'bbcode_aliases'			=> '',
 								 'bbcode_image'				=> ''
								);
								
				$this->DB->insert( 'custom_bbcode', $bbarray );
			}
		}
		
		/* OK, now onto permissions... */
		/* Insert basic perms for profiles and help */
		$this->DB->insert( 'permission_index', array( 'app'          => 'members',
													  'perm_type'    => 'profile_view',
													  'perm_type_id' => 1,
													  'perm_view'    => '*',
													  'perm_2'		 => '',
													  'perm_3'		 => '',
													  'perm_4'		 => '',
													  'perm_5'		 => '',
													  'perm_6'		 => '',
													  'perm_7'		 => '' ) );
													
		$this->DB->insert( 'permission_index', array( 'app'          => 'core',
													  'perm_type'    => 'help',
													  'perm_type_id' => 1,
													  'perm_view'    => '*',
													  'perm_2'		 => '',
													  'perm_3'		 => '',
													  'perm_4'		 => '',
													  'perm_5'		 => '',
													  'perm_6'		 => '',
													  'perm_7'		 => '' ) );
													
		/* And now calendars */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'cal_calendars' ) );
								
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			if ( strstr( $row['cal_permissions'], 'a:' ) )
			{
				$_perms = unserialize( stripslashes( $row['cal_permissions'] ) );
				
				if ( is_array( $_perms ) )
				{
					$_view  = ( $_perms['perm_read'] )  ? ',' . implode( ',', explode( ',', $_perms['perm_read'] ) ) . ',' : '';
					$_start = ( $_perms['perm_post'] )  ? ',' . implode( ',', explode( ',', $_perms['perm_post'] ) ) . ',' : '';
					$_nomod = ( $_perms['perm_nomod'] ) ? ',' . implode( ',', explode( ',', $_perms['perm_nomod'] ) ). ',' : '';
					
					$this->DB->insert( 'permission_index', array( 'app'          => 'calendar',
																  'perm_type'    => 'calendar',
																  'perm_type_id' => $row['cal_id'],
																  'perm_view'    => str_replace( ',*,', '*', $_view ),
																  'perm_2'		 => str_replace( ',*,', '*', $_start ),
																  'perm_3'		 => str_replace( ',*,', '*', $_nomod ),
																  'perm_4'		 => '',
																  'perm_5'		 => '',
																  'perm_6'		 => '',
																  'perm_7'		 => '' ) );
				}
				else
				{
					$this->DB->insert( 'permission_index', array( 'app'          => 'calendar',
																  'perm_type'    => 'calendar',
																  'perm_type_id' => $row['cal_id'],
																  'perm_view'    => '',
																  'perm_2'		 => '',
																  'perm_3'		 => '',
																  'perm_4'		 => '',
																  'perm_5'		 => '',
																  'perm_6'		 => '',
																  'perm_7'		 => '' ) );
				}
			}
			else
			{
				$this->DB->insert( 'permission_index', array( 'app'          => 'calendar',
															  'perm_type'    => 'calendar',
															  'perm_type_id' => $row['cal_id'],
															  'perm_view'    => '',
															  'perm_2'		 => '',
															  'perm_3'		 => '',
															  'perm_4'		 => '',
															  'perm_5'		 => '',
															  'perm_6'		 => '',
															  'perm_7'		 => '' ) );
			}
		}
		
		/* And now forums */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'forums' ) );
								
		$o = $this->DB->execute();
					
		while( $row = $this->DB->fetch( $o ) )
		{
			/* Do we need to tidy up the title? */
			if ( strstr( $row['name'], '&' ) )
			{
				$row['name'] = preg_replace( "#& #", "&amp; ", $row['name'] );
				
				$this->DB->update( 'forums', array( 'name' => $row['name'] ), 'id=' . $row['id'] );
			}
			
			if ( strstr( $row['permission_array'], 'a:' ) )
			{
				$_perms = unserialize( stripslashes( $row['permission_array'] ) );
				
				if ( is_array( $_perms ) )
				{
					$_view     = ( $_perms['show_perms'] )     ? ',' . implode( ',', explode( ',', $_perms['show_perms'] ) ) . ',' : '';
					$_read     = ( $_perms['read_perms'] )     ? ',' . implode( ',', explode( ',', $_perms['read_perms'] ) ) . ',' : '';
					$_reply    = ( $_perms['reply_perms'] )    ? ',' . implode( ',', explode( ',', $_perms['reply_perms'] ) ) . ',' : '';
					$_start    = ( $_perms['start_perms'] )    ? ',' . implode( ',', explode( ',', $_perms['start_perms'] ) ) . ',' : '';
					$_upload   = ( $_perms['upload_perms'] )   ? ',' . implode( ',', explode( ',', $_perms['upload_perms'] ) ) . ',' : '';
					$_download = ( $_perms['download_perms'] ) ? ',' . implode( ',', explode( ',', $_perms['download_perms'] ) ) . ',' : '';
					
					$this->DB->insert( 'permission_index', array( 'app'          => 'forums',
																  'perm_type'    => 'forum',
																  'perm_type_id' => $row['id'],
																  'perm_view'    => str_replace( ',*,', '*', $_view ),
																  'perm_2'		 => str_replace( ',*,', '*', $_read ),
																  'perm_3'		 => str_replace( ',*,', '*', $_reply ),
																  'perm_4'		 => str_replace( ',*,', '*', $_start ),
																  'perm_5'		 => str_replace( ',*,', '*', $_upload ),
																  'perm_6'		 => str_replace( ',*,', '*', $_download ),
																  'perm_7'		 => '' ) );
				}
				else
				{
					$this->DB->insert( 'permission_index', array( 'app'          => 'forums',
																  'perm_type'    => 'forum',
																  'perm_type_id' => $row['id'],
																  'perm_view'    => '',
																  'perm_2'		 => '',
																  'perm_3'		 => '',
																  'perm_4'		 => '',
																  'perm_5'		 => '',
																  'perm_6'		 => '',
																  'perm_7'		 => '' ) );
																
					IPSSetUp::addLogMessage( "Skipped perms (no array) for forum id: " . $row['id'], '30001', 'core' );
				}
			}
			else
			{
				$this->DB->insert( 'permission_index', array( 'app'          => 'forums',
															  'perm_type'    => 'forum',
															  'perm_type_id' => $row['id'],
															  'perm_view'    => '',
															  'perm_2'		 => '',
															  'perm_3'		 => '',
															  'perm_4'		 => '',
															  'perm_5'		 => '',
															  'perm_6'		 => '',
															  'perm_7'		 => '' ) );
															
				IPSSetUp::addLogMessage( "Skipped perms (no array) for forum id: " . $row['id'], '30001', 'core' );
			}
		}
		
		$this->registry->output->addMessage("Permission indexes built....");
		
		/* Fix up forum moderators */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'moderators' ) );
		
		$o = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $o ) )
		{
			$this->DB->update( 'moderators', array( 'forum_id' => ',' . IPSText::cleanPermString( $r['forum_id'] ) . ',' ), 'mid=' . $r['mid'] );
		}
		
		$this->registry->output->addMessage("Forum moderators updated....");
		
		/* Root admin reset? */
		if ( $rootAdmins )
		{
			/* Find all admin groups */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'groups',
									 'where'  => 'g_id != ' . $this->settings['admin_group'] . ' AND g_access_cp=1' ) );
									
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				/* Insert blank perm row */
				$this->DB->insert( 'admin_permission_rows', array( 'row_id'         => $row['g_id'],
																   'row_id_type'    => 'group',
																   'row_perm_cache' => serialize( array() ),
																   'row_updated'	=> time() ) );
			}
			
			$this->registry->output->addMessage("Non Root Admin groups restricted....");
		}
		
		/* Report center reset */
		$canReport	= array();
		$canView	= array();
		
		$this->DB->build( array( 'select' => 'g_id, g_view_board, g_access_cp, g_is_supmod',
								 'from'   => 'groups' ) );
		
		$o = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $o ) )
		{
			if( $r['g_access_cp'] OR $r['g_is_supmod'] )
			{
				$canView[]	= $r['g_id'];
			}
			
			if( $r['g_view_board'] AND $r['g_id'] != $this->settings['guest_group'] )
			{
				$canReport[]	= $r['g_id'];
			}
		}
		
		$this->DB->update( 'rc_classes', array( 'group_can_report' => ',' . implode( ',', $canReport ) . ',', 'mod_group_perm' => ',' . implode( ',', $canView ) . ',' ) );

		/* Next Page */
		$this->request['workact'] = 'skinlang';
	}
	
	/**
	 * Export skin and languages
	 *
	 * @access	public
	 */
	public function exportSkinLang()
	{
		/* INIT */
		$start     = intval( $this->request['st'] );
		$converted = 0;
		$options   = IPSSetUp::getSavedData('custom_options');
		$_doSkin   = $options['core'][30001]['exportSkins'];
		$_doLang   = $options['core'][30001]['exportLangs'];
		
		/* Doing anything? */
		if ( ! $_doSkin AND ! $_doLang )
		{
			$this->registry->output->addMessage( "Nothing to export" );

			/* Next Page */
			$this->request['workact'] = 'pms';
			return;
		}
		
		/* Ok... */
		if ( ! $start )
		{
			/* Do langs.. */
			if ( $_doLang )
			{
				if ( ! is_dir( IPS_CACHE_PATH . 'cache/previousLangFiles' ) )
				{
					if ( @mkdir( IPS_CACHE_PATH . 'cache/previousLangFiles', 0777 ) )
					{
						@chmod( IPS_CACHE_PATH . 'cache/previousLangFiles', 0777 );
					}
				}
				
				try
				{
					foreach( new DirectoryIterator( IPS_CACHE_PATH . 'cache/lang_cache' ) as $file )
					{
						if ( ! $file->isDot() AND $file->isDir() )
						{
							$name = $file->getFilename();
							
							if ( substr( $name, 0, 1 ) != '.' )
							{
								$this->registry->output->addMessage( "Moved Language Directory: {$name}" );
								
								@rename( IPS_CACHE_PATH . 'cache/lang_cache/' . $name, IPS_CACHE_PATH . 'cache/previousLangFiles/' . $name );
							}
						}
					}
				} catch ( Exception $e ) {}
			}
		}
		
		/* Doing skins? */
		if ( $_doSkin )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_sets',
									 'where'  => 'set_skin_set_id > ' . $start,
									 'limit'  => array( 0, 1 ),
									 'order'  => 'set_skin_set_id ASC' ) );
								
			$this->DB->execute();
		
			$set = $this->DB->fetch();
		
			if ( ! $set )
			{
				$this->request['st'] = 0;

				/* All done.. */
				$this->registry->output->addMessage( "No more skin sets to export" );

				/* Next Page */
				$this->request['workact'] = 'pms';
				return;
			}
			else
			{
				if ( ! is_dir( IPS_CACHE_PATH . 'cache/previousSkinFiles' ) )
				{
					if ( @mkdir( IPS_CACHE_PATH . 'cache/previousSkinFiles', 0777 ) )
					{
						@chmod( IPS_CACHE_PATH . 'cache/previousSkinFiles', 0777 );
					}
				}
				
				$safeName = IPSText::alphanumericalClean( $set['set_name'] );
				$dirPath  = IPS_CACHE_PATH . 'cache/previousSkinFiles/' . $safeName;
				
				if ( @mkdir( $dirPath, 0777 ) )
				{
					@chmod( $dirPath, 0777 );
				}
				
				if ( is_dir( $dirPath ) )
				{
					/* Export CSS */
					if ( @mkdir( $dirPath . '/css', 0777 ) )
					{
						@chmod( $dirPath . '/css', 0777 );
					}
					
					@file_put_contents( $dirPath . '/css/css.css', $set['set_cache_css'] );
					
					/* Export Wrapper */
					if ( @mkdir( $dirPath . '/wrapper', 0777 ) )
					{
						@chmod( $dirPath . '/wrapper', 0777 );
					}
					
					@file_put_contents( $dirPath . '/wrapper/wrapper.html', $set['set_wrapper'] );
					
					/* Export Templates */
					if ( @mkdir( $dirPath . '/templates', 0777 ) )
					{
						@chmod( $dirPath . '/templates', 0777 );
					}
					
					$this->DB->build( array( 'select' => '*',
											 'from'   => 'skin_templates_old',
											 'where'  => 'set_id=' . $set['set_skin_set_id'],
											 'order'  => 'func_name ASC' ) );
											
					$this->DB->execute();
					
					while( $row = $this->DB->fetch() )
					{
						$_groupName = IPSText::alphanumericalClean( $row['group_name'] );
						$_bitName   = IPSText::alphanumericalClean( $row['func_name'] );
						
						/* Make section dir */
						if ( @mkdir( $dirPath . '/templates/' . $_groupName, 0777 ) )
						{
							@chmod( $dirPath . '/templates/' . $_groupName, 0777 );
						}
						
						@file_put_contents( $dirPath . '/templates/' . $_groupName . '/' . $_bitName . '.html', $row['section_content'] );
					}
				}
				
				/* Set ID */
				$this->request['st'] = $set['set_skin_set_id'];
				
				/* We did some, go check again.. */
				$this->registry->output->addMessage( $set['set_name'] . " Exported" );

				/* Next Page */
				$this->request['workact'] = 'skinlang';
				return;
			}
		}
		
		/* We did some, go check again.. */
		$this->registry->output->addMessage( 'No skins or languages to export' );

		/* Next Page */
		$this->request['workact'] = 'pms';
		return;
	}
	
	/**
	* Convert PMs
	* 
	* @access	public
	*/
	public function convertPms()
	{
		/* INIT */
		$pergo     = 200;
		$start     = intval( $this->request['st'] );
		$converted = 0;
		$seen      = 0;
		
		$options   = IPSSetUp::getSavedData('custom_options');
		$skipPms   = $options['core'][30001]['skipPms'];
		
		/* Skipping? */
		if ( $skipPms )
		{
			/* Update all members */
			$this->DB->update( 'members', array( 'msg_count_reset' => 1, 'msg_count_new' => 0 ) );
			
			/* add a message */
			$this->registry->output->addMessage( "Skipping PM Conversion" );
			
			$this->request['workact'] = 'contacts';
			$this->request['st']      = 0;
			return;
		}
		
		/* Select max topic ID thus far */
		$_tmp = $this->DB->buildAndFetch( array( 'select' => 'MAX(mt_id) as max',
												 'from'   => 'message_topics' ) );
												
		$topicID = intval( $_tmp['max'] );
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'message_text',
								 'order'  => 'msg_id ASC',
								 'limit'  => array( $start, $pergo ) ) );
								
		$o = $this->DB->execute();
		
		while( $post = $this->DB->fetch( $o ) )
		{
			$seen++;
			
			/* Make sure all data is valid */
			if ( intval( $post['msg_sent_to_count'] ) < 1 )
			{
				continue;
			}
			
			/* a little set up */
			$oldTopics = array();
			
			/* Now fetch all topics */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'message_topics_old',
									 'where'  => 'mt_msg_id=' . intval( $post['msg_id'] ) ) );
									
			$t = $this->DB->execute();
			
			while( $topic = $this->DB->fetch( $t ) )
			{
				/* Got any data? */
				if ( ! $topic['mt_from_id'] OR ! $topic['mt_to_id'] )
				{
					continue;
				}
				
				$oldTopics[ $topic['mt_id'] ] = $topic;  # Luke added that space. That's his first contribution to the code vaults at IPS.
			}
			
			/* Fail safe */
			if ( ! count( $oldTopics ) )
			{
				continue;
			}
			
			/* Increment number */
			$topicID++;
			
			/* Add in the post */
			$this->DB->insert( 'message_posts', array( 'msg_topic_id'      => $topicID,
													   'msg_date'          => $post['msg_date'],
													   'msg_post'          => $post['msg_post'],
													   'msg_post_key'      => $post['msg_post_key'],
													   'msg_author_id'     => $post['msg_author_id'],
													   'msg_ip_address'    => $post['msg_ip_address'],
													   'msg_is_first_post' => 1 ) );
			$postID = $this->DB->getInsertId();
			
			/* Define some stuff. "To" member is added last in IPB 2 */
			$_tmp       = $oldTopics;
			ksort( $_tmp );
			$topicData  = array_pop( $_tmp ); 
			$_invited   = array();
			$_seenOwner = array();
			$_isDeleted = 0;
			
			/* Add the member rows */
			foreach( $oldTopics as $mt_id => $data )
			{
				/* Prevent SQL error with unique index: Seen the owner ID already? */
				if ( $_seenOwner[ $data['mt_owner_id'] ] )
				{
					continue;
				}
				
				$_seenOwner[ $data['mt_owner_id'] ] = $data['mt_owner_id'];
				
				/* Build invited - does not include 'to' person */
				if ( $data['mt_owner_id'] AND ( $post['msg_author_id'] != $data['mt_owner_id'] ) AND ( $topicData['mt_to_id'] != $data['mt_owner_id'] ) )
				{
					$_invited[ $data['mt_owner_id'] ] = $data['mt_owner_id'];
				}
				
				$_isSent  = ( $data['mt_vid_folder'] == 'sent' )   ? 1 : 0;
				$_isDraft = ( $data['mt_vid_folder'] == 'unsent' ) ? 1 : 0;
				
				$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $data['mt_owner_id'],
																	'map_topic_id'    => $topicID,
																	'map_folder_id'   => ( $_isDraft ) ? 'drafts' : 'myconvo',
																	'map_read_time'   => ( $data['mt_user_read'] ) ? $data['mt_user_read'] : ( $data['mt_read'] ? time() : 0 ),
																	'map_user_active' => 1,
																	'map_user_banned' => 0,
																	'map_has_unread'  => 0, //( $data['mt_read'] ) ? 0 : 1,
																	'map_is_system'   => 0,
																	'map_is_starter'  => ( $data['mt_owner_id'] == $post['msg_author_id'] ) ? 1 : 0 ) );
				
			}
			
			/* Now, did we see the author? If not, add them too but as inactive */
			if ( ! $_seenOwner[ $post['msg_author_id'] ] )
			{
				$_isDeleted = 1;
				
				/*$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $post['msg_author_id'],
																	'map_topic_id'    => $topicID,
																	'map_folder_id'   => 'myconvo',
																	'map_read_time'   => 0,
																	'map_user_active' => 0,
																	'map_user_banned' => 0,
																	'map_has_unread'  => 0,
																	'map_is_system'   => 0,
																	'map_is_starter'  => 1 ) );*/
			}
			
			$_isSent  = ( $topicData['mt_vid_folder'] == 'sent' )   ? 1 : 0;
			$_isDraft = ( $topicData['mt_vid_folder'] == 'unsent' ) ? 1 : 0;
			
			/* Add the topic */
			$this->DB->insert( 'message_topics', array( 'mt_id'			     => $topicID,
														'mt_date'		     => $topicData['mt_date'],
														'mt_title'		     => $topicData['mt_title'],
														'mt_starter_id'	     => $post['msg_author_id'],
														'mt_start_time'      => $post['msg_date'],
														'mt_last_post_time'  => $post['msg_date'],
														'mt_invited_members' => serialize( array_keys( $_invited ) ),
														'mt_to_count'		 => count(  array_keys( $_invited ) ) + 1,
														'mt_to_member_id'	 => $topicData['mt_to_id'],
														'mt_replies'		 => 0,
														'mt_last_msg_id'	 => $postID,
														'mt_first_msg_id'    => $postID,
														'mt_is_draft'		 => $_isDraft,
														'mt_is_deleted'		 => $_isDeleted,
														'mt_is_system'		 => 0 ) );
														
			$converted++;
		}
		
		/* What to do? */
		if ( $seen )
		{
			$this->request['st'] = $start + $pergo;
			
			/* We did some, go check again.. */
			$this->registry->output->addMessage( "Checked " . $this->request['st'] . " PMs so far. This cycle: $converted Private Messages converted into conversations.." );
			
			/* Re-do Page */
			$this->request['workact'] = 'pms';
		}
		else
		{
			/* Update all members */
			$this->DB->update( 'members', array( 'msg_count_reset' => 1 ) );
			
			/* Nope, nothing to do - we're done! */
			/* Next Page */
			$this->request['workact'] = 'contacts';
			$this->request['st']      = 0;
		}
	}
	
	/**
	* Convert Block lists
	* 
	* @access	public
	*/
	public function convertBlockLists()
	{
		/* INIT */
		$pergo     = 200;
		$start     = intval( $this->request['st'] );
		$converted = 0;
		$seen      = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'members',
								 'where'  => 'ignored_users IS NOT NULL',
								 'order'  => 'member_id ASC',
								 'limit'  => array( $start, $pergo ) ) );
								
		$o = $this->DB->execute();
		
		while( $member = $this->DB->fetch( $o ) )
		{
			$seen++;
			
			/* Got anything? */
			if ( strstr( $member['ignored_users'], ',' ) )
			{
				$ignored = explode( ',', $member['ignored_users'] );
			}
			
			if ( ! is_array( $ignored ) )
			{
				continue;
			}
			
			/* Add it to the table */
			foreach( $ignored as $iid )
			{
				if ( ! $iid )
				{
					continue;
				}
				
				$this->DB->insert( 'ignored_users', array( 'ignore_owner_id'  => $member['member_id'],
														   'ignore_ignore_id' => $iid,
														   'ignore_topics'    => 1 ) );
			}
			
			$converted++;
		}
		
		/* What to do? */
		if ( $seen )
		{
			$this->request['st'] = $start + $pergo;
			
			/* We did some, go check again.. */
			$this->registry->output->addMessage( "$converted member's ignore list converted." );
			
			/* Re-do Page */
			$this->request['workact'] = 'contacts';
		}
		else
		{
			/* Nope, nothing to do - we're done! */
			/* Next Page */
			$this->request['workact'] = 'pmblock';
			$this->request['st']      = 0;
		}
	}
	
	/**
	* Convert Block lists
	* 
	* @access	public
	*/
	public function convertPmBlockLists()
	{
		/* INIT */
		$pergo     = 200;
		$start     = intval( $this->request['st'] );
		$converted = 0;
		$seen      = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'contacts',
								 'where'  => 'allow_msg=0',
								 'order'  => 'id ASC',
								 'limit'  => array( $start, $pergo ) ) );
								
		$o = $this->DB->execute();
		
		while( $contact = $this->DB->fetch( $o ) )
		{
			$seen++;
			
			/* Already got an entry for this contact? */
			$test = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'ignored_users',
													 'where'  => 'ignore_owner_id=' . intval( $contact['member_id'] ) ) );
													
			/* Got anything? */
			if ( $test['ignore_owner_id'] )
			{
				$this->DB->update( 'ignored_users', array( 'ignore_messages' => 1 ), 'ignore_id=' . $test['ignore_id'] );
			}
			else
			{
				$this->DB->insert( 'ignored_users', array( 'ignore_owner_id'  => $contact['member_id'],
														   'ignore_ignore_id' => $contact['contact_id'],
														   'ignore_messages'  => 1 ) );
			}
			
			$converted++;
		}
		
		/* What to do? */
		if ( $seen )
		{
			$this->request['st'] = $start + $pergo;
			
			/* We did some, go check again.. */
			$this->registry->output->addMessage( "$converted member's PM block list converted." );
			
			/* Re-do Page */
			$this->request['workact'] = 'pmblock';
		}
		else
		{
			/* Nope, nothing to do - we're done! */
			/* Next Page */
			$this->request['workact'] = 'finish';
		}
	}
	
	/**
	* Finish up conversion stuff
	* 
	* @access	public
	* @param	int
	*/
	public function finish()
	{
		$options	= IPSSetUp::getSavedData('custom_options');
		$skipPms	= $options['core'][30001]['skipPms'];
		$output		= array();
		
		/* Emoticons */
		if ( file_exists( DOC_IPS_ROOT_PATH . 'style_emoticons/default' ) )
		{
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'style_emoticons/default' ) as $file )
				{
					if ( ! $file->isDot() )
					{
						$_name = $file->getFileName();
            	
						/* Annoyingly, isDot doesn't match .svn, etc */
						if ( substr( $_name, 0, 1 ) == '.' )
						{
							continue;
						}
						
						if ( @copy( DOC_IPS_ROOT_PATH . 'style_emoticons/default/' . $_name, IPS_PUBLIC_PATH . 'style_emoticons/default/' . $_name ) )
						{
							$output[] = "Emoticon: Copying $_name...";
						}
						else
						{
							$output[] = "Could not copy $_name - move it manually post-installation";
						}
					}
				}
			} catch ( Exception $e ) {}
		}
		
		/* LOG IN METHODS */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'login_methods' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$loginMethods[ $row['login_folder_name'] ] = $row;
		}
		
		$count   = 6;
		$recount = array( 'internal'   => 1,
						  'openid'     => 2,
						  'ipconverge' => 3,
						  'ldap'       => 4,
						  'external'   => 5 );
						
		/* Fetch XML */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );
		
		$xml->load( IPS_ROOT_PATH . 'setup/xml/loginauth.xml' );

		foreach( $xml->fetchElements( 'login_methods' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			$data['login_order'] = ( isset( $recount[ $data['login_folder_name'] ] ) ) ? $recount[ $data['login_folder_name'] ] : ++$count;
			
			unset( $data['login_id'] );
			
			if ( ! $loginMethods[ $data['login_folder_name'] ] )
			{
				$this->DB->insert( 'login_methods', $data );
			}
			else
			{
				$this->DB->update( 'login_methods', array( 'login_order' => $data['login_order'] ), 'login_folder_name=\'' . $data['login_folder_name'] . '\'' );
			}
		}
		
		/* Reset member languages and skins */
		$this->DB->update( 'members', array( 'language' => IPSLib::getDefaultLanguage(), 'skin' => 0 ) );
		
		/* Empty caches */
		$this->DB->delete( 'cache_store', "cs_key='forum_cache'");
		$this->DB->delete( 'cache_store', "cs_key='skin_id_cache'");
		
		/* Empty other tables */
		$this->DB->delete( 'skin_cache' );
		$this->DB->delete( 'skin_templates_cache' );
		
		/* Reset admin permissions */
		$this->DB->update( 'admin_permission_rows', array( 'row_perm_cache' => '' ) );
		
		/* Drop Tables */
		$this->DB->dropTable( 'contacts' );
		$this->DB->dropTable( 'skin_macro' );
		$this->DB->dropTable( 'skin_template_links' );
		$this->DB->dropTable( 'skin_templates_old' );
		$this->DB->dropTable( 'skin_sets' );
		$this->DB->dropTable( 'languages' );
		$this->DB->dropTable( 'topics_read' );
		$this->DB->dropTable( 'topic_markers' );
		$this->DB->dropTable( 'acp_help' );
		$this->DB->dropTable( 'members_converge' );
		$this->DB->dropTable( 'member_extra' );
		$this->DB->dropTable( 'custom_bbcode_old' );
		$this->DB->dropTable( 'admin_sessions' );
		$this->DB->dropTable( 'components' );
		$this->DB->dropTable( 'admin_permission_keys' );
		
		if ( ! $skipPms )
		{
			$this->DB->dropTable( 'message_text' );
			$this->DB->dropTable( 'message_topics_old' );
		}
		
		$message	= ( is_array( $output ) AND count($output) ) ? implode( "<br />", $output ) . '<br />' : '';
		
		$this->registry->output->addMessage( $message . "SQL clean up finished....");
		
		/* Last function, so unset workact */
		$this->request['workact'] = '';
	}
}
	
?>