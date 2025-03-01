<?php

/* Get the core config */
if (!file_exists(dirname(__DIR__) . '/config.core.php')) {
    die('ERROR: missing ' . dirname(__DIR__) . '/config.core.php file defining the MODX core path.');
}

require_once dirname(__DIR__) . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new \MODX\Revolution\modX();
$modx->initialize('mgr');
$modx->setLogTarget('ECHO');

//$settings = include dirname(__DIR__) . '/_build/data/transport.settings.php';
//$update = true;
//foreach ($settings as $key => $setting) {
//    /** @var modSystemSetting $setting */
//    $exists = $modx->getObject('modSystemSetting', array('key' => 'aikit.' . $key));
//    if (!($exists instanceof modSystemSetting)) {
//        $setting->save();
//    } elseif ($update && ($exists instanceof modSystemSetting)) {
//        $exists->fromArray($setting->toArray(), '', true);
//        $exists->save();
//    }
//}


$componentPath = dirname(__DIR__);
/* Namespace */
if (
    !createObject('modNamespace', array(
    'name' => 'aikit',
    'path' => $componentPath . '/core/components/aikit/',
    'assets_path' => $componentPath . '/assets/components/aikit/',
    ), 'name', false)
) {
    echo "Error creating namespace aikit.\n";
}

/* Path settings */
if (
    !createObject('modSystemSetting', array(
    'key' => 'aikit.core_path',
    'value' => $componentPath . '/core/components/aikit/',
    'xtype' => 'textfield',
    'namespace' => 'aikit',
    'area' => 'Paths',
    'editedon' => time(),
    ), 'key', false)
) {
    echo "Error creating aikit.core_path setting.\n";
}

if (
    !createObject('modSystemSetting', array(
    'key' => 'aikit.assets_path',
    'value' => $componentPath . '/assets/components/aikit/',
    'xtype' => 'textfield',
    'namespace' => 'aikit',
    'area' => 'Paths',
    'editedon' => time(),
    ), 'key', false)
) {
    echo "Error creating aikit.assets_path setting.\n";
}

/* Fetch assets url */
$requestUri = '/' . ltrim($_SERVER['REQUEST_URI'] ?? '/AIKit/_bootstrap/', '/');
$bootstrapPos = strpos($requestUri, '_bootstrap/');
$requestUri = rtrim(substr($requestUri, 0, $bootstrapPos), '/') . '/';
$assetsUrl = "{$requestUri}assets/components/aikit/";

if (
    !createObject('modSystemSetting', array(
    'key' => 'aikit.assets_url',
    'value' => $assetsUrl,
    'xtype' => 'textfield',
    'namespace' => 'aikit',
    'area' => 'Paths',
    'editedon' => time(),
    ), 'key', false)
) {
    echo "Error creating aikit.assets_url setting.\n";
}

/**
 * Plugin
 */

if (
    !createObject('modPlugin', array(
    'name' => 'AIKit',
    'static' => true,
    'static_file' => $componentPath . '/_build/elements/plugins/aikit.plugin.php',
    ), 'name', true)
) {
    echo "Error creating AIKit Plugin.\n";
}

$plugin = $modx->getObject('modPlugin', array('name' => 'AIKit'));
if ($plugin) {
    if (
        !createObject('modPluginEvent', array(
            'pluginid' => $plugin->get('id'),
            'event' => 'OnManagerPageBeforeRender',
            'priority' => 0,
        ), array('pluginid', 'event'), false)
    ) {
        echo "Error creating modPluginEvent for AIKit Plugin.\n";
    }
    if (
        !createObject('modPluginEvent', array(
            'pluginid' => $plugin->get('id'),
            'event' => 'OnDocFormSave',
            'priority' => 0,
        ), array('pluginid', 'event'), false)
    ) {
        echo "Error creating modPluginEvent for AIKit Plugin.\n";
    }
}

if (
    !createObject('modSnippet', array(
        'name' => 'AIKit',
        'static' => true,
        'static_file' => $componentPath . '/_build/elements/snippets/aikit.snippet.php',
    ), 'name', true)
) {
    echo "Error creating AIKit Plugin.\n";
}

if (
    !createObject('modMenu', array(
    'text' => 'aikit.configuration',
    'description' => 'aikit.configuration.menu_desc',
    'parent' => 'components',
    'menuindex' => '5',
    'namespace' => 'aikit',
    'action' => 'configuration',
//    'permissions' => 'aikit_configuration',
    ), array('namespace', 'action'), true)
) {
    echo "Error creating AIKit Configuration menu item.\n";
}


// Create policy template
echo "Creating policy template and permissions...\n";
$policyTemplateCreated = true;
if (
    !createObject('modAccessPolicyTemplate', array(
    'name' => 'AIKitTemplate',
    'description' => 'Policy Template for access to the AIKit configurator.',
    'lexicon' => 'aikit:permissions',
    'template_group' => 1,
    ), 'name', false)
) {
    echo "Error creating policy template.\n";
}

$policyTemplate = $modx->getObject('modAccessPolicyTemplate', array('name' => 'AIKitTemplate'));
if ($policyTemplate) {
    $perms = include dirname(dirname(__FILE__)) . '/_build/data/permissions/aikittemplate.permissions.php';
    foreach ($perms as $permission) {
        if (
            !createObject('modAccessPermission', array(
            'template' => $policyTemplate->get('id'),
            'name' => $permission->get('name'),
            'description' => $permission->get('description'),
            'value' => $permission->get('value')
            ), ['template','name'], false)
        ) {
            echo "Error creating aikit_" . $permission->get('name') . " permission.\n";
        }
    }
}


$settings = include dirname(__DIR__) . '/_build/data/transport.settings.php';
$update = false;
foreach ($settings as $key => $setting) {
    /** @var modSystemSetting $setting */
    $exists = $modx->getObject('modSystemSetting', array('key' => 'aikit.'.$key));
    if (!($exists instanceof modSystemSetting)) {
        $setting->save();
    }
    elseif (($key === 'system_prompt' || $update) && ($exists instanceof modSystemSetting)) {
        $exists->fromArray($setting->toArray(), '', true);
        $exists->save();
    }
}

$manager = $modx->getManager();

/* Create the tables */
$objectContainers = [
    \modmore\AIKit\Model\Conversation::class,
    \modmore\AIKit\Model\Tool::class,
    \modmore\AIKit\Model\Message::class
];
echo "Creating tables...\n";

foreach ($objectContainers as $oC) {
    $manager->createObjectContainer($oC);
}

$manager->addField(\modmore\AIKit\Model\Message::class, 'is_vector_augmented', ['after' => 'tool_call_id']);

$modx->getCacheManager()->refresh();

$exTools = [
    \modmore\AIKit\LLM\Tools\GetCurrentWeather::class,
    \modmore\AIKit\LLM\Tools\GetResourceDetails::class,
    \modmore\AIKit\LLM\Tools\FindResources::class,
    \modmore\AIKit\LLM\Tools\CreateResource::class,
];
foreach ($exTools as $tool) {
    if (!$modx->getCount(\modmore\AIKit\Model\Tool::class, ['class' => $tool])) {
        $rec = $modx->newObject(\modmore\AIKit\Model\Tool::class);
        $rec->fromArray([
            'enabled' => true,
            'class' => $tool,
            'tool_config' => [],
        ]);
        $rec->save();
    }
}


/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param mixed $primaryField
 * @param bool $update
 * @return bool
 */
function createObject($className = '', array $data = array(), $primaryField = '', $update = true)
{
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        if (is_array($primaryField)) {
            $condition = array();
            foreach ($primaryField as $key) {
                $condition[$key] = $data[$key];
            }
        } else {
            $condition = array($primaryField => $data[$primaryField]);
        }
        $object = $modx->getObject($className, $condition);
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                $condition = $modx->toJSON($condition);
                echo "Skipping {$className} {$condition}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
