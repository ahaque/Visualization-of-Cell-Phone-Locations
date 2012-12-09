<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Static SETUP Classes for IP.Board 3
 *
 * These classes are not required as objects.
 * Last Updated: $Date: 2009-08-21 12:56:02 -0400 (Fri, 21 Aug 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 5038 $
 *
 */

/**
 * Collection of methods to aid setup (install/upgrade)
 *
 *
 * @author	Matt
 */
class IPSSetUp
{
	/**
	 * Min PHP Version
	 *
	 * @var	string
	 */
	const minPhpVersion		= '5.1.0';

	/**
	 * Min MySQL Version
	 *
	 * @var	string
	 */
	const minDb_mysql		= '4.1.0';

	/**
	 * Min MSSQL Version
	 *
	 * @var	string
	 */
	const minDb_mssql		= '10.0.0';

	/**
	 * Min PHP Version
	 *
	 * @var	string
	 */
	const prefPhpVersion	= '5.2.1';

	/**
	 * Min MySQL Version
	 *
	 * @var	string
	 */
	const prefDb_mysql		= '5.0.0';

	/**
	 * Min MSSQL Version
	 *
	 * @var	string
	 */
	const prefDb_mssql		= '10.0.0';


	/**
	 * Doc Char Set
	 *
	 * @var	string
	 */
	const charSet			= 'utf-8';

	/**
	 * Saved data
	 *
	 * @access	private
	 * @var		array
	 */
	private static $_savedData	= array();

	/**
	 * Current version: Is 1.2.0+?
	 *
	 * @access	public
	 * @return	boolean
	 */
	static public function is120plus()
	{
		if ( ipsRegistry::DB()->checkForField( 'perm_id', 'forum_perms' ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Current version: Is 1.3.0+?
	 *
	 * @access	public
	 * @return	boolean
	 */
	static public function is130plus()
	{
		if ( ipsRegistry::DB()->checkForField( 'sub_id', 'subscriptions' ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Current version: Is 2.0.0+?
	 *
	 * @access	public
	 * @return	boolean
	 */
	static public function is200plus()
	{
		if ( ipsRegistry::DB()->checkForField( 'upgrade_id', 'upgrade_history' ) )
		{
			/* Now, this table is created in the first step between 1.3 and 2.0, so check for contents */
			$test = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(upgrade_version_id) as max',
			 												 'from'   => 'upgrade_history' ) );

			if ( $test['max'] < 20000 )
			{
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Current version: Is 3.0.0+?
	 *
	 * @access	public
	 * @return	boolean
	 */
	static public function is300plus()
	{
		if ( ipsRegistry::DB()->checkForField( 'member_id', 'members' ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Create a MySQL source file for larger boards
	 *
	 * @access	public
	 * @param	string		Output - list of current queries
	 * @param	int			Current Long Version ID
	 * @param	string		Unique bit to append to filename to prevent overwrites, eg 1,2 etc
	 * @param	mixed		File name on success, false on not
	 */
	static public function createSqlSourceFile( $sqlData, $uipLong, $uniqueBit='' )
	{
		$uniqueBit = ( $uniqueBit ) ? '_' . $uniqueBit : '';
		$fileName  = IPS_CACHE_PATH . 'cache/sql_source_' . $uipLong . $uniqueBit . '.sql';

		/* Try and write the file */
		if ( $sqlData AND @file_put_contents( $fileName, str_replace( "\n\n", "\n", $sqlData ) ) )
		{
			@chmod( $fileName, 0777 );
			return $fileName;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Clean  up source files
	 *
	 * @access public
	 * @return void
	 */
	static public function removeSqlSourceFiles()
	{
		try
		{
			foreach( new DirectoryIterator( IPS_CACHE_PATH . 'cache' ) as $file )
			{
				if ( ! $file->isDot() AND ! $file->isDir() )
				{
					$_name = $file->getFileName();
        	
					if ( preg_match( "#^sql_source_#", $_name ) )
					{
						@unlink( IPS_CACHE_PATH . 'cache/' . $_name );
					}
				}
			}
		} catch ( Exception $e ) {}
	}
	
	/**
	 * Add SQL Prefix to Query
	 *
	 * @access	public
	 * @param	string		SQL Query
	 * @param	string		SQL Prefix
	 */
	static public function addPrefixToQuery( $query, $prefix )
	{
		if ( $prefix )
		{
			/* Remove default ibf_ from < 3.0.0 files */
			if ( strstr( $query, 'ibf' ) )
			{
				$query = preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " \\1\\2", $query);
			}

			$query = preg_replace( "#^CREATE TABLE(?:\s+?)?(\S+)#is"        , "CREATE TABLE "  . $prefix."\\1 ", $query );
			$query = preg_replace( "#^RENAME TABLE(?:\s+?)?(\S+)\s+?TO\s+?(\S+?)(\s|$)#i"     , "RENAME TABLE "  . $prefix."\\1 TO " . $prefix ."\\2", $query );
			$query = preg_replace( "#^DROP TABLE( IF EXISTS)?(?:\s+?)?(\S+)(\s+?)?#i"    , "DROP TABLE \\1 "    . $prefix."\\2 ", $query );
			$query = preg_replace( "#^TRUNCATE TABLE(?:\s+?)?(\S+)(\s+?)?#i", "TRUNCATE TABLE ". $prefix."\\1 ", $query );
			$query = preg_replace( "#^DELETE FROM(?:\s+?)?(\S+)(\s+?)?#i"   , "DELETE FROM "   . $prefix."\\1 ", $query );
			$query = preg_replace( "#^INSERT INTO(?:\s+?)?(\S+)\s+?#i"      , "INSERT INTO "   . $prefix."\\1 ", $query );
			$query = preg_replace( "#^UPDATE(?:\s+?)?(\S+)\s+?#i"           , "UPDATE "        . $prefix."\\1 ", $query );
			$query = preg_replace( "#^REPLACE INTO(?:\s+?)?(\S+)\s+?#i"     , "REPLACE INTO "  . $prefix."\\1 ", $query );
			$query = preg_replace( "#^ALTER TABLE(?:\s+?)?(\S+)\s+?#i"      , "ALTER TABLE "   . $prefix."\\1 ", $query );
			
			# MSSQL
			$query = preg_replace( "#select FULLTEXTCATALOGPROPERTY \( '(\S+)', 'PopulateStatus' \)#i", "select FULLTEXTCATALOGPROPERTY ( '" . $prefix . "\\1', 'PopulateStatus' )", $query );
			$query = preg_replace( "#exec sp_fulltext_catalog '(\S+)', 'create'#i", "exec sp_fulltext_catalog '" . $prefix . "\\1', 'create'", $query );
			$query = preg_replace( "#sp_fulltext_table\s{1,}'(\S+)',\s{1,}'(\S+)',\s{1,}'(\S+)',\s{1,}'(\S+)'#i", "sp_fulltext_table '" . $prefix . "\\1', '\\2', '" . $prefix . "\\3', '\\4'", $query );
			$query = preg_replace( "#sp_fulltext_table '(\S+)', '(\S+)'$#i", "sp_fulltext_table '" . $prefix . "\\1', '\\2'", $query );
			$query = preg_replace( "#sp_fulltext_column\s{0,}'(\S+)',\s{0,}'(\S+)',\s{0,}'(\S+)'#i", "sp_fulltext_column '" . $prefix . "\\1', '\\2', '\\3'", $query );
			$query = preg_replace( "#(\s|'|\")PK_(\S+)#", " \\1" . $prefix."PK_\\2", $query );
			$query = preg_replace( "#(\s|'|\")ftcatalog(\s|'|\")#", " \\1" . $prefix."ftcatalog\\2", $query );
			$query = preg_replace( "#^SET IDENTITY_INSERT (?:\s+?)?(\S+)\s+?#i" , "SET IDENTITY_INSERT " . $prefix."\\1 ", $query );
		}

		return $query;
	}

	/**
	 * Add a  message to the upgader log
	 *
	 * @access		public
	 * @param		string		Main error message
	 * @param		string		Upgrade version
	 * @param		string		Upgrade App
	 */
	static public function addLogMessage( $message, $version, $app='core' )
	{
		$file_name = DOC_IPS_ROOT_PATH . 'cache/sql_upgrade_log_'.date('m_d_y').'.cgi';

		$_error_string  = "\n===================================================";
		$_error_string .= "\n Date: ". date( 'r' );
		$_error_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
		$_error_string .= "\n Application " . $app;
		$_error_string .= "\n Version Folder: " . $version;
		$_error_string .= "\n Current Sub Step: " . ipsRegistry::$request['do'];
		$_error_string .= "\n Current workact: " . ipsRegistry::$request['workact'];
		$_error_string .= "\n\n\n ".$message;

		if ( $fh = @fopen( $file_name, "a" ) )
		{
			@fwrite( $fh, $_error_string, strlen( $_error_string ) );
			@fclose( $fh );
		}
	}

	/**
	 * Fetch version classes
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @param	int			Start version number
	 * @param	int			Ending version number
	 * @return	array
	 */
	static public function fetchVersionClasses( $app, $from, $to )
	{
		/* INIT */
		$classes = array();

		/* Make sure we have stuff to get.. */
		if ( ! $from OR ! $to )
		{
			return $classes;
		}

		/* Search for files... */
		$dh = opendir( IPSLib::getAppDir( $app ) . '/setup/versions' );

 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
			if ( is_dir( IPSLib::getAppDir( $app ) . '/setup/versions/' . $file ) )
			{
				if ( $file != "." && $file != ".." && $file != 'install' )
				{
					if ( strstr( $file, 'upg_' ) )
					{
						$tmp = intval( str_replace( "upg_", "", $file ) );

						if ( $tmp > $from AND $tmp <= $to )
						{
							if ( file_exists( IPSLib::getAppDir( $app ) . '/setup/versions/' . $file . '/version_class.php' ) )
							{
								$classes[] = $tmp;
							}
						}
					}
				}
			}
 		}

		return $classes;
	}

	/**
	 * Fetch current and next app versions
	 *
     * @access	public		Application Directory
     * @return	array 		array( 'current' => array( 000000, '1.0.0 Beta1' ), 'next' => array( 000000, '1.0.0 Beta1' ), 'latest' => array( 000000, '1.0.0 Beta1' ) )
 	 */
	static public function fetchAppVersionNumbers( $app )
	{
		$return = array( 'current' => array( 0, '' ),
						 'next'	   => array( 0, '' ),
						 'latest'  => array( 0, '' ) );

		/* Latest version */
		$XMLVersions = self::fetchXmlAppVersions( $app );
		$tmp         = $XMLVersions;
		krsort( $tmp );

		foreach( $tmp as $long => $human )
		{
			$return['latest'] = array( $long, $human );
			break;
		}

		/* Current Version */
		$DBVersions  = self::fetchDbAppVersions( $app );
		$tmp         = $DBVersions;
		$key         = array_pop( $tmp );

		if ( ! $key OR ! count( $DBVersions ) )
		{
			if ( self::is300plus() === TRUE  )
			{
				$_version = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*',
															 		 'from'   => 'core_applications',
															 		 'where'  => 'app_directory=\'' . $app . '\'' ) );

				$key = intval( $_version['app_long_version'] );
			}
			else
			{
				if ( ipsRegistry::DB()->checkForField( 'upgrade_id', 'upgrade_history' ) )
				{
					/* Now, this table is created in the first step between 1.3 and 2.0, so check for contents */
					$test = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(upgrade_version_id) as max', 'from' => 'upgrade_history' ) );

					$key = ( $test['max'] ) ? $test['max'] : '10003';
				}
				else if ( self::is130plus() === TRUE )
				{
					$key = '10003';
				}
				else if ( self::is120plus() === TRUE )
				{
					$key = '10002';
				}
				else
				{
					$key = '10001';
				}
			}

			/* Blog Version Check */
			if( $app == 'blog' )
			{
				if ( ipsRegistry::DB()->checkForField( 'blog_upgrade_id', 'blog_upgrade_history' ) )
				{
					$test = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(blog_version_id) as max', 'from' => 'blog_upgrade_history' ) );
					$key  = ( $test['max'] ) ? $test['max'] : $key;
				}
				else
				{
					$key = 0;
				}
			}
			/* Gallery Version Check */
			else if( $app == 'gallery' )
			{
				if ( ipsRegistry::DB()->checkForField( 'gallery_upgrade_id', 'gallery_upgrade_history' ) )
				{
					$test = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(gallery_version_id) as max', 'from' => 'gallery_upgrade_history' ) );
					$key  = ( $test['max'] ) ? $test['max'] : $key;
				}
				else
				{
					$key = 0;
				}
			}
			else if( $app == 'downloads' )
			{
				if ( ipsRegistry::DB()->checkForField( 'idm_upgrade_id', 'idm_upgrade_history' ) )
				{
					$test = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(idm_version_id) as max', 'from' => 'idm_upgrade_history' ) );
					$key  = ( $test['max'] ) ? $test['max'] : $key;
				}
				else
				{
					$key = 0;
				}
			}
		}

		$return['current'] = ( $key ) ? array( $key, $XMLVersions[ $key ] ) : array( 0, 'install' );

		/* Next version */
		$nextKey = 0;

		foreach( $XMLVersions as $long => $human )
		{
			if ( $long > $return['current'][0] )
			{
				$nextKey = $long;
				break;
			}
		}

		$return['next'] = array( $nextKey, $XMLVersions[ $nextKey ] );

		return $return;
	}

	/**
	 * Fetch directory versions
	 *
	 * @access	public
	 * @param	string		Application directory
	 * @return	array 		Array of data
	 */
	static public function fetchAppDirStructure( $app )
	{
		$versions = array();

		$dh = opendir( IPSLib::getAppDir( $app ) . '/setup/versions' );

 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
			if ( is_dir( IPSLib::getAppDir( $app ) . '/setup/versions/' . $file ) )
			{
				if ( $file != "." && $file != ".." && $file != 'install' )
				{
					if ( strstr( $file, 'upg_' ) )
					{
						$tmp = str_replace( "upg_", "", $file );
						$versions[ $tmp ] = $tmp;
					}
				}
			}
 		}

 		closedir( $dh );

 		sort($versions);

 		return $versions;
	}

	/**
	 * Fetch app DB versions
	 *
     * @access	public		Application Directory
     * @return	array
 	 */
	static public function fetchDbAppVersions( $app )
	{
		/* INIT */
		$versions = array();

		/* 2.x+? */
		if ( self::is200plus() === TRUE )
		{
			if ( ipsRegistry::DB()->checkForField( "upgrade_app", "upgrade_history" ) )
			{
				ipsRegistry::DB()->build( array( 'select' => '*',
												 'where'  => 'upgrade_app=\'' . $app . '\'',
										 		 'from'   => 'upgrade_history',
										 		 'order'  => 'upgrade_version_id ASC' ) );
				ipsRegistry::DB()->execute();

				while( $r = ipsRegistry::DB()->fetch() )
				{
					$versions[ $r['upgrade_version_id'] ] = $r['upgrade_version_id'];
				}
			}

			/* Ok, field is there but there's nothing, so.. */
			if ( ! count( $versions ) )
			{
				if ( $app == 'core' )
				{
					ipsRegistry::DB()->build( array( 'select' => '*',
											 		 'from'   => 'upgrade_history',
											 		 'order' =>  'upgrade_version_id ASC' ) );
					ipsRegistry::DB()->execute();

					while( $r = ipsRegistry::DB()->fetch() )
					{
						$versions[ $r['upgrade_version_id'] ] = $r['upgrade_version_id'];
					}
				}
				else if ( $app == 'blog' AND ipsRegistry::DB()->checkForField( "blog_version_id", "blog_upgrade_history" ) )
				{
					ipsRegistry::DB()->build( array( 'select' => '*',
											 		 'from'   => 'blog_upgrade_history',
											 		 'order' =>  'blog_version_id ASC' ) );
					ipsRegistry::DB()->execute();

					while( $r = ipsRegistry::DB()->fetch() )
					{
						$versions[ $r['blog_version_id'] ] = $r['blog_version_id'];
					}
				}
				else if ( $app == 'gallery' AND ipsRegistry::DB()->checkForField( "gallery_version_id", "gallery_upgrade_history" ) )
				{
					ipsRegistry::DB()->build( array( 'select' => '*',
											 		 'from'   => 'gallery_upgrade_history',
											 		 'order' =>  'gallery_version_id ASC' ) );
					ipsRegistry::DB()->execute();

					while( $r = ipsRegistry::DB()->fetch() )
					{
						$versions[ $r['gallery_version_id'] ] = $r['gallery_version_id'];
					}
				}
				else if ( $app == 'downloads' AND ipsRegistry::DB()->checkForField( "idm_version_id", "downloads_upgrade_history" ) )
				{
					ipsRegistry::DB()->build( array( 'select' => '*',
											 		 'from'   => 'downloads_upgrade_history',
											 		 'order' =>  'idm_version_id ASC' ) );
					ipsRegistry::DB()->execute();

					while( $r = ipsRegistry::DB()->fetch() )
					{
						$versions[ $r['idm_version_id'] ] = $r['idm_version_id'];
					}
				}
			}
		}

		ksort( $versions );

		return $versions;
	}

	/**
	 * Fetch Apps XML Information File
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @return	array 		..of data
	 */
	static public function fetchXmlAppVersions( $app )
	{
		/* INIT */
		$versions = array();

		/* Fetch core writeable files */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );

		try
		{
			$xml->load( IPSLib::getAppDir( $app ) . '/xml/versions.xml' );

			/* Fetch general information */
			foreach( $xml->fetchElements( 'version' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				$versions[ $data['long'] ] = $data['human'];
			}

			ksort( $versions );

			return $versions;
		}
		catch( Exception $error )
		{
			$this->registry->output->addError( IPS_ROOT_PATH . 'applications/' . $app . '/xml/versions.xml' );
			return FALSE;
		}
	}

	/**
	 * Simple method for writing a file
	 *
	 * @access	public
	 * @param	string	$name	Full name, including path, of file to write
	 * @param	string	$data	Contents of file
	 * @return	bool	True
	 */
	static public function writeFile( $name, $data )
	{
		$fh = @fopen( $name, "w" );
		@fwrite( $fh, $data, strlen( $data ) );
		@fclose( $fh );

		return true;
	}

	/**
	 * Fetch next application
	 *
	 * @access	public
	 * @param	string		Previous application
	 * @param	string		XML file to search for. If supplied, function will return next app that has the XML file. Use {app} for $app ({app}_settings.xml)
	 * @return	mixed
	 */
	static public function fetchNextApplication( $previous='', $xmlFileSearch='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$apps    = explode( ",", IPSSetUp::getSavedData('install_apps') );
		$return  = FALSE;
		$flag    = ( $previous ) ? 0 : 1;
		$array   = array();

		if ( ! count( $apps ) OR ! count( $apps ) )
		{
			return FALSE;
		}

		foreach( $apps as $_app )
		{
			# Looking for an XML file?
			if ( $xmlFileSearch )
			{
				$_xmlFileSearch = str_replace( '{app}', $_app, $xmlFileSearch );

				if ( ! file_exists( IPSLib::getAppDir( $_app ) . '/xml/' . $_xmlFileSearch ) )
				{
					continue;
				}
			}

			# Flag raised? Grab it!
			if ( $flag )
			{
				$return = $_app;
				break;
			}

			# Got this one? Set the flag
			if ( $_app == $previous )
			{
				$flag = 1;
			}
		}

		//-----------------------------------------
		// Got something?
		//-----------------------------------------

		if ( $return )
		{
			return self::fetchXmlAppInformation( $return );
		}

		return FALSE;
	}

	/**
	 * Fetch Apps XML Writeable File information
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @return	array 		..of data
	 */
	static public function fetchXmlAppWriteableFiles( $app )
	{
		/* INIT */
		$info  = array( 'notexist' => array(), 'notwrite' => array(), 'other' => array() );
		$file  = IPSLib::getAppDir( $app ) . '/xml/writeablefiles.xml';

		/**
		 * Custom error checker routine...
		 */
		if( file_exists( IPSLib::getAppDir( $app ) . '/setup/versions/install/installCheck.php' ) )
		{
			require_once( IPSLib::getAppDir( $app ) . '/setup/versions/install/installCheck.php' );
			$checkerClass	= $app . '_installCheck';
			
			if( class_exists($checkerClass) )
			{
				$checker		= new $checkerClass;
				$info			= $checker->checkForProblems();
			}
		}

		/* Got a file? */
		if ( ! file_exists( $file ) )
		{
			return $info;
		}

		/* Fetch app writeable files */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );

		try
		{
			$xml->load( $file );

			foreach( $xml->fetchElements( 'file' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				if ( $data['path'] )
				{
					$_path = DOC_IPS_ROOT_PATH . $data['path'];

					if ( ! file_exists( $_path ) )
					{
						if ( $data['dir'] )
						{
							if ( ! @mkdir( $_path, 0777, TRUE ) )
							{
								$info['notexist'][] = $_path;
							}
						}
						else
						{
							$info['notexist'][] = $_path;
						}
					}
					else if ( ! is_writeable( $_path ) )
					{
						if ( ! @chmod( $_path, 0777 ) )
						{
							$info['notwrite'][] = $_path;
						}
					}
				}
			}

			return $info;
		}
		catch( Exception $error )
		{
			$this->registry->output->addError( $file );
			return FALSE;
		}
	}

	/**
	 * Fetch Apps XML Information File
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @return	array 		..of data
	 */
	static public function fetchXmlAppInformation( $app )
	{
		/* INIT */
		$info = array();

		/* Fetch core writeable files */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );

		try
		{
			$xml->load( IPSLib::getAppDir( $app ) . '/xml/information.xml' );

			/* Fetch general information */
			foreach( $xml->fetchElements( 'data' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				$info['name']				= $data['name'];
				$info['title']				= $data['name'];
				$info['author']				= $data['author'];
				$info['description']		= $data['description'];
				$info['public_name']		= $data['public_name'];
				$info['disabledatinstall']	= ( $data['disabledatinstall'] ) ? 1 : 0;
				$info['key']				= $app;
				$info['ipskey']				= $data['ipskey'];
				$info['hide_tab']			= $data['hide_tab'];
			}

			/* Fetch template information */
			foreach( $xml->fetchElements( 'template' ) as $template )
			{
				$name  = $xml->fetchItem( $template );
				$match = $xml->fetchAttribute( $template, 'match' );

				if ( $name )
				{
					$info['templates'][ $name ] = $match;
				}
			}

			return $info;
		}
		catch( Exception $error )
		{
			$this->registry->output->addError( IPS_ROOT_PATH . 'applications/' . $app . '/xml/information.xml' );
			return FALSE;
		}
	}

	/**
	 * Fetch Apps XML Modules File
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @return	array 		..of data
	 */
	static public function fetchXmlAppModules( $app )
	{
		//-----------------------------------------
		// No modules?
		//-----------------------------------------
		
		if( !file_exists( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_modules.xml' ) )
		{
			return array();
		}
		
		/* INIT */
		$modules = array();

		/* Fetch core writeable files */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );

		try
		{
			require_once( IPS_KERNEL_PATH . 'classXML.php' );
			$xml    = new classXML( IPSSetUp::charSet );

			$xml->load( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_modules.xml' );

			/* Fetch info */
			foreach( $xml->fetchElements( 'module' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );
				
				if( $data['sys_module_id'] )
				{
					unset($data['sys_module_id']);
				}

				$modules[ $data['sys_module_key'] . intval($data['sys_module_admin']) ] = $data;
			}

			return $modules;
		}
		catch( Exception $error )
		{
			return FALSE;
		}
	}

	/**
	 * Saved Data Get
	 *
	 * @access	public
	 * @param	string		Key to return value of
	 * @return	mixed		False, or array of data
	 */
	static function getSavedData( $key )
	{
		return isset( self::$_savedData[ $key ] ) ? self::$_savedData[ $key ] : FALSE;
	}

	/**
	 * Get Saved Data As Array
	 *
	 * @access	public
	 * @return	array
	 */
	static function getSavedDataAsArray()
	{
		return is_array( self::$_savedData ) ? self::$_savedData : array();
	}

	/**
	 * Saved Data Set
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	string		Value
	 * @return	void
	 */
	static function setSavedData( $key, $value )
	{
		//-----------------------------------------
		// Driver needs to be lowercased
		//-----------------------------------------

		if( $key == 'sql_driver' )
		{
			$value	= strtolower($value);
		}

		self::$_savedData[ $key ] = $value;
	}

	/**
	 * Freeze data
	 *
	 * @access	public
	 * @return	string		"Frozed" data for saving
	 */
	static function freezeSavedData()
	{
		/* Encode */
		$data = urlencode( serialize( self::$_savedData ) );

		/* Write to our cache file */
		if ( is_array( self::$_savedData ) )
		{
			self::_writeFreezerCache( $data );
		}

		/* Return */
		return $data;
	}

	/**
	 * Thaw Data
	 *
	 * @access	public
	 * @param	string		Raw data for thawing
	 * @return	void
	 */
	static function thawSavedData( $data='' )
	{
		if ( ! $data )
		{
			/* Check cache file first */
			$data = self::_readFreezerCache();

			/* Nothing? Try post data */
			if ( ! $data )
			{
				$data = $_POST['_sd'];
			}
		}

		self::$_savedData = unserialize( urldecode( $data ) );
	}

	/**
	 * Writes the frozen data to a cache
	 *
	 * @access	private
	 * @return	string
	 */
	private static function _readFreezerCache()
	{
		if ( IPS_IS_UPGRADER )
		{
			$_data = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*',
															  'from'   => 'upgrade_sessions',
															  'where'  => 'session_id=\'' . ipsRegistry::$request['s'] . '\'' ) );
			return $_data['session_data'];
		}
		else
		{
			if ( file_exists( DOC_IPS_ROOT_PATH . 'conf_global.php' ) )
			{
				require( DOC_IPS_ROOT_PATH . 'conf_global.php' );
				return trim( $VAR );
			}
		}

		return FALSE;
	}

	/**
	 * Writes the frozen data to a cache
	 *
	 * @access	private
	 * @param	string		Encoded data to write
	 * @return	void
	 */
	private static function _writeFreezerCache( $data )
	{
		if ( IPS_IS_UPGRADER )
		{
			ipsRegistry::DB()->update( 'upgrade_sessions', array( 'session_data' => $data ), 'session_id=\'' . ipsRegistry::$request['s'] . '\'' );
		}
		else
		{
			if ( file_exists( DOC_IPS_ROOT_PATH . 'conf_global.php' ) AND is_writeable( DOC_IPS_ROOT_PATH . 'conf_global.php' ) )
			{
				$_data = file_get_contents( DOC_IPS_ROOT_PATH . 'conf_global.php' );

				if ( $_data )
				{
					$fmt   = "/*~~DATA~~*/\n\$VAR = <<<EOF\n" . $data .  "\nEOF;\n/**/";
					$_data = preg_replace( "#(\n{1,})?/\*~~DATA~~\*/(.+?)/\*\*/#s", "", $_data );

					$_data = preg_replace( "#\?\>$#", $fmt . "\n?>", $_data );

					@file_put_contents( DOC_IPS_ROOT_PATH . 'conf_global.php', $_data );
				}
			}
		}
	}
}