<?php

namespace modmore\AIKit\LLM\Models;

use modmore\AIKit\LLM\ModelResponse;
use modmore\AIKit\Model\Conversation;
use MODX\Revolution\modX;

interface ModelInterface
{
    public function __construct(modX $modx, array $config = [], array $tools = []);

    public function send(Conversation $conversation): ModelResponse;
}
