<?php

namespace modmore\AIKit\LLM\Models;

use modmore\AIKit\LLM\ModelResponse;
use modmore\AIKit\LLM\Tools\ToolInterface;
use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use MODX\Revolution\modX;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class OpenAI implements ModelInterface
{
    protected modX $modx;
    protected array $config;
    protected ClientInterface $client;
    protected RequestFactoryInterface $requestFactory;
    /**
     * @var ToolInterface[]
     */
    private array $tools;

    public function __construct(modX $modx, array $config = [], array $tools = [])
    {
        $this->modx = $modx;
        $this->config = $config;

        $this->client = $this->modx->services->get(ClientInterface::class);
        $this->requestFactory = $this->modx->services->get(RequestFactoryInterface::class);
        $this->tools = $tools;

        $this->config['api_key'] = $this->config['api_key'] ?? $this->modx->getOption('aikit.openai_api_key');
        $this->config['model'] = $this->config['model'] ?? $this->modx->getOption('aikit.openai_model', null, 'gpt-4o-mini', true);
        $this->config['endpoint'] = $this->config['endpoint'] ?? $this->modx->getOption('aikit.openai_endpoint', null, 'https://api.openai.com/v1/', true);
    }

    public function send(Conversation $conversation): ModelResponse
    {
        $c = $this->modx->newQuery(Message::class);
        $c->where([
            'conversation' => $conversation->get('id'),
        ]);
        $c->sortby('created_on', 'ASC');
        $c->sortby('id', 'ASC');
        $messages = $this->modx->getCollection(Message::class, $c);
        $parsed = [];
        foreach ($messages as $message) {
            $parsed[] = $this->prepareMessage($message);
        }

        $requestData = [
            'model' => $this->config['model'], // Default to 'gpt-4o-mini' or use a different configured model
            'messages' => array_values($parsed),
            'tools' => $this->getToolsDefinitions()
        ];

        $requestBody = json_encode($requestData, JSON_THROW_ON_ERROR);
        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Sending request to OpenAI: ' . $requestBody);

        $request = $this->requestFactory
            ->createRequest('POST', $this->config['endpoint'] . 'chat/completions')
            ->withHeader('Authorization', 'Bearer ' . ($this->config['api_key'] ?? ''))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($requestBody));

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $responseData = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new ModelResponse($responseData);
        }

        $this->modx->log(modX::LOG_LEVEL_ERROR, get_class($this) . ' LLM message error: ' . (string)$response->getBody() . '  ' . $response->getReasonPhrase() . ' (' . $requestBody . ')',);
        throw new \RuntimeException(
            'Unexpected error processing your request: ' . (string)$response->getBody() . '  ' . $response->getReasonPhrase() . ' (' . $requestBody . ')',
            $response->getStatusCode()
        );
    }

    /**
     * @return \Closure
     */
    protected function prepareMessage(Message $message): array
    {
        $user = null;
        if ($message->get('user') > 0) {
            $user = $message->getOne('User');
        }
        $username = $user ? $user->get('username') : '';
        $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);

        switch ($message->get('user_role')) {
            case Message::ROLE_TOOL:
                return [
                    'role' => Message::ROLE_TOOL,
                    'content' => $message->get('content'),
                    'tool_call_id' => $message->get('tool_call_id'),
                ];

            case Message::ROLE_ASSISTANT:
            case Message::ROLE_DEVELOPER:
            case Message::ROLE_USER:
            default:
                return array_filter([
                    'role' => $message->get('user_role'), // Example: 'developer', 'user', 'assistant'
                    'content' => $message->get('content'),
                    'tool_calls' => $message->get('tool_calls'),
                    'name' => $username,
                ]);
        }
    }

    protected function getToolsDefinitions()
    {
        $tools = [];
        foreach ($this->tools as $name => $tool) {
            $props = [];
            $required = [];
            foreach ($tool->getModelParameters() as $key => $param) {
                if (!empty($param['required'])) {
                    $required[] = $key;
                    unset($param['required']);
                }
                $props[$key] = $param;
            }

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $tool->getToolDescription(),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $props,
                    ],
                    'required' => $required,
                ],
            ];
        }

        return $tools;
    }
}
