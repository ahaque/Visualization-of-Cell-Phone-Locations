<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Import and export skin sets
 * Last Updated: $Date: 2009-08-04 11:48:28 -0400 (Tue, 04 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 4973 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_importexport extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * Recursive depth guide
	 *
	 * @access	private
	 * @var		array
	 */
	private $_depthGuide = array();
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**#@+
	 * URL bits
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/
	
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=importexport';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=importexport';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		
		# I'm so sorry Keith. I know language abstraction sucks. You deserve a medal.
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );
		
		$this->skinFunctions = new skinImportExport( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_showForm();
			break;
			case 'exportSet':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_export' );
				$this->_exportSet();
			break;
			case 'exportImages':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_export' );
				$this->_exportImages();
			break;
			case 'exportReplacements':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_export' );
				$this->_exportReplacements();
			break;
			
			case 'importSet':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_importSet();
			break;
			case 'importImages':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_importImages();
			break;
			case 'importReplacements':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_importReplacements();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Import an XMLarchive skin set
	 *
	 * @access	private
	 * @return	string		HTML to show
	 */
	private function _importReplacements()
	{
		$importLocation = trim( $this->request['importLocation'] );
		$setID          = intval( $this->request['setID'] );
		
		//-----------------------------------------
		// Attempt to get contents
		//-----------------------------------------
		
		$content = $this->registry->adminFunctions->importXml( $importLocation );
		
		if ( $content )
		{
			$added = $this->skinFunctions->importReplacementsXMLArchive( $content, $setID );
			
			if ( $added !== FALSE )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['ie_replace_added'], $added );
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=templates&section=importexport&do=overview' );
			}
			else
			{
				$this->registry->output->global_error = $this->lang->words['ie_importfail'] . implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
				return $this->_showForm();
			}
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['ie_importfail_no'];
			return $this->_showForm();
		}
	}
	
	/**
	 * Import an XMLarchive skin set
	 *
	 * @access	private
	 * @return	string		HTML to show
	 */
	private function _importImages()
	{
		$importName     = trim( $this->request['importName'] );
		$importLocation = trim( $this->request['importLocation'] );
		$setID          = intval( $this->request['setID'] );
		
		//-----------------------------------------
		// Fix up import name
		//-----------------------------------------
		
		$importName = ( $importName ) ? $importName : $_FILES['FILE_UPLOAD']['name'];
		$importName = str_ireplace( array( '.xml', '.gz', 'images-' ), '', $importName );
		
		//-----------------------------------------
		// Attempt to get contents
		//-----------------------------------------
		
		$content = $this->registry->adminFunctions->importXml( $importLocation );
		
		if ( $content )
		{
			$added = $this->skinFunctions->importImagesXMLArchive( $content, $importName, $setID );
			
			if ( $added !== FALSE )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['ie_images_added'], $added );
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=templates&section=importexport&do=overview' );
			}
			else
			{
				$this->registry->output->global_error = $this->lang->words['ie_importfail'] . implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
				return $this->_showForm();
			}
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['ie_importfail_no'];
			return $this->_showForm();
		}
	}
	
	/**
	 * Import an XMLarchive skin set
	 *
	 * @access	private
	 * @return	string		HTML to show
	 */
	private function _importSet()
	{
		$importName     = trim( $this->request['importName'] );
		$importLocation = trim( $this->request['importLocation'] );
		$imageDir       = trim( $this->request['importImgDirs'] );
		
		//-----------------------------------------
		// Attempt to get contents
		//-----------------------------------------
		
		$content = $this->registry->adminFunctions->importXml( $importLocation );
		
		if ( $content )
		{
			$added = $this->skinFunctions->importSetXMLArchive( $content, 0, $imageDir, $importName );
			
			$this->registry->output->global_message = sprintf( $this->lang->words['ie_set_imported'], $added['templates'], $added['replacements'], $added['css'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=templates&section=importexport&do=overview' );
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['ie_importfail_no'];
			return $this->_showForm();
		}
	}
	
	/**
	 * Export replacements to an XMLArchive
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportReplacements()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID   = intval( $this->request['setID'] );
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Er.. that's it...
		//-----------------------------------------
		
		$this->registry->output->showDownload( $this->skinFunctions->generateReplacementsXML( $setID ), 'replacements-' . IPSText::makeSeoTitle( $setData['set_name'] ) . '.xml' );
	}
	
	/**
	 * Export an image set to an XMLArchive
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportImages()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID    = intval( $this->request['setID'] );
		$setData  = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Er.. that's it...
		//-----------------------------------------
		
		$xml = $this->skinFunctions->generateImagesXMLArchive( $setData['set_image_dir'] );
		
		if ( count( $this->skinFunctions->fetchErrorMessages( TRUE ) ) )
		{
			$this->registry->output->global_error = implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
			$this->_showForm();
			return;
		}
		
		$this->registry->output->showDownload( $xml, 'images-' . IPSText::makeSeoTitle( $setData['set_image_dir'] ) . '.xml' );
	}

	/**
	 * Export the entire set to an XMLArchive
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportSet()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID   = intval( $this->request['setID'] );
		$setOnly = ( $this->request['exportSetOptions'] == 'all' ) ? FALSE : TRUE;
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		$apps    = array();
		
		/* Figure out which apps to export */
		if ( is_array( $_POST['exportApps'] ) AND count( $_POST['exportApps'] ) )
		{
			foreach( $_POST['exportApps'] as $k => $v )
			{
				if ( $k == 'core' AND $v )
				{
					$apps[] = 'core';
					$apps[] = 'forums';
					$apps[] = 'members';
					$apps[] = 'calendar';
					$apps[] = 'chat';
					$apps[] = 'portal';
				}
				else if ( $v )
				{
					$apps[] = $k;
				}
			}
		}
		
		//-----------------------------------------
		// Er.. that's it...
		//-----------------------------------------
		
		$xml = $this->skinFunctions->generateSetXMLArchive( $setID, $setOnly, $apps );
		
		if ( count( $this->skinFunctions->fetchErrorMessages( TRUE ) ) )
		{
			$this->registry->output->global_error = implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
			$this->_showForm();
			return;
		}
		
		$this->registry->output->showDownload( $xml, IPSText::makeSeoTitle( $setData['set_name'] ) . '.xml' );
	}
	
	/**
	 * Show the main form
	 *
	 * @access	private
	 * @return	void
	 */
	private function _showForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sets        = $this->_fetchSetsDropDown( intval( $this->request['skinID'] ) );
		$imageDirs   = $this->skinFunctions->fetchImageDirectories();
		$form        = array();
		$warnings	 = array();
		$_imageDirs  = array();
		$_skinSetMap = array();
		
		//-----------------------------------------
		// Map skins to image dirs
		//-----------------------------------------
		
		foreach( $this->registry->output->allSkins as $_id => $data )
		{
			$_skinSetMap[ $data['set_image_dir'] ][] = $_id;
		}
		
		//-----------------------------------------
		// Build image dir option list
		//-----------------------------------------
		
		foreach( $imageDirs as $dir )
		{
			$name = '';
			
			/* Used in a skin set? */
			if ( isset($_skinSetMap[$dir]) )
			{
				$_count = count( $_skinSetMap[ $dir ] );
				$_name  = $this->registry->output->allSkins[ array_pop( $_skinSetMap[ $dir ] ) ];
				
				if ( $_count > 1 )
				{
					$name = $dir . sprintf( $this->lang->words['used_in_plus_others'], $_name['set_name'], $_count );
				}
				else
				{
					$name = $dir . sprintf( $this->lang->words['used_in_no_others'], $_name['set_name'] );
				}
			}
			
			$_imageDirs[] = array( $dir, ( $name ) ? $name : $dir );
		}
		
		//-----------------------------------------
		// Warnings?
		//-----------------------------------------
		
		# Image directory writeable?
		if ( ! is_writable( rtrim( $this->skinFunctions->fetchImageDirectoryPath(''), '/' ) ) )
		{
			$warnings['importImgDir'] = 1;
		}
		
		# Main cache path writeable?
		if ( ! is_writable( IPS_CACHE_PATH . 'cache/skin_cache' ) )
		{
			$warnings['importSkinCacheDir'] = 1;
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['uploadField']      = $this->registry->output->formUpload();
		$form['importName']       = $this->registry->output->formInput( 'importName'    , $_POST['importName'] );
		$form['importLocation']   = $this->registry->output->formInput( 'importLocation', $_POST['importLocation'] );
		$form['exportImgDirs']    = $this->registry->output->formDropdown( 'exportImgDirs', $_imageDirs );
		
		array_unshift( $_imageDirs, array( '0', $this->lang->words['ie_none'] ) );
		
		$form['importImgDirs']    = $this->registry->output->formDropdown( 'importImgDirs', $_imageDirs );
		
		$form['exportSetOptions'] = $this->registry->output->formDropdown("exportSetOptions", array( 0 => array( 'current'  , $this->lang->words['ie_ex_skin'] ),
																					                 1 => array( 'all'      , $this->lang->words['ie_ex_skin_p'] ) ) );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->importexport_form( $sets, $form, $warnings );
	}
	
	/**
	 * This code is duplicated from output/formats/html
	 * I did debate moving it into a function elsewhere
	 * But it's only used here also, so...
	 *
	 * @access	private
	 * @param	int			Set ID to be 'selected'
	 * @param	int			Parent id
	 * @param	int			Iteration
	 * @return	string		HTML
	 */
	private function _fetchSetsDropDown( $skinID=NULL, $parent=0, $iteration=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$output       = "";
		$depthMarkers = "";
		
		if( $iteration )
		{
			for( $i=0; $i<$iteration; $i++ )
			{
				$depthMarkers .= '--';
			}
		}
		
		//-----------------------------------------
		// Go get 'em
		//-----------------------------------------
		
		foreach( $this->registry->output->allSkins as $id => $data )
		{
			/* Allowed to use? */
			/*if ( $data['_youCanUse'] !== TRUE )
			{
				continue;
			}*/
		
			/* Root skins? */
			if ( count( $data['_parentTree'] ) AND $iteration == 0 )
			{
				continue;
			}
			else if( $iteration > 0 AND (!count( $data['_parentTree'] ) OR $data['_parentTree'][0] != $parent) )
			{
				continue;
			}
			
			$_selected = ( $skinID != NULL AND $skinID == $_data['set_id'] ) ? 'selected="selected"' : '';
			
			/* Ok to add... */
			$output .= "\n<option id='skinSetDD_" . $data['set_id'] . "' " . $_selected . " value=\"". $data['set_id'] . "\">". $depthMarkers . $data['set_name'] . "</option>";
			
			if ( is_array( $data['_childTree'] ) AND count( $data['_childTree'] ) )
			{
				$output .= $this->_fetchSetsDropDown( $skinID, $data['set_id'], $iteration + 1 );
			}
		}
		
		return $output;
	}
}