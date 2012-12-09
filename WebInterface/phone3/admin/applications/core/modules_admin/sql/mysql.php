<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * mySQL Admin
 * Last Updated: $Date: 2009-07-06 21:23:35 -0400 (Mon, 06 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4843 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

@set_time_limit(1200);


class admin_core_sql_toolbox_module extends ipsCommand {

	/**
	 * SQL version
	 *
	 * @access	public
	 * @var		string
	 */
	public $sql_version		= "";

	/**
	 * True SQL version
	 *
	 * @access	public
	 * @var		string
	 */
	public $true_version	= "";

	/**
	 * GZIP Header contents for backup
	 *
	 * @access	public
	 * @var		string
	 */
	public $str_gzip_header	= "\x1f\x8b\x08\x00\x00\x00\x00\x00";

	/**
	 * Flag that's set if there's problems
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $db_has_issues	 = false;

	/**
	 * HTML object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

	/**
	 * Form code
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= "";

	/**
	 * Form code js
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code_js	= "";

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
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_sql' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_sql' ) );

		/* URLs */
		$this->form_code	= $this->html->form_code	= 'module=sql&amp;section=toolbox';
		$this->form_code_js	= $this->html->form_code_js	= 'module=sql&section=toolbox';

		/* Get mySQL Version */
		$this->DB->getSqlVersion();

		$this->true_version	= $this->html->true_version	= $this->DB->true_version;
   		$this->sql_version	= $this->html->sql_version	= $this->DB->sql_version;

   		/* What to do */
		switch( $this->request['do'] )
		{
			case 'search_index':
				$this->sqlSearchIndexOverview();
			break;

			case 'rebuild_app_search_index':
				$this->sqlSearchRebuildAppIndex();
			break;

			case 'dotool':
				$this->sqlRunTool();
			break;

			case 'runtime':
				$this->sqlViewResults("SHOW STATUS");
			break;

			case 'system':
				$this->sqlViewResults("SHOW VARIABLES");
			break;

			case 'processes':
				$this->sqlViewResults("SHOW PROCESSLIST");
			break;

			case 'runsql':
				$_POST['query'] = isset( $_POST['query'] ) ? $_POST['query'] : '';
				$q = $_POST['query'] == "" ? urldecode( $_GET['query'] ) : $_POST['query'];
				$this->sqlViewResults( trim( stripslashes( $q ) ) );
			break;

			case 'backup':
				$this->sqlBackupForm();
			break;

			case 'safebackup':
				$this->sqlSafeBackupSplash();
			break;

			case 'dosafebackup':
				$this->sqlDoSafeBackup();
			break;

			case 'export_tbl':
				$this->sqlDoSafeBackup( trim( urldecode( stripslashes( $_GET['tbl'] ) ) ) );
			break;

			default:
				$this->sqlListIndex();
			break;
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Rebuild the index for the selected application
	 *
	 * @access	public
	 * @return	void
	 */
	public function sqlSearchRebuildAppIndex()
	{
		/* INI */
		$app   = $this->request['app_key'];
		$st    = intval( $this->request['st'] );
		$pergo = 100;

		/* Get the interface */
		require_once( IPS_ROOT_PATH . 'sources/interfaces/interface_search.php' );

		/* Check the app */
		if( ! IPSLib::appIsInstalled( $app ) )
		{
 			$this->registry->output->showError( $this->lang->words['my_noapp'], 11144 );
		}

		/* Rebuild Class */
		$_file  = IPSLib::getAppDir( $app ) . '/extensions/searchRebuild.php';
		$_class = $app . 'SearchRebuild';

		/* Check the file */
		if( file_exists( $_file ) )
		{
			/* Get the rebuild class */
			require_once( $_file );

			/* Check the class */
			if( class_exists( $_class ) )
			{
				/* Setup the rebuild class */
				$rebuild = new $_class();

				/* Clear if this is the first pass */
				if( $st == 0 && ! $this->request['rebuild_type'])
				{
					$rebuild->clear();
				}

				/* Do the rebuild */
				$processed = $rebuild->doRebuild( $st, $pergo );

				/* Check processed */
				if( is_array( $processed ) )
				{
					$rebuild_type_url = '&amp;rebuild_type=' . $processed['type'];
					$rebuild_type     = $processed['type'];
					$processed        = $processed['total'];
				}
				else
				{
					$rebuild_type_url = '';
					$rebuild_type     = '';
				}

				/* Get st again, in case the module updated it */
				if( $st != intval( $this->request['st'] ) )
				{
					$st = intval( $this->request['st'] );
				}
				else
				{
					$st += $pergo;
				}

				/* Message */
				if( $processed > 0 || ( $rebuild_type && $rebuild_type != 'done' ) )
				{
					$title = ipsRegistry::$applications[$app]['app_title'] . $this->lang->words['my_si_progress'];
					$msg   = "{$processed} " . $this->lang->words['my_processed'];
					$url   = "{$this->form_code}&amp;do=rebuild_app_search_index&amp;app_key={$app}&amp;st={$st}{$rebuild_type_url}";
				}
				else
				{
					$title = ipsRegistry::$applications[$app]['app_title'] . $this->lang->words['my_si_complete'];
					$msg   = "";
					$url   = "{$this->form_code}&amp;do=search_index";
				}

				/* Bounce */
				$this->registry->output->doneScreen( $title, $msg, $url, 'redirect' );
			}
			else
			{
				$this->registry->output->showError( sprintf( $this->lang->words['my_noloc_class'], $_class ), 2112, true );
			}
		}
		else
		{
			$this->registry->output->showError( sprintf( $this->lang->words['my_noloc'], $_file ), 2113, true );
		}

	}

	/**
	 * SQL Search Index
	 *
	 * @access	public
	 * @return void
	 */
	public function sqlSearchIndexOverview()
	{
		/* Query the overview data */
		$this->DB->query( "SHOW TABLE STATUS FROM {$this->DB->obj['sql_database']} LIKE 'search_index' " );
		$table_data = $this->DB->fetch();

		/* Format */
		$table_data['Data_length']     = IPSLib::sizeFormat( $table_data['Data_length'] );
		$table_data['Max_data_length'] = IPSLib::sizeFormat( $table_data['Max_data_length'] );
		$table_data['Index_length']    = IPSLib::sizeFormat( $table_data['Index_length'] );

		/* Check for searchable apps */
		$searchable_apps = array();

		foreach( ipsRegistry::$applications as $app )
		{
			if( IPSSearchIndex::appIsSearchable( $app['app_directory'] ) )
			{
				$searchable_apps[ $app['app_directory'] ] = array( 'title' => $app['app_title'] );
			}
		}

		/* Count the apps */
		$this->DB->build( array( 'select' => 'count(*) as total, app', 'from' => 'search_index', 'group' => 'app' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$searchable_apps[ $r['app'] ]['entries'] = $r['total'];
		}

		/* Output */
		$this->registry->output->html .= $this->html->sqlSearchIndex( $table_data, $searchable_apps );
	}

	/**
	 * Run selected tool on selected tables
	 *
	 * @access	public
	 * @return	void
	 */
	public function sqlRunTool()
	{
		/* have we got some there tables me laddo? */
		$tables = array();

 		foreach( $this->request as $key => $value )
 		{
 			if( preg_match( "/^tbl_(\S+)$/", $key, $match ) )
 			{
 				if( $this->request[ $match[0] ] )
 				{
 					$tables[] = $match[1];
 				}
 			}
 		}

 		/* Make sure we have tables to run this on */
 		if( count( $tables ) < 1 )
 		{
 			$this->registry->output->showError( $this->lang->words['my_seltables'], 11145 );
 		}

		/* What tool is one running? */
		if( strtoupper( $this->request['tool'] ) == 'DROP' || strtoupper( $this->request['tool'] ) == 'CREATE' || strtoupper( $this->request['tool'] ) == 'FLUSH' )
		{
			$this->registry->output->showError( $this->lang->words['my_cantdo'], 2114, true );
		}

		/**
		 * Rikki wants a stupid template so we can output his stupid header.  Yes, this creates more work for me.
		 * This template serves no other purpose than to make Rikki happy.  Must make Rikki happy.  Rikki gets cranky when he's not happy.
		 *
		 * Ask him about roundabouts sometime.  Apparently...they're round.
		 */
		$this->registry->output->html .= $this->html->sqlToolResultHeader();

		/* Loop through each table and run the tool */
		foreach( $tables as $table )
		{
			/* Run the query */
			$this->DB->query( strtoupper( $this->request['tool'] ) . " TABLE $table" );

			/* Results */
			$fields = $this->DB->getResultFields();
			$data   = $this->DB->fetch();

			/* Print the headers - we don't what or how many so... */
			$columns = array();
			$cnt     = count( $fields );

			for( $i = 0; $i < $cnt; $i++ )
			{
				$columns[] = $fields[$i]->name;
			}

			/* Grab the rows - we don't what or how many so... */
			$rows = array();

			for( $i = 0; $i < $cnt; $i++ )
			{
				$rows[] = $data[ $fields[$i]->name ];
			}

			/* Add to output */
			$this->registry->output->html .= $this->html->sqlToolResult( "Result: " . $this->request['tool'] . " " . $table, $columns, $rows );
		}
	}

	/**
	 * Safe backup splash screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function sqlSafeBackupSplash()
	{
		/* Output */
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['my_backupnav'] );
		$this->registry->output->html            .= $this->html->sqlSafeBackupSplashScreen();
	}

	/**
	 * Runs the database backup
	 *
	 * @access	public
	 * @param	string	$tbl_name	Specify a table to backup, leave blank to backup all
	 * @return	void
	 */
	public function sqlDoSafeBackup($tbl_name="")
	{
		/* Backup All Tables */
		if( $tbl_name == "" )
		{
			$skip        = intval( $this->request['skip'] );
			$create_tbl  = intval( $this->request['create_tbl'] );
			$enable_gzip = intval( $this->request['enable_gzip'] );
			$filename    = 'ipb_db_backup';
		}
		/* Backup specfic table */
		else
		{
			$skip        = 0;
			$create_tbl  = 0;
			$enable_gzip = 1;
			$filename    = $tbl_name;
		}

		/* Setup */
		$output = "";
		@header("Pragma: no-cache");
		$do_gzip = 0;

		/* Gzip? */
		if( $enable_gzip )
		{
			$phpver = phpversion();

			if($phpver >= "4.0")
			{
				if(extension_loaded("zlib"))
				{
					$do_gzip = 1;
				}
			}
		}

		if( $do_gzip != 0 )
		{
			@ob_start();
			@ob_implicit_flush(0);
			header("Content-Type: text/x-delimtext; name=\"{$filename}.sql.gz\"");
			header("Content-disposition: attachment; filename={$filename}.sql.gz");
		}
		else
		{
			header("Content-Type: text/x-delimtext; name=\"{$filename}.sql\"");
			header("Content-disposition: attachment; filename={$filename}.sql");
		}

		/* Get tables to work on */
		if( $tbl_name == "" )
		{
			$tmp_tbl = $this->DB->getTableNames();

			foreach( $tmp_tbl as $tbl )
			{
				/* Ensure that we're only peeking at IBF tables */
				if ( preg_match( "/^" . $this->settings['sql_tbl_prefix'] . "/", $tbl ) )
				{
					/* We've started our headers, so print as we go to stop  poss memory problems */
					$this->sqlGetTableSQL($tbl, $create_tbl, $skip);
				}
			}
		}
		else
		{
			$this->sqlGetTableSQL($tbl_name, $create_tbl, $skip);
		}

		/* Gzip the result */
		if( $do_gzip )
		{
			$size     = ob_get_length();
			$crc      = crc32( ob_get_contents() );
			$contents = gzcompress( ob_get_contents() );
			ob_end_clean();
			echo $this->str_gzip_header . substr( $contents, 0, strlen( $contents ) - 4 ) . $this->sqlGzipFourChars( $crc ) . $this->sqlGzipFourChars( $size );
		}

		exit();
	}

	/**
	 * Displays the form for backing up an sql database
	 *
	 * @access	public
	 * @return	void
	 * @author	Josh
	 */
	public function sqlBackupForm()
	{
		/* Check mySQL Version */
		if ( $this->sql_version < 3232 )
		{
			$this->registry->output->showError( $this->lang->words['my_tooold'], 11146 );
		}

		/* Form Elements */
		$form = array();

		$form['create_tbl']  = $this->registry->output->formYesNo( 'create_tbl', 1);
		$form['skip']        = $this->registry->output->formYesNo( 'skip', 1);
		$form['enable_gzip'] = $this->registry->output->formYesNo( 'enable_gzip', 0 );

		/* Output */
		$this->registry->output->html           .= $this->html->sqlBackupForm( $form );
	}

	/**
	 * Run a mysql query and display results
	 *
	 * @access	public
	 * @param	string	$sql	The query to run
	 * @return	void
	 */
	public function sqlViewResults( $sql )
	{
		/* INIT */
		$limit			= 50;
		$pages			= "";
		$the_queries	= array();

		/* Title Map */
		$map = array( 'processes' 	=> $this->lang->words['my_processes'],
					  'runtime'   	=> $this->lang->words['my_runtime'],
					  'system'    	=> $this->lang->words['my_sysvar'],
					);

		/* Figure out the title */
		if ( isset( $map[ $this->request['do'] ] ) AND $map[ $this->request['do'] ] != "" )
		{
			$tbl_title = $map[ $this->request['do'] ];
			$man_query = 0;
		}
		else
		{
			$tbl_title = $this->lang->words['my_manual'];
			$man_query = 1;
		}

		/* Turn off error die */
		$this->DB->return_die = 1;

		/* Split up multiple queries */
		$the_queries = array();

		if( strstr( $sql, ";" ) )
		{
			$the_queries = preg_split( "/;[\r\n|\n]+/", $sql, -1, PREG_SPLIT_NO_EMPTY );
		}
		else
		{
			if( $sql )
			{
				$the_queries[] = $sql;
			}
		}

		$columns			= array();
		$rows				= array();
		$queryCntForArray	= 0;

		if( ! count( $the_queries ) )
		{
			$the_queries[ $queryCntForArray ]	= '';
			$columns[ $queryCntForArray ][]		= $this->lang->words['manual_error'];
			$rows[ $queryCntForArray ][]		= array( 'error' => $this->lang->words['manual_noquery'] );
		}
		else
		{
			/* Loop through the queries and run them */
			foreach( $the_queries as $sql )
			{
				/* INIT */
				$links 	= "";
				$sql 	= trim( $sql );

				/* Check the sql */
				$test_sql = str_replace( "\'", "", $sql );
				$apos_count = substr_count( $test_sql, "'" );

				if( $apos_count % 2 != 0 )
				{
					$columns[ $queryCntForArray ][]	= $this->lang->words['manual_error'];
					$rows[ $queryCntForArray ][]	= array( 'error' => $this->lang->words['manual_invalid'] . htmlspecialchars( $sql ) );

					unset( $apos_count, $test_sql );
					continue;
				}

				unset( $apos_count, $test_sql );

				/* Check for drop and flush */
				if ( preg_match( "/^(DROP|FLUSH)/i",$sql ) )
				{
					$columns[ $queryCntForArray ][]	= $this->lang->words['manual_error'];
					$rows[ $queryCntForArray ][]	= array( 'error' => $this->lang->words['manual_notallowed'] );
					continue;
				}
				/* Protect admin_login_logs */
				else if ( preg_match( "/^(?!SELECT)/i", preg_replace( "#\s{1,}#s", "", $sql ) ) and preg_match( "/admin_login_logs/i", preg_replace( "#\s{1,}#s", "", $sql ) ) )
				{
					$columns[ $queryCntForArray ][]	= $this->lang->words['manual_error'];
					$rows[ $queryCntForArray ][]	= array( 'error' => $this->lang->words['manual_loginlogs'] );
					continue;
				}

				/* Setup for query */
				$this->DB->error = "";
				$this->DB->allow_sub_select = 1;

				/* Run the query */
				$this->DB->query( $sql, 1 );

				/* Check for errors... */
				if ( $this->DB->error != "")
				{
					$columns[ $queryCntForArray ][]	= $this->lang->words['manual_error'];
					$rows[ $queryCntForArray ][]	= array( 'error' => htmlspecialchars( $this->DB->error ) );
					continue;
				}

				/* Build display rows */
				$rows[ $queryCntForArray ]		= array();
				$columns[ $queryCntForArray ]	= array();

				if ( preg_match( "/^SELECT/i", $sql ) or preg_match( "/^SHOW/i", $sql ) )
				{
					/* Sort out the pages and stuff, auto limit if need be */
					if ( !preg_match( "/^SHOW/i", $sql ) AND ! preg_match( "/LIMIT[ 0-9,]+$/i", $sql ) )
					{
						/* Start value */
						$start = $this->request['st'] ? intval( $this->request['st'] ) : 0;

						/* Count the number of rows we got back */
						$rows_returned = $this->DB->getTotalRows();

						/* Paginate the results */
						if( $rows_returned > $limit )
						{
							$links = $this->registry->output->generatePagination( array(
																						'totalItems'        => $rows_returned,
																						'itemsPerPage'      => $limit,
																						'currentStartValue' => $start,
																						'baseUrl'           => "{$this->settings['base_url']}{$this->form_code}&do=runsql&query=" . urlencode( $sql ),
																				)	);

							/* Reformat the query with a LIMIT */
							if( substr( $sql, -1, 1 ) == ";" )
							{
								$sql = substr( $sql, 0, -1 );
							}

							$sql .= " LIMIT $start, $limit";

							/* Re-run with limit */
							$this->DB->query( $sql, 1 );
						}
					}

					/* Create the columns array */
					$fields = $this->DB->getResultFields();
					$cnt    = count( $fields );

					for( $i = 0; $i < $cnt; $i++ )
					{
						$columns[ $queryCntForArray ][] = $fields[$i]->name;
					}

					/* Populate the rows array */
					while( $r = $this->DB->fetch() )
					{
						/* Loop through the results and add to row */
						$row = array();

						for( $i = 0; $i < $cnt; $i++ )
						{
							if( $man_query == 1 )
							{
								if( IPSText::mbstrlen( $r[ $fields[$i]->name ] ) > 200 AND ! preg_match( "/^SHOW/i", $sql ) )
								{
									$r[ $fields[$i]->name ] = IPSText::truncate( $r[ $fields[$i]->name ], 200 ) .'...';
								}
							}

							$row[] = nl2br( htmlspecialchars( wordwrap( $r[ $fields[$i]->name ] , 50, "\n", 1 ) ) );
						}

						/* Add to output array */
						$rows[ $queryCntForArray ][] = $row;
					}
				}
				else
				{
					$columns[ $queryCntForArray ][]	= '';
					$rows[ $queryCntForArray ][]	= array( $this->lang->words['query_executed_successfully'] );
				}

				$this->DB->freeResult();

				$queryCntForArray++;
			}
		}

		/* Output */
		$this->registry->output->html           .= $this->html->sqlViewResults( $the_queries, $columns, $rows, $links );
	}

	/**
	 * SHOW ALL TABLES AND STUFF!
	 * 5 hours ago this seemed like a damned good idea.
	 *
	 * @access	public
	 * @return	void
	 */
	public function sqlListIndex()
	{
		/* INIT */
		$form_array = array();
		$extra 		= "";

		/* Check version */
		if ( $this->sql_version < 3232 )
		{
			$extra = $this->lang->words['my_tooold'];
		}

		/* Get a list of tables */
		$this->DB->query( "SHOW TABLE STATUS FROM `{$this->settings['sql_database']}`" );

		/* Loop through the results */
		$rows = array();

		while( $r = $this->DB->fetch() )
		{
			/* Check to ensure it's a table for this install... */
			if ( ! preg_match( "/^" . $this->settings['sql_tbl_prefix'] ."/i", $r['Name'] ) )
			{
				continue;
			}

			/* Add to output array */
			$rows[] = array(
								'table' => $r['Name'],
								'rows'  => $r['Rows'],
								'query' => urlencode( "SELECT * FROM {$r['Name']}" )
							);
		}

		/* Output */
		$this->registry->output->html           .= $this->html->sqlListTables( $rows );
	}

	/**
	 * Internal handler to return content from table
	 *
	 * @access	public
	 * @param	string	Table
	 * @param	boolean	Whether to show create table or not
	 * @param	boolean	Whether to skip non-essential tables
	 * @return	void
	 * @author	Josh
	 */
	public function sqlGetTableSQL($tbl, $create_tbl, $skip=0)
	{
		/* Add create table statement? */
		if( $create_tbl )
		{
			/* Generate table structure */
			if( $this->request['addticks'] )
			{
				$this->DB->query( "SHOW CREATE TABLE `{$this->settings['sql_database']}.{$tbl}`" );
			}
			else
			{
				$this->DB->query( "SHOW CREATE TABLE {$this->settings['sql_database']}.{$tbl}" );
			}

			$ctable = $this->DB->fetch();


			echo $this->sqlStripTicks( $ctable['Create Table'] ).";\n";
		}

		/* Are we skipping? Woohoo, where's me rope?! */
		if( $skip == 1 )
		{
			if(
				$tbl == $this->settings['sql_tbl_prefix'].'admin_sessions' OR
				$tbl == $this->settings['sql_tbl_prefix'].'sessions' OR
				$tbl == $this->settings['sql_tbl_prefix'].'captcha'
				)
			{
				return $ret;
			}
		}

		/* Get the data */
		$this->DB->query("SELECT * FROM $tbl");

		/* Check to make sure rows are in this table, if not return. */
		$row_count = $this->DB->getTotalRows();

		if( $row_count < 1 )
		{
			return TRUE;
		}

		/* Get col names */
		$f_list = "";
		$fields = $this->DB->getResultFields();
		$cnt    = count($fields);

		for( $i = 0; $i < $cnt; $i++ )
		{
			$f_list .= $fields[$i]->name . ", ";
		}

		$f_list = rtrim( $f_list, ', ' );

		/* Loop through the rows */
		while ( $row = $this->DB->fetch() )
		{
			/* Get col data */
			$d_list = "";

			for( $i = 0; $i < $cnt; $i++ )
			{
				if ( ! isset($row[ $fields[$i]->name ]) )
				{
					$d_list .= "NULL,";
				}
				elseif ( $row[ $fields[$i]->name ] != '' )
				{
					$d_list .= "'".$this->sqlAddSlashes( $row[ $fields[$i]->name ] ). "',";
				}
				else
				{
					$d_list .= "'',";
				}
			}

			$d_list = rtrim( $d_list, ',' );

			echo "INSERT INTO $tbl ($f_list) VALUES($d_list);\n";
		}

		return TRUE;

	}

	/**
	 * Strip tick marks from field names
	 *
	 * @access	public
	 * @param	string	$data	String to strip tick marks from
	 * @return	string
	 */
	public function sqlStripTicks( $data )
	{
		return str_replace( "`", "", $data );
	}

	/**
	 * Add slashes to single quotes to stop sql breaks
	 *
	 * @access	public
	 * @param	string	$data	String to add slashes too
	 * @return	string
	 */
	public function sqlAddSlashes($data)
	{
		$data = str_replace('\\', '\\\\', $data);
        $data = str_replace('\'', '\\\'', $data);
        $data = str_replace("\r", '\r'  , $data);
        $data = str_replace("\n", '\n'  , $data);

        return $data;
	}

	/**
	 * Gzip
	 *
	 * @access	public
	 * @param	string	$val
	 * @return	string
	 **/
    public function sqlGzipFourChars( $val )
	{
		for ($i = 0; $i < 4; $i ++)
		{
			$return .= chr($val % 256);
			$val     = floor($val / 256);
		}

		return $return;
	}
}