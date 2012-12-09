<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Email logs
 * Last Updated: $LastChangedDate: 2009-08-18 03:26:21 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 5023 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_logs_emaillogs extends ipsCommand 
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;

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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_emaillogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=emaillogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=emaillogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );

		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=emaillogs', $this->lang->words['elog_emaillogs'] );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'list':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emaillogs_view' );
				$this->_listCurrent();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emaillogs_delete' );
				$this->_remove();
			break;
				
		    case 'viewemail':
		    	$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emaillogs_view' );
		    	$this->_viewEmail();
		    break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
	
	/**
	 * Remove email logs
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _remove()
	{
		if ( $this->request['type'] == 'all' )
		{
			$this->DB->delete( 'email_logs' );
		}
		else
		{
			$ids = array();
		
			foreach ( $this->request as $k => $v )
			{
				if ( preg_match( "/^id_(\d+)$/", $k, $match ) )
				{
					if ($this->request[  $match[0] ] )
					{
						$ids[] = $match[1];
					}
				}
			}

			$ids = IPSLib::cleanIntArray( $ids );
			
			//-----------------------------------------
			
			if ( count($ids) < 1 )
			{
				$this->registry->output->showError( $this->lang->words['elog_noneselected'], 11119 );
			}
			
			$this->DB->delete( 'email_logs', "email_id IN (" . implode( ',', $ids ) . ")" );
		}
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( $this->lang->words['elog_removed'] );
		
		$this->registry->output->silentRedirect( $this->settings['base_url']."&{$this->form_code}" );
	}
	
	/**
	 * List the current logs
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _listCurrent()
	{
		$start = intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;

		//-----------------------------------------
		// Check URL parameters
		//-----------------------------------------
		
		$url_query	= array();
		$db_query	= array();
		
		if ( $this->request['type'] AND $this->request['type'] != "" )
		{
			$this->registry->output->html_help_title .= $this->lang->words['elog_results'];

			switch( $this->request['type'] )
			{
				case 'fromid':
					$url_query[]	= 'type=fromid';
					$url_query[]	= 'id=' . intval($this->request['id']);
					$db_query[]		= 'email.from_member_id=' . intval($this->request['id']);
				break;

				case 'toid':
					$url_query[]	= 'type=toid';
					$url_query[]	= 'id=' . intval($this->request['id']);
					$db_query[]		= 'email.to_member_id=' . intval($this->request['id']);
				break;

				case 'subject':
					$string = IPSText::parseCleanValue( urldecode($this->request['string']) );

					if ( $string == "" )
					{
						$this->registry->output->showError( $this->lang->words['elog_notsearched'], 11120 );
					}

					$url_query[]	= 'type=' . $this->request['type'];
					$url_query[]	= 'string=' . urlencode($string);
					$db_query[]		= $this->request['match'] == 'loose' ? "email.email_subject LIKE '%{$string}%'" : "email.email_subject='{$string}'";
				break;

				case 'content':
					$string = IPSText::parseCleanValue( urldecode($this->request['string']) );
					
					if ( $string == "" )
					{
						$this->registry->output->showError( $this->lang->words['elog_notsearched'], 11121 );
					}
					
					$url_query[]	= 'type=' . $this->request['type'];
					$url_query[]	= 'string=' . urlencode($string);
					$db_query[]		= $this->request['match'] == 'loose' ? "email.email_content LIKE '%{$string}%'" : "email.email_content='{$string}'";
				break;

				case 'email_from':
					$string = IPSText::parseCleanValue( urldecode($this->request['string']) );
					
					if ( $string == "" )
					{
						$this->registry->output->showError( $this->lang->words['elog_notsearched'], 11122 );
					}
					
					$url_query[]	= 'type=' . $this->request['type'];
					$url_query[]	= 'string=' . urlencode($string);
					$db_query[]		= $this->request['match'] == 'loose' ? "email.from_email_address LIKE '%{$string}%'" : "email.from_email_address='{$string}'";
				break;

				case 'email_to':
					$string = IPSText::parseCleanValue( urldecode($this->request['string']) );
					
					if ( $string == "" )
					{
						$this->registry->output->showError( $this->lang->words['elog_notsearched'], 11123 );
					}
					
					$url_query[]	= 'type=' . $this->request['type'];
					$url_query[]	= 'string=' . urlencode($string);
					$db_query[]		= $this->request['match'] == 'loose' ? "email.to_email_address LIKE '%{$string}%'" : "email.to_email_address='{$string}'";
				break;

				case 'name_from':
					$string = IPSText::parseCleanValue( urldecode($this->request['string']) );

					if ( $string == "" )
					{
						$this->registry->output->showError( $this->lang->words['elog_notsearched'], 11124 );
					}

					if ( $this->request['match'] == 'loose' )
					{
						$this->DB->build( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_display_name LIKE '%{$string}%'" ) );
						$this->DB->execute();

						if ( ! $this->DB->getTotalRows() )
						{
							$this->registry->output->showError( $this->lang->words['elog_nomatches'], 111160 );
						}
						
						$ids = array();
						
						while ( $r = $this->DB->fetch() )
						{
							$ids[] = $r['member_id'];
						}
						
						$db_query[] = 'email.from_member_id IN(' . implode( ',', $ids ) . ')';
					}
					else
					{
						$r = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_display_name='{$string}'" ) );

						if ( ! $r['member_id'] )
						{
							$this->registry->output->showError( $this->lang->words['elog_nomatches'], 11125 );
						}

						$db_query[] = 'email.from_member_id=' . $r['member_id'];
					}
					
					$url_query[]	= 'type=' . $this->request['type'];
					$url_query[]	= 'string=' . urlencode($string);
				break;

				case 'name_to':
					$string = urldecode($this->request['string']);
					
					if ( $string == "" )
					{
						$this->registry->output->showError( $this->lang->words['elog_notsearched'], 11126 );
					}

					if ( $this->request['match'] == 'loose' )
					{
						$this->DB->build( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_display_name LIKE '%{$string}%'" ) );
						$this->DB->execute();
						
						if ( ! $this->DB->getTotalRows() )
						{
							$this->registry->output->showError( $this->lang->words['elog_nomatches'], 11127 );
						}

						$ids = array();
						
						while ( $r = $this->DB->fetch() )
						{
							$ids[] = $r['member_id'];
						}
						
						$db_query[] = 'email.to_member_id IN(' . implode( ',', $ids ) . ')';
					}
					else
					{
						$r = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_display_name='{$string}'" ) );

						if ( ! $r['member_id'] )
						{
							$this->registry->output->showError( $this->lang->words['elog_nomatches'], 11128 );
						}
						
						$db_query[] = 'email.to_member_id=' . $r['member_id'];
					}
					
					$url_query[]	= 'type=' . $this->request['type'];
					$url_query[]	= 'string=' . urlencode($string);
				break;
			}
		}
		
		if( $this->request['match'] )
		{
			$url_query[]	= 'match=' . $this->request['match'];
		}
		
		//-----------------------------------------
		// LIST 'EM
		//-----------------------------------------
		
		$dbe	= "";
		$url	= "";
		
		if ( count($db_query) > 0 )
		{
			$dbe = implode(' AND ', $db_query );
		}
		
		if ( count($url_query) > 0 )
		{
			$url = '&amp;' . implode( '&amp;', $url_query );
		}
		
		$count = $this->DB->buildAndFetch( array( 'select'	=> 'count(email.email_id) as cnt',
														'from'	=> 'email_logs email',
														'where'	=> $dbe ) );

		$links = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['cnt'],
																		'itemsPerPage'		=> 25,
																		'currentStartValue'	=> $start,
																		'baseUrl'			=> $this->settings['base_url'] . "&{$this->form_code}" . $url,
														)
												 );

		$this->DB->build( array( 'select'		=> 'email.*',
										'from'		=> array( 'email_logs' => 'email' ),
										'where'		=> $dbe,
										'order'		=> 'email.email_date DESC',
										'limit'		=> array( $start, 25 ),
										'add_join'	=> array(
															array( 'select'	=> 'm.member_id, m.members_display_name',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=email.from_member_id',
																	'type'	=> 'left'
																),
															array( 'select'	=> 'mem.member_id as to_id, mem.members_display_name as to_name',
																	'from'	=> array( 'members' => 'mem' ),
																	'where'	=> 'mem.member_id=email.to_member_id',
																	'type'	=> 'left'
																),
															)
							)		);
		$this->DB->execute();

		while ( $row = $this->DB->fetch() )
		{
			$row['_date']	= $this->registry->class_localization->getDate( $row['email_date'], 'SHORT' );
			
			$rows[]			= $row;
		}

		$this->registry->output->html .= $this->html->emaillogsWrapper( $rows, $links );
	}
	
	/**
	 * View a single email
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _viewEmail()
	{
		if ( $this->request['id'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['elog_log_404'], 11129 );
		}
		
		$id = intval($this->request['id']);
		
		$row = $this->DB->buildAndFetch( array( 'select'		=> 'email.*',
														'from'		=> array( 'email_logs' => 'email' ),
														'where'		=> 'email.email_id=' . $id,
														'add_join'	=> array(
																			array( 'select'	=> 'm.member_id, m.members_display_name as name',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=email.from_member_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'mem.member_id as to_id, mem.members_display_name as to_name',
																					'from'	=> array( 'members' => 'mem' ),
																					'where'	=> 'mem.member_id=email.to_member_id',
																					'type'	=> 'left'
																				),
																			)
											)		);

		if ( ! $row['email_id'] )
		{
			$this->registry->output->showError( $this->lang->words['elog_log_404'], 11130 );
		}
		
		$row['_date']			= $this->registry->class_localization->getDate( $row['email_date'], 'LONG' );
		
		$this->registry->output->html .= $this->html->emaillogsEmail( $row );
		
		$this->registry->output->printPopupWindow();
	}
}