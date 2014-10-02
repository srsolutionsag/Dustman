<?php

require_once './Services/Cron/classes/class.ilCronJobResult.php';

class ilDustmanResult extends ilCronJobResult {

	/**
	 * @param $status int
	 * @param $message string
	 * @param null $code string
	 */
	public function __construct($status, $message, $code = null) {
		$this->setStatus($status);
		$this->setMessage($message);
		$this->setCode($code);
	}
}


?>