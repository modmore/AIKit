<?php

namespace modmore\AIKit\LLM\Vectors;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use MODX\Revolution\modX;

class Pinecone implements VectorDatabaseInterface
{
    private array $config;
    private ?Client $client = null;
    private string $contentIndex;
    private modX $modx;

    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => $config['endpoint'] ?? $this->modx->getOption('aikit.pinecone_endpoint'),
            'headers' => [
                'Api-Key' => $config['api_key'] ?? $this->modx->getOption('aikit.pinecone_api_key'),
                'Content-Type' => 'application/json',
                'X-Pinecone-API-Version' => '2025-01',
            ],
        ]);
        $this->contentIndex = (string)$modx->getOption('aikit.pinecone_content_index');
    }

    public function index($id, string $content, array $metadata = []): bool
    {
        $data = [
            'id' => 'resource_' . $id,
            'text' => $content,
            'resource_id' => $id,
        ] + $metadata;

        try {
            $response = $this->client->post("/records/namespaces/{$this->contentIndex}/upsert", [
                'json' => $data
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Pinecone non-200 response: ' . $response->getBody()->getContents());
                return false;
            }

            return true;
        } catch (RequestException $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Pinecone exception: ' . $e->getResponse()->getBody()->getContents());
            return false;
        }
    }

    public function upsert(array $vectors, array $metadata = []): bool
    {
        try {
            $response = $this->client?->post("databases/{$this->contentIndex}/vectors/upsert", [
                'json' => [
                    'vectors' => array_map(function ($vector, $key) use ($metadata) {
                        return [
                            'id' => (string)$key,
                            'values' => $vector,
                            'metadata' => $metadata[$key] ?? [],
                        ];
                    }, $vectors, array_keys($vectors)),
                ],
            ]);

            return $response?->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function delete(array $ids): bool
    {
        try {
            $response = $this->client?->post("databases/{$this->contentIndex}/vectors/delete", [
                'json' => [
                    'ids' => $ids,
                ],
            ]);

            return $response?->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function query(array $vector, int $topK = 10, array $filters = []): array
    {
        try {
            $response = $this->client?->post("databases/{$this->contentIndex}/query", [
                'json' => [
                    'vector' => $vector,
                    'topK' => $topK,
                    'filter' => $filters,
                ],
            ]);
            if ($response?->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
        } catch (GuzzleException $e) {
        }

        return [];
    }

    public function augmentChatCompletion(string $query, array $options = []): array
    {
        // Implementation depends on the embedding model being used
        return [
            'context' => [],
            'augmented_prompt' => $query,
        ];
    }
}