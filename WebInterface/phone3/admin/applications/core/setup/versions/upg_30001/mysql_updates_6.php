<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.0.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.
|   ========================================
|   Web: http://www.
|   Email: matt@
|   Licence Info: http://www./?license
+---------------------------------------------------------------------------
*/


# Member table updates
# We use backticks on the second table to stop IPSSetUp::addPrefixToQuery() from stripping the prefix

$SQL[] = "UPDATE members m, `" . trim(ipsRegistry::dbFunctions()->getPrefix()) . "members_converge` c SET m.members_pass_hash=c.converge_pass_hash WHERE c.converge_id=m.member_id;";
$SQL[] = "UPDATE members m, `" . trim(ipsRegistry::dbFunctions()->getPrefix()) . "members_converge` c SET m.members_pass_salt=c.converge_pass_salt WHERE c.converge_id=m.member_id;";

# Blank email addresses
$SQL[] = "UPDATE members SET email=CONCAT( member_id, '-', UNIX_TIMESTAMP(), '@fakeemail.com' ) WHERE email='';";

# If we upgraded from 2.1.0ish then we may not have anything in profile_portal so...
$count = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'count(*) as count',
												  'from'   => 'profile_portal' ) );
												
if ( ! $count['count'] )
{
	ipsRegistry::DB()->allow_sub_select = 1;
	$SQL[] ="INSERT INTO profile_portal (pp_member_id,notes,links,bio,ta_size,signature,avatar_location,avatar_size,avatar_type) SELECT id,notes,links,bio,ta_size,signature,avatar_location,avatar_size,avatar_type FROM `" . trim(ipsRegistry::dbFunctions()->getPrefix()) . "member_extra`";
}
else
{
	$SQL[] = "UPDATE profile_portal p, `" . trim(ipsRegistry::dbFunctions()->getPrefix()) . "member_extra` e SET p.notes=e.notes, p.links=e.links, p.bio=e.bio, p.ta_size=e.ta_size, p.signature=e.signature, p.avatar_location=e.avatar_location, p.avatar_size=e.avatar_size, p.avatar_type=e.avatar_type WHERE p.pp_member_id=e.id;";
}

$SQL[] = "UPDATE profile_portal SET pp_setting_count_friends=5 WHERE pp_setting_count_friends=0;";
$SQL[] = "UPDATE profile_portal SET pp_setting_count_comments=10 WHERE pp_setting_count_comments=0;";
$SQL[] = "UPDATE profile_portal SET pp_setting_count_visitors=5 WHERE pp_setting_count_visitors=0;";

?>