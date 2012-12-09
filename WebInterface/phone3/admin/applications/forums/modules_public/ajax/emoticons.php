<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Ourputs emoticon list via AJAX (AJAX)
 * Last Updated $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Rev: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_emoticons extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* INIT */
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
								'smilie_id'	 =>	$smilie_id							
							);					
			}
		}
		
		/* Output */
		$this->returnHtml( $this->registry->getClass('output')->getTemplate('legends')->emoticonPopUpList( $editor_id, $rows ) );
	}
}