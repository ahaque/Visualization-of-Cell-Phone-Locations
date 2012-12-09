<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Core registered caches, redirect resets and bitwise settings
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		20th February 2002
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Which caches to load by default
 */
$_LOAD = array();

if ( $_GET['module'] == 'search' )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['bbcode']			= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['reputation_levels']	= 1;
}

if( $_GET['module'] == 'usercp' AND $_GET['area'] == 'ignoredusers' )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['reputation_levels']	= 1;
}


/* Never, ever remove or re-order these options!!
 * Feel free to add, though. :) */

$_BITWISE = array();
