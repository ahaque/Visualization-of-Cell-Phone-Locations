<?php
// Invision Power Board
// LDAP -- LDAP

$LOGIN_CONF = array();

/**
* LDAP SERVER LOCATION
* This is the location of the LDAP server, either by hostname or IP address.
*/
$LOGIN_CONF['ldap_server'] = '';

/**
* LDAP SERVER PORT
* If you require a specific port number, enter it here.
*/
$LOGIN_CONF['ldap_port'] = '';

/**
* LDAP SERVER USERNAME
* If your LDAP server requires a username, enter it here.
*/
$LOGIN_CONF['ldap_server_username'] = '';

/**
* LDAP SERVER PASSWORD
* If your LDAP server requires password authentication, enter it here.
*/
$LOGIN_CONF['ldap_server_password'] = '';

/**
* LDAP UID FIELD
* This is the field which contains the user's authenticate name.
*/
$LOGIN_CONF['ldap_uid_field'] = 'cn';

/**
* LDAP BASE DN
* The part of the world directory that is held on this
* server, which could be "o=My Company,c=US"
*
*/
$LOGIN_CONF['ldap_base_dn'] = '';

/**
* LDAP FILTER
* Specify an LDAP search filter string to return a subset of the search for
* the ldap_uid_field. The string might be used for restricting authentication
* to a subgroup of your organisation, e.g. 'ou=your_department'
*
* It might be useful to list here the operators that work:
*  =xyz   - matches exact value
*  =*xyz  - matches values ending xyz
*  =xyz*  - matches values beginning xyz
*  =*xyz* - matches values containing xyz
*  =*     - matches all values 
* Boolean operators for constructing complex search
*  &(term1)(term2)  - matches term1 AND term2
*  | (term1)(term2) - matches term1 OR term2
*  !(term1) - matches NOT term1 e.g. '!(ou=Student)'
* 
* leave this blank unless you are familiar with the contents of your
* LDAP server entries.
* 
*/
$LOGIN_CONF['ldap_filter'] = '';

/**
* LDAP SERVER VERSION
* Select the relevant major version number for your LDAP server.
* If unknown, try "3"
*
* OPTIONS: 2 = Version 2. 3 = Version 3.
*/
$LOGIN_CONF['ldap_server_version'] = 3;

/**
* LDAP_OPT_REFERRALS
* If using Win2K3 Active Directory, and using a root dn as your base dn,
* this must be set to 1.
*
* OPTIONS: 1 = yes, 0 = no
*/
$LOGIN_CONF['ldap_opt_referrals'] = 0;

/**
* LDAP USERNAME SUFFIX
* If you're using Active Directory, you may need to use an account suffix
* such as '@mydomain.local'. This is not always required.
*
*/
$LOGIN_CONF['ldap_username_suffix'] = '';

/**
* LDAP USER REQUIRES PASS?
* This relates to fetching a user's record from the LDAP.
* If the each user does not have a password switch this to 'no' or authentication will fail.
* Naturally, it's highly recommended that the LDAP admin chooses to use password authentication!
*
* OPTIONS: 1 = Yes. 0 = No
*/
$LOGIN_CONF['ldap_user_requires_pass'] = 1;

/**
* LDAP Display name field
* If populated, IPB asks LDAP for the value of this field to synchronize the member account
*
* Leave blank to have member manually enter in a display name
*/
$LOGIN_CONF['ldap_display_name']	= 'cn';

/**
* LDAP email address field
* If populated, IPB asks LDAP for the value of this field to synchronize the member account
*
* Leave blank to have member manually manage their email address
*/
$LOGIN_CONF['ldap_email_field']		= 'mail';

/**
* ADDITIONAL FIELDS
* Here you can make a list of additional fields (comma separated) besides dn, display name, mail, and uid
* that you would like to be returned from the server.
*/
$LOGIN_CONF['additional_fields'] = '';
