<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Attachments: Manage
 * Last Updated: $LastChangedDate: 2009-06-24 23:14:22 -0400 (Wed, 24 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		Mon 24th May 2004
 * @version		$Rev: 4818 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_attachments_types extends ipsCommand
{
	/**
	 * Image directory
	 *
	 * @access	private
	 * @var		string
	 */
	private $image_dir = '';
	
	/**
	 * HTML  object
	 *
	 * @access	private
	 * @var		object
	 */
	private $html;
	
	/**
	 * Main execution point
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_attachments' );
		$this->html->form_code    = 'module=attachments&amp;section=types&amp;';
		$this->html->form_code_js = 'module=attachments&amp;section=types&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_attachments' ) );
		
		/* Image Set */
		$this->image_dir = $this->registry->output->skin['set_image_dir'];
		
		//-----------------------------------------
		// StRT!
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'attach_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_add' );
				$this->attachmentTypeForm('add');
			break;
			
			case 'attach_doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_add' );
				$this->attachmentTypeSave('add');
			break;
			
			case 'attach_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_edit' );
				$this->attachmentTypeForm('edit');
			break;
			
			case 'attach_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_delete' );
				$this->attachmentTypeDelete();
			break;
			
			case 'attach_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_edit' );
				$this->attachmentTypeSave('edit');
			break;
			
			case 'attach_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_export' );
				$this->attachmentTypeExport();
			break;
			
			case 'attach_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_import' );
				$this->attachmentTypeImport();
			break;
			
			case 'overview':
			default:
				$this->attachmentTypesOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Imports attachment types from an xml document
	 *
	 * @access	public
	 * @return	void
	 **/
	public function attachmentTypeImport()
	{
		/* Get the XML Content */
		$content = $this->registry->adminFunctions->importXml( 'ipb_attachtypes.xml' );
		
		/* Check to make sure we have content */
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['ty_failed'];
			$this->attachmentTypesOverview();
		}
		
		/* Get the XML class */
		require_once( IPS_KERNEL_PATH.'class_xml.php' );
		$xml = new class_xml();
		
		/* Parse the XML document */
		$xml->xml_parse_document( $content );
		
		/* Get a list of the types already installed */
		$types = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type', 'order' => "atype_extension" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$types[ $r['atype_extension'] ] = 1;
		}
		
		/* Loop through the xml document and insert new types */
		foreach( $xml->xml_array['attachtypesexport']['attachtypesgroup']['attachtype'] as $entry )
		{
			/* Build the insert array */
			$insert_array = array( 'atype_extension' => $entry['atype_extension']['VALUE'],
								   'atype_mimetype'  => $entry['atype_mimetype']['VALUE'],
								   'atype_post'      => $entry['atype_post']['VALUE'],
								   'atype_photo'     => $entry['atype_photo']['VALUE'],
								   'atype_img'       => $entry['atype_img']['VALUE']
								 );

			/* Bypass if this type has already been added */
			if ( $types[ $entry['atype_extension']['VALUE'] ] )
			{
				continue;
			}
			
			/* Insert the new type */
			if ( $entry['atype_extension']['VALUE'] and $entry['atype_mimetype']['VALUE'] )
			{
				$this->DB->insert( 'attachments_type', $insert_array );
			}
		}
		
		/* Rebuild the cache and bounce */
		$this->attachmentTypeCacheRebuild();                    
		
		$this->registry->output->global_message = $this->lang->words['ty_imported'];		
		$this->attachmentTypesOverview();
	}	
	
	/**
	 * Builds the attachment type xml export
	 *
	 * @access	public
	 * @return	void
	 **/
	public function attachmentTypeExport()
	{
		/* Get XML Class */
		require_once( IPS_KERNEL_PATH.'class_xml.php' );
		$xml = new class_xml();
		
		/* Set the root of the XML document */
		$xml->xml_set_root( 'attachtypesexport' );
		
		/* Add the attachment type group */
		$xml->xml_add_group( 'attachtypesgroup' );
		
		/* Query the attachment Types */
		$this->DB->build( array( 
										'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img',
										'from'   => 'attachments_type',
										'order'  => "atype_extension" 
								)	 );
		$this->DB->execute();
		
		/* Loop through the types */
		$entry = array();
		
		while( $r = $this->DB->fetch() )
		{
			/* INI */
			$content = array();
			
			/* Build the tag */
			foreach ( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			/* Add to the entry */
			$entry[] = $xml->xml_build_entry( 'attachtype', $content );
		}
		
		/* Add entries to the group and build the document */
		$xml->xml_add_entry_to_group( 'attachtypesgroup', $entry );
		$xml->xml_format_document();
		
		/* Send for download */
		$this->registry->output->showDownload( $xml->xml_document, 'attachments.xml', "unknown/unknown", false );
	}	
	
	/**
	 * Removes the specified attachment type
	 *
	 * @access	public
	 * @return	void
	 **/
	public function attachmentTypeDelete()
	{
		/* INI */
		$this->request[ 'id'] =  intval( $this->request['id']  );
		
		/* Delete the type */
		$this->DB->delete( 'attachments_type', "atype_id={$this->request['id']}" );
		
		/* Build the cache and Bounce */		
		$this->attachmentTypeCacheRebuild();
		
		$this->registry->output->global_message = $this->lang->words['ty_deleted'];	
		$this->attachmentTypesOverview();
	}

	/**
	 * Processes the from for adding/editing attachments
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit	 
	 * @return	void
	 **/
	public function attachmentTypeSave( $type='add' )
	{
		/* INI */
		$this->request['id'] = intval( $this->request['id'] );
		
		/* Make sure the form was filled out */
		if ( ! $this->request['atype_extension'] or ! $this->request['atype_mimetype'] )
		{
			$this->registry->output->global_message = $this->lang->words['ty_enterinfo'];
			$this->attachmentTypeForm( $type );
			return;
		}
		
		/* Build the save array */
		$save_array = array( 'atype_extension' => str_replace( ".", "", $this->request['atype_extension'] ),
							 'atype_mimetype'  => $this->request['atype_mimetype'],
							 'atype_post'      => $this->request['atype_post'],
							 'atype_photo'     => $this->request['atype_photo'],
							 'atype_img'       => $this->request['atype_img']
						   );
		
		/* Add attachment type to the database */
		if ( $type == 'add' )
		{
			/* Check to see if this attachment type already exists */
			$attach = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='".$save_array['atype_extension']."'" ) );
			
			if ( $attach['atype_id'] )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['ty_already'], $save_array['atype_extension'] );
				$this->attachmentTypeForm($type);
			}
			
			/* Insert the attachment type */
			$this->DB->insert( 'attachments_type', $save_array );
			
			/* Done Message */
			$this->registry->output->global_message = $this->lang->words['ty_added'];
			
		}
		else
		{
			/* Update the attachment type */
			$this->DB->update( 'attachments_type', $save_array, 'atype_id=' . $this->request['id'] );
			
			/* Done Message */
			$this->registry->output->global_message = $this->lang->words['ty_edited'];
		}
		
		/* Cache and Bounce */
		$this->attachmentTypeCacheRebuild();		
		$this->attachmentTypesOverview();
	}	
	
	/**
	 * Displays the form for adding/editing attachment types
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 **/
	public function attachmentTypeForm( $type='add' )
	{
		/* INI */
		$this->request[ 'id'] =  $this->request['id'] ? intval( $this->request['id'] )  : 0;
		$this->request[ 'baseon'] =  $this->request['baseon'] ? intval( $this->request['baseon'] ) : 0;
		
		/* Navigation */
		$this->registry->output->nav[] = array( '', $this->lang->words['ty_addedit'] );
		
		$baseon	= '';
		
		if( $type == 'add' )
		{
			/* Setup */
			$code   = 'attach_doadd';
			$button = $this->lang->words['ty_addnew'];
			$id     = 0;
			
			/* Default Data */
			if( $this->request['baseon'] )
			{
				$attach = $this->DB->buildAndFetch( array( 
																	'select' => '*', 
																	'from' => 'attachments_type', 
																	'where' => 'atype_id=' . $this->request['baseon']
														)		);
			}
			else
			{
				$attach = array( 'atype_extension' 	=> '',
								 'atype_mimetype'	=> '',
								 'atype_post'		=> '',
								 'atype_photo'		=> '',
								 'atype_img'		=> '' );
			}
			
			/* Generate Based On Dropdown*/
			$dd = array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type', 'order' => 'atype_extension' ) );
			$this->DB->execute();
		
			while( $r = $this->DB->fetch() )
			{
				$dd[] = array( $r['atype_id'], $this->lang->words['ty_baseon'] . $r['atype_extension'] );
			}
				
			$title	= $button;
			$baseon	= $this->html->attachmentTypeBaseOn( $this->registry->output->formDropdown( 'baseon', $dd ) );
		}
		else
		{
			/* Setup */
			$code   = 'attach_doedit';
			$button = $this->lang->words['ty_edit'];
			$title  = $button;
			$id     = intval( $this->request['id'] );
			
			/* Default Data */
			$attach = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => 'atype_id='.ipsRegistry::$request['id'] ) );
		
			/* Check for valid id */
			if ( ! $attach['atype_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['ty_noid'];
				$this->attachmentTypesOverview();
			}
		}
		
		/* Form Elements */
		$form = array(
						'atype_extension' => $this->registry->output->formSimpleInput( 'atype_extension', ( isset( $this->request['atype_extension'] ) AND $this->request['atype_extension'] ) ? $this->request['atype_extension'] : $attach['atype_extension'], 10 ),
						'atype_mimetype'  => $this->registry->output->formSimpleInput( 'atype_mimetype' , ( isset( $this->request['atype_mimetype'] )  AND $this->request['atype_mimetype'] )  ? $this->request['atype_mimetype']  : $attach['atype_mimetype'] , 40 ),
						'atype_post'      => $this->registry->output->formYesNo(       'atype_post'     , ( isset( $this->request['atype_post'] )      AND $this->request['atype_post'] )      ? $this->request['atype_post']      : $attach['atype_post']          ),
						'atype_photo'     => $this->registry->output->formYesNo(       'atype_photo'    , ( isset( $this->request['atype_photo'] )     AND $this->request['atype_photo'] )     ? $this->request['atype_photo']     : $attach['atype_photo']         ),
						'atype_img'       => $this->registry->output->formSimpleInput( 'atype_img'      , ( isset( $this->request['atype_img'] )       AND $this->request['atype_img'] )       ? $this->request['atype_img']       : $attach['atype_img']      , 40 ),
					);
		
		/* Output */
		$this->registry->output->html .= $this->html->attachmentTypeForm( $title, $code, $id, $form, $button, $baseon );
	}	
	
	/**
	 * Shows the attachment types that have been setup
	 *
	 * @access	public
	 * @return	void
	 **/
	public function attachmentTypesOverview()
	{
		/* Get the attachments */
		$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type', 'order' => 'atype_extension' ) );
		$this->DB->execute();
		
		/* Loop through the attachments */
		$attach_rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_imagedir'] = $this->image_dir;
			
			$checked_img         = "<img src='{$this->settings['skin_acp_url']}/_newimages/icons/tick.png' alt='Yes' />";
			$r['apost_checked']  = $r['atype_post']  ? $checked_img : '&nbsp;';
			$r['aphoto_checked'] = $r['atype_photo'] ? $checked_img : '&nbsp;';
			
			$attach_rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->attachmentTypeOverview( $attach_rows );
	}	
	
	/*
	 * Rebuilds the attachment type cache
	 *
	 * @access	public
	 * @return	void
	 **/
	public function attachmentTypeCacheRebuild()
	{
		$cache = array();
			
		$this->DB->build( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache[ $r['atype_extension'] ] = $r;
		}
		
		$this->cache->setCache( 'attachtypes', $cache, array( 'array' => 1, 'deletefirst' => 0, 'donow' => 0 ) );		
	}
}