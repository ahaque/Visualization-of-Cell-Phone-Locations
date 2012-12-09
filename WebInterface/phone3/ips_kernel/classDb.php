<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Database Abstraction Layer
 * Last Updated: $Date: 2009-08-19 11:59:06 -0400 (Wed, 19 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Monday 28th February 2005 16:46
 * @version		$Revision: 317 $
 *
 * Basic Usage Examples
 * <code>
 * $db = new db_driver();
 * Update:
 * $db->update( 'table', array( 'field' => 'value', 'field2' => 'value2' ), 'id=1' );
 * Insert
 * $db->insert( 'table', array( 'field' => 'value', 'field2' => 'value2' ) );
 * Delete
 * $db->delete( 'table', 'id=1' );
 * Select
 * $db->build( array( 'select' => '*',
 *						   'from'   => 'table',
 *						   'where'  => 'id=2 and mid=1',
 *						   'order'  => 'date DESC',
 *						   'limit'  => array( 0, 30 ) ) );
 * $db->execute();
 * while( $row = $db->fetch() ) { .... }
 * Select with join
 * $db->build( array( 'select'   => 'd.*',
 * 						   'from'     => array( 'dnames_change' => 'd' ),
 * 						   'where'    => 'dname_member_id='.$id,
 * 						   'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
 * 													 'from'   => array( 'members' => 'm' ),
 * 													 'where'  => 'm.member_id=d.dname_member_id',
 * 													 'type'   => 'inner' ) ),
 * 						   'order'    => 'dname_date DESC' ) );
 *  $db->execute();
 * </code>
 */

/**
 * This can be overridden by using
 * $DB->allow_sub_select = 1;
 * before any query construct
 */

define( 'IPS_DB_ALLOW_SUB_SELECTS', 0 );

/**
 * Database interface
 */
interface interfaceDb
{
    /**
	 * Connect to database server
	 *
	 * @return	boolean		Connection successful
	 */
	public function connect();
	
    /**
	 * Close database connection
	 *
	 * @return	boolean		Closed successfully
	 */
	public function disconnect();
	
	/**
	 * Returns the currently formed SQL query
	 *
	 * @access	public
	 * @return	string
	 */
	public function fetchSqlString();

    /**
	 * Delete data from a table
	 *
	 * @access	public
	 * @param	string 		Table name
	 * @param	string 		[Optional] Where clause
	 * @param	string		[Optional] Order by
	 * @param	array		[Optional] Limit clause
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	resource	Query id
	 */
	public function delete( $table, $where='', $orderBy='', $limit=array(), $shutdown=false );
	
    /**
	 * Update data in a table
	 *
	 * @param	string 		Table name
	 * @param	mixed 		Array of field => values, or pre-formatted "SET" clause
	 * @param	string 		[Optional] Where clause
	 * @param	boolean		[Optional] Run on shutdown
	 * @param	boolean		[Optional] $set is already pre-formatted
	 * @return	resource	Query id
	 */
	public function update( $table, $set, $where='', $shutdown=false, $preformatted=false );
	
    /**
	 * Insert data into a table
	 *
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	resource	Query id
	 */
	public function insert( $table, $set, $shutdown=false );
	
    /**
	 * Insert record into table if not present, otherwise update existing record
	 *
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	array 		Array of fields to check
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	resource	Query id
	 */
	public function replace( $table, $set, $where, $shutdown=false );
	
    /**
	 * Run a "kill" statement
	 *
	 * @param	integer 	Thread ID
	 * @return	resource	Query id
	 */
	public function kill( $threadId );

    /**
	 * Takes array of set commands and generates a SQL formatted query
	 *
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	void
	 */
	public function build( $data );
	
    /**
	 * Build a query based on template from cache file
	 *
	 * @param	string		Name of query file method to use
	 * @param	array		Optional arguments to be parsed inside query function
	 * @param	string		Optional class name
	 * @return	void
	 */
	public function buildFromCache( $method, $args=array(), $class='sql_queries' );
	
    /**
	 * Executes stored SQL query
	 *
	 * @return	resource	Query ID
	 */
	public function execute();
	
    /**
	 * Stores a query for shutdown execution
	 *
	 * @return	mixed		Query ID or void
	 */
	public function executeOnShutdown();
	
    /**
	 * Generates and executes SQL query, and returns the first result
	 *
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	array		First result set
	 */
	public function buildAndFetch( $data );
	
    /**
	 * Execute a direct database query
	 *
	 * @param	string		Database query
	 * @param	boolean		[Optional] Do not convert table prefix
	 * @return	resource	Query id
	 */
	public function query( $the_query, $bypass=false );

    /**
	 * Retrieve number of rows affected by last query
	 *
	 * @return	integer		Number of rows affected by last query
	 */
	public function getAffectedRows();
	
    /**
	 * Retrieve number of rows in result set
	 *
	 * @param	resource	[Optional] Query id
	 * @return	integer		Number of rows in result set
	 */
	public function getTotalRows( $query_id=null );
	
    /**
	 * Retrieve latest autoincrement insert id
	 *
	 * @return	integer		Last autoincrement id assigned
	 */
	public function getInsertId();
	
    /**
	 * Retrieve the current thread id
	 *
	 * @return	integer		Current thread id
	 */
	public function getThreadId();
	
    /**
	 * Free result set from memory
	 *
	 * @param	resource	[Optional] Query id
	 * @return	void
	 */
	public function freeResult( $query_id=null );

    /**
	 * Retrieve row from database
	 *
	 * @param	resource	[Optional] Query result id
	 * @return	mixed		Result set array, or void
	 */
	public function fetch( $query_id=null );
	
	/**
	 * Return the number calculated rows (as if there was no limit clause)
	 *
	 * @access	public
	 * @param	string 		[ alias name for the count(*) ]
	 * @return	int			The number of rows
	 */
	public function fetchCalculatedRows( $alias='count' );
	
    /**
	 * Get array of fields in result set
	 *
	 * @param	resource	[Optional] Query id
	 * @return	array		Fields in result set
	 */
	public function getResultFields( $query_id=null );

    /**
	 * Subqueries supported by driver?
	 *
	 * @return	boolean		Subqueries supported
	 */
	public function checkSubquerySupport();
	
    /**
	 * Fulltext searching supported by driver?
	 *
	 * @return	boolean		Fulltext supported
	 */
	public function checkFulltextSupport();
	
    /**
	 * Boolean fulltext searching supported by driver?
	 *
	 * @return	boolean		Boolean fulltext supported
	 */
	public function checkBooleanFulltextSupport();
	
    /**
	 * Test to see whether a field exists in a table
	 *
	 * @param	string		Field name
	 * @param	string		Table name
	 * @return	boolean		Field exists or not
	 */
	public function checkForField( $field, $table );
	
    /**
	 * Test to see whether a table exists
	 *
	 * @param	string		Table name
	 * @return	boolean		Table exists or not
	 */
	public function checkForTable( $table );
	
    /**
	 * Drop database table
	 *
	 * @param	string		Table to drop
	 * @return	resource	Query id
	 */
	public function dropTable( $table );
	
    /**
	 * Drop field in database table
	 *
	 * @param	string		Table name
	 * @param	string		Field to drop
	 * @return	resource	Query id
	 */
	public function dropField( $table, $field );
	
    /**
	 * Add field to table in database
	 *
	 * @param	string		Table name
	 * @param	string		Field to add
	 * @param	string		Field type
	 * @param	string		[Optional] Default value
	 * @return	resource	Query id
	 */
	public function addField( $table, $field, $type, $default='' );
	
    /**
	 * Change field in database table
	 *
	 * @param	string		Table name
	 * @param	string		Existing field name
	 * @param	string		New field name
	 * @param	string		[Optional] Field type
	 * @param	string		[Optional] Default value
	 * @return	resource	Query id
	 */
	public function changeField( $table, $old_field, $new_field, $type='', $default='' );
	
    /**
	 * Optimize database table
	 *
	 * @param	string		Table name
	 * @return	resource	Query id
	 */
	public function optimize( $table );
	
    /**
	 * Add fulltext index to database column
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @return	resource	Query id
	 */
	public function addFulltextIndex( $table, $field );
	
    /**
	 * Get table schematic
	 *
	 * @param	string		Table name
	 * @return	array		SQL schematic array
	 */
	public function getTableSchematic( $table );
	
    /**
	 * Get array of table names in database
	 *
	 * @return	array		SQL tables
	 */
	public function getTableNames();
	
    /**
	 * Determine if table already has a fulltext index
	 *
	 * @param	string		Table name
	 * @return	boolean		Fulltext index exists
	 */
	public function getFulltextStatus( $table );
	
    /**
	 * Retrieve SQL server version
	 *
	 * @return	string		SQL Server version
	 */
	public function getSqlVersion();

    /**
	 * Set debug mode flag
	 *
	 * @param	boolean		[Optional] Set debug mode on/off
	 * @return	void
	 */
	public function setDebugMode( $enable=false );
	
    /**
	 * Returns current number queries run
	 *
	 * @return	integer		Number of queries run
	 */
	public function getQueryCount();
	
	/**
	 * Flushes the currently queued query
	 *
	 * @return	void
	 */
	public function flushQuery();

    /**
	 * Load extra SQL query file
	 *
	 * @param	string 		File name
	 * @param	string 		Classname of file
	 * @return	void
	 */
	public function loadCacheFile( $filepath, $classname );
	
	/**
	 * Checks to see if a query file has been loaded
	 *
	 * @param	string 		Classname of file
	 * @return	void
	 */
	public function hasLoadedCacheFile( $classname );

	/**
	 * Set fields that shouldn't be escaped
	 *
	 * @param	array 		SQL table fields
	 * @return	void
	 */
	public function preventAddSlashes( $fields=array() );
	
    /**
	 * Compiles SQL fields for insertion
	 *
	 * @param	array		Array of field => value pairs
	 * @return	array		FIELD_NAMES (string) FIELD_VALUES (string)
	 */
	public function compileInsertString( $data );
	
    /**
	 * Compiles SQL fields for update query
	 *
	 * @param	array		Array of field => value pairs
	 * @return	string		SET .... update string
	 */
	public function compileUpdateString( $data );
	
    /**
	 * Escape strings for DB insertion
	 *
	 * @param	string		Text to escape
	 * @return	string		Escaped text
	 */
	public function addSlashes( $t );

    /**
	 * Un-escape strings escaped for DB
	 *
	 * @param	string		Text to un-escape
	 * @return	string		Un-escaped text
	 * @deprecated	No longer used anywhere, drivers just return the string
	 */
	public function removeSlashes( $t );

    /**
	 * Build concat string
	 *
	 * @param	array		Array of data to concat
	 * @return	string		SQL-formatted concat string
	 */
	public function buildConcat( $data );
	
    /**
	 * Build CAST string
	 *
	 * @param	string		Value to CAST
	 * @return	string		Column type to cast as (only UNSIGNED supported at this time!!)
	 */
	public function buildCast( $data, $columnType );
	
    /**
	 * Build between statement
	 *
	 * @param	string		Column
	 * @param	integer		Value 1
	 * @param	integer		Value 2
	 * @return	string		SQL-formatted between statement
	 */
	public function buildBetween( $column, $value1, $value2 );
	
    /**
	 * Build regexp 'or'
	 *
	 * @param	array		Array of values to allow
	 * @return	string		SQL-formatted regex string
	 */
	public function buildRegexp( $column, $data );
	
	/**
	 * Build LIKE CHAIN string (ONLY supports a regexp equivalent of "or field like value")
	 *
	 * @access	public
	 * @param	string		Database column
	 * @param	array		Array of values to allow
	 * @return	string		SQL-formatted like chain string
	 */
	public function buildLikeChain( $column, $data );
	
    /**
	 * Build instr string
	 *
	 * @param	string		String to look for
	 * @param	string		String to look in
	 * @return	string		SQL-formatted instr string
	 */
	public function buildInstring( $look_for, $look_in );
	
    /**
	 * Build substr string
	 *
	 * @param	string		String of characters/Column
	 * @param	integer		Offset
	 * @param	integer		[Optional] Number of chars
	 * @return	string		SQL-formatted substr string
	 */
	public function buildSubstring( $look_for, $offset, $length=0 );
	
    /**
	 * Build distinct string
	 *
	 * @param	string		Column name
	 * @return	string		SQL-formatted distinct string
	 */
	public function buildDistinct( $column );
	
    /**
	 * Build length string
	 *
	 * @param	string		Column name
	 * @return	string		SQL-formatted length string
	 */
	public function buildLength( $column );
	
	/**
	 * Build lower string
	 *
	 * @param	string		Column name
	 * @return	string		SQL-formatted length string
	 */
	public function buildLower( $column );
	
    /**
	 * Build right string
	 *
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	string		SQL-formatted right string
	 */
	public function buildRight( $column, $chars );
	
    /**
	 * Build left string
	 *
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	string		SQL-formatted left string
	 */
	public function buildLeft( $column, $chars );
	
    /**
	 * Build "is null" and "is not null" string
	 *
	 * @param	boolean		is null flag
	 * @return	string		[Optional] SQL-formatted "is null" or "is not null" string
	 */
	public function buildIsNull( $is_null=true );
	
    /**
	 * Build from_unixtime string
	 *
	 * @param	string		Column name
	 * @param	string		[Optional] Format
	 * @return	string		SQL-formatted from_unixtime string
	 */
	public function buildFromUnixtime( $column, $format='' );
	
    /**
	 * Build date_format string
	 *
	 * @param	string		Date string
	 * @param	string		Format
	 * @return	string		SQL-formatted date_format string
	 */
	public function buildDateFormat( $column, $format );
	
    /**
	 * Build fulltext search string
	 *
	 * @param	string		Column to search against
	 * @param	string		String to search
	 * @param	boolean		Search in boolean mode
	 * @param	boolean		Return a "as ranking" statement from the build
	 * @param	boolean		Use fulltext search
	 * @return	string		Fulltext search statement
	 */
	public function buildSearchStatement( $column, $keyword, $booleanMode=true, $returnRanking=false, $useFulltext=true );

    /**
	 * Prints SQL error message
	 *
	 * @param	string		Additional error message
	 * @return	mixed		Output to screen, or return void
	 */
	public function throwFatalError( $the_error='' );

    /**
	 * Logs SQL error message to log file
	 *
	 * @param	string		SQL Query
	 * @param	string		Data to log (i.e. error message)
	 * @param	integer		Timestamp for log
	 * @return	void
	 */
	public function writeDebugLog( $query, $data, $endtime );

}

/**
 * Abstract database class
 */
abstract class dbMain
{
	/**
	 * DB object array
	 *
	 * @access	public
	 * @var 		array 		Object settings
	 */
	public $obj = array(	"sql_database"			=> ""			,
							"sql_user"				=> "root"		,
							"sql_pass"				=> ""			,
							"sql_host"				=> "localhost"	,
							"sql_port"				=> ""			,
							"persistent"			=> "0"			,
							"sql_tbl_prefix"		=> ""			,
							"cached_queries"		=> array()		,
							'shutdown_queries'		=> array()		,
							'debug'					=> 0			,
							'use_shutdown'			=> 1			,
							'query_cache_file'		=> ''			,
							'force_new_connection'	=> 0			,
							'error_log'				=> ''			,
							'use_error_log'			=> 0			,
							'use_debug_log'			=> 0			,
					 );
	
	/**
	 * Error message
	 *
	 * @access	public
	 * @var		string		Error message
	 */
	public $error 				= "";
	
	/**
	 * Error code
	 *
	 * @access	public
	 * @var 	mixed		Error number/code
	 */
	public $error_no			= 0;
	
	/**
	 * Return error message or die inline
	 *
	 * @access	public
	 * @var 	boolean		Return if query fails
	 */
	public $return_die        = false;
	
	/**
	 * DB query failed
	 *
	 * @access	public
	 * @var 	boolean		Last query failed
	 */
	public $failed            = false;
	
	/**
	 * Object reference to query cache file
	 *
	 * @access	protected
	 * @var 	object		Query cache file object
	 */
	protected $sql               = null;
	
	/**
	 * Current sql query
	 *
	 * @access	protected
	 * @var 	string		Current DB query
	 */
	protected $cur_query         = "";
	
	/**
	 * Current DB query ID
	 *
	 * @access	protected
	 * @var 	resource		Last query resource id
	 */
	protected $query_id          = null;
	
	/**
	 * Current DB connection ID
	 *
	 * @access	protected
	 * @var 	resource		Connection id
	 */
	protected $connection_id     = null;
	
	/**
	 * Number of queries run so far
	 *
	 * @access	public
	 * @var 	integer		Number of queries run through driver
	 */
	public $query_count       = 0;
	
	/**
	 * Escape / don't escape slashes during insert ops
	 *
	 * @access	public
	 * @var		boolean		Do not escape strings
	 */
	public $manual_addslashes = false;
	
	/**
	 * Is a shutdown query
	 *
	 * @access	protected
	 * @var 	boolean		Currently shutdown
	 */
	protected $is_shutdown       = false;
	
	/**
	 * Prefix handling
	 *
	 * @access	public
	 * @var		boolean		Prefix has changed
	 */
	public $prefix_changed    = false;
	
	/**
	 * Prefix already converted
	 *
	 * @access	public
	 * @var 	boolean		Do not convert prefix
	 */
	public $no_prefix_convert = false;
	
	/**
	 * DB record row
	 *
	 * @access	public
	 * @var 	array 		Result set
	 */
	public $record_row        = array();
	
	/**
	 * Extra classes loaded
	 *
	 * @access	public
	 * @var 	array 		Query cache file classes loaded
	 */
	public $loaded_classes    = array();
	
	/**
	 * Optimization to stop querying the same loaded cache over and over
	 *
	 * @access	private
	 * @var		array
	 */
	private $_triedToLoadCacheFiles = array();
	
	/**
	 * Connection variables set when installed
	 *
	 * @access	public
	 * @var 	array 		Connection variables
	 */
	public $connect_vars      = array();
	
	/**
	 * Over-ride guessed data types in insert/update ops
	 *
	 * @access	public
	 * @var 	array 		Force data types for columns
	 */
	public $force_data_type   = array();
	
	/**
	 * Select which fields aren't escaped during insert/update ops
	 *
	 * @access	public
	 * @var 	array 		Do not escape these fields
	 */
	public $no_escape_fields  = array();
	
	/**
	 * Classname of query cache file
	 *
	 * @access	public
	 * @var 	string		Class name of query cache file
	 */
	public $sql_queries_name  = 'sql_queries';
	
	/**
	 * SQL server version (human)
	 *
	 * @access	public
	 * @var 	string		Human-readable SQL server version
	 */
	public $sql_version			= "";
	
	/**
	 * SQL server version (long)
	 *
	 * @access	public
	 * @var 	string		Raw SQL server version
	 */
	public $true_version		= 0;
	
	/*
	 * Allow sub selects for this query
	 *
	 * @access	public
	 * @var 	boolean		Allow subselects for this query
	 */
	public $allow_sub_select = false;
	
	/**
	 * Use (root path)/cache/ipsDriverError.php template
	 *
	 * @access	public
	 * @var 	boolean		Use template instead of printing directly
	 */
	public $use_template = true;
	
	/**
	 * Driver Class Name
	 * 
	 * @access	protected
	 * @var		string
	 */
	protected $usingClass = '';

	/**
	 * db_driver constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		//--------------------------------------------
		// Set up any required connect vars here:
		//--------------------------------------------
		
     	$this->connect_vars = array();
	}
    
	/**
	 * Global connect class
	 *
	 * @access	public
	 * @return	void
	 */
	public function connect()
	{
		$this->usingClass   = strtolower( get_class( $this ) );
		$this->writeDebugLog( '{start}', '', '' );
	}
	
	/**
	 * Returns the currently formed SQL query
	 *
	 * @access	public
	 * @return	string
	 */
	public function fetchSqlString()
	{
		return $this->cur_query;
	}
	
    /**
	 * Takes array of set commands and generates a SQL formatted query
	 *
	 * @access	public
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	void
	 */
    public function build( $data )
    {
		/* Inline build from cache files? Not all drviers may have a cache file.. */
		if ( $this->usingClass != 'db_driver_mysql' AND ( $data['queryKey'] AND $data['queryLocation'] AND $data['queryClass'] ) )
		{ 
			if ( self::loadCacheFile( $data['queryLocation'], $data['queryClass'] ) === TRUE )
			{
				self::buildFromCache( $data['queryKey'], $data['queryVars'], $data['queryClass'] );
				return;
			}
		}
		
    	if ( isset($data['select']) && $data['select'] )
    	{
    		$this->_buildSelect( $data['select'], $data['from'], isset($data['where']) ? $data['where'] : '', isset( $data['add_join'] ) ? $data['add_join'] : array(), isset( $data['calcRows'] ) ? $data['calcRows'] : '' );
    	}
    	
    	if ( isset($data['update']) && $data['update'] )
    	{
    		$this->update( $data['update'], $data['set'], isset($data['where']) ? $data['where'] : '', false, true );
    		return;
    	}
    	
    	if ( isset($data['delete']) && $data['delete'] )
    	{
    		$this->delete( $data['delete'], $data['where'], $data['order'], $data['limit'], false );
    		return;
    	}
    	
    	if ( isset($data['group']) && $data['group'] )
    	{
    		$this->_buildGroupBy( $data['group'] );
    	}
    	
    	if ( isset($data['having']) && $data['having'] )
    	{
    		$this->_buildHaving( $data['having'] );
    	} 	
    	
    	if ( isset($data['order']) && $data['order'] )
    	{
    		$this->_buildOrderBy( $data['order'] );
    	}
    	
		if ( isset( $data['calcRows'] ) AND $data['calcRows'] === TRUE )
		{
			$this->_buildCalcRows();
		}
		
    	if ( isset($data['limit']) && is_array( $data['limit'] ) )
    	{
    		$this->_buildLimit( $data['limit'][0], $data['limit'][1] );
    	}
    }
    
    /**
	 * Build a query based on template from cache file
	 *
	 * @access	public
	 * @param	string		Name of query file method to use
	 * @param	array		Optional arguments to be parsed inside query function
	 * @param	string		Optional class name
	 */
    public function buildFromCache( $method, $args=array(), $class='sql_queries' )
    {
    	$instance = null;
	
    	if ( $class == 'sql_queries' and method_exists( $this->sql, $method ) )
		{
    		$instance = $this->sql;
		}
		else if( $class != 'sql_queries' AND method_exists( $this->loaded_classes[ $class ], $method ) )
		{
    		$instance = $this->loaded_classes[ $class ];
		}

		if ( $class == 'sql_queries' and !method_exists( $this->sql, $method ) )
		{
			if ( is_array( $this->loaded_classes ) )
			{
				foreach ( $this->loaded_classes as $class_name => $class_instance )
				{
					if ( method_exists( $this->loaded_classes[ $class_name ], $method ) )
					{
						$instance = $this->loaded_classes[ $class_name ];
						continue;
					}
				}
			}
		}

    	if( $instance )
    	{
    		$this->cur_query .= $instance->$method( $args );
    	}
    }
    
    /**
	 * Executes stored SQL query
	 *
	 * @access	public
	 * @return	resource	Query ID
	 */
    public function execute()
    {
    	if ( $this->cur_query != "" )
    	{
    		$res = $this->query( $this->cur_query );
    	}
    	
    	$this->cur_query   	= "";
    	$this->is_shutdown 	= false;

    	return $res;
    }
    
    /**
	 * Stores a query for shutdown execution
	 *
	 * @access	public
	 * @return	mixed		Query ID or void
	 */
    public function executeOnShutdown()
    {
    	if ( ! $this->obj['use_shutdown'] )
    	{
    		$this->is_shutdown 		= true;
    		return $this->execute();
    	}
    	else
    	{
    		$this->obj['shutdown_queries'][] = $this->cur_query;
    		$this->cur_query = "";
    	}
    }
    
    /**
	 * Generates and executes SQL query, and returns the first result
	 *
	 * @access	public
	 * @param	array		Set commands (select, from, where, order, limit, etc)
	 * @return	array		First result set
	 */
    public function buildAndFetch( $data )
    {
    	$this->build( $data );

    	$res = $this->execute();
    	
    	if ( isset($data['select']) AND $data['select'] )
    	{
    		return $this->fetch( $res );
    	}
    }
    
    /**
	 * Determine if query is shutdown and run it
	 *
	 * @access	protected
	 * @param	string 		Query
	 * @param	boolean 	[Optional] Run on shutdown
	 * @return	resource	Query id
	 */
	protected function _determineShutdownAndRun( $query, $shutdown=false )
	{
    	//-----------------------------------------
    	// Shut down query?
    	//-----------------------------------------
    	
    	$current							= $this->no_prefix_convert;
    	$this->no_prefix_convert 			= true;
    	
    	if ( $shutdown )
    	{
    		if ( ! $this->obj['use_shutdown'] )
			{
				$current_shutdown			= $this->is_shutdown;
				$this->is_shutdown 			= true;
				$return 					= $this->query( $query );
				$this->no_prefix_convert 	= $current;
				$this->is_shutdown 			= $current_shutdown;
				return $return;
			}
			else
			{
				$this->obj['shutdown_queries'][] = $query;
				$this->no_prefix_convert 	= $current;
				$this->cur_query 			= "";
			}
    	}
    	else
    	{
    		$return 					= $this->query( $query );
    		$this->no_prefix_convert 	= $current;
    		return $return;
    	}
	}

    /**
	 * Test to see whether a field exists in a table
	 *
	 * @access	public
	 * @param	string		Field name
	 * @param	string		Table name
	 * @return	boolean		Field exists or not
	 */
	public function checkForField( $field, $table )
	{
		$current			= $this->return_die;
		$this->return_die 	= true;
		$this->error      	= "";
		
		$this->build( array( 'select' => "COUNT($field) as count", 'from' => $table ) );
		$this->execute();
		
		$return = true;
		
		if ( $this->failed )
		{
			$return = false;
		}
		
		$this->error		= "";
		$this->return_die	= $current;
		$this->error_no   	= 0;
		$this->failed     	= false;
		
		return $return;
	}

    /**
	 * Set debug mode flag
	 *
	 * @access	public
	 * @param	boolean		[Optional] Set debug mode on/off
	 * @return	void
	 */
	public function setDebugMode( $enable=false )
	{
    	$this->obj['debug'] = intval($enable);
    
    	//-----------------------------------------
     	// If debug, no shutdown....
     	//-----------------------------------------
     	
     	if ( $this->obj['debug'] )
     	{
     		$this->obj['use_shutdown'] = 0;
     	}
	}
	
    /**
	 * Returns current number queries run
	 *
	 * @access	public
	 * @return	integer		Number of queries run
	 */
	public function getQueryCount()
	{
		return $this->query_count;
	}
    
	/**
	 * Flushes the currently queued query
	 *
	 * @access	public
	 * @return	void
	 */
	public function flushQuery()
	{
		$this->cur_query = "";
	}
	
    /**
	 * Set SQL Prefix
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _setPrefix()
	{
		/*if ( ! defined( 'IPSDB::driver()' ) )
     	{
     		$this->obj['sql_tbl_prefix'] = isset($this->obj['sql_tbl_prefix']) ? $this->obj['sql_tbl_prefix'] : 'ibf_';
     		
     		define( 'IPSDB::driver()', $this->obj['sql_tbl_prefix'] );
     	}*/
	}
	
	/**
	 * Has loaded cache file
	 *
	 * @access	public
	 * @param	string 		File name
	 * @param	string 		Classname of file
	 * @return	void
	 */
	public function hasLoadedCacheFile( $classname )
	{
		if ( isset( $this->loaded_classes[ $classname ] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
    /**
	 * Load extra SQL query file
	 *
	 * @access	public
	 * @param	string 		File name
	 * @param	string 		Classname of file
	 * @param	boolean		Ignore missing files, FALSE $this->error is set, TRUE, nothing happens.
	 * @return	boolean		File exists and was loaded TRUE, File does not exist or class does not exist within file FALSE
	 */
	public function loadCacheFile( $filepath, $classname, $ignoreMissing=FALSE )
	{
		/* Tried to load this already? */
		if ( ! isset( $this->_triedToLoadCacheFiles[ $classname ] ) )
		{
			/* Try and load it */
	    	if ( ! file_exists( $filepath ) AND $ignoreMissing === FALSE )
	    	{
	    		$this->error	= "Cannot locate {$filepath} - exiting!";

				$this->_triedToLoadCacheFiles[ $classname ] = FALSE;
	    	}
	    	else if ( $this->hasLoadedCacheFile( $classname ) !== TRUE )
	    	{
	    		require_once( $filepath );
    		
	    		if( class_exists( $classname ) )
	    		{
	    			$this->loaded_classes[ $classname ] = new $classname( $this );
				
					$this->_triedToLoadCacheFiles[ $classname ] = TRUE;
	    		}
	    	}
		}

		return $this->_triedToLoadCacheFiles[ $classname ];
	}
	
    /**
	 * Load Query cache file
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _loadCacheFile()
	{
		if ( $this->obj['query_cache_file'] )
     	{
     		require_once( $this->obj['query_cache_file'] );
     	
			$sql_queries_name = $this->sql_queries_name ? $this->sql_queries_name : 'sql_queries';

     		$this->sql = new $sql_queries_name( $this );
     	}
	}

	/**
	 * Set fields that shouldn't be escaped
	 *
	 * @access	public
	 * @param	array 		SQL table fields
	 * @return	void
	 */
	public function preventAddSlashes( $fields=array() )
	{
		$this->no_escape_fields = $fields;
	}
	
    /**
	 * Compiles SQL fields for insertion
	 *
	 * @access	public
	 * @param	array		Array of field => value pairs
	 * @return	array		FIELD_NAMES (string) FIELD_VALUES (string)
	 */
	public function compileInsertString( $data )
    {
    	$field_names	= "";
		$field_values	= "";

		foreach( $data as $k => $v )
		{
			$add_slashes = 1;
			
			if ( $this->manual_addslashes )
			{
				$add_slashes = 0;
			}
			
			if ( isset($this->no_escape_fields[ $k ]) AND $this->no_escape_fields[ $k ] )
			{
				$add_slashes = 0;
			}
			
			if ( $add_slashes )
			{
				$v = $this->addSlashes( $v );
			}
			
			$field_names  .= "$k,";
			
			//-----------------------------------------
			// Forcing data type?
			//-----------------------------------------
			
			if ( isset($this->force_data_type[ $k ]) AND $this->force_data_type[ $k ] )
			{
				if ( $this->force_data_type[ $k ] == 'string' )
				{
					$field_values .= "'$v',";
				}
				else if ( $this->force_data_type[ $k ] == 'int' )
				{
					$field_values .= intval($v).",";
				}
				else if ( $this->force_data_type[ $k ] == 'float' )
				{
					$field_values .= floatval($v).",";
				}
				if ( $this->force_data_type[ $k ] == 'null' )
				{
					$field_values .= "NULL,";
				}
			}
			
			//-----------------------------------------
			// No? best guess it is then..
			//-----------------------------------------
			
			else
			{
				if ( is_numeric( $v ) and intval($v) == $v )
				{
					$field_values .= $v.",";
				}
				else
				{
					$field_values .= "'$v',";
				}
			}
		}
		
		$field_names  = rtrim( $field_names, ","  );
		$field_values = rtrim( $field_values, "," );
		
		return array( 'FIELD_NAMES'  => $field_names,
					  'FIELD_VALUES' => $field_values,
					);
	}
	
    /**
	 * Compiles SQL fields for update query
	 *
	 * @access	public
	 * @param	array		Array of field => value pairs
	 * @return	string		SET .... update string
	 */
	public function compileUpdateString( $data )
	{
		$return_string = "";
		
		foreach( $data as $k => $v )
		{
			//-----------------------------------------
			// Adding slashes?
			//-----------------------------------------
			
			$add_slashes = 1;
			
			if ( $this->manual_addslashes )
			{
				$add_slashes = 0;
			}
			
			if ( isset($this->no_escape_fields[ $k ]) && $this->no_escape_fields[ $k ] )
			{
				$add_slashes = 0;
			}
			
			if ( $add_slashes )
			{
				$v = $this->addSlashes( $v );
			}
			
			//-----------------------------------------
			// Forcing data type?
			//-----------------------------------------
			
			if ( isset($this->force_data_type[ $k ]) && $this->force_data_type[ $k ] )
			{
				if ( $this->force_data_type[ $k ] == 'string' )
				{
					$return_string .= $k . "='".$v."',";
				}
				else if ( $this->force_data_type[ $k ] == 'int' )
				{
					if ( strstr( $v, 'plus:' ) )
					{
						$return_string .= $k . "=" . $k . '+' . intval( str_replace( 'plus:', '', $v ) ).",";
					}
					else if ( strstr( $v, 'minus:' ) )
					{
						$return_string .= $k . "=" . $k . '-' . intval( str_replace( 'minus:', '', $v ) ).",";
					}
					else
					{
						$return_string .= $k . "=".intval($v).",";
					}
				}
				else if ( $this->force_data_type[ $k ] == 'float' )
				{
					$return_string .= $k . "=".floatval($v).",";
				}
				else if ( $this->force_data_type[ $k ] == 'null' )
				{
					$return_string .= $k . "=NULL,";
				}
			}
			
			//-----------------------------------------
			// No? best guess it is then..
			//-----------------------------------------
			
			else
			{
				if ( is_numeric( $v ) and intval($v) == $v )
				{
					$return_string .= $k . "=".$v.",";
				}
				else
				{
					$return_string .= $k . "='".$v."',";
				}
			}
		}
		
		$return_string = rtrim( $return_string, "," );
		
		return $return_string;
	}
	
    /**
	 * Remove quotes from a DB query
	 *
	 * @access	protected
	 * @param	string		Raw text
	 * @return	string		Text with quotes removed
	 */
	protected function _removeAllQuotes( $t )
	{
		//-----------------------------------------
		// Remove quotes
		//-----------------------------------------
		
		$t = preg_replace( "#\\\{1,}[\"']#s", "", $t );
		$t = preg_replace( "#'[^']*'#s"    , "", $t );
		$t = preg_replace( "#\"[^\"]*\"#s" , "", $t );
		$t = preg_replace( "#\"\"#s"        , "", $t );
		$t = preg_replace( "#''#s"          , "", $t );

		return $t;
	}
	
    /**
	 * Build order by clause
	 *
	 * @access	protected
	 * @param	string		Order by clause
	 * @return	void
	 */
	abstract protected function _buildOrderBy( $order );

    /**
	 * Build having clause
	 *
	 * @access	protected
	 * @param	string		Having clause
	 * @return	void
	 */
	abstract protected function _buildHaving( $having_clause );
	
    /**
	 * Build group by clause
	 *
	 * @access	protected
	 * @param	string		Group by clause
	 * @return	void
	 */
	abstract protected function _buildGroupBy( $group );

    /**
	 * Build limit clause
	 *
	 * @access	protected
	 * @param	integer		Start offset
	 * @param	integer		[Optional] Number of records
	 * @return	void
	 */
	abstract protected function _buildLimit( $offset, $limit=0 );
	
    /**
	 * Build select statement
	 *
	 * @access	protected
	 * @param	string		Columns to retrieve
	 * @param	string		Table name
	 * @param	string		[Optional] Where clause
	 * @param	array 		[Optional] Joined table data
	 * @return	void
	 */
	abstract protected function _buildSelect( $get, $table, $where, $add_join=array(), $calcRows=FALSE );
	
	/**
	 * Generates calc rows in the query if supported / runs count(*) if not
	 *
	 * @return	boolean
	 * @return	void		Sets $this->_calcRows
	 */
	abstract protected function _buildCalcRows();

    /**
	 * Prints SQL error message
	 *
	 * @access	public
	 * @param	string		Additional error message
	 * @return	mixed		Output to screen, or return void
	 */
	public function throwFatalError( $the_error='' )
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

		$this->error	= $this->_getErrorString();
		$this->error_no	= $this->_getErrorNumber();

    	if ( $this->return_die == true )
    	{
			$this->error  = ( $this->error == "" ? $the_error : $this->error );
    		$this->failed = true;
    		return;
    	}
     	else if ( $this->obj['use_error_log'] AND $this->obj['error_log'] )
		{
			$_debug   = debug_backtrace(FALSE);
			$_dString = '';

			if ( is_array( $_debug ) and count( $_debug ) )
			{
				foreach( $_debug as $idx => $data )
				{
					/* Remove non-essential items */
					if ( $data['class'] == 'dbMain' OR $data['class'] == 'ips_DBRegistry' OR $data['class'] == 'ipsRegistry' OR $data['class'] == 'ipsController' OR $data['class'] == 'ipsCommand' )
					{
						continue;
					}
					
					$_dbString[ $idx ] = array( 'file'     => $data['file'],
												'line'     => $data['line'],
												'function' => $data['function'],
												'class'    => $data['class'] );
				}
			}
			
			$_error_string  = "\n===================================================";
			$_error_string .= "\n Date: ". date( 'r' );
			$_error_string .= "\n Error Number: " . $this->error_no;
			$_error_string .= "\n Error: " . $this->error;
			$_error_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
			$_error_string .= "\n Page: " . $_SERVER['REQUEST_URI'];
			$_error_string .= "\n Debug: " . var_export( $_dbString, TRUE );
			$_error_string .= "\n ".$the_error;
			
			if ( $FH = @fopen( $this->obj['error_log'], 'a' ) )
			{
				@fwrite( $FH, $_error_string );
				@fclose( $FH );
			}
			
			if( $this->use_template )
			{
				require_once( DOC_IPS_ROOT_PATH . 'cache/skin_cache/ipsDriverError.php' );
				$template = new ipsDriverErrorTemplate();
				print $template->showError();
			}
			else
			{
				print "<html><head><title>IPS Driver Error</title>
						<style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
			    		   <blockquote><h1>IPS Driver Error</h1><b>There appears to be an error with the database.</b><br>
			    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>
					  </body></html>";
			}
		}
		else
		{
    		$the_error .= "\n\nSQL error: ".$this->error."\n";
	    	$the_error .= "SQL error code: ".$this->error_no."\n";
	    	$the_error .= "Date: ".date("l dS \o\f F Y h:i:s A");
    	
			if( $this->use_template )
			{
				require_once( DOC_IPS_ROOT_PATH . 'cache/skin_cache/ipsDriverError.php' );
				$template = new ipsDriverErrorTemplate();
				print $template->showError( true, $the_error );
			}
			else
			{
		    	print "<html><head><title>IPS Driver Error</title>
		    		   <style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
		    		   <blockquote><h1>IPS Driver Error</h1><b>There appears to be an error with the database.</b><br>
		    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>.
		    		   <br><br><b>Error Returned</b><br>
		    		   <form name='mysql'><textarea rows=\"15\" cols=\"60\">".htmlspecialchars($the_error)."</textarea></form><br>We apologise for any inconvenience</blockquote></body></html>";
	    	}
		}
		
		//-----------------------------------------
		// Need to clear this for shutdown queries
		//-----------------------------------------
		
		$this->cur_query	= '';
		
        exit();
    }
    
    /**
	 * Logs SQL error message to log file
	 *
	 * @access	public
	 * @param	string		SQL Query
	 * @param	string		Data to log (i.e. error message)
	 * @param	integer		Timestamp for log
	 * @return	void
	 */
	public function writeDebugLog( $query, $data, $endtime )
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		if ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] )
		{
			if ( $query == '{start}' )
			{
				$_string = "\n\n\n\n\n==============================================================================";
				$_string .= "\n=========================      START       ===================================";
				$_string .= "\n========================= " . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . " ===================================";
				$_string .= "\n==============================================================================";
			}
			else if ( $query == '{end}' )
			{
				$_string  = "\n==============================================================================";
				$_string .= "\n=========================        END       ===================================";
				$_string .= "\n========================= " . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . " ===================================";
				$_string .= "\n==============================================================================";
			}
			else
			{
				$_string  = "\n==============================================================================";
				$_string .= "\n Date: ". date( 'r' );
				$_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
				$_string .= "\n Time Taken: ".$endtime;
				$_string .= "\n ".$query;
				$_string .= "\n==============================================================================";
				$_string .= "\n".$data;
			}
		
			if ( $FH = @fopen( $this->obj['debug_log'], 'a' ) )
			{
				@fwrite( $FH, $_string );
				@fclose( $FH );
			}
		}
	}
	
	/**
	 * Return an object handle for a loaded class
	 *
	 * @access	public
	 * @param	string 		Class to return
	 * @return	object		Hopefully...
	 */
	public function fetchLoadedClass( $class )
	{
		return ( is_object( $this->loaded_classes[ $class ] ) ) ? $this->loaded_classes[ $class ] : NULL;
	}
	
    /**
	 * Get SQL error number
	 *
	 * @access	protected
	 * @return	mixed		Error number/code
	 */
	abstract protected function _getErrorNumber();
	
    /**
	 * Get SQL error message
	 *
	 * @access	protected
	 * @return	string		Error message
	 */
	abstract protected function _getErrorString();
		
	/**
	 * db_driver destructor: Runs shutdown queries and closes connection
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		$this->return_die = true;
		
		if ( count( $this->obj['shutdown_queries'] ) )
		{
			foreach( $this->obj['shutdown_queries'] as $q )
			{
				$this->query( $q );
			}
		}
		
		$this->writeDebugLog( '{end}', '', '' );

		$this->obj['shutdown_queries'] = array();
		
		$this->disconnect();
	}
}