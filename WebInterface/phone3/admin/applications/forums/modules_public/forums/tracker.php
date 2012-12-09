<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Subscribe to a topic or forum
 * Last Updated: $Date: 2009-03-04 07:36:56 -0500 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		20th February 2002
 * @version		$Revision: 4135 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_tracker extends ipsCommand
{
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
	* Subscription type
	*
	* @access	private
	* @var		string		topic or forum
	*/
	private $type			= 'topic';

	/**
	* Subscription method
	*
	* @access	private
	* @var		string		delayed, immediate, none, daily, weekly
	*/
	private $method			= 'delayed';

	/**
	* Class entry point
	*
	* @access	public
	* @param	object		Registry reference
	* @param	boolean		Return instead of showing topic subscribed page
	* @return	mixed		[Outputs to screen/redirects, or returns if $is_sub is true]
	*/
	public function doExecute( ipsRegistry $registry, $is_sub = false )
	{
		if( !$this->request['t'] )
		{
			$this->registry->output->showError( 'tracker_no_tid', 10361 );
		}

		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_emails' ), 'core' );

		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		if ( $this->request['type'] == 'forum' )
		{
			$this->type = 'forum';
		}
		
		//-----------------------------------------
		// Method..
		//-----------------------------------------
		
		switch ( $this->request['method'] )
		{
			case 'delayed':
			default:
				$this->method = 'delayed';
			break;
			case 'immediate':
				$this->method = 'immediate';
			break;
			case 'none':
				$this->method = 'none';
			break;
			case 'daily':
				$this->method = 'daily';
			break;
			case 'weekly':
				$this->method = 'weekly';
			break;
		}
		
		
		$this->request[ 't'] =  intval($this->request['t'] );
		$this->request[ 'f'] =  intval($this->request['f'] );
		
		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------
		
		if ( ! ipsRegistry::getClass('class_forums')->forum_by_id[ $this->request['f'] ] )
		{
			if ( $is_sub == false )
			{
				$this->registry->output->showError( 'tracker_no_fid', 10362 );
			}
			else
			{
				return false;
			}
		}

		//-----------------------------------------
		// Get the information based on ID
		//-----------------------------------------
		
		if ( $this->type == 'forum' )
		{
			$this->forum = ipsRegistry::getClass('class_forums')->forum_by_id[ $this->request['f'] ];
		}
		else
		{
			$this->topic = $this->DB->buildAndFetch( array( 'select' => 'tid, forum_id, starter_id', 'from' => 'topics', 'where' => 'tid=' . $this->request['t'] ) );
			
			if( !is_array($this->topic) OR !count($this->topic) )
			{
				$this->registry->output->showError( 'tracker_no_tid', 10363 );
			}

			$this->forum = ipsRegistry::getClass('class_forums')->forum_by_id[ $this->request['f'] ];
		}

		//-----------------------------------------
		// Error out if we can not find the topic
		//-----------------------------------------
		
		if ( $this->type != 'forum' )
		{
			if ( ! $this->topic['tid'] )
			{
				if ( $is_sub == false )
				{
					$this->registry->output->showError( 'tracker_no_fid', 10364 );
				}
				else
				{
					return false;
				}
			}
		}

		//-----------------------------------------
		// Check viewing permissions, private forums,
		// password forums, etc
		//-----------------------------------------
		
		if ( !$this->memberData['member_id'] )
		{
			if ( $is_sub == false )
			{
				$this->registry->output->showError( 'tracker_only_members', 10365 );
			}
			else
			{
				return false;
			}
		}
		
		if( $is_sub )
		{
			$this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['id'], 0, $this->type, $this->topic, true );
		}
		else
		{
			$this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['id'], 0, $this->type, $this->topic );
		}
		
		//-----------------------------------------
		// Have we already subscribed?
		//-----------------------------------------
		
		if ( $this->type == 'forum' )
		{
			$this->DB->build( array( 'select'	=> 'frid',
											'from'	=> 'forum_tracker',
											'where'	=> "forum_id='" . $this->forum['id'] . "' AND member_id=" . $this->memberData['member_id'] ) );
			$this->DB->execute();
		}
		else
		{
			$this->DB->build( array( 'select'	=> 'trid',
											'from'	=> 'tracker',
											'where'	=> "topic_id='" . $this->topic['tid'] . "' AND member_id=" . $this->memberData['member_id'] ) );
			$this->DB->execute();
		}
		
		if ( $this->DB->getTotalRows() )
		{
			if ( $is_sub == false )
			{
				$this->registry->output->showError( 'tracker_already_track', 10366 );
			}
			else
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Add it to the DB
		//-----------------------------------------
		
		if ($this->type == 'forum')
		{
			$this->DB->insert( 'forum_tracker', array (
															'member_id'			=> $this->memberData['member_id'],
															'forum_id'			=> $this->request['f'],
															'start_date'		=> time(),
															'forum_track_type'	=> $this->method,
								)						);
		}
		else
		{
			$this->DB->insert( 'tracker', array (
													'member_id'			=> $this->memberData['member_id'],
													'topic_id'			=> $this->topic['tid'],
													'start_date'		=> time(),
													'topic_track_type'	=> $this->method,
									 )	   );
		}
		
		if ( $is_sub == false )
		{
			if ( $this->type == 'forum' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['sub_added'], $this->settings['base_url'] . "showforum={$this->topic['id']}" );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['sub_added'], $this->settings['base_url'] . "showtopic={$this->topic['tid']}&amp;st=" . $this->request['st'] );
			}
		}
		else
		{
			return true;
		}
	}
}
