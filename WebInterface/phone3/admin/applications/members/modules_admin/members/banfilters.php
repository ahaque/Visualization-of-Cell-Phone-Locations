<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Ban Filters
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Rev: 5041 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_members_members_banfilters extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */	
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_banfilters' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=banfilters';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=banfilters';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ) );
				
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{				
			case 'ban_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ban_manage' );
				$this->banAdd();
			break;
				
			case 'ban_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ban_remove' );
				$this->banDelete();
			break;
			
			default:
			case 'ban':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ban_manage' );
				$this->banOverview();
			break;			
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Add a new ban filter
	 *
	 * @access	public
	 * @return	void
	 */
	public function banAdd()
	{
		/* Error checking */
		if( ! $this->request['bantext'] )
		{
			$this->registry->output->global_message = $this->lang->words['ban_entersomething'];
			$this->banOverview();
			return;
		}
		
		/* Check for duplicate */
		$result = $this->DB->buildAndFetch( array( 
														'select' => '*', 
														'from'   => 'banfilters', 
														'where'  => "ban_content='{$this->request['bantext']}' and ban_type='{$this->request['bantype']}'" 
												)	 );
		
		if ( $result['ban_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['ban_dupe'];
			$this->banOverview();
			return;
		}
		
		/* Insert the new ban filter */
		$this->DB->insert( 'banfilters', array( 'ban_type' => $this->request['bantype'], 'ban_content' => trim( $this->request['bantext'] ), 'ban_date' => time() ) );
		
		/* Rebuild cacne and bounce */
		$this->rebuildBanCache();
		
		$this->registry->output->global_message = $this->lang->words['ban_added'];		
		$this->banOverview();
		
	}	
	
	/**
	 * Delete a ban filter
	 *
	 * @access	public
	 * @return	void
	 */
	public function banDelete()
	{
		/* INI */
		$ids = array();
		
		/* Loop through the request fields and find checked ban filters */
		foreach( $this->request as $key => $value )
		{
			if( preg_match( "/^id_(\d+)$/", $key, $match ) )
			{
				if( $this->request[ $match[0] ] )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		/* Clean the array */
		$ids = IPSLib::cleanIntArray( $ids );
		
		/* Delete any checked ban filters */
		if ( count( $ids ) )
		{
			$this->DB->delete( 'banfilters', 'ban_id IN(' . implode( ",", $ids ) . ')' );
			$this->DB->execute();
		}
		
		/* Rebuild the cache */
		$this->rebuildBanCache();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['ban_removed'];
		$this->banOverview();
	}	
	
	/**
	 * Displays the ban overview screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function banOverview()
	{
		/* INI */
		$ban = array();
		
		/* Get ban filters */
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters', 'order' => 'ban_date desc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$ban[ $r['ban_type'] ][ $r['ban_id'] ] = $r;
		}
		
		/* IPs */
		$ips = array();
		
		if( isset( $ban['ip'] ) AND is_array( $ban['ip'] ) AND count( $ban['ip'] ) )
		{
			foreach( $ban['ip'] as $entry )
			{
				$entry['_checkbox'] = "<input type='checkbox' name='id_{$entry['ban_id']}' value='1' />";
				$entry['_date']     =  $this->registry->class_localization->getDate( $entry['ban_date'], 'SHORT' );
				$ips[] = $entry;
			}
		}
		
		/* Emails */
		$emails = array();
		
		if( isset( $ban['email'] ) AND  is_array( $ban['email'] ) AND count( $ban['email'] ) )
		{
			foreach( $ban['email'] as $entry )
			{
				$entry['_checkbox'] = "<input type='checkbox' name='id_{$entry['ban_id']}' value='1' />";
				$entry['_date']     =  $this->registry->class_localization->getDate( $entry['ban_date'], 'SHORT' );
				$emails[] = $entry;
			}
		}
		
		/* Banned Names */
		$names = array();
		
		if( isset( $ban['name'] ) AND is_array( $ban['name'] ) AND count( $ban['name'] ) )
		{
			foreach( $ban['name'] as $entry )
			{
				$entry['_checkbox'] = "<input type='checkbox' name='id_{$entry['ban_id']}' value='1' />";
				$entry['_date']     =  $this->registry->class_localization->getDate( $entry['ban_date'], 'SHORT' );
				$names[] = $entry;
			}
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->banOverview( $ips, $emails, $names );
	}	
	
	/**
	 * Rebuilds the ban cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildBanCache()
	{
		/* INI */		
		$cache = array();
		
		/* Get the ban filters */
		$this->DB->build( array( 'select' => 'ban_content', 'from' => 'banfilters', 'where' => "ban_type='ip' AND ban_nocache=0" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$cache[] = $r['ban_content'];
		}

		/* Update the cache */
		$this->cache->setCache( 'banfilters', $cache, array( 'name' => 'banfilters', 'array' => 1, 'deletefirst' => 1 ) );
	}
}