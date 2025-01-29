<?php

namespace modmore\AIKit\LLM\Tools;

use MODX\Revolution\modX;

interface ToolInterface
{
    /**
     * Create an instance of the tool, taking in an array of the configuration by the site's admin.
     *
     * The config may be empty in which case you need to apply your own default values.
     *
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX $modx, array $config);

    /**
     * The name of the tool passed into the LLM and visible to the user. **Must be unique**. Use clear, descriptive names without space, period (.), or dash (-) characters. Instead, use underscore (_) characters or camel case.
     *
     * @return string
     */
    public function getToolName(): string;

    /**
     * The description passed into the LLM, explaining when and how to use this particular tool.
     *
     * This is a natural language prompt, so properly explaining its usage is important.
     *
     * You can use the provided user config to customise or let the site's admin determine part
     * of your prompt.
     *
     * @return string
     */
    public function getToolDescription(): string;

    /**
     * Set the parameters that the LLM should or must provide when calling your function.
     *
     * This takes the form of an object (array) based on JSON schema:
     *
     * - [parameterName] => [
     *      'type' => 'string',
     *      'description' => 'Description for the LLM to properly interpret and prepare the value.',
     *   ]
     * - [parameterName2] => [
     *      'type' => 'enum',
     *      'enum' => [
     *          'value1',
     *          'value2',
     *      ],
     *      'description' => 'Details of when and how to use this tool.',
     *   ]
     * - ... etc
     *
     *
     * @return array Returns the parameters as an array.
     */
    public function getModelParameters(): array;

    /**
     * An array of tool parameters that should be exposed to the site's admin to configure your tool.
     *
     * Can be empty, but it is recommended to allow the user to configure the tool.
     *
     * @return array
     */
    public function getToolParameters(): array;

    /**
     * Your tool is being called by the model! Do the thing!
     *
     * @param array $arguments
     * @return string
     */
    public function runTool(array $arguments): string;
}
