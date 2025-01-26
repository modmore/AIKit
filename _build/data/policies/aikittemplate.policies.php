<?php

$policies = array();
$policies[1]= $modx->newObject('modAccessPolicy');
$policies[1]->fromArray([
    'name' => 'AIKit Configuration Access',
    'description' => 'Gives full access to the AIKit configuration. Permissions overwritten on upgrade.',
    'parent' => 0,
    'class' => '',
    'lexicon' => 'aikit:permissions',
    'data' => json_encode([
        'aikit_configuration' => true,
    ]),
], '', true, true);

return $policies;