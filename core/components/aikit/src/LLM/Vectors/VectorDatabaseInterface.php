<?php

namespace modmore\AIKit\LLM\Vectors;

use MODX\Revolution\modX;

interface VectorDatabaseInterface
{
    public function __construct(modX $modx, array $config = []);

    public function index($id, string $content, array $metadata = []): bool;

    /**
     * Upsert vectors into the database
     * @param array $vectors Array of vectors to insert/update
     * @param array $metadata Optional metadata for the vectors
     * @return bool Success status
     */
    public function upsert(array $vectors, array $metadata = []): bool;

    /**
     * Delete vectors from the database
     * @param array $ids Vector IDs to delete
     * @return bool Success status
     */
    public function delete(array $ids): bool;

    /**
     * Query vectors by similarity
     * @param array $vector Query vector
     * @param int $topK Number of results to return
     * @param array $filters Optional metadata filters
     * @return array Query results
     */
    public function query(array $vector, int $topK = 10, array $filters = []): array;

    /**
     * Augment chat completion with relevant context from vector search
     * @param string $query User query or message
     * @param array $options Additional options for RAG
     * @return array Context and augmented prompt
     */
    public function augmentChatCompletion(string $query, array $options = []): string;
}
