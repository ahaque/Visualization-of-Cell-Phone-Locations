<?php

class boardIndexTopPosters
{
	public $registry;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->DB       = $this->registry->DB();
	}
	
	public function getOutput()
	{
		/* INIT */
		$time_high	  = time();
 		$ids		  = array();
 		$rows		  = array();
 		$time_low	  = $time_high - (60*60*24);
		$todays_posts = 0;
		$store		  = array(); 		
 		
		/* List of forum ids */
		foreach( ipsRegistry::getClass('class_forums')->forum_by_id as $id => $data )
		{
			if ( ! isset( $data['inc_postcount'] ) || ! $data['inc_postcount'] )
			{
				continue;
			}
		
			$ids[] = $id;
		}
		
		/* Found some forums? */
		if( count( $ids ) )
		{
			/* Total Posts Today */
			/*$total_today = $this->DB->buildAndFetch( array( 
																	'select'   => 'count(*) as cnt',
																	'from'     => array( 'posts' => 'p' ),
																	'where'    => "p.post_date > {$time_low} AND t.forum_id IN(" . implode( ",", $ids ) . ")",
																	'add_join' => array(
																						array( 
																								'from'	=> array( 'topics' => 't' ),
																								'where'	=> 't.tid=p.topic_id',
																								'type'	=> 'left' 
																							)
																						)
														)		);*/
			
			/* Query the top posters */
			$this->DB->build( array( 
											'select'   => 'COUNT(*) as tpost',
											'from'     => array( 'posts' => 'p' ),
											'where'	   => "p.post_date > {$time_low} AND t.forum_id IN(" . implode( ",", $ids ) . ")",
											'group'	   => 'p.author_id',
											'order'	   => 'tpost DESC',
											'limit'	   => array( 0, 9 ),
											'add_join' => array( 
																array(  'from'	=> array( 'topics' => 't' ),
																		'where'	=> 't.tid=p.topic_id',
																		'type'	=> 'left'
																	),
																array(  'select'=> 'm.*',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=p.author_id',
																		'type'	=> 'left'
																	),
																array(
																		'select' => 'pp.*',
																		'from'   => array( 'profile_portal' => 'pp' ),
																		'where'  => 'pp.pp_member_id=m.member_id',
																		'type'   => 'left',
																	),
																)
								)	);
			$this->DB->execute();
			
			/* Loop through and save the members */
			while( $r = $this->DB->fetch() )
			{
				$todays_posts += $r['tpost'];
			
				$store[] = $r;
			}
			
			/* Format the results for output */
			if( $todays_posts )
			{
				foreach( $store as $info )
				{		
					$info['total_today_posts'] = $todays_posts;
				
					if ($todays_posts > 0 and $info['tpost'] > 0)
					{
						//$info['today_pct'] = sprintf( '%.2f',  ( $info['tpost'] / $total_today['cnt'] ) * 100  );
					}					
					
					$rows[] = IPSMember::buildDisplayData( $info );
				}
			}
		}

		return $this->registry->getClass('output')->getTemplate('boards')->hookTopPosters( $rows );	
	}
}