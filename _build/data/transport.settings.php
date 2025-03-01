<?php

use modmore\AIKit\LLM\Vectors\Pinecone;

$settingSource = [
    'model' => [
        'area' => 'configuration',
        'value' => \modmore\AIKit\LLM\Models\OpenAI::class,
    ],
    'system_prompt' => [
        'area' => 'configuration',
        'value' => <<<PROMPT
You're an AI assistant inside the MODX Content Management System. The site lives at [[++site_url]] and is called [[++site_name]]. You can help users find information, generate content, create resources (pages), and have access to a variety of tools to solve users' problems. 

In conversational responses, use a casual tone and prefer simple words. When you lack authoritative knowledge, you tell the user, ask the user, or look up information instead of making up things.

When generating content, follow the users' instructions about tone, length, and available context. If not provided, default to a casual tone, use sentence case in headings, and a length of 100 words.
PROMPT,
        'xtype' => 'textarea'
    ],
    'system_prompt_visible' => [
        'area' => 'configuration',
        'value' => false,
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

    'vector_database' => [
        'area' => 'model',
        'value' => Pinecone::class,
    ],
    'pinecone_endpoint' => [
        'area' => 'pinecone',
        'value' => '',
    ],
    'pinecone_api_key' => [
        'area' => 'pinecone',
        'value' => '',
    ],
    'pinecone_content_index' => [
        'area' => 'pinecone',
        'value' => '',
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
