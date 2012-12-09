<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Online list
 * Last Updated: $Date: 2009-07-08 17:04:40 -0400 (Wed, 08 Jul 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		12th March 2002
 * @version		$Revision: 4855 $
 *
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
} 

class public_members_messaging_search extends ipsCommand
{
	/**
	 * Page title
	 *
	 * @access	private
	 * @var		string
	 */
	private $_title;
	
	/**
	 * Navigation entries
	 *
	 * @access	private
	 * @var		array
	 */
	private $_navigation;
	
	/**
	 * Messenger library
	 *
	 * @access	public
	 * @var		object
	 */
	public $messengerFunctions;
	
	/**
	 * Error string
	 *
	 * @access	private
	 * @var		string
	 */
	public $_errorString = '';
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10226 );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10227 );
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_no_guests', 10228 );
		}
		
		if( ! IPSLib::moduleIsEnabled( 'messaging', 'members' ) )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10227 );
		}		

    	//-----------------------------------------
    	// Language
    	//-----------------------------------------
    	
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
    	
		//-----------------------------------------
		// Grab class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
		$this->messengerFunctions = new messengerFunctions( $registry );
		
		/* Messenger Totals */
		$totals = $this->messengerFunctions->buildMessageTotals();
		
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
			default:
    		case 'search':
				$html = $this->_search();
    		break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->registry->output->addContent( $this->registry->getClass('output')->getTemplate('messaging')->messengerTemplate( $html, $this->messengerFunctions->_jumpMenu, $this->messengerFunctions->_dirData, $totals, $this->_topicParticipants, $this->_errorString ) );
    	$this->registry->output->setTitle( $this->_title );
		
		$this->registry->output->addNavigation( $this->lang->words['messenger__nav'], 'app=members&amp;module=messaging' );
		
		if ( is_array( $this->_navigation ) AND count( $this->_navigation ) )
		{
			foreach( $this->_navigation as $idx => $data )
			{
				$this->registry->output->addNavigation( $data[0], $data[1] );
			}
    	}

        $this->registry->output->sendOutput();
 	}
	

	
	/**
	 * Search. Do it.
	 *
	 * @access	private
	 * @param	string		Any error text
	 * @return	string		returns HTML
	 */
	private function _search( $error='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start     		   = intval($this->request['st']);
		$p_end     		   = $this->settings['show_max_msg_list'] > 0 ? $this->settings['show_max_msg_list'] : 50;
		$searchFor_TAINTED = IPSText::parseCleanValue( urldecode( $_REQUEST['searchFor'] ) );
		$searchIn		   = '';
 		
		/* Got an error? */
		if ( $error )
		{
			$this->_errorString = $error;
		}
		
		/* Search for owt? */
		if( ! $searchFor_TAINTED )
		{
			$error = $this->lang->words['search_convo_no_keywords'];
		}
		else if( ( $this->settings['min_search_word'] && strlen( $searchFor_TAINTED ) < $this->settings['min_search_word'] ) )
		{
			$error = sprintf( $this->lang->words['search_term_short'], $this->settings['min_search_word'] );
		}
		/* Do the search */
		else
		{
			$searchResults = $this->messengerFunctions->searchMessages( $this->memberData['member_id'], $searchFor_TAINTED, $start, $p_end, array() );
			$totalMsg      = $searchResults['totalMatches'];
			$messages      = $searchResults['results'];
			
			/* Got anything? */
			if ( ! $totalMsg OR ( ! count( $messages ) ) )
			{
				$error = $this->lang->words['search_convo_no_results'];
			}			
		}
 		//-----------------------------------------
 		// Generate Pagination
 		//-----------------------------------------
 		
 		$pages = $this->registry->getClass('output')->generatePagination( array( 'totalItems'         => $totalMsg,
														  						 'itemsPerPage'       => $p_end,
														  						 'currentStartValue'  => $start,
														  						 'baseUrl'            => "app=members&amp;module=messaging&amp;section=search&amp;do=search&amp;searchFor=" . urlencode( $searchFor_TAINTED ) ) );
	
		//-----------------------------------------
		// Set title
		//-----------------------------------------
		
		$this->_title = $this->lang->words['t_welcome'] . $this->lang->words['search_results_pt'];
		
		//-----------------------------------------
		// Set navigation
		//-----------------------------------------
		
		//$this->_navigation[] = array( $this->messengerFunctions->_dirData[ $this->messengerFunctions->_currentFolderID ]['real'], $this->settings['base_url']."app=members&amp;module=messaging&amp;section=view&amp;do=showFolder&amp;folderID=".$this->messengerFunctions->_currentFolderID."&amp;sort=".$this->request['sort'] );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		return $this->registry->getClass('output')->getTemplate('messaging')->showSearchResults( $messages, $pages, $error );
	}
}