<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Notifications class for reported content
 * Last Updated: $LastChangedDate: 2009-05-18 22:05:12 -0400 (Mon, 18 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4668 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class reportNotifications
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Messenger object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $messenger;
	
	/**
	 * Data for the members
	 *
	 * @access	public
	 * @var		array
	 */	
	public $my_data;

	/**
	 * Data for the reported content
	 *
	 * @access	public
	 * @var		array
	 */	
	public $my_report_data;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->getClass('class_localization');
		
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
		$this->messenger	= new messengerFunctions( $this->registry );
	}
	
	/**
	 * Initialize library
	 *
	 * @access	public
	 * @param	array 		Member data
	 * @param	array 		Reported content	
	 * @return	void
	 */
	public function initNotify( $data, $report_data )
	{
		$this->my_data			= $data;
		$this->my_report_data	= $report_data;
	}
	
	/**
	 * Send the notifications
	 *
	 * @access	public
	 * @return	void
	 */
	public function sendNotifications()
	{
		if( $this->my_data['PM'] && $this->settings['report_pm_enabled'] == 1 )
		{
			$this->_sendPMNotifications( $this->my_data['PM'], $this->my_report_data );
		}

		if( $this->my_data['EMAIL'] && $this->settings['report_nemail_enabled'] == 1 )
		{
			$this->_sendEmailNotifications( $this->my_data['EMAIL'], $this->my_report_data );
		}

		$this->_buildRSSFeed( $this->my_data['RSS'], $this->my_report_data );
	}

	/**
	 * Send the PM Notifications
	 *
	 * @access	private
	 * @return	void
	 */
	private function _sendPMNotifications( $data, $report_data )
	{
		foreach( $data as $user )
		{
			IPSText::getTextClass( 'email' )->getTemplate("report_emailpm");
			
			IPSText::getTextClass( 'email' )->buildMessage( array(
																'MOD_NAME'	=> $user['name'],
																'USERNAME'	=> $this->memberData['members_display_name'],
																'LINK'		=> $this->registry->getClass('reportLibrary')->processUrl( $report_data['SAVED_URL'], $report_data['SEOTITLE'], $report_data['TEMPLATE'] ),
																'REPORT'	=> $report_data['REPORT'],
																	)
															);

			try
			{
				$this->messenger	= new messengerFunctions( $this->registry );
			 	$this->messenger->sendNewPersonalTopic( $user['member_id'], 
												$this->memberData['member_id'], 
												array(), 
												$this->lang->words['subject_report'] . ' ' . $this->memberData['members_display_name'], 
												IPSText::getTextClass( 'editor' )->method == 'rte' ? nl2br(IPSText::getTextClass( 'email' )->message) : IPSText::getTextClass( 'email' )->message, 
												array( 'origMsgID'			=> 0,
														'fromMsgID'			=> 0,
														'postKey'			=> md5(microtime()),
														'trackMsg'			=> 0,
														'addToSentFolder'	=> 0,
														'hideCCUser'		=> 0,
														'forcePm'			=> 1,
														'isSystem'          => TRUE
													)
												);
			}
			catch( Exception $error )
			{
				$msg		= $error->getMessage();
				
				if( $msg != 'CANT_SEND_TO_SELF' )
				{
					$toMember	= IPSMember::load( $user['member_id'], 'core' );
				   
					if ( strstr( $msg, 'BBCODE_' ) )
				    {
						$msg = str_replace( 'BBCODE_', '', $msg );
	
						$this->registry->output->showError( $msg, 10149 );
					}
					else if ( isset($this->lang->words[ 'err_' . $msg ]) )
					{
						$this->lang->words[ 'err_' . $msg ] = $this->lang->words[ 'err_' . $msg ];
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#NAMES#'   , implode( ",", $this->messenger->exceptionData ), $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $this->lang->words[ 'err_' . $msg ] );
						
						$this->registry->output->showError( 'err_' . $msg, 10150 );
					}
					else
					{
						$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
						
						$this->registry->output->showError( 'err_UNKNOWN', 10151 );
					}
				}
			}
		}
	}
	
	/**
	 * Send the PM Notifications
	 *
	 * @access	private
	 * @return	void
	 */
	private function _sendEmailNotifications( $data, $report_data )
	{								
		foreach( $data as $user )
		{
			IPSText::getTextClass( 'email' )->getTemplate( "report_emailpm", $user['language'] );
			
			IPSText::getTextClass( 'email' )->buildMessage( array(
																'MOD_NAME'	=> $user['name'],
																'USERNAME'	=> $this->memberData['members_display_name'],
																'LINK'		=> $this->registry->getClass('reportLibrary')->processUrl($report_data['SAVED_URL'], $report_data['SEOTITLE'], $report_data['TEMPLATE'] ),
																'REPORT'	=> $report_data['REPORT'],
																	)
															);

			IPSText::getTextClass( 'email' )->subject	= $this->lang->words['subject_report'] . ' ' . $this->memberData['members_display_name'];
			IPSText::getTextClass( 'email' )->to		= $user['email'];
				
			IPSText::getTextClass( 'email' )->sendMail();
		}
	}

	/**
	 * Build a private RSS feed for the member to monitor reports
	 *
	 * @access	private
	 * @return	void
	 */
	private function _buildRSSFeed( $data=array(), $report_data )
	{
		$ids = array();
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_reports' ), 'core' );

		if( is_array($data) AND count($data) )
		{
			foreach( $data as $user )
			{
				$ids[] = $user['member_id'];
			}
		}
		
		if( count( $ids ) == 0 )
		{
			return;
		}
		
		require_once( IPS_KERNEL_PATH . 'classRss.php' );
		
		$status = array();
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
										 'from'		=> 'rc_status', 
										 'where'	=> "is_new=1 OR is_complete=1",
								) 		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}

		//-----------------------------------------
		// Now, we loop over each of the member ids
		//-----------------------------------------
		
		foreach( $ids as $id )
		{
			//-----------------------------------------
			// Clear out for new RSS doc and add channel
			//-----------------------------------------
			
			$rss			=  new classRss();
			$channel_id = $rss->createNewChannel( array( 'title'			=> $this->lang->words['rss_feed_title'],
															'link'			=> $this->settings['board_url'],
															'description'	=> $this->lang->words['reports_rss_desc'],
															'pubDate'		=> $rss->formatDate( time() )
												)		);

			//-----------------------------------------
			// Now we need to find all open reports for
			// this member
			//-----------------------------------------
			
			$this->DB->build( array(
									'select'	=> 'i.*',
									'from'		=> array( 'rc_reports_index' => 'i' ),
									'where'		=> 's.is_active=1',
									'add_join'	=> array(
														array(
															'from'		=> array( 'rc_status' => 's' ),
															'where'		=> 's.status=i.status',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'c.my_class, c.mod_group_perm',
															'from'		=> array( 'rc_classes' => 'c' ),
															'where'		=> 'c.com_id=i.rc_class',
															'type'		=> 'left',
															),
														)
							)		);
			$outer = $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				//-----------------------------------------
				// Fix stuff....this is hackish :(
				//-----------------------------------------
				
				if( $r['my_class'] == 'post' )
				{
					$r['FORUM_ID']	= $r['exdat1'];
				}
				
				//-----------------------------------------
				// Found all open reports, can we access?
				//-----------------------------------------
				
				require_once( IPSLib::getAppdir('core') . '/sources/reportPlugins/' . $r['my_class'] . '.php' );
				$class 	= $r['my_class'] . '_plugin';
				$object	= new $class( $this->registry );
				
				$notify	= $object->getNotificationList( IPSText::cleanPermString( $r['mod_group_perm'] ), $r );
				$pass	= false;
				
				if( is_array($notify['RSS']) AND count($notify['RSS']) )
				{
					foreach( $notify['RSS'] as $memberAccount )
					{
						if( $memberAccount['mem_id'] == $id )
						{
							$pass = true;
							break;
						}
					}
				}
				
				if( $pass )
				{
					$url = $this->registry->getClass('reportLibrary')->processUrl( str_replace( '&amp;', '&', $r['url'] ) );
					
					$rss->addItemToChannel( $channel_id, array( 'title'			=> $url,
																'link'			=> $url,
																'description'	=> $r['title'],
																'content'		=> $r['title'],
																'pubDate'		=> $rss->formatDate( $r['date_updated'] )
										)					);
				}
			}

			$rss->createRssDocument();
	
			$this->DB->update( 'rc_modpref', array( 'rss_cache' => $rss->rss_document ), "mem_id=" . $id );
		}
	}
}