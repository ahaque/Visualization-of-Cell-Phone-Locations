<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->_importTemplates();
		
		$this->request['workact']	= '';
		return true;
	}
	
	/**
	* Run SQL files
	* 
	* @access	public
	* @param	int
	*/
	public function _importTemplates()
	{
		$templates	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			if( !preg_match( "/_(\d+)$/", $r['tpb_name'] ) )
			{
				$templates[ $r['tpb_name'] ]	= $r;
			}
		}
		
		$content	= file_get_contents( IPSLib::getAppDir('ccs') . '/xml/block_templates.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			if( $_template['tpb_name'] )
			{
				unset($_template['tpb_id']);
				
				if( array_key_exists( $_template['tpb_name'], $templates ) )
				{
					$this->DB->update( "ccs_template_blocks", $_template, "tpb_id={$templates[ $_template['tpb_name'] ]['tpb_id']}" );
				}
				else
				{
					$this->DB->insert( "ccs_template_blocks", $_template );
				}
			}
		}
	}	
}
