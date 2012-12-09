<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal plugin: portal
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Portal
 * @since		1st march 2002
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* This file must be named {file_name_minus_php}-cfg.php
*
* Please see each variable for more information
* $PORTAL_CONFIG is OK for each file, do not change
* this array name.
*/

$PORTAL_CONFIG = array();

/**
* Main plug in title
*
*/
$PORTAL_CONFIG['pc_title'] = 'IP.Portal Plugins';

/**
* Plug in mini description
*
*/
$PORTAL_CONFIG['pc_desc']  = "Site Navigation &amp; Affiliates Block";

/**
* Keyword for settings. This is the keyword
* entered into ibf_conf_settings_titles -> conf_title_keyword
* Can be left blank.
* PLEASE stick to the naming convention when entering a setting
* keyword: portal_{file_name_minus_php} This will prevent
* other keyword clashes. Likewise, when creating settings, choose
* NOT to cache them (they will be loaded at run time) and always
* prefix with {file_name_minus_php}_setting_key - for example
* If you had a setting called "export_forums" then please name it
* "recent_topics_export_forums". This will be available in
* $this->settings['recent_topics_export_forums'] in the
* main module.
*/
$PORTAL_CONFIG['pc_settings_keyword'] = "ipbportal";

/**
* Exportable tags key must be in the naming format of:
* {file_name_minus_php}-tag. The value *MUST* be the function
* which it corresponds to. For example:
* 'recent_topics_last_x' => 'recent_topics_last_x'
* The portal will look for function 'recent_topics_last_x' in
* module "sources/portal_plugins/recent_topics.php" when it parses
* the tag <!--::recent_topics_last_x::-->
*
* @param array[ TAG ] = array( FUNCTION NAME, DESCRIPTION );
*/
$PORTAL_CONFIG['pc_exportable_tags']['portal_sitenav'] = array( 'portal_sitenav', 'Shows a site navigation block' );
$PORTAL_CONFIG['pc_exportable_tags']['portal_affiliates'] = array( 'portal_affiliates', 'Shows an affiliates block' );
