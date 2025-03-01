<?php

namespace modmore\AIKit\LLM\Vectors;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use MODX\Revolution\modX;

class Pinecone implements VectorDatabaseInterface
{
    private array $config;
    private Client $client;
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

    public function delete(array $ids): bool
    {
        try {
            $response = $this->client->post("databases/{$this->contentIndex}/vectors/delete", [
                'json' => [
                    'ids' => $ids,
                ],
            ]);

            return $response?->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function query(string $query, array $options = []): string
    {
        try {
            $response = $this->client?->post("records/namespaces/{$this->contentIndex}/search", [
                'json' => [
                    'query' => [
                        'inputs' => ['text' => $query],
                        'top_k' => 5,
                    ],
                    'rerank' => [
                        'model' => 'bge-reranker-v2-m3',
                        'top_n' => 3,
                        'rank_fields' => ['text'],
                    ]
                ],
            ]);

            $json = json_decode($response->getBody()->getContents(), true);
            $augmented = [];
            foreach ($json['result']['hits'] as $hit) {
                $augmented[] = "Potential relevant context from resource {$hit['fields']['resource_id']} (\"{$hit['fields']['pagetitle']}\"):\n{$hit['fields']['introtext']}\n{$hit['fields']['content']}";
            }
            return implode("\n\n", $augmented);
        } catch (GuzzleException $e) {
        }
        return "No additional context found.";
    }
}