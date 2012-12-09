<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Sets up SEO templates
 * Last Updated: $Date: 2009-03-04 15:08:31 +0000 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		20th February 2002
 * @version		$Rev: 4136 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * SEO templates
 *
 * 'allowRedirect' is a flag to tell IP.Board whether to check the incoming link and if not formatted correctly, redirect the correct one
 *
 * OUT FORMAT REGEX:
 * First array element is a regex to run to see if we've a match for the URL
 * The second array element is the template to use the results of the parenthesis capture
 *
 * Special variable #{__title__} is replaced with the $title data passed to output->formatUrl( $url, $title)
 *
 * IMPORTANT: Remember that when these regex are used, the output has not been fully parsed so you will get:
 * showuser={$data['member_id']} NOT showuser=1 so do not try and match numerics only!
 *
 * IN FORMAT REGEX
 *
 * This allows the registry to piece back together a URL based on the template regex
 * So, for example: "/user/(\d+?)/", 'matches' => array(  array( 'showuser' => '$1' ) )tells IP.Board to populate 'showuser' with the result
 * of the parenthesis capture #1
 */
$_SEOTEMPLATES = array(
	
	'showannouncement'     => array( 'app'		     => 'forums',
									 'allowRedirect' => 1,
									 'out'           => array( '#showannouncement=(.+?)((?:&|&amp;)f=(.+?))?(&|$)#i', 'forum-$3/announcement-$1-#{__title__}/$4' ),
							  		 'in'            => array( 'regex'   => "#/forum-(\d+?)?/announcement-(\d+?)-#i",
													 		   'matches' => array( array( 'showannouncement', '$2' ), array( 'f', '$1' ) ) ) ),
													
	'showforum'     => array( 'app'		      => 'forums',
							  'allowRedirect' => 1,
							  'out'           => array( '#showforum=(.+?)(&|$)#i', 'forum/$1-#{__title__}/$2' ),
							  'in'            => array( 'regex'   => "#/forum/(\d+?)-#i",
													    'matches' => array( array( 'showforum', '$1' ) ) ) ),
					
	'showtopic'     => array( 'app'		      => 'forums',
							  'allowRedirect' => 1,
							  'out'           => array( '#showtopic=(.+?)(&|$)#i', 'topic/$1-#{__title__}/$2' ),
							  'in'            => array( 'regex'   => "#/topic/(\d+?)-#i",
												        'matches' => array( array( 'showtopic', '$1' ) ) ) ),
							
	'act=idx'       => array( 'app'		      => 'forums',
							  'allowRedirect' => 0,
							  'out'           => array( '#act=idx(&|$)#i', 'index' ),
							  'in'            => array( 'regex'   => "#/index$#i",
												        'matches' => array( array( 'act', 'idx' ) ) ) ),
);
	