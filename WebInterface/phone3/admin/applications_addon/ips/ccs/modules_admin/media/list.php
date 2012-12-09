<?php

/**
 * Invision Power Services
 * IP.CCS media manager overview
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

class admin_ccs_media_list extends ipsCommand
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
	 * Icons
	 *
	 * @access	private
	 * @var		array 			Extension -> icon mapping
	 */	
	private $icons				= array(
										'music'		=> array( 'wav', 'mp3', 'midi' ),
										'movie'		=> array( 'swf', 'wmv', 'avi', 'mpg', 'mpeg' ),
										'image'		=> array( 'gif', 'bmp', 'png', 'jpg', 'jpeg', 'tiff' ),
										'pdf'		=> array( 'pdf', ),
										'zip'		=> array( 'zip', 'gz', 'rar', 'bz', 'ace', 'jar' ),
										'code'		=> array( 'php', 'html', 'htm', 'css', 'js', 'pl', 'cgi', 'xml' ),
										);

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
		
		$this->form_code	= $this->html->form_code	= 'module=media&amp;section=list';
		$this->form_code_js	= $this->html->form_code_js	= 'module=media&section=list';

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

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

		$defaultPath	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );
		$path			= $path ? $path : $defaultPath;
		$files			= array();
		$folders		= array();
		$dotdot			= array();

		if( !$path OR strpos( $path, $defaultPath ) === false )
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
							'last_modified'		=> 0,
							'path'				=> '..',
							'full_path'			=> $full_path,
							'name'				=> '..',
							'size'				=> 0,
							);
		}
		
		//-----------------------------------------
		// Get folders
		//-----------------------------------------

		foreach( new DirectoryIterator( $path ) as $file )
		{
			if ( ! $file->isDot() AND $file->isDir() AND substr( $file->getFilename(), 0, 1 ) != '.' )
			{
				$folders[ strtolower($file->getPathname()) ]	= array(
																	'last_modified'		=> $file->getMTime(),
																	'path'				=> $file->getPath(),
																	'full_path'			=> str_replace( '\\', '/', $file->getPathname() ),
																	'name'				=> $file->getFilename(),
																	'size'				=> $file->getSize(),
																	'icon'				=> 'folder',
																	);
			}
			else if ( ! $file->isDot() AND $file->isFile() AND substr( $file->getFilename(), 0, 1 ) != '.' )
			{
				$icon		= 'file.png';
				$bits		= explode( '.', $file->getFilename() );
				$extension	= strtolower( array_pop($bits) );
				
				foreach( $this->icons as $png => $types )
				{
					if( in_array( $extension, $types ) )
					{
						$icon	= $png . '.png';
						break;
					}
				}

				$files[ strtolower($file->getFilename()) ]	= array(
																	'last_modified'		=> $file->getMTime(),
																	'path'				=> $file->getPath(),
																	'full_path'			=> str_replace( '\\', '/', $file->getPathname() ),
																	'name'				=> $file->getFilename(),
																	'size'				=> $file->getSize(),
																	'icon'				=> $icon,
																	);
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

		$this->registry->output->html .= $this->html->overview( $path, $folders, $files );
	}
}
