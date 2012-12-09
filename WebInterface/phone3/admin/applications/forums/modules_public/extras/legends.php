<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Emoticon and BBCode Legends
 * Last Updated: $Date: 2009-04-21 16:36:40 -0400 (Tue, 21 Apr 2009) $
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Rev: 4521 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_extras_legends extends ipsCommand
{
	/**
	 * Output
	 *
	 * @access	public
	 * @var		string
	 */
	public $output	= "";

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_legends' ), 'forums' );

		/* What to do */
		switch( $this->request['do'] )
		{
			case 'bbcode':
				$this->bbcodePopUpList();
			break;
				
			case 'emoticons':			
			default:
				$this->emoticonsPopUpList();
			break;
		}
		
		/* If we have any HTML to print, do so... */
		$this->registry->getClass('output')->setTitle( $this->page_title );
		$this->registry->getClass('output')->popUpWindow( $this->output );
	}

	/**
	 * Displays the emoticon list popup
	 *
	 * @access	public
	 * @return	void
	 **/
 	public function emoticonsPopUpList()
 	{
		/* INIT */
		$this->page_title = $this->lang->words['emo_title'];
 		$smilie_id        = 0;
 		$editor_id        = IPSText::alphanumericalClean( $this->request['editor_id'] );

		/* Query the emoticons */
 		$this->DB->build( array( 'select' => 'typed, image', 'from' => 'emoticons', 'where' => "emo_set='".$this->registry->output->skin['set_emo_dir']."'" ) );
		$this->DB->execute();
		
		/* Loop through and build output array */
		$rows = array();
		
		if( $this->DB->getTotalRows() )
		{
			while( $r = $this->DB->fetch() )
			{
				$smilie_id++;
				
				if( strstr( $r['typed'], "&quot;" ) )
				{
					$in_delim  = "'";
					$out_delim = '"';
				}
				else
				{
					$in_delim  = '"';
					$out_delim = "'";
				}
				
				$rows[] = array(
								'code'       => stripslashes( $r['typed'] ),
								'image'      => stripslashes( $r['image'] ),
								'in'         => $in_delim,
								'out'        => $out_delim,
								'smilie_id' =>	$smilie_id							
							);					
			}
		}
		
		/* Output */
		$this->output .= $this->registry->getClass('output')->getTemplate('legends')->emoticonPopUpList( $editor_id, $rows );
	}
 	
	/**
	 * Show BBCode Helpy file
	 *
	 * @access	public
	 * @return	void
	 **/
 	public function bbcodePopUpList()
 	{
		/* Load the Parser */
		IPSText::resetTextClass('bbcode');
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'global';

		/* Loop through the bbcode and build the output array */		
		$rows = array();

		/* Add in custom bbcode */
		if( count( $this->caches['bbcode'] ) )
		{
			foreach($this->caches['bbcode'] as $row )
			{
				if( $row['bbcode_groups'] != 'all' )
				{
					$pass		= false;
					$groups		= array_diff( explode( ',', $row['bbcode_groups'] ), array('') );
					$mygroups	= array( $this->memberData['member_group_id'] );
					$mygroups	= array_diff( array_merge( $mygroups, explode( ',', IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) ), array('') );
					
					foreach( $groups as $g_id )
					{
						if( in_array( $g_id, $mygroups ) )
						{
							$pass = true;
							break;
						}
					}
					
					if( !$pass )
					{
						continue;
					}
				}

				if( $row['bbcode_tag'] == 'member' )
				{
					$row['bbcode_example'] = str_replace( '[member=admin]', '[member=' . $this->memberData['members_display_name'] . ']', $row['bbcode_example'] );
				}

				$before  = htmlspecialchars( $row['bbcode_example'] );
				$t       = IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $before ) );				

				$before = preg_replace( "#(\[".$row['bbcode_tag']."(?:[^\]]+)?\])#is", $this->registry->output->getTemplate('legends')->wrap_tag("\\1"), $before );
				$before = preg_replace( "#(\[/".$row['bbcode_tag']."\])#is"          , $this->registry->output->getTemplate('legends')->wrap_tag("\\1"), $before );

				$rows[] = array( 
								'title'  => $row['bbcode_title'],
								'desc'   => $row['bbcode_desc'],
								'before' => nl2br( $before ),
								'after'  => $t,
							);				
			}
		}
		
		/* Output */
		$this->page_title = $this->lang->words['bbc_title'];		
		$this->output    .= $this->registry->getClass('output')->getTemplate('legends')->bbcodePopUpList( $rows );		
 	}
}