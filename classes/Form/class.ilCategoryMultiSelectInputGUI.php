<?php

require_once('./Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/Form/class.ilMultiSelectSearchInputGUI.php');

/**
 * Class ilCategoryMultiSelectInputGUI
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilCategoryMultiSelectInputGUI extends ilMultiSelectSearchInputGUI
{

    /**
     * @return string
     */
    protected function getValueAsJson()
    {
        global $ilDB;
        $query  = "SELECT obj_id, title FROM object_data WHERE type = 'cat' AND " . $ilDB->in("obj_id", $this->getValue(), false, "integer");
        $res    = $ilDB->query($query);
        $result = array();
        while ($row = $ilDB->fetchAssoc($res)) {
            $result[] = array("id" => $row['obj_id'], "text" => $row['title']);
        }

        return json_encode($result);
    }

    /**
     * @return mixed
     */
    public function getValues()
    {
        return $this->value;
    }
}
