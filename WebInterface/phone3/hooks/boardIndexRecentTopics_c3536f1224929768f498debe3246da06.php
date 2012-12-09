<?php

class boardIndexRecentTopics
{
	public $registry;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
	}
	
	public function getOutput()
	{
		/* INIT */
		$topicIDs	= array();
		$timesUsed	= array();
		
		/* Grab last X data */
		foreach( $this->registry->getClass('class_forums')->forum_by_id as $forumID => $forumData )
		{
			if ( ! $forumData['can_view_others'] )
			{
				continue;
			}
			
			if ( $forumData['password'] )
			{
				continue;
			}
			
			if ( ! $this->registry->permissions->check( 'read', $forumData ) )
			{
				continue;
			}
			
			/* Still here? */
			$_topics = $this->registry->getClass('class_forums')->lastXThaw( $forumData['last_x_topic_ids'] );
			
			if ( is_array( $_topics ) )
			{
				foreach( $_topics as $id => $time )
				{
					if( in_array( $time, $timesUsed ) )
					{
						while( in_array( $time, $timesUsed ) )
						{
							$time +=1;
						}
					}
					
					$topicIDs[ $time ] = $id;
				}
			}
		}
		
		$timesUsed	= array();
		
		if ( is_array( $topicIDs ) )
		{
			krsort( $topicIDs );
			
			$_topics = array_slice( $topicIDs, 0, 5 );
			
			if ( is_array( $_topics ) && count( $_topics ) )
			{
				/* Query Topics */
				$this->registry->DB()->build( array( 
														'select'   => 't.tid, t.title, t.title_seo, t.start_date, t.starter_id, t.starter_name',
														'from'     => array( 'topics' => 't' ),
														'where'    => 't.tid IN (' . implode( ',', array_values( $_topics ) ) . ')',
														'add_join' => array(
																			array(
																					'select'	=> 'm.members_display_name, m.members_seo_name',
																					'from'  	=> array( 'members' => 'm' ),
																					'where' 	=> 'm.member_id=t.starter_id',
																					'type'  	=> 'left',
																				)
																		)
											)	 );

				$this->registry->DB()->execute();

				$topic_rows = array();

				while( $r = $this->registry->DB()->fetch() )
				{
					$time	= $r['start_date'];
					
					if( in_array( $time, $timesUsed ) )
					{
						while( in_array( $time, $timesUsed ) )
						{
							$time +=1;
						}
					}
					
					$topics_rows[ $time ] = $r;
				}
				
				krsort( $topics_rows );
			}
		}
		
		return $this->registry->output->getTemplate( 'boards' )->hookRecentTopics( $topics_rows );
	}	
}