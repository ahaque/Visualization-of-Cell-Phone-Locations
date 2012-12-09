<?php
/**
 * Invision Power Services
 * Determine which caches to load, and how to recache them
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */

$_LOAD = array( 'bbcode' => 1, 'emoticons' => 1, 'profilefields' => 1, 'ranks' => 1, 'badwords' => 1, 'reputation_levels' => 1 );




/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET = array();

