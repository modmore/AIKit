<?php
/* @var modX $modx */

if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_UPGRADE:
        case xPDOTransport::ACTION_INSTALL:
            $modx =& $object->xpdo;

            require_once $modx->getOption('core_path') . 'components/aikit/vendor/autoload.php';
            $manager = $modx->getManager();
            $logLevel = $modx->setLogLevel(modX::LOG_LEVEL_ERROR);

            $objects = [
                \modmore\AIKit\Model\Conversation::class,
                \modmore\AIKit\Model\Tool::class,
                \modmore\AIKit\Model\Message::class
            ];
            foreach ($objects as $obj) {
                $manager->createObjectContainer($obj);
            }

            // For database updates, we only want absolutely fatal errors.
            $modx->setLogLevel(modX::LOG_LEVEL_FATAL);


            // Return log level to normal.
            $modx->setLogLevel($logLevel);

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
    }
}
return true;

