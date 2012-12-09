<?php

/**
 * Invision Power Services
 * IP.CCS file manager overview
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

class admin_ccs_pages_list extends ipsCommand
{
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
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */	
	private $html;

	/**
	 * Folders
	 *
	 * @access	private
	 * @var		array 			Folders
	 */	
	private $folders			= array();

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
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_filemanager' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=pages&amp;section=list';
		$this->form_code_js	= $this->html->form_code_js	= 'module=pages&section=list';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );
		
		//-----------------------------------------
		// Setting removed from ACP
		//-----------------------------------------
		
		$this->settings['ccs_prune_pages']	= 6;

		//-----------------------------------------
		// Get existing folders
		//-----------------------------------------
		
		$this->folders[ '/' ]	= time();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->folders[ $r['folder_path'] ]	= $r['last_modified'];
		}

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
				$this->_mainScreen();
			break;
			
			case 'viewdir':
				$this->_mainScreen( urldecode( $this->request['dir'] ) );
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Show main overview screen
	 *
	 * @access	protected
	 * @param	string		Directory path
	 * @return	void
	 */
	protected function _mainScreen( $path='' )
	{
		//-----------------------------------------
		// Init some vars
		//-----------------------------------------

		$defaultPath	= '/';
		$path			= $path ? $path : $defaultPath;
		$files			= array();
		$folders		= array();
		$dotdot			= array();

		if( !$path OR !array_key_exists( $path, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['path_defined_bad'] );
		}
		
		//-----------------------------------------
		// Get ../
		//-----------------------------------------
		
		if( $path != $defaultPath )
		{
			$pathBits	= explode( '/', $path );
			array_pop( $pathBits );
			$full_path	= implode( '/', $pathBits );
			
			//-----------------------------------------
			// Got our folders
			//-----------------------------------------
			
			$dotdot[]	= array(
							'last_modified'		=> $this->folders[ $full_path ],
							'path'				=> '..',
							'full_path'			=> $full_path,
							'name'				=> '..',
							'size'				=> 0,
							);
		}
		
		//-----------------------------------------
		// Get folders
		//-----------------------------------------

		$ourFolderBits	= $path == '/' ? array( '/' ) : explode( '/', $path );
		array_pop( $ourFolderBits );

		foreach( $this->folders as $folderPath => $time )
		{
			$folderBits	= explode( '/', $folderPath );
			array_pop( $folderBits );
			
			if( count($folderBits) != count($ourFolderBits) + 1 )
			{
				continue;
			}
			
			if( strpos( $folderPath, $path ) !== false AND $folderPath != $path )
			{
				$folderBits	= explode( '/', $folderPath );
				$folderName	= array_pop( $folderBits );
				
				$folders[ strtolower($folderPath) ]	= array(
															'last_modified'		=> $time,
															'path'				=> $folderName,
															'full_path'			=> $folderPath,
															'name'				=> $folderName,
															'size'				=> 0,
															'icon'				=> 'folder',
															);
			}
		}

		//-----------------------------------------
		// Get any pages in this folder
		//-----------------------------------------
		
		$shortFolder	= rtrim( $path, '/' );

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='" . $shortFolder . "'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$files[ strtolower($r['page_seo_name']) ]	= array(
																'last_modified'		=> $r['page_last_edited'],
																'path'				=> $r['page_folder'],
																'full_path'			=> $r['page_folder'] . '/' . $r['page_seo_name'],
																'name'				=> $r['page_seo_name'],
																'size'				=> strlen($r['page_content']),
																'icon'				=> $r['page_content_type'],
																'page_id'			=> $r['page_id'],
																'is_page'			=> true,
																);
		}
		
		//-----------------------------------------
		// Get unfinished pages if we're on index
		//-----------------------------------------
		
		$unfinished	= array();
		$toRemove	= array();

		if( $path == $defaultPath )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_wizard', 'order' => 'wizard_name ASC' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				//-----------------------------------------
				// If step 0 and older than 30 seconds
				//-----------------------------------------
				
				if( $r['wizard_step'] == 0 AND $r['wizard_started'] < (time() - 30) )
				{
					$toRemove[]	= $r['wizard_id'];
					continue;
				}
				else if( $this->settings['ccs_prune_pages'] AND $r['wizard_started'] < ( time() - ( $this->settings['ccs_prune_pages'] * 60 * 60 ) ) )
				{
					$toRemove[]	= $r['wizard_id'];
					continue;
				}
	
				$unfinished[]	= $r;
			}
			
			//-----------------------------------------
			// Remove any stale blocks
			//-----------------------------------------
			
			if( count($toRemove) )
			{
				$this->DB->delete( 'ccs_page_wizard', "wizard_id IN('" . implode( "','", $toRemove ) . "')" );
			}
		}
		
		//-----------------------------------------
		// Sort in a "natural" order
		//-----------------------------------------
		
		ksort( $folders, SORT_STRING );
		ksort( $files, SORT_STRING );

		//-----------------------------------------
		// Add in dotdot if it's there
		//-----------------------------------------
		
		$folders	= array_merge( $dotdot, $folders );
		
		//-----------------------------------------
		// Extra navigation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=viewdir&amp;dir=' . urlencode( $defaultPath ), '/' );
		
		if( $path != $defaultPath )
		{
			//-----------------------------------------
			// Get rid of base path in our current path
			//-----------------------------------------
			
			$navPath	= str_replace( $defaultPath . '/', '', $path );
			
			//-----------------------------------------
			// Get folders
			//-----------------------------------------
			
			$pathBits	= explode( '/', $navPath );

			//-----------------------------------------
			// Get nav...
			//-----------------------------------------
			
			$bitsSoFar	= '';
			
			if( count($pathBits) )
			{
				foreach( $pathBits as $bit )
				{
					$thisPath	= $defaultPath . $bitsSoFar . '/' . $bit;
					$bitsSoFar	.= '/' . $bit;
					
					$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=viewdir&amp;dir=' . urlencode( $thisPath ), '/' . $bit );
				}
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->overview( $path, $folders, $files, $unfinished );
	}
}
