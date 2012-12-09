<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Virus scanner: Plugin example.
 * The plugin system can be used to extend the virus scanner functionality by checking for arbitrary 
 * things to score the virus rating against.  See the two links below for more ideas/suggestions.
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		Tue. 17th August 2004
 * @version		$Rev: 3887 $
 * 
 * @link 		http://forums./index.php?autocom=tracker&showissue=8452
 * @link		http://forums./index.php?autocom=tracker&showissue=8453
 */
class virusScannerPlugin_example
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 * 
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->DB       = $registry->DB();
		$this->settings = $registry->settings();
		$this->member   = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->cache    = $registry->cache();
		$this->caches   =& $registry->cache()->fetchCaches();
		$this->request  = $registry->request();
	}
	
	/**
	 * Run scorer
	 * 
	 * @access	public
	 * @param	string		This is the full path to the file currently being scanned
	 * @return	integer		Number of points to add to the score.
	 */
	public function run( $filepath )
	{
		return 0;
	}
}