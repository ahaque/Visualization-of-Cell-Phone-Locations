<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Bounces a user to the right post
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage  Forums 
 * @version		$Rev: 4948 $
 * @since		14th April 2004
 *
 * |   > Interesting Fact: I've had iTunes playing every Radiohead tune
 * |   > I own for about a week now. Thats a lot of repeats. Got some
 * |   > cool rare tracks though. Every album+rare+b sides = 6.7 hours
 * |   > music. Not bad. I need to get our more. No, you can't take the
 * |   > laptop with you - nerd.
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class  public_forums_forums_findpost extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[redirects]
	 */
	public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
		// Find a post
		// Don't really need to check perms 'cos topic
		// will do that for us. Woohoop
		//-----------------------------------------
		
		$pid = intval($this->request['pid']);
		
		if ( ! $pid )
		{
			$this->registry->getClass('output')->showError( 'findpost_missing_pid', 10331 );
		}
		
		//-----------------------------------------
		// Get topic...
		//-----------------------------------------
		
		$post = $this->DB->buildAndFetch( array( 'select'	=> 'p.*', 
												 'from'		=> array( 'posts' => 'p' ), 
												 'where'	=> 'p.pid=' . $pid,
												 'add_join'	=> array(
												 					array(
												 						'select'	=> 't.title_seo',
												 						'from'		=> array( 'topics' => 't' ),
												 						'where'		=> 't.tid=p.topic_id',
												 						'type'		=> 'left',
												 						)
												 					)
										)		);
		
		if ( ! $post['topic_id'] )
		{
			$this->registry->getClass('output')->showError( 'findpost_missing_topic', 10332 );
		}
		
		$cposts = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$post['topic_id']} AND pid <= {$pid}" ) );							
		
		if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
		{
			$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
		}
		else
		{
			$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
			$pages = ceil( $number);
		}
		
		$st = ($pages - 1) * $this->settings['display_max_posts'];
		$hl = $this->request['hl'] ? '&hl=' . trim( $this->request['hl'] ) : '';
		
		$url = $this->registry->output->buildSEOUrl( "showtopic=" . $post['topic_id'] . "&st={$st}&p={$pid}" . $hl . "&#entry" . $pid, 'public', $post['title_seo'], 'showtopic' );
		
		$this->registry->getClass('output')->silentRedirect( $url );
 	}
}