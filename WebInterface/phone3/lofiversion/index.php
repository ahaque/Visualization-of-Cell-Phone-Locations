<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Redirect old lofi search results to the new IP.Board 3 urls
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 3887 $
 *
 */

define( 'IPS_PUBLIC_SCRIPT', 'index.php' );

require_once( '../initdata.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );

/* INIT Registry */
$reg = ipsRegistry::instance();
$reg->init();

/* GET INPUT */
$url    = my_getenv('REQUEST_URI') ? my_getenv('REQUEST_URI') : my_getenv('PHP_SELF');
$qs     = my_getenv('QUERY_STRING');
$link   = 'act=idx';
$id     = 0;
$st     = 0;

$justKeepMe = str_replace( '.html', '', ( $qs ) ? $qs : str_replace( "/", "", strrchr( $url, "/" ) ) );

/* Got pages? */
if ( strstr( $justKeepMe, "-" ) )
{
	list( $_mainBit, $_startBit ) = explode( "-", $justKeepMe );
	
	$justKeepMe = $_mainBit;
	$st         = intval( $_startBit );
}

if ( strstr( $justKeepMe, 't' ) AND is_numeric( substr( $justKeepMe, 1 ) ) )
{
	$id = intval( substr( $justKeepMe, 1 ) );
	
	$link = 'showtopic=' . $id;
	
	if ( $st )
	{
		$link .= '&amp;st=' . $st;
	}
}
else if ( strstr( $justKeepMe, 'f' ) AND is_numeric( substr( $justKeepMe, 1 ) ) )
{
	$id  = intval( substr( $justKeepMe, 1 ) );
	
	$link = 'showforum=' . $id;
	
	if ( $st )
	{
		$link .= '&amp;st=' . $st;
	}
}

/* GO GADGET GO */
header("HTTP/1.0 301 Moved Permanently");
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $reg->output->formatUrl( $reg->output->buildUrl( $link, 'public' ) ) );

exit();