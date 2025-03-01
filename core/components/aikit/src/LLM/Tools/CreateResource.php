<?php

namespace modmore\AIKit\LLM\Tools;

use MODX\Revolution\modResource;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modX;
use Throwable;

class CreateResource implements ToolInterface
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
        return 'create_resource';
    }

    /**
     * @inheritDoc
     */
    public function getToolDescription(): string
    {
        return 'Create a new resource based on the users prompt. Before calling this function, require the user to approve the creating resource in a separate message. Show each of the parameters and the values you will use.';
    }

    /**
     * @inheritDoc
     */
    public function getModelParameters(): array
    {
        $templatesList = [];
        $templates = $this->modx->getIterator(modTemplate::class);
        foreach ($templates as $template) {
            $templatesList[] = [
                'id' => $template->get('id'),
                'name' => $template->get('templatename')
            ];
        }

        return [
            'parent' => [
                'type' => 'integer',
                'description' => 'The ID of the parent resource to add the resource to. Ask the user to confirm the parent and use the find_resources tool to find the resource ID if needed. Provide 0 to create in the top-level of the site.'
            ],
            'template' => [
                'type' => 'integer',
                'description' => 'The ID of the template to use for the new resource. Available templates: ' . json_encode($templatesList,
                        JSON_THROW_ON_ERROR) . '. Ask the user what template to use if not provided.',
            ],
            'pagetitle' => [
                'type' => 'string',
                'description' => 'The title for the resource. Generate this automatically for the user.',
            ],
            'content' => [
                'type' => 'string',
                'description' => 'The content of the new resource. Generate this based on the users\' prompt. Allow the user to iterate on the content to create before finally calling the create_resource tool. Generate the content as HTML.',
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
        $parent = $arguments['parent'] ?? 0;
        $template = $arguments['template'] ?? 0;
        $content = $arguments['content'] ?? '';
        $title = $arguments['pagetitle'] ?? '';

        try {
            // Validate parent
            if ($parent !== 0) {
                $parentResource = $this->modx->getObject(\MODX\Revolution\modResource::class, ['id' => $parent]);
                if (!$parentResource) {
                    return 'Invalid parent resource ID provided.';
                }
            }

            // Validate template
            if ($template !== 0) {
                $templateObj = $this->modx->getObject(\MODX\Revolution\modTemplate::class, ['id' => $template]);
                if (!$templateObj) {
                    return 'Invalid template ID provided.';
                }
            }

            // Create resource
            /** @var \MODX\Revolution\modResource $resource */
            $resource = $this->modx->newObject(\MODX\Revolution\modResource::class);
            $resource->fromArray([
                'parent' => $parent,
                'template' => $template,
                'content' => $content,
                'pagetitle' => $title,
                'published' => false,
            ]);

            if ($resource->save()) {
                return json_encode([
                    'success' => true,
                    'message' => 'Resource created successfully',
                    'id' => $resource->get('id'),
                    'edit_url' => $this->config['site_url'] . $this->modx->config['manager_url'] . '?a=resource/update&id=' . $resource->get('id'),
                ], JSON_THROW_ON_ERROR);
            }

            return 'Failed to create resource.';
        } catch (Throwable $e) {
            return "Error creating resource: {$e->getMessage()}";
        }
    }
}
