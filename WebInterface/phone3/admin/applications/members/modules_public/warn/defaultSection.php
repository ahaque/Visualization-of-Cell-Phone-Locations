<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Define the default warn section
 * Last Updated: $Date: 2008-12-22 18:10:25 -0500 (Mon, 22 Dec 2008) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
 * @since		20th February 2002
 * @version		$Rev: 3540 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Very simply returns the default section if one is not
* passed in the URL
*/

$DEFAULT_SECTION = 'warn';
