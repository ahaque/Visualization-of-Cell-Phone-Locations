<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Custom fields library
 * Last Updated: $Date: 2009-07-29 21:58:46 -0400 (Wed, 29 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Services Kernel
 * @link		http://www.
 * @since		Thursday 8th May 2008 10:31
 * @version		$Revision: 303 $
 *
 */
 
abstract class customFieldPlugin
{
	/**
	 * Custom field name
	 *
	 * @access	public
	 * @var		string
	 **/
	public $name       = '';
	
	/**
	 * Custom field title
	 *
	 * @access	public
	 * @var		string
	 **/
	public $title      = '';
	
	/**
	 * Custom field id
	 *
	 * @access	public
	 * @var		integer
	 **/
	public $id         = 0;
	
	/**
	 * Custom field attributes
	 *
	 * @access	public
	 * @var		array
	 **/
	public $attributes = array();
	
	/**
	 * Custom field value
	 *
	 * @access	public
	 * @var		array
	 **/
	public $raw_data   = array();
	
	/**
	 * Custom field mode
	 *
	 * @access	public
	 * @var		string
	 **/
	public $mode       = '';
	
	/**
	 * Allows you to treat a custom field type as a string
	 *
	 * @access	public
	 * @return	string		Current value of $this->parsed
	 **/		
	public function __tostring()
	{
		return $this->getValue();
	}
	
	/**
	 * Gets the value of the field
	 *
	 * @access	public
	 * @return	string		Current value of $this->parsed
	 **/		
	public function getValue()
	{
		if( is_array( $this->parsed ) )
		{
			return implode( ",", $this->parsed );			
		}
		else
		{
			return $this->parsed;
		}		
	}
	
	/**
	 * Creates an attribute string from an array
	 *
	 * @access	protected
	 * @return	string
	 **/	
	protected function _compileAttributes()
	{
		$_attributes = '';
		
		if( is_array( $this->attributes ) )
		{
			foreach( $this->attributes as $k => $v )
			{
				$_attributes .= " {$k}='{$v}'";
			}
		}
		
		return $_attributes;
	}
	
	/**
	 * Removes harmful html from display
	 *
	 * @access	protected
	 * @var		string		String to make safe
	 * @return	string
	 **/
	protected function makeSafeForView( $t )
	{
		$t = htmlspecialchars( $t, ENT_QUOTES );
		$t = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $t );
		$t = nl2br( $t );
		
		return $t;
	}
	
	/**
	 * Format value for display in a form field
	 *
	 * @access	protected
	 * @var		string		String to make safe
	 * @return	string
	 **/	
	protected function makeSafeForForm( $t )
	{
		return str_replace( "'", "&#39;", $t );
	}	
}

/**
 * Interface for custom fields
 *
 **/
interface interfaceCustomFieldPlugin
{
	/**
	 * Editing a custom field
	 *
	 * @access	public
	 * @return	void
	 **/
	public function edit();
	
	/**
	 * Viewing a custom field
	 *
	 * @access	public
	 * @return	void
	 **/
	public function view();
}

/**
 * Text input custom fields
 *
 **/
class customFieldText extends customFieldPlugin implements interfaceCustomFieldPlugin
{
	/**
	 * Plugin type
	 *
	 * @access	public
	 * @var		string
	 **/
	public $plugin_type = '';
	
	/**
	 * Value
	 *
	 * @access	public
	 * @var		string
	 **/
	public $value       = '';
	
	/**
	 * Parsed value
	 *
	 * @access	public
	 * @var		string
	 **/
	public $parsed      = '';
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @param	array 	$field	Array of field data { id, data, value, class }
	 * @param	string	$mode	Set to 'view' or 'edit'
	 * @return	void
	 **/
	public function __construct( $field, $mode )
	{
		/* INI */
		$this->value       = $field['value'];
		$this->plugin_type = $field['type'];
		$this->name        = 'field_' . $field['id'];
		$this->id          = $field['id'];
		$this->title       = isset( $field['title'] ) ? $field['title'] : '';
		$this->attributes  = isset( $field['attributes'] ) ? $field['attributes'] : '';
		$this->raw_data    = $field;
		$this->mode        = $mode;
		
		if( $mode == 'edit' )
		{
			$this->edit();
		}
		else
		{
			$this->view();
		}
	}
	
	/**
	 * edit a field
	 *
	 * @access	public
	 * @return	void
	 **/
	public function edit()
	{
		/* Attributes */
		$_attributes = $this->_compileAttributes();
		
		/* Make Safe */
		$this->value = $this->makeSafeForForm( $this->value );
	
		if( $this->plugin_type == 'input' )
		{
			$this->parsed = "<input type='text' id='{$this->name}' size='40' class='input_text' name='{$this->name}' value='{$this->value}'{$_attributes}/>";
		}
		else
		{
			$this->parsed = "<textarea id='{$this->name}' class='input_text' cols='60' rows='4' name='{$this->name}'{$_attributes}>{$this->value}</textarea>";
		}
	}
	
	/**
	 * view a field
	 *
	 * @access	public
	 * @return	void
	 **/	
	public function view()
	{
		/* Make Safe */
		$this->value = $this->makeSafeForView( $this->value );
		$this->parsed = $this->value;
	}
}

/**
 * Radio button custom fields
 *
 **/
class customFieldRadio extends customFieldPlugin implements interfaceCustomFieldPlugin
{
	/**
	 * Plugin type
	 *
	 * @access	public
	 * @var		string
	 **/
	public $plugin_type = 'radio';
	
	/**
	 * Value
	 *
	 * @access	public
	 * @var		string
	 **/
	public $value       = '';
	
	/**
	 * Parsed value
	 *
	 * @access	public
	 * @var		string
	 **/
	public $parsed      = '';

	/**
	 * Data
	 *
	 * @access	public
	 * @var		string
	 **/
	public $data        = '';
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @param	array 	$field	Array of field data { id, data, value, class }
	 * @param	string	$mode	Set to 'view' or 'edit'
	 * @return	void
	 **/
	public function __construct( $field, $mode )
	{
		/* INI */
		$this->data       = $field['data'];
		$this->value      = $field['value'];
		$this->name       = 'field_' . $field['id'];
		$this->id         = $field['id'];
		$this->title      = $field['title'];
		$this->attributes = $field['attributes'];
		$this->raw_data   = $field;
		$this->mode       = $mode;
		
		if( $mode == 'edit' )
		{
			$this->edit();
		}
		else
		{
			$this->view();
		}
	}
	
	/**
	 * edit a field
	 *
	 * @access	public
	 * @return	void
	 **/
	public function edit()
	{
		/* Attributes */
		$_attributes = $this->_compileAttributes();

		$carray     = explode( '|', $this->data );

		foreach( $carray as $entry )
		{
			$value = explode( '=', $entry );
			
			$ov = trim( $value[0] );
			$td = trim( $value[1] );
			
			/* Make Safe */
			$this->value = $this->makeSafeForForm( $this->value );			
			
			if( $this->value == $ov and $this->value )
			{
				$this->parsed .= "<input type='radio' class='input_radio' id='{$this->name}_{$ov}' name='{$this->name}' value='$ov' checked='checked'{$_attributes}/> $td\n";
			}
			else
			{
				$this->parsed .= "<input type='radio' class='input_radio' id='{$this->name}_{$ov}' name='{$this->name}' value='$ov'{$_attributes}/> $td\n";
			}
		}
	}
	
	/**
	 * view a field
	 *
	 * @access	public
	 * @return	void
	 **/	
	public function view()
	{
		/* INI */
		$carray      = explode( '|', $this->data );
		$curr_values = ( is_array( $this->value ) ) ? $this->value : array( $this->value );
		
		/* Make Safe */
		foreach( $curr_values as $k => $v  )
		{
			$curr_values[$k] = $this->makeSafeForView( $v );
		}
	
		foreach( $carray as $entry )
		{
			$value = explode( '=', $entry );
			
			if( in_array( trim( $value[0] ), $curr_values ) )
			{
				$this->parsed = trim( $value[1] );
			}			
		}		
	}
}

/**
 * Dropdown custom fields
 *
 **/
class customFieldDrop extends customFieldPlugin implements interfaceCustomFieldPlugin
{
	/**
	 * Plugin type
	 *
	 * @access	public
	 * @var		string
	 **/
	public $plugin_type = 'drop';
	
	/**
	 * Value
	 *
	 * @access	public
	 * @var		string
	 **/
	public $value       = '';
	
	/**
	 * Parsed value
	 *
	 * @access	public
	 * @var		string
	 **/
	public $parsed      = '';

	/**
	 * Data
	 *
	 * @access	public
	 * @var		string
	 **/
	public $data        = '';
	
	/**
	 * Allow multi-input
	 *
	 * @access	private
	 * @var		boolean
	 **/
	private $multi       = false;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @param	array	$field	Array of field data { id, data, value, class }
	 * @param	string	$mode	Set to 'view' or 'edit'
	 * @return	void
	 **/
	public function __construct( $field, $mode )
	{
		/* INI */
		$this->data        = $field['data'];
		$this->value       = $field['value'];
		$this->multi       = ( isset( $field['multi'] ) && $field['multi'] == 1 ) ? 1 : 0;
		$this->name        = $this->multi ? 'field_' . $field['id'] . '[]' : 'field_' . $field['id'];		
		$this->id          = $field['id'];
		$this->title       = isset( $field['title'] ) ? $field['title'] : '';
		$this->attributes  = isset( $field['attributes'] ) ? $field['attributes'] : '';
		$this->raw_data    = $field;
		$this->mode        = $mode;
		
		if( $mode == 'edit' )
		{
			$this->edit();
		}
		else
		{
			$this->view();
		}
	}
	
	/**
	 * edit a field
	 *
	 * @access	public
	 * @return	void
	 **/
	public function edit()
	{
		/* INI */
		$carray      = explode( '|', $this->data );
		$multi       = ( isset( $this->multi ) && $this->multi ) ? " multiple='multiple'" : '';
		$def_values  = ( is_array( $this->value ) ) ? $this->value : array( $this->value );
		$_attributes = $this->_compileAttributes();
		
		/* Make Safe */
		foreach( $def_values as $k => $v  )
		{
			$def_values[$k] = $this->makeSafeForForm( $v );
		}		
		
		/* Start the select tag */
		$this->parsed = "<select id='{$this->name}' class='input_select' name='{$this->name}'{$multi}{$_attributes}>\n";
		
		foreach( $carray as $entry )
		{
			$value = explode( '=', $entry );
			
			$ov = trim( $value[0] );
			$td = trim( $value[1] );
						
			if( in_array( $ov, $def_values ) )
			{
				$this->parsed .= "<option value='$ov' selected='selected'>$td</option>\n";
			}
			else
			{
				$this->parsed .= "<option value='$ov'>$td</option>\n";
			}
		}
		
		/* End the tag */
		$this->parsed .= "</select>\n";
	}
	
	/**
	 * view a field
	 *
	 * @access	public
	 * @return	void
	 **/	
	public function view()
	{
		/* INI */
		$carray      = explode( '|', $this->data );
		$curr_values = ( is_array( $this->value ) ) ? $this->value : array( $this->value );
		
		/* Make Safe */
		foreach( $curr_values as $k => $v  )
		{
			$curr_values[$k] = $this->makeSafeForView( $v );
		}		
		
		foreach( $carray as $entry )
		{
			$value = explode( '=', $entry );
			
			if( in_array( trim( $value[0] ), $curr_values ) )
			{
				$this->parsed[] = trim( $value[1] );
			}			
		}
	}
}

/**
 * Primary custom fields class
 *
 **/
class classCustomFields
{
	/**
	 * List of fields
	 *
	 * @access	private
	 * @var		array
	 **/	
	private $field_list     = array();
	
	/**
	 * Loaded plugins
	 *
	 * @access	private
	 * @var		array
	 **/
	public $loaded_plugins = array();
	
	/**
	 * Mode
	 *
	 * @access	private
	 * @var		string
	 **/
	private $mode           = '';
	
	/**
	 * Custom Fields
	 *
	 * @access	public
	 * @var		array
	 **/	
	public $cfields 		= array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @param	array 		Field data
	 * @param	string		Mode
	 * @return	void
	 **/	
	public function __construct( $field_data, $mode='view' )
	{
		/* Setup */
		$this->field_list 	= $field_data;
		$this->mode 		= $mode;

		$this->loaded_plugins['drop']  = 'customFieldDrop';
		$this->loaded_plugins['radio'] = 'customFieldRadio';
		$this->loaded_plugins['text']  = 'customFielText';
		
		/* Loop through and build custom fields */
		foreach( $this->field_list as $field )
		{
			$this->cfields[ $field['id'] ] = $this->parseField( $field );
		}
	}
	
	/**
	 * Returns an array of supported field types
	 *
	 * @access	public
	 * @return	array
	 **/	
	public function getFieldTypes()
	{
		return array( 
						array( 'drop'     , ipsRegistry::instance()->getClass('class_localization')->words['cf_drop'] ),
						array( 'radio'    , ipsRegistry::instance()->getClass('class_localization')->words['cf_radio'] ),
						array( 'input'    , ipsRegistry::instance()->getClass('class_localization')->words['cf_input'] ),
						array( 'textarea' , ipsRegistry::instance()->getClass('class_localization')->words['cf_textarea'] ),
					);
	}
	
	/**
	 * Parses a submitted form for custom fields
	 *
	 * @access	public
	 * @param	array		Array of field data
	 * @return	array 		( 'save_array', 'errors' )
	 **/	
	public function getFieldsToSave( $input_array )
	{
		/* INI */
		$save_data = array();
		$errors    = array();

		/* Loop through all the fields */
		foreach( $this->field_list as $field )
		{
			/* Submitted Value */
			$submit_value = $this->formatTextToSave( $input_array[ 'field_' . $field['id'] ] );
			
			if( ! is_array( $submit_value ) )
			{
				$submit_value = trim( $submit_value );
			}

			/* Check for restrictions */
			if( isset( $field['restrictions'] ) && is_array( $field['restrictions'] ) )
			{
				/* Size Restriction */
				if( $field['restrictions']['max_size'] && IPSText::mbstrlen( $submit_value ) > $field['restrictions']['max_size'] )
				{
					$errors[ 'field_' . $field['id'] ][] = 'too_big';
				}
				
				/* Size Restriction */
				if( $field['restrictions']['min_size'] && IPSText::mbstrlen( $submit_value ) < $field['restrictions']['min_size'] )
				{
					$errors[ 'field_' . $field['id'] ][] = 'too_small';
				}				
				
				/* Null restriction */
				if( $field['restrictions']['not_null'] && ( (string)$submit_value !== '0' AND !$submit_value) )
				{
					$errors[ 'field_' . $field['id'] ][] = 'empty';
				}
				
				/* Format Restriction */
				if( $field['restrictions']['format'] && $submit_value )
				{
					$regex = str_replace( 'n', '\\d', preg_quote( $field['restrictions']['format'], "#" ) );
					$regex = str_replace( 'a', '\\w', $regex );
					
					if ( ! preg_match( "#^".$regex."$#i", $submit_value ) )
					{
						$errors[ 'field_' . $field['id'] ][] = 'invalid';
					}
				}				
			}
			
			/* Add to save */
			$this->field_list[$field['id']]['value'] = $submit_value;
			$save_data[ 'field_' . $field['id'] ]    = $submit_value;
		}
		
		return array( 'save_array' => $save_data, 'errors' => $errors );
	}
	
	/**
	 * Parsed a field for display
	 *
	 * @access	private
	 * @param	array	$field		Array of field data
	 * @param	string	[$mode]		View/Edit, or blank for class default
	 * @return	object
	 **/	
	private function parseField( $field, $mode='' )
	{
		/* INI */
		$mode = ( $mode ) ? $mode : $this->mode;
		
		/* Check the type of field */
		if( isset( $this->loaded_plugins[$field['type']] ) && class_exists( $this->loaded_plugins[$field['type']] ) )
		{
			return new $this->loaded_plugins[$field['type']]( $field, $mode );
		}
		else
		{
			return new customFieldText( $field, $mode );
		}
	}
	
	/**
	 * Formats the content field for a textarea
	 *
	 * @access	public
	 * @param	string		Content to format
	 * @return	string
	 **/	
	public function formatContentForEdit( $c )
	{
		return str_replace( '|', "\n", $c );
	}
	
	/**
	 * Formats the content field for saveing in the database
	 *
	 * @access	public
	 * @param	string		Content to format
	 * @return	string
	 **/	
	public function formatContentForSave( $c )
	{
		$c = str_replace( "\r"   , "\n", $c );
		$c = str_replace( "&#39;", "'" , $c );
		return str_replace( "\n", '|', str_replace( "\n\n", "\n", trim($c) ) );
	}
	
	/**
	 * Foramts text for saving into the database
	 *
	 * @access	public
	 * @var		string		String to make safe
	 * @return	string
	 **/
	private function formatTextToSave( $t )
	{
		$t = str_replace( "<br>"  , "\n", $t );
		$t = str_replace( "<br />", "\n", $t );
		$t = str_replace( "&#39;" , "'" , $t );
		
		if ( @get_magic_quotes_gpc() )
		{
			$t = stripslashes($t);
		}
		
		return $t;
	}	
}