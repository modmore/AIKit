<?php
$events = array();

$events['OnManagerPageBeforeRender'] = $modx->newObject('modPluginEvent');
$events['OnManagerPageBeforeRender']->fromArray([
    'event' => 'OnManagerPageBeforeRender',
    'priority' => 0,
    'propertyset' => 0
],'',true,true);

$events['OnDocFormSave'] = $modx->newObject('modPluginEvent');
$events['OnDocFormSave']->fromArray([
    'event' => 'OnDocFormSave',
    'priority' => 0,
    'propertyset' => 0
],'',true,true);

return $events;
