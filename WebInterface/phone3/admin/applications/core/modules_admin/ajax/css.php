<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * AJAX Functions For applications/core/js/ipb3CSS.js file
 * Last Updated: $Date: 2009-05-22 22:31:45 -0400 (Fri, 22 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4685 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_css extends ipsAjaxCommand 
{
	/**
	 * Skin functions object handle
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
    /**
	 * Main executable
	 *
	 * @access	public
	 * @param	object	registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
    {
    	$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );
    	
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		$this->skinFunctions = new skinCaching( $registry );

		/* Check... */
		if ( ! $registry->getClass('class_permissions')->checkPermission( 'css_manage', ipsRegistry::$current_application, 'templates' ) )
		{
			$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
	    	exit();
		}
				
    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
    		case 'getCSSGroups':
    			$this->_getCSSGroups();
    		break;
			case 'getCSSForEdit':
				$this->_getCSSForEdit();
			break;
			case 'saveCSS':
				$this->_saveCSS();
			break;
			case 'revertCSS':
				/* Check... */
				if ( !$registry->getClass('class_permissions')->checkPermission( 'css_delete', ipsRegistry::$current_application, 'templates' ) )
				{
					$this->returnJsonError( $registry->lang->words['sk_ajax_noperm'] );
			    	exit();
				}
				$this->_revertCSS();
			break;
    	}
    }
    
	/**
	 * Reverts CSS
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _revertCSS()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$setID      = intval( $this->request['setID'] );
		$cssID      = intval( $this->request['css_id'] );
		$fromDelete = intval( $this->request['fromDelete'] );
		
    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( ! $setID OR ! $cssID  )
    	{ 
    		$this->returnJsonError('Missing Data');
    		exit();
    	}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$css = $this->skinFunctions->revertCSS( $cssID, $setID, $fromDelete );
		
		$this->returnJsonArray( array( 'cssData' => $css, 'errors' => $this->skinFunctions->fetchErrorMessages()  ) );
    }

	/**
	 * Saves the CSS
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _saveCSS()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$setID              = intval( $this->request['setID'] );
		$cssID              = intval( $this->request['css_id'] );
		$type               = ( $this->request['type'] == 'add' ) ? 'add' : 'edit';
    	$css_content        = $_POST['css_content'];
		$css_group          = IPSText::alphanumericalClean( $_POST['_css_group'] );
		$css_position		= intval( $this->request['css_position'] );
		$css_attributes		= $_POST['css_attributes'];
		$css_app			= IPSText::alphanumericalClean( $_POST['css_app'] );
		$css_app_hide		= intval( $this->request['css_app_hide'] );
		$css_modules		= trim( $this->request['css_modules'] );
		
    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( ! $setID OR ( $type == 'edit' AND ! $cssID ) )
    	{ 
    		$this->returnJsonError('Missing Data');
    		exit();
    	}

		//-----------------------------------------
		// Add checks
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			if ( ! $css_group )
			{
				$this->returnJsonError('Missing Data');
	    		exit();
	    	}
		}
		
		//-----------------------------------------
		// Save it
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			$css_id = $this->skinFunctions->saveCSSFromEdit( $cssID, $setID, $css_content, $css_group, $css_position, $css_attributes, $css_app, $css_app_hide, $css_modules );
		}
		else
		{
			try
			{
				$css_id = $this->skinFunctions->saveCSSFromAdd( $setID, $css_content, str_replace( '.css', '', $css_group ), $css_position, $css_attributes, $css_app, $css_app_hide, $css_modules );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $err->getMessage() );
	    		exit();
			}
		}
		
		//-----------------------------------------
		// Get Data
		//-----------------------------------------
		
		$css = $this->skinFunctions->fetchCSSForEdit( $css_id, $setID );
		
		$this->returnJsonArray( array( 'cssData' => $css, 'errors' => $this->skinFunctions->fetchErrorMessages() ) );
    }

	/**
	 * Fetch a JSON list of template data ready for editing
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _getCSSForEdit()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$setID  = intval( $this->request['setID'] );
		$cssID  = intval( $this->request['css_id'] );
    	
    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( ! $setID OR ! $cssID  )
    	{ 
    		$this->returnJsonError('Missing Data');
    		exit();
    	}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$css = $this->skinFunctions->fetchCSSForEdit( $cssID, $setID );
		
		$this->returnJsonArray( array( 'cssData' => $css ) );
    }

    /**
	 * Fetch a JSON list of CSS group names
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _getCSSGroups()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$setID = intval( $this->request['setID'] );
    	
    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( ! $setID )
    	{ 
    		$this->returnJsonError('Missing Data');
    		exit();
    	}

		//-----------------------------------------
		// Get CSS
		//-----------------------------------------
	
		$_css = $this->skinFunctions->fetchCSS( $setID );
		
		//-----------------------------------------
		// Fix up positioning
		//-----------------------------------------
		
		foreach( $_css as $_id => $_data )
		{
			unset( $_data['css_content'] );
			$css[ $_data['css_position'] . '.' . $_data['css_id'] ] = $_data;
		}
		
		ksort( $css, SORT_NUMERIC );
	
		$this->returnJsonArray( array( 'css' => $css ) );
    }
}