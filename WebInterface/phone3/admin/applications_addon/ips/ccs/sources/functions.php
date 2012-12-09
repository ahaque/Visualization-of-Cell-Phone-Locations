<?php

/**
 * Invision Power Services
 * IP.CCS miscellaneous functions
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

class ccsFunctions
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	/**#@-*/
	
	/**
	 * Folder/page checked.  We set a property
	 * to indicate this in case there actually
	 * is no folder/page so we don't do the work twice.
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $folderCheckComplete	= false;
	
	/**
	 * Requested folder
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $folder	= '';
	
	/**
	 * Requested page SEO name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $page		= '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
	}
	
	/**
	 * Retrieve folder
	 *
	 * @access	public
	 * @return	string		Requested folder name
	 */
	public function getFolder()
	{
		if( $this->folderCheckComplete )
		{
			return $this->folder;
		}
		
		$this->_getPageAndFolder();

		return $this->folder;
	}
	
	/**
	 * Retrieve page name
	 *
	 * @access	public
	 * @return	string		Requested page name
	 */
	public function getPageName()
	{
		if( $this->folderCheckComplete )
		{
			return $this->page;
		}
		
		$this->_getPageAndFolder();
		
		return $this->page;
	}
	
	/**
	 * Sort out page and folder
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _getPageAndFolder()
	{
		$page	= '';
		$folder	= '';
		
		//-----------------------------------------
		// What page?
		//-----------------------------------------

		if( !$this->request['page'] AND !$this->request['id'] )
		{
			$page	= $this->settings['ccs_default_page'];
		}
		else
		{
			$page	= $this->request['page'];
		}

		//-----------------------------------------
		// Fix folder
		//-----------------------------------------
		
		if( $this->request['folder'] )
		{
			$_default		= str_replace( 'http://www.', 'http://', $this->settings['ccs_root_url'] );
			$_default		= substr( $_default, -1 ) == '/' ? $_default : $_default . '/';
			$_reconstructed	= ( $_SERVER['HTTPS'] ? "https://" : "http://" ) . str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) . ( $_SERVER['REQUEST_URI']  ? $_SERVER['REQUEST_URI']  : @getenv('REQUEST_URI') );
			$_path			= str_replace( $_default, '', $_reconstructed );

			//-----------------------------------------
			// Accessing URL dynamically (thru IPB)?
			//-----------------------------------------
			
			if( strpos( $_path, 'app=ccs&' ) !== false )
			{
				$_folder	= ( strpos( urldecode($this->request['folder']), '/' ) === 0 ) ? substr( urldecode($this->request['folder']), 1 ) : urldecode($this->request['folder']);
				
				$_path	= $_folder . '/' . $this->request['page'];
			}
			
			//-----------------------------------------
			// Accessing through IPB friendly urls?
			//-----------------------------------------
			
			if( strpos( $_reconstructed, $this->settings['board_url'] ) !== false )
			{
				$_path = preg_replace( "#^page/(.+?)#", "\\1", $_path );
			}
			
			//-----------------------------------------
			// Accessing through main gateway
			//-----------------------------------------
			
			if( strpos( $_path, $this->settings['ccs_root_filename'] . '/' ) !== false )
			{
				$_path	= substr( $_path, ( strpos( $_path, $this->settings['ccs_root_filename'] . '/' ) + ( strlen($this->settings['ccs_root_filename']) + 1 ) ) );
			}

			$uriBits	= explode( '/', $_path );
			$myFolder	= '';
			
			if( count($uriBits) > 1 )
			{
				array_pop($uriBits); // Get rid of filename
				$myFolder	= count($uriBits) ? '/' . implode( '/', $uriBits ) : '';
				$myFolder	= ( $myFolder == '/' ) ? '' : $myFolder;
			}
			
			$folder	= $myFolder;
		}

		$this->folder	= $folder;
		$this->page		= urldecode($page);
		
		$this->folderCheckComplete	= true;
	}

	/**
	 * Return the proper page url
	 *
	 * @access	public
	 * @param	array 	Page data
	 * @return	string	Page URL based on enabled settings
	 */
	public function returnPageUrl( $page=array() )
	{
		$url	= $this->settings['ccs_root_url'];
		
		if( $url )
		{
			if( !$this->settings['ccs_mod_rewrite'] )
			{
				if( substr( $url, -1 ) != '/' )
				{
					$url .= '/';
				}
			
				$url .= $this->settings['ccs_root_filename'];
			}
			else
			{
				$url	= rtrim( $url, "/" );
			}
			
			if( $page['page_folder'] )
			{
				$url .= $page['page_folder'] . '/';
			}
			else
			{
				$url	.= '/';
			}
			
			$url	.= $page['page_seo_name'];
		}
		else if( $this->settings['use_friendly_urls'] )
		{
			$url	= $this->registry->output->formatUrl( $this->settings['board_url'] . '/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;folder=' . $page['page_folder'] . '&amp;id=' . $page['page_id'], $page['page_seo_name'], 'page' );
		}
		else
		{
			$url	= $this->settings['board_url'] . '/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;id=' . $page['page_id'];
		}
		
		return $url;
	}
}