<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Some required extensions to check for
 * Last Updated: $Date: 2009-06-30 12:16:04 -0400 (Tue, 30 Jun 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 4830 $
 *
 */
 
$INSTALLDATA = array(
	
array( 'prettyname'		=> "DOM XML Handling",
	   'extensionname'	=> "libxml2",
	   'helpurl'		=> "http://www.php.net/manual/en/dom.setup.php",
	   'testfor'		=> 'dom',
	   'nohault'		=> false ),

array( 'prettyname'		=> "GD Library",
	   'extensionname'	=> "gd",
	   'helpurl'		=> "http://www.php.net/manual/en/image.setup.php",
	   'testfor'		=> 'gd',
	   'nohault'		=> true ),
	
	
array( 'prettyname'		=> "Reflection Class",
	   'extensionname'	=> "Reflection",
	   'helpurl'		=> "http://uk2.php.net/manual/en/language.oop5.reflection.php",
	   'testfor'		=> 'Reflection',
	   'nohault'		=> false ),
);