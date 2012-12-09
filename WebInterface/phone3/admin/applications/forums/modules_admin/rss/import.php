<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum RSS Import
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Rev: 5066 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_rss_import extends ipsCommand
{
	/**
	 * Use sockets
	 *
	 * @access	private
	 * @var		bool
	 */		
	private $use_sockets	= 1;
	
	/**
	 * Classes yet loaded?
	 *
	 * @access	private
	 * @var		bool
	 */	
	private $classes_loaded	= false;
	
	/**
	 * Items imported so far
	 *
	 * @access	private
	 * @var		integer
	 */	
	private $import_count	= 0;
	
	/**
	 * Validation message(s)
	 *
	 * @access	private
	 * @var		array
	 */	
	private $validate_msg	= array();
	
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
	 * Mod Library, for recounting stats and deleting topics
	 *
	 * @access	private
	 * @var		object
	 */		
	private $func_mod;	
	
	/**
	 * RSS Parser Class
	 *
	 * @access	private
	 * @var		object
	 */		
	private $class_rss;
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object
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
			
		/* Load Mod Class */
		require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
		$this->func_mod = new moderatorLibrary( $registry );
		
		/* URLs */
		$this->form_code	= $this->html->form_code	= 'module=rss&amp;section=import';
		$this->form_code_js	= $this->html->form_code_js	= 'module=rss&section=import';		
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'rssimport_overview':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportOverview();
			break;
				
			case 'rssimport_validate':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportValidate( 1 );
			break;				
			
			case 'rssimport_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportForm( 'add' );
			break;
				
			case 'rssimport_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportForm( 'edit' );
			break;
				
			case 'rssimport_add_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportSave( 'add' );
			break;
				
			case 'rssimport_edit_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportSave( 'edit' );
			break;
				
			case 'rssimport_recache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportRebuildCache(0);
			break;
				
			case 'rssimport_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_remove' );
				$this->rssImportRemoveDialogue();
			break;
				
			case 'rssimport_remove_complete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_remove' );
				$this->rssimportRemoveComplete( 1 );
			break;
				
			case 'rssimport_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_delete' );
				$this->rssImportDelete();
			break;
				
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}

	/**
	 * Delete an RSS Import Stream
	 *
	 * @access	public
	 * @return	void
	 **/
	public function rssImportDelete()
	{
		/* INIT */
		$rss_import_id = intval( $this->request['rss_import_id'] );

		/* Load RSS Stream */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id={$rss_import_id}" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['im_noload'];
			$this->rssImportOverview();
			return;
		}
		
		/* Delete the stream */
		$this->DB->delete( 'rss_import', 'rss_import_id=' . $rss_import_id );
		
		$this->registry->output->global_message = $this->lang->words['im_removed'];
		$this->rssImportOverview();
	}	
	
	/**
	 * Removes imported articles
	 *
	 * @access	public
	 * @param	bool	$return			Whether to return or not
	 * @param	integer	$rss_import_id	RSS import id to remove
	 * @return	mixed
	 **/
	public function rssimportRemoveComplete( $return=0, $rss_import_id=0 )
	{
		/* INIT */
		$rss_import_id = $rss_import_id ? $rss_import_id : intval( $this->request['rss_import_id'] );
		$remove_count  = intval( $this->request['remove_count'] ) ? intval( $this->request['remove_count'] ) : 500;
		$remove_tids   = array();
		
		/* Query the RSS Streams */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id={$rss_import_id}" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['im_noload'];
			$this->rssImportOverview();
			return;
		}
		
		/* Get tids */
		$this->DB->build( array( 
								'select'	=> 'rss_imported_tid',
								'from'		=> 'rss_imported',
								'where'		=> 'rss_imported_impid=' . $rss_import_id,
								'order'		=> 'rss_imported_tid DESC',
								'limit'		=> array( 0, $remove_count ) 
						)	 );												 
		$this->DB->execute();
		
		while( $tee = $this->DB->fetch() )
		{
			$remove_tids[ $tee['rss_imported_tid'] ] = $tee['rss_imported_tid'];
		}
		
		/* Check */
		if ( ! count( $remove_tids ) )
		{
			if ( $return )
			{
				$this->registry->output->global_message = $this->lang->words['im_findtopics'];
				$this->rssImportOverview();
				return;
			}
			else
			{
				return;
			}
		}
		
		/* Delete the topics */
		$this->func_mod->forum['id'] = $rssstream['rss_import_forum_id'];
		$this->func_mod->topicDelete( $remove_tids );
		
		/* Remove from the imported list */
		$this->DB->delete( 'rss_imported', 'rss_imported_tid IN(' . implode( ',', $remove_tids ) . ')' );
		
		$this->registry->output->global_message = intval( count( $remove_tids ) ) . $this->lang->words['im_topicsremoved'];
		$this->rssImportOverview();
	}	
	
	/**
	 * Splash screen for removing imported articles
	 *
	 * @access	public
	 * @return	void
	 **/
	public function rssImportRemoveDialogue()
	{
		/* Check ID */
		$rss_import_id = intval( $this->request['rss_import_id'] );
		
		if ( ! $rss_import_id )
		{
			$this->registry->output->global_message = $this->lang->words['im_noid'];
			$this->rssImportOverview();
			return;
		}
		
		/* Load RSS Stream */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id=$rss_import_id" ) );
		
		if( ! $rssstream['rss_import_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['im_noload'];
			$this->rssImportOverview();
			return;
		}
		
		/* Count the number of imported topics */
		$article_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt', 'from' => 'rss_imported', 'where' => 'rss_imported_impid='.$rss_import_id ) );
		
		if ( $article_count['cnt'] < 1 )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['im_noarticles'], $rssstream['rss_import_title'] );
			$this->rssImportOverview();
			return;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->rssImportRemoveArticlesForm( $rssstream, intval( $article_count['cnt'] ) );
	}	
	
	/**
	 * Saves the add/edit RSS Import form
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 **/
	public function rssImportSave($type='add')
	{
		/* Validate the feed? */
		if( $this->request['rssimport_validate'] AND $this->request['rssimport_validate'] )
		{
			$this->rssImportValidate();
			
			if( count($this->validate_msg) )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['im_valresults'], IPSText::stripslashes( trim( $this->request['rss_import_url'] ) ), implode( "<br />&nbsp;&middot;", $this->validate_msg ) );
				$this->rssImportForm( $type );
				return;
			}
		}
				
		/* Get Form Data */
		$rss_import_id         = intval( $this->request['rss_import_id'] );
		$rss_import_title      = trim( $this->request['rss_import_title'] );
		$rss_import_url        = IPSText::stripslashes( trim( $this->request['rss_import_url'] ) );
		$rss_import_mid        = trim( $this->request['rss_import_mid'] );
		$rss_import_showlink   = IPSText::stripslashes( trim( $this->request['rss_import_showlink'] ) );
		$rss_import_enabled    = intval( $this->request['rss_import_enabled'] );
		$rss_import_forum_id   = intval( $this->request['rss_import_forum_id'] );
		$rss_import_pergo      = intval( $this->request['rss_import_pergo'] );
		$rss_import_time       = intval( $this->request['rss_import_time'] );
		$rss_import_topic_open = intval( $this->request['rss_import_topic_open'] );
		$rss_import_topic_hide = intval( $this->request['rss_import_topic_hide'] );
		$rss_import_inc_pcount = intval( $this->request['rss_import_inc_pcount'] );
		$rss_import_topic_pre  = $this->request['rss_import_topic_pre'];
		$rss_import_charset    = $this->request['rss_import_charset'];
		$rss_import_allow_html = intval( $this->request['rss_import_allow_html'] );
		$rss_import_auth	   = intval( $this->request['rss_import_auth'] );
		$rss_import_auth_user  = trim( $this->request['rss_import_auth_user'] ) ? trim( $this->request['rss_import_auth_user'] ) : $this->lang->words['im_notneeded'];
		$rss_import_auth_pass  = trim( $this->request['rss_import_auth_pass'] ) ? trim( $this->request['rss_import_auth_pass'] ) : $this->lang->words['im_notneeded'];

		$rss_error             = array();
		
		/* Error checking */
		if ( $type == 'edit' )
		{
			if ( ! $rss_import_id )
			{
				$this->registry->output->global_message = $this->lang->words['im_noid'];
				$this->rssImportOverview();
				return;
			}
		}
		
		if ( ! $rss_import_title OR ! $rss_import_url OR ! $rss_import_pergo OR ! $rss_import_forum_id OR ! $rss_import_mid )
		{
			$this->registry->output->global_message = $this->lang->words['im_completeform'];
			$this->rssImportForm( $type );
			return;
		}
		
		/* Load the RSS Class */
		require_once( IPS_KERNEL_PATH . 'classRss.php' );
		$this->class_rss               =  new classRss();
		
		$this->class_rss->use_sockets  =  $this->use_sockets;
		$this->class_rss->rss_max_show =  $rss_import_pergo;
		
		/* Set DOC TYPE */
		$supported_encodings = array( 'utf-8', 'iso-8859-1', 'us-ascii' );
		
		if( in_array( strtolower( IPS_DOC_CHAR_SET ), $supported_encodings ) )
		{
			$this->class_rss->doc_type = IPS_DOC_CHAR_SET;
		}
		else
		{
			$this->class_rss->doc_type = 'UTF-8';
		}
		
		if( strtolower( $rss_import_charset ) != $this->class_rss->doc_type )
		{
			$this->class_rss->convert_charset     = 1;
			$this->class_rss->feed_charset        = $rss_import_charset;
			$this->class_rss->destination_charset = $this->class_rss->doc_type;
		}
		else
		{
			$this->class_rss->convert_charset = 0;
		}
		
		/* Set this import's authentication */				
		$this->class_rss->auth_req  = $rss_import_auth;
		$this->class_rss->auth_user = $rss_import_auth_user;
		$this->class_rss->auth_pass = $rss_import_auth_pass;
				
		/* Test URL */
		$this->class_rss->parseFeedFromUrl( $rss_import_url );
		
		/* Found an error? */
		if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
		{
			$rss_error = array_merge( $rss_error,  $this->class_rss->errors );
		}
		
		/* Found some data? */
		if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
		{
			$rss_error[] = sprintf( $this->lang->words['im_noopen'], $rss_import_url );
		}
		
		if ( is_array( $rss_error ) AND count( $rss_error ) )
		{
			$this->registry->output->global_message = implode( "<br />", $rss_error );
			$this->rssImportForm( $type );
			return;
		}

		/* Member data */
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, name', 'from' => 'members', 'where' => "members_l_display_name='" . strtolower( $rss_import_mid ) . "'" ) );
		
		if ( !isset( $member['member_id'] ) OR !$member['member_id'] )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['im_nomember'], $rss_import_mid );
			$this->rssImportForm( $type );
			return;
		}
		else
		{
			$rss_import_mid = $member['member_id'];
		}

		/* Check to make sure forum ID is valid */
		$this->registry->class_forums->forumsInit();
		
		if ( ! isset( $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ] ) OR !$this->registry->class_forums->forum_by_id[ $rss_import_forum_id ] )
		{
			$this->registry->output->global_message = $this->lang->words['im_noforum'];
			$this->rssImportForm( $type );
			return;
		}
		
		if ( $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['sub_can_post'] != 1 OR $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['redirect_on'] == 1 )
		{
			$this->registry->output->global_message = $this->lang->words['im_noforumperm'];
			$this->rssImportForm( $type );
			return;
		}
		
		/* Build the db array */
		$array = array( 
						'rss_import_title'      => $rss_import_title,
						'rss_import_url'        => $rss_import_url,
						'rss_import_mid'        => $rss_import_mid,
						'rss_import_showlink'   => $rss_import_showlink,
						'rss_import_enabled'    => $rss_import_enabled,
						'rss_import_forum_id'   => $rss_import_forum_id,
						'rss_import_pergo'      => $rss_import_pergo,
						'rss_import_time'       => $rss_import_time < 30 ? 30 : $rss_import_time,
						'rss_import_topic_open' => $rss_import_topic_open,
						'rss_import_topic_hide' => $rss_import_topic_hide,
						'rss_import_inc_pcount' => $rss_import_inc_pcount,
						'rss_import_topic_pre'  => $rss_import_topic_pre,
						'rss_import_charset'    => $rss_import_charset,
						'rss_import_allow_html'	=> $rss_import_allow_html,
						'rss_import_auth'		=> $rss_import_auth,
						'rss_import_auth_user'  => $rss_import_auth_user,
						'rss_import_auth_pass'  => $rss_import_auth_pass,
					 );
		
		/* Add to database */	 
		if ( $type == 'add' )
		{
			$this->DB->insert( 'rss_import', $array );
			$this->registry->output->global_message = $this->lang->words['im_created'];
			$rss_import_id = $this->DB->getInsertId();
		}
		/* Update the database */
		else
		{
			$this->DB->update( 'rss_import', $array, 'rss_import_id='.$rss_import_id );
			$this->registry->output->global_message = $this->lang->words['im_edited'];
		}
		
		/* Build the cache */
		if( $rss_import_enabled )
		{
			$this->rssImportRebuildCache( $rss_import_id, 0 );
		}
		
		/* Bounce */
		$this->rssImportOverview();
	}	
	
	/**
	 * Form for adding/editing RSS Imports
	 *
	 * @access	public
	 * @param	string	$type	Either add or edit
	 * @return	void
	 **/
	public function rssImportForm( $type='add' )
	{
		/* INIT */
		$rss_import_id = $this->request['rss_import_id'] ? intval( $this->request['rss_import_id'] ) : 0;
		
		/* Build form drop downs */
		$this->registry->class_forums->forumsInit();
						
		require_once( IPS_ROOT_PATH . 'applications/forums/sources/classes/forums/admin_forum_functions.php' );
		$aff            = new admin_forum_functions( $this->registry );
		$aff->forumsInit();
		$forum_dropdown = $aff->adForumsForumList( 1 );
		
		/* Add new import */
		if ( $type == 'add' )
		{
			/* Form Bits */
			$formcode = 'rssimport_add_save';
			$title    = $this->lang->words['im_createnew'];
			$button   = $this->lang->words['im_createnew'];
			
			/* Form Data */
			$rssstream = array( 'rss_import_topic_open' => 1, 
							    'rss_import_enabled' 	=> 1, 
							    'rss_import_showlink' 	=> $this->lang->words['im_full'],
							    'rss_import_title'		=> '',
							    'rss_import_url'		=> '',
							    'rss_import_forum_id'	=> 0,
							    'rss_import_mid'		=> '',
							    'rss_import_pergo'		=> 10,
							    'rss_import_time'		=> '200',
							    'rss_import_topic_hide'	=> 0,
							    'rss_import_inc_pcount'	=> 1,
							    'rss_import_topic_pre'	=> '',
							    'rss_import_charset'	=> '',
							    'rss_import_allow_html'	=> 0,
							    'rss_import_auth'		=> NULL,
							    'rss_import_auth_user'	=> NULL,
							    'rss_import_auth_pass'	=> NULL,
							    'rss_import_id'			=> 0 );
		}
		/* Edit Form */
		else
		{
			/* Form Data */
			$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => 'rss_import_id='.$rss_import_id ) );
			
			/* Make sure it's valid */
			if ( ! $rssstream['rss_import_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['im_noid'];
				$this->rssImportOverview();
				return;
			}
			
			/* Get the member name */
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "member_id=" . intval( $rssstream['rss_import_mid'] ) ) );
			
			if ( $member['member_id'] )
			{
				$rssstream['rss_import_mid'] = $member['members_display_name'];
			}
			
			/* Form Bits */
			$formcode = 'rssimport_edit_save';
			$title    = $this->lang->words['im_edit'] . $rssstream['rss_import_title'];
			$button   = $this->lang->words['im_save'];
		}
		
		/* Form Elements */
		$form = array();
		
		$form['rss_import_title']      = $this->registry->output->formInput(        'rss_import_title'       , ( isset($this->request['rss_import_title']) 	 AND $this->request['rss_import_title'] )      ? stripslashes($this->request['rss_import_title'])      : $rssstream['rss_import_title'] );
		$form['rss_import_enabled']    = $this->registry->output->formYesNo(       'rss_import_enabled'     , ( isset($this->request['rss_import_enabled']) 	 AND $this->request['rss_import_enabled'] )    ? $this->request['rss_import_enabled']    : $rssstream['rss_import_enabled'] );
		$form['rss_import_url']        = $this->registry->output->formInput(        'rss_import_url'         , ( isset($this->request['rss_import_url']) 		 AND $this->request['rss_import_url'] )        ? $this->request['rss_import_url']        : $rssstream['rss_import_url'] );
		$form['rss_import_forum_id']   = $this->registry->output->formDropdown(     'rss_import_forum_id'    , $forum_dropdown, ( isset($this->request['rss_import_forum_id']) AND $this->request['rss_import_forum_id'] ) ? $this->request['rss_import_forum_id'] : $rssstream['rss_import_forum_id'] );
		$form['rss_import_mid']        = $this->registry->output->formInput(        'rss_import_mid'         , ( isset($this->request['rss_import_mid']) 		 AND $this->request['rss_import_mid'] )        ? $this->request['rss_import_mid']        : $rssstream['rss_import_mid'] );
		$form['rss_import_pergo']      = $this->registry->output->formSimpleInput( 'rss_import_pergo'       , ( isset($this->request['rss_import_pergo']) 	 AND  $this->request['rss_import_pergo'] )     ? $this->request['rss_import_pergo']      : $rssstream['rss_import_pergo'], 5 );
		$form['rss_import_time']       = $this->registry->output->formSimpleInput( 'rss_import_time'        , ( isset($this->request['rss_import_time']) 		 AND $this->request['rss_import_time'] )       ? $this->request['rss_import_time']       : $rssstream['rss_import_time'], 5 );
		$form['rss_import_showlink']   = $this->registry->output->formInput(        'rss_import_showlink'    , ( isset($this->request['rss_import_showlink']) 	 AND $this->request['rss_import_showlink'] )   ? htmlspecialchars($this->request['rss_import_showlink'])   : htmlspecialchars($rssstream['rss_import_showlink']) );
		$form['rss_import_topic_open'] = $this->registry->output->formYesNo(       'rss_import_topic_open'  , ( isset($this->request['rss_import_topic_open']) AND $this->request['rss_import_topic_open'] ) ? $this->request['rss_import_topic_open'] : $rssstream['rss_import_topic_open'] );
		$form['rss_import_topic_hide'] = $this->registry->output->formYesNo(       'rss_import_topic_hide'  , ( isset($this->request['rss_import_topic_hide']) AND $this->request['rss_import_topic_hide'] ) ? $this->request['rss_import_topic_hide'] : $rssstream['rss_import_topic_hide'] );
		$form['rss_import_inc_pcount'] = $this->registry->output->formYesNo(       'rss_import_inc_pcount'  , ( isset($this->request['rss_import_inc_pcount']) AND $this->request['rss_import_inc_pcount'] ) ? $this->request['rss_import_inc_pcount'] : $rssstream['rss_import_inc_pcount'] );
		$form['rss_import_topic_pre']  = $this->registry->output->formInput(        'rss_import_topic_pre'   , ( isset($this->request['rss_import_topic_pre'])  AND $this->request['rss_import_topic_pre'] )  ? $this->request['rss_import_topic_pre']  : $rssstream['rss_import_topic_pre'] );
		$form['rss_import_charset']    = $this->registry->output->formInput(        'rss_import_charset'     , ( isset($this->request['rss_import_charset']) 	 AND $this->request['rss_import_charset'] )    ? $this->request['rss_import_charset']    : $rssstream['rss_import_charset'] );
		$form['rss_import_allow_html'] = $this->registry->output->formYesNo(       'rss_import_allow_html'  , ( isset($this->request['rss_import_allow_html']) AND $this->request['rss_import_allow_html'] ) ? $this->request['rss_import_allow_html'] : $rssstream['rss_import_allow_html'] );
		$form['rss_import_auth']	   = $this->registry->output->formCheckbox(	 'rss_import_auth'		  ,
																						( isset($this->request['rss_import_auth']) AND $this->request['rss_import_auth'] ) ? $this->request['rss_import_auth'] : $rssstream['rss_import_auth'],
																						'1',
																						"rss_import_auth",
																						'onclick="ACPRss.showAuthBoxes()"'
																				);
																
		$auth_checked = ( isset( $this->request['rss_import_auth'] ) AND $this->request['rss_import_auth'] ) ? $this->request['rss_import_auth'] : $rssstream['rss_import_auth'];
		if( !$auth_checked )
		{
			$form['rss_div_show'] = "style='display:none;'";
		}
		else
		{
			$form['rss_div_show'] = "style='display:;'";
		}
		
		$form['rss_import_auth_user'] = $this->registry->output->formInput( 'rss_import_auth_user', ( isset( $this->request['rss_import_auth_user'] ) AND $this->request['rss_import_auth_user'] ) ? $this->request['rss_import_auth_user'] : $rssstream['rss_import_auth_user'] );
		$form['rss_import_auth_pass'] = $this->registry->output->formInput( 'rss_import_auth_pass', ( isset( $this->request['rss_import_auth_pass'] ) AND $this->request['rss_import_auth_pass'] ) ? $this->request['rss_import_auth_pass'] : $rssstream['rss_import_auth_pass'] );																				
		
		/* Output */
		$this->registry->output->html           .= $this->html->rssImportForm( $form, $title, $formcode, $button, $rssstream );
	}	
	
	/**
	 * Builds a list of current RSS Imports
	 *
	 * @access	public
	 * @return	void
	 **/
	public function rssImportOverview()
	{
		/* INIT */
		$rows    = array();		
		$st		 = intval( $this->request['st'] ) > 0 ? intval( $this->request['st'] ) : 0;
		
		/* Count the number of feeds we ahve */
		$num = $this->DB->buildAndFetch( array( 'select' => 'count(*) as row_count', 'from' => 'rss_import' ) );
		
		/* Generate Pagination */
		$page_links = $this->registry->output->generatePagination( array( 
																			'totalItems'         => $num['row_count'],
																			'itemsPerPage'       => 25,
																			'currentStartValue'  => $st,
																			'baseUrl'            => "{$this->settings['base_url']}{$this->form_code}",
																)	 );

		/* Query the current feeds */
		$this->DB->build( array( 'select' => '*', 'from' => 'rss_import', 'order' => 'rss_import_id ASC', 'limit' => array( $st, 25 ) ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_enabled_img'] = $r['rss_import_enabled'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html            .= $this->html->rssImportOverview( $rows, $page_links );
	}	

	
	/**
	 * Rebuild the RSS Stream cache
	 *
	 * @access	public
	 * @param	mixed	$rss_import_id	ID of the stream to import
	 * @param	bool	$return			Set to true to return true/false
	 * @param	bool	$id_is_array	Set to true if the first paramter is an array of ids
	 * @return	mixed
	 **/
	public function rssImportRebuildCache( $rss_import_id, $return=true, $id_is_array=false )
	{
		/* INIT */
		$errors             = array();
		$affected_forum_ids = array();
		$affected_members   = array();
		$rss_error         	= array();
		$rss_import_ids		= array();
		$items_imported     = 0;
		
		/* Check the ID */
		if ( ! $rss_import_id )
		{
			$rss_import_id = $this->request['rss_import_id'] == 'all' ? 'all' : intval( $this->request['rss_import_id'] );
		}

		/* No ID Found */
		if ( ! $rss_import_id )
		{
			$this->registry->output->global_message = $this->lang->words['im_noid'];
			$this->rssImportOverview();
			return;
		}
		
		/* Create an array of ids */
		if( $id_is_array == 1 )
		{
			$rss_import_ids = explode( ",", $rss_import_id );
		}
		
		/* Load the classes we need */
		if ( ! $this->classes_loaded )
		{
			/* Get the RSS Class */
			if ( ! is_object( $this->class_rss ) )
			{
				require_once( IPS_KERNEL_PATH . 'classRss.php' );
				$this->class_rss               =  new classRss();
				
				$this->class_rss->use_sockets  =  $this->use_sockets;
				$this->class_rss->rss_max_show =  100;
			}

			/* Get the post class */
			require_once(IPSLib::getAppDir('forums') .'/sources/classes/post/classPost.php' );
			$this->post = new classPost( $this->registry );

			/* Load the mod libarry */
			if ( ! $this->func_mod )
			{
				require_once( IPSLib::getAppDir('forums') .'/sources/classes/moderate.php' );
				$this->func_mod           =  new moderatorLibrary( $this->registry );
				
			}
			
			$this->classes_loaded = 1;
		}
		
		/* INIT Forums */
		if ( ! is_array( $this->registry->class_forums->forum_by_id ) OR !count( $this->registry->class_forums->forum_by_id ) )
		{
			$this->registry->class_forums->forumsInit();
		}
		
		/* Query the RSS imports */
		$this->DB->build( array( 'select' => '*', 'from' => 'rss_import' ) );
		$outer = $this->DB->execute();
		
		/* Loop through and build cache */
		while( $row = $this->DB->fetch( $outer ) )
		{
			/* Are we caching this one? */
			if( $rss_import_id == 'all' OR $row['rss_import_id'] == $rss_import_id OR ( $id_is_array == 1 AND in_array( $row['rss_import_id'], $rss_import_ids ) ) )
			{
				/* Skip non-existent forums - bad stuff happens	*/
				if ( !isset($this->registry->class_forums->forum_by_id[ $row['rss_import_forum_id'] ]) )
				{
					continue;
				}
				
				/* Allowing badwords? */
				IPSText::getTextClass( 'bbcode' )->bypass_badwords = $row['rss_import_allow_html'];
				
				/* Set this import's doctype */
				$this->class_rss->doc_type 		= IPS_DOC_CHAR_SET;
				$this->class_rss->feed_charset 	= $row['rss_import_charset'];
				
				if( strtolower( $row['rss_import_charset'] ) != IPS_DOC_CHAR_SET )
				{
					$this->class_rss->convert_charset = 1;
				}
				else
				{
					$this->class_rss->convert_charset = 0;
				}
				
				/* Set this import's authentication */
				$this->class_rss->auth_req 		= $row['rss_import_auth'];
				$this->class_rss->auth_user 	= $row['rss_import_auth_user'];
				$this->class_rss->auth_pass 	= $row['rss_import_auth_pass'];

				/* Clear RSS object's error cache first */
				$this->class_rss->errors 		= array();
				$this->class_rss->rss_items 	= array();
				
				/* Reset the rss count as this is a new feed */
				$this->class_rss->rss_count 	= 0;
				$this->class_rss->rss_max_show 	= $row['rss_import_pergo'];
				
				/* Parse RSS */
				$this->class_rss->parseFeedFromUrl( $row['rss_import_url'] );
				
				/* Check for errors */
				if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
				{
					$rss_error = array_merge( $rss_error,  $this->class_rss->errors );
					continue;
				}
				
				if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
				{
					$rss_error[] = sprintf( $this->lang->words['im_noopen'], $row['rss_import_url'] );
					continue;
				}
				
				/* Update last check time */
				$this->DB->update( 'rss_import', array( 'rss_import_last_import' => time() ), 'rss_import_id='.$row['rss_import_id'] );
				
				/* Apparently so: Parse feeds and check for already imported GUIDs */
				$final_items = array();
				$items       = array();
				$check_guids = array();
				$final_guids = array();
				$count       = 0;
				
				if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
				{
					$rss_error[] = $row['rss_import_url'] . $this->lang->words['im_noimport'];
					continue;
				}
				
				/* Loop through the channels */
				foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
				{
					if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
					{
						/* Loop through the items in this channel */
						foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
						{
							/* Item Data */
							$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];
							$item_data['guid']     = md5( $row['rss_import_id'] . ( $item_data['guid'] ? $item_data['guid']     : preg_replace( "#\s|\r|\n#is", "", $item_data['title'].$item_data['link'].$item_data['description'] ) ) );
							$item_data['unixdate'] = intval($item_data['unixdate'])  ? intval($item_data['unixdate']) : time();

							/*  Convert char set? */
							if ( $row['rss_import_charset'] AND ( strtolower(IPS_DOC_CHAR_SET) != strtolower($row['rss_import_charset']) ) )
							{
								$item_data['title']   = IPSText::convertCharsets( $item_data['title']  , $row['rss_import_charset'], IPS_DOC_CHAR_SET );
								$item_data['content'] = IPSText::convertCharsets( $item_data['content'], $row['rss_import_charset'], IPS_DOC_CHAR_SET );
							}

							/* Dates */
							if ( $item_data['unixdate'] < 1 )
							{
								$item_data['unixdate'] = time();
							}
							else if ( $item_data['unixdate'] > time() )
							{
								$item_data['unixdate'] = time();
							}

							/* Error check */
							if ( ! $item_data['title'] OR ! $item_data['content'] )
							{
							 	$rss_error[] = sprintf( $this->lang->words['im_notitle'], $item_data['title'] );
								continue;
							}

							/* Add to array */
							$items[ $item_data['guid'] ] = $item_data;
							$check_guids[]               = $item_data['guid'];
						}
					}
				}

				/* Check GUIDs */
				if ( ! count( $check_guids ) )
				{
					$rss_error[] = $this->lang->words['im_noitems'];
					continue;
				}
				
				$this->DB->build( array( 'select' => '*', 'from' => 'rss_imported', 'where' => "rss_imported_guid IN ('".implode( "','", $check_guids )."')" ) );
				$this->DB->execute();
				
				while ( $guid = $this->DB->fetch() )
				{
					$final_guids[ $guid['rss_imported_guid'] ] = $guid['rss_imported_guid'];
				}
				
				/* Compare GUIDs */
				$item_count = 0;
				
				foreach( $items as $guid => $data )
				{
					if ( in_array( $guid, $final_guids ) )
					{
						continue;
					}
					else
					{
						$item_count++;
						
						/* Make sure each item has a unique date */
						$final_items[ $data['unixdate'].$item_count ] = $data;
					}
				}

				/* Sort Array */
				krsort( $final_items );
				
				/* Pick off last X */
				$count           = 1;
				$tmp_final_items = $final_items;
				$final_items     = array();
				
				foreach( $tmp_final_items as $date => $data )
				{
					$final_items[ $date ] = $data;
					
					if ( $count >= $row['rss_import_pergo'] )
					{
						break;
					}
						
					$count++;
				}

				/* Anything left? */
				if ( ! count( $final_items ) )
				{
					continue;
				}
				
				/* Figure out MID */
				$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, name, members_display_name, ip_address', 'from' => 'members', 'where' => "member_id={$row['rss_import_mid']}" ) );
				
				if ( ! $member['member_id'] )
				{
					continue;
				}
				
				/* Set member in post class */
				$this->post->setAuthor( $member['member_id'] );
				$this->post->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $row['rss_import_forum_id'] ] );
				
				/* Make 'dem posts */
				$affected_forum_ids[] = $row['rss_import_forum_id'];
				
				foreach( $final_items as $topic_item )
				{
					/* Fix &amp; */
					$topic_item['title'] = str_replace( '&amp;', '&', $topic_item['title'] );
					$topic_item['title'] = trim( IPSText::br2nl( $topic_item['title'] ) );
					$topic_item['title'] = strip_tags( $topic_item['title'] );
					$topic_item['title'] = IPSText::parseCleanValue( $topic_item['title'] );
					
					/* Fix up &amp;reg; */
					$topic_item['title'] = str_replace( '&amp;reg;', '&reg;', $topic_item['title'] );
					
					if( $row['rss_import_topic_pre'] )
					{
						$topic_item['title'] = str_replace( '&nbsp;', ' ', str_replace( '&amp;nbsp;', '&nbsp;', $row['rss_import_topic_pre'] ) ) .' '. $topic_item['title'];
					}
					
					/* Build topic insert array */
					$topic = array(
									'title'            => IPSText::mbsubstr( $topic_item['title'], 0, 250 ),
									'title_seo'		   => IPSText::makeSeoTitle( IPSText::mbsubstr( $topic_item['title'], 0, 250 ) ),
									'description'      => '' ,
									'state'            => $row['rss_import_topic_open'] ? 'open' : 'closed',
									'posts'            => 0,
									'starter_id'       => $member['member_id'],
									'starter_name'     => $member['members_display_name'],
									'start_date'       => $topic_item['unixdate'],
									'last_poster_id'   => $member['member_id'],
									'last_poster_name' => $member['members_display_name'],
									'last_post'        => $topic_item['unixdate'],
									'icon_id'          => 0,
									'author_mode'      => 1,
									'poll_state'       => 0,
									'last_vote'        => 0,
									'views'            => 0,
									'forum_id'         => $row['rss_import_forum_id'],
									'approved'         => $row['rss_import_topic_hide'] ? 0 : 1,
									'pinned'           => 0 );
					
					/* More post class stuff */
					$this->post->setPublished( $row['rss_import_topic_hide'] ? FALSE : TRUE );
					
					/* Sort post content: Convert HTML to BBCode */
					IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html		= intval($row['rss_import_allow_html']);
					IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';

					$this->memberData['_canUseRTE']						= true;
					$_POST['ed-0_wysiwyg_used']							= 1;
					IPSText::getTextClass( 'editor' )->method			= 'rte';

					/* Clean up.. */
					$topic_item['content'] = preg_replace( "#<br />(\r)?\n#is", "<br />", $topic_item['content'] );

					/* Add in Show link... */
					if ( $row['rss_import_showlink'] AND $topic_item['link'] )
					{
						$the_link = str_replace( '{url}', trim($topic_item['link']), $row['rss_import_showlink'] );

						if ( $row['rss_import_allow_html'] )
						{
							$_POST['_tmpPostField']	= IPSText::getTextClass( 'bbcode' )->preEditParse( stripslashes($the_link) );
							
							$the_link = "<br /><br />" . IPSText::getTextClass( 'bbcode' )->preDbParse( IPSText::getTextClass( 'editor' )->processRawPost( '_tmpPostField' ) );
						}
						else
						{
							$the_link = "<br /><br />" . $the_link;
						}
						
						$topic_item['content'] .= $the_link;
					}

					if ( ! $row['rss_import_allow_html'] )
					{
						$_POST['_tmpPostField']	= stripslashes($topic_item['content']);//IPSText::getTextClass( 'bbcode' )->preEditParse( stripslashes($topic_item['content']) );

						$post_content = IPSText::getTextClass( 'bbcode' )->preDbParse( IPSText::getTextClass( 'editor' )->processRawPost( '_tmpPostField' ) );
					}
					else
					{
						$post_content = stripslashes($topic_item['content']);
					}

					/* Build Post insert array */
					$post = array(
									'author_id'      => $member['member_id'],
									'use_sig'        => 1,
									'use_emo'        => 1,
									'ip_address'     => $member['ip_address'],
									'post_date'      => $topic_item['unixdate'],
									'icon_id'        => 0,
									'post'           => $post_content,
									'author_name'    => $member['members_display_name'],
									'topic_id'       => "",
									'queued'         => 0,
									'post_htmlstate' => 0,
								 );
								 
					/* Insert the topic into the database to get the last inserted value of the auto_increment field follow suit with the post */					
					$this->DB->insert( 'topics', $topic );
					
					$post['topic_id']  = $this->DB->getInsertId();
					$topic['tid']      = $post['topic_id'];
					
					/* Update the post info with the upload array info */
					$post['post_key']  = md5( uniqid( microtime() ) );
					$post['new_topic'] = 1;
					
					/* Add post to DB */
					$this->DB->insert( 'posts', $post );
				
					$post['pid'] = $this->DB->getInsertId();
					
					/* Update topic with firstpost ID */
					$this->DB->build( array( 
													'update' => 'topics',
													'set'    => "topic_firstpost=".$post['pid'],
													'where'  => "tid=".$topic['tid']
										)      );
										 
					$this->DB->execute();
										
					/* Insert GUID match */
					$this->DB->insert( 'rss_imported', array( 
																'rss_imported_impid' => $row['rss_import_id'],
																'rss_imported_guid'  => $topic_item['guid'],
																'rss_imported_tid'   => $topic['tid'] 
										)	 );
				
					/* Are we tracking this forum? If so generate some mailies - yay! */
					$this->post->forum = $this->registry->class_forums->forum_by_id[$row['rss_import_forum_id']];

					$this->post->sendOutTrackedForumEmails( $row['rss_import_forum_id'], $topic['tid'], $topic['title'], $this->registry->class_forums->forum_by_id[ $row['rss_import_forum_id'] ]['name'], $post['post'], $member['member_id'], $member['members_display_name'] );
					
					if( $topic['approved'] == 0 )
					{
						$this->post->sendNewTopicForApprovalEmails( $topic['tid'], $topic['title'], $topic['starter_name'], $post['pid'] );
					}
					
					$this->import_count++;
					
					/* Increment user? */
					if ( $row['rss_import_inc_pcount'] AND $this->registry->class_forums->forum_by_id[ $row['rss_import_forum_id'] ]['inc_postcount'] )
					{
						if ( ! $affected_members[ $member['member_id'] ] OR $affected_members[ $member['member_id'] ] < 0 )
						{
							$affected_members[ $member['member_id'] ] = 0;
						}
						
						$affected_members[ $member['member_id'] ]++;
					}
				}
 			}
		}
		
		/* Update Members */
		if ( is_array( $affected_members ) and count( $affected_members ) )
		{
			foreach( $affected_members as $mid => $inc )
			{
				if ( $mid AND $inc )
				{
					$this->post->setAuthor( $mid );
					$this->post->incrementUsersPostCount( $inc );
				}
			}
		}
		
		/* Recount Stats */		
		if ( is_array( $affected_forum_ids ) and count( $affected_forum_ids ) )
		{
			foreach( $affected_forum_ids as $fid )
			{
				$this->func_mod->forumRecount( $fid );
			}
			
			$this->func_mod->statsRecount();
		}
		
		/* Return */
		if ( $return )
		{
			$this->registry->output->global_message = $this->lang->words['im_recached'];
			
			if ( count( $rss_error ) )
			{
				$this->registry->output->global_message .= "<br />".implode( "<br />", $rss_error );
			}
			
			$this->rssImportOverview();
			return;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Validate an RSS Feed
	 *
	 * @access	public
	 * @param	bool	$standalone	If set to true, data will be queried from the db based on rss_id, otherwise data will be gathered from form fields
	 * @return void
	 **/
	public function rssImportValidate( $standalone=false )
	{
		/* INI */
		$return = 0;
		
		if( ! $standalone )
		{
			/* Get data from the form */
			$rss_import_id         = intval( $this->request['rss_import_id'] );
			$rss_import_title      = trim( $this->request['rss_import_title'] );
			$rss_import_url        = IPSText::stripslashes( trim( $this->request['rss_import_url'] ) );
			$rss_import_mid        = trim( $this->request['rss_import_mid'] );
			$rss_import_showlink   = IPSText::stripslashes( trim( $this->request['rss_import_showlink'] ) );
			$rss_import_enabled    = intval( $this->request['rss_import_enabled'] );
			$rss_import_forum_id   = intval( $this->request['rss_import_forum_id'] );
			$rss_import_pergo      = intval( $this->request['rss_import_pergo'] );
			$rss_import_time       = intval( $this->request['rss_import_time'] );
			$rss_import_topic_open = intval( $this->request['rss_import_topic_open'] );
			$rss_import_topic_hide = intval( $this->request['rss_import_topic_hide'] );
			$rss_import_inc_pcount = intval( $this->request['rss_import_inc_pcount'] );
			$rss_import_topic_pre  = $this->request['rss_import_topic_pre'];
			$rss_import_charset    = $this->request['rss_import_charset'];
			$rss_import_allow_html = intval( $this->request['rss_import_allow_html'] );
			$rss_import_auth	   = intval( $this->request['rss_import_auth'] );
			$rss_import_auth_user  = trim( $this->request['rss_import_auth_user'] ) ? trim( $this->request['rss_import_auth_user'] ) : 'Not Needed';
			$rss_import_auth_pass  = trim( $this->request['rss_import_auth_pass'] ) ? trim( $this->request['rss_import_auth_pass'] ) : 'Not Needed';
			
			$return				   = 1;
		}
		else
		{
			$return = 0;
			
			/* Get the RSS ID */
			$rss_input_id = $this->request['rss_id'] ? intval($this->request['rss_id']) : 0;
			
			/* Found an id */
			if( $rss_input_id > 0 )
			{
				/* Query the data from the db */
				$rss_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => 'rss_import_id=' . $rss_input_id ) );
				
				/* Format Data */
				if( ! $rss_data['rss_import_url'] )
				{
					$rss_import_url 		= "";
					$rss_import_auth 		= "";
					$rss_import_auth_user 	= "";
					$rss_import_auth_pass 	= "";
				}
				else
				{
					$standalone = 0;
					
					$rss_import_id         = intval( $rss_data['rss_import_id'] );
					$rss_import_url        = $rss_data['rss_import_url'];
					
					$member = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => 'member_id=' . $rss_data['rss_import_mid'] ) );
					
					$rss_import_mid		   = $member['members_display_name'];
					
					$rss_import_forum_id   = intval( $rss_data['rss_import_forum_id'] );
					$rss_import_inc_pcount = intval( $rss_data['rss_import_inc_pcount'] );
					$rss_import_charset    = $rss_data['rss_import_charset'];
					$rss_import_auth	   = intval( $rss_data['rss_import_auth'] );
					$rss_import_auth_user  = trim( $rss_data['rss_import_auth_user'] );
					$rss_import_auth_pass  = trim( $rss_data['rss_import_auth_pass'] );
				}
			}
			/* Try from URL */
			else
			{
				$rss_import_url 		= IPSText::stripslashes( trim( $this->request['rss_url'] ) );
				$rss_import_charset 	= IPS_DOC_CHAR_SET;
				$rss_import_auth		= "";
				$rss_import_auth_user 	= "";
				$rss_import_auth_pass 	= "";				
			}
		}
		
		/* Check for URL */
		if( ! $rss_import_url )
		{
			$this->validate_msg[] = $this->html->rssValidateMsg( array( 'msg' => $this->lang->words['im_nourl'] ) );
		}
		else
		{
			/* INIT */
			if ( ! $this->classes_loaded )
			{
				/* Load RSS Class */
				if ( ! is_object( $this->class_rss ) )
				{
					require_once( IPS_KERNEL_PATH . 'classRss.php' );
					$this->class_rss               =  new classRss();
					
					$this->class_rss->use_sockets  =  $this->use_sockets;
					$this->class_rss->rss_max_show =  100;
				}
				
				$this->classes_loaded = 1;
			}
			
			/* Set this imports doc type */
			$this->class_rss->doc_type 		= IPS_DOC_CHAR_SET;
			$this->class_rss->feed_charset 	= IPS_DOC_CHAR_SET;
			
			if( strtolower( $rss_import_charset ) != IPS_DOC_CHAR_SET )
			{
				$this->class_rss->convert_charset = 1;
			}
			else
			{
				$this->class_rss->convert_charset = 0;
			}
			
			/* Set this import's authentication */		
			$this->class_rss->auth_req = $rss_import_auth;				
			$this->class_rss->auth_user = $rss_import_auth_user;
			$this->class_rss->auth_pass = $rss_import_auth_pass;
			
			/* Clear RSS object's error cache first */
			$this->class_rss->errors 	= array();
			$this->class_rss->rss_items = array();

			/* Reset the rss count as this is a new feed */
			$this->class_rss->rss_count =  0;
			
			/* Parse RSS */
			$this->class_rss->parseFeedFromUrl( $rss_import_url );
			
			/* Validate Data - HTTP Status Code/Text */
			if( $this->class_rss->classFileManagement->http_status_code != "200" )
			{
				if( $this->class_rss->classFileManagement->http_status_code )
				{
					$this->validate_msg[] =	$this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => "{$this->lang->words['im_http']} {$this->class_rss->classFileManagement->http_status_code} ({$this->class_rss->class_file_management->http_status_text})" ) );
				}
			}
			else
			{
				$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-valid', 
																			  'msg' => "{$this->lang->words['im_http']} {$this->class_rss->classFileManagement->http_status_code} ({$this->class_rss->class_file_management->http_status_text})" ) );
			}
			
			/* Display any errors found */
			if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
			{
				foreach( $this->class_rss->errors as $error )
				{
					$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 'msg' => $error ) );
				}
			}
			else
			{
				if( $this->class_rss->orig_doc_type )
				{
					if( !$standalone AND $rss_import_charset )
					{
						if( strtolower($rss_import_charset) != strtolower($this->class_rss->orig_doc_type) )
						{
							$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																						  'msg' => sprintf( $this->lang->words['im_doc_type'], $this->class_rss->orig_doc_type, $rss_import_charset ) ) );
						}
						else
						{
							$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-valid', 
																						  'msg' => sprintf ( $this->lang->words['im_charset_cor'], $this->class_rss->orig_doc_type ) ) );
						}
					}
					else
					{
						$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-valid', 
																					  'msg' => sprintf( $this->lang->words['im_charset'], $this->class_rss->orig_doc_type ) ) );
					}
				}
				else
				{
					$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => $this->lang->words['im_nocharset'] ) );
				}
				
				/* Channels */
				if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
				{
					$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => $this->lang->words['im_nochannels'] ) );
				}
				else
				{
					$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-valid', 
																				  'msg' => sprintf( $this->lang->words['im_channelcount'], count($this->class_rss->rss_channels) ) ) );
					
					/* Any Items */
					if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
					{
						$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => $this->lang->words['im_nocontent'] ) );
					}
					else
					{
						foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
						{
							if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
							{
								$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-valid', 
																						  	  'msg' => sprintf ( $this->lang->words['im_topiccount'], count($this->class_rss->rss_items[ $channel_id ]) ) ) );
																
								foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
								{
									if( !$item_data['unixdate'] )
									{
										$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-msg', 
																									  'msg' => $this->lang->words['im_nodate'] ) );
									}
									
									if ( $item_data['unixdate'] < 1 )
									{
										$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-msg', 
																									  'msg' => $this->lang->words['im_invdate'] ) );
									}
									else if ( $item_data['unixdate'] > time() )
									{
										$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-msg',
																									  'msg' => $this->lang->words['im_invdate'] ) );
									}	
									
									$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];								
									
									if ( ! $item_data['title'] OR ! $item_data['content'] )
									{
										$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																									  'msg' => $this->lang->words['im_'] ) );
									}
									
									break 2;
								}
							}
						}
					}
				}
			}
			
			if( !$standalone )
			{
				if( $rss_import_mid )
				{
					$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, name', 'from' => 'members', 'where' => "members_l_display_name='{$rss_import_mid}'" ) );
					
					if ( ! $member['member_id'] )
					{
						$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => sprintf( $this->lang->words['im_nomember']. $rss_import_mid ) ) );
					}
				}
				else
				{
					$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => $this->lang->words['im_memval'] ) );
				}					
			}
			
			/* Init forums if not already done so */
			if ( ! is_array( $this->registry->class_forums->forum_by_id ) OR !count( $this->registry->class_forums->forum_by_id ) )
			{
				$this->registry->class_forums->forums_init();
			}			
			
			if( !$standalone AND $rss_import_forum_id )
			{
				if ( ! $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ] )
				{
					$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => $this->lang->words['im_noforum'] ) );
				}
				else
				{
					if ( $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['sub_can_post'] != 1 OR $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['redirect_on'] == 1 )
					{
						$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => $this->lang->words['im_redforum'] ) );
					}
					
					if( $rss_import_inc_pcount AND !$this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['inc_postcount'] )
					{
						$this->validate_msg[] = $this->html->rssValidateMsg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => $this->lang->words['im_noinc'] ) );
					}
				}
			}
			
			/* Display */
			if ( ! $return )
			{
				if( count( $this->validate_msg ) )
				{
					$this->registry->output->global_message = sprintf( $this->lang->words['im_valresults'], IPSText::stripslashes( trim( $this->request['rss_import_url'] ) ), implode( "<br />&nbsp;&middot;", $this->validate_msg ) );
					$this->rssImportOverview();
					return;
				}
			}
			else
			{
				return TRUE;
			}
		}	
	}

}