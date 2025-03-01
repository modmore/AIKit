<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

use GuzzleHttp\Psr7\HttpFactory;
use modmore\AIKit\LLM\Model;
use xPDO\xPDO;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Autoloader not present for AIKit');
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

if (!$modx->addPackage('modmore\\AIKit\\Model', __DIR__ . '/src/Model/', null, 'modmore\\AIKit\\Model')) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Failed adding AIKit package');
}

if (!$modx->services->has(\Psr\Http\Message\ResponseFactoryInterface::class)) {
    $modx->services->add(\Psr\Http\Message\ResponseFactoryInterface::class, function () {
        return new HttpFactory();
    });
}

$modx->services->add(Model::class, function () use ($modx) {
    return new Model($modx);
});
