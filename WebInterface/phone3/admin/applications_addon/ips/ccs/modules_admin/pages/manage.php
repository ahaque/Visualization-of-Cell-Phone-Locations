<?php

/**
 * Invision Power Services
 * IP.CCS file manager file operations
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

class admin_ccs_pages_manage extends ipsCommand
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
	private $folders			= array( '/' );

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
		
		$this->form_code	= $this->html->form_code	= 'module=pages&amp;section=manage';
		$this->form_code_js	= $this->html->form_code_js	= 'module=pages&section=manage';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		//-----------------------------------------
		// Get existing folders
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->folders[]	= $r['folder_path'];
		}

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'multi':
				$this->_multiAction();
			break;
			
			case 'deleteFolder':
				$this->_deleteFolder( urldecode( $this->request['dir'] ) );
			break;
			
			case 'emptyFolder':
				$this->_emptyFolder( urldecode( $this->request['dir'] ) );
			break;

			case 'doCreateFolder':
				$this->_doCreateFolder();
			break;
			
			case 'doRenameFolder':
				$this->_doRenameFolder();
			break;
			
			case 'editFolder':
				$this->_directoryForm( 'edit' );
			break;
			
			case 'createFolder':
			default:
				$this->_directoryForm( 'add' );
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Create a directory
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _doCreateFolder()
	{
		$path		= urldecode($this->request['parent']);
		$folder		= str_replace( '/', '_', $this->request['folder_name'] );
		$newPath	= str_replace( '//', '/', $path . '/' . $folder );

		//-----------------------------------------
		// Make sure this folder doesn't already exist
		//-----------------------------------------
		
		if( in_array( $newPath, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['createfolder_exists'] );
		}
		
		//-----------------------------------------
		// Create the directory
		//-----------------------------------------
		
		$this->DB->insert( 'ccs_folders', array( 'folder_path' => $newPath, 'last_modified' => time() ) );
		
		$this->registry->output->global_message = $this->lang->words['media_folder_created'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . $this->request['parent'] );
	}
	
	/**
	 * Rename a directory
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _doRenameFolder()
	{
		$current	= urldecode($this->request['current']);
		$newName	= str_replace( '/', '_', $this->request['folder_name'] );

		//-----------------------------------------
		// Make sure this folder exists
		//-----------------------------------------

		if( !in_array( $current, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['editfolder_not_exists'] );
		}
		
		//-----------------------------------------
		// Sort out the proper path stuff
		//-----------------------------------------
		
		$paths			= explode( '/', $current );
		$existing		= array_pop( $paths );
		$pathNoFolder	= implode( '/', $paths );
		$newFolder		= $pathNoFolder . '/' . $newName;

		//-----------------------------------------
		// Make sure new folder does not exists
		//-----------------------------------------

		if( in_array( $newFolder, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['renamefolder_failed'] );
		}
		
		$this->DB->update( 'ccs_folders', array( 'folder_path' => $newFolder, 'last_modified' => time() ), "folder_path='{$current}'" );
		$this->DB->update( 'ccs_pages', array( 'page_folder' => $newFolder ), "page_folder='{$current}'" );
		
		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $current ) === 0 AND $folder != $current )
			{
				$newFolderBit	= str_replace( $current, $newFolder, $folder );
				
				$this->DB->update( 'ccs_folders', array( 'folder_path' => $newFolderBit ), "folder_path='{$folder}'" );
				$this->DB->update( 'ccs_pages', array( 'page_folder' => $newFolderBit ), "page_folder='{$folder}'" );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['media_rename_success'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode($pathNoFolder) );
	}
	
	/**
	 * Create/edit a directory form
	 *
	 * @access	protected
	 * @param	string		Add/edit
	 * @return	void
	 */
	protected function _directoryForm( $type )
	{
		$this->registry->getClass('output')->html_main .= $this->html->directoryForm( $type );
	}

	/**
	 * Perform action on multiple files/folders
	 *
	 * @access	protected
	 * @return	void
	 * @todo 	Fix this
	 */
	protected function _multiAction()
	{
		if( $this->request['action'] == 'move' AND !$this->request['moveto'] )
		{
			$startPoint	= '/';
			$ignorable	= array();
			$pages		= array();

			if( is_array($this->request['folders']) AND count($this->request['folders']) )
			{
				foreach( $this->request['folders'] as $folder )
				{
					$folderBits		= explode( '/', urldecode( $folder ) );
					$folderPiece	= array_pop($folderBits);
					$startPoint		= implode( '/', $folderBits );
					
					$ignorable[]	= urldecode( $folder );
				}
			}
			else if( !is_array($this->request['pages']) AND !count($this->request['pages']) )
			{
				$this->registry->output->showError( $this->lang->words['nothing_to_move'] );
			}
			
			if( is_array($this->request['pages']) AND count($this->request['pages']) )
			{
				$this->DB->build( array( 'select' => 'page_id,page_seo_name,page_folder', 'from' => 'ccs_pages', 'where' => "page_id IN(" . implode( ',', $this->request['pages'] ) . ")" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$ignorable[]	= $r['page_folder'] ? $r['page_folder'] : '/';
					$pages[]		= $r;
				}
			}

			$this->registry->getClass('output')->html_main .= $this->html->moveToForm( $startPoint, $ignorable, $this->folders, $pages );
			return;
		}
		
		$this->request['moveto'] = $this->request['moveto'] == '/' ? '' : $this->request['moveto'];

		if( is_array($this->request['folders']) AND count($this->request['folders']) )
		{
			foreach( $this->request['folders'] as $folder )
			{
				switch( $this->request['action'] )
				{
					case 'move':
						$this->_moveFolder( urldecode($folder), urldecode($this->request['moveto']), true );
					break;
					
					case 'delete':
						$this->_deleteFolder( urldecode($folder), true );
					break;
				}
			}
		}
		
		if( is_array($this->request['pages']) AND count($this->request['pages']) )
		{
			foreach( $this->request['pages'] as $page )
			{
				switch( $this->request['action'] )
				{
					case 'move':
						$this->DB->update( 'ccs_pages', array( 'page_folder' => urldecode($this->request['moveto']) ), 'page_id=' . intval($page) );
					break;
					
					case 'delete':
						$this->DB->delete( 'ccs_pages', 'page_id=' . intval($page) );
					break;
				}
			}
		}

		switch( $this->request['action'] )
		{
			case 'move':
				$this->registry->output->global_message = $this->lang->words['objects_moved'];
			break;
			
			case 'delete':
				$this->registry->output->global_message = $this->lang->words['objects_deleted'];
			break;
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . $this->request['return'] );
	}
	
	/**
	 * Move a file or folder
	 *
	 * @access	protected
	 * @param	string		Path (could be file or folder)
	 * @param	string		New folder
	 * @param	bool		Return instead of output
	 * @return	void
	 */
	protected function _moveFolder( $path='', $newPath='', $return=true )
	{
		//-----------------------------------------
		// Sort some path schtuff
		//-----------------------------------------

		$pathBits	= explode( '/', $path );
		$newFolder	= array_pop( $pathBits );
		
		//-----------------------------------------
		// Rename folder
		//-----------------------------------------
		
		if( in_array( $newPath . '/' . $newFolder, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['moveitem_failed'] );
		}
		
		$this->DB->update( 'ccs_folders', array( 'folder_path' => $newPath . '/' . $newFolder ), "folder_path='{$path}'" );
		$this->DB->update( 'ccs_pages', array( 'page_folder' => $newPath . '/' . $newFolder ), "page_folder='{$path}'" );
		
		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $path ) AND $folder != $path )
			{
				$newFolderBit	= str_replace( $path, $newPath . '/' . $newFolder, $folder );
				
				$this->DB->update( 'ccs_folders', array( 'folder_path' => $newFolderBit ), "folder_path='{$folder}'" );
				$this->DB->update( 'ccs_pages', array( 'page_folder' => $newFolderBit ), "page_folder='{$folder}'" );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['objects_moved'];
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		if( !$return )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode( $newPath ) );
		}
	}
	
	/**
	 * Empty a folder
	 *
	 * @access	protected
	 * @param	string		Path
	 * @param	bool		Return instead of output
	 * @return	void
	 */
	protected function _emptyFolder( $path='', $return=false )
	{
		//-----------------------------------------
		// Empty files and folders
		//-----------------------------------------

		$this->DB->delete( 'ccs_folders', "folder_path LIKE '{$path}/%'" );
		$this->DB->delete( 'ccs_pages', "page_folder LIKE '{$path}/%'" );

		if( $return )
		{
			return true;
		}

		$this->registry->output->global_message = $this->lang->words['folder_emptied'];
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		$pathBits	= explode( '/', $folder );
		array_pop( $pathBits );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
	}
	
	/**
	 * Delete a folder
	 *
	 * @access	protected
	 * @param	string		Path
	 * @param	bool		Return instead of output
	 * @return	void
	 */
	protected function _deleteFolder( $path='', $return=false )
	{
		//-----------------------------------------
		// Delete files and folders
		//-----------------------------------------

		$this->DB->delete( 'ccs_folders', "folder_path='{$path}'" );
		$this->DB->delete( 'ccs_pages', "page_folder='{$path}'" );
		
		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $path ) === 0 AND $folder != $path )
			{
				$this->DB->delete( 'ccs_folders', "folder_path='{$folder}'" );
				$this->DB->delete( 'ccs_pages', "page_folder='{$folder}'" );
			}
		}
		
		if( $return )
		{
			return true;
		}

		$this->registry->output->global_message = $this->lang->words['folder_removed'];
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		$pathBits	= explode( '/', $folder );
		array_pop( $pathBits );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
	}
}
