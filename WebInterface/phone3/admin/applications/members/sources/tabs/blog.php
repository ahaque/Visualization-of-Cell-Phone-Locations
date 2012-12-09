<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile Plugin Library
 * Last Updated: $Date$
 *
 * @author 		$Author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.Gallery
 * @link		http://www.
 * @version		$Rev$
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profile_blog extends profile_plugin_parent
{
	/**
	 * return HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
    public function return_html_block( $member=array() ) 
    {
		/* Get blog API */
		require_once( IPS_ROOT_PATH . 'api/api_core.php' );
		require_once( IPS_ROOT_PATH . 'api/blog/api_blog.php' );
		
		/* Create API Object */
		$blog_api 			= new apiBlog;

		/* Language */
		$this->lang->loadLanguageFile( array( 'public_portal' ), 'blog' );

		$content = '';

		$blog_url = $blog_api->getBlogUrl( $blog_api->getBlogID( $member['member_id'] ) );
		$this->lang->words['visit_blog'] = "<a href=\"{$blog_url}\">{$this->lang->words['visit_blog']}</a>";
			
		$entry_content = '';
        $entries = $blog_api->lastXEntries( 'member', $member['member_id'], 5 );

        if( is_array( $entries) && count( $entries ) )
        {
			$attachments = 0;
			$entry_ids = array();
				
			foreach( $entries as $row )
			{
				$row['_post_date']  = ipsRegistry::getClass( 'class_localization' )->getDate( $row['entry_date'], 'SHORT' );
				$row['_date_array'] = IPSTime::date_getgmdate( $row['entry_date'] + ipsRegistry::getClass( 'class_localization')->getTimeOffset() );
				
				$entry_ids[ $row['entry_id'] ] = $row['entry_id'];
					 
				IPSText::getTextClass( 'bbcode' )->parse_html				= $row['entry_html_state'] ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['entry_html_state'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'blog';

				$row['post'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['entry'] );
				$row['post'] = IPSText::getTextClass( 'bbcode' )->memberViewImages( $row['post'] );					

				if( $row['entry_has_attach'] )
				{
					$parseAttachments	= true;
				}

				$entry_content .= $this->registry->output->getTemplate('profile')->tabSingleColumn( $row, $this->lang->words['readentry'], $row['entry_url'], $row['entry_name'] );
			}
				
			//-----------------------------------------
			// Attachments (but only if necessary)
			//-----------------------------------------

			if( $parseAttachments AND ! is_object( $this->class_attach ) )
			{
				require_once( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php' );
				$this->class_attach			=  new class_attach( $this->registry );
				$this->class_attach->type	= 'blogentry';
				$this->class_attach->init();
			
				$entry_content = $this->class_attach->renderAttachments( $entry_content, $entry_ids, 'blog_show' );
				$entry_content = $entry_content[0]['html'];
			}
				
			$content = $this->registry->output->getTemplate('blog_portal')->profileTabWrap( $this->lang->words['visit_blog'], $entry_content );
		}
		else
		{
			$content .= $this->registry->output->getTemplate('profile')->tabNoContent( 'noblogentries' );
		}

		//-----------------------------------------
		// Return content..
		//-----------------------------------------

		return $content;
	}

}