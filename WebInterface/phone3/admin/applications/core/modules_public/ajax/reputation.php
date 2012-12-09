<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Reputation
 * Last Updated: $Date: 2009-07-30 19:17:45 -0400 (Thu, 30 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4955 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_reputation extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'add_rating':
				$this->doRating();
			break;
		}
		
		/* Output */
		$this->returnHtml( $this->output );		
	}
	
	/**
	 * Adds a rating to the index
	 *
	 * @access	public
	 * @return	void
	 */
	public function doRating()
	{
		/* INIT */
		$app     = $this->request['app_rate'];
		$type    = $this->request['type'];
		$type_id = intval( $this->request['type_id'] );
		$rating  = intval( $this->request['rating'] );
		
		/* Check */
		if( ! $app || ! $type || ! $type_id || ! $rating )
		{
			$this->returnString( $this->lang->words['ajax_incomplete_data'] );
		}
				
		/* Get the rep library */
		require_once( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php' );
		$repCache = new classReputationCache();
		
		/* Add the rating */
		if( $repCache->addRate( $type, $type_id, $rating, '', 0, $app ) === false )
		{
			$this->returnString( $repCache->error_message );	
		}
		else
		{
			$this->returnString( 'done' );
		}
	}
}