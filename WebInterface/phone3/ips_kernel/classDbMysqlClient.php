<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * MySQL Database Driver :: MySQL client
 * Last Updated: $Date: 2009-07-16 10:24:26 -0400 (Thu, 16 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Monday 28th February 2005 16:46
 * @version		$Revision: 294 $
 */

class db_driver_mysql extends db_main_mysql implements interfaceDb
{
	/**
	 * constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		if( !defined('MYSQLI_USED') )
		{
			define( 'MYSQLI_USED', 1 );
		}
		
		//--------------------------------------
		// Set up any required connect vars here
		//--------------------------------------
		
     	$this->connect_vars['mysql_tbl_type'] = "";
	}

    /**
	 * Connect to database server
	 *
	 * @access	public
	 * @return	boolean		Connection successful
	 */
	public function connect()
	{
		//-----------------------------------------
     	// Done SQL prefix yet?
     	//-----------------------------------------
     	
     	$this->_setPrefix();
     	
    	//-----------------------------------------
    	// Load query file
    	//-----------------------------------------
    	
    	$this->_loadCacheFile();
     	
     	//-----------------------------------------
     	// Connect
     	//-----------------------------------------
     	
    	if ( $this->obj['persistent'] AND ! IPS_MAIN_DB_CLASS_LEGACY )
    	{
    	    $this->connection_id = @mysql_pconnect( $this->obj['sql_host'] ,
												   $this->obj['sql_user'] ,
												   $this->obj['sql_pass'] ,
												   $this->obj['force_new_connection']
												);
        }
        else
        { 
			if ( IPS_MAIN_DB_CLASS_LEGACY )
        	{
				$this->connection_id = @mysql_connect( $this->obj['sql_host'] ,
													  $this->obj['sql_user'] ,
													  $this->obj['sql_pass']
													);
			}
			else
			{
				$this->connection_id = @mysql_connect( $this->obj['sql_host'] ,
													  $this->obj['sql_user'] ,
													  $this->obj['sql_pass'] ,
													  $this->obj['force_new_connection']
													);
			}
		}
		
		if ( ! $this->connection_id )
		{
			$this->throwFatalError();
			return FALSE;
		}
		
        if ( ! mysql_select_db($this->obj['sql_database'], $this->connection_id) )
        {
        	$this->throwFatalError();
        	return FALSE;
        }
        
     	//-----------------------------------------
     	// Remove sensitive data
     	//-----------------------------------------
     	
		unset( $this->obj['sql_host'] );
		unset( $this->obj['sql_user'] );
		unset( $this->obj['sql_pass'] );
		
     	//-----------------------------------------
     	// If there's a charset set, run it
     	//-----------------------------------------
     	
     	if( $this->obj['sql_charset'] )
     	{
     		$this->query( "SET NAMES '{$this->obj['sql_charset']}'" );
     	}
       
        return TRUE;
    }
    
    /**
	 * Close database connection
	 *
	 * @access	public
	 * @return	boolean		Closed successfully
	 */
	public function disconnect()
	{
    	if ( $this->connection_id )
    	{
        	return @mysql_close( $this->connection_id );
        }
    }

    /**
	 * Execute a direct database query
	 *
	 * @access	public
	 * @param	string		Database query
	 * @param	boolean		[Optional] Do not convert table prefix
	 * @return	resource	Query id
	 */
	public function query( $the_query, $bypass=false )
	{
    	//-----------------------------------------
        // Change the table prefix if needed
        //-----------------------------------------
        
        if ( $this->no_prefix_convert )
        {
        	$bypass = 1;
        }
        
        if ( ! $bypass )
        {
			if ( $this->obj['sql_tbl_prefix'] != "ibf_" and ! $this->prefix_changed )
			{
			   //$the_query = preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->obj['sql_tbl_prefix']."\\1\\2", $the_query);
			}
        }
        
        //-----------------------------------------
        // Debug?
        //-----------------------------------------
        
        if ( $this->obj['debug'] OR ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) )
        {
    		IPSDebug::startTimer();
    	}
    
		//-----------------------------------------
		// Stop sub selects? (UNION)
		//-----------------------------------------
		
		if ( ! IPS_DB_ALLOW_SUB_SELECTS )
		{
			# On the spot allowance?
			if ( ! $this->allow_sub_select )
			{
				$_tmp = strtolower( $this->_removeAllQuotes($the_query) );
				
				if ( preg_match( "#(?:/\*|\*/)#i", $_tmp ) )
				{
					$this->throwFatalError( "You are not allowed to use comments in your SQL query.\nAdd \ipsRegistry::DB()->allow_sub_select=1; before any query construct to allow them\n{$the_query}" );
					return false;
				}
				
				if ( preg_match( "#[^_a-zA-Z]union[^_a-zA-Z]#s", $_tmp ) )
				{
					$this->throwFatalError( "UNION query joins are not allowed.\nAdd \ipsRegistry::DB()->allow_sub_select=1; before any query construct to allow them\n{$the_query}" );
					return false;
				}
				else if ( preg_match_all( "#[^_a-zA-Z](select)[^_a-zA-Z]#s", $_tmp, $matches ) )
				{
					if ( count( $matches ) > 1 )
					{
						$this->throwFatalError( "SUB SELECT query joins are not allowed.\nAdd \ipsRegistry::DB()->allow_sub_select=1; before any query construct to allow them\n{$the_query}" );
						return false;
					}
				}
			}
		}
		
    	//-----------------------------------------
    	// Run the query
    	//-----------------------------------------
    	
		#I had to switch this around... The query goes first, connection id second. Otherwise it just breaks - KF
		#$this->query_id = mysql_query($this->connection_id, $the_query );
		$this->query_id = mysql_query( $the_query, $this->connection_id );

      	//-----------------------------------------
      	// Reset array...
      	//-----------------------------------------
      	
      	$this->force_data_type  = array();
      	$this->allow_sub_select = false;

        if (! $this->query_id )
        {
            $this->throwFatalError("mySQL query error: $the_query");
        }
        
        //-----------------------------------------
        // Debug?
        //-----------------------------------------
        
		if ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] )
		{
			$endtime  = IPSDebug::endTimer();
			
			if ( preg_match( "/^(?:\()?select/i", $the_query ) )
        	{
        		$eid = mysql_query( "EXPLAIN {$the_query}", $this->connection_id );
        		
				while( $array = mysql_fetch_array($eid) )
				{
					$_data .= "\n+------------------------------------------------------------------------------+";
					$_data .= "\n|Table: ". $array['table'];
					$_data .= "\n|Type: ". $array['type'];
					$_data .= "\n|Possible Keys: ". $array['possible_keys'];
					$_data .= "\n|Key: ". $array['key'];
					$_data .= "\n|Key Len: ". $array['key_len'];
					$_data .= "\n|Ref: ". $array['ref'];
					$_data .= "\n|Rows: ". $array['rows'];
					$_data .= "\n|Extra: ". $array['extra'];
					$_data .= "\n+------------------------------------------------------------------------------+";
				}
			
				$this->writeDebugLog( $the_query, $_data, $endtime );
			}
			else
			{
				$this->writeDebugLog( $the_query, $_data, $endtime );
			}
		}
        else if ($this->obj['debug'])
        {
        	$endtime  = IPSDebug::endTimer();
        	
        	$shutdown = $this->is_shutdown ? 'SHUTDOWN QUERY: ' : '';
        	
        	if ( preg_match( "/^(?:\()?select/i", $the_query ) )
        	{
        		$eid = mysql_query( "EXPLAIN {$the_query}", $this->connection_id );
        		
        		$this->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FFE8F3' align='center'>
										   <tr>
										   	 <td colspan='8' style='font-size:14px' bgcolor='#FFC5Cb'><b>{$shutdown}Select Query</b></td>
										   </tr>
										   <tr>
										    <td colspan='8' style='font-family:courier, monaco, arial;font-size:14px;color:black'>$the_query</td>
										   </tr>
										   <tr bgcolor='#FFC5Cb'>
											 <td><b>table</b></td><td><b>type</b></td><td><b>possible_keys</b></td>
											 <td><b>key</b></td><td><b>key_len</b></td><td><b>ref</b></td>
											 <td><b>rows</b></td><td><b>Extra</b></td>
										   </tr>\n";
				while( $array = mysql_fetch_array($eid) )
				{
					$type_col = '#FFFFFF';
					
					if ($array['type'] == 'ref' or $array['type'] == 'eq_ref' or $array['type'] == 'const')
					{
						$type_col = '#D8FFD4';
					}
					else if ($array['type'] == 'ALL')
					{
						$type_col = '#FFEEBA';
					}
					
					$this->debug_html .= "<tr bgcolor='#FFFFFF'>
											 <td>$array[table]&nbsp;</td>
											 <td bgcolor='$type_col'>$array[type]&nbsp;</td>
											 <td>$array[possible_keys]&nbsp;</td>
											 <td>$array[key]&nbsp;</td>
											 <td>$array[key_len]&nbsp;</td>
											 <td>$array[ref]&nbsp;</td>
											 <td>$array[rows]&nbsp;</td>
											 <td>$array[Extra]&nbsp;</td>
										   </tr>\n";
				}
				
				$this->sql_time += $endtime;
				
				if ($endtime > 0.1)
				{
					$endtime = "<span style='color:red'><b>$endtime</b></span>";
				}
				
				$this->debug_html .= "<tr>
										  <td colspan='8' bgcolor='#FFD6DC' style='font-size:14px'><b>MySQL time</b>: $endtime</b></td>
										  </tr>
										  </table>\n<br />\n";
			}
			else
			{
			  $this->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FEFEFE'  align='center'>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>{$shutdown}Non Select Query</b></td>
										 </tr>
										 <tr>
										  <td style='font-family:courier, monaco, arial;font-size:14px'>$the_query</td>
										 </tr>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>MySQL time</b>: $endtime</span></td>
										 </tr>
										</table><br />\n\n";
			}
		}
		
		$this->query_count++;
        
        $this->obj['cached_queries'][] = $the_query;
        
        return $this->query_id;
    }

    /**
	 * Retrieve number of rows affected by last query
	 *
	 * @access	public
	 * @return	integer		Number of rows affected by last query
	 */
	public function getAffectedRows()
	{
		return mysql_affected_rows( $this->connection_id );
	}
	
    /**
	 * Retrieve number of rows in result set
	 *
	 * @access	public
	 * @param	resource	[Optional] Query id
	 * @return	integer		Number of rows in result set
	 */
	public function getTotalRows( $query_id=null )
	{
		if ( ! $query_id )
   		{
    		$query_id = $this->query_id;
    	}

        return mysql_num_rows( $query_id );
    }
	
    /**
	 * Retrieve latest autoincrement insert id
	 *
	 * @access	public
	 * @return	integer		Last autoincrement id assigned
	 */
	public function getInsertId()
	{
		return mysql_insert_id($this->connection_id);
	}
	
    /**
	 * Retrieve the current thread id
	 *
	 * @access	public
	 * @return	integer		Current thread id
	 */
	public function getThreadId()
	{
		return mysql_thread_id($this->connection_id);
	}
	
    /**
	 * Free result set from memory
	 *
	 * @access	public
	 * @param	resource	[Optional] Query id
	 * @return	void
	 */
	public function freeResult( $query_id=null )
	{
   		if ( ! $query_id )
   		{
    		$query_id = $this->query_id;
    	}
    	
    	@mysql_free_result( $query_id );
    }
	
    /**
	 * Retrieve row from database
	 *
	 * @access	public
	 * @param	resource	[Optional] Query result id
	 * @return	mixed		Result set array, or void
	 */
	public function fetch( $query_id=null )
	{
    	if ( ! $query_id )
    	{
    		$query_id = $this->query_id;
    	}
    	
        $this->record_row = mysql_fetch_array( $query_id, MYSQL_ASSOC );
        
        return $this->record_row;
    }

	/**
	 * Return the number calculated rows (as if there was no limit clause)
	 *
	 * @access	public
	 * @param	string 		[ alias name for the count(*) ]
	 * @return	int			The number of rows
	 */
	public function fetchCalculatedRows( $alias='count' )
	{
		$calcRowsHandle = mysql_query( "SELECT FOUND_ROWS() as " . $alias, $this->connection_id );
		
		$val = mysql_fetch_array( $calcRowsHandle, MYSQL_ASSOC );
		
		return intval( $val[ $alias ] );
	}
	
    /**
	 * Get array of fields in result set
	 *
	 * @access	public
	 * @param	resource	[Optional] Query id
	 * @return	array		Fields in result set
	 */
	public function getResultFields( $query_id=null )
	{
    	$fields = array();
    	
   		if ( !$query_id )
   		{
    		$query_id = $this->query_id;
    	}
    
		while( $field = mysql_fetch_field($query_id) )
		{
            $fields[] = $field;
		}
		
		return $fields;
	}

    /**
	 * Get array of table names in database
	 *
	 * @access	public
	 * @return	array		SQL tables
	 */
	public function getTableNames()
	{
		if ( is_array( $this->cached_tables ) AND count( $this->cached_tables ) )
	    {
		    return $this->cached_tables;
	    }
	    
	    $current			= $this->return_die;
	    $this->return_die 	= true;
	    
	    $qid = $this->query( "SHOW TABLES FROM `{$this->obj['sql_database']}`" );
	    
	    $this->return_die 	= $current;
	    
	    if( $qid AND $this->getTotalRows($qid) )
	    {
		    while( $result = mysql_fetch_array($qid) )
		    {
				$this->cached_tables[] = $result[0];
			}
		}
		
		mysql_free_result($qid);
		
		return $this->cached_tables;
   	}
	
    /**
	 * Retrieve SQL server version
	 *
	 * @access	public
	 * @return	string		SQL Server version
	 */
	public function getSqlVersion()
	{
		if ( ! $this->sql_version and ! $this->true_version )
		{
			$this->query( "SELECT VERSION() AS version" );
			
			if ( ! $row = $this->fetch() )
			{
				$this->query( "SHOW VARIABLES LIKE 'version' ");
				$row = $this->fetch();
			}
			
			$this->true_version = $row['version'];
			$tmp                = explode( '.', preg_replace( "#[^\d\.]#", "\\1", $row['version'] ) );
			
			$this->sql_version = sprintf('%d%02d%02d', $tmp[0], $tmp[1], $tmp[2] );
   		}
   		
   		return $this->sql_version;
	}

    /**
	 * Escape strings for DB insertion
	 *
	 * @access	public
	 * @param	string		Text to escape
	 * @return	string		Escaped text
	 */
	public function addSlashes( $t )
	{
		return ( IPS_MAIN_DB_CLASS_LEGACY ) ? mysql_escape_string($t) : mysql_real_escape_string($t, $this->connection_id );
	}

    /**
	 * Get SQL error number
	 *
	 * @access	protected
	 * @return	mixed		Error number/code
	 */
	protected function _getErrorNumber()
	{
	    if( $this->connection_id )
	    {
		    return @mysql_errno( $this->connection_id );
	    }
	    else
	    {
		    return @mysql_errno();
	    }
	}
	
    /**
	 * Get SQL error message
	 *
	 * @access	protected
	 * @return	string		Error message
	 */
	protected function _getErrorString()
	{
	    if( $this->connection_id )
	    {
		    return @mysql_error( $this->connection_id );
	    }
	    else
	    {
		    return @mysql_error();
	    }
	}
	
    
} // end class
