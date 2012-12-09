<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Emoticon Management
 * Last Updated: $LastChangedDate: 2009-07-30 19:17:45 -0400 (Thu, 30 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 4955 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_posts_emoticons extends ipsCommand 
{	
	/**
	 * Allowed file types
	 *
	 * @access	public
	 * @var		array
	 */
	public $allowed_files = array( 'png', 'jpeg', 'jpg', 'gif' );
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_emoticons' );
		$this->html->form_code    = '&amp;module=posts&amp;section=emoticons&amp;';
		$this->html->form_code_js = '&module=posts&section=emoticons&';
		
		$this->lang->loadLanguageFile( array( 'admin_posts' ) );

		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'emo_packsplash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_manage' );
				$this->emoticonsPackSplash();
			break;
				
			case 'emo_packexport':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_manage' );
				$this->emoticonsPackExport();
			break;
			
			case 'emo_packimport':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_manage' );
				$this->emoticonsPackImport();
			break;
				
			case 'emo_manage':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_manage' );
				$this->emoticonsManageDirectory();
			break;
				
			case 'emo_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_edit' );
				$this->emoticonsEdit();
			break;
					
			case 'emo_doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_add' );
				$this->emoticonsAdd();
			break;
				
			case 'emo_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_delete' );
				$this->emoticonsRemove();
			break;
				
			case 'emo_setadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_add' );
				$this->emoticonsSetAlter( $type='add' );
			break;
				
			case 'emo_setedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_edit' );
				$this->emoticonsSetAlter( $type='edit' );
			break;
				
			case 'emo_setremove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_delete' );
				$this->emoticonsSetRemove();
			break;
			
			case 'emo_upload':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_add' );
				$this->emoticonsUpload();
			break;
			
			case 'emo':
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emoticons_manage' );
				$this->emoticonsOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Import an emoticon pack
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsPackImport()
	{
		/* Get the xml file */
		$content = $this->registry->adminFunctions->importXml( 'ipb_emoticons.xml' );
		
		/* Check for content */
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['emo_fail'];
			return $this->emoticonsPackSplash();
		}
		
		/* Setup XML */
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		require_once( IPS_KERNEL_PATH.'classXMLArchive.php' );
		$xmlarchive = new classXMLArchive();
		
		/* Read the archive */
		$xmlarchive->readXML( $content );
		
		/* Get the data file */
		$emoticons     = array();
		$emoticon_data = array();
		
		foreach( $xmlarchive->asArray() as $k => $f )
		{
			if ( $k == 'emoticon_data.xml' )
			{
				$emoticon_data = $f;
			}
			else
			{
				$emoticons[ $f['filename'] ] = $f['content'];
			}
		}
		
		/* Parse the XML Document */
		$xml->loadXML( $emoticon_data['content'] );
		
		/* Make sure we have a destination for these emoicons */
		if ( ! $this->request['emo_set'] and ! $this->request['new_emo_set'] )
		{
			$this->registry->output->global_message = $this->lang->words['emo_specify'];
		}
		
		/* Current emoticon set directory */
		$emo_set_dir = trim( $this->request['emo_set'] );
		
		/* New emoticon set directory */
		$this->request['new_emo_set'] = preg_replace( "/[^a-zA-Z0-9\-_]/", "", $this->request['new_emo_set'] );
		
		/* Create the new set */
		if ( $this->request['new_emo_set'] )
		{
			$emo_set_dir = trim( $this->request['new_emo_set'] );
			
			/* Check to see if the directory already exists */
			if ( file_exists( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $emo_set_dir ) )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['emo_already'], $emo_set_dir ); 
				return $this->emoticonsPackSplash();
			}
		
			/* Create the directory */
			if ( @mkdir( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $emo_set_dir, 0777 ) )
			{
				@chmod( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $emo_set_dir, 0777 );
				@file_put_contents( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $emo_set_dir . '/index.html', '' );
			}
			else
			{
				$this->registry->output->global_message = $this->lang->words['emo_ftp'];
				return $this->emoticonsPackSplash();
			}
		}
	
		/* Get a list of current emoticons, if we are not overwriting */
		$emo_image = array();
		$emo_typed = array();
		
		if ( $this->request['overwrite'] != 1  )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='".$emo_set_dir."'" ) );
			$this->DB->execute();
		
			while( $r = $this->DB->fetch() )
			{
				$emo_image[ $r['image'] ] = 1;
				$emo_typed[ $r['typed'] ] = 1;
			}
		}
		
		/* Loop through the emoticons in the xml document */
		foreach( $xml->fetchElements('emoticon') as $emoticon )
		{
			$entry  = $xml->fetchElementsFromRecord( $emoticon );

			/* Emoticon Data */
			$image = $entry['image'];
			$typed = $entry['typed'];
			$click = $entry['clickable'];
			
			/* Skip if we're not overwriting */
			if ( $emo_image[ $image ] or $emo_typed[ $typed ] )
			{
				continue;
			}
			
			/* Get the extension */
			$file_extension = preg_replace( "#^.*\.(.+?)$#si", "\\1", strtolower( $image ) );
			
			/* Make sure it's allowed */
			if ( ! in_array( $file_extension, $this->allowed_files ) )
			{
				continue;
			}
			
			/* Remove any existing emoticon */
			@unlink( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $emo_set_dir . '/' . $image );
			
			$this->DB->delete( 'emoticons', "typed='$typed' and image='$image' and emo_set='$emo_set_dir'" );
			
			/* Create the image in the file system */
			if( $FH = fopen( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' .$emo_set_dir . '/' . $image, 'wb' ) )
			{
				if ( fwrite( $FH, $emoticons[ $image ] ) )
				{
					fclose( $FH );

					/* Insert the emoticon record */
					$this->DB->insert( 'emoticons', array( 'typed' => $typed, 'image' => $image, 'clickable' => $click, 'emo_set' => $emo_set_dir ) );
				}
			}
			
			/* Add the emoticon to all the other directories */
			try
			{
	 			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' ) as $file )
	 			{
	 				if( ! $file->isDot() && $file->isDir() )
	 				{
						if ( substr( $file->getFilename(), 0, 1 ) == '.' )
						{
							continue;
						}
						
						if ( ! file_exists( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $file->getFilename() . '/' . $image ) )
						{
							$this->DB->buildAndFetch( array( 'delete' => 'emoticons', 'where' => "typed='$typed' and image='$image' and emo_set='{$file->getFilename()}'" ) );
							
							if ( $FH = @fopen( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $file->getFilename() . '/' . $image, 'wb' ) )
							{
								if ( fwrite( $FH, $emoticons[ $image ] ) )
								{
									fclose( $FH );
									
									$this->DB->insert( 'emoticons', array( 'typed' => $typed, 'image' => $image, 'clickable' => $click, 'emo_set' => $file->getFilename() ) );
								}
							}
						}
	 				}
	 			}
			} catch ( Exception $e ) {}
		}
		
		/* Recache and bounce */
		$this->emoticonsRebuildCache();
                    
		$this->registry->output->global_message = $this->lang->words['emo_xml_good'];		
		$this->emoticonsOverview();
	
	}
	
	/**
	 * Export the specified emoticon directory
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsPackExport()
	{
		/* Setup XML */
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );
		$xmlarchive = new classXMLArchive();
		
		/* Check the emoticon set */
		if ( ! $this->request['emo_set'] )
		{
			$this->registry->output->global_message = $this->lang->words['emo_specify_exS'];
			$this->emoticonsOverview();
			return;
		}
		
		/* Get emoticons from the database */
		$emo_db = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='{$this->request['emo_set']}'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$emo_db[ $r['image'] ] = $r;
		}
		
		/* Get Emoticon Folders */
		$emodirs = array();
		$emodd   = array();
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $this->request['emo_set'] ) as $file )		
 			{
 				if( ! $file->isDot() )
 				{
 					if ( $emo_db[ $file->getFilename() ] != "" )
 					{
						$files_to_add[] = DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $this->request['emo_set'] . '/' . $file->getFilename();
					}
 				}
 			}
		} catch ( Exception $e ) {}
  		
		/* Add each file to the xml archive */
		foreach( $files_to_add as $f )
		{
			$xmlarchive->add( $f );
		}
		
		$xml->newXMLDocument();
		$xml->addElement( 'emoticonexport' );
		$xml->addElement( 'emogroup', 'emoticonexport' );
		
		foreach( $emo_db as $r )
		{
			$content = array();
			
			$content['typed']		= $r['typed'];
			$content['image']		= $r['image'];
			$content['clickable']	= $r['clickable'];
			
			$xml->addElementAsRecord( 'emogroup', 'emoticon', $content );
		}

		/* Create the XML Document */
		$xmlData = $xml->fetchDocument();
		
		/* Add the xml document to the archive */
		$xmlarchive->add( $xmlData, 'emoticon_data.xml' );

		/* Send the archive to the browser */
		$imagearchive = $xmlarchive->getArchiveContents();		
		$this->registry->output->showDownload( $imagearchive, 'ipb_emoticons.xml' );
	}
	
	/**
	 * Builds the emoticon import/export pack splash screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsPackSplash()
	{
		/* Check for the emoticon directory */
		if( ! is_dir( DOC_IPS_ROOT_PATH . 'public/style_emoticons' ) )
		{
			$this->registry->output->showError( $this->lang->words['emo_nolocate'], 2110 );
			
		}
		
		/* Count the number of current emoticons */
		$this->DB->build( array( 'select'	=> 'id, count(id) as count, emo_set',
										'from'	=> 'emoticons',
										'group'	=> 'emo_set',
										'order'	=> 'emo_set'
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$emo_db[ $r['emo_set'] ] = $r;
		}
		
		/* Get the emoticon folders */
		$emodirs = array();
		$emodd   = array();
		
		try
		{
 			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_emoticons' ) as $file )
 			{
 				if( $file->isDir() && ! $file->isDot() )
 				{
					$emodirs[] = $file->getFilename();
					$emodd[]   = array( $file->getFilename(), $file->getFilename() );
 				}
 			}
		} catch ( Exception $e ) {}
 		
 		/* Build Form Elements */
 		$form                = array();
 		$form['emo_set']     = $this->registry->output->formDropdown( 'emo_set', $emodd );
 		$form['new_emo_set'] = $this->registry->output->formInput( 'new_emo_set' );
 		$form['overwrite']   = $this->registry->output->formYesNo( 'overwrite' );
 		
 		/* Output */
 		$this->registry->output->html .= $this->html->emoticonsPackSplash( $form );
	}	
	
	/**
	 * Remove the specified emoticon
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsRemove()
	{
		/* Check the ID */
		if( $this->request['id'] == "" )
		{
			$this->registry->output->global_message = $this->lang->words['emo_nogid'];
			$this->emoticonsOverview();
			return;
		}
		
		/* Can only remove the default set */
		if( $this->request['id'] != "default" )
		{
			$this->registry->output->global_message = $this->lang->words['emo_onlydothis'];
			$this->emoticonsOverview();
			return;
		}		
		
		/* Check for the emoticon ID */
		$this->request['eid'] = intval( $this->request['eid'] );
		
		if( ! $this->request['eid'] )
		{
			$this->registry->output->global_message = $this->lang->words['emo_noid'];
			$this->emoticonsManageDirectory();
			return;
		}
		
		/* Get the emoticon info */
		$emo_info = $this->DB->buildAndFetch( array( 'select' => 'typed', 'from' => 'emoticons', 'where' => "id=" . $this->request['eid']) );
		
		/* Delete the emoticon */
		$this->DB->delete( 'emoticons', "typed='{$emo_info['typed']}'" );

		/* Rebuild the cache and bounce */		
		$this->emoticonsRebuildCache();
		
		$this->registry->output->global_message = $this->lang->words['emo_removed'];		
		$this->emoticonsManageDirectory();
	}	
	
	/**
	 * Add emoticons to the default directory
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsAdd()
	{
		/* Check the id */
		if( $this->request['id'] == "" )
		{
			$this->registry->output->global_message = $this->lang->words['emo_nogid'];
			$this->emoticonsOverview();
			return;
		}
		
		/* Can only upload to the default directory */
		if( $this->request['id'] != "default" )
		{
			$this->registry->output->global_message = $this->lang->words['emo_onlydothis'];
			$this->emoticonsOverview();
			return;
		}			
		
		/* Loop through the request and look for emoticons */
		foreach( $this->request as $key => $value )
		{
			if( preg_match( "/^emo_type_(\d+)$/", $key, $match ) )
			{
				if( isset( $this->request[ $match[0] ] ) )
				{
					/* Get the emoticon info */
					$typed = str_replace( '&quot;', "", $this->request[ $match[0] ] );
					$click = $this->request[ 'emo_click_'.$match[1] ];
					$add   = $this->request[ 'emo_add_'.$match[1] ];
					$image = $this->request[ 'emo_image_'.$match[1] ];
					$set   = trim( $this->request['id'] );					
					$typed = str_replace( '&#092;', "", $typed );
					
					/* Check the add all flag */
					if( $this->request['addall'] )
					{
						$add = 1;
					}
					
					/* Add this emoticon if we have have the required info */
					if( $add and $typed and $image )
					{
						/* Insert the emo record */
						$this->DB->insert( 'emoticons', array( 'clickable' => intval( $click ), 'typed' => $typed, 'image' => $image, 'emo_set' => $set ) );
						
						/* Emoticon list */
						$emodirs = array( 0 => '');
						
						/* Loop through all the emoticons */
						try
						{
							foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_emoticons' ) as $file )				 		
				 			{
				 				if( ! $file->isDot() && $file->isDir() )
				 				{
									if( $file->getFilename() == 'default' )
									{
										$emodirs[0] = $file->getFilename();
									}
									else
									{
										$emodirs[] = $file->getFilename();
									}
				 				}
				 			}
						} catch ( Exception $e ) {}
						
				 		/* Add this emoticon to the other sets */				 		
				 		foreach( $emodirs as $directory )
				 		{
					 		if( $directory == $set )
					 		{
						 		continue;
					 		}
					 		
					 		$this->DB->insert( 'emoticons', array( 'clickable' => intval( $click ), 'typed' => $typed, 'image' => $image, 'emo_set' => $directory ) );
				 		}
					}
				}
			}
		}
		
		/* Rebuild the cache and bounce */
		$this->emoticonsRebuildCache();
		
		$this->registry->output->global_message = $this->lang->words['emo_updated'];	
		$this->emoticonsManageDirectory();
	}	
	
	/**
	 * Handles the upload emoticon form
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsUpload()
	{
		/* INI */
		$overwrite		= 1;
		$uploaded		= 0;
		
		/* Check the request for uploads */
		$directories = array();
		$first_dir   = '';
		
		foreach( $this->request as $key => $value )
		{
			if( preg_match( "/^dir_(.*)$/", $key, $match ) )
			{
				if( $this->request[ $match[0] ] == 1 )
				{
					$directories[] = $match[1];
				}
			}
		}
		
		/* Can't upload to default */
		if ( ! count( $directories ) )
		{
			$this->registry->output->global_message = $this->lang->words['emo_pickanother'];
			$this->emoticonsOverview();
			return;
		}
		
		/* Remove default from the directories list */
		if ( ! in_array( 'default', $directories ) )
		{
			array_push( $directories, 'default' );
		}
		
		/* Get the first directory */
		$first_dir = array_shift( $directories );
		
		/* Loop through the dirs */
		$emodirs = array( 0 => '' );
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'public/style_emoticons' ) as $file )
 			{
 				if( ! $file->isDot() && $file->isDir() )
 				{
 					/* Add to emoticon list */
					if( $file->getFilename() == 'default' )
					{
						$emodirs[0] = $file->getFilename();
					}
					else
					{
						$emodirs[] = $file->getFilename();
					}
 				}
 			}
		} catch ( Exception $e ) {}

		/* Loop through each form upload field */
		foreach( array( 1,2,3,4 ) as $i )
		{
			/* Upload Data */
			$field     = 'upload_'.$i;
			$FILE_NAME = $_FILES[$field]['name'];
			$FILE_SIZE = $_FILES[$field]['size'];
			$FILE_TYPE = $_FILES[$field]['type'];
			
			//-----------------------------------------
			// Naughty Opera adds the filename on the end of the
			// mime type - we don't want this.
			//-----------------------------------------
			
			$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
			
			//-----------------------------------------					
			// Naughty Mozilla likes to use "none" to indicate an empty upload field.
			// I love universal languages that aren't universal.
			//-----------------------------------------
			
			if ( $_FILES[$field]['name'] == "" or ! $_FILES[$field]['name'] or ($_FILES[$field]['name'] == "none") )
			{
				continue;
			}
			
			//-----------------------------------------
			// Make sure it's not a NAUGHTY file
			//-----------------------------------------
			
			$file_extension = preg_replace( "#^.*\.(.+?)$#si", "\\1", strtolower( $_FILES[ $field ]['name'] ) );
		
			if ( ! in_array( $file_extension, $this->allowed_files ) )
			{
				$this->registry->output->global_message = $this->lang->words['emo_mimes']; // The screams of angst from Emo Mimes are silent...
				$this->emoticonsOverview();
				return;
			} 
			
			//-----------------------------------------
			// Copy the upload to the uploads directory
			//-----------------------------------------
			
			if ( ! @move_uploaded_file( $_FILES[ $field ]['tmp_name'], DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $first_dir . "/" . $FILE_NAME) )
			{
				$this->registry->output->global_message = "The upload failed, sorry!";
				$this->emoticonsOverview();
				return;
			}
			else
			{
				@chmod( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $first_dir . "/" . $FILE_NAME, 0777 );
				
				//-----------------------------------------
				// Copy to other folders
				//-----------------------------------------
				
				if ( is_array( $directories ) and count( $directories ) )
				{
					foreach ( $directories as $newdir )
					{
						if ( file_exists( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $newdir . "/" . $FILE_NAME ) )
						{
							if ( $overwrite != 1 OR $newdir == 'default' )
							{
								continue;
							}
						}
						
						if ( @copy( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $first_dir . "/" . $FILE_NAME, DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $newdir . "/" . $FILE_NAME ) )
						{
							@chmod( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $newdir . "/" . $FILE_NAME, 0777 );
						}
					}
				}
				
				// Let's make sure this 'image' is available in all directories too
				if ( is_array( $emodirs ) and count( $emodirs ) )
				{
					foreach ( $emodirs as $newdir )
					{
						if ( file_exists( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $newdir . "/" . $FILE_NAME ) )
						{
							continue;
						}
						
						if( @copy( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $first_dir . "/" .$FILE_NAME, DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $newdir . "/" . $FILE_NAME ) )
						{
							@chmod( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $newdir . "/" . $FILE_NAME, 0777 );
						}
					}
				}
				
				$uploaded++;
			}
		}
		
		if( !$uploaded )
		{
			$this->registry->output->global_message = $this->lang->words['no_emo_selected'];
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['emo_complete'];
		}

		$this->emoticonsOverview();
	}	
	
	/**
	 * Removes an emoticon set
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsSetRemove()
	{
		/* Check the ID */
		if ( ! $this->request['id'] )
		{
			$this->registry->output->global_message = $this->lang->words['emo_noset'];
			$this->emoticonsOverview();
		}
		
		/* Can't remove the default set */
		if( $this->request['id'] == 'default' )
		{
			$this->registry->output->global_message = $this->lang->words['emo_norename'];
			$this->emoticonsOverview();
			return;
		}
		
		/* Remove the directory */
		$this->registry->adminFunctions->removeDirectory( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $this->request['id'] );
		
		/* Remove the database entry */
		$this->DB->delete( 'emoticons', "emo_set='{$this->request['id']}'" );

		/* Rebuild cache */		
		$this->emoticonsRebuildCache();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['emo_folder_rem'];
		$this->emoticonsOverview();
	}	
	
	/**
	 * Add/Edit an emoticon folder name
	 *
	 * @access	public
	 * @param	string	Type (add or edit)
	 * @return	void
	 */
	public function emoticonsSetAlter( $type='add' )
	{
		/* Get the name */
		$name = preg_replace( "/[^a-zA-Z0-9\-_]/", "", $this->request['emoset'] );
		
		/* Check th ename */
		if( $name == "" )
		{
			$this->registry->output->global_message = $this->lang->words['emo_a_to_z'];
			return $this->emoticonsOverview();
		}
		
		/* Check Safe Mode */
		if( SAFE_MODE_ON )
		{
			$this->registry->output->global_message = $this->lang->words['emo_safemode'];
			return $this->emoticonsOverview();
		}
		
		/* Check to see if the directory already exists */
		if( file_exists( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name ) )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['emo_already'], $name ); 
			return $this->emoticonsOverview();
		}
		
		if( $type == 'add' )
		{
			/* Create the directory */
			if( @mkdir( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name, 0777 ) )
			{
				@chmod( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name, 0777 );
				@file_put_contents( DOC_IPS_ROOT_PATH . 'public/style_emoticons/' . $name . '/index.html', '' );
				
				/* Copy default emoticons */
				try
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . '/public/style_emoticons/default/' ) as $file )
		 			{
		 				if( ! $file->isDot() )
		 				{
							@copy( DOC_IPS_ROOT_PATH . '/public/style_emoticons/default/' . $file->getFilename(), DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name . '/' . $file->getFilename() );
							@chmod( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name . '/' . $file->getFilename(), 0777 );
		 				}
		 			}
				} catch ( Exception $e ) {}
		 		
				/* Query the default set */		 		
				$this->DB->build( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='default'" ) );
				$outer = $this->DB->execute();
				
				/* Insert emoticons for the new set */
				while( $r = $this->DB->fetch($outer) )
				{
					$this->DB->insert( "emoticons", array( 'clickable' => $r['clickable'], 'typed' => $r['typed'], 'emo_set' => $name, 'image' => $r['image'] ) );
				}
				
				/* Bounce */
				$this->registry->output->global_message = $this->lang->words['emo_folder_new'];
				return $this->emoticonsOverview();
			}
			else
			{
				/* Couldn't create the directory, Bounce */
				$this->registry->output->global_message = $this->lang->words['emo_ftp'];
				return $this->emoticonsOverview();
			}
		}
		/* Edit Folder Name */
		else
		{
			/* Check the directory ID */
			if ( ! $this->request['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['emo_miss_dir'];
				$this->emoticonsOverview();
				return;
			}
			
			/* Can't rename the default directory */
			if( $this->request['id'] == 'default' )
			{
				$this->registry->output->global_message = $this->lang->words['emo_norename'];
				$this->emoticonsOverview();
				return;
			}
			
			/* Rename the directory */
			if ( @rename( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $this->request['id'], DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name ) )
			{
				/* Check to see if the rename worked */
				if ( file_exists( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $name ) )
				{
					/* Update all the emoticons */
					$this->DB->update( 'emoticons', array( 'emo_set' => $name ), "emo_set='{$this->request['id']}'" );
				}
				
				/* Rebuild the cache */				
				$this->emoticonsRebuildCache();
				
				/* Update skins that are using this set */
				$rebuild_sets = array();
				
				/* Querh skin sets */
				$this->DB->build( array( 'select' => 'set_id', 'from' => 'skin_collections', 'where' => "set_emo_dir='{$this->request['id']}'" ) );
				$outer = $this->DB->execute();
				
				/* Loop thorugh and update the emoticon folder */
				while( $r = $this->DB->fetch($outer) )
				{
					$this->DB->update( 'skin_collections', array( 'set_emo_dir' => $name ), 'set_id=' . $r['set_id'] );
					$rebuild_sets[] = $r['set_id'];
				}

				/* Bounce */
				$this->registry->output->global_message = $this->lang->words['emo_folder_name'];
				return $this->emoticonsOverview();
			}
			else
			{
				/* Error and bounce */
				$this->registry->output->global_message = $this->lang->words['emo_wecantdoit'];
				return $this->emoticonsOverview();
			}
		}
	}	
	
	/**
	 * Process edit emoticons forum
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsEdit()
	{
		/* Check ID */
		if( $this->request['id'] == '' )
		{
			$this->registry->output->global_message = $this->lang->words['emo_nogid'];
			return $this->emoticonsOverview();
		}
		
		/* Loop through the request and pull out emoticons */
		foreach( $this->request as $key => $value )
		{
			/* Check to see if its an emoticon */
			if ( preg_match( "/^emo_id_(\d+)$/", $key, $match ) )
			{
				if ( $match[0] )
				{
					/* INI */
					$typed = '';
					
					/* Format type for default set */
					if( $this->request['id'] == 'default' )
					{
						$typed = str_replace( '&quot;', "", $this->request[ 'emo_type_' . $match[1] ] );
						$typed = str_replace( '&#092;', "", $typed );
					}
					
					/* Check clickable */
					$click = $this->request[ 'emo_click_' . $match[1] ];
					
					/* Update the emoticon */
					if ( $match[1] )
					{
						if( $typed )
						{
							/* Get the original name */
							$orig_typed = $this->DB->buildAndFetch( array( 'select' => 'typed', 'from' => 'emoticons', 'where' => 'id=' . intval( $match[1] ) ) );
							
							/* Update to new name */
							$this->DB->update( 'emoticons', array( 'clickable' => intval( $click ), 'typed' => $typed ), 'id=' . intval( $match[1] ) );							
							$this->DB->update( 'emoticons', array( 'typed' => $typed ), "typed='" . $orig_typed['typed'] . "'" );
						}
						else
						{
							/* Just update clickable */
							$this->DB->update( 'emoticons', array( 'clickable' => intval( $click ) ), 'id='.intval( $match[1] ) );
						}
					}
				}
			}
		}
		
		/* Recache and bounce */
		$this->emoticonsRebuildCache();
		
		$this->registry->output->global_message = $this->lang->words['emo_updated'];	
		$this->emoticonsManageDirectory();	
	}

	/**
	 * Manage emoticons
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsManageDirectory()
	{
		/* INI */
		$this->request['id'] = trim( $this->request['id'] );

		/* Get emoticons for this group */
		$emo_db   = array();
		$emo_file = array();
		
		$this->DB->build( array( 
								'select' => '*', 
								'from'   => 'emoticons', 
								'where'  => "emo_set='{$this->request['id']}'", 
								'order'  => 'clickable DESC, image ASC' 
						)	 );
		$this->DB->execute();
		
		/* Loop through the emoticons */
		while( $r = $this->DB->fetch() )
		{
			$emo_db[ $r['image'] ] = $r;
		}
		
		
		/* Get emoticons from the directory */
		$emo_file  = array();
		$emo_rfile = $this->_emoticonGetGolderContents( $this->request['id'] );
		
		foreach( $emo_rfile as $ef )
		{
			$emo_file[ $ef ] = $ef;
		}

		/* Number of emoticons to show per row */
		$per_row    = 5;
		$td_width   = 100 / $per_row;
		
		/* Loop through the database emoticons */		
		$poss_names = array();
		$db_rows    = array();
		
		foreach( $emo_db as $image => $data )
		{
			/* Remove this from the file list, it's already added */
			unset( $emo_file[ $image ] );
			
			/* Clickable? */
			if ( $data['clickable'] )
			{
				$data['_click'] = 'checked="checked"';
				$data['_class'] = 'tablerow1';
			}
			else
			{
				$data['_click'] = '';
				$data['_class'] = 'tablerow2';
			}
			
			/* Add image to array */
			$data['image'] = $image;
			
			/* Addd array to output */
			$db_rows[] = $data;
			
			/* Possible names filter */
			$poss_names[$data['typed']] = $data['typed'];
		}

		/* Check for unassigned images in the emoticon directory */
		$file_rows = array();
				
		if ( count( $emo_file ) && $this->request['id'] == 'default' )
		{
			/* Loop through the unassigned emoticons */
			foreach( $emo_file as $image )
			{				
				/* INI */
				$_fdata = array();
				
				/* Suggest an emoticon name */
				$_fdata['poss_name'] = ':'.preg_replace( "/(.*)(\..+?)$/", "\\1", $image ).':';
				
				if ( isset($poss_names[ $_fdata['poss_name'] ]) AND $poss_names[ $_fdata['poss_name'] ] )
				{
					$_fdata['poss_name'] = preg_replace( "/:$/", "2:", $poss_name );
				}
				
				/* Add the image */
				$_fdata['image'] = $image;
				
				/* Add to output array */
				$file_rows[] = $_fdata;
			}
		}
		
		/* Output */

		$this->registry->output->extra_nav[] = array( '', $this->lang->words['emo_manage_set'].$this->request['id'] );		
		$this->registry->output->html .= $this->html->emoticonsDirectoryManagement( $db_rows, $file_rows, $td_width, $per_row );
	}	
	
	/**
	 * Builds the emoticons overview screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsOverview()
	{
		/* Check for the emoticon directory */
		if ( ! is_dir( DOC_IPS_ROOT_PATH . '/public/style_emoticons' ) )
		{
			$this->registry->output->showError( $this->lang->words['emo_nolocate'], 2111 );
			
		}
		
		/* Check the emoticon count */
		$this->DB->build( array( 'select'	=> 'id, count(id) as count, emo_set',
										'from'	=> 'emoticons',
										'group'	=> 'emo_set, id',
										'order'	=> 'emo_set'
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$emo_db[ $r['emo_set'] ] = $r;
		}
		
		/* Get emoticon folders */
		$emodirs = array( 0 => '');
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . '/public/style_emoticons' ) as $_emoticon )
 			{
 				if ( $_emoticon->isDir() AND ! $_emoticon->isDot() AND $_emoticon->getFilename() != '.svn' )
 				{
					if ( is_dir( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $_emoticon ) )
					{
						if( $_emoticon->getFilename() == 'default' )
						{
							$emodirs[0] = $_emoticon->getFilename();
						}
						else
						{
							$emodirs[] = $_emoticon->getFilename();
						}
					}
 				}
 			}
		} catch ( Exception $e ) {}
		
 		/* Loop through the emoticon directories */
 		$rows   = array();
		$i 		= 0;
		$total 	= count($emodirs);
		
		foreach( $emodirs as $dir )
		{
			$i++;

			$data = array();
			
			$files 			= $this->_emoticonGetGolderContents( $dir );
			$data['count'] 	= intval( count($files) );
			
			if( $dir == 'default' )
			{
				$data['line_image'] = '';
				$data['link_text'] = $this->lang->words['emo_manageemos'];
			}
			else
			{
				$data['link_text'] = $this->lang->words['emo_setclickable'];
				
				if( $i == $total )
				{
					$data['line_image'] = "<img src='{$this->settings['skin_acp_url']}/images/skin_line_l.gif' border='0' />&nbsp;";
				}
				else
				{
					$data['line_image'] = "<img src='{$this->settings['skin_acp_url']}/images/skin_line_t.gif' border='0' />&nbsp;";
				}
			}
			
			if ( is_writeable( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $dir ) )
			{
				if( $dir == 'default' )
				{
					$checked_def = "checked='checked' disabled='disabled' ";
				}
				else
				{
					$checked_def = "";
				}
				
				$data['icon']     = 'icon_can_write.gif';
				$data['title']    = $this->lang->words['emo_writeable'];
				$data['checkbox'] = "<input type='checkbox' name='dir_{$dir}' {$checked_def}value='1' />";
			}
			else
			{
				$data['icon']     = 'icon_cannot_write.gif';
				$data['title']    = $this->lang->words['emo_writeable_not'];
				$data['checkbox'] = "-";
			}
			
			$data['dir'] = $dir;
			$data['dir_count'] = intval( $emo_db[ $dir ]['count'] );
			
			$rows[] = $data;
		}
		
		$this->registry->output->html .= $this->html->emoticonsOverview( $rows );
	}
	
	/**
	 * Rebuilds the emoticon cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function emoticonsRebuildCache()
	{
		$cache = array();
			
		$this->DB->build( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache[] = $r;
		}
		
		usort( $cache, array( $this, '_thisUsort' ) );

		ipsRegistry::cache()->setCache( 'emoticons', $cache, array( 'name' => 'emoticons', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Custom sort operation
	 *
	 * @access	private
	 * @param	string		A
	 * @param	string		B
	 * @return	integer
	 */
	private static function _thisUsort($a, $b)
	{
		if ( strlen($a['typed']) == strlen($b['typed']) )
		{
			return 0;
		}

		return ( strlen($a['typed']) > strlen($b['typed']) ) ? -1 : 1;
	}
	
	/**
	 * Gets all the emoticons from the specified directory
	 *
	 * @access	private
	 * @param	string	$folder	Emoticon folder to pull emoticons from
	 * @return	array
	 **/
	private function _emoticonGetGolderContents( $folder='default' )
	{
		$files = array();
		
		/* Loop through the emoticon directory */
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . '/public/style_emoticons/' . $folder ) as $_emo )
 			{
 				if( $_emo->isFile() && ! $_emo->isDot() )
 				{
					if ( preg_match( "/\.(?:gif|jpg|jpeg|png|swf)$/i", $_emo->getFilename() ) )
					{
						$files[] = $_emo->getFilename();
					}
 				}
 			}
		} catch ( Exception $e ) {}
 		 		
 		return $files;
 	}
}