<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Language Pack Management
 * Last Updated: $LastChangedDate: 2009-09-03 03:49:52 -0400 (Thu, 03 Sep 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 5081 $
 */
 
if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_languages_manage_languages extends ipsCommand
{
	/**
	 * Daily flag
	 *
	 * @access	public
	 * @var		bool
	 */
	private $__daily	= false;
	
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
		// Get skin and language file
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_system' );

		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		
		//-----------------------------------------
		// Set urls
		//-----------------------------------------
		
		$this->form_code    = $this->html->form_code = 'module=languages&amp;section=manage_languages';
		$this->form_code_js = $this->html->form_code_js = 'module=languages&section=manage_languages';	

		//-----------------------------------------
		// Go
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'copy_lang_pack':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageCopy( 'edit' );
			break;
			
			case 'edit_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageWordEntryForm( 'edit' );
			break;
			
			case 'do_edit_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->handleWordEntryForm( 'edit' );
			break;
			
			case 'add_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageWordEntryForm( 'add' );
			break;
			
			case 'do_add_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->handleWordEntryForm( 'add' );
			break;
			
			case 'list_word_packs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageListWordPacks();
			break;
			
			case 'edit_word_pack':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->languageEditWordPack();
			break;
			
			case 'do_edit_word_pack':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->languageEditWordPackValues();
			break;
			
			case 'edit_lang_info':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_pack_info' );
				$this->languageInformationForm( 'edit' );
			break;
			
			case 'do_edit_lang_info':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_pack_info' );
				$this->handleLanguageInformationForm( 'edit' );
			break;

			case 'new_language':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->languageInformationForm( 'new' );
			break;
			
			case 'do_new_language':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->handleLanguageInformationForm( 'new' );
			break;
			
			case 'revert':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageDoRevertWord();
			break;
			
			case 'export':
				$this->languageExportToXML( intval( $this->request['id'] ) );
			break;
			
			case 'language_do_import':
				$this->imprtFromXML( intval( $this->request['id'] ) );
			break;

			case 'language_do_indev_export':
				foreach( ipsRegistry::$applications as $app_dir => $app_data )
				{
					$this->request['app_dir']	= $app_dir;
					
					$this->request['type']	= 'admin';
					$this->languageExportToXML( 1, 1 );
					
					$this->request['type']	= 'public';
					$this->languageExportToXML( 1, 1 );
				}
				
				$this->registry->output->global_message = $this->lang->words['indev_lang_export_done'];
				$this->languagesList();
			break;
			
			case 'language_do_indev_import':
				$this->importFromCacheFiles();
			break;
			
			case 'remove_language':
				$this->languageRemove();
			break;
			
			case 'remove_word_entry':
				$this->removeWordEntry();
			break;
			
			case 'remove_word_pack':
				$this->removeWordPack();
			break;
			
			case 'rebuildFromXml':
				$this->rebuildFromXml();
			break;
			
			case 'recache_lang_pack':
				$this->recacheLangPack();
			break;
			
			case 'translateExtSplash':
				$this->translateExtSplash();
			break;
			
			case 'translateImport':
				$this->translateImport();
			break;
			
			case 'translateKill':
				$this->translateKill();
			break;
			
			default:
				$this->languagesList();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Finish a session and remove data
	 *
	 * @access	public
	 * @return	void
	 */
	public function translateKill()
	{
		/* INIT */
		$langId        = intval( $this->request['id'] );
		$mainDir       = DOC_IPS_ROOT_PATH . 'translate';
		$words_by_file = array();
		$errors        = array();
		$filesToImport = array();
		
		/* Start top message */
		if ( ! is_dir( $mainDir ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_dir'];
			$this->languagesList();
			return;
		}
		
		if ( ! is_writeable( $mainDir ) )
		{
			/* Just bounce back asking them to chmod translate */
			$this->registry->output->global_error = $this->lang->words['ext_chmod_translate_dir'];
			$this->languagesList();
			return;
		}
		
		/* Load kernel class for file management */
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );
		$classFileManagement = new classFileManagement();
		
		/* Remove files */
		$classFileManagement->emptyDirectory( $mainDir );
		
		/* Delete session */
		$session = $this->DB->delete( 'cache_store', 'cs_key=\'translate_session\'' );
		
		/* Done */
		$this->registry->output->global_message = $this->lang->words['ext_all_killed'];
		$this->languagesList();
		return;
	}
		
	/**
	 * Translate externally import changed files
	 *
	 * @access	public
	 * @return	void
	 */
	public function translateImport()
	{
		/* INIT */
		$langId        = intval( $this->request['id'] );
		$mainDir       = DOC_IPS_ROOT_PATH . 'translate';
		$words_by_file = array();
		$errors        = array();
		$filesToImport = array();
		
		/* Start top message */
		if ( ! is_dir( $mainDir ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_dir'];
			$this->languagesList();
			return;
		}
		
		if ( ! is_writeable( $mainDir ) )
		{
			/* Just bounce back asking them to chmod translate */
			$this->registry->output->global_error = $this->lang->words['ext_chmod_translate_dir'];
			$this->languagesList();
			return;
		}
		
		/* Load kernel class for file management */
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );
		$classFileManagement = new classFileManagement();
		
		/* Get file count */
		$fileCount = intval( count( $classFileManagement->fetchDirectoryContents( $mainDir ) ) );
		
		/* Get lang */
		$lang = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => 'lang_id=' . $langId ) );
		
		/* Get current session if one */
		$session      = $this->DB->buildandFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => 'cs_key=\'translate_session\'' ) );
		$sessionData = ( strstr( $session['cs_value'], 'a:' ) ) ? unserialize( $session['cs_value'] ) : array();
		
		/* Check */
		if ( empty( $sessionData['lang_id'] ) OR ! count( $sessionData['files'] ) OR ! count( $fileCount ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_files'];
			$this->languagesList();
			return;
		}
		
		/* Still here? Okay */
		if ( is_array( $_POST['cb'] ) AND count( $_POST['cb'] ) )
		{
			/* Gather a list of files to import */
			foreach( $_POST['cb'] as $file => $value )
			{
				if ( $_POST['cb'][ $file ] )
				{
					$filesToImport[ $file ] = $file;
				}
			}
			
			/* Assume nothing */
			if ( count( $filesToImport ) )
			{
				$counts = $this->_importFromDisk( $mainDir, $sessionData['lang_id'], array_keys( $filesToImport ), true );
				
				foreach( $counts as $file => $data )
				{
					/* Update session data */
					$sessionData['files'][ $file ]['dbtime'] = time();
					
					$this->registry->output->global_message = sprintf( $this->lang->words['ext_file_written'], $file, intval( $data['inserts'] ), intval( $data['updates'] ) );;
				}
				
				$this->registry->output->global_message .= '<br />' . sprintf( $this->lang->words['ext_recache'], "{$this->settings['base_url']}&{$this->form_code}&do=recache_lang_pack&id={$sessionData['lang_id']}" );
				
				/* Update session */
				$this->DB->update( 'cache_store', array( 'cs_value' => serialize( $sessionData ) ), 'cs_key=\'translate_session\'' );
				
				$this->translateExtSplash();
				return;
			}
		}
		else
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_selected_files'];
			$this->translateExtSplash();
			return;
		}
	}
	
	/**
	 * Translate externally splash
	 *
	 * @access	public
	 * @return	void
	 */
	public function translateExtSplash()
	{
		/* INIT */
		$langId        = intval( $this->request['id'] );
		$mainDir       = DOC_IPS_ROOT_PATH . 'translate';
		$words_by_file = array();
		$errors        = array();
		
		/* Start top message */
		if ( ! is_dir( $mainDir ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_dir'];
			$this->languagesList();
			return;
		}
		
		if ( ! is_writeable( $mainDir ) )
		{
			/* Just bounce back asking them to chmod translate */
			$this->registry->output->global_error = $this->lang->words['ext_chmod_translate_dir'];
			$this->languagesList();
			return;
		}
		
		/* Load kernel class for file management */
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );
		$classFileManagement = new classFileManagement();
		
		/* Get lang */
		$lang = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => 'lang_id=' . $langId ) );
		
		/* Get current session if one */
		$session      = $this->DB->buildandFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => 'cs_key=\'translate_session\'' ) );
		$sessionData = ( strstr( $session['cs_value'], 'a:' ) ) ? unserialize( $session['cs_value'] ) : array();
		
		/* No current session? */
		if ( empty( $sessionData['lang_id'] ) OR ! count( $sessionData['files'] ) )
		{
			/* Ensure directory is empty */
			$classFileManagement->emptyDirectory( $mainDir );
			
			/* Ensure it's gone, gone */
			$this->DB->delete( 'cache_store', 'cs_key=\'translate_session\'' );
			$sessionData          = $lang;
			$sessionData['files'] = array();
			$header               = "/*******************************************************\nNOTE: This is a translatable file generated by IP.Board " . IPB_VERSION . " (" . IPB_LONG_VERSION . ") on " . date( "r" ) . " by " . $this->memberData['members_display_name'] . "\nPLEASE set your text editor to save this document as UTF-8 regardless of your board's character-set\n*******************************************************/\n\n";
		
			/* Export all the languages into flat files */
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => 'lang_id=' . $langId, 'order' => 'word_custom_version DESC, word_default_version DESC' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$_text    = ( $r['word_custom'] ) ? $r['word_custom'] : $r['word_default'];
				$_version = ( $r['word_custom_version'] ) ? $r['word_custom_version'] : $r['word_default_version'];
				$words_by_file[$r['word_app']][$r['word_pack']][] = array( $r['word_key'], $_text );
			}
			
			//-----------------------------------------
			// Now loop and write to file
			//-----------------------------------------
			
			foreach( $words_by_file as $app => $word_packs )
			{			
				foreach( $word_packs as $pack => $words )
				{	
					if( $pack == 'public_js' )
					{
						$to_write	= '';
						$_file		= 'ipb.lang.js';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
						}					
					}
					else if( $pack == 'admin_js' )
					{
						$to_write	= '';
						$_file		= 'acp.lang.js';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
						}
					}
					else
					{
						//-----------------------------------------
						// Build cache file contents
						//-----------------------------------------
						
						$to_write	= "<?php\n\n$header\n\n\$lang = array( \n";
						$_file		= $app . '_' . $pack . '.php';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "'{$word[0]}'\t\t\t\t=> \"{$word[1]}\",\n";
						}
	
						$to_write .= " ); \n";					
					}
					
					//-----------------------------------------
					// Convert data
					//-----------------------------------------
					
					$to_write = IPSText::convertCharsets( $to_write, IPS_DOC_CHAR_SET, 'UTF-8' );
					
					//-----------------------------------------
					// Write the file
					//-----------------------------------------
					
					@unlink( $mainDir . '/' . $_file );
					
					if ( $fh = @fopen( $mainDir . '/' . $_file, 'wb' ) )
					{
						fwrite( $fh, $to_write, strlen( $to_write ) );
						fclose( $fh );
						@chmod( $mainDir . '/' . $_file, 0777 );
						
						$mtime = @filemtime( $mainDir . '/' . $_file );
						
						$sessionData['files'][ $_file ] = array( 'mtime' => $mtime, 'dbtime' => $mtime );
					}
					else
					{
						$errors[] = $this->lang->words['l_nowrite'] .  $mainDir . '/' . $_file;
					}
				}
			}
			
			/* Sort files */
			ksort( $sessionData['files'] );
			
			/* Save session */
			$this->DB->insert( 'cache_store', array( 'cs_key'     => 'translate_session',
													   'cs_value'   => serialize( $sessionData ),
													   'cs_extra'   => '',
													   'cs_array'   => 1,
													   'cs_updated' => time() ) );
		
		}
		else
		{
			/* Update mtime */
			foreach( $sessionData['files'] as $file => $data )
			{
				$sessionData['files'][ $file ]['mtime'] =  @filemtime( $mainDir . '/' . $file );
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=translateExtSplash&id={$sessionData['lang_id']}", "{$this->lang->words['ext_title_for']} {$sessionData['lang_title']}" );
		
		$this->registry->output->html .= $this->html->languages_translateExt( $sessionData, $lang );
	}
	
	/**
	 * Import files from disk
	 *
	 * @access 	Private
	 * @param	string		Directory to look in
	 * @param	int			Language ID to import into
	 * @param	array 		[Only import these files]
	 * @param	boolean		[Perform character set translation from UTF-8]
	 * @return	array		List of files successfully imported
	 */
	 private function _importFromDisk( $mainDir, $langId, $onlyTheseFiles=array(), $convertCharSet=false )
	 {
	 	/* INIT */
	 	$imported     = array();
	    $lang_entries = array();
	 	$counts		  = array();
	 	
		/* Start looping */
	 	if ( is_dir( $mainDir ) )
		{
			$dh = opendir( $mainDir );
						
			/* Ensure it has a trailing slash */
			if ( substr( $mainDir, -1 ) != '/' )
			{
				$mainDir .= '/';
			}
			
			while( $f = readdir( $dh ) )
			{
				if ( $f[0] == '.' || $f == 'index.html' )
				{
					continue;
				}
				
				/* Skipping? */
				if ( is_array( $onlyTheseFiles ) AND count( $onlyTheseFiles ) AND ! in_array( $f, $onlyTheseFiles ) )
				{
					continue;
				}
										
				if ( preg_match( "#^\S+?_\S+?\.php$#", $f ) )
				{
					//-----------------------------------------
					// INIT
					//-----------------------------------------
					
					$updated	= 0;
					$inserted	= 0;
					$app		= preg_replace( "#^([^_]+?)_(\S+?)\.php$#", "\\1", $f );
					$word_pack	= preg_replace( "#^([^_]+?)_(\S+?)\.php$#", "\\2", $f );
					$lang		= array();
					$db_lang	= array();
					
					$counts[ $f ] = array();
					
					if ( ! file_exists( $mainDir . $f ) )
					{
						continue;
					}
					
					/* Require the file */
					require( $mainDir . $f );
					
					//-----------------------------------------
					// Loop
					//-----------------------------------------

					foreach( $lang as $k => $v )
					{
						//-----------------------------------------
						// Build db array
						//-----------------------------------------
						
						$db_array = array(
											'lang_id'				=> $langId,
											'word_app'				=> $app,
											'word_pack'				=> $word_pack,
											'word_key'				=> $k,
											'word_custom'			=> IPSText::convertCharsets($v, 'UTF-8', IPS_DOC_CHAR_SET ),
											'word_js'				=> 0
										);	
		
						//-----------------------------------------
						// If cached, get from cache
						//-----------------------------------------
						
						if( $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ] )
						{
							$lang_entry	= $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ];
						}
						
						//-----------------------------------------
						// Otherwise get all langs from this entry and
						// put in cache
						//-----------------------------------------
						
						else
						{
							$this->DB->build( array(
													'select'	=> '*',
													'from'		=> 'core_sys_lang_words',
													'where'		=> "lang_id={$langId} AND word_app='{$db_array['word_app']}' AND word_pack='{$db_array['word_pack']}'"
												)		);
							$this->DB->execute();
							
							while( $r = $this->DB->fetch() )
							{
								$lang_entries[ $r['lang_id'] ][ $r['word_app'] ][ $r['word_pack'] ][ $r['word_key'] ]	= $r;
							}
							
							if( $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ] )
							{
								$lang_entry	= $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ];
							}
						}
						
						/* Finish off */
						$db_array['word_default']         = $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ]['word_default'];
						$db_array['word_default_version'] = $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ]['word_default_version'];
						$db_array['word_custom_version']  = IPB_LONG_VERSION;
						
						//-----------------------------------------
						// If there is no new custom lang bit to insert
						// don't delete what is already there.
						//-----------------------------------------
						
						if( ! $db_array['word_custom'] )
						{
							unset($db_array['word_custom']);
						}
		
						//-----------------------------------------
						// Lang bit already exists, update
						//-----------------------------------------
						
						if( $lang_entry['word_id'] )
						{
							//-----------------------------------------
							// Don't update default version
							//-----------------------------------------
							
							unset( $db_array['word_default_version'] );
							
							$counts[ $f ]['updates']++;
							$this->DB->update( 'core_sys_lang_words', $db_array, "word_id={$lang_entry['word_id']}" );
						}
						
						//-----------------------------------------
						// Lang bit doesn't exist, so insert
						//-----------------------------------------
						
						else if( !$lang_entry['word_id'] )
						{
							$counts[ $f ]['inserts']++;
							$this->DB->insert( 'core_sys_lang_words', $db_array );
						}
					}
				}
				else if( preg_match( '/(\.js)$/', $f ) )
				{
					$_js_word_pack	= '';
					
					if( $f == 'ipb.lang.js' )
					{
						$_js_word_pack	= 'public_js';
					}
					else if( $f == 'acp.lang.js' )
					{
						$_js_word_pack	= 'admin_js';
					}
					
					//-----------------------------------------
					// Delete current words for this app and word pack
					//-----------------------------------------
					
					$this->DB->delete( 'core_sys_lang_words', 'lang_id=' . $langId .' AND word_app="core" AND word_pack=\'' . $_js_word_pack . '\'' );
					
					//-----------------------------------------
					// Get each line
					//-----------------------------------------
					
					$js_file = file( $mainDir . $f );
					
					//-----------------------------------------
					// Loop through lines and import
					//-----------------------------------------
					
					foreach( $js_file as $r )
					{
						//-----------------------------------------
						// preg_match what we want
						//-----------------------------------------
						
						preg_match( "#ipb\.lang\['(.+?)'\](.+?)= [\"'](.+?)[\"'];#", $r, $matches );

						//-----------------------------------------
						// Valid?
						//-----------------------------------------
						
						if( $matches[1] && $matches[3] )
						{
							$counts[ $f ]['inserts']++;
							$insert = array(
												'lang_id'      => $langId,
												'word_app'     => 'core',
												'word_pack'    => $_js_word_pack,
												'word_key'     => $matches[1],
												'word_default' => IPSText::convertCharsets($matches[3], 'UTF-8', IPS_DOC_CHAR_SET ),
												'word_js'      => 1,
											);
							$this->DB->insert( 'core_sys_lang_words', $insert );
						}
					}
				}
			}

			closedir( $dh );
		}
		
		return $counts;
	 }


	/**
	 * Recaches a language pack
	 *
	 * @access	public
	 * @return	void
	 */
	public function recacheLangPack()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->cache_errors = array();
		
		$lang_id = intval( $this->request['id'] );
		
		$this->cacheToDisk( $lang_id );
		
		$this->registry->output->global_message = $this->lang->words['language_recache_done'] . "<br />" . implode( "<br />", $this->cache_errors );
		$this->languagesList();
	}
	
	
	/**
	 * Remove a word entry
	 *
	 * @access	public
	 * @return	void
	 */
	public function removeWordEntry()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$word_id	= intval($this->request['word_id']);
		$lang_id	= intval( $this->request['id'] );
		
		//-----------------------------------------
		// Delete lang bit
		//-----------------------------------------
		
		$this->DB->delete( 'core_sys_lang_words', 'word_id=' . $word_id );
		
		//-----------------------------------------
		// Recache to disk
		//-----------------------------------------
		
		$this->cacheToDisk( $lang_id );
		
		//-----------------------------------------
		// Bounce to new URL
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->registry->adminFunctions->generated_acp_hash;
		$this->registry->output->global_message = $this->lang->words['language_word_removed'];
		$this->languageEditWordPack();
	}
	
	/**
	 * Remove a word pack
	 *
	 * @access	public
	 * @return	void
	 */
	public function removeWordPack()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$pack		= explode( '/', $this->request['word_pack'] );
		$lang_id	= intval( $this->request['id'] );
		
		if( $lang_id )
		{
			//-----------------------------------------
			// Delete from DB
			//-----------------------------------------
			
			$this->DB->delete( 'core_sys_lang_words', "lang_id='{$lang_id}' AND word_pack='" . $pack[1] . "' AND word_app='" . $pack[0] . "'" );
			
			//-----------------------------------------
			// Delete from disk
			//-----------------------------------------
			
			$_file	= IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/' . $pack[0] . '_' . $pack[1] . '.php';
			
			if( file_exists( $_file ) )
			{
				@unlink( $_file );
			}
			
			//-----------------------------------------
			// And recache
			//-----------------------------------------
			
			$this->cacheToDisk( $lang_id );
		}
		
		//-----------------------------------------
		// Bounce back
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->registry->adminFunctions->generated_acp_hash;
		$this->registry->output->global_message = $this->lang->words['language_wordpack_removed'];
		$this->languageListWordPacks();
	}
	
	/**
	 * Rebuilds language from XML files
	 *
	 * @return void
	 */
	public function rebuildFromXml()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$apps		= array();
		$previous	= trim( $this->request['previous'] );
		$type		= trim( $this->request['type'] );
		$id			= intval($this->request['id']);
		$_word		= ( $type == 'admin' ) ? 'admin' : 'public';
		
		//-----------------------------------------
		// Verify writable
		//-----------------------------------------
		
		if ( ! is_writeable( IPS_CACHE_PATH . 'cache/lang_cache/' . $id ) )
		{
			$this->registry->output->global_message = "Cannot write to cache/lang_cache/" . $id;
			$this->languagesList();
			return;
		}
				
		//-----------------------------------------
		// Get setup class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . "setup/sources/base/setup.php" );
		
		//-----------------------------------------
		// Get apps
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $appDir => $appData )
		{
			$apps[] = $appDir;
		}
		
		//-----------------------------------------
		// Klude for setup class
		//-----------------------------------------
		
		IPSSetUp::setSavedData( 'install_apps', implode( ',', $apps ) );
		
		//-----------------------------------------
		// Get next app
		//-----------------------------------------
		
		$next = IPSSetUp::fetchNextApplication( $previous );
		
		if ( $next['key'] )
		{
			$msg	= $next['title'] . sprintf( $this->lang->words['importing_x_langs'], $_word );
			$_PATH  = IPSLib::getAppDir( $next['key'] ) .  '/xml/';
		
			//-----------------------------------------
			// Try to import all the lang packs
			//-----------------------------------------
			
			try
			{
				foreach( new DirectoryIterator( $_PATH ) as $f )
				{
					if ( preg_match( "#" . $_word . "_(.+?)_language_pack.xml#", $f->getFileName() ) )
					{
						$this->request['file_location'] = $_PATH . $f->getFileName();
						$this->imprtFromXML( 1, true, true, $next['key'] );
					}
				}
			} catch ( Exception $e ) {}

			//-----------------------------------------
			// Off to next setp
			//-----------------------------------------
			
			$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=rebuildFromXml&id={$id}&type={$type}&previous=" . $next['key'], $msg );
		}
		else
		{
			if ( $type == 'public' )
			{
				//-----------------------------------------
				// Onto admin languages
				//-----------------------------------------

				$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=rebuildFromXml&id={$id}&type=admin", $this->lang->words['starting_admin_import'] );
			}
			else
			{
				//-----------------------------------------
				// And we're done
				//-----------------------------------------

				$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages", $this->lang->words['lang_reimport_done'] );
			}
		}
	}
	
	/**
	 * Copies a language pack
	 *
	 * @return void
	 */
	public function languageCopy()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Get lang pack
		//-----------------------------------------
		
		$lang_info = $this->DB->buildAndFetch( array( 'select' => 'lang_short, lang_title, lang_isrtl', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );
		
		$lang_info['lang_title'] .= " (COPY)";
		
		//-----------------------------------------
		// Insert language pack
		//-----------------------------------------
		
		$this->DB->insert( 'core_sys_lang', $lang_info );
		$new_id	= $this->DB->getInsertID();
		
		//-----------------------------------------
		// Copy the language bits now
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$id}" ) );
		$q = $this->DB->execute();

		while( $r = $this->DB->fetch( $q ) )
		{
			unset( $r['word_id'] );
			$r['lang_id'] = $new_id;
			
			$this->DB->insert( 'core_sys_lang_words', $r );
		}

		//-----------------------------------------
		// Recache and redirect
		//-----------------------------------------
		
		$this->cacheToDisk( $new_id );

		$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages", $this->lang->words['l_copied'] );
	}
	
	/**
	 * Removes a language pack and cleans up files
	 *
	 * @return void
	 */
	public function languageRemove()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );

		//-----------------------------------------
		// Make sure this isn't default pack
		//-----------------------------------------
		
		$default	= $this->DB->buildAndFetch( array( 'select' => 'lang_id', 'from' => 'core_sys_lang', 'where' => 'lang_default=1' ) );
		
		if( $id == $default['lang_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_delete_default_lang'] );
		}

		//-----------------------------------------
		// Delete from database
		//-----------------------------------------
		
		$this->DB->delete( 'core_sys_lang'      , "lang_id={$id}" );
		$this->DB->delete( 'core_sys_lang_words', "lang_id={$id}" );
		
		//-----------------------------------------
		// Delete from disk
		//-----------------------------------------
		
		$this->registry->adminFunctions->removeDirectory( IPS_CACHE_PATH . 'cache/lang_cache/' . $id . '/' );
		
		//-----------------------------------------
		// Update member default choice
		//-----------------------------------------

		$this->DB->update( 'members', array( 'language' => $default['lang_id'] ), "language={$id}" );
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
		
		$this->registry->class_localization->rebuildLanguagesCache();
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages", $this->lang->words['l_removed'] );
	}
	
	/**
	 * Revert a word pack entry to the default value
	 *
	 * @access	public
	 * @return	void
	 */
	public function languageDoRevertWord()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$word_id	= intval( $this->request['word_id'] );
		$lang_id	= intval( $this->request['id'] );
		$pack		= explode( '/', $this->request['word_pack'] );		
		
		//-----------------------------------------
		// Revert
		//-----------------------------------------
		
		$this->DB->update( 'core_sys_lang_words', array( 'word_custom' => '' ), "word_id={$word_id}" );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->cacheToDisk( $lang_id );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->registry->adminFunctions->generated_acp_hash;
		$this->registry->output->global_message = $this->lang->words['language_word_revert'];
		$this->languageEditWordPack();
	}	
	
	/**
	 * Saves new language edits
	 *
	 * @access	public
	 * @return	void
	 */	
	public function languageEditWordPackValues()
	{			
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );

		//-----------------------------------------
		// Loop through language bits submitted
		//-----------------------------------------
		
		if( is_array( $_POST['lang'] ) && count( $_POST['lang'] ) )
		{
			foreach( $_POST['lang'] as $k => $v )
			{
				if( $v )
				{
					$v	= IPSText::safeSlashes($v);
					$v	= str_replace( '&#092;', '\\', $v );
					
					$this->DB->update( 'core_sys_lang_words', "word_custom='".$this->DB->addSlashes( $v )."', word_custom_version=word_default_version", "word_id=" . intval($k), false, true );
				}
			}
		}
		
		//-----------------------------------------
		// Recache and redirect
		//-----------------------------------------
		
		$this->cacheToDisk( $id );

		$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=edit_word_pack&word_pack={$this->request['pack']}&id={$this->request['id']}&search={$this->request['search']}&filter={$this->request['filter']}&st={$this->request['st']}", $this->lang->words['language_word_pack_edited'] );
	}
		
	/**
	 * Handles the word entry form
	 *
	 * @access	public
	 * @param	string	$mode	Either add or edit
	 * @return	void
	 */
	public function handleWordEntryForm( $mode='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$lang_id		= intval( $this->request['id'] );	
		$word_id		= intval( $this->request['word_id'] );	
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if( ! $this->request['word_pack_db'] )
		{
			$this->registry->output->global_message = $this->lang->words['l_packreq'];
			$this->languageWordEntryForm( $mode );
			return;
		}
		
		if( ! $this->request['word_key'] )
		{
			$this->registry->output->global_message = $this->lang->words['l_keyreq'];
			$this->languageWordEntryForm( $mode );
			return;
		}
		
		if( ! $this->request['word_default'] )
		{
			$this->registry->output->global_message = $this->lang->words['l_textreq'];
			$this->languageWordEntryForm( $mode );
			return;
		}
		
		$this->request['word_app']		= strtolower($this->request['word_app']);
		$this->request['word_pack_db']	= str_replace( '/', '_', strtolower($this->request['word_pack_db']) );
		
		$this->DB->build( array( 
								'select'	=> 'word_id', 
								'from'		=> 'core_sys_lang_words',
								'where'		=> "lang_id={$lang_id} AND word_app='{$this->request['word_app']}' AND word_pack='{$this->request['word_pack_db']}' and word_key='{$this->request['word_key']}'" 
						)	);
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() )
		{
			$this->registry->output->global_message = $this->lang->words['l_keydup'];
			$this->languageWordEntryForm( $mode );
			return;	
		}
		
		//-----------------------------------------
		// Build DB insert array
		//-----------------------------------------
		
		$db_array = array(
							'lang_id'             => $lang_id,
							'word_app'            => $this->request['word_app'],
							'word_pack'           => $this->request['word_pack_db'],
							'word_key'            => $this->request['word_key'],
							'word_default'        => $this->request['word_default'],
						);
						
		//-----------------------------------------
		// Add or update
		//-----------------------------------------
		
		if( $mode == 'add' )
		{
			$this->DB->insert( 'core_sys_lang_words', $db_array );
			
			$text	= $this->lang->words['l_added'];
		}
		else 
		{
			$this->DB->update( 'core_sys_lang_words', $db_array, "word_id={$word_id}" );
			
			$text	= $this->lang->words['l_updated'];
		}
		
		//-----------------------------------------
		// Recache and redirect
		//-----------------------------------------
		
		$this->cacheToDisk( $lang_id );
		
		$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=list_word_packs&id={$lang_id}", $text );
	}	
	
	/**
	 * Form for adding/editing a word entry
	 *
	 * @access	public
	 * @param	string	$mode	Either add or edit
	 * @return	void
	 */	
	public function languageWordEntryForm( $mode='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$word_id	= intval( $this->request['word_id'] );
		$lang_id	= intval( $this->request['id'] );
		$pack		= explode( '/', $this->request['word_pack'] );
		
		//-----------------------------------------
		// Adding or editing
		//-----------------------------------------
		
		if( $mode == 'add' )
		{
			$op     = 'do_add_word_entry';
			$title  = $this->lang->words['l_addnew'];
			$header = $this->lang->words['l_addnewfull'];
			$button = $this->lang->words['l_addthis'];			
			$data   = array( 'word_pack' => $pack[1] );
		}
		else 
		{
			$op     = 'do_edit_word_entry';
			$title  = $this->lang->words['l_edit'];
			$header = $this->lang->words['l_editentry'];
			$button = $this->lang->words['l_savechanges'];
			
			//-----------------------------------------
			// Get data
			//-----------------------------------------
			
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "word_id={$word_id}" ) );
		}

		//-----------------------------------------
		// Set defaults
		//-----------------------------------------
		
		$data['word_app']		= ( isset( $this->request['word_app'] )     && $this->request['word_app'] )     ? $this->request['word_app']     : $data['word_app'];
		$data['word_pack']		= ( isset( $this->request['word_pack_db'] ) && $this->request['word_pack_db'] ) ? $this->request['word_pack_db'] : $data['word_pack'];
		$data['word_key']		= ( isset( $this->request['word_key'] )     && $this->request['word_key'] )     ? $this->request['word_key']     : $data['word_key'];
		$data['word_default']	= ( isset( $this->request['word_default'] ) && $this->request['word_default'] ) ? $this->request['word_default'] : $data['word_default'];

		//-----------------------------------------
		// Applications dropdown
		//-----------------------------------------
		
		$_apps = array();
		
		foreach( ipsRegistry::$applications as $app => $appdata )
		{
			$_apps[] = array( $app, $appdata['app_title'] );
		}
		
		$data['word_app'] = $this->registry->output->compileSelectOptions( $_apps, $data['word_app'] );
		
		//-----------------------------------------
		// Output form
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->languageWordEntryForm( $op, $word_id, $lang_id, $title, $header, $data, $button );
	}	
	
	/**
	 * Edit the entries in a word pack
	 *
	 * @access	public
	 * @return	void
	 */
	public function languageEditWordPack()
	{	
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		/* Fix up for search */
		$this->request['search'] = str_replace( "&#39;", "\'", $this->request['search'] );

		$id			= intval( $this->request['id'] );
		$pack		= explode( '/', $this->request['word_pack'] );
		$per_page	= 20;
		$st			= $this->request['st']     ? intval( $this->request['st'] ) : 0;
		$search		= $this->request['search'] ? " AND word_default LIKE '%" . $this->request['search'] . "%' OR word_custom LIKE '%" . $this->request['search'] . "%'" : '';
		$filter		= $this->request['filter'] ? ' AND word_custom_version < word_default_version AND word_custom <> \'\' ' : '';
		$wp_query	= $pack[0] && $pack[1]     ? " AND word_app='{$pack[0]}' AND word_pack='{$pack[1]}' " : '';
		
		//-----------------------------------------
		// Get language pack
		//-----------------------------------------
		
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );
				
		//-----------------------------------------
		// How many words?
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 
												'select' => 'COUNT(*) as count',
												'from'   => 'core_sys_lang_words',
												'where'  => "lang_id={$id} {$wp_query} {$search} {$filter}"
										)	 );

		//-----------------------------------------
		// Pagination
		//-----------------------------------------
		
		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'        => intval( $count['count'] ),
																	'itemsPerPage'      => $per_page,
																	'currentStartValue' => $st,
																	'baseUrl'           => "{$this->settings['base_url']}{$this->form_code}&do=edit_word_pack&word_pack=".implode( '/', $pack )."&id={$id}&search={$this->request['search']}&filter={$this->request['filter']}",
												 			)      );

		//-----------------------------------------
		// Get the words
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select' => '*', 
								'from'   => 'core_sys_lang_words', 
								'where'  => "lang_id={$id} {$wp_query} {$search} {$filter}",
								'limit'  => array( $st, $per_page ),
						)	);
		$this->DB->execute();
		
		$lang = array();
		
		while( $r = $this->DB->fetch() )
		{
			$lang[] = array(
								'id'      => $r['word_id'],
								'default' => nl2br( htmlspecialchars( $r['word_default'], ENT_QUOTES ) ),
								'custom'  => htmlspecialchars( $r['word_custom'], ENT_QUOTES ),
								'pack'    => $r['word_app'] . '/' . $r['word_pack'],
								'key'	  => $r['word_key']
							);
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&do=list_word_packs&id={$id}", $data['lang_title'] );
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['language_word_pack_edit'] );
		$this->registry->output->html .= $this->html->languageWordPackEdit( $id, $lang, $pages );
	}	
	
	/**
	 * List the word packs available for the selected language set
	 *
	 * @access	public
	 * @return	void
	 * @author	Josh
	 */
	public function languageListWordPacks()
	{		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );

		//-----------------------------------------
		// Get language pack
		//-----------------------------------------
		
		$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );

		//-----------------------------------------
		// Get words
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$id}" ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Some init before looping
		//-----------------------------------------
		
		$_packs   = array();
		$_missing = array();
		$_stats   = array();
		$_menus   = array();

		while( $r = $this->DB->fetch() )
		{	
			//-----------------------------------------
			// Create our array
			//-----------------------------------------
			
			$_stats[$r['word_app']]								= isset( $_stats[$r['word_app']] ) ? $_stats[$r['word_app']] : array();
			$_stats[$r['word_app']][$r['word_pack']]			= isset( $_stats[$r['word_app']][$r['word_pack']] ) ? $_stats[$r['word_app']][$r['word_pack']] : array();
			$_stats[$r['word_app']][$r['word_pack']]['total']	= isset( $_stats[$r['word_app']][$r['word_pack']]['total'] ) ? $_stats[$r['word_app']][$r['word_pack']]['total'] : 0;

			if( ! isset( $_packs[$r['word_app']] ) )
			{
				$_packs[$r['word_app']] = array();	
			}
			
			//-----------------------------------------
			// Add this language pack to array
			//-----------------------------------------
			
			if( ! in_array( $r['word_pack'], $_packs[$r['word_app']] ) )
			{
				$_packs[$r['word_app']][] = $r['word_pack'];
				$_menus[$r['word_app']][$r['word_pack']] = $this->registry->output->buildJavascriptMenu( array(
																						array( "{$this->settings['base_url']}{$this->form_code}&word_pack={$r['word_app']}/{$r['word_pack']}&do=edit_word_pack&id={$id}", $this->lang->words['edit'], 'edit' ),
																						array( "{$this->settings['base_url']}{$this->form_code}&word_pack={$r['word_app']}/{$r['word_pack']}&do=add_word_entry&id={$id}&word_app={$r['word_app']}", "{$this->lang->words['l_addnew']}...", 'add' ),
																						array( "{$this->settings['base_url']}{$this->form_code}&word_pack={$r['word_app']}/{$r['word_pack']}&do=remove_word_pack&id={$id}&word_app={$r['word_app']}", "{$this->lang->words['l_remove_pack']}...", 'delete' ),
																				)	 );
			}
			
			//-----------------------------------------
			// Update stats
			//-----------------------------------------
			
			$_stats[$r['word_app']][$r['word_pack']]['total']++;

			if( $r['word_custom'] )
			{
				$_stats[$r['word_app']][$r['word_pack']]['custom']++;
				
				if( $r['word_custom_version'] < $r['word_default_version'] )
				{
					$_stats[$r['word_app']][$r['word_pack']]['outofdate']++;
				}				
			}
		}		
		
		//-----------------------------------------
		// Loop through applications
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app => $data )
		{
			//-----------------------------------------
			// Check if app has langs
			//-----------------------------------------
			
			if( isset( $_packs[$app] ) && count( $_packs[$app] ) )
			{
				$default_tab = ( $app == $this->request['app'] ) ? 1 : 0;
				asort($_packs[$app]);
				
				$this->registry->output->addTab( $data['app_title'], $this->html->languageAppPackList( $app, $_packs[$app], $_stats[$app], $_menus[$app] ), '', $default_tab );
			}
		}
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->languageWordPackList( $id, $this->registry->output->buildTabs(), $data['app_title'] );
	}	
	
	/**
	 * Handles the language information form submit
	 *
	 * @access	public
	 * @param	string	$mode	Either new or edit
	 * @return	void
	 */
	public function handleLanguageInformationForm( $mode='new' )
	{
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		$errors = array();
		
		if( ! $this->request['lang_title'] )
		{
			$errors[] = '<li>' . $this->lang->words['language_title_missing'] . '</li>';
		}
		
		if( ! $this->request['lang_short'] )
		{
			$errors[] = '<li>' . $this->lang->words['language_locale_missing'] . '</li>';
		}
		
		if( count( $errors ) )
		{
			$this->registry->output->global_message = '<ul>'. implode( '', $errors ).'</ul>';
			$this->lang_info_form( $mode );
			return;
		}
		
		//-----------------------------------------
		// Build insert array
		//-----------------------------------------
		
		$db_array = array(
							'lang_title'   => $this->request['lang_title'],
							'lang_short'   => $this->request['lang_short'],
							'lang_default' => $this->request['lang_default'],
							'lang_isrtl'   => $this->request['lang_isrtl'],
						);
						
		//-----------------------------------------
		// Adding or editing
		//-----------------------------------------
		
		if( $mode == 'new' )
		{
			//-----------------------------------------
			// Insert and get id
			//-----------------------------------------
			
			$this->DB->insert( 'core_sys_lang', $db_array );

			$id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Create directory
			//-----------------------------------------
			
			@mkdir( IPS_CACHE_PATH . 'cache/lang_cache/' . $id, 0777 );
			@file_put_contents( IPS_CACHE_PATH . 'cache/lang_cache/' . $id . '/index.html', '' );
			@chmod( IPS_CACHE_PATH . 'cache/lang_cache/' . $id, 0777 );
			
			//-----------------------------------------
			// Copy over language bits from default lang
			//-----------------------------------------
			
			$default	= $this->DB->buildAndFetch( array( 'select' => 'lang_id', 'from' => 'core_sys_lang', 'where' => "lang_default=1" ) );
			
			$this->DB->build( array( 'select' => 'word_app,word_pack,word_key,word_default', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$default['lang_id']}" ) );
			$q = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $q ) )
			{
				$r['lang_id'] = $id;
				$this->DB->insert( 'core_sys_lang_words', $r );
			}
						
			//-----------------------------------------
			// Rebuild IPB and disk caches
			//-----------------------------------------
			
			$this->registry->class_localization->rebuildLanguagesCache();
			$this->cacheToDisk($id);
			
			//-----------------------------------------
			// Show listing
			//-----------------------------------------
			
			$this->registry->output->global_message = $this->lang->words['language_pack_created'];
			$this->languagesList();						
		}
		else 
		{
			//-----------------------------------------
			// Check ID and update
			//-----------------------------------------
			
			$id = intval( $this->request['id'] );

			$this->DB->update( 'core_sys_lang', $db_array, "lang_id={$id}" );
			
			//-----------------------------------------
			// If we set this lang default, make sure
			// no others are set as default
			//-----------------------------------------
			
			if( $db_array['lang_default'] )
			{
				$this->DB->update( 'core_sys_lang', array( 'lang_default' => 0 ), "lang_id<>{$id}" );
			}
			
			//-----------------------------------------
			// Rebuild cache and show list
			//-----------------------------------------
			
			$this->registry->class_localization->rebuildLanguagesCache();
			$this->registry->output->global_message = $this->lang->words['language_pack_updated'];
			$this->languagesList();
		}
	}	
	
	/**
	 * Builds the language information form
	 *
	 * @access	public
	 * @param	string	$mode	new or edit
	 * @return	void
	 */	
	public function languageInformationForm( $mode='new' )
	{
		//-----------------------------------------
		// Adding or editing
		//-----------------------------------------
		
		if( $mode == 'new' )
		{
			$title	= $this->lang->words['language_form_new_title'];
			$button	= $this->lang->words['language_form_new_button'];
			$op		= 'do_new_language';
			$header	= $this->lang->words['language_form_new_info'];
			$data	= array(0);
			$id		= 0;		
		}
		else 
		{
			$title	= $this->lang->words['language_form_edit_title'];
			$button	= $this->lang->words['language_form_edit_button'];
			$op		= 'do_edit_lang_info';
			$header	= $this->lang->words['language_form_edit_info'];
			$id		= intval( $this->request['id'] );

			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['invalid_id'], 11147 );
			}	
			
			//-----------------------------------------
			// Get language pack info
			//-----------------------------------------
			
			$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );
		}
		
		//-----------------------------------------
		// Set some defaults
		//-----------------------------------------
		
		$data['lang_title']		= ( isset( $this->request['lang_title'] )   && $this->request['lang_title'] )   ? $this->request['lang_title']   : $data['lang_title'];
		$data['lang_short']		= ( isset( $this->request['lang_short'] )   && $this->request['lang_short'] )   ? $this->request['lang_short']   : $data['lang_short'];
		$data['lang_default']	= ( isset( $this->request['lang_default'] ) && $this->request['lang_default'] ) ? $this->request['lang_default'] : $data['lang_default'];
		$data['lang_isrtl']		= ( isset( $this->request['lang_isrtl'] )   && $this->request['lang_isrtl'] )   ? $this->request['lang_isrtl']   : $data['lang_isrtl'];

		$data['lang_default']	= $this->registry->output->formYesNo( 'lang_default', $data['lang_default'] );
		$data['lang_isrtl']		= $this->registry->output->formYesNo( 'lang_isrtl', $data['lang_isrtl'] );
		
		//-----------------------------------------
		// Show form
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( '', $title );
		$this->registry->output->html .= $this->html->languageInformationForm( $op, $id, $title, $header, $data, $button );
	}	
	
	/**
	 * Lists currently installed languages
	 *
	 * @access	public
	 * @return	void
	 */
	public function languagesList()
	{
		/* Do we have a valid translation session? */
		$session      = $this->DB->buildandFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => 'cs_key=\'translate_session\'' ) );
		$sessionData  = ( strstr( $session['cs_value'], 'a:' ) ) ? unserialize( $session['cs_value'] ) : array();
		$hasTranslate = false;
		
		/* Check */
		if ( ! empty( $sessionData['lang_id'] ) AND count( $sessionData['files'] ) )
		{
			$hasTranslate = true;
		}

		//-----------------------------------------
		// Get languages
		//-----------------------------------------
		
		$rows = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			/* Get Local Data */
			setlocale( LC_ALL, $r['lang_short'] );
			$this->registry->class_localization->local_data = localeconv();
			
			$_menu = array();
			
			$_menu[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=edit_lang_info&id={$r['lang_id']}", $this->lang->words['edit'], 'edit' );
			$_menu[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=list_word_packs&id={$r['lang_id']}", $this->lang->words['language_list_translate'], 'info' );
			
			/* If we don't have a current session... */
			if ( ! $hasTranslate )
			{
				$_menu[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=translateExtSplash&id={$r['lang_id']}", $this->lang->words['language_list_translate_ext'], 'info' );
			}
			
			if( ! $r['lang_default'] )
			{
				$_menu[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=remove_language&id={$r['lang_id']}", $this->lang->words['delete'], 'delete', 1 );
			}
			
			$_menu[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=copy_lang_pack&id={$r['lang_id']}", $this->lang->words['language_list_copy'] );
			$_menu[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=recache_lang_pack&id={$r['lang_id']}", $this->lang->words['language_list_recache'] );
			
			$_menu[] = array( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=export&id={$r['lang_id']}", $this->lang->words['l_xmlexportfull'] );
			
			if ( $r['lang_id'] )
			{
				$_menu[] = array( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=rebuildFromXml&id={$r['lang_id']}&type=public", $this->lang->words['rebuild_lang_from_xml'] );
			}
			
			foreach( ipsRegistry::$applications as $app_dir => $app_data )
			{
				$_menu[] = array( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=export&id={$r['lang_id']}&app_dir={$app_dir}", $this->lang->words['l_xmlexport'] . $app_data['app_title'] );
			}

			$menu = $this->registry->output->buildJavascriptMenu( $_menu );
			
			//-----------------------------------------
			// Data for output
			//-----------------------------------------
			
			$rows[] = array(
								'title'		=> $r['lang_title'],
								'local'		=> $r['lang_short'],
								'date'		=> $this->registry->class_localization->getDate( time(), 'long', 1 ) . '<br />' . $this->registry->class_localization->getDate( time(), 'short', 1 ),
								'money'		=> $this->registry->class_localization->formatMoney( '12345231.12', 0 ),
								'default'	=> ( $r['lang_default'] ) ? "<img src='{$this->settings['skin_acp_url']}/_newimages/icons/tick.png' alt='{$this->lang->words['yes']}' />" : '',
								'menu'		=> $menu,
								'id'		=> $r['lang_id'],
							);
		}
		
		//-----------------------------------------
		// Reset locale
		//-----------------------------------------
		
		setlocale( LC_ALL, $this->registry->class_localization->local );
		$this->registry->class_localization->local_data = localeconv();
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->languages_list( $rows, $hasTranslate );
	}
	
	/**
	 * Updates the cached language files from the database
	 *
	 * @access	public
	 * @param	integer	ID of the language pack to export
	 * @return	void
	 */
	public function cacheToDisk( $lang_id )
	{
		/* Generate cached warning */
		$warnString = "/*******************************************************\nNOTE: This is a cache file generated by IP.Board on " . date( "r" ) . " by " . $this->memberData['members_display_name'] . "\nDo not translate this file as you will lose your translations next time you edit via the ACP\nPlease translate via the ACP\n*******************************************************/\n\n";
		
		//-----------------------------------------
		// Build where statement
		//-----------------------------------------
		
		if( $lang_id AND $lang_id != 1 )
		{
			$where	= "lang_id={$lang_id}";
		}
		else
		{
			$lang_id	= 1;
			$where		= "lang_id=1";
		}
		
		//-----------------------------------------
		// If missing directory, create
		//-----------------------------------------
		
		if( ! is_dir( IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/' ) )
		{
			mkdir( IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/', 0777 );
		}

		//-----------------------------------------
		// Get the words
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => $where ) );
		$this->DB->execute();
		
		$words_by_file = array();
		
		while( $r = $this->DB->fetch() )
		{
			$_text = ( $r['word_custom'] ) ? $r['word_custom'] : $r['word_default'];
			$words_by_file[$r['word_app']][$r['word_pack']][] = array( $r['word_key'], $_text );
		}
		
		//-----------------------------------------
		// Now loop and write to file
		//-----------------------------------------
		
		foreach( $words_by_file as $app => $word_packs )
		{			
			foreach( $word_packs as $pack => $words )
			{	
				if( $pack == 'public_js' )
				{
					$to_write	= '';
					$_file		= 'ipb.lang.js';
					
					foreach( $words as $word )
					{
						$word[1]	= str_replace( '"', '\\"', $word[1] );
						$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
					}					
				}
				else if( $pack == 'admin_js' )
				{
					$to_write	= '';
					$_file		= 'acp.lang.js';
					
					foreach( $words as $word )
					{
						$word[1]	= str_replace( '"', '\\"', $word[1] );
						$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
					}
				}
				else
				{
					//-----------------------------------------
					// Build cache file contents
					//-----------------------------------------
					
					$to_write	= "<?php\n\n$warnString\n\n\$lang = array( \n";
					$_file		= $app . '_' . $pack . '.php';
					
					foreach( $words as $word )
					{
						$word[1]	= str_replace( '"', '\\"', $word[1] );
						$to_write	.= "'{$word[0]}' => \"{$word[1]}\",\n";
					}

					$to_write .= " ); \n";					
				}
				
				//-----------------------------------------
				// Write the file
				//-----------------------------------------
				
				$_dir = IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/';
				
				@unlink( $_dir . $_file );
				
				if ( $fh = @fopen( $_dir . $_file, 'wb' ) )
				{
					fwrite( $fh, $to_write, strlen( $to_write ) );
					fclose( $fh );
					@chmod( $_dir . $_file, 0777 );
				}
				else
				{
					$this->cache_errors[] = $this->lang->words['l_nowrite'] . $_dir . $_file;
				}
			}
		}
	}	
	
	/**
	 * Imports language packs from an xml file and updates the database and recaches the languages
	 *
	 * @access	public
	 * @param	integer	$lang_id	ID of the language pack to import
	 * @param	bool	$in_dev		Set to 1 for developer language import
	 * @param	bool	$no_return	If set to 1, this function will return a value, rather than outputting data
	 * @param	string	$app_override	Overrides the application for which languages are being imported
	 * @return	mixed
	 */
	public function imprtFromXML( $lang_id=0, $in_dev=0, $no_return=0, $app_override='' )
	{
		//-----------------------------------------
		// Set version..
		//-----------------------------------------
		
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// INDEV?
		//-----------------------------------------

		if ( $in_dev )
		{
			$_FILES['FILE_UPLOAD']['name']	= '';
		}
		else if( $this->request['file_location'] )
		{
			$this->request['file_location']	= IPS_ROOT_PATH . $this->request['file_location'];
		}

		//-----------------------------------------
		// Not an upload?
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// Check and load from server
			//-----------------------------------------
			
			if ( ! $this->request['file_location'] )
			{
				$this->registry->output->global_message = $this->lang->words['l_nofile'];
				$this->languagesList();
				return;
			}
			
			if ( ! file_exists( $this->request['file_location'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['l_noopen'] . $this->request['file_location'];
				$this->languagesList();
				return;
			}
			
			if ( preg_match( "#\.gz$#", $this->request['file_location'] ) )
			{
				if ( $FH = @gzopen( $this->request['file_location'], 'rb' ) )
				{
					while ( ! @gzeof( $FH ) )
					{
						$content .= @gzread( $FH, 1024 );
					}
					
					@gzclose( $FH );
				}
			}
			else
			{
				$content = file_get_contents( $this->request['file_location'] );
			}
			
			$originalContent	= $content;
			
			//-----------------------------------------
			// Extract archive
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH.'classXMLArchive.php' );
			$xmlarchive = new classXMLArchive();
			
			//-----------------------------------------
			// Read the archive
			//-----------------------------------------
			
			$xmlarchive->readXML( $content );
			
			//-----------------------------------------
			// Get the data
			//-----------------------------------------
			
			$content = '';
			
			foreach( $xmlarchive->asArray() as $k => $f )
			{
				if( $k == 'language_entries.xml' )
				{
					$content = $f['content'];
					break;
				}
			}

			//-----------------------------------------
			// No content from de-archiving, must not
			// be archive, but rather raw XML file
			//-----------------------------------------
			
			if( $content == '' AND strpos( $originalContent, "<languageexport" ) !== false )
			{
				$content	= $originalContent;
			}
		}
		
		//-----------------------------------------
		// It's an upload
		//-----------------------------------------
		
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			if( $_FILES['FILE_UPLOAD']['error'] )
			{
				switch( $_FILES['FILE_UPLOAD']['error'] )
				{
					case 1:					
						$this->registry->output->global_message = sprintf( $this->lang->words['lang_upload_too_large'], ini_get( 'upload_max_filesize' ) );
						$this->languagesList();
						return;
					break;
					
					default:
						$this->registry->output->global_message = $this->lang->words['lang_upload_other_error'];
						$this->languagesList();
						return;
					break;						
				}
			}
			
			//-----------------------------------------
			// Get content
			//-----------------------------------------
			
			$uploadedContent = $this->registry->adminFunctions->importXml( $tmp_name );

			//-----------------------------------------
			// Extract archive
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH.'classXMLArchive.php' );
			$xmlarchive = new classXMLArchive();
			
			//-----------------------------------------
			// Read the archive
			//-----------------------------------------
			
			$xmlarchive->readXML( $uploadedContent );
			
			//-----------------------------------------
			// Get the data
			//-----------------------------------------
			
			$content = '';
			
			foreach( $xmlarchive->asArray() as $k => $f )
			{
				if( $k == 'language_entries.xml' )
				{
					$content = $f['content'];
					break;
				}
			}

			//-----------------------------------------
			// No content from de-archiving, must not
			// be archive, but rather raw XML file
			//-----------------------------------------
			
			if( $content == '' AND strpos( $uploadedContent, "<languageexport" ) !== false )
			{
				$content	= $uploadedContent;
			}
		}

		//-----------------------------------------
		// Make sure we have content
		//-----------------------------------------
		
		if( !$content )
		{
			$this->registry->output->global_message = $this->lang->words['l_badfile'];
			$this->languagesList();
			return;
		}

		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		//-----------------------------------------
		// Is this full language pack?...
		//-----------------------------------------
		
		foreach( $xml->fetchElements('langinfo') as $lang_data )
		{
			$lang_info	= $xml->fetchElementsFromRecord( $lang_data );
			
			$lang_data	= array(
									'lang_short' => $lang_info['lang_short'],
									'lang_title' => $lang_info['lang_title'],
								);
		}

		$lang_ids	= array();
		$insertId	= 0;

		//-----------------------------------------
		// Do we have language pack info?
		//-----------------------------------------
		
		if( $lang_data['lang_short'] )
		{
			//-----------------------------------------
			// Does this pack already exist
			//-----------------------------------------
			
			$update_lang = $this->DB->buildAndFetch( array( 
													'select' => 'lang_id', 
													'from'   => 'core_sys_lang',
													'where'  => "lang_short='{$lang_data['lang_short']}'",
											)	);
	
			//-----------------------------------------
			// If doesn't exist, then create new pack
			//-----------------------------------------
			
			if( !$update_lang['lang_id'] )
			{
				$this->DB->insert( 'core_sys_lang', $lang_data );
				
				$insertId	= $this->DB->getInsertId();
				
				if( @mkdir( IPS_CACHE_PATH . '/cache/lang_cache/' . $insertId ) )
				{
					@file_put_contents( IPS_CACHE_PATH . 'cache/lang_cache/' . $insertId . '/index.html', '' );
					@chmod( IPS_CACHE_PATH . '/cache/lang_cache/' . $insertId, 0777 );
				}
				
				//-----------------------------------------
				// Copy over language bits from default lang
				//-----------------------------------------
				
				$default	= $this->DB->buildAndFetch( array( 'select' => 'lang_id', 'from' => 'core_sys_lang', 'where' => "lang_default=1" ) );
				
				$this->DB->build( array( 'select' => 'word_app,word_pack,word_key,word_default', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$default['lang_id']}" ) );
				$q = $this->DB->execute();
				
				while( $r = $this->DB->fetch( $q ) )
				{
					$r['lang_id'] = $insertId;
					$this->DB->insert( 'core_sys_lang_words', $r );
				}
							
				//-----------------------------------------
				// Rebuild IPB and disk caches
				//-----------------------------------------
				
				$this->registry->class_localization->rebuildLanguagesCache();
			}
		}

		//-----------------------------------------
		// We need to add language bits to every pack..
		//-----------------------------------------
		
		if( count($this->caches['lang_data']) )
		{
			foreach( $this->caches['lang_data'] as $langData )
			{
				$lang_ids[]	= $langData['lang_id'];
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$lang_ids[]	= $r['lang_id'];
			}
		}

		//-----------------------------------------
		// Init counts array
		//-----------------------------------------
		
		$counts = array( 'updates' => 0, 'inserts' => 0 );
		
		//-----------------------------------------
		// Init a cache array to save entries
		//-----------------------------------------
		
		$lang_entries	= array();
		
		if( $app_override )
		{
			$this->DB->build( array(
									'select'	=> '*',
									'from'		=> 'core_sys_lang_words',
									'where'		=> "word_app='{$app_override}'"
								)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$lang_entries[ $r['lang_id'] ][ $r['word_app'] ][ $r['word_pack'] ][ $r['word_key'] ]	= $r;
			}
		}
		
		//-----------------------------------------
		// Start looping
		//-----------------------------------------
		
		foreach( $xml->fetchElements('lang') as $entry )
		{
			$lang  = $xml->fetchElementsFromRecord( $entry );
			
			foreach( $lang_ids as $_lang_id )
			{
				//-----------------------------------------
				// Build db array
				//-----------------------------------------
				
				$db_array = array(
									'lang_id'				=> $_lang_id,
									'word_app'				=> $app_override ? $app_override : $lang['word_app'],
									'word_pack'				=> $lang['word_pack'],
									'word_key'				=> $lang['word_key'],
									'word_default'			=> stripslashes($lang['word_default']),
									'word_custom'			=> $in_dev ? '' : stripslashes( $lang['word_custom'] ),
									'word_js'				=> $lang['word_js'],
									'word_default_version'	=> ( $lang['word_default_version'] >= 30000 ) ? $lang['word_default_version'] : $LATESTVERSION['long'],
									'word_custom_version'	=> $lang['word_custom_version'],
								);	

				//-----------------------------------------
				// If cached, get from cache
				//-----------------------------------------
				
				if( $lang_entries[ $_lang_id ][ $db_array['word_app'] ][ $db_array['word_pack'] ] )
				{
					$lang_entry	= $lang_entries[ $_lang_id ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ];
				}
				
				//-----------------------------------------
				// Otherwise get all langs from this entry and
				// put in cache
				//-----------------------------------------
				
				else if( !$app_override )
				{
					$this->DB->build( array(
											'select'	=> '*',
											'from'		=> 'core_sys_lang_words',
											'where'		=> "lang_id={$_lang_id} AND word_app='{$db_array['word_app']}' AND word_pack='{$db_array['word_pack']}'"
										)		);
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$lang_entries[ $r['lang_id'] ][ $r['word_app'] ][ $r['word_pack'] ][ $r['word_key'] ]	= $r;
					}
					
					if( $lang_entries[ $_lang_id ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ] )
					{
						$lang_entry	= $lang_entries[ $_lang_id ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ];
					}
				}
				
				//-----------------------------------------
				// If there is no new custom lang bit to insert
				// don't delete what is already there.
				//-----------------------------------------
				
				if( !$db_array['word_custom'] )
				{
					unset($db_array['word_custom']);
					unset($db_array['word_custom_version']);
				}

				//-----------------------------------------
				// Lang bit already exists, update
				//-----------------------------------------
				
				if( $lang_entry['word_id'] AND ( !$insertId OR $insertId == $_lang_id ) )
				{
					//-----------------------------------------
					// Don't update default version
					//-----------------------------------------
					
					unset( $db_array['word_default_version'] );
					
					$counts['updates']++;
					$this->DB->update( 'core_sys_lang_words', $db_array, "word_id={$lang_entry['word_id']}" );
				}
				
				//-----------------------------------------
				// Lang bit doesn't exist, so insert
				//-----------------------------------------
				
				else if( !$lang_entry['word_id'] )
				{
					$counts['inserts']++;
					$this->DB->insert( 'core_sys_lang_words', $db_array );
				}
			}
		}
		
		//-----------------------------------------
		// Recache all our lang packs
		//-----------------------------------------

		foreach( $lang_ids as $_lang_id )
		{
			$this->cacheToDisk( $_lang_id );
		}
		
		//-----------------------------------------
		// Set output message
		//-----------------------------------------
		
		$this->registry->output->global_message = sprintf( $this->lang->words['l_updatedcount'], $counts['updates'], $counts['inserts'] );
		
		if ( is_array( $this->cache_errors ) AND count( $this->cache_errors ) )
		{
			$this->registry->output->global_message .= "<br />" . implode( "<br />", $this->cache_errors );
		}

		//-----------------------------------------
		// Free a little memory
		//-----------------------------------------
		
		unset( $xml );
		
		//-----------------------------------------
		// Update IPB cache
		//-----------------------------------------
		
		$this->registry->class_localization->rebuildLanguagesCache();
		
		//-----------------------------------------
		// Return! Now!
		//-----------------------------------------
		
		if ( ! $no_return )
		{
			$this->languagesList();
			return;
		}
	}

	/**
	 * Export language entries to xml file
	 *
	 * @access	public
	 * @param	integer	$lang_id	Language pack to export
	 * @param	bool	$disk		Save to disk instead
	 * @return void
	 */
	public function languageExportToXML( $lang_id, $disk=false )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$app_dir		= trim( $this->request['app_dir'] );
		$type			= trim( $this->request['type'] );
		$_where			= '';
		$_name			= 'language.xml';
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		$doPack			= true;

		//-----------------------------------------
		// Filter
		//-----------------------------------------
		
		if ( $app_dir )
		{
			$_where	= ' AND word_app="' . $app_dir . '"';
			$_name	= $app_dir . '_language_pack.xml';
			$doPack	= false;
		}
		
		if ( $type )
		{
			if ( $type == 'admin' )
			{
				$_where	.= ' AND word_pack LIKE "admin_%"';
				$_name	= 'admin_' . $_name;
			}
			else
			{
				$_where	.= ' AND word_pack LIKE "public_%"';
				$_name	= 'public_' . $_name;
			}
			
			$doPack	= false;
		}
		
		//-----------------------------------------
		// Create the XML library
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'languageexport' );
		$xml->addElement( 'languagegroup', 'languageexport' );

		//-----------------------------------------
		// Get language pack
		//-----------------------------------------
		
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$lang_id}" ) );
		
		//-----------------------------------------
		// Add pack if necessary
		//-----------------------------------------
		
		if( $doPack )
		{
			$xml->addElementAsRecord( 'languagegroup', 'langinfo', $data );
		}

		//-----------------------------------------
		// Get the words
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$lang_id}" . $_where ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Add words to export
		//-----------------------------------------
		
		$word_packs = array();
		
		while( $r = $this->DB->fetch() )
		{
			$content = array();
			
			$content = array( 
							'word_app'				=> $r['word_app'],
							'word_pack'				=> $r['word_pack'],
							'word_key'				=> $r['word_key'],
							'word_default'			=> stripslashes( $r['word_default'] ),
							'word_custom'			=> stripslashes( $r['word_custom'] ),
							'word_default_version'	=> ( $r['word_default_version'] >= 30000 ) ? $r['word_default_version'] : $LATESTVERSION['long'],
							'word_custom_version'	=> $r['word_custom_version'],
							'word_js'				=> $r['word_js']
						);

			$xml->addElementAsRecord( 'languagegroup', 'lang', $content );
		}

		//-----------------------------------------
		// Write to disk or output to browser
		//-----------------------------------------
		
		if( $disk )
		{
			@unlink( IPSLib::getAppDir($app_dir) . '/xml/' . $_name );
			@file_put_contents( IPSLib::getAppDir($app_dir) . '/xml/' . $_name, $xml->fetchDocument() );
			return true;
		}
		else
		{
			//-----------------------------------------
			// Create xml archive
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );
			$xmlArchive = new classXMLArchive();
			
			//-----------------------------------------
			// Add XML document
			//-----------------------------------------
			
			$xmlArchive->add( $xml->fetchDocument(), 'language_entries.xml' );
			
			//-----------------------------------------
			// Print to browser
			//-----------------------------------------
			
			$this->registry->output->showDownload( $xmlArchive->getArchiveContents(), $_name );
			exit();
		}
	}

	/**
	 * Builds the language db entries from cache
	 *
	 * @access	public
	 * @param	boolean			return as normal
	 * @return	void
	 * @author	Josh
	 */
	public function importFromCacheFiles( $returnAsNormal=TRUE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$msg			= array();
		$lang_id		= 1;
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $_POST['apps'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['l_noapp'];
			$this->languagesList();
			return;
		}
		
		//-----------------------------------------
		// Loop through apps...
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app => $app_data )
		{
			if ( in_array( $app, $_POST['apps'] ) )
			{
				//-----------------------------------------
				// Get directory
				//-----------------------------------------
				
				$_dir    = IPS_CACHE_PATH . 'cache/lang_cache/master_lang/';
				
				//-----------------------------------------
				// Go through directories
				//-----------------------------------------
				
				if ( is_dir( $_dir ) )
				{
					$dh = opendir( $_dir );
								
					while( $f = readdir( $dh ) )
					{
						if ( $f[0] == '.' || $f == 'index.html' )
						{
							continue;
						}	
				
						if ( preg_match( "#^" . $app . "_\S+?\.php$#", $f ) )
						{
							//-----------------------------------------
							// INIT
							//-----------------------------------------
							
							$updated	= 0;
							$inserted	= 0;
							$word_pack	= preg_replace( "#^" . $app . "_(\S+?)\.php$#", "\\1", $f );
							$lang		= array();
							$db_lang	= array();
							
							//-----------------------------------------
							// Delete current language bits
							//-----------------------------------------

							$this->DB->delete( 'core_sys_lang_words', 'lang_id=1 AND word_app="'.$app.'" AND word_pack=\'' . $word_pack . '\'' );

							if ( IPS_IS_SHELL )
							{
								$stdout = fopen('php://stdout', 'w');
								fwrite( $stdout, 'Processing: ' . $f . "\n" );
								fclose( $stdout );
							}
							
							require( $_dir . $f );
						
							//-----------------------------------------
							// Loop
							//-----------------------------------------

							foreach( $lang as $k => $v )
							{
								$inserted++;
							
								$insert = array(
													'lang_id'				=> $lang_id,
													'word_app'				=> $app,
													'word_pack'				=> $word_pack,
													'word_key'				=> $k,
													'word_default'			=> IPSText::encodeForXml($v),
													'word_default_version'	=> $LATESTVERSION['long'],
													'word_js'				=> 0,
												);

								$this->DB->insert( 'core_sys_lang_words', $insert );
							}
							
							$msg[] = "Imported {$f} ({$inserted} added, {$updated} updated)...";
						}
						else if( preg_match( '/(\.js)$/', $f ) AND $app == 'core' )
						{
							$_js_word_pack	= '';
							
							if( $f == 'ipb.lang.js' )
							{
								$_js_word_pack	= 'public_js';
							}
							else if( $f == 'acp.lang.js' )
							{
								$_js_word_pack	= 'admin_js';
							}
							
							//-----------------------------------------
							// Delete current words for this app and word pack
							//-----------------------------------------
							
							$this->DB->delete( 'core_sys_lang_words', 'lang_id=1 AND word_app="'.$app.'" AND word_pack=\'' . $_js_word_pack . '\'' );
						
							if ( IPS_IS_SHELL )
							{
								$stdout = fopen('php://stdout', 'w');
								fwrite( $stdout, 'Processing: ' . $f . "\n" );
								fclose( $stdout );
							}
							
							//-----------------------------------------
							// Get each line
							//-----------------------------------------
							
							$js_file = file( $_dir . $f );
							
							//-----------------------------------------
							// Loop through lines and import
							//-----------------------------------------
							
							foreach( $js_file as $r )
							{
								//-----------------------------------------
								// preg_match what we want
								//-----------------------------------------
								
								preg_match( "#ipb\.lang\['(.+?)'\](.+?)= [\"'](.+?)[\"'];#", $r, $matches );

								//-----------------------------------------
								// Valid?
								//-----------------------------------------
								
								if( $matches[1] && $matches[3] )
								{
									$inserted++;
									$insert = array(
														'lang_id'      => $lang_id,
														'word_app'     => 'core',
														'word_pack'    => $_js_word_pack,
														'word_key'     => $matches[1],
														'word_default' => IPSText::encodeForXml($matches[3]),
														'word_js'      => 1,
													);
									$this->DB->insert( 'core_sys_lang_words', $insert );
								}
							}
						}
					}
	
					closedir( $dh );
				}
			}
		}

		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		if ( $returnAsNormal === TRUE )
		{
			$this->registry->output->global_message	= implode( "<br />", $msg );
		
			if ( ! $this->__daily )
			{
				$this->languagesList();
			}
		}
		else
		{
			return $msg;
		}
	}
}