<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Attachments: Search
 * Last Updated: $LastChangedDate: 2009-06-24 23:14:22 -0400 (Wed, 24 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		Mon 24th May 2004
 * @version		$Rev: 4818 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_attachments_search extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
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
		// Load HTML and language bits
		//-----------------------------------------
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_attachments' );
		$this->lang->loadLanguageFile( array( 'admin_attachments' ) );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=attachments&amp;section=search';
		$this->form_code_js	= $this->html->form_code_js	= 'module=attachments&section=search';

		//-----------------------------------------
		// StRT!
		//-----------------------------------------

		switch( $this->request['do'] )
		{			
			case 'attach_search_complete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'view_attachments' );
				$this->_searchResults();
			break;
			
			case 'attach_bulk_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'remove_attachments' );
				$this->_bulkRemoveAttachments();
				break;
			
			case 'overview':
			case 'search':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'view_attachments' );
				$this->_searchForm();
			break;			
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Bulk remove attachments
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _bulkRemoveAttachments()
	{
		foreach ( $_POST as $key => $value )
		{
			if ( preg_match( "/^attach_(\d+)$/", $key, $match ) )
			{
				if ( $this->request[ $match[0] ] )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids		= IPSLib::cleanIntArray( $ids );
		$attach_tid	= array();

		if ( count( $ids ) )
		{
			//-----------------------------------------
			// Get attach details?
			//-----------------------------------------
			
			$this->DB->build( array(
										'select'	=> 'a.*',
										'from'		=> array( 'attachments' => 'a' ),
										'where'		=> "a.attach_rel_id > 0 AND a.attach_id IN(" . implode( ",", $ids ) . ")",
										'add_join'	=> array(
															array(
																'select'	=> 'p.pid, p.topic_id',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> "p.pid=a.attach_rel_id AND attach_rel_module='post'",
																'type'		=> 'left',
																),
															)
								)		);
			$this->DB->execute();

			while ( $killmeh = $this->DB->fetch() )
			{
				if ( $killmeh['attach_location'] )
				{
					@unlink( $this->settings['upload_dir'] . "/" . $killmeh['attach_location'] );
				}

				if ( $killmeh['attach_thumb_location'] )
				{
					@unlink( $this->settings['upload_dir'] . "/" . $killmeh['attach_thumb_location'] );
				}
				
				$attach_tid[ $killmeh['topic_id'] ] = $killmeh['topic_id'];
			}
			
			$this->DB->delete( 'attachments', "attach_id IN(" . implode( ",", $ids ) . ")" );
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['deleted_attachments'], implode( ",", $ids ) ) );
			
			//-----------------------------------------
			// Recount topic upload marker
			//-----------------------------------------
			
			require_once( IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php' );
			
			$postlib = new classPost( $this->registry );
			
			foreach( $attach_tid as $tid )
			{
				if( $tid )
				{
					$postlib->recountTopicAttachments( $tid );
				}
			}
			
			$this->registry->output->global_message = $this->lang->words['attachments_removed'];
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['noattach_to_remove'];
		}
		
		if ( $this->request['return'] == 'stats' )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=attachments&section=stats' );
		}
		else
		{
			if ( $_POST['url'] )
			{
				foreach( explode( '&', $_POST['url'] ) as $u )
				{
					list ( $k, $v ) = explode( '=', $u );
					
					$this->request[ $k] =  $v ;
				}
			}
			
			$this->_searchResults();
		}
	}
		

	/**
	 * Attachment search results
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _searchResults()
	{
		$show	= intval($this->request['show']);
		$show	= $show > 100 ? 100 : $show;

		//-----------------------------------------
		// Build URL
		//-----------------------------------------
		
		$url			= "";
		$url_components	= array( 'extension', 'filesize', 'filesize_gt', 'days', 'days_gt', 'hits', 'hits_gt', 'filename', 'authorname', 'onlyimage', 'orderby', 'sort', 'show' );
		
		foreach( $url_components as $u )
		{
			$url .= $u . '=' . $this->request[  $u  ] . '&';
		}

		//-----------------------------------------
		// Build Query
		//-----------------------------------------
		
		$queryfinal	= "";
		$query		= array();
		
		if ( $this->request['extension'] )
		{
			$query[] = 'a.attach_ext="' . strtolower( str_replace( ".", "", $this->request['extension'] ) ) . '"';
		}
		
		if ( $this->request['filesize'] )
		{
			$gt = $this->request['filesize_gt'] == 'gt' ? '>=' : '<';
			
			$query[] = "a.attach_filesize {$gt} " . intval( $this->request['filesize']*1024 );
		}
		
		if ( $this->request['days'] )
		{
			$day_break = time() - intval( $this->request['days'] * 86400 );
			
			$gt = $this->request['days_gt'] == 'lt' ? '>=' : '<';
			
			$query[] = "a.attach_date {$gt} {$day_break}";
		}
		
		if ( $this->request['hits'] )
		{
			$gt = $this->request['hits_gt'] == 'gt' ? '>=' : '<';
			
			$query[] = "a.attach_hits {$gt} " . intval($this->request['hits']);
		}
		
		if ( $this->request['filename'] )
		{
			$query[] = 'LOWER(a.attach_file) LIKE "%' . strtolower( $this->request['filename'] ) . '%"';
		}
		
		if ( $this->request['authorname'] )
		{
			$query[] = 'LOWER(p.author_name) LIKE "%' . strtolower( $this->request['authorname'] ) . '%"';
		}
		
		if ( $this->request['onlyimage'] )
		{
			$query[] = 'a.attach_is_image=1';
		}
		
		if ( count($query) )
		{
			$queryfinal = 'AND '. implode( " AND ", $query );
		}
		
		$rows	= array();
		
		$this->DB->build( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "attach_rel_module='post'".$queryfinal,
												 'add_join' => array(
												 					  0 => array( 'select' => 'p.author_id, p.author_name, p.post_date',
												 					  			  'from'   => array( 'posts' => 'p' ),
												 					  			  'where'  => 'p.pid=a.attach_rel_id',
												 					  			  'type'   => 'left' ),
												 					  1 => array( 'select' => 't.tid, t.forum_id, t.title',
												 								  'from'   => array( 'topics' => 't' ),
												 								  'where'  => 'p.topic_id=t.tid',
												 								  'type'   => 'left' ),
												 					  2 => array( 'select' => 'm.members_display_name',
												 					  			  'from'   => array( 'members' => 'm' ),
												 					  			  'where'  => 'm.member_id=a.attach_member_id',
												 					  			  'type'   => 'left' )
												 					 ),
												 'order'	=> "a.attach_" . $this->request['orderby'] . " " . $this->request['sort'],
												 'limit'    => array( 0, $show ) ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			$r['stitle']			= $r['title'] ? "<a href='{$this->settings['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' title='{$r['title']}'>" . IPSText::truncate( $r['title'], 30 ) . "</a>" : $this->lang->words['attach_not_topic'];
			$r['attach_filesize']	= IPSLib::sizeFormat($r['attach_filesize']);
			$r['attach_date']		= ipsRegistry::getClass( 'class_localization')->getDate( $r['attach_date'], 'SHORT', 1 );
			
			$rows[] = $r;
		}

		$this->registry->output->html .= $this->html->attachmentSearchResults( $url, $rows );
	}
	
	/**
	 * Show attachment search form
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _searchForm()
	{
		$gt_array = array( 0 => array( 'gt', $this->lang->words['se_morethan'] ), 1 => array( 'lt', $this->lang->words['se_lessthan'] ) );
		
		//-----------------------------------------
		// FORM
		//-----------------------------------------
		
		$form['extension']		= $this->registry->output->formSimpleInput( 'extension', isset($_POST['extension']) ? $_POST['extension'] : '', 10 );
		$form['filesize_gt']	= $this->registry->output->formDropdown( 'filesize_gt', $gt_array, isset($_POST['filesize_gt']) ? $_POST['filesize_gt'] : '' );
		$form['filesize']		= $this->registry->output->formSimpleInput( 'filesize', isset($_POST['filesize']) ? $_POST['filesize'] : '', 10 );
		$form['days_gt']		= $this->registry->output->formDropdown( 'days_gt', $gt_array, isset($_POST['days_gt']) ? $_POST['days_gt'] : '' );
		$form['days']			= $this->registry->output->formSimpleInput( 'days', isset($_POST['days']) ? $_POST['days'] : '', 10 );
		$form['hits_gt']		= $this->registry->output->formDropdown( 'hits_gt', $gt_array, isset($_POST['hits_gt']) ? $_POST['hits_gt'] : '' );
		$form['hits']			= $this->registry->output->formSimpleInput( 'hits', isset($_POST['hits']) ? $_POST['hits'] : '', 10 );
		$form['filename']		= $this->registry->output->formInput( 'filename', isset($_POST['filename']) ? $_POST['filename'] : '' );
		$form['authorname']		= $this->registry->output->formInput( 'authorname', isset($_POST['authorname']) ? $_POST['authorname'] : '' );
		$form['onlyimage']		= $this->registry->output->formYesNo( 'onlyimage', isset($_POST['onlyimage']) ? $_POST['onlyimage'] : '' );
		$form['orderby']		= $this->registry->output->formDropdown( 'orderby', array( 0 => array( 'date'    , $this->lang->words['se_odate']      ),
																 										             1 => array( 'hits'    , $this->lang->words['se_oviews']     ),
																 										             2 => array( 'filesize', $this->lang->words['se_osize'] ),
																 										             3 => array( 'file'    , $this->lang->words['se_oname'] ),
																 										           ), isset($_POST['orderby']) ? $_POST['orderby'] : '' );
		$form['sort']			= $this->registry->output->formDropdown( 'sort'   , array( 0 => array( 'desc'   , $this->lang->words['se_odsc']  ),
																 													 1 => array( 'asc'    , $this->lang->words['se_oasc']   ),
																 										           ), isset($_POST['sort']) ? $_POST['sort'] : '' );
		$form['show']			= $this->registry->output->formSimpleInput( 'show', isset($_POST['show']) ? $_POST['show'] : 25, 10 );
		

		$this->registry->output->html .= $this->html->attachmentSearchForm( $form );

	}
	
}