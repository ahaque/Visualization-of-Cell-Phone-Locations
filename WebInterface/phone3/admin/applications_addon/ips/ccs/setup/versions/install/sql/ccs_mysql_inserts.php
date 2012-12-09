<?php

$INSERT	= array();

class ccs_templates
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry		= ipsRegistry::instance();
		$this->DB			= $this->registry->DB();
		
		$this->_importTemplates();
		
		$this->_importSite();
	}
	
	/**
	 * Import the block templates
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _importTemplates()
	{
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/block_templates.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			if( $_template['tpb_name'] )
			{
				unset($_template['tpb_id']);
				
				$this->DB->insert( "ccs_template_blocks", $_template );
			}
		}
	}
	
	/**
	 * Now we get to import the default site.  Fun!
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _importSite()
	{
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/demosite.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		foreach( $xml->fetchElements('block') as $block )
		{
			$_block	= $xml->fetchElementsFromRecord( $block );

			$this->DB->insert( "ccs_blocks", $_block );
		}
		
		foreach( $xml->fetchElements('container') as $container )
		{
			$_container	= $xml->fetchElementsFromRecord( $container );

			$this->DB->insert( "ccs_containers", $_container );
		}
		
		foreach( $xml->fetchElements('folder') as $folder )
		{
			$_folder	= $xml->fetchElementsFromRecord( $folder );

			$this->DB->insert( "ccs_folders", $_folder );
		}
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			$this->DB->insert( "ccs_page_templates", $_template );
		}
		
		foreach( $xml->fetchElements('page') as $page )
		{
			$_page	= $xml->fetchElementsFromRecord( $page );

			$this->DB->insert( "ccs_pages", $_page );
		}
		
		foreach( $xml->fetchElements('tblock') as $tblock )
		{
			$_tblock	= $xml->fetchElementsFromRecord( $tblock );

			$this->DB->insert( "ccs_template_blocks", $_tblock );
		}
		
		foreach( $xml->fetchElements('cache') as $cache )
		{
			$_cache	= $xml->fetchElementsFromRecord( $cache );

			$this->DB->insert( "ccs_template_cache", $_cache );
		}
	}
}

$templateInstall = new ccs_templates();
