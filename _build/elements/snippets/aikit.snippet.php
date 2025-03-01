<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

$model = $modx->services->get(modmore\AIKit\LLM\Model::class);
$input = $scriptProperties['input'] ?? 'Give me a random joke of the day.';
return $model->prompt($input);
