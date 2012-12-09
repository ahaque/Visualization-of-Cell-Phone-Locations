<?php
// Invision Power Board
// EXTERNEL DB
// THIS ONE GOOD FOR PHPBB 2 BOARDS

$LOGIN_CONF = array();


/**
* REMOTE DB SERVER
* localhost is usually good
*/
$LOGIN_CONF['REMOTE_DB_SERVER']   = 'localhost';

/**
* REMOTE DB PORT
* leave  blank if you're not sure.
*/
$LOGIN_CONF['REMOTE_DB_PORT']     = '';

/**
* REMOTE DB DATABASE NAME
* This is the name of the database you want to
* authorize against
*/
$LOGIN_CONF['REMOTE_DB_DATABASE'] = 'testphpbb';

/**
* REMOTE DB USER
* This is the MySQL username for the database
* you want to authorize against
*/
$LOGIN_CONF['REMOTE_DB_USER']     = 'root';

/**
* REMOTE DB PASSWORD
* This is the MySQL password for the database
* you want to authorize against
*/
$LOGIN_CONF['REMOTE_DB_PASS']     = '--pass--';

/**
* REMOTE DB TABLE NAME
* This is the table name which holds your external members
*/
$LOGIN_CONF['REMOTE_TABLE_NAME']  = 'phpbb_users';

/**
* REMOTE DB TABLE PREFIX
* This is the table prefix for your remote DB
*/
$LOGIN_CONF['REMOTE_TABLE_PREFIX']  = '';

/**
* REMOTE DB FIELD NAME
* The name of the name field in the member's table
*/
$LOGIN_CONF['REMOTE_FIELD_NAME']  = 'username';

/**
* REMOTE DB PASSWORD FIELD
* The name of the password field in the member's table
*/
$LOGIN_CONF['REMOTE_FIELD_PASS']  = 'user_password';

/**
* REMOTE DB EXTRA QUERY
* Any extra query to run (eg: AND status='active')
*/
$LOGIN_CONF['REMOTE_EXTRA_QUERY'] = ' AND user_active=1';

/**
* Password Hasing Scheme
* Can be one of md5, sha1, or none (for plaintext)
* If passwords stored hashed in any other method, you will need
* to modify auth.php to handle the password appropriately
*/
$LOGIN_CONF['REMOTE_PASSWORD_SCHEME'] = 'md5';
