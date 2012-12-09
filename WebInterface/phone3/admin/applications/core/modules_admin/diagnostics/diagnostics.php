<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Diagnostic tools
 * Last Updated: $Date: 2009-08-30 23:34:46 -0400 (Sun, 30 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		1st march 2002
 * @version		$Revision: 5064 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_diagnostics_diagnostics extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Directory separator
	 *
	 * @access	private
	 * @var		string			Directory separator
	 */
	private $dir_split			= "/";
	
	/**
	 * Db tools
	 *
	 * @access	private
	 * @var		object
	 */
	private $dbTools;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_diagnostics');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=diagnostics&amp;section=diagnostics';
		$this->form_code_js	= $this->html->form_code_js	= 'module=diagnostics&section=diagnostics';
		
		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
		{
			$this->dir_split = "\\";
		}
		
		//-----------------------------------------
		// Some of these functions can take a while..
		//-----------------------------------------
		
		@set_time_limit(0);
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_system' ) );

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'dbindex':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'index_checker' );
				$this->_indexCheck();
			break;
				
			case 'dbchecker':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'database_checker' );
				$this->_dbCheck();
			break;			
				
			case 'whitespace':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'whitespace_checker' );
				$this->_whitespaceCheck();
			break;
				
			case 'filepermissions':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permission_checker' );
				$this->_permissionsCheck();
			break;
				
			case 'fileversions':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'version_checker' );
				$this->_versionCheck();
			break;
			
			/* Topic Marking */
			case 'tm_index':
				$this->_tmIndex();
			break;
			case 'tm_memberindex':
				$this->_tmMemberIndex();
			break;
			case 'tm_viewLog':
				$this->_tmViewLog();
			break;
			
			default:
				$this->_listFunctions();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Displays a log entry
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _tmViewLog()
	{
		/* INIT */
		$member_id 		  = intval( $this->request['member_id'] );
		$marker_microtime = base64_decode( trim( $this->request['_id'] ) );
		
		/* Fetch row */
		$data = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'core_topicmarker_debug',
												 'where'  => 'marker_member_id=' . $member_id . ' AND marker_microtime=\'' . $marker_microtime . '\'' ) );
												
		/* Format date */
		if ( $data )
		{
			$data['_marker_timestamp'] = $this->registry->class_localization->getDate( $data['marker_timestamp'], 'long' );
		}
		
		/* unserialize */
		if ( $data['marker_data_storage'] AND strstr( 'a:', $data['marker_data_storage'] ) )
		{
			$data['_marker_data_storage'] = unserialize( $data['marker_data_storage'] );
		}
		
		/* unserialize */
		if ( $data['marker_data_memory'] AND strstr( 'a:', $data['marker_data_memory'] ) )
		{
			$data['_marker_data_memory'] = unserialize( $data['marker_data_memory'] );
		}
		
		/* unserialize */
		if ( $data['marker_data_freezer'] AND strstr( 'a:', $data['marker_data_freezer'] ) )
		{
			$data['_marker_data_freezer'] = unserialize( $data['marker_data_freezer'] );
		}
		
		/* unserialize */
		foreach( array( 1,2,3,4,5 ) as $n )
		{
			if ( $data['marker_data_' . $n ] AND strstr( 'a:', $data['marker_data_' . $n ] ) )
			{
				$data['marker_data_' . $n ] = unserialize( $data['marker_data_' . $n ] );
			}
		}
		
		/* Print pop-up window */
		$this->registry->output->html .= $this->html->topicMarkers_viewLog( $data );
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Displays a member's session
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _tmMemberIndex()
	{
		/* INIT */
		$markers   			= array();
		$member_id 			= intval( $this->request['member_id'] );
		$marker_session_key = trim( $this->request['marker_session_key'] );
		$st 	   			= intval( $this->request['st'] );
		$perpage   			= 100;
		$query     			= array();
		$url       			= array();
		
		/* Load member */
		$member    = IPSMember::buildDisplayData( $member_id );
		
		/* Generate SQL */
		$query[]   = 't.marker_member_id=' . $member_id;
		$url[]     = 'member_id=' . $member_id;
		
		if ( $marker_session_key )
		{
			$query[] = 't.marker_session_key=\'' . $marker_session_key. '\'';
			$url[]   = 'marker_session_key=' . $marker_session_key;
		}
		
		/* Fetch count */
		$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
										  		  'from'   => 'core_topicmarker_debug t',
										  		  'where'  => implode( " AND ", $query ) ) );
										
		$pages = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['count'],
																	 'itemsPerPage'			=> $perpage,
																	 'currentStartValue'	=> $st,
																	 'baseUrl'				=> $this->settings['base_url'] . "&{$this->form_code}&do=" . $this->request['do'] . '&' . implode( "&", $url ),
														)		);
		/* Grab members */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_topicmarker_debug t',
								 'where'  => implode( " AND ", $query ),
								 'limit'  => array( $st, $perpage ),
								 'order'  => 't.marker_timestamp ASC, t.marker_microtime ASC' ) );
		$this->DB->execute();
		
		/* Barney and Luke rule the world <-- Debbie typed that */
		while( $row = $this->DB->fetch() )
		{
			$markers[] = $row;
		}
		
		/* Send to output */
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . "&{$this->form_code}&do=tm_index", "Topic Marker Debugging" );
		
		if ( count( $query ) > 1 )
		{
			$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . "&{$this->form_code}&do=tm_memberindex&member_id=" . $member_id, "Log Overview (Unfiltered)" );
		}
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . "&{$this->form_code}&do=tm_memberindex"  . '&' . implode( "&", $url ), "Log Overview" );
		
		$this->registry->output->html       .= $this->html->topicMarkers_membersIndex( $member, $markers, $pages );
	}
	
	/**
	 * List topic marking debugging index screen (shows members)
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _tmIndex()
	{
		/* INIT */
		$_debug  = array();
		$debug   = array();
		$members = array();
		
		/* Grab members */
		$this->DB->build( array( 'select' => 't.*, COUNT(*) as count, MAX(t.marker_timestamp) as max_ts',
								 'from'   => array( 'core_topicmarker_debug' => 't' ),
								 'group'  => 't.marker_member_id',
								 'order'  => 'm.members_l_display_name',
								 'add_join' => array( array( 'select' => 'm.*',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=t.marker_member_id',
															 'type'   => 'left' ) ) ) );
		$this->DB->execute();
		
		/* Barney and Luke rule the world <-- Debbie typed that */
		while( $row = $this->DB->fetch() )
		{
			$members[] = $row['marker_member_id'];
			$_debug[]  = $row;
		}
		
		/* Grab members */
		$loadedMembers = IPSMember::load( $members );
		
		/* Parse 'em */
		foreach( $_debug as $row )
		{
			$row['_memberData'] = IPSMember::buildDisplayData( $loadedMembers[ $row['marker_member_id'] ] );
			
			$debug[] = $row;
		}
		
		/* Send to output */
		$this->registry->output->html .= $this->html->topicMarkers_index( $debug );
	}
	
	/**
	 * Check file versions
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _versionCheck()
	{
		$file_versions		= array();
		$upgrade_history	= array();
		$latest_version		= array( 'upgrade_version_id' => '' );
		$file_versions		= $this->_versionDirRecurse( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) );

   		$this->DB->build( array( 'select' => '*', 'from' => 'upgrade_history', 'where' => "upgrade_app='core'", 'order' => 'upgrade_version_id DESC', 'limit' => array(0, 5) ) );
   		$this->DB->execute();
   		
   		while( $r = $this->DB->fetch() )
   		{
   			if ( $r['upgrade_version_id'] > $latest_version['upgrade_version_id'] )
   			{
   				$latest_version = $r;
   			}
   			
   			$upgrade_history[] = $r;
   		}
   		
		//-----------------------------------------
		// Got real version number?
		//-----------------------------------------

		$version['version']			= (IPB_VERSION == '3.0.3') ? $latest_version['upgrade_version_human'] : IPB_VERSION;
		$version['version_full']	= (IPB_LONG_VERSION == '30010') ? $latest_version['upgrade_version_id'] : IPB_LONG_VERSION;

		//-----------------------------------------
		// Version History
		//-----------------------------------------
			
		if( count($upgrade_history) )
		{
			foreach( $upgrade_history as $r )
			{
				$r['_date']		= ipsRegistry::getClass( 'class_localization')->getDate( $r['upgrade_date'], 'SHORT' );
				
				$thiscontent	.= $this->html->acp_version_history_row( $r );
			}
		}
				
		$this->registry->output->html .= $this->html->versionCheckerResults( $version, $thiscontent, $file_versions );
	}
	
	/**
	 * Check file versions
	 *
	 * @access	private
	 * @param	string		Directory to check
	 * @return	array 		Files => Versions
	 */
	private function _versionDirRecurse($dir)
	{
		$skip_dirs	= array( 'public', 
							'cache',  
							'uploads', 
							'images', 
							'i18n', 
							'PEAR', 
							'hooks', 
							'facebook-client', 
							'FirePHPCore', 
							'facebook', 
							'resources',
							'applications_addon', 
							'js', 
							'skin_cp',
							'install',
							'upgrade',
							'Auth'
							);

		$skip_files	= array( 'conf_global.php', 
							'conf.php',
							$this->settings['sql_driver'] . '_updates.php',
							$this->settings['sql_driver'] . '_install.php',
							$this->settings['sql_driver'] . '_fulltext.php',
							'versionnumbers.php',
							'recaptcha.php',
							'classGraph.php',		// This is Wizzy's
							);
		
		$files		= array();

		try
		{
			foreach( new DirectoryIterator( $dir ) as $directory )
			{
				if( $directory->isDot() )
				{
					continue;
				}
        	
	    		if ( strpos( $directory->getFilename(), '_' ) === 0 OR strpos( $directory->getFilename(), '.' ) === 0 )
	    		{
			    	continue;
			    }
        	
				$newpath	= $dir . $this->dir_split . $directory->getFilename();
				$level		= explode( $this->dir_split, $newpath );
        	
				if ( is_dir($newpath) && !in_array( $directory->getFilename(), $skip_dirs ) )
				{
					$files = array_merge( $files, $this->_versionDirRecurse($newpath) );
				}
				else
				{
					if ( strpos( $directory->getFilename(), ".php" ) !== false && !is_dir( $newpath ) && !in_array( $directory->getFilename(), $skip_files ) )
					{
						$file = file_get_contents($newpath);
        	
						preg_match( "#IP\.Board v([\d\.]+?)\\n#i", $file, $matches );
						
						$files[ $newpath ] = isset($matches[1]) ? $matches[1] : '';
					}
				}
			}
		} catch ( Exception $e ) {}

		return $files;
	}
	
	/**
	 * Check file permissions
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _permissionsCheck()
	{		
		$checkdirs	= array(
							'public',
							'public' . $this->dir_split . 'style_images', 
							'public' . $this->dir_split . 'style_css', 
							'public' . $this->dir_split . 'style_emoticons', 
							'cache', 
							'cache' . $this->dir_split . 'skin_cache', 
							'cache' . $this->dir_split . 'lang_cache', 
							'cache' . $this->dir_split . 'tmp', 
							'cache' . $this->dir_split . 'openid', 
							'uploads',
							'uploads' . $this->dir_split . 'profile', 
							);
	
		//-----------------------------------------		
		// Get language directories
		//-----------------------------------------

		if( is_array( $this->cache->getCache('lang_data') ) && count( $this->cache->getCache('lang_data') ) )
		{
			foreach( $this->cache->getCache('lang_data') as $v )
			{
				$this_lang		= 'cache' . $this->dir_split . 'lang_cache' . $this->dir_split . $v['lang_id'];
				$checkdirs[]	= $this_lang;
				
				try
				{
					foreach( new DirectoryIterator( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . $this->dir_split . $this_lang ) as $file )
					{
						if( $file->isFile() )
						{
							$checkdirs[] = $this_lang . $this->dir_split . $file->getFilename();
						}
					}
				} catch ( Exception $e ) {}
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$this_lang		= 'cache' . $this->dir_split . 'lang_cache' . $this->dir_split . $v['lang_id'];
				$checkdirs[]	= $this_lang;
				
				try
				{
					foreach( new DirectoryIterator( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . $this->dir_split . $this_lang ) as $file )
					{
						if( $file->isFile() )
						{
							$checkdirs[] = $this_lang . $this->dir_split . $file->getFilename();
						}
					}
				} catch ( Exception $e ) {}		
			}
		}
		
		//-----------------------------------------		
		// Get emoticon directories
		//-----------------------------------------
				
		if( is_array( $this->cache->getCache('emoticons') ) && count( $this->cache->getCache('emoticons') ) )
		{
			foreach( $this->cache->getCache('emoticons') as $v )
			{
				$checkdirs[] = 'public' . $this->dir_split . 'style_emoticons' . $this->dir_split . $v['emo_set'];
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'emo_set', 'from' => 'emoticons' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$checkdirs[] = 'public' . $this->dir_split . 'style_emoticons' . $this->dir_split . $v['emo_set'];
			}
		}
		
		//-----------------------------------------		
		// Get skin directories
		//-----------------------------------------
				
		$skin_dirs = array();
		
		if( is_array( $this->cache->getCache('skin_id_cache') ) && count( $this->cache->getCache('skin_id_cache') ) )
		{
			foreach( $this->cache->getCache('skin_id_cache') as $k => $v )
			{
				if( $k == 1 && !IN_DEV )
				{
					continue;
				}
				
				$checkdirs[]	= 'cache' . $this->dir_split . 'skin_cache' . $this->dir_split . 'cacheid_' . $v['set_id'];
				$skin_dirs[]	= $v['set_skin_set_id'];
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'set_id', 'from' => 'skin_collections' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$checkdirs[]	= 'cache' . $this->dir_split . 'skin_cache' . $this->dir_split . 'cacheid_' . $v['set_id'];
				$skin_dirs[]	= $v['set_skin_set_id'];
			}
		}
		
		//-----------------------------------------		
		// Get skin files
		//-----------------------------------------
		
		$this->DB->build( array(
										'select'	=> $this->DB->buildDistinct( 'template_group' ),
										'from'		=> 'skin_templates',
										'group'		=> 'template_group',
							)		);
		$this->DB->execute();
		
		while( $v = $this->DB->fetch() )
		{
			foreach( $skin_dirs as $dir )
			{
				$checkdirs[] = 'cache' . $this->dir_split . 'skin_cache' . $this->dir_split . 'cacheid_' . $dir . $this->dir_split . $v['group_name'] . '.php';
			}
		}
		
		$checkdirs	= array_unique($checkdirs);
		$output		= array();
		
		foreach( $checkdirs as $dir_to_check )
		{
			if( !file_exists( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . $this->dir_split . $dir_to_check ) )
			{
				# Could be skin files from custom skins for components they don't own
				# or they could be using safe_mode skins
				# Make sure skin_cache still shows up though...
				
				if( !strpos( $dir_to_check, 'skin_' ) OR !strpos( $dir_to_check, '.php' ) )
				{
					$output[] = "<span style='color:red;font-weight:bold;'>{$this->lang->words['d_p404']} ". rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . $this->dir_split . $dir_to_check . "</span>";
				}
			}
			else if( !is_writeable( DOC_IPS_ROOT_PATH . $this->dir_split . $dir_to_check ) )
			{
				$output[] = "<span style='color:red;font-weight:bold;'>{$this->lang->words['d_pno']} ". rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . $this->dir_split . $dir_to_check . "</span>";
			}
			else if( is_writeable( DOC_IPS_ROOT_PATH . $this->dir_split . $dir_to_check ) )
			{
				$output[] = "<span style='color:green;'>" . rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . $this->dir_split . $dir_to_check . " {$this->lang->words['d_pyes']}</span>";
			}
		}
		
		$this->registry->output->html .= $this->html->permissionsResults( $output );
	}
	
	
	/**
	 * Whitespace checking
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _whitespaceCheck()
	{
		$files_with_junk	= array();
		$files_with_junk	= $this->_whitespaceDirRecurse( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) );
		//$files_with_junk	= $this->_whitespaceDirRecurse( '/var/' );
		
		$this->registry->output->html .= $this->html->whitespaceResults( $files_with_junk );
	}
	
	
	/**
	 * Recurse over a directory
	 *
	 * @access	private
	 * @param	string		Directory to check
	 * @return	array 		Array of files with whitespace in them
	 */
	private function _whitespaceDirRecurse( $dir )
	{
		$skip_dirs	= array( 
							'uploads', 
							'gallery_setup', 
							'blog_setup', 
							'idm_setup',
							'public', 
							'js', 
							'images', 
							);

		$files	= array();
		
		try
		{
			foreach( new DirectoryIterator( $dir ) as $directory )
			{
				if( $directory->isDot() )
				{
					continue;
				}
        	
	    		if ( strpos( $directory->getFilename(), '_' ) === 0 OR strpos( $directory->getFilename(), '.' ) === 0 )
	    		{
			    	continue;
			    }
        	
				$newpath	= $dir . $this->dir_split . $directory->getFilename();
				$level		= explode( $this->dir_split, $newpath );
        	
				if ( is_dir($newpath) && !in_array( $directory->getFilename(), $skip_dirs ) )
				{
					$files = array_merge( $files, $this->_whitespaceDirRecurse($newpath) );
				}
				else
				{
					if ( strpos( $directory->getFilename(), ".php" ) !== false && !is_dir( $newpath ) )
					{
						$file			= file_get_contents($newpath);
						$has_whitespace	= false;
						
						if( substr( ltrim($file), 0, 3 ) == '<?php' AND substr( $file, 0, 3 ) != '<?php' )
						{
							$has_whitespace	= true;
						}
						else if( substr( rtrim($file), -2 ) == '?>' AND substr( $file, -2 ) != '?>' )
						{
							$has_whitespace	= true;
						}
        	
						if( $has_whitespace )
						{
							$files[] = $newpath;
						}
					}
				}
			}
		} catch ( Exception $e ) {}
		
		return $files;
	}
	
	/**
	 * Table and index checker basic stuff
	 *
	 * @access	private
	 * @param	void
	 * @return	array 	Table files
	 */
	private function _getDbTools()
	{
		//-----------------------------------------
		// First we get the SQL definitions for each app
		//-----------------------------------------
		
		$sql_table_files = array();
		
		foreach( $this->registry->getApplications() as $app )
		{
			$_file = IPSLib::getAppDir( $app['app_directory'] ) . '/setup/versions/install/sql/' . $app['app_directory'] . '_' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '_tables.php';
			
			if( file_exists( $_file ) )
			{
				$sql_table_files[ $app['app_title'] ] = $_file;
			}
		}
		
		//-----------------------------------------
		// Get the library to run the checks
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'db_lib/' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '_tools.php' );
		$this->dbTools = new db_tools();
		
		return $sql_table_files;
	}

	/**
	 * Check the database for missing indexes
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _indexCheck()
	{
		//-----------------------------------------
		// First we get the SQL definitions for each app
		//-----------------------------------------
		
		$sql_table_files = $this->_getDbTools();

		//-----------------------------------------
		// Now let's loop
		//-----------------------------------------
		
		$errors_array = array();
		$tables_array = array();
		
		foreach( $sql_table_files as $app_title => $sql_file )
		{
			$TABLE = array();
			require_once( $sql_file );
			
			if ( is_array( $TABLE ) AND count( $TABLE ) )
			{
				$output = $this->dbTools->dbIndexDiag( $TABLE, $this->request['fix'] );
			
			if( !$output )
			{
				continue;
			}

				if( $output['error_count'] > 0 )
				{
					$errors_array[] = $app_title;
				}
			
				$tables_array[$app_title] = $output['results'];
			}
		}			

		/* Output */
		$this->registry->output->html .= $this->html->indexChecker( $errors_array, $tables_array );
    }
    
	/**
	 * Check the database for missing tables/columns
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 * @todo 	[Future] Functionality to show EXTRA columns/tables?
	 */
	private function _dbCheck()
	{
		//-----------------------------------------
		// First we get the SQL definitions for each app
		//-----------------------------------------
		
		$sql_table_files = $this->_getDbTools();
		
		//-----------------------------------------
		// Now let's loop
		//-----------------------------------------
		
		$errors_array = array();
		$tables_array = array();
		
		foreach( $sql_table_files as $app_title => $sql_file )
		{
			$TABLE = array();
			require_once( $sql_file );
			
			$output = $this->dbTools->dbTableDiag( $TABLE, $this->request['fix'] );

			if( !$output )
			{
				continue;
			}

			if( $output['error_count'] > 0 )
			{
				$errors_array[] = $app_title;
			}
			
			$tables_array[$app_title] = $output['results'];
		}

		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->dbChecker( $errors_array, $tables_array );
    }    
	
	/**
	 * Show the overview page
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _listFunctions()
	{
		//-----------------------------------------
		// PHP INFO?
		//-----------------------------------------
		
		if ( $this->request['phpinfo'] AND $this->request['phpinfo'] )
		{
			@ob_start();
			phpinfo();
			$parsed = @ob_get_contents();
			@ob_end_clean();
			
			preg_match( "#<body>(.*)</body>#is" , $parsed, $match1 );
			
			$php_body  = $match1[1];
			
			# PREVENT WRAP: Most cookies
			$php_body  = str_replace( "; " , ";<br />"   , $php_body );
			# PREVENT WRAP: Very long string cookies
			$php_body  = str_replace( "%3B", "<br />"    , $php_body );
			# PREVENT WRAP: Serialized array string cookies
			$php_body  = str_replace( ";i:", ";<br />i:" , $php_body );
			# PREVENT WRAP: LS_COLORS env
			$php_body  = str_replace( ":*.", "<br />:*." , $php_body );
			# PREVENT WRAP: PATH env
			$php_body  = str_replace( "bin:/", "bin<br />:/" , $php_body );
			# PREVENT WRAP: Cookie %2C split
			$php_body  = str_replace( "%2C", "%2C<br />" , $php_body );
			#PREVENT WRAP: Cookie , split
			$php_body  = preg_replace( "#,(\d+),#", ",<br />\\1," , $php_body );
		  
			$this->registry->output->html = $this->html->phpInfo( $php_body );
			return;
		}

		//-----------------------------------------
		// Server stuff
		//-----------------------------------------
        
		$this->DB->getSqlVersion();

		$sql_version		= strtoupper($this->settings['sql_driver']) . " " . $this->DB->true_version;
		
		$php_version		= phpversion() . " (" . @php_sapi_name() . ")  ( <a href='{$this->settings['base_url']}{$this->form_code}&amp;phpinfo=1'>{$this->lang->words['d_aphpinfo']}</a> )";
		$server_software	= @php_uname();
		
		$load_limit			= IPSDebug::getServerLoad();
        $server_load_found	= 0;
        $total_memory		= "--";
        $avail_memory		= "--";

		//-----------------------------------------
		// Check memory
		//-----------------------------------------

		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
		{
			$mem = @shell_exec('systeminfo');
			
			if( $mem )
			{
				$server_reply = explode( "\n", str_replace( "\r", "", $mem ) );
				
				if( count($server_reply) )
				{
					foreach( $server_reply as $info )
					{
						if( strstr( $info, $this->lang->words['d_atotal'] ) )
						{
							$total_memory =  trim( str_replace( ":", "", strrchr( $info, ":" ) ) );
						}
						
						if( strstr( $info, $this->lang->words['d_aavail']) )
						{
							$avail_memory =  trim( str_replace( ":", "", strrchr( $info, ":" ) ) );
						}
					}
				}
			}
		}
		else
		{
			$mem			= @shell_exec("free -m");
			$server_reply	= explode( "\n", str_replace( "\r", "", $mem ) );
			$mem			= array_slice( $server_reply, 1, 1 );
			$mem			= preg_split( "#\s+#", $mem[0] );

			$total_memory	= ( $mem[1] ) ? $mem[1] . ' MB' : '--';
			$avail_memory	= ( $mem[3] ) ? $mem[3] . ' MB' : '--';
		}
		
		$disabled_functions	= @ini_get('disable_functions') ? str_replace( ",", ", ", @ini_get('disable_functions') ) : $this->lang->words['d_anoinfo'];
		$extensions			= get_loaded_extensions();
		$extensions			= array_combine( $extensions, $extensions );
		sort( $extensions, SORT_STRING );
		
   		//-----------------------------------------
   		// Upgrade history?
   		//-----------------------------------------
   		
		$upgrade_history	= array();
   		$latest_version		= array( 'upgrade_version_id' => NULL );
   		
   		$this->DB->build( array( 'select' => '*', 'from' => 'upgrade_history', 'where' => "upgrade_app='core'", 'order' => 'upgrade_version_id DESC', 'limit' => array( 0, 5 ) ) );
   		$this->DB->execute();
   		
   		while( $r = $this->DB->fetch() )
   		{
   			if ( $r['upgrade_version_id'] > $latest_version['upgrade_version_id'] )
   			{
   				$latest_version = $r;
   			}
   			
   			$upgrade_history[] = $r;
   		}
   		
		//-----------------------------------------
		// Got real version number?
		//-----------------------------------------

		$version['version']			= (IPB_VERSION == '3.0.0 RC 1') ? $latest_version['upgrade_version_human'] : IPB_VERSION;
		$version['version_full']	= (IPB_LONG_VERSION == '30005') ? $latest_version['upgrade_version_id'] : IPB_LONG_VERSION;

		//-----------------------------------------
		// Version History
		//-----------------------------------------
		
		foreach( $upgrade_history as $r )
		{
			$r['_date']		= ipsRegistry::getClass( 'class_localization')->getDate( $r['upgrade_date'], 'SHORT' );
			
			$thiscontent	.= $this->html->acp_version_history_row( $r );
		}
		
		//-----------------------------------------
		// Set variables and pass to skin
		//-----------------------------------------
		
		$data = array(
						'version'			=> 'v' . $version['version'],
						'version_full'		=> $version['version_full'],
						'version_sql'		=> $sql_version,
						'driver_type'		=> strtoupper($this->settings['sql_driver']),
						'version_php'		=> $php_version,
						'disabled'			=> $disabled_functions,
						'extensions'		=> str_replace( "suhosin", "<strong>suhosin</strong>", implode( ", ", $extensions ) ),
						'safe_mode'			=> SAFE_MODE_ON == 1 ? "<span style='color:red;font-weight:bold;'>{$this->lang->words['d_aon']}</span>" : "<span style='color:green;font-weight:bold;'>{$this->lang->words['d_aoff']}</span>",
						'server'			=> $server_software,
						'load'				=> $load_limit,
						'total_memory'		=> $total_memory,
						'avail_memory'		=> $avail_memory,
					);

		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
		{
			$tasks = @shell_exec( "tasklist" );
			$tasks = str_replace( " ", "&nbsp;", $tasks );
		}
		else if( strtolower( PHP_OS ) == 'darwin' )
		{
			$tasks = @shell_exec( "top -l 1" );
			$tasks = str_replace( " ", "&nbsp;", $tasks );
		}
		else
		{
			$tasks = @shell_exec( "top -b -n 1" );
			$tasks = str_replace( " ", "&nbsp;", $tasks );
		}
		
		if( !$tasks )
		{
			$tasks = $this->lang->words['d_aunable'];
		}
		else
		{
			$tasks = "<pre>".$tasks."</pre>";
		}
		
		$data['tasks']	= $tasks;
		
		$this->registry->output->html .= $this->html->diagnosticsOverview( $data );
		
		$this->registry->output->html .= $this->html->acp_version_history_wrapper( $thiscontent );
	}
	
	
}