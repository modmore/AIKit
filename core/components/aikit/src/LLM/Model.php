<?php

namespace modmore\AIKit\LLM;

use modmore\AIKit\LLM\Models\OpenAI;
use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use modmore\AIKit\Model\Tool;
use MODX\Revolution\modX;

class Model
{
    private modX $modx;
    private array $config;

    /** @var Tool[] */
    private array $tools = [];
    private OpenAI $model;

    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->getTools();

        // @todo enable alternative models
        $this->model = new OpenAI($modx, $config, $this->tools);
    }

    public function send(Conversation $conversation)
    {
        // Send all messages to the LLM
        $response = $this->model->send($conversation);

        // Create a message of the response
        /** @var Message $message */
        $message = $this->modx->newObject(Message::class);
        $message->fromArray([
            'conversation' => $conversation->get('id'),
            'llm_id' => $response->getMessageId(),
            'user_role' => Message::ROLE_ASSISTANT,
            'content' => $response->getResponseText(),
            'created_on' => time(),
            'prompt_token_count' => $response->getPromptTokens(),
            'response_token_count' => $response->getResponseTokens(),
        ]);
        $message->save();
        $conversation->addMany($message);

        switch ($response->getFinishReason()) {
            // If the finish reason indicates we need to run tools, run tools and send messages to LLM recursively
            case ModelResponse::FINISH_REASON_TOOL_CALLS:
                foreach ($response->getToolCalls() as $toolCall) {
                    $tool = $this->tools[$toolCall['function']['name']];
                    $args = json_decode($toolCall['arguments'], true, 512, JSON_THROW_ON_ERROR);
                    $function = $tool->get('function');

                    $functionResponse = function_exists($function) ? $function($args) : 'Error running function.';

                    /** @var Message $toolMessage */
                    $toolMessage = $this->modx->newObject(Message::class);
                    $toolMessage->fromArray([
                        'conversation' => $conversation->get('id'),
                        'tool_call_id' => $toolCall['id'],
                        'user_role' => Message::ROLE_TOOL,
                        'content' => $functionResponse,
                        'created_on' => time(),
                    ]);
                    $toolMessage->save();
                    $conversation->addMany($toolMessage);
                }

                // Send tool outputs to LLM and get the response
                return $this->send($conversation);


            // If the finish reason indicated we're done or encountered an error, just end.
            case ModelResponse::FINISH_REASON_STOP:
            case ModelResponse::FINISH_REASON_LENGTH:
            case ModelResponse::FINISH_REASON_CONTENT_FILTER:
            default:
                return $message;
        }
    }

    private function getTools(): void
    {
        /** @var Tool $tool */
        foreach ($this->modx->getCollection(Tool::class, ['enabled' => true]) as $tool) {
            try {
                $inst = $tool->getToolInstance();
                $this->tools[$inst->getToolName()] = $inst;
            } catch (\Throwable $e) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to load tool ' . $tool->get('id') . ': ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
            }
        }
    }
}
