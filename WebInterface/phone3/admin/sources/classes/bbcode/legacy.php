<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * BBCode parsing core - legacy routine
 * Last Updated: $Date: 2009-07-06 21:23:35 -0400 (Mon, 06 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 4843 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_bbcode_legacy extends class_bbcode_core
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
	}

	/**
	 * Parse before saving (not used in legacy parser)
	 *
	 * @access	public
	 * @param	string		Text to parse
	 * @return	string		Parsed text
	 */
	public function preDbParse( $txt="" )
	{
		return $txt;
	}
	
	/**
	 * Parse before displaying (not used in legacy parser)
	 *
	 * @access	public
	 * @param	string		Text to parse
	 * @return	string		Parsed text
	 */
	public function preDisplayParse( $txt="" )
	{
		return $txt;
	}

	/**
	 * This function processes the text before showing for editing, etc
	 * Used for rebuilding after upgrade to 3.0
	 *
	 * @access	public
	 * @param	string		Raw text
	 * @return	string		Converted text
	 */
	public function preEditParse( $txt="" )
	{
		//-----------------------------------------
		// Before we start, strip newlines or we'll
		// end up duplicating them
		//-----------------------------------------
		
		$txt	= str_replace( "\n", "", $txt );
		$txt	= str_replace( "\r", "", $txt );
		
		//-----------------------------------------
		// Clean up BR tags
		//-----------------------------------------
		
		if ( !$this->parse_html OR $this->parse_nl2br )
		{
			$txt = str_replace( "<br>"  , "\n", $txt );
			$txt = str_replace( "<br />", "\n", $txt );
		}
		
		# Make EMO_DIR safe so the ^> regex works
		$txt = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $txt );
		
		# New emo
		$txt = preg_replace( "#(\s)?<([^>]+?)emoid=\"(.+?)\"([^>]*?)".">(\s)?#is", "\\1\\3\\5", $txt );
		
		# And convert it back again...
		$txt = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $txt );
		
		# Legacy
		$txt = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $txt );
		
		# New (3.0)
		$txt = $this->unconvertSmilies( $txt );
		
		//-----------------------------------------
		// Clean up nbsp
		//-----------------------------------------
		
		$txt = str_replace( '&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $txt );
		$txt = str_replace( '&nbsp;&nbsp;'            , "  ", $txt );

		if ( $this->parse_bbcode )
		{
			//-----------------------------------------
			// Custom bbcode...
			//-----------------------------------------
			
			$txt = preg_replace( "#<acronym title=[\"'](.+?)['\"]>(.+?)</acronym>#is", "[acronym=\"\\1\"]\\2[/acronym]", $txt );
			$txt = preg_replace( "#<a href=[\"']index\.php\?automodule=blog(&|&amp;)showentry=(.+?)['\"]>(.+?)</a>#is", "[entry=\"\\2\"]\\3[/entry]", $txt );
			$txt = preg_replace( "#<a href=[\"']index\.php\?automodule=blog(&|&amp;)blogid=(.+?)['\"]>(.+?)</a>#is", "[blog=\"\\2\"]\\3[/blog]", $txt );
			$txt = preg_replace( "#<a href=[\"']index\.php\?act=findpost(&|&amp;)pid=(.+?)['\"]>(.+?)</a>#is", "[post=\"\\2\"]\\3[/post]", $txt );
			$txt = preg_replace( "#<a href=[\"']index\.php\?showtopic=(.+?)['\"]>(.+?)</a>#is", "[topic=\"\\1\"]\\2[/topic]", $txt );
			$txt = preg_replace( "#<a href=[\"'](.*?)index\.php\?act=findpost(&|&amp;)pid=(.+?)['\"]><\{POST_SNAPBACK\}></a>#is", "[snapback]\\3[/snapback]", $txt );
			$txt = preg_replace( "#<div class=[\"']codetop['\"]>(.+?)</div><div class=[\"']codemain['\"] style=[\"']height:200px;white\-space:pre;overflow:auto['\"]>(.+?)</div>#is", "[codebox]\\2[/codebox]", $txt );
			$txt = preg_replace( "#<!--blog\.extract\.start-->(.+?)<!--blog\.extract\.end-->#is", "[extract]\\1[/extract]", $txt );
			$txt = preg_replace( "#<span style=[\"']color:\#000000;background:\#000000['\"]>(.+?)</span>#is", "[spoiler]\\1[/spoiler]", $txt );
			
			//-----------------------------------------
			// SQL
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#is", array( &$this, 'unconvert_sql'), $txt );
			
			//-----------------------------------------
			// HTML
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#is", array( &$this, 'unconvert_htm'), $txt );
			
			//-----------------------------------------
			// Images / Flash
			//-----------------------------------------
		
			$txt = preg_replace_callback( "#<!--Flash (.+?)-->.+?<!--End Flash-->#", array( &$this, 'unconvert_flash'), $txt );
			$txt = preg_replace( "#<img(?:.+?)src=[\"'](\S+?)['\"][^>]+?>#is"           , "\[img\]\\1\[/img\]"            , $txt );
		
			//-----------------------------------------
			// Email, URLs
			//-----------------------------------------
			
			$txt = preg_replace( "#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#s"                                   , "\[email=\\1\]\\2\[/email\]"   , $txt );
			$txt = preg_replace( "#<a href=[\"'](http://|https://|ftp://|news://)?(\S+?)['\"].*?".">(.+?)</a>#s" , "\[url=\"\\1\\2\"\]\\3\[/url\]"  , $txt );

			//-----------------------------------------
			// Quote
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                        , '[quote]'         , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+([^>]+?)-->(.+?)<!--QuoteEBegin-->#", "[quote name='\\1' date='\\2']" , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+-->(.+?)<!--QuoteEBegin-->#"        , "[quote name='\\1']"     , $txt );
			$txt = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                            , '[/quote]'        , $txt );
			
			//-----------------------------------------
			// Super old quotes
			//-----------------------------------------
			
			$txt = preg_replace( "#\[quote=(.+?),(.+?)\]#i"											 , "[quote name='\\1' date='\\2']", $txt );
			
			//-----------------------------------------
			// URL Inside Quote
			//-----------------------------------------

			$txt = preg_replace( "#\[quote=(.*?)\[url(.*?)\](.+?)\[\/url\]\]#i", "[quote=\\1\\3]", str_replace( "\\", "", $txt ) );
			
			//-----------------------------------------
			// New quote
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#<!--quoteo([^>]+?)?-->(.+?)<!--quotec-->#si", array( &$this, '_parse_new_quote'), $txt );
			
			//-----------------------------------------
			// Ident => Block quote
			//-----------------------------------------
			
			while( preg_match( "#<blockquote>(.+?)</blockquote>#is" , $txt ) )
			{
				$txt = preg_replace( "#<blockquote>(.+?)</blockquote>#is"  , "[indent]\\1[/indent]", $txt );
			}
			
			//-----------------------------------------
			// CODE
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", '[code]' , $txt );
			$txt = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", '[/code]', $txt );
			
			//-----------------------------------------
			// left, right, center
			//-----------------------------------------

			$txt = preg_replace( "#<div align=[\"'](left|right|center)['\"]>(.+?)</div>#is"  , "[\\1]\\2[/\\1]", $txt );
			
			//-----------------------------------------
			// Start off with the easy stuff
			//-----------------------------------------
			
			$txt = $this->parse_simple_tag_recursively( 'b'     , 'b'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'i'     , 'i'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'u'     , 'u'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'strike', 's'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'sub'   , 'sub', 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'sup'   , 'sup', 0, $txt );
			
			//-----------------------------------------
			// List headache
			//-----------------------------------------
			
			$txt = preg_replace( "#(\n){0,1}<ul>#" , "\\1\[list\]"  , $txt );
			$txt = preg_replace( "#(\n){0,1}<ol>#" , "\\1\[list=1\]"  , $txt );
			$txt = preg_replace( "#(\n){0,1}<ol type=[\"'](a|A|i|I|1)[\"']>#" , "\\1\[list=\\2\]\n"  , $txt );
			$txt = preg_replace( "#(\n){0,1}<li>#" , "\n\[*\]"     , $txt );
			$txt = preg_replace( "#(\n){0,1}</ul>(\n){0,1}#", "\n\[/list\]\\2" , $txt );
			$txt = preg_replace( "#(\n){0,1}</ol>(\n){0,1}#", "\n\[/list\]\\2" , $txt );
			
			//-----------------------------------------
			// Opening style attributes
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--sizeo:(.+?)-->(.+?)<!--/sizeo-->#"               , "[size=\\1]" , $txt );
			$txt = preg_replace( "#<!--coloro:(.+?)-->(.+?)<!--/coloro-->#"             , "[color=\"\\1\"]", $txt );
			$txt = preg_replace( "#<!--fonto:(.+?)-->(.+?)<!--/fonto-->#"               , "[font=\"\\1\"]" , $txt );
			$txt = preg_replace( "#<!--backgroundo:(.+?)-->(.+?)<!--/backgroundo-->#"   , "[background=\\1]" , $txt );
			
			//-----------------------------------------
			// Closing style attributes
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--sizec-->(.+?)<!--/sizec-->#"            , "[/size]" , $txt );
			$txt = preg_replace( "#<!--colorc-->(.+?)<!--/colorc-->#"          , "[/color]", $txt );
			$txt = preg_replace( "#<!--fontc-->(.+?)<!--/fontc-->#"            , "[/font]" , $txt );
			$txt = preg_replace( "#<!--backgroundc-->(.+?)<!--/backgroundc-->#", "[/background]" , $txt );
			
			//-----------------------------------------
			// LEGACY SPAN TAGS
			//-----------------------------------------
			
			//-----------------------------------------
			// WYSI-Weirdness #9923464: Opera span tags
			//-----------------------------------------
					
			while ( preg_match( "#<span style='font-family: \"(.+?)\"'>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style='font-family: \"(.+?)\"'>(.+?)</span>#is", "\[font=\\1\]\\2\[/font\]", $txt );
			}

			while ( preg_match( "#<span style=['\"]font-size:?(.+?)pt;?\s+?line-height:?\s+?100%['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace_callback( "#<span style=['\"]font-size:?(.+?)pt;?\s+?line-height:?\s+?100%['\"]>(.+?)</span>#is" , array( &$this, 'unconvert_size' ), $txt );
			}
			
			while ( preg_match( "#<span style=['\"]color:?(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]color:?(.+?)['\"]>(.+?)</span>#is"    , "\[color=" . trim("\\1") . "\]\\2\[/color\]", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]font-family:?(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]font-family:?(.+?)['\"]>(.+?)</span>#is", "\[font=\"" . trim("\\1") . "\"\]\\2\[/font\]", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]background-color:?\s+?(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]background-color:?\s+?(.+?)['\"]>(.+?)</span>#is", "\[background=\\1\]\\2\[/font\]", $txt );
			}
			
			# Legacy <strike>
			$txt = preg_replace( "#<s>(.+?)</s>#is"            , "\[s\]\\1\[/s\]"  , $txt );
			
			//-----------------------------------------
			// Tidy up the end quote stuff
			//-----------------------------------------
			
			$txt = preg_replace( "#(\[/QUOTE\])\s*?<br />\s*#si", "\\1\n", $txt );
			$txt = preg_replace( "#(\[/QUOTE\])\s*?<br>\s*#si"  , "\\1\n", $txt );
			
			$txt = preg_replace( "#<!--EDIT\|.+?\|.+?-->#" , "" , $txt );
			$txt = str_replace( "</li>", "", $txt );
			
			$txt = str_replace( "&#153;", "(tm)", $txt );
		}
		
		//-----------------------------------------
		// Unconvert custom bbcode
		//-----------------------------------------
				
		$txt = $this->post_db_unparse_bbcode( $txt );
		
		//-----------------------------------------
		// Parse html
		//-----------------------------------------
		
		if ( $this->parse_html )
		{
			$txt = str_replace( "&#39;", "'", $txt);
		}
		
		return trim(stripslashes($txt));
	}

	/**
	 * Parse new quotes
	 *
	 * @access	private
	 * @param	string	Quote data
	 * @param	string	Raw text
	 * @return	string	Converted text
	 */
	private function _parse_new_quote( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$return     = array();
		$quote_data = $matches[1];
		$quote_text = $matches[2];
		
		//-----------------------------------------
		// No data?
		//-----------------------------------------
		
		if ( ! $quote_data )
		{
			return '[quote]';
		}
		else
		{
			preg_match( "#\(post=(.+?)?:date=(.+?)?:name=(.+?)?\)#", $quote_data, $match );
			
			if ( $match[3] )
			{
				$return[] = " name='{$match[3]}'";
			}
			
			if ( $match[1] )
			{
				$return[] = " post='".intval($match[1])."'";
			}
			
			if ( $match[2] )
			{
				$return[] = " date='{$match[2]}'";
			}
			
			return str_replace( '  ', ' ', '[quote' . implode( ' ', $return ).']' );
		}
	}

	/**
	 * Convert font-size HTML back into BBCode
	 *
	 * @param	integer	Core size
	 * @param	string	Raw text
	 * @return	string	Converted text
	 */
	private function unconvert_size( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$size = trim($matches[1]);
		$text = $matches[2];
		
		foreach( $this->font_sizes as $k => $v )
		{
			if( $size == $v )
			{
				$size = $k;
				break;
			}
		}
		//$size -= 7;
		
		return '[size='.$size.']'.$text.'[/size]';
	}

	/**
	 * Convert flash HTML back into BBCode
	 *
	 * @param	string	Raw text
	 * @return	string	Converted text
	 */
	private function unconvert_flash($matches=array())
	{
		$f_arr = explode( "+", $matches[1] );
		
		return '[flash='.$f_arr[0].','.$f_arr[1].']'.$f_arr[2].'[/flash]';
	}

	/**
	 * Convert SQL HTML back into BBCode
	 *
	 * @param	string	Raw text
	 * @return	string	Converted text
	 */
	private function unconvert_sql($matches=array())
	{
		$sql = stripslashes($matches[2]);
		
		$sql = preg_replace( "#<span style='.+?'>#is", "", $sql );
		$sql = str_replace( "</span>"                , "", $sql );
		$sql = rtrim( $sql );
		
		return '[sql]'.$sql.'[/sql]';
	}

	/**
	 * Convert HTML TAG HTML back into BBCode
	 *
	 * @param	string	Raw text
	 * @return	string	Converted text
	 */
	private function unconvert_htm($matches=array())
	{
		$html = stripslashes($matches[2]);
		
		$html = preg_replace( "#<span style='.+?'>#is", "", $html );
		$html = str_replace( "</span>"                , "", $html );
		$html = rtrim( $html );
		
		return '[html]'.$html.'[/html]';
	}

	/**
	 * Pre-edit unparse custom BBCode
	 *
	 * @param	string	Converted text
	 * @return	string	Raw text
	 */
	private function post_db_unparse_bbcode($t="")
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$snapback = 0;

		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( is_array( ipsRegistry::cache()->getCache('bbcode') ) and count( ipsRegistry::cache()->getCache('bbcode') ) )
		{
			foreach( ipsRegistry::cache()->getCache('bbcode') as $row )
			{
				if( !$row['bbcode_replace'] )
				{
					continue;
				}

				$preg_tag = preg_quote( $row['bbcode_replace'], '#' );

				//NK: only return the first match
				$preg_tag = preg_replace( '/\\\{option\\\}/', '(.*?)', $preg_tag, 1 );
				$preg_tag = preg_replace( '/\\\{content\\\}/', '(.*?)', $preg_tag, 1 );
				$preg_tag = str_replace( '\{option\}', '.*?', $preg_tag );
				$preg_tag = str_replace( '\{content\}', '.*?', $preg_tag );
				
				// Bug 5658 - </span> tags in custom bbcode don't play nice with inbuilt bbcode
				$preg_tag = str_replace( "\</span\>", "\</span\>(?!\<\!--/sizec|\<\!--/colorc|\<\!--/fontc|\<\!--/backgroundc)", $preg_tag );

				//-----------------------------------------
				// Slightly slower
				//-----------------------------------------
				
				while ( preg_match_all( "#".$preg_tag."#si", $t, $match ) )
				{
					for ( $i = 0; $i < count($match[0]); $i++)
					{
						//-----------------------------------------
						// Does the option tag come first?
						//-----------------------------------------
						
						$_option  = 1;
						$_content = 2;
						
						if ( $row['bbcode_switch_option'] )
						{
							$_option  = 2;
							$_content = 1;
						}
						else if( count( $match ) == 2 )
						{
							$_content = 1;
						}
						
						# XSS Check: Bug ID: 980
						if ( $row['bbcode_tag'] == 'post' OR $row['bbcode_tag'] == 'topic' OR $row['bbcode_tag'] == 'snapback' )
						{
							$match[ $_option ][$i] = intval( $match[ $_option ][$i] );
						}
						
						# Recurse?
						if ( preg_match( "#".$preg_tag."#si", $match[ $_content ][$i] ) )
						{
							$match[ $_content ][$i] = $this->post_db_unparse_bbcode( $match[ $_content ][$i] );
						}
							
						$tmp = '[' . $row['bbcode_tag'];
						
						if( $row['bbcode_useoption'] )
						{
							if( $row['bbcode_switch_option'] )
							{
								$tmp .= '={content}]{option}[/' . $row['bbcode_tag'] . ']';
							}
							else
							{
								$tmp .= '={option}]{content}[/' . $row['bbcode_tag'] . ']';
							}
						}
						else
						{
							$tmp .= ']{content}[/' . $row['bbcode_tag'] . ']';
						}
						
						$tmp = str_replace( '{option}' , $match[ $_option  ][$i], $tmp );
						$tmp = str_replace( '{content}', $match[ $_content ][$i], $tmp );
						$t   = str_replace( $match[0][$i], $tmp, $t );
					}
				}
			}
		}

		return $t;
	}

	/**
	 * Recursively parse a simple tag
	 *
	 * @param	string	Tag name (ie "b", "i", "s")
	 * @param	string	Convert tag (ie "b", "i", "strike" )
	 * @param	int		To BBcode
	 * @param	string	HTML to search in
	 * @return	string	Parsed HTML;
	 */
	private function parse_simple_tag_recursively( $tag_name, $convert_name, $bbcode, $text )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$_open    = ( $bbcode ) ? '[' : '<';
		$_close   = ( $bbcode ) ? ']' : '>';
		
		$_s_open  = ( $bbcode ) ? '<' : '[';
		$_s_close = ( $bbcode ) ? '>' : ']';
		
		$total_length = strlen( $text );
		$_text        = $text;
		$statement    = "";
	
		# Tag specifics
		$tag_open        = $_open . $tag_name . $_close;
		$found_tag_open  = 0;
		$tag_close       = $_open . "/" . $tag_name . $_close;
		$found_tag_close = 0;
		
		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------
		
		while ( 1 == 1 )
		{
			//-----------------------------------------
			// Update template length
			//-----------------------------------------
			
			$_beginning_of_code = 0;
			$_l_text            = strtolower( $_text );
			
			//----------------------------------------
			// Look for opening [TAG].
			//----------------------------------------
			
			$found_tag_open = strpos( $_l_text, $tag_open, $found_tag_close );
			
			//----------------------------------------
			// No logic found? 
			//----------------------------------------
			
			if ( $found_tag_open === FALSE )
			{
				break;
			}
			
			//----------------------------------------
			// End [/TAG] statement?
			//----------------------------------------
			
			$found_tag_close = strpos( $_l_text, $tag_close, $found_tag_open );
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( $found_tag_close === FALSE )
			{ 
				return $_text;
			}
			
			$_beginning_of_code = $found_tag_open + strlen( $tag_open );
			
			//----------------------------------------
			// Check recurse
			//----------------------------------------
			
			$tag_found_recurse = $_beginning_of_code;
			
			while ( 1 == 1 )
			{
				//----------------------------------------
				// Got an IF?
				//----------------------------------------
				
				$tag_found_recurse = strpos( $_l_text, $tag_open, $tag_found_recurse );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $tag_found_recurse === FALSE OR $tag_found_recurse >= $found_tag_close )
				{
					break;
				}
				
				$tag_end_recurse = $found_tag_close + strlen( $tag_close );
				
				# Start at tag_found_recurse...
				$found_tag_close = strpos( $_l_text, $tag_close, $tag_found_recurse );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $found_tag_close === FALSE )
				{
					return $_text;
				}
				
				$tag_found_recurse += strlen( $tag_open );
			}
	
			//----------------------------------------
			// Continue
			//----------------------------------------
			
			$_code  = substr( $_text, $_beginning_of_code, $found_tag_close - $_beginning_of_code );
			
			//----------------------------------------
			// Recurse
			//----------------------------------------
			
			if ( strpos( strtolower( $_code ), $tag_open ) !== FALSE )
			{
				$_code = $this->parse_simple_tag_recursively( $tag_name, $convert_name, $bbcode, $_code );
			}
			
			//----------------------------------------
			// Swap old text for new...
			//----------------------------------------
			
			$_new_code = $_s_open . $convert_name . $_s_close . $_code . $_s_open . '/' . $convert_name . $_s_close;
			
			$_text = substr_replace( $_text, $_new_code, $found_tag_open, ( $found_tag_close - $found_tag_open ) + strlen( $tag_close )  );
			
			$found_tag_close = $found_tag_open + strlen($_new_code);
		}
	
		return $_text;
	}
	
}