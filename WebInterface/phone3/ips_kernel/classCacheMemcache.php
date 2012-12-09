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

class classCacheMemcache implements interfaceCache
{
	/**
	 * Identifier
	 * @access	private
	 * @var		string
	 */
	private $identifier	= '';
	
	/**
	 * Connection resource
	 *
	 * @access	private
	 * @var		resource	Connection resource
	 */
	private $link		= null;
	
    /**
	 * Constructor
	 *
	 * @access	public
	 * @param	string 		Unique identifier
	 * @param	array 		Connection information
	 * @return	boolean		Initiation successful
	 */
	public function __construct( $identifier='', $server_info=array() )
	{
		if( !function_exists('memcache_connect') )
		{
			$this->crashed = true;
			return false;
		}

		$this->identifier	= $identifier;
		
		return $this->_connect( $server_info );
	}
	
    /**
	 * Connect to memcache server
	 *
	 * @access	private
	 * @param	array 		Connection information
	 * @return	boolean		Initiation successful
	 */
	private function _connect( $server_info=array() )
	{
		if( !count($server_info) )
		{
			$this->crashed = true;
			return false;
		}
		
		if( !isset($server_info['memcache_server_1']) OR !isset($server_info['memcache_port_1']) )
		{
			$this->crashed = true;
			return false;
		}
		
		$this->link = memcache_connect( $server_info['memcache_server_1'], $server_info['memcache_port_1'] );
		
		if( !$this->link )
		{
			$this->crashed = true;
			return false;
		}
		
		if( isset($server_info['memcache_server_2']) AND isset($server_info['memcache_port_2']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_2'], $server_info['memcache_port_2'] );
		}
		
		if( isset($server_info['memcache_server_3']) AND isset($server_info['memcache_port_3']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_3'], $server_info['memcache_port_3'] );
		}
		
		if( function_exists('memcache_set_compress_threshold') )
		{
			memcache_set_compress_threshold( $this->link, 20000, 0.2 );
		}
		
		return true;
	}
	
    /**
	 * Disconnect from remote cache store
	 *
	 * @access	public
	 * @return	boolean		Disconnect successful
	 */
	public function disconnect()
	{
		if( $this->link )
		{
			return memcache_close( $this->link );
		}
		
		return false;
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
		return memcache_set( $this->link, md5( $this->identifier . $key ), $value, MEMCACHE_COMPRESSED, intval($ttl) );
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
		return memcache_get( $this->link, md5( $this->identifier . $key ) );
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
		$this->removeFromCache( $key );
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
		return memcache_delete( $this->link, md5( $this->identifier . $key ) );
	}
}