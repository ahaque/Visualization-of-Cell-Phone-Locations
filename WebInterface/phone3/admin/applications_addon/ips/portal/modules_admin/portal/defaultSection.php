<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal default section
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Portal
 * @version		$Rev: 4948 $ 
 **/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Very simply returns the default section if one is not
* passed in the URL
*/

$DEFAULT_SECTION = 'portal';