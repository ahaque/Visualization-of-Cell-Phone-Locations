<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Last Updated: $Date$
 *
 * @author 		$Author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Rev$
 *
 */
//-----------------------------------------
// Get stuff we need
//-----------------------------------------

define( 'IPB_THIS_SCRIPT', 'api' );
define( 'IPB_LOAD_SQL'   , 'queries' );

require_once( '../../initdata.php' );

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
$registry = ipsRegistry::instance();
$registry->init();

$id		= intval(ipsRegistry::$request['id']);
$member	= IPSMember::load( $id );
$avatar = IPSMember::buildAvatar( $member );

//-----------------------------------------
// Print avatar
//-----------------------------------------

print <<<HTML
<!DOCTYPE html 
	     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
	<head>
	</head>
	<body>{$avatar}</body>
	</html>
HTML;

exit;