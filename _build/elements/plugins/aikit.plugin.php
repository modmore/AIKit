<?php
/**
 * @var \MODX\Revolution\modX $modx
 */

switch ($modx->event->name) {
    case 'OnManagerPageBeforeRender':
        $assetsUrl = $modx->getOption('aikit.assets_url', null, $modx->getOption('assets_url') . 'components/aikit/');
        $showSystemPrompt = !empty($modx->getOption('aikit.system_prompt_visible')) ? 'true' : 'false';
        $controller->addJavascript($assetsUrl . 'mgr/aikit.js');
        $controller->addJavascript('https://cdn.jsdelivr.net/npm/markdown-it/dist/markdown-it.min.js'); // @todo ship local
        $controller->addCss($assetsUrl . 'mgr/aikit.css');
        $controller->addHtml(<<<HTML
<script>
(() => {
    Ext.onReady(() => {
        const assistentElement = document.createElement('li');
        assistentElement.id = 'aikit-assistant';
        
        const leftbarTrigger = document.getElementById('modx-leftbar-trigger');
        leftbarTrigger.parentNode.insertBefore(assistentElement, leftbarTrigger);
        
        const assistant = new AIKit();
        assistant.initialize(assistentElement, {
            assetsUrl: '$assetsUrl',
            showSystemPrompt: $showSystemPrompt,
        })
    })
})()


</script>
HTML);
        break;
}

return;