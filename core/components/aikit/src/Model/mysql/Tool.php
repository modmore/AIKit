<?php
namespace modmore\AIKit\Model\mysql;

use xPDO\xPDO;

class Tool extends \modmore\AIKit\Model\Tool
{

    public static $metaMap = array (
        'package' => 'modmore\\AIKit\\Model\\',
        'version' => '3.0',
        'table' => 'aikit_tool',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'enabled' => 0,
            'class' => '',
            'tool_config' => '',
        ),
        'fieldMeta' => 
        array (
            'enabled' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 0,
            ),
            'class' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '200',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'tool_config' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'array',
                'null' => false,
                'default' => '',
            ),
        ),
        'indexes' => 
        array (
            'enabled' => 
            array (
                'alias' => 'enabled',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'enabled' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'class' => 
            array (
                'alias' => 'class',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'class' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
