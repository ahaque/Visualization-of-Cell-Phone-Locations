<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: Upgrader core file
 * Last Updated: $LastChangedDate: 2009-02-05 23:05:44 +0000 (Thu, 05 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 3898 $
 *
 */


class upgrade_upgrade extends ipsCommand
{
	/**
	 * Current Version
	 *
	 * @access	private
	 * @var		int
	 */
	private $_currentLong  = 0;
	private $_currentHuman = 0;

	/**
	 * Upgrade In Progress Version
	 *
	 * @access	private
	 * @var		int
	 */
	private $_uipLong  = 0;
	private $_uipHuman = 0;

	/**
	 * Latest version
	 *
	 * @access	private
	 * @var		int
	 */
	private $_latestLong  = 0;
	private $_latestHuman = 0;

	/**
	 * Error flag
	 *
	 * @access	private
	 * @var		array
	 */
	private $_errorMsg = array();

	/**
	 * Current version  upgrade
	 *
	 * @access	private
	 * @var		int
	 */
	private $_currentUpgrade = 0;
	private $_currentApp     = '';
	private $_appData        = array();

	/**
	 * Skin keys
	 * Now we could do some fancy method that grabs the keys from an XML file or whatever
	 * But as they are unlikely to change with any frequency, this should suffice.
	 *
	 * @access	private
	 * @var		array
	 */
	private $_skinKeys = array( 1 => 'default', 'xmlskin', 'lofi' );
	private $_skinIDs  = array( 1, 2, 3 );

	private $_totalSteps = 14;

	/**
	 * Execute selected method
	 * [ REPEAT FOR APPS: SQL > VERSION UPGRADER / FINISH ] -> SETTINGS  > TEMPLATES > TASKS > LANGUAGES > PUBLIC LANGUAGES > BBCODE > ACP HELP OTHER [ Email Templates ] > Build Caches
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Set Up */
		IPSSetUp::setSavedData( 'man'       , ( intval( $this->request['man'] ) )        ? intval( $this->request['man'] )        : IPSSetUp::getSavedData('man') );
		IPSSetUp::setSavedData( 'appdir'    , (  $this->request['appdir'] )     		 ? $this->request['appdir']     		  : IPSSetUp::getSavedData('appdir') );
		IPSSetUp::setSavedData( 'helpfile'  , ( intval( $this->request['helpfile'] ) )   ? intval( $this->request['helpfile'] )   : IPSSetUp::getSavedData('helpfile') );

		/* Do we have a current application? */
		if ( ! IPSSetUp::getSavedData('appdir') )
		{
			$_app = IPSSetUp::fetchNextApplication();

			IPSSetUp::setSavedData( 'appdir', $_app['key'] );
		}

		/* Set current app */
		$this->_currentApp = IPSSetUp::getSavedData('appdir');

		/* Fetch numbers */
		$numbers = IPSSetUp::fetchAppVersionNumbers( $this->_currentApp );

		/* Set numbers */
		$this->_currentLong  = $numbers['current'][0];
		$this->_currentHuman = $numbers['current'][1];
		$this->_uipLong      = $numbers['next'][0];
		$this->_uipHuman     = $numbers['next'][1];
		$this->_latestLong   = $numbers['latest'][0];
		$this->_latestHuman  = $numbers['latest'][1];

		$this->_dbDriver     = strtolower( $this->settings['sql_driver'] );

		if ( $this->_currentApp )
		{
			$this->_appData = IPSSetUp::fetchXmlAppInformation( $this->_currentApp );

			if ( $this->_currentApp == 'core' )
			{
				$this->_appData['name'] = 'IP.Board';
			}
		}

		/* Fail safe */
		if ( ! $this->_currentApp )
		{
			print "No app";
			exit();
		}

		$this->registry->output->setVersionAndApp( $this->_uipHuman, $this->_appData );

		/* Switch */
		switch( $this->request['do'] )
		{
			case 'sql':
				$this->_stepCount = 1;
				$this->install_sql();
			break;

			case 'appclass':
				$this->_stepCount = 2;
				$this->install_appclass();
			break;

			case 'checkdb':
				$this->_stepCount = 3;
				$this->install_checkdb();
			break;

			case 'modules':
				$this->_stepCount = 4;
				$this->install_modules();
			break;

			case 'settings':
				$this->_stepCount = 5;
				$this->install_settings();
			break;

			case 'templates':
				$this->_stepCount = 6;
				$this->install_templates();
			break;

			case 'tasks':
				$this->_stepCount = 7;
				$this->install_tasks();
			break;

			case 'languages':
				$this->_stepCount = 8;
				$this->install_languages();
			break;

			case 'clientlanguages':
				$this->_stepCount = 9;
				$this->install_client_languages();
			break;

			case 'bbcode':
				$this->_stepCount = 10;
				$this->install_bbcode();
			break;

			case 'acphelp':
				$this->_stepCount = 11;
				$this->install_acphelp();
			break;

			case 'other':
				$this->_stepCount = 12;
				$this->install_other();
			break;

			case 'caches':
				$this->_stepCount = 13;
				$this->install_caches();
			break;
			
			case 'templatecache':
				$this->_stepCount = 14;
				$this->install_template_caches();
			break;

			default:
				$this->_splash();
			break;
		}

		/* Log errors for support */
		if ( count( $this->_errorMsg ) > 0 )
		{
			IPSSetUp::addLogMessage( implode( "\n", $this->_errorMsg ), $this->_uipHuman, $this->_currentApp );
		}
	}

	/**
	 * Splash
	 *
	 * @access	public
	 */
	public function _splash()
	{
		/* Output */
		$this->registry->output->setTitle( "Upgrade" );
		$this->registry->output->setNextAction( 'upgrade&do=sql' );
		$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->upgrade_ready( $this->_appData['name'], $this->_currentHuman, $this->_latestHuman, $_customDataArray ) );
		$this->registry->output->sendOutput();
	}

	/**
	 * Installs SQL schematic
	 *
	 * @return void
	 */
	public function install_sql()
	{
		/* Lets grab that SQL! */
		$SQL        = array();
		$cnt        = 0;
		$output     = '';
		$message    = array();
		$sourceFile = '';

		/* Reset Errors */
		$this->_resetErrors();

		/* SQL */
		$file = IPSLib::getAppDir( $this->_currentApp ) . '/setup/versions/upg_' . $this->_uipLong . '/' . $this->_dbDriver . '_updates.php';

		/* Get file */
		if ( file_exists( $file ) )
		{
			require( $file );

			if ( is_array( $SQL ) AND count( $SQL ) > 0 )
			{
				/* Loop */
				foreach ( $SQL as $q )
				{
					/* Set DB driver to return any errors */
					$this->DB->return_die = 1;
					$this->DB->allow_sub_select 	= 1;
					$this->DB->error				= '';

					$q = str_replace( "<%time%>", time(), $q );

					$q = IPSSetUp::addPrefixToQuery( $q, $this->registry->dbFunctions()->getPrefix() );

					if ( $this->settings['mysql_tbl_type'] )
					{
						if ( preg_match( "/^create table(.+?)/i", $q ) )
						{
							$q = preg_replace( "/^(.+?)\);$/is", "\\1) TYPE={$this->settings['mysql_tbl_type']};", $q );
						}
					}

					if( IPSSetUp::getSavedData('man') )
					{
						$q = trim( $q );

						/* Ensure the last character is a semi-colon */
						if ( substr( $q, -1 ) != ';' )
						{
							$q .= ';';
						}

						$output .= $q . "\n\n";
					}
					else
					{
						$this->DB->query( $q );

						if ( $this->DB->error )
						{
							$this->registry->output->addError( nl2br( $q ) . "<br /><br />".$this->DB->error );
						}
						else
						{
							$count++;
						}
					}
				}

				$message[] = $count . " queries run...";
			}
		}
		else
		{
			/* No SQL */
			$this->registry->output->addMessage("No native SQL to run....");
			$this->install_appclass();
			return;
		}

		/* Got queries to show? */
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			/* Create source file */
			if ( $this->_dbDriver == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, $this->_uipLong );
			}

			$this->registry->output->setTitle( "Upgrade: SQL" );
			$this->registry->output->setNextAction( 'upgrade&do=appclass' );
			$this->registry->output->addContent( $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile ) );
			$this->registry->output->sendOutput();
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output = ( is_array( $message ) AND count( $message ) ) ? $message : array( 0 => "SQL complete" );

			$this->_finishStep( $output, "Upgrade: SQL", 'upgrade&do=appclass' );
		}
	}

	/**
	 * Runs the upgrade specific file
	 *
	 * @return void
	 */
	public function install_appclass()
	{
		/* INIT */
		$continue   = 0;
		$customHTML = '';
		$file       = '';

		/* Reset Errors */
		$this->_resetErrors();

		/* IPB 2.0.0+ Upgrade file */
		$fileNewer = IPSLib::getAppDir( $this->_currentApp ) . '/setup/versions/upg_' . $this->_uipLong . '/version_upgrade.php';

		/* Older files */
		$fileLegacy = IPSLib::getAppDir( $this->_currentApp ) . '/setup/versions/upg_' . $this->_uipLong . '/version_upgrade_' . $this->_dbDriver . '.php';

		/* Got any file? */
		if ( file_exists( $fileNewer ) )
		{
			$file = $fileNewer;
		}
		else if ( file_exists( $fileLegacy ) )
		{
			$file = $fileLegacy;
		}

		/* Do we have a file? */
		if ( $file )
		{
			require_once( $file );
			$upgrade = new version_upgrade();
			$result  = $upgrade->doExecute( $this->registry );

			if ( count( $this->registry->output->fetchWarnings() ) > 0 )
			{
				if ( ! $result )
				{
					$this->registry->output->setNextAction( 'upgrade&do=appclass' );
				}
				elseif ( $this->_uipLong >= $this->_latestLong || $this->_uipLong == 0 )
				{
					/* Got another app to do? */
					$next = IPSSetUp::fetchNextApplication( $this->_currentApp );

					if ( $next['key'] )
					{
						$this->registry->output->setNextAction( 'upgrade&do=sql&appdir=' . $next['key'] );
					}
					else
					{
						$this->registry->output->setNextAction( 'upgrade&do=checkdb' );
					}
				}
				else
				{
					$this->registry->output->setNextAction( 'upgrade&do=sql' );
				}

				$this->registry->output->setTitle( "Upgrade: Version Upgrade" );
				$this->registry->output->sendOutput();
			}

			/* App specific version upgrade is done */
			if ( $result )
			{
				/* The individual upgrade files all shoot you to 2.0... */
				if ( $this->_currentApp == 'core' AND $this->_uipLong < 20000 )
				{
					//$this->_uipLong = '10004';
				}

				/* Update version history */
				if ( IPSSetUp::is300plus() === TRUE )
				{
					$this->DB->insert( 'upgrade_history', array( 'upgrade_version_id'     => $this->_uipLong,
																 'upgrade_version_human'  => $this->_uipHuman,
																 'upgrade_date'  		  => time(),
																 'upgrade_app'			  => $this->_currentApp,
																 'upgrade_mid'   		  => 0 ) );

					/* Update app */
					$_in = ( $this->_currentApp == 'core' ) ? "'core', 'forums', 'members'" : "'" . $this->_currentApp . "'";

					$this->DB->update( 'core_applications', array( 'app_long_version' => $this->_uipLong,
																   'app_version'	  => $this->_uipHuman ), 'app_directory IN (' . $_in . ')' );
				}
				else
				{
					$this->DB->insert( 'upgrade_history', array( 'upgrade_version_id'     => $this->_uipLong,
																 'upgrade_version_human'  => $this->_uipHuman,
																 'upgrade_date'  		  => time(),
																 'upgrade_mid'   		  => 0 ) );
				}

				if ( $upgrade->fetchOutput() )
				{
					$customHTML = $upgrade->fetchOutput();
				}
				else
				{
					$output[] = "Successfully upgraded to version {$this->_uipHuman}";
				}
			}
			else
			{
				if ( $upgrade->fetchOutput() )
				{
					$customHTML = $upgrade->fetchOutput();
				}
				else
				{
					$output[] = "Proceeding with update...";
				}

				$continue = 1;
			}
		}
		else
		{
			/* Nothing to run */
			if ( $this->_uipLong )
			{
				/* Update version history */
				if ( IPSSetUp::is300plus() === TRUE )
				{
					$this->DB->insert( 'upgrade_history', array( 'upgrade_version_id'     => $this->_uipLong,
																 'upgrade_version_human'  => $this->_uipHuman,
																 'upgrade_date'  		  => time(),
																 'upgrade_app'			  => $this->_currentApp,
																 'upgrade_mid'   		  => 0 ) );

					/* Update app */
					$_in = ( $this->_currentApp == 'core' ) ? "'core', 'forums', 'members'" : "'" . $this->_currentApp . "'";

					$this->DB->update( 'core_applications', array( 'app_long_version' => $this->_uipLong,
																   'app_version'	  => $this->_uipHuman ), 'app_directory IN (' . $_in . ')' );
				}
				else
				{
					$this->DB->insert( 'upgrade_history', array( 'upgrade_version_id'     => $this->_uipLong,
																 'upgrade_version_human'  => $this->_uipHuman,
																 'upgrade_date'  		  => time(),
																 'upgrade_mid'   		  => 0 ) );
				}

				$output[] = "Successfully upgraded to version {$this->_uipHuman}";
			}
		}

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		if ( $continue )
		{
			$this->registry->output->setNextAction( 'upgrade&do=appclass' );
		}
		elseif ( $this->_uipLong >= $this->_latestLong || $this->_uipLong == 0 )
		{
			/* Got another app to do? */
			$next = IPSSetUp::fetchNextApplication( $this->_currentApp );

			if ( $next['key'] )
			{
				$this->registry->output->setNextAction( 'upgrade&do=sql&appdir=' . $next['key'] );
			}
			else
			{
				$this->registry->output->setNextAction( 'upgrade&do=checkdb' );
			}
		}
		else
		{
			$this->registry->output->setNextAction( 'upgrade&do=sql' );
		}

		$this->registry->output->setTitle( "Upgrade: Version Upgrade" );

		if ( $customHTML )
		{
			$this->registry->output->addContent( $customHTML );
		}
		else
		{
			$this->registry->output->addContent( $this->registry->output->template()->page_refresh( $output ) );
		}

		$this->registry->output->setInstallStep( $this->_stepCount, $this->_totalSteps );
		$this->registry->output->sendOutput();
	}

	/**
	 * Check DB
	 *
	 * @return void
	 */
	public function install_checkdb()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		$output[] = "Database Check Complete";

		$this->_finishStep( $output, "Upgrade: DB Check", 'upgrade&do=modules' );
	}

	/**
	 * Install Modules
	 *
	 * @return void
	 */
	public function install_modules()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous, '{app}_modules.xml' );

		//-----------------------------------------
		// Install SYSTEM Templates
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading modules...";
			$_PATH    = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			if ( file_exists( $_PATH . $next['key'] . '_modules.xml' ) )
			{
				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/applications/applications.php' );
				$apps            =  new admin_core_applications_applications();
				$apps->makeRegistryShortcuts( $this->registry );

				$this->request['_app'] = $next['key'];
				$apps->moduleImport( '', 1, FALSE );
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Modules", 'upgrade&do=modules&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All modules upgraded";

			$this->_finishStep( $output, "Upgrade: Modules", 'upgrade&do=settings' );
		}
	}

	/**
	 * Installs Settings schematic
	 *
	 * @return void
	 */
	public function install_settings()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous, '{app}_settings.xml' );

		//-----------------------------------------
		// Install settings
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading settings...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			if ( file_exists( $_PATH . $next['key'] . '_settings.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/tools/settings.php' );
				$settings =  new admin_core_tools_settings();
				$settings->makeRegistryShortcuts( $this->registry );

				$this->request['app_dir'] = $next['key'];

				//-----------------------------------------
				// Known settings
				//-----------------------------------------

				if ( substr( IPSSetUp::getSavedData('install_url'), -1 ) == '/' )
				{
					IPSSetUp::setSavedData('install_url', substr( IPSSetUp::getSavedData('install_url'), 0, -1 ) );
				}

				if ( substr( IPSSetUp::getSavedData('install_dir'), -1 ) == '/' )
				{
					IPSSetUp::setSavedData('install_dir', substr( IPSSetUp::getSavedData('install_dir'), 0, -1 ) );
				}

				/* Fetch known settings  */
				if ( file_exists( IPSLib::getAppDir( $next['key'] ) . '/setup/versions/install/knownSettings.php' ) )
				{
					require( IPSLib::getAppDir( $next['key'] ) . '/setup/versions/install/knownSettings.php' );
				}

				$settings->importAllSettings( 1, 1, $knownSettings );
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: " . $next['title'] . " Settings", 'upgrade&do=settings&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All settings upgraded";

			$this->_finishStep( $output, "Upgrade: Settings", 'upgrade&do=templates' );
		}
	}

	/**
	 * Install templates
	 *
	 * @return void
	 */
	public function install_templates()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];
		
		if ( file_exists( IPS_ROOT_PATH .'setup/sql/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH .'setup/sql/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}
		
		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous );

		/* Got any skin sets? */
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
												  'from'   => 'skin_collections' ) );

		if ( ! $count['count'] )
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "Inserting template set data...";

			require_once( IPS_KERNEL_PATH . 'classXML.php' );
			$xml    = new classXML( IPSSetUp::charSet );

			//-----------------------------------------
			// Adjust the table?
			//-----------------------------------------

			if ( $extra_install AND method_exists( $extra_install, 'before_inserts_run' ) )
			{
				 $q = $extra_install->before_inserts_run( 'skinset' );
			}

			/* Skin Set Data */
			$xml->load( IPS_PUBLIC_PATH . 'resources/skins/setsData.xml' );

			foreach( $xml->fetchElements( 'set' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				$this->DB->insert( 'skin_collections', $data );
			}

			//-----------------------------------------
			// Adjust the table?
			//-----------------------------------------

			if ( $extra_install AND method_exists( $extra_install, 'after_inserts_run' ) )
			{
				 $q = $extra_install->after_inserts_run( 'skinset' );
			}
		}

		/* Load skin classes */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );

		$skinFunctions = new skinImportExport( $this->registry );

		/* Grab skin data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections' ) );

		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			/* Bit of jiggery pokery... */
			if ( $row['set_key'] == 'default' )
			{
				$row['set_key'] = 'root';
				$row['set_id']  = 0;
			}

			$skinSets[ $row['set_key'] ] = $row;
		}

		//-----------------------------------------
		// InstallTemplates
		//-----------------------------------------

		if ( $next['key'] )
		{
			foreach( $skinSets as $skinKey => $skinData )
			{
				$_PATH    = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

				$output[] = $next['title'] . ": Upgrading {$skinData['set_name']} templates...";

				if ( file_exists( $_PATH . $next['key'] . '_' . $skinKey . '_templates.xml' ) )
				{
					//-----------------------------------------
					// Install
					//-----------------------------------------

					$return = $skinFunctions->importTemplateAppXML( $next['key'], $skinKey, $skinData['set_id'], TRUE );

					$output[] = $next['title'] . ": " . intval( $return['insertCount'] ) . " templates inserted";
				}

				if ( file_exists( $_PATH . $next['key'] . '_' . $skinKey . '_css.xml' ) )
				{
					//-----------------------------------------
					// Install
					//-----------------------------------------

					$return = $skinFunctions->importCSSAppXML( $next['key'], $skinKey, $skinData['set_id'] );

					$output[] = $next['title'] . ": " . intval( $return['insertCount'] ) . " {$skinData['set_name']} CSS files inserted";
				}
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Templates", 'upgrade&do=templates&previous=' . $next['key'] );
 		}
		else
		{
			//-----------------------------------------
			// Recache templates
			//-----------------------------------------

			$output[] = "Recaching templates...";

			foreach( $skinSets as $skinKey => $skinData )
			{
				/* Replacements */
				if ( file_exists( IPS_PUBLIC_PATH . 'resources/skins/replacements_' . $skinKey . '.xml' ) )
				{
					$skinFunctions->importReplacementsXMLArchive( file_get_contents( IPS_PUBLIC_PATH . 'resources/skins/replacements_' . $skinKey . '.xml' ) );
				}
				}

			$skinFunctions->rebuildSkinSetsCache();

			$output[] = "All templates upgraded";

			$this->_finishStep( $output, "Upgrade: Templates", 'upgrade&do=tasks' );
		}
	}


	/**
	 * Install Tasks
	 *
	 * @return void
	 */
	public function install_tasks()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous, '{app}_tasks.xml' );

		//-----------------------------------------
		// Insert tasks
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading tasks...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			if ( file_exists( $_PATH . $next['key'] . '_tasks.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/system/taskmanager.php' );
				$tasks = new admin_core_system_taskmanager();
				$tasks->makeRegistryShortcuts( $this->registry );

				$tasks->tasksImportFromXML( $_PATH . $next['key'] . '_tasks.xml', 1 );
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Tasks", 'upgrade&do=tasks&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All tasks upgraded...";

			$this->_finishStep( $output, "Upgrade: Tasks", 'upgrade&do=languages' );
		}
	}

	/**
	 * Install Languages
	 *
	 * @return void
	 */
	public function install_languages()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous );

		//-----------------------------------------
		// Install Languages
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading ADMIN languages...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			//-----------------------------------------
			// Get the language stuff
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php' );
			$lang            =  new admin_core_languages_manage_languages();
			$lang->makeRegistryShortcuts( $this->registry );

			/* Loop through the xml directory and look for lang packs */
			try
			{
				foreach( new DirectoryIterator( $_PATH ) as $f )
				{
					if( preg_match( "#admin_(.+?)_language_pack.xml#", $f->getFileName() ) )
					{
						//-----------------------------------------
						// Import and cache
						//-----------------------------------------
            	
						$this->request['file_location'] = $_PATH . $f->getFileName();
						$lang->imprtFromXML( true, true, true, $next['key'] );
					}
				}
			} catch ( Exception $e ) {}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Admin Languages", 'upgrade&do=languages&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All ADMIN languages upgraded";

			$this->_finishStep( $output, "Upgrade: Admin Languages", 'upgrade&do=clientlanguages' );
		}
	}

	/**
	 * Install Public Languages
	 *
	 * @return void
	 */
	public function install_client_languages()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous );

		//-----------------------------------------
		// Install Languages
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading Public languages...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			//-----------------------------------------
			// Get the language stuff
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php' );
			$lang            =  new admin_core_languages_manage_languages();
			$lang->makeRegistryShortcuts( $this->registry );

			/* Loop through the xml directory and look for lang packs */
			try
			{
				foreach( new DirectoryIterator( $_PATH ) as $f )
				{
					if( preg_match( "#public_(.+?)_language_pack.xml#", $f->getFileName() ) )
					{
						//-----------------------------------------
						// Import and cache
						//-----------------------------------------
            	
						$this->request['file_location'] = $_PATH . $f->getFileName();
						$lang->imprtFromXML( true, true, true, $next['key'] );
					}
				}
			} catch ( Exception $e ) {}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Public Languages", 'upgrade&do=clientlanguages&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All Public languages upgraded";

			$this->_finishStep( $output, "Upgrade: Public Languages", 'upgrade&do=bbcode' );
		}
	}

	/**
	 * Install BBCode
	 *
	 * @return void
	 */
	public function install_bbcode()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous, '{app}_bbcode.xml' );

		//-----------------------------------------
		// Install Languages
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading BBcode...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			if ( file_exists( $_PATH . $next['key'] . '_bbcode.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/bbcode.php' );
				$bbcode = new admin_core_posts_bbcode();
				$bbcode->makeRegistryShortcuts( $this->registry );

				$bbcode->bbcodeImportDo( file_get_contents( $_PATH . $next['key'] . '_bbcode.xml' ) );
			}

			$output[] = $next['title'] . ": Upgrading Media Tags...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			if ( file_exists( $_PATH . $next['key'] . '_mediatag.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/media.php' );
				$bbcode = new admin_core_posts_media();
				$bbcode->makeRegistryShortcuts( $this->registry );

				$bbcode->doMediaImport( file_get_contents( $_PATH . $next['key'] . '_mediatag.xml' ) );
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: BBCode", 'upgrade&do=bbcode&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All BBCode upgraded";

			$this->_finishStep( $output, "Upgrade: BBCode", 'upgrade&do=acphelp' );
		}
	}

	/**
	 * Install ACP Help
	 *
	 * @return void
	 */
	public function install_acphelp()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous, '{app}_help.xml' );

		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );

		//-----------------------------------------
		// Install Languages
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Upgrading Public Help...";

			if ( file_exists( $_PATH . $next['key'] . '_help.xml' ) )
			{
				//-----------------------------------------
				// Do it..
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/tools/help.php' );
				$help = new admin_core_tools_help();
				$help->makeRegistryShortcuts( $this->registry );

				$overwrite = ( IPSSetUp::getSavedData('helpfile') ) ? TRUE : FALSE;

				$done = $help->helpFilesXMLImport_app( $next['key'], $overwrite );

				$output[] = $next['key'] . ": Added " . $done['added'] . ", " . $done['updated'] . " updated help files";
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Help System", 'upgrade&do=acphelp&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Next...
			//-----------------------------------------

			$output[] = "All help files upgraded";

			$this->_finishStep( $output, "Upgrade: Help System", 'upgrade&do=other' );
		}
	}

	/**
	 * Install Other stuff
	 *
	 * @return void
	 */
	public function install_other()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$previous = $_REQUEST['previous'];
		
		//-----------------------------------------
		// HOOKS: Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous, 'hooks.xml' );

		//-----------------------------------------
		// Insert tasks
		//-----------------------------------------

		if ( $next['key'] )
		{
			$output[] = $next['title'] . ": Updating hooks...";
			$_PATH        = IPSLib::getAppDir( $next['key'] ) .  '/xml/';

			if ( file_exists( $_PATH . 'hooks.xml' ) )
			{
				//-----------------------------------------
				// Continue
				//-----------------------------------------

				require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/applications/hooks.php' );
				$hooks = new admin_core_applications_hooks();
				$hooks->makeRegistryShortcuts( $this->registry );

				$result = $hooks->installAppHooks( $next['key'] );
				
				$output[] = $next['title'] . " hooks: " . $result['inserted'] . " inserted, " . $result['updated'] . " updated";
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Hook", 'upgrade&do=other&previous=' . $next['key'] );
		}
		else
		{
		
			//-----------------------------------------
		// ****** USER AGENTS
		//-----------------------------------------

		$output[] = "Inserting default user agents...";

		require_once( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php' );
		$userAgentFunctions = new userAgentFunctions( $this->registry );

		$userAgentFunctions->rebuildMasterUserAgents();

		//-----------------------------------------
		// Build Calendar RSS
		//-----------------------------------------

		if( IPSLib::appIsInstalled('calendar') )
		{
			require_once( IPSLib::getAppDir('calendar') . '/modules_admin/calendar/calendars.php' );
			$cal = new admin_calendar_calendar_calendars();
			$cal->makeRegistryShortcuts( $this->registry );

			$output[] = "Building calendar RSS...";
			$cal->calendarRSSCache();
		}

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		$this->_finishStep( $output, "Upgrade: Other Data", 'upgrade&do=caches' );
	}
	}

	/**
	 * Install Caches
	 *
	 * @return void
	 */
	public function install_caches()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$this->settings['base_url'] = IPSSetUp::getSavedData('install_url');

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$next = IPSSetUp::fetchNextApplication( $previous );

		//-----------------------------------------
		// Install SYSTEM Templates
		//-----------------------------------------

		if ( $next['key'] )
		{
			$_PATH    = IPS_ROOT_PATH . 'applications/' . $next['key'] . '/extensions/';

			if ( file_exists( $_PATH . 'coreVariables.php' ) )
			{
				# Grab cache master file
				require_once( $_PATH . 'coreVariables.php' );

				if ( is_array( $CACHE ) )
				{
					foreach( $CACHE as $cs_key => $cs_data )
					{
						$output[] = $next['title'] . ": Building {$cs_key}...";

						ipsRegistry::cache()->rebuildCache( $cs_key, $next['key'] );
					}
				}
				else
				{
					$output[] = $next['title'] . ": No caches to build...";
				}
			}
			else
			{
				$output[] = $next['title'] . ": No caches to build...";
			}

			//-----------------------------------------
			// Done.. so get some more!
			//-----------------------------------------

			$this->_finishStep( $output, "Upgrade: Caches", 'upgrade&do=caches&previous=' . $next['key'] );
		}
		else
		{
			//-----------------------------------------
			// Global caches...
			//-----------------------------------------

			# Grab cache master file
			require_once( IPS_ROOT_PATH . 'extensions/coreVariables.php' );

			/* Add handle */
			$_tmp = new coreVariables();
			$_cache = $_tmp->fetchCaches();
			$CACHE  = $_cache['caches'];

			//-----------------------------------------
			// Continue
			//-----------------------------------------

			if ( is_array( $CACHE ) )
			{
				foreach( $CACHE as $cs_key => $cs_data )
				{
					$output[] = "System Building {$cs_key}...";

					ipsRegistry::cache()->rebuildCache( $cs_key, 'global' );
				}
			}

			//-------------------------------------------------------------
			// Systemvars
			//-------------------------------------------------------------

			$output[] = "Rebuilding system variables cache...";

			$cache = array( 'mail_queue'    => 0,
							'task_next_run' => time() + 3600 );

			ipsRegistry::cache()->setCache( 'systemvars', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );

			$output[] = "Global: All caches upgraded";
			
			$this->_finishStep( $output, "Upgrade: Caches", 'upgrade&do=templatecache' );
		}
	}
	
	/**
	 * Install Tenplate Caches
	 *
	 * @return void
	 */
	public function install_template_caches()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$this->settings['base_url'] = IPSSetUp::getSavedData('install_url');

		$previous = $_REQUEST['previous'];

		//-----------------------------------------
		// Fetch next 'un
		//-----------------------------------------

		$skinId   = intval( $this->request['skinId'] );
		$skinData = array();
		$output   = array();
		
		//-----------------------------------------
		// Recache skins: Moved here so they are
		// build after hooks are added
		//-----------------------------------------
		
		/* Load skin classes */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );

		$skinFunctions = new skinImportExport( $this->registry );

		/* Grab skin data */
		$skinData = $this->DB->buildAndFetch( array( 'select' => '*',
										 			 'from'   => 'skin_collections',
													 'where'  => 'set_id > ' . $skinId . ' AND set_parent_id=0',
													 'order'  => 'set_id ASC',
													 'limit'  => array( 0, 1 ) ) );

		if ( $skinData['set_id'] )
		{
			$skinFunctions->rebuildPHPTemplates( $skinData['set_id'] );
			$output = array_merge( $output, $skinFunctions->fetchMessages( TRUE ) );
			
			if ( $skinFunctions->fetchErrorMessages() !== FALSE )
			{
				$this->registry->output->addWarning( implode( "<br />", $skinFunctions->fetchErrorMessages() ) );
			}
			
			$skinFunctions->rebuildCSS( $skinData['set_id'] );
			$output = array_merge( $output, $skinFunctions->fetchMessages( TRUE ) );
			
			if ( $skinFunctions->fetchErrorMessages() !== FALSE )
			{
				$this->registry->output->addWarning( implode( "<br />", $skinFunctions->fetchErrorMessages() ) );
			}

			$skinFunctions->rebuildReplacementsCache( $skinData['set_id'] );
			$output = array_merge( $output, $skinFunctions->fetchMessages( TRUE ) );
			
			if ( $skinFunctions->fetchErrorMessages() !== FALSE )
			{
				$this->registry->output->addWarning( implode( "<br />", $skinFunctions->fetchErrorMessages() ) );
			}
			
			$output[] = "Recached skin " . $skinData['set_name'] . "...";
			
			/* Go for the next */
			$this->_finishStep( $output, "Upgrade: Skin Caches", 'upgrade&do=templatecache&skinId=' . $skinData['set_id'] );
		}
		else
		{
			/* All diddly done */
			$output[] = "All skins recached";

			$skinFunctions->rebuildSkinSetsCache();
			
			/* Rebuild FURL cache */
			try
			{
				IPSLib::cacheFurlTemplates();
			}
			catch( Exception $error )
			{
			}
			
			$this->_finishStep( $output, "Upgrade: Skin Caches", 'done' );
		}
	}

	/**
	 * Reset error handle
	 *
	 * @access	private
	 * @return	nufink
	 */
	private function _resetErrors()
	{
		$this->_errorMsg = array();
	}

	/**
	 * Finish Step
	 * Configures the output engine
	 *
	 * @access	private
	 * @param	string	output
	 * @param	string	title
	 * @param	string	next step
	 * @return	void
	 */
	private function _finishStep( $output, $title, $nextStep )
	{
		if ( $this->_stepCount )
		{
			$this->registry->output->setInstallStep( $this->_stepCount, $this->_totalSteps );
		}

		$this->registry->output->setTitle( $title );
		$this->registry->output->setNextAction( $nextStep );
		$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->page_refresh( $output ) );
		$this->registry->output->sendOutput();
	}
}