<?php

namespace modmore\AIKit\LLM;

use modmore\AIKit\LLM\Models\ModelInterface;
use modmore\AIKit\LLM\Tools\ToolInterface;
use modmore\AIKit\LLM\Vectors\VectorDatabaseInterface;
use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use modmore\AIKit\Model\Tool;
use MODX\Revolution\modX;

class Model
{
    private modX $modx;
    private array $config;

    /** @var ToolInterface[] */
    private array $tools = [];
    private ModelInterface $model;

    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->getTools();

        try {
            $class = $this->modx->getOption('aikit.model', null, '', true);
            if (!empty($class) && is_subclass_of($class, ModelInterface::class, true)) {
                $this->model =  new $class($this->modx, $config, $this->tools);
            }
        } catch (\Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to load vector database: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
        }
    }

    public function send(Conversation $conversation): Message
    {
        if ($vectorDb = $this->getVectorDatabase()) {
            $c = $this->modx->newQuery(Message::class);
            $c->where([
             'conversation' => $conversation->get('id'),
             'user_role' => Message::ROLE_USER
            ]);
            $c->sortby('created_on', 'DESC');
            $c->limit(1);
            // Get last user message to use for vector search
            $lastUserMessage = $this->modx->getObject(Message::class, $c);

            // If we have a last user message, query for relevant context
            if ($lastUserMessage && !$lastUserMessage->get('is_vector_augmented')) {
                $lastUserMessage->set('is_vector_augmented', true);
                $lastUserMessage->save();

                $context = $vectorDb->query($lastUserMessage->get('content'));
                if (!empty($context)) {
                    /** @var Message $contextMessage */
                    $contextMessage = $this->modx->newObject(Message::class);
                    $contextMessage->fromArray([
                        'conversation' => $conversation->get('id'),
                        'user_role' => Message::ROLE_DEVELOPER,
                        'content' => $context,
                        'created_on' => time(),
                    ]);
                    $contextMessage->save();
                    $conversation->addMany($contextMessage);
                }
            }
        }

        // Send all messages to the LLM
        $response = $this->model->send($conversation);
        $this->modx->log(modX::LOG_LEVEL_INFO, print_r($response->getRawResponse(), true));

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
            'tool_calls' => $response->getToolCalls(),
        ]);
        $message->save();

        $conversation->set('prompt_token_count', $conversation->get('prompt_token_count') + $response->getPromptTokens());
        $conversation->set('response_token_count', $conversation->get('response_token_count') + $response->getResponseTokens());
        $conversation->save();
        $conversation->addMany($message);

        switch ($response->getFinishReason()) {
            // If the finish reason indicates we need to run tools, run tools and send messages to LLM recursively
            case ModelResponse::FINISH_REASON_TOOL_CALLS:
                foreach ($response->getToolCalls() as $toolCall) {
                    $tool = $this->tools[$toolCall['function']['name']];
                    $args = json_decode($toolCall['function']['arguments'], true, 512, JSON_THROW_ON_ERROR);

                    try {
                        $functionResponse = $tool->runTool($args);
                    } catch (\Throwable $e) {
                        $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to run tool ' . $tool->getToolName() . ': ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
                        $functionResponse = 'Failed to run tool: ' . $e->getMessage();
                    }

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

    public function prompt(string $prompt)
    {
        /** @var Conversation $conversation */
        $conversation = $this->modx->newObject(Conversation::class);
        $conversation->fromArray([
            'title' => 'New conversation',
            'started_on' => time(),
        ]);
        $conversation->save();
        /** @var Message $message */
        $message = $this->modx->newObject(Message::class);
        $message->fromArray([
            'conversation' => $conversation->get('id'),
            'user_role' => Message::ROLE_USER,
            'content' => $prompt,
            'created_on' => time(),
        ]);
        $message->save();
        $conversation->addMany($message);

        try {
            $response = $this->send($conversation);
        } catch (\Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to send prompt to model: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
            return $this->modx->lexicon('aikit.error.prompt_failed');
        }

        return $response->get('content');
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

    public function getVectorDatabase(): ?VectorDatabaseInterface
    {
        try {
            $class = $this->modx->getOption('aikit.vector_database', null, '', true);
            if (!empty($class) && is_subclass_of($class, VectorDatabaseInterface::class, true)) {
                return new $class($this->modx);
            }
        } catch (\Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to load vector database: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
        }
        return null;
    }
}
