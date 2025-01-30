<?php

namespace modmore\AIKit\LLM\Tools;

use MODX\Revolution\modResource;
use MODX\Revolution\modX;
use Throwable;

class GetResourceDetails implements ToolInterface
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
        return 'get_resource_details';
    }

    /**
     * @inheritDoc
     */
    public function getToolDescription(): string
    {
        return 'Use this tool to get more information about a resource, concept, or service you are unfamiliar with from an integer resource ID. Use the find_resources tool first to identify relevant resources oin a topic. The tool will retrieve metadata, like title, description, published state, and last edit dates, as well as full HTML-formatted content. Provide at least one, or multiple comma separated, resource IDs.';
    }

    /**
     * @inheritDoc
     */
    public function getModelParameters(): array
    {
        return [
            'resource_id' => [
                'type' => 'string',
                'required' => true,
                'description' => 'The ID of the resource to get details for. When loading multiple resources, separate them with a comma. Max 5 resources per lookup.'
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
        $ids = array_map('intval', array_map('trim', explode(',', $arguments['resource_id'])));
        $return = [];

        foreach ($ids as $id) {
            $resource = $this->modx->getObject(modResource::class, [
                'id' => $id,
                'deleted' => false,
            ]);
            if ($resource) {
                $content = $resource->get('content');
                try {
                    $parser = $this->modx->getParser();
                    $maxIterations = (int)$this->modx->getOption('parser_max_iterations', null, 10);
                    $parser->processElementTags('', $content, false, false, '[[', ']]', [], $maxIterations);
                    $parser->processElementTags('', $content, true, true, '[[', ']]', [], $maxIterations);
                } catch (Throwable $e) { }

                $return[$id] = $resource->toArray();
                $return[$id]['content'] = $content;
                $return[$id]['uri'] = $this->modx->makeUrl($resource->get('id'), '', '', 'full');
            }
        }

        if (empty($return)) {
            return 'Resources not found.';
        }

        return json_encode($return, JSON_THROW_ON_ERROR);
    }
}
