<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * RSS handler: handles importing of RSS documents and exporting of RSS v2 documents
 * Last Updated: $Date: 2009-08-17 20:55:40 -0400 (Mon, 17 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Monday 5th May 2008 14:00
 * @version		$Revision: 314 $
 *
 * EXAMPLE: (CREATING AN RSS FEED)
 * <code>
 * $rss = new classRss();
 * 
 * $channel_id = $rss->createNewChannel( array( 'title'       => 'My RSS Feed',
 * 											  	 'link'        => 'http://www.mydomain.com/rss/',
 * 											     'description' => 'The latest news from my <blog>',
 * 											     'pubDate'     => $rss->formatDate( time() ),
 * 											     'webMaster'   => 'me@mydomain.com (Matt Mecham)' ) );
 * 											   
 * $rss->addItemToChannel( $channel_id, array( 'title'       => 'Hello World!',
 * 										     'link'        => 'http://www.mydomain.com/blog/helloworld.html',
 * 										     'description' => 'The first ever post!',
 * 										     'content'     => 'Hello world! This is the blog content',
 * 										     'pubDate'	   => $rss->formatDate( time() ) ) );
 * 										   
 * $rss->addItemToChannel( $channel_id, array( 'title'       => 'Second Blog!!',
 * 										     'link'        => 'http://www.mydomain.com/blog/secondblog.html',
 * 										     'description' => 'The second ever post!',
 * 										     'content'     => 'More content',
 * 										     'pubDate'	   => $rss->formatDate( time() ) ) );
 * 										   
 * $rss->addImageToChannel( $channel_id, array( 'title'     => 'My Image',
 * 											   'url'       => 'http://mydomain.com/blog/image.gif',
 * 											   'width'     => '110',
 * 											   'height'    => '400',
 * 											   'description' => 'Image title text' ) );
 * 											 
 * $rss->createRssDocument();
 * 
 * print $rss->rss_document;
 * </code>
 * EXAMPLE: (READ AN RSS FEED)
 * <code>
 * $rss = new classRss();
 *
 * $rss->parseFeedFromUrl( 'http://www.mydomain.com/blog/rss/' );
 *
 * foreach( $rss->rss_channels as $channel_id => $channel_data )
 * {
 * 	print "Title: ".$channel_data['title']."<br />";
 * 	print "Description; ".$channel_data['description']."<br />";
 * 	
 * 	foreach( $rss->rss_items[ $channel_id ] as $item_id => $item_data )
 * 	{
 * 		print "Item title: ".$item_data['title']."<br />";
 * 		print "Item URL: ".$item_data['link']."<br />";
 * 		print $item_data['content']."<hr>";
 * 	}
 * 	
 * 	print $rss->formatImage( $rss->rss_images[ $channel_id ] );
 * }
 * </code>
 */

if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

class classRss
{	
	/**
	* Class file management object
	*
	* @access	public
	* @var 		object
	*/
	public $classFileManagement;
	
	/**
	* DOC type
	*
	* @access	public
	* @var 		string
	*/
	public $doc_type 		= 'UTF-8';
	
	/**
	* Original DOC type
	*
	* @access	public
	* @var 		string
	*/
	public $orig_doc_type	= "";
	
	/**
	* Error capture
	*
	* @access	public
	* @var 		array
	*/
	public $errors 			= array();
	
	/**
	* Use sockets flag
	*
	* @access	public
	* @var 		integer
	*/
	public $use_sockets		= 0;
	
	/**#@+
	* Work item
	*
	* @access	private
	* @var 		integer 
	*/
	private $in_item		= 0;
	private $in_image		= 0;
	private $in_channel		= 0;
	public  $rss_count		= 0;
	public  $rss_max_show	= 3;
	private $cur_item		= 0;
	private $cur_channel	= 0;
	private $set_ttl		= 60;
	private $tag			= "";
	/**#@-*/
	
	/**#@+
	* RSS Items
	*
	* @access	private
	* @var 		array 
	*/
	public  $rss_items     = array();
	private $rss_headers   = array();
	private $rss_images    = array();
	private $rss_tag_names = array();
	/**#@-*/
	
	/**#@+
	* RSS Parse Items
	*
	* @access	private
	* @var 		string 
	*/
	private $rss_title;
	private $rss_description;
	private $rss_link;
	private $rss_date;
	private $rss_creator;
	private $rss_content;
	private $rss_category;
	private $rss_guid;
	/**#@-*/
	
	/**#@+
	* RSS Parse Images
	*
	* @access	private
	* @var 		string 
	*/
	private $rss_img_url;
	private $rss_img_title;
	private $rss_img_link;
	private $rss_img_width;
	private $rss_img_height;
	private $rss_img_desc;
	/**#@-*/
	
	/**#@+
	* RSS Channel items
	*
	* @access	private
	* @var 		string 
	*/
	private $rss_chan_title;
	private $rss_chan_link;
	private $rss_chan_desc;
	private $rss_chan_date;
	private $rss_chan_lang;
	/**#@-*/
	
	/**#@+
	* Create: Channels
	*
	* @access	private
	* @var 		array 
	*/
	private $channels       = array();
	private $items          = array();
	private $channel_images = array();
	/**#@-*/
	
	/**#@+
	* Set Authentication
	*
	* @access	public
	* @var 		strings 
	*/
	public $auth_req 			= 0;
	public $auth_user;
	public $auth_pass;
	/**#@-*/
	
	/**
	* Final RSS Document
	*
	* @access	public
	* @var		string
	*/
	public $rss_document		= '';
	
	/**
	* Convert char set
	*
	* @access	public
	* @var		integer
	*/
	public $convert_charset		= 0;
	
	/**
	* Convert newlines
	*
	* @access	public
	* @var		integer
	*/
	public $collapse_newlines	= 0;
	
	/**
	* Destination charset
	*
	* @access	public
	* @var		string
	*/
	public $destination_charset	= 'UTF-8';
	
	/**
	* Feed char set
	*
	* @access	public
	* @var		string
	*/
	public $feed_charset		= 'UTF-8';

	/**
	* Constructor
	*
	* @access	public
	* @return	void
	*/
	public function __construct()
	{
		$this->rss_tag_names = array( 'ITEM'            => 'ITEM',
									  'IMAGE'           => 'IMAGE',
									  'URL'             => 'URL',
									  'CONTENT:ENCODED' => 'CONTENT:ENCODED',
									  'CONTENT'			=> 'CONTENT',
									  'DESCRIPTION'     => 'DESCRIPTION',
									  'TITLE'			=> 'TITLE',
									  'LINK'		    => 'LINK',
									  'CREATOR'         => 'CREATOR',
									  'PUBDATE'		    => 'DATE',
									  'DATE'		    => 'DATE',
									  'DC:CREATOR'      => 'CREATOR',
									  'DC:DATE'	        => 'DATE',
									  'DC:LANGUAGE'     => 'LANGUAGE',
									  'WEBMASTER'       => 'WEBMASTER',
									  'LANGUAGE'        => 'LANGUAGE',
									  'CHANNEL'         => 'CHANNEL',
									  'CATEGORY'	    => 'CATEGORY',
									  'GUID'			=> 'GUID',
									  'WIDTH'			=> 'WIDTH',
									  'HEIGHT'			=> 'HEIGHT',
									);
	}
	
	/**
	* Create the RSS document
	*
	* @access	public
	* @return	void
	*/
	public function createRssDocument( )
	{
		if ( ! count( $this->channels ) )
		{
			$this->errors[] = "No channels defined";
		}
		
		$this->rss_document  = '<?xml version="1.0" encoding="'.$this->doc_type.'" ?'.'>'."\n";
		$this->rss_document .= '<rss version="2.0">'."\n";
		
		//-------------------------------
		// Add channels
		//-------------------------------
		
		foreach( $this->channels as $idx => $channel )
		{
			$tmp_data = "";
			$had_ttl  = 0;
			
			//-------------------------------
			// Add channel data
			//-------------------------------
			
			foreach( $channel as $tag => $data )
			{
				if ( strtolower($tag) == 'ttl' )
				{
					$had_ttl = 1;
				}
				$tmp_data .= "\t<" . $tag . ">" . $this->_xmlEncodeString($data) . "</" . $tag . ">\n";
			}
			
			//-------------------------------
			// Added TTL?
			//-------------------------------
			
			if ( ! $had_ttl )
			{
				$tmp_data .= "\t<ttl>" . intval($this->set_ttl) . "</ttl>\n";
			}
			
			//-------------------------------
			// Got image?
			//-------------------------------
			
			if ( isset($this->channel_images[ $idx ]) AND is_array( $this->channel_images[ $idx ] ) AND count( $this->channel_images[ $idx ] ) )
			{
				foreach( $this->channel_images[ $idx ] as $image )
				{
					$tmp_data .= "\t<image>\n";
					
					foreach( $image as $tag => $data )
					{
						$tmp_data .= "\t\t<" . $tag . ">" . $this->_xmlEncodeString($data) . "</" . $tag . ">\n";
					}
					
					$tmp_data .= "\t</image>\n";
				}
			}
			
			//-------------------------------
			// Add item data
			//-------------------------------
			
			if ( is_array( $this->items[ $idx ] ) and count( $this->items[ $idx ] ) )
			{
				foreach( $this->items[ $idx ] as $item )
				{
					$tmp_data .= "\t<item>\n";
					
					foreach( $item as $tag => $data )
					{
						$extra = "";
						
						if ( $tag == 'guid' AND ! strstr( $data, 'http://' ) )
						{
							$extra = ' isPermaLink="false"';
						}
						
						$tmp_data .= "\t\t<" . $tag . $extra . ">" . $this->_xmlEncodeString($data) . "</" . $tag . ">\n";
					}
					
					$tmp_data .= "\t</item>\n";
				}
			}
			
			//-------------------------------
			// Put it together...
			//-------------------------------
			
			$this->rss_document .= "<channel>\n";
			$this->rss_document .= $tmp_data;
			$this->rss_document .= "</channel>\n";
		}
		
		$this->rss_document .= "</rss>";
		
		//-------------------------------
		// Clean up
		//-------------------------------
		
		$this->channels       = array();
		$this->items          = array();
		$this->channel_images = array();
	}
	
	/**
	* Create RSS 2.0 document: Add channel
	*
	* title, link, description,language,pubDate,lastBuildDate,docs,generator
	* managingEditor,webMaster
	*
	* @access	public
	* @param	array 		Data to add
	* @return	integer		New channel ID
	*/
	public function createNewChannel( $in=array() )
	{
		$this->channels[ $this->cur_channel ] = $in;
		
		//-------------------------------
		// Inc. and return
		//-------------------------------
		
		$return = $this->cur_channel;
		
		$this->cur_channel++;
		
		return $return;
	}
	
	/**
	* Create RSS 2.0 document: Add channel image item
	*
	* url, link, title, width, height, description
	*
	* @access	public
	* @param	integer		Channel ID
	* @param	array		Array of image variables
	* @return	void
	*/
	public function addImageToChannel( $channel_id=0, $in=array() )
	{
		$this->channel_images[ $channel_id ][] = $in;
	}
	
	/**
	* Create RSS 2.0 document: Add item
	*
	* title,description,pubDate,guid,content,category,link
	*
	* @access	public
	* @param	integer 	Channel ID
	* @param	array		Array of item variables
	* @return	void
	*/
	public function addItemToChannel( $channel_id=0, $in=array() )
	{
		$this->items[ $channel_id ][] = $in;
	}
	
	/**
	* Create RSS 2.0 document: Format Image
	*
	* @access	public
	* @param	array		Array of item variables
	* @return	string		Image HTML
	*/
	public function formatImage( $in=array() )
	{
		if ( ! $in['url'] )
		{
			$this->errors[] = "Cannot format image, not enough input";
		}
		
		$title  = "";
		$alt    = "";
		$width  = "";
		$height = "";
		
		if ( $in['description'] )
		{
			$title = " title='".$this->_xmlEncodeAttribute( $in['description'] )."' ";
		}
		
		if ( $in['title'] )
		{
			$alt = " alt='".$this->_xmlEncodeAttribute( $in['title'] )."' ";
		}
		
		if ( $in['width'] )
		{
			if ( $in['width'] > 144 )
			{
				$in['width'] = 144;
			}
			
			$width = " width='".$this->_xmlEncodeAttribute( $in['width'] )."' ";
		}
		
		if ( $in['height'] )
		{
			if ( $in['height'] > 400 )
			{
				$in['height'] = 400;
			}
			
			$height = " height='".$this->_xmlEncodeAttribute( $in['height'] )."' ";
		}
		
		//-------------------------------
		// Draw image
		//-------------------------------
		
		$img = "<img src='".$in['url']."' $title $alt $width $height />";
		
		//-------------------------------
		// Linked?
		//-------------------------------
		
		if ( $in['link'] )
		{
			$img = "<a href='".$in['link']."'>".$img."</a>";
		}
		
		return $img;
	}
	
	/**
	* Create RSS 2.0 document: Format unixdate to rfc date
	*
	* @access	public
	* @param	integer		Unix timestamp
	* @return	string		Formatted date RFC date
	*/
	public function formatDate( $time )
	{
		return date( 'r', $time );
	}
	
	/**
	* Extract: Parse RSS document from URL
	*
	* @access	public
	* @param	string		URI
	* @return	void
	*/
	public function parseFeedFromUrl( $feed_location )
	{
		//-----------------------------------------
		// Load file management class
		//-----------------------------------------
		
		require_once( IPS_CLASSES_PATH.'/classFileManagement.php' );
		$this->classFileManagement = new classFileManagement();
		
		$this->classFileManagement->use_sockets = $this->use_sockets;
		
		$this->classFileManagement->auth_req  = $this->auth_req;
		$this->classFileManagement->auth_user = $this->auth_user;
		$this->classFileManagement->auth_pass = $this->auth_pass;
		
		//-------------------------------
		// Reset arrays
		//-------------------------------
		
		$this->rss_items    = array();
		$this->rss_channels = array();
		
		//-------------------------------
		// Get data
		//-------------------------------
		
		$data = $this->classFileManagement->getFileContents( $feed_location );
		
		if ( count( $this->classFileManagement->errors ) )
		{
			$this->errors = $this->classFileManagement->errors;
			@xml_parser_free($xml_parser); // Let's kill the parser before we return
			return FALSE;
		}
		
		if( preg_match( "#encoding=[\"'](\S+?)[\"']#si", $data, $matches ) )
		{
			$this->orig_doc_type = $matches[1];
		}
		
		if( preg_match( "#charset=(\S+?)#si", $data, $matches ) )
		{
			$this->orig_doc_type = $matches[1];
		}
		
		//-----------------------------------------
		// Charset conversion?
		// We convert the actual content in the parse
		//	routines elsewhere, this causes issues
		//-----------------------------------------
		
		$supported_encodings = array( "utf-8", "iso-8859-1", "us-ascii" );
		$charset = in_array( strtolower($this->feed_charset), $supported_encodings ) ? $this->feed_charset : "";

		/*if ( $this->convert_charset AND $data )
		{
			if ( $this->feed_charset != $this->doc_type )
			{
				$data = IPSText::convertCharsets( $data, $this->feed_charset, $this->doc_type );
				
				# Replace any char-set= data
				$data = preg_replace( "#encoding=[\"'](\S+?)[\"']#si", "encoding=\"".$this->doc_type."\"", $data );
				$data = preg_replace( "#charset=(\S+?)#si"           , "charset=".$this->doc_type        , $data );
			}
		}*/
		
		//-------------------------------
		// Generate XML parser
		//-------------------------------

		$xml_parser = xml_parser_create( $charset );
		xml_set_element_handler(       $xml_parser, array( &$this, "_parseStartElement" ), array( &$this, "_parseEndElement") );
		xml_set_character_data_handler($xml_parser, array( &$this, "_parseCharacterData" ) );
		
		//-------------------------------
		// Parse data
		//-------------------------------
		
		if ( ! xml_parse( $xml_parser, $data ) )
		{
			$this->errors[] = sprintf("XML error: %s at line %d",  xml_error_string( xml_get_error_code($xml_parser) ), xml_get_current_line_number($xml_parser) );
		}
		
		//-------------------------------
		// Free memory used by XML parser
		//-------------------------------
		
		@xml_parser_free($xml_parser);
	}
	
	/**
	* Extract: Parse RSS document from file
	*
	* @access	public
	* @param	string		Path
	* @return	void
	*/
	public function parseFeedFromFile( $feed_location )
	{
		//-------------------------------
		// Alias...
		//-------------------------------
		
		$this->parseFeedFromUrl( $feed_location );
	}
	
	/**
	* Extract: Parse RSS document from data
	*
	* @access	public
	* @param	string		Raw RSS data
	* @return	void
	*/
	public function parseFeedFromData( $data )
	{
		//-------------------------------
		// Reset arrays
		//-------------------------------
		
		$this->rss_items    = array();
		$this->rss_channels = array();
		$this->cur_channel  = 0;
		
		//-------------------------------
		// Generate XML parser
		//-------------------------------
		
		$xml_parser = xml_parser_create( $this->doc_type );
		xml_set_element_handler(       $xml_parser, array( &$this, "_parseStartElement" ), array( &$this, "_parseEndElement") );
		xml_set_character_data_handler($xml_parser, array( &$this, "_parseCharacterData" ) );
		
		
		if ( ! xml_parse( $xml_parser, $data, TRUE ) )
		{
			$this->errors[] = sprintf("XML error: %s at line %d",  xml_error_string( xml_get_error_code($xml_parser) ), xml_get_current_line_number($xml_parser) );
		}
		
		//-------------------------------
		// Free memory used by XML parser
		//-------------------------------
		
		xml_parser_free($xml_parser);
	}
	
	/**
	* Extract: Call back function for element handler
	*
	* @access	private
	* @param	object		Parser object
	* @param	string		Tag name
	* @param	array		Attributes
	* @return	void
	*/
	private function _parseStartElement( $parser, $name, $attrs )
	{
		//-------------------------------
		// Just in case
		//-------------------------------
		
		$name = strtoupper($name);
		
		if ( $this->in_item )
		{
			$this->in_item++;
			$this->tag = $this->rss_tag_names[ $name ];
		}
		
		if ( $this->in_image )
		{
			$this->in_image++;
			$this->tag = $this->rss_tag_names[ $name ];
		}
		
		if ( $this->in_channel )
		{
			$this->in_channel++;
			$this->tag = isset($this->rss_tag_names[ $name ]) ? $this->rss_tag_names[ $name ] : '';
		}
		
		if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "ITEM" )
		{
			$this->in_item = 1;
		} 
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "IMAGE")
		{
			$this->in_image = 1;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "CHANNEL")
		{
			$this->in_channel = 1;
		}
	}
	
	/**
	* Extract: Call back function for element handler
	*
	* @access	private
	* @param	object		Parser object
	* @param	string		Tag name
	* @return	void
	*/
	private function _parseEndElement( $parser, $name )
	{
		//-------------------------------
		// Just in case
		//-------------------------------
		
		$name = strtoupper($name);
		
		if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "IMAGE" )
		{
			$this->rss_images[ $this->cur_channel ]['url']         = $this->rss_img_image;
			$this->rss_images[ $this->cur_channel ]['title']       = $this->rss_img_title;
			$this->rss_images[ $this->cur_channel ]['link']        = $this->rss_img_link;
			$this->rss_images[ $this->cur_channel ]['width']       = $this->rss_img_width;
			$this->rss_images[ $this->cur_channel ]['height']      = $this->rss_img_height;
			$this->rss_images[ $this->cur_channel ]['description'] = $this->rss_img_desc;
			
			$this->_killImageElements();
			$this->in_image = 0;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "CHANNEL" )
		{
			//-------------------------------
			// Add data
			//-------------------------------
			
			$this->rss_channels[ $this->cur_channel ]['title']       = $this->_formatString($this->rss_chan_title);
			$this->rss_channels[ $this->cur_channel ]['link']        = $this->_formatString($this->rss_chan_link);
			$this->rss_channels[ $this->cur_channel ]['description'] = $this->_formatString($this->rss_chan_desc);
			$this->rss_channels[ $this->cur_channel ]['date']        = $this->_formatString($this->rss_chan_date);
			$this->rss_channels[ $this->cur_channel ]['unixdate']    = @strtotime($this->_formatString($this->rss_chan_date));
			$this->rss_channels[ $this->cur_channel ]['language']    = $this->_formatString($this->rss_chan_lang);
			
			//-------------------------------
			// Increment item
			//-------------------------------
			
			$this->cur_channel++;
			
 			//-------------------------------
			// Clean up
			//-------------------------------
			
			$this->_killChannelElements();
			$this->in_channel = 0;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "ITEM" )
		{
			if ( $this->rss_count < $this->rss_max_show )
			{
				$this->rss_count++;
				
				//-------------------------------
				// Kludge for RDF which closes
				// channel before first item
				// I'm staring at you Typepad
				//-------------------------------
				
				if ( $this->cur_channel > 0 AND ( ! is_array($this->rss_items[ $this->cur_channel ] ) ) )
				{
					$this->cur_channel--;
				}
				
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['title']       = $this->rss_title;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['link']        = $this->rss_link;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['description'] = $this->rss_description;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['content']     = $this->rss_content;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['creator']     = $this->rss_creator;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['date']        = $this->rss_date;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['unixdate']    = trim($this->rss_date) != "" ? strtotime($this->rss_date) : time();
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['category']    = $this->rss_category;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['guid']        = $this->rss_guid;
				
				//-------------------------------
				// Increment item
				//-------------------------------
				
				$this->cur_item++;
				
				//-------------------------------
				// Clean up
				//-------------------------------
				
				$this->_killElements();
				$this->in_item = 0;
			}
			else if ($this->rss_count >= $this->rss_max_show)
			{
				//-------------------------------
				// Clean up
				//-------------------------------
				
				$this->_killElements();
				$this->in_item = 0;
			}
			
		}
		if ( $this->in_channel )
		{
			$this->in_channel--;
		}
		
		if ( $this->in_item )
		{
			$this->in_item--;
		}
		
		if ( $this->in_image )
		{
			$this->in_image--;
		}
	}
	
	/**
	* Extract: Call back function for element handler
	*
	* @access	private
	* @param	object		Parser object
	* @param	string		CDATA
	* @return	void
	*/
	private function _parseCharacterData( $parser, $data )
	{
		if ( $this->in_image == 2 )
		{
			switch ($this->tag)
			{
				case "URL":
					$this->rss_img_image .= $data;
					break;
				case "TITLE":
					$this->rss_img_title .= $data;
					break;
				case "LINK":
					$this->rss_img_link .= $data;
					break;
				case "WIDTH":
					$this->rss_img_width .= $data;
					break;
				case "HEIGHT":
					$this->rss_img_height .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_img_desc .= $data;
					break;
			}
		}
		
		if ( $this->in_item == 2)
		{
			switch ($this->tag)
			{
				case "TITLE":
					$this->rss_title .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_description .= $data;
					break;
				case "LINK":
					if ( ! is_string($this->rss_link) )
					{
						$this->rss_link = "";
					}
					$this->rss_link .= $data;
					break;
				case "CONTENT:ENCODED":
					$this->rss_content .= $data;
					break;
				case "CONTENT":
					$this->rss_content .= $data;
					break;
				case "DATE":
					$this->rss_date .= $data;
					break;
				case "DC:DATE":
					$this->rss_date .= $data;
					break;
				case "CREATOR":
					$this->rss_creator .= $data;
					break;
				case "CATEGORY":
					$this->rss_category .= $data;
					break;
				case "GUID":
					$this->rss_guid .= $data;
					break;
			}
		}
		
		if ( $this->in_channel == 2)
		{
			switch ($this->tag)
			{
				case "TITLE":
					$this->rss_chan_title .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_chan_desc .= $data;
					break;
				case "LINK":
					if ( ! is_string($this->rss_chan_link) )
					{
						$this->rss_chan_link="";
					}
					$this->rss_chan_link .= $data;
					break;
				case "DATE":
					$this->rss_chan_date .= $data;
					break;
				case "LANGUAGE":
					$this->rss_chan_lang .= $data;
					break;
			}
		}
	}
	
	/**
	* Internal: Encode attribute
	*
	* @access	private
	* @param	string		Raw Text
	* @return	string		Parsed Text
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
	* Internal: Dencode attribute
	*
	* @access	private
	* @param	string		Raw Text
	* @return	string		Parsed Text
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
	* Internal: Encode string
	*
	* @access	private
	* @param	string		Raw Text
	* @return	string		Parsed Text
	*/
	private function _xmlEncodeString( $v )
	{
		# Fix up encoded & " ' and any other funnky IPB data
		$v = str_replace( '&amp;'         , '&'          , $v );
		$v = str_replace( "&#60;&#33;--"  , "&lt!--"     , $v );
		$v = str_replace( "--&#62;"		  , "--&gt;"     , $v );
		$v = str_replace( "&#60;script"   , "&lt;script" , $v );
		$v = str_replace( "&quot;"        , "\""         , $v );
		$v = str_replace( "&#036;"        , '$'          , $v );
		$v = str_replace( "&#33;"         , "!"          , $v );
		$v = str_replace( "&#39;"         , "'"          , $v );
		
		if ( preg_match( "/['\"\[\]<>&]/", $v ) )
		{
			$v = "<![CDATA[" . $this->_xmlConvertSafeCdata($v) . "]]>";
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
	private function _xmlConvertSafeCdata( $v )
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
	* @access	private
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	private function _xmlUnconvertSafeCdata( $v )
	{
		# Legacy
		$v = str_replace( "<!¢|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|¢]>"      , "]]>"      , $v );
		
		# New
		$v = str_replace( "<!#^#|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|#^#]>"      , "]]>"      , $v );
		
		return $v;
	}
	
	/**
	* Format text string
	*
	* @access	private
	* @param	string		Raw data
	* @return	string		Converted Data
	*/
	private function _formatString( $t )
	{
		return trim( $t );
	}
	
	/**
	* Internal: Reset arrays
	*
	* @access	private
	* @return	void
	*/
	private function _killElements()
	{
		$this->rss_link        = "";
		$this->rss_title       = "";
		$this->rss_description = "";
		$this->rss_content     = "";
		$this->rss_date        = "";
		$this->rss_creator     = "";
		$this->rss_category    = "";
		$this->rss_guid        = "";
	}
	
	/**
	* Internal: Reset arrays
	*
	* @access	private
	* @return	void
	*/
	private function _killImageElements()
	{
		$this->rss_img_image  = "";
		$this->rss_img_title  = "";
		$this->rss_img_link   = "";
		$this->rss_img_width  = "";
		$this->rss_img_height = "";
		$this->rss_img_desc   = "";
	}
	
	/**
	* Internal: Reset arrays
	*
	* @access	private
	* @return	void
	*/
	private function _killChannelElements()
	{
		$this->rss_chan_title = "";
		$this->rss_chan_link  = "";
		$this->rss_chan_desc  = "";
		$this->rss_chan_date  = "";
	}

}

?>