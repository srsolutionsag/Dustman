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
<#3>
<?php
/** @var $ilDB ilDBInterface */
$result = $ilDB->fetchAssoc(
    $ilDB->query("SELECT config_value FROM xdustman_config WHERE config_key LIKE 'checkdates';")
);

// migrate configuration from serialized to json.
if (null !== $result['config_value']) {
    /** @noinspection UnserializeExploitsInspection */
    $data = unserialize($result['config_value']);
    $data = (!empty($data)) ? $ilDB->quote(json_encode($data), 'text') : 'NULL';
    $ilDB->manipulate(
            "UPDATE xdustman_config SET config_value = $data WHERE config_key LIKE 'checkdates';"
    );
}
?>
<#4>
<?php
/** @var $ilDB ilDBInterface */
$result = $ilDB->fetchAssoc(
    $ilDB->query("SELECT config_value FROM xdustman_config WHERE config_key LIKE 'keywords';")
);

// migrate configuration from serialized to json.
if (null !== $result['config_value']) {
    /** @noinspection UnserializeExploitsInspection */
    $data = unserialize($result['config_value']);
    $data = (!empty($data)) ? $ilDB->quote(json_encode($data), 'text') : 'NULL';
    $ilDB->manipulate(
        "UPDATE xdustman_config SET config_value = $data WHERE config_key LIKE 'keywords';"
    );
}
?>
<#5>
<?php
/** @var $ilDB ilDBInterface */
$result = $ilDB->fetchAssoc(
    $ilDB->query("SELECT config_value FROM xdustman_config WHERE config_key LIKE 'dont_delete_objects_in_category';")
);

// migrate configuration from string to json.
if (null !== $result['config_value']) {
    $data = explode(',', $result['config_value']);
    $data = (!empty($data[0])) ? $ilDB->quote(json_encode($data), 'text') : 'NULL';
    $ilDB->manipulate(
        "UPDATE xdustman_config SET config_value = $data WHERE config_key LIKE 'dont_delete_objects_in_category';"
    );
}
?>