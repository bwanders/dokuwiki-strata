<?php

$meta['default_type'] = array('string', '_pattern'=>'/^\[([a-z0-9]+)(?:::([^\)]*))?\]$/');
$meta['predicate_type'] = array('string', '_pattern'=>'/^\[([a-z0-9]+)(?:::([^\)]*))?\]$/');
$meta['default_dsn'] = array('string', '_pattern'=>'/.+:.*/');
$meta['isa_key'] = array('string');
$meta['title_key'] = array('string');
$meta['debug'] = array('onoff');

$meta['enable_entry'] = array('onoff');
