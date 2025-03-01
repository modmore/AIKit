<?php

namespace modmore\AIKit\API;

use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use MODX\Revolution\modX;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use xPDO\Om\xPDOCriteria;

class ConversationsAPI implements ApiInterface
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 10;

    private modX $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            'GET' => $this->handleGetRequest($request),
            'POST' => $this->handlePostRequest($request),
            default => $this->createJsonResponse(['error' => 'Method not allowed'], 405),
        };
    }

    private function handleGetRequest(ServerRequestInterface $request): ResponseInterface
    {
        [$page, $limit] = $this->getPaginationParams($request->getQueryParams());
        $offset = ($page - 1) * $limit;

        $total = 0; // refactor create query to be able to get a total
        $query = $this->createConversationQuery($limit, $offset);
        $conversations = $this->modx->getCollection(Conversation::class, $query) ?? [];

        $resultData = [
            'data' => array_map(static fn($conversation) => $conversation->toArray(), $conversations),
            'total' => $total,
        ];

        return $this->createJsonResponse($resultData, 200);
    }

    private function handlePostRequest(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);
        $body['title'] = $body['title'] ?? 'New conversation';

        /** @var Conversation $conversation */
        $conversation = $this->modx->newObject(Conversation::class);
        $conversation->fromArray([
            'title' => $body['title'],
            'started_on' => time(),
            'started_by' => $this->modx->user->get('id'),
        ]);

        if (!$conversation->save()) {
            return $this->createJsonResponse(['error' => 'Failed to create conversation'], 500);
        }

        if (!$this->addPrompt($conversation, (string)$this->modx->getOption('aikit.system_prompt'))) {
            return $this->createJsonResponse(['error' => 'Failed to add system prompt'], 500);
        }

        // @todo allow the creation of a conversation to add its own system prompt (like provide current context)

        return $this->createJsonResponse(['data' => $conversation->toArray()], 201);
    }

    private function getPaginationParams(array $queryParams): array
    {
        $page = isset($queryParams['page']) ? max(self::DEFAULT_PAGE, (int)$queryParams['page']) : self::DEFAULT_PAGE;
        $limit = isset($queryParams['limit']) ? max(self::DEFAULT_LIMIT, (int)$queryParams['limit']) : self::DEFAULT_LIMIT;

        return [$page, $limit];
    }

    private function createConversationQuery(int $limit, int $offset): xPDOCriteria
    {
        $query = $this->modx->newQuery(Conversation::class);
        $query->sortby('last_message_on', 'DESC');
        $query->where(['started_by' => $this->modx->user->get('id')]);
        $query->limit($limit, $offset);

        return $query;
    }

    private function createJsonResponse(array $data, int $statusCode): ResponseInterface
    {
        /** @var ResponseFactoryInterface $factory */
        $factory = $this->modx->services->get(ResponseFactoryInterface::class);
        $response = $factory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
        $response->getBody()->write(json_encode($data));

        return $response;
    }

    private function addPrompt(Conversation $conversation, string $prompt): bool
    {
        // Process MODX placeholders and tags
        $parser = $this->modx->getParser();
        $parser->processElementTags('', $prompt, true, true, '[[', ']]', [], 10);
        $parser->processElementTags('', $prompt, true, true, '[[++', ']]', [], 10);

        /** @var Message $message */
        $message = $this->modx->newObject(Message::class);
        $message->fromArray([
            'conversation' => $conversation->get('id'),
            'user_role' => Message::ROLE_DEVELOPER,
            'user' => 0,
            'created_on' => time(),
            'content' => $prompt,
        ]);

        return $message->save();
    }
}
