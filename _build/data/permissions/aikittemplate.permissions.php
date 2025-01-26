<?php
/**
 * @var modX $modx
 */

$permissions = [];
$permissions[] = $modx->newObject('modAccessPermission', [
    'name' => 'aikit_configuration',
    'description' => 'aikit.permission.configurator',
    'value' => true,
]);
return $permissions;