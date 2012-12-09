<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin search and replace tool
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_searchandreplace extends ipsCommand
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
	
	/**
	 * Amount of items per go
	 *
	 * @access	private
	 * @var		int
	 */
	private $_bitsPerRound = 50;
	
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=searchandreplace';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=searchandreplace';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );
		
		$this->skinFunctions = new skinDifferences( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'templates_manage' );
				$this->_showForm();
			break;
			case 'start':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'templates_manage' );
				$this->_start();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Processes the template bits...
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _start()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID			   = intval( $this->request['setID'] );
		$searchParents 	   = intval( $this->request['searchParents'] );
		$isRegex	   	   = intval( $this->request['isRegex'] );
 		$searchFor_RAW     = IPSText::stripslashes( $_POST['searchFor'] );
		$replaceWith_RAW   = IPSText::stripslashes( $_POST['replaceWith'] );
		$_finalMatches	   = array();
		
		/* Checks */
		if ( ! $searchFor_RAW )
		{
			$this->registry->output->global_error = $this->lang->words['sr_sometext'];
			return $this->_showForm();
		}
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );

		try
		{
			$result = $this->skinFunctions->searchTemplates( $setID, $_POST['searchFor'], $isRegex, $searchParents );

			if ( $result['matchCount'] )
			{
				/* Finalize */
				foreach( $result['matches'] as $_group => $_gdata )
				{
					foreach( $_gdata as $_name => $_data )
					{
						$_finalMatches[ $_group ][ $_name ] = $_data['template_id'];
					}
				}
				
				/* Prep array */
				$sessionData = array(   'sandr_set_id' 				=> $setID,
										'sandr_search_only' 		=> ( $replaceWith_RAW ) ? 0 : 1,
										'sandr_search_all' 			=> $searchParents,
										'sandr_search_for' 			=> $searchFor_RAW,
										'sandr_replace_with' 		=> $replaceWith_RAW,
										'sandr_is_regex' 			=> $isRegex,
										'sandr_template_count' 		=> $result['searchCount'],
										'sandr_template_processed'  => $result['searchCount'],
										'sandr_updated'				=> time(),
										'sandr_results'				=> serialize( $_finalMatches ) );

				/* Insert into DB */
				$this->DB->insert( 'template_sandr', $sessionData );

				$sessionData['sandr_session_id'] = $this->DB->getInsertID();
			}
			else
			{
				$this->registry->output->global_error = $this->lang->words['sr_nomatches'];
				return $this->_showForm();
			}
			
			//-----------------------------------------
			// Print it...
			//-----------------------------------------

			$this->registry->output->html .= $this->html->searchandreplace_listTemplateGroups( $_finalMatches, $setData, $sessionData );
		}
		catch( Exception $error )
		{
			$this->registry->output->global_error = $error->getMessage();
			return $this->_showForm();
		}
	}
	
	/**
	 * Displays the search form
	 *
	 * @access	private
	 * @return	void
	 */
	private function _showForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->registry->output->html = $this->html->searchandreplace_form( $this->skinFunctions->getTiersFunction()->fetchAllsItemDropDown(), $this->skinFunctions->fetchNumberTemplateBits(), $this->_bitsPerRound );
	}

}
