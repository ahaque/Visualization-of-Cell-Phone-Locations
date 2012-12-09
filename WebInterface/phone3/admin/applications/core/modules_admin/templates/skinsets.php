<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin set management
 * Last Updated: $Date: 2009-08-20 18:20:40 -0400 (Thu, 20 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 5035 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_skinsets extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * Recursive depth guide
	 *
	 * @access	private
	 * @var		array
	 */
	private $_depthGuide = array();
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**#@+
	 * URL bits
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=skinsets';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=skinsets';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		
		$this->skinFunctions = new skinCaching( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_listSets();
			break;
			case 'setAdd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setForm('add');
			break;
			case 'setEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setForm('edit');
			break;
			case 'setAddDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setSave( 'add' );
			break;
			case 'setEditDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setSave( 'edit' );
			break;
			case 'setWriteMaster':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setWriteMaster();
			break;
			case 'setWriteMasterCss':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setWriteMasterCss();
			break;
			case 'setRemoveSplash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_setRemoveSplash();
			break;
			case 'setRemove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_setRemove();
			break;
			case 'revertSplash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_revertSplash();
			break;
			case 'setRevert':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_setRevert();
			break;
			case 'makeDefault':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_makeDefault();
			break;
			case 'toggleHidden':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_toggleHidden();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Toggle a skin set's hidden status
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _toggleHidden()
	{
		/* INIT */
		$set_id  = intval( $this->request['set_id'] );
		$skinSet = $this->skinFunctions->fetchSkinData( $set_id );
	
		/* Toggle.. */
		$this->DB->update( 'skin_collections', array( 'set_hide_from_list' => ( $skinSet['set_hide_from_list'] ) ? 0 : 1 ), 'set_is_default=0 AND set_id=' . $set_id );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		
		$this->registry->output->global_message = $this->lang->words['ss_hiddentoggled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Make a skin set default for output engine
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _makeDefault()
	{
		/* INIT */
		$set_id  = intval( $this->request['set_id'] );
		$skinSet = $this->skinFunctions->fetchSkinData( $set_id );
		
		/* Make none default.. */
		$this->DB->update( 'skin_collections', array( 'set_is_default' => 0 ), 'set_output_format="' . $skinSet['set_output_format'] . '"' );
		
		/* Make this one default.. */
		$this->DB->update( 'skin_collections', array( 'set_is_default' => 1 ), 'set_id=' . $set_id );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		
		$this->registry->output->global_message = $this->lang->words['ss_defaultdone'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Remove customizations from a skin set
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _setRevert()
	{
		$set_id       = intval( $this->request['setID'] );
		$authKey      = $this->request['authKey'];
		$setData      = $this->skinFunctions->fetchSkinData( $set_id );
		$templates    = intval( $this->request['templates'] );
		$css 	      = intval( $this->request['css'] );
		$replacements = intval( $this->request['replacements'] );
		
		/* Auth check */
		if ( $authKey != $this->member->form_hash )
		{
			$this->registry->output->global_error = $this->lang->words['ss_authkeyerror'];
			return $this->_listSets();
		}
		
		/* Do it */
		$this->skinFunctions->removeCustomizations( $set_id, array( 'templates' => $templates, 'css' => $css, 'replacements' => $replacements ) );
		$this->registry->output->global_message = $this->lang->words['ss_revertcomplete'];
		$this->_listSets();
	}
	
	/**
	 * Show revert splash screen
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _revertSplash()
	{
		/* INIT */
		$setId   = intval( $this->request['setID'] );
		$setData = $this->skinFunctions->fetchSkinData( $setId );
		
		/* Fetch the numbers */
		$counts = $this->skinFunctions->fetchCustomizationCount( $setId );
		
		/* done */
		$this->registry->getClass('output')->html .= $this->html->skinsets_revertSplash( $setData, $counts );
	}
	
	/**
	 * Write out skin CSS in master format
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _setWriteMasterCss()
	{
		$set_id = intval( $this->request['set_id'] );

		if ( ! $set_id OR ! IN_DEV OR ! isset( $this->skinFunctions->remapData['css'][ $set_id ] ) )
		{
			return $this->_listSets();
		}

		try
		{
			$messages = $this->skinFunctions->writeMasterSkinCss( $set_id, $this->skinFunctions->remapData['css'][ $set_id ] );
		}
		catch( Exception $error )
		{
			$this->registry->output->global_error = 'Error' . $error->getMessage();
			return $this->_listSets();
		}

		/* done */
		$this->registry->getClass('output')->html .= $this->html->tools_toolResults( $this->lang->words['ss_masterwritten'], $messages );
	}
		
	/**
	 * Write out a skin set in master format
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _setWriteMaster()
	{
		$set_id = intval( $this->request['set_id'] );
		
		if ( ! $set_id OR ! IN_DEV OR ! isset( $this->skinFunctions->remapData['templates'][ $set_id ] ) )
		{
			return $this->_listSets();
		}
		
		try
		{
			$messages = $this->skinFunctions->writeMasterSkin( $set_id, $this->skinFunctions->remapData['templates'][ $set_id ] );
		}
		catch( Exception $error )
		{
			$this->registry->output->global_error = 'Error' . $error->getMessage();
			return $this->_listSets();
		}
		
		/* done */
		$this->registry->getClass('output')->html .= $this->html->tools_toolResults( $this->lang->words['ss_masterwritten'], $messages );
	}
	
	/**
	 * Remove Skin Set
	 *
	 * @access	private
	 * @return	string	HTML
	 */
	private function _setRemove()
	{
		$set_id  = intval( $this->request['set_id'] );
		$authKey = $this->request['authKey'];
		$setData = $this->skinFunctions->fetchSkinData( $set_id );
		
		/* Auth check */
		if ( $authKey != $this->member->form_hash )
		{
			$this->registry->output->global_error = $this->lang->words['ss_authkeyerror'];
			return $this->_listSets();
		}
		
		/* Can remove check */
		if ( $this->skinFunctions->removeSet( $set_id ) === FALSE )
		{
			$this->registry->output->global_error = $this->lang->words['ss_cannotremove'];
			return $this->_listSets();
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['ss_setremoved'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
		}
	}
	
	/**
	 * Remove splash
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setRemoveSplash()
	{
		$set_id  = intval( $this->request['set_id'] );
		$setData = $this->skinFunctions->fetchSkinData( $set_id );
		
		if ( $this->skinFunctions->canRemoveSet( $set_id ) === FALSE )
		{
			$this->registry->output->global_error = $this->lang->words['ss_cannotremove'];
			return $this->_listSets();
		}
		
		$this->registry->getClass('output')->html .= $this->html->skinsets_removeSplash( $setData );
	}
	
	/**
	 * Form: Save
	 *
	 * @access	private
	 * @param	string 		Type of form to show (add/edit)
	 * @return	void
	 */
	private function _setSave( $type='' )
	{
		$set_id              = intval( $this->request['set_id'] );
		$set_name            = $this->request['set_name'];
		$set_key             = IPSText::alphanumericalClean( $this->request['set_key'] );
		$set_parent_id       = intval( $this->request['set_parent_id'] );
		$set_permissions     = '';
		$set_permissions_all = intval( $this->request['set_permissions_all'] );
		$set_is_default      = intval( $this->request['set_is_default'] );
		$set_author_name     = $this->request['set_author_name'];
		$set_author_url      = $this->request['set_author_url'];
		$set_image_dir       = $this->request['set_image_dir'];
		$set_emo_dir         = $this->request['set_emo_dir'];
		$set_css_inline      = intval( $this->request['set_css_inline'] );
		$set_output_format   = IPSText::alphanumericalClean( $this->request['set_output_format'] );
		$set_hide_from_list  = intval( $this->request['set_hide_from_list'] );
		$set_minify			 = intval( $this->request['set_minify'] );
		$skinSet             = array();
	
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			$skinSet = $this->skinFunctions->fetchSkinData( $set_id );
			
			if ( ! $skinSet['set_id'] )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['ss_noid'];
				$this->_setForm( $type );
				return;
			}
		}
		
		//-----------------------------------------
		// Global checks..
		//-----------------------------------------
		
		if ( ! $set_name )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_specifyname'];
			$this->_setForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Fix up permissions
		//-----------------------------------------
		
		if ( $set_permissions_all == 1 )
		{
			$set_permissions = '*';
		}
		else if ( is_array( $_POST['set_permissions'] ) AND count( $_POST['set_permissions'] ) )
		{
			$set_permissions = implode( ",", $_POST['set_permissions'] );
		}
		else
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_nogroupaccess'];
			$this->_setForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Check emo and img dir
		//-----------------------------------------
		
		if ( $this->skinFunctions->checkImageDirectoryExists( $set_image_dir ) !== TRUE )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_imgdirnoexist'];
			$this->_setForm( $type );
			return;
		}
		
		if ( $this->skinFunctions->checkEmoticonDirectoryExists( $set_emo_dir ) !== TRUE )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_emodirnoexist'];
			$this->_setForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Make sure we're not moving skin set into self
		//-----------------------------------------
		
		if ( $type == 'edit' AND $set_parent_id )
		{
			if ( in_array( $set_parent_id, $skinSet['_childTree'] ) )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['ss_dontmoveintoself'];
				$this->_setForm( $type );
				return;
			}
		}
		
		//-----------------------------------------
		// Build Save Array
		//-----------------------------------------
		
		$save = array(  'set_name'			=> $set_name,
					    'set_key'			=> $set_key,
						'set_parent_id'  	=> $set_parent_id,
						'set_permissions'	=> ( $set_is_default ) ? '*' : $set_permissions,
						'set_is_default'	=> $set_is_default,
						'set_author_name'	=> $set_author_name,
						'set_author_url'	=> $set_author_url,
						'set_image_dir'		=> $set_image_dir,
						'set_emo_dir'		=> $set_emo_dir,
						'set_css_inline'	=> $set_css_inline,
						'set_output_format' => $set_output_format,
						'set_hide_from_list'=> ( $set_is_default ) ? 0 : $set_hide_from_list,
						'set_minify'		=> $set_minify,
						'set_updated'       => time() );
		
		
		if ( $type == 'edit' )
		{
			$this->DB->update( 'skin_collections', $save, 'set_id=' . $set_id );
		}
		else
		{
			/* Add elements into the array */
			$save['set_added'] = time();
			
			$this->DB->insert( 'skin_collections', $save );
			$set_id = $this->DB->getInsertId();
		}
		
		//-----------------------------------------
		// Unset any other default skins
		//-----------------------------------------
		
		if ( $set_is_default )
		{
			$this->DB->update( 'skin_collections', array( 'set_is_default' => 0 ), 'set_id != ' . $set_id . ' AND set_output_format="' . $set_output_format . '"' );
		}
		
		$messages = array();
		$errors   = array();
		
		/* Flush the data */
		$this->skinFunctions->flushSkinData();
		
		//-----------------------------------------
		// Rebuild tree info
		//-----------------------------------------
		
		$this->skinFunctions->rebuildTreeInformation( $set_id );
		
		//-----------------------------------------
		// Rebuild Caches
		//-----------------------------------------
		
		$this->skinFunctions->rebuildCSS( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildPHPTemplates( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildReplacementsCache( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->getClass('output')->global_message = $this->lang->words['ss_skinsetsaved'] . '<br />' . implode( '<br />', $messages ) . '<br />' . implode( '<br />', $errors );
		
		$this->_listSets();
	}
	
	/**
	 * Form
	 *
	 * @access	private
	 * @param	string 		Type of form to show (add/edit)
	 * @return	void
	 */
	private function _setForm( $type='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		 
		$set_id         = intval( $this->request['set_id'] );
		$parents        = array();
		$allSets        = array();
		$skinSet        = array();
		$form 	        = array();
		$emoDirs        = array();
		$skinDirs       = array();
		$outputFormats  = array();
		$setPermissions = array();
		
		//-----------------------------------------
		// Get parents and this skin set if editing
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'skin_collections' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$allSets[ $row['set_id'] ] = $row;
			
			if ( ( $row['set_id'] < 0 ) AND ( $type == 'edit' AND $set_id != $row['set_id'] ) )
			{
				$parents[] = array( $row['set_id'], $row['set_name'] );
			}
			
			if ( $set_id == $row['set_id'] )
			{
				$skinSet = $row;
			}
		}
		
		//-----------------------------------------
		// Grab output formats
		//-----------------------------------------
		
		$_outputFormats = $this->skinFunctions->fetchOutputFormats();
		
		foreach( $_outputFormats as $key => $conf )
		{
			$outputFormats[] = array( $key, $conf['identifies_as'] );
		}
		
		//-----------------------------------------
		// Grab image / emo directories
		//-----------------------------------------
		
		$_imgDir = $this->skinFunctions->fetchImageDirectories();
		$_emoDir = $this->skinFunctions->fetchEmoticonDirectories();
		
		foreach( $_imgDir as $_dir )
		{
			$skinDirs[] = array( $_dir, $_dir );
		}
		
		foreach( $_emoDir as $_dir )
		{
			$emoDirs[] = array( $_dir, $_dir );
		}
 		
		//-----------------------------------------
		// Can we write into the images directory?
		//-----------------------------------------
		
		if ( is_writeable( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css' ) )
		{
			$form['set_css_inline'] = $this->registry->getClass('output')->formYesNo('set_css_inline', $skinSet['set_css_inline']);
		}
		else
		{
			$form['set_css_inline'] = $this->lang->words['ss_unavailable'];
		}
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'setAddDo';
			$title    = $this->lang->words['ss_addnewset'];
			$button   = $this->lang->words['ss_addnewset'];
		}
		else
		{
			$formcode = 'setEditDo';
			$title    = $this->lang->words['ss_edituserset'] . $skinSet['set_name'];
			$button   = $this->lang->words['ss_saveset'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
			
		$form['set_name']          = $this->registry->getClass('output')->formInput(    'set_name'         , ( $_POST['set_name'] ) ? $_POST['set_name'] : $skinSet['set_name'] );
		$form['set_key']           = $this->registry->getClass('output')->formInput(    'set_key'          , ( $_POST['set_key'] ) ? $_POST['set_key'] : $skinSet['set_key'] );
		$form['set_is_default']    = $this->registry->getClass('output')->formCheckbox( 'set_is_default'   , ( $_POST['set_is_default'] ) ? $_POST['set_is_default'] : $skinSet['set_is_default'], 1, 'setIsDefault', 'onclick="checkMakeGlobal()"' );
		$form['set_author_name']   = $this->registry->getClass('output')->formInput(    'set_author_name'  , ( $_POST['set_author_name'] ) ? $_POST['set_author_name'] : $skinSet['set_author_name'] );
		$form['set_author_url']    = $this->registry->getClass('output')->formInput(    'set_author_url'   , ( $_POST['set_author_url'] ) ? $_POST['set_author_url'] : $skinSet['set_author_url'] );
		$form['set_parent_id']     = $this->skinFunctions->getTiersFunction()->fetchAllsItemDropDown( ( $_POST['set_parent_id'] ) ? $_POST['set_parent_id'] : $skinSet['set_parent_id'], array( $skinSet['set_id'] ), array( 0, $this->lang->words['none_root_set'] ) );
	    $form['set_image_dir']     = $this->registry->getClass('output')->formDropdown( 'set_image_dir'    , $skinDirs, ( $_POST['set_image_dir'] ) ? $_POST['set_image_dir'] : $skinSet['set_image_dir'] );
		$form['set_emo_dir']       = $this->registry->getClass('output')->formDropdown( 'set_emo_dir'      , $emoDirs, ( $_POST['set_emo_dir'] ) ? $_POST['set_emo_dir'] : $skinSet['set_emo_dir'] );
		$form['set_output_format'] = $this->registry->getClass('output')->formDropdown( 'set_output_format', $outputFormats, ( $_POST['set_output_format'] ) ? $_POST['set_output_format'] : $skinSet['set_output_format'] );
		$form['set_hide_from_list']= $this->registry->getClass('output')->formYesNo(   'set_hide_from_list', ( $_POST['set_hide_from_list'] ) ? $_POST['set_hide_from_list'] : $skinSet['set_hide_from_list'] );
		$form['set_minify']        = $this->registry->getClass('output')->formYesNo(   'set_minify'        , ( $_POST['set_minify'] ) ? $_POST['set_minify'] : $skinSet['set_minify'] );
		
		//-----------------------------------------
		// Get group permissions
		//-----------------------------------------
		
		$set_permissions      = is_array( $_POST['set_permissions'] )  ? $_POST['set_permissions']  : explode( ',', $skinSet['set_permissions']   );
		$set_permissions_all  = FALSE;
		
		if ( in_array( '*', $set_permissions ) OR $_POST['set_permissions_all'] )
		{
			$set_permissions_all         = TRUE;
			$form['set_permissions_all'] = ' checked="checked"';
		}
		
		$form['set_permissions']  = $this->registry->getClass('output')->generateGroupDropdown( 'set_permissions[]', $set_permissions, TRUE, 'setPermissions' );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=list', $this->lang->words['ss_manageskinsets'] );
		
		$this->registry->getClass('output')->html .= $this->html->skinsets_setForm( $form, $title, $formcode, $button, $skinSet );
	}

	/**
	 * List template sets
	 *
	 * @access	private
	 * @return	void
	 */
	private function _listSets()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$root_id   = 'root';
		$sets      = array();
		$cacheData = array();
		
		//-----------------------------------------
		// See if we have any cached data
		//-----------------------------------------
	
		$this->DB->build( array( 'select' => 'cache_id, cache_set_id',
								 'from'   => 'skin_cache',
								 'where'  => "cache_type='phptemplate'" ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$cacheData[ $row['cache_set_id'] ]['db']++;
		}
		
		//-----------------------------------------
		// Recurse through and gather data
		//-----------------------------------------

		if ( is_array( $this->skinFunctions->recursiveTiers->getData( $root_id ) ) and count( $this->skinFunctions->recursiveTiers->getData( $root_id ) ) )
		{
			foreach( $this->skinFunctions->recursiveTiers->getData( $root_id ) as $id => $set_data )
			{
				$sets[] = $this->_listSetsFormatData( $set_data );
				$sets = $this->_listSetsRecurse( $set_data['set_id'], $sets );
			}
		}
		
		//-----------------------------------------
		// Check through...
		//-----------------------------------------
		
		foreach( $sets as $setID => $setData )
		{
			if ( @is_dir( IPS_CACHE_PATH . 'skin_cache/cacheid_'.$setID ) )
			{
				$cacheData[ $setID ]['php'] = 1;
			}
		}
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->skinsets_listSkinSets( $sets, $cacheData );
	}
	
	/**
	 * Recursive list sets function
	 *
	 * @access	private
	 * @param	int			Root ID to drill down from
	 * @param	array 		Array of skin set entries
	 * @param	int			Depth gauge count
	 * @return	array 		Array of skin set entries
	 */
	private function _listSetsRecurse($id, $sets, $depth=0)
	{
		if ( is_array( $this->skinFunctions->recursiveTiers->getData( $id ) ) and count( $this->skinFunctions->recursiveTiers->getData( $id ) ) )
		{
			$depth++;

			foreach( $this->skinFunctions->recursiveTiers->getData( $id ) as $idx => $set_data )
			{
				$sets[] = $this->_listSetsFormatData( $set_data, $depth );
				$sets = $this->_listSetsRecurse( $set_data['set_id'], $sets, $depth );
			}
		}

		return $sets;
	}
	
	/**
	 * Format the data
	 *
	 * @access	private
	 * @param	array 		Skin set entry
	 * @param	int			Depth gauge count
	 * @return	array 		Modified skin set entry
	 */
	private function _listSetsFormatData( $set_data, $depth=0 )
	{ 
		//-----------------------------------------
		// Get last modified date
		//-----------------------------------------

		$set_data['_set_updated'] = gmstrftime( '%c', $set_data['set_updated'] );
		$set_data['_set_added']   = gmstrftime( '%c', $set_data['set_added'] );
		
		/* @see http://forums./tracker/issue-18012-warnings-in-acp-302/ */
		$this->skinFunctions->remapData['export']	= is_array($this->skinFunctions->remapData['export']) ? $this->skinFunctions->remapData['export'] : array();

		$set_data['_canRemove']   = ( ! $set_data['set_is_default'] AND ( ! in_array( $set_data['set_id'], array_values( $this->skinFunctions->remapData['export'] ) ) ) ) ? 1 : 0;
		
		$set_data['_setImg']   = ( $set_data['set_parent_id'] > 0 ) ? 'package.png' : 'folder_palette.png';
		$set_data['_cssClass'] = ( $set_data['set_parent_id'] > 0 ) ? 'tablerow2' : 'tablerow1';
		
		$set_data['_canWriteMaster']    = ( isset( $this->skinFunctions->remapData['templates'][ $set_data['set_id'] ] ) );
		$set_data['_canWriteMasterCss'] = ( isset( $this->skinFunctions->remapData['css'][ $set_data['set_id'] ] ) );
		
		//-----------------------------------------
		// Set Depth
		//-----------------------------------------

		$set_data['depthguide'] = '';

		for( $i = 1 ; $i < $depth; $i++ )
		{
			$set_data['depthguide'] .= $this->_depthGuide[ $i ];
			$set_data['cssDepthGuide']++;
		}

		//-----------------------------------------
		// Last child?
		//-----------------------------------------

		if ( $depth > 0 )
		{
			$this->_depthGuide[ $depth ]  = "<img src='{$this->settings['skin_acp_url']}/images/icon_components/generic_trees/depth-guide.gif' style='vertical-align:middle' />";
			$set_data['depthguide'] .= "<img src='{$this->settings['skin_acp_url']}/images/icon_components/generic_trees/depth-guide.gif' style='vertical-align:middle' />";
			$set_data['cssDepthGuide']++;
		}

		return $set_data;
	}	
}
