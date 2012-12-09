<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin Functions
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * Owner: Matt
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 5041 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class skinImportExport extends skinCaching
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
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		parent::__construct( $registry );
	}
	
	/**
	 * Imports a replacements XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @param	int			Set ID to apply to (if desired)
	 * @return	int			Number of items added
	 */
	public function importReplacementsXMLArchive( $content, $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$addedCount  = 0;
							
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		$replacements = $this->parseReplacementsXML( $content );
		
		//-----------------------------------------
		// Replacements...
		//-----------------------------------------
		
		if ( is_array( $replacements ) )
		{
			$this->DB->delete( 'skin_replacements', 'replacement_set_id=' . $setID );
			
			foreach( $replacements as $replacement )
			{
				if ( $replacement['replacement_key'] )
				{
					$addedCount++;
					
					$this->DB->insert( 'skin_replacements', array( 'replacement_key'      => $replacement['replacement_key'],
																   'replacement_content'  => $replacement['replacement_content'],
																   'replacement_set_id'   => $setID,
																   'replacement_added_to' => $setID ) );
				}
			}
		}
		
		$this->rebuildReplacementsCache( $setID );
		$this->rebuildSkinSetsCache( $setID );
		
		return $addedCount;
	}
	
	/**
	 * Imports a set XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @param	string		Images directory name to use.
	 * @param	int			[ Set ID to apply to (if desired) ]
	 * @return	mixed		Number of items added, or bool
	 */
	public function importImagesXMLArchive( $content, $imageSetName, $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$addedCount  = 0;
							
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! strstr( $content, "<xmlarchive" ) )
		{
			$this->_addErrorMessage( "The content was not a valid XMLArchive" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab the XMLArchive class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );
		$xmlArchive = new classXMLArchive();
		
		$xmlArchive->readXML( $content );
		
		if ( ! $xmlArchive->countFileArray() )
		{
			$this->_addErrorMessage( "The XMLArchive is empty" );
			return FALSE;
		}
		
		$added = $xmlArchive->countFileArray();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( $this->checkImageDirectoryExists( $imageSetName ) === TRUE )
		{
			/* Already exists... */
			$this->_addErrorMessage( "The directory public/style_images/{$imageSetName} already exists" );
			return FALSE;
		}
		
		if ( $this->createNewImageDirectory( $imageSetName ) !== TRUE )
		{
			$this->_addErrorMessage( "Could not create public/style_images/{$imageSetName}. Please check file permissions and try again." );
			return FALSE;
		}
		
		//-----------------------------------------
		// OK, write it...
		//-----------------------------------------
		
		/* Find the name of the folder */
		//preg_match( "#<path>([^/]+?)</path>#", $content, $match );
		//$_strip = $match[1];
		
		/* Strip the path */
		//$xmlArchive->setStripPath( $_strip );

		/* Write it */
		if ( $xmlArchive->write( $content, $this->fetchImageDirectoryPath( $imageSetName ) ) === FALSE )
		{
			$this->_addErrorMessage( "Could not write the image set to disk" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Update set?
		//-----------------------------------------
		
		if ( $setID )
		{
			$this->DB->update( 'skin_collections', array( 'set_image_dir' => $imageSetName ), 'set_id=' . $setID );
			
			/* Rebuild trees */
			$this->rebuildTreeInformation( $setID );
			
			/* Now re-load to fetch the tree information */
			$newSet = $this->DB->buildAndFetch( array( 'select' => '*',
													   'from'   => 'skin_collections',
													   'where'  => 'set_id=' . $setID ) );
													
			/* Add to allSkins array for caching functions below */
			$newSet['_parentTree']     = unserialize( $newSet['set_parent_array'] );
			$newSet['_childTree']      = unserialize( $newSet['set_child_array'] );
			$newSet['_userAgents']     = unserialize( $newSet['set_locked_uagent'] );
			$newSet['_cssGroupsArray'] = unserialize( $newSet['set_css_groups'] );
			
			$this->registry->output->allSkins[ $setID ] = $newSet;
			
			$this->rebuildSkinSetsCache( $setID );
			$this->rebuildCSS( $setID );
			$this->rebuildReplacementsCache( $setID );
		}
		
		return $added;
	}
	
	/**
	 * Imports a set XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @param	int 		[ Skin set parent. If omitted, it will be made a root skin ]
	 * @param	string		[ Images directory to use. If omitted, default skin's image dir is used ]
	 * @param	string		[ Name of skin to create. If omitted, name from skin set is used ]
	 * @return	mixed		bool, or number of items added
	 */
	public function importSetXMLArchive( $content, $parentID=0, $imageDir='', $setName='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templates   = array();
		$csss		 = array();
		$groups      = array();
		$defaultSkin = array();
		$return      = array( 'replacements' => 0,
							  'css'			 => 0,
							  'templates'    => 0 );
							
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! strstr( $content, "<xmlarchive" ) )
		{
			$this->_addErrorMessage( "The content was not a valid XMLArchive" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Make admin group list
		//-----------------------------------------
		
		foreach( $this->caches['group_cache'] as $id => $data )
		{
			if ( $data['g_access_cp'] )
			{
				$groups[] = $id;
			}
		}
		
		//-----------------------------------------
		// Grab the XMLArchive class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );
		$xmlArchive = new classXMLArchive();
		
		$xmlArchive->readXML( $content );
		
		if ( ! $xmlArchive->countFileArray() )
		{
			$this->_addErrorMessage( "The XMLArchive is empty" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Gather data
		//-----------------------------------------
		
		/* Info */
		$infoXml	= $xmlArchive->getFile('info.xml');
		
		if( !$infoXml )
		{
			$this->_addErrorMessage( "The info.xml file is empty or was not included" );
			return FALSE;
		}
		
		$info		= $this->parseInfoXML( $infoXml );

		/* Replacements */
		$replacements = $this->parseReplacementsXML( $xmlArchive->getFile( 'replacements.xml' ) );

		/* Templates */
		foreach( $xmlArchive->asArray() as $path => $fileData )
		{
			if ( $fileData['path'] == 'templates' && $fileData['content'] )
			{
				$templates[ str_replace( '.xml', '', $fileData['filename'] ) ] = $this->parseTemplatesXML( $fileData['content'] );
			}
		}

		/* Templates */
		foreach( $xmlArchive->asArray() as $path => $fileData )
		{
			if ( $fileData['path'] == 'css' )
			{
				$csss[ str_replace( '.xml', '', $fileData['filename'] ) ] = $this->parseCSSXML( $fileData['content'] );
			}
		}
		
		if ( ! is_array( $info ) )
		{
			$this->_addErrorMessage( "The XMLArchive does not contain an info.xml file" );
			return FALSE;
		}
		
		$info['set_output_format'] = ( $info['set_output_format'] ) ? $info['set_output_format'] : 'html';
		
		//-----------------------------------------
		// Find default skin
		//-----------------------------------------
		
		foreach( $this->registry->output->allSkins as $id => $data )
		{
			if ( $data['set_is_default'] AND $data['set_output_format'] == $info['set_output_format'] )
			{
				$defaultSkin = $data;
				break;
			}
		}
		
		//-----------------------------------------
		// Build Set Array
		//-----------------------------------------
		
		$newSet = array('set_name'				=> ( $setName ) ? $setName : $info['set_name'] . ' (Import)',
					    'set_key'				=> '',
						'set_parent_id'  		=> $parentID,
						'set_permissions'		=> implode( ",", $groups ),
						'set_is_default'		=> 0,
						'set_author_name'		=> $info['set_author_name'],
						'set_author_url'		=> $info['set_author_url'],
						'set_image_dir'			=> ( $imageDir ) ? $imageDir : $defaultSkin['set_image_dir'],
						'set_emo_dir'			=> $defaultSkin['set_emo_dir'],
						'set_css_inline'		=> 0,
						'set_output_format' 	=> $info['set_output_format'],
						'set_css_groups'    	=> '',
						'set_hide_from_list'	=> 1,	// Per Rikki :P
						'set_updated'       	=> time() );
		
		//-----------------------------------------
		// Insert...
		//-----------------------------------------
		
		$this->DB->insert( 'skin_collections', $newSet );
		
		$setID = $this->DB->getInsertId();
		
		/* Rebuild trees */
		$this->rebuildTreeInformation( $setID );
		
		/* Now re-load to fetch the tree information */
		$newSet = $this->DB->buildAndFetch( array( 'select' => '*',
												   'from'   => 'skin_collections',
												   'where'  => 'set_id=' . $setID ) );
												
		/* Add to allSkins array for caching functions below */
		$newSet['_parentTree']     = unserialize( $newSet['set_parent_array'] );
		$newSet['_childTree']      = unserialize( $newSet['set_child_array'] );
		$newSet['_userAgents']     = unserialize( $newSet['set_locked_uagent'] );
		$newSet['_cssGroupsArray'] = unserialize( $newSet['set_css_groups'] );
		
		$this->registry->output->allSkins[ $setID ] = $newSet;
		
		//-----------------------------------------
		// Replacements...
		//-----------------------------------------
		
		if ( is_array( $replacements ) )
		{
			foreach( $replacements as $replacement )
			{
				if ( $replacement['replacement_key'] )
				{
					$return['replacements']++;
					
					$this->DB->insert( 'skin_replacements', array( 'replacement_key'      => $replacement['replacement_key'],
																   'replacement_content'  => $replacement['replacement_content'],
																   'replacement_set_id'   => $setID,
																   'replacement_added_to' => $setID ) );
				}
			}
		}
		
		//-----------------------------------------
		// CSS...
		//-----------------------------------------
		
		/* Fetch master CSS */
		$_MASTER = $this->fetchCSS( 0 );
		
		$apps = new IPSApplicationsIterator();

		if ( is_array( $csss ) )
		{
			foreach( $apps as $app )
			{
				$appDir = $apps->fetchAppDir();
				
				if ( isset( $csss[ $appDir ] ) && is_array( $csss[ $appDir ] ) )
				{
					foreach( $csss[ $appDir ] as $css )
					{
						if ( $css['css_group'] )
						{
							$return['css']++;
					
							$this->DB->insert( 'skin_css', array( 'css_group'      => $css['css_group'],
																  'css_content'    => $css['css_content'],
																  'css_position'   => $css['css_position'],
																  'css_attributes' => $css['css_attributes'],
																  'css_app'		   => $css['css_app'],
																  'css_app_hide'   => $css['css_app_hide'],
																  'css_modules'	   => str_replace( ' ', '', $css['css_modules'] ),
																  'css_updated'    => time(),
																  'css_set_id'     => $setID,
																  'css_added_to'   => ( isset( $_MASTER[ $css['css_group'] ] ) ) ? 0 : $setID ) );
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Templates - only import apps we have...
		//-----------------------------------------
		
		/* Fetch all master items */
		$_MASTER    = $this->fetchTemplates( 0, 'allNoContent' );
		
		$apps = new IPSApplicationsIterator();
		
		if ( is_array( $templates ) )
		{
			foreach( $apps as $app )
			{
				$appDir = $apps->fetchAppDir();
				
				if ( array_key_exists( $appDir, $templates ) )
				{
					foreach( $templates[ $appDir ] as $template )
					{
						if ( $template['template_group'] AND $template['template_name'] )
						{
							/* Figure out if this is added by a user or not */
							$isAdded = ( is_array( $_MASTER[ $template['template_group'] ][ strtolower( $template['template_name'] ) ] ) AND ! $_MASTER[ $template['template_group'] ][ strtolower( $template['template_name'] ) ]['template_user_added'] ) ? 0 : 1;
							
							$return['templates']++;
					
							$this->DB->insert( 'skin_templates', array( 'template_set_id'      => $setID,
																		'template_group'       => $template['template_group'],
																		'template_content'     => $template['template_content'],
																		'template_name'        => $template['template_name'],
																		'template_data'        => $template['template_data'],
																		'template_updated'     => $template['template_updated'],
																		'template_removable'   => 1,
																		'template_user_edited' => 1,
																	    'template_user_added'  => $isAdded,
																		'template_added_to'    => $setID ) );
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Re-cache
		//-----------------------------------------
		
		$this->rebuildReplacementsCache( $setID );
		$this->rebuildCSS( $setID );
		$this->rebuildPHPTemplates( $setID );
		$this->rebuildSkinSetsCache();
		
		//-----------------------------------------
		// Done....
		//-----------------------------------------
		
		return $return;
	}
	
	/**
	 * Parses an CSS XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseTemplatesXML( $xmlContents )
	{
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		$return = array();
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );
		
		foreach( $xml->fetchElements( 'template' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( is_array( $data ) )
			{
				$return[] = $data;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parses an CSS XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseCSSXML( $xmlContents )
	{
		if( ! $xmlContents )
		{
			return '';
		}
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		$return = array();
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );
		
		foreach( $xml->fetchElements( 'cssfile' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( is_array( $data ) )
			{
				$return[] = $data;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parses an replacements XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseReplacementsXML( $xmlContents )
	{
		if( ! $xmlContents )
		{
			return '';
		}
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		$return = array();
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );
		
		foreach( $xml->fetchElements( 'replacement' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( is_array( $data ) )
			{
				$return[] = $data;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parses an info XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseInfoXML( $xmlContents )
	{
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );

		foreach( $xml->fetchElements( 'data' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
		}
		
		return $data;
	}
	
	/**
	 * Generate an XML archive for an image set
	 *
	 * @access	public
	 * @param	string		Image Directory
	 * @return	mixed		bool, or xml contents
	 */
	public function generateImagesXMLArchive( $imgDir )
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		//-----------------------------------------
		// Does this image directory exist?
		//-----------------------------------------
		
		if ( $this->checkImageDirectoryExists( $imgDir ) !== TRUE )
		{
			$this->_addErrorMessage( "Image directory $imgDir does not exist" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Create new XML archive...
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );
		$xmlArchive = new classXMLArchive();
		$xmlArchive->setStripPath( $this->fetchImageDirectoryPath( $imgDir ) );
		$xmlArchive->add( $this->fetchImageDirectoryPath( $imgDir ) );
		
		return $xmlArchive->getArchiveContents();
	}
	
	/**
	 * Generate XML Archive for skin set
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @param	boolean		Modifications in this set only
	 * @param	array		[Array of apps to export from. Default is all]
	 * @return	string		XML
	 */
	public function generateSetXMLArchive( $setID=0, $setOnly=FALSE, $appslimit=null )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templates    = array();
		$csss		  = array();
		$replacements = "";
		$css          = "";
		$setData      = $this->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		//-----------------------------------------
		// First up... fetch templates
		//-----------------------------------------
		
		$apps = new IPSApplicationsIterator();

		foreach( $apps as $app )
		{
			if ( is_array( $appslimit ) AND ! in_array( $apps->fetchAppDir(), $appslimit ) )
			{
				continue;
			}
			
			if ( $apps->isActive() )
			{
				$templates[ $apps->fetchAppDir() ]	= $this->generateTemplateXML( $apps->fetchAppDir(), $setID, $setOnly );
				$csss[ $apps->fetchAppDir() ]		= $this->generateCSSXML( $apps->fetchAppDir(), $setID, $setOnly );
			}
		}

		//-----------------------------------------
		// Replacements
		//-----------------------------------------
		
		$replacements = $this->generateReplacementsXML( $setID, $setOnly );
		
		//-----------------------------------------
		// Information
		//-----------------------------------------
		
		$info = $this->generateInfoXML( $setID );
		
		//-----------------------------------------
		// De-bug
		//-----------------------------------------
		
		foreach( $templates as $app_dir => $templateXML )
		{
			IPSDebug::addLogMessage( "Template Export: $app_dir\n".$templateXML, 'admin-setExport' );
		}
		
		foreach( $csss as $app_dir => $cssXML )
		{
			IPSDebug::addLogMessage( "CSS Export: $app_dir\n".$cssXML, 'admin-setExport' );
		}
		
		IPSDebug::addLogMessage( "Replacements Export:\n".$replacements, 'admin-setExport' );
		IPSDebug::addLogMessage( "Info Export:\n".$info, 'admin-setExport' );
		
		//-----------------------------------------
		// Create new XML archive...
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );
		$xmlArchive = new classXMLArchive();
		
		# Templates
		foreach( $templates as $app_dir => $templateXML )
		{
			$xmlArchive->add( $templateXML, "templates/" . $app_dir . ".xml" );
		}
		
		# CSS
		foreach( $csss as $app_dir => $cssXML )
		{
			$xmlArchive->add( $cssXML, "css/" . $app_dir . ".xml" );
		}
		
		# Replacements
		$xmlArchive->add( $replacements, "replacements.xml" );

		# Information
		$xmlArchive->add( $info, 'info.xml' );
		
		return $xmlArchive->getArchiveContents();
	}
	
	/**
	 * Export all Apps skin files
	 *
	 * @access	public
	 * @param	int		[Set ID - 0/root if omitted]
	 * @param	bool	Include root bits in any XML export. Default is true
	 * @return	void
	 */
	public function exportAllAppTemplates( $setID=0, $setOnly=TRUE )
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			if ( ! file_exists( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
			{
				$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . "/xml/ does not exist" );
				continue;
			}
			else if ( ! is_writable( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
			{
				if ( ! @chmod( IPSLib::getAppDir(  $app_dir ) . '/xml', 0755 ) )
				{
					$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . "/xml/ is not writeable" );
					continue;
				}
			}
				
			$this->exportTemplateAppXML( $app_dir, $setID, $setOnly );
		}
	}
	
	/**
	 * Export all Apps CSS
	 *
	 * @access	public
	 * @param	int		[Set ID - 0/root if omitted]
	 * @return	void
	 */
	public function exportAllAppCSS( $setID=0 )
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------

		$this->_resetErrorHandle();
		$this->_resetMessageHandle();

		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_css.xml';

			if ( ! file_exists( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
			{
				$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . "/xml/ does not exist" );
				continue;
			}
			else if ( ! is_writable( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
			{
				if ( ! @chmod( IPSLib::getAppDir(  $app_dir ) . '/xml', 0755 ) )
				{
					$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . "/xml/ is not writeable" );
					continue;
				}
			}

			$this->exportCSSAppXML( $app_dir, $setID );
		}
	}
	
	/**
	 * Import all Apps skin files
	 *
	 * @todo  See Matt, this needs fixing! 
	 * @access	public
	 * @return	void
	 */
	public function importAllAppTemplates()
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_templates.xml';
			
			if ( ! file_exists( $file ) )
			{
				$this->_addMessage( $app_dir . ': Nothing to import' );
				continue;
			}
			else
			{
				$return = $this->importTemplateAppXML( $app_dir, 0 );
				$this->_addMessage( $app_dir . ': ' . $return['updateCount'] . ' updated, ' . $return['insertCount'] . ' added' );
			}
		}
	}
	
	/**
	 * Generate the master skin set files
	 *
	 * @access	public
	 * @param	array  		Array of IDs
	 * @return	string		XML contents
	 */
	public function generateMasterSkinSetXML( $skinIDs )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$skins = array();
		$setid = 1;
		
		/* Figure out ID 0 */
		if ( in_array( 0, $skinIDs ) )
		{
			$cssSkinCollections = array();
			$_css 			    = $this->fetchCSS( 0 );
			
			foreach( $_css as $name => $css )
			{
				/* Build skin set row*/
				$cssSkinCollections[ $css['css_position'] . '.' . $css['css_id'] ] = array( 'css_group' => $css['css_group'], 'css_position' => $css['css_position'] );
			}
			
			$setid++;
			
			$skins[ 0 ] = array( 'set_id'			  => 1,
		  						 'set_name'			  => 'IP.Board',
		  						 'set_key'			  => 'default',
		  						 'set_parent_id'	  => 0,
		  						 'set_parent_array'   => serialize( array() ),
		  						 'set_child_array'    => serialize( array() ),
		  						 'set_permissions'    => '*',
		  						 'set_is_default'	  => 1,
		  						 'set_author_name'	  => 'Invision Power Services, Inc',
		  						 'set_author_url'	  => 'http://www.',
		  						 'set_image_dir'	  => 'master',
		  						 'set_emo_dir'		  => 'default',
		  						 'set_css_inline'	  => 1,
		  						 'set_css_groups'	  => serialize( $cssSkinCollections ),
		  						 'set_added'		  => time(),
		  						 'set_updated'		  => time(),
		  						 'set_output_format'  => 'html',
		  						 'set_locked_uagent'  => '',
		  						 'set_hide_from_list' => 0 );
		}
		
		/* Grab the rest */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections',
								 'where'  => 'set_id IN (' . implode( ",", $skinIDs ) . ')' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Ensure settings */
			$row['set_id']    		   = $setid;
			$row['set_permissions']    = '*';
			$row['set_hide_from_list'] = 0;
			$row['set_css_inline']     = 1;
			
			$skins[ $row['set_id'] ] = $row;
			
			$setid++;
		}
		
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'skinsets' );
		
		foreach( $skins as $id => $setData )
		{
			$xml->addElementAsRecord( 'skinsets', 'set', $setData );
		}

		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML Replacements data file
	 *
	 * @access	public
	 * @param	int			Set ID
	 * @param	boolean		Just get the changes for this set (if TRUE)
	 * @return	mixed	bool, or XML
	 */
	public function generateReplacementsXML( $setID=0, $setOnly=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$replacements = array();
		
		//-----------------------------------------
		// Grab the CSS
		//-----------------------------------------
		
		if ( $setOnly === TRUE )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_replacements',
									 'where'  => 'replacement_set_id=' . $setID ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$replacements[ $row['replacement_key'] ] = $row;
			}
		}
		else
		{
			$replacements = $this->fetchReplacements( $setID );
		}
		
		if ( ! is_array( $replacements ) OR ! count( $replacements ) )
		{
			$this->_addMessage( "No replacements to export" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'replacements' );
		
		foreach( $replacements as $key => $replacementsData )
		{
			unset( $replacementsData['replacement_id'] );
			unset( $replacementsData['replacement_added_to'] );
			unset( $replacementsData['theorder'] );
			unset( $replacementsData['SAFE_replacement_content'] );
			
			$xml->addElementAsRecord( 'replacements', 'replacement', $replacementsData );
		}
		
		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML CSS data file
	 *
	 * @access	public
	 * @param	int			Set ID
	 * @param	boolean		Just get the changes for this set (if TRUE)
	 * @return	mixed		bool, or XML
	 */
	public function generateCSSXML( $app_dir='core', $setID=0, $setOnly=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$css          = array();
		$gotSomething = FALSE;
		
		//-----------------------------------------
		// Grab the CSS
		//-----------------------------------------

		if ( $setOnly === TRUE AND $setID > 0 )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_css',
									 'where'  => 'css_set_id=' . $setID ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$css[ $row['css_group'] ] = $row;
			}
		}
		else
		{
			$_css = $this->fetchCSS( $setID );
			
			/* Remove set 0 templates */
			if ( $setID > 0 )
			{
				if ( is_array( $_css ) AND count( $_css ) )
				{
					foreach( $_css as $name => $cssData )
					{
						if ( $cssData['css_set_id'] > 0 )
						{
							$css[ $name ] = $cssData;
						}
					}
				}
			}
			else
			{
				$css = $_css;
			}
		}

		if ( ! is_array( $css ) OR ! count( $css ) )
		{
			$this->_addMessage( "No CSS to export for " . $app_dir );
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'css' );

		foreach( $css as $name => $cssData )
		{
			/* Checking this app dir? */
			$cssData['css_app'] = ( $cssData['css_app'] ) ? $cssData['css_app']  : 'core';
			
			if ( $cssData['css_app'] != $app_dir )
			{
				continue;
			}
			
			$gotSomething = TRUE;
			
			unset( $cssData['css_id'] );
			unset( $cssData['css_added_to'] );
			unset( $cssData['theorder'] );
			unset( $cssData['_cssSize'] );
			
			$xml->addElementAsRecord( 'css', 'cssfile', $cssData );
		}

		if ( ! $gotSomething )
		{
			$this->_addMessage( "No CSS to export for " . $app_dir );
			return FALSE;
		}
		
		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML template data file
	 *
	 * @access	public
	 * @param	string		App
	 * @param	int			Set ID
	 * @param	boolean		Just get the changes for this set (if TRUE)
	 * @return	mixed		bool, or XML
	 */
	public function generateTemplateXML( $app, $setID=0, $setOnly=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templateGroups = array();
		
		//-----------------------------------------
		// XML
		//-----------------------------------------
		
		$infoXML = IPSLib::getAppDir(  $app ) . '/xml/information.xml';
		
		if ( ! file_exists( $infoXML ) )
		{
			$this->_addErrorMessage( "Could not locate: " . $infoXML );
			return FALSE;
		}
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->load( $infoXML );
		
		foreach( $xml->fetchElements( 'template' ) as $template )
		{
			$name  = $xml->fetchItem( $template );
			$match = $xml->fetchAttribute( $template, 'match' );
		
			if ( $name )
			{
				$templateGroups[ $name ] = $match;
			}
		}
		
		if ( ! is_array( $templateGroups ) OR ! count( $templateGroups ) )
		{
			$this->_addMessage( "Nothing to export for " . $app . ": No groups set in information.xml" );
			return FALSE;
		}
		
		//-----------------------------------------
		// Fetch templates
		//-----------------------------------------
		
		if ( $setOnly === TRUE AND $setID > 0 )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_templates',
									 'where'  => 'template_set_id=' . $setID ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$templates[ $row['template_group'] ][ strtolower( $row['template_name'] ) ] = $row;
			}
		}
		else
		{
			$_templates = $this->fetchTemplates( $setID );
			
			/* Remove set 0 templates */
			if ( $setID > 0 )
			{
				if ( is_array( $_templates ) AND count( $_templates ) )
				{
					foreach( $_templates as $group => $data )
					{
						foreach( $data as $name => $templateData )
						{
							if ( $templateData['template_set_id'] > 0 )
							{
								$templates[ $group ][ $name ] = $templateData;
							}
						}
					}
				}
			}
			else
			{
				$templates = $_templates;
			}
		}
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'templates', '', array( 'application' => $app, 'templategroups' => serialize( $templateGroups ) ) );
		
		if ( ! is_array( $templates ) OR ! count( $templates ) )
		{
			$this->_addMessage( "Nothing to export for " . $app . ": No template bits" );
			return FALSE;
		}
		
		$added   = 0;
		
		foreach( $templates as $group => $data )
		{
			$_okToGo = FALSE;
			
			foreach( $templateGroups as $name => $match )
			{
				if ( $match == 'contains' )
				{
					if ( stristr( $group, $name ) )
					{
						$_okToGo = TRUE;
						break;
					}
				}
				else if ( $group == $name )
				{
					$_okToGo = TRUE;
				}
			}
		
			if ( $_okToGo === TRUE )
			{
				$xml->addElement( 'templategroup', 'templates', array( 'group' => $group ) );
			
				foreach( $data as $name => $templateData )
				{
					unset( $templateData['theorder'] );
					unset( $templateData['template_id'] );
					unset( $templateData['template_set_id'] );
					unset( $templateData['template_added_to'] );
				
					$xml->addElementAsRecord( 'templategroup', 'template', $templateData );
					$added++;
				}
			}
		}
		
		if ( ! $added )
		{
			$this->_addMessage( "Nothing to export for " . $app );
			return FALSE;
		}
		
		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML Information data file
	 *
	 * @access	public
	 * @param	int			Set ID
	 * @return	string		XML
	 */
	public function generateInfoXML( $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$data    = array();
		$setData = $this->fetchSkinData( $setID );

		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'info' );
		
		$xml->addElementAsRecord( 'info', 'data', array( 'set_name'          => $setData['set_name'],
														 'set_author_name'   => $setData['set_author_name'],
														 'set_author_url'    => $setData['set_author_url'],
														 'set_output_format' => $setData['set_output_format'],
														 'ipb_major_version' => '3' ) );

		return $xml->fetchDocument();
	}
	
	/**
	 * Import CSS for a single app
	 *
	 * @access	public
	 * @param	string		App
	 * @param	string		Skin key to import
	 * @param	int			Set ID
	 * @return	mixed		bool, or number of items added
	 */
	public function importCSSAppXML( $app, $skinKey, $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$fileXML        = IPSLib::getAppDir(  $app ) . '/xml/' . $app . '_' . $skinKey . '_css.xml';
		$return			= array( 'updateCount' => 0, 'insertCount' => 0, 'updateBits' => array(), 'insertBits' => array() );
		
		//-----------------------------------------
		// File exists
		//-----------------------------------------
		
		if ( ! file_exists( $fileXML ) )
		{
			return FALSE;
		}
		
		if ( ! $setID and $skinKey != 'root' )
		{
			/* Figure out correct set ID based on key */
			$skinSetData = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_collections',
															'where'  => "set_key='" . $skinKey . "'" ) );
															
			$setID = $skinSetData['set_id'];
		}
		
		//-----------------------------------------
		// Delete all CSS if this is set ID 0
		//-----------------------------------------
		
		if ( ! $setID )
		{
			$this->DB->delete( 'skin_css', 'css_set_id=0 AND css_app=\''. addslashes( $app ) . '\'' );
		}
		
		//-----------------------------------------
		// Fetch CSS
		//-----------------------------------------
		
		$css = $this->parseCSSXML( file_get_contents( $fileXML ) );
	
		if ( is_array( $css ) )
		{
			foreach( $css as $_css )
			{
				if ( $_css['css_group'] )
				{
					$return['insertCount']++;
					
					if ( $setID )
					{
						$this->DB->delete( 'skin_css', 'css_set_id=' . $setID . ' AND css_group=\'' . addslashes( $_css['css_group'] ) . '\' AND css_app=\'' . addslashes( $_css['css_app'] ) . '\'' );
					}
					
					$this->DB->insert( 'skin_css', array( 'css_group'       => $_css['css_group'],
														  'css_content'     => $_css['css_content'],
														  'css_position'    => $_css['css_position'],
														  'css_updated'     => time(),
														  'css_app'		    => $_css['css_app'],
														  'css_app_hide'    => $_css['css_app_hide'],
														  'css_attributes'  => $_css['css_attributes'],
														  'css_modules'		=> $_css['css_modules'],
														  'css_set_id'      => $setID,
														  'css_added_to'    => $setID ) );
				}
			}
		}

		return $return;
	}
	 
	/**
	 * Export template CSS into app dirs
	 *
	 * @access	public
	 * @param	string		App to export into
	 * @param	int 		Set ID (0 / root by default )
	 * @return	void
	 */
	public function exportCSSAppXML( $app_dir, $setID=0 )
	{
		//-----------------------------------------
		// Get it
		//-----------------------------------------
		
		$setData = $this->fetchSkinData( $setID );
		$xml     = $this->generateCSSXML( $app_dir, $setID );
		
		//-----------------------------------------
		// Attempt to write...
		//-----------------------------------------
		
		/* Set file name */
		$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_' . $setData['set_key'] . '_css.xml';
		
		/* Attempt to unlink first */
		@unlink( $file );
		
		if ( $xml )
		{
			if ( file_exists( $file ) AND ! IPSLib::isWritable( $file ) )
			{
				$this->_addErrorMessage( $file . ' is not writeable' );
				return FALSE;
			}
			
			file_put_contents( $file, $xml );
			
			$this->_addMessage( "CSS for " . $app_dir . ' - ' . $setData['set_key'] . " created" );
		}
		else
		{
			//$this->_addMessage( "CSS XML for " . $app_dir . ' - ' . $setData['set_key'] . " could not be generated" );
		}
	}
	
	/**
	 * Export template XML into app dirs
	 *
	 * @access	public
	 * @param	string		App to export into
	 * @param	int			[Set ID (0/root if omitted)]
	 * @param	bool		Include root bits in any XML export. Default is true
	 * @return	void
	 */
	public function exportTemplateAppXML( $app_dir, $setID=0, $setOnly=TRUE )
	{
		//-----------------------------------------
		// Get it
		//-----------------------------------------
		
		$setData = $this->fetchSkinData( $setID );
		$xml     = $this->generateTemplateXML( $app_dir, $setID, $setOnly );
		
		//-----------------------------------------
		// Attempt to write...
		//-----------------------------------------
		
		/* Set file name */
		$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_' . $setData['set_key'] . '_templates.xml';
			
		/* Attempt to unlink first */
		@unlink( $file );
			
		if ( $xml )
		{
			if ( file_exists( $file ) AND ! IPSLib::isWritable( $file ) )
			{
				$this->_addErrorMessage( $file . ' is not writeable' );
				return FALSE;
			}
			
			file_put_contents( $file, $xml );
			
			$this->_addMessage( "Templates for " . $app_dir . ' - ' . $setData['set_key'] . " created" );
		}
		else
		{
			//$this->_addMessage( "XML for " . $app_dir . ' - ' . $setData['set_key'] . " could not be generated" );
		}
	}
	
	/**
	 * Import a single app
	 *
	 * @access	public
	 * @param	string		App
	 * @param	string		Skin key to import
	 * @param	int			Set ID
	 * @param	boolean		Set the edited / added flags to 0 (from install, upgrade)
	 * @return	mixed		bool, or array of info
	 */
	public function importTemplateAppXML( $app, $skinKey, $setID=0, $ignoreAddedEditedFlag=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templateGroups = array();
		$templates      = array();
		$fileXML        = IPSLib::getAppDir(  $app ) . '/xml/' . $app . '_' . $skinKey . '_templates.xml';
		$infoXML        = IPSLib::getAppDir(  $app ) . '/xml/information.xml';
		$return			= array( 'updateCount' => 0, 'insertCount' => 0, 'updateBits' => array(), 'insertBits' => array() );
		
		if( ! file_exists($fileXML) )
		{
			return $return;
		}
		
		if ( ! $setID and $skinKey != 'root' )
		{
			/* Figure out correct set ID based on key */
			$skinSetData = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_collections',
															'where'  => "set_key='" . $skinKey . "'" ) );
															
			$setID = $skinSetData['set_id'];
		}
		
		/* Set ignore flag correctly */
		if ( ! empty( $skinKey ) AND in_array( $skinKey, array( 'root', 'lofi', 'xmlskin' ) ) )
		{
			$ignoreAddedEditedFlag = true;
		}
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->load( $infoXML );
		
		foreach( $xml->fetchElements( 'template' ) as $template )
		{
			$name  = $xml->fetchItem( $template );
			$match = $xml->fetchAttribute( $template, 'match' );
		
			if ( $name )
			{
				$templateGroups[ $name ] = $match;
			}
		}
		
		if ( ! is_array( $templateGroups ) OR ! count( $templateGroups ) )
		{
			$this->_addMessage( "Nothing to export for " . $app );
			return FALSE;
		}
		
		//-----------------------------------------
		// Fetch templates
		//-----------------------------------------
	
		$_templates = $this->fetchTemplates( $setID, 'allNoContent' );
		$_MASTER    = $this->fetchTemplates( 0, 'allNoContent' );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		foreach( $_templates as $group => $data )
		{
			$_okToGo = FALSE;
			
			foreach( $templateGroups as $name => $match )
			{
				if ( $match == 'contains' )
				{
					if ( stristr( $group, $name ) )
					{
						$_okToGo = TRUE;
						break;
					}
				}
				else if ( $group == $name )
				{
					$_okToGo = TRUE;
				}
			}
			
			if ( $_okToGo === TRUE )
			{
				foreach( $data as $name => $templateData )
				{
					$templates[ $group ][ $name ] = $templateData;
				}
			}
		}
		
		//-----------------------------------------
		// Wipe the master skins
		//-----------------------------------------
		
		if ( $setID == 0 )
		{
			$this->DB->delete( 'skin_templates', "template_set_id=0 AND template_group IN ('" . implode( "','", array_keys( $templates ) ) . "') AND template_user_added=0 AND template_added_to=0" );
			
			/* Now wipe the array so we enforce creation */
			unset( $templates );
		}
					
		//-----------------------------------------
		// Now grab the actual XML files
		//-----------------------------------------

		$xml->load( $fileXML );

		foreach( $xml->fetchElements( 'template' ) as $templatexml )
		{
			$data = $xml->fetchElementsFromRecord( $templatexml );
			
			/* Figure out if this is added by a user or not */
			if ( $ignoreAddedEditedFlag === TRUE )
			{
				$isAdded  = 0;
				$isEdited = 0;
			}
			else
			{
				$isAdded  = ( is_array( $_MASTER[ $data['template_group'] ][ strtolower( $data['template_name'] ) ] ) AND ! $_MASTER[ $data['template_group'] ][ strtolower( $data['template_name'] ) ]['template_user_added'] ) ? 0 : 1;
				$isEdited = 1;
			}
			
			if ( is_array( $templates[ $data['template_group'] ][ strtolower( $data['template_name'] ) ] ) AND $templates[ $data['template_group'] ][ strtolower( $data['template_name'] ) ]['template_set_id'] == $setID )
			{
				/* Update.. */
				$return['updateCount']++;
				$return['updateBits'][] = $data['template_name'];
				$this->DB->update( 'skin_templates', array( 'template_content'     => $data['template_content'],
															'template_data'        => $data['template_data'],
															'template_user_edited' => $isEdited,
														    'template_user_added'  => $isAdded,
															'template_updated'     => time() ), 'template_set_id=' . $setID . " AND template_group='" . $data['template_group'] . "' AND template_name='" . $data['template_name'] . "'" );
			}
			else
			{
				/* Add... */
				$return['insertCount']++;
				$return['insertBits'][] = $data['template_name'];
				$this->DB->insert( 'skin_templates', array( 'template_set_id'      => $setID,
															'template_group'       => $data['template_group'],
														    'template_content'     => $data['template_content'],
														    'template_name'        => $data['template_name'],
														    'template_data'        => $data['template_data'],
														    'template_removable'   => ( $setID ) ? $data['template_removable'] : 0,
														    'template_added_to'    => $setID,
														    'template_user_edited' => $isEdited,
														    'template_user_added'  => $isAdded,
														    'template_updated'     => time() ) );
			}
		}

		return $return;
	}
	
}