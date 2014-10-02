<?php
require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('class.ilDustmanConfig.php');
require_once('class.ilDustmanPlugin.php');
require_once('./Services/Component/classes/class.ilComponent.php');
require_once('./Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/Form/class.ilMultiSelectSearchInputGUI.php');
require_once('./Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/Form/class.ilCategoryMultiSelectInputGUI.php');
require_once('./Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/Form/class.ilMultipleTextInputGUI.php');
require_once('./Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/Form/class.ilMultiDateInputGUI.php');

/**
 * Dustman Configuration
 *
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilDustmanConfigGUI extends ilPluginConfigGUI {

	/** @var \ilDustmanConfig  */
    protected $object;

    /** @var array  */
    protected $fields = array();

	/** @var string  */
    protected $table_name = '';

	/** @var  ilPropertyFormGUI */
	protected $form;


	function __construct() {
		global $ilCtrl, $tpl, $ilTabs;
		/**
		 * @var $ilCtrl ilCtrl
		 * @var $tpl    ilTemplate
		 * @var $ilTabs ilTabsGUI
		 */
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
		$this->pl = new ilDustmanPlugin();
		$this->object = new ilDustmanConfig($this->pl->getConfigTableName());
	}


	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->table_name;
	}


	/**
	 * @return ilDustmanConfig
	 */
	public function getObject() {
		return $this->object;
	}


	/**
	 * Handles all commmands, default is 'configure'
	 */
	public function performCommand($cmd) {
		switch ($cmd) {
			case 'configure':
			case 'searchCategories':
			case 'save':
			case 'svn':
				$this->$cmd();
				break;
		}
	}

	/**
	 * Configure screen
	 */
	public function configure() {
		$this->initConfigurationForm();
		$this->setFormValues();
		$this->tpl->setContent($this->form->getHTML());
	}

    /**
     * Save config
     */
    public function save() {
        global $tpl, $ilCtrl;
        $this->initConfigurationForm();
		$this->form->setValuesByPost();
        if ($this->form->checkInput()) {
            foreach ($this->getFields() as $key => $item) {
                $this->object->setValue($key, $this->form->getInput($key));
                if (is_array($item['subelements'])) {
                    foreach ($item['subelements'] as $subkey => $subitem) {
                        $this->object->setValue($key . '_' . $subkey, $this->form->getInput($key . '_' . $subkey));
                    }
                }
            }
	        $this->saveAdditionalFields();
            ilUtil::sendSuccess($this->pl->txt('conf_saved'), true);
            $ilCtrl->redirect($this, 'configure');
        } else {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHtml());
        }
    }

	protected function saveAdditionalFields(){
		$this->object->setValue('dont_delete_objects_in_category', implode(',', $this->form->getInput('dont_delete_objects_in_category')));

		$keywords = $this->form->getItemByPostVar('keywords')->getValue();
		$this->object->setValue('keywords', serialize(array_values($keywords)));

		$checkdates = $this->form->getItemByPostVar('checkdates')->getValue();
		$this->object->setValue('checkdates', serialize(array_values($checkdates)));
	}

    /**
     * Set form values
     */
    protected function setFormValues() {
        foreach ($this->getFields() as $key => $item) {
            $values[$key] = $this->object->getValue($key);
            if (is_array($item['subelements'])) {
                foreach ($item['subelements'] as $subkey => $subitem) {
                    $values[$key . '_' . $subkey] = $this->object->getValue($key . '_' . $subkey);
                }
            }
        }
	    $this->setAdditionalFormValues($values);
        $this->form->setValuesByArray($values);
    }

	protected function setAdditionalFormValues(&$values){
		$values['dont_delete_objects_in_category'] = $this->object->getValue('dont_delete_objects_in_category');
		$values['keywords'] = unserialize($this->object->getValue('keywords'));
		$values['checkdates'] = unserialize($this->object->getValue('checkdates'));
	}

    /**
	 * @return ilPropertyFormGUI
	 */
	protected function initConfigurationForm() {
		global $lng, $ilCtrl;
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		$this->form = new ilPropertyFormGUI();

		$this->initCustomConfigForm($this->form);

		foreach ($this->getFields() as $key => $item) {
			$field = new $item['type']($this->pl->txt($key), $key);
			if ($item['info']) {
				$field->setInfo($this->pl->txt($key . '_info'));
			}
			if (is_array($item['subelements'])) {
				foreach ($item['subelements'] as $subkey => $subitem) {
					$subfield = new $subitem['type']($this->pl->txt($key . '_' . $subkey), $key . '_' . $subkey);
					if ($subitem['info']) {
						$subfield->setInfo($this->pl->txt($key . '_info'));
					}
					$field->addSubItem($subfield);
				}
			}
			$this->form->addItem($field);
		}
		$this->form->addCommandButton('save', $lng->txt('save'));
		$this->form->setTitle($this->pl->txt('configuration'));
		$this->form->setFormAction($ilCtrl->getFormAction($this));

		return $this->form;
	}

	/**
	 * For additional form elements which are not easily configurable.
	 *
	 * @param ilPropertyFormGUI $form
	 */
	protected function initCustomConfigForm(&$form){
		$item = new ilCategoryMultiSelectInputGUI($this->pl->txt('dont_delete_objects_in_category'), 'dont_delete_objects_in_category');
		$item->setAjaxLink($this->ctrl->getLinkTarget($this, 'searchCategories'));
		$item->setMinimumInputLength(2);
		$form->addItem($item);

		$item = new ilMultipleTextInputGUI($this->pl->txt('keywords'), 'keywords', $this->pl->txt('keyword'));
		$form->addItem($item);

		$item = new ilMultiDateInputGUI($this->pl->txt('checkdates'), 'checkdates', $this->pl->txt('checkdates'));
		$form->addItem($item);
	}

    /**
     * Return the configuration fields
     * @return array
     */
    protected function getFields() {
        $this->fields = array(
			'delete_groups' => array(
				'type' => 'ilCheckboxInputGUI',
				'info' => false,
			),
			'delete_courses' => array(
				'type' => 'ilCheckboxInputGUI',
				'info' => false,
			),
			'delete_objects_in_days' => array(
				'type' => 'ilNumberInputGUI',
				'info' => false
			),
			'reminder_in_days' => array(
				'type' => 'ilNumberInputGUI',
				'info' => false
			),
			'reminder_title' => array(
				'type' => 'ilTextInputGUI',
				'info' => false
			),
			'reminder_content' => array(
				'type' => 'ilTextAreaInputGUI',
				'info' => true
			),

        );
        return $this->fields;
    }

	/**
	 * used for the ajax call from search categories.
	 */
	public function searchCategories() {
		global $ilDB;
		/** @var ilDB $ilDB */
		$ilDB = $ilDB;
		$term = $ilDB->quote('%'.$_GET['term'].'%', 'text');
		$page_limit = $ilDB->quote($_GET['page_limit'], 'integer');
		$query = "SELECT obj.obj_id, obj.title FROM object_data obj
		 LEFT JOIN object_translation trans ON trans.obj_id = obj.obj_id
		 WHERE obj.type = 'cat' and (obj.title LIKE $term OR trans.title LIKE $term)";
		$res = $ilDB->query($query);
		$result = array();
		while($row = $ilDB->fetchAssoc($res)){
			$result[] = array("id" => $row['obj_id'], "text" => $row['title']);
		}
		echo json_encode($result);
		exit;
	}

}
?>
