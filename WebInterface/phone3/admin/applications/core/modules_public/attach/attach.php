<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Attachment Poster
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage  Core
 * @link		http://www.
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_attach_attach extends ipsCommand
{
	/**
	 * Attachment Library
	 *
	 * @access	public
	 * @var		object	class_attach
	 */
	public $class_attach;
	
	/**
	 * AJAX Library
	 *
	 * @access	public
	 * @var		object	classAjax
	 */
	public $ajax;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* AJAX Class */
		require_once( IPS_KERNEL_PATH . '/classAjax.php' );
		$this->ajax = new classAjax( $registry );
		
		/* Attachment Class */
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$this->class_attach = new class_attach( $registry );
				
		/* What to do... */
		switch( $this->request['do'] )
		{	
			case 'attach_upload_show':
				$this->ajax->returnHtml( $this->attachmentUploadShow() );
			break;
			
			case 'attach_upload_process':
				$this->attachmentUploadProcess();
			break;
			
			case 'attach_upload_remove':
				$this->attachmentUploadRemove();
			break;
			
			/* IFrame based  upload */
			case 'attachiFrame':
				$this->attachiFrame();
			break;
			case 'attachUploadiFrame':
				$this->attachUploadiFrame();
			break;
			
			default:
				$this->showPostAttachment();
			break;
		}
	}
	
	/**
	 * View Post Attachment
	 *
	 * @access	public
	 * @return	void
	 */
	public function showPostAttachment()
	{
		/* INIT */
		$attach_id = intval( $this->request['attach_id'] );
		
		/* INIT module */
		$this->class_attach->init();
		
		/* Display */
		$this->class_attach->showAttachment( $attach_id );
	}
	
	/**
	 * Remove an upload
	 *
	 * @access	public
	 * @return	void
	 */
	public function attachmentUploadRemove()
	{
		/* INIT */
		$attach_post_key      = trim( IPSText::alphanumericalClean( $this->request['attach_post_key'] ) );
		$attach_rel_module    = trim( IPSText::alphanumericalClean( $this->request['attach_rel_module'] ) );
		$attach_rel_id        = intval( $this->request['attach_rel_id'] );
		$attach_id            = intval( $this->request['attach_id'] );
			
		/* Setup Module */
		$this->class_attach->type            = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->attach_rel_id   = $attach_rel_id;
		$this->class_attach->attach_id       = $attach_id;
		$this->class_attach->init();
		
		/* Remove the attachment */
		$removed = $this->class_attach->removeAttachment();
		
		/* Show the form */
		if( $removed )
		{
			$this->ajax->returnHtml( $this->attachmentUploadShow( 'attach_removed', 0 ) );
		}
		else
		{
			$this->ajax->returnHtml( $this->attachmentUploadShow( 'remove_failed', 1 ) );
		}
	}
	
	/**
	 * Perform the actual upload
	 *
	 * @access	public
	 * @return	void
	 */
	public function attachmentUploadProcess()
	{
		/* INIT */
		$attach_post_key      = trim( IPSText::alphanumericalClean( $this->request['attach_post_key'] ) );
		$attach_rel_module    = trim( IPSText::alphanumericalClean( $this->request['attach_rel_module'] ) );
		$attach_rel_id        = intval( $this->request['attach_rel_id'] );
		$attach_current_items = '';
		
		/* INIT module */
		$this->class_attach->type            = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->attach_rel_id   = $attach_rel_id;
		$this->class_attach->init();
		
		/* Process upload */
		$insert_id = $this->class_attach->processUpload();

		/* Got an error? */
		if( $this->class_attach->error )
		{
			$this->ajax->returnHtml( $this->attachmentUploadShow( $this->class_attach->error, 1, $insert_id ) );
		}
		else
		{
			$this->ajax->returnHtml( $this->attachmentUploadShow( 'upload_ok', 0, $insert_id ) );
		}
	}
	
	/**
	 * Show the attach upload field
	 *
	 * @access	public
	 * @param	string	$msg
	 * @param	bool	$is_error
	 * @param	integer	$insert_id
	 * @return	void
	 **/
	public function attachmentUploadShow( $msg="ready", $is_error=0, $insert_id=0 )
	{
		/* INIT JSON */
		$JSON             = array();
		$JSON['msg']      = $msg;
		$JSON['is_error'] = $is_error;
		
		$is_reset = 0;
		
		/* Check the upload domain setting */
		if( $this->settings['upload_domain'] )
		{
			$is_reset = 1;
			$original = $this->settings['base_url'];
			
			if( $this->member->session_type == 'cookie' )
			{
				$this->settings['base_url'] = $this->settings['upload_domain'] . '/index.' . $this->settings['php_ext'].'?';
			}
			else
			{
				$this->settings['base_url'] = $this->settings['upload_domain'] . '/index.' . $this->settings['php_ext'].'?s='.$this->member->session_id .'&amp;';
			}
		}

		/* JSON Data */
		$JSON['attach_post_key']	= $attach_post_key 		= trim( IPSText::alphanumericalClean( $this->request['attach_post_key'] ) );
		$JSON['attach_rel_module']	= $attach_rel_module 	= trim( IPSText::alphanumericalClean( $this->request['attach_rel_module'] ) );
		$JSON['attach_rel_id']		= $attach_rel_id 		= intval( $this->request['attach_rel_id'] );
		
		if( $insert_id )
		{
			$JSON['insert_id'] = $insert_id;
		}
		
		/* Get extra form fields */
		foreach( $_GET as $k => $v )
		{
			if( preg_match( "#^--ff--#", $k ) )
			{
				$JSON['extra_upload_form_url'] .= '&amp;' . str_replace( '--ff--', '', $k ) . '='.$v;
				$JSON['extra_upload_form_url'] .= '&amp;' . $k . '='.$v;
			}
		}
					
		/* INIT module */
		$this->class_attach->type            = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->init();
		$this->class_attach->getUploadFormSettings();
		
		/* Load Language Bits */
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'lang_post' ) );
		
		/* Generate current items... */
		$_more = ( $attach_rel_id ) ? ' OR c.attach_rel_id=' . $attach_rel_id : '';
	
		$this->DB->build( array( 
										'select'   => 'c.*',
										'from'     => array( 'attachments' => 'c' ),
										'where'    => "c.attach_rel_module='{$attach_rel_module}' AND c.attach_post_key='{$attach_post_key}'{$_more}",
										'add_join' => array( array(
																	'select' => 't.*',
																	'from'   => array( 'attachments_type' => 't' ),
																	'where'  => 't.atype_extension=c.attach_ext',
																	'type'   => 'left' 
															) 	)
											
								)	);
									
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			if ( $attach_rel_module != $row['attach_rel_module'] )
			{
				continue;
			}
			
			if( ( $insert_id && $row['attach_id'] == $insert_id ) || $this->request['fetch_all'] )
			{
				$JSON['current_items'][ $row['attach_id'] ] = array(	$row['attach_id']  ,
											 	 						$row['attach_file'],
																		$row['attach_filesize'],
																		$row['attach_is_image'],
																		$row['attach_thumb_location'],
																		$row['attach_thumb_width'],
																		$row['attach_thumb_height'],
																	 	$row['atype_img']
																	);
			}
		}
				
		$JSON['attach_stats'] = $this->class_attach->attach_stats;
		
		IPSDebug::addLogMessage( json_encode( $JSON ), 'uploads' );
		
		/* Return JSON */
		return json_encode( $JSON );
	}
	
	/**
	 * Show the attach upload field
	 *
	 * @access	public
	 * @param	string	$msg
	 * @param	bool	$is_error
	 * @param	integer	$insert_id
	 * @return	void
	 */
	public function attachiFrame( $msg="ready", $is_error=0, $insert_id=0 )
	{
		/* INIT JSON */
		$JSON = $this->attachmentUploadShow( $msg, $is_error, $insert_id );
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_post' ), 'forums' );
		
		$this->ajax->returnHtml( $this->registry->output->getTemplate( 'post' )->attachiFrame( $JSON, intval( $this->request['attach_rel_id'] ) ) );
	}
	
	/**
	 * Perform the actual upload
	 *
	 * @access	public
	 * @return	void
	 */
	public function attachUploadiFrame()
	{
		/* INIT */
		$attach_post_key      = trim( IPSText::alphanumericalClean( $this->request['attach_post_key'] ) );
		$attach_rel_module    = trim( IPSText::alphanumericalClean( $this->request['attach_rel_module'] ) );
		$attach_rel_id        = intval( $this->request['attach_rel_id'] );
		$attach_current_items = '';
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_post' ), 'forums' );
		
		/* INIT module */
		$this->class_attach->type            = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->attach_rel_id   = $attach_rel_id;
		$this->class_attach->init();
		
		/* Process upload */
		$insert_id = $this->class_attach->processUpload();

		/* Got an error? */
		if( $this->class_attach->error )
		{
			$JSON = $this->attachmentUploadShow( $this->class_attach->error, 1, $insert_id );
		}
		else
		{
			$JSON = $this->attachmentUploadShow( 'upload_ok', 0, $insert_id );
		}

		$this->ajax->returnHtml( $this->registry->output->getTemplate( 'post' )->attachiFrame( $JSON, $attach_rel_id ) );
	}
}