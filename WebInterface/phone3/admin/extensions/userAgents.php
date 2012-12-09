<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * User agent (browser and spider) mappings
 * Last Updated: $Date: 2009-08-30 23:34:46 -0400 (Sun, 30 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 5064 $
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$BROWSERS['amaya'] = array(
							  'b_title' => "Amaya",
							  'b_regex' => array( "amaya/([0-9.]{1,10})" => "1" )
							);
$BROWSERS['aol'] = array(
							  'b_title' => "AOL",
							  'b_regex' => array( "aol[ /\-]([0-9.]{1,10})" => "1" )
							);

$BROWSERS['blackberry'] = array(
							  'b_title' => "Blackberry",
							  'b_regex' => array( "blackberry(\d+?)/([0-9.]{1,10})" => "2" )
							);
													
$BROWSERS['camino'] = array(
							  'b_title' => "Camino",
							  'b_regex' => array( "camino/([0-9.+]{1,10})" => "1" )
							);
$BROWSERS['chimera'] = array(
							  'b_title' => "Chimera",
							  'b_regex' => array( "chimera/([0-9.+]{1,10})" => "1" )
							);
							
$BROWSERS['chrome'] = array(
							  'b_title' => "Google Chrome",
							  'b_regex' => array( "\s+?chrome/([0-9.]{1,10})" => "1" )
							);
							
$BROWSERS['curl'] = array(
							  'b_title' => "Curl",
							  'b_regex' => array( "curl[ /]([0-9.]{1,10})" => "1" )
							);

$BROWSERS['firebird'] = array(
							  'b_title' => "Firebird",
							  'b_regex' => array( "Firebird/([0-9.+]{1,10})" => "1" )
							);
$BROWSERS['firefox'] = array(
							  'b_title' => "Firefox",
							  'b_regex' => array( "Firefox/([0-9.+]{1,10})" => "1" )
							);
$BROWSERS['lotus'] = array(
							  'b_title' => "Lotus Notes",
							  'b_regex' => array( "Lotus[ \-]?Notes[ /]([0-9.]{1,10})" => "1" )
							);
$BROWSERS['konqueror'] = array(
							  'b_title' => "Konqueror",
							  'b_regex' => array( "konqueror/([0-9.]{1,10})" => "1" )
							);
$BROWSERS['lynx'] = array(
							  'b_title' => "Lynx",
							  'b_regex' => array( "lynx/([0-9a-z.]{1,10})" => "1" )
							);
$BROWSERS['maxthon'] = array(
							  'b_title' => "Maxthon",
							  'b_regex' => array( " Maxthon[\);]" => "" )
							);
$BROWSERS['omniweb'] = array(
							  'b_title' => "OmniWeb",
							  'b_regex' => array( "omniweb/[ a-z]?([0-9.]{1,10})$" => "1" )
							);
$BROWSERS['opera'] = array(
							  'b_title' => "Opera",
							  'b_regex' => array( "opera[ /]([0-9.]{1,10})" => "1" )
							);
							
$BROWSERS['safari'] = array(
							  'b_title' => "Safari",
							  'b_regex' => array( "version/([0-9.]{1,10})\s+?safari/([0-9.]{1,10})" => "1" )
							);
$BROWSERS['iphone'] = array(
							  'b_title' => "iPhone",
							  'b_regex' => array( "iphone;" => "0" )
							);
							
$BROWSERS['ipodtouch'] = array(
							  'b_title' => "iPod Touch",
							  'b_regex' => array( "ipod;" => "0" )
							);
							
$BROWSERS['webtv'] = array(
							  'b_title' => "Webtv",
							  'b_regex' => array( "webtv[ /]([0-9.]{1,10})" => "1" )
							);
$BROWSERS['explorer'] = array(
							  'b_title'    => "Explorer",
							  'b_regex'    => array( "\(compatible; MSIE[ /]([0-9.]{1,10})" => "1" ),
							  'b_position' => 5000,
							);
$BROWSERS['netscape'] = array(
							  'b_title' => "Netscape",
							  'b_regex' => array( "^mozilla/([0-4]\.[0-9.]{1,10})" => "1" )
							);
$BROWSERS['mozilla'] = array(
							  'b_title' => "Mozilla",
							  'b_regex' => array( "^mozilla/([5-9]\.[0-9a-z.]{1,10})" => "1" )
							);
							
//-----------------------------------------
// SEARCH ENGINES
//-----------------------------------------

$ENGINES['about'] = array(
							'b_title' => "About",
							'b_regex' => array( "Libby[_/ ]([0-9.]{1,10})" => "1" )
						  );
$ENGINES['alexa'] = array(
							'b_title' => "Alexa",
							'b_regex' => array( "^ia_archive" => "0" )
						  );
$ENGINES['altavista'] = array(
							'b_title' => "Altavista",
							'b_regex' => array( "Scooter[ /\-]*[a-z]*([0-9.]{1,10})" => "1" )
						  );
$ENGINES['ask'] = array(
							'b_title' => "Ask Jeeves",
							'b_regex' => array( "Ask[ \-]?Jeeves" => "0" )
						  );

$ENGINES['excite'] = array(
							'b_title' => "Excite",
							'b_regex' => array( "Architext[ \-]?Spider" => "0" )
						  );
$ENGINES['google'] = array(
							'b_title' => "Google",
							'b_regex' => array( "Googl(e|ebot)(-Image)?/([0-9.]{1,10})" => "\\\\3","Googl(e|ebot)(-Image)?/" => "" )
						  );
$ENGINES['infoseek'] = array(
							'b_title' => "Infoseek",
							'b_regex' => array( "SideWinder[ /]?([0-9a-z.]{1,10})" => "1","Infoseek" => "" )
						  );
$ENGINES['inktomi'] = array(
							'b_title' => "Inktomi",
							'b_regex' => array( "slurp@inktomi\.com" => "" )
						  );
$ENGINES['internetseer'] = array(
							'b_title' => "InternetSeer",
							'b_regex' => array( "^InternetSeer\.com" => "" )
						  );
$ENGINES['look'] = array(
							'b_title' => "Look",
							'b_regex' => array( "www\.look\.com" => "" )
						  );
$ENGINES['looksmart'] = array(
							'b_title' => "Looksmart",
							'b_regex' => array( "looksmart-sv-fw" => "" )
						  );
$ENGINES['lycos'] = array(
							'b_title' => "Lycos",
							'b_regex' => array( "Lycos_Spider_" => "" )
						  );

$ENGINES['msproxy'] = array(
							'b_title' => "MSProxy",
							'b_regex' => array( "MSProxy[ /]([0-9.]{1,10})" => "1" )
						  );

$ENGINES['msnbot'] = array(
							'b_title' => "MSN/Bing",
							'b_regex' => array( "msnbot[ /]([0-9.]{1,10})" => "1" )
						  );
											
$ENGINES['webcrawl'] = array(
							'b_title' => "WebCrawl",
							'b_regex' => array( "webcrawl\.net" => "" )
                          );
$ENGINES['websense'] = array(
							'b_title' => "Websense",
							'b_regex' => array( "(Sqworm|websense|Konqueror/3\.(0|1)(\-rc[1-6])?; i686 Linux; 2002[0-9]{4})" => "" )
						  );

$ENGINES['yahoo'] = array(
							'b_title' => "Yahoo",
							'b_regex' => array( "Yahoo(.*?)(Slurp|FeedSeeker)" => "" )
						  );