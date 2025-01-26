<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

use xPDO\xPDO;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Autoloader not present for AIKit');
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

if (!$modx->addPackage('modmore\\AIKit\\Model', __DIR__ . '/src/Model/', null, 'modmore\\AIKit\\Model')) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Failed adding AIKit package');
}
