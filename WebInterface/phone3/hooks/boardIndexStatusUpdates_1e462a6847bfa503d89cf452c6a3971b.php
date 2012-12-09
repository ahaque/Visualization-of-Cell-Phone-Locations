<?php

class boardIndexStatusUpdates
{
	public $registry;
	
	public function __construct()
	{
        /* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
	}
	
	public function getOutput()
	{
		/* Query the last 10 status updates */
		$this->DB->build( array(
								'select' 	=> 'pp.*',
								'from'		=> array( 'profile_portal' => 'pp' ),
								'where'		=> 'pp.pp_status <> ""',
								'order'		=> 'pp.pp_status_update DESC',
								'limit'		=> array( 0, 10 ),
								'add_join'	=> array(
														array(
																'select'	=> 'm.members_display_name, m.members_seo_name',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left'
															)
													)
						)	);
		$q = $this->DB->execute();
		
		/* Loop and build output array */
		$rows = array();
		
		while( $r = $this->DB->fetch( $q ) )
		{
			$r = ipsMember::buildProfilePhoto( $r );
			$rows[] = $r;
		}

		/* Return output */
		return $this->registry->output->getTemplate( 'boards' )->hookBoardIndexStatusUpdates( $rows );
	}
}