<?php
namespace modmore\AIKit\Model;

use modmore\AIKit\LLM\Tools\ToolInterface;
use RuntimeException;
use xPDO\xPDO;

/**
 * Class Tool
 *
 * @property boolean $enabled
 * @property string $class
 * @property array $tool_config
 *
 * @package modmore\AIKit\Model
 */
class Tool extends \modmore\AIKit\Model\BaseObject
{
    public function getToolInstance()
    {
        $className = $this->get('class');
        if (!class_exists($className)) {
            throw new RuntimeException('Tool class not available: ' . $className);
        }

        if (!is_subclass_of($className, ToolInterface::class, true)) {
            throw new RuntimeException('Tool does not implement the ToolInterface');
        }

        $props = $this->get('tool_config') ?? [];
        try {
            return new $className($this->xpdo, $props);
        } catch (\Throwable $e) {
            throw new RuntimeException('Tool could not be instantiated: ' . $e->getMessage());
        }
    }
}
