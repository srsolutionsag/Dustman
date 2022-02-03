<?php declare(strict_types=1);

/**
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanRemovalCronJob extends ilCronJob
{
    /**
     * @var string cron job id.
     */
    public const JOB_ID = ilDustmanPlugin::PLUGIN_ID . '_removal_job';

    /**
     * @var ilDustmanPlugin
     */
    protected $plugin;
    /**
     * @var ilDustmanRepository
     */
    protected $repository;
    /**
     * @var ilLogger
     */
    protected $logger;
    /**
     * @var array<string, mixed>
     */
    protected $config;

    /**
     * @param ilDustmanPlugin     $plugin
     * @param ilDustmanRepository $repository
     * @param ilLogger            $logger
     */
    public function __construct(
        ilDustmanPlugin $plugin,
        ilDustmanRepository $repository,
        ilLogger $logger
    ) {
        $this->plugin = $plugin;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->config = $this->loadConfig();
    }

    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return ilDustmanPlugin::PLUGIN_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getDescription() : string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return self::JOB_ID;
    }

    /**
     * @inheritDoc
     */
    public function hasAutoActivation() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_DAILY;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleValue() : int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function run() : ilCronJobResult
    {
        if ($this->isCheckDate()) {
            $this->dic->logger()->root()->info('[Dustman] Today some objects get deleted!');
            $this->deleteObjects();
        } else {
            $this->dic->logger()->root()->info('[Dustman] Today is not a deletion day.');
        }

        if ($this->isMailDate()) {
            $this->dic->logger()->root()->info("[Dustman] In {$this->reminderBeforeDays} days some objects will be deleted. Dustman sends reminder E-Mails.");
            $this->writeEmails();
        } else {
            $this->dic->logger()->root()->info("[Dustman] Today plus {$this->reminderBeforeDays} days is not a deletion Day. Dustman does not send any emails.");
        }

        return new ilDustmanCronJobResult(ilDustmanCronJobResult::STATUS_OK, 'Cron job terminated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadConfig() : array
    {
        return [
            ilDustmanConfig::CNF_DELETE_GROUPS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_GROUPS, false),
            ilDustmanConfig::CNF_DELETE_COURSES => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_COURSES, false),
            ilDustmanConfig::CNF_DELETE_IN_DAYS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_IN_DAYS, 0),
            ilDustmanConfig::CNF_REMINDER_IN_DAYS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_IN_DAYS, 0),
            ilDustmanConfig::CNF_REMINDER_TITLE => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_TITLE, null),
            ilDustmanConfig::CNF_REMINDER_CONTENT => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, null),
            ilDustmanConfig::CNF_REMINDER_EMAIL => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_EMAIL, null),
            ilDustmanConfig::CNF_FILTER_CATEGORIES => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, []),
            ilDustmanConfig::CNF_FILTER_KEYWORDS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, []),
            ilDustmanConfig::CNF_FILTER_DATES => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, null),

        ];
    }



    protected function writeEmails()
    {
        $objects = $this->getDeletableObjectsInDays();
        foreach ($objects as $object) {
            $this->writeEmail($object);
        }
    }

    protected function deleteObjects()
    {
        $objects = $this->getDeletableObjects();
        foreach ($objects as $object) {
            $this->deleteObject($object);
        }
    }

    protected function deleteObject($object)
    {
        try {
            $this->dic->logger()->root()->warning('[Dustman] Deleting object: ' . implode(', ', $object));
            ilRepUtil::deleteObjects(null, array($object['ref_id']));
        } catch (Exception $e) {
            $this->dic->logger()->root()->error($e->getMessage() . $e->getTraceAsString());
        }
    }

    protected function writeEmail($object)
    {
        $this->dic->logger()->root()->write('[Dustman] Writing email that obj ' . $object['title'] . " (" . $object['obj_id']
            . ") will be deleted in {$this->reminderBeforeDays} days.");
        $participants = ilParticipants::getInstanceByObjId($object['obj_id']);
        $admins       = $participants->getAdmins();
        foreach ($admins as $admin) {
            $this->writeEmailToUser($admin, $object);
        }
    }

    protected function writeEmailToUser($user_id, $object)
    {
        global $DIC;

        $q = "SELECT * FROM object_data JOIN usr_data ON usr_data.usr_id = object_data.obj_id WHERE obj_id = %s";
        $r = $DIC->database()->queryF($q, ['integer'], [(int) $user_id]);
        $d = $DIC->database()->fetchObject($r);
        if (!isset($d->usr_id) || is_null($d->usr_id)) {
            // User no longer exists, no mail is sent.
            return;
        }

        $user          = new ilObjUser($user_id);
        $email_address = $user->getEmail();
        $link          = ilLink::_getStaticLink($object['ref_id'], $object['type']);

        $this->dic->logger()->root()->warning("[Dustman] Send Email to: " . $email_address);

        $objecttype = $object['type'] === 'crs' ? 'Kurs' : 'Gruppe';
        $body       = str_replace('[Objekttyp]', $objecttype, $this->reminderBody);
        $body       = str_replace('[Titel]', $object['title'], $body);
        $body       = str_replace('[Link]', $link, $body);

        $senderFactory = $DIC["mail.mime.sender.factory"];
        $sender        = $senderFactory->system();

        $mail = new ilMimeMail();
        $mail->From($sender);
        $mail->To($email_address);
        $mail->Subject($this->reminderTitle);
        $mail->Body($body);
        $mail->Send();
    }

    /**
     * @return array
     */
    protected function getDeletableObjects()
    {
        $prefiltered_objects = $this->getPrefilteredObjectsAsArray();
        $objects             = array();
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
    protected function getDeletableObjectsInDays()
    {
        $prefiltered_objects = $this->getPrefilteredObjectsPrequel();
        $objects             = array();
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
    protected function isMailDate()
    {
        $date   = new DateTime();
        $days   = $this->reminderBeforeDays;
        $intval = new DateInterval('P' . $days . 'D');
        $date->add($intval);

        return $this->isCheckDate($date);
    }

    /**
     * @param $dateTime DateTime Default: today
     * @return bool Returns true if dateTime is a checkdate
     */
    protected function isCheckDate($dateTime = null)
    {
        if ($dateTime === null) {
            $dateTime = new DateTime();
        }

        $day   = $dateTime->format('d');
        $month = $dateTime->format('m');

        foreach ($this->checkdates as $checkdate) {
            $checkdate = explode('/', $checkdate);
            if (count($checkdate) != 2) {
                $this->dic->logger()->root()->write("[WARNING - Dustman Plugin] A Date is Malformed!");
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
     * @return bool Returns true iff the ref_id is somewhere in a category in this->category_ids.
     */
    protected function inCategories($ref_id)
    {
        global $tree;

        $obj_id_path = array();
        $path        = $tree->getNodePath($ref_id);
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
     * @return array
     */
    protected function getPrefilteredObjectsInDays($days)
    {
        $in       = $this->getInTypeStatement();
        $keywords = $this->getKeywordsStatement();
        $in_days  = $this->dic->database()->quote($days, 'integer');

        $query = "
		SELECT obj.obj_id, obj.title, ref.ref_id, obj.type FROM object_data obj
		INNER JOIN object_reference ref ON ref.obj_id = obj.obj_id AND ref.deleted IS NULL
		WHERE
				$in
			AND obj.create_date < DATE_SUB(NOW(), INTERVAL $in_days DAY)
			AND NOT EXISTS (
					SELECT * FROM il_meta_keyword WHERE il_meta_keyword.obj_id = obj.obj_id AND $keywords
				)
			";

        $res = $this->dic->database()->query($query);
        $set = array();
        while ($row = $this->dic->database()->fetchAssoc($res)) {
            $set[] = $row;
        }

        return $set;
    }

    /**
     * This will give you the objects that are within the time range and and do not contain the keywords.
     * @return array The object as array with ref_id, obj_id and title.
     */
    protected function getPrefilteredObjectsAsArray()
    {
        return $this->getPrefilteredObjectsInDays($this->deleteAfterDays);
    }

    protected function getPrefilteredObjectsPrequel()
    {
        return $this->getPrefilteredObjectsInDays($this->deleteAfterDays - $this->reminderBeforeDays);
    }

    /**
     * @return string
     */
    protected function getInTypeStatement()
    {
        $in = array();
        if ($this->deleteGroups) {
            $in[] = 'grp';
        }
        if ($this->deleteCourses) {
            $in[] = 'crs';
        }

        return $this->dic->database()->in('obj.type', $in, false, 'text');
    }

    /**
     * @return string
     */
    protected function getKeywordsStatement()
    {
        return $this->dic->database()->in('il_meta_keyword.keyword', $this->keywords, false, 'text');
    }
}
