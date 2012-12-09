<?php

class boardIndexWatchedItems
{
	public $registry;
	public $member;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->member	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
	}
	
	public function getOutput()
	{
		if( !$this->memberData['member_id'] )
		{
			return;
		}

		/* INIT */
		$updatedTopics	= array();
		$updatedForums	= array();
		$nUpdatedTopics	= array();
		$nUpdatedForums	= array();
		
		/* Get watched topics */
		$this->registry->DB()->build( array(
								'select'	=> 'tr.*',
								'from'		=> array( 'tracker' => 'tr' ),
								'where'		=> 'tr.member_id=' . $this->memberData['member_id'],
								'order'		=> 't.last_post DESC',
								'limit'		=> array( 0, 50 ),
								'add_join'	=> array(
													array(
														'select'	=> 't.*',
														'from'		=> array( 'topics' => 't' ),
														'where'		=> 't.tid=tr.topic_id',
														'type'		=> 'left'
														),
													array(
														'select'	=> 'm.members_seo_name',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=t.starter_id',
														'type'		=> 'left'
														)
													)
						)		);
		$this->registry->DB()->execute();
		
		while( $r = $this->registry->DB()->fetch() )
		{
			if( !$r['tid'] )
			{
				continue;
			}

			$is_read	= $this->registry->classItemMarking->isRead( array( 'forumID' => $r['forum_id'], 'itemID' => $r['tid'], 'itemLastUpdate' => $r['last_post'] ), 'forums' );
			
			if( !$is_read )
			{
				$updatedTopics[ $r['topic_id'] ]	= $r;
			}
			else
			{
				$nUpdatedTopics[ $r['topic_id'] ]	= $r;
			}
		}
		
		/* Get watched forums */
		$this->registry->DB()->build( array(
								'select'	=> 'tr.*',
								'from'		=> array( 'forum_tracker' => 'tr' ),
								'order'		=> 'f.last_post DESC',
								'where'		=> 'tr.member_id=' . $this->memberData['member_id'] . ' AND f.hide_last_info=0',
								'limit'		=> array( 0, 50 ),
								'add_join'	=> array(
													array(
														'select'	=> 'f.*',
														'from'		=> array( 'forums' => 'f' ),
														'where'		=> 'f.id=tr.forum_id',
														'type'		=> 'left'
														)
													)
						)		);
		$this->registry->DB()->execute();
		
		while( $r = $this->registry->DB()->fetch() )
		{
			$last_time	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $r['forum_id'] ), 'forums' );
			
			if( $r['last_post'] > $last_time )
			{
				$updatedForums[ $r['forum_id'] ]	= $r;
			}
			else
			{
				$nUpdatedForums[ $r['forum_id'] ]	= $r;
			}
		}
		
		/*print_r($updatedTopics);
		print_r($updatedForums);
		print_r($nUpdatedTopics);
		print_r($nUpdatedForums);*/
		return $this->registry->output->getTemplate( 'boards' )->hookWatchedItems( $updatedTopics, $nUpdatedTopics, $updatedForums, $nUpdatedForums );
	}	
}