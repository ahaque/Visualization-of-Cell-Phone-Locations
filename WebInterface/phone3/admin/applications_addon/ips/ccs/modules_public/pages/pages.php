<?php

/**
 * Invision Power Services
 * IP.CCS pages: displays the pages created in the ACP
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class public_ccs_pages_pages extends ipsCommand
{
	/**
	 * Temp output
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $output		= '';
	
	/**
	 * Page builder
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $pageBuilder;

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
		// Check online/offline first
		//-----------------------------------------

		if( !$this->settings['ccs_online'] )
		{
			$show		= false;
			
			if( $this->settings['ccs_offline_groups'] )
			{
				$groups		= explode( ',', $this->settings['ccs_offline_groups'] );
				$myGroups	= array( $this->memberData['member_group_id'] );
				$secondary	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
				$secondary	= explode( ',', $secondary );
				
				if( count($secondary) )
				{
					$myGroups	= array_merge( $myGroups, $secondary );
				}
				
				foreach( $myGroups as $groupId )
				{
					if( in_array( $groupId, $groups ) )
					{
						$show	= true;
						break;
					}
				}
			}
			
			if( !$show )
			{
				$this->registry->output->showError( $this->settings['ccs_offline_message'] );
			}
		}
		
		//-----------------------------------------
		// Load skin file
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
		$this->pageBuilder	= new pageBuilder( $this->registry );
		$this->pageBuilder->loadSkinFile();

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'redirect':
				$this->_redirector();
			break;

			case 'blockPreview':
				$this->_showBlockPreview();
			break;
			
			default:
				$this->_view();
			break;
		}
	}

	/**
	 * Show a preview of a block
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _showBlockPreview()
	{
		//-----------------------------------------
		// Check data
		//-----------------------------------------
		
		/*if( !$this->memberData['g_access_cp'] )
		{
			exit;
		}*/

		$id			= intval($this->request['id']);
		
		if( !$id )
		{
			exit;
		}
		
		//-----------------------------------------
		// Get block
		//-----------------------------------------
		
		$block		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		
		if( !$block['block_id'] )
		{
			exit;
		}

		//-----------------------------------------
		// Get block content
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/adminInterface.php' );
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
		$_class 	= "adminBlockHelper_" . $block['block_type'];
		
		$_block		= new $_class( $this->registry );
		$content	= $_block->getBlockContent( $block );

		$this->registry->output->addContent( $content );
		$this->registry->output->popUpWindow( $content );
	}
		
	/**
	 * Redirector: sends person to the page based on URL config
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _redirector()
	{
		$id	= intval($this->request['page']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['nopage_id_red'] );
		}
		
		$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );
		
		if( !$page['page_id'] )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$this->settings['ccs_default_error']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nopage_id_red'] );
			}
		}
		
		if( !$this->canView( $page ) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$this->settings['ccs_default_error']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nopage_id_red'] );
			}
		}
		
		$url	= $this->registry->ccsFunctions->returnPageUrl( $page );
		
		header("HTTP/1.0 301 Moved Permanently" );
		header("HTTP/1.1 301 Moved Permanently" );
		header( "Location: " . $url );
		exit;
	}
	
	/**
	 * View a page
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _view()
	{
		$folderName	= $this->registry->ccsFunctions->getFolder();
		$pageName	= $this->registry->ccsFunctions->getPageName();

		//-----------------------------------------
		// Sort out query where clause
		//-----------------------------------------
		
		$where	= array();
		$_where	= '';
		
		if( $pageName )
		{
			$where[]	= "page_seo_name='{$pageName}'";
			
			//-----------------------------------------
			// Even if not in a folder, need to make sure
			// page_folder='' in query
			//-----------------------------------------
			
			//if( $this->request['folder'] )
			//{
				$where[]	= "page_folder='{$folderName}'";
			//}
		}
		else if( $this->request['id'] )
		{
			$id			= intval($this->request['id']);
			$where[]	= "page_id=" . $id;
		}

		if( !count($where) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_error']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( 'no_page_specified', '10CCS1' );
			}
			else
			{
				$folderName	= '';
				$pageName	= $this->settings['ccs_default_error'];
			}
		}
		else
		{
			$_where	= implode( ' AND ', $where );
		}

		//-----------------------------------------
		// Get page
		//-----------------------------------------
		
		$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => $_where ) );
		
		if( !$page['page_id'] )
		{
			//-----------------------------------------
			// If we request site.com/pages/delete with no
			// trailing slash "delete" will be file instead
			// of folder - this could be valid, but let's
			// see if there is a delete folder + index file
			//-----------------------------------------
			
			if( !$this->request['id'] )
			{
				$where		= array();
				$where[]	= "page_seo_name='{$this->settings['ccs_default_page']}'";
				$where[]	= "page_folder='{$folderName}/{$pageName}'";

				if( count($where) )
				{
					$_where	= implode( ' AND ', $where );

					$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => $_where ) );

					$folderName	= "{$folderName}/{$pageName}";
					$pageName	= $this->settings['ccs_default_page'];
				}
			}
			
			//-----------------------------------------
			// Still no page...try error page
			//-----------------------------------------
			
			if( !$page['page_id'] )
			{
				$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_errorpage']}'" ) );
				
				if( !$page['page_id'] )
				{
					$this->registry->output->showError( 'no_page_specified', '10CCS2' );
				}
				else
				{
					$folderName	= '';
					$pageName	= $this->settings['ccs_default_errorpage'];
				}
			}
		}
		
		//-----------------------------------------
		// Check page viewing permissions
		//-----------------------------------------
		
		if( !$this->canView( $page ) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_error']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( 'no_page_permission', '10CCS3' );
			}
			else
			{
				$folderName	= '';
				$pageName	= $this->settings['ccs_default_errorpage'];
			}
		}

		//-----------------------------------------
		// Get page content and parse blocks
		//-----------------------------------------
		
		$this->output	= $this->_getPageContent( $page );

		//-----------------------------------------
		// Output
		//-----------------------------------------

		if( $page['page_meta_description'] )
		{
			$this->registry->output->addMetaTag( 'description', $page['page_meta_description'], false );
		}
		
		if( $page['page_meta_keywords'] )
		{
			$this->registry->output->addMetaTag( 'keywords', $page['page_meta_keywords'], false );
		}
		
		$title	= $page['page_name'];
		
		if( !$this->settings['ccs_online'] )
		{
			$title .= ' [' . $this->lang->words['ccs_offline_title'] . ']';
		}

		if( !$this->registry->output->getTitle() )
		{
			$this->registry->output->setTitle( $title );
		}
		
		//-----------------------------------------
		// Pass to CP output hander or output
		//-----------------------------------------

		if( !$page['page_ipb_wrapper'] )
		{
			$this->output	= $this->registry->output->templateHooks( $this->output );
			
			$this->registry->output->outputFormatClass->printHeader();
			
			print $this->registry->output->outputFormatClass->parseIPSTags( $this->output );
			
			$this->registry->output->outputFormatClass->finishUp();
			
			exit;
		}
		else
		{
			$this->settings['query_string_formatted']	= "app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;page={$page['page_id']}";
			
			$this->registry->output->addContent( $this->output );
			$this->registry->output->sendOutput();
		}
	}
	
	/**
	 * Parse out the block tags
	 *
	 * @access	protected
	 * @param	string		Unparsed output
	 * @return	string 		Parsed output
	 * @todo 	Finish this
	 */
	protected function _parseBlocks( $output )
	{
		preg_match_all( "#<\!--(.+?)_BLOCK-(\d+)-->#i", $output, $matches );

		if( count($matches[2]) )
		{
			$blocks		= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id IN(' . implode( ',', $matches[2] ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$blocks[]	= $r;
			}

			if( count($blocks) )
			{
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/adminInterface.php' );
				
				foreach( $blocks as $block )
				{
					require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
					$_class 	= "adminBlockHelper_" . $block['block_type'];
					
					$_block		= new $_class( $this->registry );
					$_content	= $_block->getBlockContent( $block );
					
					$output		= str_replace( "<!--" . strtoupper($block['block_type']) . "_BLOCK-" . $block['block_id'] . "-->", $_content, $output );
				}
			}
		}

		return $output;
	}

	/**
	 * Get the page content (verifies cache, etc.)
	 *
	 * @access	protected
	 * @param	array 		Page data
	 * @return	string		Page output
	 */
	protected function _getPageContent( $page )
	{
		//-----------------------------------------
		// Is this a different content type?
		//-----------------------------------------
		
		if( $page['page_content_type'] != 'page' )
		{
			switch( $page['page_content_type'] )
			{
				case 'css':
					@header( "Content-type: text/css" );
				break;
				
				case 'js':
					@header( "Content-type: application/x-javascript" );
				break;
			}

			$content	= $this->registry->output->outputFormatClass->parseIPSTags( $page['page_cache'] ? $page['page_cache'] : $page['page_content'] );
			
			preg_match_all( "#\{parse block=\"(.+?)\"\}#", $content, $matches );
			
			if( count($matches) )
			{
				foreach( $matches[1] as $index => $key )
				{
					$content = str_replace( $matches[0][ $index ], $this->pageBuilder->getBlock( $key ), $content );
				}
			}
			
			print $content;
						
			$this->registry->output->outputFormatClass->finishUp();
			exit;
		}
		
		//-----------------------------------------
		// Indefinite caching
		//-----------------------------------------

		if( $page['page_cache_ttl'] == '*' AND $page['page_cache'] )
		{
			return $page['page_cache'];
		}
		
		//-----------------------------------------
		// Caching enabled (verify not expired)
		//-----------------------------------------
		
		if( $page['page_cache_ttl'] > 0 )
		{
			if( ($page['page_cache_last'] + ( 60 * $page['page_cache_ttl'] )) > time() )
			{
				return $page['page_cache'];
			}
		}

		//-----------------------------------------
		// Page expired - get page builder
		//-----------------------------------------

		$content		= $this->pageBuilder->recachePage( $page );
		
		//-----------------------------------------
		// If caching enabled, update cache
		//-----------------------------------------
		
		if( $page['page_cache_ttl'] )
		{
			$this->DB->update( 'ccs_pages', array( 'page_cache' => $content, 'page_cache_last' => time() ), 'page_id=' . $page['page_id'] );
		}
		
		//-----------------------------------------
		// Return content
		//-----------------------------------------
		
		return $content;
	}
	
	/**
	 * Check page viewing permissions
	 *
	 * @access	protected
	 * @param	array 		Page information
	 * @return	bool		Can view or not
	 */
	public function canView( $page )
	{
		//-----------------------------------------
		// Open to all
		//-----------------------------------------
		
		if( $page['page_view_perms'] == '*' )
		{
			return true;
		}
		
		//-----------------------------------------
		// Figure out which perm masks to check
		//-----------------------------------------
		
		$_allowedMasks	= explode( ',', $page['page_view_perms'] );
		$_myMasks		= $this->member->perm_id_array;
		
		foreach( $_allowedMasks as $maskId )
		{
			if( in_array( $maskId, $_myMasks ) )
			{
				return true;
			}
		}
		
		return false;
	}
}
