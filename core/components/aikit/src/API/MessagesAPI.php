<?php

namespace modmore\AIKit\API;

use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use MODX\Revolution\modX;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use xPDO\Om\xPDOCriteria;

class MessagesAPI implements ApiInterface
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

        $query = $this->createMessageQuery($limit, $offset);
        $conversationId = (int)$request->getQueryParams()['conversation'];
        $query->where(['conversation' => $conversationId]);


        if (isset($request->getQueryParams()['after_id'])) {
            $query->where(['id:>' => (int)$request->getQueryParams()['after_id']]);
        }

        if (isset($request->getQueryParams()['after_timestamp'])) {
            $query->where(['created_on:>' => $request->getQueryParams()['after_timestamp']]);
        }

        $total = 0; // @todo refactor to get totals before applying limit
        $messages = $this->modx->getCollection(Message::class, $query);

        $resultData = [
            'data' => array_map(static fn($message) => $message->toArray(), $messages),
            'total' => $total,
        ];

        return $this->createJsonResponse($resultData, 200);
    }

    private function handlePostRequest(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);


        // Require a conversation parameter and load the conversation object with that id.
        if (empty($body['conversation'])) {
            return $this->createJsonResponse(['error' => 'The conversation parameter is required'], 400);
        }

        /** @var Conversation $conversation */
        $conversation = $this->modx->getObject(Conversation::class, [
            'id' => $body['conversation'],
            'started_by' => $this->modx->user->get('id'),
        ]);
        if (!$conversation) {
            return $this->createJsonResponse(['error' => 'Conversation not found'], 404);
        }
        
        if (empty($body['content'])) {
            return $this->createJsonResponse(['error' => 'The content parameter is required'], 400);
        }

        /** @var Message $message */
        $message = $this->modx->newObject(Message::class);
        $message->fromArray([
            'conversation' => $conversation->get('id'),
            'user_role' => 'user',
            'user' => $this->modx->user->get('id'),
            'content' => $body['content'],
        ]);

        if (!$message->save()) {
            return $this->createJsonResponse(['error' => 'Failed to save message'], 500);
        }

        // @todo actually send the message to the chosen AI provider

        return $message->save()
            ? $this->createJsonResponse($message->toArray(), 201)
            : $this->createJsonResponse(['error' => 'Failed to create message'], 500);
    }

    private function getPaginationParams(array $queryParams): array
    {
        $page = isset($queryParams['page']) ? max(self::DEFAULT_PAGE, (int)$queryParams['page']) : self::DEFAULT_PAGE;
        $limit = isset($queryParams['limit']) ? max(self::DEFAULT_LIMIT, (int)$queryParams['limit']) : self::DEFAULT_LIMIT;

        return [$page, $limit];
    }

    private function createMessageQuery(int $limit, int $offset): xPDOCriteria
    {
        $query = $this->modx->newQuery(Message::class);
        $query->sortby('last_message_on', 'DESC');
        $query->where(['started_by' => $this->modx->user->get('id')]);
        $query->limit($limit, $offset);

        return $query;
    }

    private function createJsonResponse(array $data, int $statusCode): ResponseInterface
    {
        $factory = $this->modx->services->get(ResponseFactoryInterface::class);
        $response = $factory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
        $response->getBody()->write(json_encode($data));

        return $response;
    }
}