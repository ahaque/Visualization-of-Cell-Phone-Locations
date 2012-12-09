<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum RSS Export
 * Last Updated: $Date: 2009-08-20 18:20:40 -0400 (Thu, 20 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Rev: 5035 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_rss_export extends ipsCommand
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
		/* Load HTML and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_rss' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_rss' ) );

		/* URLs */
		$this->form_code	= $this->html->form_code	= 'module=rss&amp;section=export';
		$this->form_code_js	= $this->html->form_code_js	= 'module=rss&section=export';		
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'rssexport_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_manage' );
				$this->rssExportForm( 'add' );
			break;
				
			case 'rssexport_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_manage' );
				$this->rssExportForm( 'edit' );
			break;
				
			case 'rssexport_add_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_manage' );
				$this->rssExportSave( 'add' );
			break;
				
			case 'rssexport_edit_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_manage' );
				$this->rssExportSave( 'edit' );
			break;
				
			case 'rssexport_recache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_manage' );
				$this->rssExportRebuildCache();
			break;
			
			case 'rssexport_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_delete' );
				$this->rssExportDelete();
			break;
			
			case 'rss_export_overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'export_manage' );
				$this->rssExportOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Deletes an RSS Export Streawm
	 *
	 * @access	public
	 * @return	void
	 **/
	public function rssExportDelete()
	{
		/* INIT */
		$rss_export_id = intval($this->request['rss_export_id']);
		
		/* Load Stream Data */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_export', 'where' => "rss_export_id=$rss_export_id" ) );
		
		if ( ! $rssstream['rss_export_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['ex_noload'];
			$this->rssExportOverview();
			return;
		}
		
		/* Delete the stream */
		$this->DB->delete( 'rss_export', 'rss_export_id=' . $rss_export_id );
		
		/* Rebuild cache and bounce */
		$this->rssExportRebuildCache( $rss_export_id, 0 );
		$this->registry->output->global_message = $this->lang->words['ex_removed'];
		$this->rssExportOverview();
	}	
	
	/**
	 * Save the add/edit RSS Export Stream form
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 **/
	public function rssExportSave( $type='add' )
	{
		/* INIT */
		$rss_export_id           = intval( $this->request['rss_export_id'] );
		$rss_export_title        = IPSText::UNhtmlspecialchars( trim( $this->request['rss_export_title'] ) );
		$rss_export_desc         = IPSText::UNhtmlspecialchars( trim( $this->request['rss_export_desc']  ) );
		$rss_export_image        = IPSText::UNhtmlspecialchars( trim( $this->request['rss_export_image'] ) );
		$rss_export_forums       = is_array( $this->request['rss_export_forums'] ) ? implode( ",", $this->request['rss_export_forums'] ) : '';
		$rss_export_include_post = intval( $this->request['rss_export_include_post'] );
		$rss_export_count        = intval( $this->request['rss_export_count'] );
		$rss_export_cache_time   = intval( $this->request['rss_export_cache_time'] );
		$rss_export_enabled      = intval( $this->request['rss_export_enabled'] );
		$rss_export_sort         = trim( $this->request['rss_export_sort'] );
		$rss_export_order        = trim( $this->request['rss_export_order'] );
		
		/* Check for Errors */
		if ( $type == 'edit' )
		{
			if ( ! $rss_export_id )
			{
				$this->registry->output->global_message = $this->lang->words['ex_noid'];
				$this->rssExportOverview();
				return;
			}
		}
		
		if ( ! $rss_export_title OR ! $rss_export_count OR ! $rss_export_forums )
		{
			$this->registry->output->global_message = $this->lang->words['ex_completeform'];
			$this->rssExportForm( $type );
			return;
		}
		
		/* Build Save Array */
		$array = array( 
						'rss_export_enabled'      => $rss_export_enabled,
						'rss_export_title'        => $rss_export_title,
						'rss_export_desc'		  => $rss_export_desc,
						'rss_export_image'        => $rss_export_image,
						'rss_export_forums'       => $rss_export_forums,
						'rss_export_include_post' => $rss_export_include_post,
						'rss_export_count'        => $rss_export_count,
						'rss_export_cache_time'   => $rss_export_cache_time,
						'rss_export_order'        => $rss_export_order,
						'rss_export_sort'         => $rss_export_sort
					 );
		
		/* Insert new record */		 
		if ( $type == 'add' )
		{
			$this->DB->insert( 'rss_export', $array );
			$rss_export_id = 'all';
			$this->registry->output->global_message = $this->lang->words['ex_created'];
		}
		/* Update existing record */
		else
		{
			
			$this->DB->update( 'rss_export', $array, 'rss_export_id='.$rss_export_id );
			$this->registry->output->global_message = $this->lang->words['ex_edited'];
		}
		
		/* Rebuild chace and bounce */
		$this->rssExportRebuildCache( $rss_export_id, 0 );
		$this->rssExportOverview();
	}	
	
	/**
	 * Form for adding/editing an RSS Export Stream
	 *
	 * @access	public
	 * @param	string	$type	add/edit
	 * @return	void
	 **/
	public function rssExportForm( $type='add' )
	{
		/* INIT */
		$rss_export_id = $this->request['rss_export_id'] ? intval( $this->request['rss_export_id'] ) : 0;
		$dd_sort       = array( 0 => array( 'DESC', $this->lang->words['ex_opt_desc'] ), 1 => array( 'ASC', $this->lang->words['ex_opt_asc'] ) );
		$dd_order      = array( 0 => array( 'start_date'        , $this->lang->words['ex_opt_start'] ),
								1 => array( 'last_post'         , $this->lang->words['ex_opt_last'] ),
								2 => array( 'views'             , $this->lang->words['ex_opt_views'] ),
								3 => array( 'starter_id'        , $this->lang->words['ex_opt_starter'] ),
								4 => array( 'topic_rating_total', $this->lang->words['ex_opt_rating'] ) );
		
		/* Check (please?) */
		if ( $type == 'add' )
		{
			/* Form Bits */
			$formcode  = 'rssexport_add_save';
			$title     = $this->lang->words['ex_createnew'];
			$button    = $this->lang->words['ex_createnew'];
			
			/* Form Data */
			$rssstream = array( 'rss_export_id'			=> 0,
								'rss_export_title'		=> '',
								'rss_export_forums'		=> NULL,
								'rss_export_desc'		=> '',
								'rss_export_image'		=> '',
								'rss_export_include_post' => 1,
								'rss_export_enabled'	=> 1,
								'rss_export_count'		=> '',
								'rss_export_cache_time'	=> '',
								'rss_export_sort'		=> '',
								'rss_export_order'		=> '' );
		}
		else
		{
			/* Form Data */
			$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_export', 'where' => 'rss_export_id=' . $rss_export_id ) );
			
			if ( ! $rssstream['rss_export_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['ex_noid'];
				$this->rssExportOverview();
				return;
			}
			
			/* Form bits */
			$formcode = 'rssexport_edit_save';
			$title    = $this->lang->words['ex_edit'] .$rssstream['rss_export_title'];
			$button   = $this->lang->words['ex_save'];
		}
		
		/* Build forum multi select list */						
		require_once( IPS_ROOT_PATH . 'applications/forums/sources/classes/forums/admin_forum_functions.php' );
		$aff               = new admin_forum_functions( $this->registry );		
		$aff->forumsInit();
		$dropdown          = $aff->adForumsForumList(1);
		$rss_export_forums = ( isset( $this->request['rss_export_forums'] ) AND is_array( $this->request['rss_export_forums'] ) ) ? implode( ",", $this->request['rss_export_forums'] ) : $rssstream['rss_export_forums'];
		
		/* Form Elements */
		$form = array();
		
		$form['rss_export_title']        = $this->registry->output->formInput(  'rss_export_title'         , IPSText::htmlspecialchars( ( isset($this->request['rss_export_title']) AND $this->request['rss_export_title'] ) ? $this->request['rss_export_title'] : $rssstream['rss_export_title'] ) );
		$form['rss_export_desc']         = $this->registry->output->formInput(  'rss_export_desc'          , IPSText::htmlspecialchars( ( isset($this->request['rss_export_desc']) AND $this->request['rss_export_desc'] )  ? $this->request['rss_export_desc']  : $rssstream['rss_export_desc']  ) );
		$form['rss_export_image']        = $this->registry->output->formInput(  'rss_export_image'         , IPSText::htmlspecialchars( ( isset($this->request['rss_export_image']) AND $this->request['rss_export_image'] ) ? $this->request['rss_export_image'] : $rssstream['rss_export_image'] ) );
		$form['rss_export_include_post'] = $this->registry->output->formYesNo( 'rss_export_include_post'  , ( isset($this->request['rss_export_include_post']) AND $this->request['rss_export_include_post'] ) ? $this->request['rss_export_include_post'] : $rssstream['rss_export_include_post'] );
		$form['rss_export_enabled']      = $this->registry->output->formYesNo( 'rss_export_enabled'       , ( isset($this->request['rss_export_enabled']) AND $this->request['rss_export_enabled'] ) ? $this->request['rss_export_enabled'] : $rssstream['rss_export_enabled'] );
		$form['rss_export_count']        = $this->registry->output->formSimpleInput( 'rss_export_count'   , ( isset($this->request['rss_export_count']) AND $this->request['rss_export_count'] )   ? $this->request['rss_export_count']   : $rssstream['rss_export_count'], 5 );
		$form['rss_export_forums']       = $this->registry->output->formMultiDropdown(  'rss_export_forums[]', $dropdown, explode( ",", $rss_export_forums ), 7 );
		$form['rss_export_cache_time']   = $this->registry->output->formSimpleInput( 'rss_export_cache_time'   , ( isset($this->request['rss_export_cache_time']) AND $this->request['rss_export_cache_time'] )  ? $this->request['rss_export_cache_time']   : $rssstream['rss_export_cache_time'], 5 );
		$form['rss_export_sort']         = $this->registry->output->formDropdown( 'rss_export_sort' , $dd_sort , ( isset($this->request['rss_export_sort']) AND $this->request['rss_export_sort'] )  ? $this->request['rss_export_sort']  : $rssstream['rss_export_sort'] );
		$form['rss_export_order']        = $this->registry->output->formDropdown( 'rss_export_order', $dd_order, ( isset($this->request['rss_export_order']) AND $this->request['rss_export_order'] ) ? $this->request['rss_export_order'] : $rssstream['rss_export_order'] );
		
		/* Output */
		$this->registry->output->html            .= $this->html->rssExportForm( $form, $title, $formcode, $button, $rssstream );
	}	
	
	/**
	 * Lists Current RSS Exports
	 *
	 * @access	public
	 * @return	void
	 **/
	public function rssExportOverview()
	{
		/* INIT */
		$content = "";
		$rows    = array();		
		$st		 = intval( $this->request['st'] ) > 0 ? intval( $this->request['st'] ) : 0;
		
		/* Count number of feed exports */
		$num = $this->DB->buildAndFetch( array( 'select' => 'count(*) as row_count', 'from' => 'rss_export' ) );
		
		/* Build Pagination */
		$page_links = $this->registry->output->generatePagination( array( 
																			'totalItems'        => $num['row_count'],
																			'itemsPerPage'      => 25,
																			'currentStartValue' => $st,
																			'baseUrl'           => $this->settings['base_url'].$this->form_code,
																)		);		

		/* Query Feeds */
		$this->DB->build( array( 'select' => '*', 'from' => 'rss_export', 'order' => 'rss_export_id ASC', 'limit' => array( $st, 25 ) ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			/* (Alex) Cross */
			$r['_enabled_img'] = $r['rss_export_enabled'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->rssExportOverview( $rows, $page_links );
	}	
	
	/**
	 * Rebuild Export Cache
	 *
	 * @access	public
	 * @param	mixed	$rss_export_id	Which export id to execute
	 * @param	bool	$return			Whether to return afterwards or output to page
	 * @return	mixed
	 **/
	public function rssExportRebuildCache( $rss_export_id='', $return=true )
	{
		/* INIT */
		if( ! $rss_export_id )
		{
			$rss_export_id = $this->request['rss_export_id'] == 'all' ? 'all' : intval( $this->request['rss_export_id'] );
		}
		
		/* Check ID */
		if ( ! $rss_export_id )
		{
			$this->registry->output->global_message = $this->lang->words['ex_noid'];
			$this->rssExportOverview();
			return;
		}
		
		/* Get RSS Clas */
		require_once( IPS_KERNEL_PATH . 'classRss.php' );
		$class_rss              =  new classRss();		
		$class_rss->use_sockets =  $this->use_sockets;
		$class_rss->doc_type    =  IPS_DOC_CHAR_SET;
		
		/* Reset rss_export cache */
		$this->cache->updateCacheWithoutSaving( 'rss_export', array() );
				
		/* Go loopy */
		$this->DB->build( array( 'select' => '*', 'from' => 'rss_export' ) );
		$outer = $this->DB->execute();
		
		$cache = array();		
		while( $row = $this->DB->fetch( $outer ) )
		{
			/* Update RSS Cache */
			if ( $row['rss_export_enabled'] )
			{
				$cache[] = array( 'url' => $this->settings['board_url'] . '/index.php?act=rssout&amp;id=' . $row['rss_export_id'], 'title' => $row['rss_export_title'] );
			}
			
			/* Add to cache? */
			if( $rss_export_id == 'all' OR $row['rss_export_id'] == $rss_export_id )
			{
				/* Build DB Query */
				if ( $row['rss_export_include_post'] )
				{
					$this->DB->build( array( 
													'select' => 't.*',
													'from'   => array( 'topics' => 't' ),
													'where'  => "t.forum_id IN( " . $row['rss_export_forums'] . " ) AND t.state != 'link' AND t.approved=1",
													'order'  => 't.' . $row['rss_export_order'] . ' ' . $row['rss_export_sort'],
													'limit'  => array( 0, $row['rss_export_count'] ),
													'add_join' => array( array( 
																				'select' => 'p.pid, p.post, p.use_emo, p.post_htmlstate',
																				'from'   => array( 'posts' => 'p' ),
																				'where'  => 't.topic_firstpost=p.pid',
																				'type'   => 'left'
																	)		)
										)	);
				}
				else
				{
					$this->DB->build( array( 
													'select' => '*',
													'from'   => 'topics',
													'where'  => "forum_id IN( ".$row['rss_export_forums']." ) AND state != 'link' AND approved=1",
													'order'  => $row['rss_export_order'].' '. $row['rss_export_sort'],
													'limit'  => array( 0, $row['rss_export_count'] )
										)		);
				}
				
				/* Exec Query */
				$inner = $this->DB->execute();
				
				/* Set var.  Doing this so we can set pubDate to start date or last post date appropriately... */
				$channelCreated	= false;

				/* Loop through topics and display */
				while( $topic = $this->DB->fetch( $inner ) )
				{
					//-----------------------------------------
					// Create channel if not already crated
					//-----------------------------------------
					
					if( !$channelCreated )
					{
						/* Create Channel */
						$channel_id = $class_rss->createNewChannel( array(
																				'title'       => $row['rss_export_title'],
																				'description' => $row['rss_export_desc'],
																				'link'        => $this->settings['board_url'].'/index.php',
																				'pubDate'     => $class_rss->formatDate( $row['rss_export_order'] == 'start_date' ? $topic['start_date'] : $topic['last_post'] ),
																				'ttl'         => $row['rss_export_cache_time']
																	)		);
		
						if( $row['rss_export_image'] )
						{
							$class_rss->addImageToChannel( $channel_id, array( 
																				'title' => $row['rss_export_title'],
																				'url' => $row['rss_export_image'],
																				'link'=> $this->settings['board_url'].'/index.php' 
														)		);
						}
						
						$channelCreated	= true;
					}
					
					//-----------------------------------------
					// Parse the post
					//-----------------------------------------
					$this->settings['__noTruncateUrl'] = 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies	= $topic['use_emo'];
					IPSText::getTextClass( 'bbcode' )->parse_html		= ( $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ]['use_html'] and $topic['post_htmlstate'] ) ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br		= $topic['post_htmlstate'] == 2 ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';
					$topic['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $topic['post'] );

					if( $row['rss_export_include_post'] AND $topic['topic_hasattach'] )
					{
						if ( ! is_object( $_attachments ) )
						{
							//-----------------------------------------
							// Grab render attach class
							//-----------------------------------------
			
							require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
							$_attachments = new class_attach( $this->registry );
						}
						
						$_attachments->type  = 'post';
						$_attachments->init();
						
						# attach_pids is generated in the func_topic_xxxxx files
						$attachHTML = $_attachments->renderAttachments( array( $topic['pid'] => $topic['post'] ), array( $topic['pid'] => $topic['pid'] ) );
						
						/* Now parse back in the rendered posts */
						if( is_array($attachHTML) AND count($attachHTML) )
						{
							foreach( $attachHTML as $id => $data )
							{
								/* Get rid of any lingering attachment tags */
								if ( stristr( $data['html'], "[attachment=" ) )
								{
									$data['html'] = IPSText::stripAttachTag( $data['html'] );
								}
								
								$topic['post']	= $data['html'];
								$topic['post']	.= $data['attachmentHtml'];
							}
						}
					}

					/* Parse */
					//$topic['post'] = preg_replace( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", "<a href='{$this->settings['board_url']}/index.php?app=core&module=attach&section=attach&attach_rel_module=post&attach_id=\\1'>".$this->settings['board_url']."/index.php?app=forums&module=forums&section=attach&type=post&attach_id=\\1</a>", $topic['post'] );

					/* Fix up relative URLs */
					$topic['post'] = preg_replace( "#([^/])style_images/(<\#IMG_DIR\#>)#is", "\\1" . $this->settings['board_url'] . "/style_images/\\2" , $topic['post'] );
					$topic['post'] = preg_replace( "#([\"'])style_emoticons/#is"			, "\\1" . $this->settings['board_url'] . "/style_emoticons/", $topic['post'] );
					
					$topic['post'] = $this->registry->output->replaceMacros( $topic['post'] );
						
					$topic['last_poster_name']	= $topic['last_poster_name']	? $topic['last_poster_name']	: 'Guest';
					$topic['starter_name']		= $topic['starter_name']		? $topic['starter_name']		: 'Guest';
					
					/* Add item */
					$class_rss->addItemToChannel( $channel_id, array(
																		'title'           	=> $topic['title'],
																		'link'            	=> $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'], 'public', $topic['title_seo'], 'showtopic' ),
																		'description'     	=> $topic['post'],
																		'pubDate'	       	=> $class_rss->formatDate( $row['rss_export_order'] == 'last_post' ? $topic['last_post'] : $topic['start_date'] ),
																		'guid'            	=> $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'], 'public', $topic['title_seo'], 'showtopic' )
											  )		);
				}
				
				/* Build document				 */
				$class_rss->createRssDocument();
				
				/* Update the cache */
				$this->DB->update( 'rss_export', array( 'rss_export_cache_last' => time(), 'rss_export_cache_content' => $class_rss->rss_document ), 'rss_export_id='.$row['rss_export_id'] );
 			}
		}
		
		/* Update cache */
		$this->cache->setCache( 'rss_export', $cache, array( 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		$this->cache->rebuildCache( 'rss_output_cache' );
		
		/* Return */
		if ( $return )
		{
			$this->registry->output->global_message = $this->lang->words['ex_recached'];
			$this->rssExportOverview();
			return;
		}
		else
		{
			return $class_rss->rss_document;
		}
	}
}