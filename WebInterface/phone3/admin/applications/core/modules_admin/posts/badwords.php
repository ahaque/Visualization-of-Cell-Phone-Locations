<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Bad Word Filters
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 3887 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_posts_badwords extends ipsCommand 
{
	/**
	 * HTML skin object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_badwords' );
		$this->html->form_code    = '&amp;module=posts&amp;section=badwords';
		$this->html->form_code_js = '&module=posts&section=badwords';
		
		$this->lang->loadLanguageFile( array( 'admin_posts' ) );

		/* What to do */
		switch( $this->request['do'] )
		{				
			case 'badword_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordAdd();
			break;
				
			case 'badword_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_delete' );
				$this->badwordRemove();
			break;
				
			case 'badword_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordEditForm();
			break;
				
			case 'badword_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->handleBadwordEdit();
			break;
				
			case 'badword_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordsExport();
			break;
				
			case 'badword_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordsImport();
			break;
			
			default:
			case 'overview':
			case 'badword':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'badword_manage' );
				$this->badwordsOvervew();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Remove a badword filter
	 *
	 * @access	public
	 * @return	void
	 */
	public function badwordRemove()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['bwl_nofiter'], 11138 );
		}
		
		/* Delete */
		$this->DB->delete( 'badwords', "wid={$id}" );
		
		/* Rebuild cache and bounce */
		$this->badwordsRebuildCache();		
		$this->registry->output->global_message = $this->lang->words['bwl_filter_removed'];
		$this->badwordsOvervew();
	}	
	
	/**
	 * Handles the badword edit form
	 *
	 * @access	public
	 * @return	void
	 */
	public function handleBadwordEdit()
	{
		/* Check for before */
		if( ! $this->request['before'] )
		{
			$this->registry->output->showError( $this->lang->words['bwl_noword'], 11139 );
		}
		
		/* Check ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['bwl_nofilter'], 11140 );
		}
		
		/* Match */
		$this->request['match'] = $this->request['match'] ? 1 : 0;
		
		/* Swap Text */
		$this->request['after'] = isset( $this->request['after'] ) && $this->request['after'] ? $this->request['after'] : "";
		
		$this->DB->force_data_type['type'] = 'string';
		$this->DB->force_data_type['swop'] = 'string';
			
		$this->DB->update( 'badwords', array( 
												'type'    => trim( $this->request['before'] ),
												'swop'    => trim( $this->request['after'] ),
												'm_exact' => $this->request['match']
												), "wid={$id}" );
			  
		/* Recache and bounce */
		$this->badwordsRebuildCache();		
		$this->registry->output->global_message = $this->lang->words['bwl_filter_edited'];		
		$this->badwordsOvervew();
	}	
	
	/**
	 * Edit Badword Form
	 *
	 * @access	public
	 * @return	void
	 * @author	Josh
	 */
	public function badwordEditForm()
	{
		/* Check ID */
		$id = intval( $this->request['id'] );
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['bwl_nofilter'], 11141 );
		}
		
		/* Get the field */		
		$this->DB->build( array( 'select' => '*', 'from' => 'badwords', 'where' => "wid='{$id}'" ) );
		$this->DB->execute();
		
		if ( ! $r = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['bwl_filter_404'], 11142 );
		}
		
		/* Form Fields */
		$form           = array();
		$form['before'] = $this->registry->output->formInput('before', stripslashes( $r['type'] ) );
		$form['after']  = $this->registry->output->formInput('after' , stripslashes( $r['swop'] ) );
		$form['match']  = $this->registry->output->formDropdown( 'match', array( 0 => array( 1, $this->lang->words['bwl_exact'] ), 1 => array( 0, $this->lang->words['bwl_loose'] ) ), $r['m_exact'] );
		
		/* Output */
		$this->registry->output->html           .= $this->html->badwordEditForm( $id, $form );		
	}
	
	/**
	 * Handle add bad word request
	 *
	 * @access	public
	 * @return	void
	 **/
	public function badwordAdd()
	{
		/* Check for before text */
		if( ! $this->request['before'] )
		{
			$this->registry->output->showError( $this->lang->words['bwl_noword'], 11143 );
		}
		
		/* Match */		
		$this->request['match'] = $this->request['match'] ? 1 : 0;
		
		/* Swap Text */
		$this->request['after'] = isset( $this->request['after'] ) && $this->request['after'] ? $this->request['after'] : "";
		
		/* Insert filter */
		$this->DB->force_data_type['type'] = 'string';
		$this->DB->force_data_type['swop'] = 'string';
		
		$this->DB->insert( 'badwords', array( 
												'type'    => trim( $this->request['before'] ),
												'swop'    => trim( $this->request['after'] ),
												'm_exact' => $this->request['match']
												)
							);
		
		/* Rebuild the cache */
		$this->badwordsRebuildCache();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['bwl_filter_new'];
		$this->badwordsOvervew();
	}	
	
	/**
	 * Badword Overview Screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function badwordsOvervew()
	{
		/* Query the bad words */
		$this->DB->build( array( 'select' => '*', 'from' => 'badwords', 'order' => 'type' ) );
		$this->DB->execute();
		
		/* Loop through the results */
		$rows = array();
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$words[] = $r;
			}
			
			foreach( $words as $r )
			{
				$r['replace'] = $r['swop']    ? stripslashes( $r['swop'] ) : '######';
				$r['method']  = $r['m_exact'] ? $this->lang->words['bwl_exact'] : $this->lang->words['bwl_loose'];
				$r['type'] 	  = stripslashes( $r['type'] );
				
				$rows[] = $r;
			}
			
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->badwordsWrapper( $rows );
	}	
	

	/**
	 * Import badwords from an xml file
	 *
	 * @access	public
	 * @return	void
	 */
	public function badwordsImport()
	{
		/* Get Badwords XML */
		$content = $this->registry->adminFunctions->importXml( 'ipb_badwords.xml' );
		
		/* Check for content */
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['bwl_upload_failed'];
			$this->badwordsOvervew();
			return;
		}
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		if( !count( $xml->fetchElements('badword') ) )
		{
			$this->registry->output->global_message = $this->lang->words['bwl_upload_wrong'];
			$this->badwordsOvervew();
			return;
		}
		
		/* Get a list of current badwords */
		$words = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'badwords', 'order' => 'type' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$words[ $r['type'] ] = 1;
		}
		
		/* Loop through the xml document and insert new bad words */
		foreach( $xml->fetchElements('badword') as $badword )
		{
			$entry  = $xml->fetchElementsFromRecord( $badword );

			/* Get the filter settings */
			$type    = $entry['type'];
			$swop    = $entry['swop'];
			$m_exact = $entry['m_exact'];
			
			/* Skip if it's already in the db */
			if ( $words[ $type ] )
			{
				continue;
			}
			
			/* Add to the db */
			if ( $type )
			{
				$this->DB->insert( 'badwords', array( 'type' => $type, 'swop' => $swop, 'm_exact' => $m_exact ) );
			}
		}
		
		/* Rebuild cache and bounce */
		$this->badwordsRebuildCache();                    
		$this->registry->output->global_message = $this->lang->words['bwl_upload_good'];	
		$this->badwordsOvervew();	
	}
	
	/**
	 * Exports badwords to an xml file
	 *
	 * @access	public
	 * @return	void
	 */
	public function badwordsExport()
	{
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'badwordexport' );
		$xml->addElement( 'badwordgroup', 'badwordexport' );

		/* Query the badwords */
		$this->DB->build( array( 'select' => 'type, swop, m_exact', 'from' => 'badwords', 'order' => 'type' ) );
		$this->DB->execute();
		
		/* Add the bad word entries to the xml file */
		while( $r = $this->DB->fetch() )
		{
			$xml->addElementAsRecord( 'badwordgroup', 'badword', $r );
		}

		/* Create the xml document and send to the browser */
		$xmlData = $xml->fetchDocument();
		$this->registry->output->showDownload( $xmlData, 'ipb_badwords.xml' );
	}
	
	/**
	 * Rebuild badword cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function badwordsRebuildCache()
	{
		$cache = array();
			
		$this->DB->build( array( 'select' => 'type,swop,m_exact', 'from' => 'badwords' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache[] = $r;
		}
		
		usort( $cache, array( $this, '_thisUsort' ) );
				
		$this->cache->setCache( 'badwords', $cache, array( 'name' => 'badwords', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/**
	 * Custom sort operation
	 *
	 * @access	private
	 * @param	string	A
	 * @param	string	B
	 * @return	integer
	 */
	private function _thisUsort($a, $b)
	{
		if ( IPSText::mbstrlen($a['type']) == IPSText::mbstrlen($b['type']) )
		{
			return 0;
		}
		return ( IPSText::mbstrlen($a['type']) > IPSText::mbstrlen($b['type']) ) ? -1 : 1;
	}
}