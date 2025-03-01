<?php

namespace modmore\AIKit\API;

use JsonException;
use modmore\AIKit\LLM\Model;
use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use MODX\Revolution\modUser;
use MODX\Revolution\modX;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use xPDO\Om\xPDOCriteria;

class MessagesAPI implements ApiInterface
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 30;

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
        try {
            $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $this->createJsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $conversationId = (int)$request->getQueryParams()['conversation'];
        // Require a conversation parameter and load the conversation object with that id.
        if (empty($conversationId)) {
            return $this->createJsonResponse(['error' => 'The conversation parameter is required'], 400);
        }

        /** @var Conversation $conversation */
        $conversation = $this->modx->getObject(Conversation::class, [
            'id' => $conversationId,
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
            'created_on' => time(),
            'content' => $body['content'],
        ]);

        if (!$message->save()) {
            return $this->createJsonResponse(['error' => 'Failed to save message'], 500);
        }

        $model = new Model($this->modx);
        try {
            $model->send($conversation);
        } catch (Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to send message to model: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
            return $this->createJsonResponse(['error' => 'Failed to send message to model. More details are available in the MODX Error Log.'], 500);
        }

        if ($conversation->get('title') === 'New conversation') {
            $this->generateTitle($model, $conversation);
        }

        return $this->createJsonResponse(['data' => ['message' => $message->get('id')]], 201);
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
        $query->leftJoin(modUser::class, 'User');
        $query->select($this->modx->getSelectColumns(Message::class, 'Message'));
        $query->select($this->modx->getSelectColumns(modUser::class, 'User', 'user_', ['id', 'username']));
        $query->sortby('created_on', 'DESC');
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

    private function generateTitle(Model $model, Conversation $conversation): void
    {
        $c = $this->modx->newQuery(Message::class);
        $c->where([
            'conversation' => $conversation->get('id'),
            'user_role' => Message::ROLE_USER
        ]);
        $c->sortby('created_on', 'ASC');

        $userMessages = [];
        foreach ($this->modx->getCollection(Message::class, $c) as $message) {
            $userMessages[] = $message->get('content');
        }

        $content = implode("\n\n", $userMessages);
        $prompt = "Based on the users prompt below, generate a short, concise, and specific title. Use at most 100 characters, but the shorter the better. Do not try to answer the prompt. Use sentence case. \n\n" . $content;

        try {
            $title = $model->prompt($prompt);
            $conversation->set('title', $title);
            $conversation->save();
        } catch (Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,
                'Failed to generate title: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
        }
    }
}