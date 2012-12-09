<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile Plugin Library
 * Last Updated: $Date: 2009-05-18 22:05:12 -0400 (Mon, 18 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		20th February 2002
 * @version		$Revision: 4668 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profile_topics extends profile_plugin_parent
{
	/**
	 * Attachment object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $attach;
	
	/**
	 * Return HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content		= '';
		$last_x			= 5;
		$forum_ids		= array();

		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! is_array( $member ) OR ! count( $member ) )
		{
			return $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_posts_to_show' );
		}
		
		//-----------------------------------------
		// Some words
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );

		//-----------------------------------------
		// Can view other member's topics?
		//-----------------------------------------
		
		if( !$this->memberData['g_other_topics'] AND $this->memberData['member_id'] != $member['member_id'] )
		{
			return $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_posts_to_show' );
		}
		
		//-----------------------------------------
		// Remove trash can...
		//-----------------------------------------
		$trash_can_remove = '';
		
		if ( $this->settings['forum_trash_can_id'] )
		{
			$trash_can_remove = ' AND i.perm_type_id <> ' . $this->settings['forum_trash_can_id'];
		}
		
		$pids	= array();
		
		//-----------------------------------------
		// And limit by post count...
		//-----------------------------------------
		
		$posts	= intval($this->memberData['posts']);
		
		$trash_can_remove	.= " AND f.min_posts_view <= {$posts}";
		
		//-----------------------------------------
		// And "can view others"
		//-----------------------------------------
		
		$trash_can_remove	.= " AND f.can_view_others=1";
		
		//-----------------------------------------
		// Will we need to parse attachments?
		//-----------------------------------------
		
		$parseAttachments	= false;		
		
		//-----------------------------------------
		// Get last X posts
		//-----------------------------------------

		$this->DB->build( array( 'select'		=> 't.*',
  										'from'		=> array( 'topics' => 't' ),
  										'where'		=> "t.starter_id={$member['member_id']} AND t.approved=1 AND {$this->registry->permissions->buildPermQuery('i')} AND {$this->registry->permissions->buildPermQuery('i', 'perm_2')} {$trash_can_remove} AND (f.password='' OR f.password" . $this->DB->buildIsNull() . ")",
										'order'		=> 't.start_date DESC',
										'limit'		=> array( 0, $last_x ),
										'add_join'	=> array( 
																array(
																		'from'   => array( 'permission_index' => 'i' ),
																		'where'  => "i.perm_type='forum' AND i.perm_type_id=t.forum_id",
																		'type'   => 'left',
																	),
																array(
																		'select' => 'p.*',
																		'from'   => array( 'posts' => 'p' ),
																		'where'  => 't.topic_firstpost=p.pid',
																		'type'   => 'left' 
																	),
																array(
																		'select' => 'f.use_html',
																		'from'   => array( 'forums' => 'f' ),
																		'where'  => 'f.id=t.forum_id',
																		'type'   => 'left' 
																	),
																array(
																		'select'	=> 'm.member_group_id, m.mgroup_others',
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> 'm.member_id=p.author_id',
																		'type'		=> 'left' 
																	),
															)
								) 		) ;
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			//-----------------------------------------
			// Ghost topics... BOO
			//-----------------------------------------
			
			if( !$row['pid'] )
			{
				continue;
			}

			$pids[ $row['pid'] ]	= $row['pid'];
			
			if( $row['topic_hasattach'] )
			{
				$parseAttachments	= true;
			}
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $row['use_html'] and $this->memberData['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

			$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
			$row['post']	= IPSText::getTextClass( 'bbcode' )->memberViewImages( $row['post'] );

			$row['_post_date']  = ipsRegistry::getClass( 'class_localization')->getDate( $row['post_date'], 'SHORT' );
			$row['_date_array'] = IPSTime::date_getgmdate( $row['post_date'] + ipsRegistry::getClass( 'class_localization')->getTimeOffset() );
			
			$row['post'] .= "\n<!--IBF.ATTACHMENT_". $row['pid']. "-->";

			$content .= $this->registry->getClass('output')->getTemplate('profile')->tabSingleColumn( $row, $this->lang->words['profile_read_topic'], $this->settings['base_url'].'app=forums&amp;module=forums&amp;section=findpost&amp;pid='.$row['pid'], $row['title'] );
		}
		
		//-----------------------------------------
		// Attachments (but only if necessary)
		//-----------------------------------------
		
		if ( $parseAttachments AND !is_object( $this->class_attach ) )
		{
			require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
			$this->class_attach           =  new class_attach( $this->registry );

			$this->class_attach->type  = 'post';
			$this->class_attach->init();
			
			if ( IPSMember::checkPermissions('download') === false )
			{
				$this->settings['show_img_upload'] = 0;
			}
			
			$content = $this->class_attach->renderAttachments( $content, $pids );
			$content = $content[0]['html'];
		}

		//-----------------------------------------
		// Macros...
		//-----------------------------------------
		
		$content = $this->registry->output->replaceMacros( $content );
		
		//-----------------------------------------
		// Return content..
		//-----------------------------------------
		
		return $content ? $this->registry->getClass('output')->getTemplate('profile')->tabTopics( $content ) : $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_posts_to_show' );
	}
	
}