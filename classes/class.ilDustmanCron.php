<?php

require_once './Services/Cron/classes/class.ilCronJob.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/Dustman/classes/class.ilDustmanStatus.php';
require_once './Services/Mail/classes/class.ilMimeMail.php';
require_once './Services/Link/classes/class.ilLink.php';
require_once './Services/Repository/classes/class.ilRepUtil.php';
require_once './Services/Membership/classes/class.ilParticipants.php';

/**
 * Class ilDustmanCron
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilDustmanCron extends ilCronJob {

	const DUSTMAN_ID = "xdust";
	/**
	 * @var  ilDustmanPlugin
	 */
	protected $pl;
	/**
	 * @var  int[]
	 */
	protected $category_ids;
	/**
	 * @var  string[]
	 */
	protected $keywords;
	/**
	 * @var  string[]
	 */
	protected $checkdates;
	/**
	 * @var  bool
	 */
	protected $deleteGroups;
	/**
	 * @var  bool
	 */
	protected $deleteCourses;
	/**
	 * @var  int
	 */
	protected $deleteAfterDays;
	/**
	 * @var  int
	 */
	protected $reminderBeforeDays;
	/**
	 * @var  string
	 */
	protected $reminderTitle;
	/**
	 * @var  string
	 */
	protected $reminderBody;
	/**
	 * @var  ilDB
	 */
	protected $db;
	/**
	 * @var  ilLog
	 */
	protected $log;


	public function __construct() {
		global $ilDB, $ilLog;
		$this->db = $ilDB;
		$this->pl = new ilDustmanPlugin();
		$this->log = $ilLog;
		$this->readConfig();
	}


	protected function readConfig() {
		$config = $this->pl->getConfigObject();

		$this->category_ids = explode(',', $config->getValue('dont_delete_objects_in_category'));
		$this->keywords = unserialize($config->getValue('keywords'));
		$this->checkdates = unserialize($config->getValue('checkdates'));
		$this->deleteGroups = (bool)$config->getValue('delete_groups');
		$this->deleteCourses = (bool)$config->getValue('delete_courses');
		$this->deleteAfterDays = (int)$config->getValue('delete_objects_in_days');
		$this->reminderBeforeDays = (int)$config->getValue('reminder_in_days');
		$this->reminderTitle = $config->getValue('reminder_title');
		$this->reminderBody = $config->getValue('reminder_content');
	}


	/**
	 * Get id
	 *
	 * @return string
	 */
	public function getId() {
		return self::DUSTMAN_ID;
	}


	/**
	 * Is to be activated on "installation"
	 *
	 * @return boolean
	 */
	public function hasAutoActivation() {
		return true;
	}


	/**
	 * Can the schedule be configured?
	 *
	 * @return boolean
	 */
	public function hasFlexibleSchedule() {
		return true;
	}


	/**
	 * Get schedule type
	 *
	 * @return int
	 */
	public function getDefaultScheduleType() {
		return self::SCHEDULE_TYPE_DAILY;
	}


	/**
	 * Get schedule value
	 *
	 * @return int|array
	 */
	function getDefaultScheduleValue() {
		return 1;
	}


	/**
	 * Run job
	 *
	 * @return ilCronJobResult
	 */
	public function run() {
		if ($this->isCheckDate()) {
			$this->log->write("[Dustman] Today some objects get deleted!");
			$this->deleteObjects();
		} else {
			$this->log->write("[Dustman] Today is not a deletion day.");
		}

		if ($this->isMailDate()) {
			$this->log->write("[Dustman] In {$this->reminderBeforeDays} days some objects will be deleted. Dustman sends reminder E-Mails.");
			$this->writeEmails();
		} else {
			$this->log->write("[Dustman] Today plus {$this->reminderBeforeDays} days is not a deletion Day. Dustman does not send any emails.");
		}

		return new ilDustmanResult(ilDustmanResult::STATUS_OK, "Cron job terminated successfully.");
	}


	protected function writeEmails() {
		$objects = $this->getDeletableObjectsInDays();
		foreach ($objects as $object) {
			$this->writeEmail($object);
		}
	}


	protected function deleteObjects() {
		$objects = $this->getDeletableObjects();
		foreach ($objects as $object) {
			$this->deleteObject($object);
		}
	}


	protected function deleteObject($object) {
		try {
            $this->log->write("[Dustman] Deleting object: " . implode(', ', $object));
            ilRepUtil::deleteObjects(null, array( $object['ref_id'] ));
        } catch (Exception $e) {
            $this->log->write($e->getMessage() . $e->getTraceAsString());
        }
	}


	protected function writeEmail($object) {
		$this->log->write("[Dustman] Writing email that obj " . $object['title'] . " (" . $object['obj_id']
		                  . ") will be deleted in {$this->reminderBeforeDays} days.");
		$participants = ilParticipants::getInstanceByObjId($object['obj_id']);
		$admins = $participants->getAdmins();
		foreach ($admins as $admin) {
			$this->writeEmailToUser($admin, $object);
		}
	}


	protected function writeEmailToUser($user_id, $object) {
		$user = new ilObjUser($user_id);
		$email_address = $user->getEmail();
		$link = ilLink::_getStaticLink($object['ref_id'], $object['type']);

		$this->log->write("[Dustman] Send Email to: " . $email_address);

		$objecttype = $object['type'] == 'crs' ? 'Kurs' : 'Gruppe';
		$body = str_replace('[Objekttyp]', $objecttype, $this->reminderBody);
		$body = str_replace('[Titel]', $object['title'], $body);
		$body = str_replace('[Link]', $link, $body);

		$mail = new ilMimeMail();
		$mail->autoCheck(false);
		$mail->From($this->pl->getConfigObject()->getValue('email'));
		$mail->To($email_address);
		$mail->Subject($this->reminderTitle);
		$mail->Body($body);
		$mail->Send();
	}


	/**
	 * @return array
	 */
	protected function getDeletableObjects() {
		$prefiltered_objects = $this->getPrefilteredObjectsAsArray();
		$objects = array();
		foreach ($prefiltered_objects as $obj) {
			if (!$this->inCategories($obj['ref_id'])) {
				$objects[] = $obj;
			}
		}

		return $objects;
	}


	/**
	 * @return array
	 */
	protected function getDeletableObjectsInDays() {
		$prefiltered_objects = $this->getPrefilteredObjectsPrequel();
		$objects = array();
		foreach ($prefiltered_objects as $obj) {
			if (!$this->inCategories($obj['ref_id'])) {
				$objects[] = $obj;
			}
		}

		return $objects;
	}


	/**
	 * @return bool Returns true if today + this->reminderBeforeDays is a checkdate
	 */
	protected function isMailDate() {
		$date = new DateTime();
		$days = $this->reminderBeforeDays;
		$intval = new DateInterval('P' . $days . 'D');
		$date->add($intval);

		return $this->isCheckDate($date);
	}


	/**
	 * @param $dateTime DateTime Default: today
	 *
	 * @return bool Returns true if dateTime is a checkdate
	 */
	protected function isCheckDate($dateTime = null) {
		if ($dateTime === null) {
			$dateTime = new DateTime();
		}

		$day = $dateTime->format('d');
		$month = $dateTime->format('m');

		foreach ($this->checkdates as $checkdate) {
			$checkdate = explode('/', $checkdate);
			if (count($checkdate) != 2) {
				$this->log->write("[WARNING - Dustman Plugin] A Date is Malformed!");
				continue;
			}
			if (intval($checkdate[0]) == intval($day) && intval($checkdate[1]) == intval($month)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $ref_id int
	 *
	 * @return bool Returns true iff the ref_id is somewhere in a category in this->category_ids.
	 */
	protected function inCategories($ref_id) {
		global $tree;

        $obj_id_path = array();
		$path = $tree->getNodePath($ref_id);
		if (is_array($path)) {
			foreach ($path as $node) {
				$obj_id_path[] = $node['obj_id'];
			}
		}

		$intersect = array_intersect($this->category_ids, $obj_id_path);

		return count($intersect) > 0;
	}


	/**
	 * @param $days int all crs/grp that are older than $days days are returned. With filter of types, keywords.
	 *
	 * @return array
	 */
	protected function getPrefilteredObjectsInDays($days) {
		$in = $this->getInTypeStatement();
		$keywords = $this->getKeywordsStatement();
		$in_days = $this->db->quote($days, 'integer');

		$query = "
		SELECT obj.obj_id, obj.title, ref.ref_id, obj.type FROM object_data obj
		INNER JOIN object_reference ref ON ref.obj_id = obj.obj_id AND ref.deleted IS NULL
		WHERE
				$in
			AND obj.create_date < DATE_SUB(NOW(), INTERVAL $in_days DAY)
			AND NOT EXISTS (
					SELECT * FROM il_meta_keyword keyword WHERE keyword.obj_id = obj.obj_id AND $keywords
				)
			";

		$res = $this->db->query($query);
		$set = array();
		while ($row = $this->db->fetchAssoc($res)) {
			$set[] = $row;
		}

		return $set;
	}


	/**
	 * This will give you the objects that are within the time range and and do not contain the keywords.
	 *
	 * @return array The object as array with ref_id, obj_id and title.
	 */
	protected function getPrefilteredObjectsAsArray() {
		return $this->getPrefilteredObjectsInDays($this->deleteAfterDays);
	}


	protected function getPrefilteredObjectsPrequel() {
		return $this->getPrefilteredObjectsInDays($this->deleteAfterDays - $this->reminderBeforeDays);
	}


	/**
	 * @return string
	 */
	protected function getInTypeStatement() {
		$in = array();
		if ($this->deleteGroups) {
			$in[] = 'grp';
		}
		if ($this->deleteCourses) {
			$in[] = 'crs';
		}

		return $this->db->in('obj.type', $in, false, 'text');
	}


	/**
	 * @return string
	 */
	protected function getKeywordsStatement() {
		return $this->db->in('keyword.keyword', $this->keywords, false, 'text');
	}
}
