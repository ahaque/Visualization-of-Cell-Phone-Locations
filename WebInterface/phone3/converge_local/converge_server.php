<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Converge Server Interface
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	IP.Converge
 * @since		2.1.0
 * @version		$Revision: 3887 $
 *
 */

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'api' );

/**
* Matches IP address of requesting API
* Set to 0 to not match with IP address
*/
define( 'CVG_IP_MATCH', 1 );

require_once( '../initdata.php' );

//===========================================================================
// MAIN PROGRAM
//===========================================================================

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );

$registry = ipsRegistry::instance();
$registry->init();

//--------------------------------
//  Initialize the FUNC
//--------------------------------

if ( ! ipsRegistry::$settings['ipconverge_enabled'] )
{
	@header( "Content-type: text/xml" );
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<methodResponse>
			   <fault>
			      <value>
			         <struct>
			            <member>
			               <name>faultCode</name>
			               <value>
			                  <int>1</int>
			                  </value>
			               </member>
			            <member>
			               <name>faultString</name>
			               <value>
			                  <string>IP.Converge is not enabled from your ACP Control Panel. Log into your IP.Board ACP and visit: Tools &amp; Settings -&gt; IP.Converge Configuration and update &quot;Enable IP.Converge&quot;</string>
			               </value>
			               </member>
			            </struct>
			         </value>
			            </fault>
			   </methodResponse>";
	exit();
}

//===========================================================================
// Define Service
//===========================================================================

require_once( DOC_IPS_ROOT_PATH   . 'converge_local/apis/server_functions.php' );
require_once( IPS_KERNEL_PATH . 'classApiServer.php' );

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$server     = new classApiServer();
$webservice = new Converge_Server( $registry );
$webservice->classApiServer =& $server;
$api        = $server->decodeRequest();

$server->addObjectMap( $webservice, 'UTF-8' );

//-----------------------------------------
// Process....
//-----------------------------------------

$server->getXmlRpc();



exit;