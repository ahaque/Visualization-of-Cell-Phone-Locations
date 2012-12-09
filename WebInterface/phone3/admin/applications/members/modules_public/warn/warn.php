<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member warn functions
 * Last Updated: $Date: 2009-06-24 23:14:22 -0400 (Wed, 24 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		20th February 2002
 * @version		$Revision: 4818 $
 *
 * @todo 		[Future] Ability to require confirmation of a warning (feature)?
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_warn_warn extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;

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
	 * Topic ID
	 *
	 * @access	private
	 * @var		integer		Topic id
	 */
	private $topic_id		= 0;

	/**
	 * Forum ID
	 *
	 * @access	private
	 * @var		integer		Forum id
	 */
	private $forum_id		= 0;

	/**
	 * Moderator information
	 *
	 * @access	private
	 * @var		array		Array of moderator details
	 */
	private $moderator		= array();

	/**
	 * Can ban member from warn panel
	 *
	 * @access	private
	 * @var		bool		Can ban member
	 */
	private $canSuspend		= 0;

	/**
	 * Can mod queue member from warn panel
	 *
	 * @access	private
	 * @var		bool		Can mod queue member
	 */
	private $canModQueue		= 0;

	/**
	 * Can remove posting rights from member from warn panel
	 *
	 * @access	private
	 * @var		bool		Can remove posting for member
	 */
	private $canRemovePostAbility	= 0;

	/**
	 * Number of times per day member can be warned
	 *
	 * @access	private
	 * @var		integer		Number of times per day member can be warned
	 */
	private $restrictWarnsPerDay	= 0;

	/**
	 * Type of member
	 *
	 * @access	private
	 * @var		string		mod, supmod, or member
	 */
	private $type			= 'mod';
	
	/**
	 * Data for the member being warned
	 *
	 * @access	private
	 * @var		array		Member being warned
	 */
	private $warn_member	= array();
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->loadData();

		//-----------------------------------------
		// Bouncy, bouncy!
		//-----------------------------------------
		
		switch ($this->request['do'])
		{
			case 'dowarn':
				$this->_doWarn();
			break;
			
			case 'add_note':
				$this->_checkAccess();
				$this->_addNoteform();
			break;
			
			case 'save_note':
				$this->_checkAccess();
				$this->_saveNote();
			break;
				
			case 'view':
				$this->_checkAccess();
				$this->viewLog();
			break;
			
			case 'form':
			default:
				$this->_showForm();
			break;
		}
		
		$this->registry->output->addNavigation( $this->lang->words['w_title'], '' );
		$this->registry->output->addContent( $this->output );
		
		if( $this->request['popup'] )
		{
			$this->registry->getClass('output')->popUpWindow( $this->output );
		}
		else
		{
			$this->registry->output->sendOutput();
		}
	}
	
	/**
	 * Load all necessary properties
	 *
	 * @access	public
	 * @param	boolean		Return false on error, instead of display
	 * @return	void
	 */
	public function loadData( $returnOnError=false )
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$this->settings[ 'warn_min'] = $this->settings['warn_min'] ? $this->settings['warn_min'] : 0;
		$this->settings[ 'warn_max'] = $this->settings['warn_max'] ? $this->settings['warn_max'] : 10;
		
		//-----------------------------------------
		// Load modules...
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ), 'forums' );

		if ( ! $this->settings['warn_on'] )
		{
			if( $returnOnError )
			{
				return false;
			}

			$this->registry->output->showError( 'warn_system_off', 10248 );
		}
		
		/* Need app_class_forums to setup moderator stuff */
		require_once( IPS_ROOT_PATH . 'applications/forums/app_class_forums.php' );
		$app_class_forums = new app_class_forums( $this->registry );

		//-----------------------------------------
		// Make sure we're a moderator...
		//-----------------------------------------
		
		$pass = 0;

		if( $this->memberData['member_id'] )
		{
			if( $this->memberData['g_is_supmod'] == 1 )
			{
				$pass				        = 1;
				$this->canSuspend	        = $this->settings['warn_gmod_ban'];
				$this->canModQueue	        = $this->settings['warn_gmod_modq'];
				$this->canRemovePostAbility	= $this->settings['warn_gmod_post'];
				$this->restrictWarnsPerDay	= intval($this->settings['warn_gmod_day']);
				$this->canApprovePosts      = 1;
				$this->canApproveTopics     = 1;
				$this->canDeletePosts       = 1;
				$this->canDeleteTopics      = 1;
				$this->type		   	        = 'supmod';
			}
			else if( $this->memberData['is_mod'] )
			{
				$other_mgroups = array();
				
				if( $this->memberData['mgroup_others'] )
				{
					$other_mgroups	= explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
				}
				
				$other_mgroups[] = $this->memberData['member_group_id'];

				$this->DB->build( array( 
										'select' => '*',
										'from'   => 'moderators',
										'where'  => "(member_id='" . $this->memberData['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" 
								)	);
											  
				$this->DB->execute();
				
				while ( $this->moderator = $this->DB->fetch() )
				{
					if ( $this->moderator['allow_warn'] )
					{
						$pass				        = 1;
						$this->canSuspend		    = $this->settings['warn_mod_ban'];
						$this->canModQueue	        = $this->settings['warn_mod_modq'];
						$this->canRemovePostAbility	= $this->settings['warn_mod_post'];
						$this->restrictWarnsPerDay	= intval($this->settings['warn_mod_day']);
						$this->canApprovePosts      = $this->moderator['post_q'];
						$this->canApproveTopics     = $this->moderator['topic_q'];
						$this->canDeletePosts       = $this->moderator['delete_post'];
						$this->canDeleteTopics      = $this->moderator['delete_topic'];
						$this->type			        = 'mod';
					}
				}
			}			
			else if( $this->settings['warn_show_own'] and $this->memberData['member_id'] == $this->request['mid'] )
			{
				$pass				        = 1;
				$this->canSuspend           = 0;
				$this->canModQueue	        = 0;
				$this->canRemovePostAbility	= 0;
				$this->restrictWarnsPerDay	= 0;
				$this->canApprovePosts      = 0;
				$this->canApproveTopics     = 0;
				$this->canDeletePosts       = 0;
				$this->canDeleteTopics      = 0;
				$this->type			        = 'member';
			}			
		}
			
		if ( $pass == 0 )
		{
			if( $returnOnError )
			{
				return false;
			}

			$this->registry->output->showError( 'warn_no_access', 2025 );
		}

		//-----------------------------------------
		// Ensure we have a valid member
		//-----------------------------------------
		
		$mid = intval($this->request['mid']);
		
		if ( $mid < 1 )
		{
			if( $returnOnError )
			{
				return false;
			}

			$this->registry->output->showError( 'warn_no_user', 10249 );
		}
		
		$this->warn_member = IPSMember::load( $mid, 'all' );

		if ( ! $this->warn_member['member_id'] )
		{
			if( $returnOnError )
			{
				return false;
			}

			$this->registry->output->showError( 'warn_no_user', 10250 );
		}
		
		$this->registry->output->setTitle( $this->page_title ? $this->page_title : $this->lang->words['w_title'] );
	}
	
	/**
	 * Save a new note
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _saveNote()
	{
		if ( $this->type == 'member' )
		{
			$this->registry->output->showError( 'warn_member_notes', 2026 );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content	= '';
		$note		= trim( $this->request['note'] );
		$save		= array();

		if ( $note )
		{
			//-----------------------------------------
			// Ready to save?
			//-----------------------------------------
		
			$save['wlog_notes']		= "<content>{$note}</content>";
			$save['wlog_notes']		.= "<mod></mod>";
			$save['wlog_notes']		.= "<post></post>";
			$save['wlog_notes']		.= "<susp></susp>";
		
			$save['wlog_mid']		= $this->warn_member['member_id'];
			$save['wlog_addedby']	= $this->memberData['member_id'];
			$save['wlog_type']		= 'note';
			$save['wlog_date']		= time();
			
			//-----------------------------------------
			// Enter into warn loggy poos (eeew - poo)
			//-----------------------------------------
		
			$this->DB->insert( 'warn_logs', $save );
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$popup	= $this->request['popup'] ? '&amp;popup=1' : '';
		
		$this->registry->output->silentRedirect( $this->settings['base_url']."app=members&amp;module=warn&amp;section=warn&amp;mid={$this->warn_member['member_id']}&amp;do=view" . $popup );
	}
	
	/**
	 * Form to add a new note
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _addNoteForm()
	{
		if ( $this->type == 'member' )
		{
			$this->registry->output->showError( 'warn_member_notes', 2027 );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content	= '';

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->warn_add_note_form( $this->warn_member['member_id'], $this->warn_member['members_display_name'], $this->warn_member['members_seo_name'] );
		
		$this->registry->getClass('output')->setTitle( $this->lang->words['warn_popup_title'] );
		$this->registry->getClass('output')->popUpWindow( $this->output );
	}
	
	/**
	 * View the warn logs
	 *
	 * @access	public
	 * @return	string		HTML output
	 */
	public function viewLog()
	{
		$perpage	= 50;
		$start		= intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;
		$row		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'warn_logs', 'where' => "wlog_mid={$this->warn_member['member_id']}" ) );
		$rows		= array();

		$links		= $this->registry->output->generatePagination( array(
																		'totalItems'		=> $row['cnt'],
																		'itemsPerPage'		=> $perpage,
																		'currentStartValue'	=> $start,
																		'baseUrl'			=> "app=members&amp;module=warn&amp;section=warn&amp;do=view&amp;mid={$this->warn_member['member_id']}",
												 )	  );
				
		if ( $row['cnt'] > 0 )
		{
			$this->DB->build( array( 
									'select'	=> 'l.*',
									'from'		=> array( 'warn_logs' => 'l' ),
									'where'		=> 'l.wlog_mid=' . $this->warn_member['member_id'],
									'order'		=> 'l.wlog_date DESC ',
									'limit'		=> array( $start, $perpage ),
									'add_join'	=> array(
														array( 
															'select'	=> 'p.member_id as punisher_id, p.members_display_name as punisher_name, p.members_seo_name, p.member_group_id, p.mgroup_others',
															'from'		=> array( 'members' => 'p' ),
															'where'		=> 'p.member_id=l.wlog_addedby',
															'type'		=> 'left',
															)
														)
							)		);
			$q =$this->DB->execute();
		
			while ( $r = $this->DB->fetch( $q ) )
			{
				if ( strstr( $r['wlog_notes'], '<content>' ) )
				{
					$raw = preg_match( "#<content>(.+?)</content>#is", $r['wlog_notes'], $match );
				}
				else
				{
					$_array = unserialize( $r['wlog_notes'] );
					
					if ( is_array( $_array ) AND $_array['content'] )
					{
						$match[1] = $_array['content'];
					}
				}
				
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'warn';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
				$r['content'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $match[1] );
				
				$rows[] = $r;
			}
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->warn_view_log( $this->warn_member, $rows, $links );
		
		$this->registry->getClass('output')->setTitle( $this->lang->words['warn_popup_title'] );
		
		return $this->output;
	}
	
	/**
	 * Add a new arn entry
	 *
	 * @access	private
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _doWarn()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$save                  = array();
		$err 				   = 0;
		$topicPosts_type       = trim( $this->request['topicPosts_type'] );
		$topicPosts_topics     = intval( $this->request['topicPosts_topics'] );
		$topicPosts_replies    = intval( $this->request['topicPosts_replies'] );
		$topicPosts_lastx      = intval( $this->request['topicPosts_lastx'] );
		$topicPosts_lastxunits = trim( $this->request['topicPosts_lastxunits'] );
		$level_custom		   = intval( $this->request['level_custom'] );
		$ban_indef			   = intval( $this->request['ban_indef'] );
		$member_banned		   = intval( $this->warn_member['member_banned'] );
		$warn_level            = intval( $this->warn_member['warn_level']) ;
		
		//-----------------------------------------
		// Load Mod Squad
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php' );
		$moderatorLibrary = new moderatorLibrary( $this->registry );
		
		//-----------------------------------------
		// Security checks
		//-----------------------------------------
		
		if ( $this->type == 'member' )
		{
			$this->registry->output->showError( 'warn_member_notes', 2028 );
		}
		
		//-----------------------------------------
		// Check security fang
		//-----------------------------------------
		
		if ( $this->request['key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'warn_bad_key', 3020 );
		}
		
		//-----------------------------------------
		// As Celine Dion once squawked, "Show me the reason"
		//-----------------------------------------
		
		if ( trim($this->request['reason']) == "" )
		{
			$this->_showForm( 'we_no_reason' );
			return;
		}
		
		//-----------------------------------------
		// Other checks
		//-----------------------------------------
		
		if ( ! $this->settings['warn_past_max'] && $this->request['level'] != 'nochange' )
		{
			if ( $this->request['level'] == 'custom' )
			{
				if ( $level_custom > $this->settings['warn_max'] )
				{
					$err = 1;
				}
				else if( $level_custom < $this->settings['warn_min'] )
				{
					$err = 2;
				}
			}
			else if ( $this->request['level'] == 'add' )
			{
				if ( $warn_level >= $this->settings['warn_max'] )
				{
					$err = 1;
				}
			}
			else
			{
				if ( $warn_level <= $this->settings['warn_min'] )
				{
					$err = 2;
				}
			}
			
			if ( $err )
			{
				$this->registry->output->showError( $err == 1 ? 'warn_past_max_high' : 'warn_past_max_low', 10251 );
			}
		}

		//-----------------------------------------
		// Plussy - minussy?
		//-----------------------------------------
		
		if( $this->request['level'] == 'nochange' )
		{
			$save['wlog_type'] = 'nochan';
		}
		else
		{
			$save['wlog_type']	= ( $this->request['level'] == 'custom' ) ? 'custom' : ( ( $this->request['level'] == 'add' ) ? 'neg' : 'pos' );
		}
		$save['wlog_date']	= time();
		
		//-----------------------------------------
		// Contacting the member?
		//-----------------------------------------
		
		$test_content = trim( IPSText::br2nl( $_POST['contact'] ) ) ;

		if ( $test_content != "" )
		{
			if ( trim($this->request['subject']) == "" )
			{
				$this->_showForm('we_no_subject');
				return;
			}
		
			unset($test_content);
			
			if ( IPSText::getTextClass('editor')->method == 'rte' )
			{
				$this->request['contact'] = IPSText::getTextClass('editor')->processRawPost( 'contact' );
			}

			IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'warn';

			$save['wlog_contact']			= $this->request['contactmethod'];
			$save['wlog_contact_content']	= "<subject>" . $this->request['subject'] . "</subject><content>" . $this->request['contact'] . "</content>";
			$save['wlog_contact_content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $save['wlog_contact_content'] );

			if ( $this->request['contactmethod'] == 'email' )
			{
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= 0;
				IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'warn';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
				
				$this->request['contact'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $this->request['contact'] ) );
				
				//-----------------------------------------
				// Send the email
				//-----------------------------------------
				
				IPSText::getTextClass( 'email' )->getTemplate("email_member");
					
				IPSText::getTextClass( 'email' )->buildMessage( array(
																		'MESSAGE'		=> IPSText::br2nl( $this->request['contact'] ),
																		'MEMBER_NAME'	=> $this->warn_member['members_display_name'],
																		'FROM_NAME'		=> $this->memberData['members_display_name']
																		)
																);

				IPSText::getTextClass( 'email' )->subject	= $this->request['subject'];
				IPSText::getTextClass( 'email' )->to		= $this->warn_member['email'];
				IPSText::getTextClass( 'email' )->from		= $this->settings['email_out'];
				IPSText::getTextClass( 'email' )->sendMail();
			}
			else
			{
				//-----------------------------------------
				// Grab PM class
				//-----------------------------------------
				
				require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
				$messengerFunctions = new messengerFunctions( $this->registry );
 				
				try
				{
				 	$messengerFunctions->sendNewPersonalTopic( $this->warn_member['member_id'],
															$this->memberData['member_id'], 
															array(), 
															$this->request['subject'], 
															IPSText::getTextClass( 'editor' )->method == 'rte' ? nl2br($_POST['contact']) : $_POST['contact'], 
															array( 'origMsgID'			=> 0,
																	'fromMsgID'			=> 0,
																	'postKey'			=> md5(microtime()),
																	'trackMsg'			=> 0,
																	'addToSentFolder'	=> 0,
																	'hideCCUser'		=> 0,
																	'forcePm'			=> 1,
																)
															);
				}
				catch( Exception $error )
				{
					$msg		= $error->getMessage();
					$toMember	= IPSMember::load( $this->warn_member['member_id'], 'core' );
				   
					if ( strstr( $msg, 'BBCODE_' ) )
				    {
						$msg = str_replace( 'BBCODE_', '', $msg );
	
						$this->registry->output->showError( $msg, 10252 );
					}
					else if ( isset($this->lang->words[ 'err_' . $msg ]) )
					{
						$this->lang->words[ 'err_' . $msg ] = $this->lang->words[ 'err_' . $msg ];
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#NAMES#'   , implode( ",", $messengerFunctions->exceptionData ), $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $this->lang->words[ 'err_' . $msg ] );
						
						$this->registry->output->showError( 'err_' . $msg, 10253 );
					}
					else if( $msg != 'CANT_SEND_TO_SELF' )
					{
						$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
						$this->registry->output->showError( $_msgString, 10254 );
					}
				}
			}
		}
		else
		{
			unset($test_content);
		}
		
		//-----------------------------------------
		// Right - is we banned or wha?
		//-----------------------------------------
			
		$restrict_post	= '';
		$mod_queue		= '';
		$susp			= '';
		$_notes         = array();
		
		$_notes['content'] = $this->request['reason'];
		$_notes['mod']     = $this->request['mod_value'];
		$_notes['post']    = $this->request['post_value'];
		$_notes['susp']    = $this->request['susp_value'];
		$_notes['ban']     = $ban_indef;
		$_notes['topicPosts_type']       = $topicPosts_type;
		$_notes['topicPosts_topics']     = $topicPosts_topics;
		$_notes['topicPosts_replies']    = $topicPosts_replies;
		$_notes['topicPosts_lastx']      = $topicPosts_lastx;
		$_notes['topicPosts_lastxunits'] = $topicPosts_lastxunits;
		
		$save['wlog_notes'] = serialize( $_notes );
		
		//-----------------------------------------
		// Member Content
		//-----------------------------------------
		
		if ( $topicPosts_type == 'unapprove' OR $topicPosts_type == 'approve' )
		{
			$time    = ( $topicPosts_lastxunits == 'd' ) ? ( $topicPosts_lastx * 24 ) : $topicPosts_lastx;
			$approve = ( $topicPosts_type == 'approve' ) ? TRUE : FALSE;
			
			if ( ( $topicPosts_topics AND $this->canApproveTopics ) AND ( $topicPosts_replies AND $this->canApprovePosts ) )
			{
				$moderatorLibrary->toggleApproveMemberContent( $this->warn_member['member_id'], $approve, 'all', $time );
			}
			else if ( $topicPosts_topics AND $this->canApproveTopics )
			{
				$moderatorLibrary->toggleApproveMemberContent( $this->warn_member['member_id'], $approve, 'topics', $time );
			}
			else if ( $topicPosts_replies AND $this->canApprovePosts )
			{
				$moderatorLibrary->toggleApproveMemberContent( $this->warn_member['member_id'], $approve, 'replies', $time );
			}
		}
		else if ( $topicPosts_type == 'delete')
		{
			$time = ( $topicPosts_lastxunits == 'd' ) ? ( $topicPosts_lastx * 24 ) : $topicPosts_lastx;
			
			if ( ( $topicPosts_topics AND $this->canDeleteTopics ) AND ( $topicPosts_replies AND $this->canDeletePosts ) )
			{
				$moderatorLibrary->deleteMemberContent( $this->warn_member['member_id'], 'all', $time );
			}
			else if ( $topicPosts_topics AND $this->canDeleteTopics )
			{
				$moderatorLibrary->deleteMemberContent( $this->warn_member['member_id'], 'topics', $time );
			}
			else if ( $topicPosts_replies AND $this->canDeletePosts )
			{
				$moderatorLibrary->deleteMemberContent( $this->warn_member['member_id'], 'replies', $time );
			}
		}
		
		//-----------------------------------------
		// Member Suspension
		//-----------------------------------------
		
		if ( $this->canModQueue )
		{
			if ( $this->request['mod_indef'] == 1 )
			{
				$mod_queue = 1;
			}
			elseif ( $this->request['mod_value'] > 0 )
			{
				$mod_queue = IPSMember::processBanEntry( array( 'timespan' => intval($this->request['mod_value']), 'unit' => $this->request['mod_unit']  ) );
			}
		}
		
		if ( $this->canRemovePostAbility )
		{
			if ( $this->request['post_indef'] == 1 )
			{
				$restrict_post = 1;
			}
			elseif ( $this->request['post_value'] > 0 )
			{
				$restrict_post = IPSMember::processBanEntry( array( 'timespan' => intval($this->request['post_value']), 'unit' => $this->request['post_unit']  ) );
			}
		}
		
		if ( $this->canSuspend )
		{
			if ( $ban_indef )
			{
				$member_banned = 1;
			}
			else if ( $this->request['susp_value'] > 0 )
			{
				$susp = IPSMember::processBanEntry( array( 'timespan' => intval($this->request['susp_value']), 'unit' => $this->request['susp_unit']  ) );
			}
			
			/* Were banned but now unticked? */
			if ( ! $ban_indef AND $member_banned )
			{
				$member_banned = 0;
			}
		}
		
		$save['wlog_mid']		= $this->warn_member['member_id'];
		$save['wlog_addedby']	= $this->memberData['member_id'];
		
		//-----------------------------------------
		// Enter into warn loggy poos (eeew - poo)
		//-----------------------------------------

		$this->DB->insert( 'warn_logs', $save );
		
		//-----------------------------------------
		// Update member
		//-----------------------------------------
		
		if( $this->request['level'] != 'nochange' )
		{
			if ( $this->request['level'] == 'custom' )
			{
				$warn_level = $level_custom;
			}
			else if ( $this->request['level'] == 'add' )
			{
				$warn_level++;
			}
			else
			{
				$warn_level--;
			}
			
			if ( $warn_level > $this->settings['warn_max'] )
			{
				$warn_level = $this->settings['warn_max'];
			}
			
			if ( $warn_level < intval($this->settings['warn_min']) )
			{
				$warn_level = intval($this->settings['warn_min']);
			}
		}
		
		IPSMember::save( $this->warn_member['member_id'], array( 'core' => array( 'mod_posts'		=> $mod_queue,
																				  'restrict_post'	=> $restrict_post,
																				  'temp_ban'		=> $susp,
																				  'member_banned'   => $member_banned,
																				  'warn_level'	    => $warn_level,
																				  'warn_lastwarn'	=> time() ) ) );
		
		//-----------------------------------------
		// Now what? Show success screen, that's what!!
		//-----------------------------------------
		
		$this->lang->words['w_done_te'] = sprintf( $this->lang->words['w_done_te'], $this->warn_member['members_display_name'] );
		
		$tid	= intval($this->request['t']);
		$topic	= array();
		
		if ( $tid > 0 )
		{
			$topic = $this->DB->buildAndFetch( array( 
														'select'	=> 't.tid, t.title, t.title_seo',
														'from'		=> array( 'topics' => 't' ),
														'where'		=> "t.tid={$tid}",
														'add_join'	=> array( array( 
																					'select' => 'f.id, f.name, f.name_seo',
																					'from'	  => array( 'forums' => 'f' ),
																					'where'  => 'f.id=t.forum_id',
																					'type'	  => 'left' 
																			)	) 
											)	);
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->warn_success( $topic );
	}
	
	/**
	 * Show the add new warn form
	 *
	 * @access	private
	 * @param 	mixed		Empty | Error message language key
	 * @return	void		[Outputs to screen/redirects]
	 */
	private function _showForm( $errors="" )
	{
		if ( $this->type == 'member' )
		{
			$this->registry->output->showError( 'warn_member_notes', 10255 );
		}

		$this->warn_member['warn_level'] = intval($this->warn_member['warn_level']);
				
		$this->request['contact'] = $this->request['contact'] ? IPSText::getTextClass( 'bbcode' )->preEditParse( $this->request['contact'] ) : '';

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->warnForm( IPSMember::buildDisplayData( $this->warn_member, '__all__' ),
																							intval($this->request['t']),
																							intval($this->request['st']),
																							$errors ? $this->lang->words[$errors] : '',
																							$this->canModQueue,
																							$this->canRemovePostAbility,
																							$this->canSuspend,
																							IPSText::getTextClass( 'editor' )->showEditor( $this->request['contact'] ? $this->request['contact'] : '', 'contact' ) );
	}
	
	/**
	 * Checking method access
	 *
	 * @access	private
	 * @return	void		[Displays error if appropriate]
	 */
	private function _checkAccess()
	{
		//-----------------------------------------
		// Protected member? Really? o_O
		//-----------------------------------------
		
		if ( strstr( ',' . $this->settings['warn_protected'] . ',', ',' . $this->warn_member['member_group_id'] . ',' ) )
		{
			$this->registry->output->showError( 'warn_protected_member', 10256 );
		}
		
		//-----------------------------------------
		// I've already warned you!!
		//-----------------------------------------
		
		if ( $this->restrictWarnsPerDay > 0 )
		{
			$time_to_check = time() -  86400;
			
			$check = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as warn_times', 'from' => 'warn_logs', 'where' => "wlog_mid={$this->warn_member['member_id']} AND wlog_date > $time_to_check" ) );
			
			if ( $check['warn_times'] >= $this->restrictWarnsPerDay )
			{
				$this->registry->output->showError( 'warned_already', 10257 );
			}
		}
	}
	
}