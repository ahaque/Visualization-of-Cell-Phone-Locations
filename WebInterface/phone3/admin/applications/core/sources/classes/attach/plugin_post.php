<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * IP.Board post attachments plugin
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		6/24/2008
 * @version		$Revision: 4948 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_post extends class_attach
{
	/**
	 * Module type
	 *
	 * @access	public
	 * @var		string
	 */
	public $module = 'post';
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		/* Load and init forums */
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			try
			{
				require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
				$this->registry->setClass( 'class_forums', new class_forums( $registry ) );
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
			
			$this->registry->getClass('class_forums')->strip_invisible = 1;
			$this->registry->getClass('class_forums')->forumsInit();
		}
	}
	
	/**
	 * Checks the attachment and checks for download / show perms
	 *
	 * @access	public
	 * @param	integer		Attachment id
	 * @return	array 		Attachment data
	 */
	public function getAttachmentData( $attach_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_ok     = 0;
		
		if( ! $attach_id )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->DB->build( array( 
									'select'   => 'a.*',
									'from'     => array( 'attachments' => 'a' ),
									'where'    => "a.attach_rel_module='{$this->module}' AND a.attach_id={$attach_id}",
									'add_join' => array( 
														array( 
																'select' => 'p.pid, p.topic_id, p.queued',
																'from'   => array( 'posts' => 'p' ),
																'where'  => "p.pid=a.attach_rel_id",
																'type'   => 'left' 
															),
														array( 
																'select' => 't.forum_id',
																'from'   => array( 'topics' => 't' ),
																'where'  => "t.tid=p.topic_id",
																'type'   => 'left' 
															) 
													)
										)	);

		$attach_sql = $this->DB->execute();
		
		$attach     = $this->DB->fetch( $attach_sql );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! isset( $attach['pid'] ) OR empty( $attach['pid'] ) )
		{
			if( $attach['attach_member_id'] != $this->memberData['member_id'] )
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
		// Queued post?
		//-----------------------------------------
		
		if ( $attach['queued'] )
		{ 
			if ( ! ipsRegistry::getClass('class_forums')->canQueuePosts( $attach['forum_id'] ) )
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
    	// TheWalrus inspired fix for previewing
    	// the post and clicking the attachment...
    	//-----------------------------------------

    	if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->memberData['member_id'] )
    	{
    		$_ok = 1;
    	}
    	else
    	{
			if ( ! ipsRegistry::getClass('class_forums')->forum_by_id[ $attach['forum_id'] ] )
			{
				//-----------------------------------------
				// TheWalrus inspired fix for previewing
				// the post and clicking the attachment...
				//-----------------------------------------
				
				if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->memberData['member_id'] )
				{
					# We're ok.
				}
				else
				{
					return FALSE;
				}
			}

			if ( IPSMember::checkPermissions('read', $attach['forum_id']) === FALSE )
			{
				return FALSE;
			}

	        if ( IPSMember::checkPermissions('download', $attach['forum_id']) === FALSE )
	        {
				return FALSE;
			}
			
			//-----------------------------------------
			// Still here?
			//-----------------------------------------
			
			$_ok = 1;
		}

		//-----------------------------------------
		// Ok?
		//-----------------------------------------

		if ( $_ok )
		{
			return $attach;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Check the attachment and make sure its OK to display
	 *
	 * @access	public
	 * @param	array		Array of ids
	 * @param	array 		Array of relationship ids
	 * @return	array 		Attachment data
	 */
	public function renderAttachment( $attach_ids, $rel_ids=array(), $attach_post_key=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rows  		= array();
		$query_bits	= array();
		$query 		= '';
		$match 		= 0;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( is_array( $attach_ids ) AND count( $attach_ids ) )
		{
			$query_bits[] = "attach_id IN (" . implode( ",", $attach_ids ) .")";
		}
		
		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			// We need to reset the array - this query bit will return everything we need, but
			// if we "OR" join the above query bit with this one, it causes bigger mysql loads
			$query_bits	  = array();
			$query_bits[] = "attach_rel_id IN (" . implode( ",", $rel_ids ) . ")";
			//$query = " OR attach_rel_id IN (-1," . implode( ",", $rel_ids ) . ")";
			$match = 1;
		}
		
		if ( $attach_post_key )
		{
			$query_bits[] = "attach_post_key='".$this->DB->addSlashes( $attach_post_key )."'";
			//$query .= " OR attach_post_key='".$this->DB->addSlashes( $attach_post_key )."'";
			$match  = 2;
		}
		
		if( !count($query_bits) )
		{
			$query = "attach_id IN (-1)";
		}
		else
		{
			$query = implode( " OR ", $query_bits );
		}
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='{$this->module}' AND ( {$query} )" ) );
		$attach_sql = $this->DB->execute();
		
		//-----------------------------------------
		// Loop through and filter off naughty ids
		//-----------------------------------------
		
		while( $db_row = $this->DB->fetch( $attach_sql ) )
		{
			$_ok = 1;
			
			if ( $match == 1 )
			{
				if ( ! in_array( $db_row['attach_rel_id'], $rel_ids ) )
				{
					$_ok = 0;
				}
			}
			else if ( $match == 2 )
			{
				if ( $db_row['attach_post_key'] != $attach_post_key )
				{
					$_ok = 0;
				}
			}
			
			//-----------------------------------------
			// Ok?
			//-----------------------------------------
			
			if ( $_ok )
			{
				$rows[ $db_row['attach_id'] ] = $db_row;
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
	
		return $rows;
	}
	
	/**
	 * Recounts number of attachments for the articles row
	 *
	 * @access	public
	 * @param	string		Post key
	 * @param	integer		Related ID
	 * @param	array 		Arguments for query
	 * @return	array 		Returns count of items found
	 */
	public function postUploadProcess( $post_key, $rel_id, $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $post_key )
		{
			return 0;
		}
		
		
		$this->DB->build( array( "select" => 'COUNT(*) as cnt', 'from' => 'attachments', 'where'  => "attach_post_key='{$post_key}'" ) );
		$this->DB->execute();
	
		$cnt = $this->DB->fetch();
		
		if ( $cnt['cnt'] )
		{
			$this->DB->update( 'topics', "topic_hasattach=topic_hasattach+" . $cnt['cnt'], "tid=" . intval( $args['topic_id'] ), false, true );
		}
		
		return array( 'count' => $cnt['cnt'] );
	}
	
	/**
	 * Recounts number of attachments for the articles row
	 *
	 * @access	public
	 * @param	array 		Attachment data
	 * @return	boolean
	 */
	public function attachmentRemovalCleanup( $attachment )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		$this->DB->build( array( 
								'select'   => 'p.pid',
								'from'     => array( 'posts' => 'p' ),
								'where'    => 'p.pid='. intval( $attachment['attach_rel_id'] ),
								'add_join' => array(
													array( 
															'select' => 't.forum_id, t.tid',
															'from'   => array( 'topics' => 't' ),
															'where'  => 't.tid=p.topic_id',
															'type'   => 'inner' 
														) 
													) 
						)	);
																				
		$this->DB->execute();
		
		$topic = $this->DB->fetch();
	
		if ( isset( $topic['tid'] ) )
		{
			//-----------------------------------------
			// GET PIDS
			//-----------------------------------------
		
			$pids  = array();
			$count = 0;
		
			$this->DB->build( array( 'select' => 'pid', 'from' => 'posts', 'where' => "topic_id={$topic['tid']}" ) );
			$this->DB->execute();
				
			while ( $p = $this->DB->fetch() )
			{
				$pids[] = $p['pid'];
			}
		
			//-----------------------------------------
			// GET ATTACHMENT COUNT
			//-----------------------------------------
		
			if ( count( $pids ) )
			{
				$this->DB->build( array( 
										'select' => 'count(*) as cnt',
										'from'   => 'attachments',
										'where'  => "attach_rel_module='post' AND attach_rel_id IN(" . implode( ',', $pids ) . ")") );
				$this->DB->execute();
			
				$cnt = $this->DB->fetch();
			
				$count = intval( $cnt['cnt'] );
			}
		
			$this->DB->update( 'topics', array( 'topic_hasattach' => $count ), "tid=".$topic['tid'] );
		}
		
		return TRUE;
	}
	
	/**
	 * Determines if you have permission for bulk attachment removal
	 * Returns TRUE or FALSE
	 * IT really does
	 *
	 * @access	public
	 * @param	array 		Ids to check against
	 * @return	boolean
	 */
	public function canBulkRemove( $attach_rel_ids=array() )
	{
		return $this->memberData['g_is_supmod'] ? true : false;
	}
	
	/**
	 * Determines if you can remove this attachment
	 * Returns TRUE or FALSE
	 * IT really does
	 *
	 * @access	public
	 * @param	array 		Attachment data
	 * @return	boolean
	 */
	public function canRemove( $attachment )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ok_to_remove = FALSE;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] == $attachment['attach_member_id'] )
		{
			$ok_to_remove = TRUE;
		}
		else if ( $this->memberData['g_is_supmod'] )
		{
			$ok_to_remove = TRUE;
		}
		else
		{
			//-----------------------------------------
			// Moderstor? Get forum ID
			//-----------------------------------------
			
			if ( $this->memberData['forumsModeratorData'] )
			{
				$_moderator = $this->memberData['forumsModeratorData'];
				
				$this->DB->build( array( 
										'select'   => 'p.pid',
										'from'     => array( 'posts' => 'p' ),
										'where'    => 'p.pid='. intval( $attachment['attach_rel_id'] ),
										'add_join' => array( 
															array( 
																	'select' => 't.forum_id, t.tid',
																	'from'   => array( 'topics' => 't' ),
																	'where'  => 't.tid=p.topic_id',
																	'type'   => 'inner' 
																) 
															) 
								)	 );
																						
				$this->DB->execute();
				
				$topic = $this->DB->fetch();
				
				if ( isset( $topic['forum_id'] ) )
				{
					if ( isset($_moderator[ $topic['forum_id'] ]['edit_post']) and $_moderator[ $topic['forum_id'] ]['edit_post'] )
					{
						$ok_to_remove = TRUE;
					}
				}
			}	
		}
		
		return $ok_to_remove;
	}
	
	/**
	 * Returns an array of the allowed upload sizes in bytes.
	 * Return 'space_allowed' as -1 to not allow uploads.
	 * Return 'space_allowed' as 0 to allow unlimited uploads
	 * Return 'max_single_upload' as 0 to not set a limit
	 *
	 * @access	public
	 * @param	string		MD5 post key
	 * @param	id			Member ID
	 * @return	array 		[ 'space_used', 'space_left', 'space_allowed', 'max_single_upload' ]
	 */
	public function getSpaceAllowance( $post_key='', $member_id='' )
	{
		$max_php_size      = intval( IPSLib::getMaxPostSize() );
		$member_id         = intval( $member_id ? $member_id : $this->memberData['member_id'] );
		$forum_id          = intval( ipsRegistry::$request['forum_id'] ? ipsRegistry::$request['forum_id'] : ipsRegistry::$request['f'] );
		$space_left        = 0;
		$space_used        = 0;
		$space_allowed     = 0;
		$max_single_upload = 0;
		$space_calculated  = 0;
	
		if ( $post_key )
		{
			//-----------------------------------------
			// Check to make sure we're not attempting
			// to upload to another's post...
			//-----------------------------------------

			if ( ! $this->memberData['g_is_supmod'] AND !$this->memberData['is_mod'] )
			{
				$post = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'posts', 'where' => "post_key='{$post_key}'" ) );
				
				if ( $post['post_key'] AND ( $post['author_id'] != $member_id ) )
				{
					$space_allowed    = -1;
					$space_calculated = 1;
				}
			}
		}
		
		//-----------------------------------------
		// Generate total space allowed
		//-----------------------------------------
		
		$total_space_allowed = ( $this->memberData['g_attach_per_post'] ? $this->memberData['g_attach_per_post'] : $this->memberData['g_attach_max'] ) * 1024;
		
		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------
		
		if ( ! $member_id OR ! $forum_id )
		{
			$space_allowed = -1;
		}
		if ( IPSMember::checkPermissions('upload', $forum_id ) !== TRUE )
		{
			$space_allowed = -1;
		}
		else if ( ! $space_calculated )
		{
			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------
			
			if ( $this->memberData['g_attach_per_post'] )
			{
				//-----------------------------------------
				// Per post limit...
				//-----------------------------------------
				
				$_space_used = $this->DB->buildAndFetch( array( 
																'select' => 'SUM(attach_filesize) as figure', 
																'from'   => 'attachments', 
																'where'  => "attach_post_key='{$post_key}'" 
														) );

				$space_used = intval( $_space_used['figure'] );
			}
			else
			{
				//-----------------------------------------
				// Global limit...
				//-----------------------------------------
				
				$_space_used = $this->DB->buildAndFetch( array( 
																'select' => 'SUM(attach_filesize) as figure',
																'from'   => 'attachments',
																'where'  => "attach_member_id={$member_id} AND attach_rel_module IN( 'post', 'msg' )" 
														)	);

				$space_used    = intval( $_space_used['figure'] );
			}	

			if ( $this->memberData['g_attach_max'] > 0 )
			{ 
				if ( $this->memberData['g_attach_per_post'] )
				{
					$_g_space_used	= $this->DB->buildAndFetch( array( 
																		'select' => 'SUM(attach_filesize) as figure',
																		'from'   => 'attachments',
																		'where'  => "attach_member_id={$member_id} AND attach_rel_module IN( 'post', 'msg' )" 
															)	 );

					$g_space_used    = intval( $_g_space_used['figure'] );
					
					if( intval( ( $this->memberData['g_attach_max'] * 1024 ) - $g_space_used ) < 0 )
					{
						$space_used    			= $g_space_used;
						$total_space_allowed	= $this->memberData['g_attach_max'] * 1024;
						
						$space_allowed = ( $this->memberData['g_attach_max'] * 1024 ) - $space_used;
						$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
					}
					else
					{
						$space_allowed = ( $this->memberData['g_attach_per_post'] * 1024 ) - $space_used;
						$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
					}
				}
				else
				{
					$space_allowed = ( $this->memberData['g_attach_max'] * 1024 ) - $space_used;
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
			}
			else
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$space_allowed = ( $this->memberData['g_attach_per_post'] * 1024 ) - $space_used;
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{ 
					# Unlimited
					$space_allowed = 0;
				}
			}
			
			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------
			
			$space_left = $space_allowed ? $space_allowed : 0;
			$space_left = ($space_left < 0) ? -1 : $space_left;
			
			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------
			
			if ( ! $max_single_upload )
			{
				if ( $space_left > 0 AND $space_left < $max_php_size )
				{
					$max_single_upload = $space_left;
				}
				else if ( $max_php_size )
				{
					$max_single_upload = $max_php_size;
				}
			}
		}
		
		IPSDebug::fireBug( 'info', array( 'Space left: ' . $space_left ) );
		IPSDebug::fireBug( 'info', array( 'Max PHP size: ' . $max_php_size ) );
		IPSDebug::fireBug( 'info', array( 'Max single file size: ' . $max_single_upload ) );
		
		$return = array( 'space_used' => $space_used, 'space_left' => $space_left, 'space_allowed' => $space_allowed, 'max_single_upload' => $max_single_upload, 'total_space_allowed' => $total_space_allowed );

		return $return;
	}
	
	/**
	 * Returns an array of settings:
	 * 'siu_thumb' = Allow thumbnail creation?
	 * 'siu_height' = Height of the generated thumbnail in pixels
	 * 'siu_width' = Width of the generated thumbnail in pixels
	 * 'upload_dir' = Base upload directory (must be a full path)
	 *
	 * You can omit any of these settings and IPB will use the default
	 * settings (which are the ones entered into the ACP for post thumbnails)
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function getSettings()
	{
		$this->mysettings = array();
		
		return true;
	}

}