<?php

$templates = array();

/* administrator template/policy */
$templates[1]= $modx->newObject('modAccessPolicyTemplate');
$templates[1]->fromArray(array(
    'id' => 1,
    'name' => 'AIKitTemplate',
    'description' => 'Policy Template for access to the AIKit configurator.',
    'lexicon' => 'aikit:permissions',
    'template_group' => 1,
));

$permissions = include __DIR__ .'/permissions/aikittemplate.permissions.php';
if (is_array($permissions)) {
    $templates[1]->addMany($permissions);
} else { 
    $modx->log(modX::LOG_LEVEL_ERROR,'Could not load AIKitTemplate Permissions.');
}

$policies = include __DIR__ .'/policies/aikittemplate.policies.php';
if (is_array($policies)) {
    $templates[1]->addMany($policies);
} else {
    $modx->log(modX::LOG_LEVEL_ERROR,'Could not load AIKitTemplate Policies.');
}

return $templates;