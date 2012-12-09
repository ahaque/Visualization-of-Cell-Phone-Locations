<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * MySQL Database Driver :: Further loads mysql or mysqli client appropriately
 * Last Updated: $Date: 2009-08-19 15:00:49 -0400 (Wed, 19 Aug 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Monday 28th February 2005 16:46
 * @version		$Revision: 318 $
 */

/**
 * 1 = Replace into
 * 2 = Insert into...on duplicate key update
 */
define( 'REPLACE_TYPE', 2 );

/**
 * Handle base class definitions
 */
if ( ! class_exists( 'dbMain' ) )
{
	require_once( dirname( __FILE__ ) . '/classDb.php' );
}

/**
 * Allow < 4.3.0 PHP client access
 */
if ( ! defined('IPS_MAIN_DB_CLASS_LEGACY') )
{
	define('IPS_MAIN_DB_CLASS_LEGACY', ( PHP_VERSION < '4.3.0' ) ? TRUE : FALSE );
}

abstract class db_main_mysql extends dbMain
{
	/**
	 * Cached field names in table
	 *
	 * @access	protected
	 * @var 		array 		Field names cached
	 */
	protected $cached_fields		= array();

	/**
	 * Cached table names in database
	 *
	 * @access	protected
	 * @var 		array 		Table names cached
	 */
	protected $cached_tables		= array();

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
	public function delete( $table, $where='', $orderBy='', $limit=array(), $shutdown=false )
	{
	    if ( ! $where )
	    {
		    $this->cur_query = "TRUNCATE TABLE " . $this->obj['sql_tbl_prefix'] . $table;
	    }
	    else
	    {
    		$this->cur_query = "DELETE FROM " . $this->obj['sql_tbl_prefix'] . $table . " WHERE " . $where;
		}

		if ( $where AND $orderBy )
		{
			$this->_buildOrderBy( $orderBy );
		}
		
		if ( $where AND $limit AND is_array( $limit ) )
		{
			$this->_buildLimit( $limit[0], $limit[1] );
		}

		$result	= $this->_determineShutdownAndRun( $this->cur_query, $shutdown );
		
		$this->cur_query	= '';
		
		return $result;
	}

    /**
	 * Update data in a table
	 *
	 * @access	public
	 * @param	string 		Table name
	 * @param	mixed 		Array of field => values, or pre-formatted "SET" clause
	 * @param	string 		[Optional] Where clause
	 * @param	boolean		[Optional] Run on shutdown
	 * @param	boolean		[Optional] $set is already pre-formatted
	 * @return	resource	Query id
	 */
	public function update( $table, $set, $where='', $shutdown=false, $preformatted=false )
    {
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------

    	$dba   = $preformatted ? $set : $this->compileUpdateString( $set );

    	$query = "UPDATE " . $this->obj['sql_tbl_prefix'] . $table . " SET " . $dba;

    	if ( $where )
    	{
    		$query .= " WHERE " . $where;
    	}

    	return $this->_determineShutdownAndRun( $query, $shutdown );
    }

    /**
	 * Insert data into a table
	 *
	 * @access	public
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	boolean		Run on shutdown
	 * @return	resource	Query id
	 */
	public function insert( $table, $set, $shutdown=false )
	{
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------

    	$dba   = $this->compileInsertString( $set );

		$query = "INSERT INTO " . $this->obj['sql_tbl_prefix'] . $table . " ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})";

		return $this->_determineShutdownAndRun( $query, $shutdown );
    }

    /**
	 * Insert record into table if not present, otherwise update existing record
	 *
	 * @access	public
	 * @param	string 		Table name
	 * @param	array 		Array of field => values
	 * @param	array 		Array of fields to check
	 * @param	boolean		[Optional] Run on shutdown
	 * @return	resource	Query id
	 */
	public function replace( $table, $set, $where, $shutdown=false )
	{
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------

    	$dba	= $this->compileInsertString( $set );

		if( REPLACE_TYPE == 1 OR $this->getSqlVersion() < 41000 )
		{
			$query	= "REPLACE INTO " . $this->obj['sql_tbl_prefix'] . $table . " ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})";
		}
		else
		{
			//$dbb	= $this->compileUpdateString( $set );
			$dbb	= array();
			
			foreach( $set as $k => $v )
			{
				$dbb[]	= "{$k}=VALUES({$k})";
			}
			
			$dbb	= implode( ',', $dbb );

			$query	= "INSERT INTO " . $this->obj['sql_tbl_prefix'] . $table . " ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']}) ON DUPLICATE KEY UPDATE " . $dbb;
		}
		
		IPSDebug::addLogMessage( $query, 'replaceintolog' );
		
    	return $this->_determineShutdownAndRun( $query, $shutdown );
    }

    /**
	 * Kill a thread
	 *
	 * @param	integer 	Thread ID
	 * @return	resource	Query id
	 */
	public function kill( $threadId )
	{
	    return $this->query( "KILL {$threadId}" );
	}

    /**
	 * Subqueries supported by driver?
	 *
	 * @access	public
	 * @return	boolean		Subqueries supported
	 */
	public function checkSubquerySupport()
	{
		$this->getSqlVersion();

		if ( $this->sql_version >= 41000 )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
	 * Fulltext searching supported by driver?
	 *
	 * @access	public
	 * @return	boolean		Fulltext supported
	 */
	public function checkFulltextSupport()
	{
		$this->getSqlVersion();

		if ( $this->sql_version >= 32323 AND strtolower($this->connect_vars['mysql_tbl_type']) == 'myisam' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
	 * Boolean fulltext searching supported by driver?
	 *
	 * @access	public
	 * @return	boolean		Boolean fulltext supported
	 */
	public function checkBooleanFulltextSupport()
	{
		$this->getSqlVersion();

		if ( $this->sql_version >= 40010 AND strtolower($this->connect_vars['mysql_tbl_type']) == 'myisam' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
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
	    if( isset($this->cached_fields[ $table ]) )
	    {
		    if( in_array( $field, $this->cached_fields[ $table ] ) )
		    {
			    return true;
		    }
		    else
		    {
			    return false;
		    }
	    }

	    $current			= $this->return_die;
		$this->return_die 	= true;
		$this->error      	= "";
		$return 		  	= false;

		$q = $this->query( "SHOW fields FROM " . $this->obj['sql_tbl_prefix'] . $table );

		if( $q AND $this->getTotalRows($q) )
		{
			while( $check = $this->fetch($q) )
			{
				$this->cached_fields[ $table ][] = $check['Field'];
			}
		}

		if ( !$this->failed AND in_array( $field, $this->cached_fields[ $table ] ) )
		{
			$return = true;
		}

		$this->error		= "";
		$this->return_die	= $current;
		$this->error_no   	= 0;
		$this->failed     	= false;

		return $return;
	}

    /**
	 * Test to see whether a table exists
	 *
	 * @access	public
	 * @param	string		Table name
	 * @return	boolean		Table exists or not
	 */
	public function checkForTable( $table )
	{
	    $table_names = $this->getTableNames();

	    $return = false;

	    if ( in_array( strtolower( trim( $this->obj['sql_tbl_prefix'] . $table ) ), array_map( 'strtolower', $table_names ) ) )
	    {
		    $return = true;
	    }

	    unset($table_names);

	    return $return;
	}

    /**
	 * Drop database table
	 *
	 * @access	public
	 * @param	string		Table to drop
	 * @return	resource	Query id
	 */
	public function dropTable( $table )
	{
		return $this->query( "DROP TABLE IF EXISTS " . $this->obj['sql_tbl_prefix'] . $table );
	}

    /**
	 * Drop field in database table
	 *
	 * @access	public
	 * @param	string		Table name
	 * @param	string		Field to drop
	 * @return	resource	Query id
	 */
	public function dropField( $table, $field )
	{
		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " DROP " . $field );
	}

    /**
	 * Add field to table in database
	 *
	 * @access	public
	 * @param	string		Table name
	 * @param	string		Field to add
	 * @param	string		Field type
	 * @param	string		[Optional] Default value
	 * @return	resource	Query id
	 */
	public function addField( $table, $field, $type, $default='' )
	{
		$default = $default ? "DEFAULT {$default}" : 'NULL';

		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " ADD {$field} {$type} {$default}" );
	}

    /**
	 * Change field in database table
	 *
	 * @access	public
	 * @param	string		Table name
	 * @param	string		Existing field name
	 * @param	string		New field name
	 * @param	string		Field type
	 * @param	string		[Optional] Default value
	 * @return	resource	Query id
	 */
	public function changeField( $table, $old_field, $new_field, $type='', $default='' )
	{
		$default = $default ? "DEFAULT {$default}" : 'NULL';

		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " CHANGE {$old_field} {$new_field} {$type} {$default}" );
	}

    /**
	 * Optimize database table
	 *
	 * @access	public
	 * @param	string		Table name
	 * @return	resource	Query id
	 */
	public function optimize( $table )
	{
		return $this->query( "OPTIMIZE TABLE " . $this->obj['sql_tbl_prefix'] . $table );
	}

    /**
	 * Add fulltext index to database column
	 *
	 * @access	public
	 * @param	string		Table name
	 * @param	string		Field name
	 * @return	resource	Query id
	 */
	public function addFulltextIndex( $table, $field )
	{
		return $this->query( "ALTER TABLE " . $this->obj['sql_tbl_prefix'] . $table . " ADD FULLTEXT({$field})" );
	}

    /**
	 * Get table schematic
	 *
	 * @access	public
	 * @param	string		Table name
	 * @return	array		SQL schematic array
	 */
	public function getTableSchematic( $table )
	{
		$current			= $this->return_die;
		$this->return_die 	= true;

		$qid = $this->query( "SHOW CREATE TABLE " . $this->obj['sql_tbl_prefix'] . $table );

		$this->return_die 	= $current;

		if( $qid )
		{
			return $this->fetch($qid);
		}
		else
		{
			return array();
		}
	}

    /**
	 * Determine if table already has a fulltext index
	 *
	 * @access	public
	 * @param	string		Table name
	 * @return	boolean		Fulltext index exists
	 */
	public function getFulltextStatus( $table )
	{
		$result = $this->getTableSchematic( $table );

		if ( preg_match( "/FULLTEXT KEY/i", $result['Create Table'] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
	 * Un-escape strings escaped for DB
	 *
	 * @access	public
	 * @param	string		Text to un-escape
	 * @return	string		Un-escaped text
	 */
	public function removeSlashes( $t )
	{
		return $t;
	}

    /**
	 * Build order by clause
	 *
	 * @access	protected
	 * @param	string		Order by clause
	 * @return	void
	 */
	protected function _buildOrderBy( $order )
	{
    	if ( $order )
    	{
    		$this->cur_query .= ' ORDER BY ' . $order;
    	}
	}

    /**
	 * Build group by clause
	 *
	 * @access	protected
	 * @param	string		Having clause
	 * @return	void
	 */
	protected function _buildHaving( $having_clause )
	{
    	if ( $having_clause )
    	{
    		$this->cur_query .= ' HAVING ' . $having_clause;
    	}
    }

    /**
	 * Build having clause
	 *
	 * @access	protected
	 * @param	string		Group by clause
	 * @return	void
	 */
	protected function _buildGroupBy( $group )
	{
    	if ( $group )
    	{
    		$this->cur_query .= ' GROUP BY ' . $group;
    	}
    }

    /**
	 * Build limit clause
	 *
	 * @access	protected
	 * @param	integer		Start offset
	 * @param	integer		[Optional] Number of records
	 * @return	void
	 */
	protected function _buildLimit( $offset, $limit=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$offset = intval( $offset );
		$offset = ( $offset < 0 ) ? 0 : $offset;
		$limit  = intval( $limit );

    	if ( $limit )
    	{
    		$this->cur_query .= ' LIMIT ' . $offset . ',' . $limit;
    	}
    	else
    	{
    		$this->cur_query .= ' LIMIT ' . $offset;
    	}
	}

    /**
	 * Build concat string
	 *
	 * @access	public
	 * @param	array		Array of data to concat
	 * @return	string		SQL-formatted concat string
	 */
	public function buildConcat( $data )
	{
		$return_string = '';

		if( is_array($data) AND count($data) )
		{
			$concat = array();

			foreach( $data as $databit )
			{
				$concat[] = $databit[1] == 'string' ? "'" . $databit[0] . "'" : $databit[0];
			}

			if( count($concat) )
			{
				$return_string = "CONCAT(" . implode( ',', $concat ) . ")";
			}
		}

		return $return_string;
	}
	
    /**
	 * Build CAST string
	 *
	 * @param	string		Value to CAST
	 * @return	string		Column type to cast as (only UNSIGNED supported at this time!!)
	 */
	public function buildCast( $data, $columnType )
	{
		return "CAST( {$data} AS {$columnType} )";
	}

    /**
	 * Build between statement
	 *
	 * @access	public
	 * @param	string		Column
	 * @param	integer		Value 1
	 * @param	integer		Value 2
	 * @return	string		SQL-formatted between statement
	 */
	public function buildBetween( $column, $value1, $value2 )
	{
		return "{$column} BETWEEN {$value1} AND {$value2}";
	}

    /**
	 * Build regexp string (ONLY supports a regexp equivalent of "or field like value")
	 *
	 * @access	public
	 * @param	string		Database column
	 * @param	array		Array of values to allow
	 * @return	string		SQL-formatted concat string
	 */
	public function buildRegexp( $column, $data )
	{
		return "{$column} REGEXP '," . implode( ',|,', $data ) . ",|\\\*'";
	}

	/**
	 * Build LIKE CHAIN string (ONLY supports a regexp equivalent of "or field like value")
	 *
	 * @access	public
	 * @param	string		Database column
	 * @param	array		Array of values to allow
	 * @return	string		SQL-formatted concat string
	 */
	public function buildLikeChain( $column, $data )
	{
		$return = $column . "='*'";

		if ( is_array( $data ) )
		{
			foreach( $data as $id )
			{
				$return .= " OR " . $column . " LIKE '%," . $id . ",%'";
			}
		}

		return $return;
	}

    /**
	 * Build instr string
	 *
	 * @access	public
	 * @param	string		String to look for
	 * @param	string		String to look in
	 * @return	string		SQL-formatted instr string
	 */
	public function buildInstring( $look_for, $look_in )
	{
		if( $look_for AND $look_in )
		{
			return "INSTR('" . $look_for . "', " . $look_in . ")";
		}
		else
		{
			return '';
		}
	}

    /**
	 * Build substr string
	 *
	 * @access	public
	 * @param	string		String of characters/Column
	 * @param	integer		Offset
	 * @param	integer		[Optional] Number of chars
	 * @return	string		SQL-formatted substr string
	 */
	public function buildSubstring( $look_for, $offset, $length=0 )
	{
		$return = '';

		if( $look_for AND $offset )
		{
			$return = "SUBSTR(" . $look_for . ", " . $offset;

			if( $length )
			{
				$return .= ", " . $length;
			}

			$return .= ")";
		}

		return $return;
	}

    /**
	 * Build distinct string
	 *
	 * @access	public
	 * @param	string		Column name
	 * @return	string		SQL-formatted distinct string
	 */
	public function buildDistinct( $column )
	{
		return "DISTINCT(" . $column . ")";
	}

    /**
	 * Build length string
	 *
	 * @access	public
	 * @param	string		Column name
	 * @return	string		SQL-formatted length string
	 */
	public function buildLength( $column )
	{
		return "LENGTH(" . $column . ")";
	}
	
	/**
	 * Build lower string
	 *
	 * @access	public
	 * @param	string		Column name
	 * @return	string		SQL-formatted length string
	 */
	public function buildLower( $column )
	{
		return "LOWER(" . $column . ")";
	}

    /**
	 * Build right string
	 *
	 * @access	public
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	string		SQL-formatted right string
	 */
	public function buildRight( $column, $chars )
	{
		return "RIGHT(" . $column . "," . intval($chars) . ")";
	}

    /**
	 * Build left string
	 *
	 * @access	public
	 * @param	string		Column name
	 * @param	integer		Number of chars
	 * @return	string		SQL-formatted left string
	 */
	public function buildLeft( $column, $chars )
	{
		return "LEFT(" . $column . "," . intval($chars) . ")";
	}

    /**
	 * Build "is null" and "is not null" string
	 *
	 * @access	public
	 * @param	boolean		is null flag
	 * @return	string		[Optional] SQL-formatted "is null" or "is not null" string
	 */
	public function buildIsNull( $is_null=true )
	{
		return $is_null ? " IS NULL " : " IS NOT NULL ";
	}

    /**
	 * Build from_unixtime string
	 *
	 * @access	public
	 * @param	string		Column name
	 * @param	string		[Optional] Format
	 * @return	string		SQL-formatted from_unixtime string
	 */
	public function buildFromUnixtime( $column, $format='' )
	{
		if( $format )
		{
			return "FROM_UNIXTIME(" . $column . ", '{$format}')";
		}
		else
		{
			return "FROM_UNIXTIME(" . $column . ")";
		}
	}

    /**
	 * Build date_format string
	 *
	 * @access	public
	 * @param	string		Date string
	 * @param	string		Format
	 * @return	string		SQL-formatted date_format string
	 */
	public function buildDateFormat( $column, $format )
	{
		return "DATE_FORMAT(" . $column . ", '{$format}')";
	}

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
	public function buildSearchStatement( $column, $keyword, $booleanMode=true, $returnRanking=false, $useFulltext=true )
	{
		if( !$useFulltext )
		{
			return "{$column} LIKE '%{$keyword}%'";
		}
		else
		{
			return "MATCH( {$column} ) AGAINST( '{$keyword}' " . ( $booleanMode === TRUE ? 'IN BOOLEAN MODE' : '' ) . " )" . ( $returnRanking === TRUE ? ' as ranking' : '' );
		}
	}

	/**
	 * Build calc rows
	 * We don't have to do anything for MySQL 4+ as it's handled internally
	 * This is always called before the limit is applied
	 *
	 * @access	public
	 * @return	void		Sets $this->_calcRows
	 */
	protected function _buildCalcRows()
	{
		return "";

		/* For other engines */
		/*if ( $this->cur_query )
		{
			$_query = preg_replace( "#SELECT\s{1,}(.+?)\s{1,}FROM\s{1,}#i", "SELECT count(*) as count FROM ", $this->cur_query );

			$this->query( $_query );
			$count = $this->fetch();

			$this->_calcRows = intval( $count['count'] );
		}*/
	}

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
	protected function _buildSelect( $get, $table, $where, $add_join=array(), $calcRows=FALSE )
	{
		$_calcRows = ( $calcRows === TRUE ) ? 'SQL_CALC_FOUND_ROWS ' : '';

		if( !count($add_join) )
		{
			if( is_array( $table ) )
			{
				$_tables	= array();
				
				foreach( $table as $tbl => $alias )
				{
					$_tables[] = $this->obj['sql_tbl_prefix'] . $tbl . ' ' . $alias;
				}
				
				$table	= implode( ', ', $_tables );
			}
			else
			{
				$table	= $this->obj['sql_tbl_prefix'] . $table;
			}
			
	    	$this->cur_query .= "SELECT {$_calcRows}{$get} FROM " . $table;

	    	if ( $where != "" )
	    	{
	    		$this->cur_query .= " WHERE " . $where;
	    	}

	    	return;
    	}
    	else
		{
	    	//-----------------------------------------
	    	// OK, here we go...
	    	//-----------------------------------------

	    	$select_array   = array();
	    	$from_array     = array();
	    	$joinleft_array = array();
	    	$where_array    = array();
	    	$final_from     = array();

	    	$select_array[] = $get;
	    	$from_array[]   = $table;

	    	if ( $where )
	    	{
	    		$where_array[]  = $where;
	    	}

	    	//-----------------------------------------
	    	// Loop through JOINs and sort info
	    	//-----------------------------------------

	    	if ( is_array( $add_join ) and count( $add_join ) )
	    	{
	    		foreach( $add_join as $join )
	    		{
	    			# Push join's select to stack
	    			if ( isset($join['select']) AND $join['select'] )
	    			{
	    				$select_array[] = $join['select'];
	    			}

	    			if ( $join['type'] == 'inner' )
	    			{
	    				# Join is inline
	    				$from_array[]  = $join['from'];

	    				if ( $join['where'] )
	    				{
	    					$where_array[] = $join['where'];
	    				}
	    			}
	    			else if ( $join['type'] == 'left' OR !$join['type'] )
	    			{
	    				# Join is left or not specified (assume left)
	    				$tmp = " LEFT JOIN ";

	    				foreach( $join['from'] as $tbl => $alias )
						{
							$tmp .= $this->obj['sql_tbl_prefix'].$tbl.' '.$alias;
						}

	    				if ( $join['where'] )
	    				{
	    					$tmp .= " ON ( ".$join['where']." ) ";
	    				}

	    				$joinleft_array[] = $tmp;

	    				unset( $tmp );
	    			}
	    			else
	    			{
	    				# Not using any other type of join
	    			}
	    		}
	    	}

	    	//-----------------------------------------
	    	// Build it..
	    	//-----------------------------------------

	    	foreach( $from_array as $i )
			{
				foreach( $i as $tbl => $alias )
				{
					$final_from[] = $this->obj['sql_tbl_prefix'] . $tbl . ' ' . $alias;
				}
			}

	    	$get   = implode( ","     , $select_array   );
	    	$table = implode( ","     , $final_from     );
	    	$where = implode( " AND " , $where_array    );
	    	$join  = implode( "\n"    , $joinleft_array );

	    	$this->cur_query .= "SELECT {$_calcRows}{$get} FROM {$table}";

	    	if ( $join )
	    	{
	    		$this->cur_query .= " " . $join . " ";
	    	}

	    	if ( $where != "" )
	    	{
	    		$this->cur_query .= " WHERE " . $where;
	    	}
		}
	}

}

if ( extension_loaded('mysqli') AND ! defined( 'FORCE_MYSQL_ONLY' ) )
{
	require( dirname( __FILE__ ) . "/classDbMysqliClient.php" );
}
else
{
	require( dirname( __FILE__ ) . "/classDbMysqlClient.php" );
}

//-----------------------------------------
// Clean up
//-----------------------------------------

unset( $versions );