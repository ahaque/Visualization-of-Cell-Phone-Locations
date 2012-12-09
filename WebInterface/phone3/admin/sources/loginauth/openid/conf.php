<?php

$LOGIN_CONF = array();

/**
* File-Store location
* This is the location where the openid filestore should be placed
*/
$LOGIN_CONF['store_path'] = DOC_IPS_ROOT_PATH . 'cache/openid';


/**
* Option args to pull
* Based on OpenID specs, optional args to request
* http://www.openidenabled.com/openid/simple-registration-extension
*/

$LOGIN_CONF['args_opt']	= 'nickname,dob';//,gender';	= removing gender until core is fixed

/**
* Required args to pull
* Based on OpenID specs, required args to request
* http://www.openidenabled.com/openid/simple-registration-extension
*/

$LOGIN_CONF['args_req']	= 'email';

/**
* Policy URL
* This is a url to the policy on data you collect (optional)
* 
*/

$LOGIN_CONF['openid_policy']	= '';