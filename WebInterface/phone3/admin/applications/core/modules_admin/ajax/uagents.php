<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * AJAX Functions For applications/core/js/ipb3CSS.js file
 * Last Updated: $Date: 2009-07-06 21:23:35 -0400 (Mon, 06 Jul 2009) $
 *
 * Author: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4843 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_uagents extends ipsAjaxCommand 
{
	/**
	 * User agent functions
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $userAgentFunctions;
	
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
	
		require_once( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php' );
		
		$this->userAgentFunctions = new userAgentFunctions( $registry );
		
    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
			case 'saveuAgent':
				if ( !$registry->getClass('class_permissions')->checkPermission( 'ua_manage', ipsRegistry::$current_application, 'tools' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
			    	exit();
				}
				$this->_saveuAgent();
			break;
			case 'removeuAgent':
				if ( !$registry->getClass('class_permissions')->checkPermission( 'ua_remove', ipsRegistry::$current_application, 'tools' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
			    	exit();
				}
				$this->_removeuAgent();
			break;
    	}
    }
    
	/**
	 * Reverts replacement
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _removeuAgent()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$uagent_id				= intval( $this->request['uagent_id'] );

    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( ! $uagent_id )
    	{ 
    		$this->returnJsonError('Missing Data');
    		exit();
    	}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$userAgents = $this->userAgentFunctions->removeUserAgent( $uagent_id );
		
		$this->returnJsonArray( array( 'uagents' => $userAgents, 'errors' => $this->userAgentFunctions->fetchErrorMessages() ) );
    }

	/**
	 * Saves the user agent
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _saveuAgent()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$uagent_id				= intval( $this->request['uagent_id'] );
		$uagent_key				= IPSText::alphanumericalClean( $this->request['uagent_key'] );
		$uagent_name			= $this->convertAndMakeSafe( $_POST['uagent_name'] );
		$uagent_regex			= $this->convertUnicode( $_POST['uagent_regex'] );
		$uagent_regex_capture	= intval( $this->request['uagent_regex_capture'] );
		$uagent_type			= IPSText::alphanumericalClean( $this->request['uagent_type'] );
		$uagent_position	    = intval( $this->request['uagent_position'] );
		$type					= $this->request['type'];
		
    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( $type == 'edit' AND ! $uagent_id )
    	{ 
    		$this->returnJsonError('Missing Data');
    		exit();
    	}

		//-----------------------------------------
		// Other checks
		//-----------------------------------------
		
		if ( ! $uagent_key OR ! $uagent_name OR ! $uagent_regex OR ! $uagent_type )
		{
			$this->returnJsonError('Missing Data');
	    	exit();
	    }
		
		//-----------------------------------------
		// Save it
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			try
			{
				$userAgentID = $this->userAgentFunctions->saveUserAgentFromEdit( $uagent_id, $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $err->getMessage() . ' ' . str_replace( "\n", "\\n", implode( ",", $this->userAgentFunctions->fetchMessages() ) ) );
	    		exit();
			}
		}
		else
		{
			try
			{
				$userAgentID = $this->userAgentFunctions->saveUserAgentFromAdd( $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position );
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
		
		$userAgents = $this->userAgentFunctions->fetchAgents();
		
		$this->returnJsonArray( array( 'uagents' => $userAgents, 'returnid' => $userAgentID, 'errors' => $this->userAgentFunctions->fetchErrorMessages() ) );
    }
}