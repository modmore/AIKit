<?php

/**
 * AIKit build script
 *
 * @package aikit
 * @subpackage build
 */

use MODX\Revolution\Transport\modPackageBuilder;

if (!function_exists('getSnippetContent')) {
    /**
     * @param string $filename The name of the file.
     * @return string The file's content
     * @by splittingred
     */
    function getSnippetContent($filename = '')
    {
        $o = file_get_contents($filename);
        $o = str_replace('<?php', '', $o);
        $o = str_replace('?>', '', $o);
        $o = trim($o);
        return $o;
    }
}

$tstart = microtime(true);

if (!defined('MOREPROVIDER_BUILD')) {
    /* define version */
    define('PKG_NAME', 'AIKit');
    define('PKG_NAMESPACE', 'aikit');
    define('PKG_VERSION', '0.1.0');
    define('PKG_RELEASE', 'dev1');

    /* load modx */
    require_once dirname(dirname(__FILE__)) . '/config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
    $modx = new modX();
    $modx->initialize('mgr');
    $modx->setLogLevel(modX::LOG_LEVEL_INFO);
    $modx->setLogTarget('ECHO');


    echo '<pre>';
    flush();
    $targetDirectory = dirname(__DIR__) . '/_packages/';
} else {
    $targetDirectory = MOREPROVIDER_BUILD_TARGET;
}

/* define sources */
$root = dirname(dirname(__FILE__)) . '/';
$sources = array(
    'root' => $root,
    'build' => $root . '_build/',
    'data' => $root . '_build/data/',
    'resolvers' => $root . '_build/resolvers/',
    'validators' => $root . '_build/validators/',
    'lexicon' => $root . 'core/components/' . PKG_NAMESPACE . '/lexicon/',
    'docs' => $root . 'core/components/' . PKG_NAMESPACE . '/docs/',
    'elements' => $root . '_build/elements/',
    'source_assets' => $root . 'assets/components/' . PKG_NAMESPACE,
    'source_core' => $root . 'core/components/' . PKG_NAMESPACE,
);
unset($root);

$builder = new modPackageBuilder($modx);
$builder->directory = $targetDirectory;
$builder->createPackage(PKG_NAMESPACE, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAMESPACE, false, true, '{core_path}components/' . PKG_NAMESPACE . '/', '{assets_path}components/' . PKG_NAMESPACE . '/');

$builder->package->put(
    [
        'source' => $sources['source_core'],
        'target' => "return MODX_CORE_PATH . 'components/';",
    ],
    [
        'vehicle_class' => \xPDO\Transport\xPDOFileVehicle::class,
        'validate' => [
            [
                'type' => 'php',
                'source' => $sources['validators'] . 'requirements.script.php'
            ]
        ]
    ]
);
$builder->package->put(
    [
        'source' => $sources['source_assets'],
        'target' => "return MODX_ASSETS_PATH . 'components/';",
    ],
    [
        'vehicle_class' => \xPDO\Transport\xPDOFileVehicle::class,
    ]
);
$modx->log(modX::LOG_LEVEL_INFO, 'Packaged in files.');
flush();


/* load system settings */
$settings = include $sources['data'] . 'transport.settings.php';
if (is_array($settings) && !empty($settings)) {
    $attributes = array(
        \xPDO\Transport\xPDOTransport::UNIQUE_KEY => 'key',
        \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => true,
        \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => false,
    );
    foreach ($settings as $setting) {
        $vehicle = $builder->createVehicle($setting, $attributes);
        $builder->putVehicle($vehicle);
    }
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($settings) . ' System Settings.');
    flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not package System Settings.');
}
unset($settings, $setting);

require_once($sources['data'] . 'transport.menu.php');
$modx->log(modX::LOG_LEVEL_INFO, 'Packaged in menu');

/**
 * Access Policy Templates & Access Policies
 */
$templates = include $sources['data'] . 'transport.policytemplates.php';
$attributes = [
    \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => true,
    \xPDO\Transport\xPDOTransport::UNIQUE_KEY => ['name'],
    \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => true,
    \xPDO\Transport\xPDOTransport::RELATED_OBJECTS => true,
    \xPDO\Transport\xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'Permissions' => [
            \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => false,
            \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => true,
            \xPDO\Transport\xPDOTransport::UNIQUE_KEY => ['template','name'],
        ],
        'Policies' => [
            \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => false,
            \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => true,
            \xPDO\Transport\xPDOTransport::UNIQUE_KEY => ['template','name'],
        ],
    ]
];
if (is_array($templates)) {
    foreach ($templates as $template) {
        $vehicle = $builder->createVehicle($template, $attributes);
        $builder->putVehicle($vehicle);
    }
    $modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($templates) . ' Access Policy Templates including permissions and policies.');
    flush();
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Access Policy Templates.');
}

/* create the plugin object */
$plugin = $modx->newObject('modPlugin');
$plugin->set('name', 'AIKit');
$plugin->set('description', 'AIKit AI assistant for MODX');
$plugin->set('plugincode', getSnippetContent($sources['build'] . 'elements/plugins/aikit.plugin.php'));
$plugin->set('category', 0);

/* add plugin events */
$events = include $sources['data'] . 'transport.plugin.events.php';
if (is_array($events) && !empty($events)) {
    $plugin->addMany($events);
} else {
    $modx->log(\xPDO\xPDO::LOG_LEVEL_ERROR, 'Could not find plugin events!');
}
$modx->log(\xPDO\xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($events) . ' Plugin Events.');
flush();
unset($events);

$attributes = [
    \xPDO\Transport\xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
    \xPDO\Transport\xPDOTransport::UNIQUE_KEY => 'name',
    \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => true,
    \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => true,
    \xPDO\Transport\xPDOTransport::RELATED_OBJECTS => true,
    \xPDO\Transport\xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'PluginEvents' => [
            \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => true,
            \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => false,
            \xPDO\Transport\xPDOTransport::UNIQUE_KEY => ['pluginid','event'],
        ],
    ],
];
$vehicle = $builder->createVehicle($plugin, $attributes);
// Add resolvers
$modx->log(modX::LOG_LEVEL_INFO, 'Adding file resolvers to plugin...');
$vehicle->resolve('php', array(
    'source' => $sources['resolvers'] . 'tables.resolver.php',
    'name' => 'tables',
    'type' => 'php'
));
$vehicle->resolve('php', array(
    'source' => $sources['resolvers'] . 'policies.resolver.php',
    'name' => 'resolve',
    'type' => 'php'
));
$builder->putVehicle($vehicle);

/* create the snippet object */
$snippet = $modx->newObject('modSnippet');
$snippet->set('name', 'AIKit');
$snippet->set('description', 'AIKit AI assistant prompt snippet');
$snippet->set('snippet', getSnippetContent($sources['build'] . 'elements/snippets/aikit.snippet.php'));
$snippet->set('category', 0);

$attributes = [
    \xPDO\Transport\xPDOTransport::UNIQUE_KEY => 'name',
    \xPDO\Transport\xPDOTransport::PRESERVE_KEYS => false,
    \xPDO\Transport\xPDOTransport::UPDATE_OBJECT => true,
];
$vehicle = $builder->createVehicle($snippet, $attributes);
$builder->putVehicle($vehicle);

$modx->log(modX::LOG_LEVEL_INFO, 'Packaged in snippet.');
flush();


$modx->log(modX::LOG_LEVEL_INFO, 'Adding package attributes and setup options...');
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
   // 'setup-options' => array(
   //     'source' => $sources['build'].'setup.options.php',
   // ),
));

/* zip up package */
$modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
$builder->pack();

$tend = microtime(true);
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO, "Package built in {$totalTime}\n");
