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
    private modX $modx;
    private array $config;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    /**
     * @var ToolInterface[]
     */
    private array $tools;

    public function __construct(modX $modx, array $config = [], array $tools = [])
    {
        $this->modx = $modx;
        $this->config = $config;

        $this->client = $this->modx->services->get(\Psr\Http\Client\ClientInterface::class);
        $this->requestFactory = $this->modx->services->get(\Psr\Http\Message\RequestFactoryInterface::class);
        $this->tools = $tools;
    }

    public function send(Conversation $conversation): ModelResponse
    {
        $requestData = [
            'model' => $this->config['model'] ?? 'gpt-4', // Default to 'gpt-4' or use a configured model
            'messages' => array_map([$this, 'prepareMessage'], $conversation->getMany('Messages')),
            'tools' => $this->getToolsDefinitions()
        ];

        $requestBody = json_encode($requestData, JSON_THROW_ON_ERROR);

        $request = $this->requestFactory
            ->createRequest('POST', 'https://api.openai.com/v1/chat/completions')
            ->withHeader('Authorization', 'Bearer ' . ($this->config['api_key'] ?? ''))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($requestBody));

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $responseData = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return new ModelResponse($responseData); // Assuming ModelResponse can accept the raw data
        }

        throw new \RuntimeException(
            'Failed to communicate with OpenAI: ' . $response->getReasonPhrase(),
            $response->getStatusCode()
        );
    }

    /**
     * @return \Closure
     */
    private function prepareMessage(Message $message): array
    {
        $profile = null;
        if ($message->get('user') > 0) {
            $user = $message->getOne('User');
            $profile = $user?->getOne('Profile');
        }

        switch ($message->get('user_role')) {
            case Message::ROLE_TOOL:
                return [
                    'role' => Message::ROLE_TOOL,
                    'content' => $message->get('content'),
                    'tool_call_id' => $message->get('tool_call_id'),
                ];

            case Message::ROLE_DEVELOPER:
            case Message::ROLE_ASSISTANT:
            case Message::ROLE_USER:
            default:
                return [
                    'role' => $message->get('user_role'), // Example: 'developer', 'user', 'assistant'
                    'content' => $message->get('content'),
                    'name' => $profile ? $profile->get('fullname') : '',
                ];
        }
    }

    private function getToolsDefinitions()
    {
        $tools = [];
        foreach ($this->tools as $name => $tool) {
            $props = [];
            $required = [];
            foreach ($tool->getModelParameters() as $key => $param) {
                if ($param['required']) {
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
                    'required' => $required
                ],
                'strict' => true,
            ];
        }

        return $tools;
    }
}
