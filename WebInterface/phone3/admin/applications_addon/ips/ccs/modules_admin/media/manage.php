<?php

/**
 * Invision Power Services
 * IP.CCS media manager file operations
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

class admin_ccs_media_manage extends ipsCommand
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
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// Make sure path is defined first...
		//-----------------------------------------
		
		if( !file_exists( DOC_IPS_ROOT_PATH . '/media_path.php' ) )
		{
			$this->registry->output->showError( $this->lang->words['missing_ccs_path'] );
		}
		
		require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );
		
		if( !defined('CCS_MEDIA') OR !CCS_MEDIA )
		{
			$this->registry->output->showError( $this->lang->words['no_media_path'] );
		}
		else if( !is_dir(CCS_MEDIA) )
		{
			$this->registry->output->showError( $this->lang->words['media_path_bad'] );
		}
		
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_mediamanager' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=media&amp;section=manage';
		$this->form_code_js	= $this->html->form_code_js	= 'module=media&section=manage';

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

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
			
			case 'delete':
				$this->_deleteFile();
			break;
			
			case 'upload':
				$this->_uploadFile();
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
	 * Upload a file
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _uploadFile()
	{
		//-----------------------------------------
		// Check path
		//-----------------------------------------
		
		$path	= urldecode( $this->request['in'] );
		$this->_checkPath( $path );
		
		//-----------------------------------------
		// Get upload class and do upload
		//-----------------------------------------
		
		require_once IPS_KERNEL_PATH . 'classUpload.php';
		$upload = new classUpload();
		
		$upload->upload_form_field	= 'FILE_UPLOAD';
		$upload->allowed_file_ext	= array( 'gif', 'bmp', 'png', 'jpg', 'jpeg', 'tiff' );
		$upload->out_file_dir		= $path;
		$upload->max_file_size		= '10000000';
		$upload->process();
		
		//-----------------------------------------
		// Successful?
		//-----------------------------------------
		
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{
				case 1:
					$this->registry->output->showError( $this->lang->words['upload_error_1'] );
				break;
				case 2:
					$this->registry->output->showError( $this->lang->words['upload_error_2'] );
				break;
				case 3:
					$this->registry->output->showError( $this->lang->words['upload_error_3'] );
				break;
				case 4:
					$this->registry->output->showError( $this->lang->words['upload_error_4'] );
				break;
				case 5:
					$this->registry->output->showError( $this->lang->words['upload_error_5'] );
				break;
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['file_uploaded'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . $path );
	}
	
	/**
	 * Delete a file
	 *
	 * @access	protected
	 * @param	string		Optional page to check
	 * @param	bool		Return instead of redirect
	 * @return	void
	 */
	protected function _deleteFile( $file='', $return=false )
	{
		$file	= $file ? $file : urldecode( $this->request['file'] );

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		$this->_checkPath( $file );
		
		if( file_exists( $file ) )
		{
			@unlink( $file );
		}
		
		if( $return )
		{
			return true;
		}
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['file_removed'];

		$pathBits	= explode( '/', $file );
		array_pop( $pathBits );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
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
		
		if( is_dir( $newPath ) )
		{
			$this->registry->output->showError( $this->lang->words['createfolder_exists'] );
		}
		
		//-----------------------------------------
		// Create the directory
		//-----------------------------------------
		
		@mkdir( $newPath, 0777 );
		
		$this->registry->output->global_message = $this->lang->words['media_folder_created'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . $this->request['parent'] );
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

		if( !is_dir( $current ) )
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

		if( is_dir( $newFolder ) )
		{
			$this->registry->output->showError( $this->lang->words['renamefolder_failed'] );
		}
		
		@rename( $current, $newFolder );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['media_rename_success'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=media&section=list&do=viewdir&dir=' . urlencode($pathNoFolder) );
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
			$startPoint	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );
			$ignorable	= array();
			$files		= array();
			$folders	= array();

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
				foreach( $this->request['pages'] as $file )
				{
					$folderBits		= explode( '/', urldecode( $file ) );
					$folderPiece	= array_pop($folderBits);
					$startPoint		= implode( '/', $folderBits );

					$files[]		= $file;
				}
			}
			
			$defaultPath	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );
			
			//-----------------------------------------
			// Get folders
			//-----------------------------------------
	
			$folders[ $defaultPath ]	= $defaultPath;

			foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $defaultPath ), RecursiveIteratorIterator::SELF_FIRST ) as $file )
			{
				if ( $file->getFilename() != '.' AND $file->getFilename() != '..' AND $file->isDir() )
				{
					$folders[ strtolower($file->getPathname()) ]	= str_replace( '\\', '/', $file->getPathname() );
				}
			}

			//-----------------------------------------
			// Show form
			//-----------------------------------------
			
			$this->registry->getClass('output')->html_main .= $this->html->moveToForm( $startPoint, $ignorable, $folders, $files );
			return;
		}

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
						$this->_moveFolder( urldecode($page), urldecode($this->request['moveto']), true );
					break;
					
					case 'delete':
						$this->_deleteFile( urldecode($page), true );
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
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . $this->request['return'] );
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
		
		if( file_exists( $newPath . '/' . $newFolder ) )
		{
			$this->registry->output->showError( $this->lang->words['moveitem_failed'] );
		}
		
		@rename( $path, $newPath . '/' . $newFolder );

		$this->registry->output->global_message = $this->lang->words['objects_moved'];
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		if( !$return )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . urlencode( $newPath ) );
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
		// Check permissions
		//-----------------------------------------
		
		$this->_checkPath( $path );
		
		//-----------------------------------------
		// Delete files and folders
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . '/classFileManagement.php' );
		$fileManagement	= new classFileManagement();
		$fileManagement->removeDirectory( $path );
		
		@mkdir( $path, 0777 );

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
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
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
		// Check permissions
		//-----------------------------------------
		
		$this->_checkPath( $path );
		
		//-----------------------------------------
		// Delete files and folders
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . '/classFileManagement.php' );
		$fileManagement	= new classFileManagement();
		$fileManagement->removeDirectory( $path );

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
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
	}
	
	/**
	 * Check the path is within our defined path
	 *
	 * @access	private
	 * @param	string		Path
	 * @return	mixed		True on success, displays error on failure
	 */
	private function _checkPath( $path )
	{
		$defaultPath	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );

		if( !$path OR strpos( $path, $defaultPath ) === false )
		{
			$this->registry->output->showError( $this->lang->words['path_defined_bad'] );
		}
		
		return true;
	}
}
