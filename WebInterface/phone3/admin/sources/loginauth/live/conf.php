<?php

$LOGIN_CONF = array();

/**
* Key file location
* This is the location where the Live Application Key XML file is located
*/
$LOGIN_CONF['key_file_location'] = IPS_ROOT_PATH . 'sources/loginauth/live/Application-Key.xml';

/**
* Login URL
* The location to send the user to for login purposes.  You should not need to changet his
*/
$LOGIN_CONF['login_url'] = 'http://login.live.com/wlogin.srf';