<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : OpenID method
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
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
							'title'			=> 'File-Store location',
							'description'	=> "This is the location where the openid filestore should be placed.  Directory must be writable.",
							'key'			=> 'store_path',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Optional args to pull',
							'description'	=> "Based on OpenID specs, optional args to request.  See <a href='http://www.openidenabled.com/openid/simple-registration-extension' target='_blank'>OpenID Specs</a> for more information.  If nickname and email are available, account will be fully created with no user intervention required.",
							'key'			=> 'args_opt',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Required args to pull',
							'description'	=> "Based on OpenID specs, optional args to request.  See <a href='http://www.openidenabled.com/openid/simple-registration-extension' target='_blank'>OpenID Specs</a> for more information.  If nickname and email are available, account will be fully created with no user intervention required.",
							'key'			=> 'args_req',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Policy URL',
							'description'	=> 'This is a url to the policy on data you collect (optional)',
							'key'			=> 'openid_policy',
							'type'			=> 'string'
						),
					);