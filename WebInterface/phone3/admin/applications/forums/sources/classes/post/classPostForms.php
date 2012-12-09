<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Posting display formatting methods
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 * File Created By: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Revision: 5066 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classPostForms extends classPost
{	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		$this->lang->words['the_max_length'] = $this->settings['max_post_length'] * 1024;
	}
	
	/**
	 * Magic __call method
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return void
	 */
	public function __call( $method, $arguments )
	{
		return parent::__call( $method, $arguments );
	}
	
	/**
	 * Displays the ajax edit box
	 *
	 * @access 	public
	 * @return	string		HTML
	 */
	public function displayAjaxEditForm()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$output     = '';
		$extraData  = array();
		$errors     = '';
		
		$this->setIsAjax( TRUE );
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
	
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
		
		//-----------------------------------------
		// Appending a reason for the edit?
		//-----------------------------------------
		$extraData['showAppendEdit'] = 0;
		
		if ( $this->getAuthor('g_append_edit') )
		{
			$extraData['showEditOptions'] = 1;
			$extraData['showAppendEdit'] = 1;
			
			if ( $this->_originalPost['append_edit'] )
			{
				$extraData['checked'] = 'checked';
			}
			else
			{
				$extraData['checked'] = '';
			}
		}
		
		if ( $this->moderator['edit_post'] OR $this->getAuthor('g_is_supmod') )
		{
			$extraData['showEditOptions'] = 1;
			
			$extraData['showReason'] = 1;
		}
		
		//-----------------------------------------
		// Form specific...
		//-----------------------------------------
		
		try
		{
			$topic = $this->editSetUp();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
		
		/* Reset reason for edit */
		$extraData['reasonForEdit']	= $this->request['post_edit_reason'] ? $this->request['post_edit_reason'] : $this->_originalPost['post_edit_reason'];
		$extraData['append_edit']	= $this->request['append_edit'] ? $this->request['append_edit'] : $this->_originalPost['append_edit'];
		
		$extraData['checkBoxes'] = $this->_generateCheckBoxes( 'edit', isset( $topic['tid'] ) ? $topic['tid'] : 0, $this->getForumData('id') );

		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$post        = $this->compilePostData();
		$postContent = $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent();
		
		//-----------------------------------------
		// Hmmmmm....
		//-----------------------------------------
		
		$postContent = IPSText::getTextClass('editor')->unProcessRawPost( $this->_afterPostCompile( $postContent, 'edit' ) );
		
		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if ( $this->_postErrors )
		{
			$errors = $this->lang->words[ $this->_postErrors ];
		}
	
		$html = $this->registry->getClass('output')->getTemplate('editors')->ajaxEditBox( $postContent, $this->getPostID(), $errors, $extraData );
		
		return $html;
	}
	
	/**
	 * Show the reply form
	 *
	 * @access	protected
	 * @param	string	Type of form (new/reply/add)
	 * @param 	array	Array of extra data
	 * @return 	void 	[Passes data to classOutput]
	 */
	protected function _displayForm( $formType, $extraData=array() )
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$output     = '';
		$titleText  = '';
		$buttonText = '';
		$doCode     = '';
		$topText    = '';
		$checkFunc  = '';
		
		//-----------------------------------------
		// Work out function type
		//-----------------------------------------
		
		switch( $formType )
		{
			default:
			case 'reply':
				$checkFunc  = 'replySetUp';
			break;
			case 'new':
				$checkFunc  = 'topicSetUp';
			break;
			case 'edit': 
				$checkFunc  = 'editSetUp';
			break;
		}
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
	
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
		
		//-----------------------------------------
		// Form specific...
		//-----------------------------------------
		
		try
		{
			$topic = $this->$checkFunc();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}

		//-----------------------------------------
		// Work out elements
		//-----------------------------------------
		
		switch( $formType )
		{
			default:
			case 'reply':
				$doCode     = 'reply_post_do';
				$titleText  = $this->lang->words['top_txt_reply'] . ' ' . $topic['title'];
				$buttonText = $this->lang->words['submit_reply'];
				$topText    = $this->lang->words['replying_in'] . ' ' . $topic['title'];
			break;
			case 'new':
				$doCode     = 'new_post_do';
				$titleText  = $this->lang->words['top_txt_new'] . $this->getForumData('name');
				$buttonText = $this->lang->words['submit_new'];
				$topText    = $this->lang->words['posting_new_topic'];
			break;
			case 'edit': 
				$doCode     = 'edit_post_do';
				$titleText  = $this->lang->words['top_txt_edit'] . ' ' . $topic['title'];
				$buttonText = $this->lang->words['submit_edit'];
				$topText    = $this->lang->words['editing_post'] . ' ' . $topic['title'];
				
				/* Reset reason for edit */
				$extraData['reasonForEdit'] = $this->request['post_edit_reason'] ? $this->request['post_edit_reason'] : $this->_originalPost['post_edit_reason'];

				/* Reset check boxes and such */
				$this->setSettings( array( 'enableSignature' => $this->_originalPost['use_sig'],
										   'enableEmoticons' => $this->_originalPost['use_emo'],
										   'post_htmlstatus' => $this->_originalPost['post_htmlstate'],
										   'enableTracker'   => ( (intval($this->request['enabletrack']) != 0) OR $this->getIsPreview() !== TRUE ) ? 1 : 0 ) );
			break;
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------

		$post        = $this->compilePostData();
		$postContent = $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent();
 
		//-----------------------------------------
		// Hmmmmm....
		//-----------------------------------------
		
		$postContent = $this->_afterPostCompile( $postContent, $formType );

		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		//-----------------------------------------
		// Are we quoting posts?
		//-----------------------------------------
		
		$postContent = $this->_checkMultiQuote( $postContent );
		
		//-----------------------------------------
		// RTE? Convert RIGHT tags that QUOTE would
		// have put there
		// Commented out 14/7/08 - _afterPostCompile handles this for edit, and should
		// 	also handle for any other type if they need it...
		//-----------------------------------------
		
		/*if ( IPSText::getTextClass('editor')->method == 'rte' )
		{
			$postContent = IPSText::getTextClass('bbcode')->convertForRTE( $postContent );
		}*/

		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if ( $this->_postErrors )
		{
			$output .= $this->registry->getClass('output')->getTemplate('post')->errors( $this->lang->words[ $this->_postErrors ] );
		}
		
		if ( $this->getIsPreview() )
		{
			$output .= $this->registry->getClass('output')->getTemplate('post')->preview( $this->_generatePostPreview( $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent(), $this->post_key ) );
		}

		/* Defaults */
		if( ! isset( $extraData['checked'] ) )
		{
			$extraData['checked'] = '';
		}
		
		//-----------------------------------------
		// Gather status messages
		//-----------------------------------------
		
		/* status from mod posts */
		$this->registry->getClass('class_forums')->checkGroupPostPerDay( $this->getAuthor(), TRUE );
		
		$_statusMsg[] = $this->registry->getClass('class_forums')->ppdStatusMessage;
		$_statusMsg[] = $this->registry->getClass('class_forums')->fetchPostModerationStatusMessage( $this->getAuthor(), $this->getForumData(), $topic, $formType );
		
		//-----------------------------------------
		// Load attachments so we get some stats
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
		$class_attach = new class_attach( $this->registry );
		$class_attach->type				= 'post';
		$class_attach->attach_post_key	= $this->post_key;
		$class_attach->init();
		$class_attach->getUploadFormSettings();
		
		//-----------------------------------------
		// START TABLE
		//-----------------------------------------

		$output .= $this->registry->getClass('output')->getTemplate('post')->postFormTemplate( array( 'title'            => $titleText,
																									  'captchaHTML'      => $this->_generateGuestCaptchaHTML(),
																									  'checkBoxes'       => $this->_generateCheckBoxes( $formType, isset( $topic['tid'] ) ? $topic['tid'] : 0, $this->getForumData('id') ),
																									  'editor'           => IPSText::getTextClass('editor')->showEditor( $postContent, 'Post' ),
																									  'buttonText'       => $buttonText,
																									  'uploadForm'       => ( $this->can_upload ) ? $this->registry->getClass('output')->getTemplate('post')->uploadForm( $this->post_key, 'post', $class_attach->attach_stats, $this->getPostID(), $this->getForumData('id') ) : "",
																									  'postIconSelected' => $this->_generatePostIcons(),
																									  'topicSummary'     => $this->_generateTopicSummary( $topic['tid'] ),
																									  'formType'         => $formType,
																									  'extraData'        => $extraData,
																									  'modOptionsData'   => $this->_generateModOptions( $topic, $formType ),
																									  'pollBoxHTML'      => $this->_generatePollBox( $formType ),
																									  'canEditTitle'     => $this->edit_title,
																									  'topicTitle'       => $this->_topicTitle ? $this->_topicTitle : $topic['title'],
																									  'topicDesc'        => $this->_topicDescription ? $this->_topicDescription  : $topic['description'],
																									  'seoTopic'		 => $topic['title_seo'],
																									  'seoForum'		 => $this->getForumData('name_seo'),
																									  'statusMsg'        => $_statusMsg
																								), 
																								array( 	'doCode' 			=> $doCode,
																									 	'p'					=> $this->getPostID(),
																										't'					=> $topic['tid'],
																										'f'					=> $this->getForumData('id'),
																										'parent'			=> ( ipsRegistry::$request['parent_id'] ? intval(ipsRegistry::$request['parent_id']) : 0 ),
																										'attach_post_key'	=> $this->post_key,
																									) );
			
		//-----------------------------------------
		// Reset multi-quote cookie
		//-----------------------------------------
		
		IPSCookie::set('mqtids', ',', 0);
		
		//-----------------------------------------
		// Send for output
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $topText . ' - ' . $this->settings['board_name']);
		$this->registry->getClass('output')->addContent( $output );
		
		$this->nav = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $this->getForumData('id') );

    	if ( isset($topic['tid']) AND $topic['tid'] )
    	{
    		$this->nav[] = array( $topic['title'], "showtopic={$topic['tid']}", $topic['title_seo'], 'showtopic' );
    	}

		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
        $this->registry->getClass('output')->sendOutput();
	}

	
	/**
	 * Show the edit form
	 *
	 * @access	public
	 * @return	string	HTML to show
	 */
	public function showEditForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$extraData   = array();
		
		/* At this point, $this->moderator isn't set up because global set up hasn't yet been run */
		if ( $this->getAuthor('member_id') != 0 and $this->getAuthor('g_is_supmod') == 0 )
        {
			$_moderator      = $this->getAuthor('forumsModeratorData');
			$this->moderator = $_moderator[ $this->getForumID() ];
        }
	
		//-----------------------------------------
		// Appending a reason for the edit?
		//-----------------------------------------
	
		if ( $this->getAuthor('g_append_edit') )
		{
			$extraData['showEditOptions'] = 1;
			
			if ( isset( $this->_originalPost['append_edit'] ) && $this->_originalPost['append_edit'] )
			{
				$extraData['checked'] = "checked";
			}		
		}
		
		if ( ( isset( $this->moderator['edit_post'] ) && $this->moderator['edit_post'] ) OR $this->getAuthor('g_is_supmod') )
		{
			$extraData['showReason'] = 1;
		}
		
		$this->_displayForm( 'edit', $extraData );
	}
	
	/**
	 * Show the reply form
	 *
	 * @access	public
	 * @return	string	HTML to show
	 */
	public function showReplyForm()
	{
		$this->_displayForm( 'reply' );
	}
	
	/**
	 * Show the topic form
	 *
	 * @access	public
	 * @return	string	HTML to show
	 */
	public function showTopicForm()
	{
		$this->_displayForm( 'new' );
	}
	
	/**
	 * After post compilation has taken place, we can manipulate it further
	 *
	 * @param	string	Post content
	 * @param	string	Form type (new/edit/reply)
	 * @access	protected
	 * @author	MattMecham
	 */
	protected function _afterPostCompile( $postContent, $formType )
	{
		$postContent = $postContent ? $postContent : $this->_originalPost['post'];
		
		if ( $formType == 'edit' )
		{
			//-----------------------------------------
			// Unconvert the saved post if required
			//-----------------------------------------

			if ( ! isset($_POST['Post']) )
			{
				//-----------------------------------------
				// If we're using RTE, then just clean up html
				//-----------------------------------------

				if ( IPSText::getTextClass('editor')->method == 'rte' )
				{
					IPSText::getTextClass('bbcode')->parse_wordwrap	= 0;
					IPSText::getTextClass('bbcode')->parse_html		= 0;

					$postContent = IPSText::getTextClass('bbcode')->convertForRTE( $postContent );
				}
				else
				{
					$this->_originalPost['post_htmlstate']				= isset($this->_originalPost['post_htmlstate']) ? $this->_originalPost['post_htmlstate'] : 0;
					IPSText::getTextClass('bbcode')->parse_html			= intval($this->_originalPost['post_htmlstate']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml') ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_nl2br		= (isset($this->_originalPost['post_htmlstate']) AND $this->_originalPost['post_htmlstate'] == 2) ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_smilies		= intval($this->_originalPost['use_emo']);
					IPSText::getTextClass('bbcode')->parse_bbcode		= $this->getForumData('use_ibc');
					IPSText::getTextClass('bbcode')->parsing_section	= 'topics';

					if ( IPSText::getTextClass('bbcode')->parse_html )
					{
						if ( ! IPSText::getTextClass('bbcode')->parse_nl2br )
						{
							$postContent = str_replace( '<br />', "", $postContent );
							$postContent = str_replace( '<br>'	, "", $postContent );
						}
					}

					$postContent = IPSText::getTextClass('bbcode')->preEditParse( $postContent );
				}
			}
			else
			{
				if ( IPSText::getTextClass('editor')->method != 'rte' && $this->request['_from'] != 'quickedit' )
				{
					$_POST['Post'] = str_replace( '&', '&amp;', $_POST['Post'] );
					$_POST['Post'] = str_replace( '&amp;#092;', '&#092;', $_POST['Post'] );
				}
				else
				{
					//-----------------------------------------
					// Coming from quick edit, need to fix the
					// HTML entities - we could even check
					// request['_from'] == 'quickedit' here if need be
					//-----------------------------------------

					$_POST['Post'] = str_replace( '&amp;', '&', $_POST['Post'] );
				
				}

				$postContent = IPSText::stripslashes( $_POST['Post'] );
				//print nl2br(htmlspecialchars($postContent));exit;
			}
		}
		else
		{
			//-----------------------------------------
			// Need to unparse if we're showing the form again
			//-----------------------------------------

			if ( $this->getIsPreview() === true OR $this->_postErrors )
			{
				if ( IPSText::getTextClass('editor')->method == 'rte' )
				{
					IPSText::getTextClass('bbcode')->bypass_badwords	= true;
					
					$postContent = IPSText::getTextClass('bbcode')->convertForRTE( $postContent );
				}
				else
				{
					IPSText::getTextClass('bbcode')->parse_html			= intval($_POST['post_htmlstate']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml') ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_nl2br		= (isset($_POST['post_htmlstate']) AND $_POST['post_htmlstate'] == 2) ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_smilies		= intval($_POST['use_emo']);
					IPSText::getTextClass('bbcode')->parse_bbcode		= $this->getForumData('use_ibc');
					IPSText::getTextClass('bbcode')->bypass_badwords	= true; // don't do the changeouts to the text that will be in the form
					IPSText::getTextClass('bbcode')->parsing_section	= 'topics';

					if ( ! IPSText::getTextClass('bbcode')->parse_html )
					{
						if ( IPSText::getTextClass('bbcode')->parse_nl2br )
						{
							$postContent = str_replace( '<br />', "", $postContent );
							$postContent = str_replace( '<br>'	, "", $postContent );
						}
					}

					$postContent = IPSText::getTextClass('bbcode')->preEditParse( $postContent );
				}			
			}
		}

		return $postContent;
	}
	
	/**
	 * Topic Summary
	 *
	 * @param	int		Topic ID
	 * @return	string	HTML elements
	 * @access	protected
	 * @author 	MattMecham
	 */
	protected function _generateTopicSummary( $topicID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$output         = '';
		$cached_members = array();
		$attach_pids	= array();
		$posts          = array();
		
		//-----------------------------------------
		// CHECK
		//-----------------------------------------
		
		if ( ! $topicID )
		{
			return;
		}
		
		if ( $this->settings['disable_summary'] )
		{
			return;
		}
		
		//-----------------------------------------
		// Get the posts
		// This section will probably change at some point
		//-----------------------------------------
						
		$this->DB->build( array( 'select'   => 'p.*',
									   'from'     => array( 'posts' => 'p' ),
									   'where'    => 'p.topic_id=' . $topicID . ' AND p.queued=0',
									   'order'    => 'pid DESC',
									   'limit'    => array( 0, 10 ),
									   'add_join' => array( 0 => array( 'select' => 'm.members_display_name, m.member_group_id, m.mgroup_others, m.member_id, m.members_seo_name',
																	    'from'   => array( 'members' => 'm' ),
																	    'where'  => 'm.member_id=p.author_id',
																	    'type'   => 'left' ) ) ) );
							 
		$post_query = $this->DB->execute();
		
		while ( $row = $this->DB->fetch($post_query) )
		{
		    $row['author'] = $row['members_display_name'] ? $row['members_display_name'] : $row['author_name'];
			
			$row['date']   = $this->registry->getClass( 'class_localization')->getDate( $row['post_date'], 'LONG' );
			
			//-----------------------------------------
			// Parse the post
			//-----------------------------------------
	
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= $row['post_htmlstate'];
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

			$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
			
			//-----------------------------------------
			// View image...
			//-----------------------------------------
			
			$row['post'] = IPSText::getTextClass( 'bbcode' )->memberViewImages( $row['post'] );
			
			$posts[ $row['pid'] ] = $row;
				
			//-----------------------------------------
			// Are we giving this bloke a good ignoring?
			//-----------------------------------------

			if ( $this->getAuthor('ignored_users') )
			{
				if ( in_array( $row['author_id'], $this->getAuthor('ignored_users') ) and $this->request['qpid'] != $row['pid'] )
				{
					if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$row['member_group_id'].',' ) )
					{
						$posts[ $row['pid'] ]['_ignored'] = 1;
					}
				}
			}			

			$attach_pids[] = $row['pid'];
		}

		$content = $this->registry->getClass('output')->getTemplate('post')->topicSummary( $posts );
		
		//-----------------------------------------
		// Got any attachments?
		//-----------------------------------------
		
		if ( count( $attach_pids ) )
		{
			//-----------------------------------------
			// Get topiclib
			//-----------------------------------------
			
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
				$this->class_attach                  =  new class_attach( $this->registry );
				$this->class_attach->attach_post_key =  '';
				
				$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );
			}

			$this->class_attach->attach_post_key =  '';
			$this->class_attach->type            = 'post';
			$this->class_attach->init();
		
			$content = $this->class_attach->renderAttachments( $content, $attach_pids );
		}	
		
		return $content;
	}
	
	/**
	 * Generates checkboxes
	 *
	 * @access	protected
	 * @param	string	Type of form
	 * @param	int		Topic ID
	 * @param	int		Forum ID
	 * @return	string	HTML of Checkboxes
	 */
	protected function _generateCheckBoxes($type="", $topicID="", $forumID="") 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$options_array   = array( 0 => '', 1 => '', 2 => '' );
		$group_cache     = $this->registry->cache()->getCache('group_cache');
		$return          = array(
								  'sig'  => 'checked="checked"',
						  		  'emo'  => 'checked="checked"',
						          'html' => array(),
						  		  'tra'  => $this->getAuthor('auto_track') ? 'checked="checked"' : ''
						        );
		
		if ( ! $this->getSettings('enableSignature') )
		{
			$return['sig'] = "";
		}
		
		if ( ! $this->getSettings('enableEmoticons') )
		{
			$return['emo'] = "";
		}
		
		if ( $this->registry->class_forums->forum_by_id[ $forumID ]['use_html'] and $group_cache[ $this->getAuthor('member_group_id') ]['g_dohtml'] )
		{
			$options_array[ $this->getSettings('post_htmlstatus') ] = ' selected="selected"';
			
			$return['html'] = $options_array;
		}
		
		if ( $type == 'reply' )
		{
			if ( $topicID and $this->getAuthor('member_id') )
			{
				$this->DB->build( array( 'select' => 'trid',
										 'from'   => 'tracker',
									     'where'  => "topic_id=" . $topicID . " AND member_id=".$this->getAuthor('member_id') ) );
				$this->DB->execute();
				
				if ( $this->DB->getTotalRows() )
				{
					$return['tra'] = '-tracking-';
				}
			}
		}
	
		return $return;
	}
	
	/**
	 * Generates mod options dropdown
	 *
	 * @access	protected
	 * @param	array 	Topic data
	 * @param	string	Type of form (new/edit/reply)
	 * @return	string	HTML of dropdown box
	 */
	protected function _generateModOptions( $topic, $type='new' )
	{
		/* INIT */
		$can_close = 0;
		$can_pin   = 0;
		$can_unpin = 0;
		$can_open  = 0;
		$can_move  = 0;
		$html      = "";
		$mytimes   = array();
			
		//-----------------------------------------
		// Mod options
		//-----------------------------------------
		
		if ( $type != 'edit' )
		{
			if ( $this->getAuthor('g_is_supmod') )
			{
				$can_close = 1;
				$can_open  = 1;
				$can_pin   = 1;
				$can_unpin = 1;
				$can_move  = 1;
			}
			else if ( $this->getAuthor('member_id') != 0 )
			{
				if ( $this->moderator['mid'] != "" )
				{
					if ($this->moderator['close_topic'])
					{
						$can_close = 1;
					}
					if ($this->moderator['open_topic'])
					{
						$can_open  = 1;
					}
					if ($this->moderator['pin_topic'])
					{
						$can_pin   = 1;
					}
					if ($this->moderator['unpin_topic'])
					{
						$can_unpin = 1;
					}
					if ($this->moderator['move_topic'])
					{
						$can_move  = 1;
					}
				}
			}
			else
			{
				// Guest
				return "";
			}
			
			if ( !($can_pin == 0 and $can_close == 0 and $can_move == 0) )
			{
				$selected = ($this->getModOptions() == 'nowt') ? " selected='selected'" : '';
				
				$html = "<select id='forminput' name='mod_options' class='forminput'>\n<option value='nowt'{$selected}>".$this->lang->words['mod_nowt']."</option>\n";
			}
			
			if ($can_pin AND !$topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'pin') ? " selected='selected'" : '';
				
				$html .= "<option value='pin'{$selected}>".$this->lang->words['mod_pin']."</option>";
			}
			else if ($can_unpin AND $topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'unpin') ? " selected='selected'" : '';
				
				$html .= "<option value='unpin'{$selected}>".$this->lang->words['mod_unpin']."</option>";	
			}
			
			if ( $can_close AND ($topic['state'] == 'open' OR !$topic['state']) )
			{
				$selected = ($this->getModOptions() == 'close') ? " selected='selected'" : '';
				
				$html .= "<option value='close'{$selected}>".$this->lang->words['mod_close']."</option>";
			}
			else if ( $can_open AND $topic['state'] == 'closed' )
			{
				$selected = ($this->getModOptions() == 'open') ? " selected='selected'" : '';
				
				$html .= "<option value='open'{$selected}>".$this->lang->words['mod_open']."</option>";
			}
			
			if ( $can_close and $can_pin and $topic['state'] == 'open' AND !$topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'pinclose') ? " selected='selected'" : '';
				
				$html .= "<option value='pinclose'{$selected}>".$this->lang->words['mod_pinclose']."</option>";
			}
			else if( $can_open and $can_pin and $topic['state'] == 'closed' AND !$topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'pinopen') ? " selected='selected'" : '';
				
				$html .= "<option value='pinopen'{$selected}>".$this->lang->words['mod_pinopen']."</option>";
			}
			else if ( $can_close and $can_unpin and $topic['state'] == 'open' AND $topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'unpinclose') ? " selected='selected'" : '';
				
				$html .= "<option value='unpinclose'{$selected}>".$this->lang->words['mod_unpinclose']."</option>";
			}
			else if( $can_open and $can_unpin and $topic['state'] == 'closed' AND $topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'unpinopen') ? " selected='selected'" : '';
				
				$html .= "<option value='unpinopen'{$selected}>".$this->lang->words['mod_unpinopen']."</option>";
			}
			
			if ($can_move and $type != 'new' )
			{
				$selected = ($this->getModOptions() == 'move') ? " selected='selected'" : '';
				
				$html .= "<option value='move'{$selected}>".$this->lang->words['mod_move']."</option>";
			}
		}
		
		//-----------------------------------------
		// If we're replying, kill off time boxes
		//-----------------------------------------

		if ( $type == 'reply' )
		{
			$this->can_set_open_time  = 0;
			$this->can_set_close_time = 0;
		}
		else
		{
			//-----------------------------------------
			// Check dates...
			//-----------------------------------------
			
			$mytimes['open_time']  = isset($_POST['open_time_time'])  ? $_POST['open_time_time']  : '';
			$mytimes['open_date']  = isset($_POST['open_time_date'])  ? $_POST['open_time_date']  : '';
			$mytimes['close_time'] = isset($_POST['close_time_time']) ? $_POST['close_time_time'] : '';
			$mytimes['close_date'] = isset($_POST['close_time_date']) ? $_POST['close_time_date'] : '';
			
			if( $this->_originalPost['new_topic'] )
			{
				if ( !isset($mytimes['open_date']) OR !$mytimes['open_date'] )
				{
					if ( isset($topic['topic_open_time']) AND $topic['topic_open_time'] )
					{
						$date                 = IPSTime::unixstamp_to_human( $topic['topic_open_time'] );
						$mytimes['open_date'] = sprintf("%02d/%02d/%04d", $date['month'], $date['day'], $date['year'] );
						$mytimes['open_time'] = sprintf("%02d:%02d"     , $date['hour'] , $date['minute'] );
					}
				}
				
				if ( !isset($mytimes['close_date']) OR !$mytimes['close_date'] )
				{
					if ( isset($topic['topic_close_time']) AND $topic['topic_close_time'] )
					{
						$date                  = IPSTime::unixstamp_to_human( $topic['topic_close_time'] );
						$mytimes['close_date'] = sprintf("%02d/%02d/%04d", $date['month'], $date['day'], $date['year'] );
						$mytimes['close_time'] = sprintf("%02d:%02d"     , $date['hour'] , $date['minute'] );
					}
				}
			}
			else
			{
				if ( $type != 'new' )
				{
					$this->can_set_open_time  = 0;
					$this->can_set_close_time = 0;
				}
			}
		}
		
		return array( 'dropDownOptions' => $html,
					  'canSetOpenTime'  => $this->can_set_open_time,
					  'canSetCloseTime' => $this->can_set_close_time,
					  'myTimes'         => $mytimes );
	}
	
	/**
	 * Generates the post icons
	 *
	 * @access	protected
	 * @param	string	Chosen post icon (optional)
	 * @return 	string	HTML
	 */
	protected function _generatePostIcons( $post_icon="" )
	{
		$postIcons = array();
		$post_icon = ( $post_icon ) ? $post_icon : intval($this->request['iconid']);
		
		if ( isset( $post_icon ) )
		{
			$postIcons[ $post_icon ] = 1;
		}
		else
		{
			/* We use a key incase we use keys for post icons */
			$postIcons['0'] = 1;
		}
		
		return $postIcons;
	}
	
	/**
	 * Generates the captcha if required
	 *
	 * @access	protected
	 * @return	string	Captcha IMG id
	 */
	protected function _generateGuestCaptchaHTML()
	{
		if ( ! $this->getAuthor('member_id') AND $this->settings['guest_captcha'] )
		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();

			return $captchaHTML;
		}
	}
	
	/**
	 * Generates the poll box
	 *
	 * @param	string	Form type (new/edit/reply)
	 * @return	string	HTML
	 * @author	MattMecham
	 * @access	protected
	 */
	protected function _generatePollBox( $formType )
	{
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Did someone hit preview / do we have
			// post info?
			//-----------------------------------------
			
			$poll_questions   = array();
			$poll_question	  = "";
			$poll_view_voters = 0;
			$poll_choices     = array();
			$show_open        = 0;
			$is_mod           = 0;
			$poll_votes		  = array();
			$poll_only	  	  = array();
			$poll_multi		  = array();			
			
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				foreach( $_POST['question'] as $id => $question )
				{
					$poll_questions[$id] = $question;
				}
				
				$poll_question = ipsRegistry::$request['poll_question'];
				$show_open     = 1;
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $checked )
				{
					$poll_multi[ $id ] = $checked;
				}
			}			
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $id => $choice )
				{
					$poll_choices[ $id ] = $choice;
				}
			}
			
			if ( $formType == 'edit' )
			{
				if ( isset( $_POST['votes'] ) && is_array( $_POST['votes'] ) and count( $_POST['votes'] ) )
				{
					foreach( $_POST['votes'] as $id => $vote )
					{
						$poll_votes[ $id ] = $vote;
					}
				}
			}
			
			$poll_only = 0;
			
			if( $this->settings['ipb_poll_only'] AND ipsRegistry::$request['poll_only'] AND ipsRegistry::$request['poll_only'] == 1 )
			{
				$poll_only = 1;
			}			
			
			if ( $formType == 'edit' AND ( ! isset($_POST['question']) OR ! is_array( $_POST['question'] ) OR ! count( $_POST['question'] ) ) )
			{
				//-----------------------------------------
				// Load the poll from the DB
				//-----------------------------------------
				
				$this->poll_data    = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid=".$this->getTopicID() ) );
	    		$this->poll_answers = $this->poll_data['choices'] ? unserialize(stripslashes($this->poll_data['choices'])) : array();

        		//-----------------------------------------
        		// Lezz go
        		//-----------------------------------------
        		
        		foreach( $this->poll_answers as $question_id => $data )
        		{
        			if( !$data['question'] OR !is_array($data['choice']) )
        			{
        				continue;
        			}
        			
        			$poll_questions[ $question_id ] = $data['question'];
        			$poll_multi[ $question_id ]     = isset($data['multi']) ? intval($data['multi']) : 0;
        			
        			foreach( $data['choice'] as $choice_id => $text )
					{
						$poll_choices[ $question_id . '_' . $choice_id ] = $text;
						$poll_votes[ $question_id . '_' . $choice_id ]   = intval($data['votes'][ $choice_id ]);
					}
				}
				
				$poll_only = 0;
				
				if ( $this->settings['ipb_poll_only'] AND $this->poll_data['poll_only'] == 1 )
				{
					$poll_only = "checked='checked'";
				}				
				
				$poll_view_voters = $this->poll_data['poll_view_voters'];
				$poll_question   = $this->poll_data['poll_question'];
				$show_open       = $this->poll_data['choices'] ? 1 : 0;
				$is_mod          = $this->can_add_poll_mod;
			}
			else
			{
				$poll_view_voters = $this->request['poll_view_voters'];
			}
			
			return $this->registry->getClass('output')->getTemplate('post')->pollBox( $this->max_poll_questions, $this->max_poll_choices_per_question, IPSText::simpleJsonEncode( $poll_questions ), IPSText::simpleJsonEncode( $poll_choices ), IPSText::simpleJsonEncode( $poll_votes ), $show_open, $poll_question, $is_mod, json_encode( $poll_multi ), $poll_only, $poll_view_voters, intval( $this->poll_data['votes'] ) );
		}
		
		return '';
	}
	
	/**
	 * Show a preview of the post
	 *
	 * @param	string	Post Content
	 * @param	string	MD5 post key for attachments
	 * @return	string	HTML
	 * @access	protected
	 * @author 	Matt Mecham
	 */
    protected function _generatePostPreview( $postContent="", $post_key='' )
    {
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ) );
	
    	IPSText::getTextClass('bbcode')->parse_html					= (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br				= $this->request['post_htmlstatus'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_smilies				= intval($this->getSettings('enableEmoticons'));
		IPSText::getTextClass('bbcode')->parse_bbcode  				= $this->getForumData('use_ibc');
		IPSText::getTextClass('bbcode')->parsing_section			= 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		# Make sure we have the pre-display look
		$postContent = IPSText::getTextClass('bbcode')->preDisplayParse( $postContent );
		
		if ( ! is_object( $this->class_attach ) )
		{
			//-----------------------------------------
			// Grab render attach class
			//-----------------------------------------

			require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
			$this->class_attach = new class_attach( $this->registry );
		}
			
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		$this->class_attach->type  = 'post';
		$this->class_attach->attach_post_key = $post_key;
		$this->class_attach->init();
		
		$attachData = $this->class_attach->renderAttachments( array( 0 => $postContent ) );			
		
		return $attachData[0]['html'] . $attachData[0]['attachmentHtml'];
    }
	
}