<?php
/**
 * @var \MODX\Revolution\modX $modx
 */

switch ($modx->event->name) {
    case 'OnManagerPageBeforeRender':
        $assetsUrl = $modx->getOption('aikit.assets_url', null, $modx->getOption('assets_url') . 'components/aikit/');
        $controller->addJavascript($assetsUrl . 'mgr/aikit.js');
        $controller->addCss($assetsUrl . 'mgr/aikit.css');
        $controller->addHtml(<<<HTML
<script>
(() => {
    MODx.on('ready', () => {
        const assistentElement = document.createElement('li');
        assistentElement.id = 'aikit-assistant';
        
        const leftbarTrigger = document.getElementById('modx-leftbar-trigger');
        leftbarTrigger.parentNode.insertBefore(assistentElement, leftbarTrigger);
        
        const assistant = new AIKit();
        assistant.initialize(assistentElement, {
            assetsUrl: '$assetsUrl'
        });
    })
})()


</script>
HTML);
        break;
}

return;