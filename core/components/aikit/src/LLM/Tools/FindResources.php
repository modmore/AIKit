<?php

namespace modmore\AIKit\LLM\Tools;

use MODX\Revolution\modResource;
use MODX\Revolution\modX;
use Throwable;

class FindResources implements ToolInterface
{
    private array $config;
    private modX $modx;

    /**
     * @param modX $modx
     * @param array $config
     * @inheritDoc
     */
    public function __construct(modX $modx, array $config)
    {
        $this->config = $config;
        $this->modx = $modx;
    }

    /**
     * @inheritDoc
     */
    public function getToolName(): string
    {
        return 'find_resources';
    }

    /**
     * @inheritDoc
     */
    public function getToolDescription(): string
    {
        return 'Gives you real time access to find pages, resources, links, and other public information on the website. Always look for context queues from the user to indicate if it references information that may exist on the website. Always double check references instead of making up information you don\'t have. Insert links to related pages on the website. Use the Resource ID from the response with the get_resource_details tool to load detailed resource information.';
    }

    /**
     * @inheritDoc
     */
    public function getModelParameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Optional but recommended. Provide a query string to look for resources matching the query. The query must be concise and specific, prefer a single word where possible.'
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getToolParameters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function runTool(array $arguments): string
    {
        $query = $arguments['query'] ?? '';
        $query = !empty($query) ? $this->modx->quote('%' . $query . '%') : '';
        $stmt = $this->modx->query("
            SELECT uri, id, FROM_UNIXTIME(editedon, '%Y-%m-%d') as editedon 
            FROM modx_site_content AS s
            WHERE s.deleted = 0 AND s.published = 1 AND s.searchable = 1 
            " . (!empty($query) ? "AND (s.pagetitle LIKE {$query} OR s.longtitle LIKE {$query} OR s.introtext LIKE {$query})" : "") . "
            GROUP BY s.id
            ORDER BY s.id ASC
        ");
        if (!$stmt) {
            return "Error running query.";
        }
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }

        if (!empty($results)) {
            return json_encode($results, JSON_THROW_ON_ERROR);
        }
        return "No results found for query.";
    }
}
