<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : OpenID method
 * Last Updated: $Date: 2009-02-04 15:03:59 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 3887 $
 *
 */

$config		= array(
					array(
							'title'			=> 'Location of key XML',
							'description'	=> "You must register your site as an application and receive an application ID to utilize Windows Live(tm) on your site.  See the <a href='http://msdn.microsoft.com/en-us/library/bb676626.aspx'>MSDN Library</a> for more information.  Note that it is recommended you store this file outside of your web root directory for security purposes.  See /admin/sources/loginauth/live/README.txt for more information.",
							'key'			=> 'key_file_location',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Control URL',
							'description'	=> "Location to send user to for login purposes.  You should not need to edit this URL.",
							'key'			=> 'control_url',
							'type'			=> 'string'
						),
					);