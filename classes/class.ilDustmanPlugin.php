<?php
include_once("./Services/Cron/classes/class.ilCronHookPlugin.php");
require_once './Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/class.ilDustmanConfig.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/class.ilDustmanCron.php';

/**
 * Class ilDustmanPlugin
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilDustmanPlugin extends ilCronHookPlugin {

	/**
	 * @var  ilDustmanCron
	 */
	protected static $instance;
	/**
	 * @var  ilDustmanConfig
	 */
	protected $configObject;


	/**
	 * @return ilDustmanCron[]
	 */
	public function getCronJobInstances() {
		$this->loadInstance();

		return array( self::$instance );
	}


	/**
	 * @param $a_job_id
	 * @return \ilDustmanCron
	 */
	public function getCronJobInstance($a_job_id) {
		if ($a_job_id == ilDustmanCron::DUSTMAN_ID) {
			$this->loadInstance();

			return self::$instance;
		}
	}


	/**
	 * Get Plugin Name. Must be same as in class name il<Name>Plugin
	 * and must correspond to plugins subdirectory name.
	 *
	 * Must be overwritten in plugin class of plugin
	 * (and should be made final)
	 *
	 * @return    string    Plugin Name
	 */
	function getPluginName() {
		return 'Dustman';
	}


	protected function loadInstance() {
		if (self::$instance === null) {
			self::$instance = new ilDustmanCron();
		}
	}


	/**
	 * @return string
	 */
	public function getConfigTableName() {
		return 'xdustman_config';
	}


	/**
	 * @return ilDustmanConfig
	 */
	public function getConfigObject() {
		if ($this->configObject === null) {
			$this->configObject = new ilDustmanConfig($this->getConfigTableName());
		}

		return $this->configObject;
	}
}
