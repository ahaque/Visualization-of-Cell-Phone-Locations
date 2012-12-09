<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template comparison
 * Last Updated: $Date: 2009-02-18 12:02:42 -0500 (Wed, 18 Feb 2009) $
  *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4036 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_compare extends ipsAjaxCommand 
{
	/**
	 * Skin functions object handle
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * HTML Skin object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $html;
	
    /**
	 * Main executable
	 *
	 * @access	public
	 * @param	object	registry object
	 * @return	void
	 */
    public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );
		
		$this->skinFunctions = new skinDifferences( $registry );
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );
		
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
    		case 'css':
    			$this->_getCssDifferences();
    		break;
    		
			default:
    		case 'differences':
    			$this->_getDifferences();
    		break;
    	}
    }
    
    /**
     * Get differences of a CSS file
     *
     * @access	private
     * @return	void
     */
    private function _getCssDifferences()
    {
    	//-----------------------------------------
    	// Init
    	//-----------------------------------------
    	
    	$setID		= intval($this->request['setID']);
    	$fileID		= intval($this->request['file_id']);
    	
    	//-----------------------------------------
    	// Get requested
    	//-----------------------------------------
    	
    	$current	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_css', 'where' => 'css_id=' . $fileID ) );
    	
    	if( $current['css_id'] )
    	{
	    	//-----------------------------------------
	    	// Get the original
	    	//-----------------------------------------
	    	
	    	$original	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_css', 'where' => "css_set_id=0 AND css_group='{$current['css_group']}'" ) );
    	}
    	else
    	{
    		$original['css_content']	= $current['css_content'];
    	}

		//-----------------------------------------
		// Get Diff library
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classDifference.php' );
		$classDifference         = new classDifference();
		$classDifference->method = 'PHP';
		
		
		$difference = $classDifference->getDifferences( $original['css_content'], $current['css_content'] );
    	
		if( $classDifference->diff_found )
		{
			$difference = str_replace( "\n", "<br>", $difference );
			$difference = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$difference );
			$difference = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$difference );
			$difference = str_replace( "\t", "&nbsp; &nbsp; ", $difference );
		}
		else
		{
			$difference = htmlspecialchars($current['css_content']);
			$difference = str_replace( ' ', '&nbsp;', $difference );
			$difference = str_replace( "\r", '<br />', $difference );
			$difference = str_replace( "\n", '<br />', $difference );
			$difference = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$difference );
			$difference = str_replace( "\t", "&nbsp; &nbsp; ", $difference );
		}
		
		$this->returnHtml( $this->html->differenceResult( $difference ) );
    }
    
    /**
     * Get differences of a template
     *
     * @access	private
     * @return	void
     */
    private function _getDifferences()
    {
    	//-----------------------------------------
    	// Init
    	//-----------------------------------------
    	
    	$setID		= intval($this->request['setID']);
    	$templateID	= intval($this->request['template_id']);
    	
    	//-----------------------------------------
    	// Get requested
    	//-----------------------------------------
    	
    	$current	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'template_id=' . $templateID ) );
    	
    	if( $current['template_set_id'] )
    	{
	    	//-----------------------------------------
	    	// Get the original
	    	//-----------------------------------------
	    	
	    	$original	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "template_set_id=0 AND template_group='{$current['template_group']}' AND template_name='{$current['template_name']}'" ) );
    	}
    	else
    	{
    		$original['template_content']	= $current['template_content'];
    	}

		//-----------------------------------------
		// Get Diff library
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classDifference.php' );
		$classDifference         = new classDifference();
		$classDifference->method = 'PHP';
		
		
		$difference = $classDifference->getDifferences( $original['template_content'], $current['template_content'] );
    	
		if( $classDifference->diff_found )
		{
			$difference = str_replace( "\n", "<br>", $difference );
			$difference = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$difference );
			$difference = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$difference );
			$difference = str_replace( "\t", "&nbsp; &nbsp; ", $difference );
		}
		else
		{
			$difference = htmlspecialchars($current['template_content']);
			$difference = str_replace( ' ', '&nbsp;', $difference );
			$difference = str_replace( "\r", '<br />', $difference );
			$difference = str_replace( "\n", '<br />', $difference );
			$difference = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$difference );
			$difference = str_replace( "\t", "&nbsp; &nbsp; ", $difference );
		}
		
		$this->returnHtml( $this->html->differenceResult( $difference ) );
    }
 
}