<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Add rating fallback
 * Last Updated: $Date: 2009-02-04 15:03:59 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_extras_rating extends ipsCommand
{
	/**
	 * Forum data
	 *
	 * @access	public
	 * @var		array
	 */
	public $forum		= array();
	
	/**
	 * Topic data
	 *
	 * @access	public
	 * @var		array
	 */
	public $topic		= array();
	
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
		// INIT
		//-----------------------------------------

		$topic_id	= intval($this->request['t']);
		$rating_id	= intval($this->request['rating']);
		$vote_cast	= array();

		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ) );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if ( ! $topic_id )
		{
			$this->registry->output->showError( 'topics_no_tid', 10346 );
		}

		$this->topic	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_id ) );
		$this->forum	= $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ];
		
		if ( ! $this->forum['id'] )
		{
			$this->registry->output->showError( 'topics_no_fid', 103150 );
		}
		
		if ( ! $this->topic['tid'] )
		{
			$this->registry->output->showError( 'topics_no_tid', 10347 );
		}

		//-----------------------------------------
		// Locked topic?
		//-----------------------------------------

   		if ( $this->topic['state'] != 'open' )
   		{
   			$this->registry->output->showError( 'topic_rate_locked', 10348 );
   		}

		if ( ! $this->registry->class_forums->canQueuePosts($this->forum['id']) )
		{
			if ( $this->topic['approved'] != 1 )
			{
				$this->registry->output->showError( 'topic_not_approved', 103151 );
			}
		}
		
		$this->registry->class_forums->forumsCheckAccess( $this->forum['id'], 1, 'topic', $this->topic );

		//-----------------------------------------
		// Permissions check
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] )
		{
			$_can_rate = intval( $this->memberData['g_topic_rate_setting'] );
		}
		else
		{
			$_can_rate = 0;
		}

		if ( ! $this->forum['forum_allow_rating'] )
		{
			$_can_rate = 0;
		}

		if ( ! $_can_rate )
		{
			$this->registry->output->showError( 'topic_rate_no_perm', 10345 );
		}

		//-----------------------------------------
		// Sneaky members rating topic more than 5?
		//-----------------------------------------

   		if( $rating_id > 5 )
   		{
	   		$rating_id = 5;
   		}

   		if( $rating_id < 0 )
   		{
	   		$rating_id = 0;
   		}

   		//-----------------------------------------
   		// Have we rated before?
		//-----------------------------------------

		$rating = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$this->topic['tid']} and rating_member_id=" . $this->memberData['member_id'] ) );

		//-----------------------------------------
		// Already rated?
		//-----------------------------------------

		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------

			if ( $this->memberData['g_topic_rate_setting'] == 2 )
			{
				if ( $rating_id != $rating['rating_value'] )
				{
					$new_rating = $rating_id - $rating['rating_value'];
					
					$this->DB->update( 'topic_ratings', array( 'rating_value' => $rating_id ), 'rating_id=' . $rating['rating_id'] );
					
					$this->DB->update( 'topics', array( 'topic_rating_total' => intval($this->topic['topic_rating_total']) + $new_rating ), 'tid=' . $this->topic['tid'] );
				}

				$this->registry->output->redirectScreen( $this->lang->words['topic_rating_changed'] , $this->settings['base_url'] . "showtopic={$this->topic['tid']}&amp;st=" . $this->request['st'] );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['topic_rated_already'] , $this->settings['base_url'] . "showtopic={$this->topic['tid']}&amp;st=" . $this->request['st'] );
			}
		}

		//-----------------------------------------
		// NEW RATING!
		//-----------------------------------------

		else
		{
			$this->DB->insert( 'topic_ratings', array( 
															'rating_tid'		=> $this->topic['tid'],
															'rating_member_id'	=> $this->memberData['member_id'],
															'rating_value'		=> $rating_id,
															'rating_ip_address'	=> $this->member->ip_address 
														) 
								);

			$this->DB->update( 'topics', array( 
													'topic_rating_hits'		=> intval( $this->topic['topic_rating_hits'] )  + 1,
													'topic_rating_total'	=> intval( $this->topic['topic_rating_total'] ) + $rating_id 
												), 'tid=' . $this->topic['tid'] );
		}

		$this->registry->output->redirectScreen( $this->lang->words['topic_rating_done'] , $this->settings['base_url'] . "showtopic={$this->topic['tid']}&amp;st=" . $this->request['st'] );
 	}
}
