<?php

namespace modmore\AIKit\API\Conversation;

use modmore\AIKit\API\ApiInterface;
use modmore\AIKit\Model\Conversation;
use modmore\AIKit\Model\Message;
use MODX\Revolution\modX;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AwaitAPI implements ApiInterface
{
    private modX $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            'GET' => $this->handleGetRequest($request),
            default => $this->createJsonResponse(['error' => 'Method not allowed'], 405),
        };
    }

    private function handleGetRequest(ServerRequestInterface $request): ResponseInterface
    {
        $conversationId = (int)$request->getQueryParams()['conversation'];
        $conversation = $this->modx->getObject(Conversation::class, [
            'id' => $conversationId,
            'started_by' => $this->modx->user->get('id'),
        ]);
        if (!$conversation) {
            return $this->createJsonResponse(['error' => 'Conversation not found.'], 404);
        }

        $lastMessage = (int)$request->getQueryParams()['last_message'];

        $timeout = 28; // timeout in seconds
        $pollInterval = 1; // interval in seconds
        $startTime = time();

        do {
            $messageCount = $this->modx->getCount(Message::class, [
                'conversation' => $conversationId,
                'id:>' => $lastMessage,
            ]);

            if ($messageCount > 0) {
                $messages = $this->modx->getCollection(Message::class, [
                    'conversation' => $conversationId,
                    'id:>' => $lastMessage,
                ]);

                // Prepare the new messages as an array of their data
                $newMessages = array_map(static function($message) {
                    $a = $message->toArray();
                    if ($user = $message->getOne('User')) {
                        $a['user_username'] = $user->get('username');
                    }
                    return $a;
                }, $messages);
                return $this->createJsonResponse(['data' => $newMessages], 200);
            }

            // Wait for the poll interval before checking again
            sleep($pollInterval);
        } while ((time() - $startTime) < $timeout);


        return $this->createJsonResponse(['data' => []], 204);
    }

    public function createJsonResponse(array $data, int $statusCode): ResponseInterface
    {
        /** @var ResponseFactoryInterface $factory */
        $factory = $this->modx->services->get(ResponseFactoryInterface::class);
        $response = $factory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
        $response->getBody()->write(json_encode($data));

        return $response;
    }
}