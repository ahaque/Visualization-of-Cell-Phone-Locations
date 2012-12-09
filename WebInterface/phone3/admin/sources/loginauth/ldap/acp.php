<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : LDAP method
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		2.1.0
 * @version		$Revision: 3887 $
 *
 */
 
$filter_description = <<<EOF
Specify an LDAP search filter string to return a subset of the search for the ldap_uid_field. The string might be used for restricting authentication to a subgroup of your organisation, e.g. 'ou=your_department'
<br /><br />
It might be useful to list here the operators that work:<br />
 =xyz   - matches exact value<br />
 =*xyz  - matches values ending xyz<br />
 =xyz*  - matches values beginning xyz<br />
 =*xyz* - matches values containing xyz<br />
 =*     - matches all values 
<br /><br />
Boolean operators for constructing complex search<br />
 &(term1)(term2)  - matches term1 AND term2<br />
 | (term1)(term2) - matches term1 OR term2<br />
 !(term1) - matches NOT term1 e.g. '!(ou=Student)'<br />
<br /><br />
Leave this blank unless you are familiar with the contents of your LDAP server entries.
EOF;

$config		= array(
					array(
							'title'			=> 'LDAP Server Location',
							'description'	=> 'This is the location of the LDAP server, either by hostname or IP address',
							'key'			=> 'ldap_server',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Server Port',
							'description'	=> 'LDAP server port, if required',
							'key'			=> 'ldap_port',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Server Username',
							'description'	=> 'If your LDAP server requires a username, enter it here',
							'key'			=> 'ldap_server_username',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Server Password',
							'description'	=> 'If your LDAP server requires authentication, enter the password here',
							'key'			=> 'ldap_server_password',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP UID Field',
							'description'	=> "This is the field which contains the user's authenticate name",
							'key'			=> 'ldap_uid_field',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Base DN',
							'description'	=> "The base DN on the LDAP server to search from (i.e. o=My Company,c=US)",
							'key'			=> 'ldap_base_dn',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Filter',
							'description'	=> $filter_description,
							'key'			=> 'ldap_filter',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Server Protocol Version',
							'description'	=> "Select the relevant major version number for your LDAP server (usually Version 3)",
							'key'			=> 'ldap_server_version',
							'type'			=> 'select',
							'options'		=> array( array( 2, 'Version 2' ), array( 3, 'Version 3' ) )
						),
					array(
							'title'			=> 'LDAP OPT Referrals',
							'description'	=> "If using Win2K3 Active Directory, this must be set to yes",
							'key'			=> 'ldap_opt_referrals',
							'type'			=> 'yesno',
						),
					array(
							'title'			=> 'LDAP Username Suffix',
							'description'	=> "If you require a suffix to the submitted username ( i.e. '@mycompany.com') enter it here" ,
							'key'			=> 'ldap_username_suffix',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'LDAP Password Required',
							'description'	=> "If each user does not have an individual password, turn this off" ,
							'key'			=> 'ldap_user_requires_pass',
							'type'			=> 'yesno',
						),
					array(
							'title'			=> 'LDAP Display Name Field',
							'description'	=> "If set, IPB attempts to retrieve this field value from LDAP to synchronize the member account" ,
							'key'			=> 'ldap_display_name',
							'type'			=> 'string',
						),
					array(
							'title'			=> 'LDAP Email Address Field',
							'description'	=> "If set, IPB attempts to retrieve this field value from LDAP to synchronize the member account" ,
							'key'			=> 'ldap_email_field',
							'type'			=> 'string',
						),
					array(
							'title'			=> 'LDAP Additional Fields',
							'description'	=> "Comma-separated list of additional fields to retrieve from LDAP.  You can then modify the auth.php file to store these values in IPB." ,
							'key'			=> 'additional_fields',
							'type'			=> 'string',
						),
					);