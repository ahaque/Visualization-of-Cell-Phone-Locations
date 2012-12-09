<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Attachments: Stats
 * Last Updated: $LastChangedDate: 2009-02-16 16:11:13 -0500 (Mon, 16 Feb 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		Mon 24th May 2004
 * @version		$Rev: 4004 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_attachments_stats extends ipsCommand
{
	/**
	 * Image directory
	 *
	 * @access	private
	 * @var		string
	 */
	private $image_dir = '';
	
	/**
	 * HTML  object
	 *
	 * @access	private
	 * @var		object
	 */
	private $html;
	
	/**
	 * Main execution point
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_attachments' );
		$this->html->form_code    = 'module=attachments&amp;section=stats&amp;';
		$this->html->form_code_js = 'module=attachments&amp;section=stats&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_attachments' ) );
		
		/* Image Set */
		$this->image_dir = $this->registry->output->skin['set_image_dir'];
		
		//-----------------------------------------
		// StRT!
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'overview':
			case 'stats':
			default:		
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'stats_attachments' );	
				$this->attachmentStatsOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Shows attachment statistics
	 *
	 * @access	public
	 * @return	void
	 **/
	public function attachmentStatsOverview()
	{
		/* Get attachment Types */
		$cache['attachtypes'] = array();
			
		$this->DB->build( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache['attachtypes'][ $r['atype_extension'] ] = $r;
		}
		
		$stats = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count, sum(attach_filesize) as sum', 'from' => 'attachments' ) );		

		/* Overall Stats */
		$overall_stats = array( 'total_attachments' => $this->registry->class_localization->formatNumber( $stats['count'] ), 'total_size' => IPSLib::sizeFormat( $stats['sum'] ) );
		
		/* Last 5 Attachments */		
		$this->DB->build( array( 
										'select'   => 'a.*',
										'from'     => array( 'attachments' => 'a' ),
										//'where'    => "attach_rel_module='post'",
										'order'    => "a.attach_date DESC",
										'limit'    => array( 0, 5 ),
										'add_join' => array(
															array( 
																	'select' => 'p.author_id, p.author_name, p.post_date',
												 					'from'   => array( 'posts' => 'p' ),
												 					'where'  => 'p.pid=a.attach_rel_id',
												 					'type'   => 'left' 
																),
												 			array( 
																	'select' => 't.tid, t.forum_id, t.title',
																	'from'   => array( 'topics' => 't' ),
																	'where'  => 'p.topic_id=t.tid',
																	'type'   => 'left' 
																),
												 			array( 
																	'select' => 'm.members_display_name',
																	'from'   => array( 'members' => 'm' ),
																	'where'  => 'm.member_id=a.attach_member_id',
																	'type'   => 'left' 
																)
														)
							)	);
												 
		$this->DB->execute();
		
		/* Loop through the last 5 */
		$last_5 = array();
	
		while ( $r = $this->DB->fetch() )
		{
			/* Format Fields */
			$r['stitle']			= $r['title'] ? "<a href='{$this->settings['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' title='{$r['title']}'>" . IPSText::truncate( $r['title'], 30 ) . "</a>" : $this->lang->words['attach_not_topic'];
			$r['_icon']				= $this->settings['mime_img'] . '/' . $cache['attachtypes'][ $r['attach_ext'] ]['atype_img'];
			$r['attach_filesize']	= IPSLib::sizeFormat( $r['attach_filesize'] );
			$r['post_date']			= $this->registry->class_localization->getDate( $r['attach_date'], 'SHORT', 1 );
																		
			/* Add to output array */
			$last_5[] = $r;
		}
		
		/* Largest 5 Attachments */		
		$this->DB->build( array( 
										'select'   => 'a.*',
										'from'     => array( 'attachments' => 'a' ),
										//'where'    => "attach_rel_module='post'",
										'order'    => "a.attach_filesize DESC",
										'limit'    => array( 0, 5 ),
										'add_join' => array(
															array( 
																	'select' => 'p.author_id, p.author_name, p.post_date',
												 					'from'   => array( 'posts' => 'p' ),
												 					'where'  => 'p.pid=a.attach_rel_id',
												 					'type'   => 'left' 
																),
												 			array( 
																	'select' => 't.tid, t.forum_id, t.title',
																	'from'   => array( 'topics' => 't' ),
																	'where'  => 'p.topic_id=t.tid',
																	'type'   => 'left' 
																),
												 			array( 
																	'select' => 'm.members_display_name',
																	'from'   => array( 'members' => 'm' ),
																	'where'  => 'm.member_id=a.attach_member_id',
																	'type'   => 'left' 
																)
														)
							)	);
												 
		$this->DB->execute();
		
		/* Loop through the last 5 */
		$largest_5 = array();
	
		while( $r = $this->DB->fetch() )
		{
			/* Format Fields */
			$r['stitle']			= $r['title'] ? "<a href='{$this->settings['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' title='{$r['title']}'>" . IPSText::truncate( $r['title'], 30 ) . "</a>" : $this->lang->words['attach_not_topic'];
			$r['_icon']				= $this->settings['mime_img'] . '/' . $cache['attachtypes'][ $r['attach_ext'] ]['atype_img'];
			$r['attach_filesize']	= IPSLib::sizeFormat( $r['attach_filesize'] );
			$r['post_date']			= $this->registry->class_localization->getDate( $r['attach_date'], 'SHORT', 1 );
																		
			/* Add to output array */
			$largest_5[] = $r;
		}
		
		/* 5 Most Viewed Downloads */		
		$this->DB->build( array( 
										'select'   => 'a.*',
										'from'     => array( 'attachments' => 'a' ),
										//'where'    => "attach_rel_module='post'",
										'order'    => "a.attach_hits DESC",
										'limit'    => array( 0, 5 ),
										'add_join' => array(
															array( 
																	'select' => 'p.author_id, p.author_name, p.post_date',
												 					'from'   => array( 'posts' => 'p' ),
												 					'where'  => 'p.pid=a.attach_rel_id',
												 					'type'   => 'left' 
																),
												 			array( 
																	'select' => 't.tid, t.forum_id, t.title',
																	'from'   => array( 'topics' => 't' ),
																	'where'  => 'p.topic_id=t.tid',
																	'type'   => 'left' 
																),
												 			array( 
																	'select' => 'm.members_display_name',
																	'from'   => array( 'members' => 'm' ),
																	'where'  => 'm.member_id=a.attach_member_id',
																	'type'   => 'left' 
																)
														)
							)	);
												 
		$this->DB->execute();
		
		/* Loop through the last 5 */
		$most_viewed_5 = array();
	
		while( $r = $this->DB->fetch() )
		{
			/* Format Fields */
			$r['stitle']			= $r['title'] ? "<a href='{$this->settings['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' title='{$r['title']}'>" . IPSText::truncate( $r['title'], 30 ) . "</a>" : $this->lang->words['attach_not_topic'];
			$r['_icon']				= $this->settings['mime_img'] . '/' . $cache['attachtypes'][ $r['attach_ext'] ]['atype_img'];
			$r['attach_filesize']	= IPSLib::sizeFormat( $r['attach_filesize'] );
			$r['post_date']			= $this->registry->class_localization->getDate( $r['attach_date'], 'SHORT', 1 );
																		
			/* Add to output array */
			$most_viewed_5[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->attachmentStats( $overall_stats, $last_5, $largest_5, $most_viewed_5 );
	}
}