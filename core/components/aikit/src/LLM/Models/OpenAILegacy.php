<?php

namespace modmore\AIKit\LLM\Models;

use modmore\AIKit\Model\Message;

class OpenAILegacy extends OpenAI
{
    /**
     * @return \Closure
     */
    protected function prepareMessage(Message $message): array
    {
        $result = parent::prepareMessage($message);
        if ($result['role'] === Message::ROLE_DEVELOPER) {
            $result['role'] = 'system';
        }
        if (isset($result['name'])) {
            unset($result['name']);
        }
        return $result;
    }

    protected function getToolsDefinitions(): array
    {
        $tools = parent::getToolsDefinitions();
        foreach ($tools as &$tool) {
            if (isset($tool['function']['required'])) {
                unset($tool['function']['required']);
            }
        }
        return $tools;
    }
}
