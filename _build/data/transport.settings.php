<?php

$settingSource = [
    'model' => [
        'area' => 'configuration',
        'value' => \modmore\AIKit\LLM\Models\OpenAI::class,
    ],
    'openai_api_key' => [
        'area' => 'openai',
        'value' => '',
    ],
    'openai_model' => [
        'area' => 'openai',
        'value' => 'gpt-4o-mini',
    ],
    'openai_endpoint' => [
        'area' => 'openai',
        'value' => 'https://api.openai.com/v1/',
    ],
];

$settings = array();

/**
 * Loop over setting stuff to interpret the xtype and to create the modSystemSetting object for the package.
 */
foreach ($settingSource as $key => $options) {
    $val = $options['value'];

    if (isset($options['xtype'])) $xtype = $options['xtype'];
    elseif (is_int($val)) $xtype = 'numberfield';
    elseif (is_bool($val)) $xtype = 'modx-combo-boolean';
    else $xtype = 'textfield';

    /** @var modSystemSetting */
    $settings[$key] = $modx->newObject('modSystemSetting');
    $settings[$key]->fromArray(array(
        'key' => 'aikit.' . $key,
        'xtype' => $xtype,
        'value' => $options['value'],
        'namespace' => 'aikit',
        'area' => $options['area'],
        'editedon' => time(),
    ), '', true, true);
}



return $settings;
