<?php

/** @var modMenu $menu */
$menu = $modx->newObject('modMenu');
$menu->fromArray(array(
    'text' => 'aikit.configuration',
    'description' => 'aikit.configuration.menu_desc',
    'parent' => 'components',
    'menuindex' => '5',
    'namespace' => 'aikit',
    'action' => 'configuration',
    'permissions' => 'aikit_configuration',
),'',true,true);

$vehicle = $builder->createVehicle($menu,array (
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
    xPDOTransport::UNIQUE_KEY => 'text',
));
$builder->putVehicle($vehicle);
unset ($vehicle,$menu);
