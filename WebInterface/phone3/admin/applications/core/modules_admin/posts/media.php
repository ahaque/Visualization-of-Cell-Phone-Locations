<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * BBCode Media Tag Management
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 3887 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_posts_media extends ipsCommand 
{
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
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_bbcode');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=posts&amp;section=media';
		$this->form_code_js	= $this->html->form_code_js	= 'module=posts&section=media';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_posts' ) );

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'do_del':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_delete' );
				$this->_mediaTagDelete();
			break;
			
			case 'form_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagForm( 'add' );
			break;
			
			case 'form_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagForm( 'edit' );
			break;
			
			case 'domediatagadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagSave( 'add' );
			break;
			
			case 'domediatagedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagSave( 'edit' );
			break;
			
			case 'mediatag_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagExport();
			break;

			case 'mediatag_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagImport();
			break;
		
			case 'index':
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Import a mediaTag XML file
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _mediaTagImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml( 'mediatag.xml' );

		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['m_upload_failed'];
			$this->_mediaTagIndex();
			return;
		}
		
		$this->doMediaImport( $content );
                    
		$this->registry->output->global_message = $this->lang->words['m_upload_complete'];
		
		$this->_mediaTagIndex();
	}
	
	/**
	 * Abstracted import routine for installer
	 *
	 * @access	public
	 * @param	string		XML file content
	 * @return	void
	 */
	public function doMediaImport( $content )
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$tags = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bbcode_mediatag' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tags[ $r['mediatag_name'] ] = $r['mediatag_id'];
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->fetchElements('mediatag') as $mediatag )
		{
			$entry  = $xml->fetchElementsFromRecord( $mediatag );

			$name		= $entry['mediatag_name'];
			$match		= $entry['mediatag_match'];
			$replace	= $entry['mediatag_replace'];
			
			$array 		= array(
								'mediatag_name'		=> $name,
								'mediatag_match'	=> $match,
								'mediatag_replace'	=> $replace
								);

			if ( $tags[ $name ] )
			{
				$this->DB->update( 'bbcode_mediatag', $array, "mediatag_id=" . $tags[ $name ] );

				continue;
			}
			
			if ( $name )
			{
				$this->DB->insert( 'bbcode_mediatag', $array );
			}
		}
		
		$this->recacheMediaTag();
	}
		
	/**
	 * Export a mediaTag XML file
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _mediaTagExport()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'mediatagexport' );
		$xml->addElement( 'mediataggroup', 'mediatagexport' );

		$select = array( 'select' => '*', 'from' => 'bbcode_mediatag' );
		
		if( $this->request['id'] )
		{
			$select['where'] = 'mediatag_id=' . intval($this->request['id']);
		}
		
		$this->DB->build( $select );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$xml->addElementAsRecord( 'mediataggroup', 'mediatag', $r );
		}
		
		$xmlData = $xml->fetchDocument();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->registry->output->showDownload( $xmlData, 'mediatag.xml', '', 0 );
	}
	
	/**
	 * Delete a custom media tag
	 *
	 * @access	private
	 * @return	void
	 */	
	private function _mediaTagDelete()
	{
		/* ID */
		$id = intval( $this->request['id'] );
		
		/* Remove */
		$this->DB->delete( 'bbcode_mediatag', "mediatag_id={$id}" );
		
		$this->recacheMediaTag();
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['m_replace_removed'];
	 	$this->_mediaTagIndex();
	}
	
	/**
	 * Save changes to a custom media tag
	 *
	 * @access	private
	 * @param	string [$type='add']
	 * @return	void
	 */
	private function _mediaTagSave( $type='add' )
	{
		/* INI */
		$errors = array();
		
		/* Check input */
		if( ! $this->request['mediatag_name'] )
		{
			$errors[] = $this->lang->words['m_error_name'];
		}
		
		if( ! $this->request['mediatag_match'] )
		{
			$errors[] = $this->lang->words['m_error_match'];
		}
		
		if( ! $this->request['mediatag_replace'] )
		{
			$errors[] = $this->lang->words['m_error_replace'];
		}
		
		if( count( $errors ) )
		{
			$this->_mediaTagForm( $type, $errors );
			return;
		}
	
	 	/* Data */
	 	$data = array( 	 			
	 					'mediatag_name'    => $this->request['mediatag_name'],
	 					'mediatag_match'   => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_match'] ) ) ) ), ',' ),
	 					'mediatag_replace' => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_replace'] ) ) ) ), ',' ),
	 				);
	 	
	 	/* Check the type */
	 	if( $type == 'add' )
	 	{
	 		/* Insert the record */
	 		$this->DB->insert( 'bbcode_mediatag', $data );
	 		
	 		/* Update cache */
	 		$this->recacheMediaTag();
	 		
	 		/* All done */
			$this->registry->output->doneScreen( sprintf( $this->lang->words['m_tag_added'], $data['mediatag_name']), $this->lang->words['m_manager'], "{$this->form_code}&amp;do=overview", 'redirect' );
	 	}
	 	else
	 	{
	 		/* ID */
	 		$id = intval( $this->request['id'] );
	 		
	 		/* Update */
	 		$this->DB->update( 'bbcode_mediatag', $data, "mediatag_id={$id}" );
	 		
	 		/* Recache */
	 		$this->recacheMediaTag();
	 		
	 		/* Done and done */
	 		$this->registry->output->doneScreen( sprintf( $this->lang->words['m_tag_updated'], $data['mediatag_name']), $this->lang->words['m_manager'], "{$this->form_code}&amp;do=overview", 'redirect' );
	 	}
	 
	}	
	
	/**
	 * Show the mediatag add/edit form
	 *
	 * @access	private
	 * @param	string	[$type='add']
	 * @param	array 	[Optional] array of errors
	 * @return	void
	 */
	private function _mediaTagForm( $type='add', $errors=array() )
	{
		/* Check form type */
		if( $type == 'add' )
		{
			/* Data */
			$data   = array(
								'mediatag_name'    => $this->request['mediatag_name'],
			 					'mediatag_match'   => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_match'] ) ) ) ), ',' ),
			 					'mediatag_replace' => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_replace'] ) ) ) ), ',' ),
							);
		}
		else
		{
			/* Data */
			$id		= intval( $this->request['id'] );
			$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bbcode_mediatag', 'where' => "mediatag_id={$id}" ) );
			
			/* Set Defaults */
			$data['mediatag_name']    = ( isset( $this->request['mediatag_name']    ) && $this->request['mediatag_name']    ) ? $this->request['mediatag_name']    : $data['mediatag_name'];
			$data['mediatag_match']   = ( isset( $this->request['mediatag_match']   ) && $this->request['mediatag_match']   ) ? $this->request['mediatag_match']   : $data['mediatag_match'];
			$data['mediatag_replace'] = ( isset( $this->request['mediatag_replace'] ) && $this->request['mediatag_replace'] ) ? $this->request['mediatag_replace'] : $data['mediatag_replace'];
		}
		
		/* Setup Form */
		$this->registry->output->html .= $this->html->mediaTagForm( $type, $data, $errors );
	}	
	
	/**
	 * List all the the current media tag types
	 *
	 * @access	private
	 * @return	void
	 */
	private function _mediaTagIndex()
	{
		/* Query Bookmarks */
		$this->DB->build( array(
												'select' => '*',
												'from'   => 'bbcode_mediatag',
												'order'  => 'mediatag_name ASC',
										)	);
		$this->DB->execute();
		
		$bbcode_rows = "";

		/* List the bookmarks */
		while( $r = $this->DB->fetch() )
		{
			$bbcode_rows .= $this->html->mediaTagRow( $r );
		}
		
		/* End table and output */
        $this->registry->output->html .= $this->html->mediaTagWrapper( $bbcode_rows );
	}
	
	/**
	 * Recache the mediatag config
	 *
	 * @access	public
	 * @return	void
	 */
	public function recacheMediaTag()
	{
		/* Query the tags */
		$this->DB->build( array( 'select' => '*', 'from' => 'bbcode_mediatag' ) );
		$this->DB->execute();

		$media_config = array();

		while( $r = $this->DB->fetch() )
		{
			$media_config[$r['mediatag_name']] = array(
														'match'   => preg_replace( "#{[0-9]}#", "(.*?)", str_replace( '.', '\.', str_replace( '?', '\?', $r['mediatag_match'] ) ) ),
														'replace' => $r['mediatag_replace'],
													);
		}

		/* Save to cache */
		$this->cache->setCache( 'mediatag', $media_config, array( 'deletefirst' => 1, 'array' => 1 ) );
	}
}