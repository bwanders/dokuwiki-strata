<?php

$lang['unnamed_group'] = 'unnamed group';
$lang['named_group'] = '\'<code>%s</code>\' group';

$lang['error_entry_block'] = 'I don\'t know what to do with the %s in the \'%s\' data entry';
$lang['error_entry_line'] = 'I don\'t understand data entry line \'<code>%s</code>\'';

$lang['error_query_bothfields'] = 'Query contains both <code>fields</code> group and normal selection';
$lang['error_query_fieldsgroups'] = 'I don\'t know how to handle a query containing multiple <code>fields</code> groups.';
$lang['error_query_fieldsblock'] = 'I don\'t know what to do with the %s in the <code>fields</code> group.';
$lang['error_query_noselect'] = 'I don\'t know which fields to select.';
$lang['error_query_unknownselect'] = 'Query selects unknown field \'<code>%s</code>\'.';

$lang['error_query_outofwhere'] = 'I don\'t know what to do with things outside of the <code>where</code> group.';
$lang['error_query_singlewhere'] = 'A query should contain at most a single <code>where</code> group.';

$lang['error_query_multisort'] = 'I don\'t know what to do with multiple <code>sort</code> groups.';
$lang['error_query_sortblock'] = 'I can\'t handle groups in a <code>sort</code> group.';
$lang['error_query_sortvar'] = '<code>sort</code> group uses out-of-scope variable \'<code>%s</code>\'.';
$lang['error_query_sortline'] = 'I can\'t handle line \'<code>%s</code>\' in the <code>sort</code> group.';
$lang['error_query_selectvar'] = 'selected variable \'<code>%s</code>\' is out-of-scope.';
$lang['error_query_group'] = 'Invalid %s in query.';
$lang['error_query_unionblocks'] = 'Lines or named groups inside a <code>union</code> group. I can only handle unnamed groups inside a <code>union</code> group.';
$lang['error_query_unionreq'] = 'I need at least 2 unnamed groups inside a <code>union</code> group.';
$lang['error_query_pattern'] = 'Unknown triple pattern or filter pattern \'<code>%s</code>\'.';
$lang['error_query_fieldsline'] = 'Weird line \'<code>%s</code>\' in <code>fields</code> group.';
$lang['error_query_fieldsdoubletyped'] = 'Double type on field declaration of \'<code>%s</code>\'; using left type.';
$lang['error_syntax_braces'] = 'Unmatched braces in %s';

$lang['error_query_multigrouping'] = 'I don\'t know what to do with multiple <code>group</code> groups.';
$lang['error_query_groupblock'] = 'I can\'t handle groups in a <code>group</code> group.';
$lang['error_query_groupvar'] = '<code>group</code> group uses out-of-scope variable \'<code>%s</code>\'.';
$lang['error_query_groupline'] = 'I can\'t handle line\'<code>%s</code>\' in the <code>group</code> group.';
$lang['error_query_grouppattern'] = 'I can\'t handle a group without at least one triple pattern or union group.';

$lang['content_error_explanation'] = '%s: An error ocurred! Use the preview to see the error message again.';

