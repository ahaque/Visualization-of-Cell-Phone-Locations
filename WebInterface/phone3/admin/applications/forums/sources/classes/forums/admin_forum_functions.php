<?php

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Admin forum functions library
 * Last Updated: $Date: 2009-06-24 23:14:22 -0400 (Wed, 24 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		26th January 2004
 * @version		$Rev: 4818 $
 *
 */
class admin_forum_functions extends class_forums
{
	/**
	 * HTML object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;
	
	/**
	 * Type
	 *
	 * @access	public
	 * @var		string
	 */
	public $type      = "";
	
	/**
	 * How many printed
	 *
	 * @access	public
	 * @var		integer
	 */	
	public $printed   = 0;
	
	/**
	 * Whether to show all or not
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $show_all  = false;
	
	/**
	 * Cached skin id -> names
	 *
	 * @access	public
	 * @var		array
	 */
	public $skins     = array();
	
	/**
	 * Need descriptions
	 *
	 * @access	public
	 * @var		array
	 */
	public $need_desc = array();

	/**
	 * Build forum children
	 *	 
	 * @access	public
	 * @param	integer	$root_id
	 * @param	string	$temp_html
	 * @param	string	$depth_guide
	 * @return	string
	 **/
	public function forumBuildChildren( $root_id, $temp_html="", $depth_guide="" )
	{
		if ( isset( $this->forum_cache[ $root_id ] ) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				if ( $this->settings['forum_cache_minimum'] AND $this->settings['forum_cache_minimum'] )
				{
					$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
					$this->need_desc[] = $forum_data['id'];
				}
					
				$temp_html .= $this->renderForum( $forum_data, $depth_guide );
				
				$temp_html = $this->forumBuildChildren( $forum_data['id'], $temp_html, $depth_guide . $this->depth_guide );
			}
		}
		
		return $temp_html;
	}
	
	/**
	 * Build forum
	 *
	 * @access	public
	 * @param	integer	$r
	 * @param	string	$depth_guide
	 * @return	string
	 **/	
	public function renderForum( $r, $depth_guide="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$desc       = "";
		$mod_string = "";
		
		$r['skin_id'] = isset( $r['skin_id'] ) ? $r['skin_id'] : '';
		
		//-----------------------------------------
		// Manage forums?
		//-----------------------------------------
		
		if ( $this->type == 'manage' )
		{
			//-----------------------------------------
			// Show main forums...
			//-----------------------------------------
			
			if ( ! $this->show_all )
			{
				$children = $this->forumsGetChildren( $r['id'] );
				
				$sub       = array();
				$subforums = "";
				$count     = 0;
				
				//-----------------------------------------
				// Build sub-forums link
				//-----------------------------------------
				
				if ( count($children) )
				{
					$r['name'] = "<a href='{$this->settings['base_url']}f={$r['id']}'>" . $r['name'] . "</a>";
					
					foreach ( $children as $cid )
					{
						$count++;
						
						$cfid = $cid;
						
						if ( $count == count( $children ) )
						{
							//-----------------------------------------
							// Last subforum, link to parent
							// forum...
							//-----------------------------------------
							
							if ( !isset($children[ $count - 2 ]) OR ! $cfid = $children[ $count - 2 ] )
							{
								$cfid = $r['id'];
							}
						}
						
						$sub[] = "<a href='{$this->settings['base_url']}f={$this->forum_by_id[$cid]['parent_id']}'>".$this->forum_by_id[$cid]['name']."</a>";
					}
				}
				
				if ( count( $sub ) )
				{
					$subforums = '<fieldset style="margin-top:4px"><legend>' . $this->lang->words['acp_subforum_legend'] . '</legend>' . implode( ", ", $sub ) . '</fieldset>';
				}
				
				//$desc = "{$r['description']}{$subforums}";
			}
			
			$desc = "{$r['description']}{$subforums}";
			
			//-----------------------------------------
			// Moderators
			//-----------------------------------------
			
			$r['_modstring'] = "";
			
			foreach( $this->moderators as $data )
			{
				$forum_ids = explode( ',', IPSText::cleanPermString( $data['forum_id'] ) );
				
				foreach( $forum_ids as $forum_id )
				{
					if ( $forum_id == $r['id'] )
					{
						if ($data['is_group'] == 1)
						{
							$data['_fullname'] = 'Group: ' . $data['group_name'];
						}
						else
						{
							$data['_fullname'] = $data['members_display_name'];
						}
						
						$data['randId']	= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );
						
						$r['_modstring'] .= $this->html->renderModeratorEntry( $data, $forum_id );
					}
				}
			}
			
			//-----------------------------------------
			// Print
			//-----------------------------------------
			
			$this->skins[$r['skin_id']] = ( isset($this->skins[ $r['skin_id'] ] ) AND $this->skins[ $r['skin_id'] ] ) ? $this->skins[ $r['skin_id'] ] : '';

			return $this->html->renderForumRow( $desc, $r, $depth_guide, $this->skins[ $r['skin_id'] ] );
		}
	}
	
	/**
	 * Show Category
	 *	 
	 * @access	public
	 * @param	string	$content
	 * @param	array 	$r
	 * @param	bool	$show_buttons	 
	 * @return	void
	 **/	
	public function forumShowCat( $content, $r, $show_buttons=1 )
	{
		$this->printed++;
		
		$no_root = count( $this->forum_cache['root'] );

		$this->registry->output->html .= $this->html->forumWrapper( $content, $r, $show_buttons );
	}
	
	/**
	 * End Category
	 *	 
	 * @access	public
	 * @param	array 	$r 
	 * @return	void
	 **/
	public function forum_end_cat($r=array())
	{
		// NO LONGER USED?
		if ( $this->type == 'manage' )
		{
			$this->registry->output->html .= $this->registry->output->end_table();
		}
	}
		
	/**
	 * List all forums
	 *
	 * @access	public
	 * @return	void
	 **/
	public function forumsListForums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->show_all = intval( $this->request['showall'] );
		
		if ( ! $this->html )
		{
			$this->html = $this->registry->output->loadTemplate( 'cp_skin_forums' );
		}
		
		//-----------------------------------------
		// Manage forums
		//-----------------------------------------
	
		if ( $this->type == 'manage' )
		{
			foreach( $this->caches['skinsets'] as $id => $data )
			{
				$this->skins[ $id ] = $data['set_name'];
			}
		}
		
		$temp_html = "";
		$fid       = intval( $this->request['f'] );
		
		//-----------------------------------------
		// Show all forums
		//-----------------------------------------
		
		if ( $this->show_all )
		{
			foreach( $this->forum_cache['root'] as $forum_data )
			{
				$cat_data    = $forum_data;
				$depth_guide = "";
				$temp_html 	 = "";
				
				if ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
				{
					foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
					{
						if ( $this->settings['forum_cache_minimum'] AND $this->settings['forum_cache_minimum'] )
						{
							$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
							$this->need_desc[]         = $forum_data['id'];
						}
				
						$temp_html .= $this->renderForum( $forum_data, $depth_guide );

						$temp_html = $this->forumBuildChildren( $forum_data['id'], $temp_html, '<span style="color:gray">&#0124;</span>'.$depth_guide . $this->depth_guide );
					}
				}
				
				if( !$temp_html )
				{
					$temp_html = $this->html->renderNoForums( $cat_data['id'] );
				}
				
				$this->registry->output->html .= $this->forumShowCat( $temp_html, $cat_data );
				unset($temp_html);
			}
		}
		
		//-----------------------------------------
		// Show root forums
		//-----------------------------------------
		
		else if ( ! $fid )
		{
			$seen_count  = 0;
			$total_items = 0;
			
			if( is_array($this->forum_cache[ 'root' ]) AND count($this->forum_cache[ 'root' ]) )
			{
				foreach( $this->forum_cache[ 'root' ] as $forum_data )
				{
					$cat_data    = $forum_data;
					$depth_guide = "";
					$temp_html	 = "";
					
					if ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
					{
						foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							if ( $this->settings['forum_cache_minimum'] AND $this->settings['forum_cache_minimum'] )
							{
								$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
								$this->need_desc[]         = $forum_data['id'];
							}
					
							$temp_html .= $this->renderForum( $forum_data, $depth_guide );
						}
					}
					
					if( !$temp_html )
					{
						$temp_html = $this->html->renderNoForums( $cat_data['id'] );
					}				
					
					$this->registry->output->html .= $this->forumShowCat( $temp_html, $cat_data );
					unset($temp_html);
				}
			}
		}
		
		//-----------------------------------------
		// Show per ID forums
		//-----------------------------------------
		
		else
		{
			$cat_data    = array();
			$depth_guide = "";
			
		
			if ( is_array( $this->forum_cache[ $fid ] ) )
			{
				$cat_data    = $this->forum_by_id[ $fid ];
				$depth_guide = "";
				
				foreach( $this->forum_cache[ $fid ] as $forum_data )
				{
					if ( $this->settings['forum_cache_minimum'] AND $this->settings['forum_cache_minimum'] )
					{
						$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
						$this->need_desc[]         = $forum_data['id'];
					}
			
					$temp_html .= $this->renderForum( $forum_data, $depth_guide );
				}
			}
			
			if( !$temp_html )
			{
				$temp_html = $this->html->renderNoForums( $cat_data['id'] );
			}
			
			$this->registry->output->html .= $this->forumShowCat( $temp_html, $this->forum_by_id[ $fid ] );
			unset( $temp_html );
		}
		
		//-----------------------------------------
        // Get descriptions?
        //-----------------------------------------
        
        if ( $this->settings['forum_cache_minimum'] AND $this->settings['forum_cache_minimum'] and count( $this->need_desc ) )
        {
        	$this->DB->build( array( 'select' => 'id,description', 'from' => 'forums', 'where' => 'id IN('.implode( ',', $this->need_desc ) .')' ) );
        	$this->DB->execute();
        	
        	while( $r = $this->DB->fetch() )
        	{
        		$this->registry->output->html = str_replace( "<!--DESCRIPTION:{$r['id']}-->", $r['description'], $this->registry->output->html );
        	}
        }
	}
	
	/**
	 * Build Forum Jump
	 *
	 * @access	public
	 * @param	bool	$restrict
	 * @return	array
	 **/
	public function adForumsForumList( $restrict=0 )
	{
		if ( $restrict != 1 )
		{	
			//$jump_array[] = array( '-1', 'Make Root (Category)' );
		}
		else
		{
			$jump_array = array();
		}
		
		foreach( $this->forum_cache['root'] as $forum_data )
		{
			$jump_array[] = array( $forum_data['id'], $forum_data['name'] );
			
			$depth_guide = $this->depth_guide;
			
			if ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$jump_array[] = array( $forum_data['id'], $depth_guide.$forum_data['name'] );
					
					$jump_array = $this->forumsForumListInternal( $forum_data['id'], $jump_array, $depth_guide . $this->depth_guide );
				}
			}
		}
		
		return $jump_array;
	}
	
	/**
	 * Build Forum List Helper
	 *
	 * @access	private
	 * @param	integer	$root_id
	 * @param	array 	$jump_array
	 * @param	string	$depth_guide
	 * @return	array
	 **/
	private function forumsForumListInternal( $root_id, $jump_array=array(), $depth_guide="" )
	{
		if( isset($this->forum_cache[ $root_id ] ) AND  is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				$jump_array[] = array( $forum_data['id'], $depth_guide.$forum_data['name'] );
				
				$jump_array = $this->forumsForumListInternal( $forum_data['id'], $jump_array, $depth_guide . $this->depth_guide );
			}
		}

		return $jump_array;
	}
	
	/**
	 * Build Forum Data
	 *
	 * @access	public
	 * @return	array
	 **/
	public function adForumsForumData()
	{
		foreach( $this->forum_cache['root'] as $forum_data )
		{
			$forum_data['depthed_name'] = $forum_data['name'];
			$forum_data['root_forum']   = 1;
			
			$jump_array[ $forum_data['id'] ] = $forum_data;
			
			$depth_guide = $this->depth_guide;
			
			if ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$forum_data['depthed_name'] = $depth_guide.$forum_data['name'];
					
					$jump_array[ $forum_data['id'] ] = $forum_data;
					
					$jump_array = $this->forumsForumDataInternal( $forum_data['id'], $jump_array, $depth_guide . $this->depth_guide );
				}
			}
		}
		
		return $jump_array;
	}
	
	/**
	 * Build Forum Data Helper
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$jump_array
	 * @param	string	$depth_guide
	 * @return	array
	 **/
	public function forumsForumDataInternal( $root_id, $jump_array=array(), $depth_guide="" )
	{
		if ( isset( $this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				$forum_data['depthed_name'] = $depth_guide.$forum_data['name'];
					
				$jump_array[ $forum_data['id'] ] = $forum_data;
				
				$jump_array = $this->forumsForumDataInternal( $forum_data['id'], $jump_array, $depth_guide . $this->depth_guide );
			}
		}
		
		return $jump_array;
	}
}