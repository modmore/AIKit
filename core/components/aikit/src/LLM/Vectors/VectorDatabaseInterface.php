<?php

namespace modmore\AIKit\LLM\Vectors;

use MODX\Revolution\modX;

interface VectorDatabaseInterface
{
    public function __construct(modX $modx, array $config = []);

    /**
     * Insert/update (upsert) the data. Not yet vectorised, so may need additional logic for that.
     *
     * @param $id
     * @param string $content
     * @param array $metadata
     * @return bool
     */
    public function index($id, string $content, array $metadata = []): bool;

    /**
     * Delete vectors from the database
     * @param array $ids Vector IDs to delete
     * @return bool Success status
     */
    public function delete(array $ids): bool;

    /**
     * Augment chat completion with relevant context from vector search
     * @param string $query User query or message
     * @param array $options Additional options for RAG
     * @return array Context and augmented prompt
     */
    public function query(string $query, array $options = []): string;
}
