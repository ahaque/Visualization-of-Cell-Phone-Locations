<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * AJAX Functions For applications/core/js/ipb3Templates.js file
 * Last Updated: $Date: 2009-05-15 21:17:56 -0400 (Fri, 15 May 2009) $
 *
 * Author: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4663 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_templatediff extends ipsAjaxCommand 
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
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );
		
		$this->skinFunctions = new skinDifferences( $registry );

		/* Check... */
		if ( !$registry->getClass('class_permissions')->checkPermission( 'skindiff_reports', ipsRegistry::$current_application, 'templates' ) )
		{
			$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
	    	exit();
		}
				
    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
			default:
    		case 'process':
    			$this->_process();
    		break;
			case 'viewDiff':
				$this->_viewDiff();
			break;
    	}
    }
    
	/**
	 * Grab a diff to show
	 *
	 * @access	private
	 * @return	string	JSON
	 */
	private function _viewDiff()
	{
		$key = trim( $this->request['key'] );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$diff_row = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'template_diff_changes',
													 'where'  => "diff_change_key='" . $this->DB->addslashes($key) . "'"  ) );
		
		
		if ( ! $diff_row['diff_change_key'] )
		{
			$this->returnJsonError('No Key Found');
    		exit();
		}
		
		$diff_row['diff_change_content'] = str_replace( "\n", "<br>", $diff_row['diff_change_content']);
		$diff_row['diff_change_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$diff_row['diff_change_content']);
		$diff_row['diff_change_content'] = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$diff_row['diff_change_content']);
		$diff_row['diff_change_content'] = str_replace( "\t", "&nbsp; &nbsp; ", $diff_row['diff_change_content'] );
		
		$this->returnJsonArray( $diff_row );
	}
	
	/**
	 * Process
	 *
	 * @access	private
	 * @return	string		Json
	 */
    private function _process()
    {
    	//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$pergo         = intval( $this->request['perGo'] ) ? intval( $this->request['perGo'] ) : 10;
		$diffSessionID = intval( $this->request['sessionID'] );
		$completed     = 0;
			
		//-----------------------------------------
		// Fetch current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $diffSessionID );
		
		if ( $session === FALSE )
		{
			$this->returnJsonError('Could not locate a valid differences session');
    		exit();
		}
		
		//-----------------------------------------
		// Get Diff library
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classDifference.php' );
		$classDifference         = new classDifference();
		$classDifference->method = 'PHP';
		
		//-----------------------------------------
		// Get template bits to check
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_templates',
								 'where'  => 'template_set_id=0',
								 'order'  => 'template_id ASC',
								 'limit'  => array( intval( $session['diff_session_done'] ), intval( $pergo ) ) ) );
												 
		$outer = $this->DB->execute();
		
		if ( ! $this->DB->getTotalRows( $outer ) )
		{
			$completed = 1;
		}
		else
		{
			while( $row = $this->DB->fetch( $outer ) )
			{
				//-----------------------------------------
				// Get corresponding row from diff table
				//-----------------------------------------
				
				$diff_row = $this->DB->buildAndFetch( array( 'select' => '*',
															 'from'   => 'templates_diff_import',
															 'where'  => "diff_func_group='{$row['template_group']}' AND diff_func_name='{$row['template_name']}' AND diff_session_id=".$diffSessionID ) );
																			  
				//-----------------------------------------
				// Got anything?
				//-----------------------------------------
				
				if ( $diff_row['diff_key'] )
				{
					//-----------------------------------------
					// Get difference
					//-----------------------------------------
		
					$difference = $classDifference->getDifferences( $diff_row['diff_func_content'], $row['template_content'] );
					
					//-----------------------------------------
					// Got any differences?
					//-----------------------------------------
					
					if ( $classDifference->diff_found )
					{
						//-----------------------------------------
						// Get corresponding row from diff table
						//-----------------------------------------
						
						$diff_check = $this->DB->buildAndFetch( array( 'select' => 'diff_change_key',
																	   'from'   => 'template_diff_changes',
																	   'where'  => "diff_change_key='" . $diffSessionID.':'.$row['template_group'].':'.$row['template_name'] . "'" ) );

						if ( $diff_check['diff_change_key'] )
						{
							$this->DB->update( 'template_diff_changes', array( 'diff_change_func_group'     => $row['template_group'],
																			   'diff_change_func_name'      => $row['template_name'],
																			   'diff_change_content'        => $difference,
																			   'diff_change_type'           => 1,
																			   'diff_session_id'            => $diffSessionID ),
																			   "diff_change_key='" . $diffSessionID.':'.$row['template_group'].':'.$row['template_name'] . "'" );
						}
						else
						{
							$this->DB->insert( 'template_diff_changes', array( 'diff_change_key'            => $diffSessionID.':'.$row['template_group'].':'.$row['template_name'],
																			   'diff_change_func_group'     => $row['template_group'],
																			   'diff_change_func_name'      => $row['template_name'],
																			   'diff_change_content'        => $difference,
																			   'diff_change_type'           => 1,
																			   'diff_session_id'            => $diffSessionID ) );
						}
					}
				}
				else
				{
					if ( ! $session['diff_session_ignore_missing'] )
					{
						$this->DB->insert( 'template_diff_changes', array( 'diff_change_key'            => $diffSessionID.':'.$row['template_group'].':'.$row['template_name'],
																		   'diff_change_func_group'     => $row['template_group'],
																		   'diff_change_func_name'      => $row['template_name'],
																		   'diff_change_content'        => htmlspecialchars($row['template_content']),
																		   'diff_change_type'           => 0,
																		   'diff_session_id'            => $diffSessionID ) );
					}
				}
				
				//-----------------------------------------
				// Increment
				//-----------------------------------------
				
				$session['diff_session_done']++;
			}
		}
		
		//-----------------------------------------
		// Update current session
		//-----------------------------------------
		
		$this->DB->update( 'template_diff_session', array( 'diff_session_done' => intval( $session['diff_session_done'] ) ), 'diff_session_id='.$diffSessionID );
		
		//-----------------------------------------
		//  Done or more?
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'processed' => $session['diff_session_done'], 'completed' => $completed, 'message' => $session['diff_session_done'] . ' template bits processed...' ) );
    }
}