<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Editor Library: RTE (WYSIWYG) Class
 * Last Updated: $Date: 2009-08-20 18:20:40 -0400 (Thu, 20 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 5035 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_editor_module extends class_editor
{
	/**
	 * Clean up HTML on save
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $clean_on_save		= true;
	
	/**
	 * Allow HTML
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $allow_html			= false;
	
	/**
	 * Debug level
	 *
	 * @access	public
	 * @var		integer
	 */
	public $debug				= 0;
	
	/**
	 * Parsing array
	 *
	 * @access	public
	 * @var		array
	 */
	public $delimiters			= array( "'", '"' );
	
	/**
	 * Parsing array
	 *
	 * @access	public
	 * @var		array
	 */
	public $non_delimiters		= array( "=", ' ' );
	
	/**
	 * Start tags
	 *
	 * @access	public
	 * @var		array
	 */
	public $start_tags			= array();
	
	/**
	 * End tags
	 *
	 * @access	public
	 * @var		array
	 */
	public $end_tags				= array();
	
	/**
	 * Dunno, forgotten
	 *
	 * @access	private
	 * @var		boolean
	 * @deprecated	Seems this isn't called/checked anymore
	 */
	private $_called			= false;
	
	/**
	 * Process the content before showing it in the form
	 *
	 * @access	public
	 * @param	string		Raw text
	 * @return	string		Text ready for editor
	 */
	public function processBeforeForm( $t )
	{
		//-----------------------------------------
		// Remove comments
		//-----------------------------------------

		$t = preg_replace( "#\<\!\-\-(.+?)\-\-\>#is", "", $t );
		
		//-----------------------------------------
		// Trim
		//-----------------------------------------
		
		$t = trim($t);
						
		//-------------------------------
		// Convert all types of single quotes
		//-------------------------------
		
		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$t = str_replace(chr(145), chr(39), $t);
			$t = str_replace(chr(146), chr(39), $t);
		}
		
		$t = str_replace( "'", "&#39;", $t);
		
		//-------------------------------
		// Convert all types of double quotes
		//-------------------------------
		
		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{		
			$t = str_replace(chr(147), chr(34), $t);
			$t = str_replace(chr(148), chr(34), $t);
		}
		
		//-------------------------------
		// Replace carriage returns & line feeds
		// These used to replace with spaces, but that causes
		// additional spaces to keep adding up when submitting/previewing/editing
		// Need to monitor this to ensure it doesn't cause bugs as I'm not sure
		// why this replaced \r\n with spaces previously
		// @see http://forums./tracker/issue-17869-rtf-editor-problems/
		//-------------------------------

		if ( $this->memberData['userAgentKey'] == 'firefox' OR $this->memberData['userAgentKey'] == 'gecko' )
		{
			$t = str_ireplace( "<br>\r\n", "<br>", $t );
			$t = str_ireplace( "<br>\n", "<br>", $t );
			$t = str_ireplace( "<br>\r", "<br>", $t );
			$t = str_ireplace( "<br />\r\n", "<br />", $t );
			$t = str_ireplace( "<br />\n", "<br />", $t );
			$t = str_ireplace( "<br />\r", "<br />", $t );
			$t = preg_replace( "/((?:\r)?\n?(?:\s)+)/", " ", $t );

		}
		else
		{
			$t = str_replace(chr(10), "", $t);
			$t = str_replace(chr(13), "", $t);
		}
		
		//-----------------------------------------
		// Clean up code tags
		//-----------------------------------------

		$t = preg_replace_callback( "#\[(code)\](.+?)\[/code\]#is", array( $this, '_cleanCodeTag' ), $t );
		$t = preg_replace_callback( "#\[(sql)\](.+?)\[/sql\]#is", array( $this, '_cleanCodeTag' ), $t );
		$t = preg_replace_callback( "#\[(html)\](.+?)\[/html\]#is", array( $this, '_cleanCodeTag' ), $t );
		$t = preg_replace_callback( "#\[(xml)\](.+?)\[/xml\]#is", array( $this, '_cleanCodeTag' ), $t );
		$t = preg_replace_callback( "#\[(codebox)\](.+?)\[/codebox\]#is", array( $this, '_cleanCodeTag' ), $t );
		
		//-----------------------------------------
		// RTE and script don't mix because of other entities
		//-----------------------------------------
		
		$t	= str_replace( '&#60;', '&lt;', $t );
		$t	= str_replace( '&#6e;', '&gt;', $t );

		//-----------------------------------------
		// Clean up quote tags (remove many <br />s
		//-----------------------------------------
		
		$t = preg_replace( "#(\[quote([^\]]+?)\])(<br />){1,}#is", "\\1<br />", $t );
		
		//-----------------------------------------
		// Clean up the rest of the tags
		//-----------------------------------------
		
		$t = str_replace( array( '&lt;br&gt;', '&lt;br /&gt;' ), '<br />', IPSText::htmlspecialchars( $t ) );
		
		//-----------------------------------------
		// Fix up stuff
		//-----------------------------------------
		
		$t = str_replace( "&lt;#IMG_DIR#&gt;", "<#IMG_DIR#>", $t );
		$t = str_replace( "&lt;#EMO_DIR#&gt;", "<#EMO_DIR#>", $t );
		
		//-----------------------------------------
		// Convert multiple spaces
		//-----------------------------------------
		
		$t = str_replace( "  ", "&nbsp;&nbsp;", $t );
		
		return $t;
	}
	
	/**
	 * Clean up content inside the code tag
	 *
	 * @access	private
	 * @param	array		Matches from preg_replace_callback
	 * @return	string		Text ready for editor
	 */
	private function _cleanCodeTag( $matches )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$tag = $matches[1];
		$txt = $matches[2];
		
		//-----------------------------------------
		// Fix...
		//-----------------------------------------

		$txt = str_replace( "&lt;br&gt;"    , "\n"   , $txt );
		$txt = str_replace( "&lt;br /&gt;"  , "\n"   , $txt );
		$txt = str_replace( "<br>"          , "\n"   , $txt );
		$txt = str_replace( "<br />"        , "\n"   , $txt );
		$txt = str_replace( "<"             , "&lt;" , $txt );
		$txt = str_replace( ">"             , "&gt;" , $txt );
		$txt = str_replace( "&#60;"         , "&lt;" , $txt );
		$txt = str_replace( "&#62;"         , "&gt;" , $txt );

		return '[' . $tag . ']' . nl2br($txt) . '[/' . $tag . ']';
	}
	
	/**
	 * Process the content before passing off to the bbcode library
	 *
	 * @access	public
	 * @param	string		Form field name OR Raw text
	 * @return	string		Text ready for editor
	 */
	public function processAfterForm( $form_field )
	{
		$t	= isset( $_POST[ $form_field ] ) ? IPSText::stripslashes($_POST[ $form_field ]) : $form_field;
		$ot	= $t;

		//-----------------------------------------
		// Fix up spaces
		//-----------------------------------------
		
		$t = str_replace( '&nbsp;', ' ', $t );
		
		//-----------------------------------------
		// Gecko engine seems to put \r\n at edge
		// of iframe when wrapping? If so, add a 
		// space or it'll get weird later
		//-----------------------------------------
//		print $t;
//		print "<br><br><br>---------------------<br><br><br>";
//print nl2br(htmlspecialchars($t));
//print "<br><br><br>---------------------<br><br><br>";
		if ( $this->memberData['userAgentKey'] == 'firefox' OR $this->memberData['userAgentKey'] == 'gecko' )
		{
			$t = str_ireplace( "<br>\r\n", "<br>", $t );
			$t = str_ireplace( "<br>\n", "<br>", $t );
			$t = str_ireplace( "<br>\r", "<br>", $t );
			$t = str_ireplace( "<br />\r\n", "<br />", $t );
			$t = str_ireplace( "<br />\n", "<br />", $t );
			$t = str_ireplace( "<br />\r", "<br />", $t );
			$t = preg_replace( "/((?:\r)?\n?(?:\s)+)/", " ", $t );

		}
		else
		{
			$t = str_replace( "\r\n", "", $t );
		}
//print nl2br(htmlspecialchars($t));exit;
		//-----------------------------------------
		// RTE sends newlines as line break tags
		//-----------------------------------------
		
		$t = str_replace( "\n", "", $t );

		//-----------------------------------------
		// Clean up already encoded HTML
		//-----------------------------------------
		
		$t = str_replace( '&quot;', '"', $t );
		$t = str_replace( '&apos;', "'", $t );
		
		//-----------------------------------------
		// Fix up incorrectly nested urls / BBcode
		//-----------------------------------------
		
		$t = preg_replace( '#<a\s+?href=[\'"]([^>]+?)\[(.+?)[\'"](.+?)'.'>(.+?)\[\\2</a>#is', '<a href="\\1"\\3>\\4</a>[\\2', $t );
		
		//-----------------------------------------
		// Make URLs safe (prevent tag stripping)
		//-----------------------------------------
		
		$t = preg_replace_callback( '#<(a href|img src)=([\'"])([^>]+?)(\\2)#is', array( $this, '_unhtmlUrl' ), $t );

		//-----------------------------------------
		// WYSI-Weirdness #1: BR tags to \n
		//-----------------------------------------
	
		$t = str_ireplace( array( "<br>", "<br />" ), "\n", $t );
		
		$t = trim( $t );
		
		//-----------------------------------------
		// Before we can use strip_tags, we should
		// clean out any javascript and CSS
		//-----------------------------------------
		
		$t	= preg_replace( "/\<script(.*?)\>(.*?)\<\/script\>/", '', $t );
		$t	= preg_replace( "/\<style(.*?)\>(.*?)\<\/style\>/", '', $t );
		
		//-----------------------------------------
		// Remove tags we're not bothering with
		// with PHPs wonderful strip tags func
		//-----------------------------------------
		
		if ( ! $this->allow_html )
		{
			$t = strip_tags( $t, '<h1><h2><h3><h4><h5><h6><font><span><div><br><p><img><a><li><ol><ul><b><strong><em><i><u><s><strike><blockquote><sub><sup>' );
		}

		//-----------------------------------------
		// WYSI-Weirdness #2: named anchors
		//-----------------------------------------
		
		$t = preg_replace( "#<a\s+?name=.+?".">(.+?)</a>#is", "\\1", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #2.1: Empty a hrefs
		//-----------------------------------------
		
		$t = preg_replace( "#<a\s+?href([^>]+)></a>#is"         , ""   , $t );
		$t = preg_replace( "#<a\s+?href=(['\"])>\\1(.+?)</a>#is", "\\1", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #2.2: Double linked links
		//-----------------------------------------
		
		$t = preg_replace( "#href=[\"']\w+://(%27|'|\"|&quot;)(.+?)\\1[\"']#is", "href=\"\\2\"", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #3: Headline tags
		//-----------------------------------------
		
		$t = preg_replace( "#<(h[0-9])>(.+?)</\\1>#is", "\n[b]\\2[/b]\n", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #4: Font tags
		//-----------------------------------------
		
		$t = preg_replace( "#<font (color|size|face)=\"([a-zA-Z0-9\s\#\-]*?)\">(\s*)</font>#is", " ", $t );

		//-----------------------------------------
		// WYSI-Weirdness #5: Fix up smilies
		//-----------------------------------------
		
		$current = $this->memberData[ 'view_img' ];
		$this->member->setProperty( 'view_img', 0 );

		$t = IPSText::getTextClass( 'bbcode' )->memberViewImages( $t );

		$this->member->setProperty( 'view_img', $current );

		//-----------------------------------------
		// WYSI-Weirdness #6: Image tags
		//-----------------------------------------
		
		$t = preg_replace( "#<img.+?src=[\"'](.+?)[\"']([^>]+?)?".">#is", "[img]\\1[/img]", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #7: Linked URL tags
		//-----------------------------------------
		
		$t = preg_replace( "#\[url=(\"|'|&quot;)<a\s+?href=[\"'](.*)/??['\"]\\2/??</a>#is", "[url=\\1\\2", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #8: Make relative images full links
		//-----------------------------------------
		
		$t = preg_replace( "#\[img\](/)?style_(emoticons|images)#i", '[img]' . $this->settings['board_url'] . '/style_' . '\\2', $t );
		
		//-----------------------------------------
		// Now, recursively parse the other tags
		// to make sure we get the nested ones
		//-----------------------------------------
		
		$t = $this->_recurseAndParse( 'b'			, $t, "_parseSimpleTag", 'b' );
		$t = $this->_recurseAndParse( 'u'			, $t, "_parseSimpleTag", 'u' );
		$t = $this->_recurseAndParse( 'strong'		, $t, "_parseSimpleTag", 'b' );
		$t = $this->_recurseAndParse( 'i'			, $t, "_parseSimpleTag", 'i' );
		$t = $this->_recurseAndParse( 'em'			, $t, "_parseSimpleTag", 'i' );
		$t = $this->_recurseAndParse( 'strike'		, $t, "_parseSimpleTag", 's' );
		$t = $this->_recurseAndParse( 's'			, $t, "_parseSimpleTag", 's' );
		$t = $this->_recurseAndParse( 'blockquote'	, $t, "_parseSimpleTag", 'indent' );
		$t = $this->_recurseAndParse( 'sup' 		, $t, "_parseSimpleTag", 'sup' );
		$t = $this->_recurseAndParse( 'sub'			, $t, "_parseSimpleTag", 'sub' );

		//-----------------------------------------
		// More complex tags
		//-----------------------------------------
		
		$t = $this->_recurseAndParse( 'a'          , $t, "_parseAnchorTag" );
		$t = $this->_recurseAndParse( 'font'       , $t, "_parseFontTag" );
		$t = $this->_recurseAndParse( 'div'        , $t, "_parseDivTag" );
		$t = $this->_recurseAndParse( 'span'       , $t, "_parseSpanTag" );
		$t = $this->_recurseAndParse( 'p'          , $t, "_parseParagraphTag" );
		
		//-----------------------------------------
		// Lists
		//-----------------------------------------
		
		$t = $this->_recurseAndParse( 'ol'         , $t, "_parseListTag" );
		$t = $this->_recurseAndParse( 'ul'         , $t, "_parseListTag" );
		
		//-----------------------------------------
		// WYSI-Weirdness #9: Fix up para tags
		//-----------------------------------------
		
		$t = str_ireplace( array( "<p>", "<p />" ), "\n\n", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #10: Random junk
		//-----------------------------------------
		
		$t = str_ireplace( array( "<a>", "</a>", "</li>" ), "", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #11: Fix up list stuff
		//-----------------------------------------
		
		$t = preg_replace( '#<li>(.*)((?=<li>)|</li>)#is', '\\1', $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #12: Convert rest to HTML
		//-----------------------------------------
		
		$t = str_replace(  '&lt;' , '<', $t );
		$t = str_replace(  '&gt;' , '>', $t );
		$t = str_replace(  '&amp;', '&', $t );
		$t = preg_replace( '#&amp;(quot|lt|gt);#', '&\\1;', $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #13: Remove useless tags
		//-----------------------------------------
		
		while( preg_match( "#\[(url|img|b|u|i|s|email|list|indent|right|left|center)\]\[/\\1\]#is", $t ) )
		{
			$t = preg_replace( "#\[(url|img|b|u|i|s|email|list|indent|right|left|center)\]\[/\\1\]#is", "", $t );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #14: Opera crap
		//-----------------------------------------
		
		$t = preg_replace( "#\[(font|size|color)\]=[\"']([^\"']+?)[\"']\]\[/\\1\]#is", "", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #15: No domain in FF?
		//-----------------------------------------	
		
		$t = preg_replace( "#(http|https):\/\/index.php(.*?)#is", $this->settings['board_url'].'/index.php\\2', $t );	
		$t = preg_replace( "#\[url=['\"]index.php(.*?)[\"']#is", "[url=\"".$this->settings['board_url'].'/index.php\\1"', $t );	
		
		//-----------------------------------------
		// Now call the santize routine to make
		// html and nasties safe. VITAL!!
		//-----------------------------------------
		
		$t = $this->_cleanPost( $t );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( $this->debug )
		{
			print "<hr>";
			print nl2br(htmlspecialchars($ot));
			print "<hr>";
			print nl2br($t);
			print "<hr>";
			exit();
		}
		
		//-----------------------------------------
		// Done
		//-----------------------------------------
		
		return $t;
	}

	/**
	 * RTE: Parse List tag
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseListTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$list_type = trim( preg_replace( '#"?list-style-type:\s+?([\d\w\_\-]+);?"?#si', '\\1', $this->_getValueOfOption( 'style', $opening_tag ) ) );
		
		//-----------------------------------------
		// Set up a default...
		//-----------------------------------------
		
		if ( ! $list_type and $tag == 'ol' )
		{
			$list_type = 'decimal';
		}
		
		//-----------------------------------------
		// Tricky regex to clean all list items
		//-----------------------------------------

		$between_text = preg_replace('#<li>((.(?!</li))*)(?=</?ul|</?ol|\[list|<li|\[/list)#siU', '<li>\\1</li>', $between_text);
		
		$between_text = $this->_recurseAndParse( 'li', $between_text, "_parseListElement" );
		
		$allowed_types = array( 'upper-alpha' => 'A',
								'upper-roman' => 'I',
								'lower-alpha' => 'a',
								'lower-roman' => 'i',
								'decimal'     => '1' );
		
		if ( ! $allowed_types[ $list_type ] )
		{
			$open_tag = '[list]';
		}
		else
		{
			$open_tag = '[list=' . $allowed_types[ $list_type ] . ']';
		}
		
		return $open_tag . $this->_recurseAndParse( $tag, $between_text, '_parseListTag' ) . '[/list]';
	}

	/**
	 * RTE: Parse List Element tag
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseListElement( $tag, $between_text, $opening_tag, $parse_tag )
	{
		return '[*]' . rtrim( $between_text );
	}
	
	/**
	 * RTE: Parse paragraph tags
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseParagraphTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align and style (if any)
		//-----------------------------------------
		
		$align = $this->_getValueOfOption( 'align', $opening_tag );
		$style = $this->_getValueOfOption( 'style', $opening_tag );
		
		if ( $align == 'center' )
		{
			$start_tags .= '[center]';
			$end_tags   .= '[/center]';
		}
		else if ( $align == 'left' )
		{
			$start_tags .= '[left]';
			$end_tags   .= '[/left]';
		}
		else if ( $align == 'right' )
		{
			$start_tags .= '[right]';
			$end_tags   .= '[/right]';
		}
		else
		{
			# No align? Make paragraph
			$end_tags .= "\n";
		}
		
		$end_tags .= "\n";
		
		return $start_tags . $this->_recurseAndParse( 'p', $between_text, '_parseParagraphTag' ) . $end_tags;
	}
	
	/**
	 * RTE: Parse Span tag
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseSpanTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------

		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		return $start_tags . $this->_recurseAndParse( 'span', $between_text, '_parseSpanTag' ) . $end_tags;
	}
	
	/**
	 * RTE: Parse DIV tag
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseDivTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "<b><span style='color:red'>DIV FIRED</b></span><br />Start tags: {$this->start_tags}<br />End tags: {$this->end_tags}<br />Between text:<br />".htmlspecialchars($between_text)."<hr />";
		}
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align (if any)
		//-----------------------------------------
		
		$align = $this->_getValueOfOption( 'align', $opening_tag );
		
		if ( $align == 'center' )
		{
			$start_tags .= '[center]';
			$end_tags   .= '[/center]';
		}
		else if ( $align == 'left' )
		{
			$start_tags .= '[left]';
			$end_tags   .= '[/left]';
		}
		else if ( $align == 'right' )
		{
			$start_tags .= '[right]';
			$end_tags   .= '[/right]';
		}

		//-----------------------------------------
		// Get recursive text
		//-----------------------------------------
		
		$final = $this->_recurseAndParse( 'div', $between_text, '_parseDivTag' );
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "\n<hr><b style='color:green'>FINISHED</b><br/ >".$start_tags . $final . $end_tags."<hr>";
		}
		
		//-----------------------------------------
		// Now return
		//-----------------------------------------
		
		return $start_tags . $final . $end_tags;
	}
	
	/**
	 * RTE: Parse style attributes (color, font, size, b, i..etc)
	 *
	 * @access	private
	 * @param	string	Opening tag
	 * @param	string	Start tags
	 * @param	string	End tags
	 * @return	string	Converted text
	 */
	private function _parseStyles( $opening_tag, &$start_tags, &$end_tags )
	{
		$style_list = array(
							array('tag' => 'color' , 'rx' => '(?<!\w)color:\s*([^;]+);?'  , 'match' => 1),
							array('tag' => 'font'  , 'rx' => 'font-family:\s*([^;]+);?'   , 'match' => 1),
							array('tag' => 'size'  , 'rx' => 'font-size:\s*([\d]+);?'     , 'match' => 1),
							array('tag' => 'b'     , 'rx' => 'font-weight:\s*(bold);?'),
							array('tag' => 'i'     , 'rx' => 'font-style:\s*(italic);?'),
							array('tag' => 'u'     , 'rx' => 'text-decoration:\s*(underline);?'),
							array('tag' => 'left'  , 'rx' => 'text-align:\s*(left);?'),
							array('tag' => 'center', 'rx' => 'text-align:\s*(center);?'),
							array('tag' => 'right' , 'rx' => 'text-align:\s*(right);?'),
						  );
		
		//-----------------------------------------
		// get style option
		//-----------------------------------------
		
		$style = $this->_getValueOfOption( 'style', $opening_tag );

		//-----------------------------------------
		// Convert RGB to hex
		//-----------------------------------------
		
		$style = preg_replace_callback( '#(?<!\w)color:\s+?rgb\((\d+,\s+?\d+,\s+?\d+)\)(;?)#i', array( &$this, '_rgbToHex' ), $style );
		
		//-----------------------------------------
		// Pick through possible styles
		//-----------------------------------------
		
		foreach( $style_list as $data )
		{
			if ( preg_match( '#' . $data['rx'] . '#i', $style, $match ) )
			{
				if ( $data['match'] )
				{
					if ( $data['tag'] != 'size' )
					{
						$start_tags .= "[{$data['tag']}={$match[$data['match']]}]";
					}
					else
					{
						$start_tags .= "[{$data['tag']}=" . $this->convertRealsizeToBbsize($match[$data['match']]) ."]";
					}
				}
				else
				{
					$start_tags .= "[{$data['tag']}]";
				}
				
				$end_tags = "[/{$data['tag']}]" . $end_tags;
			}
		}
	}

	/**
	 * RTE: Parse FONT tag
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseFontTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$font_tags  = array( 'font' => 'face', 'size' => 'size', 'color' => 'color' );
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for attributes
		//-----------------------------------------
		
		foreach( $font_tags as $bbcode => $string )
		{
			$option = $this->_getValueOfOption( $string, $opening_tag );
			
			if ( $option )
			{
				$start_tags .= "[{$bbcode}=\"{$option}\"]";
				$end_tags    = "[/{$bbcode}]" . $end_tags;
				
				if ( $this->debug == 2 )
				{
					print "<br />Got bbcode=$bbcode / opening_tag=$opening_tag";
					print "<br />- Adding [$bbcode=\"$option\"] [/$bbcode]";
					print "<br />-- start tags now: {$start_tags}";
					print "<br />-- end tags now: {$end_tags}";
				}
			}
		}
		
		//-----------------------------------------
		// Now check for inline style moz may have
		// added
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		return $start_tags . $this->_recurseAndParse( 'font', $between_text, '_parseFontTag' ) . $end_tags;
	}

	/**
	 * RTE: Simple tags
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseSimpleTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		if ( ! $parse_tag )
		{
			$parse_tag = $tag;
		}
		
		return "[{$parse_tag}]" . $this->_recurseAndParse( $tag, $between_text, '_parseSimpleTag', $parse_tag ) . "[/{$parse_tag}]";
	}

	/**
	 * RTE: Parse A HREF tag
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _parseAnchorTag( $tag, $between_text, $opening_tag, $parse_tag='' )
	{
		$mytag = 'url';
		$href  = $this->_getValueOfOption( 'href', $opening_tag );
		
		$href  = str_replace( '<', '&lt;', $href );
		$href  = str_replace( '>', '&gt;', $href );
		$href  = str_replace( ' ', '%20' , $href );
		
		if ( preg_match( '#^mailto\:#is', $href ) )
		{
			$mytag = 'email';
			$href  = str_replace( "mailto:", "", $href );
		}
		
		return "[{$mytag}=\"{$href}\"]" . $this->_recurseAndParse( $tag, $between_text, '_parseAnchorTag', $parse_tag ) . "[/{$mytag}]";
	}

	/**
	 * RTE: Recursively parse tags
	 *
	 * @access	private
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Callback Function
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	private function _recurseAndParse( $tag, $text, $function, $parse_tag='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$tag              = strtolower($tag);
		$open_tag         = "<" . $tag;
		$open_tag_len     = strlen($open_tag);
		$close_tag        = "</" . $tag . ">";
		$close_tag_len    = strlen($close_tag);
		$start_search_pos = 0;
		$tag_begin_loc    = 1;
		
		//-----------------------------------------
		// Start the loop
		//-----------------------------------------
		
		while ( $tag_begin_loc !== FALSE )
		{
			$lowtext       = strtolower($text);
			$tag_begin_loc = @strpos( $lowtext, $open_tag, $start_search_pos );
			$lentext       = strlen($text);
			$quoted        = '';
			$got           = FALSE;
			$tag_end_loc   = FALSE;
			
			//-----------------------------------------
			// No opening tag? Break
			//-----------------------------------------
		
			if ( $tag_begin_loc === FALSE )
			{
				break;
			}
			
			//-----------------------------------------
			// Pick through text looking for delims
			//-----------------------------------------
			
			for ( $end_opt = $tag_begin_loc; $end_opt <= $lentext; $end_opt++ )
			{
				$chr = $text{$end_opt};
				
				//-----------------------------------------
				// We're now in a quote
				//-----------------------------------------
				
				if ( ( in_array( $chr, $this->delimiters ) ) AND $quoted == '' )
				{
					$quoted = $chr;
				}
				
				//-----------------------------------------
				// We're not in a quote any more
				//-----------------------------------------
				
				else if ( ( in_array( $chr, $this->delimiters ) ) AND $quoted == $chr )
				{
					$quoted = '';
				}
				
				//-----------------------------------------
				// Found the closing bracket of the open tag
				//-----------------------------------------
				
				else if ( $chr == '>' AND ! $quoted )
				{
					$got = TRUE;
					break;
				}
				
				else if ( ( in_array( $chr, $this->non_delimiters ) ) AND ! $tag_end_loc )
				{
					$tag_end_loc = $end_opt;
				}
			}
			
			//-----------------------------------------
			// Not got the complete tag?
			//-----------------------------------------
			
			if ( ! $got )
			{
				break;
			}
			
			//-----------------------------------------
			// Not got a tag end location?
			//-----------------------------------------
			
			if ( ! $tag_end_loc )
			{
				$tag_end_loc = $end_opt;
			}
			
			//-----------------------------------------
			// Extract tag options...
			//-----------------------------------------
			
			$tag_opts        = substr( $text   , $tag_begin_loc + $open_tag_len, $end_opt - ($tag_begin_loc + $open_tag_len) );
			$actual_tag_name = substr( $lowtext, $tag_begin_loc + 1            , ( $tag_end_loc - $tag_begin_loc ) - 1 );
			
			//-----------------------------------------
			// Check against actual tag name...
			//-----------------------------------------
			
			if ( $actual_tag_name != $tag )
			{
				$start_search_pos = $end_opt;
				continue;
			}
	
			//-----------------------------------------
			// Now find the end tag location
			//-----------------------------------------
			
			$tag_end_loc = strpos( $lowtext, $close_tag, $end_opt );
			
			//-----------------------------------------
			// Not got one? Break!
			//-----------------------------------------
			
			if ( $tag_end_loc === FALSE )
			{
				break;
			}
	
			//-----------------------------------------
			// Check for nested tags
			//-----------------------------------------
			
			$nest_open_pos = strpos($lowtext, $open_tag, $end_opt);
			
			while ( $nest_open_pos !== FALSE AND $tag_end_loc !== FALSE )
			{
				//-----------------------------------------
				// It's not actually nested
				//-----------------------------------------
				
				if ( $nest_open_pos > $tag_end_loc )
				{
					break;
				}
				
				if ( $this->debug == 2)
				{
					print "\n\n<hr>( ".htmlspecialchars($open_tag)." ) NEST FOUND</hr>\n\n";
				}
				
				$tag_end_loc   = strpos($lowtext, $close_tag, $tag_end_loc   + $close_tag_len);
				$nest_open_pos = strpos($lowtext, $open_tag , $nest_open_pos + $open_tag_len );
			}
			
			//-----------------------------------------
			// Make sure we have an end location
			//-----------------------------------------
			
			if ( $tag_end_loc === FALSE )
			{
				$start_search_pos = $end_opt;
				continue;
			}
	
			$this_text_begin  = $end_opt + 1;
			$between_text     = substr($text, $this_text_begin, $tag_end_loc - $this_text_begin);
			$offset           = $tag_end_loc + $close_tag_len - $tag_begin_loc;
			
			//-----------------------------------------
			// Pass to function
			//-----------------------------------------
			
			$final_text       = $this->$function($tag, $between_text, $tag_opts, $parse_tag);
			
			//-----------------------------------------
			// #DEBUG
			//-----------------------------------------
			
			if ( $this->debug == 2)
			{
				print "<hr><b>REPLACED {$function}($tag, ..., $tag_opts):</b><br />".htmlspecialchars(substr($text, $tag_begin_loc, $offset))."<br /><b>WITH:</b><br />".htmlspecialchars($final_text)."<hr>NEXT ITERATION";
			}
				
			//-----------------------------------------
			// Swap text
			//-----------------------------------------
			
			$text             = substr_replace($text, $final_text, $tag_begin_loc, $offset);
			$start_search_pos = $tag_begin_loc + strlen($final_text);
		} 
	
		return $text;
	}

	/**
	 * RTE: Extract option HTML
	 *
	 * @access	private
	 * @param	string	Option
	 * @param	string	Text
	 * @return	string	Converted text
	 */
	private function _getValueOfOption( $option, $text )
	{
		if( $option == 'face' )
		{
			// Bad font face, bad
			preg_match( "#{$option}(\s+?)?\=(\s+?)?[\"']?(.+?)([\"']|$|color|size|>)#is", $text, $matches );
		}
		else
		{
			preg_match( "#{$option}(\s*?)?\=(\s*?)?[\"']?(.+?)([\"']|$|\s|>)#is", $text, $matches );
		}

		return isset($matches[3]) ? trim( $matches[3] ) : '';
	}

	/**
	 * unhtml url: Removes < and >
	 *
	 * @access	private
	 * @param	array 		Matches from preg_replace_callback
	 * @return	string		Converted text
	 */
	private function _unhtmlUrl( $matches=array() )
	{
		$url  = stripslashes( $matches[3] );
		$type = stripslashes( $matches[1] ? $matches[1] : 'a href' );
		
		$url  = str_replace( '<', '&lt;', $url );
		$url  = str_replace( '>', '&gt;', $url );
		$url  = str_replace( ' ', '%20' , $url );
		
		return '<' . $type . '="' . $url . '"';
	}

	/**
	 * Converts color:rgb(x,x,x) to color:#xxxxxx
	 *
	 * @access	private
	 * @param	string	rgb contents: x,x,x
	 * @param	string	regex end
	 * @return	string	Converted text
	 */
	private function _rgbToHex($matches)
	{
		$t  = $matches[1];
		$t2 = $matches[2];
		
		$tmp = array_map( "trim", explode( ",", $t ) );
		return 'color: ' . sprintf( "#%02X%02X%02X" . $t2, intval($tmp[0]), intval($tmp[1]), intval($tmp[2]) );
	}
}