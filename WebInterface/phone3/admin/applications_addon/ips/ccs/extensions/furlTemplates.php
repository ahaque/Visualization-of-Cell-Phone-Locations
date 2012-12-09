<?php
/**
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Sets up SEO templates
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $
 *
 */

$_SEOTEMPLATES = array(
						'page' => array(
										'app'			=> 'ccs',
										'allowRedirect'	=> 1,
										'out'			=> array( '#app=ccs(?:&amp;|&)module=pages(?:&amp;|&)section=pages(?:&amp;|&)(?:folder=(.*?)(?:&amp;|&))(?:id|page)=(.+?)(&|$)#i', 'page/$1/#{__title__}' ),
										'in'			=> array( 
																	'regex'		=> "#/page/(.*?)/(.+?)$#i",
																	'matches'	=> array( 
																							array( 'app'		, 'ccs' ),
																							array( 'module'		, 'pages' ),
																							array( 'section'	, 'pages' ),
																							array( 'folder'		, str_replace( '/page', '', '$1' ) ),
																							array( 'page'		, '$2' ),
																						)
																)	
									),
					);


