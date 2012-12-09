<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * XML Handler: Can use PHP's internal XML handler, or a lite hand-rolled parser, to create and read XML files
 * Last Updated: $Date: 2009-02-04 15:05:02 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		25th February 2004
 * @version		$Revision: 222 $
 *
 *
 * Example Usage:
 * <code>
 * <productlist name="myname" version="1.0">
 *  <productgroup name="thisgroup">
 *   <product id="1.0">
 *    <description>This is a descrption</description>
 *    <title>Baked Beans</title>
 *    <room store="1">103</room>
 *   </product>
 *  </productgroup>
 * </productlist>
 * 
 * // Set the root tag
 * $xml->xml_set_root( 'productlist', array( 'name' => 'myname', 'version' => '1.0' ) );
 * 
 * // Add a group
 * $xml->xml_add_group( 'productgroup', array( 'name' => 'thisgroup' ) );
 * 
 * // Build entry content
 * $content[] = $xml->xml_build_simple_tag( 'description', "This is a descrption" );
 * $content[] = $xml->xml_build_simple_tag( 'title'      , "Baked Beans"          );
 * $content[] = $xml->xml_build_simple_tag( 'room'       , '103'         , array( 'store' => 1 ) );
 * 
 * // Build entry
 * $entry[]   = $xml->xml_build_entry( 'product', $content, array( 'id' => '1.0' ) );
 * 
 * // Add to group 'productlist'
 * $xml->xml_add_entry_to_group( 'productgroup', $entry );
 * 				
 * // Format..
 * $xml->xml_format_document();
 * 
 * // Get XML document
 * $filecontents = $xml->xml_document;
 * 
 * // Parse XML document
 * $xml->xml_parse_document( $filecontents );
 * 
 * // Show XML array
 * print_r( $xml->xml_array );
 * 
 * // print "Baked Beans";
 * print $xml->xml_array['productlist']['productgroup']['product'][0]['title']['VALUE'];		  
 * 
 * // print "1";
 * print $xml->xml_array['productlist']['productgroup']['product'][0]['room']['ATTRIBUTES']['store'];	
 * </code>
 *
 */
 
class class_xml
{
	/**
	* XML header
	*
	* @access	public
	* @var 		string
	*/
	public $header				= "";
	
	/**
	* Root tag name
	*
	* @access	public
	* @var 		string
	*/
	public $root_tag			= '';
	
	/**
	* Root attributes
	*
	* @access	public
	* @var 		string
	*/
	public $root_attributes		= "";
	
	/**
	* Array of entries
	*
	* @access	public
	* @var 		array
	*/
	public $entries				= array();
	
	/**
	* String of compiled XML document
	*
	* @access	public
	* @var 		string
	*/
	public $xml_document		= "";
	
	/**
	* Work variable
	*
	* @access	public
	* @var 		integer
	*/
	public $depth				= 0;
	
	/**
	* Tmp doc, used during creation
	*
	* @access	public
	* @var 		string
	*/
	public $tmp_doc 			= "";
	
	/**
	* Tag groups
	*
	* @access	public
	* @var 		string
	*/
	public $groups				= "";
	
	/**
	* Index numerically flag
	*
	* @access	public
	* @var 		integer
	*/
	public $index_numeric		= 0;
	
	/**
	* Collapse duplicate tags flag
	*
	* @access	public
	* @var 		integer
	*/
	public $collapse_dups		= 1;
	
	/**
	* Main XML array of parsed components
	*
	* @access	public
	* @var 		array
	*/
	public $xml_array 			= array();
	
	/**
	* Collapse newlines in CDATA tags
	*
	* @access	public
	* @var 		integer
	*/
	public $collapse_newlines	= 1;
	
	/**
	* Use lite parser flag
	*
	* @access	public
	* @var 		integer
	*/
	public $lite_parser			= 0;
	
	/**
	* DOC type
	*
	* @access	public
	* @var 		string
	*/
	public $doc_type 			= 'ISO-8859-1';
	
	/**
	* Use docytype in document
	*
	* @access	public
	* @var 		integer
	*/
	public $use_doctype 		= 1;


	/**
	* Parse an XML document into an array of field and values
	*
	* @access	public
	* @param	string		Raw XML Data
	* @return	void
	*/
	public function xml_parse_document( $xml )
	{
		$i = -1;
		
		//-----------------------------------------
		// Use "lite" parser
		//-----------------------------------------
		
		if ( $this->lite_parser )
		{
			$lite = new xml_lite_parse();

			$lite->xml_parse_it( $xml );
		
			$this->xml_array = $this->_xmlGetChildren( $lite->stack, $i );
			
			//-----------------------------------------
			// Free willy..er..memory
			//-----------------------------------------
			
			$lite->garbage_collect();
		}
		
		//-----------------------------------------
		// Use PHP EXPAT Parser?
		//-----------------------------------------
		
		else
		{
			if ( $this->use_doctype && in_array( strtolower($this->doc_type), array( 'us-ascii', 'utf-8', 'iso-8859-1' ) ) )
			{
				$parser = xml_parser_create( $this->doc_type );
			}
			else
			{
				$parser = xml_parser_create();
			}
		
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE  , 0);
			xml_parse_into_struct($parser, $xml, $vals); 
			xml_parser_free($parser);
			
			$this->xml_array = $this->_xmlGetChildren($vals, $i);
		}
		
		//-----------------------------------------
		// Garbage collect
		//-----------------------------------------
		
		unset( $vals );
		unset( $xml );
	}

	/**
	* Parse an array into an XML document
	*
	* @access	public
	* @param	array		Entry array
	* @return	void
	*/
	public function xml_format_document( $entry=array() )
	{
		$this->header = '<?xml version="1.0" encoding="'.$this->doc_type.'"?'.'>';
		
		$this->xml_document = $this->header ? $this->header."\n" : '';
		
		$this->xml_document .= "<".$this->root_tag.$this->root_attributes.">\n";
		
		$this->xml_document .= $this->tmp_doc;
		
		$this->xml_document .= "\n</".$this->root_tag.">";
		
		$this->tmp_doc       = "";
	}

	/**
	* Set the root tag
	*
	* @access	public
	* @param	string		Root tag name
	* @param	array		Root tag attributes
	* @return	void
	*/
	public function xml_set_root($tag, $attributes=array() )
	{
		$this->root_tag        = $tag;
		$this->root_attributes = $this->_xmlBuildAttributeString( $attributes );
	}

	/**
	* Add entry to XML group
	*
	* @access	public
	* @param	string		Tag name
	* @param	array		Entry values
	* @return	void
	*/
	public function xml_add_entry_to_group($tag, $entry=array() )
	{
		$this->tmp_doc .= "\t".$this->groups[ $tag ];
		
		if ( is_array( $entry ) and count( $entry ) )
		{
			foreach( $entry as $e )
			{
				$this->tmp_doc .=  "\n\t\t".$e."\n";
			}
		}
		
		$this->tmp_doc .= "\t</".$tag.">\n";
	}

	/**
	* Build an XML entry
	*
	* @access	public
	* @param	string		Tag name
	* @param	array		Content array
	* @param	array		Attributes
	* @return	string
	*/
	public function xml_build_entry( $tag, $content=array(), $attributes=array() )
	{
		$entry = "<" . $tag . $this->_xmlBuildAttributeString($attributes) . ">\n";
		
		if ( is_array( $content ) and count( $content ) )
		{
			foreach( $content as $c )
			{
				$entry .= "\t\t\t".$c."\n";
			}
		}
		
		$entry .= "\t\t</" . $tag . ">";
		
		return $entry;
	}

	/**
	* Add a group to an XML document
	*
	* @access	public
	* @param	string		Tag name
	* @param	array		Attributes
	* @return	string
	*/
	public function xml_add_group( $tag, $attributes=array() )
	{
		$this->groups[ $tag ] = "<" . $tag . $this->_xmlBuildAttributeString($attributes) . ">";
	}

	/**
	* Build and XML simple tag
	*
	* @access	public
	* @param	string		Tag name
	* @param	string		Tag value
	* @param	array		Attributes
	* @return	string
	*/
	public function xml_build_simple_tag( $tag, $description="", $attributes=array() )
	{
		return "<" . $tag . $this->_xmlBuildAttributeString($attributes) . ">" . $this->_xmlEncodeString($description) . "</" . $tag . ">";
	}

	/**
	* Build tree node
	* Adapted from http://eric.pollmann.net/work/public_domain/
	*
	* @access	private
	* @param	array		Values
	* @param	array		Values
	* @param	string		Counter
	* @param	string		Tag type
	* @return	array
	*/
	private function _xmlBuildTag( $thisvals, $vals, &$i, $type )
	{
		$tag = array();
		
		if ( isset($thisvals['attributes']) )
		{
			$tag['ATTRIBUTES'] = $this->_xmlDecodeAttribute($thisvals['attributes']); 
		}
		
		if ( $type === 'complete' )
		{
			if( isset( $thisvals['value'] ) )
			{
				$tag['VALUE'] = $this->xmlUnconvertSafecdata( $thisvals['value'] );
			}
			else 
			{
				$tag['VALUE'] = '';
			}
		}
		else
		{
			$tag = array_merge( $tag, $this->_xmlGetChildren($vals, $i) );
		}
		
		return $tag;
	}

	/**
	* Build a nested array of children
	* Adapted from http://eric.pollmann.net/work/public_domain/
	*
	* @access	private
	* @param	array		Values
	* @param	string		Counter
	* @return	array
	*/
	private function _xmlGetChildren($vals, &$i)
	{
		$children = array();
		
		//-----------------------------------
		// CDATA before children
		//-----------------------------------
		
		if ( $i > -1 && isset( $vals[$i]['value'] ) )
		{
			$children['VALUE'] = $this->xmlUnconvertSafecdata( $vals[$i]['value'] );
		}
		
		//-----------------------------------
		// Loopy loo
		//-----------------------------------
		
		while( ++$i < count( $vals ) )
		{ 
			$type = $vals[$i]['type'];
			
			//-----------------------------------
			// CDATA after children
			//-----------------------------------
			
			if ($type === 'cdata')
			{
				$children['VALUE'] .= $this->xmlUnconvertSafecdata( $vals[$i]['value'] );
			}
			
			//-----------------------------------
			// COMPLETE: At end of current branch
			// OPEN:    Node has children, recurse
			//-----------------------------------
			
			else if ( $type === 'complete' OR $type === 'open' )
			{
				$tag = $this->_xmlBuildTag( $vals[$i], $vals, $i, $type );
				
				if ( $this->index_numeric )
				{
					$tag['TAG'] = $vals[$i]['tag'];
					$children[] = $tag;
				}
				else
				{
					$children[$vals[$i]['tag']][] = $tag;
				}
			}
			
			//-----------------------------------
			// End of node?
			//-----------------------------------
			
			else if ($type === 'close')
			{
				break;
			}
		}
		
		if ( $this->collapse_dups )
		{
			foreach( $children as $key => $value )
			{
				if ( is_array($value) && (count($value) == 1) )
				{
					$children[$key] = $value[0];
				}
			}
		}
			
		return $children;
	} 

	/**
	* Builds attribute string
	*
	* @access	private
	* @param	array		Values
	* @return	string
	*/
	private function _xmlBuildAttributeString( $array = array() )
	{
		if ( is_array( $array ) and count( $array ) )
		{
			$string = array();
			
			foreach( $array as $k => $v )
			{
				$v = trim( $this->_xmlEncodeAttribute($v) );
				
				$string[] = $k.'="'.$v.'"';
			}
			
			return ' ' . implode( " ", $string );
		}
	}

	/**
	* Encode XML attribute (Make safe for transport)
	*
	* @access	private
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	private function _xmlEncodeAttribute( $t )
	{
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );
		
		return $t;
	}

	/**
	* Decode XML attribute (Make safe for transport)
	*
	* @access	private
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	private function _xmlDecodeAttribute( $t )
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		
		return $t;
	}

	/**
	* Encode XML attribute (Make safe for transport)
	*
	* @access	private
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	private function _xmlEncodeString( $v )
	{
		if ( preg_match( "/['\"\[\]<>&]/", $v ) )
		{
			$v = "<![CDATA[" . $this->_xmlConvertSafecdata($v) . "]]>";
		}
		
		if ( $this->collapse_newlines )
		{
			$v = str_replace( "\r\n", "\n", $v );
		}
		
		return $v;
	}

	/**
	* Encode CDATA XML attribute (Make safe for transport)
	*
	* @access	private
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	private function _xmlConvertSafecdata( $v )
	{
		# Legacy
		//$v = str_replace( "<![CDATA[", "<!¢|CDATA|", $v );
		//$v = str_replace( "]]>"      , "|¢]>"      , $v );
		
		# New
		$v = str_replace( "<![CDATA[", "<!#^#|CDATA|", $v );
		$v = str_replace( "]]>"      , "|#^#]>"      , $v );
		
		return $v;
	}

	/**
	* Decode CDATA XML attribute (Make safe for transport)
	*
	* @access	public
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	public function xmlUnconvertSafecdata( $v )
	{
		# Legacy
		$v = str_replace( "<!¢|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|¢]>"      , "]]>"      , $v );
		
		# New
		$v = str_replace( "<!#^#|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|#^#]>"      , "]]>"      , $v );
		
		return $v;
	}
	
}


//======================================================================================
// CLASS: XML_LITE_PARSE (Takes parsed XML and puts into EXPAT compatible array
//======================================================================================

/**
 * XML-LITE Extraction Sub class
 *
 * Methods and functions for handling XML documents
 *
 * @package		Invision Power Services Kernel
 * @author 		$Author: bfarber $
 * @since		25th February 2004
 * @version		$Revision: 222 $
 */
 
class xml_lite_parse
{
	/**
	* XML object
	*
	* @access	public
	* @var 		object
	*/
	public $xml_class;
	
	/**
	* Parser object
	*
	* @access	object
	* @var 		integer
	*/
	public $parser;
	
	/**
	* Preserve cdata flag
	*
	* @access	public
	* @var 		integer
	*/
	public $preserve_cdata	= 1;
	
	/**
	* Internal: stack management
	*
	* @access	private
	* @var 		array
	*/
	private $stack			= array();
	
	/**
	* Depth level
	*
	* @access	public
	* @var 		integer
	*/
	public $level			= 1;
	
	/**
	* Current tagname
	*
	* @access	public
	* @var 		string
	*/
	public $tagname			= "";
	
	/**
	* Current array id
	*
	* @access	public
	* @var 		integer
	*/
	public $array_id		= 0;
	
	/**
	* Last managed id
	*
	* @access	public
	* @var 		integer
	*/
	public $last_id			= 0;
	
	/**
	* Tags opened
	*
	* @access	public
	* @var 		array
	*/
	public $tagopen			= array();
	
	/**
	* XML Document
	*
	* @access	public
	* @var 		string
	*/
	public $xmldoc			= "";

	/**
	* Parse XML document
	*
	* @access	public
	* @param	string		Raw xml document
	* @return	void
	*/
	public function xml_parse_it( $xmldoc )
	{
		$parser = new xml_extract();
		
		$this->xmldoc = $xmldoc;
		
		unset( $xmldoc );
		
		//-----------------------------------------
		// Set up element handlers
		//-----------------------------------------
		
		$parser->_myXmlSetElementHandler( array(&$this, '_myStartElement'), array(&$this, '_myEndElement') );
		$parser->_myXmlSetCharacterDataHandler(array(&$this, '_myDataElement'));
		
		if ( $this->preserve_cdata )
		{
			$parser->_myXmlSetCdataSectionHandler( array(&$this, '_myCdataElement') );
		}
		
		$parser->xml_parse_document( $this->xmldoc );
	}
	
	/**
	* Start element callback
	*
	* @access	private
	* @param	object		Parser ref
	* @param	string		Tag name
	* @param	array 		Attributes
	* @return	void
	*/
	private function _myStartElement( &$parser_obj, $name, $attr )
	{
		//-------------------------------
		// Add to stack
		//-------------------------------
		
		$this->stack[ $this->array_id ] = array( 'tag'        => $name,
												 'type'       => 'open',
												 'level'      => $this->level,
												 'value'      => '',
											   );
		
		//-------------------------------
		// Attributes?
		//-------------------------------
		
		if ( is_array( $attr ) and count( $attr ) )
		{
			 $this->stack[ $this->array_id ]['attributes'] = $attr;
		}
		
		//-------------------------------
		// Flying higher than an eagle?
		//-------------------------------
		
		if ( $this->tagname != $name )
		{
			if ( $this->tagopen[ $name ] )
			{
				$this->level = $this->tagopen[ $name ];
			}
			else
			{
				$this->level++;
			}
		}
		
		//-------------------------------
		// Set current tag name
		//-------------------------------
		
		$this->tagname = $name;
		
		//-------------------------------
		// Inc. array ID
		//-------------------------------
		
		$this->array_id++;
		
		//-------------------------------
		// Set tag == depth
		//-------------------------------
		
		$this->tagopen[ $name ] = $this->level;
	}
	
	/**
	* End element callback
	*
	* @access	private
	* @param	object		Parser ref
	* @param	string		Tag name
	* @return	void
	*/
	private function _myEndElement( &$parser_obj, $name )
	{
		$this->stack[ $this->array_id ] = array( 'tag'        => $name,
												 'type'       => 'close',
												 'level'      => $this->tagopen[ $name ] - 1,
											   );
		
		//-------------------------------
		// Update already done data?
		//-------------------------------
		
		if ( ( $this->stack[ $this->array_id - 2 ]['tag'] == $this->tagname )
				AND
			 ( $this->stack[ $this->array_id - 1 ]['_data'] == 1 )
		   )
		{
			 //-------------------------------
			 // Update previous tag
			 //-------------------------------
			 
			 $this->stack[ $this->array_id - 2 ]['value'] = class_xml::xmlUnconvertSafecdata( $this->stack[ $this->array_id - 1 ]['value'] );
			 $this->stack[ $this->array_id - 2 ]['type']  = 'complete';
			 
			 unset( $this->stack[ $this->array_id - 1 ] );
			 unset( $this->stack[ $this->array_id ] );
			 
			 $this->array_id -= 2;
			 $this->last_id   = $this->array_id - 1;
		}
		
		$this->tagname = "";
		
		$this->array_id++;
		$this->level--;
	}
	
	/**
	* Parse data
	*
	* @access	private
	* @param	object		Parser ref
	* @param	string		Tag name
	* @return	void
	*/
	private function _myDataElement( &$parser_obj, $data )
	{
		if ( $this->tagname )
		{
			$this->stack[ $this->array_id ] = array( 'tag'        => $this->tagname,
													 'type'       => 'open',
													 'level'      => $this->level,
													 'value'      => class_xml::xmlUnconvertSafecdata($data),
													 '_data'      => 1
												   );
		}
		
		//-------------------------------
		// Inc. array ID
		//-------------------------------
		
		$this->array_id++;
	}
	
	/**
	* Parse CDATA
	*
	* @access	private
	* @param	object		Parser ref
	* @param	string		Tag name
	* @return	void
	*/
	private function _myCdataElement( &$parser_obj, $data )
	{
		$this->_myDataElement( $parser_obj, $data );
	}
	
	/**
	* Garbage collection
	*
	* @access	public
	* @return	void
	*/
	public function garbage_collect()
	{
		$this->stack    = array();
		$this->tagname  = array();
		$this->array_id = 0;
		$this->level    = 0;
		$this->xmldoc   = "";
	}

}

//======================================================================================
// CLASS: XML_EXTRACT (Parses XML file)
//======================================================================================

/**
 * XML-LITE Extraction Sub class
 *
 * Methods and functions for handling XML documents
 *
 * @package		Invision Power Services Kernel
 * @author 		$Author: bfarber $
 * @since		25th February 2004
 * @version		$Revision: 222 $
 */
class xml_extract
{
	/**
	* Array of XML data
	*
	* @access	public
	* @var 		array
	*/
	public $xml_array				= array();
	
	/**
	* Characters so far parsed
	*
	* @access	public
	* @var 		integer
	*/
	public $chr_sofar				= 0;
	
	/**
	* Handler object
	*
	* @access	public
	* @var 		object
	*/
	public $handler_cdata_handler;
	
	/**
	* Handler object
	*
	* @access	public
	* @var 		object
	*/
	public $handler_character_data;
	
	/**
	* Handler object
	*
	* @access	public
	* @var 		object
	*/
	public $handler_end_element;
	
	/**
	* Handler object
	*
	* @access	public
	* @var 		object
	*/
	public $handler_start_element;
	
	/**
	* Notation mapping
	*
	* @access	public
	* @var 		array
	*/
	public $xml_constants = array(
								'CDATA_TAG' => '![CDATA[',
								'CDATA_LEN' => 8,
								'NOTATION'  => '!NOTATION',
								'DOCTYPE'   => '!DOCTYPE'
							  );
							  
	/**
	* Document parsing
	*
	* @access	public
	* @param	string		XML Document
	* @return	void
	*/
	public function xml_parse_document( $xml )
	{
		//-----------------------------------------
		// Grab all relevant XML data
		// Strip off header, DOC TYPE, etc
		//-----------------------------------------
		
		$xml = preg_replace( "#^(?:.*?)?(<.*>)(?:.*?)?$#s", "\\1", $xml );
		
		$xml_strlen = strlen( $xml );
		
		//-----------------------------------------
		// Pick through, char by char
		//-----------------------------------------
		
		for( $i = 0 ; $i < $xml_strlen; $i++ )
		{
			$chr = $xml{$i};
			
			switch( $chr )
			{
				case '<':
					if ( substr( $this->chr_sofar, 0, $this->xml_constants['CDATA_LEN'] ) == $this->xml_constants['CDATA_TAG'] )
					{
						//-----------------------------------------
						// Processing CDATA
						//-----------------------------------------
						
						$this->chr_sofar .= $chr;
					}
					else
					{
						$this->_parseBetweenTags( $this->chr_sofar );
						$this->chr_sofar = '';
					}
					break;
				case '>':
					if ( 
						( substr( $this->chr_sofar, 0, $this->xml_constants['CDATA_LEN'] ) == $this->xml_constants['CDATA_TAG'] )
						 AND
						 ! (
						 	 ( $this->_getNthCharFromEnd( $this->chr_sofar, 0 ) == ']' )
						 	 AND
						 	 ( $this->_getNthCharFromEnd( $this->chr_sofar, 1 ) == ']' )
						   )
					   )
					  	{
						 	$this->chr_sofar .= $chr;
						}
						else
						{
							if( $xml{ strlen($this->chr_sofar +1) } == ']' )
							{
								$this->chr_sofar .= $chr;
							}
							else
							{
								$this->_parseTag( $this->chr_sofar );
								$this->chr_sofar = '';
							}
						}
						break;
				default:
					$this->chr_sofar .= $chr;
			}
		}
		
		unset($xml);
	}
	
	/**
	* Parse a tag
	*
	* @access	private
	* @param	string		Text
	* @return	void
	*/
	private function _parseTag( $text )
	{
		$attr = array();
		$text = trim($text);
		$fchr = $text{0};
		
		switch ($fchr)
		{
			//-----------------------------------------
			// First char is closing tag?
			//-----------------------------------------
			case '/':
			
				$tag_name = substr($text, 1);
				$this->_execEndElement($tag_name);
				break;
			//-----------------------------------------
			// First char is instruction/doctype?
			//-----------------------------------------
			case '!':
			
				$uc_tag_text = strtoupper($text);

				if ( strpos($uc_tag_text, $this->xml_constants['CDATA_TAG']) !== false )
				{
					//-----------------------------------------
					// CDATA text
					//-----------------------------------------
					
					$total          = strlen($text);
					$openbrace_cnt  = 0;
					$tn_text        = '';

					for ( $i = 0; $i < $total; $i++ )
					{
						$cc = $text{$i};
						
						//-----------------------------------------
						// End of CDATA?
						//-----------------------------------------
						
						if ( ($cc == ']') && ( $text{($i + 1)} == ']' ) )
						{
							if( ! $text{($i + 2)} == ']' )
							{
								break;
							}
							else
							{
								$tn_text .= $cc;
							}
						}
						else if ($openbrace_cnt > 1)
						{
							$tn_text .= $cc;
						}
						else if ($cc == '[')
						{
							//-----------------------------------------
							// Won't get here until first OB reached
							//-----------------------------------------
							
							$openbrace_cnt ++;
						}
					}

					if ( $this->handler_cdata_handler == null )
					{
						$this->_execCharacterData($tn_text);
					}
					else
					{
						$this->_execCdataElement($tn_text);
					}
				}
				else if ( strpos( $uc_tag_text, $this->xml_constants['NOTATION'] ) !== false )
				{
					//-----------------------------------------
					// !NOTATION? Ignore!
					//-----------------------------------------
					
					return;
				}
				else if ( substr($text, 0, 2) == '!-' )
				{
					//-----------------------------------------
					// !Comment? Ignore!
					//-----------------------------------------
					
					return;
				}

				break;
			//-----------------------------------------
			// Case is ?INSTRUCTION?
			//-----------------------------------------
			case '?':
				
				//-----------------------------------------
				// Instruction? Ignore!
				//-----------------------------------------
				
				return;
			//-----------------------------------------
			// Normal tag - woohoo!
			//-----------------------------------------
			default:
			
				if ( (strpos($text, '"') !== false) || (strpos($text, "'") !== false) )
				{
					$total    = strlen($text);
					$tag_name = '';

					for ($i = 0; $i < $total; $i++)
					{
						$cc = $text{$i};

						if ($cc == ' ')
						{
							$attr = $this->_parseAttr(substr($text, $i));
							break;
						}
						else
						{
							$tag_name.= $cc;
						}
					}

					if ( strrpos($text, '/') == ( strlen($text) - 1 ) )
					{
						$this->_execStartElement($tag_name, $attr);
						$this->_execEndElement($tag_name);
					}
					else
					{
						$this->_execStartElement($tag_name, $attr);
					}
				}
				else {
					if ( strpos($text, '/') !== false )
					{
						$text = trim( substr( $text, 0, ( strrchr($text, '/' ) - 1 ) ) );
						$this->_execStartElement($text, $attr);
						$this->_execEndElement($text);
					}
					else
					{
						$this->_execStartElement($text, $attr);
					}
				}
		}
	}
	
	/**
	* Parse an attribute
	*
	* @access	private
	* @param	string		Text
	* @return	array 		Attributes
	*/
	private function _parseAttr( $text )
	{
		$text         = trim($text);	
		$attr_array   = array();
		$query_entity = false;			
		
		$total        = strlen($text);
		$dump_key     = '';
		$dump_value   = '';
		$cur_state    = 0;  // 0 = none, 1 = key, 2 = value
		$quote_type   = '';
		
		for ($i = 0; $i < $total; $i++)
		{								
			$cc = $text{$i};
			
			if ( $cur_state == 0 )
			{
				if ( trim($cc != '') )
				{
					$cur_state = 1;
				}
			}
			
			switch ($cc)
			{
				//-----------------------------------------
				// Tab and we're in a value?
				//-----------------------------------------
				case "\t":
					if ( $cur_state == 2 )
					{
						$dump_value .= $cc;
					}
					else
					{
						$cc = '';
					}
					break;
				//-----------------------------------------
				// Newlines..
				//-----------------------------------------
				case "\n":
				case "\r":
					$cc = '';
					break;
				//-----------------------------------------
				// Param value
				//-----------------------------------------
				case '=':
					if ( $cur_state == 2 )
					{
						$dump_value .= $cc;
					}
					else
					{
						$cur_state    = 2;
						$quote_type   = '';
						$query_entity = false;
					}
					break;
				//-----------------------------------------
				// Quoted
				//-----------------------------------------
				case '"':
					if ($cur_state == 2)
					{
						if ($quote_type == '')
						{
							$quote_type = '"';
						}
						else
						{
							if ($quote_type == $cc)
							{
								$attr_array[ trim($dump_key) ] = trim($dump_value);
								$dump_key  = $dump_value = $quote_type = '';
								$cur_state = 0;
							}
							else
							{
								$dump_value .= $cc;
							}
						}
					}
					break;
				//-----------------------------------------
				// Quoted
				//-----------------------------------------
				case "'":
					if ($cur_state == 2)
					{
						if ($quote_type == '')
						{
							$quote_type = "'";
						}
						else
						{
							if ($quote_type == $cc)
							{
								$attr_array[ trim($dump_key) ] = trim($dump_value);
								$dump_key  = $dump_value = $quote_type = '';
								$cur_state = 0;
							}
							else
							{
								$dump_value .= $cc;
							}
						}
					}
					break;
				//-----------------------------------------
				// Entity?
				//-----------------------------------------
				case '&':
					$query_entity = true;
					$dump_value  .= $cc;
					break;
					
				default:
					if ($cur_state == 1)
					{
						$dump_key .= $cc;
					}
					else
					{
						$dump_value .= $cc;
					}
			}
		}

		return $attr_array;
	}	
	
	/**
	* Parse between element tags
	*
	* @access	private
	* @param	string		Text
	* @return	void
	*/
	private function _parseBetweenTags( $t )
	{
		if ( trim($t ) != '')
		{
			$this->_execCharacterData($t);
		}
	}
	
	/**
	* Exec character callback
	*
	* @access	private
	* @param	string		Data
	* @return	void
	*/
	private function _execCharacterData( $data )
	{
		call_user_func( $this->handler_character_data, $this, $data );
	}
	
	/**
	* Exec start callback
	*
	* @access	private
	* @param	string		Data
	* @return	void
	*/
	private function _execStartElement( $tagname, $attr )
	{
		call_user_func( $this->handler_start_element, $this, $tagname, $attr );
	}
	
	/**
	* Exec end callback
	*
	* @access	private
	* @param	string		Data
	* @return	void
	*/
	private function _execEndElement( $tagname )
	{
		call_user_func( $this->handler_end_element, $this, $tagname );
	}
	
	/**
	* Exec cdata callback
	*
	* @access	private
	* @param	string		Data
	* @return	void
	*/
	private function _execCdataElement( $data )
	{
		call_user_func( $this->handler_cdata_handler, $this, $data );
	}
	
	/**
	* Set the element handler
	*
	* @access	private
	* @param	string		start handler
	* @param	string		end handler
	* @return	void
	*/
	private function _myXmlSetElementHandler($startHandler, $endHandler)
	{
		$this->handler_start_element = $startHandler;
		$this->handler_end_element   = $endHandler;
	}
	
	/**
	* Set the data handler
	*
	* @access	private
	* @param	string		start handler
	* @param	string		end handler
	* @return	void
	*/
	private function _myXmlSetCharacterDataHandler($handler)
	{
		$this->handler_character_data = &$handler;
	}
	
	/**
	* Set the cdata handler
	*
	* @access	private
	* @param	string		start handler
	* @param	string		end handler
	* @return	void
	*/
	private function _myXmlSetCdataSectionHandler($handler)
	{
		$this->handler_cdata_handler = &$handler;
	}
	
	/**
	* Get nth character of text
	*
	* @access	private
	* @param	string		Text
	* @param	integer		Position to get
	* @return	string		Character
	*/
	private function _getNthCharFromEnd( $t, $i )
	{
		return $t{ ( strlen( $t ) - 1 - $i ) };
	}
}


?>