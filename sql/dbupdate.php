<#1>
<?php
// this step has been removed due to the migration to ActiveRecord.
// Do not remove it though, otherwise update errors may occur.
?>
<#2>
<?php
/** @var $ilDB ilDBInterface */
if (!$ilDB->tableExists('xdustman_config')) {
    $ilDB->createTable('xdustman_config',
        [
            'config_key' => [
                'type' => 'text',
                'length' => 128,
                'notnull' => true,
            ],

            'config_value' => [
                'type' => 'clob',
                'notnull' => false,
            ],
        ]
    );
}
?>