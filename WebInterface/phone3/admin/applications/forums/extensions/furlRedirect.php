<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * RSS output plugin :: posts
 * Last Updated: $Date: 2009-01-05 22:21:54 +0000 (Mon, 05 Jan 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		6/24/2008
 * @version		$Revision: 3572 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_forums
{	
	/**
	 * Key type: Type of action (topic/forum)
	 *
	 * @access	private
	 * @var		string
	 */
	private $_type = '';
	
	/**
	 * Key ID
	 *
	 * @access	private
	 * @var		int
	 */
	private $_id = 0;
	
	/**
	* Constructor
	*
	*/
	function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
	}

	/**
	 * Set the key ID
	 * @example		furlRedirect_forums::setKey( 'topic', 12 );
	 *
	 * @access	public
	 * @param	string	Type
	 * @param	mixed	Value
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @access	public
	 * @param	string		URI (example: index.php?showtopic=5&view=getlastpost)
	 * @return	void
	 */
	public function setKeyByUri( $uri )
	{
		$uri = str_replace( '&amp;', '&', $uri );
		
		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		foreach( explode( '&', $uri ) as $bits )
		{
			list( $k, $v ) = explode( '=', $bits );
			
			if ( $k )
			{
				if ( $k == 'showtopic' )
				{
					$this->setKey( 'topic', intval( $v ) );
					return TRUE;
				}
				else if ( $k == 'showforum' )
				{
					$this->setKey( 'forum', intval( $v ) );
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	* Return the SEO title
	*
	* @access	public
	* @return	string		The SEO friendly name
	*/
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'topic':
				return $this->_fetchSeoTitle_topic();
			break;
			case 'forum':
				return $this->_fetchSeoTitle_forum();
			break;
		}
	}
	
	/**
	* Return the SEO title for a topic
	*
	* @access	public
	* @return	string		The SEO friendly name
	*/
	public function _fetchSeoTitle_topic()
	{
		$topic = $this->DB->buildAndFetch( array( 'select' => 'tid, title_seo, title, forum_id',
												  'from'   => 'topics',
												  'where'  => 'tid=' . intval( $this->_id ) ) );
												
		if ( $topic['tid'] )
		{
			/* Check permission */
			if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $topic['forum_id'], 0, 'topic', $topic, TRUE ) )
			{
				return FALSE;
			}
						
			return ( $topic['title_seo'] ) ? $topic['title_seo'] : IPSText::makeSeoTitle( $topic['title'] );
		}
		
		return FALSE;
	}
	
	/**
	* Return the SEO title for a topic
	*
	* @access	public
	* @return	string		The SEO friendly name
	*/
	public function _fetchSeoTitle_forum()
	{
		$forum = $this->DB->buildAndFetch( array( 'select' => 'id, name_seo, name',
												  'from'   => 'forums',
												  'where'  => 'id=' . intval( $this->_id ) ) );
												
		if ( $forum['id'] )
		{
			/* Check permission */
			if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $forum['id'], 0, 'forum', array(), TRUE ) )
			{
				return FALSE;
			}
			
			return ( $forum['name_seo'] ) ? $forum['name_seo'] : IPSText::makeSeoTitle( $forum['name'] );
		}
		
		return FALSE;
	}
}