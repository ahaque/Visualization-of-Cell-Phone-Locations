<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * IN_DEV Remapping.
 * Last Updated: $Date: 2009-01-05 22:21:54 +0000 (Mon, 05 Jan 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		3.0
 * @version		$Revision: 3572 $
 *
 */
 
/**
* This file is used to map skin IDs to a "master skin" directory for offline editing
* It is only used when IN_DEV is on.
* You are responsible for creating any directory and setting appropriate permission for IP.Board to write into it
* You can then import/export the skin in master format from the ACP -> Look & Feel (Menu button on appropriate skin row that will show when you have added it to the $REMAP array below
*/

/*
	To be able to edit a skin outside of the ACP, do this.

	1) Create a new skin set within the ACP. It can be a 'root' skin or a 'child'. It doesn't matter. Make sure you enter a 'skin_key' which must be unique.
	2) Put your board in IN_DEV mode by editing the constant in conf_global.php
	3) Create a new master skin directory in /cache/skin_cache. It must be something unique, like 'master_myskin' for example.
	4) Add your new 'skin_key' to the 'templates' array below.
	5) Back into your ACP, go to the list of skin sets. Click on the menu icon for your new skin set and choose: 'EXPORT Templates into 'master' directory...'.

	You should now be able to edit those files as you browse the board without the ACP. When you are done, simply choose 'IMPORT Templates..'.
*/

$REMAP = array(
	# This is the skin IPB uses when IN_DEV is switched on. Change the ID to
	# your own skin if desired.
	'inDevDefault' => 0,
	
	# This defines which skins are exported for the installer
	'export'       => array(  0 => 0,
							  1 => 'xmlskin',
							  2 => 'lofi' ),
	
	# Templates array. setID OR setKey => styleDir. styleDir must be created in 'cache/skin_cache' first
	'templates'    => array(
							 0          => 'master_skin',
							 'xmlskin'  => 'master_skin_xml',
							 'lofi'     => 'master_skin_lofi',
						   ),
						
	# CSS. setID OR setKey => cssDir. cssDir must be created in 'public/style_css' first
	'css'		   => array(
							0         => 'master_css',
							'xmlskin' => 'master_css_xml',
							'lofi'    => 'master_css_lofi',
							
							)
);