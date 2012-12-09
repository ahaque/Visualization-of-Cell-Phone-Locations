<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Moderator actions
 * Last Updated: $Date: 2009-07-21 19:59:38 -0400 (Tue, 21 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Revision: 4924 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_moderate_multimod extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;
	
	/**
	 * Moderator function library
	 *
	 * @access	private
	 * @var		object
	 */
	private $modLibrary;

	/**
	 * Moderator information
	 *
	 * @access	private
	 * @var		array		Array of moderator details
	 */
	private $moderator		= array();

	/**
	 * Forum information
	 *
	 * @access	private
	 * @var		array		Array of forum details
	 */
	private $forum			= array();

	/**
	 * Topic information
	 *
	 * @access	private
	 * @var		array		Array of topic details
	 */
	private $topic			= array();
	
	/**
	 * Topic id
	 *
	 * @access	private
	 * @var		integer		Topic id
	 */
	private $topic_id		= 0;

	/**
	 * Forum id
	 *
	 * @access	private
	 * @var		integer		Forum id
	 */
	private $forum_id		= 0;

	/**
	 * Multimod id
	 *
	 * @access	private
	 * @var		integer		Multimod id
	 */
	private $mm_id			= 0;

	/**
	 * Multimod data
	 *
	 * @access	private
	 * @var		array 		Multimod data
	 */
	private $mm_data		= array();
	
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
		// Load modules...
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ) );
		
		require( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php');
		$this->modLibrary = new moderatorLibrary( $this->registry );

		//-----------------------------------------
		// Clean the incoming
		//-----------------------------------------
		
		$this->request[ 't'] =  intval($this->request['t'] );
		$this->mm_id	= intval($this->request['mm_id']);
		
		if ($this->request['t'] < 0 )
		{
			$this->registry->output->showError( 'multimod_no_topic', 103121 );
		}
		
		//-----------------------------------------
		// Get the topic id / forum id
		//-----------------------------------------
		
		$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=" . intval($this->request['t']) ) );
		$this->forum = $this->registry->class_forums->allForums[ $this->topic['forum_id'] ];
		
		//-----------------------------------------
		// Error out if we can not find the topic
		//-----------------------------------------
		
		if ( !$this->topic['tid'])
		{
			$this->registry->output->showError( 'multimod_no_topic', 103122 );
		}
			
		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------
		
		if ( !$this->forum['id'] )
		{
			$this->registry->output->showError( 'multimod_no_topic', 103123 );
		}

		//-----------------------------------------
		// Are we a moderator?
		//-----------------------------------------
		
		if ( ($this->memberData['member_id']) and ($this->memberData['g_is_supmod'] != 1) )
		{
			$this->moderator = $this->DB->buildAndFetch( array( 'select'	=> '*',
																		'from'	=> 'moderators',
																		'where'	=> "forum_id LIKE '%,{$this->forum['id']},%' AND (member_id=" . $this->memberData['member_id'] . " OR (is_group=1 AND group_id='" . $this->memberData['member_group_id'] . "'))" 
															)		);
		}
		
		//-----------------------------------------
		// Init modfunc module
		//-----------------------------------------
		
		$this->modLibrary->init( $this->forum, $this->topic, $this->moderator );
		
		//-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		if ( $this->modLibrary->mmAuthorize() != TRUE )
		{
			$this->registry->output->showError( 'multimod_no_perms', 2038 );
		}
		
		//-----------------------------------------
		// Get MM data
		//-----------------------------------------
		
		$this->mm_data = $this->caches['multimod'][ $this->mm_id ];
		
		if ( ! $this->mm_data['mm_id'] )
		{
			$this->registry->output->showError( 'multimod_not_found', 103124 );
		}
		
		//-----------------------------------------
		// Does this forum have this mm_id
		//-----------------------------------------
		
		if ( $this->modLibrary->mmCheckIdInForum( $this->forum['id'], $this->mm_data ) != TRUE )
		{
			$this->registry->output->showError( 'multimod_no_perms', 2039 );
		}

		$this->modLibrary->stmInit();
		
		//-----------------------------------------
		// Open close?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_state'] != 'leave' )
		{
			if ( $this->mm_data['topic_state'] == 'close' )
			{
				$this->modLibrary->stmAddClose();
			}
			else if ( $this->mm_data['topic_state'] == 'open' )
			{
				$this->modLibrary->stmAddOpen();
			}
		}
		
		//-----------------------------------------
		// pin no-pin?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_pin'] != 'leave' )
		{
			if ( $this->mm_data['topic_pin'] == 'pin' )
			{
				$this->modLibrary->stmAddPin();
			}
			else if ( $this->mm_data['topic_pin'] == 'unpin' )
			{
				$this->modLibrary->stmAddUnpin();
			}
		}
		
		//-----------------------------------------
		// Approve / Unapprove
		//-----------------------------------------
		
		if ( $this->mm_data['topic_approve'] )
		{
			if ( $this->mm_data['topic_approve'] == 1 )
			{
				$this->modLibrary->stmAddApprove();
			}
			else if ( $this->mm_data['topic_approve'] == 2 )
			{
				$this->modLibrary->stmAddUnapprove();
			}
		}
		
		//-----------------------------------------
		// Topic title
		// Regexes clean title up
		//-----------------------------------------
		
		$title = $this->topic['title'];
		
		if ( $this->mm_data['topic_title_st'] )
		{
			$title = preg_replace( "/^" . preg_quote( $this->mm_data['topic_title_st'], '/' ) . "/", "", $title );
		}
		
		if ( $this->mm_data['topic_title_end'] )
		{
			$title = preg_replace( "/" . preg_quote( $this->mm_data['topic_title_end'], '/' ) . "$/", "", $title );
		}
		
		$this->modLibrary->stmAddTitle( $this->mm_data['topic_title_st'] . $title . $this->mm_data['topic_title_end'] );
		
		//-----------------------------------------
		// Update what we have so far...
		//-----------------------------------------
		
		$this->modLibrary->stmExec( $this->topic['tid'] );
		
		//-----------------------------------------
		// Add reply?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_reply'] and $this->mm_data['topic_reply_content'] )
		{
			$this->modLibrary->auto_update = FALSE;  // Turn off auto forum re-synch, we'll manually do it at the end
			
			IPSText::getTextClass('bbcode')->parse_smilies			= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass('bbcode')->parse_html				= 1;
			IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
			IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
		
			$this->modLibrary->topicAddReply( 
											str_replace( "\n", "", IPSText::getTextClass('bbcode')->preDbParse( str_replace( "\r", '', $this->mm_data['topic_reply_content'] ) ) )
											, array( 0 => array( $this->topic['tid'], $this->forum['id'] ) )
											, $this->mm_data['topic_reply_postcount']
										   );
		}
		
		//-----------------------------------------
		// Move topic?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_move'] )
		{
			//-----------------------------------------
			// Move to forum still exist?
			//-----------------------------------------

			$r = $this->registry->class_forums->allForums[ $this->mm_data['topic_move'] ];

			if( $r['id'] )
			{
				if ( $r['sub_can_post'] != 1 )
				{
					$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), 'mm_id=' . $this->mm_id );
				}
				else
				{
					if ( $r['id'] != $this->forum['id'] )
					{
						$this->modLibrary->topicMove( $this->topic['tid'], $this->forum['id'], $r['id'], $this->mm_data['topic_move_link'] );
					
						$this->modLibrary->forumRecount( $r['id'] );
					}
				}
			}
			else
			{
				$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), 'mm_id=' . $this->mm_id );
			}
		}
		
		//-----------------------------------------
		// Recount root forum
		//-----------------------------------------
		
		$this->modLibrary->forumRecount( $this->forum['id'] );
		
		//-----------------------------------------
		// Add mod log
		//-----------------------------------------
		
		$this->modLibrary->addModerateLog( $this->forum['id'], $this->topic['tid'], "", $this->topic['title'], "Applied multi-mod: " . $this->mm_data['mm_title'] );
		
		//-----------------------------------------
		// Redirect back with nice fluffy message
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( sprintf($this->lang->words['mm_applied'], $this->mm_data['mm_title'] ), $this->settings['base_url'] . "showforum=" . $this->forum['id'], $this->forum['name_seo'] );
				  
	}
}