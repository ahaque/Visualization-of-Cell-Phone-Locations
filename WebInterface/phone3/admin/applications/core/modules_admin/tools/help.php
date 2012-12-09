<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Help Files
 * Last Updated: $LastChangedDate: 2009-04-27 06:57:24 -0400 (Mon, 27 Apr 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4553 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_tools_help extends ipsCommand
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
		/* Load lang and skin */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_help_files' );
				
		/* URLs */
		$this->form_code    = $this->html->form_code    = 'module=tools&amp;section=help';
		$this->form_code_js = $this->html->form_code_js = 'module=tools&section=help';
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->helpFileForm( 'edit' );
			break;
			
			case 'new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->helpFileForm( 'new' );
			break;
			
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->handleHelpFileForm( 'edit' );
			break;
				
			case 'doreorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->helpFilesReorder();
			break;				
				
			case 'donew':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->handleHelpFileForm( 'new' );
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_remove' );
				$this->helpFileRemove();
			break;
				
			case 'exportXml':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->helpFilesXMLExport();
			break;
			
			case 'importXml':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->helpFilesXMLImport();
			break;
			
			case 'help_overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'help_manage' );
				$this->helpFilesList();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Import help files XML
	 *
	 * @access	public
	 * @return	void
	 */
	public function helpFilesXMLImport()
	{
		/* INIT */
		 $messages = array();
		
		/* Loop through all the applications */
		foreach( $this->registry->getApplications() as $app => $__data )
		{
			$done       = $this->helpFilesXMLImport_app( $app );
			$messages[] = sprintf( $this->lang->words['h_import_added'], $app, $done['added'], $done['updated'] );
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['help'][ $app ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['h_imported'] . "<br />" . implode( "<br />",  $messages );
		$this->helpFilesList();
	}
	
	/**
	 * Import help files XML helper. Abstracted so
	 * it can be used outside of this file.
	 *
	 * @access	public
	 * @param	string		App Directory
	 * @param	bool		Allow overwrite. If FALSE, it will not update.
	 * @return	array		Number of items added / updated
	 */
	public function helpFilesXMLImport_app( $app, $overwrite=TRUE )
	{
		/* INIT */
		$file      = IPSLib::getAppDir( $app ) . '/xml/' . $app . '_help.xml';
		$processed = array( 'added' => 0, 'updated' => 0 );
		
		/* Got anything to import? */
		if ( file_exists( $file ) )
		{
			require_once( IPS_KERNEL_PATH.'classXML.php' );
			
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->load( $file );

			foreach( $xml->fetchElements('row') as $row )
			{
				$entry = $xml->fetchElementsFromRecord( $row );
				$db    = array(  'title'       => $entry['title'],
								 'text'		   => $entry['text'],
								 'description' => $entry['description'],
								 'position'    => $entry['position'],
								 'app'		   => $app );
								
				if ( $entry['title'] )
				{
					$curFaq = $this->DB->buildAndFetch( array( 'select'	=> 'id', 'from' => 'faq', 'where' => "app='" . $app . "' AND title = '".$this->DB->addSlashes( $entry['title'] )."'" ) );

					if ( $curFaq['id'] )
					{
						if ( $overwrite === TRUE )
						{
							$processed['updated']++;
							
							$this->DB->update( 'faq', $db, "id = ".  $curFaq['id'] );
						}
					}
					else
					{
						$processed['added']++;
						
						$this->DB->insert( 'faq', $db );
					}
				}
			}
		}
		
		return $processed;
	}
	
	/**
	 * Export help files XML
	 *
	 * @access	public
	 * @return	void
	 */
	public function helpFilesXMLExport()
	{
		/* INIT */
		$entry = array();
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		
		/* Loop through all the applications */
		foreach( $this->registry->getApplications() as $app => $__data )
		{
			$c = 0;
				
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->newXMLDocument();
			$xml->addElement( 'export' );
			$xml->addElement( 'help', 'export' );

			/* Query tasks */
			$this->DB->build( array( 'select' => '*', 'from' => 'faq', 'where' => "app='{$app}'" ) );		
			$this->DB->execute();
			
			/* Loop through and add tasks to XML */
			while ( $r = $this->DB->fetch() )
			{
				$c++;
				unset( $r['id'] );
				
				$r['text'] = str_replace( '%7Bstyle_image_url%7D', '{style_image_url}', $r['text'] );
				
				$xml->addElementAsRecord( 'help', 'row', $r );
			}
			
			/* Finish XML */	
			$doc = $xml->fetchDocument();
			
			@unlink( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_help.xml' );
			
			/* Write */
			if( $doc and $c)
			{
				$fh = fopen( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_help.xml', 'w' );
				fwrite( $fh, $doc );
				fclose( $fh );
			}
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['help'][ $app ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['h_exported'];
		$this->helpFilesList();
	}	
	
	/**
	 * Removes a help file
	 *
	 * @access	public
	 * @return	void
	 * @author	Josh
	 */
	public function helpFileRemove()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );	
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['h_noid'], 11149 );
		}
		
		/* Delete the record */
		$this->DB->delete( 'faq', "id={$id}" );
		
		/* Log and bounce */
		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['h_removed'] );
		$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );		
	}	
	
	/**
	 * Handles the add/edit help file form
	 *
	 * @access	public
	 * @param	string	$type	Either new or edit
	 * @return	void
	 */
	public function handleHelpFileForm( $type='new' )
	{
		/* Error Checking */
		if( ! $this->request['title'] )
		{
			$this->registry->output->showError( $this->lang->words['h_entertitle'], 11150 );
		}

		$text = trim( $_POST['editor_main'] );
		$text = preg_replace( "/\\\/", "&#092;", $text );
		$text = str_replace( '%7Bstyle_image_url%7D', '{style_image_url}', $text );
		
		/* Build DB Array */
		$db_array = array( 
							'title'       => $this->request['title'],
							'app'		  => $this->request['appDir'],
							'text'        => $text,
							'description' => nl2br( $this->request['description'] ),
						);
		
		/* Insert help file */
		if( $type == 'new' )
		{
			/* Update the DB */
			$this->DB->insert( 'faq', $db_array );
			
			$id = $this->DB->getInsertId();
						
			/* Log */
			$this->registry->adminFunctions->saveAdminLog( $this->lang->words['h_addlog'] );
		}
		/* Update help file */
		else
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['h_noid'], 11151 );
			}
			
			/* Update the DB */
			$this->DB->update( 'faq', $db_array, "id={$id}" );
			
			$this->registry->adminFunctions->saveAdminLog( $this->lang->words['h_edited']);			
		}

		/* Bounce */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );
	}	
	
	/**
	 * Form for adding/editing help files
	 *
	 * @access	public
	 * @param	string	$type	New or edit
	 * @return	void
	 */
	public function helpFileForm( $type='new' )
	{
		/* INIT */
		$dropdown = array();
		
		/* Build Drop */
		foreach( ipsRegistry::$applications as $appDir => $appData )
		{
			$dropdown[] = array( $appDir, $appData['app_title'] );
		}
	
        /* Edit Help File */
		if( $type != 'new' )
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['h_noid'], 11152 );
			}
		
			/* Query the help file */
			$this->DB->build( array( 'select' => '*', 'from' => 'faq', 'where' => "id=" . $id ) );
			$this->DB->execute();
			
			/* Make sure we found one */	
			if( ! $r = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['h_404'], 11153 );
			}
		
			/* Text bits */
			$button = $this->lang->words['h_editbutton'];
			$code   = 'doedit';
		}
		else
		{
			/* Data */
			$r  = array();
			$id = 0;
			
			/* Text Bits */
			$button = $this->lang->words['h_addbutton'];
			$code   = 'donew';
		}
		
		/* Form Elements */
		$form = array();		
		
		$form['title']       = $this->registry->output->formInput('title'  , $r['title'] );
		$form['description'] = $this->registry->output->formTextarea('description', $r['description'] );
		$form['appDir']		 = $this->registry->output->formDropdown( 'appDir', $dropdown, $r['app'] );
		$form['text']        = $r['text'];
		
		/* Ouput */
		$this->registry->output->html .= $this->html->helpFileForm( $code, $id, $form, $button );
	}	
	
	/**
	 * Reorders help files
	 *
	 * @access	public
	 * @return	void
	 */
	public function helpFilesReorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax			= new classAjax();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['faq']) AND count($this->request['faq']) )
 		{
 			foreach( $this->request['faq'] as $this_id )
 			{
 				$this->DB->update( 'faq', array( 'position' => $position ), 'id=' . $this_id );
 				
 				$position++;
 			}
 		}

 		//$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );
 		$ajax->returnString( 'OK' );
 		exit();
	}	
	
	/**
	 * List current help files
	 *
	 * @access	public
	 * @return	void
	 */
	public function helpFilesList()
	{
		/* Query Help Files */
		$this->DB->build( array( 'select' => '*', 'from' => 'faq', 'order' => "position" ) );
		$this->DB->execute();
		
		/* Do we have help files? */
		$rows = array();
		
		if( $this->DB->getTotalRows() )
		{
			while( $r = $this->DB->fetch() )
			{
				/* Add to output array */
				$rows[] = $r;
			}
		}
		
		/* Output */
		$this->registry->output->html           .= $this->html->helpFilesList( $rows );
	}	
}