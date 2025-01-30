<?php

/**
 * Commerce
 *
 * Copyright 2014-2015 by Mark Hamstra <mark@modmore.com>
 *
 * This file is part of Commerce, developed for modmore.
 *
 * It is built to be used with the MODX Revolution CMS.
 *
 * @category aikit
 * @package aikit-component
 * @author Mark Hamstra <mark@modmore.com>
 * @license See core/components/aikit/docs/license.txt
 * @link https://www.modmore.com/aikit/
 */

use modmore\AIKit\Model\Tool;

/**
 * The main Commerce Manager Controller.
 * In this class, we define stuff we want on all of our controllers.
 */
class AikitConfigurationManagerController extends \MODX\Revolution\modExtraManagerController
{
    /**
     * Initializes the main manager controller. In this case we set up the
     * Commerce class and add the shared javascript on all controllers.
     */
    public function initialize()
    {
        $this->setPlaceholder('tools', $this->getTools());
    }

    /**
     * Defines the lexicon topics to load in our controller.
     * @return array
     */
    public function getLanguageTopics()
    {
        return array('aikit:default');
    }

    /**
     * We can use this to check if the user has permission to see this
     * controller. We'll apply this in the admin section.
     * @return bool
     */
    public function checkPermissions()
    {
        return $this->modx->context->checkPolicy('aikit');
    }

    /**
     * The name for the template file to load.
     * @return string
     */
    public function getTemplateFile()
    {
        return 'configuration.tpl';
    }

    private function getTools(): array
    {
        $tools = [];

        /** @var Tool $tool */
        foreach ($this->modx->getIterator(Tool::class) as $tool) {
            $inst = $tool->getToolInstance();
            $o = $tool->toArray();
            $o['name'] = $inst->getToolName();
            $o['description'] = $inst->getToolDescription();
            $o['parameters'] = $inst->getToolParameters();
            $tools[] = $o;
        }

        return $tools;
    }
}
