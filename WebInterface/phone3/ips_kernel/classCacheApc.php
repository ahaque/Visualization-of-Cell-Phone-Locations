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

class classCacheApc implements interfaceCache
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
		if( !function_exists('apc_fetch') )
		{
			$this->crashed = true;
			return false;
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
		$ttl = $ttl > 0 ? intval($ttl) : 0;
		
		return apc_store( md5( $this->identifier . $key ), $value, $ttl );
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
		return apc_fetch( md5( $this->identifier . $key ) );
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
		return apc_delete( md5( $this->identifier . $key ) );
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