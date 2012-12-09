<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * MySQL Database Diagnostic Methods
 * Last Updated: $Date: 2009-07-09 22:20:32 -0400 (Thu, 09 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Tuesday 1st March 2005 15:40
 * @version		$Revision: 293 $
 */

class db_tools
{
	/**
	 * Missing column/table/index flag
	 *
	 * @var		boolean			Table/index has issues
	 * @access	public
	 */
	public $has_issues			= false;
	
	/**
	 * Internal mapping - ignore me
	 *
	 * @var		array			Table/column mappings
	 * @access	private
	 */
	private $_mapping			= array();

	/**
	 * Diagnose table indexes
	 *
	 * @access	public
	 * @param	array 			Array of create table/index statements to check
	 * @param	array 			Array of issues to fix
	 * @return	array 			Array of results
	 */
	public function dbIndexDiag( $sql_statements, $issues_to_fix='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$indexes 		= array();
		$error_count 	= 0;
		$output			= array();

		//-----------------------------------------
		// Do we have SQL statements?
		//-----------------------------------------
		
		if( is_array($sql_statements) && count($sql_statements) )
		{
			//-----------------------------------------
			// Loop over our statements
			//-----------------------------------------
			
			foreach( $sql_statements as $definition )
			{
				//-----------------------------------------
				// Some more per-statement init
				//-----------------------------------------
				
				$table_name		= "";
				$fields_str		= "";
				$primary_key	= "";
				$tablename		= array();
				$fields			= array();
				$final_keys		= array();
				$col_definition	= "";
				$colmatch		= array();
				$final_primary	= array();
				
				//-----------------------------------------
				// Is this a create table statement?
				//-----------------------------------------
				
		        if ( preg_match( "#CREATE\s+TABLE\s+?(.+?)\s+?\(#ie", $definition, $tablename ) )
		        {
			        $tableName	= $tablename[1];
			        
			        //-----------------------------------------
			        // Does the table have a primary key?
			        //-----------------------------------------
			        
			        if ( preg_match( "#\s+?PRIMARY\s+?KEY\s+?(?:(\w+?)\s+?)?\((.*?)\)(?:(?:[,\s+?$])?\((.+?)\))?#is", $definition, $fields ) )
			        {
			        	$final_primary	= array();

			        	//-----------------------------------------
			        	// Did we find anything with our regex?
			        	//-----------------------------------------
			        	
				        if( count( $fields ) )
				        {
				        	//-----------------------------------------
				        	// Get the actual key name
				        	//-----------------------------------------
				        	
					        $primary_key	= trim($fields[1]);
					        $primary_fields	= implode( ",", array_map( 'trim', explode( ",", $fields[2] ) ) );

					        //-----------------------------------------
					        // Get the table definition
					        //-----------------------------------------
					        
					        $col_definition = $this->_sqlStripTicks( $definition );

							//-----------------------------------------
							// This is the primary key for this table
							//-----------------------------------------
							
					        $final_primary = array( $primary_key ? $primary_key : $primary_fields, $primary_fields );
	            		}
			        }
			        
					$table_array[$i] = $tableName;
					
					//-----------------------------------------
					// We found a primary key, store it
					//-----------------------------------------
					
					if ( count( $final_primary ) )
					{
						$primary_array[$i] = $final_primary;
					}
			    }

				//-----------------------------------------
				// Now find all non-primary keys
				//-----------------------------------------
				
		        if ( preg_match_all( "#(?<!PRIMARY)\s*?KEY\s+?(?:(\w+?)\s+?)?\((.*?)\)(\n|,\n)#is", $definition, $fields ) )
		        {
		        	//-----------------------------------------
		        	// We got some fields!
		        	//-----------------------------------------
		        	
			        if( count( $fields[2] ) )
			        {
				        $i = 0;
				        
				        //-----------------------------------------
				        // Loop over the data from the preg statement
				        //-----------------------------------------
				        
				        foreach( $fields[2] as $index_cols )
				        {
				        	//-----------------------------------------
				        	// Get index name, column name, and store
				        	//-----------------------------------------
				        	
		            		$index_cols		= implode( ",", array_map( 'trim', explode( ",", $this->_sqlStripTicks( $index_cols ) ) ) );
		            		$index_name		= $fields[1][$i] ? $fields[1][$i] : $index_cols;

		            		if( $index_cols != $final_primary[1] )
		            		{
		            			$final_keys[]	= array( $index_name, $index_cols );
	            			}
		            		
		            		$i++;
	            		}
            		}
		        }

				//-----------------------------------------
				// We have some indexes for this table
				//-----------------------------------------
				
			    if( $tableName AND ( $primary_key OR count($final_keys) ) )
			    {
				    $indexes[] = array( 'table' 	=> $tableName,
				    					'primary'	=> $final_primary,
				    					'index'		=> $final_keys
				    				  );
			    }
		    }
	    }

		//-----------------------------------------
		// No indexes on this table
		//-----------------------------------------
		
	    if( !count($indexes) )
	    {
		   return false; 
		}
		
		//-----------------------------------------
		// Loop over the indexes
		//-----------------------------------------
	    
		foreach( $indexes as $data )
		{
			//-----------------------------------------
			// Get table schematics and clean it
			//-----------------------------------------
			
			$row	= ipsRegistry::DB()->getTableSchematic( $data['table'] );
			$tbl	= $this->_sqlStripTicks( $row['Create Table'] );
			
			//-----------------------------------------
			// Start output (one per index)
			//-----------------------------------------

			$output[ $data['table'] ]	= array( 'table'		=> ipsRegistry::$settings['sql_tbl_prefix'].$data['table'],
												 'status'		=> 'ok',
												 'missing'		=> array(),
												);
			
			//-----------------------------------------
			// We had a primary key, so let's look for it
			//-----------------------------------------
			
			if( isset( $data['primary'] ) && is_array($data['primary']) AND count($data['primary']) )
			{
				$index_name = $data['primary'][0];
				$index_cols	= $data['primary'][1];
				$ok			= 0;

				//-----------------------------------------
				// Can we find it...?
				//-----------------------------------------
				
				if ( preg_match( "#\s*PRIMARY\s+?KEY\s*(\((.+?)\))?#is", $tbl, $matches ) )
				{
					$ok = 1;

					//-----------------------------------------
					// It is...now is the index right (mulicolumn)?
					//-----------------------------------------
	
					if ( $index_cols != $matches[2] )
					{
						//-----------------------------------------
						// Break out the real indexes and loop
						//-----------------------------------------

						foreach( explode( ',', $index_cols ) as $mc )
						{
							//-----------------------------------------
							// And make sure it's in the definition
							//-----------------------------------------
							
							if ( strpos( $match[2], $mc ) === false )
							{
								$query_needed	= 'ALTER TABLE ' . ipsRegistry::$settings['sql_tbl_prefix'] . $data['table'] . ' DROP INDEX ' . $index_name . ', ADD INDEX ' . $index_name . ' (' . $index_cols . ')';

								//-----------------------------------------
								// Are we fixing now?
								//-----------------------------------------
								
								if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
								{
									ipsRegistry::DB()->query( $query_needed );
									
									break;
								}
								
								//-----------------------------------------
								// We got issues gomer!
								//-----------------------------------------
								
								$this->has_issues = 1;

								$output[ $data['table'] ]['status']		= 'error';
								$output[ $data['table'] ]['index'][]	= $index_name;
								$output[ $data['table'] ]['missing'][]	= $index_name;
								$output[ $data['table'] ]['fixsql'][]	= $query_needed;

								$ok       = 0;
								
								break;
							}
						}
					}
				}
				else
				{
					//-----------------------------------------
					// Generate query and set the output array
					//-----------------------------------------
					
					$query_needed = "ALTER TABLE " . ipsRegistry::$settings['sql_tbl_prefix'] . "{$data['table']} ADD PRIMARY KEY ({$index_name})";

					//-----------------------------------------
					// Are we fixing now?
					//-----------------------------------------
					
					if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
					{
						$ok	= 1;

						ipsRegistry::DB()->query( $query_needed );
					}
					else
					{
						$this->has_issues = 1;
						
						$output[ $data['table'] ]['status']		= 'error';
						$output[ $data['table'] ]['index'][]	= $index_name;
						$output[ $data['table'] ]['missing'][]	= $index_name;
						$output[ $data['table'] ]['fixsql'][]	= $query_needed;
	
						$error_count++;
					}
				}
				
				//-----------------------------------------
				// Primary key is fine
				//-----------------------------------------
				
				if( $ok )
				{
					$output[ $data['table'] ]['index'][]	= $index_name;
				}
			}

			//-----------------------------------------
			// Got other indexes?
			//-----------------------------------------
			
			if ( isset( $data['index'] ) && is_array( $data['index'] ) and count( $data['index'] ) )
			{
				//-----------------------------------------
				// Loop over the other indexes
				//-----------------------------------------
				
				foreach( $data['index'] as $indexes )
				{
					$index_name	= $indexes[0];
					$index_cols	= $indexes[1] ? $indexes[1] : $index_name;

					$ok			= 0;

					//-----------------------------------------
					// Is the key there?
					//-----------------------------------------
					
					if ( preg_match( "#(?<!PRIMARY)\s+?KEY\s+?{$index_name}\s+?(\((.+?)\))(\n|,\n)#is", $tbl, $match ) )
					{
						$ok = 1;

						//-----------------------------------------
						// It is...now is the index right (mulicolumn)?
						//-----------------------------------------
		
						if ( $index_cols != $match[2] )
						{
							//-----------------------------------------
							// Break out the real indexes and loop
							//-----------------------------------------
							
							foreach( explode( ',', $indexes[1] ) as $mc )
							{
								//-----------------------------------------
								// And make sure it's in the definition
								//-----------------------------------------
								
								if ( strpos( $match[2], $mc ) === false )
								{
									$query_needed	= 'ALTER TABLE ' . ipsRegistry::$settings['sql_tbl_prefix'] . $data['table'] . ' DROP INDEX ' . $index_name . ', ADD INDEX ' . $index_name . ' (' . $index_cols . ')';

									//-----------------------------------------
									// Are we fixing now?
									//-----------------------------------------
									
									if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
									{
										ipsRegistry::DB()->query( $query_needed );
										
										continue 2;
									}

									$this->has_issues = 1;

									$output[ $data['table'] ]['status']		= 'error';
									$output[ $data['table'] ]['index'][]	= $index_name;
									$output[ $data['table'] ]['missing'][]	= $index_name;
									$output[ $data['table'] ]['fixsql'][]	= $query_needed;

									$ok       = 0;
									
									break;
								}
							}
						}
					}
					else
					{
						//-----------------------------------------
						// Generate query and set the output array
						//-----------------------------------------
						
						$query_needed = 'ALTER TABLE ' . ipsRegistry::$settings['sql_tbl_prefix'] . $data['table'] . ' ADD INDEX ' . $index_name . ' (' . $index_cols . ')';

						//-----------------------------------------
						// Are we fixing now?
						//-----------------------------------------
						
						if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
						{
							ipsRegistry::DB()->query( $query_needed );
							
							$ok	= 1;
						}
						else
						{
							$output[ $data['table'] ]['status']		= 'error';
							$output[ $data['table'] ]['index'][]	= $index_name;
							$output[ $data['table'] ]['missing'][]	= $index_name;
							$output[ $data['table'] ]['fixsql'][]	= $query_needed;
	
							$error_count++;
						}
					}

					//-----------------------------------------
					// The index is ok
					//-----------------------------------------
					
					if ( $ok )
					{
						$output[ $data['table'] ]['index'][]	= $index_name;
					}
				}
			}
		}
		
		return array( 'error_count'	=> $error_count, 'results' => $output );
	}


	/**
	 * Diagnose table structure
	 *
	 * @access	public
	 * @param	array 			Array of create table/index statements to check
	 * @param	array 			Array of issues to fix
	 * @return	array 			Array of results
	 */
	public function dbTableDiag( $sql_statements, $issues_to_fix='' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$queries_needed		= array();
		$tables_needed		= array();
		$table_definitions	= array();
		$error_count		= 0;

		//-----------------------------------------
		// Do we have any statements?
		//-----------------------------------------
		
		if( is_array( $sql_statements ) && count( $sql_statements ) )
		{
			//-----------------------------------------
			// Loop over those statements
			//-----------------------------------------
			
			foreach( $sql_statements as $the_table )
			{
				$expected_columns	= array();
				$missing_columns	= array();

				//-----------------------------------------
				// Is this a create table statement?
				//-----------------------------------------
				
				if( preg_match( "#CREATE\s+TABLE\s+?(.+?)\s+?\(#ie", $the_table, $definition ) )
				{
					$tableName	= ipsRegistry::$settings['sql_tbl_prefix'] . $definition[1];
					
					//-----------------------------------------
					// Store the entire table definition
					//-----------------------------------------
					
					$table_definitions[ $tableName ] = str_replace( $definition[1], $tableName, $the_table );
					
					//-----------------------------------------
					// Get the columns
					//-----------------------------------------
					
					$columns_array = explode( "\n", $the_table );

					//-----------------------------------------
					// Get rid of first row ("CREATE TABLE ...")
					//-----------------------------------------
					
					array_shift($columns_array);
					
					//-----------------------------------------
					// Get rid of the junk at the end of each line
					//-----------------------------------------
					
					if ( ( strpos(end($columns_array), ");") === 0 ) OR 
						 ( strpos(end($columns_array), ")") === 0 )  OR
						 ( strpos(end($columns_array), ";") === 0 ) )
					{
						array_pop($columns_array);
					}

					reset($columns_array);
					
					//-----------------------------------------
					// Loop over each supposed "column"
					//-----------------------------------------
					
					foreach( $columns_array as $col )
					{
						//-----------------------------------------
						// Find the column name
						//-----------------------------------------
						
						$temp		= preg_split( "/[\s]+/" , trim($col) );
						$columnName	= $temp[0];

						//-----------------------------------------
						// If this is a real column, map it
						//-----------------------------------------
						
						if( !in_array( $columnName, array( "PRIMARY", "KEY", "UNIQUE", "", "(", ";", ");" ) ) )
						{
							$expected_columns[]								= $columnName;
							$this->_mapping[ $tableName ][ $columnName ]	= trim( str_replace( ',', ';', $col ) );
						}
					}
				}
				
				//-----------------------------------------
				// This an alter table statement?
				//-----------------------------------------
				
				elseif ( preg_match( "#ALTER\s+TABLE\s+([a-z_]*)\s+ADD\s+([a-z_]*)\s+#is", $the_table, $definition ) )
				{
					//-----------------------------------------
					// If this is truly adding a new column, map it
					//-----------------------------------------

					if( $definition[1] AND $definition[2] AND $definition[2] != 'INDEX' AND strpos($definition[2], 'TYPE') === false AND strpos($definition[2], 'ENGINE') === false )
					{
						$tableName	= ipsRegistry::$settings['sql_tbl_prefix'] . trim( $definition[1] );
						$columnName	= trim($definition[2]);

						$expected_columns[]								= $columnName;
						$this->_mapping[ $tableName ][ $columnName ]	= $definition[2] . ' ' . str_replace( $definition[0], '', $the_table ) . ";";
					}
				}
				
				//-----------------------------------------
				// We don't care about any other queries
				//-----------------------------------------
				
				else
				{
					continue;
				}

				//-----------------------------------------
				// Don't die on me sarge!
				//-----------------------------------------
				
				ipsRegistry::DB()->return_die = 1;
				
				$tableNoPrefix	= preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $tableName );

				//-----------------------------------------
				// If table is missing entirely, we need to build it
				//-----------------------------------------
				
				if ( ! ipsRegistry::DB()->checkForTable( $tableNoPrefix ) )
				{
					//-----------------------------------------
					// Are we fixing now?
					//-----------------------------------------
					
					if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $tableNoPrefix OR $issues_to_fix == 'all' )
					{
						ipsRegistry::DB()->query( $table_definitions[ $tableName ] );
						
						continue;
					}

					$output[ $tableName ]	= array( 'key'		=> $tableNoPrefix,
													 'table'	=> $tableName,
													 'status'	=> 'error_table',
													 'fixsql'	=> array( $table_definitions[ $tableName ] )
													);

					//-----------------------------------------
					// Increment error counter
					//-----------------------------------------
					
					$error_count++;
					
					//-----------------------------------------
					// Reset failed status
					//-----------------------------------------
					
					ipsRegistry::DB()->failed = 0;
				}
				
				//-----------------------------------------
				// Table exists...
				//-----------------------------------------
				
				else
				{
					//-----------------------------------------
					// Loop over all the columns
					//-----------------------------------------

					foreach( $expected_columns as $trymeout )
					{
						//-----------------------------------------
						// Does column exist?
						//-----------------------------------------
						
						if( ! ipsRegistry::DB()->checkForField( $trymeout, $tableNoPrefix ) )
						{
							//-----------------------------------------
							// Missing - create "ALTER TABLE" query
							//-----------------------------------------
							
							$query_needed		= "ALTER TABLE " . $tableName . " ADD " . $this->_mapping[ $tableName ][ $trymeout ];

							//-----------------------------------------
							// If this is an autoincrement column, we need
							// to add the primary key, since it won't exist
							//-----------------------------------------
							
							if( strpos( $query_needed, "auto_increment;" ) !== false )
							{
								//-----------------------------------------
								// Cut off the ";", add primary key bit
								//-----------------------------------------
								
								$query_needed = substr( $query_needed, 0, -1 ).", ADD PRIMARY KEY( ". $trymeout . ");";
							}

							//-----------------------------------------
							// Are we fixing now?
							//-----------------------------------------
							
							if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $tableNoPrefix OR $issues_to_fix == 'all' )
							{
								ipsRegistry::DB()->query( $query_needed );
								
								continue;
							}
							
							$missing_columns[]	= $trymeout;

							//-----------------------------------------
							// We only do this once
							//-----------------------------------------
							
							if( !isset($output[ $tableName ]) OR !count($output[ $tableName ]) )
							{
								$output[ $tableName ]	= array( 'key'		=> $tableNoPrefix,
																 'table'	=> $tableName,
																 'status'	=> 'error_column',
																);
							}

							//-----------------------------------------
							// But with each error, add the query
							//-----------------------------------------
							
							$output[ $tableName ]['fixsql'][]	= $query_needed;
							
							//-----------------------------------------
							// Increment error count
							//-----------------------------------------
							
							$error_count++;
						}
					}

					//-----------------------------------------
					// If nothing was wrong, show ok message
					//-----------------------------------------
					
					if( !count( $missing_columns ) )
					{
						$output[] = array( 'key'		=> $tableNoPrefix,
										   'table'		=> $tableName,
										   'status'		=> 'ok',
										   'fixsql'		=> '' );
					}
				}
			}
		}
		
		return array( 'error_count'	=> $error_count, 'results' => $output );
	}	


	/**
	* Remove ticks from statement
	*
	* @access	private
	* @param	string 			String
	* @return	string 			String with ticks removed
	*/
	private function _sqlStripTicks( $data )
	{
		return str_replace( "`", "", $data );
	}

}

?>