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
 * @subpackage	Core
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
	
'section=register' => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=register(&amp;|&|$)#i', 'register/$3' ),
						     'in'            => array( 'regex'   => "#/register/?(.+?)?$#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'register' ) ) ) ),
											
'section=rss'      => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=rss(&amp;|&)type=(\w+?)$#i', 'rss/$4/' ),
						     'in'            => array( 'regex'   => "#/rss/(\w+?)/$#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'rss' ), array( 'type', '$1' ) ) ) ),
											
'section=rss2'     => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=rss(&amp;|&)type=(\w+?)(&amp;|&)id=(\d+?)$#i', 'rss/$4/$6-#{__title__}/' ),
						     'in'            => array( 'regex'   => "#/rss/(\w+?)/(\d+?)-#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'rss' ), array( 'type', '$1' ), array( 'id', '$2' ) ) ) ),
											
#http://81.98.113.31/invisionboard3/index.php?app=core&module=global&section=rss&type=forums&id=1
);
	