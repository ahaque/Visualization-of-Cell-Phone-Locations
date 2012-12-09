<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * This class acts as a cache layer, allowing you to store and retrieve data in
 *	external cache sources such as memcache or APC
 * Last Updated: $Date: 2009-07-07 21:22:30 -0400 (Tue, 07 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 291 $
 *
 * Basic Usage Examples
 * <code>
 * $cache = new cache_lib( 'identifier' );
 * Update:
 * $cache->putInCache( 'key', 'value' [, 'ttl'] );
 * Remove
 * $cache->removeFromCache( 'key' );
 * Retrieve
 * $cache->getFromCache( 'key' );
 * </code>
 *
 */
 
class classCacheDiskcache implements interfaceCache
{
	/**
	 * Identifier
	 * @access	private
	 * @var		string
	 */
	private $identifier	= '';
	
    /**
	 * Constructor
	 *
	 * @access	public
	 * @param	string 		Unique identifier
	 * @return	boolean		Initiation successful
	 */
	public function __construct( $identifier='' )
	{
		if( !is_writeable( DOC_IPS_ROOT_PATH . 'cache' ) )
		{
			$this->crashed = true;
			return false;
		}
		
		if( !file_exists( DOC_IPS_ROOT_PATH . 'cache/diskcache_lock.php' ) )
		{
			$fh = @fopen( DOC_IPS_ROOT_PATH . 'cache/diskcache_lock.php', 'wb' );
			
			if( $fh )
			{
				flock( $fh, LOCK_EX );
				fwrite( $fh, 0 );
				flock( $fh, LOCK_UN );
				fclose( $fh );
			}
		}
		
		$this->identifier	= $identifier;
	}
	
    /**
	 * Put data into remote cache store
	 *
	 * @access	public
	 * @param	string		Cache unique key
	 * @param	string		Cache value to add
	 * @param	integer		[Optional] Time to live
	 * @return	boolean		Cache update successful
	 */
	public function putInCache( $key, $value, $ttl=0 )
	{
		$lock = @fopen( DOC_IPS_ROOT_PATH . 'cache/diskcache_lock.php', 'wb' );
		
		if( $lock )
		{
			flock( $lock, LOCK_EX );
		}
		
		$fh = @fopen( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php', 'wb' );
		
		if( !$fh )
		{
			return FALSE;
		}
		
		$extra_flag = "";
		
		if( is_array( $value ) )
		{
			$value = serialize($value);
			$extra_flag = "\n" . '$is_array = 1;' . "\n\n";
		}
		
		$extra_flag .= "\n" . '$ttl = ' . $ttl . ";\n\n";
		
		$value = '"' . addslashes( $value ) . '"';
		
		$file_content = "<?" . "php\n\n" . '$value = ' . $value . ";\n" . $extra_flag . "\n?" . '>';
		
		flock( $fh, LOCK_EX );
		fwrite( $fh, $file_content );
		flock( $fh, LOCK_UN );
		fclose( $fh );
		
		@chmod( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php', 0777 );
		
		flock( $lock, LOCK_UN );
		fclose( $lock );
				
		return true;
	}
	
    /**
	 * Retrieve a value from remote cache store
	 *
	 * @access	public
	 * @param	string		Cache unique key
	 * @return	mixed		Cached value
	 */
	public function getFromCache( $key )
	{
		$lock = @fopen( DOC_IPS_ROOT_PATH . 'cache/diskcache_lock.php', 'wb' );
		
		if( $lock )
		{
			flock( $lock, LOCK_SH );
		}
		
		$return_val = "";
		
		if( file_exists( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php' ) )
		{
			require DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php';
			
			$return_val = stripslashes($value);

			if( isset($is_array) AND $is_array == 1 )
			{
				$return_val = unserialize($return_val);
			}
			
			if( isset($ttl) AND $ttl > 0 )
			{
				if( $mtime = filemtime( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php' ) )
				{
					if( time() - $mtime > $ttl )
					{
						@unlink( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php' );
						
						return FALSE;
					}
				}
			}
		}

		flock( $lock, LOCK_UN );
		fclose( $lock );
		
		return $return_val;
	}
	
    /**
	 * Update value in remote cache store
	 *
	 * @access	public
	 * @param	string		Cache unique key
	 * @param	string		Cache value to set
	 * @param	integer		[Optional] Time to live
	 * @return	boolean		Cache update successful
	 */
	public function updateInCache( $key, $value, $ttl=0 )
	{
		// The putInCache method opens in 'wb' mode, meaning
		// the file is truncated automatically, so no
		// need to delete - deletion is an unnecessary
		// expense with diskcache
		
		return $this->putInCache( $key, $value, $ttl );
	}
	
    /**
	 * Remove a value in the remote cache store
	 *
	 * @access	public
	 * @param	string		Cache unique key
	 * @return	boolean		Cache removal successful
	 */
	public function removeFromCache( $key )
	{
		$lock = @fopen( DOC_IPS_ROOT_PATH . 'cache/diskcache_lock.php', 'wb' );
		
		if( $lock )
		{
			flock( $lock, LOCK_EX );
		}
		
		if( file_exists( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php' ) )
		{
			@unlink( DOC_IPS_ROOT_PATH . 'cache/' . md5( $this->identifier . $key ) . '.php' );
		}
		
		flock( $lock, LOCK_UN );
		fclose( $lock );
	}
	
    /**
	 * Not used by this library
	 *
	 * @return	boolean		Disconnect successful
	 */
	public function disconnect()
	{
		return true;
	}	
}