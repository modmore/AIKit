<?php
namespace modmore\AIKit\Model\mysql;

use xPDO\xPDO;

class ExtFunction extends \modmore\AIKit\Model\ExtFunction
{

    public static $metaMap = array (
        'package' => 'modmore\\AIKit\\Model\\',
        'version' => '3.0',
        'table' => 'aikit_function',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'function' => '',
            'enabled' => 0,
            'function_config' => '',
        ),
        'fieldMeta' => 
        array (
            'function' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '200',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'enabled' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 0,
            ),
            'function_config' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'array',
                'null' => false,
                'default' => '',
            ),
        ),
        'indexes' => 
        array (
            'function' => 
            array (
                'alias' => 'function',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'function' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
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
        ),
    );

}
